/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core_events,main_loader,main_popup,tasks_sidePanelIntegration,main_core) {
	'use strict';

	var _flowId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("flowId");
	var _pageSize = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pageSize");
	var _pageNum = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pageNum");
	var _pages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pages");
	var _consoleError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("consoleError");
	class TeamAjax {
	  constructor(flowId) {
	    Object.defineProperty(this, _consoleError, {
	      value: _consoleError2
	    });
	    Object.defineProperty(this, _flowId, {
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
	    Object.defineProperty(this, _pages, {
	      writable: true,
	      value: {}
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _flowId)[_flowId] = flowId;
	  }
	  async get() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages][babelHelpers.classPrivateFieldLooseBase(this, _pageNum)[_pageNum]]) {
	      return {
	        page: [],
	        members: Object.values(babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages]).flat()
	      };
	    }
	    const {
	      data,
	      error
	    } = await main_core.ajax.runAction('tasks.flow.Team.list', {
	      data: {
	        flowData: {
	          id: babelHelpers.classPrivateFieldLooseBase(this, _flowId)[_flowId]
	        }
	      },
	      navigation: {
	        page: babelHelpers.classPrivateFieldLooseBase(this, _pageNum)[_pageNum],
	        size: babelHelpers.classPrivateFieldLooseBase(this, _pageSize)[_pageSize]
	      }
	    }).catch(response => ({
	      data: [],
	      error: response.errors[0]
	    }));
	    if (error) {
	      babelHelpers.classPrivateFieldLooseBase(this, _consoleError)[_consoleError]('getList', error);
	    }
	    const members = data;
	    babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages][babelHelpers.classPrivateFieldLooseBase(this, _pageNum)[_pageNum]] = members;
	    if (members.length >= babelHelpers.classPrivateFieldLooseBase(this, _pageSize)[_pageSize]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _pageNum)[_pageNum]++;
	    }
	    return {
	      page: members,
	      members: Object.values(babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages]).flat()
	    };
	  }
	}
	function _consoleError2(action, error) {
	  // eslint-disable-next-line no-console
	  console.error(`TeamPopup: ${action} error`, error);
	}

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	var _renderIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderIcon");
	var _renderWorkPosition = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderWorkPosition");
	class TeamMember {
	  constructor(data) {
	    Object.defineProperty(this, _renderWorkPosition, {
	      value: _renderWorkPosition2
	    });
	    Object.defineProperty(this, _renderIcon, {
	      value: _renderIcon2
	    });
	    Object.defineProperty(this, _data, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data] = data;
	  }
	  render() {
	    return main_core.Tag.render(_t || (_t = _`
			<div class="tasks-flow__team-popup_member" data-id="tasks-flow-team-popup-member-${0}">
				${0}
				<div class="tasks-flow__team-popup_member-name-content">
					<a class="tasks-flow__team-popup_member-name" href="${0}">
						${0}
					</a>
					${0}
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].id, babelHelpers.classPrivateFieldLooseBase(this, _renderIcon)[_renderIcon](), main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].pathToProfile), main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].name), babelHelpers.classPrivateFieldLooseBase(this, _renderWorkPosition)[_renderWorkPosition]());
	  }
	}
	function _renderIcon2() {
	  let photoIcon = '<i></i>';
	  if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].avatar)) {
	    photoIcon = `<i style="background-image: url('${encodeURI(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].avatar)}')"></i>`;
	  }
	  return main_core.Tag.render(_t2 || (_t2 = _`
			<a href="${0}">
				<div class="tasks-flow__team-popup_member-avatar ui-icon ui-icon-common-user">
					${0}
				</div>
			</a>
		`), main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].pathToProfile), photoIcon);
	}
	function _renderWorkPosition2() {
	  if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].workPosition)) {
	    return '';
	  }
	  return main_core.Tag.render(_t3 || (_t3 = _`
			<span class="tasks-flow__team-popup_member-position">
				${0}
			</span>
		`), main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].workPosition));
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1;
	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _layout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _teamAjax = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("teamAjax");
	var _members = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("members");
	var _subscribeEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeEvents");
	var _load = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("load");
	var _render = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("render");
	var _renderMembers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderMembers");
	var _showLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLoader");
	var _destroyLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("destroyLoader");
	class TeamPopup {
	  constructor(params) {
	    Object.defineProperty(this, _destroyLoader, {
	      value: _destroyLoader2
	    });
	    Object.defineProperty(this, _showLoader, {
	      value: _showLoader2
	    });
	    Object.defineProperty(this, _renderMembers, {
	      value: _renderMembers2
	    });
	    Object.defineProperty(this, _render, {
	      value: _render2
	    });
	    Object.defineProperty(this, _load, {
	      value: _load2
	    });
	    Object.defineProperty(this, _subscribeEvents, {
	      value: _subscribeEvents2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _teamAjax, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _members, {
	      writable: true,
	      value: []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _teamAjax)[_teamAjax] = new TeamAjax(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].flowId);
	    void babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]();
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeEvents)[_subscribeEvents]();
	  }
	  static showInstance(params) {
	    this.getInstance(params).show(params.bindElement);
	  }
	  static getInstance(params) {
	    var _this$instances, _params$flowId, _this$instances$_para;
	    (_this$instances$_para = (_this$instances = this.instances)[_params$flowId = params.flowId]) != null ? _this$instances$_para : _this$instances[_params$flowId] = new this(params);
	    return this.instances[params.flowId];
	  }
	  static removeInstance(flowId) {
	    if (Object.hasOwn(this.instances, flowId)) {
	      delete this.instances[flowId];
	    }
	  }
	  show(bindElement) {
	    const popup = this.getPopup();
	    popup.setContent(babelHelpers.classPrivateFieldLooseBase(this, _render)[_render]());
	    popup.setBindElement(bindElement);
	    popup.show();
	  }
	  getPopup() {
	    const id = `tasks-flow-team-popup-${babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].flowId}`;
	    if (main_popup.PopupManager.getPopupById(id)) {
	      return main_popup.PopupManager.getPopupById(id);
	    }
	    const popup = new main_popup.Popup({
	      id,
	      className: 'tasks-flow__team-popup',
	      width: 200,
	      padding: 2,
	      autoHide: true,
	      closeByEsc: true,
	      events: {
	        onShow: () => {
	          if (babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].loader) {
	            babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader]();
	          }
	        }
	      }
	    });
	    new tasks_sidePanelIntegration.SidePanelIntegration(popup);
	    return popup;
	  }
	}
	function _subscribeEvents2() {
	  main_core_events.EventEmitter.subscribe('BX.Tasks.Flow.EditForm:afterSave', event => {
	    var _event$data$id, _event$data;
	    const flowId = (_event$data$id = (_event$data = event.data) == null ? void 0 : _event$data.id) != null ? _event$data$id : 0;
	    TeamPopup.removeInstance(flowId);
	  });
	}
	async function _load2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].loader) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader]();
	  let {
	    members,
	    page
	  } = await babelHelpers.classPrivateFieldLooseBase(this, _teamAjax)[_teamAjax].get();
	  if (!main_core.Type.isNil(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].excludeMembers)) {
	    const isNeedToExclude = member => babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].excludeMembers.includes(Number(member.id));
	    page = page.filter(member => !isNeedToExclude(member));
	    members = members.filter(member => !isNeedToExclude(member));
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _members)[_members] = members;
	  page.forEach(data => main_core.Dom.append(new TeamMember(data).render(), babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].members));
	  babelHelpers.classPrivateFieldLooseBase(this, _destroyLoader)[_destroyLoader]();
	}
	function _render2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap = main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="tasks-flow__team-popup_container">
				<div class="tasks-flow__team-popup_content">
					<div class="tasks-flow__team-popup_content-box">
						<span class="tasks-flow__team-popup_label">
							<span class="tasks-flow__team-popup_label-text">
								${0}
							</span>
						</span>
						${0}
					</div>
				</div>
			</div>
		`), main_core.Loc.getMessage('TASKS_FLOW_TEAM_POPUP_LABEL'), babelHelpers.classPrivateFieldLooseBase(this, _renderMembers)[_renderMembers]());
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap;
	}
	function _renderMembers2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].members = main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
			<div class="tasks-flow__team-popup_members">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _members)[_members].map(data => new TeamMember(data).render()));
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].members, 'scroll', () => {
	    const scrollTop = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].members.scrollTop;
	    const maxScroll = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].members.scrollHeight - babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].members.offsetHeight;
	    if (Math.abs(scrollTop - maxScroll) < 1) {
	      void babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]();
	    }
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].members;
	}
	function _showLoader2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _destroyLoader)[_destroyLoader]();
	  const targetPosition = main_core.Dom.getPosition(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].members);
	  const size = 40;
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].loader = new main_loader.Loader({
	    target: babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].members,
	    size,
	    mode: 'inline',
	    offset: {
	      left: `${targetPosition.width / 2 - size / 2}px`
	    }
	  });
	  void babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].loader.show();
	}
	function _destroyLoader2() {
	  var _babelHelpers$classPr;
	  (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].loader) == null ? void 0 : _babelHelpers$classPr.destroy();
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].loader = null;
	}
	TeamPopup.instances = {};

	exports.TeamPopup = TeamPopup;

}((this.BX.Tasks.Flow = this.BX.Tasks.Flow || {}),BX.Event,BX,BX.Main,BX.Tasks,BX));
//# sourceMappingURL=team-popup.bundle.js.map
