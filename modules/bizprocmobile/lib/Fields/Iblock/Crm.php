<?php

namespace Bitrix\BizprocMobile\Fields\Iblock;

use Bitrix\Bizproc\FieldType;
use Bitrix\Crm\Integration\UI\EntitySelector\DynamicMultipleProvider;
use Bitrix\Main\Loader;

Loader::requireModule('iblock');

class Crm extends \Bitrix\Iblock\BizprocType\ECrm
{
	public static function internalizeValue(FieldType $fieldType, $context, $value)
	{
		if (
			defined('Bitrix\Bizproc\FieldType::VALUE_CONTEXT_JN_MOBILE')
			&& $context === FieldType::VALUE_CONTEXT_JN_MOBILE
		)
		{
			$pairs = mb_split('_(?=[^_]*$)', $value);
			if ($pairs && count($pairs) === 2)
			{
				$abbr = \CCrmOwnerTypeAbbr::ResolveByTypeName($pairs[0]);
				if ($abbr)
				{
					return sprintf(
						'%s_%d',
						\CCrmOwnerTypeAbbr::ResolveByTypeName($pairs[0]),
						(int)$pairs[1],
					);
				}
			}
		}

		return parent::internalizeValue($fieldType, $context, $value);
	}

	public static function internalizeValueSingle(FieldType $fieldType, $context, $value)
	{
		return parent::internalizeValueSingle(
			$fieldType,
			$context,
			static::toSingleValue($fieldType, $value),
		);
	}

	private static function isUsePrefix(FieldType $fieldType): ?bool
	{
		$options = $fieldType->getOptions();

		if (is_array($options))
		{
			unset($options['VISIBLE']);

			return count(array_filter($options, static fn($mark) => $mark === 'Y')) !== 1;
		}

		return null;
	}

	private static function getDefaultEntityType(FieldType $fieldType) : ?string
	{
		$options = $fieldType->getOptions();

		if (is_array($options))
		{
			unset($options['VISIBLE']);
			$enabledTypes = array_filter($options, static fn($mark) => $mark === 'Y');

			if (count($enabledTypes) === 1)
			{
				return current($enabledTypes);
			}
		}

		return null;
	}


	public static function convertPropertyToView(FieldType $fieldType, int $viewMode, array $property): array
	{
		if ($viewMode === FieldType::RENDER_MODE_JN_MOBILE)
		{
			[$entityIds, $providerOptions] = static::getJnMobileOptions($fieldType);

			$property['Settings'] = [
				'selectorType' => 'crm-element',
				'castType' => static::isUsePrefix($fieldType) ? 'string' : 'int',
				'selectorTitle' => $fieldType->getName(),
				'entityIds' => $entityIds,
				'provider' => [
					'options' => $providerOptions,
				],
			];
			$value = $fieldType->getValue();
			if ($value)
			{
				$property['Settings']['entityList'] = static::loadMobileEntityList(
					\CBPHelper::flatten($value),
					static::getDefaultEntityType($fieldType),
				);
			}
		}

		return parent::convertPropertyToView($fieldType, $viewMode, $property);
	}

	private static function loadMobileEntityList(array $ids, $defaultType): array
	{
		$result = [];

		foreach ($ids as $id)
		{
			$parts = explode('_', $id);
			if(count($parts) > 1)
			{
				$entityName = \CCrmOwnerType::getCaption(
					\CCrmOwnerType::resolveID(\CCrmOwnerTypeAbbr::resolveName($parts[0])), $parts[1]
				);
				$result[] = [
					'id' => $id,
					'title' => $entityName,
				];
			}
			elseif($id && $defaultType)
			{
				$entityName = \CCrmOwnerType::getCaption(
					\CCrmOwnerType::resolveID($defaultType), $id
				);
				$result[] = [
					'id' => $id,
					'title' => $entityName,
				];
			}
		}

		return $result;
	}

	private static function getJnMobileOptions(FieldType $fieldType): array
	{
		$options = [];
		$entityTypeNames = [];

		$entityTypes = $fieldType->getOptions();

		if (is_array($entityTypes))
		{
			unset($entityTypes['VISIBLE']);
			$enabledTypes = array_keys(array_filter($entityTypes, static fn($mark) => $mark === 'Y'));

			foreach ($enabledTypes as $entityName)
			{
				$entityName = mb_strtolower($entityName);

				if (mb_strpos($entityName, 'dynamic_') === 0)
				{
					$entityTypeId = (int)mb_substr($entityName, 8);
					$entityName = DynamicMultipleProvider::DYNAMIC_MULTIPLE_ID;

					$options[$entityName]['dynamicTypeIds'][] = $entityTypeId;
				}

				$entityTypeNames[] = mb_strtolower($entityName);
			}
		}
		$entityTypeNames = array_values(array_unique($entityTypeNames));

		return [$entityTypeNames, $options];
	}
}
