<?php

namespace Bitrix\HumanResources\Contract;

use Bitrix\HumanResources\Internals\BaseDto;
use Bitrix\HumanResources\Contract;

/**
 * @template D of BaseDto
 * @template I of Contract\Item
 */
interface DtoMapper
{
	/**
	 * @param D $dto
	 * @return I
	 */
	public function mapToItem($dto);

	/**
	 * @param I $item
	 * @return D
	 */
	public function mapToDto($item);
}