<?php

namespace Bitrix\Crm\ItemMiniCard\Builder\Layout;

use Bitrix\Crm\Integration\OpenLineManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemMiniCard\Layout\Field\Value\Client;
use Bitrix\Crm\Multifield\Type\Email;
use Bitrix\Crm\Multifield\Type\Im;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Router;
use Bitrix\Crm\Service\UserPermissions;
use CCrmFieldMulti;
use CCrmOwnerType;
use CCrmViewHelper;

final class ClientBuilder
{
	private const AVAILABLE_TYPES = [
		Phone::ID,
		Email::ID,
		Im::ID,
	];

	private readonly Router $router;
	private readonly UserPermissions\EntityPermissions\Item $permissions;

	public function __construct(
		private readonly int $ownerTypeId,
		private readonly int $ownerId,
	)
	{
		$this->router = Container::getInstance()->getRouter();
		$this->permissions = Container::getInstance()->getUserPermissions()->item();
	}

	public function buildClient(Item $client): ?Client
	{
		if (!$this->permissions->canRead($client->getEntityTypeId(), $client->getId()))
		{
			return new Client(
				fullName: CCrmViewHelper::GetHiddenEntityCaption($client->getEntityTypeId()),
			);
		}

		$communications = [];
		foreach ($client->getFm()->getAll() as $multiField)
		{
			if (!in_array($multiField->getTypeId(), self::AVAILABLE_TYPES, true))
			{
				continue;
			}

			if (
				$multiField->getTypeId() === Im::ID
				&& !OpenLineManager::isImOpenLinesValue($multiField->getValue())
			)
			{
				continue;
			}

			$valueTypeCaption = CCrmFieldMulti::GetEntityTypes()[$multiField->getTypeId()][$multiField->getValueType()]['SHORT'] ?? '';

			$communications[] = new Client\Communication(
				$multiField->getTypeId(),
				$valueTypeCaption,
				$multiField->getValueType(),
				$multiField->getValue(),
			);
		}

		$entity = new Client\Entity(
			entityTypeId: $client->getEntityTypeId(),
			entityId: $client->getId(),
			ownerTypeId: $this->ownerTypeId,
			ownerId: $this->ownerId,
		);

		return new Client(
			$client->getHeading(),
			$this->router->getItemDetailUrl($client->getEntityTypeId(), $client->getId()),
			$entity,
			$communications,
		);
	}
}
