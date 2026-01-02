<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Controller;

use Bitrix\Booking\Command\WaitListItem\AddWaitListItemCommand;
use Bitrix\Booking\Command\WaitListItem\CreateWaitListItemFromBookingCommand;
use Bitrix\Booking\Command\WaitListItem\RemoveWaitListItemCommand;
use Bitrix\Booking\Command\WaitListItem\UpdateWaitListItemCommand;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\Params\WaitListItem\WaitListItemFilter;
use Bitrix\Booking\Provider\Params\WaitListItem\WaitListItemSelect;
use Bitrix\Booking\Provider\WaitListItemProvider;
use Bitrix\Booking\Rest\V1\Controller;
use Bitrix\Booking\Rest\V1\Factory\Filter\CreatedWithin;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\UI\PageNavigation;

class WaitList extends Controller
{
	private const ENTITY_ID = 'WAIT_LIST';

	private WaitListItemProvider $waitListItemProvider;
	private \Bitrix\Booking\Rest\V1\Factory\Entity\WaitList $waitListFactory;

	public function init(): void
	{
		$this->waitListItemProvider = new WaitListItemProvider();
		$this->waitListFactory = new \Bitrix\Booking\Rest\V1\Factory\Entity\WaitList();

		parent::init();
	}

	/**
	 * @restMethod booking.v1.WaitList.get
	 */
	public function getAction(int $id): ?array
	{
		$waitListItem = $this->waitListItemProvider->getById($id, $this->getUserId());
		if (!$waitListItem)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					message: "Wait list not found",
					code: Exception::CODE_WAIT_LIST_ITEM_NOT_FOUND,
				)
			);
		}

		return [
			self::ENTITY_ID => $this->convertToRestFields($waitListItem)
		];
	}

	/**
	 * @restMethod booking.v1.WaitList.list
	 */
	public function listAction(
		PageNavigation $pageNavigation,
		array $filter = [],
	): ?Page
	{
		if (isset($filter['CREATED_WITHIN']))
		{
			$createdWithinFactory = new CreatedWithin();
			$validationResult = $createdWithinFactory->validateRestFields($filter['CREATED_WITHIN']);
			if (!$validationResult->isSuccess())
			{
				return $this->responseWithErrors($validationResult->getErrors());
			}

			$filter['CREATED_WITHIN'] = $createdWithinFactory->createFromRestFields($filter['CREATED_WITHIN']);
		}

		$waitListItemCollection =
			$this
				->waitListItemProvider
				->getList(
					new GridParams(
						limit: $pageNavigation->getLimit(),
						offset: $pageNavigation->getOffset(),
						filter: new WaitListItemFilter($filter),
						select: new WaitListItemSelect(['NOTE']),
					),
					userId: $this->getUserId(),
				)
		;

		return new Page(
			id: self::ENTITY_ID,
			items: $this->convertToRestFields($waitListItemCollection),
			totalCount: 0,
		);
	}

	/**
	 * @restMethod booking.v1.WaitList.add
	 */
	public function addAction(array $fields): ?int
	{
		$command = new AddWaitListItemCommand(
			createdBy: $this->getUserId(),
			waitListItem: $this->waitListFactory->createFromRestFields($fields),
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return
			$result
				->getWaitListItem()
				->getId()
			;
	}

	/**
	 * @restMethod booking.v1.WaitList.update
	 */
	public function updateAction(int $id, array $fields): ?bool
	{
		$waitList = $this->waitListItemProvider->getById($id, $this->getUserId());
		if (!$waitList)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					message: "Wait list not found",
					code: Exception::CODE_WAIT_LIST_ITEM_NOT_FOUND,
				)
			);
		}

		$command = new UpdateWaitListItemCommand(
			updatedBy: $this->getUserId(),
			waitListItem: $this->waitListFactory->createFromRestFields($fields, $waitList),
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}

	/**
	 * @restMethod booking.v1.WaitList.delete
	 */
	public function deleteAction(int $id): ?bool
	{
		$command = new RemoveWaitListItemCommand(
			id: $id,
			removedBy: $this->getUserId(),
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}

	/**
	 * @restMethod booking.v1.WaitList.createFromBooking
	 */
	public function createFromBookingAction(int $bookingId): ?int
	{
		$command = new CreateWaitListItemFromBookingCommand(
			bookingId: $bookingId,
			createdBy: $this->getUserId(),
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return
			$result
				->getWaitListItem()
				->getId()
			;
	}
}
