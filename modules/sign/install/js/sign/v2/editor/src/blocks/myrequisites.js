import { FieldsetViewer } from 'crm.requisite.fieldset-viewer';
import { Cache, Loc, Tag, Text } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { UI } from 'ui.notification';

import Dummy from './dummy';

export default class MyRequisites extends Dummy
{
	#cache = new Cache.MemoryCache();

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
		event.stopPropagation();
		this.#getFieldsetViewer().show();
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

	#getFieldsNameBlackList(): Array<string>
	{
		return [
			'ADDRESS',
			'REG_ADDRESS',
		];
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

	#getFieldsetViewer(): FieldsetViewer
	{
		return this.#cache.remember('fieldSetViewer', () => {
			const blocksManager = this.block.blocksManager;
			const member = blocksManager.getMemberByPart(this.block.getMemberPart());

			const { entityTypeId, presetId, entityId } = member;
			const cid = member.cid ?? entityId;

			const fieldsetViewer = new FieldsetViewer({
				entityTypeId,
				entityId: cid,
				documentUid: blocksManager.getDocumentUid(),
				events: {
					onClose: () => {
						this.block.assign();
						this.#cache.delete('fieldSetViewer');
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
								'double',
								'integer',
							],
							'-fields': [
								({ entity_field_name: fieldName }) => this.#getFieldsNameBlackList().includes(fieldName),
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
			})
				.setEndpoint('sign.api_v1.Integration.Crm.FieldSet.load')
			;

			fieldsetViewer.subscribe('onFieldSetLoadError', this.#onFieldsetViewerFieldsetLoadError.bind(this));

			return fieldsetViewer;
		});
	}

	#onFieldsetViewerFieldsetLoadError(event: BaseEvent<{ requestErrors: Array<{ message: string, code: string }> }>)
	{
		this.#getFieldsetViewer().hide();
		const hasAccessDeniedError = event.getData().requestErrors.some((error) => error.code === 'ACCESS_DENIED');
		if (hasAccessDeniedError)
		{
			UI.Notification.Center.notify({
				content: Text.encode(Loc.getMessage('SIGN_EDITOR_ERROR_REQUISITE_BLOCK_REQUISITE_ACCESS_DENIED')),
				autoHideDelay: 3000,
				width: 480,
			});
		}
	}
}
