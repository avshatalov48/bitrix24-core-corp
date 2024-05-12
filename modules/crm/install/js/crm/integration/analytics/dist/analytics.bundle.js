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
	  STATUS_ERROR_B24: 'error_b24'
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
	var _type = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	var _element = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _activityId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("activityId");
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
	  setType(type) {
	    babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] = type;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element)[_element] = element;
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
	    return filterOutNilValues({
	      tool: Dictionary.TOOL_AI,
	      category: Dictionary.CATEGORY_CRM_OPERATIONS,
	      event: Dictionary.EVENT_CALL_PARSING,
	      type: babelHelpers.classPrivateFieldLooseBase(this, _type)[_type],
	      c_section: Dictionary.SECTION_CRM,
	      c_sub_section: analyticsEntityType,
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element)[_element],
	      status: babelHelpers.classPrivateFieldLooseBase(this, _status)[_status],
	      p1: getCrmMode(),
	      p5: `idCall_${babelHelpers.classPrivateFieldLooseBase(this, _activityId)[_activityId]}`
	    });
	  }
	}

	var _entityType$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _subSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _element$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	/**
	 * @memberof BX.Crm.Integration.Analytics.Builder.Entity
	 */
	class AddEvent {
	  constructor() {
	    Object.defineProperty(this, _entityType$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _subSection, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$1, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static createDefault(entityType) {
	    const self = new AddEvent();
	    babelHelpers.classPrivateFieldLooseBase(self, _entityType$1)[_entityType$1] = entityType;
	    return self;
	  }
	  setSubSection(subSection) {
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection)[_subSection] = subSection;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$1)[_element$1] = element;
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
	      category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
	      event: Dictionary.EVENT_ENTITY_ADD,
	      type,
	      c_section: `${type}_section`,
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection)[_subSection],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$1)[_element$1],
	      p1: getCrmMode()
	    });
	  }
	}

	var _srcEntityType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("srcEntityType");
	var _dstEntityType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dstEntityType");
	var _section = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("section");
	var _subSection$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _element$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _status$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
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
	    Object.defineProperty(this, _subSection$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _status$1, {
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
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection$1)[_subSection$1] = subSection;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$2)[_element$2] = element;
	    return this;
	  }
	  setStatus(status) {
	    babelHelpers.classPrivateFieldLooseBase(this, _status$1)[_status$1] = status;
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
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$1)[_subSection$1],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$2)[_element$2],
	      status: babelHelpers.classPrivateFieldLooseBase(this, _status$1)[_status$1],
	      p1: getCrmMode(),
	      p2: `from_${main_core.Text.toCamelCase(srcType)}`
	    });
	  }
	}

	var _srcEntityType$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("srcEntityType");
	var _dstEntityType$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dstEntityType");
	var _subSection$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subSection");
	var _element$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("element");
	var _status$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
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
	    Object.defineProperty(this, _subSection$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _element$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _status$2, {
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
	  setSubSection(subSection) {
	    babelHelpers.classPrivateFieldLooseBase(this, _subSection$2)[_subSection$2] = subSection;
	    return this;
	  }
	  setElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _element$3)[_element$3] = element;
	    return this;
	  }
	  setStatus(status) {
	    babelHelpers.classPrivateFieldLooseBase(this, _status$2)[_status$2] = status;
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
	      c_section: `${srcType}_section`,
	      c_sub_section: babelHelpers.classPrivateFieldLooseBase(this, _subSection$2)[_subSection$2],
	      c_element: babelHelpers.classPrivateFieldLooseBase(this, _element$3)[_element$3],
	      status: babelHelpers.classPrivateFieldLooseBase(this, _status$2)[_status$2],
	      p1: getCrmMode(),
	      p2: `from_${main_core.Text.toCamelCase(srcType)}`
	    });
	  }
	}

	const Builder = Object.freeze({
	  Entity: {
	    AddEvent: AddEvent,
	    ConvertEvent: ConvertEvent,
	    ConvertBatchEvent: ConvertBatchEvent
	  },
	  AI: {
	    CallParsingEvent: CallParsingEvent
	  }
	});

	exports.Builder = Builder;
	exports.Dictionary = Dictionary;

}((this.BX.Crm.Integration.Analytics = this.BX.Crm.Integration.Analytics || {}),BX));
//# sourceMappingURL=analytics.bundle.js.map
