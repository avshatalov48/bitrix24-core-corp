<?php

namespace Bitrix\CrmMobile\Controller\ReceivePayment;

use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\CrmMobile\Controller\PrimaryAutoWiredEntity;
use Bitrix\CrmMobile\Controller\PublicErrorsTrait;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\JsonController;

class Base extends JsonController
{
	use PrimaryAutoWiredEntity;
	use PublicErrorsTrait;

	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\ContentType([ActionFilter\ContentType::JSON]),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
			new CheckReadPermission(),
		];
	}

	protected function getOrderId(Item $entity): ?int
	{
		$relation = Container::getInstance()->getRelationManager()
			->getRelation(
				new RelationIdentifier(
					$entity->getEntityTypeId(),
					\CCrmOwnerType::Order
				)
			)
		;
		if (!$relation)
		{
			return null;
		}

		$result = null;

		$orderIdentifiers = $relation->getChildElements(
			new ItemIdentifier(
				$entity->getEntityTypeId(),
				$entity->getId()
			)
		);
		foreach ($orderIdentifiers as $orderIdentifier)
		{
			$result = $orderIdentifier->getEntityId();
		}

		return $result;
	}
}
