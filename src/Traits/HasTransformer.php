<?php

namespace JoBins\Meilisearch\Traits;

use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Infrastructure\Utils\DataArraySerializer;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;

trait HasTransformer
{
    protected ?Manager $manager;

    protected ?TransformerAbstract $transformer = null;

    public function setTransformer(TransformerAbstract $transformer, Closure $closure = null): self
    {
        $this->transformer = $transformer;
        $this->manager     = $this->getFractal();

        if ($closure) {
            $closure($this->manager);
        }

        return $this;
    }

    public function getFractal(): Manager
    {
        $fractal = new Manager();

        // TODO: make it use from config
        $serializer = new DataArraySerializer();
        $fractal->setSerializer($serializer);

        return $fractal;
    }

    /**
     * @param Model|EloquentCollection|LengthAwarePaginator|null $result
     *
     * @return array|Model|EloquentCollection|LengthAwarePaginator|null;
     */
    public function parserResult($result)
    {
        if (is_null($this->transformer) || is_null($this->manager)) {
            return $result;
        }

        $resource = match (true) {
            $result instanceof EloquentCollection => new Collection($result, $this->transformer),
            $result instanceof LengthAwarePaginator => (new Collection($result->getCollection(), $this->transformer))
                ->setPaginator(new IlluminatePaginatorAdapter($result)),
            $result instanceof Model => new Item($result, $this->transformer),
            default => $result
        };

        return $this->manager->createData($resource)->toArray();
    }
}
