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
	data-role="worktime-record-cell"
	data-individual-param="useIndividualViolationRules">
<? endif; ?>
<? if ($recordShiftplanData['WORKTIME_RECORD']['EXPIRED']): ?>
	<div class="timeman-grid-worktime-inner timeman-grid-worktime timeman-grid-worktime-expired-text"
			data-shift-block="true"><?=
		htmlspecialcharsbx(Loc::getMessage('TM_WORKTIME_GRID_RECORD_EXPIRED_TITLE')); ?></div>

<? else: ?>
	<?
	$recordShiftplanData['CSS_CLASSES'] = ['timeman-grid-worktime',];
	if ($recordShiftplanData['DRAWING_DATE'] && $recordShiftplanData['DRAWING_DATE']->format('d.m.Y') === $arResult['nowDate'])
	{
		$recordShiftplanData['CSS_CLASSES'][] = 'timeman-grid-worktime-today';
	}

	$recordShiftplanData['CSS_CLASSES'] = implode(' ', $recordShiftplanData['CSS_CLASSES']);
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
		}
		// various style for letters and numbers
		$formattedDuration = TimeHelper::getInstance()->convertSecondsToHoursMinutesLocal((int)$recordShiftplanData['WORKTIME_RECORD']['CALCULATED_DURATION']);
		$recordShiftplanData['WORKTIME_RECORD']['FORMATTED_DURATION'] = preg_replace(
			'#([0-9]+)([\D ]+)#',
			'\\1<span>\\2</span>',
			$formattedDuration
		);
	}
	?>
	<? if (empty($recordShiftplanData['WORKTIME_RECORD'])): ?>
		<?php return; ?>
	<? endif; ?>

	<div class="" data-shift-block="true">
		<div class="timeman-grid-worktime-inner <?= $recordShiftplanData['CSS_CLASSES']; ?>">
			<div class="timeman-grid-worktime-container">
				<div class="timeman-grid-worktime-duration">
					<span class="timeman-grid-worktime-duration-value">
						<?= $recordShiftplanData['WORKTIME_RECORD']['FORMATTED_DURATION']; ?>
					</span>
				</div>
				<div class="timeman-grid-worktime-interval <?= $arResult['SHOW_START_FINISH'] ? '' : 'timeman-hide'; ?>"
						data-role="start-end">
					<span data-role="start"><?= htmlspecialcharsbx($recordShiftplanData['WORKTIME_RECORD']['FORMATTED_START']) ?></span>
					<span>-</span>
					<span data-role="end"><?= htmlspecialcharsbx($recordShiftplanData['WORKTIME_RECORD']['FORMATTED_END']) ?></span>
				</div>
			</div>
			<span class="timeman-grid-worktime-icon-container">
			<? if (!empty($recordShiftplanData['WORKTIME_RECORD']['VIOLATIONS']) || $recordShiftplanData['WORKTIME_RECORD']['IS_APPROVED'] === false): ?>
				<?
				$cssClass = 'timeman-grid-worktime-icon-warning';
				$text = Loc::getMessage('TM_WORKTIME_STATS_APPROVAL_REQUIRED');
				if ($recordShiftplanData['WORKTIME_RECORD']['IS_APPROVED'] && !empty($recordShiftplanData['WORKTIME_RECORD']['VIOLATIONS']))
				{
					$cssClass = 'timeman-grid-worktime-icon-confirmed';
					$text = Loc::getMessage('TM_WORKTIME_STATS_APPROVED');
				}
				$hintWarningPersonal = null;
				$hintWarningCommon = null;
				$defaultWarningTitle = '<span class="tm-violation-hint-title">' . $text . "</span><br>";
				foreach ((array)$recordShiftplanData['WORKTIME_RECORD']['VIOLATIONS'] as $warning)
				{
					if ($warning['type'] === 'personal')
					{
						if (!$hintWarningPersonal)
						{
							$hintWarningPersonal = $defaultWarningTitle;
						}
						$hintWarningPersonal .= ' ' . $warning['text'] . '<br>';
					}
					elseif ($warning['type'] === 'common')
					{
						if (!$hintWarningCommon)
						{
							$hintWarningCommon = $defaultWarningTitle;
						}
						$hintWarningCommon .= ' ' . $warning['text'] . '<br>';
					}
				}
				?>
				<? if ($hintWarningPersonal): ?>
					<span class="timeman-grid-worktime-icon <?= $cssClass . ($arParams['GRID_OPTIONS']['SHOW_VIOLATIONS_PERSONAL'] ? '' : ' timeman-hide'); ?>"
							data-hint-no-icon
							data-role="violation-icon"
							data-type="personal"
							data-hint="<?= htmlspecialcharsbx($hintWarningPersonal); ?>">
					</span>
				<? endif; ?>
				<? if ($hintWarningCommon): ?>
					<span class="timeman-grid-worktime-icon <?= $cssClass . ($arParams['GRID_OPTIONS']['SHOW_VIOLATIONS_COMMON'] ? '' : ' timeman-hide'); ?>"
							data-hint-no-icon
							data-role="violation-icon"
							data-type="common"
							data-hint="<?= htmlspecialcharsbx($hintWarningCommon); ?>">
					</span>
				<? endif; ?>
			<? endif; ?>

				<? if (!empty($recordShiftplanData['WORKTIME_RECORD']['WARNINGS'])):
					$violationTitle = '<span class="tm-violation-hint-title">' . Loc::getMessage('TM_WORKTIME_STATS_WARNING_TEXT') . "</span><br>";
					$hintViolationCommon = null;
					$hintViolationPersonal = null;
					foreach ($recordShiftplanData['WORKTIME_RECORD']['WARNINGS'] as $violation)
					{
						if ($violation['type'] === 'personal')
						{
							if (!$hintViolationPersonal)
							{
								$hintViolationPersonal = $violationTitle;
							}
							$hintViolationPersonal .= ' ' . $violation['text'] . '<br>';
						}
						elseif ($violation['type'] === 'common')
						{
							if (!$hintViolationCommon)
							{
								$hintViolationCommon = $violationTitle;
							}
							$hintViolationCommon .= ' ' . $violation['text'] . '<br>';
						}
					}
					?>
					<? if ($hintViolationCommon): ?>
					<span class="timeman-grid-worktime-icon timeman-grid-worktime-icon-notice <?php
					echo ($arParams['GRID_OPTIONS']['SHOW_VIOLATIONS_COMMON'] ? '' : ' timeman-hide'); ?>"
							data-type="common"
							data-role="violation-icon"
							data-hint-no-icon data-hint="<?= htmlspecialcharsbx($hintViolationCommon); ?>">
					</span>
				<? endif; ?>
					<? if ($hintViolationPersonal): ?>
					<span class="timeman-grid-worktime-icon timeman-grid-worktime-icon-notice <?php
					echo ($arParams['GRID_OPTIONS']['SHOW_VIOLATIONS_PERSONAL'] ? '' : ' timeman-hide'); ?>"
							data-type="personal"
							data-role="violation-icon"
							data-hint-no-icon data-hint="<?= htmlspecialcharsbx($hintViolationPersonal); ?>">
					</span>
				<? endif; ?>

				<? endif; ?>
		</span>
		</div>
	</div>
<? endif; ?>
<? if ($wrapInLink): ?></a><? endif; ?>