<?php

namespace Bitrix\Intranet\Entity\Type;

class Logo
{
	public function __construct(
		private readonly string $color,
		private readonly string $white,
		private readonly string $black,
	){}

	/**
	 * @return string
	 */
	public function getColor(): string
	{
		return $this->color;
	}

	/**
	 * @return string
	 */
	public function getWhite(): string
	{
		return $this->white;
	}

	/**
	 * @return string
	 */
	public function getBlack(): string
	{
		return $this->black;
	}

	public function toArray(): array
	{
		return [
			'color' => $this->color,
			'white' => $this->white,
			'black' => $this->black,
		];
	}
}