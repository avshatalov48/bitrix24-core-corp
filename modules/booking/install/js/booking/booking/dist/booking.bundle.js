/* eslint-disable */
this.BX = this.BX || {};
(function (exports,booking_component_mixin_locMixin,main_loader,booking_provider_service_mainPageService,booking_provider_service_dictionaryService,booking_provider_service_calendarService,main_core_events,ui_counterpanel,ui_cnt,ui_vue3_components_richLoc,booking_lib_drag,booking_lib_mousePosition,booking_component_timeSelector,booking_component_popupMaker,main_sidepanel,ui_vue3_directives_lazyload,ui_notificationManager,booking_provider_service_bookingActionsService,booking_component_loader,ui_vue3_directives_hint,ui_iconSet_crm,booking_lib_dealHelper,booking_component_counter,booking_lib_isRealId,booking_component_popup,booking_lib_grid,booking_lib_range,booking_core,ui_datePicker,booking_lib_removeBooking,booking_lib_currencyFormat,booking_component_statisticsPopup,ui_dialogs_messagebox,ui_hint,booking_provider_service_resourcesService,ui_iconSet_actions,booking_resourceCreationWizard,booking_provider_service_resourceDialogService,booking_lib_resources,booking_lib_resourcesDateCache,main_popup,ui_iconSet_api_vue,ui_iconSet_main,booking_provider_service_optionService,booking_lib_helpDesk,booking_lib_busySlots,ui_entitySelector,booking_lib_limit,booking_provider_service_bookingService,ui_ears,main_date,booking_lib_duration,booking_component_clientPopup,booking_component_button,ui_autoLaunch,ui_vue3_vuex,main_core,ui_vue3,ui_bannerDispatcher,booking_lib_resolvable,booking_const,booking_lib_ahaMoments) {
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
	  NotConfirmed: 'booking-filter-preset-unconfirmed',
	  Delayed: 'booking-filter-preset-delayed',
	  CreatedByMe: 'booking-filter-preset-created-by-me'
	});
	const FilterField = Object.freeze({
	  CreatedBy: 'CREATED_BY',
	  Contact: 'CONTACT',
	  Company: 'COMPANY',
	  Resource: 'RESOURCE',
	  Confirmed: 'CONFIRMED',
	  Delayed: 'DELAYED'
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
	    setPresetId(presetId) {
	      if (this.getPresetId() === presetId) {
	        return;
	      }
	      this.filter.getApi().setFilter({
	        preset_id: presetId
	      });
	    },
	    getPresetId() {
	      const preset = this.filter.getPreset().getCurrentPresetId();
	      return preset === 'tmp_filter' ? null : preset;
	    },
	    isFilterEmpty() {
	      return Object.keys(this.getFields()).length === 0;
	    },
	    getFields() {
	      const booleanFields = [FilterField.Confirmed, FilterField.Delayed];
	      const arrayFields = [FilterField.Company, FilterField.Contact, FilterField.CreatedBy, FilterField.Resource];
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
	      return fields;
	    }
	  },
	  template: `
		<div></div>
	`
	};

	const CounterItem = Object.freeze({
	  NotConfirmed: 'not-confirmed',
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
	          id: CounterItem.NotConfirmed,
	          title: this.loc('BOOKING_BOOKING_COUNTER_PANEL_NOT_CONFIRMED'),
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
	        if (item.id === CounterItem.NotConfirmed) {
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
		<div></div>
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
	  components: {
	    StickyPopup: booking_component_popup.StickyPopup,
	    RichLoc: ui_vue3_components_richLoc.RichLoc
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
					<RichLoc :text="loc('BOOKING_QUICK_FILTER_HELP')" placeholder="[bold]">
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
	      return this.hour in this.$store.getters[`${booking_const.Model.Interface}/quickFilter`].active;
	    },
	    hovered() {
	      return this.hour in this.$store.getters[`${booking_const.Model.Interface}/quickFilter`].hovered;
	    }
	  },
	  methods: {
	    onClick() {
	      this.closeHelpPopup();
	      if (this.active) {
	        void this.$store.dispatch(`${booking_const.Model.Interface}/deactivateQuickFilter`, this.hour);
	      } else {
	        void this.$store.dispatch(`${booking_const.Model.Interface}/activateQuickFilter`, this.hour);
	      }
	    },
	    hover() {
	      this.helpPopupTimeout = setTimeout(() => this.showHelpPopup(), 1000);
	      void this.$store.dispatch(`${booking_const.Model.Interface}/hoverQuickFilter`, this.hour);
	    },
	    flee() {
	      this.closeHelpPopup();
	      void this.$store.dispatch(`${booking_const.Model.Interface}/fleeQuickFilter`, this.hour);
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

	const AddClient = {
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
	  data() {
	    return {
	      showPopup: false
	    };
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
	  computed: ui_vue3_vuex.mapGetters({
	    providerModuleId: `${booking_const.Model.Clients}/providerModuleId`,
	    isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	  }),
	  methods: {
	    clickHandler() {
	      if (!this.isFeatureEnabled) {
	        booking_lib_limit.limit.show();
	        return;
	      }
	      this.showPopup = true;
	    },
	    async addClientsToBook(clients) {
	      const booking = this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	      await booking_provider_service_bookingService.bookingService.update({
	        id: booking.id,
	        clients
	      });
	    },
	    async showAhaMoment() {
	      await booking_lib_ahaMoments.ahaMoments.show({
	        id: 'booking-add-client',
	        title: this.loc('BOOKING_AHA_ADD_CLIENT_TITLE'),
	        text: this.loc('BOOKING_AHA_ADD_CLIENT_TEXT'),
	        article: booking_const.HelpDesk.AhaAddClient,
	        target: this.$refs.button
	      });
	      booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.AddClient);
	    }
	  },
	  components: {
	    ClientPopup: booking_component_clientPopup.ClientPopup
	  },
	  template: `
		<div
			v-if="providerModuleId"
			class="booking-booking-booking-add-client"
			:class="{ '--expired': expired }"
			data-element="booking-add-client-button"
			:data-id="bookingId"
			:data-resource-id="resourceId"
			ref="button"
			@click="clickHandler"
		>
			{{ loc('BOOKING_BOOKING_PLUS_CLIENT') }}
		</div>
		<ClientPopup
			v-if="showPopup"
			:bindElement="this.$refs.button"
			:offset-top="-100"
			:offset-left="this.$refs.button.offsetWidth + 10"
			@create="addClientsToBook"
			@close="showPopup = false"
		/>
	`
	};

	const ChangeTimePopup = {
	  emits: ['close'],
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    targetNode: HTMLElement
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
	      return `booking-change-time-popup-${this.bookingId}`;
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
	      return this.bookings.filter(({
	        id
	      }) => id !== this.bookingId).some(({
	        dateToTs,
	        dateFromTs
	      }) => dateToTs > this.fromTs && this.toTs > dateFromTs);
	    },
	    bookings() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getByDateAndResources`](this.selectedDateTs, this.booking.resourcesIds);
	    },
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
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
			:targetNode="$refs.time"
			@close="closePopup"
		/>
	`
	};

	const NotePopup = {
	  emits: ['close'],
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    bindElement: {
	      type: Function,
	      required: true
	    },
	    isEditMode: {
	      type: Boolean,
	      required: true
	    }
	  },
	  data() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      note: '',
	      mountedPromise: new booking_lib_resolvable.Resolvable()
	    };
	  },
	  created() {
	    this.note = this.bookingNote;
	  },
	  mounted() {
	    this.mountedPromise.resolve();
	    this.adjustPosition();
	    this.focusOnTextarea();
	    main_core.Event.bind(document, 'scroll', this.adjustPosition, true);
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(document, 'scroll', this.adjustPosition, true);
	  },
	  computed: {
	    bookingNote() {
	      var _this$booking$note;
	      return (_this$booking$note = this.booking.note) != null ? _this$booking$note : '';
	    },
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
	    },
	    popupId() {
	      return `booking-booking-note-popup-${this.bookingId}`;
	    },
	    config() {
	      return {
	        className: 'booking-booking-note-popup',
	        bindElement: this.bindElement(),
	        minWidth: this.bindElement().offsetWidth,
	        height: 120,
	        offsetTop: -10,
	        background: 'var(--ui-color-background-note)',
	        bindOptions: {
	          forceBindPosition: true,
	          position: 'top'
	        },
	        autoHide: this.isEditMode
	      };
	    }
	  },
	  methods: {
	    saveNote() {
	      const note = this.note.trim();
	      if (this.bookingNote !== note) {
	        void booking_provider_service_bookingService.bookingService.update({
	          id: this.booking.id,
	          note
	        });
	      }
	      this.closePopup();
	    },
	    onMouseDown() {
	      main_core.Event.unbind(window, 'mouseup', this.onMouseUp);
	      main_core.Event.bind(window, 'mouseup', this.onMouseUp);
	      this.setAutoHide(false);
	    },
	    onMouseUp() {
	      main_core.Event.unbind(window, 'mouseup', this.onMouseUp);
	      setTimeout(() => this.setAutoHide(this.isEditMode), 0);
	    },
	    setAutoHide(autoHide) {
	      var _this$$refs$popup, _this$$refs$popup$get;
	      (_this$$refs$popup = this.$refs.popup) == null ? void 0 : (_this$$refs$popup$get = _this$$refs$popup.getPopupInstance()) == null ? void 0 : _this$$refs$popup$get.setAutoHide(autoHide);
	    },
	    adjustPosition() {
	      this.$refs.popup.adjustPosition();
	    },
	    closePopup() {
	      this.$emit('close');
	    },
	    focusOnTextarea() {
	      setTimeout(() => {
	        if (this.isEditMode) {
	          this.$refs.textarea.focus();
	        }
	      }, 0);
	    }
	  },
	  watch: {
	    isEditMode(isEditMode) {
	      this.setAutoHide(isEditMode);
	      this.focusOnTextarea();
	    },
	    async note() {
	      await this.mountedPromise;
	      this.$refs.popup.getPopupInstance().setHeight(0);
	      const minHeight = 120;
	      const maxHeight = 280;
	      const height = this.$refs.textarea.scrollHeight + 45;
	      const popupHeight = Math.min(maxHeight, Math.max(minHeight, height));
	      this.$refs.popup.getPopupInstance().setHeight(popupHeight);
	      this.adjustPosition();
	    }
	  },
	  components: {
	    Popup: booking_component_popup.Popup,
	    Button: booking_component_button.Button
	  },
	  template: `
		<Popup
			:id="popupId"
			:config="config"
			ref="popup"
			@close="closePopup"
		>
			<div
				class="booking-booking-note-popup-content"
				data-element="booking-note-popup"
				:data-id="bookingId"
				@mousedown="onMouseDown"
			>
				<div
					class="booking-booking-note-popup-title"
					data-element="booking-note-popup-title"
					:data-id="bookingId"
				>
					{{ loc('BOOKING_BOOKING_NOTE_TITLE') }}
				</div>
				<textarea
					v-model="note"
					class="booking-booking-note-popup-textarea"
					:placeholder="loc('BOOKING_BOOKING_NOTE_HINT')"
					:disabled="!isEditMode"
					data-element="booking-note-popup-textarea"
					:data-id="bookingId"
					:data-disabled="!isEditMode"
					ref="textarea"
				></textarea>
				<div v-if="isEditMode" class="booking-booking-note-popup-buttons">
					<Button
						:dataset="{id: bookingId, element: 'booking-note-popup-save'}"
						:text="loc('BOOKING_BOOKING_NOTE_SAVE')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.PRIMARY"
						@click="saveNote"
					/>
					<Button
						:dataset="{id: bookingId, element: 'booking-note-popup-cancel'}"
						:text="loc('BOOKING_BOOKING_NOTE_CANCEL')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LINK"
						@click="closePopup"
					/>
				</div>
			</div>
		</Popup>
	`
	};

	const Note = {
	  emits: ['popupShown', 'popupClosed'],
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      isPopupShown: false,
	      isEditMode: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
	    },
	    hasNote() {
	      return Boolean(this.booking.note);
	    }
	  },
	  methods: {
	    onMouseEnter() {
	      this.showNoteTimeout = setTimeout(() => this.showViewPopup(), 100);
	    },
	    onMouseLeave() {
	      clearTimeout(this.showNoteTimeout);
	      this.closeViewPopup();
	    },
	    showViewPopup() {
	      if (this.isPopupShown || !this.hasNote) {
	        return;
	      }
	      this.isEditMode = false;
	      this.showPopup();
	    },
	    closeViewPopup() {
	      if (this.isEditMode) {
	        return;
	      }
	      this.closePopup();
	    },
	    showEditPopup() {
	      this.isEditMode = true;
	      this.showPopup();
	    },
	    closeEditPopup() {
	      if (!this.isEditMode) {
	        return;
	      }
	      this.closePopup();
	    },
	    showPopup() {
	      this.isPopupShown = true;
	      this.$emit('popupShown');
	    },
	    closePopup() {
	      this.isPopupShown = false;
	      this.$emit('popupClosed');
	    }
	  },
	  components: {
	    NotePopup,
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<div
			class="booking-actions-popup__item-client-note"
			data-element="booking-menu-note"
			:data-booking-id="bookingId"
			:data-has-note="hasNote"
			:class="{'--empty': !hasNote}"
			ref="note"
		>
			<div
				class="booking-actions-popup__item-client-note-inner"
				data-element="booking-menu-note-add"
				:data-booking-id="bookingId"
				@mouseenter="onMouseEnter"
				@mouseleave="onMouseLeave"
				@click="() => hasNote ? showViewPopup() : showEditPopup()"
			>
				<template v-if="hasNote">
					<div
						class="booking-actions-popup__item-client-note-text"
						data-element="booking-menu-note-text"
						:data-booking-id="bookingId"
					>
						{{ booking.note }}
					</div>
					<div
						v-if="isFeatureEnabled"
						class="booking-actions-popup__item-client-note-edit"
						data-element="booking-menu-note-edit"
						:data-booking-id="bookingId"
						@click="showEditPopup"
					>
						<Icon :name="IconSet.PENCIL_40"/>
					</div>
				</template>
				<template v-else>
					<Icon :name="IconSet.PLUS_20"/>
					<div class="booking-actions-popup__item-client-note-text">
						{{ loc('BB_ACTIONS_POPUP_ADD_NOTE') }}
					</div>
				</template>
			</div>
		</div>
		<NotePopup
			v-if="isPopupShown"
			:isEditMode="isEditMode && isFeatureEnabled"
			:bookingId="bookingId"
			:bindElement="() => $refs.note"
			@close="closeEditPopup"
		/>
	`
	};

	const Empty = {
	  emits: ['popupShown', 'popupClosed'],
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  components: {
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon,
	    ClientPopup: booking_component_clientPopup.ClientPopup
	  },
	  data() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      isLoading: true,
	      shownClientPopup: false
	    };
	  },
	  methods: {
	    showClientPopup() {
	      if (!this.isFeatureEnabled) {
	        booking_lib_limit.limit.show();
	        return;
	      }
	      this.shownClientPopup = true;
	      this.$emit('popupShown');
	    },
	    hideClientPopup() {
	      this.shownClientPopup = false;
	      this.$emit('popupClosed');
	    },
	    async addClientsToBook(clients) {
	      const booking = this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	      await booking_provider_service_bookingService.bookingService.update({
	        id: booking.id,
	        clients
	      });
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    btnIcon() {
	      return this.isFeatureEnabled ? booking_component_button.ButtonIcon.ADD : booking_component_button.ButtonIcon.LOCK;
	    },
	    userIcon() {
	      return ui_iconSet_api_vue.Set.PERSON;
	    },
	    personSize() {
	      return 26;
	    },
	    callIcon() {
	      return ui_iconSet_api_vue.Set.TELEPHONY_HANDSET_1;
	    },
	    messageIcon() {
	      return ui_iconSet_api_vue.Set.CHATS_1;
	    },
	    iconSize() {
	      return 20;
	    },
	    iconColor() {
	      return 'var(--ui-color-palette-gray-20)';
	    },
	    soonHint() {
	      return {
	        text: this.loc('BOOKING_BOOKING_SOON_HINT'),
	        popupOptions: {
	          offsetLeft: -60
	        }
	      };
	    }
	  },
	  template: `
		<div class="booking-actions-popup__item-client-icon-container">
			<div class="booking-actions-popup__item-client-icon">
				<Icon :name="userIcon" :size="personSize" :color="iconColor"/>
			</div>
		</div>
		<div class="booking-actions-popup__item-client-info --empty">
			<div class="booking-actions-popup__item-client-info-label --empty">
				{{loc('BB_ACTIONS_POPUP_CLIENT_EMPTY_NAME_LABEL')}}
			</div>
			<div class="booking-actions-popup__item-client-info-empty">
				<div></div>
				<div></div>
			</div>
			<div
				class="booking-actions-popup-item-buttons booking-actions-popup__item-client-info-btn"
				ref="clientButton"
			>
				<Button
					:text="loc('BB_ACTIONS_POPUP_CLIENT_BTN_EMPTY_LABEL')"
					:size="ButtonSize.EXTRA_SMALL"
					:color="ButtonColor.PRIMARY"
					:icon="btnIcon"
					:round="true"
					@click="showClientPopup"
				/>
			</div>
			<ClientPopup
				v-if="shownClientPopup"
				:bindElement="this.$refs.clientButton"
				@create="addClientsToBook"
				@close="hideClientPopup"
			/>
		</div>
		<div v-hint="soonHint" class="booking-actions-popup__item-client-action">
			<Icon :name="callIcon" :size="iconSize" :color="iconColor"/>
			<Icon :name="messageIcon" :size="iconSize" :color="iconColor"/>
		</div>
	`
	};

	const EditClientButton = {
	  name: 'EditClientButton',
	  emits: ['visible', 'invisible'],
	  props: {
	    bookingId: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      isClientPopupShowed: false
	    };
	  },
	  computed: {
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    },
	    currentClient() {
	      const getByClientData = this.$store.getters[`${booking_const.Model.Clients}/getByClientData`];
	      const client = {
	        contact: null,
	        company: null
	      };
	      (this.booking.clients || []).map(clientData => getByClientData(clientData)).forEach(clientModel => {
	        if (clientModel.type.code === booking_const.CrmEntity.Contact) {
	          client.contact = clientModel;
	        } else if (clientModel.type.code === booking_const.CrmEntity.Company) {
	          client.company = clientModel;
	        }
	      });
	      return client;
	    }
	  },
	  methods: {
	    async updateClient(clients) {
	      await booking_provider_service_bookingService.bookingService.update({
	        id: this.booking.id,
	        clients
	      });
	    },
	    showPopup() {
	      this.isClientPopupShowed = true;
	      this.$emit('visible');
	    },
	    closePopup() {
	      this.isClientPopupShowed = false;
	      this.$emit('invisible');
	    }
	  },
	  components: {
	    ClientPopup: booking_component_clientPopup.ClientPopup,
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<Button
			data-element="booking-menu-client-edit"
			:data-booking-id="bookingId"
			:size="ButtonSize.EXTRA_SMALL"
			:color="ButtonColor.LIGHT"
			:round="true"
			ref="editClientButton"
			@click="showPopup"
		>
			<Icon :name="IconSet.MORE"/>
		</Button>
		<ClientPopup
			v-if="isClientPopupShowed"
			:bind-element="$refs.editClientButton.$el"
			:current-client="currentClient"
			@create="updateClient"
			@close="closePopup"
		/>
	`
	};

	const Client = {
	  emits: ['freeze', 'unfreeze'],
	  name: 'BookingActionsPopupClient',
	  directives: {
	    lazyload: ui_vue3_directives_lazyload.lazyload,
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  components: {
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon,
	    Loader: booking_component_loader.Loader,
	    Empty,
	    Note,
	    EditClientButton
	  },
	  data() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      isLoading: true
	    };
	  },
	  async mounted() {
	    this.isLoading = false;
	  },
	  methods: {
	    openClient() {
	      const entity = this.client.type.code.toLowerCase();
	      main_sidepanel.SidePanel.Instance.open(`/crm/${entity}/details/${this.client.id}/`);
	    }
	  },
	  computed: {
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
	    },
	    client() {
	      const clientData = this.booking.primaryClient;
	      return clientData ? this.$store.getters['clients/getByClientData'](clientData) : null;
	    },
	    clientPhone() {
	      const client = this.client;
	      return client.phones.length > 0 ? client.phones[0] : this.loc('BB_ACTIONS_POPUP_CLIENT_PHONE_LABEL');
	    },
	    clientAvatar() {
	      const client = this.client;
	      return client.image;
	    },
	    clientStatus() {
	      if (!this.client.isReturning) {
	        return this.loc('BB_ACTIONS_POPUP_CLIENT_STATUS_FIRST');
	      }
	      return this.loc('BB_ACTIONS_POPUP_CLIENT_STATUS_RETURNING');
	    },
	    userIcon() {
	      return ui_iconSet_api_vue.Set.PERSON;
	    },
	    personSize() {
	      return 26;
	    },
	    callIcon() {
	      return ui_iconSet_api_vue.Set.TELEPHONY_HANDSET_1;
	    },
	    messageIcon() {
	      return ui_iconSet_api_vue.Set.CHATS_1;
	    },
	    iconSize() {
	      return 20;
	    },
	    iconColor() {
	      return 'var(--ui-color-palette-gray-20)';
	    },
	    imageTypeClass() {
	      return '--user';
	    },
	    soonHint() {
	      return {
	        text: this.loc('BOOKING_BOOKING_SOON_HINT'),
	        popupOptions: {
	          offsetLeft: -60
	        }
	      };
	    }
	  },
	  template: `
		<div class="booking-actions-popup__item booking-actions-popup__item-client">
			<div class="booking-actions-popup__item-client-client">
				<Loader v-if="isLoading" class="booking-actions-popup__item-client-loader" />
				<template v-else-if="client">
					<div class="booking-actions-popup__item-client-icon-container">
						<div
							v-if="clientAvatar"
							class="booking-actions-popup-user__avatar"
							:class="imageTypeClass"
						>
							<img
								v-lazyload :data-lazyload-src="clientAvatar"
								class="booking-actions-popup-user__source"
							/>
						</div>
						<div v-else class="booking-actions-popup__item-client-icon">
							<Icon :name="userIcon" :size="personSize" :color="iconColor"/>
						</div>
					</div>
					<div class="booking-actions-popup__item-client-info">
						<div class="booking-actions-popup__item-client-info-label" :title="client.name">
							{{ client.name }}
						</div>
						<div class="booking-actions-popup-item-info">
							<div class="booking-actions-popup-item-subtitle">
								{{ clientStatus }}
							</div>
							<div class="booking-actions-popup-item-subtitle">
								{{ clientPhone }}
							</div>
						</div>
						<div class="booking-actions-popup-item-buttons booking-actions-popup__item-client-info-btn">
							<Button
								data-element="booking-menu-client-open"
								:data-booking-id="bookingId"
								class="booking-actions-popup-item-client-open-button"
								:text="loc('BB_ACTIONS_POPUP_CLIENT_BTN_LABEL')"
								:size="ButtonSize.EXTRA_SMALL"
								:color="ButtonColor.LIGHT_BORDER"
								:round="true"
								@click="openClient"
							/>
							<EditClientButton
								:bookingId="bookingId"
								@visible="$emit('freeze')"
								@invisible="$emit('unfreeze')"
							/>
						</div>
					</div>
					<div v-hint="soonHint" class="booking-actions-popup__item-client-action">
						<Icon :name="callIcon" :size="iconSize" :color="iconColor"/>
						<Icon :name="messageIcon" :size="iconSize" :color="iconColor"/>
					</div>
				</template>
				<template v-else>
					<Empty
						:bookingId="bookingId"
						@popupShown="$emit('freeze')"
						@popupClosed="$emit('unfreeze')"
					/>
				</template>
			</div>
			<Note
				:bookingId="bookingId"
				@popupShown="$emit('freeze')"
				@popupClosed="$emit('unfreeze')"
			/>
		</div>
	`
	};

	const Deal = {
	  name: 'BookingActionsPopupDeal',
	  emits: ['freeze', 'unfreeze'],
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  components: {
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon,
	    Loader: booking_component_loader.Loader
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      isLoading: false,
	      saveDealDebounce: main_core.Runtime.debounce(this.saveDeal, 10, this)
	    };
	  },
	  created() {
	    this.dealHelper = new booking_lib_dealHelper.DealHelper(this.bookingId);
	  },
	  mounted() {
	    this.dialog = new ui_entitySelector.Dialog({
	      context: 'BOOKING',
	      multiple: false,
	      targetNode: this.getDialogButton(),
	      width: 340,
	      height: 340,
	      enableSearch: true,
	      dropdownMode: true,
	      preselectedItems: this.deal ? [[booking_const.EntitySelectorEntity.Deal, this.deal.value]] : [],
	      entities: [{
	        id: booking_const.EntitySelectorEntity.Deal,
	        dynamicLoad: true,
	        dynamicSearch: true
	      }],
	      events: {
	        onShow: this.freeze,
	        onHide: this.unfreeze,
	        'Item:onSelect': this.itemChange,
	        'Item:onDeselect': this.itemChange
	      }
	    });
	    main_core.Event.bind(document, 'scroll', this.adjustPosition, true);
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(document, 'scroll', this.adjustPosition, true);
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    menuId() {
	      return 'booking-actions-popup-deal-menu';
	    },
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    },
	    deal() {
	      var _this$booking$externa, _this$booking$externa2;
	      return (_this$booking$externa = (_this$booking$externa2 = this.booking.externalData) == null ? void 0 : _this$booking$externa2.find(data => data.entityTypeId === booking_const.CrmEntity.Deal)) != null ? _this$booking$externa : null;
	    },
	    dateFormatted() {
	      if (!this.deal.data.createdTimestamp) {
	        return '';
	      }
	      const format = main_date.DateTimeFormat.getFormat('DAY_MONTH_FORMAT');
	      return main_date.DateTimeFormat.format(format, this.deal.data.createdTimestamp);
	    }
	  },
	  methods: {
	    freeze() {
	      this.$emit('freeze');
	    },
	    unfreeze() {
	      var _this$dialog, _this$getMenu;
	      if ((_this$dialog = this.dialog) != null && _this$dialog.isOpen() || (_this$getMenu = this.getMenu()) != null && _this$getMenu.getPopupWindow().isShown()) {
	        return;
	      }
	      this.$emit('unfreeze');
	    },
	    createDeal() {
	      if (!this.isFeatureEnabled) {
	        void booking_lib_limit.limit.show();
	        return;
	      }
	      this.dealHelper.createDeal();
	    },
	    showMenu() {
	      if (!this.isFeatureEnabled) {
	        void booking_lib_limit.limit.show();
	        return;
	      }
	      const bindElement = this.$refs.moreButton.$el;
	      main_popup.MenuManager.destroy(this.menuId);
	      main_popup.MenuManager.show({
	        id: this.menuId,
	        bindElement,
	        items: this.getMenuItems(),
	        offsetLeft: bindElement.offsetWidth / 2,
	        angle: true,
	        events: {
	          onShow: this.freeze,
	          onAfterClose: this.unfreeze,
	          onDestroy: this.unfreeze
	        }
	      });
	    },
	    getMenuItems() {
	      return [{
	        text: this.loc('BB_ACTIONS_POPUP_DEAL_CHANGE'),
	        onclick: () => {
	          this.showDealDialog();
	          this.getMenu().close();
	        }
	      }, {
	        text: this.loc('BB_ACTIONS_POPUP_DEAL_CLEAR'),
	        onclick: () => {
	          var _this$dialog2;
	          (_this$dialog2 = this.dialog) == null ? void 0 : _this$dialog2.deselectAll();
	          this.saveDealDebounce(null);
	          this.getMenu().close();
	        }
	      }];
	    },
	    showDealDialog() {
	      if (!this.isFeatureEnabled) {
	        void booking_lib_limit.limit.show();
	        return;
	      }
	      this.dialog.setTargetNode(this.getDialogButton());
	      this.dialog.show();
	    },
	    adjustPosition() {
	      var _this$getMenu2;
	      this.dialog.setTargetNode(this.getDialogButton());
	      this.dialog.adjustPosition();
	      (_this$getMenu2 = this.getMenu()) == null ? void 0 : _this$getMenu2.getPopupWindow().adjustPosition();
	    },
	    getMenu() {
	      return main_popup.MenuManager.getMenuById(this.menuId);
	    },
	    openDeal() {
	      this.dealHelper.openDeal();
	    },
	    itemChange() {
	      const dealData = this.getDealData();
	      this.saveDealDebounce(dealData);
	      this.dialog.hide();
	    },
	    getDealData() {
	      const item = this.dialog.getSelectedItems()[0];
	      if (!item) {
	        return null;
	      }
	      return this.dealHelper.mapEntityInfoToDeal(item.getCustomData().get('entityInfo'));
	    },
	    saveDeal(dealData) {
	      this.dealHelper.saveDeal(dealData);
	    },
	    getDialogButton() {
	      return this.deal ? this.$refs.moreButton.$el : this.$refs.addButton.$el;
	    },
	    showHelpDesk() {
	      booking_lib_helpDesk.helpDesk.show(booking_const.HelpDesk.BookingActionsDeal.code, booking_const.HelpDesk.BookingActionsDeal.anchorCode);
	    }
	  },
	  template: `
		<div
			class="booking-actions-popup__item booking-actions-popup__item-deal-content"
			:class="{ '--active': deal }"
		>
			<Loader v-if="isLoading" class="booking-actions-popup__item-deal-loader" />
			<template v-else>
				<div class="booking-actions-popup__item-deal">
					<div class="booking-actions-popup-item-icon">
						<Icon :name="IconSet.DEAL"/>
					</div>
					<div class="booking-actions-popup-item-info">
						<div class="booking-actions-popup-item-title">
							<span>{{ loc('BB_ACTIONS_POPUP_DEAL_LABEL') }}</span>
							<Icon :name="IconSet.HELP" @click="showHelpDesk" />
						</div>
						<template v-if="deal">
							<div
								class="booking-actions-popup__item-deal-profit"
								data-element="booking-menu-deal-profit"
								:data-profit="deal.data.opportunity"
								:data-booking-id="bookingId"
								v-html="deal.data.formattedOpportunity"
							></div>
							<div
								class="booking-actions-popup-item-subtitle"
								data-element="booking-menu-deal-ts"
								:data-ts="deal.data.createdTimestamp * 1000"
								:data-booking-id="bookingId"
							>
								{{ dateFormatted }}
							</div>
						</template>
						<template v-else>
							<div class="booking-actions-popup-item-subtitle">
								{{ loc('BB_ACTIONS_POPUP_DEAL_ADD_LABEL') }}
							</div>
						</template>
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<template v-if="deal">
						<Button
							data-element="booking-menu-deal-open-button"
							:data-booking-id="bookingId"
							buttonClass="ui-btn-shadow"
							:text="loc('BB_ACTIONS_POPUP_DEAL_OPEN')"
							:size="ButtonSize.EXTRA_SMALL"
							:color="ButtonColor.LIGHT"
							:round="true"
							@click="openDeal"
						/>
						<Button
							data-element="booking-menu-deal-more-button"
							:data-booking-id="bookingId"
							buttonClass="ui-btn-shadow"
							:size="ButtonSize.EXTRA_SMALL"
							:color="ButtonColor.LIGHT"
							:round="true"
							ref="moreButton"
							@click="showMenu"
						>
							<Icon :name="IconSet.MORE"/>
						</Button>
					</template>
					<template v-else>
						<Button
							data-element="booking-menu-deal-create-button"
							:data-booking-id="bookingId"
							class="booking-actions-popup-plus-button"
							:class="{'--lock': !isFeatureEnabled}"
							buttonClass="ui-btn-shadow"
							:size="ButtonSize.EXTRA_SMALL"
							:color="ButtonColor.LIGHT"
							:round="true"
							@click="createDeal"
						>
							<Icon v-if="isFeatureEnabled" :name="IconSet.PLUS_30"/>
							<Icon v-else :name="IconSet.LOCK"/>
						</Button>
						<Button
							class="booking-menu-deal-add-button"
							:class="{'--lock': !isFeatureEnabled}"
							data-element="booking-menu-deal-add-button"
							:data-booking-id="bookingId"
							buttonClass="ui-btn-shadow"
							:text="loc('BB_ACTIONS_POPUP_DEAL_BTN_LABEL')"
							:size="ButtonSize.EXTRA_SMALL"
							:color="ButtonColor.LIGHT"
							:round="true"
							ref="addButton"
							@click="showDealDialog"
						>
							<Icon v-if="!isFeatureEnabled" :name="IconSet.LOCK"/>
						</Button>
					</template>
				</div>
			</template>
		</div>
	`
	};

	const Document = {
	  name: 'BookingActionsPopupDocument',
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  components: {
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon,
	    Loader: booking_component_loader.Loader
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      isLoading: true
	    };
	  },
	  async mounted() {
	    await booking_provider_service_bookingActionsService.bookingActionsService.getDocData();
	    this.isLoading = false;
	  },
	  methods: {
	    linkDoc() {}
	  },
	  template: `
		<div class="booking-actions-popup__item booking-actions-popup__item-doc-content --disabled">
			<Loader v-if="isLoading" class="booking-actions-popup__item-doc-loader" />
			<template v-else>
				<div class="booking-actions-popup__item-doc">
					<div class="booking-actions-popup-item-icon">
						<Icon :name="IconSet.DOCUMENT"/>
					</div>
					<div class="booking-actions-popup-item-info">
						<div class="booking-actions-popup-item-title">
							<span>{{ loc('BB_ACTIONS_POPUP_DOC_LABEL') }}</span>
							<Icon :name="IconSet.HELP"/>
						</div>
						<div class="booking-actions-popup-item-subtitle">
							{{ loc('BB_ACTIONS_POPUP_DOC_ADD_LABEL') }}
						</div>
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<Button
						class="booking-actions-popup-plus-button"
						buttonClass="ui-btn-shadow"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LIGHT"
						:round="true"
						:disabled="true"
					>
						<Icon :name="IconSet.PLUS_30"/>
					</Button>
					<Button
						buttonClass="ui-btn-shadow"
						:text="loc('BB_ACTIONS_POPUP_DOC_BTN_LABEL')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LIGHT"
						:round="true"
						@click="linkDoc"
					/>
				</div>
			</template>
			<div class="booking-booking-actions-popup-label">
				{{ loc('BB_ACTIONS_POPUP_LABEL_SOON') }}
			</div>
		</div>
	`
	};

	const Message = {
	  emits: ['freeze', 'unfreeze'],
	  name: 'BookingActionsPopupMessage',
	  props: {
	    bookingId: {
	      type: Number,
	      required: true
	    }
	  },
	  components: {
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon,
	    Loader: booking_component_loader.Loader
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      isLoading: true,
	      isPrimaryClientIdUpdated: false
	    };
	  },
	  mounted() {
	    void this.fetchMessageData();
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
	    openMenu() {
	      var _this$getMenu, _this$getMenu$getPopu;
	      if (!this.isFeatureEnabled) {
	        booking_lib_limit.limit.show();
	        return;
	      }
	      if (this.status.isDisabled && this.isCurrentSenderAvailable) {
	        return;
	      }
	      if ((_this$getMenu = this.getMenu()) != null && (_this$getMenu$getPopu = _this$getMenu.getPopupWindow()) != null && _this$getMenu$getPopu.isShown()) {
	        this.destroyMenu();
	        return;
	      }
	      const menuButton = this.$refs.button.$el;
	      main_popup.MenuManager.create(this.menuId, menuButton, this.getMenuItems(), {
	        autoHide: true,
	        offsetTop: 0,
	        offsetLeft: menuButton.offsetWidth - menuButton.offsetWidth / 2,
	        angle: true,
	        events: {
	          onClose: this.destroyMenu,
	          onDestroy: this.destroyMenu
	        }
	      }).show();
	      this.$emit('freeze');
	      main_core.Event.bind(document, 'scroll', this.adjustPosition, {
	        capture: true
	      });
	    },
	    getMenuItems() {
	      return Object.values(this.dictionary).map(({
	        name,
	        value
	      }) => ({
	        text: name,
	        onclick: () => this.sendMessage(value),
	        disabled: value === this.dictionary.Feedback.value
	      }));
	    },
	    async sendMessage(notificationType) {
	      this.destroyMenu();
	      const result = await booking_provider_service_bookingActionsService.bookingActionsService.sendMessage(this.bookingId, notificationType);
	      if (!result.isSuccess) {
	        ui_notificationManager.Notifier.notify({
	          id: 'booking-message-send-error',
	          text: result.errorText
	        });
	      }
	      void this.fetchMessageData();
	    },
	    destroyMenu() {
	      main_popup.MenuManager.destroy(this.menuId);
	      this.$emit('unfreeze');
	      main_core.Event.unbind(document, 'scroll', this.adjustPosition, {
	        capture: true
	      });
	    },
	    adjustPosition() {
	      var _this$getMenu2, _this$getMenu2$getPop;
	      (_this$getMenu2 = this.getMenu()) == null ? void 0 : (_this$getMenu2$getPop = _this$getMenu2.getPopupWindow()) == null ? void 0 : _this$getMenu2$getPop.adjustPosition();
	    },
	    getMenu() {
	      return main_popup.MenuManager.getMenuById(this.menuId);
	    },
	    async fetchMessageData() {
	      this.isLoading = true;
	      await booking_provider_service_bookingActionsService.bookingActionsService.getMessageData(this.bookingId);
	      this.isLoading = false;
	    },
	    showHelpDesk() {
	      booking_lib_helpDesk.helpDesk.show(booking_const.HelpDesk.BookingActionsMessage.code, booking_const.HelpDesk.BookingActionsMessage.anchorCode);
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      dictionary: `${booking_const.Model.Dictionary}/getNotifications`,
	      isCurrentSenderAvailable: `${booking_const.Model.Interface}/isCurrentSenderAvailable`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    menuId() {
	      return `booking-message-menu-${this.bookingId}`;
	    },
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
	    },
	    status() {
	      return this.$store.getters[`${booking_const.Model.MessageStatus}/getById`](this.bookingId);
	    },
	    iconColor() {
	      const colorMap = {
	        success: '#ffffff',
	        primary: '#ffffff',
	        failure: '#ffffff'
	      };
	      return colorMap[this.status.semantic] || '';
	    },
	    failure() {
	      return this.status.semantic === 'failure';
	    }
	  },
	  template: `
		<div
			class="booking-actions-popup__item booking-actions-popup__item-message-content"
			:class="{'--disabled': !isCurrentSenderAvailable}"
		>
			<Loader v-if="isLoading" class="booking-actions-popup__item-message-loader" />
			<template v-else>
				<div
					class="booking-actions-popup-item-icon"
					:class="'--' + status.semantic"
				>
					<Icon
						:name="IconSet.SMS"
						:color="iconColor"
					/>
				</div>
				<div class="booking-actions-popup-item-info">
					<div class="booking-actions-popup-item-title">
						<span :title="status.title">{{ status.title }}</span>
						<Icon :name="IconSet.HELP" @click="showHelpDesk"/>
					</div>
					<div
						class="booking-actions-popup-item-subtitle"
						:class="'--' + status.semantic"
					>
						{{ status.description }}
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<Button
						data-element="booking-menu-message-button"
						:data-booking-id="bookingId"
						class="booking-actions-popup-button-with-chevron"
						:class="{
							'--lock': !isFeatureEnabled,
							'--disabled': status.isDisabled && isCurrentSenderAvailable
						}"
						buttonClass="ui-btn-shadow"
						:text="loc('BB_ACTIONS_POPUP_MESSAGE_BUTTON_SEND')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LIGHT"
						:round="true"
						ref="button"
						@click="openMenu"
					>
						<Icon v-if="isFeatureEnabled" :name="IconSet.CHEVRON_DOWN"/>
						<Icon v-else :name="IconSet.LOCK"/>
					</Button>
					<div
						v-if="failure"
						class="booking-actions-popup-item-buttons-counter"
					></div>
				</div>
			</template>
			<div
				v-if="!isCurrentSenderAvailable"
				class="booking-booking-actions-popup-label"
			>
				{{ loc('BB_ACTIONS_POPUP_LABEL_SOON') }}
			</div>
		</div>
	`
	};

	const ConfirmationMenu = {
	  emits: ['popupShown', 'popupClosed'],
	  name: 'ConfirmationMenu',
	  props: {
	    bookingId: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      menuPopup: null
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    popupId() {
	      return `booking-confirmation-menu-${this.bookingId}`;
	    },
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
	    }
	  },
	  unmounted() {
	    if (this.menuPopup) {
	      this.destroy();
	    }
	  },
	  methods: {
	    updateConfirmStatus(isConfirmed) {
	      void booking_provider_service_bookingService.bookingService.update({
	        id: this.booking.id,
	        isConfirmed
	      });
	    },
	    openMenu() {
	      var _this$menuPopup, _this$menuPopup$popup;
	      if (!this.isFeatureEnabled) {
	        booking_lib_limit.limit.show();
	        return;
	      }
	      if ((_this$menuPopup = this.menuPopup) != null && (_this$menuPopup$popup = _this$menuPopup.popupWindow) != null && _this$menuPopup$popup.isShown()) {
	        this.destroy();
	        return;
	      }
	      const menuButton = this.$refs.button.$el;
	      this.menuPopup = main_popup.MenuManager.create(this.popupId, menuButton, this.getMenuItems(), {
	        className: 'booking-confirmation-menu-popup',
	        closeByEsc: true,
	        autoHide: true,
	        offsetTop: 0,
	        offsetLeft: menuButton.offsetWidth - menuButton.offsetWidth / 2,
	        angle: true,
	        cacheable: true,
	        events: {
	          onClose: () => this.destroy(),
	          onDestroy: () => this.unbindScrollEvent()
	        }
	      });
	      this.menuPopup.show();
	      this.bindScrollEvent();
	      this.$emit('popupShown');
	    },
	    getMenuItems() {
	      const text = this.booking.isConfirmed ? this.loc('BB_ACTIONS_POPUP_CONFIRMATION_MENU_NOT_CONFIRMED') : this.loc('BB_ACTIONS_POPUP_CONFIRMATION_MENU_CONFIRMED');
	      return [{
	        text,
	        onclick: () => {
	          this.updateConfirmStatus(!this.booking.isConfirmed);
	          this.destroy();
	        }
	      }];
	    },
	    destroy() {
	      main_popup.MenuManager.destroy(this.popupId);
	      this.unbindScrollEvent();
	      this.$emit('popupClosed');
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
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon,
	    Button: booking_component_button.Button
	  },
	  template: `
		<Button
			data-element="booking-menu-confirmation-button"
			:data-booking-id="bookingId"
			class="booking-actions-popup-button-with-chevron"
			:class="{'--lock': !isFeatureEnabled}"
			buttonClass="ui-btn-shadow"
			:text="loc('BB_ACTIONS_POPUP_CONFIRMATION_BTN_LABEL')"
			:size="ButtonSize.EXTRA_SMALL"
			:color="ButtonColor.LIGHT"
			:round="true"
			ref="button"
			@click="openMenu"
		>
			<Icon v-if="isFeatureEnabled" :name="IconSet.CHEVRON_DOWN"/>
			<Icon v-else :name="IconSet.LOCK"/>
		</Button>
	`
	};

	const Confirmation = {
	  emits: ['freeze', 'unfreeze'],
	  name: 'BookingActionsPopupConfirmation',
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon,
	    Loader: booking_component_loader.Loader,
	    ConfirmationMenu
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      isLoading: true
	    };
	  },
	  async mounted() {
	    this.isLoading = false;
	  },
	  methods: {
	    showHelpDesk() {
	      booking_lib_helpDesk.helpDesk.show(booking_const.HelpDesk.BookingActionsConfirmation.code, booking_const.HelpDesk.BookingActionsConfirmation.anchorCode);
	    }
	  },
	  computed: {
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
	    },
	    iconColor() {
	      var _this$booking$counter, _this$booking$counter2;
	      const unconfirmedCounter = (_this$booking$counter = this.booking.counters.find(counter => counter.type === 'booking_unconfirmed')) == null ? void 0 : _this$booking$counter.value;
	      const delayedCounter = (_this$booking$counter2 = this.booking.counters.find(counter => counter.type === 'booking_delayed')) == null ? void 0 : _this$booking$counter2.value;
	      if (this.booking.isConfirmed === false && !unconfirmedCounter && !delayedCounter) {
	        return '#BDC1C6';
	      }
	      return '#ffffff';
	    },
	    stateClass() {
	      var _this$booking$counter3, _this$booking$counter4;
	      if (this.booking.isConfirmed) {
	        return '--confirmed';
	      }
	      const unconfirmedCounter = (_this$booking$counter3 = this.booking.counters.find(counter => counter.type === 'booking_unconfirmed')) == null ? void 0 : _this$booking$counter3.value;
	      const delayedCounter = (_this$booking$counter4 = this.booking.counters.find(counter => counter.type === 'booking_delayed')) == null ? void 0 : _this$booking$counter4.value;
	      if (unconfirmedCounter) {
	        return '--not-confirmed';
	      }
	      if (delayedCounter) {
	        return '--delayed';
	      }
	      return '--awaiting';
	    },
	    stateText() {
	      var _this$booking$counter5, _this$booking$counter6;
	      if (this.booking.isConfirmed) {
	        return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_CONFIRMED');
	      }
	      const unconfirmedCounter = (_this$booking$counter5 = this.booking.counters.find(counter => counter.type === 'booking_unconfirmed')) == null ? void 0 : _this$booking$counter5.value;
	      const delayedCounter = (_this$booking$counter6 = this.booking.counters.find(counter => counter.type === 'booking_delayed')) == null ? void 0 : _this$booking$counter6.value;
	      if (unconfirmedCounter) {
	        return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_NOT_CONFIRMED');
	      }
	      if (delayedCounter) {
	        return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_DELAYED');
	      }
	      return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_AWAITING');
	    },
	    hasBtnCounter() {
	      var _this$booking$counter7, _this$booking$counter8;
	      if (this.booking.isConfirmed) {
	        return false;
	      }
	      const unconfirmedCounter = (_this$booking$counter7 = this.booking.counters.find(counter => counter.type === 'booking_unconfirmed')) == null ? void 0 : _this$booking$counter7.value;
	      const delayedCounter = (_this$booking$counter8 = this.booking.counters.find(counter => counter.type === 'booking_delayed')) == null ? void 0 : _this$booking$counter8.value;
	      return Boolean(unconfirmedCounter || delayedCounter);
	    }
	  },
	  template: `
		<div class="booking-actions-popup__item booking-actions-popup__item-confirmation-content">
			<Loader v-if="isLoading" class="booking-actions-popup__item-confirmation-loader" />
			<template v-else>
				<div :class="['booking-actions-popup-item-icon', stateClass]">
					<Icon :name="IconSet.CHECK" :color="iconColor"/>
				</div>
				<div class="booking-actions-popup-item-info">
					<div class="booking-actions-popup-item-title">
						<span>{{ loc('BB_ACTIONS_POPUP_CONFIRMATION_LABEL') }}</span>
						<Icon :name="IconSet.HELP" @click="showHelpDesk" />
					</div>
					<div
						:class="['booking-actions-popup-item-subtitle', stateClass]"
						data-element="booking-menu-confirmation-status"
						:data-booking-id="bookingId"
						:data-confirmed="booking.isConfirmed"
					>
						{{ stateText }}
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<ConfirmationMenu
						:bookingId="bookingId"
						@popupShown="$emit('freeze')"
						@popupClosed="$emit('unfreeze')"
					/>
					<div
						v-if="hasBtnCounter"
						class="booking-actions-popup-item-buttons-counter"
					></div>
				</div>
			</template>
		</div>
	`
	};

	const VisitMenu = {
	  emits: ['popupShown', 'popupClosed'],
	  name: 'VisitMenu',
	  props: {
	    bookingId: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      menuPopup: null
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      dictionary: `${booking_const.Model.Dictionary}/getBookingVisitStatuses`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    popupId() {
	      return `booking-visit-menu-${this.bookingId}`;
	    },
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
	    }
	  },
	  unmounted() {
	    if (this.menuPopup) {
	      this.destroy();
	    }
	  },
	  methods: {
	    updateVisitStatus(status) {
	      void booking_provider_service_bookingService.bookingService.update({
	        id: this.booking.id,
	        visitStatus: status
	      });
	    },
	    openMenu() {
	      var _this$menuPopup, _this$menuPopup$popup;
	      if (!this.isFeatureEnabled) {
	        booking_lib_limit.limit.show();
	        return;
	      }
	      if ((_this$menuPopup = this.menuPopup) != null && (_this$menuPopup$popup = _this$menuPopup.popupWindow) != null && _this$menuPopup$popup.isShown()) {
	        this.destroy();
	        return;
	      }
	      const menuButton = this.$refs.button.$el;
	      this.menuPopup = main_popup.MenuManager.create(this.popupId, menuButton, this.getMenuItems(), {
	        autoHide: true,
	        offsetTop: 0,
	        offsetLeft: menuButton.offsetWidth - menuButton.offsetWidth / 2,
	        angle: true,
	        events: {
	          onClose: () => this.destroy(),
	          onDestroy: () => this.unbindScrollEvent()
	        }
	      });
	      this.menuPopup.show();
	      this.bindScrollEvent();
	      this.$emit('popupShown');
	    },
	    getMenuItems() {
	      return [{
	        text: this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_UNKNOWN'),
	        onclick: () => this.setVisitStatus(this.dictionary.Unknown)
	      }, {
	        text: this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_VISITED'),
	        onclick: () => this.setVisitStatus(this.dictionary.Visited)
	      }, {
	        text: this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_NOT_VISITED'),
	        onclick: () => this.setVisitStatus(this.dictionary.NotVisited)
	      }];
	    },
	    setVisitStatus(status) {
	      this.updateVisitStatus(status);
	      this.destroy();
	    },
	    destroy() {
	      main_popup.MenuManager.destroy(this.popupId);
	      this.unbindScrollEvent();
	      this.$emit('popupClosed');
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
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon,
	    Button: booking_component_button.Button
	  },
	  template: `
		<Button
			data-element="booking-menu-visit-button"
			:data-booking-id="bookingId"
			class="booking-actions-popup-button-with-chevron"
			:class="{'--lock': !isFeatureEnabled}"
			buttonClass="ui-btn-shadow"
			:text="loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL')"
			:size="ButtonSize.EXTRA_SMALL"
			:color="ButtonColor.LIGHT"
			:round="true"
			ref="button"
			@click="openMenu"
		>
			<Icon v-if="isFeatureEnabled" :name="IconSet.CHEVRON_DOWN"/>
			<Icon v-else :name="IconSet.LOCK"/>
		</Button>
	`
	};

	const Visit = {
	  emits: ['freeze', 'unfreeze'],
	  name: 'BookingActionsPopupVisit',
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon,
	    Loader: booking_component_loader.Loader,
	    VisitMenu
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      isLoading: true
	    };
	  },
	  async mounted() {
	    this.isLoading = false;
	  },
	  methods: {
	    showHelpDesk() {
	      booking_lib_helpDesk.helpDesk.show(booking_const.HelpDesk.BookingActionsVisit.code, booking_const.HelpDesk.BookingActionsVisit.anchorCode);
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      dictionary: `${booking_const.Model.Dictionary}/getBookingVisitStatuses`
	    }),
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
	    },
	    getLocVisitStatus() {
	      switch (this.booking.visitStatus) {
	        case this.dictionary.Visited:
	          return this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_VISITED');
	        case this.dictionary.NotVisited:
	          return this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_NOT_VISITED');
	        default:
	          return this.booking.clients.length === 0 ? this.loc('BB_ACTIONS_POPUP_VISIT_ADD_LABEL') : this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_UNKNOWN');
	      }
	    },
	    getVisitInfoStyles() {
	      switch (this.booking.visitStatus) {
	        case this.dictionary.Visited:
	          return '--visited';
	        case this.dictionary.NotVisited:
	          return '--not-visited';
	        default:
	          return '--unknown';
	      }
	    },
	    cardIconColor() {
	      switch (this.booking.visitStatus) {
	        case this.dictionary.NotVisited:
	        case this.dictionary.Visited:
	          return 'var(--ui-color-palette-white-base)';
	        default:
	          return 'var(--ui-color-palette-gray-20)';
	      }
	    },
	    iconClass() {
	      switch (this.booking.visitStatus) {
	        case this.dictionary.Visited:
	          return '--visited';
	        case this.dictionary.NotVisited:
	          return '--not-visited';
	        default:
	          return '';
	      }
	    }
	  },
	  template: `
		<div class="booking-actions-popup__item booking-actions-popup__item-visit-content">
			<Loader v-if="isLoading" class="booking-actions-popup__item-visit-loader" />
			<template v-else>
				<div :class="['booking-actions-popup-item-icon', iconClass]">
					<Icon :name="IconSet.CUSTOMER_CARD" :color="cardIconColor"/>
				</div>
				<div class="booking-actions-popup-item-info">
					<div class="booking-actions-popup-item-title">
						<span>{{ loc('BB_ACTIONS_POPUP_VISIT_LABEL') }}</span>
						<Icon :name="IconSet.HELP" @click="showHelpDesk" />
					</div>
					<div
						:class="['booking-actions-popup-item-subtitle', getVisitInfoStyles]"
						data-element="booking-menu-visit-status"
						:data-booking-id="bookingId"
						:data-visit-status="booking.visitStatus"
					>
						{{ getLocVisitStatus }}
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<VisitMenu
						:bookingId="bookingId"
						@popupShown="$emit('freeze')"
						@popupClosed="$emit('unfreeze')"
					/>
				</div>
			</template>
		</div>
	`
	};

	const FullForm = {
	  name: 'BookingActionsPopupFullForm',
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  computed: {
	    arrowIcon() {
	      return ui_iconSet_api_vue.Set.CHEVRON_RIGHT;
	    },
	    arrowIconSize() {
	      return 12;
	    },
	    arrowIconColor() {
	      return 'var(--ui-color-palette-gray-40)';
	    },
	    soonHint() {
	      return {
	        text: this.loc('BOOKING_BOOKING_SOON_HINT'),
	        popupOptions: {
	          offsetLeft: 60
	        }
	      };
	    }
	  },
	  methods: {
	    click() {}
	  },
	  template: `
		<div
			class="booking-actions-popup__item booking-actions-popup__item-full-form-content --disabled"
			@click="click"
			v-hint="soonHint"
		>
			<div class="booking-actions-popup__item-full-form-label">
				{{loc('BB_ACTIONS_POPUP_FULL_FORM_LABEL')}}
			</div>
			<div class="booking-actions-popup__item-full-form-icon">
				<Icon :name="arrowIcon" :size="arrowIconSize" :color="arrowIconColor"/>
			</div>
		</div>
	`
	};

	const Overbooking = {
	  name: 'BookingActionsPopupOverbooking',
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  methods: {
	    openOverbooking() {},
	    sendToOverbookingList() {}
	  },
	  computed: {
	    plusIcon() {
	      return ui_iconSet_api_vue.Set.PLUS_20;
	    },
	    plusIconSize() {
	      return 20;
	    },
	    plusIconColor() {
	      return 'var(--ui-color-palette-gray-20)';
	    }
	  },
	  template: `
		<div class="booking-actions-popup__item-overbooking-icon">
			<Icon :name="plusIcon" :size="plusIconSize" :color="plusIconColor"/>
			<div class="booking-actions-popup__item-overbooking-label">
				{{loc('BB_ACTIONS_POPUP_OVERBOOKING_LABEL')}}
			</div>
		</div>
	`
	};

	const Waitlist = {
	  name: 'BookingActionsPopupWaitlist',
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  computed: {
	    clockIcon() {
	      return ui_iconSet_api_vue.Set.BLACK_CLOCK;
	    },
	    clockIconSize() {
	      return 20;
	    },
	    clockIconColor() {
	      return 'var(--ui-color-palette-gray-20)';
	    }
	  },
	  template: `
		<div class="booking-actions-popup__item-waitlist-icon --end">
			<Icon :name="clockIcon" :size="clockIconSize" :color="clockIconColor"/>
			<div class="booking-actions-popup__item-waitlist-label">
				{{loc('BB_ACTIONS_POPUP_OVERBOOKING_LIST')}}
			</div>
		</div>
	`
	};

	const RemoveBtn = {
	  name: 'BookingActionsPopupRemoveBtn',
	  emits: ['close'],
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set
	    };
	  },
	  methods: {
	    removeBooking() {
	      this.$emit('close');
	      new booking_lib_removeBooking.RemoveBooking(this.bookingId);
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<div
			class="booking-actions-popup__item-remove-button"
			data-element="booking-menu-remove-button"
			:data-booking-id="bookingId"
			@click="removeBooking"
		>
			<div class="booking-actions-popup__item-overbooking-label">
				{{ loc('BB_ACTIONS_POPUP_OVERBOOKING_REMOVE') }}
			</div>
			<Icon :name="IconSet.TRASH_BIN"/>
		</div>
	`
	};

	const ActionsPopup = {
	  name: 'BookingActionsPopup',
	  emits: ['close'],
	  props: {
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    },
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  data() {
	    return {
	      soonTmp: false
	    };
	  },
	  beforeCreate() {
	    main_popup.PopupManager.getPopups().filter(popup => /booking-booking-actions-popup/.test(popup.getId())).forEach(popup => popup.destroy());
	  },
	  computed: {
	    popupId() {
	      return `booking-booking-actions-popup-${this.bookingId}`;
	    },
	    config() {
	      return {
	        className: 'booking-booking-actions-popup',
	        bindElement: this.bindElement,
	        width: 325,
	        offsetLeft: this.bindElement.offsetWidth,
	        offsetTop: -200,
	        animation: 'fading-slide'
	      };
	    },
	    contentStructure() {
	      return [{
	        id: 'client',
	        props: {
	          bookingId: this.bookingId
	        },
	        component: Client
	      }, [{
	        id: 'deal',
	        props: {
	          bookingId: this.bookingId
	        },
	        component: Deal
	      }, {
	        id: 'document',
	        props: {
	          bookingId: this.bookingId
	        },
	        component: Document
	      }], {
	        id: 'message',
	        props: {
	          bookingId: this.bookingId
	        },
	        component: Message
	      }, {
	        id: 'confirmation',
	        props: {
	          bookingId: this.bookingId
	        },
	        component: Confirmation
	      }, {
	        id: 'visit',
	        props: {
	          bookingId: this.bookingId
	        },
	        component: Visit
	      }, {
	        id: 'fullForm',
	        props: {
	          bookingId: this.bookingId
	        },
	        component: FullForm
	      }];
	    },
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
	    }
	  },
	  components: {
	    StickyPopup: booking_component_popup.StickyPopup,
	    PopupMaker: booking_component_popupMaker.PopupMaker,
	    Client,
	    Deal,
	    Document,
	    Message,
	    Confirmation,
	    Visit,
	    FullForm,
	    Overbooking,
	    Waitlist,
	    RemoveBtn
	  },
	  template: `
		<StickyPopup
			v-slot="{freeze, unfreeze}"
			:id="popupId"
			:config="config"
			@close="$emit('close')"
		>
			<PopupMaker
				:contentStructure="contentStructure"
				@freeze="freeze"
				@unfreeze="unfreeze"
			/>
			<div class="booking-booking-actions-popup-footer">
				<template v-if="soonTmp">
					<Overbooking :bookingId />
					<Waitlist :bookingId />
				</template>
				<RemoveBtn :bookingId @close="$emit('close')" />
			</div>
		</StickyPopup>
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
	    ActionsPopup
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
		<ActionsPopup
			v-if="showPopup"
			:bookingId="bookingId"
			:bindElement="this.$refs.node"
			@close="showPopup = false"
		/>
	`
	};

	const Name = {
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
	      var _this$client$name, _this$client;
	      return (_this$client$name = (_this$client = this.client) == null ? void 0 : _this$client.name) != null ? _this$client$name : this.booking.name;
	    }
	  },
	  template: `
		<div
			class="booking-booking-booking-name"
			:title="bookingName"
			data-element="booking-booking-name"
			:data-id="bookingId"
			:data-resource-id="resourceId"
		>
			{{ bookingName }}
		</div>
	`
	};

	const Note$1 = {
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    },
	    bindElement: {
	      type: Function,
	      required: true
	    }
	  },
	  data() {
	    return {
	      isPopupShown: false,
	      isEditMode: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    booking() {
	      return this.$store.getters['bookings/getById'](this.bookingId);
	    },
	    hasNote() {
	      return Boolean(this.booking.note);
	    }
	  },
	  methods: {
	    showViewPopup() {
	      if (this.isPopupShown || !this.hasNote) {
	        return;
	      }
	      this.isEditMode = false;
	      this.isPopupShown = true;
	    },
	    closeViewPopup() {
	      if (this.isEditMode) {
	        return;
	      }
	      this.isPopupShown = false;
	    },
	    showEditPopup() {
	      this.isEditMode = true;
	      this.isPopupShown = true;
	    },
	    closeEditPopup() {
	      if (!this.isEditMode) {
	        return;
	      }
	      this.isPopupShown = false;
	    }
	  },
	  components: {
	    NotePopup
	  },
	  template: `
		<div class="booking-booking-booking-note">
			<div
				class="booking-booking-booking-note-button"
				:class="{'--has-note': hasNote}"
				data-element="booking-booking-note-button"
				:data-id="bookingId"
				@click="showEditPopup"
			>
				<div class="ui-icon-set --note"></div>
			</div>
		</div>
		<NotePopup
			v-if="isPopupShown"
			:isEditMode="isEditMode && isFeatureEnabled"
			:bookingId="bookingId"
			:bindElement="bindElement"
			@close="closeEditPopup"
		/>
	`
	};

	const Profit$1 = {
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
		<div
			v-if="deal"
			class="booking-booking-booking-profit"
			data-element="booking-booking-profit"
			:data-id="bookingId"
			:data-resource-id="resourceId"
			:data-profit="deal.data.opportunity"
			v-html="deal.data.formattedOpportunity"
		></div>
	`
	};

	const Communication = {
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set
	    };
	  },
	  computed: {
	    soonHint() {
	      return {
	        text: this.loc('BOOKING_BOOKING_SOON_HINT'),
	        popupOptions: {
	          offsetLeft: -60
	        }
	      };
	    }
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<div v-hint="soonHint" class="booking-booking-booking-communication">
			<Icon :name="IconSet.TELEPHONY_HANDSET_1"/>
			<Icon :name="IconSet.CHATS_2"/>
		</div>
	`
	};

	const CrmButton = {
	  props: {
	    bookingId: [Number, String],
	    required: true
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set
	    };
	  },
	  created() {
	    this.dealHelper = new booking_lib_dealHelper.DealHelper(this.bookingId);
	  },
	  computed: {
	    hasDeal() {
	      return this.dealHelper.hasDeal();
	    },
	    isFeatureEnabled() {
	      return this.$store.getters[`${booking_const.Model.Interface}/isFeatureEnabled`];
	    }
	  },
	  methods: {
	    onClick() {
	      if (!this.isFeatureEnabled) {
	        void booking_lib_limit.limit.show();
	        return;
	      }
	      if (this.hasDeal) {
	        this.dealHelper.openDeal();
	      } else {
	        this.dealHelper.createDeal();
	      }
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<Icon
			:name="IconSet.CRM_LETTERS"
			class="booking-booking-booking-crm-button"
			:class="{'--no-deal': !hasDeal}"
			data-element="booking-crm-button"
			:data-booking-id="bookingId"
			@click="onClick"
		/>
	`
	};

	const Counter = {
	  props: {
	    bookingId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  computed: {
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    },
	    counterOptions() {
	      return Object.freeze({
	        color: booking_component_counter.CounterColor.DANGER,
	        size: booking_component_counter.CounterSize.LARGE
	      });
	    }
	  },
	  components: {
	    UiCounter: booking_component_counter.Counter
	  },
	  template: `
		<UiCounter
			v-if="booking.counter > 0"
			:value="booking.counter"
			:color="counterOptions.color"
			:size="counterOptions.size"
			border
			counter-class="booking--counter"
		/>
	`
	};

	const DisabledPopup = {
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
	    bindElement: {
	      type: Function,
	      required: true
	    }
	  },
	  mounted() {
	    this.adjustPosition();
	    setTimeout(() => this.closePopup(), 3000);
	    main_core.Event.bind(document, 'scroll', this.adjustPosition, true);
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(document, 'scroll', this.adjustPosition, true);
	  },
	  computed: {
	    popupId() {
	      return `booking-booking-disabled-popup-${this.bookingId}-${this.resourceId}`;
	    },
	    config() {
	      return {
	        className: 'booking-booking-disabled-popup',
	        bindElement: this.bindElement(),
	        width: this.bindElement().offsetWidth,
	        offsetTop: -10,
	        bindOptions: {
	          forceBindPosition: true,
	          position: 'top'
	        },
	        autoHide: true,
	        darkMode: true
	      };
	    }
	  },
	  methods: {
	    adjustPosition() {
	      this.$refs.popup.adjustPosition();
	    },
	    closePopup() {
	      this.$emit('close');
	    }
	  },
	  components: {
	    Popup: booking_component_popup.Popup
	  },
	  template: `
		<Popup
			:id="popupId"
			:config="config"
			ref="popup"
			@close="closePopup"
		>
			<div class="booking-booking-disabled-popup-content">
				{{ loc('BOOKING_BOOKING_YOU_CANNOT_EDIT_THIS_BOOKING') }}
			</div>
		</Popup>
	`
	};

	const ResizeDirection = Object.freeze({
	  From: -1,
	  None: 0,
	  To: 1
	});
	const minDuration = booking_lib_duration.Duration.getUnitDurations().i * 5;
	const minInitialDuration = booking_lib_duration.Duration.getUnitDurations().i * 15;
	const Resize = {
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
	  data() {
	    return {
	      resizeDirection: ResizeDirection.None,
	      resizeFromTs: null,
	      resizeToTs: null
	    };
	  },
	  computed: {
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
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
	    colliding() {
	      return this.$store.getters[`${booking_const.Model.Interface}/getColliding`](this.resourceId, [this.bookingId]);
	    }
	  },
	  methods: {
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
	        this.resizeFromTs = this.booking.dateFromTs;
	        this.resizeToTs = Math.min(this.booking.dateFromTs + newDuration, this.closestOnTo);
	      } else {
	        this.resizeFromTs = Math.max(this.booking.dateToTs - newDuration, this.closestOnFrom);
	        this.resizeToTs = this.booking.dateToTs;
	      }
	      this.$emit('update', this.resizeFromTs, this.resizeToTs);
	    },
	    async endResize() {
	      this.resizeBooking();
	      main_core.Dom.style(document.body, 'user-select', '');
	      main_core.Event.unbind(window, 'mouseup', this.endResize);
	      main_core.Event.unbind(window, 'pointermove', this.resize);
	      this.$emit('update', null, null);
	      void this.updateIds(null, null);
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
			<div class="booking-booking-resize --from" @mousedown="onMouseDown"></div>
			<div class="booking-booking-resize --to" @mousedown="onMouseDown"></div>
		</div>
	`
	};

	const BookingWidth = 280;
	const Booking = {
	  name: 'Booking',
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
	     * @param {BookingUiDuration[]} uiBookings
	     */
	    uiBookings: {
	      type: Array,
	      default: () => []
	    }
	  },
	  data() {
	    return {
	      visible: true,
	      isDisabledPopupShown: false,
	      resizeFromTs: null,
	      resizeToTs: null
	    };
	  },
	  mounted() {
	    this.updateVisibility();
	    this.updateVisibilityDuringTransition();
	    setTimeout(() => {
	      if (!this.isReal && booking_lib_mousePosition.mousePosition.isMousePressed()) {
	        void this.$refs.resize.startResize();
	      }
	    }, 200);
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
	    overlappingBookings() {
	      const uiBooking = this.uiBookings.find(booking => this.booking.dateFromTs === booking.fromTs);
	      if (!uiBooking) {
	        return [];
	      }
	      const {
	        fromTs,
	        toTs
	      } = uiBooking;
	      return [...this.uiBookings.filter(booking => fromTs > booking.fromTs && fromTs < booking.toTs), uiBooking, ...this.uiBookings.filter(booking => toTs > booking.fromTs && toTs < booking.toTs)];
	    },
	    bookingWidth() {
	      const overlappingBookingsCount = this.overlappingBookings.length;
	      return BookingWidth / (overlappingBookingsCount > 0 ? overlappingBookingsCount : 1);
	    },
	    bookingOffset() {
	      const index = this.overlappingBookings.findIndex(({
	        id
	      }) => id === this.booking.id);
	      return this.bookingWidth * this.zoom * index;
	    },
	    shortView() {
	      return this.overlappingBookings.length > 1;
	    },
	    isExpiredBooking() {
	      return this.booking.dateToTs < this.nowTs;
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
	      if (!this.$el) {
	        return;
	      }
	      const rect = this.$el.getBoundingClientRect();
	      this.visible = rect.right > 0 && rect.left < window.innerWidth;
	    },
	    onNoteMouseEnter() {
	      this.showNoteTimeout = setTimeout(() => this.$refs.note.showViewPopup(), 100);
	    },
	    onNoteMouseLeave() {
	      clearTimeout(this.showNoteTimeout);
	      this.$refs.note.closeViewPopup();
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
	    }
	  },
	  components: {
	    AddClient,
	    BookingTime,
	    Actions,
	    Name,
	    Note: Note$1,
	    Profit: Profit$1,
	    Communication,
	    CrmButton,
	    Counter,
	    DisabledPopup,
	    Resize
	  },
	  template: `
		<div
			class="booking-booking-booking"
			data-element="booking-booking"
			:data-id="bookingId"
			:data-resource-id="resourceId"
			:style="{
				'--left': left + bookingOffset + 'px',
				'--top': top + 'px',
				'--height': height + 'px',
				'--width': bookingWidth + 'px',
			}"
			:class="{
				'--not-real': !isReal,
				'--zoom-is-less-than-08': zoom < 0.8,
				'--compact-mode': realHeight < 40 || zoom < 0.8,
				'--small': realHeight <= 15,
				'--short': shortView,
				'--disabled': disabled,
				'--confirmed': booking.isConfirmed,
				'--expired': isExpiredBooking,
				'--resizing': resizeFromTs && resizeToTs,
			}"
			@click.capture="onClick"
		>
			<div v-if="visible" class="booking-booking-booking-padding">
				<div class="booking-booking-booking-inner">
					<div class="booking-booking-booking-content">
						<div class="booking-booking-booking-content-row">
							<div
								v-show="!shortView"
								class="booking-booking-booking-name-container"
								@mouseenter="onNoteMouseEnter"
								@mouseleave="onNoteMouseLeave"
								@click="$refs.note.showViewPopup()"
							>
								<Name :bookingId="bookingId" :resourceId="resourceId"/>
								<Note
									:bookingId="bookingId"
									:bindElement="() => $el"
									ref="note"
								/>
							</div>
							<BookingTime
								:bookingId="bookingId"
								:resourceId="resourceId"
								:dateFromTs="dateFromTsRounded"
								:dateToTs="dateToTsRounded"
							/>
							<Profit :bookingId="bookingId" :resourceId="resourceId"/>
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
								<CrmButton :bookingId="bookingId"/>
							</div>
							<AddClient
								v-else
								:bookingId="bookingId"
								:resourceId="resourceId"
								:expired="isExpiredBooking"
							/>
						</div>
					</div>
					<Actions :bookingId="bookingId" :resourceId="resourceId"/>
				</div>
			</div>
			<Resize
				v-if="!disabled"
				:bookingId="bookingId"
				:resourceId="resourceId"
				ref="resize"
				@update="resizeUpdate"
			/>
			<Counter :bookingId="bookingId"/>
			<DisabledPopup
				v-if="isDisabledPopupShown"
				:bookingId="bookingId"
				:resourceId="resourceId"
				:bindElement="() => $el"
				@close="isDisabledPopupShown = false"
			/>
		</div>
	`
	};

	const BusyPopup = {
	  emits: ['close'],
	  props: {
	    busySlot: {
	      type: Object,
	      required: true
	    }
	  },
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
	      const messageId = this.busySlot.type === booking_const.BusySlot.Intersection ? 'BOOKING_BOOKING_INTERSECTING_RESOURCE_IS_BUSY' : 'BOOKING_BOOKING_RESOURCE_IS_BUSY';
	      return this.loc(messageId, {
	        '#RESOURCE#': this.resource.name,
	        '#TIME_FROM#': main_date.DateTimeFormat.format(timeFormat, (this.busySlot.fromTs + this.offset) / 1000),
	        '#TIME_TO#': main_date.DateTimeFormat.format(timeFormat, (this.busySlot.toTs + this.offset) / 1000)
	      });
	    }
	  },
	  methods: {
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
	  watch: {
	    mousePosition: {
	      handler() {
	        this.adjustPosition();
	      },
	      deep: true
	    }
	  },
	  components: {
	    Popup: booking_component_popup.Popup
	  },
	  template: `
		<Popup
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
	const BookingBusySlotClassName = 'booking-booking-busy-slot';
	const BusySlot = {
	  name: 'BusySlot',
	  props: {
	    busySlot: {
	      type: Object,
	      required: true
	    }
	  },
	  setup() {
	    return {
	      BookingBusySlotClassName
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
	      isFilterMode: 'isFilterMode',
	      isEditingBookingMode: 'isEditingBookingMode',
	      isDragMode: 'isDragMode'
	    }),
	    isDisabled() {
	      const isDragOffHours = this.isDragMode && this.busySlot.type === booking_const.BusySlot.OffHours;
	      if (this.isFilterMode || isDragOffHours) {
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
	      if (this.isFilterMode || this.isEditingBookingMode || this.busySlot.type === booking_const.BusySlot.Intersection) {
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
	  components: {
	    BusyPopup
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
	      draggedBookingId: `${booking_const.Model.Interface}/draggedBookingId`
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
	      void this.$store.dispatch(`${booking_const.Model.Interface}/addQuickFilterIgnoredBookingId`, this.creatingBookingId);
	      void this.$store.dispatch(`${booking_const.Model.Bookings}/add`, {
	        id: this.creatingBookingId,
	        dateFromTs: this.cell.fromTs,
	        dateToTs: this.cell.toTs,
	        name: this.loc('BOOKING_BOOKING_DEFAULT_BOOKING_NAME'),
	        resourcesIds: [...new Set([this.cell.resourceId, ...((_this$intersections$ = this.intersections[0]) != null ? _this$intersections$ : []), ...((_this$intersections$t = this.intersections[this.cell.resourceId]) != null ? _this$intersections$t : [])])],
	        timezoneFrom: this.timezone,
	        timezoneTo: this.timezone
	      });
	      main_core.Event.bind(window, 'mouseup', this.addBooking);
	    },
	    addBooking() {
	      main_core.Event.unbind(window, 'mouseup', this.addBooking);
	      if (!this.isFeatureEnabled) {
	        void booking_lib_limit.limit.show();
	        return;
	      }
	      setTimeout(() => {
	        const creatingBooking = this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.creatingBookingId);
	        void booking_provider_service_bookingService.bookingService.add(creatingBooking);
	      });
	    }
	  },
	  template: `
		<div
			class="booking-booking-base-cell"
			:class="{
				'--selected': selected,
				'--bounded-to-bottom': cell.boundedToBottom,
				'--height-is-less-than-40': height < 40,
				'--compact-mode': height < 40 || zoom < 0.8,
				'--small': height <= 12.5,
			}"
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
								v-if="!draggedBookingId"
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
						v-if="!hasSelectedCells && !draggedBookingId"
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
	 */
	const Cell = {
	  props: {
	    /** @type {Cell} */
	    cell: {
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    left() {
	      return booking_lib_grid.grid.calculateLeft(this.cell.resourceId);
	    },
	    top() {
	      return booking_lib_grid.grid.calculateTop(this.cell.fromTs);
	    },
	    height() {
	      return booking_lib_grid.grid.calculateHeight(this.cell.fromTs, this.cell.toTs);
	    }
	  },
	  components: {
	    BaseCell
	  },
	  template: `
		<div
			v-if="left >= 0"
			class="booking-booking-selected-cell"
			:style="{
				'--left': left + 'px',
				'--top': top + 'px',
				'--height': height + 'px',
			}"
			@mouseleave="$store.dispatch('interface/setHoveredCell', null)"
		>
			<BaseCell
				:cell="cell"
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

	const {
	  mapGetters: mapInterfaceGetters$1
	} = ui_vue3_vuex.createNamespacedHelpers(booking_const.Model.Interface);
	const MinUiBookingDurationMs = 15 * 60 * 1000;
	const Bookings = {
	  name: 'Bookings',
	  data() {
	    return {
	      nowTs: Date.now()
	    };
	  },
	  computed: {
	    ...mapInterfaceGetters$1({
	      resourcesIds: 'resourcesIds',
	      selectedDateTs: 'selectedDateTs',
	      isFilterMode: 'isFilterMode',
	      filteredBookingsIds: 'filteredBookingsIds',
	      selectedCells: 'selectedCells',
	      hoveredCell: 'hoveredCell',
	      busySlots: 'busySlots',
	      quickFilter: 'quickFilter',
	      isFeatureEnabled: 'isFeatureEnabled',
	      editingBookingId: 'editingBookingId'
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
	        return booking.resourcesIds.filter(resourceId => this.resourcesIds.includes(resourceId)).map(resourceId => ({
	          ...booking,
	          resourcesIds: [resourceId]
	        }));
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
	    }
	  },
	  mounted() {
	    this.startInterval();
	    if (this.isFeatureEnabled) {
	      const dataId = this.editingBookingId ? `[data-id="${this.editingBookingId}"]` : '';
	      this.dragManager = new booking_lib_drag.Drag({
	        container: this.$el.parentElement,
	        draggable: `.booking-booking-booking${dataId}`
	      });
	    }
	  },
	  beforeUnmount() {
	    var _this$dragManager;
	    (_this$dragManager = this.dragManager) == null ? void 0 : _this$dragManager.destroy();
	  },
	  methods: {
	    generateBookingKey(booking) {
	      return `${booking.id}-${booking.resourcesIds[0]}`;
	    },
	    getUiBookings(resourceId) {
	      return this.bookings.filter(booking => {
	        var _booking$resourcesIds;
	        return ((_booking$resourcesIds = booking.resourcesIds) == null ? void 0 : _booking$resourcesIds[0]) === resourceId;
	      }).map(booking => {
	        const duration = booking.dateToTs - booking.dateFromTs;
	        return {
	          id: booking.id,
	          fromTs: booking.dateFromTs,
	          toTs: duration < MinUiBookingDurationMs ? booking.dateFromTs + MinUiBookingDurationMs : booking.dateToTs
	        };
	      });
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
	    }
	  },
	  components: {
	    Booking,
	    BusySlot,
	    Cell,
	    QuickFilterLine
	  },
	  template: `
		<div class="booking-booking-bookings">
			<TransitionGroup name="booking-transition-booking">
				<template v-for="booking of bookings" :key="generateBookingKey(booking)">
					<Booking
						:bookingId="booking.id"
						:resourceId="booking.resourcesIds[0]"
						:nowTs
						:uiBookings="getUiBookings(booking.resourcesIds[0])"
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
	      isFilterMode: `${booking_const.Model.Interface}/isFilterMode`,
	      isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`,
	      draggedBookingId: `${booking_const.Model.Interface}/draggedBookingId`,
	      resizedBookingId: `${booking_const.Model.Interface}/resizedBookingId`,
	      quickFilter: `${booking_const.Model.Interface}/quickFilter`
	    }),
	    isAvailable() {
	      if (this.isFilterMode || this.resizedBookingId || this.isEditingBookingMode && !this.draggedBookingId) {
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
	      return this.$store.getters[`${booking_const.Model.Interface}/getColliding`](this.cell.resourceId, [this.draggedBookingId]);
	    },
	    quickFilterHovered() {
	      return this.cell.minutes / 60 in this.quickFilter.hovered;
	    },
	    quickFilterActive() {
	      return this.cell.minutes / 60 in this.quickFilter.active;
	    }
	  },
	  methods: {
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
	      if (!bottomHalf && !canSubtractHalfHour && this.freeSpace.fromTs - this.cell.fromTs > 0) {
	        this.halfOffset = this.freeSpace.fromTs - this.cell.fromTs;
	      }
	      if (!bottomHalf && canSubtractHalfHour || bottomHalf && !canAddHalfHour) {
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
	    draggedBookingId() {
	      if (!this.draggedBookingId) {
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
	const duration = 200;
	const counterPanelScopeClass = 'ui-counter-panel__scope';
	const darkThemeClass = 'bitrix24-dark-theme';
	var _slider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("slider");
	var _overlay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("overlay");
	var _handleSliderClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSliderClose");
	var _renderOverlay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderOverlay");
	var _appContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appContainer");
	var _appHeader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appHeader");
	var _counterPanel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("counterPanel");
	var _appContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appContent");
	var _appContentPaddingBottomElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appContentPaddingBottomElement");
	var _imBar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("imBar");
	var _imBarWidth = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("imBarWidth");
	var _isExpanded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isExpanded");
	var _getInset = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInset");
	var _animate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animate");
	var _applyMaximizedStyles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applyMaximizedStyles");
	var _applyMinimizedStyles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applyMinimizedStyles");
	class Maximize {
	  constructor({
	    onOverlayClick
	  }) {
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
	    Object.defineProperty(this, _imBarWidth, {
	      get: _get_imBarWidth,
	      set: void 0
	    });
	    Object.defineProperty(this, _imBar, {
	      get: _get_imBar,
	      set: void 0
	    });
	    Object.defineProperty(this, _appContentPaddingBottomElement, {
	      get: _get_appContentPaddingBottomElement,
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
	    Object.defineProperty(this, _renderOverlay, {
	      value: _renderOverlay2
	    });
	    Object.defineProperty(this, _handleSliderClose, {
	      value: _handleSliderClose2
	    });
	    Object.defineProperty(this, _slider, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _overlay, {
	      writable: true,
	      value: void 0
	    });
	    this.onOverlayClick = onOverlayClick;
	    babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider] = new (BX.SidePanel.Manager.getSliderClass())('');
	    babelHelpers.classPrivateFieldLooseBase(this, _overlay)[_overlay] = babelHelpers.classPrivateFieldLooseBase(this, _renderOverlay)[_renderOverlay]();
	    if (top.BX) {
	      this.handleSliderClose = babelHelpers.classPrivateFieldLooseBase(this, _handleSliderClose)[_handleSliderClose].bind(this);
	      top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', this.handleSliderClose);
	      top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onDestroy', this.handleSliderClose);
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
	function _handleSliderClose2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isExpanded)[_isExpanded]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider].applyHacks();
	    BX.SidePanel.Instance.disablePageScrollbar();
	  }
	}
	function _renderOverlay2() {
	  return main_core.Tag.render(_t || (_t = _`
			<div class="booking-booking-overlay" onclick="${0}"></div>
		`), this.onOverlayClick);
	}
	function _get_appContainer() {
	  return BX('content-table');
	}
	function _get_appHeader() {
	  return document.querySelector('.page-header');
	}
	function _get_counterPanel() {
	  return booking_core.Core.getParams().counterPanelContainer.firstElementChild;
	}
	function _get_appContent() {
	  return BX('workarea-content');
	}
	function _get_appContentPaddingBottomElement() {
	  var _BX;
	  return (_BX = BX('workarea')) == null ? void 0 : _BX.parentElement;
	}
	function _get_imBar() {
	  return BX('bx-im-bar');
	}
	function _get_imBarWidth() {
	  return window.innerWidth - babelHelpers.classPrivateFieldLooseBase(this, _imBar)[_imBar].getBoundingClientRect().left;
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
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appHeader)[_appHeader], 'border-bottom', '1px solid var(--ui-color-base-10)');
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appHeader)[_appHeader], 'max-width', '100%');
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appHeader)[_appHeader].parentElement, 'padding-right', `${babelHelpers.classPrivateFieldLooseBase(this, _imBarWidth)[_imBarWidth]}px`);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContent)[_appContent], 'margin', 0);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContent)[_appContent], 'border-radius', 0);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContentPaddingBottomElement)[_appContentPaddingBottomElement], 'padding-bottom', 0);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContentPaddingBottomElement)[_appContentPaddingBottomElement], 'padding-right', `${babelHelpers.classPrivateFieldLooseBase(this, _imBarWidth)[_imBarWidth]}px`);
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
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContentPaddingBottomElement)[_appContentPaddingBottomElement], 'padding-bottom', null);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _appContentPaddingBottomElement)[_appContentPaddingBottomElement], 'padding-right', null);
	  BX.ZIndexManager.unregister(babelHelpers.classPrivateFieldLooseBase(this, _appContainer)[_appContainer]);
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
	        title: this.loc('BOOKING_AHA_EXPAND_GRID_TITLE'),
	        text: this.loc('BOOKING_AHA_EXPAND_GRID_TEXT'),
	        article: booking_const.HelpDesk.AhaExpandGrid,
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
	const Calendar = {
	  data() {
	    return {
	      expanded: true
	    };
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
	  computed: {
	    ...mapInterfaceGetters$3({
	      filteredMarks: 'filteredMarks',
	      freeMarks: 'freeMarks',
	      isFilterMode: 'isFilterMode',
	      getCounterMarks: 'getCounterMarks',
	      offset: 'offset'
	    }),
	    selectedDateTs() {
	      return this.$store.getters[`${booking_const.Model.Interface}/selectedDateTs`] + this.offset;
	    },
	    viewDateTs() {
	      return this.$store.getters[`${booking_const.Model.Interface}/viewDateTs`] + this.offset;
	    },
	    counterMarks() {
	      if (this.isFilterMode) {
	        return this.getCounterMarks(this.filteredMarks);
	      }
	      return this.getCounterMarks();
	    },
	    formattedDate() {
	      const format = this.expanded ? this.loc('BOOKING_MONTH_YEAR_FORMAT') : main_date.DateTimeFormat.getFormat('LONG_DATE_FORMAT');
	      const timestamp = this.expanded ? this.viewDateTs / 1000 : this.selectedDateTs / 1000;
	      return main_date.DateTimeFormat.format(format, timestamp);
	    }
	  },
	  methods: {
	    onCollapseClick() {
	      this.expanded = !this.expanded;
	    },
	    onPreviousClick() {
	      if (this.expanded) {
	        this.previousMonth();
	      } else {
	        this.previousDate();
	      }
	    },
	    onNextClick() {
	      if (this.expanded) {
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
	      if (this.isFilterMode) {
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
	    setFilterMarks() {
	      const bgColorFilter = 'rgba(var(--ui-color-primary-rgb), 0.20)';
	      const dates = this.prepareDates(this.filteredMarks);
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
	  template: `
		<div class="booking-booking-sidebar-calendar">
			<div class="booking-booking-sidebar-calendar-header">
				<div class="booking-booking-sidebar-calendar-button" @click="onPreviousClick">
					<div class="ui-icon-set --chevron-left"></div>
				</div>
				<div class="booking-booking-sidebar-calendar-title">
					{{ formattedDate }}
				</div>
				<div class="booking-booking-sidebar-calendar-button" @click="onNextClick">
					<div class="ui-icon-set --chevron-right"></div>
				</div>
				<div class="booking-booking-sidebar-calendar-button --collapse" @click="onCollapseClick">
					<div v-if="expanded" class="ui-icon-set --collapse"></div>
					<div v-else class="ui-icon-set --expand-1"></div>
				</div>
			</div>
			<div
				class="booking-booking-sidebar-calendar-date-picker"
				:class="{'--expanded': expanded}"
				ref="datePicker"
			></div>
		</div>
	`
	};

	const Sidebar = {
	  components: {
	    Calendar
	  },
	  template: `
		<div class="booking-booking-sidebar">
			<Calendar/>
		</div>
	`
	};

	const DragDelete = {
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set
	    };
	  },
	  computed: ui_vue3_vuex.mapGetters({
	    draggedBookingId: `${booking_const.Model.Interface}/draggedBookingId`
	  }),
	  methods: {
	    onMouseUp() {
	      new booking_lib_removeBooking.RemoveBooking(this.draggedBookingId);
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<div v-if="draggedBookingId" class="booking-booking-drag-delete">
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

	const Grid = {
	  data() {
	    return {
	      scrolledToBooking: false
	    };
	  },
	  mounted() {
	    this.ears = new ui_ears.Ears({
	      container: this.$refs.columnsContainer,
	      smallSize: true,
	      className: 'booking-booking-grid-columns-ears'
	    }).init();
	    main_core.Event.EventEmitter.subscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
	    main_core.Event.EventEmitter.subscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      resourcesIds: `${booking_const.Model.Interface}/resourcesIds`,
	      scroll: `${booking_const.Model.Interface}/scroll`,
	      editingBookingId: `${booking_const.Model.Interface}/editingBookingId`
	    }),
	    editingBooking() {
	      var _this$$store$getters$;
	      return (_this$$store$getters$ = this.$store.getters['bookings/getById'](this.editingBookingId)) != null ? _this$$store$getters$ : null;
	    }
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
	      const top = booking_lib_grid.grid.calculateTop(this.editingBooking.dateFromTs);
	      const height = booking_lib_grid.grid.calculateHeight(this.editingBooking.dateFromTs, this.editingBooking.dateToTs);
	      this.$refs.inner.scrollTop = top + height / 2 + this.$refs.inner.offsetHeight / 2;
	      this.scrolledToBooking = true;
	    },
	    tryShowAhaMoment() {
	      if (this.areEarsShown() && booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.ExpandGrid)) {
	        main_core.Event.EventEmitter.unsubscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
	        main_core.Event.EventEmitter.unsubscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
	        void this.$refs.scalePanel.showAhaMoment();
	      }
	    }
	  },
	  watch: {
	    scroll(value) {
	      this.$refs.columnsContainer.scrollLeft = value;
	    },
	    editingBooking() {
	      this.scrollToEditingBooking();
	    }
	  },
	  components: {
	    LeftPanel,
	    NowLine,
	    Column,
	    Bookings,
	    ScalePanel,
	    Sidebar,
	    DragDelete
	  },
	  template: `
		<div class="booking-booking-grid">
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

	const ResourceWorkload = {
	  name: 'ResourceWorkload',
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
	      return Math.round(this.bookingCount / this.slotsCount * 100);
	    },
	    bookingCount() {
	      return this.bookings.length;
	    },
	    slotsCount() {
	      var _this$resource$slotRa;
	      const selectedDate = new Date(this.selectedDateTs);
	      const selectedWeekDay = booking_const.DateFormat.WeekDays[selectedDate.getDay()];
	      const slotRanges = booking_lib_busySlots.busySlots.filterSlotRanges(this.resource.slotRanges.filter(slotRange => {
	        return slotRange.weekDays.includes(selectedWeekDay);
	      }));
	      const slotSize = (_this$resource$slotRa = this.resource.slotRanges[0].slotSize) != null ? _this$resource$slotRa : 60;
	      return Math.floor(slotRanges.reduce((sum, slotRange) => {
	        return sum + (slotRange.to - slotRange.from) / slotSize;
	      }, 0));
	    },
	    bookings() {
	      const dateTs = this.selectedDateTs;
	      return this.$store.getters[`${booking_const.Model.Bookings}/getByDateAndResources`](dateTs, [this.resourceId]);
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
	  watch: {
	    bookingsCount(newCount, previousCount) {
	      if (this.isGrid && newCount > previousCount && booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.ResourceWorkload)) {
	        void this.showAhaMoment();
	      }
	    }
	  },
	  components: {
	    BatteryIcon,
	    WorkloadPopup
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
			:bookingCount="bookingCount"
			:workLoadPercent="workLoadPercent"
			:bindElement="$refs.container"
			@close="closePopup"
		/>
	`
	};

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
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
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
	          await this.deleteResource(this.resourceId);
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
	    async hideResource(resourceId) {
	      const ids = [...this.favoritesIds];
	      const index = this.favoritesIds.indexOf(resourceId);
	      if (index === -1) {
	        return;
	      }
	      ids.splice(index, 1);
	      await booking_lib_resources.hideResources(ids);
	    },
	    async deleteResource(resourceId) {
	      const confirmed = await this.confirmDelete(resourceId);
	      if (confirmed) {
	        await booking_provider_service_resourcesService.resourceService.delete(resourceId);
	      }
	    },
	    async confirmDelete(resourceId) {
	      const disabled = await booking_provider_service_resourcesService.resourceService.hasBookings(resourceId);
	      return new Promise(resolve => {
	        const messageBox = ui_dialogs_messagebox.MessageBox.create({
	          message: main_core.Loc.getMessage('BOOKING_RESOURCE_CONFIRM_DELETE'),
	          yesCaption: main_core.Loc.getMessage('BOOKING_RESOURCE_CONFIRM_DELETE_YES'),
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
	        if (disabled) {
	          const popup = messageBox.getPopupWindow();
	          popup.subscribe('onAfterShow', () => {
	            const yesButton = messageBox.getYesButton();
	            yesButton.setDisabled(true);
	            main_core.Event.bind(yesButton.getContainer(), 'mouseenter', () => {
	              this.hint.show(yesButton.getContainer(), main_core.Loc.getMessage('BOOKING_RESOURCE_CONFIRM_DELETE_HINT'), true);
	            });
	            main_core.Event.bind(yesButton.getContainer(), 'mouseleave', () => {
	              this.hint.hide(yesButton.getContainer());
	            });
	          });
	        }
	        messageBox.show();
	      });
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
				<div class="booking-booking-header-resource-profit" v-html="profit"></div>
				<div class="booking-booking-header-resource-actions">
					<ResourceMenu :resource-id/>
				</div>
			</template>
		</div>
	`
	};

	const AddResourceButton = {
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set
	    };
	  },
	  computed: ui_vue3_vuex.mapGetters({
	    isLoaded: `${booking_const.Model.Interface}/isLoaded`,
	    resourcesIds: `${booking_const.Model.Interface}/resourcesIds`,
	    isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	  }),
	  methods: {
	    addResource() {
	      if (!this.isFeatureEnabled) {
	        booking_lib_limit.limit.show();
	        return;
	      }
	      new booking_resourceCreationWizard.ResourceCreationWizard().open();
	    },
	    async showAhaMoment() {
	      await booking_lib_ahaMoments.ahaMoments.show({
	        id: 'booking-add-resource',
	        title: this.loc('BOOKING_AHA_ADD_RESOURCES_TITLE'),
	        text: this.loc('BOOKING_AHA_ADD_RESOURCES_TEXT'),
	        article: booking_const.HelpDesk.AhaAddResource,
	        target: this.$refs.button
	      });
	      booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.AddResource);
	    }
	  },
	  watch: {
	    isLoaded() {
	      if (booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.AddResource)) {
	        void this.showAhaMoment();
	      }
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<div
			class="booking-booking-header-add-resource"
			ref="button"
			@click="addResource"
		>
			<div
				class="booking-booking-header-add-resource-icon"
				:class="{'--lock': !isFeatureEnabled}"
			>
				<Icon v-if="isFeatureEnabled" :name="IconSet.PLUS_20"/>
				<Icon v-else :name="IconSet.LOCK" :size="16"/>
			</div>
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
	  computed: ui_vue3_vuex.mapGetters({
	    resourceTypes: 'resourceTypes/get'
	  }),
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
		<div class="booking-booking-resources-dialog-header-types">
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
				<template v-for="resourceType of resourceTypes" :key="resourceType.id">
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

	const SelectResources = {
	  data() {
	    return {
	      dialogFilled: false,
	      query: '',
	      saveItemsDebounce: main_core.Runtime.debounce(this.saveItems, 10, this),
	      workloadRefs: {},
	      selectedTypes: {}
	    };
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
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      selectedDateTs: `${booking_const.Model.Interface}/selectedDateTs`,
	      favoritesIds: `${booking_const.Model.Favorites}/get`,
	      resources: `${booking_const.Model.Resources}/get`,
	      isFilterMode: `${booking_const.Model.Interface}/isFilterMode`,
	      isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`,
	      isLoaded: `${booking_const.Model.Interface}/isLoaded`,
	      mainResources: `${booking_const.Model.MainResources}/resources`
	    }),
	    mainResourceIds() {
	      return new Set(this.mainResources);
	    },
	    isDefaultState() {
	      return this.mainResourceIds.size === this.favoritesIds.length && this.favoritesIds.every(id => this.mainResourceIds.has(id));
	    }
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
	        const resourcesIds = this.resources.map(resource => resource.id);
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
	      const visible = loadedResourcesIds.includes(id) && resource && this.selectedTypes[resource.typeId];
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
	      await booking_provider_service_resourceDialogService.resourceDialogService.doSearch(this.query, this.selectedDateTs / 1000);
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
	        title: this.loc('BOOKING_AHA_SELECT_RESOURCES_TITLE'),
	        text: this.loc('BOOKING_AHA_SELECT_RESOURCES_TEXT'),
	        article: booking_const.HelpDesk.AhaSelectResources,
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
	        this.updateItems();
	      },
	      deep: true
	    },
	    isLoaded() {
	      this.tryShowAhaMoment();
	    }
	  },
	  components: {
	    DialogHeader,
	    DialogFooter,
	    ResourceWorkload
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
	      const deletedIds = previousResourcesIds.filter(id => !resourcesIds.includes(id));
	      const newIds = resourcesIds.filter(id => !previousResourcesIds.includes(id));
	      deletedIds.forEach(id => {
	        const item = this.selector.getItem([booking_const.EntitySelectorEntity.Resource, id]);
	        this.selector.removeItem(item);
	      });
	      newIds.forEach(id => {
	        const resource = this.$store.getters[`${booking_const.Model.Resources}/getById`](id);
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
						{{ loc('BOOKING_BOOKING_INTERSECTION_BUTTON') }}
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
	        addButtonCaption: this.loc('BOOKING_BOOKING_ADD_INTERSECTION'),
	        showCreateButton: false,
	        maxHeight: 50,
	        dialogOptions: {
	          header: this.loc('BOOKING_BOOKING_ADD_INTERSECTION_DIALOG_HEADER'),
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
	      const deletedIds = previousResourcesIds.filter(id => !resourcesIds.includes(id));
	      const newIds = resourcesIds.filter(id => !previousResourcesIds.includes(id));
	      deletedIds.forEach(id => {
	        const item = this.selector.getDialog().getItem([booking_const.EntitySelectorEntity.Resource, id]);
	        this.selector.getDialog().removeItem(item);
	        const tag = this.selector.getTags().find(it => it.getId() === id);
	        tag == null ? void 0 : tag.remove();
	      });
	      newIds.forEach(id => {
	        const resource = this.$store.getters[`${booking_const.Model.Resources}/getById`](id);
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
	      isFilterMode: `${booking_const.Model.Interface}/isFilterMode`,
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

	const BaseComponent = {
	  data() {
	    return {
	      DateTimeFormat: main_date.DateTimeFormat
	    };
	  },
	  computed: ui_vue3_vuex.mapGetters({
	    fromHour: 'interface/fromHour',
	    toHour: 'interface/toHour',
	    zoom: 'interface/zoom'
	  }),
	  components: {
	    Header,
	    Intersections,
	    Grid
	  },
	  template: `
		<div
			id="booking-content"
			class="booking"
			:style="{
				'--from-hour': fromHour,
				'--to-hour': toHour,
				'--zoom': zoom,
			}"
			:class="{
				'--zoom-is-less-than-07': zoom < 0.7,
				'--zoom-is-less-than-08': zoom < 0.8,
				'--am-pm-mode': DateTimeFormat.isAmPmMode(),
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

	const MultiBookingItem = {
	  name: 'MultiBookingItem',
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
	  components: {
	    UiButton: booking_component_button.Button
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

	const MultiBookingItemsList = {
	  name: 'MultiBookingItemsList',
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
	  components: {
	    MultiBookingItem
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
	      clients: []
	    };
	  },
	  computed: ui_vue3_vuex.mapGetters({
	    selectedCells: `${booking_const.Model.Interface}/selectedCells`,
	    timezone: `${booking_const.Model.Interface}/timezone`
	  }),
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
	      this.fetching = false;
	      this.showNotification(bookingList);
	      await this.closeMultiBooking();
	    },
	    getBookings() {
	      return Object.values(this.selectedCells).map(cell => ({
	        id: `tmp-id-${Date.now()}-${Math.random()}`,
	        dateFromTs: cell.fromTs,
	        dateToTs: cell.toTs,
	        name: this.loc('BOOKING_BOOKING_DEFAULT_BOOKING_NAME'),
	        resourcesIds: [cell.resourceId],
	        timezoneFrom: this.timezone,
	        timezoneTo: this.timezone,
	        clients: this.clients
	      }));
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
					<CancelButton @click="closeMultiBooking" />
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
	        this.bannerClosed = new booking_lib_resolvable.Resolvable();
	        await this.bannerClosed;
	        onDone();
	      });
	    },
	    closeBanner() {
	      this.isBannerShown = false;
	      this.setShown();
	      this.bannerClosed.resolve();
	    },
	    setShown() {
	      booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.Banner);
	    }
	  },
	  template: `
		<component v-if="isBannerShown" :is="bannerComponent" @setShown="setShown" @close="closeBanner"/>
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

	const App = {
	  name: 'BookingApp',
	  props: {
	    afterTitleContainer: HTMLElement,
	    counterPanelContainer: HTMLElement,
	    filterId: String
	  },
	  data() {
	    return {
	      loader: new main_loader.Loader()
	    };
	  },
	  beforeMount() {
	    booking_lib_mousePosition.mousePosition.init();
	  },
	  async mounted() {
	    this.showLoader();
	    expandOffHours.setExpanded(true);
	    this.addAfterTitle();
	    await Promise.all([booking_provider_service_dictionaryService.dictionaryService.fetchData(), this.fetchPage(this.isEditingBookingMode ? 0 : this.selectedDateTs / 1000)]);
	    void this.$store.dispatch(`${booking_const.Model.Interface}/setIsLoaded`, true);
	  },
	  beforeUnmount() {
	    booking_lib_mousePosition.mousePosition.destroy();
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      selectedDateTs: `${booking_const.Model.Interface}/selectedDateTs`,
	      viewDateTs: `${booking_const.Model.Interface}/viewDateTs`,
	      isFilterMode: `${booking_const.Model.Interface}/isFilterMode`,
	      filteredBookingsIds: `${booking_const.Model.Interface}/filteredBookingsIds`,
	      selectedCells: `${booking_const.Model.Interface}/selectedCells`,
	      resourcesIds: `${booking_const.Model.Favorites}/get`,
	      extraResourcesIds: `${booking_const.Model.Interface}/extraResourcesIds`,
	      bookings: `${booking_const.Model.Bookings}/get`,
	      intersections: `${booking_const.Model.Interface}/intersections`,
	      editingBookingId: `${booking_const.Model.Interface}/editingBookingId`,
	      isEditingBookingMode: `${booking_const.Model.Interface}/isEditingBookingMode`,
	      offset: `${booking_const.Model.Interface}/offset`
	    }),
	    hasSelectedCells() {
	      return Object.keys(this.selectedCells).length > 0;
	    },
	    editingBooking() {
	      var _this$$store$getters$;
	      return (_this$$store$getters$ = this.$store.getters['bookings/getById'](this.editingBookingId)) != null ? _this$$store$getters$ : null;
	    }
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
	      this.$refs.filter.setPresetId(this.getPresetIdByCounterItem(counterItem));
	    },
	    async applyFilter() {
	      const presetId = this.$refs.filter.getPresetId();
	      const fields = this.$refs.filter.getFields();
	      this.setCounterItem(this.getCounterItemByPresetId(presetId));
	      this.showLoader();
	      await Promise.all([this.$store.dispatch(`${booking_const.Model.Interface}/setFilterMode`, true), this.updateMarks(), booking_provider_service_bookingService.bookingService.filter(fields)]);
	      this.hideLoader();
	    },
	    async clearFilter() {
	      this.setCounterItem(null);
	      booking_provider_service_calendarService.calendarService.clearFilterCache();
	      booking_provider_service_bookingService.bookingService.clearFilterCache();
	      void Promise.all([this.$store.dispatch(`${booking_const.Model.Interface}/setResourcesIds`, this.resourcesIds), this.$store.dispatch(`${booking_const.Model.Interface}/setFilterMode`, false), this.$store.dispatch(`${booking_const.Model.Interface}/setFilteredBookingsIds`, []), this.$store.dispatch(`${booking_const.Model.Interface}/setFilteredMarks`, [])]);
	      this.hideLoader();
	    },
	    setCounterItem(item) {
	      this.ignoreConterPanel = true;
	      setTimeout(() => {
	        this.ignoreConterPanel = false;
	      }, 0);
	      this.$refs.countersPanel.setItem(item);
	    },
	    getCounterItemByPresetId(presetId) {
	      return {
	        [FilterPreset.NotConfirmed]: CounterItem.NotConfirmed,
	        [FilterPreset.Delayed]: CounterItem.Delayed
	      }[presetId];
	    },
	    getPresetIdByCounterItem(counterItem) {
	      return {
	        [CounterItem.NotConfirmed]: FilterPreset.NotConfirmed,
	        [CounterItem.Delayed]: FilterPreset.Delayed
	      }[counterItem];
	    },
	    addAfterTitle() {
	      this.afterTitleContainer.append(this.$refs.afterTitle.$el);
	    },
	    showResourcesWithBookings() {
	      const resourcesIds = this.$store.getters[`${booking_const.Model.Bookings}/getByDateAndIds`](this.selectedDateTs, this.filteredBookingsIds).map(booking => booking.resourcesIds[0]).filter((value, index, array) => array.indexOf(value) === index);
	      void this.$store.dispatch(`${booking_const.Model.Interface}/setResourcesIds`, resourcesIds);
	    },
	    async updateMarks() {
	      if (this.isFilterMode) {
	        await this.updateFilterMarks();
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
	      await this.$store.dispatch(`${booking_const.Model.Interface}/setFilteredMarks`, []);
	      await booking_provider_service_calendarService.calendarService.loadFilterMarks(fields);
	    },
	    async updateCounterMarks() {
	      await booking_provider_service_calendarService.calendarService.loadCounterMarks(this.viewDateTs);
	    },
	    showLoader() {
	      void this.loader.show(this.$refs.baseComponent.$el);
	    },
	    hideLoader() {
	      void this.loader.hide();
	    }
	  },
	  watch: {
	    selectedDateTs() {
	      if (this.isFilterMode) {
	        void this.applyFilter();
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
	    viewDateTs() {
	      void this.updateMarks();
	    },
	    resourcesIds() {
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
	    }
	  },
	  components: {
	    BaseComponent,
	    AfterTitle,
	    Filter,
	    CountersPanel,
	    MultiBooking,
	    Banner,
	    Trial
	  },
	  template: `
		<div>
			<MultiBooking v-if="hasSelectedCells"/>
			<AfterTitle ref="afterTitle"/>
			<Filter
				:filterId="filterId"
				ref="filter"
				@apply="applyFilter"
				@clear="clearFilter"
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

}((this.BX.Booking = this.BX.Booking || {}),BX.Booking.Component.Mixin,BX,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.Event,BX.UI,BX.UI,BX.UI.Vue3.Components,BX.Booking.Lib,BX.Booking.Lib,BX.Booking.Component,BX.Booking.Component,BX,BX.Vue3.Directives,BX.UI.NotificationManager,BX.Booking.Provider.Service,BX.Booking.Component,BX.Vue3.Directives,BX,BX.Booking.Lib,BX.Booking.Component,BX.Booking.Lib,BX.Booking.Component,BX.Booking.Lib,BX.Booking.Lib,BX.Booking,BX.UI.DatePicker,BX.Booking.Lib,BX.Booking.Lib,BX.Booking.Component,BX.UI.Dialogs,BX,BX.Booking.Provider.Service,BX,BX.Booking,BX.Booking.Provider.Service,BX.Booking.Lib,BX.Booking.Lib,BX.Main,BX.UI.IconSet,BX,BX.Booking.Provider.Service,BX.Booking.Lib,BX.Booking.Lib,BX.UI.EntitySelector,BX.Booking.Lib,BX.Booking.Provider.Service,BX.UI,BX.Main,BX.Booking.Lib,BX.Booking.Component,BX.Booking.Component,BX.UI.AutoLaunch,BX.Vue3.Vuex,BX,BX.Vue3,BX.UI,BX.Booking.Lib,BX.Booking.Const,BX.Booking.Lib));
//# sourceMappingURL=booking.bundle.js.map
