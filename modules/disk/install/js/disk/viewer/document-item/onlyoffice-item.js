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
		},

		loadData: function ()
		{
			//dirty hack! we don't want to use ui.viewer at all when we are open office document
			this.controller.close();

			var backupProcessPreload = BX.UI.Viewer.Instance.processPreload;
			var backupUpdateControls = BX.UI.Viewer.Instance.updateControls;
			var backupLockScroll = BX.UI.Viewer.Instance.lockScroll;
			var backupAdjustViewerHeight = BX.UI.Viewer.Instance.adjustViewerHeight;
			var backupBindEvents = BX.UI.Viewer.Instance.bindEvents;

			BX.UI.Viewer.Instance.processPreload = function(){};
			BX.UI.Viewer.Instance.updateControls = function(){};
			BX.UI.Viewer.Instance.lockScroll = function(){};
			BX.UI.Viewer.Instance.adjustViewerHeight = function() {};
			BX.UI.Viewer.Instance.bindEvents = function() {
				this.close();

				BX.UI.Viewer.Instance.processPreload = backupProcessPreload;
				BX.UI.Viewer.Instance.updateControls = backupUpdateControls;
				BX.UI.Viewer.Instance.lockScroll = backupLockScroll;
				BX.UI.Viewer.Instance.adjustViewerHeight = backupAdjustViewerHeight;
				BX.UI.Viewer.Instance.bindEvents = backupBindEvents;

				setTimeout(function(){
					BX.UI.Viewer.Instance.close();

					BX.remove(BX.UI.Viewer.Instance.layout.container);
					BX.removeClass(BX.UI.Viewer.Instance.layout.container, 'ui-viewer-hide');
					BX.unbindAll(BX.UI.Viewer.Instance.layout.container);
					BX.UI.Viewer.Instance.actionPanel.hidePanel();
					BX.UI.Viewer.Instance.unLockScroll();
					BX.UI.Viewer.Instance.unbindEvents();
					BX.UI.Viewer.Instance.disableReadingMode();
					if (BX.UI.Viewer.Instance.isBodyPaddingAdded)
					{
						BX.UI.Viewer.Instance.removeBodyPadding();
					}
					window.dispatchEvent(new Event('resize'));

				}, 10);
			}

			BX.SidePanel.Instance.open(BX.util.add_url_param('/bitrix/services/main/ajax.php', this.getSliderQueryParameters()), {
				width: '100%',
				cacheable: false,
				customLeftBoundary: 30,
				allowChangeHistory: false,
				data: {
					documentEditor: true
				}
			});

			return new BX.Promise();
		},

		getSliderQueryParameters: function()
		{
			return {
				action: 'disk.api.documentService.goToPreview',
				serviceCode: 'onlyoffice',
				objectId: this.objectId || 0,
				attachedObjectId: this.attachedObjectId || 0,
				versionId: this.versionId || 0
			}
		},

		render: function ()
		{
			return document.createDocumentFragment();
		},

		/**
		 * @returns {BX.Promise}
		 */
		getContentWidth: function()
		{
			return new BX.Promise();
		},
	};

})();
