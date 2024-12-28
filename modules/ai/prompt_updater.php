<?php

/**
 * Use this file only when you want to update prompt's base immediately (during platform update).
 */

use Bitrix\AI\Updater;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;

$incVersion = 1286;
$incFormatVersion = 2;
$optCode = '~prompts_system_update_version';
$optFormatVersionCode = '~prompts_system_update_format_version';

$version = (int)Option::get('ai', $optCode);
$formatVersion = (int)Option::get('ai', $optFormatVersionCode);
if ($version >= $incVersion && $incFormatVersion === $formatVersion)
{
	return;
}

if (ModuleManager::isModuleInstalled('bitrix24'))
{
	$file = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/bitrix24/install/ai/prompts/world.json';
}
else
{
	$file = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/ai/install/prompts/world.json';
}

if (file_exists($file))
{
	Application::getInstance()->addBackgroundJob(function () use ($file, $incFormatVersion, $optFormatVersionCode, $incVersion, $optCode) {
		if (\defined('BX_CHECK_AGENT_START'))
		{
			return;
		}
		if (!ModuleManager::isModuleInstalled('ai'))
		{
			return;
		}

		Updater::refreshFromLocalFile($file);
		Option::set('ai', $optCode, $incVersion);
		Option::set('ai', $optFormatVersionCode, $incFormatVersion);
	});
}
