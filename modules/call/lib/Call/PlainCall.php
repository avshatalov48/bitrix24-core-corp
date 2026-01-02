<?php

namespace Bitrix\Call\Call;

use Bitrix\Im\Call\Call;
use Bitrix\Im\Call\Util;
use Bitrix\Main\Config\Option;
use Bitrix\Call\ControllerClient;
use Bitrix\Call\Settings;

\Bitrix\Main\Loader::includeModule('im');

class PlainCall extends Call
{
	protected $provider = parent::PROVIDER_PLAIN;

	protected function initCall(): void
	{
		if (Settings::isNewCallsEnabled() && Settings::isPlainCallsUseNewScheme())
		{
			return;
		}

		if ($this->getState() == static::STATE_NEW)
		{
			if (empty($this->uuid))
			{
				$this->uuid = Util::generateUUID();
				$this->save();
			}
			(new ControllerClient())->createCall($this);
		}

		parent::initCall();
	}

	public function finish(): void
	{
		if (
			$this->scheme !== self::SCHEME_JWT
			&& $this->getState() != static::STATE_FINISHED
		)
		{
			(new ControllerClient())->finishCall($this);
		}

		parent::finish();
	}

	public function getMaxUsers(): int
	{
		return (int)Option::get('call', 'turn_server_max_users');
	}

	/**
	 * Do need to record call.
	 * @return bool
	 */
	public function autoStartRecording(): bool
	{
		return false;
	}
}