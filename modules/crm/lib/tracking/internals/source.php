<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Type\DateTime;

use Bitrix\Crm\Tracking;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__FILE__);

/**
 * Class SourceTable
 *
 * @package Bitrix\Crm\Tracking\Internals
 */
class SourceTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_tracking_source';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'DATE_CREATE' => [
				'data_type' => 'datetime',
				'default_value' => new DateTime(),
			],
			'ACTIVE' => [
				'data_type' => 'boolean',
				'default_value' => 'Y',
				'values' => ['N', 'Y']
			],
			'CODE' => [
				'data_type' => 'string',
			],
			'NAME' => [
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('CRM_TRACKING_INTERNALS_SOURCE_TITLE_NAME'),
			],
			'ICON_COLOR' => [
				'data_type' => 'string',
			],
			'PHONE' => [
				'data_type' => 'string',
			],
			'EMAIL' => [
				'data_type' => 'string',
			],
			'UTM_SOURCE' => [
				'data_type' => 'string',
			],
			'TAGS' => [
				'data_type' => 'text',
				'serialized' => true,
			],
			'AD_CLIENT_ID' => [
				'data_type' => 'string',
			],
			'AD_ACCOUNT_ID' => [
				'data_type' => 'string',
			],
		];
	}

	/**
	 * Get sources.
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getSources()
	{
		return static::getList([
			'select' => [
				'ID', 'CODE',
			],
			'filter' => [
				//'=ACTIVE' => 'Y'
			],
			'cache' => ['ttl' => 3600],
		])->fetchAll();
	}

	/**
	 * Get source by code.
	 *
	 * @param string $code Code.
	 * @return null|int
	 */
	public static function getSourceByCode($code)
	{
		return self::getSourceByField('CODE', $code);
	}

	/**
	 * Get source by email.
	 *
	 * @param string $email Email.
	 * @return null|int
	 */
	public static function getSourceByEmail($email)
	{
		return self::getSourceByField(SourceFieldTable::FIELD_EMAIL, $email);
	}

	/**
	 * Get source by phone number.
	 *
	 * @param string $phoneNumber Phone number.
	 * @return null|int
	 */
	public static function getSourceByPhoneNumber($phoneNumber)
	{
		return self::getSourceByField(SourceFieldTable::FIELD_PHONE, $phoneNumber);
	}

	/**
	 * Get source by UTM source.
	 *
	 * @param string $utmSource UTM source.
	 * @return null|int
	 */
	public static function getSourceByUtmSource($utmSource)
	{
		return self::getSourceByField(SourceFieldTable::FIELD_UTM_SOURCE, $utmSource);
	}

	/**
	 * Get source by Referrer page.
	 *
	 * @param string $ref Referrer page.
	 * @return null|int
	 */
	public static function getSourceByReferrer($ref)
	{
		if (!$ref)
		{
			return null;
		}

		$ref = (new Uri($ref))->getHost();
		return self::getSourceByField(SourceFieldTable::FIELD_REF_DOMAIN, $ref);
	}

	/**
	 * Get source by field.
	 *
	 * @param string $name Name.
	 * @param string $value Value.
	 * @return null|int
	 */
	private static function getSourceByField($name, $value)
	{
		static $sources = null;
		if ($sources === null)
		{
			$sources = Tracking\Provider::getActualSources();
		}
		foreach ($sources as $source)
		{
			if (!isset($source[$name]))
			{
				continue;
			}

			if (is_array($source[$name]))
			{
				foreach ($source[$name] as $item)
				{
					if (is_array($item) && !empty($item['regexp']))
					{
						if (preg_match("/{$item['regexp']}/", $value))
						{
							return $source['ID'];
						}
					}
					elseif ($item == $value)
					{
						return $source['ID'];
					}
				}

				continue;
			}
			elseif ($source[$name] == $value)
			{
				return $source['ID'];
			}
		}

		return null;
	}
}