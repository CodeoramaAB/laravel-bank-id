<?php

use Jgroup\BankID\Service\BankIDService;

/**
 * https://www.bankid.com/utvecklare/guider/teknisk-integrationsguide
 */
return [

    /**
     * Which BankID environment to use.
     * Possible values: 'test', 'prod'
     */
    'use_environment' => env('BANKID_ENV', 'test'),

    /**
     * The session key to use for storing the BankID transaction.
     */
    'transaction_session_key' => 'bankid_transaction',

    /**
     * BankID environments.
     */
    'environments' => [
        'prod' => [
            'url' => 'https://appapi2.bankid.com/rp/v6.0',
            'cert_path' => env('BANKID_CERT_PATH'),
            'cert_key_path' => env('BANKID_CERT_KEY_PATH'),
            'ca_cert_path' => __DIR__.'/../bankid_certificates/appapi2.prod.ca.crt',
        ],
        'test' => [
            'url' => 'https://appapi2.test.bankid.com/rp/v6.0',
            'cert_path' => __DIR__.'/../bankid_certificates/appapi2.test.pem',
            'cert_key_path' => __DIR__.'/../bankid_certificates/appapi2.test.key.pem',
            'ca_cert_path' => __DIR__.'/../bankid_certificates/appapi2.test.ca.crt',
        ],
    ],

    /**
     * Logging configuration for completed BankID transactions.
     * RP should keep the completion data for future references/compliance/audit.
     */
    'logging' => [
        'channels' => [
            BankIDService::LOG_CHANNEL_COMPLETIONS => [
                'driver' => 'single',
                'path' => storage_path('logs/bankid-completions.log'),
                'level' => 'debug',
            ],
        ],
    ],
];
