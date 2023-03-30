<?php

namespace Florisbosch\MultiStageBatch\Concerns;

use Illuminate\Cache\TaggedCache;
use Florisbosch\MultiStageBatch\CallJob;
use Florisbosch\MultiStageBatch\MultiStageBatch;
use Florisbosch\MultiStageBatch\MultiStageJob;

trait InteractsWithStages
{
    protected MultiStageJob $multiStageJob;
    protected string $stageId = '';
    protected string $stageName = '';

    /**
     * Override the method we call to start the action
     * @var string
     */
    protected string $runStepMethod;

    public function initMultiStage(MultiStageJob $multiStageJob): void
    {
        // Set the workflow step
        $this->multiStageJob = $multiStageJob;

        // Find method to call
        $method = $this->runStepMethod ?? CallJob::callableMethod($this);
        $this->{$method}(...func_get_args());
    }

    /**
     * Get the workflow id
     * @return string
     */
    public function getStageId(): string
    {
        if ($this->multiStageJob) {
            return $this->multiStageJob->getStageId();
        }
    }

    /**
     * Get the stage from this job
     * @return string
     */
    public function getStageName(): string
    {
        if ($this->multiStageJob) {
            return $this->multiStageJob->getStageName();
        }
    }


    /**
     * Gets the cache for this workflow
     * @return TaggedCache
     */
    protected function getWorkflowCache(): TaggedCache
    {
        return MultiStageBatch::getMultiStageCache($this->getStageId());
    }
}
