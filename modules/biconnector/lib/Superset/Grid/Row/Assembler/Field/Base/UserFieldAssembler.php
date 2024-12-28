<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base;

use Bitrix\Main;
use Bitrix\Main\Web\Json;

class UserFieldAssembler extends Main\Grid\Row\Assembler\Field\UserFieldAssembler
{
	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];

		foreach ($this->getColumnIds() as $columnId)
		{
			$value = $row['data'];
			$row['columns'][$columnId] = $this->prepareColumn($value);
		}

		return $row;
	}

	protected function getAvatarSrc(int $avatarId, int $width = 22, int $height = 22): string
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

	protected function prepareColumnWithParams($value, string $fieldId, string $clickHandler): string
	{
		$ormFilter = $this->getSettings()->getOrmFilter();
		$isFiltered = isset($ormFilter[$fieldId], $value[$fieldId]) && in_array((int)$value[$fieldId], $ormFilter[$fieldId]);

		$userId = (int)$value[$fieldId];

		if ($userId > 0)
		{
			$user = \CUser::getByID($userId)->fetch();
			$avatar = '';
			if ((int)$user['PERSONAL_PHOTO'] > 0)
			{
				$avatarSrc = $this->getAvatarSrc((int)$user['PERSONAL_PHOTO']);
				$avatar = " style=\"background-image: url('{$avatarSrc}');\"";
			}

			$userName = $this->loadUserName($userId);
			$event = Json::encode([
				'ID' => $userId,
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
					onclick='{$clickHandler}($event)'
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
