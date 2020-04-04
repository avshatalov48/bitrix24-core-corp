<?
namespace Bitrix\Intranet\Internals;

use Bitrix\Main;

//use Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

class UserSubordinationTable extends Main\Entity\DataManager
{
	private static $delayed = false;
	private static $changed = false;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_intranet_usersubord';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'primary' => true,
			),
			'DIRECTOR_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				//'title' => Loc::getMessage('SUBORD_CACHE_ENTITY_DIRECTOR_FIELD'),
			),
			'SUBORDINATE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				//'title' => Loc::getMessage('SUBORD_CACHE_ENTITY_SUBORDINATE_FIELD'),
			),

			'DIRECTOR' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.DIRECTOR_ID' => 'ref.ID')
			),
			'SUBORDINATE' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.SUBORDINATE_ID' => 'ref.ID')
			),
		);
	}

	public static function delayReInitialization()
	{
		static::$delayed = true;
	}

	public static function getDelayed()
	{
		return static::$delayed;
	}

	public static function performReInitialization()
	{
		if(static::$delayed)
		{
			if(static::$changed)
			{
				static::$delayed = false;

				static::reInitialize();
				static::$changed = false;
			}
		}
	}

	public static function reInitialize()
	{
		if(static::$delayed)
		{
			static::$changed = true;
			return;
		}

		$tableName = static::getTableName();
		$connection = \Bitrix\Main\HttpApplication::getConnection();

		if($connection->isTableExists($tableName))
		{
			$connection->query("truncate table ".$tableName);

			$ibDept = \COption::GetOptionInt('intranet', 'iblock_structure', false);

			$res = \CAllUserTypeEntity::GetList(array(), array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_DEPARTMENT',
			))->fetch();
			$ufDepartmentFieldId = intval($res['ID']);

			$ufHeadExists = false;
			$res = \CAllUserTypeEntity::GetList(array(), array(
				'ENTITY_ID' => 'IBLOCK_'.$ibDept.'_SECTION',
				'FIELD_NAME' => 'UF_HEAD',
			))->fetch();
			if(intval($res['ID']))
			{
				$ufHeadExists = true;
			}

			if($ibDept && $ufDepartmentFieldId && $ufHeadExists)
			{
				// you can access you employees
				$connection->query("
					insert into ".$tableName." (DIRECTOR_ID, SUBORDINATE_ID)
					select
						U.ID as DIRECTOR,
						SUF.VALUE_ID as SUBORDINATE
					from
						-- select users
						b_user U

						-- select departments of department heads
						inner join
							b_uts_iblock_".$ibDept."_section UDF
						on
							UDF.UF_HEAD = U.ID

						-- select department data
						inner join
							b_iblock_section UD_S
						on
							UD_S.ID = UDF.VALUE_ID

						-- select sub-departments
						inner join
							b_iblock_section UD_SS
						on
							UD_SS.LEFT_MARGIN >= UD_S.LEFT_MARGIN and UD_SS.RIGHT_MARGIN <= UD_S.RIGHT_MARGIN

						-- select sub-department users
						inner join
							b_utm_user SUF
						on
							SUF.FIELD_ID = ".$ufDepartmentFieldId." and SUF.VALUE_INT = UD_SS.ID

					where
						U.ID != SUF.VALUE_ID
				");
			}

			// you can access self
			$connection->query("
				insert into ".$tableName." (DIRECTOR_ID, SUBORDINATE_ID)
				select
					ID, ID
				from
					b_user
			");
		}
	}
}