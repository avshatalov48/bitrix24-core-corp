<?php

namespace Bitrix\DocumentGenerator\DataProvider;

use Bitrix\DocumentGenerator\DataProvider;
use Bitrix\DocumentGenerator\Model\SpreadsheetTable;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\Main\Localization\Loc;

class Spreadsheet extends DataProvider //implements Nameable
{
	protected $data;

	public function __construct($source, array $options = [])
	{
		parent::__construct($source, $options);
		if($source instanceof ArrayDataProvider)
		{
			$this->data = $source;
		}
		Loc::loadLanguageFile(__FILE__);
	}

	/**
	 * Returns list of value names for this entity.
	 *
	 * @return array
	 */
	public function getFields()
	{
		return [
			'CONTENT' => [
				'TITLE' => Loc::getMessage('DOCGEN_DATAPROVIDER_SPREADSHEET_CONTENT_TITLE'),
				'VALUE' => function()
				{
					return $this->getContent();
				}
			],
		];
	}

	/**
	 * @return string
	 */
	protected function getContent()
	{
		if(!$this->isLoaded())
		{
			return null;
		}
		$result = $columnNames = [];
		$columns = SpreadsheetTable::getList(['filter' => ['FIELD_ID' => $this->getFieldId()]])->fetchAll();
		if($columns)
		{
			foreach($columns as $column)
			{
				$columnNames[] = $column['ENTITY_NAME'];
			}
		}
		foreach($this->data as $index => $provider)
		{
			$result['DATA'][$index] = [];
			foreach($columnNames as $columnName)
			{
				/** @var DataProvider $provider */
				$result['DATA'][$index][] = $provider->getValue($columnName);
			}
		}
	}

	public static function getLangName()
	{
		return 'Настраиваемая таблица';
	}

	protected function getFieldId()
	{
		return $this->options['FIELD_ID'];
	}
}