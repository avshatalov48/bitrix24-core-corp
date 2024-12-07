<?php

namespace Bitrix\Crm\Activity\Entity;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\LayoutDto;
use Bitrix\Crm\Activity\Provider\Eventable\PingOffset;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;

class ConfigurableRestApp
{
	protected const ERR_CANT_CHANGE_PROVIDER_TYPE_ID = 'CANT_CHANGE_PROVIDER_TYPE_ID';

	protected ?int $id = null;
	protected int $responsibleId;
	protected ?bool $completed = null;
	protected ?DateTime $deadline = null;
	protected ?bool $isIncomingChannel = null;
	/** @var int[] */
	protected ?array $pingOffsets = [];
	protected ?string $restClientId = null;
	protected ?string $badgeCode = null;

	protected ?string $originatorId = null;
	protected ?string $originId = null;

	protected ?string $typeId = null;

	protected ItemIdentifier $owner;

	protected ?LayoutDto $layoutDto = null;

	public function __construct(ItemIdentifier $owner)
	{
		$this->owner = $owner;
		$this->responsibleId = Container::getInstance()->getContext()->getUserId();
	}

	protected static function loadMessages(): void
	{
		Container::getInstance()->getLocalization()->loadMessages();
	}

	public static function load(int $id): ?self
	{
		$data = \CCrmActivity::GetList(
			[],
			[
				'=ID' => $id,
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			[
				'ID',
				'COMPLETED',
				'DEADLINE',
				'DESCRIPTION',
				'RESPONSIBLE_ID',
				'PROVIDER_ID',
				'PROVIDER_TYPE_ID',
				'PROVIDER_DATA',
				'PROVIDER_PARAMS',
				'OWNER_TYPE_ID',
				'OWNER_ID',
				'ORIGIN_ID',
				'ORIGINATOR_ID',
				'IS_INCOMING_CHANNEL',
			]
		)->Fetch();

		if (!$data)
		{
			return null;
		}
		$id = (int)$data['ID'];

		if ($data['PROVIDER_ID'] !== \Bitrix\Crm\Activity\Provider\ConfigurableRestApp::getId())
		{
			return null;
		}
		if (!\Bitrix\Crm\Activity\Provider\ConfigurableRestApp::checkReadPermission($data))
		{
			return null;
		}

		try
		{
			$layout = (array)Json::decode($data['PROVIDER_DATA'] ?? '{}');
		}
		catch (ArgumentException $e)
		{
			$layout = [];
		}

		$activity = new self(new ItemIdentifier($data['OWNER_TYPE_ID'], $data['OWNER_ID']));
		$activity
			->setId($id)
			->setTypeId($data['PROVIDER_TYPE_ID'] ?? null)
			->setCompleted($data['COMPLETED'] === 'Y')
			->setDeadline(
				($data['DEADLINE'] && !\CCrmDateTimeHelper::IsMaxDatabaseDate($data['DEADLINE']))
					? DateTime::createFromUserTime($data['DEADLINE'])
					: null
			)
			->setPingOffsets(PingOffset::getInstance()->getOffsetsByActivityId($id))
			->setIsIncomingChannel($data['IS_INCOMING_CHANNEL'] === 'Y')
			->setResponsibleId($data['RESPONSIBLE_ID'])
			->setBadgeCode($data['PROVIDER_PARAMS']['badgeCode'] ?? null)
			->setRestClientId($data['PROVIDER_PARAMS']['clientId'] ?? null)
			->setOriginId($data['ORIGIN_ID'] ?? null)
			->setOriginatorId($data['ORIGINATOR_ID'] ?? null)
			->setLayoutDto(new LayoutDto($layout))
		;

		return $activity;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getOwner(): ItemIdentifier
	{
		return $this->owner;
	}

	public function getRestClientId(): ?string
	{
		return $this->restClientId;
	}

	public function setRestClientId(?string $restClientId): self
	{
		$this->restClientId = $restClientId;

		return $this;
	}

	private function setId(?int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getLayoutDto(): ?LayoutDto
	{
		return $this->layoutDto;
	}

	public function setLayoutDto(?LayoutDto $layoutDto): self
	{
		$this->layoutDto = $layoutDto;

		return $this;
	}

	public function getCompleted(): ?bool
	{
		return $this->completed;
	}

	public function setCompleted(?bool $completed): self
	{
		$this->completed = $completed;

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

	public function getResponsibleId(): ?int
	{
		return $this->responsibleId;
	}

	public function setResponsibleId(?int $responsibleId): self
	{
		$this->responsibleId = $responsibleId;

		return $this;
	}

	public function getIsIncomingChannel(): ?bool
	{
		return $this->isIncomingChannel;
	}

	public function setIsIncomingChannel(?bool $isIncomingChannel): self
	{
		$this->isIncomingChannel = $isIncomingChannel;

		return $this;
	}

	public function getPingOffsets(): ?array
	{
		return $this->pingOffsets;
	}

	/**
	 * @param int[] $pingOffsets
	 * @return self
	 */
	public function setPingOffsets(?array $pingOffsets): self
	{
		$this->pingOffsets = $pingOffsets;

		return $this;
	}

	public function getBadgeCode(): ?string
	{
		return $this->badgeCode;
	}

	public function setBadgeCode(?string $badgeCode): self
	{
		$this->badgeCode = $badgeCode;

		return $this;
	}

	public function getOriginatorId(): ?string
	{
		return $this->originatorId;
	}

	public function setOriginatorId(?string $originatorId): self
	{
		$this->originatorId = $originatorId;

		return $this;
	}

	public function getOriginId(): ?string
	{
		return $this->originId;
	}

	public function setOriginId(?string $originId): self
	{
		$this->originId = $originId;

		return $this;
	}

	public function setTypeId(?string $typeId): self
	{
		$this->typeId = $typeId;

		return $this;
	}

	public function getTypeId(): ?string
	{
		return $this->typeId;
	}

	public function save(): Result
	{
		$result = new Result();

		if (!$this->getLayoutDto())
		{
			$result->addError(\Bitrix\Crm\Controller\ErrorCode::getRequiredArgumentMissingError('layout'));

			return $result;
		}

		$fields = [
			'SUBJECT' => $this->getLayoutDto()->header ? $this->getLayoutDto()->header->title : null,
		];
		if ($this->getDeadline())
		{
			$fields['END_TIME'] = $this->getDeadline()->toString();
		}
		$fields['RESPONSIBLE_ID'] = $this->getResponsibleId();
		$fields['COMPLETED'] = $this->getCompleted() ? 'Y' : 'N';
		$fields['IS_INCOMING_CHANNEL'] = $this->getIsIncomingChannel() ? 'Y' : 'N';
		$fields['PROVIDER_DATA'] = Json::encode($this->getLayoutDto(), 0);
		$fields['PROVIDER_PARAMS'] =[
			'clientId' => $this->getRestClientId(),
			'badgeCode' => $this->getBadgeCode(),
		];
		$fields['PING_OFFSETS'] = $this->getPingOffsets();
		$fields['ORIGINATOR_ID'] = $this->getOriginatorId();
		$fields['ORIGIN_ID'] = $this->getOriginId();

		if ($this->getId())
		{
			if (!$this->hasPermissionToUpdate($fields))
			{
				$result->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

				return $result;
			}

			$existedActivity = \CCrmActivity::GetList(
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
					'PROVIDER_TYPE_ID',
				]
			)->Fetch();

			if (!$existedActivity)
			{
				$result->addError(\Bitrix\Crm\Controller\ErrorCode::getNotFoundError());

				return $result;
			}
			if ($existedActivity['PROVIDER_ID'] !== \Bitrix\Crm\Activity\Provider\ConfigurableRestApp::getId())
			{
				$result->addError(\Bitrix\Crm\Controller\ErrorCode::getNotFoundError());

				return $result;
			}
			if ($this->getTypeId() !== null && $this->getTypeId() !== $existedActivity['PROVIDER_TYPE_ID'])
			{
				static::loadMessages();

				$result->addError(
					new Error(
						Loc::getMessage('CRM_ACT_ENT_CONF_CANT_CHANGE_PROVIDER_TYPE_ID'),
						static::ERR_CANT_CHANGE_PROVIDER_TYPE_ID
					)
				);

				return $result;
			}
			$isSuccess = \CCrmActivity::Update($this->getId(), $fields, false);

			if (!$isSuccess)
			{
				foreach (\CCrmActivity::GetErrorMessages() as $errorMessage)
				{
					$result->addError(new Error($errorMessage));
				}
			}
		}
		else
		{
			if (!$this->hasPermissionToAdd())
			{
				$result->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

				return $result;
			}

			$fields['BINDINGS'] = [
				[
					'OWNER_TYPE_ID' => $this->owner->getEntityTypeId(),
					'OWNER_ID' => $this->owner->getEntityId(),
				]
			];

			$provider = new \Bitrix\Crm\Activity\Provider\ConfigurableRestApp();
			$result = $provider->createActivity(
				$this->getTypeId() ?? $provider::PROVIDER_TYPE_ID_DEFAULT,
				$fields,
				['skipTypeCheck' => true]
			);
			if ($result->isSuccess())
			{
				$this->id = (int)$result->getData()['id'];
			}
		}

		return $result;
	}

	private function hasPermissionToAdd(): bool
	{
		return \CCrmActivity::CheckUpdatePermission($this->owner->getEntityTypeId(), $this->owner->getEntityId());
	}
	private function hasPermissionToUpdate(array $fields): bool
	{
		$fields['OWNER_TYPE_ID'] = $this->owner->getEntityTypeId();
		$fields['OWNER_ID'] = $this->owner->getEntityId();

		return \Bitrix\Crm\Activity\Provider\ConfigurableRestApp::checkUpdatePermission($fields);
	}
}
