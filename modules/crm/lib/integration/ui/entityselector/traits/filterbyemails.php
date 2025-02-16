<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector\Traits;

trait FilterByEmails
{
	protected bool $emailOnlyModeEnabled = false;

	protected function setEmailOnlyMode(bool $emailOnlyModeEnabled = false): void
	{
		$this->emailOnlyModeEnabled = $emailOnlyModeEnabled;
	}

	protected function isEmailOnlyMode(): bool
	{
		return $this->emailOnlyModeEnabled;
	}

	protected function getEmailFilters(): array
	{
		if ($this->isEmailOnlyMode())
		{
			return [
				'=HAS_EMAIL' => 'Y',
			];
		}

		return [];
	}
}
