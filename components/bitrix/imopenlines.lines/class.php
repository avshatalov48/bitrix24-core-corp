<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\Date;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

Loc::loadMessages(__FILE__);

class CImOpenLinesListComponent extends \CBitrixComponent
{
	protected $errors = array();
	/** @var \Bitrix\ImOpenlines\Security\Permissions */
	protected $userPermissions;

	private function showList()
	{
		global $USER;

		$configManager = new \Bitrix\ImOpenLines\Config();
		$result = $configManager->getList(Array(
			'select' => Array(
				'*',
				'STATS_SESSION' => 'STATISTIC.SESSION',
				'STATS_MESSAGE' => 'STATISTIC.MESSAGE',
				'STATS_CLOSED' => 'STATISTIC.CLOSED',
				'STATS_IN_WORK' => 'STATISTIC.IN_WORK',
				'STATS_LEAD' => 'STATISTIC.LEAD',
			),
			'filter' => Array('=TEMPORARY' => 'N')
		),
		Array(
			'QUEUE' => 'Y',
			'CHECK_PERMISSION' => \Bitrix\ImOpenlines\Security\Permissions::ACTION_VIEW,
		));

		foreach ($result as $id => $config)
		{
			$dateCreate = $config['DATE_CREATE'];
			$config['DATE_CREATE_DISPLAY'] = $dateCreate ? $dateCreate->format(Date::getFormat()) : '';

			$activeChangeDate = $config['DATE_MODIFY'];
			/** @var DateTime $activeChangeDate */
			if($activeChangeDate)
			{
				$config['CHANGE_DATE_DISPLAY'] = $activeChangeDate->toUserTime()->format(IsAmPmMode() ? 'g:i a': 'H:i');
				$config['CHANGE_DATE_DISPLAY'] .= ', '. $activeChangeDate->format(Date::getFormat());
			}
			else
			{
				$config['DATE_CREATE_DISPLAY'] = '';
			}

			$config['CHANGE_BY_DISPLAY'] = $this->getUserInfo($config['MODIFY_USER_ID']);
			$config['CHANGE_BY_NOW_DISPLAY'] = $this->getUserInfo($USER->GetID());
			$config['ACTIVE_CONNECTORS'] = \Bitrix\ImConnector\Connector::getListConnectedConnectorReal($config['ID']);

			$config['CAN_EDIT_CONNECTOR'] = $configManager->canEditConnector($config['ID']);
			$config['CAN_EDIT'] = $configManager->canEditLine($config['ID']);

			$result[$id] = $config;
		}

		$this->arResult['PERM_CAN_EDIT'] = true;
		$this->arResult['LINES'] = $result;
		$this->arResult['PUBLIC_PATH'] = \Bitrix\ImOpenLines\Common::getContactCenterPublicFolder();
		$this->arResult['PATH_TO_EDIT'] = \Bitrix\ImOpenLines\Common::getContactCenterPublicFolder() . 'lines_edit/?ID=#ID#';
		$this->arResult['PATH_TO_LIST'] = \Bitrix\ImOpenLines\Common::getPublicFolder() . 'list/';
		$this->arResult['PATH_TO_STATISTICS'] = \Bitrix\ImOpenLines\Common::getContactCenterPublicFolder() . 'dialog_list/?CONFIG_ID=#ID#';
		$this->arResult['PATH_TO_CONNECTOR'] = \Bitrix\ImOpenLines\Common::getContactCenterPublicFolder() . 'connector/?ID=#ID#&LINE=#LINE#&LINE_SETTING=Y&IFRAME=Y';

		$this->includeComponentTemplate();

		return true;
	}

	public function getUserInfo($userId)
	{
		static $users = array();

		if(!$userId)
		{
			return null;
		}

		if(!$users[$userId])
		{
			// prepare link to profile
			$replaceList = array('user_id' => $userId);

			if (!isset($this->arParams['PATH_TO_USER_PROFILE']))
			{
				$extranetSiteID = 'ex';
				if (\Bitrix\Main\Loader::includeModule("extranet"))
				{
					$extranetSiteID = \CExtranet::GetExtranetSiteID();
				}

				$this->arParams['PATH_TO_USER_PROFILE'] = \COption::GetOptionString(
					"socialnetwork",
					"user_page",
					SITE_DIR.'company/personal/',
					(\Bitrix\Main\Loader::includeModule('extranet') && !\CExtranet::IsIntranetUser() ? $extranetSiteID : SITE_ID)
				)."user/#user_id#/";
			}
			$link = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER_PROFILE'], $replaceList);

			$userFields = \Bitrix\Main\UserTable::getRowById($userId);
			if(!$userFields)
			{
				return null;
			}

			// format name
			$userName = CUser::FormatName(
				CSite::GetNameFormat(false),
				array(
					'LOGIN' => $userFields['LOGIN'],
					'NAME' => $userFields['NAME'],
					'LAST_NAME' => $userFields['LAST_NAME'],
					'SECOND_NAME' => $userFields['SECOND_NAME']
				),
				true, false
			);

			// prepare icon
			$fileTmp = CFile::ResizeImageGet(
				$userFields['PERSONAL_PHOTO'],
				array('width' => 42, 'height' => 42),
				BX_RESIZE_IMAGE_EXACT,
				false
			);
			//$userIcon = CFile::ShowImage($fileTmp['src'], 50, 50, 'border=0');
			$userIcon = $fileTmp['src'];

			$users[$userId] = array(
				'ID' => $userId,
				'NAME' => $userName,
				'LINK' => $link,
				'ICON' => $userIcon
			);
		}

		return $users[$userId];
	}

	public function executeComponent()
	{
		if (!$this->checkModules())
		{
			$this->showErrors();
			return false;
		}

		$this->userPermissions = \Bitrix\ImOpenlines\Security\Permissions::createWithCurrentUser();

		if (!$this->checkAccess())
		{
			$this->showErrors();
			return false;
		}

		$this->showList();

		return true;
	}

	protected function checkModules()
	{
		if(!Loader::includeModule('imopenlines'))
		{
			$this->errors[] = Loc::getMessage('OL_COMPONENT_MODULE_NOT_INSTALLED');
			return false;
		}
		if(!Loader::includeModule('imconnector'))
		{
			$this->errors[] = Loc::getMessage('OL_COMPONENT_MODULE_NOT_INSTALLED');
			return false;
		}

		return true;
	}

	protected function checkAccess()
	{
		if(!$this->userPermissions->canPerform(\Bitrix\ImOpenlines\Security\Permissions::ENTITY_LINES, \Bitrix\ImOpenlines\Security\Permissions::ACTION_VIEW))
		{
			$this->errors[] = Loc::getMessage('OL_COMPONENT_PERMISSION_DENIED');
			return false;
		}

		return true;
	}

	protected function hasErrors()
	{
		return (count($this->errors) > 0);
	}

	protected function showErrors()
	{
		if(count($this->errors) <= 0)
		{
			return;
		}

		foreach($this->errors as $error)
		{
			ShowError($error);
		}
	}
}