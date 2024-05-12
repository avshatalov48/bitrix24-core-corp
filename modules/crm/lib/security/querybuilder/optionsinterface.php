<?php

namespace Bitrix\Crm\Security\QueryBuilder;

interface OptionsInterface
{
	public function getOperations(): array;

	public function getAliasPrefix(): string;

	public function isReadAllAllowed(): bool;

	public function canSkipCheckOtherEntityTypes(): bool;

	/** @deprecated used only in compatible mode */
	public function getLimitByIds(): ?array;

	/** @deprecated used only in compatible mode */
	public function needUseDistinctUnion(): bool;
}