<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Mail\Helper;

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');

$renderLog = function($log) use ($arResult)
{
	if (empty($arResult['LOG'][$log]))
		return;

	foreach ($arResult['LOG'][$log] as $item)
	{
		$datetimeFormat = \CIntranetUtils::getCurrentDatetimeFormat();
		$startDatetimeFormatted = \CComponentUtil::getDateTimeFormatted(
			makeTimeStamp($item['START_TIME']),
			$datetimeFormat,
			\CTimeZone::getOffset()
		);
		$readDatetimeFormatted = !empty($item['SETTINGS']['READ_CONFIRMED']) && $item['SETTINGS']['READ_CONFIRMED']
			? \CComponentUtil::getDateTimeFormatted(
				$item['SETTINGS']['READ_CONFIRMED']+\CTimeZone::getOffset(),
				$datetimeFormat,
				\CTimeZone::getOffset()
			) : null;
		?>
		<div class="crm-task-list-mail-item crm-activity-email-logitem-<?=intval($item['ID']) ?>"
			data-id="<?=intval($item['ID']) ?>" data-log="<?=htmlspecialcharsbx($log) ?>">
			<span class="crm-task-list-mail-item-icon-reply-<?=($item['DIRECTION'] == \CCrmActivityDirection::Incoming ? 'incoming' : 'coming') ?>"></span>
			<span class="crm-task-list-mail-item-icon <? if ($item['COMPLETED'] != 'Y'): ?>active-mail<? endif ?>"></span>
			<span class="crm-task-list-mail-item-user"
				<? if (!empty($item['LOG_IMAGE'])): ?> style="background: url('<?=htmlspecialcharsbx($item['LOG_IMAGE']) ?>'); background-size: 23px 23px; "<? endif ?>>
			</span>
			<span class="crm-task-list-mail-item-name"><?=htmlspecialcharsbx($item['LOG_TITLE']) ?></span>
			<span class="crm-task-list-mail-item-description"><?=htmlspecialcharsbx($item['SUBJECT']) ?></span>
			<span class="crm-task-list-mail-item-date crm-activity-email-item-date">
				<span class="crm-activity-email-item-date-short">
					<?=$startDatetimeFormatted ?>
				</span>
				<span class="crm-activity-email-item-date-full">
					<? if (\CCrmActivityDirection::Outgoing == $item['DIRECTION']): ?>
						<?=getMessage('CRM_ACT_EMAIL_VIEW_SENT', array('#DATETIME#' => $startDatetimeFormatted)) ?><!--
						--><? if ($item['__trackable']): ?>,
							<? if (!empty($readDatetimeFormatted)): ?>
								<?=getMessage('CRM_ACT_EMAIL_VIEW_READ_CONFIRMED', array('#DATETIME#' => $readDatetimeFormatted)) ?>
							<? else: ?>
								<?=getMessage('CRM_ACT_EMAIL_VIEW_READ_AWAITING') ?>
							<? endif ?>
						<? endif ?>
					<? else: ?>
						<?=getMessage('CRM_ACT_EMAIL_VIEW_RECEIVED', array('#DATETIME#' => $startDatetimeFormatted)) ?>
					<? endif ?>
				</span>
			</span>
		</div>
		<div class="crm-task-list-mail-item-inner crm-task-list-mail-item-inner-slider crm-activity-email-details-<?=intval($item['ID']) ?>"
			style="display: none; text-align: center; " data-id="<?=intval($item['ID']) ?>" data-empty="1">
			<div class="crm-task-list-mail-item-loading crm-task-list-mail-border-bottom"></div>
		</div>
		<?
	}
};

$activity = $arParams['ACTIVITY'];

?>

<script type="text/javascript">

BX.ready(function ()
{
	BXCrmActivityEmailController.init({
		activityId: <?=intval($activity['ID']) ?>,
		mailMessageId: <?=intval($activity['UF_MAIL_MESSAGE']) ?>,
		ajaxUrl: '<?=$this->__component->getPath() ?>/ajax.php?site_id=<?=\CUtil::jsEscape(SITE_ID) ?>',
		pageSize: <?=intval($arParams['PAGE_SIZE']) ?>
	});
});

</script>

<div class="crm-task-list-inner">
	<div class="crm-task-list-mail crm-task-list-mail-slider">

		<div class="crm-task-list-mail-item-separator crm-task-list-mail-item-separator-slider"
			style="margin-bottom: 1px; <? if (count($arResult['LOG']['A']) < $arParams['PAGE_SIZE']): ?> display: none; <? endif ?>">
			<a class="crm-task-list-mail-more crm-task-list-mail-more-slider crm-task-list-mail-more-a" href="#"><?=getMessage('CRM_ACT_EMAIL_HISTORY_MORE') ?></a>
		</div>

		<? $renderLog('A'); ?>

		<div style="display: none; "></div>
		<div class="crm-task-list-mail-item-inner crm-task-list-mail-item-inner-slider"
			id="crm-activity-email-details-<?=intval($activity['ID']) ?>"
			data-id="<?=intval($activity['ID']) ?>">
			<? $APPLICATION->includeComponent(
				'bitrix:crm.activity.email.body', 'slider',
				array(
					'ACTIVITY'  => $activity,
					'TEMPLATES' => $arParams['TEMPLATES'],
				),
				false,
				array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
			); ?>
		</div>

		<? $renderLog('B'); ?>

		<div class="crm-task-list-mail-item-separator crm-task-list-mail-item-separator-slider"
			style="margin-top: 1px; <? if (count($arResult['LOG']['B']) < $arParams['PAGE_SIZE']): ?> display: none; <? endif ?>">
			<a class="crm-task-list-mail-more crm-task-list-mail-more-slider crm-task-list-mail-more-b" href="#"><?=getMessage('CRM_ACT_EMAIL_HISTORY_MORE') ?></a>
		</div>

	</div>
</div>

<?
	$APPLICATION->includeComponent('bitrix:main.mail.confirm', '', array());

	if(!Loader::includeModule("mail"))
	{
		echo getMessage('CRM_ACT_EMAIL_NO_MAIL');
		die();
	}
?>

<script type="text/javascript">

BX.message({
	CRM_ACT_EMAIL_REPLY_EMPTY_RCPT: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_REPLY_EMPTY_RCPT')) ?>',
	CRM_ACT_EMAIL_REPLY_UPLOADING: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_REPLY_UPLOADING')) ?>',
	CRM_ACT_EMAIL_MAX_SIZE: <?=Helper\Message::getMaxAttachedFilesSize();?>,
	CRM_ACT_EMAIL_MAX_SIZE_EXCEED: '<?=\CUtil::jsEscape(getMessage(
		'CRM_ACTIVITY_EMAIL_MAX_SIZE_EXCEED',
		['#SIZE#' => \CFile::formatSize(Helper\Message::getMaxAttachedFilesSizeAfterEncoding(),1)]
	)) ?>',
	CRM_ACT_EMAIL_CREATE_NOTEMPLATE: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_CREATE_NOTEMPLATE')) ?>',
	CRM_ACT_EMAIL_VIEW_READ_CONFIRMED_SHORT: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_VIEW_READ_CONFIRMED_SHORT')) ?>',
	CRM_ACT_EMAIL_DELETE_CONFIRM: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_DELETE_CONFIRM')) ?>',
	CRM_ACT_EMAIL_SKIP_CONFIRM: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_SKIP_CONFIRM')) ?>',
	CRM_ACT_EMAIL_SPAM_CONFIRM: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_SPAM_CONFIRM')) ?>'
});

BX.ready(function ()
{
	BXCrmActivityEmailController.scrollTo(BX('crm-activity-email-details-<?=intval($activity['ID']) ?>'));
});

</script>
