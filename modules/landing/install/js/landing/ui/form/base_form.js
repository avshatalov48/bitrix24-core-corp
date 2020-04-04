;(function() {
	"use strict";

	BX.namespace("BX.Landing.UI.Form");

	var append = BX.Landing.Utils.append;
	var clone = BX.Landing.Utils.clone;
	var remove = BX.Landing.Utils.remove;

	/**
	 * Implements base interface for works with forms
	 *
	 * @param {{
	 * 		[title]: ?string,
	 * 		[description]: string,
	 * 		[type]: string,
	 * 		[code]: string | number,
	 * 		[label]: string,
	 * 		[headerCheckbox]: {
	 * 		 [text]: string,
	 * 		 [onChange]: function
	 * 		}
	 * }} [data]
	 *
	 * @constructor
	 */
	BX.Landing.UI.Form.BaseForm = function(data)
	{
		this.data = BX.type.isPlainObject(data) ? data : {};
		this.id = "id" in this.data ? this.data.id : BX.Landing.Utils.random();
		this.selector = "selector" in this.data ? this.data.selector : "";
		this.title = "title" in this.data ? this.data.title : "";
		this.label = "label" in this.data ? this.data.label : "";
		this.type = "type" in this.data ? this.data.type : "content";
		this.code = "code" in this.data ? this.data.code : "";
		this.descriptionText = "description" in this.data ? this.data.description : "";
		this.headerCheckbox = this.data.headerCheckbox;
		this.layout = BX.Landing.UI.Form.BaseForm.createLayout();
		this.fields = new BX.Landing.Collection.BaseCollection();
		this.cards = new BX.Landing.Collection.BaseCollection();
		this.description = BX.Landing.UI.Form.BaseForm.createDescription();
		this.header = BX.Landing.UI.Form.BaseForm.createHeader();
		this.body = BX.Landing.UI.Form.BaseForm.createBody();
		this.footer = BX.Landing.UI.Form.BaseForm.createFooter();
		this.header.innerHTML = this.title;
		this.layout.appendChild(this.header);

		if (this.descriptionText)
		{
			this.description.innerHTML = this.descriptionText;
			this.layout.appendChild(this.description);
		}

		this.layout.appendChild(this.body);
		this.layout.appendChild(this.footer);

		var sources = BX.Landing.Main.getInstance().options.sources;

		if (!BX.type.isArray(sources) || sources.length < 1)
		{
			this.headerCheckbox = null;
		}

		if (this.headerCheckbox)
		{
			this.adjustHeaderCheckbox();
		}

	};


	/**
	 * Creates form layout
	 * @return {HTMLElement}
	 */
	BX.Landing.UI.Form.BaseForm.createLayout = function()
	{
		return BX.create("div", {props: {className: "landing-ui-form"}});
	};


	/**
	 * Creates form header layout
	 * @return {HTMLElement}
	 */
	BX.Landing.UI.Form.BaseForm.createHeader = function()
	{
		return BX.create("div", {props: {className: "landing-ui-form-header"}});
	};

	/**
	 * Creates form description layout
	 * @return {HTMLElement}
	 */
	BX.Landing.UI.Form.BaseForm.createDescription = function()
	{
		return BX.create("div", {props: {className: "landing-ui-form-description"}});
	};

	/**
	 * Creates form body layout
	 * @return {HTMLElement}
	 */
	BX.Landing.UI.Form.BaseForm.createBody = function()
	{
		return BX.create("div", {props: {className: "landing-ui-form-body"}});
	};


	/**
	 * Creates form footer layout
	 * @return {HTMLElement}
	 */
	BX.Landing.UI.Form.BaseForm.createFooter = function()
	{
		return BX.create("div", {props: {className: "landing-ui-form-footer"}});
	};


	BX.Landing.UI.Form.BaseForm.prototype = {
		adjustHeaderCheckbox: function()
		{
			var form = this;
			var headerLayout = BX.create("div", {
				props: {
					className: "landing-form-header"
				},
				children: [
					BX.create("div", {
						props: {
							className: "landing-form-dynamic-block-header-text"
						},
						text: this.header.innerText
					}),
					BX.create("div", {
						props: {
							className: "landing-form-header-checkbox-wrapper"
						},
						children: [
							BX.create("input", {
								props: {
									type: "checkbox",
									id: this.id,
									className: "landing-form-header-checkbox-input"
								},
								attrs: !!this.headerCheckbox.state ? {checked: true} : null,
								events: {
									change: function() {
										if (BX.type.isFunction(form.headerCheckbox.onChange))
										{
											form.headerCheckbox.onChange({
												state: this.checked,
												form: form
											});
										}
									}
								}
							}),
							BX.create("label", {
								props: {
									className: "landing-form-header-checkbox-label"
								},
								attrs: {
									"for": this.id
								},
								text: this.headerCheckbox.text
							}),
							this.headerCheckbox.help ? BX.create("div", {
								props: {
									className: "landing-form-header-checkbox-help"
								},
								events: {
									click: function() {
										top.open(this.headerCheckbox.help, '_blank');
									}.bind(this)
								}
							}) : undefined
						]
					})
				]
			});

			this.header.innerHTML = "";
			this.header.appendChild(headerLayout);
		},

		isDynamicEnabled: function()
		{
			var checkbox = this.header.querySelector('input');
			return !!checkbox && checkbox.checked;
		},

		addField: function(field)
		{
			this.fields.add(field);
			this.body.appendChild(field.getNode());
		},

		getNode: function()
		{
			return this.layout;
		},

		addCard: function(card)
		{
			this.cards.push(card);
			append(card.layout, this.body);
			card.fields.forEach(function(field) {
				this.fields.add(field);
			}, this)
		},

		replaceCard: function(oldCard, newCard)
		{
			if (oldCard)
			{
				oldCard.fields.forEach(function(field) {
					this.fields.remove(field);
				}, this);

				this.cards.remove(oldCard);
				remove(oldCard.layout);
			}

			this.addCard(newCard);
		},

		removeCard: function(card)
		{
			if (card)
			{
				card.fields.forEach(function(field) {
					this.fields.remove(field);
				}, this);

				this.cards.remove(card);
				remove(card.layout);
			}
		},

		clone: function(data)
		{
			var instance = new this.constructor(clone(data || this.data));

			this.fields.forEach(function(field) {
				var newFieldData = clone(field.data);
				delete newFieldData.content;
				newFieldData.selector = instance.selector;
				instance.addField(field.clone());
			});

			return instance;
		},

		serialize: function()
		{
			var result = {};

			this.fields.forEach(function(field) {
				result[field.selector] = field.getValue();
			});

			return result;
		}
	};
})();