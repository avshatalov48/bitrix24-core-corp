<?php
namespace Bitrix\Crm\Integration\Sender;

use Bitrix\Main;
use Bitrix\Sender;

/**
 * Class Utm
 * @package Bitrix\Crm\Integration\Sender
 */
class Utm
{
	/**
	 * Return true if integration can be used.
	 *
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function canUse()
	{
		return Main\Loader::includeModule('sender');
	}

	/**
	 * Get `utm_source` tag values.
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getUtmSources()
	{
		if (!self::canUse())
		{
			return [];
		}

		static $list = null;
		if ($list === null)
		{
			$list = array_column(
				Sender\Internals\Model\MessageUtmTable::query()
					->addSelect('VALUE')
					->where('CODE', 'utm_source')
					->addGroup('VALUE')
					->setCacheTtl(36000)
					->fetchAll()
				,
				'VALUE'
			);
		}

		return $list;
	}

	/**
	 * Get read and click statistics by `utm_source` tags.
	 *
	 * @param array $utmSources
	 * @param Main\Type\Date $from
	 * @param Main\Type\Date $to
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getStatByUtmSources(array $utmSources, Main\Type\Date $from, Main\Type\Date $to)
	{
		$row = Sender\Internals\Model\Posting\RecipientTable::query()
			->addSelect(new Main\ORM\Fields\ExpressionField(
				'CNT_READ',
				'COUNT(%s)',
				['IS_READ']
			))
			->addSelect(new Main\ORM\Fields\ExpressionField(
				'CNT_CLICK',
				'SUM(CASE WHEN %s = "Y" THEN 1 ELSE 0 END)',
				['IS_CLICK']
			))
			->where('POSTING.LETTER.MESSAGE.UTM.CODE', 'utm_source')
			->whereIn('POSTING.LETTER.MESSAGE.UTM.VALUE', $utmSources)
			->where('STATUS', Sender\Internals\Model\Posting\RecipientTable::SEND_RESULT_SUCCESS)
			->where('DATE_SENT', '>=', $from)
			->where('DATE_SENT', '<=', $to)
			->where('IS_READ', 'Y')
			->fetch()
		;

		return [
			'read' => $row['CNT_READ'] ?? 0,
			'click' => $row['CNT_CLICK'] ?? 0,
		];
	}
}