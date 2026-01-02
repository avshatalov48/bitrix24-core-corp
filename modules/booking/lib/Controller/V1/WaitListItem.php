<?php

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Command\WaitListItem\AddWaitListItemCommand;
use Bitrix\Booking\Command\WaitListItem\CreateWaitListItemFromBookingCommand;
use Bitrix\Booking\Command\WaitListItem\UpdateWaitListItemCommand;
use Bitrix\Booking\Command\WaitListItem\RemoveWaitListItemCommand;
use Bitrix\Booking\Controller\V1\Filter\AllowByFeature;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Provider\WaitListItemProvider;
use Bitrix\Main\Engine\CurrentUser;

class WaitListItem extends BaseController
{
	/**
	 * @return \Bitrix\Main\Engine\ActionFilter\Base[]
	 */
	public function getDefaultPreFilters(): array
	{
		$prefilters = parent::getDefaultPreFilters();

		$prefilters[] = new AllowByFeature();

		return $prefilters;
	}

	public function addAction(CurrentUser $currentUser, array $waitListItem): Entity\WaitListItem\WaitListItem|null
	{
		try
		{
			/** @var Entity\WaitListItem\WaitListItem $waitListItemEntity */
			$waitListItemEntity = Entity\WaitListItem\WaitListItem::mapFromArray($waitListItem);
		}
		catch (Exception $exception)
		{
			$this->addError(ErrorBuilder::buildFromException($exception));

			return null;
		}

		$addWaitListItemCommand = new AddWaitListItemCommand(
			createdBy: (int)$currentUser->getId(),
			waitListItem: $waitListItemEntity,
		);

		$result = $addWaitListItemCommand->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getWaitListItem();
	}

	public function updateAction(CurrentUser $currentUser, array $waitListItem): Entity\WaitListItem\WaitListItem|null
	{
		if (empty($waitListItem['id']))
		{
			$this->addError(ErrorBuilder::build('Wait list item identifier is not specified.'));

			return null;
		}

		$entity = Container::getWaitListItemRepository()->getById((int)$waitListItem['id'], (int)$currentUser->getId());
		if (!$entity)
		{
			$this->addError(ErrorBuilder::build('Wait list item has not been found.'));

			return null;
		}

		try
		{
			/** @var Entity\WaitListItem\WaitListItem $waitListItemEntity */
			$waitListItemEntity = Entity\WaitListItem\WaitListItem::mapFromArray([
				...$entity->toArray(),
				...$waitListItem,
			]);
		}
		catch (Exception $exception)
		{
			$this->addError(ErrorBuilder::buildFromException($exception));

			return null;
		}

		$command = new UpdateWaitListItemCommand(
			updatedBy: (int)$currentUser->getId(),
			waitListItem: $waitListItemEntity,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getWaitListItem();
	}

	public function deleteAction(CurrentUser $currentUser, int $id): array|null
	{
		$command = new RemoveWaitListItemCommand(
			id: $id,
			removedBy: (int)$currentUser->getId(),
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getData();
	}

	public function deleteListAction(CurrentUser $currentUser, array $ids): array
	{
		$filteredIds = array_values(array_filter($ids, 'is_int'));

		if (count($filteredIds) !== count($ids))
		{
			$this->addError(ErrorBuilder::build('ids should contain only integers'));

			return [];
		}

		foreach ($filteredIds as $id)
		{
			$command = new RemoveWaitListItemCommand(
				id: $id,
				removedBy: (int)$currentUser->getId(),
			);

			$commandResult = $command->run();
			if (!$commandResult->isSuccess())
			{
				$this->addErrors($commandResult->getErrors());
			}
		}

		return [];
	}

	public function createFromBookingAction(
		CurrentUser $currentUser,
		int $bookingId
	): Entity\WaitListItem\WaitListItem|null
	{
		$command = new CreateWaitListItemFromBookingCommand(
			bookingId: $bookingId,
			createdBy: (int)$currentUser->getId(),
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getWaitListItem();
	}

	public function getAction(CurrentUser $currentUser, int $id): Entity\WaitListItem\WaitListItem|null
	{
		$waitListItem = null;

		try
		{
			$waitListItem = (new WaitListItemProvider())
				->getById(id: $id, userId: (int)$currentUser->getId())
			;
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

		}

		return $waitListItem;
	}
}
