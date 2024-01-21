<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImOpenlines\Model;
use Bitrix\ImOpenlines\Common;

Loc::loadMessages(__FILE__);

class CImOpenlinesPermsComponent extends CBitrixComponent
{
	protected $errors;
	protected $saveMode = false;

	public function __construct($component)
	{
		parent::__construct($component);

		$this->errors = new ErrorCollection();
		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		if($request['act'] === 'save' && check_bitrix_sessid())
			$this->saveMode = true;
	}

	protected function checkModules()
	{
		$result = true;
		if(!Loader::includeModule('imopenlines'))
		{
			ShowError(Loc::getMessage('IMOL_PERM_MODULE_ERROR'));
			$result = false;
		}

		return $result;
	}

	public function prepareData()
	{
		$roles = array();
		$cursor = Model\RoleTable::getList();
		while($row = $cursor->fetch())
		{
			$roles[$row['ID']] = array(
				'ID' => $row['ID'],
				'NAME' => $row['NAME'],
				'EDIT_URL' => Common::getContactCenterPublicFolder() . 'permissions/editrole.php?ID='.$row['ID'],
			);
		}

		$roleAccessCodes = array();
		$accessCodesToResolve = array();
		$cursor = Model\RoleAccessTable::getList(array(
			'select' => array('ID', 'ROLE_ID', 'ROLE_NAME' => 'ROLE.NAME', 'ACCESS_CODE'),
		));
		while($row = $cursor->fetch())
		{
			$roleAccessCodes[$row['ID']] = array(
				'ID' => $row['ID'],
				'ROLE_ID' => $row['ROLE_ID'],
				'ROLE_NAME' => $row['ROLE_NAME'],
				'ACCESS_CODE' => $row['ACCESS_CODE']
			);
			$accessCodesToResolve[] = $row['ACCESS_CODE'];
		}

		$accessManager = new CAccess();
		$resolvedAccessCodes = $accessManager->GetNames($accessCodesToResolve);

		foreach($roleAccessCodes as $id => $roleAccessCode)
		{
			if(isset($resolvedAccessCodes[$roleAccessCode['ACCESS_CODE']]))
			{
				$codeDescription = $resolvedAccessCodes[$roleAccessCode['ACCESS_CODE']];
				$roleAccessCodes[$id]['ACCESS_PROVIDER'] = $codeDescription['provider'];
				$roleAccessCodes[$id]['ACCESS_NAME'] = $codeDescription['name'];
			}
			else
			{
				$roleAccessCodes[$id]['ACCESS_NAME'] = Loc::getMessage('IMOL_PERM_UNKNOWN_ACCESS_CODE');
			}
		}

		$this->arResult['ROLES'] = $roles;
		$this->arResult['ROLE_ACCESS_CODES'] = $roleAccessCodes;
		$this->arResult['ADD_URL'] = Common::getContactCenterPublicFolder() . 'permissions/editrole.php?ID=0';
		$this->arResult['INDEX_URL'] = Common::getContactCenterPublicFolder();
		$this->arResult['CAN_EDIT'] = \Bitrix\ImOpenlines\Security\Helper::canUse();

		$this->arResult['IFRAME'] = $this->request['IFRAME'] === 'Y';

		$uri = new \Bitrix\Main\Web\Uri(htmlspecialchars_decode(POST_FORM_ACTION_URI));
		$uri->addParams(['action-line' => 'permission-add']);
		$this->arResult['ACTION_URI'] = htmlspecialcharsbx($uri->getUri());
	}

	public function save()
	{
		$request = Application::getInstance()->getContext()->getRequest();
		$roleAccessCodes = $request['PERMS'];
		\Bitrix\ImOpenlines\Security\Helper::clearMenuCache();

		Model\RoleAccessTable::truncate();

		if(!is_array($roleAccessCodes))
		{
			return true;
		}

		foreach ($roleAccessCodes as $roleAccessCode => $roleId)
		{
			$insertResult = Model\RoleAccessTable::add([
				'ROLE_ID' => $roleId,
				'ACCESS_CODE' => $roleAccessCode
			]);
			if(!$insertResult->isSuccess())
			{
				$this->errors[] = new Error(Loc::getMessage('IMOL_PERM_UNKNOWN_SAVE_ERROR'));
				return false;
			}
		}

		$iblocksCount = \CIBlock::getList(
			[],
			[
				'ACTIVE' => 'Y',
				'TYPE' => \Bitrix\ImOpenlines\QuickAnswers\ListsDataManager::TYPE,
				'CODE' => \Bitrix\ImOpenlines\QuickAnswers\ListsDataManager::IBLOCK_CODE,
				'CHECK_PERMISSIONS' => 'N'
			]
		)->SelectedRowsCount();

		if ($iblocksCount > 0 && $iblocksCount <= 20)
		{
			\Bitrix\Imopenlines\Update\Agent::updateRightsQuickAnswersAgent();
		}
		elseif ($iblocksCount > 20)
		{
			\CAgent::AddAgent(
				'Bitrix\Imopenlines\Update\Agent::updateRightsQuickAnswersAgent();',
				'imopenlines',
				'N',
				0,
				'',
				'Y',
				\ConvertTimeStamp(time() + \CTimeZone::GetOffset(), 'FULL')
			);
		}

		return true;
	}

	public function executeComponent()
	{
		if ($this->checkModules())
		{
			$permissions = \Bitrix\ImOpenlines\Security\Permissions::createWithCurrentUser();
			if(!$permissions->canPerform(\Bitrix\ImOpenlines\Security\Permissions::ENTITY_SETTINGS, \Bitrix\ImOpenlines\Security\Permissions::ACTION_MODIFY))
			{
				ShowError(Loc::getMessage('IMOL_PERM_ACCESS_DENIED'));
				return false;
			}

			if($this->saveMode)
			{
				if(\Bitrix\ImOpenlines\Security\Helper::canUse())
				{
					$this->save();
				}
				else
				{
					ShowError(Loc::getMessage('IMOL_PERM_LICENSING_ERROR'));
					return false;
				}
			}

			$this->prepareData();
			$this->includeComponentTemplate();
		}

		return $this->arResult;
	}
}