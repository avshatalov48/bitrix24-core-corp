<?php
namespace Bitrix\Timeman\Model\Monitor;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\ORM;
use Bitrix\Main\Error;
use Bitrix\Main\SystemException;

abstract class Base extends ORM\Data\DataManager
{
	/**
	 * Inserts new record into the table, or updates existing record, if record is already found in the table.
	 *
	 * @param array $data Record to be merged to the table.
	 * @return AddResult
	 */
	public static function merge(array $data): AddResult
	{
		$result = new AddResult();

		$helper = Application::getConnection()->getSqlHelper();
		$insertData = $data;
		$updateData = $data;
		$mergeFields = static::getMergeFields();
		foreach ($mergeFields as $field)
		{
			unset($updateData[$field]);
		}
		$merge = $helper->prepareMerge(
			static::getTableName(),
			static::getMergeFields(),
			$insertData,
			$updateData
		);

		if ($merge[0] != "")
		{
			Application::getConnection()->query($merge[0]);
			$id = Application::getConnection()->getInsertedId();
			$result->setId($id);
			$result->setData($data);
		}
		else
		{
			$result->addError(new Error('Error constructing query'));
		}

		return $result;
	}

	/**
	 * Should return array of names of fields, that should be used to detect record duplication.
	 * @return array;
	 */
	protected static function getMergeFields()
	{
		throw new SystemException("Method should be implemented in class " . get_called_class());
	}
}