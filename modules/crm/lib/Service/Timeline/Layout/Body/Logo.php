<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body;

use Bitrix\Crm\Service\Timeline\Layout\Base;
use Bitrix\Crm\Service\Timeline\Layout\Mixin\Actionable;

class Logo extends Base
{
	use Actionable;

	public const ICON_TYPE_DEFAULT = 'default';
	public const ICON_TYPE_FAILURE = 'failure';
	public const ICON_TYPE_SUCCESS = 'success';

	protected const BODY_LOGO_BACKGROUND_SIZE = 60;

	protected string $iconCode;
	protected ?string $iconType = null;
	protected ?string $backgroundUrl = null;
	protected ?int $backgroundSize = null;

	protected ?string $additionalIconCode = null;
	protected ?string $additionalIconType = null;

	protected ?bool $isInCircle = null;

	public function __construct(string $iconCode)
	{
		$this->iconCode = $iconCode;
	}

	public function getIconCode(): string
	{
		return $this->iconCode;
	}

	public function getIconType(): ?string
	{
		return $this->iconType;
	}

	public function setIconType(?string $iconType): self
	{
		$this->iconType = $iconType;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getBackgroundUrl(): ?string
	{
		return $this->backgroundUrl;
	}

	/**
	 * @param string|null $backgroundUrl
	 * @return Logo
	 */
	public function setBackgroundUrl(?string $backgroundUrl): Logo
	{
		$this->backgroundUrl = $backgroundUrl;

		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getBackgroundSize(): ?int
	{
		return $this->backgroundSize;
	}

	/**
	 * @param int|null $backgroundSize
	 * @return Logo
	 */
	public function setBackgroundSize(?int $backgroundSize = null): Logo
	{
		$this->backgroundSize = ($backgroundSize ?? self::BODY_LOGO_BACKGROUND_SIZE);

		return $this;
	}

	public function toArray(): array
	{
		return [
			'type' => $this->getIconType(),
			'iconType' => $this->getIconType(),
			'icon' => $this->getIconCode(),
			'backgroundUrl' => $this->getBackgroundUrl(),
			'backgroundSize' => $this->getBackgroundSize(),
			'addIcon' => $this->getAdditionalIconCode(),
			'addIconType' => $this->getAdditionalIconType(),
			'inCircle' => $this->isInCircle(),
			'action' => $this->getAction(),
		];
	}

	public function getAdditionalIconCode(): ?string
	{
		return $this->additionalIconCode;
	}

	public function setAdditionalIconCode(string $additionalIconCode): self
	{
		$this->additionalIconCode = $additionalIconCode;

		return $this;
	}

	public function getAdditionalIconType(): ?string
	{
		return $this->additionalIconType;
	}

	public function setAdditionalIconType(?string $additionalIconType): self
	{
		$this->additionalIconType = $additionalIconType;

		return $this;
	}

	public function isInCircle(): ?bool
	{
		return $this->isInCircle;
	}

	public function setInCircle(?bool $isInCircle = true): self
	{
		$this->isInCircle = $isInCircle;

		return $this;
	}
}
