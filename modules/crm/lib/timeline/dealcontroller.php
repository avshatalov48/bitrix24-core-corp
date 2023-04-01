<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm;
use Bitrix\Crm\Data\EntityFieldsHelper;
use Bitrix\Crm\Entity\PaymentDocumentsRepository;
use Bitrix\Crm\History\DealStageHistoryEntry;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter\SignDocument;
use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class DealController extends EntityController implements Interfaces\FinalSummaryController
{
	use Crm\Timeline\Traits\FinalSummaryControllerTrait;

	//region Event Names
	const ADD_EVENT_NAME = 'timeline_deal_add';
	const REMOVE_EVENT_NAME = 'timeline_deal_remove';
	const RESTORE_EVENT_NAME = 'timeline_deal_restore';
	//endregion

	//region EntityController
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Deal;
	}

	public function onConvert($ownerID, array $params)
	{
		$this->onConvertImplementation($ownerID, $params);
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
		if (is_array($fields))
		{
			$fieldsMap = $params['FIELDS_MAP'] ?? null;
			if (is_array($fieldsMap))
			{
				$fields = EntityFieldsHelper::replaceFieldNamesByMap($fields, $fieldsMap);
			}
		}
		else
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

		$this->createManualOpportunityModificationEntryIfNeeded($ownerID, $authorID, $fields);
	}

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

		$currentFields =
			isset($params['CURRENT_FIELDS']) && is_array($params['CURRENT_FIELDS'])
				? $params['CURRENT_FIELDS']
				: []
		;
		$previousFields =
			isset($params['PREVIOUS_FIELDS']) && is_array($params['PREVIOUS_FIELDS'])
				? $params['PREVIOUS_FIELDS']
				: []
		;

		$fieldsMap = $params['FIELDS_MAP'] ?? null;
		if (is_array($fieldsMap))
		{
			$currentFields = EntityFieldsHelper::replaceFieldNamesByMap($currentFields, $fieldsMap);
			$previousFields = EntityFieldsHelper::replaceFieldNamesByMap($previousFields, $fieldsMap);
		}

		$authorID = self::resolveEditorID($currentFields);

		$prevStageID = $previousFields['STAGE_ID'] ?? '';
		$curStageID = $currentFields['STAGE_ID'] ?? $prevStageID;

		$categoryID = isset($previousFields['CATEGORY_ID']) ? (int)$previousFields['CATEGORY_ID'] : -1;
		if ($categoryID < 0)
		{
			$categoryID = \CCrmDeal::GetCategoryID($ownerID);
		}

		$categoryChanged = false;
		if (isset($previousFields['CATEGORY_ID']) && isset($currentFields['CATEGORY_ID']) && $previousFields['CATEGORY_ID'] != $currentFields['CATEGORY_ID'])
		{
			$categoryChanged = true;

			$currentCategoryId = (int)$currentFields['CATEGORY_ID'];
			$prevCategoryId = (int)$previousFields['CATEGORY_ID'];
			$factory = Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
			$currentCategory = $factory ? $factory->getCategory($currentCategoryId) : null;
			$prevCategory = $factory ? $factory->getCategory($prevCategoryId) : null;
			$currentStage = $factory ? $factory->getStage($curStageID) : null;
			$prevStage = $factory ? $factory->getStage($prevStageID) : null;

			$historyEntryID = ModificationEntry::create(
				array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
					'ENTITY_ID' => $ownerID,
					'AUTHOR_ID' => $authorID,
					'SETTINGS' => array(
						'FIELD' => 'CATEGORY_ID',
						'START_CATEGORY_ID' => $prevCategoryId,
						'FINISH_CATEGORY_ID' => $currentCategoryId,
						'START_CATEGORY_NAME' => $prevCategory ? $prevCategory->getName() : $prevCategoryId,
						'FINISH_CATEGORY_NAME' => $currentCategory ? $currentCategory->getName() : $currentCategoryId,
						'START_STAGE_ID' => $prevStageID,
						'FINISH_STAGE_ID' => $curStageID,
						'START_STAGE_NAME' => $prevStage ? $prevStage->getName() : $prevStageID,
						'FINISH_STAGE_NAME' => $currentStage ? $currentStage->getName() : $curStageID
					)
				)
			);
			$this->sendPullEventOnAdd(new Crm\ItemIdentifier(\CCrmOwnerType::Deal, $ownerID), $historyEntryID);
		}

		if (!$categoryChanged && $prevStageID !== $curStageID)
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
						'START_NAME' => $stageNames[$prevStageID] ?? $prevStageID,
						'FINISH_NAME' => $stageNames[$curStageID] ?? $curStageID
					)
				)
			);
			$this->sendPullEventOnAdd(new Crm\ItemIdentifier(\CCrmOwnerType::Deal, $ownerID), $historyEntryID);
		}

		$this->createManualOpportunityModificationEntryIfNeeded($ownerID, $authorID, $currentFields, $previousFields);
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

	protected function applySettingsBaseData(array &$data, array $base, string $caption = ''): void
	{
		if (empty($base))
		{
			return;
		}

		$entityTypeId = $base['ENTITY_TYPE_ID'] ?? 0;
		$entityId = $base['ENTITY_ID'] ?? 0;
		$source = $base['SOURCE'] ?? '';
		if ($entityId > 0 && \CCrmOwnerType::IsDefined($entityTypeId))
		{
			if (!empty($caption))
			{
				$data['BASE'] = ['CAPTION' => $caption];
			}

			if (\CCrmOwnerType::TryGetEntityInfo($entityTypeId, $entityId, $baseEntityInfo, false))
			{
				$data['BASE']['ENTITY_INFO'] = $baseEntityInfo;
				if (!empty($source))
				{
					$data['BASE']['ENTITY_INFO']['SOURCE'] = $source;
				}
			}
		}
	}

	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : TimelineType::UNDEFINED;
		$typeCategoryId = (int)($data['TYPE_CATEGORY_ID'] ?? LogMessageType::UNDEFINED);
		$settings = $data['SETTINGS'] ?? [];
		$base = $settings['BASE'] ?? [];
		$culture = Main\Context::getCurrent()->getCulture();
		$associatedEntityTypeID = isset($data['ASSOCIATED_ENTITY_TYPE_ID'])
			? (int)$data['ASSOCIATED_ENTITY_TYPE_ID']
			: \CCrmOwnerType::Deal;

		if (isset($settings[SignDocument::DOCUMENT_DATA_KEY]))
		{
			$data[SignDocument::DOCUMENT_DATA_KEY] = $settings[SignDocument::DOCUMENT_DATA_KEY];
		}

		if (isset($settings[SignDocument::MESSAGE_DATA_KEY]))
		{
			$data[SignDocument::MESSAGE_DATA_KEY] = $settings[SignDocument::MESSAGE_DATA_KEY];
		}

		if($typeID === TimelineType::CREATION)
		{
			$data['TITLE'] =  Loc::getMessage('CRM_DEAL_CREATION');

			if($associatedEntityTypeID === \CCrmOwnerType::SuspendedDeal)
			{
				$data['LEGEND'] = Loc::getMessage('CRM_DEAL_MOVING_TO_RECYCLEBIN');
			}
			else
			{
				$entityTypeId = $base['ENTITY_TYPE_ID'] ?? 0;
				$caption = $entityTypeId <= 0
					? ''
					: Loc::getMessage(sprintf('CRM_DEAL_BASE_CAPTION_%s', \CCrmOwnerType::ResolveName($entityTypeId)));
				$this->applySettingsBaseData($data, $base, $caption);

				$order = $settings['ORDER'] ?? null;
				if ($order)
				{
					$orderId = $order['ENTITY_ID'];
					$order = Order::load($orderId);
					if ($order)
					{
						$data['ASSOCIATED_ENTITY']['ORDER']['ID'] = $orderId;
						$data['ASSOCIATED_ENTITY']['ORDER']['SHOW_URL'] = Crm\Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()->getOrderDetailsLink(
							$orderId,
							Crm\Service\Sale\EntityLinkBuilder\Context::getShopForcedContext()
						);
						$data['ASSOCIATED_ENTITY']['ORDER']['SUM'] = \CCrmCurrency::MoneyToString(
							$order->getPrice(),
							$order->getCurrency()
						);
						$data['ASSOCIATED_ENTITY']['ORDER']['ORDER_DATE'] = \FormatDate(
							$culture->getLongDateFormat(), $order->getDateInsert()->getTimestamp()
						);
						$data['ASSOCIATED_ENTITY']['ORDER']['FIELD_VALUES'] = $order->getFieldValues();
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
			if ($fieldName === 'CATEGORY_ID')
			{
				$data['TITLE'] =  Loc::getMessage('CRM_DEAL_MODIFICATION_CATEGORY');
				$data['START_CATEGORY_NAME'] = $settings['START_CATEGORY_NAME'];
				$data['FINISH_CATEGORY_NAME'] = $settings['FINISH_CATEGORY_NAME'];
				$data['START_STAGE_NAME'] = $settings['START_STAGE_NAME'];
				$data['FINISH_STAGE_NAME'] = $settings['FINISH_STAGE_NAME'];
			}
			if($fieldName === 'IS_MANUAL_OPPORTUNITY')
			{
				$data['TITLE'] =  Loc::getMessage('CRM_DEAL_MODIFICATION_IS_MANUAL_OPPORTUNITY');
				$data['START_NAME'] = isset($settings['START_NAME']) ? $settings['START_NAME'] : $settings['START'];
				$data['FINISH_NAME'] = isset($settings['FINISH_NAME']) ? $settings['FINISH_NAME'] : $settings['FINISH'];
				$data['START'] = $settings['START'];
				$data['FINISH'] = $settings['FINISH'];
			}
			$data['MODIFIED_FIELD'] = $fieldName;
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

				if(\CCrmOwnerType::IsDefined($entityTypeID))
				{
					\CCrmOwnerType::TryGetEntityInfo($entityTypeID, $entityID, $entityInfo, false);
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
		elseif (
			$typeID === TimelineType::FINAL_SUMMARY
			|| $typeID === TimelineType::FINAL_SUMMARY_DOCUMENTS
		)
		{
			$entityId = (int)$data['ASSOCIATED_ENTITY_ID'];

			/** @var PaymentDocumentsRepository */
			$repository = ServiceLocator::getInstance()->get('crm.entity.paymentDocumentsRepository');
			$result = $repository->getDocumentsForEntity($associatedEntityTypeID, $entityId);
			if ($result->isSuccess())
			{
				$data['RESULT']['TIMELINE_SUMMARY_OPTIONS'] = $result->getData();
			}
		}
		elseif ($typeID === TimelineType::PRODUCT_COMPILATION)
		{
			if (isset($settings['COMPILATION_CREATION_DATE']))
			{
				$settings['COMPILATION_CREATION_DATE'] = FormatDate(
					Main\Context::getCurrent()->getCulture()->getLongDateFormat(),
					$settings['COMPILATION_CREATION_DATE']
				);
			}
			elseif (
				isset($data['TYPE_CATEGORY_ID'])
				&& (int)$data['TYPE_CATEGORY_ID'] === ProductCompilationType::NEW_DEAL_CREATED
			)
			{
				$newDealId = $settings['NEW_DEAL_ID'];
				$entityInfo = [];
				\CCrmOwnerType::TryGetEntityInfo(
					\CCrmOwnerType::Deal,
					$newDealId,
					$entityInfo,
					false
				);
				$data['NEW_DEAL_DATA'] = $entityInfo;
			}

			$data = array_merge($data, $settings);
		}
		elseif ($typeID === TimelineType::LOG_MESSAGE)
		{
			$this->applySettingsBaseData($data, $base);
		}

		return parent::prepareHistoryDataModel($data, $options);
	}
	//endregion
}
