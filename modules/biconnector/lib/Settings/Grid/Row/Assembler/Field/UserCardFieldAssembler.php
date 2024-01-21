<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field;

use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Grid\Settings;
use Bitrix\Main\Web\Uri;

class UserCardFieldAssembler extends FieldAssembler
{
	private Dto\UserCard $userColumns;

	public function __construct(Dto\UserCard $userColumns, array $columnIds, ?Settings $settings = null)
	{
		$this->userColumns = $userColumns;
		parent::__construct($columnIds, $settings);
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
			if (!isset($row['data'][$columnId]))
			{
				$row['columns'][$columnId] = null;
				continue;
			}

			$userName = \CUser::FormatName(
				\CSite::GetNameFormat(false),
				[
					'ID' => $row['data'][$columnId],
					'NAME' => $row['data'][$this->userColumns->getName()] ?? null,
					'LAST_NAME' => $row['data'][$this->userColumns->getLastName()] ?? null,
					'SECOND_NAME' => $row['data'][$this->userColumns->getSecondName()] ?? null,
					'EMAIL' => $row['data'][$this->userColumns->getEmail()] ?? null,
					'LOGIN' => $row['data'][$this->userColumns->getLogin()] ?? null,
				],
				true
			);

			$userEmptyAvatar = ' biconnector-grid-avatar-empty';
			$userAvatar = '';

			if (isset($row['data'][$this->userColumns->getPhoto()]))
			{
				$fileInfo = \CFile::ResizeImageGet(
					$row['data'][$this->userColumns->getPhoto()],
					['width' => 60, 'height' => 60],
					BX_RESIZE_IMAGE_EXACT
				);

				if (is_array($fileInfo) && isset($fileInfo['src']))
				{
					$userEmptyAvatar = '';
					$photoUrl = $fileInfo['src'];
					$userAvatar = ' style="background-image: url(\'' . Uri::urnEncode($photoUrl) . '\')"';
				}
			}

			$userNameElement = '<span class="biconnector-grid-avatar ui-icon ui-icon-common-user'
				. $userEmptyAvatar
				. '">'
				. '<i'
				. $userAvatar
				. '></i>'
				. '</span>'
				. '<span class="biconnector-grid-username-inner">'
				. $userName
				. '</span>';

			$result = '<div class="biconnector-grid-username-wrapper">'
				. '<a class="biconnector-grid-username" href="/company/personal/user/'
				. $row['data'][$columnId]
				. '/">'
				. $userNameElement
				. '</a>'
				. '</div>';

			$row['columns'][$columnId] = $result;
		}

		return $row;
	}
}
