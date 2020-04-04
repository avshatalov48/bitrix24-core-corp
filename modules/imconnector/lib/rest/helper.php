<?php
namespace Bitrix\ImConnector\Rest;

use \Bitrix\ImConnector\Model\CustomConnectorsTable;
use \Bitrix\Rest\PlacementTable;

/**
 * Class Helper
 * @package Bitrix\ImConnector\Rest
 */
class Helper
{
	const PLACEMENT_SETTING_CONNECTOR = 'SETTING_CONNECTOR';

	/**
	 * @param $params
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function registerApp($params)
	{
		$result = false;

		if(!empty($params['ID'])
			&& !empty($params['NAME'])
			&& !empty($params['ICON']['DATA_IMAGE'])
			&& !empty($params['REST_APP_ID']))
		{
			$raw = CustomConnectorsTable::getList(array(
				'select' => array('ID'),
				'filter' => array(
					'ID_CONNECTOR' => $params['ID'],
					'REST_APP_ID' => $params['REST_APP_ID']
				)
			));

			$changeParams = array(
				'ID_CONNECTOR' => strtolower($params['ID']),
				'NAME' => $params['NAME'],
				'ICON' => $params['ICON'],
				'COMPONENT' => $params['COMPONENT'],
				'REST_APP_ID' => $params['REST_APP_ID'],
			);
			$placementParams = array(
				'REST_APP_ID' => $params['REST_APP_ID'],
				'PLACEMENT_HANDLER' => $params['PLACEMENT_HANDLER'],
				'TITLE' => $params['TITLE']
			);

			if(isset($params['ICON_DISABLED']))
			{
				$changeParams['ICON_DISABLED'] = $params['ICON_DISABLED'];
			}
			if(isset($params['DEL_EXTERNAL_MESSAGES']))
			{
				$changeParams['DEL_EXTERNAL_MESSAGES'] = $params['DEL_EXTERNAL_MESSAGES'];
			}
			if(isset($params['EDIT_INTERNAL_MESSAGES']))
			{
				$changeParams['EDIT_INTERNAL_MESSAGES'] = $params['EDIT_INTERNAL_MESSAGES'];
			}
			if(isset($params['DEL_INTERNAL_MESSAGES']))
			{
				$changeParams['DEL_INTERNAL_MESSAGES'] = $params['DEL_INTERNAL_MESSAGES'];
			}
			if(isset($params['NEWSLETTER']))
			{
				$changeParams['NEWSLETTER'] = $params['NEWSLETTER'];
			}
			if(isset($params['NEED_SYSTEM_MESSAGES']))
			{
				$changeParams['NEED_SYSTEM_MESSAGES'] = $params['NEED_SYSTEM_MESSAGES'];
			}
			if(isset($params['NEED_SIGNATURE']))
			{
				$changeParams['NEED_SIGNATURE'] = $params['NEED_SIGNATURE'];
			}
			if(isset($params['CHAT_GROUP']))
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

			if($row = $raw->fetch())
			{
				if(CustomConnectorsTable::update($row['ID'], $changeParams)->isSuccess())
				{
					$result = true;
				}
			}
			else
			{
				if(CustomConnectorsTable::add($changeParams)->isSuccess())
				{
					$result = true;
				}
			}
		}

		return $result;
	}

	/**
	 * @param $params
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function unRegisterApp($params)
	{
		$result = true;

		if(!empty($params['REST_APP_ID']))
		{
			$filter['REST_APP_ID'] = $params['REST_APP_ID'];

			if(!empty($params['ID']))
			{
				$filter['ID_CONNECTOR'] = strtolower($params['ID']);
			}

			$raw = CustomConnectorsTable::getList(array(
				'select' => array('ID', 'REST_PLACEMENT_ID'),
				'filter' => $filter
			));

			while ($row = $raw->fetch())
			{
				CustomConnectorsTable::delete($row['ID']);
				self::unRegisterPlacement($row['REST_PLACEMENT_ID']);
			}
		}

		return $result;
	}

	/**
	 * @param $params
	 *
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function registerPlacement($params)
	{
		$placementBind = array(
			'APP_ID' => $params['REST_APP_ID'],
			'PLACEMENT' => self::PLACEMENT_SETTING_CONNECTOR,
			'PLACEMENT_HANDLER' => $params['PLACEMENT_HANDLER'],
		);
		$placement = PlacementTable::getList(
			array(
				'filter' => $placementBind
			)
		)->fetch();

		$result = ($placement['ID'] > 0) ? $placement['ID'] : self::addPlacement($params)->getId();

		return intval($result);
	}

	/**
	 * @param $params
	 *
	 * @return \Bitrix\Main\ORM\Data\AddResult
	 * @throws \Exception
	 */
	protected static function addPlacement($params)
	{
		$placementBind = array(
			'APP_ID' => $params['REST_APP_ID'],
			'PLACEMENT' => self::PLACEMENT_SETTING_CONNECTOR,
			'PLACEMENT_HANDLER' => $params['PLACEMENT_HANDLER'],
		);

		if(!empty($params['TITLE']))
		{
			$placementBind['TITLE'] = trim($params['TITLE']);
		}

		if(!empty($params['COMMENT']))
		{
			$placementBind['COMMENT'] = trim($params['COMMENT']);
		}

		$result = PlacementTable::add($placementBind);

		return $result;
	}

	/**
	 * @param $placementId
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function unRegisterPlacement($placementId)
	{
		if (intval($placementId) > 0)
		{
			$connectors = CustomConnectorsTable::getList(
				array(
					'filter' => array('REST_PLACEMENT_ID' => $placementId)
				)
			)->fetchAll();

			//count == 0 because this method is called after delete of connector
			if (count($connectors) == 0)
			{
				self::deletePlacement($placementId);
			}
		}

		return true;
	}

	/**
	 * @param $placementId
	 *
	 * @return \Bitrix\Main\ORM\Data\DeleteResult
	 * @throws \Exception
	 */
	protected static function deletePlacement($placementId)
	{
		return PlacementTable::delete($placementId);
	}

	/**
	 * @param array $filter
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function listRestConnector($filter = array())
	{
		$result = array();

		$query = CustomConnectorsTable::query();
		$query->setSelect(array('*'));
		$query->setCacheTtl(3600);
		$query->cacheJoins(true);

		if(!empty($filter))
		{
			$query->setFilter($filter);
		}

		$raw = $query->exec();

		while ($row = $raw->fetch())
		{
			$result[$row['ID_CONNECTOR']] = array(
				'ID' => strtolower($row['ID_CONNECTOR']),
				'NAME' => $row['NAME'],
				'COMPONENT' => $row['COMPONENT'],
				'ICON' => $row['ICON'],
			);

			if (isset($row['ICON_DISABLED']) && $row['ICON_DISABLED'] !== false)
			{
				$result[$row['ID_CONNECTOR']]['ICON_DISABLED'] = $row['ICON_DISABLED'];
			}

			if (isset($row['DEL_EXTERNAL_MESSAGES']))
			{
				if($row['DEL_EXTERNAL_MESSAGES'] == 'Y')
				{
					$result[$row['ID_CONNECTOR']]['DEL_EXTERNAL_MESSAGES'] = true;
				}
				elseif($row['DEL_EXTERNAL_MESSAGES'] == 'N')
				{
					$result[$row['ID_CONNECTOR']]['DEL_EXTERNAL_MESSAGES'] = false;
				}
			}

			if (isset($row['EDIT_INTERNAL_MESSAGES']))
			{
				if($row['EDIT_INTERNAL_MESSAGES'] == 'Y')
				{
					$result[$row['ID_CONNECTOR']]['EDIT_INTERNAL_MESSAGES'] = true;
				}
				elseif($row['EDIT_INTERNAL_MESSAGES'] == 'N')
				{
					$result[$row['ID_CONNECTOR']]['EDIT_INTERNAL_MESSAGES'] = false;
				}
			}

			if (isset($row['DEL_INTERNAL_MESSAGES']))
			{
				if($row['DEL_INTERNAL_MESSAGES'] == 'Y')
				{
					$result[$row['ID_CONNECTOR']]['DEL_INTERNAL_MESSAGES'] = true;
				}
				elseif($row['DEL_INTERNAL_MESSAGES'] == 'N')
				{
					$result[$row['ID_CONNECTOR']]['DEL_INTERNAL_MESSAGES'] = false;
				}
			}

			if (isset($row['NEWSLETTER']))
			{
				if($row['NEWSLETTER'] == 'Y')
				{
					$result[$row['ID_CONNECTOR']]['NEWSLETTER'] = true;
				}
				elseif($row['NEWSLETTER'] == 'N')
				{
					$result[$row['ID_CONNECTOR']]['NEWSLETTER'] = false;
				}
			}

			if (isset($row['NEED_SYSTEM_MESSAGES']))
			{
				if($row['NEED_SYSTEM_MESSAGES'] == 'Y')
				{
					$result[$row['ID_CONNECTOR']]['NEED_SYSTEM_MESSAGES'] = true;
				}
				elseif($row['NEED_SYSTEM_MESSAGES'] == 'N')
				{
					$result[$row['ID_CONNECTOR']]['NEED_SYSTEM_MESSAGES'] = false;
				}
			}

			if (isset($row['NEED_SIGNATURE']))
			{
				if($row['NEED_SIGNATURE'] == 'Y')
				{
					$result[$row['ID_CONNECTOR']]['NEED_SIGNATURE'] = true;
				}
				elseif($row['NEED_SIGNATURE'] == 'N')
				{
					$result[$row['ID_CONNECTOR']]['NEED_SIGNATURE'] = false;
				}
			}

			if (isset($row['CHAT_GROUP']))
			{
				if($row['CHAT_GROUP'] == 'Y')
				{
					$result[$row['ID_CONNECTOR']]['CHAT_GROUP'] = true;
				}
				elseif($row['NEED_SIGNATURE'] == 'N')
				{
					$result[$row['ID_CONNECTOR']]['CHAT_GROUP'] = false;
				}
			}
		}

		return $result;
	}

	/**
	 * @param $idConnector
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getAppRestConnector($idConnector)
	{
		$result = 0;

		$row = CustomConnectorsTable::getRow(array(
			'select' => array('REST_APP_ID'),
			'filter' => array(
				'ID_CONNECTOR' => $idConnector
			)
		));

		if(!empty($row['REST_APP_ID']))
		{
			$result = $row['REST_APP_ID'];
		}

		return $result;
	}
}