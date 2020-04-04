BX.namespace("BX.Disk");
BX.Disk.ExternalLinkListClass = (function ()
{
	var ExternalLinkListClass = function (parameters)
	{
		this.gridId = parameters.gridId;
		this.grid = BX.Main.gridManager.getById(this.gridId);

		this.ajaxUrl = '/bitrix/components/bitrix/disk.external.link.list/ajax.php';

		this.setEvents();
	};

	ExternalLinkListClass.prototype.setEvents = function()
	{
	};

	ExternalLinkListClass.prototype.removeRow = function(objectId)
	{
		this.grid.instance.removeRow(objectId);
	};

	ExternalLinkListClass.prototype.showExternalLink = function (externalId, objectId, link)
	{
		BX.Disk.modalWindow({
			modalId: 'bx-disk-external-link',
			title: BX.message("DISK_EXTERNAL_LINK_LIST_SHOW_LINK_WINDOW"),
			contentClassName: 'tac',
			contentStyle: {
			},
			events: {
				onAfterPopupShow: function () {
					var inputLink = BX('disk-get-external-link');
					BX.focus(inputLink);
					inputLink.setSelectionRange(0, inputLink.value.length)
				},
				onPopupClose: function () {
					this.destroy();
				}
			},
			content: [
				BX.create('label', {
					props: {
						className: 'bx-disk-popup-label',
						"for": 'disk-get-external-link'
					}
				}),
				BX.create('input', {
					style: {
						marginTop: '10px'
					},
					props: {
						id: 'disk-get-external-link',
						className: 'bx-viewer-inp',
						type: 'text',
						value: link
					}
				})
			],
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message("DISK_JS_BTN_CLOSE"),
					events: {
						click: function() {
							BX.PopupWindowManager.getCurrentPopup().close();
						}
					}
				})
			]
		});
	};

	ExternalLinkListClass.prototype.disableExternalLink = function (externalId, objectId)
	{
		BX.Disk.modalWindowLoader(BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'disableExternalLink'), {
			id: 'status-action',
			responseType: 'json',
			postData: {
				externalId: externalId,
				objectId: objectId
			},
			afterSuccessLoad: BX.delegate(function(response){

				if(!response || response.status != 'success')
				{
					BX.Disk.showModalWithStatusAction(response);
					return;
				}

				this.removeRow(externalId);
				BX.Disk.showModalWithStatusAction(response);

			}, this)
		});
	};

	return ExternalLinkListClass;
})();

