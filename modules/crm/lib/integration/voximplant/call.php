<?php

namespace Bitrix\Crm\Integration\VoxImplant;


use Bitrix\Main\Loader;
use Bitrix\Crm\ItemIdentifier;

class Call
{
	private ?array $callStatistic = null;
	public function __construct(private readonly string $callId)
	{

	}

	/**
	 * @return ItemIdentifier[]
	 */
	public function getCrmEntities(): array
	{
		$result = [];

		$call = $this->getVoximplantCall();
		if ($call)
		{
			$crmEntities = $call->getCrmEntities();
		}
		else
		{
			$callStatistic = $this->getVoximplantCallStatistic();
			if (empty($callStatistic))
			{
				return [];
			}
			$crmEntities = $callStatistic['CRM_ENTITIES'];
		}

		foreach ($crmEntities as $crmEntity)
		{
			$identifier = ItemIdentifier::createByParams(\CCrmOwnerType::ResolveID($crmEntity['ENTITY_TYPE']), $crmEntity['ENTITY_ID']);
			if ($identifier)
			{
				$result[] = $identifier;
			}
		}

		return $result;
	}

	public function getDirection(): int
	{
		$call = $this->getVoximplantCall();
		if ($call)
		{
			$callDirectionType = (int)$call->getIncoming();
		}
		else
		{
			$callStatistic = $this->getVoximplantCallStatistic();
			if (empty($callStatistic))
			{
				return \CCrmActivityDirection::Undefined;
			}
			$callDirectionType = (int)$callStatistic['INCOMING'];
		}

		return match($callDirectionType)
		{
			\CVoxImplantMain::CALL_OUTGOING => \CCrmActivityDirection::Outgoing,
			\CVoxImplantMain::CALL_INCOMING => \CCrmActivityDirection::Incoming,
			\CVoxImplantMain::CALL_INCOMING_REDIRECT => \CCrmActivityDirection::Incoming,
			\CVoxImplantMain::CALL_CALLBACK => \CCrmActivityDirection::Incoming,
			\CVoxImplantMain::CALL_INFO => \CCrmActivityDirection::Outgoing,
			default => \CCrmActivityDirection::Undefined,
		};
	}

	private function getVoximplantCall(): ?\Bitrix\Voximplant\Call
	{
		if (!Loader::includeModule('voximplant'))
		{
			return null;
		}
		$call = \Bitrix\Voximplant\Call::load($this->callId);
		if (!$call)
		{
			return null;
		}

		return $call;
	}

	private function getVoximplantCallStatistic(): array
	{
		if (!Loader::includeModule('voximplant'))
		{
			return [];
		}

		if (is_null($this->callStatistic))
		{
			$this->callStatistic = [];
			$callStatistic = \Bitrix\Voximplant\StatisticTable::query()
				->where('CALL_ID', $this->callId)
				->setSelect([
					'ID',
					'INCOMING',
					'CRM_BINDINGS'
				])
				->setLimit(1)
				->fetchObject()
			;
			if ($callStatistic)
			{
				$bindings = [];
				foreach ($callStatistic->getCrmBindings() as $binding)
				{
					$bindings[] = [
						'ENTITY_TYPE' => $binding->getEntityType(),
						'ENTITY_ID' => $binding->getEntityId(),
					];
				}
				$this->callStatistic = [
					'INCOMING' => $callStatistic->getIncoming(),
					'CRM_ENTITIES' => $bindings,
				];
			}
		}

		return $this->callStatistic;
	}
}
