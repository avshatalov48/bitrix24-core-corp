<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field;

use Bitrix\Main;

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
}
