<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Crm\Widget\Custom\SaleTarget;
use Bitrix\Main;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Statistics\Entity\DealSumStatisticsTable;

class DealSaleTarget extends DealDataSource
{
	const TYPE_NAME = 'DEAL_SALE_TARGET';

	/**
	 * Get type name.
	 * @return string
	 */
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}
	/**
	 * Prepare item list according to income parameters.
	 * @param array $params Parameters.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\InvalidOperationException
	 * @throws Main\ObjectNotFoundException
	 */
	public function getList(array $params)
	{
		$data = SaleTarget::getInstance()->getDataFor($this->getUserID());
		list($current, $totalCurrent) = static::getCurrentValues($data['configuration']);
		$data['current'] = $current;
		$data['totalCurrent'] = $totalCurrent;

		return $data ? array($data) : array();
	}

	public function getAttributes()
	{
		$attributes = parent::getAttributes();
		$attributes['isConfigurable'] = true;
		$attributes['canEdit'] = SaleTarget::getInstance()->canEdit($this->getUserID());
		return $attributes;
	}

	public static function getCurrentValues(array $configuration)
	{
		$result = array();
		if ($configuration['target']['totalGoal'] < 0) //Restricted by READ permissions.
		{
			return array(0, 0);
		}

		$widget = SaleTarget::getInstance();

		list($periodLeftBorder, $periodRightBorder) = $widget->getPeriodBorders($configuration['period']);

		$query = new Query(DealSumStatisticsTable::getEntity());
		$name = $nameAlias = 'SUM_TOTAL';
		if($configuration['target']['type'] === $widget::TARGET_TYPE_QUANTITY)
		{
			$query->registerRuntimeField('', new ExpressionField($nameAlias, "COUNT(*)"));
		}
		else
		{
			$nameAlias = "{$nameAlias}_R";
			$query->registerRuntimeField('', new ExpressionField($nameAlias, "SUM(%s)", $name));
		}

		$query->addSelect($nameAlias);
		$query->setTableAliasPostfix('_s2');

		$subQuery = new Query(DealSumStatisticsTable::getEntity());
		$subQuery->setTableAliasPostfix('_s1');
		$subQuery->addSelect('OWNER_ID');

		$subQuery->addFilter('>=END_DATE', Main\Type\Date::createFromTimestamp($periodLeftBorder));
		$subQuery->addFilter('<=END_DATE',Main\Type\Date::createFromTimestamp($periodRightBorder));

		$subQuery->addFilter('=STAGE_SEMANTIC_ID', PhaseSemantics::SUCCESS);

		if (
			$configuration['type'] === $widget::TYPE_USER
			&& is_array($configuration['target']['goal'])
			&& count($configuration['target']['goal']) > 0
		)
		{
			$subQuery->addFilter('@RESPONSIBLE_ID', array_keys($configuration['target']['goal']));
		}

		$subQuery->addGroup('OWNER_ID');
		$subQuery->addSelect('MAX_CREATED_DATE');
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(%s)', 'CREATED_DATE'));

		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.OWNER_ID' => 'ref.OWNER_ID', '=this.CREATED_DATE' => 'ref.MAX_CREATED_DATE'),
				array('join_type' => 'INNER')
			)
		);

		if($configuration['type'] === $widget::TYPE_USER)
		{
			$query->addSelect('RESPONSIBLE_ID');
			$query->addGroup('RESPONSIBLE_ID');
		}
		elseif($configuration['type'] === $widget::TYPE_CATEGORY)
		{
			$query->addSelect('CATEGORY_ID');
			$query->addGroup('CATEGORY_ID');
		}

		$dbResult = $query->exec();
		$useAlias = $nameAlias !== $name;
		if($configuration['type'] === $widget::TYPE_USER)
		{
			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$ary[$name] = $ary[$nameAlias];
					unset($ary[$nameAlias]);
				}

				if (!isset($result[$ary['RESPONSIBLE_ID']]))
					$result[$ary['RESPONSIBLE_ID']] = 0;

				$result[$ary['RESPONSIBLE_ID']] += $ary[$name];
			}
		}
		elseif($configuration['type'] === $widget::TYPE_CATEGORY)
		{
			$result['*'] = 0; //Array keys may be [0, 1, 2, ...], '*' prevents js Array creation, plain Object is expected.
			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$ary[$name] = $ary[$nameAlias];
					unset($ary[$nameAlias]);
				}

				if (!isset($result[$ary['CATEGORY_ID']]))
					$result[$ary['CATEGORY_ID']] = 0;

				$result[$ary['CATEGORY_ID']] += $ary[$name];
			}
		}
		else
		{
			$companyValue = 0;
			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$companyValue += $ary[$nameAlias];
				}
				else
				{
					$companyValue += $ary[$name];
				}
			}
			$result[$widget::TYPE_COMPANY] = $companyValue;
		}

		$total = 0;
		foreach ($result as $key => $value)
		{
			$total += $value;
			if ($configuration['target']['goal'][$key] < 0) // = -1, restricted by READ permissions
			{
				$result[$key] = 0;
			}
		}

		return array($result, $total);
	}
}