<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$renderLog = function($log) use ($arResult)
{
	if (empty($arResult['LOG'][$log]))
		return;

	foreach ($arResult['LOG'][$log] as $item)
	{
		?>
		<div class="crm-task-list-mail-item crm-activity-email-logitem-<?=intval($item['ID']) ?>"
			data-id="<?=intval($item['ID']) ?>" data-log="<?=htmlspecialcharsbx($log) ?>">
			<span class="crm-task-list-mail-item-icon-reply-<?=($item['DIRECTION'] == \CCrmActivityDirection::Incoming ? 'incoming' : 'coming') ?>"></span>
			<span class="crm-task-list-mail-item-icon <? if ($item['COMPLETED'] != 'Y'): ?>active-mail<? endif ?>"></span>
			<span class="crm-task-list-mail-item-user"
				<? if (!empty($item['LOG_IMAGE'])): ?>style="background: url('<?=htmlspecialcharsbx($item['LOG_IMAGE']) ?>'); background-size: 23px 23px; "<? endif ?>>
				</span>
			<span class="crm-task-list-mail-item-name"><?=htmlspecialcharsbx($item['LOG_TITLE']) ?></span>
			<span class="crm-task-list-mail-item-description"><?=htmlspecialcharsbx($item['SUBJECT']) ?></span>
			<span class="crm-task-list-mail-item-date"><?=formatDate('x', makeTimeStamp($item['START_TIME']), time()+\CTimeZone::getOffset()) ?></span>
		</div>
		<div class="crm-task-list-mail-item-inner crm-task-list-mail-border-bottom crm-activity-email-details-<?=intval($item['ID']) ?>"
			style="display: none; text-align: center; " data-empty="1">
			<div class="crm-task-list-mail-item-loading"></div>
		</div>
		<?
	}
};

$arParams['ACTIVITY']['DESCRIPTION_HTML'] = $arParams['~ACTIVITY']['DESCRIPTION_HTML'];

?>

<div class="crm-task-list-mail">

	<div class="crm-task-list-mail-item-separator"
		style="margin-bottom: 1px; <? if (count($arResult['LOG']['A']) < $arParams['PAGE_SIZE']): ?> display: none; <? endif ?>">
		<a class="crm-task-list-mail-more crm-task-list-mail-more-a" href="#"><?=getMessage('CRM_ACT_EMAIL_HISTORY_MORE') ?></a>
	</div>

	<? $renderLog('A'); ?>

	<div style="display: none; "></div>
	<div class="crm-task-list-mail-item-inner crm-task-list-mail-border-bottom" id="crm-activity-email-details-<?=intval($arParams['ACTIVITY']['ID']) ?>">
		<? $APPLICATION->includeComponent(
			'bitrix:crm.activity.email.body', '',
			array('ACTIVITY' => $arParams['ACTIVITY']),
			false,
			array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
		); ?>
	</div>

	<? $renderLog('B'); ?>

	<div class="crm-task-list-mail-item-separator"
		style="margin-top: 1px; <? if (count($arResult['LOG']['B']) < $arParams['PAGE_SIZE']): ?> display: none; <? endif ?>">
		<a class="crm-task-list-mail-more crm-task-list-mail-more-b" href="#"><?=getMessage('CRM_ACT_EMAIL_HISTORY_MORE') ?></a>
	</div>
</div>

<script type="text/javascript">

	BX.ready(function() {

		new CrmActivityEmailView(
			<?=intval($arParams['ACTIVITY']['ID']) ?>,
			{
				'ajaxUrl': '<?=$this->__component->getPath() ?>/ajax.php?site_id=<?=SITE_ID ?>',
				'pageSize': <?=intval($arParams['PAGE_SIZE']) ?>
			}
		);

	});

</script>
