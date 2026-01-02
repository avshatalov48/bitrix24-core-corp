<?php

namespace Bitrix\Crm\Service\Router\Dto;

use JsonSerializable;

final class SidePanelAnchorOptions implements JsonSerializable
{
	private ?int $width = null;
	private ?string $title = null;
	private ?bool $cacheable = null;
	private ?bool $autoFocus = null;
	private ?bool $printable = null;
	private ?bool $allowCrossOrigin = null;
	private ?bool $allowChangeHistory = null;
	private ?bool $allowChangeTitle = null;
	private ?bool $hideControls = null;
	private ?string $requestMethod = null;
	private ?array $requestParams = null;
	private ?string $loader = null;
	private ?string $contentClassName = null;
	private ?array $data = null;
	private ?array $minimizeOptions = null;
	private ?string $typeLoader = null;
	private ?int $animationDuration = null;
	private ?int $customLeftBoundary = null;
	private ?int $customRightBoundary = null;
	private ?int $customTopBoundary = null;
	private ?array $label = null;
	private ?bool $newWindowLabel = null;
	private ?string $newWindowUrl = null;
	private ?bool $copyLinkLabel = null;
	private ?bool $minimizeLabel = null;

	public function __construct()
	{
	}

	public function setWidth(?int $width): self
	{
		$this->width = $width;

		return $this;
	}

	public function setTitle(?string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function setCacheable(?bool $cacheable): self
	{
		$this->cacheable = $cacheable;

		return $this;
	}

	public function setAutoFocus(?bool $autoFocus): self
	{
		$this->autoFocus = $autoFocus;

		return $this;
	}

	public function setPrintable(?bool $printable): self
	{
		$this->printable = $printable;

		return $this;
	}

	public function setAllowCrossOrigin(?bool $allowCrossOrigin): self
	{
		$this->allowCrossOrigin = $allowCrossOrigin;

		return $this;
	}

	public function setAllowChangeHistory(?bool $allowChangeHistory): self
	{
		$this->allowChangeHistory = $allowChangeHistory;

		return $this;
	}

	public function setAllowChangeTitle(?bool $allowChangeTitle): self
	{
		$this->allowChangeTitle = $allowChangeTitle;

		return $this;
	}

	public function setHideControls(?bool $hideControls): self
	{
		$this->hideControls = $hideControls;

		return $this;
	}

	public function setRequestMethod(?string $requestMethod): self
	{
		$this->requestMethod = $requestMethod;

		return $this;
	}

	public function setRequestParams(array|JsonSerializable|null $requestParams): self
	{
		$this->requestParams = $requestParams instanceof JsonSerializable
			? $requestParams->jsonSerialize()
			: $requestParams
		;

		return $this;
	}

	public function setLoader(?string $loader): self
	{
		$this->loader = $loader;

		return $this;
	}

	public function setContentClassName(?string $contentClassName): self
	{
		$this->contentClassName = $contentClassName;

		return $this;
	}

	public function setData(array|JsonSerializable|null $data): self
	{
		$this->data = $data instanceof JsonSerializable
			? $data->jsonSerialize()
			: $data
		;

		return $this;
	}

	public function setMinimizeOptions(array|JsonSerializable|null $minimizeOptions): self
	{
		$this->minimizeOptions = $minimizeOptions instanceof JsonSerializable
			? $minimizeOptions->jsonSerialize()
			: $minimizeOptions
		;

		return $this;
	}

	public function setTypeLoader(?string $typeLoader): self
	{
		$this->typeLoader = $typeLoader;

		return $this;
	}

	public function setAnimationDuration(?int $animationDuration): self
	{
		$this->animationDuration = $animationDuration;

		return $this;
	}

	public function setCustomLeftBoundary(?int $customLeftBoundary): self
	{
		$this->customLeftBoundary = $customLeftBoundary;

		return $this;
	}

	public function setCustomRightBoundary(?int $customRightBoundary): self
	{
		$this->customRightBoundary = $customRightBoundary;

		return $this;
	}

	public function setCustomTopBoundary(?int $customTopBoundary): self
	{
		$this->customTopBoundary = $customTopBoundary;

		return $this;
	}

	public function setLabel(array|JsonSerializable|null $label): self
	{
		$this->label = $label instanceof JsonSerializable
			? $label->jsonSerialize()
			: $label
		;

		return $this;
	}

	public function setNewWindowLabel(?bool $newWindowLabel): self
	{
		$this->newWindowLabel = $newWindowLabel;

		return $this;
	}

	public function setNewWindowUrl(?string $newWindowUrl): self
	{
		$this->newWindowUrl = $newWindowUrl;

		return $this;
	}

	public function setCopyLinkLabel(?bool $copyLinkLabel): self
	{
		$this->copyLinkLabel = $copyLinkLabel;

		return $this;
	}

	public function setMinimizeLabel(?bool $minimizeLabel): self
	{
		$this->minimizeLabel = $minimizeLabel;

		return $this;
	}

	public function jsonSerialize(): array
	{
		return [
			'width' => $this->width,
			'title' => $this->title,
			'cacheable' => $this->cacheable,
			'autoFocus' => $this->autoFocus,
			'printable' => $this->printable,
			'allowCrossOrigin' => $this->allowCrossOrigin,
			'allowChangeHistory' => $this->allowChangeHistory,
			'allowChangeTitle' => $this->allowChangeTitle,
			'hideControls' => $this->hideControls,
			'requestMethod' => $this->requestMethod,
			'requestParams' => $this->requestParams,
			'loader' => $this->loader,
			'contentClassName' => $this->contentClassName,
			'data' => $this->data,
			'minimizeOptions' => $this->minimizeOptions,
			'typeLoader' => $this->typeLoader,
			'animationDuration' => $this->animationDuration,
			'customLeftBoundary' => $this->customLeftBoundary,
			'customRightBoundary' => $this->customRightBoundary,
			'customTopBoundary' => $this->customTopBoundary,
			'label' => $this->label,
			'newWindowLabel' => $this->newWindowLabel,
			'newWindowUrl' => $this->newWindowUrl,
			'copyLinkLabel' => $this->copyLinkLabel,
			'minimizeLabel' => $this->minimizeLabel,
		];
	}
}
