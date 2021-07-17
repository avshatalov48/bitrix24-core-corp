;(function ()
{
	'use strict';
	BX.namespace('BX.CrmStart.Onec');

	BX.CrmStart.Onec = {
		initTile: function (params) {
			this.tileManagerId = params.tileManagerId;
			this.tileManager = BX.UI.TileList.Manager.getById(this.tileManagerId);

			BX.addCustomEvent(
				this.tileManager,
				this.tileManager.events.tileClick,
				function (tile) {
					BX.SidePanel.Instance.open(tile.data.url/*, {width: 735, cacheable: false}*/);
				}
			);

			this.synchroTileManagerId = params.synchroTileManagerId;
			this.synchrotileManager = BX.UI.TileList.Manager.getById(this.synchroTileManagerId);

			BX.addCustomEvent(
				this.synchrotileManager,
				this.synchrotileManager.events.tileClick,
				function (tile) {
					BX.SidePanel.Instance.open(tile.data.url/*, {width: 735, cacheable: false}*/);
				}
			);

			this.integrationTileManagerId = params.integrationTileManagerId;
			this.integrationtileManager = BX.UI.TileList.Manager.getById(this.integrationTileManagerId);

			if (this.integrationtileManager)
			{
				BX.addCustomEvent(
					this.integrationtileManager,
					this.integrationtileManager.events.tileClick,
					function (tile) {
						BX.SidePanel.Instance.open(tile.data.url/*, {width: 735, cacheable: false}*/);
					}
				);
			}
		}
	};

})();

function BXOneCStart(type)
{
	if (type === "doc")
	{
		var app_url = '/marketplace/detail/bitrix.1cdoc/';
	}
	else if (type === "backoffice")
	{
		var app_url = '/marketplace/detail/bitrix.1ctotal/';
	}
	else
	{
		var app_url = '/marketplace/detail/bitrix.1c/';
	}

    BX.ready(function () {
		if (BX.type.isDomNode(BX('b24-integration-active-button')))
		{
			BX.bind(BX('b24-integration-active-button'), 'click', function () {
				_BXOneCStart();
			});
		}
    });

	function _BXOneCStart()
	{
		if(window.ONEC_APP_INACTIVE)
		{
			BX.SidePanel.Instance.open(app_url);
		}
		else if(typeof window.LICENCE_RESTRICTED !== 'undefined' && window.LICENCE_RESTRICTED)
		{
			B24.licenseInfoPopup.show('onec-face-card-block', BX.message('CRM_1C_START_FACE_CARD_B24_BLOCK_TITLE2'), BX.message('CRM_1C_START_FACE_CARD_B24_BLOCK_TEXT2'));
		}
		else if(typeof window.LICENCE_ACCEPTED !== 'undefined' && window.LICENCE_ACCEPTED === false)
		{
			var licensePopup = new BX.PopupWindow('1c_license_popup' + (new Date()).getTime(), null, {
				autoHide: false,
				closeIcon: true,
				closeByEsc: true,
				titleBar: BX.message('CRM_1C_START_FACE_CARD_CONSENT_TITLE'),
				content: BX.create('div', {style: {'max-width': '595px'}, html: BX.message('CRM_1C_START_FACE_CARD_CONSENT_AGREEMENT')}),
				overlay: {
					opacity: 50
				},
				buttons:[
					new BX.PopupWindowButton({
						text: BX.message('CRM_1C_START_FACE_CARD_CONSENT_AGREED'),
						className: 'popup-window-button-accept',
						events: {
							click: function()
							{
								this.popupWindow.close();
								BX.ajax({
									url: window.ONEC_AJAX_URL,
									method: 'POST',
									dataType: 'json',
									data: {
										action: 'acceptAgreement',
										sessid: BX.bitrix_sessid()
									},
									onsuccess: function(){
									   appLayoutShow();
									}
								});

							}
						}
					})
				]
			});

			licensePopup.show();
		}
		else
		{
			appLayoutShow();
		}
	}

	function appLayoutShow()
	{
		BX.toggleClass(BX('b24-integration-active'), 'b24-integration-wrap-animate');
		BX.rest.AppLayout.initialize('DEFAULT', window.ONEC_APP_SID);
	}
}