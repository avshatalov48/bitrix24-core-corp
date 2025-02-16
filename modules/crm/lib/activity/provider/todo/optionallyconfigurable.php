<?php

namespace Bitrix\Crm\Activity\Provider\ToDo;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

interface OptionallyConfigurable
{
	public function getProviderId(): string;
	public function getProviderTypeId(): string;
	public function getId(): ?int;
	public function getDescription(): string;
	public function getOwner(): ItemIdentifier;
	public function getCalendarEventId(): ?int;
	public function setCalendarEventId(int $id): self;
	public function setStorageElementIds($storageElementIds): self;
	public function setAdditionalFields(array $fields): self;
	public function getAdditionalFields(): array;
	public function getDeadline(): ?DateTime;
	public function getStorageElementIds(): ?array;
	public function load(int $id): ?static;
	public function save(array $options = []): Result;
	public function isValidProviderId(string $providerId): bool;
	public function setContext(Context $context): self;
	public function getContext(): ?Context;
}
