<?php

namespace Bitrix\Crm\Integration\Intranet;

use Bitrix\Crm\Service\Container;
use Bitrix\Intranet\Settings\Tools;
use Bitrix\Main\Loader;

final class ToolsManager
{
	private bool $canUseIntranetToolsManager;

	private const CRM_TOOL_ID = 'crm';
	public const CRM_SLIDER_CODE = 'limit_crm_off';

	private const INVOICE_TOOL_ID = 'invoices';
	public const INVOICE_SLIDER_CODE = 'limit_crm_invoices_off';

	private const QUOTE_TOOL_ID = 'offers';
	public const QUOTE_SLIDER_CODE = 'limit_crm_quotes_off';

	private const DYNAMIC_TOOL_ID = 'dynamic_items';
	public const DYNAMIC_SLIDER_CODE = 'limit_smart_processes_off';

	private const EXTERNAL_DYNAMIC_TOOL_ID = 'crm-dynamic';
	public const EXTERNAL_DYNAMIC_SLIDER_CODE = 'limit_digital_workplaces_off';

	private const REPORTS_CONSTRUCT_TOOL_ID = 'report_construct';
	public const REPORTS_CONSTRUCT_SLIDER_CODE = 'limit_crm_analytics_off';

	private const REPORTS_ANALYTICS_TOOL_ID = 'analytics';
	public const REPORTS_ANALYTICS_SLIDER_CODE = 'limit_crm_analytics_off';

	private const ROBOTS_TOOL_ID = 'robots';
	public const ROBOTS_SLIDER_CODE = 'limit_crm_rules_off';

	private const BIZPROC_TOOL_ID = 'bizproc';
	public const BIZPROC_SLIDER_CODE = 'limit_automation_off';
	private const BIZPROC_SCRIPT_TOOL_ID = 'bizproc_script';

	private const TERMINAL_TOOL_ID = 'terminal';
	public const TERMINAL_SLIDER_CODE = 'limit_crm_terminal_off';

	public function __construct()
	{
		$this->canUseIntranetToolsManager = (
			Loader::includeModule('intranet')
			&& class_exists('\Bitrix\Intranet\Settings\Tools\ToolsManager')
		);
	}

	public function checkReportsConstructAvailability(): bool
	{
		return
			$this->check(self::REPORTS_CONSTRUCT_TOOL_ID)
			&& $this->checkCrmAvailability()
		;
	}

	public function checkEntityTypeAvailability(int $entityTypeId): bool
	{
		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			if ($this->isEntityTypeIdExternal($entityTypeId))
			{
				return $this->checkExternalDynamicAvailability();
			}

			return $this->checkCrmAvailability() && $this->checkDynamicAvailability();
		}

		if (!$this->checkCrmAvailability())
		{
			return false;
		}

		if (
			$entityTypeId === \CCrmOwnerType::Invoice
			|| $entityTypeId === \CCrmOwnerType::SmartInvoice
		)
		{
			return $this->checkInvoiceAvailability();
		}

		if ($entityTypeId === \CCrmOwnerType::Quote)
		{
			return $this->checkQuoteAvailability();
		}

		return true;
	}

	public function checkExternalDynamicAvailability(): bool
	{
		return $this->check(self::EXTERNAL_DYNAMIC_TOOL_ID);
	}

	public function checkReportsAnalyticsAvailability(): bool
	{
		return
			$this->check(self::REPORTS_ANALYTICS_TOOL_ID)
			&& $this->checkCrmAvailability()
		;
	}

	public function checkRobotsAvailability(): bool
	{
		return
			$this->check(self::ROBOTS_TOOL_ID)
			&& $this->checkCrmAvailability()
		;
	}

	public function checkBizprocAvailability(): bool
	{
		return
			$this->check(self::BIZPROC_TOOL_ID)
			&& $this->checkCrmAvailability()
		;
	}

	public function checkBizprocScriptAvailability(): bool
	{
		return
			$this->check(self::BIZPROC_SCRIPT_TOOL_ID)
			&& $this->checkCrmAvailability()
		;
	}

	public function checkCrmAvailability(): bool
	{
		return $this->check(self::CRM_TOOL_ID);
	}

	public function checkDynamicAvailability(): bool
	{
		return $this->check(self::DYNAMIC_TOOL_ID);
	}

	private function checkInvoiceAvailability(): bool
	{
		return $this->check(self::INVOICE_TOOL_ID);
	}

	private function checkQuoteAvailability(): bool
	{
		return $this->check(self::QUOTE_TOOL_ID);
	}

	public function checkTerminalAvailability(): bool
	{
		return $this->check(self::TERMINAL_TOOL_ID);
	}

	private function check(string $toolId): bool
	{
		if ($this->canUseIntranetToolsManager)
		{
			return Tools\ToolsManager::getInstance()->checkAvailabilityByToolId($toolId);
		}

		return true;
	}

	public function getSliderCodeByEntityTypeId(int $entityTypeId): ?string
	{
		if (
			$entityTypeId === \CCrmOwnerType::Invoice
			|| $entityTypeId === \CCrmOwnerType::SmartInvoice
		)
		{
			return self::INVOICE_SLIDER_CODE;
		}

		if ($entityTypeId === \CCrmOwnerType::Quote)
		{
			return self::QUOTE_SLIDER_CODE;
		}

		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			if ($this->isEntityTypeIdExternal($entityTypeId))
			{
				return self::EXTERNAL_DYNAMIC_SLIDER_CODE;
			}

			return self::DYNAMIC_SLIDER_CODE;
		}

		return null;
	}

	public function isEntityTypeIdExternal(int $entityTypeId): bool
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);

		return $factory && $factory->isInCustomSection();
	}
}
