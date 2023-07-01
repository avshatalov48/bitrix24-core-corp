(function() {

	"use strict";

	/**
	 * @namespace BX.Disk.Model
	 */
	BX.namespace("BX.Disk.Model");

	/**
	 *
	 * @param {object} parameters
	 * @constructor
	 */
	BX.Disk.Model.Item = function(parameters)
	{
		this.node = null;
		this.renderCount = 0;
		this.layout = {};
		this.layout.container = null;
		this.layout.content = null;
		this.newState = {};
		this.models = parameters.models || {};
		this.data = parameters.data;
		this.templateId = parameters.templateId;
		this.setState(parameters.state);
	};

	BX.Disk.Model.Item.prototype =
	{
		getTemplate: function()
		{
			if (!this._template)
			{
				var template = BX(this.templateId);
				this._template = BX.type.isDomNode(template) ? template.innerHTML : '';
			}

			return this._template;
		},

		getAdditionalContainerClasses: function()
		{
			return [];
		},

		getContainer: function()
		{
			if (!this.layout.container)
			{
				var classes = this.getAdditionalContainerClasses();
				classes.push('disk-item-container');

				this.layout.container = BX.create('div', {
					props: {
						className: classes.join(' ')
					}
				});
			}

			return this.layout.container;
		},

		getContent: function()
		{
			if (!this.layout.content)
			{
				this.render();
			}

			return this.layout.content;
		},

		getEntity: function (parent, entity, additionalFilter)
		{
			if (!parent || !entity)
			{
				return null;
			}

			additionalFilter = additionalFilter || '';

			return parent.querySelector(additionalFilter + '[data-entity="' + entity + '"]');
		},

		getEntities: function (parent, entity, additionalFilter)
		{
			if (!parent || !entity)
			{
				return {length: 0};
			}

			additionalFilter = additionalFilter || '';

			return parent.querySelectorAll(additionalFilter + '[data-entity="' + entity + '"]');
		},

		render: function ()
		{
			var html = this.renderTemplate(
				this.getTemplate(),
				this.state
			);

			BX.adjust(this.getContainer(), {
				html: html
			});

			this.layout.content = this.getContainer().firstElementChild;

			this.renderCount++;
			this.bindEvents();
			this.renderSubItems();

			BX.onCustomEvent(this, "Disk.Model.Item:afterRender", [this]);
			this.afterRender();

			return this.layout.content;
		},

		afterRender: function ()
		{},

		renderSubItems: function ()
		{},

		getRenderCount: function ()
		{
			return this.renderCount;
		},

		remove: function()
		{
			BX.remove(this.layout.container);
		},

		renderTemplate: function (template, state)
		{
			state.encodeURI = () => {
				return (text, render) => {
					return encodeURI(render(text));
				};
			};

			return Mustache.render(template, state);
		},

		bindEvents: function ()
		{},

		/**
		 * @param {?Item~saveCallback} callback
		 */
		save: function (callback)
		{},

		setState: function (state)
		{
			this.state = state;

			var defaultStateValues = this.getDefaultStateValues();
			Object.keys(defaultStateValues).forEach(function(key) {
				this.state[key] = defaultStateValues[key];
			}, this);

			this.spreadState();
		},

		getDefaultStateValues: function ()
		{
			return {};
		},

		spreadState: function ()
		{
			Object.keys(this.models).forEach(function(key) {
				var model = this.models[key];
				model.setState(this.state);
			}, this);
		}
	};

	/**
	 * @callback Item~saveCallback
	 * @param {object} state
	 * @param {BX.Disk.Model.Item} model
	 */
})();