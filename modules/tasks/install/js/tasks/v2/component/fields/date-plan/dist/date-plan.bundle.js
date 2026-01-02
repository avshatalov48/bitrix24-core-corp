/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,tasks_v2_component_elements_fieldList,tasks_v2_component_elements_hoverPill,tasks_v2_component_elements_fieldAdd,ui_system_chip_vue,ui_iconSet_api_core,main_core,main_date,ui_datePicker,ui_vue3_vuex,ui_vue3_components_button,ui_system_input_vue,ui_vue3_components_menu,ui_iconSet_api_vue,ui_iconSet_outline,tasks_v2_const,tasks_v2_component_elements_bottomSheet,tasks_v2_lib_fieldHighlighter,tasks_v2_lib_calendar,tasks_v2_lib_timezone,tasks_v2_provider_service_taskService,ui_system_typography_vue,ui_switcher,ui_vue3_components_switcher,ui_vue3_directives_hint,tasks_v2_component_elements_hint) {
	'use strict';

	const DatePlanDate = {
	  components: {
	    HoverPill: tasks_v2_component_elements_hoverPill.HoverPill
	  },
	  props: {
	    dateTs: {
	      type: Number,
	      required: true
	    },
	    readonly: {
	      type: Boolean,
	      default: false
	    }
	  },
	  computed: {
	    dateFormatted() {
	      return tasks_v2_lib_calendar.calendar.formatDateTime(this.dateTs, {
	        forceYear: true
	      });
	    }
	  },
	  template: `
		<div class="tasks-field-date-plan-date-container">
			<HoverPill :readonly="readonly">
				<div class="tasks-field-date-plan-date">{{ dateFormatted }}</div>
			</HoverPill>
		</div>
	`
	};

	const DatePlanContent = {
	  components: {
	    HoverPill: tasks_v2_component_elements_hoverPill.HoverPill,
	    FieldAdd: tasks_v2_component_elements_fieldAdd.FieldAdd
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    tooltip() {
	      return () => tasks_v2_component_elements_hint.tooltip({
	        text: this.loc('TASKS_V2_DATE_PLAN_NO_SUBTASKS_HINT'),
	        popupOptions: {
	          offsetLeft: this.$refs.hint.offsetWidth / 2
	        },
	        timeout: 100
	      });
	    }
	  },
	  template: `
		<HoverPill v-if="task.matchesSubTasksTime" :readonly="true">
			<div class="tasks-field-date-plan-content">
				<div>{{ loc('TASKS_V2_DATE_PLAN_MATCH_SUBTASKS_TIME_STATE') }}</div>
				<div v-hint="tooltip" class="tasks-hint-badge" ref="hint">?</div>
			</div>
		</HoverPill>
		<FieldAdd v-else :icon="Outline.PLANNING"/>
	`
	};

	const datePlanMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.DatePlan,
	  title: main_core.Loc.getMessage('TASKS_V2_DATE_PLAN_TITLE')
	});

	// @vue/component
	const DatePlan = {
	  components: {
	    FieldList: tasks_v2_component_elements_fieldList.FieldList
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  emits: ['open'],
	  setup() {
	    return {
	      datePlanMeta
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    wasFilled() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, datePlanMeta.id);
	    },
	    fields() {
	      const isEmpty = !this.task.startPlanTs && !this.task.endPlanTs;
	      if (isEmpty && (this.wasFilled || this.task.matchesSubTasksTime)) {
	        return [{
	          title: datePlanMeta.title,
	          component: DatePlanContent,
	          props: {
	            taskId: this.taskId
	          }
	        }];
	      }
	      return [{
	        title: this.loc('TASKS_V2_DATE_PLAN_FIELD_START'),
	        component: DatePlanDate,
	        props: {
	          dateTs: this.task.startPlanTs,
	          readonly: this.readonly
	        }
	      }, {
	        title: this.loc('TASKS_V2_DATE_PLAN_FIELD_END'),
	        component: DatePlanDate,
	        props: {
	          dateTs: this.task.endPlanTs,
	          readonly: this.readonly
	        }
	      }].filter(({
	        props: {
	          dateTs
	        }
	      }) => dateTs);
	    },
	    readonly() {
	      return !this.task.rights.datePlan;
	    }
	  },
	  methods: {
	    handleClick() {
	      if (!this.readonly) {
	        this.$emit('open');
	      }
	    }
	  },
	  template: `
		<div
			class="tasks-field-date-plan"
			:class="{ '--readonly': readonly }"
			:data-task-id="taskId"
			:data-task-field-id="datePlanMeta.id"
			:data-task-plan-start="task.startPlanTs"
			:data-task-plan-end="task.endPlanTs"
			@click="handleClick"
		>
			<FieldList :fields="fields"/>
		</div>
	`
	};

	// @vue/component
	const DatePlanChip = {
	  components: {
	    Chip: ui_system_chip_vue.Chip
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  emits: ['open'],
	  setup() {
	    return {
	      Outline: ui_iconSet_api_core.Outline,
	      datePlanMeta
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    design() {
	      return this.isSelected ? ui_system_chip_vue.ChipDesign.ShadowAccent : ui_system_chip_vue.ChipDesign.ShadowNoAccent;
	    },
	    isSelected() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, datePlanMeta.id);
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    }
	  },
	  methods: {
	    handleClick() {
	      if (this.isSelected) {
	        this.highlightField();
	        return;
	      }
	      this.$emit('open');
	    },
	    highlightField() {
	      void tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).highlight(datePlanMeta.id);
	    }
	  },
	  template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:icon="Outline.PLANNING"
			:text="loc('TASKS_V2_DATE_PLAN_TITLE_CHIP')"
			:data-task-id="taskId"
			:data-task-chip-id="datePlanMeta.id"
			:data-task-plan-start="task.startPlanTs"
			:data-task-plan-end="task.endPlanTs"
			@click="handleClick"
		/>
	`
	};

	const DatePlanSwitcher = {
	  components: {
	    TextSm: ui_system_typography_vue.TextSm,
	    Switcher: ui_vue3_components_switcher.Switcher
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    modelValue: {
	      type: Boolean,
	      required: true
	    },
	    text: {
	      type: String,
	      required: true
	    },
	    hint: {
	      type: String,
	      default: ''
	    }
	  },
	  computed: {
	    switcherOptions() {
	      return {
	        size: ui_switcher.SwitcherSize.extraSmall,
	        useAirDesign: true
	      };
	    },
	    tooltip() {
	      return () => tasks_v2_component_elements_hint.tooltip({
	        text: this.hint,
	        popupOptions: {
	          offsetLeft: this.$refs.hint.offsetWidth / 2
	        },
	        timeout: 100
	      });
	    }
	  },
	  template: `
		<div class="tasks-field-date-plan-switcher" @click="$emit('update:modelValue', !modelValue)">
			<Switcher :isChecked="modelValue" :options="switcherOptions"/>
			<TextSm>{{ text }}</TextSm>
			<div v-if="hint" v-hint="tooltip" class="tasks-hint-badge" ref="hint" @click.capture.stop>?</div>
		</div>
	`
	};

	const MAX_INT = 2 ** 31;

	// @vue/component
	const DatePlanSheet = {
	  components: {
	    BottomSheet: tasks_v2_component_elements_bottomSheet.BottomSheet,
	    BIcon: ui_iconSet_api_vue.BIcon,
	    UiButton: ui_vue3_components_button.Button,
	    HeadlineMd: ui_system_typography_vue.HeadlineMd,
	    TextSm: ui_system_typography_vue.TextSm,
	    BInput: ui_system_input_vue.BInput,
	    BMenu: ui_vue3_components_menu.BMenu,
	    DatePlanSwitcher
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    isShown: {
	      type: Boolean,
	      required: true
	    },
	    getBindElement: {
	      type: Function,
	      default: null
	    },
	    getTargetContainer: {
	      type: Function,
	      default: null
	    }
	  },
	  emits: ['close'],
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline,
	      InputDesign: ui_system_input_vue.InputDesign,
	      ButtonSize: ui_vue3_components_button.ButtonSize,
	      ButtonColor: ui_vue3_components_button.ButtonColor,
	      wasEmpty: false
	    };
	  },
	  data() {
	    return {
	      isEndPicker: false,
	      isEndDuration: false,
	      isPickerShown: false,
	      isMenuShown: false,
	      unitId: tasks_v2_const.DurationUnit.Days,
	      durationValue: '',
	      startTsTemp: null,
	      endTsTemp: null,
	      matchesWorkTimeTemp: null
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      titleFieldOffsetHeight: `${tasks_v2_const.Model.Interface}/titleFieldOffsetHeight`
	    }),
	    isEdit() {
	      return Number.isInteger(this.taskId) && this.taskId > 0;
	    },
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    // do not remove startPlanTs, otherwise startTs is not updated
	    startPlanTs() {
	      return this.task.startPlanTs;
	    },
	    endPlanTs() {
	      return this.task.endPlanTs;
	    },
	    startTs() {
	      var _this$startTsTemp;
	      return (_this$startTsTemp = this.startTsTemp) != null ? _this$startTsTemp : this.startPlanTs;
	    },
	    endTs() {
	      var _this$endTsTemp;
	      return (_this$endTsTemp = this.endTsTemp) != null ? _this$endTsTemp : this.endPlanTs;
	    },
	    wasFilled() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, datePlanMeta.id);
	    },
	    allowsChangeDatePlan: {
	      get() {
	        var _this$task$allowsChan;
	        return (_this$task$allowsChan = this.task.allowsChangeDatePlan) != null ? _this$task$allowsChan : false;
	      },
	      set(allowsChangeDatePlan) {
	        void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	          allowsChangeDatePlan
	        });
	      }
	    },
	    matchesWorkTime: {
	      get() {
	        var _ref, _this$matchesWorkTime;
	        return (_ref = (_this$matchesWorkTime = this.matchesWorkTimeTemp) != null ? _this$matchesWorkTime : this.task.matchesWorkTime) != null ? _ref : false;
	      },
	      set(matchesWorkTime) {
	        this.matchesWorkTimeTemp = matchesWorkTime;
	        this.update();
	        if (!this.maxDurationReached) {
	          void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	            matchesWorkTime
	          });
	        }
	      }
	    },
	    matchesSubTasksTime: {
	      get() {
	        var _this$task$matchesSub;
	        return (_this$task$matchesSub = this.task.matchesSubTasksTime) != null ? _this$task$matchesSub : false;
	      },
	      set(matchesSubTasksTime) {
	        void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	          matchesSubTasksTime
	        });
	      }
	    },
	    maxDurationReached() {
	      return this.endTs && this.startTs && Math.floor((this.endTs - this.startTs) / 1000) >= MAX_INT;
	    },
	    menuOptions() {
	      return () => ({
	        bindElement: this.$refs.unit.$el,
	        items: [tasks_v2_const.DurationUnit.Days, tasks_v2_const.DurationUnit.Hours, tasks_v2_const.DurationUnit.Minutes].map(unitId => {
	          const unit = this.units[unitId];
	          return {
	            title: unit.title,
	            isSelected: unitId === this.unitId,
	            onClick: () => {
	              this.unitId = unitId;
	              this.update();
	            }
	          };
	        })
	      });
	    },
	    units() {
	      const unitDurations = main_date.DurationFormat.getUnitDurations();
	      return {
	        [tasks_v2_const.DurationUnit.Days]: {
	          duration: this.matchesWorkTime ? tasks_v2_lib_calendar.calendar.workdayDuration : unitDurations.d,
	          title: this.loc('TASKS_V2_DATE_PLAN_DAYS')
	        },
	        [tasks_v2_const.DurationUnit.Hours]: {
	          duration: unitDurations.H,
	          title: this.loc('TASKS_V2_DATE_PLAN_HOURS')
	        },
	        [tasks_v2_const.DurationUnit.Minutes]: {
	          duration: unitDurations.i,
	          title: this.loc('TASKS_V2_DATE_PLAN_MINUTES')
	        }
	      };
	    },
	    inputDesign() {
	      return this.matchesSubTasksTime ? ui_system_input_vue.InputDesign.Disabled : ui_system_input_vue.InputDesign.Grey;
	    }
	  },
	  watch: {
	    isShown(isShown) {
	      if (isShown) {
	        this.clearTemps();
	        this.updateDuration(this.task.startPlanTs, this.task.endPlanTs);
	        this.wasEmpty = !this.wasFilled;
	      }
	    },
	    titleFieldOffsetHeight() {
	      var _this$$refs$bottomShe;
	      (_this$$refs$bottomShe = this.$refs.bottomSheet) == null ? void 0 : _this$$refs$bottomShe.adjustPosition();
	    }
	  },
	  mounted() {
	    this.updateDuration(this.task.startPlanTs, this.task.endPlanTs);
	  },
	  methods: {
	    clearStart() {
	      this.clearTemps();
	      this.durationValue = '';
	      this.updateTaskPlan(null, this.task.endPlanTs);
	    },
	    clearEnd() {
	      this.clearTemps();
	      this.durationValue = '';
	      this.updateTaskPlan(this.task.startPlanTs, null);
	    },
	    handleDateClick({
	      target
	    }, {
	      isEnd
	    }) {
	      this.clearTemps();
	      this.updateDuration(this.task.startPlanTs, this.task.endPlanTs);
	      this.isEndPicker = isEnd;
	      const datePicker = this.getDatePicker();
	      datePicker.setTargetNode(target);
	      datePicker.show();
	      if (this.isEndPicker && this.task.endPlanTs) {
	        datePicker.setFocusDate(this.task.endPlanTs + tasks_v2_lib_timezone.timezone.getOffset(this.task.endPlanTs));
	      }
	    },
	    getDatePicker() {
	      var _this$handlePickerCha, _this$datePicker;
	      (_this$handlePickerCha = this.handlePickerChangedDebounced) != null ? _this$handlePickerCha : this.handlePickerChangedDebounced = main_core.Runtime.debounce(this.handlePickerChanged, 10, this);
	      (_this$datePicker = this.datePicker) != null ? _this$datePicker : this.datePicker = new ui_datePicker.DatePicker({
	        enableTime: true,
	        selectionMode: 'range',
	        defaultTime: tasks_v2_lib_calendar.calendar.defaultTime,
	        events: {
	          [ui_datePicker.DatePickerEvent.SELECT]: this.handlePickerChangedDebounced,
	          [ui_datePicker.DatePickerEvent.DESELECT]: this.handlePickerChangedDebounced,
	          onShow: () => {
	            this.isPickerShown = true;
	          },
	          onHide: () => {
	            this.isPickerShown = false;
	          }
	        }
	      });
	      return this.datePicker;
	    },
	    handlePickerChanged() {
	      let startPlanTs = this.preparePickerTimestamp(this.datePicker.getRangeStart());
	      let endPlanTs = this.preparePickerTimestamp(this.datePicker.getRangeEnd());
	      if (this.isEndPicker && !endPlanTs && !this.task.startPlanTs) {
	        [startPlanTs, endPlanTs] = [null, startPlanTs];
	      }
	      if (startPlanTs && !this.task.startPlanTs) {
	        startPlanTs = tasks_v2_lib_calendar.calendar.setHours(startPlanTs, tasks_v2_lib_calendar.calendar.workdayStart.H, tasks_v2_lib_calendar.calendar.workdayStart.M);
	      }
	      this.updateDuration(startPlanTs, endPlanTs);
	    },
	    updateDuration(startTs, endTs) {
	      const [startPlanTs, endPlanTs] = this.prepareRange(startTs, endTs);
	      if (!startPlanTs || !endPlanTs) {
	        this.durationValue = '';
	        this.updateTaskPlan(startPlanTs, endPlanTs);
	        return;
	      }
	      const [durationValue, unitId] = this.calculateDurationValue(startPlanTs, endPlanTs, this.matchesWorkTime);
	      this.durationValue = String(durationValue);
	      this.unitId = unitId;
	      this.updateTaskPlan(startPlanTs, endPlanTs);
	    },
	    update() {
	      const [startPlanTs, endPlanTs] = this.prepareRange(this.task.startPlanTs, this.task.endPlanTs);
	      if (startPlanTs && endPlanTs) {
	        this.updateRangeFromDuration();
	      } else {
	        this.updateTaskPlan(startPlanTs, endPlanTs);
	      }
	    },
	    updateRangeFromDuration() {
	      let [startPlanTs, endPlanTs] = this.prepareRange(this.task.startPlanTs, this.task.endPlanTs);
	      if (!startPlanTs && !endPlanTs) {
	        return;
	      }
	      if (this.isEndDuration) {
	        startPlanTs = null;
	      }
	      this.durationValue = this.durationValue.replaceAll(/\D/g, '');
	      const duration = this.durationValue * this.units[this.unitId].duration;
	      if (this.matchesWorkTime) {
	        startPlanTs = tasks_v2_lib_calendar.calendar.calculateStartTs(startPlanTs, endPlanTs, duration);
	        endPlanTs = tasks_v2_lib_calendar.calendar.calculateEndTs(startPlanTs, endPlanTs, duration);
	      } else {
	        var _startPlanTs;
	        (_startPlanTs = startPlanTs) != null ? _startPlanTs : startPlanTs = endPlanTs - duration;
	        endPlanTs = startPlanTs + duration;
	      }
	      this.updateTaskPlan(startPlanTs, endPlanTs);
	    },
	    calculateDurationValue(startTs, endTs, matchWorkTime) {
	      const duration = matchWorkTime ? tasks_v2_lib_calendar.calendar.calculateDuration(startTs, endTs) : endTs - startTs;
	      const minutes = duration / this.units[tasks_v2_const.DurationUnit.Minutes].duration;
	      const hours = duration / this.units[tasks_v2_const.DurationUnit.Hours].duration;
	      const days = duration / this.units[tasks_v2_const.DurationUnit.Days].duration;
	      return {
	        [true]: [minutes, tasks_v2_const.DurationUnit.Minutes],
	        [Number.isInteger(hours)]: [hours, tasks_v2_const.DurationUnit.Hours],
	        [Number.isInteger(days)]: [days, tasks_v2_const.DurationUnit.Days]
	      }.true;
	    },
	    updateTaskPlan(startPlanTs, endPlanTs) {
	      this.startTsTemp = startPlanTs;
	      this.endTsTemp = endPlanTs;
	      if (this.maxDurationReached) {
	        return;
	      }
	      this.clearTemps();
	      const datePicker = this.getDatePicker();
	      const options = {
	        emitEvents: false
	      };
	      if (startPlanTs || endPlanTs) {
	        const rangeStart = startPlanTs + tasks_v2_lib_timezone.timezone.getOffset(startPlanTs);
	        const rangeEnd = endPlanTs ? endPlanTs + tasks_v2_lib_timezone.timezone.getOffset(endPlanTs) : null;
	        if (startPlanTs) {
	          datePicker.selectRange(rangeStart, rangeEnd, options);
	        } else {
	          datePicker.selectRange(rangeEnd, null, options);
	        }
	      } else {
	        datePicker.deselectAll(options);
	      }
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        startPlanTs,
	        endPlanTs
	      });
	    },
	    clearTemps() {
	      this.startTsTemp = null;
	      this.endTsTemp = null;
	      this.matchesWorkTimeTemp = null;
	    },
	    prepareRange(startTs, endTs) {
	      if (this.matchesWorkTime) {
	        return [tasks_v2_lib_calendar.calendar.clampWorkDateTime(startTs), tasks_v2_lib_calendar.calendar.clampWorkDateTime(endTs)];
	      }
	      return [startTs, endTs];
	    },
	    preparePickerTimestamp(date) {
	      if (!date) {
	        return null;
	      }
	      const dateTs = tasks_v2_lib_calendar.calendar.createDateFromUtc(date).getTime();
	      return dateTs - tasks_v2_lib_timezone.timezone.getOffset(dateTs);
	    },
	    formatDate(timestamp) {
	      return tasks_v2_lib_calendar.calendar.formatDateTime(timestamp, {
	        forceYear: true
	      });
	    },
	    close() {
	      if (this.wasEmpty && this.wasFilled) {
	        void tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).highlight(datePlanMeta.id);
	      }
	      if (this.task.matchesSubTasksTime && !this.isEdit) {
	        this.updateDuration(null, null);
	      }
	      this.$emit('close');
	    }
	  },
	  template: `
		<BottomSheet
			v-if="isShown"
			:getBindElement="getBindElement"
			:getTargetContainer="getTargetContainer"
			ref="bottomSheet"
		>
			<div class="tasks-field-date-plan-sheet">
				<div class="tasks-field-date-plan-header">
					<HeadlineMd>{{ loc('TASKS_V2_DATE_PLAN_TITLE_SHEET') }}</HeadlineMd>
					<BIcon class="tasks-field-date-plan-close" :name="Outline.CROSS_L" :hoverable="true" @click="close"/>
				</div>
				<TextSm class="tasks-field-date-plan-description">{{ loc('TASKS_V2_DATE_PLAN_DESCRIPTION') }}</TextSm>
				<div class="tasks-field-date-plan-fields">
					<BInput
						:modelValue="formatDate(startTs)"
						:label="loc('TASKS_V2_DATE_PLAN_START')"
						:design="inputDesign"
						:icon="Outline.CALENDAR_WITH_SLOTS"
						:withClear="Boolean(startTs)"
						:active="isPickerShown && !isEndPicker"
						@clear="clearStart"
						@click="handleDateClick($event, { isEnd: false })"
						@focus="$event.target.blur()"
					/>
					<BInput
						:modelValue="formatDate(endTs)"
						:label="loc('TASKS_V2_DATE_PLAN_END')"
						:design="inputDesign"
						:icon="Outline.CALENDAR_WITH_SLOTS"
						:withClear="Boolean(endTs)"
						:active="isPickerShown && isEndPicker"
						@clear="clearEnd"
						@click="handleDateClick($event, { isEnd: true })"
						@focus="$event.target.blur()"
					/>
					<div class="tasks-field-date-plan-duration">
						<BInput
							v-model="durationValue"
							:label="loc('TASKS_V2_DATE_PLAN_DURATION')"
							:design="inputDesign"
							:error="maxDurationReached ? ' ' : null"
							@input="updateRangeFromDuration"
							@focus="isEndDuration = !task.startPlanTs"
							@blur="isEndDuration = false"
						/>
						<BInput
							:modelValue="units[unitId].title"
							:design="inputDesign"
							:dropdown="true"
							:active="isMenuShown"
							ref="unit"
							@click="isMenuShown = true"
							@focus="$event.target.blur()"
						/>
					</div>
				</div>
				<div class="tasks-field-date-plan-switchers">
					<DatePlanSwitcher
						v-if="task.rights.edit"
						v-model="allowsChangeDatePlan"
						:text="loc('TASKS_V2_DATE_PLAN_ALLOW_CHANGE')"
					/>
					<DatePlanSwitcher
						v-model="matchesWorkTime"
						:text="loc('TASKS_V2_DATE_PLAN_MATCH_WORK_TIME')"
						:hint="loc('TASKS_V2_DATE_PLAN_MATCH_WORK_TIME_HINT')"
					/>
					<DatePlanSwitcher
						v-model="matchesSubTasksTime"
						:text="loc('TASKS_V2_DATE_PLAN_MATCH_SUBTASKS_TIME')"
						:hint="loc('TASKS_V2_DATE_PLAN_MATCH_SUBTASKS_TIME_HINT')"
					/>
				</div>
				<div class="tasks-field-date-plan-footer">
					<UiButton
						:text="loc('TASKS_V2_DATE_PLAN_BUTTON_SAVE')"
						:size="ButtonSize.MEDIUM"
						:color="ButtonColor.PRIMARY"
						@click="close"
					/>
				</div>
			</div>
		</BottomSheet>
		<BMenu v-if="isMenuShown" :options="menuOptions()" @close="isMenuShown = false"/>
	`
	};

	exports.DatePlan = DatePlan;
	exports.DatePlanChip = DatePlanChip;
	exports.DatePlanSheet = DatePlanSheet;
	exports.datePlanMeta = datePlanMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Elements,BX.UI.System.Chip.Vue,BX.UI.IconSet,BX,BX.Main,BX.UI.DatePicker,BX.Vue3.Vuex,BX.Vue3.Components,BX.UI.System.Input.Vue,BX.UI.Vue3.Components,BX.UI.IconSet,BX,BX.Tasks.V2.Const,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib,BX.Tasks.V2.Lib,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX.UI.System.Typography.Vue,BX.UI,BX.UI.Vue3.Components,BX.Vue3.Directives,BX.Tasks.V2.Component.Elements));
//# sourceMappingURL=date-plan.bundle.js.map
