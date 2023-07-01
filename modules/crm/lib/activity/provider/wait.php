<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\DB\SqlExpression;

use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\ActivityBindingTable;

Loc::loadMessages(__FILE__);

class Wait extends Base
{
	const PROVIDER_ID = 'CRM_WAIT';
	public static function getId()
	{
		return static::PROVIDER_ID;
	}
	public static function getName()
	{
		return Loc::getMessage('CRM_ACT_PROVIDER_WAIT_NAME');
	}
	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return true;
	}
	/**
	 * @param array $activity
	 * @return string
	 */
	public static function getPlannerTitle(array $activity)
	{
		return Loc::getMessage('CRM_ACT_PROVIDER_WAIT_NAME');
	}
	/**
	 * @param string $action Action ADD or UPDATE.
	 * @param array $fields Activity fields.
	 * @param int $id Activity ID.
	 * @param null|array $params Additional parameters.
	 * @return Main\Result Check fields result.
	 */
	public static function checkFields($action, &$fields, $id, $params = null)
	{
		$result = new Main\Result();

		$bindings = isset($fields['BINDINGS']) && is_array($fields['BINDINGS'])
			? $fields['BINDINGS'] : array();
		if(!empty($bindings))
		{
			foreach($bindings as $binding)
			{
				$ownerTypeID = isset($binding['OWNER_TYPE_ID']) ? (int)$binding['OWNER_TYPE_ID'] : 0;
				$ownerID = isset($binding['OWNER_ID']) ? (int)$binding['OWNER_ID'] : 0;

				$query = new Query(ActivityTable::getEntity());
				$query->addSelect('ID');
				$query->addFilter('=IS_HANDLEABLE', 'Y');
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
				if(is_array($dbResult->fetch()))
				{
					$result->addError(
						new Main\Error(Loc::getMessage('CRM_ACT_PROVIDER_WAIT_SCHEDULE_NOT_EMPTY_ERROR'))
					);
					return $result;
				}
			}
		}

		if (
			$action === self::ACTION_UPDATE
			&& isset($fields['COMPLETED'])
			&& $fields['COMPLETED'] === 'Y'
			&& isset($params['PREVIOUS_FIELDS'])
			&& empty($params['PREVIOUS_FIELDS']['END_TIME'])
		)
		{
			$end = new Main\Type\DateTime();
			$fields['END_TIME'] = $end->toString();
		}

		//Only END TIME can be taken for DEADLINE!
		if (isset($fields['END_TIME']) && $fields['END_TIME'] !== '')
		{
			$fields['DEADLINE'] = $fields['END_TIME'];
		}

		//Wait is not handleabele always.
		$fields['IS_HANDLEABLE'] = 'N';
		return $result;
	}

	/**
	 * Process activity creation.
	 * @param array $activityFields
	 * @param array|null $params
	 */
	public static function processCreation(array $activityFields, array $params = null)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		if(isset($params['IS_RESTORATION']) && $params['IS_RESTORATION'])
		{
			return;
		}

		$completed = isset($activityFields['COMPLETED']) && $activityFields['COMPLETED'] === 'Y';
		if($completed)
		{
			return;
		}

		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS'])
			? $params['BINDINGS'] : array();

		if(empty($bindings))
		{
			return;
		}

		$providerID = isset($activityFields['PROVIDER_ID']) ? $activityFields['PROVIDER_ID'] : '';
		self::complete($bindings, array('SKIP_RECENT' => $providerID === self::PROVIDER_ID));
	}

	public static function complete(array $bindings, array $options = array())
	{
		$entityMap = array();
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

			if(!isset($entityMap[$ownerTypeID]))
			{
				$entityMap[$ownerTypeID] = array();
			}
			$entityMap[$ownerTypeID][] = $ownerID;
		}

		$activityIDs = array();
		foreach($entityMap as $ownerTypeID => $ownerIDs)
		{
			$query = new Query(ActivityTable::getEntity());
			$query->addSelect('ID');
			$query->addFilter('=COMPLETED', 'N');
			$query->addFilter('=PROVIDER_ID', self::PROVIDER_ID);
			$query->addOrder('ID', 'ASC');

			if(count($ownerIDs) > 1)
			{
				$query->registerRuntimeField(
					'',
					new ReferenceField('B',
						ActivityBindingTable::getEntity(),
						array(
							'=ref.ACTIVITY_ID' => 'this.ID',
							'@ref.OWNER_ID' => new SqlExpression(implode(', ', $ownerIDs)),
							'=ref.OWNER_TYPE_ID' => new SqlExpression($ownerTypeID)
						),
						array('join_type' => 'INNER')
					)
				);
			}
			else
			{
				$query->registerRuntimeField(
					'',
					new ReferenceField('B',
						ActivityBindingTable::getEntity(),
						array(
							'=ref.ACTIVITY_ID' => 'this.ID',
							'=ref.OWNER_ID' => new SqlExpression($ownerIDs[0]),
							'=ref.OWNER_TYPE_ID' => new SqlExpression($ownerTypeID)
						),
						array('join_type' => 'INNER')
					)
				);
			}


			$dbResult = $query->exec();
			while($fields = $dbResult->fetch())
			{
				$activityIDs[] = (int)$fields['ID'];
			}
		}

		if(isset($options['SKIP_RECENT']) && $options['SKIP_RECENT'] === true)
		{
			//Removing of recent activity ID.
			array_pop($activityIDs);
		}

		foreach($activityIDs as $activityID)
		{
			\CCrmActivity::Complete(
				$activityID,
				true,
				array('REGISTER_SONET_EVENT' => true)
			);
		}
	}
}
