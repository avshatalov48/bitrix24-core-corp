<?php

namespace Bitrix\Location\Repository\Location\Capability;

use Bitrix\Location\Entity\Generic\Collection;
use Bitrix\Location\Common\Point;

/**
 * Interface IFindByPoint
 * @package Bitrix\Location\Repository
 */
interface IFindByPoint
{
	/**
	 * @param Point $point
	 * @return Collection|bool
	 */
	public function findByPoint(Point $point, string $languageId);
}