<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Crm\Entity\Identificator;
use Bitrix\Crm\Tracking;

/*
 * Fill class.
 */
class Fill
{
	/** @var Form $form Form. */
	protected $form;
	/** @var array $values Values. */
	protected $values;
	/** @var int|string $trace Trace. */
	protected $trace;
	/** @var array $properties Properties. */
	protected $properties;
	/** @var Identificator\ComplexCollection $entities Entities. */
	protected $entities;
	/** @var bool $isFieldCheckingEnabled Is field checking enabled. */
	protected $isFieldCheckingEnabled = true;

	/*
	 * Constructor.
	 */
	public function __construct(Form $form)
	{
		$this->form = $form;
	}

	/*
	 * Set values.
	 *
	 * @param array $values Values.
	 * @return $this
	 */
	public function setValues(array $values)
	{
		$this->values = $values;
		return $this;
	}

	/*
	 * Set field checking.
	 *
	 * @param bool $mode Mode.
	 * @return $this
	 */
	public function setFieldChecking(bool $mode)
	{
		$this->isFieldCheckingEnabled = $mode;
		return $this;
	}

	/*
	 * Set values.
	 *
	 * @param array $properties Properties.
	 * @return $this
	 */
	public function setProperties(array $properties)
	{
		$this->properties = $properties;
		return $this;
	}

	/*
	 * Set entities.
	 *
	 * @param Identificator\ComplexCollection $entities Entities.
	 * @return $this
	 */
	public function setEntities(Identificator\ComplexCollection $entities)
	{
		$this->entities = $entities;
		return $this;
	}

	/*
	 * Set values.
	 *
	 * @param Tracking\Trace|int|string $values Values.
	 * @return $this
	 */
	public function setTrace($trace)
	{
		$this->trace = $trace;
		return $this;
	}

	/*
	 * Save form fill.
	 *
	 * @return Result
	 */
	public function save()
	{
		return $this->form->addResult(
			$this->getFilledFields(),
			[
				'ENTITIES' => $this->entities ? $this->entities->toSimpleArray(['typeId', 'id']) : [],
				'DISABLE_FIELD_CHECKING' => !$this->isFieldCheckingEnabled,
				'COMMON_FIELDS' => [],
				'PLACEHOLDERS' => $this->properties,
				'STOP_CALLBACK' => false,
				'COMMON_DATA' => [
					'VISITED_PAGES' => [],
					'TRACE' => $this->trace
				],
			]
		);
	}

	private function getFilledFields()
	{
		$fields = $this->form->getFieldsMap();
		foreach($fields as $fieldKey => $field)
		{
			$fieldName = $field['name'];
			$fieldValues = isset($this->values[$fieldName]) ? $this->values[$fieldName] : [];
			if(!is_array($fieldValues))
			{
				$fieldValues = [$fieldValues];
			}

			if($field['type'] == 'file')
			{
				$files = [];
				foreach ($fieldValues as $fileData)
				{
					if (empty($fileData['content']))
					{
						continue;
					}

					$files[] = \CRestUtil::saveFile($fileData['content'], $fileData['name']);
				}
				$fieldValues = $files;
			}
			elseif($field['type'] == 'phone')
			{
				$fieldValues = array_map(
					function ($value)
					{
						return preg_replace("/[^0-9+]/", '', $value);
					},
					$fieldValues
				);
			}
			else if ($field['entity_field_name'] == 'COMMENTS')
			{
				$fieldValues = array_map(
					function ($value)
					{
						return nl2br(htmlspecialcharsbx($value));
					},
					$fieldValues
				);
			}

			$field['values'] = $fieldValues;
			$fields[$fieldKey] = $field;
		}

		return $fields;
	}
}
