<?php
$json_url = "http://api.ebulksms.com:80/sendsms.json";
$xml_url = "http://api.ebulksms.com:80/sendsms.xml";
$http_get_url = "http://api.ebulksms.com:80/sendsms";
$username = '';
$apikey = '';


// -------------------------------------------------------------------- \\
//termi API
function doSendMessage1($to, $message)
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


//if (isset($_POST['button'])) {

$username = 'nasuoouth@gmail.com'; //$_POST['username'];
$apikey = '5dc386632074fd26cf1778efdeb517966b6a1b76'; //$_POST['apikey'];
$sendername = substr($_POST['SentMessageDisplayname'], 0, 11);
$recipients = $_POST['SentMessageRecipient'];
$message = $_POST['SentMessageMessage'];
$flash = 0;

$message = htmlspecialchars($message, ENT_NOQUOTES, 'UTF-8');
$message = substr($_POST['SentMessageMessage'], 0, 320);

// 	Termii plateform
$result =   doSendMessage1($recipients, $message);


#Use the next line for HTTP POST with JSON
//  $result = useJSON($json_url, $username, $apikey, $flash, $sendername, $message, $recipients);
#Uncomment the next line and comment the one above if you want to use HTTP POST with XML
//$result = useXML($xml_url, $username, $apikey, $flash, $sendername, $message, $recipients);

#Uncomment the next line and comment the ones above if you want to use simple HTTP GET
//$result = useHTTPGet($http_get_url, $username, $apikey, $flash, $sendername, $message, $recipients);
//}

function useJSON($url, $username, $apikey, $flash, $sendername, $messagetext, $recipients)
{
    $gsm = array();
    $country_code = '234';
    $arr_recipient = explode(',', $recipients);
    foreach ($arr_recipient as $recipient) {
        $mobilenumber = trim($recipient);
        if (substr($mobilenumber, 0, 1) == '0') {
            $mobilenumber = $country_code . substr($mobilenumber, 1);
        } elseif (substr($mobilenumber, 0, 1) == '+') {
            $mobilenumber = substr($mobilenumber, 1);
        }
        $generated_id = uniqid('int_', false);
        $generated_id = substr($generated_id, 0, 30);
        $gsm['gsm'][] = array('msidn' => $mobilenumber, 'msgid' => $generated_id);
    }
    $message = array(
        'sender' => $sendername,
        'messagetext' => $messagetext,
        'flash' => "{$flash}",
    );

    $request = array('SMS' => array(
        'auth' => array(
            'username' => $username,
            'apikey' => $apikey
        ),
        'message' => $message,
        'recipients' => $gsm
    ));
    $json_data = json_encode($request);
    if ($json_data) {
        $response = doPostRequest($url, $json_data, array('Content-Type: application/json'));
        $result = json_decode($response);
        return $result->response->status;
    } else {
        return false;
    }
}

function useXML($url, $username, $apikey, $flash, $sendername, $messagetext, $recipients)
{
    $country_code = '234';
    $arr_recipient = explode(',', $recipients);
    $count = count($arr_recipient);
    $msg_ids = array();
    $recipients = '';

    $xml = new SimpleXMLElement('<SMS></SMS>');
    $auth = $xml->addChild('auth');
    $auth->addChild('username', $username);
    $auth->addChild('apikey', $apikey);

    $msg = $xml->addChild('message');
    $msg->addChild('sender', $sendername);
    $msg->addChild('messagetext', $messagetext);
    $msg->addChild('flash', $flash);

    $rcpt = $xml->addChild('recipients');
    for ($i = 0; $i < $count; $i++) {
        $generated_id = uniqid('int_', false);
        $generated_id = substr($generated_id, 0, 30);
        $mobilenumber = trim($arr_recipient[$i]);
        if (substr($mobilenumber, 0, 1) == '0') {
            $mobilenumber = $country_code . substr($mobilenumber, 1);
        } elseif (substr($mobilenumber, 0, 1) == '+') {
            $mobilenumber = substr($mobilenumber, 1);
        }
        $gsm = $rcpt->addChild('gsm');
        $gsm->addchild('msidn', $mobilenumber);
        $gsm->addchild('msgid', $generated_id);
    }
    $xmlrequest = $xml->asXML();

    if ($xmlrequest) {
        $result = doPostRequest($url, $xmlrequest, array('Content-Type: application/xml'));
        $xmlresponse = new SimpleXMLElement($result);
        return $xmlresponse->status;
    }
    return false;
}

//Function to connect to SMS sending server using HTTP GET
function useHTTPGet($url, $username, $apikey, $flash, $sendername, $messagetext, $recipients)
{
    $query_str = http_build_query(array('username' => $username, 'apikey' => $apikey, 'sender' => $sendername, 'messagetext' => $messagetext, 'flash' => $flash, 'recipients' => $recipients));
    return file_get_contents("{$url}?{$query_str}");
}

//Function to connect to SMS sending server using HTTP POST
function doPostRequest($url, $arr_params, $headers = array('Content-Type: application/x-www-form-urlencoded'))
{
    $response = array();
    $final_url_data = $arr_params;
    if (is_array($arr_params)) {
        $final_url_data = http_build_query($arr_params, '', '&');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $final_url_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response['body'] = curl_exec($ch);
    $response['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $response['body'];
}

$result = (json_decode($result));

if ($result->message == 'Successfully Sent') {

    echo 'Username and Password for New member Sent Succesfully';
} else {
    echo $result . 'ERROR';
}
