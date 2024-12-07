<?php

namespace Bitrix\Tasks\Flow\Access;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection;
use Bitrix\Tasks\Flow\Internal\FlowMemberTable;
use Bitrix\Tasks\Integration\Intranet\Flow\Department;

final class FlowModel implements AccessibleItem
{
	protected int $id = 0;
	protected ?int $ownerId = null;
	protected ?int $creatorId = null;
	protected ?int $projectId = null;
	protected ?int $templateId = null;
	protected static array $userMembers = [];
	protected static array $departments = [];
	protected static array $forAll = [];

	public static function createFromArray(array|Arrayable $data): self
	{
		if ($data instanceof Arrayable)
		{
			$data = $data->toArray();
		}

		$model = new self();

		if (isset($data['id']))
		{
			$model->id = $data['id'];
		}

		if (isset($data['ID']))
		{
			$model->id = $data['ID'];
		}

		$model->ownerId = $data['ownerId'] ?? $data['OWNER_ID'] ?? null;
		$model->projectId = $data['groupId'] ?? $data['GROUP_ID'] ?? null;
		$model->templateId = $data['templateId'] ?? $data['TEMPLATE_ID'] ?? null;

		return $model;
	}

	public static function createFromId(int $itemId): self
	{
		$model = new self();
		$model->id = $itemId;

		return $model;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getOwnerId(): int
	{
		$this->ownerId ??= $this->ownerId = (int)$this->getEntity()?->getOwnerId();

		return $this->ownerId;
	}

	public function getCreatorId(): int
	{
		$this->creatorId ??= (int)$this->getEntity()?->getCreatorId();

		return $this->creatorId;
	}

	public function getMembers(): FlowMemberCollection
	{
		$creators = $this->getEntity(['MEMBERS'])?->getMembers();

		return $creators ?? new FlowMemberCollection();
	}

	public function getProjectId(): int
	{
		$this->projectId ??= (int)$this->getEntity()?->getGroupId();

		return $this->projectId;
	}

	public function isNew(): bool
	{
		return $this->id === 0;
	}

	public function getTemplateId(): int
	{
		$this->templateId ??= (int)$this->getEntity()?->getTemplateId();

		return $this->templateId;
	}

	public function isUserMember(int $userId): bool
	{
		if ($this->id <= 0 || $userId <= 0)
		{
			return false;
		}

		if (isset(self::$userMembers[$this->id][$userId]))
		{
			return self::$userMembers[$this->id][$userId];
		}

		$row = FlowMemberTable::query()
			->setSelect(['ID'])
			->where('ENTITY_TYPE', 'U')
			->where('ENTITY_ID', $userId)
			->where('FLOW_ID', $this->id)
			->exec()
			->fetchObject();

		self::$userMembers[$this->id][$userId] = (null !== $row);

		return self::$userMembers[$this->id][$userId];
	}

	public function isForAll(): bool
	{
		if ($this->id <= 0)
		{
			return false;
		}

		if (isset(self::$forAll[$this->id]))
		{
			return self::$forAll[$this->id];
		}

		$row = FlowMemberTable::query()
			->setSelect(['ID'])
			->where('ACCESS_CODE', 'UA')
			->where('FLOW_ID', $this->id)
			->exec()
			->fetchObject();

		self::$forAll[$this->id] = (null !== $row);

		return self::$forAll[$this->id];
	}

	public function getDepartments(): array
	{
		if ($this->id <= 0)
		{
			return [];
		}

		if (isset(self::$departments[$this->id]))
		{
			return self::$departments[$this->id];
		}

		$filter = Query::filter()
			->logic(ConditionTree::LOGIC_OR)
			->where('ENTITY_TYPE', 'D')
			->where('ENTITY_TYPE', 'DR');

		$departments = FlowMemberTable::query()
			->setSelect(['ID', 'ENTITY_ID', 'ENTITY_TYPE'])
			->where($filter)
			->where('FLOW_ID', $this->id)
			->exec()
			->fetchCollection();

		if ($departments->isEmpty())
		{
			self::$departments[$this->id] = [];
			return self::$departments[$this->id];
		}

		$ids = [];
		foreach ($departments as $department)
		{
			if ($department->getEntityType() === 'DR') // department recursive
			{
				$ids = array_merge($ids,$this->getSubDepartments($department->getEntityId()));
			}
			elseif ($department->getEntityType() === 'D')
			{
				$ids[] = $department->getEntityId();
			}
		}

		self::$departments[$this->id] = $ids;

		return self::$departments[$this->id];
	}

	protected function getEntity(array $additionalSelect = []): ?FlowEntity
	{
		return FlowRegistry::getInstance()->get($this->id, array_merge(['*'], $additionalSelect));
	}

	protected function getSubDepartments(int $departmentId): array
	{
		$subDepartments = array_map('intval', Department::getSubDepartments($departmentId));
		return array_unique(array_merge([$departmentId], $subDepartments));
	}
}