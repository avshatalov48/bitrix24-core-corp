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
	tool: Dictionary.TOOL_AI,
	category: Dictionary.CATEGORY_CRM_OPERATIONS,
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
};
