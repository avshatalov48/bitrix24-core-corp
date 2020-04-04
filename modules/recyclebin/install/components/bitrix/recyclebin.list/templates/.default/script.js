'use strict';

BX.namespace('Recyclebin');

(function() {

	BX.Recyclebin.List = function() {
	};

	BX.Recyclebin.List.reloadTable = function() {
		var gridObject = BX.Main.gridManager.getById(BX.Recyclebin.List.gridId);
		var reloadParams = { apply_filter: 'Y', clear_nav: 'Y' };
		if (gridObject.hasOwnProperty('instance'))
		{
			gridObject.instance.reloadTable('POST', reloadParams);
		}
	};

	BX.Recyclebin.List.restore = function(recyclebinId, type)
	{
		BX.ajax.runComponentAction('bitrix:recyclebin.list', 'restore', {
			mode: 'ajax',
			data: {
				recyclebinId: recyclebinId
			}
		}).then(function(response) {
			BX.UI.Notification.Center.notify({
				content: BX.message('RECYCLEBIN_NOTIFY_RESTORE_'+type)
			});

			BX.Recyclebin.List.reloadTable();

		}, function(response) {
			BX.UI.Notification.Center.notify({
				content: response.errors[0].message
			});
		});
	};

	BX.Recyclebin.List.remove = function(recyclebinId, type)
	{
		BX.Recyclebin.confirm(BX.message('RECYCLEBIN_CONFIRM_REMOVE')).then(function(code) {
			BX.ajax.runComponentAction('bitrix:recyclebin.list', 'remove', {
				mode: 'ajax',
				data: {
					recyclebinId: recyclebinId
				}
			}).then(function(response) {
				BX.UI.Notification.Center.notify({
					content: BX.message('RECYCLEBIN_NOTIFY_REMOVE_' + type)
				});
				BX.Recyclebin.List.reloadTable();
			}, function(response) {
				BX.UI.Notification.Center.notify({
					content: response.errors[0].message
				});
			});
		});
	};

	BX.Recyclebin.List.restoreBatch = function() {

		var gridObject = BX.Main.gridManager.getById(BX.Recyclebin.List.gridId).instance;
		var selectedRows = gridObject.getRows().getSelectedIds();

		var isAll = gridObject.getActionsPanel().getForAllCheckbox() != undefined &&
			gridObject.getActionsPanel().getForAllCheckbox().checked ? 1 : 0;

		BX.ajax.runComponentAction('bitrix:recyclebin.list', 'restore', {
			mode: 'ajax',
			data: {
				recyclebinId: selectedRows,
				isAll: isAll
			}
		}).then(function(response) {
			BX.UI.Notification.Center.notify({
				content: BX.message('RECYCLEBIN_RESTORE_SUCCESS_MULTIPLE')
			});
			BX.Recyclebin.List.reloadTable();
		}, function(response) {
			BX.UI.Notification.Center.notify({
				content: response.errors[0].message
			});
		});
	}

	BX.Recyclebin.List.removeBatch = function() {
		BX.Recyclebin.confirm(BX.message('RECYCLEBIN_CONFIRM_REMOVE')).then(function(code) {
			var gridObject = BX.Main.gridManager.getById(BX.Recyclebin.List.gridId).instance;
			var selectedRows = gridObject.getRows().getSelectedIds();

			var isAll = gridObject.getActionsPanel().getForAllCheckbox() != undefined &&
			gridObject.getActionsPanel().getForAllCheckbox().checked ? 1 : 0;

			BX.ajax.runComponentAction('bitrix:recyclebin.list', 'remove', {
				mode: 'ajax',
				data: {
					recyclebinId: selectedRows,
					isAll: isAll
				}
			}).then(function(response) {
				BX.UI.Notification.Center.notify({
					content: BX.message('RECYCLEBIN_DELETE_SUCCESS_MULTIPLE')
				});
				BX.Recyclebin.List.reloadTable();
			}, function(response) {
				BX.UI.Notification.Center.notify({
					content: response.errors[0].message
				});
			});
		});
	};

	BX.Recyclebin.confirm = function(body, callback, params)
	{
		if(!BX.type.isFunction(callback))
		{
			callback = BX.DoNothing;
		}

		params = params || {};
		params.ctx = params.ctx || this;

		var p = new BX.Promise(null, params.ctx);

		if(BX.Recyclebin.confirmPopup == null)
		{
			BX.Recyclebin.confirmPopup = new BX.PopupWindow(
				"recyclebin-confirm-popup",
				null,
				{
					zIndex : 22000,
					overlay : { opacity: 50 },
					content : '',
					autoHide   : false,
					closeByEsc : false
				}
			);
		}

		var disposable = params.isDisposable && params.id;
		var cb = null;
		var buttonSet = params.buttonSet || [
			{text: BX.message('JS_CORE_WINDOW_CONTINUE'), type: 'green', code: 'continue', default: true}
		];

		var buttons = [];
		buttonSet.forEach(function(button){

			(function(buttons, button, params, disposable, callback){
				buttons.push(new BX.PopupWindowButton({
					text: button.text,
					className: button.type == 'red' ? 'popup-window-button-decline' : 'popup-window-button-accept',
					events: {
						click: function () {

							callback.apply(params.ctx, [true]);
							this.popupWindow.close();

							if (disposable && BX('bx-recyclebin-disposable-' + params.id).checked) {
								BX.Tasks.Util.hintManager.disable(params.id);
							}

							p.fulfill(button.code);

							// delete(params);
						}
					}
				}));
			})(buttons, button, params, disposable, callback);
		});

		buttons.push(new BX.PopupWindowButtonLink({
			text: BX.message('JS_CORE_WINDOW_CANCEL'),
			events : {
				click : function(){
					callback.apply(params.ctx, [false]);
					this.popupWindow.close();

					p.reject();

					// delete(params);
				}
			}
		}));

		// each time "callback" variable will be different, so we can not cache buttons
		BX.Recyclebin.confirmPopup.setButtons(buttons);

		if(typeof params.title != 'undefined')
		{
			// this feature does not work due to limitations of PopupWindow
			BX.Recyclebin.confirmPopup.setTitleBar(BX.type.isElementNode(params.title) ? params.title : BX.create('div', {
				html: params.title
			}));
		}
		body = BX.create(
			'div',
			{
				style: {padding: '16px 12px', maxWidth: '400px', maxHeight: '400px', overflow: 'hidden'},
				html : BX.type.isElementNode(body) ? body.outerHTML : body.toString()
			}
		);
		if(cb)
		{
			BX.append(cb, body);
		}

		BX.Recyclebin.confirmPopup.setContent(body.outerHTML);
		BX.Recyclebin.confirmPopup.show();

		return p;
	};

}).call(this);

if (typeof(BX.FilterEntitySelector) === "undefined")
{
	BX.FilterEntitySelector = function() {
		this._id = "";
		this._settings = {};
		this._fieldId = "";
		this._control = null;
		this._selector = null;

		this._inputKeyPressHandler = BX.delegate(this.keypress, this);
	};

	BX.FilterEntitySelector.prototype =
		{
			initialize: function(id, settings) {
				this._id = id;
				this._settings = settings ? settings : {};
				this._fieldId = this.getSetting("fieldId", "");

				BX.addCustomEvent(window, "BX.Main.Filter:customEntityFocus", BX.delegate(this.onCustomEntitySelectorOpen, this));
				BX.addCustomEvent(window, "BX.Main.Filter:customEntityBlur", BX.delegate(this.onCustomEntitySelectorClose, this));

			},
			getId: function() {
				return this._id;
			},
			getSetting: function(name, defaultval) {
				return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
			},
			keypress: function(e) {
				//e.target.value
			},
			open: function(field, query) {
				this._selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
					scope: field,
					id: this.getId() + "-selector",
					mode: this.getSetting("mode"),
					query: query ? query : false,
					useSearch: true,
					useAdd: false,
					parent: this,
					popupOffsetTop: 5,
					popupOffsetLeft: 40
				});
				this._selector.bindEvent("item-selected", BX.delegate(function(data) {
					this._control.setData(BX.util.htmlspecialcharsback(data.nameFormatted), data.id);
					if (!this.getSetting("multi"))
					{
						this._selector.close();
					}
				}, this));
				this._selector.open();
			},
			close: function() {
				if (this._selector)
				{
					this._selector.close();
				}
			},
			onCustomEntitySelectorOpen: function(control) {
				this._control = control;

				//BX.bind(control.field, "keyup", this._inputKeyPressHandler);

				if (this._fieldId !== control.getId())
				{
					this._selector = null;
					this.close();
				}
				else
				{
					this._selector = control;
					this.open(control.field);
				}
			},
			onCustomEntitySelectorClose: function(control) {
				if (this._fieldId !== control.getId())
				{
					this.close();
					//BX.unbind(control.field, "keyup", this._inputKeyPressHandler);
				}
			}
		};
	BX.FilterEntitySelector.closeAll = function() {
		for (var k in this.items)
		{
			if (this.items.hasOwnProperty(k))
			{
				this.items[k].close();
			}
		}
	};
	BX.FilterEntitySelector.items = {};
	BX.FilterEntitySelector.create = function(id, settings) {
		var self = new BX.FilterEntitySelector(id, settings);
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}