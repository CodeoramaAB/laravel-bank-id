<?php

namespace Jgroup\BankID;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Jgroup\BankID\RpApi\RpApi;
use Jgroup\BankID\Service\BankIDService;
use JsonMapper;

class BankIDServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('bankid.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../bankid_certificates' => base_path('bankid_certificates'),
            ], 'config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'bankid');

        $this->setupBindings();
        $this->mergeLoggingChannels();
    }

    protected function mergeLoggingChannels(): void
    {
        $packageChannels = config('bankid.logging.channels', []);
        $appChannels = config('logging.channels', []);

        config(['logging.channels' => array_merge($packageChannels, $appChannels)]);
    }

    protected function setupBindings(): void
    {
        $this->app->bind(RpApi::class, function (Application $app, array $httpClientOptions = []) {
            $envConfig = config('bankid.environments.'.config('bankid.use_environment'), []);
            $baseUrl = $envConfig['url'] ?? '';
            $caCert = $envConfig['ca_cert_path'] ?? null;
            $cert = $envConfig['cert_path'] ?? null;
            $certKey = $envConfig['cert_key_path'] ?? null;

            $baseOptions = [
                'base_uri' => Str::finish($baseUrl, '/'),
                'http_errors' => true,
                'headers' => ['Content-Type' => 'application/json'],
                'verify' => $caCert,
            ];

            if ($cert && $certKey) {
                $baseOptions['cert'] = $cert;
                $baseOptions['ssl_key'] = $certKey;
            }

            $baseOptions = array_merge($baseOptions, $httpClientOptions);

            return new RpApi(
                new HttpClient($baseOptions),
                new JsonMapper
            );
        });

        $this->app->bind('bankid', function (Application $app) {
            return new BankIDService(
                $app->make(RpApi::class),
                $app->make('session.store'),
                $app->make('log'),
                config('bankid.transaction_session_key')
            );
        });
    }
}
