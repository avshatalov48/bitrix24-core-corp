<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\BIConnector\Configuration\DashboardTariffConfigurator;
use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base\DetailLinkFieldAssembler;

class IdFieldAssembler extends DetailLinkFieldAssembler
{
	protected function prepareColumn($value): string
	{
		$id = (int)$value['ID'];

		if ($value['IS_TARIFF_RESTRICTED'])
		{
			$onclick = '';
			$sliderCode = DashboardTariffConfigurator::getSliderRestrictionCodeByAppId($value['APP_ID']);
			if (!empty($sliderCode))
			{
				$onclick = "onclick=\"top.BX.UI.InfoHelper.show('{$sliderCode}');\"";
			}

			$link = "
				<a 
					style='cursor: pointer' 
					{$onclick}
				>
					<span class='tariff-lock'></span> {$id}
				</a>
			";
		}
		elseif (empty($value['HAS_ZONE_URL_PARAMS']))
		{
			$link = "<a href='{$value['DETAIL_URL']}'>{$id}</a>";
		}
		else
		{
			$link = "
				<a 
					style='cursor: pointer' 
					onclick='BX.BIConnector.SupersetDashboardGridManager.Instance.showLockedByParamsPopup()'
				>
					{$id}
				</a>
			";
		}

		return $link;
	}

}