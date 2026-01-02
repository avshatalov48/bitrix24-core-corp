<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField;

use Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Extension\PaymentQrCodePurpose;
use Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\FieldInterface;
use Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Renderer\RendererFactory;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\DocumentGenerator\CustomField\Manager;
use Bitrix\DocumentGenerator\Document;
use Bitrix\Main\Web\Json;

final class FieldManager extends Manager
{
	private readonly DependencyManager $dependencyManager;

	protected function initFields(): void
	{
		$this->dependencyManager = new DependencyManager();

		$this->addField(
			PaymentQrCodePurpose::getField((new FieldController($this->templateId))->load())
		);
	}

	public function renderField(array $field): string
	{
		return RendererFactory::create($field)->render();
	}

	public function getDocumentFields(Document $document): array
	{
		// @todo: currently we support only one field PaymentQrCodePurpose::FIELD_UID
		$field = $this->getField(PaymentQrCodePurpose::FIELD_UID);
		$identifier = $this->getItemIdentifierByDocument($document);
		if (isset($field, $identifier))
		{
			$value = PaymentQrCodePurpose::getValue($this->templateId, $identifier);
			$field['VALUE'] = $value ?? '';

			return [
				$field,
			];
		}

		return [];
	}

	public function getJavaScript(): string
	{
		$jsCode = $this->generateCommonScript(array_column($this->fields, 'UID'));

		foreach ($this->fields as $field)
		{
			if (!empty($field['JAVASCRIPT']))
			{
				$jsCode .= $field['JAVASCRIPT'] . "\n";
			}
		}

		$jsCode .= $this->dependencyManager->generateJavaScript();

		return $jsCode;
	}

	private function generateCommonScript(array $fieldIds): string
	{
		$fieldIds = Json::encode($fieldIds);

		return <<<JS
			BX.ready(function() 
			{
				BX.DocumentGenerator.UploadTemplate.customFieldExtentions = {}; // empty object by default

				BX.DocumentGenerator.UploadTemplate.getCustomValues = function()
				{
					const fieldIds = JSON.parse('$fieldIds');
					if (!BX.Type.isArrayFilled(fieldIds))
					{
						console.warn('Custom fields not found');

						return [];
					}

					const container = BX('add-template-custom-fields-block');
					if (!BX.Type.isDomNode(container))
					{
						console.warn('Custom fields DOM container not found');
						
						return [];
					}

					let values = [];

					fieldIds.forEach(function (fieldId) 
					{
						let fieldNode = container.querySelector('[name="' + fieldId + '"]');
						if (BX.Type.isDomNode(fieldNode))
						{
							if (fieldNode.type === 'radio')
							{
								fieldNode = container.querySelector('[name="' + fieldId + '"]:checked');
							}
							
							const isEmptyValue = BX.Type.isNull(fieldNode.value)
								|| BX.Type.isUndefined(fieldNode.value)
								|| !BX.Type.isStringFilled(fieldNode.value)
							;

							if (fieldNode.hasAttribute('required') && isEmptyValue) 
							{
								// TODO: Users are not currently seeing this message, need translate
								alert('The input element has the required attribute');

								throw 'The input element has the required attribute';
							}

							if (!BX.Type.isUndefined(fieldNode.value))
							{
								values[fieldId] = fieldNode.value;
							}
						}
					});

					if (
						BX.Type.isPlainObject(this.customFieldExtentions) 
						&& Object.keys(this.customFieldExtentions).length > 0
					)
					{
						for (const [fieldId, fieldExtention] of Object.entries(this.customFieldExtentions))
						{
							if (BX.Type.isFunction(fieldExtention.getValue))
							{
								values[fieldId] = fieldExtention.getValue();
							}
						}
					}

					return values;
				}
			});
JS;
	}

	private function addField(FieldInterface $field): void
	{
		$type = $field->getType();
		if (!RendererFactory::supports($type))
		{
			throw new \InvalidArgumentException("Unsupported field type: $type");
		}

		$uid = $field->getUid();
		if (array_key_exists($uid, $this->fields))
		{
			throw new \InvalidArgumentException("Field with UID '$uid' already exists");
		}

		$this->fields[$uid] = $field->toArray();

		foreach ($field->getDependencies() as $dependency)
		{
			$this->dependencyManager->addDependency($uid, $dependency);
		}
	}

	private function getItemIdentifierByDocument(Document $document): ?ItemIdentifier
	{
		$provider = $document->PROVIDER ?? '';
		$map = array_map(
			'mb_strtolower',
			DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvidersMap()
		);
		$entityTypeId = (int)array_search($provider, $map, true);
		$entityId = (int)($document->VALUE ?? 0);
		try {
			$identifier = new ItemIdentifier($entityTypeId, $entityId);
		}
		catch (\Throwable $e)
		{
			$identifier = null;
		}

		return $identifier;
	}
}
