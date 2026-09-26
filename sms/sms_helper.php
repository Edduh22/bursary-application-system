<?php

/*
|--------------------------------------------------------------------------
| SMS HELPER
|--------------------------------------------------------------------------
| Africa's Talking Sandbox SMS integration
|--------------------------------------------------------------------------
*/

function sendSMS($phone, $message)
{
    /*
    |--------------------------------------------------------------------------
    | AFRICA'S TALKING CONFIGURATION
    |--------------------------------------------------------------------------
    */

    $username = "sandbox";

    /*
    | IMPORTANT:
    | Put your NEW sandbox API key here.
    | Do NOT share it publicly.
    */

    $apiKey = "atsk_7785e7b6f8ea187b4b5862a236b7b4675fcb1323a2d64c1b57f12446aa4dfac019011380";


    /*
    |--------------------------------------------------------------------------
    | VALIDATE PHONE NUMBER
    |--------------------------------------------------------------------------
    */

    $phone = trim($phone);

    if ($phone === "") {
        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | CONVERT KENYAN PHONE NUMBERS
    |--------------------------------------------------------------------------
    |
    | 0712345678  -> +254712345678
    | 254712345678 -> +254712345678
    | +254712345678 -> +254712345678
    |
    */

    if (preg_match('/^07\d{8}$/', $phone)) {

        $phone = "+254" . substr($phone, 1);

    } elseif (preg_match('/^2547\d{8}$/', $phone)) {

        $phone = "+" . $phone;

    } elseif (preg_match('/^\+2547\d{8}$/', $phone)) {

        // Already correct

    } else {

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | AFRICA'S TALKING SANDBOX API
    |--------------------------------------------------------------------------
    */

    $url = "https://api.sandbox.africastalking.com/version1/messaging";


    /*
    |--------------------------------------------------------------------------
    | POST DATA
    |--------------------------------------------------------------------------
    */

    $data = http_build_query([
        "username" => $username,
        "to"       => $phone,
        "message"  => $message
    ]);


    /*
    |--------------------------------------------------------------------------
    | CURL
    |--------------------------------------------------------------------------
    */

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        $data
    );

    curl_setopt(
        $ch,
        CURLOPT_RETURNTRANSFER,
        true
    );

    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        [
            "Accept: application/json",
            "Content-Type: application/x-www-form-urlencoded",
            "apiKey: " . $apiKey
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | EXECUTE REQUEST
    |--------------------------------------------------------------------------
    */

    $response = curl_exec($ch);


    /*
    |--------------------------------------------------------------------------
    | CURL ERROR
    |--------------------------------------------------------------------------
    */

    if ($response === false) {

        curl_close($ch);

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | HTTP STATUS
    |--------------------------------------------------------------------------
    */

    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


    curl_close($ch);


    /*
    |--------------------------------------------------------------------------
    | DECODE RESPONSE
    |--------------------------------------------------------------------------
    */

    $result = json_decode(
        $response,
        true
    );


    /*
    |--------------------------------------------------------------------------
    | CHECK API RESPONSE
    |--------------------------------------------------------------------------
    */

    if (
        $httpCode >= 200 &&
        $httpCode < 300 &&
        isset($result["SMSMessageData"]["Recipients"]) &&
        is_array($result["SMSMessageData"]["Recipients"])
    ) {

        foreach (
            $result["SMSMessageData"]["Recipients"]
            as $recipient
        ) {

            /*
            | 100 = Processed
            | 101 = Sent
            | 102 = Queued
            */

            if (
                isset($recipient["statusCode"]) &&
                in_array(
                    (int) $recipient["statusCode"],
                    [100, 101, 102],
                    true
                )
            ) {
                return true;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | FAILED
    |--------------------------------------------------------------------------
    */

    return false;
}