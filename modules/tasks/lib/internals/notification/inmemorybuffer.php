<?php

namespace Bitrix\Tasks\Internals\Notification;

class InMemoryBuffer implements BufferInterface
{
	private static ?InMemoryBuffer $instance = null;
	private ProviderCollection $buffer;

	/**
	 * @return InMemoryBuffer
	 */
	public static function getInstance(): InMemoryBuffer
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function addProvider(ProviderInterface $provider): void
	{
		$this->buffer->add($provider);
	}

	public function flush(): ProviderCollection
	{
		$snapshot = $this->buffer;

		$this->initEmptyBuffer();

		return $snapshot;
	}

	public function __construct()
	{
		$this->initEmptyBuffer();
	}

	private function initEmptyBuffer(): void
	{
		$this->buffer = new ProviderCollection();
	}
}