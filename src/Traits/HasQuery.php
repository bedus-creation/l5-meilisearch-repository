<?php

namespace JoBins\Meilisearch\Traits;

use JoBins\Meilisearch\Repository;

trait HasQuery
{
    /**
     * @var callable
     */
    public $queryCallback;

    public function query(callable $callback): self
    {
        $this->queryCallback = $callback;

        return $this;
    }
}
