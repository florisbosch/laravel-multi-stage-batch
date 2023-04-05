# Laravel multi-stage batch

### WORK IN PROGRESS
- [ ] Add missing tests
- [ ] Add missing correct errorhandling
- [ ] Optimize InteractsWithStages to provide better sharing of the cache

laravel-multi-stage-batch is a package that allows you to run multiple stages after another in a Laravel application where the stages can run jobs in parallel. They can also share a cache between all stages.

## Installation
You can install the package via composer:

```bash
composer require florisbosch/laravel-multi-stage-batch
```

## Usage
To use the package, you need to create an instance of the MultiStageBatch class and add your stages with their respective jobs. You can then call the dispatch method to start the execution of the batch.

```php
    use Florisbosch\MultiStageBatch\MultiStageBatch;
    use Florisbosch\MultiStageBatch\Stage;

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
```
In the example above, we create an instance of MultiStageBatch with a name "Workflow 1". We then add three stages: "Stage 1", "Stage 2", and "Stage 3". Each stage can contain one or more jobs to execute. In the example, "Stage 1" has two jobs: a single function and an anonymous function. "Stage 2" has a single job that is an instance of DataExport. "Stage 3" has a single job that is a class name DataExport.

All jobs in a stage will run on the queue so you can set it up to work in parallel. This means that the jobs in "Stage 1" will run at the same time as the jobs in "Stage 2". Once all jobs in a stage are completed, the next stage will start executing.

The laravel-multi-stage-batch package uses a tagged Laravel's cache implementation you can use inside your jobs this way you can share data between stages.

### Testing

```bash
composer test
```

## Contributing
Contributions are welcome! Please feel free to submit a pull request or create an issue if you find any bugs or have any suggestions.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
