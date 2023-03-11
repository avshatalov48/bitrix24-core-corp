<?php

/**
 * @var array $arResult
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);

if (!(is_array($arResult['ENTRIES']) && count($arResult['ENTRIES']) > 0))
{
	return;
}

const NUMBER_SECONDS_DAY = 86400;
const START_DAY_TIME = '00:00';
const MAX_NUMBER_ABSENCES = 5;
$fullDaySeconds = NUMBER_SECONDS_DAY - 1; // 23:59:59
$formShortDate = Context::getCurrent()->getCulture()->getDayMonthFormat();
$timeZone = \CTimeZone::GetOffset();

function formatUserAbsenceDate(int $startDate, int $finishDate, int $now): array
{
	$culture = Context::getCurrent()->getCulture();
	$formFullDate = $culture->getShortDateFormat();
	$formShortDate = $culture->getDayMonthFormat();
	$formatTime = $culture->getShortTimeFormat();
	$formTimeForStart = $formShortDate;
	$formTimeForFinish = $formShortDate;

	if ((date('y', $startDate) !== date('y', $finishDate)) || (date('y', $now) !== date('y', $startDate)))
	{
		$formTimeForStart = $formFullDate;
		$formTimeForFinish = $formFullDate;
	}

	if (date('H:i', $startDate) !== START_DAY_TIME)
	{
		$formTimeForStart .= ' ' . $formatTime;
	}

	if (date('H:i', $finishDate) !== START_DAY_TIME)
	{
		$formTimeForFinish .= ' ' . $formatTime;
	}

	return ['start' => $formTimeForStart, 'finish' => $formTimeForFinish];
}

$minStart = MakeTimeStamp($arResult['ENTRIES'][0]['DATE_ACTIVE_FROM']);
$reasonStart = htmlspecialcharsbx($arResult['ENTRIES'][0]['TITLE']);
$reasonFinish = htmlspecialcharsbx($arResult['ENTRIES'][0]['TITLE']);
$now = time() + $timeZone;
$maxFinish = 0;
$oneDayList = [];
$nextDaysAbsenceList = [];
$countOneDayAbsence = 0;
$diff = 0;

foreach ($arResult['ENTRIES'] as $key => $arEntry)
{
	$startAbsence = MakeTimeStamp($arEntry['DATE_ACTIVE_FROM']);
	$finishAbsence = MakeTimeStamp($arEntry['DATE_ACTIVE_TO']);
	$startDateForDiff = date('y:d:m', $startAbsence);
	$nowDateForDiff = date('y:d:m', $now);
	$finishDateForDiff = date('y:d:m', $finishAbsence);
	$title = htmlspecialcharsbx($arEntry['TITLE']);

	if ($startAbsence > $now && (date('d', $startAbsence) !== date('d', $now)))
	{
		$formatsForNextDaysAbsences = formatUserAbsenceDate($startAbsence, $finishAbsence, $now);

		if ($startAbsence === $finishAbsence && date('H:i', $startAbsence) === START_DAY_TIME)
		{
			$nextDaysAbsenceList[] = FormatDate($formatsForNextDaysAbsences['finish'], $finishAbsence)
				. ' - '
				. Loc::getMessage('INTR_IAU_TPL_ALL_DAY')
				. ' '
				. '('
				. $title
				. ')';
		}
		else
		{
			$nextDaysAbsenceList[] = FormatDate($formatsForNextDaysAbsences['start'], $startAbsence)
				. '&nbsp-&nbsp'
				. FormatDate($formatsForNextDaysAbsences['finish'], $finishAbsence)
				. ' '
				. '('
				. $title
				. ')';
		}
		continue;
	}

	if (
		($startDateForDiff === $finishDateForDiff)
		&& ($startAbsence !== $finishAbsence)
		&& ($finishAbsence > $now)
		&& ($countOneDayAbsence < MAX_NUMBER_ABSENCES)
	)
	{
		$oneDayList[] = date('H:i', $startAbsence)
			. '&nbsp-&nbsp'
			. FormatDate(Context::getCurrent()->getCulture()->getShortTimeFormat(), $finishAbsence)
			. ' '
			. '('
			. $title
			. ')';
		$countOneDayAbsence++;
	}

	if ($startAbsence === $finishAbsence && $startDateForDiff === $nowDateForDiff)
	{
		if ($minStart > $startAbsence)
		{
			$minStart = $startAbsence;
		}

		if ($maxFinish < ($startAbsence + $fullDaySeconds))
		{
			$maxFinish = $startAbsence + $fullDaySeconds;
		}
	}
	elseif ($startAbsence !== $finishAbsence && $finishDateForDiff === $nowDateForDiff && date('H:i:s', $finishAbsence) === '00:00:00')
	{
		if ($maxFinish < ($finishAbsence + $fullDaySeconds))
		{
			$maxFinish = $finishAbsence + $fullDaySeconds;
		}
	}

	if ($now >= $finishAbsence || $now <= $startAbsence)
	{
		continue;
	}

	if ($minStart > $startAbsence)
	{
		$reasonStart = $title;
		$minStart = $startAbsence;
	}

	if ($maxFinish < $finishAbsence)
	{
		$reasonFinish = $title;
		$maxFinish = $finishAbsence;
	}
}

$diff = $maxFinish - $minStart;

if ($diff < 0 && !$oneDayList && !$nextDaysAbsenceList)
{
	return;
}

if($reasonStart === $reasonFinish)
{
	$reasonResult = '(' . $reasonStart . ')';
}
elseif (!$reasonStart && $reasonFinish)
{
	$reasonResult = '(' . $reasonFinish . ')';
}
elseif (!$reasonFinish && $reasonStart)
{
	$reasonResult = '(' . $reasonStart . ')';
}
else
{
	$reasonResult = '(' . $reasonStart . ', ' . $reasonFinish . ')';
}

$formTimeForStart = date("H:i", $minStart);
$formTimeForFinish = date("H:i", $maxFinish);
$dayForStart = date("d", $minStart);
$dayForFinish = date("d", $maxFinish);
?>
<div class="intranet-user-profile-absence">
	<div class="intranet-user-profile-absence-title"><?= Loc::getMessage("SONET_USER_ABSENCE") ?></div>
	<div class="intranet-user-profile-absence-value">
		<div class="intranet-user-profile-absence-value-item">
			<?php
			if (
				($diff > NUMBER_SECONDS_DAY)
				|| ((0 < $diff && $diff < NUMBER_SECONDS_DAY) && ($dayForStart !== $dayForFinish))
			)
			{
				$formatsForAssociationAbsences = formatUserAbsenceDate($minStart, $maxFinish, $now);
				echo Loc::getMessage('INTR_IAU_TPL_FROM')
					. ' '
					. FormatDate($formatsForAssociationAbsences['start'], $minStart)
					. ' '
					. Loc::getMessage('INTR_IAU_TPL_TO')
					. ' '
					. FormatDate($formatsForAssociationAbsences['finish'], $maxFinish)
					. ' '
					. $reasonResult
					. '<br>'
				;

				if ($nextDaysAbsenceList)
				{
					echo Loc::getMessage('INTR_IAU_TPL_IN_NEXT_DAYS') . "<br>";
					$i = 0;

					foreach ($nextDaysAbsenceList as $item)
					{
						echo $item . "<br>";
						$i++;
						if ($i === (MAX_NUMBER_ABSENCES - 1))
						{
							break;
						}
					}
				}
			}
			elseif (($diff === NUMBER_SECONDS_DAY) && ($formTimeForStart === START_DAY_TIME))
			{
				echo Loc::getMessage('INTR_IAU_TPL_FROM')
					. ' '
					. FormatDate($formShortDate, $minStart)
					. ' '
					. Loc::getMessage('INTR_IAU_TPL_TO')
					. ' '
					. FormatDate($formShortDate, $maxFinish)
					. ' '
					. $reasonResult
					. '<br>'
				;

				if ($nextDaysAbsenceList)
				{
					echo Loc::getMessage('INTR_IAU_TPL_IN_NEXT_DAYS') . "<br>";
					$i = 0;

					foreach ($nextDaysAbsenceList as $item)
					{
						echo $item . "<br>";
						$i++;
						if ($i === (MAX_NUMBER_ABSENCES - 1))
						{
							break;
						}
					}
				}
			}
			elseif (($diff === $fullDaySeconds) && ($formTimeForStart === START_DAY_TIME))
			{
				echo Loc::getMessage('INTR_IAU_TPL_TODAY', ['#TIME_LIST#' => Loc::getMessage('INTR_IAU_TPL_ALL_DAY')]) . ' ' . $reasonResult . "<br>";
				if ($nextDaysAbsenceList)
				{
					echo Loc::getMessage('INTR_IAU_TPL_IN_NEXT_DAYS') . "<br>";
					$i = 0;

					foreach ($nextDaysAbsenceList as $item)
					{
						echo $item . "<br>";
						$i++;
						if ($i === (MAX_NUMBER_ABSENCES - 1))
						{
							break;
						}
					}
				}
			}
			else
			{
				$countOneDayAbsence = count($oneDayList);
				$amountFreeLinesForNextDays = MAX_NUMBER_ABSENCES - $countOneDayAbsence;
				if ($countOneDayAbsence)
				{
					foreach ($oneDayList as $item)
					{
						echo Loc::getMessage('INTR_IAU_TPL_TODAY', ['#TIME_LIST#' => $item]) . "<br>";
					}
				}
				$countArrayNextDays = count($nextDaysAbsenceList);
				if ($countArrayNextDays)
				{
					echo Loc::getMessage('INTR_IAU_TPL_IN_NEXT_DAYS') . "<br>";
					if ($amountFreeLinesForNextDays > $countArrayNextDays)
					{
						$amountFreeLinesForNextDays = $countArrayNextDays;
					}

					for ($i = 0; $i < $amountFreeLinesForNextDays; $i++)
					{
						echo $nextDaysAbsenceList[$i] . "<br>";
					}
				}
			}
			?>
		</div>
	</div>
</div>
