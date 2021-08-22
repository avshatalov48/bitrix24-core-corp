<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage socialnetwork
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Socialnetwork;

use Bitrix\Main\Entity;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\SystemException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\UserTable;

/**
 * Class UserContentViewTable
 *
 * Fields:
 * <ul>
 * <li> USER_ID int mandatory
 * <li> USER reference to {@link \Bitrix\Main\UserTable}
 * <li> RATING_TYPE_ID varchar mandatory
 * <li> RATING_ENTITY_ID int mandatory
 * <li> DATE_VIEW datetime
 * </ul>
 *
 * @package Bitrix\Socialnetwork
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_UserContentView_Query query()
 * @method static EO_UserContentView_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_UserContentView_Result getById($id)
 * @method static EO_UserContentView_Result getList(array $parameters = array())
 * @method static EO_UserContentView_Entity getEntity()
 * @method static \Bitrix\Socialnetwork\EO_UserContentView createObject($setDefaultValues = true)
 * @method static \Bitrix\Socialnetwork\EO_UserContentView_Collection createCollection()
 * @method static \Bitrix\Socialnetwork\EO_UserContentView wakeUpObject($row)
 * @method static \Bitrix\Socialnetwork\EO_UserContentView_Collection wakeUpCollection($rows)
 */
class UserContentViewTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sonet_user_content_view';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
			'RATING_TYPE_ID' => array(
				'data_type' => 'string',
				'primary' => true
			),
			'RATING_ENTITY_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'CONTENT_ID' => array(
				'data_type' => 'string'
			),
			'DATE_VIEW' => array(
				'data_type' => 'datetime'
			),
		);

		return $fieldsMap;
	}

	public static function set($params = array())
	{
		static $controllerUser = array();

		$userId = (isset($params['userId']) ? intval($params['userId']) : 0);
		$typeId = (isset($params['typeId']) ? trim($params['typeId']) : false);
		$entityId = (isset($params['entityId']) ? intval($params['entityId']) : 0);
		$save = (isset($params['save']) ? !!$params['save'] : false);

		if (
			$userId <= 0
			|| empty($typeId)
			|| $entityId <= 0
		)
		{
			throw new SystemException("Invalid input data.");
		}

		$saved = false;

		if (ModuleManager::isModuleInstalled('bitrix24'))
		{
			if (!isset($controllerUser[$userId]))
			{
				$res = UserTable::getList(array(
					'filter' => array(
						'=ID' => $userId,
						'=EXTERNAL_AUTH_ID' => '__controller'
					),
					'select' => array('ID')
				));
				if ($res->fetch())
				{
					$controllerUser[$userId] = true;
				}
				else
				{
					$controllerUser[$userId] = false;
				}
			}

			if ($controllerUser[$userId])
			{
				return array(
					'success' => true,
					'savedInDB' => false
				);
			}
		}

		if ($save)
		{
			$listRes = self::getList([
				'filter' => [
					"=USER_ID" => $userId,
					"=RATING_TYPE_ID" => $typeId,
					"=RATING_ENTITY_ID" => $entityId,
				]
			]);
			if (!$listRes->fetch())
			{
				$connection = \Bitrix\Main\Application::getConnection();
				$helper = $connection->getSqlHelper();

				$nowDate = new SqlExpression($helper->getCurrentDateTimeFunction());

				$insertFields = array(
					"USER_ID" => $userId,
					"RATING_TYPE_ID" => $typeId,
					"RATING_ENTITY_ID" => $entityId,
					"CONTENT_ID" => $typeId."-".$entityId,
					"DATE_VIEW" => $nowDate
				);

				$tableName = static::getTableName();
				list($prefix, $values) = $helper->prepareInsert($tableName, $insertFields);

				$connection->queryExecute("INSERT IGNORE INTO {$tableName} ({$prefix}) VALUES ({$values})");

				$saved = true;
			}
		}

		return array(
			'success' => true,
			'savedInDB' => $saved
		);
	}

	public static function add(array $data)
	{
		throw new NotImplementedException("Use set() method of the class.");
	}

	public static function update($primary, array $data)
	{
		throw new NotImplementedException("Use set() method of the class.");
	}
}
