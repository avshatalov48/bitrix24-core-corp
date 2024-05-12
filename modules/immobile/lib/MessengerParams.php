<?php

namespace Bitrix\ImMobile;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class MessengerParams
{
	public static function get(): array
	{
		$isCloud = ModuleManager::isModuleInstalled('bitrix24') && defined('BX24_HOST_NAME');

		$hasActiveBucket = false;
		if (Loader::includeModule('clouds'))
		{
			$buckets = \CCloudStorageBucket::getAllBuckets();
			foreach ($buckets as $bucket)
			{
				if ($bucket['ACTIVE'] === 'Y' && $bucket['READ_ONLY'] !== 'Y')
				{
					$hasActiveBucket = true;
					break;
				}
			}
		}

		return [
			'IS_CLOUD' => $isCloud,
			'HAS_ACTIVE_CLOUD_STORAGE_BUCKET' => $hasActiveBucket,
			'IS_BETA_AVAILABLE' => Settings::isBetaAvailable(),
			'IS_CHAT_M1_ENABLED' => Settings::isChatM1Enabled(),
			'IS_CHAT_LOCAL_STORAGE_AVAILABLE' => Settings::isChatLocalStorageAvailable(),
			'SHOULD_SHOW_CHAT_V2_UPDATE_HINT' => Settings::shouldShowChatV2UpdateHint(),
			'SMILE_LAST_UPDATE_DATE' => \CSmile::getLastUpdate()->format(\DateTime::ATOM),
			'IS_COPILOT_AVAILABLE' => Settings::isCopilotAvailable(),
			'IS_COPILOT_ADD_USERS' => Settings::isCopilotAddUsersEnabled(),
		];
	}
}
