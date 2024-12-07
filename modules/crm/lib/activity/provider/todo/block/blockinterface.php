<?php

namespace Bitrix\Crm\Activity\Provider\ToDo\Block;

use Bitrix\Crm\Activity\Provider\ToDo\OptionallyConfigurable;
use Bitrix\Crm\Activity\Provider\ToDo\SaveConfig;

interface BlockInterface
{
	/*
	 @todo may be needed in the future for settings blocks
	public function update(OptionallyConfigurable $entity): bool;
	public function remove(OptionallyConfigurable $entity): bool;
	*/
	public function fetchSettings(): array;
	public function prepareEntity(OptionallyConfigurable $entity, bool $skipActiveSectionCheck = false): void;
	public function getOptions(OptionallyConfigurable $entity): array;
	public function prepareEntityBefore(OptionallyConfigurable $entity): SaveConfig;
}
