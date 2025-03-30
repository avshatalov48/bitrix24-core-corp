<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\ResourceType\ResourceType;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Internals\Repository\AdvertisingResourceTypeRepository;
use Bitrix\Booking\Internals\Repository\ResourceTypeRepositoryInterface;
use Bitrix\Booking\Provider\Params\ResourceType\ResourceTypeFilter;

class AdsProvider
{
	private AdvertisingResourceTypeRepository $advertisingResourceTypeRepository;
	private ResourceTypeRepositoryInterface $resourceTypeRepository;

	public function __construct()
	{
		$this->advertisingResourceTypeRepository = Container::getAdvertisingTypeRepository();
		$this->resourceTypeRepository = Container::getResourceTypeRepository();
	}

	public function getAdsResourceTypes(): array
	{
		$result = [];

		$advertisingTypes = $this->advertisingResourceTypeRepository->getList();
		$advTypeToResourceTypeCodeMap = $this->getAdvTypeToResourceTypeCodeMap($advertisingTypes);
		$resourceTypeFilter = (new ResourceTypeFilter([
			'MODULE_ID' => 'booking',
			'CODE' => array_values($advTypeToResourceTypeCodeMap)
		]))->prepareFilter();

		$resourceTypeList = $this->resourceTypeRepository->getList(filter: $resourceTypeFilter);

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

		foreach ($advertisingTypes as $advertisingType)
		{
			$result[$advertisingType['code']] = $advertisingType['resourceType']['code'];
		}

		return $result;
	}
}
