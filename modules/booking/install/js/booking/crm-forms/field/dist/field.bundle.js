/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,booking_const,booking_lib_segments,booking_lib_slotRanges,ui_datePicker,main_date,main_loader,booking_component_mixin_locMixin) {
	'use strict';

	const ALL_RESOURCES_ID = -1;
	const AllResource = {
	  id: ALL_RESOURCES_ID,
	  name: '',
	  typeName: '',
	  slotRanges: []
	};
	const DayIndexDict = Object.freeze({
	  Sun: 0,
	  Mon: 1,
	  Tue: 2,
	  Wed: 3,
	  Thu: 4,
	  Fri: 5,
	  Sat: 6
	});

	function mapDtoToResource(resourcesDto) {
	  return resourcesDto.map(dto => {
	    return {
	      ...dto,
	      slotRanges: dto.slotRanges.map(slotRange => {
	        return {
	          ...slotRange,
	          weekDays: slotRange.weekDays.map(weekDay => DayIndexDict[weekDay])
	        };
	      })
	    };
	  });
	}

	let occupancyInstance = null;
	function createOccupancy(runAction) {
	  if (occupancyInstance instanceof Occupancy) {
	    return occupancyInstance;
	  }
	  occupancyInstance = new Occupancy(runAction);
	  return occupancyInstance;
	}
	var _runAction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("runAction");
	var _timezone = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("timezone");
	var _resources = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resources");
	var _requestCache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestCache");
	var _requestedResourcesIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestedResourcesIds");
	var _occupancy = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("occupancy");
	var _requestOccupancy = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestOccupancy");
	var _getPromises = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPromises");
	var _calculateOccupancy = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calculateOccupancy");
	class Occupancy {
	  constructor(runAction) {
	    Object.defineProperty(this, _calculateOccupancy, {
	      value: _calculateOccupancy2
	    });
	    Object.defineProperty(this, _getPromises, {
	      value: _getPromises2
	    });
	    Object.defineProperty(this, _requestOccupancy, {
	      value: _requestOccupancy2
	    });
	    Object.defineProperty(this, _runAction, {
	      writable: true,
	      value: () => {}
	    });
	    Object.defineProperty(this, _timezone, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resources, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _requestCache, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _requestedResourcesIds, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _occupancy, {
	      writable: true,
	      value: {}
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _runAction)[_runAction] = runAction;
	  }
	  setResources(resources) {
	    babelHelpers.classPrivateFieldLooseBase(this, _resources)[_resources] = resources;
	    return this;
	  }
	  setTimezone(timezone) {
	    babelHelpers.classPrivateFieldLooseBase(this, _timezone)[_timezone] = timezone;
	    return this;
	  }
	  async getOccupancy(ids, dateTs) {
	    const requestedResourcesIds = babelHelpers.classPrivateFieldLooseBase(this, _requestedResourcesIds)[_requestedResourcesIds][dateTs];
	    const unrequestedResourcesIds = ids.filter(id => !(requestedResourcesIds != null && requestedResourcesIds.has(id)));
	    if (unrequestedResourcesIds.length > 0) {
	      var _babelHelpers$classPr, _babelHelpers$classPr2;
	      const request = babelHelpers.classPrivateFieldLooseBase(this, _requestOccupancy)[_requestOccupancy](unrequestedResourcesIds, dateTs);
	      (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _requestCache)[_requestCache])[dateTs]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[dateTs] = {};
	      unrequestedResourcesIds.forEach(resourceId => {
	        babelHelpers.classPrivateFieldLooseBase(this, _requestCache)[_requestCache][dateTs][resourceId] = request;
	      });
	    }
	    await Promise.all(babelHelpers.classPrivateFieldLooseBase(this, _getPromises)[_getPromises](ids, dateTs));
	    return babelHelpers.classPrivateFieldLooseBase(this, _calculateOccupancy)[_calculateOccupancy](ids, dateTs);
	  }
	  static calcResourceSlots({
	    date,
	    slotRanges,
	    resourceOccupancy,
	    resourceId = null,
	    timezone
	  }) {
	    const slots = [];
	    const now = Date.now();
	    booking_lib_slotRanges.SlotRanges.applyTimezone(slotRanges, date.getTime(), timezone).filter(slotRange => slotRange.weekDays.includes(date.getDay())).sort((a, b) => a.from - b.from).forEach(slotRange => {
	      const slotSizeTs = slotRange.slotSize * 60 * 1000;
	      const slotToTs = convertMinToTs(date, slotRange.to);
	      const step = slotRange.slotSize <= 120 ? 30 : 60;
	      const stepTs = step * 60 * 1000;
	      const occupancies = resourceId === null ? resourceOccupancy : resourceOccupancy.filter(({
	        resourcesIds
	      }) => resourcesIds.includes(resourceId));
	      let fromTs = convertMinToTs(date, slotRange.from);
	      if (fromTs < now) {
	        fromTs = roundSlotStart(fromTs, step);
	      }
	      while (fromTs < slotToTs) {
	        const toTs = fromTs + slotSizeTs;
	        if (fromTs <= now || toTs > slotToTs || checkIsOccupiedSlot({
	          fromTs,
	          toTs
	        }, occupancies)) {
	          fromTs += stepTs;
	          continue;
	        }
	        slots.push({
	          fromTs,
	          toTs,
	          label: main_date.DateTimeFormat.format('H:i', new Date(fromTs))
	        });
	        fromTs += stepTs;
	      }
	    });
	    return slots;
	  }
	}
	async function _requestOccupancy2(ids, dateTs) {
	  var _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6;
	  (_babelHelpers$classPr4 = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _occupancy)[_occupancy])[dateTs]) != null ? _babelHelpers$classPr4 : _babelHelpers$classPr3[dateTs] = {};
	  (_babelHelpers$classPr6 = (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _requestedResourcesIds)[_requestedResourcesIds])[dateTs]) != null ? _babelHelpers$classPr6 : _babelHelpers$classPr5[dateTs] = new Set();
	  ids.forEach(resourceId => {
	    var _babelHelpers$classPr7, _babelHelpers$classPr8;
	    (_babelHelpers$classPr8 = (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _occupancy)[_occupancy][dateTs])[resourceId]) != null ? _babelHelpers$classPr8 : _babelHelpers$classPr7[resourceId] = [];
	    babelHelpers.classPrivateFieldLooseBase(this, _requestedResourcesIds)[_requestedResourcesIds][dateTs].add(resourceId);
	  });
	  const {
	    data: occupancy
	  } = await babelHelpers.classPrivateFieldLooseBase(this, _runAction)[_runAction]('booking.api_v1.CrmForm.getOccupancy', {
	    data: {
	      ids,
	      dateTs: dateTs / 1000
	    }
	  });
	  occupancy.forEach(({
	    resourcesIds,
	    fromTs,
	    toTs
	  }) => {
	    resourcesIds.forEach(resourceId => {
	      var _babelHelpers$classPr9;
	      (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _occupancy)[_occupancy][dateTs][resourceId]) == null ? void 0 : _babelHelpers$classPr9.push({
	        fromTs,
	        toTs,
	        resourcesIds
	      });
	    });
	  });
	}
	function _getPromises2(ids, dateTs) {
	  return Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _requestCache)[_requestCache][dateTs]).filter(resourceId => ids.includes(Number(resourceId))).map(resourceId => babelHelpers.classPrivateFieldLooseBase(this, _requestCache)[_requestCache][dateTs][resourceId]);
	}
	function _calculateOccupancy2(resourcesIds, dateTs) {
	  const segments = new booking_lib_segments.Segments([[dateTs, new Date(dateTs).setDate(new Date(dateTs).getDate() + 1)]]);
	  const resource = babelHelpers.classPrivateFieldLooseBase(this, _resources)[_resources].find(({
	    id
	  }) => id === resourcesIds[0]);
	  const selectedWeekDay = booking_const.DateFormat.WeekDays[new Date(dateTs).getDay()];
	  booking_lib_slotRanges.SlotRanges.applyTimezone(resource.slotRanges, dateTs, babelHelpers.classPrivateFieldLooseBase(this, _timezone)[_timezone]).filter(slotRange => slotRange.weekDays.includes(selectedWeekDay)).forEach(slotRange => segments.subtract([new Date(dateTs).setMinutes(slotRange.from), new Date(dateTs).setMinutes(slotRange.to)]));
	  return resourcesIds.flatMap(resourceId => babelHelpers.classPrivateFieldLooseBase(this, _occupancy)[_occupancy][dateTs][resourceId]).map(({
	    fromTs,
	    toTs,
	    resourcesIds: slotResourcesIds
	  }) => {
	    return {
	      fromTs: fromTs * 1000,
	      toTs: toTs * 1000,
	      resourcesIds: slotResourcesIds
	    };
	  });
	}
	function roundSlotStart(slotStartTs, step) {
	  const date = new Date(slotStartTs);
	  date.setMinutes(step - date.getMinutes() > 3 ? step : step * 2);
	  return date.getTime();
	}
	function convertMinToTs(date, min) {
	  const d = new Date(date);
	  d.setHours(0, 0, 0, 0);
	  d.setMinutes(min);
	  return d.getTime();
	}
	function checkIsOccupiedSlot(slot, resourceOccupancy) {
	  return resourceOccupancy.some(occupancy => {
	    return slot.fromTs >= occupancy.fromTs && slot.fromTs < occupancy.toTs || slot.toTs > occupancy.fromTs && slot.toTs <= occupancy.toTs;
	  });
	}

	// @vue/component
	const ResourceSelector = {
	  name: 'ResourceSelector',
	  props: {
	    resources: {
	      type: Array,
	      default: () => []
	    }
	  },
	  emits: ['select'],
	  template: `
		<div class="booking--crm-forms--resource-selector">
			<div
				v-for="(resource) in resources"
				:key="resource.id"
				class="b24-form-control-list-selector-item booking--crm-forms--resource-selector-resource"
				@click="$emit('select', resource)"
			>
				<div>
					<div class="booking--crm-forms--time-selector-block-resource-name">
						{{ resource.name }}
					</div>
					<div class="booking--crm-forms--time-selector-block-resource-type-name">
						{{ resource.typeName }}
					</div>
				</div>
			</div>
		</div>
	`
	};

	// eslint-disable-next-line no-unused-vars

	// @vue/component
	const ResourceSelectBlock = {
	  name: 'ResourceSelectBlock',
	  components: {
	    ResourceSelector
	  },
	  props: {
	    resourceId: {
	      type: Number,
	      required: true
	    },
	    /**
	     * @type {Resource[]}
	     */
	    resources: {
	      type: Array,
	      required: true
	    },
	    settingsData: {
	      type: Object,
	      required: true
	    },
	    fetching: {
	      type: Boolean,
	      default: false
	    },
	    errorMessage: {
	      type: String,
	      default: ''
	    },
	    hasErrors: {
	      type: Boolean,
	      default: false
	    },
	    dependencies: {
	      type: Object,
	      required: true
	    }
	  },
	  emits: ['update:resourceId'],
	  data() {
	    return {
	      dropdownOpened: false
	    };
	  },
	  computed: {
	    label() {
	      var _this$settingsData;
	      return ((_this$settingsData = this.settingsData) == null ? void 0 : _this$settingsData.label) || '';
	    },
	    placeholder() {
	      var _this$settingsData2;
	      return `${((_this$settingsData2 = this.settingsData) == null ? void 0 : _this$settingsData2.textHeader) || ''} *`;
	    },
	    resource() {
	      return this.resources.find(resource => resource.id === this.resourceId);
	    },
	    resourceName() {
	      var _this$resource;
	      return ((_this$resource = this.resource) == null ? void 0 : _this$resource.name) || '';
	    },
	    hint() {
	      var _this$settingsData3, _this$settingsData4;
	      return {
	        text: ((_this$settingsData3 = this.settingsData) == null ? void 0 : _this$settingsData3.hint) || '',
	        visible: Boolean((_this$settingsData4 = this.settingsData) == null ? void 0 : _this$settingsData4.isVisibleHint)
	      };
	    },
	    fieldItemDropdownComponent() {
	      return this.dependencies.mixinDropdown.components['field-item-dropdown'];
	    }
	  },
	  methods: {
	    toggleDropdown() {
	      if (this.dropdownOpened) {
	        this.closeDropdown();
	        return;
	      }
	      if (this.fetching) {
	        return;
	      }
	      this.dropdownOpened = true;
	    },
	    closeDropdown() {
	      setTimeout(() => {
	        this.dropdownOpened = false;
	      }, 0);
	    },
	    setResource(resource) {
	      this.$emit('update:resourceId', resource.id);
	      if (this.dropdownOpened) {
	        this.closeDropdown();
	      }
	    }
	  },
	  template: `
		<div
			class="booking-crm-forms-field"
			:class="{
				'--error': hasErrors,
			}"
		>
			<div class="b24-form-field-layout-section booking-crm-forms-field-title">
				{{ label }}
			</div>
			<div
				ref="tagSelector"
				class="booking-crm-forms-field-tag-selector b24-form-control-string"
				:class="{
					'--disabled': fetching,
				}"
			>
				<div
					class="b24-form-control-container b24-form-control-icon-after"
					@click="toggleDropdown"
				>
					<input
						name="resourceName"
						type="text"
						readonly
						:placeholder="placeholder"
						:value="resourceName"
						class="b24-form-control booking--crm-forms--field-tag-selector-input"
						@click.capture.stop.prevent="toggleDropdown"
					/>
					<div class="booking--crm-forms--field-tag-selector-input-icon"></div>
				</div>
			</div>
			<div class="b24-form-control-alert-message" style="top: 75px">{{ errorMessage }}</div>
			<component
				v-if="dropdownOpened"
				:is="fieldItemDropdownComponent"
				:marginTop="0"
				:visible="dropdownOpened"
				:title="label"
				@close="closeDropdown()"
			>
				<ResourceSelector :resources="resources" @select="setResource"/>
			</component>
			<div v-if="hint.visible" class="booking--crm-forms--field-hint">
				<div class="booking--crm-forms--field-hint-text">{{ hint.text }}</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const CalendarBlock = {
	  name: 'CalendarBlock',
	  mixins: [booking_component_mixin_locMixin.locMixin],
	  props: {
	    date: {
	      type: Date,
	      default: null
	    },
	    resource: {
	      type: Object,
	      required: true
	    },
	    titleOnly: {
	      type: Boolean,
	      default: false
	    },
	    hasError: {
	      type: Boolean,
	      default: false
	    },
	    errorMessage: {
	      type: String,
	      default: ''
	    }
	  },
	  emits: ['updateDate'],
	  data() {
	    return {
	      viewDate: null,
	      disabledPrevMonth: true
	    };
	  },
	  computed: {
	    formattedViewDate() {
	      return main_date.DateTimeFormat.format('f Y', this.viewDate);
	    },
	    title() {
	      if (this.date !== null && this.titleOnly) {
	        return this.loc('BOOKING_CRM_FORMS_FIELD_TIME_TITLE', {
	          '#DATE#': main_date.DateTimeFormat.format(this.loc('DAY_MONTH_FORMAT'), this.date)
	        });
	      }
	      return this.loc('BOOKING_CRM_FORMS_FIELD_DATE_TIME_TITLE');
	    }
	  },
	  created() {
	    this.viewDate = this.date;
	    const selectedDates = this.date instanceof Date ? [this.date.getTime()] : [];
	    this.datePicker = new ui_datePicker.DatePicker({
	      selectedDates,
	      startDate: new Date(),
	      inline: true,
	      hideHeader: true
	    });
	    this.datePicker.subscribe(ui_datePicker.DatePickerEvent.SELECT, event => {
	      const date = event.getData().date;
	      const selectedDate = this.toDateFromUtc(date);
	      this.setViewDate();
	      if (selectedDate !== this.date) {
	        this.$emit('updateDate', selectedDate);
	      }
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
	    setPreviousMonth() {
	      const viewDate = this.datePicker.getViewDate();
	      if (this.checkPastDate()) {
	        this.updateDisabledPrevMonth();
	        return;
	      }
	      this.datePicker.setViewDate(ui_datePicker.getNextDate(viewDate, 'month', -1));
	      this.setViewDate();
	    },
	    setNextMonth() {
	      const viewDate = this.datePicker.getViewDate();
	      this.updateDisabledPrevMonth();
	      this.datePicker.setViewDate(ui_datePicker.getNextDate(viewDate, 'month'));
	      this.setViewDate();
	    },
	    setViewDate() {
	      this.viewDate = this.toDateFromUtc(this.datePicker.getViewDate());
	      this.updateDisabledPrevMonth();
	    },
	    toDateFromUtc(date) {
	      return new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate());
	    },
	    updateDisabledPrevMonth() {
	      this.disabledPrevMonth = this.checkPastDate();
	    },
	    checkPastDate() {
	      const viewDate = this.datePicker.getViewDate();
	      const today = new Date();
	      return viewDate.getMonth() <= today.getMonth() && viewDate.getYear() <= today.getYear();
	    }
	  },
	  template: `
		<div
			class="booking-crm-forms-field booking--crm-forms--calendar-block"
			:class="{
				'--error': hasError,
			}"
		>
			<div class="b24-form-field-layout-section booking-crm-forms-field-title">{{ title }}</div>
			<div
				v-if="hasError"
				class="b24-form-control-alert-message"
				style="top: 30px"
			>
				{{ errorMessage }}
			</div>
			<div v-show="!titleOnly" class="booking--crm-forms--calendar-block-content">
				<div class="booking--crm-forms--calendar-block-datepicker-header">
					<div class="booking--crm-forms--calendar-block-datepicker-header-title">
						{{ formattedViewDate }}
					</div>
					<div
						class="booking--crm-forms--calendar-block-datepicker-header-button --left"
						:class="{ '--disabled': disabledPrevMonth }"
						@click="setPreviousMonth"
					>
						<div class="booking--crm-forms--calendar-block-datepicker-icon --chevron-left"></div>
					</div>
					<div
						class="booking--crm-forms--calendar-block-datepicker-header-button --right"
						@click="setNextMonth"
					>
						<div class="booking--crm-forms--calendar-block-datepicker-icon --chevron-right"></div>
					</div>
				</div>
				<div ref="datePicker" class="booking--crm-forms--calendar-block-datepicker"></div>
			</div>
		</div>
	`
	};

	// @vue/component
	const ResourceSlotsUiBlock = {
	  name: 'ResourceSlotsUiBlock',
	  mixins: [booking_component_mixin_locMixin.locMixin],
	  props: {
	    /**
	     * @type {ResourceSlot|null}
	     */
	    slot: {
	      type: Object,
	      default: null
	    },
	    date: {
	      type: Date,
	      required: true
	    },
	    /**
	     * @type {Resource}
	     */
	    resource: {
	      type: Object,
	      required: true
	    },
	    resourceSlots: {
	      type: Array,
	      default: () => []
	    },
	    loading: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['select'],
	  computed: {
	    formatDate() {
	      return main_date.DateTimeFormat.format(this.loc('DAY_MONTH_FORMAT'), this.date);
	    },
	    title() {
	      return this.loc('BOOKING_CRM_FORMS_FIELD_TIME_TITLE', {
	        '#DATE#': this.formatDate
	      });
	    },
	    emptySlotsMessage() {
	      const day = this.date.getDay();
	      if (this.resource.slotRanges.every(({
	        weekDays
	      }) => !weekDays.includes(day))) {
	        return this.loc('BOOKING_CRM_FORMS_RESOURCE_RESOURCE_NOT_WORKING_MESSAGE');
	      }
	      return this.loc('BOOKING_CRM_FORMS_RESOURCE_NO_SLOTS_MESSAGE', {
	        '#BR#': '<br />'
	      });
	    }
	  },
	  watch: {
	    loading: {
	      handler(loading) {
	        if (loading) {
	          var _this$loader;
	          (_this$loader = this.loader) == null ? void 0 : _this$loader.show == null ? void 0 : _this$loader.show(this.$refs.slotsContainer);
	        } else {
	          var _this$loader2;
	          (_this$loader2 = this.loader) == null ? void 0 : _this$loader2.hide == null ? void 0 : _this$loader2.hide();
	        }
	      },
	      immediate: true
	    }
	  },
	  beforeMount() {
	    this.loader = new main_loader.Loader({
	      target: this.$refs.slotsContainer,
	      size: 60
	    });
	  },
	  methods: {
	    selectSlot(slot) {
	      const payload = {
	        date: this.date,
	        resource: this.resource,
	        slot
	      };
	      this.$emit('select', payload);
	    }
	  },
	  template: `
		<div class="booking-crm-forms-field booking--crm-forms--resource-slots">
			<slot name="title"/>
			<div class="booking--crm-forms--time-selector-block-header">
				<div class="booking--crm-forms--time-selector-block-resource">
					<div class="booking--crm-forms--time-selector-block-resource-name">
						{{ resource.name }}
					</div>
					<div class="booking--crm-forms--time-selector-block-resource-type-name">
						{{ resource.typeName }}
					</div>
				</div>
				<div class="booking--crm-forms--time-selector-block-header__button">
					<slot name="changeDateBtn" :date="date" :resource="resource"/>
				</div>
			</div>
			<div ref="slotsContainer" class="booking--crm-forms--resource-slots__slot-list-wrapper">
				<div v-show="!loading" class="booking--crm-forms--resource-slots__slot-list">
					<div
						v-for="resourceSlot in resourceSlots"
						:key="resourceSlot"
						class="booking--crm-forms-time-selector-block-time-list-item booking--crm-forms--resource-slots__slot"
						:class="{
							'--selected': slot !== null && resourceSlot.fromTs === slot.fromTs
						}"
						@click="selectSlot(resourceSlot)"
					>
						<span>{{ resourceSlot.label }}</span>
					</div>
					<template v-if="resourceSlots.length === 0">
						<div class="booking--crm-forms--resource-slots__empty-slots">
							<div
								class="booking--crm-forms--resource-slots__empty-slots-message"
								v-html="emptySlotsMessage"
							>
							</div>
						</div>
					</template>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const TimeSelectorBlock = {
	  name: 'TimeSelectorBlock',
	  components: {
	    ResourceSlotsUiBlock
	  },
	  mixins: [booking_component_mixin_locMixin.locMixin],
	  inject: ['isPreview'],
	  props: {
	    slot: {
	      type: Object,
	      default: null
	    },
	    /**
	     * @type {Resource}
	     */
	    resource: {
	      type: Object,
	      default: null
	    },
	    /**
	     * @type {Resource[]}
	     */
	    resources: {
	      type: Array,
	      default: () => []
	    },
	    date: {
	      type: Date,
	      required: true
	    },
	    fetching: {
	      type: Boolean,
	      default: false
	    },
	    showChangeDateButton: {
	      type: Boolean,
	      default: false
	    },
	    runAction: {
	      type: Function,
	      required: true
	    },
	    timezone: {
	      type: String,
	      required: true
	    }
	  },
	  emits: ['update:slot', 'update:fetching', 'showCalendar'],
	  data() {
	    return {
	      resourceOccupancy: []
	    };
	  },
	  computed: {
	    resourceSlots() {
	      var _this$resource;
	      return Occupancy.calcResourceSlots({
	        date: this.date,
	        slotRanges: ((_this$resource = this.resource) == null ? void 0 : _this$resource.slotRanges) || [],
	        resourceOccupancy: this.resourceOccupancy || [],
	        timezone: this.timezone
	      });
	    }
	  },
	  watch: {
	    date: {
	      handler(date) {
	        if (date instanceof Date && !this.isPreview) {
	          void this.fetchOccupancy();
	        }
	      },
	      immediate: true
	    },
	    resource: {
	      handler() {
	        if (!this.isPreview) {
	          void this.fetchOccupancy();
	        }
	      }
	    }
	  },
	  created() {
	    this.initOccupancy();
	  },
	  methods: {
	    initOccupancy() {
	      if (this.occupancy instanceof Occupancy) {
	        return;
	      }
	      this.occupancy = createOccupancy(this.runAction);
	      this.occupancy.setResources(this.resources);
	      this.occupancy.setTimezone(this.timezone);
	    },
	    async fetchOccupancy() {
	      if (!this.date || this.fetching || this.resource === null) {
	        return;
	      }
	      if (!this.occupancy) {
	        this.initOccupancy();
	      }
	      this.$emit('update:fetching', true);
	      try {
	        const response = await this.occupancy.getOccupancy([this.resource.id], this.date.getTime());
	        this.resourceOccupancy = response || [];
	      } catch (error) {
	        console.error('Booking.CrmForms. GetOccupancy error', error);
	      } finally {
	        this.$emit('update:fetching', false);
	      }
	    },
	    changeSlot({
	      slot
	    }) {
	      this.$emit('update:slot', slot);
	    },
	    changeDate() {
	      this.$emit('showCalendar');
	    }
	  },
	  template: `
		<ResourceSlotsUiBlock
			:slot="slot"
			:resource="resource"
			:date="date"
			:resourceSlots="resourceSlots"
			:loading="fetching"
			@select="changeSlot"
		>
			<template #changeDateBtn>
				<button
					v-if="showChangeDateButton"
					type="button"
					class="booking--crm-forms--change-date-btn"
					@click="changeDate"
				>
					{{ loc('BOOKING_CRM_FORMS_CHANGE_DATE_BUTTON_CAPTION') }}
				</button>
			</template>
		</ResourceSlotsUiBlock>
	`
	};

	const DELAY = 300;

	// @vue/component
	const AvailableSlotsBlock = {
	  name: 'AvailableSlotsBlock',
	  components: {
	    ResourceSlotsUiBlock
	  },
	  mixins: [booking_component_mixin_locMixin.locMixin],
	  inject: ['isPreview'],
	  props: {
	    date: {
	      type: Date,
	      required: true
	    },
	    resources: {
	      type: Array,
	      required: true
	    },
	    runAction: {
	      type: Function,
	      required: true
	    },
	    timezone: {
	      type: String,
	      required: true
	    }
	  },
	  emits: ['update:form', 'update:resourceId'],
	  data() {
	    return {
	      fetching: false,
	      selectedResourceId: null,
	      visibleResourcesCount: 3,
	      resourceOccupancy: [],
	      resourcesSlots: []
	    };
	  },
	  computed: {
	    availableResource() {
	      return this.resourcesSlots.filter(({
	        slots
	      }) => slots.length > 0);
	    },
	    visibleResources() {
	      return this.availableResource.slice(0, this.visibleResourcesCount);
	    },
	    emptyResourcesSlotsMessage() {
	      return this.loc('BOOKING_CRM_FORMS_RESOURCE_NO_SLOTS_MESSAGE', {
	        '#BR#': '<br />'
	      });
	    }
	  },
	  watch: {
	    date: {
	      handler(date) {
	        if (date instanceof Date && !this.isPreview) {
	          void this.fetchAvailableSlots();
	        }
	      },
	      immediate: true
	    },
	    fetching: {
	      handler(fetching) {
	        var _this$loader2;
	        if (fetching) {
	          var _this$loader;
	          (_this$loader = this.loader) == null ? void 0 : _this$loader.show(this.$refs.resources);
	          return;
	        }
	        (_this$loader2 = this.loader) == null ? void 0 : _this$loader2.hide();
	      },
	      immediate: true
	    }
	  },
	  created() {
	    this.initOccupancy();
	  },
	  beforeMount() {
	    this.loader = new main_loader.Loader({
	      target: this.$refs.resources,
	      size: 60
	    });
	  },
	  methods: {
	    initOccupancy() {
	      if (this.occupancy instanceof Occupancy) {
	        return;
	      }
	      this.occupancy = createOccupancy(this.runAction);
	      this.occupancy.setResources(this.resources);
	      this.occupancy.setTimezone(this.timezone);
	    },
	    async fetchAvailableSlots() {
	      this.fetching = true;
	      if (!this.occupancy) {
	        this.initOccupancy();
	      }
	      try {
	        const resourcesIds = this.resources.map(resource => resource.id);
	        const response = await this.occupancy.getOccupancy(resourcesIds, this.date.getTime());
	        this.resourceOccupancy = response || [];
	        this.setResourcesSlots();
	      } catch (error) {
	        console.error('Booking.CrmForms. GetOccupancy for resources error', error);
	      } finally {
	        this.fetching = false;
	      }
	    },
	    setResourcesSlots() {
	      this.resourcesSlots = this.resources.map(resource => {
	        const resourceId = resource.id;
	        const resourceOccupancies = this.resourceOccupancy;
	        return {
	          resourceId,
	          resource,
	          slots: Occupancy.calcResourceSlots({
	            date: this.date,
	            slotRanges: resource.slotRanges,
	            resourceOccupancy: resourceOccupancies,
	            resourceId,
	            timezone: this.timezone
	          })
	        };
	      }).sort((a, b) => b.slots.length - a.slots.length);
	    },
	    showMore() {
	      this.visibleResourcesCount += 3;
	    },
	    changeDate(resourceId) {
	      this.selectedResourceId = resourceId;
	      setTimeout(() => {
	        this.$emit('update:form', {
	          resourceId
	        });
	      }, DELAY);
	    },
	    selectSlot({
	      resource,
	      slot
	    }) {
	      this.selectedResourceId = resource.id;
	      setTimeout(() => {
	        this.$emit('update:form', {
	          resourceId: resource.id,
	          slot
	        });
	      }, DELAY);
	    }
	  },
	  template: `
		<div ref="resources" class="booking--crm-forms--field-group">
			<template v-show="!fetching">
				<ResourceSlotsUiBlock
					v-for="resource in visibleResources"
					:key="resource.resourceId"
					:date="date"
					:resource="resource.resource"
					:resourceSlots="resource.slots"
					:class="{
						'--fade': selectedResourceId !== null && selectedResourceId !== resource.resourceId,
					}"
					@select="selectSlot"
				>
					<template #changeDateBtn>
						<button
							type="button"
							class="booking--crm-forms--change-date-btn"
							@click="changeDate(resource.resourceId)"
						>
							{{ loc('BOOKING_CRM_FORMS_CHANGE_DATE_BUTTON_CAPTION') }}
						</button>
					</template>
				</ResourceSlotsUiBlock>
			</template>
			<template v-if="!fetching && visibleResources.length === 0">
				<p
					class="booking--crm-forms--available-slots-block__empty-slots"
					v-html="emptyResourcesSlotsMessage"
				></p>
			</template>
			<template v-if="resources.length > 0 && visibleResources.length > 0 && visibleResourcesCount < availableResource.length">
				<div class="booking--crm-forms--field-group--available-slots-block__footer">
					<button
						type="button"
						class="booking--crm-forms--change-date-btn booking--crm-forms--field-group--available-slots-block__btn-show-more"
						@click="showMore"
					>
						{{ loc('BOOKING_CRM_FORMS_SHOW_MORE_SLOTS') }}
					</button>
				</div>
			</template>
		</div>
	`
	};

	// @vue/component
	const Field = {
	  name: 'CrmFormBookingField',
	  components: {
	    AvailableSlotsBlock,
	    CalendarBlock,
	    ResourceSelectBlock,
	    TimeSelectorBlock
	  },
	  mixins: [booking_component_mixin_locMixin.locMixin],
	  provide() {
	    return {
	      isPreview: this.isPreview
	    };
	  },
	  props: {
	    field: {
	      type: Object,
	      required: true
	    },
	    runAction: {
	      type: Function,
	      required: true
	    },
	    dependencies: {
	      type: Object,
	      required: true
	    }
	  },
	  emits: ['change'],
	  data() {
	    return {
	      form: {
	        resourceId: 0,
	        date: null,
	        dateTs: 0,
	        slot: null
	      },
	      resources: [],
	      fetchingResources: false,
	      fetchingOccupancy: false,
	      fetchingAutoSelectionResource: false,
	      visibleCalendar: false,
	      occupancy: null
	    };
	  },
	  computed: {
	    timezone() {
	      return Intl.DateTimeFormat().resolvedOptions().timeZone;
	    },
	    isPreview() {
	      return this.$root.form.editMode || window.location.pathname.indexOf('/sites/site/') === 0;
	    },
	    isAutoSelectionOn() {
	      var _this$field, _this$field$options, _this$field$options$s;
	      return Boolean((_this$field = this.field) == null ? void 0 : (_this$field$options = _this$field.options) == null ? void 0 : (_this$field$options$s = _this$field$options.settingsData) == null ? void 0 : _this$field$options$s.isAutoSelectionOn);
	    },
	    settingsData() {
	      var _this$field2, _this$field2$options, _this$field3, _this$field3$options, _this$field3$options$, _this$field4, _this$field4$options, _this$field4$options$;
	      if (this.isPreview && Array.isArray((_this$field2 = this.field) == null ? void 0 : (_this$field2$options = _this$field2.options) == null ? void 0 : _this$field2$options.settingsData)) {
	        return {
	          label: this.loc('BOOKING_CRM_FORMS_DEFAULT_RESOURCE_FIELD_LABEL'),
	          textHeader: this.loc('BOOKING_CRM_FORMS_DEFAULT_RESOURCE_FIELD_PLACEHOLDER'),
	          hint: this.loc('BOOKING_CRM_FORMS_DEFAULT_RESOURCE_FIELD_HINT'),
	          isVisibleHint: true
	        };
	      }
	      return this.isAutoSelectionOn ? (_this$field3 = this.field) == null ? void 0 : (_this$field3$options = _this$field3.options) == null ? void 0 : (_this$field3$options$ = _this$field3$options.settingsData) == null ? void 0 : _this$field3$options$.autoSelection : (_this$field4 = this.field) == null ? void 0 : (_this$field4$options = _this$field4.options) == null ? void 0 : (_this$field4$options$ = _this$field4$options.settingsData) == null ? void 0 : _this$field4$options$.default;
	    },
	    hasSlotsAllAvailableResources() {
	      var _this$settingsData;
	      return !this.isAutoSelectionOn && ((_this$settingsData = this.settingsData) == null ? void 0 : _this$settingsData.hasSlotsAllAvailableResources);
	    },
	    fetching() {
	      return this.fetchingResources || this.fetchingOccupancy || this.fetchingAutoSelectionResource;
	    },
	    resource() {
	      if (!this.form.resourceId) {
	        return null;
	      }
	      return this.resources.find(resource => resource.id === this.form.resourceId) || null;
	    },
	    realResources() {
	      return this.hasSlotsAllAvailableResources ? this.resources.filter(({
	        id
	      }) => id !== AllResource.id) : this.resources;
	    },
	    value() {
	      if (!this.form.slot || !this.form.resourceId) {
	        return null;
	      }
	      return {
	        resourcesIds: [this.form.resourceId],
	        dateFromTs: this.form.slot.fromTs / 1000,
	        dateToTs: this.form.slot.toTs / 1000,
	        timezone: this.timezone
	      };
	    },
	    resourcesIds() {
	      var _this$settingsData2;
	      return ((_this$settingsData2 = this.settingsData) == null ? void 0 : _this$settingsData2.resourceIds) || [];
	    },
	    errorMessage() {
	      return this.field.messages.get('fieldErrorRequired');
	    },
	    hasErrors() {
	      return this.field.validated && !this.field.focused && !this.field.valid();
	    },
	    hasTitleOnlyInCalendar() {
	      return this.form.date && !this.visibleCalendar && this.form.resourceId && this.isAutoSelectionOn;
	    },
	    showedCalendarBlock() {
	      return this.form.resourceId && !this.form.date || this.form.date !== null || this.visibleCalendar;
	    },
	    showedSlotsBlock() {
	      return !this.isPreview && this.hasSlotsAllAvailableResources && this.form.resourceId === AllResource.id && this.resources.length > 0 && this.form.date !== null;
	    },
	    showedTimeSelectorBlock() {
	      return !this.isPreview && this.form.resourceId > 0 && this.realResources.length > 0 && this.form.date !== null;
	    }
	  },
	  created() {
	    this.occupancyManager = createOccupancy(this.runAction);
	    this.occupancyManager.setTimezone(this.timezone);
	    if (this.hasSlotsAllAvailableResources) {
	      this.form.resourceId = AllResource.id;
	      this.form.date = new Date();
	    }
	  },
	  async mounted() {
	    if (!this.isPreview) {
	      await this.loadData();
	    }
	    main_core.Event.bind(window, 'click', this.handleFocus, true);
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(window, 'click', this.handleFocus, true);
	  },
	  methods: {
	    async loadData() {
	      const promises = [this.loadResources()];
	      if (this.isAutoSelectionOn) {
	        promises.push(this.fetchAutoSelectionData());
	      }
	      await Promise.all(promises);
	    },
	    handleFocus({
	      target
	    }) {
	      this.field.focused = this.$el.contains(target);
	    },
	    onSelectorChange() {
	      this.updateValue();
	    },
	    updateValue() {
	      if (this.form.resourceId || this.form.slot) {
	        this.field.validated = false;
	      }
	      this.$emit('change', this.value);
	    },
	    async fetchAutoSelectionData() {
	      try {
	        var _this$settingsData3;
	        this.fetchingAutoSelectionResource = true;
	        const formData = new FormData();
	        formData.append('timezone', this.timezone);
	        const resourceIds = ((_this$settingsData3 = this.settingsData) == null ? void 0 : _this$settingsData3.resourceIds) || [];
	        resourceIds.forEach(resourceId => {
	          formData.append('resourceIds[]', resourceId);
	        });
	        const response = await this.runAction('booking.api_v1.CrmForm.getAutoSelectionData', {
	          data: formData
	        });
	        if (main_core.Type.isPlainObject(response == null ? void 0 : response.data)) {
	          this.form.resourceId = response.data.resourceId || 0;
	          this.form.date = response.data.date ? new Date(response.data.date) : null;
	        }
	      } catch (error) {
	        console.error('RunAction getAutoSelectionData error', error);
	      } finally {
	        this.fetchingAutoSelectionResource = false;
	      }
	    },
	    async loadResources() {
	      try {
	        this.fetchingResources = true;
	        const response = await this.runAction('booking.api_v1.CrmForm.getResources', {
	          data: {
	            ids: this.resourcesIds
	          }
	        });
	        this.setResources(mapDtoToResource(response.data || []));
	        if (this.occupancyManager instanceof Occupancy) {
	          this.occupancyManager.setResources(this.resources);
	        }
	      } catch (error) {
	        console.error('Load resource error', error);
	      } finally {
	        this.fetchingResources = false;
	      }
	    },
	    changeDate() {
	      if (this.isPreview) {
	        return;
	      }
	      this.visibleCalendar = true;
	    },
	    async setResource(resourceId) {
	      this.form.resourceId = resourceId;
	      this.form.slot = null;
	    },
	    setResources(resources) {
	      var _this$settingsData4;
	      const resourcesIds = ((_this$settingsData4 = this.settingsData) == null ? void 0 : _this$settingsData4.resourceIds) || [];
	      const artificialResources = [];
	      if (this.hasSlotsAllAvailableResources) {
	        artificialResources.push({
	          ...AllResource,
	          name: this.loc('BOOKING_CRM_FORMS_ALL_RESOURCES_LABEL')
	        });
	      }
	      this.resources = [...artificialResources, ...resources.filter(({
	        id
	      }) => resourcesIds.includes(id))];
	    },
	    setDate(date) {
	      this.form.date = date;
	      this.form.slot = null;
	    },
	    setSlot(selectedSlot) {
	      this.form.slot = selectedSlot;
	      this.updateValue();
	    },
	    updateForm(formPatch) {
	      this.form = {
	        ...this.form,
	        ...formPatch
	      };
	      this.updateValue();
	    }
	  },
	  template: `
		<div class="booking-crm-forms-field-container">
			<ResourceSelectBlock
				:resourceId="form.resourceId"
				:resources="resources"
				:settingsData="settingsData"
				:errorMessage="errorMessage"
				:hasErrors="hasErrors && form.resourceId <= 0"
				:fetching="fetchingAutoSelectionResource || fetchingResources || fetchingOccupancy"
				:dependencies="dependencies"
				@update:resourceId="setResource"
			/>
			<CalendarBlock
				v-if="!isPreview && showedCalendarBlock"
				:resource="resource"
				:date="form.date"
				:titleOnly="hasTitleOnlyInCalendar"
				:hasError="hasErrors && form.slot === null"
				:errorMessage="errorMessage"
				@updateDate="setDate"
			/>
			<AvailableSlotsBlock
				v-if="showedSlotsBlock"
				:date="form.date"
				:resources="realResources"
				:runAction="runAction"
				:timezone="timezone"
				@update:form="updateForm"
			/>
			<TimeSelectorBlock
				v-if="showedTimeSelectorBlock"
				:slot="form.slot"
				:resource="resource"
				:resources="realResources"
				:date="form.date"
				:runAction="runAction"
				:fetching="fetchingOccupancy"
				:timezone="timezone"
				:showChangeDateButton="hasTitleOnlyInCalendar"
				@update:fetching="fetchingOccupancy = $event"
				@update:slot="setSlot"
				@showCalendar="visibleCalendar = true"
			/>
		</div>
	`
	};

	exports.Field = Field;

}((this.BX.Booking.CrmForms = this.BX.Booking.CrmForms || {}),BX,BX.Booking.Const,BX.Booking.Lib,BX.Booking.Lib,BX.UI.DatePicker,BX.Main,BX,BX.Booking.Component.Mixin));
//# sourceMappingURL=field.bundle.js.map
