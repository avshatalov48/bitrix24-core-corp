<?php

namespace Bitrix\Crm\Security\Controller;

class OrderPermissionAttributesMaker
{
	public function make(int $userId): array
	{
		$result = ["U{$userId}"];

		$userAttributes = \Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($userId)
			->getAttributesProvider()
			->getEntityAttributes()
		;

		return array_merge($result, $userAttributes['INTRANET']);
	}
}
