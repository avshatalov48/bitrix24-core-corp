<?php

namespace Bitrix\Iblock\BizprocType;

use Bitrix\Bizproc\BaseType\Base;
use Bitrix\Bizproc\FieldType;
use Bitrix\Disk\File;
use Bitrix\Main\Loader;

if (Loader::requireModule('bizproc'))
{
	class UserTypePropertyDiskFile extends UserTypeProperty
	{
		private const MOBILE_COMPONENT_NAME = 'bitrix:mobile.iblock.bizproctype.diskfile';

		/**
		 * @return string
		 */
		public static function getType()
		{
			return FieldType::INT;
		}

		public static function formatValueMultiple(FieldType $fieldType, $value, $format = 'printable')
		{
			if (!is_array($value) || is_array($value) && \CBPHelper::isAssociativeArray($value))
				$value = array($value);

			foreach ($value as $k => $v)
			{
				$value[$k] = static::formatValuePrintable($fieldType, $v);
			}

			return implode(static::getFormatSeparator($format), $value);
		}

		public static function formatValueSingle(FieldType $fieldType, $value, $format = 'printable')
		{
			return static::formatValueMultiple($fieldType, $value, $format);
		}

		/**
		 * @param FieldType $fieldType
		 * @param $value
		 * @return string
		 */
		protected static function formatValuePrintable(FieldType $fieldType, $value)
		{
			$iblockId = self::getIblockId($fieldType);

			$property = static::getUserType($fieldType);
			if (array_key_exists('GetUrlAttachedFileWorkflow', $property))
			{
				return call_user_func_array($property['GetUrlAttachedFileWorkflow'], array($iblockId, $value));
			}
			else
			{
				return '';
			}
		}

		/**
		 * @param FieldType $fieldType Document field object.
		 * @param mixed $value Field value.
		 * @param string $toTypeClass Type class manager name.
		 * @return null|mixed
		 */
		public static function convertTo(FieldType $fieldType, $value, $toTypeClass)
		{
			if (is_array($value) && isset($value['VALUE']))
				$value = $value['VALUE'];

			$value = (int) $value;

			/** @var Base $toTypeClass */
			$type = $toTypeClass::getType();
			switch ($type)
			{
				case FieldType::FILE:
					$diskFile = File::getById($value);
					$value = $diskFile? $diskFile->getFileId() : null;
					break;
				default:
					$value = null;
			}

			return $value;
		}

		/**
		 * Return conversion map for current type.
		 * @return array Map.
		 */
		public static function getConversionMap()
		{
			return array(
				array(
					FieldType::FILE
				)
			);
		}

		public static function canRenderControl($renderMode)
		{
			if ($renderMode & FieldType::RENDER_MODE_MOBILE)
			{
				return (static::getMobileControlRenderer() !== null);
			}

			return parent::canRenderControl($renderMode);
		}

		/**
		 * @param FieldType $fieldType Document field object.
		 * @param array $field Form field information.
		 * @param mixed $value Field value.
		 * @param bool $allowSelection Allow selection flag.
		 * @param int $renderMode Control render mode.
		 * @return string
		 */
		public static function renderControlSingle(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
		{
			return static::renderControlMultiple($fieldType, $field, $value, $allowSelection, $renderMode);
		}

		/**
		 * @param FieldType $fieldType Document field object.
		 * @param array $field Form field information.
		 * @param mixed $value Field value.
		 * @param bool $allowSelection Allow selection flag.
		 * @param int $renderMode Control render mode.
		 * @return string
		 */
		public static function renderControlMultiple(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
		{
			if ($allowSelection)
			{
				$selectorValue = null;
				if(is_array($value))
				{
					$value = current($value);
				}
				if (\CBPActivity::isExpression($value))
				{
					$selectorValue = $value;
					$value = null;
				}
				return static::renderControlSelector($field, $selectorValue, true, '', $fieldType);
			}

			if ($renderMode & FieldType::RENDER_MODE_DESIGNER)
				return '';

			if ($renderMode & FieldType::RENDER_MODE_MOBILE)
			{
				return self::renderMobileControl($fieldType, $field, $value);
			}

			$userType = static::getUserType($fieldType);
			$iblockId = self::getIblockId($fieldType);

			if (!empty($userType['GetPublicEditHTML']))
			{
				if (is_array($value) && isset($value['VALUE']))
					$value = $value['VALUE'];

				$fieldName = static::generateControlName($field);
				$renderResult = call_user_func_array(
					$userType['GetPublicEditHTML'],
					array(
						array(
							'IBLOCK_ID' => $iblockId,
							'IS_REQUIRED' => $fieldType->isRequired()? 'Y' : 'N',
							'PROPERTY_USER_TYPE' => $userType
						),
						array('VALUE' => $value),
						array(
							'FORM_NAME' => $field['Form'],
							'VALUE' => $fieldName,
							'DESCRIPTION' => '',
						),
						true
					)
				);
			}
			else
				$renderResult = static::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);

			return $renderResult;
		}

		public static function extractValueSingle(FieldType $fieldType, array $field, array $request)
		{
			return static::extractValueMultiple($fieldType, $field, $request);
		}

		public static function extractValueMultiple(FieldType $fieldType, array $field, array $request)
		{
			if (defined('BX_MOBILE'))
			{
				return self::extractValueMobile($fieldType, $field, $request);
			}

			return parent::extractValueMultiple($fieldType, $field, $request);
		}

		private static function getIblockId(FieldType $fieldType)
		{
			$documentType = $fieldType->getDocumentType();
			$type = explode('_', $documentType[2]);
			return intval($type[1]);
		}

		public static function extractValue(FieldType $fieldType, array $field, array $request)
		{
			$value = parent::extractValue($fieldType, $field, $request);
			if (is_array($value) && isset($value['VALUE']))
			{
				$value = $value['VALUE'];
			}

			if(!$value)
			{
				return null;
			}

			$property = static::getUserType($fieldType);
			$iblockId = self::getIblockId($fieldType);

			if (array_key_exists('AttachFilesWorkflow', $property))
			{
				return call_user_func_array($property['AttachFilesWorkflow'], array($iblockId, $value));
			}

			return null;
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

		public static function clearValueSingle(FieldType $fieldType, $value)
		{
			static::clearValueMultiple($fieldType, $value);
		}

		public static function clearValueMultiple(FieldType $fieldType, $values)
		{
			if(!is_array($values))
			{
				$values = array($values);
			}

			$property = static::getUserType($fieldType);
			$iblockId = self::getIblockId($fieldType);

			if (array_key_exists('DeleteAttachedFiles', $property))
			{
				call_user_func_array($property['DeleteAttachedFiles'], array($iblockId, $values));
			}
		}

		public static function toSingleValue(FieldType $fieldType, $value)
		{
			if (is_array($value) && isset($value['VALUE']))
			{
				$value = $value['VALUE'];
			}
			if (is_array($value) && isset($value[0]))
			{
				$value = $value[0];
			}

			return $value;
		}

		protected static function getMobileControlRenderer(): ?string
		{
			ob_start();
			$className = \CBitrixComponent::includeComponentClass(self::MOBILE_COMPONENT_NAME);
			ob_end_clean();

			return $className ?: null;
		}

		protected static function renderMobileControl(FieldType $fieldType, array $field, $value): string
		{
			/** @var \CMain */
			global $APPLICATION;
			ob_start();
			$APPLICATION->IncludeComponent(
				self::MOBILE_COMPONENT_NAME,
				'',
				[
					'INPUT_NAME' => static::generateControlName($field),
					'INPUT_VALUE' => $value,
					'MULTIPLE' => $fieldType->isMultiple() ? 'Y' : 'N'
				]
			);

			return ob_get_clean();
		}
	}
}