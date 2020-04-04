<?php
namespace Bitrix\Crm\Automation;

/**
 * Class Result
 *
 * @package Bitrix\Crm\Automation
 */
class Result extends \Bitrix\Main\Result
{
	/**
	 * Get conversion result.
	 *
	 * @return Converter\Result|null
	 */
	public function getConversionResult()
	{
		return isset($this->data['conversionResult']) ? $this->data['conversionResult'] : null;
	}

	/**
	 * Set conversion result.
	 *
	 * @param Converter\Result $conversionResult Conversion result.
	 * @return void
	 */
	public function setConversionResult(Converter\Result $conversionResult)
	{
		$this->data['conversionResult'] = $conversionResult;
	}
}