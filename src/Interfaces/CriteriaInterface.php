<?php

namespace JoBins\Meilisearch\Interfaces;

use JoBins\Meilisearch\Meilisearch\Builder;

interface CriteriaInterface
{
    public function apply(Builder $query): Builder;

    public function preQuery(Builder $query, array $filters): Builder;

    public function postQuery(Builder $query, array $filters): Builder;
}
