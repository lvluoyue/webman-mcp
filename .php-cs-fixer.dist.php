<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in('./src')
    ->notPath(['config']);
$config = new Config();

return $config->setRules([
    '@Symfony' => true,
    'array_syntax' => ['syntax' => 'short'],
    'list_syntax' => ['syntax' => 'short'],
    'elseif' => false,
    'single_space_around_construct' => true,
    'control_structure_braces' => true,
    'control_structure_continuation_position' => true,
    'declare_parentheses' => true,
    'no_multiple_statements_per_line' => true,
    'braces_position' => false,
    'statement_indentation' => true,
    'no_extra_blank_lines' => true,
    'concat_space' => [
        'spacing' => 'one',
    ],
    'declare_strict_types' => false,
    'heredoc_to_nowdoc' => true,
    'linebreak_after_opening_tag' => true,
    'new_with_parentheses' => false,
    'multiline_whitespace_before_semicolons' => false,
    'ordered_imports' => true,
    'phpdoc_add_missing_param_annotation' => false,
    'phpdoc_align' => false,
    'phpdoc_annotation_without_dot' => false,
    'phpdoc_separation' => false,
    'phpdoc_to_comment' => false,
    'phpdoc_var_without_name' => true,
    'unary_operator_spaces' => false,
    'semicolon_after_instruction' => true,
    'yoda_style' => false,
    'single_line_throw' => false,
    'php_unit_method_casing' => false,
    'blank_line_between_import_groups' => false,
    'global_namespace_import' => false,
    'nullable_type_declaration_for_default_null_value' => true,
    'blank_line_before_statement' => false,
])
    ->setFinder($finder);
