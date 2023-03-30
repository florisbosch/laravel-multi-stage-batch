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
                fn() => Log::info('Single function to execute on the queue'),
                function() {
                    $text = 'This is a function on the job';
                }
            ]
        )->addStage(stage: 'Stage 2', steps: [
            new DataExport(),
        ]);

        $multiStage->addStage(stage: 'Stage 3', steps: [
            DataExport::class,
        ]);

        $multiStage->dispatch();

        $this->assertEquals("Workflow 1", $multiStage->getName());
    }
}