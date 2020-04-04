<?
namespace Bitrix\Intranet\Internals;

use Bitrix\Iblock\SectionTable;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\DB;
use Bitrix\Main\Config\Option;

//use	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

class UserToDepartmentTable extends Main\Entity\DataManager
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
		return 'b_intranet_user2dep';
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
				'primary' => true,
				'autocomplete' => true,
				//'title' => Loc::getMessage('USER2DEP_ENTITY_ID_FIELD'),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				//'title' => Loc::getMessage('USER2DEP_ENTITY_USER_ID_FIELD'),
			),
			'DEPARTMENT_ID' => array(
				'data_type' => 'integer',
				//'title' => Loc::getMessage('USER2DEP_ENTITY_DEPARTMENT_ID_FIELD'),
			),
			'WEIGHT' => array(
				'data_type' => 'integer',
				//'title' => Loc::getMessage('USER2DEP_ENTITY_IS_MAIN_FIELD'),
			),

			'DEPARTMENT' => array(
				'data_type' => 'Bitrix\IBlock\Section',
				'reference' => array('=this.DEPARTMENT_ID' => 'ref.ID')
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
		);
	}

	public static function delayReInitialization()
	{
		static::$delayed = true;
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

		$connection = \Bitrix\Main\HttpApplication::getConnection();

		if($connection->isTableExists(static::getTableName()))
		{
			$connection->query("truncate table ".static::getTableName());

			$ibDept = Option::get('intranet', 'iblock_structure', false);

			if(static::getUfDepartmentFieldId() && $ibDept && Main\Loader::includeModule('iblock'))
			{
				$users = array();
				$haveMultiple = false;
				$res = Main\UserTable::getList(array(
					'select' => array('ID', 'UF_DEPARTMENT'),
					'filter' => array('=ACTIVE' => 'Y'),
				));
				while($item = $res->fetch())
				{
					$users[$item['ID']] = $item['UF_DEPARTMENT'];
					if(is_array($item['UF_DEPARTMENT']) && count($item['UF_DEPARTMENT']))
					{
						$haveMultiple = true;
					}
				}

				$departments = array();
				if($haveMultiple)
				{
					// get all departments
					$departments = array();
					$res = SectionTable::getList(array(
						'select' => array('ID', 'DEPTH_LEVEL'),
						'filter' => array('=IBLOCK_ID' => $ibDept),
						'order' => array('DEPTH_LEVEL' => 'asc'),
					));
					while($item = $res->fetch())
					{
						$departments[$item['ID']] = $item['DEPTH_LEVEL'];
					}
				}

				$data = array();
				foreach($users as $userId => $userDepartments)
				{
					if(is_array($userDepartments))
					{
						$weights = static::arrangeDepartmentWeights($departments, $userDepartments);

						foreach($userDepartments as $departmentId)
						{
							if(!array_key_exists($departmentId, $weights)) // section was deleted or like that
							{
								continue;
							}

							$data[] = array(
								'USER_ID' => $userId,
								'DEPARTMENT_ID' => $departmentId,
								'WEIGHT' => $weights[$departmentId],
							);
						}
					}
					else
					{
						$data[] = array(
							'USER_ID' => $userId,
							'DEPARTMENT_ID' => false,
							'WEIGHT' => 1,
						);
					}
				}

				static::insertBatch(static::getTableName(), $data);
			}
		}
	}

	public static function deleteByUserId($userId)
	{
		$connection = \Bitrix\Main\HttpApplication::getConnection();

		if($connection->isTableExists(static::getTableName()))
		{
			$connection->query("delete from ".static::getTableName()." where USER_ID = '".intval($userId)."'");
		}
	}

	protected static function getUfDepartmentFieldId()
	{
		$res = \CAllUserTypeEntity::GetList(array(), array(
			'ENTITY_ID' => 'USER',
			'FIELD_NAME' => 'UF_DEPARTMENT',
		))->fetch();
		return intval($res['ID']);
	}

	private static function arrangeDepartmentWeights(array $departments, array $subSet)
	{
		if(count($subSet) == 1 || count($departments) < 2)
		{
			return array(
				$subSet[0] => 1
			);
		}

		$subSet = array_flip($subSet);

		$result = array();
		$i = 1;
		foreach($departments as $id => $depthLevel)
		{
			if(array_key_exists($id, $subSet))
			{
				$result[$id] = $i++;
			}
		}

		return $result;
	}

	private static function insertBatch($tableName, array $items)
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$query = $prefix = '';
		if ($connection instanceof DB\MysqlCommonConnection)
		{
			foreach ($items as $item)
			{
				list($prefix, $values) = $sqlHelper->prepareInsert($tableName, $item);

				$query .= ($query? ', ' : ' ') . '(' . $values . ')';
				if(strlen($query) > 2048)
				{
					$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) VALUES {$query}");
					$query = '';
				}
			}
			unset($item);

			if ($query && $prefix)
			{
				$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) VALUES {$query}");
			}
		}
		elseif ($connection instanceof DB\MssqlConnection)
		{
			$valueData = array();
			foreach ($items as $item)
			{
				list($prefix, $values) = $sqlHelper->prepareInsert($tableName, $item);
				$valueData[] = "SELECT {$values}";
			}
			unset($item);

			$valuesSql = implode(' UNION ALL ', $valueData);
			if ($valuesSql && $prefix)
			{
				$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) $valuesSql");
			}
		}
		elseif ($connection instanceof DB\OracleConnection)
		{
			$valueData = array();
			foreach ($items as $item)
			{
				list($prefix, $values) = $sqlHelper->prepareInsert($tableName, $item);
				$valueData[] = "SELECT {$values} FROM dual";
			}
			unset($item);

			$valuesSql = implode(' UNION ALL ', $valueData);
			if ($valuesSql && $prefix)
			{
				$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) $valuesSql");
			}
		}
	}
}