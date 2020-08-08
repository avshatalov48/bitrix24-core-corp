import {VueVendorV2} from "../../../../../../../../ui/install/js/ui/vue/vendor/v2/prod/src/vue.js";
import * as Type from "./types";
import * as Field from "../field/registry";
import * as Pager from "./pager";
import * as Messages from "./messages";
import * as Design from "./design";
import Dependence from "./dependence";
import Analytics from "./analytics";
import ReCaptcha from "./recaptcha";
import {Basket} from "./basket";
import * as Components from "./components/registry";
import * as Util from "../util/registry";
import Event from "../util/event";

const DefaultOptions: Type.Options = {
	view: 'inline',
};

class Controller extends Event
{
	#id: string;
	identification: Type.Identification = {};
	view: Type.View = {type: 'inline'};
	provider: Object = {};
	languages: Array = [];
	language: string = 'en';
	messages: Messages.Storage;
	design: Design.Model;

	#fields: Array<Field.BaseField> = [];
	#dependence: Dependence;
	#properties: Object = {};
	agreements: Array<Field.AgreementField.Controller> = [];
	basket: Basket;
	analytics: Analytics;
	recaptcha: ReCaptcha;

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
		super(options);
		this.setGlobalEventNamespace('b24:form');
		this.messages = new Messages.Storage();
		this.design = new Design.Model();
		this.#dependence = new Dependence(this);
		this.analytics = new Analytics(this);
		this.recaptcha = new ReCaptcha();

		this.emit(Type.EventTypes.initBefore, options);

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
								console.log('b24form get `form` error:', e.message);
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

		this.emit(Type.EventTypes.init);

		this.render();


	}

	load()
	{
		if (this.#fields.length === 0)
		{
			this.disabled = true;
		}

		if (this.visible)
		{
			this.show();
		}
	}

	show()
	{
		this.visible = true;
		this.emitOnce(Type.EventTypes.showFirst);
		this.emit(Type.EventTypes.show);
	}

	hide()
	{
		this.visible = false;
		this.emit(Type.EventTypes.hide);
	}

	submit(): boolean
	{
		this.error = false;
		this.sent = false;

		if (!this.valid())
		{
			return false;
		}

		if (!this.recaptcha.isVerified())
		{
			this.recaptcha.verify(() => this.submit());
			return false;
		}

		this.emit(Type.EventTypes.submit);

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
		formData.set('properties', JSON.stringify(this.#properties));
		formData.set('consents', JSON.stringify(consents));
		formData.set('recaptcha', this.recaptcha.getResponse());

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

		promise.then((data: Type.SubmitResponse) => {
			this.sent = true;
			this.loading = false;
			this.stateText = data.message || this.messages.get('stateSuccess');
			this.emit(Type.EventTypes.sendSuccess, data);

			let redirect = data.redirect || {};
			if (redirect.url)
			{
				let handler = () => {
					try { top.location = redirect.url; } catch (e) {}
					window.location = redirect.url;
				};
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
			this.emit(Type.EventTypes.sendError, e);
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

		if (typeof options.identification === 'object')
		{
			this.identification = options.identification;
		}

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
			Object.keys(options.handlers).forEach(key => this.subscribe(key, options.handlers[key]));
		}
		if (options.properties && typeof options.properties === 'object')
		{
			Object.keys(options.properties).forEach(key => this.setProperty(key, options.properties[key]));
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
		if (typeof options.recaptcha !== 'undefined')
		{
			this.recaptcha.adjust(options.recaptcha);
		}
		if (Array.isArray(options.dependencies))
		{
			this.#dependence.setDependencies(options.dependencies);
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
			field.subscribeAll((data, obj, type) => {
				this.emit('field:' + type, {data, type, field: obj});
			});
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

	getFields(): Field.BaseField
	{
		return this.#fields;
	}

	setProperty(key: string, value: string)
	{
		if (!key || typeof key !== 'string')
		{
			return;
		}
		if (typeof value !== 'string')
		{
			value = '';
		}

		this.#properties[key] = value;
	}

	getProperty(key: string)
	{
		return this.#properties[key];
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

	destroy()
	{
		this.emit(Type.EventTypes.destroy);
		this.unsubscribeAll();
		this.#vue.$destroy();
		this.#vue = null;
	}
}

type Options = Type.Options;
export {Controller, Options}