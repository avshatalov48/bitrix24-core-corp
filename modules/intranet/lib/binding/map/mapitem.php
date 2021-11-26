<?php

namespace Bitrix\Intranet\Binding\Map;

class MapItem
{
	/** @var string */
	protected $code;
	/** @var string|null */
	protected $customRestPlacementCode;

	public function __construct(string $code, ?string $customRestPlacementCode = null)
	{
		$this->code = $code;
		$this->customRestPlacementCode = $customRestPlacementCode;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getCustomRestPlacementCode(): ?string
	{
		return $this->customRestPlacementCode;
	}
}
