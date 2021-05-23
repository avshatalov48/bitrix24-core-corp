<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Order\Payment;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class OrderPaymentController
 * @package Bitrix\Crm\Timeline
 */
class OrderPaymentController extends EntityController
{
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

	/**
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::OrderPayment;
	}

	/**
	 * @param $ownerID
	 * @param array $params
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
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

	/**
	 * @param $ownerId
	 * @param array $params
	 * @throws Main\ArgumentException
	 */
	public function onPaid($ownerId, array $params)
	{
		return $this->notifyOrderPaymentEntry($ownerId, $params);
	}

	/**
	 * @param $ownerId
	 * @param array $params
	 * @throws Main\ArgumentException
	 */
	public function onClick($ownerId, array $params)
	{
		$params['SETTINGS']['FIELDS']['PAY_SYSTEM_CLICK'] = 'Y';
		return $this->notifyOrderPaymentEntry($ownerId, $params);
	}

	/**
	 * @param $ownerId
	 * @param array $params
	 * @throws Main\ArgumentException
	 */
	private function notifyOrderPaymentEntry($ownerId, array $params)
	{
		if (!is_int($ownerId))
		{
			$ownerId = (int)$ownerId;
		}
		if ($ownerId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$settings = is_array($params['SETTINGS']) ? $params['SETTINGS'] : [];
		$paymentFields = is_array($params['FIELDS']) ? $params['FIELDS'] : [];
		$bindings = $params['BINDINGS'] ?? [];

		$authorId = self::resolveCreatorID($paymentFields);
		if (!empty($settings))
		{
			$historyEntryID = OrderEntry::create([
				'ENTITY_ID' => $ownerId,
				'TYPE_CATEGORY_ID' => TimelineType::MODIFICATION,
				'ENTITY_TYPE_ID' => \CCrmOwnerType::OrderPayment,
				'AUTHOR_ID' => $authorId,
				'BINDINGS' => $bindings,
				'SETTINGS' => $settings
			]);

			if ($historyEntryID > 0)
			{
				foreach ($bindings as $binding)
				{
					$tag = TimelineEntry::prepareEntityPushTag($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']);
					self::pushHistoryEntry($historyEntryID, $tag, 'timeline_activity_add');
				}
			}
		}
	}

	/**
	 * @param $ownerID
	 * @param $entryTypeID
	 * @param array $fields
	 * @return Main\Result
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
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

	/**
	 * @param $ID
	 * @return Main\ORM\Fields\ScalarField[]|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
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

	/**
	 * @param array $fields
	 * @return int
	 */
	protected static function resolveCreatorID(array $fields)
	{
		$authorId = 0;

		if (isset($fields['RESPONSIBLE_ID']))
		{
			$authorId = (int)$fields['RESPONSIBLE_ID'];
		}

		if ($authorId === 0 && isset($fields['ORDER_CREATED_BY']))
		{
			$authorId = (int)$fields['ORDER_CREATED_BY'];
		}

		if ($authorId <= 0)
		{
			$authorId = self::getDefaultAuthorId();
		}

		return $authorId;
	}

	/**
	 * @param array $data
	 * @param array|null $options
	 * @return array
	 */
	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : TimelineType::UNDEFINED;
		$settings = is_array($data['SETTINGS']) ? $data['SETTINGS'] : [];
		$fields = $settings['FIELDS'];

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
			$title = htmlspecialcharsbx(\CUtil::JSEscape($data['ASSOCIATED_ENTITY']['TITLE']));
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
		elseif($typeID === TimelineType::ORDER)
		{
			if (!empty($fields['PAY_SYSTEM_CLICK']) && $fields['PAY_SYSTEM_CLICK'] === 'Y')
			{
				$data['TITLE'] = Loc::getMessage('CRM_PAYMENT_PAYSYSTEM_CLICK_TITLE');
				$data['ASSOCIATED_ENTITY']['CLICK'] = 'Y';
			}
			else
			{
				$data['TITLE'] = \CCrmOwnerType::GetDescription(\CCrmOwnerType::OrderPayment);
				$data['ASSOCIATED_ENTITY']['TITLE'] = Loc::getMessage(
					'CRM_PAYMENT_PAID_TITLE',
					['#ACCOUNT_NUMBER#' => $data['ASSOCIATED_ENTITY']['TITLE']]
				);
			}

			$data = array_merge($data, $settings);
			unset($data['SETTINGS']);
		}

		return parent::prepareHistoryDataModel($data, $options);
	}
}