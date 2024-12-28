<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/recent.bundle.css',
	'js' => 'dist/recent.bundle.js',
	'rel' => [
		'imopenlines.v2.css.tokens',
		'main.core',
		'im.v2.component.search.chat-search-input',
		'im.v2.component.search.chat-search',
		'im.v2.const',
		'imopenlines.v2.component.list.items.recent',
	],
	'skip_core' => false,
];
