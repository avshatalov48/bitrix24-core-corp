/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_vue3,timeman,CJSTask,planner,tasks_planner_handler,calendar_planner_handler,ajax,timer,popup,ls,main_core,main_core_events,ui_iconSet_api_vue,ui_vue3_components_button) {
	'use strict';

	// @vue/component
	const App = {
	  name: 'WorkStatusControlPanel',
	  components: {
	    UiButton: ui_vue3_components_button.Button,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  provide() {
	    return {};
	  },
	  props: {},
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline,
	      Loc: main_core.Loc
	    };
	  },
	  data() {
	    return {
	      dataId: '',
	      workStatus: '',
	      reportReq: '',
	      canOpen: '',
	      canOpenAndRelaunch: '',
	      canEdit: '',
	      timerWorkingDayValue: 0,
	      timerPauseValue: 0
	    };
	  },
	  computed: {
	    titleText() {
	      if (this.workStatus === 'PAUSED') {
	        return main_core.Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_STATUS_PAUSED');
	      }
	      if (this.workStatus === 'CLOSED') {
	        return this.canOpen === 'OPEN' ? main_core.Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_STATUS_NOT_STARTED') : main_core.Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_STATUS_CLOSED');
	      }
	      if (this.workStatus === 'EXPIRED') {
	        return main_core.Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_STATUS_NOT_CLOSED');
	      }
	      return main_core.Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_STATUS_STARTED');
	    },
	    isEditingAvailable() {
	      return this.dataId && this.canEdit === 'Y' && !(this.workStatus === 'EXPIRED' && this.reportReq !== 'A');
	    },
	    styleForTimerProps() {
	      if (this.workStatus === 'PAUSED') {
	        return {
	          icon: null,
	          status: 'paused'
	        };
	      }
	      if (this.workStatus === 'EXPIRED') {
	        return {
	          icon: ui_iconSet_api_vue.Outline.ALERT,
	          status: 'expired'
	        };
	      }
	      return null;
	    },
	    // control buttons

	    buttonStartProps() {
	      return {
	        text: main_core.Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_START'),
	        icon: ui_iconSet_api_vue.Outline.PLAY_L,
	        size: ui_vue3_components_button.ButtonSize.LARGE,
	        onClick: async () => {
	          event.preventDefault();
	          event.stopPropagation();
	          window.BXTIMEMAN.WND.ACTIONS.OPEN(event);
	        }
	      };
	    },
	    buttonPauseProps() {
	      return {
	        text: main_core.Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_PAUSE'),
	        icon: ui_iconSet_api_vue.Outline.PAUSE_L,
	        size: ui_vue3_components_button.ButtonSize.LARGE,
	        style: ui_vue3_components_button.AirButtonStyle.OUTLINE_ACCENT_2,
	        onClick: async () => {
	          event.preventDefault();
	          event.stopPropagation();
	          window.BXTIMEMAN.WND.ACTIONS.PAUSE(event);
	        }
	      };
	    },
	    buttonContinueProps() {
	      return {
	        text: main_core.Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_CONTINUE'),
	        icon: ui_iconSet_api_vue.Outline.PLAY_L,
	        size: ui_vue3_components_button.ButtonSize.LARGE,
	        onClick: async () => {
	          event.preventDefault();
	          event.stopPropagation();
	          window.BXTIMEMAN.WND.ACTIONS.REOPEN(event);
	        }
	      };
	    },
	    buttonStopProps() {
	      return {
	        text: main_core.Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_STOP'),
	        icon: ui_iconSet_api_vue.Outline.CROSS_L,
	        size: ui_vue3_components_button.ButtonSize.LARGE,
	        style: ui_vue3_components_button.AirButtonStyle.OUTLINE,
	        onClick: async () => {
	          event.preventDefault();
	          event.stopPropagation();
	          window.BXTIMEMAN.WND.ACTIONS.CLOSE(event);
	        }
	      };
	    },
	    buttonRestartProps() {
	      return {
	        text: main_core.Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_RESTART'),
	        icon: ui_iconSet_api_vue.Outline.REFRESH,
	        size: ui_vue3_components_button.ButtonSize.LARGE,
	        style: ui_vue3_components_button.AirButtonStyle.OUTLINE_ACCENT_2,
	        onClick: async () => {
	          event.preventDefault();
	          event.stopPropagation();
	          window.BXTIMEMAN.WND.ACTIONS.REOPEN(event);
	        }
	      };
	    },
	    buttonFinishExpiredProps() {
	      return {
	        text: main_core.Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_FINISH_EXPIRED'),
	        icon: ui_iconSet_api_vue.Outline.ALERT_ACCENT,
	        size: ui_vue3_components_button.ButtonSize.LARGE,
	        style: ui_vue3_components_button.AirButtonStyle.TINTED_ALERT,
	        onClick: async () => {
	          event.preventDefault();
	          event.stopPropagation();
	          window.BXTIMEMAN.WND.ACTIONS.CLOSE(event);
	        }
	      };
	    },
	    // control buttons end

	    actions() {
	      const actionItems = [];
	      if (this.workStatus === 'OPENED') {
	        actionItems.push(this.buttonPauseProps);
	        actionItems.push(this.buttonStopProps);
	      }
	      if (this.workStatus === 'PAUSED') {
	        actionItems.push(this.buttonContinueProps);
	        actionItems.push(this.buttonStopProps);
	      }
	      if (this.workStatus === 'CLOSED') {
	        if (this.canOpen === 'OPEN') {
	          actionItems.push(this.buttonStartProps);
	        } else {
	          actionItems.push(this.buttonRestartProps);
	        }
	      }
	      if (this.workStatus === 'EXPIRED') {
	        actionItems.push(this.buttonFinishExpiredProps);
	      }
	      return actionItems;
	    }
	  },
	  watch: {},
	  mounted() {
	    this.updateDayState();
	    this.updateWorkingDayTimer();
	    setInterval(() => {
	      this.updateWorkingDayTimer();
	    }, 1000);
	    main_core_events.EventEmitter.subscribe('onTimeManDataRecieved', this.handleTimemanDataRecieved);
	    main_core_events.EventEmitter.subscribe('onPlannerDataRecieved', this.handleTimemanDataRecieved);
	    main_core_events.EventEmitter.subscribe('onTimeManNeedRebuild', this.handleTimemanDataRecieved);
	    main_core_events.EventEmitter.subscribe('onTopPanelCollapse', this.handleTimemanDataRecieved);
	    main_core_events.EventEmitter.subscribe('onTimeManWindowBuild', this.handleTimemanDataRecieved);
	    main_core_events.EventEmitter.subscribe('onTimemanInit', this.handleTimemanDataRecieved);
	  },
	  beforeUnmount() {},
	  unmounted() {},
	  methods: {
	    convertMillisecondsToHrMinSec(time) {
	      const timeFullSeconds = Math.ceil(time / 1000);
	      const hours = Math.floor(timeFullSeconds / 3600);
	      const minutes = Math.floor(timeFullSeconds / 60) - hours * 60;
	      const seconds = timeFullSeconds - minutes * 60 - hours * 3600;
	      return {
	        hours,
	        minutes,
	        seconds
	      };
	    },
	    timeNumToDoubleDigitString(num) {
	      return num > 9 ? String(num) : ('00' + num).slice(-2);
	    },
	    updateWorkingDayTimer() {
	      const dateNow = Math.floor(Date.now() / 1000) * 1000;
	      const timerInfo = {
	        ...window.BXTIMEMAN.DATA.INFO
	      };
	      const dateStart = parseInt(timerInfo.DATE_START) * 1000;
	      const dateWorkingDayStopped = parseInt(timerInfo.DATE_FINISH) * 1000;
	      const timeTimeLeaks = parseInt(timerInfo.TIME_LEAKS) * 1000;
	      const delta = dateNow - dateStart;
	      const deltaPast = dateWorkingDayStopped - dateStart;
	      const deltaPause = dateNow - dateWorkingDayStopped;
	      if (this.workStatus === 'CLOSED') {
	        if (this.canOpen === 'OPEN') {
	          this.timerWorkingDayValue = 0;
	          this.timerPauseValue = 0;
	        } else if (this.canOpen === 'REOPEN') {
	          this.timerWorkingDayValue = deltaPast - timeTimeLeaks;
	          this.timerPauseValue = timeTimeLeaks;
	        }
	      } else if (this.workStatus === 'OPENED') {
	        this.timerWorkingDayValue = delta - timeTimeLeaks;
	        this.timerPauseValue = timeTimeLeaks;
	      } else if (this.workStatus === 'PAUSED') {
	        this.timerWorkingDayValue = deltaPast - timeTimeLeaks;
	        this.timerPauseValue = deltaPause + timeTimeLeaks;
	      } else if (this.workStatus === 'EXPIRED') {
	        this.timerWorkingDayValue = delta - timeTimeLeaks;
	        this.timerPauseValue = timeTimeLeaks;
	      }
	    },
	    getDataId() {
	      return window.BXTIMEMAN.DATA.ID || '';
	    },
	    getWorkStatus() {
	      return window.BXTIMEMAN.DATA.STATE || '';
	    },
	    getReportReq() {
	      return window.BXTIMEMAN.DATA.REPORT_REQ || '';
	    },
	    getCanOpen() {
	      return window.BXTIMEMAN.DATA.CAN_OPEN || '';
	    },
	    getCanOpenAndRelaunch() {
	      return window.BXTIMEMAN.DATA.CAN_OPEN_AND_RELAUNCH || '';
	    },
	    getCanEdit() {
	      return window.BXTIMEMAN.DATA.CAN_EDIT || '';
	    },
	    updateDayState() {
	      this.dataId = this.getDataId();
	      this.workStatus = this.getWorkStatus();
	      this.reportReq = this.getReportReq();
	      this.canOpen = this.getCanOpen();
	      this.canOpenAndRelaunch = this.getCanOpenAndRelaunch();
	      this.canEdit = this.getCanEdit();
	    },
	    // handlers

	    handleTimemanDataRecieved() {
	      this.updateDayState();
	    },
	    handleClickTimerEditorOpener() {
	      window.BXTIMEMAN.WND.ShowEditVue(event.target);
	    },
	    handleClickTimemanOpener() {
	      event.preventDefault();
	      event.stopPropagation();
	      window.BXTIMEMAN.setBindOptions({
	        node: event.target,
	        mode: 'popup',
	        popupOptions: {
	          autoHide: true,
	          angle: false,
	          offsetTop: -40,
	          closeByEsc: true,
	          bindOptions: {
	            forceBindPosition: true,
	            forceTop: true,
	            forceLeft: false
	          },
	          events: {
	            onClose: () => {},
	            onDestroy: () => {}
	          },
	          fixed: true
	        }
	      });
	      window.BXTIMEMAN.OpenVue();
	    }

	    // handlers end
	  },

	  template: `
		<div class="tm-control-panel">
			<div class="tm-control-panel__info">
				<div
					:class="[
						'tm-control-panel__timer',
						'tm-timer',
						this.styleForTimerProps?.status ? ('tm-timer_' + this.styleForTimerProps.status) : null,
					]"
				>
					<div
						v-if="this.styleForTimerProps?.icon"
						class="tm-timer__visual"
					>
						<BIcon
							class="tm-timer__visual-img"
							:name="this.styleForTimerProps.icon"
						/>
					</div>
					<p class="tm-timer__title">{{ this.titleText }}</p>
					<p class="tm-timer__value">
						<span class="tm-timer__value-number tm-timer__value-number_hours">{{
								timeNumToDoubleDigitString(convertMillisecondsToHrMinSec(this.timerWorkingDayValue).hours)
							}}</span>
						<span class="tm-timer__value-number tm-timer__value-number_minutes">{{
								timeNumToDoubleDigitString(convertMillisecondsToHrMinSec(this.timerWorkingDayValue).minutes)
							}}</span>
						<span class="tm-timer__value-number tm-timer__value-number_seconds">{{
								timeNumToDoubleDigitString(convertMillisecondsToHrMinSec(this.timerWorkingDayValue).seconds)
							}}</span>
						<button
							v-if="isEditingAvailable"
							class="tm-timer__editor-opener"
							@click="handleClickTimerEditorOpener"
						>
							<BIcon
								class="tm-timer__editor-opener-img"
								:size="16"
								:name="Outline.EDIT_L"
							/>
						</button>
					</p>
				</div>
				<div class="tm-control-panel__widget-opener-container">
					<!-- span instead of button is used to collapse element width when text is wrapped on multiple lines -->
					<span
						class="tm-control-panel__widget-opener"
						@click="this.handleClickTimemanOpener"
					>{{ Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_OPEN_PLAN') }}</span>
				</div>
			</div>
			<div
				v-if="Boolean(this.timerPauseValue)"
				class="tm-control-panel__info tm-control-panel__info_pause"
			>
				<div
					:class="[
						'tm-control-panel__timer',
						'tm-control-panel__timer_pause',
						'tm-timer',
						this.styleForTimerProps?.status ? ('tm-timer_' + this.styleForTimerProps.status) : null,
					]"
				>
					<p class="tm-timer__title">{{ Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_NOTE_PAUSE_LENGTH') }}</p>
					<p class="tm-timer__value">
						<span class="tm-timer__value-number tm-timer__value-number_hours">{{
								timeNumToDoubleDigitString(convertMillisecondsToHrMinSec(this.timerPauseValue).hours)
							}}</span>
						<span class="tm-timer__value-number tm-timer__value-number_minutes">{{
								timeNumToDoubleDigitString(convertMillisecondsToHrMinSec(this.timerPauseValue).minutes)
							}}</span>
						<span class="tm-timer__value-number tm-timer__value-number_seconds">{{
								timeNumToDoubleDigitString(convertMillisecondsToHrMinSec(this.timerPauseValue).seconds)
							}}</span>
					</p>
				</div>
			</div>
			<ul class="tm-control-panel__actions-list">
				<li
					v-for="action in this.actions"
					:key="action.text"
					class="tm-control-panel__actions-item"
				>
					<UiButton
						class="tm-control-panel__action"
						:wide="true"
						:size="action.size"
						:left-icon="action.icon"
						:text="action.text"
						:disabled="action.isDisabled"
						:loading="action.isLoading"
						:style="action.style || null"
						@click="action.onClick"
					/>
				</li>
			</ul>
		</div>
	`
	};

	let _ = t => t,
	  _t,
	  _t2;
	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	var _timemanInstantContainerNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("timemanInstantContainerNode");
	var _mountApplication = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mountApplication");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _updateState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateState");
	class WorkStatusControlPanel {
	  constructor() {
	    Object.defineProperty(this, _updateState, {
	      value: _updateState2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _mountApplication, {
	      value: _mountApplication2
	    });
	    Object.defineProperty(this, _data, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _timemanInstantContainerNode, {
	      writable: true,
	      value: void 0
	    });
	    const settings = main_core.Extension.getSettings('timeman.work-status-control-panel');
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].workReport = settings.get('workReport');
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].info = settings.get('info');
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].siteId = settings.get('siteId');
	    babelHelpers.classPrivateFieldLooseBase(this, _timemanInstantContainerNode)[_timemanInstantContainerNode] = main_core.Tag.render(_t || (_t = _`
			<div class="timeman-instant-container"></div>
		`));
	    main_core_events.EventEmitter.subscribe('onTimemanInit', babelHelpers.classPrivateFieldLooseBase(this, _init)[_init].bind(this));
	    main_core_events.EventEmitter.subscribe('onTimeManDataRecieved', babelHelpers.classPrivateFieldLooseBase(this, _updateState)[_updateState].bind(this));
	    window.BX.timeman('bx_tm', babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].info, babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].siteId);
	  }
	  renderWorkStatusControlPanel() {
	    event.stopPropagation();
	    babelHelpers.classPrivateFieldLooseBase(this, _mountApplication)[_mountApplication](babelHelpers.classPrivateFieldLooseBase(this, _timemanInstantContainerNode)[_timemanInstantContainerNode]);
	    return main_core.Tag.render(_t2 || (_t2 = _`
			${0}
		`), babelHelpers.classPrivateFieldLooseBase(this, _timemanInstantContainerNode)[_timemanInstantContainerNode]);
	  }
	}
	function _mountApplication2(container) {
	  const application = ui_vue3.BitrixVue.createApp(App, {});
	  application.mount(container);
	}
	function _init2() {
	  window.BXTIMEMAN.initFormWeekly(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].workReport);
	}
	function _updateState2(baseEvent) {
	  const [data] = baseEvent.getCompatData();
	  babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].info = data;
	}

	exports.WorkStatusControlPanel = WorkStatusControlPanel;

}((this.BX.Timeman = this.BX.Timeman || {}),BX.Vue3,BX,BX,BX,BX,BX,BX,BX,BX,BX,BX,BX.Event,BX.UI.IconSet,BX.Vue3.Components));
//# sourceMappingURL=work-status-control-panel.bundle.js.map
