<?php
namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use \Bitrix\MobileApp\Mobile;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\WebComponentManager;

class OpenLines implements Tabable
{
	private $context;

	public function isAvailable()
	{
		if (!Loader::includeModule('mobileapp') || !Loader::includeModule('im'))
		{
			return false;
		}

		if (Mobile::getApiVersion() >= 41)
		{
			return false;
		}

		if ($this->context->extranet)
		{
			return false;
		}

		return \Bitrix\Im\Integration\Imopenlines\User::isOperator();
	}

	public function getData()
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		return [
			"sort" => 150,
			"imageName" => "openlines",
			"badgeCode" => "openlines",
			"component" => [
				"name" => "JSComponentChatRecent",
				"title" => GetMessage("MD_COMPONENT_IM_OPENLINES"),
				"componentCode" => "im.openlines.recent",
				"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("im.recent"), // TODO change
				"params" => [
					"COMPONENT_CODE" => "im.openlines.recent",
					"USER_ID" => $this->context->userId,
					"OPENLINES_USER_IS_OPERATOR" => true,
					"SITE_ID" => $this->context->siteId,
					"SITE_DIR" => $this->context->siteDir,
					"LANGUAGE_ID" => LANGUAGE_ID,
					"LIMIT_ONLINE" => \CUser::GetSecondsForLimitOnline(),
					"IM_GENERAL_CHAT_ID" => \CIMChat::GetGeneralChatId(),
					"SEARCH_MIN_SIZE" => \CSQLWhere::GetMinTokenSize(),

					"WIDGET_CHAT_USERS_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im:im.chat.user.list'),
					"WIDGET_CHAT_RECIPIENTS_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im:im.chat.user.selector'),
					"WIDGET_CHAT_TRANSFER_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im:im.chat.transfer.selector'),
					"COMPONENT_CHAT_DIALOG_VERSION" => WebComponentManager::getWebComponentVersion('im.dialog'),
					"COMPONENT_CHAT_DIALOG_VUE_VERSION" => WebComponentManager::getWebComponentVersion('im.dialog.vue'),

					"MESSAGES" => [
						"COMPONENT_TITLE" => GetMessage("MD_COMPONENT_IM_OPENLINES"),
						"IMOL_CHAT_ANSWER_M" => \Bitrix\Im\Integration\Imopenlines\Localize::get(\Bitrix\Im\Integration\Imopenlines\Localize::FILE_LIB_CHAT, "IMOL_CHAT_ANSWER_M"),
						"IMOL_CHAT_ANSWER_F" => \Bitrix\Im\Integration\Imopenlines\Localize::get(\Bitrix\Im\Integration\Imopenlines\Localize::FILE_LIB_CHAT, "IMOL_CHAT_ANSWER_F")
					]
				],
				"settings" => [
					"useSearch" => false,
					"preload" => false,
					"useLargeTitleMode" => true
				],
			]
		];
	}

	/**
	 * @return boolean
	 */
	public function shouldShowInMenu()
	{
		return false;
	}

	/**
	 * @return null|array
	 */
	public function getMenuData()
	{
		return null;
	}

	public function canBeRemoved()
	{
		return false;
	}

	/**
	 * @return integer
	 */
	public function defaultSortValue()
	{
		return 150;
	}

	public function canChangeSort()
	{
		return true;
	}

	public function getTitle()
	{
		return Loc::getMessage("TAB_NAME_IM_OPENLINES");
	}

	public function setContext($context)
	{
		$this->context = $context;
	}

	public function getShortTitle()
	{
		return Loc::getMessage("TAB_NAME_IM_OPENLINES_SHORT");
	}

	public function getId()
	{
		return "ol";
	}
}