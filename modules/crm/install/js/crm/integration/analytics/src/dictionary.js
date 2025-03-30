/**
 * @memberOf BX.Crm.Integration.Analytics
 */
export const Dictionary = Object.freeze({
	TOOL_CRM: 'crm',
	TOOL_AI: 'AI',

	CATEGORY_ENTITY_OPERATIONS: 'entity_operations',
	CATEGORY_CRM_OPERATIONS: 'crm_operations',
	CATEGORY_AI_OPERATIONS: 'ai_operations',
	CATEGORY_AUTOMATION_OPERATIONS: 'automation_operations',
	CATEGORY_KANBAN_OPERATIONS: 'kanban_operations',
	CATEGORY_POPUP_OPERATIONS: 'popup_operations',

	// region Event const
	EVENT_ENTITY_ADD_OPEN: 'entity_add_open',
	EVENT_ENTITY_ADD: 'entity_add',
	EVENT_ENTITY_CLOSE: 'entity_close',
	EVENT_ENTITY_COPY_OPEN: 'entity_copy_open',
	EVENT_ENTITY_COPY: 'entity_copy',
	EVENT_ENTITY_CONVERT: 'entity_convert',
	EVENT_ENTITY_CONVERT_BATCH: 'entity_convert_batch',
	EVENT_ENTITY_CONVERT_OPEN: 'entity_convert_open',
	EVENT_ENTITY_UPDATE: 'entity_update',

	EVENT_CALL_PARSING: 'call_parsing',
	EVENT_AUDIO_TO_TEXT: 'audio_to_text',
	EVENT_SUMMARY: 'summary',
	EVENT_EXTRACT_FIELDS: 'extract_fields',
	EVENT_CALL_ACTIVITY_WITH_AUDIO_RECORDING: 'activity_call_with_audio_recording',

	EVENT_AUTOMATION_CREATE: 'automation_create',
	EVENT_AUTOMATION_EDIT: 'automation_edit',
	EVENT_AUTOMATION_DELETE: 'automation_delete',

	EVENT_BLOCK_CLOSE: 'block_close',
	EVENT_BLOCK_ENABLE: 'block_enable',
	EVENT_BLOCK_LINK: 'block_link',
	// endregion

	// region Type const
	TYPE_MANUAL: 'manual',
	TYPE_AUTO: 'auto',
	TYPE_AUTOMATED_SOLUTION: 'automated_solution',
	TYPE_DYNAMIC: 'dynamic',
	TYPE_CONTACT_CENTER: 'contact_center',
	TYPE_ITEM_INDUSTRY: 'item_industry',
	TYPE_POPUP_AI_TRANSCRIPT: 'popup_ai_transcript',
	// endregion

	// region Section const
	SECTION_CRM: 'crm',
	SECTION_AUTOMATION: 'automation',
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
	SUB_SECTION_GRID_ROW_MENU: 'grid_row_menu',

	SUB_SECTION_KANBAN_DROPZONE: 'kanban_dropzone',
	SUB_SECTION_ACTION_BUTTON: 'action_button',

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
	ELEMENT_WON_BUTTON: 'won_button',
	ELEMENT_LOSE_BUTTON: 'lose_button',
	ELEMENT_CANCEL_BUTTON: 'cancel_button',
	ELEMENT_ESC_BUTTON: 'esc_button',
	ELEMENT_DELETE_BUTTON: 'delete_button',
	ELEMENT_GRID_PROGRESS_BAR: 'grid_progress_bar',
	ELEMENT_LOSE_COLUMN: 'lose_column',
	ELEMENT_GRID_GROUP_ACTIONS_WON_STAGE: 'grid_group_actions_won_stage',
	ELEMENT_GRID_GROUP_ACTIONS_LOSE_STAGE: 'grid_group_actions_lose_stage',
	ELEMENT_LOSE_TOP_ACTIONS: 'lose_top_actions',
	ELEMENT_WON_TOP_ACTIONS: 'won_top_actions',
	ELEMENT_DETAILS_PROGRESS_BAR: 'details_progress_bar',
	ELEMENT_SAVE_IS_REQUIRED_TO_PROCEED_POPUP: 'save_is_required_to_proceed_popup',
	ELEMENT_CLOSE_BUTTON: 'close_button',
	ELEMENT_HIDE_CONTACT_CENTER: 'hide_contact_center',
	ELEMENT_ENABLE_CONTACT_CENTER: 'enable_contact_center',
	ELEMENT_CONTACT_CENTER_MARKETPLACE: 'contact_center_marketplace',
	ELEMENT_CONTACT_CENTER_IMPORTEXCEL: 'contact_center_importexcel',
	ELEMENT_ITEM_CONTACT_CENTER: 'item_contact_center',
	ELEMENT_ITEM_INDUSTRY_BUTTON: 'item_industry_button',
	// endregion

	// region Status const
	STATUS_ATTEMPT: 'attempt',
	STATUS_SUCCESS: 'success',
	STATUS_ERROR: 'error',
	STATUS_CANCEL: 'cancel',

	STATUS_SUCCESS_FIELDS: 'success_fields',
	STATUS_SUCCESS_COMMENT: 'success_comment_only',
	STATUS_ERROR_NO_LIMITS: 'error_no_limits',
	STATUS_ERROR_AGREEMENT: 'error_agreement',
	STATUS_ERROR_LIMIT_DAILY: 'error_limit_daily',
	STATUS_ERROR_LIMIT_MONTHLY: 'error_limit_monthly',
	STATUS_ERROR_PROVIDER: 'error_provider',
	STATUS_ERROR_B24: 'error_b24',
	STATUS_ERROR_PERMISSIONS: 'error_permissions',
	// endregion
});
