<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\History\Entity\DealStageHistoryTable;
use Bitrix\Crm\History\HistoryEntryType;


class DealStageHistory extends DealDataSource
{
	const TYPE_NAME = 'DEAL_STAGE_HISTORY';
	const GROUP_BY_STAGE = 'STAGE';
	/**
	* @return string
	*/
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}
	/** @return array */
	public function getList(array $params)
	{
		/** @var Filter $filter */
		$filter = isset($params['filter']) ? $params['filter'] : null;
		if(!($filter instanceof Filter))
		{
			throw new Main\ObjectNotFoundException("The 'filter' is not found in params.");
		}

		$this->applyFilterContext($filter);

		$permissionSql = '';
		if($this->enablePermissionCheck)
		{
			$permissionSql = $this->preparePermissionSql();
			if($permissionSql === false)
			{
				//Access denied;
				return array();
			}
		}

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query = new Query(DealStageHistoryTable::getEntity());
		$query->addSelect('STAGE_ID');
		$query->addSelect('QTY');
		$query->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(DISTINCT OWNER_ID)'));

		$query->addFilter('>=EFFECTIVE_DATE', $periodStartDate);
		$query->addFilter('<=EFFECTIVE_DATE', $periodEndDate);

		$typeID = $filter->getExtraParam('typeID', HistoryEntryType::UNDEFINED);
		if($typeID !== HistoryEntryType::UNDEFINED)
		{
			$query->addFilter('=TYPE_ID', $typeID);
		}

		$isLost = $filter->getExtraParam('isLost', null);
		if(is_bool($isLost))
		{
			$query->addFilter('=IS_LOST', $isLost);
		}

		if($this->enablePermissionCheck && is_string($permissionSql) && $permissionSql !== '')
		{
			$query->addFilter('@OWNER_ID', new SqlExpression($permissionSql));
		}

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		//HACK: use SqlExpression to avoid filter sql like (CATEGORY_ID IS NULL OR CATEGORY_ID = 0), that cause the filesort.
		$categoryID = $filter->getExtraParam('dealCategoryID', -1);
		if($categoryID < 0)
		{
			$categoryID = $filter->getExtraParam('dealCategoryId', 0);
		}
		$query->addFilter('=CATEGORY_ID', new Main\DB\SqlExpression('?i', $categoryID));
		$query->addGroup('STAGE_ID');

		$dbResult = $query->exec();
		//Trace('sql', Query::getLastQuery(), 1);
		$result = array();
		while($ary = $dbResult->fetch())
		{
			$result[] = $ary;
		}
		return $result;
	}
	/**
	 * Get current data context
	 * @return string
	 */
	public function getDataContext()
	{
		return DataContext::ENTITY;
	}
}