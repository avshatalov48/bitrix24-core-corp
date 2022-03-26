<?php

namespace Bitrix\Crm\Service\WebForm\Scenario\DependencyScenario;

interface DependencyScenario
{
	/**
	 * @return array
	 */
	public function getFields(): array;

	/**
	 * @return array
	 */
	public function getDependencies(): array;
}