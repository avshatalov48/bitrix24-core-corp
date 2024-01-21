<?php

namespace Bitrix\BiConnector\Settings\Grid;

use Bitrix\Main\Grid\Settings;
use Bitrix\BIConnector;

class KeysSettings extends Settings
{
	protected bool $canWrite;
	protected bool $canRead;
	protected string $editUrl;

	public function __construct(array $params)
	{
		$this->canWrite = (bool)($params['CAN_WRITE'] ?? false);
		$this->canRead = (bool)($params['CAN_READ'] ?? false);
		$this->editUrl = $params['KEY_EDIT_URL'] ? (string)$params['KEY_EDIT_URL'] : '';
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

	public function getEditUrl(): string
	{
		return $this->editUrl;
	}

	public function isWithConnections(): bool
	{
		return count(BIConnector\Manager::getInstance()->getConnections()) > 1;
	}
}
