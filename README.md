# laravel-bank-id

A package for integrating Swedish BankID in your Laravel applications.

The source code is based on the [sample application provided by BankID](https://github.com/BankID/SampleCode) which is written in Java using Spring.

**This package has not been tested on < Laravel 8.**

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
        null, // returnUrl
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

## Animated QR codes and order lifetime

BankID's [QR guide](https://developers.bankid.com/how-to-guides/qr-code) requires
the QR code to be refreshed every second, with `time` measured on the backend.
`qrStartSecret` must never be sent to the frontend. Compute the code on the
server and send only the result:

```php
use Jgroup\BankID\Facades\BankID;
use Jgroup\BankID\Service\QrCode;
use Jgroup\BankID\Service\Models\BankIDTransaction;

$transaction = BankID::getSessionTransaction();

// bankid.{qrStartToken}.{time}.{qrAuthCode} for the current second
$qr = $transaction->getQrData();

// or from stored values
$qr = QrCode::payload($qrStartToken, $qrStartSecret, $time);

// whole seconds since the auth/sign response, never negative
$age = $transaction->getAgeInSeconds();

// an order must be started (QR scanned) within 30 seconds
if ($age >= BankIDTransaction::START_TIMEOUT_SECONDS - 5) {
    // create a new order to keep the QR code valid
}
```

`CollectResult` tells you where the order is:

```php
$result = BankID::collect()->getCollectResult();

$result->isAwaitingStart(); // pending, not yet scanned (outstandingTransaction / noClient)
$result->isStartTimeout();  // failed with startFailed: not scanned in time
```

## Network errors

A client error about the order (e.g. `400 invalidParameters` or `404`) makes
`auth()`, `sign()` and `collect()` return `null`.

A failure that says nothing about the order is thrown instead:

- `GuzzleHttp\Exception\ConnectException` for a connection failure
- `GuzzleHttp\Exception\BadResponseException` for `408`, `429` or any `5xx`
  (e.g. `503 maintenance`)

The order may still be alive at BankID, so the session transaction is kept and
the caller can retry. This differs from upstream `janjoo-group/laravel-bank-id`,
which returns `null` for all of these and so drops a valid transaction on a
blip. `cancel()` stays best-effort and returns `false` for every failure.

## Front-end

We've also developed a web component that works really well with this package and makes it easy to integrate BankID on websites.

[Component README](https://github.com/janjoo-group/bank-id-components/tree/main/src/components/jgroup-bank-id)

Example usage:

```html
<script type="module" src="https://cdn.jsdelivr.net/gh/janjoo-group/bank-id-components@latest/dist/jgroup-bank-id-components/jgroup-bank-id-components.esm.js"></script>

<!-- this will render a button that will start the configured process, qr generation etc. -->
<jgroup-bank-id
    type="auth"
    auth-url="BANKID_AUTH_START_URL"
    collect-url="BANKID_AUTH_COLLECT_URL"
    cancel-url="BANKID_CANCEL_URL"
    language="sv"
    dark-theme="true"
/>
```

You can see it in action here: 
[https://app.pejly.com/login](https://app.pejly.com/login)
