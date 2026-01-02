<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Service\Container;
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

		if (Container::getInstance()->getUserPermissions()->product()->canRead())
		{
			$checkResult->addError(new Error('Access Denied'));
		}

		return $checkResult;
	}
}
