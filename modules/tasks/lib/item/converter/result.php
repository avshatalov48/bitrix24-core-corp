<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Item\Converter;

use Bitrix\Tasks\Item\Converter;

final class Result extends \Bitrix\Tasks\Item\Result
{
	/** @var Converter */
	protected $converter = null;

	public function setConverter($converter)
	{
		$this->converter = $converter;
	}

	public function abortConversion()
	{
		$instance = $this->instance;
		$converter = $this->converter;
		if($instance && $converter)
		{
			return $converter->abortConversion($instance);
		}

		$result = new \Bitrix\Tasks\Item\Result();
		$result->addError('NO_CONVERSION', 'No conversion performed before');

		return $result;
	}
}