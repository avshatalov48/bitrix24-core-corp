<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Integration\Intranet\ToolsManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use CUtil;


class AvailabilityManager
{
	private static ?string $crmAvailabilityLock = null;
	private static ?string $reportsConstructAvailabilityLock = null;
	private static ?string $robotsAvailabilityLock = null;
	private static ?string $bizprocAvailabilityLock = null;
	private static array $entityTypeAvailabilityLocks = [];
	private static ?string $terminalAvailabilityLock = null;

	public static function getInstance(): self
	{
		return new self();
	}

	public function getCrmInaccessibilityContent(): string
	{
		return $this->getInaccessibilityComponentContent();
	}

	public function getDynamicInaccessibilityContent(): string
	{
		return $this->getInaccessibilityComponentContent([
			'sliderCode' => ToolsManager::DYNAMIC_SLIDER_CODE,
		]);
	}

	public function getExternalDynamicInaccessibilityContent(): string
	{
		return $this->getInaccessibilityComponentContent([
			'sliderCode' => ToolsManager::EXTERNAL_DYNAMIC_SLIDER_CODE,
		]);
	}

	public function getEntityTypeInaccessibilityContent(int $entityTypeId): string
	{
		return $this->getInaccessibilityComponentContent([
			'entityTypeId' => $entityTypeId,
		]);
	}

	public function getReportsConstructInaccessibilityContent(): string
	{
		return $this->getInaccessibilityComponentContent([
			'sliderCode' => ToolsManager::REPORTS_CONSTRUCT_SLIDER_CODE,
		]);
	}

	public function getInvoiceInaccessibilityContent(): string
	{
		return $this->getInaccessibilityComponentContent([
			'sliderCode' => ToolsManager::INVOICE_SLIDER_CODE,
		]);
	}

	public function getQuoteInaccessibilityContent(): string
	{
		return $this->getInaccessibilityComponentContent([
			'sliderCode' => ToolsManager::QUOTE_SLIDER_CODE,
		]);
	}

	public function getRobotsInaccessibilityContent(): string
	{
		return $this->getInaccessibilityComponentContent([
			'sliderCode' => ToolsManager::ROBOTS_SLIDER_CODE,
		]);
	}

	public function getBizprocInaccessibilityContent(): string
	{
		return $this->getInaccessibilityComponentContent([
			'sliderCode' => ToolsManager::BIZPROC_SLIDER_CODE,
		]);
	}

	public function getTerminalInaccessibilityContent(): string
	{
		return $this->getInaccessibilityComponentContent([
			'sliderCode' => ToolsManager::TERMINAL_SLIDER_CODE,
		]);
	}

	/**
	 * @param array{entityTypeId: int, sliderCode: string} $data
	 * @return string
	 */
	private function getInaccessibilityComponentContent(array $data = []): string
	{
		$params = [];

		if (!empty($data['entityTypeId']))
		{
			$params['ENTITY_TYPE_ID'] = (int)$data['entityTypeId'];
		}

		if (!empty($data['sliderCode']))
		{
			$params['SLIDER_CODE'] = $data['sliderCode'];
		}

		ob_start();
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'bitrix:crm.inaccessibility',
			'',
			$params,
			null,
			['HIDE_ICONS' => 'Y'],
		);

		return ob_get_clean();
	}

	public function getCrmAvailabilityLock(): string
	{
		if (!self::$crmAvailabilityLock)
		{
			self::$crmAvailabilityLock = $this->getJs(ToolsManager::CRM_SLIDER_CODE);
		}

		return self::$crmAvailabilityLock;
	}

	public function getReportsConstructAvailabilityLock(): string
	{
		if (!self::$reportsConstructAvailabilityLock)
		{
			self::$reportsConstructAvailabilityLock = $this->getJs(ToolsManager::REPORTS_CONSTRUCT_SLIDER_CODE);
		}

		return self::$reportsConstructAvailabilityLock;
	}

	public function getRobotsAvailabilityLock(): string
	{
		if (!self::$robotsAvailabilityLock)
		{
			self::$robotsAvailabilityLock = $this->getJs(ToolsManager::ROBOTS_SLIDER_CODE);
		}

		return self::$robotsAvailabilityLock;
	}

	public function getTerminalAvailabilityLock(): string
	{
		if (!self::$terminalAvailabilityLock)
		{
			self::$terminalAvailabilityLock = $this->getJs(ToolsManager::TERMINAL_SLIDER_CODE);
		}

		return self::$terminalAvailabilityLock;
	}

	public function getBizprocAvailabilityLock(): string
	{
		if (!self::$bizprocAvailabilityLock)
		{
			self::$bizprocAvailabilityLock = $this->getJs(ToolsManager::BIZPROC_SLIDER_CODE);
		}

		return self::$bizprocAvailabilityLock;
	}

	public function getEntityTypeAvailabilityLock(int $entityTypeId): ?string
	{
		if (!isset(self::$entityTypeAvailabilityLocks[$entityTypeId]))
		{
			if ($entityTypeId === \CCrmOwnerType::Invoice || $entityTypeId === \CCrmOwnerType::SmartInvoice)
			{
				self::$entityTypeAvailabilityLocks[$entityTypeId] = $this->getInvoiceAvailabilityLock();
			}

			if ($entityTypeId === \CCrmOwnerType::Quote)
			{
				self::$entityTypeAvailabilityLocks[$entityTypeId] = $this->getQuoteAvailabilityLock();
			}

			if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
			{
				$factory = Container::getInstance()->getFactory($entityTypeId);
				if ($factory && $factory->isInCustomSection())
				{
					self::$entityTypeAvailabilityLocks[$entityTypeId] = $this->getExternalDynamicAvailabilityLock();
				}
				else
				{
					self::$entityTypeAvailabilityLocks[$entityTypeId] = $this->getDynamicAvailabilityLock();
				}
			}
		}

		return self::$entityTypeAvailabilityLocks[$entityTypeId] ?? null;
	}

	private function getInvoiceAvailabilityLock(): string
	{
		return $this->getJs(ToolsManager::INVOICE_SLIDER_CODE);
	}

	private function getQuoteAvailabilityLock(): string
	{
		return $this->getJs(ToolsManager::QUOTE_SLIDER_CODE);
	}

	public function getDynamicAvailabilityLock(): string
	{
		return $this->getJs(ToolsManager::DYNAMIC_SLIDER_CODE);
	}

	public function getExternalDynamicAvailabilityLock(): string
	{
		return $this->getJs(ToolsManager::EXTERNAL_DYNAMIC_SLIDER_CODE);
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
