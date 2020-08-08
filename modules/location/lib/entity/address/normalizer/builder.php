<?php

namespace Bitrix\Location\Entity\Address\Normalizer;

/**
 * Class Builder
 * @package Bitrix\Location\Entity\Address\Normalizer
 * Build location name normalizer
 */
class Builder implements IBuilder
{
	/**
	 * @param string $lang Language identifier
	 * @return INormalizer
	 */
	public static function build($lang)
	{
		return new Normalizer([
			new CommonNormalizer(),
			new LanguageNormalizer($lang),
			new SpaceNormalizer()
		]);
	}
}