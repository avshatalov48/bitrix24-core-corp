<?php
namespace Bitrix\Crm\Activity\Rest;

use \Bitrix\Crm\Activity\Entity\AppTypeTable;

use Bitrix\Main\Loader;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\RestException;

class TypeProxy implements \ICrmRestProxy
{
	/** @var \CRestServer|null  */
	private $server = null;

	/**
	 * Get REST-server
	 * @return \CRestServer
	 */
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * Set REST-server
	 * @param \CRestServer $server
	 */
	public function setServer(\CRestServer $server)
	{
		$this->server = $server;
	}

	public function processMethodRequest($name, $nameDetails, $params, $nav, $server)
	{
		$action = mb_strtolower($nameDetails[0]);

		if ($action === 'add')
		{
			return $this->addType($params);
		}
		elseif ($action === 'list')
		{
			return $this->getTypeList($params);
		}
		elseif ($action === 'delete')
		{
			return $this->deleteType($params);
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}

	private function addType(array $params)
	{
		$this->checkAdminPermissions();
		$clientId = $this->getServer()->getClientId();

		if (!$clientId)
		{
			throw new AccessException('Application context required');
		}

		$params = array_change_key_case($params, CASE_UPPER);
		$params = $params['FIELDS'];

		$app = AppTable::getList(
			array(
				'filter' => array(
					'=CLIENT_ID' => $clientId
				),
				'select' => array('ID')
			)
		)->fetch();

		$exists = AppTypeTable::getList(array(
			'filter' => array(
				'=APP_ID' => $app['ID'],
				'=TYPE_ID' => $params['TYPE_ID']
			),
			'select' => array('ID')
		))->fetch();

		if ($exists)
		{
			throw new RestException('Type is already registered');
		}

		$fields = array(
			'APP_ID' => $app['ID'],
			'TYPE_ID' => $params['TYPE_ID'],
			'NAME' => $params['NAME'],
			'ICON_ID' => 0
		);

		if (!empty($params['ICON_FILE']))
		{
			$fileFields = \CRestUtil::saveFile($params['ICON_FILE']);

			if ($fileFields)
			{
				$fileFields['MODULE_ID'] = 'crm';
				$fileId = \CFile::saveFile($fileFields, 'crm_act_app_type');
				if ($fileId)
				{
					$fields['ICON_ID'] = $fileId;
				}
			}
		}

		$addResult = AppTypeTable::add($fields);

		return $addResult->isSuccess();
	}

	private function deleteType(array $params)
	{
		$this->checkAdminPermissions();
		$clientId = $this->getServer()->getClientId();

		if (!$clientId)
		{
			throw new AccessException('Application context required');
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$app = AppTable::getList(
			array(
				'filter' => array(
					'=CLIENT_ID' => $clientId
				),
				'select' => array('ID')
			)
		)->fetch();

		$exists = AppTypeTable::getList(array(
			'filter' => array(
				'=APP_ID' => $app['ID'],
				'=TYPE_ID' => $params['TYPE_ID']
			)
		))->fetch();

		if (!$exists)
		{
			throw new RestException('Type not found');
		}

		$deleteResult = AppTypeTable::delete($exists['ID']);

		if ($deleteResult->isSuccess() && $exists['ICON_ID'] > 0)
		{
			\CFile::Delete($exists['ICON_ID']);
		}

		return $deleteResult->isSuccess();
	}

	private function getTypeList(array $params)
	{
		$this->checkAdminPermissions();
		$clientId = $this->getServer()->getClientId();

		if (!$clientId)
		{
			throw new AccessException('Application context required');
		}

		$app = AppTable::getList(
			array(
				'filter' => array(
					'=CLIENT_ID' => $clientId
				),
				'select' => array('ID')
			)
		)->fetch();

		return AppTypeTable::getList(array(
			'filter' => array(
				'=APP_ID' => $app['ID'],
			),
			'select' => array('TYPE_ID', 'NAME', 'ICON_ID')
		))->fetchAll();
	}

	private function checkAdminPermissions()
	{
		if (!static::isAdmin())
		{
			throw new AccessException('Admin permissions required');
		}
	}

	private function isAdmin()
	{
		global $USER;
		return (
			isset($USER)
			&& is_object($USER)
			&& (
				$USER->isAdmin()
				|| Loader::includeModule('bitrix24') && \CBitrix24::isPortalAdmin($USER->getID())
			)
		);
	}
}