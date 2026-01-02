<?php

namespace Bitrix\HumanResources\Contract\Repository;

use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\HumanResources\Contract\Item;

/**
 * Interface for repository that fires events execution of witch should be controlled by higher level code
 */
interface EventableRepository
{
	/**
	 * Save item but don't send event, just put it in queue
	 *
	 * @param Item $item
	 * @return \Bitrix\HumanResources\Result\PropertyResult
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws UpdateFailedException
	 * @throws WrongStructureItemException
	 */
	public function updateWithEventQueue(Item $item): \Bitrix\HumanResources\Result\PropertyResult;

	/**
	 * Send queued events when transaction is completed
	 *
	 * @return void
	 */
	public function sendEventQueue(): void;

	/**
	 * Clear event queue in case transaction is unsuccessful
	 *
	 * @return void
	 */
	public function clearEventQueue(): void;
}