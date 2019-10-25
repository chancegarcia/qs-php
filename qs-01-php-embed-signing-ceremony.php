<?php

# PHP Quick start example: embedded signing ceremony.
# Copyright (c) 2018 by DocuSign, Inc.
# License: The MIT License -- https://opensource.org/licenses/MIT

require_once('vendor/autoload.php');
require_once('vendor/docusign/esign-client/autoload.php');

function embedded_signing_ceremony(){
    #
    # The document $fileNamePath will be signed by <signer_name> via an
    # embedded signing ceremony.

    # Settings
    # Fill in these constants
    #
    # Obtain an OAuth access token from https://developers.docusign.com/oauth-token-generator
    $accessToken = '{ACCESS_TOKEN}';
    # Obtain your accountId from https://appdemo.docusign.com -- the account id is shown in the drop down on the
    # upper right corner of the screen by your picture or the default picture.
    $accountId = '{ACCOUNT_ID}';
    # Recipient Information:
    $signerName = '{USER_FULLNAME}';
    $signerEmail = '{USER_EMAIL}';
    # The document you wish to send. Path is relative to the root directory of this repo.
    $fileNamePath = 'demo_documents/World_Wide_Corp_lorem.pdf';
    # The url of this web application's folder. If you leave it blank, the script will attempt to figure it out.
    $baseUrl = '';
    $clientUserId = '123'; # Used to indicate that the signer will use an embedded
                        # Signing Ceremony. Represents the signer's userId within
                        # your application.
    $authenticationMethod = 'None'; # How is this application authenticating
                                    # the signer? See the `authenticationMethod' definition
                                    # https://developers.docusign.com/esign-rest-api/reference/Envelopes/EnvelopeViews/createRecipient

    # The API base_path
    $basePath = 'https://demo.docusign.net/restapi';

    # Constants
    $appPath = getcwd();

    #
    # Step 1. The envelope definition is created.
    #         One signHere tab is added.
    #         The document path supplied is relative to the working directory
    #
    # Create the component objects for the envelope definition...
    $contentBytes = file_get_contents($appPath . "/" . $fileNamePath);
    $base64FileContent =  base64_encode ($contentBytes);

    # create the DocuSign document object
    $document = new DocuSign\eSign\Model\Document([
        'document_base64' => $base64FileContent,
        'name' => 'Example document', # can be different from actual file name
        'file_extension' => 'pdf', # many different document types are accepted
        'document_id' => '1' # a label used to reference the doc
    ]);

    # The signer object
    $signer = new DocuSign\eSign\Model\Signer([
        'email' => $signerEmail, 'name' => $signerName, 'recipient_id' => "1", 'routing_order' => "1",
        'client_user_id' => $clientUserId # Setting the client_user_id marks the signer as embedded
    ]);

    # DocuSign SignHere field/tab object
    $signHere = new DocuSign\eSign\Model\SignHere([
        'document_id' => '1', 'page_number' => '1', 'recipient_id' => '1',
        'tab_label' => 'SignHereTab', 'x_position' => '195', 'y_position' => '147'
    ]);

    # Add the tabs to the signer object
    # The Tabs object wants arrays of the different field/tab types
    $signer->setTabs(new DocuSign\eSign\Model\Tabs(['sign_here_tabs' => [$signHere]]));

    # Next, create the top level envelope definition and populate it.
    $envelopeDefinition = new DocuSign\eSign\Model\EnvelopeDefinition([
        'email_subject' => "Please sign this document",
        'documents' => [$document], # The order in the docs array determines the order in the envelope
        # The Recipients object wants arrays for each recipient type
        'recipients' => new DocuSign\eSign\Model\Recipients(['signers' => [$signer]]),
        'status' => "sent" # requests that the envelope be created and sent.
    ]);

    #
    #  Step 2. Create/send the envelope.
    #
    $config = new DocuSign\eSign\Configuration();
    $config->setHost($basePath);
    $config->addDefaultHeader("Authorization", "Bearer " . $accessToken);
    $apiClient = new DocuSign\eSign\ApiClient($config);
    $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);
    $results = $envelopeApi->createEnvelope($accountId, $envelopeDefinition);
    $envelopeId = $results['envelope_id'];

    #
    # Step 3. The envelope has been created.
    #         Request a Recipient View URL (the Signing Ceremony URL)
    #
    if ($baseUrl == '') {
        # Try to figure out our URL folder
        # NOTE: The following code relies on browser-supplied headers to be correct.
        #       In production, DO NOT use this code since it is not bullet-proof.
        #       Instead, set the $baseUrl appropriately.
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        # remove the current script name from the $baseUrl:
        $chars = -1 * (strlen(__FILE__) - strlen(__DIR__));
        $baseUrl = substr($baseUrl, 0, $chars);
    }
    $recipientViewRequest = new DocuSign\eSign\Model\RecipientViewRequest([
        'authentication_method' => $authenticationMethod, 'client_user_id' => $clientUserId,
        'recipient_id' => '1', 'return_url' => $baseUrl . '/dsreturn.php',
        'user_name' => $signerName, 'email' => $signerEmail
    ]);

    $results = $envelopeApi->createRecipientView($accountId, $envelopeId,
        $recipientViewRequest);

    #
    # Step 4. The Recipient View URL (the Signing Ceremony URL) has been received.
    #         The user's browser will be redirected to it.
    #
    return $results['url'];
};

# Mainline
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $redirectUrl = embedded_signing_ceremony();
        # Redirect...
        header('Location: ' . $redirectUrl);
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
        if ($e instanceof DocuSign\eSign\ApiException) {
            print ("\nDocuSign API error information: \n");
            var_dump ($e->getResponseBody());
        }
    }
    die();
}
# Since it isn't a POST, print the form:
?>
<html lang="en">
    <body>
        <form method="post">
            <input type="submit" value="Sign the document!"
                style="width:13em;height:2em;background:#1f32bb;color:white;font:bold 1.5em arial;margin: 3em;"/>
        </form>
    </body>
</html>
