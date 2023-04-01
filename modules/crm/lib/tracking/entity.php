<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;

use Bitrix\Sale;

use Bitrix\Crm;
use Bitrix\Crm\Tracking;
//use Bitrix\Crm\Entity\Identificator;

/**
 * Class Entity
 *
 * @package Bitrix\Crm\Tracking
 */
class Entity
{
	/**
	 * Get paths.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @param int $limit Limit.
	 * @return array
	 */
	public static function getPaths($entityTypeId, $entityId, $limit = 10)
	{
		$paths = [];
		$rows = Internals\TraceEntityTable::getRowsByEntity($entityTypeId, $entityId, $limit);
		if (empty($rows))
		{
			return $paths;
		}

		static $actualSources = null;
		if ($actualSources === null)
		{
			$actualSources = Tracking\Provider::getActualSources();
			$actualSources = array_combine(
				array_column($actualSources, 'ID'),
				array_values($actualSources)
			);
		}

		$traceIds = array_column($rows, 'TRACE_ID');
		$traces = Internals\TraceTable::getList([
			'select' => ['ID', 'SOURCE_ID', 'DATE_CREATE'],
			'filter' => ['=ID' => $traceIds],
			'order' => ['DATE_CREATE' => 'DESC'],
		])->fetchAll();
		foreach ($traces as $index => $trace)
		{
			$trace['SOURCE_ID'] = $trace['SOURCE_ID'] ?: 0;

			// skip traces without source, except first
			if ($index > 0 && !$trace['SOURCE_ID'])
			{
				unset($traces[$index]);
			}

			if (!isset($actualSources[$trace['SOURCE_ID']]))
			{
				unset($traces[$index]);
			}

			$traceId = $trace['ID'];
			$source = $actualSources[$trace['SOURCE_ID']];
			$paths[$traceId] = [
				'TRACE_ID' => $traceId,
				'DATE_CREATE' => $trace['DATE_CREATE'],
				'LIST' => []
			];
			if ($source)
			{
				$paths[$traceId]['LIST'][] = [
					'NAME' => $source['NAME'],
					'DESC' => $source['DESCRIPTION'],
					'ICON' => $source['ICON_CLASS'],
					'ICON_COLOR' => $source['ICON_COLOR'],
					'IS_SOURCE' => true,
					'SOURCE_ID' => $trace['SOURCE_ID'],
				];
			}
		}

		$channels = [];
		$channelsResult = Internals\TraceChannelTable::getList([
			'select' => ['TRACE_ID', 'CODE', 'VALUE'],
			'filter' => [
				'=TRACE_ID' => $traceIds
			],
			'order' => ['ID' => 'ASC']
		]);
		foreach ($channelsResult as $channel)
		{
			if (!is_array($channels[$channel['TRACE_ID']] ?? null))
			{
				$channels[$channel['TRACE_ID']] = [];
			}
			$channels[$channel['TRACE_ID']][] = $channel;
		}

		foreach ($channels as $traceId => $items)
		{
			$collection = new Channel\Collection();
			foreach ($items as $row)
			{
				$collection->addChannel($row['CODE'], $row['VALUE']);
			}

			foreach ($collection as $channel)
			{
				if (!is_array($paths[$traceId]))
				{
					$paths[$traceId] = [];
				}

				/** @var Tracking\Channel\Base $channel */
				$paths[$traceId]['LIST'][] = [
					'NAME' => $channel->getName(),
					'DESC' => $channel->getDescription(),
					'ICON' => null,
					'ICON_COLOR' => null,
					'IS_SOURCE' => false,
				];
			}
		}

		usort(
			$paths,
			function (array $a, array $b)
			{
				$a = $a['DATE_CREATE'] instanceof DateTime
					? $a['DATE_CREATE']->getTimestamp()
					: 0;
				$b = $b['DATE_CREATE'] instanceof DateTime
					? $b['DATE_CREATE']->getTimestamp()
					: 0;

				return ($a > $b) ? -1 : 1;
			}
		);

		return $paths;
	}

	/**
	 * Event handler of after entity adding.
	 *
	 * @param int $entityTypeId Entity Type ID.
	 * @param int $entityId Entity ID.
	 * @param array $fields New Entity Type ID.
	 * @return void
	 */
	public static function onAfterAdd($entityTypeId, $entityId, array $fields)
	{
		$allowedEntityTypes = [
			\CCrmOwnerType::Contact, \CCrmOwnerType::Company,
			\CCrmOwnerType::Lead, \CCrmOwnerType::Deal, \CCrmOwnerType::Quote,
		];
		if (!$entityTypeId || !$entityId || !in_array($entityTypeId, $allowedEntityTypes, true))
		{
			return;
		}

		// add utm
		Crm\UtmTable::addEntityUtmFromFields($entityTypeId, $entityId, $fields);

		// track orders in deals
		self::traceOrderFromFields($entityTypeId, $entityId, $fields);

		// check attribution window
		self::checkAttrWindow($entityTypeId, $entityId, $fields);
	}

	public static function onSaleOrderSaved(Main\Event $event)
	{
		$order = $event->getParameter('ENTITY');
		/** @var Crm\Order\Order $order */

		if (!$order->isNew())
		{
			return;
		}

		self::traceLocalOrder($order->getId());
	}

	public static function onLocalShopChannelSaved()
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return;
		}

		Main\EventManager::getInstance()->registerEventHandler(
			'sale',
			Sale\EventActions::EVENT_ON_ORDER_SAVED,
			'crm',
			self::class,
			'onSaleOrderSaved'
		);
	}

	/**
	 * Event handler of entity updating by REST .
	 *
	 * @param int $entityTypeId Entity Type ID.
	 * @param int $entityId Entity ID.
	 * @param array $fields New Entity Type ID.
	 * @return void
	 */
	public static function onRestAfterUpdate($entityTypeId, $entityId, array $fields)
	{
		if (!$entityTypeId || !$entityId)
		{
			return;
		}

		$allowedTypes = [
			\CCrmOwnerType::Lead, \CCrmOwnerType::Contact, \CCrmOwnerType::Company,
			\CCrmOwnerType::Deal, \CCrmOwnerType::Quote,
		];
		if (!in_array($entityTypeId, $allowedTypes, true))
		{
			return;
		}

		self::processRestUtm($fields);

		$traceId = null;
		if (!empty($fields['TRACE']) && is_numeric($fields['TRACE']))
		{
			$traceId = (int) $fields['TRACE'];
		}

		if (!$traceId)
		{
			$trace = Tracking\Trace::create($fields['TRACE'] ?? null);
			foreach (Crm\UtmTable::getCodeList() as $utmCode)
			{
				if (!empty($fields[$utmCode]))
				{
					$trace->addUtm($utmCode, $fields[$utmCode]);
				}
			}

			if ($trace->getSourceId())
			{
				$previousTraceData = Tracking\Internals\TraceTable::getTraceByEntity($entityTypeId, $entityId);
				if (!$previousTraceData || $previousTraceData['SOURCE_ID'] != $trace->getSourceId())
				{
					$traceId = $trace->save();
				}
			}
		}

		if ($traceId)
		{
			Tracking\Trace::appendChannel($traceId, new Tracking\Channel\Rest());
			Tracking\Trace::appendEntity($traceId, $entityTypeId, $entityId);
		}
	}

	/**
	 * Event handler of entity adding by REST .
	 *
	 * @param int $entityTypeId Entity Type ID.
	 * @param int $entityId Entity ID.
	 * @param array $fields New Entity Type ID.
	 * @return void
	 */
	public static function onRestAfterAdd($entityTypeId, $entityId, array $fields)
	{
		if (!$entityTypeId || !$entityId)
		{
			return;
		}

		$allowedTypes = [
			\CCrmOwnerType::Lead, \CCrmOwnerType::Contact, \CCrmOwnerType::Company,
			\CCrmOwnerType::Deal, \CCrmOwnerType::Quote,
		];
		if (!in_array($entityTypeId, $allowedTypes, true))
		{
			return;
		}

		self::processRestUtm($fields);

		$traceId = Tracking\Internals\TraceTable::getTraceIdByEntity($entityTypeId, $entityId);
		if (!$traceId && is_numeric($fields['TRACE']))
		{
			$traceId = (int) $fields['TRACE'];
			$traceId = Tracking\Internals\TraceTable::getRowById($traceId)
				? (int) $fields['TRACE']
				: null;
		}
		if (!$traceId)
		{
			$trace = Tracking\Trace::create(isset($fields['TRACE']) ? $fields['TRACE'] : null);
			foreach (Crm\UtmTable::getCodeList() as $utmCode)
			{
				if (!empty($fields[$utmCode]))
				{
					$trace->addUtm($utmCode, $fields[$utmCode]);
				}
			}
			$traceId = $trace->save();
		}

		if ($traceId)
		{
			Tracking\Trace::appendChannel($traceId, new Tracking\Channel\Rest());
			Tracking\Trace::appendEntity($traceId, $entityTypeId, $entityId);
			if (in_array($entityTypeId, [\CCrmOwnerType::Deal], true))
			{
				if (!empty($fields['CONTACT_ID']))
				{
					Tracking\Trace::appendEntity($traceId, \CCrmOwnerType::Contact, (int) $fields['CONTACT_ID']);
				}
				if (!empty($fields['COMPANY_ID']))
				{
					Tracking\Trace::appendEntity($traceId, \CCrmOwnerType::Contact, (int) $fields['COMPANY_ID']);
				}
			}
		}
	}

	/**
	 * Unbind tracing data from old entity and bind them to new entity.
	 *
	 * @param int $oldEntityTypeID Old Entity Type ID.
	 * @param int $oldEntityID Old Entity ID.
	 * @param int $newEntityTypeID New Entity Type ID.
	 * @param int $newEntityID New Entity ID.
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function rebindTrace($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		Internals\TraceEntityTable::rebind($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID);
	}

	/*
	 * Delete trace from entity.
	 *
	 * @param int $fromEntityTypeId From entity type ID.
	 * @param int $fromEntityId From entity ID.
	 * @return void
	 */
	public static function deleteTrace($entityTypeId, $entityId)
	{
		Internals\TraceEntityTable::removeEntity($entityTypeId, $entityId);
	}

	/**
	 * Copy trace from entity to entity.
	 *
	 * @param int $fromEntityTypeId From entity type ID.
	 * @param int $fromEntityId From entity ID.
	 * @param int $toEntityTypeId To entity type ID.
	 * @param int $toEntityId To entity ID.
	 * @return void
	 */
	public static function copyTrace($fromEntityTypeId, $fromEntityId, $toEntityTypeId, $toEntityId)
	{
		$row = Internals\TraceEntityTable::getRowByEntity($fromEntityTypeId, $fromEntityId);
		if (!$row)
		{
			return;
		}

		Internals\TraceEntityTable::addEntity($row['TRACE_ID'], $toEntityTypeId, $toEntityId);
	}

	/**
	 * Trace local order.
	 *
	 * @param int $orderId Order ID.
	 * @return void
	 */
	protected static function traceLocalOrder($orderId)
	{
		self::traceOrder($orderId, \CCrmOwnerType::Order, $orderId);
	}

	protected static function traceOrder($orderId, $entityTypeId, $entityId)
	{
		if (!$orderId || !$entityId || !$entityTypeId)
		{
			return;
		}

		$traceId = Internals\TraceTable::getSpareTraceIdByChannel(
			Tracking\Channel\Base::Order,
			$orderId,
			(new DateTime())->add('-' . Tracking\Trace::FIND_ENTITIES_TIME_DAYS . ' days')
		);
		if (!$traceId)
		{
			return;
		}

		Tracking\Trace::appendEntity($traceId, $entityTypeId, $entityId);
	}

	protected static function traceOrderFromFields($entityTypeId, $entityId, $fields)
	{
		if (!in_array($entityTypeId, [\CCrmOwnerType::Deal], true))
		{
			return;
		}

		$hasOriginator = !empty($fields['ORIGINATOR_ID']) && $fields['ORIGINATOR_ID'] === 'bitrix.cms.sync';
		if ($hasOriginator && !empty($fields['ORIGIN_ID']))
		{
			$orderId = $fields['ORIGIN_ID'];
			$orderId = is_string($orderId) || is_integer($orderId)
				? trim((string)$orderId)
				: null
			;
		}
		else
		{
			$fieldName = Tracking\Channel\Order::getDealField();
			$orderId = ($fieldName && isset($fields[$fieldName]))
				? $fields[$fieldName]
				: null
			;
			$orderId = is_array($orderId)
				? current($orderId)
				: $orderId
			;
			$orderId = is_string($orderId) || is_integer($orderId)
				? trim((string)$orderId)
				: null
			;
		}


		if ($orderId)
		{
			self::traceOrder($orderId, $entityTypeId, $entityId);
		}
	}

	protected static function checkAttrWindow($entityTypeId, $entityId, $fields)
	{
		if (!in_array($entityTypeId, [\CCrmOwnerType::Deal, \CCrmOwnerType::Lead], true))
		{
			return;
		}

		// only clean entities, not tracing
		if (!empty($fields['UTM_SOURCE']))
		{
			return;
		}

		// check option in settings
		if (!Settings::isAttrWindowOffline())
		{
			return;
		}

		if (!empty($fields['CONTACT_ID']))
		{
			$queryEntityTypeId = \CCrmOwnerType::Contact;
			$queryEntityId = $fields['CONTACT_ID'];
		}
		elseif (!empty($fields['COMPANY_ID']))
		{
			$queryEntityTypeId = \CCrmOwnerType::Company;
			$queryEntityId = $fields['CONTACT_ID'];
		}
		else
		{
			return;
		}

		// fetch last trace with source ID
		$row = Tracking\Internals\TraceTable::getTraceByEntity($queryEntityTypeId, $queryEntityId);
		if (!$row || !$row['SOURCE_ID'])
		{
			return;
		}

		if (!($row['DATE_CREATE'] instanceof DateTime))
		{
			return;
		}

		// check attribution window
		$row['DATE_CREATE']->add(Settings::getAttrWindow() . ' day');
		if ($row['DATE_CREATE']->getTimestamp() < time())
		{
			return;
		}

		$traceId = Trace::create()->setSource($row['SOURCE_ID'])->save();
		Trace::appendEntity($traceId, $entityTypeId, $entityId);
	}

	protected static function processRestUtm(array $fields)
	{
		$tags = [];
		foreach (Crm\UtmTable::getCodeList() as $utmCode)
		{
			if (!empty($fields[$utmCode]))
			{
				$tags[$utmCode] = $fields[$utmCode];
			}
		}

		if (empty($tags['UTM_SOURCE']))
		{
			return;
		}

		$prefixSource1C = '1c-b24-standalone-';
		if (mb_substr($tags['UTM_SOURCE'], 0, mb_strlen($prefixSource1C)) !== $prefixSource1C)
		{
			return;
		}

		$sourceId = Tracking\Internals\SourceTable::getSourceByUtmSource($tags['UTM_SOURCE']);
		if ($sourceId)
		{
			return;
		}

		$result = Tracking\Internals\SourceTable::add([
			'NAME' => $tags['UTM_CAMPAIGN'] ?? '1C',
			'CODE' => '1c',
		]);
		$sourceId = $result->getId();
		if (!$sourceId || !$result->isSuccess())
		{
			return;
		}

		Tracking\Internals\SourceFieldTable::setSourceField(
			$sourceId,
			Tracking\Internals\SourceFieldTable::FIELD_UTM_SOURCE,
			[$tags['UTM_SOURCE']]
		);
	}
}
