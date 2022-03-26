<?php

namespace Bitrix\Crm\Service\WebForm;

interface WebFormScenarioBuilder
{
	public function prepare(array &$options): array;
}