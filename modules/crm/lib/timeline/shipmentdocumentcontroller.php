<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\Shipment;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Repository\ShipmentRepository;

Loc::loadMessages(__FILE__);

class ShipmentDocumentController extends EntityController
{
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::ShipmentDocument;
	}

	public function onCreate($ownerID, array $params)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if ($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		/* @var Shipment $shipment */
		$shipment = $params['SHIPMENT'];
		if (!$shipment)
		{
			return;
		}

		$total = $this->calculateTotalForShipment($shipment);

		$settings = [
			'TOTAL' => $total,
			'CURRENCY' => $this->getShipmentCurrency($shipment),
		];

		$authorID = self::resolveCreatorID($shipment->getFieldValues());
		$order = $params['ORDER'] ?? $shipment->getOrder();
		$bindings = $this->getDefaultBindings($ownerID, $order);
		$historyEntryID = StoreDocumentEntry::create([
			'TYPE_CATEGORY_ID' => TimelineType::CREATION,
			'ENTITY_TYPE_ID' => \CCrmOwnerType::ShipmentDocument,
			'ENTITY_ID' => $ownerID,
			'AUTHOR_ID' => $authorID,
			'SETTINGS' => $settings,
			'BINDINGS' => $bindings,
		]);

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new \Bitrix\Crm\ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$historyEntryID
			);
		}

		$this->onStatusModify($ownerID, $params);
	}

	public function onModify($entityID, array $params)
	{
		$shipment = $params['SHIPMENT'];
		if (!$shipment)
		{
			return;
		}

		$oldSum = $this->calculateTotalForShipment(ShipmentRepository::getInstance()->getById($entityID));
		$newSum = $this->calculateTotalForShipment($shipment);

		if ($oldSum !== $newSum)
		{
			$this->onTotalModify($entityID, $params);
		}

		$isStatusModified = $shipment->getFields()->isChanged('DEDUCTED');
		if ($isStatusModified)
		{
			ShipmentDocumentController::getInstance()->onStatusModify($shipment->getId(), $params);
		}
	}

	private function onTotalModify($documentId, $params)
	{
		/* @var Shipment $shipment */
		$shipment = $params['SHIPMENT'];
		if (!$shipment)
		{
			return;
		}
		$historyEntryID = null;

		$newTotal = $this->calculateTotalForShipment($shipment);
		$newCurrency = $this->getShipmentCurrency($shipment);

		$currentUserId = (int)\Bitrix\Main\Engine\CurrentUser::get()->getId();
		$authorID =  $currentUserId > 0 ? $currentUserId : 1;

		$order = $params['ORDER'] ?? $shipment->getOrder();
		$bindings = $this->getDefaultBindings($documentId, $order);

		$historyEntryID = StoreDocumentEntry::create(
			[
				'TYPE_CATEGORY_ID' => TimelineType::MODIFICATION,
				'ENTITY_TYPE_ID' => \CCrmOwnerType::ShipmentDocument,
				'ENTITY_ID' => $documentId,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => [
					'FIELD' => 'TOTAL',
					'TOTAL' => $newTotal,
					'CURRENCY' => $newCurrency,
				],
				'BINDINGS' => $bindings
			]
		);

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new \Bitrix\Crm\ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$historyEntryID
			);
		}
	}

	public function onStatusModify($documentId, $params)
	{
		$shipment = $params['SHIPMENT'];
		if (!$shipment)
		{
			return;
		}

		if (!$shipment->isShipped())
		{
			if ($shipment->getField('DATE_DEDUCTED'))
			{
				$newStageName = Loc::getMessage('STORE_DOCUMENT_STATUS_CANCELLED');
				$newStageClass = StoreDocumentStatusDictionary::CANCELLED;
			}
			else
			{
				$newStageName = Loc::getMessage('STORE_DOCUMENT_STATUS_DRAFT');
				$newStageClass = StoreDocumentStatusDictionary::DRAFT;
			}
		}
		else
		{
			$newStageName = Loc::getMessage('STORE_DOCUMENT_STATUS_CONDUCTED');
			$newStageClass = StoreDocumentStatusDictionary::CONDUCTED;
		}

		$authorID = self::resolveEditorID($shipment->getFieldValues());
		$order = $params['ORDER'] ?? $shipment->getOrder();
		$bindings = $this->getDefaultBindings($documentId, $order);

		$historyEntryID = StoreDocumentEntry::create(
			[
				'TYPE_CATEGORY_ID' => TimelineType::MODIFICATION,
				'ENTITY_TYPE_ID' => \CCrmOwnerType::ShipmentDocument,
				'ENTITY_ID' => $documentId,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => [
					'FIELD' => 'STATUS',
					'NEW_VALUE' => $newStageName,
					'CLASS' => $newStageClass,
					'TOTAL' => $this->calculateTotalForShipment($shipment),
					'CURRENCY' => $this->getShipmentCurrency($shipment),
				],
				'BINDINGS' => $bindings,
			]
		);

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new \Bitrix\Crm\ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$historyEntryID
			);
		}
	}

	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$data['ASSOCIATED_ENTITY']['DOC_TYPE'] = 'W';
		$data['ASSOCIATED_ENTITY']['TITLE'] = Loc::getMessage(
			'SHIPMENT_DOCUMENT_TITLE',
			[
				'%ACCOUNT_NUMBER%' => $data['ASSOCIATED_ENTITY']['TITLE']
			]
		);
		$data['TITLE_TEMPLATE'] = Loc::getMessage(
			'STORE_DOCUMENT_TITLE',
			[
				'#DATE#' => new Main\Type\Date($data['ASSOCIATED_ENTITY']['DATE_INSERT']),
			]
		);
		$data['TOTAL'] = $data['SETTINGS']['TOTAL'] ?? null;
		$data['CURRENCY'] = $data['SETTINGS']['CURRENCY'] ?? null;

		if (!empty($data['ASSOCIATED_ENTITY']['ID']))
		{
			$data['DETAIL_LINK'] = \CComponentEngine::MakePathFromTemplate(
				\COption::GetOptionString('crm', 'path_to_shipment_document_details'),
				[
					'shipment_document_id' => (int)$data['ASSOCIATED_ENTITY']['ID'],
				]
			);
		}

		if ((int)$data['TYPE_CATEGORY_ID'] === TimelineType::MODIFICATION)
		{
			$data['FIELD'] = $data['SETTINGS']['FIELD'] ?? '';
			if ($data['SETTINGS']['FIELD'] === 'STATUS')
			{
				$data['STATUS_TITLE'] = $data['SETTINGS']['NEW_VALUE'];
				$data['STATUS_CLASS'] = $data['SETTINGS']['CLASS'];
			}
			$data['MODIFIED_FIELD'] = $data['FIELD'];
		}

		return parent::prepareHistoryDataModel($data, $options);
	}

	protected static function resolveCreatorID(array $fields)
	{
		$responsibleId = (int)($fields['EMP_RESPONSIBLE_ID'] ?? 0);
		if ($responsibleId > 0)
		{
			return $responsibleId;
		}

		$currentUserId = (int)\Bitrix\Main\Engine\CurrentUser::get()->getId();
		if ($currentUserId > 0)
		{
			return $currentUserId;
		}

		return 1;
	}

	protected static function resolveEditorID(array $fields)
	{
		$deductedById = (int)($fields['EMP_DEDUCTED_ID'] ?? 0);
		if ($deductedById > 0)
		{
			return $deductedById;
		}

		$responsibleId = (int)($fields['EMP_RESPONSIBLE_ID'] ?? 0);
		if ($responsibleId > 0)
		{
			return $responsibleId;
		}

		$currentUserId = (int)\Bitrix\Main\Engine\CurrentUser::get()->getId();
		if ($currentUserId > 0)
		{
			return $currentUserId;
		}

		return 1;
	}

	private function getDefaultBindings($shipmentDocumentId, ?Order $order = null)
	{
		$bindings = [
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::ShipmentDocument,
				'ENTITY_ID' => $shipmentDocumentId,
			]
		];

		if (!$order)
		{
			return $bindings;
		}

		$orderBinding = $order->getEntityBinding();
		if (!($orderBinding && $orderBinding->getOwnerTypeId() === \CCrmOwnerType::Deal))
		{
			return $bindings;
		}

		$bindings[] = [
			'ENTITY_TYPE_ID' => $orderBinding->getOwnerTypeId(),
			'ENTITY_ID' => $orderBinding->getOwnerId(),
		];

		return $bindings;
	}

	private function calculateTotalForShipment(\Bitrix\Sale\Shipment $shipment)
	{
		$total = 0;
		/** @var \Bitrix\Crm\Order\ShipmentItem $shipmentItem */
		foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
		{
			$basketItem = $shipmentItem->getBasketItem();

			$total += $basketItem->getPrice() * $shipmentItem->getQuantity();
		}

		return $total;
	}

	private function getShipmentCurrency(\Bitrix\Sale\Shipment $shipment)
	{
		$currency = $shipment->getCurrency();
		if (!$currency)
		{
			$currency = $shipment->getOrder()->getCurrency();
		}

		return $currency;
	}
}
