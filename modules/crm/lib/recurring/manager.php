<?php
namespace Bitrix\Crm\Recurring;

use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use CCrmOwnerType;

Loc::loadMessages(__FILE__);

class Manager
{
	public const INVOICE = 'Invoice';
	public const DYNAMIC = 'Dynamic';
	public const DEAL = 'Deal';

	public const SINGLE_EXECUTION = 1;
	public const SINGLE_EXECUTION_NAME = 'single';
	public const MULTIPLY_EXECUTION = 2;
	public const MULTIPLY_EXECUTION_NAME = 'multiple';

	private const ENTITY_TYPE_LIST = [
		CCrmOwnerType::Invoice => self::INVOICE,
		CCrmOwnerType::SmartInvoice => self::DYNAMIC,
		CCrmOwnerType::Deal => self::DEAL,
	];

	public static function getEntityTypeList(): array
	{
		return array_values(self::ENTITY_TYPE_LIST);
	}

	public static function isAvailableEntityTypeId(int $entityTypeId): bool
	{
		if (isset(self::ENTITY_TYPE_LIST[$entityTypeId]))
		{
			return true;
		}

		return CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId);
	}

	/**
	 * Create a new recurring entity.
	 *
	 * @param $typeEntity
	 * @param $entityFields
	 * @param $recurringParams
	 *
	 * @return Result
	 */
	public static function createEntity(array $entityFields, array $recurringParams, $typeEntity = self::INVOICE)
	{
		return Command::execute($typeEntity, __FUNCTION__, array($entityFields, $recurringParams));
	}

	/**
	 * Update a recurring entity.
	 *
	 * @param $typeEntity
	 * @param $primary
	 * @param $data
	 *
	 * @return Result
	 */
	public static function update($primary, array $data, $typeEntity = self::INVOICE)
	{
		return Command::execute($typeEntity, __FUNCTION__, array($primary, $data));
	}

	/**
	 * Creating new entities by recurring template entities. Filter is used for filtering from {EntityType}RecurTable
	 *
	 * @param $typeEntity
	 * @param $filter
	 * @param $limit
	 *
	 * @return Result
	 */
	public static function expose(array $filter = array(), $limit = null, $typeEntity = self::INVOICE)
	{
		return Command::execute($typeEntity, __FUNCTION__, array($filter, $limit));
	}


	/**
	 * Deactivate recurring entity
	 *
	 * @param $entityId
	 * @param $typeEntity.			Entity type from class constants. Default is INVOICE for compatibility
	 *
	 * @return Main\Result
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	public static function deactivate($entityId, $typeEntity = self::INVOICE)
	{
		return Command::execute($typeEntity, __FUNCTION__, array($entityId));
	}

	/**
	 * Activate recurring entity
	 *
	 * @param $entityId
	 * @param $typeEntity.			Entity type from class constants. Default is INVOICE for compatibility.
	 *
	 * @return Main\Result
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	public static function activate($entityId, $typeEntity = self::INVOICE)
	{
		return Command::execute($typeEntity, __FUNCTION__, array($entityId));
	}

	/**
	 * @param $entityId
	 * @param string $reason
	 * @param $typeEntity.			Entity type. Default is INVOICE for	compatibility.
	 *
	 * @return Main\Result
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	public static function cancel($entityId, $reason = "", $typeEntity = self::INVOICE)
	{
		return Command::execute($typeEntity, __FUNCTION__, array($entityId, $reason));
	}

	/**
	 * @param $params
	 * @param $typeEntity.			Entity type. Default is INVOICE for	compatibility.
	 *
	 * @return Main\DB\Result
	 */
	public static function getList(array $params = array(), $typeEntity = self::INVOICE)
	{
		return Command::execute($typeEntity, __FUNCTION__, array($params));
	}

	/**
	 * Check an ability of new entities creation.
	 *
	 * @param $typeEntity
	 *
	 * @return bool
	 */
	public static function isAllowedExpose($typeEntity)
	{
		$result = Command::execute($typeEntity, __FUNCTION__);
		if ($result instanceof Result)
		{
			return $result->isSuccess();
		}

		return $result;
	}

	/**
	 * Start controlling agent.
	 * @param $typeEntity
	 *
	 * @return string
	 */
	public static function initCheckAgent($typeEntity = self::INVOICE)
	{
		$agentData = \CAgent::GetList(
			array("ID"=>"DESC"),
			array(
				"MODULE_ID" => "crm",
				"NAME" => "\\".__CLASS__."::checkAgent();"
			)
		);

		$agent = $agentData->Fetch();

		if (!($agent))
		{
			$tomorrow = DateTime::createFromTimestamp(strtotime('tomorrow 00:01:00'));
			\CAgent::AddAgent("\\".__CLASS__."::checkAgent();", "crm", "N", 86400, "", "Y", $tomorrow->toString());
		}

		static::exposeAgent($typeEntity);

		return static::checkAgent();
	}

	/**
	 * Control of exposing agent.
	 *
	 * @return string
	 */
	public static function checkAgent()
	{
		$agentNames = array();
		$listActive = array();

		$entities = self::getEntityTypeList();
		foreach ($entities as $typeEntity)
		{
			if (self::isAllowedExpose($typeEntity))
			{
				$agentNames[$typeEntity] = "\\".__CLASS__."::exposeAgent('".$typeEntity."');";
			}
		}

		if (empty($agentNames))
		{
			return '';
		}

		$agentData = \CAgent::GetList(
			array("ID"=>"DESC"),
			array(
				"MODULE_ID" => "crm",
				"NAME" => "\\".__CLASS__."::exposeAgent(%"
			)
		);

		while ($agent = $agentData->Fetch())
		{
			if ($agent['LAST_EXEC'] < $agent['NEXT_EXEC'])
			{
				$listActive[$agent['NAME']] = $agent['ID'];
			}
			else
			{
				\CAgent::Delete($agent['ID']);
			}
		}

		foreach ($agentNames as $name)
		{
			if (empty($listActive[$name]))
			{
				\CAgent::AddAgent($name, "crm", "N", 180, "", "Y");
			}
		}

		$currentAgentData = \CAgent::GetList(
			array(),
			array(
				"MODULE_ID" => "crm",
				"NAME" => "\\".__CLASS__."::checkAgent(%"
			)
		);

		if ($currentAgent = $currentAgentData->Fetch())
		{
			\CAgent::Delete($currentAgent['ID']);
			$nextCheckDate = DateTime::createFromTimestamp(strtotime('tomorrow 00:01:00'));
			\CAgent::AddAgent("\\".__CLASS__."::checkAgent();", "crm", "N", 86400, "", "Y", $nextCheckDate->toString());
		}

		return '';
	}

	/**
	 * Create new entities in agent. By default limit of exposing is 10 entities by hit.
	 *
	 * @param $typeEntity. 		Entity type from class constants. Default is INVOICE for compatibility.
	 *
	 * @return string
	 */
	public static function exposeAgent($typeEntity = self::INVOICE)
	{
		global $USER;

		@set_time_limit(0);

		/** @var /Bitrix/Crm/Recurring/Entity/Base $entity */
		$entity = Command::loadEntity($typeEntity);

		if (!$entity || !$entity->isAllowedExpose())
			return '';

		$limit = Main\Config\Option::get('crm', 'day_limit_exposing_invoices', 10);

		$params = [
			'select' => ['ID'],
			'filter' => self::getTodayFilter(),
			'runtime' => $entity->getRuntimeTemplateField(),
		];

		$todayEntities = $entity->getList($params);
		$todayCount = count($todayEntities->fetchAll());

		if ($todayCount > 0)
		{
			if (!(isset($USER) && $USER instanceof \CUser))
			{
				$USER = new \CUser();
			}

			static::exposeToday($limit, $typeEntity);
		}
		else
		{
			return '';
		}

		return "\\".__CLASS__."::exposeAgent('".$typeEntity."');";
	}

	/**
	 * @param $limit
	 * @param $typeEntity.		Entity type from class constants. Default is INVOICE for compatibility.
	 *
	 * @return Main\Result
	 */
	public static function exposeToday($limit = null, $typeEntity = self::INVOICE)
	{
		$today = new Date();
		return static::expose(
			self::getTodayFilter(),
			$limit,
			$typeEntity
		);
	}

	protected static function getTodayFilter(): array
	{
		$today = new Date();

		return [
			'<=NEXT_EXECUTION' => $today,
			[
				'LOGIC' => 'OR',
				['LAST_EXECUTION' => null],
				['<LAST_EXECUTION' => $today],
			],
			'=ACTIVE' => 'Y',
		];
	}

	/**
	 * @param $limit
	 *
	 * @return Main\Result
	 * @deprecated
	 */
	public static function exposeTodayInvoices($limit = null)
	{
		return static::exposeToday($limit, self::INVOICE);
	}

	/**
	 * Create recurring invoice
	 *
	 * @param array $invoiceFields
	 * @param array $recurParams
	 *
	 * @return Main\Result
	 * @throws \Exception
	 * @deprecated
	 */
	public static function createInvoice(array $invoiceFields, array $recurParams)
	{
		return static::createEntity($invoiceFields, $recurParams, self::INVOICE);
	}

	/**
	 * Update recurring invoice
	 *
	 * @param int $primary
	 * @param array $data
	 *
	 * @return Result
	 * @throws \Exception
	 * @deprecated
	 */
	public static function updateRecurring($primary, array $data)
	{
		return static::update($primary, $data, self::INVOICE);
	}

	/**
	 * Create new invoices from recurring invoices. Invoices's selection is from InvoiceRecurTable.
	 *
	 * @param $filter
	 * @param $limit
	 *
	 * @return Main\Result
	 * @throws Main\ArgumentException
	 * @deprecated
	 */
	public static function exposeInvoices($filter, $limit = null)
	{
		return static::expose($filter, $limit, self::INVOICE);
	}

	/**
	 * Check date of next invoicing by params.
	 *
	 * @param $params
	 * @return bool
	 * @deprecated
	 */
	public static function isActiveExecutionDate($params)
	{
		$nextTimeStamp = ($params['NEXT_EXECUTION'] instanceof Date) ? $params['NEXT_EXECUTION']->getTimestamp() : 0;
		$endTimeStamp = ($params['LIMIT_DATE'] instanceof Date) ? $params['LIMIT_DATE']->getTimestamp() : 0;

		if ($params['IS_LIMIT'] === Entity\Base::LIMITED_BY_TIMES)
			return (int)$params['LIMIT_REPEAT'] > (int)$params['COUNTER_REPEAT'];
		elseif ($params['IS_LIMIT'] === Entity\Base::LIMITED_BY_DATE)
			return $nextTimeStamp < $endTimeStamp;

		return false;
	}

	public static function getEntityListTitle(int $entityTypeId): ?string
	{
		if (!self::isAvailableEntityTypeId($entityTypeId))
		{
			return null;
		}

		Container::getInstance()->getLocalization()->loadMessages();

		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);

		return
			Loc::getMessage('CRM_RECURRING_LIST_TITLE_' . $entityTypeName)
			?? Loc::getMessage('CRM_RECURRING_LIST_TITLE');
	}
}