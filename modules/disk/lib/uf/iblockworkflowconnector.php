<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\Ui;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

final class IblockWorkflowConnector extends StubConnector
{
	public function canRead($userId)
	{
		if(!Loader::includeModule("iblock"))
		{
			return false;
		}

		$iblockId = $this->entityId;

		return (\CIBlockRights::userHasRightTo($iblockId, $iblockId, "element_read") ||
			\CIBlockSectionRights::userHasRightTo($iblockId, 0, "section_element_bind"));
	}

	public function canUpdate($userId)
	{
		return false;
	}
}
