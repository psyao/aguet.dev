<?php

namespace Tests\Unit;

use App\Filament\Forms\TagsSelect;
use PHPUnit\Framework\TestCase;

class TagsSelectTest extends TestCase
{
    public function test_it_builds_a_reorderable_multiple_select(): void
    {
        $select = TagsSelect::make();

        $this->assertTrue($select->isMultiple(), 'tags select must stay multiple');
        $this->assertTrue($select->isReorderable(), 'tags select must be reorderable so badges can be dragged');
    }
}
