<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;
use Bitrix\Main\ORM\Objectify\Values;

class Observers extends Field
{
	/** @var \Bitrix\Crm\Integration\Im\Chat */
	protected $integrationClassName = \Bitrix\Crm\Integration\Im\Chat::class;

	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$previousObservers = $itemBeforeSave->remindActual($this->getName());
		$currentObservers = $item->get($this->getName());

		$addedObservers = array_diff($currentObservers, $previousObservers);
		$removedObservers = array_diff($previousObservers, $currentObservers);

		$this->integrationClassName::onEntityModification(
			$item->getEntityTypeId(),
			$item->getId(),
			[
				'CURRENT_FIELDS' => $item->getData(),
				'PREVIOUS_FIELDS' => $itemBeforeSave->getData(Values::ACTUAL),
				'ADDED_OBSERVER_IDS' => $addedObservers,
				'REMOVED_OBSERVER_IDS' => $removedObservers,
			]
		);

		return parent::processAfterSave($itemBeforeSave, $item, $context);
	}
}