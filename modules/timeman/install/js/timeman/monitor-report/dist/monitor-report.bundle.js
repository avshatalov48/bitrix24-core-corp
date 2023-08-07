/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_forms,ui_layoutForm,ui_vuex,ui_vue_components_hint,ui_dialogs_messagebox,ui_icons,ui_fonts_opensans,timeman_component_timeline,timeman_timeformatter,timeman_monitor,ui_vue_portal,ui_notification,main_core,ui_vue,timeman_const,timeman_dateformatter,main_popup,main_loader) {
    'use strict';

    var AddIntervalPopup = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-popup-addinterval', {
      directives: {
        'bx-focus': {
          inserted: function inserted(element) {
            element.focus();
          }
        }
      },
      props: {
        minStart: Date,
        maxFinish: Date
      },
      data: function data() {
        return {
          title: '',
          start: this.getTime(this.minStart),
          finish: this.getTime(this.maxFinish),
          comment: ''
        };
      },
      created: function created() {
        this.minStart.setSeconds(0);
        this.minStart.setMilliseconds(0);
        this.maxFinish.setSeconds(0);
        this.maxFinish.setMilliseconds(0);
        if (this.createDateFromTimeString(this.finish) > this.saveMaxFinish) {
          this.finish = this.getTime(this.saveMaxFinish);
        }
      },
      computed: {
        TimeFormatter: function TimeFormatter() {
          return timeman_timeformatter.TimeFormatter;
        },
        DateFormatter: function DateFormatter() {
          return timeman_dateformatter.DateFormatter;
        },
        Type: function Type() {
          return main_core.Type;
        },
        saveMaxFinish: function saveMaxFinish() {
          var safeMaxFinish = this.maxFinish;
          var currentDateTime = new Date();
          currentDateTime.setSeconds(0);
          currentDateTime.setMilliseconds(0);
          if (safeMaxFinish > currentDateTime) {
            safeMaxFinish = currentDateTime;
          }
          return safeMaxFinish;
        },
        canAddInterval: function canAddInterval() {
          if (this.title.trim() === '' || !this.start || !this.finish) {
            return false;
          }
          var start = this.createDateFromTimeString(this.start);
          var finish = this.createDateFromTimeString(this.finish);
          var isStartError = start < this.minStart;
          var isFinishError = finish > this.saveMaxFinish;
          var isIntervalsConfusedError = start > finish;
          return !(isStartError || isFinishError || isIntervalsConfusedError);
        }
      },
      methods: {
        addInterval: function addInterval() {
          if (!this.canAddInterval) {
            return;
          }
          var start = this.createDateFromTimeString(this.start);
          var finish = this.createDateFromTimeString(this.finish);
          this.$store.dispatch('monitor/addHistory', {
            dateLog: timeman_dateformatter.DateFormatter.toString(start),
            title: this.title,
            type: timeman_const.EntityType.custom,
            comments: [{
              dateLog: timeman_dateformatter.DateFormatter.toString(start),
              text: this.comment
            }],
            time: [{
              start: start,
              preFinish: null,
              finish: finish
            }]
          });
          this.addIntervalPopupClose();
        },
        addIntervalPopupClose: function addIntervalPopupClose() {
          this.$emit('addIntervalPopupClose');
        },
        addIntervalPopupHide: function addIntervalPopupHide() {
          this.$emit('addIntervalPopupHide');
        },
        inputStart: function inputStart(value) {
          var start = this.createDateFromTimeString(this.start);
          if (start < this.minStart || value === '') {
            this.start = this.getTime(this.minStart);
            return;
          }
          if (start < this.minStart) {
            this.start = this.getTime(this.minStart);
            return;
          }
          if (this.finish) {
            var finish = this.createDateFromTimeString(this.finish);
            if (start >= finish || start >= this.getTime(this.saveMaxFinish)) {
              start.setHours(this.saveMaxFinish.getHours());
              start.setMinutes(this.saveMaxFinish.getMinutes() - 1);
              this.start = this.getTime(start);
              return;
            }
          }
          this.start = value;
        },
        inputFinish: function inputFinish(value) {
          var finish = this.createDateFromTimeString(this.finish);
          if (finish > this.saveMaxFinish || value === '') {
            this.finish = this.getTime(this.saveMaxFinish);
            return;
          }
          if (this.start) {
            var start = this.createDateFromTimeString(this.start);
            if (finish <= start || finish <= this.getTime(this.minStart)) {
              finish.setHours(start.getHours());
              finish.setMinutes(start.getMinutes() + 1);
              this.finish = this.getTime(finish);
              return;
            }
          }
          this.finish = value;
        },
        getTime: function getTime(date) {
          if (!main_core.Type.isDate(date)) {
            date = new Date(date);
          }
          var addZero = function addZero(num) {
            return num >= 0 && num <= 9 ? '0' + num : num;
          };
          var hour = addZero(date.getHours());
          var min = addZero(date.getMinutes());
          return hour + ':' + min;
        },
        createDateFromTimeString: function createDateFromTimeString(time) {
          var baseDate = this.minStart;
          var year = baseDate.getFullYear();
          var month = baseDate.getMonth();
          var day = baseDate.getDate();
          var hourMin = time.split(':');
          return new Date(year, month, day, hourMin[0], hourMin[1], 0, 0);
        }
      },
      // language=Vue
      template: "\n\t\t<div class=\"bx-monitor-group-wrap\">\n\t\t\t<div class=\"bx-timeman-monitor-report-popup-wrap\">\n\t\t\t\t<div class=\"popup-window popup-window-with-titlebar ui-message-box ui-message-box-medium-buttons popup-window-fixed-width popup-window-fixed-height\" style=\"padding: 0\">\n\t\t\t\t\t<div class=\"popup-window-titlebar\">\n\t\t\t\t\t\t<span class=\"popup-window-titlebar-text\">\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_CLICKABLE_HINT') }}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div\n\t\t\t\t\t\tclass=\"\n\t\t\t\t\t\t\tpopup-window-content\n\t\t\t\t\t\t\tbx-timeman-monitor-popup-window-content\n\t\t\t\t\t\t\"\n\t\t\t\t\t\tstyle=\"\n\t\t\t\t\t\t\toverflow: auto; \n\t\t\t\t\t\t\tbackground: transparent;\n\t\t\t\t\t\t\twidth: 440px;\n\t\t\t\t\t\t\"\n\t\t\t\t\t>\n\t\t\t\t\t  \n\t\t\t\t\t\t<div class=\"ui-form\">\n\t\t\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_TITLE') }}\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\t\t\tv-model=\"title\"\n\t\t\t\t\t\t\t\t\t\t\tv-bx-focus\n\t\t\t\t\t\t\t\t\t\t\ttype=\"text\" \n\t\t\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-form-row-inline\">\n\t\t\t\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_START') }}\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t\t\t{{ \n\t\t\t\t\t\t\t\t\t\t\t\t$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIN_START_HINT')\n\t\t\t\t\t\t\t\t\t\t  \t\t\t.replace('#TIME#', TimeFormatter.toShort(minStart))\n\t\t\t\t\t\t\t\t\t\t  \t}}\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-time\">\n\t\t\t\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\t\t\t\tv-model=\"start\"\n\t\t\t\t\t\t\t\t\t\t\t\tv-on:blur=\"inputStart($event.target.value)\"\n\t\t\t\t\t\t\t\t\t\t\t\ttype=\"time\" \n\t\t\t\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t\t\t\t\t\t\t\t\tstyle=\"padding-right: 4px !important;\"\n\t\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_FINISH') }}\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t\t\t{{\n\t\t\t\t\t\t\t\t\t\t\t\t$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MAX_FINISH_HINT')\n\t\t\t\t\t\t\t\t\t\t\t\t\t.replace('#TIME#', TimeFormatter.toShort(saveMaxFinish))\n\t\t\t\t\t\t\t\t\t\t\t}}\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-time\">\n\t\t\t\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\t\t\t\tv-model=\"finish\"\n\t\t\t\t\t\t\t\t\t\t\t\tv-on:blur=\"inputFinish($event.target.value)\"\n\t\t\t\t\t\t\t\t\t\t\t\ttype=\"time\" \n\t\t\t\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t\t\t\t\t\t\t\t\tstyle=\"padding-right: 4px !important;\"\n\t\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_COMMENT') }}\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textarea ui-ctl-no-resize\">\n\t\t\t\t\t\t\t\t\t\t<textarea\n\t\t\t\t\t\t\t\t\t\t\tv-model=\"comment\"\n\t\t\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t</textarea>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t\n\t\t\t\t\t<div class=\"popup-window-buttons\">\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t@click=\"addInterval\"\n\t\t\t\t\t\t\t:class=\"[\n\t\t\t\t\t\t\t\t'ui-btn',\n\t\t\t\t\t\t\t\t'ui-btn-md',\n\t\t\t\t\t\t\t\t'ui-btn-primary',\n\t\t\t\t\t\t\t\t!canAddInterval ? 'ui-btn-disabled' : ''\n\t\t\t\t\t\t\t]\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<span class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_ADD_BUTTON') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<button \n\t\t\t\t\t\t\t@click=\"addIntervalPopupHide\" \n\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-light\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<span class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CANCEL_BUTTON') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t </button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
    });

    var Interval = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-popup-selectintervalpopup-interval', {
      props: {
        start: Date,
        finish: Date
      },
      computed: {
        TimeFormatter: function TimeFormatter() {
          return timeman_timeformatter.TimeFormatter;
        },
        safeFinish: function safeFinish() {
          var safeFinish = this.finish;
          var currentDateTime = new Date();
          currentDateTime.setSeconds(0);
          currentDateTime.setMilliseconds(0);
          if (safeFinish > currentDateTime) {
            safeFinish = currentDateTime;
          }
          return safeFinish;
        }
      },
      methods: {
        intervalSelected: function intervalSelected() {
          this.$emit('intervalSelected', {
            start: this.start,
            finish: this.safeFinish
          });
        }
      },
      // language=Vue
      template: "\n\t\t<div class=\"bx-timeman-monitor-report-popup-selectintervalpopup-interval\">\n\t\t\t<div\n\t\t\t\t@click=\"intervalSelected\"\n                class=\"bx-timeman-monitor-report-popup-item\"\n\t\t\t>\n\t\t\t  <div class=\"bx-timeman-monitor-report-popup-title\">\n                {{ TimeFormatter.toShort(start) }} - {{ TimeFormatter.toShort(safeFinish) }}\n\t\t\t  </div>\n\t\t\t</div>\n\t\t</div>\n\t"
    });

    var SelectIntervalPopup = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-popup-selectintervalpopup', {
      components: {
        Interval: Interval
      },
      computed: {
        inactiveIntervals: function inactiveIntervals() {
          return this.$store.getters['monitor/getChartData'].filter(function (interval) {
            return interval.type === timeman_const.EntityGroup.inactive.value && interval.start < new Date();
          });
        }
      },
      methods: {
        selectIntervalPopupCloseClick: function selectIntervalPopupCloseClick() {
          this.$emit('selectIntervalPopupCloseClick');
        },
        onIntervalSelected: function onIntervalSelected(event) {
          this.$emit('intervalSelected', event);
        }
      },
      // language=Vue
      template: "\n\t\t<div class=\"bx-timeman-monitor-report-popup-selectintervalpopup\">\n\t\t\t<div class=\"bx-timeman-monitor-report-popup-wrap\">\n\t\t\t\t<div \n\t\t\t\t\tclass=\"\n\t\t\t\t\t\tbx-timeman-monitor-report-popup\n\t\t\t\t\t\tpopup-window \n\t\t\t\t\t\tpopup-window-with-titlebar \n\t\t\t\t\t\tui-message-box \n\t\t\t\t\t\tui-message-box-medium-buttons \n\t\t\t\t\t\tpopup-window-fixed-width \n\t\t\t\t\t\tpopup-window-fixed-height\n\t\t\t\t\t\" \n\t\t\t\t\tstyle=\"padding: 0\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"popup-window-titlebar\">\n\t\t\t\t\t\t<span class=\"popup-window-titlebar-text\">\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_SELECT_INTERVAL') }}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div\n\t\t\t\t\t\tclass=\"\n\t\t\t\t\t\t\tpopup-window-content\n\t\t\t\t\t\t\tbx-timeman-monitor-popup-window-content\n\t\t\t\t\t\t\"\n\t\t\t\t\t\tstyle=\"\n\t\t\t\t\t\t\toverflow: auto; \n\t\t\t\t\t\t\tbackground: transparent;\n\t\t\t\t\t\t\twidth: 440px;\n\t\t\t\t\t\t\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<div class=\"bx-timeman-monitor-report-popup-items-container\">\n\t\t\t\t\t\t\t<Interval\n\t\t\t\t\t\t\t\tv-for=\"interval of inactiveIntervals\"\n\t\t\t\t\t\t\t\t:key=\"interval.start.toString()\"\n\t\t\t\t\t\t\t\t:start=\"interval.start\"\n\t\t\t\t\t\t\t\t:finish=\"interval.finish\"\n\t\t\t\t\t\t\t\t@intervalSelected=\"onIntervalSelected\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"popup-window-buttons\">\n\t\t\t\t\t\t<button \n\t\t\t\t\t\t\t@click=\"selectIntervalPopupCloseClick\" \n\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-light\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<span class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CANCEL_BUTTON') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
    });

    var Time = {
      computed: {
        fullTime: function fullTime() {
          return this.workingTime + this.personalTime;
        },
        workingTime: function workingTime() {
          return this.$store.getters['monitor/getWorkingEntities'].reduce(function (sum, entry) {
            return sum + entry.time;
          }, 0);
        },
        personalTime: function personalTime() {
          return this.$store.getters['monitor/getPersonalEntities'].reduce(function (sum, entry) {
            return sum + entry.time;
          }, 0);
        },
        inactiveTime: function inactiveTime() {
          return 86400 - (this.workingTime + this.personalTime);
        }
      },
      methods: {
        formatSeconds: function formatSeconds(seconds) {
          if (seconds < 1) {
            return 0 + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_MINUTES_SHORT');
          } else if (seconds < 60) {
            return this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_LESS_THAN_MINUTE');
          }
          var hours = Math.floor(seconds / 3600);
          var minutes = Math.round(seconds / 60 % 60);
          if (minutes === 60) {
            hours += 1;
            minutes = 0;
          }
          if (hours > 0) {
            hours = hours + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_HOUR_SHORT');
            if (minutes > 0) {
              minutes = minutes + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_MINUTES_SHORT');
              return hours + ' ' + minutes;
            }
            return hours;
          }
          return minutes + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_MINUTES_SHORT');
        },
        calculateEntryTime: function calculateEntryTime(entry) {
          var time = entry.time.map(function (interval) {
            var finish = interval.finish ? new Date(interval.finish) : new Date();
            return finish - new Date(interval.start);
          }).reduce(function (sum, interval) {
            return sum + interval;
          }, 0);
          return Math.round(time / 1000);
        },
        getEntityByPrivateCode: function getEntityByPrivateCode(privateCode) {
          return this.monitor.entity.find(function (entity) {
            return entity.privateCode === privateCode;
          });
        }
      }
    };

    function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
    function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
    var Item = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-group-item', {
      mixins: [Time],
      props: ['readOnly', 'group', 'privateCode', 'type', 'title', 'time', 'allowedTime', 'comment', 'hint'],
      data: function data() {
        return {
          action: '',
          hintOptions: {
            targetContainer: document.body
          },
          selected: false,
          selectIntervalTimeout: null
        };
      },
      computed: _objectSpread(_objectSpread(_objectSpread({}, ui_vuex.Vuex.mapGetters('monitor', ['getSiteDetailByPrivateCode'])), ui_vuex.Vuex.mapState({
        monitor: function monitor(state) {
          return state.monitor;
        }
      })), {}, {
        EntityType: function EntityType() {
          return timeman_const.EntityType;
        },
        EntityGroup: function EntityGroup() {
          return timeman_const.EntityGroup;
        }
      }),
      methods: {
        addPersonal: function addPersonal(privateCode) {
          this.$store.dispatch('monitor/addPersonal', privateCode);
          this.onIntervalUnselected();
        },
        removePersonal: function removePersonal(privateCode) {
          var _this = this;
          if (this.type === timeman_const.EntityType.absence && this.comment.trim() === '') {
            this.action = function () {
              return _this.$store.dispatch('monitor/removePersonal', _this.privateCode);
            };
            this.onCommentClick();
            return;
          }
          this.$store.dispatch('monitor/removePersonal', privateCode);
          this.onIntervalUnselected();
        },
        addToStrictlyWorking: function addToStrictlyWorking(privateCode) {
          var _this2 = this;
          if (this.type === timeman_const.EntityType.absence && this.comment.trim() === '') {
            this.action = function () {
              return _this2.$store.dispatch('monitor/addToStrictlyWorking', privateCode);
            };
            this.onCommentClick();
            return;
          }
          this.$store.dispatch('monitor/addToStrictlyWorking', privateCode);
        },
        removeFromStrictlyWorking: function removeFromStrictlyWorking(privateCode) {
          this.$store.dispatch('monitor/removeFromStrictlyWorking', privateCode);
        },
        removeEntityByPrivateCode: function removeEntityByPrivateCode(privateCode) {
          this.$store.dispatch('monitor/removeEntityByPrivateCode', privateCode);
        },
        onCommentClick: function onCommentClick(event) {
          this.$emit('commentClick', {
            event: event,
            group: this.group,
            content: {
              privateCode: this.privateCode,
              title: this.title,
              time: this.time,
              comment: this.comment,
              type: this.type
            },
            onSaveComment: this.action
          });
        },
        onDetailClick: function onDetailClick(event) {
          this.$emit('detailClick', {
            event: event,
            group: this.group,
            content: {
              privateCode: this.privateCode,
              title: this.title,
              detail: this.getSiteDetailByPrivateCode(this.privateCode),
              time: this.time
            }
          });
        },
        onIntervalSelected: function onIntervalSelected() {
          var _this3 = this;
          this.$emit('intervalSelected', this.privateCode);
          if (this.readOnly) {
            return;
          }
          this.selectIntervalTimeout = setTimeout(function () {
            _this3.selected = true;
          }, 500);
        },
        onIntervalUnselected: function onIntervalUnselected() {
          this.$emit('intervalUnselected');
          if (this.readOnly) {
            return;
          }
          clearTimeout(this.selectIntervalTimeout);
          this.selected = false;
        }
      },
      // language=Vue
      template: "\n\t\t<div class=\"bx-monitor-group-item-wrap\">\n\t\t\t<div\n\t\t\t\t:class=\"[\n            \t\t'bx-monitor-group-item',\n\t\t\t\t\tthis.selected ? 'bx-monitor-group-item-' + this.group + '-selected' : ''\n\t\t\t\t]\"\n\t\t\t\t@mouseenter=\"onIntervalSelected\"\n\t\t\t\t@mouseleave=\"onIntervalUnselected\"\n\t\t\t>\n\t\t\t\t<template\n\t\t\t\t\tv-if=\"\n\t\t\t\t\t\ttype !== EntityType.group\n\t\t\t\t\t\t&& type !== EntityType.absenceShort\n\t\t\t\t\t\t&& type !== EntityType.other\n\t\t\t\t\t\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"bx-monitor-group-item-container\">\n\t\t\t\t\t\t<div class=\"bx-monitor-group-item-title-container\">\n\t\t\t\t\t\t \t<template v-if=\"type === EntityType.absence\">\n\t\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\t\tclass=\"bx-monitor-group-item-icon bx-monitor-group-item-icon-away\"\n\t\t\t\t\t\t\t\t\tv-bx-hint=\"{\n\t\t\t\t\t\t\t\t\t\ttext: $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_ABSENCE'),\n\t\t\t\t\t\t\t\t\t\tpopupOptions: {\n\t\t\t\t\t\t\t\t\t\t\thintOptions,\n\t\t\t\t\t\t\t\t\t\t\tid: 'bx-vue-hint-monitor-absence-hint',\n\t\t\t\t\t\t\t\t\t\t},\n\t\t\t\t\t\t\t\t\t}\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\t\tv-if=\"type === EntityType.absence\"\n\t\t\t\t\t\t\t\t\t:class=\"{\n\t\t\t\t\t\t\t\t\t  'bx-monitor-group-item-title': comment,\n\t\t\t\t\t\t\t\t\t  'bx-monitor-group-item-title-small': !comment\n\t\t\t\t\t\t\t\t\t}\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<template v-if=\"comment\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"bx-monitor-group-item-title\">{{ comment }}</div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"bx-monitor-group-item-subtitle\">{{ title }}</div>\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t\t{{ title }}\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else-if=\"type === EntityType.custom\">\n\t\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\t\tclass=\"ui-icon ui-icon-common-user bx-monitor-group-item-icon\"\n\t\t\t\t\t\t\t\t\tv-bx-hint=\"{\n\t\t\t\t\t\t\t\t\t\ttext: $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CUSTOM_HINT'),\n\t\t\t\t\t\t\t\t\t\tpopupOptions: {\n\t\t\t\t\t\t\t\t\t\t\thintOptions,\n\t\t\t\t\t\t\t\t\t\t\tid: 'bx-vue-hint-monitor-custom-hint',\n\t\t\t\t\t\t\t\t\t\t},\n\t\t\t\t\t\t\t\t\t}\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<i/>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"bx-monitor-group-item-title\">\n\t\t\t\t\t\t\t\t\t{{ title }}\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<div v-else class=\"bx-monitor-group-item-title\">\n\t\t\t\t\t\t\t\t<template v-if=\"type !== EntityType.site || readOnly\">\n\t\t\t\t\t\t\t\t\t{{ title }}\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t\t\t@click=\"onDetailClick\"\n\t\t\t\t\t\t\t\t\t\thref=\"#\"\n\t\t\t\t\t\t\t\t\t\tclass=\"bx-monitor-group-site-title\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t{{ title }}\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<bx-hint v-if=\"hint\" :text=\"hint\" :popupOptions=\"hintOptions\"/>\n\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\tv-if=\"group === EntityGroup.working.value && !readOnly\"\n\t\t\t\t\t\t\t\tclass=\"bx-monitor-group-item-button-comment ui-icon ui-icon-xs\"\n\t\t\t\t\t\t\t\t:class=\"{\n\t\t\t\t\t\t\t\t  'ui-icon-service-imessage': comment,\n\t\t\t\t\t\t\t\t  'ui-icon-service-light-imessage': !comment\n\t\t\t\t\t\t\t\t}\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<i\n\t\t\t\t\t\t\t\t\t@click=\"onCommentClick\"\n\t\t\t\t\t\t\t\t\t:style=\"{\n\t\t\t\t\t\t\t\t\t\tbackgroundColor: comment ? '#77c18d' : 'transparent'\n\t\t\t\t\t\t\t\t\t}\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-else-if=\"group === EntityGroup.working.value && readOnly && comment\"\n\t\t\t\t\t\t\t\tclass=\"bx-monitor-group-item-icon bx-monitor-group-item-icon-comment\"\n\t\t\t\t\t\t\t\tv-bx-hint=\"{\n\t\t\t\t\t\t\t\t\ttext: comment,\n\t\t\t\t\t\t\t\t\tpopupOptions: {\n\t\t\t\t\t\t\t\t\t\t...hintOptions,\n\t\t\t\t\t\t\t\t\t\tid: 'bx-vue-hint-monitor-comment',\n\t\t\t\t\t\t\t\t\t},\n\t\t\t\t\t\t\t\t}\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-monitor-group-item-time\">\n\t\t\t\t\t\t\t{{ time }}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<button\n\t\t\t\t\t\tv-if=\"group === EntityGroup.personal.value && !readOnly\"\n\t\t\t\t\t\t@click=\"removePersonal(privateCode)\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-xs ui-btn-light-border ui-btn-round bx-monitor-group-btn-right\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_TO_WORKING') }}\n\t\t\t\t\t</button>\n\t\t\t\t\t<button\n\t\t\t\t\t\tv-if=\"\n\t\t\t\t\t\t\tgroup === EntityGroup.working.value\n\t\t\t\t\t\t\t&& (type !== EntityType.unknown && type !== EntityType.custom)\n\t\t\t\t\t\t\t&& !readOnly\n\t\t\t\t\t\t\"\n\t\t\t\t\t\t@click=\"addPersonal(privateCode)\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-xs ui-btn-light-border ui-btn-round bx-monitor-group-btn-right\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_TO_PERSONAL') }}\n\t\t\t\t\t</button>\n\t\t\t\t\t<button\n\t\t\t\t\t\tv-if=\"\n\t\t\t\t\t\t\ttype === EntityType.custom\n\t\t\t\t\t\t\t&& !readOnly\n\t\t\t\t\t\t\"\n\t\t\t\t\t\t@click=\"removeEntityByPrivateCode(privateCode)\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-xs ui-btn-danger-light ui-btn-round bx-monitor-group-btn-right\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_REMOVE') }}\n\t\t\t\t\t</button>\n\t\t\t\t</template>\n\t\t\t\t<template v-else>\n\t\t\t\t\t<div class=\"bx-monitor-group-item-container\">\n\t\t\t\t\t\t<div class=\"bx-monitor-group-item-title-container\">\n\t\t\t\t\t\t\t<div class=\"bx-monitor-group-item-title-full\">\n\t\t\t\t\t\t\t\t{{ title }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<bx-hint v-if=\"hint\" :text=\"hint\" :popupOptions=\"hintOptions\"/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-monitor-group-item-menu\">\n\t\t\t\t\t\t\t<div class=\"bx-monitor-group-item-time\">\n\t\t\t\t\t\t\t\t{{ time }} / {{ allowedTime }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t</div>\n\t\t</div>\n\t"
    });

    var Group = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-group', {
      components: {
        Item: Item,
        MountingPortal: ui_vue_portal.MountingPortal
      },
      directives: {
        'bx-focus': {
          inserted: function inserted(element) {
            element.focus();
          }
        }
      },
      mixins: [Time],
      props: ['group', 'items', 'time', 'reportComment', 'readOnly', 'hasIntervalsToAdd'],
      data: function data() {
        return {
          popupInstance: null,
          popupIdSelector: !!this.readOnly ? '#bx-timeman-pwt-popup-preview' : '#bx-timeman-pwt-popup-editor',
          popupContent: {
            privateCode: '',
            title: '',
            time: '',
            comment: '',
            detail: '',
            type: '',
            onSaveComment: ''
          },
          comment: '',
          isCommentPopup: false,
          isDetailPopup: false,
          isReportCommentPopup: false
        };
      },
      computed: {
        EntityType: function EntityType() {
          return timeman_const.EntityType;
        },
        EntityGroup: function EntityGroup() {
          return timeman_const.EntityGroup;
        },
        displayedGroup: function displayedGroup() {
          if (this.EntityGroup.getValues().includes(this.group)) {
            return this.EntityGroup[this.group];
          }
        }
      },
      methods: {
        onCommentClick: function onCommentClick(event) {
          var _this = this;
          this.isCommentPopup = true;
          this.popupContent.privateCode = event.content.privateCode;
          this.popupContent.title = event.content.title;
          this.popupContent.time = event.content.time;
          this.popupContent.type = event.content.type;
          this.popupContent.onSaveComment = event.onSaveComment;
          this.comment = event.content.comment;
          if (this.popupInstance !== null) {
            this.popupInstance.destroy();
            this.popupInstance = null;
          }
          var popup = main_popup.PopupManager.create({
            id: "bx-timeman-pwt-external-data",
            targetContainer: document.body,
            autoHide: true,
            closeByEsc: true,
            bindOptions: {
              position: "top"
            },
            events: {
              onPopupDestroy: function onPopupDestroy() {
                _this.isCommentPopup = false;
                _this.popupInstance = null;
              }
            }
          });

          //little hack for correct open several popups in a row.
          this.$nextTick(function () {
            return _this.popupInstance = popup;
          });
        },
        onReportCommentClick: function onReportCommentClick() {
          var _this2 = this;
          this.isReportCommentPopup = true;
          this.popupContent.title = this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_REPORT_COMMENT');
          this.comment = this.reportComment;
          if (this.popupInstance !== null) {
            this.popupInstance.destroy();
            this.popupInstance = null;
          }
          var popup = main_popup.PopupManager.create({
            id: "bx-timeman-pwt-external-data",
            targetContainer: document.body,
            autoHide: true,
            closeByEsc: true,
            bindOptions: {
              position: "top"
            },
            events: {
              onPopupDestroy: function onPopupDestroy() {
                _this2.isReportCommentPopup = false;
                _this2.popupInstance = null;
              }
            }
          });

          //little hack for correct open several popups in a row.
          this.$nextTick(function () {
            return _this2.popupInstance = popup;
          });
        },
        onDetailClick: function onDetailClick(event) {
          var _this3 = this;
          this.isDetailPopup = true;
          this.popupContent.privateCode = event.content.privateCode;
          this.popupContent.title = event.content.title;
          this.popupContent.time = event.content.time;
          this.popupContent.detail = event.content.detail;
          if (this.popupInstance !== null) {
            this.popupInstance.destroy();
            this.popupInstance = null;
          }
          var popup = main_popup.PopupManager.create({
            id: "bx-timeman-pwt-external-data",
            targetContainer: document.body,
            autoHide: true,
            closeByEsc: true,
            bindOptions: {
              position: "top"
            },
            events: {
              onPopupDestroy: function onPopupDestroy() {
                _this3.isDetailPopup = false;
                _this3.popupInstance = null;
              }
            }
          });

          //little hack for correct open several popups in a row.
          this.$nextTick(function () {
            return _this3.popupInstance = popup;
          });
        },
        saveComment: function saveComment(privateCode) {
          if (this.comment.trim() === '' && this.popupContent.type === timeman_const.EntityType.absence) {
            return;
          }
          this.$store.dispatch('monitor/setComment', {
            privateCode: privateCode,
            comment: this.comment
          });
          if (typeof this.popupContent.onSaveComment === 'function') {
            this.popupContent.onSaveComment();
          }
          this.popupInstance.destroy();
        },
        saveReportComment: function saveReportComment() {
          this.$store.dispatch('monitor/setReportComment', this.comment);
          this.popupInstance.destroy();
        },
        addNewLineToComment: function addNewLineToComment() {
          this.comment += '\n';
        },
        selectIntervalClick: function selectIntervalClick(event) {
          if (!this.hasIntervalsToAdd) {
            return;
          }
          this.$emit('selectIntervalClick', event);
        },
        onIntervalSelected: function onIntervalSelected(privateCode) {
          this.$emit('intervalSelected', privateCode);
        },
        onIntervalUnselected: function onIntervalUnselected() {
          this.$emit('intervalUnselected');
        }
      },
      // language=Vue
      template: "\t\t  \n\t\t<div class=\"bx-timeman-monitor-report-group-wrap\">\t\t\t\n\t\t\t<div class=\"bx-monitor-group\">\t\t\t\t  \n\t\t\t\t<div class=\"bx-monitor-group-header\" v-bind:style=\"{ background: displayedGroup.secondaryColor }\">\n\t\t\t\t\t<div class=\"bx-monitor-group-title-container\">\n                      \t<div class=\"bx-monitor-group-title-wrap\">\n\t\t\t\t\t\t\t<div class=\"bx-monitor-group-title\">\n\t\t\t\t\t\t\t\t{{ displayedGroup.title }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"bx-monitor-group-title-wrap\">\n\t\t\t\t\t\t\t\t<div class=\"bx-monitor-group-subtitle\">\n\t\t\t\t\t\t\t\t  {{ formatSeconds(time) }}\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<button \n\t\t\t\t\t\t\t\tv-if=\"this.displayedGroup.value === EntityGroup.working.value && !readOnly\"\n\t\t\t\t\t\t\t\t@click=\"onReportCommentClick\"\n\t\t\t\t\t\t\t\tclass=\"bx-monitor-group-item-button-comment ui-icon ui-icon-xs\"\n\t\t\t\t\t\t\t\t:class=\"{\n\t\t\t\t\t\t\t\t\t'ui-icon-service-imessage': reportComment, \n\t\t\t\t\t\t\t\t\t'ui-icon-service-light-imessage': !reportComment \n\t\t\t\t\t\t\t\t}\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<i \n\t\t\t\t\t\t\t\t\t:style=\"{\n\t\t\t\t\t\t\t\t\t\tbackgroundColor: reportComment ? '#77c18d' : 'transparent'\n\t\t\t\t\t\t\t\t\t}\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-else-if=\"\n\t\t\t\t\t\t\t\t\tthis.displayedGroup.value === EntityGroup.working.value \n\t\t\t\t\t\t\t\t\t&& readOnly \n\t\t\t\t\t\t\t\t\t&& reportComment\n\t\t\t\t\t\t\t\t\"\n\t\t\t\t\t\t\t\tclass=\"bx-monitor-group-item-icon bx-monitor-group-item-icon-comment\"\n\t\t\t\t\t\t\t\tv-bx-hint=\"reportComment\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tv-if=\"(\n\t\t\t\t\t\t\t\tthis.displayedGroup.value === EntityGroup.working.value\n\t\t\t\t\t\t\t\t&& !readOnly \n\t\t\t\t\t\t\t)\"\n\t\t\t\t\t\t\t@click=\"selectIntervalClick\"\n\t\t\t\t\t\t\t:class=\"{\n\t\t\t\t\t\t\t\t'bx-monitor-group-btn-add': true,\n\t\t\t\t\t\t\t\t'ui-btn': true, \n\t\t\t\t\t\t\t\t'ui-btn-xs': true, \n\t\t\t\t\t\t\t\t'ui-btn-round': true, \n\t\t\t\t\t\t\t\t'ui-btn-light': hasIntervalsToAdd, \n\t\t\t\t\t\t\t\t'ui-btn-disabled': !hasIntervalsToAdd, \n\t\t\t\t\t\t\t}\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<span class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t{{ '+ ' + $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_ADD') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div v-if=\"!readOnly\" class=\"bx-monitor-group-subtitle-wrap\">\n\t\t\t\t\t\t<div class=\"bx-monitor-group-hint\">\n\t\t\t\t\t\t\t{{ displayedGroup.hint }}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"bx-monitor-group-content\" v-bind:style=\"{ background: displayedGroup.lightColor }\">\n\t\t\t\t\t<transition-group name=\"bx-monitor-group-item\" class=\"bx-monitor-group-content-wrap\">\n\t\t\t\t\t\n\t\t\t\t\t\t<Item\n\t\t\t\t\t\t\tv-for=\"item of items\"\n\t\t\t\t\t\t\t:key=\"item.privateCode ? item.privateCode : item.title\"\n\t\t\t\t\t\t\t:group=\"displayedGroup.value\"\n\t\t\t\t\t\t\t:privateCode=\"item.privateCode\"\n\t\t\t\t\t\t\t:type=\"item.type\"\n\t\t\t\t\t\t\t:title=\"item.title\"\n\t\t\t\t\t\t\t:comment=\"item.comment\"\n\t\t\t\t\t\t\t:time=\"formatSeconds(item.time)\"\n\t\t\t\t\t\t\t:allowedTime=\"item.allowedTime ? formatSeconds(item.allowedTime) : null\"\n\t\t\t\t\t\t\t:readOnly=\"!!readOnly\"\n\t\t\t\t\t\t\t:hint=\"item.hint !== '' ? item.hint : null\"\n\t\t\t\t\t\t\t@commentClick=\"onCommentClick\"\n\t\t\t\t\t\t\t@detailClick=\"onDetailClick\"\n\t\t\t\t\t\t\t@intervalSelected=\"onIntervalSelected\"\n\t\t\t\t\t\t\t@intervalUnselected=\"onIntervalUnselected\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t  \n\t\t\t\t\t</transition-group>\n\t\t\t\t</div>\n\t\t\t</div>\n\n\t\t\t<mounting-portal :mount-to=\"popupIdSelector\" append v-if=\"popupInstance\">\n\t\t\t\t<div class=\"bx-timeman-monitor-popup-wrap\">\t\t\t\t\t\n\t\t\t\t\t<div class=\"popup-window popup-window-with-titlebar ui-message-box ui-message-box-medium-buttons popup-window-fixed-width popup-window-fixed-height\" style=\"padding: 0\">\n\t\t\t\t\t\t<div class=\"bx-timeman-monitor-popup-title popup-window-titlebar\">\n\t\t\t\t\t\t\t<span class=\"bx-timeman-monitor-popup--titlebar-text popup-window-titlebar-text\">\n\t\t\t\t\t\t\t\t{{ popupContent.title }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t<span \n\t\t\t\t\t\t\t\tv-if=\"isCommentPopup || isDetailPopup\" \n\t\t\t\t\t\t\t\tclass=\"bx-timeman-monitor-popup--titlebar-text popup-window-titlebar-text\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t{{ popupContent.time }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"popup-window-content\" style=\"overflow: auto; background: transparent;\">\n\t\t\t\t\t\t\t<textarea \n\t\t\t\t\t\t\t\tclass=\"bx-timeman-monitor-popup-input\"\n\t\t\t\t\t\t\t\tid=\"bx-timeman-monitor-popup-input-comment\"\n\t\t\t\t\t\t\t\tv-if=\"isCommentPopup || isReportCommentPopup\"\n\t\t\t\t\t\t\t\tv-model=\"comment\"\n\t\t\t\t\t\t\t\tv-bx-focus\n\t\t\t\t\t\t\t\t:placeholder=\"$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_ITEM_COMMENT')\"\n\t\t\t\t\t\t\t\t@keydown.enter.prevent.exact=\"\n\t\t\t\t\t\t\t\t\tisCommentPopup \n\t\t\t\t\t\t\t\t\t\t? saveComment(popupContent.privateCode) \n\t\t\t\t\t\t\t\t\t\t: saveReportComment()\n\t\t\t\t\t\t\t\t\"\n\t\t\t\t\t\t\t\t@keyup.shift.enter.exact=\"addNewLineToComment\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t<div v-if=\"isDetailPopup\" class=\"bx-timeman-monitor-popup-items-container\">\n\t\t\t\t\t\t\t\t<div \n\t\t\t\t\t\t\t\t\tv-for=\"detailItem in popupContent.detail\" \n\t\t\t\t\t\t\t\t\tclass=\"bx-timeman-monitor-popup-item\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<div class=\"bx-timeman-monitor-popup-content\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"bx-timeman-monitor-popup-content-title\">\n\t\t\t\t\t\t\t\t\t\t\t{{ detailItem.siteTitle }}\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"bx-timeman-monitor-popup-content-title\">\n\t\t\t\t\t\t\t\t\t\t\t<a target=\"_blank\" :href=\"detailItem.siteUrl\" class=\"bx-timeman-monitor-popup-content-title\">\n\t\t\t\t\t\t\t\t\t\t\t\t{{ detailItem.siteUrl }}\n\t\t\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"bx-timeman-monitor-popup-time\">\n\t\t\t\t\t\t\t\t\t\t{{ formatSeconds(detailItem.time) }}\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"popup-window-buttons\">\n\t\t\t\t\t\t\t<button \n\t\t\t\t\t\t\t\tv-if=\"isCommentPopup || isReportCommentPopup\" \n\t\t\t\t\t\t\t\t@click=\"\n\t\t\t\t\t\t\t\t\tisCommentPopup \n\t\t\t\t\t\t\t\t\t\t? saveComment(popupContent.privateCode) \n\t\t\t\t\t\t\t\t\t\t: saveReportComment()\n\t\t\t\t\t\t\t\t\"\n\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-primary\"\n\t\t\t\t\t\t\t\t:class=\"{'ui-btn-disabled': (comment.trim() === '' && popupContent.type === EntityType.absence)}\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<span class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_OK') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t<button @click=\"popupInstance.destroy()\" class=\"ui-btn ui-btn-md ui-btn-light\">\n\t\t\t\t\t\t\t\t<span v-if=\"isCommentPopup || isReportCommentPopup\" class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_CANCEL') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<span v-if=\"isDetailPopup\" class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_CLOSE') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</mounting-portal>\n\t\t</div>\n\t"
    });

    var Consent = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-consent', {
      computed: {
        isWindows: function isWindows() {
          return navigator.userAgent.toLowerCase().includes('windows') || !this.isMac && !this.isLinux;
        },
        isMac: function isMac() {
          return navigator.userAgent.toLowerCase().includes('macintosh');
        },
        isLinux: function isLinux() {
          return navigator.userAgent.toLowerCase().includes('linux');
        }
      },
      methods: {
        grantPermissionMac: function grantPermissionMac() {
          //If no native permission window has appeared before, this method will cause it to appear
          BXDesktopSystem.ListScreenMedia(function () {});
          this.grantPermission();
        },
        grantPermissionWindows: function grantPermissionWindows() {
          this.grantPermission();
        },
        grantPermissionLinux: function grantPermissionLinux() {
          this.grantPermission();
        },
        grantPermission: function grantPermission() {
          this.$store.dispatch('monitor/grantPermission').then(function () {
            BX.Timeman.Monitor.launch();
          });
        },
        openPermissionHelp: function openPermissionHelp() {
          this.openHelpdesk('13857358');
        },
        openHelpdesk: function openHelpdesk(code) {
          if (top.BX.Helper) {
            top.BX.Helper.show('redirect=detail&code=' + code);
          }
        },
        showGrantingPermissionLater: function showGrantingPermissionLater() {
          this.$store.dispatch('monitor/showGrantingPermissionLater').then(function () {
            BX.SidePanel.Instance.close();
          });
        }
      },
      // language=Vue
      template: "\n\t\t<div class=\"bx-timeman-monitor-report-consent\">\n\t\t\t<div class=\"pwt-report-header-container\">\n\t\t\t\t<div class=\"pwt-report-header-title\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SLIDER_TITLE') }}\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class=\"pwt-report-content-container\">\n\t\t\t\t<div class=\"pwt-report-content\">\n\t\t\t\t\t<div class=\"bx-timeman-monitor-report-consent-logo-container\">\n\t\t\t\t\t\t<svg class=\"bx-timeman-monitor-report-consent-logo\"/>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"bx-timeman-monitor-report-consent-description\">\n\t\t\t\t\t\t<p>{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PRODUCT_DESCRIPTION_1') }}</p>\n\t\t\t\t\t\t<p>{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PRODUCT_DESCRIPTION_2') }}</p>\n\t\t\t\t\t\t<p>{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PRODUCT_DESCRIPTION_3') }}</p>\n\t\t\t\t\t\t<p>{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PRODUCT_DESCRIPTION_4') }}</p>\n\t\t\t\t\t\t<p v-if=\"isMac\">\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PROVIDE_DESCRIPTION_MAC') + ' ' }}\n\t\t\t\t\t\t\t<span @click=\"openPermissionHelp\" class=\"bx-timeman-monitor-report-consent-link\">\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PROVIDE_DESCRIPTION_MAC_DETAIL') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</p>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"pwt-report-button-panel-wrapper ui-pinner ui-pinner-bottom ui-pinner-full-width\" style=\"z-index: 0\">\n\t\t\t\t\t<div class=\"pwt-report-button-panel\">\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tv-if=\"isMac\"\n\t\t\t\t\t\t\t@click=\"grantPermissionMac\"\n\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-success\"\n\t\t\t\t\t\t\tstyle=\"margin-left: 16px;\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PROVIDE') }}\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tv-else-if=\"isWindows\"\n\t\t\t\t\t\t\t@click=\"grantPermissionWindows\"\n\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-success\"\n\t\t\t\t\t\t\tstyle=\"margin-left: 16px;\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PROVIDE') }}\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tv-else-if=\"isLinux\"\n\t\t\t\t\t\t\t@click=\"grantPermissionLinux\"\n\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-success\"\n\t\t\t\t\t\t\tstyle=\"margin-left: 16px;\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PROVIDE') }}\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t@click=\"showGrantingPermissionLater\"\n\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-light-border\"\n\t\t\t\t\t\t\tstyle=\"margin-left: 16px;\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PROVIDE_LATER') }}\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
    });

    var Timeline = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-timeline', {
      props: {
        readOnly: Boolean,
        selectedPrivateCode: {
          type: String,
          "default": null
        }
      },
      mixins: [Time],
      computed: {
        EntityGroup: function EntityGroup() {
          return timeman_const.EntityGroup;
        },
        Type: function Type() {
          return main_core.Type;
        },
        chartData: function chartData() {
          return this.$store.getters['monitor/getChartData'];
        },
        overChartData: function overChartData() {
          if (this.selectedPrivateCode) {
            return this.$store.getters['monitor/getOverChartData'](this.selectedPrivateCode);
          }
          return [];
        },
        legendData: function legendData() {
          return [{
            id: 1,
            type: timeman_const.EntityGroup.working.value,
            title: timeman_const.EntityGroup.working.title + ': ' + this.formatSeconds(this.workingTime)
          }, {
            id: 2,
            type: timeman_const.EntityGroup.personal.value,
            title: timeman_const.EntityGroup.personal.title + ': ' + this.formatSeconds(this.personalTime)
          }];
        }
      },
      methods: {
        onIntervalClick: function onIntervalClick(event) {
          this.$emit('intervalClick', event);
        }
      },
      // language=Vue
      template: "\n\t\t<div class=\"bx-timeman-component-monitor-timeline\">\n\t\t\t<bx-timeman-component-timeline\n\t\t\t\tv-if=\"Type.isArrayFilled(chartData)\"\n\t\t\t\t:chart=\"chartData\"\n\t\t\t\t:overChart=\"overChartData\"\n\t\t\t\t:legend=\"legendData\"\n\t\t\t\t:fixedSizeType=\"EntityGroup.inactive.value\"\n\t\t\t\t:readOnly=\"readOnly\"\n\t\t\t\t@intervalClick=\"onIntervalClick\"\n\t\t\t/>\n\t\t</div>\n\t"
    });

    var PausePopup = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-popup-pause', {
      props: {
        popupInstance: Object
      },
      mounted: function mounted() {
        this.popupInstance.show();
      },
      beforeDestroy: function beforeDestroy() {
        this.close();
      },
      methods: {
        hourPause: function hourPause() {
          var pauseUntilTime = new Date();
          pauseUntilTime.setHours(pauseUntilTime.getHours() + 1);
          pauseUntilTime.setSeconds(0);
          pauseUntilTime.setMilliseconds(0);
          this.pause(pauseUntilTime);
          this.close();
        },
        fourHourPause: function fourHourPause() {
          var pauseUntilTime = new Date();
          pauseUntilTime.setHours(pauseUntilTime.getHours() + 4);
          pauseUntilTime.setSeconds(0);
          pauseUntilTime.setMilliseconds(0);
          this.pause(pauseUntilTime);
          this.close();
        },
        dayPause: function dayPause() {
          var pauseUntilTime = new Date();
          pauseUntilTime.setDate(pauseUntilTime.getDate() + 1);
          pauseUntilTime.setHours(0);
          pauseUntilTime.setMinutes(0);
          pauseUntilTime.setSeconds(0);
          pauseUntilTime.setMilliseconds(0);
          this.pause(pauseUntilTime);
          this.close();
        },
        pause: function pause(dateTime) {
          this.$emit('monitorPause', dateTime);
        },
        close: function close() {
          this.popupInstance.destroy();
        }
      },
      //language=Vue
      template: "\n\t\t<div class=\"bx-timeman-monitor-report-popup-pause\">\n\t\t\t<button @click=\"hourPause\" class=\"ui-btn ui-btn-light ui-btn-no-caps bx-timeman-pwt-popup-pause-btn\">\n\t\t\t  {{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PAUSE_ONE_HOUR_BUTTON') }}\n\t\t\t</button>\n\t\t\t<button @click=\"fourHourPause\" class=\"ui-btn ui-btn-light ui-btn-no-caps bx-timeman-pwt-popup-pause-btn\">\n\t\t\t  {{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PAUSE_FOUR_HOURS_BUTTON') }}\n\t\t\t</button>\n\t\t\t<button @click=\"dayPause\" class=\"ui-btn ui-btn-light ui-btn-no-caps bx-timeman-pwt-popup-pause-btn\">\n\t\t\t  {{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PAUSE_UNTIL_TOMORROW_BUTTON') }}\n\t\t\t</button>\n\t\t</div>\n\t"
    });

    var ConfirmPopup = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-popup-confirm', {
      props: {
        popupInstance: Object,
        title: String,
        text: String,
        buttonOkTitle: String,
        buttonCancelTitle: String
      },
      mounted: function mounted() {
        this.popupInstance.show();
      },
      beforeDestroy: function beforeDestroy() {
        this.close();
      },
      methods: {
        ok: function ok() {
          this.$emit('okClick');
          this.close();
        },
        close: function close() {
          this.$emit('cancelClick');
          this.popupInstance.destroy();
        }
      },
      //language=Vue
      template: "\n\t\t<div class=\"bx-timeman-monitor-report-popup-confirm\">\n\t\t\t<div class=\"popup-window popup-window-with-titlebar ui-message-box ui-message-box-medium-buttons popup-window-fixed-width popup-window-fixed-height\" style=\"padding: 0\">\n\t\t\t\t<div class=\"bx-timeman-monitor-popup-title popup-window-titlebar\">\n\t\t\t\t\t<span class=\"bx-timeman-monitor-popup--titlebar-text popup-window-titlebar-text\">\n\t\t\t\t\t\t{{ title }}\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"popup-window-content\" style=\"overflow: auto; background: transparent;\">\n\t\t\t\t\t{{ text }}\n\t\t\t\t</div>\n\t\t\t\t<div class=\"popup-window-buttons\">\n\t\t\t\t\t<button @click=\"ok\" class=\"ui-btn ui-btn-success\">\n\t\t\t\t\t\t<span class=\"ui-btn-text\">\n\t\t\t\t\t\t\t{{ buttonOkTitle }}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</button>\n\t\t\t\t\t<button @click=\"close\" class=\"ui-btn ui-btn-light\">\n\t\t\t\t\t\t<span class=\"ui-btn-text\">\n\t\t\t\t\t\t\t{{ buttonCancelTitle }}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
    });

    var _templateObject;
    var Viewer = /*#__PURE__*/function () {
      function Viewer() {
        babelHelpers.classCallCheck(this, Viewer);
      }
      babelHelpers.createClass(Viewer, [{
        key: "open",
        value: function open(event) {
          var _this = this;
          this.report = null;
          var data = event.currentTarget.dataset;
          var userId = data.user;
          var dateLog = data.date;
          BX.SidePanel.Instance.open("timeman:pwt-report-viewer", {
            contentCallback: function contentCallback() {
              return _this.getAppPlaceholder();
            },
            animationDuration: 200,
            width: 750,
            closeByEsc: true,
            cacheable: false,
            title: main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_VIEWER_SLIDER_TITLE'),
            label: {
              text: main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_VIEWER_SLIDER_LABEL')
            },
            contentClassName: 'pwt-report-viewer-side-panel-content-container',
            events: {
              onLoad: function onLoad() {
                _this.loadReport(userId, dateLog).then(function (response) {
                  if (response.status === 'success') {
                    _this.report = response.data;
                    if (!timeman_dateformatter.DateFormatter.isInit()) {
                      timeman_dateformatter.DateFormatter.init(_this.report.info.date.format);
                    }
                    _this.createApp(_this.report);
                  }
                })["catch"](function (response) {
                  if (response.errors) {
                    response.errors.forEach(function (error) {
                      console.error(error.message);
                    });
                  }
                });
              }
            }
          });
        }
      }, {
        key: "loadReport",
        value: function loadReport(userId, dateLog) {
          return BX.ajax.runAction('bitrix:timeman.api.monitor.getdayreport', {
            data: {
              userId: userId,
              dateLog: dateLog
            }
          });
        }
      }, {
        key: "getAppPlaceholder",
        value: function getAppPlaceholder() {
          return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div id=\"pwt\">\n\t\t\t\t\t\t<div \n\t\t\t\t\t\t\tclass=\"main-ui-loader main-ui-show\" \n\t\t\t\t\t\t\tstyle=\"width: 110px; height: 110px;\" \n\t\t\t\t\t\t\tdata-is-shown=\"true\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<svg class=\"main-ui-loader-svg\" viewBox=\"25 25 50 50\">\n\t\t\t\t\t\t\t\t<circle \n\t\t\t\t\t\t\t\t\tclass=\"main-ui-loader-svg-circle\" \n\t\t\t\t\t\t\t\t\tcx=\"50\" \n\t\t\t\t\t\t\t\t\tcy=\"50\" \n\t\t\t\t\t\t\t\t\tr=\"20\" \n\t\t\t\t\t\t\t\t\tfill=\"none\" \n\t\t\t\t\t\t\t\t\tstroke-miterlimit=\"10\"\n\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t"])));
        }
      }, {
        key: "createApp",
        value: function createApp(report) {
          var reports = {};
          var dateLog = report.info.date.value;
          reports[dateLog] = report;
          ui_vue.BitrixVue.createApp({
            components: {
              Timeline: Timeline,
              Group: Group
            },
            data: function data() {
              return {
                dateLog: dateLog,
                reports: reports
              };
            },
            computed: {
              Type: function Type() {
                return main_core.Type;
              },
              EntityGroup: function EntityGroup() {
                return timeman_const.EntityGroup;
              },
              DateFormatter: function DateFormatter() {
                return timeman_dateformatter.DateFormatter;
              },
              report: function report() {
                return this.reports[this.dateLog];
              },
              date: function date() {
                return this.report.info.date.value;
              },
              userId: function userId() {
                return this.report.info.user.id;
              },
              userName: function userName() {
                return this.report.info.user.fullName;
              },
              userIcon: function userIcon() {
                return this.report.info.user.icon;
              },
              userLink: function userLink() {
                return this.report.info.user.link;
              },
              reportComment: function reportComment() {
                if (!main_core.Type.isArrayFilled(this.report.info.reportComment)) {
                  return null;
                }
                return this.report.info.reportComment[0].TEXT;
              },
              chartData: function chartData() {
                if (this.report.timeline === null) {
                  return [];
                }
                return this.report.timeline.data.map(function (interval) {
                  interval.start = new Date(interval.start);
                  interval.finish = new Date(interval.finish);
                  return interval;
                });
              },
              workingEntities: function workingEntities() {
                if (this.report.report === null) {
                  return [];
                }
                return this.report.report.data;
              },
              workingTime: function workingTime() {
                if (this.report.report === null) {
                  return [];
                }
                return this.report.report.data.reduce(function (sum, entity) {
                  return sum + entity.time;
                }, 0);
              },
              canShowRightEar: function canShowRightEar() {
                return !(this.dateLog === timeman_dateformatter.DateFormatter.toString(new Date()));
              }
            },
            methods: {
              getPreviousReport: function getPreviousReport() {
                var dateLog = new Date(this.date);
                dateLog.setDate(dateLog.getDate() - 1);
                this.getReport(timeman_dateformatter.DateFormatter.toString(dateLog));
              },
              getNextReport: function getNextReport() {
                var dateLog = new Date(this.date);
                dateLog.setDate(dateLog.getDate() + 1);
                this.getReport(timeman_dateformatter.DateFormatter.toString(dateLog));
              },
              getReport: function getReport(dateLog) {
                var _this2 = this;
                if (this.reports[dateLog]) {
                  this.dateLog = dateLog;
                } else {
                  this.loadReport(this.userId, dateLog).then(function (response) {
                    if (response.status === 'success') {
                      var _dateLog = response.data.info.date.value;
                      _this2.reports[_dateLog] = response.data;
                      _this2.dateLog = _dateLog;
                    }
                  })["catch"](function (response) {
                    if (response.errors) {
                      response.errors.forEach(function (error) {
                        console.error(error.message);
                      });
                    }
                  });
                }
              },
              loadReport: function loadReport(userId, dateLog) {
                return BX.ajax.runAction('bitrix:timeman.api.monitor.getdayreport', {
                  data: {
                    userId: userId,
                    dateLog: dateLog
                  }
                });
              }
            },
            // language=Vue
            template: "\n\t\t\t\t<div id=\"pwt-report-container-viewer\" class=\"pwt-report-container pwt-report-container-viewer\">\n\t\t\t\t\t<div class=\"pwt-report pwt-report-viewer\">\n\t\t\t\t\t\t<div class=\"pwt-report-content\">\n\t\t\t\t\t\t\t<div class=\"pwt-report-content-header\" style=\"margin-bottom: 0\">\n\t\t\t\t\t\t\t\t<div class=\"ui-icon ui-icon-common-user pwt-report-content-header-user-icon\">\n\t\t\t\t\t\t\t\t\t<i v-if=\"userIcon\" :style=\"{backgroundImage: 'url(' + encodeURI(userIcon) + ')'}\"></i>\n\t\t\t\t\t\t\t\t\t<i v-else-if=\"!userIcon\"></i>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<a class=\"pwt-report-content-header-title\" :href=\"userLink\">\n\t\t\t\t\t\t\t\t\t{{ userName }}\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"pwt-report-content-container\">\n\t\t\t\t\t\t\t<div class=\"pwt-report-content\">\n\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-header\">\n\t\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-header-title\">\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_WORKDAY') }}, \n\t\t\t\t\t\t\t\t\t\t{{ DateFormatter.toLong(date) }}\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"bx-timeman-component-monitor-timeline\">\n\t\t\t\t\t\t\t\t\t<bx-timeman-component-timeline\n\t\t\t\t\t\t\t\t\t\tv-if=\"Type.isArrayFilled(chartData)\"\n\t\t\t\t\t\t\t\t\t\t:chart=\"chartData\"\n\t\t\t\t\t\t\t\t\t\t:fixedSizeType=\"EntityGroup.inactive.value\"\n\t\t\t\t\t\t\t\t\t\t:readOnly=\"true\"\n\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"pwt-report-content\">\n\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-viewer-single-group\">\n\t\t\t\t\t\t\t\t\t<Group\n\t\t\t\t\t\t\t\t\t\t:group=\"EntityGroup.working.value\"\n\t\t\t\t\t\t\t\t\t\t:items=\"workingEntities\"\n\t\t\t\t\t\t\t\t\t\t:time=\"workingTime\"\n\t\t\t\t\t\t\t\t\t\t:reportComment=\"reportComment\"\n\t\t\t\t\t\t\t\t\t\t:readOnly=\"true\"\n\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"pwt-report-viewer-ears\">\n\t\t\t\t\t\t<div\n                            @click=\"getPreviousReport\"\n\t\t\t\t\t\t\tclass=\"\n\t\t\t\t\t\t\t\tpwt-report-viewer-ear \n\t\t\t\t\t\t\t\tpwt-report-viewer-ear-left \n\t\t\t\t\t\t\t\tpwt-report-viewer-ear-show\n\t\t\t\t\t\t\t\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t\t<div \n\t\t\t\t\t\t\tv-if=\"canShowRightEar\"\n\t\t\t\t\t\t\t@click=\"getNextReport\"\n\t\t\t\t\t\t\tclass=\"\n\t\t\t\t\t\t\t\tpwt-report-viewer-ear \n\t\t\t\t\t\t\t\tpwt-report-viewer-ear-right \n\t\t\t\t\t\t\t\tpwt-report-viewer-ear-show\n\t\t\t\t\t\t\t\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"
          }).mount('#pwt');
        }
      }]);
      return Viewer;
    }();
    var viewer = new Viewer();

    var _templateObject$1;
    function ownKeys$1(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
    function _objectSpread$1(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$1(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$1(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
    var MonitorReport = /*#__PURE__*/function () {
      function MonitorReport() {
        babelHelpers.classCallCheck(this, MonitorReport);
      }
      babelHelpers.createClass(MonitorReport, [{
        key: "open",
        value: function open(store) {
          var _this = this;
          if (this.isReportOpen) {
            return;
          }
          BX.SidePanel.Instance.open("timeman:pwt-report", {
            contentCallback: function contentCallback() {
              return _this.getAppPlaceholder();
            },
            animationDuration: 200,
            width: 960,
            closeByEsc: true,
            title: main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_DAY'),
            events: {
              onOpen: function onOpen() {
                if (!BXIM || !BXIM.desktop) {
                  return;
                }
                if (main_core.Type.isFunction(BXIM.desktop.setPreventEsc)) {
                  BXIM.desktop.setPreventEsc(true);
                }
              },
              onOpenComplete: function onOpenComplete() {
                _this.isReportOpen = true;
              },
              onLoad: function onLoad() {
                return _this.createEditor(store);
              },
              onCloseComplete: function onCloseComplete() {
                _this.isReportOpen = false;
                if (timeman_monitor.Monitor.shouldShowGrantingPermissionWindow()) {
                  timeman_monitor.Monitor.showGrantingPermissionLater();
                }
                if (!BXIM || !BXIM.desktop) {
                  return;
                }
                if (main_core.Type.isFunction(BXIM.desktop.setPreventEsc)) {
                  BXIM.desktop.setPreventEsc(false);
                }
              }
            }
          });
        }
      }, {
        key: "createEditor",
        value: function createEditor(store) {
          this.createEditorApp(store);
        }
      }, {
        key: "openPreview",
        value: function openPreview(store) {
          var _this2 = this;
          if (this.isReportPreviewOpen) {
            return;
          }
          BX.SidePanel.Instance.open("timeman:pwt-report-preview", {
            contentCallback: function contentCallback() {
              return _this2.getAppPlaceholder();
            },
            animationDuration: 200,
            width: 750,
            closeByEsc: true,
            title: main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_DAY'),
            label: {
              text: main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_PREVIEW_SLIDER_LABEL')
            },
            events: {
              onOpenComplete: function onOpenComplete() {
                _this2.isReportPreviewOpen = true;
              },
              onLoad: function onLoad() {
                return _this2.createPreview(store);
              },
              onCloseComplete: function onCloseComplete() {
                _this2.isReportPreviewOpen = false;
              }
            }
          });
        }
      }, {
        key: "createPreview",
        value: function createPreview(store) {
          this.createPreviewApp(store);
        }
      }, {
        key: "openViewer",
        value: function openViewer(event) {
          viewer.open(event);
        }
      }, {
        key: "getAppPlaceholder",
        value: function getAppPlaceholder() {
          return main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div id=\"pwt\">\n\t\t\t\t\t\t<div \n\t\t\t\t\t\t\tclass=\"main-ui-loader main-ui-show\" \n\t\t\t\t\t\t\tstyle=\"width: 110px; height: 110px;\" \n\t\t\t\t\t\t\tdata-is-shown=\"true\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<svg class=\"main-ui-loader-svg\" viewBox=\"25 25 50 50\">\n\t\t\t\t\t\t\t\t<circle \n\t\t\t\t\t\t\t\t\tclass=\"main-ui-loader-svg-circle\" \n\t\t\t\t\t\t\t\t\tcx=\"50\" \n\t\t\t\t\t\t\t\t\tcy=\"50\" \n\t\t\t\t\t\t\t\t\tr=\"20\" \n\t\t\t\t\t\t\t\t\tfill=\"none\" \n\t\t\t\t\t\t\t\t\tstroke-miterlimit=\"10\"\n\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t"])));
        }
      }, {
        key: "createEditorApp",
        value: function createEditorApp(store) {
          ui_vue.BitrixVue.createApp({
            components: {
              Timeline: Timeline,
              Group: Group,
              AddIntervalPopup: AddIntervalPopup,
              SelectIntervalPopup: SelectIntervalPopup,
              Consent: Consent,
              MountingPortal: ui_vue_portal.MountingPortal,
              PausePopup: PausePopup,
              ConfirmPopup: ConfirmPopup
            },
            store: store,
            mixins: [Time],
            data: function data() {
              return {
                newInterval: null,
                showSelectInternalPopup: false,
                popupInstance: null,
                popupId: null,
                showPlayAlert: false,
                selectedPrivateCode: null,
                selectIntervalTimeout: null
              };
            },
            computed: _objectSpread$1(_objectSpread$1({}, ui_vuex.Vuex.mapGetters('monitor', ['getWorkingEntities', 'getPersonalEntities', 'getReportComment', 'hasActivityOtherThanBitrix24'])), {}, {
              EntityGroup: function EntityGroup() {
                return timeman_const.EntityGroup;
              },
              TimeFormatter: function TimeFormatter() {
                return timeman_timeformatter.TimeFormatter;
              },
              dateLog: function dateLog() {
                return timeman_dateformatter.DateFormatter.toLong(new Date(this.$store.state.monitor.reportState.dateLog));
              },
              isHistorySent: function isHistorySent() {
                return !!this.$store.getters['monitor/isHistorySent'];
              },
              isPermissionGranted: function isPermissionGranted() {
                return this.$store.state.monitor.config.grantingPermissionDate !== null;
              },
              isPaused: function isPaused() {
                return !!this.$store.state.monitor.config.pausedUntil;
              },
              pausedUntil: function pausedUntil() {
                var pausedUntil = this.$store.state.monitor.config.pausedUntil;
                if (!pausedUntil) {
                  return '';
                }
                if (pausedUntil.getDay() - new Date().getDay() !== 0) {
                  return this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_TOMORROW');
                }
                return timeman_timeformatter.TimeFormatter.toShort(pausedUntil);
              },
              isWindows: function isWindows() {
                return navigator.userAgent.toLowerCase().includes('windows') || !this.isMac && !this.isLinux;
              },
              isMac: function isMac() {
                return navigator.userAgent.toLowerCase().includes('macintosh');
              },
              isLinux: function isLinux() {
                return navigator.userAgent.toLowerCase().includes('linux');
              },
              hasActivity: function hasActivity() {
                if (this.isMac) {
                  return this.hasActivityOtherThanBitrix24;
                }
                return true;
              },
              hasIntervalsToAdd: function hasIntervalsToAdd() {
                return main_core.Type.isArrayFilled(this.$store.getters['monitor/getChartData'].filter(function (interval) {
                  return interval.type === timeman_const.EntityGroup.inactive.value && interval.start < new Date();
                }));
              }
            }),
            methods: {
              onIntervalClick: function onIntervalClick(event) {
                this.newInterval = event;
              },
              onAddIntervalPopupHide: function onAddIntervalPopupHide() {
                this.newInterval = null;
              },
              onAddIntervalPopupClose: function onAddIntervalPopupClose() {
                this.newInterval = null;
                this.showSelectInternalPopup = false;
              },
              onSelectIntervalClick: function onSelectIntervalClick() {
                this.showSelectInternalPopup = true;
              },
              onSelectIntervalPopupCloseClick: function onSelectIntervalPopupCloseClick() {
                this.showSelectInternalPopup = false;
              },
              pauseClick: function pauseClick(event) {
                var _this3 = this;
                if (this.popupInstance != null) {
                  this.popupInstance.destroy();
                  this.popupInstance = null;
                }
                var popup = main_popup.PopupManager.create({
                  id: 'bx-timeman-pwt-editor-pause-popup',
                  targetContainer: document.body,
                  className: 'bx-timeman-pwt-pause-popup',
                  bindElement: event.target,
                  lightShadow: true,
                  offsetTop: 0,
                  offsetLeft: 10,
                  autoHide: true,
                  closeByEsc: true,
                  angle: {},
                  bindOptions: {
                    position: 'top'
                  },
                  events: {
                    onPopupClose: function onPopupClose() {
                      return _this3.popupInstance.destroy();
                    },
                    onPopupDestroy: function onPopupDestroy() {
                      return _this3.popupInstance = null;
                    }
                  }
                });
                this.popupIdSelector = "#bx-timeman-pwt-editor-pause-popup";
                this.popupId = 'PausePopup';

                //little hack for correct open several popups in a row.
                this.$nextTick(function () {
                  _this3.popupInstance = popup;
                });
              },
              pause: function pause(dateTime) {
                timeman_monitor.Monitor.pauseUntil(dateTime);
              },
              play: function play() {
                var _this4 = this;
                timeman_monitor.Monitor.play();
                this.showPlayAlert = true;
                setTimeout(function () {
                  return _this4.showPlayAlert = false;
                }, 1000);
              },
              openReportPreview: function openReportPreview() {
                timeman_monitor.Monitor.openReportPreview();
              },
              selectInterval: function selectInterval(privateCode) {
                var _this5 = this;
                this.selectIntervalTimeout = setTimeout(function () {
                  _this5.selectedPrivateCode = privateCode;
                }, 500);
              },
              unselectInterval: function unselectInterval() {
                clearTimeout(this.selectIntervalTimeout);
                this.selectedPrivateCode = null;
              },
              openPermissionHelp: function openPermissionHelp() {
                this.openHelpdesk('13857358');
              },
              openSkipConfirm: function openSkipConfirm() {
                var _this6 = this;
                if (this.popupInstance != null) {
                  this.popupInstance.destroy();
                  this.popupInstance = null;
                }
                var popup = main_popup.PopupManager.create({
                  id: 'bx-timeman-pwt-skip-report-confirm-popup',
                  targetContainer: BX('pwt-report-container-editor'),
                  autoHide: false,
                  closeByEsc: true,
                  overlay: true,
                  events: {
                    onPopupDestroy: function onPopupDestroy() {
                      _this6.popupInstance = null;
                    }
                  }
                });
                this.popupIdSelector = "#bx-timeman-pwt-skip-report-confirm-popup";
                this.popupId = 'SkipReportPopup';

                //little hack for correct open several popups in a row.
                this.$nextTick(function () {
                  _this6.popupInstance = popup;
                });
              },
              skipReport: function skipReport() {
                var _this7 = this;
                this.$store.dispatch('monitor/clearStorageBeforeDate', this.$store.state.monitor.reportState.dateLog).then(function () {
                  var notifyText = _this7.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_NOTIFICATION_REPORT_SKIPPED').replace('#DATE#', _this7.dateLog);
                  _this7.$store.dispatch('monitor/refreshDateLog').then(function () {
                    ui_notification.UI.Notification.Center.notify({
                      content: notifyText,
                      autoHideDelay: 5000
                    });
                  });
                });
              },
              openPwtHelp: function openPwtHelp() {
                // if (this.isMac)
                // {
                // 	this.openHelpdesk('');
                // 	return;
                // }
                //
                // if (this.isWindows)
                // {
                // 	this.openHelpdesk('');
                // 	return;
                // }

                this.openPermissionHelp();
              },
              openHelpdesk: function openHelpdesk(code) {
                if (top.BX.Helper) {
                  top.BX.Helper.show('redirect=detail&code=' + code);
                }
              }
            },
            // language=Vue
            template: "\n\t\t\t\t<div id=\"pwt-report-container-editor\" class=\"pwt-report-container\">\n\t\t\t\t\t<div class=\"pwt-report\">\n\t\t\t\t\t\t<Consent v-if=\"!isPermissionGranted\"/>\n\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t<div class=\"pwt-report-header-container\">\n\t\t\t\t\t\t\t\t<div class=\"pwt-report-header-title\">\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SLIDER_TITLE') }}\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"pwt-report-header-buttons-container\">\n\t\t\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-light-border\"\n\t\t\t\t\t\t\t\t\t\t@click=\"openPwtHelp\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_VIEWER_HELP_BUTTON_TITLE') }}\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t \t<transition-group\n\t\t\t\t\t\t\t\tname=\"bx-timeman-pwt-report\"\n\t\t\t\t\t\t\t\ttag=\"div\"\n\t\t\t\t\t\t\t\tclass=\"pwt-report-content-container\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\t\t:key=\"'reportNotSentAlert'\"\n\t\t\t\t\t\t\t\t\tv-if=\"!isHistorySent\"\n\t\t\t\t\t\t\t\t\tclass=\"pwt-report-alert ui-alert ui-alert-md ui-alert-danger ui-alert-icon-danger\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<span class=\"ui-alert-message\">\n\t\t\t\t\t\t\t\t\t\t{{ \n\t\t\t\t\t\t\t\t\t\t\t$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_ALERT_NOT_SENT')\n\t\t\t\t\t\t\t\t\t\t\t\t.replace('#DATE#', dateLog)\n\t\t\t\t\t\t\t\t\t\t}}\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div \n\t\t\t\t\t\t\t\t\t:key=\"'pauseAlert'\"\n\t\t\t\t\t\t\t\t\tv-if=\"isPaused || showPlayAlert\"\n\t\t\t\t\t\t\t\t\t:class=\"[\n\t\t\t\t\t\t\t\t\t\t'pwt-report-alert',\n\t\t\t\t\t\t\t\t\t\t'ui-alert',\n\t\t\t\t\t\t\t\t\t\t'ui-alert-md',\n\t\t\t\t\t\t\t\t\t\t{\n\t\t\t\t\t\t\t\t\t\t\t'ui-alert-warning ui-alert-icon-warning': isPaused, \n\t\t\t\t\t\t\t\t\t\t\t'ui-alert-success ui-alert-icon-info' : showPlayAlert,\n\t\t\t\t\t\t\t\t\t\t}\n\t\t\t\t\t\t\t\t\t]\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<span v-if=\"isPaused\" class=\"ui-alert-message\">\n\t\t\t\t\t\t\t\t\t\t{{ \n\t\t\t\t\t\t\t\t\t\t\t$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_ALERT_PAUSE_UNTIL_TIME')\n\t\t\t\t\t\t\t\t\t\t\t\t.replace('#TIME#', pausedUntil)\n\t\t\t\t\t\t\t\t\t\t}}\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t<span v-if=\"showPlayAlert\" class=\"ui-alert-message\">\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_ALERT_PLAY') }}\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t\tv-if=\"isPaused\"\n\t\t\t\t\t\t\t\t\t\t@click=\"play\"\n\t\t\t\t\t\t\t\t\t\tclass=\"\n\t\t\t\t\t\t\t\t\t\t\tui-btn \n\t\t\t\t\t\t\t\t\t\t\tui-btn-xs \n\t\t\t\t\t\t\t\t\t\t\tui-btn-success-dark\n\t\t\t\t\t\t\t\t\t\t\tui-btn-round \n\t\t\t\t\t\t\t\t\t\t\tui-btn-icon-start\n\t\t\t\t\t\t\t\t\t\t\tbx-monitor-group-btn-right\n\t\t\t\t\t\t\t\t\t\t\tbx-monitor-alert-btn-right\n\t\t\t\t\t\t\t\t\t\t\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PLAY') }}\n\t\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\t\t:key=\"'activityAlert'\"\n\t\t\t\t\t\t\t\t\tv-if=\"!hasActivity\"\n\t\t\t\t\t\t\t\t\tclass=\"pwt-report-alert ui-alert ui-alert-icon-info ui-alert-md\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<span class=\"ui-alert-message\">\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_VIEWER_MAC_INACTIVE_ALERT') }}\n\t\t\t\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t\t\t\tclass=\"pwt-report-alert-link\"\n\t\t\t\t\t\t\t\t\t\t\t@click=\"openPermissionHelp\"\n\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_VIEWER_MAC_HELP_DETAIL') }}\n\t\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t\t\t<div class=\"pwt-report-content\" :key=\"'report-header'\">\n\t\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-header\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-header-title\">\n\t\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_WORKDAY') }}, {{ dateLog }}\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<Timeline\n\t\t\t\t\t\t\t\t\t\t:selectedPrivateCode=\"selectedPrivateCode\"\n\t\t\t\t\t\t\t\t\t\t@intervalClick=\"onIntervalClick\"\n\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"pwt-report-content\" :key=\"'report-content'\">\n\t\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-groups\">\n\t\t\t\t\t\t\t\t\t\t<Group \n\t\t\t\t\t\t\t\t\t\t\t:group=\"EntityGroup.working.value\"\n\t\t\t\t\t\t\t\t\t\t\t:items=\"getWorkingEntities\"\n\t\t\t\t\t\t\t\t\t\t\t:time=\"workingTime\"\n\t\t\t\t\t\t\t\t\t\t\t:reportComment=\"getReportComment\"\n\t\t\t\t\t\t\t\t\t\t\t:hasIntervalsToAdd=\"hasIntervalsToAdd\"\n\t\t\t\t\t\t\t\t\t\t\t@selectIntervalClick=\"onSelectIntervalClick\"\n\t\t\t\t\t\t\t\t\t\t\t@intervalSelected=\"selectInterval\"\n\t\t\t\t\t\t\t\t\t\t\t@intervalUnselected=\"unselectInterval\"\n\t\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t\t\t<Group \n\t\t\t\t\t\t\t\t\t\t\t:group=\"EntityGroup.personal.value\"\n\t\t\t\t\t\t\t\t\t\t\t:items=\"getPersonalEntities\"\n\t\t\t\t\t\t\t\t\t\t\t:time=\"personalTime\"\n\t\t\t\t\t\t\t\t\t\t\t@intervalSelected=\"selectInterval\"\n\t\t\t\t\t\t\t\t\t\t\t@intervalUnselected=\"unselectInterval\"\n\t\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</transition-group>\n\t\n\t\t\t\t\t\t\t<div \n\t\t\t\t\t\t\t\tclass=\"\n\t\t\t\t\t\t\t\t\tpwt-report-button-panel-wrapper \n\t\t\t\t\t\t\t\t\tui-pinner \n\t\t\t\t\t\t\t\t\tui-pinner-bottom \n\t\t\t\t\t\t\t\t\tui-pinner-full-width\" \n\t\t\t\t\t\t\t\tstyle=\"z-index: 0\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<div class=\"pwt-report-button-panel\">\n\t\t\t\t\t\t\t\t\t<button \n\t\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-success ui-btn-icon-page\"\n\t\t\t\t\t\t\t\t\t\t@click=\"openReportPreview\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PREVIEW_BUTTON') }}\n\t\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t\tid=\"timeman-pwt-button-pause\"\n\t\t\t\t\t\t\t\t\t\t@click=\"pauseClick\"\n\t\t\t\t\t\t\t\t\t\tclass=\"\n\t\t\t\t\t\t\t\t\t\t\tui-btn \n\t\t\t\t\t\t\t\t\t\t\tui-btn-light-border \n\t\t\t\t\t\t\t\t\t\t\tui-btn-dropdown \n\t\t\t\t\t\t\t\t\t\t\tui-btn-icon-pause\n\t\t\t\t\t\t\t\t\t\t\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PAUSE_BUTTON') }}\n\t\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t\tv-if=\"!isHistorySent\"\n\t\t\t\t\t\t\t\t\t\t@click=\"openSkipConfirm\"\n\t\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-danger ui-btn-icon-remove\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SKIP_BUTTON') }}\n\t\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t\t<mounting-portal \n\t\t\t\t\t\t\t\t\t\t:mount-to=\"popupIdSelector\" \n\t\t\t\t\t\t\t\t\t\tappend \n\t\t\t\t\t\t\t\t\t\tv-if=\"popupInstance\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t<PausePopup\n\t\t\t\t\t\t\t\t\t\t\tv-if=\"popupId === 'PausePopup'\"\n\t\t\t\t\t\t\t\t\t\t\t:popupInstance=\"popupInstance\" \n\t\t\t\t\t\t\t\t\t\t\t@monitorPause=\"pause\"\n\t\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t\t\t<ConfirmPopup\n                                            v-if=\"popupId === 'SkipReportPopup'\"\n\t\t\t\t\t\t\t\t\t\t\t:popupInstance=\"popupInstance\"\n\t\t\t\t\t\t\t\t\t\t\t:title=\"$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_NOTIFICATION_REPORT_SKIP_POPUP_TITLE')\"\n\t\t\t\t\t\t\t\t\t\t\t:text=\"$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_NOTIFICATION_REPORT_SKIP_POPUP_TEXT')\"\n\t\t\t\t\t\t\t\t\t\t\t:buttonOkTitle=\"$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SKIP_CONFIRM_BUTTON')\"\n\t\t\t\t\t\t\t\t\t\t\t:buttonCancelTitle=\"$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CANCEL_BUTTON')\"\n\t\t\t\t\t\t\t\t\t\t\t@okClick=\"skipReport\"\n\t\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t\t</mounting-portal>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\n\t\t\t\t\t\t\t<div id=\"bx-timeman-pwt-popup-editor\" class=\"bx-timeman-pwt-popup\">\n\t\t\t\t\t\t\t\t<SelectIntervalPopup\n\t\t\t\t\t\t\t\t\tv-if=\"showSelectInternalPopup\"\n\t\t\t\t\t\t\t\t\t@selectIntervalPopupCloseClick=\"onSelectIntervalPopupCloseClick\"\n\t\t\t\t\t\t\t\t\t@intervalSelected=\"onIntervalClick\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t<AddIntervalPopup\n\t\t\t\t\t\t\t\t\tv-if=\"newInterval\"\n\t\t\t\t\t\t\t\t\t:minStart=\"newInterval.start\"\n\t\t\t\t\t\t\t\t\t:maxFinish=\"newInterval.finish\"\n\t\t\t\t\t\t\t\t\t@addIntervalPopupClose=\"onAddIntervalPopupClose\"\n\t\t\t\t\t\t\t\t\t@addIntervalPopupHide=\"onAddIntervalPopupHide\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"
          }).mount('#pwt');
        }
      }, {
        key: "createPreviewApp",
        value: function createPreviewApp(store) {
          ui_vue.BitrixVue.createApp({
            components: {
              Timeline: Timeline,
              Group: Group,
              MountingPortal: ui_vue_portal.MountingPortal
            },
            mixins: [Time],
            data: function data() {
              return {
                popupIdSelector: false,
                popupInstance: null
              };
            },
            store: store,
            computed: _objectSpread$1(_objectSpread$1({}, ui_vuex.Vuex.mapGetters('monitor', ['getWorkingEntities', 'getReportComment'])), {}, {
              EntityGroup: function EntityGroup() {
                return timeman_const.EntityGroup;
              },
              dateLog: function dateLog() {
                return timeman_dateformatter.DateFormatter.toLong(new Date(this.$store.state.monitor.reportState.dateLog));
              }
            }),
            methods: {
              sendReport: function sendReport() {
                timeman_monitor.Monitor.send();
              },
              close: function close() {
                BX.SidePanel.Instance.close();
              }
            },
            // language=Vue
            template: "\n\t\t\t\t<div id=\"pwt-report-container-preview\" class=\"pwt-report-container\">\n\t\t\t\t\t<div class=\"pwt-report\">\n\t\t\t\t\t\t<div class=\"pwt-report-content\">\n\t\t\t\t\t\t\t<div class=\"pwt-report-content-header\" style=\"margin-bottom: 0\">\n\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-header-title\">\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PREVIEW_SLIDER_TITLE') }}\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"pwt-report-content-container\">\n\t\t\t\t\t\t\t<div class=\"pwt-report-content\">\n\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-header\">\n\t\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-header-title\">\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_WORKDAY') }}, {{ dateLog }}\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<Timeline\n\t\t\t\t\t\t\t\t\t:readOnly=\"true\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"pwt-report-content\">\n\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-groups\">\n\t\t\t\t\t\t\t\t\t<Group \n\t\t\t\t\t\t\t\t\t\t:group=\"EntityGroup.working.value\"\n\t\t\t\t\t\t\t\t\t\t:items=\"this.getWorkingEntities\"\n\t\t\t\t\t\t\t\t\t\t:time=\"this.workingTime\"\n\t\t\t\t\t\t\t\t\t\t:reportComment=\"this.getReportComment\"\n\t\t\t\t\t\t\t\t\t\t:readOnly=\"true\"\n\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"pwt-report-button-panel-wrapper ui-pinner ui-pinner-bottom ui-pinner-full-width\" style=\"z-index: 0\">\n\t\t\t\t\t\t\t<div class=\"pwt-report-button-panel\">\n\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t@click=\"sendReport\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-success ui-btn-icon-share\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SEND_BUTTON') }}\n\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t@click=\"close\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-light-border\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CANCEL_BUTTON') }}\n\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div id=\"bx-timeman-pwt-popup-preview\" class=\"bx-timeman-pwt-popup\"/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"
          }).mount('#pwt');
        }
      }]);
      return MonitorReport;
    }();
    var monitorReport = new MonitorReport();

    exports.MonitorReport = monitorReport;

}((this.BX.Timeman = this.BX.Timeman || {}),BX,BX.UI,BX,window,BX.UI.Dialogs,BX,BX,BX.Timeman.Component,BX.Timeman,BX.Timeman,BX.Vue,BX,BX,BX,BX.Timeman.Const,BX.Timeman,BX.Main,BX));
//# sourceMappingURL=monitor-report.bundle.js.map
