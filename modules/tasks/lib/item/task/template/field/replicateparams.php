<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 * @internal
 */

namespace Bitrix\Tasks\Item\Task\Template\Field;

use Bitrix\Main\ObjectException;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\Type\StructureChecker;
use Bitrix\Tasks\Util\Type\Structure;

class ReplicateParams extends \Bitrix\Tasks\Item\Field\Scalar
{
	public function translateValueFromDatabase($value, $key, $item)
	{
		return $this->createValue(Type::unSerializeArray($value), $key, $item);
	}

	public function translateValueToDatabase($value, $key, $item)
	{
		return Type::serializeArray($value->get());
	}

	public function createValue($value, $key, $item)
	{
		return static::createValueStructure($value);
	}

	public static function createValueStructure($value)
	{
		if(Structure::isA($value))
		{
			return $value;
		}

		$structure = new Structure($value, array(

			"PERIOD" => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array('daily', 'weekly', 'monthly', 'yearly'), 'DEFAULT' => 'daily'),
			"EVERY_DAY" => array('VALUE' => StructureChecker::TYPE_INT_POSITIVE, 'DEFAULT' => 1),
			"WORKDAY_ONLY" => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array('Y', 'N'), 'DEFAULT' => 'N'),
			"EVERY_WEEK" => array('VALUE' => StructureChecker::TYPE_INT_POSITIVE, 'DEFAULT' => 1),
			"WEEK_DAYS" => array('VALUE' => function($value){
				return array_intersect(array(1, 2, 3, 4, 5, 6, 7), $value);
			}, 'DEFAULT' => array()),
			"MONTHLY_TYPE" => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array(1, 2), 'DEFAULT' => 1),
			"MONTHLY_DAY_NUM" => array('VALUE' => StructureChecker::TYPE_INT_POSITIVE, 'DEFAULT' => 1),
			"MONTHLY_MONTH_NUM_1" => array('VALUE' => StructureChecker::TYPE_INT_POSITIVE, 'DEFAULT' => 1),
			"MONTHLY_WEEK_DAY_NUM" => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array(0, 1, 2, 3, 4), 'DEFAULT' => 0),
			"MONTHLY_WEEK_DAY" => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array(0, 1, 2, 3, 4, 5, 6), 'DEFAULT' => 0),
			"MONTHLY_MONTH_NUM_2" => array('VALUE' => StructureChecker::TYPE_INT_POSITIVE, 'DEFAULT' => 1),
			"YEARLY_TYPE" => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array(1, 2), 'DEFAULT' => 1),
			"YEARLY_DAY_NUM" => array('VALUE' => StructureChecker::TYPE_INT_POSITIVE, 'DEFAULT' => 1),
			"YEARLY_MONTH_1" => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11), 'DEFAULT' => 0),
			"YEARLY_WEEK_DAY_NUM" => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array(0, 1, 2, 3, 4), 'DEFAULT' => 0),
			"YEARLY_WEEK_DAY" => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array(0, 1, 2, 3, 4, 5, 6), 'DEFAULT' => 0),
			"YEARLY_MONTH_2" => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11), 'DEFAULT' => 0),
			"TIME" => array('VALUE' => function($value){
				$time = 18000; // 3600 * 5 (five hours)
				if(trim($value) != '')
				{
					$time = \Bitrix\Tasks\UI::parseTimeAmount($value, 'HH:MI');
				}
				return \Bitrix\Tasks\UI::formatTimeAmount($time, 'HH:MI');
			}, 'DEFAULT' => '05:00'),
			"TIMEZONE_OFFSET" => array('VALUE' => StructureChecker::TYPE_INT),
			"DAILY_MONTH_INTERVAL" => array('VALUE' => StructureChecker::TYPE_INT_POSITIVE, 'DEFAULT' => 0),
			"REPEAT_TILL" => array('VALUE' => function($value, $params){
				if(is_array($params) && (string)($params['data']['END_DATE'] ?? null) != '' && !array_key_exists('REPEAT_TILL', $params['data']))
				{
					$value = 'date';
				}
				return $value;
			}, 'DEFAULT' => 'endless'),
			"TIMES" => array('VALUE' => StructureChecker::TYPE_INT, 'DEFAULT' => 0),
			"START_DATE" => array('VALUE' => function($value)
			{
				if ($value == '')
				{
					return '';
				}

				try
				{
					$value = \Bitrix\Main\Type\DateTime::createFromUserTime($value)->toString();
				}
				catch (ObjectException)
				{
					$value = '';
				}

				return UI::checkDateTime($value);
			}),
			"END_DATE" => array('VALUE' => function($value)
			{
				if ($value == '')
				{
					return '';
				}
				try
				{
					$value = \Bitrix\Main\Type\DateTime::createFromUserTime($value)->toString();
				}
				catch (ObjectException)
				{
					$value = '';
				}

				return UI::checkDateTime($value);
			}),
			"NEXT_EXECUTION_TIME" => array('VALUE' => StructureChecker::TYPE_STRING, 'DEFAULT' => ''),
			'DEADLINE_OFFSET' => ['VALUE' => StructureChecker::TYPE_INT_POSITIVE, 'DEFAULT' => 0],
		));

		return $structure;
	}
}