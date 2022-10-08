<?php

namespace Bitrix\Crm\Item;

use Bitrix\Crm\Item;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

/**
 * @internal
 *
 * Do not use or implement this interface. For internal system use only. Is not covered by backwards compatibility.
 *
 * Still in active development phase and is subject to change.
 */
interface FieldImplementation
{
	/**
	 * Returns list of common field names that are handled by this implementation
	 *
	 * @return string[]
	 */
	public function getHandledFieldNames(): array;

	/**
	 * @return mixed
	 */
	public function get(string $commonFieldName);

	public function set(string $commonFieldName, $value): void;

	public function isChanged(string $commonFieldName): bool;

	/**
	 * @return mixed
	 */
	public function remindActual(string $commonFieldName);

	public function reset(string $commonFieldName): void;

	public function unset(string $commonFieldName): void;

	public function afterSuccessfulItemSave(Item $item, EntityObject $entityObject): void;

	public function save(): Result;

	/**
	 * Returns list of common field names that are can be externalized from this implementation
	 * Generally is a subset of getHandledList
	 *
	 * @return string[]
	 */
	public function getExternalizableFieldNames(): array;

	/**
	 * Serialize value to an external data format if needed. In basic implementation can simply return $value
	 *
	 * @param string $commonFieldName
	 * @param $value - value that was received from methods of this object: get for Values::ALL|Values::CURRENT,
	 * and remindActual for Values::ACTUAL
	 * @param int $valuesType - const of Values
	 *
	 * @see Values
	 *
	 * @return mixed
	 */
	public function transformToExternalValue(string $commonFieldName, $value, int $valuesType);

	/**
	 * @param Array<string, mixed> $externalValues - [$commonFieldName => $externalValue]
	 * @return void
	 */
	public function setFromExternalValues(array $externalValues): void;

	public function afterItemClone(Item $item, EntityObject $entityObject): void;
}
