<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Deal\PaymentDocumentsRepository;
use Bitrix\Crm\Order\DealBinding;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\Payment;
use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\History\DealStageHistoryEntry;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Sale\Cashbox\CheckManager;

Loc::loadMessages(__FILE__);

class DealController extends EntityController
{
	//region Event Names
	const ADD_EVENT_NAME = 'timeline_deal_add';
	const REMOVE_EVENT_NAME = 'timeline_deal_remove';
	const RESTORE_EVENT_NAME = 'timeline_deal_restore';
	//endregion

	//region Singleton
	/** @var DealController|null */
	protected static $instance = null;
	/**
	 * @return DealController
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new DealController();
		}
		return self::$instance;
	}
	//endregion
	//region EntityController
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Deal;
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

		$settings = array();
		if(isset($fields['LEAD_ID']) && $fields['LEAD_ID'] > 0)
		{
			$settings['BASE'] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
				'ENTITY_ID' => (int)$fields['LEAD_ID']
			);
		}

		if (isset($fields['ORDER_ID']) && $fields['ORDER_ID'] > 0)
		{
			$settings['ORDER'] = [
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => (int)$fields['ORDER_ID'],
			];
		}

		$authorID = self::resolveCreatorID($fields);
		$historyEntryID = CreationEntry::create(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => $settings,
				'BINDINGS' => array(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
						'ENTITY_ID' => $ownerID
					)
				)
			)
		);

		//region Register Link
		$linkParams = array(
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
			'ENTITY_ID' => $ownerID,
			'AUTHOR_ID' => $authorID
		);

		$leadID = isset($fields['LEAD_ID']) ? (int)$fields['LEAD_ID'] : 0;
		if($leadID > 0)
		{
			$linkParams['BASE_INFO'] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
				'ENTITY_ID' => $leadID,
			);
		}

		$contactBindings = isset($params['CONTACT_BINDINGS']) && is_array($params['CONTACT_BINDINGS'])
			? $params['CONTACT_BINDINGS'] : array();
		if(!empty($contactBindings))
		{
			foreach($contactBindings as $contactBinding)
			{
				if(isset($contactBinding['CONTACT_ID']))
				{
					ContactController::getInstance()->onLink($contactBinding['CONTACT_ID'], $linkParams);
				}
			}
		}

		$companyID = isset($fields['COMPANY_ID']) ? (int)$fields['COMPANY_ID'] : 0;
		if($companyID > 0)
		{
			CompanyController::getInstance()->onLink($companyID, $linkParams);
		}
		//endregion

		$enableHistoryPush = $historyEntryID > 0;
		if($enableHistoryPush && Main\Loader::includeModule('pull'))
		{
			$pushParams = array('ID' => $ownerID);
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

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Deal, 0);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => self::ADD_EVENT_NAME,
					'params' => $pushParams,
				)
			);
		}

		$isManualOpportunity = isset($fields['IS_MANUAL_OPPORTUNITY']) ? $fields['IS_MANUAL_OPPORTUNITY'] : 'N';
		if ($isManualOpportunity === 'Y')
		{
			$this->createManualOpportunityModificationEntry($ownerID, $authorID, 'N', $isManualOpportunity);
		}
	}
	public function onModify($ownerID, array $params)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$currentFields = isset($params['CURRENT_FIELDS']) && is_array($params['CURRENT_FIELDS'])
			? $params['CURRENT_FIELDS'] : array();
		$previousFields = isset($params['PREVIOUS_FIELDS']) && is_array($params['PREVIOUS_FIELDS'])
			? $params['PREVIOUS_FIELDS'] : array();

		$prevCompanyID = isset($previousFields['COMPANY_ID']) ? (int)$previousFields['COMPANY_ID'] : 0;
		$curCompanyID = isset($currentFields['COMPANY_ID']) ? (int)$currentFields['COMPANY_ID'] : $prevCompanyID;

		$contactBindings = isset($params['CONTACT_BINDINGS']) && is_array($params['CONTACT_BINDINGS'])
			? $params['CONTACT_BINDINGS'] : null;
		if($contactBindings === null)
		{
			$contactBindings = DealContactTable::getDealBindings($ownerID);
		}

		$addedContactBindings = isset($params['ADDED_CONTACT_BINDINGS']) && is_array($params['ADDED_CONTACT_BINDINGS'])
			? $params['ADDED_CONTACT_BINDINGS'] : array();

		$prevStageID = isset($previousFields['STAGE_ID']) ? $previousFields['STAGE_ID'] : '';
		$curStageID = isset($currentFields['STAGE_ID']) ? $currentFields['STAGE_ID'] : $prevStageID;

		$categoryID = isset($previousFields['CATEGORY_ID']) ? (int)$previousFields['CATEGORY_ID'] : -1;
		if($categoryID < 0)
		{
			$categoryID = \CCrmDeal::GetCategoryID($ownerID);
		}

		$authorID = self::resolveEditorID($currentFields);
		if($prevStageID !== $curStageID)
		{
			$stageNames = \CCrmDeal::GetStageNames($categoryID);
			$historyEntryID = ModificationEntry::create(
				array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
					'ENTITY_ID' => $ownerID,
					'AUTHOR_ID' => $authorID,
					'SETTINGS' => array(
						'FIELD' => 'STAGE_ID',
						'START' => $prevStageID,
						'FINISH' => $curStageID,
						'START_NAME' => isset($stageNames[$prevStageID]) ? $stageNames[$prevStageID] : $prevStageID,
						'FINISH_NAME' => isset($stageNames[$curStageID]) ? $stageNames[$curStageID] : $curStageID
					)
				)
			);

			$enableHistoryPush = $historyEntryID > 0;
			if(($enableHistoryPush) && Main\Loader::includeModule('pull'))
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

				$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Deal, $ownerID);
				\CPullWatch::AddToStack(
					$tag,
					array(
						'module_id' => 'crm',
						'command' => 'timeline_activity_add',
						'params' => $pushParams,
					)
				);
			}

			$curSemanticID = \CCrmDeal::GetSemanticID($curStageID, $categoryID);
			$prevSemanticID = \CCrmDeal::GetSemanticID($prevStageID, $categoryID);
			if($curSemanticID !== PhaseSemantics::PROCESS && $curSemanticID !== $prevSemanticID)
			{
				$bindings = array(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
						'ENTITY_ID' => $ownerID
					)
				);

				if($curCompanyID > 0)
				{
					$bindings[] =  array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
						'ENTITY_ID' => $curCompanyID
					);
				}

				foreach($contactBindings as $contactBinding)
				{
					if(isset($contactBinding['CONTACT_ID']) && $contactBinding['CONTACT_ID'] > 0)
					{
						$bindings[] = array(
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Contact,
							'ENTITY_ID' => (int)$contactBinding['CONTACT_ID']
						);
					}
				}

				if(!empty($bindings))
				{
					$markHistoryEntryID = MarkEntry::create(
						array(
							'MARK_TYPE_ID' => $curSemanticID === PhaseSemantics::SUCCESS
								? TimelineMarkType::SUCCESS : TimelineMarkType::FAILED,
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
							'ENTITY_ID' => $ownerID,
							'AUTHOR_ID' => $authorID,
							'BINDINGS' => $bindings
						)
					);

					if($markHistoryEntryID > 0)
					{
						foreach($bindings as $binding)
						{
							self::pushHistoryEntry(
								$markHistoryEntryID,
								TimelineEntry::prepareEntityPushTag($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
								'timeline_activity_add'
							);
						}
					}

					$orderIdList = [];

					$dbRes = DealBinding::getList([
						'select' => ['ORDER_ID'],
						'filter' => [
							'=DEAL_ID' => $ownerID,
						]
					]);

					while ($data = $dbRes->fetch())
					{
						$orderIdList[] = $data['ORDER_ID'];
					}

					if ($orderIdList)
					{
						if (\CCrmSaleHelper::isWithOrdersMode())
						{
							$entryId = FinalSummaryEntry::create([
								'ENTITY_ID' => $ownerID,
								'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
								'TYPE_CATEGORY_ID' => TimelineType::CREATION,
								'AUTHOR_ID' => $authorID,
								'SETTINGS' => [
									'ORDER_IDS' => $orderIdList
								],
								'BINDINGS' => [
									[
										'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
										'ENTITY_ID' => $ownerID
									]
								]
							]);
						}
						else
						{
							$entryId = FinalSummaryDocumentsEntry::create([
								'ENTITY_ID' => $ownerID,
								'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
								'TYPE_CATEGORY_ID' => TimelineType::CREATION,
								'AUTHOR_ID' => $authorID,
								'SETTINGS' => [
									'ORDER_IDS' => $orderIdList
								],
								'BINDINGS' => [
									[
										'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
										'ENTITY_ID' => $ownerID
									]
								]
							]);
						}

						self::pushHistoryEntry(
							$entryId,
							TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Deal, $ownerID),
							'timeline_activity_add'
						);
					}
				}
			}
		}

		$prevIsManualOpportunity = isset($previousFields['IS_MANUAL_OPPORTUNITY']) ? $previousFields['IS_MANUAL_OPPORTUNITY'] : 'N';
		$curIsManualOpportunity = isset($currentFields['IS_MANUAL_OPPORTUNITY']) ? $currentFields['IS_MANUAL_OPPORTUNITY'] : $prevIsManualOpportunity;

		if ($prevIsManualOpportunity !== $curIsManualOpportunity)
		{
			$this->createManualOpportunityModificationEntry($ownerID, $authorID, $prevIsManualOpportunity, $curIsManualOpportunity);
		}

		//region Register Link
		$linkParams = array(
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
			'ENTITY_ID' => $ownerID,
			'AUTHOR_ID' => $authorID
		);

		$leadID = isset($currentFields['LEAD_ID'])
			? (int)$currentFields['LEAD_ID']
			: (isset($previousFields['LEAD_ID']) ? (int)$previousFields['LEAD_ID'] : 0);

		if($leadID > 0)
		{
			$linkParams['BASE_INFO'] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
				'ENTITY_ID' => $leadID,
			);
		}

		if(!empty($addedContactBindings))
		{
			foreach($addedContactBindings as $contactBinding)
			{
				if(isset($contactBinding['CONTACT_ID']) && $contactBinding['CONTACT_ID'] > 0)
				{
					ContactController::getInstance()->onLink($contactBinding['CONTACT_ID'], $linkParams);
				}
			}
		}

		if($curCompanyID > 0 && $curCompanyID !== $prevCompanyID)
		{
			CompanyController::getInstance()->onLink($curCompanyID, $linkParams);
		}
		//endregion
	}

	public function onDelete($ownerID, array $params)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		if(Main\Loader::includeModule('pull'))
		{
			$pushParams = array('ID' => $ownerID);

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Deal, 0);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => self::REMOVE_EVENT_NAME,
					'params' => $pushParams,
				)
			);
		}
	}
	public function onRestore($ownerID, array $params)
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

		$historyEntryID = RestorationEntry::create(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => self::resolveCreatorID($fields),
				'SETTINGS' => array(),
				'BINDINGS' => array(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
						'ENTITY_ID' => $ownerID
					)
				)
			)
		);

		$enableHistoryPush = $historyEntryID > 0;
		if($enableHistoryPush && Main\Loader::includeModule('pull'))
		{
			$pushParams = array('ID' => $ownerID);
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

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Lead, 0);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => self::RESTORE_EVENT_NAME,
					'params' => $pushParams,
				)
			);
		}
	}
	public function getSupportedPullCommands()
	{
		return array(
			'add' => self::ADD_EVENT_NAME,
			'remove' => self::REMOVE_EVENT_NAME,
			'restore' => self::RESTORE_EVENT_NAME
		);
	}

	/**
	 * Register existed entity in retrospect mode.
	 * @param int $ownerID Entity ID
	 * @return void
	 */
	public function register($ownerID, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$enableCheck = isset($options['EXISTS_CHECK']) ? (bool)$options['EXISTS_CHECK'] : true;
		if($enableCheck && TimelineEntry::isAssociatedEntityExist(\CCrmOwnerType::Deal, $ownerID))
		{
			return;
		}

		$fields = self::getEntity($ownerID);
		if(!is_array($fields))
		{
			return;
		}

		//region Register Creation
		CreationEntry::create(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => self::resolveCreatorID($fields),
				'CREATED' => isset($fields['DATE_CREATE']) ? DateTime::tryParse($fields['DATE_CREATE']) : null,
				'BINDINGS' => array(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
						'ENTITY_ID' => $ownerID
					)
				)
			)
		);
		//endregion
		//region Register Stage History
		$authorID = self::resolveEditorID($fields);
		$historyItems = DealStageHistoryEntry::getAll($ownerID);
		if(count($historyItems) > 1)
		{
			$initialItem = array_shift($historyItems);
			$stageNames = \CCrmDeal::GetStageNames(
				isset($initialItem['CATEGORY_ID']) ? (int)$initialItem['CATEGORY_ID'] : 0
			);
			$prevStageID = isset($initialItem['STAGE_ID']) ? $initialItem['STAGE_ID'] : '';
			foreach($historyItems as $item)
			{
				$curStageID = isset($item['STAGE_ID']) ? $item['STAGE_ID'] : '';
				if($curStageID === '')
				{
					continue;
				}

				if($prevStageID !== '')
				{
					ModificationEntry::create(
						array(
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
							'ENTITY_ID' => $ownerID,
							'AUTHOR_ID' => $authorID,
							'SETTINGS' => array(
								'FIELD' => 'STAGE_ID',
								'START' => $prevStageID,
								'FINISH' => $curStageID,
								'START_NAME' => isset($stageNames[$prevStageID]) ? $stageNames[$prevStageID] : $prevStageID,
								'FINISH_NAME' => isset($stageNames[$curStageID]) ? $stageNames[$curStageID] : $curStageID
							)
						)
					);
				}
				$prevStageID = $curStageID;
			}
		}
		//endregion
		//region Register Live Feed Messages
		LiveFeed::registerEntityMessages(\CCrmOwnerType::Deal, $ownerID);
		//endregion
	}
	protected static function getEntity($ID)
	{
		$dbResult = \CCrmDeal::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array(
				'ID', 'TITLE',
				'CATEGORY_ID', 'STAGE_ID', 'LEAD_ID',
				'DATE_CREATE', 'DATE_MODIFY', 'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID'
			)
		);
		return is_object($dbResult) ? $dbResult->Fetch() : null;
	}
	protected static function resolveCreatorID(array $fields)
	{
		$authorID = 0;
		if(isset($fields['CREATED_BY_ID']))
		{
			$authorID = (int)$fields['CREATED_BY_ID'];
		}

		if($authorID <= 0 && isset($fields['MODIFY_BY_ID']))
		{
			$authorID = (int)$fields['MODIFY_BY_ID'];
		}

		if($authorID <= 0 && isset($fields['ASSIGNED_BY_ID']))
		{
			$authorID = (int)$fields['ASSIGNED_BY_ID'];
		}

		if($authorID <= 0)
		{
			//Set portal admin as default creator
			$authorID = 1;
		}

		return $authorID;
	}
	protected static function resolveEditorID(array $fields)
	{
		$authorID = 0;
		if(isset($fields['MODIFY_BY_ID']))
		{
			$authorID = (int)$fields['MODIFY_BY_ID'];
		}

		if($authorID <= 0 && isset($fields['CREATED_BY_ID']))
		{
			$authorID = (int)$fields['CREATED_BY_ID'];
		}

		if($authorID <= 0 && isset($fields['ASSIGNED_BY_ID']))
		{
			$authorID = (int)$fields['ASSIGNED_BY_ID'];
		}

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
		$settings = isset($data['SETTINGS']) ? $data['SETTINGS'] : array();
		$culture = Main\Context::getCurrent()->getCulture();

		if($typeID === TimelineType::CREATION)
		{
			$data['TITLE'] =  Loc::getMessage('CRM_DEAL_CREATION');

			$associatedEntityTypeID = isset($data['ASSOCIATED_ENTITY_TYPE_ID'])
				? (int)$data['ASSOCIATED_ENTITY_TYPE_ID'] : \CCrmOwnerType::Deal;
			if($associatedEntityTypeID === \CCrmOwnerType::SuspendedDeal)
			{
				$data['LEGEND'] = Loc::getMessage('CRM_DEAL_MOVING_TO_RECYCLEBIN');
			}
			else
			{
				$base = isset($settings['BASE']) ? $settings['BASE'] : null;
				if(is_array($base))
				{
					$entityTypeID = isset($base['ENTITY_TYPE_ID']) ? $base['ENTITY_TYPE_ID'] : 0;
					$entityID = isset($base['ENTITY_ID']) ? $base['ENTITY_ID'] : 0;
					if(\CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0)
					{
						$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
						$data['BASE'] = array('CAPTION' => Loc::getMessage("CRM_DEAL_BASE_CAPTION_{$entityTypeName}"));

						if(\CCrmOwnerType::TryGetEntityInfo($entityTypeID, $entityID, $baseEntityInfo, false))
						{
							$data['BASE']['ENTITY_INFO'] = $baseEntityInfo;
						}
					}
				}

				$order = $settings['ORDER'] ?? null;
				if ($order)
				{
					$orderId = $order['ENTITY_ID'];
					$order = Order::load($orderId);
					if ($order)
					{
						$data['ASSOCIATED_ENTITY']['ORDER']['ID'] = $orderId;
						$data['ASSOCIATED_ENTITY']['ORDER']['SHOW_URL'] = \CComponentEngine::MakePathFromTemplate(
							Main\Config\Option::get('crm', 'path_to_order_details'),
							['order_id' => $orderId]
						);
						$data['ASSOCIATED_ENTITY']['ORDER']['SUM'] = \CCrmCurrency::MoneyToString(
							$order->getPrice(),
							$order->getCurrency()
						);
						$data['ASSOCIATED_ENTITY']['ORDER']['ORDER_DATE'] = \FormatDate(
							$culture->getLongDateFormat(), $order->getDateInsert()->getTimestamp()
						);
					}
				}
			}
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::MODIFICATION)
		{
			$fieldName = isset($settings['FIELD']) ? $settings['FIELD'] : '';
			if($fieldName === 'STAGE_ID')
			{
				$data['TITLE'] =  Loc::getMessage('CRM_DEAL_MODIFICATION_STAGE');
				$data['START_NAME'] = isset($settings['START_NAME']) ? $settings['START_NAME'] : $settings['START'];
				$data['FINISH_NAME'] = isset($settings['FINISH_NAME']) ? $settings['FINISH_NAME'] : $settings['FINISH'];
			}
			if($fieldName === 'IS_MANUAL_OPPORTUNITY')
			{
				$data['TITLE'] =  Loc::getMessage('CRM_DEAL_MODIFICATION_IS_MANUAL_OPPORTUNITY');
				$data['START_NAME'] = isset($settings['START_NAME']) ? $settings['START_NAME'] : $settings['START'];
				$data['FINISH_NAME'] = isset($settings['FINISH_NAME']) ? $settings['FINISH_NAME'] : $settings['FINISH'];
			}
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::CONVERSION)
		{
			$data['TITLE'] =  Loc::getMessage('CRM_DEAL_CREATION_BASED_ON');
			$entities = isset($settings['ENTITIES']) && is_array($settings['ENTITIES'])
				? $settings['ENTITIES'] : array();

			$entityInfos = array();
			foreach($entities as $entityData)
			{
				$entityTypeID = isset($entityData['ENTITY_TYPE_ID']) ? (int)$entityData['ENTITY_TYPE_ID'] : 0;
				$entityID = isset($entityData['ENTITY_ID']) ? (int)$entityData['ENTITY_ID'] : 0;

				if(\CCrmOwnerType::TryGetEntityInfo($entityTypeID, $entityID, $entityInfo, false) && $entityTypeID)
				{
					$entityInfo['ENTITY_TYPE_ID'] = $entityTypeID;
					$entityInfo['ENTITY_ID'] = $entityID;
					$entityInfos[] = $entityInfo;
				}
			}

			$data['ENTITIES'] = $entityInfos;
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::RESTORATION)
		{
			$data['TITLE'] =  Loc::getMessage('CRM_DEAL_RESTORATION');
		}
		elseif($typeID === TimelineType::FINAL_SUMMARY)
		{
			$data['RESULT'] = [];
			foreach ($data['SETTINGS']['ORDER_IDS'] as $orderId)
			{
				$order = Order::load($orderId);
				if (!$order)
				{
					continue;
				}

				$row['PAYMENTS'] = [];

				/** @var Payment $payment */
				foreach ($order->getPaymentCollection() as $payment)
				{
					if ($payment->isPaid())
					{
						$row['PAYMENTS'][] = [
							'PRICE_FORMAT' => \CCrmCurrency::MoneyToString(
								$payment->getField('SUM'),
								$order->getCurrency()
							),
							'DATE_PAID' => FormatDate($culture->getLongDateFormat(), $payment->getField('DATE_PAID')->getTimestamp()),
						];
					}
				}

				$row['ORDER'] = [
					'TITLE' => Loc::getMessage(
						'CRM_DEAL_SUMMARY_ORDER',
						[
							'#ORDER_ID#' => $orderId,
							'#ORDER_DATE#' => FormatDate($culture->getLongDateFormat(), $order->getDateInsert()->getTimestamp()),
						]
					),
					'SHOW_URL' => \CComponentEngine::MakePathFromTemplate(
						Main\Config\Option::get('crm', 'path_to_order_details'),
						['order_id' => $orderId]
					),
					'PRICE_FORMAT' => \CCrmCurrency::MoneyToString(
						$order->getPrice(),
						$order->getCurrency()
					),
					'SUM_FOR_PAID_FORMAT' => \CCrmCurrency::MoneyToString(
						$order->getPrice() - $order->getSumPaid(),
						$order->getCurrency()
					),
					'IS_PAID' => $order->isPaid(),
				];

				$basePriceOrder = $order->getBasket()->getBasePrice() + $order->getShipmentCollection()->getBasePriceDelivery();
				if (abs($basePriceOrder - $order->getPrice()) > 1e-5)
				{
					$row['ORDER']['BASE_PRICE_FORMAT'] = \CCrmCurrency::MoneyToString(
						$order->getBasket()->getBasePrice() + $order->getShipmentCollection()->getBasePriceDelivery(),
						$order->getCurrency()
					);
				}

				$row['BASKET'] = [
					'BASE_PRICE_FORMAT' => \CCrmCurrency::MoneyToString(
						$order->getBasket()->getBasePrice(),
						$order->getCurrency()
					),
					'PRICE_FORMAT' => \CCrmCurrency::MoneyToString(
						$order->getBasket()->getPrice(),
						$order->getCurrency()
					),
				];

				$row['CHECK'] = $this->getCheckData($orderId);

				$data['RESULT'][] = $row;
			}
		}
		elseif ($typeID === TimelineType::FINAL_SUMMARY_DOCUMENTS)
		{
			$data['RESULT'] = [];

			if ((int)$data['ASSOCIATED_ENTITY_TYPE_ID'] === \CCrmOwnerType::Deal)
			{
				$dealId = (int)$data['ASSOCIATED_ENTITY_ID'];

				/** @var PaymentDocumentsRepository */
				$repository = ServiceLocator::getInstance()->get('crm.deal.paymentDocumentsRepository');
				$result = $repository->getDocumentsForDeal($dealId);
				if ($result->isSuccess())
				{
					$data['RESULT']['TIMELINE_SUMMARY_OPTIONS'] = $result->getData();
				}

				$data['RESULT']['CHECKS'] = [];
				foreach ($settings['ORDER_IDS'] as $orderId)
				{
					$data['RESULT']['CHECKS'][] = $this->getCheckData($orderId);
				}

				$data['RESULT']['CHECKS'] = array_merge(...$data['RESULT']['CHECKS']);
			}
		}

		return parent::prepareHistoryDataModel($data, $options);
	}

	/**
	 * @param $orderId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function getCheckData($orderId)
	{
		$culture = Main\Context::getCurrent()->getCulture();
		$result = [];

		$dbRes = CheckManager::getList([
			'select' => ['ID'],
			'filter' => [
				'=ORDER_ID' => $orderId,
				'=STATUS' => 'Y'
			]
		]);

		while ($data = $dbRes->fetch())
		{
			$check = CheckManager::getObjectById($data['ID']);

			$result[] = [
				'TITLE' => Loc::getMessage('CRM_DEAL_CHECK_TITLE', [
					'#NAME#' => $check::getName(),
					'#DATE_PRINT#' => FormatDate($culture->getLongDateFormat(), $check->getField('DATE_CREATE')->getTimestamp())
				]),
				'URL' => $check->getUrl()
			];
		}

		return $result;
	}
	//endregion

	protected function createManualOpportunityModificationEntry($ownerId, $authorId, $prevValue, $curValue)
	{
		$names = [
			'N' => Loc::getMessage('CRM_DEAL_MODIFICATION_IS_MANUAL_OPPORTUNITY_N'),
			'Y' => Loc::getMessage('CRM_DEAL_MODIFICATION_IS_MANUAL_OPPORTUNITY_Y'),
		];
		$historyEntryID = ModificationEntry::create(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => $ownerId,
				'AUTHOR_ID' => $authorId,
				'SETTINGS' => array(
					'FIELD' => 'IS_MANUAL_OPPORTUNITY',
					'START' => $prevValue,
					'FINISH' => $curValue,
					'START_NAME' => isset($names[$prevValue]) ? $names[$prevValue] : $prevValue,
					'FINISH_NAME' => isset($names[$curValue]) ? $names[$curValue] : $curValue
				)
			)
		);

		$enableHistoryPush = $historyEntryID > 0;
		if(($enableHistoryPush) && Main\Loader::includeModule('pull'))
		{
			$pushParams = array();
			$historyFields = TimelineEntry::getByID($historyEntryID);
			if (is_array($historyFields))
			{
				$pushParams['HISTORY_ITEM'] = $this->prepareHistoryDataModel(
					$historyFields,
					array('ENABLE_USER_INFO' => true)
				);
			}
			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Deal, $ownerId);

			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => 'timeline_activity_add',
					'params' => $pushParams,
				)
			);
		}
	}
}