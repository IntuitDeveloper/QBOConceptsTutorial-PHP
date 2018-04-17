<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Facades\Customer;


session_start();

function customerAdd()
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
    $dataService->setLogLocation("/Users/hlu2/Desktop/newFolderForLog");

    $dataService->throwExceptionOnError(true);
    /*
     * Update the OAuth2Token of the dataService object
     */
    $dataService->updateOAuth2Token($accessToken);
    $theResourceObj = Customer::create([
      "BillAddr" => [
          "Line1" => "123 Main Street",
          "City" => "Mountain View",
          "Country" => "USA",
          "CountrySubDivisionCode" => "CA",
          "PostalCode" => "94042"
      ],
      "Notes" => "Here are other details.",
      "Title" => "Mr",
      "GivenName" => "JamesCRUD",
      "MiddleName" => "B",
      "FamilyName" => "KingCRUD",
      "Suffix" => "Jr",
      "FullyQualifiedName" => "JamesCRUD KingCRUD",
      "CompanyName" => "King Groceries CRUD",
      "DisplayName" => "JamesCfdRdUDesf KingCRUDm Fes",
      "PrimaryPhone" => [
          "FreeFormNumber" => "(555) 555-5555"
      ],
      "PrimaryEmailAddr" => [
          "Address" => "jdrew@myemail.com"
      ]
    ]);
    $resultingObj = $dataService->Add($theResourceObj);
    $result = json_encode($resultingObj, JSON_PRETTY_PRINT);
    print_r($result);
    return $result;
}

$result = customerAdd();

?>
