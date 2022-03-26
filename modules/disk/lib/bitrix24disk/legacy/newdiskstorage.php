<?php
namespace Bitrix\Disk\Bitrix24Disk\Legacy;


use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Bitrix24Disk\Legacy\Exceptions\UnexpectedNextIdException;
use Bitrix\Disk\Bitrix24Disk\PageState;
use Bitrix\Disk\Bitrix24Disk\TreeNode;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Collection\CustomHeap;
use Bitrix\Disk\Internals\Collection\FixedArray;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\ObjectLock;
use Bitrix\Disk\Sharing;
use Bitrix\Disk\SpecificFolder;
use Bitrix\Disk\Ui;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\DateTime;
use \Bitrix\Disk\Internals;

class NewDiskStorage extends DiskStorage
{
	const MAX_COUNT_LINKS_FOR_CYCLE = 5;
	const SNAPSHOT_PAGE_SIZE        = 200;

	const LOCK_PREFIX_IN_SELECT = 'LOCK_';

	/** @var TreeNode[] */
	private $treeData = array();
	/** @var bool */
	private $isLoadedTree = false;

	public function getSnapshot($version = 0, PageState $pageState = null, PageState &$nextPageState = null)
	{
		if($pageState === null)
		{
			$pageState = new PageState(PageState::STEP_PERSONAL);
		}
		$nextPageState = clone $pageState;

		$internalVersion = $this->convertFromExternalVersion($version);
		$items = new FixedArray(self::SNAPSHOT_PAGE_SIZE);

		$this->workFromState($pageState, $items, $nextPageState, $internalVersion);

		return $this->setEmptyArray($items)->getSplFixedArray();
	}

	/**
	 * Sets empty array in positions where is only field 'version'.
	 * It is necessary for right pagination.
	 *
	 * @param FixedArray $items
	 * @return FixedArray
	 */
	protected function setEmptyArray(FixedArray $items)
	{
		foreach ($items as $i => $item)
		{
			if ($item && !$item['id'])
			{
				$items[$i] = array();
			}
		}

		return $items;
	}

	protected function workFromState(PageState $pageState, FixedArray $items, PageState &$newPageState, $internalVersion)
	{
		foreach($this->getStepSlice($pageState->getStep()) as $stepName => $stepData)
		{
			$this->runStep($stepName, array(
				$items, $newPageState, $internalVersion, $this::SNAPSHOT_PAGE_SIZE - $items->getCountOfPushedElements()
			));

			if($items->getCountOfPushedElements() === self::SNAPSHOT_PAGE_SIZE && ($newPageState->hasCursor() ||$newPageState->hasNextId()))
			{
				//same step, next page
				return;
			}

			$nextStep = $this->getNextStep($stepName);
			if($nextStep)
			{
				//next step, first page
				$newPageState->setStep(key($nextStep));
			}

			if($items->getCountOfPushedElements() === self::SNAPSHOT_PAGE_SIZE && $nextStep)
			{
				return;
			}
		}
		//last page
		$newPageState = null;

		return;
	}

	private function runStep($stepName, array $args)
	{
		$step = $this->getStep($stepName);
		if(!$step)
		{
			throw new ArgumentException("Invalid step name {$stepName}");
		}

		list($object, $method) = $step;
		$reflectionMethod = new \ReflectionMethod($object, $method);
		$reflectionMethod->setAccessible(true);

		return $reflectionMethod->invokeArgs($this, $args);
	}

	private function getSteps()
	{
		/** @see NewDiskStorage::snapshotFromPersonalStorage() */
		/** @see NewDiskStorage::snapshotFromLinks() */
		/** @see NewDiskStorage::snapshotDeletedElements() */
		return array(
			PageState::STEP_PERSONAL => array($this, 'snapshotFromPersonalStorage'),
			PageState::STEP_SYMLINKS => array($this, 'snapshotFromLinks'),
			PageState::STEP_DELETED_OBJECTS => array($this, 'snapshotDeletedElements'),
		);
	}

	private function getStep($stepName)
	{
		$steps = $this->getSteps();

		return isset($steps[$stepName])? $steps[$stepName] : null;
	}

	private function getNextStep($stepName)
	{
		return array_slice($this->getStepSlice($stepName), 1, 1);
	}

	private function getStepSlice($startStepName)
	{
		$startCollect = false;
		$slice = array();
		foreach($this->getSteps() as $name => $data)
		{
			if($name === $startStepName || $startCollect)
			{
				$startCollect = true;
			}
			if(!$startCollect)
			{
				continue;
			}
			$slice[$name] = $data;
		}

		return $slice;
	}

	private function getSelectableColumnsForObject()
	{
		$fields = [];
		$entity = ObjectTable::getEntity();
		foreach ($entity->getScalarFields() as $field)
		{
			if($field->getColumnName() !== 'SEARCH_INDEX')
			{
				$fields[] = $field->getColumnName();
			}
		}

		return $fields;
	}

	private function snapshotFromPersonalStorage(FixedArray $items, PageState $pageState, $internalVersion, $pageSize = self::SNAPSHOT_PAGE_SIZE)
	{
		if($pageState->getStep() !== $pageState::STEP_PERSONAL)
		{
			return;
		}

		$hasNextPage = false;
		$nextCursor = $nextId = null;
		$expectedFirstId = $pageState->getNextId();
		$cursor = $pageState->getCursor()?: $internalVersion;

		$query = new Internals\Entity\Query(ObjectTable::getEntity());
		$query->setSelect($this->getSelectableColumnsForObject());

		if(!$this->checkOpportunityToSkipRights())
		{
			$securityContext = $this->storage->getSecurityContext($this->userId);
			$query
				->addFilter('=RIGHTS_CHECK', true)
				->registerRuntimeField(
					'RIGHTS_CHECK',
					new ExpressionField(
						'RIGHTS_CHECK',
						'CASE WHEN ' . $securityContext->getSqlExpressionForList('%1$s', '%2$s') . ' THEN 1 ELSE 0 END',
						array('ID', 'CREATED_BY')
					)
				)
			;
		}

		if($cursor > 0)
		{
			$query->addFilter('>=SYNC_UPDATE_TIME', DateTime::createFromTimestamp($cursor));
		}

		if ($this->isEnabledObjectLock)
		{
			$query->addSelect('LOCK.*', self::LOCK_PREFIX_IN_SELECT);
		}

		$query
			->addFilter('STORAGE_ID', $this->storage->getId())
			->addFilter('DELETED_TYPE', ObjectTable::DELETED_TYPE_NONE)
			->addOrder('SYNC_UPDATE_TIME')
			->addOrder('ID')
			->setLimit($pageSize + 1)
		;

		$offset = $pageState->getOffset();
		if ($cursor && $offset)
		{
			//we want to skip values which were on previous page.
			$query->setOffset($offset);
		}

		$count = 0;
		foreach($query->exec() as $item)
		{
			if($count === 0)
			{
				if($expectedFirstId !== null && $item['ID'] != $expectedFirstId)
				{
					throw new UnexpectedNextIdException("{$expectedFirstId} vs {$item['ID']}");
				}

				$this->loadTree();
				$this->loadSharedData();
			}

			$count++;
			if($count > $pageSize)
			{
				$nextCursor = $item['SYNC_UPDATE_TIME']->getTimestamp();
				$nextId = $item['ID'];
				$hasNextPage = true;

				break;
			}

			$formattedItem = $this->formatObjectRowToResponse($item);
			if(!$formattedItem || $formattedItem['path'] === '/')
			{
				$items->push(array(
					'version' => (string)$this->generateTimestamp($item['SYNC_UPDATE_TIME']->getTimestamp()),
				));
				//but we can have null on the page in snapshots. It's necessary for correct page navigation.
				continue;
			}

			$items->push($formattedItem);
		}

		$pageState->reset();
		if($hasNextPage)
		{
			$toSkipOnNextStep = 0;
			if($cursor == $nextCursor)
			{
				$toSkipOnNextStep += $offset;
			}

			$toSkipOnNextStep += $this->countIdsWithSameSyncDate($items, $this->convertToExternalVersion($nextCursor));
			if($toSkipOnNextStep)
			{
				$pageState->setOffset($toSkipOnNextStep);
			}

			$pageState
				->setStep($pageState::STEP_PERSONAL)
				->setCursor($nextCursor)
				->setNextId($nextId)
			;
		}
	}

	private function countIdsWithSameSyncDate(FixedArray $items, $syncDateVersion)
	{
		$count = 0;
		foreach ($items->reverse() as $item)
		{
			if (
				!empty($item['version']) &&
				$item['version'] == $syncDateVersion
			)
			{
				$count++;
			}
		}

		return $count;
	}

	private function formatObjectRowToResponse(array $row)
	{
		if(empty($row['TYPE']) || empty($row['ID']) || $row['NAME'] == '')
		{
			return array();
		}

		if (empty($row['UPDATE_TIME']))
		{
			$row['UPDATE_TIME'] = $row['CREATE_TIME'];
		}

		if($row['TYPE'] == ObjectTable::TYPE_FILE)
		{
			return $this->formatFileRowToResponse($row);
		}

		return $this->formatFolderRowToResponse($row);
	}

	private function formatFolderRowToResponse(array $row)
	{
		$path = $this->getPath($row['ID'], true);
		if(!$path)
		{
			return array();
		}

		$isLink = !empty($row['REAL_OBJECT_ID']) && $row['REAL_OBJECT_ID'] != $row['ID'];
		$name = Ui\Text::cleanTrashCanSuffix($row['NAME']);
		$result = array(
			'id' => $this->generateId(array('FILE' => false, 'ID' => $row['ID'])),
			'isDirectory' => true,
			'isShared' => (bool)$this->isSharedObject($row['ID']),
			'isSymlinkDirectory' => $isLink,
			'isDeleted' => !empty($row['DELETED_TYPE']),
			'storageId' => $this->getStringStorageId(),
			'path' => '/' . trim($path, '/'),
			'name' => (string)$name,
			'version' => (string)$this->generateTimestamp($row['SYNC_UPDATE_TIME']->getTimestamp()),
			'originalTimestamp' => (string)$this->generateTimestamp($row['UPDATE_TIME']->getTimestamp()),
			'extra' => array(
				'id' => (string)$row['ID'],
				'iblockId' => (string)$row['STORAGE_ID'],
				'sectionId' => (string)$row['PARENT_ID'],
				'linkSectionId' => (string)($isLink? $row['REAL_OBJECT_ID'] : ''),
				'rootSectionId' => (string)$this->storage->getRootObjectId(),
				'name' => (string)$name,
			),
			'permission' => 'W',
			'createdBy' => (string)$row['CREATED_BY'],
			'modifiedBy' => (string)$row['UPDATED_BY'],
		);

		if($this->storage->getRootObjectId() != $row['PARENT_ID'])
		{
			$result['parentId'] = $this->generateId(array('FILE' => false, 'ID' => $row['PARENT_ID']));
		}

		return $result;
	}

	private function formatFileRowToResponse(array $row)
	{
		if(empty($row['PARENT_ID']))
		{
			return array();
		}

		$path = $this->getPath($row['PARENT_ID']);
		if(!$path)
		{
			return array();
		}

		$isLink = !empty($row['REAL_OBJECT_ID']) && $row['REAL_OBJECT_ID'] != $row['ID'];
		$name = Ui\Text::cleanTrashCanSuffix($row['NAME']);
		$result = array(
			'id' => $this->generateId(array('FILE' => true, 'ID' => $row['ID'])),
			'isDirectory' => false,
			'isShared' => (bool)$this->isSharedObject($row['ID']),
			'isSymlinkFile' => $isLink,
			'isDeleted' => !empty($row['DELETED_TYPE']),
			'storageId' => $this->getStringStorageId(),
			'path' => $path === '/'? '/' . $name : '/' . trim($path, '/') . '/' . $name,
			'name' => (string)$name,
			'revision' => $row['FILE_ID'],
			'etag' => $row['ETAG'],
			'version' => (string)$this->generateTimestamp($row['SYNC_UPDATE_TIME']->getTimestamp()),
			'originalTimestamp' => (string)$this->generateTimestamp($row['UPDATE_TIME']->getTimestamp()),
			'extra' => array(
				'id' => (string)$row['ID'],
				'iblockId' => (string)$row['STORAGE_ID'],
				'sectionId' => (string)$row['PARENT_ID'],
				'rootSectionId' => (string)$this->storage->getRootObjectId(),
				'name' => (string)$name,
			),
			'size' => (string)$row['SIZE'],
			'permission' => 'W',
			'createdBy' => (string)$row['CREATED_BY'],
			'modifiedBy' => (string)$row['UPDATED_BY'],
		);
		if($this->storage->getRootObjectId() != $row['PARENT_ID'])
		{
			$result['parentId'] = $this->generateId(array('FILE' => false, 'ID' => $row['PARENT_ID']));
		}

		if($this->isEnabledObjectLock)
		{
			$lock = $this->getLockFromRow($row);
			if($lock)
			{
				$result['lock'] = array(
					'createdBy' => (string)$lock->getCreatedBy(),
					'createTimestamp' => (string)$this->generateTimestamp($lock->getCreateTime()->getTimestamp()),
					'canUnlock' => $lock->canUnlock($this->getUser()->getId()),
				);
			}
		}


		return $result;
	}

	private function getLockFromRow(array $row, $prefix = self::LOCK_PREFIX_IN_SELECT)
	{
		$lockData = array();
		$length = mb_strlen(self::LOCK_PREFIX_IN_SELECT);
		foreach ($row as $key => $value)
		{
			if (mb_strpos($key, $prefix) === 0)
			{
				$lockData[mb_substr($key, $length)] = $value;
			}
		}

		if (!array_filter($lockData))
		{
			return null;
		}

		return ObjectLock::buildFromArray($lockData);
	}

	private function snapshotFromLinks(FixedArray $items, PageState $pageState, $internalVersion, $pageSize = self::SNAPSHOT_PAGE_SIZE)
	{
		if($pageState->getStep() !== $pageState::STEP_SYMLINKS)
		{
			return;
		}

		$expectedFirstId = $pageState->getNextId();
		$countElementPushedFromLinks = 0;
		$countBeforeStart = $items->getCountOfPushedElements();

		foreach($this->getSymlinkFoldersSortedById() as $link)
		{
			if(empty($expectedFirstId) || $link->id >= $expectedFirstId)
			{
				if($items->getCountOfPushedElements() === self::SNAPSHOT_PAGE_SIZE)
				{
					$pageState->setStep($pageState::STEP_SYMLINKS);
					if(!$pageState->hasNextId())
					{
						//if the page state does not have next id, that means we have to work with next link.
						//if the page state has next id, that means we have to continue process to get snapshot (next page).
						$pageState->setNextId($link->id);
					}
					return;
				}

				if(!$link->isReplica())
				{
					$this->snapshotFromLink($link, $items, $pageState, $internalVersion, $pageSize - $countElementPushedFromLinks);
				}

				$countElementPushedFromLinks = $items->getCountOfPushedElements() - $countBeforeStart;
			}
			elseif($link->id < $expectedFirstId)
			{
				continue;
			}
		}
		unset($link);

		if(!$pageState->hasNextId())
		{
			$pageState->reset();
		}
	}

	private function snapshotFromLink(TreeNode $link, FixedArray $items, PageState $pageState, $internalVersion, $pageSize = self::SNAPSHOT_PAGE_SIZE)
	{
		$hasNextPage = false;
		$nextCursor = $nextId = null;
		$expectedFirstId = empty($dataByStep['lid'])? null : $dataByStep['lid'];
		$securityContext = $this->storage->getSecurityContext($this->userId);

		$query = new Internals\Entity\Query(ObjectTable::getEntity());
		$query
			->setSelect($this->getSelectableColumnsForObject())
			->addFilter('PATH_CHILD.PARENT_ID', $link->realObjectId)
			->addFilter('DELETED_TYPE', ObjectTable::DELETED_TYPE_NONE)
			->addFilter('=RIGHTS_CHECK', true)
			->registerRuntimeField(
				'RIGHTS_CHECK',
				new ExpressionField(
					'RIGHTS_CHECK',
					'CASE WHEN ' . $securityContext->getSqlExpressionForList('%1$s', '%2$s') . ' THEN 1 ELSE 0 END',
					array('ID', 'CREATED_BY')
				)
			)
			->addOrder('SYNC_UPDATE_TIME')
			->addOrder('ID')
//			->addOrder('PATH_CHILD.OBJECT_ID')
			->setLimit($pageSize + 1)
		;

		if ($this->isEnabledObjectLock)
		{
			$query->addSelect('LOCK.*', self::LOCK_PREFIX_IN_SELECT);
		}

		$cursor = $pageState->getCursor();
		if (
			!$cursor &&
			$internalVersion > 0 &&
			$this->compareVersion(
				$this->convertToExternalVersion($link->createDate->getTimestamp()),
				$this->convertToExternalVersion($internalVersion)
			) < 0
		)
		{
			$cursor = $internalVersion;
		}

		if($cursor > 0)
		{
			$query->addFilter('>=SYNC_UPDATE_TIME', DateTime::createFromTimestamp($cursor));
		}

		$offset = $pageState->getOffset();
		if ($cursor && $offset)
		{
			//we want to skip values which were on previous page.
			$query->setOffset($offset);
		}

		$count = 0;
		foreach($query->exec() as $item)
		{
			if($count === 0)
			{
				if($expectedFirstId !== null && $item['ID'] != $expectedFirstId)
				{
					throw new UnexpectedNextIdException("{$expectedFirstId} vs {$item['ID']}");
				}

				$this->loadTree();
				$this->loadSharedData();
			}

			$count++;
			if($count > $pageSize)
			{
				$nextCursor = $item['SYNC_UPDATE_TIME']->getTimestamp();
				$nextId = $item['ID'];
				$hasNextPage = true;

				break;
			}

			$formattedItem = $this->formatObjectRowToResponse($item);
			if(
				!$formattedItem ||
				$formattedItem['path'] === '/' ||
				//this is root of symlink. We don't have to show it. We show only symlink.
				($link->realObjectId == $item['ID'] && $link->realObjectId == $item['REAL_OBJECT_ID'])
			)
			{
				$items->push(array(
					'version' => (string)$this->generateTimestamp($item['SYNC_UPDATE_TIME']->getTimestamp()),
				));
				//but we can have null on the page in snapshots. It's necessary for correct page navigation.
				continue;
			}

			$items->push($formattedItem);
		}

		$pageState->reset();
		if($hasNextPage)
		{
			$toSkipOnNextStep = 0;
			if($cursor == $nextCursor)
			{
				$toSkipOnNextStep += $offset;
			}

			$toSkipOnNextStep += $this->countIdsWithSameSyncDate($items, $this->convertToExternalVersion($nextCursor));
			if($toSkipOnNextStep)
			{
				$pageState->setOffset($toSkipOnNextStep);
			}

			$pageState
				->setStep($pageState::STEP_SYMLINKS)
				->setNextId($link->id)
				->setCursor($nextCursor)
				->setDataByStep(array(
					'lid' => $nextId
				))
			;
		}
	}

	/**
	 * @return TreeNode[]
	 */
	private function getSymlinkFoldersSortedById()
	{
		$links = new CustomHeap(function(TreeNode $treeNode1, TreeNode $treeNode2){
			if($treeNode1->id == $treeNode2->id)
			{
				return 0;
			}

			return $treeNode1->id < $treeNode2->id? 1 : -1;
		});

		$this->loadTree();
		foreach($this->treeData as $id => $treeNode)
		{
			if (!($treeNode instanceof TreeNode))
			{
				continue;
			}

			if(!$treeNode->isLink() || is_string($id) && mb_substr($id, 0, 1) === TreeNode::TREE_SYMLINK_PREFIX)
			{
				continue;
			}

			$links->insert($treeNode);
		}

		return $links;
	}

	private function snapshotDeletedElements(FixedArray $items, PageState $pageState, $internalVersion, $pageSize = self::SNAPSHOT_PAGE_SIZE)
	{
		if($pageState->getStep() !== $pageState::STEP_DELETED_OBJECTS)
		{
			return;
		}

		if ($internalVersion == 0)
		{
			//we don't show deleted files when we send starter snapshot.
			return;
		}

		$hasNextPage = false;
		$nextCursor = $nextId = null;
		$cursor = $pageState->getCursor()?: $internalVersion;
		$expectedFirstId = $pageState->getNextId();

		$deletedLogManager = Driver::getInstance()->getDeletedLogManager();
		$deletedLogTable = $deletedLogManager->getLogTable();

		$query = new Internals\Entity\Query($deletedLogTable::getEntity());
		$query
			->addSelect('*')
			->addFilter('STORAGE_ID', $this->storage->getId())
			->addOrder('CREATE_TIME')
			->addOrder('ID')
			->setLimit($pageSize + 1)
		;

		if($cursor > 0)
		{
			$query->addFilter('>=CREATE_TIME', DateTime::createFromTimestamp($cursor));
		}

		$offset = $pageState->getOffset();
		if ($cursor && $offset)
		{
			//we want to skip values which were on previous page.
			$query->setOffset($offset);
		}

		$count = 0;
		foreach($query->exec() as $item)
		{
			if($count === 0)
			{
				if($expectedFirstId !== null && $item['ID'] != $expectedFirstId)
				{
					throw new UnexpectedNextIdException("{$expectedFirstId} vs {$item['ID']}");
				}
			}

			$count++;
			if($count > $pageSize)
			{
				$nextCursor = $item['CREATE_TIME']->getTimestamp();
				$nextId = $item['ID'];
				$hasNextPage = true;

				break;
			}

			$items->push(
				$this->formatDeletedObjectRowToResponse($item)
			);
		}

		$pageState->reset();
		if($hasNextPage)
		{
			$toSkipOnNextStep = 0;
			if($cursor == $nextCursor)
			{
				$toSkipOnNextStep += $offset;
			}

			$toSkipOnNextStep += $this->countIdsWithSameSyncDate($items, $this->convertToExternalVersion($nextCursor));
			if($toSkipOnNextStep)
			{
				$pageState->setOffset($toSkipOnNextStep);
			}

			$pageState
				->setStep($pageState::STEP_DELETED_OBJECTS)
				->setCursor($nextCursor)
				->setNextId($nextId)
			;
		}
	}

	private function formatDeletedObjectRowToResponse(array $row)
	{
		return array(
			'id' => $this->generateId(array('FILE' => $row['TYPE'] == ObjectTable::TYPE_FILE, 'ID' => $row['OBJECT_ID'])),
			'isDirectory' => $row['TYPE'] == ObjectTable::TYPE_FOLDER,
			'deletedBy' => (string) (isset($row['USER_ID'])? $row['USER_ID'] : 0),
			'isDeleted' => true,
			'storageId' => $this->getStringStorageId(),
			'version' => $this->convertToExternalVersion($row['CREATE_TIME']->getTimestamp()),
		);
	}

	private function buildSelfTree()
	{
		$deletedTypeNone = ObjectTable::DELETED_TYPE_NONE;
		$typeFolder = ObjectTable::TYPE_FOLDER;
		$storageId = $this->storage->getId();

		/** @var TreeNode[] $firstLevelLinks */
		$firstLevelLinks = array();
		$this->treeData = array();

		$query = new Internals\Entity\Query(ObjectTable::getEntity());
		$query
			->setSelect(array(
				'ID',
				'NAME',
				'REAL_OBJECT_ID',
				'PARENT_ID',
				'CODE',
				'CREATE_TIME',
			))
			->addFilter('STORAGE_ID', $storageId)
			->addFilter('DELETED_TYPE', $deletedTypeNone)
			->addFilter('TYPE', $typeFolder)
		;

		foreach($query->exec() as $folderRow)
		{
			if($folderRow['CODE'] === SpecificFolder::CODE_FOR_UPLOADED_FILES)
			{
				continue;
			}

			$node = $this->fillTreeData($folderRow);
			if ($node->isLink() && !$this->isRealObjectExists($node))
			{
				$firstLevelLinks[] = $node;
			}
		}

		if(isset($this->treeData[$this->storage->getRootObjectId()]))
		{
			$this->treeData[$this->storage->getRootObjectId()]->setAsRoot();
		}

		return $firstLevelLinks;
	}

	/**
	 * @param TreeNode[] $firstLevelLinks
	 * @return array
	 */
	private function buildTreeFromFirstLevelLinks(array $firstLevelLinks)
	{
		$deepLinks = array();
		if(!$firstLevelLinks)
		{
			return array();
		}
		if(count($firstLevelLinks) < self::MAX_COUNT_LINKS_FOR_CYCLE)
		{
			/** @var TreeNode[] $firstLevelLinks */
			foreach($firstLevelLinks as $link)
			{
				$deepLinks = array_merge($deepLinks, $this->buildTreeFromLink($link));
			}
		}
		else
		{
			$deletedTypeNone = ObjectTable::DELETED_TYPE_NONE;
			$typeFolder = ObjectTable::TYPE_FOLDER;
			$storageId = $this->storage->getId();
			$securityContext = $this->storage->getSecurityContext($this->userId);
			$rightExists = $securityContext->getSqlExpressionForList('object.ID', 'object.CREATED_BY');

			$sqlQuery = "
				SELECT object_pl1.ID, object_pl1.NAME, object_pl1.REAL_OBJECT_ID, object_pl1.PARENT_ID, object_pl1.CREATE_TIME
				FROM b_disk_object object
				INNER JOIN b_disk_object_path p ON p.PARENT_ID = object.REAL_OBJECT_ID
				INNER JOIN b_disk_object object_pl1 ON object_pl1.ID = p.OBJECT_ID
				WHERE
					object.STORAGE_ID = {$storageId} AND object.DELETED_TYPE = {$deletedTypeNone} AND object.TYPE = {$typeFolder} AND
					object.REAL_OBJECT_ID <> object.ID AND
					object_pl1.DELETED_TYPE = {$deletedTypeNone} AND object_pl1.TYPE = {$typeFolder} AND
					({$rightExists})
			";

			$iterator = $this->connection->query($sqlQuery);
			foreach($iterator as $folderRow)
			{
				$node = $this->fillTreeData($folderRow);
				if ($node->isLink() && !$this->isRealObjectExists($node))
				{
					$deepLinks[] = $node;
				}
			}
		}

		return $deepLinks;
	}

	protected function flushTreeCache()
	{
		$this->isLoadedTree = false;
		TreeNode::$__pathNodes = [];
	}

	public function loadTree()
	{
		if($this->isLoadedTree)
		{
			return;
		}

		$cache = Data\Cache::createInstance();
		if($cache->initCache(15768000, 'new_storage_tr_' . $this->storage->getId(), 'disk'))
		{
			list($this->treeData,) = $cache->getVars();
		}
		else
		{
			$this->buildTree();

			$cache->startDataCache();
			$cache->endDataCache(array($this->treeData,));
		}

		$this->isLoadedTree = true;
	}

	private function buildTree()
	{
		TreeNode::$__pathNodes = [];
		$this->treeData = [];
		$firstLevelLinks = $this->buildSelfTree();
		$deepLinks = $this->buildTreeFromFirstLevelLinks($firstLevelLinks);
		$theDeepestLinks = $this->buildTreeRecursiveFromLinks($deepLinks);

		foreach($firstLevelLinks as $node)
		{
			if(isset($this->treeData[$node->realObjectId]))
			{
				$this->treeData[$node->realObjectId]->setLink($node);
			}
		}
		foreach($deepLinks as $node)
		{
			if(isset($this->treeData[$node->realObjectId]))
			{
				$this->treeData[$node->realObjectId]->setLink($node);
			}
		}
		foreach($theDeepestLinks as $node)
		{
			if(isset($this->treeData[$node->realObjectId]))
			{
				$this->treeData[$node->realObjectId]->setLink($node);
			}
		}
	}

	private function buildTreeRecursiveFromLinks(array $links)
	{
		if(!$links)
		{
			return array();
		}

		$subLinks = array();
		/** @var TreeNode[] $links */
		foreach($links as $link)
		{
			$subLinks = array_merge($subLinks, $this->buildTreeFromLink($link));
		}
		unset($link);

		return array_merge($subLinks, $this->buildTreeRecursiveFromLinks($subLinks));
	}

	private function getStorageIdByRealObjectId(int $realObjectId): int
	{
		$storageId = $this->connection->queryScalar("
			SELECT STORAGE_ID FROM b_disk_object WHERE ID = {$realObjectId}
		");

		return (int)$storageId;
	}

	private function buildTreeFromLink(TreeNode $link)
	{
		if ($this->isRealObjectExists($link))
		{
			$link->markAsReplica();

			return array();
		}

		$deletedTypeNone = ObjectTable::DELETED_TYPE_NONE;
		$typeFolder = ObjectTable::TYPE_FOLDER;
		$securityContext = $this->storage->getSecurityContext($this->userId);
		$rightExists = $securityContext->getSqlExpressionForList('object.ID', 'object.CREATED_BY');
		$storageId = $this->getStorageIdByRealObjectId($link->realObjectId);

		$sqlQuery = "
			SELECT object.ID, object.NAME, object.REAL_OBJECT_ID, object.PARENT_ID, object.CREATE_TIME
			FROM b_disk_object object
			INNER JOIN b_disk_object_path path ON path.OBJECT_ID = object.ID
			WHERE 
				path.PARENT_ID = {$link->realObjectId} AND 
				object.STORAGE_ID = {$storageId} AND 
				object.DELETED_TYPE = {$deletedTypeNone} AND 
				object.TYPE = {$typeFolder} AND ({$rightExists})
		";
		$iterator = $this->connection->query($sqlQuery);
		$subLinks = array();
		foreach($iterator as $folderRow)
		{
			//prevent possible cycle
			if($this->isTreeNodeExists($folderRow))
			{
				continue;
			}

			$node = $this->fillTreeData($folderRow);
			if ($node->isLink() && !$this->isRealObjectExists($node))
			{
				$subLinks[] = $node;
			}
		}

		return $subLinks;
	}

	/**
	 * Checks if real node is already in tree. It means that we have source and link in the storage.
	 * So, now we build data only under source.
	 * @param TreeNode $node
	 *
	 * @return bool
	 */
	private function isRealObjectExists(TreeNode $node)
	{
		return isset($this->treeData[$node->realObjectId]);
	}

	private function isTreeNodeExists(array $folderRow)
	{
		if (!isset($this->treeData[$folderRow['ID']]))
		{
			return false;
		}

		$treeNode = $this->treeData[$folderRow['ID']];
		if(
			$treeNode->id == $folderRow['ID'] &&
			$treeNode->name == $folderRow['NAME'] &&
			$treeNode->parentId == $folderRow['PARENT_ID'] &&
			$treeNode->realObjectId == $folderRow['REAL_OBJECT_ID']
		)
		{
			return true;
		}

		return false;
	}

	private function fillTreeData(array $folderRow)
	{
		$isReplica = false;
		if (isset($this->treeData[$folderRow['REAL_OBJECT_ID']]))
		{
			$isReplica = true;
		}

		$this->treeData[$folderRow['ID']] = new TreeNode(
			$folderRow['ID'],
			$folderRow['NAME'],
			$folderRow['PARENT_ID'],
			$folderRow['REAL_OBJECT_ID']
		);

		$this->treeData[$folderRow['ID']]->setTree($this->treeData);

		if ($isReplica)
		{
			$this->treeData[$folderRow['ID']]->markAsReplica();
		}

		if($this->treeData[$folderRow['ID']]->isLink())
		{
			$this->treeData[$folderRow['ID']]->setCreateDate($folderRow['CREATE_TIME']);
			$this->treeData[TreeNode::TREE_SYMLINK_PREFIX . $folderRow['REAL_OBJECT_ID']] = $this->treeData[$folderRow['ID']];
		}

		return $this->treeData[$folderRow['ID']];
	}

	public function getPath($id, $getDirectPathIfPossible = false)
	{
		if(!isset($this->treeData[$id]))
		{
			return null;
		}

		$path = null;
		if ($getDirectPathIfPossible)
		{
			$path = $this->treeData[$id]->getPathWithoutFirstLink();
		}

		return $path?: $this->treeData[$id]->getPath();
	}

	protected function getPathByObject(BaseObject $object)
	{
		if($object instanceof Folder)
		{
			return $this->getPath($object->getId());
		}

		$parentPath = $this->getPath($object->getParentId());
		if(!$parentPath)
		{
			return null;
		}

		return $parentPath . $object->getName();
	}

	public function checkOpportunityToSkipRights()
	{
		return $this->checkRootNodeReadRights() && !$this->checkNegativeRightsInSubTree();
	}

	private function checkRootNodeReadRights()
	{
		$rootObjectId = $this->storage->getRootObjectId();
		$userId = $this->userId;
		$intranetUserCode = $this->connection->getSqlHelper()->forSql('IU' . $userId);
		$simpleUserCode = $this->connection->getSqlHelper()->forSql('U' . $userId);

		$hasReadRight = $this->connection->queryScalar("
			SELECT 'x'
			FROM b_disk_simple_right simple_right
			INNER JOIN b_user_access uaccess ON uaccess.ACCESS_CODE = simple_right.ACCESS_CODE
			WHERE simple_right.OBJECT_ID = {$rootObjectId} AND uaccess.USER_ID = {$userId} AND
			(uaccess.ACCESS_CODE = '{$intranetUserCode}' OR uaccess.ACCESS_CODE = '{$simpleUserCode}')
		");

		return (bool)$hasReadRight;
	}

	private function checkNegativeRightsInSubTree()
	{
		$rootObjectId = $this->storage->getRootObjectId();
		$userId = $this->userId;
		$intranetUserCode = $this->connection->getSqlHelper()->forSql('IU' . $userId);
		$simpleUserCode = $this->connection->getSqlHelper()->forSql('U' . $userId);

		$hasNegativeRights = $this->connection->queryScalar("
			SELECT 'x'
			FROM b_disk_right r
			INNER JOIN b_disk_object_path path ON path.OBJECT_ID = r.OBJECT_ID
			INNER JOIN b_user_access uaccess ON uaccess.ACCESS_CODE = r.ACCESS_CODE
			WHERE path.PARENT_ID = {$rootObjectId} AND uaccess.USER_ID = {$userId} AND r.NEGATIVE = 1 AND
			(uaccess.ACCESS_CODE = '{$intranetUserCode}' OR uaccess.ACCESS_CODE = '{$simpleUserCode}')
		");

		return (bool)$hasNegativeRights;
	}
}