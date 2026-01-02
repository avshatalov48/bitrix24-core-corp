<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Controller\WaitList;

use Bitrix\Booking\Command\WaitListItem\UpdateWaitListItemCommand;
use Bitrix\Booking\Entity\Client\ClientCollection;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Provider\WaitListItemProvider;
use Bitrix\Booking\Rest\V1\Controller;
use Bitrix\Main\Engine\Response\DataType\Page;

class Client extends Controller
{
	private const ENTITY_ID = 'WAIT_LIST_CLIENT';
	private \Bitrix\Booking\Rest\V1\Factory\Entity\Client $clientFactory;
	private WaitListItemProvider $waitListItemProvider;

	public function init(): void
	{
		$this->clientFactory = new \Bitrix\Booking\Rest\V1\Factory\Entity\Client();
		$this->waitListItemProvider = new WaitListItemProvider();

		parent::init();
	}

	/**
	 * @restMethod booking.v1.waitlist.Client.list
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
			items: $this->convertToRestFields($waitListItem->getClientCollection()),
			totalCount: 0,
		);
	}

	/**
	 * @restMethod booking.v1.waitlist.Client.unset
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

		$waitListItem->setClientCollection(new ClientCollection());

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
	 * @restMethod booking.v1.waitlist.Client.set
	 */
	public function setAction(
		int $waitListId,
		array $clients,
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
				)
			);
		}

		$validationResult = $this->clientFactory->validateRestFieldsList($clients);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		/** @var ClientCollection $clientCollection */
		$clientCollection = $this->clientFactory->createCollectionFromRestFields($clients);
		$waitListItem->setClientCollection($clientCollection);

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
