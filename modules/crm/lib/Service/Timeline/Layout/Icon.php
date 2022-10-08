<?php

namespace Bitrix\Crm\Service\Timeline\Layout;

class Icon extends Base
{
	public const COUNTER_TYPE_DANGER = 'danger';
	public const COUNTER_TYPE_SUCCESS = 'success';

	public const BACKGROUND_PRIMARY = 'primary';
	public const BACKGROUND_PRIMARY_ALT = 'primary_alt';
	public const BACKGROUND_FAILURE = 'failure';

	protected string $code = '';
	protected ?bool $showCounter = null;
	protected ?string $counterType = null;
	protected ?string $backgroundColorToken = null;

	public function getCode(): string
	{
		return $this->code;
	}

	public function setCode(string $className): self
	{
		$this->code = $className;

		return $this;
	}

	public function getCounterType(): ?string
	{
		return $this->counterType;
	}

	public function setCounterType(?string $counterType): self
	{
		$this->counterType = $counterType;

		return $this;
	}

	public function getBackgroundColorToken(): ?string
	{
		return $this->backgroundColorToken;
	}


	public function setBackgroundColorToken(?string $backgroundColorToken): self
	{
		$this->backgroundColorToken = $backgroundColorToken;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'code' => $this->getCode(),
			'counterType' => $this->getCounterType(),
			'backgroundColorToken' => $this->getBackgroundColorToken(),
		];
	}
}
