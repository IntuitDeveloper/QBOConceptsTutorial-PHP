<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Facades\Account;
use QuickBooksOnline\API\Facades\JournalEntry;
// Import Facade classes you are going to use here
// For example, if you need to use Customer, add
// use QuickBooksOnline\API\Facades\Customer;

session_start();

function accounting()
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

     // Retrieve the accessToken value from session variable
    $dataService->setLogLocation("/Users/mshah6/QBOConceptsTutorial-PHP/logs");
    $accessToken = $_SESSION['sessionAccessToken'];
    $dataService->throwExceptionOnError(true);

     // Update the OAuth2Token of the dataService object

    $dataService->updateOAuth2Token($accessToken);

    // Start write your business logic here, and store the final result to $result object
    // Create  bank account
    $theBankAccountResourceObj = Account::create([
      "AccountType" => "Bank",
      "Name" => "Bank account1212"
    ]);
    $resultingBankAccountObj = $dataService->Add($theBankAccountResourceObj);
    print "Created Id={$resultingBankAccountObj->Id}. Reconstructed response body:\n\n";
    $result = json_encode($resultingBankAccountObj, JSON_PRETTY_PRINT);
    print_r($result);
    // Create credit card account
    $theCCResourceObj = Account::create([
        "AccountType" => "Credit Card",
        "Name" => "Credit card account1212"
    ]);
    $resultingCCObj = $dataService->Add($theCCResourceObj);
    print "Created Id={$resultingCCObj->Id}. Reconstructed response body:\n\n";
    $result = json_encode($resultingCCObj, JSON_PRETTY_PRINT);
    print_r($result);

    // Make jornal using the two accounts created
    $theResourceObj = JournalEntry::create([
        "Line" => [
            [
                "Id" => "0",
                "Description" => "nov portion of rider insurance",
                "Amount" => 100.0,
                "DetailType" => "JournalEntryLineDetail",
                "JournalEntryLineDetail" => [
                "PostingType" => "Debit",
                "AccountRef" => [
                    "value" => $resultingCCObj->Id,
                    "name" => "I forget"
                ]
             ]
            ],
            [
                "Description" => "nov portion of rider insurance",
                "Amount" => 100.0,
                "DetailType" => "JournalEntryLineDetail",
                "JournalEntryLineDetail" => [
                    "PostingType" => "Credit",
                    "AccountRef" => [
                        "value" => $resultingBankAccountObj->Id,
                        "name" => "I don't remember"
                    ]
                ]
            ]
        ]
    ]);
    $resultingObj = $dataService->Add($theResourceObj);
    $result = json_encode($resultingObj, JSON_PRETTY_PRINT);
    print "Created Id={$resultingObj->Id}. Reconstructed response body:\n\n";
    print_r($result);


    return $resultingObj;
    }

$result = accounting();

?>
