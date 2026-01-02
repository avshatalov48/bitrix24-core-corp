<?php

namespace Bitrix\Crm\Integration\VoxImplant;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Loader;
use Bitrix\Rest\AppTable;
use Bitrix\Voximplant;
use CCrmActivityDirection;
use CCrmOwnerType;
use CVoxImplantConfig;
use CVoxImplantMain;

final class Call
{
	private const TELEPHONY_TYPE_UNDEFINED = 'undefined';
	private const TELEPHONY_TYPE_REST = 'rest';
	private const TELEPHONY_TYPE_SIP = 'sip';
	private const TELEPHONY_TYPE_RENT = 'rent';

	private bool $isVoximplantEnabled;
	private ?array $callStatistic = null;
	
	public function __construct(private readonly string $callId)
	{
		$this->isVoximplantEnabled = Loader::includeModule('voximplant');
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
			$identifier = ItemIdentifier::createByParams(CCrmOwnerType::ResolveID($crmEntity['ENTITY_TYPE']), $crmEntity['ENTITY_ID']);
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
				return CCrmActivityDirection::Undefined;
			}

			$callDirectionType = (int)$callStatistic['INCOMING'];
		}

		return match($callDirectionType)
		{
			CVoxImplantMain::CALL_OUTGOING, CVoxImplantMain::CALL_INFO => CCrmActivityDirection::Outgoing,
			CVoxImplantMain::CALL_INCOMING, CVoxImplantMain::CALL_INCOMING_REDIRECT, CVoxImplantMain::CALL_CALLBACK => CCrmActivityDirection::Incoming,
			default => CCrmActivityDirection::Undefined,
		};
	}
	
	public function getTelephonyType(): string
	{
		$call = $this->getVoximplantCall();
		if ($call)
		{
			$restAppId = $call->getRestAppId();
			if (isset($restAppId))
			{
				return sprintf(
					'%s:%s',
					self::TELEPHONY_TYPE_REST,
					str_replace('_', '-', $restAppId)
				);
			}

			$config = $call->getConfig();
			if (is_array($config) && isset($config['PORTAL_MODE']))
			{
				return match($config['PORTAL_MODE'])
				{
					CVoxImplantConfig::MODE_SIP => self::TELEPHONY_TYPE_SIP,
					CVoxImplantConfig::MODE_RENT => self::TELEPHONY_TYPE_RENT,
					default => self::TELEPHONY_TYPE_UNDEFINED,
				};
			}
		}
		else
		{
			$callStatistic = $this->getVoximplantCallStatistic();
			if ($callStatistic)
			{
				$callRestAppId = (int)($callStatistic['REST_APP_ID'] ?? 0);
				if ($callRestAppId > 0)
				{
					$restAppId = AppTable::getRowById($callRestAppId)['CODE'] ?? null;
					if (isset($restAppId))
					{
						return sprintf(
							'%s:%s',
							self::TELEPHONY_TYPE_REST,
							str_replace('_', '-', $restAppId)
						);
					}

					// @todo: add "sip" and "rent" support after implementing to StatisticTable
				}
			}
		}
		
		return self::TELEPHONY_TYPE_UNDEFINED;
	}

	private function getVoximplantCall(): ?Voximplant\Call
	{
		if (!$this->isVoximplantEnabled)
		{
			return null;
		}

		$call = Voximplant\Call::load($this->callId);
		if (!$call)
		{
			return null;
		}

		return $call;
	}

	private function getVoximplantCallStatistic(): array
	{
		if (!$this->isVoximplantEnabled)
		{
			return [];
		}

		if (is_null($this->callStatistic))
		{
			$this->callStatistic = [];
			$callStatistic = Voximplant\StatisticTable::query()
				->where('CALL_ID', $this->callId)
				->setSelect([
					'ID',
					'INCOMING',
					'CRM_BINDINGS',
					'REST_APP_ID',
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
					'REST_APP_ID' => $callStatistic->getRestAppId(),
				];
			}
		}

		return $this->callStatistic;
	}
}
