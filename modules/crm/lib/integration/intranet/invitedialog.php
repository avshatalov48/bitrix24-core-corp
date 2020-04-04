<?php

/**
* Bitrix Framework
* @package bitrix
* @subpackage crm
* @copyright 2001-2019 Bitrix
*/

namespace Bitrix\Crm\Integration\Intranet;

class InviteDialog
{
	public static function onTransferEMailUser(&$arFields)
	{
		$res = \CUserTypeEntity::getList(
			[],
			[
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_USER_CRM_ENTITY',
			]
		);
		if ($res->fetch())
		{
			$arFields["UF_USER_CRM_ENTITY"] = false;
		}

		return true;
	}
}
?>