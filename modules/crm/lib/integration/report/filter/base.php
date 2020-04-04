<?php

namespace Bitrix\Crm\Integration\Report\Filter;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Report\VisualConstructor\Helper\Filter;

class Base extends Filter
{
	public static function getFieldsList()
	{
		Loader::includeModule('socialnetwork');
		Extension::load([
			'socnetlogdest',
			'crm.report.filterselectors'
		]);
		return parent::getFieldsList();
	}

}