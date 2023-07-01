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

	public function __construct(string $mode, array $types = [])
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
				$this->managers[$type] = $managerInstance;
			}
		}

		if (empty($this->managers))
		{
			throw new \InvalidArgumentException('Field restriction managers must be set');
		}
	}

	public function fetchRestrictedFields(string $gridId, array $headers, ?CrmFilter $entityFilter): array
	{
		$result = [];
		/** @var $manager FieldRestrictionManagerBase $manager */
		foreach ($this->managers as $type => $manager)
		{
			if ($manager->hasRestrictions())
			{
				$result[$type] = [
					'callback' => $manager->getJsCallback(),
					'filterId' => $gridId,
					'filterFields' =>
						isset($entityFilter)
							? $manager->getRestrictedFilterFields($entityFilter)
							: []
					,
					'gridId' => $gridId,
					'gridFields' => $manager->getRestrictedGridFields($headers)
				];

				if ($manager->returnJsComponent())
				{
					$result[$type] = '<script type="text/javascript">'
						. 'BX.ready(function(){ new BX.Crm.Restriction.FilterFieldsRestriction( ' . CUtil::PhpToJSObject($result[$type]) . ' ); });'
						. '</script>'
					;
				}
			}
		}

		return $result;
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
