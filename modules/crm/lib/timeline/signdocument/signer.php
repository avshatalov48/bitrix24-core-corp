<?php

namespace Bitrix\Crm\Timeline\SignDocument;

use Bitrix\Main\Type\Contract\Arrayable;

final class Signer implements \JsonSerializable, Arrayable
{
	protected string $title;
	protected string $hash;

	protected function __construct(string $title, string $hash)
	{
		$this->title = $title;
		$this->hash = $hash;
	}

	public static function createFromArray(array $data): ?self
	{
		if (empty($data['title']))
		{
			return null;
		}
		$signer = new self((string)$data['title'], (string)$data['hash']);

		return $signer;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function getHash(): string
	{
		return $this->hash;
	}

	/**
	 * @param string $hash
	 * @return Signer
	 */
	public function setHash(string $hash): Signer
	{
		$this->hash = $hash;
		return $this;
	}

	public function toArray(): array
	{
		return [
			'title' => $this->title,
			'hash' => $this->hash,
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
