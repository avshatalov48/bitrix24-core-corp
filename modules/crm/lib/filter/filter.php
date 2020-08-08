<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Crm;

class Filter
{
	/** @var string */
	protected $ID = '';
	/** @var DataProvider|null */
	protected $entityDataProvider = null;
	/** @var DataProvider[]|null */
	protected $extraProviders = null;

	/** @var array|null  */
	protected $params = null;

	/** @var Field[]|null */
	protected $fields = null;

	function __construct($ID, DataProvider $entityDataProvider, array $extraDataProviders = null, array $params = null)
	{
		$this->ID = $ID;
		$this->entityDataProvider = $entityDataProvider;

		$this->extraProviders = array();
		if(is_array($extraDataProviders))
		{
			foreach($extraDataProviders as $dataProvider)
			{
				if($dataProvider instanceof DataProvider)
				{
					$this->extraProviders[] = $dataProvider;
				}
			}
		}

		$this->params = is_array($params) ? $params : array();
	}

	/**
	 * Get Filter ID.
	 * @return string
	 */
	function getID()
	{
		return $this->ID;
	}

	/**
	 * Get Default Field IDs.
	 * @return array
	 */
	public function getDefaultFieldIDs()
	{
		$results = array();
		foreach($this->getFields() as $fieldID => $field)
		{
			if($field->isDefault())
			{
				$results[] = $fieldID;
			}
		}
		return $results;
	}

	/**
	 * Get Field list.
	 * @return Field[]
	 */
	public function getFields()
	{
		if($this->fields === null)
		{
			$this->fields = $this->entityDataProvider->prepareFields();
			foreach($this->extraProviders as $dataProvider)
			{
				$fields = $dataProvider->prepareFields();
				if(!empty($fields))
				{
					$this->fields += $fields;
				}
			}
		}
		return $this->fields;
	}

	/**
	 * Get Fields converted to plain object (array).
	 * @return array
	 */
	public function getFieldArrays()
	{
		$results = array();
		$fields = $this->getFields();
		foreach($fields as $field)
		{
			$results[] = $field->toArray();
		}
		return $results;
	}

	/**
	 * Get Field by ID.
	 * @param string $fieldID Field ID.
	 * @return Field|null
	 */
	public function getField($fieldID)
	{
		$fields = $this->getFields();
		return isset($fields[$fieldID]) ? $fields[$fieldID] : null;
	}


	/**
	 * @return DataProvider|null
	 */
	public function getEntityDataProvider()
	{
		return $this->entityDataProvider;
	}

	public function prepareQuery()
	{
	}

	protected function getDateFieldNames()
	{
		$result = [];
		$fields = $this->getFields();
		foreach ($fields as $field)
		{
			if ($field->getType() === 'date')
			{
				$result[] = $field->getName();
			}
		}
		return $result;
	}

	/**
	 * Prepare list filter params.
	 * @param array $filter Source Filter.
	 * @return void
	 */
	public function prepareListFilterParams(array &$filter)
	{
		foreach ($filter as $k => $v)
		{
			$match = array();
			if (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $match))
			{
				Crm\UI\Filter\Range::prepareFrom($filter, $match[1], $v);
			}
			elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $match))
			{
				if ($v != '' && in_array($match[1], $this->getDateFieldNames()) && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
				{
					$v = \CCrmDateTimeHelper::SetMaxDayTime($v);
				}
				Crm\UI\Filter\Range::prepareTo($filter, $match[1], $v);
			}

			$this->entityDataProvider->prepareListFilterParam($filter, $k);
		}
		Crm\UI\Filter\EntityHandler::internalize($this->getFieldArrays(), $filter);
	}
}