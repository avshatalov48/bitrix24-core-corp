<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\BIConnector\Configuration\DashboardTariffConfigurator;
use Bitrix\BIConnector\Integration\Superset\Repository\DashboardGroupRepository;
use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base\DetailLinkFieldAssembler;
use Bitrix\Main\Web\Json;

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
		elseif ($value['ENTITY_TYPE'] === DashboardGroupRepository::TYPE_GROUP)
		{
			$ormFilter = $this->getSettings()->getOrmFilter();
			$eventGroup = Json::encode([
				'ID' => (int)$value['ID'],
				'TITLE' => $value['TITLE'],
				'IS_FILTERED' => isset($ormFilter['GROUPS.ID']) && in_array((int)$value['ID'], $ormFilter['GROUPS.ID']),
			]);

			$link = "
				<a 
					style='cursor: pointer'
					onclick='BX.BIConnector.SupersetDashboardGridManager.Instance.handleGroupTitleClick($eventGroup)'
				>
					{$id}
				</a>
			";
		}
		elseif (!$value['IS_ACCESS_ALLOWED'])
		{
			$link = $id;
		}
		else
		{
			$link = "<a href='{$value['DETAIL_URL']}' target='_blank'>{$id}</a>";
		}

		return $link;
	}
}
