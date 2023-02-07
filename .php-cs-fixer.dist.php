<?php

//$header = <<<'EOF'
//This file is part of the SgDatatablesBundle package.
//
//(c) event it AG <https://github.com/eventit/DatatablesBundle>
//
//For the full copyright and license information, please view the LICENSE
//file that was distributed with this source code.
//EOF;

$header = <<<'EOF'
This file is part of the SgDatatablesBundle package.

<https://github.com/eventit/DatatablesBundle>
EOF;

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor/')
    ->in(__DIR__)
    ->append([__DIR__.'/php-cs-fixer'])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHPUnit60Migration:risky' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'header_comment' => ['header' => $header],
        'list_syntax' => ['syntax' => 'long'],
        'no_php4_constructor' => true,
        'no_superfluous_phpdoc_tags' => true,
        'no_useless_return' => true,
        'not_operator_with_successor_space' => true,
        'align_multiline_comment' => true,
        'array_indentation' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'compact_nullable_typehint' => true,
        'concat_space' => ['spacing' => 'one'],
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => true,
        'heredoc_to_nowdoc' => true,
        // 'logical_operators' => true,
        'multiline_comment_opening_closing' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'],
        'no_alternative_syntax' => true,
        'no_binary_string' => true,
        'no_null_property_initialization' => true,
        'echo_tag_syntax' => ['format' => 'long'],
        'no_superfluous_elseif' => true,
        'no_unset_cast' => true,
        // 'no_unset_on_property' => true,
        'no_useless_else' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
        // 'php_unit_set_up_tear_down_visibility' => true,
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => true],
        'phpdoc_order' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_var_annotation_correct_order' => true,
        'return_assignment' => true,
        // 'static_lambda' => true,
        // 'strict_comparison' => true,
        'single_line_throw' => false,
        'yoda_style' => ['equal' => null, 'identical' => null, 'less_and_greater' => null],
        'phpdoc_var_without_name' => true,
    ])
    ->setFinder($finder);
