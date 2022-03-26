import {Dom, Loc, Tag, Text, Type} from 'main.core';
import {Layout} from 'ui.sidepanel.layout';
import './embed.css';

type EmbedOptions = {
	onCloseComplete: function;
	widgetsCount: number;
	activeMenuItemId: ?string;
};

type EmbedDictPhrase = {
	id: string,
	name: string,
}

type Widget = {
	checked: boolean,
	id: number,
	name: string,
	relatedFormIds: number[],
	relatedFormNames: {
		[key:number]: string,
	},
}

type Line = {
	checked: boolean,
	id: number,
	name: string,
	formEnabled: boolean,
	formId: number,
	formName: string,
	formDelay: boolean,
}

type EmbedDict = {
	viewOptions: {
		delays: EmbedDictPhrase[],
		positions: EmbedDictPhrase[],
		types: EmbedDictPhrase[],
		verticals: EmbedDictPhrase[],
	},
};

type EmbedData = {
	dict: EmbedDict,
	embed: {
		pubLink: string,
		scripts: {
			auto: {old: string, text: string},
			click: {old: string, text: string},
			inline: {old: string, text: string},
		},
		viewOptions: {
			[key:string]: {
				[key:string]: Array,
			},
		},
		viewValues: {
			[key:string]: object,
		},
	},
};

type WidgetsList = {
	[key:number]: Widget,
}

type WidgetsData = {
	formName: string,
	formType: string,
	showMoreLink: boolean,
	url: {
		allWidgets: string,
	},
	widgets: WidgetsList,
};

type LinesList = {
	[key:number]: Line,
}

type OpenlinesData = {
	formName: string,
	showMoreLink: boolean,
	url: {
		allLines: string,
	},
	lines: LinesList,
};

const DEFAULT_WIDGETS_COUNT = 10;
const ERROR_CODE_FORM_READ_ACCESS_DENIED = 1;
const ERROR_CODE_FORM_WRITE_ACCESS_DENIED = 2;
const ERROR_CODE_WIDGET_READ_ACCESS_DENIED = 3;
const ERROR_CODE_WIDGET_WRITE_ACCESS_DENIED = 4;
const ERROR_CODE_OPENLINES_READ_ACCESS_DENIED = 5;
const ERROR_CODE_OPENLINES_WRITE_ACCESS_DENIED = 6;

export class Embed
{
	/**
	 * @public
	 * @access public
	 */
	static open(
		formId: number,
		options: EmbedOptions = {
			widgetsCount: DEFAULT_WIDGETS_COUNT,
			activeMenuItemId: 'inline',
		}
	)
	{
		BX.SidePanel.Instance.open("crm.webform:embed", {
			width: 900,
			cacheable: false,
			events: {
				onCloseComplete: (event) => {
					// const slider = event.getSlider();
					if (Type.isFunction(options.onCloseComplete))
					{
						options.onCloseComplete();
					}
				},
				// onLoad: (event) => {
				// 	BX.UI.Switcher.initByClassName();
				// }
			},
			contentCallback: () => {

				const menuItems = [
					{
						label: Loc.getMessage('EMBED_SLIDER_INLINE_HEADING1'),
						id: 'inline',
					},
					{
						label: Loc.getMessage('EMBED_SLIDER_CLICK_HEADING1'),
						id: 'click',
					},
					{
						label: Loc.getMessage('EMBED_SLIDER_AUTO_HEADING1'),
						id: 'auto',
					},
					{
						label: Loc.getMessage('EMBED_SLIDER_WIDGET_HEADER'),
						id: 'widgets',
					},
					{
						label: Loc.getMessage('EMBED_SLIDER_OPENLINES_HEADER'),
						id: 'openlines',
					},
					{
						label: Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK'),
						id: 'link',
					},
				];
				menuItems.forEach(item => item.active = item.id === options.activeMenuItemId);

				return Layout.createContent({
					extensions: ['crm.form.embed', 'ui.forms', 'ui.sidepanel-content', 'clipboard', 'ui.switcher', 'ui.notification'],
					title: BX.Loc.getMessage('EMBED_SLIDER_TITLE'),
					design: {
						section: false,
					},
					menu: {
						items: menuItems,
					},
					content ()
					{
						return BX.ajax.runAction('crm.form.getEmbed', {
							json: {
								formId: formId
							}
						}).then((response) => {
							return {
								embedData: response.data,
							};
						}).then((responseEmbed) => {
							return BX.ajax.runAction('crm.form.getWidgetsForEmbed', {
								json: {
									formId: formId,
									count: options.widgetsCount,
								}
							}).then((response) => {
								responseEmbed.widgetsData = response.data;
								return responseEmbed;
							}).catch((response) => {
								if (Type.isObject(response.data) && Type.isObject(response.data.error) && response.data.error.status === 'access denied')
								{
									responseEmbed.widgetsData = null;
									return responseEmbed;
								}
								throw new Error(BX.Loc.getMessage('EMBED_SLIDER_GENERAL_ERROR'));
							});
						}).then((responseWidgets) => {
							return BX.ajax.runAction('crm.form.getOpenlinesForEmbed', {
								json: {
									formId: formId,
									count: options.widgetsCount,
								}
							}).then((response) => {
								responseWidgets.openlinesData = response.data;
								return responseWidgets;
							}).catch((response) => {
								if (Type.isObject(response.data) && Type.isObject(response.data.error) && response.data.error.status === 'access denied')
								{
									responseWidgets.openlinesData = null;
									return responseWidgets;
								}
								throw new Error(BX.Loc.getMessage('EMBED_SLIDER_GENERAL_ERROR'));
							});
						}).then((result) => {
							return Embed.#renderSliderContent(
								formId,
								result.embedData,
								result.widgetsData,
								result.openlinesData,
							);
						}).catch((response) => {
							let errorMessage = BX.Loc.getMessage('EMBED_SLIDER_GENERAL_ERROR');

							if (Type.isObject(response.data) && Type.isObject(response.data.error) && response.data.error.status === 'access denied')
							{
								if (response.data.error.code === ERROR_CODE_FORM_READ_ACCESS_DENIED)
								{
									errorMessage = BX.Loc.getMessage('EMBED_SLIDER_FORM_ACCESS_DENIED');
								}
							}

							return Tag.render`
								<div class="ui-alert ui-alert-warning">
									<span class="ui-alert-message">${errorMessage}</span>
								</div>
							`;
						});
					},
					buttons ({closeButton})
					{
						return [closeButton];
					},
				});
			},
		});
	}

	/**
	 * @this Switcher
	 * @param formId number
	 * @param widgetId number
	 * @package
	 */
	static #handleToggledWidgetSwitcher(formId: number, widgetId: number)
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
		Embed.#updateRelatedForms(this.isChecked(), formName, formType, this);

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
			Embed.#updateRelatedForms(this.isChecked(), response.data.formName, response.data.formType, this);

			top.BX.UI.Notification.Center.notify({
				content: BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_FORM_ALERT_SETTINGS_SAVED'),
			});
		}).catch((response) => {
			this.setLoading(false);

			// rollback on error
			this.check(! this.isChecked(), false);
			Embed.#updateRelatedForms(formNameOld.length > 0, formNameOld, formTypeOld, this);

			let messageId = 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR';
			if (! Type.isUndefined(response.data.error.status) && response.data.error.status === 'access denied')
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

	/**
	 * @this Switcher
	 * @param formId number
	 * @param lineId number
	 * @package
	 */
	static #handleToggledLineSwitcher(formId: number, lineId: number)
	{
		this.setLoading(true);

		// save old widget values for rollback on error
		const formNameOld = this.getNode().closest('.crm-form-embed-widgets-block').querySelector('.crm-form-embed-widgets-detail').dataset.formName;
		// get values for current form
		const formName = this.getNode().closest('.crm-form-embed-widgets').dataset.formName;
		// set related forms field to new values
		Embed.#updateRelatedOpenlineForms(this.isChecked() ? formName : '', this);

		return BX.ajax.runAction('crm.form.assignOpenlinesToForm', {
			json: {
				formId: formId,
				lineId: lineId,
				assigned: this.isChecked() ? 'Y' : 'N',
				// afterMessage: 'N',
			}
		}).then((response) => {
			this.setLoading(false);

			// set to returned values (must match current)
			this.check(response.data.assigned, false);
			Embed.#updateRelatedOpenlineForms(this.isChecked() ? response.data.formName : '', this);

			top.BX.UI.Notification.Center.notify({
				content: BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_FORM_ALERT_SETTINGS_SAVED'),
			});
		}).catch((response) => {
			this.setLoading(false);

			// rollback on error
			this.check(! this.isChecked(), false);
			Embed.#updateRelatedOpenlineForms(formNameOld.length > 0 ? formNameOld : '' , this);

			let messageId = 'EMBED_SLIDER_OPENLINES_FORM_ALERT_ERROR';
			if (! Type.isUndefined(response.data.error.status) && response.data.error.status === 'access denied')
			{
				messageId = response.data.error.code === ERROR_CODE_OPENLINES_WRITE_ACCESS_DENIED
					? 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR_ACCESS_DENIED' // 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR_ACCESS_DENIED_OPENLINES'
					: 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR_ACCESS_DENIED_FORM';
			}

			top.BX.UI.Notification.Center.notify({
				content: BX.Loc.getMessage(messageId),
			});
		});
	}

	static #updateRelatedOpenlineForms(formName: string, switcher: Switcher)
	{
		const elem = switcher.getNode().closest('.crm-form-embed-widgets-block').querySelector('.crm-form-embed-widgets-detail');
		elem.textContent = formName;
		elem.setAttribute('data-form-name', formName);
	}

	static #updateRelatedForms(assign: boolean, formName: string, formType: string, switcher: Switcher)
	{
		assign
			? Embed.#setRelatedForms(switcher, formName, formType)
			: Embed.#clearRelatedForms(switcher);
	}

	static #setRelatedForms(switcher: Switcher, formName: string, formType: string)
	{
		const formTypeMessage = Embed.#getFormTypeMessage(formType);
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

	static #getFormTypeMessage(formType: string)
	{
		const formTypeLangKey = 'EMBED_SLIDER_WIDGET_FORM_TYPE_MESSAGE_' + formType.toUpperCase();
		return BX.Loc.hasMessage(formTypeLangKey) ? BX.Loc.getMessage(formTypeLangKey) : formType;
	}

	static #getFormTypeName(formType: string)
	{
		const formTypeLangKey = 'EMBED_SLIDER_WIDGET_FORM_TYPE_' + formType.toUpperCase();
		return BX.Loc.hasMessage(formTypeLangKey) ? BX.Loc.getMessage(formTypeLangKey) : formType;
	}

	static #renderSliderContent(formId: number, embedData: EmbedData, widgetsData: WidgetsData, openlinesData: OpenlinesData): string
	{
		const widgetBox = Embed.#renderWidgets(formId, widgetsData);

		const openlinesBox = Embed.#renderOpenlines(formId, openlinesData);

		const pubLinkBox = Tag.render`
			<div class="ui-slider-section" data-menu-item-id="link">
				<div class="ui-slider-content-box">
					<div class="ui-slider-heading-3">${BX.Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK')}</div>
					<div class="ui-slider-inner-box">
						<div class="ui-slider-paragraph">
							${BX.Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK_HEADER')}
						</div>
						<div class="crm-form-embed-inner-section">
							<div class="crm-form-embed-script crm-form-embed-publink"><a href="${Text.encode(embedData.embed.pubLink)}" target="_blank">${Text.encode(embedData.embed.pubLink)}</a></div>
							<button class="ui-btn ui-btn-md ui-btn-primary crm-form-embed-btn-copy">${BX.Loc.getMessage('EMBED_SLIDER_COPY_BUTTON')}</button>
						</div>
					</div>
				</div>
			</div>
		`;

		top.BX.clipboard.bindCopyClick(pubLinkBox.querySelector('.crm-form-embed-btn-copy'), {text: pubLinkBox.querySelector('.crm-form-embed-script')});

		return Tag.render`
			<div class="crm-form-embed-slider-wrapper">
				${pubLinkBox}
				${widgetBox}
				${openlinesBox}
				${Embed.#renderCodeBlockWrapper(formId, 'inline', embedData, 'inline')}
				${Embed.#renderCodeBlockWrapper(formId, 'click', embedData, 'click')}
				${Embed.#renderCodeBlockWrapper(formId, 'auto', embedData, 'auto')}
			</div>
		`;
	}

	static #renderCodeBlockWrapper(formId: number, type: string, embedData: EmbedData, blockId: string)
	{
		const viewOptions = Type.isObject(embedData.embed.viewOptions[type]) ? embedData.embed.viewOptions[type] : {},
			viewValues = Type.isObject(embedData.embed.viewValues[type]) ? embedData.embed.viewValues[type] : {};

		return Embed.#renderCodeBlock(
			BX.Loc.getMessage('EMBED_SLIDER_' + blockId.toUpperCase() + '_HEADING1'),
			BX.Loc.getMessage('EMBED_SLIDER_' + blockId.toUpperCase() + '_HEADING2'),
			BX.Loc.getMessage('EMBED_SLIDER_' + blockId.toUpperCase() + '_DESCRIPTION'),
			embedData.embed.scripts[type].text,
			viewValues,
			viewOptions,
			embedData.dict,
			type,
			formId,
			blockId
		);
	}

	static #renderWidgets(formId: number, widgetsData: WidgetsData)
	{
		if (Type.isNull(widgetsData))
		{
			return Tag.render`
				<div class="ui-slider-section" data-menu-item-id="widgets">
					<div class="ui-slider-content-box">
						<div class="ui-slider-heading-3">${BX.Loc.getMessage('EMBED_SLIDER_WIDGET_HEADER')}</div>
						<div class="ui-slider-inner-box">
							<div class="ui-slider-paragraph">
								${BX.Loc.getMessage('EMBED_SLIDER_WIDGET_HEADER_INNER')}
							</div>
							<div class="ui-alert ui-alert-warning">
								<span class="ui-alert-message">${BX.Loc.getMessage('EMBED_SLIDER_WIDGET_ACCESS_DENIED')}</span>
							</div>
						</div>
					</div>
				</div>
			`;
		}

		const allIds = Object.keys(widgetsData.widgets);
		const allWidgetsUrl = Type.isStringFilled(widgetsData.url.allWidgets) ? widgetsData.url.allWidgets : '/crm/button/';

		let widgetsInner;
		if (allIds.length === 0)
		{
			widgetsInner = BX.Loc.getMessage('EMBED_SLIDER_WIDGET_EMPTY');
		}
		else
		{
			widgetsInner = Embed.#renderWidgetsList(widgetsData.widgets, formId, widgetsData.formName, widgetsData.formType);
		}

		const widgetBox = Tag.render`
			<div class="ui-slider-section" data-menu-item-id="widgets">
				<div class="ui-slider-content-box">
					<div class="ui-slider-heading-3">${BX.Loc.getMessage('EMBED_SLIDER_WIDGET_HEADER')}</div>
					<div class="ui-slider-inner-box">
						<div class="ui-slider-paragraph">
							${BX.Loc.getMessage('EMBED_SLIDER_WIDGET_HEADER_INNER')}
						</div>
						<!--<p class="ui-slider-paragraph-2"></p>-->
						<div class="crm-form-embed-inner-section">
						</div>
					</div>
				</div>
			</div>
		`;

		widgetBox.querySelector('.crm-form-embed-inner-section').append(widgetsInner);

		if (widgetsData.showMoreLink)
		{
			const showMoreLink = Tag.render`
				<p class="crm-form-embed-widgets-all-buttons">
					<a href="${Text.encode(allWidgetsUrl)}" target="_blank" onclick="BX.SidePanel.Instance.open('${Text.encode(allWidgetsUrl)}'); return false;">
						${BX.Loc.getMessage('EMBED_SLIDER_WIDGET_FORM_ALL_WIDGETS')}
					</a>
				</p>
			`;
			widgetBox.querySelector('.crm-form-embed-inner-section').append(showMoreLink);
		}

		return widgetBox;
	}

	static #renderOpenlines(formId: number, openlinesData: OpenlinesData)
	{
		if (Type.isNull(openlinesData))
		{
			return Tag.render`
				<div class="ui-slider-section" data-menu-item-id="openlines">
					<div class="ui-slider-content-box">
						<div class="ui-slider-heading-3">${BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_HEADER')}</div>
						<div class="ui-slider-inner-box">
							<div class="ui-slider-paragraph">
								${BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_HEADER_INNER')}
							</div>
							<div class="ui-alert ui-alert-warning">
								<span class="ui-alert-message">${BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_ACCESS_DENIED')}</span>
							</div>
						</div>
					</div>
				</div>
			`;
		}

		const allIds = Object.keys(openlinesData.lines);
		const allLinesUrl = Type.isStringFilled(openlinesData.url.allLines) ? openlinesData.url.allLines : '/services/contact_center/openlines';

		let linesInner;
		if (allIds.length === 0)
		{
			linesInner = BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_EMPTY');
		}
		else
		{
			linesInner = Embed.#renderLinesList(openlinesData.lines, formId, openlinesData.formName);
		}

		const linesBox = Tag.render`
			<div class="ui-slider-section" data-menu-item-id="openlines">
				<div class="ui-slider-content-box">
					<div class="ui-slider-heading-3">${BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_HEADER')}</div>
					<div class="ui-slider-inner-box">
						<div class="ui-slider-paragraph">
							${BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_HEADER_INNER')}
						</div>
						<div class="crm-form-embed-inner-section">
						</div>
					</div>
				</div>
			</div>
		`;

		linesBox.querySelector('.crm-form-embed-inner-section').append(linesInner);

		if (openlinesData.showMoreLink)
		{
			const showMoreLink = Tag.render`
				<p class="crm-form-embed-widgets-all-buttons">
					<a href="${Text.encode(allLinesUrl)}" target="_blank" onclick="BX.SidePanel.Instance.open('${Text.encode(allLinesUrl)}'); return false;">
						${BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_FORM_ALL_LINES')}
					</a>
				</p>
			`;
			linesBox.querySelector('.crm-form-embed-inner-section').append(showMoreLink);
		}

		return linesBox;
	}

	static #renderWidgetsList(widgets: WidgetsList, formId: number, currentFormName: string, currentFormType: string): HTMLElement
	{
		const widgetsList = Tag.render`<div class="crm-form-embed-widgets" data-form-name="${Text.encode(currentFormName)}" data-form-type="${Text.encode(currentFormType)}"></div>`;
		const widgetIds = Embed.#getOrderedWidgetIds(widgets);
		widgetIds.forEach(id => {
			const data = widgets[id];
			widgetsList.append(Embed.#renderWidgetRow(data.id, data.name, data.relatedFormNames, currentFormType, data.checked, formId));
		});
		return widgetsList;
	}

	static #renderLinesList(lines: LinesList, formId: number, currentFormName: string): HTMLElement
	{
		const linesList = Tag.render`<div class="crm-form-embed-widgets" data-form-name="${Text.encode(currentFormName)}"></div>`;
		const lineIds = Embed.#getOrderedWidgetIds(lines);
		lineIds.forEach(id => {
			const data = lines[id];
			const checked = data.formEnabled && data.checked;
			const formName = data.formEnabled ? data.formName : '';
			linesList.append(Embed.#renderLineRow(data.id, data.name, formName, checked, formId));
		});
		return linesList;
	}

	static #getOrderedWidgetIds(widgets: WidgetsList): Array
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

	static #createSwitcher(checked: boolean, inputName: string)
	{
		const switcherNode = document.createElement('span');
		switcherNode.className = 'ui-switcher';
		return new top.BX.UI.Switcher({
			node: switcherNode,
			checked: checked,
			inputName: inputName,
		});
	}

	static #renderWidgetRow(widgetId: number, widgetName: string, formNames: object, formType: string, checked: boolean, formId: number)
	{
		const switcher = Embed.#createSwitcher(checked, 'crm-form-embed-widget-input-' + widgetId);
		switcher.handlers = {
			toggled: BX.Crm.Form.Embed.#handleToggledWidgetSwitcher.bind(switcher, formId, widgetId),
		};

		formNames = Object.values(formNames);
		const sFormType = Embed.#getFormTypeMessage(formType);
		const sFormNames = Type.isArray(formNames) && formNames.length > 0
			? formNames.join(', ')
			: ''
		;
		const sFormNamesField = sFormNames.length > 0 ? sFormType + ': ' + sFormNames : '';
		const row = Tag.render`
			<div class="crm-form-embed-widgets-block">
				<div class="crm-form-embed-widgets-name-block">
					<span class="crm-form-embed-widgets-name">${Text.encode(widgetName)}</span>
					<span
						class="crm-form-embed-widgets-detail"
						title="${Text.encode(sFormNamesField)}"
						data-form-name="${Text.encode(sFormNames)}"
						data-form-type="${Text.encode(sFormType)}"
					>
						${Text.encode(sFormNamesField)}
					</span>
				</div>
				<div class="crm-form-embed-widgets-control">
				</div>
			</div>
		`;
		row.querySelector('.crm-form-embed-widgets-control').append(switcher.getNode());
		return row;
	}

	static #renderLineRow(lineId: number, lineName: string, formName: string, checked: boolean, formId: number)
	{
		const switcher = Embed.#createSwitcher(checked, 'crm-form-embed-line-input-' + lineId);
		switcher.handlers = {
			toggled: BX.Crm.Form.Embed.#handleToggledLineSwitcher.bind(switcher, formId, lineId),
		};

		const row = Tag.render`
			<div class="crm-form-embed-widgets-block">
				<div class="crm-form-embed-widgets-name-block">
					<span class="crm-form-embed-widgets-name">${Text.encode(lineName)}</span>
					<span
						class="crm-form-embed-widgets-detail"
						title="${Text.encode(formName)}"
						data-form-name="${Text.encode(formName)}"
					>
						${Text.encode(formName)}
					</span>
				</div>
				<div class="crm-form-embed-widgets-control">
				</div>
			</div>
		`;
		row.querySelector('.crm-form-embed-widgets-control').append(switcher.getNode());
		return row;
	}

	static #renderCodeBlock(heading1: string, heading2: string, description: string, code: string, viewValues: object, viewOptions: object, dict: EmbedDict, type: string, formId: number, blockId: string)
	{
		const contentBox = Tag.render`
			<div class="ui-slider-section" data-menu-item-id="${Text.encode(blockId)}">
				<div class="ui-slider-content-box">
					<div class="ui-slider-heading-3">${Text.encode(heading1)}</div>
					<div class="ui-slider-inner-box">
						<!-- <p class="ui-slider-paragraph-2">${Text.encode(heading2)}</p> -->
						<p class="ui-slider-paragraph">${Text.encode(description)}</p>
						<div class="crm-form-embed-inner-section">
							<div class="crm-form-embed-script"><pre><span>${Text.encode(code)}</span></pre></div>
							<button class="ui-btn ui-btn-md ui-btn-primary crm-form-embed-btn-copy">${BX.Loc.getMessage('EMBED_SLIDER_COPY_BUTTON')}</button>
						</div>
					</div>
				</div>
			</div>
		`;
		contentBox.querySelector('.crm-form-embed-inner-section').prepend(
			Embed.#renderViewOptions(viewValues, viewOptions, dict, type, formId)
		);
		top.BX.clipboard.bindCopyClick(
			contentBox.querySelector('.crm-form-embed-btn-copy'),
			{text: contentBox.querySelector('.crm-form-embed-script')}
		);
		return contentBox;
	}

	static #renderViewOptions(values: object, options: object, dict: EmbedDict, type: string, formId: number): string
	{
		let renderedOptions = Tag.render`<div></div>`;
		Object.keys(options).forEach(key => {
			const keyName = Embed.#getOptionKeyName(key);
			const keyOptions = Type.isArray(options[key]) ? options[key] : [];
			const selected = Type.isUndefined(values[key]) ? keyOptions[0] : values[key];
			const selectedName = Embed.#getOptionValueName(key, selected, dict);
			const elementId = `crm-form-embed-select-${formId}-${type}-${key}`;
			const wrapperId = `crm-form-embed-wrapper-${formId}-${type}-${key}`;
			const optionElem = Tag.render`
				<div class="inline-options crm-form-embed-field-item-wrapper" id="${Text.encode(wrapperId)}">
					<label>${Text.encode(keyName)}:</label>
					<span class="crm-form-embed-field-item-text-button" id="${Text.encode(elementId)}">
						${Text.encode(selectedName)}
					</span>
				</div>
			`;
			Dom.append(optionElem, renderedOptions);

			if (key === "vertical" && values["type"] === "popup")
			{
				const elem = renderedOptions.querySelector(`#${wrapperId}`);
				Dom.hide(elem);
			}

			const button = renderedOptions.querySelector('#'+elementId);

			let items = [];
			keyOptions.forEach(option => {
				const valueName = Embed.#getOptionValueName(key, option, dict);
				items.push({
					text: valueName,
					value: Text.encode(option),
					onclick: function (event, item) {
						const elem = renderedOptions.querySelector(`#crm-form-embed-wrapper-${formId}-${type}-vertical`);
						if (key === "type")
						{
							item.value === "popup" && Dom.hide(elem);
							item.value === "panel" && Dom.show(elem);
						}

						const prevValue = button.textContent
						button.textContent = '...';
						item.getMenuWindow().close();
						return BX.ajax.runAction('crm.form.setViewOption', {
							json: {
								formId: formId,
								type: type,
								key: key,
								value: item.value,
							}
						}).then((response) => {
							button.textContent = valueName;
							top.BX.UI.Notification.Center.notify({
								content: BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_FORM_ALERT_SETTINGS_SAVED'),
							});
						}).catch((response) => {
							if (key === "type")
							{
								item.value === "popup" && Dom.show(elem);
								item.value === "panel" && Dom.hide(elem);
							}

							button.textContent = prevValue;

							let messageId = 'EMBED_SLIDER_FORM_SETTINGS_ALERT_ERROR';
							if (! Type.isUndefined(response.data.error.status) && response.data.error.status === 'access denied')
							{
								messageId = 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR_ACCESS_DENIED_FORM';
							}

							top.BX.UI.Notification.Center.notify({
								content: BX.Loc.getMessage(messageId),
							});
						});
					}
				});
			});

			const menu = new top.BX.PopupMenuWindow({
				bindElement: button,
				items: items,
			});
			button.addEventListener("click", function() {
				menu.show();
			});
		});

		return renderedOptions;
	}

	static #getOptionValueName(key: string, value: string, dict: object): object
	{
		const namesForOption = Type.isArray(dict['viewOptions'][key + 's']) ? dict['viewOptions'][key + 's'] : null;
		let result = value;
		if (namesForOption !== null)
		{
			namesForOption.forEach(elem => {
				if (elem.id.toString() === value.toString())
				{
					result = elem.name;
				}
			});
		}
		return Text.encode(result);
	}

	static #getOptionKeyName(key: string)
	{
		const msgKey = 'EMBED_SLIDER_KEY_' + key.toUpperCase();
		return BX.Loc.hasMessage(msgKey) ? BX.Loc.getMessage(msgKey) : Text.encode(key);
	}
}
