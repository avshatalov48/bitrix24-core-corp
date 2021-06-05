<?php
namespace Bitrix\Crm\Integration\Numerator;

use Bitrix\Crm\QuoteTable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Numerator\Generator\Contract\DynamicConfigurable;
use Bitrix\Main\Numerator\Generator\NumberGenerator;

/**
 * Class QuoteUserQuotesNumberGenerator
 * @package Bitrix\Crm\Integration\Numerator
 */
class QuoteUserQuotesNumberGenerator extends NumberGenerator implements DynamicConfigurable
{
	protected $quoteId;

	const TEMPLATE_WORD_USER_ID_QUOTES_COUNT = "USER_ID_QUOTES_COUNT";

	/** @inheritdoc */
	public static function getTemplateWordsForParse()
	{
		return [
			static::getPatternFor(static::TEMPLATE_WORD_USER_ID_QUOTES_COUNT),
		];
	}

	/*** @inheritdoc */
	public static function getTemplateWordsSettings()
	{
		return [
			static::getPatternFor(static::TEMPLATE_WORD_USER_ID_QUOTES_COUNT) =>
				Loc::getMessage('BITRIX_CRM_INTEGRATION_NUMERATOR_QUOTEUSERQUOTESNUMBERGENERATOR_WORD_USER_ID_QUOTES_COUNT'),
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
		if (mb_strpos($template, self::TEMPLATE_WORD_USER_ID_QUOTES_COUNT) !== false)
		{
			$value = '';
			$assignedById = QuoteTable::query()
				->addSelect('ASSIGNED_BY_ID')
				->where('ID', $this->quoteId)
				->exec()
				->fetch();

			if ($assignedById)
			{
				$userId = intval($assignedById['ASSIGNED_BY_ID']);

				$count = QuoteTable::query()
					->addSelect('QUOTES_COUNT')
					->registerRuntimeField(
						new ExpressionField(
							'QUOTES_COUNT',
							'COUNT(ID)'
						)
					)
					->where('ASSIGNED_BY_ID', $userId)
					->addGroup('ASSIGNED_BY_ID')
					->exec()
					->fetch();

				if ($count)
				{
					$numID = ((int)$count["QUOTES_COUNT"] > 0) ? (int)$count["QUOTES_COUNT"] : 1;
					$value = $userId . "_" . $numID;
				}
				else
				{
					$value = $userId . "_1";
				}
			}

			$template = str_replace(self::getPatternFor(self::TEMPLATE_WORD_USER_ID_QUOTES_COUNT), $value, $template);
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
