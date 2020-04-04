<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Main;
class ExpressionDataSource extends DataSource
{
	const TYPE_NAME = 'EXPRESSION';

	/**
	 * @param array $settings Settings array.
	 * @param int $userID User ID.
	 */
	public function __construct(array $settings, $userID = 0)
	{
		parent::__construct($settings, $userID, false);
	}

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
	 * @throws Main\NotSupportedException
	 */
	public function getList(array $params)
	{
		/** @var array $select */
		$select = isset($params['select']) && is_array($params['select']) ? $params['select'] : array();
		$name = '';
		if(!empty($select))
		{
			$selectItem = $select[0];
			if(isset($selectItem['name']))
			{
				$name = $selectItem['name'];
			}
		}
		if($name === '')
		{
			$name = 'EXPR';
		}

		$operation = isset($this->settings['operation'])
			? strtoupper($this->settings['operation']) : '';

		$arguments = isset($this->settings['arguments']) && is_array($this->settings['arguments'])
			? $this->settings['arguments'] : array();

		$result = isset($params['result']) && is_array($params['result'])
			? $params['result'] : array();

		if(empty($arguments))
		{
			return array();
		}

		$argQty = count($arguments);
		for($i = 0; $i < $argQty; $i++)
		{
			$argument = $arguments[$i];
			if(is_numeric($argument))
			{
				continue;
			}

			if(preg_match('/^%([^%]+)%$/', $argument, $m) === 1)
			{
				$key = $m[1];
				if(isset($result[$key]))
				{
					$arguments[$i] = isset($result[$key]['value']) ? $result[$key]['value'] : 0;
				}
				else
				{
					$arguments[$i] = (double)$argument;
				}
			}
			else
			{
				$arguments[$i] = (double)$argument;
			}
		}

		if($operation === ExpressionOperation::SUM_OPERATION)
		{
			return array(array($name => array_sum($arguments)));
		}
		elseif($operation === ExpressionOperation::DIFF_OPERATION)
		{
			$value = array_shift($arguments);
			$value -= array_sum($arguments);

			return array(array($name => $value));
		}
		elseif($operation === ExpressionOperation::PERCENT_OPERATION)
		{
			if($argQty > 2)
			{
				throw new Main\NotSupportedException("The Percent operation does not support more than two arguments");
			}

			if($argQty === 1)
			{
				return array(array($name => 100));
			}

			if($arguments[1] == 0)
			{
				return array(array($name => 0));
			}

			return array(array($name => (int)round($arguments[0] / ($arguments[1] / 100), 0)));
		}
		else
		{
			throw new Main\NotSupportedException("The '{$operation}' operation is not supported in current context");
		}
	}

	/**
	 * Prepare permissions check SQL-query.
	 * @return string
	 */
	protected function preparePermissionSql()
	{
		return '';
	}
}