<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm\Helper;

Loc::loadMessages(__FILE__);

class FormCounterTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_webform_counter';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'FORM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'default_value' => new DateTime(),
			),
			'VIEWS' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'MONEY' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'START_FILL' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'END_FILL' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ENTITY_CONTACT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ENTITY_COMPANY' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ENTITY_LEAD' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ENTITY_DEAL' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ENTITY_QUOTE' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ENTITY_INVOICE' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
		);
	}

	public static function addByFormId($formId)
	{
		$result = static::add(array('FORM_ID' => $formId));
		if($result->isSuccess())
		{
			return $result->getId();
		}
		else
		{
			return null;
		}
	}

	public static function getCurrentFormCounter($formId)
	{
		$counterDb = static::getList(array(
			'select' => array('ID'),
			'filter' => array('=FORM_ID' => $formId),
			'order' => array('DATE_CREATE' => 'DESC'),
			'limit' => 1
		));
		if($counter = $counterDb->fetch())
		{
			return $counter['ID'];
		}
		else
		{
			return static::addByFormId($formId);
		}
	}

	public static function getByFormId($formId)
	{
		$counterId = static::getCurrentFormCounter($formId);
		$counters = static::getRowById($counterId);

		unset($counters['ID']);
		unset($counters['FORM_ID']);

		return $counters;
	}

	public static function incCounters($formId, array $counters)
	{
		$updateFields = array();

		$counterId = static::getCurrentFormCounter($formId);
		foreach($counters as $key => $value)
		{
			if(is_numeric($key))
			{
				$counterName = $value;
				$incValue = '1';
			}
			else
			{
				$counterName = $key;
				$incValue = (string) $value;
			}
			$updateFields[$counterName] = new \Bitrix\Main\DB\SqlExpression('?# + ' . $incValue, $counterName);
		}

		$result = static::update($counterId, $updateFields);
		return $result->isSuccess();
	}

	public static function incEntityCounters($formId, array $counters)
	{
		$fieldsMap = static::getMap();
		$entityFieldPrefix = static::getEntityFieldPrefix();

		$fields = array();
		foreach($counters as $counterName)
		{
			$fieldName = $entityFieldPrefix . $counterName;
			if(!isset($fieldsMap[$fieldName]))
			{
				continue;
			}

			$fields[] = $fieldName;
		}

		$isSuccess = true;
		if(count($fields) > 0)
		{
			$isSuccess = static::incCounters($formId, $fields);
		}

		return $isSuccess;
	}

	public static function getEntityFieldPrefix()
	{
		return 'ENTITY_';
	}

	public static function getEntityFieldsMap()
	{
		$fieldsMap = static::getMap();
		$prefix = static::getEntityFieldPrefix();
		$prefixLen = strlen($prefix);

		$result = array();
		foreach($fieldsMap as $fieldName => $field)
		{
			if(substr($fieldName, 0, $prefixLen) != $prefix)
			{
				continue;
			}

			$result[$fieldName] = substr($fieldName, $prefixLen);
		}

		return $result;
	}
}
