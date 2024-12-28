<?php

declare(strict_types=1);

namespace Bitrix\AI\Payload\Tokens;


class HiddenToken
{
	private ?string $prefix = null;
	private TokenProcessor $processor;

	public function __construct(
		private readonly string $value,
		private readonly TokenType $type,
	)
	{
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function getPrefix(): ?string
	{
		return $this->prefix;
	}

	public function setPrefix(?string $prefix): static
	{
		$this->prefix = $prefix;

		return $this;
	}

	public function getType(): TokenType
	{
		return $this->type;
	}

	public function getReplacement(): string
	{
		return $this->processor->getReplacement($this);
	}

	/**
	 * @param TokenProcessor $processor
	 * @return void
	 * @internal
	 */
	public function attachToProcessor(TokenProcessor $processor): void
	{
		$this->processor = $processor;
	}
}
