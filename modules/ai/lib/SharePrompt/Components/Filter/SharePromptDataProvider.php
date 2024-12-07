<?php

namespace Bitrix\AI\SharePrompt\Components\Filter;

use Bitrix\Main\Filter\DataProvider;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI;

class SharePromptDataProvider extends DataProvider
{
	private string $gridId;

	public function __construct(string $gridID)
	{
		$this->gridId = $gridID;
	}

	/**
	 * @inheritDoc
	 */
	public function getSettings(): Settings
	{
		return new Settings([
			'ID' => $this->gridId,
		]);
	}

	/**
	 * @inheritDoc
	 */
	public function prepareFields(): array
	{
		$result = [];

		$result['TYPE'] = $this->createField('TYPE', [
			'name' => Loc::getMessage('PROMPT_LIBRARY_GRID_COLUMN_TYPE'),
			'type' => 'list',
			'default' => false,
			'partial' => true,
		]);

		$result['DATE_CREATE'] = $this->createField('DATE_CREATE', [
			'name' => Loc::getMessage('PROMPT_LIBRARY_GRID_COLUMN_DATE_CREATE'),
			'type' => 'date',
			'default' => false,
			'partial' => true,
			'time' => false,
			'data' => [
				'exclude' => [
					UI\Filter\DateType::TOMORROW,
					UI\Filter\DateType::NEXT_DAYS,
					UI\Filter\DateType::NEXT_WEEK,
					UI\Filter\DateType::NEXT_MONTH,
				],
			],
		]);

		$result['DATE_MODIFY'] = $this->createField('DATE_MODIFY', [
			'name' => Loc::getMessage('PROMPT_LIBRARY_GRID_COLUMN_DATE_MODIFY'),
			'type' => 'date',
			'default' => false,
			'partial' => true,
			'time' => false,
			'data' => [
				'exclude' => [
					UI\Filter\DateType::TOMORROW,
					UI\Filter\DateType::NEXT_DAYS,
					UI\Filter\DateType::NEXT_WEEK,
					UI\Filter\DateType::NEXT_MONTH,
				],
			],
		]);

		$result['AUTHOR'] = $this->createField('AUTHOR', [
			'name' => Loc::getMessage('PROMPT_LIBRARY_GRID_COLUMN_AUTHOR'),
			'type' => 'entity_selector',
			'default' => true,
			'partial' => true,
		]);

		$result['EDITOR'] = $this->createField('EDITOR', [
			'name' => Loc::getMessage('PROMPT_LIBRARY_GRID_COLUMN_EDITOR'),
			'type' => 'entity_selector',
			'default' => false,
			'partial' => true,
		]);

		$result['SHARE'] = $this->createField('SHARE', [
			'name' => Loc::getMessage('PROMPT_LIBRARY_GRID_COLUMN_SHARE'),
			'type' => 'entity_selector',
			'default' => false,
			'partial' => true,
		]);

		$result['CATEGORIES'] = $this->createField('CATEGORIES', [
			'name' => Loc::getMessage('PROMPT_LIBRARY_GRID_COLUMN_ACCESS'),
			'type' => 'entity_selector',
			'default' => false,
			'partial' => true,
		]);

		$result['IS_ACTIVE'] = $this->createField('IS_ACTIVE', [
			'name' => Loc::getMessage('PROMPT_LIBRARY_GRID_COLUMN_ACTIVE'),
			'type' => 'checkbox',
			'default' => true,
		]);

		$result['IS_DELETED'] = $this->createField('IS_DELETED', [
			'name' => Loc::getMessage('PROMPT_LIBRARY_GRID_COLUMN_DELETED'),
			'type' => 'checkbox',
			'default' => false,
		]);

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function prepareFieldData($fieldID): ?array
	{
		if ($fieldID === 'TYPE')
		{
			return [
				'items' => [
					'DEFAULT' => Loc::getMessage('PROMPT_LIBRARY_GRID_PROMPT_TYPE_DEFAULT'),
					'SIMPLE_TEMPLATE' => Loc::getMessage('PROMPT_LIBRARY_GRID_PROMPT_TYPE_SIMPLE_TEMPLATE'),
				],
			];
		}

		if (in_array($fieldID, ['DATE_MODIFY', 'DATE_CREATE']))
		{
			return [
				'exclude' => [
					UI\Filter\DateType::TOMORROW,
					UI\Filter\DateType::NEXT_DAYS,
					UI\Filter\DateType::NEXT_WEEK,
					UI\Filter\DateType::NEXT_MONTH,
				],
			];
		}

		if ($fieldID === 'AUTHOR' || $fieldID === 'EDITOR')
		{
			return [
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'context' => 'filter',
						'entities' => [
							[
								'id' => 'user',
								'dynamicLoad' => true,
								'dynamicSearch' => true,
							],
						],
					],
				],
			];
		}

		if ($fieldID === 'SHARE')
		{
			return [
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'context' => 'filter',
						'entities' => [
							[
								'id' => 'user',
								'options' => [
									'inviteEmployeeLink' => false,
								],
							],
							[
								'id' => 'department',
								'options' => [
									'selectMode' => 'usersAndDepartments',
								],
							],
							[
								'id' => 'meta-user',
								'options' => [
									'all-users' => true,
								],
							],
							[
								'id' => 'project',
							],
						],
					],
				],
			];
		}

		if ($fieldID === 'CATEGORIES')
		{
			return [
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'context' => 'filter',
						'compactView' => true,
						'dropdownMode' => true,
						'width' => 300,
						'entities' => [
							[
								'id' => 'prompt-category',
								'dynamicLoad' => true,
								'dynamicSearch' => true,
							],
						],
					],
				],
			];
		}

		return null;
	}
}
