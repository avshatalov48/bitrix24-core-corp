<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Loader;

Loader::requireModule('crm');

trait PrimaryAutoWiredEntity
{
	public function getPrimaryAutoWiredParameter(): ExactParameter
	{
		return new ExactParameter(
			Item::class,
			'entity',
			function ($className, Factory $factory, ?int $entityId = null, ?int $categoryId = null) {
				if ($entityId)
				{
					$entity = $factory->getItem($entityId);
					if (!$entity)
					{
						$this->addError(ErrorCode::getNotFoundError());
					}
				}
				else
				{
					$entity = $factory->createItem();

					if ($categoryId !== null && $entity->isCategoriesSupported())
					{
						$entity->setCategoryId($categoryId);
					}
				}

				return $entity;
			}
		);
	}
}
