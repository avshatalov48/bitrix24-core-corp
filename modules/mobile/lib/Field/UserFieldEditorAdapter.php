<?php

namespace Bitrix\Mobile\Field;

use Bitrix\Mobile\Field\Type\BaseField;
use Bitrix\Mobile\Field\Type\FileField;
use Bitrix\Mobile\Field\Type\HasBoundEntities;
use Bitrix\Mobile\Field\Type\IblockElementField;

class UserFieldEditorAdapter
{
	protected array $userFieldInfos = [];
	/** @var BaseField[] $fieldCollection */
	protected array $fieldCollection = [];
	protected string $fileControllerEntityId = '';
	protected array $fieldValues = [];

	public function __construct(string $entityId, array $fieldDescriptions, array $fieldValues)
	{
		global $USER_FIELD_MANAGER;
		$this->fieldValues = $fieldValues;

		$this->userFieldInfos[$entityId] = $USER_FIELD_MANAGER->GetUserFields($entityId, 0, LANGUAGE_ID);
		$entityUserFieldIds = array_keys($this->userFieldInfos[$entityId]);

		$reindexedFieldDescriptions = array_combine(array_column($fieldDescriptions, 'name'), $fieldDescriptions);
		if (!$reindexedFieldDescriptions)
		{
			return;
		}

		$boundEntities = [];
		foreach ($entityUserFieldIds as $userFieldId)
		{
			if (!isset($reindexedFieldDescriptions[$userFieldId]))
			{
				continue;
			}

			$fieldDescription = $reindexedFieldDescriptions[$userFieldId];
			$fieldInfo = $fieldDescription['data']['fieldInfo'];
			$fieldType = $fieldInfo['USER_TYPE_ID'];
			$fieldValue = $fieldValues[$userFieldId]['VALUE'] ?? '';

			$field = Factory::getField($fieldType, $userFieldId, $fieldValue);
			if ($field)
			{
				$field
					->setTitle($fieldDescription['title'])
					->setData($fieldDescription['data'])
					->setUserFieldInfo($fieldInfo)
					->setEditable($fieldDescription['editable'])
					->setMultiple($fieldInfo['MULTIPLE'] === 'Y')
					->setRequired($fieldInfo['MANDATORY'] === 'Y')
				;
			}
			$this->fieldCollection[$userFieldId] = $field;

			if ($field instanceof HasBoundEntities)
			{
				$boundEntitiesInfo = $field->getBoundEntities();
				foreach ($boundEntitiesInfo as $entityName => $entityInfo)
				{
					if (!array_key_exists($entityName, $boundEntities))
					{
						$boundEntities[$entityName] = [$entityInfo];
					}
					else
					{
						$boundEntities[$entityName][] = $entityInfo;
					}
				}
			}
		}

		if (!empty($boundEntities))
		{
			BoundEntitiesLoader::loadEntities($boundEntities);
		}
	}

	/**
	 * @param string $userFieldId
	 * @return array
	 */
	public function getAdaptedUserField(string $userFieldId): array
	{
		$field = $this->fieldCollection[$userFieldId] ?? null;
		if (!$field)
		{
			return [];
		}

		$data = $field->getData();
		if ($field instanceof FileField && $this->fileControllerEntityId)
		{
			$data['controller'] = [
				'entityId' => $this->fileControllerEntityId,
			];
		}
		elseif ($field instanceof IblockElementField)
		{
			$fieldValue = $this->fieldValues[$field->getId()];
			if (empty($fieldValue['IS_EMPTY']) && isset($fieldValue['VALUE']))
			{
				$data['provider']['options']['fieldInfo']['VALUE'] = $fieldValue['VALUE'];
			}

			$data['provider']['options']['fieldInfo']['SIGNATURE'] = $fieldValue['SIGNATURE'];
		}

		return [
			'name' => $field->getId(),
			'title' => $field->getTitle(),
			'type' => $field::TYPE,
			'data' => $data,
			'editable' => $field->isEditable(),
			'multiple' => $field->isMultiple(),
			'required' => $field->isRequired(),
		];
	}

	/**
	 * @param string $fieldId
	 * @return mixed
	 */
	public function getAdaptedUserFieldValue(string $fieldId)
	{
		$field = $this->fieldCollection[$fieldId] ?? null;
		if (!$field)
		{
			return '';
		}

		return $field->getFormattedValue();
	}

	/**
	 * @param string $fileControllerEntityId
	 */
	public function setFileControllerEntityId(string $fileControllerEntityId): void
	{
		$this->fileControllerEntityId = $fileControllerEntityId;
	}
}
