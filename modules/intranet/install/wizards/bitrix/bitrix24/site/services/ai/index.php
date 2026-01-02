<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (\Bitrix\Main\Loader::includeModule('ai') && \Bitrix\AI\Facade\Bitrix24::shouldUseB24())
{
	\COption::SetOptionString(
		'ai',
		'bitrixaudio_modules',
		json_encode(['crm'])
	);
	\COption::SetOptionString(
		'ai',
		'bitrixaudio_availableIn',
		json_encode(['crm_copilot_fill_item_from_call_engine_audio'])
	);
	\COption::SetOptionString(
		'ai',
		'bitrixaudio_portalSettingsItemsToForceReset',
		json_encode(['crm_copilot_fill_item_from_call_engine_audio'])
	);

	/** @see \Bitrix\AI\Agents\EngineSettings::resetToBitrixAudioInCloudAgent */
	\CAgent::AddAgent(
		'\Bitrix\AI\Agents\EngineSettings::resetToBitrixAudioInCloudAgent();',
		'ai',
		interval: 3600,
		next_exec: ConvertTimeStamp(time() + CTimeZone::GetOffset() + 600, 'FULL'),
	);

	/** @see \Bitrix\AI\Agents\EngineSettings::resetToBitrixGPTInCloudAgent */
	\CAgent::AddAgent(
		'\Bitrix\AI\Agents\EngineSettings::resetToBitrixGPTInCloudAgent();',
		'ai',
		interval: 3600,
		next_exec: ConvertTimeStamp(time() + CTimeZone::GetOffset() + 600, 'FULL'),
	);
} 