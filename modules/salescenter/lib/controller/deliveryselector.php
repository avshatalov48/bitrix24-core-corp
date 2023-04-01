<?php

namespace Bitrix\SalesCenter\Controller;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Sale\Internals\OrderPropsRelationTable;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\Repository\ShipmentRepository;
use Bitrix\SalesCenter\Delivery\Handlers\HandlersRepository;
use Bitrix\SalesCenter\Delivery\Handlers\IRestHandler;
use Bitrix\Voximplant\Security\Helper;

Loc::loadMessages(__FILE__);

class DeliverySelector extends Base
{
	/**
	 * @param int $personTypeId
	 * @param int $responsibleId
	 * @param array $excludedServiceIds
	 * @return array|null
	 */
	public function getInitializationDataAction(int $personTypeId, int $responsibleId = 0, array $excludedServiceIds = [])
	{
		if (!Loader::includeModule('sale'))
		{
			$this->addError(new Error('sale module is not installed'));
			return null;
		}

		$activeServices = Delivery\Services\Manager::getActiveList();
		$installedHandlers = (new HandlersRepository())
			->getCollection()
			->getInstalledItems();

		$flatServices = [];
		foreach ($installedHandlers as $installedHandler)
		{
			foreach ($activeServices as $service)
			{
				$isChild = false;
				if ($service['PARENT_ID']
					&& ($parentServiceFields = Delivery\Services\Manager::getById($service['PARENT_ID']))
				)
				{
					$serviceClassName = $parentServiceFields['CLASS_NAME'];
					$isChild = true;
				}
				else
				{
					$serviceClassName = $service['CLASS_NAME'];
				}

				if ($serviceClassName !== $installedHandler->getHandlerClass())
				{
					continue;
				}

				if (!$isChild && $installedHandler instanceof IRestHandler
					&& $installedHandler->getRestHandlerCode() !== $service['CONFIG']['MAIN']['REST_CODE']
				)
				{
					continue;
				}

				if (in_array($service['ID'], $excludedServiceIds))
				{
					continue;
				}

				$logoParams = \CFile::_GetImgParams($service['LOGOTIP']);
				$deliveryService = Delivery\Services\Manager::getObjectById($service['ID']);

				$flatServices[$service['ID']] = [
					'id' => $service['ID'],
					'name' => $service['NAME'],
					'description' => $service['DESCRIPTION'],
					'title' => $service['ID'] != Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId()
						? Loc::getMessage('SALESCENTER_CONTROLLER_DELIVERY_SELECTOR_DELIVERY_SERVICE')
						: '',

					'restrictions' => $this->makeServiceRestrictions($service['CODE']),
					'tags' => $deliveryService ? $deliveryService->getTags() : [],
					'code' => $isChild ? $service['CODE'] : $installedHandler->getCode(),
					'logo' => $logoParams
						? [
							'src' => $logoParams['SRC'],
							'width' => $logoParams['WIDTH'],
							'height' => $logoParams['HEIGHT'],
						]
						: null,
					'parentId' => (int)$service['PARENT_ID'],
					'profiles' => [],
				];
			}
		}

		$deliveryServiceIds = array_keys($flatServices);

		$services = array_filter(
			$flatServices,
			function ($service)
			{
				return $service['parentId'] === 0;
			}
		);
		foreach ($flatServices as $serviceId => $service)
		{
			if ($service['parentId'] === 0)
			{
				continue;
			}
			if (!isset($services[$service['parentId']]))
			{
				continue;
			}
			$services[$service['parentId']]['profiles'][] = $service;
		}

		$services = array_values($services);

		/**
		 * Related properties
		 */
		$properties = [];
		$supportedPropertyTypes = ['ADDRESS', 'STRING'];

		$relatedProperties = OrderPropsRelationTable::getList(
			[
				'filter' => [
					'ENTITY_TYPE' => 'D',
					'ENTITY_ID' => $deliveryServiceIds,
					'lPROPERTY.TYPE' => $supportedPropertyTypes,
					'lPROPERTY.PERSON_TYPE_ID' => $personTypeId,
				],
				'select' => [
					'*',
					'PROPERTY_PERSON_TYPE_ID' => 'lPROPERTY.PERSON_TYPE_ID',
					'PROPERTY_TYPE' => 'lPROPERTY.TYPE',
					'PROPERTY_DEFAULT_VALUE' => 'lPROPERTY.DEFAULT_VALUE',
					'PROPERTY_SETTINGS' => 'lPROPERTY.SETTINGS',
					'PROPERTY_CODE' => 'lPROPERTY.CODE',
					'PROPERTY_REQUIRED' => 'lPROPERTY.REQUIRED',
					'PROPERTY_NAME' => 'lPROPERTY.NAME',
					'PROPERTY_IS_ADDRESS_FROM' => 'lPROPERTY.IS_ADDRESS_FROM',
					'PROPERTY_IS_ADDRESS_TO' => 'lPROPERTY.IS_ADDRESS_TO',
				]
			]
		);
		foreach ($relatedProperties as $relatedProperty)
		{
			if (isset($properties[$relatedProperty['PROPERTY_ID']]))
			{
				$properties[$relatedProperty['PROPERTY_ID']]['deliveryServiceIds'][] = $relatedProperty['ENTITY_ID'];
			}
			else
			{
				$properties[$relatedProperty['PROPERTY_ID']] = [
					'id' => $relatedProperty['PROPERTY_ID'],
					'code' => $relatedProperty['PROPERTY_CODE'],
					'type' => $relatedProperty['PROPERTY_TYPE'],
					'isAddressFrom' => $relatedProperty['PROPERTY_IS_ADDRESS_FROM'] === 'Y',
					'isAddressTo' => $relatedProperty['PROPERTY_IS_ADDRESS_TO'] === 'Y',
					'required' => $relatedProperty['PROPERTY_REQUIRED'] === 'Y',
					'initValue' => $relatedProperty['PROPERTY_DEFAULT_VALUE'],
					'settings' => empty($relatedProperty['PROPERTY_SETTINGS']) ? null : $relatedProperty['PROPERTY_SETTINGS'],
					'name' => $relatedProperty['PROPERTY_NAME'],
					'deliveryServiceIds' => [$relatedProperty['ENTITY_ID']],
				];
			}
		}

		return [
			'services' => $services,
			'properties' => array_values($properties),
			'extraServices' => $this->getExtraServices($deliveryServiceIds),
			'deliverySettingsUrl' => $this->getDeliverySettingsUrl(),
			'responsible' => $this->getResponsibleData($responsibleId),
			'userPageTemplate' => Option::get(
					'socialnetwork',
					'user_page',
					SITE_DIR.'company/personal/',
					SITE_ID
				).'user/#user_id#/',
		];
	}

	public function getShipmentDataAction(int $id)
	{
		if (!Loader::includeModule('sale'))
		{
			$this->addError(new Error('sale module is not installed'));
			return null;
		}

		$shipment = ShipmentRepository::getInstance()->getById($id);
		if (!$shipment)
		{
			$this->addError(new Error('shipment not found'));
			return null;
		}

		$parentDeliveryService = null;
		$extraServiceDisplayValues = [];

		$deliveryService = $shipment->getDelivery();
		if ($deliveryService)
		{
			$deliveryServiceName = $deliveryService->getName();
			$deliveryServiceLogo = $deliveryService->getLogotipPath();

			$parentDeliveryService = $deliveryService->getParentService();

			$extraServiceInstances = $shipment->getExtraServicesObjects();
			foreach ($extraServiceInstances as $extraServiceInstance)
			{
				$extraServiceDisplayValues[] = [
					'name' => $extraServiceInstance->getName(),
					'value' => $extraServiceInstance->getDisplayValue(),
				];
			}
		}

		$deliveryRequest = null;
		$deliveryRequestId = Delivery\Requests\Manager::getRequestIdByShipmentId($shipment->getId());
		if ($deliveryRequestId)
		{
			$deliveryRequest = Delivery\Requests\RequestTable::getRowById($deliveryRequestId);
		}

		return [
			'shipment' => [
				'deliveryService' => [
					'name' => $deliveryServiceName ?? $shipment->getDeliveryName(),
					'logo' => $deliveryServiceLogo ?? null,
					'parent' => $parentDeliveryService
						? [
							'name' => $parentDeliveryService->getName(),
							'logo' => $parentDeliveryService->getLogotipPath(),
						]
						: null,
				],
				'priceDelivery' => $shipment->getField('PRICE_DELIVERY'),
				'basePriceDelivery' => $shipment->getField('BASE_PRICE_DELIVERY'),
				'currency' => $shipment->getCurrency(),
				'extraServices' => $extraServiceDisplayValues,
				'requestProperties' =>
					(
						isset($deliveryRequest['EXTERNAL_PROPERTIES'])
						&& is_array($deliveryRequest['EXTERNAL_PROPERTIES'])
					)
						? array_map(
							static function (array $property)
							{
								return [
									'name' => $property['NAME'] ?? null,
									'value' => $property['VALUE'] ?? null,
									'tags' => $property['TAGS'] ?? null,
								];
							},
							$deliveryRequest['EXTERNAL_PROPERTIES']
						)
						: []
				,
			],
			'canUserPerformCalls' => (
				Loader::includeModule('voximplant')
				&& Helper::canCurrentUserPerformCalls()
			),
		];
	}

	/**
	 * @param array $deliveryServiceIds
	 * @return array
	 */
	private function getExtraServices(array $deliveryServiceIds): array
	{
		//@TODO extract to API

		$result = [];

		$dbExtraServices = \Bitrix\Sale\Delivery\ExtraServices\Table::getList(
			[
				'filter' => ['DELIVERY_ID' => $deliveryServiceIds],
				'select' => [
					'*',
					'DELIVERY_SERVICE_CODE' => 'DELIVERY_SERVICE.CODE',
				]
			]
		)->fetchAll();

		$knownClassesMap = [
			'\\' . \Bitrix\Sale\Delivery\ExtraServices\Enum::class => 'dropdown',
			'\\' . \Bitrix\Sale\Delivery\ExtraServices\Checkbox::class => 'checkbox',
		];

		foreach ($dbExtraServices as $extraService)
		{
			if (!isset($knownClassesMap[$extraService['CLASS_NAME']]))
			{
				continue;
			}

			$type = $knownClassesMap[$extraService['CLASS_NAME']];

			$options = [];
			if ($type === 'dropdown' && isset($extraService['PARAMS']['PRICES']) && is_array($extraService['PARAMS']['PRICES']))
			{
				foreach ($extraService['PARAMS']['PRICES'] as $id => $paramItem)
				{
					$options[] = [
						'id' => $id,
						'title' => $paramItem['TITLE'],
						'code' => $paramItem['CODE'],
						'price' => $paramItem['PRICE'],
					];
				}
			}

			$result[] = [
				'id' => $extraService['ID'],
				'deliveryServiceCode' => $extraService['DELIVERY_SERVICE_CODE'],
				'code' => $extraService['CODE'],
				'name' => $extraService['NAME'],
				'type' => $type,
				'initValue' => $extraService['INIT_VALUE'],
				'options' => $options,
				'deliveryServiceIds' => [$extraService['DELIVERY_ID']],
			];
		}

		return $result;
	}

	/**
	 * @param string|null $serviceCode
	 * @return array
	 */
	private function makeServiceRestrictions(?string $serviceCode): array
	{
		$restrictions = [];

		$i = 0;
		do
		{
			$restriction = Loc::getMessage(
				sprintf(
					'SALESCENTER_CONTROLLER_DELIVERY_SELECTOR_%s_RESTRICTIONS_%s',
					$serviceCode,
					$i
				)
			);
			if ($restriction)
			{
				$restrictions[] = $restriction;
			}

			$i++;

		} while($restriction);

		return $restrictions;
	}

	/**
	 * @param int $responsibleId
	 * @return null|array
	 */
	private function getResponsibleData(int $responsibleId)
	{
		if (!$responsibleId)
		{
			return null;
		}

		$user = \CUser::getById($responsibleId)->fetch();
		if (!$user)
		{
			return null;
		}

		return [
			'id' => $user['ID'],
			'name' => trim(sprintf('%s %s', $user['NAME'], $user['LAST_NAME'])),
			'photo' => $user['PERSONAL_PHOTO'] ? \CFile::getPath($user['PERSONAL_PHOTO']) : null,
		];
	}

	/**
	 * @return string
	 */
	private function getDeliverySettingsUrl()
	{
		$deliverySettingsComponentPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.delivery.panel');

		return (new Uri(
			getLocalPath('components' . $deliverySettingsComponentPath . '/slider.php')
		))->addParams(
			[
				'analyticsLabel' => 'salescenterClickDeliveryTile',
				'type' => 'main',
				'mode' => 'main',
			]
		)->getLocator();
	}
}
