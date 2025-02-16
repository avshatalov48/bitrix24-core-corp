<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\AdvertisingResourceType;

use Bitrix\Booking\Entity\ResourceType\ResourceType;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Query\ResourceType\ResourceTypeFilter;
use Bitrix\Booking\Internals\Repository\AdvertisingResourceTypeRepository;
use Bitrix\Booking\Internals\Repository\ResourceTypeRepositoryInterface;

final class GetListHandler
{
	private AdvertisingResourceTypeRepository $advertisingResourceTypeRepository;
	private ResourceTypeRepositoryInterface $resourceTypeRepository;

	public function __construct()
	{
		$this->advertisingResourceTypeRepository = Container::getAdvertisingTypeRepository();
		$this->resourceTypeRepository = Container::getResourceTypeRepository();
	}

	public function __invoke(): array
	{
		$result = [];

		$advertisingTypes = $this->advertisingResourceTypeRepository->getList();
		$advTypeToResourceTypeCodeMap = $this->getAdvTypeToResourceTypeCodeMap($advertisingTypes);

		$resourceTypeList = $this->resourceTypeRepository->getList(
			filter: new ResourceTypeFilter(['CODE' => array_values($advTypeToResourceTypeCodeMap)]),
		);

		foreach ($advertisingTypes as $advertisingType)
		{
			$relatedResource = null;
			$relatedResourceTypeCode = $advTypeToResourceTypeCodeMap[$advertisingType['code']] ?? null;
			if ($relatedResourceTypeCode)
			{
				$relatedResource = $resourceTypeList->findByCode(
					$relatedResourceTypeCode,
					ResourceType::INTERNAL_MODULE_ID
				);
			}

			$result[] = [
				'code' => $advertisingType['code'],
				'name' => $advertisingType['name'],
				'description' => $advertisingType['description'],
				'relatedResourceTypeId' => $relatedResource?->getId(),
			];
		}

		return $result;
	}

	private function getAdvTypeToResourceTypeCodeMap(array $advertisingTypes): array
	{
		$result = [];

		$advertisingTypes = $this->advertisingResourceTypeRepository->getList();
		foreach ($advertisingTypes as $advertisingType)
		{
			$result[$advertisingType['code']] = $advertisingType['resourceType']['code'];
		}

		return $result;
	}
}
