<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return Symfony\CS\Config\Config::create()
    ->fixers([
        'align_double_arrow',
        'concat_with_spaces',
        'ereg_to_preg',
        'multiline_spaces_before_semicolon',
        'newline_after_open_tag',
        'ordered_use',
        'php4_constructor',
        'php_unit_construct',
        'php_unit_strict',
        'short_array_syntax',
        'strict',
        'strict_param',

        '-concat_without_spaces',
        '-linefeed',
        '-phpdoc_no_empty_return',
        '-phpdoc_params',
        '-phpdoc_separation',
        '-pre_increment',
        '-unalign_double_arrow',
        '-unary_operators_spaces',
    ])
    ->finder($finder);
