<?php
namespace Bitrix\Mobile\AppTabs;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\WebComponentManager;
use Bitrix\Intranet\Invitation;


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

		$isIntranetInvitationAdmin = (
			Loader::includeModule('intranet')
			&& Invitation::canListDelete()
		);

		$canInvite = (
			Loader::includeModule('intranet')
			&& Invitation::canCurrentUserInvite()
		);

		$registerUrl = (
			$canInvite
				? Invitation::getRegisterUrl()
				: ''
		);

		$registerAdminConfirm = (
			$canInvite
				? Invitation::getRegisterAdminConfirm()
				: 'N'
		);

		$disableRegisterAdminConfirm = !Invitation::canListDelete();

		$registerSharingMessage = (
			$canInvite
				? Invitation::getRegisterSharingMessage()
				: ''
		);

		$rootStructureSectionId = Invitation::getRootStructureSectionId();

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
						"SITE_ID" => $this->context->siteId,
						"SITE_DIR" => $this->context->siteDir,
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LIMIT_ONLINE" => \CUser::GetSecondsForLimitOnline(),
						"IM_GENERAL_CHAT_ID" => \CIMChat::GetGeneralChatId(),
						"SEARCH_MIN_SIZE" => \Bitrix\Main\ORM\Query\Filter\Helper::getMinTokenSize()?: 3,
						"OPENLINES_USER_IS_OPERATOR" => $isOpenlinesOperator,
						"INTRANET_INVITATION_CAN_INVITE" => $canInvite,
						"INTRANET_INVITATION_ROOT_STRUCTURE_SECTION_ID" => $rootStructureSectionId,
						"INTRANET_INVITATION_REGISTER_URL" => $registerUrl,
						"INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM" => $registerAdminConfirm,
						"INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM_DISABLE" => $disableRegisterAdminConfirm,
						"INTRANET_INVITATION_REGISTER_SHARING_MESSAGE" => $registerSharingMessage,
						"INTRANET_INVITATION_IS_ADMIN" => $isIntranetInvitationAdmin,
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
					"settings" => ["useSearch" => true, "preload" => false, "useLargeTitleMode" => true],
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


	public function getShortTitle()
	{
		return Loc::getMessage("TAB_NAME_IM_RECENT_SHORT");
	}

	public function getId()
	{
		return "chats";
	}
}

