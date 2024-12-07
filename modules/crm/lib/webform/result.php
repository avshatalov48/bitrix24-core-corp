<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm\Internals\ResultTable;
use Bitrix\Crm\WebForm\Internals\Model;
use Bitrix\Crm\WebForm\Internals\FormCounterTable;
use Bitrix\Crm\WebForm\Internals\FormCounterDailyTable;
use Bitrix\Main\Entity\Result as EntityResult;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class Result extends Model
{
	/**@var $resultEntity ResultEntity Result entity. */
	protected $resultEntity;

	/** @var string $resultUrl Result Url. */
	protected $url;

	protected function getClassTable()
	{
		return 'ResultTable';
	}

	/*
	 * Get result entity.
	 *
	 * @return ResultEntity
	 * */
	public function getResultEntity()
	{
		return $this->resultEntity;
	}

	/**
	 * Get result url.
	 *
	 * @return string
	 */
	public function getUrl()
	{
		if (preg_match('#^(?:/|https?://)#', $this->url))
		{
			return $this->url;
		}

		return null;
	}

	/**
	 * Set result url.
	 *
	 * @param string $url Url.
	 * @return void
	 */
	public function setUrl(string $url = null)
	{
		$this->url = $url;
	}

	public function load($id)
	{
		$class = $this->getClassTable();

		$this->id = $id;
		$result = $class::getRowById($id);
		if(!$result)
		{
			return;
		}

		$this->params = $result;
	}

	public function save($onlyCheck = false)
	{
		$fields = $this->params;
		$fieldsResult = array(
			'FORM_ID' => $fields['FORM_ID'],
			'ORIGIN_ID' => $fields['ORIGIN_ID'],
			'DATE_INSERT' => new DateTime()
		);

		if(!$this->check())
		{
			return;
		}

		if($this->id)
		{
			$result = ResultTable::update($this->id, $fieldsResult);
		}
		else
		{
			$result = ResultTable::add($fieldsResult);
			$this->id = $result->getId();
			if($this->id > 0)
			{
				$this->addEntity();
				FormCounterDailyTable::incrementEndFill(new Date(), (int)$fields['FORM_ID']);
				FormCounterTable::incCounters(
					$fields['FORM_ID'],
					array(
						'END_FILL',
						'MONEY' => $this->getProductSum()
					)
				);
			}
		}

		$this->prepareResult($result);
	}

	public function check()
	{
		$fields = $this->params;
		$fieldsResult = array(
			'FORM_ID' => $fields['FORM_ID'],
			'DATE_INSERT' => new DateTime()
		);

		$result = new EntityResult;
		ResultTable::checkFields($result, $this->id, $fieldsResult);
		$this->prepareResult($result);

		if(!$this->hasErrors())
		{
			$this->checkFields();
		}

		return !$this->hasErrors();
	}

	protected function checkFields()
	{
		$result = true;
		$fields = $this->params['FIELDS'];
		foreach($fields as $field)
		{
			if(!$this->checkField($field))
			{
				$result = false;
			}
		}

		return $result;
	}

	protected function getProductSum()
	{
		$productSum = 0;
		foreach($this->params['PRODUCTS'] as $product)
		{
			$productSum += (int) $product['PRICE'];
		}

		return $productSum;
	}

	public function getFieldsForAdd()
	{
		$fieldsForAdd = array();

		$fields = $this->params['FIELDS'];
		foreach($fields as $field)
		{
			if($field['hidden'])
			{
				continue;
			}

			if(count($field['values']) == 0)
			{
				continue;
			}

			$fieldsForAdd[] = array(
				'type' => '',
				'name' => '',
				'caption' => '',
			);

			foreach($field['values'] as $value)
			{
				foreach($field['items'] as $item)
				{
					if($item['value'] != $value)
					{
						continue;
					}

					$fieldsForAdd['values'][] = $item;
					break;
				}
			}
		}

		return $fieldsForAdd;
	}

	protected function checkField($field)
	{
		if($field['hidden'] || 1)
		{
			return true;
		}

		$values = array();
		foreach($field['values'] as $value)
		{
			if(is_string($value))
			{
				$value = trim($value);
			}
			else
			{
				trimArr($value);
			}

			if(!$value && $value !== '0' && $value !== 0)
			{
				continue;
			}

			$values[] = $value;
		}


		if($field['required'] && count($values) == 0 && empty($this->params['DISABLE_FIELD_CHECKING']))
		{
			$this->errors[] = Loc::getMessage('CRM_WEBFORM_RESULT_ERROR_REQUIRED_FIELD_EMPTY', array('%field%' => $field['caption']));
			return false;
		}
		else if(count($values) == 0)
		{
			return true;
		}

		$result = true;
		switch($field['type'])
		{
			case 'checkbox':
			case 'radio':
			case 'list':
				$itemValues = array();
				foreach($field['items'] as $item)
				{
					$itemValues[] = $item['value'];
				}

				$result = array_intersect($values, $itemValues) > 0;
				break;

			case 'email':
				foreach($values as $value)
				{
					if(!$this->checkEmail($value))
					{
						$result = false;
						break;
					}
				}
				break;
			case 'phone':
				foreach($values as $value)
				{
					if(!$this->checkPhone($value))
					{
						$result = false;
						break;
					}
				}
				break;
			case 'int':
				foreach($values as $value)
				{
					if(!$this->checkInt($value))
					{
						$result = false;
						break;
					}
				}
				break;
		}


		if(!$result)
		{
			$this->errors[] = Loc::getMessage('CRM_WEBFORM_RESULT_ERROR_REQUIRED_FIELD_EMPTY', array('%field%' => $field['caption']));
		}

		return $result;
	}

	protected function checkEmail($value)
	{
		return check_email($value);
	}

	protected function checkInt($value)
	{
		return is_numeric($value);
	}

	protected function checkPhone($value)
	{
		return true;
	}

	public static function formatFieldsByTemplate(
		array $fields,
		$fieldTemplate = "%caption%%required%: %values%\n",
		$valueTemplate = "%value%\n",
		$valueListTemplate = "\n%value%",
		?string $languageId = null,
	)
	{
		$result = '';
		foreach($fields as $field)
		{
			if(!$field || !is_array($field['value']))
			{
				continue;
			}

			$values = array();
			foreach($field['value'] as $value)
			{
				if(is_array($value))
				{
					$values[] = htmlspecialcharsbx($value['title'] ?? $value['name']);

				}
				else
				{
					$values[] = htmlspecialcharsbx($value);
				}
			}

			// format values
			$displayedValues = '';
			foreach ($values as $value)
			{
				switch ($field['type'])
				{
					case Internals\FieldTable::TYPE_ENUM_CHECKBOX:
						if (in_array($value, ['Y', 'N']))
						{
							EntityFieldProvider::getBooleanFieldItems();

							$codePostfix = $value === 'Y' ? 'YES' : 'NO';
							$code = "CRM_WEBFORM_FIELD_PROVIDER_{$codePostfix}";

							$value = Loc::getMessage($code, null, $languageId);
						}
						break;
					case Internals\FieldTable::TYPE_ENUM_DATETIME:
						if (is_numeric($value))
						{
							$value = DateTime::createFromTimestamp($value)->toString();
						}
						break;
				}

				$displayedValues .= str_replace(
					array('%value%'),
					array($value),
					($valueListTemplate && count($values) > 1) ? $valueListTemplate : $valueTemplate
				);
			}

			// format field
			$result .= str_replace(
				array(
					'%caption%',
					'%required%',
					'%values%'
				),
				array(
					htmlspecialcharsbx($field['caption']),
					$field['required'] ? '*' : '',
					$displayedValues
				),
				$fieldTemplate
			);
		}

		return $result;
	}

	protected function addEntity()
	{
		$scheme = $this->params['ENTITY_SCHEME'];
		$fields = $this->params['FIELDS'];
		$presetFields = $this->params['PRESET_FIELDS'];
		$products = $this->params['PRODUCTS'];
		$currencyId = $this->params['CURRENCY_ID'];
		$assignedById = $this->params['ASSIGNED_BY_ID'];
		$activityFields = $this->params['ACTIVITY_FIELDS'];
		$invoiceSettings = $this->params['INVOICE_SETTINGS'];
		$duplicateMode = $this->params['DUPLICATE_MODE'];
		$commonFields = $this->params['COMMON_FIELDS'];
		$commonData = $this->params['COMMON_DATA'];
		$placeholders = $this->params['PLACEHOLDERS'];
		$isCallback = $this->params['IS_CALLBACK'];
		$callbackPhone = $this->params['CALLBACK_PHONE'];
		$entities = $this->params['ENTITIES'];
		$agreements = $this->params['AGREEMENTS'];

		$resultEntity = new ResultEntity;
		$resultEntity->setFormData($this->params['FORM']);
		$resultEntity->setFormId($this->params['FORM_ID']);
		$resultEntity->setResultId($this->id);
		$resultEntity->setAssignedById($assignedById);
		$resultEntity->setPresetFields($presetFields);
		$resultEntity->setCommonFields($commonFields);
		$resultEntity->setCommonData($commonData);
		$resultEntity->setPlaceholders($placeholders);
		$resultEntity->setProductRows($products);
		$resultEntity->setCurrencyId($currencyId);
		$resultEntity->setActivityFields($activityFields);
		$resultEntity->setInvoiceSettings($invoiceSettings);
		$resultEntity->setDuplicateMode($duplicateMode);
		$resultEntity->setCallback($isCallback, $callbackPhone);
		$resultEntity->setEntities($entities);
		$resultEntity->setAgreements($agreements);

		$resultEntity->add($scheme, $fields);
		$this->resultEntity = $resultEntity;
	}
}
