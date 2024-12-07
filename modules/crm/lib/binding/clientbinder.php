<?php

namespace Bitrix\Crm\Binding;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class ClientBinder
{
	public function bind(Factory $factory, Item $entity, ItemIdentifier $client): Result
	{
		$result = new Result();

		if (!Container::getInstance()->getUserPermissions()->canUpdateItem($entity))
		{
			return $result->addError(new Error(Loc::getMessage('CRM_ACCESS_DENIED')));
		}

		$clientTypeId = $client->getEntityTypeId();
		$clientId = $client->getEntityId();

		if ($clientTypeId === \CCrmOwnerType::Contact)
		{
			$contact = Container::getInstance()->getFactory(\CCrmOwnerType::Contact)->getItem($clientId);
			if (!$contact)
			{
				return $result->addError(new Error(Loc::getMessage('CRM_CLIENT_BINDER_WRONG_CONTACT')));
			}

			$bindings = EntityBinding::prepareEntityBindings($clientTypeId, [$clientId]);
			$entity->bindContacts($bindings);
		}
		else if ($clientTypeId === \CCrmOwnerType::Company)
		{
			$company = Container::getInstance()->getFactory(\CCrmOwnerType::Company)->getItem($clientId);
			if (!$company)
			{
				return $result->addError(new Error(Loc::getMessage('CRM_CLIENT_BINDER_WRONG_COMPANY')));
			}

			$entity->setCompanyId($clientId);
		}

		$saveResult = $factory->getUpdateOperation($entity)->launch();

		if ($saveResult->isSuccess())
		{
			return $saveResult;
		}

		$errors = $saveResult->getErrorCollection()->getValues();
		if (empty($errors))
		{
			return $result->addError(new Error(Loc::getMessage('CRM_CLIENT_BINDER_CLIENT_BIND_ERROR')));
		}

		return $result->addErrors($errors);
	}
}
