<?php

namespace Bitrix\Crm\Activity\Entity;

use Bitrix\Crm\Activity\Provider;

class ToDo extends BaseActivity
{
	public function isValidProviderId(string $providerId): bool
	{
		return $this->provider::getId() === Provider\ToDo\ToDo::getId() && $providerId === Provider\ToDo\ToDo::getId();
	}

	public function getProviderId(): string
	{
		return Provider\ToDo\ToDo::PROVIDER_ID;
	}

	public function getProviderTypeId(): string
	{
		return Provider\ToDo\ToDo::PROVIDER_TYPE_ID_DEFAULT;
	}
}
