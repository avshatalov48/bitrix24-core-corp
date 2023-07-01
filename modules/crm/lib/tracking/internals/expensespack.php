<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main;

/**
 * Class ExpensesPackTable
 *
 * @package Bitrix\Crm\Tracking\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExpensesPack_Query query()
 * @method static EO_ExpensesPack_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ExpensesPack_Result getById($id)
 * @method static EO_ExpensesPack_Result getList(array $parameters = [])
 * @method static EO_ExpensesPack_Entity getEntity()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_ExpensesPack createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_ExpensesPack_Collection createCollection()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_ExpensesPack wakeUpObject($row)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_ExpensesPack_Collection wakeUpCollection($rows)
 */
class ExpensesPackTable extends Main\ORM\Data\DataManager
{
	const TYPE_MANUAL = 0;
	const TYPE_AD = 1;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_tracking_expenses_pack';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'DATE_INSERT' => [
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => function ()
				{
					return new Main\Type\DateTime();
				},
			],
			'SOURCE_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'TYPE_ID' => [
				'data_type' => 'integer',
				'required' => true,
				'default_value' => static::TYPE_MANUAL
			],
			'DATE_FROM' => [
				'data_type' => 'date',
				'required' => true,
				'default_value' => function ()
				{
					return new Main\Type\Date();
				},
			],
			'DATE_TO' => [
				'data_type' => 'date',
				'required' => true,
				'default_value' => function ()
				{
					return new Main\Type\Date();
				},
			],
			'ACTIONS' => [
				'data_type' => 'integer',
				'required' => true,
				'default_value' => 0
			],
			'EXPENSES' => [
				'data_type' => 'float',
				'required' => true,
				'default_value' => 0
			],
			'CURRENCY_ID' => [
				'data_type' => 'string',
				'required' => true
			],
			'COMMENT' => [
				'data_type' => 'string',
				'required' => false
			],
			'SOURCE' => [
				'data_type' => SourceTable::class,
				'reference' => ['=this.SOURCE_ID' => 'ref.ID'],
			],
		];
	}

	protected static function addExpenses($sourceId, Main\Type\Date $from, Main\Type\Date $to, $sum, $currencyId, $actions, $comment = null)
	{
		if (!$sourceId || !$sum || !$currencyId)
		{
			return;
		}

		if ($from->getTimestamp() > $to->getTimestamp())
		{
			return;
		}

		$date = clone $from;
		$days = (int) (($to->getTimestamp() - $from->getTimestamp()) / 86400) + 1;
		if ($days > 365)
		{
			return;
		}

		$stepSum = round($sum / $days, 2);
		$stepSumModulo = round($sum - $stepSum * $days, 2);

		$stepActions = (int) ($actions / $days);
		$stepActionModulo = $actions - $stepActions * $days;

		for ($i = 1; $i <= $days; $i++)
		{
			$currentSum = $stepSum;
			if ($stepSumModulo && $i === 1)
			{
				$currentSum = $stepSum + $stepSumModulo;
			}

			$currentActions = $stepActions;
			if ($stepActionModulo && $i === 1)
			{
				$currentActions = $stepActions + $stepActionModulo;
			}

			static::add([
				'SOURCE_ID' => $sourceId,
				'DATE_STAT' => $date,
				'ACTIONS' => $currentActions,
				'EXPENSES' => $currentSum,
				'CURRENCY_ID' => $currencyId,
				'COMMENT' => $comment
			]);

			$date->add('+1 day');
		}
	}

	/**
	 * On delete event handler.
	 *
	 * @param Main\Orm\Event $event Event.
	 * @return Main\Orm\EventResult
	 */
	public static function onDelete(Main\Orm\Event $event)
	{
		$data = $event->getParameters();
		$id = $data['primary']['ID'];

		$entities = SourceExpensesTable::getList([
			'select' => ['ID'],
			'filter' => ['=PACK_ID' => $id]
		]);
		while ($row = $entities->fetch())
		{
			SourceExpensesTable::delete($row['ID']);
		}

		return new Main\Orm\EventResult();
	}
}