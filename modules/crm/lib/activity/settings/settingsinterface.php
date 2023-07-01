<?php

namespace Bitrix\Crm\Activity\Settings;

interface SettingsInterface
{
	public function apply(): bool;
	/*
	 @todo may be needed in the future for settings blocks
	public function update(OptionallyConfigurable $entity): bool;
	public function remove(OptionallyConfigurable $entity): bool;
	*/
	public function fetchSettings(): array;
	public function prepareEntity(OptionallyConfigurable $entity, bool $skipActiveSectionCheck = false): void;
	public function getOptions(OptionallyConfigurable $entity): array;
}
