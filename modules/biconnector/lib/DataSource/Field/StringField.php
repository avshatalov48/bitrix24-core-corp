<?php

namespace Bitrix\BIConnector\DataSource\Field;

use Bitrix\BIConnector\DataSource\DatasetField;

class StringField extends DatasetField
{
	protected const TYPE = 'string';

	protected ?array $dictionary = null;
	protected ?string $dictionaryDefaultCase = null;

	/**
	 * Set dictionary for switch value from list. It`s false by default
	 *
	 * @param array $dictionary
	 * @return $this
	 */
	public function setDictionary(array $dictionary, string $dictionaryDefaultCase = null): static
	{
		$this->dictionary = $dictionary;
		$this->dictionaryDefaultCase = $dictionaryDefaultCase;
		return $this;
	}

	/**
	 * Get sql switch for selection by dictionary
	 *
	 * @param string $fieldName
	 * @return string
	 */
	public function mapDictionaryToSqlCase(string $fieldName): string
	{
		ksort($this->dictionary);
		$helper = $this->dataset->getSqlHelper();
		$dictionaryForSql = [];
		foreach ($this->dictionary as $id => $value)
		{
			$fieldName = $helper->quote($fieldName);
			$sqlId = $helper->forSql($id);
			$sqlValue = $helper->forSql($value);
			$dictionaryForSql[] = "when {$fieldName} = '{$sqlId}' then '{$sqlValue}'";
		}

		$defaultValue =
			$this->dictionaryDefaultCase === null
				? 'null' :
				"'{$helper->forSql($this->dictionaryDefaultCase)}'"
		;

		return 'case ' . implode("\n", $dictionaryForSql) . ' else '. $defaultValue .' end';
	}

	/**
	 * @return string
	 */
	protected function getName(): string
	{
		if (!empty($this->name))
		{
			return $this->name;
		}

		if (!empty($this->dictionary) && $this->dataset)
		{
			$fieldName = $this->dataset->getAliasFieldName($this->code);

			return $this->mapDictionaryToSqlCase($fieldName);
		}

		return parent::getName();
	}
}
