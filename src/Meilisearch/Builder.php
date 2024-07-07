<?php

namespace JoBins\Meilisearch\Meilisearch;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class Builder extends \Laravel\Scout\Builder
{
    public Filter $filter;

    // Specially used for multi search OR
    public Collection $queries;

    public function __construct($model, $query, $callback = null, $softDelete = false)
    {
        parent::__construct($model, $query, $callback, $softDelete);

        $this->filter = Filter::query();
        $this->queries = collect();
    }

    /**
     * @param string|Closure $field
     * @param                $value
     *
     * @return $this|Builder
     */
    public function where($field, $value = null)
    {
        if ($field instanceof Closure) {
            $this->filter = $this->filter->where($field);
        } else {
            parent::where($field, $value);
        }

        return $this;
    }

    public function orWhere($field, $operator = null, $value = null): self
    {
        $this->filter = $this->filter->orWhere(...func_get_args());

        return $this;
    }

    public function whereNull($field): self
    {
        $this->filter = $this->filter->whereNull($field);

        return $this;
    }

    public function whereNotNull($field): self
    {
        $this->filter = $this->filter->whereNotNull($field);

        return $this;
    }

    public function whereBetween(string $field, array $values)
    {
        $this->filter = $this->filter->whereBetween($field, $values);

        return $this;
    }

    public function getMultiSearchTotalCount(): int
    {
        /** @var Meilisearch $engine */
        $engine = $this->engine();

        return $engine->getMultiSearchTotalCount($this);
    }

    public function multiSearchPaginate(?int $perPage = null, ?string $pageName = 'page', ?int $page = null)
    {
        /** @var Meilisearch $engine */
        $engine = $this->engine();

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $rawResults = $engine->multiSearchPaginate($this, $perPage, $page);

        $results = $this->model->newCollection($engine->map($this, $rawResults, $this->model)->all());

        return Container::getInstance()->makeWith(LengthAwarePaginator::class, [
            'items'       => $results,
            'total'       => $rawResults['totalHits'],
            'perPage'     => $perPage,
            'currentPage' => $page,
            'options'     => [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        ]);
    }
}
