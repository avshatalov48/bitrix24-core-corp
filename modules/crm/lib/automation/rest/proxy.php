<?php
namespace Bitrix\Crm\Automation\Rest;

use \Bitrix\Crm\Automation;
use \Bitrix\Crm\Automation\Trigger\Entity\TriggerAppTable;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\RestException;

class Proxy implements \ICrmRestProxy
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
		$name = strtoupper($name);
		if ($name === 'TRIGGER')
		{
			$triggerAction = isset($nameDetails[0]) ? strtolower($nameDetails[0]) : 'webhook';

			if ($triggerAction === 'webhook')
			{
				return $this->executeWebHookTrigger($params);
			}
			elseif ($triggerAction === 'add')
			{
				return $this->addTrigger($params);
			}
			elseif ($triggerAction === 'list')
			{
				return $this->getTriggerList($params);
			}
			elseif ($triggerAction === 'delete')
			{
				return $this->deleteTrigger($params);
			}
			elseif ($triggerAction === 'execute')
			{
				return $this->executeAppTrigger($params);
			}
		}
		throw new RestException("Resource '{$name}' is not supported in current context.");
	}

	private function executeWebHookTrigger(array $params)
	{
		if (isset($params['target']))
		{
			$pairs = explode('_', $params['target']);
			if (count($pairs) > 1)
			{
				$entityTypeId = \CCrmOwnerType::ResolveID($pairs[0]);
				$entityId = (int)$pairs[1];

				if ($entityTypeId && $entityId)
				{
					if (Automation\Trigger\WebHookTrigger::canExecute($entityTypeId, $entityId))
					{
						$data = array();
						if (isset($params['code']))
						{
							$data['code'] = (string)$params['code'];
						}

						Automation\Trigger\WebHookTrigger::execute(array(array(
							'OWNER_TYPE_ID' => $entityTypeId,
							'OWNER_ID' => $entityId
						)), $data);
					}
					else
						throw new AccessException('There is no permissions to update the entity.');
				}
				else
					throw new RestException("Target is not found.");
			}
			else
				throw new RestException("Incorrect target format.");
		}
		else
			throw new RestException("Target is not set.");

		return true;
	}

	private function addTrigger(array $params)
	{
		$this->checkAdminPermissions();
		$clientId = $this->getServer()->getClientId();

		if (!$clientId)
		{
			throw new AccessException('Application context required');
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$this->validateTriggerCode($params['CODE']);
		$this->validateTriggerName($params['NAME']);

		$app = AppTable::getList(
			array(
				'filter' => array(
					'=CLIENT_ID' => $clientId
				),
				'select' => array('ID')
			)
		)->fetch();

		$exists = TriggerAppTable::getList(array(
			'filter' => array(
				'=APP_ID' => $app['ID'],
				'=CODE' => $params['CODE']
			),
			'select' => array('ID')
		))->fetch();

		if ($exists)
		{
			$updateResult = TriggerAppTable::update($exists['ID'], array('NAME' => $params['NAME']));
			return $updateResult->isSuccess();
		}

		$fields = array(
			'APP_ID' => $app['ID'],
			'CODE' => $params['CODE'],
			'NAME' => $params['NAME'],
			'DATE_CREATE' => new DateTime()
		);

		$addResult = TriggerAppTable::add($fields);

		return $addResult->isSuccess();
	}

	private function deleteTrigger(array $params)
	{
		$this->checkAdminPermissions();
		$clientId = $this->getServer()->getClientId();

		if (!$clientId)
		{
			throw new AccessException('Application context required');
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$this->validateTriggerCode($params['CODE']);

		$app = AppTable::getList(
			array(
				'filter' => array(
					'=CLIENT_ID' => $clientId
				),
				'select' => array('ID')
			)
		)->fetch();

		$exists = TriggerAppTable::getList(array(
			'filter' => array(
				'=APP_ID' => $app['ID'],
				'=CODE' => $params['CODE']
			)
		))->fetch();

		if (!$exists)
		{
			throw new RestException('Trigger not found');
		}

		$deleteResult = TriggerAppTable::delete($exists['ID']);

		return $deleteResult->isSuccess();
	}

	private function getTriggerList(array $params)
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

		return TriggerAppTable::getList(array(
			'filter' => array(
				'=APP_ID' => $app['ID'],
			),
			'select' => array('NAME', 'CODE')
		))->fetchAll();
	}

	private function executeAppTrigger(array $params)
	{
		$this->checkAdminPermissions();
		$clientId = $this->getServer()->getClientId();

		if (!$clientId)
		{
			throw new AccessException('Application context required');
		}

		$params = array_change_key_case($params, CASE_UPPER);
		$code = $params['CODE'];
		$this->validateTriggerCode($code);

		$app = AppTable::getList(
			array(
				'filter' => array(
					'=CLIENT_ID' => $clientId
				),
				'select' => array('ID')
			)
		)->fetch();

		$appTrigger = TriggerAppTable::getList(array(
			'filter' => array(
				'=APP_ID' => $app['ID'],
				'=CODE' => $code
			),
			'select' => array('ID')
		))->fetch();

		if (!$appTrigger)
		{
			throw new RestException("Trigger with code {$code} is not registered.");
		}

		$ownerTypeId = (int)$params['OWNER_TYPE_ID'];
		if (!\CCrmOwnerType::IsDefined($ownerTypeId))
		{
			throw new RestException("Incorrect parameter OWNER_TYPE_ID.");
		}
		$ownerId = (int)$params['OWNER_ID'];
		if ($ownerId <= 0)
		{
			throw new RestException("Incorrect parameter OWNER_ID.");
		}

		Automation\Trigger\AppTrigger::execute(array(
				array(
					'OWNER_TYPE_ID' => $ownerTypeId,
					'OWNER_ID' => $ownerId
				)
			),
			array(
				'APP_ID' => $app['ID'],
				'CODE' => $code
			)
		);

		return true;
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

	private function validateTriggerName($name)
	{
		if (empty($name))
			throw new RestException('Empty trigger name!');
	}

	private function validateTriggerCode($code)
	{
		if (empty($code))
			throw new RestException('Empty trigger code!');
		if (!preg_match('#^[a-z0-9\.\-_]+$#i', $code))
			throw new RestException('Wrong trigger code!');
	}
}