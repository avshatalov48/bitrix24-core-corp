this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_popup,ui_buttons,ui_notification,main_date,calendar_planner,crm_datetime,main_core,crm_timeline_tools,crm_activity_settingsPopup,crm_entitySelector,ui_vue3) {
	'use strict';

	const Section = {
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    toggleTitle: {
	      type: String,
	      default: ''
	    },
	    toggleEnabled: {
	      type: Boolean,
	      default: true
	    },
	    toggleVisible: {
	      type: Boolean,
	      default: true
	    }
	  },
	  watch: {
	    enabled() {
	      this.$emit('onToggle', {
	        id: this.id,
	        isActive: this.enabled
	      });
	    }
	  },
	  data() {
	    return {
	      enabled: this.toggleEnabled
	    };
	  },
	  template: `
		<section>
			<div class="ui-form-row" v-if="toggleVisible">
				<label for class="ui-ctl ui-ctl-checkbox" @click="enabled = !enabled">
					<input type="checkbox" class="ui-ctl-element" v-model="enabled">
					<div class="ui-ctl-label-text">{{ toggleTitle }}</div>
				</label>
			</div>
			<div class="ui-form-row" v-else>
				<label>
					<div class="ui-ctl-label-text">{{ toggleTitle }}</div>
				</label>
			</div>
			<slot v-if="enabled"></slot>
		</section>
	`
	};

	const RecallButton = {
	  props: {
	    id: String,
	    active: Boolean,
	    title: String
	  },
	  methods: {
	    onClick: function () {
	      this.$emit('onClick', this.id);
	    }
	  },
	  template: `
		<button @click="onClick" :class="['ui-btn ui-btn-secondary ui-btn-md ui-btn-no-caps', { active }]">
			{{this.title}}
		</button>
	`
	};

	const Recall = {
	  today: {
	    id: 'today',
	    days: 0
	  },
	  tomorrow: {
	    id: 'tomorrow',
	    days: 1
	  },
	  after2days: {
	    id: 'after2days',
	    days: 2
	  },
	  after3days: {
	    id: 'after3days',
	    days: 3
	  }
	};
	const DurationPeriods = {
	  minute: {
	    id: 'minute',
	    seconds: 60
	  },
	  hour: {
	    id: 'hour',
	    seconds: 60 * 60
	  },
	  day: {
	    id: 'day',
	    seconds: 60 * 60 * 24
	  }
	};
	const Calendar = {
	  components: {
	    RecallButton
	  },
	  props: {
	    params: {
	      type: Object,
	      default: {}
	    }
	  },
	  data() {
	    const timestamp = this.params.from || crm_datetime.Factory.getUserNow().getTime() / 1000;
	    const from = Math.round(timestamp / 60) * 60; // round timestamp to minutes

	    let duration = 1;
	    let durationPeriodId = DurationPeriods.hour.id;
	    if (this.params.duration) {
	      durationPeriodId = this.getPeriodIdBySeconds(this.params.duration);
	      duration = this.params.duration / DurationPeriods[durationPeriodId].seconds;
	    }
	    const to = from + duration * DurationPeriods[durationPeriodId].seconds;
	    return {
	      id: this.getId(),
	      from,
	      to,
	      duration,
	      durationPeriodId,
	      timeFromClockInstance: null,
	      timeToClockInstance: null,
	      plannerInstance: null
	    };
	  },
	  computed: {
	    DurationPeriods: () => DurationPeriods,
	    Recall: () => Recall,
	    fromDateFormatted: {
	      get() {
	        return main_date.Date.format(BX.Crm.DateTime.Dictionary.Format.SHORT_DATE_FORMAT, this.from);
	      },
	      set(value) {
	        const date = main_date.Date.parse(value);
	        const currentDate = this.createDateInstance(this.from);
	        date.setHours(currentDate.getHours(), currentDate.getMinutes(), 0, 0);
	        this.from = date.getTime() / 1000;
	        this.to = this.from + this.duration;
	      }
	    },
	    fromTimeFormatted: {
	      get() {
	        return this.getFormattedTime('from');
	      },
	      set(newTime) {
	        const date = this.getDateInstanceWithTime(this.from, newTime);
	        this.from = date.getTime() / 1000;
	        this.timeFromClockInstance.closeWnd();
	      }
	    },
	    toDateFormatted: {
	      get() {
	        const toTime = this.from + this.duration * DurationPeriods[this.durationPeriodId].seconds;
	        return main_date.Date.format(BX.Crm.DateTime.Dictionary.Format.SHORT_DATE_FORMAT, toTime);
	      },
	      set(value) {
	        const date = main_date.Date.parse(value);
	        const toTime = this.from + this.duration * DurationPeriods[this.durationPeriodId].seconds;
	        const currentDate = crm_datetime.Factory.createFromTimestampInUserTimezone(toTime);
	        date.setHours(currentDate.getHours(), currentDate.getMinutes(), 0, 0);
	        this.calcDuration(date);
	      }
	    },
	    toTimeFormatted: {
	      get() {
	        const toTime = this.from + this.duration * DurationPeriods[this.durationPeriodId].seconds;
	        return main_date.Date.format(BX.Crm.DateTime.Dictionary.Format.SHORT_TIME_FORMAT, toTime);
	      },
	      set(newTime) {
	        const date = this.getDateInstanceWithTime(this.to, newTime);
	        this.calcDuration(date);
	        this.timeToClockInstance.closeWnd();
	      }
	    },
	    activeRecallId() {
	      const isDatesAreEqual = (date1, date2) => date1.getTime() === date2.getTime();
	      const fromDate = this.createDateInstance(this.from, true);
	      const date = this.createDateInstance(null, true);
	      if (isDatesAreEqual(fromDate, date)) {
	        return Recall.today.id;
	      }
	      const addDay = date => date.setDate(date.getDate() + 1);
	      addDay(date);
	      if (isDatesAreEqual(fromDate, date)) {
	        return Recall.tomorrow.id;
	      }
	      addDay(date);
	      if (isDatesAreEqual(fromDate, date)) {
	        return Recall.after2days.id;
	      }
	      addDay(date);
	      if (isDatesAreEqual(fromDate, date)) {
	        return Recall.after3days.id;
	      }
	      return null;
	    }
	  },
	  mounted() {
	    this.plannerInstance = new calendar_planner.Planner({
	      wrap: this.$refs.plannerContainer,
	      compactMode: true,
	      minWidth: 770,
	      minHeight: 104,
	      height: 104,
	      width: 770
	      //dayOfWeekMonthFormat: this.dayOfWeekMonthFormat
	    });

	    this.plannerInstance.subscribe('onDateChange', this.handlePlannerSelectorChanges.bind(this));
	    this.plannerInstance.show();
	    this.getAccessibilityForUsers();
	    this.onDataUpdate();
	  },
	  unmounted() {
	    this.emitSettingsChange(false);
	  },
	  watch: {
	    duration() {
	      this.onDataUpdate();
	    },
	    durationPeriodId() {
	      this.onDataUpdate();
	    },
	    from() {
	      this.onDataUpdate();
	    },
	    to() {
	      this.onDataUpdate();
	    }
	  },
	  methods: {
	    getId() {
	      return 'calendar';
	    },
	    getDateInstanceWithTime(timestamp, time) {
	      const timeArr = time.split(':');
	      const date = crm_datetime.Factory.createFromTimestampInUserTimezone(timestamp);
	      let hours = Number(timeArr[0]);
	      let minutes = timeArr[1];
	      const isAmPm = minutes.includes('am') || minutes.includes('pm');
	      if (isAmPm) {
	        if (minutes.includes('pm') && hours !== 12) {
	          hours += 12;
	        }
	        minutes = parseInt(minutes, 10);
	      }
	      date.setHours(hours, minutes, 0, 0);
	      return date;
	    },
	    calcDuration(date) {
	      const durationSeconds = date.getTime() / 1000 - this.from;
	      if (durationSeconds % DurationPeriods[this.durationPeriodId].seconds === 0) {
	        this.duration = durationSeconds / DurationPeriods[this.durationPeriodId].seconds;
	      } else {
	        this.duration = durationSeconds / DurationPeriods.minute.seconds;
	        this.durationPeriodId = DurationPeriods.minute.id;
	      }
	    },
	    getPeriodIdBySeconds(value) {
	      if (value % DurationPeriods.day.seconds === 0) {
	        return DurationPeriods.day.id;
	      }
	      if (value % DurationPeriods.hour.seconds === 0) {
	        return DurationPeriods.hour.id;
	      }
	      return DurationPeriods.minute.id;
	    },
	    handlePlannerSelectorChanges({
	      data: {
	        dateFrom,
	        dateTo
	      }
	    }) {
	      this.from = dateFrom.getTime() / 1000;
	      this.calcDuration(dateTo);
	    },
	    onDataUpdate() {
	      this.updatePlanner();
	      this.emitSettingsChange();
	    },
	    updatePlanner() {
	      const dateFrom = this.createDateInstance(this.from);
	      const durationSeconds = this.getDurationSeconds();
	      const dateTo = this.createDateInstance(this.from + durationSeconds);
	      this.plannerInstance.updateSelector(dateFrom, dateTo);
	    },
	    emitSettingsChange(active = true) {
	      const data = this.exportParams(active);
	      if (active && this.validateParams(data) || !active) {
	        this.$Bitrix.eventEmitter.emit(crm_activity_settingsPopup.Events.EVENT_SETTINGS_CHANGE, data);
	      } else {
	        this.$Bitrix.eventEmitter.emit(crm_activity_settingsPopup.Events.EVENT_SETTINGS_VALIDATION, {
	          isValid: false
	        });
	      }
	    },
	    getDurationSeconds() {
	      return this.duration * DurationPeriods[this.durationPeriodId].seconds;
	    },
	    getFormattedDate(id) {
	      return this.getFormattedValue(id, BX.Crm.DateTime.Dictionary.Format.SHORT_DATE_FORMAT);
	    },
	    getFormattedTime(id) {
	      return this.getFormattedValue(id, BX.Crm.DateTime.Dictionary.Format.SHORT_TIME_FORMAT);
	    },
	    getFormattedValue(id, format) {
	      const timestamp = id === 'from' ? this.from : this.to;
	      return main_date.Date.format(format, timestamp);
	    },
	    getSecondsFromStartOfDay(timestamp) {
	      const startOfDay = this.createDateInstance(timestamp, true);
	      return timestamp - startOfDay.getTime() / 1000;
	    },
	    getDurationPeriodTitle(periodId) {
	      const code = `CRM_SETTINGS_POPUP_CALENDAR_DURATION_${periodId.toUpperCase()}S`;
	      return this.$Bitrix.Loc.getMessage(code);
	    },
	    onDateFromClick() {
	      BX.calendar({
	        node: this.$refs.dateFrom,
	        field: this.$refs.dateFrom,
	        bTime: false
	      });
	    },
	    onDateFromChange(event) {
	      this.fromDateFormatted = event.target.value;
	    },
	    onDateToChange(event) {
	      this.toDateFormatted = event.target.value;
	    },
	    onDateToClick() {
	      BX.calendar({
	        node: this.$refs.dateTo,
	        field: this.$refs.dateTo,
	        bTime: false
	      });
	    },
	    onTimeFromClick() {
	      this.showClockSelector('timeFromClockInstance', this.from, this.$refs.timeFrom, 'fromTimeFormatted');
	    },
	    onTimeToClick() {
	      this.showClockSelector('timeToClockInstance', this.to, this.$refs.timeTo, 'toTimeFormatted');
	    },
	    showClockSelector(instanceName, startTimestamp, node, propertyName) {
	      if (!this[instanceName]) {
	        this[instanceName] = new BX.CClockSelector({
	          start_time: this.getSecondsFromStartOfDay(startTimestamp),
	          node,
	          callback: time => {
	            this[propertyName] = time;
	          }
	        });
	      }
	      this[instanceName].Show();
	    },
	    onDurationKeyUp() {
	      this.duration = this.duration.replace(/\D/g, '');
	    },
	    onRecallClick(id) {
	      const fromDate = this.createDateInstance(this.from);
	      const todayDate = this.createDateInstance(null, true);
	      todayDate.setHours(fromDate.getHours(), fromDate.getMinutes());
	      this.from = todayDate.setDate(todayDate.getDate() + Recall[id].days) / 1000;
	      const duration = this.duration * DurationPeriods[this.durationPeriodId].seconds;
	      this.to = this.from + duration;
	    },
	    createDateInstance(timestamp = null, startOfDay = false) {
	      if (!timestamp) {
	        timestamp = Date.now() / 1000;
	      }
	      const date = new Date(timestamp * 1000);
	      if (startOfDay) {
	        date.setHours(0, 0, 0, 0);
	      }
	      return date;
	    },
	    isActiveRecall(name) {
	      return name === this.activeRecallId;
	    },
	    exportParams(active = true) {
	      const from = this.from;
	      const duration = this.duration * DurationPeriods[this.durationPeriodId].seconds;
	      const to = this.from + duration;
	      return {
	        id: this.id,
	        from,
	        duration,
	        to,
	        active,
	        fromText: main_date.DateTimeFormat.format(crm_timeline_tools.DatetimeConverter.getSiteDateTimeFormat(), from),
	        toText: main_date.DateTimeFormat.format(crm_timeline_tools.DatetimeConverter.getSiteDateTimeFormat(), to)
	      };
	    },
	    validateParams(data) {
	      if (data.duration < 0) {
	        this.$refs.durationRegion.classList.add('ui-ctl-danger');
	        return false;
	      }
	      this.$refs.durationRegion.classList.remove('ui-ctl-danger');
	      return true;
	    },
	    getAccessibilityForUsers() {
	      const offset = 12 * 24 * 3600; //12 days
	      main_core.ajax.runAction('crm.activity.settings.calendar.getAccessibilityForUsers', {
	        data: {
	          from: this.from - offset,
	          to: this.from + offset
	          // @todo add currentEventId
	          //currentEventId:
	        }
	      }).then(({
	        data
	      }) => this.plannerInstance.update(data.entries, data.accessibility));
	    },
	    updateSettings(data) {
	      if (!data || !data.deadline) {
	        return;
	      }
	      this.from = data.deadline.getTime() / 1000;
	    }
	  },
	  template: `
		<div class="ui-form">
			<div class="ui-form-row-inline">
				<div class="ui-form-row">
					<div class="ui-form-label">
                		<div class="ui-ctl-label-text">
							{{ $Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_FROM_DATETIME') }}
						</div>
            		</div>
            		<div class="ui-form-content">
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-date">
							<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
							<input
								ref="dateFrom"
								type="text"
								class="ui-ctl-element"
								@click="onDateFromClick"
								@change="onDateFromChange"
								readonly
								v-model="fromDateFormatted"
							>
						</div>
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-time">
    						<div class="ui-ctl-after ui-ctl-icon-clock"></div>
							<input
								ref="timeFrom"
								type="text"
								class="ui-ctl-element"
								@click="onTimeFromClick"
								readonly
								v-model="fromTimeFormatted"
							>
						</div>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-label">
                		<div class="ui-ctl-label-text">
							{{ $Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_DURATION') }}
						</div>
            		</div>
            		<div class="ui-form-content">
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-date" ref="durationRegion">
							<input
								ref="durationValue"
								type="text" 
								v-model="duration"
								class="ui-ctl-element"
								@keyup="onDurationKeyUp"
							>
						</div>
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
    						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select
								ref="durationPeriod"
								class="ui-ctl-element"
								v-model="durationPeriodId"
							>
								<option
									v-for="duration in DurationPeriods"
									key="id"
									:selected="duration.id === durationPeriodId"
									:value="duration.id"
								>
									{{ getDurationPeriodTitle(duration.id) }}
								</option>
							</select>
						</div>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-label">
                		<div class="ui-ctl-label-text">
							{{ $Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_TO_DATETIME') }}
						</div>
            		</div>
            		<div class="ui-form-content">
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-date">
							<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
							<input
								ref="dateTo"
								type="text"
								class="ui-ctl-element"
								@click="onDateToClick"
								@change="onDateToChange"
								readonly
								v-model="toDateFormatted"
							>
						</div>
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-time">
    						<div class="ui-ctl-after ui-ctl-icon-clock"></div>
							<input
								ref="timeTo"
								type="text"
								class="ui-ctl-element"
								@click="onTimeToClick"
								readonly
								v-model="toTimeFormatted"
							>
						</div>
					</div>
				</div>
			</div>
			<div class="ui-form-row-inline crm-activity__settings_popup__calendar__recall-container">
				<RecallButton
					:id=Recall.today.id
					:active=isActiveRecall(Recall.today.id)
					@onClick="this.onRecallClick"
					:title="$Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_RECALL_TODAY')"
				/>
				<RecallButton
					:id=Recall.tomorrow.id
					:active=isActiveRecall(Recall.tomorrow.id)
					@onClick="this.onRecallClick"
					:title="$Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_RECALL_TOMORROW')"
				/>
				<RecallButton
					:id=Recall.after2days.id
					:active=isActiveRecall(Recall.after2days.id)
					@onClick="this.onRecallClick"
					:title="$Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_RECALL_AFTER_2_DAYS')"
				/>
				<RecallButton
					:id=Recall.after3days.id
					:active=isActiveRecall(Recall.after3days.id)
					@onClick="this.onRecallClick"
					:title="$Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_RECALL_AFTER_3_DAYS')"
				/>
			</div>
			<div ref="plannerContainer" class="crm-activity__settings_popup__calendar__planner-container"></div>
		</div>
	`
	};

	const Ping = {
	  props: {
	    params: {
	      type: Object,
	      default: {}
	    }
	  },
	  data() {
	    const selectedItems = this.params.selectedItems || [];
	    return {
	      id: this.getId(),
	      selectedItems
	    };
	  },
	  mounted() {
	    const preselectedItems = [];
	    this.selectedItems.forEach(item => preselectedItems.push(['timeline_ping', item]));
	    this.pingSelector = new crm_entitySelector.TagSelector({
	      textBoxWidth: '100%',
	      dialogOptions: {
	        height: 330,
	        dropdownMode: true,
	        showAvatars: false,
	        enableSearch: false,
	        preselectedItems: preselectedItems,
	        entities: [{
	          id: 'timeline_ping'
	        }],
	        events: {
	          'Item:onSelect': () => {
	            this.onChangeSelectorData();
	          },
	          'Item:onDeselect': () => {
	            this.onChangeSelectorData();
	          }
	        }
	      }
	    });
	    this.pingSelector.renderTo(this.$refs.pingSel);
	    this.emitSettingsChange();
	  },
	  unmounted() {
	    this.emitSettingsChange(false);
	  },
	  watch: {
	    selectedItems() {
	      this.emitSettingsChange();
	    }
	  },
	  methods: {
	    getId() {
	      return 'ping';
	    },
	    onChangeSelectorData() {
	      if (this.pingSelector) {
	        this.selectedItems = this.pingSelector.getDialog().getSelectedItems().map(item => item.id);
	      }
	    },
	    emitSettingsChange(active = true) {
	      this.$Bitrix.eventEmitter.emit(crm_activity_settingsPopup.Events.EVENT_SETTINGS_CHANGE, this.exportParams(active));
	    },
	    exportParams(active = true) {
	      this.pingSelector.getDialog().getSelectedItems().map(item => item.id);
	      return {
	        id: this.id,
	        selectedItems: this.selectedItems,
	        active
	      };
	    },
	    updateSettings(data) {}
	  },
	  template: `
		<div class="ui-form">
			<div ref="pingSel" class="crm-activity__settings_popup__ping-selector-container"></div>
		</div>
	`
	};

	const Wrapper = {
	  components: {
	    WrapperSection: Section,
	    SettingsPopupCalendar: Calendar,
	    SettingsPopupPing: Ping
	  },
	  props: {
	    onSettingsChangeCallback: {
	      type: Function,
	      required: true
	    },
	    onSettingsValidationCallback: {
	      type: Function
	    },
	    sections: {
	      type: Array,
	      required: true
	    }
	  },
	  data() {
	    return {
	      settings: new Map()
	    };
	  },
	  computed: {
	    activeSettingsIds() {
	      const result = {};
	      this.sections.forEach(section => {
	        result[section.id] = Boolean(section.active);
	      });
	      return result;
	    }
	  },
	  methods: {
	    getSectionTitle(id) {
	      id = id.toUpperCase();
	      const defaultCode = 'CRM_SETTINGS_POPUP_SECTION_SWITCH';
	      const code = `${defaultCode}_${id}`;
	      return this.$Bitrix.Loc.getMessage(code) || this.$Bitrix.Loc.getMessage(defaultCode);
	    },
	    getSectionToggle(showToggleSelector) {
	      if (main_core.Type.isBoolean(showToggleSelector)) {
	        return showToggleSelector;
	      }
	      return true;
	    },
	    prepareSettings() {
	      for (const sectionName in this.activeSettingsIds) {
	        if (!this.activeSettingsIds[sectionName]) {
	          continue;
	        }
	        const section = this.sections.find(section => section.id === sectionName);
	        if (!section) {
	          continue;
	        }
	        const data = {
	          id: section.id,
	          ...section.params
	        };
	        this.settings.set(sectionName, data);
	      }
	    },
	    onSettingsChange({
	      data
	    }) {
	      if (data.id) {
	        if (data.active) {
	          this.settings.set(data.id, data);
	        } else {
	          this.settings.delete(data.id);
	        }
	      }
	      if (this.onSettingsChangeCallback) {
	        this.onSettingsChangeCallback(this.exportParams());
	      }
	    },
	    onSettingsValidation({
	      data
	    }) {
	      if (this.onSettingsChangeCallback) {
	        this.onSettingsValidationCallback(data);
	      }
	    },
	    exportParams() {
	      const settings = Object.fromEntries(this.settings);
	      let result = {};
	      for (const id in this.activeSettingsIds) {
	        if (this.activeSettingsIds[id] && settings[id]) {
	          result[id] = settings[id];
	        }
	      }
	      return result;
	    },
	    onToggleSettingsSection({
	      id,
	      isActive
	    }) {
	      if (this.activeSettingsIds.hasOwnProperty(id)) {
	        this.activeSettingsIds[id] = isActive;
	      }
	    },
	    updateSettings(data) {
	      this.sections.forEach(section => {
	        if (this.$refs['section-' + section.id] && this.$refs['section-' + section.id][0]) {
	          this.$refs['section-' + section.id][0].updateSettings(data);
	        }
	      });
	    }
	  },
	  mounted() {
	    this.prepareSettings();
	    this.$Bitrix.eventEmitter.subscribe(crm_activity_settingsPopup.Events.EVENT_SETTINGS_CHANGE, this.onSettingsChange);
	    this.$Bitrix.eventEmitter.subscribe(crm_activity_settingsPopup.Events.EVENT_SETTINGS_VALIDATION, this.onSettingsValidation);
	  },
	  beforeUnmount() {
	    this.$Bitrix.eventEmitter.unsubscribe(crm_activity_settingsPopup.Events.EVENT_SETTINGS_CHANGE, this.onSettingsChange);
	    this.$Bitrix.eventEmitter.unsubscribe(crm_activity_settingsPopup.Events.EVENT_SETTINGS_VALIDATION, this.onSettingsValidation);
	  },
	  template: `
		<div class="crm-activity__settings-popup_body">
			<WrapperSection
				v-for="section in sections"
				:id="section.id"
				:toggle-title="getSectionTitle(section.id)"
				:toggle-enabled="activeSettingsIds[section.id]"
				:toggle-visible="getSectionToggle(section.showToggleSelector)"
				@onToggle="onToggleSettingsSection"
			>
				<component 
					v-bind:is="section.component"
					:params="section.params || {}"
					:ref="'section-' + section.id"
				></component>
			</WrapperSection>
		</div>
	`
	};

	let _ = t => t,
	  _t;
	const SAVE_BUTTON_ID = 'save';
	const CANCEL_BUTTON_ID = 'cancel';
	const Events = {
	  EVENT_SETTINGS_CHANGE: 'crm:settings-popup:settings-change',
	  EVENT_SETTINGS_VALIDATION: 'crm:settings-popup:settings-validation'
	};
	var _onSettingsChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSettingsChange");
	var _onSave = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSave");
	var _settingsSections = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("settingsSections");
	var _fetchSettingsPath = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fetchSettingsPath");
	var _ownerTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ownerTypeId");
	var _ownerId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ownerId");
	var _id = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _currentSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentSettings");
	var _onSettingsValidation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSettingsValidation");
	var _getPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupContent");
	var _getPopupTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupTitle");
	var _getPopupButtons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupButtons");
	var _cancel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cancel");
	var _initLayoutComponent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initLayoutComponent");
	var _adjustPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("adjustPopup");
	var _closePopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closePopup");
	class SettingsPopup {
	  constructor(options) {
	    Object.defineProperty(this, _closePopup, {
	      value: _closePopup2
	    });
	    Object.defineProperty(this, _adjustPopup, {
	      value: _adjustPopup2
	    });
	    Object.defineProperty(this, _initLayoutComponent, {
	      value: _initLayoutComponent2
	    });
	    Object.defineProperty(this, _cancel, {
	      value: _cancel2
	    });
	    Object.defineProperty(this, _getPopupButtons, {
	      value: _getPopupButtons2
	    });
	    Object.defineProperty(this, _getPopupTitle, {
	      value: _getPopupTitle2
	    });
	    Object.defineProperty(this, _getPopupContent, {
	      value: _getPopupContent2
	    });
	    Object.defineProperty(this, _onSettingsValidation, {
	      value: _onSettingsValidation2
	    });
	    this.container = null;
	    this.layoutApp = null;
	    this.layoutComponent = null;
	    this.popup = null;
	    Object.defineProperty(this, _onSettingsChange, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _onSave, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _settingsSections, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _fetchSettingsPath, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _ownerTypeId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _ownerId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _id, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _currentSettings, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _settingsSections)[_settingsSections] = options.sections || [];
	    babelHelpers.classPrivateFieldLooseBase(this, _fetchSettingsPath)[_fetchSettingsPath] = options.fetchSettingsPath || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _ownerTypeId)[_ownerTypeId] = options.ownerTypeId || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _ownerId)[_ownerId] = options.ownerId || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] = options.id || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _onSettingsChange)[_onSettingsChange] = options.onSettingsChange || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _currentSettings)[_currentSettings] = options.settings || null;
	    if (options.onSave) {
	      babelHelpers.classPrivateFieldLooseBase(this, _onSave)[_onSave] = options.onSave;
	    }
	  }
	  show() {
	    if (!this.popup || this.popup.isDestroyed()) {
	      const htmlStyles = getComputedStyle(document.documentElement);
	      const popupPadding = htmlStyles.getPropertyValue('--ui-space-inset-sm');
	      const popupPaddingNumberValue = parseFloat(popupPadding) || 12;
	      const popupOverlayColor = htmlStyles.getPropertyValue('--ui-color-base-solid') || '#000000';
	      const content = main_core.Tag.render(_t || (_t = _`
				<div class="crm-activity__settings">
					<div class="crm-activity__settings_title">${0}</div>
					<div class="crm-activity__todo-settings_content"></div>
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _getPopupTitle)[_getPopupTitle]());
	      this.popup = new main_popup.Popup({
	        closeIcon: true,
	        closeByEsc: true,
	        padding: popupPaddingNumberValue,
	        overlay: {
	          opacity: 40,
	          backgroundColor: popupOverlayColor
	        },
	        content,
	        buttons: babelHelpers.classPrivateFieldLooseBase(this, _getPopupButtons)[_getPopupButtons](),
	        minWidth: 850,
	        width: 850,
	        className: 'crm-activity__settings-popup'
	      });
	      this.popup.subscribeOnce('onFirstShow', () => {
	        this.loadSettings().then(() => {
	          babelHelpers.classPrivateFieldLooseBase(this, _getPopupContent)[_getPopupContent]();
	          this.popup.adjustPosition();
	        }, () => {
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('CRM_SETTINGS_POPUP_ERROR'),
	            autoHideDelay: 5000
	          });
	        });
	      });
	      this.popup.subscribe('onClose', babelHelpers.classPrivateFieldLooseBase(this, _initLayoutComponent)[_initLayoutComponent].bind(this));
	    }
	    this.popup.show();
	  }
	  loadSettings() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _fetchSettingsPath)[_fetchSettingsPath]) {
	      return Promise.resolve();
	    }
	    const data = {
	      id: babelHelpers.classPrivateFieldLooseBase(this, _id)[_id],
	      ownerTypeId: babelHelpers.classPrivateFieldLooseBase(this, _ownerTypeId)[_ownerTypeId],
	      ownerId: babelHelpers.classPrivateFieldLooseBase(this, _ownerId)[_ownerId]
	    };
	    return new Promise((resolve, reject) => {
	      main_core.ajax.runAction(babelHelpers.classPrivateFieldLooseBase(this, _fetchSettingsPath)[_fetchSettingsPath], {
	        data
	      }).then(({
	        data
	      }) => {
	        data.forEach(item => {
	          const section = babelHelpers.classPrivateFieldLooseBase(this, _settingsSections)[_settingsSections].find(settingsSection => settingsSection.id === item.id);
	          if (!section) {
	            return;
	          }
	          section.active = item.active;
	          section.params = item.settings;
	        });
	        resolve();
	      }).catch(reject);
	    });
	  }
	  save() {
	    babelHelpers.classPrivateFieldLooseBase(this, _currentSettings)[_currentSettings] = this.getSettings();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _onSettingsChange)[_onSettingsChange]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _onSettingsChange)[_onSettingsChange](this.getSettings());
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _closePopup)[_closePopup]();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _onSave)[_onSave]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _onSave)[_onSave](babelHelpers.classPrivateFieldLooseBase(this, _ownerTypeId)[_ownerTypeId], babelHelpers.classPrivateFieldLooseBase(this, _ownerId)[_ownerId], babelHelpers.classPrivateFieldLooseBase(this, _id)[_id], this.getSettings());
	    }
	  }
	  getSettings() {
	    var _this$layoutComponent;
	    return (_this$layoutComponent = this.layoutComponent) == null ? void 0 : _this$layoutComponent.exportParams();
	  }
	  syncSettings(data = null) {
	    if (data && this.layoutComponent) {
	      this.layoutComponent.updateSettings(data);
	    }
	  }
	}
	function _onSettingsValidation2(data) {
	  if (this.popup) {
	    this.popup.buttons[0].setDisabled(!data.isValid);
	  }
	}
	function _getPopupContent2() {
	  this.container = this.popup.getContentContainer().getElementsByClassName('crm-activity__todo-settings_content').item(0);
	  babelHelpers.classPrivateFieldLooseBase(this, _initLayoutComponent)[_initLayoutComponent]();
	  return this.layoutComponent;
	}
	function _getPopupTitle2() {
	  return main_core.Loc.getMessage('CRM_SETTINGS_POPUP_TITLE');
	}
	function _getPopupButtons2() {
	  return [new ui_buttons.SaveButton({
	    id: SAVE_BUTTON_ID,
	    round: true,
	    state: ui_buttons.ButtonState.ACTIVE,
	    events: {
	      click: this.save.bind(this)
	    }
	  }), new ui_buttons.CancelButton({
	    id: CANCEL_BUTTON_ID,
	    round: true,
	    events: {
	      click: babelHelpers.classPrivateFieldLooseBase(this, _cancel)[_cancel].bind(this)
	    },
	    text: main_core.Loc.getMessage('CRM_SETTINGS_POPUP_CANCEL'),
	    color: ui_buttons.ButtonColor.LIGHT_BORDER
	  })];
	}
	function _cancel2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _closePopup)[_closePopup]();
	  babelHelpers.classPrivateFieldLooseBase(this, _initLayoutComponent)[_initLayoutComponent]();
	}
	function _initLayoutComponent2() {
	  if (this.layoutApp && this.layoutComponent) {
	    this.layoutApp.unmount(this.container);
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _currentSettings)[_currentSettings]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _settingsSections)[_settingsSections].forEach(section => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _currentSettings)[_currentSettings][section.id]) {
	        section.active = true;
	        section.params = babelHelpers.classPrivateFieldLooseBase(this, _currentSettings)[_currentSettings][section.id];
	      }
	    });
	  }
	  this.layoutApp = ui_vue3.BitrixVue.createApp(Wrapper, {
	    onSettingsChangeCallback: babelHelpers.classPrivateFieldLooseBase(this, _adjustPopup)[_adjustPopup].bind(this),
	    onSettingsValidationCallback: babelHelpers.classPrivateFieldLooseBase(this, _onSettingsValidation)[_onSettingsValidation].bind(this),
	    sections: babelHelpers.classPrivateFieldLooseBase(this, _settingsSections)[_settingsSections]
	  });
	  this.layoutComponent = this.layoutApp.mount(this.container);
	}
	function _adjustPopup2() {
	  this.popup.adjustPosition();
	  this.popup.buttons[0].setDisabled(false);
	}
	function _closePopup2() {
	  var _this$popup;
	  (_this$popup = this.popup) == null ? void 0 : _this$popup.close();
	}

	exports.Calendar = Calendar;
	exports.Ping = Ping;
	exports.SettingsPopup = SettingsPopup;
	exports.Events = Events;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX.Main,BX.UI,BX,BX.Main,BX.Calendar,BX.Crm.DateTime,BX,BX.Crm.Timeline,BX.Crm.Activity,BX.Crm.EntitySelectorEx,BX.Vue3));
//# sourceMappingURL=settings-popup.bundle.js.map
