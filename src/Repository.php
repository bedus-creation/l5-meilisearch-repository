<?php

namespace JoBins\Meilisearch;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use JoBins\Meilisearch\Meilisearch\Builder;
use JoBins\Meilisearch\Traits\HasCriteria;
use JoBins\Meilisearch\Traits\HasQuery;
use JoBins\Meilisearch\Traits\HasTransformer;

abstract class Repository
{
    use HasCriteria;
    use HasTransformer;
    use HasQuery;

    protected Builder $scoutQuery;

    public function __construct()
    {
        $this->criteria   = collect();
        $this->scoutQuery = $this->model();
    }

    public abstract function model(): Builder;

    public function whereBetween(string $column, array $values): static
    {
        $this->scoutQuery = $this->scoutQuery->whereBeetween($column, $values);

        return $this;
    }

    protected function applyQuery(callable $callback = null): array|Collection|Model|int
    {
        $this->applyFilters();

        $query = $this->scoutQuery->query($this->queryCallback);

        $data = $callback($query);

        return $this->parserResult($data);
    }

    public function get(): Model|Collection|array
    {
        return $this->applyQuery(fn(Builder $scoutQuery) => $scoutQuery->get());
    }

    public function paginate($perPage = null, $pageName = 'page', $page = null): Model|Collection|array
    {
        return $this->applyQuery(fn(Builder $scoutQuery) => $scoutQuery->paginate($perPage, $pageName, $page));
    }

    /**
     * @return LengthAwarePaginator|array
     */
    public function multiSearchPaginate(?int $perPage = null, string $pageName = 'page', ?int $page = null)
    {
        return $this->applyQuery(fn(Builder $scoutQuery) => $scoutQuery->multiSearchPaginate($perPage, $pageName, $page));
    }

    public function raw(): Model|Collection|array
    {
        return $this->applyQuery(fn(Builder $scoutQuery) => $scoutQuery->raw());
    }

    public function getTotalCount(): int
    {
        return $this->raw()['nbHits'];
    }

    public function getMultiSearchTotalCount(): int
    {
        return $this->applyQuery(fn(Builder $scoutQuery) => $scoutQuery->getMultiSearchTotalCount());
    }
}
