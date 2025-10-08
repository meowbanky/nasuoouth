<?php
function doSendMessage($to, $message)
{
    $curl = curl_init();
    $country_code = '234';
    $mobilenumber = trim($to);
    if (substr($mobilenumber, 0, 1) == '0') {
        $mobilenumber = $country_code . substr($mobilenumber, 1);
    } elseif (substr($mobilenumber, 0, 1) == '+') {
        $mobilenumber = substr($mobilenumber, 1);
    }

    $data = array(
        "to" => [$mobilenumber], "from" => "NASUOOUTH",
        "sms" => $message, "type" => "plain", "channel" => "generic", "api_key" => "TLJJ8KJkyaxODiQB8Fpvv4Umni0YaiWDRAMFzUcPMgLQCmjGjsBPYDC0EfRuYz"
    );

    $post_data = json_encode($data);

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.ng.termii.com/api/sms/send/bulk',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}
