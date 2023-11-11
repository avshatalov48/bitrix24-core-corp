<?php
namespace Bitrix\Crm\Integration\Numerator;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Numerator\Generator\Contract\DynamicConfigurable;
use Bitrix\Main\Numerator\Generator\NumberGenerator;

/**
 * Class QuoteIdNumberGenerator
 * @package Bitrix\Sale\Integration\Numerator
 */
class QuoteIdNumberGenerator extends NumberGenerator implements DynamicConfigurable
{
	protected $quoteId;

	const TEMPLATE_WORD_QUOTE_ID = "QUOTE_ID";

	/** @inheritdoc */
	public static function getTemplateWordsForParse()
	{
		return [
			static::getPatternFor(static::TEMPLATE_WORD_QUOTE_ID),
		];
	}

	/** @inheritdoc */
	public static function getTemplateWordsSettings()
	{
		return [
			static::getPatternFor(static::TEMPLATE_WORD_QUOTE_ID)
			=> Loc::getMessage('BITRIX_CRM_INTEGRATION_NUMERATOR_QUOTEIDNUMBERGENERATOR_WORD_QUOTE_ID_MSGVER_1'),
		];
	}

	/**
	 * @return string
	 */
	public static function getAvailableForType()
	{
		return REGISTRY_TYPE_CRM_QUOTE;
	}

	/** @inheritdoc */
	public function parseTemplate($template)
	{
		if (!is_null($this->quoteId))
		{
			return str_replace(self::getPatternFor(static::TEMPLATE_WORD_QUOTE_ID), $this->quoteId, $template);
		}
		return $template;
	}

	/**
	 * @param array $config
	 */
	public function setDynamicConfig($config)
	{
		if (is_array($config) && array_key_exists('QUOTE_ID', $config))
		{
			$this->quoteId = $config['QUOTE_ID'];
		}
	}
}