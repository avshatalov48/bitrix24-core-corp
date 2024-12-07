<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order;
use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class OrderController
 * @package Bitrix\Crm\Timeline
 */
class OrderController extends EntityController
{
	/**
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Order;
	}

	/**
	 * @param $ownerId
	 * @param array $params
	 * @throws Main\ArgumentException
	 */
	public function onCreate($ownerId, array $params)
	{
		if ($ownerId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.');
		}

		$orderFields = $params['ORDER_FIELDS'] ?? [];
		$settings = $params['SETTINGS'] ?? [];
		$bindings = $params['BINDINGS'] ?? [];

		$timelineEntryId = OrderEntry::create([
			'ENTITY_ID' => $ownerId,
			'TYPE_CATEGORY_ID' => TimelineType::CREATION,
			'AUTHOR_ID' => self::resolveCreatorID($orderFields),
			'SETTINGS' => $settings,
			'BINDINGS' => $bindings,
		]);
		$this->sendAddPullEventToBindings($bindings, $timelineEntryId);
	}

	/**
	 * @param $ownerId
	 * @param array $params
	 */
	public function onLandingOrderCreate(int $ownerId, int $dealId, array $params)
	{
		if ($ownerId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.');
		}

		if ($dealId <= 0)
		{
			throw new Main\ArgumentException('Deal ID must be greater than zero.');
		}

		$orderFields = $params['ORDER_FIELDS'] ?? [];
		$settings = $params['SETTINGS'] ?? [];

		$bindings = [
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => $dealId
			]
		];

		$timelineEntryId = OrderEntry::create([
			'ENTITY_ID' => $ownerId,
			'TYPE_CATEGORY_ID' => OrderCategoryType::ENCOURAGE_BUY_PRODUCTS,
			'AUTHOR_ID' => self::resolveCreatorID($orderFields),
			'SETTINGS' => $settings,
			'BINDINGS' => $bindings,
		]);
		$this->sendAddPullEventToBindings($bindings, $timelineEntryId);
	}

	/**
	 * @param $ownerID
	 * @param array $params
	 * @throws Main\ArgumentException
	 */
	public function onCancel($ownerID, array $params)
	{
		if (!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if ($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null;
		$value = ($fields['CANCELED'] === 'Y') ? 'Y' : 'N';
		$bindings = $params['BINDINGS'] ?? [];

		if (!empty($fields['REASON_CANCELED']) && $fields['REASON_CANCELED'] <> '' && $value === 'Y')
		{
			$timelineEntryId = ModificationEntry::create([
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => self::resolveEditorID($params),
				'TEXT' => $fields['REASON_CANCELED'],
				'SETTINGS' => [
					'FIELD' => 'REASON_CANCELED',
				],
				'BINDINGS' => $bindings,
			]);
			$this->sendAddPullEventToBindings($bindings, $timelineEntryId);
		}
	}

	/**
	 * @param $ownerID
	 * @param array $params
	 * @throws Main\ArgumentException
	 */
	public function afterModifyExternalEntity($ownerID, array $params)
	{
		if (!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if ($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$timelineEntryId = MarkEntry::create([
			'MARK_TYPE_ID' =>
				((int)$params['TYPE'] === TimelineMarkType::SUCCESS)
					? TimelineMarkType::SUCCESS
					: TimelineMarkType::FAILED
			,
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
			'ENTITY_ID' => $ownerID,
			'AUTHOR_ID' => $this->getCurrentOrDefaultAuthorId(),
			'SETTINGS' => [
				'MESSAGE' => $params['MESSAGE'] <>'' ? $params['MESSAGE'] : '',
			]
		]);
		$this->sendPullEventOnAdd(
			new ItemIdentifier(\CCrmOwnerType::Order, $ownerID),
			$timelineEntryId
		);
	}

	/**
	 * @param $ownerID
	 * @param array $params
	 * @throws Main\ArgumentException
	 */
	public function onModify($ownerID, array $params)
	{
		if (!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if ($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$currentFields = isset($params['CURRENT_FIELDS']) && is_array($params['CURRENT_FIELDS'])
			? $params['CURRENT_FIELDS'] : array();
		$previousFields = isset($params['PREVIOUS_FIELDS']) && is_array($params['PREVIOUS_FIELDS'])
			? $params['PREVIOUS_FIELDS'] : array();

		$bindings = $params['BINDINGS'] ?? ['ENTITY_TYPE_ID' => \CCrmOwnerType::Order, 'ENTITY_ID' => $ownerID];

		if (isset($currentFields['STATUS_ID']))
		{
			$this->onStatusModify($ownerID, $currentFields, $previousFields, $bindings);
		}
	}

	/**
	 * @param $ownerId
	 * @param $dealId
	 * @param array $params
	 * @throws Main\ArgumentException
	 */
	public function notifyBindingDeal($ownerId, $dealId, array $params)
	{
		if (!is_int($dealId))
		{
			$dealId = (int)$dealId;
		}

		if ($dealId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'dealID');
		}

		$settings = is_array($params['SETTINGS']) ? $params['SETTINGS'] : [];
		$orderFields = is_array($params['ORDER_FIELDS']) ? $params['ORDER_FIELDS'] : [];
		$authorId = self::resolveCreatorID($orderFields);
		if (!empty($settings))
		{
			$bindings = [
				[
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
					'ENTITY_ID' => $dealId,
				]
			];
			$timelineEntryId = OrderEntry::create([
				'ENTITY_ID' => $ownerId,
				'TYPE_CATEGORY_ID' => TimelineType::MODIFICATION,
				'AUTHOR_ID' => $authorId,
				'BINDINGS' => $bindings,
				'SETTINGS' => $settings
			]);
			$this->sendAddPullEventToBindings($bindings, $timelineEntryId);
		}
	}

	/**
	 * @param $ownerId
	 * @param $dealId
	 * @param array $params
	 */
	public function onBindingDealCreation($ownerId, $dealId, array $params)
	{
		if (!is_int($ownerId))
		{
			$ownerId = (int)$ownerId;
		}

		if ($ownerId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		if (!is_int($dealId))
		{
			$dealId = (int)$dealId;
		}

		if ($dealId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'dealID');
		}

		$orderFields = is_array($params['ORDER_FIELDS']) ? $params['ORDER_FIELDS'] : [];
		$authorId = self::resolveCreatorID($orderFields);

		if ($params['IS_NEW_ORDER'] === 'Y')
		{
			$timelineParams = [
				'ORDER_FIELDS' => $orderFields,
				'SETTINGS' => [
					'FIELDS' => [
						'DONE' => ($orderFields['STATUS_ID'] === OrderStatus::getFinalStatus()) ? 'Y' : 'N',
						'CANCELED' => $orderFields['CANCELED'],
					]
				],
				'BINDINGS' => [
					[
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
						'ENTITY_ID' => $dealId
					]
				]
			];

			$this->onCreate($ownerId, $timelineParams);
		}

		$bindings = [
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $ownerId,
			]
		];
		if ($params['IS_NEW_DEAL'] === 'Y')
		{
			$timelineEntryId = ConversionEntry::create([
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $ownerId,
				'AUTHOR_ID' => $authorId,
				'SETTINGS' => [
					'ENTITIES' => [
						[
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
							'ENTITY_ID' => $dealId
						]
					]
				],
				'BINDINGS' => $bindings,
			]);
		}
		else
		{
			$timelineEntryId = LinkEntry::create([
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => $dealId,
				'AUTHOR_ID' => $authorId,
				'BINDINGS' => $bindings,
			]);
		}
		$this->sendAddPullEventToBindings($bindings, $timelineEntryId);
	}

	/**
	 * @param int $ownerId
	 * @param int $dealId
	 */
	final public function markDealAsCreatedFromOrder(int $ownerId, int $dealId): void
	{
		$creationEntry =
			Entity\TimelineTable::query()
				->setSelect(['ID', 'SETTINGS'])
				->where('ASSOCIATED_ENTITY_TYPE_ID', \CCrmOwnerType::Deal)
				->where('ASSOCIATED_ENTITY_ID', $dealId)
				->where('TYPE_ID', TimelineType::CREATION)
				->setLimit(1)
				->fetch()
		;

		if ($creationEntry)
		{
			$settings = $creationEntry['SETTINGS'] ?? [];

			$settings['ORDER'] = [
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $ownerId,
			];

			Entity\TimelineTable::update($creationEntry['ID'], ['SETTINGS' => $settings]);
		}
	}

	/**
	 * @param $ownerID
	 * @param $entryTypeID
	 * @param array $fields
	 * @return Main\Result
	 * @throws Main\ArgumentException
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
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'TYPE_ID' => $entryTypeID,
			],
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
			else
			{
				$row['SETTINGS'] = $settings;
				$items = array($row['ID'] => $row);
				TimelineManager::prepareDisplayData($items);
				if(Main\Loader::includeModule('pull') && \CPullOptions::GetQueueServerStatus())
				{
					$tag = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Order, $ownerID);
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

		return $result;
	}

	/**
	 * @param $ID
	 * @return array|false|null
	 */
	protected static function getEntity($ID)
	{
		$resultDB = Order\Order::getList(
			array(
				'filter' => array('=ID' => $ID),
				'select' => array('ID', 'DATE_INSERT', 'CREATED_BY')
			)
		);
		return is_object($resultDB) ? $resultDB->fetch() : null;
	}

	/**
	 * @param array $fields
	 * @return int
	 */
	protected static function resolveCreatorID(array $fields)
	{
		$authorId = 0;
		if (isset($fields['CREATED_BY']))
		{
			$authorId = (int)$fields['CREATED_BY'];
		}

		if ($authorId <= 0 && isset($fields['RESPONSIBLE_ID']))
		{
			$authorId = (int)$fields['RESPONSIBLE_ID'];
		}

		if ($authorId <= 0)
		{
			$authorId = self::getDefaultAuthorId();
		}

		return $authorId;
	}

	/**
	 * @param array $fields
	 * @return int
	 */
	protected static function resolveEditorID(array $fields)
	{
		$authorID = 0;

		if(isset($fields['RESPONSIBLE_ID']))
		{
			$authorID = (int)$fields['RESPONSIBLE_ID'];
		}

		if(isset($fields['EMP_PAYED_ID']))
		{
			$authorID = (int)$fields['CREATED_BY'];
		}

		if(isset($fields['EMP_CANCELED_ID']))
		{
			$authorID = (int)$fields['CREATED_BY'];
		}

		if(isset($fields['EMP_STATUS_ID']))
		{
			$authorID = (int)$fields['EMP_STATUS_ID'];
		}

		if($authorID <= 0)
		{
			$authorID = self::getDefaultAuthorId();
		}

		return $authorID;
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
		$fields = $settings['FIELDS'] ?? [];

		if ($typeID === TimelineType::MODIFICATION)
		{
			$fieldName = isset($settings['FIELD']) ? $settings['FIELD'] : '';
			if($fieldName === 'STATUS_ID')
			{
				$data['TITLE'] = Loc::getMessage('CRM_ORDER_MODIFICATION_STATUS');
				$data['START_NAME'] = isset($settings['START_NAME']) ? $settings['START_NAME'] : $settings['START'];
				$data['FINISH_NAME'] = isset($settings['FINISH_NAME']) ? $settings['FINISH_NAME'] : $settings['FINISH'];
			}
			elseif ($fieldName === 'CANCELED')
			{
				if ($settings['VALUE'] === 'Y')
				{
					$data['TITLE'] = Loc::getMessage('CRM_ORDER_MODIFICATION_CANCELED');
					$data['START_NAME'] = Loc::getMessage('CRM_ORDER_ACTIVE');
					$data['FINISH_NAME'] = Loc::getMessage('CRM_ORDER_CANCELLED');
				}
				else
				{
					$data['TITLE'] = Loc::getMessage('CRM_ORDER_MODIFICATION_RESTORED');
					$data['START_NAME'] = Loc::getMessage('CRM_ORDER_CANCELLED');
					$data['FINISH_NAME'] = Loc::getMessage('CRM_ORDER_ACTIVE');
				}
			}
			elseif ($fieldName === 'REASON_CANCELED')
			{
				$data['TITLE'] = Loc::getMessage('CRM_ORDER_MODIFICATION_REASON_CANCELED');
				if (!empty($data['COMMENT']))
				{
					$data['FINISH_NAME'] = $data['COMMENT'];
				}
			}
			$data['MODIFIED_FIELD'] = $fieldName;
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::CONVERSION)
		{
			$data['TITLE'] =  Loc::getMessage('CRM_ORDER_CONVERSION');
			$entities = isset($settings['ENTITIES']) && is_array($settings['ENTITIES'])
				? $settings['ENTITIES'] : array();

			$entityInfos = array();
			foreach($entities as $entityData)
			{
				$entityTypeID = isset($entityData['ENTITY_TYPE_ID']) ? (int)$entityData['ENTITY_TYPE_ID'] : 0;
				$entityID = isset($entityData['ENTITY_ID']) ? (int)$entityData['ENTITY_ID'] : 0;

				if(\CCrmOwnerType::TryGetEntityInfo($entityTypeID, $entityID, $entityInfo, false))
				{
					$entityInfo['ENTITY_TYPE_ID'] = $entityTypeID;
					$entityInfo['ENTITY_ID'] = $entityID;
					$entityInfos[] = $entityInfo;
				}
			}
			$data['ENTITIES'] = $entityInfos;
			unset($data['SETTINGS']);

		}
		elseif($typeID === TimelineType::MARK)
		{
			$data['MESSAGE'] = isset($settings['MESSAGE']) ? $settings['MESSAGE'] : '';
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::ORDER)
		{
			if (!isset($data['ASSOCIATED_ENTITY']))
			{
				$entityInfos = [$data['ASSOCIATED_ENTITY_ID'] => []];
				\CCrmOwnerType::PrepareEntityInfoBatch($data['TYPE_ID'], $entityInfos, true, ['ENABLE_RESPONSIBLE' => true]);

				$data['ASSOCIATED_ENTITY'] = $entityInfos[$data['ASSOCIATED_ENTITY_ID']];
			}

			$data['TITLE'] = \CCrmOwnerType::GetDescription(\CCrmOwnerType::Order);
			$data = array_merge($data, $settings);

			if (isset($data['FIELDS']['VIEWED']) && $data['FIELDS']['VIEWED'] === 'Y')
			{
				$data['TITLE'] = \CCrmOwnerType::GetDescription(\CCrmOwnerType::OrderPayment);
				$data['ASSOCIATED_ENTITY']['TITLE'] = Loc::getMessage('CRM_PAYMENT_VIEWED_TITLE');
				$data['ASSOCIATED_ENTITY']['HTML_TITLE'] = Loc::getMessage(
					'CRM_ORDER_VIEWED_HTML_TITLE_2',
					[
						'#ORDER_ID#' => $data['FIELDS']['ORDER_ID'],
						'#DATE#' => $data['ASSOCIATED_ENTITY']['DATE'],
						'#SUM#' => $data['ASSOCIATED_ENTITY']['SUM_WITH_CURRENCY'],
					]
				);
				$data['ASSOCIATED_ENTITY']['VIEWED'] = 'Y';
			}
			elseif (isset($data['FIELDS']['SENT']) && $data['FIELDS']['SENT'] === 'Y')
			{
				if ($fields['DESTINATION'])
				{
					$destinationTitle = Loc::getMessage('CRM_ORDER_DESTINATION_TITLE_'.$fields['DESTINATION']);
					if ($destinationTitle)
					{
						$data['ASSOCIATED_ENTITY']['DESTINATION_TITLE'] = $destinationTitle;
					}
				}

				$data['TITLE'] = \CCrmOwnerType::GetDescription(\CCrmOwnerType::OrderPayment);
				$data['ASSOCIATED_ENTITY']['HTML_TITLE'] = Loc::getMessage(
					'CRM_ORDER_VIEWED_HTML_TITLE_2',
					[
						'#ORDER_ID#' => $data['FIELDS']['ORDER_ID'],
						'#DATE#' => $data['ASSOCIATED_ENTITY']['DATE'],
						'#SUM#' => $data['ASSOCIATED_ENTITY']['SUM_WITH_CURRENCY'],
					]
				);
				$data['ASSOCIATED_ENTITY']['SENT'] = 'Y';
			}

			$data['ASSOCIATED_ENTITY']['PAYMENT_ID'] = $fields['PAYMENT_ID'] ?? null;
			$data['ASSOCIATED_ENTITY']['SHIPMENT_ID'] = $fields['SHIPMENT_ID'] ?? null;

			unset($data['SETTINGS']);
		}

		return parent::prepareHistoryDataModel($data, $options);
	}

	/**
	 * @param $ownerId
	 * @param $currentFields
	 * @param $previousFields
	 * @param array $bindings
	 */
	protected function onStatusModify($ownerId, $currentFields, $previousFields, $bindings = [])
	{
		$historyEntryID = null;
		$prevStageID = isset($previousFields['STATUS_ID']) ? $previousFields['STATUS_ID'] : '';
		$currentStageID = isset($currentFields['STATUS_ID']) ? $currentFields['STATUS_ID'] : $prevStageID;

		$authorID = self::resolveEditorID($currentFields);

		$stageNames = OrderStatus::getListInCrmFormat();
		$timelineEntryId = ModificationEntry::create(
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $ownerId,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => [
					'FIELD' => 'STATUS_ID',
					'START' => $prevStageID,
					'FINISH' => $currentStageID,
					'START_NAME' => $stageNames[$prevStageID]['NAME'] ?? $prevStageID,
					'FINISH_NAME' => $stageNames[$currentStageID]['NAME'] ?? $currentStageID
				],
				'BINDINGS' => $bindings
			]
		);
		$this->sendAddPullEventToBindings($bindings, $timelineEntryId);
	}

	/**
	 * @param $ownerId
	 * @param $params
	 */
	public function onPay($ownerId, $params)
	{
		$this->notifyOrderEntry($ownerId, $params);
	}

	/**
	 * @param $ownerId
	 * @param $params
	 */
	public function onDeduct($ownerId, $params)
	{
		$this->notifyOrderEntry($ownerId, $params);
	}

	/**
	 * @param $ownerId
	 * @param $params
	 * @throws Main\ArgumentException
	 */
	private function notifyOrderEntry($ownerId, $params)
	{
		if ($ownerId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.');
		}

		$settings = $params['SETTINGS'] ?? [];
		$orderFields = $params['ORDER_FIELDS'] ?? [];
		$bindings = $params['BINDINGS'] ?? [];

		$authorId = self::resolveCreatorID($orderFields);
		if (!empty($settings))
		{
			$timelineEntryId = OrderEntry::create([
				'ENTITY_ID' => $ownerId,
				'TYPE_CATEGORY_ID' => TimelineType::MODIFICATION,
				'AUTHOR_ID' => $authorId,
				'BINDINGS' => $bindings,
				'SETTINGS' => $settings
			]);
			$this->sendAddPullEventToBindings($bindings, $timelineEntryId);
		}
	}

	/**
	 * @param $ownerId
	 * @param $params
	 */
	public function onSend($ownerId, $params)
	{
		$this->notifyOrderEntry($ownerId, $params);

		$order = Order\Order::load($ownerId);

		/** @var Order\EntityBinding $binding */
		$binding = $order->getEntityBinding();
		if (
			$binding
			&& $binding->getOwnerTypeId() === \CCrmOwnerType::Deal
		)
		{
			$this->changeOrderStageDealOnSentNoViewed(
				$binding->getOwnerId()
			);
		}
	}

	private function changeOrderStageDealOnSentNoViewed($dealId)
	{
		$fields = ['ORDER_STAGE' => Order\OrderStage::SENT_NO_VIEWED];

		$deal = new \CCrmDeal(false);
		$deal->Update($dealId, $fields);
	}

	/**
	 * @param $ownerId
	 * @param array $params
	 */
	public function onManualContinuePay($ownerId, array $params)
	{
		$params['SETTINGS']['FIELDS']['MANUAL_CONTINUE_PAY'] = 'Y';

		$this->notifyOrderEntry($ownerId, $params);
	}

	protected function sendAddPullEventToBindings(array $bindings, int $timelineEntryId): void
	{
		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$timelineEntryId
			);
		}
	}
}
