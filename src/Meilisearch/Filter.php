<?php

namespace JoBins\Meilisearch\Meilisearch;

use Closure;
use Illuminate\Support\HigherOrderWhenProxy;

class Filter
{
    public array $filter = [];

    public static function query(): static
    {
        return new static();
    }

    public function when($value = null, callable $callback = null, callable $default = null)
    {
        $value = $value instanceof Closure ? $value($this) : $value;

        if (func_num_args() === 0) {
            return new HigherOrderWhenProxy($this);
        }

        if (func_num_args() === 1) {
            return (new HigherOrderWhenProxy($this))->condition($value);
        }

        if ($value) {
            return $callback($this, $value) ?? $this;
        } else if ($default) {
            return $default($this, $value) ?? $this;
        }

        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->filter[] = [
            'column'   => $column,
            'operator' => ' IS NULL',
            'value'    => '',
        ];

        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->filter[] = [
            'column'   => $column,
            'operator' => ' IS NOT NULL',
            'value'    => '',
        ];

        return $this;
    }


    public function orWhereNull(string $column): self
    {
        $this->filter[] = [
            'column'   => $column,
            'operator' => ' IS NULL',
            'value'    => '',
            'type'     => ' OR '
        ];

        return $this;
    }

    public function where($column, $operator = null, $value = null)
    {
        [$value, $operator] = func_num_args() === 2 ? [$operator, " = "] : [$value, $operator];

        $this->whereToFilter($column, $operator, $value, ' AND ');

        return $this;
    }

    public function orWhere($column, $operator = null, $value = null): self
    {
        [$value, $operator] = func_num_args() === 2 ? [$operator, " = "] : [$value, $operator];

        $this->whereToFilter($column, $operator, $value, ' OR ');

        return $this;
    }

    public function whereBetween(string $column, array $values): self
    {
        $this->filter[] = [
            'column'   => $column,
            'operator' => implode(' TO ', $values),
            'value'    => '',
            'type'     => ' AND ',
        ];

        return $this;
    }

    protected function whereToFilter($column, $operator = null, $value = null, $type = ' AND ')
    {
        if ($column instanceof Closure && is_null($operator)) {
            $filter = $column(new static())->toBase();

            $this->filter[] = [
                'column'   => '('.$filter.')',
                'operator' => '',
                'value'    => '',
                'type'     => $type
            ];
        } else if (is_array($column)) {
            foreach ($column as $field => $value) {
                $this->where($field, $value);
            }
        } else if ($column && $operator && !is_null($value)) {
            $this->filter[] = [
                'column'   => $column,
                'operator' => $operator,
                'value'    => $value,
                'type'     => $type
            ];
        }
    }

    public function getConnector(): string
    {
        return collect($this->filter)->last()['type'] ?? ' AND ';
    }

    public function toBase(): string
    {
        $filters = collect($this->filter);

        return $filters->reduce(function ($collect, $item) {
            $value = $item['value'];

            $value = match (true) {
                is_numeric($value) => $value,
                is_bool($value) => $value ? 'true' : 'false',
                empty($value) => "",
                default => sprintf('"%s"', $value)
            };

            $current = implode('', [
                $item['column'],
                $item['operator'],
                $value
            ]);

            $type = $item['type'] ?? ' AND ';

            return implode($type, array_filter([$collect, $current]));
        }, '');
    }

    public function whereIn(string $column, array $values): self
    {
        $formattedValues = implode(', ', array_map(function ($value) {
            return is_numeric($value) ? $value : sprintf('"%s"', $value);
        }, $values));
        $this->filter[]  = [
            'column'   => $column,
            'operator' => ' IN ['.$formattedValues.']',
            'value'    => '',
            'type'     => ' AND ',
        ];

        return $this;
    }

    public function orWhereIn(string $column, array $values): self
    {
        $formattedValues = implode(', ', array_map(function ($value) {
            return is_numeric($value) ? $value : sprintf('"%s"', $value);
        }, $values));
        $this->filter[]  = [
            'column'   => $column,
            'operator' => ' IN ['.$formattedValues.']',
            'value'    => '',
            'type'     => ' OR ',
        ];

        return $this;
    }
}
