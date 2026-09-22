<?php

function testEndpoint($url, $isPost = false, $postData = [])
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_NOPROXY, '*'); // Direct connection
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if ($isPost) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    }

    $response = curl_exec($ch);

    $result = [
        'url' => $url,
        'status' => 'FAILED',
        'error' => curl_error($ch),
        'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        'content_type' => curl_getinfo($ch, CURLINFO_CONTENT_TYPE),
        'headers' => '',
        'body' => '',
    ];

    if ($response !== false) {
        $result['status'] = 'CONNECTED';
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $result['headers'] = substr($response, 0, $headerSize);
        $result['body'] = substr($response, $headerSize);

        // Truncate body for safety
        if (strlen($result['body']) > 500) {
            $result['body'] = substr($result['body'], 0, 500).'... [TRUNCATED]';
        }
    } else {
        if (strpos($result['error'], 'Connection refused') !== false) {
            $result['status'] = 'REFUSED';
        } elseif (strpos($result['error'], 'timed out') !== false) {
            $result['status'] = 'TIMEOUT';
        } elseif (strpos($result['error'], 'SSL') !== false) {
            $result['status'] = 'SSL ERROR';
        }
    }

    curl_close($ch);

    return $result;
}

$endpoints = [
    'http://10.172.172.2',
    'http://10.172.172.2/api',
    'https://10.172.172.2',
    'https://10.172.172.2/api',
    'http://10.172.172.2:8866/api',
];

echo "==================================================\n";
echo "NETWORK & HTTP TESTS\n";
echo "==================================================\n";

foreach ($endpoints as $ep) {
    $res = testEndpoint($ep);
    echo str_pad($ep.':', 35).' '.$res['status'].' (HTTP '.$res['http_code'].")\n";
    if ($res['status'] === 'CONNECTED') {
        echo '   Content-Type: '.$res['content_type']."\n";
        // Extract server header
        if (preg_match('/Server: (.*)/i', $res['headers'], $matches)) {
            echo '   Server: '.trim($matches[1])."\n";
        }
        if (preg_match('/Location: (.*)/i', $res['headers'], $matches)) {
            echo '   Redirect: '.trim($matches[1])."\n";
        }
        if ($res['http_code'] != 200 && $res['http_code'] != 301 && $res['http_code'] != 302) {
            echo '   Body: '.trim(str_replace(["\n", "\r"], ' ', $res['body']))."\n";
        }
    } else {
        echo '   Error: '.$res['error']."\n";
    }
    echo "\n";
}

echo "==================================================\n";
echo "LOGIN ENDPOINT TESTS\n";
echo "==================================================\n";

$loginEndpoints = [
    'http://10.172.172.2/api/login',
    'https://10.172.172.2/api/login',
    'http://10.172.172.2:8866/api/login',
];

// Read credentials from env to test login
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$email = $_ENV['SMPP_API_EMAIL'] ?? 'admin@example.com';
$password = $_ENV['SMPP_API_PASSWORD'] ?? 'password';

$postData = [
    'email' => $email,
    'username' => $email,
    'password' => $password,
];

foreach ($loginEndpoints as $ep) {
    $res = testEndpoint($ep, true, $postData);
    echo str_pad($ep.':', 35).' '.$res['status'].' (HTTP '.$res['http_code'].")\n";
    if ($res['status'] === 'CONNECTED') {
        echo '   Content-Type: '.$res['content_type']."\n";
        $body = trim($res['body']);
        echo '   Response Body: '.(strlen($body) > 100 ? substr($body, 0, 100).'...' : $body)."\n";

        $json = json_decode($body, true);
        if ($json !== null) {
            echo "   Is JSON: Yes\n";
            if (isset($json['token'])) {
                echo "   Token field: 'token'\n";
                echo '   Token returned: Yes (Length: '.strlen($json['token']).")\n";
                echo "   Authentication result: SUCCESS\n";
            } elseif (isset($json['access_token'])) {
                echo "   Token field: 'access_token'\n";
                echo "   Token returned: Yes\n";
                echo "   Authentication result: SUCCESS (but wrong field name)\n";
            } else {
                echo "   Token returned: No\n";
                echo "   Authentication result: FAILED (No token)\n";
            }
        } else {
            echo "   Is JSON: No\n";
        }
    }
    echo "\n";
}
