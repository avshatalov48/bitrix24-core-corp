<?php

namespace Bitrix\SalesCenter\Controller;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Sale\Internals\OrderPropsRelationTable;
use Bitrix\Sale\Delivery;
use Bitrix\SalesCenter\Delivery\Handlers\HandlersRepository;

class DeliverySelector extends \Bitrix\Main\Engine\Controller
{
	/**
	 * @param int $personTypeId
	 * @param int $responsibleId
	 * @return array
	 */
	public function getInitializationDataAction(int $personTypeId, int $responsibleId = 0)
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

		$services = [];
		foreach ($installedHandlers as $installedHandler)
		{
			foreach ($activeServices as $service)
			{
				if (
					(
						!$installedHandler->getProfileClass()
						&& $service['CLASS_NAME'] !== $installedHandler->getHandlerClass()
					)
					||
					(
						$installedHandler->getProfileClass()
						&& $service['CLASS_NAME'] !== $installedHandler->getProfileClass()
					)
				)
				{
					continue;
				}

				$name = $service['NAME'];
				if ($installedHandler->getProfileClass()
					&& $deliveryObj = Delivery\Services\Manager::createObject($service)
				)
				{
					$name = $deliveryObj->getNameWithParent();
				}

				$services[] = [
					'id' => $service['ID'],
					'name' => $name,
					'title' => $installedHandler->getTypeDescription(),
					'code' => $installedHandler->getCode(),
					'logo' => $installedHandler->getWorkingImagePath(),
				];
			}
		}

		$deliveryServiceIds = array_column($services, 'id');

		/**
		 * Installed delivery services
		 */
		$installedServices = [];
		$deliveryServices = Delivery\Services\Manager::getActiveList();

		foreach ($deliveryServices as $deliveryService)
		{
			$installedServices[] = [
				'id' => $deliveryService['ID'],
				'code' => $deliveryService['CODE'],
			];
		}

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
					'required' => ($relatedProperty['PROPERTY_REQUIRED'] == 'Y'),
					'initValue' => $relatedProperty['PROPERTY_DEFAULT_VALUE'],
					'settings' => empty($relatedProperty['PROPERTY_SETTINGS']) ? null : $relatedProperty['PROPERTY_SETTINGS'],
					'name' => $relatedProperty['PROPERTY_NAME'],
					'deliveryServiceIds' => [$relatedProperty['ENTITY_ID']],
				];
			}
		}

		/**
		 * Related extra services
		 */
		$extraServices = [];
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
			if ($type == 'dropdown' && isset($extraService['PARAMS']['PRICES']) && is_array($extraService['PARAMS']['PRICES']))
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

			$extraServices[] = [
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

		return [
			'services' => $services,
			'properties' => array_values($properties),
			'extraServices' => $extraServices,
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
