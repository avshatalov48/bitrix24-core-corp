const showPopup = function ()
{
	const nodes = document.querySelectorAll('#kanban-popup');
	const kanbanNode = Array.from(nodes).find(node => node.className === 'tasks-kanban-popup');
	var ajaxPath = BX.data(kanbanNode, "ajax");
	var kanbanType = BX.data(kanbanNode, "type");

	var closeHandler = BX.delegate(function()
	{
		BX.PopupWindowManager.getCurrentPopup().close();
		BX.ajax({
			method: "POST",
			dataType: "json",
			url: ajaxPath,
			data: {
				type: kanbanType
			}
		});
	}, this);

	BX.PopupWindowManager.create("kanban-popup", null, {
		content: kanbanNode,
		closeIcon: false,
		lightShadow: true,
		offsetLeft: 100,
		overlay: true,
		buttons: [
			new BX.PopupWindowButton({
				text: BX.data(kanbanNode, "close"),
				className: "webform-button webform-button-blue",
				events: {
					click: closeHandler
				}
			})
		]
	}).show();

	BX.bind(BX("kanban-readmore"), "click", BX.delegate(function(e)
	{
		var helpId = BX.data(BX.proxy_context, "helpId");
		BX.Helper.show("redirect=detail&HD_ID=" + helpId);
		closeHandler();
		BX.PreventDefault(e);
	}, this));
};
