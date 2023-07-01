<?php

namespace Bitrix\Crm\Component\EntityDetails\Traits;

trait InitializeGuid
{
	abstract protected function getDefaultGuid();

	private function initializeGuid(): void
	{
		$this->guid = $this->arParams['GUID'] ?? $this->getDefaultGuid();
		$this->arResult['GUID'] = $this->guid;
	}
}
