<?php

namespace Bitrix\BiConnector\Settings\Grid;

use Bitrix\Main\Grid\Settings;

class DashboardSettings extends Settings
{
	protected bool $canWrite;
	protected bool $canRead;
	protected string $viewUrl;
	protected string $editUrl;

	public function __construct(array $params)
	{
		$this->canWrite = (bool)($params['CAN_WRITE'] ?? false);
		$this->canRead = (bool)($params['CAN_READ'] ?? false);
		$this->viewUrl = $params['DASHBOARD_VIEW_URL'] ? (string)$params['DASHBOARD_VIEW_URL'] : '';
		$this->editUrl = $params['DASHBOARD_EDIT_URL'] ? (string)$params['DASHBOARD_EDIT_URL'] : '';
		parent::__construct($params);
	}

	public function isCanRead(): bool
	{
		return $this->canRead;
	}

	public function isCanWrite(): bool
	{
		return $this->canWrite;
	}

	public function getViewUrl(): string
	{
		return $this->viewUrl;
	}

	public function getEditUrl(): string
	{
		return $this->editUrl;
	}
}
