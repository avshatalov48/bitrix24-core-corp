<?php

namespace Bitrix\Crm\Activity\Entity;

use Bitrix\Crm\Activity\Provider;

class Task extends BaseActivity
{
	public function isValidProviderId(string $providerId): bool
	{
		return $this->provider::getId() === Provider\Bizproc\Task::getId() && $providerId === Provider\Bizproc\Task::getId();
	}

	public function getProviderId(): string
	{
		return Provider\Bizproc\Task::getId();
	}

	public function getProviderTypeId(): string
	{
		return Provider\Bizproc\Task::getProviderTypeId();
	}

	public function setDescription(?string $description): self
	{
		$this->description = $description ?? '';

		return $this;
	}
}
