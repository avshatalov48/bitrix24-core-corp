<?php

namespace Bitrix\Crm\Service\Communication\Channel;

final class ChannelsCollection implements \Iterator, \ArrayAccess, \Countable
{
	/**
	 * @var Channel[] $channels
	 */
	protected array $channels = [];

	/**
	 * @param Channel[] $channels
	 */
	public function __construct(array $channels)
	{
		foreach ($channels as $channel)
		{
			$this->channels[$this->getKeyFromChannel($channel)] = $channel;
		}
	}

	public function toArray(): array
	{
		$channels = [];
		foreach ($this->channels as $channel)
		{
			$channels[$this->getKeyFromChannel($channel)] = $channel->toArray();
		}

		return $channels;
	}

	public function hasChannel(string $moduleId, string $code): bool
	{
		return ($this->getChannel($moduleId, $code) !== null);
	}

	public function getChannel(string $moduleId, string $code): ?Channel
	{
		return $this[$this->getKey($moduleId, $code)];
	}

	public function getPropertyNameList(): array
	{
		$names = [];
		foreach ($this->channels as $channel)
		{
			$names[] = $this->getKeyFromChannel($channel);
		}

		return $names;
	}

	private function getKeyFromChannel(Channel $channel): string
	{
		return $this->getKey($channel->getModuleId(), $channel->getCode());
	}

	private function getKey(string $moduleId, string $code): string
	{
		return $moduleId . '-' . $code;
	}

	public function current(): ?Channel
	{
		return current($this->channels);
	}

	public function next(): void
	{
		next($this->channels);
	}

	public function key(): string
	{
		return key($this->channels);
	}

	public function valid(): bool
	{
		return (key($this->channels) !== null);
	}

	public function rewind(): void
	{
		reset($this->channels);
	}

	public function offsetExists($offset): bool
	{
		return isset($this->channels[$offset]);
	}

	public function offsetGet($offset): ?Channel
	{
		if (isset($this->channels[$offset]) && is_string($offset))
		{
			return $this->channels[$offset];
		}

		return null;
	}

	public function offsetSet($offset, $value): void
	{
		if ($value instanceof Channel && $this->getKeyFromChannel($value) === $offset)
		{
			$this->channels[$this->getKeyFromChannel($value)] = $value;
		}
	}

	public function offsetUnset($offset): void
	{
		unset($this->channels[$offset]);
	}

	public function count(): int
	{
		return count($this->channels);
	}

}
