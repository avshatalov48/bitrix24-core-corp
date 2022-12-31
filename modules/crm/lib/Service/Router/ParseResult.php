<?php

namespace Bitrix\Crm\Service\Router;

class ParseResult
{
	protected $componentName;
	protected $componentParameters;
	protected $templateName;
	protected int $entityTypeId = \CCrmOwnerType::Undefined;

	public function __construct(
		string $componentName = null,
		array $componentParameters = null,
		string $templateName = null,
		int $entityTypeId = \CCrmOwnerType::Undefined
	)
	{
		$this->componentName = $componentName;
		$this->componentParameters = $componentParameters;
		$this->templateName = $templateName;
		$this->entityTypeId = $entityTypeId;
	}

	public function isFound(): bool
	{
		return ($this->componentName !== null);
	}

	public function getComponentName(): ?string
	{
		return $this->componentName;
	}

	public function getComponentParameters(): ?array
	{
		return $this->componentParameters;
	}

	public function getTemplateName(): ?string
	{
		return $this->templateName;
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}
}
