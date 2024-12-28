<?php

namespace Bitrix\Sign\Service\Providers;

use Bitrix\Sign\Type\FieldType;

abstract class InfoProvider
{
	protected const USER_FIELD_ENTITY_ID = '';

	/**
	 * @return list<array{
	 *     type: string,
	 *     entity_name: string,
	 *	   name: string,
	 *     caption: string,
	 *     multiple: bool,
	 *     required: bool,
	 *     hidden: bool
	 * }>
	 */
	public function getFieldsForSelector(): array
	{
		$fields = $this->getUserFields();

		$result = [];
		foreach ($fields as $field)
		{
			$result[] = [
				'type' => $field['USER_TYPE_ID'],
				'entity_name' => $field['ENTITY_ID'],
				'name' => $field['FIELD_NAME'],
				'caption' => $this->getCaption($field),
				'multiple' => false,
				'required' => false,
				'hidden' => false,
			];
		}

		return $result;
	}

	/**
	 * @return array<string, array{
	 *     type: string,
	 *     caption: string,
	 *     sort: int,
	 *     source:int,
	 *     sourceName: string,
	 *     entityId: string,
	 *     userFieldId: int,
	 *	   items: list<array{id: int, value: string, sort: int, userFieldId: int, xmlId: string}>
	 *	 }>
	 */
	public function getFieldsMap(): array
	{
		$result = [];

		$fields = $this->getUserFields();
		foreach ($fields as $field)
		{
			$items = null;
			if ($field['USER_TYPE_ID'] === FieldType::ENUMERATION)
			{
				$items = $this->getEnumItems($field);
			}

			$result[$field['FIELD_NAME']] = [
				'type' => $this->getType($field),
				'caption' => $this->getCaption($field),
				'sort' => $field['SORT'],
				'source' => ProfileProvider::SOURCE_UF,
				'sourceName' => $field['FIELD_NAME'],
				'entityId' => $field['ENTITY_ID'],
				'userFieldId' => $field['ID'],
				'items' => $items,
			];
		}

		return $result;
	}

	public function getUserFields(): array
	{
		global $USER_FIELD_MANAGER;

		$fields = $USER_FIELD_MANAGER->getUserFields(
			entity_id: static::USER_FIELD_ENTITY_ID,
			LANG: LANGUAGE_ID
		);

		if (!is_array($fields))
		{
			return [];
		}

		return $fields;
	}

	/**
	 * @param string $fieldName
	 *
	 * @return array{
	 *      type: string,
	 *      caption: string,
	 *      sort: int,
	 *      source:int,
	 *      sourceName: string,
	 *      entityId: string,
	 *      userFieldId: int,
	 *       items: list<array{id: int, value: string, sort: int, userFieldId: int, xmlId: string}>
	 *     }|null
	 */
	public function getFieldDescription(string $fieldName): ?array
	{
		return $this->getFieldsMap()[$fieldName] ?? null;
	}

	public function getCaption(array $field): string
	{
		return $field['EDIT_FORM_LABEL']
			?? $field['LIST_COLUMN_LABEL']
			?? $field['LIST_FILTER_LABEL']
			?? $field['FIELD_NAME']
			?? ''
		;
	}

	protected function getType(array $field): string
	{
		return $field['USER_TYPE_ID'] ?? FieldType::STRING;
	}

	/**
	 * @param array{ID: int} $field
	 *
	 * @return list<array{id: int, value: string, sort: int, userFieldId: int, xmlId: string}>
	 */
	private function getEnumItems(array $field): array
	{
		$items = [];

		$dbRes = \CUserFieldEnum::GetList([], ['USER_FIELD_ID' => (int)$field['ID']]);
		while ($res = $dbRes->Fetch())
		{
			$items[] = [
				'id' => $res['ID'],
				'value' => $res['VALUE'],
				'sort' => $res['SORT'],
				'userFieldId' => $res['USER_FIELD_ID'],
				'xmlId' => $res['XML_ID'],
			];
		}

		return $items;
	}
}
