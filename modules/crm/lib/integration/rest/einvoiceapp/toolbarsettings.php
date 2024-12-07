<?php

namespace Bitrix\Crm\Integration\Rest\EInvoiceApp;

use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Buttons;

final class ToolbarSettings
{
	use Availability;

	private const MARKET_COLLECTION_TAG = 'einvoicing';

	public function getItems(): array
	{
		if (!$this->isEInvoiceAvailable())
		{
			return [];
		}

		if (!$this->isHasInstalledApps())
		{
			return [ $this->getEInvoiceItem() ];
		}

		return [
			$this->getEInvoiceItem([
				...$this->getInstalledAppsItems(),
				$this->getChooseEInvoiceServiceItem(),
				// $this->getGoToMarketItem(), // There is no collection of einvoice applications yet
			]),
		];
	}

	public static function getEInvoiceTitle(): string
	{
		return Loc::getMessage('CRM_INTEGRATION_REST_EINVOICE_TITLE');
	}

	private function getEInvoiceItem(array $subItems = []): array
	{
		return [
			'text' => self::getEInvoiceTitle(),
			'items' => $subItems,
			'onclick' => empty($subItems)
				? $this->getOpenInstallerSliderJsCode()
				: null
			,
		];
	}

	private function getInstalledAppsItems(): array
	{
		$items = [];

		$installedApps = \Bitrix\Rest\EInvoice::getInstalledApplications();
		foreach ($installedApps as $app)
		{
			$appUrl = \Bitrix\Rest\Marketplace\Url::getApplicationUrl($app['ID']);
			$items[] = [
				'text' => $app['MENU_NAME'],
				'onclick' => $this->getOpenSliderJsCode($appUrl),
			];
		}

		if (!empty($items))
		{
			$items[] = [ 'delimiter' => true ];
		}

		return $items;
	}

	private function getChooseEInvoiceServiceItem(): array
	{
		return [
			'text' => Loc::getMessage('CRM_INTEGRATION_REST_EINVOICE_CHOOSE_INVOICE_SERVICE'),
			'onclick' => $this->getOpenInstallerSliderJsCode(),
		];
	}

	private function getGoToMarketItem(): array
	{
		$collectionTag = self::MARKET_COLLECTION_TAG;
		$marketUrl = "/market/collection/{$collectionTag}/";

		return [
			'text' => Loc::getMessage('CRM_INTEGRATION_REST_EINVOICE_GO_TO_MARKET'),
			'onclick' => $this->getOpenSliderJsCode($marketUrl),
		];
	}

	private function getOpenInstallerSliderJsCode(): Buttons\JsCode
	{
		$installerSlider = new InstallerSlider();

		return new Buttons\JsCode("
			{$this->getCloseToolbarMenuJsString()}
			{$installerSlider->buildSlider()}
		");
	}

	private function getOpenSliderJsCode(string $url): Buttons\JsCode
	{
		return new Buttons\JsCode("
			{$this->getCloseToolbarMenuJsString()}
			BX.SidePanel.Instance.open('{$url}');
		");
	}

	private function getCloseToolbarMenuJsString(): string
	{
		/** @lang JavaScript */
		return '
			BX.Crm.Router.Instance.closeToolbarSettingsMenuRecursively(...arguments);
		';
	}
}
