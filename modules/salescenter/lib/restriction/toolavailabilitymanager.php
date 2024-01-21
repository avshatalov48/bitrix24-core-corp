<?php

namespace Bitrix\Salescenter\Restriction;

use Bitrix\Crm\Service\Container;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;
use CUtil;


class ToolAvailabilityManager
{
	private bool $canUseIntranetToolsManager;
	private const SALESCENTER_TOOL_ID = 'saleshub';
	public const SALESCENTER_SLIDER_CODE = 'limit_crm_sales_center_off';

	public function __construct()
	{
		$this->canUseIntranetToolsManager = (
			Loader::includeModule('intranet')
			&& class_exists('\Bitrix\Intranet\Settings\Tools\ToolsManager')
		);
	}

	public static function getInstance(): self
	{
		return new self();
	}

	public function checkSalescenterAvailability(): bool
	{
		return $this->check(self::SALESCENTER_TOOL_ID);
	}

	private function check(string $toolId): bool
	{
		if ($this->canUseIntranetToolsManager)
		{
			return ToolsManager::getInstance()->checkAvailabilityByToolId($toolId);
		}

		return true;
	}

	public function getSalescenterStubContent(): string
	{
		return $this->getStubComponentContent([
			'sliderCode' => self::SALESCENTER_SLIDER_CODE,
			'locationHref' => Container::getInstance()->getRouter()->getDefaultRoot(),
		]);
	}

	public function getSalescenterStubJs(): string
	{
		return $this->getJs(self::SALESCENTER_SLIDER_CODE);
	}

	private function getStubComponentContent(array $data = []): string
	{
		$params = [];

		if (!empty($data['sliderCode']))
		{
			$params['SLIDER_CODE'] = $data['sliderCode'];
		}

		if (!empty($data['locationHref']))
		{
			$params['LOCATION_HREF'] = $data['locationHref'];
		}

		ob_start();
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'bitrix:intranet.tool.inaccessibility',
			'',
			$params,
			null,
			['HIDE_ICONS' => 'Y'],
		);

		return ob_get_clean();
	}

	private function getJs(string $id): string
	{
		if (!Loader::includeModule('ui'))
		{
			return '';
		}

		return '
			top && top.BX.loadExt("ui.info-helper").then(() => {
				top.BX.UI.InfoHelper.show("' . CUtil::JSEscape($id) . '");
			});
		';
	}
}
