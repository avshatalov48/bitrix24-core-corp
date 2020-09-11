<?php


namespace Bitrix\Crm\Merger\ConflictResolver;


class OpportunityField extends Base
{
	protected $entityTypeId = \CCrmOwnerType::Undefined;

	public function setEntityTypeId($entityTypeId): void
	{
		if(!\CCrmOwnerType::IsDefined($entityTypeId))
		{
			throw new \Bitrix\Main\ArgumentException('Is not defined', 'entityTypeId');
		}
		$this->entityTypeId = $entityTypeId;
	}

	public function resolveByValue(&$seedOpportunity, &$targetOpportunity): bool
	{
		if (parent::resolveByValue($seedOpportunity, $targetOpportunity))
		{
			return true;
		}

		$seed = $this->getSeed();
		$target = $this->getTarget();

		$seedId = isset($seed['ID']) ? (int)$seed['ID'] : 0;
		$targetId = isset($target['ID']) ? (int)$target['ID'] : 0;

		$dataSourceClassName = '';
		switch ($this->entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				$dataSourceClassName = '\CCrmLead';
				break;
			case \CCrmOwnerType::Deal:
				$dataSourceClassName = '\CCrmDeal';
				break;
			default:
				throw new \Bitrix\Main\NotSupportedException(\CCrmOwnerType::ResolveName($this->entityTypeId).' is not supported');
		}

		if (isset($seed['PRODUCT_ROWS']) && is_array($seed['PRODUCT_ROWS']))
		{
			$seedProductRows = $seed['PRODUCT_ROWS'];
		}
		else
		{
			$seedProductRows = $dataSourceClassName::LoadProductRows($seedId);
			$seed['PRODUCT_ROWS'] = $seedProductRows;
			$this->setNewSeedValue($seedProductRows, 'PRODUCT_ROWS');
		}

		if (isset($target['PRODUCT_ROWS']) && is_array($target['PRODUCT_ROWS']))
		{
			$targProductRows = $target['PRODUCT_ROWS'];
		}
		else
		{
			$targProductRows = $dataSourceClassName::LoadProductRows($targetId);
			$target['PRODUCT_ROWS'] = $targProductRows;
			$this->setNewTargetValue($seedProductRows, 'PRODUCT_ROWS');
		}

		$seedIsManualOpportunity = isset($seed['IS_MANUAL_OPPORTUNITY']) && $seed['IS_MANUAL_OPPORTUNITY'] === 'Y';
		$targIsManualOpportunity = isset($target['IS_MANUAL_OPPORTUNITY']) && $target['IS_MANUAL_OPPORTUNITY'] === 'Y';

		if(
			!$seedIsManualOpportunity &&
			!$targIsManualOpportunity &&
			(!empty($seedProductRows) || !empty($targProductRows)))
		{
			//Opportunity is depends on Product Rows. Product Rows will be merged in innerMergeBoundEntities
			return true;
		}

		return false;
	}

	protected function getSeedValue(): float
	{
		return (float)parent::getSeedValue();
	}

	protected function getTargetValue(): float
	{
		return (float)parent::getTargetValue();
	}
}