<?php

namespace Bitrix\Sign\Document\Entity;

use Bitrix\Crm;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Document;
use Bitrix\Sign\Document\Member;

abstract class Dummy
{
	/**
	 * Class constructor.
	 *
	 * @param int $id Entity id.
	 */
	abstract public function __construct(int $id);

	/**
	 * Creates new entity and returns its id.
	 * @return int|null
	 */
	abstract public static function create(bool $checkPermission = true): ?int;

	abstract public static function getEntityTypeId(): int;

	/**
	 * Returns current entity's id.
	 * @return int
	 */
	abstract public function getId(): int;

	/**
	 * Returns current entity's number.
	 * @return int|string
	 */
	abstract public function getNumber();

	/**
	 * Refreshes entity number and returns new value.
	 * @return string|int|null
	 */
	abstract public function refreshNumber();

	/**
	 * Returns current entity's title.
	 * @return string|null
	 */
	abstract public function getTitle(): ?string;

	/**
	 * Saves new title to Document.
	 *
	 * @param string $title New title.
	 * @return bool
	 */
	abstract public function setTitle(string $title): bool;

	/**
	 * Returns current entity's stage.
	 * @return string|null
	 */
	abstract public function getStageId(): ?string;

	/**
	 * Returns entity contact's ids.
	 * @return int[]
	 */
	abstract public function getContactsIds(): array;

	/**
	 * Returns entity base company id.
	 * @return int
	 */
	abstract public function getCompanyId(): int;

	/**
	 * Returns entity base company title.
	 * @return string|null
	 */
	abstract public function getCompanyTitle(): ?string;

	/**
	 * Calls after member was assigned to doc.
	 *
	 * @param Document $document Document instance.
	 *
	 * @return Result
	 */
	abstract public function afterAssignMembers(Document $document): Result;

	/**
	 * Actualize company requisites.
	 *
	 * @param Document $document Document.
	 *
	 * @return void
	 */
	abstract public function actualizeCompanyRequisites(Document $document): array;

	/**
	 * Returns communications list for member instance.
	 *
	 * @param Member $member Member instance.
	 *
	 * @return array
	 */
	abstract public function getCommunications(Member $member): array;

	public static function getEntityDetailUrlId(): Uri | string | null
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		return Crm\Service\Container::getInstance()->getRouter()
				->getItemDetailUrl(static::getEntityTypeId())
		;
	}
}
