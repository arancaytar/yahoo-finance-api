aran/yahoo-finance-api
=======================

**This is a PHP client for Yahoo Finance API.**

Specifically, this is a fork of [scheb/yahoo-finance-api](https://github.com/scheb/yahoo-finance-api)
that replaces the guzzlehttp/guzzle client with [reactphp/http](https://github.com/reactphp/http)
and uses asynchronous requests.

This fork is mostly created for personal use and may be abandoned in the future;
please use https://github.com/scheb/yahoo-finance-api for any long-term projects.

<p align="center"><img alt="Logo" src="doc/logo.svg" width="180" /></p>

Since YQL APIs have been discontinued in November 2017, this client is using non-official API endpoints for quotes, search and historical data.

⚠️ **WARNING:** These non-official APIs cannot be assumed stable and might break any time. Also, you might violate Yahoo's terms of service. So use them at your own risk.

## Installation

Download via Composer:

```bash
composer require aran/yahoo-finance-api
```

Alternatively you can also add the package directly to composer.json:

```json
{
    "require": {
        "aran/yahoo-finance-api": "^5.0"
    }
}
```

and then tell Composer to install the package:

```bash
composer update aran/yahoo-finance-api
```

## Usage

```php
use Aran\YahooFinanceApi\ApiClient;
use Aran\YahooFinanceApi\ApiClientFactory;
use Aran\YahooFinanceApi\Results\Quote;use React\Http\Browser;

// Create a new client from the factory
$eventLoop = \React\EventLoop\Factory::create();
$client = ApiClientFactory::createFromLoop($eventLoop);

// Or use your own Browser and pass it in
$browser = new Browser($eventLoop);
$client = ApiClientFactory::createFromBrowser($browser);

// Returns an array of Scheb\YahooFinanceApi\Results\SearchResult
$client->search("Apple")->then(function (array $searchResult) {

});

// Returns an array of Scheb\YahooFinanceApi\Results\HistoricalData
$client->getHistoricalData("AAPL", ApiClient::INTERVAL_1_DAY, new \DateTime("-14 days"), new \DateTime("today"))
    ->then(function (array $historicalData) {

    });

// Returns Scheb\YahooFinanceApi\Results\Quote
$client->getExchangeRate("USD", "EUR")->then(function (Quote $exchangeRate) {

});

// Returns an array of Scheb\YahooFinanceApi\Results\Quote
$client->getExchangeRates([
    ["USD", "EUR"],
    ["EUR", "USD"],
])->then(function (array $exchangeRates) {

});

// Returns Scheb\YahooFinanceApi\Results\Quote
$client->getQuote("AAPL")->then(function (Quote $quote) {

});

// Returns an array of Scheb\YahooFinanceApi\Results\Quote
$client->getQuotes(["AAPL", "GOOG"])->then(function (array $quotes) {

});
```

License
-------
This bundle is available under the [MIT license](LICENSE).
