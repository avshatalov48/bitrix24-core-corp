import { addCustomEvent } from 'main.core';

export class EntityEditorRender
{
	#params: EntityEditorRenderParams;
	constructor(params: EntityEditorRenderParams)
	{
		this.#params = params;
	}

	async render(): Promise
	{
		this.#fetchEntityEditor(this.#params);

		return new Promise((resolve) => {
			addCustomEvent(
				window,
				'BX.Crm.EntityEditor:onUserFieldsDeployed',
				async (editor) => {
					if (editor.getId() !== this.#params.domContainerId)
					{
						return;
					}
					resolve(editor);
				},
			);
		});
	}

	#fetchEntityEditor(params: EntityEditorRenderParams): void
	{
		let eeUrl = '';
		switch (params.entityTypeName)
		{
			case 'DEAL':
				eeUrl = '/bitrix/components/bitrix/crm.deal.details/ajax.php';
				break;
			case 'LEAD':
				eeUrl = '/bitrix/components/bitrix/crm.lead.details/ajax.php';
				break;
			default:
				throw new Error(`Unknown entity type: ${params.entityTypeName}`);
		}

		// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
		eeUrl = `${eeUrl}?sessid=${BX.bitrix_sessid()}`;

		BX.ajax.post(
			eeUrl,
			{
				ACTION: 'PREPARE_EDITOR_HTML',
				ACTION_ENTITY_TYPE_NAME: params.entityTypeName,
				ACTION_ENTITY_ID: params.entityId,
				GUID: params.domContainerId,
				CONFIG_ID: params.configId,
				FORCE_DEFAULT_CONFIG: 'N',
				FORCE_DEFAULT_OPTIONS: 'Y',
				IS_EMBEDDED: 'Y',
				ENABLE_CONFIG_SCOPE_TOGGLE: 'N',
				ENABLE_CONFIGURATION_UPDATE: 'N',
				ENABLE_REQUIRED_USER_FIELD_CHECK: 'N',
				ENABLE_FIELDS_CONTEXT_MENU: 'N',
				CONTEXT: {},
				READ_ONLY: 'Y',
				MODULE_ID: 'crm',
			},
			() => {},
		);
	}
}

export interface EntityEditorRenderParams {
	entityTypeName: string;
	entityId: number;
	configId: string;
	domContainerId: string;
}
