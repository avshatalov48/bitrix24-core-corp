<?php

namespace Bitrix\Intranet\CustomSection\Provider;

class Component
{
	/** @var string */
	protected $componentName;
	/** @var string */
	protected $componentTemplate = '';
	/** @var array */
	protected $componentParams = [];

	/**
	 * Returns name of a component to include, e.g., bitrix:intranet.component
	 *
	 * @return string
	 */
	public function getComponentName(): string
	{
		return $this->componentName;
	}

	/**
	 * Set name of a component to include, e.g., bitrix:intranet.component
	 *
	 * @param string $componentName
	 *
	 * @return Component
	 */
	public function setComponentName(string $componentName): Component
	{
		$this->componentName = $componentName;

		return $this;
	}

	/**
	 * Returns name of a component template, e.g., '.default'
	 *
	 * @return string
	 */
	public function getComponentTemplate(): string
	{
		return $this->componentTemplate;
	}

	/**
	 * Sets name of a component template, e.g., '.default'
	 *
	 * @param string $componentTemplate
	 *
	 * @return Component
	 */
	public function setComponentTemplate(string $componentTemplate): Component
	{
		$this->componentTemplate = $componentTemplate;

		return $this;
	}

	/**
	 * Returns array of params ($arParams) of component to include
	 *
	 * @return array
	 */
	public function getComponentParams(): array
	{
		return $this->componentParams;
	}

	/**
	 * Sets array of params ($arParams) of component to include
	 *
	 * @param array $componentParams
	 *
	 * @return Component
	 */
	public function setComponentParams(array $componentParams): Component
	{
		$this->componentParams = $componentParams;

		return $this;
	}
}
