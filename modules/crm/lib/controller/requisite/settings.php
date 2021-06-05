<?php


namespace Bitrix\Crm\Controller\Requisite;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Main\Engine\Controller;

class Settings extends Controller
{
	public function setSelectedEntityRequisiteAction($entityTypeId, $entityId, $requisiteId, $bankDetailId = null)
	{
		return EntityRequisite::setDef($entityTypeId, $entityId, $requisiteId, $bankDetailId);
	}
}