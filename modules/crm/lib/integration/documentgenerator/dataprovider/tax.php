<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

\Bitrix\Main\Loader::includeModule('documentgenerator');

use Bitrix\DocumentGenerator\DataProvider\HashDataProvider;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;

class Tax extends HashDataProvider
{
	const MODE_TAX = 'TAX';
	const MODE_VAT = 'VAT';

	protected $mode = 'VAT';

	public function __construct($source, array $options = [])
	{
		parent::__construct($source, $options);
		if(isset($source['MODE']) && $source['MODE'] === static::MODE_TAX)
		{
			$this->mode = static::MODE_TAX;
		}
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		$fields = [
			'NAME' => [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_TAX_TITLE_TITLE'),
				'HIDE_ROW' => 'Y',
			],
			'TITLE' => [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_TAX_TITLE_NEW_TITLE'),
				'VALUE' => function()
				{
					if($this->isVatMode())
					{
						Loc::loadLanguageFile(__DIR__.'/productsdataprovider.php', DataProviderManager::getInstance()->getContext()->getRegionLanguageId());
						$name = Loc::getMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TAX_VAT_NAME', null, DataProviderManager::getInstance()->getContext()->getRegionLanguageId());
					}
					else
					{
						$name = $this->data['NAME'];
					}

					if($this->data['TAX_INCLUDED'] == 'Y')
					{
						$phrase = 'TAX_INCLUDED';
					}
					else
					{
						$phrase = 'TAX_NOT_INCLUDED';
					}
					if(\CCrmTax::isTaxMode())
					{
						$phrase .= '_NOT_VAT';
					}
					$title = DataProviderManager::getInstance()->getLangPhraseValue($this, $phrase);

					return str_replace('#NAME#', $name, $title);
				},
				'HIDE_ROW' => 'Y',
			],
			'RATE' => [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_TAX_RATE_TITLE'),
				'HIDE_ROW' => 'Y',
			],
			'NETTO' => [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_TAX_NETTO_TITLE'),
				'HIDE_ROW' => 'Y',
			],
			'VALUE' => [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_TAX_VALUE_TITLE'),
				'HIDE_ROW' => 'Y',
			],
			'BRUTTO' => [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_TAX_BRUTTO_TITLE'),
				'HIDE_ROW' => 'Y',
			],
			'TAX_INCLUDED' => [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_TAX_TAX_INCLUDED_TITLE'),
			]
		];

		return $fields;
	}

	/**
	 * @return string
	 */
	public function getLangPhrasesPath()
	{
		return Path::getDirectory(__FILE__).'/../phrases';
	}

	/**
	 * @return bool
	 */
	protected function isTaxMode()
	{
		return $this->mode === static::MODE_TAX;
	}

	/**
	 * @return bool
	 */
	protected function isVatMode()
	{
		return !$this->isTaxMode();
	}
}