<?
namespace Bitrix\Location\Entity\Address\Normalizer;

/**
 * Erase spaces from name
 * Class SpaceNormalizer
 * @package Bitrix\Location\Entity\Address\Normalizer
 */
class SpaceNormalizer implements INormalizer
{
	/**
	 * @inheritdoc
	 */
	public function normalize($string)
	{
		return str_replace(' ', '', $string);
	}
}