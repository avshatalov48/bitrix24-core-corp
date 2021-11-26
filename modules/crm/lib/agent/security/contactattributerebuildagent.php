<?php
namespace Bitrix\Crm\Agent\Security;

use Bitrix\Main;
use Bitrix\Crm;

class ContactAttributeRebuildAgent extends Crm\Agent\ContactStepwiseAgent
{
	const ITERATION_LIMIT = 200;

	/** @var ContactAttributeRebuildAgent|null */
	private static $instance = null;

	/**
	 * @return ContactAttributeRebuildAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new ContactAttributeRebuildAgent();
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
		return '~CRM_REBUILD_CONTACT_SECURITY_ATTR';
	}
	protected function getProgressOptionName()
	{
		return '~CRM_REBUILD_CONTACT_SECURITY_ATTR_PROGRESS';
	}
	public function process(array $itemIDs)
	{
		$controller = Crm\Security\Manager::getEntityController(\CCrmOwnerType::Contact);
		foreach($itemIDs as $itemID)
		{
			$controller->register('CONTACT', $itemID);
		}
	}
	public function enable($enable)
	{
		parent::enable($enable);
		if (!$enable)
		{
			Crm\Security\Controller\Contact::setEnabled(true);
		}
	}
}