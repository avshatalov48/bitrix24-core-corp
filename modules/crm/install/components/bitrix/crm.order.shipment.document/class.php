<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Component\ComponentError;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;

Loc::loadMessages(__FILE__);

class CCrmOrderShipmentDocumentComponent extends \CBitrixComponent
{
	public function executeComponent()
	{
		global $APPLICATION;
		//region Params
		$this->arResult['ENTITY_ID'] = $this->arParams['~ENTITY_ID'] ?? 0;

		$serviceContainer = Container::getInstance();
		$shipment = Crm\Order\Manager::getShipmentObject((int)$this->arResult['ENTITY_ID']);
		if (empty($shipment))
		{
			$checkPermissions = $serviceContainer
				->getUserPermissions()
				->entityType()
				->canAddItems(\CCrmOwnerType::Order)
			;
		}
		else
		{
			$checkPermissions = $serviceContainer
				->getUserPermissions()
				->item()
				->canUpdate(\CCrmOwnerType::Order, $shipment->getParentOrderId())
			;
		}

		if (!$checkPermissions)
		{
			ShowError(ComponentError::getMessage(ComponentError::PERMISSION_DENIED));

			return;
		}

		$APPLICATION->SetTitle(Loc::getMessage('CRM_ORDER_SHIPMENT_DOCUMENT_TITLE'));

		$this->arResult['GUID'] = $this->arParams['GUID'] ?? "order_shipment_document_{$this->arResult['ENTITY_ID']}";

		$this->arResult['EDITOR_CONFIG_ID'] = $this->arParams['EDITOR_CONFIG_ID'] ?? 'order_shipment_document_detail';

		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::OrderShipmentName.'_'.$this->arResult['ENTITY_ID'];

		$this->arResult['ENTITY_FIELDS'] = [
			[
				'name' => 'FIELDS',
				'type' => 'order_subsection',
				'required' => true,
				'elements' => [
					[
						'name' => 'TRACKING_NUMBER',
						'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_FIELD_TRACKING_NUMBER'),
						'type' => 'text',
						'editable' => true,
						'enabledMenu' => false,
						'transferable' => false,
					],
					[
						'name' => 'DELIVERY_DOC_NUM',
						'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_FIELD_DELIVERY_DOC_NUM'),
						'type' => 'text',
						'editable' => true,
						'enabledMenu' => false,
						'transferable' => false,
					],
					[
						'name' => 'DELIVERY_DOC_DATE',
						'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_FIELD_DELIVERY_DOC_DATE'),
						'type' => 'datetime',
						'editable' => true,
						'enabledMenu' => false,
						'transferable' => false,
						'data' => [
							'enableTime' => false,
						],
					],
				],
			],
		];

		$this->arResult['ENTITY_CONFIG'] = [
			[
				'name' => 'document',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_DOCUMENT_SUBTITLE'),
				'type' => 'section',
				'data' => [
					'showButtonPanel' => false,
				],
				'elements' => [
					[
						'name' => 'FIELDS',
					],
				],
			],
		];

		$this->arResult['ENTITY_DATA'] = [];
		if (!empty($shipment))
		{
			$entityData = $shipment->getFieldValues();
			if ($entityData['DELIVERY_DOC_DATE'])
			{
				$date = new Date($entityData['DELIVERY_DOC_DATE']);
				$entityData['DELIVERY_DOC_DATE'] = $date->toString();
			}

			$this->arResult['ENTITY_DATA'] = $entityData;
		}
		elseif (!empty($this->arParams['ENTITY_DATA']) && is_array($this->arParams['ENTITY_DATA']))
		{
			$this->arResult['ENTITY_DATA'] = $this->arParams['ENTITY_DATA'];
		}

		$this->includeComponentTemplate();
	}

	protected function prepareFieldInfos()
	{
		return $this->arResult['ENTITY_FIELDS'];
	}
}
