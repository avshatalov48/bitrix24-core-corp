<?php
namespace Bitrix\Crm\Integration\Numerator;

use Bitrix\Main\Numerator\Generator\Contract\DynamicConfigurable;
use Bitrix\Main\Numerator\Generator\NumberGenerator;

/**
 * Class QuoteNumberGenerator
 * @package Bitrix\Crm\Integration\Numerator
 */
class QuoteNumberGenerator extends NumberGenerator implements DynamicConfigurable
{
	protected $orderId;

	const TEMPLATE_WORD_USER_ID_QUOTE_ID = "USER_ID_QUOTE_ID";

	/** @inheritdoc */
	public static function getTemplateWordsForParse()
	{
		return [
			static::getPatternFor(static::TEMPLATE_WORD_USER_ID_QUOTE_ID),
		];
	}

	/**
	 * @return string
	 */
	public static function getAvailableForType()
	{
		return 'QUOTE';
	}

	/** @inheritdoc */
	public function parseTemplate($template)
	{
		global $DB;
		$dbres = $DB->Query("SELECT USER_ID FROM b_sale_order WHERE ID = '" . $this->orderId . "'", true);
		$value = '';
		if ($arRes = $dbres->GetNext())
		{
			$userID = $arRes["USER_ID"];

			switch (strtolower($DB->type))
			{
				case "mysql":
					$strSql = "SELECT MAX(CAST(SUBSTRING(ACCOUNT_NUMBER, LENGTH('" . $userID . "_') + 1) as UNSIGNED)) as NUM_ID FROM b_sale_order WHERE ACCOUNT_NUMBER LIKE '" . $userID . "\_%'";
					break;
				case "oracle":
					$strSql = "SELECT MAX(CAST(SUBSTR(ACCOUNT_NUMBER, LENGTH('" . $userID . "_') + 1) as NUMBER)) as NUM_ID FROM b_sale_order WHERE ACCOUNT_NUMBER LIKE '" . $userID . "_%'";
					break;
				case "mssql":
					$strSql = "SELECT MAX(CAST(SUBSTRING(ACCOUNT_NUMBER, LEN('" . $userID . "_') + 1, LEN(ACCOUNT_NUMBER)) as INT)) as NUM_ID FROM b_sale_order WHERE ACCOUNT_NUMBER LIKE '" . $userID . "_%'";
					break;
			}

			$dbres = $DB->Query($strSql, true);
			if ($arRes = $dbres->GetNext())
			{
				$numID = (intval($arRes["NUM_ID"]) > 0) ? $arRes["NUM_ID"] + 1 : 1;
				$value = $userID . "_" . $numID;
			}
			else
			{
				$value = $userID . "_1";
			}
		}

		$template = str_replace(self::getPatternFor(self::TEMPLATE_WORD_USER_ID_QUOTE_ID), $value, $template);

		return $template;
	}

	/**
	 * @param array $config
	 */
	public function setDynamicConfig($config)
	{
		$this->quoteId = $config['QUOTE_ID'];
	}
}