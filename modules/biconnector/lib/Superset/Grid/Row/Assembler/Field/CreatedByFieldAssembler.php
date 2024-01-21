<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

class CreatedByFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$createdById = (int)$value['CREATED_BY_ID'];
		$type = $value['TYPE'];

		if ($type === SupersetDashboardTable::DASHBOARD_TYPE_MARKET)
		{
			return '<img src="/bitrix/images/biconnector/superset-dashboard-grid/icon-type-market.svg" alt="' .
				Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_TYPE_MARKET') . '">';
		}

		if ($type === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
		{
			return '<img src="/bitrix/images/biconnector/superset-dashboard-grid/icon-type-system.svg" alt="' .
				Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_TYPE_SYSTEM') . '">';
		}

		if ($createdById > 0)
		{
			$user = \CUser::GetByID($createdById)->Fetch();
			$avatar = '';
			if ((int)$user['PERSONAL_PHOTO'] > 0)
			{
				$avatarSrc = $this->getAvatarSrc((int)$user['PERSONAL_PHOTO']);
				$avatar = " style=\"background-image: url('{$avatarSrc}');\"";
			}

			$userName = \CUser::FormatName(
				\CSite::GetNameFormat(false),
				$user,
				true
			);

			return <<<HTML
<span 
	class="biconnector-grid-avatar ui-icon ui-icon-common-user"
	data-hint="$userName" 
	data-hint-no-icon
>
	<i{$avatar}></i>
</span>
HTML;
		}

		return '';
	}

	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];

		foreach ($this->getColumnIds() as $columnId)
		{
			$value = [
				'CREATED_BY_ID' => $row['data']['CREATED_BY_ID'],
				'TYPE' => $row['data']['TYPE'],
			];
			$row['columns'][$columnId] = $this->prepareColumn($value);
		}

		return $row;
	}

	private function getAvatarSrc(int $avatarId, int $width = 22, int $height = 22): string
	{
		$imageFile = \CFile::getFileArray($avatarId);
		if ($imageFile !== false)
		{
			$fileTmp = \CFile::resizeImageGet(
				$imageFile,
				compact('width', 'height'),
				BX_RESIZE_IMAGE_EXACT,
				false
			);

			return (string)$fileTmp['src'];
		}

		return '';
	}
}
