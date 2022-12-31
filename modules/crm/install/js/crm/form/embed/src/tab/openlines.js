import {Tag, Text, Type, Dom} from 'main.core';
import {Widget} from './widget';
import type {OpenlinesData, LinesList, WidgetOptions} from "../types";
import "ui.switcher";
import {ERROR_CODE_OPENLINES_WRITE_ACCESS_DENIED} from "../errors";
import {handlerToggleSwitcher} from '../handler';

export class Openlines extends Widget
{
	constructor(formId: number, options: WidgetOptions = {})
	{
		super(formId, options);
		Openlines.prototype.actionGet = 'crm.form.getOpenlinesForEmbed';
	}

	renderHeader(helpCenterId: number, helpCenterUrl: string): HTMLElement
	{
		return this.renderHeaderSection(
			'message-widget',
			BX.Loc.getMessage('EMBED_SLIDER_OL_TITLE'),
			BX.Loc.getMessage('EMBED_SLIDER_OL_DESC'),
			helpCenterId,
			helpCenterUrl
		);
	}

	renderPreview(container: HTMLElement, data: OpenlinesData): undefined
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

	renderFinalBlock(container: HTMLElement): undefined {
	}

	renderWidgets(data: OpenlinesData): HTMLElement
	{
		// FIXME test, moved to error handler in widgets
		// if (Type.isNull(data))
		// {
		// 	return this.renderAccessDeniedAlert();
		// }

		const section = this.renderSection();
		Dom.append(Tag.render`
			<div class="crm-form-embed__title-block">
				<div class="ui-slider-heading-4">${BX.Loc.getMessage('EMBED_SLIDER_OL_SETTINGS_TITLE')}</div>
			</div>
		`, section);
		// Dom.append(
		// 	this.renderBubble("text", false),
		// 	section
		// );

		const allIds = Object.keys(data.lines);
		const contentInner = allIds.length === 0
			? this.renderEmptyInner()
			: this.renderLineRows(data.lines, data.formName);

		// let showMoreLink = '';
		// if (data.showMoreLink)
		// {
		// 	const allLinesUrl = Type.isStringFilled(data.url.allLines) ? data.url.allLines : '/services/contact_center/openlines';
		// 	showMoreLink = Tag.render`
		// 		<p class="crm-form-embed-widgets-all-buttons">
		// 			<a href="${Text.encode(allLinesUrl)}" target="_blank" onclick="BX.SidePanel.Instance.open('${Text.encode(allLinesUrl)}'); return false;">
		// 				${BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_FORM_ALL_LINES')}
		// 			</a>
		// 		</p>
		// 	`;
		// }

		Dom.append(Tag.render`
			<div class="crm-form-embed__customization-settings"> <!-- --without-btn -->
				${contentInner}
				
				<a
					class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round ui-btn-no-caps crm-form-embed__customization-settings--btn"
					href="${Text.encode(data.url.allLines)}"
					style="float: right;"
				>
					${BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_FORM_ALL_LINES')}
				</a>
				<div style="clear: both"></div>
			</div>
		`, section);
		return section;
	}

	renderEmptyInner(): string
	{
		return BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_EMPTY');
	}

	renderAccessDeniedAlert(): HTMLElement
	{
		return Tag.render`
			<div class="ui-alert ui-alert-warning">
				<span class="ui-alert-message">${BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_ACCESS_DENIED')}</span>
			</div>
		`;
	}

	renderLineRows(lines: LinesList, currentFormName: string): HTMLElement
	{
		const linesList = Tag.render`<div class="crm-form-embed-widgets" data-form-name="${Text.encode(currentFormName)}"></div>`;
		const lineIds = Openlines.getOrderedWidgetIds(lines);
		lineIds.forEach(id => {
			const data = lines[id];
			const checked = data.formEnabled && data.checked;
			const formName = data.formEnabled ? data.formName : '';
			linesList.append(Openlines.#renderLineRow(data.id, data.name, formName, checked, this.formId));
		});
		return linesList;
	}

	static #renderLineRow(lineId: number, lineName: string, formName: string, checked: boolean, formId: number): HTMLElement
	{
		const switcher = Openlines.createSwitcher(checked, 'crm-form-embed-line-input-' + lineId);
		switcher.handlers = {
			toggled: Openlines.#handleSwitcher.bind(switcher, formId, lineId),
		};

		const row = Tag.render`
			<div class="crm-form-embed__customization-settings--row crm-form-embed-widgets-block">
				<div class="crm-form-embed__customization-settings--switcher crm-form-embed-widgets-control"></div>
				<div class="crm-form-embed__customization-settings--row-label" onclick="${handlerToggleSwitcher}">
					${Text.encode(lineName)}
				</div>
				<div
					class="crm-form-embed-widgets-detail crm-form-embed__customization-settings--row-label-secondary"
					title="${Text.encode(formName)}"
					data-form-name="${Text.encode(formName)}"
				>
					${Text.encode(formName)}
				</div>
			</div>
		`;
		row.querySelector('.crm-form-embed-widgets-control').append(switcher.getNode());
		return row;
	}

	/**
	 * @this Switcher
	 * @param formId number
	 * @param lineId number
	 */
	static #handleSwitcher(formId: number, lineId: number): undefined
	{
		this.setLoading(true);

		// save old widget values for rollback on error
		const formNameOld = this.getNode().closest('.crm-form-embed-widgets-block').querySelector('.crm-form-embed-widgets-detail').dataset.formName;
		// get values for current form
		const formName = this.getNode().closest('.crm-form-embed-widgets').dataset.formName;
		// set related forms field to new values
		Openlines.#updateRelatedOpenlineForms(this.isChecked() ? formName : '', this);

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
			Openlines.#updateRelatedOpenlineForms(this.isChecked() ? response.data.formName : '', this);

			top.BX.UI.Notification.Center.notify({
				content: BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_FORM_ALERT_SETTINGS_SAVED'),
			});
		}).catch((response) => {
			this.setLoading(false);

			// rollback on error
			this.check(! this.isChecked(), false);
			Openlines.#updateRelatedOpenlineForms(formNameOld.length > 0 ? formNameOld : '', this);

			let messageId = 'EMBED_SLIDER_OPENLINES_FORM_ALERT_ERROR';
			if (!Type.isUndefined(response.data.error) && !Type.isUndefined(response.data.error.status) && response.data.error.status === 'access denied')
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

	static #updateRelatedOpenlineForms(formName: string, switcher: Switcher) {
		const elem = switcher.getNode().closest('.crm-form-embed-widgets-block').querySelector('.crm-form-embed-widgets-detail');
		elem.textContent = formName;
		elem.setAttribute('data-form-name', formName);
	}
}