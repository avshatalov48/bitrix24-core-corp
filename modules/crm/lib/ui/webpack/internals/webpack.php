<?
namespace Bitrix\Crm\UI\Webpack\Internals;

use	Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Entity\DataManager;

Loc::loadMessages(__FILE__);

/**
 * Class WebPackTable
 *
 * @package Bitrix\Crm\SiteButton\Internals
 */
class WebpackTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_webpack';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'ENTITY_TYPE' => [
				'data_type' => 'string',
				'primary' => true,
			],
			'ENTITY_ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'FILE_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
		];
	}
}