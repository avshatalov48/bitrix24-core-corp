<?php
namespace Bitrix\ImConnector\Rest;

use Bitrix\Rest\PlacementTable,
	Bitrix\Main\Application,
	Bitrix\Main\ORM\Data\AddResult,
	Bitrix\Main\ORM\Data\DeleteResult,
	Bitrix\ImConnector\Model\CustomConnectorsTable,
	Bitrix\ImConnector\Model\StatusConnectorsTable;

/**
 * Class Helper
 * @package Bitrix\ImConnector\Rest
 */
class Helper
{
	const PLACEMENT_SETTING_CONNECTOR = 'SETTING_CONNECTOR';
	const LOCK_KEY_NAME = 'imconnector_register_app_lock_';

	/**
	 * @param array $params
	 * @return bool
	 */
	public static function registerApp(array $params): bool
	{
		$result = false;

		if (
			!empty($params['ID'])
			&& !empty($params['NAME'])
			&& !empty($params['ICON']['DATA_IMAGE'])
			&& !empty($params['REST_APP_ID'])
		)
		{
			$keyLock = self::LOCK_KEY_NAME . $params['ID'];
			if (Application::getConnection()->lock($keyLock))
			{
				$changeParams = [
					'ID_CONNECTOR' => mb_strtolower($params['ID']),
					'NAME' => $params['NAME'],
					'ICON' => $params['ICON'],
					'COMPONENT' => $params['COMPONENT'],
					'REST_APP_ID' => $params['REST_APP_ID'],
				];
				$placementParams = [
					'REST_APP_ID' => $params['REST_APP_ID'],
					'PLACEMENT_HANDLER' => $params['PLACEMENT_HANDLER'],
					'TITLE' => $params['TITLE']
				];

				if (isset($params['ICON_DISABLED']))
				{
					$changeParams['ICON_DISABLED'] = $params['ICON_DISABLED'];
				}
				if (isset($params['DEL_EXTERNAL_MESSAGES']))
				{
					$changeParams['DEL_EXTERNAL_MESSAGES'] = $params['DEL_EXTERNAL_MESSAGES'];
				}
				if (isset($params['EDIT_INTERNAL_MESSAGES']))
				{
					$changeParams['EDIT_INTERNAL_MESSAGES'] = $params['EDIT_INTERNAL_MESSAGES'];
				}
				if (isset($params['DEL_INTERNAL_MESSAGES']))
				{
					$changeParams['DEL_INTERNAL_MESSAGES'] = $params['DEL_INTERNAL_MESSAGES'];
				}
				if (isset($params['NEWSLETTER']))
				{
					$changeParams['NEWSLETTER'] = $params['NEWSLETTER'];
				}
				if (isset($params['NEED_SYSTEM_MESSAGES']))
				{
					$changeParams['NEED_SYSTEM_MESSAGES'] = $params['NEED_SYSTEM_MESSAGES'];
				}
				if (isset($params['NEED_SIGNATURE']))
				{
					$changeParams['NEED_SIGNATURE'] = $params['NEED_SIGNATURE'];
				}
				if (isset($params['CHAT_GROUP']))
				{
					$changeParams['CHAT_GROUP'] = $params['CHAT_GROUP'];
				}
				if (isset($params['COMMENT']))
				{
					$placementParams['COMMENT'] = $params['COMMENT'];
				}

				$placementId = self::registerPlacement($placementParams);
				if ($placementId > 0)
				{
					$changeParams['REST_PLACEMENT_ID'] = $placementId;
				}

				$raw = CustomConnectorsTable::getList([
					'select' => ['ID'],
					'filter' => [
						'=ID_CONNECTOR' => $changeParams['ID_CONNECTOR'],
					]
				]);
				if ($row = $raw->fetch())
				{
					$result = CustomConnectorsTable::update($row['ID'], $changeParams)->isSuccess();
				}
				else
				{
					$result = CustomConnectorsTable::add($changeParams)->isSuccess();
				}

				Application::getConnection()->unlock($keyLock);
			}
		}

		return $result;
	}

	/**
	 * @param array $params
	 * @return bool
	 */
	public static function unRegisterApp(array $params): bool
	{
		$result = true;

		if (empty($params['REST_APP_ID']) || empty($params['ID']))
		{
			return false;
		}

		$restAppId = $params['REST_APP_ID'];
		$connectorId = mb_strtolower($params['ID']);

		$connection = \Bitrix\Main\Application::getInstance()->getConnection();
		$connection->startTransaction();

		try
		{
			$raw = StatusConnectorsTable::getList([
				'select' => ['ID', 'LINE', 'CONNECTOR'],
				'filter' => [
					'=CONNECTOR' => $connectorId,
				],
			]);
			while ($row = $raw->fetch())
			{
				$isStatusDeleted = \Bitrix\ImConnector\Status::delete($row['CONNECTOR'], (int)$row['LINE']);
				if (!$isStatusDeleted)
				{
					$result = false;
					break;
				}
			}

			if ($result)
			{
				$raw = CustomConnectorsTable::getList([
					'select' => ['ID', 'REST_PLACEMENT_ID'],
					'filter' => [
						'=REST_APP_ID' => $restAppId,
						'=ID_CONNECTOR' => $connectorId,
					],
				]);
				while ($row = $raw->fetch())
				{
					$deleteConnectorResult = CustomConnectorsTable::delete($row['ID']);
					$isPlacementUnRegistered = self::unRegisterPlacement((int)$row['REST_PLACEMENT_ID']);
					if (!$isPlacementUnRegistered || !$deleteConnectorResult->isSuccess())
					{
						$result = false;
						break;
					}
				}
			}

			if ($result)
			{
				$connection->commitTransaction();
			}
			else
			{
				$connection->rollbackTransaction();
			}
		}
		catch (\Bitrix\Main\SystemException $e)
		{
			$connection->rollbackTransaction();
			$result = false;
		}

		return $result;
	}

	/**
	 * @param array $params
	 *
	 * @return int
	 */
	protected static function registerPlacement(array $params): int
	{
		$placementBind = [
			'=APP_ID' => $params['REST_APP_ID'],
			'=PLACEMENT' => self::PLACEMENT_SETTING_CONNECTOR,
			'=PLACEMENT_HANDLER' => $params['PLACEMENT_HANDLER'],
		];
		$placement = PlacementTable::getList(['filter' => $placementBind])->fetch();

		$result = ($placement['ID'] > 0) ? $placement['ID'] : self::addPlacement($params)->getId();

		return (int)$result;
	}

	/**
	 * @param array $params
	 * @return AddResult
	 */
	protected static function addPlacement(array $params): AddResult
	{
		$placementBind = [
			'APP_ID' => $params['REST_APP_ID'],
			'PLACEMENT' => self::PLACEMENT_SETTING_CONNECTOR,
			'PLACEMENT_HANDLER' => $params['PLACEMENT_HANDLER'],
		];

		if (!empty($params['TITLE']))
		{
			$placementBind['TITLE'] = trim($params['TITLE']);
		}

		if (!empty($params['COMMENT']))
		{
			$placementBind['COMMENT'] = trim($params['COMMENT']);
		}

		return PlacementTable::add($placementBind);
	}

	/**
	 * @param int $placementId
	 *
	 * @return bool
	 */
	protected static function unRegisterPlacement(int $placementId): bool
	{
		if ($placementId > 0)
		{
			$connectorCount = CustomConnectorsTable::getCount(['=REST_PLACEMENT_ID' => $placementId]);

			//count == 0 because this method is called after connector delete
			if ($connectorCount == 0)
			{
				return self::deletePlacement($placementId)->isSuccess();
			}
		}

		return true;
	}

	/**
	 * @param int $placementId
	 * @return DeleteResult
	 */
	protected static function deletePlacement(int $placementId): DeleteResult
	{
		return PlacementTable::delete($placementId);
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	public static function listRestConnector(array $filter = []): array
	{
		$result = [];

		$query = CustomConnectorsTable::query();
		$query->setSelect(['*']);
		$query->setCacheTtl(3600);
		$query->cacheJoins(true);

		if (!empty($filter))
		{
			$query->setFilter($filter);
		}

		$raw = $query->exec();
		while ($row = $raw->fetch())
		{
			$connectorId = $row['ID_CONNECTOR'];
			$result[$connectorId] = [
				'ID' => mb_strtolower($connectorId),
				'NAME' => $row['NAME'],
				'COMPONENT' => $row['COMPONENT'],
				'ICON' => $row['ICON'],
			];

			if (isset($row['ICON_DISABLED']) && $row['ICON_DISABLED'] !== false)
			{
				$result[$connectorId]['ICON_DISABLED'] = $row['ICON_DISABLED'];
			}

			if (isset($row['DEL_EXTERNAL_MESSAGES']))
			{
				if ($row['DEL_EXTERNAL_MESSAGES'] == 'Y')
				{
					$result[$connectorId]['DEL_EXTERNAL_MESSAGES'] = true;
				}
				elseif ($row['DEL_EXTERNAL_MESSAGES'] == 'N')
				{
					$result[$connectorId]['DEL_EXTERNAL_MESSAGES'] = false;
				}
			}

			if (isset($row['EDIT_INTERNAL_MESSAGES']))
			{
				if ($row['EDIT_INTERNAL_MESSAGES'] == 'Y')
				{
					$result[$connectorId]['EDIT_INTERNAL_MESSAGES'] = true;
				}
				elseif ($row['EDIT_INTERNAL_MESSAGES'] == 'N')
				{
					$result[$connectorId]['EDIT_INTERNAL_MESSAGES'] = false;
				}
			}

			if (isset($row['DEL_INTERNAL_MESSAGES']))
			{
				if ($row['DEL_INTERNAL_MESSAGES'] == 'Y')
				{
					$result[$connectorId]['DEL_INTERNAL_MESSAGES'] = true;
				}
				elseif ($row['DEL_INTERNAL_MESSAGES'] == 'N')
				{
					$result[$connectorId]['DEL_INTERNAL_MESSAGES'] = false;
				}
			}

			if (isset($row['NEWSLETTER']))
			{
				if ($row['NEWSLETTER'] == 'Y')
				{
					$result[$connectorId]['NEWSLETTER'] = true;
				}
				elseif ($row['NEWSLETTER'] == 'N')
				{
					$result[$connectorId]['NEWSLETTER'] = false;
				}
			}

			if (isset($row['NEED_SYSTEM_MESSAGES']))
			{
				if ($row['NEED_SYSTEM_MESSAGES'] == 'Y')
				{
					$result[$connectorId]['NEED_SYSTEM_MESSAGES'] = true;
				}
				elseif ($row['NEED_SYSTEM_MESSAGES'] == 'N')
				{
					$result[$connectorId]['NEED_SYSTEM_MESSAGES'] = false;
				}
			}

			if (isset($row['NEED_SIGNATURE']))
			{
				if ($row['NEED_SIGNATURE'] == 'Y')
				{
					$result[$connectorId]['NEED_SIGNATURE'] = true;
				}
				elseif ($row['NEED_SIGNATURE'] == 'N')
				{
					$result[$connectorId]['NEED_SIGNATURE'] = false;
				}
			}

			if (isset($row['CHAT_GROUP']))
			{
				if ($row['CHAT_GROUP'] == 'Y')
				{
					$result[$connectorId]['CHAT_GROUP'] = true;
				}
				elseif ($row['NEED_SIGNATURE'] == 'N')
				{
					$result[$connectorId]['CHAT_GROUP'] = false;
				}
			}
		}

		return $result;
	}

	/**
	 * @param $connectorId
	 * @return int
	 */
	public static function getAppRestConnector($connectorId): int
	{
		$result = 0;

		$row = CustomConnectorsTable::getRow([
			'select' => ['REST_APP_ID'],
			'filter' => ['=ID_CONNECTOR' => $connectorId]
		]);

		if (!empty($row['REST_APP_ID']))
		{
			$result = (int)$row['REST_APP_ID'];
		}

		return $result;
	}
}