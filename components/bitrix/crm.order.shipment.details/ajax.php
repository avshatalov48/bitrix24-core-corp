<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('BX_PUBLIC_MODE', true);
define('DisableEventsCheck', true);

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Order\Permissions;
use Bitrix\Sale\Delivery;
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

Loc::loadMessages(__FILE__);

if(!Loader::includeModule('crm'))
{
	die('Can\'t include module CRM');
}

/** @internal  */
final class AjaxProcessor extends \Bitrix\Crm\Order\AjaxProcessor
{
	use \Bitrix\Crm\Component\EntityDetails\SaleProps\AjaxProcessorTrait;

	protected function changeDeliveryAction()
	{
		if(!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Shipment::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_SD_INSUFFICIENT_RIGHTS'));
				return;
			}
		}
		elseif (!Permissions\Shipment::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_SD_INSUFFICIENT_RIGHTS'));
			return;
		}

		$deliveryId = (int)($formData['DELIVERY_ID']);

		if($deliveryId <= 0)
		{
			return;
		}

		$service = Delivery\Services\Manager::getObjectById($deliveryId);

		if($service && $service->canHasProfiles())
		{
			$profiles = Delivery\Services\Manager::getByParentId($deliveryId);
			reset($profiles);
			$initProfile = current($profiles);
			$formData['DELIVERY_ID'] = $initProfile['ID'];
		}

		if(!($shipment = $this->buildShipment($formData)))
		{
			return;
		}

		$this->addData([
			'SHIPMENT_DATA' => $this->createDataByComponent($shipment)
		]);
	}

	protected function refreshShipmentDataAction()
	{
		if(!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Shipment::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_SD_INSUFFICIENT_RIGHTS'));
				return;
			}
		}
		elseif (!Permissions\Shipment::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_SD_INSUFFICIENT_RIGHTS'));
			return;
		}

		if(!($shipment = $this->buildShipment($formData)))
		{
			return;
		}

		$this->addData([
			'SHIPMENT_DATA' => $this->createDataByComponent($shipment),
			'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($shipment)
		]);
	}

	protected function saveAction()
	{
		$id = (int)$this->request['ACTION_ENTITY_ID'] > 0 ? (int)$this->request['ACTION_ENTITY_ID'] : 0;
		$isNew = $id <= 0;
		$productData = [];

		if(!empty($this->request['ORDER_SHIPMENT_PRODUCT_DATA']))
		{
			$productData = current(\CUtil::JsObjectToPhp($this->request['ORDER_SHIPMENT_PRODUCT_DATA']));

			if(!is_array($productData))
			{
				$productData = [];
			}
		}

		$shipmentFields = [];

		if($id > 0)
		{
			if(!Permissions\Shipment::checkUpdatePermission($id, $this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_SD_INSUFFICIENT_RIGHTS'));
				return;
			}

			$res = \Bitrix\Crm\Order\Shipment::getList(['filter'=>['=ID' => $id]]);

			if(!($shipmentFields = $res->fetch()))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_SD_SHIPMENT_NOT_FOUND'));
				return;
			}
		}
		else
		{
			if(!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(new \Bitrix\Main\Error(Loc::getMessage('CRM_ORDER_SD_INSUFFICIENT_RIGHTS')));
				return;
			}
		}

		$shipmentFields = array_merge(
			$shipmentFields,
			$this->request,
			$productData
		);

		$shipmentFields['PROPERTIES'] = $this->getPropertiesField($this->request);

		if(!($shipment = $this->buildShipment($shipmentFields)) || !$this->result->isSuccess())
		{
			return;
		}

		$collection = $shipment->getCollection();
		$order = $collection->getOrder();
		$res = $order->save();

		if(!$res->isSuccess())
		{
			$this->addErrors($res->getErrors());
			return;
		}

		if($res->hasWarnings())
		{
			$this->addErrors($res->getWarnings());
		}

		$entityData = $this->createDataByComponent($shipment);

		if(is_array($productData))
		{
			$entityData['PRODUCT_COMPONENT_RESULT'] = $this->getProductComponentData($shipment);
		}

		$id = $shipment->getId();
		$this->addData(['ENTITY_ID' => $id, 'ENTITY_DATA' => $entityData]);

		if($isNew)
		{
			$this->addData(['REDIRECT_URL' =>\CCrmOwnerType::GetDetailsUrl(
				\CCrmOwnerType::OrderShipment,
				$id,
				false,
				['OPEN_IN_SLIDER' => true]
			)]);
		}
	}

	private function createDataByComponent(\Bitrix\Crm\Order\Shipment $shipment)
	{
		\CBitrixComponent::includeComponentClass('bitrix:crm.order.shipment.details');
		$component = new \CCrmOrderShipmentDetailsComponent();

		$formDataContextParams = $this->request['FORM_DATA']['PARAMS'] ?? [];
		$formDataParams = $this->request['PARAMS'] ?? [];
		$componentParams = array_merge($formDataContextParams, $formDataParams);

		$component->initializeParams($componentParams);
		$component->setEntityID($shipment->getId());
		$component->setShipment($shipment);

		$entityData = $component->prepareEntityData();

		$entityData['SHIPMENT_PROPERTIES_SCHEME'] = $component->prepareProperties(
			$shipment->getPropertyCollection(),
			\Bitrix\Crm\Order\ShipmentProperty::class,
			$shipment->getPersonTypeId(),
			($shipment->getId() === 0)
		);

		return $entityData;
	}

	/**
	 * @param $formData
	 * @return \Bitrix\Crm\Order\Shipment
	 */
	protected function buildShipment($formData)
	{
		$formData['PROPERTIES'] = $this->getPropertiesField($formData);

		$builderSettings = new \Bitrix\Sale\Helpers\Order\Builder\SettingsContainer([]);
		$orderBuilder = new \Bitrix\Crm\Order\OrderBuilderCrm($builderSettings);
		$director = new \Bitrix\Sale\Helpers\Order\Builder\Director;

		if(isset($formData['IS_PRODUCT_LIST_LOADED']) && $formData['IS_PRODUCT_LIST_LOADED'] == 'Y' && !isset($formData['PRODUCT']))
		{
			// null - means products not loaded (maybe yet), empty array - means basket is empty.
			$formData['PRODUCT'] = [];
		}

		$shipment = $director->getUpdatedShipment($orderBuilder, $formData);

		if(!empty($orderBuilder->getErrorsContainer()->getErrors()))
		{
			$this->addErrors($orderBuilder->getErrorsContainer()->getErrors());
		}

		return $shipment;
	}

	protected function getFormData()
	{
		$result = [];

		if(isset($this->request['FORM_DATA']) && is_array($this->request['FORM_DATA']) && !empty($this->request['FORM_DATA']))
		{
			$result = $this->request['FORM_DATA'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_SD_FORM_DATA_MISSING'));
		}

		return $result;
	}

	protected function addProductAction()
	{
		$basketId = (int)$this->request['BASKET_ID'] > 0 ? (int)$this->request['BASKET_ID'] : 0;

		if($basketId <= 0)
		{
			$this->addError('BASKET_ID must be greater than 0');
			return;
		}

		if(!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Shipment::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_SD_INSUFFICIENT_RIGHTS'));
				return;
			}
		}
		elseif (!Permissions\Shipment::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_SD_INSUFFICIENT_RIGHTS'));
			return;
		}

		if(!($shipment = $this->buildShipment($formData)))
		{
			return;
		}

		/** @var \Bitrix\Crm\Order\Shipment $systemShipment */
		$systemShipment = $shipment->getCollection()->getSystemShipment();
		/** @var \Bitrix\Crm\Order\ShipmentItemCollection $systemShipmentItemCollection */
		$systemShipmentItemCollection = $systemShipment->getShipmentItemCollection();
		/** @var \Bitrix\Crm\Order\ShipmentItemCollection $systemShipmentItemCollection */
		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		if(!($shipmentItemCollection->getItemByBasketId($basketId)))
		{
			$shipmentItem = $systemShipmentItemCollection->getItemByBasketId($basketId);

			if($shipmentItem)
			{
				$basketItem = $shipmentItem->getBasketItem();
				$newItem = $shipmentItemCollection->createItem($basketItem);

				if($newItem)
				{
					$newItem->setField('QUANTITY', $shipmentItem->getField('QUANTITY'));
				}
				else
				{
					$this->addError(Loc::getMessage('CRM_ORDER_SD_ERROR_SHIPPING_DEDUCTED'));
				}
			}
		}

		$this->addData([
			'SHIPMENT_DATA' => $this->createDataByComponent($shipment),
			'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($shipment)
		]);
	}

	protected function deleteProductAction()
	{
		$basketCode = !empty($this->request['BASKET_CODE']) ? trim($this->request['BASKET_CODE']) : '';

		if($basketCode == '')
		{
			$this->addError('BASKET_CODE is not defined');
			return;
		}

		if(!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Shipment::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_SD_INSUFFICIENT_RIGHTS'));
				return;
			}
		}
		elseif (!Permissions\Shipment::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_SD_INSUFFICIENT_RIGHTS'));
			return;
		}

		if(!($shipment = $this->buildShipment($formData)))
		{
			return;
		}

		$collection = $shipment->getShipmentItemCollection();

		if((int)$formData['ID'] > 0)
		{
			$shipmentItem = $collection->getItemByBasketCode($basketCode);
		}
		else
		{
			$shipmentItem = $collection->getItemByBasketId($basketCode);
		}

		if($shipmentItem)
		{
			$res = $shipmentItem->delete();

			if(!$res->isSuccess())
			{
				$this->addErrors($res->getErrors());
				return;
			}
		}

		$this->addData([
			'SHIPMENT_DATA' => $this->createDataByComponent($shipment),
			'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($shipment)
		]);
	}

	protected function getProductComponentData(\Bitrix\Crm\Order\Shipment $shipment)
	{
		global $APPLICATION;
		$componentParams = $this->request['PRODUCT_COMPONENT_DATA'];
		$componentParams['params']['signedParameters'] = \CCrmInstantEditorHelper::signComponentParams(
			(array)$componentParams['params'],
			'crm.order.shipment.product.list'
		);

		ob_start();
		$APPLICATION->IncludeComponent('bitrix:crm.order.shipment.product.list',
			$componentParams['template'] ?? '',
			array_merge(
				$componentParams['params'],
				[
					'SHIPMENT' => $shipment,
					'AJAX_MODE' => 'N',
					'AJAX_LOADER' => [
						'url' => '/bitrix/components/bitrix/crm.order.shipment.product.list/lazyload.ajax.php?&site=' . SITE_ID . '&' . bitrix_sessid_get(),
						'method' => 'POST',
						'dataType' => 'ajax',
						'data' => [
							'PARAMS' => [
								'signedParameters' => $componentParams['params']['signedParameters']
							],
						],
					],
				]
			),
			false,
			array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
		);

		return ob_get_clean();
	}

	protected function rollbackAction()
	{
		if(!($formData = $this->getFormData()))
		{
			return;
		}

		if (!Permissions\Shipment::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_SD_INSUFFICIENT_RIGHTS'));
			return;
		}

		if(!($shipment = \Bitrix\Crm\Order\Manager::getShipmentObject($formData['ID'])))
		{
			return;
		}

		$this->addData([
			'SHIPMENT_DATA' => $this->createDataByComponent($shipment),
			'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($shipment)
		]);
	}

	protected function deleteAction()
	{
		$id = (int)$this->request['ACTION_ENTITY_ID'] > 0 ? (int)$this->request['ACTION_ENTITY_ID'] : 0;

		if($id <= 0)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_SD_SHIPMENT_NOT_FOUND'));
			return;
		}

		if(!Permissions\Shipment::checkDeletePermission($id, $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_SD_INSUFFICIENT_RIGHTS'));
			return;
		}
		$shipmentRaw = \Bitrix\Crm\Order\Shipment::getList([
			'filter' => ['=ID' => $id],
			'select' => ['ORDER_ID'],
			'limit' => 1
		]);
		$shipmentData = $shipmentRaw->fetch();
		$order = \Bitrix\Crm\Order\Order::load($shipmentData['ORDER_ID']);
		if(!$order)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_SD_SHIPMENT_NOT_FOUND'));
			return;
		}

		$shipmentCollection = $order->getShipmentCollection();
		$shipment = $shipmentCollection->getItemById($id);
		$res = $shipment->delete();
		$order->save();
		if ($res->isSuccess())
		{
			$this->addData(['ENTITY_ID' => $id]);
		}
		else
		{
			$this->addErrors($res->getErrors());
		}
	}
}

CUtil::JSPostUnescape();
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