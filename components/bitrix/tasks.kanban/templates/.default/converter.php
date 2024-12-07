<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>

<div id="tasks-kanban-converter" style="display: none;">
	<?= Loc::getMessage('TASKS_KANBAN_CONVERTER_DIALOG_TEXT', array(
			'#PROCESSED#' => '<span id="tasks-kanban-cp">0</span>',
			'#TOTAL#' => '<span id="tasks-kanban-ct">' . $arResult['MP_CONVERTER'] . '</span>'
		))?>
</div>

<script>
	BX.ready(function(){

		var mpConverterSuccess = false;
		var mpConverter = function(last)
		{
			if (typeof Kanban !== "undefined")
			{

				Kanban.ajax('converterMP', {
					last: last
				}).then(
					(response) => {
						const data = response.data;
						const error = response.errors.pop();

						if (!error)
						{
							if (data.finish !== true)
							{
								BX("tasks-kanban-cp").innerHTML =
									parseInt(BX("tasks-kanban-cp").innerHTML) + data.processed;
								mpConverter(data.last);
							}
							else
							{
								mpConverterSuccess = true;
								BX.PopupWindowManager.getCurrentPopup().close();
								window.location.href = window.location.href;
							}
						}
						else
						{
							BX.Kanban.Utils.showErrorDialog(error.message, true);
						}
					}
				);
			}
		};

		mpConverter(0);

		(BX.PopupWindowManager.create(
			"tasks-kanban-converter-dialog",
			null,
			{
				titleBar: "<?= \CUtil::JSEscape(Loc::getMessage('TASKS_KANBAN_CONVERTER_DIALOG_TITLE'))?>",
				content: BX("tasks-kanban-converter"),
				autoHide: false,
				overlay: {
					opacity: 50
				},
				width: 400,
				closeByEsc : true,
				closeIcon : true,
				contentColor: "white",
				draggable : { restrict : true},
				buttons: [
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
			}
		)).show();
	});
</script>