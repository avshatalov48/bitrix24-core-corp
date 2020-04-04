<?php
namespace Bitrix\Crm\Integration\Numerator;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Numerator\Generator\Contract\DynamicConfigurable;
use Bitrix\Main\Numerator\Generator\NumberGenerator;

/**
 * Class InvoiceIdNumberGenerator
 * @package Bitrix\Sale\Integration\Numerator
 */
class InvoiceIdNumberGenerator extends NumberGenerator implements DynamicConfigurable
{
	protected $invoiceId;

	const TEMPLATE_WORD_INVOICE_ID = 'INVOICE_ID';

	/** @inheritdoc */
	public static function getTemplateWordsForParse()
	{
		return [
			static::getPatternFor(static::TEMPLATE_WORD_INVOICE_ID),
		];
	}

	/** @inheritdoc */
	public static function getTemplateWordsSettings()
	{
		return [
			static::getPatternFor(static::TEMPLATE_WORD_INVOICE_ID)
			=> Loc::getMessage('BITRIX_CRM_INTEGRATION_NUMERATOR_INVOICEIDNUMBERGENERATOR_WORD_INVOICE_ID'),
		];
	}

	/**
	 * @return string
	 */
	public static function getAvailableForType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/** @inheritdoc */
	public function parseTemplate($template)
	{
		if (!is_null($this->invoiceId))
		{
			return str_replace(self::getPatternFor(static::TEMPLATE_WORD_INVOICE_ID), $this->invoiceId, $template);
		}
		return $template;
	}

	/**
	 * @param array $config
	 */
	public function setDynamicConfig($config)
	{
		if (is_array($config) && array_key_exists('ORDER_ID', $config))
		{
			$this->invoiceId = $config['ORDER_ID'];
		}
	}
}