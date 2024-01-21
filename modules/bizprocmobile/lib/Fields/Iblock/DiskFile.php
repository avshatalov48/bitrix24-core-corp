<?php

namespace Bitrix\BizprocMobile\Fields\Iblock;

use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Loader;

Loader::requireModule('iblock');

class DiskFile extends \Bitrix\Iblock\BizprocType\UserTypePropertyDiskFile
{
	public static function convertPropertyToView(FieldType $fieldType, int $viewMode, array $property): array
	{
		if ($viewMode === FieldType::RENDER_MODE_JN_MOBILE)
		{
			$property['Type'] = FieldType::FILE;
		}

		return parent::convertPropertyToView($fieldType, $viewMode, $property);
	}

	public static function internalizeValueMultiple(FieldType $fieldType, $context, $value)
	{
		if (
			defined('Bitrix\Bizproc\FieldType::VALUE_CONTEXT_JN_MOBILE')
			&& $context === FieldType::VALUE_CONTEXT_JN_MOBILE
		)
		{
			return static::internalizeValue($fieldType, $context, $value);
		}

		return parent::internalizeValueMultiple($fieldType, $context, $value);
	}

	public static function internalizeValue(FieldType $fieldType, $context, $value)
	{
		if (
			defined('Bitrix\Bizproc\FieldType::VALUE_CONTEXT_JN_MOBILE')
			&& $context === FieldType::VALUE_CONTEXT_JN_MOBILE
		)
		{
			return static::extractValueMobile(
				$fieldType,
				['Field' => 'file'],
				['file' => $value],
			);
		}

		return parent::internalizeValue($fieldType, $context, $value);
	}

	private static function extractValueMobile(FieldType $fieldType, array $field, array $request)
	{
		$renderer = self::getMobileControlRenderer();
		if (!$renderer)
		{
			return null;
		}

		$diskFileIds = call_user_func(
			[$renderer, 'extractValues'],
			static::generateControlName($field),
			$request
		);

		$property = static::getUserType($fieldType);
		$iblockId = self::getIblockId($fieldType);

		if (array_key_exists('AttachFilesWorkflow', $property))
		{
			foreach ($diskFileIds as $i => $diskFileId)
			{
				$diskFileIds[$i] = call_user_func_array(
					$property['AttachFilesWorkflow'], [$iblockId, $diskFileId]
				);
			}
		}

		if (!$fieldType->isMultiple())
		{
			return $diskFileIds ? end($diskFileIds) : null;
		}

		return $diskFileIds;
	}

	private static function getIblockId(FieldType $fieldType)
	{
		$documentType = $fieldType->getDocumentType();
		$type = explode('_', $documentType[2]);
		return intval($type[1]);
	}
}
