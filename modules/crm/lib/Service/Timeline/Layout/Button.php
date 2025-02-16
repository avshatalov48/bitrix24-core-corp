<?php

namespace Bitrix\Crm\Service\Timeline\Layout;

use Bitrix\Crm\Service\Timeline\Layout\Mixin\Actionable;

class Button extends Base
{
	use Actionable;

	public const STATE_DEFAULT = 'default';
	public const STATE_HIDDEN = 'hidden';
	public const STATE_DISABLED = 'disabled';
	public const STATE_LOADING = 'loading';
	public const STATE_LOCKED = 'locked';
	public const STATE_AI_LOADING = 'ai-loading';
	public const STATE_AI_SUCCESS = 'ai-success';

	public const SCOPE_WEB = 'web';
	public const SCOPE_MOBILE = 'mobile';

	protected string $title;
	protected ?string $state = null;
	protected ?array $props = null;
	protected ?string $scope = null;
	protected ?bool $hideIfReadonly = null;
	protected ?int $sort = null;
	protected ?string $tooltip = null;

	public function __construct(string $title)
	{
		$this->title = $title;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getTooltip(): ?string
	{
		return $this->tooltip;
	}

	public function setTooltip(?string $hint): self
	{
		$this->tooltip = $hint;

		return $this;
	}

	public function getState(): ?string
	{
		return $this->state;
	}

	public function setState(?string $state): self
	{
		$this->state = $state;

		return $this;
	}

	public function getProps(): ?array
	{
		return $this->props;
	}

	public function setProps(?array $props): self
	{
		$this->props = $props;

		return $this;
	}

	public function setStateHidden(): self
	{
		return $this->setState(self::STATE_HIDDEN);
	}

	public function setStateDisabled(): self
	{
		return $this->setState(self::STATE_DISABLED);
	}

	public function getHideIfReadonly(): ?bool
	{
		return $this->hideIfReadonly;
	}

	public function setHideIfReadonly(?bool $hideIfReadonly = true): self
	{
		$this->hideIfReadonly = $hideIfReadonly;

		return $this;
	}

	public function getSort(): ?int
	{
		return $this->sort;
	}

	public function setSort(int $sort): self
	{
		$this->sort = $sort;

		return $this;
	}

	public function getScope(): ?string
	{
		return $this->scope;
	}

	public function setScope(?string $scope): self
	{
		$this->scope = $scope;

		return $this;
	}

	public function setScopeWeb(): self
	{
		return $this->setScope(self::SCOPE_WEB);
	}

	public function setScopeMobile(): self
	{
		return $this->setScope(self::SCOPE_MOBILE);
	}

	public function toArray(): array
	{
		return [
			'title' => $this->getTitle(),
			'tooltip' => $this->getTooltip(),
			'state' => $this->getState(),
			'props' => $this->getProps(),
			'action' => $this->getAction(),
			'hideIfReadonly' => $this->getHideIfReadonly(),
			'sort' => $this->getSort(),
			'scope' => $this->getScope(),
		];
	}
}
