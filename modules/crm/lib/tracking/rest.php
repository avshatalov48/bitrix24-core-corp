<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\Tracking;

use Bitrix\Main\Web\Json;
use Bitrix\Rest\RestException;

/**
 * Class Rest
 * @package Bitrix\Crm\WebForm
 */
class Rest
{
	/**
	 * Register bindings.
	 *
	 * @param array $bindings Rest bindings.
	 * @return void
	 */
	public static function register(array &$bindings)
	{
		$bindings['crm.tracking.trace.add'] = [__CLASS__, 'addTrace'];
		$bindings['crm.tracking.trace.delete'] = [__CLASS__, 'deleteTrace'];
	}

	/**
	 * Delete trace.
	 *
	 * @param array $query Query parameters.
	 * @param int $nav Navigation.
	 * @param \CRestServer $server Rest server.
	 * @return void
	 * @throws RestException
	 */
	public static function deleteTrace($query, $nav = 0, \CRestServer $server)
	{
		$id = empty($query['id']) ? null : (int) $query['id'];
		if (!$id)
		{
			self::printErrors(["Parameter `id` required."]);
		}

		$rows = Internals\TraceEntityTable::getList([
			'filter' => ['=TRACE_ID' => $id]
		])->fetchAll();
		$hasRights = true;
		foreach ($rows as $row)
		{
			if (!\CCrmAuthorizationHelper::CheckUpdatePermission($row['ENTITY_TYPE_ID'], $row['ENTITY_ID']))
			{
				$hasRights = false;
				continue;
			}

			Internals\TraceEntityTable::delete($row['ID']);
		}

		if ($hasRights)
		{
			Internals\TraceTable::delete($id);
		}
	}

	/**
	 * Add trace.
	 *
	 * @param array $query Query parameters.
	 * @param int $nav Navigation.
	 * @param \CRestServer $server Rest server.
	 * @return int
	 * @throws RestException
	 */
	public static function addTrace($query, $nav = 0, \CRestServer $server)
	{
		$trace = empty($query['TRACE']) ? null : $query['TRACE'];
		if (!$trace)
		{
			self::printErrors(["Parameter `TRACE` required."]);
		}

		try
		{
			Json::decode($trace);
		}
		catch (\Exception $exception)
		{
			self::printErrors(["Can not parse JSON in parameter `TRACE`."]);
		}

		$entities = isset($query['ENTITIES']) ? $query['ENTITIES'] : [];
		$entities = is_array($entities) ? $entities : [];
		$allowedEntityTypes = [
			\CCrmOwnerType::CompanyName,
			\CCrmOwnerType::ContactName,
			\CCrmOwnerType::DealName,
			\CCrmOwnerType::LeadName,
			\CCrmOwnerType::QuoteName,
		];
		foreach ($entities as $entity)
		{
			if (!isset($entity['TYPE']) || !in_array($entity['TYPE'], $allowedEntityTypes, true))
			{
				self::printErrors(["Wrong TYPE in parameter `ENTITIES`. Allowed types: " . implode(',', $allowedEntityTypes)]);
			}
			if (!isset($entity['ID']) || !is_numeric($entity['ID']) || $entity['ID'] <= 0)
			{
				self::printErrors(["Wrong ID in parameter `ENTITIES`."]);
			}

			if (!\CCrmAuthorizationHelper::CheckUpdatePermission($entity['TYPE'], $entity['ID']))
			{
				self::printErrors(["You have no access to entity `{$entity['TYPE']}` with ID `{$entity['ID']}`."]);
			}
		}

		$traceId = Trace::create($trace)->save();
		foreach ($entities as $entity)
		{
			$entityId = (int) $entity['ID'];
			$entityTypeId = \CCrmOwnerType::ResolveID($entity['TYPE']);
			Trace::appendEntity($traceId, $entityTypeId, $entityId);
		}

		return $traceId;
	}

	/**
	 * Print rest errors.
	 *
	 * @param string[] $errors Errors.
	 * @param string $errorCode Error Code.
	 * @return void
	 * @throws RestException
	 */
	protected static function printErrors(array $errors,  $errorCode = RestException::ERROR_CORE)
	{
		foreach ($errors as $error)
		{
			throw new RestException(
				$error,
				$errorCode,
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}
	}
}
