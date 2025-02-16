/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_designTokens,crm_integration_analytics,ui_analytics,ui_dialogs_messagebox,main_core) {
	'use strict';

	let instance = null;

	/**
	 * @memberOf BX.Crm.Autorun
	 */
	var _gridIdToProcessCountMap = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("gridIdToProcessCountMap");
	class ProcessRegistry {
	  constructor() {
	    Object.defineProperty(this, _gridIdToProcessCountMap, {
	      writable: true,
	      value: new Map()
	    });
	  }
	  static get Instance() {
	    if (window.top !== window && main_core.Reflection.getClass('top.BX.Crm.Autorun.ProcessRegistry')) {
	      return window.top.BX.Crm.Autorun.ProcessRegistry.Instance;
	    }
	    if (instance === null) {
	      instance = new ProcessRegistry();
	    }
	    return instance;
	  }
	  isProcessRunning(gridId) {
	    return this.getProcessCount(gridId) > 0;
	  }
	  getProcessCount(gridId) {
	    var _babelHelpers$classPr;
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _gridIdToProcessCountMap)[_gridIdToProcessCountMap].get(gridId)) != null ? _babelHelpers$classPr : 0;
	  }
	  registerProcessRun(gridId) {
	    var _babelHelpers$classPr2;
	    const currentCount = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _gridIdToProcessCountMap)[_gridIdToProcessCountMap].get(gridId)) != null ? _babelHelpers$classPr2 : 0;
	    babelHelpers.classPrivateFieldLooseBase(this, _gridIdToProcessCountMap)[_gridIdToProcessCountMap].set(gridId, currentCount + 1);
	  }
	  registerProcessStop(gridId) {
	    var _babelHelpers$classPr3;
	    const currentCount = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _gridIdToProcessCountMap)[_gridIdToProcessCountMap].get(gridId)) != null ? _babelHelpers$classPr3 : 0;
	    let newCount = currentCount - 1;
	    if (newCount < 0) {
	      newCount = 0;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _gridIdToProcessCountMap)[_gridIdToProcessCountMap].set(gridId, newCount);
	  }
	  static isProcessRunning(gridId) {
	    return this.Instance.isProcessRunning(gridId);
	  }
	}

	/**
	 * @memberOf BX.Crm.Autorun
	 * @alias BX.AutoRunProcessState
	 */
	const ProcessState = Object.freeze({
	  intermediate: 0,
	  running: 1,
	  completed: 2,
	  stopped: 3,
	  error: 4
	});

	/* eslint-disable @bitrix24/bitrix24-rules/no-pseudo-private,no-underscore-dangle,no-throw-literal */

	/**
	 * @memberOf BX.Crm.Autorun
	 * @alias BX.AutorunProcessPanel
	 */
	class ProcessPanel {
	  static isExists(id) {
	    return id in ProcessPanel.items;
	  }
	  static create(id, settings) {
	    const self = new ProcessPanel(id, settings);
	    ProcessPanel.items[self.getId()] = self;
	    return self;
	  }
	  constructor(id, settings) {
	    this._id = '';
	    this._settings = {};
	    this._manager = null;
	    this._container = null;
	    this._wrapper = null;
	    this._stateNode = null;
	    this._progressNode = null;
	    this._hasLayout = false;
	    this._isHidden = false;
	    this._id = id;
	    this._settings = settings || {};
	    this._container = BX(this.getSetting('container'));
	    if (!BX.type.isElementNode(this._container)) {
	      throw 'AutorunProcessPanel: Could not find container.';
	    }
	    this._manager = this.getSetting('manager');
	    this._isHidden = this.getSetting('isHidden', false);
	  }
	  getId() {
	    return this._id;
	  }
	  getSetting(name, defaultval) {
	    return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	  }
	  scrollInToView() {
	    if (!this._container) {
	      return;
	    }
	    const rect = BX.pos(this._container);
	    if (window.scrollY > rect.top) {
	      window.scrollTo(window.scrollX, rect.top);
	    }
	  }
	  layout() {
	    if (this._hasLayout) {
	      return;
	    }
	    this._wrapper = BX.create('DIV', {
	      attrs: {
	        className: 'crm-view-progress'
	      }
	    });
	    BX.addClass(this._wrapper, this._isHidden ? 'crm-view-progress-hide' : 'crm-view-progress-show crm-view-progress-bar-active');
	    this._container.appendChild(this._wrapper);
	    this._wrapper.appendChild(BX.create('DIV', {
	      attrs: {
	        className: 'crm-view-progress-info'
	      },
	      text: this.getSetting('title', 'Please wait...')
	    }));
	    this._progressNode = BX.create('DIV', {
	      attrs: {
	        className: 'crm-view-progress-bar-line'
	      }
	    });
	    this._stateNode = BX.create('DIV', {
	      attrs: {
	        className: 'crm-view-progress-steps'
	      }
	    });
	    this._wrapper.appendChild(BX.create('DIV', {
	      attrs: {
	        className: 'crm-view-progress-inner'
	      },
	      children: [BX.create('DIV', {
	        attrs: {
	          className: 'crm-view-progress-bar'
	        },
	        children: [this._progressNode]
	      }), this._stateNode]
	    }));
	    if (BX.prop.getBoolean(this._settings, 'enableCancellation', false)) {
	      this._wrapper.appendChild(BX.create('a', {
	        attrs: {
	          className: 'crm-view-progress-link',
	          href: '#'
	        },
	        text: main_core.Loc.getMessage('JS_CORE_WINDOW_CANCEL'),
	        events: {
	          click: this.onCancelButtonClick.bind(this)
	        }
	      }));
	    }
	    this._hasLayout = true;
	  }
	  hasLayout() {
	    return this._hasLayout;
	  }
	  isHidden() {
	    return this._isHidden;
	  }
	  show() {
	    if (!this._isHidden) {
	      return;
	    }
	    if (!this._hasLayout) {
	      return;
	    }
	    BX.removeClass(this._wrapper, 'crm-view-progress-hide');
	    BX.addClass(this._wrapper, 'crm-view-progress-show');
	    this._isHidden = false;
	  }
	  hide() {
	    if (this._isHidden) {
	      return;
	    }
	    if (!this._hasLayout) {
	      return;
	    }
	    BX.removeClass(this._wrapper, 'crm-view-progress-show');
	    BX.addClass(this._wrapper, 'crm-view-progress-hide');
	    this._isHidden = true;
	  }
	  clearLayout() {
	    if (!this._hasLayout) {
	      return;
	    }
	    BX.remove(this._wrapper);
	    this._wrapper = this._stateNode = null;
	    this._hasLayout = false;
	  }
	  getManager() {
	    return this._manager;
	  }
	  setManager(manager) {
	    this._manager = manager;
	  }
	  onManagerStateChange() {
	    if (!(this._hasLayout && this._manager)) {
	      return;
	    }
	    const state = this._manager.getState();
	    if (state !== ProcessState.error) {
	      const processed = this._manager.getProcessedItemCount();
	      const total = this._manager.getTotalItemCount();
	      let progress = 0;
	      if (total !== 0) {
	        progress = Math.floor(processed / total * 100);
	        const offset = progress % 5;
	        if (offset !== 0) {
	          progress -= offset;
	        }
	      }
	      if (processed > 0 && total > 0) {
	        const template = this.getSetting('stateTemplate', main_core.Loc.getMessage('CRM_AUTORUN_PROCESS_PANEL_DEFAULT_STATE_TEMPLATE'));
	        this._stateNode.innerHTML = template.replace('#processed#', processed).replace('#total#', total);
	      } else {
	        this._stateNode.innerHTML = '';
	      }
	      this._progressNode.className = 'crm-view-progress-bar-line';
	      if (progress > 0) {
	        this._progressNode.className += ` crm-view-progress-line-${progress.toString()}`;
	      }
	    }
	  }
	  onCancelButtonClick(e) {
	    this._manager.stop();
	    return BX.eventReturnFalse(e);
	  }
	}
	ProcessPanel.items = {};

	/* eslint-disable @bitrix24/bitrix24-rules/no-pseudo-private,no-underscore-dangle,no-throw-literal */

	/**
	 * @memberof BX.Crm.Autorun
	 * @alias BX.AutorunProcessManager
	 */
	var _setErrorsFromResponseData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setErrorsFromResponseData");
	class Processor {
	  static create(id, settings) {
	    const self = new Processor(id, settings);
	    Processor.items[self.getId()] = self;
	    return self;
	  }
	  static createIfNotExists(id, settings) {
	    if (id in Processor.items) {
	      return Processor.items[id];
	    }
	    return Processor.create(id, settings);
	  }
	  constructor(id, settings) {
	    Object.defineProperty(this, _setErrorsFromResponseData, {
	      value: _setErrorsFromResponseData2
	    });
	    this._id = '';
	    this._settings = {};
	    this._serviceUrl = '';
	    this._actionName = '';
	    this._controllerActionName = '';
	    this._params = null;
	    this._container = null;
	    this._panel = null;
	    this._runHandle = 0;
	    this._hasLayout = false;
	    this._state = ProcessState.intermediate;
	    this._processedItemCount = 0;
	    this._totalItemCount = 0;
	    this._errors = [];
	    this._id = main_core.Type.isStringFilled(id) ? id : `crm_lrp_mgr_${Math.random().toString().slice(2)}`;
	    this._settings = settings || {};
	    this._serviceUrl = BX.prop.getString(this._settings, 'serviceUrl', '');
	    this._actionName = BX.prop.getString(this._settings, 'actionName', '');
	    this._controllerActionName = BX.prop.getString(this._settings, 'controllerActionName', '');
	    if (this._serviceUrl === '' && this._controllerActionName === '') {
	      throw 'AutorunProcessManager: Either the serviceUrl or controllerActionName parameter must be specified.';
	    }
	    this._container = BX(this.getSetting('container'));
	    if (!main_core.Type.isElementNode(this._container)) {
	      throw 'AutorunProcessManager: Could not find container.';
	    }
	    this._params = BX.prop.getObject(this._settings, 'params', null);
	    if (BX.prop.getBoolean(this._settings, 'enableLayout', false)) {
	      this.layout();
	    }
	  }
	  getId() {
	    return this._id;
	  }
	  getSetting(name, defaultval) {
	    if (name in this._settings) {
	      return this._settings[name];
	    }
	    return defaultval;
	  }
	  getTimeout() {
	    return BX.prop.getInteger(this._settings, 'timeout', 2000);
	  }
	  getMessage(name, defaultValue) {
	    if (name in Processor.messages) {
	      return Processor.messages[name];
	    }
	    return main_core.Type.isUndefined(defaultValue) ? name : defaultValue;
	  }
	  getParams() {
	    return this._params;
	  }
	  setParams(params) {
	    this._params = params;
	  }
	  isHidden() {
	    return !this._hasLayout || this._panel.isHidden();
	  }
	  show() {
	    if (this._hasLayout) {
	      this._panel.show();
	    }
	  }
	  hide() {
	    if (this._hasLayout) {
	      this._panel.hide();
	    }
	  }
	  scrollInToView() {
	    if (this._panel) {
	      this._panel.scrollInToView();
	    }
	  }
	  layout() {
	    if (this._hasLayout) {
	      return;
	    }
	    if (!this._panel) {
	      let title = BX.prop.getString(this._settings, 'title', '');
	      if (title === '') {
	        title = this.getMessage('title', '');
	      }
	      let stateTemplate = BX.prop.getString(this._settings, 'stateTemplate', '');
	      if (stateTemplate === '') {
	        stateTemplate = this.getMessage('stateTemplate', '');
	      }
	      const panelSettings = {
	        manager: this,
	        container: this._container,
	        enableCancellation: BX.prop.getBoolean(this._settings, 'enableCancellation', false)
	      };
	      if (main_core.Type.isStringFilled(title)) {
	        panelSettings.title = title;
	      }
	      if (main_core.Type.isStringFilled(stateTemplate)) {
	        panelSettings.stateTemplate = stateTemplate;
	      }
	      this._panel = ProcessPanel.create(this._id, panelSettings);
	    }
	    this._panel.layout();
	    this._hasLayout = true;
	  }
	  clearLayout() {
	    if (!this._hasLayout) {
	      return;
	    }
	    this._panel.clearLayout();
	    this._hasLayout = false;
	  }
	  getPanel() {
	    return this._panel;
	  }
	  setPanel(panel) {
	    this._panel = panel;
	    if (this._panel) {
	      this._panel.setManager(this);
	      this._hasLayout = this._panel.hasLayout();
	    } else {
	      this._hasLayout = false;
	    }
	  }
	  refresh() {
	    if (!this._hasLayout) {
	      this.layout();
	    }
	    if (this._panel.isHidden()) {
	      this._panel.show();
	    }
	    this._panel.onManagerStateChange();
	  }
	  getState() {
	    return this._state;
	  }
	  getProcessedItemCount() {
	    return this._processedItemCount;
	  }
	  getTotalItemCount() {
	    return this._totalItemCount;
	  }
	  getErrorCount() {
	    return this._errors.length;
	  }
	  getErrors() {
	    return this._errors;
	  }
	  run() {
	    if (this._state === ProcessState.stopped) {
	      this._state = ProcessState.intermediate;
	    }
	    this.startRequest();
	  }
	  runAfter(timeout) {
	    this._runHandle = window.setTimeout(this.run.bind(this), timeout);
	  }
	  stop() {
	    this._state = ProcessState.stopped;
	    BX.onCustomEvent(this, 'ON_AUTORUN_PROCESS_STATE_CHANGE', [this]);
	  }
	  reset() {
	    if (this._runHandle > 0) {
	      window.clearTimeout(this._runHandle);
	      this._runHandle = 0;
	    }
	    if (this._panel && this._panel.isHidden()) {
	      this._panel.show();
	    }
	    this._processedItemCount = 0;
	    this._totalItemCount = 0;
	  }
	  startRequest() {
	    if (this._state === ProcessState.stopped) {
	      return;
	    }
	    if (this._requestIsRunning) {
	      return;
	    }
	    this._requestIsRunning = true;
	    this._state = ProcessState.running;
	    const data = {};
	    if (this._serviceUrl === '') {
	      if (this._params) {
	        data.params = this._params;
	      }
	      main_core.ajax.runAction(this._controllerActionName, {
	        data
	      }).then(result => {
	        this.onRequestSuccess(BX.prop.getObject(result, 'data', {}));
	      }).catch(result => {
	        this.onRequestFailure(BX.prop.getObject(result, 'data', {}));
	      });
	    } else {
	      if (this._actionName !== '') {
	        data.ACTION = this._actionName;
	      }
	      if (this._params) {
	        data.PARAMS = this._params;
	      }
	      data.sessid = BX.bitrix_sessid();
	      main_core.ajax({
	        url: this._serviceUrl,
	        method: 'POST',
	        dataType: 'json',
	        data,
	        onsuccess: this.onRequestSuccess.bind(this),
	        onfailure: this.onRequestFailure.bind(this)
	      });
	    }
	  }
	  onRequestSuccess(result) {
	    this._requestIsRunning = false;
	    if (this._state === ProcessState.stopped) {
	      return;
	    }
	    if (this._serviceUrl === '') {
	      const status = BX.prop.getString(result, 'status', '');
	      if (status === 'ERROR') {
	        this._state = ProcessState.error;
	      } else if (status === 'COMPLETED') {
	        this._state = ProcessState.completed;
	      }
	      if (this._state === ProcessState.error) {
	        babelHelpers.classPrivateFieldLooseBase(this, _setErrorsFromResponseData)[_setErrorsFromResponseData](result);
	      } else {
	        this._processedItemCount = BX.prop.getInteger(result, 'processedItems', 0);
	        this._totalItemCount = BX.prop.getInteger(result, 'totalItems', 0);
	        this._errors = BX.prop.getArray(result, 'errors', []);
	      }
	    } else {
	      const status = BX.prop.getString(result, 'STATUS', '');
	      if (status === 'ERROR') {
	        this._state = ProcessState.error;
	      } else if (status === 'COMPLETED') {
	        this._state = ProcessState.completed;
	      }
	      if (this._state === ProcessState.error) {
	        babelHelpers.classPrivateFieldLooseBase(this, _setErrorsFromResponseData)[_setErrorsFromResponseData](result);
	      } else {
	        this._processedItemCount = BX.prop.getInteger(result, 'PROCESSED_ITEMS', 0);
	        this._totalItemCount = BX.prop.getInteger(result, 'TOTAL_ITEMS', 0);
	        this._errors = BX.prop.getArray(result, 'ERRORS', []);
	      }
	    }
	    this.refresh();
	    if (this._state === ProcessState.running) {
	      window.setTimeout(this.startRequest.bind(this), this.getTimeout());
	    } else if (this._state === ProcessState.completed && BX.prop.getBoolean(this._settings, 'hideAfterComplete', true)) {
	      this.hide();
	    }
	    BX.onCustomEvent(this, 'ON_AUTORUN_PROCESS_STATE_CHANGE', [this]);
	  }
	  onRequestFailure(result) {
	    this._requestIsRunning = false;
	    this._state = ProcessState.error;
	    babelHelpers.classPrivateFieldLooseBase(this, _setErrorsFromResponseData)[_setErrorsFromResponseData](result);
	    this.refresh();
	    BX.onCustomEvent(this, 'ON_AUTORUN_PROCESS_STATE_CHANGE', [this]);
	  }
	}
	function _setErrorsFromResponseData2(responseData) {
	  const key = this._serviceUrl === '' ? 'errors' : 'ERRORS';
	  this._errors = BX.prop.getArray(responseData, key, []);
	  if (this._errors.length === 0) {
	    this._errors.push({
	      message: this.getMessage('requestError')
	    });
	  }
	}
	Processor.messages = {
	  // default messages, you can override them via settings.messages
	  requestError: main_core.Loc.getMessage('CRM_AUTORUN_PROCESS_REQUEST_ERROR')
	};
	Processor.items = {};

	/**
	 * @memberOf BX.Crm.Autorun
	 * @alias BX.Crm.ProcessSummaryPanel
	 */
	class SummaryPanel {
	  static create(id, settings) {
	    return new SummaryPanel(id, settings);
	  }
	  constructor(id, settings) {
	    this._id = '';
	    this._settings = {};
	    this._data = null;
	    this._container = null;
	    this._wrapper = null;
	    this._id = id;
	    this._settings = settings || {};
	    this._container = BX(BX.prop.get(this._settings, 'container'));
	    if (!BX.type.isElementNode(this._container)) {
	      throw 'BatchConversionPanel: Could not find container.';
	    }
	    this._data = BX.prop.getObject(this._settings, 'data', {});
	  }
	  getId() {
	    return this._id;
	  }
	  getMessage(name) {
	    const messages = BX.prop.getObject(this._settings, 'messages', SummaryPanel.messages);
	    return BX.prop.getString(messages, name, name);
	  }
	  layout() {
	    if (this._hasLayout) {
	      return;
	    }
	    this._wrapper = BX.create('DIV', {
	      attrs: {
	        className: 'crm-view-progress'
	      }
	    });
	    BX.addClass(this._wrapper, this._isHidden ? 'crm-view-progress-hide' : 'crm-view-progress-show');
	    BX.addClass(this._wrapper, 'crm-view-progress-row-hidden');
	    this._container.appendChild(this._wrapper);
	    const summaryElements = [BX.create('span', {
	      text: this.getMessage('summaryCaption')
	    })];
	    const substitution = new RegExp(BX.prop.getString(this._settings, 'numberSubstitution', '#number#'), 'ig');
	    const succeeded = BX.prop.getInteger(this._data, 'succeededCount', 0);
	    if (succeeded > 0) {
	      summaryElements.push(BX.create('span', {
	        attrs: {
	          className: 'crm-view-progress-text'
	        },
	        text: this.getMessage('summarySucceeded').replace(substitution, succeeded)
	      }));
	    }
	    const failed = BX.prop.getInteger(this._data, 'failedCount', 0);
	    if (failed > 0) {
	      summaryElements.push(BX.create('span', {
	        attrs: {
	          className: 'crm-view-progress-link crm-view-progress-text-button'
	        },
	        text: this.getMessage('summaryFailed').replace(substitution, failed),
	        events: {
	          click: this.onToggleErrorButtonClick.bind(this)
	        }
	      }));
	    }
	    const elements = [];
	    elements.push(BX.create('DIV', {
	      attrs: {
	        className: 'crm-view-progress-info'
	      },
	      children: summaryElements
	    }));
	    elements.push(BX.create('a', {
	      attrs: {
	        className: 'crm-view-progress-link',
	        href: '#'
	      },
	      text: main_core.Loc.getMessage('JS_CORE_WINDOW_CLOSE'),
	      events: {
	        click: this.onCloseButtonClick.bind(this)
	      }
	    }));
	    this._wrapper.appendChild(BX.create('DIV', {
	      attrs: {
	        className: 'crm-view-progress-row'
	      },
	      children: elements
	    }));
	    const errors = BX.prop.getArray(this._data, 'errors', []);
	    if (errors.length > 0) {
	      for (let i = 0, length = errors.length; i < length; i++) {
	        const error = errors[i];
	        const errorElements = [];
	        const info = BX.prop.getObject(BX.prop.getObject(error, 'customData', {}), 'info', null);
	        if (info) {
	          const title = BX.prop.getString(info, 'title', '');
	          const showUrl = BX.prop.getString(info, 'showUrl', '');
	          if (title !== '' && showUrl !== '') {
	            errorElements.push(BX.create('a', {
	              props: {
	                className: 'crm-view-progress-link',
	                href: showUrl,
	                target: '_blank'
	              },
	              text: `${title}:`
	            }));
	          }
	        }
	        errorElements.push(BX.create('span', {
	          attrs: {
	            className: 'crm-view-progress-text'
	          },
	          text: error.message
	        }));
	        this._wrapper.appendChild(BX.create('DIV', {
	          attrs: {
	            className: 'crm-view-progress-row'
	          },
	          children: [BX.create('DIV', {
	            attrs: {
	              className: 'crm-view-progress-info'
	            },
	            children: errorElements
	          })]
	        }));
	      }
	    } else {
	      const timeout = this.getDisplayTimeout();
	      if (timeout > 0) {
	        window.setTimeout(() => {
	          this.clearLayout();
	        }, timeout);
	      }
	    }
	    this._hasLayout = true;
	    BX.onCustomEvent(window, 'BX.Crm.ProcessSummaryPanel:onLayout', [this]);
	  }
	  hasLayout() {
	    return this._hasLayout;
	  }
	  isHidden() {
	    return this._isHidden;
	  }
	  show() {
	    if (!this._isHidden) {
	      return;
	    }
	    if (!this._hasLayout) {
	      return;
	    }
	    BX.removeClass(this._wrapper, 'crm-view-progress-hide');
	    BX.addClass(this._wrapper, 'crm-view-progress-show');
	    this._isHidden = false;
	  }
	  hide() {
	    if (this._isHidden) {
	      return;
	    }
	    if (!this._hasLayout) {
	      return;
	    }
	    BX.removeClass(this._wrapper, 'crm-view-progress-show');
	    BX.addClass(this._wrapper, 'crm-view-progress-hide');
	    this._isHidden = true;
	  }
	  clearLayout() {
	    if (!this._hasLayout) {
	      return;
	    }
	    BX.remove(this._wrapper);
	    this._wrapper = null;
	    this._hasLayout = false;
	    BX.onCustomEvent(window, 'BX.Crm.ProcessSummaryPanel:onClearLayout', [this]);
	  }
	  getDisplayTimeout() {
	    return BX.prop.getInteger(this._settings, 'displayTimeout', 0);
	  }
	  onCloseButtonClick(e) {
	    this.clearLayout();
	    return BX.eventReturnFalse(e);
	  }
	  onToggleErrorButtonClick() {
	    BX.toggleClass(this._wrapper, 'crm-view-progress-row-hidden');
	  }
	}
	SummaryPanel.messages = {};

	/* eslint-disable @bitrix24/bitrix24-rules/no-pseudo-private,no-underscore-dangle,no-throw-literal */

	/**
	 * @abstract
	 */
	var _throwNotImplementedError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("throwNotImplementedError");
	class BatchManager {
	  constructor(id, settings) {
	    Object.defineProperty(this, _throwNotImplementedError, {
	      value: _throwNotImplementedError2
	    });
	    this._id = '';
	    this._settings = {};
	    this._gridId = '';
	    this._entityTypeId = BX.CrmEntityType.enumeration.undefined;
	    this._entityIds = null;
	    this._filter = null;
	    this._operationHash = '';
	    this._containerId = '';
	    this._errors = null;
	    this._progress = null;
	    this._hasLayout = false;
	    this._isRunning = false;
	    this._progressChangeHandler = this.onProgress.bind(this);
	    this._documentUnloadHandler = this.onDocumentUnload.bind(this);
	    this._summaryLayoutHandler = this.onSummaryLayout.bind(this);
	    this._summaryClearLayoutHandler = this.onSummaryClearLayout.bind(this);
	    this._id = main_core.Type.isStringFilled(id) ? id : `${this.getIdPrefix()}_${Math.random().toString().slice(2)}`;
	    this._settings = settings || {};
	    this._gridId = BX.prop.getString(this._settings, 'gridId', this._id);
	    this._entityTypeId = BX.prop.getInteger(this._settings, 'entityTypeId', BX.CrmEntityType.enumeration.undefined);
	    this._containerId = BX.prop.getString(this._settings, 'container', '');
	    if (this._containerId === '') {
	      throw `${this.getEventNamespace()}: Could not find container.`;
	    }

	    // region progress
	    this._progress = Processor.create(`${this.getIdPrefix()}_${this._id}`, {
	      controllerActionName: this.getProcessActionName(),
	      container: this._containerId,
	      enableCancellation: true,
	      title: this.getMessage('title'),
	      timeout: 1000,
	      stateTemplate: BX.prop.getString(this._settings, 'stateTemplate', null),
	      enableLayout: false
	    });
	    // region
	    this._errors = [];
	  }
	  getEventNamespace() {
	    return `BX.Crm.${this.getEventNamespacePostfix()}`;
	  }

	  /**
	   * @abstract
	   * @protected
	   */
	  getEventNamespacePostfix() {
	    babelHelpers.classPrivateFieldLooseBase(this, _throwNotImplementedError)[_throwNotImplementedError]();
	  }

	  /**
	   * @abstract
	   * @protected
	   */
	  getIdPrefix() {
	    babelHelpers.classPrivateFieldLooseBase(this, _throwNotImplementedError)[_throwNotImplementedError]();
	  }

	  /**
	   * @abstract
	   * @protected
	   */
	  getProcessActionName() {
	    babelHelpers.classPrivateFieldLooseBase(this, _throwNotImplementedError)[_throwNotImplementedError]();
	  }

	  /**
	   * @abstract
	   * @protected
	   */
	  getPrepareActionName() {
	    babelHelpers.classPrivateFieldLooseBase(this, _throwNotImplementedError)[_throwNotImplementedError]();
	  }

	  /**
	   * @abstract
	   * @protected
	   */
	  getCancelActionName() {
	    babelHelpers.classPrivateFieldLooseBase(this, _throwNotImplementedError)[_throwNotImplementedError]();
	  }
	  getId() {
	    return this._id;
	  }
	  getEntityIds() {
	    return this._entityIds;
	  }
	  setEntityIds(entityIds) {
	    this._entityIds = main_core.Type.isArray(entityIds) ? entityIds : [];
	  }
	  resetEntityIds() {
	    this._entityIds = [];
	  }
	  getFilter() {
	    return this._filter;
	  }
	  setFilter(filter) {
	    this._filter = main_core.Type.isPlainObject(filter) ? filter : null;
	  }
	  getMessage(name, defaultValue = null) {
	    const messages = BX.prop.getObject(this._settings, 'messages', this.getDefaultMessages());
	    return BX.prop.getString(messages, name, defaultValue != null ? defaultValue : name);
	  }

	  /**
	   * @protected
	   */
	  getDefaultMessages() {
	    // kinda 'late static binding'
	    return this.constructor.messages;
	  }
	  scrollInToView() {
	    if (this._progress) {
	      this._progress.scrollInToView();
	      this.refreshGridHeader();
	    }
	  }
	  refreshGridHeader() {
	    window.requestAnimationFrame(() => {
	      const grid = BX.Main.gridManager.getById(this._gridId);
	      if (grid && grid.instance && grid.instance.pinHeader) {
	        grid.instance.pinHeader.refreshRect();
	        grid.instance.pinHeader._onScroll();
	      }
	    });
	  }
	  layout() {
	    if (this._hasLayout) {
	      return;
	    }
	    this._progress.layout();
	    this._hasLayout = true;
	  }
	  clearLayout() {
	    if (!this._hasLayout) {
	      return;
	    }
	    this._progress.clearLayout();
	    this._hasLayout = false;
	  }
	  getState() {
	    return this._progress.getState();
	  }
	  getProcessedItemCount() {
	    return this._progress.getProcessedItemCount();
	  }
	  getTotalItemCount() {
	    return this._progress.getTotalItemCount();
	  }
	  execute() {
	    this.layout();
	    this.run();
	    window.setTimeout(this.scrollInToView.bind(this), 100);
	  }
	  isRunning() {
	    return this._isRunning;
	  }
	  run() {
	    if (this._isRunning) {
	      return;
	    }
	    this._isRunning = true;
	    ProcessRegistry.Instance.registerProcessRun(this._gridId);
	    BX.bind(window, 'beforeunload', this._documentUnloadHandler);
	    this.enableGridFilter(false);
	    main_core.ajax.runAction(this.getPrepareActionName(), {
	      data: {
	        params: this.getPrepareActionParams()
	      }
	    }).then(response => {
	      const hash = BX.prop.getString(BX.prop.getObject(response, 'data', {}), 'hash', '');
	      if (hash === '') {
	        this.reset();
	        return;
	      }
	      this._operationHash = hash;
	      this._progress.setParams({
	        hash: this._operationHash
	      });
	      this._progress.run();
	      BX.addCustomEvent(this._progress, 'ON_AUTORUN_PROCESS_STATE_CHANGE', this._progressChangeHandler);
	    }).catch(() => {
	      this.reset();
	    });
	  }
	  getPrepareActionParams() {
	    const params = {
	      gridId: this._gridId,
	      entityTypeId: this._entityTypeId,
	      extras: BX.prop.getObject(this._settings, 'extras', {})
	    };
	    if (main_core.Type.isArray(this._entityIds) && this._entityIds.length > 0) {
	      params.entityIds = this._entityIds;
	    }
	    return params;
	  }
	  stop() {
	    if (!this._isRunning) {
	      return;
	    }
	    this._isRunning = false;
	    void main_core.ajax.runAction(this.getCancelActionName(), {
	      data: {
	        params: {
	          hash: this._operationHash
	        }
	      }
	    });
	    this.reset();
	  }
	  reset() {
	    BX.unbind(window, 'beforeunload', this._documentUnloadHandler);
	    BX.removeCustomEvent(this._progress, 'ON_AUTORUN_PROCESS_STATE_CHANGE', this._progressChangeHandler);
	    this._isRunning = false;
	    ProcessRegistry.Instance.registerProcessStop(this._gridId);
	    this._operationHash = '';
	    this._errors = [];
	    const enableGridReload = this._progress.getProcessedItemCount() > 0;
	    this._progress.reset();
	    if (this._hasLayout) {
	      window.setTimeout(this.clearLayout.bind(this), 100);
	    }
	    this.enableGridFilter(true);
	    if (enableGridReload) {
	      BX.Main.gridManager.reload(this._gridId);
	    }
	  }
	  enableGridFilter(enable) {
	    const container = this._gridId === '' ? null : BX(`${this._gridId}_search_container`);
	    if (!container) {
	      return;
	    }
	    if (enable) {
	      BX.removeClass(container, 'main-ui-disable');
	    } else {
	      BX.addClass(container, 'main-ui-disable');
	    }
	  }
	  getErrorCount() {
	    return this._errors ? this._errors.length : 0;
	  }
	  getErrors() {
	    var _this$_errors;
	    return (_this$_errors = this._errors) != null ? _this$_errors : [];
	  }

	  /**
	   * Triggers a browser native warning when a user tries to close the tab that data may not be saved yet
	   */
	  onDocumentUnload(event) {
	    // recommended MDN way
	    event.preventDefault();

	    // compatibility with older browsers
	    // eslint-disable-next-line no-param-reassign
	    event.returnValue = true;
	  }
	  onProgress(sender) {
	    const state = this._progress.getState();
	    if (state === ProcessState.stopped) {
	      this.stop();
	      return;
	    }
	    const errors = this._progress.getErrors();
	    if (errors.length > 0) {
	      if (this._errors) {
	        this._errors = [...this._errors, ...errors];
	      } else {
	        this._errors = errors;
	      }
	    }
	    if (state === ProcessState.completed || state === ProcessState.error) {
	      const failed = this.getErrorCount();
	      const succeeded = this.getProcessedItemCount() - failed;
	      BX.addCustomEvent(window, 'BX.Crm.ProcessSummaryPanel:onLayout', this._summaryLayoutHandler);
	      SummaryPanel.create(this._id, {
	        container: this._containerId,
	        data: {
	          succeededCount: succeeded,
	          failedCount: failed,
	          errors: this.getErrors()
	        },
	        messages: BX.prop.getObject(this._settings, 'messages', this.constructor.messages),
	        numberSubstitution: '#number#',
	        displayTimeout: 1500
	      }).layout();
	      this.reset();
	      window.setTimeout(() => {
	        BX.onCustomEvent(window, `${this.getEventNamespace()}:onProcessComplete`, [this]);
	      }, 300);
	    }
	  }
	  onSummaryLayout() {
	    this.refreshGridHeader();
	    BX.removeCustomEvent(window, 'BX.Crm.ProcessSummaryPanel:onLayout', this._summaryLayoutHandler);
	    BX.addCustomEvent(window, 'BX.Crm.ProcessSummaryPanel:onClearLayout', this._summaryClearLayoutHandler);
	  }
	  onSummaryClearLayout() {
	    this.refreshGridHeader();
	    BX.removeCustomEvent(window, 'BX.Crm.ProcessSummaryPanel:onClearLayout', this._summaryClearLayoutHandler);
	  }
	}
	function _throwNotImplementedError2() {
	  throw new Error('not implemented');
	}
	BatchManager.messages = {};

	/* eslint-disable no-underscore-dangle */

	/**
	 * @memberOf BX.Crm.Autorun
	 */
	var _assignedById = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("assignedById");
	class BatchAssignmentManager extends BatchManager {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _assignedById, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static getItem(id) {
	    return BX.prop.get(BatchAssignmentManager.items, id, null);
	  }
	  static create(id, settings) {
	    const self = new BatchAssignmentManager(id, settings);
	    BatchAssignmentManager.items[self.getId()] = self;
	    return self;
	  }
	  getIdPrefix() {
	    return 'crm_batch_assignment_mgr';
	  }
	  getEventNamespacePostfix() {
	    return 'BatchAssignmentManager';
	  }
	  getPrepareActionName() {
	    return 'crm.api.autorun.assign.prepare';
	  }
	  getPrepareActionParams() {
	    const params = super.getPrepareActionParams();
	    params.assignedById = babelHelpers.classPrivateFieldLooseBase(this, _assignedById)[_assignedById];
	    return params;
	  }
	  getProcessActionName() {
	    return 'crm.api.autorun.assign.process';
	  }
	  getCancelActionName() {
	    return 'crm.api.autorun.assign.cancel';
	  }
	  setAssignedById(assignedById) {
	    babelHelpers.classPrivateFieldLooseBase(this, _assignedById)[_assignedById] = main_core.Text.toInteger(assignedById);
	  }
	}
	BatchAssignmentManager.messages = {
	  // default messages, you can override them via settings.messages
	  title: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_ASSIGN_TITLE'),
	  summaryCaption: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_ASSIGN_SUMMARY_CAPTION'),
	  summarySucceeded: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_ASSIGN_SUMMARY_SUCCEEDED'),
	  summaryFailed: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_ASSIGN_SUMMARY_FAILED')
	};
	BatchAssignmentManager.items = {};

	/* eslint-disable @bitrix24/bitrix24-rules/no-pseudo-private,no-underscore-dangle,no-throw-literal */

	/**
	 * @memberOf BX.Crm.Autorun
	 * @alias BX.Crm.BatchConversionManager
	 */
	var _sendAnalyticsData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsData");
	class BatchConversionManager {
	  static getItem(id) {
	    return BX.prop.get(BatchConversionManager.items, id, null);
	  }
	  static create(id, settings) {
	    const self = new BatchConversionManager(id, settings);
	    BatchConversionManager.items[self.getId()] = self;
	    return self;
	  }
	  constructor(id, settings) {
	    Object.defineProperty(this, _sendAnalyticsData, {
	      value: _sendAnalyticsData2
	    });
	    this._id = '';
	    this._settings = {};
	    this._gridId = '';
	    this._config = null;
	    this._entityIds = null;
	    this._enableUserFieldCheck = true;
	    this._enableConfigCheck = true;
	    this._filter = null;
	    this._serviceUrl = '';
	    this._containerId = '';
	    this._errors = null;
	    this._progress = null;
	    this._hasLayout = false;
	    this._succeededItemCount = 0;
	    this._failedItemCount = 0;
	    this._isRunning = false;
	    this._messages = null;
	    this._progressChangeHandler = this.onProgress.bind(this);
	    this._documentUnloadHandler = this.onDocumentUnload.bind(this);
	    this._id = main_core.Type.isStringFilled(id) ? id : `crm_batch_conversion_mgr_${Math.random().toString().slice(2)}`;
	    this._settings = settings || {};
	    this._gridId = BX.prop.getString(this._settings, 'gridId', this._id);
	    this._config = BX.prop.getObject(this._settings, 'config', {});
	    this._entityIds = BX.prop.getArray(this._settings, 'entityIds', []);
	    this._serviceUrl = BX.prop.getString(this._settings, 'serviceUrl', '');
	    if (this._serviceUrl === '') {
	      throw "BX.Crm.BatchConversionManager. Could not find 'serviceUrl' parameter in settings.";
	    }
	    this._containerId = BX.prop.getString(this._settings, 'container', '');
	    if (this._containerId === '') {
	      throw 'BX.Crm.BatchConversionManager: Could not find container.';
	    }

	    // region progress

	    const processorSettings = {
	      serviceUrl: this._serviceUrl,
	      actionName: 'PROCESS_BATCH_CONVERSION',
	      container: this._containerId,
	      enableCancellation: true,
	      title: this.getMessage('title'),
	      enableLayout: false
	    };
	    const stateTemplate = BX.prop.getString(this._settings, 'stateTemplate', null);
	    if (main_core.Type.isStringFilled(stateTemplate)) {
	      processorSettings.stateTemplate = stateTemplate;
	    }
	    this._progress = Processor.create(this._id, processorSettings);
	    // region
	    this._errors = [];
	  }
	  isRunning() {
	    return this._isRunning;
	  }
	  resetEntityIds() {
	    this._entityIds = [];
	  }
	  getId() {
	    return this._id;
	  }
	  getConfig() {
	    return this._config;
	  }
	  setConfig(config) {
	    this._config = main_core.Type.isPlainObject(config) ? config : {};
	  }
	  getEntityIds() {
	    return this._entityIds;
	  }
	  setEntityIds(entityIds) {
	    this._entityIds = main_core.Type.isArray(entityIds) ? entityIds : [];
	  }
	  getFilter() {
	    return this._filter;
	  }
	  setFilter(filter) {
	    this._filter = main_core.Type.isPlainObject(filter) ? filter : null;
	  }
	  isUserFieldCheckEnabled() {
	    return this._enableUserFieldCheck;
	  }
	  enableUserFieldCheck(enableUserFieldCheck) {
	    this._enableUserFieldCheck = enableUserFieldCheck;
	  }
	  isConfigCheckEnabled() {
	    return this._enableConfigCheck;
	  }
	  enableConfigCheck(enableConfigCheck) {
	    this._enableConfigCheck = enableConfigCheck;
	  }
	  getMessage(name) {
	    if (this._messages && BX.prop.getString(this._messages, name, null)) {
	      return BX.prop.getString(this._messages, name, name);
	    }
	    const messages = BX.prop.getObject(this._settings, 'messages', BatchConversionManager.messages);
	    return BX.prop.getString(messages, name, name);
	  }
	  layout() {
	    if (this._hasLayout) {
	      return;
	    }
	    this._progress.layout();
	    this._hasLayout = true;
	  }
	  clearLayout() {
	    if (!this._hasLayout) {
	      return;
	    }
	    this._progress.clearLayout();
	    this._hasLayout = false;
	  }
	  getState() {
	    return this._progress.getState();
	  }
	  getProcessedItemCount() {
	    return this._progress.getProcessedItemCount();
	  }
	  getTotalItemCount() {
	    return this._progress.getTotalItemCount();
	  }
	  execute() {
	    const params = {
	      GRID_ID: this._gridId,
	      CONFIG: this._config,
	      ENABLE_CONFIG_CHECK: this._enableConfigCheck ? 'Y' : 'N',
	      ENABLE_USER_FIELD_CHECK: this._enableUserFieldCheck ? 'Y' : 'N'
	    };
	    if (this._filter === null) {
	      params.IDS = this._entityIds;
	    } else {
	      params.FILTER = this._filter;
	    }
	    const data = {
	      ACTION: 'PREPARE_BATCH_CONVERSION',
	      PARAMS: params,
	      sessid: BX.bitrix_sessid()
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsData)[_sendAnalyticsData]('attempt');
	    main_core.ajax({
	      url: this._serviceUrl,
	      method: 'POST',
	      dataType: 'json',
	      data,
	      onsuccess: this.onPrepare.bind(this)
	    });
	  }
	  onPrepare(result) {
	    const data = main_core.Type.isPlainObject(result.DATA) ? result.DATA : {};
	    const status = BX.prop.getString(data, 'STATUS', '');
	    this._config = BX.prop.getObject(data, 'CONFIG', {});
	    if (data.hasOwnProperty('messages') && main_core.Type.isPlainObject(data.messages)) {
	      this._messages = data.messages;
	      if (!BX.CrmLeadConverter.messages) {
	        BX.CrmLeadConverter.messages = {};
	      }
	      BX.CrmLeadConverter.messages = Object.assign(BX.CrmLeadConverter.messages, data.messages);
	    }
	    if (status === 'ERROR') {
	      babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsData)[_sendAnalyticsData]('error');
	      const errors = BX.prop.getArray(data, 'ERRORS', []);
	      ui_dialogs_messagebox.MessageBox.alert(errors.map(error => main_core.Text.encode(error)).join('<br/>'), this.getMessage('title'));
	      return;
	    }
	    if (status === 'REQUIRES_SYNCHRONIZATION') {
	      const syncEditor = BX.CrmLeadConverter.getCurrent().createSynchronizationEditor(this._id, this._config, BX.prop.getArray(data, 'FIELD_NAMES', []));
	      syncEditor.addClosingListener(this.onSynchronizationEditorClose.bind(this));
	      syncEditor.show();
	      return;
	    }
	    this.layout();
	    this.run();
	  }
	  run() {
	    if (this._isRunning) {
	      return;
	    }
	    this._isRunning = true;
	    ProcessRegistry.Instance.registerProcessRun(this._gridId);
	    this._progress.setParams({
	      GRID_ID: this._gridId,
	      CONFIG: this._config
	    });
	    this._progress.run();
	    BX.addCustomEvent(this._progress, 'ON_AUTORUN_PROCESS_STATE_CHANGE', this._progressChangeHandler);
	    BX.bind(window, 'beforeunload', this._documentUnloadHandler);
	  }
	  stop() {
	    if (!this._isRunning) {
	      return;
	    }
	    this._isRunning = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsData)[_sendAnalyticsData]('cancel');
	    main_core.ajax({
	      url: this._serviceUrl,
	      method: 'POST',
	      dataType: 'json',
	      data: {
	        ACTION: 'STOP_BATCH_CONVERSION',
	        PARAMS: {
	          GRID_ID: this._gridId
	        }
	      },
	      onsuccess: this.onStop.bind(this)
	    });
	  }
	  onStop(result) {
	    this.reset();
	    window.setTimeout(() => {
	      BX.onCustomEvent(window, 'BX.Crm.BatchConversionManager:onStop', [this]);
	    }, 300);
	  }
	  reset() {
	    this._progress.reset();
	    BX.removeCustomEvent(this._progress, 'ON_AUTORUN_PROCESS_STATE_CHANGE', this._progressChangeHandler);
	    BX.unbind(window, 'beforeunload', this._documentUnloadHandler);
	    if ((this._succeededItemCount > 0 || this._failedItemCount > 0) && BX.getClass('BX.Main.gridManager')) {
	      BX.Main.gridManager.reload(this._gridId);
	    }
	    this._succeededItemCount = this._failedItemCount = 0;
	    this._isRunning = false;
	    ProcessRegistry.Instance.registerProcessStop(this._gridId);
	    if (this._hasLayout) {
	      window.setTimeout(this.clearLayout.bind(this), 100);
	    }
	    this._errors = [];
	  }
	  getSucceededItemCount() {
	    return this._succeededItemCount;
	  }
	  getFailedItemCount() {
	    return this._failedItemCount;
	  }
	  getErrors() {
	    return this._errors;
	  }

	  /**
	   * Triggers a browser native warning when a user tries to close the tab that data may not be saved yet
	   */
	  onDocumentUnload(event) {
	    // recommended MDN way
	    event.preventDefault();

	    // compatibility with older browsers
	    // eslint-disable-next-line no-param-reassign
	    event.returnValue = true;
	  }
	  onSynchronizationEditorClose(sender, args) {
	    if (BX.prop.getBoolean(args, 'isCanceled', false)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsData)[_sendAnalyticsData]('cancel');
	      this.clearLayout();
	      return;
	    }
	    this._config = sender.getConfig();
	    this.run();
	  }
	  onProgress(sender) {
	    const state = this._progress.getState();
	    if (state === ProcessState.stopped) {
	      this.stop();
	      return;
	    }
	    const errors = this._progress.getErrors();
	    if (errors.length === 0) {
	      this._succeededItemCount++;
	    } else {
	      if (this._errors) {
	        this._errors = [...this._errors, ...errors];
	      } else {
	        this._errors = errors;
	      }
	      this._failedItemCount++;
	      babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsData)[_sendAnalyticsData]('error');
	    }
	    if (state === ProcessState.completed) {
	      babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsData)[_sendAnalyticsData]('success');
	      SummaryPanel.create(this._id, {
	        container: this._containerId,
	        data: {
	          succeededCount: this.getSucceededItemCount(),
	          failedCount: this.getFailedItemCount(),
	          errors: this.getErrors()
	        },
	        messages: BX.prop.getObject(this._settings, 'messages', null),
	        numberSubstitution: '#number_leads#'
	      }).layout();
	      this.reset();
	      window.setTimeout(() => {
	        BX.onCustomEvent(window, 'BX.Crm.BatchConversionManager:onProcessComplete', [this]);
	      }, 300);
	    }
	  }
	}
	function _sendAnalyticsData2(status) {
	  if (!BX.CrmEntityType.isDefined(this._settings.entityTypeId)) {
	    return;
	  }
	  for (const dstEntityTypeName of Object.keys(this._config)) {
	    if (this._config[dstEntityTypeName].active !== 'Y') {
	      continue;
	    }
	    const event = crm_integration_analytics.Builder.Entity.ConvertBatchEvent.createDefault(this._settings.entityTypeId, BX.CrmEntityType.resolveId(dstEntityTypeName));
	    if (main_core.Type.isPlainObject(this._settings.analytics)) {
	      if (main_core.Type.isStringFilled(this._settings.analytics.c_section)) {
	        event.setSection(this._settings.analytics.c_section);
	      }
	      if (main_core.Type.isStringFilled(this._settings.analytics.c_sub_section)) {
	        event.setSubSection(this._settings.analytics.c_sub_section);
	      }
	      if (main_core.Type.isStringFilled(this._settings.analytics.c_element)) {
	        event.setElement(this._settings.analytics.c_element);
	      }
	    }
	    event.setStatus(status);
	    ui_analytics.sendData(event.buildData());
	  }
	}
	BatchConversionManager.messages = {};
	BatchConversionManager.items = {};

	/* eslint-disable no-underscore-dangle */

	/**
	 * @memberOf BX.Crm.Autorun
	 * @alias BX.Crm.BatchDeletionManager
	 */
	class BatchDeletionManager extends BatchManager {
	  static getItem(id) {
	    return BX.prop.get(BatchDeletionManager.items, id, null);
	  }
	  static create(id, settings) {
	    const self = new BatchDeletionManager(id, settings);
	    BatchDeletionManager.items[self.getId()] = self;
	    return self;
	  }
	  getDefaultMessages() {
	    const messages = super.getDefaultMessages();
	    const entityTypeName = BX.CrmEntityType.resolveName(this._entityTypeId);

	    /**
	     * CRM_AUTORUN_BATCH_DELETE_TITLE_LEAD
	     * CRM_AUTORUN_BATCH_DELETE_TITLE_DEAL
	     * CRM_AUTORUN_BATCH_DELETE_TITLE_CONTACT
	     * CRM_AUTORUN_BATCH_DELETE_TITLE_COMPANY
	     *
	     * CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_TITLE_LEAD
	     * CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_TITLE_DEAL
	     * CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_TITLE_CONTACT
	     * CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_TITLE_COMPANY
	     */

	    const specificTitle = main_core.Loc.getMessage(`CRM_AUTORUN_BATCH_DELETE_TITLE_${entityTypeName}`);
	    if (main_core.Type.isStringFilled(specificTitle)) {
	      messages.title = specificTitle;
	    }
	    const specificConfirmationTitle = main_core.Loc.getMessage(`CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_TITLE_${entityTypeName}`);
	    if (main_core.Type.isStringFilled(specificConfirmationTitle)) {
	      messages.confirmationTitle = specificConfirmationTitle;
	    }
	    return messages;
	  }
	  getIdPrefix() {
	    return 'crm_batch_deletion_mgr';
	  }
	  getEventNamespacePostfix() {
	    return 'BatchDeletionManager';
	  }
	  getPrepareActionName() {
	    return 'crm.api.autorun.delete.prepare';
	  }
	  getProcessActionName() {
	    return 'crm.api.autorun.delete.process';
	  }
	  getCancelActionName() {
	    return 'crm.api.autorun.delete.cancel';
	  }
	  execute() {
	    ui_dialogs_messagebox.MessageBox.confirm(this.getMessage('confirmation'), this.getMessage('confirmationTitle', '') || this.getMessage('title'), () => {
	      super.execute();

	      // to close messagebox
	      return true;
	    }, this.getMessage('confirmationYesCaption'));
	  }
	}
	BatchDeletionManager.messages = {
	  // default messages, you can override them via settings.messages
	  title: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_TITLE'),
	  // default message for all entity types
	  confirmation: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_CONFIRMATION'),
	  confirmationTitle: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_TITLE'),
	  // default message for all entity types
	  confirmationYesCaption: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_YES_CAPTION'),
	  summaryCaption: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_SUMMARY_CAPTION'),
	  summarySucceeded: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_SUMMARY_SUCCEEDED'),
	  summaryFailed: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_SUMMARY_FAILED')
	};
	BatchDeletionManager.items = {};

	/* eslint-disable no-underscore-dangle */

	/**
	 * @memberOf BX.Crm.Autorun
	 */
	class BatchExclusionManager extends BatchManager {
	  static getItem(id) {
	    return BX.prop.get(BatchExclusionManager.items, id, null);
	  }
	  static create(id, settings) {
	    const self = new BatchExclusionManager(id, settings);
	    BatchExclusionManager.items[self.getId()] = self;
	    return self;
	  }
	  getIdPrefix() {
	    return 'crm_batch_exclusion_mgr';
	  }
	  getEventNamespacePostfix() {
	    return 'BatchExclusionManager';
	  }
	  getPrepareActionName() {
	    return 'crm.api.autorun.exclusion.prepare';
	  }
	  getProcessActionName() {
	    return 'crm.api.autorun.exclusion.process';
	  }
	  getCancelActionName() {
	    return 'crm.api.autorun.exclusion.cancel';
	  }
	}
	BatchExclusionManager.messages = {
	  // default messages, you can override them via settings.messages
	  title: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_EXCLUSION_TITLE'),
	  summaryCaption: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_EXCLUSION_SUMMARY_CAPTION'),
	  summarySucceeded: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_EXCLUSION_SUMMARY_SUCCEEDED'),
	  summaryFailed: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_EXCLUSION_SUMMARY_FAILED')
	};
	BatchExclusionManager.items = {};

	var _observerIdList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("observerIdList");
	class BatchObserversManager extends BatchManager {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _observerIdList, {
	      writable: true,
	      value: []
	    });
	  }
	  static getItem(id) {
	    var _BatchObserversManage;
	    return (_BatchObserversManage = BatchObserversManager.items[id]) != null ? _BatchObserversManage : null;
	  }
	  static create(id, settings) {
	    const self = new BatchObserversManager(id, settings);
	    BatchObserversManager.items[self.getId()] = self;
	    return self;
	  }
	  getCancelActionName() {
	    return 'crm.api.autorun.observers.cancel';
	  }
	  getEventNamespacePostfix() {
	    return 'BatchObserversManager';
	  }
	  getIdPrefix() {
	    return 'crm_batch_observers_mgr';
	  }
	  getPrepareActionName() {
	    return 'crm.api.autorun.observers.prepare';
	  }
	  getPrepareActionParams() {
	    const params = super.getPrepareActionParams();
	    params.observerIdList = babelHelpers.classPrivateFieldLooseBase(this, _observerIdList)[_observerIdList];
	    return params;
	  }
	  getProcessActionName() {
	    return 'crm.api.autorun.observers.process';
	  }
	  setObserverIdList(userIdList) {
	    babelHelpers.classPrivateFieldLooseBase(this, _observerIdList)[_observerIdList] = userIdList;
	  }
	}
	BatchObserversManager.messages = {
	  title: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_OBSERVERS_TITLE'),
	  summaryCaption: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_OBSERVERS_SUMMARY_CAPTION'),
	  summarySucceeded: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_OBSERVERS_SUMMARY_SUCCEEDED'),
	  summaryFailed: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_OBSERVERS_SUMMARY_FAILED')
	};
	BatchObserversManager.items = {};

	/**
	 * @memberOf BX.Crm.Autorun
	 */
	class BatchRefreshAccountingDataManager extends BatchManager {
	  static getItem(id) {
	    return BX.prop.get(BatchRefreshAccountingDataManager.items, id, null);
	  }
	  static create(id, settings) {
	    const self = new BatchRefreshAccountingDataManager(id, settings);
	    BatchRefreshAccountingDataManager.items[self.getId()] = self;
	    return self;
	  }
	  getIdPrefix() {
	    return 'crm_batch_refresh_accounting_data_mgr';
	  }
	  getEventNamespacePostfix() {
	    return 'BatchRefreshAccountingDataManager';
	  }
	  getPrepareActionName() {
	    return 'crm.api.autorun.refreshaccountingdata.prepare';
	  }
	  getProcessActionName() {
	    return 'crm.api.autorun.refreshaccountingdata.process';
	  }
	  getCancelActionName() {
	    return 'crm.api.autorun.refreshaccountingdata.cancel';
	  }
	}
	BatchRefreshAccountingDataManager.messages = {
	  // default messages, you can override them via settings.messages
	  title: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_REFRESH_ACCOUNTING_DATA_TITLE'),
	  summaryCaption: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_REFRESH_ACCOUNTING_DATA_SUMMARY_CAPTION'),
	  summarySucceeded: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_REFRESH_ACCOUNTING_DATA_SUMMARY_SUCCEEDED'),
	  summaryFailed: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_REFRESH_ACCOUNTING_DATA_SUMMARY_FAILED')
	};
	BatchRefreshAccountingDataManager.items = {};

	/* eslint-disable no-underscore-dangle */

	/**
	 * @memberOf BX.Crm.Autorun
	 */
	class BatchRestartAutomationManager extends BatchManager {
	  static getItem(id) {
	    return BX.prop.get(BatchRestartAutomationManager.items, id, null);
	  }
	  static create(id, settings) {
	    const self = new BatchRestartAutomationManager(id, settings);
	    BatchRestartAutomationManager.items[self.getId()] = self;
	    return self;
	  }
	  getIdPrefix() {
	    return 'crm_batch_restart_automation_mgr';
	  }
	  getEventNamespacePostfix() {
	    return 'BatchRestartAutomationManager';
	  }
	  getPrepareActionName() {
	    return 'crm.api.autorun.restartAutomation.prepare';
	  }
	  getProcessActionName() {
	    return 'crm.api.autorun.restartAutomation.process';
	  }
	  getCancelActionName() {
	    return 'crm.api.autorun.restartAutomation.cancel';
	  }
	}
	BatchRestartAutomationManager.messages = {
	  // default messages, you can override them via settings.messages
	  title: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_RESTART_AUTOMATION_TITLE'),
	  summaryCaption: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_RESTART_AUTOMATION_SUMMARY_CAPTION'),
	  summarySucceeded: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_RESTART_AUTOMATION_SUMMARY_SUCCEEDED'),
	  summaryFailed: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_RESTART_AUTOMATION_SUMMARY_FAILED')
	};
	BatchRestartAutomationManager.items = {};

	/* eslint-disable no-underscore-dangle */

	/**
	 * @memberOf BX.Crm.Autorun
	 */
	var _categoryId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("categoryId");
	class BatchSetCategoryManager extends BatchManager {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _categoryId, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static getItem(id) {
	    return BX.prop.get(BatchSetCategoryManager.items, id, null);
	  }
	  static create(id, settings) {
	    const self = new BatchSetCategoryManager(id, settings);
	    BatchSetCategoryManager.items[self.getId()] = self;
	    return self;
	  }
	  getIdPrefix() {
	    return 'crm_batch_set_category_mgr';
	  }
	  getEventNamespacePostfix() {
	    return 'BatchSetCategoryManager';
	  }
	  getPrepareActionName() {
	    return 'crm.api.autorun.setcategory.prepare';
	  }
	  getPrepareActionParams() {
	    const params = super.getPrepareActionParams();
	    params.categoryId = babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId];
	    return params;
	  }
	  getProcessActionName() {
	    return 'crm.api.autorun.setcategory.process';
	  }
	  getCancelActionName() {
	    return 'crm.api.autorun.setcategory.cancel';
	  }
	  setCategoryId(categoryId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId] = main_core.Text.toInteger(categoryId);
	  }
	}
	BatchSetCategoryManager.messages = {
	  // default messages, you can override them via settings.messages
	  title: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_CATEGORY_TITLE'),
	  summaryCaption: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_CATEGORY_SUMMARY_CAPTION'),
	  summarySucceeded: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_CATEGORY_SUMMARY_SUCCEEDED'),
	  summaryFailed: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_CATEGORY_SUMMARY_FAILED')
	};
	BatchSetCategoryManager.items = {};

	/* eslint-disable no-underscore-dangle */

	/**
	 * @memberOf BX.Crm.Autorun
	 */
	var _export = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("export");
	class BatchSetExportManager extends BatchManager {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _export, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static getItem(id) {
	    return BX.prop.get(BatchSetExportManager.items, id, null);
	  }
	  static create(id, settings) {
	    const self = new BatchSetExportManager(id, settings);
	    BatchSetExportManager.items[self.getId()] = self;
	    return self;
	  }
	  getDefaultMessages() {
	    const messages = super.getDefaultMessages();
	    const entityTypeName = BX.CrmEntityType.resolveName(this._entityTypeId);

	    /**
	     * CRM_AUTORUN_BATCH_SET_EXPORT_TITLE_CONTACT
	     */
	    const specificTitle = main_core.Loc.getMessage(`CRM_AUTORUN_BATCH_SET_EXPORT_TITLE_${entityTypeName}`);
	    if (main_core.Type.isStringFilled(specificTitle)) {
	      messages.title = specificTitle;
	    }
	    return messages;
	  }
	  getIdPrefix() {
	    return 'crm_batch_set_export_mgr';
	  }
	  getEventNamespacePostfix() {
	    return 'BatchSetExportManager';
	  }
	  getPrepareActionName() {
	    return 'crm.api.autorun.setexport.prepare';
	  }
	  getPrepareActionParams() {
	    const params = super.getPrepareActionParams();
	    params.export = babelHelpers.classPrivateFieldLooseBase(this, _export)[_export] ? 'Y' : 'N';
	    return params;
	  }
	  getProcessActionName() {
	    return 'crm.api.autorun.setexport.process';
	  }
	  getCancelActionName() {
	    return 'crm.api.autorun.setexport.cancel';
	  }
	  setExport(isExport) {
	    if (main_core.Type.isString(isExport)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _export)[_export] = isExport === 'Y';
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _export)[_export] = Boolean(isExport);
	    }
	  }
	}
	BatchSetExportManager.messages = {
	  // default messages, you can override them via settings.messages
	  title: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_EXPORT_TITLE'),
	  // default message for all entity types
	  summaryCaption: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_EXPORT_SUMMARY_CAPTION'),
	  summarySucceeded: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_EXPORT_SUMMARY_SUCCEEDED'),
	  summaryFailed: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_EXPORT_SUMMARY_FAILED')
	};
	BatchSetExportManager.items = {};

	/* eslint-disable no-underscore-dangle */

	/**
	 * @memberOf BX.Crm.Autorun
	 */
	var _isOpened = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isOpened");
	class BatchSetOpenedManager extends BatchManager {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _isOpened, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static getItem(id) {
	    return BX.prop.get(BatchSetOpenedManager.items, id, null);
	  }
	  static create(id, settings) {
	    const self = new BatchSetOpenedManager(id, settings);
	    BatchSetOpenedManager.items[self.getId()] = self;
	    return self;
	  }
	  getIdPrefix() {
	    return 'crm_batch_set_stage_mgr';
	  }
	  getEventNamespacePostfix() {
	    return 'BatchSetOpenedManager';
	  }
	  getPrepareActionName() {
	    return 'crm.api.autorun.setopened.prepare';
	  }
	  getPrepareActionParams() {
	    const params = super.getPrepareActionParams();
	    params.isOpened = babelHelpers.classPrivateFieldLooseBase(this, _isOpened)[_isOpened] ? 'Y' : 'N';
	    return params;
	  }
	  getProcessActionName() {
	    return 'crm.api.autorun.setopened.process';
	  }
	  getCancelActionName() {
	    return 'crm.api.autorun.setopened.cancel';
	  }
	  setIsOpened(isOpened) {
	    if (main_core.Type.isString(isOpened)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _isOpened)[_isOpened] = isOpened === 'Y';
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _isOpened)[_isOpened] = Boolean(isOpened);
	    }
	  }
	}
	BatchSetOpenedManager.messages = {
	  // default messages, you can override them via settings.messages
	  title: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_OPENED_TITLE'),
	  summaryCaption: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_OPENED_SUMMARY_CAPTION'),
	  summarySucceeded: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_OPENED_SUMMARY_SUCCEEDED'),
	  summaryFailed: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_OPENED_SUMMARY_FAILED')
	};
	BatchSetOpenedManager.items = {};

	/* eslint-disable no-underscore-dangle */

	/**
	 * @memberOf BX.Crm.Autorun
	 */
	var _stageId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("stageId");
	class BatchSetStageManager extends BatchManager {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _stageId, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static getItem(id) {
	    return BX.prop.get(BatchSetStageManager.items, id, null);
	  }
	  static create(id, settings) {
	    const self = new BatchSetStageManager(id, settings);
	    BatchSetStageManager.items[self.getId()] = self;
	    return self;
	  }
	  getIdPrefix() {
	    return 'crm_batch_set_stage_mgr';
	  }
	  getEventNamespacePostfix() {
	    return 'BatchSetStageManager';
	  }
	  getPrepareActionName() {
	    return 'crm.api.autorun.setstage.prepare';
	  }
	  getPrepareActionParams() {
	    const params = super.getPrepareActionParams();
	    params.stageId = babelHelpers.classPrivateFieldLooseBase(this, _stageId)[_stageId];
	    return params;
	  }
	  getProcessActionName() {
	    return 'crm.api.autorun.setstage.process';
	  }
	  getCancelActionName() {
	    return 'crm.api.autorun.setstage.cancel';
	  }
	  setStageId(stageId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _stageId)[_stageId] = String(stageId);
	  }
	}
	BatchSetStageManager.messages = {
	  // default messages, you can override them via settings.messages
	  title: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_STAGE_TITLE'),
	  summaryCaption: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_STAGE_SUMMARY_CAPTION'),
	  summarySucceeded: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_STAGE_SUMMARY_SUCCEEDED'),
	  summaryFailed: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_SET_STAGE_SUMMARY_FAILED')
	};
	BatchSetStageManager.items = {};

	/* eslint-disable no-underscore-dangle */
	var _templateParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("templateParams");
	var _instances = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instances");
	/**
	 * @memberOf BX.Crm.Autorun
	 */
	class BatchWhatsappMessageManager extends BatchManager {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _templateParams, {
	      writable: true,
	      value: null
	    });
	  }
	  setTemplateParams(templateParams) {
	    babelHelpers.classPrivateFieldLooseBase(this, _templateParams)[_templateParams] = templateParams;
	  }
	  static getInstance(id, settings) {
	    if (babelHelpers.classPrivateFieldLooseBase(BatchWhatsappMessageManager, _instances)[_instances].has(id)) {
	      return babelHelpers.classPrivateFieldLooseBase(BatchWhatsappMessageManager, _instances)[_instances].get(id);
	    }
	    const instance = new BatchWhatsappMessageManager(id, settings);
	    babelHelpers.classPrivateFieldLooseBase(BatchWhatsappMessageManager, _instances)[_instances].set(instance.getId(), instance);
	    return instance;
	  }
	  getPrepareActionParams() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _templateParams)[_templateParams]) {
	      throw new Error('templateParams is required');
	    }
	    const params = super.getPrepareActionParams();
	    return {
	      ...params,
	      extras: {
	        messageBody: babelHelpers.classPrivateFieldLooseBase(this, _templateParams)[_templateParams].messageBody,
	        messageTemplate: babelHelpers.classPrivateFieldLooseBase(this, _templateParams)[_templateParams].messageTemplate,
	        fromPhone: babelHelpers.classPrivateFieldLooseBase(this, _templateParams)[_templateParams].fromPhone
	      }
	    };
	  }
	  getIdPrefix() {
	    return 'crm_batch_whatsappmessage_mgr';
	  }
	  getEventNamespacePostfix() {
	    return 'BatchWhatsAppMessageManager';
	  }
	  getPrepareActionName() {
	    return 'crm.api.autorun.whatsappmessage.prepare';
	  }
	  getProcessActionName() {
	    return 'crm.api.autorun.whatsappmessage.process';
	  }
	  getCancelActionName() {
	    return 'crm.api.autorun.whatsappmessage.cancel';
	  }
	}
	BatchWhatsappMessageManager.messages = {
	  // default messages, you can override them via settings.messages
	  title: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_WHATSAPP_MESSAGE_TITLE'),
	  summaryCaption: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_WHATSAPP_MESSAGE_SUMMARY_CAPTION'),
	  summarySucceeded: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_WHATSAPP_MESSAGE_SUMMARY_SUCCEEDED'),
	  summaryFailed: main_core.Loc.getMessage('CRM_AUTORUN_BATCH_WHATSAPP_MESSAGE_SUMMARY_FAILED')
	};
	Object.defineProperty(BatchWhatsappMessageManager, _instances, {
	  writable: true,
	  value: new Map()
	});

	let _ = t => t,
	  _t;

	/**
	 * @memberOf BX.Crm.Autorun
	 */
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _storage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("storage");
	class ProgressBarRepository {
	  constructor(container) {
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _storage, {
	      writable: true,
	      value: new Map()
	    });
	    if (!main_core.Type.isElementNode(container)) {
	      throw new TypeError('expected element node');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = container;
	  }
	  getOrCreateProgressBarContainer(id) {
	    const fullId = ProgressBarRepository.getFullId(id);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage].has(fullId)) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage].get(fullId);
	    }
	    let progressBarContainer = babelHelpers.classPrivateFieldLooseBase(this, _container)[_container].querySelector(`div#${fullId}`);
	    if (!progressBarContainer) {
	      progressBarContainer = main_core.Tag.render(_t || (_t = _`<div id="${0}"></div>`), fullId);
	      main_core.Dom.append(progressBarContainer, babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage].set(fullId, progressBarContainer);
	    return progressBarContainer;
	  }
	  static getFullId(id) {
	    return `crm-autorun-progress-bar-${id}`;
	  }
	}

	// region Compatibility
	const bxNamespace = main_core.Reflection.namespace('BX');
	bxNamespace.AutorunProcessManager = Processor;
	bxNamespace.AutorunProcessPanel = ProcessPanel;
	bxNamespace.AutoRunProcessState = ProcessState;
	const bxCrmNamespace = main_core.Reflection.namespace('BX.Crm');
	bxCrmNamespace.ProcessSummaryPanel = SummaryPanel;
	bxCrmNamespace.BatchDeletionManager = BatchDeletionManager;
	bxCrmNamespace.BatchConversionManager = BatchConversionManager;

	exports.ProcessRegistry = ProcessRegistry;
	exports.Processor = Processor;
	exports.ProcessPanel = ProcessPanel;
	exports.ProcessState = ProcessState;
	exports.SummaryPanel = SummaryPanel;
	exports.ProgressBarRepository = ProgressBarRepository;
	exports.BatchAssignmentManager = BatchAssignmentManager;
	exports.BatchDeletionManager = BatchDeletionManager;
	exports.BatchConversionManager = BatchConversionManager;
	exports.BatchSetCategoryManager = BatchSetCategoryManager;
	exports.BatchSetStageManager = BatchSetStageManager;
	exports.BatchSetOpenedManager = BatchSetOpenedManager;
	exports.BatchSetExportManager = BatchSetExportManager;
	exports.BatchExclusionManager = BatchExclusionManager;
	exports.BatchWhatsappMessageManager = BatchWhatsappMessageManager;
	exports.BatchRefreshAccountingDataManager = BatchRefreshAccountingDataManager;
	exports.BatchRestartAutomationManager = BatchRestartAutomationManager;
	exports.BatchObserversManager = BatchObserversManager;

}((this.BX.Crm.Autorun = this.BX.Crm.Autorun || {}),BX,BX.Crm.Integration.Analytics,BX.UI.Analytics,BX.UI.Dialogs,BX));
//# sourceMappingURL=autorun.bundle.js.map
