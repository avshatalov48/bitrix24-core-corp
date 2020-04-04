<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Order\DeliveryStatus;
use Bitrix\Crm\Order\Shipment;
use Bitrix\Main;
use Bitrix\Crm\Order\OrderShipmentStatus;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OrderCheckController extends EntityController
{
	//region Singleton
	/** @var OrderCheckController|null */
	protected static $instance = null;
	/**
	 * @return OrderCheckController
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new OrderCheckController();
		}
		return self::$instance;
	}
	//endregion
	//region EntityController
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::OrderCheck;
	}

	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$data['TITLE'] = Loc::getMessage('CRM_ORDER_CHECK_TITLE', [
			'#CHECK_ID#' => $data['ASSOCIATED_ENTITY_ID']
		]);
		$entity = $data['ASSOCIATED_ENTITY'];
		$data['LEGEND'] = Loc::getMessage('CRM_ORDER_CHECK_LEGEND', [
			'#DATE_CREATE#' => $entity['DATE_CREATE_FORMATTED'],
			'#SUM_WITH_CURRENCY#' => $entity['SUM_WITH_CURRENCY']
		]);
		$data['PRINTED'] = $data['SETTINGS']['PRINTED'];
		unset($data['SETTINGS']);
		return parent::prepareHistoryDataModel($data, $options);
	}
}