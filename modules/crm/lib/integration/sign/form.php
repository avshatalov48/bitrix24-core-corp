<?php
namespace Bitrix\Crm\Integration\Sign;

use Bitrix\Main;
use Bitrix\Crm;

class Form
{
	private string $type;

	private Crm\WebForm\Form $form;

	private Crm\WebForm\Options $options;

	public static function getFieldSet(int $entityTypeId, ?int $presetId = null): ?Crm\FieldSet\Item
	{
		$factory = new Crm\FieldSet\Factory;
		$code = 'def-req-' . $entityTypeId . ($presetId ? '-' . $presetId : '');
		$item = $factory->getItemByCode($code);
		if ($item)
		{
			return $item;
		}

		$factory->installDefaults($presetId);
		return $factory->getItemByCode($code);
	}

	public static function restoreDefaultFieldSet(int $entityTypeId, ?int $presetId = null): void
	{
		$factory = new Crm\FieldSet\Factory;
		$code = 'def-req-' . $entityTypeId . ($presetId ? '-' . $presetId : '');
		$item = $factory->getItemByCode($code);

		if ($item && empty($item->getFields()))
		{
			$factory->deleteItem($item);
		}

		$factory->installDefaults($presetId);
	}

	public static function getFieldSetValues(
		int $entityTypeId,
		int $entityId,
		array $options = [],
		?int $requisitePresetId = null
	): array
	{
		$result = [];

		$set = self::getFieldSet($entityTypeId, $requisitePresetId);
		if (!$set)
		{
			return $result;
		}

		$values = Crm\WebForm\Requisite::instance()
			->load($entityTypeId, $entityId, $requisitePresetId)
			->getData()
		;
		if (!$values)
		{
			return $result;
		}

		$entityTypeName = \CCrmOwnerType::resolveName($entityTypeId);
		foreach ($values as $key => $value)
		{
			unset($values[$key]);
			$values["{$entityTypeName}_{$key}"] = $value;
		}

		$entityKeys = [];
		$prefix = "{$entityTypeName}_";
		$prefixRq = "{$entityTypeName}_RQ_";

		foreach ($set->getFields() as $field)
		{
			$name = $field['name'];
			if (mb_strpos($name, $prefix) === 0 && mb_strpos($name, $prefixRq) !== 0)
			{
				$entityKeys[] = mb_substr($name, mb_strlen($prefix));
				continue;
			}

			$value = $values[$name] ?? '';
			if ($value === null || $value === false)
			{
				continue;
			}

			$result[$name] = $value;
		}
		
		foreach ($entityKeys as $key)
		{
			$name = "{$entityTypeName}_$key";
			$value = $values[$name] ?? '';
			if ($value === false || !empty($result[$name]))
			{
				continue;
			}
			
			$result[$name] = $value ?? '';
		}
		
		if (!empty($options['appendExtended']))
		{
			$title = '';
			if (!empty($values["{$prefix}RQ_NAME"]) || !empty($values["{$prefix}RQ_LAST_NAME"]))
			{
				$title = trim(str_replace(
					['#NAME#', '#LAST_NAME#'],
					[$values["{$prefix}RQ_NAME"], $values["{$prefix}RQ_LAST_NAME"]],
					Main\Context::getCurrent()->getCulture()->getFormatName()
				));
			}
			elseif (!empty($values["{$prefix}RQ_COMPANY_NAME"]))
			{
				$title = $values["{$prefix}RQ_COMPANY_NAME"];
			}
			$result['extended'] = [
				'presetId' => $values[$prefix . 'presetId'] ?? 0,
				'requisiteId' => $values[$prefix . 'requisiteId'] ?? 0,
				'title' => $title,
			];
		}

		if (!$entityKeys)
		{
			return $result;
		}

		$entityFactory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if (!$entityFactory)
		{
			return $result;
		}

		$item = $entityFactory->getItem($entityId);
		if (!$item)
		{
			return $result;
		}

		$values = $item->getData();
		foreach ($item->getFm()->getAll() as $fmItem)
		{
			$type = $fmItem->getTypeId();
			$value = $fmItem->getValue();
			if ($value && empty($values[$type]))
			{
				$values[$type] = $value;
			}
		}

		foreach ($entityKeys as $key)
		{
			$name = "{$entityTypeName}_$key";
			if (!empty($result[$name]))
			{
				continue;
			}
			
			$value = isset($values[$key]) && !empty($values[$key])
				? $entityFactory->getFieldValueCaption($key, $values[$key])
				: null;
			$value ??= '';
			$result[$name] = $value ?? '';
		}

		return $result;
	}

	public static function create(string $type = 'sign'): self
	{
		return new self($type);
	}

	private function __construct(string $type)
	{
		$this->type = $type;
		$this->form = new Crm\WebForm\Form();
		$this->options = new Crm\WebForm\Options($this->form);
	}

	public function load(int $id): self
	{
		if (!$this->form->load($id))
		{
			$this->form->setId(null);
		}

		return $this;
	}

	public function loadByXmlId(string $xmlId): self
	{
		$id = Crm\WebForm\Internals\FormTable::query()
			->setSelect(['ID'])
			->where('XML_ID', $xmlId)
			->setLimit(1)
			->fetch()['ID'] ?? null
		;
		if (!$id)
		{
			return $this;
		}

		return $this->load($id);
	}

	public function isExists(): bool
	{
		return !!$this->form->getId();
	}

	public function save(): Main\Result
	{
		$this->form->merge([
			'ACTIVE' => 'Y',
			'TYPE_ID' => Crm\WebForm\Internals\FormTable::TYPE_SMART_DOCUMENT,
		]);
		return $this->options->save();
	}

	public function appendField(array $options): self
	{
		$this->options->getConfig()->appendField($options);
		return $this;
	}

	public function setFields(array $list): self
	{
		$this->options->getConfig()->clearFields();
		foreach ($list as $options)
		{
			$this->options->getConfig()->appendField($options);
		}

		return $this;
	}

	public function appendFieldsFromFieldSet(int $entityTypeId, ?int $requisitePresetId = null): self
	{
		$item = self::getFieldSet($entityTypeId, $requisitePresetId);
		if ($item)
		{
			foreach ($item->getFields() as $field)
			{
				if (mb_strpos($field['name'], '_RQ_ADDR_') > 0)
				{
					$label = $field['label'] ?? '';
					if ($label === '')
					{
						$label = Crm\WebForm\Options\Fields::getFieldByName($field['name'])['ENTITY_FIELD_CAPTION'] ?? null;
					}
					$this->appendField(['type' => 'page', 'label' => $label]);
				}

				$this->appendField($field);
			}

			if ($requisitePresetId !== null && $requisitePresetId > 0)
			{
				$this->setRequisitePresetId($requisitePresetId);
			}
		}

		return $this;
	}

	public function setXmlId(string $xmlId): self
	{
		$this->form->merge(['XML_ID' => $xmlId]);
		return $this;
	}

	public function setName(string $name): self
	{
		$this->form->merge(['NAME' => $name]);
		return $this;
	}

	public function setResultText(string $successText, string $failureText = null): self
	{
		$this->form->merge([
			'RESULT_SUCCESS_TEXT' => $successText,
			'RESULT_FAILURE_TEXT' => $failureText,
		]);
		return $this;
	}

	public function setRequisitePresetId(int $requisitePresetId): self
	{
		$this->form->merge([
			'FORM_SETTINGS' => [
				'REQUISITE_PRESET_ID' => $requisitePresetId
			]
		]);

		return $this;
	}

	public function getRequisitePresetId(): ?int
	{
		$formData = $this->form->get();
		return
			$formData['FORM_SETTINGS']['REQUEST_PRESET_ID']
				? (int)$formData['FORM_SETTINGS']['REQUEST_PRESET_ID']
				: null
		;
	}

	public function getRequisitePresetIdByFieldSet(int $entityTypeId): ?int
	{
		$fieldSet = static::getFieldSet($entityTypeId);
		if (!$fieldSet)
		{
			return null;
		}
		return $fieldSet->getRequisitePresetId();
	}

	public function setRefillButtonCaption(string $text): self
	{
		$this->form->merge([
			'FORM_SETTINGS' => [
				'REFILL' => [
					'ACTIVE' => $text ? 'Y' : 'N',
					'CAPTION' => $text,
				],
			],
		]);
		return $this;
	}

	public function setButtonCaption(string $text): self
	{
		$this->form->merge(['BUTTON_CAPTION' => $text]);
		return $this;
	}

	public function setDynamicTypeId(int $typeId): self
	{
		$schemeId = null;
		foreach (Crm\WebForm\Entity::getSchemes() as $innerSchemeId => $scheme)
		{
			if (!$scheme['DYNAMIC'] || $scheme['MAIN_ENTITY'] !== $typeId)
			{
				continue;
			}

			$schemeId = $innerSchemeId;
			break;
		}

		$this->form->merge([
			'ENTITY_SCHEME' => $schemeId,
			'DUPLICATE_MODE' => 'REPLACE',
			'FORM_SETTINGS' => [
				'DYNAMIC_CATEGORY' => null,
				'DYNAMIC_DC_ENABLED' => 'Y',
			],
		]);

		return $this;
	}

	private function getDynamicTypeId(): ?int
	{
		$schemeId = $this->form->get()['ENTITY_SCHEME'];
		$scheme = Crm\WebForm\Entity::getSchemes($schemeId);
		if (!$scheme || !$scheme['DYNAMIC'])
		{
			return \CCrmOwnerType::Undefined;
		}

		return (int)$scheme['MAIN_ENTITY'];
	}

	public function getPersonalizationData(int $entityId, int $contactId = null, int $companyId = null): string
	{
		$sign = (new Crm\WebForm\Embed\Sign())
			->addEntity(-1, $this->form->getId())
			->addEntity($this->getDynamicTypeId(), $entityId)
			->setProperty('eventNamePostfix', $this->getEventProperty())
		;
		if ($contactId)
		{
			$sign->addEntity(\CCrmOwnerType::Contact, $contactId);
		}
		if ($companyId)
		{
			$sign->addEntity(\CCrmOwnerType::Company, $companyId);
		}

		return $sign->pack();
	}

	private function getEventProperty(): string
	{
		return ucfirst($this->type);
	}

	public function getFillEventName(): string
	{
		return 'onSiteFormFill' . $this->getEventProperty();
	}

	public function getLoaderScript(string $formInstanceId = ''): string
	{
		return Crm\UI\Webpack\Form::instance($this->form->getId())
			->setTagAttribute('data-b24-id', $formInstanceId)
			->getEmbeddedScript()
		;
	}

	public function getId(): ?int
	{
		return $this->form->getId();
	}

	public function delete(): Main\Result
	{
		$result = new Main\Result();
		if (!$this->isExists())
		{
			return $result;
		}

		$this->form::delete($this->form->getId());
		return $result;
	}
}