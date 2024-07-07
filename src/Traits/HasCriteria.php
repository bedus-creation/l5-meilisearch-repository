<?php

namespace JoBins\Meilisearch\Traits;

use Illuminate\Support\Collection;
use JoBins\Meilisearch\Criteria\Criteria;

trait HasCriteria
{
    /** @var Collection<int, Criteria> */
    protected Collection $criteria;

    /**
     * @param Criteria $filter
     *
     * @return HasCriteria
     */
    public function pushCriteria(Criteria $filter): static
    {
        $this->criteria->push($filter);

        return $this;
    }

    /**
     * @return $this
     */
    protected function applyFilters(): static
    {
        if ($this->criteria->isEmpty()) {
            return $this;
        }

        $this->criteria->each(fn(Criteria $filter) => $this->scoutQuery = $filter->apply($this->scoutQuery));

        return $this;
    }
}
