<?php

namespace Bitrix\BIConnector\Superset\ExternalSource\CrmTracking;

use Bitrix\BIConnector\Superset\ExternalSource;
use Bitrix\Main\Localization\Loc;

final class Source implements ExternalSource\Source
{
	public const CRM_SOURCE_VK_ADS = 'vkads';
	public const CRM_SOURCE_YANDEX = 'yandex';
	public const CRM_SOURCE_FACEBOOK = 'facebook';
	public const CRM_SOURCE_GOOGLE = 'google';

	public function __construct(
		protected readonly string $code,
		protected readonly string $crmSourceCode,
		protected bool $isConnected
	)
	{}

	public function getCode(): string
	{
		return $this->code;
	}

	public function isConnected(): bool
	{
		return $this->isConnected;
	}

	public function setConnected(bool $isConnected): void
	{
		$this->isConnected = $isConnected;
	}

	public function getOnClickConnectButtonScript(): string
	{
		\Bitrix\Main\UI\Extension::load([
			'biconnector.apache-superset-crm-tracking-source-manager'
		]);

		$sourceCode = \CUtil::JSEscape($this->crmSourceCode);

		$link = "/crm/tracking/source/edit/{$sourceCode}/";

		return "BX.SidePanel.Instance.open('{$link}', {width: 735, cacheable: false});";
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return Loc::getMessage('BICONNECTOR_CRM_TRACKING_SOURCE_' . mb_strtoupper($this->code) . '_TITLE') ?? '';
	}

	/**
	 * @return string
	 */
	public function getDescription(): string
	{
		return Loc::getMessage('BICONNECTOR_CRM_TRACKING_SOURCE_' . mb_strtoupper($this->code) . '_DESCRIPTION') ?? '';
	}
}
