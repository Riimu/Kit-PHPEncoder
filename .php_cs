<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return Symfony\CS\Config\Config::create()
    ->fixers([
        'strict_param',
        'strict',
        'short_array_syntax',
        'php_unit_strict',
        'php_unit_construct',
        'php4_constructor',
        'ordered_use',
        'newline_after_open_tag',
        'multiline_spaces_before_semicolon',
        'ereg_to_preg',
        'concat_with_spaces',
        'align_double_arrow',

        '-unalign_double_arrow',
        '-unary_operators_spaces',
        '-phpdoc_separation',
        '-concat_without_spaces',
        '-phpdoc_params',
        '-linefeed',
        '-pre_increment',
    ])
    ->finder($finder);
