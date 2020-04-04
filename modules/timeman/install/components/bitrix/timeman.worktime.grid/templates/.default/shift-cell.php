<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Helper\TimeHelper;

if (empty($recordShiftplanData))
{
	$recordShiftplanData = $arResult;
}
$wrapInLink = $recordShiftplanData['WORKTIME_RECORD'] && $arResult['WRAP_CELL_IN_RECORD_LINK']
			  && $recordShiftplanData['WORKTIME_RECORD']['RECORD_LINK'];
?>
<? if ($wrapInLink): ?>
	<a class="<? if (!$recordShiftplanData['ABSENCE']): ?>timeman-grid-worktime<? endif; ?> timeman-grid-worktime-link"
	href="<?php echo $recordShiftplanData['WORKTIME_RECORD']['RECORD_LINK']; ?>"
	data-id="<?php echo htmlspecialcharsbx($recordShiftplanData['WORKTIME_RECORD']['ID']) ?>"
	data-role="worktime-record-cell"><? endif; ?>
<? if ($recordShiftplanData['WORKTIME_RECORD']['EXPIRED']): ?>
	<div class="timeman-grid-worktime-inner timeman-grid-worktime timeman-grid-worktime-expired-text"><?= Loc::getMessage('TM_WORKTIME_GRID_RECORD_EXPIRED_TITLE'); ?></div>

<? else: ?>
	<?
	$recordShiftplanData['CSS_CLASSES'] = ['timeman-grid-worktime',];
	if (!$recordShiftplanData['IS_SHIFTED_SCHEDULE'])
	{
		if ($recordShiftplanData['DRAWING_DATE'] && $recordShiftplanData['DRAWING_DATE']->format('d.m.Y') === $arResult['nowDate'])
		{
			$recordShiftplanData['CSS_CLASSES'][] = 'timeman-grid-worktime-today';
		}
	}
	else
	{
		$recordShiftplanData['CSS_CLASSES'][] = 'timeman-grid-worktime-shift';
		$theme = 'day';
		$utcShiftEndTime = -1;
		if (isset($recordShiftplanData['WORK_SHIFT']) && $recordShiftplanData['WORK_SHIFT']['WORK_TIME_END'] !== null
			&& isset($recordShiftplanData['SHIFT_PLAN']) && $recordShiftplanData['SHIFT_PLAN']['USER_ID'])
		{
			$utcShiftEndTime = TimeHelper::getInstance()->getUtcTimestampForUserTime(
				$recordShiftplanData['SHIFT_PLAN']['USER_ID'],
				$recordShiftplanData['WORK_SHIFT']['WORK_TIME_END'],
				$recordShiftplanData['SHIFT_PLAN']['DATE_ASSIGNED']
			);
		}

		$isFutureShiftPlan = ($recordShiftplanData['SHIFT_PLAN'] && $arResult['nowTime'] < $utcShiftEndTime);
		$recordShiftplanData['DRAW_DELETE_BTN'] = (bool)$isFutureShiftPlan && $arResult['SHOW_DELETE_SHIFT_PLAN_BTN'];
		if ($isFutureShiftPlan)
		{
			$recordShiftplanData['CSS_CLASSES'][] = 'timeman-grid-worktime-shift-future timeman-grid-worktime-shift-future-' . $theme;
		}
		else
		{
			if ($recordShiftplanData['WORKTIME_RECORD'] && !$recordShiftplanData['SHIFT_PLAN'])
			{
				$theme = 'no-shiftplan';
			}
			if (!$recordShiftplanData['WORKTIME_RECORD'] && $recordShiftplanData['SHIFT_PLAN'])
			{
				if ($recordShiftplanData['WORK_SHIFT']
					&& $arResult['nowTime'] > $utcShiftEndTime
				)
				{
					$theme = 'missed-shift';
				}
			}
			$recordShiftplanData['CSS_CLASSES'][] = 'timeman-grid-worktime-shift-past timeman-grid-worktime-shift-past-' . $theme;
		}
	}


	$recordShiftplanData['CSS_CLASSES'] = implode(' ', $recordShiftplanData['CSS_CLASSES']);
	#
	if (!empty($recordShiftplanData['SHIFT_PLAN']['DATE_ASSIGNED'])
		&& $recordShiftplanData['SHIFT_PLAN']['DATE_ASSIGNED'] instanceof \Bitrix\Main\Type\Date
	)
	{
		$recordShiftplanData['SHIFT_PLAN']['DATE_ASSIGNED_FORMATTED'] = $recordShiftplanData['SHIFT_PLAN']['DATE_ASSIGNED']->format('Y-m-d');
	}
	#
	if ($recordShiftplanData['WORK_SHIFT'])
	{
		$recordShiftplanData['WORK_SHIFT']['FORMATTED_START'] = TimeHelper::getInstance()->convertSecondsToHoursMinutesPostfix($recordShiftplanData['WORK_SHIFT']['WORK_TIME_START']);
		$recordShiftplanData['WORK_SHIFT']['FORMATTED_END'] = TimeHelper::getInstance()->convertSecondsToHoursMinutesPostfix($recordShiftplanData['WORK_SHIFT']['WORK_TIME_END']);
	}
	#
	$utcOffset = $arResult['recordShowUtcOffset'];
	if ($recordShiftplanData['WORKTIME_RECORD'])
	{
		$recordShiftplanData['WORKTIME_RECORD']['FORMATTED_START'] = TimeHelper::getInstance()->convertUtcTimestampToHoursMinutesPostfix(
			$recordShiftplanData['WORKTIME_RECORD']['RECORDED_START_TIMESTAMP'],
			$utcOffset
		);

		$recordShiftplanData['WORKTIME_RECORD']['FORMATTED_END'] = '...';
		if ((int)$recordShiftplanData['WORKTIME_RECORD']['RECORDED_STOP_TIMESTAMP'] !== 0)
		{
			$recordShiftplanData['WORKTIME_RECORD']['FORMATTED_END'] = TimeHelper::getInstance()->convertUtcTimestampToHoursMinutesPostfix(
				$recordShiftplanData['WORKTIME_RECORD']['RECORDED_STOP_TIMESTAMP'],
				$utcOffset
			);
			// various style for letters and numbers
			$recordShiftplanData['WORKTIME_RECORD']['FORMATTED_DURATION'] = preg_replace(
				'#([0-9]+)([\D ]+)#',
				'\\1<span>\\2</span>',
				TimeHelper::getInstance()->convertSecondsToHoursMinutesLocal((int)$recordShiftplanData['WORKTIME_RECORD']['RECORDED_DURATION']));
		}
		else
		{
			$recordShiftplanData['WORKTIME_RECORD']['FORMATTED_DURATION'] = preg_replace(
				'#([0-9]+)([\D ]+)#',
				'\\1<span>\\2</span>',
				TimeHelper::getInstance()->convertSecondsToHoursMinutesLocal($arResult['nowTime'] - $recordShiftplanData['WORKTIME_RECORD']['RECORDED_START_TIMESTAMP']));
		}
	}
	$dataset = '';
	$recordShiftplanData['DATA_ATTRS'] = [];
	if ($recordShiftplanData['WORKTIME_RECORD'] && $recordShiftplanData['IS_SHIFTED_SCHEDULE'])
	{
		$recordShiftplanData['DATA_ATTRS']['role'] = "record-time-info";
		$recordShiftplanData['DATA_ATTRS']['hint-no-icon'] = true;
		$recordShiftplanData['DATA_ATTRS']['hint'] = Loc::getMessage('TM_WORKTIME_GRID_RECORD_INFO_TITLE') . '<br>' .
													 $recordShiftplanData['WORKTIME_RECORD']['FORMATTED_START'] . ' - ' .
													 $recordShiftplanData['WORKTIME_RECORD']['FORMATTED_END'];
	}
	foreach ($recordShiftplanData['DATA_ATTRS'] as $name => $value)
	{
		$dataset .= ' data-' . htmlspecialcharsbx($name) . '="' . htmlspecialcharsbx($value) . '"';
	};
	$recordShiftplanData['DATASET'] = $dataset;

	if ($recordShiftplanData['SHOW_ADD_SHIFT_PLAN_BTN'])
	{
		require __DIR__ . '/add-shift-plan.php';
		return;
	}

	?>
	<? if (empty($recordShiftplanData['WORKTIME_RECORD'])): ?>
		<?php return; ?>
	<? endif; ?>

	<div class="" <?= $recordShiftplanData['DATASET']; ?> data-shift-block="true">
		<div class="timeman-grid-worktime-inner <?= $recordShiftplanData['CSS_CLASSES']; ?>">
			<div class="timeman-grid-worktime-container">
				<? if ($recordShiftplanData['DRAW_DELETE_BTN'] && $recordShiftplanData['SHIFT_PLAN']): ?>
					<div class="timeman-grid-shift-plan-delete" data-role="delete-shiftplan-btn">
						<input type="hidden"
								data-role="shiftId"
								name="<?= htmlspecialcharsbx($arResult['SHIFT_PLAN_FORM_NAME'] . '[shiftId]'); ?>"
								value="<?= htmlspecialcharsbx($recordShiftplanData['WORK_SHIFT']['ID']); ?>">
						<input type="hidden"
								data-role="userId"
								name="<?= htmlspecialcharsbx($arResult['SHIFT_PLAN_FORM_NAME'] . '[userId]'); ?>"
								value="<?= htmlspecialcharsbx($recordShiftplanData['USER_ID']); ?>">
						<input type="hidden"
								data-role="dateAssigned"
								name="<?= htmlspecialcharsbx($arResult['SHIFT_PLAN_FORM_NAME'] . '[dateAssignedFormatted]'); ?>"
								value="<?= htmlspecialcharsbx($recordShiftplanData['SHIFT_PLAN']['DATE_ASSIGNED_FORMATTED']); ?>">
					</div>
				<? endif; ?>
				<? if ($recordShiftplanData['IS_SHIFTED_SCHEDULE']): ?>
					<span class="timeman-grid-worktime-name" data-role="name"><?= htmlspecialcharsbx($recordShiftplanData['WORK_SHIFT']['NAME']) ?></span>
				<? endif; ?>
				<? if ($recordShiftplanData['WORKTIME_RECORD']): ?>
					<div class="timeman-grid-worktime-duration">
					<span class="timeman-grid-worktime-duration-value">
						<?= $recordShiftplanData['WORKTIME_RECORD']['FORMATTED_DURATION']; ?>
					</span>
					</div>
				<? endif; ?>


				<? if ($recordShiftplanData['WORKTIME_RECORD']): ?>
					<? if (!$recordShiftplanData['IS_SHIFTED_SCHEDULE']): ?>
						<div class="timeman-grid-worktime-interval <?= $arResult['SHOW_START_FINISH'] ? '' : 'timeman-hide'; ?>"
								data-role="start-end">
							<span data-role="start"><?= htmlspecialcharsbx($recordShiftplanData['WORKTIME_RECORD']['FORMATTED_START']) ?></span>
							<span>-</span>
							<span data-role="end"><?= htmlspecialcharsbx($recordShiftplanData['WORKTIME_RECORD']['FORMATTED_END']) ?></span>
						</div>
					<? endif; ?>
				<? else: ?>
					<div class="timeman-grid-worktime-interval <?= $arResult['SHOW_START_FINISH'] ? '' : 'timeman-hide'; ?>"
							data-role="start-end">
						<span data-role="start"><?= htmlspecialcharsbx($recordShiftplanData['WORK_SHIFT']['FORMATTED_START']) ?></span>
						<span>-</span>
						<span data-role="end"><?= htmlspecialcharsbx($recordShiftplanData['WORK_SHIFT']['FORMATTED_END']) ?></span>
					</div>
				<? endif; ?>

			</div>
			<span class="timeman-grid-worktime-icon-container">
			<? if (!empty($recordShiftplanData['WORKTIME_RECORD']['WARNINGS']) || $recordShiftplanData['WORKTIME_RECORD']['IS_APPROVED'] === false): ?>
				<?
				$cssClass = 'timeman-grid-worktime-icon-warning';
				$text = Loc::getMessage('TM_WORKTIME_STATS_APPROVAL_REQUIRED');
				if ($recordShiftplanData['WORKTIME_RECORD']['IS_APPROVED'] && !empty($recordShiftplanData['WORKTIME_RECORD']['WARNINGS']))
				{
					$cssClass = 'timeman-grid-worktime-icon-confirmed';
					$text = Loc::getMessage('TM_WORKTIME_STATS_APPROVED');
				}

				$hint = '<span class="tm-violation-hint-title">' . $text . "</span><br>";
				foreach ((array)$recordShiftplanData['WORKTIME_RECORD']['WARNINGS'] as $warning)
				{
					$hint .= ' ' . $warning . '<br>';
				}
				?>
				<span class="timeman-grid-worktime-icon <?= $cssClass; ?>" data-hint-no-icon
						data-role="violation-icon"
						data-hint="<?= htmlspecialcharsbx($hint); ?>">
							</span>
			<? endif; ?>

				<? if (!empty($recordShiftplanData['WORKTIME_RECORD']['VIOLATIONS'])):
					$hint = '<span class="tm-violation-hint-title">' . Loc::getMessage('TM_WORKTIME_STATS_WARNING_TEXT') . "</span><br>";
					foreach ($recordShiftplanData['WORKTIME_RECORD']['VIOLATIONS'] as $violation)
					{
						$hint .= ' ' . $violation . '<br>';
					}
					?>
					<span class="timeman-grid-worktime-icon timeman-grid-worktime-icon-notice"
							data-hint-no-icon data-hint="<?= htmlspecialcharsbx($hint); ?>">
						</span>
				<? endif; ?>
		</span>
		</div>
	</div>
<? endif; ?>
<? if ($wrapInLink): ?></a><? endif; ?>