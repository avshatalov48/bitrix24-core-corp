<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Controller\Resource;

use Bitrix\Booking\Command\Resource\ResourceResult;
use Bitrix\Booking\Command\Resource\UpdateResourceCommand;
use Bitrix\Booking\Entity\Slot\RangeCollection;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Provider\ResourceProvider;
use Bitrix\Booking\Rest\V1\Controller;
use Bitrix\Booking\Rest\V1\Factory\Entity\Range;
use Bitrix\Main\Engine\Response\DataType\Page;

class Slots extends Controller
{
	private const ENTITY_ID = 'SLOTS';
	private Range $rangeFactory;
	private ResourceProvider $resourceProvider;

	public function init(): void
	{
		$this->rangeFactory = new Range();
		$this->resourceProvider = new ResourceProvider();

		parent::init();
	}

	/**
	 * @restMethod booking.v1.resource.Slots.list
	 */
	public function listAction(
		int $resourceId,
	): ?Page
	{
		$resource =
			$this
				->resourceProvider
				->getById(
					userId: $this->getUserId(),
					resourceId: $resourceId,
				)
		;
		if (!$resource)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					message: 'Resource not found',
					code: Exception::CODE_RESOURCE_NOT_FOUND,
				)
			);
		}

		return new Page(
			id: self::ENTITY_ID,
			items: $this->convertToRestFields($resource->getSlotRanges()),
			totalCount: 0,
		);
	}

	/**
	 * @restMethod booking.v1.resource.Slots.unset
	 */
	public function unsetAction(int $resourceId): ?bool
	{
		$resource =
			$this
				->resourceProvider
				->getById(
					userId: $this->getUserId(),
					resourceId: $resourceId,
				)
		;
		if (!$resource)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					message: 'Resource not found',
					code: Exception::CODE_RESOURCE_NOT_FOUND,
				),
			);
		}

		$resource->setSlotRanges(new RangeCollection());

		$command = new UpdateResourceCommand(
			updatedBy: $this->getUserId(),
			resource: $resource,
		);

		/** @var ResourceResult $result */
		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}

	/**
	 * @restMethod booking.v1.resource.Slots.set
	 */
	public function setAction(
		int $resourceId,
		array $slots,
	): ?bool
	{
		$resource =
			$this
				->resourceProvider
				->getById(
					userId: $this->getUserId(),
					resourceId: $resourceId,
				)
		;
		if (!$resource)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					message: 'Resource not found',
					code: Exception::CODE_RESOURCE_NOT_FOUND,
				)
			);
		}

		$validationResult = $this->rangeFactory->validateRestFieldsList($slots);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		$rangeCollection = $this->rangeFactory->createCollectionFromRestFields($slots, $resource);
		$resource->setSlotRanges($rangeCollection);

		$command = new UpdateResourceCommand(
			updatedBy: $this->getUserId(),
			resource: $resource,
		);

		/** @var ResourceResult $result */
		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}
}
