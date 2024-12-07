import { ajax as Ajax, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Loc } from 'main.core';

let editorInstance = null;

export function createOrUpdatePlaceholder(
	templateId: number,
	entityTypeId: number,
	entityCategoryId: ?number,
	params: Object,
): void
{
	const { id, value, entityType, text } = params;

	Ajax.runAction(
		'crm.activity.smsplaceholder.createOrUpdatePlaceholder',
		{
			data: {
				placeholderId: id,
				fieldName: Type.isStringFilled(value) ? value : null,
				entityType: Type.isStringFilled(entityType) ? entityType : null,
				fieldValue: Type.isStringFilled(text) ? text : null,
				templateId,
				entityTypeId,
				entityCategoryId,
			},
		},
	);
}

export const SmsEditorWrapper = {
	name: 'SmsEditorWrapper',
	props: {
		templateParam: Object,
		title: String,
		entityTypeId: Number,
		categoryId: Number,
	},
	data() {
		return {
			counter: 0,
			editorInstance: null,
			messages: {
				send: Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_SEND'),
			},
		};
	},
	methods: {
		onSend()
		{
			if (!editorInstance)
			{
				console.error('SmsEditorWrapper: editorInstance is null');

				return;
			}

			let text = '';
			if (editorInstance)
			{
				const tplEditorData = editorInstance.getData();
				if (Type.isPlainObject(tplEditorData))
				{
					text = tplEditorData.body;
				}
			}

			if (text === '')
			{
				text = this.templateParam.PREVIEW;
			}

			const templateId = this.templateParam.ID;

			EventEmitter.emit('BX.Crm.SmsEditorWrapper:click', { text, templateId });
		},
	},
	mounted()
	{
		const editorParams = {
			target: this.$refs.editorContainerEl,
			categoryId: this.categoryId,
			entityId: 0,
			entityTypeId: this.entityTypeId,
			onSelect: (params) => { // this callback is called when templates placeholder is changed
				createOrUpdatePlaceholder(
					this.templateParam.ORIGINAL_ID,
					this.entityTypeId,
					this.categoryId,
					{
						id: params.id,
						value: params.value,
						entityType: params.entityType,
						text: params.text,
					},
				);
			},
		};
		const preview = this.templateParam.PREVIEW;
		const placeholders = this.templateParam.PLACEHOLDERS || {};
		const filledPlaceholders = this.templateParam.FILLED_PLACEHOLDERS || [];

		editorInstance = (new BX.Crm.Template.Editor(editorParams))
			.setPlaceholders(placeholders)
			.setFilledPlaceholders(filledPlaceholders)
		;

		editorInstance.setBody(preview);
	},
	unmounted()
	{
		editorInstance = null;
	},
	template: `
		<div class="bx-crm-group-actions-messages__item">
			<div class="bx-crm-group-actions-messages__item-title">{{ title }}</div>
			<div class="bx-crm-group-actions-messages__editor" ref="editorContainerEl"></div>

			<button
				@click="onSend"
				class="ui-btn ui-btn-primary ui-btn-md bx-crm-group-actions-messages__button"
			>{{ messages.send }}</button>
		</div>
	`,
};
