<?php

namespace Bitrix\ImMobile\NavigationTab;

use Bitrix\Im\V2\Integration\AI\Transcription\TranscribeManager;
use Bitrix\Im\V2\Integration\HumanResources\Structure;
use Bitrix\Main\EventManager;
use DateTimeInterface;
use CCloudStorageBucket;
use CSmile;
use CUser;
use Bitrix\Mobile;
use Bitrix\MobileApp;
use Bitrix\Im\V2\Chat\GeneralChat;
use Bitrix\Im\V2\Chat\InputAction\StatusMessageProvider;
use Bitrix\Im\V2\Chat\InputAction\Platform;
use Bitrix\ImMobile\Settings;
use Bitrix\ImMobile\User;
use Bitrix\Im\Integration\Imopenlines\Localize;
use Bitrix\Im\Integration\Imopenlines;
use Bitrix\Intranet\Invitation;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Filter\Helper;

class Manager
{
	use MessengerComponentTitle;

	private Mobile\Context $context;

	private Tab\Copilot $copilot;
	private Tab\Messenger $messenger;
	private Tab\OpenLines $openLines;
	private Tab\Channel $channel;
	private Tab\Collab $collab;
	private Tab\Task $task;

	public function __construct(Mobile\Context $context)
	{
		$this->context = $context;

		$this->messenger = new Tab\Messenger();
		$this->copilot = new Tab\Copilot();
		$this->openLines = new Tab\OpenLines($context);
		$this->channel = new Tab\Channel();
		$this->collab = new Tab\Collab();
		$this->task = new Tab\Task();
	}

	public static function getShortTitle()
	{
		return Loc::getMessage('IMMOBILE_NAVIGATION_TAB_SHORT_TITLE');
	}

	public function getComponent(): array
	{
		if (Settings::isMessengerV2Enabled())
		{
			return $this->getMessengerV2Component();
		}

		return $this->getMessengerComponent();
	}

	public function getMessengerV2Component(): array
	{
		$sortedTabs = $this->getSortedItems();
		/**
		 * @type Tab\BaseRecent[] $items
		 */
		$items = array_values(
			array_filter($sortedTabs, static fn ($item) => $item->isWidgetAvailable()
				|| ($item->getId() === 'openlines' && $item->isAvailable())
			)
		);

		$params = $this->getMessengerV2Params($sortedTabs);
		//TODO add cache name by md5 by params;
		$itemsData = [];
		foreach ($items as $item)
		{
			if ($item->getId() === 'openlines')
			{
				$openLinesParams = $this->getMessengerParams($items);
				$item->mergeParams($openLinesParams['SHARED_PARAMS']);
				$itemsData[] = $item->getComponentData();

				continue;
			}

			$itemsData[] = $item->getWidgetData();
		}


		$encodedParams = '';
		try {
			$encodedParams = json_encode($params);
		}
		catch (\Throwable $error)
		{

		}

		return [
			'sort' => 100,
			'imageName' => 'chat',
			'cacheId' => $this->getCacheId($encodedParams),
			'badgeCode' => 'messages',
			'component' => [
				'name' => 'JSStackComponent',
				'title' => Loc::getMessage('MD_COMPONENT_IM_RECENT'),
				'componentCode' => 'im.messenger',
				'scriptPath' => MobileApp\Janative\Manager::getComponentPath('im:messenger-v2'),
				'params' => $params,
				'rootWidget' => [
					'name' => 'tabs',
					'settings' => [
						'code' => 'im.tabs',
						'preload' => true,
						'objectName' => 'tabs',
						'titleParams'=> [
							'text' => $this->getTitle(),
							'useLargeTitleMode' => true
						],
						'grabTitle' => false,
						'grabButtons' => true,
						'tabs' => [
							'items' => $itemsData,
						],
					],
				],
			],
		];
	}

	private function getTabs(): array
	{
		return [
			$this->messenger,
			$this->copilot,
			$this->openLines,
			$this->channel,
			$this->collab,
			$this->task,
		];
	}

	private function getCacheId($additionalString = ''): string
	{
		$enabledIds = [];
		$tabs = $this->getTabs();

		foreach ($tabs as $tab)
		{
			if ($tab->isAvailable())
			{
				$enabledIds[] = $tab->getId();
			}
		}

		sort($enabledIds);
		return 'chat_tabs_' . hash('sha256', implode('_', $enabledIds) . $additionalString);
	}

	public function getMessengerComponent(): array
	{
		/**
		 * @type Tab\TabInterface[] $items
		 */
		$items = array_values(
			array_filter($this->getSortedItems(), static fn ($item) => $item->isAvailable())
		);

		$itemsData = [];
		$params = $this->getMessengerParams($items);
		foreach ($items as $item)
		{
			if ($item->getId() === 'openlines')
			{
				$item->mergeParams($params['SHARED_PARAMS']);
			}

			$itemsData[] = $item->getComponentData();
		}

		return [
			'sort' => 100,
			'imageName' => 'chat',
			'cacheId' => $this->getCacheId(),
			'badgeCode' => 'messages',
			'component' => [
				'name' => 'JSStackComponent',
				'title' => Loc::getMessage('MD_COMPONENT_IM_RECENT'),
				'componentCode' => 'im.navigation',
				'scriptPath' => MobileApp\Janative\Manager::getComponentPath('im:im.navigation'),
				'params' => array_merge([
					'COMPONENT_CODE' => 'im.navigation',
					'firstTabId' => $items[0]->getId(),
				], $params),
				'rootWidget' => [
					'name' => 'tabs',
					'settings' => [
						'code' => 'im.tabs',
						'objectName' => 'tabs',
						'titleParams'=> [
							'text' => $this->getTitle(),
							'useLargeTitleMode' => true
						],
						'tabs' => [
							'items' => $itemsData,
						],
					],
				],
			],
		];
	}

	/**
	 * @return Tab\TabInterface[]
	 */
	private function getSortedItems(): array
	{
		if ($this->isLinesOperator())
		{
			if ((new Mobile\Tab\Manager())->getPresetName() === 'crm')
			{
				return $this->getCrmOpenLineOperatorPreset();
			}

			return $this->getOpenLineOperatorPreset();
		}

		return $this->getDefaultPreset();
	}

	private function getCrmOpenLineOperatorPreset(): array
	{
		return [
			$this->openLines,
			$this->messenger,
			$this->copilot,
			$this->task,
			$this->channel,
			$this->collab,
		];
	}

	private function getOpenLineOperatorPreset(): array
	{
		return [
			$this->messenger,
			$this->openLines,
			$this->copilot,
			$this->task,
			$this->channel,
			$this->collab,
		];
	}

	private function getDefaultPreset(): array
	{
		return [
			$this->messenger,
			$this->copilot,
			$this->task,
			$this->channel,
			$this->collab,
			$this->openLines,
		];
	}

	/**
	 * @param Tab\BaseRecent[] $tabs
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function getMessengerV2Params(array $tabs): array
	{
		$availableTabs = [];
		foreach ($tabs as $tab)
		{
			$availableTabs[$tab->getId()] = $tab->isWidgetAvailable();
		}

		return [
			...$this->getSharedParams($tabs),
			'COMPONENT_CODE' => 'im.messenger',
			'AVAILABLE_TABS' => $availableTabs,
		];
	}

	/**
	 * @param Tab\TabInterface[] $tabs
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function getMessengerParams(array $tabs): array
	{
		return [
			'SHARED_PARAMS' => [
				...$this->getSharedParams($tabs),
				'AVAILABLE_MESSENGER_COMPONENTS' => [
					$this->messenger->getComponentCode() => $this->messenger->isAvailable(),
					$this->copilot->getComponentCode() => $this->copilot->isAvailable(),
					$this->channel->getComponentCode() => $this->channel->isAvailable(),
					$this->collab->getComponentCode() => $this->collab->isAvailable(),
					$this->openLines->getComponentCode() => $this->openLines->isAvailable(),
				],
				'PRELOADED_MESSENGER_COMPONENTS' => [
					$this->messenger->getComponentCode() => $this->messenger->isPreload(),
					$this->copilot->getComponentCode() => $this->copilot->isPreload(),
					$this->channel->getComponentCode() => $this->channel->isPreload(),
					$this->collab->getComponentCode() => $this->collab->isPreload(),
					$this->openLines->getComponentCode() => $this->openLines->isPreload(),
				],
				'WIDGET_CHAT_CREATE_VERSION' => MobileApp\Janative\Manager::getComponentVersion('im:im.chat.create'),
				'WIDGET_CHAT_USERS_VERSION' => MobileApp\Janative\Manager::getComponentVersion('im:im.chat.user.list'),
				'WIDGET_CHAT_RECIPIENTS_VERSION' => MobileApp\Janative\Manager::getComponentVersion('im:im.chat.user.selector'),
				'WIDGET_CHAT_TRANSFER_VERSION' => MobileApp\Janative\Manager::getComponentVersion('im:im.chat.transfer.selector'),
				'WIDGET_BACKDROP_MENU_VERSION' => MobileApp\Janative\Manager::getComponentVersion('backdrop.menu'),
				...$this->getInvitationParams(),
			],
		];
	}

	/**
	 * @param Tab\TabInterface[] $tabs
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function getSharedParams(array $tabs): array
	{
		$permissions = [];
		if (Loader::includeModule('im'))
		{
			$permissionManager = new \Bitrix\Im\V2\Permission(true);
			$permissions = [
				'byChatType' => $permissionManager->getByChatTypes(),
				'byUserType' => $permissionManager->getByUserTypes(),
				'actionGroups' => $permissionManager->getActionGroupDefinitions(),
				'actionGroupsDefaults' => $permissionManager->getDefaultPermissionForGroupActions(),
			];
		}

		$isCloud = ModuleManager::isModuleInstalled('bitrix24') && defined('BX24_HOST_NAME');

		$hasActiveBucket = false;
		if (Loader::includeModule('clouds'))
		{
			$buckets = CCloudStorageBucket::getAllBuckets();
			foreach ($buckets as $bucket)
			{
				if ($bucket['ACTIVE'] === 'Y' && $bucket['READ_ONLY'] !== 'Y')
				{
					$hasActiveBucket = true;
					break;
				}
			}
		}

		$firstTabId = '';
		if (count($tabs) > 0)
		{
			$firstTabId = $tabs[0]->getId();
		}
		$enableDevWorkspace = false;
		if (Option::get('mobile', 'developers_menu_section', 'N') === 'Y')
		{
			foreach (EventManager::getInstance()->findEventHandlers("mobileapp", "onJNComponentWorkspaceGet", ['immobile']) as $event)
			{
				if ($event['TO_METHOD'] === 'getImmobileJNDevWorkspace')
				{
					$enableDevWorkspace = true;
				}
			}
		}

		return [
			'USER_ID' => $this->context->userId,
			'SITE_ID' => $this->context->siteId,
			'SITE_DIR' => $this->context->siteDir,
			'LANGUAGE_ID' => LANGUAGE_ID,
			'LIMIT_ONLINE' => CUser::GetSecondsForLimitOnline(),
			'IM_GENERAL_CHAT_ID' => GeneralChat::getGeneralChatId(),
			'SEARCH_MIN_SIZE' => Helper::getMinTokenSize()?: 3,
			'OPENLINES_USER_IS_OPERATOR' => $this->openLines->isAvailable(),

			'COMPONENT_CHAT_DIALOG_VERSION' => Mobile\WebComponentManager::getWebComponentVersion('im.dialog'),
			'COMPONENT_CHAT_DIALOG_VUE_VERSION' => Mobile\WebComponentManager::getWebComponentVersion('im.dialog.vue'),
			'IS_NETWORK_SEARCH_AVAILABLE' => $this->isNetworkSearchAvailable(),
			'IS_DEVELOPMENT_ENVIRONMENT' => $this->isDevelopmentEnvironment(),
			'MESSAGES' => [
				'IMOL_CHAT_ANSWER_M' => Localize::get(Localize::FILE_LIB_CHAT, "IMOL_CHAT_ANSWER_M"),
				'IMOL_CHAT_ANSWER_F' => Localize::get(Localize::FILE_LIB_CHAT, "IMOL_CHAT_ANSWER_F"),
				'AI_ASSISTANT' => $this->getAiAssistantStatusMessages(),
			],
			'IS_CLOUD' => $isCloud,
			'HAS_ACTIVE_CLOUD_STORAGE_BUCKET' => $hasActiveBucket,
			'IS_BETA_AVAILABLE' => Settings::isBetaAvailable(),
			'IS_MESSENGER_V2_ENABLED' => Settings::isMessengerV2Enabled(),
			'IS_CHAT_LOCAL_STORAGE_AVAILABLE' => Settings::isChatLocalStorageAvailable(),
			'SHOULD_SHOW_CHAT_V2_UPDATE_HINT' => Settings::shouldShowChatV2UpdateHint(),
			'SMILE_LAST_UPDATE_DATE' => CSmile::getLastUpdate()->format(DateTimeInterface::ATOM),
			'CAN_USE_TELEPHONY' => Loader::includeModule('voximplant') && \Bitrix\Voximplant\Security\Helper::canCurrentUserPerformCalls(),
			'FIRST_TAB_ID' => $firstTabId,
			'HUMAN_RESOURCES_STRUCTURE_AVAILABLE' => Structure::isSyncAvailable() ? 'Y' : 'N',
			'ENABLE_DEV_WORKSPACE' => $enableDevWorkspace ? 'Y' : 'N',
			'PLAN_LIMITS' => Settings::planLimits(),
			'IM_FEATURES' => Settings::getImFeatures(),
			'USER_INFO' => [
				'id' => User::getCurrent()?->getId() ?? 0,
				'type' => User::getCurrent()?->getType()?->value ?? 'user',
			],
			'PERMISSIONS' => $permissions,
			'MULTIPLE_ACTION_MESSAGE_LIMIT' => Settings::getMultipleActionMessageLimit(),
			'CALL_SERVER_MAX_USERS' => $this->getCallServerMaxUsers(),
			'SERVICE_HEALTH_URL' => $this->getServiceHealthUrl(),
			'AI_SETTINGS' => [
				'MAX_TRANSCRIBABLE_FILE_SIZE' => $this->getMaxTranscribableFileSize(),
			],
			'CAN_USE_AUDIO_PANEL' => $this->canUseAudioPanel(),
		];
	}

	/**
	 * @deprecated
	 */
	private function getInvitationParams(): array
	{
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
			'INTRANET_INVITATION_CAN_INVITE' => $canInvite,
			'INTRANET_INVITATION_ROOT_STRUCTURE_SECTION_ID' => $rootStructureSectionId,
			'INTRANET_INVITATION_REGISTER_URL' => $registerUrl,
			'INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM' => $registerAdminConfirm,
			'INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM_DISABLE' => $disableRegisterAdminConfirm,
			'INTRANET_INVITATION_REGISTER_SHARING_MESSAGE' => $registerSharingMessage,
			'INTRANET_INVITATION_IS_ADMIN' => $isIntranetInvitationAdmin,
		];
	}

	private function isDevelopmentEnvironment(): bool
	{
		return Option::get('immobile', 'IS_DEVELOPMENT_ENVIRONMENT', 'N') === 'Y';
	}

	private function isNetworkSearchAvailable(): bool
	{
		return Loader::includeModule('imbot') && class_exists('\Bitrix\ImBot\Integration\Ui\EntitySelector\NetworkProvider');
	}

	private function isLinesOperator(): bool
	{
		if (!ModuleManager::isModuleInstalled('imopenlines'))
		{
			return false;
		}

		return Imopenlines\User::isOperator();
	}

	private function getCallServerMaxUsers(): int
	{
		if (Loader::includeModule('bitrix24'))
		{
			return (int)\Bitrix\Bitrix24\Feature::getVariable('im_max_call_participants');
		}
		return (int)Option::get('im', 'call_server_max_users');
	}

	private function getServiceHealthUrl(): string
	{
		$license = Application::getInstance()->getLicense();

		$baseUrl = $license->isCis()
			? 'https://status.bitrix24.ru/json_status.php?reg='
			: 'https://status.bitrix24.com/json_status.php?reg='
		;

		return $baseUrl . $license->getRegion();
	}

	private function getMaxTranscribableFileSize(): int
	{
		if (!Loader::includeModule('im'))
		{
			return 26214400;
		}

		return TranscribeManager::MAX_TRANSCRIBABLE_FILE_SIZE;
	}

	private function canUseAudioPanel(): bool
	{
		return Option::get('im', 'can_use_audio_panel', 'N') !== 'N';
	}

	private function getAiAssistantStatusMessages(): array
	{
		if (!Loader::includeModule('im'))
		{
			return [];
		}

		return StatusMessageProvider::get(Platform::MOBILE);
	}
}
