<?php

namespace Bitrix\ListsMobile\EntityEditor;

use Bitrix\Lists\Api\Service\AccessService;
use Bitrix\Lists\Security\ElementRight;
use Bitrix\Lists\Service\Param;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::requireModule('ui');

class Provider extends \Bitrix\UI\EntityEditor\BaseProvider
{
	private string $iBlockTypeId;
	private array $entity;
	private array $entityFields;
	private array $wfSections = [];
	private array $wfValues = [];
	private array $wfFields = [];

	private bool $useSectionBorder = false;

	public function __construct(array $element, array $fields, array $wfStates)
	{
		$this->entity = $element;
		$this->iBlockTypeId = $element['IBLOCK_TYPE_ID'] ?? '';

		$this->entityFields = [];
		$this->prepareEntityFields($fields);
		$this->prepareWfStates($wfStates);
	}

	public function useSectionBorder(bool $flag = true): void
	{
		$this->useSectionBorder = $flag;
	}

	public static function getBaseFieldsMap(): array
	{
		$defaultSettings = [
			'SHOW_ADD_FORM' => 'Y',
			'SHOW_EDIT_FORM' => 'Y',
			'ADD_READ_ONLY_FIELD' => 'N',
			'EDIT_READ_ONLY_FIELD' => 'N',
		];
		$defaultReadOnlySettings = [
			'SHOW_ADD_FORM' => 'N',
			'SHOW_EDIT_FORM' => 'Y',
			'ADD_READ_ONLY_FIELD' => 'Y',
			'EDIT_READ_ONLY_FIELD' => 'Y',
		];

		return [
			'NAME' => ['TYPE' => 'S', 'SETTINGS' => $defaultSettings],
			'SORT' => ['TYPE' => 'N', 'SETTINGS' => $defaultSettings],
			'ACTIVE_FROM' => ['TYPE' => 'S:DateTime', 'SETTINGS' => $defaultSettings],
			'ACTIVE_TO' => ['TYPE' => 'S:DateTime', 'SETTINGS' => $defaultSettings],
			'PREVIEW_PICTURE' => ['TYPE' => 'F', 'SETTINGS' => $defaultSettings],
			'DETAIL_PICTURE' => ['TYPE' => 'F', 'SETTINGS' => $defaultSettings],
			'DETAIL_TEXT' => ['TYPE' => 'S:HTML', 'SETTINGS' => $defaultSettings],
			'PREVIEW_TEXT' => ['TYPE' => 'S:HTML', 'SETTINGS' => $defaultSettings],

			'DATE_CREATE' => ['TYPE' => 'S:DateTime', 'SETTINGS' => $defaultReadOnlySettings],
			'TIMESTAMP_X' => ['TYPE' => 'S:DateTime', 'SETTINGS' => $defaultReadOnlySettings],
			'CREATED_BY' => ['TYPE' => 'S:employee', 'SETTINGS' => $defaultReadOnlySettings],
			'MODIFIED_BY' => ['TYPE' => 'S:employee', 'SETTINGS' => $defaultReadOnlySettings],
		];
	}

	private function prepareEntityFields(array $fields): void
	{
		$entityId = $this->getEntityId() ?? 0;
		$isNewEntity = $entityId <= 0;

		$baseFieldsMap = self::getBaseFieldsMap();

		foreach ($fields as $fieldId => $property)
		{
			$modifiedProperty = $property;
			if (array_key_exists($fieldId, $baseFieldsMap))
			{
				$modifiedProperty = array_merge($property, $baseFieldsMap[$fieldId]);
				if (isset($baseFieldsMap[$fieldId]['SETTINGS'], $property['SETTINGS']))
				{
					$modifiedProperty['SETTINGS'] = array_merge(
						$baseFieldsMap[$fieldId]['SETTINGS'],
						is_array($property['SETTINGS']) ? $property['SETTINGS'] : [],
					);
				}
			}

			$elementField =
				(new ElementField($modifiedProperty))
					->setEntityId($entityId)
					->setEntityValue($this->entity[$fieldId] ?? null)
			;

			if (
				($isNewEntity && !$elementField->isShowInAddForm())
				|| (!$isNewEntity && !$elementField->isShowInEditForm())
			)
			{
				continue;
			}

			$this->entityFields[$fieldId] = $elementField->getPreparedProperty();
		}
	}

	private function prepareWfStates(array $states)
	{
		if (!Loader::includeModule('bizprocmobile') || !Loader::includeModule('lists'))
		{
			return;
		}

		$complexDocumentType =
			\BizprocDocument::generateDocumentComplexType($this->iBlockTypeId, $this->entity['IBLOCK_ID'])
		;

		foreach ($states as $state)
		{
			if ($state['ID'] !== '')
			{
				continue;
			}

			$parameters = $state['TEMPLATE_PARAMETERS'] ?? [];

			if (!empty($parameters) && is_array($parameters) && !empty($state['TEMPLATE_NAME']))
			{
				$converter = new \Bitrix\BizprocMobile\EntityEditor\Converter($parameters, null);
				$converter
					->setDocumentType($complexDocumentType)
					->setContext(
					\Bitrix\BizprocMobile\EntityEditor\Converter::CONTEXT_PARAMETERS,
					['templateId' => $state['TEMPLATE_ID']]
					)
				;

				$sectionName = 'template_' . $state['TEMPLATE_ID'];

				$convertedParameters = $converter->toMobile()->getConvertedProperties();
				$elements = [];
				foreach ($convertedParameters as $key => $property)
				{
					$elementName = $sectionName . '_' . $key;
					$elements[] = ['name' => $elementName];

					$this->wfFields[$elementName] = array_merge(
						$property,
						[
							'name' => $elementName,
							'showAlways' => true,
							'showNew' => true,
						]
					);
					$this->wfValues[$elementName] = $property['custom']['default'] ?? null;
				}

				$this->wfValues['TEMPLATE_ID_' . $state['TEMPLATE_ID']] = $state['TEMPLATE_ID'];
				$this->wfSections[] = [
					'name' => $sectionName,
					'title' => $state['TEMPLATE_NAME'],
					'type' => 'section',
					'elements' => $elements,
				];
			}
		}

		$entityId = (int)$this->getEntityId();
		$this->wfValues['SIGNED_DOCUMENT'] = \CBPDocument::signParameters(
			[$complexDocumentType, $entityId !== 0 ? (string)$entityId : '']
		);
	}

	public function getGUID(): string
	{
		return 'LISTS_ELEMENT_DETAIL';
	}

	public function getEntityId(): ?int
	{
		return $this->entity['ID'] ?? null;
	}

	public function getEntityTypeName(): string
	{
		return 'lists_element';
	}

	public function getEntityFields(): array
	{
		$fields = $this->entityFields;
		if (
			isset($fields['IBLOCK_SECTION_ID']['custom']['hasSections'])
			&& !$fields['IBLOCK_SECTION_ID']['custom']['hasSections']
		)
		{
			unset($fields['IBLOCK_SECTION_ID']);
		}

		if ($this->wfFields)
		{
			$fields = array_merge($fields, $this->wfFields);
		}

		return $fields;
	}

	public function getEntityConfig(): array
	{
		$columnElements = [
			$this->getMainSection(),
			$this->getSectionSection(),
		];

		if ($this->wfSections)
		{
			foreach ($this->wfSections as $section)
			{
				if (isset($section['data']))
				{
					$section['data'] = array_merge($section['data'], [
						'showBorder' => $this->useSectionBorder,
					]);
				}
				else
				{
					$section['data'] = ['showBorder' => $this->useSectionBorder];
				}

				$columnElements[] = $section;
			}
		}

		return [
			[
				'name' => 'default_column',
				'type' => 'column',
				'elements' => $columnElements,
			],
		];
	}

	private function getMainSection(): array
	{
		$elements = $this->entityFields;
		unset($elements['IBLOCK_SECTION_ID']);
		\Bitrix\Main\Type\Collection::sortByColumn($elements, ['sort' => SORT_ASC]);

		return [
			'name' => 'main',
			'title' => Loc::getMessage('LISTSMOBILE_LIB_ENTITY_EDITOR_PROVIDER_MAIN_SECTION_TITLE_1'),
			'type' => 'section',
			'elements' => $elements,
			'data' => [
				'showBorder' => $this->useSectionBorder,
			],
		];
	}

	private function getSectionSection(): array
	{
		return [
			'name' => 'section',
			'title' => Loc::getMessage('LISTSMOBILE_LIB_ENTITY_EDITOR_PROVIDER_SECTION_SECTION_TITLE'),
			'type' => 'section',
			'elements' => [['name' => 'IBLOCK_SECTION_ID']],
			'data' => [
				'showBorder' => $this->useSectionBorder,
			],
		];
	}

	public function getEntityData(): array
	{
		$data = [];
		foreach ($this->entityFields as $key => $preparedProperty)
		{
			if (array_key_exists($key, $this->entity))
			{
				$data[$key] = $preparedProperty['custom']['value'] ?? $this->entity[$key];
				continue;
			}

			$data[$key] = $preparedProperty['custom']['default'];
		}

		$data['ID'] = $this->entity['ID'];
		$data['IBLOCK_ID'] = $this->entity['IBLOCK_ID'];

		if ($this->wfValues)
		{
			$data = array_merge($data, $this->wfValues);
		}

		return $data;
	}

	public function isReadOnly(): bool
	{
		$entityId = $this->getEntityId();
		if ($entityId === null || $entityId === 0)
		{
			return false; // create
		}

		$currentUserId = (int)(CurrentUser::get()->getId());
		$iBlockId = $this->entity['IBLOCK_ID'] ?? 0;

		$accessService = new AccessService(
			$currentUserId,
			new Param([
				'IBLOCK_TYPE_ID' => $this->entity['IBLOCK_TYPE_ID'] ?? '',
				'IBLOCK_ID' => $iBlockId,
				'SOCNET_GROUP_ID' => $this->entity['SOCNET_GROUP_ID'] ?? 0,
			])
		);

		$sectionId = $this->entity['IBLOCK_SECTION_ID'] ?? 0;
		$response = $accessService->checkElementPermission($entityId, $sectionId, ElementRight::EDIT, $iBlockId);

		$canEdit = $response->isSuccess();

		return !$canEdit;
	}
}
