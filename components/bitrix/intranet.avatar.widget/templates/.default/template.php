<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die;
}

/**
 * @var array $arResult
 */

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Extension::load([
	'intranet.widget-loader',
	'timeman.work-time-state-icon',
]);

$style = '';
$hasMeridiem = IsAmPmMode();
$workTimeClass = $arResult['workTimeData']['workTimeClass'] ?? '';

if (!empty($arResult['avatar']))
{
	$style = "background: url('{$arResult['avatar']}') no-repeat center; background-size: cover;";
}

$this->setFrameMode(true);
?>

<div data-testid="user-id-<?= (int)$arResult['userId'] ?>" class="air-user-profile --no-transition <?= $workTimeClass ?>" data-id="bx-avatar-widget">
	<div class="air-user-profile__wrapper">
		<div class="air-user-profile-avatar__work-time-state">
			<i class="ui-icon-set air-user-profile-avatar__work-time-icon"></i>
		</div>
		<div class="air-user-profile-avatar__time">
			<?php if ($hasMeridiem): ?>
				<span class="air-user-profile-avatar__time-meridiem"></span>
			<?php endif; ?>
		</div>
	</div>
	<div class="air-user-profile__avatar --ui-hoverable-alt ui-icon-common-user <?= '--' . $arResult['userRole'] ?>">
		<i style="<?= htmlspecialcharsbx($style) ?>"></i>
		<div class="air-user-profile-avatar__counter"></div>
		<div class="air-user-profile-avatar__work-time-state-short"></div>
	</div>
</div>
<script>
	BX.ready(() => {
		const avatarWrapper = document.querySelector('[data-id="bx-avatar-widget"]');
		const updateTime = () => {
			const timeElement = document.querySelector('.air-user-profile-avatar__time');
			const meridiemElement = document.querySelector('.air-user-profile-avatar__time-meridiem');
			const now = new Date();

			if (<?= $hasMeridiem ? 'true' : 'false' ?>)
			{
				const hours = now.getHours() % 12 || 12;
				const minutes = now.getMinutes().toString().padStart(2, '0');
				const meridiem = now.getHours() >= 12 ? 'PM' : 'AM';

				timeElement.childNodes[0].textContent = `${hours}:${minutes} `;

				if (meridiemElement)
				{
					meridiemElement.textContent = meridiem;
				}
			}
			else
			{
				const hours = now.getHours().toString().padStart(2, '0');
				const minutes = now.getMinutes().toString().padStart(2, '0');
				timeElement.textContent = `${hours}:${minutes}`;
			}
		};
		const handleScroll = () => {
			if (window.scrollY > 20 && !BX.Dom.hasClass(avatarWrapper, '--collapsed'))
			{
				BX.Dom.addClass(avatarWrapper, '--collapsed');
			}
			else if (window.scrollY <= 20)
			{
				BX.Dom.removeClass(avatarWrapper, '--collapsed');
			}
		};
		handleScroll();
		BX.Event.bind(window, 'scroll', handleScroll);
		BX.Event.EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', () => {
			if (BX.SidePanel.Instance.getOpenSlidersCount() === 0)
			{
				handleScroll();
			}
		});
		BX.Event.EventEmitter.subscribe('SidePanel.Slider:onOpen', () => {
			if (!BX.Dom.hasClass(avatarWrapper, '--collapsed'))
			{
				BX.Dom.addClass(avatarWrapper, '--collapsed');
			}
		});

		let timerInterval = null;
		let syncTimeout = null;

		const startTimer = () => {
			if (timerInterval)
			{
				clearInterval(timerInterval);
				timerInterval = null;
			}

			if (syncTimeout)
			{
				clearTimeout(syncTimeout);
				syncTimeout = null;
			}

			const syncToNextMinute = () => {
				const now = new Date();
				updateTime();

				const secondsUntilNextMinute = 60 - now.getSeconds();
				const msUntilNextMinute = secondsUntilNextMinute * 1000 - now.getMilliseconds() + 100;

				syncTimeout = setTimeout(() => {
					const newNow = new Date();
					if (newNow.getMinutes() !== now.getMinutes() || newNow.getHours() !== now.getHours())
					{
						updateTime();
					}

					timerInterval = setInterval(() => {
						updateTime();
					}, 60000);
				}, Math.max(100, msUntilNextMinute));
			};

			syncToNextMinute();
		};

		const handleVisibilityChange = () => {
			if (!document.hidden)
			{
				setTimeout(() => {
					startTimer();
				}, 100);
			}
		};

		document.addEventListener('visibilitychange', handleVisibilityChange);

		startTimer();
	});
</script>
<?php
$frame = $this->createFrame()->begin('');
?>
<script>
	BX.ready(() => {
		const avatarWrapper = document.querySelector('[data-id="bx-avatar-widget"]');
		BX.Dom.removeClass(avatarWrapper, '--no-transition');
		BX.Intranet.Bitrix24.AvatarButton.init({
			userId: <?= (int)$arResult['userId'] ?>,
			skeleton: <?= Json::encode($arResult['skeleton']) ?>,
			signDocumentsCounter: <?= (int)$arResult['signDocumentsCounter'] ?>,
			signDocumentsPullEventName: '<?= \CUtil::JSEscape($arResult['signDocumentsPullEventName']) ?>',
			workTimeAvailable: '<?= \CUtil::JSEscape($arResult['workTimeData']['workTimeAvailable']) ?>',
			workTimeState: '<?= \CUtil::JSEscape($arResult['workTimeData']['workTimeState']) ?>',
			workTimeAction: '<?= \CUtil::JSEscape($arResult['workTimeData']['workTimeAction']) ?>',
		});
	});
</script>
<?php
$frame->end();