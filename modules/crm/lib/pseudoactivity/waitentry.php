<?php
namespace Bitrix\Crm\Pseudoactivity;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\DB\SqlExpression;

use Bitrix\Crm\Pseudoactivity\Entity\WaitTable;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Counter\EntityCounterManager;
use Bitrix\Crm\Counter\EntityCounterType;

Loc::loadMessages(__FILE__);

class WaitEntry
{
	/** @var  \CCrmPerms|null */
	private static $userPermissions = null;

	public static function add(array $fields)
	{
		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : 0;
		$ownerID = isset($fields['OWNER_ID']) ? (int)$fields['OWNER_ID'] : 0;

		/*
		if(self::checkScheduledActivities($ownerTypeID, $ownerID))
		{
			$result = new Main\Entity\AddResult();
			$result->addError(
				new Main\Error(Loc::getMessage('CRM_WAIT_SCHEDULE_NOT_EMPTY_ERROR'))
			);
			return $result;
		}
		*/

		$fields['CREATED'] = new DateTime();
		if(!isset($fields['COMPLETED']))
		{
			$fields['COMPLETED'] = 'N';
		}

		$result = WaitTable::add($fields);
		if(!$result->isSuccess())
		{
			return 0;
		}

		$ID = $result->getId();
		$fields['ID'] = $ID;
		\Bitrix\Crm\Timeline\WaitController::getInstance()->onCreate($ID, array('FIELDS' => $fields));

		if($fields['COMPLETED'] === 'N')
		{
			self::completeByOwner($ownerTypeID, $ownerID, array('SKIP_RECENT' => true));
		}

		$counterCodes = EntityCounterManager::prepareCodes(
			$ownerTypeID,
			EntityCounterType::getAll(true),
			array('ENTITY_ID' => $ownerID, 'EXTENDED_MODE' => true)
		);
		if(!empty($counterCodes))
		{
			EntityCounterManager::reset($counterCodes, array());
		}

		if(!\Bitrix\Crm\Agent\Activity\WaitAgent::isActive())
		{
			\Bitrix\Crm\Agent\Activity\WaitAgent::activate();
		}

		return $result;
	}
	public static function getByID($ID)
	{
		$dbResult = WaitTable::getById($ID);
		$fields = $dbResult->fetch();
		return is_array($fields) ? $fields : null;
	}
	public static function getRecentByOwner($ownerTypeID, $ownerID)
	{
		$dbResult = WaitTable::getList(
			array(
				'filter' => array(
					'OWNER_TYPE_ID' => $ownerTypeID,
					'OWNER_ID' => $ownerID,
					'=COMPLETED' => 'N'
				),
				'order' => array('ID' => 'DESC'),
				'limit' => 1
			)
		);

		$fields = $dbResult->fetch();
		return is_array($fields) ? $fields : null;
	}

	public static function getRecentIDsByOwner($ownerTypeID, array $ownerIDs)
	{
		$ownerStr = implode(',', $ownerIDs);
		$dbResult = Main\Application::getConnection()->query(
			"SELECT OWNER_ID, MAX(ID) ID FROM b_crm_wait WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID IN ({$ownerStr}) and COMPLETED = 'N' GROUP BY OWNER_ID"
		);

		$results = array();
		while($fields = $dbResult->fetch())
		{
			$results[] = $fields;
		}
		return $results;
	}

	public static function getRecentInfos($ownerTypeID, array $ownerIDs)
	{
		$ownerStr = implode(',', $ownerIDs);
		$dbResult = Main\Application::getConnection()->query(
			"SELECT w1.ID, w1.OWNER_TYPE_ID, w1.OWNER_ID, w1.END_TIME FROM b_crm_wait w1 INNER JOIN (SELECT OWNER_ID, MAX(ID) ID FROM b_crm_wait WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID IN ({$ownerStr}) and COMPLETED = 'N' GROUP BY OWNER_ID) w2 ON w1.ID = w2.ID"
		);

		$format = preg_replace('/:s$/', '', DateTime::getFormat());

		$results = array();
		while($fields = $dbResult->fetch())
		{
			$results[] = array(
				'ID' => $fields['ID'],
				'OWNER_TYPE_ID' => $fields['OWNER_TYPE_ID'],
				'OWNER_ID' => $fields['OWNER_ID'],
				'TITLE' => Loc::getMessage(
					'CRM_WAIT_INFO_TITLE',
					array('#END_TIME#' => isset($fields['END_TIME']) ? $fields['END_TIME']->format($format) : '-')
				)
			);
		}
		return $results;
	}

	public static function isInWaiting($ownerTypeID, $ownerID)
	{
		$dbResult = WaitTable::getList(
			array(
				'filter' => array(
					'OWNER_TYPE_ID' => $ownerTypeID,
					'OWNER_ID' => $ownerID,
					'=COMPLETED' => 'N'
				),
				'select' => array('ID'),
				'limit' => 1
			)
		);

		$fields = $dbResult->fetch();
		return is_array($fields);
	}
	public static function update($ID, array $fields, array $params = null)
	{
		if(empty($fields))
		{
			return new Main\Entity\UpdateResult();
		}

		$previousFields = self::getByID($ID);
		if(!is_array($previousFields))
		{
			$result = new Main\Entity\UpdateResult();
			$result->addError(new Main\Error("Not found") );
			return $result;
		}

		$result = WaitTable::update($ID, $fields);

		if($result->isSuccess())
		{
			$timelineParams = array(
				'CURRENT_FIELDS' => self::getByID($ID),
				'PREVIOUS_FIELDS' => $previousFields
			);

			if(is_array($params) && isset($params['USER_ID']) && $params['USER_ID'] > 0)
			{
				$timelineParams['USER_ID'] = $params['USER_ID'];
			}

			\Bitrix\Crm\Timeline\WaitController::getInstance()->onModify($ID, $timelineParams);

			$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : 0;
			if($ownerTypeID <= 0)
			{
				$ownerTypeID = isset($previousFields['OWNER_TYPE_ID']) ? (int)$previousFields['OWNER_TYPE_ID'] : 0;
			}

			$ownerID = isset($fields['OWNER_ID']) ? (int)$fields['OWNER_ID'] : 0;
			if($ownerID <= 0)
			{
				$ownerID = isset($previousFields['OWNER_ID']) ? (int)$previousFields['OWNER_ID'] : 0;
			}

			$counterCodes = EntityCounterManager::prepareCodes(
				$ownerTypeID,
				EntityCounterType::getAll(true),
				array('ENTITY_ID' => $ownerID, 'EXTENDED_MODE' => true)
			);
			if(!empty($counterCodes))
			{
				EntityCounterManager::reset($counterCodes, array());
			}
		}
		return $result;
	}
	public static function delete($ID)
	{
		$fields = self::getByID($ID);
		if(!is_array($fields))
		{
			$result = new Main\Entity\UpdateResult();
			$result->addError(new Main\Error("Not found") );
			return $result;
		}

		$result = WaitTable::delete($ID);
		if($result->isSuccess())
		{
			$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : 0;
			$ownerID = isset($fields['OWNER_ID']) ? (int)$fields['OWNER_ID'] : 0;
			$counterCodes = EntityCounterManager::prepareCodes(
				$ownerTypeID,
				EntityCounterType::getAll(true),
				array('ENTITY_ID' => $ownerID, 'EXTENDED_MODE' => true)
			);
			if(!empty($counterCodes))
			{
				EntityCounterManager::reset($counterCodes, array());
			}

			if(Main\Loader::includeModule('pull'))
			{
				$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : 0;
				$ownerID = isset($fields['OWNER_ID']) ? (int)$fields['OWNER_ID'] : 0;
				$tag = TimelineEntry::prepareEntityPushTag($ownerTypeID, $ownerID);
				\CPullWatch::AddToStack(
					$tag,
					array(
						'module_id' => 'crm',
						'command' => 'timeline_wait_delete',
						'params' => array('ENTITY_ID' => $ID, 'TAG' => $tag),
					)
				);
			}
		}
		return $result;

	}
	public static function complete($ID, $completed, array $params = null)
	{
		return self::update($ID, array('COMPLETED' => $completed ? 'Y' : 'N'), $params);
	}
	public static function completeByOwner($ownerTypeID, $ownerID, array $options = array())
	{
		if(!($ownerID > 0 && \CCrmOwnerType::IsDefined($ownerTypeID)))
		{
			return;
		}

		$dbResult = WaitTable::getList(
			array(
				'select' => array('ID'),
				'filter' => array(
					'OWNER_TYPE_ID' => $ownerTypeID,
					'OWNER_ID' => $ownerID,
					'=COMPLETED' => 'N'
				)
			)
		);

		$itemIDs = array();
		while($fields = $dbResult->fetch())
		{
			$itemIDs[] = (int)$fields['ID'];
		}

		if(isset($options['SKIP_RECENT']) && $options['SKIP_RECENT'] === true)
		{
			//Removing of recent item ID.
			array_pop($itemIDs);
		}

		foreach($itemIDs as $itemID)
		{
			self::complete($itemID, true);
		}
	}
	public static function deleteByOwner($ownerTypeID, $ownerID)
	{
		WaitTable::deleteByOwner($ownerTypeID, $ownerID);

		$counterCodes = EntityCounterManager::prepareCodes(
			$ownerTypeID,
			EntityCounterType::getAll(true),
			array('ENTITY_ID' => $ownerID, 'EXTENDED_MODE' => true)
		);
		if(!empty($counterCodes))
		{
			EntityCounterManager::reset($counterCodes, array());
		}
	}
	public static function postpone($ID, $offset)
	{
		$previousFields = self::getByID($ID);
		if(!is_array($previousFields))
		{
			$result = new Main\Entity\UpdateResult();
			$result->addError(new Main\Error("Not found") );
			return $result;
		}

		$fields = array();
		if(isset($previousFields['END_TIME']))
		{
			$previousFields['END_TIME']->add("+{$offset} seconds");
			$fields['END_TIME'] = $previousFields['END_TIME'];
		}

		return self::update($ID, $fields);
	}
	public static function exists($ID)
	{
		if(!is_numeric($ID))
		{
			$ID = (int)$ID;
		}

		if($ID <= 0)
		{
			return false;
		}

		$dbResult = WaitTable::getList(array('select' => array('ID'), 'filter' => array('=ID' => $ID)));
		return is_array($dbResult->fetch());
	}
	public static function checkScheduledActivities($ownerTypeID, $ownerID)
	{
		$query = new Query(ActivityTable::getEntity());
		$query->addSelect('ID');
		$query->addFilter('=COMPLETED', 'N');
		$query->setLimit(1);

		$query->registerRuntimeField(
			'',
			new ReferenceField('B',
				ActivityBindingTable::getEntity(),
				array(
					'=ref.ACTIVITY_ID' => 'this.ID',
					'=ref.OWNER_ID' => new SqlExpression($ownerID),
					'=ref.OWNER_TYPE_ID' => new SqlExpression($ownerTypeID)
				),
				array('join_type' => 'INNER')
			)
		);

		$dbResult = $query->exec();
		return is_array($dbResult->fetch());
	}
	/**
	 * Process activity creation.
	 * @param array $activityFields
	 * @param array|null $params
	 */
	public static function processActivityCreation(array $activityFields, array $params = null)
	{
		$provider = \CCrmActivity::GetProviderById(
			isset($activityFields['PROVIDER_ID']) ? $activityFields['PROVIDER_ID'] : ''
		);
		if(!($provider && $provider::checkForWaitingCompletion($activityFields)))
		{
			return;
		}

		$bindings = is_array($params) && isset($params['BINDINGS']) && is_array($params['BINDINGS'])
			? $params['BINDINGS'] : array();

		if(empty($bindings))
		{
			return;
		}

		foreach($bindings as $binding)
		{
			$ownerTypeID = isset($binding['OWNER_TYPE_ID']) ? (int)$binding['OWNER_TYPE_ID'] : 0;
			$ownerID = isset($binding['OWNER_ID']) ? (int)$binding['OWNER_ID'] : 0;

			//if(!(\CCrmOwnerType::IsDefined($ownerTypeID) && $ownerID > 0))
			if(!($ownerID > 0
				&& ($ownerTypeID === \CCrmOwnerType::Deal || $ownerTypeID === \CCrmOwnerType::Lead))
			)
			{
				continue;
			}

			self::completeByOwner($ownerTypeID, $ownerID, array('SKIP_RECENT' => false));
		}
	}
	public static function transferOwnership($oldOwnerTypeID, $oldOwnerID, $newOwnerTypeID, $newOwnerID)
	{
		if(!\CCrmOwnerType::IsDefined($oldOwnerTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('oldOwnerTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_int($oldOwnerID))
		{
			$oldOwnerID = (int)$oldOwnerID;
		}

		if($oldOwnerID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldOwnerID');
		}

		if($newOwnerTypeID <= 0)
		{
			throw new Main\ArgumentOutOfRangeException('newOwnerTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_int($newOwnerID))
		{
			$newOwnerID = (int)$newOwnerID;
		}

		if($newOwnerID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newOwnerID');
		}

		WaitTable::transferOwnership($oldOwnerTypeID, $oldOwnerID, $newOwnerTypeID, $newOwnerID);
	}
}