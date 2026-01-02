<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\UnusedElements;

use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base\UserFieldAssembler;
use Bitrix\Main\Grid\Settings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;

class OwnersFieldAssembler extends UserFieldAssembler
{
	protected function prepareColumn($value)
	{
		$ownerIds = $value['OWNERS'];
		$result = '';
		foreach ($ownerIds as $ownerId)
		{
			if ($ownerId === 0)
			{
				$ownerText = Loc::getMessage('BICONNECTOR_SUPERSET_UNUSED_ELEMENTS_GRID_OWNER_SYSTEM');

				$result .= <<<HTML
				<span class="biconnector-grid-username-cell">
					<img src="/bitrix/images/biconnector/superset-dashboard-grid/icon-type-system.png" width="24" height="24" alt="{$ownerText}"> 
					<span class="biconnector-grid-username">{$ownerText}</span>
				</span>
				HTML;

				continue;
			}

			$user = \CUser::getByID($ownerId)->fetch();
			$avatar = '';
			if ((int)$user['PERSONAL_PHOTO'] > 0)
			{
				$avatarSrc = $this->getAvatarSrc((int)$user['PERSONAL_PHOTO']);
				$avatar = " style=\"background-image: url('{$avatarSrc}');\"";
			}

			$userName = $this->loadUserName($ownerId);

			$result .= <<<HTML
			<span 
				class="biconnector-grid-username-cell"
			>
				<span class="biconnector-grid-avatar ui-icon ui-icon-common-user">
					<i{$avatar}></i>
				</span>
				<span class="biconnector-grid-username">$userName</span>
			</span>
			HTML;
		}

		return $result;
	}
}
