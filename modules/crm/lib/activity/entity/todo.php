<?php

namespace Bitrix\Crm\Activity\Entity;

use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use CCrmActivity;

class ToDo
{
	private const MAX_SUBJECT_LENGTH = 500;

	protected ?int $id = null;

	protected string $description = '';
	protected ?DateTime $deadline = null;
	protected ?int $parentActivityId = null;

	protected int $responsibleId;
	protected ItemIdentifier $owner;

	protected ?int $autocompleteRule = null;
	protected string $completed;

	protected bool $checkPermissions = true;

	protected ?array $storageElementIds = null;

	public static function createWithDefaultDescription(int $entityTypeId, int $id, DateTime $deadline): Result
	{
		$itemIdentifier = new ItemIdentifier($entityTypeId, $id);
		return (new self($itemIdentifier))
			->setDefaultDescription()
			->setDeadline($deadline)
			->save()
		;
	}

	public function __construct(ItemIdentifier $owner)
	{
		$this->owner = $owner;
		$this->responsibleId = Container::getInstance()->getContext()->getUserId();
	}

	public static function load(ItemIdentifier $owner, int $id): ?self
	{
		$data = CCrmActivity::GetList(
			[],
				[
					'BINDINGS' => [
						'OWNER_TYPE_ID' => $owner->getEntityTypeId(),
						'OWNER_ID' => $owner->getEntityId(),
					],
					'ID' => $id
			],
			false,
			false,
			[
				'ID',
				'COMPLETED',
				'DEADLINE',
				'DESCRIPTION',
				'RESPONSIBLE_ID',
				'ASSOCIATED_ENTITY_ID',
				'AUTOCOMPLETE_RULE',
				'STORAGE_ELEMENT_IDS',
			]
		)->Fetch();

		if ($data)
		{
			$todo = new self($owner);
			$todo
				->setId((int)$data['ID'])
				->setDeadline(
					($data['DEADLINE'] && !\CCrmDateTimeHelper::IsMaxDatabaseDate($data['DEADLINE']))
						? DateTime::createFromUserTime($data['DEADLINE'])
						: null
				)
				->setDescription($data['DESCRIPTION'])
				->setResponsibleId($data['RESPONSIBLE_ID'])
				->setParentActivityId($data['ASSOCIATED_ENTITY_ID'] ?: null)
				->setAutocompleteRule($data['AUTOCOMPLETE_RULE'] ?: null)
				->setCompleted($data['COMPLETED'])
				->setStorageElementIds($data['STORAGE_ELEMENT_IDS'] ?: null)
			;

			return $todo;
		}

		return null;
	}

	public static function getDescriptionForEntityType(int $entityTypeId): string
	{
		$defaultDescription = Loc::getMessage('CRM_TODO_ENTITY_ACTIVITY_DESCRIPTION_CONTACT_CLIENT') ?? '';
		if ($entityTypeId === \CCrmOwnerType::Undefined)
		{
			return $defaultDescription;
		}

		$entityType = \CCrmOwnerType::ResolveName($entityTypeId);

		return (
			Loc::getMessage('CRM_TODO_ENTITY_ACTIVITY_DESCRIPTION_CONTACT_CLIENT_IN_' . $entityType) ?? $defaultDescription
		);
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId(?int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getOwner(): ItemIdentifier
	{
		return $this->owner;
	}

	public function setOwner(ItemIdentifier $owner): self
	{
		$this->owner = $owner;

		return $this;
	}

	public function getResponsibleId(): ?int
	{
		return $this->responsibleId;
	}

	public function setResponsibleId(int $responsibleId): self
	{
		$this->responsibleId = $responsibleId;

		return $this;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDefaultDescription(): ToDo
	{
		$entityTypeId = $this->getOwner()->getEntityTypeId();
		return $this->setDescription(self::getDescriptionForEntityType($entityTypeId));
	}

	public function setDescription(string $description): self
	{
		$this->description = $description;

		return $this;
	}

	public function getDeadline(): ?DateTime
	{
		return $this->deadline;
	}

	public function setDeadline(?DateTime $deadline): self
	{
		$this->deadline = $deadline;

		return $this;
	}

	public function getParentActivityId(): ?int
	{
		return $this->parentActivityId;
	}

	public function setParentActivityId(?int $parentActivityId): self
	{
		$this->parentActivityId = $parentActivityId;

		return $this;
	}

	public function getAutocompleteRule(): ?int
	{
		return $this->autocompleteRule;
	}

	public function setAutocompleteRule(?int $autocompleteRule): self
	{
		$this->autocompleteRule = $autocompleteRule;

		return $this;
	}

	public function getCompleted(): string
	{
		return $this->completed;
	}

	protected function setCompleted(string $completed): self
	{
		$this->completed = $completed;

		return $this;
	}

	public function isCompleted(): bool
	{
		return $this->completed === 'Y';
	}

	public function getStorageElementIds(): ?array
	{
		return $this->storageElementIds;
	}

	/**
	 * @param string|int[] $storageElementIds
	 *
	 * @return $this
	 */
	public function setStorageElementIds($storageElementIds): self
	{
		if (is_string($storageElementIds))
		{
			$storageElementIds = unserialize($storageElementIds, ['allowed_classes' => false]);
		}

		if (is_array($storageElementIds))
		{
			$this->storageElementIds = $storageElementIds;
		}

		return $this;
	}

	public function setCheckPermissions(bool $checkPermissions): self
	{
		$this->checkPermissions = $checkPermissions;

		return $this;
	}

	public function save(): Result
	{
		$result = new Result();

		if ($this->getDescription() === '')
		{
			$result->addError(new Error(Loc::getMessage('CRM_TODO_ENTITY_ACTIVITY_EMPTY_DESCRIPTION'), 'ERROR_EMPTY_DESCRIPTION'));

			return $result;
		}

		$fields = [
			'DESCRIPTION' => $this->getDescription(),
			'SUBJECT' => $this->getSubjectFromDescription($this->getDescription()),
		];

		if ($this->getDeadline())
		{
			$fields['END_TIME'] = $this->getDeadline()->toString();
		}
		if (!is_null($this->getAutocompleteRule()))
		{
			$fields['AUTOCOMPLETE_RULE'] = $this->getAutocompleteRule();
		}

		$parentActivityBindings = [];
		if ($this->getParentActivityId())
		{
			if ($this->isParentActivityCompleted($this->getParentActivityId()))
			{
				$result->addError(new Error(Loc::getMessage('CRM_TODO_ENTITY_ACTIVITY_PARENT_ACTIVITY_RESTRICT'), 'ERROR_PARENT_ACTIVITY_RESTRICT'));

				return $result;
			}

			$parentActivityBindings = CCrmActivity::GetBindings($this->getParentActivityId());
			if ($this->isParentActivityValid($parentActivityBindings))
			{
				$fields['ASSOCIATED_ENTITY_ID'] = $this->getParentActivityId();
			}
		}

		if (!is_null($this->getStorageElementIds()))
		{
			$fields['STORAGE_TYPE_ID'] = StorageType::Disk;
			$fields['STORAGE_ELEMENT_IDS'] = $this->getStorageElementIds();
		}

		if($this->checkPermissions && !CCrmActivity::CheckUpdatePermission($this->getOwner()->getEntityTypeId(), $this->getOwner()->getEntityId()))
		{
			$result->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return $result;
		}

		if ($this->getId())
		{
			$existedActivity = CCrmActivity::GetList(
				[],
				[
					'=ID' => $this->getId(),
					'CHECK_PERMISSIONS' => 'N',
				],
				false,
				false,
				[
					'ID',
					'COMPLETED',
					'PROVIDER_ID',
				]
			)->Fetch();

			if (!$existedActivity)
			{
				$result->addError(\Bitrix\Crm\Controller\ErrorCode::getNotFoundError());

				return $result;
			}
			if ($existedActivity['COMPLETED'] === 'Y')
			{
				$result->addError(
					new Error(Loc::getMessage("CRM_TODO_ENTITY_ACTIVITY_ALREADY_COMPLETED"), 'CAN_NOT_UPDATE_COMPLETED_TODO'),
				);

				return $result;
			}
			if ($existedActivity['PROVIDER_ID'] !== \Bitrix\Crm\Activity\Provider\ToDo::getId())
			{
				$result->addError(\Bitrix\Crm\Controller\ErrorCode::getNotFoundError());

				return $result;
			}
			$isSuccess = CCrmActivity::Update($this->getId(), $fields, $this->checkPermissions);

			if (!$isSuccess)
			{
				foreach (CCrmActivity::GetErrorMessages() as $errorMessage)
				{
					$result->addError(new Error($errorMessage));
				}
			}

		}
		else
		{
			$fields['RESPONSIBLE_ID'] = $this->getResponsibleId();
			$fields['BINDINGS'] = empty($parentActivityBindings)
				? [
					[
						'OWNER_TYPE_ID' => $this->owner->getEntityTypeId(),
						'OWNER_ID' => $this->owner->getEntityId(),
					],
				]
				: $parentActivityBindings;
			$provider = new \Bitrix\Crm\Activity\Provider\ToDo();
			$result = $provider->createActivity(\Bitrix\Crm\Activity\Provider\ToDo::PROVIDER_TYPE_ID_DEFAULT, $fields);
			if ($result->isSuccess())
			{
				$this->id = (int)$result->getData()['id'];

				if ($this->getParentActivityId())
				{
					// close parent activity
					if (!CCrmActivity::Complete($this->getParentActivityId(), true, ['REGISTER_SONET_EVENT' => true]))
					{
						$this->addError(new Error(implode(', ', CCrmActivity::GetErrorMessages()), 'CAN_NOT_COMPLETE'));
					}
				}
			}
		}

		return $result;
	}

	private function getSubjectFromDescription(string $description): string
	{
		$lines = explode("\n", $description, 2);
		if (count($lines) > 1)
		{
			$subject = $lines[0];
			if(mb_strlen($subject) > self::MAX_SUBJECT_LENGTH)
			{
				$subject = mb_substr($subject, 0, self::MAX_SUBJECT_LENGTH);
			}
			return rtrim($subject, '.') . '...';
		}

		return TruncateText($lines[0], self::MAX_SUBJECT_LENGTH);
	}

	private function isParentActivityCompleted(int $activityId): bool
	{
		$dbRes = CCrmActivity::GetList(
			[],
			['ID'=> $activityId, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID', 'COMPLETED']
		);
		$fields = $dbRes->Fetch();

		return isset($fields['COMPLETED']) && $fields['COMPLETED'] === true;
	}

	private function isParentActivityValid($bindings): bool
	{
		if (is_array($bindings) && !empty($bindings))
		{
			foreach ($bindings as $binding)
			{
				if (
					$this->owner->getEntityTypeId() === (int)$binding['OWNER_TYPE_ID']
					&& $this->owner->getEntityId() === (int)$binding['OWNER_ID']
				)
				{
					return true;
				}
			}
		}

		return false;
	}
}
