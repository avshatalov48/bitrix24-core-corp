<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body;

use Bitrix\Crm\Service\Timeline\Layout\Base;

abstract class ContentBlock extends Base
{
	public const SCOPE_WEB = 'web';
	public const SCOPE_MOBILE = 'mobile';

	protected ?int $sort = null;
	protected ?string $scope = null;

	public function getSort(): ?int
	{
		return $this->sort;
	}

	public function setSort(?int $sort): self
	{
		$this->sort = $sort;

		return $this;
	}

	abstract public function getRendererName(): string;

	protected function getProperties(): ?array
	{
		return null;
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
			'sort' => $this->getSort(),
			'scope' => $this->getScope(),
			'rendererName' => $this->getRendererName(),
			'properties' => $this->getProperties(),
		];
	}
}
