<?php

require_once(__DIR__ . '/vendor/autoload.php');
//Import Facade classes you are going to use here
//For example, if you need to use Customer, add
//use QuickBooksOnline\API\Facades\Customer;

use QuickBooksOnline\API\Core\ServiceContext;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\PlatformService\PlatformService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Facades\Purchase;
use QuickBooksOnline\API\Data\IPPPurchase;
use QuickBooksOnline\API\QueryFilter\QueryMessage;
use QuickBooksOnline\API\ReportService\ReportService;
use QuickBooksOnline\API\ReportService\ReportName;


session_start();

function analyzeBusiness()
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

    $dataService->setLogLocation("/Users/psridhar1/repositories/QBOConceptsTutorial-PHP/Logs");

    /*
     * Initialize the Report service from the data service context
     */
    $serviceContext = $dataService->getServiceContext();
    $reportService = new ReportService($serviceContext);
    if (!$reportService) {
        exit("Problem while initializing ReportService.\n");
    }

    /*
     * Usecase 1
     * Choose the reports - Balance Sheet, ProfitAndLoss
     */
    $balancesheet = $reportService->executeReport("BalanceSheet");
    $profitAndLossReport = $reportService->executeReport("ProfitAndLoss");

    /*
     * Print the reports
     */
    echo("ProfitAndLoss Report Execution Start!" . "\n");
    if (!$profitAndLossReport) {
        exit("ProfitAndLossReport Is Null.\n");
    } else {
        $result = json_encode($profitAndLossReport, JSON_PRETTY_PRINT);
        print_r($result);
        echo("Profit And Loss Report Execution Successful!" . "\n");
    }
    echo("\nBalanceSheet Execution Start!" . "\n");
    if (!$balancesheet) {
        exit("BalanceShee Is Null.\n");
    } else {
        $result = json_encode($balancesheet, JSON_PRETTY_PRINT);
        print_r($result);
        echo("BalanceSheet Execution Successful!" . "\n");
    }

    /*
     * Usecase 2
     * Configure report service to be summarized by Customer
     * Report service is default configured for the Current Year end, so no conf needed there
     */
    $reportService->setSummarizeColumnBy("Customers");

    /*
    * Once the report service is configured, Choose the reports - Balance Sheet, ProfitAndLoss
    */
    $balancesheet = $reportService->executeReport("BalanceSheet");
    $profitAndLossReport = $reportService->executeReport("ProfitAndLoss");

    /*
    * Print the reports
    */
    echo("Year End Profit And Loss Report Summarized by Customers Start!" . "\n");
    if (!$profitAndLossReport) {
        exit("ProfitAndLossReport Is Null.\n");
    } else {
        $result = json_encode($profitAndLossReport, JSON_PRETTY_PRINT);
        print_r($result);
        echo("Year End Profit And Loss Report Summarized by Customers Execution Successful!" . "\n");
    }
    echo("Year End BalanceSheet Summarized by Customers Start!" . "\n");
    if (!$balancesheet) {
        exit("BalanceSheet Is Null.\n");
    } else {
        $result = json_encode($balancesheet, JSON_PRETTY_PRINT);
        print_r($result);
        echo("BalanceSheet Execution Successful!" . "\n");
    }

    return;
}

$result = analyzeBusiness();

?>
