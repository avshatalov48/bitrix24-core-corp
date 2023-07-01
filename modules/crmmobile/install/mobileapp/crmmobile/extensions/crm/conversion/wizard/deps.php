<?php

return [
	'extensions' => [
		'loc',
		'utils/array',
		'utils/object',
		'layout/ui/wizard',
		'layout/ui/banners',
		'layout/ui/wizard/step',
		'layout/ui/fields/string',
		'layout/ui/fields/boolean',
		'layout/ui/wizard/backdrop',

		'crm:loc',
		'crm:type',
		'crm:assets/entity',
		'crm:conversion/wizard/layout',
		'crm:conversion/wizard/fields',
		'crm:conversion/wizard/landing',
	],
	'bundle' => [
		'./step',
		'./layout',
	],
];