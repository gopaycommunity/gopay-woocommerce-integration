<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1c4379f75f2119f2e46c041ff07fda8b
{
    public static $files = array (
        '7b11c4dc42b3b3023073cb14e519683c' => __DIR__ . '/..' . '/ralouphie/getallheaders/src/getallheaders.php',
        '6e3fae29631ef280660b3cdad06f25a8' => __DIR__ . '/..' . '/symfony/deprecation-contracts/function.php',
        'c964ee0ededf28c96ebd9db5099ef910' => __DIR__ . '/..' . '/guzzlehttp/promises/src/functions_include.php',
        'a0edc8309cc5e1d60e3047b5df6b7052' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/functions_include.php',
        '37a3dc5111fe8f707ab4c132ef1dbc62' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/functions_include.php',
        'a9b805bf529b5a997093b3cddca2af6f' => __DIR__ . '/..' . '/gopay/payments-sdk-php/factory.php',
    );

    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
            'Psr\\Http\\Client\\' => 16,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Psr7\\' => 16,
            'GuzzleHttp\\Promise\\' => 19,
            'GuzzleHttp\\' => 11,
            'GoPay\\' => 6,
        ),
        'D' => 
        array (
            'Dealerdirect\\Composer\\Plugin\\Installers\\PHPCodeSniffer\\' => 55,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'Psr\\Http\\Client\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-client/src',
        ),
        'GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/psr7/src',
        ),
        'GuzzleHttp\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/promises/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
        'GoPay\\' => 
        array (
            0 => __DIR__ . '/..' . '/gopay/payments-sdk-php/src',
        ),
        'Dealerdirect\\Composer\\Plugin\\Installers\\PHPCodeSniffer\\' => 
        array (
            0 => __DIR__ . '/..' . '/dealerdirect/phpcodesniffer-composer-installer/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'GoPay\\Auth' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Auth.php',
        'GoPay\\Definition\\Account\\StatementGeneratingFormat' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Account/StatementGeneratingFormat.php',
        'GoPay\\Definition\\Language' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Language.php',
        'GoPay\\Definition\\Payment\\BankSwiftCode' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Payment/BankSwiftCode.php',
        'GoPay\\Definition\\Payment\\Currency' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Payment/Currency.php',
        'GoPay\\Definition\\Payment\\PaymentInstrument' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Payment/PaymentInstrument.php',
        'GoPay\\Definition\\Payment\\PaymentItemType' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Payment/PaymentItemType.php',
        'GoPay\\Definition\\Payment\\Recurrence' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Payment/Recurrence.php',
        'GoPay\\Definition\\RequestMethods' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/RequestMethods.php',
        'GoPay\\Definition\\Response\\PaymentStatus' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Response/PaymentStatus.php',
        'GoPay\\Definition\\Response\\PaymentSubStatus' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Response/PaymentSubStatus.php',
        'GoPay\\Definition\\Response\\PreAuthState' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Response/PreAuthState.php',
        'GoPay\\Definition\\Response\\RecurrenceState' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Response/RecurrenceState.php',
        'GoPay\\Definition\\Response\\Result' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Response/Result.php',
        'GoPay\\Definition\\TokenScope' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/TokenScope.php',
        'GoPay\\GoPay' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/GoPay.php',
        'GoPay\\Http\\JsonBrowser' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Http/JsonBrowser.php',
        'GoPay\\Http\\Log\\Logger' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Http/Log/Logger.php',
        'GoPay\\Http\\Log\\NullLogger' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Http/Log/NullLogger.php',
        'GoPay\\Http\\Log\\PrintHttpRequest' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Http/Log/PrintHttpRequest.php',
        'GoPay\\Http\\Request' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Http/Request.php',
        'GoPay\\Http\\Response' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Http/Response.php',
        'GoPay\\OAuth2' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/OAuth2.php',
        'GoPay\\Payments' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Payments.php',
        'GoPay\\Token\\AccessToken' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Token/AccessToken.php',
        'GoPay\\Token\\CachedOAuth' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Token/CachedOAuth.php',
        'GoPay\\Token\\InMemoryTokenCache' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Token/InMemoryTokenCache.php',
        'GoPay\\Token\\TokenCache' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Token/TokenCache.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1c4379f75f2119f2e46c041ff07fda8b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1c4379f75f2119f2e46c041ff07fda8b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit1c4379f75f2119f2e46c041ff07fda8b::$classMap;

        }, null, ClassLoader::class);
    }
}
