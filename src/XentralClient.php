<?php

namespace Bastiaigner\LaravelXentral;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

final class XentralClient extends PendingRequest
{
    /**
     * @var int Keeps track of the number of filters added to the query.
     */
    private int $filterCount = 0;

    /**
     * @var int Keeps track of the number of orderBy clauses added to the query.
     */
    private int $orderByCount = 0;

    public static function make(?string $baseUrl = null, ?string $token = null): self
    {
        $baseUrl = $baseUrl ?? config('xentral.base_url');
        $token = $token ?? config('xentral.token');

        if (empty($baseUrl) || empty($token)) {
            throw new \InvalidArgumentException('Base URL and token must be provided.');
        }

        // We are using the Http facade to support testing via Http::fake()
        $base = Http::baseUrl($baseUrl)
            ->withToken($token)
            ->throw()
            ->retry(
                times: 6,
                sleepMilliseconds: function ($attempt, $exception) {

                    if ($exception instanceof RequestException) {
                        if ($exception->response->status() == 429 || $exception->response->status() >= 500) {
                            Log::warning(
                                'Xentral API request failed with status code {status} on attempt {attempt}. Retrying in {delay}ms.',
                                [
                                    'status' => $exception->response->status(),
                                    'attempt' => $attempt,
                                    'delay' => pow(2, $attempt) * 1000,
                                ]
                            );

                            return pow(2, $attempt) * 1000; // exponential backoff in milliseconds
                        }
                    }

                    return 0; // don't retry for other statuses
                }
            );

        // This is a workaround to return our own instance of PendingRequest, while still using the Http facade for testability
        return tap(new self, function ($instance) use ($base) {
            foreach (get_object_vars($base) as $prop => $value) {
                $instance->{$prop} = $value;
            }
        });
    }

    public function getAllPages(string $endpoint, array $params = [], int $pageSize = 50): Collection
    {
        $results = collect();
        $pageNumber = 1;
        $pageSize = null;
        $totalCount = null;

        do {

            $response = $this->get($endpoint, array_merge($params, [
                'page[number]' => $pageNumber,
                'page[size]' => $pageSize ?? 50,
            ]));

            $response->throw();
            $json = $response->json();
            $results = $results->merge(data_get($json, 'data', []));

            $extra = data_get($json, 'extra', []);
            $totalCount = $totalCount ?? data_get($extra, 'totalCount');
            $pageSize = data_get($extra, 'page.size', $pageSize ?? 50);

            $pageNumber++;
        } while (($pageNumber - 1) * $pageSize < $totalCount);

        return $results;
    }

    /**
     * Lazily iterate over all items of a v3 endpoint using cursor pagination.
     * Only one page is held in memory at a time.
     */
    public function lazy(string $endpoint, array $params = [], int $perPage = 500): LazyCollection
    {
        return LazyCollection::make(function () use ($endpoint, $params, $perPage) {
            $cursor = null;

            do {
                $json = (clone $this)
                    ->withHeaders(['X-Pagination' => 'cursor'])
                    ->get($endpoint, array_merge($params, array_filter([
                        'perPage' => $perPage,
                        'cursor' => $cursor,
                    ])))
                    ->json();

                // Plain yield (not yield from) so keys keep incrementing across pages
                foreach (data_get($json, 'data', []) as $item) {
                    yield $item;
                }

                $cursor = data_get($json, 'meta.nextCursor');
            } while ($cursor);
        });
    }

    public function filter(string $field, int|string|array $value, string|FilterOperator $operator = FilterOperator::Equals): self
    {
        // We need the following format: ?filter[0][key]=companyName&filter[0][op]=startsWith&filter[0][value]=X

        $filterKey = 'filter['.$this->filterCount.'][key]';
        $filterOpKey = 'filter['.$this->filterCount.'][op]';
        $filterValueKey = 'filter['.$this->filterCount.'][value]';

        return tap($this, function () use ($filterKey, $filterOpKey, $filterValueKey, $field, $operator, $value) {

            $this->withQueryParameters([
                $filterKey => $field,
                $filterOpKey => $operator instanceof FilterOperator ? $operator->value : $operator,
                $filterValueKey => is_array($value) ? implode(',', $value) : $value,
            ]);
            $this->filterCount++;
        });
    }

    public function orderBy(string $field, string $direction = 'asc'): self
    {
        // We need the following format: ?orderBy[0][key]=companyName&orderBy[0][direction]=asc

        $orderByKey = 'order['.$this->orderByCount.'][field]';
        $orderByDirectionKey = 'order['.$this->orderByCount.'][dir]';

        return tap($this, function () use ($orderByKey, $orderByDirectionKey, $field, $direction) {

            $this->withQueryParameters([
                $orderByKey => $field,
                $orderByDirectionKey => $direction,
            ]);
            $this->orderByCount++;
        });
    }
}
