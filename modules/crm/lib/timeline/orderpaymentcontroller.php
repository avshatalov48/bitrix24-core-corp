<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Order\Payment;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OrderPaymentController extends EntityController
{
	//region Singleton
	/** @var OrderPaymentController|null */
	protected static $instance = null;
	/**
	 * @return OrderPaymentController
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new OrderPaymentController();
		}
		return self::$instance;
	}
	//endregion
	//region EntityController
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::OrderPayment;
	}
	public function onCreate($ownerID, array $params)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null;
		if(!is_array($fields))
		{
			$fields = self::getEntity($ownerID);
		}
		if(!is_array($fields))
		{
			return;
		}

		$settingFields = [
			'SUM' => $fields['SUM'],
			'CURRENCY' => $fields['CURRENCY']
		];

		if ($fields['DATE_BILL'] instanceof Main\Type\Date)
		{
			$settingFields['DATE_BILL_TIMESTAMP'] = $fields['DATE_BILL']->getTimestamp();
		}

		$settings = ['FIELDS' => $settingFields];

		$orderId = (isset($fields['ORDER_ID']) && (int)$fields['ORDER_ID'] > 0) ? (int)$fields['ORDER_ID'] : 0;
		if($orderId > 0)
		{
			$settings['BASE'] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => (int)$fields['ORDER_ID']
			);
		}

		$authorID = self::resolveCreatorID($fields);
		$bindings = array(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::OrderPayment,
				'ENTITY_ID' => $ownerID
			)
		);

		if ($orderId > 0)
		{
			$bindings[] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $orderId
			);
		}

		$historyEntryID = CreationEntry::create(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::OrderPayment,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => $settings,
				'BINDINGS' => $bindings
			)
		);

		$enableHistoryPush = $historyEntryID > 0;
		if($enableHistoryPush && Main\Loader::includeModule('pull'))
		{
			$pushParams = array();
			if($enableHistoryPush)
			{
				$historyFields = TimelineEntry::getByID($historyEntryID);
				if(is_array($historyFields))
				{
					$pushParams['HISTORY_ITEM'] = $this->prepareHistoryDataModel(
						$historyFields,
						array('ENABLE_USER_INFO' => true)
					);
				}
			}

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::OrderPayment, $ownerID);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => 'timeline_order_payment_add',
					'params' => $pushParams,
				)
			);
		}
	}
	public function onModify($ownerID, array $params)
	{
	}
	public function updateSettingFields($ownerID, $entryTypeID, array $fields)
	{
		$result = new Main\Result();
		$ownerID = (int)$ownerID;
		$entryTypeID = (int)$entryTypeID;
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$timelineData = Entity\TimelineTable::getList([
			'filter' => [
				'ASSOCIATED_ENTITY_ID' => $ownerID,
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::OrderPayment,
				'TYPE_ID' => $entryTypeID,
			]
		]);
		while ($row = $timelineData->fetch())
		{
			$settings = $row['SETTINGS'];
			$settings['FIELDS'] = $fields;
			$r = Entity\TimelineTable::update($row['ID'], ['SETTINGS' => $settings]);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
			elseif (is_array($settings['BASE']))
			{
				$baseOwnerId = (int)$settings['BASE']['ENTITY_ID'];
				$baseOwnerTypeId = (int)$settings['BASE']['ENTITY_TYPE_ID'];
				if ($baseOwnerId > 0 && \CCrmOwnerType::IsDefined($baseOwnerTypeId))
				{
					$row['SETTINGS'] = $settings;
					$items = array($row['ID'] => $row);
					TimelineManager::prepareDisplayData($items);
					if(Main\Loader::includeModule('pull') && \CPullOptions::GetQueueServerStatus())
					{
						$tag = TimelineEntry::prepareEntityPushTag($baseOwnerTypeId, $baseOwnerId);
						\CPullWatch::AddToStack(
							$tag,
							array(
								'module_id' => 'crm',
								'command' => 'timeline_item_update',
								'params' => array('ENTITY_ID' => $row['ID'], 'TAG' => $tag, 'HISTORY_ITEM' => $items[$row['ID']]),
							)
						);
					}
				}
			}
		}

		return $result;
	}
	protected static function getEntity($ID)
	{
		$payment = Payment::getList(	array(
			'filter' => array('ID' => $ID),
			'select' => array(
				'ORDER_CREATED_BY' => 'ORDER.CREATE_BY',
				'ORDER_ACCOUNT_NUMBER' => 'ORDER.ACCOUNT_NUMBER',
				'RESPONSIBLE_ID','ACCOUNT_NUMBER', 'DATE_BILL', 'ORDER_ID'
			)
		));

		return is_object($payment) ? $payment->getFields() : null;
	}
	protected static function resolveCreatorID(array $fields)
	{
		$authorID = 0;

		if ($authorID <= 0 && isset($fields['RESPONSIBLE_ID']))
		{
			$authorID = (int)$fields['RESPONSIBLE_ID'];
		}

		if ($authorID <= 0 && isset($fields['ORDER_CREATED_BY']))
		{
			$authorID = (int)$fields['ORDER_CREATED_BY'];
		}

		if($authorID <= 0)
		{
			//Set portal admin as default creator
			$authorID = 1;
		}

		return $authorID;
	}
	/** @ToDo Change EditorId */
	protected static function resolveEditorID(array $fields)
	{
		$authorID = 0;

		if($authorID <= 0)
		{
			//Set portal admin as default editor
			$authorID = 1;
		}

		return $authorID;
	}
	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : TimelineType::UNDEFINED;
		$settings = $data['SETTINGS'];
		if($typeID === TimelineType::CREATION)
		{
			$base = isset($settings['BASE']) ? $settings['BASE'] : null;
			$data['TITLE'] = Loc::getMessage('CRM_ORDER_PAYMENT_CREATION');

			if(is_array($base))
			{
				$entityTypeID = isset($base['ENTITY_TYPE_ID']) ? $base['ENTITY_TYPE_ID'] : 0;
				$caption = Loc::getMessage("CRM_PAYMENT_BASE_CAPTION_BASED_ON_ORDER");

				$entityID = isset($base['ENTITY_ID']) ? $base['ENTITY_ID'] : 0;
				if(\CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0)
				{
					$data['BASE']['CAPTION'] = $caption;
					if(\CCrmOwnerType::TryGetEntityInfo(\CCrmOwnerType::Order, $entityID, $baseEntityInfo, false))
					{
						$data['BASE']['ENTITY_INFO'] = $baseEntityInfo;
					}
				}
			}

			$fields = $settings['FIELDS'];
			$title = $data['ASSOCIATED_ENTITY']['TITLE'];
			if (!empty($fields['DATE_BILL_TIMESTAMP']))
			{
				$dateInsert = \CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp($fields['DATE_BILL_TIMESTAMP'],'SHORT'));
			}
			if (empty($dateInsert))
			{
				$dateInsert = \CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($data['DATE_INSERT']),'SHORT'));
			}

			$data['ASSOCIATED_ENTITY']['HTML_TITLE'] = Loc::getMessage(
				'CRM_PAYMENT_CREATION_MESSAGE',
				[
					'#ACCOUNT_NUMBER#' => $title,
					'#DATE_BILL#' => $dateInsert,
				]
			);
			if (!empty($fields['SUM']) && !empty($fields['CURRENCY']))
			{
				$data['ASSOCIATED_ENTITY']['HTML_TITLE'] .= " ".Loc::getMessage(
					'CRM_PAYMENT_CREATION_MESSAGE_SUM',
					['#SUM_WITH_CURRENCY#' => \CCrmCurrency::MoneyToString($fields['SUM'], $fields['CURRENCY'])]
				);
			}

			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::MODIFICATION)
		{
		}
		return parent::prepareHistoryDataModel($data, $options);
	}
}