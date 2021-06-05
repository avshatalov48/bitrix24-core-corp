<?php

namespace Bitrix\Tasks\Integration\Bizproc\Automation;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\RestException;

class Rest
{
	public static function getManifest()
	{
		return(array(
			'REST: shortname alias to class' => 'automation.trigger',
			'REST: available methods' => array(
				'webhook' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'type',
							'type'        => 'string'
						),
						array(
							'description' => 'id',
							'type'        => 'string'
						),
						array(
							'description' => 'code',
							'type'        => 'string'
						)
					)
				),
				'add' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'FIELDS',
							'type'        => 'array'
						),
					)
				),
				'delete' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'CODE',
							'type'        => 'string'
						)
					)
				),
				'execute' => array(
					'mandatoryParamsCount' => 2,
					'params' => array(
						'params' => array(
							array(
								'description' => 'TASK_ID',
								'type'        => 'string'
							),
							array(
								'description' => 'CODE',
								'type'        => 'string'
							)
						)
					)
				),
				'list' => array(
					'mandatoryParamsCount' => 0,
					'params' => [],
				),
			)
		));
	}

	public static function runRestMethod($executiveUserId, $methodName, $args, $navigation, $server)
	{
		self::checkAdminPermissions();
		if ($methodName === 'webhook')
		{
			return self::executeWebHookTrigger($args);
		}
		elseif ($methodName === 'add')
		{
			return self::addTrigger($args, $server);
		}
		elseif ($methodName === 'list')
		{
			return self::getTriggerList($args, $server);
		}
		elseif ($methodName === 'delete')
		{
			return self::deleteTrigger($args, $server);
		}
		elseif ($methodName === 'execute')
		{
			return self::executeAppTrigger($args, $server);
		}
		throw new RestException("Resource '{$methodName}' is not supported in current context.");
	}

	private static function executeWebHookTrigger(array $params)
	{
		$documentType = array_shift($params);
		$taskId = array_shift($params);
		$code = array_shift($params);

		if ($documentType && $taskId)
		{
			$data = ['code' => $code];
			$result = Trigger\WebHook::execute($documentType, $taskId, $data);
			if ($result->isSuccess())
			{
				return [$result->getData()];
			}
		}

		return [false];
	}

	private static function addTrigger(array $params, $server)
	{
		/** @var \CRestServer $server */
		$clientId = $server ? $server->getClientId() : null;

		if (!$clientId)
		{
			throw new AccessException('Application context required');
		}

		$fields = array_change_key_case($params[0], CASE_UPPER);

		self::validateTriggerCode($fields['CODE']);
		self::validateTriggerName($fields['NAME']);

		$app = AppTable::getList(
			array(
				'filter' => array(
					'=CLIENT_ID' => $clientId
				),
				'select' => array('ID')
			)
		)->fetch();

		$exists = Trigger\Entity\AppTable::getList(array(
			'filter' => array(
				'=APP_ID' => $app['ID'],
				'=CODE' => $fields['CODE']
			),
			'select' => array('ID')
		))->fetch();

		if ($exists)
		{
			$updateResult = Trigger\Entity\AppTable::update($exists['ID'], array('NAME' => $fields['NAME']));
			return [$updateResult->isSuccess()];
		}

		$fields = array(
			'APP_ID' => $app['ID'],
			'CODE' => $fields['CODE'],
			'NAME' => $fields['NAME'],
			'DATE_CREATE' => new DateTime()
		);

		$addResult = Trigger\Entity\AppTable::add($fields);

		return [$addResult->isSuccess()];
	}

	private static function deleteTrigger(array $params, $server)
	{
		/** @var \CRestServer $server */
		$clientId = $server ? $server->getClientId() : null;

		if (!$clientId)
		{
			throw new AccessException('Application context required');
		}

		$code = $params[0];

		self::validateTriggerCode($code);

		$app = AppTable::getList(
			array(
				'filter' => array(
					'=CLIENT_ID' => $clientId
				),
				'select' => array('ID')
			)
		)->fetch();

		$exists = Trigger\Entity\AppTable::getList(array(
			'filter' => array(
				'=APP_ID' => $app['ID'],
				'=CODE' => $code
			)
		))->fetch();

		if (!$exists)
		{
			throw new RestException('Trigger not found');
		}

		$deleteResult = Trigger\Entity\AppTable::delete($exists['ID']);

		return [$deleteResult->isSuccess()];
	}

	private static function getTriggerList(array $params, $server)
	{
		/** @var \CRestServer $server */
		$clientId = $server ? $server->getClientId() : null;

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

		return [
			Trigger\Entity\AppTable::getList(array(
			'filter' => array(
				'=APP_ID' => $app['ID'],
			),
			'select' => array('NAME', 'CODE')
			))->fetchAll()
		];
	}

	private static function executeAppTrigger(array $params, $server)
	{
		/** @var \CRestServer $server */
		$clientId = $server ? $server->getClientId() : null;

		if (!$clientId)
		{
			throw new AccessException('Application context required');
		}

		$code = $params[0];
		self::validateTriggerCode($code);

		$app = AppTable::getList(
			array(
				'filter' => array(
					'=CLIENT_ID' => $clientId
				),
				'select' => array('ID')
			)
		)->fetch();

		$appTrigger = Trigger\Entity\AppTable::getList(array(
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

		$taskId = (int) $params[1];
		if ($taskId <= 0)
		{
			throw new RestException("Incorrect parameter TASK_ID.");
		}

		$result = Trigger\App::execute($taskId, ['APP_ID' => $app['ID'], 'CODE' => $code]);

		if ($result->isSuccess())
		{
			return [$result->getData()];
		}

		return [false];
	}

	private static function checkAdminPermissions()
	{
		if (!static::isAdmin())
		{
			throw new AccessException('Admin permissions required');
		}
	}

	private static function isAdmin()
	{
		global $USER;
		return (
			isset($USER)&&
			is_object($USER) &&
			(
				$USER->isAdmin() ||
				(
					Loader::includeModule('bitrix24') &&
					\CBitrix24::isPortalAdmin($USER->getID())
				)
			)
		);
	}

	private static function validateTriggerName($name)
	{
		if (empty($name))
		{
			throw new RestException('Empty trigger name!');
		}
	}

	private static function validateTriggerCode($code)
	{
		if (empty($code))
		{
			throw new RestException('Empty trigger code!');
		}
		if (!preg_match('#^[a-z0-9\.\-_]+$#i', $code))
		{
			throw new RestException('Wrong trigger code!');
		}
	}
}
