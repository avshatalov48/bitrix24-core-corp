<?php

namespace Bitrix\Tasks\Helper;


class Options extends \Bitrix\Main\UI\Filter\Options
{
	public function updateDefaultPresets(): void
	{
		$this->options['update_default_presets'] = true;
		$this->save();
	}
}