<?php
namespace Bitrix\Crm\Agent\Security;

use Bitrix\Main;
use Bitrix\Crm;

class DealAttributeRebuildAgent extends Crm\Agent\DealStepwiseAgent
{
	const ITERATION_LIMIT = 200;

	/** @var DealAttributeRebuildAgent|null */
	private static $instance = null;

	/**
	 * @return DealAttributeRebuildAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new DealAttributeRebuildAgent();
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
		return '~CRM_REBUILD_DEAL_SECURITY_ATTR';
	}
	protected function getProgressOptionName()
	{
		return '~CRM_REBUILD_DEAL_SECURITY_ATTR_PROGRESS';
	}
	public function process(array $itemIDs)
	{
		$controller = Crm\Security\Manager::getEntityController(\CCrmOwnerType::Deal);
		foreach ($itemIDs as $item)
		{
			$controller->register(
				Crm\Category\DealCategory::convertToPermissionEntityType($item['CATEGORY_ID']),
				$item['ID']
			);
		}
	}
	public function enable($enable)
	{
		parent::enable($enable);
		if (!$enable)
		{
			Crm\Security\Controller\Deal::setEnabled(true);
		}
	}

	public function getEntityIDs($offsetID, $limit)
	{
		$filter = ['CHECK_PERMISSIONS' => 'N'];
		if ($offsetID > 0)
		{
			$filter['>ID'] = $offsetID;
		}

		$dbResult = \CCrmDeal::GetListEx(
			[
				'ID' => 'ASC',
			],
			$filter,
			false,
			['nTopCount' => $limit],
			[
				'ID',
				'CATEGORY_ID',
			]
		);

		$results = [];

		if (is_object($dbResult))
		{
			while ($fields = $dbResult->Fetch())
			{
				$results[] = [
					'ID' => (int)$fields['ID'],
					'CATEGORY_ID' => (int)$fields['CATEGORY_ID'],
				];
			}
		}

		return $results;
	}
}
