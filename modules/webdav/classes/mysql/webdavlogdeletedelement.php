<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CWebDavLogDeletedElement extends CWebDavLogDeletedElementBase
{
	public static function addBatch(array $items)
	{
		$query = '';
		foreach ($items as $item)
		{
			list($prefix, $values) = static::prepareSqlInsert($item);
			$query .= ($query? ', ' : ' ') . '(' . $values . ')';

			if(strlen($query) > static::$maxLengthBatch)
			{
				static::getDb()->query($prefix . $query);
				$query = '';
			}
		}
		unset($item);

		if($query)
		{
			static::getDb()->query($prefix . $query);
		}

		return;
	}

	protected static function prepareSqlInsert(array $fields)
	{
		$t = static::TABLE_NAME;
		if(empty($fields['VERSION']))
		{
			$fields['VERSION'] = time();
		}
		//todo version is long int
		list($cols, $values) = static::getDb()->prepareInsert($t, $fields);

		return array("INSERT INTO {$t} ({$cols}) VALUES ", $values);
	}
}
