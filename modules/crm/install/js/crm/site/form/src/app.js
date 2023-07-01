import {B24Options} from './type';
import {Conv, Type} from './util/registry';
import {Button} from './util/button';
import * as Form from './form/registry';
import * as Compatibility from './compatibility';

class Application
{
	#forms: Array<Form.Controller> = [];
	#userProviderPromise: Promise;

	list(): Array<Form.Controller>
	{
		return this.#forms;
	}

	get(id: string): Form.Controller | null
	{
		return this.#forms.filter((form) => {
			return form.getId() === id;
		})[0];
	}

	create(options: Form.Options): Form.Controller
	{
		const form = new Form.Controller(options);
		this.#forms.push(form);
		return form;
	}

	remove(id: String): void
	{
		this.#forms = this.#forms.filter(form => form.getId() !== id);
	}

	post(uri: string, body: FormData, headers: Object): Promise
	{
		return window.fetch(
			uri,
			{
				method: 'POST',
				mode: 'cors',
				cache: 'no-cache',
				headers: Object.assign(
					headers || {},
					{
						'Origin': window.location.origin,
					}
				),
				body: body
			}
		)
	}

	createForm24(b24options: B24Options, options: Form.Options): Form.Controller
	{
		options.provider = options.provider || {};
		if (!options.provider.user)
		{
			options.provider.user = this.getUserProvider24(b24options, options);
		}

		if (!options.provider.entities)
		{
			let entities = b24form.util.url.parameter.get('b24form_entities');
			if (entities)
			{
				entities = JSON.parse(entities);
				if (typeof entities === 'object')
				{
					options.provider.entities = entities;
				}
			}
		}

		options.provider.submit = this.getSubmitProvider24(b24options);
		options.analyticsHandler = this.getAnalyticsSender(b24options);

		if (b24options.lang)
		{
			options.language = b24options.lang;
		}

		options.proxy = b24form.common?.properties?.proxy || {};
		options.abuse = b24form.common?.properties?.abuse || {};
		options.languages = b24form.common.languages || [];
		options.messages = options.messages || {};
		options.messages = Object.assign(
			b24form.common.messages,
			options.messages || {}
		);
		options.identification = {
			type: 'b24',
			id: b24options.id,
			sec: b24options.sec,
			address: b24options.address,
		};

		const instance = this.create(options);
		instance.subscribe(Form.EventTypes.destroy, () => this.remove(instance.getId()));

		return instance;
	}

	createWidgetForm24(b24options: B24Options, options: Form.Options): Form.Controller
	{
		let pos = parseInt(BX.SiteButton.config.location) || 4;
		let positions = {
			1: ['left', 'top'],
			2: ['center', 'top'],
			3: ['right', 'top'],
			4: ['right', 'bottom'],
			5: ['center', 'bottom'],
			6: ['left', 'bottom'],
		};

		options.view = {
			type: (((options.fields || []).length + (options.agreements || []).length) <= 3)
				? 'widget'
				: 'panel',
			position: positions[pos][0],
			vertical: positions[pos][1]
		};

		Compatibility.performEventOfWidgetFormInit(b24options, options);
		const instance = this.createForm24(b24options, options);
		instance.subscribe(Form.EventTypes.hide, () => BX.SiteButton.onWidgetClose());

		return instance;
	}

	getUserProvider24(b24options: B24Options): Promise|Object
	{
		let signTtl = 3600 * 24;
		let sign = b24form.util.url.parameter.get('b24form_data');
		if (!sign)
		{
			sign = b24form.util.url.parameter.get('b24form_user');
			if (sign)
			{
				b24options.sign = sign;
				if (b24form.util.ls.getItem('b24-form-sign', signTtl))
				{
					sign = null;
				}
			}
		}

		const eventData = {sign};
		dispatchEvent(new CustomEvent(
			'b24:form:app:user:init',
			{
				detail: {
					object: this,
					data: eventData,
				}
			}
		));
		sign = eventData.sign;

		let ttl = 3600 * 24 * 28;
		if (!sign)
		{
			if (b24form.user && typeof b24form.user === 'object')
			{
				b24options.entities = b24options.entities || b24form.user.entities || [];
				return b24form.user.fields || {};
			}

			try
			{
				let user = b24form.util.ls.getItem('b24-form-user', ttl);
				if (user !== null && typeof user === 'object')
				{
					return user.fields || {};
				}
			}
			catch (e)
			{

			}
		}

		if (this.#userProviderPromise)
		{
			return this.#userProviderPromise;
		}

		if (!sign)
		{
			return null;
		}

		b24options.sign = sign;
		b24form.util.ls.setItem('b24-form-sign', sign, signTtl);

		let formData = new FormData();
		formData.set('id', b24options.id);
		formData.set('sec', b24options.sec);
		formData.set('security_sign', b24options.sign);

		this.#userProviderPromise = this.post(
			b24options.address + '/bitrix/services/main/ajax.php?action=crm.site.user.get',
			formData
		).then((response) => {
			return response.json();
		}).then(data => {
			if (data.error)
			{
				throw new Error(data.error_description || data.error);
			}

			data = data.result;
			data = data && typeof data === 'object' ? data : {};
			data.fields = data && typeof data.fields === 'object' ? data.fields : {};

			let properties = data.properties || {};
			delete data.properties;

			this.list()
				.filter(form => form.identification.id === b24options.id)
				.forEach(form => {
					Object
						.keys(properties)
						.forEach(key => form.setProperty(key, properties[key]))
				})
			;

			b24form.util.ls.setItem('b24-form-user', data, ttl);

			dispatchEvent(new CustomEvent(
				'b24:form:app:user:loaded',
				{
					detail: {
						object: this,
						data: {},
					}
				}
			));

			return data.fields;
		});

		return this.#userProviderPromise;
	}

	getSubmitProvider24(b24options: B24Options): Promise
	{
		return (form: Form.Controller, formData: FormData) => {

			let trace = (b24options.usedBySiteButton && BX.SiteButton)
				? BX.SiteButton.getTrace()
				: (window.b24Tracker && b24Tracker.guest) ? b24Tracker.guest.getTrace() : null;

			const eventData = {
				id: b24options.id,
				sec: b24options.sec,
				language: b24options.language,
				sign: b24options.sign,
			};
			form.emit('submit:post:before', eventData);

			formData.set('id', b24options.id);
			formData.set('sec', b24options.sec);
			formData.set('lang', form.language);
			formData.set('trace', trace);
			formData.set('entities', JSON.stringify(b24options.entities || []));
			formData.set('security_sign', eventData.sign || b24options.sign);

			return this.post(
				b24options.address + '/bitrix/services/main/ajax.php?action=crm.site.form.fill',
				formData
			).then((response) => {
				return response.json();
			}).then((data) => {
				if (data.error)
				{
					throw new Error(data.error_description || data.error);
				}

				data = data.result;
				if (data && data.gid && window.b24Tracker && b24Tracker.guest && b24Tracker.guest.setGid)
				{
					b24Tracker.guest.setGid(data.gid);
				}

				return new Promise(resolve => {
					resolve(data);
				});
			});
		};
	}

	initFormScript24(b24options: B24Options): Form.Controller|null
	{
		if (b24options.usedBySiteButton)
		{
			this.createWidgetForm24(b24options, Conv.cloneDeep(b24options.data));
			return;
		}

		let nodes = document.querySelectorAll('script[data-b24-form]');
		nodes = Array.prototype.slice.call(nodes);
		nodes.forEach(node => {
			if (node.hasAttribute('data-b24-loaded'))
			{
				return;
			}

			let attributes = node.getAttribute('data-b24-form').split('/');
			if (attributes[1] !== b24options.id || attributes[2] !== b24options.sec)
			{
				return;
			}

			node.setAttribute('data-b24-loaded', true);
			const options = Conv.cloneDeep(b24options.data);
			const id = node.getAttribute('data-b24-id');
			if (id)
			{
				options.id = id;
			}

			switch (attributes[0])
			{
				case 'auto':
					setTimeout(() => {
						this.createForm24(
							b24options,
							Object.assign(options, {
								view: b24options.views.auto
							})
						).show();
					}, (b24options.views.auto.delay || 1) * 1000);
					break;
				case 'click':
					let clickElement = node.nextElementSibling;
					const buttonUseMode = b24options?.views?.click?.button?.use === '1';
					if (buttonUseMode)
					{
						const newButton = Button.create(b24options);
						node.after(newButton);
						clickElement = newButton.querySelector('.b24-form-click-btn');
					}
					if (clickElement)
					{
						let form;
						clickElement.addEventListener('click',  () => {
							if (!form)
							{
								form = this.createForm24(
									b24options,
									Object.assign(options, {
										view: b24options.views.click
									})
								);
							}

							form.show();
						});
					}
					break;
				default:
					let target = document.createElement('div');
					node.parentElement.insertBefore(target, node);
					this.createForm24(
						b24options,
						Object.assign(options, {
							node: target
						})
					);
					break;
			}
		});
	}

	getAnalyticsSender(b24options)
	{
		return (counter: string, formId: string) =>
		{
			if (window.sessionStorage)
			{
				const key = `b24-analytics-counter-${formId}-${counter}`;
				if (sessionStorage.getItem(key) === 'y')
				{
					return Promise.resolve([]);
				}

				sessionStorage.setItem(key, 'y');
			}

			const formData = new FormData();
			formData.append('counter', counter);
			formData.append('formId', formId);

			return this.post(
				b24options.address + '/bitrix/services/main/ajax.php?action=crm.site.form.handleAnalytics',
				formData
			).then((response) =>
			{
				return response.json();
			}).then((data) =>
			{
				if (data.error)
				{
					throw new Error(data.error_description || data.error);
				}
				return new Promise(resolve =>
				{
					resolve(data);
				});
			});
		};
		;
	}
}

const App = new Application();
export {
	App,
	Compatibility,
}
