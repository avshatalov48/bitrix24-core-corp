<?php


namespace Bitrix\Crm\Merger\ConflictResolver;


use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\NotSupportedException;

class OpportunityField extends Base
{
	public function __construct(
		string $fieldId,
		protected int $entityTypeId = \CCrmOwnerType::Undefined,
	)
	{
		parent::__construct($fieldId);
	}

	/**
	 * @throws ArgumentNullException
	 * @throws NotSupportedException
	 */
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

		if (isset($seed['PRODUCT_ROWS']) && is_array($seed['PRODUCT_ROWS']))
		{
			$seedProductRows = $seed['PRODUCT_ROWS'];
		}
		else
		{
			$seedProductRows = $this->getProductRowData($seedId);
			$seed['PRODUCT_ROWS'] = $seedProductRows;
			$this->setNewSeedValue($seedProductRows, 'PRODUCT_ROWS');
		}

		if (isset($target['PRODUCT_ROWS']) && is_array($target['PRODUCT_ROWS']))
		{
			$targProductRows = $target['PRODUCT_ROWS'];
		}
		else
		{
			$targProductRows = $this->getProductRowData($targetId);
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

	protected function getProductRowData(int $entityId): array
	{
		$factory = Container::getInstance()->getFactory($this->entityTypeId);
		if (!$factory || !$factory->isLinkWithProductsEnabled())
		{
			$entityName = \CCrmOwnerType::ResolveName($this->entityTypeId);
			throw new NotSupportedException("{$entityName} is not supported");
		}

		$item = $factory->getItem($entityId);
		if ($item === null)
		{
			return [];
		}

		return $item->getProductRows()?->toArray() ?? [];
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