<?php

namespace Florisbosch\MultiStageBatch\Tests\Feature;

use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;
use Florisbosch\MultiStageBatch\MultiStageBatch;

class CreateMultiStageTest extends TestCase
{
    /** @test **/
    public function it_can_create_a_multi_stage_with_name()
    {
        $multiStage = new MultiStageBatch("Workflow 1");

        $multiStage->addStage(
            stage: 'Stage 1',
            steps: [
                fn() => Log::info('1'),
                fn() => Log::info('2'),
                fn() => Log::info('3'),
            ]
        )->addStage(stage: 'Stage 2', steps: [
            fn() => Log::info('XXX'),
        ]);

        $multiStage->dispatch();


        $this->assertEquals("Workflow 1", $multiStage->getName());
    }
}