<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Landing\Hook\Page\Settings;
use Bitrix\Main\Loader;
use Bitrix\Crm;

class StoreMenuSidebar extends \Bitrix\Landing\LandingBlock
{
	protected $crmIncluded;

	protected $userAuthorized = false;

	protected $userId;

	protected $userName = '';

	/**
	 * Method, which will be called once time.
	 * @param array Params array.
	 * @return void
	 */
	public function init(array $params = [])
	{
		$this->crmIncluded = Loader::includeModule('crm');
		$this->params = Settings::getDataForSite(
			$params['site_id']
		);
		$this->params['SITE_ID'] = $params['site_id'];
		$this->params['LANDING_ID'] = $params['landing_id'];

		$this->initCurrentUser();
	}

	public function getUserInformation(): ?array
	{
		if (!$this->userAuthorized)
		{
			return null;
		}

		return [
			'USER_NAME' => $this->userName,
			'LAST_ORDER_URL' => $this->getLastOrderUrl()
		];
	}

	protected function initCurrentUser(): void
	{
		global $USER;

		if (isset($USER) && $USER instanceof \CUser && $USER->IsAuthorized())
		{
			$this->userAuthorized = true;
			$this->userId = (int)$USER->GetID();
			$this->userName = $USER->GetFormattedName();
		}
	}

	protected function getLastOrderUrl(): string
	{
		$orderId = $this->getLastOrderId();
		if ($orderId === null)
		{
			return '#system_order';
		}
		return '';
	}

	protected function getLastOrderId(): ?int
	{
		$result = null;
		if ($this->crmIncluded && $this->userAuthorized)
		{
			$iterator = Crm\Order\TradeBindingCollection::getList([
				'select' => [
					'ORDER_ID'
				],
				'filter' => [
					'=TRADING_PLATFORM.CODE' => 'landing_'.$this->params['SITE_ID'],
					'=ORDER.USER_ID' => $this->userId
				],
				'order' => ['ORDER_ID' => 'DESC'],
				'limit' => 1
			]);
			$row = $iterator->fetch();
			if (!empty($row))
			{
				$result = (int)$row['ORDER_ID'];
			}
		}

		return $result;
	}
}