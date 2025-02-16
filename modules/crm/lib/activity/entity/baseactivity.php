<?php

namespace Bitrix\Crm\Activity\Entity;

use Bitrix\Crm\Activity\Provider\Base as ActivityProvider;
use Bitrix\Crm\Activity\Provider\ToDo\OptionallyConfigurable;
use Bitrix\Crm\Activity\ToDo\ColorSettings\ColorSettingsProvider;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use CCrmActivity;
use CCrmDateTimeHelper;
use CCrmOwnerType;

class BaseActivity implements OptionallyConfigurable
{
	private const MAX_SUBJECT_LENGTH = 500;

	protected array $additionalFields = [];
	protected ?int $autocompleteRule = null;
	protected ?int $calendarEventId = null;
	protected bool $checkPermissions = true;
	protected ?string $colorId = null;
	protected string $completed;
	protected ?DateTime $deadline = null;
	protected string $description = '';
	protected ?int $id = null;
	protected ItemIdentifier $owner;
	protected ?int $parentActivityId = null;
	protected ActivityProvider $provider;
	private ?string $providerId = null;
	private ?string $providerTypeId = null;
	protected int $responsibleId;
	protected ?array $settings = null;
	protected ?array $storageElementIds = null;
	protected string $subject = '';
	protected ?Context $context = null;

	public function __construct(ItemIdentifier $owner, ActivityProvider $provider)
	{
		$this->owner = $owner;
		$this->provider = $provider;
		$this->responsibleId = Container::getInstance()->getContext()->getUserId();
	}

	public function getAdditionalFields(): array
	{
		return $this->additionalFields;
	}

	public function setAdditionalFields(array $fields): self
	{
		$this->additionalFields = $fields;

		return $this;
	}

	private function getAutocompleteRule(): ?int
	{
		return $this->autocompleteRule;
	}

	public function setAutocompleteRule(?int $autocompleteRule): self
	{
		$this->autocompleteRule = $autocompleteRule;

		return $this;
	}

	public function getCalendarEventId(): ?int
	{
		return $this->calendarEventId;
	}

	public function setCalendarEventId(?int $id): self
	{
		$this->calendarEventId = $id;

		return $this;
	}

	public function setCheckPermissions(bool $checkPermissions): self
	{
		$this->checkPermissions = $checkPermissions;

		return $this;
	}

	final public function getColorId(): ?string
	{
		return $this->colorId;
	}

	final public function setColorId(?string $colorId): self
	{
		$colorSettingsProvider = new ColorSettingsProvider();
		if ($colorSettingsProvider->isAvailableColorId($colorId))
		{
			$this->colorId = $colorId;
		}

		return $this;
	}

	private function setCompleted(string $completed): self
	{
		$this->completed = $completed;

		return $this;
	}

	public function isCompleted(): bool
	{
		return $this->completed === 'Y';
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

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): self
	{
		$this->description = $description;

		return $this;
	}

	public function setDefaultDescription(): static
	{
		$entityTypeId = $this->getOwner()->getEntityTypeId();
		return $this->setDescription(self::getDescriptionForEntityType($entityTypeId));
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

	public function getParentActivityId(): ?int
	{
		return $this->parentActivityId;
	}

	public function setParentActivityId(?int $parentActivityId): self
	{
		$this->parentActivityId = $parentActivityId;

		return $this;
	}

	public function getProviderId(): string
	{
		return $this->providerId;
	}

	private function setProviderId(string $providerId): self
	{
		$this->providerId = $providerId;

		return $this;
	}

	public function isValidProviderId(string $providerId): bool
	{
		return false;
	}

	public function getProviderTypeId(): string
	{
		//return $this->providerTypeId;

		return 'BASE_ACTIVITY_PROVIDER_TYPE_ID';
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

	public function getSettings(): ?array
	{
		return $this->settings;
	}

	public function setSettings(?array $settings): self
	{
		$this->settings = $settings;

		return $this;
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

	public function getSubject(): string
	{
		return $this->subject;
	}

	public function setSubject(string $subject): self
	{
		$this->subject = $subject;

		return $this;
	}

	public function getDefaultSubject(): string
	{
		return Loc::getMessage('CRM_TODO_ENTITY_ACTIVITY_DEFAULT_SUBJECT');
	}

	public function setDefaultSubject(): self
	{
		return $this->setSubject($this->getDefaultSubject());
	}

	public static function getDescriptionForEntityType(int $entityTypeId): string
	{
		$defaultDescription = Loc::getMessage('CRM_TODO_ENTITY_ACTIVITY_DESCRIPTION_CONTACT_CLIENT') ?? '';
		if ($entityTypeId === CCrmOwnerType::Undefined)
		{
			return $defaultDescription;
		}

		$entityType = CCrmOwnerType::ResolveName($entityTypeId);

		return (
			Loc::getMessage('CRM_TODO_ENTITY_ACTIVITY_DESCRIPTION_CONTACT_CLIENT_IN_' . $entityType) ?? $defaultDescription
		);
	}

	public function createWithDefaultSubjectAndDescription(
		DateTime $deadline,
		bool $ceilDeadlineTime = true,
		bool $skipSubject = false
	): Result
	{
		if ($ceilDeadlineTime)
		{
			$deadline
				->setTime($deadline->format('H'), 0)
				->add('PT1H');
		}

		$activity = (new self($this->owner, $this->provider))
			->setDefaultDescription()
			->setDeadline($deadline);

		if (!$skipSubject)
		{
			$activity->setDefaultSubject();
		}

		return $activity->save();
	}

	final public function load(int $id): ?static
	{
		$filter = [
			'BINDINGS' => [
				[
					'OWNER_TYPE_ID' => $this->owner->getEntityTypeId(),
					'OWNER_ID' => $this->owner->getEntityId(),
				]
			],
			'=PROVIDER_ID' => $this->provider::getId(),
			'=ID' => $id
		];

		return $this->getInstanceByParams($filter);
	}

	public function loadNearest(): ?static
	{
		$filter = [
			'=COMPLETED' => 'N',
			'=PROVIDER_ID' => $this->getProviderId(),
			'=PROVIDER_TYPE_ID' => $this->getProviderTypeId(),
			'BINDINGS' => [
				[
					'OWNER_TYPE_ID' => $this->owner->getEntityTypeId(),
					'OWNER_ID' => $this->owner->getEntityId(),
				],
			],
		];

		$order = [
			'DEADLINE' => 'ASC',
		];

		$options = [
			'QUERY_OPTIONS' => [
				'LIMIT' => 1,
			],
		];

		return $this->getInstanceByParams($filter, $order, $options);
	}

	private function getInstanceByParams(array $filter, array $order = [], array $options = []): ?static
	{
		$data = CCrmActivity::GetList(
			$order,
			$filter,
			false,
			false,
			[
				'ID',
				'COMPLETED',
				'DEADLINE',
				'DESCRIPTION',
				'SUBJECT',
				'RESPONSIBLE_ID',
				'ASSOCIATED_ENTITY_ID',
				'AUTOCOMPLETE_RULE',
				'STORAGE_ELEMENT_IDS',
				'CALENDAR_EVENT_ID',
				'SETTINGS',
				'PROVIDER_ID',
			],
			$options
		)->Fetch();

		if (!$data)
		{
			return null;
		}

		$activity = new static($this->owner, $this->provider);
		$activity
			->setId((int)$data['ID'])
			->setDeadline(
				($data['DEADLINE'] && !CCrmDateTimeHelper::IsMaxDatabaseDate($data['DEADLINE']))
					? DateTime::createFromUserTime($data['DEADLINE'])
					: null
			)
			->setDescription($data['DESCRIPTION'])
			->setSubject($data['SUBJECT'])
			->setResponsibleId($data['RESPONSIBLE_ID'])
			->setParentActivityId($data['ASSOCIATED_ENTITY_ID'] ?: null)
			->setAutocompleteRule($data['AUTOCOMPLETE_RULE'] ?: null)
			->setCompleted($data['COMPLETED'])
			->setCalendarEventId($data['CALENDAR_EVENT_ID'])
			->setStorageElementIds($data['STORAGE_ELEMENT_IDS'] ?: null)
			->setSettings($data['SETTINGS'] ?: null)
			->setProviderId($data['PROVIDER_ID'] ?: null);

		return $activity;
	}


	public function save(array $options = [], $useCurrentSettings = false): Result
	{
		$result = new Result();

		$fields = [
			'SUBJECT' => $this->getSubject(),
			'DESCRIPTION' => $this->getDescription(),
			'DESCRIPTION_TYPE' => \CCrmContentType::BBCode,
			'CALENDAR_EVENT_ID' => $this->getCalendarEventId(),
			'RESPONSIBLE_ID' => $this->getResponsibleId(),
			'BINDINGS' => CCrmActivity::GetBindings($this->getId()),
		];

		if ($this->getDeadline())
		{
			$fields['START_TIME'] = $this->getDeadline()->toString();
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

		if (is_array($this->getStorageElementIds()))
		{
			$fields['STORAGE_TYPE_ID'] = StorageType::Disk;
			$fields['STORAGE_ELEMENT_IDS'] = $this->getStorageElementIds();
		}

		$fields = array_merge($fields, $this->getAdditionalFields());

		if ($useCurrentSettings)
		{
			$fields['SETTINGS'] = $this->getSettings();
		}

		$color = $this->getColorId();
		if ($color)
		{
			$fields['SETTINGS']['COLOR'] = $color;
		}

		if ($this->checkPermissions && !CCrmActivity::CheckUpdatePermission($this->getOwner()->getEntityTypeId(), $this->getOwner()->getEntityId()))
		{
			$result->addError(ErrorCode::getAccessDeniedError());

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
				$result->addError(ErrorCode::getNotFoundError());

				return $result;
			}
			if ($existedActivity['COMPLETED'] === 'Y')
			{
				$result->addError(
					new Error(Loc::getMessage("CRM_TODO_ENTITY_ACTIVITY_ALREADY_COMPLETED"), 'CAN_NOT_UPDATE_COMPLETED_TODO'),
				);

				return $result;
			}
			if (!$this->isValidProviderId($existedActivity['PROVIDER_ID']))
			{
				$result->addError(ErrorCode::getNotFoundError());

				return $result;
			}

			$isSuccess = CCrmActivity::Update($this->getId(), $fields, $this->checkPermissions, true, $options);

			if (!$isSuccess)
			{
				foreach (CCrmActivity::GetErrorMessages() as $errorMessage)
				{
					$result->addError(new Error($errorMessage));
				}
			}
		} else
		{
			$fields['BINDINGS'] = empty($parentActivityBindings)
				? [
					[
						'OWNER_TYPE_ID' => $this->owner->getEntityTypeId(),
						'OWNER_ID' => $this->owner->getEntityId(),
					],
				]
				: $parentActivityBindings;
			$provider = new \Bitrix\Crm\Activity\Provider\ToDo\ToDo();
			$result = $provider->createActivity(\Bitrix\Crm\Activity\Provider\ToDo\ToDo::PROVIDER_TYPE_ID_DEFAULT, $fields, $options);
			if ($result->isSuccess())
			{
				$this->id = (int)$result->getData()['id'];

				if ($this->getParentActivityId())
				{
					// close parent activity
					if (!CCrmActivity::Complete($this->getParentActivityId(), true, ['REGISTER_SONET_EVENT' => true]))
					{
						$result->addError(new Error(implode(', ', CCrmActivity::GetErrorMessages()), 'CAN_NOT_COMPLETE'));
					}
				}
			}
		}

		return $result;
	}

	private function isParentActivityCompleted(int $activityId): bool
	{
		$dbRes = CCrmActivity::GetList(
			[],
			['ID' => $activityId, 'CHECK_PERMISSIONS' => 'N'],
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

	public function appendAdditionalFields(array $fields): self
	{
		$this->additionalFields = array_merge_recursive($this->additionalFields, $fields);

		return $this;
	}

	public function setContext(Context $context): self
	{
		$this->context = $context;

		return $this;
	}

	public function getContext(): ?Context
	{
		return $this->context;
	}
}
