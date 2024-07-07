<?php

namespace JoBins\Meilisearch\Meilisearch;

use Domain\Shared\Helper\SearchHelper;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Scout\Builder;
use JoBins\Meilisearch\Meilisearch\Builder as JoBinsBuilder;
use Laravel\Scout\Engines\MeilisearchEngine as BaseMeilisearchEngine;
use Meilisearch\Contracts\SearchQuery;

class Meilisearch extends BaseMeilisearchEngine
{
    protected function filters(Builder $builder): string
    {
        $filter = parent::filters($builder);

        if (!$builder->filter) {
            return $filter;
        }

        $filter = collect([$filter, $builder->filter->toBase()])
            ->filter()
            ->implode($builder->filter->getConnector());

        return $filter;
    }

    public function prepareMultiSearchPaginateWithOnce(Builder $builder, array $searchParams = [])
    {
        return once(fn() => $this->prepareMultiSearchPaginate($builder, $searchParams));
    }

    public function prepareMultiSearchPaginate(JoBinsBuilder $builder, array $searchParams = [])
    {
        $searchParams = array_merge($builder->options, $searchParams);

        if (array_key_exists('attributesToRetrieve', $searchParams)) {
            $searchParams['attributesToRetrieve'] = array_unique(array_merge(
                [$builder->model->getScoutKeyName()],
                $searchParams['attributesToRetrieve'],
            ));
        }

        $terms = $builder->queries->toArray();
        $terms = SearchHelper::generateExclusiveSearchTerms($terms);

        $searches = [];
        foreach ($terms as $term => $index) {
            $searches[] = (new SearchQuery())
                ->setQuery($term)
                ->setAttributesToRetrieve([$builder->model->getScoutKeyName()])
                ->setIndexUid($builder->model->searchableAs())
                ->setFilter([$this->filters($builder)])
                ->setPage(1)
                ->setHitsPerPage(1)
                ->setMatchingStrategy('all');
        }

        return [
            $searchParams,
            $terms,
            $this->meilisearch->multiSearch($searches)
        ];
    }

    public function getMultiSearchTotalCount(Builder $builder)
    {
        return $this->performMultiSearchTotalCount($builder, array_filter([
            'filter'      => $this->filters($builder),
            'hitsPerPage' => 1,
            'page'        => 1,
            'sort'        => $this->buildSortFromOrderByClauses($builder),
        ]));
    }

    public function performMultiSearchTotalCount(Builder $builder, array $searchParams = [])
    {
        [, , $response] = $this->prepareMultiSearchPaginateWithOnce($builder, $searchParams);

        return collect($response['results'])
            ->keyBy('query')
            ->sum('totalHits');
    }


    /**
     * @param Builder $builder
     * @param array   $searchParams
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    public function performMultiSearchPaginate(Builder $builder, array $searchParams = [])
    {
        $results['totalHits'] = $this->performMultiSearchTotalCount($builder, $searchParams);

        [$searchParams, $terms, $response] = $this->prepareMultiSearchPaginateWithOnce($builder, $searchParams);

        $results['processingTimeMs'] = collect($response['results'])->sum('processingTimeMs');
        $metadata                    = collect($response['results'])->pluck('totalHits', 'query')->toArray();

        $perPage = $searchParams['hitsPerPage'];
        $page    = $searchParams['page'];

        $possibleSearchTerms = SearchHelper::getPossibleTerms($metadata, $terms, $page, $perPage);

        $searches = [];
        foreach ($possibleSearchTerms as $term) {
            $searches[] = (new SearchQuery())
                ->setQuery($term['q'])
                ->setAttributesToRetrieve(array_values($searchParams['attributesToRetrieve']) ?? [$builder->model->getScoutKeyName()])
                ->setIndexUid($builder->model->searchableAs())
                ->setFilter([$this->filters($builder)])
                ->setOffset($term['offset'] ?? 0)
                ->setLimit($term['limit'] ?? $perPage)
                ->setSort($searchParams['sort'])
                ->setMatchingStrategy('all');
        }

        $response                    = $this->meilisearch->multiSearch($searches);
        $results['processingTimeMs'] = $results['processingTimeMs'] + collect($response['results'])->sum('processingTimeMs');
        $results['hits']             = collect($response['results'])->pluck('hits')->flatten(1);

        return $results;
    }


    /**
     * @param Builder  $builder
     * @param int|null $perPage
     * @param int|null $page
     *
     * @return LengthAwarePaginator
     * @throws BindingResolutionException
     */
    public function multiSearchPaginate(Builder $builder, ?int $perPage = null, ?int $page = null)
    {
        return $this->performMultiSearchPaginate($builder, array_filter([
            'filter'      => $this->filters($builder),
            'hitsPerPage' => $perPage,
            'page'        => $page,
            'sort'        => $this->buildSortFromOrderByClauses($builder),
        ]));
    }
}
