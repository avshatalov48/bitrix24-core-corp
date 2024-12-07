<?php

namespace Bitrix\TasksMobile\Provider;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Internals\Task\CheckListTreeTable;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\TasksMobile\Dto\ChecklistAttachmentDto;

final class ChecklistProvider
{
	private static array $plainItems = [];

	/** @var array<int, ChecklistAttachmentDto> */
	private static array $attachments = [];

	public function __construct(
		private ?int $userId = null,

		/** @var CheckListFacade */
		private readonly string $facade = TaskCheckListFacade::class,
	)
	{
		$this->userId = ($userId ?? CurrentUser::get()->getId());
		if (!is_subclass_of($this->facade, CheckListFacade::class))
		{
			throw new ArgumentException("Parameter 'facade' must be subclass of CheckListFacade");
		}
	}

	public function canAdd(int $taskId): bool
	{
		return $this->facade::isActionAllowed(
			$taskId,
			null,
			$this->userId,
			CheckListFacade::ACTION_ADD,
		);
	}

	/**
	 * @param array $tasks
	 * @return array
	 */
	public function fillFullDataForTasks(array $tasks): array
	{
		$tasks = $this->fillCommonDataForTasks($tasks);

		foreach ($tasks as $taskId => $task)
		{
			$tasks[$taskId]['CHECKLIST_DETAILS'] = $this->getCheckListDetailsData($this->getChecklistTree($taskId));
		}

		return $tasks;
	}

	/**
	 * @param array $tasks
	 * @return array
	 */
	public function fillCommonDataForTasks(array $tasks): array
	{
		$taskIds = array_keys($tasks);

		foreach ($taskIds as $taskId)
		{
			$tasks[$taskId]['CHECKLIST'] = [
				'COMPLETED' => 0,
				'UNCOMPLETED' => 0,
			];
			$tasks[$taskId]['CHECKLIST_DETAILS'] = [];
		}

		$query = new Query(CheckListTable::getEntity());
		$query
			->setSelect([
				'ID',
				'TASK_ID',
				'TITLE',
				'IS_COMPLETE',
				new ExpressionField('LEVEL', 'MAX(%s)', ['IT.LEVEL']),
			])
			->setFilter(['TASK_ID' => $taskIds])
			->setOrder(['SORT_INDEX' => 'ASC'])
			->registerRuntimeField(
				'IT',
				new ReferenceField(
					'IT',
					CheckListTreeTable::class,
					Join::on('this.ID', 'ref.CHILD_ID'),
					['join_type' => 'INNER']
				)
			)
		;

		$result = $query->exec();
		while ($row = $result->fetch())
		{
			$taskId = (int)$row['TASK_ID'];
			$level = (int)$row['LEVEL'];
			$completedKey = ($row['IS_COMPLETE'] === 'Y') ? 'COMPLETED' : 'UNCOMPLETED';

			if ($level === 1)
			{
				$tasks[$taskId]['CHECKLIST'][$completedKey] += 1;
			}
			else
			{
				$tasks[$taskId]['CHECKLIST_DETAILS'][] = [
					'TITLE' => $row['TITLE'],
					'COMPLETED' => null,
					'UNCOMPLETED' => null,
				];
			}
		}

		return $tasks;
	}

	/**
	 * @param int $taskId
	 * @param bool $isForCopy
	 * @return array
	 */
	public function getChecklistTree(int $taskId, bool $isForCopy = false): array
	{
		$checkListItems = $this->getPlainCheckListItems($taskId);
		$this->loadDiskAttachmentsData($checkListItems);

		if ($isForCopy)
		{
			foreach ($checkListItems as $id => $item)
			{
				$checkListItems[$id]['COPIED_ID'] = $id;
				$checkListItems[$id]['IS_COMPLETE'] = 'N';
				unset($checkListItems[$id]['ID']);
			}
		}
		else
		{
			$checkListItems = $this->fillAdditionalActionsForCheckListItems($checkListItems, $taskId);
		}

		$objectTreeStructure = $this->buildTreeStructure($checkListItems);
		$objectTreeStructure = $this->fillTreeInfo($objectTreeStructure);

		return $this->fillAttachments($objectTreeStructure->toTreeArray());
	}

	private function getPlainCheckListItems($taskId): array
	{
		if (!array_key_exists($taskId, self::$plainItems))
		{
			self::$plainItems[$taskId] = $this->facade::getItemsForEntity($taskId, $this->userId);
		}

		return self::$plainItems[$taskId];
	}

	private function fillAdditionalActionsForCheckListItems(array $checkListItems, int $taskId): array
	{
		$canAdd = $this->canAdd($taskId);
		$canAddAccomplice = (
			TaskAccessController::can(
				$this->userId,
				ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES,
				$taskId,
			)
			&& !TariffPlanRestrictionProvider::isAccompliceAuditorRestricted()
		);

		foreach ($checkListItems as $id => $item)
		{
			if (array_key_exists('ACTION', $item))
			{
				$checkListItems[$id]['ACTION']['ADD'] = $canAdd;
				$checkListItems[$id]['ACTION']['ADD_ACCOMPLICE'] = $canAddAccomplice;
			}
		}

		return $checkListItems;
	}

	public function buildTreeStructure($checkListItems): CheckList
	{
		$nodeId = 0;
		$sortIndex = 0;

		$result = new CheckList($nodeId, $this->userId, $this->facade);
		$arrayTreeStructure = $this->facade::getArrayStructuredRoots(
			$checkListItems,
			$this->getKeyToSort($checkListItems),
		);
		foreach ($arrayTreeStructure as $root)
		{
			$nodeId++;
			$result->add(
				$this->makeTree($root['NODE_ID'] ?? $nodeId, $root, $sortIndex, false),
			);
			$sortIndex++;
		}

		return $result;
	}

	private function getKeyToSort($items): string
	{
		$keyToSort = 'PARENT_ID';

		foreach ($items as $item)
		{
			if (array_key_exists('PARENT_NODE_ID', $item))
			{
				$keyToSort = 'PARENT_NODE_ID';
				break;
			}
		}

		return $keyToSort;
	}

	private function makeTree($nodeId, $root, $sortIndex, $displaySortIndex): CheckList
	{

		$root['SORT_INDEX'] = $sortIndex;
		$root['DISPLAY_SORT_INDEX'] = htmlspecialcharsbx($displaySortIndex);

		$tree = new CheckList($nodeId, $this->userId, $this->facade, $root);

		$localSortIndex = 0;
		foreach ($root['SUB_TREE'] as $item)
		{
			++$localSortIndex;
			$nodeId = is_int($nodeId) ? ++$nodeId : $item['NODE_ID'];
			$nextDisplaySortIndex = (
			($displaySortIndex === false) ? $localSortIndex : "$displaySortIndex.$localSortIndex"
			);

			$tree->add(
				$this->makeTree($nodeId, $item, $localSortIndex - 1, $nextDisplaySortIndex),
			);
		}

		return $tree;
	}

	private function fillTreeInfo(CheckList $tree): CheckList
	{
		$completedCount = 0;

		foreach ($tree->getDescendants() as $descendant)
		{
			/** @var CheckList $descendant */
			$fields = $descendant->getFields();

			if ($fields['IS_COMPLETE'])
			{
				$completedCount++;
			}

			$this->fillTreeInfo($descendant);
		}

		$tree->setFields(['COMPLETED_COUNT' => $completedCount]);

		return $tree;
	}

	private function fillAttachments(array $tree): array
	{
		if (!empty($tree['FIELDS']['ATTACHMENTS']))
		{
			$attachments = [];
			foreach ($tree['FIELDS']['ATTACHMENTS'] as $fileId => $originValue)
			{
				$attachments[$fileId] = self::$attachments[$fileId] ?? $originValue;
			}
			$tree['FIELDS']['ATTACHMENTS'] = $attachments;
		}

		foreach ($tree['DESCENDANTS'] as $key => $descendant)
		{
			$tree['DESCENDANTS'][$key] = $this->fillAttachments($descendant);
		}

		return $tree;
	}

	private function getCheckListDetailsData(array $checkListTree): array
	{
		$checkListDetailsData = [];

		foreach ($checkListTree['DESCENDANTS'] as $descendant)
		{
			$counts = $this->fillDetailsDataRecursively($descendant);
			$checkListDetailsData[] = [
				'TITLE' => $descendant['FIELDS']['TITLE'],
				'COMPLETED' => $counts['COMPLETED'],
				'UNCOMPLETED' => $counts['UNCOMPLETED'],
			];
		}

		return $checkListDetailsData;
	}

	private function fillDetailsDataRecursively(array $item): array
	{
		$result = [
			'COMPLETED' => 0,
			'UNCOMPLETED' => 0,
		];

		foreach ($item['DESCENDANTS'] as $descendant)
		{
			$isComplete = $descendant['FIELDS']['IS_COMPLETE'];

			$result['COMPLETED'] += ($isComplete ? 1 : 0);
			$result['UNCOMPLETED'] += ($isComplete ? 0 : 1);

			$descendantResult = $this->fillDetailsDataRecursively($descendant);

			$result['COMPLETED'] += $descendantResult['COMPLETED'];
			$result['UNCOMPLETED'] += $descendantResult['UNCOMPLETED'];
		}

		return $result;
	}

	private function loadDiskAttachmentsData(array $plainChecklistItems = []): void
	{
		$attachmentIds = [];

		foreach ($plainChecklistItems as $item)
		{
			if (!empty($item['ATTACHMENTS']))
			{
				$attachmentIds = array_merge(
					$attachmentIds,
					array_keys($item['ATTACHMENTS'])
				);
			}
		}

		if (empty($attachmentIds))
		{
			return;
		}

		$attachmentIds = array_unique(
			array_map('intval', $attachmentIds)
		);

		$loadIds = array_diff(
			$attachmentIds,
			array_keys(self::$attachments)
		);

		if (empty($loadIds))
		{
			return;
		}

		$attachments = (new DiskFileProvider())->getDiskFileAttachments($loadIds);

		foreach ($attachments as $id => $attachment)
		{
			self::$attachments[$id] = new ChecklistAttachmentDto(
				id: $id,
				fileId: $id,
				serverFileId: 'n' . $attachment['OBJECT_ID'],
				name: $attachment['NAME'] ?? '',
				url: $attachment['URL'] ?? '',
				type: $attachment['TYPE'] ?? '',
				isUploading: false,
			);
		}
	}
}
