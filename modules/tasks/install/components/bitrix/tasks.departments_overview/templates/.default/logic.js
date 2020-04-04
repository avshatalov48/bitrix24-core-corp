'use strict';

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

(function(){

	BX.namespace('Tasks.Component');

	if(typeof BX.Tasks.Component.TasksDepartmentsOverview != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksDepartmentsOverview = BX.Tasks.Component.extend({
		sys: {
			code: 'departments-overview'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				// create sub-instances through this.subInstance(), do some initialization, etc

				// do ajax call, like
				// this.callRemote('this.sampleCreateTask', {data: {TITLE: 'Sample Task'}}).then(function(result){ ... });
				// dont care about CSRF, SITE_ID and LANGUAGE_ID: it will be sent and checked automatically
			},

			bindEvents: function()
			{
				var filterId = this.option('filterId');
				var filter = BX.Main.filterManager.getById(filterId);

				var scope = this.scope();

				BX.bindDelegate(scope, 'click', {
					tagName: 'a',
					className: 'js-id-department'
				}, BX.delegate(function(event) { //TODO
					console.log('click');
					BX.PreventDefault(event);
					var id = this.dataset.id;
					if (!!filter && (filter instanceof BX.Main.Filter))
					{
						var filterApi = filter.getApi();
						filterApi.setFields({ 'UF_DEPARTMENT': { 0: id } });
						filterApi.apply();
					}
				}));
			}

			// add more methods, then call them like this.methodName()
		}
	});

	// may be some sub-controllers here...

}).call(this);