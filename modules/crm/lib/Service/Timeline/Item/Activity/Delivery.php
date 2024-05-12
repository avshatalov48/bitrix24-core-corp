<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\Payload;
use Bitrix\Crm\Service\Timeline\Layout\Action\CallRestBatch;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Action\RunAjaxAction;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EditableDate;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Money;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Body\Logo;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Action\Animation;
use Bitrix\Crm\Activity\Provider;
use Bitrix\Sale;
use Bitrix\Voximplant;

Loader::requireModule('sale');

class Delivery extends Activity
{
	private ?array $deliveryInfo = null;

	protected function getActivityTypeId(): string
	{
		return 'Delivery';
	}

	public function getTitle(): ?string
	{
		if ($this->isScheduled())
		{
			return Loc::getMessage('CRM_TIMELINE_ACTIVITY_DELIVERY_DELIVERY');
		}

		return Loc::getMessage('CRM_TIMELINE_ACTIVITY_DELIVERY_DELIVERY_COMPLETED');
	}

	public function getIconCode(): ?string
	{
		return 'taxi';
	}

	public function getContentBlocks(): ?array
	{
		$deliveryService = $this->getDeliveryService();
		$deliveryRequest = $this->getDeliveryRequest();
		$deliveryShipments = $this->getDeliveryShipments();
		// multiple shipments are not supported
		if (count($deliveryShipments) !== 1)
		{
			return [];
		}
		$deliveryShipment = $deliveryShipments[0];

		$result = [];

		if (
			$this->isPlanned()
			&& $this->getDeadline()
			&& $this->isScheduled()
			&& (
				!$deliveryRequest
				|| (int)$deliveryRequest['STATUS'] !== Sale\Delivery\Requests\Manager::STATUS_PROCESSED
			)
		)
		{
			$deadLineAction =
				$this->isScheduled()
					? (new RunAjaxAction('crm.timeline.activity.setDeadline'))
						->addActionParamInt('activityId', $this->getActivityId())
						->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
						->addActionParamInt('ownerId', $this->getContext()->getEntityId())
					: null
			;

			$result['deadline'] =
				(new ContentBlockWithTitle())
					->setTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_DELIVERY_COMPLETE_UNTIL'))
					->setInline(true)
					->setFixedWidth(false)
					->setContentBlock(
						(new EditableDate())
							->setStyle(EditableDate::STYLE_PILL)
							->setDate($this->getDeadline())
							->setAction($deadLineAction)
							->setBackgroundColor(
								$this->isScheduled()
									? EditableDate::BACKGROUND_COLOR_WARNING
									: null
							)
					)
			;
		}

		if ($deliveryService)
		{
			$result['deliveryServiceName'] =
				(new ContentBlockWithTitle())
					->setTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_DELIVERY_SERVICE_NAME'))
					->setContentBlock(
						(new Text())
							->setValue($this->getDeliveryServiceName())
							->setColor(Text::COLOR_BASE_90)
					)
			;
		}

		if (
			isset($deliveryShipment['PRICE_DELIVERY'])
			&& isset($deliveryShipment['CURRENCY'])
		)
		{
			$result['priceDelivery'] =
				(new ContentBlockWithTitle())
					->setTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_DELIVERY_AMOUNT_TO_PAY'))
					->setContentBlock(
						(new Money())
							->setOpportunity((float)$deliveryShipment['PRICE_DELIVERY'])
							->setCurrencyId((string)$deliveryShipment['CURRENCY'])
							->setColor(Text::COLOR_BASE_90)
					)
			;
		}

		if (
			isset($deliveryRequest['EXTERNAL_PROPERTIES'])
			&& is_array($deliveryRequest['EXTERNAL_PROPERTIES'])
		)
		{
			foreach ($deliveryRequest['EXTERNAL_PROPERTIES'] as $index => $externalProperty)
			{
				if (!isset($externalProperty['VALUE']))
				{
					continue;
				}

				$action = null;
				if (
					isset($externalProperty['TAGS'])
					&& is_array($externalProperty['TAGS'])
					&& in_array('phone', $externalProperty['TAGS'], true)
				)
				{
					$action =
						(new JsEvent('Delivery:MakeCall'))
							->addActionParamString(
								'phoneNumber',
								(string)$externalProperty['VALUE']
							)
							->addActionParamBoolean(
								'canUserPerformCalls',
								(
									Loader::includeModule('voximplant')
									&& Voximplant\Security\Helper::canCurrentUserPerformCalls()
								)
							)
					;
				}

				$result['deliveryRequestProperty' . $index] =
					(new ContentBlockWithTitle())
						->setTitle((string)$externalProperty['NAME'])
						->setContentBlock(
							$action
								? (new Link())
									->setValue((string)$externalProperty['VALUE'])
									->setAction($action)
								: (new Text())
									->setValue((string)$externalProperty['VALUE'])
									->setColor(Text::COLOR_BASE_90)
						)
				;
			}
		}

		if (isset($deliveryShipment['ADDRESS_FROM_FORMATTED']))
		{
			$result['addressFrom'] =
				(new ContentBlockWithTitle())
					->setTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_DELIVERY_SOURCE'))
					->setContentBlock(
						(new Text())
							->setValue((string)$deliveryShipment['ADDRESS_FROM_FORMATTED'])
							->setColor(Text::COLOR_BASE_90)
					)
			;
		}

		if (isset($deliveryShipment['ADDRESS_FROM_FORMATTED']))
		{
			$result['addressTo'] =
				(new ContentBlockWithTitle())
					->setTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_DELIVERY_DESTINATION'))
					->setContentBlock(
						(new Text())
							->setValue((string)$deliveryShipment['ADDRESS_TO_FORMATTED'])
							->setColor(Text::COLOR_BASE_90)
					)
			;
		}

		return $result;
	}

	public function getButtons(): array
	{
		$result = [];

		$deliveryRequest = $this->getDeliveryRequest();
		$deliveryService = $this->getDeliveryService();
		$deliveryShipments = $this->getDeliveryShipments();

		if (!$deliveryRequest && $this->isScheduled())
		{
			$result['createDeliveryRequest'] =
				(new Button(
					Loc::getMessage('CRM_TIMELINE_ACTIVITY_DELIVERY_ORDER_BUTTON'),
				Button::TYPE_PRIMARY,
				))
					->setAction(
						(new RunAjaxAction('sale.deliveryrequest.create'))
							->addActionParamArray(
								'shipmentIds',
								array_column($deliveryShipments, 'ID')
							)
							->addActionParamArray(
								'additional',
								[
									'ACTIVITY_ID' => $this->getActivityId(),
								]
							)
							->setAnimation(Animation::disableBlock()->setForever())
					)
			;
		}
		elseif(
			isset($deliveryShipments[0]['ID'])
			&& !in_array(
				$this->getContext()->getEntityTypeId(),
				[
					\CCrmOwnerType::Order,
					\CCrmOwnerType::ShipmentDocument,
				],
				true
			)
		)
		{
			$shipmentId = (int)$deliveryShipments[0]['ID'];
			$shipment = Sale\Repository\ShipmentRepository::getInstance()->getById($shipmentId);

			if ($shipment)
			{
				$openDetailsButton = new Button(
					Loc::getMessage('CRM_TIMELINE_ACTIVITY_DELIVERY_OPEN'),
					$this->isScheduled() ? Button::TYPE_PRIMARY : Button::TYPE_SECONDARY
				);

				$result['openDetails'] = $openDetailsButton->setAction(
					(new JsEvent('SalescenterApp:Start'))
						->addActionParamString('mode', 'delivery')
						->addActionParamInt('orderId', $shipment->getOrder()->getId())
						->addActionParamInt('shipmentId', $shipmentId)
						->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
						->addActionParamInt('ownerId', $this->getContext()->getEntityId())
				);
			}
		}

		if (
			$deliveryService
			&& $deliveryService['IS_CANCELLABLE'] === true
			&& $deliveryRequest
			&& (int)$deliveryRequest['STATUS'] !== Sale\Delivery\Requests\Manager::STATUS_PROCESSED
		)
		{
			$cancelDeliveryRequestAction =
				(new CallRestBatch())
					->addActionParamArray(
						'cancel',
						[
							'method' => 'sale.deliveryrequest.execute',
							'params' => [
								'requestId' => $deliveryRequest['ID'],
								'actionType' => $deliveryService['CANCEL_ACTION_CODE'],
							]
						]
					)
					->addActionParamArray(
						'create_message',
						[
							'method' => 'crm.timeline.deliveryactivity.createcanceldeliveryrequestmessage',
							'params' => [
								'requestId' => $deliveryRequest['ID'],
								'message' => '$result[cancel][message]',
							],
						]
					)
					->addActionParamArray(
						'delete',
						[
							'method' => 'sale.deliveryrequest.delete',
							'params' => [
								'requestId' => $deliveryRequest['ID'],
							],
						]
					)
					->setAnimation(Animation::disableBlock())
			;

			$cancelDeliveryRequestButton = new Button(
				isset($deliveryService['CANCEL_ACTION_NAME'])
					? (string)$deliveryService['CANCEL_ACTION_NAME']
					: Loc::getMessage('CRM_TIMELINE_ACTIVITY_DELIVERY_CANCEL')
				,
				Button::TYPE_SECONDARY,
			);

			$result['cancelDeliveryRequest'] = $cancelDeliveryRequestButton->setAction($cancelDeliveryRequestAction);
		}

		return $result;
	}

	public function getTags(): ?array
	{
		$deliveryRequest = $this->getDeliveryRequest();
		if (!$deliveryRequest)
		{
			return null;
		}

		$statusMap = [
			Sale\Delivery\Requests\Manager::EXTERNAL_STATUS_SEMANTIC_SUCCESS => Tag::TYPE_SUCCESS,
			Sale\Delivery\Requests\Manager::EXTERNAL_STATUS_SEMANTIC_PROCESS => Tag::TYPE_WARNING,
		];
		$hasStatus = (
			$deliveryRequest['EXTERNAL_STATUS']
			&& $deliveryRequest['EXTERNAL_STATUS_SEMANTIC']
			&& isset($statusMap[$deliveryRequest['EXTERNAL_STATUS_SEMANTIC']])
		);
		if (!$hasStatus)
		{
			return null;
		}

		return [
			'status' => new Tag(
				$deliveryRequest['EXTERNAL_STATUS'],
				$statusMap[$deliveryRequest['EXTERNAL_STATUS_SEMANTIC']]
			),
		];
	}

	public function getLogo(): ?Logo
	{
		return (new Logo('delivery'))->setInCircle();
	}

	public function getPayload(): ?Payload
	{
		$deliveryRequest = $this->getDeliveryRequest();
		$deliveryService = $this->getDeliveryService();
		$deliveryShipments = $this->getDeliveryShipments();

		$result =
			(new Payload\DeliveryActivityPayload())
				->addValueArrayOfInt(
					'shipmentIds',
					array_column($deliveryShipments, 'ID')
				)
				->addValueArrayOfInt(
					'deliveryServiceIds',
					(
						$deliveryService
						&& isset($deliveryService['IDS'])
						&& is_array($deliveryService['IDS'])
					)
						? $deliveryService['IDS']
						: []
				)
		;

		if ($deliveryRequest)
		{
			$result
				->addValueDeliveryRequest(
					'deliveryRequest',
					$deliveryRequest['ID'],
					$deliveryRequest['IS_PROCESSED'] === 'Y'
				)
			;
		}

		return $result;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	private function getDeliveryServiceName(): string
	{
		$deliveryService = $this->getDeliveryService();
		if (
			is_null($deliveryService)
			|| !isset($deliveryService['NAME'])
		)
		{
			return '';
		}

		// delivery service with profiles
		if (isset($deliveryService['PARENT_NAME']))
		{
			return implode(
				', ',
				[
					(string)$deliveryService['PARENT_NAME'],
					(string)$deliveryService['NAME']
				]
			);
		}

		return (string)$deliveryService['NAME'];
	}

	private function loadDeliveryInfo()
	{
		$this->deliveryInfo = Provider\Delivery::getDeliveryInfo(
			$this->getActivityId()
		);
	}

	private function getDeliveryRequest(): ?array
	{
		if (is_null($this->deliveryInfo))
		{
			$this->loadDeliveryInfo();
		}

		return
			(
				isset($this->deliveryInfo['DELIVERY_REQUEST'])
				&& is_array($this->deliveryInfo['DELIVERY_REQUEST'])
			)
				? $this->deliveryInfo['DELIVERY_REQUEST']
				: null
		;
	}

	private function getDeliveryService(): ?array
	{
		if (is_null($this->deliveryInfo))
		{
			$this->loadDeliveryInfo();
		}

		return
			(
				isset($this->deliveryInfo['DELIVERY_SERVICE'])
				&& is_array($this->deliveryInfo['DELIVERY_SERVICE'])
			)
				? $this->deliveryInfo['DELIVERY_SERVICE']
				: null
		;
	}

	private function getDeliveryShipments(): array
	{
		if (is_null($this->deliveryInfo))
		{
			$this->loadDeliveryInfo();
		}

		return
			(
				isset($this->deliveryInfo['SHIPMENTS'])
				&& is_array($this->deliveryInfo['SHIPMENTS'])
			)
				? $this->deliveryInfo['SHIPMENTS']
				: []
		;
	}
}
