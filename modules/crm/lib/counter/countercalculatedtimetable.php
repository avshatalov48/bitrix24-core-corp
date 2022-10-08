<?php

namespace Bitrix\Crm\Counter;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\Validator\Length;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\Validators\DateValidator;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;

class CounterCalculatedTimeTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_counter_calculated_time';
	}

	public static function getMap(): array
	{
		return  [
			(new IntegerField('USER_ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new StringField('CODE'))
				->configurePrimary()
				->addValidator(new Length(1, 255)),
			(new DatetimeField('CALCULATED_AT'))
				->addValidator(new DateValidator())
		];
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function getCalculatedAt(int $userId, string $code): int
	{
		$result = self::query()
			->setSelect(['CALCULATED_AT'])
			->where('USER_ID', $userId)
			->where( 'CODE',  $code)
			->setCacheTtl(3600)
			->fetch();

		return $result
			? $result['CALCULATED_AT']->getTimestamp()
			: 0;
	}

	public static function setCalculatedAt(int $userId, string $code, int $timestamp): void
	{
		$connection = Application::getConnection();

		$table = self::getTableName();
		$code = $connection->getSqlHelper()->forSql($code);
		$calculatedAt = $connection->getSqlHelper()->convertToDbDateTime(DateTime::createFromTimeStamp($timestamp));

		$connection->queryExecute(
			<<<SQL
			INSERT INTO $table (USER_ID, CODE, CALCULATED_AT)
			VALUES ($userId, '$code', $calculatedAt)
			ON DUPLICATE KEY UPDATE CALCULATED_AT = $calculatedAt;
			SQL
		);

		self::cleanCache();
	}
}