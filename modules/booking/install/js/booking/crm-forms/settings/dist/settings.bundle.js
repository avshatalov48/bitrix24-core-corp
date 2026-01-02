/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_loader,booking_provider_service_crmFormService,main_core_events,booking_const,ui_entitySelector,main_core) {
	'use strict';

	var _resourcesById = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resourcesById");
	var _isLoaded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isLoaded");
	class ResourceStore {
	  constructor() {
	    Object.defineProperty(this, _isLoaded, {
	      value: _isLoaded2
	    });
	    Object.defineProperty(this, _resourcesById, {
	      writable: true,
	      value: new Map()
	    });
	  }
	  async ensure(ids) {
	    if (ids.length === 0) {
	      return;
	    }
	    const needToLoadIds = ids.filter(id => !babelHelpers.classPrivateFieldLooseBase(this, _isLoaded)[_isLoaded](id));
	    if (needToLoadIds.length === 0) {
	      return;
	    }
	    const loaded = await booking_provider_service_crmFormService.crmFormService.getResources(needToLoadIds);
	    for (const resource of loaded) {
	      babelHelpers.classPrivateFieldLooseBase(this, _resourcesById)[_resourcesById].set(resource.id, resource);
	    }
	  }
	  getByIds(ids) {
	    return ids.map(id => babelHelpers.classPrivateFieldLooseBase(this, _resourcesById)[_resourcesById].get(id)).filter(Boolean);
	  }
	  getAll() {
	    return [...babelHelpers.classPrivateFieldLooseBase(this, _resourcesById)[_resourcesById].values()];
	  }
	}
	function _isLoaded2(id) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _resourcesById)[_resourcesById].has(id);
	}
	const resourceStore = new ResourceStore();

	const defaultBookingForm = {
	  resourceIds: [],
	  label: main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_FIELD_LABEL_DEFAULT'),
	  isVisibleHint: true,
	  hint: main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_HINT_DEFAULT_VALUE'),
	  hasSlotsAllAvailableResources: false
	};
	const defaultBookingDefaultForm = {
	  ...defaultBookingForm,
	  resourceIds: [],
	  textHeader: main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_FIELD_PLACEHOLDER_DEFAULT_VALUE')
	};
	const defaultBookingAutoSelectionForm = {
	  ...defaultBookingForm,
	  resourceIds: [],
	  hasSlotsAllAvailableResources: false,
	  textHeader: main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_FIELD_PLACEHOLDER_AUTO_SELECT_DEFAULT_VALUE')
	};

	var _isAutoSelectionOn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAutoSelectionOn");
	var _settingsData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("settingsData");
	var _getAutoSelectionFormByTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAutoSelectionFormByTemplate");
	var _getDefaultFormByTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDefaultFormByTemplate");
	class BookingSettingsDataModel {
	  constructor(settingsData, isAutoSelectionOn, _templateId) {
	    Object.defineProperty(this, _getDefaultFormByTemplate, {
	      value: _getDefaultFormByTemplate2
	    });
	    Object.defineProperty(this, _getAutoSelectionFormByTemplate, {
	      value: _getAutoSelectionFormByTemplate2
	    });
	    Object.defineProperty(this, _isAutoSelectionOn, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _settingsData, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _isAutoSelectionOn)[_isAutoSelectionOn] = isAutoSelectionOn;
	    const autoSelectionData = settingsData[booking_const.CrmFormSettingsDataPropName.autoSelection] || babelHelpers.classPrivateFieldLooseBase(this, _getAutoSelectionFormByTemplate)[_getAutoSelectionFormByTemplate](_templateId);
	    const defaultData = settingsData[booking_const.CrmFormSettingsDataPropName.default] || babelHelpers.classPrivateFieldLooseBase(this, _getDefaultFormByTemplate)[_getDefaultFormByTemplate](_templateId);
	    babelHelpers.classPrivateFieldLooseBase(this, _settingsData)[_settingsData] = {
	      [booking_const.CrmFormSettingsDataPropName.isAutoSelectionOn]: isAutoSelectionOn,
	      [booking_const.CrmFormSettingsDataPropName.autoSelection]: autoSelectionData,
	      [booking_const.CrmFormSettingsDataPropName.default]: defaultData
	    };
	  }
	  get dataSettingsProperty() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isAutoSelectionOn)[_isAutoSelectionOn] ? booking_const.CrmFormSettingsDataPropName.autoSelection : booking_const.CrmFormSettingsDataPropName.default;
	  }
	  setSettingsData(patch = {}) {
	    Object.assign(babelHelpers.classPrivateFieldLooseBase(this, _settingsData)[_settingsData][this.dataSettingsProperty], patch);
	  }
	  get settingsData() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _settingsData)[_settingsData];
	  }
	  get form() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _settingsData)[_settingsData][this.dataSettingsProperty];
	  }
	}
	function _getAutoSelectionFormByTemplate2() {
	  return defaultBookingAutoSelectionForm;
	}
	function _getDefaultFormByTemplate2(templateId) {
	  if (templateId === booking_const.CrmFormTemplateId.BookingAnyResource) {
	    return {
	      ...defaultBookingDefaultForm,
	      hasSlotsAllAvailableResources: true
	    };
	  }
	  return {
	    ...defaultBookingDefaultForm
	  };
	}

	class ContentHeader extends ui_entitySelector.BaseHeader {
	  constructor(...props) {
	    super(...props);
	    this.getContainer();
	  }
	  render() {
	    return this.options.content;
	  }
	}

	let _ = t => t,
	  _t;
	var _resources = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resources");
	var _onClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClose");
	var _onSave = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSave");
	var _add = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("add");
	var _close = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("close");
	var _handleOnTagAdd = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleOnTagAdd");
	var _handleOnTagRemove = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleOnTagRemove");
	var _onResourceToggle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onResourceToggle");
	class ResourceSelectorDialogFooter extends ui_entitySelector.BaseFooter {
	  constructor(tab, options) {
	    super(tab, options);
	    Object.defineProperty(this, _onResourceToggle, {
	      value: _onResourceToggle2
	    });
	    Object.defineProperty(this, _handleOnTagRemove, {
	      value: _handleOnTagRemove2
	    });
	    Object.defineProperty(this, _handleOnTagAdd, {
	      value: _handleOnTagAdd2
	    });
	    Object.defineProperty(this, _close, {
	      value: _close2
	    });
	    Object.defineProperty(this, _add, {
	      value: _add2
	    });
	    Object.defineProperty(this, _resources, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _onClose, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _onSave, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _resources)[_resources] = [];
	    babelHelpers.classPrivateFieldLooseBase(this, _onClose)[_onClose] = options.onClose || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _onSave)[_onSave] = options.onSave || null;
	    this.getDialog().subscribe('Item:onSelect', babelHelpers.classPrivateFieldLooseBase(this, _handleOnTagAdd)[_handleOnTagAdd].bind(this));
	    this.getDialog().subscribe('Item:onDeselect', babelHelpers.classPrivateFieldLooseBase(this, _handleOnTagRemove)[_handleOnTagRemove].bind(this));
	  }
	  render() {
	    const {
	      footer,
	      footerAddButton,
	      footerCloseButton
	    } = main_core.Tag.render(_t || (_t = _`
			<div ref="footer" class="crm-forms--booking--resource-selector-dialog__footer">
				<button
					ref="footerAddButton"
					class="ui-btn ui-btn ui-btn-sm ui-btn-primary ui-btn-round crm-forms--booking--resource-selector-dialog__footer-btn-width"
				>
					${0}
				</button>
				<button ref="footerCloseButton" class="ui-btn ui-btn ui-btn-sm ui-btn-light-border ui-btn-round crm-forms--booking--resource-selector-dialog__footer-btn-width">
					${0}
				</button>
			</div>
		`), main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_SELECTOR_DIALOG_ADD_BUTTON'), main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_SELECTOR_DIALOG_CANCEL_BUTTON'));
	    this.footerAddButton = footerAddButton;
	    main_core.Event.bind(footerAddButton, 'click', babelHelpers.classPrivateFieldLooseBase(this, _add)[_add].bind(this));
	    this.footerCloseButton = footerCloseButton;
	    main_core.Event.bind(this.footerCloseButton, 'click', babelHelpers.classPrivateFieldLooseBase(this, _close)[_close].bind(this));
	    return footer;
	  }
	  get resourcesCount() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _resources)[_resources].length;
	  }
	  destroyDialog() {
	    main_core.Event.unbindAll(this.footerAddButton, 'click');
	    main_core.Event.unbindAll(this.footerCloseButton, 'click');
	    this.getDialog().destroy();
	  }
	}
	function _add2() {
	  const resources = this.dialog.getSelectedItems();
	  const resourceIds = resources.map(resource => resource.id);
	  if (main_core.Type.isFunction(babelHelpers.classPrivateFieldLooseBase(this, _onSave)[_onSave])) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onSave)[_onSave]({
	      resourceIds
	    });
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _close)[_close]();
	}
	function _close2() {
	  var _this$dialog;
	  if (main_core.Type.isFunction(babelHelpers.classPrivateFieldLooseBase(this, _onClose)[_onClose])) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onClose)[_onClose]();
	  }
	  (_this$dialog = this.dialog) == null ? void 0 : _this$dialog.hide();
	}
	function _handleOnTagAdd2(event) {
	  const {
	    item
	  } = event.getData();
	  babelHelpers.classPrivateFieldLooseBase(this, _onResourceToggle)[_onResourceToggle](item, true);
	}
	function _handleOnTagRemove2(event) {
	  const {
	    item
	  } = event.getData();
	  babelHelpers.classPrivateFieldLooseBase(this, _onResourceToggle)[_onResourceToggle](item, false);
	}
	function _onResourceToggle2(item, isSelected = false) {
	  if (isSelected) {
	    babelHelpers.classPrivateFieldLooseBase(this, _resources)[_resources] = [...babelHelpers.classPrivateFieldLooseBase(this, _resources)[_resources], item];
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _resources)[_resources] = babelHelpers.classPrivateFieldLooseBase(this, _resources)[_resources].filter(resource => resource.id !== item.id);
	  }
	}

	var _selector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selector");
	var _targetNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("targetNode");
	var _selectedIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedIds");
	var _selectedItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedItems");
	var _onClose$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClose");
	var _onSave$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSave");
	var _changeSelected = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("changeSelected");
	class ResourcesSelector {
	  constructor(options) {
	    Object.defineProperty(this, _changeSelected, {
	      value: _changeSelected2
	    });
	    Object.defineProperty(this, _selector, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _targetNode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedIds, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedItems, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _onClose$1, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _onSave$1, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _targetNode)[_targetNode] = options.targetNode;
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedIds)[_selectedIds] = options.selectedIds || [];
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedItems)[_selectedItems] = [];
	    babelHelpers.classPrivateFieldLooseBase(this, _onClose$1)[_onClose$1] = options.onClose;
	    babelHelpers.classPrivateFieldLooseBase(this, _onSave$1)[_onSave$1] = options.onSave;
	  }
	  getSelectedItems() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _selectedItems)[_selectedItems];
	  }
	  createSelector() {
	    babelHelpers.classPrivateFieldLooseBase(this, _selector)[_selector] = new ui_entitySelector.Dialog({
	      id: 'crm-forms--booking--booking-setting--resources-selector',
	      preselectedItems: babelHelpers.classPrivateFieldLooseBase(this, _selectedIds)[_selectedIds].map(id => [booking_const.EntitySelectorEntity.Resource, id]),
	      width: 400,
	      enableSearch: true,
	      dropdownMode: true,
	      context: 'crmFormsBookingResourcesSelector',
	      multiple: true,
	      cacheable: true,
	      showAvatars: false,
	      footer: ResourceSelectorDialogFooter,
	      footerOptions: {
	        onSave: babelHelpers.classPrivateFieldLooseBase(this, _onSave$1)[_onSave$1],
	        onClose: babelHelpers.classPrivateFieldLooseBase(this, _onClose$1)[_onClose$1]
	      },
	      entities: [{
	        id: booking_const.EntitySelectorEntity.Resource,
	        dynamicLoad: true,
	        dynamicSearch: true
	      }],
	      searchOptions: {
	        allowCreateItem: false
	      },
	      popupOptions: {
	        overlay: {
	          opacity: 40
	        }
	      },
	      events: {
	        onHide: this.hide.bind(this),
	        onLoad: babelHelpers.classPrivateFieldLooseBase(this, _changeSelected)[_changeSelected].bind(this)
	      }
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _selector)[_selector];
	  }
	  getSelectedIds() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _selectedItems)[_selectedItems].map(({
	      id
	    }) => id);
	  }
	  hide() {
	    if (main_core.Type.isFunction(babelHelpers.classPrivateFieldLooseBase(this, _onClose$1)[_onClose$1])) {
	      babelHelpers.classPrivateFieldLooseBase(this, _onClose$1)[_onClose$1]();
	    }
	  }
	}
	function _changeSelected2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _selectedItems)[_selectedItems] = babelHelpers.classPrivateFieldLooseBase(this, _selector)[_selector].getSelectedItems();
	}

	let _$1 = t => t,
	  _t$1,
	  _t2,
	  _t3;
	const subHeaderClassName = 'crm-form--booking--resources-manager--header-resources-section';
	var _targetNode$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("targetNode");
	var _selectedIds$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedIds");
	var _resourcesIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resourcesIds");
	var _loadingResources = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadingResources");
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _btnDeleteResources = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("btnDeleteResources");
	var _btnChangeResources = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("btnChangeResources");
	var _initDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initDialog");
	var _selectItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectItem");
	var _deselectItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("deselectItem");
	var _deleteResources = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("deleteResources");
	var _appendEmptyState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appendEmptyState");
	var _removeEmptyState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeEmptyState");
	var _updateResourcesCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateResourcesCount");
	var _loadResources = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadResources");
	var _addSelectedItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addSelectedItems");
	var _setLoadingResources = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setLoadingResources");
	var _setResourceIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setResourceIds");
	var _openResourceSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openResourceSelector");
	var _saveResourceSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveResourceSelector");
	var _closeResourceSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeResourceSelector");
	var _getHeaderContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHeaderContent");
	var _appendSubHeaderContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appendSubHeaderContent");
	var _showDeleteButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showDeleteButton");
	var _hideDeleteButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideDeleteButton");
	var _removeSubHeadContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeSubHeadContent");
	class ResourcesManager {
	  constructor(options) {
	    Object.defineProperty(this, _removeSubHeadContent, {
	      value: _removeSubHeadContent2
	    });
	    Object.defineProperty(this, _hideDeleteButton, {
	      value: _hideDeleteButton2
	    });
	    Object.defineProperty(this, _showDeleteButton, {
	      value: _showDeleteButton2
	    });
	    Object.defineProperty(this, _appendSubHeaderContent, {
	      value: _appendSubHeaderContent2
	    });
	    Object.defineProperty(this, _getHeaderContent, {
	      value: _getHeaderContent2
	    });
	    Object.defineProperty(this, _closeResourceSelector, {
	      value: _closeResourceSelector2
	    });
	    Object.defineProperty(this, _saveResourceSelector, {
	      value: _saveResourceSelector2
	    });
	    Object.defineProperty(this, _openResourceSelector, {
	      value: _openResourceSelector2
	    });
	    Object.defineProperty(this, _setResourceIds, {
	      value: _setResourceIds2
	    });
	    Object.defineProperty(this, _setLoadingResources, {
	      value: _setLoadingResources2
	    });
	    Object.defineProperty(this, _addSelectedItems, {
	      value: _addSelectedItems2
	    });
	    Object.defineProperty(this, _loadResources, {
	      value: _loadResources2
	    });
	    Object.defineProperty(this, _updateResourcesCount, {
	      value: _updateResourcesCount2
	    });
	    Object.defineProperty(this, _removeEmptyState, {
	      value: _removeEmptyState2
	    });
	    Object.defineProperty(this, _appendEmptyState, {
	      value: _appendEmptyState2
	    });
	    Object.defineProperty(this, _deleteResources, {
	      value: _deleteResources2
	    });
	    Object.defineProperty(this, _deselectItem, {
	      value: _deselectItem2
	    });
	    Object.defineProperty(this, _selectItem, {
	      value: _selectItem2
	    });
	    Object.defineProperty(this, _initDialog, {
	      value: _initDialog2
	    });
	    this.dialog = null;
	    Object.defineProperty(this, _targetNode$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedIds$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resourcesIds, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loadingResources, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _btnDeleteResources, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _btnChangeResources, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds] = options.selectedIds || [];
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedIds$1)[_selectedIds$1] = [];
	    babelHelpers.classPrivateFieldLooseBase(this, _targetNode$1)[_targetNode$1] = options.target;
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	  }
	  async show() {
	    if (!this.dialog) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initDialog)[_initDialog]();
	      await babelHelpers.classPrivateFieldLooseBase(this, _loadResources)[_loadResources](babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds]);
	      const renderInitialContent = babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds].length > 0 ? babelHelpers.classPrivateFieldLooseBase(this, _addSelectedItems)[_addSelectedItems].bind(this) : babelHelpers.classPrivateFieldLooseBase(this, _appendEmptyState)[_appendEmptyState].bind(this);
	      renderInitialContent();
	    }
	    this.dialog.show();
	    main_core.Event.bind(document, 'scroll', this.adjustPosition, true);
	  }
	  close() {
	    if (this.dialog) {
	      babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].onUpdateResourceIds(babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds]);
	      if (main_core.Type.isFunction(babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].onClose)) {
	        babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].onClose(babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds]);
	      }
	      this.dialog.destroy();
	      main_core.Event.unbind(document, 'scroll', this.adjustPosition, true);
	    }
	  }
	  adjustPosition() {
	    if (this.dialog) {
	      this.dialog.adjustPosition();
	    }
	  }
	  get selectedResources() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds].length === 0) {
	      return [];
	    }
	    return resourceStore.getByIds(babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds]);
	  }
	}
	function _initDialog2() {
	  this.dialog = new ui_entitySelector.Dialog({
	    targetNode: babelHelpers.classPrivateFieldLooseBase(this, _targetNode$1)[_targetNode$1],
	    id: 'booking-crm-form-resource-selector',
	    height: Math.max(window.innerHeight - 300, 500),
	    width: 356,
	    offsetLeft: babelHelpers.classPrivateFieldLooseBase(this, _targetNode$1)[_targetNode$1].offsetWidth + 5,
	    offsetTop: -300,
	    addTagOnSelect: false,
	    showAvatars: false,
	    focusOnFirst: false,
	    dropdownMode: true,
	    enableSearch: true,
	    searchOptions: {
	      allowCreateItem: false
	    },
	    header: ContentHeader,
	    headerOptions: {
	      content: babelHelpers.classPrivateFieldLooseBase(this, _getHeaderContent)[_getHeaderContent]()
	    },
	    popupOptions: {
	      className: 'crm-form--booking--resource-manager-popup',
	      angle: {
	        position: 'left'
	      }
	    },
	    tagSelectorOptions: {
	      textBoxWidth: '90%',
	      placeholder: main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_SEARCH_PLACEHOLDER')
	    },
	    events: {
	      onHide: () => {
	        var _babelHelpers$classPr, _babelHelpers$classPr2;
	        return (_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _options)[_options]).onClose) == null ? void 0 : _babelHelpers$classPr.call(_babelHelpers$classPr2, babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds]);
	      },
	      'Item:onSelect': event => {
	        babelHelpers.classPrivateFieldLooseBase(this, _selectItem)[_selectItem](event.getData().item);
	      },
	      'Item:onDeselect': event => {
	        babelHelpers.classPrivateFieldLooseBase(this, _deselectItem)[_deselectItem](event.getData().item);
	      }
	    }
	  });
	  if (babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds].length > 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _appendSubHeaderContent)[_appendSubHeaderContent]();
	  }
	  this.dialog.removeItems();
	}
	function _selectItem2(item) {
	  babelHelpers.classPrivateFieldLooseBase(this, _selectedIds$1)[_selectedIds$1].push(item.id);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _selectedIds$1)[_selectedIds$1].length > 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _showDeleteButton)[_showDeleteButton]();
	  }
	}
	function _deselectItem2(item) {
	  babelHelpers.classPrivateFieldLooseBase(this, _selectedIds$1)[_selectedIds$1] = babelHelpers.classPrivateFieldLooseBase(this, _selectedIds$1)[_selectedIds$1].filter(id => id !== item.id);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _selectedIds$1)[_selectedIds$1].length === 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _hideDeleteButton)[_hideDeleteButton]();
	  }
	}
	function _deleteResources2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _selectedIds$1)[_selectedIds$1].length === 0) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _setResourceIds)[_setResourceIds](babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds].filter(id => !babelHelpers.classPrivateFieldLooseBase(this, _selectedIds$1)[_selectedIds$1].includes(id)));
	  babelHelpers.classPrivateFieldLooseBase(this, _selectedIds$1)[_selectedIds$1] = [];
	  babelHelpers.classPrivateFieldLooseBase(this, _hideDeleteButton)[_hideDeleteButton]();
	}
	function _appendEmptyState2() {
	  const container = this.dialog.getPopup().getContentContainer();
	  if (main_core.Type.isDomNode(container.querySelector('.crm-forms--booking--resources-manager-empty-state'))) {
	    return;
	  }
	  const emptyState = main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="crm-forms--booking--resources-manager-empty-state d-flex h-100 w-100 justify-content-center">
				<div class="d-flex flex-column align-items-center p-4">
					<div class="mb-3 crm-forms--booking--resources-manager-empty-state_icon"></div>
					<div class="mb-3 crm-forms--booking--resources-manager-empty-state_title fw-medium fs-6 lh-base">
						${0}
					</div>
					<div class="mb-3 crm-forms--booking--resources-manager-empty-state_text fw-normal">
						${0}
					</div>
					<div>
						<button
							class="btn btn-primary g-btn-size-l crm-forms--booking--resources-manager__empty-state-btn-add"
							type="button"
							onclick="${0}"
						>
							${0}
						</button>
					</div>
				</div>
			</div>
		`), main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_EMPTY_TITLE'), main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_EMPTY_MESSAGE'), babelHelpers.classPrivateFieldLooseBase(this, _openResourceSelector)[_openResourceSelector].bind(this), main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_ADD_RESOURCES_BUTTON'));
	  main_core.Dom.insertBefore(emptyState, container.querySelector('.ui-selector-items'));
	}
	function _removeEmptyState2() {
	  const emptyStateEl = this.dialog.getPopup().getContentContainer().querySelector('.crm-forms--booking--resources-manager-empty-state');
	  removeElement(emptyStateEl);
	}
	function _updateResourcesCount2(resourcesCount) {
	  const resourcesCountEl = this.dialog.getPopup().getContentContainer().querySelector('.crm-form--booking--resources-manager--header-resources-count');
	  if (main_core.Type.isDomNode(resourcesCountEl)) {
	    resourcesCountEl.innerText = resourcesCount;
	  }
	}
	async function _loadResources2(ids) {
	  if (ids.length === 0) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _setLoadingResources)[_setLoadingResources](true);
	  await resourceStore.ensure(ids);
	  babelHelpers.classPrivateFieldLooseBase(this, _setLoadingResources)[_setLoadingResources](false);
	}
	function _addSelectedItems2() {
	  this.dialog.removeItems();
	  const resources = resourceStore.getByIds(babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds]);
	  for (const resource of resources) {
	    this.dialog.addItem({
	      id: resource.id,
	      entityId: booking_const.EntitySelectorEntity.Resource,
	      title: resource.name,
	      subtitle: resource.typeName,
	      tabs: booking_const.EntitySelectorTab.Recent
	    });
	  }
	}
	function _setLoadingResources2(loading) {
	  babelHelpers.classPrivateFieldLooseBase(this, _loadingResources)[_loadingResources] = loading;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _loadingResources)[_loadingResources]) {
	    this.dialog.showLoader();
	  } else {
	    this.dialog.hideLoader();
	  }
	}
	function _setResourceIds2(selectedIds) {
	  babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds] = selectedIds;
	  babelHelpers.classPrivateFieldLooseBase(this, _addSelectedItems)[_addSelectedItems]();
	  babelHelpers.classPrivateFieldLooseBase(this, _updateResourcesCount)[_updateResourcesCount](selectedIds.length);
	  babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].onUpdateResourceIds(selectedIds);
	  if (selectedIds.length > 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _appendSubHeaderContent)[_appendSubHeaderContent]();
	    babelHelpers.classPrivateFieldLooseBase(this, _removeEmptyState)[_removeEmptyState]();
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _appendEmptyState)[_appendEmptyState]();
	    babelHelpers.classPrivateFieldLooseBase(this, _removeSubHeadContent)[_removeSubHeadContent]();
	  }
	}
	function _openResourceSelector2(event) {
	  const resourceSelector = new ResourcesSelector({
	    targetNode: (event == null ? void 0 : event.target) || null,
	    selectedIds: babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds],
	    onSave: babelHelpers.classPrivateFieldLooseBase(this, _saveResourceSelector)[_saveResourceSelector].bind(this),
	    onClose: babelHelpers.classPrivateFieldLooseBase(this, _closeResourceSelector)[_closeResourceSelector].bind(this)
	  }).createSelector();
	  this.dialog.freeze();
	  resourceSelector.show();
	}
	async function _saveResourceSelector2({
	  resourceIds
	}) {
	  await babelHelpers.classPrivateFieldLooseBase(this, _loadResources)[_loadResources](resourceIds);
	  babelHelpers.classPrivateFieldLooseBase(this, _setResourceIds)[_setResourceIds](resourceIds);
	}
	function _closeResourceSelector2() {
	  this.dialog.unfreeze();
	}
	function _getHeaderContent2() {
	  return main_core.Tag.render(_t2 || (_t2 = _$1`
			<div class="w-100 pt-3 px-3">
				<div class="d-flex align-items-end w-100">
					<h5 class="flex-grow-1">
						${0}
					</h5>
					<div
						class="landing-ui-button-icon-remove" 
						role="button"
						tabindex="0"
						onclick="${0}"
					></div>
				</div>
			</div>
		`), main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_HEADER_TITLE'), this.close.bind(this));
	}
	function _appendSubHeaderContent2() {
	  if (this.dialog.getPopup().getPopupContainer().querySelector(`.${subHeaderClassName}`)) {
	    return;
	  }
	  const buttonChangeResourcesLabel = main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_HEADER_ADD_RESOURCES_BUTTON', {
	    '#PLUS#': '+'
	  });
	  const buttonDeleteResourcesLabel = main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_HEADER_DELETE_RESOURCES_BUTTON', {
	    '#ICON#': '×'
	  });
	  const resourcesCount = main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_RESOURCES_COUNT', {
	    '#COUNT#': `<span class="crm-form--booking--resources-manager--header-resources-count">${babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds].length}</span>`
	  });
	  const {
	    root,
	    btnDeleteResources,
	    btnChangeResources
	  } = main_core.Tag.render(_t3 || (_t3 = _$1`
			<div class="${0} d-flex align-items-center">
				<div class="crm-form--booking--resources-manager--header-resources flex-grow-1">
					<span class="pr-2 crm-form--booking--resources-manager--header-resources-title">
						${0}
					</span>
				</div>
				<div>
					<button
						ref="btnDeleteResources"
						class="crm-form--booking--resources-manager--header__btn --none btn btn-outline-danger g-btn-size-sm"
						type="button"
						onclick="${0}"
					>
						${0}
					</button>
					<button
						ref="btnChangeResources"
						class="crm-form--booking--resources-manager--header__btn btn btn-primary g-btn-size-sm"
						type="button"
						onclick="${0}"
					>
						${0}
					</button>
				</div>
			</div>
		`), subHeaderClassName, resourcesCount, babelHelpers.classPrivateFieldLooseBase(this, _deleteResources)[_deleteResources].bind(this), buttonDeleteResourcesLabel, babelHelpers.classPrivateFieldLooseBase(this, _openResourceSelector)[_openResourceSelector].bind(this), buttonChangeResourcesLabel);
	  babelHelpers.classPrivateFieldLooseBase(this, _btnChangeResources)[_btnChangeResources] = btnChangeResources;
	  babelHelpers.classPrivateFieldLooseBase(this, _btnDeleteResources)[_btnDeleteResources] = btnDeleteResources;
	  const searchEl = this.dialog.getPopup().getPopupContainer().querySelector('.ui-selector-search');
	  main_core.Dom.insertAfter(root, searchEl);
	}
	function _showDeleteButton2() {
	  if (main_core.Type.isDomNode(babelHelpers.classPrivateFieldLooseBase(this, _btnDeleteResources)[_btnDeleteResources]) && main_core.Type.isDomNode(babelHelpers.classPrivateFieldLooseBase(this, _btnChangeResources)[_btnChangeResources])) {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _btnChangeResources)[_btnChangeResources], '--none');
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _btnDeleteResources)[_btnDeleteResources], '--none');
	  }
	}
	function _hideDeleteButton2() {
	  if (main_core.Type.isDomNode(babelHelpers.classPrivateFieldLooseBase(this, _btnDeleteResources)[_btnDeleteResources]) && main_core.Type.isDomNode(babelHelpers.classPrivateFieldLooseBase(this, _btnChangeResources)[_btnChangeResources])) {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _btnChangeResources)[_btnChangeResources], '--none');
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _btnDeleteResources)[_btnDeleteResources], '--none');
	  }
	}
	function _removeSubHeadContent2() {
	  const subHeadEl = document.querySelector(`.${subHeaderClassName}`);
	  removeElement(subHeadEl);
	}
	function removeElement(el) {
	  if (main_core.Type.isDomNode(el)) {
	    main_core.Dom.remove(el);
	  }
	}

	var _field = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("field");
	class BookingBaseField {
	  constructor(field) {
	    Object.defineProperty(this, _field, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _field)[_field] = field;
	  }
	  getValue() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _field)[_field].getValue();
	  }
	  setValue(value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _field)[_field].setValue(value);
	  }
	  getField() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _field)[_field];
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _field)[_field].layout;
	  }
	}

	const DATA_HINT = 'data-hint';
	const DATA_HINT_NO_ICON = 'data-hint-no-icon';
	var _disabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disabled");
	var _addItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addItem");
	var _updateLayoutDataAttrs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateLayoutDataAttrs");
	class HasSlotsAllAvailableResourcesField extends BookingBaseField {
	  constructor(value, onChange, _disabled2 = false) {
	    super(new BX.Landing.UI.Field.Checkbox({
	      selector: 'hasSlotsAllAvailableResources',
	      compact: true,
	      multiple: false,
	      items: []
	    }));
	    Object.defineProperty(this, _updateLayoutDataAttrs, {
	      value: _updateLayoutDataAttrs2
	    });
	    Object.defineProperty(this, _addItem, {
	      value: _addItem2
	    });
	    Object.defineProperty(this, _disabled, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _addItem)[_addItem](_disabled2);
	    babelHelpers.classPrivateFieldLooseBase(this, _disabled)[_disabled] = _disabled2;
	    if (main_core.Type.isFunction(onChange)) {
	      this.getField().subscribe('onChange', () => {
	        onChange(Boolean(this.getField().getValue()));
	      });
	    }
	    this.setValue(value);
	  }
	  setValue(value) {
	    super.setValue(value ? ['hasSlotsAllAvailableResources'] : false);
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _updateLayoutDataAttrs)[_updateLayoutDataAttrs](super.getLayout());
	  }
	}
	function _addItem2(disabled = false) {
	  const hintMessage = main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_SHOW_SLOTS_ALL_AVAILABLE_RESOURCES_HELP_HINT', {
	    '#NBSP# ': '&nbsp;'
	  });
	  this.getField().addItem({
	    name: main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_SHOW_SLOTS_ALL_AVAILABLE_RESOURCES'),
	    html: `
				${main_core.Text.encode(main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_SHOW_SLOTS_ALL_AVAILABLE_RESOURCES'))}
				<span
					class="landing-ui-form-help booking--crm-forms-settins--show-slots-all-available-resources-help-hint"
					title=""
					${DATA_HINT}="${hintMessage}"
					onclick="return false;"
				>
					<div></div>
				</span>
			`,
	    value: 'hasSlotsAllAvailableResources',
	    disabled
	  });
	}
	function _updateLayoutDataAttrs2(layout) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _disabled)[_disabled]) {
	    layout.setAttribute(DATA_HINT, main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_SHOW_SLOTS_ALL_AVAILABLE_RESOURCES_DISABLED_HINT'));
	    layout.setAttribute(DATA_HINT_NO_ICON, true);
	  } else {
	    layout.removeAttribute(DATA_HINT);
	    layout.removeAttribute(DATA_HINT_NO_ICON);
	  }
	  return layout;
	}

	class HintField extends BookingBaseField {
	  constructor(value, onChange) {
	    super(new BX.Landing.UI.Field.Text({
	      title: main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_HINT_LABEL'),
	      textOnly: true,
	      content: value
	    }));
	    if (main_core.Type.isFunction(onChange)) {
	      this.getField().subscribe('onChange', () => onChange(this.getValue() || ''));
	    }
	    this.setValue(value);
	  }
	  setValue(value) {
	    super.setValue(value);
	  }
	}

	class HintVisibilityField extends BookingBaseField {
	  constructor(value, onChange) {
	    super(new BX.Landing.UI.Field.Checkbox({
	      selector: 'isVisibleHint',
	      compact: true,
	      multiple: false,
	      items: [{
	        name: main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_IS_VISIBLE_HINT_LABEL'),
	        value: 'isVisibleHint'
	      }]
	    }));
	    if (main_core.Type.isFunction(onChange)) {
	      this.getField().subscribe('onChange', () => onChange(Boolean(this.getValue())));
	    }
	    this.setValue(value);
	  }
	  setValue(value) {
	    super.setValue(value ? ['isVisibleHint'] : false);
	  }
	}

	class LabelField extends BookingBaseField {
	  constructor(value, onChange) {
	    super(new BX.Landing.UI.Field.Text({
	      title: main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_FIELD_LABEL'),
	      textOnly: true,
	      content: value
	    }));
	    if (main_core.Type.isFunction(onChange)) {
	      this.getField().subscribe('onChange', () => onChange(this.getValue() || ''));
	    }
	    this.setValue(value);
	  }
	  setValue(value) {
	    super.setValue(value);
	  }
	}

	class PlaceholderField extends BookingBaseField {
	  constructor(value, onChange) {
	    super(new BX.Landing.UI.Field.Text({
	      title: main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_FIELD_PLACEHOLDER'),
	      textOnly: true,
	      content: value
	    }));
	    if (main_core.Type.isFunction(onChange)) {
	      this.getField().subscribe('onChange', () => onChange(this.getValue() || ''));
	    }
	    this.setValue(value);
	  }
	  setValue(value) {
	    super.setValue(value);
	  }
	}

	let _$2 = t => t,
	  _t$2,
	  _t2$1,
	  _t3$1;
	var _options$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _layout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _bookingSettingsDataModel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bookingSettingsDataModel");
	var _isAutoSelectionOn$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAutoSelectionOn");
	var _resourceLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resourceLoader");
	var _loadingResources$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadingResources");
	var _resourcesManagerButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resourcesManagerButton");
	var _initFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initFields");
	var _loadResources$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadResources");
	var _setLoadingResources$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setLoadingResources");
	var _filterAvailableResourceIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filterAvailableResourceIds");
	var _getHeaderContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHeaderContainer");
	var _getBodyContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBodyContainer");
	var _renderContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderContent");
	var _renderResource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderResource");
	var _showResourcesManager = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showResourcesManager");
	var _getResourceIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getResourceIds");
	var _setResourceIds$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setResourceIds");
	var _updateResourcesCounter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateResourcesCounter");
	var _updateResourceManagerButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateResourceManagerButton");
	var _renderLabelField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderLabelField");
	var _renderPlaceholderField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPlaceholderField");
	var _renderHintField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderHintField");
	var _renderIsVisibleHint = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderIsVisibleHint");
	var _renderHasSlotsAllAvailableResources = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderHasSlotsAllAvailableResources");
	var _updateSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateSettings");
	class BookingSettingsPopup extends main_core_events.EventEmitter {
	  constructor({
	    listItemOptions,
	    isAutoSelectionOn,
	    templateId
	  }) {
	    super();
	    Object.defineProperty(this, _updateSettings, {
	      value: _updateSettings2
	    });
	    Object.defineProperty(this, _renderHasSlotsAllAvailableResources, {
	      value: _renderHasSlotsAllAvailableResources2
	    });
	    Object.defineProperty(this, _renderIsVisibleHint, {
	      value: _renderIsVisibleHint2
	    });
	    Object.defineProperty(this, _renderHintField, {
	      value: _renderHintField2
	    });
	    Object.defineProperty(this, _renderPlaceholderField, {
	      value: _renderPlaceholderField2
	    });
	    Object.defineProperty(this, _renderLabelField, {
	      value: _renderLabelField2
	    });
	    Object.defineProperty(this, _updateResourceManagerButton, {
	      value: _updateResourceManagerButton2
	    });
	    Object.defineProperty(this, _updateResourcesCounter, {
	      value: _updateResourcesCounter2
	    });
	    Object.defineProperty(this, _setResourceIds$1, {
	      value: _setResourceIds2$1
	    });
	    Object.defineProperty(this, _getResourceIds, {
	      value: _getResourceIds2
	    });
	    Object.defineProperty(this, _showResourcesManager, {
	      value: _showResourcesManager2
	    });
	    Object.defineProperty(this, _renderResource, {
	      value: _renderResource2
	    });
	    Object.defineProperty(this, _renderContent, {
	      value: _renderContent2
	    });
	    Object.defineProperty(this, _getBodyContainer, {
	      value: _getBodyContainer2
	    });
	    Object.defineProperty(this, _getHeaderContainer, {
	      value: _getHeaderContainer2
	    });
	    Object.defineProperty(this, _filterAvailableResourceIds, {
	      value: _filterAvailableResourceIds2
	    });
	    Object.defineProperty(this, _setLoadingResources$1, {
	      value: _setLoadingResources2$1
	    });
	    Object.defineProperty(this, _loadResources$1, {
	      value: _loadResources2$1
	    });
	    Object.defineProperty(this, _initFields, {
	      value: _initFields2
	    });
	    Object.defineProperty(this, _options$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _bookingSettingsDataModel, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isAutoSelectionOn$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resourceLoader, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _loadingResources$1, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _resourcesManagerButton, {
	      writable: true,
	      value: null
	    });
	    this.setEventNamespace('BX.Booking.CrmForm.BookingSettingsPopup');
	    babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1] = listItemOptions;
	    babelHelpers.classPrivateFieldLooseBase(this, _isAutoSelectionOn$1)[_isAutoSelectionOn$1] = isAutoSelectionOn;
	    babelHelpers.classPrivateFieldLooseBase(this, _bookingSettingsDataModel)[_bookingSettingsDataModel] = new BookingSettingsDataModel(listItemOptions.sourceOptions.settingsData || {}, isAutoSelectionOn, templateId);
	    babelHelpers.classPrivateFieldLooseBase(this, _initFields)[_initFields](babelHelpers.classPrivateFieldLooseBase(this, _bookingSettingsDataModel)[_bookingSettingsDataModel].form);
	  }
	  async show() {
	    await babelHelpers.classPrivateFieldLooseBase(this, _loadResources$1)[_loadResources$1](babelHelpers.classPrivateFieldLooseBase(this, _getResourceIds)[_getResourceIds]());
	    const container = babelHelpers.classPrivateFieldLooseBase(this, _getBodyContainer)[_getBodyContainer]();
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _renderContent)[_renderContent](), container);
	    BX.UI.Hint.init(container);
	    babelHelpers.classPrivateFieldLooseBase(this, _updateResourcesCounter)[_updateResourcesCounter]();
	    main_core.Dom.style(container, 'display', 'block');
	  }
	  close() {
	    const container = babelHelpers.classPrivateFieldLooseBase(this, _getBodyContainer)[_getBodyContainer]();
	    this.emit('onClose');
	    main_core.Dom.style(container, 'display', 'none');
	    main_core.Dom.clean(container);
	  }
	  getSettings() {
	    babelHelpers.classPrivateFieldLooseBase(this, _updateSettings)[_updateSettings]();
	    return babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].sourceOptions.settingsData;
	  }
	}
	function _initFields2(settingsData) {
	  const changeField = field => {
	    babelHelpers.classPrivateFieldLooseBase(this, _updateSettings)[_updateSettings](field);
	  };
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout] = {};
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].label = new LabelField(settingsData.label || '', label => changeField({
	    label
	  }));
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].placeholder = new PlaceholderField(settingsData.textHeader || '', textHeader => changeField({
	    textHeader
	  }));
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].hint = new HintField(settingsData.hint || '', hint => changeField({
	    hint
	  }));
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].isVisibleHint = new HintVisibilityField(Boolean(settingsData == null ? void 0 : settingsData.isVisibleHint), isVisibleHint => changeField({
	    isVisibleHint
	  }));
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].hasSlotsAllAvailableResources = new HasSlotsAllAvailableResourcesField(Boolean(settingsData == null ? void 0 : settingsData.hasSlotsAllAvailableResources), hasSlotsAllAvailableResources => changeField({
	    hasSlotsAllAvailableResources
	  }), babelHelpers.classPrivateFieldLooseBase(this, _isAutoSelectionOn$1)[_isAutoSelectionOn$1]);
	}
	async function _loadResources2$1(ids) {
	  if (ids.length === 0) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _setLoadingResources$1)[_setLoadingResources$1](true);
	  await resourceStore.ensure(babelHelpers.classPrivateFieldLooseBase(this, _getResourceIds)[_getResourceIds]());
	  babelHelpers.classPrivateFieldLooseBase(this, _setResourceIds$1)[_setResourceIds$1](babelHelpers.classPrivateFieldLooseBase(this, _filterAvailableResourceIds)[_filterAvailableResourceIds](ids));
	  babelHelpers.classPrivateFieldLooseBase(this, _setLoadingResources$1)[_setLoadingResources$1](false);
	}
	function _setLoadingResources2$1(loading) {
	  const container = babelHelpers.classPrivateFieldLooseBase(this, _getHeaderContainer)[_getHeaderContainer]();
	  babelHelpers.classPrivateFieldLooseBase(this, _loadingResources$1)[_loadingResources$1] = loading;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _loadingResources$1)[_loadingResources$1]) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _resourceLoader))[_resourceLoader]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_resourceLoader] = new main_loader.Loader({
	      size: 40
	    });
	    main_core.Dom.style(container, 'opacity', 0.8);
	    void babelHelpers.classPrivateFieldLooseBase(this, _resourceLoader)[_resourceLoader].show(container);
	  } else {
	    main_core.Dom.style(container, 'opacity', 1);
	    void babelHelpers.classPrivateFieldLooseBase(this, _resourceLoader)[_resourceLoader].hide();
	  }
	}
	function _filterAvailableResourceIds2(ids) {
	  const availableResources = resourceStore.getAll();
	  const availableResourceIds = new Set(availableResources.map(resource => resource.id));
	  return ids.filter(id => availableResourceIds.has(id));
	}
	function _getHeaderContainer2() {
	  return document.querySelector(`.landing-ui-component-list-item[data-id="${babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].id}"] .landing-ui-component-list-item-header`);
	}
	function _getBodyContainer2() {
	  return document.querySelector(`.landing-ui-component-list-item[data-id="${babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].id}"] .landing-ui-component-list-item-body`);
	}
	function _renderContent2() {
	  return main_core.Tag.render(_t$2 || (_t$2 = _$2`
			<div class="landing-ui-form landing-ui-form-form-settings booking-crm-forms-settings">
				<div class="landing-ui-form-description"></div>
				${0}
				${0}
				${0}
				${0}
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderResource)[_renderResource](), babelHelpers.classPrivateFieldLooseBase(this, _renderLabelField)[_renderLabelField](), babelHelpers.classPrivateFieldLooseBase(this, _renderPlaceholderField)[_renderPlaceholderField](), babelHelpers.classPrivateFieldLooseBase(this, _renderHintField)[_renderHintField](), babelHelpers.classPrivateFieldLooseBase(this, _renderIsVisibleHint)[_renderIsVisibleHint](), babelHelpers.classPrivateFieldLooseBase(this, _renderHasSlotsAllAvailableResources)[_renderHasSlotsAllAvailableResources]());
	}
	function _renderResource2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _resourcesManagerButton)[_resourcesManagerButton] = main_core.Tag.render(_t2$1 || (_t2$1 = _$2`
			<button
				id="booking--crm-forms--resource-manager-button"
				type="button"
				class="btn btn-primary g-btn-size-l"
				onclick="${0}"
			>
				${0}
			</button>
		`), babelHelpers.classPrivateFieldLooseBase(this, _showResourcesManager)[_showResourcesManager].bind(this), babelHelpers.classPrivateFieldLooseBase(this, _getResourceIds)[_getResourceIds]().length > 0 ? main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_CHANGE_BUTTON') : main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_ADD_BUTTON'));
	  return main_core.Tag.render(_t3$1 || (_t3$1 = _$2`
			<div class="landing-ui-field d-flex">
				<div class="flex-grow-1">
					<div class="g-line-height-1_7 g-font-size-18 g-font-weight-500 g-color-gray-dark-v2">
						${0}
					</div>
					<div class="crm-form--booking-resources-count"></div>
				</div>
				<div style="align-self: flex-end">
					${0}
				</div>
			</div>
		`), main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_LABEL'), babelHelpers.classPrivateFieldLooseBase(this, _resourcesManagerButton)[_resourcesManagerButton]);
	}
	function _showResourcesManager2() {
	  const resourcesManager = new ResourcesManager({
	    target: babelHelpers.classPrivateFieldLooseBase(this, _resourcesManagerButton)[_resourcesManagerButton],
	    selectedIds: babelHelpers.classPrivateFieldLooseBase(this, _getResourceIds)[_getResourceIds](),
	    onUpdateResourceIds: resourceIds => {
	      babelHelpers.classPrivateFieldLooseBase(this, _setResourceIds$1)[_setResourceIds$1](resourceIds);
	      babelHelpers.classPrivateFieldLooseBase(this, _updateSettings)[_updateSettings]();
	      babelHelpers.classPrivateFieldLooseBase(this, _updateResourcesCounter)[_updateResourcesCounter]();
	      babelHelpers.classPrivateFieldLooseBase(this, _updateResourceManagerButton)[_updateResourceManagerButton]();
	    }
	  });
	  resourcesManager.show();
	}
	function _getResourceIds2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _bookingSettingsDataModel)[_bookingSettingsDataModel].form.resourceIds;
	}
	function _setResourceIds2$1(resourceIds) {
	  babelHelpers.classPrivateFieldLooseBase(this, _bookingSettingsDataModel)[_bookingSettingsDataModel].setSettingsData({
	    resourceIds: main_core.Type.isArray(resourceIds) ? resourceIds : []
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _updateSettings)[_updateSettings]();
	}
	function _updateResourcesCounter2() {
	  const counterEl = babelHelpers.classPrivateFieldLooseBase(this, _getBodyContainer)[_getBodyContainer]().querySelector('.crm-form--booking-resources-count');
	  if (main_core.Type.isDomNode(counterEl)) {
	    counterEl.innerText = babelHelpers.classPrivateFieldLooseBase(this, _getResourceIds)[_getResourceIds]().length > 0 ? main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_TEXT', {
	      '#COUNT#': babelHelpers.classPrivateFieldLooseBase(this, _getResourceIds)[_getResourceIds]().length
	    }) : main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_EMPTY');
	  }
	}
	function _updateResourceManagerButton2() {
	  if (main_core.Type.isDomNode(babelHelpers.classPrivateFieldLooseBase(this, _resourcesManagerButton)[_resourcesManagerButton])) {
	    babelHelpers.classPrivateFieldLooseBase(this, _resourcesManagerButton)[_resourcesManagerButton].innerText = babelHelpers.classPrivateFieldLooseBase(this, _getResourceIds)[_getResourceIds]().length > 0 ? main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_CHANGE_BUTTON') : main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_ADD_BUTTON');
	  }
	}
	function _renderLabelField2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].label.getLayout();
	}
	function _renderPlaceholderField2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].placeholder.getLayout();
	}
	function _renderHintField2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isAutoSelectionOn$1)[_isAutoSelectionOn$1] && !babelHelpers.classPrivateFieldLooseBase(this, _bookingSettingsDataModel)[_bookingSettingsDataModel].form.hint) {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].hint.setValue(main_core.Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_HINT_DEFAULT_VALUE'));
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].hint.getLayout();
	}
	function _renderIsVisibleHint2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].isVisibleHint.getLayout();
	}
	function _renderHasSlotsAllAvailableResources2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].hasSlotsAllAvailableResources.getLayout();
	}
	function _updateSettings2(settings = null) {
	  babelHelpers.classPrivateFieldLooseBase(this, _bookingSettingsDataModel)[_bookingSettingsDataModel].setSettingsData(settings);
	  babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].sourceOptions.settingsData = {
	    ...babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].sourceOptions.settingsData,
	    isAutoSelectionOn: babelHelpers.classPrivateFieldLooseBase(this, _isAutoSelectionOn$1)[_isAutoSelectionOn$1],
	    ...babelHelpers.classPrivateFieldLooseBase(this, _bookingSettingsDataModel)[_bookingSettingsDataModel].settingsData
	  };
	  this.emit('onChange');
	  babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].form.emit('onChange');
	}

	var _isAutoSelectionOn$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAutoSelectionOn");
	var _options$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	class Settings {
	  constructor(listItemOptions, formOptions = {}) {
	    var _formOptions$bookingR;
	    Object.defineProperty(this, _isAutoSelectionOn$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _options$2, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2] = listItemOptions;
	    babelHelpers.classPrivateFieldLooseBase(this, _isAutoSelectionOn$2)[_isAutoSelectionOn$2] = Boolean(formOptions == null ? void 0 : (_formOptions$bookingR = formOptions.bookingResourceAutoSelection) == null ? void 0 : _formOptions$bookingR.use);
	    this.settingsPopup = new BookingSettingsPopup({
	      listItemOptions,
	      isAutoSelectionOn: babelHelpers.classPrivateFieldLooseBase(this, _isAutoSelectionOn$2)[_isAutoSelectionOn$2],
	      templateId: formOptions == null ? void 0 : formOptions.templateId
	    });
	  }
	  getSettings() {
	    return this.settingsPopup.getSettings();
	  }
	  showSettingsPopup() {
	    const isToolDisabled = main_core.Extension.getSettings('booking.crm-forms.settings').isToolDisabled;
	    if (isToolDisabled) {
	      main_core.Runtime.loadExtension('ui.info-helper').then(({
	        InfoHelper
	      }) => {
	        InfoHelper.show('limit_v2_booking_off');
	      }).catch(err => {
	        console.error(err);
	      });
	      return;
	    }
	    const container = document.querySelector(`.landing-ui-component-list-item[data-id="${babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].id}"] .landing-ui-component-list-item-body`);
	    if (main_core.Dom.style(container, 'display') === 'block') {
	      this.settingsPopup.close();
	    } else {
	      this.settingsPopup.show();
	    }
	  }
	}

	exports.Settings = Settings;

}((this.BX.Booking.CrmForms = this.BX.Booking.CrmForms || {}),BX,BX.Booking.Provider.Service,BX.Event,BX.Booking.Const,BX.UI.EntitySelector,BX));
//# sourceMappingURL=settings.bundle.js.map
