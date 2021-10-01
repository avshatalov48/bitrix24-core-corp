<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant;
use Bitrix\Voximplant\Limits;
use Bitrix\Voximplant\Security\Permissions;

Loc::loadMessages(__FILE__);

class CVoximplantQueueListComponent extends \CBitrixComponent
{
	protected $gridId = "voximplant_queue_list";
	protected $gridOptions;
	protected $userPermissions;

	public function __construct($component)
	{
		parent::__construct($component);

		\Bitrix\Main\Loader::includeModule("voximplant");

		$this->gridOptions = new CGridOptions($this->gridId);
		$this->userPermissions = Permissions::createWithCurrentUser();
	}

	public function executeComponent()
	{
		if(!$this->checkAccess())
			return false;

		$this->arResult = $this->prepareData();
		$this->includeComponentTemplate();
		return $this->arResult;
	}

	protected function checkAccess()
	{
		return $this->userPermissions->canPerform(Permissions::ENTITY_SETTINGS, Permissions::ACTION_MODIFY);
	}

	protected function prepareData()
	{
		$result = array();

		$sorting = $this->gridOptions->GetSorting(array("sort" => array("ID" => "DESC")));
		$navParams = $this->gridOptions->GetNavParams();
		$pageSize = $navParams['nPageSize'];

		$nav = new \Bitrix\Main\UI\PageNavigation("page");
		$nav->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri();

		$cursor = Voximplant\Model\QueueTable::getList(array(
			//"order" => $sorting["sort"],
			"order" => array('ID' => 'ASC'),
			"select" => array('ID', 'NAME', 'TYPE', 'PHONE_NUMBER'),
			"count_total" => true,
			"offset" => ($nav->getOffset()),
			"limit" => ($nav->getLimit())
		));

		$rows = array();
		while ($row = $cursor->fetch())
		{
			$row["TYPE"] = $this->getTypeName($row['TYPE']);
			$editUrl = CVoxImplantMain::GetPublicFolder().'editgroup.php?ID='.intval($row['ID']);
			$t_row = array(
				"data" => array_map('htmlspecialcharsbx', $row),
				"columns" => array(),
				"editable" => true,
				"actions" => array(
					array(
						'TITLE' => Loc::getMessage('VOX_QUEUE_LIST_EDIT'),
						'TEXT' => Loc::getMessage('VOX_QUEUE_LIST_EDIT'),
						'ONCLICK' => "BX.Voximplant.QueueList.getInstance().edit('".CUtil::JSEscape($editUrl)."')",
						'DEFAULT' => true
					),
					array(
						'TITLE' => Loc::getMessage("VOX_QUEUE_LIST_DELETE"),
						'TEXT' => Loc::getMessage("VOX_QUEUE_LIST_DELETE"),
						'ONCLICK' => "BX.Voximplant.QueueList.getInstance().delete({$row['ID']})"
					)
				),
			);
			$rows[] = $t_row;
		}

		$result['ROWS'] = $rows;
		$result["ROWS_COUNT"] = $cursor->getCount();
		$nav->setRecordCount($cursor->getCount());
		$result["SORT"] = $sorting["sort"];
		$result["SORT_VARS"] = $sorting["vars"];
		$result["NAV_OBJECT"] = $nav;

		$result["HEADERS"] = array(
			array("id" => "ID", "name" => GetMessage("VOX_QUEUE_LIST_ID"), "default" => true, "editable" => false),
			array("id" => "NAME", "name" => GetMessage("VOX_QUEUE_LIST_NAME"), "default" => true, "editable" => false),
			array("id" => "TYPE", "name" => GetMessage("VOX_QUEUE_LIST_TYPE"), "default" => true, "editable" => false),
			array("id" => "PHONE_NUMBER", "name" => Loc::getMessage("VOX_QUEUE_LIST_PHONE_NUMBER"), "default" => true, "editable" => false),
		);
		$result["GRID_ID"] = $this->gridId;
		$result["CAN_CREATE_GROUP"] = Limits::canCreateGroup();
		$result["MAXIMUM_GROUPS"] = Limits::getMaximumGroups();
		if ($result["CAN_CREATE_GROUP"])
		{
			$result["CREATE_QUEUE_URL"] = CVoxImplantMain::GetPublicFolder().'editgroup.php?ID=0';
		}
		else
		{
			if ($result["MAXIMUM_GROUPS"] == 0)
			{
				$helperCode = 'limit_contact_center_telephony_groups_zero';
			}
			else
			{
				$helperCode = 'limit_contact_center_telephony_groups';
			}
			$result["CREATE_QUEUE_URL"] = 'javascript: BX.UI.InfoHelper.show(\''.$helperCode.'\');';
		}

		return $result;
	}

	protected static function getTypeName($type)
	{
		switch ($type)
		{
			case CVoxImplantConfig::QUEUE_TYPE_EVENLY:
				return GetMessage("VOX_QUEUE_LIST_TYPE_EVENLY");
			case CVoxImplantConfig::QUEUE_TYPE_STRICTLY:
				return GetMessage("VOX_QUEUE_LIST_TYPE_STRICTLY");
			case CVoxImplantConfig::QUEUE_TYPE_ALL:
				return GetMessage("VOX_QUEUE_LIST_TYPE_ALL");
			default:
				return $type;
		}
	}
}