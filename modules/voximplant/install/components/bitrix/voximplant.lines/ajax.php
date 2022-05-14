<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

class VoximplantLinesAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function __construct(\Bitrix\Main\Request $request = null)
	{
		parent::__construct($request);

		\Bitrix\Main\Loader::includeModule('voximplant');
	}

	public function getUnassignedNumbersAction()
	{
		if (!\Bitrix\Voximplant\Security\Permissions::createWithCurrentUser()->canModifySettings())
		{
			$this->addError(new \Bitrix\Main\Error('Access denied', 'ACCESS_DENIED'));
			return null;
		}

		$cursor = \Bitrix\Voximplant\ConfigTable::getList([
			'select' => [
				'RENTED_NUMBER' => 'NUMBER.NUMBER'
			],
			'filter' => [
				'=PORTAL_MODE' => CVoxImplantConfig::MODE_RENT
			]
		]);

		$result = [];

		while($row = $cursor->fetch())
		{
			if(!$row['RENTED_NUMBER'])
			{
				continue;
			}

			$result[] = [
				'ID' => $row['RENTED_NUMBER'],
				'NAME' => \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($row['RENTED_NUMBER'])->format(\Bitrix\Main\PhoneNumber\Format::INTERNATIONAL)
			];
		}

		return $result;
	}

	public function createGroupAction($name, array $numbers = [])
	{
		if (!\Bitrix\Voximplant\Security\Permissions::createWithCurrentUser()->canModifySettings())
		{
			$this->addError(new \Bitrix\Main\Error('Access denied', 'ACCESS_DENIED'));
			return null;
		}

		if(!is_string($name) || $name == "")
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage("VOX_LINES_ERROR_EMPTY_NAME"));
			return null;
		}

		$row = \Bitrix\Voximplant\ConfigTable::getRow([
			'filter' => [
				'=PHONE_NAME' => $name
			]
		]);
		if ($row)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage("VOX_LINES_ERROR_NAME_EXISTS"));
			return null;
		}

		$unassignedNumbers = $this->getUnassignedNumbersAction();
		if(!is_array($unassignedNumbers))
		{
			return null;
		}

		$unassignedNumbers = array_map(function($a){return $a['ID'];}, $unassignedNumbers);
		$numbers = array_intersect($numbers, $unassignedNumbers);

		$numberFields = \Bitrix\Voximplant\Model\NumberTable::getList([
			'filter' => ['=NUMBER' => $numbers]
		])->fetchAll();

		$numbersCount = count($numberFields);
		if($numbersCount == 0)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage("VOX_LINES_ERROR_NO_NUMBERS"));
			return null;
		}

		// using config of the first number
		$configId = $numberFields[0]["CONFIG_ID"];
		$updateResult = \Bitrix\Voximplant\ConfigTable::update($configId, [
			"PORTAL_MODE" => CVoxImplantConfig::MODE_GROUP,
			"PHONE_NAME" => $name,
			"SEARCH_ID" => null
		]);

		if(!$updateResult->isSuccess())
		{
			$this->errorCollection->add($updateResult->getErrors());
			return null;
		}

		for($i = 1; $i < $numbersCount; $i++)
		{
			$updateResult = \Bitrix\Voximplant\Model\NumberTable::update($numberFields[$i]["ID"], [
				"CONFIG_ID" => $configId
			]);

			if($updateResult->isSuccess())
			{
				\Bitrix\Voximplant\ConfigTable::delete($numberFields[$i]["CONFIG_ID"]);
			}
		}

		return true;
	}

	public function addToGroupAction($number, $groupId)
	{
		if (!\Bitrix\Voximplant\Security\Permissions::createWithCurrentUser()->canModifySettings())
		{
			$this->addError(new \Bitrix\Main\Error('Access denied', 'ACCESS_DENIED'));
			return null;
		}

		$numberFields = \Bitrix\Voximplant\Model\NumberTable::getRow([
			"filter" => [
				"=NUMBER" => $number
			]
		]);

		if(!$numberFields)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage("VOX_LINES_ERROR_NUMBER_NOT_FOUND"));
			return null;
		}

		$groupId = (int)$groupId;
		$checkRow = \Bitrix\Voximplant\ConfigTable::getRow([
			'filter' => [
				'=ID' => $groupId,
				'=PORTAL_MODE' => CVoxImplantConfig::MODE_GROUP
			]
		]);

		if(!$checkRow)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage("VOX_LINES_ERROR_GROUP_NOT_FOUND"));
			return null;
		}

		if($numberFields["CONFIG_ID"] == $groupId)
		{
			// nothing to do
			return true;
		}

		$oldConfigId = $numberFields["CONFIG_ID"];
		$updateResult = \Bitrix\Voximplant\Model\NumberTable::update($numberFields["ID"], [
			"CONFIG_ID" => $groupId
		]);

		if(!$updateResult->isSuccess())
		{
			$this->errorCollection->add($updateResult->getErrors());
			return null;
		}

		\Bitrix\Voximplant\ConfigTable::delete($oldConfigId);

		return true;
	}

	public function removeFromGroupAction($number)
	{
		if (!\Bitrix\Voximplant\Security\Permissions::createWithCurrentUser()->canModifySettings())
		{
			$this->addError(new \Bitrix\Main\Error('Access denied', 'ACCESS_DENIED'));
			return null;
		}

		$numberFields = \Bitrix\Voximplant\Model\NumberTable::getRow([
			"filter" => [
				"=NUMBER" => $number
			]
		]);

		if(!$numberFields)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage("VOX_LINES_ERROR_NUMBER_NOT_FOUND"));
			return null;
		}

		$configFields = \Bitrix\Voximplant\ConfigTable::getRowById($numberFields["CONFIG_ID"]);
		if($configFields["PORTAL_MODE"] != CVoxImplantConfig::MODE_GROUP)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage("VOX_LINES_ERROR_NUMBER_NOT_IN_GROUP"));
			return null;
		}

		unset($configFields["ID"]);
		unset($configFields["PHONE_NAME"]);

		$configFields["PORTAL_MODE"] = CVoxImplantConfig::MODE_RENT;
		$newConfigResult = \Bitrix\Voximplant\ConfigTable::add($configFields);
		if(!$newConfigResult->isSuccess())
		{
			$this->addErrors($newConfigResult->getErrors());
			return null;
		}

		$newConfigId = $newConfigResult->getId();

		\Bitrix\Voximplant\Model\NumberTable::update($numberFields["ID"], [
			"CONFIG_ID" => $newConfigId
		]);

		return true;
	}

	public function deleteGroupAction($id)
	{
		if (!\Bitrix\Voximplant\Security\Permissions::createWithCurrentUser()->canModifySettings())
		{
			$this->addError(new \Bitrix\Main\Error('Access denied', 'ACCESS_DENIED'));
			return null;
		}

		$id = (int)$id;
		$configFields = \Bitrix\Voximplant\ConfigTable::getRowById($id);
		if($configFields["PORTAL_MODE"] != CVoxImplantConfig::MODE_GROUP)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage("VOX_LINES_ERROR_GROUP_NOT_FOUND"));
			return null;
		}

		$numbers = \Bitrix\Voximplant\Model\NumberTable::getList([
			'filter' => [
				'=CONFIG_ID' => $id
			]
		]);

		unset($configFields["ID"]);
		unset($configFields["PHONE_NAME"]);
		$configFields["PORTAL_MODE"] = CVoxImplantConfig::MODE_RENT;
		$configFields["USE_SIP_TO"] = "N";

		foreach ($numbers as $numberFields)
		{
			$newConfigResult = \Bitrix\Voximplant\ConfigTable::add($configFields);
			if(!$newConfigResult->isSuccess())
			{
				$this->errorCollection[] = new \Bitrix\Main\Error("Database error");
				return null;
			}

			$newConfigId = $newConfigResult->getId();

			$updateResult = \Bitrix\Voximplant\Model\NumberTable::update($numberFields["ID"], [
				"CONFIG_ID" => $newConfigId
			]);
			if(!$updateResult->isSuccess())
			{
				$this->errorCollection[] = new \Bitrix\Main\Error("Database error");
				return null;
			}
		}

		\Bitrix\Voximplant\ConfigTable::delete($id);

		return true;
	}
}
