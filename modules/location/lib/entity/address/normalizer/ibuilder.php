<?php

namespace Bitrix\Location\Entity\Address\Normalizer;

/**
 * For building locations names normalizers
 * Interface IBuilder
 * @package Bitrix\Location\Entity\Address\Normalizer
 */
interface IBuilder
{
	/**
	 * @param string $lang Language id.
	 * @return INormalizer
	 */
	public static function build($lang);
}