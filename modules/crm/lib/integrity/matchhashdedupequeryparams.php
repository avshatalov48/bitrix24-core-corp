<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Main\SystemException;

class MatchHashDedupeQueryParams
{
	protected string $contextId;
	protected int $typeId;
	protected int $entityTypeId;
	protected int $userId;
	protected string $scope;
	protected bool $enablePermissionCheck;
	protected string $maxHashDateModify;

	protected array $paramNameList = [
		'contextId',
		'typeId',
		'entityTypeId',
		'userId',
		'scope',
		'enablePermissionCheck',
		'maxHashDateModify',
	];

	public function __construct(
		string $contextId,
		int $typeId,
		int $entityTypeId,
		int $userId,
		string $scope = '',
		bool $enablePermissionCheck = false,
		string $maxHashDateModify = ''
	)
	{
		foreach ($this->paramNameList as $paramName)
		{
			$this->$paramName = $$paramName;
		}
	}

	public function getHash()
	{
		$hashDataVersion = '1';
		$hashData = $hashDataVersion;
		foreach ($this->paramNameList as $paramName)
		{
			$value = $this->$paramName;
			if (is_bool($value))
			{
				$value = $value ? 'Y' : 'N';
			}
			$hashData .= '|' . $value;
		}

		return substr(hash('SHA256', $hashData), 0, 16);
	}

	public function getContextId(): string
	{
		return $this->contextId;
	}

	public function getTypeId(): int
	{
		return $this->typeId;
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getScope(): string
	{
		return $this->scope;
	}

	public function isEnablePermissionCheck(): bool
	{
		return $this->enablePermissionCheck;
	}

	public function getMaxHashDateModify(): string
	{
		return $this->maxHashDateModify;
	}
}
