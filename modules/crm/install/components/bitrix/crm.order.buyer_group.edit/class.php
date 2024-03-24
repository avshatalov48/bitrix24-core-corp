<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class CrmBuyerGroupsEdit extends \CBitrixComponent
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
		return ['IFRAME', 'GROUP_ID', 'PATH_TO_BUYER_GROUP_EDIT'];
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
		$params['GROUP_ID'] = isset($params['GROUP_ID']) ? (int)$params['GROUP_ID'] : 0;
		$params['IFRAME'] = isset($params['IFRAME']) && $params['IFRAME'];

		return $params;
	}

	protected function getNewGroup()
	{
		return [
			'ID' => '',
			'ACTIVE' => 'Y',
			'C_SORT' => '100',
			'NAME' => '',
			'DESCRIPTION' => '',
		];
	}

	protected function getExistingGroup()
	{
		return \Bitrix\Main\GroupTable::getRow([
			'select' => [
				'ID', 'ACTIVE', 'C_SORT', 'NAME', 'DESCRIPTION', 'IS_SYSTEM', 'STRING_ID'
			],
			'filter' => [
				'=ID' => $this->arParams['GROUP_ID'],
				[
					'LOGIC' => 'OR',
					'=IS_SYSTEM' => 'N',
					'=STRING_ID' => \Bitrix\Crm\Order\BuyerGroup::BUYER_GROUP_NAME
				]
			]
		]);
	}

	protected function canEditGroup($group)
	{
		return !isset($group['IS_SYSTEM']) || $group['IS_SYSTEM'] === 'N';
	}

	protected function getFields()
	{
		return [
			[
				'ID' => 'NAME',
				'NAME' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_EDIT_COLUMN_NAME'),
				'TYPE' => 'text',
				'EDITABLE' => $this->arResult['CAN_EDIT_GROUP'],
				'REQUIRED' => $this->arResult['CAN_EDIT_GROUP'],
			],
			[
				'ID' => 'ACTIVE',
				'NAME' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_EDIT_COLUMN_ACTIVE'),
				'TYPE' => 'checkbox',
				'EDITABLE' => $this->arResult['CAN_EDIT_GROUP'],
				'REQUIRED' => false,
			],
			[
				'ID' => 'DESCRIPTION',
				'NAME' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_EDIT_COLUMN_DESCRIPTION'),
				'TYPE' => 'text',
				'EDITABLE' => $this->arResult['CAN_EDIT_GROUP'],
				'REQUIRED' => false,
			],
			[
				'ID' => 'C_SORT',
				'NAME' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_EDIT_COLUMN_C_SORT'),
				'TYPE' => 'text',
				'EDITABLE' => $this->arResult['CAN_EDIT_GROUP'],
				'REQUIRED' => false,
			]
		];
	}

	protected function isNewGroup()
	{
		return $this->arParams['GROUP_ID'] === 0;
	}

	protected function initialLoadAction()
	{
		global $APPLICATION;

		if ($this->isNewGroup())
		{
			$title = Loc::getMessage('CRM_ORDER_BUYER_GROUP_EDIT_CREATE_TITLE');
			$this->arResult['GROUP'] = $this->getNewGroup();
		}
		else
		{
			$title = Loc::getMessage('CRM_ORDER_BUYER_GROUP_EDIT_TITLE');
			$this->arResult['GROUP'] = $this->getExistingGroup();
		}

		$APPLICATION->SetTitle($title);

		$this->arResult['CAN_EDIT_GROUP'] = $this->canEditGroup($this->arResult['GROUP']);
		$this->arResult['FIELDS'] = $this->getFields();

		$this->includeComponentTemplate();
	}

	protected function getDetailUrl($groupId)
	{
		return str_replace('#group_id#', $groupId, $this->arParams['PATH_TO_BUYER_GROUP_EDIT']);
	}

	protected function validateGroupData($group)
	{
		$forbiddenKeys = array_diff_key($group, $this->getNewGroup());

		return empty($forbiddenKeys);
	}

	protected function isSystemGroup($group)
	{
		if (isset($group['ID']) && (int)$group['ID'] > 0)
		{
			return \Bitrix\Main\GroupTable::getRow([
				'select' => ['ID'],
				'filter' => [
					'=ID' => $group['ID'],
					'=IS_SYSTEM' => 'Y'
				]
			]);
		}

		return false;
	}

	protected function saveBuyerGroup($group)
	{
		if (!$this->validateGroupData($group) || $this->isSystemGroup($group))
		{
			return false;
		}

		$id = 0;
		$groupEntity = new \CGroup();

		if ($this->isNewGroup())
		{
			unset($group['ID']);
			$group['IS_SYSTEM'] = 'N';
			$group['USER_ID'] = [];

			$result = $groupEntity->add($group);

			if ($result)
			{
				$id = $result;
			}
		}
		else
		{
			$result = $groupEntity->update($group['ID'], array_diff_key($group, ['ID' => true]));

			if ($result)
			{
				$id = $group['ID'];
			}
		}

		if (!$result)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error($groupEntity->LAST_ERROR);
		}

		return $id;
	}

	public function saveFormAjaxAction()
	{
		$response = [];

		if (!$this->checkModules() || !$this->checkPermissions())
		{
			return $response;
		}

		$data = $this->request->get('data') ?: [];
		$groupId = $this->saveBuyerGroup($data);

		if (!empty($groupId))
		{
			$response['redirectUrl'] = $this->getDetailUrl($groupId);
		}
		elseif ($this->errorCollection->isEmpty())
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_ORDER_BUYER_GROUP_EDIT_SAVE_ERROR'));
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

		return true;
	}

	protected function checkPermissions()
	{
		$crmPerms = new CCrmPerms(\Bitrix\Main\Engine\CurrentUser::get()->getId());

		if (!$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_PERMISSION_DENIED'));
			return false;
		}

		$this->arResult['PERM_CAN_EDIT'] = $crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

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