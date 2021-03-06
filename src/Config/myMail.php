<?php
return [
    'Hosts' => ['example.com'], // Specify main and backup SMTP servers
    'SMTPAuth' => true,                    // Enable SMTP authentication
    'Username' => 'no-reply',    // SMTP username
    'Password' => 'password',                // SMTP password
    'SMTPSecure' => 'tls',                // Enable TLS encryption, `ssl` also accepted
    'Port' => 587,                        // Port

    'setMailTemplateDir' => APP_DIR . 'View/templates/mail',
    'smartyHtmlExtension' => '.html.php',              // Default '.html.php'
    'smartyTxtExtension' => '.txt.php',                // Default '.txt.php'
    'fileExtension' => '.html.php',

    'senderName' => APP_NAME,      // Name of default sender
    'senderMail' => 'senderMail@mail'  // Default sender's address
];
