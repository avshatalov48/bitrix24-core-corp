<?php


namespace Bitrix\Sale\Rest;


use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Rest\RestException;
use Bitrix\Sale\Internals\Entity;
use Bitrix\Sale\Rest\Synchronization\LoggerDiag;
use Bitrix\Sale\Rest\Synchronization\Manager;
use Bitrix\Sale\Rest\Synchronization\Synchronizer;

class RestManager
{

//RegisterModuleDependences("rest", "onRestGetModule", "sale", "\\Bitrix\\Sale\\Rest\\RestManager", "onRestGetModule");
//RegisterModuleDependences("rest", "OnRestServiceBuildDescription", "sale", "\\Bitrix\\Sale\\Rest\\RestManager", "onRestServiceBuildDescription");
///rest/event.bind.json?auth=423f8e5b0000cdb90000cdb8000000010000030eb629c718430b3c900e901aa414b84c&auth_type=0&event=OnSaleOrderSaved&handler=http://evgenik.office.bitrix.ru/handler/
///rest/event.bind.json?auth=423f8e5b0000cdb90000cdb8000000010000030eb629c718430b3c900e901aa414b84c&auth_type=0&event=OnSaleBeforeOrderDelete&handler=http://evgenik.office.bitrix.ru/handler/

//	public static function onRestGetModule()
//	{
//		return ['MODULE_ID' => 'sale'];
//	}

	protected static function isB24()
	{
		return ModuleManager::isModuleInstalled('crm');
	}

	public static function onRestServiceBuildDescription()
	{
		Loader::includeModule('sale');

		return [
			'sale' => [
				\CRestUtil::EVENTS=>[
					'OnSaleOrderSaved'=>[
						'sale',
						'OnSaleOrderSaved',
						[
							RestManager::class,
							'processEvent'
						],
						[
							'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM
						]
					],
					'OnSaleBeforeOrderDelete'=>[
						'sale',
						'OnSaleBeforeOrderDelete',
						[
							RestManager::class,
							'processEvent'
						],
						[
							'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM
						]
					],

					'OnPropertyValueEntitySaved'=>[
						'sale',
						self::isB24()? 'OnCrmOrderPropertyValueEntitySaved':'OnSalePropertyValueEntitySaved',
						[
							RestManager::class,
							'processEvent'
						],
						[
							'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM
						]
					],
					'OnPaymentEntitySaved'=>[
						'sale',
						self::isB24()? 'OnCrmOrderPaymentEntitySaved':'OnSalePaymentEntitySaved',
						[
							RestManager::class,
							'processEvent'
						],
						[
							'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM
						]
					],
					'OnShipmentEntitySaved'=>[
						'sale',
						self::isB24()? 'OnCrmOrderShipmentEntitySaved':'OnSaleShipmentEntitySaved',
						[
							RestManager::class,
							'processEvent'
						],
						[
							'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM
						]
					],
					'OnOrderEntitySaved'=>[
						'sale',
						self::isB24()? 'OnCrmOrderOrderEntitySaved':'OnSaleOrderEntitySaved',
						[
							RestManager::class,
							'processEvent'
						],
						[
							'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM
						]
					],
					'OnPropertyValueDeleted'=>[
						'sale',
						self::isB24()? 'OnCrmOrderPropertyValueDeleted':'OnSalePropertyValueDeleted',
						[
							RestManager::class,
							'processEvent'
						],
						[
							'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM
						]
					],
					'OnPaymentDeleted'=>[
						'sale',
						self::isB24()? 'OnCrmOrderPaymentDeleted':'OnSalePaymentDeleted',
						[
							RestManager::class,
							'processEvent'
						],
						[
							'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM
						]
					],
					'OnShipmentDeleted'=>[
						'sale',
						self::isB24()? 'OnCrmOrderShipmentDeleted':'OnSaleShipmentDeleted',
						[
							RestManager::class,
							'processEvent'
						],
						[
							'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM
						]
					],
					'OnOrderDeleted'=>[
						'sale',
						self::isB24()? 'OnCrmOrderOrderEntitySaved':'OnSaleOrderEntitySaved',
						[
							RestManager::class,
							'processEvent'
						],
						[
							'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM
						]
					],
				]
			]
		];
	}

	public static function processEvent(array $params, array $handlerFields)
	{
		$event = $params[0];
		$eventName = $handlerFields['EVENT_NAME'];
		$eventHandler = $handlerFields['EVENT_HANDLER'];

		$instance = Manager::getInstance();

		LoggerDiag::addMessage('processEvent', var_export([
			'processEvent [process-01]'=> [
				'eventName'=>$eventName,
				'action'=>$instance->getAction()
			]
		], true));

		switch (strtolower($eventName))
		{
			case 'onsaleordersaved':

				/** @var Entity $entity */
				$entity = $event->getParameters()['ENTITY'];

				// ���� ���������� ����� ������, �� ������ ��������� �������
				// ���� ��������� ��������, �� ���� ������ ������� �.�. ������� onsaleordersaved ��� ����� ���������� ��� ��������
				// �������� �� deleted ����� ������ ����� ������ ������� onsalebeforeorderdeleterest
				// ���� ���������� ��������, �� ������ ��������� ������� ������ ��� ��������� ������������ ������� �����������
				if($instance->getAction() == Manager::ACTION_IMPORT || $instance->getAction() == Manager::ACTION_DELETED)
				{
					// ������ ��� ��������
					throw new RestException("Event stopped");
				}
				elseif($instance->isExecutedHandler($eventHandler))
				{
					//��������� ������������ �������
					throw new RestException("Event stopped");
				}


				if($entity->getId()<= 0)
				{
					throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
				}

				//�������� ������� �������, ��� ������ �� ����� ��������� ��� ������������� ����������|��������
				$parameters = ['FIELDS'=>['ID'=>$entity->getId(), 'XML_ID'=>$entity->getField('XML_ID'), 'ACTION'=>Synchronizer::MODE_SAVE]];

				LoggerDiag::addMessage(strtolower($eventName), var_export([
					'processEvent [process-02]'=> [
						'parameters'=>$parameters
					]
				], true));

				// ��������� ��������� ���������� �����������, ���� ��������� ������ ������� ���������� �������������� ������, �������� ����������� �����
				$instance->pushHandlerExecuted($eventHandler);

				return $parameters;
				break;
			case 'onsalebeforeorderdelete':

				/** @var Entity $entity */
				$entity = $event->getParameters()['ENTITY'];

				// ���� �������� ����� ������, �� ������ ��������� �������
				// ���� ��������� ��������, �� ���������� ��������� ��������� �� ������� �������� � ��������� ��������
				// ���� ��������� �������� � ������� �������� �������� ������ �������� (onpropertyvaluedeleted)
				if($instance->getAction() == Manager::ACTION_IMPORT || $instance->getAction() == Manager::ACTION_DELETED)
					throw new RestException("Event stopped");

				//TODO: chack - ������������� action � deleted ��� ����� �������� ��������� ������� onsaleordersavedrest.
				$instance->setAction(Manager::ACTION_DELETED);

				//�������� ������� �������, ��� ������ �� ����� ��������� ��� ������������� ����������|��������
				$parameters = ['FIELDS'=>['ID'=>$entity->getId(), 'XML_ID'=>$entity->getField('XML_ID'), 'ACTION'=>Synchronizer::MODE_DELETE]];

				LoggerDiag::addMessage(strtolower($eventName), var_export([
					'processEvent [process-03]'=> [
						'parameters'=>$parameters
					]
				], true));

				return $parameters;
				break;

			case 'onpropertyvalueentitysaved':
			case 'onpaymententitysaved':
			case 'onshipmententitysaved':
			case 'onorderentitysaved':
			case 'onpropertyvaluedeleted':
			case 'onpaymentdeleted':
			case 'onshipmentdeleted':
			case 'onorderdeleted':

				/** @var Entity $entity */
				$entity = $event->getParameters()['ENTITY'];
				$entityId = 0;
				if($entity !== null)
				{
					$entityId = $entity->getId();
				}
				elseif(isset($event->getParameters()['VALUES']))
				{
					$entityId = $event->getParameters()['VALUES']['ID'];
				}

				$parameters = ['FIELDS'=>['ID'=>$entityId]];

				LoggerDiag::addMessage(strtolower($eventName), var_export([
					'processEvent [process-04]'=> [
						'parameters'=>$parameters
					]
				], true));

				return $parameters;
			break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}
	}
}