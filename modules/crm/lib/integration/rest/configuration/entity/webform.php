<?php

namespace Bitrix\Crm\Integration\Rest\Configuration\Entity;

use Bitrix\Crm\Integration\Rest\Configuration\Helper;
use Bitrix\Rest;
use Bitrix\Crm;
use CCrmOwnerType;

/**
 * Class WebForm
 * @package Bitrix\Crm\Integration\Rest\Configuration\Entity
 */
class WebForm
{
	const ENTITY_CODE = 'CRM_FORM';

	private static $instance = null;

	private $accessManifest = [
		'crm_form',
		'automated_solution',
	];

	/**
	 * Get instance.
	 *
	 * @return static
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Export.
	 *
	 * @param array $option Option.
	 * @return array|null
	 */
	public function export($option)
	{
		if(!Rest\Configuration\Helper::checkAccessManifest($option, $this->accessManifest))
		{
			return null;
		}

		$helper = new Helper();

		if (!$helper->checkAutomatedSolutionModeExportParams($option))
		{
			return null;
		}

		$automatedSolutionModeParams = $helper->getAutomatedSolutionModeParams($option);

		$content = [
			'list' => [],
			'dynamicTypesInfo' => $helper->exportCrmDynamicTypesInfo(
				['automatedSolutionModeParams' => $automatedSolutionModeParams]
			),
		];
		$list = Crm\WebForm\Internals\FormTable::getDefaultTypeList([
			'select' => ['ID'],
			'filter' => [
				'=ACTIVE' => 'Y',
				'=IS_SYSTEM' => 'N',
			],
		]);
		foreach ($list as $item)
		{
			$formOptions = Crm\WebForm\Options::create($item['ID'])->getArray();
			$formOptions = self::cleanFormOptions($formOptions);

			if (
				$helper->checkDynamicTypeExportConditions(
					array_merge(
						$automatedSolutionModeParams,
						$helper->getDynamicTypeCheckExportParamsByEntityTypeId(
							$this->getDynamicEntityTypeIdBySchemeValue($this->getSchemeValue($formOptions))
						)
					)
				)
			)
			{
				$content['list'][] = $formOptions;
			}
		}

		return [
			'FILE_NAME' => 'list',
			'CONTENT' => $content,
		];
	}

	/**
	 * Clear.
	 *
	 * @param array $option Option.
	 * @return array|null
	 */
	public function clear(array $option)
	{
		if(!Rest\Configuration\Helper::checkAccessManifest($option, $this->accessManifest))
		{
			return null;
		}

		$helper = new Helper();
		if (!$helper->checkAutomatedSolutionModeClearParams($option))
		{
			return null;
		}

		$result = [];

		$helper = new Helper();
		$automatedSolutionModeParams = $helper->getAutomatedSolutionModeParams($option);


		if ($option['CLEAR_FULL'])
		{
			$list = Crm\WebForm\Internals\FormTable::getDefaultTypeList([
				'select' => ['ID', 'ENTITY_SCHEME'],
				'filter' => [
					'=IS_SYSTEM' => 'N',
				],
			]);
			foreach ($list as $item)
			{
				if (
					$helper->checkDynamicTypeExportConditions(
						array_merge(
							$automatedSolutionModeParams,
							$helper->getDynamicTypeCheckExportParamsByEntityTypeId(
								$this->getDynamicEntityTypeIdBySchemeValue((int)($item['ENTITY_SCHEME'] ?? 0))
							)
						)
					)
				)
				{
					Crm\WebForm\Form::delete($item['ID']);
				}
			}
		}

		return $result;
	}

	/**
	 * Import.
	 *
	 * @param array $import Import.
	 * @return array|null
	 */
	public function import(array $import)
	{
		if(!Rest\Configuration\Helper::checkAccessManifest($import, $this->accessManifest))
		{
			return null;
		}

		$helper = new Helper();
		if (!$helper->checkAutomatedSolutionModeImportParams($import))
		{
			return null;
		}

		$result = [];

		if(empty($import['CONTENT']['DATA']))
		{
			return $result;
		}

		$data = $import['CONTENT']['DATA'];
		if(empty($data['list']))
		{
			return $result;
		}

		foreach ($data['list'] as $options)
		{
			$options = self::cleanFormOptions($options);

			$newDynamicEntityTypeId = $this->getNewDynamicEntityTypeIdByScheme($options, $import);
			if (
				$newDynamicEntityTypeId > 0
				&& !$helper->checkDynamicTypeImportConditions($newDynamicEntityTypeId, $import)
			)
			{
				continue;
			}

			$options = $this->prepareFormOptions($options, $import);
			$options = Crm\WebForm\Options::createFromArray($options);
			$options->getForm()->merge([
				'ACTIVE' => 'Y',
				'XML_ID' => 'rest/crm_form',
			]);
			$options->save();
		}

		return $result;
	}

	private function cleanFormOptions(array $options)
	{
		$options['id'] = null;
		$options['captcha'] = [];
		$options['responsible']['userId'] = null;
		$options['callback']['from'] = null;
		$options['analytics'] = [];
		$options['integration'] = [];

		$options['data']['agreements'] = [];
		$options['data']['fields'] = array_map(
			function ($field)
			{
				$field['editing'] = $field['editing']['editable'];
				return $field;
			},
			$options['data']['fields']
		);

		return $options;
	}

	private function getSchemeValue(array $data): int
	{
		if (isset($data['document']['scheme']) && $data['document']['scheme'] > 0)
		{
			return (int)$data['document']['scheme'];
		}

		return 0;
	}

	private function getSchemeValueReminder(int $schemeValue)
	{
		return $schemeValue % 10;
	}

	private function getDynamicEntityTypeIdBySchemeValue(int $schemeValue): int
	{
		$result = CCrmOwnerType::Undefined;

		if ($schemeValue > 10)
		{
			$result = (int)(($schemeValue - ($this->getSchemeValueReminder($schemeValue))) / 10);
		}

		return $result;
	}

	private function getNewDynamicEntityTypeIdByScheme(array $data, array $importData): int
	{
		$oldDynamicEntityTypeId = $this->getDynamicEntityTypeIdBySchemeValue($this->getSchemeValue($data));
		$ratioKey = "DYNAMIC_$oldDynamicEntityTypeId";
		if (isset($importData['RATIO']['CRM_DYNAMIC_TYPES'][$ratioKey]))
		{
			$newDynamicEntityTypeId = (int)$importData['RATIO']['CRM_DYNAMIC_TYPES'][$ratioKey];
			if (CCrmOwnerType::isPossibleDynamicTypeId($newDynamicEntityTypeId))
			{
				return $newDynamicEntityTypeId;
			}
		}

		return CCrmOwnerType::Undefined;
	}

	private function replaceDynamicScheme(array $data, array $importData): array
	{
		$newDynamicEntityTypeId = $this->getNewDynamicEntityTypeIdByScheme($data, $importData);
		if ($newDynamicEntityTypeId > 0)
		{
			$schemeValue = $this->getSchemeValue($data);
			if ($schemeValue > 10)
			{
				$reminder = $this->getSchemeValueReminder($schemeValue);
				$data['document']['scheme'] = $newDynamicEntityTypeId * 10 + $reminder;
			}
		}

		return $data;
	}

	private function replaceDynamicCategory(array $data, array $importData, int $oldDynamicEntityTypeId): array
	{
		if (
			isset($data['document']['dynamic']['category'])
			&& $data['document']['dynamic']['category'] > 0
		)
		{
			$oldCategoryId = (int)$data['document']['dynamic']['category'];
			$ratioKey = "DT{$oldDynamicEntityTypeId}_$oldCategoryId";
			if (
				isset($importData['RATIO']['CRM_STATUS'][$ratioKey])
				&& $importData['RATIO']['CRM_STATUS'][$ratioKey] > 0
			)
			{
				$newCategoryId = (int)$importData['RATIO']['CRM_STATUS'][$ratioKey];
				$data['document']['dynamic']['category'] = $newCategoryId;
			}
		}

		return $data;
	}

	private function prepareDynamicTypeReplacementLists(array $dynamicTypesInfo, array $ratioInfo): array
	{
		$result = [
			'from' => [],
			'to' => [],
		];

		foreach ($dynamicTypesInfo as $oldDynamicTypeId => $dynamicTypeInfo)
		{
			$oldDynamicTypeId = (int)$oldDynamicTypeId;
			$oldDynamicEntityTypeId = (int)($dynamicTypeInfo['entityTypeId'] ?? 0);
			if (
				$oldDynamicEntityTypeId > 0
				&& isset($ratioInfo['CRM_DYNAMIC_TYPES'])
			)
			{
				$newDynamicTypeIdRatioKey = "DT$oldDynamicTypeId";
				$newEntityTypeIdRatioKey = CCrmOwnerType::DynamicTypePrefixName . $oldDynamicEntityTypeId;
				if (
					isset($ratioInfo['CRM_DYNAMIC_TYPES'][$newDynamicTypeIdRatioKey])
					&& $ratioInfo['CRM_DYNAMIC_TYPES'][$newDynamicTypeIdRatioKey] > 0
					&& isset($ratioInfo['CRM_DYNAMIC_TYPES'][$newEntityTypeIdRatioKey])
					&& $ratioInfo['CRM_DYNAMIC_TYPES'][$newEntityTypeIdRatioKey] > 0
				)
				{
					$newDynamicTypeId = (int)$ratioInfo['CRM_DYNAMIC_TYPES'][$newDynamicTypeIdRatioKey];
					$newDynamicEntityTypeId = (int)$ratioInfo['CRM_DYNAMIC_TYPES'][$newEntityTypeIdRatioKey];
					
					$oldDynamicEntityTypePrefix = CCrmOwnerType::DynamicTypePrefixName . $oldDynamicEntityTypeId;
					$newDynamicEntityTypePrefix = CCrmOwnerType::DynamicTypePrefixName . $newDynamicEntityTypeId;

					$matches = [];
					if (
						isset($dynamicTypeInfo['userFieldEntityId'])
						&& is_string($dynamicTypeInfo['userFieldEntityId'])
						&& isset($dynamicTypeInfo['userFieldNames'])
						&& is_array($dynamicTypeInfo['userFieldNames'])
						&& !empty($dynamicTypeInfo['userFieldNames'])
						&& preg_match('/CRM_(\d+)/u', $dynamicTypeInfo['userFieldEntityId'], $matches)
					)
					{
						$oldDynamicTypeId = $matches[1];
						$oldUserFieldPrefix = "UF_CRM_{$oldDynamicTypeId}_";
						$oldUserFieldPrefixLength = strlen($oldUserFieldPrefix);
						$newUserFieldPrefix = "UF_CRM_{$newDynamicTypeId}_";
						foreach($dynamicTypeInfo['userFieldNames'] as $oldUserFieldName)
						{
							if (substr($oldUserFieldName, 0, $oldUserFieldPrefixLength) === $oldUserFieldPrefix)
							{
								$newUserFieldName =
									$newUserFieldPrefix . substr($oldUserFieldName, $oldUserFieldPrefixLength)
								;

								// /DYNAMIC_\d+_UF_CRM_\d+/
								$result['from'][] = "{$oldDynamicEntityTypePrefix}_$oldUserFieldName";
								$result['to'][] = "{$newDynamicEntityTypePrefix}_$newUserFieldName";

								// /UF_CRM_\d+_/
								$result['from'][] = $oldUserFieldName;
								$result['to'][] = $newUserFieldName;
							}
						}
					}

					// /DYNAMIC_\d+/
					$result['from'][] = $oldDynamicEntityTypePrefix;
					$result['to'][] = $newDynamicEntityTypePrefix;
				}
			}
		}

		return $result;
	}

	private function getDynamicTypeReplacementLists(
		array $dynamicTypesInfo,
		array $ratioInfo,
		bool $refresh = false
	): array
	{
		static $replacementLists = null;

		if ($replacementLists === null || $refresh)
		{
			$replacementLists = $this->prepareDynamicTypeReplacementLists($dynamicTypesInfo, $ratioInfo);
		}

		return $replacementLists;
	}

	private function changeDynamicTypeIdentifiers(
		array|string $data,
		array $replacementLists
	): array|string
	{
		if (
			isset($replacementLists['from'])
			&& is_array($replacementLists['from'])
			&& !empty($replacementLists['from'])
			&& isset($replacementLists['to'])
			&& is_array($replacementLists['to'])
			&& !empty($replacementLists['to'])
		)
		{
			if (is_string($data))
			{
				$replaceMarkers = [];
				for ($i = 0; $i < count($replacementLists['from']); $i++)
				{
					$replaceMarkers[] = "_{<-rm[$i]->}_";
				}
				$data = str_replace($replacementLists['from'], $replaceMarkers, $data);
				$data = str_replace($replaceMarkers, $replacementLists['to'], $data);
			}
			elseif (is_array($data))
			{

				foreach ($data as $key => $value)
				{
					$newKey = $this->changeDynamicTypeIdentifiers($key, $replacementLists);
					if ($newKey !== $key)
					{
						unset($data[$key]);
					}

					if (is_string($value) || is_array($value))
					{
						$data[$newKey] = static::changeDynamicTypeIdentifiers($value, $replacementLists);
					}
					else
					{
						$data[$newKey] = $value;
					}
				}
			}
		}

		return $data;
	}

	private function prepareFormOptions(
		array|string $data,
		array $importData,
	): array|string
	{

		$oldDynamicEntityTypeId = $this->getDynamicEntityTypeIdBySchemeValue($this->getSchemeValue($data));
		$data = $this->replaceDynamicScheme($data, $importData);
		$data = $this->replaceDynamicCategory($data, $importData, $oldDynamicEntityTypeId);
		$dynamicTypesInfo = $importData["CONTENT"]["DATA"]["dynamicTypesInfo"] ?? [];
		if (is_array($dynamicTypesInfo) && !empty($dynamicTypesInfo))
		{
			$isSetRatio = (isset($importData['RATIO']) && is_array($importData['RATIO']));
			$replacementLists = $this->getDynamicTypeReplacementLists(
				$dynamicTypesInfo,
				$isSetRatio ? $importData['RATIO'] : []
			);
			$data = $this->changeDynamicTypeIdentifiers($data, $replacementLists);
		}

		return $data;
	}
}