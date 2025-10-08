<?php
namespace class\services;

class NotificationService {
    private $db;
    private $oneSignalConfig;
    private $smsConfig;

    public function __construct($db) {
        $this->db = $db;
        $this->oneSignalConfig = [
            'appId' => '2ec0cda9-7643-471c-9b3f-f607768d243d',
            'apiKey' => 'os_v2_app_f3am3klwindrzgz76ydxndjehwojhjtgmfdun24d3inh4lbrvlvtrxpvnvlxuj3p3a44ykijqmlz53ovuvial3twmiwtwzstfzcyo5y'
        ];
        $this->smsConfig = [
            'sender' => 'NASUOOUTH',
            'apiKey' => 'TLJJ8KJkyaxODiQB8Fpvv4Umni0YaiWDRAMFzUcPMgLQCmjGjsBPYDC0EfRuYz',
            'endpoint' => 'https://v3.api.termii.com/api/sms/send'
        ];
    }

    private function getTransactionDetails($memberId, $periodId) {
        $query = "SELECT tlb_mastertransaction.staff_id,tbpayrollperiods.Periodid,
                CONCAT(tbl_personalinfo.Lname, ' , ', tbl_personalinfo.Fname, ' ', IFNULL(tbl_personalinfo.Mname, '')) AS namess,
                tbl_personalinfo.MobilePhone,
								concat(LEFT(tbpayrollperiods.PhysicalMonth, 3),' -',tbpayrollperiods.PhysicalYear) as PayrollPeriod,
                SUM(tlb_mastertransaction.Contribution) as Contribution,
                SUM(tlb_mastertransaction.loanAmount) as loanAmount,
                SUM(tlb_mastertransaction.loanRepayment) as loanRepayment,
                (
                    SELECT 
                        (SUM(m2.loanAmount) + SUM(m2.interest))- SUM(m2.loanRepayment)
                    FROM tlb_mastertransaction m2
                    WHERE m2.staff_id = tlb_mastertransaction.staff_id
                    AND m2.periodid <= tlb_mastertransaction.periodid
                ) as loanBalance,
                (
                    SELECT 
                        SUM(m2.Contribution)
                    FROM tlb_mastertransaction m2
                    WHERE m2.staff_id = tlb_mastertransaction.staff_id
                    AND m2.periodid <= tlb_mastertransaction.periodid
                ) as welfareContribution,
                SUM(tlb_mastertransaction.Contribution + 
                    tlb_mastertransaction.loanRepayment ) as total
            FROM tlb_mastertransaction 
            INNER JOIN tbl_personalinfo ON tlb_mastertransaction.staff_id = tbl_personalinfo.staff_id
            LEFT JOIN tbpayrollperiods ON tlb_mastertransaction.periodid = tbpayrollperiods.Periodid             
            WHERE tbl_personalinfo.staff_id = :memberId 
            AND tlb_mastertransaction.periodid = :periodId
            GROUP BY tbpayrollperiods.Periodid 
            ORDER BY tbpayrollperiods.Periodid DESC 
            LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':memberId', $memberId, \PDO::PARAM_STR);
        $stmt->bindValue(':periodId', $periodId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function logNotification($memberId, $message,$title = 'Transaction Alert') {
        $query = "INSERT INTO notifications 
                  (staff_id, message, created_at, status, title) 
                  VALUES 
                  (:memberId, :message, NOW(), 'unread', :title)";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':memberId', $memberId, \PDO::PARAM_STR);
        $stmt->bindValue(':message', $message, \PDO::PARAM_STR);
        $stmt->bindValue(':title', $title, \PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function sendTransactionNotification($memberId, $periodId) {
        try {
            // Get transaction details
            $transactionData = $this->getTransactionDetails($memberId, $periodId);
            if (!$transactionData) {
                throw new \Exception("No transaction data found");
            }

            // Format message
            $message = $this->formatTransactionMessage($transactionData);

            // Send notifications
            $smsResult = $this->sendSMS($transactionData['MobilePhone'], $message);

            if (!empty($transactionData['onesignal_id'])) {
                $this->sendPushNotification(
                    $transactionData['onesignal_id'],
                    "Transaction Update",
                    $message
                );
            }

            // Log notification
            $this->logNotification($memberId, $message);

            return true;
        } catch (\Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            return false;
        }
    }

    private function formatTransactionMessage($data) {
        return sprintf(
            "NASUWEL ACCT. BAL., MONTHLY CONTR: %s\n" .
            "WELFARE SAVINGS: %s\n" .
            "WELFARE BALANCE: %s\n" .
            "LOAN : %s\n" .
            "LOAN BALANCE: %s\n" .
            "AS AT: %s ENDING\n",
            number_format(floatval($data['total']), 2, '.', ','),
            number_format(floatval($data['Contribution']), 2, '.', ','),
            number_format(floatval($data['welfareContribution']), 2, '.', ','),
            number_format(floatval($data['loanAmount']), 2, '.', ','),
            number_format(floatval($data['loanBalance']), 2, '.', ','),
            $data['PayrollPeriod']
        );
    }

    private function sendSMS($phone, $message) {
        if (empty($phone)) {
            throw new \Exception("Phone number is required");
        }

        $phone = $this->formatPhoneNumber($phone);

        $data = [
            "api_key" => $this->smsConfig['apiKey'],
            "to" => $phone,
            "from" => $this->smsConfig['sender'],
            "sms" => $message,
            "type" => "plain",
            "channel" => "generic"
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->smsConfig['endpoint'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json"
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("Curl error: $error");
        }

        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($httpCode !== 200 && $httpCode !== 201) {
            $errorMessage = isset($responseData['message']) ? $responseData['message'] : $response;
            throw new \Exception("SMS API Error ($httpCode): $errorMessage");
        }

        return $responseData;
    }

    private function sendPushNotification($playerId, $title, $message) {
        if (empty($playerId)) {
            return false;
        }

        $fields = [
            'app_id' => $this->oneSignalConfig['appId'],
            'include_player_ids' => [$playerId],
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'priority' => 10
        ];

        $ch = curl_init('https://onesignal.com/api/v1/notifications');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . $this->oneSignalConfig['apiKey']
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($fields),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("OneSignal API Error: $response");
        }

        return json_decode($response, true);
    }

    private function formatPhoneNumber($phone) {
        $phone = trim($phone);
        if (substr($phone, 0, 1) === '0') {
            return '234' . substr($phone, 1);
        } elseif (substr($phone, 0, 1) === '+') {
            return substr($phone, 1);
        }
        return $phone;
    }
}