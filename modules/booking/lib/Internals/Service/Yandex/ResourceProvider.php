<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex;

use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Internals\Exception\Yandex\InternalErrorException;
use Bitrix\Booking\Internals\Exception\Yandex\ServiceNotFoundException;
use Bitrix\Booking\Internals\Integration\Catalog\ServiceSkuProvider;
use Bitrix\Booking\Internals\Model\Enum\ResourceLinkedEntityType;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\ResourceCollection;
use Bitrix\Booking\Provider\Params\Resource\ResourceFilter;
use Bitrix\Booking\Provider\Params\Resource\ResourceSelect;
use Bitrix\Booking\Internals\Service\Yandex;

class ResourceProvider
{
	public function __construct(
		private readonly CompanyRepository $companyRepository,
		private readonly ResourceRepositoryInterface $resourceRepository,
		private readonly ServiceSkuProvider $serviceSkuProvider
	)
	{
	}

	public function getResources(string $companyId, array $serviceIds = []): ResourceCollection
	{
		$result = new ResourceCollection();

		$company = $this->companyRepository->getById($companyId);
		if (!$company)
		{
			throw new InternalErrorException('Company not found');
		}

		$serviceIds = array_unique(array_map('intval', $serviceIds));
		if (!empty($serviceIds))
		{
			$skus = $this->serviceSkuProvider->get($serviceIds);
			if (count($skus) !== count($serviceIds))
			{
				throw new ServiceNotFoundException();
			}
		}

		$resourceCollection = $this->resourceRepository->getList(
			filter: (new ResourceFilter([
				'IS_MAIN' => true,
				'LINKED_ENTITY' => [
					'TYPE' => ResourceLinkedEntityType::Sku,
					'ID' => $serviceIds,
				],
			])),
			select: new ResourceSelect(),
		);

		/** @var Resource $resource */
		foreach ($resourceCollection as $resource)
		{
			if (
				$resource->getId() === null
				|| $resource->getName() === null
			)
			{
				continue;
			}

			$result
				->add(
					new Yandex\Dto\Item\Resource(
						(string)$resource->getId(),
						(string)$resource->getName(),
					)
				)
			;
		}

		return $result;
	}
}
