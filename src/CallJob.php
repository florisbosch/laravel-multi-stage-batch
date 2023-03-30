<?php

namespace Florisbosch\MultiStageBatch;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Closure;
use Laravel\SerializableClosure\Serializers\Native;
use RuntimeException;
use Florisbosch\MultiStageBatch\Concerns\InteractsWithStages;

class CallJob
{
    use InteractsWithStages;

    /**
     * The step to send to the function
     * @var MultiStageJob
     */
    protected MultiStageJob $job;
    /**
     * The serializable Closure instance.
     *
     * @var Closure|Native
     */
    protected Closure|Native $closure;

    /**
     * Create a new job instance.
     *
     * @param  MultiStageJob  $job
     * @param  Closure  $closure
     */
    public function __construct(MultiStageJob $job, Closure $closure)
    {
        $this->job = $job;
        $this->closure = $closure;
    }

    /**
     * Create a new step instance.
     *
     * @param  MultiStageJob $job
     * @param  Closure  $closure
     * @return CallJob
     */
    public static function fromClosure(MultiStageJob $job, Closure $closure): self
    {
        return new self($job, $closure);
    }

    /**
     * Create a new step instance.
     *
     * @param  string|object  $class  class name or instance
     * @return CallJob
     * @throws BindingResolutionException
     */
    public static function fromClass(MultiStageJob $job, string|object $class): self
    {
        // Get instance from string if needed
        $class = is_string($class) ? app()->make($class) : $class;
        // Get the method to execute
        if (in_array(InteractsWithStages::class, class_uses_recursive($class))) {
            $method = 'initMultiStage';
        } else {
            $method = self::callableMethod($class);
        }

        // Create the workflowstep
        return self::fromClosure($job, Closure::fromCallable([$class, $method]));
    }

    public static function callableMethod(object $class): string
    {
        if (method_exists($class, '__invoke')) {
            return '__invoke';
        } else if (method_exists($class, 'handle')) {
            return 'handle';
        } else if (method_exists($class, 'execute')) {
            return 'execute';
        } else if (method_exists($class, 'runStep')) {
            return 'runStep';
        }

        throw new RuntimeException("No callable method found");
    }
    /**
     * Get the actual closure from this workflow step
     * @return Closure
     */
    public function getClosure(): Closure
    {
        return Closure::fromCallable([$this, 'handle']);
    }

    /**
     * Execute the job. (with container things are injected if needed
     *
     * @param  Container  $container
     * @return void
     */
    public function handle(Container $container): void
    {
        $container->call($this->closure, [
            'job' => $this->job,
        ]);
    }
}
