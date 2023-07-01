<?php
namespace Bitrix\Mobile\AppTabs;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Tab\Manager;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\WebComponentManager;
use Bitrix\Intranet\Invitation;
use Bitrix\MobileApp\Mobile;

class Chat implements Tabable
{
	private $context;

	public function isAvailable()
	{
		return (
			Loader::includeModule('im')
			&& Loader::includeModule('mobileapp')
		);
	}

	public function getData()
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		if ($this->isNextNavigation())
		{
			$component = $this->getNavigationComponent();
		}
		else
		{
			$component = $this->getRecentComponent();
		}

		return [
			"sort" => 100,
			"imageName" => "chat",
			"badgeCode" => "messages",
			"component" => $component,
		];
	}


	public function getMessengerComponent(): array
	{
		return [
			'name' => 'JSComponentChatRecent',
			'componentCode' => 'im.messenger',
			'scriptPath' => \Bitrix\MobileApp\Janative\Manager::getComponentPath('im:messenger'),
			'params' => array_merge(
				$this->getComponentParams(),
				[
					'TAB_CODE' => 'chats',
					'COMPONENT_CODE' => 'im.messenger',
					'MESSAGES' => [
						'COMPONENT_TITLE' => Loc::getMessage('TAB_NAME_IM_RECENT_FULL'),
					],
					'MIN_SEARCH_SIZE' => \Bitrix\Main\ORM\Query\Filter\Helper::getMinTokenSize(),
					'IS_NETWORK_SEARCH_AVAILABLE' => $this->isNetworkSearchAvailable(),
					'IS_DEVELOPMENT_ENVIRONMENT' => $this->isDevelopmentEnvironment(),
				]
			),
			'settings' => [
				'useSearch' => true,
				'preload' => true,
				'titleParams' => [
					'useLargeTitleMode' => true,
					'text' => Loc::getMessage('TAB_NAME_IM_RECENT_FULL'),
				],
			],
		];
	}

	public function getRecentComponent(): array
	{
		return [
			"name" => "JSComponentChatRecent",
			"componentCode" => "im.recent",
			"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("im:im.recent"),
			"params" => array_merge(
				$this->getComponentParams(),
				[
					"TAB_CODE" => "chats",
					"COMPONENT_CODE" => "im.recent",
					"MESSAGES" => [
						"COMPONENT_TITLE" => Loc::getMessage("TAB_NAME_IM_RECENT_FULL"),
					]
				]
			),
			"settings" => [
				"useSearch" => true,
				"preload" => true,
				"titleParams" => [
					"useLargeTitleMode" => true,
					"text" => Loc::getMessage("TAB_NAME_IM_RECENT_FULL")
				],
			],
		];
	}

	public function getNavigationComponent()
	{
		$chatComponent = $this->getMessengerComponent();
		$openlinesChatComponent = $this->getRecentComponent();

		// recent list
		$chats = [
			"id" => "chats",
			"title" => Loc::getMessage("TAB_NAME_IM_RECENT_SHORT"),
			"component" => $chatComponent
		];

		// imopenliens list
		if ($this->canShowLines())
		{
			$openlines = [
				"id" => "openlines",
				"title" => Loc::getMessage("TAB_NAME_IM_OPENLINES_SHORT"),
				"component" => array_merge(
					$openlinesChatComponent,
					[
						"componentCode" => "im.openlines.recent",
						"params" => array_merge(
							$openlinesChatComponent["params"],
							[
								"TAB_CODE" => "openlines",
								"COMPONENT_CODE" => "im.openlines.recent",
								"MESSAGES" => [
									"COMPONENT_TITLE" => Loc::getMessage("TAB_NAME_IM_RECENT_FULL"),
								]
							]
						),
						"settings" => array_merge(
							$openlinesChatComponent["settings"],
							[
								"preload" => false,
								"useSearch" => false,
							]
						),
					]
				)
			];
		}
		else
		{
			$openlines = null;
		}

		// notify
		$notifications = [
			"id" => "notifications",
			"title" => Loc::getMessage("TAB_NAME_NOTIFY"),
			"component" => [
				"name" => "JSStackComponent",
				"componentCode" => "im.notify.legacy",
				"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("im:im.notify.legacy"),
				"params" => [
					"MESSAGES" => [
						"COMPONENT_TITLE" => Loc::getMessage("TAB_NAME_IM_RECENT_FULL")
					]
				],
				"rootWidget" => [
					"name" => "web",
					"settings" => [
						"page" => [
							"url" => $this->context->siteDir . "mobile/im/notify.php?navigation",
							"preload" => false,
						],
						"objectName" => "widget",
						"titleParams" => [
							"useLargeTitleMode" => true,
							"text" => Loc::getMessage("TAB_NAME_IM_RECENT_FULL"),
						],
					],
				],
			],
		];

		$firstTabId = 'chats';
		if ($openlines)
		{
			if ($this->isLinesOperator())
			{
				$userPresetId = (new Manager())->getPresetName();
				if ($userPresetId === 'crm')
				{
					$firstTabId = 'openlines';
					$items = [$openlines, $chats, $notifications];
				}
				else
				{
					$items = [$chats, $openlines, $notifications];
				}
			}
			else
			{
				$items = [$chats, $notifications, $openlines];
			}
		}
		else
		{
			$items = [$chats, $notifications];
		}

		return [
			"name" => "JSStackComponent",
			"title" => Loc::getMessage("MD_COMPONENT_IM_RECENT"),
			"componentCode" => "im.navigation",
			"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("im:im.navigation"),
			"params" => [
				"firstTabId" => $firstTabId,
			],
			"rootWidget" => [
				"name" => "tabs",
				"settings" => [
					"objectName" => "tabs",
					"titleParams"=> [
						"text" => Loc::getMessage("TAB_NAME_IM_RECENT_FULL"),
						"useLargeTitleMode"=>true
					],
					"tabs" => [
						"items" => $items
					]
				]
			],
		];
	}

	private function isNetworkSearchAvailable()
	{
		return Loader::includeModule('imbot') && class_exists('\Bitrix\ImBot\Integration\Ui\EntitySelector\NetworkProvider');
	}

	private function getComponentParams()
	{
		$isOpenlinesOperator = $this->canShowLines();

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

		return [
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

			"WIDGET_CHAT_CREATE_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im:im.chat.create'),
			"WIDGET_CHAT_USERS_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im:im.chat.user.list'),
			"WIDGET_CHAT_RECIPIENTS_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im:im.chat.user.selector'),
			"WIDGET_CHAT_TRANSFER_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im:im.chat.transfer.selector'),
			"WIDGET_BACKDROP_MENU_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('backdrop.menu'),
			"COMPONENT_CHAT_DIALOG_VERSION" => WebComponentManager::getWebComponentVersion('im.dialog'),
			"COMPONENT_CHAT_DIALOG_VUE_VERSION" => WebComponentManager::getWebComponentVersion('im.dialog.vue'),

			"MESSAGES" => [
				"IMOL_CHAT_ANSWER_M" => \Bitrix\Im\Integration\Imopenlines\Localize::get(\Bitrix\Im\Integration\Imopenlines\Localize::FILE_LIB_CHAT, "IMOL_CHAT_ANSWER_M"),
				"IMOL_CHAT_ANSWER_F" => \Bitrix\Im\Integration\Imopenlines\Localize::get(\Bitrix\Im\Integration\Imopenlines\Localize::FILE_LIB_CHAT, "IMOL_CHAT_ANSWER_F")
			],

			"NEXT_NOTIFICATIONS" => $this->isNextNotifications() ? 'Y': 'N',
			"NEXT_NAVIGATION" => $this->isNextNavigation() ? 'Y': 'N',
		];
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

	public function canShowLines()
	{
		if ($this->context->extranet)
		{
			return false;
		}

		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('imopenlines'))
		{
			return false;
		}

		if ($this->isNextNavigation())
		{
			return true;
		}

		return $this->isLinesOperator();
	}

	public function isLinesOperator()
	{
		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('imopenlines'))
		{
			return false;
		}

		return \Bitrix\Im\Integration\Imopenlines\User::isOperator();
	}

	public function isNextNavigation()
	{
		return Mobile::getApiVersion() >= 41;
	}

	public function isNextNotifications()
	{
		return \Bitrix\Main\Config\Option::get("mobile", "NEXT_NOTIFICATIONS", "N") !== "N";
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

	public function isDevelopmentEnvironment(): bool
	{
		return \Bitrix\Main\Config\Option::get('immobile', 'IS_DEVELOPMENT_ENVIRONMENT', 'N') === 'Y';
	}
}
