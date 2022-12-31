<?php

declare(strict_types = 1);

namespace Bitrix\Mobile\UI\EntityEditor;

use Bitrix\UI\EntityEditor\BaseProvider;

final class FormWrapper
{
	private const DEFAULT_FORM_COMPONENT = 'bitrix:ui.form';

	private $provider;
	private $formComponentName;

	public function __construct(BaseProvider $provider = null, string $formComponentName = null)
	{
		$this->provider = $provider;
		$this->formComponentName = $formComponentName;
	}

	public function getResult(): array
	{
		$fields = $this->provider->getFields();

		return $this->executeFormComponent($fields);
	}

	public function getRequiredFields(array $fieldCodes = null): array
	{
		if (empty($fieldCodes) && is_array($fieldCodes))
		{
			return [];
		}

		$requiredFieldsToFill = [];

		$entityData = $this->provider->getEntityData();
		$entityFields = $this->provider->getEntityFields();

		foreach ($entityFields as &$field)
		{
			if ($fieldCodes === null)
			{
				$isRequiredField = $field['required'] && empty($entityData[$field['name']]);
			}
			else
			{
				$isRequiredField = in_array($field['name'], $fieldCodes, true);
			}

			if ($isRequiredField)
			{
				$field['required'] = true;
				$requiredFieldsToFill[] = ['name' => $field['name']];
			}
			else
			{
				// to remove additional section for required fields without sections
				$field['required'] = false;
			}
		}

		unset($field);

		if (empty($requiredFieldsToFill))
		{
			return [];
		}

		$formParams = $this->provider->getFields();
		$formParams['GUID'] .= '_required_fields';
		$formParams['ENABLE_COMMON_CONFIGURATION_UPDATE'] = false;
		$formParams['ENABLE_CONFIGURATION_UPDATE'] = false;
		$formParams['ENTITY_FIELDS'] = $entityFields;
		$formParams['FORCE_DEFAULT_CONFIG'] = true;
		$formParams['INITIAL_MODE'] = 'edit';
		$formParams['ENABLE_MODE_TOGGLE'] = false;
		$formParams['ENTITY_CONFIG'] = [
			[
				'name' => 'main',
				'type' => 'section',
				'elements' => $requiredFieldsToFill,
				'data' => [
					'showButtonPanel' => false,
					'isChangeable' => false,
					'isRemovable' => false,
				],
			],
		];

		return $this->executeFormComponent($formParams);
	}

	private function executeFormComponent(array $formParams): array
	{
		return
			$this
				->getFormComponent($formParams)
				->executeComponent()
		;
	}

	private function getFormComponent(array $formParams): \UIFormComponent
	{
		$componentName = $this->formComponentName ?? self::DEFAULT_FORM_COMPONENT;
		$componentClass = \CBitrixComponent::includeComponentClass($componentName);

		/** @var \UIFormComponent $formComponent */
		$formComponent = new $componentClass();
		$formComponent->initComponent($componentName);

		$formComponent->arParams = $formComponent->onPrepareComponentParams(array_merge(
			$formComponent->arParams,
			$formParams,
			['SKIP_TEMPLATE' => true]
		));
		$formComponent->__prepareComponentParams($formComponent->arParams);

		return $formComponent;
	}
}
