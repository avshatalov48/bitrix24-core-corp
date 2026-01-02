<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper;

use Bitrix\Booking\Entity\WaitListItem\WaitListItem;
use Bitrix\Booking\Internals\Model\EO_WaitListItem;
use Bitrix\Booking\Internals\Model\WaitListItemTable;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\Trait\ClientTrait;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\Trait\ExternalDataTrait;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\Trait\NoteTrait;
use Bitrix\Main\Type\DateTime;

class WaitListItemMapper
{
	use ClientTrait;
	use ExternalDataTrait;
	use NoteTrait;

	public function __construct(
		ClientMapper $clientMapper,
		ExternalDataItemMapper $externalDataItemMapper
	)
	{
		$this->clientMapper = $clientMapper;
		$this->externalDataItemMapper = $externalDataItemMapper;
	}

	public function convertFromOrm(EO_WaitListItem $entity): WaitListItem
	{
		$waitListItem = (new WaitListItem())
			->setId($entity->getId())
			->setCreatedBy($entity->getCreatedBy())
			->setCreatedAt($entity->getCreatedAt()->getTimestamp())
			->setUpdatedAt($entity->getUpdatedAt()->getTimestamp())
			->setDeleted($entity->getIsDeleted())
		;

		$this->setClientCollection($waitListItem, $entity);
		$this->setExternalDataCollection($waitListItem, $entity);
		$this->setNote($waitListItem, $entity);

		return $waitListItem;
	}

	public function convertToOrm(WaitListItem $waitListItem): EO_WaitListItem
	{
		$result = $waitListItem->getId()
			? EO_WaitListItem::wakeUp($waitListItem->getId())
			: WaitListItemTable::createObject();

		$result
			->setCreatedBy($waitListItem->getCreatedBy())
			->setUpdatedAt(new DateTime())
		;

		return $result;
	}

	protected function getClientMapper(): ClientMapper
	{
		return $this->clientMapper;
	}

	protected function getExternalDataItemMapper(): ExternalDataItemMapper
	{
		return $this->externalDataItemMapper;
	}
}
