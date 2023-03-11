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
					query: false,
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

'use strict';

BX.namespace('Tasks.Component');

function DeleteTemplate(templateId)
{
	var instance = BX.Tasks.Component.TasksTemplatesList.getInstance();

	instance.deleteTemplate(templateId);
}

(function() {

	if (typeof BX.Tasks.Component.TasksTemplatesList != 'undefined')
	{
		return;
	}

	BX.Tasks.Component.TasksTemplatesList = BX.Tasks.Component.extend({
		sys: {
			code: 'templates'
		},
		methodsStatic: {
			instance: {},

			getInstance: function() {
				return BX.Tasks.Component.TasksTemplatesList.instance;
			},

			addInstance: function(obj) {
				BX.Tasks.Component.TasksTemplatesList.instance = obj;
			}
		},
		methods: {
			construct: function() {
				this.callConstruct(BX.Tasks.Component);
				BX.Tasks.Component.TasksTemplatesList.addInstance(this);

				this.option('grid', BX.Main.gridManager.getById(this.option('gridId')));
			},

			bindEvents: function() {
				// this.sliderInit();
			},

			sliderInit: function() {
				var patterns = this.option('patternsUrl');
				var self = this;

				BX.SidePanel.Instance.bindAnchors({
					rules: [
						{
							condition: [patterns.create, patterns.view],
							loader: 'default-loader',
							options: {
								cacheable: false,
								events: {
									onClose: function() {
										// self.reloadGrid();
									}
								}
							}
						}
					]
				});
			},

			deleteTemplate: function(templateId) {
				var self = this;

				BX.ajax.runComponentAction('bitrix:tasks.task.template', 'delete', {
					mode: 'class',
					data: {
						templateId: templateId
					}
				}).then(
					function(response)
					{
						if (
							!response.status
							|| response.status !== 'success'
						)
						{
							return;
						}

						self.reloadGrid();
					}.bind(this),
					function(response)
					{

					}.bind(this)
				);
			},
			reloadGrid: function() {
				var grid = this.getGrid();

				if(!grid)
				{
					return false;
				}

				if (BX.Bitrix24 && BX.Bitrix24.Slider && BX.Bitrix24.Slider.getLastOpenPage())
				{
					BX.Bitrix24.Slider.destroy(
						BX.Bitrix24.Slider.getLastOpenPage().getUrl()
					);
				}

				grid.reloadTable('POST', { apply_filter: 'Y', clear_nav: 'Y' });
			},

			getGrid: function(){
				var gridHandle;
				if(BX && BX.Main && BX.Main.gridManager)
				{
					gridHandle = BX.Main.gridManager.getById(this.option('gridId'));
				}

				return gridHandle ? gridHandle.instance : null;
			},

			deleteSelected: function()
			{
				var self = this;
				var grid = this.getGrid();
				if(!grid)
				{
					return false;
				}

				var selectedIds = grid.getRows().getSelectedIds();
				var isAllSelected = grid.getActionsPanel().getForAllCheckbox().checked;

				if(!BX.type.isArray(selectedIds) && !isAllSelected)
				{
					return false;
				}

				BX.Tasks
					.confirm(BX.message('TASKS_TEMPLATE_LIST_GROUP_ACTION_REMOVE_CONFIRM'))
					.then(function()
					{
						self.deleteItems(selectedIds, isAllSelected);
					});
			},

			deleteItems: function(itemIds, isAllSelected)
			{
				BX.ajax.runComponentAction('bitrix:tasks.templates.list', 'batchDelete', {
					mode: 'class',
					data: {
						ids: itemIds,
						isAllSelected: isAllSelected
					}
				}).then(
					function(response)
					{
						if (
							!response.status
							|| response.status !== 'success'
							|| !response.data.success
						)
						{
							var onErrorBox = new top.BX.UI.Dialogs.MessageBox({
								title: BX.message('TASKS_TEMPLATES_LIST_TITLE_ERROR'),
								message: response.data.message,
								noCaption: BX.message('TASKS_TEMPLATES_LIST_CLOSE'),
								maxWidth: 437,
								maxHeight: 160,
								buttons: top.BX.UI.Dialogs.MessageBoxButtons.NO,
								onNo: function(){
									onErrorBox.close();
								}.bind(this),
							});
							onErrorBox.show();
							if (response.data.needReload)
							{
								this.reloadGrid();
							}

							return;
						}

						this.reloadGrid();
					}.bind(this),
					function(response)
					{

					}.bind(this)
				);
			},

			toggleFilter: function (data)
			{
				var tag = data.tag;
				if (typeof tag === 'undefined')
				{
					return;
				}

				BX.SidePanel.Instance.closeAll()
				var filter = top.BX.Main.filterManager.getById(this.option('gridId'));

				if (!filter)
				{
					console.log('BX.Main.filterManager not initialised');
					return;
				}

				var filterApi = filter.getApi();
				filterApi.setFields({ TAGS: tag, TAGS_label: tag });
				filterApi.apply();
			},
		}
	});

	// may be some sub-controllers here...

}).call(this);