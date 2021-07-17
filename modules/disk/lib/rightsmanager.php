<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\Internals\ObjectPathTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\Rights\SetupSession;
use Bitrix\Disk\Internals\Rights\Table\TmpSimpleRight;
use Bitrix\Disk\Internals\RightTable;
use Bitrix\Disk\Internals\SimpleRightTable;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Collection;

class RightsManager implements IErrorable
{
	const ERROR_COULD_NOT_SET_NEGATIVE_RIGHT = 'DISK_RIGHTS_22002';

	const OP_READ      = 'disk_read';
	const OP_ADD       = 'disk_add';
	const OP_EDIT      = 'disk_edit';
	const OP_SHARING   = 'disk_sharing';
	const OP_DELETE    = 'disk_delete';
	const OP_DESTROY   = 'disk_destroy';
	const OP_RESTORE   = 'disk_restore';
	const OP_SETTINGS  = 'disk_settings';
	const OP_RIGHTS    = 'disk_rights';
	const OP_START_BP  = 'disk_start_bp';
	const OP_CREATE_WF = 'disk_create_wf';

	const TASK_READ    = 'disk_access_read';
	const TASK_EDIT    = 'disk_access_edit';
	const TASK_ADD     = 'disk_access_add';
	const TASK_SHARING = 'disk_access_sharing';
	const TASK_FULL    = 'disk_access_full';

	const DOMAIN_SHARING = 'share';
	const DOMAIN_BIZPROC = 'bp';
	const DOMAIN_BASE    = null;

	/** @var  ErrorCollection */
	protected $errorCollection;
	/** @var  array */
	protected $accessTasks;
	/** @var array  */
	private $operationsByTask = array();

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * Rewrite if exists.
	 * @param \Bitrix\Disk\BaseObject|BaseObject $object
	 * @param array                      $rights
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @return bool
	 */
	public function set(BaseObject $object, array $rights)
	{
		$this->checkUseInternalsRightsOnStorage($object, $rights);
		$this->errorCollection->clear();

		foreach($rights as &$right)
		{
			if (empty($right['OBJECT_ID']))
			{
				$right['OBJECT_ID'] = $object->getId();
			}
			if (!isset($right['NEGATIVE']))
			{
				$right['NEGATIVE'] = 0;
			}
		}
		unset($right);

		$rights = $this->uniqualizeRightsOnObject($rights);
		$rights = $this->cleanWrongNegativeRights($object, $rights);

		$this->deleteInternal($object);
		if(!$this->insertRightsInternal($object, $rights))
		{
			return false;
		}

		$simpleBuilder = new SimpleReBuilder($object, $rights);
		$simpleBuilder->run();

		Driver::getInstance()->getIndexManager()->recalculateRights($object);

		return true;
	}

	/**
	 * BaseObject is leaf (does not have children)
	 * @param \Bitrix\Disk\BaseObject|BaseObject $object
	 * @param array                      $rights
	 * @throws \Bitrix\Main\SystemException
	 * @return bool
	 */
	public function setAsNewLeaf(BaseObject $object, array $rights)
	{
		$this->checkUseInternalsRightsOnStorage($object, $rights);
		$this->errorCollection->clear();

		foreach($rights as &$right)
		{
			if (empty($right['OBJECT_ID']))
			{
				$right['OBJECT_ID'] = $object->getId();
			}
			if (!isset($right['NEGATIVE']))
			{
				$right['NEGATIVE'] = 0;
			}
		}

		$rights = $this->uniqualizeRightsOnObject($rights);
		$rights = $this->cleanWrongNegativeRights($object, $rights);

		if(!$this->insertRightsInternal($object, $rights))
		{
			return false;
		}

		$simpleBuilder = new SimpleReBuilder($object, $rights);
		$simpleBuilder->runAsNewLeaf();

		Driver::getInstance()->getIndexManager()->recalculateRights($object);

		return true;
	}

	private function validateNegativeRights(BaseObject $object, array $rights)
	{
		$negativeRights = array();
		foreach ($rights as $right)
		{
			if (!empty($right['NEGATIVE']))
			{
				$negativeRights[$right['TASK_ID'] . '-' . $right['ACCESS_CODE']] = true;
			}
		}

		if ($negativeRights)
		{
			foreach ($this->getParentsRights($object->getId()) as $right)
			{
				if (empty($right['NEGATIVE']) &&
					isset($negativeRights[$right['TASK_ID'] . '-' . $right['ACCESS_CODE']]))
				{
					unset($negativeRights[$right['TASK_ID'] . '-' . $right['ACCESS_CODE']]);
				}
			}
		}

		return count($negativeRights) == 0;
	}

	/**
	 * Recalculate rights after move
	 * @param \Bitrix\Disk\BaseObject|BaseObject $object
	 * @return bool
	 */
	public function setAfterMove(BaseObject $object)
	{
		$this->errorCollection->clear();

		$existsRights = $this->getSpecificRights($object);
		$rights = $this->cleanWrongNegativeRights($object, $this->uniqualizeRightsOnObject($existsRights));

		$this->deleteInternal($object);
		if(!$this->insertRightsInternal($object, $rights))
		{
			return false;
		}

		$simpleBuilder = new SimpleReBuilder($object, $rights);
		$simpleBuilder->run();

		Driver::getInstance()->getIndexManager()->recalculateRights($object);

		return true;
	}

	/**
	 * Use only after move object.
	 * @param \Bitrix\Disk\BaseObject|BaseObject $object
	 * @param array                      $rights
	 * @return array
	 */
	private function cleanWrongNegativeRights(BaseObject $object, array $rights)
	{
		$dataSetOfRights = new DataSetOfRights($rights);
		$negativeRights = $dataSetOfRights->filterNegative();

		if ($negativeRights->isEmpty())
		{
			//there is only positive rights
			return $rights;
		}

		$validNegativeRights = [];
		$dataSetOfParentRights = new DataSetOfRights($this->getParentsRights($object->getId()));
		$positiveParentRights = $dataSetOfParentRights->filterPositive();

		foreach ($negativeRights as $negativeRight)
		{
			if ($positiveParentRights->isExists([
				'ACCESS_CODE' => $negativeRight['ACCESS_CODE'],
				'TASK_ID' => $negativeRight['TASK_ID'],
			]))
			{
				$validNegativeRights[] = $negativeRight;
			}
		}

		return array_merge($dataSetOfRights->filterPositive()->toArray(), $validNegativeRights);
	}

	public function getSpecificRights(BaseObject $object)
	{
		return RightTable::getList(array(
			'filter' => array(
				'OBJECT_ID' => $object->getId(),
			),
		))->fetchAll();
	}

	public function getAllListNormalizeRights(BaseObject $object)
	{
		$query = new Query(RightTable::getEntity());
		$rights = $query
			->setSelect(array('*', 'DEPTH_LEVEL' => 'PATH_PARENT.DEPTH_LEVEL',))
			->setFilter(array(
				'PATH_PARENT.OBJECT_ID' => $object->getId(),
			))
			->exec()
			->fetchAll()
		;
		Collection::sortByColumn($rights, array('DEPTH_LEVEL' => SORT_DESC));

		return $this->uniqualizeRightsOnObject($rights);
	}

	public function getAllListNormalizeRightsForUserId(BaseObject $object, $userId)
	{
		$query = new Query(RightTable::getEntity());
		$rights = $query
			->setSelect(array('*', 'DEPTH_LEVEL' => 'PATH_PARENT.DEPTH_LEVEL',))
			->setFilter(array(
				'PATH_PARENT.OBJECT_ID' => $object->getId(),
				'USER_ACCESS.USER_ID' => $userId,
			))
			->exec()
			->fetchAll()
		;
		Collection::sortByColumn($rights, array('DEPTH_LEVEL' => SORT_DESC));

		return $this->uniqualizeRightsOnObject($rights);
	}

	public function delete(BaseObject $object)
	{
		if(!$this->deleteInternal($object))
		{
			return false;
		}

		return $this->set($object, array());
	}

	public function deleteByDomain(BaseObject $object, $domain)
	{
		if(empty($domain))
		{
			return false;
		}
		if(!$this->deleteInternal($object, $domain))
		{
			return false;
		}
		if(Application::getConnection()->getAffectedRowsCount() <= 0)
		{
			//todo we don't need to recalc rights
			return true;
		}

		return $this->append($object, array());
	}

	public function append(BaseObject $object, array $rights)
	{
		return $this->set($object, array_merge($this->getSpecificRights($object), $rights));
	}

	public function hasSimpleRight(int $userId, int $objectId): bool
	{
		$sql = "
			SELECT 'x' FROM b_disk_simple_right simple_right
			INNER JOIN b_user_access uaccess ON uaccess.ACCESS_CODE = simple_right.ACCESS_CODE AND uaccess.USER_ID = {$userId}
			WHERE (simple_right.OBJECT_ID = {$objectId})
			LIMIT 1
		";

		$connection = Application::getConnection();

		return (bool)$connection->query($sql)->getSelectedRowsCount();
	}

	public function revokeByAccessCodes(BaseObject $object, array $accessCodes)
	{
		if (empty($accessCodes))
		{
			return true;
		}

		$rights = $this->getSpecificRights($object);
		$isUnset = false;

		foreach ($rights as $id => $right)
		{
			if (in_array($right['ACCESS_CODE'], $accessCodes))
			{
				unset($rights[$id]);
				$isUnset = true;
			}
		}

		if (!$isUnset)
		{
			return true;
		}

		return $this->set($object, $rights);
	}

	/**
	 * Resets simple rights on the object.
	 * Notice: it will be process for long time.
	 * @param BaseObject $object File or Folder.
	 * @return void
	 */
	public function resetSimpleRights(BaseObject $object)
	{
		$rights = $this->getSpecificRights($object);

		$simpleBuilder = new SimpleReBuilder($object, $rights);
		$simpleBuilder->run();
	}

	private function isEqual(array $right1, array $right2)
	{
		return 	$right1['TASK_ID'] == $right2['TASK_ID'] &&
				$right1['NEGATIVE'] == $right2['NEGATIVE'] &&
				$right1['ACCESS_CODE'] === $right2['ACCESS_CODE']
		;
	}

	private function isOpposite(array $right1, array $right2)
	{
		return 	$right1['TASK_ID'] == $right2['TASK_ID'] &&
				$right1['NEGATIVE'] != $right2['NEGATIVE'] &&
				$right1['ACCESS_CODE'] === $right2['ACCESS_CODE']
		;
	}

	/**
	 * Remove non-unique rights.
	 * And drop pair negative + positive on same task_id + access_code
	 * @param array $rights
	 * @return array
	 */
	private function uniqualizeRightsOnObject(array $rights)
	{
		$idToDelete = array();
		$rights = array_values($rights);
		foreach ($rights as $i => $right)
		{
			foreach(array_slice($rights, $i+1, null, true) as $j => $upRight)
			{
				if($this->isOpposite($right, $upRight))
				{
					$idToDelete[$j] = $j;
					$idToDelete[$i] = $i;
				}
			}
			unset($upRight);
		}
		unset($right);

		foreach($idToDelete as $id)
		{
			unset($rights[$id]);
		}
		unset($id);

		$byKey = array();
		foreach($rights as $right)
		{
			$uniqueKey = $right['ACCESS_CODE'] . '-' . $right['TASK_ID'] . '-' . $right['NEGATIVE'];
			if(!isset($byKey[$uniqueKey]))
			{
				$byKey[$uniqueKey] = $right;
			}
		}
		unset($right);

		return array_values($byKey);
	}

	private function deleteInternal(BaseObject $object, $domain = null)
	{
		$filter = array('OBJECT_ID' => $object->getId());
		if($domain !== null)
		{
			$filter['DOMAIN'] = $domain;
		}

		RightTable::deleteBatch($filter);

		return true;
	}

	public function generateDomain($domain, $id)
	{
		return $domain . '-' . $id;
	}

	public function getSharingDomain($id)
	{
		return $this->generateDomain(self::DOMAIN_SHARING, $id);
	}

	public function getIdBySharingDomain($domain)
	{
		return mb_substr($domain, mb_strlen(self::DOMAIN_SHARING.'-'));
	}

	public function getBizProcDomain($id)
	{
		return $this->generateDomain(self::DOMAIN_BIZPROC, $id);
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function getOperationsByTask($taskId)
	{
		if(!isset($this->operationsByTask[$taskId]))
		{

			$this->operationsByTask[$taskId] = \CTask::getOperations($taskId, true);
		}
		return $this->operationsByTask[$taskId];
	}

	public function containsOperationInTask($operationName, $taskId)
	{
		return in_array($operationName, $this->getOperationsByTask($taskId));
	}

	public function listOperations()
	{
		static $operations = null;
		if($operations !== null)
		{
			return $operations;
		}

		$refClass = new \ReflectionClass($this);
		foreach($refClass->getConstants() as $name => $value)
		{
			if(mb_substr($name, 0, 3) === 'OP_')
			{
				$operations[$value] = $value;
			}
		}

        return $operations;
	}

	public function getParentsRights($objectId)
	{
		$query = new Query(RightTable::getEntity());
		$rights = $query
			->setSelect(array('*', 'DEPTH_LEVEL' => 'PATH_PARENT.DEPTH_LEVEL',))
			->setFilter(array(
				'PATH_PARENT.OBJECT_ID' => $objectId,
				'!PATH_PARENT.PARENT_ID' => $objectId,
			))
			->exec()
			->fetchAll()
		;
		Collection::sortByColumn($rights, array('DEPTH_LEVEL' => SORT_DESC));

		return $rights;
	}

	/**
	 * Get rights for all descendants by objectId.
	 * @param $objectId
	 * @return array
	 */
	public function getDescendantsRights($objectId)
	{
		$query = new Query(RightTable::getEntity());
		$query
			->setSelect(array('*', 'DEPTH_LEVEL' => 'PATH_CHILD.DEPTH_LEVEL',))
			->setFilter(array(
				'PATH_CHILD.PARENT_ID' => $objectId,
				'!PATH_CHILD.OBJECT_ID' => $objectId,
			))
		;

		return $query->exec()->fetchAll();
	}

	/**
	 * Get rights for direct children by objectId.
	 * @param $objectId
	 * @return array
	 */
	public function getChildrenRights($objectId)
	{
		$query = new Query(RightTable::getEntity());
		$query
			->setSelect(array('*'))
			->setFilter(array(
				'PATH_CHILD.PARENT_ID' => $objectId,
				'PATH_CHILD.DEPTH_LEVEL' => 1,
			))
		;

		return $query->exec()->fetchAll();
	}

	/**
	 * Get all operations by object (with specific and inherited)
	 * @param $objectId
	 * @param $userId
	 * @return array
	 */
	public function getUserOperationsByObject($objectId, $userId)
	{
		return $this->reformatRightsToOperations(
			$this->getUserRightsByObject($objectId, $userId)
		);
	}

	public function getUserOperationsForChildren($parentId, $userId, array $restrictIds = [])
	{
		$parentId = (int)$parentId;
		$userId = (int)$userId;

		$rightsByObjectId = [];
		if ($restrictIds)
		{
			$restrictIds[] = $parentId;
		}

		$needToLoadByLink = $this->appendChildRightsForChildren($parentId, $userId, $restrictIds, $rightsByObjectId);
		if($needToLoadByLink)
		{
			$this->appendChildRightsForConnectedChildren($parentId, $userId, $restrictIds, $rightsByObjectId);
		}
		$this->appendChildCrRightsForChildren($parentId, $userId, $restrictIds, $rightsByObjectId);
		$this->appendChildAuRightsForChildren($parentId, $userId, $restrictIds, $rightsByObjectId);

		$operations = [];
		foreach ($rightsByObjectId as $objectId => $rights)
		{
			$operations[$objectId] = $this->reformatRightsToOperations($rights);
		}

		return $operations;
	}

	private function appendChildRightsForChildren($parentId, $userId, array $restrictIds, array &$rightsByObjectId)
	{
		$restrictById = '';
		$restrictIds = array_filter(array_map('intval', $restrictIds));
		if ($restrictIds)
		{
			$restrictById = ' AND o.ID IN (' . implode(',', $restrictIds) . ')';
		}

		$query = Application::getConnection()->query("
			SELECT op.NAME, r.TASK_ID, r.DOMAIN, r.NEGATIVE, o.REAL_OBJECT_ID O_REAL_OBJECT_ID, o.ID O_OBJECT_ID, r.ACCESS_CODE
			FROM b_disk_right r
				INNER JOIN b_disk_object_path p ON p.PARENT_ID = r.OBJECT_ID
				INNER JOIN b_disk_object o ON p.OBJECT_ID = o.ID
				INNER JOIN b_task_operation task_op ON r.TASK_ID = task_op.TASK_ID
				INNER JOIN b_operation op ON task_op.OPERATION_ID = op.ID
				INNER JOIN b_user_access uaccess ON r.ACCESS_CODE = uaccess.ACCESS_CODE

			WHERE o.PARENT_ID = {$parentId} AND uaccess.USER_ID = {$userId} {$restrictById}
		"
		);

		$needToLoadByLink = false;
		while ($row = $query->fetch())
		{
			$rightsByObjectId[$row['O_OBJECT_ID']][] = array(
				'ACCESS_CODE' => $row['ACCESS_CODE'],
				'NAME' => $row['NAME'],
				'TASK_ID' => $row['TASK_ID'],
				'DOMAIN' => $row['DOMAIN'],
				'NEGATIVE' => $row['NEGATIVE'],
				'REAL_OBJECT_ID' => $row['O_REAL_OBJECT_ID'],
				'OBJECT_ID' => $row['O_OBJECT_ID'],
			);

			if ($row['O_REAL_OBJECT_ID'] != $row['O_OBJECT_ID'])
			{
				$needToLoadByLink = true;
			}
		}

		return $needToLoadByLink;
	}

	private function appendChildRightsForConnectedChildren($parentId, $userId, array $restrictIds, &$rightsByObjectId)
	{
		$restrictById = '';
		$restrictIds = array_filter(array_map('intval', $restrictIds));
		if ($restrictIds)
		{
			$restrictById = ' AND o.ID IN (' . implode(',', $restrictIds) . ')';
		}

		$query = Application::getConnection()->query("
			SELECT op.NAME, r.TASK_ID, r.DOMAIN, r.NEGATIVE, o.REAL_OBJECT_ID O_REAL_OBJECT_ID, o.ID O_OBJECT_ID, r.ACCESS_CODE
			FROM b_disk_right r
				INNER JOIN b_disk_object_path p ON p.PARENT_ID = r.OBJECT_ID
				INNER JOIN b_disk_object o ON p.OBJECT_ID = o.REAL_OBJECT_ID
				INNER JOIN b_task_operation task_op ON r.TASK_ID = task_op.TASK_ID
				INNER JOIN b_operation op ON task_op.OPERATION_ID = op.ID
				INNER JOIN b_user_access uaccess ON r.ACCESS_CODE = uaccess.ACCESS_CODE

			WHERE o.PARENT_ID = {$parentId} AND uaccess.USER_ID = {$userId} AND o.ID <> o.REAL_OBJECT_ID {$restrictById}
		"
		);

		while ($row = $query->fetch())
		{
			$rightsByObjectId[$row['O_REAL_OBJECT_ID']][] = array(
				'ACCESS_CODE' => $row['ACCESS_CODE'],
				'NAME' => $row['NAME'],
				'TASK_ID' => $row['TASK_ID'],
				'DOMAIN' => $row['DOMAIN'],
				'NEGATIVE' => $row['NEGATIVE'],
				'REAL_OBJECT_ID' => $row['O_REAL_OBJECT_ID'],
				'OBJECT_ID' => $row['O_OBJECT_ID'],
			);
		}
	}

	private function appendChildCrRightsForChildren($parentId, $userId, array $restrictIds, &$rightsByObjectId)
	{
		$restrictById = '';
		$restrictIds = array_filter(array_map('intval', $restrictIds));
		if ($restrictIds)
		{
			$restrictById = ' AND o.ID IN (' . implode(',', $restrictIds) . ')';
		}

		$query = Application::getConnection()->query("
			SELECT op.NAME, r.TASK_ID, r.DOMAIN, r.NEGATIVE, o.REAL_OBJECT_ID O_REAL_OBJECT_ID, o.ID O_OBJECT_ID
			FROM b_disk_right r
				INNER JOIN b_disk_object_path p ON p.PARENT_ID = r.OBJECT_ID
				INNER JOIN b_disk_object o ON p.OBJECT_ID = o.REAL_OBJECT_ID
				INNER JOIN b_task_operation task_op ON r.TASK_ID = task_op.TASK_ID
				INNER JOIN b_operation op ON task_op.OPERATION_ID = op.ID

			WHERE o.PARENT_ID = {$parentId} AND o.CREATED_BY = {$userId} AND r.ACCESS_CODE = 'CR' {$restrictById}
		"
		);

		while ($row = $query->fetch())
		{
			$rightsByObjectId[$row['O_OBJECT_ID']][] = array(
				'ACCESS_CODE' => 'CR',
				'NAME' => $row['NAME'],
				'TASK_ID' => $row['TASK_ID'],
				'DOMAIN' => $row['DOMAIN'],
				'NEGATIVE' => $row['NEGATIVE'],
				'REAL_OBJECT_ID' => $row['O_REAL_OBJECT_ID'],
				'OBJECT_ID' => $row['O_OBJECT_ID'],
			);

			if ($row['O_REAL_OBJECT_ID'] != $row['O_OBJECT_ID'])
			{
				$rightsByObjectId[$row['O_REAL_OBJECT_ID']][] = array(
					'ACCESS_CODE' => 'CR',
					'NAME' => $row['NAME'],
					'TASK_ID' => $row['TASK_ID'],
					'DOMAIN' => $row['DOMAIN'],
					'NEGATIVE' => $row['NEGATIVE'],
					'REAL_OBJECT_ID' => $row['O_REAL_OBJECT_ID'],
					'OBJECT_ID' => $row['O_OBJECT_ID'],
				);
			}
		}
	}

	private function appendChildAuRightsForChildren($parentId, $userId, array $restrictIds, &$rightsByObjectId)
	{
		$restrictById = '';
		$restrictIds = array_filter(array_map('intval', $restrictIds));
		if ($restrictIds)
		{
			$restrictById = ' AND o.ID IN (' . implode(',', $restrictIds) . ')';
		}

		$query = Application::getConnection()->query("
			SELECT op.NAME, r.TASK_ID, r.DOMAIN, r.NEGATIVE, o.REAL_OBJECT_ID O_REAL_OBJECT_ID, o.ID O_OBJECT_ID
			FROM b_disk_right r
				INNER JOIN b_disk_object_path p ON p.PARENT_ID = r.OBJECT_ID
				INNER JOIN b_disk_object o ON p.OBJECT_ID = o.ID
				INNER JOIN b_task_operation task_op ON r.TASK_ID = task_op.TASK_ID
				INNER JOIN b_operation op ON task_op.OPERATION_ID = op.ID

			WHERE o.PARENT_ID = {$parentId} AND r.ACCESS_CODE = 'AU' {$restrictById}
		"
		);

		while ($row = $query->fetch())
		{
			$rightsByObjectId[$row['O_OBJECT_ID']][] = array(
				'ACCESS_CODE' => 'AU',
				'NAME' => $row['NAME'],
				'TASK_ID' => $row['TASK_ID'],
				'DOMAIN' => $row['DOMAIN'],
				'NEGATIVE' => $row['NEGATIVE'],
				'REAL_OBJECT_ID' => $row['O_REAL_OBJECT_ID'],
				'OBJECT_ID' => $row['O_OBJECT_ID'],
			);

			if ($row['O_REAL_OBJECT_ID'] != $row['O_OBJECT_ID'])
			{
				$rightsByObjectId[$row['O_REAL_OBJECT_ID']][] = array(
					'ACCESS_CODE' => 'AU',
					'NAME' => $row['NAME'],
					'TASK_ID' => $row['TASK_ID'],
					'DOMAIN' => $row['DOMAIN'],
					'NEGATIVE' => $row['NEGATIVE'],
					'REAL_OBJECT_ID' => $row['O_REAL_OBJECT_ID'],
					'OBJECT_ID' => $row['O_OBJECT_ID'],
				);
			}
		}
	}

	/**
	 * Get tasks associated with module
	 * @return array
	 */
	public function getTasks()
	{
		$this->loadAccessTasks();

		return $this->accessTasks;
	}

	public function isValidTaskName($taskName)
	{
		return in_array($taskName, array_column($this->getTasks(), 'NAME'), true);
	}

	public function getTaskById($taskId)
	{
		$this->loadAccessTasks();
		if(isset($this->accessTasks[$taskId]))
		{
			return $this->accessTasks[$taskId];
		}

		return null;
	}

	public function getTaskIdByName($name)
	{
		$this->loadAccessTasks();
		foreach($this->accessTasks as $task)
		{
			if(isset($task['NAME']) && $task['NAME'] == $name)
			{
				return $task['ID'];
			}
		}
		unset($task);

		return null;
	}

	public function getTaskNameById($taskId)
	{
		$this->loadAccessTasks();
		if(isset($this->accessTasks[$taskId]))
		{
			return $this->accessTasks[$taskId]['NAME'];
		}

		return null;
	}

	public function getTaskTitleByName($name)
	{
		$this->loadAccessTasks();
		foreach($this->accessTasks as $task)
		{
			if(isset($task['NAME']) && $task['NAME'] == $name)
			{
				return $task['TITLE'];
			}
		}

		return null;
	}

	/**
	 * This is specific function for Sharing model. And you can't use this to compare rights task in another contexts.
	 * We don't check operations in task, Compare only pseudo-names.
	 * @param $taskName1
	 * @param $taskName2
	 * @return int Returns < 0 if $taskName1 is less than $taskName2; > 0 if $taskName1 is greater than $taskName2, and 0 if they are equal.
	 * @internal
	 */
	public function pseudoCompareTaskName($taskName1, $taskName2)
	{
		switch($taskName1)
		{
			case 'disk_access_read':
				RightsManager::TASK_READ;
				$taskName1Pos = 2;
				break;
			case 'disk_access_add':
				RightsManager::TASK_ADD;
				$taskName1Pos = 3;
				break;
			case 'disk_access_edit':
				RightsManager::TASK_EDIT;
				$taskName1Pos = 4;
				break;
			case 'disk_access_full':
				RightsManager::TASK_FULL;
				$taskName1Pos = 5;
				break;
			default:
				//unknown task names
				$taskName1Pos = -1;
		}
		switch($taskName2)
		{
			case 'disk_access_read':
				RightsManager::TASK_READ;
				$taskName2Pos = 2;
				break;
			case 'disk_access_add':
				RightsManager::TASK_ADD;
				$taskName2Pos = 3;
				break;
			case 'disk_access_edit':
				RightsManager::TASK_EDIT;
				$taskName2Pos = 4;
				break;
			case 'disk_access_full':
				RightsManager::TASK_FULL;
				$taskName2Pos = 5;
				break;
			default:
				//unknown task names
				$taskName2Pos = -1;
		}
		if($taskName1Pos == $taskName2Pos)
		{
			return 0;
		}

		return $taskName1Pos > $taskName2Pos? 1 : -1;
	}

	/**
	 * This is specific function for Sharing model. And you can't use this in another contexts.
	 * @param BaseObject $object
	 * @param        $userId
	 * @return null|string
	 * @internal
	 */
	public function getPseudoMaxTaskByObjectForUser(BaseObject $object, $userId)
	{
		$maxTaskName = null;
		foreach($this->getAllListNormalizeRightsForUserId($object->getRealObject(), $userId) as $rightOnObject)
		{
			if(empty($rightOnObject['NEGATIVE']))
			{
				$taskName = $this->getTaskNameById($rightOnObject['TASK_ID']);
				if($taskName && ($this->pseudoCompareTaskName($taskName, $maxTaskName) > 0))
				{
					$maxTaskName = $taskName;
				}
			}
		}
		unset($rightOnObject);

		return $maxTaskName;
	}

	protected function loadAccessTasks()
	{
		if($this->accessTasks)
		{
			return $this;
		}

		$this->accessTasks = array();
		/** @noinspection PhpUndefinedClassInspection */
		$query = \CTask::getList(array('ID' => 'asc'), array('MODULE_ID' => 'disk',));
		while($task = $query->fetch())
		{
			$this->accessTasks[$task['ID']] = $task;
		}

		return $this;
	}

	/**
	 * @param \Bitrix\Disk\BaseObject|BaseObject $object
	 * @param array                      $rights
	 * @return bool
	 */
	private function insertRightsInternal(BaseObject $object, array $rights)
	{
		$rightsToInsert = array();
		foreach ($rights as $right)
		{
			if (isset($right['NEGATIVE']))
			{
				$right['NEGATIVE'] = (int)$right['NEGATIVE'];
			}
			else
			{
				$right['NEGATIVE'] = 0; //default value @see \Bitrix\Disk\Internals\RightTable
			}

			if (!isset($right['DOMAIN']))
			{
				$right['DOMAIN'] = null;
			}

			$rightsToInsert[] = array(
				'OBJECT_ID' => $object->getId(),
				'TASK_ID' => $right['TASK_ID'],
				'ACCESS_CODE' => $right['ACCESS_CODE'],
				'DOMAIN' => $right['DOMAIN'],
				'NEGATIVE' => $right['NEGATIVE'],
			);
		}

		RightTable::insertBatch($rightsToInsert);

		return true;
	}

	private function checkUseInternalsRightsOnStorage(BaseObject $object, $rights)
	{
		$storageModel = Storage::loadById($object->getStorageId());
		if($storageModel && !empty($rights) && !$storageModel->isUseInternalRights())
		{
			throw new SystemException('Attempt to set the rights, but not to use the internal rights.');
		}
	}

	/**
	 * @param array $rights
	 * @return array
	 */
	private function reformatRightsToOperations(array $rights)
	{
		$operations = array();
		foreach($rights as $right)
		{
			$key = $right['TASK_ID'] . '-' . $right['ACCESS_CODE'];
			if(!empty($right['NEGATIVE']))
			{
				if(isset($operations[$key]))
				{
					unset($operations[$key]);
				}
				continue;
			}

			if(!isset($key))
			{
				$operations[$key] = array();
			}
			$operations[$key][] = $right['NAME'];
		}
		unset($right);

		$operationNames = array();
		foreach($operations as $key => $item)
		{
			$operationNames = array_merge($operationNames, array_values($item));
		}
		unset($key);
		$values = array_values(array_unique($operationNames));

		//https://bugs.php.net/bug.php?id=29972
		return $values? array_combine($values, $values) : array();
	}

	/**
	 * @param $objectId
	 * @param $userId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function getUserRightsByObject($objectId, $userId)
	{
		$query = new Query(RightTable::getEntity());
		$rights = $query
			->setSelect(array(
				'ACCESS_CODE',
				'TASK_ID',
				'NEGATIVE',
				'NAME' => 'TASK_OPERATION.OPERATION.NAME',
				'DEPTH_LEVEL' => 'PATH_PARENT.DEPTH_LEVEL',
			))
			->setFilter(array(
				'PATH_PARENT.OBJECT_ID' => $objectId,
				'USER_ACCESS.USER_ID' => $userId,
			))->exec()->fetchAll();

		$query = new Query(RightTable::getEntity());
		$query
			->setSelect(
				array(
					'ACCESS_CODE',
					'TASK_ID',
					'NEGATIVE',
					'NAME' => 'TASK_OPERATION.OPERATION.NAME',
					'DEPTH_LEVEL' => 'PATH_PARENT.DEPTH_LEVEL',
				)
			)
			->setFilter(
				array(
					'LOGIC' => 'OR',
					array(
						'PATH_PARENT.OBJECT_ID' => $objectId,
						'=ACCESS_CODE' => 'CR',
					),
					array(
						'PATH_PARENT.OBJECT_ID' => $objectId,
						'=ACCESS_CODE' => 'AU',
					)
				)
			)
		;

		$creatorRights = array();
		foreach($query->exec()->fetchAll() as $additionalRight)
		{
			if($additionalRight['ACCESS_CODE'] === 'AU')
			{
				$rights[] = $additionalRight;
			}
			elseif($additionalRight['ACCESS_CODE'] === 'CR')
			{
				$creatorRights[] = $additionalRight;
			}
		}

		if($creatorRights)
		{
			$query = new Query(ObjectTable::getEntity());
			$query
				->setSelect(array('CREATED_BY'))
				->addFilter('=ID', $objectId)
			;
			$creatorData = $query->exec()->fetch();
			if($creatorData && $creatorData['CREATED_BY'] == $userId)
			{
				$rights = array_merge($rights, $creatorRights);
			}
		}

		Collection::sortByColumn($rights, array('DEPTH_LEVEL' => SORT_DESC));

		return $rights;
	}

	/**
	 * Add to parameters rights check by security context for use in getList.
	 * @param Security\SecurityContext $securityContext
	 * @param array                    $parameters
	 * @param array                    $specificColumns List of columns to use in $securityContext->getSqlExpressionForList.
	 * @return array
	 */
	public function addRightsCheck(Security\SecurityContext $securityContext, array $parameters, array $specificColumns)
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = array();
		}
		if(!isset($parameters['runtime']))
		{
			$parameters['runtime'] = array();
		}
		$parameters['runtime'][] = new ExpressionField('RIGHTS_CHECK',
			'CASE WHEN ' . $securityContext->getSqlExpressionForList('%1$s', '%2$s') . ' THEN 1 ELSE 0 END', $specificColumns, array('data_type' => 'boolean',)
		);

		if ($parameters['filter'] instanceof ConditionTree)
		{
			$parameters['filter']->addCondition(Query::filter()->where('RIGHTS_CHECK', true));
		}
		else
		{
			$parameters['filter']['=RIGHTS_CHECK'] = true;
		}

		return $parameters;
	}
}

final class SimpleReBuilder
{
	const SCENARIO_AS_NEW_LEAF = 'leaf';
	const SCENARIO_FULL_RECALC = 'recalc';
	const SCENARIO_SKIP        = 'skip';

	/** @var \Bitrix\Disk\BaseObject */
	protected $object;
	/** @var array */
	protected $specificRights;
	/** @var array */
	protected $simpleRights;
	/** @var array */
	protected $simpleRightsFromParent;
	/** @var array */
	protected $parentRights;
	/** @var  SetupSession */
	protected $setupSession;
	/** @var string */
	protected $scenario = self::SCENARIO_FULL_RECALC;

	public function __construct(BaseObject $object, array $specificRights)
	{
		$this->object = $object;
		$this->specificRights = $specificRights;
		$this->setupSession = SetupSession::register($this->object->getId());
	}

	/**
	 * Set rights and calculate simple rights with children.
	 */
	public function run()
	{
		$this->scenario = self::SCENARIO_FULL_RECALC;

		$this->runByOnceObject();
		$this->fillChildren();

		$this->finalize();
	}

	/**
	 * Set rights and calculate simple rights without operation with children.
	 */
	public function runAsNewLeaf()
	{
		$this->scenario = self::SCENARIO_AS_NEW_LEAF;

		$this->runByOnceObject();

		$this->finalize();
	}

	public function finalize()
	{
		if($this->scenario === self::SCENARIO_SKIP)
		{
			return;
		}

		$this->setupSession->finish();

		$this->scenario = self::SCENARIO_SKIP;
	}

	private function runByOnceObject()
	{
		$this->simpleRights = $this->uniqualizeSimpleRights(
			$this->getNewSimpleRight(
				$this->specificRights,
				$this->getParentRights(),
				$this->getSimpleRightsFromParent()
		));
		$items = array();
		foreach($this->simpleRights as $right)
		{
			$items[] = array(
				'OBJECT_ID' => $this->object->getId(),
				'ACCESS_CODE' => $right['ACCESS_CODE'],
			);
		}
		unset($right);

		TmpSimpleRight::insertBatchBySessionId($items, $this->setupSession->getId());
	}

	private function uniqualizeSimpleRights(array $rights)
	{
		$byKey = array();
		foreach ($rights as $right)
		{
			if(!isset($byKey[$right['ACCESS_CODE']]))
			{
				$byKey[$right['ACCESS_CODE']] = $right;
			}
		}
		unset($right);

		return array_values($byKey);
	}

	private function getParentRights()
	{
		if($this->parentRights !== null)
		{
			return $this->parentRights;
		}
		$this->parentRights = Driver::getInstance()->getRightsManager()->getParentsRights($this->object->getId());
		Collection::sortByColumn($this->parentRights, array('DEPTH_LEVEL' => SORT_DESC));

		return $this->parentRights;
	}

	private function getNewSimpleRight(array $specificRights, array $parentRights, array $parentSimpleRights)
	{
		list($canRead, $mayCannotRead) = $this->splitRightsByReadable($specificRights);
		if(count($mayCannotRead))
		{
			//analyze negative + positive read rights. Clean pair
			$rightsManager = Driver::getInstance()->getRightsManager();
			$readableRightsFromParents = array();
			foreach($parentRights as $parentRight)
			{
				if(!$rightsManager->containsOperationInTask($rightsManager::OP_READ, $parentRight['TASK_ID']))
				{
					continue;
				}
				$key = $parentRight['TASK_ID'] . '-' . $parentRight['ACCESS_CODE'];
				if(empty($parentRight['NEGATIVE']))
				{
					$readableRightsFromParents[$key] = $parentRight;
					continue;
				}
				elseif(isset($readableRightsFromParents[$key]))
				{
					unset($readableRightsFromParents[$key]);
				}
			}
			unset($parentRight);
			//clean parent read right if we have negative right on this task_id + access_code
			foreach($readableRightsFromParents as $i => $right)
			{
				if(isset($mayCannotRead[$right['ACCESS_CODE']]))
				{
					foreach($mayCannotRead[$right['ACCESS_CODE']] as $cannotReadRight)
					{
						if($cannotReadRight['TASK_ID'] == $right['TASK_ID'])
						{
							unset($readableRightsFromParents[$i]);
							break;
						}
					}
					unset($cannotReadRight);
				}
			}
			unset($right);
			//in $readableRightsFromParents stay only readable rights by task and access code.
			$simpleRights = $readableRightsFromParents;
		}
		else
		{
			$simpleRights = $parentSimpleRights;
		}
		foreach($canRead as $accessCode => $rights)
		{
			$simpleRights = array_merge($simpleRights, $rights);
		}
		unset($accessCode, $rights);

		return $simpleRights;
	}

	private function getSimpleRightsFromParent()
	{
		if($this->simpleRightsFromParent !== null)
		{
			return $this->simpleRightsFromParent;
		}

		if($this->object->getParentId())
		{
			$this->simpleRightsFromParent = SimpleRightTable::getList(array('filter' => array(
				'OBJECT_ID' => $this->object->getParentId(),
			)))->fetchAll();
		}
		else
		{
			$this->simpleRightsFromParent = array();
		}

		return $this->simpleRightsFromParent;
	}

	private function splitRightsByReadable(array $specificRights)
	{
		$rightsManager = Driver::getInstance()->getRightsManager();
		$canRead = $cannotRead = array();
		foreach ($specificRights as $right)
		{
			if($rightsManager->containsOperationInTask($rightsManager::OP_READ, $right['TASK_ID']))
			{
				if(empty($right['NEGATIVE']))
				{
					if(!isset($canRead[$right['ACCESS_CODE']]))
					{
						$canRead[$right['ACCESS_CODE']] = array();
					}
					$canRead[$right['ACCESS_CODE']][] = $right;
				}
				else
				{
					if(!isset($cannotRead[$right['ACCESS_CODE']]))
					{
						$cannotRead[$right['ACCESS_CODE']] = array();
					}
					$cannotRead[$right['ACCESS_CODE']][] = $right;
				}
			}
		}
		unset($right);

		return array($canRead, $cannotRead);
	}

	private function fillChildren()
	{
		if($this->object instanceof File)
		{
			return;
		}

		$specificRightsByObjectId = array(
			$this->object->getId() => $this->specificRights,
		);
		//store all rights on object (all inherited rights)
		$inheritedRightsByObjectId = array(
			$this->object->getId() => $this->getParentRights(),
		);

		$childrenRights = Driver::getInstance()->getRightsManager()->getDescendantsRights($this->object->getId());
		if(!$childrenRights)
		{
			TmpSimpleRight::fillDescendants($this->object->getId(), $this->setupSession->getId());
			return;
		}

		//store all specific rights on object
		foreach ($childrenRights as $right)
		{
			if(!isset($specificRightsByObjectId[$right['OBJECT_ID']]))
			{
				$specificRightsByObjectId[$right['OBJECT_ID']] = array();
			}
			$specificRightsByObjectId[$right['OBJECT_ID']][] = $right;
		}
		unset($right, $childrenRights);

		$simpleRightsByObjectId = array(
			$this->object->getId() => $this->simpleRights,
		);

		$query = ObjectTable::getDescendants($this->object->getId(), array('select' => array('ID', 'PARENT_ID')));
		while($object = $query->fetch())
		{
			//specific rights on object
			if(!isset($specificRightsByObjectId[$object['ID']]))
			{
				$specificRightsByObjectId[$object['ID']] = array();
			}
			if(!isset($inheritedRightsByObjectId[$object['ID']]))
			{
				$inheritedRightsByObjectId[$object['ID']] = array();
			}
			if(!isset($simpleRightsByObjectId[$object['PARENT_ID']]))
			{
				$simpleRightsByObjectId[$object['PARENT_ID']] = array();
			}
			if(isset($inheritedRightsByObjectId[$object['PARENT_ID']]))
			{
				$inheritedRightsByObjectId[$object['ID']] = array_merge(
					$inheritedRightsByObjectId[$object['PARENT_ID']],
					($specificRightsByObjectId[$object['PARENT_ID']]?: array())
				);
			}
			else
			{
				$inheritedRightsByObjectId[$object['PARENT_ID']] = array();
			}

			$simpleRightsByObjectId[$object['ID']] = $this->uniqualizeSimpleRights(
				$this->getNewSimpleRight(
					$specificRightsByObjectId[$object['ID']],
					$inheritedRightsByObjectId[$object['ID']],
					$simpleRightsByObjectId[$object['PARENT_ID']]
			));

			$items = array();
			foreach($simpleRightsByObjectId[$object['ID']] as $right)
			{
				$items[] = array(
					'OBJECT_ID' => $object['ID'],
					'ACCESS_CODE' => $right['ACCESS_CODE'],
				);
			}
			unset($right);

			TmpSimpleRight::insertBatchBySessionId($items, $this->setupSession->getId());
		}
		unset($object);
	}
}

final class RightsSetter implements Internals\Error\IErrorable
{
	/** @var  ErrorCollection */
	protected $errorCollection;
	/** @var \Bitrix\Main\DB\Connection */
	protected $connection;
	/** @var \Bitrix\Main\Db\SqlHelper */
	protected $sqlHelper;

	/** @var BaseObject */
	protected $object;
	/** @var array */
	protected $parentRights;
	/** @var array */
	protected $rights = array();

	/** @var array */
	protected $currentRightsOnObject = array();

	/**
	 * Constructor RightsSetter.
	 * @param BaseObject $object
	 * @param array      $rights
	 */
	public function __construct(BaseObject $object, array $rights)
	{
		$this->object = $object;
		$this->rights = $rights;

		$this->connection = Application::getConnection();
		$this->sqlHelper = Application::getConnection()->getSqlHelper();
	}

	public function run()
	{
		foreach($this->rights as $right)
		{
			$this->appendOne($right);
		}
		unset($right);
	}

	private function appendOne(array $right)
	{
		$hadOppositeRight = false;
		foreach($this->getCurrentRightsOnObject() as $currentRight)
		{
			if($this->isEqual($currentRight, $right))
			{
				return true;
			}
			if($this->isOpposite($currentRight, $right))
			{
				$hadOppositeRight = true;
				RightTable::delete($currentRight['ID']);
			}
		}
		unset($currentRight);

		return empty($right['NEGATIVE'])?
			$this->appendOnePositive($right, $hadOppositeRight) : $this->appendOneNegative($right, $hadOppositeRight);
	}

	private function isEqual(array $right1, array $right2)
	{
		return 	$right1['TASK_ID'] == $right2['TASK_ID'] &&
				$right1['NEGATIVE'] == $right2['NEGATIVE'] &&
				$right1['ACCESS_CODE'] == $right2['ACCESS_CODE']
		;
	}

	private function isOpposite(array $right1, array $right2)
	{
		return 	$right1['TASK_ID'] == $right2['TASK_ID'] &&
				$right1['NEGATIVE'] != $right2['NEGATIVE'] &&
				$right1['ACCESS_CODE'] == $right2['ACCESS_CODE']
		;
	}

	private function appendOnePositive(array $right, $hadOppositeRight = false)
	{
		//May we have to add record to b_disk_right in final.
		$right['OBJECT_ID'] = $this->object->getId();
		$result = RightTable::add($right);
		if (!$result->isSuccess())
		{
			$this->errorCollection->addFromResult($result);

			return false;
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		if(!$rightsManager->containsOperationInTask($rightsManager::OP_READ, $right['TASK_ID']))
		{
			return true;
		}

		$conflictRightsInSubTree = $this->getConflictRightsInSubTree($right['ACCESS_CODE'], $right['TASK_ID']);
		$accessCode = $this->sqlHelper->forSql($right['ACCESS_CODE']);
		if(empty($conflictRightsInSubTree))
		{
			//we have to add simple right to all descendants and to current OBJECT_ID
			$this->connection->queryExecute("
				INSERT INTO b_disk_simple_right (OBJECT_ID, ACCESS_CODE)
				SELECT OBJECT_ID, '{$accessCode}' FROM b_disk_object_path
					WHERE PARENT_ID = {$this->object->getId()}
			");
		}
		else
		{
			$objectIds = array();
			foreach($conflictRightsInSubTree as $conflictRight)
			{
				$objectIds[] = $conflictRight['OBJECT_ID'];
			}
			unset($conflictRight);
			//we have to add simple right to all descendants and to current OBJECT_ID without nodes with conflict rights in path.
			//when I write conflict I mean right with same ACCESS_CODE and same TASK_ID
			$this->connection->queryExecute("
				INSERT INTO b_disk_simple_right (OBJECT_ID, ACCESS_CODE)
				SELECT OBJECT_ID, '{$accessCode}' FROM b_disk_object_path
					WHERE PARENT_ID = {$this->object->getId()} AND
					NOT EXISTS(
						SELECT 'x' FROM b_disk_object_path pp
							WHERE pp.OBJECT_ID = p.OBJECT_ID AND
							pp.PARENT_ID IN (" . implode(',', $objectIds)  . ") )
			");
		}

		$this->removeDuplicates($this->object->getId());

		return true;
	}

	private function removeDuplicates($parentObjectId)
	{
	}

	private function hasAlreadySimpleRight($accessCode)
	{
		$objectId = (int)$this->object->getId();
		$accessCode = $this->sqlHelper->forSql($accessCode);

		return (bool)$this->connection->query("
			SELECT 1 FROM b_disk_simple_right WHERE OBJECT_ID = {$objectId} AND ACCESS_CODE = '{$accessCode}'
		")->fetch();
	}

	private function getConflictRightsInSubTree($accessCode, $taskId)
	{
		$objectId = (int)$this->object->getId();
		$accessCode = $this->sqlHelper->forSql($accessCode);
		$accessCodeForSql = $this->sqlHelper->forSql($accessCode);
		$taskId = (int)$taskId;

		$rights = $this->connection->query("
			SELECT r.OBJECT_ID, r.NEGATIVE, r.DOMAIN
			FROM b_disk_right r
				INNER JOIN b_disk_object_path p ON p.OBJECT_ID = r.OBJECT_ID
			WHERE
				p.PARENT_ID = {$objectId} AND
				r.ACCESS_CODE = '{$accessCodeForSql}' AND
				r.TASK_ID = {$taskId}
		")->fetchAll();

		foreach($rights as $i => $right)
		{
			$rights[$i]['ACCESS_CODE'] = $accessCode;
			$rights[$i]['TASK_ID'] = $taskId;
		}
		unset($right);

		return $rights;
	}

	private function splitNegativeAndPositive(array $rights)
	{
		$negative = $positive = array();
		foreach($rights as $i => $right)
		{
			if(empty($right['NEGATIVE']))
			{
				$positive[] = $right;
			}
			else
			{
				$negative[] = $right;
			}
		}
		unset($right);

		return array($negative, $positive);
	}

	private function appendOneNegative(array $right, $hadOppositeRight = false)
	{
		$isValidNegaviteRight = $this->validateNegaviteRight($right);
		if(!$isValidNegaviteRight && !$hadOppositeRight)
		{
			$this->errorCollection->addOne(new Error('Invalid negative right'));
			return false;
		}

		//we don't have to add negative right. We must only delete old simple rights. condition($hadOppositeRight && !$isValidNegaviteRight)
		if($isValidNegaviteRight)
		{
			//May we have to add record to b_disk_right in final.
			$right['OBJECT_ID'] = $this->object->getId();
			$result = RightTable::add($right);
			if (!$result->isSuccess())
			{
				$this->errorCollection->addFromResult($result);

				return false;
			}
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		if(!$rightsManager->containsOperationInTask($rightsManager::OP_READ, $right['TASK_ID']))
		{
			return true;
		}

		if(!$this->hasAlreadySimpleRight($right['ACCESS_CODE']))
		{
			//below we already have negative rights, which deleted simple rights.
			return true;
		}
		//need to delete simple rights from descendants
		$conflictRightsInSubTree = $this->getConflictRightsInSubTree($right['ACCESS_CODE'], $right['TASK_ID']);
		$accessCode = $this->sqlHelper->forSql($right['ACCESS_CODE']);
		if(empty($conflictRightsInSubTree))
		{
			//we have to destroy simple right from all descendants and from current OBJECT_ID
			$this->connection->queryExecute("
				DELETE simple FROM b_disk_simple_right simple
					INNER JOIN b_disk_object_path p ON p.OBJECT_ID = simple.OBJECT_ID
				WHERE p.PARENT_ID = {$this->object->getId()} AND simple.ACCESS_CODE = '{$accessCode}'
			");
		}
		else
		{
			$objectIds = array();
			foreach($conflictRightsInSubTree as $conflictRight)
			{
				$objectIds[] = $conflictRight['OBJECT_ID'];
			}
			unset($conflictRight);
			//we have to destroy simple right from all descendants and from current OBJECT_ID without nodes with conflict rights in path.
			$this->connection->queryExecute("
				DELETE simple FROM b_disk_simple_right simple
					INNER JOIN b_disk_object_path p ON p.OBJECT_ID = simple.OBJECT_ID
				WHERE
					p.PARENT_ID = {$this->object->getId()} AND simple.ACCESS_CODE = '{$accessCode}' AND
					NOT EXISTS(
						SELECT 'x' FROM b_disk_object_path pp
							WHERE pp.OBJECT_ID = p.OBJECT_ID AND
							pp.PARENT_ID IN (" . implode(',', $objectIds)  . ") )
			");
		}

		return true;
	}

	private function deleteOnePositive(array $right)
	{
		foreach($this->getParentRights() as $parentRight)
		{
			if($this->isEqual($parentRight, $right))
			{
				//we don't have to clean negative rights in subtree and recalculate simple right, when in under-tree exists similar right.
				$result = RightTable::delete($right['ID']);
				if($result->isSuccess())
				{
					$this->errorCollection->addFromResult($result);
					return false;
				}
				return true;
			}
		}
		//we don't find right in under-tree.

		/**
		 * 1. Delete all negative rights in sub-tree before positive rights.
		 *  r.NEGATIVE = 1 AND ... r_p.NEGATIVE=0 AND r_p.DEPTH_LEVEL > r.DEPTH_LEVEL
		 * 2. Delete all simple rights in sub-tree before positive rights.
		 */

		return true;
	}

	private function deleteOneNegative(array $right)
	{
		$result = RightTable::delete($right['ID']);
		if($result->isSuccess())
		{
			$this->errorCollection->addFromResult($result);

			return false;
		}

		$firstNegative = false;
		foreach($this->getParentRights() as $parentRight)
		{
			if($this->isEqual($parentRight, $right))
			{
				$firstNegative = true;
				break;
			}
			if($this->isOpposite($parentRight, $right))
			{
				$firstNegative = false;
				break;
			}
		}

		if($firstNegative)
		{
			//parent negative is like current negative right ($right)
			return true;
		}


		/**
		 * Insert all simple rights in sub-tree before positive rights and negative rights.
		 */

		return true;
	}

	private function updateOnePositive(array $oldRight, array $newRight)
	{
		$rightsManager = Driver::getInstance()->getRightsManager();
		if(!$rightsManager->containsOperationInTask($rightsManager::OP_READ, $oldRight['TASK_ID']))
		{
			//simple appendOnePositive
			return true;
		}

		if(!$rightsManager->containsOperationInTask($rightsManager::OP_READ, $newRight['TASK_ID']))
		{
			//? delete old right?
			return true;
		}

		//shame. It is false.
	}


	private function getCurrentRightsOnObject()
	{
		if($this->currentRightsOnObject !== null)
		{
			return $this->currentRightsOnObject;
		}
		$this->currentRightsOnObject = Driver::getInstance()->getRightsManager()->getSpecificRights($this->object);

		return $this->currentRightsOnObject;
	}

	private function getParentRights()
	{
		if($this->parentRights !== null)
		{
			return $this->parentRights;
		}
		$this->parentRights = Driver::getInstance()->getRightsManager()->getParentsRights($this->object->getId());
		Collection::sortByColumn($this->parentRights, array('DEPTH_LEVEL' => SORT_DESC));

		return $this->parentRights;
	}

	private function validateNegaviteRight(array $potentialRight)
	{
		foreach($this->getParentRights() as $parentRight)
		{
			if($this->isOpposite($parentRight, $potentialRight))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}

/**
 * Class SimpleRightBuilder
 * @package Bitrix\Disk
 * @internal
 */
final class SimpleRightBuilder
{
	/** @var \Bitrix\Main\DB\Connection */
	private $connection;
	/** @var \Bitrix\Main\Db\SqlHelper */
	private $sqlHelper;
	private $objectId;

	/**
	 * SimpleRightBuilder constructor.
	 * @param int $objectId Object id. Root of storage.
	 */
	public function __construct($objectId)
	{
		$this->objectId = $objectId;
		$this->connection = Application::getConnection();
		$this->sqlHelper = Application::getConnection()->getSqlHelper();
	}

	/**
	 * Runs simple right builder.
	 * @return void
	 */
	public function run()
	{
		$this
			->cleanTree()
			->fillTree()
			->workWithNegativeNodes()
		;
	}

	/**
	 * Works with nodes which have negative rights.
	 *
	 * We have to get all negative nodes in subtree and order by DEPTH_LEVEL ASC.
	 * Then we go from each negative node up and calculate opportunity to read this object by ACCESS_CODE.
	 * If we have positive right on different TASK_ID in subtree, then we can't delete simple rights from subtree.
	 * If we don't have positive right on different TASK_ID in subtree, then we delete simple rights from subtree
	 * before we find another positive rights with same ACCESS_CODE.
	 *
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function workWithNegativeNodes()
	{
		$negativeNodes = $this->connection->query("
			SELECT
				r.ACCESS_CODE, r.TASK_ID,
				r.OBJECT_ID, p.DEPTH_LEVEL
			FROM b_disk_right r
				INNER JOIN b_disk_object_path p ON p.OBJECT_ID = r.OBJECT_ID
			WHERE
				p.PARENT_ID = {$this->objectId} AND r.NEGATIVE = 1
		")->fetchAll();

		$rightsManager = Driver::getInstance()->getRightsManager();
		Collection::sortByColumn($negativeNodes, array('DEPTH_LEVEL' => SORT_ASC));
		foreach($negativeNodes as $negativeNode)
		{
			$nodeObject = BaseObject::buildFromArray(array(
				'ID' => $negativeNode['OBJECT_ID'],
				'TYPE' => ObjectTable::TYPE_FOLDER,
			));

			$runClean = true;
			foreach($rightsManager->getAllListNormalizeRights($nodeObject) as $right)
			{
				if($right['ACCESS_CODE'] !== $negativeNode['ACCESS_CODE'])
				{
					continue;
				}
				//the right goes from parent
				if(
					!empty($right['NEGATIVE']) &&
					$right['OBJECT_ID'] != $negativeNode['OBJECT_ID'] &&
					$right['TASK_ID'] == $negativeNode['TASK_ID']
				)
				{
					$runClean = false;
					break;
				}
				if(!empty($right['NEGATIVE']))
				{
					continue;
				}

				if($rightsManager->containsOperationInTask($rightsManager::OP_READ, $right['TASK_ID']))
				{
					$runClean = false;
					break;
				}
			}
			unset($right);

			if(!$runClean)
			{
				//the node and all sub-elements inherit OP_READ from another positive right.
				continue;
			}

			$this->deleteSimpleRightFromSubTree($negativeNode['OBJECT_ID'], $negativeNode['ACCESS_CODE']);
		}
		unset($negativeNode);

		return $this;
	}

	/**
	 * Deletes simple rights from subtree of object by ACCESS_CODE.
	 * Skips nodes affected by positive right with same ACCESS_CODE.
	 * @param int $objectId Object id.
	 * @param string $accessCode Access code.
	 * @return void
	 */
	private function deleteSimpleRightFromSubTree($objectId, $accessCode)
	{
		//need to delete simple rights from descendants
		$objectIds = $this->getConflictRightsInSubTree($objectId, $accessCode);

		$objectId = (int)$objectId;
		$accessCode = $this->sqlHelper->forSql($accessCode);

		if(!$objectIds)
		{
			//we have to destroy simple right from all descendants and from current OBJECT_ID
			$this->connection->queryExecute("
				DELETE simple FROM b_disk_simple_right simple
					INNER JOIN b_disk_object_path p ON p.OBJECT_ID = simple.OBJECT_ID
				WHERE p.PARENT_ID = {$objectId} AND simple.ACCESS_CODE = '{$accessCode}'
			");
		}
		else
		{
			//we have to destroy simple right from all descendants and from current OBJECT_ID without nodes with conflict rights in path.
			$this->connection->queryExecute("
				DELETE simple FROM b_disk_simple_right simple
					INNER JOIN b_disk_object_path p ON p.OBJECT_ID = simple.OBJECT_ID
				WHERE
					p.PARENT_ID = {$objectId} AND simple.ACCESS_CODE = '{$accessCode}' AND
					NOT EXISTS(
						SELECT 'x' FROM b_disk_object_path pp
							WHERE pp.OBJECT_ID = p.OBJECT_ID AND
							pp.PARENT_ID IN (" . implode(',', $objectIds)  . ") )
			");
		}
	}

	/**
	 * Returns list of id of objects which act by positive rights on descendants objects by the same ACCESS_CODE.
	 * @param int $objectId Object id.
	 * @param string $accessCode Access code.
	 * @return array
	 */
	private function getConflictRightsInSubTree($objectId, $accessCode)
	{
		$objectId = (int)$objectId;
		$accessCode = $this->sqlHelper->forSql($accessCode);

		$rights = $this->connection->query("
			SELECT r.OBJECT_ID
			FROM b_disk_right r
				INNER JOIN b_disk_object_path p ON p.OBJECT_ID = r.OBJECT_ID
			WHERE
				p.PARENT_ID = {$objectId} AND
				r.NEGATIVE = 0 AND
				r.ACCESS_CODE = '{$accessCode}'
		")->fetchAll();

		$ids = array();
		foreach($rights as $i => $right)
		{
			$ids[] = $right['OBJECT_ID'];
		}
		unset($right);

		return $ids;
	}

	/**
	 * Deletes all simple rights from subtree.
	 * @return $this
	 */
	private function cleanTree()
	{
		$this->connection->queryExecute("
			DELETE r FROM b_disk_simple_right r
				INNER JOIN b_disk_object_path p ON p.OBJECT_ID = r.OBJECT_ID
			WHERE p.PARENT_ID =  {$this->objectId}
		");

		return $this;
	}

	/**
	 * Fills subtree by simple rights.
	 * Any positive rights, which contains operation OP_READ, will fill subtree. It's unnecessary has node negative rights
	 * and not.
	 * @return $this
	 */
	private function fillTree()
	{
		if($this->hasAllTasksWithOperationRead())
		{
			$this->connection->queryExecute("
				INSERT INTO b_disk_simple_right (OBJECT_ID, ACCESS_CODE)
				SELECT DISTINCT pathchild.OBJECT_ID, r.ACCESS_CODE FROM b_disk_object_path path
				    INNER JOIN b_disk_right r ON r.OBJECT_ID = path.OBJECT_ID
				    INNER JOIN b_disk_object_path pathchild ON pathchild.PARENT_ID = r.OBJECT_ID
				WHERE path.PARENT_ID = {$this->objectId} AND r.NEGATIVE = 0
			");
		}
		else
		{
			$ids = array();
			foreach($this->getTasksWithOperationRead() as $task)
			{
				$ids[] = (int)$task['ID'];
			}
			unset($task);

			if(!$ids)
			{
				return $this;
			}

			$this->connection->queryExecute("
				INSERT INTO b_disk_simple_right (OBJECT_ID, ACCESS_CODE)
				SELECT DISTINCT pathchild.OBJECT_ID, r.ACCESS_CODE FROM b_disk_object_path path
				    INNER JOIN b_disk_right r ON r.OBJECT_ID = path.OBJECT_ID
				    INNER JOIN b_disk_object_path pathchild ON pathchild.PARENT_ID = r.OBJECT_ID
				WHERE path.PARENT_ID = {$this->objectId} AND r.NEGATIVE = 0 AND r.TASK_ID IN (" . implode(', ', $ids) .")
			");
		}

		return $this;
	}

	/**
	 * Returns tasks which contains operation OP_READ.
	 * @return array
	 */
	private function getTasksWithOperationRead()
	{
		$rightsManager = Driver::getInstance()->getRightsManager();

		$tasks = $rightsManager->getTasks();
		$readableTasks = array();
		foreach($tasks as $task)
		{
			if($rightsManager->containsOperationInTask($rightsManager::OP_READ, $task['ID']))
			{
				$readableTasks[$task['ID']] = $task;
			}
		}
		unset($task);

		return $readableTasks;
	}

	/**
	 * Tells true if all tasks in module disk have operation OP_READ.
	 * @return bool
	 */
	private function hasAllTasksWithOperationRead()
	{
		$rightsManager = Driver::getInstance()->getRightsManager();
		$tasks = $rightsManager->getTasks();

		return count($tasks) === count($this->getTasksWithOperationRead());
	}
}

final class DataSetOfRights extends \Bitrix\Disk\Type\DataSet
{
	public function filterByTaskId($taskId)
	{
		return $this->filterByField('TASK_ID', $taskId);
	}

	public function filterByAccessCode($accessCode)
	{
		return $this->filterByField('ACCESS_CODE', $accessCode);
	}

	public function filterNegative()
	{
		return $this->filterByField('NEGATIVE', true);
	}

	public function filterPositive()
	{
		return $this->filterByCallback(function($item) {
			return empty($item['NEGATIVE']);
		});
	}
}