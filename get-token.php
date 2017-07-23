<?php
session_start();

require __DIR__ . '/vendor/autoload.php';
require 'config.php';

$client = new Tumblr\API\Client(CONSUMER_KEY, CONSUMER_SECRET);

$requestHandler = $client->getRequestHandler();
$requestHandler->setBaseUrl('https://www.tumblr.com/');

if (!$_GET['oauth_verifier']) {

    // grab the oauth token
    $resp = $requestHandler->request('POST', 'oauth/request_token', array());
    $out = $result = $resp->body;
    $data = array();
    parse_str($out, $data);

    $url = "https://www.tumblr.com/oauth/authorize?oauth_token=".$data['oauth_token'];
    header('Location: ' . $url); 
    
    $_SESSION['t']=$data['oauth_token'];
    $_SESSION['s']=$data['oauth_token_secret'];

} else {

    $verifier = $_GET['oauth_verifier'];

    // use the stored tokens
    $client->setToken($_SESSION['t'], $_SESSION['s']);

    // to grab the access tokens
    $resp = $requestHandler->request('POST', 'oauth/access_token', array('oauth_verifier' => $verifier));
    $out = $result = $resp->body;
    $data = array();
    parse_str($out, $data);

    // and print out our new keys we got back
    foreach ($data as $key=>$value) {
        echo "$key : ".trim($value)."<br>";
    }

}