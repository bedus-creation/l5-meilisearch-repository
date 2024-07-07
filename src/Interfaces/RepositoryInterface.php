<?php

namespace JoBins\Meilisearch\Interfaces;

interface RepositoryInterface
{
    public function whereBetween(string $column, array $values): static;

    public function getTotalCount(): int;

    public function getMultiSearchTotalCount(): int;


    public function multiSearchPaginate();
}
