<?php

namespace Bitrix\DocumentGenerator\CustomField;

use Bitrix\DocumentGenerator\Document;

abstract class Manager
{
	protected array $fields = [];

	/**
	 *
	 *  Custom fields initialization.
	 *
	 * @return void
	 */
	abstract protected function initFields(): void;

	/**
	 * Render custom field.
	 *
	 * @param array $field Field config
	 *
	 * @return string
	 */
	abstract public function renderField(array $field): string;

	/**
	 * Get custom fields for document.
	 *
	 * @param Document $document Document instance
	 *
	 * @return array
	 */
	abstract public function getDocumentFields(Document $document): array;

	public function __construct(
		protected readonly ?int $templateId = null
	) {
		$this->initFields();
	}

	/**
	 * Get all custom fields list.
	 *
	 * @return array
	 */
	final public function getFields(): array
	{
		return $this->fields;
	}

	/**
	 * Get custom field by unique ID.
	 *
	 * @param string $fieldId
	 *
	 * @return array|null
	 */
	final public function getField(string $fieldId): ?array
	{
		return $this->fields[$fieldId] ?? null;
	}

	/**
	 * Get custom fields javaScript code. Empty by default.
	 *
	 * @return string
	 */
	public function getJavaScript(): string
	{
		return '';
	}
}
