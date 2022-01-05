<?php

namespace Bitrix\DocumentGenerator\DataProvider;

use Bitrix\DocumentGenerator\DataProvider;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\DocumentGenerator\Template;
use Bitrix\Main\Localization\Loc;

class Document extends DataProvider
{
	/**
	 * Returns list of value names for this Provider.
	 *
	 * @return array
	 */
	public function getFields()
	{
		$fields = [
			'DOCUMENT_CREATE_TIME' => [
				'TITLE' => Loc::getMessage('DOCGEN_DATAPROVIDER_DOCUMENT_CREATE_TIME_TITLE'),
				'VALUE' => [$this, 'getCreateTime'],
				'TYPE' => static::FIELD_TYPE_DATE,
			],
			'DOCUMENT_TITLE' => [
				'TITLE' => Loc::getMessage('DOCGEN_DATAPROVIDER_DOCUMENT_TITLE_TITLE'),
				'VALUE' => [$this, 'getTitle'],
				'REQUIRED' => 'Y',
			],
			'DOCUMENT_NUMBER' => [
				'TITLE' => Loc::getMessage('DOCGEN_DATAPROVIDER_DOCUMENT_NUMBER_TITLE'),
				'VALUE' => [$this, 'getNumber'],
				'REQUIRED' => 'Y',
			],
		];

		$dataProvider = $this->getDataProvider();
		if($dataProvider instanceof Nameable)
		{
			$fields[Template::MAIN_PROVIDER_PLACEHOLDER] = [
				'TITLE' => $dataProvider->getLangName(),
				'PROVIDER' => get_class($dataProvider),
			];
		}
		else
		{
			$fields[Template::MAIN_PROVIDER_PLACEHOLDER] = [
				'TITLE' => Loc::getMessage('DOCGEN_DATAPROVIDER_DOCUMENT_SOURCE_TITLE'),
			];
		}

		return $fields;
	}

	/**
	 * @return bool
	 */
	public function isLoaded()
	{
		return ($this->source instanceof \Bitrix\DocumentGenerator\Document);
	}

	/**
	 * @param int $userId
	 * @return boolean
	 */
	public function hasAccess($userId)
	{
		return ($this->isLoaded() && $this->getDocument()->hasAccess($userId));
	}

	/**
	 * @return DataProvider|false
	 */
	protected function getDataProvider()
	{
		$provider = false;
		if(isset($this->data['PROVIDER']))
		{
			$provider = $this->data['PROVIDER'];
		}
		elseif(isset($this->options['PROVIDER']))
		{
			$provider = $this->options['PROVIDER'];
		}
		if($provider)
		{
			if(!is_object($provider))
			{
				$provider = new $provider(' ');
			}
		}

		return $provider;
	}

	/**
	 * @return \Bitrix\DocumentGenerator\Document
	 */
	protected function getDocument()
	{
		if($this->isLoaded())
		{
			return $this->source;
		}

		return null;
	}

	public function getCreateTime()
	{
		if($this->isLoaded())
		{
			return $this->getDocument()->getCreateTime();
		}

		return false;
	}

	public function getNumber()
	{
		if($this->isLoaded())
		{
			return $this->getDocument()->getNumber();
		}

		return false;
	}

	public function getTitle()
	{
		if($this->isLoaded())
		{
			return $this->getDocument()->getTitle();
		}

		return false;
	}
}
