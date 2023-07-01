import 'ui.sidepanel-content';
import './integration.css';
import {Loc, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {FormDictionary, FormOptions} from "crm.form.type";
import {Mapper} from 'crm.form.fields.mapper';
import {Alert} from 'ui.alerts';
import {Button, ButtonColor} from 'ui.buttons';
import 'ui.dropdown';
import {ajax} from "main.core.ajax";
import {Loader} from "main.loader";
import { LoginFactory } from 'seo.ads.login';
import { MessageBox } from 'ui.dialogs.messagebox';

type Options = {
	type: String;
	dictionary: FormDictionary;
	fields: Array<Object>;
	form: FormOptions;
};

let instances = [];

/**
 * Crm-From Integration
 *
 * @memberOf BX.Crm.Form
 */
export class Integration extends EventEmitter
{
	dictionary: FormDictionary;
	fields: Array<Object>;
	form: FormOptions;
	type: String;
	#container: HTMLElement;
	#profileContainer: HTMLElement;
	#pagesContainer: HTMLElement;
	#formsContainer: HTMLElement;
	#mapperContainer: HTMLElement;
	#adForms: Array|null = null;
	#adFormsErrors: Array|null = null;
	#adAccounts: Array|null = null;
	#seoEventHandler: Function;

	constructor(options: Options)
	{
		super();
		this.type = options.type;
		this.form = options.form;
		this.fields = options.fields;
		this.#seoEventHandler = (options) => {
			this.#onLogedIn(options);
			options.reload = false;
		};

		this.dictionary = options.dictionary;
		BX.addCustomEvent(window, 'seo-client-auth-result', this.#seoEventHandler);

		instances.forEach(instance => instance.destroy());
		instances = [];
		instances.push(this);
	}

	destroy()
	{
		BX.removeCustomEvent(window, 'seo-client-auth-result', this.#seoEventHandler);
	}

	getCase()
	{
		let item = this.form.integration.cases.filter(item => item.providerCode === this.type)[0] || null;
		if (!item)
		{
			const profile = (this.getProvider() || {}).profile || {};
			item = {
				linkDirection: 1,
				providerCode: this.type,
				date: null,
				account: {
					id: profile.id || null,
					name: profile.name || null,
				},
				form: {
					id: null,
					name: null,
				},
				fieldsMapping: [],
			};

			this.form.integration.cases.push(item);
		}

		return item;
	}

	getProvider()
	{
		return this.dictionary.integration.providers.filter(item => item.type === this.type)[0] || null;
	}

	getTypeTitle()
	{
		return Loc.getMessage('CRM_FORM_INTEGRATION_JS_PROVIDER_' + this.type.toUpperCase());
	}

	getAdForm(id: string = null)
	{
		if (id === null)
		{
			id = this.getCase().form.id + '';
		}

		return (this.#adForms || []).filter(item => item.id === id)[0] || null;
	}

	getAdAccount(id: string = null)
	{
		if (id === null)
		{
			id = this.getCase().account.id;
		}

		return (this.#adAccounts || []).filter(item => item.id === id)[0] || null;
	}


	getAdFormId()
	{
		const obj = this.getAdForm();
		if (obj && this.#adForms.some(item => item.id === obj.id))
		{
			return obj.id;
		}

		return null;
	}

	getAdAccountId()
	{
		const obj = this.getAdAccount();
		if (obj && this.#adAccounts.some(item => item.id === obj.id))
		{
			return obj.id;
		}

		return null;
	}

	#onClickChangeDirection()
	{
		this.getCase().linkDirection = 1;
		this.emit('change');
		this.render();
	}

	#onLogedIn(options)
	{
		if (!this.#container)
		{
			return;
		}

		ajax.runAction('crm.api.form.getDict', {json: {}})
			.then(response => response.data)
			.then(data => {
				this.dictionary.integration = data.integration;
				if (/.group/.test(options.engine || ''))
				{
					this.#renderPageSelector();
				}
				else
				{
					this.#renderProfileSelector();
				}
			})
		;

		ajax.runAction('crm.api.ads.leadads.account.loginCompletion', {
			data: {
				type: this.type,
			}
		}).then((data) => {

		}, (error) => {
			this.handleLoginCompletionError(error);
		});
	}

	#loginProfile()
	{
		LoginFactory.getLoginObject({
			TYPE: this.type,
			ENGINE_CODE: this.getProvider().engineCode,
			AUTH_URL: this.getProvider().authUrl,
		}).login();
	}

	#logoutProfile()
	{
		ajax.runAction('crm.api.ads.leadads.service.logout',{
			data: {
				type: this.type
			}
		})
		.then(() => {
			this.#requestAuthUrl();
			this.#adAccounts = null;
			this.getProvider().profile = null;
			this.#renderProfileSelector();
			this.#adForms = null;
		})
	}

	#loginGroup()
	{
		const popup = BX.util.popup('', 800, 600);
		ajax.runAction('crm.api.ads.leadads.service.registerGroup', {
			data: {
				type: this.type,
				group: this.getAdAccountId(),
			}
		})
			.then(response => {
				popup.location = response.data.authUrl;
			})
		;
	}

	#logoutGroup()
	{
		const group = this.getProvider().group;
		if (!group.groupId)
		{
			return Promise.resolve();
		}

		return ajax.runAction('crm.api.ads.leadads.service.logoutGroup', {
			data: {
				type: this.type,
				groupId: group.groupId,
			}
		}).then(response => {
			this.getProvider().group.hasAuth = false;
			this.#renderPageSelector();
		});
	}

	render(): HTMLElement
	{
		if (!this.#container)
		{
			this.#container = Tag.render`<div></div>`;
		}

		this.#container.innerHTML = '';
		if (!this.dictionary.integration.canUse)
		{
			return this.#container;
		}

		const currentCase = this.getCase();
		if (currentCase && currentCase.linkDirection === 0)
		{
			// show alert and button for changing direction
			this.#container.appendChild((new Alert({
				color: Alert.Color.WARNING,
				text: Loc.getMessage(
					'CRM_FORM_INTEGRATION_JS_NEW_INTEGRATION',
					{'%providerName%': this.getTypeTitle()}
				),
			})).render());

			this.#container.appendChild((new Button({
				text: Loc.getMessage('CRM_FORM_INTEGRATION_JS_NEW_INTEGRATION_BTN'),
				color: ButtonColor.PRIMARY,
				onclick: () => this.#onClickChangeDirection(),
			})).render());

			return this.#container;
		}
		this.#container.appendChild(this.#renderProfileSelector());
		return this.#container;
	}

	#renderProfileSelector()
	{
		if (!this.#profileContainer)
		{
			this.#profileContainer = Tag.render`<div></div>`;
		}

		this.#profileContainer.innerHTML = '';

		const provider = this.getProvider();
		if (!provider.profile)
		{
			this.#profileContainer.appendChild((new Alert({
				color: Alert.Color.PRIMARY,
				text: `
					<div class="ui-slider-heading-3">
						${Loc.getMessage('CRM_FORM_INTEGRATION_JS_LOGIN_TITLE', {'%providerName%': this.getTypeTitle()})}
					</div>
					<p class="ui-slider-paragraph-2">
						${Loc.getMessage('CRM_FORM_INTEGRATION_JS_LOGIN_DESC', {'%providerName%': this.getTypeTitle()})}
					</p>
				`,
			})).render());
			this.#profileContainer.appendChild((new Button({
				text: Loc.getMessage('CRM_FORM_INTEGRATION_JS_LOGIN_BTN'),
				color: ButtonColor.PRIMARY,
				onclick: () => this.#loginProfile(),
			})).render());
			return this.#profileContainer;
		}

		this.#profileContainer.appendChild(Tag.render`
			<div>
				<div class="crm-ads-conversion-block">
					<div class="crm-ads-conversion-social crm-ads-conversion-social-facebook"  style="padding-bottom: 15px; height: 58px;">
						${this.showAvatar(provider)}
						<div class="crm-ads-conversion-social-user">
							<a
								${provider.profile.url ? 'href="' + Tag.safe`${provider.profile.url}` + '"' : ""}
								target="_top"
								class="crm-ads-conversion-social-user-link"
								>
								${Tag.safe`${provider.profile.name}`}
							</a>
						</div>
						<div class="crm-ads-conversion-social-shutoff">
							${this.createLogoutProfileButton().render()}
						</div>
					</div>
				</div>
			</div>
		`);

		if (this.type === 'vkontakte')
		{
			this.#profileContainer.appendChild(this.#renderFormSelector());
		}
		else
		{
			this.#profileContainer.appendChild((this.#renderPageSelector()));
		}

		if (this.type === 'vkontakte')
		{
			this.#checkNewProfile()
		}

		return this.#profileContainer;
	}

	showBannerForOldProfile()
	{
		const message = MessageBox.create({
			message: Loc.getMessage('CRM_FORM_INTEGRATION_JS_ALERT_POPUP_MESSAGE'),
			title: Loc.getMessage('CRM_FORM_INTEGRATION_JS_ALERT_POPUP_TITLE'),
			minWidth: 517,
			buttons: [
				new Button({
					text: Loc.getMessage('CRM_FORM_INTEGRATION_JS_ALERT_POPUP_BTN_YES'),
					color: Button.Color.SUCCESS,
					onclick: () => this.#loginProfile(),
				}),
				new Button({
					text: Loc.getMessage('CRM_FORM_INTEGRATION_JS_ALERT_POPUP_BTN_OK'),
					color: Button.Color.LIGHT_BORDER,
					onclick: () =>
					{
						message.close()
					},
				}),
			]
		});
		message.show();
	}

	showAvatar(provider)
	{
		if (provider.profile.picture !== undefined)
		{
			return Tag.render`<div>
				<div
					class="crm-ads-conversion-social-avatar-icon"
					style="background-image: url(${Tag.safe`${provider.profile.picture}`})"
				>
				</div>
			</div>`;
		}
	}

	#setPageId(id)
	{
		this.getCase().account.id = id || '';
		this.getCase().account.name = (this.getAdAccount(id) || {}).name;
		this.emit('change');

		this.#adForms = null;
		this.#renderPageSelector();
	}

	#renderPageSelector()
	{
		if (!this.#pagesContainer)
		{
			this.#pagesContainer = Tag.render`<div></div>`;
		}

		this.#pagesContainer.innerHTML = '';
		if (!this.#adAccounts)
		{
			this.#pagesContainer.appendChild(this.#renderLoader());
			ajax.runAction('crm.api.ads.leadads.account.getAccounts', {
				data: {
					type: this.type,
					proxyId: null,
				}
			}).then(response => {
				this.#adAccounts = response.data.accounts.map(item => {
					return {
						id: item.id + '',
						name: item.name + '',
					};
				});
				this.#renderPageSelector();
			});

			return this.#pagesContainer;
		}

		if (this.#adAccounts.length === 0)
		{
			this.#pagesContainer.appendChild((new Alert({
				color: Alert.Color.PRIMARY,
				text: Loc.getMessage(
					'CRM_FORM_INTEGRATION_JS_PAGE_EMPTY',
					{'%providerName%': this.getTypeTitle()}
				),
			})).render());
			return this.#pagesContainer;
		}

		const id = this.getAdAccountId();
		const pagesDropdown = new BX.Landing.UI.Field.Dropdown({
			selector: 'page-list',
			title: Loc.getMessage('CRM_FORM_INTEGRATION_JS_PAGE_' + this.type.toUpperCase()),
			content: id,
			items: [
				{
					name: Loc.getMessage('CRM_FORM_INTEGRATION_JS_NOT_SELECTED'),
					value: ''
				},
				...this.#adAccounts.map(item => {
					return {
						value: item.id,
						name: item.name,
					};
				})
			],
		});
		pagesDropdown.subscribe('onChange', () => {
			this.#setPageId(pagesDropdown.getValue());
		});

		const container = Tag.render`<div class="crm-form-integration-page-container"></div>`;
		const selectorContainer = Tag.render`<div class="crm-form-integration-page-selector"></div>`;
		selectorContainer.appendChild(pagesDropdown.getNode());
		container.appendChild(selectorContainer);

		this.#pagesContainer.appendChild(container);

		const group = this.getProvider().group;
		const hasAuthGroup = group.isAuthUsed && group.hasAuth && group.groupId;
		if (hasAuthGroup && id && id !== group.groupId)
		{
			this.#pagesContainer.appendChild((new Alert({
				color: Alert.Color.WARNING,
				text: Loc.getMessage(
					'CRM_FORM_INTEGRATION_JS_PAGE_VKONTAKTE_RESTRICTED',
					{
						'%groupName%': (this.getAdAccount(group.groupId) || {}).name,
					}
				),
			})).render());

			const groupConnectBtn = new Button({
				text: Loc.getMessage('CRM_FORM_INTEGRATION_JS_PAGE_DISCONNECT_BTN'),
				color: ButtonColor.PRIMARY,
				className: '',
				onclick: () => {
					groupConnectBtn.setWaiting(true);
					this.#logoutGroup()
						.then(() => {
							groupConnectBtn.setWaiting(false);
						})
						.catch(() => {
							groupConnectBtn.setWaiting(false);
						})
					;
				},
			});

			this.#pagesContainer.appendChild(groupConnectBtn.render());
			return this.#pagesContainer;
		}


		if (id && group.isAuthUsed && !group.hasAuth)
		{
			const groupConnectBtn = new Button({
				text: Loc.getMessage('CRM_FORM_INTEGRATION_JS_PAGE_CONNECT_BTN'),
				color: ButtonColor.PRIMARY,
				className: '',
				onclick: () => this.#loginGroup(),
			});
			container.appendChild(groupConnectBtn.render());

			this.#pagesContainer.appendChild((new Alert({
				color: Alert.Color.PRIMARY,
				text: Loc.getMessage('CRM_FORM_INTEGRATION_JS_PAGE_CONNECT_INFO'),
			})).render());

			return this.#pagesContainer;
		}

		this.#pagesContainer.appendChild(this.#renderFormSelector());
		return this.#pagesContainer;
	}

	#renderFormSelector()
	{
		if (!this.#formsContainer)
		{
			this.#formsContainer = Tag.render`<div></div>`;
		}

		this.#formsContainer.innerHTML = '';

		// hack for vk
		if (!this.getCase().account.id && this.type === 'vkontakte')
		{
			this.#formsContainer.appendChild(this.#renderLoader());
			ajax.runAction('crm.api.ads.leadads.account.getProfile', {
				data: {
					type: this.type,
					proxyId: null,
				}
			}).then(response => {
				this.#adAccounts = [{
					id: response.data.profile.id + '',
					name: response.data.profile.name + '',
				}];

				this.getCase().account.id = this.#adAccounts[0].id;
				this.getCase().account.name = this.#adAccounts[0].name;

				this.#renderFormSelector();
			});

			return this.#formsContainer;
		}

		if (this.getProvider().hasPages)
		{
			const accountId = this.getAdAccountId();
			if (!accountId)
			{
				this.#formsContainer.appendChild((new Alert({
					color: Alert.Color.PRIMARY,
					text: Loc.getMessage('CRM_FORM_INTEGRATION_JS_PAGE_CHOOSE'),
				})).render());

				return this.#formsContainer;
			}
		}

		if (!this.#adForms)
		{
			this.#formsContainer.appendChild(this.#renderLoader());
			ajax.runAction('crm.api.ads.leadads.form.list', {
				data: {
					type: this.type,
					accountId: this.getAdAccountId() || 0,
					proxyId: null,
				}
			}).then(response => {
				this.#adForms = response.data.forms.map(item => {
					item.id += '';
					return item;
				});
				this.#renderFormSelector();
			}).catch(response => {
				this.#adFormsErrors = response.errors;
				this.#adForms = [];
				this.#renderFormSelector();
			});

			return this.#formsContainer;
		}

		if (this.#adForms.length === 0)
		{
			this.#formsContainer.appendChild((new Alert({
				color: Alert.Color.PRIMARY,
				text: this.#adFormsErrors.length > 0 ? this.#adFormsErrors[0].message : Loc.getMessage(
					'CRM_FORM_INTEGRATION_JS_FORM_EMPTY',
					{'%providerName%': this.getTypeTitle()}
				),
			})).render());
			return this.#formsContainer;
		}

		const formsDropdown = new BX.Landing.UI.Field.Dropdown({
			selector: 'form-list',
			title: Loc.getMessage('CRM_FORM_INTEGRATION_JS_FORM'),
			content: this.getAdFormId(),
			items: [
				{
					name: Loc.getMessage('CRM_FORM_INTEGRATION_JS_NOT_SELECTED'),
					value: ''
				},
				...this.#adForms.map(item => {
					return {
						name: item.name,
						value: item.id,
					};
				})
			],
		});

		formsDropdown.subscribe('onChange', () => {
			const formId = formsDropdown.getValue();
			this.getCase().form.id = formId;
			this.getCase().form.name = (this.getAdForm(formId) || {}).name;
			this.getCase().fieldsMapping = [];

			this.emit('change');
			this.#renderMapper();
		});

		this.#formsContainer.appendChild(formsDropdown.getNode());

		this.#formsContainer.appendChild(this.#renderMapper());

		return this.#formsContainer;
	}

	#renderLoader()
	{
		const container = Tag.render`<div style="position: relative; min-height: 100px;"></div>`;
		(new Loader()).show(container).then(() => {});
		return container;
	}

	#renderMapper()
	{
		if (!this.#mapperContainer)
		{
			this.#mapperContainer = Tag.render`<div></div>`;
		}

		this.#mapperContainer.innerHTML = '';
		if (!this.getAdForm())
		{
			this.#mapperContainer.appendChild((new Alert({
				color: Alert.Color.PRIMARY,
				text: Loc.getMessage('CRM_FORM_INTEGRATION_JS_FORM_CHOOSE'),
			})).render());

			return this.#mapperContainer;
		}

		const mappingMessageContainer = Tag.render`<div style="margin-bottom: 29px"></div>`;
		(new Alert({
			color: Alert.Color.PRIMARY,
			text: Loc.getMessage('CRM_FORM_INTEGRATION_JS_FIELD_MAP'),
		})).renderTo(mappingMessageContainer);
		this.#mapperContainer.appendChild(mappingMessageContainer);

		if (this.getAdForm()?.fields === undefined)
		{
			this.#mapperContainer.appendChild(this.#renderLoader());
			ajax.runAction('crm.api.ads.leadads.form.get', {
				data: {
					type: this.type,
					accountId: this.getAdAccountId() || 0,
					proxyId: null,
					formId: this.getAdForm().id,
				}
			}).then(response => {
				this.getAdForm().fields = response.data.form.fields;
				this.#renderMapper();
			}).catch(response => {
				this.#adFormsErrors = response.errors;
				this.#renderMapper();
			});

			return this.#mapperContainer;
		}
		const mapper = new Mapper({
			from: {
				caption: this.getTypeTitle(),
			},
			fields: this.fields,
			map: this.getAdForm().fields.map(field => {
				let outputCode = this.getCase()
					.fieldsMapping
					.filter(item => item.adsFieldKey === field.key)[0]
				;
				outputCode = (outputCode || {}).crmFieldKey || '';
				if (!outputCode)
				{
					outputCode = this.getProvider()
						.defaultMapping
						.filter(item => {
							return item.adsFieldType.toLowerCase() === (field.type || '').toLowerCase();
						})[0]
					;
					outputCode = (outputCode || {}).crmFieldType || '';
				}

				return {
					inputType: (field.type || '').toLowerCase(),
					inputCode: field.key,
					inputName: field.label,
					outputCode,
					outputName: '',
					data: {
						items: field.options || [],
					},
				};
			})
		});

		const emitChangeEvent = () => {
			const eventFields = [];
			this.getCase().fieldsMapping =  mapper.getMap()
				.map(item => {
					if (item.outputCode)
					{
						eventFields.push({name: item.outputCode});
					}

					return {
						crmFieldKey: item.outputCode,
						adsFieldKey: item.inputCode,
						items: item.data.items || [],
					};
				})
				.filter(item => item.crmFieldKey)
			;

			this.emit('change', {fields: eventFields});
		};
		emitChangeEvent();
		mapper.subscribe('change', emitChangeEvent);

		this.#mapperContainer.appendChild(mapper.render());
		return this.#mapperContainer;
	}

	handleLoginCompletionError(error)
	{
		//show banner
	}

	createLogoutProfileButton()
	{
		return new Button({
			text : Loc.getMessage('CRM_FORM_INTEGRATION_JS_LOGOUT_BTN'),
			color: Button.Color.LIGHT_BORDER,
			round: true,
			size: Button.Size.EXTRA_SMALL,
			onclick: this.#logoutProfile.bind(this),
		});
	}

	#checkNewProfile()
	{
		ajax.runAction('crm.api.ads.leadads.service.checkProfile',{
			data: {
				type: this.type
			}
		})
		.then(
			(response) => {},
			(error) => {
				this.#logoutProfile();
				this.showBannerForOldProfile();
			}
		)
	}

	#requestAuthUrl()
	{
		ajax.runAction('crm.api.ads.leadads.service.getAuthUrl', {
			data: {
				type: this.type
			}
		}).then((response) => {
			this.getProvider().authUrl = response.data.authUrl;
		});

	}
}
