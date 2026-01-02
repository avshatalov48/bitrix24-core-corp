<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Controller;

use Bitrix\Booking\Command\Resource\AddResourceCommand;
use Bitrix\Booking\Command\Resource\RemoveResourceCommand;
use Bitrix\Booking\Command\Resource\ResourceResult;
use Bitrix\Booking\Command\Resource\UpdateResourceCommand;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\Params\Resource\ResourceFilter;
use Bitrix\Booking\Provider\Params\Resource\ResourceSort;
use Bitrix\Booking\Provider\ResourceProvider;
use Bitrix\Booking\Rest\V1\Controller;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\UI\PageNavigation;

class Resource extends Controller
{
	private const ENTITY_ID = 'RESOURCE';
	private \Bitrix\Booking\Rest\V1\Factory\Entity\Resource $resourceFactory;
	private ResourceProvider $resourceProvider;

	public function init(): void
	{
		$this->resourceFactory = new \Bitrix\Booking\Rest\V1\Factory\Entity\Resource();
		$this->resourceProvider = new ResourceProvider();

		parent::init();
	}

	/**
	 * @restMethod booking.v1.Resource.get
	 */
	public function getAction(int $id): ?array
	{
		$resource =
			$this
				->resourceProvider
				->getById(
					userId: $this->getUserId(),
					resourceId: $id,
				)
		;
		if (!$resource)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					'Resource not found',
					Exception::CODE_RESOURCE_NOT_FOUND,
				)
			);
		}

		$resourceRestFields = $this->convertToRestFields($resource);
		$resourceRestFields['TYPE_ID'] = $resource->getType()?->getId();

		return [
			self::ENTITY_ID => $resourceRestFields,
		];
	}

	/**
	 * @restMethod booking.v1.Resource.list
	 */
	public function listAction(
		PageNavigation $pageNavigation,
		array $filter = [],
		array $order = [],
	): Page
	{
		if (isset($order['NAME']))
		{
			$order['DATA.NAME'] = $order['NAME'];
			unset($order['NAME']);
		}

		$resourceCollection =
			$this
				->resourceProvider
				->getList(
					new GridParams(
						limit: $pageNavigation->getLimit(),
						offset: $pageNavigation->getOffset(),
						filter: new ResourceFilter($filter),
						sort: new ResourceSort($order),
					),
					userId: $this->getUserId()
				);

		$convertedRestFields = [];
		/** @var \Bitrix\Booking\Entity\Resource\Resource $resource */
		foreach ($resourceCollection as $resource)
		{
			$resourceRestFields = $this->convertToRestFields($resource);
			$resourceRestFields['TYPE_ID'] = $resource->getType()?->getId();

			$convertedRestFields[] = $resourceRestFields;
		}

		return new Page(
			id: self::ENTITY_ID,
			items: $convertedRestFields,
			totalCount: 0,
		);
	}

	/**
	 * @restMethod booking.v1.Resource.add
	 */
	public function addAction(array $fields): ?int
	{
		$validationResult = $this->resourceFactory->validateRestFields(
			fields: $fields,
			userId: $this->getUserId(),
		);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		$resource =
			$this
				->resourceFactory
				->createFromRestFields(
					fields: $fields,
					userId: $this->getUserId(),
				)
		;
		$command = new AddResourceCommand(
			createdBy: $this->getUserId(),
			resource: $resource,
		);

		/** @var ResourceResult $result */
		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return
			$result
				->getResource()
				->getId()
			;
	}

	/**
	 * @restMethod booking.v1.Resource.update
	 */
	public function updateAction(int $id, array $fields): ?bool
	{
		$validationResult = $this->resourceFactory->validateRestFields(
			fields: $fields,
			userId: $this->getUserId(),
		);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		$resource =
			$this
				->resourceProvider
				->getById(
					userId: $this->getUserId(),
					resourceId: $id,
				)
		;
		if (!$resource)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					'Resource not found',
					Exception::CODE_RESOURCE_UPDATE,
				)
			);
		}

		$resource =
			$this
				->resourceFactory
				->createFromRestFields(
					fields: [
						...$this->convertToRestFields($resource),
						...$fields,
					],
					userId: $this->getUserId(),
				)
		;

		$resource->setId($id);

		$command = new UpdateResourceCommand(
			updatedBy: $this->getUserId(),
			resource: $resource,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}

	/**
	 * @restMethod booking.v1.Resource.delete
	 */
	public function deleteAction(int $id): ?bool
	{
		$command = new RemoveResourceCommand($id, $this->getUserId());

		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}
}
