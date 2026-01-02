<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\RepeatSale\Widget\WidgetManager;

$settings = [
	'feedbackFormParams' => WidgetManager::getInstance()->getFeedbackFormParams(),
];

return [
	'css' => 'dist/widget.bundle.css',
	'js' => 'dist/widget.bundle.js',
	'rel' => [
		'ui.confetti',
		'ui.notification',
		'crm.timeline.tools',
		'ui.feedback.form',
		'crm.integration.analytics',
		'main.core',
		'main.popup',
		'ui.analytics',
		'ui.lottie',
		'ui.hint',
		'ui.design-tokens',
		'ui.design-tokens.air',
	],
	'skip_core' => false,
	'settings' => $settings,
];
