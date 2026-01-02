<?php

use Bitrix\Crm\Feature;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$settings = [];
if (Loader::includeModule('crm'))
{
	$settings['callAssessmentAvailabilityEnabled'] = Feature::enabled(Feature\CopilotCallAssessmentAvailability::class);
}

return [
	'css' => 'dist/call-assessment.bundle.css',
	'js' => 'dist/call-assessment.bundle.js',
	'rel' => [
		'ui.vue3',
		'pull.queuemanager',
		'ui.buttons',
		'ui.bbcode.parser',
		'ui.text-editor',
		'ui.icon-set.main',
		'ui.notification',
		'ui.entity-selector',
		'ui.info-helper',
		'ui.forms',
		'main.date',
		'crm.timeline.tools',
		'main.core.events',
		'ui.date-picker',
		'main.core',
		'ui.design-tokens',
	],
	'skip_core' => false,
	'settings' => $settings,
];
