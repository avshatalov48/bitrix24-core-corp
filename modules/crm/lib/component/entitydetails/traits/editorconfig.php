<?php

namespace Bitrix\Crm\Component\EntityDetails\Traits;

use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Integration\Analytics\Builder\Entity\CloseEvent;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;

trait EditorConfig
{
	use EditorInitialMode;
	use EditorRequisiteEditMode;

	abstract protected function getEntityId();

	abstract protected function getCategoryId();

	public function getEditorConfig(): array
	{
		$entityTypeId = $this->factory->getEntityTypeId();
		$editorGuid = "{$this->arResult['GUID']}_editor";
		$componentNameWithoutBitrixPrefix = mb_substr($this->getDetailComponentName(), 7);
		$sessionId = bitrix_sessid_get();

		/** @var \Bitrix\Crm\Integration\Analytics\Builder\BuilderContract $analyticsBuilder */
		if ($this->isCopyMode)
		{
			$analyticsBuilder = \Bitrix\Crm\Integration\Analytics\Builder\Entity\CopyEvent::createDefault($this->factory->getEntityTypeId());
		}
		elseif ($this->isEditMode)
		{
			$analyticsBuilder = \Bitrix\Crm\Integration\Analytics\Builder\Entity\UpdateEvent::createDefault($this->factory->getEntityTypeId());
		}
		elseif (isset($this->conversionWizard))
		{
			$analyticsBuilder =
				\Bitrix\Crm\Integration\Analytics\Builder\Entity\ConvertEvent::createDefault($this->factory->getEntityTypeId())
					->setSrcEntityTypeId($this->conversionWizard->getEntityTypeID())
			;
		}
		else
		{
			$analyticsBuilder = \Bitrix\Crm\Integration\Analytics\Builder\Entity\AddEvent::createDefault($this->factory->getEntityTypeId());
		}
		//@codingStandardsIgnoreStart
		return [
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $this->isCopyMode ? 0 :$this->getEntityId(),
			'IS_COPY_MODE' => $this->isCopyMode,
			'READ_ONLY' => $this->arResult['READ_ONLY'],
			'EXTRAS' => $this->getExtras(),
			'INITIAL_MODE' => $this->getInitialMode($this->isCopyMode),
			'DETAIL_MANAGER_ID' => $editorGuid,
			'MODULE_ID' => 'crm',
			'SERVICE_URL' => "/bitrix/components/bitrix/{$componentNameWithoutBitrixPrefix}/ajax.php?{$sessionId}",
			'GUID' => $editorGuid,
			'CONFIG_ID' => $this->arResult['EDITOR_CONFIG_ID'],
			'ENTITY_CONFIG' => $this->arResult['ENTITY_CONFIG'],
			'DUPLICATE_CONTROL' => $this->arResult['DUPLICATE_CONTROL'] ?? [],
			'ENTITY_CONTROLLERS' => $this->arResult['ENTITY_CONTROLLERS'],
			'ENTITY_FIELDS' => $this->arResult['ENTITY_FIELDS'],
			'ENTITY_DATA' => $this->arResult['ENTITY_DATA'],
			'ENTITY_VALIDATORS' => $this->arResult['ENTITY_VALIDATORS'],
			'ENABLE_SECTION_EDIT' => true,
			'ENABLE_SECTION_CREATION' => true,
			'ENABLE_USER_FIELD_CREATION' => $this->arResult['ENABLE_USER_FIELD_CREATION'],
			'USER_FIELD_ENTITY_ID' => $this->arResult['USER_FIELD_ENTITY_ID'],
			'USER_FIELD_CREATE_PAGE_URL' => $this->arResult['USER_FIELD_CREATE_PAGE_URL'],
			'USER_FIELD_CREATE_SIGNATURE' => $this->arResult['USER_FIELD_CREATE_SIGNATURE'],
			'USER_FIELD_FILE_URL_TEMPLATE' => $this->arResult['USER_FIELD_FILE_URL_TEMPLATE'],
			'EXTERNAL_CONTEXT_ID' => $this->arResult['EXTERNAL_CONTEXT_ID'],
			'CONTEXT_ID' => $this->arResult['CONTEXT_ID'],
			'CONTEXT' => $this->arResult['CONTEXT'],
			'ATTRIBUTE_CONFIG' => [
				'ENTITY_SCOPE' => $this->arResult['ENTITY_ATTRIBUTE_SCOPE'],
				'CAPTIONS' => FieldAttributeManager::getCaptionsForEntityWithStages($entityTypeId),
			],
			'COMPONENT_AJAX_DATA' => [
				'RELOAD_ACTION_NAME' => 'LOAD',
				'RELOAD_FORM_DATA' => [
						'ACTION_ENTITY_ID' => $this->arResult['ENTITY_ID'],
					] + $this->arResult['CONTEXT'],
			],
			'SHOW_EMPTY_FIELDS' => !empty($this->arParams['SHOW_EMPTY_FIELDS']),
			'ENABLE_PAGE_TITLE_CONTROLS' => $this->arResult['IS_EDIT_MODE'],
			// this data is used to send analytics when user clicks 'save' button
			'ANALYTICS_CONFIG' => [
				'data' => $analyticsBuilder->buildData(),

				'appendParamsFromCurrentUrl' => true,
				'entityClose' => $this->getCloseEventConfig()?->buildData(),
				'finalStagesSemantics' => $this->getFinalStagesSemantics(),
			],
		];
		//@codingStandardsIgnoreEnd
	}

	private function getDetailComponentName(): ?string
	{
		return
			Container::getInstance()
				->getRouter()
				->getItemDetailComponentName($this->factory->getEntityTypeId())
		;
	}

	private function getExtras(): array
	{
		if ($this->factory->isCategoriesSupported())
		{
			return [
				'CATEGORY_ID' => $this->getCategoryId(),
			];
		}

		return [];
	}

	private function getFinalStagesSemantics(): array
	{
		$stages =
			$this->factory->isCategoriesSupported()
				? $this->factory->getStages($this->getCategoryId())
				: $this->factory->getStages()
		;
		$finalStages = [];
		foreach ($stages as $stage)
		{
			if (PhaseSemantics::isFinal($stage->getSemantics()))
			{
				$finalStages[$stage->getStatusId()] = $stage->getSemantics();
			}
		}

		return $finalStages;
	}

	/**
	 * @return CloseEvent|null
	 */
	public function getCloseEventConfig(): ?CloseEvent
	{
		if (!$this->factory || !$this->factory->isStagesSupported() || !$this->factory->isStagesEnabled())
		{
			return null;
		}

		return  CloseEvent::createDefault($this->factory->getEntityTypeId());
	}
}
