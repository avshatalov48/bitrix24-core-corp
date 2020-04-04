<?php
namespace Bitrix\Mobile\AppTabs;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\WebComponentManager;


class Chat implements Tabable
{
	private $context;

	public function isAvailable()
	{
		return \Bitrix\Main\Loader::includeModule('im');
	}

	public function getData()
	{
		$isOpenlinesOperator = (
			!$this->context->extranet
			&& \Bitrix\Main\Loader::includeModule('im')
			&& \Bitrix\Im\Integration\Imopenlines\User::isOperator()
		);
		if ($this->isAvailable())
		{
			return [
				"sort" => 100,
				"imageName" => "chat",
				"badgeCode" => "messages",
				"component" => [
					"name" => "JSComponentChatRecent",
					"title" => GetMessage("MD_COMPONENT_IM_RECENT"),
					"componentCode" => "im.recent",
					"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("im.recent"),
					"params" => [
						"COMPONENT_CODE" => "im.recent",
						"USER_ID" => $this->context->userId,
						"OPENLINES_USER_IS_OPERATOR" => $isOpenlinesOperator,
						"SITE_ID" => $this->context->siteId,
						"LANGUAGE_ID" => LANGUAGE_ID,
						"SITE_DIR" => $this->context->siteDir,
						"LIMIT_ONLINE" => \CUser::GetSecondsForLimitOnline(),
						"IM_GENERAL_CHAT_ID" => \CIMChat::GetGeneralChatId(),
						"SEARCH_MIN_SIZE" => \CSQLWhere::GetMinTokenSize(),

						"WIDGET_CHAT_CREATE_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im.chat.create'),
						"WIDGET_CHAT_USERS_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im.chat.user.list'),
						"WIDGET_CHAT_RECIPIENTS_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im.chat.user.selector'),
						"WIDGET_CHAT_TRANSFER_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im.chat.transfer.selector'),
						"WIDGET_BACKDROP_MENU_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('backdrop.menu'),
						"COMPONENT_CHAT_DIALOG_VERSION" => WebComponentManager::getWebComponentVersion('im.dialog'),
						"COMPONENT_CHAT_DIALOG_VUE_VERSION" => WebComponentManager::getWebComponentVersion('im.dialog.vue'),

						"MESSAGES" => [
							"COMPONENT_TITLE" => GetMessage("MD_COMPONENT_IM_RECENT"),
							"IMOL_CHAT_ANSWER_M" => \Bitrix\Im\Integration\Imopenlines\Localize::get(\Bitrix\Im\Integration\Imopenlines\Localize::FILE_LIB_CHAT, "IMOL_CHAT_ANSWER_M"),
							"IMOL_CHAT_ANSWER_F" => \Bitrix\Im\Integration\Imopenlines\Localize::get(\Bitrix\Im\Integration\Imopenlines\Localize::FILE_LIB_CHAT, "IMOL_CHAT_ANSWER_F")
						]
					],
					"settings" => ["useSearch" => true, "preload" => false],
				],
			];
		}

		return null;
	}

	public function getMenuData()
	{
		return null;
	}

	public function shouldShowInMenu()
	{
		return false;
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
		return 100;
	}

	public function canChangeSort()
	{
		return true;
	}

	public function getTitle()
	{
		return Loc::getMessage("TAB_NAME_IM_RECENT");
	}

	public function setContext($context)
	{
		$this->context = $context;
	}


}

