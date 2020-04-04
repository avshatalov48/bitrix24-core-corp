<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\Ui;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

final class IblockConnector extends StubConnector
{
	public function canRead($userId)
	{
		if(!Loader::includeModule("lists"))
		{
			return false;
		}

		$elementId = $this->entityId;
		$elementQuery = \CIBlockElement::getList(
			array(),
			array('ID' => $elementId),
			false,
			false,
			array('IBLOCK_TYPE_ID', 'IBLOCK_ID')
		);
		$element = $elementQuery->fetch();
		$listPerm = \CListPermissions::checkAccess(
			$this->getUser(),
			$element['IBLOCK_TYPE_ID'],
			$element['IBLOCK_ID']
		);
		if($listPerm < 0)
		{
			return false;
		}
		elseif(($listPerm < \CListPermissions::CAN_READ &&
			!\CIBlockElementRights::userHasRightTo($element['IBLOCK_ID'], $elementId, "element_read")))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	public function canUpdate($userId)
	{
		return false;
	}
}
