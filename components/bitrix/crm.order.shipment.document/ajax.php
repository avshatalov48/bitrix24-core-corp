<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Order\Permissions;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

Loc::loadMessages(__FILE__);

if(!Loader::includeModule('crm'))
{
	die('Can\'t include module CRM');
}

/** @internal  */
final class AjaxProcessor extends \Bitrix\Crm\Order\AjaxProcessor
{
	protected function saveAction()
	{
		$id = (int)$this->request['ACTION_ENTITY_ID'] > 0 ? (int)$this->request['ACTION_ENTITY_ID'] : 0;

		if($id > 0)
		{
			if(!Permissions\Order::checkUpdatePermission($id, $this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
				return;
			}

			$res = \Bitrix\Crm\Order\Shipment::getList(['filter'=>['=ID' => $id]]);

			if(!($shipmentFields = $res->fetch()))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_SHIPMENT_NOT_FOUND'));
				return;
			}

			$shipmentFields = array_merge(
				$shipmentFields,
				$this->request
			);

			if(!($shipment = $this->buildShipment($shipmentFields)))
			{
				return;
			}

			$collection = $shipment->getCollection();
			$order = $collection->getOrder();

			$result = $order->save();
			if(!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());
				return;
			}
			if ($result->hasWarnings())
			{
				$this->addErrors($result->getWarnings());
				return;
			}
			$fieldValues = $shipmentFields;
		}
		else
		{
			if(!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
				return;
			}

			$fieldValues = array_intersect_key($this->request, array_flip(['TRACKING_NUMBER', 'DELIVERY_DOC_NUM', 'DELIVERY_DOC_DATE']));
		}

		$this->addData(['ENTITY_ID' => $id, 'ENTITY_DATA' => $fieldValues]);
	}

	protected function buildShipment($formData)
	{
		$builderSettings = new \Bitrix\Sale\Helpers\Order\Builder\SettingsContainer([]);
		$orderBuilder = new \Bitrix\Crm\Order\OrderBuilderCrm($builderSettings);
		$director = new \Bitrix\Sale\Helpers\Order\Builder\Director;
		$shipment = $director->getUpdatedShipment($orderBuilder, $formData);

		if(!$shipment)
		{
			$this->addErrors($orderBuilder->getErrorsContainer()->getErrors());
		}

		return $shipment;
	}
}

$APPLICATION->RestartBuffer();
$processor = new AjaxProcessor($_REQUEST);
$result = $processor->checkConditions();

if($result->isSuccess())
{
	$result = $processor->processRequest();
}

$processor->sendResponse($result);

if(!defined('PUBLIC_AJAX_MODE'))
{
	define('PUBLIC_AJAX_MODE', true);
}

\CMain::FinalActions();

die();
