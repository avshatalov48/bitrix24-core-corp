import {Tag, Type, Text, Dom, Event} from 'main.core';
import {DataProvider} from "../../data_provider";
import {Tab} from "../../tab";
import {ERROR_CODE_FORM_READ_ACCESS_DENIED} from "../../errors";
import type {EmbedData} from "../../types";
import {handlerToggleCodeBlock} from '../../handler';
import "ui.switcher";
import "ui.notification";

export class Form extends Tab
{
	#formId: number;
	#dataProvider: DataProvider;

	constructor(formId: number, dataProvider: DataProvider)
	{
		super();
		this.#formId = formId;
		this.#dataProvider = dataProvider;
	}

	get dataProvider(): DataProvider
	{
		return this.#dataProvider;
	}

	get loaded(): boolean
	{
		return this.dataProvider.loaded;
	}

	/**
	 * @protected
	 * @deprecated this.dataProvider.data
	 */
	get data(): EmbedData
	{
		return this.dataProvider.data;
	}

	/**
	 * @deprecated this.dataProvider.data
	 */
	set data(data: EmbedData)
	{
		this.dataProvider.data = data;
	}

	get formId(): number
	{
		return this.#formId;
	}

	load(force: boolean = false): Promise
	{
		if (!force && this.dataProvider.loaded)
		{
			return Promise.resolve();
		}

		if (Type.isObject(this.dataProvider.errors) && Object.keys(this.dataProvider.errors).length > 0)
		{
			return Promise.reject();
		}

		return new Promise((resolve, reject) => {
			BX.ajax.runAction('crm.form.getEmbed', {
				json: {
					formId: this.#formId
				}
			}).then(response => {
				this.dataProvider.data = response.data;
				this.dataProvider.loaded = true;
				resolve(response);
			}).catch(response => {
				this.dataProvider.data = response.data;
				this.dataProvider.errors = response.errors;
				this.dataProvider.loaded = false;
				reject(response);
			});
		});
	}

	save(): Promise
	{
		return new Promise((resolve, reject) => {
			return BX.ajax.runAction('crm.form.saveEmbed', {
				json: {
					formId: this.formId,
					data: this.dataProvider.data.embed.viewValues,
				}
			}).then((response) => {
				// TODO move to controls
				// const elem = renderedOptions.querySelector(`#crm-form-embed-wrapper-${formId}-${type}-vertical`);
				// if (key === "type")
				// {
				// 	item.value === "popup" && Dom.hide(elem);
				// 	item.value === "panel" && Dom.show(elem);
				// }

				top.BX.UI.Notification.Center.notify({
					content: BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_FORM_ALERT_SETTINGS_SAVED'),
				});

				resolve(response);
			}).catch((response) => {
				// FIXME move to controls
				// if (key === "type")
				// {
				// 	item.value === "popup" && Dom.show(elem);
				// 	item.value === "panel" && Dom.hide(elem);
				// }

				let messageId = 'EMBED_SLIDER_FORM_SETTINGS_ALERT_ERROR';
				if (! Type.isUndefined(response.data.error.status) && response.data.error.status === 'access denied')
				{
					messageId = 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR_ACCESS_DENIED_FORM';
				}

				top.BX.UI.Notification.Center.notify({
					content: BX.Loc.getMessage(messageId),
				});

				reject(response);
			});
		});
	}

	render(): HTMLElement
	{
		return this.renderContainer();
	}

	renderTo(node: HTMLElement): undefined
	{
		if (!Type.isDomNode(node))
		{
			throw new Error('Parameter `node` not an element.');
		}

		Dom.append(this.render(), node);
	}

	renderError(responseData: Object): HTMLElement
	{
		let errorMessage = BX.Loc.getMessage('EMBED_SLIDER_GENERAL_ERROR');

		if (Type.isObject(responseData) && Type.isObject(responseData.error) && responseData.error.status === 'access denied')
		{
			if (responseData.error.code === ERROR_CODE_FORM_READ_ACCESS_DENIED) {
				errorMessage = BX.Loc.getMessage('EMBED_SLIDER_FORM_ACCESS_DENIED');
			}
		}

		return Tag.render`
			<div class="ui-alert ui-alert-warning">
				<span class="ui-alert-message">${errorMessage}</span>
			</div>
		`;
	}

	renderSettingsHeader(title: string, switcher: boolean = false, expertMode: function = null): HTMLElement
	{
		const switcherNode = switcher ? Tag.render`<div class="crm-form-embed__customization-settings--switcher"></div>` : '';
		const headingBox = Tag.render`
			<div class="ui-slider-heading-box ${switcher ? '--toggle' : ''}" data-roll="heading-block">
				<div class="ui-slider-heading-main">
					${switcherNode}
					<div class="ui-slider-heading-4">${title ? title : BX.Loc.getMessage('EMBED_SLIDER_SETTINGS_HEADING')}</div>
				</div>
				${expertMode ? Tag.render`
					<div class="ui-slider-heading-rest">
						<a 
							class="ui-slider-link crm-form-embed__link --expert-mode --visible"
							data-roll="data-more-settings"
							onclick="${expertMode.bind(this)}"
						>
							${BX.Loc.getMessage('EMBED_SLIDER_EXPERT_MODE')}
						</a>
					</div>
				` : ''}
			</div>
		`;

		if (switcher)
		{
			const heading = headingBox.querySelector('.ui-slider-heading-4');
			Event.bind(heading, 'click', (event) => {
				// event.preventDefault();
				// const switcherId = event.currentTarget
				// 	.closest('.ui-slider-heading-main')
				// 	.querySelector('.ui-switcher[data-switcher-init="y"][data-switcher-id]')
				// 	.dataset.switcherId
				// ;
				// const innerSwitcher = top.BX.UI.Switcher.getById(switcherId);
				// innerSwitcher.toggle();

				event.currentTarget
					.closest('.ui-slider-heading-main')
					.querySelector('.ui-switcher[data-switcher-init="y"]')
					.click();
			});
		}

		return headingBox;
	}

	/**
	 * @protected
	 */
	renderCodeBlock(code: string): HTMLElement
	{
		const section = this.renderSection();
		Dom.append(Tag.render`
			<div class="crm-form-embed__customization--show-code">
				<div class="ui-icon ui-icon-service-code crm-form-embed__customization--show-code-icon"><i></i></div>
				<a class="ui-slider-link crm-form-embed__link --with-arrow" data-roll="data-show-code">${BX.Loc.getMessage('EMBED_SLIDER_SHOW_CODE')}</a>
			</div>
		`, section);
		Dom.append(Tag.render`
			<div class="crm-form-embed__code" data-roll="crm-form-embed__code" style="height: 0px;">
				<pre class="crm-form-embed__code-block"><span>${Text.encode(code)}</span></pre>
			</div>
		`, section);

		const toggleBtn = section.querySelector('[data-roll="data-show-code"]');
		Event.unbind(toggleBtn, 'click', handlerToggleCodeBlock);
		Event.bind(toggleBtn, 'click', handlerToggleCodeBlock);

		return section;
	}

	updateDependentFields(container: Element, name: string, value: string)
	{
		switch (name)
		{
			case 'type':
				const containerVertical = container.querySelector('[data-option="vertical"]');
				if (value === 'panel')
				{
					Dom.show(containerVertical)
				}
				else
				{
					Dom.hide(containerVertical);
				}
				break;
			case 'button.plain':
				const containerButtonStyle = container.querySelector('[data-option="buttonStyle"]');
				const containerLinkStyle = container.querySelector('[data-option="button.decoration"]');
				// const containerFont = container.querySelector('[data-option="button.font"]');
				const containerButtonPosition = container.querySelector('[data-option="button.align"]');
				const containerLinkPosition = container.querySelector('[data-option="link.align"]');
				if (value === '1')
				{
					Dom.hide(containerButtonStyle);
					Dom.show(containerLinkStyle);

					Dom.hide(containerButtonPosition);
					Dom.show(containerLinkPosition);

					// // Dom.hide(containerFont);
					// containerFont?.querySelectorAll('.crm-form-embed__settings-main--option').forEach((node) => {
					// 	Dom.attr(node, 'disabled', true);
					// })
				}
				else
				{
					Dom.hide(containerLinkStyle);
					Dom.show(containerButtonStyle);

					Dom.hide(containerLinkPosition);
					Dom.show(containerButtonPosition);

					// // Dom.show(containerFont);
					// containerFont?.querySelectorAll('.crm-form-embed__settings-main--option').forEach((node) => {
					// 	Dom.attr(node, 'disabled', false);
					// })
				}
				break;
		}
	}
}