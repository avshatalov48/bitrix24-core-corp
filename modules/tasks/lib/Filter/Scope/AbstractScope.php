<?php

namespace Bitrix\Tasks\Filter\Scope;

use Bitrix\Tasks\Filter\PresetCollection;

abstract class AbstractScope
{
	abstract public function getPresetCollection(): PresetCollection;
}
