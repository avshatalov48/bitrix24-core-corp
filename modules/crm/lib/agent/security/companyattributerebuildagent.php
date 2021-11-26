<?php
namespace Bitrix\Crm\Agent\Security;

use Bitrix\Main;
use Bitrix\Crm;

class CompanyAttributeRebuildAgent extends Crm\Agent\CompanyStepwiseAgent
{
	const ITERATION_LIMIT = 200;

	/** @var CompanyAttributeRebuildAgent|null */
	private static $instance = null;

	/**
	 * @return CompanyAttributeRebuildAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new CompanyAttributeRebuildAgent();
		}
		return self::$instance;
	}
	public function getIterationLimit()
	{
		return (int)Main\Config\Option::get(
			'crm',
			'~CRM_SECURITY_ATTR_REBUILD_STEP_LIMIT',
			self::ITERATION_LIMIT
		);
	}
	protected function getOptionName()
	{
		return '~CRM_REBUILD_COMPANY_SECURITY_ATTR';
	}
	protected function getProgressOptionName()
	{
		return '~CRM_REBUILD_COMPANY_SECURITY_ATTR_PROGRESS';
	}
	public function process(array $itemIDs)
	{
		$controller = Crm\Security\Manager::getEntityController(\CCrmOwnerType::Company);
		foreach($itemIDs as $itemID)
		{
			$controller->register('COMPANY', $itemID);
		}
	}
	public function enable($enable)
	{
		parent::enable($enable);
		if (!$enable)
		{
			Crm\Security\Controller\Company::setEnabled(true);
		}
	}
}