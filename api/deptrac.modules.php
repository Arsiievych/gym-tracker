<?php

use Deptrac\Deptrac\Contract\Config\Collector\ClassLikeConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

return static function (DeptracConfig $config): void {
    $config->paths(__DIR__.'/app');

    // Each entry lists the other modules whose public API this module may use.
    $allowedDependencies = [
        'Workouts' => [],
    ];

    $moduleDirectories = glob(__DIR__.'/app/Modules/*', GLOB_ONLYDIR) ?: [];
    $moduleNames = array_unique([...array_keys($allowedDependencies), ...array_map('basename', $moduleDirectories)]);

    $application = Layer::withName('Application')->collectors(
        ClassLikeConfig::create('^App\\(?!(?:Modules|Providers)\\)'),
    );
    $config->layers($application)->rulesets(Ruleset::forLayer($application));

    $modules = [];
    $publicApi = '(?:Actions|Data)\\Api\\';

    foreach ($moduleNames as $name) {
        $namespace = "^App\\Modules\\{$name}\\";
        $modules[$name] = Layer::withName($name)->collectors(
            ClassLikeConfig::create($namespace.$publicApi),
            ClassLikeConfig::create($namespace.'(?!'.$publicApi.')')->private(),
        );
        $config->layers($modules[$name]);
    }

    foreach ($modules as $name => $layer) {
        $dependencies = array_map(static fn (string $dependency): Layer => $modules[$dependency], $allowedDependencies[$name] ?? []);
        $config->rulesets(Ruleset::forLayer($layer)->accesses(...$dependencies));
    }
};
