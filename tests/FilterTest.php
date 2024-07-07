<?php

namespace JoBins\Meilisearch\Tests;

use JoBins\Meilisearch\Meilisearch\Filter;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    public function testWhereBasicClause()
    {
        $this->assertEquals("is_test = IS NULL", Filter::query()->where("is_test", "IS NULL"));
    }

    public function testWhereGroup()
    {
        $query = Filter::query()->where(function (Filter $query) {
            return $query->whereNull('deleted_at')
                ->where([
                    'publish_status' => "Yes",
                    'delete_status'  => 'NO'
                ])->where(function (Filter $query) {
                    return $query->orWhere('admin_verification_status', 'approved')
                        ->orWhereNull('admin_verification_status');
                });
        })->toBase();

        $filter=  <<<FILTER
        (deleted_at IS NULL AND publish_status = Yes AND delete_status = NO AND (admin_verification_status = approved OR admin_verification_status IS NULL))
        FILTER;

        $this->assertEquals($filter, $query);
    }
}
