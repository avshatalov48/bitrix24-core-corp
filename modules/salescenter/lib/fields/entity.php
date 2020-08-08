<?php

namespace Bitrix\SalesCenter\Fields;

use Bitrix\Main\Entity\Base;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;

abstract class Entity
{
	abstract public function getCode(): string;

	/**
	 * @return string|ORM\Data\DataManager
	 */
	protected function getTableClassName(): ?string
	{
		return null;
	}

	protected function getUserFieldEntity(): ?string
	{
		return null;
	}

	/**
	 * @return Field[]
	 */
	public function getFields(): array
	{
		$result = [];

		$entity = $this->getEntity();
		if(!$entity)
		{
			return $result;
		}
		$fields = array_keys($entity->getFields());
		$hiddenFields = $this->getHiddenFields();
		$fields = array_diff($fields, $hiddenFields);
		foreach($fields as $fieldName)
		{
			$field = $entity->getField($fieldName);
			if($field instanceof ORM\Fields\StringField || $field instanceof ORM\Fields\IntegerField || $field instanceof ORM\Fields\FloatField)
			{
				$title = $field->getTitle();
				if(Loc::getMessage($field->getLangCode()) <> '' || $fieldName === 'ID')
				{
					$result[] = new Field($fieldName, [
						'title' => $title,
					]);
				}
			}
		}

		$result = array_merge($result, $this->getUserFields());

		return $result;
	}

	public function getName(): string
	{
		return Loc::getMessage('SALESCENTER_FIELDS_ENTITY_'.mb_strtoupper(static::getCode()));
	}

	public function loadData(int $id): ?array
	{
		$result = null;
		$tableClassName = static::getTableClassName();
		if($tableClassName)
		{
			$result = $tableClassName::getById($id)->fetch();
			if(!$result)
			{
				$result = null;
			}
		}

		return $result;
	}

	protected function getHiddenFields(): ?array
	{
		return [];
	}

	protected function getEntity(): ?Base
	{
		/** @var \Bitrix\Main\Entity\DataManager $className */
		$className = $this->getTableClassName();
		if($className)
		{
			return Base::getInstance($className);
		}

		return null;
	}

	protected function getUserFields(): array
	{
		$result = [];

		$userFieldEntity = $this->getUserFieldEntity();
		if(!$userFieldEntity)
		{
			return $result;
		}

		$hiddenFields = $this->getHiddenFields();

		global $USER_FIELD_MANAGER;
		$fields = $USER_FIELD_MANAGER->GetUserFields($userFieldEntity, 0, LANGUAGE_ID);
		foreach($fields as $field)
		{
			if(in_array($field['FIELD_NAME'], $hiddenFields))
			{
				continue;
			}
			if($field['EDIT_IN_LIST'] !== 'Y')
			{
				continue;
			}
			$result[] = new Field($field['FIELD_NAME'], [
				'title' => $this->getUserFieldTitle($field),
			]);
		}

		return $result;
	}

	protected function getUserFieldTitle(array $userField): ?string
	{
		if($userField['EDIT_FORM_LABEL'])
		{
			return $userField['EDIT_FORM_LABEL'];
		}
		elseif($userField['LIST_COLUMN_LABEL'])
		{
			return $userField['LIST_COLUMN_LABEL'];
		}
		elseif($userField['LIST_FILTER_LABEL'])
		{
			return $userField['LIST_FILTER_LABEL'];
		}

		return null;
	}
}