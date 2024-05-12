<?php

namespace Bitrix\BizprocMobile\EntityEditor;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::requireModule('ui');

class ParametersProvider extends \Bitrix\UI\EntityEditor\BaseProvider
{
	private array $fields = [];
	private array $data = [];

	private bool $isReadOnly = false;
	private bool $useSectionBorder = false;

	public function __construct(array $parameters, int $templateId, string $signedDocument)
	{
		foreach ($parameters as $name => $property)
		{
			$id = $property['Id'] ?? $name;

			$this->fields[$id] = array_merge(
				$property,
				[
					'showAlways' => true,
					'showNew' => true,
				]
			);
			$this->data[$id] = $property['custom']['default'] ?? null;
		}

		$this->data['TEMPLATE_ID_' . $templateId] = $templateId;
		$this->data['SIGNED_DOCUMENT'] = $signedDocument;
	}

	public function setIsReadOnly(bool $readonly): void
	{
		$this->isReadOnly = $readonly;
	}

	public function setUseSectionBorder(bool $useSectionBorder): void
	{
		$this->useSectionBorder = $useSectionBorder;
	}

	public function getGUID(): string
	{
		return 'BIZPROC_TEMPLATE_PARAMETERS';
	}

	public function getEntityId(): ?int
	{
		return null;
	}

	public function getEntityTypeName(): string
	{
		return 'bizproc_parameters';
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
				'elements' => [
					[
						'name' => 'main',
						'title' => Loc::getMessage('M_BP_LIB_ENTITY_EDITOR_PARAMETERS_PROVIDER_MAIN_SECTION_TITLE_1'),
						'type' => 'section',
						'elements' => $this->getEntityFields(),
						'data' => [
							'showBorder' => $this->useSectionBorder,
						],
					]
				],
			],
		];
	}

	public function getEntityData(): array
	{
		return $this->data;
	}

	public function isReadOnly(): bool
	{
		return $this->isReadOnly;
	}
}
