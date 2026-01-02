<?php

namespace Bitrix\Intranet\Integration\Socialnetwork\Collab;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Collab\CollabFeature;
use Bitrix\Socialnetwork\Collab\Requirement;

final class Collab
{
	protected bool $available = false;

	public function __construct()
	{
		if (Loader::includeModule('socialnetwork'))
		{
			$this->available = CollabFeature::isOn()
				&& CollabFeature::isFeatureEnabled()
				&& Requirement::check()->isSuccess();
		}
	}

	public function isAvailable(): bool
	{
		return $this->available;
	}
}