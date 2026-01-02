/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Integration = this.BX.Crm.Integration || {};
(function (exports,main_core) {
	'use strict';

	/**
	 * @memberOf BX.Crm.Integration.Analytics
	 */
	const Dictionary = Object.freeze({
	  TOOL_CRM: 'crm',
	  TOOL_AI: 'AI',
	  CATEGORY_ENTITY_OPERATIONS: 'entity_operations',
	  CATEGORY_CRM_OPERATIONS: 'crm_operations',
	  CATEGORY_AI_OPERATIONS: 'ai_operations',
	  CATEGORY_AUTOMATION_OPERATIONS: 'automation_operations',
	  CATEGORY_KANBAN_OPERATIONS: 'kanban_operations',
	  CATEGORY_POPUP_OPERATIONS: 'popup_operations',
	  CATEGORY_COMMUNICATION_OPERATIONS: 'communication',
	  CATEGORY_BANNERS: 'banners',
	  CATEGORY_EDITOR: 'editor',
	  // region Event const
	  EVENT_ENTITY_ADD_OPEN: 'entity_add_open',
	  EVENT_ENTITY_ADD: 'entity_add',
	  EVENT_ENTITY_CREATE: 'entity_create',
	  EVENT_ENTITY_CLOSE: 'entity_close',
	  EVENT_ENTITY_COMPLETE: 'entity_complete',
	  EVENT_ENTITY_COPY_OPEN: 'entity_copy_open',
	  EVENT_ENTITY_COPY: 'entity_copy',
	  EVENT_ENTITY_CONVERT: 'entity_convert',
	  EVENT_ENTITY_CONVERT_BATCH: 'entity_convert_batch',
	  EVENT_ENTITY_CONVERT_OPEN: 'entity_convert_open',
	  EVENT_ENTITY_UPDATE: 'entity_update',
	  EVENT_ENTITY_EDIT: 'entity_edit',
	  EVENT_ENTITY_CHANGE_STAGE: 'entity_change_stage',
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
	  EVENT_WA_CONNECT: 'wa_connect',
	  EVENT_WA_POPUP: 'wa_popup',
	  EVENT_WA_UPDATE: 'wa_update',
	  EVENT_WA_SEND: 'wa_send',
	  EVENT_WA_TIMELINE: 'wa_timeline',
	  EVENT_WA_DELETE: 'wa_delete',
	  EVENT_REPEAT_SALE_BANNER_VIEW: 'banner_view',
	  EVENT_REPEAT_SALE_BANNER_CLICK: 'banner_click',
	  EVENT_REPEAT_SALE_BANNER_CLOSE: 'banner_close',
	  EVENT_REPEAT_SALE_SEGMENT_VIEW: 'view',
	  EVENT_REPEAT_SALE_SEGMENT_EDIT: 'edit',
	  EVENT_REPEAT_SALE_SEGMENT_CANCEL: 'cancel',
	  // endregion

	  // region Type const
	  TYPE_MANUAL: 'manual',
	  TYPE_AUTO: 'auto',
	  TYPE_AUTOMATED_SOLUTION: 'automated_solution',
	  TYPE_DYNAMIC: 'dynamic',
	  TYPE_CONTACT_CENTER: 'contact_center',
	  TYPE_ITEM_INDUSTRY: 'item_industry',
	  TYPE_POPUP_AI_TRANSCRIPT: 'popup_ai_transcript',
	  TYPE_WA_CONNECT: 'wa_connect',
	  TYPE_WA_EDIT: 'wa_edit',
	  TYPE_WA_ACTIVITY_CREATE: 'wa_activity_create',
	  TYPE_WA_ACTIVITY_DELETE: 'wa_activity_delete',
	  TYPE_REPEAT_SALE_SEGMENT: 'repeat_sale',
	  TYPE_REPEAT_SALE_BANNER_NULL: 'repeat_sale_null',
	  TYPE_REPEAT_SALE_BANNER_START_EMPTY: 'repeat_sale_start_empty',
	  TYPE_REPEAT_SALE_BANNER_START: 'repeat_sale_start',
	  TYPE_REPEAT_SALE_BANNER_START_FORCE: 'repeat_sale_force_start',
	  TYPE_REPEAT_SALE_BANNER_STATISTICS: 'repeat_sale_statistics',
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
	   * @see \Bitrix\Crm\Integration\Catalog\Contractor\CategoryRepository::CATALOG_CONTRACTOR_CONTACT
	   */
	  SECTION_CATALOG_CONTRACTOR_CONTACT: 'catalog_contractor_contact_section',
	  /**
	   * @see \Bitrix\Crm\Integration\Catalog\Contractor\CategoryRepository::CATALOG_CONTRACTOR_COMPANY
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
	  ELEMENT_STAGE_BAR_BUTTON: 'stage_bar_btn',
	  ELEMENT_STREAM_CONTENT_WHATSAPP: 'stream_content_wa',
	  ELEMENT_WA_PREVIEW: 'wa_preview',
	  ELEMENT_WA_HELP: 'wa_help_link',
	  ELEMENT_WA_TEMPLATE_SELECTOR: 'wa_template_selector',
	  ELEMENT_WA_TEMPLATE_OFFER: 'wa_template_offer',
	  ELEMENT_WA_POPUP_GUIDE: 'wa_popup_guide',
	  ELEMENT_WA_POPUP_CLOSE: 'wa_popup_close',
	  ELEMENT_WA_SEND: 'wa_send',
	  ELEMENT_WA_CANCEL: 'wa_cancel',
	  ELEMENT_WA_RESEND: 'wa_resend',
	  ELEMENT_WA_NOTE: 'wa_note',
	  ELEMENT_WA_NOTE_PIN: 'wa_note_pin',
	  ELEMENT_WA_MESSAGE_DELETE: 'wa_message_delete',
	  ELEMENT_WA_NOTE_DELETE: 'wa_note_delete',
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
	  STATUS_ERROR_PERMISSIONS: 'error_permissions'
	  // endregion
	});

	let extensionSettings = null;
	function getAnalyticsEntityType(entityType) {
	  let entityTypeName = null;
	  if (BX.CrmEntityType.isDefined(entityType)) {
	    entityTypeName = BX.CrmEntityType.resolveName(entityType);
	  } else if (BX.CrmEntityType.isDefinedByName(entityType)) {
	    entityTypeName = entityType;
	  }
	  if (!main_core.Type.isStringFilled(entityTypeName)) {
	    return null;
	  }
	  if (BX.CrmEntityType.isDynamicTypeByName(entityTypeName)) {
	    return 'dynamic';
	  }
	  return entityTypeName.toLowerCase();
	}
	function getCrmMode() {
	  if (!extensionSettings) {
	    extensionSettings = main_core.Extension.getSettings('crm.integration.analytics');
	  }
	  return `crmMode_${extensionSettings.get('crmMode', '').toLowerCase()}`;
	}
	function filterOutNilValues(object) {
	  const result = {};
	  Object.entries(object).forEach(([key, value]) => {
	    if (!main_core.Type.isNil(value)) {
	      result[key] = value;
	    }
	  });
	  return result;
	}

	var _entityType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _tool = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tool");
	var _category = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("category");
	var _type = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	var _element = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _activityId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("activityId");
	var _activityDirection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("activityDirection");
	var _status = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.AI
	 */
	class CallParsingEvent {
	  constructor() {
	    Object.defineProperty(this, _entityType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _tool, {
	      writable: true,
	      value: Dictionary.TOOL_AI
	    });
	    Object.defineProperty(this, _category, {
	      writable: true,
	      value: Dictionary.CATEGORY_CRM_OPERATIONS
	    });
	    Object.defineProperty(this, _type, {
	      writable: true,
	      value: Dictionary.TYPE_MANUAL
	    });
	    Object.defineProperty(this, _element, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _activityId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _activityDirection, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _status, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static createDefault(entityType, activityId, status) {
	    const self = new CallParsingEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _entityType)[_entityType] = entityType;
	    babelHelpers.classPrivateFieldLooseBase(self, _activityId)[_activityId] = main_core.Text.toInteger(activityId);
	    babelHelpers.classPrivateFieldLooseBase(self, _status)[_status] = status;
	    return self;
	  }
	  setTool(tool) {
	    babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool] = tool;
	    return this;
	  }
	  setCategory(category) {
	    babelHelpers.classPrivateFieldLooseBase(this, _category)[_category] = category;
	    return this;
	  }
	  setType(type) {
	    babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] = type;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element)[_element] = element;
	    return this;
	  }
	  setActivityDirection(direction) {
	    babelHelpers.classPrivateFieldLooseBase(this, _activityDirection)[_activityDirection] = direction;
	    return this;
	  }
	  buildData() {
	    const analyticsEntityType = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType]);
	    if (!analyticsEntityType) {
	      console.error('crm.integration.analytics: Unknown entity type');
	      return null;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _activityId)[_activityId] <= 0) {
	      console.error('crm.integration.analytics: invalid activity id');
	      return null;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _activityDirection)[_activityDirection] !== 'incoming' && babelHelpers.classPrivateFieldLooseBase(this, _activityDirection)[_activityDirection] !== 'outgoing') {
	      console.error('crm.integration.analytics: invalid activity direction', babelHelpers.classPrivateFieldLooseBase(this, _activityDirection)[_activityDirection]);
	      return null;
	    }
	    return filterOutNilValues({
	      tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	      category: babelHelpers.classPrivateFieldLooseBase(this, _category)[_category],
	      event: Dictionary.EVENT_CALL_PARSING,
	      type: babelHelpers.classPrivateFieldLooseBase(this, _type)[_type],
	      c_section: Dictionary.SECTION_CRM,
	      c_sub_section: analyticsEntityType,
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element)[_element],
	      status: babelHelpers.classPrivateFieldLooseBase(this, _status)[_status],
	      p1: getCrmMode(),
	      p2: `callDirection_${babelHelpers.classPrivateFieldLooseBase(this, _activityDirection)[_activityDirection]}`,
	      p5: `idCall_${babelHelpers.classPrivateFieldLooseBase(this, _activityId)[_activityId]}`
	    });
	  }
	}

	var _element$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _status$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
	var _id = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _typeIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("typeIds");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Automation.AutomatedSolution
	 */
	class CreateEvent {
	  constructor() {
	    Object.defineProperty(this, _element$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _status$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _typeIds, {
	      writable: true,
	      value: []
	    });
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$1)[_element$1] = element;
	    return this;
	  }
	  setStatus(status) {
	    babelHelpers.classPrivateFieldLooseBase(this, _status$1)[_status$1] = status;
	    return this;
	  }
	  setId(id) {
	    babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] = main_core.Text.toInteger(id);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] <= 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] = null;
	    }
	    return this;
	  }
	  setTypeIds(ids) {
	    if (main_core.Type.isArrayFilled(ids)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _typeIds)[_typeIds] = ids.map(id => main_core.Text.toInteger(id)).filter(id => id > 0).sort();
	    }
	    return this;
	  }
	  buildData() {
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_AUTOMATION_OPERATIONS,
	      event: Dictionary.EVENT_AUTOMATION_CREATE,
	      type: Dictionary.TYPE_AUTOMATED_SOLUTION,
	      c_section: Dictionary.SECTION_AUTOMATION,
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$1)[_element$1],
	      status: babelHelpers.classPrivateFieldLooseBase(this, _status$1)[_status$1],
	      p1: getCrmMode(),
	      p2: babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] > 0 ? `id_${babelHelpers.classPrivateFieldLooseBase(this, _id)[_id]}` : null,
	      p3: main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _typeIds)[_typeIds]) ? `typeIds_${babelHelpers.classPrivateFieldLooseBase(this, _typeIds)[_typeIds].join(',')}` : null
	    });
	  }
	}

	var _element$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _status$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
	var _id$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Automation.AutomatedSolution
	 */
	class DeleteEvent {
	  constructor() {
	    Object.defineProperty(this, _element$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _status$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _id$1, {
	      writable: true,
	      value: void 0
	    });
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$2)[_element$2] = element;
	    return this;
	  }
	  setStatus(status) {
	    babelHelpers.classPrivateFieldLooseBase(this, _status$2)[_status$2] = status;
	    return this;
	  }
	  setId(id) {
	    babelHelpers.classPrivateFieldLooseBase(this, _id$1)[_id$1] = main_core.Text.toInteger(id);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _id$1)[_id$1] <= 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _id$1)[_id$1] = null;
	    }
	    return this;
	  }
	  buildData() {
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_AUTOMATION_OPERATIONS,
	      event: Dictionary.EVENT_AUTOMATION_DELETE,
	      type: Dictionary.TYPE_AUTOMATED_SOLUTION,
	      c_section: Dictionary.SECTION_AUTOMATION,
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$2)[_element$2],
	      status: babelHelpers.classPrivateFieldLooseBase(this, _status$2)[_status$2],
	      p1: getCrmMode(),
	      p2: babelHelpers.classPrivateFieldLooseBase(this, _id$1)[_id$1] > 0 ? `id_${babelHelpers.classPrivateFieldLooseBase(this, _id$1)[_id$1]}` : null
	    });
	  }
	}

	var _element$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _status$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
	var _id$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _typeIds$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("typeIds");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Automation.AutomatedSolution
	 */
	class EditEvent {
	  constructor() {
	    Object.defineProperty(this, _element$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _status$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _id$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _typeIds$1, {
	      writable: true,
	      value: []
	    });
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$3)[_element$3] = element;
	    return this;
	  }
	  setStatus(status) {
	    babelHelpers.classPrivateFieldLooseBase(this, _status$3)[_status$3] = status;
	    return this;
	  }
	  setId(id) {
	    babelHelpers.classPrivateFieldLooseBase(this, _id$2)[_id$2] = main_core.Text.toInteger(id);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _id$2)[_id$2] <= 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _id$2)[_id$2] = null;
	    }
	    return this;
	  }
	  setTypeIds(ids) {
	    if (main_core.Type.isArrayFilled(ids)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _typeIds$1)[_typeIds$1] = ids.map(id => main_core.Text.toInteger(id)).filter(id => id > 0).sort();
	    }
	    return this;
	  }
	  buildData() {
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_AUTOMATION_OPERATIONS,
	      event: Dictionary.EVENT_AUTOMATION_EDIT,
	      type: Dictionary.TYPE_AUTOMATED_SOLUTION,
	      c_section: Dictionary.SECTION_AUTOMATION,
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$3)[_element$3],
	      status: babelHelpers.classPrivateFieldLooseBase(this, _status$3)[_status$3],
	      p1: getCrmMode(),
	      p2: babelHelpers.classPrivateFieldLooseBase(this, _id$2)[_id$2] > 0 ? `id_${babelHelpers.classPrivateFieldLooseBase(this, _id$2)[_id$2]}` : null,
	      p3: main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _typeIds$1)[_typeIds$1]) ? `typeIds_${babelHelpers.classPrivateFieldLooseBase(this, _typeIds$1)[_typeIds$1].join(',')}` : null
	    });
	  }
	}

	var _element$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _status$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
	var _isExternal = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isExternal");
	var _id$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _preset = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("preset");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Automation.Type
	 */
	class CreateEvent$1 {
	  constructor() {
	    Object.defineProperty(this, _element$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _status$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isExternal, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _id$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _preset, {
	      writable: true,
	      value: void 0
	    });
	  }
	  setIsExternal(isExternal) {
	    babelHelpers.classPrivateFieldLooseBase(this, _isExternal)[_isExternal] = Boolean(isExternal);
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$4)[_element$4] = element;
	    return this;
	  }
	  setStatus(status) {
	    babelHelpers.classPrivateFieldLooseBase(this, _status$4)[_status$4] = status;
	    return this;
	  }
	  setId(id) {
	    babelHelpers.classPrivateFieldLooseBase(this, _id$3)[_id$3] = main_core.Text.toInteger(id);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _id$3)[_id$3] <= 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _id$3)[_id$3] = null;
	    }
	    return this;
	  }
	  setPreset(presetId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _preset)[_preset] = String(presetId);
	    return this;
	  }
	  buildData() {
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_AUTOMATION_OPERATIONS,
	      event: Dictionary.EVENT_AUTOMATION_CREATE,
	      type: Dictionary.TYPE_DYNAMIC,
	      c_section: babelHelpers.classPrivateFieldLooseBase(this, _isExternal)[_isExternal] ? Dictionary.SECTION_AUTOMATION : Dictionary.SECTION_CRM,
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$4)[_element$4],
	      status: babelHelpers.classPrivateFieldLooseBase(this, _status$4)[_status$4],
	      p1: getCrmMode(),
	      p2: babelHelpers.classPrivateFieldLooseBase(this, _id$3)[_id$3] > 0 ? `id_${babelHelpers.classPrivateFieldLooseBase(this, _id$3)[_id$3]}` : null,
	      p4: main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _preset)[_preset]) ? `preset_${babelHelpers.classPrivateFieldLooseBase(this, _preset)[_preset]}` : null
	    });
	  }
	}

	var _subSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _status$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
	var _isExternal$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isExternal");
	var _element$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _id$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Automation.Type
	 */
	class DeleteEvent$1 {
	  constructor() {
	    Object.defineProperty(this, _subSection, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _status$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isExternal$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _id$4, {
	      writable: true,
	      value: void 0
	    });
	  }
	  setIsExternal(isExternal) {
	    babelHelpers.classPrivateFieldLooseBase(this, _isExternal$1)[_isExternal$1] = Boolean(isExternal);
	    return this;
	  }
	  setSubSection(subSection) {
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection)[_subSection] = main_core.Type.isNil(subSection) ? null : String(subSection);
	    return this;
	  }
	  setStatus(status) {
	    babelHelpers.classPrivateFieldLooseBase(this, _status$5)[_status$5] = status;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$5)[_element$5] = element;
	    return this;
	  }
	  setId(id) {
	    babelHelpers.classPrivateFieldLooseBase(this, _id$4)[_id$4] = main_core.Text.toInteger(id);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _id$4)[_id$4] <= 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _id$4)[_id$4] = null;
	    }
	    return this;
	  }
	  buildData() {
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_AUTOMATION_OPERATIONS,
	      event: Dictionary.EVENT_AUTOMATION_DELETE,
	      type: Dictionary.TYPE_DYNAMIC,
	      c_section: babelHelpers.classPrivateFieldLooseBase(this, _isExternal$1)[_isExternal$1] ? Dictionary.SECTION_AUTOMATION : Dictionary.SECTION_CRM,
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection)[_subSection],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$5)[_element$5],
	      status: babelHelpers.classPrivateFieldLooseBase(this, _status$5)[_status$5],
	      p1: getCrmMode(),
	      p2: babelHelpers.classPrivateFieldLooseBase(this, _id$4)[_id$4] > 0 ? `id_${babelHelpers.classPrivateFieldLooseBase(this, _id$4)[_id$4]}` : null
	    });
	  }
	}

	var _subSection$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _element$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _status$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
	var _isExternal$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isExternal");
	var _id$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _preset$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("preset");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Automation.Type
	 */
	class EditEvent$1 {
	  constructor() {
	    Object.defineProperty(this, _subSection$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$6, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _status$6, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isExternal$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _id$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _preset$1, {
	      writable: true,
	      value: void 0
	    });
	  }
	  setIsExternal(isExternal) {
	    babelHelpers.classPrivateFieldLooseBase(this, _isExternal$2)[_isExternal$2] = Boolean(isExternal);
	    return this;
	  }
	  setSubSection(subSection) {
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection$1)[_subSection$1] = String(subSection);
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$6)[_element$6] = element;
	    return this;
	  }
	  setStatus(status) {
	    babelHelpers.classPrivateFieldLooseBase(this, _status$6)[_status$6] = status;
	    return this;
	  }
	  setId(id) {
	    babelHelpers.classPrivateFieldLooseBase(this, _id$5)[_id$5] = main_core.Text.toInteger(id);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _id$5)[_id$5] <= 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _id$5)[_id$5] = null;
	    }
	    return this;
	  }
	  setPreset(presetId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _preset$1)[_preset$1] = String(presetId);
	    return this;
	  }
	  buildData() {
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_AUTOMATION_OPERATIONS,
	      event: Dictionary.EVENT_AUTOMATION_EDIT,
	      type: Dictionary.TYPE_DYNAMIC,
	      c_section: babelHelpers.classPrivateFieldLooseBase(this, _isExternal$2)[_isExternal$2] ? Dictionary.SECTION_AUTOMATION : Dictionary.SECTION_CRM,
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$1)[_subSection$1],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$6)[_element$6],
	      status: babelHelpers.classPrivateFieldLooseBase(this, _status$6)[_status$6],
	      p1: getCrmMode(),
	      p2: babelHelpers.classPrivateFieldLooseBase(this, _id$5)[_id$5] > 0 ? `id_${babelHelpers.classPrivateFieldLooseBase(this, _id$5)[_id$5]}` : null,
	      p4: main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _preset$1)[_preset$1]) ? `preset_${babelHelpers.classPrivateFieldLooseBase(this, _preset$1)[_preset$1]}` : null
	    });
	  }
	}

	var _entityType$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _subSection$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _element$7 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _type$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Block
	 */
	class CloseEvent {
	  constructor() {
	    Object.defineProperty(this, _entityType$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _subSection$2, {
	      writable: true,
	      value: Dictionary.SUB_SECTION_KANBAN
	    });
	    Object.defineProperty(this, _element$7, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _type$1, {
	      writable: true,
	      value: Dictionary.TYPE_CONTACT_CENTER
	    });
	  }
	  static createDefault(entityType) {
	    const self = new CloseEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _entityType$1)[_entityType$1] = entityType;
	    return self;
	  }
	  setSubSection(subSection) {
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection$2)[_subSection$2] = subSection;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$7)[_element$7] = element;
	    return this;
	  }
	  setType(type) {
	    babelHelpers.classPrivateFieldLooseBase(this, _type$1)[_type$1] = type;
	    return this;
	  }
	  buildData() {
	    const type = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _entityType$1)[_entityType$1]);
	    if (!type) {
	      console.error('crm.integration.analytics: Unknown entity type');
	      return null;
	    }
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_KANBAN_OPERATIONS,
	      event: Dictionary.EVENT_BLOCK_CLOSE,
	      type: babelHelpers.classPrivateFieldLooseBase(this, _type$1)[_type$1],
	      c_section: `${type}_section`,
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$2)[_subSection$2],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$7)[_element$7],
	      p1: getCrmMode()
	    });
	  }
	}

	var _entityType$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _subSection$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _element$8 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _type$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Block
	 */
	class EnableEvent {
	  constructor() {
	    Object.defineProperty(this, _entityType$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _subSection$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$8, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _type$2, {
	      writable: true,
	      value: Dictionary.TYPE_CONTACT_CENTER
	    });
	  }
	  static createDefault(entityType) {
	    const self = new EnableEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _entityType$2)[_entityType$2] = entityType;
	    return self;
	  }
	  setSubSection(subSection) {
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection$3)[_subSection$3] = subSection;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$8)[_element$8] = element;
	    return this;
	  }
	  setType(type) {
	    babelHelpers.classPrivateFieldLooseBase(this, _type$2)[_type$2] = type;
	    return this;
	  }
	  buildData() {
	    const type = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _entityType$2)[_entityType$2]);
	    if (!type) {
	      console.error('crm.integration.analytics: Unknown entity type');
	      return null;
	    }
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_KANBAN_OPERATIONS,
	      event: Dictionary.EVENT_BLOCK_ENABLE,
	      type: babelHelpers.classPrivateFieldLooseBase(this, _type$2)[_type$2],
	      c_section: `${type}_section`,
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$3)[_subSection$3],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$8)[_element$8],
	      p1: getCrmMode()
	    });
	  }
	}

	var _entityType$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _element$9 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _type$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Block
	 */
	class LinkEvent {
	  constructor() {
	    Object.defineProperty(this, _entityType$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$9, {
	      writable: true,
	      value: Dictionary.ELEMENT_ITEM_CONTACT_CENTER
	    });
	    Object.defineProperty(this, _type$3, {
	      writable: true,
	      value: Dictionary.TYPE_CONTACT_CENTER
	    });
	  }
	  static createDefault(entityType) {
	    const self = new LinkEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _entityType$3)[_entityType$3] = entityType;
	    return self;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$9)[_element$9] = element;
	    return this;
	  }
	  setType(type) {
	    babelHelpers.classPrivateFieldLooseBase(this, _type$3)[_type$3] = type;
	    return this;
	  }
	  buildData() {
	    const type = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _entityType$3)[_entityType$3]);
	    if (!type) {
	      console.error('crm.integration.analytics: Unknown entity type');
	      return null;
	    }
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_KANBAN_OPERATIONS,
	      event: Dictionary.EVENT_BLOCK_LINK,
	      type: babelHelpers.classPrivateFieldLooseBase(this, _type$3)[_type$3],
	      c_section: `${type}_section`,
	      c_sub_section: Dictionary.SUB_SECTION_KANBAN,
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$9)[_element$9],
	      p1: getCrmMode()
	    });
	  }
	}

	var _entityType$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _subSection$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _element$a = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Entity
	 */
	class AddEvent {
	  constructor() {
	    Object.defineProperty(this, _entityType$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _subSection$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$a, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static createDefault(entityType) {
	    const self = new AddEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _entityType$4)[_entityType$4] = entityType;
	    return self;
	  }
	  setSubSection(subSection) {
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection$4)[_subSection$4] = subSection;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$a)[_element$a] = element;
	    return this;
	  }
	  buildData() {
	    const type = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _entityType$4)[_entityType$4]);
	    if (!type) {
	      console.error('crm.integration.analytics: Unknown entity type');
	      return null;
	    }
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
	      event: Dictionary.EVENT_ENTITY_CREATE,
	      type,
	      c_section: `${type}_section`,
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$4)[_subSection$4],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$a)[_element$a],
	      p1: getCrmMode()
	    });
	  }
	}

	var _entityType$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _subSection$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _element$b = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _status$7 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
	var _countEntityChangeStage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("countEntityChangeStage");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Entity
	 */
	class ChangeStageEvent {
	  constructor() {
	    Object.defineProperty(this, _entityType$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _subSection$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$b, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _status$7, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _countEntityChangeStage, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static createDefault(entityType, countEntityChangeStage = 1) {
	    const self = new ChangeStageEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _entityType$5)[_entityType$5] = entityType;
	    babelHelpers.classPrivateFieldLooseBase(self, _countEntityChangeStage)[_countEntityChangeStage] = countEntityChangeStage;
	    return self;
	  }
	  setSubSection(subSection) {
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection$5)[_subSection$5] = subSection;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$b)[_element$b] = element;
	    return this;
	  }
	  setStatus(status) {
	    babelHelpers.classPrivateFieldLooseBase(this, _status$7)[_status$7] = status;
	    return this;
	  }
	  buildData() {
	    const type = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _entityType$5)[_entityType$5]);
	    if (!type) {
	      console.error('crm.integration.analytics: Unknown entity type');
	      return null;
	    }
	    const analyticsData = {
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
	      event: Dictionary.EVENT_ENTITY_CHANGE_STAGE,
	      type,
	      c_section: `${type}_section`,
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$5)[_subSection$5],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$b)[_element$b],
	      status: babelHelpers.classPrivateFieldLooseBase(this, _status$7)[_status$7],
	      p1: getCrmMode()
	    };
	    if (babelHelpers.classPrivateFieldLooseBase(this, _countEntityChangeStage)[_countEntityChangeStage] > 1) {
	      analyticsData.p2 = `entityCount_${babelHelpers.classPrivateFieldLooseBase(this, _countEntityChangeStage)[_countEntityChangeStage]}`;
	    }
	    return filterOutNilValues(analyticsData);
	  }
	}

	var _entityType$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _subSection$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _element$c = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _entityId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Entity
	 */
	class CloseEvent$1 {
	  constructor() {
	    Object.defineProperty(this, _entityType$6, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _subSection$6, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$c, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityId, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static createDefault(entityType, entityId) {
	    const self = new CloseEvent$1();
	    babelHelpers.classPrivateFieldLooseBase(self, _entityType$6)[_entityType$6] = entityType;
	    babelHelpers.classPrivateFieldLooseBase(self, _entityId)[_entityId] = entityId;
	    return self;
	  }
	  setSubSection(subSection) {
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection$6)[_subSection$6] = subSection;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$c)[_element$c] = element;
	    return this;
	  }
	  buildData() {
	    const type = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _entityType$6)[_entityType$6]);
	    if (!type) {
	      console.error('crm.integration.analytics: Unknown entity type');
	      return null;
	    }
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
	      event: Dictionary.EVENT_ENTITY_COMPLETE,
	      type,
	      c_section: `${type}_section`,
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$6)[_subSection$6],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$c)[_element$c],
	      p1: getCrmMode(),
	      p2: babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId]
	    });
	  }
	}

	var _srcEntityType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("srcEntityType");
	var _dstEntityType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dstEntityType");
	var _section = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("section");
	var _subSection$7 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _element$d = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _status$8 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Entity
	 */
	class ConvertBatchEvent {
	  constructor() {
	    Object.defineProperty(this, _srcEntityType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dstEntityType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _section, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _subSection$7, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$d, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _status$8, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static createDefault(srcEntityType, dstEntityType) {
	    const self = new ConvertBatchEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _srcEntityType)[_srcEntityType] = srcEntityType;
	    babelHelpers.classPrivateFieldLooseBase(self, _dstEntityType)[_dstEntityType] = dstEntityType;
	    return self;
	  }
	  setSection(section) {
	    babelHelpers.classPrivateFieldLooseBase(this, _section)[_section] = section;
	    return this;
	  }
	  setSubSection(subSection) {
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection$7)[_subSection$7] = subSection;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$d)[_element$d] = element;
	    return this;
	  }
	  setStatus(status) {
	    babelHelpers.classPrivateFieldLooseBase(this, _status$8)[_status$8] = status;
	    return this;
	  }
	  buildData() {
	    const srcType = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _srcEntityType)[_srcEntityType]);
	    const dstType = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _dstEntityType)[_dstEntityType]);
	    if (!srcType || !dstType) {
	      console.error('crm.integration.analytics: Unknown entity type');
	      return null;
	    }
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
	      event: Dictionary.EVENT_ENTITY_CONVERT_BATCH,
	      type: dstType,
	      c_section: babelHelpers.classPrivateFieldLooseBase(this, _section)[_section],
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$7)[_subSection$7],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$d)[_element$d],
	      status: babelHelpers.classPrivateFieldLooseBase(this, _status$8)[_status$8],
	      p1: getCrmMode(),
	      p2: `from_${main_core.Text.toCamelCase(srcType)}`
	    });
	  }
	}

	var _srcEntityType$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("srcEntityType");
	var _dstEntityType$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dstEntityType");
	var _section$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("section");
	var _subSection$8 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _element$e = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _status$9 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Entity
	 */
	class ConvertEvent {
	  constructor() {
	    Object.defineProperty(this, _srcEntityType$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dstEntityType$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _section$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _subSection$8, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$e, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _status$9, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static createDefault(srcEntityType, dstEntityType) {
	    const self = new ConvertEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _srcEntityType$1)[_srcEntityType$1] = srcEntityType;
	    babelHelpers.classPrivateFieldLooseBase(self, _dstEntityType$1)[_dstEntityType$1] = dstEntityType;
	    return self;
	  }
	  setSection(section) {
	    babelHelpers.classPrivateFieldLooseBase(this, _section$1)[_section$1] = section;
	    return this;
	  }
	  setSubSection(subSection) {
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection$8)[_subSection$8] = subSection;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$e)[_element$e] = element;
	    return this;
	  }
	  setStatus(status) {
	    babelHelpers.classPrivateFieldLooseBase(this, _status$9)[_status$9] = status;
	    return this;
	  }
	  buildData() {
	    const srcType = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _srcEntityType$1)[_srcEntityType$1]);
	    const dstType = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _dstEntityType$1)[_dstEntityType$1]);
	    if (!srcType || !dstType) {
	      console.error('crm.integration.analytics: Unknown entity type');
	      return null;
	    }
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
	      event: Dictionary.EVENT_ENTITY_CONVERT,
	      type: dstType,
	      c_section: babelHelpers.classPrivateFieldLooseBase(this, _section$1)[_section$1],
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$8)[_subSection$8],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$e)[_element$e],
	      status: babelHelpers.classPrivateFieldLooseBase(this, _status$9)[_status$9],
	      p1: getCrmMode(),
	      p2: `from_${main_core.Text.toCamelCase(srcType)}`
	    });
	  }
	}

	var _type$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	var _section$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("section");
	var _subSection$9 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _element$f = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _period = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("period");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.RepeatSale.Banner
	 */
	class ClickEvent {
	  constructor() {
	    Object.defineProperty(this, _type$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _section$2, {
	      writable: true,
	      value: Dictionary.SECTION_DEAL
	    });
	    Object.defineProperty(this, _subSection$9, {
	      writable: true,
	      value: Dictionary.SUB_SECTION_KANBAN
	    });
	    Object.defineProperty(this, _element$f, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _period, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static createDefault(type, subSection) {
	    const self = new ClickEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _type$4)[_type$4] = type;
	    babelHelpers.classPrivateFieldLooseBase(self, _subSection$9)[_subSection$9] = subSection;
	    return self;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$f)[_element$f] = element;
	    return this;
	  }
	  setPeriod(period) {
	    babelHelpers.classPrivateFieldLooseBase(this, _period)[_period] = period;
	    return this;
	  }
	  setSection(section) {
	    babelHelpers.classPrivateFieldLooseBase(this, _section$2)[_section$2] = section;
	    return this;
	  }
	  buildData() {
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_BANNERS,
	      event: Dictionary.EVENT_REPEAT_SALE_BANNER_CLICK,
	      type: babelHelpers.classPrivateFieldLooseBase(this, _type$4)[_type$4],
	      c_section: babelHelpers.classPrivateFieldLooseBase(this, _section$2)[_section$2],
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$9)[_subSection$9],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$f)[_element$f],
	      p1: getCrmMode(),
	      p3: babelHelpers.classPrivateFieldLooseBase(this, _period)[_period] ? `period_${babelHelpers.classPrivateFieldLooseBase(this, _period)[_period]}` : null
	    });
	  }
	}

	var _type$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	var _section$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("section");
	var _subSection$a = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _element$g = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.RepeatSale.Banner
	 */
	class CloseEvent$2 {
	  constructor() {
	    Object.defineProperty(this, _type$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _section$3, {
	      writable: true,
	      value: Dictionary.SECTION_DEAL
	    });
	    Object.defineProperty(this, _subSection$a, {
	      writable: true,
	      value: Dictionary.SUB_SECTION_KANBAN
	    });
	    Object.defineProperty(this, _element$g, {
	      writable: true,
	      value: Dictionary.ELEMENT_CLOSE_BUTTON
	    });
	  }
	  static createDefault(type, subSection) {
	    const self = new CloseEvent$2();
	    babelHelpers.classPrivateFieldLooseBase(self, _type$5)[_type$5] = type;
	    babelHelpers.classPrivateFieldLooseBase(self, _subSection$a)[_subSection$a] = subSection;
	    return self;
	  }
	  setSection(section) {
	    babelHelpers.classPrivateFieldLooseBase(this, _section$3)[_section$3] = section;
	    return this;
	  }
	  buildData() {
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_BANNERS,
	      event: Dictionary.EVENT_REPEAT_SALE_BANNER_CLOSE,
	      type: babelHelpers.classPrivateFieldLooseBase(this, _type$5)[_type$5],
	      c_section: babelHelpers.classPrivateFieldLooseBase(this, _section$3)[_section$3],
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$a)[_subSection$a],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$g)[_element$g],
	      p1: getCrmMode()
	    });
	  }
	}

	var _section$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("section");
	var _subSection$b = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _type$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.RepeatSale.Banner
	 */
	class ViewEvent {
	  constructor() {
	    Object.defineProperty(this, _section$4, {
	      writable: true,
	      value: Dictionary.SECTION_DEAL
	    });
	    Object.defineProperty(this, _subSection$b, {
	      writable: true,
	      value: Dictionary.SUB_SECTION_KANBAN
	    });
	    Object.defineProperty(this, _type$6, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static createDefault(type, subSection) {
	    const self = new ViewEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _type$6)[_type$6] = type;
	    babelHelpers.classPrivateFieldLooseBase(self, _subSection$b)[_subSection$b] = subSection;
	    return self;
	  }
	  setSection(section) {
	    babelHelpers.classPrivateFieldLooseBase(this, _section$4)[_section$4] = section;
	    return this;
	  }
	  buildData() {
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_BANNERS,
	      event: Dictionary.EVENT_REPEAT_SALE_BANNER_VIEW,
	      type: babelHelpers.classPrivateFieldLooseBase(this, _type$6)[_type$6],
	      c_section: babelHelpers.classPrivateFieldLooseBase(this, _section$4)[_section$4],
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$b)[_subSection$b],
	      p1: getCrmMode()
	    });
	  }
	}

	var _section$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("section");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.RepeatSale.Segment
	 */
	class CancelEvent {
	  constructor() {
	    Object.defineProperty(this, _section$5, {
	      writable: true,
	      value: Dictionary.SECTION_DEAL
	    });
	  }
	  static createDefault(section) {
	    const self = new CancelEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _section$5)[_section$5] = section;
	    return self;
	  }
	  buildData() {
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_EDITOR,
	      event: Dictionary.EVENT_REPEAT_SALE_SEGMENT_CANCEL,
	      type: Dictionary.TYPE_REPEAT_SALE_SEGMENT,
	      c_section: babelHelpers.classPrivateFieldLooseBase(this, _section$5)[_section$5],
	      p1: getCrmMode()
	    });
	  }
	}

	var _section$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("section");
	var _isActivityTextChanged = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isActivityTextChanged");
	var _isEntityTitlePatternChanged = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isEntityTitlePatternChanged");
	var _isCopilotEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCopilotEnabled");
	var _segmentCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("segmentCode");
	var _getP = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getP5");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.RepeatSale.Segment
	 */
	class EditEvent$2 {
	  constructor() {
	    Object.defineProperty(this, _getP, {
	      value: _getP2
	    });
	    Object.defineProperty(this, _section$6, {
	      writable: true,
	      value: Dictionary.SECTION_DEAL
	    });
	    Object.defineProperty(this, _isActivityTextChanged, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isEntityTitlePatternChanged, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isCopilotEnabled, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _segmentCode, {
	      writable: true,
	      value: null
	    });
	  }
	  static createDefault(section) {
	    const self = new EditEvent$2();
	    babelHelpers.classPrivateFieldLooseBase(self, _section$6)[_section$6] = section;
	    return self;
	  }
	  setIsCopilotEnabled(isCopilotEnabled) {
	    babelHelpers.classPrivateFieldLooseBase(this, _isCopilotEnabled)[_isCopilotEnabled] = isCopilotEnabled;
	    return this;
	  }
	  setIsActivityTextChanged(isActivityTextChanged) {
	    babelHelpers.classPrivateFieldLooseBase(this, _isActivityTextChanged)[_isActivityTextChanged] = isActivityTextChanged;
	    return this;
	  }
	  setIsEntityTitlePatternChanged(isEntityTitlePatternChanged) {
	    babelHelpers.classPrivateFieldLooseBase(this, _isEntityTitlePatternChanged)[_isEntityTitlePatternChanged] = isEntityTitlePatternChanged;
	    return this;
	  }
	  setSegmentCode(code) {
	    babelHelpers.classPrivateFieldLooseBase(this, _segmentCode)[_segmentCode] = code;
	    return this;
	  }
	  buildData() {
	    let p1 = null;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isCopilotEnabled)[_isCopilotEnabled] === true) {
	      p1 = 'scenario-copilot-enable-on';
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _isCopilotEnabled)[_isCopilotEnabled] === false) {
	      p1 = 'scenario-copilot-enable-off';
	    }
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_EDITOR,
	      event: Dictionary.EVENT_REPEAT_SALE_SEGMENT_EDIT,
	      type: Dictionary.TYPE_REPEAT_SALE_SEGMENT,
	      c_section: babelHelpers.classPrivateFieldLooseBase(this, _section$6)[_section$6],
	      p1,
	      p2: babelHelpers.classPrivateFieldLooseBase(this, _isActivityTextChanged)[_isActivityTextChanged] ? 'scenario-text-deal-box' : null,
	      p3: babelHelpers.classPrivateFieldLooseBase(this, _isEntityTitlePatternChanged)[_isEntityTitlePatternChanged] ? 'scenario-deal-name' : null,
	      p5: babelHelpers.classPrivateFieldLooseBase(this, _getP)[_getP]()
	    });
	  }
	}
	function _getP2() {
	  switch (babelHelpers.classPrivateFieldLooseBase(this, _segmentCode)[_segmentCode]) {
	    case 'deal_activity_less_12_month':
	      return 'deal-activity-less-12m';
	    case 'deal_lost_more_12_month':
	      return 'deal-lost-more-12m';
	    case 'deal_every_year':
	      return 'deal-annual';
	    case 'deal_every_half_year':
	      return 'deal-semiannual';
	    case 'deal_every_month_year':
	      return 'deal-month-yr';
	    default:
	      return null;
	  }
	}

	var _section$7 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("section");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.RepeatSale.Segment
	 */
	class ViewEvent$1 {
	  constructor() {
	    Object.defineProperty(this, _section$7, {
	      writable: true,
	      value: Dictionary.SECTION_DEAL
	    });
	  }
	  static createDefault(section) {
	    const self = new ViewEvent$1();
	    babelHelpers.classPrivateFieldLooseBase(self, _section$7)[_section$7] = section;
	    return self;
	  }
	  buildData() {
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_EDITOR,
	      event: Dictionary.EVENT_REPEAT_SALE_SEGMENT_VIEW,
	      type: Dictionary.TYPE_REPEAT_SALE_SEGMENT,
	      c_section: babelHelpers.classPrivateFieldLooseBase(this, _section$7)[_section$7],
	      p1: getCrmMode()
	    });
	  }
	}

	var _entityType$7 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _element$h = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Communication
	 */
	class DeleteEvent$2 {
	  constructor() {
	    Object.defineProperty(this, _entityType$7, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$h, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static createDefault(entityType) {
	    const self = new DeleteEvent$2();
	    babelHelpers.classPrivateFieldLooseBase(self, _entityType$7)[_entityType$7] = entityType;
	    return self;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$h)[_element$h] = element;
	    return this;
	  }
	  buildData() {
	    const type = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _entityType$7)[_entityType$7]);
	    if (!type) {
	      console.error('crm.integration.analytics: Unknown entity type');
	      return null;
	    }
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_COMMUNICATION_OPERATIONS,
	      event: Dictionary.EVENT_WA_DELETE,
	      type: Dictionary.TYPE_WA_ACTIVITY_DELETE,
	      c_section: `${type}_section`,
	      c_sub_section: Dictionary.SUB_SECTION_DETAILS,
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$h)[_element$h],
	      p1: getCrmMode()
	    });
	  }
	}

	var _entityType$8 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _event = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("event");
	var _element$i = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _subSection$c = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Communication
	 */
	class FormEvent {
	  constructor() {
	    Object.defineProperty(this, _entityType$8, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _event, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$i, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _subSection$c, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static createDefault(entityType) {
	    const self = new FormEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _entityType$8)[_entityType$8] = entityType;
	    return self;
	  }
	  setSubSection(subSection) {
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection$c)[_subSection$c] = subSection;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$i)[_element$i] = element;
	    return this;
	  }
	  setEvent(event) {
	    babelHelpers.classPrivateFieldLooseBase(this, _event)[_event] = event;
	    return this;
	  }
	  buildData() {
	    const type = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _entityType$8)[_entityType$8]);
	    if (!type) {
	      console.error('crm.integration.analytics: Unknown entity type');
	      return null;
	    }
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_COMMUNICATION_OPERATIONS,
	      event: babelHelpers.classPrivateFieldLooseBase(this, _event)[_event],
	      type: Dictionary.TYPE_WA_EDIT,
	      c_section: `${type}_section`,
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$c)[_subSection$c],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$i)[_element$i],
	      p1: getCrmMode()
	    });
	  }
	}

	var _entityType$9 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _event$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("event");
	var _element$j = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _subSection$d = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _contactsCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("contactsCount");
	var _templateId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("templateId");
	var _resend = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resend");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Communication
	 */
	class SendEvent {
	  constructor() {
	    Object.defineProperty(this, _entityType$9, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _event$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$j, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _subSection$d, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _contactsCount, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _templateId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resend, {
	      writable: true,
	      value: false
	    });
	  }
	  static createDefault(entityType) {
	    const self = new SendEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _entityType$9)[_entityType$9] = entityType;
	    return self;
	  }
	  setSubSection(subSection) {
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection$d)[_subSection$d] = subSection;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$j)[_element$j] = element;
	    return this;
	  }
	  setEvent(event) {
	    babelHelpers.classPrivateFieldLooseBase(this, _event$1)[_event$1] = event;
	    return this;
	  }
	  setContactsCount(count) {
	    if (count === 'all') {
	      babelHelpers.classPrivateFieldLooseBase(this, _contactsCount)[_contactsCount] = 'all';
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _contactsCount)[_contactsCount] = main_core.Text.toInteger(count);
	      if (babelHelpers.classPrivateFieldLooseBase(this, _contactsCount)[_contactsCount] <= 0) {
	        babelHelpers.classPrivateFieldLooseBase(this, _contactsCount)[_contactsCount] = null;
	      }
	    }
	    return this;
	  }
	  setTemplateId(id) {
	    babelHelpers.classPrivateFieldLooseBase(this, _templateId)[_templateId] = main_core.Text.toInteger(id);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _templateId)[_templateId] <= 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _templateId)[_templateId] = null;
	    }
	    return this;
	  }
	  setResend() {
	    babelHelpers.classPrivateFieldLooseBase(this, _resend)[_resend] = true;
	    return this;
	  }
	  buildData() {
	    const type = getAnalyticsEntityType(babelHelpers.classPrivateFieldLooseBase(this, _entityType$9)[_entityType$9]);
	    if (!type) {
	      console.error('crm.integration.analytics: Unknown entity type');
	      return null;
	    }
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_CRM,
	      category: Dictionary.CATEGORY_COMMUNICATION_OPERATIONS,
	      event: babelHelpers.classPrivateFieldLooseBase(this, _event$1)[_event$1],
	      type: Dictionary.TYPE_WA_ACTIVITY_CREATE,
	      c_section: `${type}_section`,
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$d)[_subSection$d],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$j)[_element$j],
	      p1: getCrmMode(),
	      p2: babelHelpers.classPrivateFieldLooseBase(this, _contactsCount)[_contactsCount],
	      p3: babelHelpers.classPrivateFieldLooseBase(this, _templateId)[_templateId],
	      p4: babelHelpers.classPrivateFieldLooseBase(this, _resend)[_resend] ? 'resend' : null
	    });
	  }
	}

	const Builder = Object.freeze({
	  Entity: {
	    AddEvent: AddEvent,
	    ConvertEvent: ConvertEvent,
	    ConvertBatchEvent: ConvertBatchEvent,
	    CloseEvent: CloseEvent$1,
	    ChangeStageEvent: ChangeStageEvent
	  },
	  AI: {
	    CallParsingEvent: CallParsingEvent
	  },
	  Automation: {
	    AutomatedSolution: {
	      CreateEvent: CreateEvent,
	      EditEvent: EditEvent,
	      DeleteEvent: DeleteEvent
	    },
	    Type: {
	      CreateEvent: CreateEvent$1,
	      EditEvent: EditEvent$1,
	      DeleteEvent: DeleteEvent$1
	    }
	  },
	  Block: {
	    CloseEvent: CloseEvent,
	    EnableEvent: EnableEvent,
	    LinkEvent: LinkEvent
	  },
	  Communication: {
	    DeleteEvent: DeleteEvent$2,
	    FormEvent: FormEvent,
	    SendEvent: SendEvent
	  },
	  RepeatSale: {
	    Banner: {
	      ViewEvent: ViewEvent,
	      ClickEvent: ClickEvent,
	      CloseEvent: CloseEvent$2
	    },
	    Segment: {
	      ViewEvent: ViewEvent$1,
	      CancelEvent: CancelEvent,
	      EditEvent: EditEvent$2
	    }
	  }
	});

	exports.Builder = Builder;
	exports.Dictionary = Dictionary;
	exports.getCrmMode = getCrmMode;

}((this.BX.Crm.Integration.Analytics = this.BX.Crm.Integration.Analytics || {}),BX));
//# sourceMappingURL=analytics.bundle.js.map
