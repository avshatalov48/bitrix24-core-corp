<?php

namespace Bitrix\SalesCenter\Model;

use Bitrix\Main\Localization\Loc;

class PageCategory extends EO_PageCategory
{
	/**
	 * @return string
	 */
	public function getName()
	{
		$name = parent::getName();
		$langName = Loc::getMessage('SALESCENTER_PAGE_CATEGORY_NAME_'.$name);
		if($langName)
		{
			return $langName;
		}

		return $name;
	}
}