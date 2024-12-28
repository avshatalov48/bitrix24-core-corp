<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base\UserFieldAssembler;
use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

/**
 * @method DashboardSettings getSettings()
 */
class OwnerFieldAssembler extends UserFieldAssembler
{
	protected function prepareColumn($value)
	{
		$ormFilter = $this->getSettings()->getOrmFilter();
		$isFiltered = isset($ormFilter['OWNER_ID']) && in_array((int)$value['OWNER_ID'], $ormFilter['OWNER_ID']);

		$ownerId = (int)$value['OWNER_ID'];
		$type = $value['TYPE'];

		if ($type === SupersetDashboardTable::DASHBOARD_TYPE_MARKET)
		{
			$ownerText = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_OWNER_MARKET');

			return "<span class=\"biconnector-grid-market-cell\">
				<img src=\"/bitrix/images/biconnector/superset-dashboard-grid/icon-type-market.png\" width='24' height='24' alt=\"{$ownerText}\"> 
				<span class=\"biconnector-grid-username\">{$ownerText}</span>
			</span>";
		}

		if ($type === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
		{
			$ownerText = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_OWNER_SYSTEM');

			return "<span class=\"biconnector-grid-market-cell\">
				<img src=\"/bitrix/images/biconnector/superset-dashboard-grid/icon-type-system.png\" width='24' height='24' alt=\"{$ownerText}\"> 
				<span class=\"biconnector-grid-username\">{$ownerText}</span>
			</span>";
		}

		if ($ownerId > 0)
		{
			$user = \CUser::getByID($ownerId)->fetch();
			$avatar = '';
			if ((int)$user['PERSONAL_PHOTO'] > 0)
			{
				$avatarSrc = $this->getAvatarSrc((int)$user['PERSONAL_PHOTO']);
				$avatar = " style=\"background-image: url('{$avatarSrc}');\"";
			}

			$userName = $this->loadUserName($ownerId);

			$event = Json::encode([
				'ID' => $ownerId,
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
	onclick='BX.BIConnector.SupersetDashboardGridManager.Instance.handleOwnerClick($event)'
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