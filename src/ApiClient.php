<?php

declare(strict_types=1);

namespace Aran\YahooFinanceApi;

use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use Aran\YahooFinanceApi\Exception\ApiException;
use Aran\YahooFinanceApi\Results\HistoricalData;
use Aran\YahooFinanceApi\Results\Quote;
use Aran\YahooFinanceApi\Results\SearchResult;

class ApiClient
{

    public const INTERVAL_1_DAY = '1d';

    public const INTERVAL_1_WEEK = '1wk';

    public const INTERVAL_1_MONTH = '1mo';

    public const CURRENCY_SYMBOL_SUFFIX = '=X';

    /**
     * @var \React\Http\Browser
     */
    private $client;

    /**
     * @var ResultDecoder
     */
    private $resultDecoder;

    public function __construct(Browser $client, ResultDecoder $resultDecoder)
    {
        $this->client = $client;
        $this->resultDecoder = $resultDecoder;
    }

    /**
     * Search for stocks.
     *
     * @return PromiseInterface<array|SearchResult[]>
     *
     * @throws ApiException
     */
    public function search(string $searchTerm): PromiseInterface
    {
        $url
            = 'https://finance.yahoo.com/_finance_doubledown/api/resource/searchassist;gossipConfig=%7B%22queryKey%22:%22query%22,%22resultAccessor%22:%22ResultSet.Result%22,%22suggestionTitleAccessor%22:%22symbol%22,%22suggestionMeta%22:[%22symbol%22],%22url%22:%7B%22query%22:%7B%22region%22:%22US%22,%22lang%22:%22en-US%22%7D%7D%7D;searchTerm='
            . urlencode($searchTerm)
            . '?bkt=[%22findd-ctrl%22,%22fin-strm-test1%22,%22fndmtest%22,%22finnossl%22]&device=desktop&feature=canvassOffnet,finGrayNav,newContentAttribution,relatedVideoFeature,videoNativePlaylist,livecoverage&intl=us&lang=en-US&partner=none&prid=eo2okrhcni00f&region=US&site=finance&tz=UTC&ver=0.102.432&returnMeta=true';
        return $this->client->get($url)
            ->then(
                function (ResponseInterface $response) {
                    $responseBody = (string) $response->getBody();
                    return $this->resultDecoder->transformSearchResult($responseBody);
                }
            );
    }

    /**
     * Get historical data for a symbol.
     *
     * @return PromiseInterface<array|HistoricalData[]>
     *
     * @throws ApiException
     */
    public function getHistoricalData(string $symbol, string $interval, \DateTime $startDate, \DateTime $endDate): PromiseInterface
    {
        $allowedIntervals = [self::INTERVAL_1_DAY, self::INTERVAL_1_WEEK, self::INTERVAL_1_MONTH];
        if (!\in_array($interval, $allowedIntervals)) {
            throw new \InvalidArgumentException(sprintf('Interval must be one of: %s', implode(', ', $allowedIntervals)));
        }

        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        $initialUrl = 'https://finance.yahoo.com/quote/'.urlencode($symbol).'/history?p='.urlencode($symbol);
        return $this->client->get($initialUrl)
            ->then(function ($response) use ($symbol, $startDate, $endDate, $interval) {
                $responseBody = (string) $response->getBody();
                $crumb = $this->resultDecoder->extractCrumb($responseBody);
                $dataUrl = 'https://query1.finance.yahoo.com/v7/finance/download/'.urlencode($symbol).'?period1='.$startDate->getTimestamp().'&period2='.$endDate->getTimestamp().'&interval='.$interval.'&events=history&crumb='.urlencode($crumb);
                return $this->client->get($dataUrl);
            })
            ->then(function ($response) {
                $responseBody = (string) $response->getBody();
                return $this->resultDecoder->transformHistoricalDataResult($responseBody);
            });
    }

    /**
     * Get quote for a single symbol.
     *
     * @return PromiseInterface<Quote>
     */
    public function getQuote(string $symbol): PromiseInterface
    {
        return $this->fetchQuotes([$symbol])
            ->then(function ($list) {
                return isset($list[0]) ? $list[0] : null;
            });
    }

    /**
     * Get quotes for one or multiple symbols.
     *
     * @return PromiseInterface<array|Quote[]>
     */
    public function getQuotes(array $symbols): array
    {
        return $this->fetchQuotes($symbols);
    }


    /**
     * Get exchange rate for two currencies. Accepts concatenated ISO 4217 currency codes.
     *
     * @return PromiseInterface<Quote>
     */
    public function getExchangeRate(string $currency1, string $currency2): ?Quote
    {
        return $this->getExchangeRatesAsync([[$currency1, $currency2]])
            ->then(function ($list) {
                return isset($list[0]) ? $list[0] : null;
            });
    }

    /**
     * Retrieves currency exchange rates. Accepts concatenated ISO 4217 currency codes such as "GBPUSD".
     *
     * @param array $currencyPairs List of pairs of currencies
     *
     * @return PromiseInterface<array|Quote[]>
     */
    public function getExchangeRates(array $currencyPairs): array
    {
        $currencySymbols = array_map(function (array $currencies) {
            return implode($currencies).self::CURRENCY_SYMBOL_SUFFIX; // Currency pairs are suffixed with "=X"
        }, $currencyPairs);

        return $this->fetchQuotes($currencySymbols);
    }

    /**
     * Fetch quote data from API.
     *
     * @return PromiseInterface<Quote[]>
     */
    private function fetchQuotes(array $symbols): PromiseInterface
    {
        $url = 'https://query1.finance.yahoo.com/v7/finance/quote?symbols='.urlencode(implode(',', $symbols));
        return $this->client->get($url)
            ->then(function ($response) {
                $responseBody = (string) $response->getBody();
                return $this->resultDecoder->transformQuotes($responseBody);
            });
    }


}
