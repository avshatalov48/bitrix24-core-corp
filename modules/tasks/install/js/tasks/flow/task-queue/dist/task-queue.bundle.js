/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_loader,main_popup,tasks_sidePanelIntegration,main_core) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	var _id = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _name = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("name");
	var _avatar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("avatar");
	var _workPosition = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("workPosition");
	var _pathToProfile = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pathToProfile");
	var _renderAvatar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderAvatar");
	class Member {
	  constructor(data) {
	    Object.defineProperty(this, _renderAvatar, {
	      value: _renderAvatar2
	    });
	    Object.defineProperty(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _name, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _avatar, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _workPosition, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _pathToProfile, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] = parseInt(data.id, 10);
	    babelHelpers.classPrivateFieldLooseBase(this, _name)[_name] = data.name;
	    babelHelpers.classPrivateFieldLooseBase(this, _avatar)[_avatar] = data.avatar;
	    babelHelpers.classPrivateFieldLooseBase(this, _workPosition)[_workPosition] = data.workPosition;
	    babelHelpers.classPrivateFieldLooseBase(this, _pathToProfile)[_pathToProfile] = data.pathToProfile;
	  }
	  render() {
	    return main_core.Tag.render(_t || (_t = _`
			<div class="tasks-flow__task-queue-member" data-id="tasks-flow-task-queue-member-${0}">
				${0}
			</div>
		`), this.getId(), babelHelpers.classPrivateFieldLooseBase(this, _renderAvatar)[_renderAvatar]());
	  }
	  getId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _id)[_id];
	  }
	  getName() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _name)[_name];
	  }
	  getAvatar() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _avatar)[_avatar];
	  }
	  getPathToProfile() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _pathToProfile)[_pathToProfile];
	  }
	}
	function _renderAvatar2() {
	  let photoIcon = '<i></i>';
	  if (this.getAvatar()) {
	    photoIcon = `<i style="background-image: url('${encodeURI(this.getAvatar())}')"></i>`;
	  }
	  return main_core.Tag.render(_t2 || (_t2 = _`
			<a 
				href="${0}"
				class="tasks-flow__task-queue-member_avatar ui-icon ui-icon-common-user"
			>
				${0}
			</a>
		`), main_core.Text.encode(this.getPathToProfile()), photoIcon);
	}

	let _$1 = t => t,
	  _t$1;
	var _serial = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("serial");
	var _createdBy = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createdBy");
	var _creator = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("creator");
	var _responsibleId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("responsibleId");
	var _responsible = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("responsible");
	var _timeInStatus = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("timeInStatus");
	class Line {
	  constructor(lineData) {
	    Object.defineProperty(this, _serial, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _createdBy, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _creator, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _responsibleId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _responsible, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _timeInStatus, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _serial)[_serial] = parseInt(lineData.serial, 10);
	    babelHelpers.classPrivateFieldLooseBase(this, _createdBy)[_createdBy] = parseInt(lineData.createdBy, 10);
	    babelHelpers.classPrivateFieldLooseBase(this, _creator)[_creator] = new Member(lineData.creator);
	    babelHelpers.classPrivateFieldLooseBase(this, _responsibleId)[_responsibleId] = parseInt(lineData.responsibleId, 10);
	    babelHelpers.classPrivateFieldLooseBase(this, _responsible)[_responsible] = new Member(lineData.responsible);
	    babelHelpers.classPrivateFieldLooseBase(this, _timeInStatus)[_timeInStatus] = lineData.timeInStatus.formatted;
	  }
	  render() {
	    return main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="tasks-flow__task-queue-line">
				<div class="tasks-flow__task-queue-line_number">${0}</div>
				<div class="tasks-flow__task-queue-line_avatar">${0}</div>
				<div class="ui-icon-set --chevron-right" style="--ui-icon-set__icon-size: 18px;"></div>
				<div class="tasks-flow__task-queue-line_avatar">${0}</div>
				<div class="tasks-flow__task-queue-line_time" title="${0}">${0}</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _serial)[_serial], babelHelpers.classPrivateFieldLooseBase(this, _creator)[_creator].render(), babelHelpers.classPrivateFieldLooseBase(this, _responsible)[_responsible].render(), babelHelpers.classPrivateFieldLooseBase(this, _timeInStatus)[_timeInStatus], babelHelpers.classPrivateFieldLooseBase(this, _timeInStatus)[_timeInStatus]);
	  }
	}

	let _$2 = t => t,
	  _t$2,
	  _t2$1,
	  _t3,
	  _t4;
	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _flowId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("flowId");
	var _type = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	var _pageSize = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pageSize");
	var _pageNum = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pageNum");
	var _pending = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pending");
	var _pages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pages");
	var _totalTaskCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("totalTaskCount");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _layout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _getList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getList");
	var _renderContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderContent");
	var _renderCounterContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderCounterContainer");
	var _renderTotalTaskCounter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderTotalTaskCounter");
	var _renderLines = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderLines");
	var _showLines = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLines");
	var _appendLines = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appendLines");
	var _setTotalTaskCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setTotalTaskCount");
	var _showLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLoader");
	var _destroyLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("destroyLoader");
	var _consoleError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("consoleError");
	class TaskQueue {
	  constructor(params) {
	    Object.defineProperty(this, _consoleError, {
	      value: _consoleError2
	    });
	    Object.defineProperty(this, _destroyLoader, {
	      value: _destroyLoader2
	    });
	    Object.defineProperty(this, _showLoader, {
	      value: _showLoader2
	    });
	    Object.defineProperty(this, _setTotalTaskCount, {
	      value: _setTotalTaskCount2
	    });
	    Object.defineProperty(this, _appendLines, {
	      value: _appendLines2
	    });
	    Object.defineProperty(this, _showLines, {
	      value: _showLines2
	    });
	    Object.defineProperty(this, _renderLines, {
	      value: _renderLines2
	    });
	    Object.defineProperty(this, _renderTotalTaskCounter, {
	      value: _renderTotalTaskCounter2
	    });
	    Object.defineProperty(this, _renderCounterContainer, {
	      value: _renderCounterContainer2
	    });
	    Object.defineProperty(this, _renderContent, {
	      value: _renderContent2
	    });
	    Object.defineProperty(this, _getList, {
	      value: _getList2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _flowId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _type, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _pageSize, {
	      writable: true,
	      value: 10
	    });
	    Object.defineProperty(this, _pageNum, {
	      writable: true,
	      value: 1
	    });
	    Object.defineProperty(this, _pending, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _pages, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _totalTaskCount, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loader, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _flowId)[_flowId] = parseInt(params.flowId, 10);
	    if (!(params.type in TaskQueue.TYPES)) {
	      throw new Error('The specified queue type is incorrect');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] = params.type;
	    babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout] = {};
	  }
	  static showInstance(params) {
	    new this(params).show(params.bindElement);
	  }
	  show(bindElement) {
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = this.getPopup();
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setContent(babelHelpers.classPrivateFieldLooseBase(this, _renderContent)[_renderContent]());
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setBindElement(bindElement);
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  }
	  getPopup() {
	    const queueId = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].flowId + babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].type;
	    const id = `tasks-flow-task-queue-popup-${queueId}`;
	    if (main_popup.PopupManager.getPopupById(id)) {
	      return main_popup.PopupManager.getPopupById(id);
	    }
	    const popup = new main_popup.Popup({
	      id,
	      className: 'tasks-flow__task-queue-popup',
	      padding: 2,
	      autoHide: true,
	      closeByEsc: true,
	      events: {
	        onFirstShow: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _showLines)[_showLines]();
	        },
	        onClose: () => {
	          popup.destroy();
	        }
	      }
	    });
	    new tasks_sidePanelIntegration.SidePanelIntegration(popup);
	    return popup;
	  }
	}
	function _getList2(pageNum) {
	  babelHelpers.classPrivateFieldLooseBase(this, _pending)[_pending] = true;
	  const map = {
	    PENDING: 'Pending',
	    AT_WORK: 'Progress',
	    COMPLETED: 'Completed'
	  };
	  return new Promise(resolve => {
	    main_core.ajax.runAction(`tasks.flow.Task.${map[babelHelpers.classPrivateFieldLooseBase(this, _type)[_type]]}.list`, {
	      data: {
	        flowData: {
	          id: babelHelpers.classPrivateFieldLooseBase(this, _flowId)[_flowId]
	        },
	        ago: {
	          days: 30
	        }
	      },
	      navigation: {
	        page: pageNum,
	        size: babelHelpers.classPrivateFieldLooseBase(this, _pageSize)[_pageSize]
	      }
	    }).then(response => {
	      babelHelpers.classPrivateFieldLooseBase(this, _pending)[_pending] = false;
	      if (response.data.tasks.length >= babelHelpers.classPrivateFieldLooseBase(this, _pageSize)[_pageSize]) {
	        babelHelpers.classPrivateFieldLooseBase(this, _pageNum)[_pageNum]++;
	      }
	      resolve({
	        lines: response.data.tasks,
	        totalTaskCount: response.data.totalCount
	      });
	    }).catch(error => {
	      babelHelpers.classPrivateFieldLooseBase(this, _consoleError)[_consoleError]('getList', error);
	    });
	  });
	}
	function _renderContent2() {
	  const {
	    popupContainer,
	    popupContent
	  } = main_core.Tag.render(_t$2 || (_t$2 = _$2`
			<div ref="popupContainer" class="tasks-flow__task-queue-popup_container">
				<div ref="listContainer" class="tasks-flow__task-queue-popup_content">
					<div class="tasks-flow__task-queue-popup_content-box">
						<span class="tasks-flow__task-queue-popup_label">
							<span class="tasks-flow__task-queue-popup_label-text" title="${0}">
								${0}
							</span>
							${0}
						</span>
						${0}
					</div>
				</div>
			</div>
		`), main_core.Loc.getMessage(`TASKS_FLOW_TASK_QUEUE_POPUP_LABEL_${babelHelpers.classPrivateFieldLooseBase(this, _type)[_type]}`), main_core.Loc.getMessage(`TASKS_FLOW_TASK_QUEUE_POPUP_LABEL_${babelHelpers.classPrivateFieldLooseBase(this, _type)[_type]}`), babelHelpers.classPrivateFieldLooseBase(this, _renderCounterContainer)[_renderCounterContainer](), babelHelpers.classPrivateFieldLooseBase(this, _renderLines)[_renderLines]());
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].popupContainer = popupContainer;
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].popupContent = popupContent;
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].popupContainer;
	}
	function _renderCounterContainer2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].counterContainer = main_core.Tag.render(_t2$1 || (_t2$1 = _$2`
			<div class="tasks-flow__total-task-counter-container ui-counter">
					${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderTotalTaskCounter)[_renderTotalTaskCounter]());
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].counterContainer, 'display', 'none');
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].counterContainer;
	}
	function _renderTotalTaskCounter2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].totalTaskCounter = main_core.Tag.render(_t3 || (_t3 = _$2`
			<div class="tasks-flow__total-task-counter ui-counter-inner"></div>
		`));
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].totalTaskCounter;
	}
	function _renderLines2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].popupInner = main_core.Tag.render(_t4 || (_t4 = _$2`
			<div class="tasks-flow__task-queue-popup_inner">
				${0}
			</div>
		`), Object.values(babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages]).flat().map(line => line.render()));
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].popupInner, 'scroll', () => {
	    const scrollTop = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].popupInner.scrollTop;
	    const maxScroll = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].popupInner.scrollHeight - babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].popupInner.offsetHeight;
	    if (Math.abs(scrollTop - maxScroll) < 1 && babelHelpers.classPrivateFieldLooseBase(this, _pending)[_pending] === false) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showLines)[_showLines]();
	    }
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].popupInner;
	}
	function _showLines2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader]();

	  // eslint-disable-next-line promise/catch-or-return
	  babelHelpers.classPrivateFieldLooseBase(this, _appendLines)[_appendLines](babelHelpers.classPrivateFieldLooseBase(this, _pageNum)[_pageNum]).then(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _destroyLoader)[_destroyLoader]();
	    babelHelpers.classPrivateFieldLooseBase(this, _setTotalTaskCount)[_setTotalTaskCount]();
	  });
	}
	function _appendLines2(pageNum) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages][pageNum]) {
	    return Promise.resolve();
	  }
	  const list = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].popupInner;

	  // eslint-disable-next-line promise/catch-or-return
	  return babelHelpers.classPrivateFieldLooseBase(this, _getList)[_getList](pageNum).then(({
	    lines,
	    totalTaskCount
	  }) => {
	    babelHelpers.classPrivateFieldLooseBase(this, _totalTaskCount)[_totalTaskCount] = totalTaskCount;
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].counterContainer, 'display', 'inline-flex');
	    babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages][pageNum] = lines.map(data => new Line(data));
	    babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages][pageNum].forEach(line => main_core.Dom.append(line.render(), list));
	  });
	}
	function _setTotalTaskCount2() {
	  if (!main_core.Type.isNil(babelHelpers.classPrivateFieldLooseBase(this, _totalTaskCount)[_totalTaskCount])) {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].totalTaskCounter.innerText = babelHelpers.classPrivateFieldLooseBase(this, _totalTaskCount)[_totalTaskCount] > 99 ? '99+' : babelHelpers.classPrivateFieldLooseBase(this, _totalTaskCount)[_totalTaskCount];
	  }
	}
	function _showLoader2() {
	  const targetPosition = main_core.Dom.getPosition(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].popupInner);
	  const size = 40;
	  babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = new main_loader.Loader({
	    target: babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].popupInner,
	    size,
	    mode: 'inline',
	    offset: {
	      left: `${targetPosition.width / 2 - size / 2}px`
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].show();
	}
	function _destroyLoader2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].destroy();
	}
	function _consoleError2(action, error) {
	  // eslint-disable-next-line no-console
	  console.error(`TaskQueue: ${action} error`, error);
	}
	TaskQueue.TYPES = {
	  PENDING: 'PENDING',
	  AT_WORK: 'AT_WORK',
	  COMPLETED: 'COMPLETED'
	};

	exports.TaskQueue = TaskQueue;

}((this.BX.Tasks.Flow = this.BX.Tasks.Flow || {}),BX,BX.Main,BX.Tasks,BX));
//# sourceMappingURL=task-queue.bundle.js.map
