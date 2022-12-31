<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Crm\Order;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class CrmBuyerEdit extends \CBitrixComponent
	implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	protected $action = null;
	protected $errorCollection = null;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new \Bitrix\Main\ErrorCollection();
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function configureActions()
	{
		return [];
	}

	protected function listKeysSignedParameters()
	{
		return ['IFRAME', 'USER_ID', 'PATH_TO_BUYER_EDIT'];
	}

	protected function showErrors()
	{
		foreach ($this->getErrors() as $error)
		{
			ShowError($error);
		}
	}

	public function onPrepareComponentParams($params)
	{
		$params['USER_ID'] = isset($params['USER_ID']) ? (int)$params['USER_ID'] : 0;
		$params['IFRAME'] = isset($params['IFRAME']) && $params['IFRAME'];

		return $params;
	}

	protected function getNewBuyer()
	{
		return [
			'ID' => '',
			'EMAIL' => '',
			'ACTIVE' => 'Y',
			'LAST_NAME' => '',
			'NAME' => '',
			'SECOND_NAME' => '',
			'PASSWORD' => '',
			'CONFIRM_PASSWORD' => '',
		];
	}

	protected function getExistingBuyer()
	{
		$existedData = \Bitrix\Main\UserTable::getRow([
			'select' => [
				'ID', 'EMAIL', 'ACTIVE', 'LAST_NAME', 'NAME', 'SECOND_NAME',
			],
			'filter' => [
				'=UF_DEPARTMENT' => false,
				'=EXTERNAL_AUTH_ID' => Order\Buyer::AUTH_ID,
				'=ID' => $this->arParams['USER_ID'],
				'!=ID' => Order\Manager::getAnonymousUserID(),
			],
		]);

		$buyer = $this->getNewBuyer();

		if (!empty($existedData))
		{
			$buyer = array_merge($buyer, $existedData);
		}
		else
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_ORDER_BUYER_EDIT_LOAD_USER_ERROR'));
		}

		return $buyer;
	}

	protected function getFields()
	{
		return [
			[
				'ID' => 'ACTIVE',
				'NAME' => Loc::getMessage('CRM_ORDER_BUYER_EDIT_COLUMN_ACTIVE'),
				'TYPE' => 'checkbox',
				'EDITABLE' => true,
				'REQUIRED' => false,
			],
			[
				'ID' => 'EMAIL',
				'NAME' => Loc::getMessage('CRM_ORDER_BUYER_EDIT_COLUMN_EMAIL'),
				'TYPE' => 'text',
				'EDITABLE' => true,
				'REQUIRED' => true,
			],
			[
				'ID' => 'PASSWORD',
				'NAME' => $this->isNewUser()
					? Loc::getMessage('CRM_ORDER_BUYER_EDIT_COLUMN_PASSWORD_NEW')
					: Loc::getMessage('CRM_ORDER_BUYER_EDIT_COLUMN_PASSWORD'),
				'TYPE' => 'password',
				'EDITABLE' => true,
				'REQUIRED' => $this->isNewUser(),
			],
			[
				'ID' => 'CONFIRM_PASSWORD',
				'NAME' => $this->isNewUser()
					? Loc::getMessage('CRM_ORDER_BUYER_EDIT_COLUMN_CONFIRM_PASSWORD_NEW')
					: Loc::getMessage('CRM_ORDER_BUYER_EDIT_COLUMN_CONFIRM_PASSWORD'),
				'TYPE' => 'password',
				'EDITABLE' => true,
				'REQUIRED' => $this->isNewUser(),
			],
			[
				'ID' => 'LAST_NAME',
				'NAME' => Loc::getMessage('CRM_ORDER_BUYER_EDIT_COLUMN_LAST_NAME'),
				'TYPE' => 'text',
				'EDITABLE' => true,
				'REQUIRED' => false,
			],
			[
				'ID' => 'NAME',
				'NAME' => Loc::getMessage('CRM_ORDER_BUYER_EDIT_COLUMN_NAME'),
				'TYPE' => 'text',
				'EDITABLE' => true,
				'REQUIRED' => false,
			],
			[
				'ID' => 'SECOND_NAME',
				'NAME' => Loc::getMessage('CRM_ORDER_BUYER_EDIT_COLUMN_SECOND_NAME'),
				'TYPE' => 'text',
				'EDITABLE' => true,
				'REQUIRED' => false,
			],
		];
	}

	protected function getGroups()
	{
		$groupsIterator = \Bitrix\Main\GroupTable::getList([
			'select' => ['ID', 'NAME'],
			'filter' => ['=IS_SYSTEM' => 'N'],
		]);

		return $groupsIterator->fetchAll();
	}

	protected function getUserGroups()
	{
		if ($this->arParams['USER_ID'] > 0)
		{
			return CUser::GetUserGroup($this->arParams['USER_ID']);
		}

		return [];
	}

	protected function isNewUser()
	{
		return $this->arParams['USER_ID'] === 0;
	}

	protected function initialLoadAction()
	{
		global $APPLICATION;

		if ($this->isNewUser())
		{
			$title = Loc::getMessage('CRM_ORDER_BUYER_EDIT_CREATE_TITLE');
			$this->arResult['BUYER'] = $this->getNewBuyer();
		}
		else
		{
			$title = Loc::getMessage('CRM_ORDER_BUYER_EDIT_EDIT_TITLE');
			$this->arResult['BUYER'] = $this->getExistingBuyer();
		}

		$APPLICATION->SetTitle($title);

		if ($this->errorCollection->isEmpty())
		{
			$this->arResult['FIELDS'] = $this->getFields();
			$this->arResult['GROUPS'] = $this->getGroups();
			$this->arResult['USER_GROUPS'] = $this->getUserGroups();
		}

		$this->arResult['ERRORS'] = $this->errorCollection->toArray();

		$this->includeComponentTemplate();
	}

	protected function getDetailUrl($userId)
	{
		return str_replace('#user_id#', $userId, $this->arParams['PATH_TO_BUYER_EDIT']);
	}

	protected function validateBuyerData($buyer)
	{
		$allowedKeys = array_merge($this->getNewBuyer(), ['GROUP_ID' => '']);
		$forbiddenKeys = array_diff_key($buyer, $allowedKeys);

		return empty($forbiddenKeys);
	}

	protected function getGroupsToSave($buyer)
	{
		$existingGroups = $this->getUserGroups();

		if (empty($buyer['GROUP_ID']))
		{
			$buyer['GROUP_ID'] = [];
		}

		$buyerGroups = Order\BuyerGroup::getPublicList();
		$buyerGroupsIds = array_column($buyerGroups, 'ID');

		// filter unacceptable system groups
		$newBuyerGroups = array_intersect($buyer['GROUP_ID'], $buyerGroupsIds);

		// default buyer groups
		$newBuyerGroups = array_merge($newBuyerGroups, Order\BuyerGroup::getDefaultGroups());
		$newBuyerGroups = array_unique($newBuyerGroups);

		// keep existing system groups and add new buyer groups
		$groups = array_merge(array_diff($existingGroups, $buyerGroupsIds), $newBuyerGroups);

		return $groups;
	}

	protected function saveBuyer($buyer)
	{
		if (!$this->validateBuyerData($buyer))
		{
			return false;
		}

		$groups = $this->getGroupsToSave($buyer);

		$user = new CUser;

		if ($this->isNewUser())
		{
			$dataToCreate = [
				'LOGIN' => $buyer['EMAIL'],
				'NAME' => $buyer['NAME'],
				'LAST_NAME' => $buyer['LAST_NAME'],
				'SECOND_NAME' => $buyer['SECOND_NAME'],
				'PASSWORD' => $buyer['PASSWORD'],
				'CONFIRM_PASSWORD' => $buyer['CONFIRM_PASSWORD'],
				'EMAIL' => $buyer['EMAIL'],
				'GROUP_ID' => $groups,
				'ACTIVE' => $buyer['ACTIVE'],
				'LID' => $this->getSiteId(),
				// reset department for intranet
				'UF_DEPARTMENT' => [],
				'EXTERNAL_AUTH_ID' => Order\Buyer::AUTH_ID,
			];
			$result = $user->Add($dataToCreate);
		}
		else
		{
			$dataToUpdate = [
				'LOGIN' => $buyer['EMAIL'],
				'NAME' => $buyer['NAME'],
				'LAST_NAME' => $buyer['LAST_NAME'],
				'SECOND_NAME' => $buyer['SECOND_NAME'],
				'EMAIL' => $buyer['EMAIL'],
				'GROUP_ID' => $groups,
				'ACTIVE' => $buyer['ACTIVE'],
				'EXTERNAL_AUTH_ID' => Order\Buyer::AUTH_ID,
			];

			if (!empty($buyer['PASSWORD']))
			{
				$dataToUpdate['PASSWORD'] = $buyer['PASSWORD'];
				$dataToUpdate['CONFIRM_PASSWORD'] = $buyer['CONFIRM_PASSWORD'];
			}

			$result = $user->Update($buyer['ID'], $dataToUpdate);
			$result = $result ? $buyer['ID'] : $result;
		}

		if (!$result)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error($user->LAST_ERROR);
		}

		return $result;
	}

	public function saveFormAjaxAction()
	{
		$response = [];

		if (!$this->checkModules() || !$this->checkPermissions())
		{
			return $response;
		}

		$data = $this->request->get('data') ?: [];
		$userId = $this->saveBuyer($data);

		if (!empty($userId))
		{
			$response['redirectUrl'] = $this->getDetailUrl($userId);
		}
		elseif ($this->errorCollection->isEmpty())
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_ORDER_BUYER_EDIT_SAVE_ERROR'));
		}

		return $response;
	}

	protected function checkModules()
	{
		if (!Loader::includeModule('crm'))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));

			return false;
		}

		if (!CAllCrmInvoice::installExternalEntities())
		{
			return false;
		}

		if (!CCrmQuote::LocalComponentCausedUpdater())
		{
			return false;
		}

		if (!Loader::includeModule('sale'))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_MODULE_NOT_INSTALLED_SALE'));

			return false;
		}

		if (!Loader::includeModule('catalog'))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));

			return false;
		}

		return true;
	}

	protected function checkPermissions()
	{
		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_PERMISSION_DENIED'));

			return false;
		}

		$this->arResult['PERM_CAN_EDIT'] = true;

		return true;
	}

	public function executeComponent()
	{
		if (!$this->checkModules() || !$this->checkPermissions())
		{
			$this->showErrors();

			return;
		}

		$this->initialLoadAction();
	}
}