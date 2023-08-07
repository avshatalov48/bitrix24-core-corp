import {Tag, Type, Loc, ajax} from 'main.core';
import {Layout} from "ui.sidepanel.layout";
import {EventEmitter} from "main.core.events";
import "ui.notification";

export class FileLimit extends EventEmitter
{
	static #instance: FileLimit | null = null;

	#data: Object = {
		limitMb: undefined,
		currentBytes: null,
		canChange: null,
	};

	#ui: Object = {
		container: HTMLElement = null,
		limit: {
			block: HTMLElement = null,
			input: HTMLInputElement = null,
		},
		percentage: {
			block: HTMLElement = null,
		}
	};

	static instance(): FileLimit
	{
		if (!FileLimit.#instance)
		{
			FileLimit.#instance = new FileLimit();
		}

		return FileLimit.#instance;
	}

	open(): Promise
	{
		let resolver;
		const promise = new Promise(resolve => {
			resolver = resolve;
		});

		const instance = FileLimit.instance();
		BX.SidePanel.Instance.open("crm.webform:file-limit", {
			width: 700,
			cacheable: false,
			events: {
				onCloseComplete: () => {
					resolver({...instance.getValue()});
				},
			},
			contentCallback: () => {
				return Layout.createContent({
					extensions: ['crm.form.file-limit', 'ui.forms', 'ui.sidepanel-content'],
					title: Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_TITLE'),
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
											content: Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_ACCESS_DENIED'),
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

	#render(): HTMLElement
	{
		const limitMb = this.#data.limitMb;

		this.#ui.percentage.block = this.#createLimitPercentageBlock();

		this.#ui.limit.input = Tag.render`
			<input 
					type="number" 
					name="limit"
					value="${limitMb}"
					min="1"
					maxlength="5"
					class="ui-ctl-element"
					onfocus="this.parentElement.classList.remove('ui-ctl-danger')"
				>
		`;
		this.#ui.limit.block = Tag.render`
			<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
				${this.#ui.limit.input}
			</div>
		`;

		this.#ui.container = Tag.render`
			<div>
				<div class="ui-slider-section">
					<div class="ui-slider-content-box">
						<div class="ui-slider-heading-4">${Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_DESCRIPTION_TITLE')}</div>
						<p class="ui-slider-paragraph-2">
							${Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_DESCRIPTION_TEXT')}
						</p>
						<div class="ui-form-row">
							${this.#ui.percentage.block}
						</div>
					</div>
				</div>
				<div class="ui-slider-section">
					<div class="ui-slider-content-box">
						<div class="ui-slider-heading-4">${Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_SETTING_TITLE')}</div>
						<p class="ui-slider-paragraph-2">
							${Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_SETTING_DISABLE_HINT')}
						</p>
					</div>
					<div>
						<div class="ui-form-row">
							<div class="ui-form-label">
								<div class="ui-ctl-label-text">${Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_LIMIT_SETTING_TITLE')}</div>
							</div>
							<div class="ui-form-content">
								${this.#ui.limit.block}
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
		return this.#ui.container;
	}

	#createLimitPercentageBlock()
	{
		const percentage = Type.isInteger(this.#data.limitMb)
			? Math.ceil((this.#data.currentBytes / (this.#data.limitMb * 1024 * 1024)) * 100)
			: 0
		;

		const percentageBlock = Tag.render`
			<div class="ui-alert"></div>
		`;

		if (Type.isInteger(this.#data.limitMb))
		{
			let colorAlertStyle = 'ui-alert-success';
			if (percentage >= 95)
			{
				colorAlertStyle = 'ui-alert-danger';
			}
			else if (percentage >= 85)
			{
				colorAlertStyle = 'ui-alert-warning';
			}
			BX.addClass(percentageBlock, colorAlertStyle);
			percentageBlock.innerText = this.#getLimitPercentageText(Math.min(percentage, 100));
		}
		else if (Type.isNull(this.#data.limitMb))
		{
			BX.addClass(percentageBlock, 'ui-alert-default');
			percentageBlock.innerText = Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_LIMIT_DISABLED');
		}
		else
		{
			BX.Hide(percentageBlock)
		}

		return percentageBlock;
	}

	#getLimitPercentageText(percentage: number): string
	{
		let percentageText = Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_CURRENT_LIMIT_PERCENTAGE_TEXT').replace('%percentage%', percentage);
		if (percentage >= 85)
		{
			percentageText = Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_CURRENT_LIMIT_USERS_MIGHT_TROUBLE').replace('%percentage%', percentage);
		}
		return percentageText;
	}

	canChange(): boolean
	{
		return this.#data.canChange;
	}

	load(): Promise
	{
		return ajax.runAction('crm.form.getFileLimit', {json: {}}).then(response => {
			this.#data = response.data;
			return this.#render();
		});
	}

	save(): Promise
	{
		let limitMb = this.#ui.limit.input.value;
		this.#ui?.limit.block.classList.remove('ui-ctl-danger');

		if (
			(Type.isInteger(limitMb) && limitMb <= 0)
			|| (Type.isStringFilled(limitMb && !Type.isInteger(limitMb)))
		)
		{
			this.#ui.limit.block.classList.add('ui-ctl-danger');
			return Promise.reject();
		}
		limitMb = Type.isStringFilled(limitMb)
			? Number(limitMb)
			: null
		;

		return ajax
			.runAction('crm.form.setFileLimit', {json: {limitMb}})
			.then(response => {
				this.#data = response.data;
				this.emit('onSuccessLimitChanged', {limit: this.#data.limitMb});
				return this.#data;
			})
		;
	}

	getValue(): {[key: string]: any}
	{
		return this.#data;
	}
}