<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Task;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable;
use Bitrix\Tasks\Control\Exception\TemplateAddException;
use Bitrix\Tasks\Control\Exception\TemplateUpdateException;
use Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateTagTable;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\Item\Field\Collection;
use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Item\Task\Template\Field;
use Bitrix\Tasks\Item\Field\Scalar;
use Bitrix\Tasks\Util\Replicator;
use Bitrix\Tasks\Item\SystemLog;
use Bitrix\Tasks\Item\Access;

use Bitrix\Tasks\Item\Converter\Task\Template\ToTemplate as TemplateToTemplate;
use Bitrix\Tasks\Item\Converter\Task\Template\ToTask as TemplateToTask;
use Bitrix\Tasks\Util\Type\Structure;

Loc::loadMessages(__FILE__);

final class Template extends \Bitrix\Tasks\Item
{
	private $permissions;

	public static function getDataSourceClass()
	{
		return TemplateTable::getClass();
	}
	public static function getAccessControllerClass()
	{
		return Access\Task\Template::getClass();
	}
	public static function getUserFieldControllerClass()
	{
		return \Bitrix\Tasks\Util\UserField\Task\Template::getClass();
	}

	protected static function generateMap(array $parameters = array())
	{
		$map = parent::generateMap(array(
			'EXCLUDE' => array(
				'FILES' => true, // deprecated
				'STATUS' => true, // status in the template?!

				// will be overwritten below
				'RESPONSIBLE_ID' => true,
				'ACCOMPLICES' => true,
				'AUDITORS' => true,
				'RESPONSIBLES' => true,
				'DEPENDS_ON' => true,
				'REPLICATE_PARAMS' => true,
				'TAGS' => true,
			)
		));

		$map->placeFields(array(
			// override some tablet fields
			'RESPONSIBLE_ID' => new Template\Field\Legacy\Responsible(array(
				'NAME' => 'RESPONSIBLE_ID',

				'SOURCE' => Scalar::SOURCE_TABLET,
				'DB_READABLE' => false, // will be calculated from RESPONSIBLES
				'DB_WRITABLE' => true,

				'OFFSET_GET_CACHEABLE' => false,
			)),
			'ACCOMPLICES' => new Collection\Integer(array(
				'NAME' => 'ACCOMPLICES',

				'SOURCE' => Scalar::SOURCE_TABLET,
				'DB_READABLE' => true,
				'DB_WRITABLE' => true,
				'DB_SERIALIZED' => true,
			)),
			'AUDITORS' => new Collection\Integer(array(
				'NAME' => 'AUDITORS',

				'SOURCE' => Scalar::SOURCE_TABLET,
				'DB_READABLE' => true,
				'DB_WRITABLE' => true,
				'DB_SERIALIZED' => true,
			)),
			'RESPONSIBLES' => new Collection\Integer(array(
				'NAME' => 'RESPONSIBLES',

				'SOURCE' => Scalar::SOURCE_TABLET,
				'DB_READABLE' => true,
				'DB_WRITABLE' => true,
				'DB_SERIALIZED' => true,
			)),
			'DEPENDS_ON' => new Collection\Integer(array(
				'NAME' => 'DEPENDS_ON',

				'SOURCE' => Scalar::SOURCE_TABLET,
				'DB_READABLE' => true,
				'DB_WRITABLE' => true,
				'DB_SERIALIZED' => true,
			)),
			'REPLICATE_PARAMS' => new Field\ReplicateParams(array(
				'NAME' => 'REPLICATE_PARAMS',

				'SOURCE' => Scalar::SOURCE_TABLET,
				'DB_READABLE' => true,
				'DB_WRITABLE' => true,
			)),

			// sub-entity
			'SE_CHECKLIST' => new Field\CheckList(array(
				'NAME' => 'SE_CHECKLIST',
				'TITLE' => Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_FIELD_SE_CHECKLIST'),

				'SOURCE' => Scalar::SOURCE_CUSTOM,
			)),
			'SE_ACCESS' => new Field\Access(array(
				'NAME' => 'SE_ACCESS',
				'TITLE' => Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_FIELD_SE_ACCESS'),

				'SOURCE' => Scalar::SOURCE_CUSTOM,
			)),
			'SE_TAG' => new Field\Tag(array(
				'NAME' => 'SE_TAG',
				'TITLE' => Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_FIELD_SE_TAG'),

				'SOURCE' => Scalar::SOURCE_TABLET,
				'DB_NAME' => 'TAGS',
				'DB_READABLE' => true,
				'DB_WRITABLE' => true,
			)),
			'BASE_TEMPLATE_ID' => new Field\BaseTemplate(array(
				'NAME' => 'BASE_TEMPLATE_ID',
				'TITLE' => Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_FIELD_BASE_TEMPLATE_ID'),

				'SOURCE' => Scalar::SOURCE_CUSTOM,
			)),
		));

		return $map;
	}
	public static function getFieldsDescription()
	{
		return static::generateMap();
	}

	public static function find(array $parameters = [], $settings = null)
	{
		$result = parent::find($parameters, $settings);

		$ids = [];
		foreach ($result as $template)
		{
			$ids[] = $template['ID'];
		}

		if (empty($ids))
		{
			return $result;
		}

		$members = static::findMembers($parameters, $ids);
		$tags = static::findTags($parameters, $ids);
		$depends = static::findDepends($parameters, $ids);

		if (
			empty($members)
			&& empty($tags)
			&& empty($depends)
		)
		{
			return $result;
		}

		foreach ($result as &$template)
		{
			$templateId = $template['ID'];
			if (isset($members[$templateId]['RESPONSIBLES']))
			{
				$template['RESPONSIBLES'] = $members[$templateId]['RESPONSIBLES'];
			}
			if (isset($members[$templateId]['ACCOMPLICES']))
			{
				$template['ACCOMPLICES'] = $members[$templateId]['ACCOMPLICES'];
			}
			if (isset($members[$templateId]['ACCOMPLICES']))
			{
				$template['ACCOMPLICES'] = $members[$templateId]['ACCOMPLICES'];
			}

			if (isset($tags[$templateId]))
			{
				$template['TAGS'] = $tags[$templateId];
			}
			if (isset($depends[$templateId]))
			{
				$template['DEPENDS_ON'] = $template[$templateId];
			}
		}

		return $result;
	}

	/**
	 * @param array $parameters
	 * @param array $ids
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function findDepends(array $parameters, array $ids): array
	{
		if (
			array_key_exists('select', $parameters)
			&& is_array($parameters['select'])
			&& !empty($parameters['select'])
			&& !in_array('DEPENDS_ON', $parameters['select'])
			&& $parameters['select'][0] !== '*'
		)
		{
			return [];
		}

		$res = TemplateDependenceTable::getList([
			'filter' => [
				'@TEMPLATE_ID' => $ids,
			]
		]);

		$deps = [];
		while ($row = $res->fetch())
		{
			$deps[$row['TEMPLATE_ID']][] = $row['DEPENDS_ON_ID'];
		}

		foreach ($deps as $templateId => $taskIds)
		{
			$deps[$templateId] = serialize($taskIds);
		}

		return $deps;
	}

	/**
	 * @param array $parameters
	 * @param array $ids
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function findTags(array $parameters, array $ids): array
	{
		if (
			array_key_exists('select', $parameters)
			&& is_array($parameters['select'])
			&& !empty($parameters['select'])
			&& !in_array('TAGS', $parameters['select'])
			&& $parameters['select'][0] !== '*'
		)
		{
			return [];
		}

		$res = TemplateTagTable::getList([
			'filter' => [
				'@TEMPLATE_ID' => $ids,
			]
		]);

		$tags = [];
		while ($row = $res->fetch())
		{
			$tags[$row['TEMPLATE_ID']][] = $row['NAME'];
		}

		foreach ($tags as $templateId => $names)
		{
			$tags[$templateId] = serialize($names);
		}

		return $tags;
	}

	/**
	 * @param array $parameters
	 * @param array $ids
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function findMembers(array $parameters, array $ids): array
	{
		if (
			array_key_exists('select', $parameters)
			&& is_array($parameters['select'])
			&& !empty($parameters['select'])
			&& !in_array('RESPONSIBLES', $parameters['select'])
			&& !in_array('ACCOMPLICES', $parameters['select'])
			&& !in_array('AUDITORS', $parameters['select'])
			&& $parameters['select'][0] !== '*'
		)
		{
			return [];
		}

		$res = TemplateMemberTable::getList([
			'filter' => [
				'@TEMPLATE_ID' => $ids,
			]
		]);

		$memberTypes = [
			TemplateMemberTable::MEMBER_TYPE_RESPONSIBLE => 'RESPONSIBLES',
			TemplateMemberTable::MEMBER_TYPE_ACCOMPLICE => 'ACCOMPLICES',
			TemplateMemberTable::MEMBER_TYPE_AUDITOR => 'AUDITORS',
		];

		$members = [];
		while ($row = $res->fetch())
		{
			if (!array_key_exists($row['TYPE'], $memberTypes))
			{
				continue;
			}
			$members[$row['TEMPLATE_ID']][$memberTypes[$row['TYPE']]][] = (int) $row['USER_ID'];
		}

		foreach ($members as $templateId => $templateMembers)
		{
			foreach ($templateMembers as $type => $users)
			{
				$members[$templateId][$type] = serialize($users);
			}
		}

		return $members;
	}

	/**
	 * @param null $result
	 * @return bool
	 *
	 * @deprecated
	 */
	public function canRead($result = null)
	{
		return parent::canRead();
	}

	public function prepareData($result)
	{
		if(parent::prepareData($result))
		{
			$id = $this->getId();

			if(!$id)
			{
				if(!$this->isFieldModified('SITE_ID'))
				{
					$this['SITE_ID'] = $this->getContext()->getSiteId();
				}

				if(!$this->isFieldModified('RESPONSIBLES'))
				{
					$this['RESPONSIBLES'] = array($this->getUserId());
				}

				if(!$this->isFieldModified('CREATED_BY'))
				{
					$this['CREATED_BY'] = $this->getUserId();
				}
			}
		}

		return $result->isSuccess();
	}

	public function save($settings = [])
	{
		$arFields = $this->getFieldsToSave($this->values);
		$id = $this->id;
		if (
			!$id
			&& array_key_exists('ID', $arFields)
		)
		{
			$id = (int) $arFields['ID'];
		}
		unset($arFields['ID']);

		$result = new Result();
		$manager = new \Bitrix\Tasks\Control\Template($this->userId);

		try
		{
			if ($id)
			{
				$template = $manager->update($id, $arFields);
			}
			else
			{
				$template = $manager->add($arFields);
				$this->setId($template->getId());
			}
		}
		catch(TemplateUpdateException | TemplateAddException $e)
		{
			$result->getErrors()->add('TEMPLATE_SAVE', $e->getMessage());
		}
		catch (\Exception $e)
		{
			$result->getErrors()->add('TEMPLATE_SAVE', $e->getMessage());
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getFieldsToSave(array $fields): array
	{
		$arFields = [];
		foreach ($fields as $k => $v)
		{
			if (is_scalar($v) || is_array($v))
			{
				$arFields[$k] = $v;
				continue;
			}

			if (is_a($v, \Bitrix\Tasks\Util\Collection::class))
			{
				$arFields[$k] = $this->getFieldsToSave($v->toArray());
				continue;
			}

			if (is_a($v, Structure::class))
			{
				$arFields[$k] = $this->getFieldsToSave($v->toArray());
				continue;
			}
		}

		return $arFields;
	}

	public function checkData($result)
	{
		if(parent::checkData($result))
		{
			// data looks good for orm, now check some high-level conditions...

			$this->checkDataMultitasking();
			$this->checkDataTypeNewUser($result);
			$this->checkDataBaseItem($result);

			$this->checkFieldCombinations($result);
		}

		return $result->isSuccess();
	}

	public function getAccessPermissions(): array
	{
		if ($this->permissions === null)
		{
			$this->permissions = [];

			$permissions = TasksTemplatePermissionTable::getList([
				'filter' => [
					'=TEMPLATE_ID' => $this->getId()
				]
			])
			->fetchCollection();

			foreach ($permissions as $permission)
			{
				$this->permissions[] = $permission;
			}
		}
		return $this->permissions;
	}

	protected function fetchBaseData($fetchBase = true, $fetchUFs = false)
	{
		$result = parent::fetchBaseData($fetchBase, $fetchUFs);

		$result['TAGS'] = serialize($this->loadTags());
		$result['DEPENDS_ON'] = serialize($this->loadDependTasks());

		$members = $this->loadMembers();
		$result['RESPONSIBLES'] = serialize($members[TemplateMemberTable::MEMBER_TYPE_RESPONSIBLE]);
		$result['ACCOMPLICES'] = serialize($members[TemplateMemberTable::MEMBER_TYPE_ACCOMPLICE]);
		$result['AUDITORS'] = serialize($members[TemplateMemberTable::MEMBER_TYPE_AUDITOR]);

		return $result;
	}

	private function loadDependTasks(): array
	{
		$depends = [];

		$dependList = TemplateDependenceTable::getList([
			'filter' => [
				'=TEMPLATE_ID' => $this->id,
			],
		])->fetchCollection();

		foreach ($dependList as $depend)
		{
			$depends[] = $depend->getDependsOnId();
		}

		return $depends;
	}

	/**
	 * @return array|array[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function loadMembers(): array
	{
		$members = [
			TemplateMemberTable::MEMBER_TYPE_RESPONSIBLE => [],
			TemplateMemberTable::MEMBER_TYPE_ACCOMPLICE => [],
			TemplateMemberTable::MEMBER_TYPE_AUDITOR => [],
		];

		$memberList = TemplateMemberTable::getList([
			'filter' => [
				'=TEMPLATE_ID' => $this->id,
			],
		])->fetchCollection();

		foreach ($memberList as $member)
		{
			$members[$member->getType()][] = $member->getUserId();
		}

		return $members;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function loadTags(): array
	{
		$tagList = TemplateTagTable::getList([
			'filter' => [
				'=TEMPLATE_ID' => $this->id,
			],
		])->fetchCollection();

		$tags = [];
		foreach ($tagList as $tag)
		{
			$tags[] = $tag->getName();
		}

		return $tags;
	}

	protected function modifyTabletDataBeforeSave($data)
	{
		// move 0 to null in PARENT_ID to avoid constraint and query problems
		// todo: move PARENT_ID and other "foreign keys" to the unique way of keeping absense of relation: null, 0 or ''
		if(array_key_exists('PARENT_ID', $data))
		{
			$data['PARENT_ID'] = intval($data['PARENT_ID']);
			if(!intval($data['PARENT_ID']))
			{
				$data['PARENT_ID'] = null;
			}
		}

		return $data;
	}

	protected function doPreActions($state)
	{
		if($state->isModeDelete())
		{
			$id = $this->getId();

			// remove log entries for this entity
			SystemLog::deleteByEntity($id, 1);
		}

		return $state->getResult()->isSuccess();
	}

	protected function doPostActions($state)
	{
		$id = $this->getId();
		if($state->isModeDelete()) // create or update
		{
			Replicator\Task\FromTemplate::unInstallAgent($id);
		}
		else
		{
			if($this->isFieldModified('REPLICATE') || $this->isFieldModified('REPLICATE_PARAMS'))
			{
				// update replication
				Replicator\Task\FromTemplate::reInstallAgent($id, array(
					'CREATED_BY' => $this->getActualFieldValue('CREATED_BY'),
					'REPLICATE' => $this->getActualFieldValue('REPLICATE'),
					'REPLICATE_PARAMS' => $this->getActualFieldValue('REPLICATE_PARAMS')->toArray(),
					'TPARAM_REPLICATION_COUNT' => $this->getActualFieldValue('TPARAM_REPLICATION_COUNT'),
				));
			}
		}

		if ($state->isModeCreate())
		{
			\Bitrix\Tasks\Item\Access\Task\Template::grantAccessLevel($this->getId(), 'U'.$this->getUserId(), 'full', array(
				'CHECK_RIGHTS' => false,
			));
		}
	}

	/**
	 * Creates new virtual (not presented in database) instance of \Bitrix\Tasks\Item\Task\Template based on
	 * data from $this
	 *
	 * @return \Bitrix\Tasks\Item\Converter\Result
	 */
	public function transformToTemplate()
	{
		// todo: better to use the same converter over and over again, so use object pool here when ready
		$converter = new TemplateToTask();

		return $this->transform($converter);
	}

	/**
	 * Creates new virtual (not presented in database) instance of \Bitrix\Tasks\Item\Task based on
	 * data from $this
	 *
	 * @return \Bitrix\Tasks\Item\Converter\Result
	 */
	public function transformToTask()
	{
		// todo: better to use the same converter over and over again, so use object pool here when ready
		$converter = new TemplateToTemplate();

		return $this->transform($converter);
	}

	/**
	 * @param Result $result
	 */
	private function checkDataBaseItem($result)
	{
		if($this->isFieldModified('PARENT_ID') || $this->isFieldModified('BASE_TEMPLATE_ID'))
		{
			$parentId = $this->getActualFieldValue('PARENT_ID');
			$templateId = $this->getActualFieldValue('BASE_TEMPLATE_ID');

			if(intval($parentId) && intval($templateId))
			{
				// can not be set both
				$result->addError('PARENT_ITEM_CONFLICT', Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_PARENT_ITEM_CONFLICT_ERROR'));
			}
		}
	}

	private function checkDataMultitasking()
	{
		// one of those fields was modified, then initiate check
		if($this->isFieldModified('MULTITASK') || $this->isFieldModified('RESPONSIBLES'))
		{
			$this['MULTITASK'] = count($this->getActualFieldValue('RESPONSIBLES')) > 1 ? 'Y' : 'N'; // force multitasking mode
		}
	}

	/**
	 * @param Result $result
	 */
	private function checkDataTypeNewUser($result)
	{
		$id = $this->getId();

		if($id)
		{
			if($this->isFieldModified('TPARAM_TYPE'))
			{
				// you can not switch types while doing update() on template
				if($this->offsetGetPristine('TPARAM_TYPE') != $this['TPARAM_TYPE']);
				{
					$result->addError('TYPE_SWITCH_ERROR', Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_CANT_SWITCH_TYPE_ERROR'));
				}
			}
		}
		else
		{
			$responsibles = $this->getActualFieldValue('RESPONSIBLES');
			$paramType = $this->getActualFieldValue('TPARAM_TYPE');

			if ($paramType != 1 && count($responsibles) == 1 && !$responsibles[0])
			{
				$result->addError('BAD_RESPONSIBLE_ERROR', Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_BAD_ASSIGNEE_ERROR'));
			}
		}
	}

	/**
	 * Checks for some field values that can not be combined together
	 * See BX.Tasks.Component.TasksTaskTemplate.solveFieldOpLock() to deal with the same rules at the client-side
	 *
	 * @param Result $result
	 * @return Result
	 */
	private function checkFieldCombinations($result)
	{
		if($this->isFieldModified('TPARAM_TYPE'))
		{
			if($this->getActualFieldValue('TPARAM_TYPE') == 1) // type is or intend to be 1
			{
				// you cant replicate because of new user
				if($this->getActualFieldValue('REPLICATE') == 'Y')
				{
					$this->setNoReplicationAllowed($result, Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_NO_REPLICATION_ALLOWED_ERROR_BECAUSE_TYPE_1'));
				}

				// you cant use base template because of new user
				if($this->getActualFieldValue('BASE_TEMPLATE_ID'))
				{
					$this->setNoBaseTemplateAllowed($result, Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_NO_BASE_TEMPLATE_ALLOWED_ERROR_BECAUSE_TYPE_1'));
				}

				// you cant do multitasking because of new user
				if($this->getActualFieldValue('MULTITASK') == 'Y')
				{
					$this->setNoMultitaskingAllowed($result, Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_NO_MULTITASKING_ALLOWED_ERROR_BECAUSE_TYPE_1'));
				}
			}
		}
		if($this->isFieldModified('REPLICATE'))
		{
			if($this->getActualFieldValue('REPLICATE') == 'Y') // template is replicating or will be
			{
				// you cant use base template because of REPLICATE
				if($this->getActualFieldValue('BASE_TEMPLATE_ID'))
				{
					$this->setNoBaseTemplateAllowed($result, Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_NO_BASE_TEMPLATE_ALLOWED_ERROR_BECAUSE_REPLICATION'));
				}

				// you cant set type to 1 because of REPLICATE
				if($this->getActualFieldValue('TPARAM_TYPE') == 1)
				{
					$this->setNoTParamType1Allowed($result, Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_NO_TPARAM_TYPE_1_ALLOWED_ERROR_BECAUSE_REPLICATION'));
				}
			}
		}
		if($this->isFieldModified('BASE_TEMPLATE_ID'))
		{
			if($this->getActualFieldValue('BASE_TEMPLATE_ID')) // base template is set or will be set
			{
				// you cant replicate because of base template
				if($this->getActualFieldValue('REPLICATE') == 'Y')
				{
					$this->setNoReplicationAllowed($result, Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_NO_REPLICATION_ALLOWED_ERROR_BASE_TEMPLATE_ID'));
				}

				// you cant set type to 1 because of base template
				if($this->getActualFieldValue('TPARAM_TYPE') == 1)
				{
					$this->setNoTParamType1Allowed($result, Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_NO_TPARAM_TYPE_1_ALLOWED_ERROR_BASE_TEMPLATE_ID'));
				}
			}
		}
		if($this->isFieldModified('MULTITASK'))
		{
			if($this->getActualFieldValue('MULTITASK') == 'Y') // template is producing multitask or will be
			{
				// you cant set type to 1 because of MULTITASK
				if($this->getActualFieldValue('TPARAM_TYPE') == 1)
				{
					$this->setNoTParamType1Allowed($result, Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_NO_TPARAM_TYPE_1_ALLOWED_ERROR_BECAUSE_MULTITASK'));
				}
				// you cant use base template because of MULTITASK
				if($this->getActualFieldValue('BASE_TEMPLATE_ID'))
				{
					$this->setNoBaseTemplateAllowed($result, Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_NO_BASE_TEMPLATE_ALLOWED_ERROR_BECAUSE_MULTITASK'));
				}
			}
		}

		return $result;
	}

	/**
	 * @param Result $result
	 * @param String $message
	 */
	private function setNoMultitaskingAllowed($result, $message = '')
	{
		$result->addError('NO_MULTITASKING_ALLOWED', $message);
	}

	/**
	 * @param Result $result
	 * @param String $message
	 */
	private function setNoBaseTemplateAllowed($result, $message = '')
	{
		$result->addError('NO_BASE_TEMPLATE_ALLOWED', $message);
	}

	/**
	 * @param Result $result
	 * @param String $message
	 */
	private function setNoReplicationAllowed($result, $message = '')
	{
		$result->addError('NO_REPLICATION_ALLOWED', $message);
	}

	/**
	 * @param Result $result
	 * @param String $message
	 */
	private function setNoTParamType1Allowed($result, $message = '')
	{
		$result->addError('NO_TPARAM_TYPE_1_ALLOWED', $message);
	}

	private function getActualFieldValue($name)
	{
		if($this->isFieldModified($name))
		{
			return $this[$name];
		}

		return $this->offsetGetPristine($name);
	}
}