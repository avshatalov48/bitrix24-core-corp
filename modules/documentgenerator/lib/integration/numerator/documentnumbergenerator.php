<?php

namespace Bitrix\DocumentGenerator\Integration\Numerator;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Numerator\Generator\Contract\DynamicConfigurable;
use Bitrix\Main\Numerator\Generator\NumberGenerator;

/**
 * Class DocumentNumberGenerator
 * @package Bitrix\DocumentGenerator\Integration\Numerator
 */
class DocumentNumberGenerator extends NumberGenerator implements DynamicConfigurable
{
	protected $selfId;
	protected $selfCompanyId;
	protected $clientId;

	const TEMPLATE_WORD_SELF_ID         = "SELF_ID";
	const TEMPLATE_WORD_SELF_COMPANY_ID = "SELF_COMPANY_ID";
	const TEMPLATE_WORD_CLIENT_ID       = "CLIENT_ID";

	/** @inheritdoc */
	public static function getTemplateWordsForParse()
	{
		return [
			static::getPatternFor(static::TEMPLATE_WORD_CLIENT_ID),
			static::getPatternFor(static::TEMPLATE_WORD_SELF_ID),
			static::getPatternFor(static::TEMPLATE_WORD_SELF_COMPANY_ID),
		];
	}

	/**
	 * @return string
	 */
	public static function getAvailableForType()
	{
		return 'DOCUMENT';
	}

	/*** @inheritdoc */
	public function setDynamicConfig($context)
	{
		if ($context instanceof DocumentNumerable)
		{
			$this->selfCompanyId = $context->getSelfCompanyId();
			$this->selfId = $context->getSelfId();
			$this->clientId = $context->getClientId();
		}
	}

	/** @inheritdoc */
	public static function getTemplateWordsSettings()
	{
		return [
			static::getPatternFor(static::TEMPLATE_WORD_SELF_ID)         =>
				Loc::getMessage('BITRIX_DOCUMENTGENERATOR_INTEGRATION_NUMERATOR_DOCUMENTNUMBERGENERATOR_WORD_SELF_ID'),
			static::getPatternFor(static::TEMPLATE_WORD_SELF_COMPANY_ID) =>
				Loc::getMessage('BITRIX_DOCUMENTGENERATOR_INTEGRATION_NUMERATOR_DOCUMENTNUMBERGENERATOR_WORD_SELF_COMPANY_ID'),
			static::getPatternFor(static::TEMPLATE_WORD_CLIENT_ID)       =>
				Loc::getMessage('BITRIX_DOCUMENTGENERATOR_INTEGRATION_NUMERATOR_DOCUMENTNUMBERGENERATOR_WORD_CLIENT_ID'),
		];
	}

	/** @inheritdoc */
	public function parseTemplate($template)
	{
		$template = str_replace(self::getPatternFor(self::TEMPLATE_WORD_CLIENT_ID), $this->clientId, $template);
		$template = str_replace(self::getPatternFor(self::TEMPLATE_WORD_SELF_COMPANY_ID), $this->selfCompanyId, $template);
		$template = str_replace(self::getPatternFor(self::TEMPLATE_WORD_SELF_ID), $this->selfId, $template);
		return $template;
	}
}