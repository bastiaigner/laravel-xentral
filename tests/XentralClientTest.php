<?php

use Bastiaigner\LaravelXentral\FilterOperator;
use Bastiaigner\LaravelXentral\XentralClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('it throws an exception if no base URL is specified', function () {
    XentralClient::make()->get('api/v1/products');
})->throws(\InvalidArgumentException::class, 'Base URL and token must be provided.');

test('it uses the correct base URL', function () {

    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/api/v1/products' => Http::response(['data' => 'test'], 200),
    ]);

    $result = XentralClient::make('https://example.com', 'foo')->get('api/v1/products')->json();

    expect($result)->toBe(['data' => 'test']);
});

test('it sends the API token in the Authorization header', function () {

    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/api/v1/products' => Http::response(['data' => 'test'], 200),
    ]);

    XentralClient::make('https://example.com', 'foo')->get('api/v1/products')->json();

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('Authorization', 'Bearer foo');
    });
});

test('it can fetch results from all pages', function () {

    $elementCount = 2000;

    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/api/v1/products*' => function (Request $request) use ($elementCount) {

            $page = $request->data()['page[number]'];
            $pageSize = $request->data()['page[size]'];

            return Http::response([
                'data' => array_map(fn ($i) => 'item'.(($page - 1) * $pageSize + $i), range(1, $pageSize)),
                'extra' => [
                    'totalCount' => $elementCount,
                    'page' => [
                        'size' => $pageSize,
                    ],
                ],
            ]);
        },
    ]);

    $results = XentralClient::make('https://example.com', 'foo')->getAllPages('api/v1/products');
    $this->assertCount($elementCount, $results);
});

test('it can filter', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/api/v1/products*' => function (Request $request) {
            $queryString = parse_url($request->url(), PHP_URL_QUERY);
            parse_str($queryString, $query);
            expect($query)->toMatchArray([
                'filter' => [
                    '0' => [
                        'key' => 'foo',
                        'value' => 'bar',
                        'op' => 'equals',
                    ],
                ],
            ]);

            return Http::response(['data' => 'test'], 200);
        },
    ]);

    XentralClient::make('https://example.com', 'foo')
        ->filter('foo', 'bar')
        ->get('api/v1/products')->json();
});

test('it can filter by multiple criteria', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/api/v1/products*' => function (Request $request) {
            $queryString = parse_url($request->url(), PHP_URL_QUERY);
            parse_str($queryString, $query);
            expect($query)->toMatchArray([
                'filter' => [
                    '0' => [
                        'key' => 'foo',
                        'value' => 'bar',
                        'op' => 'equals',
                    ],
                    '1' => [
                        'key' => 'alice',
                        'value' => 'bob',
                        'op' => 'greaterThan',
                    ],
                ],
            ]);

            return Http::response(['data' => 'test'], 200);
        },
    ]);

    XentralClient::make('https://example.com', 'foo')
        ->filter('foo', 'bar')
        ->filter('alice', 'bob', FilterOperator::GreaterThan)
        ->get('api/v1/products')->json();
});

test('it can order by', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/api/v1/products*' => function (Request $request) {
            $queryString = parse_url($request->url(), PHP_URL_QUERY);
            parse_str($queryString, $query);
            expect($query)->toMatchArray([
                'order' => [
                    '0' => [
                        'field' => 'bob',
                        'dir' => 'desc',
                    ],
                ],
            ]);

            return Http::response(['data' => 'test'], 200);
        },
    ]);

    XentralClient::make('https://example.com', 'foo')
        ->orderBy('bob', 'desc')
        ->get('api/v1/products')->json();
});

test('it retries on failure', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/api/v1/products' => Http::sequence()
            ->push(['data' => 'test'], 500)
            ->push(['data' => 'test'], 200),
    ]);

    $result = XentralClient::make('https://example.com', 'foo')->get('api/v1/products')->json();

    expect($result)->toBe(['data' => 'test']);
});

test('it throws exception on 4XX errors', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/api/v1/products' => Http::response([], 400),
    ]);

    $this->expectException(\Illuminate\Http\Client\RequestException::class);

    XentralClient::make('https://example.com', 'foo')->get('api/v1/products');
});
