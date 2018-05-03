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
    $accessToken = $_SESSION['sessionAccessToken'];
    $dataService->throwExceptionOnError(true);

     // Update the OAuth2Token of the dataService object
    $dataService->updateOAuth2Token($accessToken);


    /*
     * Usecase 1
     * Generate names for the Bank Account and Credit Card Account
     */

    // Generate GUID to associate with the sample account names
    function getGUID(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = // "{"
                $hyphen.substr($charid, 0, 8);
            return $uuid;
        }
    }

    $GUID = getGUID();

    $bankAccountName = 'Sample-Bank-Account' . $GUID;
    $creditCardAccountName = 'Sample-Credit-Card-Account' . $GUID;

    $bankAccountId = null;
    $creditCardAccountId = null;


    // Iterate through all Accounts, even if it takes multiple pages
    $i = 1;
    while (1) {
        $allAccounts = $dataService->FindAll('Account', $i, 500);
        $error = $dataService->getLastError();
        if ($error) {
            echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
            exit();
        }
        if (!$allAccounts || (0==count($allAccounts))) {
            break;
        }
        foreach ($allAccounts as $oneAccount) {
            // Check if the Bank Account exists
            if($oneAccount->AccountType == "Bank" && $oneAccount->AccountSubType == "Bank")
            {
                if($oneAccount->Name == $bankAccountName) {
                    $bankAccountId = $oneAccount->Id;
                }
            }
            // Check if the Credit Card Account exists
            if($oneAccount->AccountType == "Credit Card" && $oneAccount->AccountSubType == "CreditCard")
            {
                if($oneAccount->Name == $creditCardAccountName)
                {
                    $creditCardAccountId = $oneAccount->Id;
                }
            }
        }
    }

    // Create or Update Bank Account based on the above result
    if($bankAccountId == null) {
        $theBankAccountResourceObj = Account::create([
            "AccountType" => "Bank",
             "Name" => $bankAccountName
        ]);
        $resultingBankAccountObj = $dataService->Add($theBankAccountResourceObj);
        print "Created Id={$resultingBankAccountObj->Id}. Reconstructed response body:\n\n";
        $bankAccountId = $resultingBankAccountObj->Id;
        $result = json_encode($resultingBankAccountObj, JSON_PRETTY_PRINT);
        print_r($result);
    }

    // Create or Update Credit Card  Account based on the above result
    if($creditCardAccountId == null) {
        $theCCResourceObj = Account::create([
            "AccountType" => "Bank",
            "Name" => $creditCardAccountName
        ]);
        $resultingCCObj = $dataService->Add($theCCResourceObj);
        print "Created Id={$resultingCCObj->Id}. Reconstructed response body:\n\n";
        $creditCardAccountId = $resultingCCObj->Id;
        $result = json_encode($resultingCCObj, JSON_PRETTY_PRINT);
        print_r($result);
    }

    // Make Journal Entry using the above two accounts created / Updated
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
                    "value" => $bankAccountId               ]
             ]
            ],
            [
                "Description" => "nov portion of rider insurance",
                "Amount" => 100.0,
                "DetailType" => "JournalEntryLineDetail",
                "JournalEntryLineDetail" => [
                    "PostingType" => "Credit",
                    "AccountRef" => [
                        "value" => $creditCardAccountId                    ]
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
