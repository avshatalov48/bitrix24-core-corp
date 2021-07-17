<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\Ui;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

final class IblockElementConnector extends StubConnector
{
	public function canRead($userId)
	{
		if(!Loader::includeModule("iblock"))
		{
			return false;
		}

		$elementId = $this->entityId;
		$elementQuery = \CIBlockElement::getList(array(), array("ID" => $elementId), false, false, array("IBLOCK_ID"));
		$element = $elementQuery->fetch();
		if(!$element["IBLOCK_ID"])
		{
			return false;
		}

		return \CIBlockElementRights::userHasRightTo($element["IBLOCK_ID"], $elementId, "element_read");
	}

	public function canUpdate($userId)
	{
		if (!Loader::includeModule("iblock"))
		{
			return false;
		}

		$elementId = $this->entityId;
		$elementQuery = \CIBlockElement::getList(array(), array("ID" => $elementId), false, false, array("IBLOCK_ID"));
		$element = $elementQuery->fetch();
		if (!$element["IBLOCK_ID"])
		{
			return false;
		}

		return \CIBlockElementRights::userHasRightTo($element["IBLOCK_ID"], $elementId, "element_edit");
	}

	public function getDataToShow()
	{
		return $this->getDataToShowByUser($this->getUser()->getId());
	}

	public function getDataToShowByUser(int $userId)
	{
		if(!Loader::includeModule("lists"))
		{
			return false;
		}

		$elementId = $this->entityId;
		$elementQuery = \CIBlockElement::getList(array(),
			array("ID" => $elementId), false, false, array("NAME", "IBLOCK_ID"));
		$element = $elementQuery->fetch();
		if(!$element["IBLOCK_ID"] ||
			!\CIBlockElementRights::userHasRightTo($element["IBLOCK_ID"], $elementId, "element_read"))
		{
			return false;
		}
		$query = \CIBlock::getList(array(), array("ID" => $element["IBLOCK_ID"]), true);
		$iblock = $query->fetch();
		if(!$iblock)
		{
			return false;
		}

		$iblockMessages = \CIBlock::getMessages($element["IBLOCK_ID"]);
		$urlTemplate = \CList::getUrlByIblockId($element["IBLOCK_ID"]);

		return array(
			"TITLE" => $iblockMessages["ELEMENT_NAME"].": ".$iblock["NAME"],
			"DETAIL_URL" => \CComponentEngine::makePathFromTemplate($urlTemplate, array(
				"list_id" => $element["IBLOCK_ID"], "section_id" => 0, "element_id" => $elementId)),
			"DESCRIPTION" => $element["NAME"],
			"MEMBERS" => array()
		);
	}
}
