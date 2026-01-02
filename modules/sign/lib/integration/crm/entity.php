<?php

namespace Bitrix\Sign\Integration\CRM;

use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Service\Operation\Action\Compatible\SocialNetwork\ProcessSendNotification\WhenUpdatingEntity;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;
use Bitrix\Crm\Service\Context;

class Entity
{
	public static function getDetailPageUri(int $entityTypeId, int $entityId): ?Uri
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		return \Bitrix\Crm\Service\Container::getInstance()
			->getRouter()
			->getItemDetailUrl($entityTypeId, $entityId)
		;
	}

	public static function getContactName(int $entityId): ?string
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		return \Bitrix\Crm\Service\Container::getInstance()
			->getContactBroker()->getFormattedName($entityId)
		;
	}

	public static function updateEntityResponsible(int $entityTypeId, int $entityId, int $responsibleUserId): Result
	{
		$result = new Result();
		if (!Loader::includeModule('crm'))
		{
			return $result->addError(new Error('CRM module is not installed'));
		}

		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return $result->addError(new Error('Factory not found'));
		}

		$item = $factory->getItem($entityId);
		if (!$item)
		{
			return $result->addError(new Error('Item not found'));
		}

		$item->setAssignedById($responsibleUserId);
		$operation = $factory->getUpdateOperation($item);
		$operation->disableAllChecks();
		$operation->removeAction(Operation::ACTION_AFTER_SAVE, WhenUpdatingEntity::class);

		$context = clone \Bitrix\Crm\Service\Container::getInstance()->getContext();
		$context->setUserId($responsibleUserId);
		$context->setScope(Context::SCOPE_AUTOMATION);

		$operation->setContext($context);

		return $operation->launch();
	}

}