<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main;

/**
 * Class SourceExpensesTable
 *
 * @package Bitrix\Crm\Tracking\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SourceExpenses_Query query()
 * @method static EO_SourceExpenses_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SourceExpenses_Result getById($id)
 * @method static EO_SourceExpenses_Result getList(array $parameters = [])
 * @method static EO_SourceExpenses_Entity getEntity()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SourceExpenses createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SourceExpenses_Collection createCollection()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SourceExpenses wakeUpObject($row)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SourceExpenses_Collection wakeUpCollection($rows)
 */
class SourceExpensesTable extends Main\ORM\Data\DataManager
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
		return 'b_crm_tracking_source_expenses';
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
			'PACK_ID' => [
				'data_type' => 'integer',
				'required' => true,
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
			'DATE_STAT' => [
				'data_type' => 'date',
				'required' => true,
				'default_value' => function ()
				{
					return new Main\Type\Date();
				},
			],
			'IMPRESSIONS' => [
				'data_type' => 'integer',
				'required' => true,
				'default_value' => 0
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
			'PACK' => [
				'data_type' => ExpensesPackTable::class,
				'reference' => ['=this.PACK_ID' => 'ref.ID'],
			],
			'SOURCE_CHILD_ID' => [
				'data_type' => 'integer',
				'required' => true,
				'default_value' => 0
			],
			'SOURCE_CHILD' => [
				'data_type' => SourceChildTable::class,
				'reference' => ['=this.SOURCE_CHILD_ID' => 'ref.ID'],
			],
			'SOURCE' => [
				'data_type' => SourceTable::class,
				'reference' => ['=this.SOURCE_ID' => 'ref.ID'],
			],
		];
	}

	/**
	 * Add expenses.
	 *
	 * @param int $sourceId Source ID.
	 * @param Main\Type\Date $from Date from.
	 * @param Main\Type\Date $to Date to.
	 * @param float $sum Sum of expenses
	 * @param string $currencyId Currency ID.
	 * @param int $actions Actions
	 * @param string|null $comment Comment.
	 * @return void
	 */
	public static function addExpenses($sourceId, Main\Type\Date $from, Main\Type\Date $to, $sum, $currencyId, $actions, $comment = null)
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

		$resultPack = ExpensesPackTable::add([
			'SOURCE_ID' => $sourceId,
			'DATE_FROM' => $from,
			'DATE_TO' => $to,
			'ACTIONS' => $actions,
			'EXPENSES' => $sum,
			'CURRENCY_ID' => $currencyId,
			'COMMENT' => $comment
		]);
		if (!$resultPack->getId())
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
				'PACK_ID' => $resultPack->getId(),
				'SOURCE_ID' => $sourceId,
				'DATE_STAT' => $date,
				'ACTIONS' => $currentActions,
				'EXPENSES' => $currentSum,
				'CURRENCY_ID' => $currencyId,
				//'COMMENT' => $comment
			]);

			$date->add('+1 day');
		}
	}
}