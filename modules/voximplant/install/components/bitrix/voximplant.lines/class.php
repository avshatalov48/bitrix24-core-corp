<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Security\Permissions;

Loc::loadMessages(__FILE__);


class VoximplantLinesComponent extends \CBitrixComponent
{
	protected $gridId = "voximplant_lines";
	protected $gridOptions;
	protected $userPermissions;
	protected $rentedNumbers;
	protected $localNumbers;

	public function __construct($component)
	{
		parent::__construct($component);

		\Bitrix\Main\Loader::includeModule("voximplant");

		$this->gridOptions = new \Bitrix\Main\Grid\Options($this->gridId);
		$this->userPermissions = Permissions::createWithCurrentUser();
		$this->rentedNumbers = CVoxImplantPhone::GetRentNumbers();
		$sip = new CVoxImplantSip();
		$sip->updateSipRegistrations();
		if(is_array($this->rentedNumbers) && CVoxImplantPhone::GetRentedNumbersCount() != count($this->rentedNumbers))
		{
			CVoxImplantPhone::syncWithController([
				'numbers' => $this->rentedNumbers,
				'create' => true,
				'delete' => true
			]);
		}

		$this->localNumbers = [];
		$cursor = \Bitrix\Voximplant\Model\NumberTable::getList();
		while ($row = $cursor->fetch())
		{
			$this->localNumbers[$row["NUMBER"]] = $row;
		}
	}

	public function executeComponent()
	{
		if (!\Bitrix\Main\Loader::includeModule("voximplant"))
		{
			return false;
		}

		$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
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

		$rows = [];

		$cursor = \Bitrix\Voximplant\ConfigTable::getList([
			//"order" => $sorting["sort"],
			"select" => [
				"ID",
				"SEARCH_ID",
				"PORTAL_MODE",
				"PHONE_NAME",
				"SIP_TYPE" => "SIP_CONFIG.TYPE",
				"SIP_SERVER" => "SIP_CONFIG.SERVER",
				"SIP_LOGIN" => "SIP_CONFIG.LOGIN",
				"SIP_STATUS_CODE" => "SIP_CONFIG.REGISTRATION_STATUS_CODE",
				"CALLER_ID_NUMBER" => "CALLER_ID.NUMBER",
				"CALLER_ID_VERIFIED" => "CALLER_ID.VERIFIED",
				"CALLER_ID_VERIFIED_UNTIL" => "CALLER_ID.VERIFIED_UNTIL"
			],
			"order" => ["ID" => "ASC"],
		]);

		while ($row = $cursor->fetch())
		{
			$gridRow = $this->getGridRow($row);
			if($gridRow)
			{
				$rows[] = $gridRow;
			}
		}

		$expandedIds = $this->gridOptions->getExpandedRows();
		if($expandedIds)
		{
			$expandedIds = array_map(function($a)
			{
				return mb_substr($a, 0, 6) == "group_" ? (int)mb_substr($a, 6) : (int)$a;
			}, $expandedIds);
			$expandedIds = array_filter($expandedIds);

			foreach ($expandedIds as $id)
			{
				$numbers = array_filter($this->localNumbers, function ($a) use ($id)
				{
					return $id == $a['CONFIG_ID'];
				});

				foreach ($numbers as $number)
				{
					$rows[] = [
						"data" => $this->getRentedFields($id, $number["NUMBER"]),
						"parent_id" => "group_".$id,
						"columns" => array(),
						"editable" => false,
						"actions" => $this->getGroupedNumberActions($number["NUMBER"], $number["TO_DELETE"] == "Y"),
					];
				}
			}
		}

		$result["NAV_OBJECT"] ??= null;
		$result["ROWS_COUNT"] ??= null;
		$result["ROWS"] = $rows;
		$result["TOTAL_ROWS_COUNT"] = count($rows);
		$result["SORT"] = $sorting["sort"];
		$result["SORT_VARS"] = $sorting["vars"];

		$result["HEADERS"] = array(
			array("id" => "ID", "name" => GetMessage("VOX_LINES_HEADER_ID"), "default" => true, "editable" => false),
			array("id" => "NAME", "name" => GetMessage("VOX_LINES_HEADER_NAME"), "default" => true, "editable" => false, "shift" => true),
			array("id" => "TYPE", "name" => GetMessage("VOX_LINES_HEADER_TYPE"), "default" => true, "editable" => false),
			array("id" => "DESCRIPTION", "name" => GetMessage("VOX_LINES_HEADER_DESCRIPTION"), "default" => true, "editable" => false),
			array("id" => "STATE", "name" => GetMessage("VOX_LINES_HEADER_STATE"), "default" => true, "editable" => false)
		);
		$result["GRID_ID"] = $this->gridId;
		$result["TELEPHONY_AVAILABLE"] = \Bitrix\Voximplant\Limits::canManageTelephony();
		return $result;
	}

	protected function getGridRow($databaseRow)
	{
		if($databaseRow["PORTAL_MODE"] == CVoxImplantConfig::MODE_LINK)
		{
			return [
				"data" => $this->getLinkFields($databaseRow),
				"columns" => array(),
				"editable" => false,
				"actions" => $this->getLinkActions($databaseRow["ID"], $databaseRow["CALLER_ID_NUMBER"], $databaseRow["CALLER_ID_VERIFIED"] == "Y"),
			];
		}
		else if($databaseRow["PORTAL_MODE"] == CVoxImplantConfig::MODE_RENT)
		{
			$numbers = array_filter($this->localNumbers, function ($a) use ($databaseRow)
			{
				return $databaseRow['ID'] == $a['CONFIG_ID'];
			});
			$numbers = array_values($numbers);

			if(empty($numbers))
			{
				return null;
			}
			$number = $numbers[0]['NUMBER'];
			$toDelete = $numbers[0]['TO_DELETE'] == 'Y';

			return [
				"data" => $this->getRentedFields($databaseRow['ID'], $number),
				"columns" => array(),
				"editable" => false,
				"actions" => $this->getRentActions($databaseRow['ID'], $number, $toDelete),
			];
		}
		else if($databaseRow["PORTAL_MODE"] == CVoxImplantConfig::MODE_GROUP)
		{
			return [
				"id" => "group_".$databaseRow["ID"],
				"data" => $this->getGroupFields($databaseRow),
				"columns" => array(),
				"editable" => false,
				"actions" => $this->getGroupActions($databaseRow["ID"]),
				"has_child" => true
			];
		}
		else if($databaseRow["PORTAL_MODE"] == CVoxImplantConfig::MODE_SIP)
		{
			return [
				"data" => $this->getSipFields($databaseRow),
				"columns" => array(),
				"editable" => false,
				"actions" => $this->getSipActions($databaseRow["ID"]),
			];
		}
	}

	protected function getLineTypeName($portalMode)
	{
		switch ($portalMode)
		{
			case CVoxImplantConfig::MODE_LINK:
				return Loc::getMessage("VOX_LINES_MODE_LINK");
			case CVoxImplantConfig::MODE_RENT:
				return Loc::getMessage("VOX_LINES_MODE_RENT");
			case CVoxImplantConfig::MODE_SIP:
				return Loc::getMessage("VOX_LINES_MODE_SIP");
			case CVoxImplantConfig::MODE_GROUP:
				return Loc::getMessage("VOX_LINES_MODE_GROUP");
			default:
				return "";
		}
	}

	protected function getLinkFields($row)
	{
		$description = CVoxImplantPhone::getCallerIdDescription([
			"VERIFIED" => $row["CALLER_ID_VERIFIED"] == "Y",
			"VERIFIED_UNTIL" => $row["CALLER_ID_VERIFIED_UNTIL"]
		]);
		$name = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse("+" . $row["CALLER_ID_NUMBER"])->format(\Bitrix\Main\PhoneNumber\Format::INTERNATIONAL);

		return [
			"ID" => $row["ID"],
			"NAME" => "<span class='voximplant-grid-icon voximplant-grid-link'>" . htmlspecialcharsbx($name) . "</span>",
			"TYPE" => $this->getLineTypeName(CVoxImplantConfig::MODE_LINK),
			"DESCRIPTION" => $description,
		];
	}

	protected function getRentedFields($configId, $number)
	{
		$numberFields = $this->rentedNumbers[$number];

		$name = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($number)->format(\Bitrix\Main\PhoneNumber\Format::INTERNATIONAL);
		$description = CVoxImplantPhone::getNumberDescription($numberFields);

		if($numberFields["TO_DELETE"] === 'Y')
		{
			$state = "<span class='voximplant-grid-state-fail'>" . CVoxImplantPhone::getNumberStatus($numberFields) . "</span>";
		}
		else
		{
			$state = "<span class='voximplant-grid-state-active'>" . CVoxImplantPhone::getNumberStatus($numberFields) . "</span>";
		}

		return [
			"ID" => $configId,
			"NAME" => "<span class='voximplant-grid-icon voximplant-grid-rented'>" . htmlspecialcharsbx($name) . "</span>",
			"TYPE" => $this->getLineTypeName(CVoxImplantConfig::MODE_RENT),
			"DESCRIPTION" => $description,
			"STATE" => $state
		];
	}

	protected function getGroupFields($row)
	{
		$name = $row["PHONE_NAME"];
		$numbers = array_filter($this->localNumbers, function ($a) use ($row)
		{
			return $row['ID'] == $a['CONFIG_ID'];
		});
		$numbers = array_map(function($a) {return $a['NUMBER'];}, $numbers);
		$numbers = array_values($numbers);

		$totalSum = 0;
		foreach ($this->rentedNumbers as $numberFields)
		{
			if(in_array($numberFields['NUMBER'], $numbers))
			{
				$totalSum += $numberFields['PRICE'];
			}
		}
		$description = Loc::getMessage("VOX_LINES_DESCRIPTION_GROUP", [
			"#PRICE#" => CVoxImplantMain::formatMoney($totalSum)
		]);

		return [
			"ID" => $row["ID"],
			"NAME" => "<span class='voximplant-grid-icon voximplant-grid-group'>" . htmlspecialcharsbx($name) . "</span>",
			"TYPE" => $this->getLineTypeName(CVoxImplantConfig::MODE_GROUP),
			"DESCRIPTION" => $description,
		];
	}

	protected function getSipFields($row)
	{
		$name = $row["PHONE_NAME"] ? htmlspecialcharsbx($row["PHONE_NAME"]) : CVoxImplantConfig::GetDefaultPhoneName($row);
		if($row["SIP_TYPE"] === CVoxImplantSip::TYPE_OFFICE)
		{
			$state = "-";
		}
		if ($row['SIP_TYPE'] === CVoxImplantSip::TYPE_CLOUD)
		{
			if (!$row['SIP_STATUS_CODE'])
			{
				$state = "<span class='voximplant-grid-state-process'>" . Loc::getMessage("VOX_LINES_SIP_STATUS_IN_PROCESS") . "</span>";
			}
			elseif ((int)$row["SIP_STATUS_CODE"] === 200)
			{
				$state = "<span class='voximplant-grid-state-active'>" . Loc::getMessage("VOX_LINES_SIP_STATUS_RECOVERED") . "</span>";
			}
			else
			{
				$state = "<span class='voximplant-grid-state-fail'>" . Loc::getMessage("VOX_LINES_SIP_STATUS_FAIL") . "</span>";
			}
		}


		return [
			"ID" => $row["ID"],
			"NAME" => "<span class='voximplant-grid-icon voximplant-grid-sip'>" . $name . "</span>",
			"TYPE" => $this->getLineTypeName(CVoxImplantConfig::MODE_SIP),
			"DESCRIPTION" => htmlspecialcharsbx(CVoxImplantSip::getConnectionDescription($row)),
			"STATE" => $state
		];
	}

	/**
	 * @param string $callerIdNumber
	 * @param bool $isVerified
	 * @return array
	 */
	protected function getLinkActions($configId, $callerIdNumber, $isVerified)
	{
		$result = [];

		if(!$this->userPermissions->canModifyLines())
		{
			return $result;
		}
		$configId = (int)$configId;

		if($isVerified)
		{
			$result[] = [
				"TITLE" => Loc::getMessage("VOX_LINES_ACTION_PROLONG_CALLER_ID"),
				"TEXT" => Loc::getMessage("VOX_LINES_ACTION_PROLONG_CALLER_ID"),
				"ONCLICK" => "BX.Voximplant.Lines.verifyCallerId('$callerIdNumber')",
				"DEFAULT" => false
			];

			$result[] = [
				"TITLE" => Loc::getMessage("VOX_LINES_ACTION_CONFIGURE"),
				"TEXT" => Loc::getMessage("VOX_LINES_ACTION_CONFIGURE"),
				"ONCLICK" => "BX.Voximplant.Lines.showConfig('$configId')",
				"DEFAULT" => true
			];
		}
		else
		{
			$result[] = [
				"TITLE" => Loc::getMessage("VOX_LINES_ACTION_VERIFY_CALLER_ID"),
				"TEXT" => Loc::getMessage("VOX_LINES_ACTION_VERIFY_CALLER_ID"),
				"ONCLICK" => "BX.Voximplant.Lines.verifyCallerId('$callerIdNumber')",
				"DEFAULT" => false
			];
		}

		$result[] = [
			"TITLE" => Loc::getMessage("VOX_LINES_ACTION_DELETE"),
			"TEXT" => Loc::getMessage("VOX_LINES_ACTION_DELETE"),
			"ONCLICK" => "BX.Voximplant.Lines.deleteCallerId('$callerIdNumber')",
			"DEFAULT" => false
		];

		return $result;
	}

	protected function getGroupActions($configId)
	{
		$configId = (int)$configId;
		$result = [];

		if(!$this->userPermissions->canModifyLines())
		{
			return $result;
		}

		$result[] = [
			"TITLE" => Loc::getMessage("VOX_LINES_ACTION_CONFIGURE"),
			"TEXT" => Loc::getMessage("VOX_LINES_ACTION_CONFIGURE"),
			"ONCLICK" => "BX.Voximplant.Lines.showConfig('$configId')",
			"DEFAULT" => true
		];

		$result[] = [
			"TITLE" => Loc::getMessage("VOX_LINES_ACTION_UNGROUP"),
			"TEXT" => Loc::getMessage("VOX_LINES_ACTION_UNGROUP"),
			"ONCLICK" => "BX.Voximplant.Lines.deleteGroup('$configId')",
			"DEFAULT" => false
		];

		return $result;
	}

	/**
	 * @return array
	 */
	protected function getRentActions($configId, $number, $queedForDeletion = false)
	{
		$result = [];
		$configId = (int)$configId;
		if(!$this->userPermissions->canModifyLines())
		{
			return $result;
		}

		$result[] = [
			"TITLE" => Loc::getMessage("VOX_LINES_ACTION_CONFIGURE"),
			"TEXT" => Loc::getMessage("VOX_LINES_ACTION_CONFIGURE"),
			"ONCLICK" => "BX.Voximplant.Lines.showConfig('$configId')",
			"DEFAULT" => true
		];

		$groups = $this->getGroups();
		if(!empty($groups))
		{
			$groupMenuItems = array_map(function($g) use($number)
			{
				return [
					"TITLE" => htmlspecialcharsbx($g["NAME"]),
					"TEXT" => htmlspecialcharsbx($g["NAME"]),
					"ONCLICK" => "BX.Voximplant.Lines.addNumberToGroup('{$number}', {$g["ID"]})"
				];
			}, $groups);

			$result[] = [
				"TITLE" => Loc::getMessage("VOX_LINES_ACTION_ADD_TO_GROUP"),
				"TEXT" => Loc::getMessage("VOX_LINES_ACTION_ADD_TO_GROUP"),
				"MENU" => $groupMenuItems
			];
		}

		if($queedForDeletion)
		{
			$result[] = [
				"TITLE" => Loc::getMessage("VOX_LINES_CANCEL_NUMBER_DISCONNECT"),
				"TEXT" => Loc::getMessage("VOX_LINES_CANCEL_NUMBER_DISCONNECT"),
				"ONCLICK" => "BX.Voximplant.Lines.cancelNumberDeletion('{$number}')"
			];
		}
		else
		{
			$result[] = [
				"TITLE" => Loc::getMessage("VOX_LINES_DISCONNECT_NUMBER"),
				"TEXT" => Loc::getMessage("VOX_LINES_DISCONNECT_NUMBER"),
				"ONCLICK" => "BX.Voximplant.Lines.deleteNumber('{$number}')"
			];
		}

		return $result;
	}

	protected function getGroupedNumberActions($number, $queedForDeletion = false)
	{
		$result = [];
		if(!$this->userPermissions->canModifyLines())
		{
			return $result;
		}

		$result[] = [
			"TITLE" => Loc::getMessage("VOX_LINES_ACTION_REMOVE_FROM_GROUP"),
			"TEXT" => Loc::getMessage("VOX_LINES_ACTION_REMOVE_FROM_GROUP"),
			"ONCLICK" => "BX.Voximplant.Lines.removeNumberFromGroup('{$number}')"
		];

		if($queedForDeletion)
		{
			$result[] = [
				"TITLE" => Loc::getMessage("VOX_LINES_CANCEL_NUMBER_DISCONNECT"),
				"TEXT" => Loc::getMessage("VOX_LINES_CANCEL_NUMBER_DISCONNECT"),
				"ONCLICK" => "BX.Voximplant.Lines.cancelNumberDeletion('{$number}')"
			];
		}
		else
		{
			$result[] = [
				"TITLE" => Loc::getMessage("VOX_LINES_DISCONNECT_NUMBER"),
				"TEXT" => Loc::getMessage("VOX_LINES_DISCONNECT_NUMBER"),
				"ONCLICK" => "BX.Voximplant.Lines.deleteNumber('{$number}')"
			];
		}

		return $result;
	}

	protected function getSipActions($configId)
	{
		$configId = (int)$configId;
		$result = [];

		$result[] = [
			"TITLE" => Loc::getMessage("VOX_LINES_ACTION_CONFIGURE"),
			"TEXT" => Loc::getMessage("VOX_LINES_ACTION_CONFIGURE"),
			"ONCLICK" => "BX.Voximplant.Lines.showConfig('$configId')",
			"DEFAULT" => true
		];

		$result[] = [
			"TITLE" => Loc::getMessage("VOX_LINES_REMOVE_CONNECTION"),
			"TEXT" => Loc::getMessage("VOX_LINES_REMOVE_CONNECTION"),
			"ONCLICK" => "BX.Voximplant.Lines.deleteSip('$configId')"
		];

		return $result;
	}

	protected function getGroups()
	{
		static $result = null;

		if(!is_null($result))
		{
			return $result;
		}

		$result = \Bitrix\Voximplant\ConfigTable::getList([
			'select' => [
				'ID' => 'ID',
				'NAME' => 'PHONE_NAME'
			],
			'filter' => [
				'=PORTAL_MODE' => CVoxImplantConfig::MODE_GROUP
			],
		])->fetchAll();

		return $result;
	}
}