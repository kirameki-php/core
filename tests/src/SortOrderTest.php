<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\SortOrder;
use Kirameki\Core\Testing\TestCase;
use const SORT_ASC;
use const SORT_DESC;

final class SortOrderTest extends TestCase
{
    public function test_values(): void
    {
        $this->assertSame(SORT_ASC, SortOrder::Ascending->value);
        $this->assertSame(SORT_DESC, SortOrder::Descending->value);
    }
}
