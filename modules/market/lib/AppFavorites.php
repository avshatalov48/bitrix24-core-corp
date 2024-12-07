<?php

namespace Bitrix\Market;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;


class AppFavoritesTable extends DataManager
{
	private static ?array $appList = null;

	public static function getTableName()
	{
		return 'b_market_app_favorites';
	}

	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
				]
			),
			new StringField(
				'APP_CODE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateAppCode'],
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'required' => true,
				]
			),
		];
	}

	public static function validateAppCode(): array
	{
		return [
			new LengthValidator(null, 128),
		];
	}

	public static function getUserFavorites(): array
	{
		if (is_null(AppFavoritesTable::$appList)) {
			global $USER;

			AppFavoritesTable::$appList = [];

			$favList = AppFavoritesTable::getList([
				'filter' => [
					'=USER_ID' => $USER->GetID(),
				],
				'select' => ['APP_CODE'],
			])->fetchAll();

			AppFavoritesTable::$appList = array_unique(array_column($favList, 'APP_CODE'));
		}

		return AppFavoritesTable::$appList;
	}

	public static function getUserFavoritesForList($offset, $limit): array
	{
		global $USER;

		$favList = AppFavoritesTable::getList([
			'filter' => [
				'=USER_ID' => $USER->GetID(),
			],
			'order' => ['ID' => 'ASC'],
			'offset' => $offset,
			'limit' => $limit,
		])->fetchAll();

		return array_unique(array_column($favList, 'APP_CODE'));
	}

	public static function addItem($appCode)
	{
		global $USER;

		$exist = AppFavoritesTable::getRow([
			'filter' => [
				'=APP_CODE' => $appCode,
				'=USER_ID' => $USER->GetID(),
			],
		]);
		if (!$exist) {
			AppFavoritesTable::add([
				'APP_CODE' => $appCode,
				'USER_ID' => $USER->GetID(),
			]);
		}
	}

	public static function rmItem($appCode)
	{
		global $USER;

		$exist = AppFavoritesTable::getRow([
			'filter' => [
				'=APP_CODE' => $appCode,
				'=USER_ID' => $USER->GetID(),
			],
		]);
		if ($exist) {
			AppFavoritesTable::delete($exist['ID']);
		}
	}
}