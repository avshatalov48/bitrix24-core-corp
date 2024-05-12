<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Timeline;

use Bitrix\CrmMobile\Controller\BaseJson;
use Bitrix\Crm\Engine\ActionFilter\CheckReadMyCompanyPermission;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Repository;
use Bitrix\CrmMobile\Controller\PrimaryAutoWiredEntity;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Type\DateTime;

abstract class Controller extends BaseJson
{
	use PrimaryAutoWiredEntity;

	/**
	 * @return ExactParameter[]
	 */
	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				Repository::class,
				'repository',
				function($className, Item $entity)
				{
					if ($entity->isNew())
					{
						return new Repository\NullRepository();
					}

					$itemIdentifier = ItemIdentifier::createByItem($entity);
					$context = new Context($itemIdentifier,Context::MOBILE);
					return new Repository($context);
				}
			),
			new ExactParameter(
				Pagination::class,
				'pagination',
				function($className, int $offsetId = 0, ?string $offsetTime = null)
				{
					$offsetTime = $offsetTime === null ? $offsetTime : DateTime::createFromUserTime($offsetTime);
					return new Pagination($offsetId, $offsetTime);
				}
			),
		];
	}

	protected function getDefaultPreFilters(): array
	{
		return [
			...parent::getDefaultPreFilters(),
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\ContentType([ActionFilter\ContentType::JSON]),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
			new ActionFilter\CloseSession(),
			new CheckReadPermission(),
			new CheckReadMyCompanyPermission(),
		];
	}

	protected function isEntityEditable(Item $entity): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions();

		if ($entity->isNew())
		{
			$isEditable = $userPermissions->checkAddPermissions(
				$entity->getEntityTypeId(),
				$entity->getCategoryId()
			);
		}
		else
		{
			$isEditable = $userPermissions->checkUpdatePermissions(
				$entity->getEntityTypeId(),
				$entity->getId(),
				$entity->getCategoryId()
			);
		}

		return $isEditable;
	}
}
