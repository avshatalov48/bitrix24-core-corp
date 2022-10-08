import {Tag, Type, Loc, ajax} from 'main.core';
import {Layout} from "ui.sidepanel.layout";
import "ui.notification";

export class Captcha
{
	static open(): Promise
	{
		let resolver;
		const promise = new Promise(resolve => {
			resolver = resolve;
		});

		const instance = new Captcha;
		BX.SidePanel.Instance.open("crm.webform:captcha", {
			width: 700,
			cacheable: false,
			events: {
				onCloseComplete: () => {
					resolver({...instance.getValue()});
				},
			},
			contentCallback: () => {
				return Layout.createContent({
					extensions: ['crm.form.captcha', 'ui.forms', 'ui.sidepanel-content'],
					title: Loc.getMessage('CRM_FORM_CAPTCHA_JS_TITLE'),
					design: {
						section: false,
					},
					content ()
					{
						return instance.load();
					},
					buttons ({SaveButton, closeButton})
					{
						return [
							new SaveButton({
								onclick: btn => {

									if (!instance.canChange())
									{
										btn.setDisabled(true);
										BX.UI.Notification.Center.notify({
											content: Loc.getMessage('CRM_FORM_CAPTCHA_JS_ACCESS_DENIED'),
										});
										return;
									}

									btn.setWaiting(true);
									instance.save()
										.then(() => {
											btn.setWaiting(false);
											BX.SidePanel.Instance.close();
										})
										.catch(() => {
											btn.setWaiting(false);
										})
									;
								}
							}),
							closeButton
						];
					},
				});
			},
		});

		return promise;
	}

	#data: Object = {
		key: null,
		secret: null,
		canChange: false,
		hasDefaults: false,
	};
	#container: HTMLElement;

	#render(): HTMLElement
	{
		const key = Tag.safe`${this.#data.key}`;
		const secret = Tag.safe`${this.#data.secret}`;
		this.#container = Tag.render`				
			<div>
				<div class="ui-slider-section" ${this.#data.hasDefaults ? '' : 'hidden'}>
					<div class="ui-slider-content-box">
						<div class="ui-slider-heading-4">${Loc.getMessage('CRM_FORM_CAPTCHA_JS_STD_TITLE')}</div>
						<div class="ui-alert ui-alert-success">
							<span class="ui-alert-message">${Loc.getMessage('CRM_FORM_CAPTCHA_JS_STD_TEXT')}</span>
						</div>
					</div>
				</div>
				
				<div class="ui-slider-section">
					<div class="ui-slider-content-box">
						<div class="ui-slider-heading-4">${Loc.getMessage('CRM_FORM_CAPTCHA_JS_CUSTOM_TITLE')}</div>
						<p class="ui-slider-paragraph-2">
							${Loc.getMessage('CRM_FORM_CAPTCHA_JS_CUSTOM_TEXT')}
							<br>
							<a href="https://www.google.com/recaptcha/about/" target="_blank">${Loc.getMessage('CRM_FORM_CAPTCHA_JS_CUSTOM_HOWTO')}</a>
						</p>
					</div>
					
					<div>
						<div class="ui-form-row">
							<div class="ui-form-label">
								<div class="ui-ctl-label-text">Key</div>
							</div>
							<div class="ui-form-content">
								<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
									<input 
										type="text" 
										name="key"
										value="${key}"
										class="ui-ctl-element"
										onfocus="this.parentElement.classList.remove('ui-ctl-danger')"
									>
								</div>
							</div>
						</div>
						<div class="ui-form-row" style="margin: 20px 0 0;">
							<div class="ui-form-label">
								<div class="ui-ctl-label-text">Secret</div>
							</div>
							<div class="ui-form-content">
								<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
									<input 
										type="text" 
										name="secret"
										value="${secret}"
										class="ui-ctl-element"
										onfocus="this.parentElement.classList.remove('ui-ctl-danger')"
									>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
		return this.#container;
	}

	hasKeys(): boolean
	{
		const data = this.#data;
		return data.hasDefaults || (data.key && data.secret);
	}

	canChange(): boolean
	{
		return this.#data.canChange;
	}

	load(): Promise
	{
		return ajax.runAction('crm.form.getCaptcha', {json: {}}).then(response => {
			this.#data = response.data;
			return this.#render();
		});
	}

	save(): Promise
	{
		const keyNode = this.#container.querySelector('input[name="key"]');
		const secretNode = this.#container.querySelector('input[name="secret"]');

		const key = keyNode.value || '';
		const secret = secretNode.value || '';

		keyNode.parentElement.classList.remove('ui-ctl-danger');
		secretNode.parentElement.classList.remove('ui-ctl-danger');
		if (Type.isStringFilled(key) !== Type.isStringFilled(secret))
		{
			if (!key)
			{
				keyNode.parentElement.classList.add('ui-ctl-danger');
			}

			if (!secret)
			{
				secretNode.parentElement.classList.add('ui-ctl-danger');
			}

			return Promise.reject();
		}

		return ajax
			.runAction('crm.form.setCaptcha', {json: {key, secret}})
			.then(response => {
				this.#data = response.data;
				return this.#data;
			})
		;
	}

	getValue(): {[key: string]: any}
	{
		return this.#data;
	}
}