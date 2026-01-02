/* eslint-disable */
this.BX = this.BX || {};
(function (exports,booking_component_mixin_locMixin,main_loader,booking_component_emptyFilterResultsPopup,booking_provider_service_mainPageService,booking_provider_service_dictionaryService,booking_provider_service_calendarService,booking_lib_filterResultNavigator,ui_counterpanel,ui_cnt,main_core_events,booking_lib_drag,ui_vue3_components_richLoc,ui_notificationManager,booking_provider_service_bookingActionsService,booking_component_loader,ui_vue3_directives_hint,booking_lib_mousePosition,booking_component_timeSelector,booking_component_notePopup,ui_iconSet_api_core,ui_iconSet_animated,booking_component_counter,booking_lib_checkBookingIntersection,booking_lib_grid,booking_lib_inInterval,booking_lib_range,booking_core,ui_datePicker,booking_component_counterFloating,booking_lib_isRealId,ui_dialogs_messagebox,booking_component_actionsPopup,booking_component_booking,booking_lib_dealHelper,booking_model_bookings,booking_model_clients,booking_provider_service_waitListService,booking_lib_removeBooking,booking_lib_removeWaitListItem,ui_label,booking_lib_currencyFormat,booking_component_statisticsPopup,ui_hint,booking_lib_removeResource,ui_iconSet_actions,booking_lib_sidePanelInstance,booking_resourceCreationWizard,booking_provider_service_resourceDialogService,booking_lib_resources,booking_lib_resourcesDateCache,ui_iconSet_api_vue,ui_iconSet_main,booking_provider_service_optionService,booking_lib_helpDesk,booking_lib_busySlots,ui_entitySelector,booking_lib_limit,booking_provider_service_bookingService,booking_provider_service_clientService,ui_ears,main_date,booking_lib_duration,booking_component_clientPopup,booking_lib_analytics,ui_autoLaunch,ui_vue3_vuex,main_core,ui_bannerDispatcher,booking_lib_resolvable,booking_lib_ahaMoments,main_popup,booking_component_popup,booking_component_helpDeskLoc,ui_vue3,booking_const,booking_component_button) {
	'use strict';

	const cellHeight = 50;
	const cellHeightProperty = '--booking-off-hours-cell-height';
	const classCollapse = '--booking-booking-collapse';
	const classExpand = '--booking-booking-expand';
	var _animation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animation");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _content = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	var _gridWrap = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("gridWrap");
	var _fromHour = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fromHour");
	var _toHour = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toHour");
	class ExpandOffHours {
	  constructor() {
	    Object.defineProperty(this, _toHour, {
	      get: _get_toHour,
	      set: void 0
	    });
	    Object.defineProperty(this, _fromHour, {
	      get: _get_fromHour,
	      set: void 0
	    });
	    Object.defineProperty(this, _gridWrap, {
	      get: _get_gridWrap,
	      set: void 0
	    });
	    Object.defineProperty(this, _content, {
	      get: _get_content,
	      set: void 0
	    });
	    Object.defineProperty(this, _container, {
	      get: _get_container,
	      set: void 0
	    });
	    Object.defineProperty(this, _animation, {
	      writable: true,
	      value: void 0
	    });
	  }
	  expand({
	    keepScroll
	  }) {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], classCollapse);
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], classExpand);
	    this.animate(0, cellHeight, keepScroll);
	  }
	  collapse() {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], classExpand);
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], classCollapse);
	    this.animate(cellHeight, 0, true);
	  }
	  animate(fromHeight, toHeight, keepScroll = false) {
	    var _babelHelpers$classPr;
	    const savedScrollTop = babelHelpers.classPrivateFieldLooseBase(this, _gridWrap)[_gridWrap].scrollTop;
	    const savedScrollHeight = babelHelpers.classPrivateFieldLooseBase(this, _gridWrap)[_gridWrap].scrollHeight;
	    const topCellsCoefficient = babelHelpers.classPrivateFieldLooseBase(this, _fromHour)[_fromHour] / (24 - (babelHelpers.classPrivateFieldLooseBase(this, _toHour)[_toHour] - babelHelpers.classPrivateFieldLooseBase(this, _fromHour)[_fromHour]));
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _animation)[_animation]) == null ? void 0 : _babelHelpers$classPr.stop();
	    babelHelpers.classPrivateFieldLooseBase(this, _animation)[_animation] = new BX.easing({
	      duration: 200,
	      start: {
	        height: fromHeight
	      },
	      finish: {
	        height: toHeight
	      },
	      step: ({
	        height
	      }) => {
	        main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _content)[_content], cellHeightProperty, `calc(var(--zoom) * ${height}px)`);
	        if (keepScroll) {
	          const heightChange = babelHelpers.classPrivateFieldLooseBase(this, _gridWrap)[_gridWrap].scrollHeight - savedScrollHeight;
	          babelHelpers.classPrivateFieldLooseBase(this, _gridWrap)[_gridWrap].scrollTop = savedScrollTop + heightChange * topCellsCoefficient;
	        }
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _animation)[_animation].animate();
	  }
	  setExpanded(isExpanded) {
	    const savedScrollTop = babelHelpers.classPrivateFieldLooseBase(this, _gridWrap)[_gridWrap].scrollTop;
	    const savedScrollHeight = babelHelpers.classPrivateFieldLooseBase(this, _gridWrap)[_gridWrap].scrollHeight;
	    const topCellsCoefficient = babelHelpers.classPrivateFieldLooseBase(this, _fromHour)[_fromHour] / (24 - (babelHelpers.classPrivateFieldLooseBase(this, _toHour)[_toHour] - babelHelpers.classPrivateFieldLooseBase(this, _fromHour)[_fromHour]));
	    const height = isExpanded ? cellHeight : 0;
	    const className = isExpanded ? classExpand : classCollapse;
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], [classCollapse, classExpand]);
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], className);
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _content)[_content], cellHeightProperty, `calc(var(--zoom) * ${height}px)`);
	    const heightChange = babelHelpers.classPrivateFieldLooseBase(this, _gridWrap)[_gridWrap].scrollHeight - savedScrollHeight;
	    babelHelpers.classPrivateFieldLooseBase(this, _gridWrap)[_gridWrap].scrollTop = savedScrollTop + heightChange * topCellsCoefficient;
	    void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setOffHoursExpanded`, isExpanded);
	  }
	}
	function _get_container() {
	  return booking_core.Core.getParams().container;
	}
	function _get_content() {
	  return BX('booking-content');
	}
	function _get_gridWrap() {
	  return BX('booking-booking-grid-wrap');
	}
	function _get_fromHour() {
	  return booking_core.Core.getStore().getters['interface/fromHour'];
	}
	function _get_toHour() {
	  return booking_core.Core.getStore().getters['interface/toHour'];
	}
	const expandOffHours = new ExpandOffHours();

	const FilterPreset = Object.freeze({
	  CreatedByMe: 'booking-filter-preset-created-by-me'
	});
	const FilterField = Object.freeze({
	  CreatedBy: 'CREATED_BY',
	  Contact: 'CONTACT',
	  Company: 'COMPANY',
	  Resource: 'RESOURCE',
	  ResourceLabel: 'RESOURCE_label',
	  Confirmed: 'CONFIRMED',
	  RequireAttention: 'REQUIRE_ATTENTION'
	});
	const RequireAttention = Object.freeze({
	  Delayed: 'D',
	  AwaitConfirmation: 'AC'
	});
	const Filter = {
	  emits: ['apply', 'clear'],
	  props: {
	    filterId: {
	      type: String,
	      required: true
	    }
	  },
	  data() {
	    return {
	      filter: null
	    };
	  },
	  created() {
	    this.filter = BX.Main.filterManager.getById(this.filterId);
	    main_core_events.EventEmitter.subscribe('BX.Main.Filter:beforeApply', this.onBeforeApply);
	  },
	  methods: {
	    onBeforeApply(event) {
	      const [filterId] = event.getData();
	      if (filterId !== this.filterId) {
	        return;
	      }
	      if (this.isFilterEmpty()) {
	        this.$emit('clear');
	      } else {
	        this.$emit('apply');
	      }
	    },
	    setFields(fields) {
	      const preparedFields = this.filter.getFilterFieldsValues();
	      preparedFields[FilterField.RequireAttention] = fields.REQUIRE_ATTENTION;
	      preparedFields[FilterField.Resource] = fields.RESOURCE;
	      if (FilterField.ResourceLabel in fields) {
	        preparedFields[FilterField.ResourceLabel] = fields.RESOURCE_label;
	      }
	      this.filter.getApi().setFields(preparedFields);
	      this.filter.getApi().apply();
	    },
	    isFilterEmpty() {
	      return Object.keys(this.getFields()).length === 0;
	    },
	    getFields() {
	      const booleanFields = [FilterField.Confirmed, FilterField.Delayed];
	      const arrayFields = [FilterField.Company, FilterField.Contact, FilterField.CreatedBy, FilterField.Resource];
	      const stringFields = [FilterField.RequireAttention];
	      const filterFields = this.filter.getFilterFieldsValues();
	      const fields = booleanFields.filter(field => ['Y', 'N'].includes(filterFields[field])).reduce((acc, field) => ({
	        ...acc,
	        [field]: filterFields[field]
	      }), {});
	      arrayFields.forEach(field => {
	        var _filterFields$field;
	        if (((_filterFields$field = filterFields[field]) == null ? void 0 : _filterFields$field.length) > 0) {
	          fields[field] = filterFields[field];
	        }
	      });
	      stringFields.forEach(field => {
	        var _filterFields$field2;
	        if (((_filterFields$field2 = filterFields[field]) == null ? void 0 : _filterFields$field2.length) > 0) {
	          fields[field] = filterFields[field];
	        }
	      });
	      return fields;
	    }
	  },
	  template: `
		<div></div>
	`
	};

	const CounterItem = Object.freeze({
	  AwaitConfirmation: 'await-confirmation',
	  Delayed: 'delayed'
	});
	const CountersPanel = {
	  emits: ['activeItem'],
	  props: {
	    target: HTMLElement
	  },
	  mounted() {
	    this.addCounterPanel();
	    main_core_events.EventEmitter.subscribe('BX.UI.CounterPanel.Item:activate', this.onActiveItem);
	    main_core_events.EventEmitter.subscribe('BX.UI.CounterPanel.Item:deactivate', this.onActiveItem);
	  },
	  computed: ui_vue3_vuex.mapGetters({
	    counters: 'counters/get'
	  }),
	  methods: {
	    setItem(itemId) {
	      if (this.getActiveItem() === itemId) {
	        return;
	      }
	      Object.values(CounterItem).forEach(id => this.counterPanel.getItemById(id).deactivate());
	      const item = this.counterPanel.getItemById(itemId);
	      item == null ? void 0 : item.activate();
	    },
	    addCounterPanel() {
	      this.counterPanel = new ui_counterpanel.CounterPanel({
	        target: this.target,
	        items: [{
	          id: CounterItem.AwaitConfirmation,
	          title: this.loc('BOOKING_BOOKING_COUNTER_PANEL_AWAIT_CONFIRMATION'),
	          value: this.counters.unConfirmed,
	          color: getFieldName(ui_cnt.CounterColor, ui_cnt.CounterColor.THEME)
	        }, {
	          id: CounterItem.Delayed,
	          title: this.loc('BOOKING_BOOKING_COUNTER_PANEL_DELAYED'),
	          value: this.counters.delayed
	        }]
	      });
	      this.counterPanel.init();
	    },
	    onActiveItem() {
	      this.$emit('activeItem', this.getActiveItem());
	    },
	    getActiveItem() {
	      var _this$counterPanel$ge, _this$counterPanel$ge2;
	      return (_this$counterPanel$ge = (_this$counterPanel$ge2 = this.counterPanel.getItems().find(({
	        isActive
	      }) => isActive)) == null ? void 0 : _this$counterPanel$ge2.id) != null ? _this$counterPanel$ge : null;
	    }
	  },
	  watch: {
	    counters(counters) {
	      this.counterPanel.getItems().forEach(item => {
	        if (item.id === CounterItem.AwaitConfirmation) {
	          item.updateColor(getFieldName(ui_cnt.CounterColor, ui_cnt.CounterColor.DANGER));
	          item.updateValue(counters.unConfirmed);
	        }
	        if (item.id === CounterItem.Delayed) {
	          item.updateColor(getFieldName(ui_cnt.CounterColor, ui_cnt.CounterColor.DANGER));
	          item.updateValue(counters.delayed);
	        }
	      });
	    }
	  },
	  template: `
		<div v-if="false"></div>
	`
	};
	const getFieldName = (obj, field) => Object.entries(obj).find(([, value]) => value === field)[0];

	const Statistics = {
	  props: {
	    value: {
	      type: Number,
	      required: true
	    },
	    valueFormatted: {
	      type: String,
	      required: true
	    },
	    increasedValue: {
	      type: Number,
	      required: true
	    },
	    increasedValueFormatted: {
	      type: String,
	      required: true
	    },
	    popupId: {
	      type: String,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    rows: {
	      type: Array,
	      required: true
	    },
	    button: {
	      type: Object,
	      required: false
	    }
	  },
	  data() {
	    return {
	      isPopupShown: false
	    };
	  },
	  methods: {
	    onMouseEnter() {
	      this.clearTimeouts();
	      this.showTimeout = setTimeout(() => this.showPopup(), 100);
	    },
	    onMouseLeave() {
	      main_core.Event.unbind(document, 'mouseover', this.updateHoverElement);
	      main_core.Event.bind(document, 'mouseover', this.updateHoverElement);
	      this.clearTimeouts();
	      if (!this.button) {
	        this.closePopup();
	        return;
	      }
	      this.closeTimeout = setTimeout(() => {
	        var _this$popupContainer;
	        this.popupContainer = document.getElementById(this.popupId);
	        if (!((_this$popupContainer = this.popupContainer) != null && _this$popupContainer.contains(this.hoverElement)) && !this.$refs.container.contains(this.hoverElement)) {
	          this.closePopup();
	        }
	        if (this.popupContainer) {
	          main_core.Event.unbind(this.popupContainer, 'mouseleave', this.onMouseLeave);
	          main_core.Event.bind(this.popupContainer, 'mouseleave', this.onMouseLeave);
	        }
	      }, 100);
	    },
	    updateHoverElement(event) {
	      this.hoverElement = event.target;
	    },
	    showPopup() {
	      this.clearTimeouts();
	      this.isPopupShown = true;
	    },
	    closePopup() {
	      this.clearTimeouts();
	      this.isPopupShown = false;
	      main_core.Event.unbind(this.popupContainer, 'mouseleave', this.onMouseLeave);
	      main_core.Event.unbind(document, 'mouseover', this.updateHoverElement);
	    },
	    clearTimeouts() {
	      clearTimeout(this.closeTimeout);
	      clearTimeout(this.showTimeout);
	    },
	    close() {
	      this.closePopup();
	      this.$emit('close');
	    }
	  },
	  watch: {
	    value() {
	      var _this$$refs$animation;
	      (_this$$refs$animation = this.$refs.animation) == null ? void 0 : _this$$refs$animation.replaceWith(this.$refs.animation);
	    }
	  },
	  components: {
	    StatisticsPopup: booking_component_statisticsPopup.StatisticsPopup
	  },
	  template: `
		<div class="booking-toolbar-after-title-info-profit-container" ref="container">
			<div
				v-html="valueFormatted"
				class="booking-toolbar-after-title-info-profit"
				@click="showPopup"
				@mouseenter="onMouseEnter"
				@mouseleave="onMouseLeave"
			></div>
			<div
				v-if="increasedValue > 0"
				v-html="increasedValueFormatted"
				class="booking-toolbar-after-title-profit-increased"
				ref="animation"
			></div>
		</div>
		<StatisticsPopup
			v-if="isPopupShown"
			:popupId="popupId"
			:bindElement="$refs.container"
			:title="title"
			:rows="rows"
			:button="button"
			@close="close"
		/>
	`
	};

	const Clients = {
	  data() {
	    return {
	      increasedValue: 0
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      totalNewClientsToday: `${booking_const.Model.Interface}/totalNewClientsToday`,
	      totalClients: `${booking_const.Model.Interface}/totalClients`
	    }),
	    popupId() {
	      return 'booking-booking-after-title-clients-popup';
	    },
	    title() {
	      return this.loc('BOOKING_BOOKING_AFTER_TITLE_CLIENTS_POPUP_TITLE');
	    },
	    rows() {
	      return [{
	        title: this.loc('BOOKING_BOOKING_AFTER_TITLE_CLIENTS_POPUP_TOTAL_CLIENTS_TODAY'),
	        value: `+${this.totalNewClientsToday}`
	      }, {
	        title: this.loc('BOOKING_BOOKING_AFTER_TITLE_CLIENTS_POPUP_TOTAL_CLIENTS'),
	        value: `<div>${this.totalClients}</div>`
	      }];
	    },
	    button() {
	      return {
	        title: this.loc('BOOKING_BOOKING_CLIENTS_LIST'),
	        click: () => BX.SidePanel.Instance.open('/crm/contact/list/')
	      };
	    },
	    clientsProfitFormatted() {
	      return main_core.Loc.getMessagePlural('BOOKING_BOOKING_PLUS_NUM_CLIENTS', this.totalNewClientsToday, {
	        '#NUM#': this.totalNewClientsToday
	      });
	    },
	    increasedValueFormatted() {
	      return main_core.Loc.getMessagePlural('BOOKING_BOOKING_PLUS_NUM_CLIENTS', this.increasedValue, {
	        '#NUM#': this.increasedValue
	      });
	    }
	  },
	  watch: {
	    totalNewClientsToday(newValue, previousValue) {
	      this.increasedValue = newValue - previousValue;
	    }
	  },
	  components: {
	    Statistics
	  },
	  template: `
		<Statistics
			:value="totalNewClientsToday"
			:valueFormatted="clientsProfitFormatted"
			:increasedValue="increasedValue"
			:increasedValueFormatted="increasedValueFormatted"
			:popupId="popupId"
			:title="title"
			:rows="rows"
			:button="button"
		/>
	`
	};

	const Profit = {
	  data() {
	    return {
	      increasedValue: 0
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      moneyStatistics: `${booking_const.Model.Interface}/moneyStatistics`
	    }),
	    popupId() {
	      return 'booking-booking-after-title-profit-popup';
	    },
	    title() {
	      return this.loc('BOOKING_BOOKING_AFTER_TITLE_PROFIT_POPUP_TITLE');
	    },
	    rows() {
	      var _this$moneyStatistics, _this$moneyStatistics2, _this$moneyStatistics3, _this$moneyStatistics4;
	      const otherCurrencies = (_this$moneyStatistics = (_this$moneyStatistics2 = this.moneyStatistics) == null ? void 0 : (_this$moneyStatistics3 = _this$moneyStatistics2.month) == null ? void 0 : (_this$moneyStatistics4 = _this$moneyStatistics3.filter(({
	        currencyId
	      }) => currencyId !== this.baseCurrencyId)) == null ? void 0 : _this$moneyStatistics4.map(({
	        currencyId
	      }) => currencyId)) != null ? _this$moneyStatistics : [];
	      return [this.getTodayRow(this.baseCurrencyId), ...otherCurrencies.map(currencyId => this.getTodayRow(currencyId)), this.getMonthRow(this.baseCurrencyId), ...otherCurrencies.map(currencyId => this.getMonthRow(currencyId))];
	    },
	    todayProfit() {
	      return this.getTodayProfit(this.moneyStatistics);
	    },
	    todayProfitFormatted() {
	      return this.formatTodayProfit(this.todayProfit);
	    },
	    increasedValueFormatted() {
	      return this.formatTodayProfit(this.increasedValue);
	    },
	    baseCurrencyId() {
	      return booking_lib_currencyFormat.currencyFormat.getBaseCurrencyId();
	    }
	  },
	  methods: {
	    getTodayRow(currencyId) {
	      const title = this.loc('BOOKING_BOOKING_AFTER_TITLE_PROFIT_POPUP_TOTAL_TODAY');
	      return {
	        title: currencyId === this.baseCurrencyId ? title : '',
	        value: `+${this.getTodayProfitFormatted(currencyId)}`
	      };
	    },
	    getMonthRow(currencyId) {
	      const title = this.loc('BOOKING_BOOKING_AFTER_TITLE_PROFIT_POPUP_MONTH', {
	        '#MONTH#': main_date.DateTimeFormat.format('f')
	      });
	      return {
	        title: currencyId === this.baseCurrencyId ? title : '',
	        value: `<div>${this.getMonthProfitFormatted(currencyId)}</div>`
	      };
	    },
	    getTodayProfitFormatted(currency) {
	      var _this$moneyStatistics5, _this$moneyStatistics6, _statistics$opportuni;
	      const statistics = (_this$moneyStatistics5 = this.moneyStatistics) == null ? void 0 : (_this$moneyStatistics6 = _this$moneyStatistics5.today) == null ? void 0 : _this$moneyStatistics6.find(({
	        currencyId
	      }) => currencyId === currency);
	      const profit = (_statistics$opportuni = statistics == null ? void 0 : statistics.opportunity) != null ? _statistics$opportuni : 0;
	      return booking_lib_currencyFormat.currencyFormat.format(currency, profit);
	    },
	    getMonthProfitFormatted(currency) {
	      var _this$moneyStatistics7, _this$moneyStatistics8, _statistics$opportuni2;
	      const statistics = (_this$moneyStatistics7 = this.moneyStatistics) == null ? void 0 : (_this$moneyStatistics8 = _this$moneyStatistics7.month) == null ? void 0 : _this$moneyStatistics8.find(({
	        currencyId
	      }) => currencyId === currency);
	      const profit = (_statistics$opportuni2 = statistics == null ? void 0 : statistics.opportunity) != null ? _statistics$opportuni2 : 0;
	      return booking_lib_currencyFormat.currencyFormat.format(currency, profit);
	    },
	    getTodayProfit(statistics) {
	      var _statistics$today, _statistic$opportunit;
	      const statistic = statistics == null ? void 0 : (_statistics$today = statistics.today) == null ? void 0 : _statistics$today.find(({
	        currencyId
	      }) => currencyId === this.baseCurrencyId);
	      return (_statistic$opportunit = statistic == null ? void 0 : statistic.opportunity) != null ? _statistic$opportunit : 0;
	    },
	    formatTodayProfit(profit) {
	      return `+ <span>${booking_lib_currencyFormat.currencyFormat.format(this.baseCurrencyId, profit)}</span>`;
	    }
	  },
	  watch: {
	    moneyStatistics(newValue, previousValue) {
	      this.increasedValue = this.getTodayProfit(newValue) - this.getTodayProfit(previousValue);
	    }
	  },
	  components: {
	    Statistics
	  },
	  template: `
		<Statistics
			:value="todayProfit"
			:valueFormatted="todayProfitFormatted"
			:increasedValue="increasedValue"
			:increasedValueFormatted="increasedValueFormatted"
			:popupId="popupId"
			:title="title"
			:rows="rows"
		/>
	`
	};

	const AfterTitle = {
	  computed: {
	    dateFormatted() {
	      const format = main_date.DateTimeFormat.getFormat('DAY_SHORT_MONTH_FORMAT');
	      return main_date.DateTimeFormat.format(format, Date.now() / 1000);
	    }
	  },
	  components: {
	    Clients,
	    Profit
	  },
	  template: `
		<div class="booking-toolbar-after-title">
			<div class="booking-toolbar-after-title-date" ref="date">
				{{ dateFormatted }}
			</div>
			<div class="booking-toolbar-after-title-info">
				<Clients/>
				<Profit/>
			</div>
		</div>
	`
	};

	const OffHours = {
	  props: {
	    bottom: {
	      type: Boolean,
	      default: false
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      offHoursHover: 'interface/offHoursHover',
	      offHoursExpanded: 'interface/offHoursExpanded',
	      fromHour: 'interface/fromHour',
	      toHour: 'interface/toHour'
	    }),
	    fromFormatted() {
	      if (this.bottom) {
	        return this.formatHour(this.toHour);
	      }
	      return this.formatHour(0);
	    },
	    toFormatted() {
	      if (this.bottom) {
	        return this.formatHour(24);
	      }
	      return this.formatHour(this.fromHour);
	    }
	  },
	  methods: {
	    formatHour(hour) {
	      const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	      const timestamp = new Date().setHours(hour, 0) / 1000;
	      return main_date.DateTimeFormat.format(timeFormat, timestamp);
	    },
	    animateOffHours({
	      keepScroll
	    }) {
	      if (this.offHoursExpanded) {
	        expandOffHours.collapse();
	      } else {
	        expandOffHours.expand({
	          keepScroll
	        });
	      }
	      void this.$store.dispatch('interface/setOffHoursExpanded', !this.offHoursExpanded);
	    }
	  },
	  template: `
		<div
			class="booking-booking-off-hours"
			:class="{'--hover': offHoursHover, '--bottom': bottom, '--top': !bottom}"
			@click="animateOffHours({ keepScroll: bottom })"
			@mouseenter="$store.dispatch('interface/setOffHoursHover', true)"
			@mouseleave="$store.dispatch('interface/setOffHoursHover', false)"
		>
			<div class="booking-booking-off-hours-border"></div>
			<span>{{ fromFormatted }}</span>
			<span>{{ toFormatted }}</span>
		</div>
	`
	};

	const HelpPopup = {
	  name: 'HelpPopup',
	  components: {
	    RichLoc: ui_vue3_components_richLoc.RichLoc,
	    StickyPopup: booking_component_popup.StickyPopup
	  },
	  emits: ['close'],
	  props: {
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    }
	  },
	  computed: {
	    popupId() {
	      return 'booking-quick-filter-help-popup';
	    },
	    config() {
	      return {
	        className: 'booking-quick-filter-help-popup',
	        bindElement: this.bindElement,
	        offsetLeft: this.bindElement.offsetWidth,
	        offsetTop: this.bindElement.offsetHeight,
	        maxWidth: 220
	      };
	    }
	  },
	  methods: {
	    closePopup() {
	      this.$emit('close');
	    }
	  },
	  template: `
		<StickyPopup
			:id="popupId"
			:config="config"
			@close="closePopup"
		>
			<div class="booking-quick-filter-help-popup-content">
				<div class="booking-quick-filter-help-popup-icon-container">
					<div class="booking-quick-filter-help-popup-icon"></div>
				</div>
				<div class="booking-quick-filter-help-popup-description">
					<RichLoc :text="loc('BOOKING_QUICK_FILTER_HELP_MSGVER_1')" placeholder="[bold]">
						<template #bold="{ text }">
							<span>{{ text }}</span>
						</template>
					</RichLoc>
				</div>
			</div>
		</StickyPopup>
	`
	};

	const QuickFilter = {
	  props: {
	    hour: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      isHelpPopupShown: false
	    };
	  },
	  computed: {
	    active() {
	      return this.hour in this.$store.getters[`${booking_const.Model.Filter}/quickFilter`].active;
	    },
	    hovered() {
	      return this.hour in this.$store.getters[`${booking_const.Model.Filter}/quickFilter`].hovered;
	    }
	  },
	  methods: {
	    onClick() {
	      this.closeHelpPopup();
	      if (this.active) {
	        void this.$store.dispatch(`${booking_const.Model.Filter}/deactivateQuickFilter`, this.hour);
	      } else {
	        void this.$store.dispatch(`${booking_const.Model.Filter}/activateQuickFilter`, this.hour);
	      }
	    },
	    hover() {
	      this.helpPopupTimeout = setTimeout(() => this.showHelpPopup(), 1000);
	      void this.$store.dispatch(`${booking_const.Model.Filter}/hoverQuickFilter`, this.hour);
	    },
	    flee() {
	      this.closeHelpPopup();
	      void this.$store.dispatch(`${booking_const.Model.Filter}/fleeQuickFilter`, this.hour);
	    },
	    showHelpPopup() {
	      this.isHelpPopupShown = true;
	    },
	    closeHelpPopup() {
	      clearTimeout(this.helpPopupTimeout);
	      this.isHelpPopupShown = false;
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon,
	    HelpPopup
	  },
	  template: `
		<div
			class="booking-booking-quick-filter-container"
			:class="{'--hover': hovered || active, '--active': active}"
		>
			<div
				class="booking-booking-quick-filter"
				@mouseenter="hover"
				@mouseleave="flee"
				@click="onClick"
			>
				<Icon :name="active ? IconSet.CROSS_25 : IconSet.FUNNEL"/>
			</div>
			<HelpPopup
				v-if="isHelpPopupShown"
				:bindElement="$el"
				@close="closeHelpPopup"
			/>
		</div>
	`
	};

	const LeftPanel = {
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      offHoursHover: 'interface/offHoursHover',
	      offHoursExpanded: 'interface/offHoursExpanded',
	      fromHour: 'interface/fromHour',
	      toHour: 'interface/toHour'
	    }),
	    panelHours() {
	      const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	      const lastHour = this.offHoursExpanded ? 24 : this.toHour;
	      return booking_lib_range.range(0, 24).map(hour => {
	        const timestamp = new Date().setHours(hour, 0) / 1000;
	        return {
	          value: hour,
	          formatted: main_date.DateTimeFormat.format(timeFormat, timestamp),
	          offHours: hour < this.fromHour || hour >= this.toHour,
	          last: hour === lastHour
	        };
	      });
	    }
	  },
	  components: {
	    OffHours,
	    QuickFilter
	  },
	  template: `
		<div class="booking-booking-grid-left-panel-container">
			<div class="booking-booking-grid-left-panel">
				<OffHours/>
				<OffHours :bottom="true"/>
				<template v-for="hour of panelHours" :key="hour.value">
					<div
						v-if="hour.last"
						class="booking-booking-grid-left-panel-time-text"
					>
						{{ hour.formatted }}
					</div>
					<div
						v-if="hour.value !== 24"
						class="booking-booking-grid-left-panel-time"
						:class="{'--off-hours': hour.offHours}"
					>
						<div class="booking-booking-grid-left-panel-time-text">
							{{ hour.formatted }}
						</div>
						<QuickFilter :hour="hour.value"/>
					</div>
				</template>
			</div>
		</div>
	`
	};

	const NowLine = {
	  data() {
	    return {
	      visible: true
	    };
	  },
	  mounted() {
	    this.updateNowLine();
	    setInterval(() => this.updateNowLine(), 1000);
	  },
	  computed: ui_vue3_vuex.mapGetters({
	    zoom: `${booking_const.Model.Interface}/zoom`,
	    selectedDateTs: `${booking_const.Model.Interface}/selectedDateTs`,
	    offHoursExpanded: `${booking_const.Model.Interface}/offHoursExpanded`,
	    fromHour: `${booking_const.Model.Interface}/fromHour`,
	    toHour: `${booking_const.Model.Interface}/toHour`,
	    offset: `${booking_const.Model.Interface}/offset`
	  }),
	  methods: {
	    setVisible(visible) {
	      this.visible = visible;
	      this.updateNowLine();
	    },
	    updateNowLine() {
	      const now = new Date(Date.now() + this.offset);
	      const hourHeight = 50 * this.zoom;
	      const fromMinutes = this.fromHour * 60;
	      const nowMinutes = now.getHours() * 60 + now.getMinutes();
	      const toHour = this.offHoursExpanded ? 24 : this.toHour;
	      const toMinutes = Math.min(toHour * 60 + 21, nowMinutes);
	      const top = (toMinutes - fromMinutes) * (hourHeight / 60);
	      main_core.Dom.style(this.$refs.nowLine, 'top', `${top}px`);
	      const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	      const timeFormatted = main_date.DateTimeFormat.format(timeFormat, now.getTime() / 1000);
	      if (timeFormatted !== this.$refs.nowText.innerText) {
	        this.$refs.nowText.innerText = timeFormatted;
	      }
	      const date = new Date(this.selectedDateTs + this.offset);
	      const visible = this.visible && Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()) === Date.UTC(now.getFullYear(), now.getMonth(), now.getDate());
	      main_core.Dom.style(this.$refs.nowLine, 'display', visible ? '' : 'none');
	    }
	  },
	  watch: {
	    selectedDateTs() {
	      this.updateNowLine();
	    },
	    zoom() {
	      this.updateNowLine();
	    },
	    offHoursExpanded(offHoursExpanded) {
	      const now = new Date();
	      const nowMinutes = now.getHours() * 60 + now.getMinutes();
	      if (nowMinutes < this.toHour * 60) {
	        return;
	      }
	      this.setVisible(!offHoursExpanded);
	      setTimeout(() => this.setVisible(true), 200);
	    }
	  },
	  template: `
		<div class="booking-booking-grid-now-line" ref="nowLine">
			<div class="booking-booking-grid-now-line-text" ref="nowText"></div>
		</div>
	`
	};

	// @vue/component
	const BookingClient = {
	  name: 'BookingActionsPopupClient',
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  emits: ['freeze', 'unfreeze'],
	  computed: {
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
	    }
	  },
	  methods: {
	    async addClients({
	      clients
	    }) {
	      await booking_provider_service_bookingService.bookingService.update({
	        id: this.booking.id,
	        clients
	      });
	    },
	    async updateClients({
	      clients
	    }) {
	      await booking_provider_service_bookingService.bookingService.update({
	        id: this.booking.id,
	        clients
	      });
	    },
	    async updateNote({
	      note
	    }) {
	      await booking_provider_service_bookingService.bookingService.update({
	        id: this.booking.id,
	        note
	      });
	    }
	  },
	  components: {
	    Client: booking_component_actionsPopup.Client
	  },
	  template: `
		<Client
			:id="bookingId"
			:clients="booking.clients"
			:primaryClientData="booking.primaryClient"
			:note="booking.note"
			:dataId="bookingId"
			:dataAttributes="{
				'data-booking-id': bookingId,
			}"
			dataElementPrefix="booking"
			@freeze="$emit('freeze')"
			@unfreeze="$emit('unfreeze')"
			@addClients="addClients"
			@updateClients="updateClients"
			@updateNote="updateNote"
		/>
	`
	};

	// @vue/component
	const BookingDeal = {
	  name: 'BookingActionsPopupDeal',
	  components: {
	    Deal: booking_component_actionsPopup.Deal
	  },
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  emits: ['freeze', 'unfreeze'],
	  setup(props) {
	    const dealHelper = new booking_lib_dealHelper.BookingDealHelper(props.bookingId);
	    return {
	      dealHelper
	    };
	  },
	  computed: {
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    },
	    deal() {
	      var _this$booking$externa, _this$booking$externa2;
	      return (_this$booking$externa = (_this$booking$externa2 = this.booking.externalData) == null ? void 0 : _this$booking$externa2.find(data => data.entityTypeId === booking_const.CrmEntity.Deal)) != null ? _this$booking$externa : null;
	    }
	  },
	  template: `
		<Deal
			:deal="deal"
			:dealHelper="dealHelper"
			:dataId="booking.id"
			:dataAttributes="{
				'data-booking-id': bookingId,
			}"
			dataElementPrefix="booking"
			@freeze="$emit('freeze')"
			@unfreeze="$emit('unfreeze')"
		/>
	`
	};

	const BookingDocument = {
	  name: 'BookingActionsPopupDocument',
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  data() {
	    return {
	      isLoading: true
	    };
	  },
	  async mounted() {
	    await booking_provider_service_bookingActionsService.bookingActionsService.getDocData();
	    this.isLoading = false;
	  },
	  components: {
	    Document: booking_component_actionsPopup.Document
	  },
	  template: `
		<Document
			:id="bookingId"
			:loading="isLoading"
			disabled
		/>
	`
	};

	// @vue/component
	const BookingExtraResourcesInfo = {
	  name: 'BookingExtraResourcesInfo',
	  components: {
	    ExtraResourcesInfo: booking_component_actionsPopup.ExtraResourcesInfo
	  },
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  emits: ['freeze', 'unfreeze'],
	  template: `
		<ExtraResourcesInfo
			:id="bookingId"
			@open="$emit('freeze')"
			@close="$emit('unfreeze')"
			@freeze="$emit('freeze')"
			@unfreeze="$emit('unfreeze')"
		/>
	`
	};

	const BookingMessage = {
	  name: 'BookingActionsPopupMessage',
	  emits: ['freeze', 'unfreeze'],
	  props: {
	    bookingId: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      isLoading: true,
	      isPrimaryClientIdUpdated: false
	    };
	  },
	  mounted() {
	    void this.fetchMessageData();
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      isCurrentSenderAvailable: `${booking_const.Model.Interface}/isCurrentSenderAvailable`
	    }),
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
	    },
	    client() {
	      const clientData = this.booking.primaryClient;
	      return clientData ? this.$store.getters['clients/getByClientData'](clientData) : null;
	    },
	    clientId() {
	      var _this$booking$primary;
	      return (_this$booking$primary = this.booking.primaryClient) == null ? void 0 : _this$booking$primary.id;
	    },
	    updatedAt() {
	      return this.booking.updatedAt;
	    }
	  },
	  watch: {
	    clientId() {
	      this.isPrimaryClientIdUpdated = true;
	    },
	    updatedAt() {
	      if (this.isPrimaryClientIdUpdated && this.isCurrentSenderAvailable) {
	        void this.fetchMessageData();
	        this.isPrimaryClientIdUpdated = false;
	      }
	    }
	  },
	  methods: {
	    async sendMessage({
	      notificationType
	    }) {
	      try {
	        await booking_provider_service_bookingActionsService.bookingActionsService.sendMessage(this.bookingId, notificationType);
	        void this.fetchMessageData();
	      } catch (result) {
	        if (main_core.Type.isArrayFilled(result.errors)) {
	          ui_notificationManager.Notifier.notify({
	            id: 'booking-message-send-error',
	            text: result.errors[0].message
	          });
	        }
	      }
	    },
	    async fetchMessageData() {
	      this.isLoading = true;
	      await booking_provider_service_bookingActionsService.bookingActionsService.getMessageData(this.bookingId);
	      this.isLoading = false;
	    }
	  },
	  components: {
	    Message: booking_component_actionsPopup.Message
	  },
	  template: `
		<Message
			:id="bookingId"
			:clientData="booking.primaryClient"
			:loading="isLoading"
			:dataId="bookingId"
			dataElementPrefix="booking"
			@open="$emit('freeze')"
			@close="$emit('unfreeze')"
			@updateNotificationType="sendMessage"
		/>
	`
	};

	const BookingConfirmation = {
	  emits: ['freeze', 'unfreeze'],
	  name: 'BookingActionsPopupConfirmation',
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  computed: {
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    }
	  },
	  methods: {
	    updateConfirmationStatus({
	      isConfirmed
	    }) {
	      void booking_provider_service_bookingService.bookingService.update({
	        id: this.booking.id,
	        isConfirmed
	      });
	    }
	  },
	  components: {
	    Confirmation: booking_component_actionsPopup.Confirmation
	  },
	  template: `
		<Confirmation
			:id="bookingId"
			:isConfirmed="booking.isConfirmed"
			:counters="booking.counters"
			:dataId="booking.id"
			dataElementPrefix="booking"
			@open="$emit('freeze')"
			@close="$emit('unfreeze')"
			@updateConfirmationStatus="updateConfirmationStatus"
		/>
	`
	};

	const BookingVisit = {
	  emits: ['freeze', 'unfreeze'],
	  name: 'BookingActionsPopupVisit',
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  computed: {
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    }
	  },
	  methods: {
	    updateVisitStatus({
	      visitStatus
	    }) {
	      void booking_provider_service_bookingService.bookingService.update({
	        id: this.booking.id,
	        visitStatus
	      });
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon,
	    Loader: booking_component_loader.Loader,
	    Visit: booking_component_actionsPopup.Visit
	  },
	  template: `
		<Visit
			:id="booking.id"
			:visitStatus="booking.visitStatus"
			:dataId="booking.id"
			dataElementPrefix="booking"
			:hasClients="booking.clients.length > 0"
			@freeze="$emit('freeze')"
			@unfreeze="$emit('unfreeze')"
			@update:visitStatus="updateVisitStatus"
		/>
	`
	};

	// @vue/component
	const Overbooking = {
	  name: 'BookingActionsPopupOverbooking',
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    },
	    disabled: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['close'],
	  setup(props) {
	    const plusIcon = ui_iconSet_api_vue.Set.PLUS_20;
	    const plusIconSize = 20;
	    const plusIconColor = ui_vue3.computed(() => {
	      return props.disabled ? 'var(--ui-color-palette-gray-20)' : 'var(--ui-color-palette-gray-60)';
	    });
	    return {
	      plusIcon,
	      plusIconSize,
	      plusIconColor
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      getBookingById: `${booking_const.Model.Bookings}/getById`,
	      dictionary: `${booking_const.Model.Dictionary}/getBookingVisitStatuses`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`,
	      timezone: `${booking_const.Model.Interface}/timezone`,
	      embedItems: `${booking_const.Model.Interface}/embedItems`
	    }),
	    booking() {
	      return this.getBookingById(this.bookingId);
	    },
	    hasOverbookingHint() {
	      if (!this.disabled) {
	        return undefined;
	      }
	      return {
	        text: this.loc('BB_ACTIONS_POPUP_OVERBOOKING_DISABLED_HINT')
	      };
	    },
	    clients() {
	      const clients = this.embedItems.filter(item => {
	        return item.entityTypeId === 'CONTACT' || item.entityTypeId === 'COMPANY';
	      });
	      return clients.map(item => {
	        return {
	          id: item.value,
	          type: {
	            code: item.entityTypeId,
	            module: item.moduleId
	          }
	        };
	      });
	    }
	  },
	  methods: {
	    async addOverbooking() {
	      if (this.disabled) {
	        return;
	      }
	      if (!this.isFeatureEnabled) {
	        booking_lib_limit.limit.show();
	        return;
	      }
	      const overbooking = {
	        ...this.booking,
	        id: main_core.Text.getRandom(10),
	        clients: this.clients,
	        counter: 0,
	        counters: [],
	        createdAt: Date.now(),
	        externalData: this.embedItems,
	        isConfirmed: false,
	        name: null,
	        note: null,
	        resourcesIds: [this.resourceId],
	        primaryClient: undefined,
	        rrule: null,
	        timezoneFrom: this.timezone,
	        timezoneTo: this.timezone,
	        updatedAt: Date.now(),
	        visitStatus: this.dictionary.Unknown
	      };
	      delete overbooking.name;
	      const result = await booking_provider_service_bookingService.bookingService.add(overbooking);
	      if (result.success && result.booking) {
	        booking_lib_analytics.BookingAnalytics.sendAddBooking({
	          isOverbooking: true
	        });
	      }
	      this.$emit('close');
	    }
	  },
	  template: `
		<div
			v-hint="hasOverbookingHint"
			class="booking-actions-popup__item-overbooking-button"
			:class="{'--disabled': disabled}"
			role="button"
			tabindex="0"
			@click="addOverbooking"
		>
			<Icon :name="plusIcon" :size="plusIconSize" :color="plusIconColor"/>
			<div class="booking-actions-popup__item-overbooking-label">
				{{ loc('BB_ACTIONS_POPUP_OVERBOOKING_LABEL') }}
			</div>
		</div>
	`
	};

	// @vue/component
	const Waitlist = {
	  name: 'BookingActionsPopupWaitlist',
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      getBookingById: `${booking_const.Model.Bookings}/getById`
	    }),
	    clockIcon() {
	      return ui_iconSet_api_vue.Set.BLACK_CLOCK;
	    },
	    disabled() {
	      return !booking_lib_isRealId.isRealId(this.bookingId);
	    }
	  },
	  methods: {
	    async toWaitList() {
	      if (this.disabled) {
	        return;
	      }
	      const bookingId = this.bookingId;
	      const booking = this.getBookingById(bookingId);
	      await this.$store.dispatch(`${booking_const.Model.Interface}/addDeletingBooking`, bookingId);
	      const result = await booking_provider_service_waitListService.waitListService.createFromBooking(bookingId, {
	        id: `tmp-id-${Date.now()}-${main_core.Text.getRandom(4)}`,
	        clients: booking.clients,
	        primaryClient: booking.primaryClient,
	        externalData: booking.externalData,
	        createdAt: Date.now(),
	        updatedAt: Date.now()
	      });
	      if (result.success && result.waitListItem) {
	        booking_lib_analytics.BookingAnalytics.sendAddWaitListItem();
	      }
	    }
	  },
	  template: `
		<div
			class="booking--booking-actions-popup__item-waitlist-btn --end"
			:class="{'--disabled': disabled}"
			@click="toWaitList">
			<Icon :name="clockIcon" :size="20" color="var(--ui-color-palette-gray-60)"/>
			<div class="booking-actions-popup__item-waitlist-label">
				{{ loc('BB_ACTIONS_POPUP_OVERBOOKING_LIST') }}
			</div>
		</div>
	`
	};

	function BookingRemoveBtn(props, {
	  emit
	}) {
	  const bookingId = props.bookingId;
	  const removeBooking = () => {
	    emit('close');
	    new booking_lib_removeBooking.RemoveBooking(bookingId);
	  };
	  return ui_vue3.h(booking_component_actionsPopup.RemoveButton, {
	    dataAttributes: {
	      'data-booking-id': bookingId,
	      'data-element': 'booking-menu-remove-button'
	    },
	    onRemove: removeBooking
	  });
	}
	const bookingRemoveBtnProps = ['bookingId'];
	BookingRemoveBtn.props = bookingRemoveBtnProps;
	BookingRemoveBtn.emits = ['close'];

	const ActionsPopupActionEnum = Object.freeze({
	  client: 'client',
	  confirmation: 'confirmation',
	  deal: 'deal',
	  document: 'document',
	  extraResourcesInfo: 'extraResourcesInfo',
	  fullForm: 'fullForm',
	  message: 'message',
	  visit: 'visit',
	  overbooking: 'overbooking',
	  remove: 'remove',
	  waitList: 'waitList'
	});

	// @vue/component
	const BookingActionsPopup = {
	  name: 'BookingActionsPopup',
	  components: {
	    ActionsPopup: booking_component_actionsPopup.ActionsPopup,
	    Overbooking,
	    Waitlist,
	    BookingRemoveBtn
	  },
	  props: {
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    },
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    },
	    /**
	     * @type ActionsPopupOptions
	     */
	    options: {
	      type: Object,
	      default: null
	    }
	  },
	  emits: ['close'],
	  data() {
	    return {
	      soonTmp: false
	    };
	  },
	  computed: {
	    config() {
	      return {
	        offsetLeft: this.getOffsetLeft(),
	        offsetTop: -200
	      };
	    },
	    contentStructure() {
	      return [{
	        id: ActionsPopupActionEnum.client,
	        props: {
	          bookingId: this.bookingId
	        },
	        component: BookingClient
	      }, {
	        id: ActionsPopupActionEnum.extraResourcesInfo,
	        props: {
	          bookingId: this.bookingId,
	          resourceId: this.resourceId
	        },
	        component: BookingExtraResourcesInfo
	      }, [{
	        id: ActionsPopupActionEnum.deal,
	        props: {
	          bookingId: this.bookingId
	        },
	        component: BookingDeal
	      }, {
	        id: ActionsPopupActionEnum.document,
	        props: {
	          bookingId: this.bookingId
	        },
	        component: BookingDocument
	      }], {
	        id: ActionsPopupActionEnum.message,
	        props: {
	          bookingId: this.bookingId
	        },
	        component: BookingMessage
	      }, {
	        id: ActionsPopupActionEnum.confirmation,
	        props: {
	          bookingId: this.bookingId
	        },
	        component: BookingConfirmation
	      }, {
	        id: ActionsPopupActionEnum.visit,
	        props: {
	          bookingId: this.bookingId
	        },
	        component: BookingVisit
	      }, [{
	        id: ActionsPopupActionEnum.fullForm,
	        props: {
	          bookingId: this.bookingId
	        },
	        component: booking_component_actionsPopup.FullForm
	      }, {
	        id: ActionsPopupActionEnum.info,
	        class: '--shrink',
	        props: {
	          bookingId: this.bookingId
	        },
	        component: booking_component_actionsPopup.Info
	      }]];
	    },
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    }
	  },
	  methods: {
	    getOffsetLeft() {
	      const {
	        left
	      } = this.bindElement.getBoundingClientRect();
	      if (window.innerWidth - left < 325) {
	        return -325;
	      }
	      return this.bindElement.offsetWidth;
	    }
	  },
	  template: `
		<ActionsPopup
			:popupId="bookingId"
			:bindElement="bindElement"
			:contentStructure="contentStructure"
			:popupOptions="config"
			@close="$emit('close')"
		>
			<template #footer>
				<Overbooking
					v-if="!options?.overbooking?.hidden"
					:bookingId
					:resourceId
					:disabled="Boolean(options?.overbooking?.disabled)"
					@close="$emit('close')"
				/>
				<Waitlist v-if="!options?.waitList?.hidden" :bookingId/>
				<BookingRemoveBtn :bookingId @close="$emit('close')"/>
			</template>
		</ActionsPopup>
	`
	};

	const Actions = {
	  name: 'BookingActions',
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    },
	    actionsPopupOptions: {
	      type: Object,
	      default: null
	    }
	  },
	  data() {
	    return {
	      showPopup: false
	    };
	  },
	  mounted() {
	    if (this.isEditingBookingMode && this.editingBookingId === this.bookingId) {
	      this.showPopup = true;
	    }
	  },
	  computed: ui_vue3_vuex.mapGetters({
	    editingBookingId: `${booking_const.Model.Interface}/editingBookingId`,
	    isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`
	  }),
	  methods: {
	    clickHandler() {
	      this.showPopup = true;
	    }
	  },
	  components: {
	    BookingActionsPopup
	  },
	  template: `
		<div 
			ref="node"
			class="booking-booking-booking-actions"
			data-element="booking-booking-actions-button"
			:data-id="bookingId"
			:data-resource-id="resourceId"
			@click="clickHandler"
		>
			<div class="booking-booking-booking-actions-inner">
				<div class="ui-icon-set --chevron-down"></div>
			</div>
		</div>
		<BookingActionsPopup
			v-if="showPopup"
			:bookingId
			:bindElement="this.$refs.node"
			:resourceId
			:options="actionsPopupOptions"
			@close="showPopup = false"
		/>
	`
	};

	const BookingAddClient = {
	  name: 'BookingAddClient',
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    },
	    expired: {
	      type: Boolean,
	      default: false
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      getBookingById: `${booking_const.Model.Bookings}/getById`
	    })
	  },
	  mounted() {
	    if (booking_lib_isRealId.isRealId(this.bookingId)) {
	      booking_lib_ahaMoments.ahaMoments.setBookingForAhaMoment(this.bookingId);
	    }
	    if (booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.AddClient, {
	      bookingId: this.bookingId
	    })) {
	      void this.showAhaMoment();
	    }
	  },
	  methods: {
	    async addClientsToBook(clients) {
	      const booking = this.getBookingById(this.bookingId);
	      await booking_provider_service_bookingService.bookingService.update({
	        id: booking.id,
	        clients
	      });
	    },
	    async showAhaMoment() {
	      var _this$$refs$bookingAd, _this$$refs$bookingAd2;
	      await booking_lib_ahaMoments.ahaMoments.show({
	        id: 'booking-add-client',
	        title: this.loc('BOOKING_AHA_ADD_CLIENT_TITLE'),
	        text: this.loc('BOOKING_AHA_ADD_CLIENT_TEXT_MSGVER_1'),
	        target: (_this$$refs$bookingAd = this.$refs.bookingAddClientBtn) == null ? void 0 : (_this$$refs$bookingAd2 = _this$$refs$bookingAd.$refs) == null ? void 0 : _this$$refs$bookingAd2.button
	      });
	      booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.AddClient);
	    }
	  },
	  components: {
	    AddClient: booking_component_booking.AddClient
	  },
	  template: `
		<AddClient
			:expired="expired"
			:dataAttributes="{
				'data-id': bookingId,
				'data-element': 'booking-add-client-button',
				'data-resource-id': resourceId,
			}"
			buttonClass="booking-booking-booking-add-client"
			ref="bookingAddClientBtn"
			@add="addClientsToBook"
		/>
	`
	};

	const ChangeTimePopup = {
	  name: 'ChangeTimePopup',
	  emits: ['close'],
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    },
	    targetNode: {
	      type: HTMLElement,
	      required: true
	    }
	  },
	  data() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      fromTs: 0,
	      toTs: 0,
	      duration: 0
	    };
	  },
	  created() {
	    this.fromTs = this.booking.dateFromTs;
	    this.toTs = this.booking.dateToTs;
	    this.duration = this.toTs - this.fromTs;
	  },
	  mounted() {
	    main_core.Event.bind(document, 'scroll', this.adjustPosition, true);
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(document, 'scroll', this.adjustPosition, true);
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      selectedDateTs: `${booking_const.Model.Interface}/selectedDateTs`
	    }),
	    popupId() {
	      return `booking-change-time-popup-${this.bookingId}-${this.resourceId}`;
	    },
	    config() {
	      return {
	        className: 'booking-booking-change-time-popup',
	        bindElement: this.targetNode,
	        offsetTop: -10,
	        bindOptions: {
	          forceBindPosition: true,
	          position: 'top'
	        },
	        angle: {
	          offset: this.targetNode.offsetWidth / 2
	        }
	      };
	    },
	    isBusy() {
	      const bookingId = this.bookingId;
	      const bookings = this.bookings.filter(({
	        id,
	        dateToTs,
	        dateFromTs
	      }) => {
	        if (id !== bookingId && this.overbookingMap.has(id)) {
	          var _resourceIntersection;
	          const overbooking = this.overbookingMap.get(id);
	          const resourceIntersections = overbooking.items.find(item => item.resourceId === this.resourceId);
	          const intersections = (resourceIntersections == null ? void 0 : (_resourceIntersection = resourceIntersections.intersections) == null ? void 0 : _resourceIntersection.filter(intersection => {
	            return intersection.id !== bookingId && intersection.dateToTs > this.fromTs && this.toTs > intersection.dateFromTs;
	          })) || [];
	          if (resourceIntersections && intersections.length === 0) {
	            return false;
	          }
	        }
	        return id !== bookingId && dateToTs > this.fromTs && this.toTs > dateFromTs;
	      });
	      return bookings.length > 1;
	    },
	    bookings() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getByDateAndResources`](this.selectedDateTs, this.booking.resourcesIds);
	    },
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
	    },
	    overbookingMap() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/overbookingMap`];
	    }
	  },
	  methods: {
	    adjustPosition() {
	      this.$refs.popup.adjustPosition();
	      this.$refs.timeFrom.adjustMenuPosition();
	      this.$refs.timeTo.adjustMenuPosition();
	    },
	    closePopup() {
	      const tsChanged = this.booking.dateFromTs !== this.fromTs || this.booking.dateToTs !== this.toTs;
	      if (!this.isBusy && tsChanged) {
	        void booking_provider_service_bookingService.bookingService.update({
	          id: this.booking.id,
	          dateFromTs: this.fromTs,
	          dateToTs: this.toTs,
	          timezoneFrom: this.booking.timezoneFrom,
	          timezoneTo: this.booking.timezoneTo
	        });
	      }
	      this.$emit('close');
	    },
	    freeze() {
	      this.$refs.popup.getPopupInstance().setAutoHide(false);
	    },
	    unfreeze() {
	      this.$refs.popup.getPopupInstance().setAutoHide(true);
	    }
	  },
	  watch: {
	    fromTs() {
	      this.toTs = this.fromTs + this.duration;
	    },
	    toTs() {
	      if (this.toTs <= this.fromTs) {
	        this.fromTs = this.toTs - this.duration;
	      }
	      this.duration = this.toTs - this.fromTs;
	    },
	    isBusy() {
	      setTimeout(() => this.adjustPosition(), 0);
	    }
	  },
	  components: {
	    Popup: booking_component_popup.Popup,
	    TimeSelector: booking_component_timeSelector.TimeSelector,
	    Button: booking_component_button.Button
	  },
	  template: `
		<Popup
			:id="popupId"
			:config="config"
			@close="closePopup"
			ref="popup"
		>
			<div class="booking-booking-change-time-popup-content">
				<div class="booking-booking-change-time-popup-main">
					<TimeSelector
						v-model="fromTs"
						:hasError="isBusy"
						data-element="booking-change-time-from"
						:data-ts="fromTs"
						:data-booking-id="bookingId"
						ref="timeFrom"
						@freeze="freeze"
						@unfreeze="unfreeze"
						@enterSave="closePopup"
					/>
					<div class="booking-booking-change-time-popup-separator"></div>
					<TimeSelector
						v-model="toTs"
						:hasError="isBusy"
						:minTs="fromTs"
						data-element="booking-change-time-to"
						:data-ts="toTs"
						:data-booking-id="bookingId"
						ref="timeTo"
						@freeze="freeze"
						@unfreeze="unfreeze"
						@enterSave="closePopup"
					/>
					<Button
						class="booking-booking-change-time-popup-button"
						:size="ButtonIcon.MEDIUM"
						:color="ButtonColor.PRIMARY"
						:icon="ButtonIcon.DONE"
						:disabled="isBusy"
						@click="closePopup"
					/>
				</div>
				<div v-if="isBusy" class="booking-booking-change-time-popup-error">
					{{ loc('BOOKING_BOOKING_TIME_IS_NOT_AVAILABLE') }}
				</div>
			</div>
		</Popup>
	`
	};

	const BookingTime = {
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    },
	    dateFromTs: {
	      type: Number,
	      required: true
	    },
	    dateToTs: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      showPopup: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      offset: `${booking_const.Model.Interface}/offset`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
	    },
	    timeFormatted() {
	      const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	      return this.loc('BOOKING_BOOKING_TIME_RANGE', {
	        '#FROM#': main_date.DateTimeFormat.format(timeFormat, (this.dateFromTs + this.offset) / 1000),
	        '#TO#': main_date.DateTimeFormat.format(timeFormat, (this.dateToTs + this.offset) / 1000)
	      });
	    }
	  },
	  methods: {
	    clickHandler() {
	      if (!this.isFeatureEnabled) {
	        return;
	      }
	      this.showPopup = true;
	    },
	    closePopup() {
	      this.showPopup = false;
	    }
	  },
	  components: {
	    ChangeTimePopup
	  },
	  template: `
		<div
			class="booking-booking-booking-time"
			:class="{'--lock': !isFeatureEnabled}"
			data-element="booking-booking-time"
			:data-booking-id="bookingId"
			:data-resource-id="resourceId"
			:data-from="booking.dateFromTs"
			:data-to="booking.dateToTs"
			ref="time"
			@click="clickHandler"
		>
			{{ timeFormatted }}
		</div>
		<ChangeTimePopup
			v-if="showPopup"
			:bookingId="bookingId"
			:resourceId="resourceId"
			:targetNode="$refs.time"
			@close="closePopup"
		/>
	`
	};

	// @vue/component
	const BookingName = {
	  name: 'BookingName',
	  components: {
	    Name: booking_component_booking.Name
	  },
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    }
	  },
	  computed: {
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    },
	    client() {
	      const clientData = this.booking.primaryClient;
	      return clientData ? this.$store.getters[`${booking_const.Model.Clients}/getByClientData`](clientData) : null;
	    },
	    bookingName() {
	      var _this$client;
	      return ((_this$client = this.client) == null ? void 0 : _this$client.name) || this.booking.name || this.loc('BOOKING_BOOKING_DEFAULT_BOOKING_NAME');
	    }
	  },
	  template: `
		<Name
			:name="bookingName"
			className="booking-booking-booking-name"
			:dataAttributes="{
				'data-element': 'booking-booking-name',
				'data-id': bookingId,
				'data-resource-id':resourceId,
			}"
		/>
	`
	};

	// @vue/component
	const BookingNote = {
	  name: 'BookingNote',
	  components: {
	    Note: booking_component_booking.Note,
	    NotePopup: booking_component_notePopup.NotePopup
	  },
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    bindElement: {
	      type: Function,
	      required: true
	    },
	    visiblePopup: {
	      type: Boolean,
	      default: false
	    }
	  },
	  computed: {
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    }
	  },
	  watch: {
	    visiblePopup(visible) {
	      if (visible) {
	        var _this$$refs$note;
	        (_this$$refs$note = this.$refs.note) == null ? void 0 : _this$$refs$note.showViewPopup();
	      } else {
	        var _this$$refs$note2;
	        (_this$$refs$note2 = this.$refs.note) == null ? void 0 : _this$$refs$note2.closeViewPopup();
	      }
	    }
	  },
	  methods: {
	    async saveBookingNote({
	      note
	    }) {
	      await booking_provider_service_bookingService.bookingService.update({
	        id: this.booking.id,
	        note
	      });
	    }
	  },
	  template: `
		<Note
			ref="note"
			:id="bookingId"
			:note="booking.note"
			:bindElement
			className="booking-booking-booking-note-button"
			:dataId="bookingId"
			dataElementPrefix="booking"
			:dataAttributes="{
				'data-id': bookingId,
				'data-element': 'booking-booking-note-button',
			}"
			@save="saveBookingNote"
		/>
	`
	};

	const BookingProfit = {
	  name: 'BookingProfit',
	  components: {
	    Profit: booking_component_booking.Profit
	  },
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    }
	  },
	  computed: {
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    },
	    deal() {
	      var _this$booking$externa, _this$booking$externa2;
	      return (_this$booking$externa = (_this$booking$externa2 = this.booking.externalData) == null ? void 0 : _this$booking$externa2.find(data => data.entityTypeId === booking_const.CrmEntity.Deal)) != null ? _this$booking$externa : null;
	    }
	  },
	  template: `
		<Profit
			:deal
			:dataAttributes="{
				'data-id': bookingId,
				'data-resource-id': resourceId,
				'data-element': 'booking-booking-profit'
			}"
			className="booking-booking-booking-profit"
		/>
	`
	};

	const BookingCrmButton = {
	  name: 'BookingCrmButton',
	  components: {
	    CrmButton: booking_component_booking.CrmButton
	  },
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup(props) {
	    const dealHelper = new booking_lib_dealHelper.BookingDealHelper(props.bookingId);
	    return {
	      dealHelper
	    };
	  },
	  template: `
		<CrmButton
			:dealHelper
			:dataAttributes="{
				'data-booking-id': bookingId,
				'data-element': 'booking-crm-button'
			}"
		/>
	`
	};

	// @vue/component
	const Counter = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    UiCounter: booking_component_counter.Counter
	  },
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    nowTs: {
	      type: Number,
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Animated: ui_iconSet_api_core.Animated,
	      Main: ui_iconSet_api_core.Main,
	      CounterColor: booking_component_counter.CounterColor,
	      CounterSize: booking_component_counter.CounterSize
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      notificationTypes: `${booking_const.Model.Dictionary}/getNotifications`
	    }),
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    },
	    showClocking() {
	      var _this$booking$message;
	      if (this.showCounter || this.isExpiredBooking || this.hasVisitStatus || this.isNotVisited) {
	        return false;
	      }
	      const notificationTypes = Object.fromEntries(Object.entries(this.notificationTypes).map(([type, {
	        value
	      }]) => [type, value]));
	      const confirmationSent = (_this$booking$message = this.booking.messages) == null ? void 0 : _this$booking$message.some(({
	        notificationType
	      }) => notificationType === notificationTypes.Confirmation);
	      return !this.booking.isConfirmed && confirmationSent;
	    },
	    showConfirmed() {
	      if (this.showCounter || this.isExpiredBooking || this.isNotVisited) {
	        return false;
	      }
	      return this.booking.isConfirmed;
	    },
	    showCounter() {
	      return this.booking.counter > 0;
	    },
	    isExpiredBooking() {
	      return this.nowTs > this.booking.dateToTs;
	    },
	    hasVisitStatus() {
	      return [booking_const.VisitStatus.Visited, booking_const.VisitStatus.NotVisited].includes(this.booking.visitStatus);
	    },
	    isNotVisited() {
	      const started = this.nowTs > this.booking.dateFromTs;
	      const statusUnknown = this.booking.visitStatus === booking_const.VisitStatus.Unknown;
	      const statusNotVisited = this.booking.visitStatus === booking_const.VisitStatus.NotVisited;
	      return started && statusUnknown || statusNotVisited;
	    }
	  },
	  template: `
		<div class="booking--counter">
			<div v-if="showClocking" class="booking-counter-icon --clocking">
				<BIcon :name="Animated.LOADER_CLOCK" :hoverable="false"/>
			</div>
			<div v-else-if="showConfirmed" class="booking-counter-icon --confirmed">
				<BIcon :name="Main.CHECK" :hoverable="false"/>
			</div>
			<UiCounter
				v-else-if="showCounter"
				:value="booking.counter"
				:color="CounterColor.DANGER"
				:size="CounterSize.LARGE"
				border
			/>
		</div>
	`
	};

	const ResizeDirection = Object.freeze({
	  From: -1,
	  None: 0,
	  To: 1
	});
	const minDuration = booking_lib_duration.Duration.getUnitDurations().i * 5;
	const minInitialDuration = booking_lib_duration.Duration.getUnitDurations().i * 15;

	// @vue/component
	const Resize = {
	  name: 'BookingResize',
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    }
	  },
	  setup() {
	    const tooltipTop = null;
	    const tooltipBottom = null;
	    return {
	      tooltipTop,
	      tooltipBottom
	    };
	  },
	  data() {
	    return {
	      resizeDirection: ResizeDirection.None,
	      resizeFromTs: null,
	      resizeToTs: null
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      selectedDateTs: `${booking_const.Model.Interface}/selectedDateTs`,
	      overbookingMap: `${booking_const.Model.Bookings}/overbookingMap`
	    }),
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    },
	    limits() {
	      return {
	        fromTs: new Date(this.selectedDateTs).setHours(0, 0, 0, 0),
	        toTs: new Date(this.selectedDateTs).setHours(24, 0, 0, 0)
	      };
	    },
	    initialHeight() {
	      return booking_lib_grid.grid.calculateHeight(this.booking.dateFromTs, this.booking.dateToTs);
	    },
	    initialDuration() {
	      return Math.max(this.booking.dateToTs - this.booking.dateFromTs, minInitialDuration);
	    },
	    dateFromTsRounded() {
	      return this.roundTimestamp(this.resizeFromTs);
	    },
	    dateToTsRounded() {
	      return this.roundTimestamp(this.resizeToTs);
	    },
	    closestOnFrom() {
	      return this.colliding.reduce((closest, {
	        toTs
	      }) => {
	        return closest < toTs && toTs <= this.booking.dateFromTs ? toTs : closest;
	      }, 0);
	    },
	    closestOnTo() {
	      return this.colliding.reduce((closest, {
	        fromTs
	      }) => {
	        return this.booking.dateToTs <= fromTs && fromTs < closest ? fromTs : closest;
	      }, Infinity);
	    },
	    excludeBookings() {
	      // return when overbooking option disabled
	      // 	return [this.bookingId];
	      const overbookingMap = this.overbookingMap;
	      return booking => {
	        if (booking.id === this.bookingId) {
	          return true;
	        }
	        const overbooking = overbookingMap.get(booking.id);
	        const resourcesIds = this.booking.resourcesIds;
	        return !overbooking || overbooking.items.every(item => !resourcesIds.includes(item.resourceId));
	      };
	    },
	    colliding() {
	      return this.$store.getters[`${booking_const.Model.Interface}/getColliding`](this.booking.resourcesIds, this.excludeBookings);
	    },
	    hasIntersections() {
	      return this.booking.resourcesIds.length > 1;
	    }
	  },
	  methods: {
	    createPopup(options) {
	      return new main_popup.Popup({
	        autoHide: true,
	        cacheable: true,
	        darkMode: true,
	        ...options
	      });
	    },
	    showTooltipTop(options) {
	      if (!this.tooltipTop) {
	        this.tooltipTop = this.createPopup({
	          id: `resize-top-${this.bookingId}-${this.resourceId}`,
	          bindElement: this.$refs.bookingResizeTop,
	          bindOptions: {
	            position: 'bottom'
	          },
	          offsetLeft: this.$refs.bookingResizeTop.offsetWidth / 2,
	          content: this.loc('BOOKING_RESIZE_GRID_LIMIT'),
	          ...options
	        });
	      } else if (options != null && options.content) {
	        this.tooltipTop.setContent(options.content);
	      }
	      this.tooltipTop.show();
	    },
	    showTooltipBottom(options) {
	      if (!this.tooltipBottom) {
	        this.tooltipBottom = this.createPopup({
	          id: `resize-bottom-${this.bookingId}-${this.resourceId}`,
	          bindElement: this.$refs.bookingResizeBottom,
	          bindOptions: {
	            position: 'bottom'
	          },
	          offsetLeft: this.$refs.bookingResizeBottom.offsetWidth / 2,
	          content: this.loc('BOOKING_RESIZE_GRID_LIMIT'),
	          ...options
	        });
	      } else if (options != null && options.content) {
	        this.tooltipBottom.setContent(options.content);
	      }
	      this.tooltipBottom.show();
	    },
	    hideTooltips() {
	      var _this$tooltipTop, _this$tooltipBottom;
	      (_this$tooltipTop = this.tooltipTop) == null ? void 0 : _this$tooltipTop.close == null ? void 0 : _this$tooltipTop.close();
	      (_this$tooltipBottom = this.tooltipBottom) == null ? void 0 : _this$tooltipBottom.close == null ? void 0 : _this$tooltipBottom.close();
	    },
	    onMouseDown(event) {
	      const direction = main_core.Dom.hasClass(event.target, '--from') ? ResizeDirection.From : ResizeDirection.To;
	      void this.startResize(direction);
	    },
	    async startResize(direction = ResizeDirection.To) {
	      main_core.Dom.style(document.body, 'user-select', 'none');
	      main_core.Event.bind(window, 'mouseup', this.endResize);
	      main_core.Event.bind(window, 'pointermove', this.resize);
	      this.resizeDirection = direction;
	      void this.updateIds(this.bookingId, this.resourceId);
	    },
	    resize(event) {
	      if (!this.resizeDirection) {
	        return;
	      }
	      const resizeHeight = this.resizeDirection === ResizeDirection.To ? event.clientY - this.$el.getBoundingClientRect().top : this.$el.getBoundingClientRect().bottom - event.clientY;
	      const duration = resizeHeight * this.initialDuration / this.initialHeight;
	      const newDuration = Math.max(duration, minDuration);
	      if (this.resizeDirection === ResizeDirection.To) {
	        const resizeToTs = this.booking.dateFromTs + newDuration;
	        const toTs = Math.min(resizeToTs, this.limits.toTs);
	        this.manageToLimitNotification(resizeToTs, toTs);
	        this.resizeFromTs = this.booking.dateFromTs;
	        this.resizeToTs = Math.min(toTs, this.closestOnTo);
	      } else {
	        const resizeFromTs = this.booking.dateToTs - newDuration;
	        const fromTs = Math.max(resizeFromTs, this.limits.fromTs);
	        this.manageFromLimitNotification(resizeFromTs, fromTs);
	        this.resizeFromTs = Math.max(fromTs, this.closestOnFrom);
	        this.resizeToTs = this.booking.dateToTs;
	      }
	      this.$emit('update', this.resizeFromTs, this.resizeToTs);
	    },
	    async endResize() {
	      this.resizeBooking();
	      this.hideTooltips();
	      main_core.Dom.style(document.body, 'user-select', '');
	      main_core.Event.unbind(window, 'mouseup', this.endResize);
	      main_core.Event.unbind(window, 'pointermove', this.resize);
	      this.$emit('update', null, null);
	      await this.updateIds(null, null);
	    },
	    manageFromLimitNotification(resizeFromTs, fromTs) {
	      const colliding = this.colliding.filter(({
	        toTs
	      }) => toTs === this.closestOnFrom);
	      if (resizeFromTs < this.limits.fromTs) {
	        this.showTooltipTop({
	          content: this.loc('BOOKING_RESIZE_GRID_LIMIT')
	        });
	        return;
	      }
	      if (fromTs < this.closestOnFrom) {
	        if (this.checkClosestByIntersection(colliding)) {
	          this.showTooltipTop({
	            content: this.loc('BOOKING_RESIZE_INTERSECTIONS_LIMIT')
	          });
	          return;
	        }
	        this.showTooltipTop({
	          content: this.loc('BOOKING_RESIZE_LIMIT')
	        });
	        return;
	      }
	      this.hideTooltips();
	    },
	    manageToLimitNotification(resizeToTs, toTs) {
	      const colliding = this.colliding.filter(({
	        fromTs
	      }) => fromTs === this.closestOnTo);
	      if (resizeToTs > this.limits.toTs) {
	        this.showTooltipBottom({
	          content: this.loc('BOOKING_RESIZE_GRID_LIMIT')
	        });
	        return;
	      }
	      if (toTs > this.closestOnTo) {
	        if (this.checkClosestByIntersection(colliding)) {
	          this.showTooltipBottom({
	            content: this.loc('BOOKING_RESIZE_INTERSECTIONS_LIMIT')
	          });
	          return;
	        }
	        this.showTooltipBottom({
	          content: this.loc('BOOKING_RESIZE_LIMIT')
	        });
	        return;
	      }
	      this.hideTooltips();
	    },
	    checkClosestByIntersection(colliding) {
	      return this.hasIntersections && (colliding.length === 1 || colliding.filter(coll => coll.resourcesIds[0] !== this.resourceId).length >= 2);
	    },
	    async updateIds(bookingId, resourceId) {
	      await Promise.all([this.$store.dispatch(`${booking_const.Model.Interface}/setResizedBookingId`, bookingId), this.$store.dispatch(`${booking_const.Model.Interface}/setDraggedBookingResourceId`, resourceId)]);
	      void booking_lib_busySlots.busySlots.loadBusySlots();
	    },
	    resizeBooking() {
	      if (!this.dateFromTsRounded || !this.dateToTsRounded) {
	        return;
	      }
	      if (this.dateFromTsRounded === this.booking.dateFromTs && this.dateToTsRounded === this.booking.dateToTs) {
	        return;
	      }
	      const resource = this.$store.getters[`${booking_const.Model.Resources}/getById`](this.resourceId);
	      if (resource.isDeleted) {
	        return;
	      }
	      const id = this.bookingId;
	      const booking = {
	        id,
	        dateFromTs: this.dateFromTsRounded,
	        dateToTs: this.dateToTsRounded,
	        timezoneFrom: this.booking.timezoneFrom,
	        timezoneTo: this.booking.timezoneTo
	      };
	      if (!booking_lib_isRealId.isRealId(this.bookingId)) {
	        void this.$store.dispatch(`${booking_const.Model.Bookings}/update`, {
	          id,
	          booking
	        });
	        return;
	      }
	      void booking_provider_service_bookingService.bookingService.update({
	        id,
	        ...booking
	      });
	    },
	    roundTimestamp(timestamp) {
	      const fiveMinutes = booking_lib_duration.Duration.getUnitDurations().i * 5;
	      return Math.round(timestamp / fiveMinutes) * fiveMinutes;
	    }
	  },
	  template: `
		<div>
			<div ref="bookingResizeTop" class="booking-booking-resize --from" @mousedown="onMouseDown"></div>
			<div ref="bookingResizeBottom" class="booking-booking-resize --to" @mousedown="onMouseDown"></div>
		</div>
	`
	};

	const BookingWidth = 280;

	// @vue/component
	const BookingBase = {
	  name: 'BookingBase',
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    },
	    nowTs: {
	      type: Number,
	      required: true
	    },
	    width: {
	      type: Number,
	      default: BookingWidth
	    },
	    leftOffset: {
	      type: Number,
	      default: 0
	    },
	    bookingClass: {
	      type: [String, Object, Array],
	      default: ''
	    },
	    bookingStyle: {
	      type: [String, Object, Array],
	      default: ''
	    }
	  },
	  data() {
	    return {
	      visible: true,
	      isDisabledPopupShown: false,
	      resizeFromTs: null,
	      resizeToTs: null,
	      visibleNotePopup: false
	    };
	  },
	  mounted() {
	    this.updateVisibility();
	    this.updateVisibilityDuringTransition();
	    setTimeout(() => {
	      if (!this.isReal && booking_lib_mousePosition.mousePosition.isMousePressed()) {
	        void this.$refs.resize.startResize();
	      }
	    }, 300);
	  },
	  beforeUnmount() {
	    var _this$booking;
	    if (this.deletingBookings[this.bookingId] || !((_this$booking = this.booking) != null && _this$booking.resourcesIds.includes(this.resourceId))) {
	      this.$el.remove();
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      resourcesIds: `${booking_const.Model.Interface}/resourcesIds`,
	      zoom: `${booking_const.Model.Interface}/zoom`,
	      scroll: `${booking_const.Model.Interface}/scroll`,
	      draggedBookingId: `${booking_const.Model.Interface}/draggedBookingId`,
	      draggedDataTransfer: `${booking_const.Model.Interface}/draggedDataTransfer`,
	      editingBookingId: `${booking_const.Model.Interface}/editingBookingId`,
	      isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`,
	      deletingBookings: `${booking_const.Model.Interface}/deletingBookings`
	    }),
	    isReal() {
	      return booking_lib_isRealId.isRealId(this.bookingId);
	    },
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    },
	    client() {
	      const clientData = this.booking.primaryClient;
	      return clientData ? this.$store.getters[`${booking_const.Model.Clients}/getByClientData`](clientData) : null;
	    },
	    left() {
	      return booking_lib_grid.grid.calculateLeft(this.resourceId);
	    },
	    top() {
	      return booking_lib_grid.grid.calculateTop(this.dateFromTs);
	    },
	    height() {
	      return booking_lib_grid.grid.calculateHeight(this.dateFromTs, this.dateToTs);
	    },
	    realHeight() {
	      return booking_lib_grid.grid.calculateRealHeight(this.dateFromTs, this.dateToTs);
	    },
	    dateFromTs() {
	      var _this$resizeFromTs;
	      return (_this$resizeFromTs = this.resizeFromTs) != null ? _this$resizeFromTs : this.booking.dateFromTs;
	    },
	    dateToTs() {
	      var _this$resizeToTs;
	      return (_this$resizeToTs = this.resizeToTs) != null ? _this$resizeToTs : this.booking.dateToTs;
	    },
	    dateFromTsRounded() {
	      var _this$roundTimestamp;
	      return (_this$roundTimestamp = this.roundTimestamp(this.resizeFromTs)) != null ? _this$roundTimestamp : this.dateFromTs;
	    },
	    dateToTsRounded() {
	      var _this$roundTimestamp2;
	      return (_this$roundTimestamp2 = this.roundTimestamp(this.resizeToTs)) != null ? _this$roundTimestamp2 : this.dateToTs;
	    },
	    disabled() {
	      return this.isEditingBookingMode && this.editingBookingId !== this.bookingId;
	    },
	    bookingOffset() {
	      return this.leftOffset * this.zoom;
	    },
	    isExpiredBooking() {
	      return this.booking.dateToTs < this.nowTs;
	    },
	    isNotVisited() {
	      const started = this.nowTs > this.booking.dateFromTs;
	      const statusUnknown = this.booking.visitStatus === booking_const.VisitStatus.Unknown;
	      const statusNotVisited = this.booking.visitStatus === booking_const.VisitStatus.NotVisited;
	      return started && statusUnknown || statusNotVisited;
	    },
	    disabledHover() {
	      return this.draggedDataTransfer.id > 0 && (this.draggedDataTransfer.kind !== booking_const.DraggedElementKind.Booking || this.draggedDataTransfer.id !== this.bookingId);
	    }
	  },
	  methods: {
	    updateVisibilityDuringTransition() {
	      var _this$animation;
	      (_this$animation = this.animation) == null ? void 0 : _this$animation.stop();
	      // eslint-disable-next-line new-cap
	      this.animation = new BX.easing({
	        duration: 200,
	        start: {},
	        finish: {},
	        step: this.updateVisibility
	      });
	      this.animation.animate();
	    },
	    updateVisibility() {
	      if (!this.$el) {
	        return;
	      }
	      const rect = this.$el.getBoundingClientRect();
	      this.visible = rect.right > 0 && rect.left < window.innerWidth;
	    },
	    onNoteMouseEnter() {
	      this.showNoteTimeout = setTimeout(() => {
	        this.visibleNotePopup = true;
	      }, 100);
	    },
	    onNoteMouseLeave() {
	      clearTimeout(this.showNoteTimeout);
	      this.visibleNotePopup = false;
	    },
	    onClick(event) {
	      if (this.disabled) {
	        this.isDisabledPopupShown = true;
	        event.stopPropagation();
	      }
	    },
	    resizeUpdate(resizeFromTs, resizeToTs) {
	      this.resizeFromTs = resizeFromTs;
	      this.resizeToTs = resizeToTs;
	    },
	    roundTimestamp(timestamp) {
	      const fiveMinutes = booking_lib_duration.Duration.getUnitDurations().i * 5;
	      return timestamp ? Math.round(timestamp / fiveMinutes) * fiveMinutes : null;
	    }
	  },
	  watch: {
	    scroll() {
	      this.updateVisibility();
	    },
	    zoom() {
	      this.updateVisibility();
	    },
	    resourcesIds() {
	      this.updateVisibilityDuringTransition();
	    },
	    visible(visible) {
	      if (visible) {
	        return;
	      }
	      setTimeout(() => {
	        this.updateVisibility();
	      }, 2000);
	    }
	  },
	  components: {
	    Actions,
	    BookingAddClient,
	    BookingCrmButton,
	    BookingTime,
	    BookingName,
	    BookingNote,
	    BookingProfit,
	    Communication: booking_component_booking.Communication,
	    Counter,
	    DisabledPopup: booking_component_booking.DisabledPopup,
	    Resize
	  },
	  template: `
		<div
			class="booking-booking-booking booking--draggable-item"
			data-element="booking-booking"
			:data-id="bookingId"
			:data-resource-id="resourceId"
			data-kind="booking"
			:style="[bookingStyle, {
				'--left': left + bookingOffset + 'px',
				'--top': top + 'px',
				'--height': height + 'px',
				'--width': width + 'px',
			}].flat(1)"
			:class="[bookingClass, {
				'--not-real': !isReal,
				'--zoom-is-less-than-08': zoom < 0.8,
				'--compact-mode': realHeight < 40 || zoom < 0.8,
				'--small': realHeight <= 15,
				'--long': realHeight >= 65,
				'--disabled': disabled,
				'--confirmed': booking.isConfirmed && !isNotVisited,
				'--expired': isExpiredBooking,
				'--not-visited': isNotVisited,
				'--resizing': resizeFromTs && resizeToTs,
				'--no-pointer-events': disabledHover,
			}].flat(1)"
			@click.capture="onClick"
		>
			<div v-if="visible" class="booking-booking-booking-padding">
				<Counter :bookingId="bookingId" :nowTs="nowTs"/>
				<div class="booking-booking-booking-inner">
					<div class="booking-booking-booking-content">
						<div class="booking-booking-booking-content-row">
							<div
								class="booking-booking-booking-name-container"
								@mouseenter="onNoteMouseEnter"
								@mouseleave="onNoteMouseLeave"
								@click="visibleNotePopup = true"
							>
								<BookingName :bookingId="bookingId" :resourceId="resourceId"/>
								<BookingNote
									:bookingId="bookingId"
									:bindElement="() => $el"
									:visiblePopup="visibleNotePopup"
									ref="note"
								/>
							</div>
							<BookingTime
								:bookingId="bookingId"
								:resourceId="resourceId"
								:dateFromTs="dateFromTsRounded"
								:dateToTs="dateToTsRounded"
							/>
							<BookingProfit :bookingId="bookingId" :resourceId="resourceId"/>
						</div>
						<div class="booking-booking-booking-content-row --lower">
							<BookingTime
								:bookingId="bookingId"
								:resourceId="resourceId"
								:dateFromTs="dateFromTsRounded"
								:dateToTs="dateToTsRounded"
							/>
							<div v-if="client" class="booking-booking-booking-buttons">
								<Communication/>
								<BookingCrmButton :bookingId="bookingId"/>
							</div>
							<BookingAddClient
								v-else
								:bookingId="bookingId"
								:resourceId="resourceId"
								:expired="isExpiredBooking"
							/>
						</div>
					</div>
					<slot name="actions">
						<Actions :bookingId="bookingId" :resourceId="resourceId"/>
					</slot>
				</div>
			</div>
			<Resize
				v-if="!disabled"
				:bookingId="bookingId"
				:resourceId="resourceId"
				ref="resize"
				@update="resizeUpdate"
			/>
			<DisabledPopup
				v-if="isDisabledPopupShown"
				:popupId="['booking-booking-disabled-popup', bookingId, resourceId].join('-')"
				:bindElement="() => $el"
				contentClass="booking-booking-disabled-popup-content"
				@close="isDisabledPopupShown = false"
			/>
			<slot/>
		</div>
	`
	};

	function getOverbookingFreeSpace({
	  booking,
	  colliding,
	  selectedDateTs,
	  draggedBookingResourcesIds
	}) {
	  const minTs = new Date(selectedDateTs).setHours(0, 0, 0, 0);
	  const maxTs = new Date(selectedDateTs).setHours(24, 0, 0, 0);
	  const freeSpace = {
	    fromTs: minTs,
	    toTs: maxTs
	  };
	  if (draggedBookingResourcesIds.length > 1) {
	    const bookingColliding = colliding.find(({
	      fromTs
	    }) => fromTs === booking.dateFromTs);
	    if (bookingColliding && draggedBookingResourcesIds.every(id => bookingColliding.resourcesIds.includes(id))) {
	      return null;
	    }
	  }
	  for (const {
	    fromTs,
	    toTs
	  } of colliding) {
	    if (toTs <= booking.dateFromTs) {
	      freeSpace.fromTs = Math.max(freeSpace.fromTs, toTs);
	    }
	    if (booking.dateToTs <= fromTs) {
	      freeSpace.toTs = Math.min(freeSpace.toTs, fromTs);
	    }
	  }
	  if (freeSpace.fromTs === minTs && freeSpace.toTs === maxTs) {
	    return null;
	  }
	  return freeSpace;
	}
	function findTimeForDroppedBooking(freeSpace, booking, droppedBooking) {
	  const duration = droppedBooking.dateToTs - droppedBooking.dateFromTs;
	  if (booking.dateFromTs >= freeSpace.fromTs && booking.dateFromTs + duration <= freeSpace.toTs) {
	    return {
	      dateFromTs: booking.dateFromTs,
	      dateToTs: booking.dateFromTs + duration
	    };
	  }
	  if (booking.dateToTs - duration >= freeSpace.fromTs && booking.dateToTs <= freeSpace.toTs) {
	    return {
	      dateFromTs: booking.dateToTs - duration,
	      dateToTs: booking.dateToTs
	    };
	  }
	  return {
	    dateFromTs: freeSpace.fromTs,
	    dateToTs: freeSpace.fromTs + duration
	  };
	}

	function countBookingWidth(overlappingBookings) {
	  const overlappingBookingsCount = overlappingBookings.length > 0 ? overlappingBookings.length : 1;
	  return BookingWidth / overlappingBookingsCount;
	}
	function countBookingLeftOffset({
	  bookingId,
	  bookingWidth,
	  overlappingBookings
	}) {
	  let index = overlappingBookings.indexOf(bookingId);
	  if (index === -1) {
	    index = 0;
	  }
	  return bookingWidth * index;
	}

	// @vue/component
	const Booking = {
	  name: 'Booking',
	  components: {
	    BookingBase,
	    Actions
	  },
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    },
	    nowTs: {
	      type: Number,
	      required: true
	    },
	    /**
	     * @param {BookingUiGroup[]} bookingUiGroups
	     */
	    bookingUiGroups: {
	      type: Array,
	      default: () => []
	    }
	  },
	  data() {
	    return {
	      dropArea: false,
	      freeSpace: null
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      getBookingById: `${booking_const.Model.Bookings}/getById`,
	      overbookingMap: `${booking_const.Model.Bookings}/overbookingMap`,
	      selectedDateTs: `${booking_const.Model.Interface}/selectedDateTs`,
	      deletingBookings: `${booking_const.Model.Interface}/deletingBookings`,
	      animationPause: `${booking_const.Model.Interface}/animationPause`,
	      isBookingCreatedFromEmbed: `${booking_const.Model.Interface}/isBookingCreatedFromEmbed`,
	      editingBookingId: `${booking_const.Model.Interface}/editingBookingId`,
	      draggedDataTransfer: `${booking_const.Model.Interface}/draggedDataTransfer`,
	      draggedBookingId: `${booking_const.Model.Interface}/draggedBookingId`,
	      getWaitListItemById: `${booking_const.Model.WaitList}/getById`,
	      getResourceById: `${booking_const.Model.Resources}/getById`,
	      isDeletingResourceFilterMode: `${booking_const.Model.Filter}/isDeletingResourceFilterMode`,
	      deletingResource: `${booking_const.Model.Filter}/deletingResource`
	    }),
	    booking() {
	      return this.getBookingById(this.bookingId);
	    },
	    deletingBookings() {
	      return Object.values(this.$store.getters[`${booking_const.Model.Interface}/deletingBookings`]);
	    },
	    overbooking() {
	      return this.overbookingMap.get(this.bookingId) || null;
	    },
	    overbookingInResource() {
	      var _this$overbooking, _this$overbooking$ite;
	      return ((_this$overbooking = this.overbooking) == null ? void 0 : (_this$overbooking$ite = _this$overbooking.items) == null ? void 0 : _this$overbooking$ite.find(item => item.resourceId === this.resourceId)) || null;
	    },
	    hasOverbooking() {
	      var _this$overbookingInRe;
	      return (((_this$overbookingInRe = this.overbookingInResource) == null ? void 0 : _this$overbookingInRe.intersections) || []).some(({
	        id
	      }) => !this.deletingBookings.includes(id));
	    },
	    isShifted() {
	      return this.hasOverbooking && main_core.Type.isPlainObject(this.overbookingInResource) && this.overbookingInResource.shifted;
	    },
	    overlappingBookings() {
	      var _this$bookingUiGroups;
	      const bookingId = !this.isShifted || !this.hasOverbooking ? this.bookingId : this.overbookingDependencies[0];
	      return ((_this$bookingUiGroups = this.bookingUiGroups.find(({
	        bookingIds
	      }) => bookingIds.includes(bookingId))) == null ? void 0 : _this$bookingUiGroups.bookingIds) || [];
	    },
	    overbookingDependencies() {
	      var _this$overbookingInRe2;
	      return (((_this$overbookingInRe2 = this.overbookingInResource) == null ? void 0 : _this$overbookingInRe2.intersections) || []).map(({
	        id
	      }) => id);
	    },
	    bookingWidth() {
	      if (this.hasOverbooking) {
	        return countBookingWidth(this.overlappingBookings) / 2;
	      }
	      return countBookingWidth(this.overlappingBookings);
	    },
	    leftOffset() {
	      if (this.isShifted) {
	        const bookingId = this.overbookingDependencies[0];
	        const leftOffset = countBookingLeftOffset({
	          bookingId,
	          bookingWidth: this.bookingWidth,
	          overlappingBookings: this.overlappingBookings
	        });
	        return leftOffset * 2 + this.bookingWidth;
	      }
	      return countBookingLeftOffset({
	        bookingId: this.booking.id,
	        bookingWidth: this.hasOverbooking ? this.bookingWidth * 2 : this.bookingWidth,
	        overlappingBookings: this.overlappingBookings
	      });
	    },
	    actionsPopupOptions() {
	      return {
	        overbooking: {
	          disabled: this.overbooking !== null,
	          hidden: this.isDeletedResource
	        },
	        waitList: {
	          hidden: this.isDeletedResource
	        }
	      };
	    },
	    realBooking() {
	      return main_core.Type.isNumber(this.bookingId);
	    },
	    hasAccent() {
	      return this.editingBookingId === this.bookingId || this.isBookingCreatedFromEmbed(this.bookingId);
	    },
	    isDeletedResource() {
	      var _this$getResourceById, _this$getResourceById2;
	      return (_this$getResourceById = (_this$getResourceById2 = this.getResourceById(this.resourceId)) == null ? void 0 : _this$getResourceById2.isDeleted) != null ? _this$getResourceById : false;
	    },
	    isShaded() {
	      return this.isDeletingResourceFilterMode && this.resourceId !== this.deletingResource.id;
	    }
	  },
	  watch: {
	    draggedDataTransfer: {
	      handler(draggedDataTransfer) {
	        if (this.hasOverbooking) {
	          return;
	        }
	        if (draggedDataTransfer.kind === booking_const.DraggedElementKind.Booking && draggedDataTransfer.id === this.bookingId) {
	          return;
	        }
	        if (!draggedDataTransfer || !draggedDataTransfer.kind || !draggedDataTransfer.id) {
	          this.stopDropHandler();
	        } else {
	          this.startDropHandler();
	        }
	      },
	      deep: true
	    }
	  },
	  methods: {
	    dragMouseEnter() {
	      if (this.isDeletedResource) {
	        this.dropArea = false;
	        return;
	      }
	      if (this.dropArea || !this.draggedDataTransfer.id) {
	        return;
	      }
	      if (this.draggedDataTransfer.kind === booking_const.DraggedElementKind.WaitListItem) {
	        this.freeSpace = {
	          fromTs: this.booking.dateFromTs,
	          toTs: this.booking.dateToTs,
	          resourcesIds: this.booking.resourcesIds
	        };
	        this.dropArea = true;
	        return;
	      }
	      const draggedBookingId = this.draggedBookingId;
	      if (draggedBookingId === null) {
	        return;
	      }
	      const draggedBooking = this.getBookingById(draggedBookingId);
	      const bookingDuration = this.booking.dateToTs - this.booking.dateFromTs;
	      const draggedBookingDuration = draggedBooking.dateToTs - draggedBooking.dateFromTs;
	      if (bookingDuration >= draggedBookingDuration && draggedBooking.resourcesIds.length <= 1) {
	        this.freeSpace = {
	          fromTs: this.booking.dateFromTs,
	          toTs: this.booking.dateToTs,
	          resourcesIds: this.booking.resourcesIds
	        };
	        this.dropArea = true;
	        return;
	      }
	      const excludeBookingFn = booking => {
	        if (booking.id === this.draggedBookingId) {
	          return true;
	        }
	        const overbooking = this.overbookingMap.get(booking.id);
	        const resourceId = this.resourceId;
	        return !overbooking || overbooking.items.some(item => item.resourceId === resourceId);
	      };
	      const colliding = this.$store.getters[`${booking_const.Model.Interface}/getColliding`](this.resourceId, excludeBookingFn);
	      if (colliding.length === 0) {
	        this.freeSpace = {
	          fromTs: this.booking.dateFromTs,
	          toTs: this.booking.dateToTs,
	          resourcesIds: this.booking.resourcesIds
	        };
	        this.dropArea = true;
	        return;
	      }
	      const freeSpace = getOverbookingFreeSpace({
	        booking: this.booking,
	        colliding,
	        selectedDateTs: this.selectedDateTs,
	        draggedBookingResourcesIds: draggedBooking.resourcesIds
	      });
	      this.freeSpace = freeSpace;
	      this.dropArea = freeSpace && freeSpace.toTs - freeSpace.fromTs >= draggedBookingDuration;
	    },
	    dragMouseLeave() {
	      this.dropArea = false;
	      this.freeSpace = null;
	    },
	    async dropElement() {
	      const id = this.draggedDataTransfer.id;
	      if (!id || !this.freeSpace) {
	        return;
	      }
	      if (this.draggedDataTransfer.kind === booking_const.DraggedElementKind.Booking) {
	        await this.dropBooking(id);
	        return;
	      }
	      if (this.draggedDataTransfer.kind === booking_const.DraggedElementKind.WaitListItem) {
	        await this.dropWaitListItem(id);
	      }
	    },
	    async dropBooking(id) {
	      const droppedBooking = this.getBookingById(id);
	      const {
	        dateFromTs,
	        dateToTs
	      } = findTimeForDroppedBooking(this.freeSpace, this.booking, droppedBooking);
	      const overbooking = {
	        id,
	        dateFromTs,
	        dateToTs,
	        timezoneFrom: droppedBooking.timezoneFrom,
	        timezoneTo: droppedBooking.timezoneTo,
	        resourcesIds: [...new Set([this.resourceId, ...droppedBooking.resourcesIds.slice(1, droppedBooking.resourcesIds.length)])]
	      };
	      if (!booking_lib_isRealId.isRealId(id)) {
	        await this.$store.dispatch(`${booking_const.Model.Bookings}/update`, {
	          id,
	          booking: overbooking
	        });
	        return;
	      }
	      await booking_provider_service_bookingService.bookingService.update({
	        id,
	        ...overbooking
	      });
	    },
	    async dropWaitListItem(id) {
	      var _resource$slotRanges, _resource$slotRanges$;
	      const droppedWaitListItem = this.getWaitListItemById(id);
	      const clients = [...droppedWaitListItem.clients];
	      const resource = this.getResourceById(this.resourceId);
	      const timezone = resource == null ? void 0 : (_resource$slotRanges = resource.slotRanges) == null ? void 0 : (_resource$slotRanges$ = _resource$slotRanges[0]) == null ? void 0 : _resource$slotRanges$.timezone;
	      const overbooking = {
	        id: `wl${id}`,
	        resourcesIds: [this.resourceId],
	        name: droppedWaitListItem.name,
	        note: droppedWaitListItem.note,
	        clients,
	        primaryClient: clients.length > 0 ? clients[0] : undefined,
	        externalData: [...droppedWaitListItem.externalData],
	        dateFromTs: this.booking.dateFromTs,
	        dateToTs: this.booking.dateToTs,
	        timezoneFrom: timezone,
	        timezoneTo: timezone
	      };
	      const result = await booking_provider_service_bookingService.bookingService.createFromWaitListItem(id, overbooking);
	      if (result.success && result.booking) {
	        booking_lib_analytics.BookingAnalytics.sendAddBooking({
	          isOverbooking: true
	        });
	      }
	    },
	    startDropHandler() {
	      main_core.Event.bind(this.$el, 'mousemove', this.dragMouseEnter, {
	        capture: true
	      });
	      main_core.Event.bind(this.$el, 'mouseleave', this.dragMouseLeave, {
	        capture: true
	      });
	      main_core.Event.bind(this.$el, 'mouseup', this.dropElement, {
	        capture: true
	      });
	    },
	    stopDropHandler() {
	      this.dropArea = false;
	      this.freeSpace = null;
	      main_core.Event.unbind(this.$el, 'mousemove', this.dragMouseEnter, {
	        capture: true
	      });
	      main_core.Event.unbind(this.$el, 'mouseleave', this.dragMouseLeave, {
	        capture: true
	      });
	      main_core.Event.unbind(this.$el, 'mouseup', this.dropElement, {
	        capture: true
	      });
	    }
	  },
	  template: `
		<BookingBase
			:bookingId
			:resourceId
			:nowTs
			:leftOffset
			:bookingClass="{
				'--short': overlappingBookings.length > 1,
				'--overbooking': hasOverbooking,
				'--overbooking-bg': hasOverbooking || overbooking !== null,
				'--shifted': isShifted && !realBooking,
				'--drop-area': dropArea,
				'--accent': hasAccent,
				'--shaded': isShaded,
				'not-transition': animationPause,
			}"
			:width="bookingWidth"
		>
			<template #actions>
				<Actions
					:bookingId
					:resourceId
					:actionsPopupOptions
				/>
			</template>
			<div class="booking--booking-pseudo-overbooking"></div>
		</BookingBase>
	`
	};

	// @vue/component
	const BusyPopup = {
	  name: 'BusyPopup',
	  components: {
	    Popup: booking_component_popup.Popup
	  },
	  props: {
	    busySlot: {
	      type: Object,
	      required: true
	    }
	  },
	  emits: ['close'],
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      offset: `${booking_const.Model.Interface}/offset`,
	      mousePosition: `${booking_const.Model.Interface}/mousePosition`
	    }),
	    resource() {
	      const resourceId = this.busySlot.type === booking_const.BusySlot.Intersection ? this.busySlot.intersectingResourceId : this.busySlot.resourceId;
	      return this.$store.getters[`${booking_const.Model.Resources}/getById`](resourceId);
	    },
	    popupId() {
	      return `booking-booking-busy-popup-${this.busySlot.resourceId}`;
	    },
	    config() {
	      const width = 200;
	      const angleLeft = main_popup.Popup.getOption('angleMinBottom');
	      const angleOffset = width / 2 - angleLeft;
	      return {
	        bindElement: this.mousePosition,
	        width,
	        background: '#2878ca',
	        offsetTop: -5,
	        offsetLeft: -angleOffset + angleLeft,
	        bindOptions: {
	          forceBindPosition: true,
	          position: 'top'
	        },
	        angle: {
	          offset: angleOffset,
	          position: 'bottom'
	        },
	        angleBorderRadius: '4px 0',
	        autoHide: false
	      };
	    },
	    textFormatted() {
	      const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	      const messageId = this.getPopupMessageId(this.busySlot.type);
	      return this.loc(messageId, {
	        '#RESOURCE#': this.resource.name,
	        '#TIME_FROM#': main_date.DateTimeFormat.format(timeFormat, (this.busySlot.fromTs + this.offset) / 1000),
	        '#TIME_TO#': main_date.DateTimeFormat.format(timeFormat, (this.busySlot.toTs + this.offset) / 1000)
	      });
	    }
	  },
	  watch: {
	    mousePosition: {
	      handler() {
	        this.adjustPosition();
	      },
	      deep: true
	    }
	  },
	  methods: {
	    getPopupMessageId(slotType) {
	      if (slotType === booking_const.BusySlot.Intersection) {
	        return 'BOOKING_BOOKING_INTERSECTING_RESOURCE_IS_BUSY';
	      }
	      if (slotType === booking_const.BusySlot.IntersectionOverbooking) {
	        return 'BOOKING_BOOKING_INTERSECTING_RESOURCE_IS_FULL_BUSY';
	      }
	      return 'BOOKING_BOOKING_RESOURCE_IS_BUSY';
	    },
	    adjustPosition() {
	      var _this$$refs$popup;
	      const popup = (_this$$refs$popup = this.$refs.popup) == null ? void 0 : _this$$refs$popup.getPopupInstance();
	      if (!popup) {
	        return;
	      }
	      popup.setBindElement(this.mousePosition);
	      popup.adjustPosition();
	    },
	    closePopup() {
	      this.$emit('close');
	    }
	  },
	  template: `
		<Popup
			v-if="mousePosition.left !== 0 && mousePosition.top !== 0"
			:id="popupId"
			:config="config"
			ref="popup"
			@close="closePopup"
		>
			<div class="booking-booking-busy-popup">
				{{ textFormatted }}
			</div>
		</Popup>
	`
	};

	const {
	  mapGetters: mapInterfaceGetters
	} = ui_vue3_vuex.createNamespacedHelpers(booking_const.Model.Interface);
	const {
	  mapGetters: mapFilterGetters
	} = ui_vue3_vuex.createNamespacedHelpers(booking_const.Model.Filter);
	const BookingBusySlotClassName = 'booking-booking-busy-slot';
	// @vue/component
	const BusySlot = {
	  name: 'BusySlot',
	  components: {
	    BusyPopup
	  },
	  props: {
	    busySlot: {
	      type: Object,
	      required: true
	    }
	  },
	  setup() {
	    return {
	      BookingBusySlotClassName,
	      BusySlotType: booking_const.BusySlot
	    };
	  },
	  data() {
	    return {
	      isPopupShown: false
	    };
	  },
	  computed: {
	    ...mapInterfaceGetters({
	      disabledBusySlots: 'disabledBusySlots',
	      isEditingBookingMode: 'isEditingBookingMode',
	      isDragMode: 'isDragMode'
	    }),
	    ...mapFilterGetters({
	      isFilterMode: 'isFilterMode'
	    }),
	    isDisabled() {
	      const isDragOffHours = this.isDragMode && this.busySlot.type === booking_const.BusySlot.OffHours;
	      const isDragOverbooking = this.isDragMode && this.busySlot.type === booking_const.BusySlot.IntersectionOverbooking;
	      if (this.isFilterMode || isDragOffHours || isDragOverbooking) {
	        return true;
	      }
	      return this.busySlot.id in this.disabledBusySlots;
	    },
	    left() {
	      return booking_lib_grid.grid.calculateLeft(this.busySlot.resourceId);
	    },
	    top() {
	      return booking_lib_grid.grid.calculateTop(this.busySlot.fromTs);
	    },
	    height() {
	      return booking_lib_grid.grid.calculateHeight(this.busySlot.fromTs, this.busySlot.toTs);
	    }
	  },
	  methods: {
	    onClick() {
	      if (this.isFilterMode || this.isEditingBookingMode || this.busySlot.type === booking_const.BusySlot.IntersectionOverbooking) {
	        return;
	      }
	      void this.$store.dispatch(`${booking_const.Model.Interface}/addDisabledBusySlot`, this.busySlot);
	    },
	    onMouseEnter() {
	      clearTimeout(this.showTimeout);
	      this.showTimeout = setTimeout(() => this.showPopup(), 300);
	      main_core.Event.unbind(document, 'mousemove', this.onMouseMove);
	      main_core.Event.bind(document, 'mousemove', this.onMouseMove);
	    },
	    onMouseMove(event) {
	      if (this.cursorInsideContainer(event.target)) {
	        this.updatePopup(event);
	      } else {
	        main_core.Event.unbind(document, 'mousemove', this.onMouseMove);
	        this.closePopup();
	      }
	    },
	    onMouseLeave(event) {
	      var _event$relatedTarget, _event$relatedTarget$;
	      if ((_event$relatedTarget = event.relatedTarget) != null && (_event$relatedTarget$ = _event$relatedTarget.closest('.popup-window')) != null && _event$relatedTarget$.querySelector('.booking-booking-busy-popup')) {
	        return;
	      }
	      main_core.Event.unbind(document, 'mousemove', this.onMouseMove);
	      this.closePopup();
	    },
	    cursorInsideContainer(eventTarget) {
	      return !main_core.Type.isNull(eventTarget) && main_core.Dom.hasClass(eventTarget, this.BookingBusySlotClassName);
	    },
	    updatePopup(event) {
	      var _this$$refs$container, _this$showTimeout;
	      const rect = (_this$$refs$container = this.$refs.container) == null ? void 0 : _this$$refs$container.getBoundingClientRect();
	      if (this.isDragMode || !rect || event.clientY > rect.top + rect.height || event.clientY < rect.top || event.clientX < rect.left || event.clientX > rect.left + rect.width) {
	        this.closePopup();
	        return;
	      }
	      (_this$showTimeout = this.showTimeout) != null ? _this$showTimeout : this.showTimeout = setTimeout(() => this.showPopup(), 300);
	    },
	    showPopup() {
	      this.isPopupShown = true;
	    },
	    closePopup() {
	      clearTimeout(this.showTimeout);
	      this.showTimeout = null;
	      this.isPopupShown = false;
	    }
	  },
	  template: `
		<div
			v-if="left >= 0"
			:class="[BookingBusySlotClassName, {
				'--disabled': isDisabled,
			}]"
			:style="{
				'--left': left + 'px',
				'--top': top + 'px',
				'--height': height + 'px',
				'z-index': busySlot.type === BusySlotType.IntersectionOverbooking ? 1 : 'unset',
			}"
			data-element="booking-busy-slot"
			:data-id="busySlot.resourceId"
			:data-from="busySlot.fromTs"
			:data-to="busySlot.toTs"
			ref="container"
			@click.stop="onClick"
			@mouseenter.stop="onMouseEnter"
			@mouseleave.stop="onMouseLeave"
		></div>
		<BusyPopup
			v-if="isPopupShown"
			:busySlot="busySlot"
			@close="closePopup"
		/>
	`
	};

	/**
	 * @typedef {Object} Cell
	 * @property {string} id
	 * @property {number} fromTs
	 * @property {number} toTs
	 * @property {number} resourceId
	 * @property {boolean} boundedToBottom
	 */
	const BaseCell = {
	  props: {
	    /** @type {Cell} */
	    cell: {
	      type: Object,
	      required: true
	    },
	    className: {
	      type: [String, Object],
	      default: ''
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      selectedCells: `${booking_const.Model.Interface}/selectedCells`,
	      zoom: `${booking_const.Model.Interface}/zoom`,
	      intersections: `${booking_const.Model.Interface}/intersections`,
	      timezone: `${booking_const.Model.Interface}/timezone`,
	      offset: `${booking_const.Model.Interface}/offset`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`,
	      draggedDataTransfer: `${booking_const.Model.Interface}/draggedDataTransfer`,
	      embedItems: `${booking_const.Model.Interface}/embedItems`
	    }),
	    selected() {
	      return this.cell.id in this.selectedCells;
	    },
	    hasSelectedCells() {
	      return Object.keys(this.selectedCells).length > 0;
	    },
	    timeFormatted() {
	      const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	      return this.loc('BOOKING_BOOKING_TIME_RANGE', {
	        '#FROM#': main_date.DateTimeFormat.format(timeFormat, (this.cell.fromTs + this.offset) / 1000),
	        '#TO#': main_date.DateTimeFormat.format(timeFormat, (this.cell.toTs + this.offset) / 1000)
	      });
	    },
	    height() {
	      return booking_lib_grid.grid.calculateRealHeight(this.cell.fromTs, this.cell.toTs);
	    },
	    externalData() {
	      return this.embedItems;
	    },
	    clients() {
	      const clients = this.embedItems.filter(item => {
	        return item.entityTypeId === 'CONTACT' || item.entityTypeId === 'COMPANY';
	      });
	      return clients.map(item => {
	        return {
	          id: item.value,
	          type: {
	            code: item.entityTypeId,
	            module: item.moduleId
	          }
	        };
	      });
	    }
	  },
	  methods: {
	    onCellSelected({
	      target: {
	        checked
	      }
	    }) {
	      if (!this.isFeatureEnabled) {
	        booking_lib_limit.limit.show();
	        return;
	      }
	      if (checked) {
	        this.$store.dispatch(`${booking_const.Model.Interface}/addSelectedCell`, this.cell);
	      } else {
	        this.$store.dispatch(`${booking_const.Model.Interface}/removeSelectedCell`, this.cell);
	      }
	    },
	    onMouseDown() {
	      var _this$intersections$, _this$intersections$t;
	      if (!this.isFeatureEnabled) {
	        void booking_lib_limit.limit.show();
	        return;
	      }
	      void this.$store.dispatch(`${booking_const.Model.Interface}/setHoveredCell`, null);
	      this.creatingBookingId = `tmp-id-${Date.now()}-${Math.random()}`;
	      void this.$store.dispatch(`${booking_const.Model.Filter}/addQuickFilterIgnoredBookingId`, this.creatingBookingId);
	      void this.$store.dispatch(`${booking_const.Model.Bookings}/add`, {
	        id: this.creatingBookingId,
	        dateFromTs: this.cell.fromTs,
	        dateToTs: this.cell.toTs,
	        resourcesIds: [...new Set([this.cell.resourceId, ...((_this$intersections$ = this.intersections[0]) != null ? _this$intersections$ : []), ...((_this$intersections$t = this.intersections[this.cell.resourceId]) != null ? _this$intersections$t : [])])],
	        timezoneFrom: this.timezone,
	        timezoneTo: this.timezone,
	        externalData: this.externalData,
	        clients: this.clients
	      });
	      main_core.Event.bind(window, 'mouseup', this.addBooking);
	    },
	    addBooking() {
	      main_core.Event.unbind(window, 'mouseup', this.addBooking);
	      if (!this.isFeatureEnabled) {
	        void booking_lib_limit.limit.show();
	        return;
	      }
	      setTimeout(async () => {
	        const creatingBooking = this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.creatingBookingId);
	        const result = await booking_provider_service_bookingService.bookingService.add(creatingBooking);
	        if (result.success && result.booking) {
	          const overbookingMap = this.$store.getters[`${booking_const.Model.Bookings}/overbookingMap`];
	          booking_lib_analytics.BookingAnalytics.sendAddBooking({
	            isOverbooking: Boolean(overbookingMap == null ? void 0 : overbookingMap.has == null ? void 0 : overbookingMap.has(result.booking.id))
	          });
	        }
	      });
	    }
	  },
	  template: `
		<div
			class="booking-booking-base-cell"
			:class="[className, {
				'--selected': selected,
				'--bounded-to-bottom': cell.boundedToBottom,
				'--height-is-less-than-40': height < 40,
				'--compact-mode': height < 40 || zoom < 0.8,
				'--small': height <= 12.5,
			}]"
			:style="{
				'--height': height + 'px',
			}"
			data-element="booking-base-cell"
			:data-resource-id="cell.resourceId"
			:data-from="cell.fromTs"
			:data-to="cell.toTs"
			:data-selected="selected"
		>
			<div class="booking-booking-grid-cell-padding">
				<div class="booking-booking-grid-cell-inner">
					<label
						class="booking-booking-grid-cell-time"
						data-element="booking-grid-cell-select-label"
					>
						<span class="booking-booking-grid-cell-time-inner">
							<input
								v-if="!draggedDataTransfer.id"
								class="booking-booking-grid-cell-checkbox"
								type="checkbox"
								:checked="selected"
								@change="onCellSelected"
							>
							<span data-element="booking-grid-cell-time">
								{{ timeFormatted }}
							</span>
						</span>
					</label>
					<div
						v-if="!hasSelectedCells && !draggedDataTransfer.id"
						class="booking-booking-grid-cell-select-button-container"
						ref="button"
					>
						<div
							class="booking-booking-grid-cell-select-button"
							:class="{'--lock': !isFeatureEnabled}"
							data-element="booking-grid-cell-add-button"
							@mousedown="onMouseDown"
						>
							<div class="booking-booking-grid-cell-select-button-text">
								{{ loc('BOOKING_BOOKING_SELECT') }}
								<Icon v-if="!isFeatureEnabled" :name="IconSet.LOCK" />
							</div>
							<div class="ui-icon-set --chevron-right"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	/**
	 * @typedef {Object} Cell
	 * @property {string} id
	 * @property {number} fromTs
	 * @property {number} toTs
	 * @property {number} resourceId
	 * @property {boolean} boundedToBottom
	 *
	 * @vue/component
	 */
	const Cell = {
	  name: 'HoveredCell',
	  components: {
	    BaseCell
	  },
	  props: {
	    /** @type {Cell} */
	    cell: {
	      type: Object,
	      required: true
	    },
	    draggedBooking: {
	      type: Object,
	      default: null
	    }
	  },
	  data() {
	    return {
	      overbookingPositionsInCell: []
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      overbookingMap: `${booking_const.Model.Bookings}/overbookingMap`
	    }),
	    left() {
	      const left = booking_lib_grid.grid.calculateLeft(this.cell.resourceId);
	      const overbookingPositions = this.overbookingPositionsInCell;
	      if (overbookingPositions.length > 1) {
	        return -1;
	      }
	      if (overbookingPositions.length === 0 || overbookingPositions[0]) {
	        return left;
	      }
	      return left + booking_lib_grid.grid.calculateWidth(this.width);
	    },
	    top() {
	      return booking_lib_grid.grid.calculateTop(this.cell.fromTs);
	    },
	    height() {
	      const fromTs = this.cell.fromTs;
	      const draggedBookingDuration = this.draggedBooking ? this.draggedBooking.dateToTs - this.draggedBooking.dateFromTs : Infinity;
	      const toTs = draggedBookingDuration < this.cell.toTs - fromTs ? fromTs + draggedBookingDuration : this.cell.toTs;
	      return booking_lib_grid.grid.calculateHeight(fromTs, toTs);
	    },
	    width() {
	      return this.overbookingPositionsInCell.length === 0 ? 280 : 280 / 2;
	    }
	  },
	  mounted() {
	    this.calcOverbookingPositionsInCell();
	  },
	  methods: {
	    calcOverbookingPositionsInCell() {
	      const resourceId = this.cell.resourceId;
	      const cellTimespan = {
	        dateFromTs: this.cell.fromTs,
	        dateToTs: this.cell.toTs
	      };
	      const positions = [];
	      for (const [, overbooking] of this.overbookingMap) {
	        const resourceOverbooking = overbooking.items.find(item => item.resourceId === resourceId);
	        if (resourceOverbooking && booking_lib_checkBookingIntersection.checkBookingIntersection(overbooking.booking, cellTimespan) && !positions.includes(resourceOverbooking == null ? void 0 : resourceOverbooking.shifted)) {
	          positions.push(resourceOverbooking == null ? void 0 : resourceOverbooking.shifted);
	        }
	        if (positions.length > 2) {
	          break;
	        }
	      }
	      this.overbookingPositionsInCell = positions;
	    }
	  },
	  template: `
		<div
			v-if="left >= 0"
			class="booking-booking-selected-cell"
			:style="{
				'--left': left + 'px',
				'--top': top + 'px',
				'--height': height + 'px',
				'--width': width + 'px',
			}"
			@mouseleave="$store.dispatch('interface/setHoveredCell', null)"
		>
			<BaseCell
				:cell="cell"
				:className="{ '--overbooking': overbookingPositionsInCell.length > 0 }"
			/>
		</div>
	`
	};

	const QuickFilterLine = {
	  props: {
	    hour: {
	      type: Number,
	      required: true
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      selectedDateTs: `${booking_const.Model.Interface}/selectedDateTs`,
	      resourcesIds: `${booking_const.Model.Interface}/resourcesIds`
	    }),
	    top() {
	      return booking_lib_grid.grid.calculateTop(this.fromTs);
	    },
	    width() {
	      return this.resourcesIds.length * 280;
	    },
	    fromTs() {
	      return new Date(this.selectedDateTs).setHours(this.hour);
	    }
	  },
	  template: `
		<div
			class="booking-booking-quick-filter-line"
			:style="{
				'--top': top + 'px',
				'--width': width + 'px',
			}"
		></div>
	`
	};

	const MinUiBookingDurationMs = 15 * 60 * 1000;

	function getBookingUiSlot({
	  dateFromTs,
	  dateToTs
	}) {
	  const duration = dateToTs - dateFromTs;
	  return [dateFromTs, duration < MinUiBookingDurationMs ? dateFromTs + MinUiBookingDurationMs : dateToTs];
	}
	function createBookingModelUi(resourceId, booking, overbookingMapItem) {
	  var _overbookingMapItem$i;
	  return {
	    ...booking,
	    resourcesIds: [resourceId],
	    uiSlot: getBookingUiSlot(booking),
	    overbooking: overbookingMapItem == null ? void 0 : (_overbookingMapItem$i = overbookingMapItem.items) == null ? void 0 : _overbookingMapItem$i.some(item => {
	      return item.resourceId === resourceId && item.shifted;
	    })
	  };
	}
	function splitBookingsByResourceId(bookings) {
	  const resourceBookingsMap = new Map();
	  for (const booking of bookings) {
	    var _booking$resourcesIds;
	    const resourceId = ((_booking$resourcesIds = booking.resourcesIds) == null ? void 0 : _booking$resourcesIds[0]) || 0;
	    let resourceBookings = [];
	    if (resourceBookingsMap.has(resourceId)) {
	      resourceBookings = resourceBookingsMap.get(resourceId) || [];
	    }
	    resourceBookings.push(booking);
	    resourceBookingsMap.set(resourceId, resourceBookings);
	  }
	  return resourceBookingsMap;
	}
	function groupBookingUis(bookings) {
	  const groups = [];
	  if (bookings.length === 0) {
	    return groups;
	  }
	  let group = {
	    slot: [0, 0],
	    bookingIds: []
	  };
	  bookings.filter(booking => !booking.overbooking).map(booking => {
	    return {
	      id: booking.id,
	      uiSlot: booking.uiSlot
	    };
	  }).sort((a, b) => a.uiSlot[0] - b.uiSlot[0]).forEach(booking => {
	    if (group.bookingIds.length === 0) {
	      group.slot = [booking.uiSlot[0], booking.uiSlot[1]];
	    } else if (booking_lib_inInterval.inInterval(booking.uiSlot[0], group.slot)) {
	      group.slot[1] = booking.uiSlot[1];
	    } else {
	      groups.push(group);
	      group = {
	        slot: [booking.uiSlot[0], booking.uiSlot[1]],
	        bookingIds: []
	      };
	    }
	    group.bookingIds.push(booking.id);
	  });
	  groups.push(group);
	  return groups;
	}
	function getResourceBookingUiGroups(resourceBookingsMap) {
	  const resourceBookingUiGroups = new Map();
	  if (resourceBookingsMap instanceof Map) {
	    [...resourceBookingsMap.keys()].forEach(resourceId => {
	      const bookingGroups = groupBookingUis(resourceBookingsMap.get(resourceId) || []);
	      resourceBookingUiGroups.set(resourceId, bookingGroups);
	    });
	  }
	  return resourceBookingUiGroups;
	}

	const {
	  mapGetters: mapBookingsGetters
	} = ui_vue3_vuex.createNamespacedHelpers(booking_const.Model.Bookings);
	const {
	  mapGetters: mapInterfaceGetters$1
	} = ui_vue3_vuex.createNamespacedHelpers(booking_const.Model.Interface);
	const {
	  mapGetters: mapFilterGetters$1
	} = ui_vue3_vuex.createNamespacedHelpers(booking_const.Model.Filter);
	const Bookings = {
	  name: 'Bookings',
	  data() {
	    return {
	      nowTs: Date.now()
	    };
	  },
	  computed: {
	    ...mapBookingsGetters({
	      overbookingMap: 'overbookingMap'
	    }),
	    ...mapInterfaceGetters$1({
	      resourcesIds: 'resourcesIds',
	      selectedDateTs: 'selectedDateTs',
	      selectedCells: 'selectedCells',
	      hoveredCell: 'hoveredCell',
	      busySlots: 'busySlots',
	      isFeatureEnabled: 'isFeatureEnabled',
	      editingBookingId: 'editingBookingId',
	      embedItems: 'embedItems',
	      draggedBookingId: 'draggedBookingId'
	    }),
	    ...mapFilterGetters$1({
	      filteredBookingsIds: 'filteredBookingsIds',
	      isFilterMode: 'isFilterMode',
	      quickFilter: 'quickFilter'
	    }),
	    resourcesHash() {
	      const resources = this.$store.getters[`${booking_const.Model.Resources}/getByIds`](this.resourcesIds).map(({
	        id,
	        slotRanges
	      }) => ({
	        id,
	        slotRanges
	      }));
	      return JSON.stringify(resources);
	    },
	    bookingsHash() {
	      const bookings = this.bookings.map(({
	        id,
	        dateFromTs,
	        dateToTs
	      }) => ({
	        id,
	        dateFromTs,
	        dateToTs
	      }));
	      return JSON.stringify(bookings);
	    },
	    bookings() {
	      const dateTs = this.selectedDateTs;
	      let bookings = [];
	      if (this.isFilterMode) {
	        bookings = this.$store.getters[`${booking_const.Model.Bookings}/getByDateAndIds`](dateTs, this.filteredBookingsIds);
	      } else {
	        bookings = this.$store.getters[`${booking_const.Model.Bookings}/getByDateAndResources`](dateTs, this.resourcesIds);
	      }
	      return bookings.flatMap(booking => {
	        return booking.resourcesIds.filter(resourceId => this.resourcesIds.includes(resourceId)).map(resourceId => {
	          return createBookingModelUi(resourceId, booking, this.overbookingMap.get(booking.id));
	        });
	      }).sort((a, b) => {
	        if (a.resourcesIds[0] !== b.resourcesIds[0]) {
	          return b.resourcesIds[0] - a.resourcesIds[0];
	        }
	        if (a.dateFromTs !== b.dateFromTs) {
	          return a.dateFromTs - b.dateFromTs;
	        }
	        return b.overbooking - a.overbooking;
	      });
	    },
	    cells() {
	      const cells = [...Object.values(this.selectedCells), this.hoveredCell];
	      const dateFromTs = this.selectedDateTs;
	      const dateToTs = new Date(dateFromTs).setDate(new Date(dateFromTs).getDate() + 1);
	      return cells.filter(cell => cell && cell.toTs > dateFromTs && dateToTs > cell.fromTs);
	    },
	    quickFilterHours() {
	      const activeHours = new Set(Object.values(this.quickFilter.active));
	      return Object.values(this.quickFilter.hovered).filter(hour => !activeHours.has(hour));
	    },
	    resourceBookings() {
	      return splitBookingsByResourceId(this.bookings);
	    },
	    resourceBookingsUiGroupsMap() {
	      return getResourceBookingUiGroups(this.resourceBookings);
	    },
	    embedEditingMode() {
	      var _this$embedItems$leng, _this$embedItems;
	      return this.isFeatureEnabled && (this.editingBookingId > 0 || ((_this$embedItems$leng = (_this$embedItems = this.embedItems) == null ? void 0 : _this$embedItems.length) != null ? _this$embedItems$leng : 0) > 0);
	    },
	    draggedBooking() {
	      if (!this.draggedBookingId) {
	        return null;
	      }
	      return this.bookings.find(({
	        id
	      }) => id === this.draggedBookingId) || null;
	    }
	  },
	  mounted() {
	    this.startInterval();
	  },
	  methods: {
	    generateBookingKey(booking) {
	      return `${booking.id}-${booking.resourcesIds[0]}`;
	    },
	    getBookingUiGroupsByResourceId(resourceId) {
	      return this.resourceBookingsUiGroupsMap.get(resourceId) || [];
	    },
	    startInterval() {
	      setInterval(() => {
	        this.nowTs = Date.now();
	      }, 5 * 1000);
	    }
	  },
	  watch: {
	    selectedDateTs() {
	      void booking_lib_busySlots.busySlots.loadBusySlots();
	    },
	    bookingsHash() {
	      void booking_lib_busySlots.busySlots.loadBusySlots();
	    },
	    resourcesHash() {
	      void booking_lib_busySlots.busySlots.loadBusySlots();
	    },
	    overbookingMap() {
	      void booking_lib_busySlots.busySlots.loadBusySlots();
	    }
	  },
	  components: {
	    Booking,
	    BusySlot,
	    Cell,
	    QuickFilterLine
	  },
	  template: `
		<div
			class="booking-booking-bookings"
			:class="{
				'embed-editing-mode': embedEditingMode,
			}"
		>
			<TransitionGroup name="booking-transition-booking">
				<template v-for="booking of bookings" :key="generateBookingKey(booking)">
					<Booking
						:bookingId="booking.id"
						:resourceId="booking.resourcesIds[0]"
						:nowTs
						:bookingUiGroups="getBookingUiGroupsByResourceId(booking.resourcesIds[0])"
					/>
				</template>
			</TransitionGroup>
			<template v-for="busySlot of busySlots" :key="busySlot.id">
				<BusySlot
					:busySlot="busySlot"
				/>
			</template>
			<template v-for="cell of cells" :key="cell.id">
				<Cell
					:cell="cell"
					:draggedBooking="draggedBooking"
				/>
			</template>
			<template v-for="hour of quickFilterHours" :key="hour">
				<QuickFilterLine
					:hour="hour"
				/>
			</template>
		</div>
	`
	};

	const halfHour = 30 * 60 * 1000;
	const Cell$1 = {
	  name: 'Cell',
	  props: {
	    /** @type {CellDto} */
	    cell: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {
	      halfOffset: 0
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      overbookingMap: `${booking_const.Model.Bookings}/overbookingMap`,
	      isFilterMode: `${booking_const.Model.Filter}/isFilterMode`,
	      isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`,
	      draggedBookingId: `${booking_const.Model.Interface}/draggedBookingId`,
	      draggedDataTransfer: `${booking_const.Model.Interface}/draggedDataTransfer`,
	      resizedBookingId: `${booking_const.Model.Interface}/resizedBookingId`,
	      quickFilter: `${booking_const.Model.Filter}/quickFilter`
	    }),
	    draggedElementId() {
	      return this.draggedDataTransfer.id;
	    },
	    isAvailable() {
	      if (this.isFilterMode || this.resizedBookingId || this.isEditingBookingMode && !this.draggedDataTransfer.id) {
	        return false;
	      }
	      const {
	        fromTs,
	        toTs
	      } = this.freeSpace;
	      const cellFromTs = this.cell.fromTs;
	      const cellHalfTs = this.cell.fromTs + halfHour;
	      return (toTs > cellFromTs || toTs > cellHalfTs) && toTs - fromTs >= this.duration;
	    },
	    fromTs() {
	      return Math.min(this.freeSpace.toTs - this.duration, this.cell.fromTs) + this.halfOffset;
	    },
	    toTs() {
	      return this.fromTs + this.duration;
	    },
	    duration() {
	      if (this.draggedBooking) {
	        return this.draggedBooking.dateToTs - this.draggedBooking.dateFromTs;
	      }
	      return this.cell.toTs - this.cell.fromTs;
	    },
	    draggedBooking() {
	      var _this$$store$getters;
	      return (_this$$store$getters = this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.draggedBookingId)) != null ? _this$$store$getters : null;
	    },
	    freeSpace() {
	      let maxFrom = 0;
	      let minTo = Infinity;
	      for (const {
	        fromTs,
	        toTs
	      } of this.colliding) {
	        if (this.cell.fromTs + halfHour > fromTs && this.cell.fromTs + halfHour < toTs) {
	          maxFrom = toTs;
	          minTo = fromTs;
	          break;
	        }
	        if (toTs <= this.cell.fromTs + halfHour) {
	          maxFrom = Math.max(maxFrom, toTs);
	        }
	        if (fromTs >= this.cell.fromTs + halfHour) {
	          minTo = Math.min(minTo, fromTs);
	        }
	      }
	      return {
	        fromTs: maxFrom,
	        toTs: minTo
	      };
	    },
	    colliding() {
	      return this.$store.getters[`${booking_const.Model.Interface}/getColliding`](this.cell.resourceId, this.excludeBookingColliding);
	    },
	    quickFilterHovered() {
	      return this.cell.minutes / 60 in this.quickFilter.hovered;
	    },
	    quickFilterActive() {
	      return this.cell.minutes / 60 in this.quickFilter.active;
	    }
	  },
	  methods: {
	    excludeBookingColliding(booking) {
	      if (booking.id === this.draggedBookingId) {
	        return true;
	      }
	      const resourceId = this.cell.resourceId;
	      const overbooking = this.overbookingMap.get(booking.id);
	      return overbooking && overbooking.items.some(item => item.resourceId === resourceId);
	    },
	    mouseEnterHandler(event) {
	      this.updateHalfHour(event);
	    },
	    mouseLeaveHandler(event) {
	      var _event$relatedTarget, _nextHoveredCell$data;
	      const nextHoveredCell = (_event$relatedTarget = event.relatedTarget) == null ? void 0 : _event$relatedTarget.closest('.booking-booking-base-cell');
	      if (!nextHoveredCell || (nextHoveredCell == null ? void 0 : (_nextHoveredCell$data = nextHoveredCell.dataset) == null ? void 0 : _nextHoveredCell$data.selected) === 'true') {
	        void this.$store.dispatch(`${booking_const.Model.Interface}/setHoveredCell`, null);
	      }
	    },
	    mouseMoveHandler(event) {
	      this.updateHalfHour(event);
	    },
	    updateHalfHour(event) {
	      var _this$$refs$button;
	      if ((_this$$refs$button = this.$refs.button) != null && _this$$refs$button.contains(event.target)) {
	        return;
	      }
	      this.halfOffset = 0;
	      const clientY = event.clientY - window.scrollY;
	      const rect = this.$el.getBoundingClientRect();
	      const bottomHalf = clientY > (rect.top + rect.top + rect.height) / 2;
	      const canSubtractHalfHour = this.fromTs >= this.freeSpace.fromTs;
	      const canAddHalfHour = this.toTs + halfHour <= this.freeSpace.toTs;
	      if (bottomHalf && canAddHalfHour || !bottomHalf && !canSubtractHalfHour) {
	        this.halfOffset = halfHour;
	      }
	      if ((!bottomHalf && !canSubtractHalfHour || bottomHalf && !canAddHalfHour) && this.freeSpace.fromTs - this.cell.fromTs > 0) {
	        this.halfOffset = this.freeSpace.fromTs - this.cell.fromTs;
	      } else if (!bottomHalf && canSubtractHalfHour || bottomHalf && !canAddHalfHour) {
	        this.halfOffset = 0;
	      }
	      const offsetNotMatchesHalf = bottomHalf === (this.halfOffset === 0);
	      if (this.duration <= halfHour && offsetNotMatchesHalf) {
	        this.clearCell(event);
	        return;
	      }
	      this.hoverCell({
	        id: `${this.cell.resourceId}-${this.fromTs}-${this.toTs}`,
	        fromTs: this.fromTs,
	        toTs: this.toTs,
	        resourceId: this.cell.resourceId,
	        boundedToBottom: this.toTs === this.freeSpace.toTs
	      });
	    },
	    clearCell(event) {
	      var _event$relatedTarget2, _nextHoveredCell$data2;
	      const nextHoveredCell = (_event$relatedTarget2 = event.relatedTarget) == null ? void 0 : _event$relatedTarget2.closest('.booking-booking-base-cell');
	      if (!nextHoveredCell || (nextHoveredCell == null ? void 0 : (_nextHoveredCell$data2 = nextHoveredCell.dataset) == null ? void 0 : _nextHoveredCell$data2.selected) === 'true') {
	        void this.$store.dispatch(`${booking_const.Model.Interface}/setHoveredCell`, null);
	      }
	    },
	    hoverCell(cell) {
	      void this.$store.dispatch(`${booking_const.Model.Interface}/setHoveredCell`, null);
	      if (this.isAvailable) {
	        void this.$store.dispatch(`${booking_const.Model.Interface}/setHoveredCell`, cell);
	      }
	    }
	  },
	  watch: {
	    draggedElementId() {
	      if (!this.draggedElementId) {
	        void this.$store.dispatch(`${booking_const.Model.Interface}/setHoveredCell`, null);
	      }
	    }
	  },
	  template: `
		<div
			class="booking-booking-grid-cell"
			:class="{
				'--quick-filter-hovered': quickFilterHovered,
				'--quick-filter-active': quickFilterActive,
			}"
			data-element="booking-grid-cell"
			:data-resource-id="cell.resourceId"
			:data-from="cell.fromTs"
			:data-to="cell.toTs"
			@mouseenter="mouseEnterHandler"
			@mouseleave="mouseLeaveHandler"
			@mousemove="mouseMoveHandler"
		></div>
	`
	};

	const OffHours$1 = {
	  props: {
	    bottom: {
	      type: Boolean,
	      default: false
	    }
	  },
	  computed: ui_vue3_vuex.mapGetters({
	    offHoursHover: `${booking_const.Model.Interface}/offHoursHover`,
	    offHoursExpanded: `${booking_const.Model.Interface}/offHoursExpanded`
	  }),
	  methods: {
	    animateOffHours({
	      keepScroll
	    }) {
	      if (this.offHoursExpanded) {
	        expandOffHours.collapse();
	      } else {
	        expandOffHours.expand({
	          keepScroll
	        });
	      }
	      void this.$store.dispatch(`${booking_const.Model.Interface}/setOffHoursExpanded`, !this.offHoursExpanded);
	    }
	  },
	  template: `
		<div class="booking-booking-grid-padding">
			<div
				class="booking-booking-column-off-hours"
				:class="{'--bottom': bottom, '--hover': offHoursHover}"
				@click="animateOffHours({ keepScroll: bottom })"
				@mouseenter="$store.dispatch('interface/setOffHoursHover', true)"
				@mouseleave="$store.dispatch('interface/setOffHoursHover', false)"
			></div>
		</div>
	`
	};

	const {
	  mapGetters: mapInterfaceGetters$2
	} = ui_vue3_vuex.createNamespacedHelpers(booking_const.Model.Interface);
	const Column = {
	  props: {
	    resourceId: Number
	  },
	  data() {
	    return {
	      visible: true
	    };
	  },
	  mounted() {
	    this.updateVisibility();
	    this.updateVisibilityDuringTransition();
	  },
	  computed: {
	    ...mapInterfaceGetters$2({
	      resourcesIds: 'resourcesIds',
	      zoom: 'zoom',
	      scroll: 'scroll',
	      selectedDateTs: 'selectedDateTs',
	      offHoursHover: 'offHoursHover',
	      offHoursExpanded: 'offHoursExpanded',
	      fromHour: 'fromHour',
	      toHour: 'toHour',
	      offset: 'offset'
	    }),
	    resource() {
	      return this.$store.getters['resources/getById'](this.resourceId);
	    },
	    fromMinutes() {
	      return this.fromHour * 60;
	    },
	    toMinutes() {
	      return this.toHour * 60;
	    },
	    slotSize() {
	      var _this$resource$slotRa, _this$resource$slotRa2;
	      return (_this$resource$slotRa = (_this$resource$slotRa2 = this.resource.slotRanges[0]) == null ? void 0 : _this$resource$slotRa2.slotSize) != null ? _this$resource$slotRa : 60;
	    },
	    offHoursTopCells() {
	      return this.cells.filter(it => it.minutes < this.fromMinutes);
	    },
	    workTimeCells() {
	      return this.cells.filter(it => it.minutes >= this.fromMinutes && it.minutes < this.toMinutes);
	    },
	    offHoursBottomCells() {
	      return this.cells.filter(it => it.minutes >= this.toMinutes);
	    },
	    cells() {
	      const hour = 3600 * 1000;
	      const from = this.selectedDateTs;
	      const to = new Date(from).setDate(new Date(from).getDate() + 1);
	      return booking_lib_range.range(from, to - hour, hour).map(fromTs => {
	        const toTs = fromTs + this.slotSize * 60 * 1000;
	        return {
	          id: `${this.resource.id}-${fromTs}-${toTs}`,
	          fromTs,
	          toTs,
	          minutes: new Date(fromTs + this.offset).getHours() * 60,
	          resourceId: this.resource.id
	        };
	      });
	    }
	  },
	  methods: {
	    updateVisibilityDuringTransition() {
	      var _this$animation;
	      (_this$animation = this.animation) == null ? void 0 : _this$animation.stop();
	      this.animation = new BX.easing({
	        duration: 200,
	        start: {},
	        finish: {},
	        step: this.updateVisibility
	      });
	      this.animation.animate();
	    },
	    updateVisibility() {
	      const rect = this.$el.getBoundingClientRect();
	      this.visible = rect.right > 0 && rect.left < window.innerWidth;
	    }
	  },
	  watch: {
	    scroll() {
	      this.updateVisibility();
	    },
	    zoom() {
	      this.updateVisibility();
	    },
	    resourcesIds() {
	      this.updateVisibilityDuringTransition();
	    }
	  },
	  components: {
	    Cell: Cell$1,
	    OffHours: OffHours$1
	  },
	  template: `
		<div
			class="booking-booking-grid-column"
			data-element="booking-grid-column"
			:data-id="resourceId"
		>
			<template v-if="visible">
				<OffHours/>
				<div class="booking-booking-grid-off-hours-cells">
					<Cell v-for="cell of offHoursTopCells" :key="cell.id" :cell="cell"/>
				</div>
				<Cell v-for="cell of workTimeCells" :key="cell.id" :cell="cell"/>
				<div class="booking-booking-grid-off-hours-cells --bottom">
					<Cell v-for="cell of offHoursBottomCells" :key="cell.id" :cell="cell"/>
				</div>
				<OffHours :bottom="true"/>
			</template>
		</div>
	`
	};

	let _ = t => t,
	  _t;
	const isAirTemplate = main_core.Extension.getSettings('booking.booking').isAirTemplate;
	const duration = 200;
	const counterPanelScopeClass = 'ui-counter-panel__scope';
	const darkThemeClass = 'bitrix24-dark-theme --ui-context-content-light';
	var _slider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("slider");
	var _overlay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("overlay");
	var _handleSliderClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSliderClose");
	var _renderOverlay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderOverlay");
	var _isExpanded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isExpanded");
	var _getInset = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInset");
	var _animate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animate");
	var _applyMaximizedStyles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applyMaximizedStyles");
	var _applyMinimizedStyles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applyMinimizedStyles");
	var _appContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appContainer");
	var _appHeader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appHeader");
	var _counterPanel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("counterPanel");
	var _appContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appContent");
	var _contentPaddingElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("contentPaddingElement");
	var _imBarWidth = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("imBarWidth");
	class Maximize {
	  constructor({
	    onOverlayClick
	  }) {
	    Object.defineProperty(this, _imBarWidth, {
	      get: _get_imBarWidth,
	      set: void 0
	    });
	    Object.defineProperty(this, _contentPaddingElement, {
	      get: _get_contentPaddingElement,
	      set: void 0
	    });
	    Object.defineProperty(this, _appContent, {
	      get: _get_appContent,
	      set: void 0
	    });
	    Object.defineProperty(this, _counterPanel, {
	      get: _get_counterPanel,
	      set: void 0
	    });
	    Object.defineProperty(this, _appHeader, {
	      get: _get_appHeader,
	      set: void 0
	    });
	    Object.defineProperty(this, _appContainer, {
	      get: _get_appContainer,
	      set: void 0
	    });
	    Object.defineProperty(this, _applyMinimizedStyles, {
	      value: _applyMinimizedStyles2
	    });
	    Object.defineProperty(this, _applyMaximizedStyles, {
	      value: _applyMaximizedStyles2
	    });
	    Object.defineProperty(this, _animate, {
	      value: _animate2
	    });
	    Object.defineProperty(this, _getInset, {
	      value: _getInset2
	    });
	    Object.defineProperty(this, _isExpanded, {
	      get: _get_isExpanded,
	      set: void 0
	    });
	    Object.defineProperty(this, _renderOverlay, {
	      value: _renderOverlay2
	    });
	    Object.defineProperty(this, _slider, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _overlay, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _handleSliderClose, {
	      writable: true,
	      value: () => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _isExpanded)[_isExpanded]) {
	          babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider].applyHacks();
	          BX.SidePanel.Instance.disablePageScrollbar();
	        }
	      }
	    });
	    this.onOverlayClick = onOverlayClick;
	    babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider] = new (BX.SidePanel.Manager.getSliderClass())('');
	    babelHelpers.classPrivateFieldLooseBase(this, _overlay)[_overlay] = babelHelpers.classPrivateFieldLooseBase(this, _renderOverlay)[_renderOverlay]();
	    if (top.BX) {
	      top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', babelHelpers.classPrivateFieldLooseBase(this, _handleSliderClose)[_handleSliderClose]);
	      top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onDestroy', babelHelpers.classPrivateFieldLooseBase(this, _handleSliderClose)[_handleSliderClose]);
	    }
	  }
	  async maximize() {
	    await booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setExpanded`, true);
	    babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider].applyHacks();
	    BX.SidePanel.Instance.disablePageScrollbar();
	    const start = babelHelpers.classPrivateFieldLooseBase(this, _getInset)[_getInset](babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer]);
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], 'position', 'fixed');
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], 'inset', '0 0 0 0');
	    const finish = babelHelpers.classPrivateFieldLooseBase(this, _getInset)[_getInset](babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer]);
	    babelHelpers.classPrivateFieldLooseBase(this, _applyMaximizedStyles)[_applyMaximizedStyles]();
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _overlay)[_overlay], '--closing');
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _overlay)[_overlay], '--opening');
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _overlay)[_overlay], document.body);
	    await babelHelpers.classPrivateFieldLooseBase(this, _animate)[_animate](start, finish);
	  }
	  async minimize() {
	    await booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setExpanded`, false);
	    babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider].resetHacks();
	    BX.SidePanel.Instance.enablePageScrollbar();
	    const start = babelHelpers.classPrivateFieldLooseBase(this, _getInset)[_getInset](babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer]);
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], 'position', null);
	    const finish = babelHelpers.classPrivateFieldLooseBase(this, _getInset)[_getInset](babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer]);
	    babelHelpers.classPrivateFieldLooseBase(this, _applyMinimizedStyles)[_applyMinimizedStyles]();
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _overlay)[_overlay], '--opening');
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _overlay)[_overlay], '--closing');
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], 'position', 'fixed');
	    await babelHelpers.classPrivateFieldLooseBase(this, _animate)[_animate](start, finish);
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], 'position', null);
	    main_core.Dom.remove(babelHelpers.classPrivateFieldLooseBase(this, _overlay)[_overlay]);
	  }
	}
	function _renderOverlay2() {
	  return main_core.Tag.render(_t || (_t = _`
			<div class="booking-booking-overlay" onclick="${0}"></div>
		`), this.onOverlayClick);
	}
	function _get_isExpanded() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/expanded`];
	}
	function _getInset2(container) {
	  const rect = container.getBoundingClientRect();
	  return {
	    left: rect.left,
	    top: rect.top,
	    right: window.innerWidth - (rect.left + rect.width),
	    bottom: window.innerHeight - (rect.top + rect.height)
	  };
	}
	function _animate2(start, finish) {
	  return new Promise(complete => new BX.easing({
	    duration,
	    start,
	    finish,
	    step: ({
	      top,
	      right,
	      bottom,
	      left
	    }) => {
	      main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], 'inset', `${top}px ${right}px ${bottom}px ${left}px`);
	    },
	    complete
	  }).animate());
	}
	function _applyMaximizedStyles2() {
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _counterPanel)[_counterPanel], counterPanelScopeClass);
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], darkThemeClass);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], 'height', 'initial');
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], 'position', 'fixed');
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], 'clip-path', `inset(0 ${babelHelpers.classPrivateFieldLooseBase(this, _imBarWidth)[_imBarWidth]}px 0 0)`);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], 'background', 'var(--ui-color-palette-white-base)');
	  if (isAirTemplate) {
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appHeader)[_appHeader], 'padding-left', '12px');
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appHeader)[_appHeader], 'padding-right', '12px');
	  }
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appHeader)[_appHeader], 'border-bottom', '1px solid var(--ui-color-base-10)');
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appHeader)[_appHeader], 'max-width', '100%');
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appHeader)[_appHeader].parentElement, 'padding-right', `${babelHelpers.classPrivateFieldLooseBase(this, _imBarWidth)[_imBarWidth]}px`);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContent)[_appContent], 'margin', 0);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContent)[_appContent], 'border-radius', 0);
	  if (isAirTemplate) {
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _contentPaddingElement)[_contentPaddingElement], 'padding', 0);
	  } else {
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _contentPaddingElement)[_contentPaddingElement], 'padding-bottom', 0);
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _contentPaddingElement)[_contentPaddingElement], 'padding-right', `${babelHelpers.classPrivateFieldLooseBase(this, _imBarWidth)[_imBarWidth]}px`);
	  }
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _overlay)[_overlay], '--right', `${babelHelpers.classPrivateFieldLooseBase(this, _imBarWidth)[_imBarWidth]}px`);
	  BX.ZIndexManager.register(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], {
	    overlay: babelHelpers.classPrivateFieldLooseBase(this, _overlay)[_overlay]
	  });
	}
	function _applyMinimizedStyles2() {
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _counterPanel)[_counterPanel], counterPanelScopeClass);
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], darkThemeClass);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], 'position', null);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], 'clip-path', null);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer], 'background', null);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appHeader)[_appHeader], 'border-bottom', null);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appHeader)[_appHeader], 'max-width', null);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appHeader)[_appHeader].parentElement, 'padding-right', null);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContent)[_appContent], 'margin', null);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContent)[_appContent], 'border-radius', null);
	  if (isAirTemplate) {
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _contentPaddingElement)[_contentPaddingElement], 'padding', null);
	  } else {
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _contentPaddingElement)[_contentPaddingElement], 'padding-bottom', null);
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _contentPaddingElement)[_contentPaddingElement], 'padding-right', null);
	  }
	  BX.ZIndexManager.unregister(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer]);
	}
	function _get_appContainer() {
	  if (isAirTemplate) {
	    return document.querySelector('.app__page');
	  }
	  return BX('content-table');
	}
	function _get_appHeader() {
	  if (isAirTemplate) {
	    return document.querySelector('.page__toolbar');
	  }
	  return document.querySelector('.page-header');
	}
	function _get_counterPanel() {
	  return booking_core.Core.getParams().counterPanelContainer.firstElementChild;
	}
	function _get_appContent() {
	  if (isAirTemplate) {
	    return BX('air-workarea-content');
	  }
	  return BX('workarea-content');
	}
	function _get_contentPaddingElement() {
	  var _BX;
	  if (isAirTemplate) {
	    return document.querySelector('.app__page');
	  }
	  return (_BX = BX('workarea')) == null ? void 0 : _BX.parentElement;
	}
	function _get_imBarWidth() {
	  if (isAirTemplate) {
	    return 0;
	  }
	  const imBar = isAirTemplate ? BX('right-bar') : BX('bx-im-bar');
	  return window.innerWidth - imBar.getBoundingClientRect().left;
	}

	const ScalePanel = {
	  props: {
	    getColumnsContainer: Function
	  },
	  data() {
	    return {
	      isSlider: booking_core.Core.getParams().isSlider,
	      maximize: new Maximize({
	        onOverlayClick: () => this.collapse()
	      }),
	      desiredZoom: this.$store.getters['interface/zoom'],
	      minZoom: 0.5,
	      maxZoom: 1
	    };
	  },
	  mounted() {
	    if (location.hash === '#maximize') {
	      void this.maximize.maximize();
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      zoom: 'interface/zoom',
	      expanded: 'interface/expanded'
	    }),
	    zoomFormatted() {
	      return this.loc('BOOKING_BOOKING_ZOOM_PERCENT', {
	        '#PERCENT#': Math.round(this.zoom * 100)
	      });
	    }
	  },
	  methods: {
	    expand(event) {
	      if (location.hash === '#maximize' || this.isAnyModifierKeyPressed(event)) {
	        void this.maximize.maximize();
	      } else {
	        window.open(`${location.href}#maximize`, '_blank').focus();
	      }
	    },
	    isAnyModifierKeyPressed(event) {
	      return event.altKey || event.shiftKey || event.ctrlKey || event.metaKey;
	    },
	    collapse() {
	      void this.maximize.minimize();
	    },
	    fitToScreen() {
	      const sidebarPadding = 260;
	      const view = this.getColumnsContainer();
	      const zoomCoefficient = (view.offsetWidth - sidebarPadding) / (view.scrollWidth - sidebarPadding);
	      const newZoom = Math.floor(this.zoom * zoomCoefficient * 10) / 10;
	      this.zoomInto(newZoom);
	    },
	    zoomInto(zoomInto) {
	      var _this$animation;
	      if (Number.isNaN(zoomInto)) {
	        return;
	      }
	      const noTransitionClass = '--booking-booking-no-transition';
	      const container = booking_core.Core.getParams().container;
	      const maxAnimationDuration = 400;
	      this.desiredZoom = Math.max(this.minZoom, Math.min(this.maxZoom, zoomInto));
	      if (this.zoom === this.desiredZoom) {
	        return;
	      }
	      (_this$animation = this.animation) == null ? void 0 : _this$animation.stop();
	      main_core.Dom.addClass(container, noTransitionClass);
	      this.animation = new BX.easing({
	        duration: Math.abs(this.zoom - this.desiredZoom) / this.minZoom * maxAnimationDuration,
	        start: {
	          zoom: this.zoom * 100
	        },
	        finish: {
	          zoom: this.desiredZoom * 100
	        },
	        step: ({
	          zoom
	        }) => this.$store.dispatch('interface/setZoom', zoom / 100),
	        complete: () => main_core.Dom.removeClass(container, noTransitionClass)
	      });
	      this.animation.animate();
	    },
	    async onMouseDown(direction) {
	      main_core.Event.unbind(window, 'mouseup', this.onMouseUp);
	      main_core.Event.bind(window, 'mouseup', this.onMouseUp);
	      this.mouseDown = true;
	      await new Promise(resolve => setTimeout(resolve, 50));
	      if (this.mouseDown) {
	        clearInterval(this.zoomInterval);
	        this.zoomInterval = setInterval(() => this.zoomInto(this.desiredZoom + direction * 0.1), 40);
	      }
	    },
	    onMouseUp() {
	      this.mouseDown = false;
	      if (this.desiredZoom > this.zoom) {
	        this.desiredZoom = Math.ceil(this.zoom * 10) / 10;
	      }
	      if (this.desiredZoom < this.zoom) {
	        this.desiredZoom = Math.floor(this.zoom * 10) / 10;
	      }
	      this.zoomInto(this.desiredZoom);
	      clearInterval(this.zoomInterval);
	      main_core.Event.unbind(window, 'mouseup', this.onMouseUp);
	    },
	    async showAhaMoment() {
	      booking_lib_ahaMoments.ahaMoments.setPopupShown(booking_const.AhaMoment.ExpandGrid);
	      await booking_lib_ahaMoments.ahaMoments.show({
	        id: 'booking-expand-grid',
	        title: this.loc('BOOKING_AHA_EXPAND_GRID_TITLE_MSGVER_1'),
	        text: this.loc('BOOKING_AHA_EXPAND_GRID_TEXT_MSGVER_1'),
	        target: this.$refs.expand,
	        top: true
	      });
	      booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.ExpandGrid);
	    }
	  },
	  template: `
		<div class="booking-booking-grid-scale-panel">
			<div v-if="!isSlider" class="booking-booking-grid-scale-panel-full-screen" ref="expand">
				<div v-if="expanded" class="ui-icon-set --collapse-diagonal" @click="collapse"></div>
				<div v-else class="ui-icon-set --expand-diagonal" @click="expand"></div>
			</div>
			<div class="booking-booking-grid-scale-panel-fit-to-screen">
				<div class="booking-booking-grid-scale-panel-fit-to-screen-text" @click="fitToScreen">
					{{ loc('BOOKING_BOOKING_SHOW_ALL') }}
				</div>
			</div>
			<div class="booking-booking-grid-scale-panel-change">
				<div
					class="ui-icon-set --minus-30"
					:class="{'--disabled': zoom <= minZoom}"
					@click="zoomInto(desiredZoom - 0.1)"
					@mousedown="onMouseDown(-1)"
				></div>
				<div v-html="zoomFormatted" class="booking-booking-grid-scale-panel-zoom"></div>
				<div
					class="ui-icon-set --plus-30"
					:class="{'--disabled': zoom >= maxZoom}"
					@click="zoomInto(desiredZoom + 0.1)"
					@mousedown="onMouseDown(1)"
				></div>
			</div>
		</div>
	`
	};

	const {
	  mapGetters: mapInterfaceGetters$3
	} = ui_vue3_vuex.createNamespacedHelpers(booking_const.Model.Interface);
	const {
	  mapGetters: mapFilterGetters$2
	} = ui_vue3_vuex.createNamespacedHelpers(booking_const.Model.Filter);

	// @vue/component
	const Calendar = {
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon,
	    CounterFloating: booking_component_counterFloating.CounterFloating
	  },
	  props: {
	    calendarClass: {
	      type: [String, Object, Array],
	      default: ''
	    }
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      calendarExpanded: `${booking_const.Model.Interface}/calendarExpanded`,
	      datesCount: `${booking_const.Model.Filter}/datesCount`
	    }),
	    ...mapInterfaceGetters$3({
	      freeMarks: 'freeMarks',
	      getCounterMarks: 'getCounterMarks',
	      offset: 'offset'
	    }),
	    ...mapFilterGetters$2({
	      filteredMarks: 'filteredMarks',
	      isFilterMode: 'isFilterMode',
	      isDeletingResourceFilterMode: 'isDeletingResourceFilterMode'
	    }),
	    selectedDateTs() {
	      return this.$store.getters[`${booking_const.Model.Interface}/selectedDateTs`] + this.offset;
	    },
	    viewDateTs() {
	      return this.$store.getters[`${booking_const.Model.Interface}/viewDateTs`] + this.offset;
	    },
	    counterMarks() {
	      if (this.isFilterMode || this.isDeletingResourceFilterMode) {
	        return this.getCounterMarks(this.filteredMarks);
	      }
	      return this.getCounterMarks();
	    },
	    formattedDate() {
	      const format = this.calendarExpanded ? this.loc('BOOKING_MONTH_YEAR_FORMAT') : main_date.DateTimeFormat.getFormat('LONG_DATE_FORMAT');
	      const timestamp = this.calendarExpanded ? this.viewDateTs / 1000 : this.selectedDateTs / 1000;
	      return main_date.DateTimeFormat.format(format, timestamp);
	    },
	    isShowCounterFloating() {
	      return (this.isDeletingResourceFilterMode || this.isFilterMode) && this.datesCount.count > 0;
	    }
	  },
	  watch: {
	    selectedDateTs(selectedDateTs) {
	      this.datePicker.selectDate(ui_datePicker.createDate(selectedDateTs));
	      this.updateMarks();
	    },
	    filteredMarks() {
	      this.updateMarks();
	    },
	    freeMarks() {
	      this.updateMarks();
	    },
	    counterMarks() {
	      this.setCounterMarks();
	    },
	    isFilterMode() {
	      this.updateMarks();
	    }
	  },
	  created() {
	    this.datePicker = new ui_datePicker.DatePicker({
	      selectedDates: [this.selectedDateTs],
	      inline: true,
	      hideHeader: true
	    });
	    this.setViewDate();
	    this.datePicker.subscribe(ui_datePicker.DatePickerEvent.SELECT, event => {
	      const date = event.getData().date;
	      const selectedDate = this.createDateFromUtc(date);
	      void this.$store.dispatch(`${booking_const.Model.Interface}/setSelectedDateTs`, selectedDate.getTime());
	      this.setViewDate();
	    });
	  },
	  mounted() {
	    this.datePicker.setTargetNode(this.$refs.datePicker);
	    this.datePicker.show();
	  },
	  beforeUnmount() {
	    this.datePicker.destroy();
	  },
	  methods: {
	    onPreviousClick() {
	      if (this.calendarExpanded) {
	        this.previousMonth();
	      } else {
	        this.previousDate();
	      }
	    },
	    onNextClick() {
	      if (this.calendarExpanded) {
	        this.nextMonth();
	      } else {
	        this.nextDate();
	      }
	    },
	    previousDate() {
	      const selectedDate = this.datePicker.getSelectedDate() || this.datePicker.getToday();
	      this.datePicker.selectDate(ui_datePicker.getNextDate(selectedDate, 'day', -1));
	      this.setViewDate();
	    },
	    nextDate() {
	      const selectedDate = this.datePicker.getSelectedDate() || this.datePicker.getToday();
	      this.datePicker.selectDate(ui_datePicker.getNextDate(selectedDate, 'day'));
	      this.setViewDate();
	    },
	    previousMonth() {
	      const viewDate = this.datePicker.getViewDate();
	      this.datePicker.setViewDate(ui_datePicker.getNextDate(viewDate, 'month', -1));
	      this.setViewDate();
	    },
	    nextMonth() {
	      const viewDate = this.datePicker.getViewDate();
	      this.datePicker.setViewDate(ui_datePicker.getNextDate(viewDate, 'month'));
	      this.setViewDate();
	    },
	    setViewDate() {
	      const viewDate = this.createDateFromUtc(this.datePicker.getViewDate());
	      const viewDateTs = viewDate.setDate(1);
	      void this.$store.dispatch(`${booking_const.Model.Interface}/setViewDateTs`, viewDateTs);
	    },
	    createDateFromUtc(date) {
	      return new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate());
	    },
	    updateMarks() {
	      if (this.isFilterMode || this.isDeletingResourceFilterMode) {
	        this.setFilterMarks();
	      } else {
	        this.setFreeMarks();
	      }
	      this.setCounterMarks();
	    },
	    setFreeMarks() {
	      const bgColorFree = 'rgba(var(--ui-color-background-success-rgb), 0.7)';
	      const dates = this.prepareDates(this.freeMarks);
	      this.datePicker.setDayColors([{
	        matcher: dates,
	        bgColor: bgColorFree
	      }]);
	    },
	    getFilterMarks() {
	      if (!this.isDeletingResourceFilterMode) {
	        return this.filteredMarks;
	      }
	      const today = new Date();
	      const todayTs = today.setHours(0, 0, 0, 0);
	      return this.filteredMarks.filter(freeMarkTs => new Date(freeMarkTs).getTime() >= todayTs);
	    },
	    setFilterMarks() {
	      const bgColorFilter = 'rgba(var(--ui-color-primary-rgb), 0.20)';
	      const dates = this.prepareDates(this.getFilterMarks());
	      this.datePicker.setDayColors([{
	        matcher: dates,
	        bgColor: bgColorFilter
	      }]);
	    },
	    setCounterMarks() {
	      const dates = this.prepareDates(this.counterMarks);
	      this.datePicker.setDayMarks([{
	        matcher: dates,
	        bgColor: 'red'
	      }]);
	    },
	    prepareDates(dates) {
	      return dates.map(markDate => {
	        const date = main_date.DateTimeFormat.parse(markDate, false, booking_const.DateFormat.ServerParse);
	        return this.prepareTimestamp(date.getTime());
	      });
	    },
	    prepareTimestamp(timestamp) {
	      const dateFormat = main_date.DateTimeFormat.getFormat('FORMAT_DATE');
	      return main_date.DateTimeFormat.format(dateFormat, timestamp / 1000);
	    },
	    async collapseToggle() {
	      await Promise.all([this.$store.dispatch(`${booking_const.Model.Interface}/setCalendarExpanded`, !this.calendarExpanded), booking_provider_service_optionService.optionService.setBool(booking_const.Option.CalendarExpanded, this.calendarExpanded)]);
	    }
	  },
	  template: `
		<div 
			class="booking-sidebar-calendar-container"
			:class="[calendarClass, {
				'--expanded': calendarExpanded,
				'--counter': isShowCounterFloating,
			}].flat(1)"
		>
			<div class="booking-booking-sidebar-calendar">
				<div class="booking-booking-sidebar-calendar-header">
					<div class="booking-sidebar-button" @click="onPreviousClick">
						<div class="ui-icon-set --chevron-left"></div>
					</div>
					<div class="booking-booking-sidebar-calendar-title">
						{{ formattedDate }}
					</div>
					<div class="booking-sidebar-button --right" @click="onNextClick">
						<div class="ui-icon-set --chevron-right"></div>
					</div>
					<div class="booking-sidebar-button" @click="collapseToggle">
						<Icon :name="calendarExpanded ? IconSet.COLLAPSE : IconSet.EXPAND_1"/>
					</div>
				</div>
				<div class="booking-booking-sidebar-calendar-date-picker" ref="datePicker"></div>
			</div>
			<CounterFloating
				v-if="isShowCounterFloating"
				:count="datesCount.count"
			/>
		</div>
	`
	};

	// @vue/component
	const WaitListLayout = {
	  name: 'WaitListLayout',
	  props: {
	    waitListItemsCount: {
	      type: Number,
	      default: 0
	    },
	    waitListClass: {
	      type: [String, Object, Array],
	      default: ''
	    },
	    showEmptyState: {
	      type: Boolean,
	      default: false
	    },
	    dragging: {
	      type: Boolean,
	      default: false
	    },
	    expanded: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['mouseUp'],
	  methods: {
	    showHelpDesk() {
	      if (!top.BX.Helper) {
	        return;
	      }
	      const anchor = booking_const.HelpDesk.WaitListDescription.anchorCode || null;
	      const params = {
	        redirect: 'detail',
	        code: booking_const.HelpDesk.WaitListDescription.code,
	        ...(anchor !== null && {
	          anchor
	        })
	      };
	      const queryString = Object.entries(params).map(([key, value]) => `${key}=${value}`).join('&');
	      top.BX.Helper.show(queryString);
	    }
	  },
	  template: `
		<div
			class="booking-wait-list"
			:class="waitListClass"
			@mouseup.capture="$emit('mouseUp', $event)"
		>
			<div class="booking-wait-list-header">
				<div class="booking-wait-list-title">{{ loc('BOOKING_BOOKING_WAIT_LIST') }}</div>
				<div v-if="waitListItemsCount > 0" class="booking--wait-list-title-count">{{ waitListItemsCount }}</div>
				<div class="booking-wait-list-buttons">
					<slot name="header" />
				</div>
			</div>
			<div
				v-if="expanded"
				class="booking--wait-list-content"
			>
				<div v-if="showEmptyState" class="booking-wait-list-empty">
					<div class="booking-wait-list-empty-icon"></div>
					<div class="booking-wait-list-empty-title">{{ loc('BOOKING_BOOKING_WAIT_LIST') }}</div>
					<div class="booking-wait-list-empty-subtitle">{{ loc('BOOKING_BOOKING_WAIT_LIST_DESCRIPTION') }}</div>
					<div
						class="booking-wait-list-empty-help"
						@click="showHelpDesk"
					>
						{{ loc('BOOKING_BOOKING_WAIT_LIST_HOW') }}
					</div>
				</div>
				<div v-if="dragging" class="booking-wait-list-drag-area">
					{{ loc('BOOKING_BOOKING_WAIT_LIST_DRAG_AREA') }}
				</div>
				<template v-else>
					<slot name="waitlist" />
				</template>
			</div>
		</div>
	`
	};

	const useDeleteWaitListGroup = () => {
	  const confirmDelete = async () => {
	    return new Promise(resolve => {
	      const messageBox = ui_dialogs_messagebox.MessageBox.create({
	        message: main_core.Loc.getMessage('BOOKING_BOOKING_WAIT_LIST_GROUP_CONFIRM_DELETE'),
	        yesCaption: main_core.Loc.getMessage('BOOKING_BOOKING_WAIT_LIST_GROUP_CONFIRM_DELETE_YES'),
	        modal: true,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	        onYes: () => {
	          messageBox.close();
	          resolve(true);
	        },
	        onCancel: () => {
	          messageBox.close();
	          resolve(false);
	        }
	      });
	      messageBox.show();
	    });
	  };
	  const deleteWaitListGroup = async waitListItemsIds => {
	    // eslint-disable-next-line @bitrix24/bitrix24-rules/no-native-dialogs
	    if (await confirmDelete()) {
	      await booking_provider_service_waitListService.waitListService.deleteList(waitListItemsIds);
	    }
	  };
	  return {
	    deleteWaitListGroup
	  };
	};

	// @vue/component
	const WaitListGroupMenu = {
	  name: 'WaitListGroupMenu',
	  props: {
	    waitListGroup: {
	      type: Object,
	      required: true
	    }
	  },
	  setup() {
	    const menuPopup = null;
	    const {
	      deleteWaitListGroup
	    } = useDeleteWaitListGroup();
	    return {
	      menuPopup,
	      deleteWaitListGroup
	    };
	  },
	  computed: {
	    popupId() {
	      return `wait-list-group-menu-${main_core.Text.getRandom(4)}`;
	    }
	  },
	  unmounted() {
	    if (this.menuPopup) {
	      this.destroy();
	    }
	  },
	  methods: {
	    openMenu() {
	      var _this$menuPopup, _this$menuPopup$popup;
	      if ((_this$menuPopup = this.menuPopup) != null && (_this$menuPopup$popup = _this$menuPopup.popupWindow) != null && _this$menuPopup$popup.isShown()) {
	        this.destroy();
	        return;
	      }
	      const menuButton = this.$refs['menu-button'];
	      this.menuPopup = main_popup.MenuManager.create(this.popupId, menuButton, this.getMenuItems(), {
	        className: 'booking--wait-list--wait-list-group-menu-popup',
	        closeByEsc: true,
	        autoHide: true,
	        offsetTop: -3,
	        offsetLeft: menuButton.offsetWidth - 6,
	        angle: true,
	        cacheable: true,
	        events: {
	          onDestroy: () => this.unbindScrollEvent()
	        }
	      });
	      this.menuPopup.show();
	      this.bindScrollEvent();
	    },
	    getMenuItems() {
	      return [{
	        html: `<span>${this.loc('BOOKING_BOOKING_WAIT_LIST_GROUP_DELETE')}</span>`,
	        onclick: async () => {
	          this.destroy();
	          await this.deleteWaitListGroup(this.waitListGroup.items.map(({
	            id
	          }) => id));
	        }
	      }];
	    },
	    // async deleteWaitListGroup(): Promise<void>
	    // {
	    // 	const waitListItemsIds = this.waitListGroup.items.map(({ id }) => id);
	    // 	await waitListService.deleteList(waitListItemsIds);
	    // },
	    destroy() {
	      main_popup.MenuManager.destroy(this.popupId);
	      this.unbindScrollEvent();
	    },
	    bindScrollEvent() {
	      main_core.Event.bind(document, 'scroll', this.adjustPosition, {
	        capture: true
	      });
	    },
	    unbindScrollEvent() {
	      main_core.Event.unbind(document, 'scroll', this.adjustPosition, {
	        capture: true
	      });
	    },
	    adjustPosition() {
	      var _this$menuPopup2, _this$menuPopup2$popu;
	      (_this$menuPopup2 = this.menuPopup) == null ? void 0 : (_this$menuPopup2$popu = _this$menuPopup2.popupWindow) == null ? void 0 : _this$menuPopup2$popu.adjustPosition();
	    }
	  },
	  template: `
		<button ref="menu-button" class="ui-icon-set --more" @click="openMenu"></button>
	`
	};

	// @vue/component
	const WaitListGroups = {
	  name: 'WaitListGroups',
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon,
	    WaitListGroupMenu
	  },
	  setup() {
	    const dragManager = null;
	    return {
	      dragManager,
	      IconSet: ui_iconSet_api_vue.Set
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      editingWaitListItemId: `${booking_const.Model.Interface}/editingWaitListItemId`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`,
	      animationPause: `${booking_const.Model.Interface}/animationPause`,
	      waitListItems: `${booking_const.Model.WaitList}/get`
	    }),
	    waitListGroups() {
	      const groups = [{
	        title: this.loc('BOOKING_BOOKING_WAIT_LIST_ADDED_TODAY'),
	        items: []
	      }, {
	        title: this.loc('BOOKING_BOOKING_WAIT_LIST_ADDED_THIS_WEEK'),
	        items: []
	      }, {
	        title: this.loc('BOOKING_BOOKING_WAIT_LIST_ADDED_THIS_MONTH'),
	        items: []
	      }, {
	        title: this.loc('BOOKING_BOOKING_WAIT_LIST_ADDED_OVER_MONTH'),
	        items: []
	      }];
	      const today = new Date().setHours(0, 0, 0, 0);
	      const {
	        d: dayDuration,
	        w: weekDuration,
	        m: monthDuration
	      } = booking_lib_duration.Duration.getUnitDurations();
	      [...this.waitListItems].sort((a, b) => b.createdAt - a.createdAt).forEach(waitListItem => {
	        const duration = today - new Date(waitListItem.createdAt).setHours(0, 0, 0, 0);
	        if (duration < dayDuration) {
	          groups[0].items.push(waitListItem);
	        } else if (duration < weekDuration) {
	          groups[1].items.push(waitListItem);
	        } else if (duration < monthDuration) {
	          groups[2].items.push(waitListItem);
	        } else {
	          groups[3].items.push(waitListItem);
	        }
	      });
	      return groups.filter(({
	        items
	      }) => items.length > 0);
	    }
	  },
	  template: `
		<div class="booking-wait-list-groups">
			<div v-for="group of waitListGroups" :key="group.title" class="booking-wait-list-group">
				<div class="booking-wait-list-group-header">
					<div class="booking-wait-list-group-title">{{ group.title }}</div>
					<div class="booking-sidebar-button">
						<WaitListGroupMenu :waitListGroup="group"/>
					</div>
				</div>
				<TransitionGroup :name="animationPause ? 'none' : 'wait-list'" tag="div">
					<template v-for="item of group.items" :key="item.id">
						<slot name="item" :item="item"/>
					</template>
				</TransitionGroup>
			</div>
		</div>
	`
	};

	// @vue/component
	const WaitListItemName = {
	  name: 'WaitListItemName',
	  components: {
	    Name: booking_component_booking.Name
	  },
	  props: {
	    /**
	     * @type {WaitListItemModel}
	     */
	    waitListItem: {
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    client() {
	      var _this$waitListItem$cl;
	      const clientData = ((_this$waitListItem$cl = this.waitListItem.clients) == null ? void 0 : _this$waitListItem$cl[0]) || null;
	      return clientData ? this.$store.getters[`${booking_const.Model.Clients}/getByClientData`](clientData) : null;
	    },
	    itemName() {
	      var _this$client;
	      return ((_this$client = this.client) == null ? void 0 : _this$client.name) || this.waitListItem.name || this.loc('BOOKING_BOOKING_DEFAULT_BOOKING_NAME');
	    }
	  },
	  template: `
		<Name
			:name="itemName"
			:dataAttributes="{
				'data-element': 'wait-list-item-name',
				'data-id': waitListItem.id,
			}"
		/>
	`
	};

	// @vue/component
	const WaitListItemNote = {
	  name: 'WaitListItemNote',
	  components: {
	    Note: booking_component_booking.Note
	  },
	  props: {
	    /**
	     * @type {WaitListItemModel}
	     */
	    waitListItem: {
	      type: Object,
	      required: true
	    },
	    bindElement: {
	      type: Function,
	      required: true
	    },
	    visiblePopup: {
	      type: Boolean,
	      default: false
	    }
	  },
	  watch: {
	    visiblePopup(visible) {
	      if (visible) {
	        var _this$$refs$note;
	        (_this$$refs$note = this.$refs.note) == null ? void 0 : _this$$refs$note.showViewPopup();
	      } else {
	        var _this$$refs$note2;
	        (_this$$refs$note2 = this.$refs.note) == null ? void 0 : _this$$refs$note2.closeViewPopup();
	      }
	    }
	  },
	  methods: {
	    async saveWaitListItemNote({
	      note
	    }) {
	      const id = this.waitListItem.id;
	      await booking_provider_service_waitListService.waitListService.update({
	        id,
	        note
	      });
	    }
	  },
	  template: `
		<Note
			ref="note"
			:id="waitListItem.id"
			:note="waitListItem.note"
			:bindElement
			className="booking--wait-list-item-note"
			:dataId="waitListItem.id"
			dataElementPrefix="wait-list-item"
			:dataAttributes="{
				'data-id': waitListItem.id,
				'data-element': 'booking-wait-list-item-note-button',
			}"
			@save="saveWaitListItemNote"
		/>
	`
	};

	// @vue/component
	const WaitListItemProfit = {
	  name: 'WaitListItemProfit',
	  components: {
	    Profit: booking_component_booking.Profit
	  },
	  props: {
	    /**
	     * @type {WaitListItemModel}
	     */
	    waitListItem: {
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    deal() {
	      var _this$waitListItem$ex, _this$waitListItem$ex2;
	      return (_this$waitListItem$ex = (_this$waitListItem$ex2 = this.waitListItem.externalData) == null ? void 0 : _this$waitListItem$ex2.find(data => data.entityTypeId === booking_const.CrmEntity.Deal)) != null ? _this$waitListItem$ex : null;
	    }
	  },
	  template: `
		<Profit
			:deal
			:dataAttributes="{
				'data-id': waitListItem.id,
				'data-element': 'booking-wait-list-item-profit'
			}"
		/>
	`
	};

	// @vue/component
	const WaitListItemClient = {
	  name: 'WaitListItemClient',
	  components: {
	    Client: booking_component_actionsPopup.Client
	  },
	  props: {
	    waitListItemId: {
	      type: Number,
	      required: true
	    }
	  },
	  emits: ['freeze', 'unfreeze'],
	  computed: {
	    waitListItem() {
	      return this.$store.getters[`${booking_const.Model.WaitList}/getById`](this.waitListItemId);
	    }
	  },
	  methods: {
	    async addClients({
	      clients
	    }) {
	      await booking_provider_service_waitListService.waitListService.update({
	        id: this.waitListItem.id,
	        clients
	      });
	    },
	    async updateClients({
	      clients
	    }) {
	      await booking_provider_service_waitListService.waitListService.update({
	        id: this.waitListItem.id,
	        clients
	      });
	    },
	    async updateNote({
	      note
	    }) {
	      await booking_provider_service_waitListService.waitListService.update({
	        id: this.waitListItem.id,
	        note
	      });
	    }
	  },
	  template: `
		<Client
			:id="waitListItemId"
			:primaryClientData="waitListItem.clients?.[0] || null"
			:clients="waitListItem.clients"
			:note="waitListItem.note"
			:dataId="waitListItemId"
			dataElementPrefix="wait-list-item"
			:dataAttributes="{
				'data-wait-list-item-id': waitListItemId,
			}"
			@freeze="$emit('freeze')"
			@unfreeze="$emit('unfreeze')"
			@addClients="addClients"
			@updateClients="updateClients"
			@updateNote="updateNote"
		/>
	`
	};

	// @vue/component
	const WaitListItemConfirmation = {
	  name: 'WaitListItemConfirmation',
	  components: {
	    Confirmation: booking_component_actionsPopup.Confirmation
	  },
	  props: {
	    waitListItemId: {
	      type: Number,
	      required: true
	    }
	  },
	  emits: ['freeze', 'unfreeze'],
	  template: `
		<Confirmation
			:id="waitListItemId"
			:isConfirmed="false"
			:counters="[]"
			disabled
			:dataId="waitListItemId"
			dataPre
		/>
	`
	};

	// @vue/component
	const WaitListItemDeal = {
	  name: 'WaitListItemDeal',
	  components: {
	    Deal: booking_component_actionsPopup.Deal
	  },
	  props: {
	    waitListItemId: {
	      type: Number,
	      required: true
	    }
	  },
	  emits: ['freeze', 'unfreeze'],
	  setup(props) {
	    const dealHelper = new booking_lib_dealHelper.WaitListDealHelper(props.waitListItemId);
	    return {
	      dealHelper
	    };
	  },
	  computed: {
	    waitListItem() {
	      return this.$store.getters[`${booking_const.Model.WaitList}/getById`](this.waitListItemId);
	    },
	    deal() {
	      var _this$waitListItem$ex, _this$waitListItem, _this$waitListItem$ex2;
	      return (_this$waitListItem$ex = (_this$waitListItem = this.waitListItem) == null ? void 0 : (_this$waitListItem$ex2 = _this$waitListItem.externalData) == null ? void 0 : _this$waitListItem$ex2.find(data => data.entityTypeId === booking_const.CrmEntity.Deal)) != null ? _this$waitListItem$ex : null;
	    }
	  },
	  template: `
		<Deal
			:deal="deal"
			:dealHelper="dealHelper"
			:dataId="waitListItemId"
			:dataAttributes="{
				'data-wait-list-item-id': waitListItemId,
			}"
			dataElementPrefix="wait-list"
			@freeze="$emit('freeze')"
			@unfreeze="$emit('unfreeze')"
		/>
	`
	};

	// @vue/component
	const WaitListItemDocument = {
	  name: 'WaitListItemDocument',
	  components: {
	    Document: booking_component_actionsPopup.Document
	  },
	  props: {
	    waitListItemId: {
	      type: Number,
	      required: true
	    }
	  },
	  setup() {
	    const isLoading = ui_vue3.ref(true);
	    ui_vue3.onMounted(() => {
	      isLoading.value = false;
	    });
	    return {
	      isLoading
	    };
	  },
	  template: `
		<Document
			:id="waitListItemId"
			:loading="isLoading"
			disabled
		/>
	`
	};

	// @vue/component
	const WaitListItemMessage = {
	  name: 'WaitListItemMessage',
	  components: {
	    Message: booking_component_actionsPopup.Message
	  },
	  props: {
	    waitListItemId: {
	      type: Number,
	      required: true
	    }
	  },
	  template: `
		<Message
			:id="waitListItemId"
			:clientData="null"
			:loading="false"
			disabled
			:dataId="waitListItemId"
			dataElementPrefix="wait-list"
		/>
	`
	};

	// @vue/component
	const WaitListItemRemove = {
	  name: 'WaitListItemRemove',
	  components: {
	    RemoveButton: booking_component_actionsPopup.RemoveButton
	  },
	  props: {
	    waitListItemId: {
	      type: Number,
	      required: true
	    }
	  },
	  emits: ['close'],
	  methods: {
	    remove() {
	      new booking_lib_removeWaitListItem.RemoveWaitListItem(this.waitListItemId);
	      this.$emit('close');
	    }
	  },
	  template: `
		<RemoveButton
			showLabel
			:dataAttributes="{
				'data-id': waitListItemId,
				'data-element': 'booking-wait-list-item-menu-remove-button'
			}"
			@remove="remove"
		/>
	`
	};

	// @vue/component
	const WaitListItemVisit = {
	  name: 'WaitListItemVisit',
	  components: {
	    Visit: booking_component_actionsPopup.Visit
	  },
	  props: {
	    waitListItemId: {
	      type: Number,
	      required: true
	    }
	  },
	  computed: {
	    waitListItem() {
	      return this.$store.getters[`${booking_const.Model.WaitList}/getById`](this.waitListItemId);
	    }
	  },
	  template: `
		<Visit
			:id="waitListItem.id"
			:hasClients="waitListItem.clients.length > 0"
			visitStatus="0"
			disabled
			:dataId="waitListItem.id"
			dataElementPrefix="wait-list"
		/>
	`
	};

	const ActionsPopupActionEnum$1 = Object.freeze({
	  client: 'client',
	  confirmation: 'confirmation',
	  deal: 'deal',
	  document: 'document',
	  fullForm: 'fullForm',
	  message: 'message',
	  visit: 'visit',
	  overbooking: 'overbooking',
	  remove: 'remove',
	  waitList: 'waitList'
	});

	// @vue/component
	const WaitListItemActionsPopup = {
	  name: 'WaitListItemActionsPopup',
	  components: {
	    ActionsPopup: booking_component_actionsPopup.ActionsPopup,
	    FullForm: booking_component_actionsPopup.FullForm,
	    WaitListItemClient,
	    WaitListItemConfirmation,
	    WaitListItemDeal,
	    WaitListItemDocument,
	    WaitListItemMessage,
	    WaitListItemRemove,
	    WaitListItemVisit
	  },
	  props: {
	    /**
	     * @type {WaitListItemModel}
	     */
	    waitListItem: {
	      type: Object,
	      required: true
	    },
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    }
	  },
	  emits: ['close'],
	  computed: {
	    config() {
	      return {
	        offsetTop: -100,
	        offsetLeft: -500,
	        className: 'booking--wait-list--wait-list-item--sticky-popup'
	      };
	    },
	    contentStructure() {
	      const waitListItemId = this.waitListItem.id;
	      return [{
	        id: ActionsPopupActionEnum$1.client,
	        props: {
	          waitListItemId
	        },
	        component: WaitListItemClient
	      }, [{
	        id: ActionsPopupActionEnum$1.deal,
	        props: {
	          waitListItemId
	        },
	        component: WaitListItemDeal
	      }, {
	        id: ActionsPopupActionEnum$1.document,
	        props: {
	          waitListItemId
	        },
	        component: WaitListItemDocument
	      }], [{
	        id: ActionsPopupActionEnum$1.fullForm,
	        props: {
	          waitListItemId
	        },
	        component: booking_component_actionsPopup.FullForm
	      }, {
	        id: ActionsPopupActionEnum$1.info,
	        class: '--shrink',
	        props: {
	          waitListItemId
	        },
	        component: booking_component_actionsPopup.Info
	      }]];
	    }
	  },
	  // language=Vue
	  template: `
		<ActionsPopup
			:popupId="waitListItem.id"
			:bindElement="bindElement"
			:contentStructure="contentStructure"
			:popupOptions="config"
			@close="$emit('close')"
		>
			<template #footer>
				<WaitListItemRemove
					:waitListItemId="waitListItem.id"
					@close="$emit('close')"
				/>
			</template>
		</ActionsPopup>
	`
	};

	// @vue/component
	const WaitListItemActions = {
	  name: 'WaitListItemActions',
	  components: {
	    WaitListItemActionsPopup
	  },
	  props: {
	    /**
	     * @type {WaitListItemModel}
	     */
	    waitListItem: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {
	      showPopup: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      editingWaitListItemId: `${booking_const.Model.Interface}/editingWaitListItemId`,
	      isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`
	    })
	  },
	  mounted() {
	    if (this.isEditingBookingMode && this.editingWaitListItemId === this.waitListItem.id) {
	      this.showPopup = true;
	    }
	  },
	  methods: {
	    clickHandler() {
	      this.showPopup = !this.showPopup;
	    }
	  },
	  template: `
		<div
			ref="node"
			class="booking-booking-booking-actions booking--wait-list-item-actions"
			data-element="booking-wait-list-item-actions-button"
			:data-id="waitListItem.id"
			@click="clickHandler"
		>
			<div class="booking-booking-booking-actions-inner">
				<div class="ui-icon-set --chevron-down"></div>
			</div>
		</div>
		<WaitListItemActionsPopup
			v-if="showPopup"
			:waitListItem
			:bindElement="this.$refs.node"
			@close="showPopup = false"
		/>
	`
	};

	// @vue/component
	const WaitListItemAddClient = {
	  name: 'WaitListItemAddClient',
	  components: {
	    AddClient: booking_component_booking.AddClient
	  },
	  props: {
	    /**
	     * @type {WaitListItemModel}
	     */
	    waitListItem: {
	      type: Object,
	      required: true
	    }
	  },
	  methods: {
	    async addClient(clients) {
	      const id = this.waitListItem.id;
	      await booking_provider_service_waitListService.waitListService.update({
	        id,
	        clients
	      });
	    }
	  },
	  template: `
		<AddClient
			:dataAttributes="{
				'data-id': waitListItem.id,
				'data-element': 'booking-wait-list-item-add-client-button',
			}"
			:popupOffsetLeft="-300"
			@add="addClient"
		/>
	`
	};

	// @vue/component
	const WaitListItemCrmButton = {
	  name: 'WaitListItemCrmButton',
	  components: {
	    CrmButton: booking_component_booking.CrmButton
	  },
	  props: {
	    /**
	     * @type {WaitListItemModel}
	     */
	    waitListItem: {
	      type: Object,
	      required: true
	    }
	  },
	  setup(props) {
	    const dealHelper = new booking_lib_dealHelper.WaitListDealHelper(props.waitListItem.id);
	    return {
	      dealHelper
	    };
	  },
	  template: `
		<CrmButton
			:dealHelper
			:dataAttributes="{
				'data-wait-list-item-id': waitListItem.id,
				'data-element': 'wait-list-item-crm-button'
			}"
		/>
	`
	};

	// @vue/component
	const WaitListItem = {
	  name: 'WaitListItem',
	  components: {
	    BookingBase: booking_component_booking.BookingBase,
	    Communication: booking_component_booking.Communication,
	    DisabledPopup: booking_component_booking.DisabledPopup,
	    WaitListItemName,
	    WaitListItemNote,
	    WaitListItemProfit,
	    WaitListItemActions,
	    WaitListItemAddClient,
	    WaitListItemCrmButton
	  },
	  props: {
	    /**
	     * @type {WaitListItemModel}
	     */
	    item: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {
	      isDisabledPopupShown: false,
	      visibleNotePopup: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      editingWaitListItemId: `${booking_const.Model.Interface}/editingWaitListItemId`,
	      isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`,
	      isWaitListItemCreatedFromEmbed: `${booking_const.Model.Interface}/isWaitListItemCreatedFromEmbed`,
	      animationPause: `${booking_const.Model.Interface}/animationPause`
	    }),
	    hasClient() {
	      return this.item.clients.length > 0;
	    },
	    disabled() {
	      return this.isEditingBookingMode && this.editingWaitListItemId !== this.item.id;
	    },
	    dataAttributes() {
	      return {
	        'data-id': this.item.id,
	        'data-kind': booking_const.DraggedElementKind.WaitListItem,
	        'data-element': 'booking-wait-list-item'
	      };
	    },
	    hasAccent() {
	      return this.editingWaitListItemId === this.item.id || this.isWaitListItemCreatedFromEmbed(this.item.id);
	    }
	  },
	  methods: {
	    onClick() {
	      if (this.disabled) {
	        this.isDisabledPopupShown = true;
	      }
	    },
	    onNoteMouseEnter() {
	      this.showNoteTimeout = setTimeout(() => {
	        this.visibleNotePopup = true;
	      }, 100);
	    },
	    onNoteMouseLeave() {
	      clearTimeout(this.showNoteTimeout);
	      this.visibleNotePopup = false;
	    }
	  },
	  template: `
		<BookingBase
			:disabled
			:bookingClass="{
				'booking--draggable-item': true,
				'booking-wait-list-item': true,
				'--disabled': disabled,
				'--accent': hasAccent,
				'no-transition': animationPause,
			}"
			:dataAttributes="dataAttributes"
			@click="onClick"
		>
			<template #upper-content-row>
				<div
					class="booking-booking-booking-name-container"
					@mouseenter="onNoteMouseEnter"
					@mouseleave="onNoteMouseLeave"
				>
					<WaitListItemName :waitListItem="item"/>
					<WaitListItemNote
						:waitListItem="item"
						:bindElement="() => $el"
						:visiblePopup="visibleNotePopup"
					/>
				</div>
				<WaitListItemProfit :waitListItem="item"/>
			</template>
			<template #lower-content-row>
				<div class="booking--wait-list-item--space"></div>
				<div v-if="hasClient" class="booking--booking-base-buttons">
					<Communication/>
					<WaitListItemCrmButton :waitListItem="item"/>
				</div>
				<WaitListItemAddClient
					v-else
					:waitListItem="item"
				/>
			</template>
			<template #end>
				<DisabledPopup
					v-if="isDisabledPopupShown"
					:popupId="'booking-wait-list-item-disabled-popup-' + item.id"
					:bindElement="() => $el"
					@close="isDisabledPopupShown = false"
				/>
			</template>
			<template #actions>
				<WaitListItemActions :waitListItem="item"/>
			</template>
		</BookingBase>
	`
	};

	// @vue/component
	const ButtonAddWaitListItem = {
	  name: 'ButtonAddWaitListItem',
	  components: {
	    Button: booking_component_button.Button
	  },
	  setup() {
	    return {
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonSize: booking_component_button.ButtonSize
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      embedItems: `${booking_const.Model.Interface}/embedItems`,
	      isLoaded: `${booking_const.Model.Interface}/isLoaded`,
	      isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`,
	      waitListItems: `${booking_const.Model.WaitList}/get`,
	      waitListExpanded: `${booking_const.Model.Interface}/waitListExpanded`
	    }),
	    addButtonText() {
	      return this.isLoaded && this.waitListItems.length === 0 ? this.loc('BOOKING_BOOKING_WAIT_LIST_ADD').replace('[plus]', '+') : '+';
	    },
	    disabled() {
	      return this.isEditingBookingMode || !this.isLoaded;
	    },
	    clients() {
	      const clients = this.embedItems.filter(item => {
	        return item.entityTypeId === 'CONTACT' || item.entityTypeId === 'COMPANY';
	      });
	      return clients.map(item => {
	        return {
	          id: item.value,
	          type: {
	            code: item.entityTypeId,
	            module: item.moduleId
	          }
	        };
	      });
	    }
	  },
	  methods: {
	    async addWaitListItem() {
	      if (this.disabled || !this.isLoaded) {
	        return;
	      }
	      if (!this.waitListExpanded) {
	        await this.expandWaitListWidget();
	      }
	      const now = Date.now();
	      const id = `tmp-id-${now}-${main_core.Text.getRandom(4)}`;
	      await this.addCreatedFromEmbedWaitListItem(id);
	      const result = await booking_provider_service_waitListService.waitListService.add({
	        id,
	        clients: this.clients,
	        externalData: this.embedItems,
	        createdAt: now,
	        updatedAt: now
	      });
	      if (result.success && result.waitListItem) {
	        booking_lib_analytics.WaitListAnalytics.sendAddBooking();
	        await this.addCreatedFromEmbedWaitListItem(result.waitListItem.id);
	      }
	    },
	    async addCreatedFromEmbedWaitListItem(id) {
	      await this.$store.dispatch(`${booking_const.Model.Interface}/addCreatedFromEmbedWaitListItem`, id);
	    },
	    async expandWaitListWidget() {
	      await this.$store.commit('interface/setWaitListExpanded', true);
	    }
	  },
	  template: `
		<Button
			class="booking-wait-list-add"
			:text="addButtonText"
			:size="ButtonSize.EXTRA_SMALL"
			:color="ButtonColor.SECONDARY_LIGHT"
			:disabled
			:round="true"
			@click="addWaitListItem"
		/>
	`
	};

	// @vue/component
	const WaitList = {
	  name: 'WaitList',
	  components: {
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon,
	    ButtonAddWaitListItem,
	    WaitListLayout,
	    WaitListGroups,
	    WaitListItem
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonSize: booking_component_button.ButtonSize
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      getBookingById: `${booking_const.Model.Bookings}/getById`,
	      waitListItems: `${booking_const.Model.WaitList}/get`,
	      waitListExpanded: `${booking_const.Model.Interface}/waitListExpanded`,
	      draggedBookingId: `${booking_const.Model.Interface}/draggedBookingId`,
	      draggedBookingResourceId: `${booking_const.Model.Interface}/draggedBookingResourceId`,
	      editingBookingId: `${booking_const.Model.Interface}/editingBookingId`,
	      editingWaitListItemId: `${booking_const.Model.Interface}/editingWaitListItemId`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`,
	      isBookingCreatedFromEmbed: `${booking_const.Model.Interface}/isBookingCreatedFromEmbed`,
	      embedItems: `${booking_const.Model.Interface}/embedItems`,
	      getResourceById: `${booking_const.Model.Resources}/getById`
	    }),
	    isEmpty() {
	      return this.waitListItems.length === 0;
	    },
	    isAvailableToDrop() {
	      var _this$getResourceById;
	      return Boolean(this.draggedBookingId && !((_this$getResourceById = this.getResourceById(this.draggedBookingResourceId)) != null && _this$getResourceById.isDeleted));
	    },
	    showEmptyState() {
	      return this.isEmpty && !this.draggedBookingId;
	    },
	    embedEditingMode() {
	      var _this$embedItems$leng, _this$embedItems;
	      return this.isFeatureEnabled && (this.editingBookingId > 0 || this.editingWaitListItemId > 0 || ((_this$embedItems$leng = (_this$embedItems = this.embedItems) == null ? void 0 : _this$embedItems.length) != null ? _this$embedItems$leng : 0) > 0);
	    }
	  },
	  watch: {
	    // wait list can be expanded externally (currently in ButtonAddWaitListItem)
	    async waitListExpanded(newValue, oldValue) {
	      if (newValue !== oldValue) {
	        await booking_provider_service_optionService.optionService.setBool(booking_const.Option.WaitListExpanded, newValue);
	      }
	    }
	  },
	  methods: {
	    async onMouseUp() {
	      if (!this.draggedBookingId) {
	        return;
	      }
	      const bookingId = this.draggedBookingId;
	      await this.$store.dispatch(`${booking_const.Model.Interface}/addDeletingBooking`, bookingId);
	      if (booking_lib_isRealId.isRealId(bookingId)) {
	        if (this.editingBookingId === bookingId) {
	          await this.setEditingWaitListItemId(bookingId);
	        }
	        const booking = this.getBookingById(bookingId);
	        const result = await booking_provider_service_waitListService.waitListService.createFromBooking(bookingId, {
	          id: bookingId,
	          clients: booking.clients,
	          primaryClient: booking.primaryClient,
	          externalData: booking.externalData,
	          createdAt: Date.now(),
	          updatedAt: Date.now()
	        });
	        if (result.success && result.waitListItem) {
	          booking_lib_analytics.BookingAnalytics.sendAddWaitListItem();
	          if (this.editingWaitListItemId === bookingId) {
	            await this.setEditingWaitListItemId(result.waitListItem.id);
	          }
	        }
	      } else {
	        if (this.isBookingCreatedFromEmbed(bookingId)) {
	          await this.addCreatedFromEmbedWaitListItem(bookingId);
	        }
	        const result = await booking_provider_service_waitListService.waitListService.add({
	          id: bookingId,
	          clients: [],
	          createdAt: Date.now(),
	          updatedAt: Date.now()
	        });
	        if (result.success && result.waitListItem) {
	          await this.addCreatedFromEmbedWaitListItem(result.waitListItem.id);
	        }
	      }
	    },
	    async addCreatedFromEmbedWaitListItem(id) {
	      await this.$store.dispatch(`${booking_const.Model.Interface}/addCreatedFromEmbedWaitListItem`, id);
	    },
	    async setEditingWaitListItemId(id) {
	      await Promise.all([this.$store.dispatch(`${booking_const.Model.Interface}/setEditingWaitListItemId`, id), this.$store.dispatch(`${booking_const.Model.Interface}/setEditingBookingId`, null)]);
	    },
	    async collapseToggle() {
	      await this.$store.dispatch(`${booking_const.Model.Interface}/setWaitListExpanded`, !this.waitListExpanded);
	    }
	  },
	  template: `
		<WaitListLayout
			:dragging="isAvailableToDrop"
			:showEmptyState
			:expanded="waitListExpanded"
			:waitListItemsCount="waitListItems.length"
			:waitListClass="{
				'--expand': waitListExpanded,
				'embed-editing-mode': embedEditingMode,
			}"
			@mouseUp="onMouseUp"
		>
			<template #header>
				<ButtonAddWaitListItem/>
				<div class="booking-sidebar-button" @click="collapseToggle">
					<Icon :name="waitListExpanded ? IconSet.COLLAPSE : IconSet.EXPAND_1"/>
				</div>
			</template>
			<template #waitlist>
				<WaitListGroups>
					<template #item="{ item }">
						<WaitListItem :item/>
					</template>
				</WaitListGroups>
			</template>
		</WaitListLayout>
	`
	};

	const Sidebar = {
	  components: {
	    Calendar,
	    WaitList
	  },
	  template: `
		<div class="booking-booking-sidebar">
			<Calendar/>
			<WaitList/>
		</div>
	`
	};

	const DragDelete = {
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      draggedDataTransfer: `${booking_const.Model.Interface}/draggedDataTransfer`
	    })
	  },
	  methods: {
	    onMouseUp() {
	      if (this.draggedDataTransfer.kind === booking_const.DraggedElementKind.Booking) {
	        new booking_lib_removeBooking.RemoveBooking(this.draggedDataTransfer.id);
	        return;
	      }
	      if (this.draggedDataTransfer.kind === booking_const.DraggedElementKind.WaitListItem) {
	        new booking_lib_removeWaitListItem.RemoveWaitListItem(this.draggedDataTransfer.id);
	      }
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<div v-if="draggedDataTransfer.id > 0" class="booking-booking-drag-delete">
			<div
				class="booking-booking-drag-delete-button"
				data-element="booking-drag-delete"
				@mouseup.capture="onMouseUp"
			>
				<Icon :name="IconSet.TRASH_BIN"/>
				<div class="booking-booking-drag-delete-button-text">
					{{ loc('BOOKING_BOOKING_DRAG_DELETE') }}
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const Grid = {
	  name: 'BookingsGrid',
	  components: {
	    LeftPanel,
	    NowLine,
	    Column,
	    Bookings,
	    ScalePanel,
	    Sidebar,
	    DragDelete
	  },
	  data() {
	    return {
	      scrolledToBooking: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      resourcesIds: `${booking_const.Model.Interface}/resourcesIds`,
	      scroll: `${booking_const.Model.Interface}/scroll`,
	      editingBookingId: `${booking_const.Model.Interface}/editingBookingId`,
	      editingWaitListItemId: `${booking_const.Model.Interface}/editingWaitListItemId`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`,
	      isLoaded: `${booking_const.Model.Interface}/isLoaded`,
	      selectedDateTs: `${booking_const.Model.Interface}/selectedDateTs`,
	      filteredBookingsIds: `${booking_const.Model.Filter}/filteredBookingsIds`,
	      isFilterMode: `${booking_const.Model.Filter}/isFilterMode`
	    }),
	    editingBooking() {
	      var _this$$store$getters$;
	      return (_this$$store$getters$ = this.$store.getters['bookings/getById'](this.editingBookingId)) != null ? _this$$store$getters$ : null;
	    }
	  },
	  watch: {
	    scroll(value) {
	      this.$refs.columnsContainer.scrollLeft = value;
	    },
	    editingBooking() {
	      this.scrollToEditingBooking();
	    },
	    isLoaded(isLoaded) {
	      if (isLoaded) {
	        let dataId = null;
	        let dataKind = null;
	        if (this.editingBookingId) {
	          dataId = this.editingBookingId;
	          dataKind = booking_const.DraggedElementKind.Booking;
	        }
	        if (this.editingWaitListItemId) {
	          dataId = this.editingWaitListItemId;
	          dataKind = booking_const.DraggedElementKind.WaitListItem;
	        }
	        this.initDragManager(dataId, dataKind);
	      }
	    },
	    editingBookingId(id) {
	      if (id) {
	        this.initDragManager(id, booking_const.DraggedElementKind.Booking);
	      }
	    },
	    editingWaitListItemId(id) {
	      if (id) {
	        this.initDragManager(id, booking_const.DraggedElementKind.WaitListItem);
	      }
	    },
	    filteredBookingsIds(ids) {
	      var _this$$store$getters$2;
	      if (!this.isFilterMode || ids.length === 0) {
	        return;
	      }
	      const booking = (_this$$store$getters$2 = this.$store.getters['bookings/getById'](ids[0])) != null ? _this$$store$getters$2 : null;
	      if (booking !== null) {
	        this.scrollToBooking(booking);
	      }
	    }
	  },
	  mounted() {
	    this.ears = new ui_ears.Ears({
	      container: this.$refs.columnsContainer,
	      smallSize: true,
	      className: 'booking-booking-grid-columns-ears'
	    }).init();
	    main_core_events.EventEmitter.subscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
	    main_core_events.EventEmitter.subscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
	  },
	  beforeUnmount() {
	    var _this$dragManager;
	    (_this$dragManager = this.dragManager) == null ? void 0 : _this$dragManager.destroy();
	  },
	  unmounted() {
	    main_core_events.EventEmitter.unsubscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
	    main_core_events.EventEmitter.unsubscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
	  },
	  methods: {
	    updateEars() {
	      this.ears.toggleEars();
	      this.tryShowAhaMoment();
	    },
	    areEarsShown() {
	      const shownClass = 'ui-ear-show';
	      return main_core.Dom.hasClass(this.ears.getRightEar(), shownClass) || main_core.Dom.hasClass(this.ears.getLeftEar(), shownClass);
	    },
	    scrollToEditingBooking() {
	      if (!this.editingBooking || this.scrolledToBooking) {
	        return;
	      }
	      this.scrollToBooking(this.editingBooking);
	    },
	    scrollToBooking(booking) {
	      const top = booking_lib_grid.grid.calculateTop(booking.dateFromTs);
	      const height = booking_lib_grid.grid.calculateHeight(booking.dateFromTs, booking.dateToTs);
	      this.$refs.inner.scrollTop = top + height / 2 + this.$refs.inner.offsetHeight / 2;
	      this.scrolledToBooking = true;
	    },
	    tryShowAhaMoment() {
	      if (this.areEarsShown() && booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.ExpandGrid)) {
	        main_core.Event.EventEmitter.unsubscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
	        main_core.Event.EventEmitter.unsubscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
	        void this.$refs.scalePanel.showAhaMoment();
	      }
	    },
	    initDragManager(id = '', kind = null) {
	      if (this.isFeatureEnabled) {
	        const dataId = id ? `[data-id="${id}"]` : '';
	        const dataKind = kind ? `[data-kind="${kind}"]` : '';
	        this.dragManager = new booking_lib_drag.Drag({
	          container: this.$el.parentElement,
	          draggable: `.booking--draggable-item${dataId}${dataKind}`
	        });
	      }
	    }
	  },
	  template: `
		<div ref="bookingContainer" class="booking-booking-grid">
			<div
				id="booking-booking-grid-wrap"
				class="booking-booking-grid-inner --vertical-scroll-bar"
				ref="inner"
			>
				<LeftPanel/>
				<NowLine/>
				<div
					id="booking-booking-grid-columns"
					class="booking-booking-grid-columns --horizontal-scroll-bar"
					ref="columnsContainer"
					@scroll="$store.dispatch('interface/setScroll', $refs.columnsContainer.scrollLeft)"
				>
					<Bookings/>
					<TransitionGroup
						name="booking-transition-resource"
						@after-leave="updateEars"
						@after-enter="updateEars"
					>
						<template v-for="resourceId of resourcesIds" :key="resourceId">
							<Column :resourceId="resourceId"/>
						</template>
					</TransitionGroup>
				</div>
			</div>
			<ScalePanel
				:getColumnsContainer="() => $refs.columnsContainer"
				ref="scalePanel"
			/>
			<DragDelete/>
		</div>
		<Sidebar/>
	`
	};

	const MIN_CHARGE = 0;
	const MAX_CHARGE = 12;
	const CHARGE_COLOR = 'var(--ui-color-primary-alt)';
	const EMPTY_COLOR = 'var(--ui-color-background-secondary)';
	const BATTERY_ICON_HEIGHT = 14;
	const BATTERY_ICON_WIDTH = 27;
	const BatteryIcon = {
	  name: 'BatteryIcon',
	  props: {
	    percent: {
	      type: Number,
	      default: 0
	    },
	    dataId: {
	      type: [String, Number],
	      default: ''
	    },
	    height: {
	      type: Number,
	      default: BATTERY_ICON_HEIGHT
	    },
	    width: {
	      type: Number,
	      default: BATTERY_ICON_WIDTH
	    }
	  },
	  mounted() {
	    this.repaint();
	  },
	  methods: {
	    getCharge(percent) {
	      if (percent <= 0) {
	        return MIN_CHARGE;
	      }
	      if (percent >= 100) {
	        return MAX_CHARGE;
	      }
	      return Math.round(percent * MAX_CHARGE * 0.01);
	    },
	    repaint() {
	      var _this$$refs$iconBatt;
	      const rects = ((_this$$refs$iconBatt = this.$refs['icon-battery-charge']) == null ? void 0 : _this$$refs$iconBatt.children) || [];
	      const charge = this.getCharge(this.percent);
	      let index = 1;
	      for (const rect of rects) {
	        rect.setAttribute('fill', index > charge ? EMPTY_COLOR : CHARGE_COLOR);
	        index++;
	      }
	    }
	  },
	  watch: {
	    percent: {
	      handler() {
	        this.repaint();
	      }
	    }
	  },
	  template: `
		<div :data-id="dataId" :data-percent="percent" data-element="booking-resource-workload-percent">
			<svg id="booking--battery-icon" :width="width" :height="height" viewBox="0 0 27 14" fill="none"
				 xmlns="http://www.w3.org/2000/svg">
				<rect width="23.2875" height="13.8" rx="4" fill="white"/>
				<rect x="22.6871" y="0.6" width="12.6" height="22.0875" rx="3.4" transform="rotate(90 22.6871 0.6)" stroke="#C9CCD0" stroke-width="1.2"/>
				<g ref="icon-battery-charge" id="booking--battery-icon-charge" clip-path="url(#clip0_5003_187951)">
					<rect x="2.58789" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="4.09766" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="5.60547" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="7.11523" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="8.625" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="10.1328" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="11.6426" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="13.1523" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="14.6621" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="16.1699" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="17.6797" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="19.1895" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
				</g>
				<g clip-path="url(#clip1_5003_187951)">
					<ellipse cx="23.102" cy="6.89999" rx="2.9" ry="3.48" transform="rotate(90 23.102 6.89999)" fill="#C9CCD0"/>
				</g>
				<defs>
					<clipPath id="clip0_5003_187951">
						<rect x="2.58789" y="2.5875" width="18.1125" height="8.625" rx="1.5" fill="white"/>
					</clipPath>
					<clipPath id="clip1_5003_187951">
						<rect width="6.9" height="2.15625" fill="white" transform="translate(26.7383 3.45) rotate(90)"/>
					</clipPath>
				</defs>
			</svg>
		</div>
	`
	};

	const WorkloadPopup = {
	  emits: ['close'],
	  props: {
	    resourceId: {
	      type: Number,
	      required: true
	    },
	    slotsCount: {
	      type: Number,
	      required: true
	    },
	    bookingCount: {
	      type: Number,
	      required: true
	    },
	    workLoadPercent: {
	      type: Number,
	      required: true
	    },
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    }
	  },
	  computed: {
	    popupId() {
	      return 'booking-booking-resource-workload-popup';
	    },
	    title() {
	      return this.loc('BOOKING_BOOKING_RESOURCE_WORKLOAD_POPUP_TITLE');
	    },
	    rows() {
	      return [{
	        title: this.loc('BOOKING_BOOKING_RESOURCE_WORKLOAD_SLOTS_BOOKED'),
	        value: this.slotsBookedFormatted,
	        dataset: {
	          element: 'booking-resource-workload-popup-count',
	          resourceId: this.resourceId,
	          bookedCount: this.bookingCount,
	          totalCount: this.slotsCount
	        }
	      }, {
	        title: this.loc('BOOKING_BOOKING_RESOURCE_WORKLOAD'),
	        value: this.workLoadPercentFormatted,
	        dataset: {
	          element: 'booking-resource-workload-popup-percent',
	          resourceId: this.resourceId,
	          percent: this.workLoadPercent
	        }
	      }];
	    },
	    slotsBookedFormatted() {
	      return this.loc('BOOKING_BOOKING_RESOURCE_WORKLOAD_BOOKED_FROM_SLOTS_COUNT', {
	        '#BOOKED#': this.bookingCount,
	        '#SLOTS_COUNT#': this.slotsCount
	      });
	    },
	    workLoadPercentFormatted() {
	      return this.loc('BOOKING_BOOKING_RESOURCE_WORKLOAD_PERCENT', {
	        '#PERCENT#': this.workLoadPercent
	      });
	    }
	  },
	  components: {
	    StatisticsPopup: booking_component_statisticsPopup.StatisticsPopup
	  },
	  template: `
		<StatisticsPopup
			:popupId="popupId"
			:bindElement="bindElement"
			:title="title"
			:rows="rows"
			:dataset="{
				id: resourceId,
				element: 'booking-resource-workload-popup',
			}"
			@close="$emit('close')"
		/>
	`
	};

	// @vue/component
	const ResourceWorkload = {
	  name: 'ResourceWorkload',
	  components: {
	    BatteryIcon,
	    WorkloadPopup
	  },
	  props: {
	    resourceId: {
	      type: Number,
	      required: true
	    },
	    scale: {
	      type: Number,
	      default: 1
	    },
	    isGrid: {
	      type: Boolean,
	      default: false
	    }
	  },
	  data() {
	    return {
	      isPopupShown: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      selectedDateTs: 'interface/selectedDateTs',
	      fromHour: 'interface/fromHour',
	      toHour: 'interface/toHour'
	    }),
	    workLoadPercent() {
	      if (this.slotsCount === 0) {
	        return 0;
	      }
	      const slotsDuration = this.slotSize * this.slotsCount;
	      const percent = this.bookings.reduce((acc, booking) => {
	        return acc + booking.duration * 100 / slotsDuration;
	      }, 0);
	      return Math.round(percent);
	    },
	    slotSize() {
	      var _this$resource$slotRa;
	      return (_this$resource$slotRa = this.resource.slotRanges[0].slotSize) != null ? _this$resource$slotRa : 60;
	    },
	    slotsCount() {
	      const selectedDate = new Date(this.selectedDateTs);
	      const selectedWeekDay = booking_const.DateFormat.WeekDays[selectedDate.getDay()];
	      const slotRanges = booking_lib_busySlots.busySlots.filterSlotRanges(this.resource.slotRanges.filter(slotRange => {
	        return slotRange.weekDays.includes(selectedWeekDay);
	      }));
	      return Math.floor(slotRanges.reduce((sum, slotRange) => {
	        return sum + Math.round((slotRange.to - slotRange.from) / this.slotSize);
	      }, 0));
	    },
	    bookings() {
	      const dateTs = this.selectedDateTs;
	      return this.$store.getters[`${booking_const.Model.Bookings}/getByDateAndResources`](dateTs, [this.resourceId]).map(booking => {
	        return {
	          dateFromTs: booking.dateFromTs,
	          dateToTs: booking.dateToTs,
	          duration: (booking.dateToTs - booking.dateFromTs) / 60000
	        };
	      });
	    },
	    bookingsDuration() {
	      return this.bookings.reduce((acc, booking) => {
	        return acc + booking.duration;
	      }, 0);
	    },
	    resource() {
	      return this.$store.getters['resources/getById'](this.resourceId);
	    },
	    batteryIconOptions() {
	      return {
	        height: Math.round(BATTERY_ICON_HEIGHT * this.scale),
	        width: Math.round(BATTERY_ICON_WIDTH * this.scale)
	      };
	    },
	    bookingsCount() {
	      return this.bookings.length;
	    }
	  },
	  watch: {
	    bookingsCount(newCount, previousCount) {
	      if (this.isGrid && newCount > previousCount && booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.ResourceWorkload)) {
	        void this.showAhaMoment();
	      }
	    }
	  },
	  methods: {
	    onMouseEnter() {
	      this.showTimeout = setTimeout(() => this.showPopup(), 100);
	    },
	    onMouseLeave() {
	      clearTimeout(this.showTimeout);
	      this.closePopup();
	    },
	    showPopup() {
	      this.isPopupShown = true;
	    },
	    closePopup() {
	      this.isPopupShown = false;
	    },
	    async showAhaMoment() {
	      booking_lib_ahaMoments.ahaMoments.setPopupShown(booking_const.AhaMoment.ResourceWorkload);
	      await booking_lib_ahaMoments.ahaMoments.show({
	        id: 'booking-resource-workload',
	        title: this.loc('BOOKING_AHA_RESOURCE_WORKLOAD_TITLE'),
	        text: this.loc('BOOKING_AHA_RESOURCE_WORKLOAD_TEXT'),
	        article: booking_const.HelpDesk.AhaResourceWorkload,
	        target: this.$refs.container
	      });
	      booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.ResourceWorkload);
	    }
	  },
	  template: `
		<div
			class="booking-booking-header-resource-workload"
			data-element="booking-resource-workload"
			:data-id="resourceId"
			ref="container"
			@click="showPopup"
			@mouseenter="onMouseEnter"
			@mouseleave="onMouseLeave"
		>
			<BatteryIcon
				:percent="workLoadPercent"
				:data-id="resourceId"
				:height="batteryIconOptions.height"
				:width="batteryIconOptions.width"
			/>
		</div>
		<WorkloadPopup
			v-if="isPopupShown"
			:resourceId="resourceId"
			:slotsCount="slotsCount"
			:bookingCount="Math.ceil(bookingsDuration / slotSize)"
			:workLoadPercent="workLoadPercent"
			:bindElement="$refs.container"
			@close="closePopup"
		/>
	`
	};

	// @vue/component
	const ResourceMenu = {
	  name: 'ResourceMenu',
	  props: {
	    resourceId: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      menuPopup: null
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      favoritesIds: `${booking_const.Model.Favorites}/get`,
	      isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`,
	      isFilterMode: `${booking_const.Model.Filter}/isFilterMode`
	    }),
	    popupId() {
	      return `resource-menu-${this.resourceId || 'new'}`;
	    }
	  },
	  created() {
	    this.hint = BX.UI.Hint.createInstance({
	      popupParameters: {}
	    });
	  },
	  unmounted() {
	    if (this.menuPopup) {
	      this.destroy();
	    }
	  },
	  methods: {
	    openMenu() {
	      var _this$menuPopup, _this$menuPopup$popup;
	      if ((_this$menuPopup = this.menuPopup) != null && (_this$menuPopup$popup = _this$menuPopup.popupWindow) != null && _this$menuPopup$popup.isShown()) {
	        this.destroy();
	        return;
	      }
	      if (this.isFilterMode) {
	        return;
	      }
	      const menuButton = this.$refs['menu-button'];
	      this.menuPopup = main_popup.MenuManager.create(this.popupId, menuButton, this.getMenuItems(), {
	        className: 'booking-resource-menu-popup',
	        closeByEsc: true,
	        autoHide: true,
	        offsetTop: -3,
	        offsetLeft: menuButton.offsetWidth - 6,
	        angle: true,
	        cacheable: true,
	        events: {
	          onDestroy: () => this.unbindScrollEvent()
	        }
	      });
	      this.menuPopup.show();
	      this.bindScrollEvent();
	    },
	    getMenuItems() {
	      return [
	      // {
	      // 	html: `<span>${this.loc('BOOKING_RESOURCE_MENU_ADD_BOOKING')}</span>`,
	      // 	onclick: () => this.destroy(),
	      // },
	      {
	        html: `<span>${this.loc('BOOKING_RESOURCE_MENU_EDIT_RESOURCE')}</span>`,
	        className: this.isFeatureEnabled ? 'menu-popup-item menu-popup-no-icon' : 'menu-popup-item --lock',
	        onclick: async () => {
	          if (!this.isFeatureEnabled) {
	            booking_lib_limit.limit.show();
	            return;
	          }
	          const wizard = new booking_resourceCreationWizard.ResourceCreationWizard();
	          this.editResource(this.resourceId, wizard);
	          this.destroy();
	        }
	      },
	      // {
	      // 	html: `<span>${this.loc('BOOKING_RESOURCE_MENU_EDIT_NOTIFY')}</span>`,
	      // 	onclick: () => this.destroy(),
	      // },
	      // {
	      // 	html: `<span>${this.loc('BOOKING_RESOURCE_MENU_CREATE_COPY')}</span>`,
	      // 	onclick: () => this.destroy(),
	      // },
	      // {
	      // 	html: '<span></span>',
	      // 	disabled: true,
	      // 	className: 'menu-item-divider',
	      // },
	      {
	        html: `<span>${this.loc('BOOKING_RESOURCE_MENU_HIDE')}</span>`,
	        onclick: async () => {
	          this.destroy();
	          await this.hideResource(this.resourceId);
	        }
	      }, {
	        html: `<span class="alert-text">${this.loc('BOOKING_RESOURCE_MENU_DELETE')}</span>`,
	        onclick: async () => {
	          this.destroy();
	          await this.removeResource(this.resourceId);
	        }
	      }];
	    },
	    destroy() {
	      main_popup.MenuManager.destroy(this.popupId);
	      this.unbindScrollEvent();
	    },
	    bindScrollEvent() {
	      main_core.Event.bind(document, 'scroll', this.adjustPosition, {
	        capture: true
	      });
	    },
	    unbindScrollEvent() {
	      main_core.Event.unbind(document, 'scroll', this.adjustPosition, {
	        capture: true
	      });
	    },
	    adjustPosition() {
	      var _this$menuPopup2, _this$menuPopup2$popu;
	      (_this$menuPopup2 = this.menuPopup) == null ? void 0 : (_this$menuPopup2$popu = _this$menuPopup2.popupWindow) == null ? void 0 : _this$menuPopup2$popu.adjustPosition();
	    },
	    async editResource(resourceId, wizard) {
	      wizard.open(resourceId);
	    },
	    async removeResource(resourceId) {
	      await new booking_lib_removeResource.RemoveResource(resourceId).run();
	    },
	    async hideResource(resourceId) {
	      const ids = [...this.favoritesIds];
	      const index = this.favoritesIds.indexOf(resourceId);
	      if (index === -1) {
	        return;
	      }
	      ids.splice(index, 1);
	      await booking_lib_resources.hideResources(ids);
	    }
	  },
	  template: `
		<button ref="menu-button" class="ui-icon-set --more" @click="openMenu"></button>
	`
	};

	const Resource = {
	  props: {
	    resourceId: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      visible: true
	    };
	  },
	  mounted() {
	    this.updateVisibility();
	    this.updateVisibilityDuringTransition();
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      resourcesIds: `${booking_const.Model.Interface}/resourcesIds`,
	      zoom: `${booking_const.Model.Interface}/zoom`,
	      scroll: `${booking_const.Model.Interface}/scroll`,
	      selectedDateTs: `${booking_const.Model.Interface}/selectedDateTs`
	    }),
	    resource() {
	      return this.$store.getters[`${booking_const.Model.Resources}/getById`](this.resourceId);
	    },
	    resourceType() {
	      return this.$store.getters[`${booking_const.Model.ResourceTypes}/getById`](this.resource.typeId);
	    },
	    profit() {
	      const currencyId = booking_lib_currencyFormat.currencyFormat.getBaseCurrencyId();
	      const deals = this.bookings.map(({
	        externalData
	      }) => {
	        var _externalData$find;
	        return (_externalData$find = externalData == null ? void 0 : externalData.find(data => data.entityTypeId === booking_const.CrmEntity.Deal)) != null ? _externalData$find : null;
	      }).filter(deal => {
	        var _deal$data;
	        return (deal == null ? void 0 : (_deal$data = deal.data) == null ? void 0 : _deal$data.currencyId) === currencyId;
	      });
	      if (deals.length === 0) {
	        return '';
	      }
	      const uniqueDeals = [...new Map(deals.map(it => [it.value, it])).values()];
	      const profit = uniqueDeals.reduce((sum, deal) => sum + deal.data.opportunity, 0);
	      return booking_lib_currencyFormat.currencyFormat.format(currencyId, profit);
	    },
	    bookings() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getByDateAndResources`](this.selectedDateTs, [this.resourceId]);
	    },
	    labelHTML() {
	      const label = new ui_label.Label({
	        size: ui_label.LabelSize.SM,
	        text: this.loc('BOOKING_BOOKING_RESOURCE_DELETED'),
	        fill: true
	      });
	      return label.render().outerHTML;
	    }
	  },
	  methods: {
	    updateVisibilityDuringTransition() {
	      var _this$animation;
	      (_this$animation = this.animation) == null ? void 0 : _this$animation.stop();
	      this.animation = new BX.easing({
	        duration: 200,
	        start: {},
	        finish: {},
	        step: this.updateVisibility
	      });
	      this.animation.animate();
	    },
	    updateVisibility() {
	      if (!this.$refs.container) {
	        return;
	      }
	      const rect = this.$refs.container.getBoundingClientRect();
	      this.visible = rect.right > 0 && rect.left < window.innerWidth;
	    }
	  },
	  watch: {
	    scroll() {
	      this.updateVisibility();
	    },
	    zoom() {
	      this.updateVisibility();
	    },
	    resourcesIds() {
	      this.updateVisibilityDuringTransition();
	    }
	  },
	  components: {
	    ResourceMenu,
	    ResourceWorkload
	  },
	  template: `
		<div
			class="booking-booking-header-resource"
			data-element="booking-resource"
			:data-id="resourceId"
			ref="container"
		>
			<template v-if="visible">
				<ResourceWorkload
					v-if="!resource.isDeleted"
					:resourceId="resourceId"
					:scale="zoom"
					:isGrid="true"
				/>
				<div class="booking-booking-header-resource-title">
					<div class="booking-booking-header-resource-name" :title="resource.name">
						{{ resource.name }}
					</div>
					<div class="booking-booking-header-resource-type" :title="resourceType.name">
						{{ resourceType.name }}
					</div>
				</div>
				<div
					v-if="resource.isDeleted"
					v-html="labelHTML"
				></div>
				<div
					class="booking-booking-header-resource-profit"
					v-else
					v-html="profit"
				></div>
				<div class="booking-booking-header-resource-actions" v-if="!resource.isDeleted">
					<ResourceMenu :resource-id/>
				</div>
			</template>
		</div>
	`
	};

	// @vue/component
	const AddResourceButton = {
	  name: 'AddResourceButton',
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      hovered: false
	    };
	  },
	  computed: ui_vue3_vuex.mapGetters({
	    isLoaded: `${booking_const.Model.Interface}/isLoaded`,
	    isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	  }),
	  watch: {
	    isLoaded() {
	      if (booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.AddResource)) {
	        void this.showAhaMoment();
	      }
	    }
	  },
	  methods: {
	    async addResource() {
	      if (!this.isFeatureEnabled) {
	        await booking_lib_limit.limit.show();
	        return;
	      }
	      new booking_resourceCreationWizard.ResourceCreationWizard().open();
	      booking_lib_analytics.RcwAnalytics.sendClickAddResource();
	    },
	    async showAhaMoment() {
	      await booking_lib_ahaMoments.ahaMoments.show({
	        id: 'booking-add-resource',
	        title: this.loc('BOOKING_AHA_ADD_RESOURCES_TITLE'),
	        text: this.loc('BOOKING_AHA_ADD_RESOURCES_TEXT_MSGVER_1'),
	        article: {
	          ...booking_const.HelpDesk.AhaAddResource,
	          title: this.loc('BOOKING_AHA_ARTICLE_LINK_TITLE')
	        },
	        target: this.$refs.button
	      });
	      booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.AddResource);
	      if (booking_lib_sidePanelInstance.SidePanelInstance.openSliders.every(({
	        url
	      }) => url !== booking_resourceCreationWizard.ResourceCreationWizard.makeName())) {
	        void this.addResource();
	      }
	    }
	  },
	  template: `
		<div
			class="booking-booking-header-add-resource-icon-container"
			:class="{ '--hover': hovered }"
			@click="addResource"
			@mouseenter="hovered = true"
			@mouseleave="hovered = false"
		>
			<div
				class="booking-booking-header-add-resource-icon"
				:class="{'--lock': !isFeatureEnabled}"
			>
				<Icon v-if="isFeatureEnabled" :name="IconSet.PLUS_20"/>
				<Icon v-else :name="IconSet.LOCK" :size="16"/>
			</div>
		</div>
		<div
			class="booking-booking-header-add-resource"
			:class="{ '--hover': hovered }"
			ref="button"
			@click="addResource"
			@mouseenter="hovered = true"
			@mouseleave="hovered = false"
		>
			<div class="booking-booking-header-add-resource-text">
				{{ loc('BOOKING_BOOKING_ADD_RESOURCE') }}
			</div>
		</div>
	`
	};

	class ContentHeader extends ui_entitySelector.BaseHeader {
	  constructor(...props) {
	    super(...props);
	    this.getContainer();
	  }
	  render() {
	    return this.options.content;
	  }
	}

	const ResourceTypes = {
	  emits: ['update:modelValue'],
	  data() {
	    return {
	      selectedTypes: {}
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      resourceTypes: 'resourceTypes/get'
	    }),
	    visibleResourceTypes() {
	      return this.resourceTypes.filter(item => item.resourcesCnt > 0);
	    },
	    isResourceTypesSectionVisible() {
	      return this.visibleResourceTypes.length > 0;
	    }
	  },
	  methods: {
	    selectAll() {
	      Object.keys(this.selectedTypes).forEach(typeId => {
	        this.selectedTypes[typeId] = true;
	      });
	    },
	    deselectAll() {
	      Object.keys(this.selectedTypes).forEach(typeId => {
	        this.selectedTypes[typeId] = false;
	      });
	    }
	  },
	  watch: {
	    resourceTypes(resourceTypes) {
	      resourceTypes.forEach(resourceType => {
	        var _this$selectedTypes, _resourceType$id, _this$selectedTypes$_;
	        (_this$selectedTypes$_ = (_this$selectedTypes = this.selectedTypes)[_resourceType$id = resourceType.id]) != null ? _this$selectedTypes$_ : _this$selectedTypes[_resourceType$id] = true;
	      });
	    },
	    selectedTypes: {
	      handler() {
	        this.$emit('update:modelValue', this.selectedTypes);
	      },
	      deep: true
	    }
	  },
	  template: `
		<div v-if="isResourceTypesSectionVisible" class="booking-booking-resources-dialog-header-types">
			<div class="booking-booking-resources-dialog-header-header">
				<div class="booking-booking-resources-dialog-header-title">
					{{ loc('BOOKING_BOOKING_RESOURCES_DIALOG_RESOURCE_TYPES') }}
				</div>
				<div
					class="booking-booking-resources-dialog-header-button"
					data-element="booking-resources-dialog-select-all-types-button"
					@click="selectAll"
				>
					{{ loc('BOOKING_BOOKING_RESOURCES_DIALOG_SELECT_ALL') }}
				</div>
				<div
					class="booking-booking-resources-dialog-header-button"
					data-element="booking-resources-dialog-deselect-all-types-button"
					@click="deselectAll"
				>
					{{ loc('BOOKING_BOOKING_RESOURCES_DIALOG_DESELECT_ALL') }}
				</div>
			</div>
			<div class="booking-booking-resources-dialog-header-items">
				<template v-for="resourceType of visibleResourceTypes" :key="resourceType.id">
					<label
						class="booking-booking-resources-dialog-header-item"
						data-element="booking-resources-dialog-type"
						:data-id="resourceType.id"
						:data-selected="selectedTypes[resourceType.id]"
					>
						<span
							class="booking-booking-resources-dialog-header-item-text"
							data-element="booking-resources-dialog-type-name"
							:data-id="resourceType.id"
						>
							{{ resourceType.name }}
						</span>
						<input type="checkbox" v-model="selectedTypes[resourceType.id]">
					</label>
				</template>
			</div>
		</div>
	`
	};

	const Resize$1 = {
	  emits: ['startResize', 'endResize'],
	  props: {
	    getNode: {
	      type: Function,
	      required: true
	    }
	  },
	  data() {
	    return {
	      isResized: false,
	      startMouseY: 0,
	      startHeight: 0
	    };
	  },
	  methods: {
	    startResize(event) {
	      this.$emit('startResize');
	      main_core.Dom.style(document.body, 'user-select', 'none');
	      main_core.Event.bind(window, 'mouseup', this.endResize);
	      main_core.Event.bind(window, 'pointermove', this.resize);
	      this.isResized = true;
	      this.startMouseY = event.clientY;
	      this.startHeight = this.getNode().offsetHeight;
	    },
	    resize(event) {
	      if (!this.isResized) {
	        return;
	      }
	      event.preventDefault();
	      const minHeight = 110;
	      const maxHeight = 180;
	      const height = this.startHeight + event.clientY - this.startMouseY;
	      const newHeight = Math.min(maxHeight, Math.max(height, minHeight));
	      main_core.Dom.style(this.getNode(), 'max-height', `${newHeight}px`);
	    },
	    endResize() {
	      this.$emit('endResize');
	      main_core.Dom.style(document.body, 'user-select', '');
	      main_core.Event.unbind(window, 'mouseup', this.endResize);
	      main_core.Event.unbind(window, 'pointermove', this.resize);
	      this.isResized = false;
	    }
	  },
	  template: `
		<div
			class="booking-booking-resources-dialog-header-resize"
			@mousedown="startResize"
		></div>
	`
	};

	const Search = {
	  emits: ['search'],
	  data() {
	    return {
	      searchDebounced: main_core.Runtime.debounce(this.search, 200, this),
	      query: ''
	    };
	  },
	  computed: {
	    searchIcon() {
	      return ui_iconSet_api_vue.Set.SEARCH_2;
	    }
	  },
	  methods: {
	    onInput(event) {
	      const query = event.target.value;
	      this.query = query;
	      if (main_core.Type.isStringFilled(query)) {
	        this.searchDebounced(query);
	      } else {
	        this.search(query);
	      }
	    },
	    search(query) {
	      if (this.query === query) {
	        this.$emit('search', query);
	      }
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<div class="booking-booking-resources-dialog-header-input-container">
			<input
				class="booking-booking-resources-dialog-header-input"
				:placeholder="loc('BOOKING_BOOKING_RESOURCES_DIALOG_SEARCH')"
				data-element="booking-resources-dialog-search-input"
				@input="onInput"
			>
			<div class="booking-booking-resources-dialog-header-input-icon">
				<Icon :name="searchIcon"/>
			</div>
		</div>
	`
	};

	const DialogHeader = {
	  emits: ['update:modelValue', 'search', 'startResize', 'endResize', 'selectAll', 'deselectAll'],
	  data() {
	    return {
	      selectedTypes: {}
	    };
	  },
	  computed: ui_vue3_vuex.mapGetters({
	    resources: 'resources/get'
	  }),
	  watch: {
	    selectedTypes: {
	      handler() {
	        this.$emit('update:modelValue', this.selectedTypes);
	      },
	      deep: true
	    }
	  },
	  components: {
	    ResourceTypes,
	    Resize: Resize$1,
	    Search
	  },
	  template: `
		<div class="booking-booking-resources-dialog-header" ref="header">
			<ResourceTypes
				ref="resourceTypes"
				v-model="selectedTypes"
			/>
			<Resize
				:getNode="() => this.$refs.resourceTypes.$el"
				@startResize="$emit('startResize')"
				@endResize="$emit('endResize')"
			/>
			<div class="booking-booking-resources-dialog-header-resources">
				<div class="booking-booking-resources-dialog-header-header">
					<div class="booking-booking-resources-dialog-header-title">
						{{ loc('BOOKING_BOOKING_RESOURCES_DIALOG_RESOURCES') }}
					</div>
					<div
						class="booking-booking-resources-dialog-header-button"
						data-element="booking-resources-dialog-select-all-button"
						@click="$emit('selectAll')"
					>
						{{ loc('BOOKING_BOOKING_RESOURCES_DIALOG_SELECT_ALL') }}
					</div>
					<div
						class="booking-booking-resources-dialog-header-button"
						data-element="booking-resources-dialog-deselect-all-button"
						@click="$emit('deselectAll')"
					>
						{{ loc('BOOKING_BOOKING_RESOURCES_DIALOG_DESELECT_ALL') }}
					</div>
				</div>
				<Search @search="(query) => this.$emit('search', query)"/>
			</div>
		</div>
	`
	};

	class ContentFooter extends ui_entitySelector.BaseFooter {
	  constructor(...props) {
	    super(...props);
	    this.getContainer();
	  }
	  render() {
	    return this.options.content;
	  }
	}

	const DialogFooter = {
	  name: 'DialogFooter',
	  emits: ['reset'],
	  computed: {
	    buttonSettings() {
	      return Object.freeze({
	        size: booking_component_button.ButtonSize.SMALL,
	        color: booking_component_button.ButtonColor.LINK
	      });
	    },
	    buttonLabel() {
	      return this.loc('BOOKING_BOOKING_RESOURCES_DIALOG_RESET');
	    }
	  },
	  components: {
	    UiButton: booking_component_button.Button
	  },
	  template: `
		<div class="booking--booking--select-resources-dialog-footer">
			<UiButton
				:size="buttonSettings.size"
				:color="buttonSettings.color"
				:text="buttonLabel"
				button-class="booking--booking--select-resources-dialog-footer__button"
				@click="$emit('reset')"
			/>
		</div>
	`
	};

	// @vue/component
	const SelectResources = {
	  name: 'SelectResources',
	  components: {
	    DialogHeader,
	    DialogFooter,
	    ResourceWorkload
	  },
	  data() {
	    return {
	      dialogFilled: false,
	      query: '',
	      saveItemsDebounce: main_core.Runtime.debounce(this.saveItems, 10, this),
	      workloadRefs: {},
	      selectedTypes: {}
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      selectedDateTs: `${booking_const.Model.Interface}/selectedDateTs`,
	      favoritesIds: `${booking_const.Model.Favorites}/get`,
	      resources: `${booking_const.Model.Resources}/get`,
	      isFilterMode: `${booking_const.Model.Filter}/isFilterMode`,
	      isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`,
	      isLoaded: `${booking_const.Model.Interface}/isLoaded`,
	      mainResources: `${booking_const.Model.MainResources}/resources`,
	      resourceTypes: `${booking_const.Model.ResourceTypes}/get`
	    }),
	    mainResourceIds() {
	      return new Set(this.mainResources);
	    },
	    isDefaultState() {
	      return this.mainResourceIds.size === this.favoritesIds.length && this.favoritesIds.every(id => this.mainResourceIds.has(id));
	    },
	    visibleResourceTypes() {
	      return this.resourceTypes.filter(item => item.resourcesCnt > 0).map(({
	        id
	      }) => id);
	    }
	  },
	  watch: {
	    favoritesIds() {
	      this.updateItems();
	    },
	    resources: {
	      handler(resources) {
	        setTimeout(() => this.addItems(resources));
	        booking_provider_service_resourceDialogService.resourceDialogService.clearMainResourcesCache();
	      },
	      deep: true
	    },
	    selectedTypes: {
	      handler() {
	        void this.updateItemsByType();
	      },
	      deep: true
	    },
	    isLoaded() {
	      this.tryShowAhaMoment();
	    }
	  },
	  mounted() {
	    this.dialog = new ui_entitySelector.Dialog({
	      context: 'BOOKING',
	      targetNode: this.$refs.button,
	      width: 340,
	      height: Math.min(window.innerHeight - 280, 600),
	      offsetLeft: 4,
	      dropdownMode: true,
	      preselectedItems: this.favoritesIds.map(resourceId => [booking_const.EntitySelectorEntity.Resource, resourceId]),
	      items: this.resources.map(resource => this.getItemOptions(resource)),
	      entities: [{
	        id: booking_const.EntitySelectorEntity.Resource
	      }],
	      events: {
	        onShow: this.onShow,
	        'Item:onSelect': this.saveItemsDebounce,
	        'Item:onDeselect': this.saveItemsDebounce
	      },
	      header: ContentHeader,
	      headerOptions: {
	        content: this.$refs.dialogHeader.$el
	      },
	      footer: ContentFooter,
	      footerOptions: {
	        content: this.$refs.dialogFooter.$el
	      }
	    });
	    main_core.Event.bind(this.dialog.getRecentTab().getContainer(), 'scroll', this.loadOnScroll);
	    main_core.Event.EventEmitter.subscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
	    main_core.Event.EventEmitter.subscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
	  },
	  methods: {
	    showDialog() {
	      this.updateItems();
	      void this.loadMainResources();
	      this.dialog.show();
	    },
	    async onShow() {
	      if (this.dialogFilled) {
	        void this.loadOnScroll();
	        return;
	      }
	      this.dialogFilled = true;
	      await booking_provider_service_resourceDialogService.resourceDialogService.fillDialog(this.selectedDateTs / 1000);
	    },
	    async loadOnScroll() {
	      const container = this.dialog.getRecentTab().getContainer();
	      const scrollTop = container.scrollTop;
	      const maxScroll = container.scrollHeight - container.offsetHeight;
	      if (scrollTop + 10 >= maxScroll) {
	        const loadedResourcesIds = booking_lib_resourcesDateCache.resourcesDateCache.getIdsByDateTs(this.selectedDateTs / 1000);
	        const activeResources = this.resources.filter(resource => !resource.isDeleted);
	        const resourcesIds = activeResources.map(resource => resource.id);
	        const idsToLoad = resourcesIds.filter(id => !loadedResourcesIds.includes(id)).slice(0, booking_const.Limit.ResourcesDialog);
	        await booking_provider_service_resourceDialogService.resourceDialogService.loadByIds(idsToLoad, this.selectedDateTs / 1000);
	        this.updateItems();
	      }
	    },
	    async loadMainResources() {
	      await booking_provider_service_resourceDialogService.resourceDialogService.getMainResources();
	    },
	    updateItems() {
	      this.dialog.getItems().forEach(item => {
	        const id = item.getId();
	        const workload = this.workloadRefs[id];
	        const isHidden = this.isItemHidden(id);
	        const isSelected = this.isItemSelected(id);
	        item.getNodes().forEach(node => {
	          const avatarContainer = node.getAvatarContainer();
	          main_core.Dom.style(avatarContainer, 'width', 'max-content');
	          main_core.Dom.style(avatarContainer, 'height', 'max-content');
	          main_core.Dom.append(workload, avatarContainer);
	        });
	        item.setHidden(isHidden);
	        if (!item.isSelected() && isSelected) {
	          item.select();
	        }
	        if (item.isSelected() && !isSelected) {
	          item.deselect();
	        }
	      });
	    },
	    saveItems() {
	      void booking_lib_resources.hideResources(this.dialog.getSelectedItems().map(item => item.id));
	    },
	    addItems(resources) {
	      const itemsOptions = resources.reduce((acc, resource) => ({
	        ...acc,
	        [resource.id]: this.getItemOptions(resource)
	      }), {});
	      Object.values(itemsOptions).forEach(itemOptions => this.dialog.addItem(itemOptions));
	      const itemsIds = this.dialog.getItems().map(item => item.getId()).filter(id => itemsOptions[id]);
	      this.dialog.removeItems();
	      itemsIds.forEach(id => this.dialog.addItem(itemsOptions[id]));

	      // I don't know why, but tab is being removed after this.dialog.removeItems();
	      const tab = this.dialog.getActiveTab();
	      if (tab) {
	        tab.getContainer().append(tab.getRootNode().getChildrenContainer());
	        tab.render();
	      }
	      this.updateItems();
	      if (main_core.Type.isStringFilled(this.query)) {
	        void this.search(this.query);
	      }
	    },
	    getItemOptions(resource) {
	      return {
	        id: resource.id,
	        entityId: booking_const.EntitySelectorEntity.Resource,
	        title: resource.name,
	        subtitle: this.getResourceType(resource.typeId).name,
	        avatarOptions: {
	          bgImage: 'none',
	          borderRadius: '0'
	        },
	        tabs: booking_const.EntitySelectorTab.Recent,
	        selected: this.isItemSelected(resource.id),
	        hidden: this.isItemHidden(resource.id),
	        nodeAttributes: {
	          'data-id': resource.id,
	          'data-element': 'booking-select-resources-dialog-item'
	        }
	      };
	    },
	    isItemSelected(id) {
	      return this.favoritesIds.includes(id);
	    },
	    isItemHidden(id) {
	      const loadedResourcesIds = booking_lib_resourcesDateCache.resourcesDateCache.getIdsByDateTs(this.selectedDateTs / 1000);
	      const resource = this.getResource(id);
	      const visible = loadedResourcesIds.includes(id) && resource && !resource.isDeleted && this.selectedTypes[resource.typeId];
	      return !visible;
	    },
	    getResource(id) {
	      return this.$store.getters['resources/getById'](id);
	    },
	    getResourceType(id) {
	      return this.$store.getters['resourceTypes/getById'](id);
	    },
	    async search(query) {
	      this.query = query;
	      this.dialog.search(this.query);
	      this.updateItems();
	      this.dialog.getSearchTab().getStub().hide();
	      this.dialog.getSearchTab().getSearchLoader().show();
	      await booking_provider_service_resourceDialogService.resourceDialogService.doSearch(this.query, this.selectedDateTs / 1000, this.visibleResourceTypes);
	      this.dialog.search(this.query);
	      this.updateItems();
	      this.dialog.getSearchTab().getSearchLoader().hide();
	      if (this.dialog.getSearchTab().isEmptyResult()) {
	        this.dialog.getSearchTab().getStub().show();
	      }
	    },
	    startResize() {
	      this.dialog.freeze();
	    },
	    endResize() {
	      setTimeout(() => this.dialog.unfreeze());
	    },
	    selectAll() {
	      this.dialog.getItems().forEach(item => {
	        if (!item.isHidden()) {
	          item.select();
	        }
	      });
	    },
	    deselectAll() {
	      this.dialog.getItems().forEach(item => {
	        if (!item.isHidden()) {
	          item.deselect();
	        }
	      });
	    },
	    setWorkloadRef(element, id) {
	      this.workloadRefs[id] = element;
	    },
	    tryShowAhaMoment() {
	      if (booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.SelectResources)) {
	        main_core.Event.EventEmitter.unsubscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
	        main_core.Event.EventEmitter.unsubscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
	        void this.showAhaMoment();
	      }
	    },
	    async showAhaMoment() {
	      await booking_lib_ahaMoments.ahaMoments.show({
	        id: 'booking-select-resources',
	        title: this.loc('BOOKING_AHA_SELECT_RESOURCES_TITLE_MSGVER_1'),
	        text: this.loc('BOOKING_AHA_SELECT_RESOURCES_TEXT_MSGVER_1'),
	        article: {
	          ...booking_const.HelpDesk.AhaSelectResources,
	          title: this.loc('BOOKING_AHA_ARTICLE_LINK_TITLE')
	        },
	        target: this.$refs.button
	      });
	      booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.SelectResources);
	    },
	    reset() {
	      const mainResourceIds = this.mainResourceIds;
	      this.dialog.getItems().forEach(item => {
	        if (mainResourceIds.has(item.id)) {
	          item.select();
	        } else {
	          item.deselect();
	        }
	      });
	    },
	    getSelectedTypes() {
	      return this.visibleResourceTypes.filter(id => this.selectedTypes[id]);
	    },
	    async updateItemsByType() {
	      if (this.query || this.visibleResourceTypes.every(id => this.selectedTypes[id]) || this.visibleResourceTypes.every(id => !this.selectedTypes[id])) {
	        this.updateItems();
	        return;
	      }
	      await booking_provider_service_resourceDialogService.resourceDialogService.doSearch(this.query, this.selectedDateTs / 1000, this.getSelectedTypes());
	      this.updateItems();
	      this.dialog.getSearchTab().getSearchLoader().hide();
	      if (this.dialog.getSearchTab().isEmptyResult()) {
	        this.dialog.getSearchTab().getStub().show();
	      }
	    }
	  },
	  template: `
		<div
			class="booking-booking-select-resources"
			:class="{'--disabled': isFilterMode}"
			data-element="booking-select-resources"
			ref="button"
			@click="showDialog"
		>
			<div class="ui-icon-set --funnel"></div>
		</div>
		<DialogHeader
			ref="dialogHeader"
			v-model="selectedTypes"
			@search="search"
			@startResize="startResize"
			@endResize="endResize"
			@selectAll="selectAll"
			@deselectAll="deselectAll"
		/>
		<DialogFooter
			v-show="dialogFilled && !isDefaultState"
			ref="dialogFooter"
			@reset="reset"
		/>
		<div class="booking-booking-select-resources-workload-container">
			<template v-for="resource of resources">
				<span class="booking-booking-select-resources-workload" :ref="(el) => setWorkloadRef(el, resource.id)">
					<ResourceWorkload :resourceId="resource.id"/>
				</span>
			</template>
		</div>
	`
	};

	const Header = {
	  computed: ui_vue3_vuex.mapGetters({
	    resourcesIds: `${booking_const.Model.Interface}/resourcesIds`,
	    scroll: `${booking_const.Model.Interface}/scroll`,
	    isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`
	  }),
	  watch: {
	    scroll(value) {
	      this.$refs.inner.scrollLeft = value;
	    }
	  },
	  components: {
	    Resource,
	    AddResourceButton,
	    SelectResources
	  },
	  template: `
		<div class="booking-booking-header">
			<SelectResources/>
			<div
				class="booking-booking-header-inner"
				ref="inner"
				@scroll="$store.dispatch('interface/setScroll', $refs.inner.scrollLeft)"
			>
				<TransitionGroup name="booking-transition-resource">
					<template v-for="resourceId of resourcesIds" :key="resourceId">
						<Resource :resourceId="resourceId"/>
					</template>
				</TransitionGroup>
				<AddResourceButton v-if="!isEditingBookingMode"/>
			</div>
		</div>
	`
	};

	const Multiple = {
	  emits: ['change'],
	  props: {
	    resourceId: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      isSelected: false,
	      selectedItems: []
	    };
	  },
	  mounted() {
	    this.selector = this.createSelector();
	  },
	  unmounted() {
	    this.selector.destroy();
	    this.selector = null;
	  },
	  methods: {
	    createSelector() {
	      var _this$intersections$t;
	      const selectedIds = (_this$intersections$t = this.intersections[this.resourceId]) != null ? _this$intersections$t : [];
	      return new ui_entitySelector.Dialog({
	        id: `booking-intersection-selector-resource-${this.resourceId}`,
	        targetNode: this.$refs.intersectionField,
	        preselectedItems: selectedIds.map(id => [booking_const.EntitySelectorEntity.Resource, id]),
	        width: 400,
	        enableSearch: true,
	        dropdownMode: true,
	        context: 'bookingResourceIntersection',
	        multiple: true,
	        cacheable: true,
	        showAvatars: false,
	        entities: [{
	          id: booking_const.EntitySelectorEntity.Resource,
	          dynamicLoad: true,
	          dynamicSearch: true
	        }],
	        searchOptions: {
	          allowCreateItem: false,
	          footerOptions: {
	            label: this.loc('BOOKING_BOOKING_ADD_INTERSECTION_DIALOG_SEARCH_FOOTER')
	          }
	        },
	        events: {
	          onHide: this.changeSelected.bind(this),
	          onLoad: this.changeSelected.bind(this)
	        }
	      });
	    },
	    showSelector() {
	      if (this.isFeatureEnabled) {
	        this.selector.show();
	      } else {
	        void booking_lib_limit.limit.show();
	      }
	    },
	    changeSelected() {
	      this.selectedItems = this.selector.getSelectedItems();
	      this.isSelected = this.selectedItems.length > 0;
	      const selectedIds = this.selectedItems.map(item => item.id);
	      this.$emit('change', selectedIds, this.resourceId);
	    },
	    handleRemove(itemId) {
	      this.selector.getItem([booking_const.EntitySelectorEntity.Resource, itemId]).deselect();
	      this.changeSelected();
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      intersections: `${booking_const.Model.Interface}/intersections`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`,
	      resources: `${booking_const.Model.Resources}/get`
	    }),
	    resourcesIds() {
	      return this.resources.map(({
	        id
	      }) => id);
	    },
	    firstItemTitle() {
	      return this.selectedItems.length > 0 ? this.selectedItems[0].title : '';
	    },
	    remainingItemsCount() {
	      return this.selectedItems.length > 1 ? this.selectedItems.length - 1 : 0;
	    }
	  },
	  watch: {
	    resourcesIds(resourcesIds, previousResourcesIds) {
	      if (resourcesIds.join(',') === previousResourcesIds.join(',')) {
	        return;
	      }
	      const removedIds = previousResourcesIds.filter(id => !resourcesIds.includes(id));
	      const newIds = resourcesIds.filter(id => !previousResourcesIds.includes(id));
	      removedIds.forEach(id => {
	        const item = this.selector.getItem([booking_const.EntitySelectorEntity.Resource, id]);
	        this.selector.removeItem(item);
	      });
	      newIds.forEach(id => {
	        const resource = this.$store.getters[`${booking_const.Model.Resources}/getById`](id);
	        if (!resource || resource.isDeleted) {
	          return;
	        }
	        const resourceType = this.$store.getters[`${booking_const.Model.ResourceTypes}/getById`](resource.typeId);
	        this.selector.addItem({
	          id,
	          entityId: booking_const.EntitySelectorEntity.Resource,
	          title: resource.name,
	          subtitle: resourceType.name,
	          tabs: booking_const.EntitySelectorTab.Recent
	        });
	      });
	      this.changeSelected();
	    },
	    resources: {
	      handler() {
	        this.selector.getItems().forEach(item => {
	          const resource = this.$store.getters[`${booking_const.Model.Resources}/getById`](item.getId());
	          if (!resource) {
	            return;
	          }
	          const resourceType = this.$store.getters[`${booking_const.Model.ResourceTypes}/getById`](resource.typeId);
	          item.setTitle(resource.name);
	          item.setSubtitle(resourceType.name);
	        });
	        this.selector.getTagSelector().getTags().forEach(tag => {
	          const resource = this.$store.getters[`${booking_const.Model.Resources}/getById`](tag.getId());
	          if (!resource) {
	            return;
	          }
	          tag.setTitle(resource.name);
	          tag.render();
	        });
	        this.selectedItems = this.selector.getSelectedItems();
	      },
	      deep: true
	    }
	  },
	  template: `
		<div
			ref="intersectionField"
			class="booking-booking-intersections-resource"
			:data-id="'booking-booking-intersections-resource-' + resourceId"
		>
			<template v-if="isSelected">
				<div
					ref="selectorItemContainer"
					class="booking-booking-intersections-resource-container"
				>
					<div
						v-if="selectedItems.length > 0"
						class="bbi-resource-selector-item bbi-resource-selector-tag"
					>
						<div class="bbi-resource-selector-tag-content" :title="firstItemTitle">
							<div class="bbi-resource-selector-tag-title">{{ firstItemTitle }}</div>
						</div>
						<div 
							class="bbi-resource-selector-tag-remove"
							@click="handleRemove(selectedItems[0].id)"
							:data-id="'bbi-resource-selector-tag-remove-' + resourceId"
						></div>
					</div>
					<div
						v-if="remainingItemsCount > 0"
						class="bbi-resource-selector-item bbi-resource-selector-tag --count"
						@click="showSelector"
						:data-id="'bbi-resource-selector-tag-count-' + resourceId"
					>
						<div class="bbi-resource-selector-tag-content">
							<div class="bbi-resource-selector-tag-title --count">+{{ remainingItemsCount }}</div>
						</div>
					</div>
					<div>
						<span
							class="bbi-resource-selector-item bbi-resource-selector-add-button"
							@click="showSelector"
							:data-id="'bbi-resource-selector-add-button' + resourceId"
						>
							<span class="bbi-resource-selector-add-button-caption">
								{{ loc('BOOKING_BOOKING_INTERSECTION_BUTTON_MORE') }}
							</span>
						</span>
					</div>
				</div>
			</template>
			<template v-else>
				<span
					ref="selectorButton"
					class="bbi-resource-selector-item bbi-resource-selector-add-button"
					@click="showSelector"
					:data-id="'bbi-resource-selector-add-button' + resourceId"
				>
					<span class="bbi-resource-selector-add-button-caption">
						{{ loc('BOOKING_BOOKING_INTERSECTION_BUTTON_MSGVER_1') }}
					</span>
				</span>
			</template>
		</div>
	`
	};

	const Single = {
	  emits: ['change'],
	  created() {
	    this.selector = this.createSelector();
	    if (!this.isFeatureEnabled) {
	      this.selector.lock();
	    }
	  },
	  mounted() {
	    this.mountSelector();
	    main_core.Event.EventEmitter.subscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
	    main_core.Event.EventEmitter.subscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
	  },
	  beforeUnmount() {
	    this.destroySelector();
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`,
	      intersections: `${booking_const.Model.Interface}/intersections`,
	      isLoaded: `${booking_const.Model.Interface}/isLoaded`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`,
	      resources: `${booking_const.Model.Resources}/get`
	    }),
	    resourcesIds() {
	      return this.resources.map(({
	        id
	      }) => id);
	    }
	  },
	  methods: {
	    createSelector() {
	      return new ui_entitySelector.TagSelector({
	        multiple: true,
	        addButtonCaption: this.loc('BOOKING_BOOKING_ADD_INTERSECTION_MSGVER_1'),
	        showCreateButton: false,
	        maxHeight: 50,
	        dialogOptions: {
	          context: 'bookingResourceIntersection',
	          width: 290,
	          height: 340,
	          dropdownMode: true,
	          enableSearch: true,
	          cacheable: true,
	          showAvatars: false,
	          entities: [{
	            id: booking_const.EntitySelectorEntity.Resource,
	            dynamicLoad: true,
	            dynamicSearch: true
	          }],
	          searchOptions: {
	            allowCreateItem: false,
	            footerOptions: {
	              label: this.loc('BOOKING_BOOKING_ADD_INTERSECTION_DIALOG_SEARCH_FOOTER')
	            }
	          }
	        },
	        events: {
	          onAfterTagAdd: this.onSelectorChange,
	          onAfterTagRemove: this.onSelectorChange
	        }
	      });
	    },
	    onSelectorChange() {
	      const selectedIds = this.selector.getDialog().getSelectedItems().map(({
	        id
	      }) => id);
	      this.$emit('change', selectedIds);
	    },
	    mountSelector() {
	      this.selector.renderTo(this.$refs.intersectionField);
	    },
	    destroySelector() {
	      this.selector.getDialog().destroy();
	      this.selector = null;
	      this.$refs.intersectionField.innerHTML = '';
	    },
	    getResource(id) {
	      return this.$store.getters['resources/getById'](id);
	    },
	    getResourceType(id) {
	      return this.$store.getters['resourceTypes/getById'](id);
	    },
	    tryShowAhaMoment() {
	      if (booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.ResourceIntersection) && this.selector) {
	        main_core.Event.EventEmitter.unsubscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
	        main_core.Event.EventEmitter.unsubscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
	        void this.showAhaMoment();
	      }
	    },
	    async showAhaMoment() {
	      await booking_lib_ahaMoments.ahaMoments.show({
	        id: 'booking-resource-intersection',
	        title: this.loc('BOOKING_AHA_RESOURCE_INTERSECTION_TITLE'),
	        text: this.loc('BOOKING_AHA_RESOURCE_INTERSECTION_TEXT'),
	        article: booking_const.HelpDesk.AhaResourceIntersection,
	        target: this.selector.getAddButton()
	      });
	      booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.ResourceIntersection);
	    },
	    click() {
	      if (!this.isFeatureEnabled) {
	        void booking_lib_limit.limit.show();
	      }
	    }
	  },
	  watch: {
	    intersections(intersections) {
	      var _intersections$;
	      if (!this.isEditingBookingMode) {
	        return;
	      }
	      const resourcesIds = (_intersections$ = intersections[0]) != null ? _intersections$ : [];
	      resourcesIds.forEach(id => {
	        const resource = this.getResource(id);
	        this.selector.getDialog().addItem({
	          id: resource.id,
	          entityId: booking_const.EntitySelectorEntity.Resource,
	          title: resource.name,
	          subtitle: this.getResourceType(resource.typeId).name,
	          selected: true
	        });
	      });
	    },
	    isLoaded() {
	      this.tryShowAhaMoment();
	    },
	    resourcesIds(resourcesIds, previousResourcesIds) {
	      if (resourcesIds.join(',') === previousResourcesIds.join(',')) {
	        return;
	      }
	      const removedIds = previousResourcesIds.filter(id => !resourcesIds.includes(id));
	      const newIds = resourcesIds.filter(id => !previousResourcesIds.includes(id));
	      removedIds.forEach(id => {
	        const item = this.selector.getDialog().getItem([booking_const.EntitySelectorEntity.Resource, id]);
	        this.selector.getDialog().removeItem(item);
	        const tag = this.selector.getTags().find(it => it.getId() === id);
	        tag == null ? void 0 : tag.remove();
	      });
	      newIds.forEach(id => {
	        const resource = this.$store.getters[`${booking_const.Model.Resources}/getById`](id);
	        if (!resource || resource.isDeleted) {
	          return;
	        }
	        const resourceType = this.$store.getters[`${booking_const.Model.ResourceTypes}/getById`](resource.typeId);
	        this.selector.getDialog().addItem({
	          id,
	          entityId: booking_const.EntitySelectorEntity.Resource,
	          title: resource.name,
	          subtitle: resourceType.name,
	          tabs: booking_const.EntitySelectorTab.Recent
	        });
	      });
	      this.onSelectorChange();
	      this.tryShowAhaMoment();
	    },
	    resources: {
	      handler() {
	        this.selector.getDialog().getItems().forEach(item => {
	          const resource = this.$store.getters[`${booking_const.Model.Resources}/getById`](item.getId());
	          if (!resource) {
	            return;
	          }
	          const resourceType = this.$store.getters[`${booking_const.Model.ResourceTypes}/getById`](resource.typeId);
	          item.setTitle(resource.name);
	          item.setSubtitle(resourceType.name);
	        });
	        this.selector.getTags().forEach(tag => {
	          const resource = this.$store.getters[`${booking_const.Model.Resources}/getById`](tag.getId());
	          if (!resource) {
	            return;
	          }
	          tag.setTitle(resource.name);
	          tag.render();
	        });
	      },
	      deep: true
	    }
	  },
	  template: `
		<div
			ref="intersectionField"
			class="booking-booking-intersections-line"
			data-id="booking-booking-intersections-line"
			@click="click"
		></div>
	`
	};

	const Intersections = {
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon,
	    Multiple,
	    Single
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      intersectionModeMenuItemId: 'booking-intersection-menu-mode'
	    };
	  },
	  mounted() {
	    this.menu = main_popup.MenuManager.create('booking-intersection-menu', this.$refs.intersectionMenu, this.getMenuItems(), {
	      closeByEsc: true,
	      autoHide: true,
	      cacheable: true
	    });
	  },
	  unmounted() {
	    this.menu.destroy();
	    this.menu = null;
	  },
	  methods: {
	    showMenu() {
	      if (this.isFeatureEnabled) {
	        this.menu.show();
	      } else {
	        void booking_lib_limit.limit.show();
	      }
	    },
	    getMenuItems() {
	      return [this.getIntersectionForAllItem(), {
	        delimiter: true
	      }, this.getHelpDeskItem()];
	    },
	    getIntersectionForAllItem() {
	      return {
	        id: this.intersectionModeMenuItemId,
	        dataset: {
	          id: this.intersectionModeMenuItemId
	        },
	        text: this.loc('BOOKING_BOOKING_INTERSECTION_MENU_ALL'),
	        className: this.isIntersectionForAll ? 'menu-popup-item menu-popup-item-accept' : 'menu-popup-item menu-popup-no-icon',
	        onclick: () => {
	          this.menu.close();
	          const value = !this.isIntersectionForAll;
	          void this.$store.dispatch(`${booking_const.Model.Interface}/setIntersectionMode`, value);
	          void booking_provider_service_optionService.optionService.setBool(booking_const.Option.IntersectionForAll, value);
	        }
	      };
	    },
	    getHelpDeskItem() {
	      return {
	        id: 'booking-intersection-menu-info',
	        dataset: {
	          id: 'booking-intersection-menu-info'
	        },
	        text: this.loc('BOOKING_BOOKING_INTERSECTION_MENU_HOW'),
	        onclick: () => {
	          booking_lib_helpDesk.helpDesk.show(booking_const.HelpDesk.Intersection.code, booking_const.HelpDesk.Intersection.anchorCode);
	        }
	      };
	    },
	    async showIntersections(selectedResourceIds, resourceId = 0) {
	      const intersections = {
	        ...(resourceId === 0 ? {} : this.intersections),
	        [resourceId]: selectedResourceIds
	      };
	      await this.$store.dispatch(`${booking_const.Model.Interface}/setIntersections`, intersections);
	      await booking_lib_busySlots.busySlots.loadBusySlots();
	    },
	    toggleMenuItemActivityState(item) {
	      main_core.Dom.toggleClass(item.getContainer(), 'menu-popup-item-accept');
	      main_core.Dom.toggleClass(item.getContainer(), 'menu-popup-no-icon');
	    },
	    updateScroll() {
	      if (this.$refs.inner) {
	        this.$refs.inner.scrollLeft = this.scroll;
	      }
	    }
	  },
	  watch: {
	    async isIntersectionForAll() {
	      await this.$store.dispatch(`${booking_const.Model.Interface}/setIntersections`, {});
	      this.updateScroll();
	      await booking_lib_busySlots.busySlots.loadBusySlots();
	      this.toggleMenuItemActivityState(this.menu.getMenuItem(this.intersectionModeMenuItemId));
	    },
	    scroll() {
	      this.updateScroll();
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      resourcesIds: `${booking_const.Model.Interface}/resourcesIds`,
	      isFilterMode: `${booking_const.Model.Filter}/isFilterMode`,
	      isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`,
	      intersections: `${booking_const.Model.Interface}/intersections`,
	      isIntersectionForAll: `${booking_const.Model.Interface}/isIntersectionForAll`,
	      scroll: `${booking_const.Model.Interface}/scroll`,
	      isLoaded: `${booking_const.Model.Interface}/isLoaded`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    hasIntersections() {
	      return Object.values(this.intersections).some(resourcesIds => resourcesIds.length > 0);
	    },
	    disabled() {
	      return !this.isLoaded || this.isFilterMode || this.isEditingBookingMode;
	    }
	  },
	  template: `
		<div class="booking-booking-intersections" :class="{'--disabled': disabled}">
			<div
				ref="intersectionMenu"
				class="booking-booking-intersections-left-panel"
				:class="{'--active': hasIntersections}"
				@click="showMenu"
				data-id="booking-intersections-left-panel-menu"
			>
				<div class="ui-icon-set --double-rhombus"></div>
				<div v-if="!isFeatureEnabled" class="booking-lock-icon-container">
					<Icon :name="IconSet.LOCK"/>
				</div>
			</div>
			<Single v-if="isIntersectionForAll" @change="showIntersections"/>
			<template v-else>
				<div
					ref="inner"
					class="booking-booking-intersections-inner"
					@scroll="$store.dispatch('interface/setScroll', $refs.inner.scrollLeft)"
				>
					<div class="booking-booking-intersections-row">
						<div class="booking-booking-intersections-row-inner">
							<template v-for="resourceId of resourcesIds" :key="resourceId">
								<Multiple :resourceId="resourceId" @change="showIntersections"/>
							</template>
						</div>
					</div>
					<div class="booking-booking-intersections-inner-blank"></div>
				</div>
			</template>
		</div>
	`
	};

	const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');

	// @vue/component
	const BaseComponent = {
	  name: 'BaseComponent',
	  components: {
	    Header,
	    Intersections,
	    Grid
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      fromHour: 'interface/fromHour',
	      toHour: 'interface/toHour',
	      zoom: 'interface/zoom'
	    }),
	    isAmPmMode() {
	      if (main_date.DateTimeFormat.isAmPmMode()) {
	        return true;
	      }
	      const now = main_date.DateTimeFormat.format(timeFormat, Date.now());
	      return now.endsWith('am') || now.endsWith('pm');
	    }
	  },
	  template: `
		<div
			id="booking-content"
			class="booking --ui-context-content-light"
			:style="{
				'--from-hour': fromHour,
				'--to-hour': toHour,
				'--zoom': zoom,
			}"
			:class="{
				'--zoom-is-less-than-07': zoom < 0.7,
				'--zoom-is-less-than-08': zoom < 0.8,
				'--am-pm-mode': isAmPmMode,
			}"
		>
			<Header/>
			<Intersections/>
			<Grid/>
		</div>
	`
	};

	const BookingMultipleButton = {
	  name: 'BookingMultipleButton',
	  emits: ['book'],
	  props: {
	    fetching: Boolean
	  },
	  computed: {
	    text() {
	      return this.loc('BOOKING_MULTI_BUTTON_LABEL');
	    },
	    size() {
	      return booking_component_button.ButtonSize.SMALL;
	    },
	    color() {
	      return booking_component_button.ButtonColor.SUCCESS;
	    }
	  },
	  components: {
	    UiButton: booking_component_button.Button
	  },
	  template: `
		<UiButton
			:text
			:size
			:color
			:waiting="fetching"
			@click="$emit('book')"
		/>
	`
	};

	// @vue/component
	const MultiBookingItem = {
	  name: 'MultiBookingItem',
	  components: {
	    UiButton: booking_component_button.Button
	  },
	  emits: ['remove-selected'],
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    fromTs: {
	      type: Number,
	      required: true
	    },
	    toTs: {
	      type: Number,
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      offset: `${booking_const.Model.Interface}/offset`
	    }),
	    label() {
	      return this.loc('BOOKING_MULTI_ITEM_TITLE', {
	        '#DATE#': main_date.DateTimeFormat.format('d M H:i', (this.fromTs + this.offset) / 1000),
	        '#DURATION#': new booking_lib_duration.Duration(this.toTs - this.fromTs).format()
	      });
	    },
	    buttonColor() {
	      return booking_component_button.ButtonColor.LINK;
	    },
	    buttonSize() {
	      return booking_component_button.ButtonSize.EXTRA_SMALL;
	    }
	  },
	  template: `
		<div class="booking--multi-booking--book">
			<label>
				{{ label }}
			</label>
			<button
				:class="[buttonSize, buttonColor, 'ui-btn ui-icon-set --cross-20']"
				type="button"
				@click="$emit('remove-selected', this.id)">
			</button>
		</div>
	`
	};

	// @vue/component
	const MultiBookingItemsList = {
	  name: 'MultiBookingItemsList',
	  components: {
	    MultiBookingItem
	  },
	  emits: ['remove-selected'],
	  computed: {
	    selectedCells() {
	      return this.$store.getters[`${booking_const.Model.Interface}/selectedCells`];
	    },
	    selectedCellsCount() {
	      return Object.keys(this.selectedCells).length;
	    }
	  },
	  mounted() {
	    this.ears = new ui_ears.Ears({
	      container: this.$refs.wrapper,
	      smallSize: true,
	      className: 'booking--multi-booking--items-ears',
	      noScrollbar: true
	    }).init();
	  },
	  watch: {
	    selectedCellsCount: {
	      handler() {
	        setTimeout(() => this.ears.toggleEars(), 0);
	      }
	    }
	  },
	  template: `
		<div class="booking--multi-booking--book-list">
			<div ref="wrapper" class="booking--multi-booking--books-wrapper">
				<MultiBookingItem
					v-for="cell in selectedCells"
					:key="cell.id"
					:id="cell.id"
					:from-ts="cell.fromTs"
					:to-ts="cell.toTs"
					:resource-id="cell.resourceId"
					@remove-selected="$emit('remove-selected', $event)"/>
			</div>
		</div>
	`
	};

	const AddClientButton = {
	  name: 'AddClientButton',
	  emits: ['update:model-value'],
	  props: {
	    modelValue: {
	      type: Array,
	      required: true
	    }
	  },
	  data() {
	    return {
	      isPopupShown: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      getByClientData: `${booking_const.Model.Clients}/getByClientData`
	    }),
	    color() {
	      return booking_component_button.ButtonColor.LINK;
	    },
	    size() {
	      return booking_component_button.ButtonSize.EXTRA_SMALL;
	    },
	    label() {
	      var _this$modelValue$find;
	      if (this.modelValue.length === 0) {
	        return this.loc('BOOKING_MULTI_CLIENT');
	      }
	      return this.loc('BOOKING_MULTI_CLIENT_WHIT_NAME', {
	        '#NAME#': ((_this$modelValue$find = this.modelValue.find(client => client.name)) == null ? void 0 : _this$modelValue$find.name) || ''
	      });
	    },
	    currentClient() {
	      if (this.modelValue.length === 0) {
	        return null;
	      }
	      return {
	        contact: this.findClientByType(booking_const.CrmEntity.Contact),
	        company: this.findClientByType(booking_const.CrmEntity.Company)
	      };
	    }
	  },
	  methods: {
	    createClients(clients) {
	      const clientsData = clients.map(client => this.getByClientData(client));
	      this.$emit('update:model-value', clientsData);
	    },
	    findClientByType(clientTypeCode) {
	      return this.modelValue.find(({
	        type
	      }) => type.code === clientTypeCode);
	    }
	  },
	  components: {
	    ClientPopup: booking_component_clientPopup.ClientPopup
	  },
	  template: `
		<button
			:class="['ui-btn', 'booking--multi-booking--client-button', color, size]"
			type="button"
			ref="button"
			@click="isPopupShown = !isPopupShown"
		>
			<i class="ui-icon-set --customer-card"></i>
			<span>{{ label }}</span>
		</button>
		<ClientPopup
			v-if="isPopupShown"
			:bind-element="$refs.button"
			:currentClient
			@create="createClients"
			@close="isPopupShown = false"/>
	`
	};

	const CancelButton = {
	  name: 'CancelButton',
	  emits: ['click'],
	  setup() {
	    return {
	      color: booking_component_button.ButtonColor.LINK,
	      size: booking_component_button.ButtonSize.EXTRA_SMALL
	    };
	  },
	  template: `
		<button
			:class="['ui-btn', 'booking--multi-booking--cancel-button', color, size]"
			type="button"
			ref="button"
			@click="$emit('click')"
		>
			<i
				class="ui-icon-set --cross-25"
				style="--ui-icon-set__icon-base-color: rgba(var(--ui-color-palette-white-base-rgb), 0.3);--ui-icon-set__icon-size: var(--ui-size-2xl)"></i>
		</button>
	`
	};

	const MultiBooking = {
	  name: 'MultiBooking',
	  data() {
	    return {
	      fetching: false,
	      clients: [],
	      externalData: []
	    };
	  },
	  async beforeMount() {
	    this.clients = [];
	    this.externalData = [];
	    if (this.embedItems.length === 0) {
	      return;
	    }
	    const embedContact = this.embedItems.find(item => item.entityTypeId === booking_const.CrmEntity.Contact);
	    const embedCompany = this.embedItems.find(item => item.entityTypeId === booking_const.CrmEntity.Company);
	    const embedDeal = this.embedItems.find(item => item.entityTypeId === booking_const.CrmEntity.Deal);
	    if (embedContact) {
	      const contact = await booking_provider_service_clientService.clientService.getContactById(Number(embedContact.value));
	      if (contact) {
	        this.clients.push(contact);
	      }
	    }
	    if (embedCompany) {
	      const company = await booking_provider_service_clientService.clientService.getCompanyById(Number(embedCompany.value));
	      if (company) {
	        this.clients.push(company);
	      }
	    }
	    if (embedDeal) {
	      this.externalData.push(embedDeal);
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      selectedCells: `${booking_const.Model.Interface}/selectedCells`,
	      timezone: `${booking_const.Model.Interface}/timezone`,
	      embedItems: `${booking_const.Model.Interface}/embedItems`,
	      intersections: `${booking_const.Model.Interface}/intersections`
	    })
	  },
	  methods: {
	    removeSelected(id) {
	      if (Object.hasOwnProperty.call(this.selectedCells, id)) {
	        this.$store.dispatch(`${booking_const.Model.Interface}/removeSelectedCell`, this.selectedCells[id]);
	      }
	    },
	    async book() {
	      const bookings = this.getBookings();
	      if (bookings.length === 0) {
	        return;
	      }
	      this.fetching = true;
	      const bookingList = await booking_provider_service_bookingService.bookingService.addList(bookings);
	      booking_lib_analytics.BookingAnalytics.sendAddMultiBookings(bookingList.map(({
	        id
	      }) => id));
	      this.fetching = false;
	      this.showNotification(bookingList);
	      await this.closeMultiBooking();
	    },
	    getBookings() {
	      return Object.values(this.selectedCells).map(cell => {
	        var _this$intersections$, _this$intersections$c;
	        return {
	          id: `tmp-id-${Date.now()}-${Math.random()}`,
	          dateFromTs: cell.fromTs,
	          dateToTs: cell.toTs,
	          resourcesIds: [...new Set([cell.resourceId, ...((_this$intersections$ = this.intersections[0]) != null ? _this$intersections$ : []), ...((_this$intersections$c = this.intersections[cell.resourceId]) != null ? _this$intersections$c : [])])],
	          timezoneFrom: this.timezone,
	          timezoneTo: this.timezone,
	          externalData: this.externalData,
	          clients: this.clients
	        };
	      });
	    },
	    showNotification(bookingList) {
	      const bookingQuantity = bookingList.length;
	      const balloon = BX.UI.Notification.Center.notify({
	        id: main_core.Text.getRandom(),
	        content: main_core.Loc.getMessagePlural('BOOKING_MULTI_CREATED', bookingQuantity, {
	          '#QUANTITY#': bookingQuantity
	        }),
	        actions: [{
	          title: this.loc('BOOKING_MULTI_CREATED_CANCEL'),
	          events: {
	            click: () => this.reset(bookingList, balloon)
	          }
	        }]
	      });
	    },
	    async reset(bookingList, balloon) {
	      await booking_provider_service_bookingService.bookingService.deleteList(bookingList.map(({
	        id
	      }) => id));
	      balloon == null ? void 0 : balloon.close();
	    },
	    async closeMultiBooking() {
	      await this.$store.dispatch(`${booking_const.Model.Interface}/clearSelectedCells`);
	    }
	  },
	  components: {
	    BookingMultipleButton,
	    MultiBookingItemsList,
	    AddClientButton,
	    CancelButton
	  },
	  template: `
		<Teleport to="#uiToolbarContainer" defer>
			<div class="booking--multi-booking--bar">
				<BookingMultipleButton :fetching @book="book"/>
				<MultiBookingItemsList @remove-selected="removeSelected"/>
				<div class="booking--multi-booking--divider-vertical"></div>
				<AddClientButton v-model="clients"/>
				<div class="booking--multi-booking--space"></div>
				<div class="booking--multi-booking--close">
					<div class="booking--multi-booking--divider-vertical"></div>
					<CancelButton @click="closeMultiBooking"/>
				</div>
			</div>
		</Teleport>
	`
	};

	const Banner = {
	  data() {
	    return {
	      isBannerShown: false,
	      bannerComponent: null
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      canTurnOnDemo: `${booking_const.Model.Interface}/canTurnOnDemo`
	    })
	  },
	  mounted() {
	    if (booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.Banner)) {
	      void this.showBanner();
	    }
	  },
	  methods: {
	    async showBanner() {
	      ui_bannerDispatcher.BannerDispatcher.critical.toQueue(async onDone => {
	        const {
	          PromoBanner
	        } = await main_core.Runtime.loadExtension('booking.component.promo-banner');
	        this.bannerComponent = ui_vue3.shallowRef(PromoBanner);
	        this.isBannerShown = true;
	        this.setShown();
	        this.bannerClosed = new booking_lib_resolvable.Resolvable();
	        await this.bannerClosed;
	        onDone();
	      });
	    },
	    closeBanner() {
	      this.isBannerShown = false;
	      this.bannerClosed.resolve();
	    },
	    setShown() {
	      booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.Banner);
	      booking_lib_analytics.BannerAnalytics.sendShowPopup();
	    },
	    buttonClick() {
	      booking_lib_analytics.BannerAnalytics.sendClickEnable();
	    }
	  },
	  template: `
		<component
			v-if="isBannerShown"
			:is="bannerComponent"
			:canTurnOnDemo="canTurnOnDemo"
			@buttonClick="buttonClick"
			@close="closeBanner"
		/>
	`
	};

	const Trial = {
	  data() {
	    return {
	      isBannerShown: false,
	      bannerComponent: null
	    };
	  },
	  watch: {
	    isShownTrialPopup() {
	      if (booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.TrialBanner)) {
	        if (!ui_autoLaunch.AutoLauncher.isEnabled()) {
	          ui_autoLaunch.AutoLauncher.enable();
	        }
	        void this.showBanner();
	      }
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      isShownTrialPopup: `${booking_const.Model.Interface}/isShownTrialPopup`
	    })
	  },
	  methods: {
	    async showBanner() {
	      ui_bannerDispatcher.BannerDispatcher.critical.toQueue(async onDone => {
	        const {
	          TrialBanner
	        } = await main_core.Runtime.loadExtension('booking.component.trial-banner');
	        this.bannerComponent = ui_vue3.shallowRef(TrialBanner);
	        this.isBannerShown = true;
	        this.bannerClosed = new booking_lib_resolvable.Resolvable();
	        await this.bannerClosed;
	        onDone();
	      });
	    },
	    closeBanner() {
	      this.isBannerShown = false;
	      booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.TrialBanner);
	      this.bannerClosed.resolve();
	    }
	  },
	  template: `
		<component v-if="isBannerShown" :is="bannerComponent" @close="closeBanner"/>
	`
	};

	// @vue/component
	const CrmFormsPopupLayout = {
	  name: 'CrmFormsPopupLayout',
	  components: {
	    HelpDeskLoc: booking_component_helpDeskLoc.HelpDeskLoc
	  },
	  setup() {
	    const helpDesk = booking_const.HelpDesk.CrmFormsPopup;
	    return {
	      helpDesk
	    };
	  },
	  template: `
		<div class="booking--booking--crm-forms-popup__wrapper">
			<div class="booking--booking--crm-forms-popup__header">
				<span class="booking--booking--crm-forms-popup__header-title">
					{{ loc('BOOKING_OPEN_CRM_FORMS_POPUP_TITLE') }}
				</span>
				<div class="booking--booking--crm-forms-popup__header-description">
					<HelpDeskLoc
						:message="loc('BOOKING_OPEN_CRM_FORMS_POPUP_DESCRIPTION')"
						:code="helpDesk.code"
						:anchor="helpDesk.anchorCode"
					/>
				</div>
			</div>
			<div class="booking--booking--crm-forms-popup__toolbar">
				<slot name="toolbar"/>
			</div>
			<div class="booking--booking-crm-forms-popup__list-wrapper">
				<slot/>
			</div>
			<div class="booking--booking-crm-forms-popup__footer">
				<slot name="footer"/>
			</div>
		</div>
	`
	};

	// @vue/component
	const CrmFormItemSkeleton = {
	  name: 'CrmFormItemSkeleton',
	  template: `
		<div class="booking--booking--crm-forms-popup--item-skeleton">
			<div class="booking--booking--crm-forms-popup--item-skeleton__circle"></div>
			<div class="booking--booking--crm-forms-popup--item-skeleton__line"></div>
			<div class="booking--booking--crm-forms-popup--item-skeleton__space"></div>
			<div class="booking--booking--crm-forms-popup--item-skeleton__line --thin"></div>
			<div class="booking--booking--crm-forms-popup--item-skeleton__circle"></div>
		</div>
	`
	};

	// @vue/component
	const CrmFormListEmpty = {
	  name: 'CrmFormListEmpty',
	  components: {
	    CrmFormItemSkeleton
	  },
	  template: `
		<CrmFormItemSkeleton v-for="i in 4" :key="i"/>
		<div class="booking--booking--crm-forms-popup--list__placeholder-bg"></div>
		<div class="booking--booking--crm-forms-popup--list__placeholder">
			<span>{{ loc('BOOKING_OPEN_CRM_FORMS_POPUP_FORMS_LIST_PLACEHOLDER') }}</span>
		</div>
	`
	};

	function CrmFormsListItemNameLink(props, {
	  slots
	}) {
	  if (props.canEdit && props.editUrl) {
	    return ui_vue3.h('a', {
	      href: props.editUrl,
	      target: '_blank'
	    }, slots.default());
	  }
	  return slots.default();
	}
	const props = ['canEdit', 'editUrl'];
	CrmFormsListItemNameLink.props = props;

	// eslint-disable-next-line no-unused-vars

	// @vue/component
	const CrmFormsListItem = {
	  name: 'CrmFormsListItem',
	  components: {
	    CrmFormsListItemNameLink
	  },
	  props: {
	    /**
	     * @type {FormsMenuItem}
	     */
	    item: {
	      type: Object,
	      required: true
	    },
	    canEdit: Boolean
	  },
	  setup() {
	    return {
	      AirButtonStyle: booking_component_button.AirButtonStyle
	    };
	  },
	  data() {
	    return {
	      copedPopupTimeoutId: null
	    };
	  },
	  methods: {
	    copyLink() {
	      if (!BX.clipboard.isCopySupported()) {
	        return;
	      }
	      BX.clipboard.copy(this.item.publicUrl);
	      this.showCopedPopup();
	    },
	    showCopedPopup() {
	      var _this$popup;
	      (_this$popup = this.popup) == null ? void 0 : _this$popup.destroy();
	      this.popup = new BX.PopupWindow(`booking_open_crm_forms_popup_item_${this.item.id}_clipboard_copy`, this.$refs.copy, {
	        content: this.loc('BOOKING_OPEN_CRM_FORMS_POPUP_FORMS_LIST_ITEM_LINK_COPED'),
	        darkMode: true,
	        autoHide: true,
	        zIndex: 1000,
	        angle: true,
	        offsetLeft: 20,
	        bindOptions: {
	          position: 'top'
	        }
	      });
	      this.popup.show();
	      this.copedPopupTimeoutId = setTimeout(() => {
	        var _this$popup2;
	        this.popup.close();
	        (_this$popup2 = this.popup) == null ? void 0 : _this$popup2.destroy();
	      }, 1200);
	    }
	  },
	  template: `
		<div class="booking--booking--crm-forms-popup--item">
			<div class="ui-icon-set --o-form booking--booking--crm-forms-popup--item__icon"></div>
			<div
				class="booking--booking--crm-forms-popup--item__name"
				:title="item.name"
			>
				<CrmFormsListItemNameLink :canEdit :editUrl="item.editUrl">
					<span>{{ item.name }}</span>
				</CrmFormsListItemNameLink>
			</div>
			<div
				ref="copy"
				:class="['booking--booking--crm-forms-popup--item__copy-btn', AirButtonStyle.OUTLINE_NO_ACCENT]"
				role="button"
				tabindex="0"
				@click="copyLink"
			>
				{{ loc('BOOKING_OPEN_CRM_FORMS_POPUP_FORMS_LIST_ITEM_COPY_LINK_BUTTON_LABEL') }}
				<div class="ui-icon-set --o-copy booking--booking--crm-forms-popup--item__copy-btn-icon"></div>
			</div>
		</div>
	`
	};

	// @vue/component
	const CrmFormsList = {
	  name: 'CrmFormsList',
	  components: {
	    CrmFormListEmpty,
	    CrmFormsListItem
	  },
	  computed: {
	    canEdit() {
	      return this.$store.state[booking_const.Model.FormsMenu].canEdit;
	    },
	    formList() {
	      return this.$store.getters[`${booking_const.Model.FormsMenu}/formList`];
	    }
	  },
	  template: `
		<div class="booking--booking--crm-forms-popup--list__container">
			<div class="booking--booking--crm-forms-popup--list-items">
				<template v-if="formList.length === 0">
					<CrmFormListEmpty />
				</template>
				<template v-else>
					<CrmFormsListItem
						v-for="item in formList"
						:key="item.id"
						:item="item"
						:canEdit
					/>
				</template>
			</div>
		</div>
	`
	};

	// @vue/component
	const CrmFormsToolbar = {
	  name: 'CrmFormsToolbar',
	  template: `
		<div class="booking--booking--crm-forms-popup-sort">
			<span>{{ loc('BOOKING_OPEN_CRM_FORMS_POPUP_SORT_LATEST_CREATED_LABEL') }}</span>
			<div class="ui-icon-set --o-change-order-2 booking--booking--crm-forms-popup--sort-icon"></div>
		</div>
	`
	};

	// @vue/component
	const AddCrmFormButton = {
	  name: 'AddCrmFormButton',
	  components: {
	    UiButton: booking_component_button.Button
	  },
	  setup() {
	    return {
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonStyle: booking_component_button.ButtonStyle
	    };
	  },
	  computed: {
	    createFormLink() {
	      return this.$store.state[booking_const.Model.FormsMenu].createFormLink;
	    },
	    addBtnLabel() {
	      return this.loc('BOOKING_OPEN_CRM_FORMS_POPUP_ADD_FORM_BUTTON_LABEL').replace('[plus]', '+');
	    }
	  },
	  template: `
		<a :href="createFormLink" target="_blank">
			<UiButton
				:text="addBtnLabel"
				:color="ButtonColor.LIGHT_BORDER"
				:size="ButtonSize.SMALL"
				:buttonClass="['--air', ButtonStyle.NO_CAPS]"
			/>
		</a>
	`
	};

	// @vue/component
	const AllCrmFormsButton = {
	  name: 'AllCrmFormsButton',
	  components: {
	    UiButton: booking_component_button.Button
	  },
	  setup() {
	    return {
	      AirButtonStyle: booking_component_button.AirButtonStyle,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonStyle: booking_component_button.ButtonStyle
	    };
	  },
	  template: `
		<a href="/crm/webform/" target="_blank">
			<UiButton
				:text="loc('BOOKING_OPEN_CRM_FORMS_POPUP_ALL_FORMS_BUTTON_LABEL')"
				:color="ButtonColor.LIGHT_BORDER"
				:size="ButtonSize.SMALL"
				:buttonClass="['--air', AirButtonStyle.OUTLINE_NO_ACCENT, ButtonStyle.NO_CAPS]"
			/>
		</a>
	`
	};

	// @vue/component
	const CrmFormsPopup = {
	  name: 'CrmFormsPopup',
	  components: {
	    HelpDeskLoc: booking_component_helpDeskLoc.HelpDeskLoc,
	    Popup: booking_component_popup.Popup,
	    CrmFormsList,
	    CrmFormsPopupLayout,
	    CrmFormsToolbar,
	    AddCrmFormButton,
	    AllCrmFormsButton
	  },
	  props: {
	    visible: {
	      type: Boolean,
	      default: false
	    },
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    }
	  },
	  emits: ['update:visible'],
	  computed: {
	    config() {
	      return {
	        className: 'booking--booking--crm-forms-popup',
	        bindElement: this.bindElement,
	        offsetTop: 10,
	        padding: 24,
	        minHeight: 376,
	        minWidth: 488,
	        maxWidth: 500,
	        bindOptions: {
	          forceBindPosition: true,
	          position: 'bottom'
	        },
	        angle: {
	          offset: this.bindElement.offsetWidth / 2
	        }
	      };
	    }
	  },
	  methods: {
	    closePopup() {
	      this.$emit('update:visible', false);
	    }
	  },
	  template: `
		<Popup
			ref="popup"
			id="booking-crm-forms-popup"
			:config
			@close="closePopup"
		>
			<CrmFormsPopupLayout>
				<template #toolbar>
					<CrmFormsToolbar />
				</template>
				<CrmFormsList />
				<template #footer>
					<div class="booking--booking--crm-forms-popup--footer-buttons-bar">
						<AllCrmFormsButton />
						<AddCrmFormButton />
					</div>
				</template>
			</CrmFormsPopupLayout>
		</Popup>
	`
	};

	// @vue/component
	const CrmFormsButton = {
	  name: 'CrmFormsButton',
	  components: {
	    CrmFormsPopup,
	    UiButton: booking_component_button.Button
	  },
	  props: {
	    container: {
	      type: HTMLElement,
	      required: true
	    }
	  },
	  setup() {
	    const isPopupShown = ui_vue3.ref(false);
	    const openMenu = () => {
	      isPopupShown.value = true;
	    };
	    return {
	      isPopupShown,
	      AirButtonStyle: booking_component_button.AirButtonStyle,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonStyle: booking_component_button.ButtonStyle,
	      openMenu
	    };
	  },
	  computed: {
	    label() {
	      return this.loc('BOOKING_OPEN_CRM_FORMS_BUTTON_LABEL');
	    }
	  },
	  mounted() {
	    this.container.replaceWith(this.$refs.button.$el);
	  },
	  template: `
		<UiButton
			ref="button"
			:buttonClass="['--air', ButtonStyle.NO_CAPS, AirButtonStyle.OUTLINE_NO_ACCENT]"
			:text="label"
			:color="ButtonColor.LIGHT_BORDER"
			:size="ButtonSize.SMALL"
			@click="openMenu"
		/>
		<CrmFormsPopup
			v-if="isPopupShown"
			v-model:visible="isPopupShown"
			:bindElement="$refs.button.$el"
		/>
	`
	};

	// @vue/component
	const App = {
	  name: 'BookingApp',
	  components: {
	    BaseComponent,
	    AfterTitle,
	    BookingFilter: Filter,
	    CountersPanel,
	    MultiBooking,
	    Banner,
	    Trial,
	    CrmFormsButton,
	    EmptyFilterResultsPopup: booking_component_emptyFilterResultsPopup.EmptyFilterResultsPopup
	  },
	  props: {
	    afterTitleContainer: HTMLElement,
	    counterPanelContainer: HTMLElement,
	    settingsButtonContainer: HTMLElement,
	    filterId: {
	      type: String,
	      required: true
	    }
	  },
	  data() {
	    return {
	      loadingFilter: false,
	      loader: new main_loader.Loader()
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      selectedDateTs: `${booking_const.Model.Interface}/selectedDateTs`,
	      viewDateTs: `${booking_const.Model.Interface}/viewDateTs`,
	      isFilterMode: `${booking_const.Model.Filter}/isFilterMode`,
	      isDeletingResourceFilterMode: `${booking_const.Model.Filter}/isDeletingResourceFilterMode`,
	      deletingResource: `${booking_const.Model.Filter}/deletingResource`,
	      filteredBookingsIds: `${booking_const.Model.Filter}/filteredBookingsIds`,
	      selectedCells: `${booking_const.Model.Interface}/selectedCells`,
	      resourcesIds: `${booking_const.Model.Favorites}/get`,
	      extraResourcesIds: `${booking_const.Model.Interface}/extraResourcesIds`,
	      bookings: `${booking_const.Model.Bookings}/get`,
	      getBookingsByIds: `${booking_const.Model.Bookings}/getByIds`,
	      getFutureBookingsByResourceId: `${booking_const.Model.Bookings}/getFutureByResourceId`,
	      intersections: `${booking_const.Model.Interface}/intersections`,
	      editingBookingId: `${booking_const.Model.Interface}/editingBookingId`,
	      fetchingNextDate: `${booking_const.Model.Filter}/fetchingNextDate`,
	      datesCount: `${booking_const.Model.Filter}/datesCount`,
	      requestFields: `${booking_const.Model.Filter}/requestFields`
	    }),
	    hasSelectedCells() {
	      return Object.keys(this.selectedCells).length > 0;
	    },
	    editingBooking() {
	      var _this$$store$getters$;
	      return (_this$$store$getters$ = this.$store.getters['bookings/getById'](this.editingBookingId)) != null ? _this$$store$getters$ : null;
	    }
	  },
	  watch: {
	    selectedDateTs() {
	      if (this.isFilterMode) {
	        void this.applyFilter();
	      } else if (this.isDeletingResourceFilterMode) {
	        void this.applyDeletingResourceFilter(this.deletingResource);
	      } else {
	        void this.fetchPage(this.selectedDateTs / 1000);
	      }
	    },
	    filteredBookingsIds() {
	      if (this.isFilterMode) {
	        this.showResourcesWithBookings();
	      }
	    },
	    isFilterMode(isFilterMode) {
	      if (!isFilterMode) {
	        void this.fetchPage(this.selectedDateTs / 1000);
	      }
	    },
	    isDeletingResourceFilterMode(isDeletingResourceFilterMode) {
	      if (isDeletingResourceFilterMode) {
	        booking_lib_filterResultNavigator.deletingResourceFilterResultCountActualizer.subscribe();
	      } else {
	        booking_lib_filterResultNavigator.deletingResourceFilterResultCountActualizer.unsubscribe();
	        void this.clearDeletingResourceFilter();
	        void this.fetchPage(this.selectedDateTs / 1000);
	      }
	    },
	    viewDateTs() {
	      void this.updateMarks();
	    },
	    resourcesIds(resourcesIds) {
	      if (this.isDeletingResourceFilterMode) {
	        if (resourcesIds.includes(this.deletingResource.id)) {
	          this.$store.dispatch(`${booking_const.Model.Interface}/setPinnedResourceIds`, [...resourcesIds]);
	        } else {
	          void this.clearDeletingResourceFilter();
	        }
	      }
	      void this.updateMarks();
	    },
	    intersections() {
	      void this.updateMarks();
	    },
	    editingBooking(booking) {
	      var _booking$resourcesIds, _booking$resourcesIds2;
	      const additionalResourcesIds = (_booking$resourcesIds = booking == null ? void 0 : (_booking$resourcesIds2 = booking.resourcesIds) == null ? void 0 : _booking$resourcesIds2.slice(1)) != null ? _booking$resourcesIds : [];
	      if (additionalResourcesIds.length > 0) {
	        void this.$store.dispatch(`${booking_const.Model.Interface}/setIntersections`, {
	          0: additionalResourcesIds
	        });
	      }
	    },
	    fetchingNextDate(fetching) {
	      if (fetching) {
	        this.showLoader();
	      } else {
	        this.hideLoader();
	      }
	    },
	    deletingResource(resource) {
	      if (resource !== null) {
	        void this.applyDeletingResourceFilter(resource, true);
	      }
	    }
	  },
	  beforeMount() {
	    booking_lib_mousePosition.mousePosition.init();
	  },
	  async mounted() {
	    this.showLoader();
	    expandOffHours.setExpanded(true);
	    this.addAfterTitle();
	    booking_lib_analytics.SectionAnalytics.sendOpenSection();
	    await Promise.all([booking_provider_service_dictionaryService.dictionaryService.fetchData(), this.fetchPage(this.editingBookingId > 0 ? 0 : this.selectedDateTs / 1000)]);
	    void this.$store.dispatch(`${booking_const.Model.Interface}/setIsLoaded`, true);
	  },
	  beforeUnmount() {
	    booking_lib_mousePosition.mousePosition.destroy();
	  },
	  methods: {
	    async fetchPage(dateTs = 0) {
	      this.showLoader();
	      await booking_provider_service_mainPageService.mainPageService.fetchData(dateTs);
	      if (this.extraResourcesIds.length > 0) {
	        await booking_provider_service_resourceDialogService.resourceDialogService.loadByIds(this.extraResourcesIds, this.selectedDateTs / 1000);
	      }
	      this.hideLoader();
	    },
	    onActiveItem(counterItem) {
	      if (this.ignoreConterPanel) {
	        return;
	      }
	      const fields = this.getFilterFieldsByCounterItem(counterItem);
	      this.$refs.filter.setFields(fields);
	    },
	    getFilterFields() {
	      const fields = this.$refs.filter.getFields();
	      if (this.isDeletingResourceFilterMode && 'RESOURCE' in fields && Object.keys(fields).length === 1) {
	        return {
	          RESOURCE: this.$store.getters[`${booking_const.Model.Interface}/resourcesIds`]
	        };
	      }
	      return this.$refs.filter.getFields();
	    },
	    async applyFilter({
	      fromFilter = false
	    } = {}) {
	      const fields = this.$refs.filter.getFields();
	      this.setCounterItem(this.getCounterItemByFilterFields(fields));
	      this.showLoader();
	      await this.$store.dispatch(`${booking_const.Model.Filter}/setFilterFields`, this.$refs.filter.getFields());
	      await Promise.all([this.$store.dispatch(`${booking_const.Model.Filter}/setFilterMode`, true), this.updateMarks(),
	      // eslint-disable-next-line unicorn/no-array-callback-reference
	      booking_provider_service_bookingService.bookingService.filter(this.requestFields)]);
	      if (fromFilter && (this.filteredBookingsIds.length === 0 || Date.now() > this.selectedDateTs)) {
	        await this.tryNavigateToOptimalFilterResult();
	      }
	      this.hideLoader();
	    },
	    async applyDeletingResourceFilter(resource, force = false) {
	      if (!force && this.isDeletingResourceFilterMode && !this.isDeletionResourceFilterFields(this.$refs.filter.getFields())) {
	        await this.$store.dispatch(`${booking_const.Model.Filter}/setFilterMode`, true);
	        await this.clearDeletingResourceFilter(true);
	        await this.applyFilter({
	          fromFilter: true
	        });
	        return;
	      }
	      const fields = {
	        RESOURCE: [resource.id.toString()],
	        RESOURCE_label: [resource.name]
	      };
	      if (force) {
	        this.$refs.filter.setFields(fields);
	      }
	      const requestFields = this.getFilterFields();
	      this.showLoader();
	      await Promise.all([this.$store.dispatch(`${booking_const.Model.Filter}/setFilterFields`, fields), this.$store.dispatch(`${booking_const.Model.Filter}/setDeletionResourceFilterFields`, requestFields), this.$store.dispatch(`${booking_const.Model.Interface}/setPinnedResourceIds`, requestFields.RESOURCE)]);
	      await Promise.all([this.updateMarks(),
	      // eslint-disable-next-line unicorn/no-array-callback-reference
	      booking_provider_service_bookingService.bookingService.filter(this.requestFields)]);
	      if (force && this.getFutureBookingsByResourceId(resource.id).length === 0) {
	        await this.tryNavigateToOptimalFilterResult(true);
	      }
	      this.hideLoader();
	    },
	    isDeletionResourceFilterFields(fields) {
	      return Object.keys(fields).length === 1 && 'RESOURCE' in fields && fields.RESOURCE.length === 1 && fields.RESOURCE[0] === this.deletingResource.id.toString();
	    },
	    setCounterItem(item) {
	      this.ignoreConterPanel = true;
	      setTimeout(() => {
	        this.ignoreConterPanel = false;
	      }, 0);
	      this.$refs.countersPanel.setItem(item);
	    },
	    getCounterItemByFilterFields(filterFields) {
	      return {
	        [RequireAttention.AwaitConfirmation]: CounterItem.AwaitConfirmation,
	        [RequireAttention.Delayed]: CounterItem.Delayed
	      }[filterFields.REQUIRE_ATTENTION];
	    },
	    async tryNavigateToOptimalFilterResult(inFuture = false) {
	      const dateTs = await booking_lib_filterResultNavigator.filterResultNavigator.getOptimalFilterDateTs(inFuture);
	      if (dateTs && dateTs !== this.selectedDateTs) {
	        await this.$store.dispatch(`${booking_const.Model.Interface}/setSelectedDateTs`, dateTs);
	      }
	    },
	    getFilterFieldsByCounterItem(counterItem) {
	      const fields = this.$refs.filter.getFields();
	      fields.REQUIRE_ATTENTION = {
	        [CounterItem.AwaitConfirmation]: RequireAttention.AwaitConfirmation,
	        [CounterItem.Delayed]: RequireAttention.Delayed
	      }[counterItem];
	      return fields;
	    },
	    async clearFilter() {
	      this.setCounterItem(null);
	      booking_provider_service_calendarService.calendarService.clearFilterCache();
	      booking_provider_service_bookingService.bookingService.clearFilterCache();
	      await Promise.all([this.$store.dispatch(`${booking_const.Model.Interface}/setResourcesIds`, this.resourcesIds), this.$store.dispatch(`${booking_const.Model.Filter}/setFilterMode`, false), this.$store.dispatch(`${booking_const.Model.Filter}/setFilteredBookingsIds`, []), this.$store.dispatch(`${booking_const.Model.Filter}/setFilteredMarks`, []), this.$store.dispatch(`${booking_const.Model.Filter}/clearFilter`, {}), this.$store.dispatch(`${booking_const.Model.Interface}/setPinnedResourceIds`, [])]);
	      this.hideLoader();
	    },
	    async clearDeletingResourceFilter(modeOnly = false) {
	      await Promise.all([this.$store.dispatch(`${booking_const.Model.Filter}/setDeletingResourceFilter`, null), this.$store.dispatch(`${booking_const.Model.Interface}/setPinnedResourceIds`, [])]);
	      if (this.isFilterMode || modeOnly) {
	        return;
	      }
	      this.$refs.filter.setFields({
	        RESOURCE: [],
	        RESOURCE_label: []
	      });
	      await this.clearFilter();
	    },
	    clearDeletingResourceFilterFields() {
	      if (this.isDeletionResourceFilterFields(this.$refs.filter.getFields())) {
	        this.$refs.filter.setFields({
	          RESOURCE: [],
	          RESOURCE_label: []
	        });
	      }
	    },
	    addAfterTitle() {
	      this.afterTitleContainer.append(this.$refs.afterTitle.$el);
	    },
	    showResourcesWithBookings() {
	      const resourcesIds = this.$store.getters[`${booking_const.Model.Bookings}/getByDateAndIds`](this.selectedDateTs, this.filteredBookingsIds).map(booking => booking.resourcesIds[0]).filter((value, index, array) => array.indexOf(value) === index);
	      void this.$store.dispatch(`${booking_const.Model.Interface}/setResourcesIds`, resourcesIds);
	    },
	    async updateMarks() {
	      if (this.isFilterMode || this.isDeletingResourceFilterMode) {
	        await Promise.all([this.updateFilterBookingsCount(), this.updateFilterMarks()]);
	      } else {
	        await Promise.all([this.updateFreeMarks(), this.updateCounterMarks()]);
	      }
	    },
	    async updateFreeMarks() {
	      const resources = this.resourcesIds.map(id => {
	        var _this$intersections$, _this$intersections$i;
	        return [id, ...((_this$intersections$ = this.intersections[0]) != null ? _this$intersections$ : []), ...((_this$intersections$i = this.intersections[id]) != null ? _this$intersections$i : [])];
	      });
	      await this.$store.dispatch(`${booking_const.Model.Interface}/setFreeMarks`, []);
	      await booking_provider_service_calendarService.calendarService.loadMarks(this.viewDateTs, resources);
	    },
	    async updateFilterMarks() {
	      const fields = this.$refs.filter.getFields();
	      await this.$store.dispatch(`${booking_const.Model.Filter}/setFilteredMarks`, []);
	      await booking_provider_service_calendarService.calendarService.loadFilterMarks(fields, this.isDeletingResourceFilterMode);
	    },
	    async updateCounterMarks() {
	      await booking_provider_service_calendarService.calendarService.loadCounterMarks(this.viewDateTs);
	    },
	    async updateFilterBookingsCount() {
	      const fields = this.$refs.filter.getFields();
	      await booking_provider_service_calendarService.calendarService.loadBookingsDateCount(fields, this.isDeletingResourceFilterMode);
	    },
	    showLoader() {
	      this.loadingFilter = true;
	      void this.loader.show(this.$refs.baseComponent.$el);
	    },
	    hideLoader() {
	      this.loadingFilter = false;
	      void this.loader.hide();
	    }
	  },
	  template: `
		<div>
			<MultiBooking v-if="hasSelectedCells"/>
			<AfterTitle ref="afterTitle"/>
			<CrmFormsButton :container="settingsButtonContainer"/>
			<BookingFilter
				:filterId="filterId"
				ref="filter"
				@apply="isDeletingResourceFilterMode ? applyDeletingResourceFilter(deletingResource) : applyFilter({ fromFilter: true })"
				@clear="clearFilter"
			/>
			<EmptyFilterResultsPopup
				v-if="isFilterMode && !isDeletingResourceFilterMode && !loadingFilter && !fetchingNextDate && datesCount.count === 0"
			/>
			<CountersPanel
				:target="counterPanelContainer"
				ref="countersPanel"
				@activeItem="onActiveItem"
			/>
			<BaseComponent ref="baseComponent"/>
			<Banner/>
			<Trial/>
		</div>
	`
	};

	var _mountApplication = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mountApplication");
	class Booking$1 {
	  constructor(params) {
	    Object.defineProperty(this, _mountApplication, {
	      value: _mountApplication2
	    });
	    booking_core.Core.setParams(params);
	    void babelHelpers.classPrivateFieldLooseBase(this, _mountApplication)[_mountApplication]();
	  }
	}
	async function _mountApplication2() {
	  await booking_core.Core.init();
	  const application = ui_vue3.BitrixVue.createApp(App, booking_core.Core.getParams());
	  application.mixin(booking_component_mixin_locMixin.locMixin);
	  application.use(booking_core.Core.getStore());
	  application.mount(booking_core.Core.getParams().container);
	}

	exports.Booking = Booking$1;

}((this.BX.Booking = this.BX.Booking || {}),BX.Booking.Component.Mixin,BX,BX.Booking.Component,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.Booking.Lib,BX.UI,BX.UI,BX.Event,BX.Booking.Lib,BX.UI.Vue3.Components,BX.UI.NotificationManager,BX.Booking.Provider.Service,BX.Booking.Component,BX.Vue3.Directives,BX.Booking.Lib,BX.Booking.Component,BX.Booking.Component,BX.UI.IconSet,BX,BX.Booking.Component,BX.Booking.Lib,BX.Booking.Lib,BX.Booking.Lib,BX.Booking.Lib,BX.Booking,BX.UI.DatePicker,BX.Booking.Component,BX.Booking.Lib,BX.UI.Dialogs,BX.Booking.Component,BX.Booking.Component,BX.Booking.Lib,BX.Booking.Model,BX.Booking.Model,BX.Booking.Provider.Service,BX.Booking.Lib,BX.Booking.Lib,BX.UI,BX.Booking.Lib,BX.Booking.Component,BX,BX.Booking.Lib,BX,BX.Booking.Lib,BX.Booking,BX.Booking.Provider.Service,BX.Booking.Lib,BX.Booking.Lib,BX.UI.IconSet,BX,BX.Booking.Provider.Service,BX.Booking.Lib,BX.Booking.Lib,BX.UI.EntitySelector,BX.Booking.Lib,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.UI,BX.Main,BX.Booking.Lib,BX.Booking.Component,BX.Booking.Lib,BX.UI.AutoLaunch,BX.Vue3.Vuex,BX,BX.UI,BX.Booking.Lib,BX.Booking.Lib,BX.Main,BX.Booking.Component,BX.Booking.Component,BX.Vue3,BX.Booking.Const,BX.Booking.Component));
//# sourceMappingURL=booking.bundle.js.map
