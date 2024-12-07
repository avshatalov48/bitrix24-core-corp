/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,crm_template_editor,bizproc_automation) {
	'use strict';

	const namespace = main_core.Reflection.namespace('BX.Crm.Activity');
	var _isRobot = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isRobot");
	var _documentType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentType");
	var _formName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("formName");
	var _editorWrapper = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("editorWrapper");
	var _templates = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("templates");
	var _placeholders = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("placeholders");
	var _templateId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("templateId");
	var _dialogItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialogItems");
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	var _onChangeTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onChangeTemplate");
	var _setOnBeforeSaveSettingsCallback = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setOnBeforeSaveSettingsCallback");
	var _setCurrentTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setCurrentTemplate");
	var _addTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addTemplate");
	var _loadTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadTemplate");
	var _insertTemplateEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("insertTemplateEditor");
	var _fillDialogItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fillDialogItems");
	var _prepareFilledPlaceholders = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareFilledPlaceholders");
	var _removeTemplateEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeTemplateEditor");
	var _onBeforeSaveRobotSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforeSaveRobotSettings");
	class CrmSendWhatsAllMessageActivity {
	  constructor(options) {
	    Object.defineProperty(this, _onBeforeSaveRobotSettings, {
	      value: _onBeforeSaveRobotSettings2
	    });
	    Object.defineProperty(this, _removeTemplateEditor, {
	      value: _removeTemplateEditor2
	    });
	    Object.defineProperty(this, _prepareFilledPlaceholders, {
	      value: _prepareFilledPlaceholders2
	    });
	    Object.defineProperty(this, _fillDialogItems, {
	      value: _fillDialogItems2
	    });
	    Object.defineProperty(this, _insertTemplateEditor, {
	      value: _insertTemplateEditor2
	    });
	    Object.defineProperty(this, _loadTemplate, {
	      value: _loadTemplate2
	    });
	    Object.defineProperty(this, _addTemplate, {
	      value: _addTemplate2
	    });
	    Object.defineProperty(this, _setCurrentTemplate, {
	      value: _setCurrentTemplate2
	    });
	    Object.defineProperty(this, _setOnBeforeSaveSettingsCallback, {
	      value: _setOnBeforeSaveSettingsCallback2
	    });
	    Object.defineProperty(this, _onChangeTemplate, {
	      value: _onChangeTemplate2
	    });
	    Object.defineProperty(this, _isRobot, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _formName, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _editorWrapper, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _templates, {
	      writable: true,
	      value: new Map()
	    });
	    Object.defineProperty(this, _placeholders, {
	      writable: true,
	      value: new Map()
	    });
	    Object.defineProperty(this, _templateId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dialogItems, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _items, {
	      writable: true,
	      value: {}
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _isRobot)[_isRobot] = main_core.Type.isBoolean(options.isRobot) ? options.isRobot : true;
	    if (!main_core.Type.isArrayFilled(options.documentType)) {
	      throw new Error('documentType must be filled array');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _documentType)[_documentType] = options.documentType;
	    if (!main_core.Type.isElementNode(options.editorWrapper)) {
	      throw new Error('editorWrapper must be HTMLDivElement');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _editorWrapper)[_editorWrapper] = options.editorWrapper;
	    if (!main_core.Type.isStringFilled(options.formName)) {
	      throw new Error('formName must be filled string');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _formName)[_formName] = options.formName;
	    const form = document.forms[babelHelpers.classPrivateFieldLooseBase(this, _formName)[_formName]];
	    if (!form || !form.template_id) {
	      throw new Error('form must have template_id element');
	    }
	    main_core.Event.bind(form.template_id, 'change', babelHelpers.classPrivateFieldLooseBase(this, _onChangeTemplate)[_onChangeTemplate].bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _setOnBeforeSaveSettingsCallback)[_setOnBeforeSaveSettingsCallback]();
	    babelHelpers.classPrivateFieldLooseBase(this, _fillDialogItems)[_fillDialogItems]();
	    if (main_core.Type.isPlainObject(options.currentTemplate) && main_core.Text.toInteger(options.currentTemplateId) > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _setCurrentTemplate)[_setCurrentTemplate](main_core.Text.toInteger(options.currentTemplateId), options.currentTemplate, options.currentPlaceholders);
	    }
	  }
	}
	function _onChangeTemplate2(event) {
	  const target = event.target;
	  if (!target) {
	    return;
	  }
	  const selectedOptions = target.selectedOptions;
	  const templateId = selectedOptions.item(0) ? main_core.Text.toInteger(selectedOptions.item(0).value) : 0;
	  babelHelpers.classPrivateFieldLooseBase(this, _templateId)[_templateId] = templateId;
	  if (templateId <= 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _removeTemplateEditor)[_removeTemplateEditor]();
	    return;
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _templates)[_templates].has(templateId)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _insertTemplateEditor)[_insertTemplateEditor](templateId);
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _loadTemplate)[_loadTemplate](templateId).then(({
	    data
	  }) => {
	    if (main_core.Type.isPlainObject(data)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _addTemplate)[_addTemplate](templateId, data);
	      babelHelpers.classPrivateFieldLooseBase(this, _insertTemplateEditor)[_insertTemplateEditor](templateId);
	    }
	  }).catch(response => console.error(response.errors));
	}
	function _setOnBeforeSaveSettingsCallback2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isRobot)[_isRobot]) {
	    return;
	  }
	  const designer = bizproc_automation.Designer.getInstance();
	  const dialog = designer ? designer.getRobotSettingsDialog() : null;
	  if (dialog != null && dialog.robot) {
	    dialog.robot.setOnBeforeSaveRobotSettings(babelHelpers.classPrivateFieldLooseBase(this, _onBeforeSaveRobotSettings)[_onBeforeSaveRobotSettings].bind(this));
	  }
	}
	function _setCurrentTemplate2(templateId, template, placeholders) {
	  babelHelpers.classPrivateFieldLooseBase(this, _templateId)[_templateId] = templateId;
	  babelHelpers.classPrivateFieldLooseBase(this, _addTemplate)[_addTemplate](templateId, template);
	  if (main_core.Type.isPlainObject(placeholders)) {
	    const templatePlaceholders = babelHelpers.classPrivateFieldLooseBase(this, _placeholders)[_placeholders].get(templateId);
	    Object.entries(placeholders).forEach(([key, value]) => {
	      if (Object.hasOwn(babelHelpers.classPrivateFieldLooseBase(this, _items)[_items], value)) {
	        templatePlaceholders.set(key, {
	          value,
	          parentTitle: babelHelpers.classPrivateFieldLooseBase(this, _items)[_items][value].parentTitle,
	          title: babelHelpers.classPrivateFieldLooseBase(this, _items)[_items][value].title
	        });
	      }
	    });
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _insertTemplateEditor)[_insertTemplateEditor](templateId);
	}
	function _addTemplate2(templateId, data) {
	  const content = main_core.Type.isString(data.content) ? data.content : '';
	  babelHelpers.classPrivateFieldLooseBase(this, _templates)[_templates].set(templateId, {
	    content: main_core.Text.encode(content).replaceAll('\n', '<br>'),
	    placeholders: main_core.Type.isPlainObject(data.placeholders) ? data.placeholders : {}
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _placeholders)[_placeholders].set(templateId, new Map());
	}
	function _loadTemplate2(templateId) {
	  return main_core.ajax.runAction('bizproc.activity.request', {
	    data: {
	      documentType: babelHelpers.classPrivateFieldLooseBase(this, _documentType)[_documentType],
	      activity: 'CrmSendWhatsAppMessageActivity',
	      params: {
	        template_id: templateId,
	        form_name: babelHelpers.classPrivateFieldLooseBase(this, _formName)[_formName]
	      }
	    }
	  });
	}
	function _insertTemplateEditor2(templateId) {
	  var _babelHelpers$classPr;
	  const data = babelHelpers.classPrivateFieldLooseBase(this, _templates)[_templates].get(templateId);
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _editorWrapper)[_editorWrapper], 'bizproc-automation-whats-app-message-activity-editor');
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _isRobot)[_isRobot] ? babelHelpers.classPrivateFieldLooseBase(this, _editorWrapper)[_editorWrapper].parentElement : (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _editorWrapper)[_editorWrapper].parentElement) == null ? void 0 : _babelHelpers$classPr.parentElement, '--hidden');
	  const editor = new crm_template_editor.Editor({
	    target: babelHelpers.classPrivateFieldLooseBase(this, _editorWrapper)[_editorWrapper],
	    onSelect: ({
	      id,
	      value,
	      parentTitle,
	      title
	    }) => {
	      const templatePlaceholders = babelHelpers.classPrivateFieldLooseBase(this, _placeholders)[_placeholders].get(templateId);
	      templatePlaceholders.set(id, {
	        value,
	        parentTitle,
	        title
	      });
	    },
	    dialogOptions: {
	      items: babelHelpers.classPrivateFieldLooseBase(this, _dialogItems)[_dialogItems],
	      entities: []
	    },
	    usePlaceholderProvider: false,
	    canUseFieldsDialog: true,
	    canUseFieldValueInput: false
	  });
	  editor.setPlaceholders(data.placeholders).setFilledPlaceholders(babelHelpers.classPrivateFieldLooseBase(this, _prepareFilledPlaceholders)[_prepareFilledPlaceholders](templateId)).setBody(data.content);
	}
	function _fillDialogItems2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isRobot)[_isRobot]) {
	    return;
	  }
	  const context = bizproc_automation.tryGetGlobalContext();
	  if (!context) {
	    return;
	  }
	  const designer = bizproc_automation.Designer.getInstance();
	  const component = designer ? designer.component : null;
	  const dialog = designer ? designer.getRobotSettingsDialog() : null;
	  const template = dialog ? dialog.template : null;
	  const triggerManager = component ? component.triggerManager : null;
	  const robotsWithReturnFields = template ? template.getRobotsWithReturnFields(dialog.robot) : [];
	  const manager = new bizproc_automation.SelectorItemsManager({
	    documentFields: bizproc_automation.enrichFieldsWithModifiers(context.document.getFields(), 'Document'),
	    documentTitle: context.document.title,
	    globalVariables: context.automationGlobals.globalVariables,
	    variables: template ? template.getVariables() : null,
	    globalConstants: context.automationGlobals.globalConstants,
	    constants: template ? template.getConstants() : null,
	    activityResultFields: robotsWithReturnFields.map(robot => {
	      return {
	        id: robot.getId(),
	        title: robot.getTitle(),
	        fields: bizproc_automation.enrichFieldsWithModifiers(robot.getReturnFieldsDescription(), robot.getId(), {
	          friendly: false,
	          printable: false,
	          server: false,
	          responsible: false,
	          shortLink: true
	        })
	      };
	    }),
	    triggerResultFields: triggerManager && template ? triggerManager.getReturnProperties(template.getStatusId()) : null,
	    useModifier: true
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _dialogItems)[_dialogItems] = manager.groupsWithChildren;
	  manager.items.forEach(field => {
	    babelHelpers.classPrivateFieldLooseBase(this, _items)[_items][field.id] = {
	      title: field.title,
	      parentTitle: field.supertitle
	    };
	  });
	}
	function _prepareFilledPlaceholders2(templateId) {
	  const placeholders = [];
	  const templatePlaceholders = babelHelpers.classPrivateFieldLooseBase(this, _placeholders)[_placeholders].get(templateId);
	  templatePlaceholders.forEach((data, key) => {
	    placeholders.push({
	      PLACEHOLDER_ID: key,
	      FIELD_NAME: data.value,
	      FIELD_ENTITY_TYPE: 'bp',
	      TITLE: data.title,
	      PARENT_TITLE: data.parentTitle
	    });
	  });
	  return placeholders;
	}
	function _removeTemplateEditor2() {
	  var _babelHelpers$classPr2;
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _editorWrapper)[_editorWrapper], 'bizproc-automation-whats-app-message-activity-editor');
	  main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _editorWrapper)[_editorWrapper]);
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _isRobot)[_isRobot] ? babelHelpers.classPrivateFieldLooseBase(this, _editorWrapper)[_editorWrapper].parentElement : (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _editorWrapper)[_editorWrapper].parentElement) == null ? void 0 : _babelHelpers$classPr2.parentElement, '--hidden');
	}
	function _onBeforeSaveRobotSettings2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _templateId)[_templateId] > 0) {
	    const placeholders = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _placeholders)[_placeholders].get(babelHelpers.classPrivateFieldLooseBase(this, _templateId)[_templateId]).forEach(({
	      value
	    }, key) => {
	      placeholders[key] = value;
	    });
	    return {
	      placeholders
	    };
	  }
	  return {};
	}
	namespace.CrmSendWhatsAllMessageActivity = CrmSendWhatsAllMessageActivity;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX,BX.Crm.Template,BX.Bizproc.Automation));
//# sourceMappingURL=script.js.map
