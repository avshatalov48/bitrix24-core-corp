<?php

namespace Bitrix\AI\ShareRole\Components\Filter;

use Bitrix\Main\Filter\DataProvider;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI;

class ShareRoleDataProvider extends DataProvider
{

	private string $gridId;

	public function __construct(string $gridId)
	{
		$this->gridId = $gridId;
	}

	public function getSettings(): Settings
	{
		return new Settings([
			'ID' => $this->gridId,
		]);
	}

	public function prepareFields(): array
	{
		$result = [];

		$result['DATE_CREATE'] = $this->createField('DATE_CREATE', [
			'name' => Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_DATE_CREATE'),
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
			'name' => Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_DATE_MODIFY'),
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
			'name' => Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_AUTHOR'),
			'type' => 'entity_selector',
			'default' => true,
			'partial' => true,
		]);

		$result['EDITOR'] = $this->createField('EDITOR', [
			'name' => Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_EDITOR'),
			'type' => 'entity_selector',
			'default' => false,
			'partial' => true,
		]);

		$result['SHARE'] = $this->createField('SHARE', [
			'name' => Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_SHARE'),
			'type' => 'entity_selector',
			'default' => false,
			'partial' => true,
		]);

		$result['IS_ACTIVE'] = $this->createField('IS_ACTIVE', [
			'name' => Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_ACTIVE'),
			'type' => 'checkbox',
			'default' => true,
		]);

		$result['IS_DELETED'] = $this->createField('IS_DELETED', [
			'name' => Loc::getMessage('ROLE_LIBRARY_GRID_COLUMN_DELETED'),
			'type' => 'checkbox',
			'default' => false,
		]);

		return $result;
	}

	public function prepareFieldData($fieldID): ?array
	{
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

		return null;
	}
}