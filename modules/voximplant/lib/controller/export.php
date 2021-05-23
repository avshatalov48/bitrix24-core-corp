<?php

namespace Bitrix\Voximplant\Controller;

use Bitrix\Main;

class Export extends Main\Controller\Export
{
	/** @var string - Module Id. */
	protected $module = 'voximplant';

	/**
	 * Returns file name
	 *
	 * @return string
	 */
	protected function generateExportFileName()
	{
		$fileExt = 'xls';

		$prefix = 'calls_detail'. '_' . date('Ymd');
		$hash = str_pad(dechex(crc32($prefix)), 8, '0', STR_PAD_LEFT);

		return uniqid($prefix. '_' . $hash. '_', false). '.' .$fileExt;
	}
}
