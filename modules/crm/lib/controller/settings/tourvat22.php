<?php

namespace Bitrix\Crm\Controller\Settings;

use Bitrix\Crm\Controller\Base;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Catalog\Model\Vat;
use Bitrix\Sale\Cashbox;
use Bitrix\Crm\Service\Container;

final class TourVat22 extends Base
{
	private const VAT_22_KEY = 'vat22';
	private const VAT_22_KEY_YOOKASSA = '11';

	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();

		$filters[] = new Scope(Scope::AJAX);

		return $filters;
	}

	private function isAllowDate(): bool
	{
		$vat22BeginDateString = Option::get('crm', 'vat22BeginDate', '2026-01-01 00:00:00');
		$vat22BeginDate = new DateTime($vat22BeginDateString, 'Y-m-d H:i:s');
		$nowDate = new DateTime();

		return $nowDate->getTimestamp() >= $vat22BeginDate->getTimestamp() - \CTimeZone::GetOffset();
	}

	private function isNotAllowDoAction(): bool
	{
		return Application::getInstance()->getLicense()->getRegion() !== 'ru'
			|| !Loader::includeModule('catalog')
			|| !Loader::includeModule('sale')
			|| !$this->isAllowDate()
			|| Option::get('crm', 'isVat22UpdateFinished', 'N') === 'Y'
			|| (int)\CUserOptions::GetOption('crm', 'vat22PopupShowCount', 0) >= 3
			|| !Container::getInstance()->getUserPermissions()->entityType()->canUpdateItems(
				\CCrmOwnerType::Deal,
			)
		;
	}

	public function updateLaterAction(): void
	{
		if ($this->isNotAllowDoAction())
		{
			return;
		}

		$currentDateTimeEntity = new DateTime();
		$currentDate = $currentDateTimeEntity->format('Y-m-d');

		$lastCloseDate = \CUserOptions::GetOption('crm', 'vat22PopupCloseDate', null);

		if ($lastCloseDate === null || $lastCloseDate !== $currentDate)
		{
			\CUserOptions::SetOption('crm', 'vat22PopupCloseDate', $currentDate);
			$vat22PopupShowCount = (int)\CUserOptions::GetOption('crm', 'vat22PopupShowCount', 0);
			\CUserOptions::SetOption('crm', 'vat22PopupShowCount', $vat22PopupShowCount + 1);
		}
	}

	public function updateVat22Action(): void
	{
		if ($this->isNotAllowDoAction())
		{
			return;
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
		if ($vat22Ids)
		{
			$this->updateCashboxesSettings([$vat22Ids]);
			Option::set('crm', 'isVat22UpdateFinished', 'Y');

			return;
		}

		Loc::loadLanguageFile(Application::getDocumentRoot() . '/bitrix/modules/crm/install/sale_link.php');

		$vat20dbResult = Vat::getList([
			'select' => [
				'ID',
				'NAME',
			],
			'filter' => [
				'=RATE' => 20,
				'=ACTIVE' => 'Y',
			],
			'cache' => [
				'ttl' => 86400,
			],
		]);
		$vat20Ids = [];
		while ($vat20Row = $vat20dbResult->fetch())
		{
			$vat20Ids[] = (int)$vat20Row['ID'];
			$updateFields = ['RATE' => 22];
			if ($vat20Row['NAME'] === Loc::getMessage('CRM_VAT_21'))
			{
				$updateFields['NAME'] = Loc::getMessage('CRM_VAT_22');
			}
			Vat::update(
				(int)$vat20Row['ID'],
				$updateFields,
			);
		}
		if (!$vat20Ids)
		{
			Option::set('crm', 'isVat22UpdateFinished', 'Y');

			return;
		}

		$this->updateCashboxesSettings($vat20Ids);

		Option::set('crm', 'isVat22UpdateFinished', 'Y');
	}

	private function updateCashboxesSettings(array $idsForUpdate): void
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

			$isChanged = false;
			foreach ($vatSettings as $vatId => $vatKey)
			{
				if (!in_array((int)$vatId, $idsForUpdate, true))
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
					$vatSettings[$vatId] = $vat22Key;
					$isChanged = true;
				}
			}
			if ($isChanged)
			{
				$cashboxRow['SETTINGS']['VAT'] = $vatSettings;
				Cashbox\Manager::update(
					$cashboxRow['ID'],
					[
						'SETTINGS' => $cashboxRow['SETTINGS'],
					],
				);
			}
		}
	}
}
