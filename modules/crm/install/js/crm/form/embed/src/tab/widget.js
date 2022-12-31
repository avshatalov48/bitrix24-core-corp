import {Tag, Type, Text, Dom} from 'main.core';
import {Tab, HELP_CENTER_ID, HELP_CENTER_URL} from "../tab";
import type {WidgetOptions, WidgetsData, WidgetsList, Widget as Button} from "../types";
import {
	ERROR_CODE_FORM_READ_ACCESS_DENIED,
	ERROR_CODE_WIDGET_READ_ACCESS_DENIED,
	ERROR_CODE_WIDGET_WRITE_ACCESS_DENIED
} from "../errors";
import "ui.switcher";
import {handlerToggleSwitcher} from '../handler';

export class Widget extends Tab
{
	#formId: number;
	#options: WidgetOptions;
	#loaded: boolean = false;
	#data: WidgetsData;
	#errors: Object;
	#container: HTMLElement;

	constructor(formId: number, options: WidgetOptions = {})
	{
		super();
		this.#formId = formId;
		this.#options = options;
		Widget.prototype.actionGet = 'crm.form.getWidgetsForEmbed';
		this.#container = this.renderContainer();
	}

	get formId(): number
	{
		return this.#formId;
	}

	load(): Promise
	{
		if (this.#loaded)
		{
			return Promise.resolve();
		}

		return BX.ajax.runAction(this.actionGet, {
			json: {
				formId: this.#formId,
				count: this.#options.widgetsCount,
			}
		}).then(response => {
			this.#data = response.data;
			this.#loaded = true;
			this.render();
		}).catch(response => {
			this.#data = response.data;
			this.#errors = response.errors;
			this.#loaded = false;
			this.renderError(response.data);
		});
	}

	render(): HTMLElement
	{
		this.#container.innerHTML = '';
		const headerSection = this.renderHeader(HELP_CENTER_ID, HELP_CENTER_URL);
		Dom.append(headerSection, this.#container);

		if (!this.#loaded)
		{
			this.loader.show(this.#container);
		}
		else
		{
			Dom.replace(headerSection, this.renderHeader(
				this.#data.helpCenterId,
				this.#data.helpCenterUrl
			));
			this.renderPreview(this.#container, this.#data);
			Dom.append(this.renderWidgets(this.#data), this.#container);
			this.renderFinalBlock(this.#container);
		}

		return this.#container;
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
		this.#container.innerHTML = '';

		let errorMessage = BX.Loc.getMessage('EMBED_SLIDER_GENERAL_ERROR');

		if (Type.isObject(responseData) && Type.isObject(responseData.error) && responseData.error.status === 'access denied')
		{
			if (responseData.error.code === ERROR_CODE_FORM_READ_ACCESS_DENIED) {
				errorMessage = BX.Loc.getMessage('EMBED_SLIDER_FORM_ACCESS_DENIED');
			}

			if (responseData.error.code === ERROR_CODE_WIDGET_READ_ACCESS_DENIED) {
				errorMessage = BX.Loc.getMessage('EMBED_SLIDER_WIDGET_ACCESS_DENIED');
			}
		}

		Dom.append(Tag.render`
			<div class="ui-alert ui-alert-warning">
				<span class="ui-alert-message">${errorMessage}</span>
			</div>
		`, this.#container);
	}
	
	renderPreview(container: HTMLElement, data: WidgetsData): undefined
	{
		if (!Type.isNull(data.previewLink))
		{
			Dom.append(
				this.renderPreviewSection(
					BX.Loc.getMessage('EMBED_SLIDER_QR_TITLE'),
					BX.Loc.getMessage('EMBED_SLIDER_QR_DESC'),
					BX.Loc.getMessage('EMBED_SLIDER_OPEN_IN_NEW_TAB'),
					data.previewLink
				),
				container
			);
		}
	}

	renderFinalBlock(container: HTMLElement): undefined
	{
		Dom.append(
			this.renderCopySection(
				null,
				null,
				null,
				this.renderBubble(BX.Loc.getMessage('EMBED_SLIDER_WIDGET_COPY_BUBBLE'), true),
			),
			container
		);
	}

	renderHeader(helpCenterId: number, helpCenterUrl: string): HTMLElement
	{
		return this.renderHeaderSection(
			'message-widget',
			BX.Loc.getMessage('EMBED_SLIDER_WIDGET_TITLE'),
			BX.Loc.getMessage('EMBED_SLIDER_WIDGET_DESC'),
			helpCenterId,
			helpCenterUrl
		);
	}

	renderWidgets(data: WidgetsData): HTMLElement
	{
		const section = this.renderSection();
		Dom.append(Tag.render`
			<div class="crm-form-embed__title-block">
				<div class="ui-slider-heading-4">${BX.Loc.getMessage('EMBED_SLIDER_WIDGET_SETTINGS_TITLE')}</div>
			</div>
		`, section);
		// Dom.append(
		// 	this.renderBubble("text", false),
		// 	section
		// );

		const allIds = Object.keys(data.widgets);
		const contentInner = allIds.length === 0
			? this.renderEmptyInner()
			: this.renderWidgetRows(data.widgets, data.formName, data.formType);

		// let showMoreLink = '';
		// if (data.showMoreLink)
		// {
		// 	const allWidgetsUrl = Type.isStringFilled(data.url.allWidgets) ? data.url.allWidgets : '/crm/button/';
		// 	showMoreLink = Tag.render`
		// 		<p class="crm-form-embed-widgets-all-buttons">
		// 			<a href="${Text.encode(allWidgetsUrl)}" target="_blank" onclick="BX.SidePanel.Instance.open('${Text.encode(allWidgetsUrl)}'); return false;">
		// 				${BX.Loc.getMessage('EMBED_SLIDER_WIDGET_FORM_ALL_WIDGETS')}
		// 			</a>
		// 		</p>
		// 	`;
		// }

		Dom.append(
			Tag.render`
				<div class="crm-form-embed__customization-settings">
					${contentInner}

					<button
						class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round ui-btn-no-caps ui-btn-icon-add crm-form-embed__customization-settings--btn crm-form-embed__customization-settings--btn-add"
						onclick="window.open('/crm/button/edit/0/')"
					>
						${BX.Loc.getMessage('EMBED_SLIDER_WIDGET_SETTINGS_BUTTON')}
					</button>

					<a
						class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round ui-btn-no-caps crm-form-embed__customization-settings--btn"
						href="${Text.encode(data.url.allWidgets)}"
						style="float: right"
					>
						${BX.Loc.getMessage('EMBED_SLIDER_WIDGET_FORM_ALL_WIDGETS')}
					</a>
				</div>
			`,
			section
		);
		return section;
	}

	renderEmptyInner(): string
	{
		return BX.Loc.getMessage('EMBED_SLIDER_WIDGET_EMPTY');
	}

	renderWidgetRows(widgets: WidgetsList, currentFormName: string, currentFormType: string): HTMLElement
	{
		// TODO remove data-attr and div
		const widgetsList = Tag.render`<div class="crm-form-embed-widgets" data-form-name="${Text.encode(currentFormName)}" data-form-type="${Text.encode(currentFormType)}"></div>`;
		const widgetIds = Widget.getOrderedWidgetIds(widgets);
		widgetIds.forEach(id => {
			const widget = widgets[id];
			widgetsList.append(Widget.#renderWidgetRow(widget, this.#formId, currentFormType));
		});
		return widgetsList;
	}

	static #renderWidgetRow(widget: Button, formId: number, currentFormType: string): HTMLElement
	{
		const switcher = Widget.createSwitcher(widget.checked, 'crm-form-embed-widget-input-' + widget.id);
		switcher.handlers = {
			toggled: Widget.#handleSwitcher.bind(switcher, formId, widget.id),
		};

		const formNames = Object.values(widget.relatedFormNames);
		const sFormType = Widget.#getFormTypeMessage(currentFormType);
		const sFormNames = Type.isArray(formNames) && formNames.length > 0
			? formNames.join(', ')
			: ''
		;
		const sFormNamesField = sFormNames.length > 0 ? sFormType + ': ' + sFormNames : '';
		const row = Tag.render`
			<div class="crm-form-embed__customization-settings--row crm-form-embed-widgets-block">
				<div class="crm-form-embed__customization-settings--switcher crm-form-embed-widgets-control"></div>
				<div class="crm-form-embed__customization-settings--row-label" onclick="${handlerToggleSwitcher}">
					${Text.encode(widget.name)}
				</div>
				<div
					class="crm-form-embed-widgets-detail crm-form-embed__customization-settings--row-label-secondary"
					title="${Text.encode(sFormNamesField)}"
					data-form-name="${Text.encode(sFormNames)}"
					data-form-type="${Text.encode(sFormType)}"
				>
					${Text.encode(sFormNamesField)}
				</div>
			</div>
		`;
		row.querySelector('.crm-form-embed-widgets-control').append(switcher.getNode());
		return row;
	}

	static getOrderedWidgetIds(widgets: WidgetsList): Array
	{
		const widgetIds = Object.keys(widgets);
		widgetIds.sort((a, b) => {
			const aData = widgets[a];
			const bData = widgets[b];
			switch (true)
			{
				case (aData.checked && bData.checked) || (! aData.checked && ! bData.checked):
					return 0;
				case aData.checked && ! bData.checked:
					return -1;
				case ! aData.checked && bData.checked:
					return 1;
			}
		});
		return widgetIds;
	}

	static #getFormTypeMessage(formType: string): string
	{
		const formTypeLangKey = 'EMBED_SLIDER_WIDGET_FORM_TYPE_MESSAGE_' + formType.toUpperCase();
		return BX.Loc.hasMessage(formTypeLangKey) ? BX.Loc.getMessage(formTypeLangKey) : formType;
	}

	/**
	 * @this Switcher
	 * @param formId number
	 * @param widgetId number
	 */
	static #handleSwitcher(formId: number, widgetId: number): undefined
	{
		this.setLoading(true);

		// save old widget values for rollback on error
		const dataSetOld = this.getNode().closest('.crm-form-embed-widgets-block').querySelector('.crm-form-embed-widgets-detail').dataset;
		const formNameOld = dataSetOld.formName;
		const formTypeOld = dataSetOld.formType;

		// get values for current form
		const dataSetCurrent = this.getNode().closest('.crm-form-embed-widgets').dataset;
		const formName = dataSetCurrent.formName;
		const formType = dataSetCurrent.formType;

		// set related forms field to new values
		Widget.#updateRelatedForms(this.isChecked(), formName, formType, this);

		return BX.ajax.runAction('crm.form.assignWidgetToForm', {
			json: {
				formId: formId,
				buttonId: widgetId,
				assigned: this.isChecked() ? 'Y' : 'N',
			}
		}).then((response) => {
			this.setLoading(false);

			// set to returned values (must match current)
			this.check(response.data.assigned, false);
			Widget.#updateRelatedForms(this.isChecked(), response.data.formName, response.data.formType, this);

			top.BX.UI.Notification.Center.notify({
				content: BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_FORM_ALERT_SETTINGS_SAVED'),
			});
		}).catch((response) => {
			this.setLoading(false);

			// rollback on error
			this.check(! this.isChecked(), false);
			Widget.#updateRelatedForms(formNameOld.length > 0, formNameOld, formTypeOld, this);

			let messageId = 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR';
			if (!Type.isUndefined(response.data.error) && !Type.isUndefined(response.data.error.status) && response.data.error.status === 'access denied')
			{
				messageId = response.data.error.code === ERROR_CODE_WIDGET_WRITE_ACCESS_DENIED
					? 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR_ACCESS_DENIED_WIDGET'
					: 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR_ACCESS_DENIED_FORM';
			}

			top.BX.UI.Notification.Center.notify({
				content: BX.Loc.getMessage(messageId),
			});
		});
	}

	static #updateRelatedForms(assign: boolean, formName: string, formType: string, switcher: Switcher)
	{
		if (assign)
		{
			Widget.#setRelatedForms(switcher, formName, formType);
		}
		else
		{
			Widget.#clearRelatedForms(switcher);
		}
	}

	static #setRelatedForms(switcher: Switcher, formName: string, formType: string)
	{
		const formTypeMessage = Widget.#getFormTypeMessage(formType);
		const elem = switcher.getNode().closest('.crm-form-embed-widgets-block').querySelector('.crm-form-embed-widgets-detail');
		elem.textContent = formTypeMessage + ': ' + formName;
		elem.setAttribute('data-form-name', formName);
		elem.setAttribute('data-form-type', formTypeMessage);
	}

	static #clearRelatedForms(switcher: Switcher)
	{
		const elem = switcher.getNode().closest('.crm-form-embed-widgets-block').querySelector('.crm-form-embed-widgets-detail');
		elem.textContent = '';
		elem.setAttribute('data-form-name', '');
		elem.setAttribute('data-form-type', '');
	}

	/**
	 * @protected
	 */
	static createSwitcher(checked: boolean, inputName: string): Switcher
	{
		const switcherNode = document.createElement('span');
		switcherNode.className = 'ui-switcher';
		return new top.BX.UI.Switcher({
			node: switcherNode,
			checked: checked,
			inputName: inputName,
		});
	}
}