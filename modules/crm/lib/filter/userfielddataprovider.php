<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

Loc::loadMessages(__FILE__);

class UserFieldDataProvider extends \Bitrix\Main\Filter\EntityUFDataProvider
{
	protected $dynamicEntityTitles = null;

	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields(): array
	{
		$userFields = $this->getUserFields();
		$result = parent::prepareFields();
		foreach($result as $fieldName => $field)
		{
			if($userFields[$fieldName]['USER_TYPE_ID'] === 'resourcebooking')
			{
				unset($result[$fieldName]);
			}
		}

		return $result;
	}

	/**
	 * @param string $fieldID
	 * @return array[]|null
	 */
	public function prepareFieldData($fieldID): ?array
	{
		$userFields = $this->getUserFields();
		if(!isset($userFields[$fieldID]))
		{
			return null;
		}

		$userField = $userFields[$fieldID];

		if($userField['USER_TYPE']['USER_TYPE_ID'] === 'crm')
		{
			$settings = (
				isset($userField['SETTINGS']) && is_array($userField['SETTINGS'])
				? $userField['SETTINGS']
				: []
			);

			$entityTypeNames = [];
			$supportedEntityTypeNames = [
				\CCrmOwnerType::LeadName,
				\CCrmOwnerType::DealName,
				\CCrmOwnerType::ContactName,
				\CCrmOwnerType::CompanyName
			];

			foreach($settings as $entityTypeName => $value)
			{
				if(
					$value === 'Y'
					&& (
						in_array($entityTypeName, $supportedEntityTypeNames, true)
						|| \CCrmOwnerType::isPossibleDynamicTypeId(\CCrmOwnerType::ResolveID($entityTypeName))
					)
				)
				{
					$entityTypeNames[] = $entityTypeName;
				}
			}

			$isMultiple = (isset($userField['MULTIPLE']) && $userField['MULTIPLE'] === 'Y');
			$destSelectorParams = [
				'apiVersion' => 3,
				'context' => 'CRM_UF_FILTER_ENTITY',
				'contextCode' => 'CRM',
				'useClientDatabase' => 'N',
				'enableAll' => 'N',
				'enableDepartments' => 'N',
				'enableUsers' => 'N',
				'enableSonetgroups' => 'N',
				'allowEmailInvitation' => 'N',
				'allowSearchEmailUsers' => 'N',
				'departmentSelectDisable' => 'Y',
				'enableCrm' => 'Y',
				'multiple' => ($isMultiple ? 'Y' : 'N'),
				'convertJson' => 'Y',
			];

			$destSelectorParams = array_merge_recursive(
				$destSelectorParams,
				ElementType::getEnableEntityTypesForSelectorOptions($entityTypeNames)
			);

			if (
				is_array($destSelectorParams['addTabCrmDynamics'])
				&& count($destSelectorParams['addTabCrmDynamics'])
			)
			{
				$destSelectorParams['crmDynamicTitles'] = $this->getDynamicEntityTitles();
			}

			return [
				'params' => $destSelectorParams,
			];
		}

		return parent::prepareFieldData($fieldID);
	}

	/**
	 * @return array
	 */
	protected function getDynamicEntityTitles(): array
	{
		if ($this->dynamicEntityTitles)
		{
			return $this->dynamicEntityTitles;
		}

		$typesMap = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadStages' => false
		]);

		$types = $typesMap->getTypes();
		$crmDynamicTitles = [];
		foreach($types as $type)
		{
			$code = 'dynamics_' . $type->getEntityTypeId();
			$crmDynamicTitles[mb_strtoupper($code)] = HtmlFilter::encode($type->getTitle());
		}

		$this->dynamicEntityTitles = $crmDynamicTitles;
		return $this->dynamicEntityTitles;
	}
}
