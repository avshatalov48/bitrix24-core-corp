import { Dictionary } from './dictionary';

// region General
export type EventStatus = Dictionary.STATUS_ATTEMPT
	| Dictionary.STATUS_SUCCESS
	| Dictionary.STATUS_ERROR
	| Dictionary.STATUS_CANCEL
;
// endregion

// region General, but CRM-specific
export type CrmMode = 'crmMode_simple' | 'crmMode_classic';
// endregion

// event structure can be a little bit different from time to time, but structures below should give you
// general understanding of their shapes

export type EntityAddEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
	event: Dictionary.EVENT_ENTITY_ADD,
	type: 'lead' | 'deal' | 'smart_invoice' | 'quote' | 'contact' | 'company' | 'dynamic',
	c_section: Dictionary.SECTION_LEAD
		| Dictionary.SECTION_DEAL
		| Dictionary.SECTION_SMART_INVOICE
		| Dictionary.SECTION_QUOTE
		| Dictionary.SECTION_CONTACT
		| Dictionary.SECTION_COMPANY
		| Dictionary.SECTION_DYNAMIC
		| Dictionary.SECTION_CUSTOM
		| Dictionary.SECTION_MYCOMPANY
		| Dictionary.SECTION_SMART_DOCUMENT_CONTACT
		| Dictionary.SECTION_CATALOG_CONTRACTOR_CONTACT
		| Dictionary.SECTION_CATALOG_CONTRACTOR_COMPANY
	,
	c_sub_section: Dictionary.SUB_SECTION_LIST
		| Dictionary.SUB_SECTION_KANBAN
		| Dictionary.SUB_SECTION_ACTIVITIES
		| Dictionary.SUB_SECTION_DEADLINES
		| Dictionary.SUB_SECTION_CALENDAR
		| Dictionary.SUB_SECTION_DETAILS
	,
	c_element?: Dictionary.ELEMENT_CREATE_BUTTON | Dictionary.ELEMENT_QUICK_BUTTON,
	status?: EventStatus,
	p1: CrmMode,
	p2?: 'category_smartDocumentContact' | 'category_catalogContractorContact' | 'category_catalogContractorCompany',
	p3?: 'myCompany_1' | 'myCompany_0',
};

export type EntityCloseEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
	event: Dictionary.EVENT_ENTITY_CLOSE,
	type: 'lead' | 'deal',
	c_section: Dictionary.SECTION_LEAD
		| Dictionary.SECTION_DEAL
	,
	c_sub_section: Dictionary.SUB_SECTION_LIST
		| Dictionary.SUB_SECTION_KANBAN
		| Dictionary.SUB_SECTION_KANBAN_DROPZONE
		| Dictionary.SUB_SECTION_DETAILS
	,
	c_element?: Dictionary.ELEMENT_WON_BUTTON
		| Dictionary.ELEMENT_LOSE_BUTTON
		| Dictionary.ELEMENT_CANCEL_BUTTON
	,
	status?: EventStatus,
	p1: CrmMode,
	p2: string
};

export type EntityConvertEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
	event: Dictionary.EVENT_ENTITY_CONVERT,
	type: 'deal' | 'smart_invoice' | 'quote' | 'contact' | 'company',
	c_section: Dictionary.SECTION_LEAD
		| Dictionary.SECTION_DEAL
		| Dictionary.SECTION_SMART_INVOICE
		| Dictionary.SECTION_QUOTE
	,
	c_sub_section: Dictionary.SUB_SECTION_LIST | Dictionary.SUB_SECTION_DETAILS,
	c_element?: Dictionary.ELEMENT_CONVERT_BUTTON
		| Dictionary.ELEMENT_TERMINATION_CONTROL
		| Dictionary.ELEMENT_CREATE_LINKED_ENTITY_BUTTON
		| Dictionary.ELEMENT_GRID_ROW_CONTEXT_MENU
	,
	status?: EventStatus,
	p1: CrmMode,
	p2: 'from_lead' | 'from_deal' | 'from_quote' | 'from_smartInvoice',
};

export type EntityConvertBatchEvent = EntityConvertEvent & {
	event: Dictionary.EVENT_ENTITY_CONVERT_BATCH,
};

export type AICallParsingEvent = {
	tool: Dictionary.TOOL_CRM | Dictionary.TOOL_AI,
	category: Dictionary.CATEGORY_CRM_OPERATIONS | Dictionary.CATEGORY_AI_OPERATIONS,
	event: Dictionary.EVENT_CALL_PARSING,
	type: Dictionary.TYPE_MANUAL | Dictionary.TYPE_AUTO,
	c_section: Dictionary.SECTION_CRM,
	c_sub_section: Dictionary.SUB_SECTION_DEAL | Dictionary.SUB_SECTION_LEAD,
	c_element: Dictionary.ELEMENT_COPILOT_BUTTON
		| Dictionary.ELEMENT_FEEDBACK_SEND
		| Dictionary.ELEMENT_FEEDBACK_REFUSED
		| Dictionary.ELEMENT_CONFLICT_ACCEPT_CHANGES
		| Dictionary.ELEMENT_CONFLICT_CANCEL_CHANGES
	,
	status: Dictionary.STATUS_SUCCESS
		| Dictionary.STATUS_SUCCESS_FIELDS
		| Dictionary.STATUS_SUCCESS_COMMENT
		| Dictionary.STATUS_ERROR_B24
		| Dictionary.STATUS_ERROR_PROVIDER
		| Dictionary.STATUS_ERROR_AGREEMENT
		| Dictionary.STATUS_ERROR_NO_LIMITS
		| Dictionary.STATUS_ERROR_LIMIT_MONTHLY
		| Dictionary.STATUS_ERROR_LIMIT_DAILY
	,
	p2: 'callDirection_incoming' | 'callDirection_outgoing',
	p4: 'duration_',
};

export type BlockCloseEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_KANBAN_OPERATIONS,
	event: Dictionary.EVENT_BLOCK_CLOSE,
	type: Dictionary.TYPE_CONTACT_CENTER | Dictionary.TYPE_ITEM_INDUSTRY,
	c_section: Dictionary.SECTION_LEAD | Dictionary.SECTION_DEAL,
	c_sub_section: Dictionary.SUB_SECTION_KANBAN | Dictionary.SUB_SECTION_GRID_ROW_MENU,
	c_element: Dictionary.ELEMENT_HIDE_CONTACT_CENTER | Dictionary.ELEMENT_CLOSE_BUTTON,
	p1: CrmMode,
}

export type BlockEnableEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_KANBAN_OPERATIONS,
	event: Dictionary.EVENT_BLOCK_ENABLE,
	type: Dictionary.TYPE_CONTACT_CENTER,
	c_section: Dictionary.SECTION_LEAD | Dictionary.SECTION_DEAL,
	c_sub_section: Dictionary.SUB_SECTION_GRID_ROW_MENU,
	c_element: Dictionary.ELEMENT_ENABLE_CONTACT_CENTER,
	p1: CrmMode,
}

export type BlockLinkEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_KANBAN_OPERATIONS,
	event: Dictionary.EVENT_BLOCK_LINK,
	type: Dictionary.TYPE_CONTACT_CENTER | Dictionary.TYPE_ITEM_INDUSTRY,
	c_section: Dictionary.SECTION_LEAD | Dictionary.SECTION_DEAL,
	c_sub_section: Dictionary.SUB_SECTION_KANBAN,
	c_element: Dictionary.ELEMENT_ITEM_INDUSTRY_BUTTON
		| Dictionary.ELEMENT_CONTACT_CENTER_MARKETPLACE
		| Dictionary.ELEMENT_CONTACT_CENTER_IMPORTEXCEL
		| Dictionary.ELEMENT_ITEM_CONTACT_CENTER
	,
	p1: CrmMode,
}
