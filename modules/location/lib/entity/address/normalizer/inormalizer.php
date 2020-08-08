<?php
namespace Bitrix\Location\Entity\Address\Normalizer;

/**
 * Interface for location names normalizers
 * Interface INormalizer
 * @package Bitrix\Location\Entity\Address\Normalizer
 */
interface INormalizer
{
	/**
	 * @param string $name Location name
	 * @return string
	 */
	public function normalize($name);
}