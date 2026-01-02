<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Error;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Tasks\TaskTable;
use Bitrix\Tasks\Internals\DataBase\Mesh;
use Bitrix\Tasks\Internals\DataBase\Tree;
use Bitrix\Tasks\ActionFailedException;

Loc::loadMessages(__FILE__);

/**
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ProjectDependence_Query query()
 * @method static EO_ProjectDependence_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ProjectDependence_Result getById($id)
 * @method static EO_ProjectDependence_Result getList(array $parameters = [])
 * @method static EO_ProjectDependence_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectDependence createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectDependence wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection wakeUpCollection($rows)
 */

final class ProjectDependenceTable extends Mesh
{
	public const LINK_TYPE_START_START = 0x00;
	public const LINK_TYPE_START_FINISH = 0x01;
	public const LINK_TYPE_FINISH_START = 0x02;
	public const LINK_TYPE_FINISH_FINISH = 0x03;

	public static function getTableName(): string
	{
		return 'b_tasks_proj_dep';
	}

	public static function getIDColumnName(): string
	{
		return 'TASK_ID';
	}

	public static function getPARENTIDColumnName(): string
	{
		return 'DEPENDS_ON_ID';
	}

	public static function getClass(): string
	{
		return self::class;
	}

	public static function createLink($id, $parentId, $behaviour = array()): AddResult
	{
		$id = (int)$id;
		$parentId = (int)$parentId;

		if ($id <= 0 || $parentId <= 0)
		{
			throw new ArgumentException('Both must be positive integers');
		}

		$allowed = [
			self::LINK_TYPE_START_START,
			self::LINK_TYPE_START_FINISH,
			self::LINK_TYPE_FINISH_START,
			self::LINK_TYPE_FINISH_FINISH,
		];

		$behaviour = (array)$behaviour;
		$type = self::LINK_TYPE_FINISH_START;
		if (
			isset($behaviour['LINK_TYPE'])
			&& in_array((int)$behaviour['LINK_TYPE'], $allowed, true)
		)
		{
			$type = (int)$behaviour['LINK_TYPE'];
		}

		$result = new AddResult();

		$toTask = $behaviour['TASK_DATA'] ?? TaskTable::getById($id)->fetch();
		if(empty($toTask))
		{
			throw new ActionFailedException('Task not found');
		}

		$fromTask = $behaviour['PARENT_TASK_DATA'] ?? TaskTable::getById($parentId)->fetch();
		if (empty($fromTask))
		{
			throw new ActionFailedException('Parent task not found');
		}

		if (isset($toTask['CREATED_DATE']) && (string)$toTask['CREATED_DATE'] === '')
		{
			$result->addError(new Error(Loc::getMessage('DEPENDENCE_ENTITY_CANT_ADD_LINK_CREATED_DATE_NOT_SET')));
		}
		if (isset($toTask['END_DATE_PLAN']) && (string)$toTask['END_DATE_PLAN'] === '')
		{
			$result->addError(new Error(Loc::getMessage('DEPENDENCE_ENTITY_CANT_ADD_LINK_END_DATE_PLAN_NOT_SET')));
		}
		if (isset($fromTask['CREATED_DATE']) && (string)$fromTask['CREATED_DATE'] === '')
		{
			$result->addError(new Error(Loc::getMessage('DEPENDENCE_ENTITY_CANT_ADD_LINK_CREATED_DATE_NOT_SET_PARENT_TASK')));
		}
		if (isset($fromTask['END_DATE_PLAN']) && (string)$fromTask['END_DATE_PLAN'] === '')
		{
			$result->addError(new Error(Loc::getMessage('DEPENDENCE_ENTITY_CANT_ADD_LINK_END_DATE_PLAN_NOT_SET_PARENT_TASK')));
		}

		if (!$result->isSuccess())
		{
			return $result;
		}

		$linkData = ['TYPE' => $type];
		$creatorId = (int)($behaviour['CREATOR_ID'] ?? null);
		if ($creatorId > 0)
		{
			$linkData['CREATOR_ID'] = $behaviour['CREATOR_ID'];
		}

		return parent::createLink($id, $parentId, ['LINK_DATA' => $linkData]);
	}

	protected static function applyCreateRestrictions(&$id, &$parentId): void
	{
		if (self::checkLinkExists($id, $parentId, ['BIDIRECTIONAL' => true]))
		{
			throw new Tree\LinkExistsException(false, ['NODES' => [$id, $parentId]]);
		}
	}

	public static function checkLinkExists($id, $parentId, array $parameters = array('BIDIRECTIONAL' => false))
	{
		$parentColName = static::getPARENTIDColumnName();
		$idColName = static::getIDColumnName();
		//$directColName = static::getDIRECTColumnName();

		$id = intval($id);
		$parentId = intval($parentId);
		if(!$id || !$parentId)
		{
			return false; // link to non-existed nodes does not exist
		}

		$item = HttpApplication::getConnection()->query("
			select ".$idColName."
				from
					".static::getTableName()."
				where
					(
						".$idColName." = '".$id."'
						and ".$parentColName." = '".$parentId."'
					)
					".($parameters['BIDIRECTIONAL'] ? "

					or
					(
						".$idColName." = '".$parentId."'
						and ".$parentColName." = '".$id."'
					)

					" : "")."
			")->fetch();

		return is_array($item);
	}

	protected static function applyDeleteRestrictions(&$id, &$parentId): void
	{
		if ((int)$id <= 0)
		{
			throw new ArgumentException('Must be a positive integer');
		}

		if ($parentId !== false && !self::checkLinkExists($id, $parentId))
		{
			throw new Tree\LinkNotExistException(false, ['NODES' => [$id, $parentId]]);
		}
	}

	/**
	 * Returns a list of INGOING DIRECT links according to the old-style (sutable for CTask::GetList()) filter
	 * A heavy-artillery function
	 */
	public static function getListByLegacyTaskFilter(array $filter = [], array $parameters = [])
	{
		/**
		 * Group by subtask should be ignored for fulltext search
		 * See #140001 for more information
		 */
		if (
			array_key_exists('::SUBFILTER-FULL_SEARCH_INDEX', $filter)
			|| array_key_exists('::SUBFILTER-COMMENT_SEARCH_INDEX', $filter)
		)
		{
			unset($filter['ONLY_ROOT_TASKS']);
		}

		$mixins = TaskTable::getRuntimeMixins(
			[
				[
					'CODE' => 'LEGACY_FILTER',
					'FILTER' => $filter,
					'REF_FIELD' => 'TASK_ID',
				],
			]
		);

		if (!empty($mixins))
		{
			if (!isset($parameters['runtime']) || !is_array($parameters['runtime']))
			{
				$parameters['runtime'] = [];
			}

			$parameters['runtime'] = array_merge($parameters['runtime'], $mixins);
		}

		$parameters['filter']['=DIRECT'] = '1';

		return self::getList($parameters);
	}

	public static function getMap(): array
	{
		$map = [
			(new IntegerField('TASK_ID'))
				->configurePrimary()
				->configureTitle(Loc::getMessage('DEPENDENCE_ENTITY_TASK_ID_FIELD'))
				->configureRequired(),

			(new IntegerField('DEPENDS_ON_ID'))
				->configurePrimary()
				->configureTitle(Loc::getMessage('DEPENDENCE_ENTITY_DEPENDS_ON_ID_FIELD'))
				->configureRequired(),

			(new IntegerField('TYPE'))
				->configureTitle(Loc::getMessage('DEPENDENCE_ENTITY_TYPE_FIELD')),

			(new IntegerField('CREATOR_ID')),

			(new Reference('TASK', TaskTable::getEntity(), Join::on('this.TASK_ID', 'ref.ID'))),

			(new Reference('DEPENDS_ON', TaskTable::getEntity(), Join::on('this.DEPENDS_ON_ID', 'ref.ID'))),
		];

		$parentMap = parent::getMap(self::class);

		return array_merge($map, $parentMap);
	}

	public static function moveLink($id, $parentId, $behaviour = ['CREATE_PARENT_NODE_ON_NOTFOUND' => true]): void
	{
		throw new NotImplementedException('Calling moveLink() is meaningless for this entity');
	}

	public static function link($id, $parentId): void
	{
		throw new NotImplementedException('Calling link() is meaningless for this entity');
	}

	public static function unlink($id): void
	{
		throw new NotImplementedException('Calling unlink() is meaningless for this entity');
	}
}
