<?php declare(strict_types = 1);

namespace Bitrix\AI\SharePrompt\Service\GridPrompt\Dto;

use Bitrix\Main\Type\Contract\Arrayable;

class ShareDto implements Arrayable
{
	public function __construct(
		protected string $name,
		protected string $code,
		protected ?string $img = null
	)
	{
	}

	public function toArray()
	{
		return [
			'name' => $this->getName(),
			'img' => $this->getImg(),
		];
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getImg(): ?string
	{
		return $this->img;
	}

	public function getCode(): string
	{
		return $this->code;
	}
}
