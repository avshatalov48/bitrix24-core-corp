<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Main\Entity;

/**
 * Class MailBodyTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_MailBody_Query query()
 * @method static EO_MailBody_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_MailBody_Result getById($id)
 * @method static EO_MailBody_Result getList(array $parameters = [])
 * @method static EO_MailBody_Entity getEntity()
 * @method static \Bitrix\Crm\Activity\EO_MailBody createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Activity\EO_MailBody_Collection createCollection()
 * @method static \Bitrix\Crm\Activity\EO_MailBody wakeUpObject($row)
 * @method static \Bitrix\Crm\Activity\EO_MailBody_Collection wakeUpCollection($rows)
 */
class MailBodyTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_act_mail_body';
	}

	public static function getMap()
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'BODY' => [
				'data_type' => 'string',
				'fetch_data_modification' => function () {
					return [
						function ($value)
						{
							return static::uncompressBody($value);
						},
					];
				},
				'save_data_modification' => function () {
					return [
						function ($value)
						{
							return static::compressBody($value);
						},
					];
				},
			],
			'BODY_HASH' => [
				'data_type' => 'string',
			],
		];
	}

	public static function addByBody(string $body): int
	{
		$body = trim($body);
		$hash = static::calculateBodyHash($body);

		$row = static::getList([
			'select' => ['ID'],
			'filter' => ['=BODY_HASH' => $hash],
		])->fetch();

		if ($row)
		{
			return (int)$row['ID'];
		}

		$addResult = static::add([
			'BODY' => $body,
			'BODY_HASH' => $hash,
		]);

		return (int)$addResult->getId();
	}

	protected static function calculateBodyHash(string $body)
	{
		return md5($body);
	}

	public static function compressBody(string $body): string
	{
		if (!function_exists('gzcompress'))
		{
			return $body;
		}

		return gzcompress($body, 9);
	}

	public static function uncompressBody(string $compressed): string
	{
		if (!function_exists('gzuncompress'))
		{
			return $compressed;
		}
		$result = gzuncompress($compressed);

		return $result !== false ? $result : $compressed;
	}
}
