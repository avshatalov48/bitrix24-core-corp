<?php

namespace Bitrix\Crm\Security\QueryBuilder;

use Bitrix\Crm\Security\QueryBuilder\Result\ResultOption;
use Bitrix\Crm\Service\UserPermissions;

class QueryBuilderOptions implements OptionsInterface
{
	private ResultOption $resultOption;

	private string $aliasPrefix;

	private array $operations = [UserPermissions::OPERATION_READ];

	private bool $readAllAllowed;

	private bool $skipCheckOtherEntityTypes;

	/** @deprecated used only in compatible mode */
	private ?array $limitByIds;

	/** @deprecated used only in compatible mode */
	private bool $useDistinctUnion;

	public function __construct(
		ResultOption $resultType,
		?array $operations = null,
		string $aliasPrefix = '',
		bool $readAllAllowed = false,
		bool $skipCheckOtherEntityTypes = false,
		?array $limitByIds = null,
		bool $useDistinctUnion = false,
	)
	{
		$this->resultOption = $resultType;
		$this->aliasPrefix = $aliasPrefix;
		$this->readAllAllowed = $readAllAllowed;
		$this->skipCheckOtherEntityTypes = $skipCheckOtherEntityTypes;
		$this->limitByIds = $limitByIds;
		$this->useDistinctUnion = $useDistinctUnion;

		if ($operations !== null)
		{
			$this->operations = $operations;
		}
	}

	public function getOperations(): array
	{
		return $this->operations;
	}

	public function getResult(): ResultOption
	{
		return $this->resultOption;
	}

	public function getAliasPrefix(): string
	{
		return $this->aliasPrefix;
	}

	public function isReadAllAllowed(): bool
	{
		return $this->readAllAllowed;
	}

	/**
	 * @return array|null
	 * @deprecated used only in compatible mode
	 */
	public function getLimitByIds(): ?array
	{
		return $this->limitByIds;
	}

	/**
	 * @return bool
	 * @deprecated used only in compatible mode
	 */
	public function needUseDistinctUnion(): bool
	{
		return $this->useDistinctUnion;
	}

	public function canSkipCheckOtherEntityTypes(): bool
	{
		return $this->skipCheckOtherEntityTypes;
	}

}