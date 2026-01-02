<?php

namespace Bitrix\Mobile\Profile\Tab;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Profile\Enum\TabContextType;
use Bitrix\Mobile\Profile\Enum\TabType;

class LiveFeedTab extends BaseProfileTab
{
	/**
	 * @return TabType
	 */
	public function getType(): TabType
	{
		return TabType::LIVE_FEED;
	}

	/**
	 * @return TabContextType
	 */
	public function getContextType(): TabContextType
	{
		return TabContextType::COMPONENT;
	}

	/**
	 * @return bool
	 */
	public function isAvailable(): bool
	{
		$isToolAvailable = (
			!Loader::includeModule('intranet')
			|| ToolsManager::getInstance()->checkAvailabilityByToolId('news')
		);

		return $isToolAvailable && Loader::includeModule('socialnetwork');
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return Loc::getMessage('PROFILE_TAB_LIVE_FEED_TITLE');
	}

	public function getComponent(): ?array
	{
		$siteDir = SITE_DIR;

		return [
			'name' => 'JSStackComponent',
			'componentCode' => $this->getType()->value,
			'rootWidget' => [
				'name' => 'web',
				'settings' => [
					'page' => [
						'url' => "{$siteDir}mobile/index.php?blog=Y&created_by_id=$this->ownerId",
						'preload' => false,
					],
				],
			],
		];
	}
}
