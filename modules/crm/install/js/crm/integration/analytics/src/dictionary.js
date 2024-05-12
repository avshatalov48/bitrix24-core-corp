/**
 * @memberOf BX.Crm.Integration.Analytics
 */
export const Dictionary = Object.freeze({
	TOOL_CRM: 'crm',
	TOOL_AI: 'AI',

	CATEGORY_ENTITY_OPERATIONS: 'entity_operations',
	CATEGORY_CRM_OPERATIONS: 'crm_operations',

	// region Event const
	EVENT_ENTITY_ADD_OPEN: 'entity_add_open',
	EVENT_ENTITY_ADD: 'entity_add',
	EVENT_ENTITY_UPDATE: 'entity_update',
	EVENT_ENTITY_COPY_OPEN: 'entity_copy_open',
	EVENT_ENTITY_COPY: 'entity_copy',
	EVENT_ENTITY_CONVERT: 'entity_convert',
	EVENT_ENTITY_CONVERT_BATCH: 'entity_convert_batch',
	EVENT_ENTITY_CONVERT_OPEN: 'entity_convert_open',

	EVENT_CALL_PARSING: 'call_parsing',
	EVENT_AUDIO_TO_TEXT: 'audio_to_text',
	EVENT_SUMMARY: 'summary',
	EVENT_EXTRACT_FIELDS: 'extract_fields',
	// endregion

	// region Type const
	TYPE_MANUAL: 'manual',
	TYPE_AUTO: 'auto',
	// endregion

	// region Section const
	SECTION_CRM: 'crm',
	SECTION_LEAD: 'lead_section',
	SECTION_DEAL: 'deal_section',
	SECTION_CONTACT: 'contact_section',
	SECTION_COMPANY: 'company_section',
	SECTION_MYCOMPANY: 'my_company_section',
	SECTION_QUOTE: 'quote_section',
	SECTION_SMART_INVOICE: 'smart_invoice_section',
	SECTION_DYNAMIC: 'dynamic_section',
	SECTION_CUSTOM: 'custom_section',
	/**
	 * @see \Bitrix\Crm\Service\Factory\SmartDocument::CONTACT_CATEGORY_CODE
	 */
	SECTION_SMART_DOCUMENT_CONTACT: 'smart_document_contact_section',
	/**
	 * @see \Bitrix\Crm\Integration\Catalog\Contractor\CategoryRepository::CONTACT_CODE
	 */
	SECTION_CATALOG_CONTRACTOR_CONTACT: 'catalog_contractor_contact_section',
	/**
	 * @see \Bitrix\Crm\Integration\Catalog\Contractor\CategoryRepository::COMPANY_CODE
	 */
	SECTION_CATALOG_CONTRACTOR_COMPANY: 'catalog_contractor_company_section',
	// endregion

	// region Sub Section const
	SUB_SECTION_LIST: 'list',
	SUB_SECTION_KANBAN: 'kanban',
	SUB_SECTION_ACTIVITIES: 'activities',
	SUB_SECTION_CALENDAR: 'calendar',
	SUB_SECTION_DEADLINES: 'deadlines',
	SUB_SECTION_DETAILS: 'details',

	SUB_SECTION_DEAL: 'deal',
	SUB_SECTION_LEAD: 'lead',
	// endregion

	// region Element const
	ELEMENT_CREATE_BUTTON: 'create_button',
	ELEMENT_CONTROL_PANEL_CREATE_BUTTON: 'control_panel_create_button',
	ELEMENT_QUICK_BUTTON: 'quick_button',
	ELEMENT_SETTINGS_BUTTON: 'settings_button',
	ELEMENT_GRID_ROW_CONTEXT_MENU: 'grid_row_context_menu',
	ELEMENT_GRID_GROUP_ACTIONS: 'grid_group_actions',
	ELEMENT_CONVERT_BUTTON: 'convert_button',
	ELEMENT_TERMINATION_CONTROL: 'termination_control',
	ELEMENT_CREATE_LINKED_ENTITY_BUTTON: 'create_linked_entity_button',
	ELEMENT_DRAG_N_DROP: 'drag_n_drop',
	ELEMENT_FILL_REQUIRED_FIELDS_POPUP: 'fill_required_fields_popup',
	ELEMENT_CRM_MODE_CHANGE_POPUP: 'crm_mode_change_popup',
	ELEMENT_COPILOT_BUTTON: 'copilot_button',
	ELEMENT_FEEDBACK_SEND: 'feedback_send',
	ELEMENT_FEEDBACK_REFUSED: 'feedback_refused',
	ELEMENT_CONFLICT_ACCEPT_CHANGES: 'conflict_accept_changes',
	ELEMENT_CONFLICT_CANCEL_CHANGES: 'conflict_cancel_changes',
	// endregion

	// region Status const
	STATUS_ATTEMPT: 'attempt',
	STATUS_SUCCESS: 'success',
	STATUS_ERROR: 'error',
	STATUS_CANCEL: 'cancel',

	STATUS_SUCCESS_FIELDS: 'success_fields',
	STATUS_SUCCESS_COMMENT: 'success_comment_only',
	STATUS_ERROR_NO_LIMITS: 'error_no_limits',
	STATUS_ERROR_GPT: 'error_gpt',
	STATUS_ERROR_B24: 'error_b24',
	// endregion
});
