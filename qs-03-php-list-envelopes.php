<?php

# PHP Quick start example: list envelopes and their status.
# Copyright (c) 2018 by DocuSign, Inc.
# License: The MIT License -- https://opensource.org/licenses/MIT

require_once('vendor/autoload.php');
require_once('vendor/docusign/esign-client/autoload.php');

function list_envelopes(){
    # Settings
    # Fill in these constants
    #
    # Obtain an OAuth access token from https://developers.docusign.com/oauth-token-generator
    $accessToken = '{ACCESS_TOKEN}';
    # Obtain your accountId from https://appdemo.docusign.com -- the account id is shown in the drop down on the
    # upper right corner of the screen by your picture or the default picture.
    $accountId = '{ACCOUNT_ID}';

    # The API base_path
    $basePath = 'https://demo.docusign.net/restapi';

    # configure the EnvelopesApi object
    $config = new DocuSign\eSign\Configuration();
    $config->setHost($basePath);
    $config->addDefaultHeader("Authorization", "Bearer " . $accessToken);
    $apiClient = new DocuSign\eSign\ApiClient($config);
    $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);

    #
    # Step 1. Create the options object. We want the envelopes created 10 days ago or more recently.
    #
    $date = new Datetime();
    $date->sub(new DateInterval("P10D"));
    $options = new DocuSign\eSign\Api\EnvelopesApi\ListStatusChangesOptions();
    $options->setFromDate($date->format("Y/m/d"));

    #
    #  Step 2. Request the envelope list.
    #
    $results = $envelopeApi->listStatusChanges($accountId, $options);
    return $results;
};

# Mainline
try {
    $results = list_envelopes();
    ?>
<html lang="en">
    <body>
        <h4>Results</h4>
        <p><code><pre><?= print_r ($results) ?></pre></code></p>
    </body>
</html>
    <?php
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    if ($e instanceof DocuSign\eSign\ApiException) {
        print ("\nDocuSign API error information: \n");
        var_dump ($e->getResponseBody());
    }
}
