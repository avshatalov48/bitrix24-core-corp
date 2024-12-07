import { Loc } from 'main.core';
import { EntityCatalog } from 'ui.entity-catalog';
import { fetchTemplates, Templates } from './http';
import { SmsEditorWrapper } from './components/sms-editor-wrapper';

export class TemplateCatalogCreator
{
	#messages = {
		startConversation: Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_START'),
		sendFirst: Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_FIRST'),
		howItWork: Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_HOW'),
		learnCompliance: Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_COMPLIANCE'),
		learnMore: Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_MORE'),
	};

	async create(entityTypeId: number, categoryId: ?number): Promise<EntityCatalog>
	{
		const rawTemplates = await fetchTemplates(entityTypeId, categoryId);

		const itemsData = this.#getTemplateItems(entityTypeId, categoryId, rawTemplates);

		const itemSlot = this.#itemSlot();

		return new EntityCatalog({
			canDeselectGroups: false,
			showEmptyGroups: false,
			customComponents: {
				SmsEditorWrapper,
			},
			slots: {
				[EntityCatalog.SLOT_MAIN_CONTENT_ITEM]: itemSlot,
				[EntityCatalog.SLOT_MAIN_CONTENT_HEADER]: this.#catalogHeader(),
				[EntityCatalog.SLOT_MAIN_CONTENT_FOOTER]: this.#catalogFooter(),
			},
			groups: itemsData.groups,
			items: itemsData.templateItems,
			title: Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_TITLE'),
			popupOptions: {
				overlay: true,
			},
		});
	}

	#itemSlot(): string
	{
		return `
			<div>
				<SmsEditorWrapper  
					:templateParam="itemSlotProps.itemData.customData"
					:title="itemSlotProps.itemData.title" 
					:entityTypeId="itemSlotProps.itemData.entityTypeId"
					:categoryId="itemSlotProps.itemData.categoryId"
				/>
			</div>
		`;
	}

	#getTemplateItems(entityTypeId: number, categoryId: ?number, templates: Templates[])
	{
		const groups = templates.map((template) => {
			return {
				id: template.ID,
				name: template.TITLE,
			};
		});

		if (groups.length > 0)
		{
			groups[0].selected = true;
		}

		const templateItems = templates.map((template) => {
			return {
				id: template.ORIGINAL_ID,
				title: template.TITLE,
				entityTypeId,
				categoryId,
				groupIds: [template.ID],
				customData: {
					title: template.TITLE,
					FILLED_PLACEHOLDERS: template.FILLED_PLACEHOLDERS || [],
					...template,
				},
			};
		});

		return {
			groups,
			templateItems,
		};
	}

	#catalogHeader()
	{
		return `
			<div class="bx-crm-group-actions-messages-tpl-header">
				<div class="bx-crm-group-actions-messages-tpl-header-left">
					<div class="bx-crm-group-actions-messages-whatsapp-icon"></div>
				</div>
				<div class="bx-crm-group-actions-messages-tpl-header-center">
					<strong 
						class="bx-crm-group-actions-messages-tpl-header-center-title"
					>${this.#messages.startConversation}</strong><br>
					<span class="bx-crm-group-actions-messages-tpl-header_gray">${this.#messages.sendFirst}</span><br>
					<a 
							href="#" 
							onclick="BX.Event.EventEmitter.emit('BX.Crm.GroupActionsWhatsApp.Settings:help', { code: 20526810});"
						>${this.#messages.howItWork}</a>
				</div>
				<div class="bx-crm-group-actions-messages-tpl-header-right">
					<div 
						class="bx-crm-group-actions-messages-settings-icon"
						onclick="BX.Event.EventEmitter.emit('BX.Crm.GroupActionsWhatsApp.Settings:click');"
					></div>
				</div>
			</div>
		`;
	}

	#catalogFooter()
	{
		return `
			<div class="bx-crm-group-actions-messages-compliance">
				${this.#messages.learnCompliance}
			</div>
		`;
	}
}
