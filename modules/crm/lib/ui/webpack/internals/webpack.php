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
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Webpack_Query query()
 * @method static EO_Webpack_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Webpack_Result getById($id)
 * @method static EO_Webpack_Result getList(array $parameters = [])
 * @method static EO_Webpack_Entity getEntity()
 * @method static \Bitrix\Crm\UI\Webpack\Internals\EO_Webpack createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\UI\Webpack\Internals\EO_Webpack_Collection createCollection()
 * @method static \Bitrix\Crm\UI\Webpack\Internals\EO_Webpack wakeUpObject($row)
 * @method static \Bitrix\Crm\UI\Webpack\Internals\EO_Webpack_Collection wakeUpCollection($rows)
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
			'FILE_DATE_UPDATE' => [
				'data_type' => 'datetime',
				'expression' => array(
					'(SELECT TIMESTAMP_X FROM b_file WHERE ID=%s)',
					'FILE_ID'
				)
			],
		];
	}
}