# laravel-bank-id

A package for integrating Swedish BankID in your Laravel applications.

The source code is based on the [sample application provided by BankID](https://github.com/BankID/SampleCode) which is written in Java using Spring.

**This package was developed using Laravel 8 and has not been tested on earlier versions.**

## Getting started

Start by installing the package using Composer.

`composer require codeorama/laravel-bank-id`

Publish config and certificates (test certificates and production ca root certificate) by running:

`php artisan vendor:publish --provider="Jgroup\BankID\BankIDServiceProvider"`

## Production Certificate

Convert your production certificate from PKCS_12 format to two PEM files, ready to be used by running:

`openssl pkcs12 -in /path/to/certificate.p12 -passin pass:password_for_certificate_p12 -out /destination/folder/certificate.pem -clcerts -nokeys`

`openssl pkcs12 -in /path/to/certificate.p12 -passin pass:password_for_certificate_p12 -out /destination/folder/key.pem -nocerts -nodes`

## Quick auth example

```php
// routes/web.php

<?php

use App\Models\User;
use Illuminate\Http\Response;
use Jgroup\BankID\Facades\BankID;
use Illuminate\Support\Facades\Route;

Route::post('/auth/bankid', function(Request $request) {
    return BankID::auth(
        $request->getClientIp(),
        'Text to display to the user'
    );
});

Route::post('/auth/bankid/collect', function(Request $request) {
    $response = BankID::collect();

    if ($response->getStatus() === 'complete') {
        $personalNumber = $response->getCollectResult()
            ->getCompletionResult()
            ->getPersonalNumber();

        $user = User::where('pnr', $personalNumber)->firstOrFail();

        // clear session transaction when completed
        BankID::setSessionTransaction(null);

        Auth::login($user);
    }

    return $response;
});
```

## Quick sign example

```php
// routes/web.php

<?php

use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Jgroup\BankID\Facades\BankID;
use Jgroup\BankID\Rules\SignCompleted;
use Illuminate\Support\Facades\Route;

const SIGN_TEXT = 'Text to sign';

Route::post('/bankid/collect', function(Request $request) {
    return BankID::collect();
});

Route::post('/submit-form/sign', function(Request $request) {
    return BankID::sign(
        $request->getClientIp(),
        SIGN_TEXT
    );
});

Route::post('/submit-form', function(Request $request) {

    $request->validate([
        // transactionId should be sent along with the form data
        'transactionId' => [
            new SignCompleted(SIGN_TEXT)
        ],
        // your validation rules
    ]);

    // logic for handling form submission

    // clear session transaction if submission was processed successfully
    BankID::setSessionTransaction(null);
});

```
