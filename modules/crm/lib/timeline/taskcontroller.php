<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class TaskController extends ActivityController
{
	//region EntityController
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

		$fields = isset($params['TASK_FIELDS']) && is_array($params['TASK_FIELDS']) ? $params['TASK_FIELDS'] : null;

		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS'])
			? $params['BINDINGS'] : array();

		if(empty($bindings))
		{
			$bindings = \CCrmActivity::GetBindings($ownerID);
		}

		if(empty($bindings))
		{
			return;
		}

		$authorID = isset($fields['CREATED_BY']) ? $fields['CREATED_BY'] : 0;
		if($authorID <= 0 && isset($fields['CHANGED_BY']))
		{
			$authorID = (int)$fields['CHANGED_BY'];
		}
		if($authorID <= 0 && isset($fields['RESPONSIBLE_ID']))
		{
			$authorID = (int)$fields['RESPONSIBLE_ID'];
		}
		if($authorID <= 0)
		{
			$authorID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		$created = null;
		if(isset($params['PRESERVE_CREATION_TIME'])
			&& $params['PRESERVE_CREATION_TIME'] === true
			&& isset($fields['CREATED_DATE'])
		)
		{
			$created = new DateTime($fields['CREATED_DATE'], Date::convertFormatToPhp(FORMAT_DATETIME));
		}

		$historyEntryID = CreationEntry::create([
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
			'ENTITY_ID' => $ownerID,
			'AUTHOR_ID' => $authorID,
			'CREATED' => $created,
			'BINDINGS' => self::mapBindings($bindings)
		]);

		$pullEventData = [$ownerID => $fields];

		\Bitrix\Crm\Timeline\EntityController::loadCommunicationsAndMultifields(
			$pullEventData,
			\Bitrix\Crm\Service\Container::getInstance()
				->getUserPermissions($params['CURRENT_USER'] ?? null)
				->getCrmPermissions()
		);

		foreach($bindings as $binding)
		{
			$itemIdentifier = new \Bitrix\Crm\ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);
			$this->sendPullEventOnAdd($itemIdentifier, $historyEntryID, $params['CURRENT_USER'] ?? null);
			$this->sendPullEventOnAddScheduled(
				$itemIdentifier,
				$pullEventData[$ownerID],
				$params['CURRENT_USER'] ?? null
			);
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

		$currentFields = isset($params['CURRENT_TASK_FIELDS']) && is_array($params['CURRENT_TASK_FIELDS'])
			? $params['CURRENT_TASK_FIELDS'] : array();
		$previousFields = isset($params['PREVIOUS_TASK_FIELDS']) && is_array($params['PREVIOUS_TASK_FIELDS'])
			? $params['PREVIOUS_TASK_FIELDS'] : array();

		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS'])
			? $params['BINDINGS'] : array();

		$prevStatusID = isset($previousFields['STATUS']) ? (int)$previousFields['STATUS'] : 1;
		$curStatusID = isset($currentFields['STATUS']) ? (int)$currentFields['STATUS'] : $prevStatusID;

		$historyEntryID = 0;
		if($prevStatusID !== $curStatusID)
		{
			$authorID = isset($currentFields['CHANGED_BY']) ? $currentFields['CHANGED_BY'] : 0;
			if($authorID <= 0 && isset($currentFields['RESPONSIBLE_ID']))
			{
				$authorID = (int)$currentFields['RESPONSIBLE_ID'];
			}
			if($authorID <= 0)
			{
				$authorID = \CCrmSecurityHelper::GetCurrentUserID();
			}

			$statusNames = self::getAllStatusNames();
			$historyEntryID = ModificationEntry::create(
				array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
					'ENTITY_ID' => $ownerID,
					'AUTHOR_ID' => $authorID,
					'SETTINGS' => array(
						'ENTITY' => array('TYPE_ID' => \CCrmActivityType::Task),
						'FIELD' => 'TASK:STATUS',
						'START' => $prevStatusID,
						'FINISH' => $curStatusID,
						'START_NAME' => isset($statusNames[$prevStatusID]) ? $statusNames[$prevStatusID] : $prevStatusID,
						'FINISH_NAME' => isset($statusNames[$curStatusID]) ? $statusNames[$curStatusID] : $curStatusID
					),
					'BINDINGS' => self::mapBindings($bindings)
				)
			);
		}
		foreach($bindings as $binding)
		{
			$this->sendPullEventOnAdd(new \Bitrix\Crm\ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']), $historyEntryID);
		}
	}

	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : TimelineType::UNDEFINED;
		if($typeID === TimelineType::MODIFICATION)
		{
			$settings = isset($data['SETTINGS']) ? $data['SETTINGS'] : array();
			$fieldName = isset($settings['FIELD']) ? $settings['FIELD'] : '';

			if($fieldName === 'TASK:STATUS')
			{
				$data['TITLE'] =  Loc::getMessage('CRM_TASK_MODIFICATION_STATUS');
				$data['START_NAME'] = isset($settings['START_NAME']) ? $settings['START_NAME'] : $settings['START'];
				$data['FINISH_NAME'] = isset($settings['FINISH_NAME']) ? $settings['FINISH_NAME'] : $settings['FINISH'];
			}
			$data['MODIFIED_FIELD'] = $fieldName;
			unset($data['SETTINGS']);
		}
		return parent::prepareHistoryDataModel($data, $options);
	}
	//endregion
	protected static function getAllStatusNames()
	{
		return array();
	}
}
