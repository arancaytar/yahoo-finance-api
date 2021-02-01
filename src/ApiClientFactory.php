<?php

declare(strict_types=1);

namespace Aran\YahooFinanceApi;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Http\Browser;

class ApiClientFactory
{
    public static function createFromBrowser(Browser $client): ApiClient
    {
        $resultDecoder = new ResultDecoder(new ValueMapper());

        return new ApiClient($client, $resultDecoder);
    }

    public static function createFromLoop(LoopInterface $loop): ApiClient
    {
        return static::createFromBrowser(new Browser($loop));
    }
}
