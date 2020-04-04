<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant;
use Bitrix\Voximplant\Security\Permissions;

Loc::loadMessages(__FILE__);

class CVoximplantIvrListComponent extends \CBitrixComponent
{
	protected $gridId = "voximplant_ivr_list";
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

		$cursor = Voximplant\Model\IvrTable::getList(array(
			"order" => $sorting["sort"],
			"select" => array('ID', 'NAME'),
			"count_total" => true,
			"offset" => ($nav->getOffset()),
			"limit" => ($nav->getLimit())
		));

		$rows = array();
		while ($row = $cursor->fetch())
		{
			$ivrId = intval($row['ID']);
			$editUrl = CVoxImplantMain::GetPublicFolder().'editivr.php?ID='.$ivrId;
			$t_row = array(
				"data" => array_map('htmlspecialcharsbx', $row),
				"columns" => array(),
				"editable" => true,
				"actions" => array(
					array(
						'TITLE' => Loc::getMessage('VOX_IVR_LIST_EDIT'),
						'TEXT' => Loc::getMessage('VOX_IVR_LIST_EDIT'),
						'ONCLICK' => "BX.Voximplant.IvrList.getInstance().edit('" . CUtil::JSEscape($editUrl) . "')",
						'DEFAULT' => true
					),
					array(
						'TITLE' => Loc::getMessage("VOX_IVR_LIST_DELETE"),
						'TEXT' => Loc::getMessage("VOX_IVR_LIST_DELETE"),
						'ONCLICK' => "BX.Voximplant.IvrList.getInstance().delete($ivrId)"
					)
				),
			);
			$rows[] = $t_row;
		}

		$result['GRID_ID'] = $this->gridId;
		$result['ROWS'] = $rows;
		$result["ROWS_COUNT"] = $cursor->getCount();
		$nav->setRecordCount($cursor->getCount());
		$result["SORT"] = $sorting["sort"];
		$result["SORT_VARS"] = $sorting["vars"];
		$result["NAV_OBJECT"] = $nav;

		$result["HEADERS"] = array(
			array("id" => "ID", "name" => Loc::getMessage("VOX_IVR_LIST_ID"), "default" => true, "editable" => false),
			array("id" => "NAME", "name" => Loc::getMessage("VOX_IVR_LIST_NAME"), "default" => true, "editable" => false),
		);

		$result["CREATE_IVR_URL"] = CVoxImplantMain::GetPublicFolder().'editivr.php?ID=0';
		$result["IS_IVR_ENABLED"] = \Bitrix\Voximplant\Ivr\Ivr::isEnabled();
		return $result;
	}
}