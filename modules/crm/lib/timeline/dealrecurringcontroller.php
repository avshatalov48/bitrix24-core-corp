<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Crm\Recurring;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DealRecurringController extends DealController
{
	/** @ToDo DealRecurringController */
	const CONTROLLER_NAME = __CLASS__;
	const PUSH_COMMAND_DEAL_ADD = "timeline_deal_add";
	const PUSH_COMMAND_DEAL_MODIFY = "timeline_activity_add";

	public static function getInstance()
	{
		if (!(self::$instance instanceof self))
		{
			self::$instance = new DealRecurringController();
		}
		return self::$instance;
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
		if(!is_array($fields) && $fields['IS_RECURRING'] !== 'Y')
		{
			return;
		}

		$recurringFields = isset($params['RECURRING']) && is_array($params['RECURRING']) ? $params['RECURRING'] : null;
		if(!is_array($recurringFields))
		{
			$fields = Recurring\Manager::getList(
				array(
					'filter' => array("ID" => $ownerID),
					'limit' => 1
				),
				Recurring\Manager::DEAL
			);
			$recurringFields = $fields->fetch();
		}

		if(!is_array($recurringFields))
		{
			return;
		}

		$settings = array();
		if ((int)$recurringFields['BASED_ID'] > 0)
		{
			$settings['BASE'] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => (int)$recurringFields['BASED_ID']
			);
		}

		$historyEntryID = CreationEntry::create(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::DealRecurring,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => self::resolveCreatorID($fields),
				'SETTINGS' => $settings,
				'BINDINGS' => array(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::DealRecurring,
						'ENTITY_ID' => $ownerID
					)
				)
			)
		);

		$this->pushHistory($historyEntryID, $ownerID, self::PUSH_COMMAND_DEAL_ADD);

		if ((int)$recurringFields['BASED_ID'] > 0)
		{
			$historyEntryID = ConversionEntry::create(
				array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
					'ENTITY_ID' => (int)$recurringFields['BASED_ID'],
					'AUTHOR_ID' => self::resolveCreatorID($fields),
					'SETTINGS' => array(
						'ENTITIES' => array(
							array(
								'ENTITY_TYPE_ID' => \CCrmOwnerType::DealRecurring,
								'ENTITY_ID' => $ownerID
							)
						)
					)
				)
			);

			$this->pushHistory($historyEntryID, $ownerID, self::PUSH_COMMAND_DEAL_MODIFY);
		}
	}

	public function onExpose($ownerID, array $params)
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

		if(isset($fields['RECURRING_ID']) && (int)$fields['RECURRING_ID'] > 0)
		{
			$settings['BASE'] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::DealRecurring,
				'ENTITY_ID' => (int)$fields['RECURRING_ID']
			);
		}

		$historyEntryID = CreationEntry::create(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => self::resolveCreatorID($fields),
				'SETTINGS' => $settings,
				'BINDINGS' => array(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
						'ENTITY_ID' => $ownerID
					)
				)
			)
		);

		$this->pushHistory($historyEntryID, $ownerID, self::PUSH_COMMAND_DEAL_ADD);

		if ((int)$fields['RECURRING_ID'] > 0)
		{
			$historyEntryID = ConversionEntry::create(
				array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::DealRecurring,
					'ENTITY_ID' => (int)$fields['RECURRING_ID'],
					'AUTHOR_ID' => self::resolveCreatorID($fields),
					'SETTINGS' => array(
						'ENTITIES' => array(
							array(
								'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
								'ENTITY_ID' => $ownerID
							)
						)
					)
				)
			);

			$this->pushHistory($historyEntryID, (int)$fields['RECURRING_ID'], self::PUSH_COMMAND_DEAL_MODIFY);
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

		if (strlen($params['FIELD_NAME']))
			$fieldName = $params['FIELD_NAME'];
		else
			return;

		$previousValue = isset($previousFields['VALUE']) ? $previousFields['VALUE'] : '';
		$currentValue = isset($currentFields['VALUE']) ? $currentFields['VALUE'] : $previousValue;

		if($previousValue !== $currentValue)
		{
			$historyEntryID = ModificationEntry::create(
				array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::DealRecurring,
					'ENTITY_ID' => $ownerID,
					'AUTHOR_ID' => self::resolveEditorID($currentFields),
					'SETTINGS' => array(
						'FIELD' => $fieldName,
						'START' => $previousValue,
						'FINISH' => $currentValue,
					)
				)
			);

			$this->pushHistory($historyEntryID, $ownerID, self::PUSH_COMMAND_DEAL_MODIFY);
		}
	}

	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : TimelineType::UNDEFINED;
		$settings = isset($data['SETTINGS']) ? $data['SETTINGS'] : array();

		if($typeID === TimelineType::CREATION)
		{
			$base = isset($settings['BASE']) ? $settings['BASE'] : null;
			$codeTitle = ($base['ENTITY_TYPE_ID'] === \CCrmOwnerType::DealRecurring) ? 'CRM_DEAL_RECURRING_CREATION' : 'CRM_DEAL_CREATION';
			$data['TITLE'] = Loc::getMessage($codeTitle);

			if(is_array($base))
			{
				$entityTypeID = isset($base['ENTITY_TYPE_ID']) ? $base['ENTITY_TYPE_ID'] : 0;
				$caption = Loc::getMessage("CRM_DEAL_BASE_CAPTION_BASED_ON_DEAL");

				$entityID = isset($base['ENTITY_ID']) ? $base['ENTITY_ID'] : 0;
				if(\CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0)
				{
					$data['BASE']['CAPTION'] = $caption;
					if(\CCrmOwnerType::TryGetEntityInfo(\CCrmOwnerType::Deal, $entityID, $baseEntityInfo, false))
					{
						$data['BASE']['ENTITY_INFO'] = $baseEntityInfo;
					}
				}
			}
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::CONVERSION)
		{
			$data['TITLE'] =  Loc::getMessage('CRM_RECURRING_CREATION_BASED_ON');
			$entities = isset($settings['ENTITIES']) && is_array($settings['ENTITIES'])
				? $settings['ENTITIES'] : array();

			$entityInfos = array();
			foreach($entities as $entityData)
			{
				$entityTypeID = isset($entityData['ENTITY_TYPE_ID']) ? (int)$entityData['ENTITY_TYPE_ID'] : 0;
				$entityID = isset($entityData['ENTITY_ID']) ? (int)$entityData['ENTITY_ID'] : 0;

				if(\CCrmOwnerType::TryGetEntityInfo(\CCrmOwnerType::Deal, $entityID, $entityInfo, false) && $entityTypeID)
				{
					$entityInfo['ENTITY_TYPE_ID'] = $entityTypeID;
					$entityInfo['ENTITY_ID'] = $entityID;
					$entityInfos[] = $entityInfo;
				}
			}
			$data['ENTITIES'] = $entityInfos;
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::MODIFICATION)
		{
			$fieldName = isset($settings['FIELD']) ? $settings['FIELD'] : '';
			if($fieldName === 'NEXT_EXECUTION')
			{
				$data['START_NAME'] = isset($settings['START_NAME']) ? $settings['START_NAME'] : $settings['START'];
				$data['FINISH_NAME'] = isset($settings['FINISH_NAME']) ? $settings['FINISH_NAME'] : $settings['FINISH'];
				$titleCode = !empty($data['START_NAME']) ? 'CRM_DEAL_RECURRING_NEXT_EXECUTION_CHANGED' : 'CRM_DEAL_RECURRING_NEXT_EXECUTION';
				$data['TITLE'] =  Loc::getMessage($titleCode);
			}
			if ($fieldName === 'ACTIVE')
			{
				$messageCode = ($settings['FINISH'] !== 'Y') ? "CRM_DEAL_RECURRING_NOT_ACTIVE" : "CRM_DEAL_RECURRING_ACTIVE";
				$data['TITLE'] =  Loc::getMessage($messageCode);
			}
			unset($data['SETTINGS']);
		}

		return EntityController::prepareHistoryDataModel($data, $options);
	}
	//endregion

	protected function pushHistory($historyEntryID, $ownerID, $command)
	{
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

			if (!empty($historyFields['ASSOCIATED_ENTITY_TYPE_ID']))
			{
				$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag($historyFields['ASSOCIATED_ENTITY_TYPE_ID'], $ownerID);

				\CPullWatch::AddToStack(
					$tag,
					array(
						'module_id' => 'crm',
						'command' => $command,
						'params' => $pushParams,
					)
				);
			}
		}
	}
}