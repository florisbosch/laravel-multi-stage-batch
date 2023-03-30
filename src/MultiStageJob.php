<?php

namespace Florisbosch\MultiStageBatch;

use Florisbosch\MultiStageBatch\Concerns\InteractsWithStages;

final class MultiStageJob
{
    use InteractsWithStages;

    /**
     * Create a new workflow step
     * @param  string  $stageId
     * @param  string  $stageName
     */
    public function __construct(string $stageId, string $stageName)
    {
        $this->stageId = $stageId;
        $this->stageName = $stageName;
    }

    /**
     * Get the stage id
     * @return string
     */
    public function getStageId(): string
    {
        return $this->stageId;
    }

    /**
     * Get the stage from this job
     * @return string
     */
    public function getStageName(): string
    {
        return $this->stageName;
    }
}
