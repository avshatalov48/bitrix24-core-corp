<?php

namespace Bitrix\Crm\Component\EntityList;

use Bitrix\Crm\Filter\Filter as CrmFilter;
use Bitrix\Main\Grid;
use Bitrix\Main\UI\Filter;
use CUtil;

final class FieldRestrictionManager
{
	public const MODE_GRID = 'GRID';
	public const MODE_KANBAN = 'KANBAN';

	private string $mode;
	private array $managers;
	private ?array $filterFields = null;

	public function __construct(string $mode, array $types = [], int $entityTypeId = null)
	{
		$this->mode = $mode;

		// all type supported by default
		if (empty($types))
		{
			$types = [
				FieldRestrictionManagerTypes::CLIENT,
				FieldRestrictionManagerTypes::OBSERVERS,
				FieldRestrictionManagerTypes::ACTIVITY
			];
		}

		foreach ($types as $type)
		{
			$managerInstance = FieldRestrictionManagerTypes::createManagerByType($type);
			if (isset($managerInstance))
			{
				if (isset($entityTypeId))
				{
					$managerInstance->setEntityTypeId($entityTypeId);
				}

				$this->managers[$type] = $managerInstance;
			}
		}

		if (empty($this->managers))
		{
			throw new \InvalidArgumentException('Field restriction managers must be set');
		}
	}

	public function getFilterFields(string $gridId, array $headers, ?CrmFilter $entityFilter): array
	{
		if ($this->filterFields === null)
		{
			$this->fetchRestrictedFieldsEngine($gridId, $headers, $entityFilter);
		}

		return $this->filterFields;
	}

	public function fetchRestrictedFieldsEngine(string $gridId, array $headers, ?CrmFilter $entityFilter): string
	{
		$result = [];
		$this->filterFields = [];

		/** @var $manager FieldRestrictionManagerBase $manager */
		foreach ($this->managers as $manager)
		{
			if ($manager->hasRestrictions())
			{
				$filterFields = (
					isset($entityFilter)
						? $manager->getRestrictedFilterFields($entityFilter)
						: []
				);
				$config = CUtil::PhpToJSObject([
					'callback' => $manager->getJsCallback(),
					'filterId' => $gridId,
					'filterFields' => $filterFields,
					'gridId' => $gridId,
					'gridFields' => $manager->getRestrictedGridFields($headers)
				]);

				$this->filterFields = [...$this->filterFields, ...$filterFields];

				$js = 'BX.ready(() => new BX.Crm.Restriction.FilterFieldsRestriction( ' . $config . ' ));';

				$result[] = '<script>' . $js . '</script>';
			}
		}

		return empty($result) ? '' : implode("\n", $result);
	}

	/**
	 * Clean restricted fields.
	 *
	 * @param Filter\Options $filterOptions
	 * @param Grid\Options|null $gridOptions
	 *
	 * @return void
	 */
	public function removeRestrictedFields(Filter\Options $filterOptions, Grid\Options $gridOptions = null): void
	{
		/** @var $manager FieldRestrictionManagerBase $manager */
		foreach ($this->managers as $manager)
		{
			if ($this->mode === self::MODE_GRID )
			{
				$manager->removeRestrictedFieldsFromSort($gridOptions);
			}

			$manager->removeRestrictedFieldsFromFilter($filterOptions);
		}
	}
}
