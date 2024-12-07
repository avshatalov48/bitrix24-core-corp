import { FieldsetViewer } from 'crm.requisite.fieldset-viewer';
import { Loc, Tag, Text as TextFormat } from 'main.core';

import Dummy from './dummy';

export default class Requisites extends Dummy
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
		const blocksManager = this.block.blocksManager;
		const member = blocksManager.getMemberByPart(this.block.getMemberPart());

		event.stopPropagation();

		if (!member)
		{
			return;
		}

		const { cid, entityTypeId, presetId } = member;
		(new FieldsetViewer({
			entityTypeId,
			entityId: cid,
			documentUid: blocksManager.getDocumentUid(),
			events: {
				onClose: () => {
					this.block.assign();
				},
			},
			fieldListEditorOptions: {
				fieldsPanelOptions: {
					filter: {
						'+categories': [
							'CONTACT',
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
							'double',
							'integer',
						],
						'-fields': [
							({ entity_field_name: fieldName }) => this.#getCrmFieldsNameBlackList().has(fieldName),
						],
					},
					fieldsFactory: {
						filter: this.#filterRequisiteCreateFields.bind(this),
					},
					presetId,
					controllerOptions: {
						hideVirtual: 1,
						hideRequisites: 0,
						hideSmartDocument: 1,
						presetId,
					},
				},
			},
			popupOptions: { overlay: true, cacheable: false },
		}))
			.setEndpoint('sign.api_v1.Integration.Crm.FieldSet.load')
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

	/**
	 * Returns type's content in view mode.
	 * @return {HTMLElement | string}
	 */
	getViewContent(): HTMLElement | string
	{
		const text = this.data.text || Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_REQUISITES');
		const tagBody = this.data.text ? '' : ' class="sign-document__block-content_member-nodata"';

		return Tag.render`
			<div${tagBody}>
				${TextFormat.encode(text).toString().replaceAll('[br]', '<br>')}
			</div>
		`;
	}

	getStyles(): { [p: string]: string }
	{
		return { ...super.getStyles(), ...Requisites.defaultTextBlockPaddingStyles };
	}

	#getCrmFieldsNameBlackList(): Set<string>
	{
		return new Set([
			'ADDRESS',
			'REG_ADDRESS',
		]);
	}

	#getCreateFieldTypeNamesBlackList(): Set<string>
	{
		return new Set([
			'file',
			'employee',
			'boolean',
			'money',
		]);
	}

	#filterRequisiteCreateFields(fields: Array<{ name: string }>): Array<Object>
	{
		return fields.filter(
			(createFieldType) => !this.#getCreateFieldTypeNamesBlackList().has(createFieldType.name),
		);
	}
}
