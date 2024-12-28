/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_core_events,ui_stepprocessing) {
	'use strict';

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _action = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("action");
	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	var _title = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("title");
	var _forAll = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("forAll");
	var _step = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("step");
	var _requestStopFunction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestStopFunction");
	var _process = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("process");
	var _getProcess = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProcess");
	var _getTotalElements = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTotalElements");
	var _allSteps = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("allSteps");
	var _getTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTitle");
	class GroupActionsStepper extends main_core_events.EventEmitter {
	  // eslint-disable-next-line no-unused-private-class-members

	  constructor(params) {
	    var _params$step;
	    super();
	    Object.defineProperty(this, _getTitle, {
	      value: _getTitle2
	    });
	    Object.defineProperty(this, _allSteps, {
	      value: _allSteps2
	    });
	    Object.defineProperty(this, _getTotalElements, {
	      value: _getTotalElements2
	    });
	    Object.defineProperty(this, _getProcess, {
	      value: _getProcess2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _action, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _data, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _title, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _forAll, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _step, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _requestStopFunction, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _process, {
	      writable: true,
	      value: null
	    });
	    this.setEventNamespace('BX.Tasks.GroupActionsStepper');
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _action)[_action] = params.action;
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data] = params.data;
	    babelHelpers.classPrivateFieldLooseBase(this, _step)[_step] = (_params$step = params.step) != null ? _params$step : 20;
	    babelHelpers.classPrivateFieldLooseBase(this, _requestStopFunction)[_requestStopFunction] = params.requestStopFunction;
	    babelHelpers.classPrivateFieldLooseBase(this, _forAll)[_forAll] = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].forAll === 'Y';
	    babelHelpers.classPrivateFieldLooseBase(this, _title)[_title] = babelHelpers.classPrivateFieldLooseBase(this, _getTitle)[_getTitle](babelHelpers.classPrivateFieldLooseBase(this, _action)[_action]);
	    babelHelpers.classPrivateFieldLooseBase(this, _process)[_process] = babelHelpers.classPrivateFieldLooseBase(this, _getProcess)[_getProcess]();
	  }
	  showDialog() {
	    babelHelpers.classPrivateFieldLooseBase(this, _process)[_process].showDialog();
	    return babelHelpers.classPrivateFieldLooseBase(this, _process)[_process];
	  }
	  closeDialog() {
	    babelHelpers.classPrivateFieldLooseBase(this, _process)[_process].closeDialog();
	    return this;
	  }
	}
	function _getProcess2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _process)[_process]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _process)[_process] = new ui_stepprocessing.Process({
	      id: 'TaskListGroupActionsStepper',
	      controller: GroupActionsStepper.ACTION_CONTROLLER,
	      messages: {
	        DialogTitle: main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME'),
	        DialogSummary: main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_DESCRIPTION'),
	        RequestCanceling: main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_CANCELING')
	      },
	      showButtons: {
	        start: true,
	        stop: true,
	        close: true
	      },
	      dialogMaxWidth: 600,
	      popupOptions: {
	        resizable: false,
	        draggable: false,
	        disableScroll: true
	      }
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(this, _forAll)[_forAll]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _getTotalElements)[_getTotalElements]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _allSteps)[_allSteps]();
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _process)[_process];
	}
	function _getTotalElements2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].nPageSize = babelHelpers.classPrivateFieldLooseBase(this, _step)[_step];
	  delete babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].selectedIds;
	  delete babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].forAll;

	  // add spetial step for determine total sessions
	  babelHelpers.classPrivateFieldLooseBase(this, _process)[_process].addQueueAction({
	    title: main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_COUNTING_ELEMENTS_PROGRESS'),
	    action: GroupActionsStepper.ACTION_CONTROLLER_COUNT_TASKS,
	    handlers: {
	      StepCompleted(state, result) {
	        if (state === ui_stepprocessing.ProcessResultStatus.completed) {
	          const data = this.getParam('data') || [];
	          // add total count in request
	          if (result.TOTAL_ITEMS) {
	            data.totalItems = parseInt(result.TOTAL_ITEMS, 10);
	          }
	          this.setParam('data', data);
	        }
	      }
	    }
	  });
	}
	function _allSteps2() {
	  const requestStopFunction = babelHelpers.classPrivateFieldLooseBase(this, _requestStopFunction)[_requestStopFunction];
	  // on finish
	  babelHelpers.classPrivateFieldLooseBase(this, _process)[_process].setHandler(ui_stepprocessing.ProcessCallback.StateChanged, function (state) {
	    if (state === ui_stepprocessing.ProcessResultStatus.completed) {
	      requestStopFunction();
	      // eslint-disable-next-line no-invalid-this
	      this.closeDialog();
	    }
	  })
	  // on cancel
	  .setHandler(ui_stepprocessing.ProcessCallback.RequestStop, function (actionData) {
	    setTimeout(
	    // eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
	    BX.delegate(function () {
	      requestStopFunction();
	      // eslint-disable-next-line no-invalid-this
	      this.closeDialog();
	    },
	    // eslint-disable-next-line no-invalid-this
	    this), 2000);
	  })
	  // payload action step
	  .addQueueAction({
	    title: babelHelpers.classPrivateFieldLooseBase(this, _title)[_title],
	    action: babelHelpers.classPrivateFieldLooseBase(this, _action)[_action],
	    handlers: {
	      // keep total and processed in request
	      StepCompleted(state, result) {
	        if (state === ui_stepprocessing.ProcessResultStatus.progress) {
	          const data = this.getParam('data') || [];
	          if (result.PROCESSED_ITEMS) {
	            data.processedItems = parseInt(result.PROCESSED_ITEMS, 10);
	          }
	          this.setParam('data', data);
	        }
	        if (state === ui_stepprocessing.ProcessState.error) {
	          requestStopFunction();
	          this.setMessage('RequestError', result.ERRORS);
	          this.getDialog().setWarning(result.WARNING_TEXT, true);
	        }
	      }
	    }
	  })
	  // params
	  .setParam('data', babelHelpers.classPrivateFieldLooseBase(this, _data)[_data]);
	}
	function _getTitle2(action) {
	  let title = '';
	  switch (action) {
	    case 'unmute':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_UNMUTE');
	      break;
	    case 'mute':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_MUTE');
	      break;
	    case 'ping':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_PING');
	      break;
	    case 'complete':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_COMPLETE');
	      break;
	    case 'setdeadline':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETDEADLINE');
	      break;
	    case 'adjustdeadline':
	    case 'substractdeadline':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_ADJUSTDEADLINE');
	      break;
	    case 'settaskcontrol':
	      if (babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].taskControlState === 'Y') {
	        title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETTASKCONTROL_YES');
	      } else {
	        title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETTASKCONTROL');
	      }
	      break;
	    case 'setresponsible':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETRESPONSIBLE');
	      break;
	    case 'setoriginator':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETORIGINATOR');
	      break;
	    case 'addauditor':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_ADDAUDITOR');
	      break;
	    case 'addaccomplice':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_ADDACCOMPLICE');
	      break;
	    case 'addtofavorite':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_ADDTOFAVORITE');
	      break;
	    case 'removefromfavorite':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_REMOVEFROMFAVORITE');
	      break;
	    case 'setgroup':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETGROUP');
	      break;
	    case 'setflow':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETFLOW');
	      break;
	    case 'delete':
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_DELETE');
	      break;
	    default:
	      title = main_core.Loc.getMessage('TASKS_GRID_GROUP_ACTION_NONE');
	  }
	  return title;
	}
	GroupActionsStepper.ACTION_CONTROLLER = 'tasks.task.action.group';
	GroupActionsStepper.ACTION_CONTROLLER_COUNT_TASKS = 'getTotalCountTasks';

	exports.GroupActionsStepper = GroupActionsStepper;

}((this.BX.Tasks = this.BX.Tasks || {}),BX,BX.Event,BX.UI.StepProcessing));
//# sourceMappingURL=group-actions-stepper.bundle.js.map
