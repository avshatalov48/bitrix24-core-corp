/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.EntityList = this.BX.Crm.EntityList || {};
(function (exports,crm_activity_planner,main_core_collections,main_core_events,crm_merger_batchmergemanager,crm_autorun,ui_entitySelector,ui_notification,ui_dialogs_messagebox,main_core) {
	'use strict';

	/**
	 * @memberof BX.Crm.EntityList.Panel
	 */
	function createCallListAndShowAlertOnErrors(entityTypeId, selectedIds, createActivity, gridId = null, forAll = false) {
	  void createCallList(entityTypeId, selectedIds, createActivity, gridId, forAll).then(({
	    errorMessages
	  }) => {
	    if (main_core.Type.isArrayFilled(errorMessages)) {
	      const error = errorMessages.join('. \n');
	      ui_dialogs_messagebox.MessageBox.alert(main_core.Text.encode(error));
	    }
	  });
	}

	/**
	 * @memberof BX.Crm.EntityList.Panel
	 */
	function createCallList(entityTypeId, selectedIds, createActivity, gridId = null, forAll = false) {
	  return new Promise(resolve => {
	    BX.CrmCallListHelper.createCallList({
	      entityType: BX.CrmEntityType.resolveName(entityTypeId),
	      entityIds: forAll ? [] : selectedIds,
	      gridId: main_core.Type.isNil(gridId) ? undefined : gridId,
	      createActivity
	    }, response => {
	      if (!main_core.Type.isPlainObject(response)) {
	        resolve({});
	        return;
	      }
	      if (!response.SUCCESS && response.ERRORS) {
	        resolve({
	          errorMessages: response.ERRORS
	        });
	        return;
	      }
	      if (!response.SUCCESS || !response.DATA) {
	        resolve({});
	        return;
	      }
	      const data = response.DATA;
	      if (data.RESTRICTION) {
	        showRestriction(data.RESTRICTION);
	        resolve({});
	        return;
	      }
	      const callListId = data.ID;
	      if (createActivity && top.BXIM) {
	        top.BXIM.startCallList(callListId, {});
	      } else {
	        new BX.Crm.Activity.Planner().showEdit({
	          PROVIDER_ID: 'CALL_LIST',
	          PROVIDER_TYPE_ID: 'CALL_LIST',
	          ASSOCIATED_ENTITY_ID: callListId
	        });
	      }
	      resolve({});
	    });
	  });
	}
	function showRestriction(restriction) {
	  if (main_core.Type.isPlainObject(restriction) && main_core.Reflection.getClass('B24.licenseInfoPopup')) {
	    // eslint-disable-next-line no-undef
	    B24.licenseInfoPopup.show('ivr-limit-popup', restriction.HEADER, restriction.CONTENT);
	  } else if (main_core.Type.isStringFilled(restriction)) {
	    // eslint-disable-next-line no-eval
	    eval(restriction);
	  }
	}
	function addItemsToCallList(entityTypeId, selectedIds, callListId, context, gridId, forAll) {
	  BX.CrmCallListHelper.addToCallList({
	    callListId,
	    context,
	    entityType: BX.CrmEntityType.resolveName(entityTypeId),
	    entityIds: forAll ? [] : selectedIds,
	    gridId
	  });
	}

	/**
	 * @abstract
	 */
	class BaseHandler {
	  /**
	   * @abstract
	   */
	  static getEventName() {
	    throw new Error('not implemented');
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {}

	  /**
	   * @abstract
	   */
	  execute(grid, selectedIds, forAll) {
	    throw new Error('not implemented');
	  }
	}

	var _entityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _categoryId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("categoryId");
	var _loadedFieldsCache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadedFieldsCache");
	var _getEmptyItemsFieldNames = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEmptyItemsFieldNames");
	var _getAlreadyLoadedFieldNames = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAlreadyLoadedFieldNames");
	var _getCells = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCells");
	class LoadEnumsAndEditSelected extends BaseHandler {
	  constructor({
	    entityTypeId,
	    categoryId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _categoryId, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId])) {
	      throw new Error('entityTypeId is required');
	    }
	    if (!main_core.Type.isNil(categoryId)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId] = main_core.Text.toInteger(categoryId);
	    }
	  }
	  static getEventName() {
	    return 'loadEnumsAndEditSelected';
	  }
	  execute(grid, selectedIds, forAll) {
	    void LoadEnumsAndEditSelected.loadEnums(grid, babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId], babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId]).finally(() => grid.editSelected());
	  }
	  static loadEnums(grid, entityTypeId, categoryId) {
	    const fieldNames = babelHelpers.classPrivateFieldLooseBase(this, _getEmptyItemsFieldNames)[_getEmptyItemsFieldNames](grid);
	    if (fieldNames.length === 0) {
	      return Promise.resolve();
	    }
	    return new Promise((resolve, reject) => {
	      main_core.ajax.runAction('crm.controller.list.userField.getData', {
	        data: {
	          entityTypeId,
	          fieldNames,
	          categoryId
	        }
	      }).then(({
	        data: {
	          fields
	        }
	      }) => {
	        const alreadyLoaded = babelHelpers.classPrivateFieldLooseBase(this, _getAlreadyLoadedFieldNames)[_getAlreadyLoadedFieldNames](grid.getId());
	        for (const cell of babelHelpers.classPrivateFieldLooseBase(this, _getCells)[_getCells](grid)) {
	          const {
	            name
	          } = cell.dataset;
	          if (!fields[name]) {
	            continue;
	          }
	          cell.dataset.edit = `(${fields[name]})`;
	          alreadyLoaded.add(name);
	        }
	        resolve();
	      }).catch(response => {
	        console.error('Could not load UF enum values for edit', {
	          response,
	          grid,
	          entityTypeId,
	          categoryId,
	          fieldNames
	        });
	        reject();
	      });
	    });
	  }
	  /**
	   * @internal
	   */
	  static onAfterGridUpdate(grid) {
	    babelHelpers.classPrivateFieldLooseBase(this, _loadedFieldsCache)[_loadedFieldsCache].delete(grid.getId());
	  }
	}
	function _getEmptyItemsFieldNames2(grid) {
	  const columnsAll = grid.getParam('COLUMNS_ALL');
	  const alreadyLoaded = babelHelpers.classPrivateFieldLooseBase(this, _getAlreadyLoadedFieldNames)[_getAlreadyLoadedFieldNames](grid.getId());
	  const fields = [];
	  for (const cell of babelHelpers.classPrivateFieldLooseBase(this, _getCells)[_getCells](grid)) {
	    var _cell$dataset$name;
	    const name = (_cell$dataset$name = cell.dataset.name) != null ? _cell$dataset$name : null;
	    const columnData = columnsAll[name];
	    const isListColumnWithEmptyData = main_core.Type.isObjectLike(columnData == null ? void 0 : columnData.editable) && !columnData.editable.DATA && columnData.type === 'list';
	    if (isListColumnWithEmptyData && !alreadyLoaded.has(name)) {
	      fields.push(name);
	    }
	  }
	  return fields;
	}
	function _getAlreadyLoadedFieldNames2(gridId) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _loadedFieldsCache)[_loadedFieldsCache].has(gridId)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _loadedFieldsCache)[_loadedFieldsCache].set(gridId, new Set());
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _loadedFieldsCache)[_loadedFieldsCache].get(gridId);
	}
	function _getCells2(grid) {
	  const {
	    cells
	  } = grid.getRows().getHeadFirstChild().getNode();
	  return [...cells];
	}
	Object.defineProperty(LoadEnumsAndEditSelected, _getCells, {
	  value: _getCells2
	});
	Object.defineProperty(LoadEnumsAndEditSelected, _getAlreadyLoadedFieldNames, {
	  value: _getAlreadyLoadedFieldNames2
	});
	Object.defineProperty(LoadEnumsAndEditSelected, _getEmptyItemsFieldNames, {
	  value: _getEmptyItemsFieldNames2
	});
	Object.defineProperty(LoadEnumsAndEditSelected, _loadedFieldsCache, {
	  writable: true,
	  value: new Map()
	});
	main_core_events.EventEmitter.subscribe('Grid::updated', event => {
	  const [grid] = event.getData();
	  LoadEnumsAndEditSelected.onAfterGridUpdate(grid);
	});

	var _handlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handlers");
	var _grid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("grid");
	var _progressBarRepo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("progressBarRepo");
	var _extensionSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extensionSettings");
	var _subscriptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscriptions");
	var _createSubscriptionHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createSubscriptionHandler");
	class Router {
	  constructor(grid, progressBarRepo, extensionSettings) {
	    Object.defineProperty(this, _createSubscriptionHandler, {
	      value: _createSubscriptionHandler2
	    });
	    Object.defineProperty(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _progressBarRepo, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _extensionSettings, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _subscriptions, {
	      writable: true,
	      value: new Map()
	    });
	    if (!(grid instanceof BX.Main.grid)) {
	      throw new TypeError('expected grid to be instance of BX.Main.grid');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid] = grid;
	    if (!(progressBarRepo instanceof crm_autorun.ProgressBarRepository)) {
	      throw new TypeError('expected progressBarRepo to be instance of ProgressBarRepository');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo)[_progressBarRepo] = progressBarRepo;
	    if (!(extensionSettings instanceof main_core_collections.SettingsCollection)) {
	      throw new TypeError('expected extensionSettings to be instance of SettingsCollection');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings] = extensionSettings;
	  }
	  static registerHandler(handler) {
	    babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers].add(handler);
	  }
	  startListening() {
	    for (const HandlerClass of babelHelpers.classPrivateFieldLooseBase(this.constructor, _handlers)[_handlers]) {
	      const eventName = `BX.Crm.EntityList.Panel:${HandlerClass.getEventName()}`;
	      const subscriptionHandler = babelHelpers.classPrivateFieldLooseBase(this, _createSubscriptionHandler)[_createSubscriptionHandler](HandlerClass);
	      babelHelpers.classPrivateFieldLooseBase(this, _subscriptions)[_subscriptions].set(eventName, subscriptionHandler);
	      main_core_events.EventEmitter.subscribe(eventName, subscriptionHandler);
	    }
	  }
	  stopListening() {
	    for (const [eventName, subscriptionHandler] of babelHelpers.classPrivateFieldLooseBase(this, _subscriptions)[_subscriptions].entries()) {
	      main_core_events.EventEmitter.unsubscribe(eventName, subscriptionHandler);
	    }
	  }
	}
	function _createSubscriptionHandler2(HandlerClass) {
	  return event => {
	    var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3;
	    const eventHandler = new HandlerClass(event.getData());
	    eventHandler.injectDependencies(babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo)[_progressBarRepo], babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings]);
	    eventHandler.execute(babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid], babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getRows().getSelectedIds(), (_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getActionsPanel()) == null ? void 0 : (_babelHelpers$classPr3 = _babelHelpers$classPr2.getForAllCheckbox()) == null ? void 0 : _babelHelpers$classPr3.checked) != null ? _babelHelpers$classPr : false);
	  };
	}
	Object.defineProperty(Router, _handlers, {
	  writable: true,
	  value: new Set([])
	});

	const NOTIFICATION_AUTO_HIDE_DELAY = 5000;
	function showAnotherProcessRunningNotification() {
	  ui_notification.UI.Notification.Center.notify({
	    content: main_core.Loc.getMessage('CRM_ENTITY_LIST_PANEL_ANOTHER_PROCESS_IN_PROGRESS'),
	    autoHide: true,
	    autoHideDelay: NOTIFICATION_AUTO_HIDE_DELAY
	  });
	}

	var _entityTypeId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _valueElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("valueElement");
	var _progressBarRepo$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("progressBarRepo");
	class ExecuteAssigment extends BaseHandler {
	  constructor({
	    entityTypeId,
	    valueElementId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _valueElement, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _progressBarRepo$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1])) {
	      throw new Error('entityTypeId is required');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _valueElement)[_valueElement] = document.getElementById(valueElementId);
	    if (!main_core.Type.isElementNode(babelHelpers.classPrivateFieldLooseBase(this, _valueElement)[_valueElement])) {
	      throw new Error('value element not found');
	    }
	  }
	  static getEventName() {
	    return 'BatchManager:executeAssigment';
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$1)[_progressBarRepo$1] = progressBarRepo;
	  }
	  execute(grid, selectedIds, forAll) {
	    let assignManager = crm_autorun.BatchAssignmentManager.getItem(grid.getId());
	    if (!assignManager) {
	      assignManager = crm_autorun.BatchAssignmentManager.create(grid.getId(), {
	        gridId: grid.getId(),
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1],
	        container: babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$1)[_progressBarRepo$1].getOrCreateProgressBarContainer('assign').id
	      });
	    }
	    if (assignManager.isRunning()) {
	      return;
	    }
	    if (crm_autorun.ProcessRegistry.isProcessRunning(grid.getId())) {
	      showAnotherProcessRunningNotification();
	      return;
	    }
	    const userId = main_core.Text.toInteger(babelHelpers.classPrivateFieldLooseBase(this, _valueElement)[_valueElement].dataset.value);
	    if (userId <= 0) {
	      ui_notification.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('CRM_ENTITY_LIST_PANEL_SELECT_ASSIGNED_BY_ID'),
	        autoHide: true,
	        autoHideDelay: NOTIFICATION_AUTO_HIDE_DELAY
	      });
	      return;
	    }
	    assignManager.setAssignedById(userId);
	    if (forAll) {
	      assignManager.resetEntityIds();
	    } else {
	      assignManager.setEntityIds(selectedIds);
	    }
	    assignManager.execute();
	  }
	}

	var _valueElement$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("valueElement");
	class ExecuteConversion extends BaseHandler {
	  constructor({
	    valueElementId
	  }) {
	    super();
	    Object.defineProperty(this, _valueElement$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _valueElement$1)[_valueElement$1] = document.getElementById(valueElementId);
	    if (!main_core.Type.isElementNode(babelHelpers.classPrivateFieldLooseBase(this, _valueElement$1)[_valueElement$1])) {
	      throw new Error('value element not found');
	    }
	  }
	  static getEventName() {
	    return 'BatchManager:executeConversion';
	  }
	  execute(grid, selectedIds, forAll) {
	    const manager = crm_autorun.BatchConversionManager.getItem(grid.getId());
	    if (!manager) {
	      console.error(`BatchConversionManager with id ${grid.getId()} not found`);
	      return;
	    }
	    if (manager.isRunning()) {
	      return;
	    }
	    if (crm_autorun.ProcessRegistry.isProcessRunning(grid.getId())) {
	      showAnotherProcessRunningNotification();
	      return;
	    }
	    const schemeName = babelHelpers.classPrivateFieldLooseBase(this, _valueElement$1)[_valueElement$1].dataset.value || BX.CrmLeadConversionScheme.dealcontactcompany;
	    manager.setConfig(BX.CrmLeadConversionScheme.createConfig(schemeName));
	    if (forAll) {
	      manager.resetEntityIds();
	    } else {
	      manager.setEntityIds(selectedIds);
	    }
	    manager.execute();
	  }
	}

	var _entityTypeId$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _progressBarRepo$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("progressBarRepo");
	var _notifyOnComplete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("notifyOnComplete");
	class ExecuteDeletion extends BaseHandler {
	  constructor({
	    entityTypeId
	  }) {
	    super();
	    Object.defineProperty(this, _notifyOnComplete, {
	      value: _notifyOnComplete2
	    });
	    Object.defineProperty(this, _entityTypeId$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _progressBarRepo$2, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$2)[_entityTypeId$2] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$2)[_entityTypeId$2])) {
	      throw new Error('entityTypeId is required');
	    }
	  }
	  static getEventName() {
	    return 'BatchManager:executeDeletion';
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$2)[_progressBarRepo$2] = progressBarRepo;
	  }
	  execute(grid, selectedIds, forAll) {
	    let deletionManager = crm_autorun.BatchDeletionManager.getItem(grid.getId());
	    if (deletionManager && deletionManager.isRunning()) {
	      return;
	    }
	    if (crm_autorun.ProcessRegistry.isProcessRunning(grid.getId())) {
	      showAnotherProcessRunningNotification();
	      return;
	    }
	    if (!deletionManager) {
	      deletionManager = crm_autorun.BatchDeletionManager.create(grid.getId(), {
	        gridId: grid.getId(),
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$2)[_entityTypeId$2],
	        container: babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$2)[_progressBarRepo$2].getOrCreateProgressBarContainer('delete').id
	      });
	    }
	    if (forAll) {
	      deletionManager.resetEntityIds();
	    } else {
	      deletionManager.setEntityIds(selectedIds);
	    }
	    deletionManager.execute();
	    main_core_events.EventEmitter.subscribeOnce('BX.Crm.BatchDeletionManager:onProcessComplete', babelHelpers.classPrivateFieldLooseBase(this, _notifyOnComplete)[_notifyOnComplete].bind(this));
	  }
	}
	function _notifyOnComplete2() {
	  ui_notification.UI.Notification.Center.notify({
	    content: main_core.Loc.getMessage('CRM_ENTITY_LIST_PANEL_DELETION_ANALYTICS_WARNING'),
	    actions: [{
	      title: main_core.Loc.getMessage('CRM_ENTITY_LIST_PANEL_SHOW_DETAILS'),
	      events: {
	        click: (event, balloon) => {
	          balloon.close();
	          if (window.top.BX.Helper) {
	            window.top.BX.Helper.show('redirect=detail&code=8969825');
	          }
	        }
	      }
	    }],
	    autoHideDelay: NOTIFICATION_AUTO_HIDE_DELAY
	  });
	}

	var _entityTypeId$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _progressBarRepo$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("progressBarRepo");
	class ExecuteExclusion extends BaseHandler {
	  constructor({
	    entityTypeId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _progressBarRepo$3, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$3)[_entityTypeId$3] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$3)[_entityTypeId$3])) {
	      throw new Error('entityTypeId is required');
	    }
	  }
	  static getEventName() {
	    return 'BatchManager:executeExclusion';
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$3)[_progressBarRepo$3] = progressBarRepo;
	  }
	  execute(grid, selectedIds, forAll) {
	    let exclusionManager = crm_autorun.BatchExclusionManager.getItem(grid.getId());
	    if (exclusionManager && exclusionManager.isRunning()) {
	      return;
	    }
	    if (crm_autorun.ProcessRegistry.isProcessRunning(grid.getId())) {
	      showAnotherProcessRunningNotification();
	      return;
	    }
	    if (!exclusionManager) {
	      exclusionManager = crm_autorun.BatchExclusionManager.create(grid.getId(), {
	        gridId: grid.getId(),
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$3)[_entityTypeId$3],
	        container: babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$3)[_progressBarRepo$3].getOrCreateProgressBarContainer('exclude').id
	      });
	    }
	    if (forAll) {
	      exclusionManager.resetEntityIds();
	    } else {
	      exclusionManager.setEntityIds(selectedIds);
	    }
	    exclusionManager.execute();
	  }
	}

	var _entityTypeId$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _mergerUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mergerUrl");
	class ExecuteMerge extends BaseHandler {
	  constructor({
	    entityTypeId,
	    mergerUrl
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _mergerUrl, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$4)[_entityTypeId$4] = main_core.Text.toInteger(entityTypeId);
	    babelHelpers.classPrivateFieldLooseBase(this, _mergerUrl)[_mergerUrl] = main_core.Type.isStringFilled(mergerUrl) ? mergerUrl : null;
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$4)[_entityTypeId$4])) {
	      throw new Error('entityTypeId is required');
	    }
	  }
	  static getEventName() {
	    return 'BatchManager:executeMerge';
	  }
	  execute(grid, selectedIds, forAll) {
	    let mergeManager = crm_merger_batchmergemanager.BatchMergeManager.getItem(grid.getId());
	    if (!mergeManager) {
	      mergeManager = crm_merger_batchmergemanager.BatchMergeManager.create(grid.getId(), {
	        gridId: grid.getId(),
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$4)[_entityTypeId$4],
	        mergerUrl: babelHelpers.classPrivateFieldLooseBase(this, _mergerUrl)[_mergerUrl]
	      });
	    }
	    if (!mergeManager.isRunning() && selectedIds.length > 1) {
	      mergeManager.setEntityIds(selectedIds);
	      mergeManager.execute();
	    }
	  }
	}

	var _entityTypeId$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _valueElement$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("valueElement");
	var _progressBarRepo$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("progressBarRepo");
	class ExecuteObservers extends BaseHandler {
	  constructor({
	    entityTypeId,
	    valueElementId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _valueElement$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _progressBarRepo$4, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$5)[_entityTypeId$5] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$5)[_entityTypeId$5])) {
	      throw new Error('entityTypeId is required');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _valueElement$2)[_valueElement$2] = document.getElementById(valueElementId);
	    if (!main_core.Type.isElementNode(babelHelpers.classPrivateFieldLooseBase(this, _valueElement$2)[_valueElement$2])) {
	      throw new Error('value element not found');
	    }
	  }
	  static getEventName() {
	    return 'BatchManager:executeObservers';
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$4)[_progressBarRepo$4] = progressBarRepo;
	  }
	  execute(grid, selectedIds, forAll) {
	    let observersManager = crm_autorun.BatchObserversManager.getItem(grid.getId());
	    if (!observersManager) {
	      observersManager = crm_autorun.BatchObserversManager.create(grid.getId(), {
	        gridId: grid.getId(),
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$5)[_entityTypeId$5],
	        container: babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$4)[_progressBarRepo$4].getOrCreateProgressBarContainer('observers').id
	      });
	    }
	    if (observersManager.isRunning()) {
	      return;
	    }
	    if (crm_autorun.ProcessRegistry.isProcessRunning(grid.getId())) {
	      showAnotherProcessRunningNotification();
	      return;
	    }
	    let userIdList = main_core.Dom.attr(babelHelpers.classPrivateFieldLooseBase(this, _valueElement$2)[_valueElement$2], 'data-observers');
	    if (main_core.Type.isNull(userIdList)) {
	      userIdList = '';
	    }
	    userIdList = userIdList.toString().split(',').map(Number).filter(Boolean);
	    if (!main_core.Type.isArrayFilled(userIdList)) {
	      ui_notification.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('CRM_ENTITY_LIST_PANEL_SELECT_OBSERVERS_BY_ID'),
	        autoHide: true,
	        autoHideDelay: NOTIFICATION_AUTO_HIDE_DELAY
	      });
	      return;
	    }
	    observersManager.setObserverIdList(userIdList);
	    if (forAll) {
	      observersManager.resetEntityIds();
	    } else {
	      observersManager.setEntityIds(selectedIds);
	    }
	    observersManager.execute();
	  }
	}

	var _entityTypeId$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _progressBarRepo$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("progressBarRepo");
	class ExecuteRefreshAccountingData extends BaseHandler {
	  constructor({
	    entityTypeId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$6, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _progressBarRepo$5, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$6)[_entityTypeId$6] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$6)[_entityTypeId$6])) {
	      throw new Error('entityTypeId is required');
	    }
	  }
	  static getEventName() {
	    return 'BatchManager:executeRefreshAccountingData';
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$5)[_progressBarRepo$5] = progressBarRepo;
	  }
	  execute(grid, selectedIds, forAll) {
	    let accountingManager = crm_autorun.BatchRefreshAccountingDataManager.getItem(grid.getId());
	    if (accountingManager && accountingManager.isRunning()) {
	      return;
	    }
	    if (crm_autorun.ProcessRegistry.isProcessRunning(grid.getId())) {
	      showAnotherProcessRunningNotification();
	      return;
	    }
	    if (!accountingManager) {
	      accountingManager = crm_autorun.BatchRefreshAccountingDataManager.create(grid.getId(), {
	        gridId: grid.getId(),
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$6)[_entityTypeId$6],
	        container: babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$5)[_progressBarRepo$5].getOrCreateProgressBarContainer('refresh-accounting-data').id
	      });
	    }
	    if (forAll) {
	      accountingManager.resetEntityIds();
	    } else {
	      accountingManager.setEntityIds(selectedIds);
	    }
	    accountingManager.execute();
	  }
	}

	var _entityTypeId$7 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _progressBarRepo$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("progressBarRepo");
	class ExecuteRestartAutomation extends BaseHandler {
	  constructor({
	    entityTypeId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$7, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _progressBarRepo$6, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$7)[_entityTypeId$7] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$7)[_entityTypeId$7])) {
	      throw new Error('entityTypeId is required');
	    }
	  }
	  static getEventName() {
	    return 'BatchManager:restartAutomation';
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$6)[_progressBarRepo$6] = progressBarRepo;
	  }
	  execute(grid, selectedIds, forAll) {
	    let restartAutomationManager = crm_autorun.BatchRestartAutomationManager.getItem(grid.getId());
	    if (restartAutomationManager && restartAutomationManager.isRunning()) {
	      return;
	    }
	    if (crm_autorun.ProcessRegistry.isProcessRunning(grid.getId())) {
	      showAnotherProcessRunningNotification();
	      return;
	    }
	    if (!restartAutomationManager) {
	      restartAutomationManager = crm_autorun.BatchRestartAutomationManager.create(grid.getId(), {
	        gridId: grid.getId(),
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$7)[_entityTypeId$7],
	        container: babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$6)[_progressBarRepo$6].getOrCreateProgressBarContainer('restartAutomation').id
	      });
	    }
	    if (forAll) {
	      restartAutomationManager.resetEntityIds();
	    } else {
	      restartAutomationManager.setEntityIds(selectedIds);
	    }
	    restartAutomationManager.execute();
	  }
	}

	var _entityTypeId$8 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _valueElement$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("valueElement");
	var _progressBarRepo$7 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("progressBarRepo");
	class ExecuteSetCategory extends BaseHandler {
	  constructor({
	    entityTypeId,
	    valueElementId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$8, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _valueElement$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _progressBarRepo$7, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$8)[_entityTypeId$8] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$8)[_entityTypeId$8])) {
	      throw new Error('entityTypeId is required');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _valueElement$3)[_valueElement$3] = document.getElementById(valueElementId);
	    if (!main_core.Type.isElementNode(babelHelpers.classPrivateFieldLooseBase(this, _valueElement$3)[_valueElement$3])) {
	      throw new Error('value element not found');
	    }
	  }
	  static getEventName() {
	    return 'BatchManager:executeSetCategory';
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$7)[_progressBarRepo$7] = progressBarRepo;
	  }
	  execute(grid, selectedIds, forAll) {
	    let categoryManager = crm_autorun.BatchSetCategoryManager.getItem(grid.getId());
	    if (!categoryManager) {
	      categoryManager = crm_autorun.BatchSetCategoryManager.create(grid.getId(), {
	        gridId: grid.getId(),
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$8)[_entityTypeId$8],
	        container: babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$7)[_progressBarRepo$7].getOrCreateProgressBarContainer('set-category').id
	      });
	    }
	    if (categoryManager.isRunning()) {
	      return;
	    }
	    if (crm_autorun.ProcessRegistry.isProcessRunning(grid.getId())) {
	      showAnotherProcessRunningNotification();
	      return;
	    }
	    categoryManager.setCategoryId(main_core.Text.toInteger(babelHelpers.classPrivateFieldLooseBase(this, _valueElement$3)[_valueElement$3].dataset.value));
	    if (forAll) {
	      categoryManager.resetEntityIds();
	    } else {
	      categoryManager.setEntityIds(selectedIds);
	    }
	    categoryManager.execute();
	  }
	}

	var _entityTypeId$9 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _valueElement$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("valueElement");
	var _progressBarRepo$8 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("progressBarRepo");
	class ExecuteSetExport extends BaseHandler {
	  constructor({
	    entityTypeId,
	    valueElementId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$9, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _valueElement$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _progressBarRepo$8, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$9)[_entityTypeId$9] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$9)[_entityTypeId$9])) {
	      throw new Error('entityTypeId is required');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _valueElement$4)[_valueElement$4] = document.getElementById(valueElementId);
	    if (!main_core.Type.isElementNode(babelHelpers.classPrivateFieldLooseBase(this, _valueElement$4)[_valueElement$4])) {
	      throw new Error('value element not found');
	    }
	  }
	  static getEventName() {
	    return 'BatchManager:executeSetExport';
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$8)[_progressBarRepo$8] = progressBarRepo;
	  }
	  execute(grid, selectedIds, forAll) {
	    let setExportManager = crm_autorun.BatchSetExportManager.getItem(grid.getId());
	    if (!setExportManager) {
	      setExportManager = crm_autorun.BatchSetExportManager.create(grid.getId(), {
	        gridId: grid.getId(),
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$9)[_entityTypeId$9],
	        container: babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$8)[_progressBarRepo$8].getOrCreateProgressBarContainer('set-export').id
	      });
	    }
	    if (setExportManager.isRunning()) {
	      return;
	    }
	    if (crm_autorun.ProcessRegistry.isProcessRunning(grid.getId())) {
	      showAnotherProcessRunningNotification();
	      return;
	    }
	    const isExport = babelHelpers.classPrivateFieldLooseBase(this, _valueElement$4)[_valueElement$4].dataset.value;
	    if (isExport !== 'Y' && isExport !== 'N') {
	      console.error('Invalid isExport in value element', isExport, this);
	      return;
	    }
	    setExportManager.setExport(isExport);
	    if (forAll) {
	      setExportManager.resetEntityIds();
	    } else {
	      setExportManager.setEntityIds(selectedIds);
	    }
	    setExportManager.execute();
	  }
	}

	var _entityTypeId$a = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _valueElement$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("valueElement");
	var _progressBarRepo$9 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("progressBarRepo");
	class ExecuteSetOpened extends BaseHandler {
	  constructor({
	    entityTypeId,
	    valueElementId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$a, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _valueElement$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _progressBarRepo$9, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$a)[_entityTypeId$a] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$a)[_entityTypeId$a])) {
	      throw new Error('entityTypeId is required');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _valueElement$5)[_valueElement$5] = document.getElementById(valueElementId);
	    if (!main_core.Type.isElementNode(babelHelpers.classPrivateFieldLooseBase(this, _valueElement$5)[_valueElement$5])) {
	      throw new Error('value element not found');
	    }
	  }
	  static getEventName() {
	    return 'BatchManager:executeSetOpened';
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$9)[_progressBarRepo$9] = progressBarRepo;
	  }
	  execute(grid, selectedIds, forAll) {
	    let openedManager = crm_autorun.BatchSetOpenedManager.getItem(grid.getId());
	    if (!openedManager) {
	      openedManager = crm_autorun.BatchSetOpenedManager.create(grid.getId(), {
	        gridId: grid.getId(),
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$a)[_entityTypeId$a],
	        container: babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$9)[_progressBarRepo$9].getOrCreateProgressBarContainer('set-opened').id
	      });
	    }
	    if (openedManager.isRunning()) {
	      return;
	    }
	    if (crm_autorun.ProcessRegistry.isProcessRunning(grid.getId())) {
	      showAnotherProcessRunningNotification();
	      return;
	    }
	    const isOpened = babelHelpers.classPrivateFieldLooseBase(this, _valueElement$5)[_valueElement$5].dataset.value;
	    if (isOpened !== 'Y' && isOpened !== 'N') {
	      console.error('Invalid isOpened in value element', isOpened, this);
	      return;
	    }
	    openedManager.setIsOpened(isOpened);
	    if (forAll) {
	      openedManager.resetEntityIds();
	    } else {
	      openedManager.setEntityIds(selectedIds);
	    }
	    openedManager.execute();
	  }
	}

	var _entityTypeId$b = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _valueElement$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("valueElement");
	var _progressBarRepo$a = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("progressBarRepo");
	class ExecuteSetStage extends BaseHandler {
	  constructor({
	    entityTypeId,
	    valueElementId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$b, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _valueElement$6, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _progressBarRepo$a, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$b)[_entityTypeId$b] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$b)[_entityTypeId$b])) {
	      throw new Error('entityTypeId is required');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _valueElement$6)[_valueElement$6] = document.getElementById(valueElementId);
	    if (!main_core.Type.isElementNode(babelHelpers.classPrivateFieldLooseBase(this, _valueElement$6)[_valueElement$6])) {
	      throw new Error('value element not found');
	    }
	  }
	  static getEventName() {
	    return 'BatchManager:executeSetStage';
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$a)[_progressBarRepo$a] = progressBarRepo;
	  }
	  execute(grid, selectedIds, forAll) {
	    let stageManager = crm_autorun.BatchSetStageManager.getItem(grid.getId());
	    if (!stageManager) {
	      stageManager = crm_autorun.BatchSetStageManager.create(grid.getId(), {
	        gridId: grid.getId(),
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$b)[_entityTypeId$b],
	        container: babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$a)[_progressBarRepo$a].getOrCreateProgressBarContainer('set-stage').id
	      });
	    }
	    if (stageManager.isRunning()) {
	      return;
	    }
	    if (crm_autorun.ProcessRegistry.isProcessRunning(grid.getId())) {
	      showAnotherProcessRunningNotification();
	      return;
	    }
	    const stageId = babelHelpers.classPrivateFieldLooseBase(this, _valueElement$6)[_valueElement$6].dataset.value;
	    if (!main_core.Type.isStringFilled(stageId)) {
	      console.error('Empty stage id in value element', stageId, this);
	      return;
	    }
	    stageManager.setStageId(stageId);
	    if (forAll) {
	      stageManager.resetEntityIds();
	    } else {
	      stageManager.setEntityIds(selectedIds);
	    }
	    this.registerAnalyticsCloseEvent(forAll, selectedIds, stageId);
	    stageManager.execute();
	  }
	  registerAnalyticsCloseEvent(forAll, selectedIds, stageId) {
	    const stage = JSON.parse(babelHelpers.classPrivateFieldLooseBase(this, _valueElement$6)[_valueElement$6].dataset.items).find(obj => {
	      return obj.VALUE === stageId;
	    });
	    if (!stage.SEMANTICS) {
	      return;
	    }
	    let element = null;
	    if (stage.SEMANTICS === 'F') {
	      element = BX.Crm.Integration.Analytics.Dictionary.ELEMENT_GRID_GROUP_ACTIONS_LOSE_STAGE;
	    }
	    if (stage.SEMANTICS === 'S') {
	      element = BX.Crm.Integration.Analytics.Dictionary.ELEMENT_GRID_GROUP_ACTIONS_WON_STAGE;
	    }
	    const entityIds = forAll ? '' : selectedIds.toString();
	    const analyticsData = BX.Crm.Integration.Analytics.Builder.Entity.CloseEvent.createDefault(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$b)[_entityTypeId$b], entityIds).setSubSection(BX.Crm.Integration.Analytics.Dictionary.SUB_SECTION_LIST).setElement(element).buildData();
	    if (forAll) {
	      analyticsData.p3 = 'for_all';
	    }
	    analyticsData.status = BX.Crm.Integration.Analytics.Dictionary.STATUS_ATTEMPT;
	    BX.UI.Analytics.sendData(analyticsData);
	  }
	}

	var _entityTypeId$c = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _categoryId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("categoryId");
	var _isWhatsAppEdnaEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isWhatsAppEdnaEnabled");
	var _ednaManageUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ednaManageUrl");
	var _progressBarRepo$b = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("progressBarRepo");
	var _isEntityTypeSupported = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isEntityTypeSupported");
	var _showConnectEdnaSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showConnectEdnaSlider");
	class ExecuteWhatsappMessage extends BaseHandler {
	  constructor({
	    entityTypeId: _entityTypeId2,
	    categoryId,
	    isWhatsAppEdnaEnabled,
	    ednaManageUrl
	  }) {
	    super();
	    Object.defineProperty(this, _showConnectEdnaSlider, {
	      value: _showConnectEdnaSlider2
	    });
	    Object.defineProperty(this, _isEntityTypeSupported, {
	      value: _isEntityTypeSupported2
	    });
	    Object.defineProperty(this, _entityTypeId$c, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _categoryId$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isWhatsAppEdnaEnabled, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _ednaManageUrl, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _progressBarRepo$b, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$c)[_entityTypeId$c] = _entityTypeId2;
	    babelHelpers.classPrivateFieldLooseBase(this, _categoryId$1)[_categoryId$1] = categoryId;
	    babelHelpers.classPrivateFieldLooseBase(this, _isWhatsAppEdnaEnabled)[_isWhatsAppEdnaEnabled] = isWhatsAppEdnaEnabled;
	    babelHelpers.classPrivateFieldLooseBase(this, _ednaManageUrl)[_ednaManageUrl] = ednaManageUrl;
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$b)[_progressBarRepo$b] = progressBarRepo;
	  }
	  static getEventName() {
	    return 'BatchManager:whatsappMessage';
	  }
	  async execute(grid, selectedIds, forAll) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isWhatsAppEdnaEnabled)[_isWhatsAppEdnaEnabled]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showConnectEdnaSlider)[_showConnectEdnaSlider]();
	      return;
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isEntityTypeSupported)[_isEntityTypeSupported](babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$c)[_entityTypeId$c])) {
	      console.error(`entityTypeId ${babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$c)[_entityTypeId$c]} is not supported for whatsapp message`);
	      return;
	    }
	    try {
	      const exports = await main_core.Runtime.loadExtension('crm.group-actions.messages');
	      const {
	        Messages
	      } = exports;
	      const options = {
	        gridId: grid.getId(),
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$c)[_entityTypeId$c],
	        categoryId: babelHelpers.classPrivateFieldLooseBase(this, _categoryId$1)[_categoryId$1],
	        selectedIds,
	        forAll
	      };
	      const whatsAppMessage = Messages.getInstance(babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo$b)[_progressBarRepo$b], options);
	      await whatsAppMessage.execute();
	    } catch (e) {
	      console.error(e);
	    }
	  }
	}
	function _isEntityTypeSupported2(entityTypeId) {
	  const supportTypes = [BX.CrmEntityType.enumeration.contact, BX.CrmEntityType.enumeration.company];
	  return supportTypes.includes(entityTypeId);
	}
	function _showConnectEdnaSlider2() {
	  BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldLooseBase(this, _ednaManageUrl)[_ednaManageUrl], {
	    width: 700,
	    events: {
	      onClose(e) {
	        BX.SidePanel.Instance.postMessage(e.getSlider(), 'ContactCenter:reloadItem', {
	          moduleId: 'imopenlines',
	          itemCode: 'whatsappbyedna'
	        });
	      }
	    }
	  });
	}

	var _entityTypeId$d = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _callListId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("callListId");
	var _context = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("context");
	class AddItemsToCallList extends BaseHandler {
	  constructor({
	    entityTypeId,
	    callListId,
	    context
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$d, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _callListId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _context, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$d)[_entityTypeId$d] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$d)[_entityTypeId$d])) {
	      throw new Error('entityTypeId is required');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _callListId)[_callListId] = main_core.Text.toInteger(callListId);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _callListId)[_callListId] <= 0) {
	      throw new Error('callListId is required');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _context)[_context] = String(context);
	    if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _context)[_context])) {
	      throw new Error('context is required');
	    }
	  }
	  static getEventName() {
	    return 'CallList:addItemsToCallList';
	  }
	  execute(grid, selectedIds, forAll) {
	    if (selectedIds.length === 0 && !forAll) {
	      return;
	    }
	    addItemsToCallList(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$d)[_entityTypeId$d], selectedIds, babelHelpers.classPrivateFieldLooseBase(this, _callListId)[_callListId], babelHelpers.classPrivateFieldLooseBase(this, _context)[_context], grid.getId(), forAll);
	  }
	}

	var _entityTypeId$e = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	class CreateAndStartCallList extends BaseHandler {
	  constructor({
	    entityTypeId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$e, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$e)[_entityTypeId$e] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$e)[_entityTypeId$e])) {
	      throw new Error('entityTypeId is required');
	    }
	  }
	  static getEventName() {
	    return 'CallList:createAndStartCallList';
	  }
	  execute(grid, selectedIds, forAll) {
	    createCallListAndShowAlertOnErrors(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$e)[_entityTypeId$e], selectedIds, true, grid.getId(), forAll);
	  }
	}

	var _entityTypeId$f = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	class CreateCallList extends BaseHandler {
	  constructor({
	    entityTypeId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$f, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$f)[_entityTypeId$f] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$f)[_entityTypeId$f])) {
	      throw new Error('entityTypeId is required');
	    }
	  }
	  static getEventName() {
	    return 'CallList:createCallList';
	  }
	  execute(grid, selectedIds, forAll) {
	    createCallListAndShowAlertOnErrors(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$f)[_entityTypeId$f], selectedIds, false, grid.getId(), forAll);
	  }
	}

	var _entityTypeId$g = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _extensionSettings$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extensionSettings");
	class OpenTaskCreationForm extends BaseHandler {
	  constructor({
	    entityTypeId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$g, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _extensionSettings$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$g)[_entityTypeId$g] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$g)[_entityTypeId$g])) {
	      throw new Error('entityTypeId is required');
	    }
	  }
	  static getEventName() {
	    return 'openTaskCreationForm';
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings$1)[_extensionSettings$1] = extensionSettings;
	  }
	  execute(grid, selectedIds, forAll) {
	    const urlTemplate = String(babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings$1)[_extensionSettings$1].get('taskCreateUrl'));
	    if (urlTemplate === '') {
	      return;
	    }
	    const entityTypeName = BX.CrmEntityType.resolveName(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$g)[_entityTypeId$g]);
	    const entityKeys = selectedIds.map(id => BX.CrmEntityType.prepareEntityKey(entityTypeName, id));
	    const url = urlTemplate.replace(encodeURIComponent('#USER_ID#'), main_core.Loc.getMessage('USER_ID')).replace(encodeURIComponent('#ENTITY_KEYS#'), entityKeys.join(';'));
	    if (main_core.Reflection.getClass('BX.SidePanel.Instance.open')) {
	      BX.SidePanel.Instance.open(url);
	    } else {
	      window.open(url);
	    }
	  }
	}

	var _targetElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("targetElement");
	class RenderUserTagSelector extends BaseHandler {
	  constructor({
	    targetElementId
	  }) {
	    super();
	    Object.defineProperty(this, _targetElement, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _targetElement)[_targetElement] = document.getElementById(targetElementId);
	    if (!main_core.Type.isElementNode(babelHelpers.classPrivateFieldLooseBase(this, _targetElement)[_targetElement])) {
	      throw new Error('target element not found');
	    }
	  }
	  static getEventName() {
	    return 'renderUserTagSelector';
	  }
	  execute(grid, selectedIds, forAll) {
	    const tagSelector = new ui_entitySelector.TagSelector({
	      multiple: false,
	      dialogOptions: {
	        context: `crm.entity-list.${RenderUserTagSelector.getEventName()}.${grid.getId()}`,
	        entities: [{
	          id: 'user'
	        }]
	      },
	      events: {
	        onTagAdd: event => {
	          const {
	            tag
	          } = event.getData();
	          babelHelpers.classPrivateFieldLooseBase(this, _targetElement)[_targetElement].dataset.value = String(tag.getId());
	        },
	        onTagRemove: event => {
	          const {
	            tag
	          } = event.getData();
	          if (String(babelHelpers.classPrivateFieldLooseBase(this, _targetElement)[_targetElement].dataset.value) === String(tag.getId())) {
	            delete babelHelpers.classPrivateFieldLooseBase(this, _targetElement)[_targetElement].dataset.value;
	          }
	        }
	      }
	    });
	    tagSelector.renderTo(babelHelpers.classPrivateFieldLooseBase(this, _targetElement)[_targetElement]);
	  }
	}

	var _targetElement$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("targetElement");
	var _tagSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tagSelector");
	class RenderUserTagMultipleSelector extends BaseHandler {
	  constructor({
	    targetElementId
	  }) {
	    super();
	    Object.defineProperty(this, _targetElement$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _tagSelector, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _targetElement$1)[_targetElement$1] = document.getElementById(targetElementId);
	    if (!main_core.Type.isElementNode(babelHelpers.classPrivateFieldLooseBase(this, _targetElement$1)[_targetElement$1])) {
	      throw new Error('target element not found');
	    }
	  }
	  static getEventName() {
	    return 'renderUserTagMultipleSelector';
	  }
	  execute(grid, selectedIds, forAll) {
	    babelHelpers.classPrivateFieldLooseBase(this, _tagSelector)[_tagSelector] = new ui_entitySelector.TagSelector({
	      multiple: true,
	      dialogOptions: {
	        context: `crm.entity-list.${RenderUserTagMultipleSelector.getEventName()}.${grid.getId()}`,
	        entities: [{
	          id: 'user'
	        }]
	      },
	      events: {
	        onTagAdd: () => {
	          this.updateDatasetValue();
	        },
	        onTagRemove: () => {
	          this.updateDatasetValue();
	        }
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _tagSelector)[_tagSelector].renderTo(babelHelpers.classPrivateFieldLooseBase(this, _targetElement$1)[_targetElement$1]);
	  }
	  updateDatasetValue() {
	    const tags = babelHelpers.classPrivateFieldLooseBase(this, _tagSelector)[_tagSelector].getTags();
	    main_core.Dom.attr(babelHelpers.classPrivateFieldLooseBase(this, _targetElement$1)[_targetElement$1], 'data-observers', tags.map(tag => tag.id).toString());
	  }
	}

	function saveEntitiesToSegment(segmentId, entityTypeId, entityIds, gridId) {
	  return new Promise((resolve, reject) => {
	    main_core.ajax.runAction('crm.integration.sender.segment.upload', {
	      data: {
	        segmentId,
	        entityTypeName: BX.CrmEntityType.resolveName(entityTypeId),
	        entities: entityIds,
	        gridId
	      }
	    }).then(({
	      data
	    }) => {
	      if ('errors' in data) {
	        ui_dialogs_messagebox.MessageBox.alert(main_core.Text.encode(data.errors.join('\n')));
	        reject();
	        return;
	      }
	      resolve({
	        segment: data
	      });
	    }).catch(reject);
	  });
	}

	var _entityTypeId$h = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _valueElement$7 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("valueElement");
	class AddItemsToSegment extends BaseHandler {
	  constructor({
	    entityTypeId,
	    valueElementId
	  }) {
	    super();
	    Object.defineProperty(this, _entityTypeId$h, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _valueElement$7, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$h)[_entityTypeId$h] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$h)[_entityTypeId$h])) {
	      throw new Error('entityTypeId is required');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _valueElement$7)[_valueElement$7] = document.getElementById(valueElementId);
	    if (!main_core.Type.isElementNode(babelHelpers.classPrivateFieldLooseBase(this, _valueElement$7)[_valueElement$7])) {
	      throw new Error('value element not found');
	    }
	  }
	  static getEventName() {
	    return 'Sender:addItemsToSegment';
	  }
	  execute(grid, selectedIds, forAll) {
	    const segmentId = main_core.Text.toInteger(babelHelpers.classPrivateFieldLooseBase(this, _valueElement$7)[_valueElement$7].dataset.value);
	    grid.disableActionsPanel();
	    void saveEntitiesToSegment(segmentId <= 0 ? null : segmentId, babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$h)[_entityTypeId$h], selectedIds, forAll ? grid.getId() : null).then(({
	      segment
	    }) => {
	      if (segment.textSuccess) {
	        ui_notification.UI.Notification.Center.notify({
	          content: segment.textSuccess,
	          autoHide: true,
	          autoHideDelay: NOTIFICATION_AUTO_HIDE_DELAY
	        });
	      }
	    }).finally(() => grid.enableActionsPanel());
	  }
	}

	var _settings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("settings");
	var _entityTypeId$i = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _valueElement$8 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("valueElement");
	var _getAvailableLetterCodes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAvailableLetterCodes");
	class AddLetter extends BaseHandler {
	  constructor({
	    entityTypeId,
	    valueElementId
	  }) {
	    super();
	    Object.defineProperty(this, _getAvailableLetterCodes, {
	      value: _getAvailableLetterCodes2
	    });
	    Object.defineProperty(this, _settings, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityTypeId$i, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _valueElement$8, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$i)[_entityTypeId$i] = main_core.Text.toInteger(entityTypeId);
	    if (!BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$i)[_entityTypeId$i])) {
	      throw new Error('entityTypeId is required');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _valueElement$8)[_valueElement$8] = document.getElementById(valueElementId);
	    if (!main_core.Type.isElementNode(babelHelpers.classPrivateFieldLooseBase(this, _valueElement$8)[_valueElement$8])) {
	      throw new Error('value element not found');
	    }
	  }
	  static getEventName() {
	    return 'Sender:addLetter';
	  }
	  injectDependencies(progressBarRepo, extensionSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings] = extensionSettings;
	  }
	  execute(grid, selectedIds, forAll) {
	    const letterCode = babelHelpers.classPrivateFieldLooseBase(this, _valueElement$8)[_valueElement$8].dataset.value;
	    if (!main_core.Type.isStringFilled(letterCode)) {
	      return;
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _getAvailableLetterCodes)[_getAvailableLetterCodes]().includes(letterCode) && main_core.Reflection.getClass('BX.Sender.B24License')) {
	      BX.Sender.B24License.showMailingPopup();
	      return;
	    }
	    void saveEntitiesToSegment(null, babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$i)[_entityTypeId$i], selectedIds, forAll ? grid.getId() : null).then(({
	      segment
	    }) => {
	      const url = babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings].get('sender.letterAddUrl').replace('#code#', letterCode).replace('#segment_id#', segment.id);
	      BX.SidePanel.Instance.open(url, {
	        cacheable: false
	      });
	    });
	  }
	}
	function _getAvailableLetterCodes2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings].get('sender.availableLetterCodes') || [];
	}

	// region batch processing
	Router.registerHandler(ExecuteDeletion);
	Router.registerHandler(ExecuteSetStage);
	Router.registerHandler(ExecuteSetCategory);
	Router.registerHandler(ExecuteSetOpened);
	Router.registerHandler(ExecuteSetExport);
	Router.registerHandler(ExecuteMerge);
	Router.registerHandler(ExecuteExclusion);
	Router.registerHandler(ExecuteAssigment);
	Router.registerHandler(ExecuteConversion);
	Router.registerHandler(ExecuteWhatsappMessage);
	Router.registerHandler(ExecuteRefreshAccountingData);
	Router.registerHandler(ExecuteRestartAutomation);
	Router.registerHandler(ExecuteObservers);
	// endregion

	// region call list
	Router.registerHandler(CreateCallList);
	Router.registerHandler(CreateAndStartCallList);
	Router.registerHandler(AddItemsToCallList);
	// endregion

	// region sender
	Router.registerHandler(AddLetter);
	Router.registerHandler(AddItemsToSegment);
	// endregion

	Router.registerHandler(RenderUserTagSelector);
	Router.registerHandler(RenderUserTagMultipleSelector);
	Router.registerHandler(OpenTaskCreationForm);
	Router.registerHandler(LoadEnumsAndEditSelected);

	/**
	 * @memberOf BX.Crm.EntityList.Panel
	 */
	function init({
	  gridId,
	  progressBarContainerId
	}) {
	  if (!main_core.Reflection.getClass('BX.Main.gridManager.getInstanceById')) {
	    console.error('BX.Main.gridManager is not found on page');
	    return;
	  }
	  const grid = BX.Main.gridManager.getInstanceById(gridId);
	  if (!grid) {
	    console.error('grid not found', gridId);
	    return;
	  }
	  const progressBarContainer = document.getElementById(progressBarContainerId);
	  if (!main_core.Type.isElementNode(progressBarContainer)) {
	    console.error('progressBarContainer not found', progressBarContainerId);
	    return;
	  }
	  const progressBarRepo = new crm_autorun.ProgressBarRepository(progressBarContainer);
	  const settings = main_core.Extension.getSettings('crm.entity-list.panel');
	  const eventRouter = new Router(grid, progressBarRepo, settings);
	  eventRouter.startListening();
	}

	/**
	 * @memberof BX.Crm.EntityList.Panel
	 */
	function loadEnumsGridEditData(grid, entityTypeId, categoryId) {
	  return LoadEnumsAndEditSelected.loadEnums(grid, entityTypeId, categoryId);
	}

	exports.init = init;
	exports.loadEnumsGridEditData = loadEnumsGridEditData;
	exports.createCallList = createCallList;
	exports.createCallListAndShowAlertOnErrors = createCallListAndShowAlertOnErrors;

}((this.BX.Crm.EntityList.Panel = this.BX.Crm.EntityList.Panel || {}),BX,BX.Collections,BX.Event,BX.Crm,BX.Crm.Autorun,BX.UI.EntitySelector,BX,BX.UI.Dialogs,BX));
//# sourceMappingURL=panel.bundle.js.map
