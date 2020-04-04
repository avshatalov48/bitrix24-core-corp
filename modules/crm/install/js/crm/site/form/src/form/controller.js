import {VueVendorV2} from "../../../../../../../../ui/install/js/ui/vue/vendor/v2/src/prod/vue.js";
import * as Type from "./types";
import * as Field from "../field/registry";
import * as Pager from "./pager";
import * as Messages from "./messages";
import * as Design from "./design";
import {Basket} from "./basket";
import * as Components from "./components/registry";
import * as Util from "../util/registry";

let DefaultOptions: Type.Options = {
	view: 'inline',
};

class Controller
{
	#id: string;
	view: Type.View = {type: 'inline'};
	provider: Object = {};
	languages: Array = [];
	language: string = 'en';
	messages: Messages.Storage;
	design: Design.Model;

	#handlers: Handlers = {
		hide: [],
		show: [],
	};
	#fields: Array<Field.BaseField> = [];
	agreements: Array<Field.AgreementField.Controller> = [];
	basket: Basket;

	title: string;
	buttonCaption: string;
	desc: string;
	useSign: boolean = false;

	date: Type.Date = {
		dateFormat: 'DD.MM.YYYY',
		dateTimeFormat: 'DD.MM.YYYY HH:mm:ss',
		sundayFirstly: false,
	};
	currency: Type.Currency = {
		code: 'USD',
		title: '$',
		format: '$#',
	};

	#personalisation = {
		title: '',
		desc: '',
	};

	validated: Boolean = false;
	visible: Boolean = true;
	loading: Boolean = false;
	disabled: Boolean = false;
	sent: Boolean = false;
	error: Boolean | Error = false;

	pager: Pager.Navigation;
	state: Element;
	stateText: String = '';
	stateButton: Object = {
		text: '',
		handler: null
	};
	node: Element;
	#vue: Object;

	constructor(options: Type.Options = DefaultOptions)
	{
		this.messages = new Messages.Storage();
		this.design = new Design.Model();

		options = this.adjust(options);
		this.#id = options.id || (
			Math.random().toString().split('.')[1]
			+
			Math.random().toString().split('.')[1]
		);

		this.provider = options.provider || {};
		if (this.provider.form)
		{
			this.loading = true;
			if (this.provider.form)
			{
				if (typeof this.provider.form === 'string')
				{

				}
				else if (typeof this.provider.form === 'function')
				{
					this.provider.form()
						.then(options => {
							this.adjust(options);
							this.load();
						})
						.catch((e) => {
							if (window.console && console.log)
							{
								console.log('b24form get `user` error:', e.message);
							}
						});
				}
			}
		}
		else
		{
			this.load();

			if (this.provider.user)
			{
				if (typeof this.provider.user === 'string')
				{

				}
				else if (this.provider.user instanceof Promise)
				{
					this.provider.user
						.then(user => {
							this.setValues(user);
							return user;
						})
						.catch((e) => {
							if (window.console && console.log)
							{
								console.log('b24form get `user` error:', e.message);
							}
						});
				}
				else if (typeof this.provider.user === 'object')
				{
					this.setValues(this.provider.user);
				}
			}
		}

		this.render();
	}

	load()
	{
		if (this.#fields.length === 0)
		{
			this.disabled = true;
		}
	}

	show()
	{
		this.visible = true;
		this.#handlers.show.forEach((handler) => handler(this));
	}

	hide()
	{
		this.visible = false;
		this.#handlers.hide.forEach((handler) => handler(this));
	}

	submit(): boolean
	{
		this.error = false;
		this.sent = false;

		if (!this.valid())
		{
			return false;
		}

		if (!this.provider.submit)
		{
			return true;
		}

		let consents = this.agreements.reduce((acc, field) => {
			acc[field.name] = field.value();
			return acc;
		}, {});

		this.loading = true;


		let formData = new FormData();
		formData.set('values', JSON.stringify(this.values()));
		formData.set('consents', JSON.stringify(consents));

		let promise;
		if (typeof this.provider.submit === 'string')
		{
			promise = window.fetch(this.provider.submit, {
				method: 'POST',
				mode: 'cors',
				cache: 'no-cache',
				headers: {
					'Origin': window.location.origin,
				},
				body: formData
			})
		}
		else if (typeof this.provider.submit === 'function')
		{
			promise = this.provider.submit(this, formData);
		}

		promise.then(data => {
			this.sent = true;
			this.loading = false;
			this.stateText = data.message || this.messages.get('stateSuccess');

			let redirect = data.redirect || {};
			if (redirect.url)
			{
				let handler = () => window.location = redirect.url;
				if (data.pay)
				{
					this.stateButton.text = this.messages.get('stateButtonPay');
					this.stateButton.handler = handler;
				}

				setTimeout(handler, (redirect.delay || 0) * 1000);
			}

		}).catch(e => {
			this.error = true;
			this.loading = false;
			this.stateText = this.messages.get('stateError');
		});

		return false;
	}

	setValues(values: Object)
	{
		if (!values || typeof values !== 'object')
		{
			return;
		}

		if (this.#personalisation.title)
		{
			this.title = Util.Conv.replaceText(this.#personalisation.title, values);
		}
		if (this.#personalisation.desc)
		{
			this.desc = Util.Conv.replaceText(this.#personalisation.desc, values);
		}

		this.#fields.forEach(field => {
			if (!values[field.type] || !field.item())
			{
				return;
			}

			field.item().value = field.format(values[field.type]);
		});
	}

	adjust(options: Type.Options = DefaultOptions)
	{
		options = Object.assign({}, DefaultOptions, options);

		if (options.messages)
		{
			this.messages.setMessages(options.messages || {});
		}
		if (options.language)
		{
			this.language = options.language;
			this.messages.setLanguage(this.language);
		}
		if (options.languages)
		{
			this.languages = options.languages;
		}
		////////////////////////////////////////

		if (options.handlers && typeof options.handlers === 'object')
		{
			if (typeof options.handlers.hide === 'function')
			{
				this.#handlers.hide.push(options.handlers.hide);
			}
			if (typeof options.handlers.show === 'function')
			{
				this.#handlers.show(options.handlers.show);
			}
		}

		if (typeof options.title !== 'undefined')
		{
			this.#personalisation.title = options.title;
			this.title = Util.Conv.replaceText(options.title, {});
		}
		if (typeof options.desc !== 'undefined')
		{
			this.#personalisation.desc = options.desc;
			this.desc = Util.Conv.replaceText(options.desc, {});
		}
		if (typeof options.useSign !== 'undefined')
		{
			this.useSign = !!options.useSign;
		}
		if (typeof options.date === 'object')
		{
			this.setDate(options.date);
		}
		if (typeof options.currency === 'object')
		{
			this.setCurrency(options.currency);
		}

		if (Array.isArray(options.fields))
		{
			this.setFields(options.fields);
		}
		if (Array.isArray(options.agreements))
		{
			options.agreements.forEach(fieldOptions => {
				fieldOptions.messages = this.messages;
				fieldOptions.design = this.design;
				this.agreements.push(new Field.AgreementField.Controller(fieldOptions));
			});
		}

		this.setView(options.view);
		this.buttonCaption = options.buttonCaption || this.messages.get('defButton');
		if (typeof options.visible !== 'undefined')
		{
			this.visible = !!options.visible;
		}
		if (typeof options.design !== 'undefined')
		{
			this.design.adjust(options.design);
		}


		if (options.node)
		{
			this.node = options.node;
		}
		if (!this.node)
		{
			this.node = document.createElement('div');
			document.body.appendChild(this.node);
		}

		return options;
	}

	setView(options: string|Type.View)
	{
		let view = (typeof (options || '') === 'string')
			? {type: options}
			: options;

		if (typeof view.type !== 'undefined')
		{
			this.view.type = Type.ViewTypes.includes(view.type)
				? view.type
				: 'inline';
		}

		if (typeof view.position !== 'undefined')
		{
			this.view.position = Type.ViewPositions.includes(view.position)
				? view.position
				: null;
		}
		if (typeof view.vertical !== 'undefined')
		{
			this.view.vertical = Type.ViewVerticals.includes(view.vertical)
				? view.vertical
				: null;
		}
		if (typeof view.title !== 'undefined')
		{
			this.view.title = view.title;
		}
		if (typeof view.delay !== 'undefined')
		{
			this.view.delay = parseInt(view.delay);
			this.view.delay = isNaN(this.view.delay) ? 0 : this.view.delay;
		}
	}

	setDate(date: Type.Date)
	{
		if (typeof date !== 'object')
		{
			return;
		}

		if (date.dateFormat)
		{
			this.date.dateFormat = date.dateFormat
		}
		if (date.dateTimeFormat)
		{
			this.date.dateTimeFormat = date.dateTimeFormat
		}
		if (typeof date.sundayFirstly !== 'undefined')
		{
			this.date.sundayFirstly = date.sundayFirstly
		}
	}

	setCurrency(currency: Type.Currency)
	{
		if (typeof currency !== 'object')
		{
			return;
		}

		if (currency.code)
		{
			this.currency.code = currency.code
		}
		if (currency.title)
		{
			this.currency.title = currency.title
		}
		if (currency.format)
		{
			this.currency.format = currency.format
		}
	}

	setFields(fieldOptionsList: Array<Field.Options>)
	{
		this.#fields = [];

		let page = new Pager.Page(this.title);
		this.pager = new Pager.Navigation();
		this.pager.add(page);
		fieldOptionsList.forEach(options => {
			switch (options.type)
			{
				case 'page':
					page = new Pager.Page(options.label || this.title);
					this.pager.add(page);
					return;

				case 'date':
				case 'datetime':
					options.format = options.type === 'date'
						? this.date.dateFormat
						: this.date.dateTimeFormat;
					options.sundayFirstly = this.date.sundayFirstly;
					break;

				case 'product':
					options.currency = this.currency;
					break;
			}

			options.messages = this.messages;
			options.design = this.design;

			let field = Field.Factory.create(options);
			page.fields.push(field);
			this.#fields.push(field);
		});
		this.pager.removeEmpty();
		this.basket = new Basket(this.#fields, this.currency);
	}

	getId()
	{
		return this.#id;
	}

	delete()
	{
		return null;
	}

	valid()
	{
		this.validated = true;
		return this.#fields.filter((field) => !field.valid()).length === 0
			&&
			this.agreements.every((field) => field.requestConsent())
		;
	}

	values()
	{
		return this.#fields.reduce((acc, field) => {
			acc[field.name] = field.values();
			return acc;
		}, {});
	}

	isOnState()
	{
		return this.disabled || this.error || this.sent || this.loading;
	}

	render(): void
	{
		//this.node.innerHTML = '';
		this.#vue = new VueVendorV2({
			el: this.node,
			components: Components.Definition,
			data: {
				form: this,
			},
			template: `
				<component v-bind:is="'b24-form-' + form.view.type"
					:key="form.id"
					:form="form"
				>
					<b24-form
						v-bind:key="form.id"
						v-bind:form="form"
					></b24-form>
				</component>			
			`,
		});
	}
}

type Options = Type.Options;
export {Controller, Options}