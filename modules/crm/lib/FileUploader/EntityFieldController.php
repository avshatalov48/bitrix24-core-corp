<?php

namespace Bitrix\Crm\FileUploader;

use Bitrix\Crm\Field;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\UI\FileUploader\Configuration;

final class EntityFieldController extends EntityController
{
	private ?Field $field;

	/**
	 * @param array{
	 *     entityTypeId: int,
	 *     entityId: ?int,
	 *     categoryId: ?int,
	 *     fieldName: string
	 * } $options
	 * @throws ArgumentException
	 */
	public function __construct(array $options)
	{
		$options['fieldName'] ??= '';
		$options['fieldName'] = (string)$options['fieldName'];

		if ($options['fieldName'] === '')
		{
			throw new ArgumentException('Parameter "fieldName" must be defined in options.');
		}

		parent::__construct($options);

		$factory = Container::getInstance()->getFactory($options['entityTypeId']);

		$this->field = $factory->getFieldsCollection()->getField($options['fieldName']);
		if (!$this->field || $this->field->getType() !== 'file')
		{
			throw new ArgumentException("Field {{$this->field->getName()}} does not support file upload.");
		}
	}

	public function getConfiguration(): Configuration
	{
		$configuration = new Configuration();

		$settings = $this->field->getSettings();

		if ($this->field->getValueType() === Field::VALUE_TYPE_IMAGE)
		{
			$configuration->acceptOnlyImages();
		}
		elseif (!empty($settings['EXTENSIONS']))
		{
			$acceptedFileExtensions = array_map(
				static fn ($extension) => '.' . ltrim($extension, '. '),
				array_keys($settings['EXTENSIONS'])
			);
			$configuration->setAcceptedFileTypes($acceptedFileExtensions);
		}

		if (!empty($settings['MAX_ALLOWED_SIZE']))
		{
			$configuration->setMaxFileSize($settings['MAX_ALLOWED_SIZE']);
		}

		return $configuration;
	}
}
