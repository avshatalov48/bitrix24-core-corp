import { FieldsetViewer } from 'crm.requisite.fieldset-viewer';
import { Loc, Tag } from 'main.core';

import Dummy from './dummy';

export default class MyRequisites extends Dummy
{
	/**
	 * Returns true if block is in singleton mode.
	 * @return {boolean}
	 */
	isSingleton(): boolean
	{
		return true;
	}

	/**
	 * Returns current data.
	 * @return {any}
	 */
	getData(): any
	{
		const data = this.data;

		if (data.text)
		{
			data.text = null;
		}

		return data;
	}

	/**
	 * Returns initial dimension of block.
	 * @return {width: number, height: number}
	 */
	getInitDimension(): { width: number, height: number }
	{
		return {
			width: 250,
			height: 220,
		};
	}

	/**
	 * Calls when action button was clicked.
	 * @param {PointerEvent} event
	 */
	#onActionClick(event: PointerEvent)
	{
		const document = this.block.getDocument();
		const config = document.getConfig();

		event.stopPropagation();

		(new FieldsetViewer({
			entityTypeId: config.crmOwnerTypeCompany,
			entityId: document.getCompanyId(),
			events: {
				onClose: () => {
					this.block.assign();
				},
			},
			fieldListEditorOptions: {
				fieldsPanelOptions: {
					filter: {
						'+categories': [
							'COMPANY',
						],
						'+fields': [
							'list',
							'string',
							'date',
							'typed_string',
							'text',
							'datetime',
							'enumeration',
							'address',
							'url',
							'money',
							'boolean',
							'double',
						],
					},
					presetId: config.crmRequisiteCompanyPresetId,
					controllerOptions: {
						hideVirtual: 1,
						hideRequisites: 0,
						hideSmartDocument: 1,
					},
				},
			},
		}))
			.show()
		;
	}

	/**
	 * Returns action button for edit content.
	 * @return {HTMLElement}
	 */
	getActionButton(): HTMLElement
	{
		return Tag.render`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${this.#onActionClick.bind(this)}" data-role="action" data-id="action-${this.block.getCode()}">
					${Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_ACTION_BUTTON')}
				</button>
			</div>
		`;
	}

	getStyles(): { [p: string]: string }
	{
		return { ...super.getStyles(), ...MyRequisites.defaultTextBlockPaddingStyles };
	}
}
