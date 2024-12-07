<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$ai_default_option = [
	'check_limits' => \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24') ?  'Y' : 'N',
	'max_history_per_user' => 30,
	'stable_diffusion_bearer' => 'FREE',
];
