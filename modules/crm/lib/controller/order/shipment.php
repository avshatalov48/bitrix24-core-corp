<?php

namespace Bitrix\Crm\Controller\Order;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;

Main\Localization\Loc::loadLanguageFile(__FILE__);
Main\Loader::requireModule('sale');

class Shipment extends Entity
{
	private const SHIPMENT_ACCESS_DENIED_ERROR_CODE = 'SHIPMENT_ACCESS_DENIED';

	protected function processBeforeAction(Main\Engine\Action $action)
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		$actionArguments = $action->getArguments();
		$id = $actionArguments['shipment'] ? $actionArguments['shipment']->getId() : 0;

		if (!Crm\Order\Permissions\Shipment::checkUpdatePermission($id, $userPermissions))
		{
			$this->addError(
				new Main\Error(
					Main\Localization\Loc::getMessage('CRM_CONTROLLER_SHIPMENT_DOCUMENT_ACCESS_DENIED'),
					self::SHIPMENT_ACCESS_DENIED_ERROR_CODE
				)
			);
			return false;
		}

		$this->temporarilyDisableAutomationIfNeeded();

		return parent::processBeforeAction($action);
	}

	protected function processAfterAction(Main\Engine\Action $action, $result)
	{
		$this->restoreDisabledAutomationIfNeeded();

		parent::processAfterAction($action, $result);
	}

	public function getPrimaryAutoWiredParameter()
	{
		return new Main\Engine\AutoWire\ExactParameter(
			Crm\Order\Shipment::class,
			'shipment',
			function($className, $id) {
				$shipment = Sale\Repository\ShipmentRepository::getInstance()->getById($id);

				if ($shipment)
				{
					return $shipment;
				}

				$this->addError(new Main\Error('shipment not found'));
				return null;
			}
		);
	}

	public function setShippedAction(Crm\Order\Shipment $shipment, string $value): void
	{
		$order = $shipment->getOrder();

		$setResult = $shipment->setField('DEDUCTED', $value);
		if ($setResult->isSuccess())
		{
			$saveOrderResult = $order->save();
			if (!$saveOrderResult->isSuccess())
			{
				$this->addErrors($saveOrderResult->getErrors());
			}
		}
		else
		{
			$this->addErrors($setResult->getErrors());
		}
	}

	public function deleteAction(Crm\Order\Shipment $shipment): void
	{
		$order = $shipment->getOrder();
		$deleteResult = $shipment->delete();
		if ($deleteResult->isSuccess())
		{
			$saveOrderResult = $order->save();
			if (!$saveOrderResult->isSuccess())
			{
				$this->addErrors($saveOrderResult->getErrors());
			}
		}
		else
		{
			$this->addErrors($deleteResult->getErrors());
		}
	}
}