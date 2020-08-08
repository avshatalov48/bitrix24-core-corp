<?php

namespace Bitrix\Rpa\UrlManager;

class ParseResult
{
	protected $componentName;
	protected $componentParameters;
	protected $templateName;

	public function __construct(string $componentName = null, array $componentParameters = null, string $templateName = null)
	{
		$this->componentName = $componentName;
		$this->componentParameters = $componentParameters;
		$this->templateName = $templateName;
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
}