<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Catalog\Model\Vat;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Cashbox;

class Vat22 extends Base
{
	private const VAT_22_KEY = 'vat22';
	private const VAT_22_KEY_YOOKASSA = '11';

	private function isAllowDate(): bool
	{
		$vat22BeginDateString = Option::get('crm', 'vat22BeginDate', '2026-01-01 00:00:00');
		$vat22BeginDate = new DateTime($vat22BeginDateString, 'Y-m-d H:i:s');
		$nowDate = new DateTime();

		return $nowDate->getTimestamp() >= $vat22BeginDate->getTimestamp() - \CTimeZone::GetOffset();
	}

	protected function canShow(): bool
	{
		if (
			Application::getInstance()->getLicense()->getRegion() !== 'ru'
			|| !Loader::includeModule('catalog')
			|| !Loader::includeModule('sale')
			|| !$this->isAllowDate()
			|| Option::get('crm', 'isVat22UpdateFinished', 'N') === 'Y'
			|| (int)\CUserOptions::GetOption('crm', 'vat22PopupShowCount', 0) >= 3
			|| !Container::getInstance()->getUserPermissions()->entityType()->canUpdateItems(
				\CCrmOwnerType::Deal,
			)
		)
		{
			return false;
		}

		$currentDateTimeEntity = new DateTime();
		$currentDate = $currentDateTimeEntity->format('Y-m-d');
		$lastCloseDate = \CUserOptions::GetOption('crm', 'vat22PopupCloseDate', null);
		if ($lastCloseDate !== null && $lastCloseDate === $currentDate)
		{
			return false;
		}

		$vat22DbResult = Vat::getList([
			'select' => [
				'ID',
			],
			'filter' => [
				'=RATE' => 22,
				'=ACTIVE' => 'Y',
			],
			'cache' => [
				'ttl' => 86400,
			],
		]);
		$vat22Ids = [];
		while ($vat22Row = $vat22DbResult->fetch())
		{
			$vat22Ids[] = (int)$vat22Row['ID'];
		}
		if ($vat22Ids && !$this->isNeedUpdateCashbox($vat22Ids))
		{
			Option::set('crm', 'isVat22UpdateFinished', 'Y');

			return false;
		}

		$vat20dbResult = Vat::getRow([
			'select' => [
				'ID',
			],
			'filter' => [
				'=RATE' => 20,
				'=ACTIVE' => 'Y',
			],
			'cache' => [
				'ttl' => 86400,
			],
		]);
		if (!$vat20dbResult)
		{
			Option::set('crm', 'isVat22UpdateFinished', 'Y');

			return false;
		}

		return true;
	}

	private function isNeedUpdateCashbox(array $vat22Ids): bool
	{
		$cashboxListDbResult = Cashbox\Manager::getList([
			'select' => ['ID', 'HANDLER', 'SETTINGS'],
			'filter' => [
				'=ACTIVE' => 'Y',
				'=HANDLER' => [
					'\Bitrix\Sale\Cashbox\CashboxAtolFarmV4',
					'\Bitrix\Sale\Cashbox\CashboxAtolFarmV5',
					'\Bitrix\Sale\Cashbox\CashboxBusinessRu',
					'\Bitrix\Sale\Cashbox\CashboxBusinessRuV5',
					'\Bitrix\Sale\Cashbox\CashboxRobokassa',
					'\Bitrix\Sale\Cashbox\CashboxYooKassa',
				],
			],
			'cache' => [
				'ttl' => 86400,
			],
		]);
		while ($cashboxRow = $cashboxListDbResult->fetch())
		{
			$vatSettings = $cashboxRow['SETTINGS']['VAT'] ?? null;
			if (!is_array($vatSettings))
			{
				continue;
			}

			foreach ($vatSettings as $vatId => $vatKey)
			{
				if (!in_array((int)$vatId, $vat22Ids, true))
				{
					continue;
				}

				$vat22Key =
					$cashboxRow['HANDLER'] === '\Bitrix\Sale\Cashbox\CashboxYooKassa'
						? self::VAT_22_KEY_YOOKASSA
						: self::VAT_22_KEY
				;

				if ($vatKey !== $vat22Key)
				{
					return true;
				}
			}
		}

		return false;
	}

	protected function getComponentTemplate(): string
	{
		return 'vat_22';
	}

	protected function getOptions(): array
	{
		return [];
	}
}
