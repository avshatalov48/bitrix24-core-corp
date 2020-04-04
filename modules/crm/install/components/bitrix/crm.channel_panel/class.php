<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Activity\Provider\ExternalChannel;
use Bitrix\Crm\WebForm\Manager as WebFormManager;
use Bitrix\Crm\Integration\Channel\EmailTracker;
use Bitrix\Crm\Integration\Channel\VoxImplantTracker;
use Bitrix\Crm\Integration\Channel\IMOpenLineTracker;

Loc::loadMessages(__FILE__);

class CCrmChannelPanelComponent extends CBitrixComponent
{
	//region Fields
	/** @var int  */
	protected $userID = 0;
	/** @var \CCrmPerms|null  */
	protected $userPermissions = null;
	/** @var string  */
	protected $guid = '';
	/** @var array|null  */
	protected $settings = null;
	/** @var bool */
	protected $autorewind = true;
	/** @var array|null  */
	protected $items = null;
	//endregion
	//region Methods
	public static function getSettings($guid)
	{
		$settings = CUserOptions::GetOption('crm.channel_panel', strtolower($guid), null);
		if(!is_array($settings))
		{
			$settings = array('enabled' => 'Y');
		}
		return $settings;
	}
	public static function saveSettings($guid, array $settings)
	{
		CUserOptions::SetOption('crm.channel_panel', strtolower($guid), $settings);
	}
	public static function markAsEnabled($guid, $enabled)
	{
		$settings = self::getSettings($guid);
		$settings['enabled'] = $enabled ? 'Y' : 'N';
		self::saveSettings($guid, $settings);
	}
	public static function checkIfEnabled($guid)
	{
		$settings = self::getSettings($guid);
		return !isset($settings['enabled']) || strtoupper($settings['enabled']) === 'Y';
	}
	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->items = array();
		$this->settings = array();
	}
	public function executeComponent()
	{
		$this->initialize();
		if(!$this->isEnabled())
		{
			return;
		}

		if(!\Bitrix\Main\Loader::includeModule('crm'))
		{
			ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
			return;
		}

		$this->prepareItems();
		$this->includeComponentTemplate();
	}
	protected function initialize()
	{
		$this->userID = CCrmSecurityHelper::GetCurrentUserID();
		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();

		if(isset($this->arParams['GUID']))
		{
			$this->guid = $this->arParams['GUID'];
		}

		if($this->guid === '')
		{
			$this->guid = 'channel_panel';
		}

		$this->settings = self::getSettings($this->guid);

		if(isset($this->arParams['AUTO_REWIND']))
		{
			$this->autorewind = ($this->arParams['AUTO_REWIND'] == 'Y' || $this->arParams['AUTO_REWIND'] == true);
		}
	}
	protected function prepareItems()
	{
		//region EmailTracker
		$emailManager = EmailTracker::getInstance();
		if($emailManager->isEnabled()
			&& $emailManager->checkConfigurationPermission()
			&& !$emailManager->isInUse()
		)
		{
			$this->items[] = array(
				'CAPTION' => Loc::getMessage('CRM_CHANNEL_EMAIL_CAPTION'),
				'CAPTION_CLASS_NAME' => 'crm-e-mail',
				'LEGEND' => Loc::getMessage('CRM_CHANNEL_EMAIL_LEGEND'),
				'URL' => $emailManager->getUrl()
			);
		}
		//endregion

		//region IMOpenLineTracker
		$openLineManager = IMOpenLineTracker::getInstance();
		if($openLineManager->isEnabled())
		{
			if($openLineManager->checkConfigurationPermission()
				&& !$openLineManager->isInUse()
			)
			{
				$this->items[] = array(
					'CAPTION' => Loc::getMessage('CRM_CHANNEL_OPEN_LINE_CAPTION'),
					'CAPTION_CLASS_NAME' => 'crm-openlines',
					'LEGEND' => Loc::getMessage('CRM_CHANNEL_OPEN_LINE_LEGEND'),
					'URL' => $openLineManager->getUrl(null)
				);
			}

			if($openLineManager->checkConnectorConfigurationPermission(IMOpenLineTracker::CHAT_CONNECTOR)
				&& !$openLineManager->isConnectorInUse(IMOpenLineTracker::CHAT_CONNECTOR)
			)
			{
				$this->items[] = array(
					'CAPTION' => Loc::getMessage('CRM_CHANNEL_CHAT_CAPTION'),
					'CAPTION_CLASS_NAME' => 'crm-messages',
					'LEGEND' => Loc::getMessage('CRM_CHANNEL_CHAT_LEGEND'),
					'URL' => $openLineManager->getConnectorUrl(IMOpenLineTracker::CHAT_CONNECTOR)
				);
			}
		}
		//endregion

		//region Deferred
		/*

		$this->items[] = array(
			'CAPTION' => Loc::getMessage('CRM_CHANNEL_CALLBACK_CAPTION'),
			'CAPTION_CLASS_NAME' => 'crm-callback',
			'LEGEND' => Loc::getMessage('CRM_CHANNEL_CALLBACK_LEGEND'),
			'URL' => ''
		);
		*/
		//endregion

		if(WebFormManager::checkReadPermission($this->userPermissions)
			&& !WebFormManager::isInUse()
		)
		{
			$this->items[] = array(
				'CAPTION' => Loc::getMessage('CRM_CHANNEL_WEB_FORM_CAPTION'),
				'CAPTION_CLASS_NAME' => 'crm-webform',
				'LEGEND' => Loc::getMessage('CRM_CHANNEL_WEB_FORM_LEGEND'),
				'URL' => WebFormManager::getUrl()
			);
		}

		//region VoxImplantTracker
		$voxImplantManager = VoxImplantTracker::getInstance();
		if($voxImplantManager->isEnabled()
			&& $voxImplantManager->checkConfigurationPermission()
			&& !$voxImplantManager->isInUse()
		)
		{
			$this->items[] = array(
				'CAPTION' => Loc::getMessage('CRM_CHANNEL_TELEPHONY_CAPTION'),
				'CAPTION_CLASS_NAME' => 'crm-phone',
				'LEGEND' => Loc::getMessage('CRM_CHANNEL_TELEPHONY_LEGEND'),
				'URL' => $voxImplantManager->getUrl(null)
			);
		}
		//endregion

		if(CCrmPerms::IsAdmin($this->userID) && !ExternalChannel::isActive())
		{
			$url = ExternalChannel::getRenderUrl();
			if($url !== '')
			{
				$this->items[] = array(
					'CAPTION' => Loc::getMessage('CRM_CHANNEL_1C_CAPTION'),
					'CAPTION_CLASS_NAME' => 'crm-1c',
					'LEGEND' => Loc::getMessage('CRM_CHANNEL_1C_LEGEND'),
					'URL' => $url
				);
			}
		}
	}
	public function getGuid()
	{
		return $this->guid;
	}
	public function isEnabled()
	{
		return !isset($this->settings['enabled']) || strtoupper($this->settings['enabled']) === 'Y';
	}
	public function isAutoRewindEnabled()
	{
		return $this->autorewind;
	}
	public function getConnectButtonText()
	{
		return Loc::getMessage('CRM_CHANNEL_PANEL_CONNECT_BUTTON');
	}
	public function getCloseConfirmationText()
	{
		return Loc::getMessage('CRM_CHANNEL_PANEL_CLOSE_CONFIRMATION');
	}
	public function hasItems()
	{
		return !empty($this->items);
	}
	public function getItems()
	{
		return $this->items;
	}
	//endregion
}