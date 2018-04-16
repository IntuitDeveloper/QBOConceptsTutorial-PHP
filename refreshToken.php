<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;

session_start();

function refreshToken()
{

    // Create SDK instance
    $config = include('config.php');
     /*
     * Retrieve the accessToken value from session variable
     */
    $accessToken = $_SESSION['sessionAccessToken'];
    $oauth2LoginHelper = new OAuth2LoginHelper($accessToken->getclientID(),$accessToken->getClientSecret());
    $newAccessTokenObj = $oauth2LoginHelper->
                    refreshAccessTokenWithRefreshToken($accessToken->getRefreshToken());
    $newAccessTokenObj->setRealmID($accessToken->getRealmID());
    $newAccessTokenObj->setBaseURL($accessToken->getBaseURL());
    $_SESSION['sessionAccessToken'] = $newAccessTokenObj;

    print_r($newAccessTokenObj);
    return $newAccessTokenObj;
}

$result = refreshToken();

?>
