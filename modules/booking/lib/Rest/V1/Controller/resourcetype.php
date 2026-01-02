<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Controller;

use Bitrix\Booking\Command\ResourceType\AddResourceTypeCommand;
use Bitrix\Booking\Command\ResourceType\RemoveResourceTypeCommand;
use Bitrix\Booking\Command\ResourceType\ResourceTypeResult;
use Bitrix\Booking\Command\ResourceType\UpdateResourceTypeCommand;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\Params\ResourceType\ResourceTypeFilter;
use Bitrix\Booking\Provider\Params\ResourceType\ResourceTypeSort;
use Bitrix\Booking\Provider\ResourceTypeProvider;
use Bitrix\Booking\Rest\V1\Controller;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Engine\Response\DataType\Page;

class ResourceType extends Controller
{
	private const ENTITY_ID = 'RESOURCE_TYPE';
	private \Bitrix\Booking\Rest\V1\Factory\Entity\ResourceType $resourceTypeFactory;
	private ResourceTypeProvider $resourceTypeProvider;

	public function init(): void
	{
		$this->resourceTypeFactory = new \Bitrix\Booking\Rest\V1\Factory\Entity\ResourceType();
		$this->resourceTypeProvider = new ResourceTypeProvider();

		parent::init();
	}

	/**
	 * @restMethod booking.v1.ResourceType.get
	 */
	public function getAction(int $id): ?array
	{
		$resourceType =
			$this
				->resourceTypeProvider
				->getById(
					userId: $this->getUserId(),
					id: $id,
				)
		;
		if (!$resourceType)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					'Resource type not found',
					Exception::CODE_RESOURCE_TYPE_NOT_FOUND,
				)
			);
		}

		return [
			self::ENTITY_ID => $this->convertToRestFields($resourceType),
		];
	}

	/**
	 * @restMethod booking.v1.ResourceType.list
	 */
	public function listAction(
		PageNavigation $pageNavigation,
		array $filter = [],
		array $order = [],
	): Page
	{
		$resourceTypeCollection =
			$this
				->resourceTypeProvider
				->getList(
					new GridParams(
						limit: $pageNavigation->getLimit(),
						offset: $pageNavigation->getOffset(),
						filter: new ResourceTypeFilter($filter),
						sort: new ResourceTypeSort($order),
					),
				userId: $this->getUserId(),
		);

		return new Page(
			id: self::ENTITY_ID,
			items: $this->convertToRestFields($resourceTypeCollection),
			totalCount: 0,
		);
	}

	/**
	 * @restMethod booking.v1.ResourceType.add
	 */
	public function addAction(array $fields): ?int
	{
		$validationResult = $this->resourceTypeFactory->validateRestFields($fields);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		$resourceTypeCode = $fields['CODE'];
		if (Container::getResourceTypeRepository()->getByModuleIdAndCode('booking', $resourceTypeCode)
		) {
			return $this->responseWithError(ErrorBuilder::build(
				"Resource type with code \"$resourceTypeCode\" already exists",
				Exception::CODE_RESOURCE_TYPE_CREATE,
			));
		}

		$resourceType = $this->resourceTypeFactory->createFromRestFields($fields);
		$command = new AddResourceTypeCommand(
			createdBy: $this->getUserId(),
			resourceType: $resourceType,
			rangeCollection: null,
		);

		/** @var ResourceTypeResult $result */
		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return
			$result
				->getResourceType()
				->getId()
			;
	}

	/**
	 * @restMethod booking.v1.ResourceType.update
	 */
	public function updateAction(int $id, array $fields): ?bool
	{
		$validationResult = $this->resourceTypeFactory->validateRestFields($fields);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		$resourceTypeCode = $fields['CODE'];
		if (Container::getResourceTypeRepository()->getByModuleIdAndCode('booking', $resourceTypeCode)
		) {
			return $this->responseWithError(ErrorBuilder::build(
				"Resource type with code \"$resourceTypeCode\" already exists",
				Exception::CODE_RESOURCE_TYPE_UPDATE,
			));
		}

		$resourceType =
			$this
				->resourceTypeProvider
				->getById(
					userId: $this->getUserId(),
					id: $id,
				)
		;
		if (!$resourceType)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					'Resource type not found',
					Exception::CODE_RESOURCE_UPDATE,
				)
			);
		}

		$resourceType =
			$this
				->resourceTypeFactory
				->createFromRestFields(
					[
						...$this->convertToRestFields($resourceType),
						...$fields,
					],
				)
		;

		$resourceType->setId($id);

		$command = new UpdateResourceTypeCommand(
			updatedBy: $this->getUserId(),
			resourceType: $resourceType,
			rangeCollection: null,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}

	/**
	 * @restMethod booking.v1.ResourceType.delete
	 */
	public function deleteAction(int $id): ?bool
	{
		$command = new RemoveResourceTypeCommand($id, $this->getUserId());

		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}
}
