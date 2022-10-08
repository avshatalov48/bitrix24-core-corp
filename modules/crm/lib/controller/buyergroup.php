<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class BuyerGroup extends Controller
{
	public function listAction(): Page
	{
		$groups = \Bitrix\Crm\Order\BuyerGroup::getPublicList();

		return new Page('BUYER_GROUPS', $groups, count($groups));
	}

	protected function checkReadPermissionEntity()
	{
		$checkResult = new Result();

		$crmPerms = new \CCrmPerms(\Bitrix\Main\Engine\CurrentUser::get()->getId());

		if (!$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$checkResult->addError(new Error('Access Denied'));
		}

		return $checkResult;
	}
}
