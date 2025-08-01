<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'coverage',
        'stubs',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PHP80Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => [
            'sort_algorithm' => 'length',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => false,
        'trailing_comma_in_multiline' => true,
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
            ],
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'single_trait_insert_per_statement' => true,
        'declare_strict_types' => true,
        'void_return' => true,
    ])
    ->setFinder($finder)
    ->setUsingCache(true)
    ->setRiskyAllowed(true);
