<?
namespace Bitrix\Tasks\Internals\Task;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\NotImplementedException;
use \Bitrix\Main\Entity\AddResult;
use \Bitrix\Main\Error;
use \Bitrix\Main\HttpApplication;

use \Bitrix\Tasks\TaskTable;
use \Bitrix\Tasks\Internals\DataBase\Mesh;
use \Bitrix\Tasks\Internals\DataBase\Tree;
use \Bitrix\Tasks\Util\Assert;
use \Bitrix\Tasks\ActionFailedException;

Loc::loadMessages(__FILE__);

/**
 * Class DependenceTable
 *
 * Fields:
 * <ul>
 * <li> TASK_ID int mandatory
 * <li> DEPENDS_ON_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Tasks
 *
 * See GanttDependency (tasks/install/public/js/tasks/gantt.js) for client-side implementation of this logic
 * This class does not check rights
 *
 **/

final class ProjectDependenceTable extends Mesh
{
	const LINK_TYPE_START_START = 		0x00;
	const LINK_TYPE_START_FINISH = 		0x01;
	const LINK_TYPE_FINISH_START = 		0x02;
	const LINK_TYPE_FINISH_FINISH = 	0x03;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_proj_dep';
	}

	public static function getIDColumnName()
	{
		return 'TASK_ID';
	}

	public static function getPARENTIDColumnName()
	{
		return 'DEPENDS_ON_ID';
	}

	/**
	 * @return static
	 */
	public static function getClass()
	{
		return get_called_class();
	}

	protected static function allowMultipleParents()
	{
		return true;
	}

	public static function getLinkCountForUser($userId = 0)
	{
		if(!$userId)
		{
			$userId = \Bitrix\Tasks\Util\User::getId();
		}

		$item = \Bitrix\Main\HttpApplication::getConnection()->query("select count(*) as CNT from ".static::getTableName()." where ".static::getDIRECTColumnName()." = '1' and CREATOR_ID = '".intval($userId)."'")->fetch();

		return intval($item['CNT']);
	}

	public static function createLink($id, $parentId, $behaviour = array())
	{
		// bless the php7
		// was: ($id, $parentId, $type = self::LINK_TYPE_FINISH_START, array $behaviour = array())

		$type = self::LINK_TYPE_FINISH_START;

		if(func_num_args() > 2) // the third argument may be $type or $behaviour
		{
			$arg3 = func_get_arg(2);
			if(is_array($arg3))
			{
				$behaviour = $arg3;
			}
			else
			{
				$type = $arg3;
				if(func_num_args() > 3)
				{
					$behaviour = func_get_arg(3);
				}
			}
		}
		else
		{
			$behaviour = array();
		}

		if(array_key_exists('LINK_TYPE', $behaviour))
		{
			$type = $behaviour['LINK_TYPE'];
		}

		$id = 			Assert::expectIntegerPositive($id, '$id');
		$parentId = 	Assert::expectIntegerPositive($parentId, '$parentId');
		$type = 		Assert::expectEnumerationMember($type, array(
			self::LINK_TYPE_START_START,
			self::LINK_TYPE_START_FINISH,
			self::LINK_TYPE_FINISH_START,
			self::LINK_TYPE_FINISH_FINISH
		), '$type');

		$exceptionInfo = array(
			'AUX' => array(
				'MESSAGE' => array(
					'FROM_TASK_ID' => $parentId,
					'TASK_ID' => $id,
					'LINK_TYPE' => $type
				)
			)
		);

		$result = new AddResult();

		if(is_array($behaviour['TASK_DATA']) && !empty($behaviour['TASK_DATA']))
		{
			$toTask = $behaviour['TASK_DATA'];
		}
		else
		{
			$toTask = 	TaskTable::getById($id)->fetch();
		}
		if(empty($toTask))
		{
			throw new ActionFailedException('Task not found', $exceptionInfo);
		}

		if(is_array($behaviour['PARENT_TASK_DATA']) && !empty($behaviour['PARENT_TASK_DATA']))
		{
			$fromTask = $behaviour['PARENT_TASK_DATA'];
		}
		else
		{
			$fromTask = 	TaskTable::getById($parentId)->fetch();
		}
		if(empty($fromTask))
		{
			throw new ActionFailedException('Parent task not found', $exceptionInfo);
		}

		if((string) $toTask['CREATED_DATE'] == '')
		{
			$result->addError(new Error(Loc::getMessage('DEPENDENCE_ENTITY_CANT_ADD_LINK_CREATED_DATE_NOT_SET')));
		}
		if((string) $toTask['END_DATE_PLAN'] == '')
		{
			$result->addError(new Error(Loc::getMessage('DEPENDENCE_ENTITY_CANT_ADD_LINK_END_DATE_PLAN_NOT_SET')));
		}

		if((string) $fromTask['CREATED_DATE'] == '')
		{
			$result->addError(new Error(Loc::getMessage('DEPENDENCE_ENTITY_CANT_ADD_LINK_CREATED_DATE_NOT_SET_PARENT_TASK')));
		}
		if((string) $fromTask['END_DATE_PLAN'] == '')
		{
			$result->addError(new Error(Loc::getMessage('DEPENDENCE_ENTITY_CANT_ADD_LINK_END_DATE_PLAN_NOT_SET_PARENT_TASK')));
		}

		if(!$result->isSuccess())
		{
			return $result;
		}
		else
		{
			$linkData = array('TYPE' => $type);
			if(array_key_exists('CREATOR_ID', $behaviour) && intval($behaviour['CREATOR_ID']))
			{
				$linkData['CREATOR_ID'] = $behaviour['CREATOR_ID'];
			}

			return parent::createLink($id, $parentId, array('LINK_DATA' => $linkData));
		}
	}

	protected static function applyCreateRestrictions(&$id, &$parentId)
	{
		if(static::checkLinkExists($id, $parentId, array('BIDIRECTIONAL' => true)))
		{
			throw new Tree\LinkExistsException(false, array('NODES' => array($id, $parentId)));
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

	protected static function applyDeleteRestrictions(&$id, &$parentId)
	{
		$id = 			Assert::expectIntegerPositive($id, '$id');

		if($parentId !== false && !static::checkLinkExists($id, $parentId))
		{
			throw new Tree\LinkNotExistException(false, array('NODES' => array($id, $parentId)));
		}
	}

	/**
	 * Returns a list of INGOING DIRECT links according to the old-style (sutable for CTask::GetList()) filter
	 * A heavy-artillery function
	 */
	public static function getListByLegacyTaskFilter(array $filter = array(), array $parameters = array())
	{
		$mixins = TaskTable::getRuntimeMixins(
			array(
				array(
					'CODE' => 			'LEGACY_FILTER',
					'FILTER' => 		$filter,
					'REF_FIELD' => 		'TASK_ID',
				)
			)
		);

		if(!empty($mixins))
		{
			if(!is_array($parameters['runtime']))
			{
				$parameters['runtime'] = array();
			}

			$parameters['runtime'] = array_merge($parameters['runtime'], $mixins);
		}

		$parameters['filter']['=DIRECT'] = '1';

		return self::getList($parameters);
	}

	/**
	 * For better perfomance purposes
	 */
	public static function getSubTreeSql($id)
	{
		$id = Assert::expectIntegerPositive($id, '$id');

		global $DB;

		$tableName = 		static::getTableName();

		$parentColName = 	static::getPARENTIDColumnName();
		$idColName = 		static::getIDColumnName();
		$directColName = 	static::getDIRECTColumnName();

		// select STATUS as REAL_STATUS, according to the CTasks::GetList() behaviour
		// (in \Bitrix\Tasks\TaskTable there is different alias naming)

		/*
		--DEP_T.DURATION_PLAN as DURATION_PLAN,
		*/

		// enough data to say if and how we can change dates
		// look also at \Bitrix\Tasks\Util\Scheduler::initializeTargetTask()

		// CDatabase::DateToCharFunction() converts database format like "2015-10-15 00:00:00"
		// to a site format: "22.06.2015 11:39:50", and optionally adds the timezone offset
		return "
			select
				DEP.".$idColName." as ".$idColName.",
				DEP_P.TYPE as TYPE,
				DEP_P.".$parentColName." as FROM_TASK_ID,

				DEP_T.ID as ID,
				DEP_T.MATCH_WORK_TIME as MATCH_WORK_TIME,
				DEP_T.ALLOW_CHANGE_DEADLINE as ALLOW_CHANGE_DEADLINE,
				DEP_T.DURATION_TYPE as DURATION_TYPE,

				DEP_T.RESPONSIBLE_ID as RESPONSIBLE_ID,
				DEP_T.CREATED_BY as CREATED_BY,
				DEP_T.GROUP_ID as GROUP_ID,
				DEP_T.STATUS as REAL_STATUS,

				".$DB->DateToCharFunction("DEP_T.CREATED_DATE", "FULL")." as CREATED_DATE,
				".$DB->DateToCharFunction("DEP_T.START_DATE_PLAN", "FULL")." as START_DATE_PLAN,
				".$DB->DateToCharFunction("DEP_T.END_DATE_PLAN", "FULL")." as END_DATE_PLAN
			from
				".$tableName." DEP

			inner join
				b_tasks DEP_T
					on
						DEP.".$idColName." = DEP_T.ID
			inner join
				".$tableName." DEP_P
					on
						DEP_P.".$directColName." = '1' and DEP.".$idColName." = DEP_P.".$idColName."

			where DEP.".$parentColName." = '".intval($id)."'
		";
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		// to avoid warnings in php7
		$entityName = '\\Bitrix\\Tasks\\TaskTable';
		if(func_num_args() > 0)
		{
			$entityName = func_get_arg(0);
		}

		$map = array_merge(array(
			new Entity\IntegerField('TASK_ID', array(
				'primary' => true,
				'title' => Loc::getMessage('DEPENDENCE_ENTITY_TASK_ID_FIELD'),
				'required' => true
			)),
			new Entity\IntegerField('DEPENDS_ON_ID', array(
				'primary' => true,
				'title' => Loc::getMessage('DEPENDENCE_ENTITY_DEPENDS_ON_ID_FIELD'),
				'required' => true
			)),
			new Entity\IntegerField('TYPE', array(
				'title' => Loc::getMessage('DEPENDENCE_ENTITY_TYPE_FIELD'),
				//'validation' => array(__CLASS__, 'validateType'),
			)),
			new Entity\IntegerField('CREATOR_ID', array(
			)),
			new Entity\ReferenceField(
				'TASK',
				$entityName,
				array(
					'=this.TASK_ID' => 'ref.ID',
				)
			),
			new Entity\ReferenceField(
				'DEPENDS_ON',
				$entityName,
				array(
					'=this.DEPENDS_ON_ID' => 'ref.ID',
				)
			)
		), parent::getMap('\\Bitrix\\Tasks\\Task\\Dependence'));

		return $map;
	}

	/**
	 * Returns validators for TYPE field.
	 *
	 * @return array
	 */
	public static function validateType()
	{
		return array(
			new Entity\Validator\Enum(null, array(
				static::LINK_TYPE_START_START,
				static::LINK_TYPE_START_FINISH,
				static::LINK_TYPE_FINISH_START,
				static::LINK_TYPE_FINISH_FINISH
			)),
		);
	}

	public static function moveLink($id, $parentId, $behaviour = array('CREATE_PARENT_NODE_ON_NOTFOUND' => true))
	{
		throw new NotImplementedException('Calling moveLink() is meaningless for this entity');
	}

	public static function link($id, $parentId)
	{
		throw new NotImplementedException('Calling link() is meaningless for this entity');
	}

	public static function unlink($id)
	{
		throw new NotImplementedException('Calling unlink() is meaningless for this entity');
	}
}