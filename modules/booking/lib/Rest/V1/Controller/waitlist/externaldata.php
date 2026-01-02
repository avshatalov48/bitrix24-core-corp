<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Controller\WaitList;

use Bitrix\Booking\Command\WaitListItem\UpdateWaitListItemCommand;
use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Provider\WaitListItemProvider;
use Bitrix\Booking\Rest\V1\Controller;
use Bitrix\Main\Engine\Response\DataType\Page;

class ExternalData extends Controller
{
	private const ENTITY_ID = 'EXTERNAL_DATA';
	private \Bitrix\Booking\Rest\V1\Factory\Entity\ExternalData $externalDataFactory;
	private WaitListItemProvider $waitListItemProvider;

	public function init(): void
	{
		$this->externalDataFactory = new \Bitrix\Booking\Rest\V1\Factory\Entity\ExternalData();
		$this->waitListItemProvider = new WaitListItemProvider();

		parent::init();
	}

	/**
	 * @restMethod booking.v1.waitlist.ExternalData.list
	 */
	public function listAction(
		int $waitListId,
	): ?Page
	{
		$waitListItem =
			$this
				->waitListItemProvider
				->getById(
					id: $waitListId,
					userId: $this->getUserId(),
				)
		;
		if (!$waitListItem)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					message: 'Wait list not found',
					code: Exception::CODE_WAIT_LIST_ITEM_NOT_FOUND,
				)
			);
		}

		return new Page(
			id: self::ENTITY_ID,
			items: $this->convertToRestFields($waitListItem->getExternalDataCollection()),
			totalCount: 0,
		);
	}

	/**
	 * @restMethod booking.v1.waitlist.ExternalData.unset
	 */
	public function unsetAction(int $waitListId): ?bool
	{
		$waitListItem =
			$this
				->waitListItemProvider
				->getById(
					id: $waitListId,
					userId: $this->getUserId(),
				)
		;
		if (!$waitListItem)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					message: 'Wait list not found',
					code: Exception::CODE_WAIT_LIST_ITEM_NOT_FOUND,
				)
			);
		}

		$waitListItem->setExternalDataCollection(new ExternalDataCollection());

		$command = new UpdateWaitListItemCommand(
			updatedBy: $this->getUserId(),
			waitListItem: $waitListItem,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}

	/**
	 * @restMethod booking.v1.waitlist.ExternalData.set
	 */
	public function setAction(
		int $waitListId,
		array $externalData,
	): ?bool
	{
		$waitListItem =
			$this
				->waitListItemProvider
				->getById(
					id: $waitListId,
					userId: $this->getUserId(),
				)
		;
		if (!$waitListItem)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					message: 'Wait list not found',
					code: Exception::CODE_WAIT_LIST_ITEM_NOT_FOUND,
				),
			);
		}

		/** @var ExternalDataCollection $externalDataCollection */
		$externalDataCollection = $this->externalDataFactory->createCollectionFromRestFields($externalData);
		$waitListItem->setExternalDataCollection($externalDataCollection);

		$command = new UpdateWaitListItemCommand(
			updatedBy: $this->getUserId(),
			waitListItem: $waitListItem,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}
}
