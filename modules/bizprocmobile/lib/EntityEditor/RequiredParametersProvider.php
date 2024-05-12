<?php

namespace Bitrix\BizprocMobile\EntityEditor;

use Bitrix\Main\Loader;

Loader::requireModule('ui');

class RequiredParametersProvider extends \Bitrix\UI\EntityEditor\BaseProvider
{
	private array $fields = [];
	private array $sections = [];
	private array $data = [];
	private ?array $complexDocumentType;
	private ?array $complexDocumentId;

	public function __construct(string $signedDocument, int $autoExecuteType)
	{
		$unsignedDocument = \CBPDocument::unSignParameters($signedDocument);
		[$complexDocumentType, $complexDocumentId] = $this->getComplexDocumentData($unsignedDocument);
		$this->complexDocumentType = $complexDocumentType;
		$this->complexDocumentId = $complexDocumentId;

		if (!in_array($autoExecuteType, [\CBPDocumentEventType::Edit, \CBPDocumentEventType::Create], true))
		{
			$autoExecuteType = 0;
		}

		if ($this->complexDocumentType && $autoExecuteType)
		{
			$documentStates = \CBPWorkflowTemplateLoader::getDocumentTypeStates($this->complexDocumentType, $autoExecuteType);
			foreach ($documentStates as $state)
			{
				$this->addSection($state);
			}

			$this->data['SIGNED_DOCUMENT'] = $signedDocument;
		}
	}

	private function getComplexDocumentData(array $unsignedDocument): array
	{
		$complexDocumentType = null;
		$complexDocumentId = null;

		if ($unsignedDocument)
		{
			try
			{
				$complexDocumentType = \CBPHelper::parseDocumentId($unsignedDocument[0]);
			}
			catch (\CBPArgumentNullException $exception)
			{}

			if ($complexDocumentType && isset($unsignedDocument[1]))
			{
				$complexDocumentId = [$complexDocumentType[0], $complexDocumentType[1], $unsignedDocument[1]];
				try
				{
					$complexDocumentId = \CBPHelper::parseDocumentId($complexDocumentId);
				}
				catch (\CBPArgumentNullException $exception)
				{
					$complexDocumentId = null;
				}
			}
		}

		return [$complexDocumentType, $complexDocumentId];
	}

	private function addSection(array $state): void
	{
		$templateId = (int)$state['TEMPLATE_ID'];
		$parameters = $state['TEMPLATE_PARAMETERS'] ?? [];

		if (!empty($parameters) && is_array($parameters))
		{
			$this->data['TEMPLATE_ID_' . $templateId] = $templateId;

			$sectionName = 'template_' . $templateId;

			$elements = [];
			foreach ($this->convertToMobileProperties($parameters, $templateId) as $key => $property)
			{
				$elementName = $sectionName . '_' . $key;
				$elements[] = ['name' => $elementName];

				$property['name'] = $elementName;
				$property['showAlways'] = true;
				$property['showNew'] = true;

				$this->fields[$elementName] = $property;
				$this->data[$elementName] = $property['custom']['default'] ?? null;
			}

			$this->sections[] = [
				'name' => $sectionName,
				'title' => $state['TEMPLATE_NAME'],
				'type' => 'section',
				'elements' => $elements,
				'data' => ['showBorder' => true],
			];
		}
	}

	public function convertToMobileProperties(array $parameters, int $templateId): array
	{
		$converter =
			(new \Bitrix\BizprocMobile\EntityEditor\Converter($parameters, $this->complexDocumentId))
				->setDocumentType($this->complexDocumentType)
				->setContext(Converter::CONTEXT_PARAMETERS, ['templateId' => $templateId])
		;

		return $converter->toMobile()->getConvertedProperties();
	}

	public function getGUID(): string
	{
		return 'BIZPROC_REQUIRED_PARAMETERS';
	}

	public function getEntityId(): ?int
	{
		return null;
	}

	public function getEntityTypeName(): string
	{
		return 'bizproc_required_parameters';
	}

	public function getEntityFields(): array
	{
		return $this->fields;
	}

	public function getEntityConfig(): array
	{
		return [
			[
				'name' => 'default_column',
				'type' => 'column',
				'elements' => $this->sections,
			]
		];
	}

	public function getEntityData(): array
	{
		return $this->data;
	}
}
