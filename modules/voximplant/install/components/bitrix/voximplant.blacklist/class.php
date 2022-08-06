<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Security\Permissions;

Loc::loadMessages(__FILE__);


class VoximplantBlackListComponent extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	protected $gridId = "voximplant_blacklist";
	protected $gridOptions;
	protected $userPermissions;

	public function __construct($component = null)
	{
		\Bitrix\Main\Loader::includeModule("voximplant");

		parent::__construct($component);

		$this->gridOptions = new CGridOptions($this->gridId);
		$this->userPermissions = Permissions::createWithCurrentUser();
	}

	public function executeComponent()
	{
		$permissions = Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Permissions::ENTITY_SETTINGS, Permissions::ACTION_MODIFY))
		{
			return false;
		}

		if(\Bitrix\Voximplant\Limits::isRestOnly())
		{
			return false;
		}

		$this->arResult = $this->prepareData();
		$this->includeComponentTemplate();
		return $this->arResult;
	}

	protected function prepareData()
	{
		$result = [];

		$sorting = $this->gridOptions->getSorting(["sort" => ["ID" => "DESC"]]);
		$navParams = $this->gridOptions->getNavParams();
		$pageSize = $navParams["nPageSize"];

		$nav = new \Bitrix\Main\UI\PageNavigation("page");
		$nav->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri();

		$cursor = \Bitrix\Voximplant\BlacklistTable::getList([
			//"order" => $sorting["sort"],
			"count_total" => true,
			"offset" => $nav->getOffset(),
			"limit" => $nav->getLimit(),
			"order" => ["ID" => "ASC"],
		]);

		$rows = array();
		while ($row = $cursor->fetch())
		{
			$t_row = array(
				"data" => array_map("htmlspecialcharsbx", $row),
				"columns" => array(),
				"editable" => false,
				"actions" => array(
					array(
						"TITLE" => Loc::getMessage("VOX_BLACKLIST_ACTION_DELETE"),
						"TEXT" => Loc::getMessage("VOX_BLACKLIST_ACTION_DELETE"),
						"ONCLICK" => "BX.Voximplant.Blacklist.deleteNumber({$row['ID']})"
					)
				),
			);
			$rows[] = $t_row;
		}

		$result["ROWS"] = $rows;
		$result["ROWS_COUNT"] = $cursor->getCount();
		$nav->setRecordCount($cursor->getCount());
		$result["SORT"] = $sorting["sort"];
		$result["SORT_VARS"] = $sorting["vars"];
		$result["NAV_OBJECT"] = $nav;

		$result["HEADERS"] = array(
			array("id" => "ID", "name" => GetMessage("VOX_BLACKLIST_HEADER_ID"), "default" => false, "editable" => false),
			array("id" => "PHONE_NUMBER", "name" => GetMessage("VOX_BLACKLIST_HEADER_PHONE_NUMBER"), "default" => true, "editable" => false),
			array("id" => "INSERTED", "name" => GetMessage("VOX_BLACKLIST_HEADER_DATE_CREATE"), "default" => true, "editable" => false),
		);
		$result["GRID_ID"] = $this->gridId;

		return $result;
	}

	public function configureActions()
	{
		return array();
	}

	public function getSettingsAction()
	{
		return [
			"autoBlock" => Bitrix\Main\Config\Option::get("voximplant", "blacklist_auto", "N") == "Y" ? "Y" : "N",
			"interval" => (int)Bitrix\Main\Config\Option::get("voximplant", "blacklist_time", 5),
			"ringsCount" => (int)Bitrix\Main\Config\Option::get("voximplant", "blacklist_count", 5),
			"registerInCRM" => Bitrix\Main\Config\Option::get("voximplant", "blacklist_register_in_crm", "N") == "Y" ? "Y" : "N",
		];
	}

	public function setSettingsAction(array $settings)
	{
		if(!$this->userPermissions->canPerform(Permissions::ENTITY_SETTINGS, Permissions::ACTION_MODIFY))
		{
			return false;
		}

		Bitrix\Main\Config\Option::set("voximplant", "blacklist_auto", $settings["autoBlock"] == "Y" ? "Y" : "N");
		Bitrix\Main\Config\Option::set("voximplant", "blacklist_time", (int)$settings["interval"] ?: 5);
		Bitrix\Main\Config\Option::set("voximplant", "blacklist_count", (int)$settings["ringsCount"] ?: 5);
		Bitrix\Main\Config\Option::set("voximplant", "blacklist_register_in_crm", $settings["registerInCRM"] == "Y" ? "Y" : "N");

		return true;
	}

	public function addNumbersAction(array $numbers)
	{
		if(!$this->userPermissions->canPerform(Permissions::ENTITY_SETTINGS, Permissions::ACTION_MODIFY))
		{
			return false;
		}

		foreach ($numbers as $number)
		{
			$row = \Bitrix\Voximplant\BlacklistTable::getRow([
				"filter" => ["=PHONE_NUMBER" => $number]
			]);

			if($row)
			{
				continue;
			}

			Bitrix\Voximplant\BlacklistTable::add(array(
				"PHONE_NUMBER" => $number
			));
		}

		return true;
	}

	public function deleteNumberAction($numberId)
	{
		if(!$this->userPermissions->canPerform(Permissions::ENTITY_SETTINGS, Permissions::ACTION_MODIFY))
		{
			return false;
		}
		
		\Bitrix\Voximplant\BlacklistTable::delete($numberId);
		return true;
	}
}