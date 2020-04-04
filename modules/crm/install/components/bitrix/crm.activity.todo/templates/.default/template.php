<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

if ($arParams['IS_AJAX'] == 'Y')
{
	echo '<link rel="stylesheet" type="text/css" href="', $this->getFolder(), '/style.css?5" />';
	echo '<script type="text/javascript" src="', $this->getFolder(), '/script.js?v13"></script>';
}
?>

<script type="text/javascript">
	BX.message({
		CRM_ACTIVITY_TODO_VIEW_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('CRM_ACTIVITY_TODO_VIEW_TITLE'));?>',
		CRM_ACTIVITY_TODO_CLOSE: '<?= CUtil::JSEscape(Loc::getMessage('CRM_ACTIVITY_TODO_CLOSE'));?>'
	});
</script>

<div id="crm-activity-todo-items" class="crm-activity-todo-items">
<?foreach ($arResult['ITEMS'] as $item):
	if ($item['DETAIL_EXIST'])
	{
		$uriView = new \Bitrix\Main\Web\Uri('/bitrix/components/bitrix/crm.activity.planner/slider.php');
		$uriView->addParams(array(
			'site_id' => SITE_ID,
			'sessid' => bitrix_sessid_get(),
			'ajax_action' => 'ACTIVITY_VIEW',
			'activity_id' => $item['ID']
		));
	}
	?>
<div class="crm-activity-todo-item<?= $item['COMPLETED']=='Y' ? ' crm-activity-todo-item-completed' : ''?>"<?
	?> data-id="<?= $item['ID']?>"<?
	?> data-ownerid="<?= $item['OWNER_ID']?>"<?
	?> data-ownertypeid="<?= $item['OWNER_TYPE_ID']?>"<?
	?> data-deadlined="<?= $item['DEADLINED']?>"<?
	?> data-associatedid="<?= isset($item['ASSOCIATED_ENTITY_ID']) ? $item['ASSOCIATED_ENTITY_ID'] : 0?>"<?
	?> data-icon="<?= $item['ICON']?>">
		<div class="crm-activity-todo-item-left">
			<input type="checkbox" id="check<?= $item['ID']?>" value="1" class="crm-activity-todo-check"<?= $item['COMPLETED']=='Y' ? ' checked="checked" disabled="disabled"' : ''?> />
		</div>
		<label class="crm-activity-todo-item-middle" for="check<?= $item['ID']?>">
			<?if (isset($item['DEADLINE']) && $item['DEADLINE'] != ''):?>
			<div class="crm-activity-todo-date<?= $item['HIGH']=='Y' ? ' crm-activity-todo-date-alert' : ''?>"<?= $item['DEADLINED'] ? ' style="color: red"' : ''?> <?
				?>title="<?= Loc::getMessage('CRM_ACTIVITY_TODO_DEADLINE')?><?= $item['HIGH']=='Y' ? ' '.Loc::getMessage('CRM_ACTIVITY_TODO_HOT') : ''?>">
				<?= $item['DEADLINE']?>
			</div>
			<?elseif (isset($item['START_TIME']) && $item['START_TIME'] != ''):?>
			<div class="crm-activity-todo-date<?= $item['HIGH']=='Y' ? ' crm-activity-todo-date-alert' : ''?>">
				<?= $item['START_TIME']?>
			</div>
			<?endif;?>
			<?if ($item['DETAIL_EXIST']):?>
				<a href="<?= $uriView->getUri();?>" data-id="<?= $item['ID']?>" class="crm-activity-todo-link">
					<span class="crm-activity-todo-link-txt"><?= $item['SUBJECT']?></span>
					<span class="crm-activity-todo-link-num"><?= $item['DEADLINED'] ? ' <span>1</span>' : ''?><? ?></span>
				</a>
			<?else:?>
				<span data-id="<?= $item['ID']?>" class="crm-activity-todo-link"><?= $item['SUBJECT']?></span>
			<?endif;?>
			<?if (!empty($item['CONTACTS'])):?>
			<div class="crm-activity-todo-info">
				<?= Loc::getMessage('CRM_ACTIVITY_TODO_CONTACT')?>:
				<?foreach ($item['CONTACTS'] as $contact):?>
					<a href="<?= $contact['URL']?>"><?= $contact['TITLE']?></a>
				<?endforeach;?>
			</div>
			<?endif;?>
		</label>
		<?if ($item['ICON'] == 'no'):?>
		<div class="crm-activity-todo-item-right-nopadding">
			<div class="crm-activity-todo-event crm-activity-todo-event-no">
			</div>
		</div>
		<?else:?>
		<div class="crm-activity-todo-item-right-nopadding<?if (!empty($item['CONTACTS'])):?> crm-activity-todo-item-right<?endif;?>">
			<div class="crm-activity-todo-event crm-activity-todo-event-<?= $item['ICON']?>" title="<?= $item['PROVIDER_TITLE']!='' ? $item['PROVIDER_TITLE'] : $item['TYPE_NAME']?>">
			<?if (!empty($item['PROVIDER_ANCHOR'])):?>
				<?= $item['PROVIDER_TITLE']!='' ? $item['PROVIDER_TITLE'] : $item['TYPE_NAME']?>
				<?if (isset($item['PROVIDER_ANCHOR']['HTML']) && !empty($item['PROVIDER_ANCHOR']['HTML'])):?>
					<br/>
					<?= $item['PROVIDER_ANCHOR']['HTML']?>
				<?elseif (false && isset($item['PROVIDER_ANCHOR']['TEXT']) && !empty($item['PROVIDER_ANCHOR']['URL'])):?>
					<a href="<?= $item['PROVIDER_ANCHOR']['URL']?>"><?= $item['PROVIDER_ANCHOR']['TEXT']?></a>
				<?endif;?>
			<?else:?>
				<?= $item['PROVIDER_TITLE']!='' ? $item['PROVIDER_TITLE'] : $item['TYPE_NAME']?>
			<?endif;?>
			</div>
		</div>
		<?endif;?>
</div>
<?endforeach;?>
</div>

<script type="text/javascript">
	BX.CrmActivityTodo.create({
		container: 'crm-activity-todo-items'
	});
</script>
