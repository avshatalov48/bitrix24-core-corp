<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Error;
use Bitrix\Main\ORM;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Search\Content;

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

		$updateData = $insertData = [
			'SEARCH_CONTENT' => $searchContent,
			'UPDATED_TIME' => new DateTime(),
		];
		$insertData['ITEM_ID'] = $itemId;

		$preparedSearchContent = $DB->forSql($searchContent);
		$encryptedSearchContent = sha1($searchContent);
		$updateData['SEARCH_CONTENT'] = new SqlExpression("IF(SHA1(SEARCH_CONTENT) = '{$encryptedSearchContent}', SEARCH_CONTENT, '{$preparedSearchContent}')");

		$merge = $helper->prepareMerge(
			static::getEntity()->getDBTableName(),
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

	public static function prepareFullTextQuery(string $query): ?string
	{
		$query = trim($query);
		if (Content::isIntegerToken($query))
		{
			$query = Content::prepareIntegerToken($query);
		}
		else
		{
			$query = Content::prepareStringToken($query);
		}

		if(Content::canUseFulltextSearch($query, Content::TYPE_MIXED))
		{
			return $query;
		}

		return null;
	}

	public static function getItemIdsByQuery(string $query): ?array
	{
		$query = static::prepareFullTextQuery($query);

		if ($query)
		{
			$result = [];
			$list = static::getList([
				'select' => ['ITEM_ID'],
				'filter' => [
					'*SEARCH_CONTENT' => $query,
				],
			]);
			while($item = $list->fetch())
			{
				$result[] = $item['ITEM_ID'];
			}

			return $result;
		}

		return null;
	}
}