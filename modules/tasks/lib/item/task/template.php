<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Task;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\Item\Field\Collection;
use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Item\Task\Template\Field;
use Bitrix\Tasks\Item\Field\Scalar;
use Bitrix\Tasks\Util\Replicator;
use Bitrix\Tasks\Item\SystemLog;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Item\Access;

use Bitrix\Tasks\Item\Converter\Task\Template\ToTemplate as TemplateToTemplate;
use Bitrix\Tasks\Item\Converter\Task\Template\ToTask as TemplateToTask;

Loc::loadMessages(__FILE__);

final class Template extends \Bitrix\Tasks\Item
{
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

			// todo:
//			'TAGS' => new Field\Legacy\Tag(array(
//				'NAME' => 'TAGS',
//
//				'SOURCE' => Scalar::SOURCE_TABLET,
//				'DB_READABLE' => false,
//				'DB_WRITABLE' => false,
//
//				'OFFSET_GET_CACHEABLE' => false,
//			)),

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

	public function checkData($result)
	{
		if(parent::checkData($result))
		{
			// data looks good for orm, now check some high-level conditions...

			$this->checkDataMultitasking();
			$this->checkDataTypeNewUser($result);
			$this->checkDataBaseItem($result);

			$this->checkFieldCombinations($result);

			// todo: ???
//			if(array_key_exists('TPARAM_REPLICATION_COUNT', $arFields))
//			{
//				$arFields['TPARAM_REPLICATION_COUNT'] = intval($arFields['TPARAM_REPLICATION_COUNT']);
//			}
//			elseif(!$ID)
//			{
//				$arFields['TPARAM_REPLICATION_COUNT'] = 1;
//			}
		}

		return $result->isSuccess();
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
				$result->addError('BAD_RESPONSIBLE_ERROR', Loc::getMessage('TASKS_ITEM_TASK_TEMPLATE_BAD_RESPONSIBLE_ERROR'));
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