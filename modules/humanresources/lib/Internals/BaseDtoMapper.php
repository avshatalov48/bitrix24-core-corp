<?php

namespace Bitrix\HumanResources\Internals;

use Bitrix\HumanResources\Contract;
use TypeError;

/**
 * @template D of BaseDto
 * @template I of Contract\Item
 * @implements Contract\DtoMapper<D, I>
*/
abstract class BaseDtoMapper implements Contract\DtoMapper
{
	public function mapToItem($dto)
	{
		$this->validateDto($dto);
		return $this->mapToItemInner($dto);
	}

	public function mapToDto($item)
	{
		$this->validateItem($item);
		return $this->mapToDtoInner($item);
	}

	/**
	 * @param D $dto
	 * @return I
	 */
	protected abstract function mapToItemInner($dto);

	/**
	 * @param I $item
	 * @return D
	 */
	protected abstract function mapToDtoInner($item);

	protected abstract function getItemClass(): string;
	protected abstract function getDtoClass(): string;

	private function validateDto(BaseDto $dto): void
	{
		$dtoClass = $this->getDtoClass();
		if (!$dto instanceof $dtoClass)
		{
			throw new TypeError('Dto must be instance of ' . $dtoClass);
		}
	}

	private function validateItem(Contract\Item $item): void
	{
		$itemClass = $this->getItemClass();
		if (!$item instanceof $itemClass)
		{
			throw new TypeError('Item must be instance of ' . $itemClass);
		}
	}
}