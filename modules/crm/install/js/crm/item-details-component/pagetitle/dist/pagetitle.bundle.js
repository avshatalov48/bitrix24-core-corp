/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.ItemDetailsComponent = this.BX.Crm.ItemDetailsComponent || {};
(function (exports,crm_categoryModel,main_core,main_core_events,main_popup,ui_notification,ui_dialogs_messagebox) {
	'use strict';

	var _prefix = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prefix");
	var _format = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("format");
	class Logger {
	  constructor(prefix) {
	    Object.defineProperty(this, _format, {
	      value: _format2
	    });
	    Object.defineProperty(this, _prefix, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _prefix)[_prefix] = prefix;
	  }
	  warn(message, ...params) {
	    // eslint-disable-next-line no-console
	    console.warn(babelHelpers.classPrivateFieldLooseBase(this, _format)[_format](message), ...params);
	  }
	  error(message, ...params) {
	    // eslint-disable-next-line no-console
	    console.error(babelHelpers.classPrivateFieldLooseBase(this, _format)[_format](message), ...params);
	  }
	}
	function _format2(message) {
	  return `${babelHelpers.classPrivateFieldLooseBase(this, _prefix)[_prefix]}: ${message}`;
	}
	const logger = new Logger('crm.item-details-component.pagetitle');

	let _ = t => t,
	  _t;
	var _entityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _id = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _categoryId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("categoryId");
	var _categories = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("categories");
	var _editorGuid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("editorGuid");
	var _labelTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("labelTemplate");
	var _logger = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("logger");
	var _isProgress = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isProgress");
	var _onCategorySelectorClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCategorySelectorClick");
	var _onCategorySelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCategorySelect");
	var _changeCategory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("changeCategory");
	var _reloadPageWhenCategoryChanged = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("reloadPageWhenCategoryChanged");
	var _changeCategoryViaAjax = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("changeCategoryViaAjax");
	var _showUnsavedChangesDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showUnsavedChangesDialog");
	var _startProgress = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("startProgress");
	var _stopProgress = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("stopProgress");
	var _isEditorChanged = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isEditorChanged");
	var _getEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEditor");
	var _isNew = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isNew");
	var _getCurrentCategory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCurrentCategory");
	var _getAllCategoriesExceptCurrent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAllCategoriesExceptCurrent");
	/**
	 * Renders category change control, typically in the details card header near title.
	 * WARNING! On successful change, the page is reloaded with window.location.reload or window.location.href.
	 * It's a feature :)
	 *
	 * @memberOf BX.Crm.ItemDetailsComponent.PageTitle
	 *
	 * @mixes EventEmitter
	 *
	 * @emits BX.Crm.ItemDetailsComponent.CategoryChanger:onProgressStart
	 * @emits BX.Crm.ItemDetailsComponent.CategoryChanger:onProgressStop
	 */
	class CategoryChanger {
	  constructor({
	    entityTypeId,
	    entityId,
	    categoryId,
	    categories,
	    editorGuid,
	    labelTemplate
	  }) {
	    Object.defineProperty(this, _getAllCategoriesExceptCurrent, {
	      value: _getAllCategoriesExceptCurrent2
	    });
	    Object.defineProperty(this, _getCurrentCategory, {
	      value: _getCurrentCategory2
	    });
	    Object.defineProperty(this, _isNew, {
	      value: _isNew2
	    });
	    Object.defineProperty(this, _getEditor, {
	      value: _getEditor2
	    });
	    Object.defineProperty(this, _isEditorChanged, {
	      value: _isEditorChanged2
	    });
	    Object.defineProperty(this, _stopProgress, {
	      value: _stopProgress2
	    });
	    Object.defineProperty(this, _startProgress, {
	      value: _startProgress2
	    });
	    Object.defineProperty(this, _showUnsavedChangesDialog, {
	      value: _showUnsavedChangesDialog2
	    });
	    Object.defineProperty(this, _changeCategoryViaAjax, {
	      value: _changeCategoryViaAjax2
	    });
	    Object.defineProperty(this, _reloadPageWhenCategoryChanged, {
	      value: _reloadPageWhenCategoryChanged2
	    });
	    Object.defineProperty(this, _changeCategory, {
	      value: _changeCategory2
	    });
	    Object.defineProperty(this, _onCategorySelect, {
	      value: _onCategorySelect2
	    });
	    Object.defineProperty(this, _onCategorySelectorClick, {
	      value: _onCategorySelectorClick2
	    });
	    Object.defineProperty(this, _entityTypeId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _categoryId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _categories, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _editorGuid, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _labelTemplate, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _logger, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isProgress, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] = entityTypeId;
	    babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] = entityId;
	    babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId] = categoryId;
	    babelHelpers.classPrivateFieldLooseBase(this, _categories)[_categories] = categories;
	    babelHelpers.classPrivateFieldLooseBase(this, _editorGuid)[_editorGuid] = editorGuid;
	    babelHelpers.classPrivateFieldLooseBase(this, _labelTemplate)[_labelTemplate] = main_core.Type.isStringFilled(labelTemplate) ? labelTemplate : '#CATEGORY#';
	    babelHelpers.classPrivateFieldLooseBase(this, _logger)[_logger] = logger;
	    main_core_events.EventEmitter.makeObservable(this, 'BX.Crm.ItemDetailsComponent.CategoryChanger');
	  }
	  static renderToTarget(target, params) {
	    const changer = new CategoryChanger({
	      ...params,
	      categories: params.categories.map(category => new crm_categoryModel.CategoryModel(category))
	    });
	    const realTarget = main_core.Type.isDomNode(target) ? target : document.querySelector(target);
	    if (!realTarget) {
	      logger.warn('target not found, skip rendering');
	      return null;
	    }
	    main_core.Dom.append(changer.render(), realTarget);
	    return changer;
	  }
	  render() {
	    const current = babelHelpers.classPrivateFieldLooseBase(this, _getCurrentCategory)[_getCurrentCategory]();
	    if (!current) {
	      babelHelpers.classPrivateFieldLooseBase(this, _logger)[_logger].warn('current category not found, skip rendering');
	      return null;
	    }
	    const label = babelHelpers.classPrivateFieldLooseBase(this, _labelTemplate)[_labelTemplate].replace('#CATEGORY#', current.getName());
	    const element = main_core.Tag.render(_t || (_t = _`
			<div class="crm-details-pagetitle-legend-container">
				<a href="#" onclick="${0}">
					${0}
				</a>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onCategorySelectorClick)[_onCategorySelectorClick].bind(this), main_core.Text.encode(label));
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isNew)[_isNew]()) {
	      main_core.Dom.addClass(element, '--new');
	    }
	    return element;
	  }
	}
	function _onCategorySelectorClick2(event) {
	  const items = [];
	  for (const category of babelHelpers.classPrivateFieldLooseBase(this, _getAllCategoriesExceptCurrent)[_getAllCategoriesExceptCurrent]()) {
	    items.push({
	      text: category.getName(),
	      onclick: babelHelpers.classPrivateFieldLooseBase(this, _onCategorySelect)[_onCategorySelect].bind(this, category.getId())
	    });
	  }
	  main_popup.MenuManager.show({
	    id: `item-category-changer-${babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId]}-${babelHelpers.classPrivateFieldLooseBase(this, _id)[_id]}`,
	    bindElement: event.target,
	    items
	  });
	}
	function _onCategorySelect2(wantCategoryId) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isProgress)[_isProgress]) {
	    return;
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isEditorChanged)[_isEditorChanged]()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _showUnsavedChangesDialog)[_showUnsavedChangesDialog](babelHelpers.classPrivateFieldLooseBase(this, _changeCategory)[_changeCategory].bind(this, wantCategoryId));
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _changeCategory)[_changeCategory](wantCategoryId);
	  }
	}
	function _changeCategory2(wantCategoryId) {
	  babelHelpers.classPrivateFieldLooseBase(this, _startProgress)[_startProgress]();
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isNew)[_isNew]()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _reloadPageWhenCategoryChanged)[_reloadPageWhenCategoryChanged](wantCategoryId);
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _changeCategoryViaAjax)[_changeCategoryViaAjax](wantCategoryId);
	  }
	}
	function _reloadPageWhenCategoryChanged2(wantCategoryId) {
	  const url = new main_core.Uri(window.location.href);
	  // for factory-based details
	  url.setQueryParam('categoryId', wantCategoryId);
	  // for crm.deal.details
	  url.setQueryParam('category_id', wantCategoryId);
	  setTimeout(() => {
	    window.location.href = url.toString();
	    babelHelpers.classPrivateFieldLooseBase(this, _stopProgress)[_stopProgress]();
	  });
	}
	function _changeCategoryViaAjax2(wantCategoryId) {
	  main_core.ajax.runAction('crm.controller.item.update', {
	    analyticsLabel: 'crmItemDetailsChangeCategory',
	    data: {
	      entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId],
	      id: babelHelpers.classPrivateFieldLooseBase(this, _id)[_id],
	      fields: {
	        categoryId: wantCategoryId
	      }
	    }
	  }).then(() => {
	    setTimeout(() => {
	      window.location.reload();
	      babelHelpers.classPrivateFieldLooseBase(this, _stopProgress)[_stopProgress]();
	    });
	  }).catch(response => {
	    var _response$errors, _response$errors$;
	    babelHelpers.classPrivateFieldLooseBase(this, _logger)[_logger].warn('error changing category via ajax', response);
	    const message = (response == null ? void 0 : (_response$errors = response.errors) == null ? void 0 : (_response$errors$ = _response$errors[0]) == null ? void 0 : _response$errors$.message) || 'Something went wrong';
	    BX.UI.Notification.Center.notify({
	      content: message,
	      position: 'top-right',
	      autoHideDelay: 3000
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _stopProgress)[_stopProgress]();
	  });
	}
	function _showUnsavedChangesDialog2(onOk) {
	  var _BX$CrmEntityType;
	  const entityTypeName = (_BX$CrmEntityType = BX.CrmEntityType) == null ? void 0 : _BX$CrmEntityType.resolveName(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId]);
	  const message = main_core.Loc.getMessage(`CRM_ITEM_PAGETITLE_CATEGORY_CHANGER_CONFIRM_DIALOG_MESSAGE_${entityTypeName}`) || main_core.Loc.getMessage('CRM_ITEM_PAGETITLE_CATEGORY_CHANGER_CONFIRM_DIALOG_MESSAGE');
	  ui_dialogs_messagebox.MessageBox.show({
	    modal: true,
	    title: main_core.Loc.getMessage('CRM_ITEM_PAGETITLE_CATEGORY_CHANGER_CONFIRM_DIALOG_TITLE'),
	    message,
	    minHeight: 100,
	    buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	    okCaption: main_core.Loc.getMessage('CRM_ITEM_PAGETITLE_CATEGORY_CHANGER_CONFIRM_DIALOG_OK_BTN'),
	    onOk: messageBox => {
	      messageBox.close();
	      onOk();
	    },
	    onCancel: messageBox => messageBox.close()
	  });
	}
	function _startProgress2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isProgress)[_isProgress]) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _isProgress)[_isProgress] = true;
	  this.emit('onProgressStart');
	}
	function _stopProgress2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isProgress)[_isProgress]) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _isProgress)[_isProgress] = false;
	  this.emit('onProgressStop');
	}
	function _isEditorChanged2() {
	  const editor = babelHelpers.classPrivateFieldLooseBase(this, _getEditor)[_getEditor]();
	  return Boolean((editor == null ? void 0 : editor.hasChangedControls()) || (editor == null ? void 0 : editor.hasChangedControllers()));
	}
	function _getEditor2() {
	  var _BX$Crm2, _BX$Crm2$EntityEditor;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _editorGuid)[_editorGuid]) {
	    var _BX$Crm, _BX$Crm$EntityEditor;
	    return (_BX$Crm = BX.Crm) == null ? void 0 : (_BX$Crm$EntityEditor = _BX$Crm.EntityEditor) == null ? void 0 : _BX$Crm$EntityEditor.get(babelHelpers.classPrivateFieldLooseBase(this, _editorGuid)[_editorGuid]);
	  }
	  return (_BX$Crm2 = BX.Crm) == null ? void 0 : (_BX$Crm2$EntityEditor = _BX$Crm2.EntityEditor) == null ? void 0 : _BX$Crm2$EntityEditor.getDefault();
	}
	function _isNew2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] <= 0;
	}
	function _getCurrentCategory2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _categories)[_categories].find(category => category.getId() === babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId]);
	}
	function _getAllCategoriesExceptCurrent2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _categories)[_categories].filter(category => category.getId() !== babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId]);
	}

	exports.CategoryChanger = CategoryChanger;

}((this.BX.Crm.ItemDetailsComponent.PageTitle = this.BX.Crm.ItemDetailsComponent.PageTitle || {}),BX.Crm.Models,BX,BX.Event,BX.Main,BX,BX.UI.Dialogs));
//# sourceMappingURL=pagetitle.bundle.js.map
