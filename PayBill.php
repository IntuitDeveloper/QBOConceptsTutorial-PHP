<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;
//Import Facade classes you are going to use here
//For example, if you need to use Customer, add
//use QuickBooksOnline\API\Facades\Customer;


session_start();

function payBill()
{

    // Create SDK instance
    $config = include('config.php');
    $dataService = DataService::Configure(array(
        'auth_mode' => 'oauth2',
        'ClientID' => $config['client_id'],
        'ClientSecret' =>  $config['client_secret'],
        'RedirectURI' => $config['oauth_redirect_uri'],
        'scope' => $config['oauth_scope'],
        'baseUrl' => "development"
    ));

    /*
     * Retrieve the accessToken value from session variable
     */
    $accessToken = $_SESSION['sessionAccessToken'];
    $dataService->throwExceptionOnError(true);
    /*
     * Update the OAuth2Token of the dataService object
     */
    $dataService->updateOAuth2Token($accessToken);

    //Start write your business logic here, and store the final result to $result object





    //print result
    print_r($result);
    return $result;
}

$result = payBill();

?>
