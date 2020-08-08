<?
namespace Bitrix\Location\Entity\Address\Normalizer;

/**
 * Class CommonNormalizer
 * @package Bitrix\Location\Entity\Address\Normalizer
 * Delete all except letters and spaces, trim and converts to uppercase.
 */
class CommonNormalizer implements INormalizer
{
	/**
	 * @inheritdoc
	 */
	public function normalize($string)
	{
		$result = $string;
		$result = preg_replace('/([^\w\s]|_)/i'.BX_UTF_PCRE_MODIFIER, ' ', $result);
		$result = preg_replace('/\s+/i'.BX_UTF_PCRE_MODIFIER, ' ', $result);
		$result = trim($result);
		return ToUpper($result);
	}
}