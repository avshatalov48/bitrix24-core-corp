/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core_events,main_popup,tasks_flow_teamPopup,tasks_sidePanelIntegration,ui_label,main_core,main_loader,ui_buttons,ui_infoHelper) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _segments = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("segments");
	var _renderSegment = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderSegment");
	var _selectSegment = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectSegment");
	class SegmentButton {
	  constructor(params) {
	    Object.defineProperty(this, _selectSegment, {
	      value: _selectSegment2
	    });
	    Object.defineProperty(this, _renderSegment, {
	      value: _renderSegment2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _segments, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments] = params.segments;
	  }
	  render() {
	    return main_core.Tag.render(_t || (_t = _`
			<div class="tasks-flow__segment-button">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments].map(segment => babelHelpers.classPrivateFieldLooseBase(this, _renderSegment)[_renderSegment](segment)));
	  }
	}
	function _renderSegment2(segment) {
	  segment.node = main_core.Tag.render(_t2 || (_t2 = _`
			<div class="tasks-flow__segment-button-segment ${0}">
				${0}
			</div>
		`), segment.isActive ? '--active' : '', segment.title);
	  main_core.Event.bind(segment.node, 'click', () => babelHelpers.classPrivateFieldLooseBase(this, _selectSegment)[_selectSegment](segment));
	  return segment.node;
	}
	function _selectSegment2(selectedSegment) {
	  babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments].forEach(segment => {
	    main_core.Dom.removeClass(segment.node, '--active');
	    if (segment.id === selectedSegment.id) {
	      main_core.Dom.addClass(segment.node, '--active');
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].onSegmentSelected(selectedSegment);
	}

	var _flowId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("flowId");
	var _pageSize = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pageSize");
	var _pageNum = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pageNum");
	var _pages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pages");
	var _convertUserToEntity = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("convertUserToEntity");
	class ViewAjax {
	  constructor(flowId) {
	    Object.defineProperty(this, _convertUserToEntity, {
	      value: _convertUserToEntity2
	    });
	    Object.defineProperty(this, _flowId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _pageSize, {
	      writable: true,
	      value: 7
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
	  async getViewFormData() {
	    const {
	      data
	    } = await main_core.ajax.runAction('tasks.flow.View.Flow.get', {
	      data: {
	        flowId: babelHelpers.classPrivateFieldLooseBase(this, _flowId)[_flowId]
	      }
	    });
	    return {
	      flow: data.flow,
	      team: data.team.map(member => babelHelpers.classPrivateFieldLooseBase(this, _convertUserToEntity)[_convertUserToEntity](member)),
	      teamCount: data.teamCount,
	      owner: babelHelpers.classPrivateFieldLooseBase(this, _convertUserToEntity)[_convertUserToEntity](data.owner),
	      creator: babelHelpers.classPrivateFieldLooseBase(this, _convertUserToEntity)[_convertUserToEntity](data.creator),
	      project: data.project
	    };
	  }
	  async getSimilarFlows() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages][babelHelpers.classPrivateFieldLooseBase(this, _pageNum)[_pageNum]]) {
	      return {
	        page: [],
	        similarFlows: Object.values(babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages]).flat()
	      };
	    }
	    const {
	      data: page
	    } = await main_core.ajax.runAction('tasks.flow.View.SimilarFlow.list', {
	      data: {
	        flowId: babelHelpers.classPrivateFieldLooseBase(this, _flowId)[_flowId]
	      },
	      navigation: {
	        page: babelHelpers.classPrivateFieldLooseBase(this, _pageNum)[_pageNum],
	        size: babelHelpers.classPrivateFieldLooseBase(this, _pageSize)[_pageSize]
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages][babelHelpers.classPrivateFieldLooseBase(this, _pageNum)[_pageNum]] = page;
	    if (page.length >= babelHelpers.classPrivateFieldLooseBase(this, _pageSize)[_pageSize]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _pageNum)[_pageNum]++;
	    }
	    return {
	      page,
	      similarFlows: Object.values(babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages]).flat()
	    };
	  }
	}
	function _convertUserToEntity2(user) {
	  return {
	    name: user.name,
	    avatar: user.avatar,
	    url: user.pathToProfile
	  };
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3,
	  _t4;
	var _flowId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("flowId");
	var _viewAjax = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("viewAjax");
	var _createTaskButtonClickHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createTaskButtonClickHandler");
	var _similarFlows = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("similarFlows");
	var _layout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _load = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("load");
	var _renderSimilarFlowsListTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderSimilarFlowsListTitle");
	var _renderSimilarFlow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderSimilarFlow");
	var _renderEmptyState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderEmptyState");
	class SimilarFlows {
	  constructor(params) {
	    var _params$createTaskBut;
	    Object.defineProperty(this, _renderEmptyState, {
	      value: _renderEmptyState2
	    });
	    Object.defineProperty(this, _renderSimilarFlow, {
	      value: _renderSimilarFlow2
	    });
	    Object.defineProperty(this, _renderSimilarFlowsListTitle, {
	      value: _renderSimilarFlowsListTitle2
	    });
	    Object.defineProperty(this, _load, {
	      value: _load2
	    });
	    Object.defineProperty(this, _flowId$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _viewAjax, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _createTaskButtonClickHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _similarFlows, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _layout, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _flowId$1)[_flowId$1] = params.flowId;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout] = {};
	    this.isFeatureEnabled = params.isFeatureEnabled;
	    babelHelpers.classPrivateFieldLooseBase(this, _viewAjax)[_viewAjax] = new ViewAjax(params.flowId);
	    babelHelpers.classPrivateFieldLooseBase(this, _createTaskButtonClickHandler)[_createTaskButtonClickHandler] = (_params$createTaskBut = params.createTaskButtonClickHandler) != null ? _params$createTaskBut : null;
	  }
	  show() {
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap, 'display', '');
	    if (!main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _similarFlows)[_similarFlows])) {
	      main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap, 'overflow', 'hidden');
	      babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]().then(() => main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap, 'overflow', ''));
	    }
	  }
	  hide() {
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap, 'display', 'none');
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap = main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="tasks-flow__view-form_similar-flows">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _similarFlows)[_similarFlows].map(flow => babelHelpers.classPrivateFieldLooseBase(this, _renderSimilarFlow)[_renderSimilarFlow](flow)));
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap, 'scroll', () => {
	      const scrollTop = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap.scrollTop;
	      const maxScroll = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap.scrollHeight - babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap.offsetHeight;
	      if (Math.abs(scrollTop - maxScroll) < 1) {
	        void babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]();
	      }
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap;
	  }
	}
	async function _load2() {
	  var _babelHelpers$classPr;
	  (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].emptyState) == null ? void 0 : _babelHelpers$classPr.remove();
	  const loader = new main_loader.Loader({
	    target: babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap,
	    size: 60
	  });
	  void loader.show();
	  const {
	    page,
	    similarFlows
	  } = await babelHelpers.classPrivateFieldLooseBase(this, _viewAjax)[_viewAjax].getSimilarFlows();
	  const isFirstPageLoaded = !main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _similarFlows)[_similarFlows]) && main_core.Type.isArrayFilled(similarFlows);
	  if (isFirstPageLoaded) {
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _renderSimilarFlowsListTitle)[_renderSimilarFlowsListTitle](), babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _similarFlows)[_similarFlows] = similarFlows;
	  page.forEach(data => main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _renderSimilarFlow)[_renderSimilarFlow](data), babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap));
	  if (!main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _similarFlows)[_similarFlows])) {
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _renderEmptyState)[_renderEmptyState](), babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap);
	  }
	  loader.destroy();
	}
	function _renderSimilarFlowsListTitle2() {
	  return main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
			<div class="tasks-flow__view-form_similar-flows-title">
				${0}
			</div>
		`), main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_SIMILAR_FLOWS_TITLE'));
	}
	function _renderSimilarFlow2(flow) {
	  const button = new ui_buttons.Button({
	    color: ui_buttons.Button.Color.SECONDARY_LIGHT,
	    size: ui_buttons.Button.Size.EXTRA_SMALL,
	    round: true,
	    text: main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_CREATE_TASK'),
	    noCaps: true,
	    onclick: () => {
	      var _babelHelpers$classPr2, _babelHelpers$classPr3;
	      (_babelHelpers$classPr2 = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _createTaskButtonClickHandler))[_createTaskButtonClickHandler]) == null ? void 0 : _babelHelpers$classPr2.call(_babelHelpers$classPr3);
	      if (this.isFeatureEnabled) {
	        BX.SidePanel.Instance.open(flow.createTaskUri);
	      } else {
	        ui_infoHelper.FeaturePromotersRegistry.getPromoter({
	          code: 'limit_tasks_flows'
	        }).show();
	      }
	    }
	  });
	  return main_core.Tag.render(_t3 || (_t3 = _$1`
			<div class="tasks-flow__view-form_similar-flow">
				<div class="tasks-flow__view-form_similar-flow-name" title="${0}">
					${0}
				</div>
				${0}
			</div>
		`), main_core.Text.encode(flow.name), main_core.Text.encode(flow.name), button.render());
	}
	function _renderEmptyState2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].emptyState = main_core.Tag.render(_t4 || (_t4 = _$1`
			<div class="tasks-flow__view-form_similar-flows-empty-state">
				<div class="tasks-flow__view-form_similar-flows-empty-state-icon"></div>
				<div class="tasks-flow__view-form_similar-flows-empty-state-text">
					${0}
				</div>
			</div>
		`), main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_NO_SIMILAR_FLOWS'));
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].emptyState;
	}

	let _$2 = t => t,
	  _t$2,
	  _t2$2,
	  _t3$1,
	  _t4$1,
	  _t5,
	  _t6,
	  _t7,
	  _t8,
	  _t9,
	  _t10,
	  _t11,
	  _t12;
	var _params$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _layout$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _notificationList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("notificationList");
	var _viewAjax$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("viewAjax");
	var _selectedSegment = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedSegment");
	var _viewFormData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("viewFormData");
	var _subscribeEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeEvents");
	var _load$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("load");
	var _render = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("render");
	var _renderLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderLoader");
	var _renderHeader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderHeader");
	var _renderDescription = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderDescription");
	var _renderEfficiencyLabel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderEfficiencyLabel");
	var _renderTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderTitle");
	var _renderContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderContent");
	var _renderSegmentButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderSegmentButton");
	var _updateSegmentsVisibility = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateSegmentsVisibility");
	var _renderDetails = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderDetails");
	var _renderProjectField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderProjectField");
	var _renderField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderField");
	var _renderEntity = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderEntity");
	var _renderTeam = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderTeam");
	var _renderAvatar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderAvatar");
	var _isAvatar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAvatar");
	var _renderShowTeamButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderShowTeamButton");
	var _getShowTeamButtonText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getShowTeamButtonText");
	var _onShowTeamButtonClickHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onShowTeamButtonClickHandler");
	var _renderSimilarFlows = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderSimilarFlows");
	class ViewForm {
	  constructor(params) {
	    Object.defineProperty(this, _renderSimilarFlows, {
	      value: _renderSimilarFlows2
	    });
	    Object.defineProperty(this, _onShowTeamButtonClickHandler, {
	      value: _onShowTeamButtonClickHandler2
	    });
	    Object.defineProperty(this, _getShowTeamButtonText, {
	      value: _getShowTeamButtonText2
	    });
	    Object.defineProperty(this, _renderShowTeamButton, {
	      value: _renderShowTeamButton2
	    });
	    Object.defineProperty(this, _isAvatar, {
	      value: _isAvatar2
	    });
	    Object.defineProperty(this, _renderAvatar, {
	      value: _renderAvatar2
	    });
	    Object.defineProperty(this, _renderTeam, {
	      value: _renderTeam2
	    });
	    Object.defineProperty(this, _renderEntity, {
	      value: _renderEntity2
	    });
	    Object.defineProperty(this, _renderField, {
	      value: _renderField2
	    });
	    Object.defineProperty(this, _renderProjectField, {
	      value: _renderProjectField2
	    });
	    Object.defineProperty(this, _renderDetails, {
	      value: _renderDetails2
	    });
	    Object.defineProperty(this, _updateSegmentsVisibility, {
	      value: _updateSegmentsVisibility2
	    });
	    Object.defineProperty(this, _renderSegmentButton, {
	      value: _renderSegmentButton2
	    });
	    Object.defineProperty(this, _renderContent, {
	      value: _renderContent2
	    });
	    Object.defineProperty(this, _renderTitle, {
	      value: _renderTitle2
	    });
	    Object.defineProperty(this, _renderEfficiencyLabel, {
	      value: _renderEfficiencyLabel2
	    });
	    Object.defineProperty(this, _renderDescription, {
	      value: _renderDescription2
	    });
	    Object.defineProperty(this, _renderHeader, {
	      value: _renderHeader2
	    });
	    Object.defineProperty(this, _renderLoader, {
	      value: _renderLoader2
	    });
	    Object.defineProperty(this, _render, {
	      value: _render2
	    });
	    Object.defineProperty(this, _load$1, {
	      value: _load2$1
	    });
	    Object.defineProperty(this, _subscribeEvents, {
	      value: _subscribeEvents2
	    });
	    Object.defineProperty(this, _params$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _notificationList, {
	      writable: true,
	      value: new Set()
	    });
	    Object.defineProperty(this, _viewAjax$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedSegment, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _viewFormData, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params$1)[_params$1] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _viewAjax$1)[_viewAjax$1] = new ViewAjax(babelHelpers.classPrivateFieldLooseBase(this, _params$1)[_params$1].flowId);
	    this.isFeatureEnabled = params.isFeatureEnabled === 'Y';
	    this.flowUrl = params.flowUrl;
	    void babelHelpers.classPrivateFieldLooseBase(this, _load$1)[_load$1]();
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
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].popup = this.getPopup();
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].popup.setContent(babelHelpers.classPrivateFieldLooseBase(this, _render)[_render]());
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].popup.setBindElement(bindElement);
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].popup.show();
	  }
	  getPopup() {
	    const id = `tasks-flow-view-popup-${babelHelpers.classPrivateFieldLooseBase(this, _params$1)[_params$1].flowId}`;
	    if (main_popup.PopupManager.getPopupById(id)) {
	      return main_popup.PopupManager.getPopupById(id);
	    }
	    const popup = new main_popup.Popup({
	      id,
	      className: 'tasks-flow__view-popup',
	      animation: 'fading-slide',
	      minWidth: 347,
	      maxWidth: 347,
	      padding: 0,
	      borderRadius: 12,
	      autoHide: true,
	      overlay: true,
	      closeByEsc: true,
	      autoHideHandler: ({
	        target
	      }) => {
	        var _babelHelpers$classPr;
	        const isSelf = popup.getPopupContainer().contains(target);
	        const isTeam = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].teamPopup) == null ? void 0 : _babelHelpers$classPr.getPopup().getPopupContainer().contains(target);
	        return !isSelf && !isTeam;
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
	    ViewForm.removeInstance(flowId);
	  });
	}
	async function _load2$1() {
	  babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData] = await babelHelpers.classPrivateFieldLooseBase(this, _viewAjax$1)[_viewAjax$1].getViewFormData();
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].popup.setContent(babelHelpers.classPrivateFieldLooseBase(this, _render)[_render]());
	}
	function _render2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _renderLoader)[_renderLoader]();
	  }
	  return main_core.Tag.render(_t$2 || (_t$2 = _$2`
			<div class="tasks-flow__view-form">
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderHeader)[_renderHeader](), babelHelpers.classPrivateFieldLooseBase(this, _renderContent)[_renderContent]());
	}
	function _renderLoader2() {
	  const loaderContainer = main_core.Tag.render(_t2$2 || (_t2$2 = _$2`
			<div class="tasks-flow__view-form-loader" style="width: 347px; height: 300px;">
			</div>
		`));
	  void new main_loader.Loader({
	    target: loaderContainer
	  }).show();
	  return loaderContainer;
	}
	function _renderHeader2() {
	  return main_core.Tag.render(_t3$1 || (_t3$1 = _$2`
			<div class="tasks-flow__view-form_header">
				<div class="tasks-flow__view-form_header-title">
					${0}
					<div class="tasks-flow__view-form-header_title-efficiency">
						${0}
					</div>
				</div>
				<div class="tasks-flow__view-form-header_description">
					${0}
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderTitle)[_renderTitle](), babelHelpers.classPrivateFieldLooseBase(this, _renderEfficiencyLabel)[_renderEfficiencyLabel](babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].flow.efficiency), babelHelpers.classPrivateFieldLooseBase(this, _renderDescription)[_renderDescription](babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].flow.description));
	}
	function _renderDescription2(description) {
	  const descriptionNode = main_core.Tag.render(_t4$1 || (_t4$1 = _$2`
			<div 
				class="tasks-flow__view-form_header-description" 
				title="${0}"
			></div>
		`), main_core.Text.encode(description));
	  descriptionNode.innerText = description;
	  return descriptionNode;
	}
	function _renderEfficiencyLabel2(efficiency) {
	  return new ui_label.Label({
	    text: `${efficiency}%`,
	    color: efficiency < 60 ? ui_label.LabelColor.DANGER : ui_label.LabelColor.SUCCESS,
	    size: ui_label.LabelSize.SM,
	    fill: true
	  }).render();
	}
	function _renderTitle2() {
	  const title = main_core.Tag.render(_t5 || (_t5 = _$2`
			<div class="tasks-flow__view-form-header_title-link">
				<div
					class="tasks-flow__view-form-header_title-text"
					title="${0}"
				>
					${0}
				</div>
				<div 
					class="tasks-flow__view-form-header_title-link-icon ui-icon-set --link-3"
					style="--ui-icon-set__icon-size: 16px;"
					title="${0}"
				></div>
			</div>
		`), main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].flow.name), main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].flow.name), main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_LINK_TITLE'));
	  main_core.Event.bind(title, 'click', () => {
	    const notificationId = 'copy-link';
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _notificationList)[_notificationList].has(notificationId)) {
	      const flowURL = window.location.protocol + this.flowUrl;
	      BX.clipboard.copy(flowURL);
	      BX.UI.Notification.Center.notify({
	        id: notificationId,
	        content: main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_TITLE_COPY_LINK')
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _notificationList)[_notificationList].add(notificationId);
	      main_core_events.EventEmitter.subscribeOnce('UI.Notification.Balloon:onClose', baseEvent => {
	        const closingBalloon = baseEvent.getTarget();
	        if (closingBalloon.getId() === notificationId) {
	          babelHelpers.classPrivateFieldLooseBase(this, _notificationList)[_notificationList].delete(notificationId);
	        }
	      });
	    }
	  });
	  return title;
	}
	function _renderContent2() {
	  const content = main_core.Tag.render(_t6 || (_t6 = _$2`
			<div class="tasks-flow__view-form-content">
				${0}
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderSegmentButton)[_renderSegmentButton](), babelHelpers.classPrivateFieldLooseBase(this, _renderDetails)[_renderDetails](), babelHelpers.classPrivateFieldLooseBase(this, _renderSimilarFlows)[_renderSimilarFlows]());
	  babelHelpers.classPrivateFieldLooseBase(this, _updateSegmentsVisibility)[_updateSegmentsVisibility]();
	  return content;
	}
	function _renderSegmentButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _selectedSegment)[_selectedSegment] = 'details';
	  return new SegmentButton({
	    segments: [{
	      id: 'details',
	      title: main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_DETAILS'),
	      isActive: true
	    }, {
	      id: 'similarFlows',
	      title: main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_SIMILAR_FLOWS')
	    }],
	    onSegmentSelected: segment => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _selectedSegment)[_selectedSegment] !== segment.id) {
	        babelHelpers.classPrivateFieldLooseBase(this, _selectedSegment)[_selectedSegment] = segment.id;
	        babelHelpers.classPrivateFieldLooseBase(this, _updateSegmentsVisibility)[_updateSegmentsVisibility]();
	      }
	    }
	  }).render();
	}
	function _updateSegmentsVisibility2() {
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].details, 'display', 'none');
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].similarFlows.hide();
	  if (babelHelpers.classPrivateFieldLooseBase(this, _selectedSegment)[_selectedSegment] === 'details') {
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].details, 'display', '');
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _selectedSegment)[_selectedSegment] === 'similarFlows') {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].similarFlows.show();
	  }
	}
	function _renderDetails2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].details = main_core.Tag.render(_t7 || (_t7 = _$2`
			<div class="tasks-flow__view-form-details">
				${0}
				${0}
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderField)[_renderField](main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_CREATOR'), babelHelpers.classPrivateFieldLooseBase(this, _renderEntity)[_renderEntity](babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].creator)), babelHelpers.classPrivateFieldLooseBase(this, _renderField)[_renderField](main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_ADMINISTRATOR'), babelHelpers.classPrivateFieldLooseBase(this, _renderEntity)[_renderEntity](babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].owner)), babelHelpers.classPrivateFieldLooseBase(this, _renderField)[_renderField](main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_TEAM'), babelHelpers.classPrivateFieldLooseBase(this, _renderTeam)[_renderTeam]()), babelHelpers.classPrivateFieldLooseBase(this, _renderProjectField)[_renderProjectField]());
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].details;
	}
	function _renderProjectField2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].project) {
	    const content = babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].flow.demo === true ? main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_PROJECT_DEMO') : main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_PROJECT_HIDDEN');
	    return babelHelpers.classPrivateFieldLooseBase(this, _renderField)[_renderField](main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_PROJECT'), content);
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _renderField)[_renderField](main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_PROJECT'), babelHelpers.classPrivateFieldLooseBase(this, _renderEntity)[_renderEntity](babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].project));
	}
	function _renderField2(title, content) {
	  return main_core.Tag.render(_t8 || (_t8 = _$2`
			<div class="tasks-flow__view-form_field-name">
				${0}
			</div>
			<div class="tasks-flow__view-form_field-value">
				${0}
			</div>
		`), title, content);
	}
	function _renderEntity2(entity) {
	  return main_core.Tag.render(_t9 || (_t9 = _$2`
			<a class="tasks-flow__view-form_entity" href="${0}">
				${0}
				<div class="tasks-flow__view-form_entity-name" title="${0}">
					${0}
				</div>
			</a>
		`), encodeURI(entity.url), babelHelpers.classPrivateFieldLooseBase(this, _renderAvatar)[_renderAvatar](entity), main_core.Text.encode(entity.name), main_core.Text.encode(entity.name));
	}
	function _renderTeam2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].flow.demo === true) {
	    return main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_PROJECT_DEMO');
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].team.length === 1) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _renderEntity)[_renderEntity](babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].team[0]);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].teamNode = main_core.Tag.render(_t10 || (_t10 = _$2`
			<div class="tasks-flow__view-form_line-avatars">
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].team.map(entity => babelHelpers.classPrivateFieldLooseBase(this, _renderAvatar)[_renderAvatar](entity)), babelHelpers.classPrivateFieldLooseBase(this, _renderShowTeamButton)[_renderShowTeamButton]());
	  if (babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].teamCount === babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].team.length) {
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].teamNode, 'click', babelHelpers.classPrivateFieldLooseBase(this, _onShowTeamButtonClickHandler)[_onShowTeamButtonClickHandler].bind(this));
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].teamNode;
	}
	function _renderAvatar2(entity) {
	  const style = babelHelpers.classPrivateFieldLooseBase(this, _isAvatar)[_isAvatar](entity.avatar) ? `background-image: url('${encodeURI(entity.avatar)}');` : '';
	  return main_core.Tag.render(_t11 || (_t11 = _$2`
			<span class="ui-icon ui-icon-common-user tasks-flow__view-form_avatar" title="${0}">
				<i style="${0}"></i>
			</span>
		`), main_core.Text.encode(entity.name), style);
	}
	function _isAvatar2(avatar) {
	  return main_core.Type.isStringFilled(avatar);
	}
	function _renderShowTeamButton2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].teamCount === babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].team.length) {
	    var _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].showTeamButton) == null ? void 0 : _babelHelpers$classPr2.remove();
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].showTeamButton = null;
	    return '';
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].showTeamButton = main_core.Tag.render(_t12 || (_t12 = _$2`
			<div class="tasks-flow__view-form_show-team-button">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getShowTeamButtonText)[_getShowTeamButtonText]());
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].showTeamButton, 'click', babelHelpers.classPrivateFieldLooseBase(this, _onShowTeamButtonClickHandler)[_onShowTeamButtonClickHandler].bind(this));
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].showTeamButton;
	}
	function _getShowTeamButtonText2() {
	  return main_core.Loc.getMessage('TASKS_FLOW_VIEW_FORM_ALL_N', {
	    '#NUM#': babelHelpers.classPrivateFieldLooseBase(this, _viewFormData)[_viewFormData].teamCount
	  });
	}
	function _onShowTeamButtonClickHandler2() {
	  var _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5;
	  const flowId = babelHelpers.classPrivateFieldLooseBase(this, _params$1)[_params$1].flowId;
	  const bindElement = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].showTeamButton) != null ? _babelHelpers$classPr3 : babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].teamNode;
	  (_babelHelpers$classPr5 = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1]).teamPopup) != null ? _babelHelpers$classPr5 : _babelHelpers$classPr4.teamPopup = tasks_flow_teamPopup.TeamPopup.getInstance({
	    flowId
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].teamPopup.show(bindElement);
	}
	function _renderSimilarFlows2() {
	  var _babelHelpers$classPr6, _babelHelpers$classPr7;
	  (_babelHelpers$classPr7 = (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1]).similarFlows) != null ? _babelHelpers$classPr7 : _babelHelpers$classPr6.similarFlows = new SimilarFlows({
	    flowId: babelHelpers.classPrivateFieldLooseBase(this, _params$1)[_params$1].flowId,
	    isFeatureEnabled: this.isFeatureEnabled,
	    createTaskButtonClickHandler: () => {
	      var _babelHelpers$classPr8;
	      return (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].popup) == null ? void 0 : _babelHelpers$classPr8.destroy();
	    }
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].similarFlows.render();
	}
	ViewForm.instances = {};

	exports.ViewForm = ViewForm;

}((this.BX.Tasks.Flow = this.BX.Tasks.Flow || {}),BX.Event,BX.Main,BX.Tasks.Flow,BX.Tasks,BX.UI,BX,BX,BX.UI,BX.UI));
//# sourceMappingURL=view-form.bundle.js.map
