<?
namespace Bitrix\Tasks\Integration\Intranet\Internals\Runtime;

use Bitrix\Main\Entity;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\Filter\Expressions\ColumnExpression;

class UserDepartment extends \Bitrix\Tasks\Integration\Intranet
{

	public static function getStructureIblockId()
	{
		static $iblockId;

		if (is_null($iblockId))
		{
			$iblockId = (int) \Bitrix\Main\Config\Option::get('intranet', 'iblock_structure', false);
		}

		return $iblockId > 0 ? $iblockId : false;
	}

	public static function getDepartmentUfId()
	{
		static $userField;

		if (is_null($userField))
		{
			$userField = \CUserTypeEntity::getList(
				array(),
				array(
					'ENTITY_ID' => 'USER',
					'FIELD_NAME' => 'UF_DEPARTMENT',
				)
			)->fetch();
		}

		return $userField['ID'] > 0 ? $userField['ID'] : false;
	}

	public static function getSubordinateFilter(array $parameters = array())
	{
		if(!static::includeModule())
		{
			return array();
		}

		if (!($iblockId = self::getStructureIblockId()))
		{
			return array();
		}

		if (!($ufId = self::getDepartmentUfId()))
		{
			return array();
		}

		return array(
			'runtime' => array(
				new Fields\Relations\Reference(
					'IBS_UFV',
					UtsIblockSectionTable::getEntity(),
					Query::filter()->where('ref.UF_HEAD', '=', $parameters['USER_ID']),
					array('join_type' => 'inner')
				),
				new Fields\Relations\Reference(
					'IBS',
					\Bitrix\Iblock\SectionTable::getEntity(),
					Query::filter()->where('ref.ID', '=', new ColumnExpression('this.IBS_UFV.VALUE_ID')),
					array('join_type' => 'inner')
				),
				new Fields\Relations\Reference(
					'DEP',
					\Bitrix\Iblock\SectionTable::getEntity(),
					Query::filter()
						->where('ref.IBLOCK_ID', '=', $iblockId)
						->where('ref.LEFT_MARGIN', '>=', new ColumnExpression('this.IBS.LEFT_MARGIN'))
						->where('ref.RIGHT_MARGIN', '<=', new ColumnExpression('this.IBS.RIGHT_MARGIN')),
					array('join_type' => 'inner')
				),
				new Fields\Relations\Reference(
					'U_UFV',
					UtmUserTable::getEntity(),
					Query::filter()
						->where('ref.FIELD_ID', '=', $ufId)
						->where('ref.VALUE_INT', '>', 0)
						->where('ref.VALUE_INT', '=', new ColumnExpression('this.DEP.ID'))
						->where('ref.VALUE_ID', '=', new ColumnExpression("this.{$parameters['REF_FIELD']}")),
					array('join_type' => 'inner')
				),
			),
		);
	}

	public static function getUserDepartmentField(array $parameters = array())
	{
		if(!static::includeModule() || !Loader::includeModule('iblock'))
		{
			return array();
		}

		if (!($iblockId = self::getStructureIblockId()))
		{
			return array();
		}

		if (!($ufId = self::getDepartmentUfId()))
		{
			return array();
		}

		return array(
			'runtime' => array(
				new Fields\Relations\Reference(
					'U_UFV',
					UtmUserTable::getEntity(),
					Query::filter()
						->where('ref.FIELD_ID', '=', $ufId)
						->where('ref.VALUE_INT', '>', 0)
						->where('ref.VALUE_ID', '=', new ColumnExpression("this.{$parameters['REF_FIELD']}")),
					array('join_type' => 'inner')
				),
				new Fields\Relations\Reference(
					'DEP',
					\Bitrix\Iblock\SectionTable::getEntity(),
					Query::filter()
						->where('ref.IBLOCK_ID', '=', $iblockId)
						->where('ref.ID', '=', new ColumnExpression('this.U_UFV.VALUE_INT'))
						->where('ref.ACTIVE', '=', 'Y'),
					array('join_type' => 'inner')
				),
			),
		);
	}

}

/**
 * Class UtsIblockSectionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_UtsIblockSection_Query query()
 * @method static EO_UtsIblockSection_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_UtsIblockSection_Result getById($id)
 * @method static EO_UtsIblockSection_Result getList(array $parameters = [])
 * @method static EO_UtsIblockSection_Entity getEntity()
 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection_Collection createCollection()
 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection wakeUpObject($row)
 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection_Collection wakeUpCollection($rows)
 */
class UtsIblockSectionTable extends Entity\DataManager
{

	public static function getTableName()
	{
		if (!($iblockId = UserDepartment::getStructureIblockId()))
		{
			return '';
		}

		return "b_uts_iblock_{$iblockId}_section";
	}

	public static function getMap()
	{
		return array(
			'VALUE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'UF_HEAD' => array(
				'data_type' => 'integer',
			),
		);
	}

}