<?php
namespace Bitrix\Tasks\TourGuide;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Task\LogTable;
use Bitrix\Tasks\TourGuide\Exception\TourGuideException;
use Exception;

Loc::loadMessages(__FILE__);

/**
 * Class ExpiredTasksDeadlineChange
 *
 * @package Bitrix\Tasks\TourGuide
 */
class ExpiredTasksDeadlineChange extends TourGuide
{
	protected const OPTION_NAME = 'expiredTasksDeadlineChange';

	/**
	 * @throws TourGuideException
	 */
	public function proceed(): bool
	{
		try
		{
			if (!$this->canPotentiallyProceed())
			{
				return false;
			}

			if (!$this->isNeededExpiredTasksCountReached())
			{
				return false;
			}

			if ($this->isOldUser() || $this->isAlreadyChangeDeadline())
			{
				$this->finish();
				return false;
			}

			if (($currentStep = $this->getCurrentStep()) && !$currentStep->isFinished())
			{
				$currentStep->makeTry();

				if ($currentStep->isFinished() && $this->isCurrentStepTheLast())
				{
					$this->finish();
					return true;
				}

				$currentStep->setAdditionalData(['expiredTasksCountToCheck' => 6]);

				$this->saveToLocalSession();
				$this->saveData();

				return true;
			}

			return false;
		}
		catch (Exception $exception)
		{
			throw new TourGuideException($exception->getMessage());
		}
	}

	public function canPotentiallyProceed(): bool
	{
		return !$this->isFinished() && !$this->isInLocalSession();
	}

	/**
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function isNeededExpiredTasksCountReached(): bool
	{
		$counter = Counter::getInstance($this->getUserId());
		$currentCount =
			$counter->get(Counter\CounterDictionary::COUNTER_MY_EXPIRED)
			+ $counter->get(Counter\CounterDictionary::COUNTER_ORIGINATOR_EXPIRED)
		;

		return $currentCount >= $this->getNeededExpiredTasksCount();
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function isOldUser(): bool
	{
		$query = UserTable::query();
		$query
			->registerRuntimeField(
				null,
				new ExpressionField('MONTH_AGO', 'CURDATE() - INTERVAL 1 MONTH')
			)
			->setSelect(['ID'])
			->where('ID', $this->getUserId())
			->whereColumn('DATE_REGISTER', '<', 'MONTH_AGO')
		;

		return (bool)$query->exec()->fetch();
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function isAlreadyChangeDeadline(): bool
	{
		$query = LogTable::query();
		$query
			->registerRuntimeField(
				null,
				new ExpressionField('CREATED_TS', 'UNIX_TIMESTAMP(%s)', 'CREATED_DATE')
			)
			->setSelect(['ID'])
			->setLimit(1)
			->where('USER_ID', $this->getUserId())
			->where('FIELD', 'DEADLINE')
			->where('FROM_VALUE', '!=', '')
			->whereNotNull('FROM_VALUE')
			->whereColumn('CREATED_TS', '>', 'FROM_VALUE')
		;

		return (bool)$query->exec()->fetch();
	}

	public function getNeededExpiredTasksCount(): int
	{
		$neededCount = 0;

		if ($currentStep = $this->getCurrentStep())
		{
			$neededCount = $currentStep->getAdditionalData()['expiredTasksCountToCheck'];
		}

		return $neededCount;
	}

	protected function getDefaultSteps(): array
	{
		return [
			[
				'maxTriesCount' => 2,
				'additionalData' => [
					'expiredTasksCountToCheck' => 3,
				],
			],
		];
	}

	protected function loadPopupData(): array
	{
		$prefix = 'TASKS_TOUR_GUIDE_EXPIRED_TASKS_DEADLINE_CHANGE';

		return [
			[
				[
					'title' => Loc::getMessage("{$prefix}_POPUP_0_TITLE"),
					'text' => Loc::getMessage("{$prefix}_POPUP_0_TEXT"),
				],
				[
					'title' => Loc::getMessage("{$prefix}_POPUP_1_TITLE"),
					'text' => Loc::getMessage("{$prefix}_POPUP_1_TEXT"),
				],
				[
					'title' => Loc::getMessage("{$prefix}_POPUP_2_TITLE"),
					'text' => Loc::getMessage("{$prefix}_POPUP_2_TEXT"),
					'buttons' => [
						Loc::getMessage("{$prefix}_POPUP_2_BUTTON"),
					],
				],
			],
		];
	}
}
