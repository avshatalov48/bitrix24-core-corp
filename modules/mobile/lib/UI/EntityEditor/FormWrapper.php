<?php

declare(strict_types = 1);

namespace Bitrix\Mobile\UI\EntityEditor;

use Bitrix\UI\EntityEditor\BaseProvider;

final class FormWrapper
{
	private const FORM_COMPONENT_NAME = 'bitrix:ui.form';

	private $provider;

	public function __construct(BaseProvider $provider = null)
	{
		$this->provider = $provider;
	}

	public function setProvider(BaseProvider $provider): self
	{
		$this->provider = $provider;

		return $this;
	}

	public function getResult(): array
	{
		$fields = $this->provider->getFields();

		return $this->executeFormComponent($fields);
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
		$componentClass = \CBitrixComponent::includeComponentClass(self::FORM_COMPONENT_NAME);

		/** @var \UIFormComponent $formComponent */
		$formComponent = new $componentClass();
		$formComponent->initComponent(self::FORM_COMPONENT_NAME);

		$formComponent->arParams = $formComponent->onPrepareComponentParams(array_merge(
			$formComponent->arParams,
			$formParams,
			['SKIP_TEMPLATE' => true]
		));
		$formComponent->__prepareComponentParams($formComponent->arParams);

		return $formComponent;
	}
}
