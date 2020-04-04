<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Crm\SiteButton\Manager;
use Bitrix\Crm\SiteButton\Preset;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\Date;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

Loc::loadMessages(__FILE__);


class CCrmSiteButtonListComponent extends \CBitrixComponent
{
	protected $errors = array();

	public function prepareResult()
	{
		/**@var $USER \CUser*/
		global $USER;

		$this->arResult['ITEMS'] = array();
		Manager::disableWidgetSelect();
		$this->arResult['TYPE_LIST'] = Manager::getTypeList();
		$buttonDataList = Manager::getList();
		foreach ($buttonDataList as $buttonData)
		{
			$dateCreate = $buttonData['DATE_CREATE'];
			/** @var DateTime $dateCreate */
			$buttonData['DATE_CREATE_DISPLAY'] = $dateCreate ? $dateCreate->format(Date::getFormat()) : '';

			$activeChangeDate = $buttonData['ACTIVE_CHANGE_DATE'];
			/** @var DateTime $activeChangeDate */
			if($activeChangeDate)
			{
				$buttonData['DATE_CREATE_DISPLAY_TIME'] = $activeChangeDate->toUserTime()->format(IsAmPmMode() ? 'g:i a': 'H:i');
				$buttonData['DATE_CREATE_DISPLAY_DATE'] = $activeChangeDate->format(Date::getFormat());
				$buttonData['ACTIVE_CHANGE_DATE_DISPLAY'] = $buttonData['DATE_CREATE_DISPLAY_TIME'] . ', '. $buttonData['DATE_CREATE_DISPLAY_DATE'];
			}
			else
			{
				$buttonData['DATE_CREATE_DISPLAY_TIME'] = '';
				$buttonData['DATE_CREATE_DISPLAY_DATE'] = '';
				$buttonData['DATE_CREATE_DISPLAY'] = '';
			}

			$buttonData['ACTIVE_CHANGE_BY_DISPLAY'] = $this->getUserInfo($buttonData['ACTIVE_CHANGE_BY']);
			$buttonData['ACTIVE_CHANGE_BY_NOW_DISPLAY'] = $this->getUserInfo($USER->GetID());

			$replaceList = array('id' => $buttonData['ID'], 'button_id' => $buttonData['ID']);
			$buttonData['PATH_TO_BUTTON_LIST'] = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_BUTTON_LIST'], $replaceList);
			$buttonData['PATH_TO_BUTTON_EDIT'] = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_BUTTON_EDIT'], $replaceList);

			$this->arResult['ITEMS'][] = $buttonData;
		}

		$replaceListNew = array('id' => 0, 'button_id' => 0);
		$this->arResult['PATH_TO_BUTTON_NEW'] = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_BUTTON_EDIT'], $replaceListNew);
		$this->arResult['SHOW_PLUGINS'] = false;
	}

	public function checkParams()
	{
		$this->arParams['IFRAME'] = isset($this->arParams['IFRAME']) ? (bool) $this->arParams['IFRAME'] : false;
		$this->arParams['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams["NAME_TEMPLATE"]);

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
			$link = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER_PROFILE'], $replaceList);

			$userFields = \Bitrix\Main\UserTable::getRowById($userId);
			if(!$userFields)
			{
				return null;
			}

			// format name
			$userName = CUser::FormatName(
				$this->arParams['NAME_TEMPLATE'],
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
			return;
		}

		if (!$this->checkParams())
		{
			$this->showErrors();
			return;
		}

		/**@var $USER \CUser*/
		global $USER;
		$CrmPerms = new CCrmPerms($USER->GetID());
		if($CrmPerms->HavePerm('BUTTON', BX_CRM_PERM_NONE))
		{
			ShowError(Loc::getMessage('CRM_PERMISSION_DENIED'));
			return;
		}
		$this->arResult['PERM_CAN_EDIT'] = !$CrmPerms->HavePerm('BUTTON', BX_CRM_PERM_NONE, 'WRITE');

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('CRM_BUTTON_LIST_TITLE'));

		$this->checkInstalledPresets();
		$this->prepareResult();
		$this->includeComponentTemplate();
	}

	protected function checkInstalledPresets()
	{
		if (Preset::checkVersion())
		{
			$preset = new Preset();
			$preset->install();
		}
	}

	protected function checkModules()
	{
		if(!Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
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