import * as Form from './form/registry';

/** @requires module:webpacker */
/** @var {Object} module Current module.*/

class Application
{
	#forms: Array<Form.Controller> = [];
	#userProviderPromise: Promise;

	constructor()
	{

	}

	list() : Array<Form.Controller>
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
		let form = new Form.Controller(options);
		this.#forms.push(form);
		return form;
	}

	remove(id: String): void
	{

	}

	post(uri, body, headers): Promise
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

	createForm24(b24options, options): Form.Controller
	{
		options.provider = options.provider || {};
		if (!options.provider.user)
		{
			options.provider.user = this.getUserProvider24(b24options, options);
		}

		if (!options.provider.entities)
		{
			let entities = webPacker.url.parameter.get('b24form_entities');
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
		if (b24options.lang)
		{
			options.language = b24options.lang;
		}

		options.languages = module.languages || [];
		options.messages = options.messages || {};
		options.messages = Object.assign(
			module.messages,
			options.messages || {}
		);

		return this.create(options);
	}

	createWidgetForm24(b24options, options): Form.Controller
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
			type: (
					(options.fields || []).length <= 1
					&&
					(options.agreements || []).length <= 1
				)
				? 'widget'
				: 'panel',
			position: positions[pos][0],
			vertical: positions[pos][1]
		};

		options.handlers = {
			hide: () => {
				BX.SiteButton.onWidgetClose();
			}
		};

		return b24form.App.createForm24(b24options, options);
	}

	getUserProvider24(b24options): Promise|Object
	{
		let signTtl = 3600 * 24;
		let sign = webPacker.url.parameter.get('b24form_user');
		if (sign)
		{
			b24options.sign = sign;
			if (webPacker.ls.getItem('b24-form-sign', sign, signTtl))
			{
				sign = null;
			}
		}

		let ttl = 3600 * 24 * 28;
		if (!sign)
		{
			if (b24form.user && typeof b24form.user === 'object')
			{
				b24options.entities = b24options.entities || b24form.user.entities || [];
				return b24form.user.fields || {};
			}

			let user = webPacker.ls.getItem('b24-form-user', ttl);
			if (user !== null && typeof user === 'object')
			{
				return user.fields || {};
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

		webPacker.ls.setItem('b24-form-sign', sign, signTtl);

		let formData = new FormData();
		formData.set('security_sign', sign);
		formData.set('id', b24options.id);
		formData.set('sec', b24options.sec);

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

			let user = data.result;
			user = user && typeof user === 'object' ? user : {};
			user.fields = user && typeof user.fields === 'object' ? user.fields : {};

			webPacker.ls.setItem('b24-form-user', user, ttl);
			return user.fields;
		});

		return this.#userProviderPromise;
	}

	getSubmitProvider24(b24options): Promise
	{
		return (form, formData) => {

			let trace = (b24options.usedBySiteButton && BX.SiteButton)
				? BX.SiteButton.getTrace()
				: (window.b24Tracker && b24Tracker.guest) ? b24Tracker.guest.getTrace() : null;

			formData.set('id', b24options.id);
			formData.set('sec', b24options.sec);
			formData.set('lang', form.language);
			formData.set('trace', trace);
			formData.set('entities', JSON.stringify(b24options.entities || []));
			formData.set('security_sign', b24options.sign);

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
				return new Promise(resolve => {
					resolve(data);
				});
			});
		};
	}

	initFormScript24(b24options: Object): Form.Controller|null
	{
		let options = b24options.data;
		// noinspection JSUnresolvedVariable
		if (b24options.usedBySiteButton)
		{
			this.createWidgetForm24(b24options, options);
			return;
		}

		let nodes = document.querySelectorAll('script[data-b24-form]');
		nodes = Array.prototype.slice.call(nodes);
		nodes.forEach(node => {
			if (node.hasAttribute('data-b24-loaded'))
			{
				return;
			}
			node.setAttribute('data-b24-loaded', true);

			let attributes = node.getAttribute('data-b24-form').split('/');
			if (attributes[1] !== b24options.id || attributes[2] !== b24options.sec)
			{
				return;
			}

			switch (attributes[0])
			{
				case 'auto':
					setTimeout(() => {
						this.createForm24(
							b24options,
							Object.assign({}, options, {
								view: b24options.views.auto
							})
						).show();
					}, (b24options.views.auto.delay || 1) * 1000);
					break;
				case 'click':
					let clickElement = node.nextElementSibling;
					if (clickElement)
					{
						let form;
						clickElement.addEventListener('click',  () => {
							if (!form)
							{
								form = this.createForm24(
									b24options,
									Object.assign({}, options, {
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
						Object.assign({}, options, {
							node: target
						})
					);
					break;
			}
		});
	}
}

const App = new Application();
export {
	App
}