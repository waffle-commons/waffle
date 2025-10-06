<?php

$finder = PhpCsFixer\Finder::create()
    ->in(dirs: ['src', 'tests'])
;

$config = new PhpCsFixer\Config();

return $config
    ->setParallelConfig(new PhpCsFixer\Runner\Parallel\ParallelConfig(2, 10))
    ->setCacheFile(cacheFile: __DIR__ . '/var/cache/.php-cs-fixer.cache')
    ->setRules(rules: [
        '@PSR1' => true,
        '@PSR2' => true,
        '@PSR12' => true,
        '@PER-CS3x0' => true,
        '@PHP84Migration' => true,
        '@PhpCsFixer' => true,
        'concat_space' => ['spacing' => 'one'],
        'types_spaces' => ['space' => 'single'],
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
;
