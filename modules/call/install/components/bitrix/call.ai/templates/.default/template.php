<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Call\Integration\AI;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;


// include 'template.html.php'; return;
// include 'error.php'; return;


/**
 * @global \CMain $APPLICATION
 * @var array $arResult
 * @var array $arParams
 */
global $APPLICATION;

$overview = $arResult['OVERVIEW'] instanceof AI\Outcome\Overview ? $arResult['OVERVIEW'] : null;
$insights = $arResult['INSIGHTS'] instanceof AI\Outcome\Insights ? $arResult['INSIGHTS'] : null;
$summary = $arResult['SUMMARY'] instanceof AI\Outcome\Summary ? $arResult['SUMMARY'] : null;
$transcribe = $arResult['TRANSCRIBE'] instanceof AI\Outcome\Transcription ? $arResult['TRANSCRIBE'] : null;
$track = !empty($arResult['RECORD']) ? $arResult['RECORD'] : null;

\Bitrix\Main\UI\Extension::load([
	'ui.tooltip',
	'call.component.user-list',
	'call.component.elements.audioplayer',
	'call.lib.analytics',
]);


$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass . ' ' : '') . 'no-all-paddings no-background bx-call-component-call-ai-page bitrix24-light-theme'
);

$userCount = Loc::getMessagePlural('CALL_COMPONENT_USER', $arResult['USER_COUNT'], ['#USER_COUNT#' => $arResult['USER_COUNT']]);

Toolbar::deleteFavoriteStar();
Toolbar::addAfterTitleHtml(<<<HTML
	<div class="bx-call-component-call-ai-page__title-details">
		<div class="bx-call-component-call-ai-page__title-users-container" data-call-id="{$arResult['CALL_ID']}"></div>
	</div>
	HTML
);

if (!empty($arResult['FEEDBACK_URL']))
{
	$feedbackButton = new Button([
		'color' => Color::LIGHT_BORDER,
		'text' => Loc::getMessage('CALL_COMPONENT_FEEDBACK'),
		'classList' => [],
		'link' => $arResult['FEEDBACK_URL'],
	]);

	Toolbar::addButton($feedbackButton, ButtonLocation::RIGHT);
}

?>
<div class="bx-call-component-call-ai" data-call-id="<?= $arResult['CALL_ID'] ?>">
	<div class="bx-call-component-call-ai__resume-container">
		<div class="bx-call-component-call-ai__resume-wrapper">
			<h3 class="bx-call-component-call-ai__resume-title"><?= $overview?->topic ?></h3>

			<p class="bx-call-component-call-ai__resume-description">
				<?
				if ($overview?->agenda)
				{
					echo $overview->agenda?->explanation. '<br>';
					echo $overview->agenda?->quote;
				}
				?>
			</p>
			<?

			if ($overview->efficiencyValue >= 0)
			{
				// use --success when >75% or --failure
				$state = $overview->efficiencyValue > 75 ? '--success' : '--failure';
				$stateShort = match ($overview->efficiencyValue)
				{
					50 => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_50'),
					75 => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_75'),
					100 => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_100'),
					default => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_0'),
				};
				if ($overview->isExceptionMeeting)
				{
					$recommendation = Loc::getMessage('CALL_COMPONENT_EXCEPTION_MEETING', [
						'#MEETING_TYPE#' => $overview->meetingDetails?->type ?? Loc::getMessage('CALL_COMPONENT_EXCEPTION_MEETING_DAILY')
					]);
				}
				else
				{
					$recommendation = match ($overview->efficiencyValue)
					{
						75 => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_RECOMMENDATIONS_75'),
						100 => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_RECOMMENDATIONS_100'),
						default => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_RECOMMENDATIONS_0'),
					};
				}
				?>
				<div class="bx-call-component-call-ai__resume-banner bx-call-component-call-ai-resume-banner <?= $state ?>">
					<div class="bx-call-component-call-ai-resume-banner__icon <?= $state ?>"></div>
					<div class="bx-call-component-call-ai-resume-banner__result">
						<div class="bx-call-component-call-ai-resume-banner__result-title"><?= Loc::getMessage('CALL_COMPONENT_EFFICIENCY') ?></div>
						<div class="bx-call-component-call-ai-resume-banner__result-description"><?= $stateShort ?></div>
					</div>
					<div class="bx-call-component-call-ai-resume-banner__grade">
						<span class="bx-call-component-call-ai-resume-banner__grade-value"><?= $overview->efficiencyValue ?></span>
						<span class="bx-call-component-call-ai-resume-banner__grade-symbol">%</span>
					</div>
				</div>

				<div class="bx-call-component-call-ai__resume-popup bx-call-component-call-ai-resume-popup">
					<h4 class="bx-call-component-call-ai-resume-popup__title"><?= Loc::getMessage('CALL_COMPONENT_EFFICIENCY_RECOMMENDATIONS') ?></h4>
					<span class="bx-call-component-call-ai-resume-popup__comment"><?= $recommendation ?></span>

					<ul class="bx-call-component-call-ai-resume-popup__list">
						<?

						// #1
						$disable = '';
						if ($overview->isExceptionMeeting)
						{
							//$disable = (bool)$overview->efficiency?->agenda_clearly_stated?->value ? '' : '--disable';
							$disable = '--disable';
						}
						$state = (bool)$overview->efficiency?->agenda_clearly_stated?->value ? '--success' : '--failure';
						?>
						<li class="bx-call-component-call-ai-resume-popup__list-item <?= $disable?>">
							<span class="bx-call-component-call-ai-resume-popup__list-item-icon <?= $state ?>"></span>
							<?= Loc::getMessage('CALL_COMPONENT_EFFICIENCY_AGENDA_CLEARLY') ?>
						</li>
						<?

						// #2
						$state = (bool)$overview->efficiency?->agenda_items_covered?->value ? '--success' : '--failure';
						?>
						<li class="bx-call-component-call-ai-resume-popup__list-item">
							<span class="bx-call-component-call-ai-resume-popup__list-item-icon <?= $state ?>"></span>
							<?= Loc::getMessage('CALL_COMPONENT_EFFICIENCY_AGENDA_COVERED_V2') ?>
						</li>
						<?

						// #3
						$disable = '';
						if ($overview->isExceptionMeeting)
						{
							//$disable = (bool)$overview->efficiency?->conclusions_and_actions_outlined?->value ? '' : '--disable';
							$disable = '--disable';
						}
						$state = (bool)$overview->efficiency?->conclusions_and_actions_outlined?->value ? '--success' : '--failure';
						?>
						<li class="bx-call-component-call-ai-resume-popup__list-item <?= $disable?>">
							<span class="bx-call-component-call-ai-resume-popup__list-item-icon <?= $state ?>"></span>
							<?= Loc::getMessage('CALL_COMPONENT_EFFICIENCY_AGENDA_CONCLUSIONS') ?>
						</li>

						<?
						// #4
						$state = '--success';
						if ($overview?->calendar)
						{
							$state = $overview->calendar->overhead ? '--failure' : '--success';
						}
						?>
						<li class="bx-call-component-call-ai-resume-popup__list-item">
							<span class="bx-call-component-call-ai-resume-popup__list-item-icon <?= $state ?>"></span>
							<?= Loc::getMessage('CALL_COMPONENT_EFFICIENCY_AGENDA_TIME_EXCEED') ?>
						</li>
					</ul>
				</div>
				<?
			}
			?>
			<div class="bx-call-component-call-ai__disclaimer">
				<?= Loc::getMessage('CALL_COMPONENT_COPILOT_DISCLAIMER', [
					'#LINK_START#' => '<span class="bx-call-component-call-ai__disclaimer-link">',
					'#LINK_END#' => '</span>'
				]) ?>
			</div>
		</div>
	</div>

	<div class="bx-call-component-call-ai__tabs-container">
		<div class="bx-call-component-call-ai__tabs-header">
			<button class="bx-call-component-call-ai__tab-header-button" data-tab-id="TabAgreements" data-tab-name="notes"><?= Loc::getMessage('CALL_COMPONENT_AGREEMENTS') ?></button>
			<button class="bx-call-component-call-ai__tab-header-button" data-tab-id="TabRecommendations" data-tab-name="ai_call_quality"><?= Loc::getMessage('CALL_COMPONENT_INSIGHTS_V2') ?></button>
			<button class="bx-call-component-call-ai__tab-header-button" data-tab-id="TabSummary" data-tab-name="followup"><?= Loc::getMessage('CALL_COMPONENT_SUMMARY') ?></button>
			<button class="bx-call-component-call-ai__tab-header-button" data-tab-id="TabTranscriptions" data-tab-name="transcript"><?= Loc::getMessage('CALL_COMPONENT_TRANSCRIPTIONS') ?></button>
		</div>

		<div class="bx-call-component-call-ai__tab-content-wrapper">
			<div id="TabAgreements" class="bx-call-component-call-ai__tab-content">
			<?
			if ($overview?->agreements || $overview?->meetings || $overview?->tasks)
			{
				if ($overview?->agreements)
				{
					?>
				<div class="bx-call-component-call-ai__recommendations__title"><?= Loc::getMessage('CALL_COMPONENT_AGREEMENTS_COMMON') ?></div>
					<ol class="bx-call-component-call-ai__result-list">
					<?
					foreach ($overview->agreements as $row)
					{
						?>
						<li class="bx-call-component-call-ai__result-list-item">
							<?= $row->agreement ?>
						</li>
						<?
					}
					?>
					</ol>
					<?
				}
				if ($overview?->tasks)
				{
					?>
				<div class="bx-call-component-call-ai__recommendations__title"><?= Loc::getMessage('CALL_COMPONENT_AGREEMENTS_TASKS') ?></div>
					<ol class="bx-call-component-call-ai__result-list">
					<?
					foreach ($overview->tasks as $row)
					{
						?>
						<li class="bx-call-component-call-ai__result-list-item">
							<p class="bx-call-component-call-ai__task-description">
								<?= $row->task ?>
							</p>
							<span
								class="bx-call-component-call-ai__task-button"
								data-user-id="<?= $arResult['CURRENT_USER_ID'] ?>"
								data-description="<?= htmlspecialcharsbx($row->taskMentionLess) ?>"
								data-auditors="">
									<?= Loc::getMessage('CALL_COMPONENT_TASK_CREATE') ?>
								</span>
						</li>
						<?
					}
					?>
					</ol>
					<?
				}
				if ($overview?->meetings)
				{
					?>
					<div class="bx-call-component-call-ai__recommendations__title"><?= Loc::getMessage('CALL_COMPONENT_AGREEMENTS_MEETINGS') ?></div>
					<ol class="bx-call-component-call-ai__result-list">
					<?
					foreach ($overview->meetings as $row)
					{
						?>
						<li class="bx-call-component-call-ai__result-list-item">
							<p class="bx-call-component-call-ai__meetings-description">
								<?= $row->meeting ?>
							</p>
							<span
								class="bx-call-component-call-ai__meetings-button"
								data-meeting-id=""
								data-meeting-type=""
								data-meeting-description="<?= htmlspecialcharsbx($row->meetingMentionLess) ?>">
									<?= Loc::getMessage('CALL_COMPONENT_MEETING_CREATE') ?>
								</span>
						</li>
						<?
					}
					?>
					</ol>
					<?
				}
			}
			else
			{
				?><div class="bx-call-component-call-ai__result-empty-state"><?= Loc::getMessage('CALL_COMPONENT_EMPTY_AGREEMENTS'); ?></div><?
			}

			?>
			</div>
			<div id="TabRecommendations" class="bx-call-component-call-ai__tab-content">
			<?

			if ($insights?->insights)
			{
				foreach ($insights->insights as $row)
				{
					?>
					<p class="bx-call-component-call-ai__recommendations">
						<?= $row->detailed_insight ?>
					</p>
					<?
				}
			}
			else
			{
				?><div class="bx-call-component-call-ai__result-empty-state"><?= Loc::getMessage('CALL_COMPONENT_EMPTY_INSIGHTS'); ?></div><?
			}
			?>
			</div>
			<div id="TabSummary" class="bx-call-component-call-ai__tab-content">
			<?

			if ($summary?->summary)
			{
				foreach ($summary->summary as $row)
				{
					?>
					<div class="bx-call-component-call-ai-resume-block">
						<div class="bx-call-component-call-ai-resume-block__title">
							<span class="bx-call-component-call-ai-resume-block__time"><?= $row->start ?>—<?= $row->end ?></span>
							<span class="bx-call-component-call-ai-resume-block__name"><?= $row->title ?></span>
						</div>
						<p class="bx-call-component-call-ai-resume-block__description">
							<?= $row->summary ?>
						</p>
					</div>
					<?
				}
			}
			else
			{
				?><div class="bx-call-component-call-ai__result-empty-state"><?= Loc::getMessage('CALL_COMPONENT_EMPTY_SUMMARY'); ?></div><?
			}

			?>
			</div>
			<div id="TabTranscriptions" class="bx-call-component-call-ai__tab-content">
			<?

			if ($transcribe?->transcriptions)
			{
				if (!empty($track['REL_URL']))
				{
					?>
					<div class="bx-call-component-call-ai__call-audio-record" data-audio-src="<?= $track['REL_URL'] ?>"></div>
					<?
				}
				foreach ($transcribe->transcriptions as $row)
				{
					?>
					<div class="bx-call-component-call-ai-decryption-block">
						<p class="bx-call-component-call-ai-decryption-block__description">
							<span class="bx-call-component-call-ai-decryption-block__time"><?= $row->start ?>—<?= $row->end ?></span>
							<span class="bx-call-component-call-ai-decryption-block__name"><?= $row->user ?>:</span>
							<?= $row->text ?>
						</p>
					</div>
					<?
				}
			}
			else
			{
				?><div class="bx-call-component-call-ai__result-empty-state"><?= Loc::getMessage('CALL_COMPONENT_EMPTY_TRANSCRIPTIONS'); ?></div><?
			}

			?>
			</div>
		</div>
	</div>
</div>
<?

