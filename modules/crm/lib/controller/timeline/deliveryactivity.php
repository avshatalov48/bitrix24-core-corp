<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Timeline\DeliveryController;
use Bitrix\Main\Engine\Controller;
use Bitrix\Crm\Activity\Provider\Delivery;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class DeliveryActivity
 * @package Bitrix\Crm\Controller\Timeline
 */
class DeliveryActivity extends Controller
{
	/**
	 * @param int $activityId
	 * @return array
	 */
	public function getDeliveryInfoAction(int $activityId)
	{
		return Delivery::getDeliveryInfo($activityId);
	}

	/**
	 * @param int $requestId
	 * @return array
	 */
	public function createCancelDeliveryRequestMessageAction(int $requestId, string $message = ''): array
	{
		DeliveryController::getInstance()->createDeliveryRequestMessage(
			[
				'TITLE' => Loc::getMessage('CRM_TIMELINE_CONTROLLER_DELIVERY_ACTIVITY_CANCEL_DELIVERY_ORDER_V2'),
				'DESCRIPTION' => $message,
			],
			$requestId
		);

		return [];
	}

	/**
	 * @return array
	 */
	public function checkRequestStatusAction(): array
	{
		return [];
	}
}
