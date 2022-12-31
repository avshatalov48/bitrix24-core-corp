<?php

namespace Bitrix\Crm\Kanban\Sort;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;

final class Settings implements \JsonSerializable
{
	private array $supportedTypes;
	private string $currentType;

	public function __construct(array $supportedTypes, string $currentType)
	{
		$this->supportedTypes = array_filter($supportedTypes, [Type::class, 'isDefined']);
		if (empty($this->supportedTypes))
		{
			throw new ArgumentException('No valid supported types provided');
		}

		if (!Type::isDefined($currentType))
		{
			throw new ArgumentOutOfRangeException('currentType', Type::getAll());
		}
		if (!in_array($currentType, $this->supportedTypes, true))
		{
			throw new ArgumentException('$currentType is not supported');
		}

		$this->currentType = $currentType;
	}

	public function getSupportedTypes(): array
	{
		return $this->supportedTypes;
	}

	public function getCurrentType(): string
	{
		return $this->currentType;
	}

	public function isUserSortSupported(): bool
	{
		return ($this->currentType === Type::BY_ID);
	}

	public function jsonSerialize(): array
	{
		return [
			'supportedTypes' => $this->supportedTypes,
			'currentType' => $this->currentType,
		];
	}
}
