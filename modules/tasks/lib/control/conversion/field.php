<?php

namespace Bitrix\Tasks\Control\Conversion;

use Bitrix\Tasks\Control\Conversion\Factory\FieldFactoryInterface;
use Bitrix\Tasks\Control\Conversion\Fields\Accomplice;
use Bitrix\Tasks\Control\Conversion\Fields\Auditor;
use Bitrix\Tasks\Control\Conversion\Fields\DependsOn;
use Bitrix\Tasks\Control\Conversion\Fields\Group;
use Bitrix\Tasks\Control\Conversion\Fields\Originator;
use Bitrix\Tasks\Control\Conversion\Fields\ParentTask;
use Bitrix\Tasks\Control\Conversion\Fields\OrdinaryField;
use Bitrix\Tasks\Control\Conversion\Fields\Tag;
use Bitrix\Tasks\Manager;

abstract class Field implements FieldFactoryInterface
{
	public const SUB_ENTITY_KEY = '';
	public const SUB_ENTITY_PREFIX = Manager::SE_PREFIX;

	private static array $convertMap = [
		Accomplice::SUB_ENTITY_KEY => Accomplice::class,
		Auditor::SUB_ENTITY_KEY => Auditor::class,
		DependsOn::SUB_ENTITY_KEY => DependsOn::class,
		Group::SUB_ENTITY_KEY => Group::class,
		Originator::SUB_ENTITY_KEY => Originator::class,
		ParentTask::SUB_ENTITY_KEY => ParentTask::class,
		Tag::SUB_ENTITY_KEY => Tag::class,
	];

	protected string $key;
	protected mixed $value;

	abstract public function convertValue(): mixed;

	abstract public function getNormalizedKey(): string;

	public function __construct(string $key, mixed $value)
	{
		$this->key = $key;
		$this->value = $value;
	}

	public static function getSubEntityKey(): string
	{
		return static::SUB_ENTITY_KEY;
	}

	public static function createByData(string $key, mixed $value): static
	{
		$class = static::getFieldClass($key);
		return new $class($key, $value);
	}

	private static function getFieldClass(string $key): string
	{
		return static::needToConvert($key)
			? static::$convertMap[$key]
			: OrdinaryField::class;
	}

	private static function needToConvert(string $key): bool
	{
		return
			str_starts_with($key, static::SUB_ENTITY_PREFIX)
			&& array_key_exists($key, static::$convertMap);
	}

	public function isSubEntity(): bool
	{
		return str_starts_with($this->key, static::SUB_ENTITY_PREFIX);
	}
}