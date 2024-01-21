<?php

namespace Bitrix\Crm\Model\Dynamic;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Error;
use Bitrix\Main\ORM;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

abstract class PrototypeItemIndex extends ORM\Data\DataManager
{
	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ITEM_ID'))
				->configurePrimary(),
			(new ORM\Fields\DatetimeField('UPDATED_TIME')),
			(new ORM\Fields\TextField('SEARCH_CONTENT')),
		];
	}

	public static function merge(int $itemId, string $searchContent): Result
	{
		$result = new Result();
		global $DB;
		$helper = Application::getConnection()->getSqlHelper();
		$updatedTime = new DateTime();

		$insertData = [
			'SEARCH_CONTENT' => $searchContent,
			'UPDATED_TIME' => $updatedTime,
			'ITEM_ID' => $itemId,
		];

		$preparedSearchContent = $DB->forSql($searchContent);
		$encryptedSearchContent = sha1($searchContent);

		$tableName =static::getEntity()->getDBTableName();
		$updateData = [
			'SEARCH_CONTENT' => new SqlExpression("CASE WHEN " . $helper->getSha1Function('?#.?#') . " = '{$encryptedSearchContent}' THEN ?#.?# ELSE '{$preparedSearchContent}' END", $tableName, 'SEARCH_CONTENT', $tableName, 'SEARCH_CONTENT'),
			'UPDATED_TIME' => $updatedTime,
		];

		$merge = $helper->prepareMerge(
			$tableName,
			['ITEM_ID'],
			$insertData,
			$updateData
		);

		if ($merge[0] !== '')
		{
			Application::getConnection()->query($merge[0]);

		}
		else
		{
			$result->addError(new Error('Error constructing item index merge query'));
		}

		return $result;
	}
}