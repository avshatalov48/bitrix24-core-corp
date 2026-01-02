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
	event: Dictionary.EVENT_ENTITY_CREATE,
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
	p2?: string,
	p3?: 'category_smartDocumentContact' | 'category_catalogContractorContact' | 'category_catalogContractorCompany',
	p4?: 'myCompany_1' | 'myCompany_0',
};

export type EntityCloseEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
	event: Dictionary.EVENT_ENTITY_COMPLETE,
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

export type EntityChangeStageEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
	event: Dictionary.EVENT_ENTITY_CHANGE_STAGE,
	type: 'lead' | 'deal' | 'dynamic',
	c_section: Dictionary.SECTION_LEAD
		| Dictionary.SECTION_DEAL
		| Dictionary.SECTION_DYNAMIC
	,
	c_sub_section: Dictionary.SUB_SECTION_LIST
		| Dictionary.SUB_SECTION_KANBAN
		| Dictionary.SUB_SECTION_DETAILS
	,
	c_element?: Dictionary.ELEMENT_STAGE_BAR_BUTTON
		| Dictionary.ELEMENT_GRID_ROW_CONTEXT_MENU
	,
	status?: EventStatus,
	p1: CrmMode,
	p2: number
};

export type WhatsAppFormEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_COMMUNICATION_OPERATIONS,
	event: Dictionary.EVENT_WA_UPDATE | Dictionary.EVENT_WA_POPUP,
	type: Dictionary.TYPE_WA_EDIT,
	c_section: Dictionary.SECTION_LEAD
		| Dictionary.SECTION_DEAL
		| Dictionary.SECTION_CONTACT
		| Dictionary.SECTION_COMPANY
		| Dictionary.SECTION_DYNAMIC
		| Dictionary.SECTION_CUSTOM
		| Dictionary.SECTION_MYCOMPANY
		| Dictionary.SECTION_SMART_DOCUMENT_CONTACT
		| Dictionary.SECTION_CATALOG_CONTRACTOR_CONTACT
	,
	c_sub_section: Dictionary.SUB_SECTION_LIST
		| Dictionary.SUB_SECTION_KANBAN
		| Dictionary.SUB_SECTION_ACTIVITIES
		| Dictionary.SUB_SECTION_DEADLINES
		| Dictionary.SUB_SECTION_DETAILS
		| Dictionary.SUB_SECTION_CALENDAR
	,
	c_element: Dictionary.ELEMENT_WA_PREVIEW
		| Dictionary.ELEMENT_WA_HELP
		| Dictionary.ELEMENT_WA_TEMPLATE_SELECTOR
		| Dictionary.ELEMENT_WA_TEMPLATE_OFFER
		| Dictionary.ELEMENT_WA_POPUP_GUIDE
		| Dictionary.ELEMENT_WA_POPUP_CLOSE
	,
	p1: CrmMode,
}

export type WhatsAppSendEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_COMMUNICATION_OPERATIONS,
	event: Dictionary.EVENT_WA_SEND | Dictionary.EVENT_WA_POPUP,
	type: Dictionary.TYPE_WA_EDIT,
	c_section: Dictionary.SECTION_LEAD
		| Dictionary.SECTION_DEAL
		| Dictionary.SECTION_CONTACT
		| Dictionary.SECTION_COMPANY
		| Dictionary.SECTION_DYNAMIC
		| Dictionary.SECTION_CUSTOM
		| Dictionary.SECTION_MYCOMPANY
		| Dictionary.SECTION_SMART_DOCUMENT_CONTACT
		| Dictionary.SECTION_CATALOG_CONTRACTOR_CONTACT
	,
	c_sub_section: Dictionary.SUB_SECTION_LIST
		| Dictionary.SUB_SECTION_KANBAN
		| Dictionary.SUB_SECTION_ACTIVITIES
		| Dictionary.SUB_SECTION_DEADLINES
		| Dictionary.SUB_SECTION_DETAILS
		| Dictionary.SUB_SECTION_CALENDAR
	,
	c_element: Dictionary.ELEMENT_WA_SEND
		| Dictionary.ELEMENT_WA_CANCEL
		| Dictionary.ELEMENT_WA_RESEND
		| Dictionary.ELEMENT_WA_NOTE
		| Dictionary.ELEMENT_WA_NOTE_PIN
	,
	p1: CrmMode,
	p2?: number,
	p3?: number,
	p4?: string
}

export type WhatsAppDeleteEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_COMMUNICATION_OPERATIONS,
	event: Dictionary.EVENT_WA_DELETE,
	type: Dictionary.TYPE_WA_ACTIVITY_DELETE,
	c_section: Dictionary.SECTION_LEAD
		| Dictionary.SECTION_DEAL
		| Dictionary.SECTION_CONTACT
		| Dictionary.SECTION_COMPANY
		| Dictionary.SECTION_DYNAMIC
		| Dictionary.SECTION_CUSTOM
		| Dictionary.SECTION_MYCOMPANY
		| Dictionary.SECTION_SMART_DOCUMENT_CONTACT
		| Dictionary.SECTION_CATALOG_CONTRACTOR_CONTACT
	,
	c_sub_section: Dictionary.SUB_SECTION_DETAILS,
	c_element: Dictionary.ELEMENT_WA_MESSAGE_DELETE
		| Dictionary.ELEMENT_WA_NOTE_DELETE
	,
	p1: CrmMode,
}

export type RepeatSaleBannerViewEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_BANNERS,
	event: Dictionary.EVENT_REPEAT_SALE_BANNER_VIEW,
	type: Dictionary.TYPE_REPEAT_SALE_BANNER_START
		| Dictionary.TYPE_REPEAT_SALE_BANNER_START_EMPTY
		| Dictionary.TYPE_REPEAT_SALE_BANNER_START_FORCE
		| Dictionary.TYPE_REPEAT_SALE_BANNER_STATISTICS
		| Dictionary.TYPE_REPEAT_SALE_BANNER_NULL
	,
	c_section: Dictionary.SECTION_DEAL | Dictionary.SECTION_CONTACT | Dictionary.SECTION_COMPANY,
	c_sub_section: Dictionary.SUB_SECTION_LIST
		| Dictionary.SUB_SECTION_KANBAN
		| Dictionary.SUB_SECTION_ACTIVITIES
		| Dictionary.SUB_SECTION_CALENDAR
		| Dictionary.SUB_SECTION_DEADLINES
		| Dictionary.SUB_SECTION_DETAILS
	,
	p1: CrmMode,
};

export type RepeatSaleBannerClickEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_BANNERS,
	event: Dictionary.EVENT_REPEAT_SALE_BANNER_CLICK,
	type: Dictionary.TYPE_REPEAT_SALE_BANNER_START
		| Dictionary.TYPE_REPEAT_SALE_BANNER_START_EMPTY
		| Dictionary.TYPE_REPEAT_SALE_BANNER_START_FORCE
		| Dictionary.TYPE_REPEAT_SALE_BANNER_STATISTICS
		| Dictionary.TYPE_REPEAT_SALE_BANNER_NULL
	,
	c_section: Dictionary.SECTION_DEAL | Dictionary.SECTION_CONTACT | Dictionary.SECTION_COMPANY,
	c_sub_section: Dictionary.SUB_SECTION_LIST
		| Dictionary.SUB_SECTION_KANBAN
		| Dictionary.SUB_SECTION_ACTIVITIES
		| Dictionary.SUB_SECTION_CALENDAR
		| Dictionary.SUB_SECTION_DEADLINES
		| Dictionary.SUB_SECTION_DETAILS
	,
	c_element: 'info_button' | 'start_flow' | 'change_period' | 'config' | 'feedback',
	p1: CrmMode,
	p3: 'period_0' | 'period_1' | 'period_2' | 'period_3', // @see \Bitrix\Crm\RepeatSale\Statistics\PeriodType
};

export type RepeatSaleBannerCloseEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_BANNERS,
	event: Dictionary.EVENT_REPEAT_SALE_BANNER_CLOSE,
	type: Dictionary.TYPE_REPEAT_SALE_BANNER_START
		| Dictionary.TYPE_REPEAT_SALE_BANNER_START_EMPTY
		| Dictionary.TYPE_REPEAT_SALE_BANNER_START_FORCE
		| Dictionary.TYPE_REPEAT_SALE_BANNER_STATISTICS
		| Dictionary.TYPE_REPEAT_SALE_BANNER_NULL
	,
	c_section: Dictionary.SECTION_DEAL | Dictionary.SECTION_CONTACT | Dictionary.SECTION_COMPANY,
	c_sub_section: Dictionary.SUB_SECTION_LIST
		| Dictionary.SUB_SECTION_KANBAN
		| Dictionary.SUB_SECTION_ACTIVITIES
		| Dictionary.SUB_SECTION_CALENDAR
		| Dictionary.SUB_SECTION_DEADLINES
		| Dictionary.SUB_SECTION_DETAILS
	,
	c_element: Dictionary.ELEMENT_CLOSE_BUTTON,
	p1: CrmMode,
};

export type RepeatSaleSegmentViewEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_EDITOR,
	event: Dictionary.EVENT_REPEAT_SALE_SEGMENT_VIEW,
	type: Dictionary.TYPE_REPEAT_SALE_SEGMENT,
	c_section: Dictionary.SECTION_DEAL | 'grid',
	p1: CrmMode,
};

export type RepeatSaleSegmentCancelEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_EDITOR,
	event: Dictionary.EVENT_REPEAT_SALE_SEGMENT_CANCEL,
	type: Dictionary.TYPE_REPEAT_SALE_SEGMENT,
	c_section: Dictionary.SECTION_DEAL | 'grid',
	p1: CrmMode,
};

export type RepeatSaleSegmentEditEvent = {
	tool: Dictionary.TOOL_CRM,
	category: Dictionary.CATEGORY_EDITOR,
	event: Dictionary.EVENT_REPEAT_SALE_SEGMENT_CANCEL,
	type: Dictionary.TYPE_REPEAT_SALE_SEGMENT,
	c_section: Dictionary.SECTION_DEAL | 'grid',
	p1: CrmMode,
};
