(function() {

	"use strict";

	/**
	 * @namespace BX.Disk.Viewer
	 */
	BX.namespace("BX.Disk.Viewer");


	/**
	 * @extends {BX.UI.Viewer.Item}
	 * @param options
	 * @constructor
	 */
	BX.Disk.Viewer.OnlyOfficeItem = function (options)
	{
		options = options || {};

		BX.UI.Viewer.Item.apply(this, arguments);
		this.objectId = options.objectId;
		this.attachedObjectId = options.attachedObjectId;
		this.versionId = options.versionId;
		this.openEditInsteadPreview = options.openEditInsteadPreview;
	};

	BX.Disk.Viewer.OnlyOfficeItem.prototype =
	{
		__proto__: BX.UI.Viewer.Item.prototype,
		constructor: BX.UI.Viewer.Item,

		setController: function (controller)
		{
			BX.UI.Viewer.Item.prototype.setController.apply(this, arguments);

			this.controller.preload = 0;
		},

		/**
		 * @param {HTMLElement} node
		 */
		setPropertiesByNode: function (node)
		{
			BX.UI.Viewer.Item.prototype.setPropertiesByNode.apply(this, arguments);
			this.objectId = node.dataset.objectId;
			this.attachedObjectId = node.dataset.attachedObjectId;
			this.versionId = node.dataset.versionId;
			this.openEditInsteadPreview = node.dataset.openEditInsteadPreview;
		},

		loadData: function ()
		{
			var uid = BX.util.getRandomString(16);
			BX.Disk.sendTelemetryEvent({
				action: 'start',
				uid: uid
			});

			var hasPromoPopups = BX.getClass('BX.Disk.OnlyOfficePromo.PromoPopup');
			if (hasPromoPopups)
			{
				if (BX.Disk.OnlyOfficePromo.PromoPopup.shouldBlockViewAndEdit())
				{
					BX.Disk.OnlyOfficePromo.PromoPopup.showCommonPromoForNonPaid();

					return new BX.Promise();
				}
			}

			BX.SidePanel.Instance.open(BX.util.add_url_param('/bitrix/services/main/ajax.php', this.getSliderQueryParameters()), {
				width: '100%',
				cacheable: false,
				customLeftBoundary: 30,
				allowChangeHistory: false,
				data: {
					documentEditor: true,
					uid: uid
				}
			});

			if (hasPromoPopups)
			{
				if (BX.Disk.OnlyOfficePromo.PromoPopup.shouldShowViewPromo())
				{
					BX.Disk.OnlyOfficePromo.PromoPopup.showViewPromo();
				}
			}

			return new BX.Promise();
		},

		getSliderQueryParameters: function()
		{
			var action = 'disk.api.documentService.goToPreview';
			var hasPromoPopups = BX.getClass('BX.Disk.OnlyOfficePromo.PromoPopup')

			if (this.openEditInsteadPreview && BX.Disk.getDocumentService() === 'onlyoffice')
			{
				if (!hasPromoPopups || !BX.Disk.OnlyOfficePromo.PromoPopup.shouldShowEditPromo())
				{
					action = 'disk.api.documentService.goToEditOrPreview';
				}
			}

			return {
				action: action,
				serviceCode: 'onlyoffice',
				objectId: this.objectId || 0,
				attachedObjectId: this.attachedObjectId || 0,
				versionId: this.versionId || 0
			}
		},
	};

})();
