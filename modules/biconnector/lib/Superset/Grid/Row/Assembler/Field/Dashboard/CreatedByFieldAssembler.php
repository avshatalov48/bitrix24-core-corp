<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base\UserFieldAssembler;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

class CreatedByFieldAssembler extends UserFieldAssembler
{
	protected function prepareColumn($value): string
	{
		$ormFilter = $this->getSettings()->getOrmFilter();
		$isFiltered = isset($ormFilter['CREATED_BY_ID']) && in_array((int)$value['CREATED_BY_ID'], $ormFilter['CREATED_BY_ID']);

		$createdById = (int)$value['CREATED_BY_ID'];
		$type = $value['TYPE'];

		if ($type === SupersetDashboardTable::DASHBOARD_TYPE_MARKET)
		{
			$authorText = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_AUTHOR_MARKET');

			return "<span class=\"biconnector-grid-market-cell\">
				<img src=\"/bitrix/images/biconnector/superset-dashboard-grid/icon-type-market.png\" width='24' height='24' alt=\"{$authorText}\"> 
				<span class=\"biconnector-grid-username\">{$authorText}</span>
			</span>";
		}

		if ($type === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
		{
			$authorText = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_AUTHOR_SYSTEM');

			return "<span class=\"biconnector-grid-market-cell\">
				<img src=\"/bitrix/images/biconnector/superset-dashboard-grid/icon-type-system.png\" width='24' height='24' alt=\"{$authorText}\"> 
				<span class=\"biconnector-grid-username\">{$authorText}</span>
			</span>";
		}

		if ($createdById > 0)
		{
			$user = \CUser::getByID($createdById)->fetch();
			$avatar = '';
			if ((int)$user['PERSONAL_PHOTO'] > 0)
			{
				$avatarSrc = $this->getAvatarSrc((int)$user['PERSONAL_PHOTO']);
				$avatar = " style=\"background-image: url('{$avatarSrc}');\"";
			}

			$userName = $this->loadUserName($createdById);
			$event = Json::encode([
				'ID' => $createdById,
				'TITLE' => $userName,
				'IS_FILTERED' => $isFiltered,
			]);

			$selector = 'biconnector-grid-username-cell';
			$closeIcon = '';
			if ($isFiltered)
			{
				$selector .= ' biconnector-grid-username-cell-active';
				$closeIcon = '<div class="biconnector-grid-filter-close ui-icon-set --cross-20"></div>';
			}

			return <<<HTML
<span 
	class="$selector"
	onclick='BX.BIConnector.SupersetDashboardGridManager.Instance.handleCreatedByClick($event)'
>
	<span class="biconnector-grid-avatar ui-icon ui-icon-common-user">
		<i{$avatar}></i>
	</span>
	<span class="biconnector-grid-username">$userName</span>
	$closeIcon
</span>
HTML;
		}

		return '';
	}
}