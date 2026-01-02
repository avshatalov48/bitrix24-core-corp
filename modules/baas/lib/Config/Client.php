<?php

namespace Bitrix\Baas\Config;

use Bitrix\Main;

class Client extends Config
{
	protected const SYNCHRONIZATION_DELTA = 7200;

	protected function getModuleId(): string
	{
		return 'baas';
	}

	public function getHost(): string
	{
		$server = Main\Context::getCurrent()->getServer();

		return (new Main\Web\Uri(
			($server->getRequestScheme() === 'http' ? 'http' : 'https')
			. '://'
			. ($server->getHttpHost() ?? $server->getServerName() ?? ''),
		))->getUri();
	}

	public function getSynCode(): ?string
	{
		return $this->get('verification_code');
	}

	public function setSynCode(string $code): static
	{
		$this->set('verification_code', $code);

		return $this;
	}

	public function isRegistered(): bool
	{
		return $this->get('host_key') !== null && $this->get('host_secret') !== null;
	}

	public function setRegistrationData(string $hostKey, string $hostSecret): static
	{
		$this->set('host_key', $hostKey);
		$this->set('host_secret', $hostSecret);

		return $this;
	}

	public function getRegistrationData(): array
	{
		return [
			'host_key' => $this->get('host_key'),
			'host_secret' => $this->get('host_secret'),
		];
	}

	public function setNextSyncTime(int $timestamp): static
	{
		$this->set('synchronization:next_sync_time_because_of_error', (string)$timestamp);

		return $this;
	}

	public function getNextSyncTime(): int
	{
		return (int)$this->get('synchronization:next_sync_time_because_of_error');
	}

	public function setLastSyncTime(int $timestamp): static
	{
		$this->set('synchronization:last_sync_time', (string)$timestamp);

		return $this;
	}

	public function getLastSyncTime(): int
	{
		return (int)$this->get('synchronization:last_sync_time');
	}

	public function getSyncDelta(): int
	{
		$delta = (int)$this->get('synchronization:delta', 0);
		if ($delta > 0)
		{
			return $delta;
		}

		$delta = rand(1, self::SYNCHRONIZATION_DELTA);

		$this->set('synchronization:delta', $delta);

		return $delta;
	}

	public function isTurnedOn(): bool
	{
		return $this->get('is_available') !== 'N';
	}

	public function turnOn(): static
	{
		$this->set('is_available', 'Y');

		return $this;
	}

	public function turnOff(): static
	{
		$this->set('is_available', 'N');

		return $this;
	}

	public function isLoggingEnabled(): bool
	{
		return $this->get('enabled_logging', 'N') === 'Y';
	}

	public function enableLogging(): static
	{
		$this->set('enabled_logging', 'Y');

		return $this;
	}

	public function disableLogging(): static
	{
		$this->delete('enabled_logging');

		return $this;
	}
}
