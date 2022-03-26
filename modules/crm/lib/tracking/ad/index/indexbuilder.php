<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Ad\Index;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

/**
 * Class IndexBuilder.
 *
 * @package Bitrix\Crm\Tracking\Source\Level
 */
final class IndexBuilder extends Tracking\Ad\Builder
{
	private const fetchDayLimit = 14;

	/** @var Tracking\Source\Base[] $sources Sources. */
	protected $sources;
	protected $sourceCodes = [];

	/** @var static $instance Instance. */
	protected static $instance;

	protected $sourceId;

	/** @var Main\Type\Date $minDate */
	protected $maxDate;
	/** @var Main\Type\Date $maxDate */
	protected $minDate;

	/** @var Main\Type\Date $dateFrom */
	protected $dateFrom;
	/** @var Main\Type\Date $dateTo */
	protected $dateTo;

	/**
	 * Get complete label.
	 *
	 * @return string|null
	 */
	public function getCompleteLabel()
	{
		if ($this->isComplete())
		{
			return null;
		}

		if (!(list($dateFrom, $dateTo) = $this->getFetchDates()))
		{
			return null;
		}

		$format = Main\Context::getCurrent()->getCulture()->getDayMonthFormat();
		return Loc::getMessage('CRM_TRACKING_AD_INDEX_INDEX_BUILDER_LABEL', [
			'%from%' => $dateFrom->format($format),
			'%to%' => $dateTo->format($format),
		]);
	}

	protected function isBuilt()
	{
		$dateExtremum = Tracking\Internals\SourceExpensesTable::getRow([
			'select' => ['MIN_DATE_STAT', 'MAX_DATE_STAT'],
			'filter' => [
				'=SOURCE_ID' => $this->sourceId,
				'=TYPE_ID' => Tracking\Internals\SourceExpensesTable::TYPE_AD,
			],
			'runtime' => [
				new Main\ORM\Fields\ExpressionField('MIN_DATE_STAT', 'MIN(%s)', 'DATE_STAT'),
				new Main\ORM\Fields\ExpressionField('MAX_DATE_STAT', 'MAX(%s)', 'DATE_STAT'),
			]
		]);
		$this->minDate = $dateExtremum['MIN_DATE_STAT'] ?? null;
		$this->maxDate = $dateExtremum['MAX_DATE_STAT'] ?? null;

		if (!$this->minDate || !$this->maxDate)
		{
			return false;
		}

		return (
			$this->minDate->getTimestamp() <= $this->dateFrom->getTimestamp()
			&&
			$this->maxDate->getTimestamp() >= $this->dateTo->getTimestamp()
		);
	}

	protected function build()
	{
		if (!$this->sourceId)
		{
			$this->errorCollection->setError(new Main\Error('Source ID is empty.'));
			return;
		}

		$sources = Tracking\Provider::getReadySources();
		$sources = array_combine(
			array_column($sources, 'ID'),
			array_values($sources)
		);
		if (!$sources[$this->sourceId])
		{
			$this->errorCollection->setError(new Main\Error('Source not found by ID.'));
			return;
		}


		$ad = new Tracking\Analytics\Ad($sources[$this->sourceId]);
		if (!$ad->isSupportExpensesReport())
		{
			$this->errorCollection->setError(new Main\Error('Source expenses report not supported.'));
			return;
		}

		if (!(list($dateFrom, $dateTo) = $this->getFetchDates()))
		{
			return;
		}
		$result = $ad->getExpensesReport($dateFrom, $dateTo);
		if (!$result->isSuccess())
		{
			$this->errorCollection->add($result->getErrors());
			return;
		}

		$data = $result->getData();
		unset($result);

		// import structure of campaigns, groups, keywords
		$map = $this->importStructure($data);

		// import daily expenses
		if (empty($map['KEYWORDS']))
		{
			$map['KEYWORDS'] = [];
		}
		$packId = $this->importDailyExpenses($map['KEYWORDS'], $data);

		// insert zero expenses for actual dates if no data imported
		$this->closeDailyExpenses($dateFrom, $dateTo, $packId);
	}

	private function getFetchDates(): array
	{
		$dateFrom = clone $this->dateFrom;
		$dateTo = clone $this->dateTo;

		$diff = ($dateTo->getTimestamp() - $dateFrom->getTimestamp()) / (3600 * 24);
		if ($diff < 0)
		{
			$this->errorCollection->setError(new Main\Error('Wrong period.'));
			return [];
		}

		if ($diff > 365)
		{
			$this->errorCollection->setError(new Main\Error('Period is too long. Set period less than 1 year.'));
			return [];
		}

		if ((time() - $dateFrom->getTimestamp()) / (3600 * 24) > 365)
		{
			$this->errorCollection->setError(new Main\Error('Period should be earlier than 1 year.'));
			return [];
		}

		if ($dateFrom->getTimestamp() >= time() || $dateTo->getTimestamp() >= time())
		{
			$this->errorCollection->setError(new Main\Error('Period should not contain today or future days.'));
			return [];
		}

		if ($this->minDate && $dateFrom->getTimestamp() < $this->minDate->getTimestamp())
		{
			$dateFrom = clone $this->minDate;
			$dateTo = clone $this->minDate;
			$dateTo->add('-1 day');
			for ($i = 1; $i < static::fetchDayLimit; $i++)
			{
				$dateFrom->add('-1 day');
				if ($dateFrom->getTimestamp() <= $this->dateFrom->getTimestamp())
				{
					break;
				}
			}

			return [$dateFrom, $dateTo];
		}

		if ($this->maxDate && $dateTo->getTimestamp() > $this->maxDate->getTimestamp())
		{
			$dateFrom = clone $this->maxDate;
			$dateTo = clone $this->maxDate;
			$dateFrom->add('1 day');
			for ($i = 1; $i < static::fetchDayLimit; $i++)
			{
				$dateTo->add('1 day');
				if ($dateTo->getTimestamp() >= $this->dateTo->getTimestamp())
				{
					break;
				}
			}

			return [$dateFrom, $dateTo];
		}

		// if no index
		return [$dateFrom, $dateTo];
	}

	private function addChild($level, $code, $title = '--', $parentId = 0)
	{
		$title = $title ?: '--';
		$row = Tracking\Internals\SourceChildTable::getRow([
			'select' => ['ID', 'TITLE'],
			'filter' => [
				'=SOURCE_ID' => $this->sourceId,
				'=CODE' => $code,
				'=PARENT_ID' => $parentId,

			],
		]);
		if ($row)
		{
			if ($row['TITLE'] !== $title)
			{
				Tracking\Internals\SourceChildTable::update($row['ID'], ['TITLE' => $title]);
			}
			return $row['ID'];
		}
		else
		{
			$result = Tracking\Internals\SourceChildTable::add([
				'SOURCE_ID' => $this->sourceId,
				'CODE' => $code,
				'LEVEL' => $level,
				'TITLE' => $title,
				'PARENT_ID' => $parentId,
			]);
			return $result->getId();
		}

	}

	private function getSequence()
	{
		return [
			['key' => 'CAMPAIGNS', 'level' => Tracking\Source\Level\Type::Campaign, 'mappable' => true],
			['key' => 'AD_GROUPS', 'level' => Tracking\Source\Level\Type::Adgroup, 'mappable' => true],
			['key' => 'KEYWORDS', 'level' => Tracking\Source\Level\Type::Keyword],
		];
	}

	private function importStructure(array &$data)
	{
		$sequence = $this->getSequence();

		$map = [];
		$parentKey = null;
		foreach ($sequence as list('key' => $key, 'level' => $level, 'unique' => $unique))
		{
			foreach ($data[$key] as $row)
			{
				$parentId = 0;
				if ($parentKey)
				{
					$parentId = $map[$parentKey][$row['PARENT_ID']] ?? null;
					if (!$parentId)
					{
						continue;
					}
				}

				if (!$row['ID'])
				{
					continue;
				}

				$id = $this->addChild(
					$level,
					$row['ID'],
					$row['NAME'],
					$parentId
				);

				if (!$unique && isset($map[$key][$row['ID']]))
				{
					if (!is_array($map[$key][$row['ID']]))
					{
						$map[$key][$row['ID']] = [
							0 => $map[$key][$row['ID']]
						];
					}
					$map[$key][$row['ID']][$row['PARENT_ID']] = $id;
				}
				else
				{
					$map[$key][$row['ID']] = $id;
				}
			}

			$parentKey = $key;
		}

		return $map;
	}

	private function importDailyExpenses(array &$map, &$data)
	{
		if (empty($data['ROWS']))
		{
			return null;
		}

		$rows = &$data['ROWS'];

		$packId = Tracking\Internals\ExpensesPackTable::add([
			'SOURCE_ID' => $this->sourceId,
			'TYPE_ID' => Tracking\Internals\ExpensesPackTable::TYPE_AD,
			'DATE_FROM' => $this->dateFrom,
			'DATE_TO' => $this->dateTo,
			'ACTIONS' => 0,
			'EXPENSES' => 0,
			'CURRENCY_ID' => 0,
		])->getId();


		foreach ($rows as $row)
		{
			if (!(int) $row['COST'])
			{
				continue;
			}

			$childId = $map[$row['KWID']] ?? null;
			$childId = is_array($childId)
				? $childId[$row['GID']] ?: $childId[0]
				: $childId;

			if (!$childId)
			{
				continue;
			}

			$date = new Main\Type\Date($row['DATE'], 'Y-m-d');
			if ($this->dateFrom && $this->dateFrom->getTimestamp() > $date->getTimestamp())
			{
				continue;
			}
			if ($this->dateTo && $this->dateTo->getTimestamp() < $date->getTimestamp())
			{
				continue;
			}

			Tracking\Internals\SourceExpensesTable::add([
				'PACK_ID' => $packId,
				'SOURCE_ID' => $this->sourceId,
				'TYPE_ID' => Tracking\Internals\SourceExpensesTable::TYPE_AD,
				'DATE_STAT' => $date,
				'IMPRESSIONS' => (int) $row['IMPRESSIONS'],
				'ACTIONS' => (int) $row['CLICKS'],
				'EXPENSES' => (int) $row['COST'],
				'CURRENCY_ID' => $data['CURRENCY'],
				'SOURCE_CHILD_ID' => $childId,
			]);
		}

		return $packId;
	}

	private function closeDailyExpenses(Main\Type\Date $dateFrom, Main\Type\Date $dateTo, $packId = null)
	{
		$rowFrom = Tracking\Internals\SourceExpensesTable::getRow([
			'select' => ['ID'],
			'filter' => [
				'=SOURCE_ID' => $this->sourceId,
				'=DATE_STAT' => $dateFrom,
				'=TYPE_ID' => Tracking\Internals\SourceExpensesTable::TYPE_AD,
			],
		]);

		$rowTo = Tracking\Internals\SourceExpensesTable::getRow([
			'select' => ['ID'],
			'filter' => [
				'=SOURCE_ID' => $this->sourceId,
				'=DATE_STAT' => $dateTo,
				'=TYPE_ID' => Tracking\Internals\SourceExpensesTable::TYPE_AD,
			],
		]);

		if ($rowFrom && $rowTo)
		{
			return;
		}

		if (!$packId)
		{
			$packId = Tracking\Internals\ExpensesPackTable::add([
				'SOURCE_ID' => $this->sourceId,
				'TYPE_ID' => Tracking\Internals\ExpensesPackTable::TYPE_AD,
				'DATE_FROM' => $this->dateFrom,
				'DATE_TO' => $this->dateTo,
				'ACTIONS' => 0,
				'EXPENSES' => 0,
				'CURRENCY_ID' => 0,
			])->getId();
		}

		if (!$rowFrom)
		{
			Tracking\Internals\SourceExpensesTable::add([
				'PACK_ID' => $packId,
				'SOURCE_ID' => $this->sourceId,
				'TYPE_ID' => Tracking\Internals\SourceExpensesTable::TYPE_AD,
				'DATE_STAT' => $dateFrom,
				'IMPRESSIONS' => 0,
				'ACTIONS' => 0,
				'EXPENSES' => 0,
				'CURRENCY_ID' => 'USD',
				'SOURCE_CHILD_ID' => 0,
			]);
		}


		if (!$rowTo)
		{
			Tracking\Internals\SourceExpensesTable::add([
				'PACK_ID' => $packId,
				'SOURCE_ID' => $this->sourceId,
				'TYPE_ID' => Tracking\Internals\SourceExpensesTable::TYPE_AD,
				'DATE_STAT' => $dateTo,
				'IMPRESSIONS' => 0,
				'ACTIONS' => 0,
				'EXPENSES' => 0,
				'CURRENCY_ID' => 'USD',
				'SOURCE_CHILD_ID' => 0,
			]);
		}
	}
}