<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/examples')
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests');

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'no_superfluous_phpdoc_tags' => true,
        'nullable_type_declaration_for_default_null_value' => false,
        'assign_null_coalescing_to_coalesce_equal' => true,
        'php_unit_method_casing' => [
            'case' => 'snake_case',
        ],
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        'not_operator_with_successor_space' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
    ])
    ->setLineEnding("\n")
    ->setUsingCache(false)
    ->setRiskyAllowed(true)
    ->setFinder($finder);
