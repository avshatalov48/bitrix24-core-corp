<?php

namespace Bitrix\AI\Context;

class Message
{
	private function __construct(
		private string $content,
		private ?string $role = null,
		private array $meta = [],
	) {}

	/**
	 * Transforms incoming message array to Message instance.
	 *
	 * @param array $message Row message array.
	 * @return self|null
	 */
	public static function retrieveFromArray(array $message): ?self
	{
		if (!isset($message['content']) || !is_string($message['content']))
		{
			return null;
		}
		if (isset($message['role']) && !is_string($message['role']))
		{
			return null;
		}
		if (!isset($message['meta']) || !is_array($message['meta']))
		{
			$message['meta'] = [];
		}
		if ($message['is_original_message'] ?? false)
		{
			$message['meta']['is_original_message'] = true;
		}

		return new self(
			$message['content'],
			$message['role'] ?? null,
			$message['meta'],
		);
	}

	/**
	 * Transforms incoming messages arrays to array of Message instance.
	 * Wrapper helper for retrieveFromArray.
	 *
	 * @param array $messages Row message array.
	 * @return self[]
	 */
	public static function retrieveFromArrays(array $messages): array
	{
		$return = [];

		foreach ($messages as $message)
		{
			$message = self::retrieveFromArray($message);
			if (!empty($message))
			{
				$return[] = $message;
			}
		}

		return $return;
	}

	public function getContent(): string
	{
		return $this->content;
	}

	public function getRole(?string $defaultRole = null): ?string
	{
		return $this->role ?: $defaultRole;
	}

	public function getMeta(?string $key = null): mixed
	{
		return is_null($key) ? $this->meta : ($this->meta[$key] ?? null);
	}

	public function toArray(): array
	{
		return [
			'content' => $this->content,
			'role' => $this->role,
			'meta' => $this->meta,
		];
	}
}
