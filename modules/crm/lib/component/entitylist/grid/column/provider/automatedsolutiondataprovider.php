<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Column\Provider;

use Bitrix\Crm\AutomatedSolution\Entity\AutomatedSolutionTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Grid\Column\DataProvider;
use Bitrix\Main\Grid\Column\Type;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Entity;

final class AutomatedSolutionDataProvider extends DataProvider
{
	/**
	 * Used only for field captions. If you are doing something else with it, you are wrong.
	 */
	private Entity $entity;

	public function __construct()
	{
		$this->entity = AutomatedSolutionTable::getEntity();

		parent::__construct();
	}

	public function prepareColumns(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		return [
			$this->createColumn('ID')
				->setType(Type::INT)
				->setName($this->getFieldCaptionFromOrm('ID'))
				->setTitle($this->getFieldCaptionFromOrm('ID'))
				->setSort('ID')
				->setDefault(true)
				->setNecessary(true)
			,

			$this->createColumn('TITLE')
				->setType(Type::HTML)
				->setName($this->getFieldCaptionFromOrm('TITLE'))
				->setTitle($this->getFieldCaptionFromOrm('TITLE'))
				->setSort('TITLE')
				->setDefault(true)
			,

			$this->createColumn('CREATED_BY')
				->setType(Type::HTML)
				->setName($this->getFieldCaptionFromOrm('CREATED_BY'))
				->setTitle($this->getFieldCaptionFromOrm('CREATED_BY'))
				->setSort('CREATED_BY')
			,

			$this->createColumn('CREATED_TIME')
				->setType(Type::DATE)
				->setName($this->getFieldCaptionFromOrm('CREATED_TIME'))
				->setTitle($this->getFieldCaptionFromOrm('CREATED_TIME'))
				->setSort('CREATED_TIME')
			,

			$this->createColumn('UPDATED_BY')
				->setType(Type::HTML)
				->setName($this->getFieldCaptionFromOrm('UPDATED_BY'))
				->setTitle($this->getFieldCaptionFromOrm('UPDATED_BY'))
				->setSort('UPDATED_BY')
				->setDefault(true)
			,

			$this->createColumn('UPDATED_TIME')
				->setType(Type::DATE)
				->setName($this->getFieldCaptionFromOrm('UPDATED_TIME'))
				->setTitle($this->getFieldCaptionFromOrm('UPDATED_TIME'))
				->setSort('UPDATED_TIME')
				->setDefault(true)
			,

			$this->createColumn('LAST_ACTIVITY_TIME')
				->setType(Type::DATE)
				->setName(Loc::getMessage('CRM_TYPE_ITEM_FIELD_LAST_ACTIVITY_TIME_2'))
				->setTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_LAST_ACTIVITY_TIME_2'))
				->setDefault(true)
			,

			$this->createColumn('TYPE_IDS')
				->setType(Type::HTML)
				->setName(Loc::getMessage('CRM_GRID_AUTOMATED_SOLUTION_COLUMN_TYPE_IDS'))
				->setTitle(Loc::getMessage('CRM_GRID_AUTOMATED_SOLUTION_COLUMN_TYPE_IDS'))
				->setDefault(true)
			,
		];
	}

	private function getFieldCaptionFromOrm(string $fieldName): string
	{
		return $this->entity->getField($fieldName)->getTitle();
	}
}
