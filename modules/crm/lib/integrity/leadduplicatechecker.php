<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
use Bitrix\Crm;
//IncludeModuleLangFile(__FILE__);
class LeadDuplicateChecker extends DuplicateChecker
{
	protected $useStrictComparison = false;

	public function __construct()
	{
		parent::__construct(\CCrmOwnerType::Lead);
	}
	public function findDuplicates(Crm\EntityAdapter $adapter, DuplicateSearchParams $params)
	{
		$result = array();
		$fieldNames = $params->getFieldNames();
		$processAllFields = empty($fieldNames);

		$lastName = ($processAllFields || in_array('LAST_NAME', $fieldNames, true)) ? $adapter->getFieldValue('LAST_NAME', '') : '';
		if($lastName !== '')
		{
			$name = ($processAllFields || in_array('NAME', $fieldNames, true)) ? $adapter->getFieldValue('NAME', '') : '';
			$secondName = ($processAllFields || in_array('SECOND_NAME', $fieldNames, true)) ? $adapter->getFieldValue('SECOND_NAME', '') : '';

			$criterion = new DuplicatePersonCriterion($lastName, $name, $secondName);
			$duplicate = $criterion->find($params->getEntityTypeId() ?? \CCrmOwnerType::Undefined, 20);

			if($duplicate !== null)
			{
				$result[] = $duplicate;
			}
		}

		$companyTitle = ($processAllFields || in_array('COMPANY_TITLE', $fieldNames, true)) ? $adapter->getFieldValue('COMPANY_TITLE', '') : '';
		if($companyTitle !== '')
		{
			$criterion = new DuplicateOrganizationCriterion($companyTitle);
			$criterion->setStrictComparison($this->useStrictComparison);

			$duplicate = $criterion->find($params->getEntityTypeId() ?? \CCrmOwnerType::Undefined);
			if($duplicate !== null)
			{
				$result[] = $duplicate;
			}
		}

		if($processAllFields || in_array('FM.PHONE', $fieldNames, true))
		{
			$phones = $this->findMultifieldDuplicates('PHONE', $adapter, $params);
			if(!empty($phones))
			{
				$result = array_merge($result, $phones);
			}
		}
		if($processAllFields || in_array('FM.EMAIL', $fieldNames, true))
		{
			$email = $this->findMultifieldDuplicates('EMAIL', $adapter, $params);
			if(!empty($email))
			{
				$result = array_merge($result, $email);
			}
		}
		return $result;
	}
	public function isStrictComparison()
	{
		return $this->useStrictComparison;
	}
	public function setStrictComparison($useStrictComparison)
	{
		if(!is_bool($useStrictComparison))
		{
			throw new Main\ArgumentTypeException('useStrictComparison', 'boolean');
		}

		$this->useStrictComparison = $useStrictComparison;
	}
}