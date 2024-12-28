<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field;

use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Grid\Settings;

class TagsFieldAssembler extends CustomUserFieldAssembler
{
	private array $userTagsList;

	public function __construct(array $columnIds, ?Settings $settings = null)
	{
		parent::__construct($columnIds, $settings);

		$this->userTagsList = [];

		$rawTagsList = \Bitrix\Socialnetwork\UserTagTable::getList([
			'select' => ['USER_ID', 'NAME'],
		])->fetchAll();

		foreach ($rawTagsList as $rawTagsRow)
		{
			$this->userTagsList[$rawTagsRow['USER_ID']][] = $rawTagsRow['NAME'];
		}
	}

	protected function prepareColumn($value): mixed
	{
		$userTagsList = $this->getUserTagsList($value['ID']);

		if (empty($userTagsList))
		{
			return '';
		}

		$excelMode = $this->getSettings()->isExcelMode();

		return implode(', ', array_map(
			function ($userTag) use ($excelMode)
			{
				$uri = new \Bitrix\Main\Web\Uri('');

				$uri->addParams([
					'apply_filter' => 'Y',
					'TAGS' => $userTag
				]);

				return
					$excelMode
						? $userTag
						: '<a href="'.$uri->getUri().'" rel="nofollow" bx-tag-value="'.htmlspecialcharsBx($userTag).'">'.htmlspecialcharsBx($userTag).'</a>'
					;
			},
			$userTagsList
		));
	}

	private function getUserTagsList(int $userId = 0): array
	{
		return $this->userTagsList[$userId] ?? [];
	}
}