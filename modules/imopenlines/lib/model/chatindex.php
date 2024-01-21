<?php

namespace Bitrix\ImOpenLines\Model;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\MergeTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Search\MapBuilder;

/**
 * Class ChatIndexTable
 *
 * Fields:
 * <ul>
 * <li> CHAT_ID int mandatory
 * <li> SEARCH_TITLE string(511) optional
 * </ul>
 *
 * @package Bitrix\Imopenlines
 **/

class ChatIndexTable extends DataManager
{
	use MergeTrait;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_chat_index';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'CHAT_ID' => new IntegerField(
				'CHAT_ID',
				[
					'primary' => true,
				]
			),
			'SEARCH_TITLE' => new StringField(
				'SEARCH_TITLE',
				[
					'validation' => function()
					{
						return[
							new LengthValidator(null, 511),
						];
					},
				]
			),
		];
	}

	public static function addIndex(int $chatId, ?string $title = null): void
	{
		$title ??= static::getChatTitle($chatId);

		$preparedTitle = static::prepareTitle($title);
		if ($preparedTitle === '')
		{
			return;
		}

		self::add(['CHAT_ID' => $chatId, 'SEARCH_TITLE' => $preparedTitle]);
	}

	public static function updateIndex(int $chatId, ?string $title = null): void
	{
		$title ??= static::getChatTitle($chatId);

		$preparedTitle = static::prepareTitle($title);
		if ($preparedTitle === '')
		{
			self::delete($chatId);

			return;
		}

		$helper = Application::getConnection()->getSqlHelper();
		$updateData['SEARCH_TITLE'] = new SqlExpression($helper->getConditionalAssignment('SEARCH_TITLE', $preparedTitle));

		$update = $helper->prepareUpdate(
			static::getTableName(),
			$updateData
		);

		if ($update[0] === '')
		{
			return;
		}

		$tableName = static::getTableName();
		$primaryField = static::getEntity()->getPrimary();
		Application::getConnection()->query("UPDATE {$tableName} SET {$update[0]} WHERE {$primaryField} = {$chatId}");
	}

	/**
	 * @internal
	 * @param int $chatId
	 * @param string|null $title
	 * @return void
	 */
	public static function mergeIndex(int $chatId, ?string $title = null): void
	{
		$title ??= static::getChatTitle($chatId);

		$preparedTitle = static::prepareTitle($title);
		if ($preparedTitle === '')
		{
			self::delete($chatId);

			return;
		}

		$helper = Application::getConnection()->getSqlHelper();
		$insertData = [
			'CHAT_ID' => $chatId,
			'SEARCH_TITLE' => $preparedTitle,
		];
		$updateData['SEARCH_TITLE'] = new SqlExpression($helper->getConditionalAssignment('SEARCH_TITLE', $preparedTitle));

		static::merge($insertData, $updateData);
	}

	public static function getChatTitle(int $chatId): string
	{
		if (!Loader::includeModule('im'))
		{
			return '';
		}

		return \Bitrix\Im\Model\ChatTable::query()->setSelect(['TITLE'])->where('ID', $chatId)->fetch()['TITLE'] ?? '';
	}

	public static function prepareTitle(string $title): string
	{
		if (!Loader::includeModule('im'))
		{
			return '';
		}

		$clearedTitle = \Bitrix\Im\Internals\ChatIndex::create()->setTitle($title)->getClearedTitle();

		return MapBuilder::create()->addText($clearedTitle)->build();
	}
}