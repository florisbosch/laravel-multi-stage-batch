<?php

namespace Florisbosch\MultiStageBatch;

use Illuminate\Bus\Batch;
use Illuminate\Cache\TaggedCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Closure;;
use Throwable;
use Illuminate\Support\Facades\Cache;

class MultiStageBatch
{
    public const    STATUS_PENDING      = 'pending',
                    STATUS_PROCESSING   = 'processing',
                    STATUS_DONE         = 'done',
                    STATUS_FAILED       = 'processing';

    /**
     * @var string
     */
    private string $id;

    // Steps are seperated so we don't push the whole classes in the cache
    protected Collection $steps;
    protected Collection $stages;

    protected array $settings;

    public function __construct(protected string $name, array $settings = [])
    {
        $this->id = Str::uuid();
        $this->stages = collect([]);
        $this->steps = collect([]);

        // Add default settings?
        $this->settings = $settings;

        // Set data in the cache
        self::getMultiStageCache($this->id)->put('settings', $settings);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Append a s tage with steps to the end of a stage.
     *
     * @param  string  $stage
     * @param  array  $steps
     * @return $this
     */
    public function addStage(string $stage, array $steps): self
    {
        $steps = Collection::wrap($steps)->map(function ($step) use ($stage) {
            $job = new MultiStageJob($this->id, $stage);
            $step = $step instanceof Closure
                ? CallJob::fromClosure(job: $job, closure: $step)
                : CallJob::fromClass(job: $job, class: $step);

            return $step->getClosure();
        });

        // Add or get the stage by name and then create steps
        tap($this->stages->getOrPut($stage, [
            'id' => Str::uuid()->toString(),
            'name' => $stage,
            'status' => self::STATUS_PENDING
        ]), function($stage) use ($steps) {
            $this->steps->put($stage['id'], [
                ...$this->steps->get($stage['id'], []),
                ...$steps
            ]);
        });

        return $this;
    }

    /**
     * Dispatch the workflows
     * @return void
     */
    public function dispatch(): void
    {
        // Put the stages in the cache
        MultiStageBatch::getMultiStageCache($this->id)->put('stages', $this->stages);

        // Start the workflow
        MultiStageBatch::startNextStage(multiStageId: $this->id, multiStageSteps: $this->steps);
    }

    public static function startNextStage(string $multiStageId, Collection $multiStageSteps): void
    {
        // Get stage statusses from cache
        $stages = MultiStageBatch::getMultiStageCache($multiStageId)->get('stages');

        // Get the next stage
        $nextStage = $stages->firstWhere('status', MultiStageBatch::STATUS_PENDING);

        if(!isset($nextStage)) {
            // No more pending stages
            return;
        }

        $stageInfo = [
            'stageId' => $multiStageId,
            'stageSteps' => $multiStageSteps,
            'stage' => $nextStage
        ];

        tap($multiStageSteps->get($nextStage['id']), function ($steps) use ($stageInfo) {
            $batch = Bus::batch($steps)
                ->then(function(Batch $batch) use ($stageInfo) {
                    // Update stage
                    $stageInfo['stage']['status'] = MultiStageBatch::STATUS_DONE;
                    MultiStageBatch::updateStage($stageInfo['id'], $stageInfo['stage']);

                    // Stage is succesful so start next stage
                    MultiStageBatch::startNextStage(
                        multiStageId: $stageInfo['multiStageId'],
                        multiStageSteps: $stageInfo['multiStageSteps']
                    );
                })->catch(function(Batch $batch, Throwable $e) use ($stageInfo) {
                    // Update stage
                    $stageInfo['stage']['status'] = MultiStageBatch::STATUS_FAILED;
                    MultiStageBatch::updateStage($stageInfo['id'], $stageInfo['stage']);

                    // step in stage errored catch en handle
                    Log::info($e->getMessage());

                })->finally(function (Batch $batch) use ($stageInfo) {
                    $stages = self::getMultiStageCache($stageInfo['id'])->get('stages');
                    Log::info($stages);
                    // If there are no more stages left we are done
                    if ($stages->where('status', MultiStageBatch::STATUS_PENDING)->count() === 0) {
                        // Retrieve the cache  :)
                        Log::info(MultiStageBatch::getMultiStageCache($stageInfo['workflowId'])->get('dummyjob'));
                        Log::info("Syndication workflow complete");
                    }

                })
                ->dispatch();

            // Update stage
            $stageInfo['stage']['status'] = MultiStageBatch::STATUS_PROCESSING;
            MultiStageBatch::updateStage($stageInfo['id'], $stageInfo['stage']);
        });
    }

    /**
     * Update the stage in the cahce
     * @param  string  $stageId
     * @param  array  $updatedStage
     */
    public static function updateStage(string $stageId, array $updatedStage): void
    {
        // Retrieve from cache
        $cache = MultiStageBatch::getMultiStageCache($stageId);
        $stages = $cache->get('stages');

        // Change value in collection
        $stages->put($updatedStage['name'], $updatedStage);

        // Push value to cache
        $cache->put('stages', $stages);
    }

    /**
     * Gets the cache for this workflow
     * @return TaggedCache
     */
    public static function getMultiStageCache(string $multiStageId): TaggedCache
    {
        return Cache::tags([
            config('multi-stage-batch.cache_tag'),
            $multiStageId
        ]);
    }
}
