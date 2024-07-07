<?php

namespace JoBins\Meilisearch\Criteria;

use Illuminate\Support\Str;
use JoBins\Meilisearch\Interfaces\CriteriaInterface;
use JoBins\Meilisearch\Meilisearch\Builder;

class Criteria implements CriteriaInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(public array $filters) {}

    public function apply(Builder $query): Builder
    {
        $model = $this->preQuery($query, $this->filters);

        foreach ($this->filters as $key => $filter) {
            $methodName = $this->getMethodName($key);

            if (method_exists($this, $methodName)) {
                $model = $this->$methodName($model, $filter ?? null);
            }
        }

        return $this->postQuery($model, $this->filters);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function preQuery(Builder $query, array $filters): Builder
    {
        return $query;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function postQuery(Builder $query, array $filters): Builder
    {
        return $query;
    }

    public function getMethodName(string $key): string
    {
        $key = (string) Str::of($key)->camel();

        return "{$key}Filter";
    }
}
