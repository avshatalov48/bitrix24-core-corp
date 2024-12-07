<?php

namespace Bitrix\Sign\Document\Entity;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Sign\Document;
use Bitrix\Sign\Document\Member;

final class SmartB2e extends Dummy
{
	/**
	 *
	 * @var Item|null
	 */
	private $item = null;

	public function __construct(?int $id = null)
	{
		if ($id !== null && \Bitrix\Main\Loader::includeModule('crm'))
		{
			$entityTypeId = \CCrmOwnerType::SmartB2eDocument;
			$factory = Service\Container::getInstance()->getFactory($entityTypeId);
			$this->item = $factory?->getItem($id);
		}
	}

	public static function create(bool $checkPermission = true): ?int
	{
		$entityTypeId = self::getEntityTypeId();

		if (!$entityTypeId)
		{
			return null;
		}

		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}

		$item = $factory->createItem();
		$operation = $factory->getAddOperation($item)
			->disableAllChecks()
			->disableAutomation()
			->disableBizProc()
			->disableSaveToTimeline()
		;

		$result = $operation->launch();
		if ($result->isSuccess())
		{
			return $item->getId();
		}

		return null;
	}

	public static function getEntityTypeId(): int
	{
		if (!Loader::includeModule('crm'))
		{
			return 0;
		}

		return \CCrmOwnerType::SmartB2eDocument;
	}

	public function getId(): int
	{
		return $this->item?->getId() ?? 0;
	}

	public function setItem(?Item $item): self
	{
		$this->item = $item;

		return $this;
	}

	public function getNumber()
	{
		return $this->item?->getNumber() ?? 0;
	}

	public function refreshNumber()
	{
		return null;
	}

	public function getStageId(): ?string
	{
		return $this->item?->getStageId();
	}


	public function getContactsIds(): array
	{
		return $this->item?->getContactIds() ?? [];
	}

	public function getCompanyTitle(): ?string
	{
		return null;
	}

	public function getCompanyId(): int
	{
		return $this->item?->get('MYCOMPANY_ID') ?? 0;
	}

	public function getTitle(): ?string
	{
		return $this->item?->getTitle();
	}

	/**
	 * Saves new title to Document.
	 *
	 * @param string $title New title.
	 * @return bool
	 */
	public function setTitle(string $title): bool
	{
		if ($this->item)
		{
			$this->item->setTitle($title);
			$result = $this->item->save();

			return $result->isSuccess();
		}

		return false;
	}

	public function afterAssignMembers(Document $document): Result
	{
		return new Result();
	}

	public function actualizeCompanyRequisites(Document $document): array
	{
		return [];
	}

	public function getCommunications(Member $member): array
	{
		return [];
	}
}
