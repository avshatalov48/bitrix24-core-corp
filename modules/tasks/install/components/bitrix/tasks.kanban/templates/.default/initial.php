<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>

<div id="tasks-kanban-views" style="display: none;">
	<form action="<?= POST_FORM_ACTION_URI?>" method="post" id="tasks-kanban-views-form">
		<?= bitrix_sessid_post()?>
		<select name="set_view_id" class="content-edit-form-field-input-select" style="width: 250px;" id="tasks-kanban-views-select">
			<option value="0"><?= Loc::getMessage('TASKS_KANBAN_VIEWS_DIALOG_DEFAULT')?></option>
			<?foreach ($arResult['VIEWS'] as $id => $view):?>
			<option value="<?= $id?>"><?= \htmlspecialcharsbx($view['NAME'])?></option>
			<?endforeach;?>
		</select>
	</form>
</div>

<script type="text/javascript">
	BX.ready(function(){

		BX.bind(BX("tasks-kanban-views-select"), "change", BX.delegate(function()
		{
			if (typeof(Kanban) !== "undefined")
			{
				Kanban.changeDemoView(BX.proxy_context.value);
			}
		}, this));

		(BX.PopupWindowManager.create(
			"tasks-kanban-views-dialog",
			null,
			{
				titleBar: "<?= \CUtil::JSEscape(Loc::getMessage('TASKS_KANBAN_VIEWS_DIALOG'))?>",
				content: BX("tasks-kanban-views"),
				autoHide: false,
				overlay: {
					opacity: 20
				},
				closeByEsc : true,
				closeIcon : true,
				contentColor: "white",
				draggable : { restrict : true},
				buttons: [
					new BX.PopupWindowButton({
						text: "<?= \CUtil::JSEscape(Loc::getMessage('TASKS_COMMON_SELECT'))?>",
						className: "popup-window-button-accept",
						events: {
							click: function()
							{
								BX("tasks-kanban-views-form").submit();
							}
						}
					}),
					new BX.PopupWindowButton({
						text: "<?= \CUtil::JSEscape(Loc::getMessage('TASKS_COMMON_CANCEL'))?>",
						className: "popup-window-button-cancel",
						events: {
							click: function()
							{
								this.popupWindow.close();
							}
						}
					}),
				],
				events: {
					onPopupClose: function()
					{
						var listUrl = window.location.href;

						if (listUrl.indexOf("?") !== -1)
						{
							listUrl = window.location.href.split("?")[0];
						}

						listUrl += "?F_STATE=sV80";

						window.location.href = listUrl;
					}
				}
			}
		)).show();
	});
</script>