<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Intranet\User;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Result;

abstract class AbstractSettings implements SettingsInterface
{
	public const TYPE = 'abstract';

	protected array $data;

	public function __construct(array $data = [])
	{
		$this->data = $data;
	}

	public function validate(): ErrorCollection
	{
		return new ErrorCollection();
	}

	public function getType(): string
	{
		return static::TYPE;
	}

	public abstract function save(): Result;

	public abstract function get(): SettingsInterface;

	public function toArray(): array
	{
		return $this->data;
	}

	public function set(array $data): SettingsInterface
	{
		return new static($data);
	}

	public function hasAccess(User $user, string $fieldName): bool
	{
		return true;
	}

	public static function isAvailable(): bool
	{
		return true;
	}
}