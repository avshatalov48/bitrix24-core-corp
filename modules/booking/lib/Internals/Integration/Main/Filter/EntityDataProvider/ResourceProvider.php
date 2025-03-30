<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Main\Filter\EntityDataProvider;

use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\ResourceTypeProvider;
use Bitrix\Main;
use Bitrix\Main\Engine\CurrentUser;

class ResourceProvider extends Main\Filter\EntityDataProvider
{
	public function getSettings()
	{
		// @TODO: Implement getSettings() method.
	}

	public function prepareFields()
	{
		return [
			'ID' => $this->createField('ID', [
				//@todo lang
				'name' => 'Id',
				'default' => true,
				'type' => 'text',
			]),
			'TYPE_ID' => $this->createField('TYPE_ID', [
				//@todo lang
				'name' => 'Type',
				'default' => true,
				'type' => 'list',
				'partial' => true,
			]),
			'NAME' => $this->createField('NAME', [
				//@todo lang
				'name' => 'Name',
				'default' => true,
				'type' => 'text',
			]),
			'DESCRIPTION' => $this->createField('DESCRIPTION', [
				//@todo lang
				'name' => 'Description',
				'default' => true,
				'type' => 'text',
			]),
		];
	}

	protected function getFieldName($fieldID)
	{
		//@todo lang
		return $fieldID;

		//return Main\Localization\Loc::getMessage("LANG_PHRASE_{$fieldID}");
	}

	public function prepareFieldData($fieldID)
	{
		if ($fieldID === 'TYPE_ID')
		{
			$resourceTypes = (new ResourceTypeProvider())->getList(
				new GridParams(
					limit: 100,
				),
				userId: (int)CurrentUser::get()->getId(),
			);

			$items = [];
			foreach ($resourceTypes as $resourceType)
			{
				$items[$resourceType->getId()] = $resourceType->getName();
			}

			return [
				'params' => [
					'multiple' => 'Y',
				],
				'items' => $items,
			];
		}

		return null;
	}

	public function getGridColumns(): array
	{
		return [
			[
				'id' => 'ID',
				//@todo lang
				'name' => 'Id',
				'sort' => 'ID',
				'default' => true,
			],
			[
				'id' => 'TYPE_ID',
				//@todo lang
				'name' => 'Type',
				'sort' => 'TYPE.NAME',
				'default' => true,
			],
			[
				'id' => 'NAME',
				//@todo lang
				'name' => 'Name',
				'sort' => 'NAME',
				'default' => true,
			],
			[
				'id' => 'DESCRIPTION',
				//@todo lang
				'name' => 'Description',
				'sort' => 'DESCRIPTION',
				'default' => true,
			],
		];
	}
}
