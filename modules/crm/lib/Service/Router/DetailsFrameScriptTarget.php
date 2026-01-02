<?php

namespace Bitrix\Crm\Service\Router;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;

final class DetailsFrameScriptTarget
{
	public function __construct(
		protected int $entityTypeId,
		protected int $entityId = 0,
	)
	{
	}

	public function isAvailable(): bool
	{
		if (!\CCrmOwnerType::IsDefined($this->entityTypeId))
		{
			return false;
		}

		$router = Container::getInstance()->getRouter();
		$itemDetailsUrl = $router->getItemDetailUrl(
			$this->getEntityTypeId(),
			$this->getEntityId(),
			$this->getCategoryId(),
		);

		/** @see \CCrmViewHelper::getDetailFrameWrapperScript exclude fatal when $url === null */
		return $itemDetailsUrl !== null;
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getEntityId(): int
	{
		return $this->entityId;
	}

	public function getCategoryId(): ?int
	{
		if ($this->getEntityId() === 0)
		{
			$values = Application::getInstance()->getContext()->getRequest()->getValues();

			return isset($values['categoryId']) ? (int)$values['categoryId'] : null;
		}

		$factory = Container::getInstance()->getFactory($this->entityTypeId);

		return $factory?->getItem($this->entityId)?->getCategoryIdForPermissions();
	}
}
