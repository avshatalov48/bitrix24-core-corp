/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,main_popup,ui_vue3_vuex,booking_lib_duration,booking_const,main_date) {
	'use strict';

	var _isIncorrectTimeValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isIncorrectTimeValue");
	var _beautifyTime = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("beautifyTime");
	var _getMinutesAndHours = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMinutesAndHours");
	var _clearTimeString = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clearTimeString");
	var _areTimeDigitsCorrect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("areTimeDigitsCorrect");
	var _formatHours = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("formatHours");
	var _formatMinutes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("formatMinutes");
	var _getMaxHours = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMaxHours");
	class TimeFormatter {
	  constructor() {
	    Object.defineProperty(this, _getMaxHours, {
	      value: _getMaxHours2
	    });
	    Object.defineProperty(this, _formatMinutes, {
	      value: _formatMinutes2
	    });
	    Object.defineProperty(this, _formatHours, {
	      value: _formatHours2
	    });
	    Object.defineProperty(this, _areTimeDigitsCorrect, {
	      value: _areTimeDigitsCorrect2
	    });
	    Object.defineProperty(this, _clearTimeString, {
	      value: _clearTimeString2
	    });
	    Object.defineProperty(this, _getMinutesAndHours, {
	      value: _getMinutesAndHours2
	    });
	    Object.defineProperty(this, _beautifyTime, {
	      value: _beautifyTime2
	    });
	    Object.defineProperty(this, _isIncorrectTimeValue, {
	      value: _isIncorrectTimeValue2
	    });
	  }
	  parseTime(value, previousTimestamp) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isIncorrectTimeValue)[_isIncorrectTimeValue](value)) {
	      return previousTimestamp;
	    }
	    let time = this.getMaskedTime(value);
	    time = babelHelpers.classPrivateFieldLooseBase(this, _beautifyTime)[_beautifyTime](time);
	    if (main_date.DateTimeFormat.isAmPmMode()) {
	      var _value$toLowerCase$ma;
	      let amPmSymbol = ((_value$toLowerCase$ma = value.toLowerCase().match(/[ap]/g)) != null ? _value$toLowerCase$ma : []).pop();
	      if (!amPmSymbol) {
	        const hour = Number(babelHelpers.classPrivateFieldLooseBase(this, _getMinutesAndHours)[_getMinutesAndHours](time).hours);
	        if (hour >= 8 && hour <= 11) {
	          amPmSymbol = 'a';
	        } else {
	          amPmSymbol = 'p';
	        }
	      }
	      if (amPmSymbol === 'a') {
	        time += ' am';
	      }
	      if (amPmSymbol === 'p') {
	        time += ' pm';
	      }
	    }
	    return new Date(`${main_date.DateTimeFormat.format('Y-m-d', previousTimestamp / 1000)} ${time}`).getTime();
	  }
	  getMaskedTime(value, key) {
	    let time = '';
	    const {
	      hours,
	      minutes
	    } = babelHelpers.classPrivateFieldLooseBase(this, _getMinutesAndHours)[_getMinutesAndHours](value, key);
	    if (hours && !minutes) {
	      time = String(hours);
	      if (value.length - time.length === 1 || value.includes(':')) {
	        time += ':';
	      }
	    }
	    if (hours && minutes) {
	      time = `${hours}:${minutes}`;
	    }
	    if (main_date.DateTimeFormat.isAmPmMode() && babelHelpers.classPrivateFieldLooseBase(this, _clearTimeString)[_clearTimeString](time) !== '') {
	      var _value$toLowerCase$ma2;
	      const amPmSymbol = ((_value$toLowerCase$ma2 = value.toLowerCase().match(/[ap]/g)) != null ? _value$toLowerCase$ma2 : []).pop();
	      if (amPmSymbol === 'a') {
	        time = `${babelHelpers.classPrivateFieldLooseBase(this, _beautifyTime)[_beautifyTime](time)} am`;
	      }
	      if (amPmSymbol === 'p') {
	        time = `${babelHelpers.classPrivateFieldLooseBase(this, _beautifyTime)[_beautifyTime](time)} pm`;
	      }
	    }
	    return time;
	  }
	  formatTime(timestamp) {
	    const format = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	    return main_date.DateTimeFormat.format(format, timestamp / 1000);
	  }
	}
	function _isIncorrectTimeValue2(timeValue) {
	  if (main_date.DateTimeFormat.isAmPmMode()) {
	    return timeValue === '';
	  }
	  const date = new Date(`${main_date.DateTimeFormat.format('Y-m-d')} ${timeValue}`);
	  return timeValue === '' || timeValue[0] !== '0' && date.getHours() === 0;
	}
	function _beautifyTime2(time) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _clearTimeString)[_clearTimeString](time) === '') {
	    return '';
	  }
	  if (!time.includes(':')) {
	    time += ':00';
	  }
	  if (time.indexOf(':') === time.length - 1) {
	    time += '00';
	  }
	  let {
	    hours,
	    minutes
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getMinutesAndHours)[_getMinutesAndHours](time);
	  hours = `0${hours}`.slice(-2);
	  minutes = `0${minutes}`.slice(-2);
	  return `${hours}:${minutes}`;
	}
	function _getMinutesAndHours2(value, key) {
	  const time = babelHelpers.classPrivateFieldLooseBase(this, _clearTimeString)[_clearTimeString](value, key);
	  let hours = 0;
	  let minutes = 0;
	  if (time.includes(':')) {
	    hours = time.match(/\d*:/g)[0].slice(0, -1);
	    minutes = time.match(/:\d*/g)[0].slice(1);
	  } else {
	    var _time$match;
	    const digits = ((_time$match = time.match(/\d/g)) != null ? _time$match : []).splice(0, 4).map(d => Number(d));
	    if (digits.length === 4 && digits[0] > babelHelpers.classPrivateFieldLooseBase(this, _getMaxHours)[_getMaxHours]() / 10) {
	      digits.pop();
	    }
	    if (digits.length === 1) {
	      hours = String(digits[0]);
	    }
	    if (digits.length === 2) {
	      hours = `${digits[0]}${digits[1]}`;
	      if (Number(hours) > babelHelpers.classPrivateFieldLooseBase(this, _getMaxHours)[_getMaxHours]()) {
	        hours = String(digits[0]);
	        minutes = String(digits[1]);
	      }
	    }
	    if (digits.length === 3) {
	      if (main_date.DateTimeFormat.isAmPmMode()) {
	        if (digits[0] >= 1) {
	          hours = String(digits[0]);
	          minutes = `${digits[1]}${digits[2]}`;
	        } else {
	          hours = `${digits[0]}${digits[1]}`;
	          minutes = String(digits[2]);
	        }
	      } else if (Number(`${digits[0]}${digits[1]}`) < 24) {
	        hours = `${digits[0]}${digits[1]}`;
	        minutes = String(digits[2]);
	      } else {
	        hours = String(digits[0]);
	        minutes = `${digits[1]}${digits[2]}`;
	      }
	    }
	    if (digits.length === 4) {
	      hours = `${digits[0]}${digits[1]}`;
	      minutes = `${digits[2]}${digits[3]}`;
	    }
	  }
	  if (hours) {
	    hours = babelHelpers.classPrivateFieldLooseBase(this, _formatHours)[_formatHours](hours);
	  }
	  if (minutes) {
	    minutes = babelHelpers.classPrivateFieldLooseBase(this, _formatMinutes)[_formatMinutes](minutes);
	  }
	  return {
	    hours,
	    minutes
	  };
	}
	function _clearTimeString2(str, key) {
	  let validatedTime = str.replaceAll(/[ap]/g, '').replaceAll(/\D/g, ':'); // remove a and p and replace not digits to :
	  validatedTime = validatedTime.replace(/:*/, ''); // remove everything before first digit

	  // leave only first :
	  const firstColonIndex = validatedTime.indexOf(':');
	  validatedTime = validatedTime.slice(0, firstColonIndex + 1) + validatedTime.slice(firstColonIndex + 1).replaceAll(':', '');

	  // leave not more than 2 hour digits and 2 minute digits
	  if (firstColonIndex !== -1) {
	    const hours = babelHelpers.classPrivateFieldLooseBase(this, _formatHours)[_formatHours](validatedTime.match(/\d*:/g)[0].slice(0, -1));
	    const minutes = validatedTime.match(/:\d*/g)[0].slice(1).slice(0, 3);
	    if (hours.length === 1 && minutes.length === 3 && !Number.isNaN(Number(key)) && babelHelpers.classPrivateFieldLooseBase(this, _areTimeDigitsCorrect)[_areTimeDigitsCorrect](`${hours}${minutes}`)) {
	      return `${hours}${minutes}`;
	    }
	    return `${hours}:${minutes}`;
	  }
	  return validatedTime.slice(0, 4);
	}
	function _areTimeDigitsCorrect2(time) {
	  const hh = time.slice(0, 2);
	  const mm = time.slice(2);
	  return babelHelpers.classPrivateFieldLooseBase(this, _formatHours)[_formatHours](hh) === hh && babelHelpers.classPrivateFieldLooseBase(this, _formatMinutes)[_formatMinutes](mm) === mm;
	}
	function _formatHours2(hours) {
	  if (main_date.DateTimeFormat.isAmPmMode()) {
	    return hours;
	  }
	  const firstDigit = hours[0];
	  if (Number(firstDigit) > babelHelpers.classPrivateFieldLooseBase(this, _getMaxHours)[_getMaxHours]() / 10) {
	    return `0${firstDigit}`;
	  }
	  if (Number(hours) <= babelHelpers.classPrivateFieldLooseBase(this, _getMaxHours)[_getMaxHours]()) {
	    var _hours$;
	    return `${firstDigit}${(_hours$ = hours[1]) != null ? _hours$ : ''}`;
	  }
	  return String(firstDigit);
	}
	function _formatMinutes2(minutes) {
	  var _minutes$;
	  const firstDigit = minutes[0];
	  return firstDigit >= 6 ? `0${firstDigit}` : `${firstDigit}${(_minutes$ = minutes[1]) != null ? _minutes$ : ''}`;
	}
	function _getMaxHours2() {
	  return main_date.DateTimeFormat.isAmPmMode() ? 12 : 24;
	}
	const timeFormatter = new TimeFormatter();

	const hour = booking_lib_duration.Duration.getUnitDurations().H;
	const halfHour = hour / 2;
	const TimeSelector = {
	  emits: ['update:modelValue', 'freeze', 'unfreeze', 'enterSave'],
	  props: {
	    modelValue: {
	      type: Number,
	      required: true
	    },
	    minTs: {
	      type: Number,
	      default: 0
	    },
	    hasError: {
	      type: Boolean,
	      default: false
	    }
	  },
	  data() {
	    return {
	      isMenuShown: false,
	      focusByMouseDown: false,
	      menuShownOnFocus: false
	    };
	  },
	  mounted() {
	    this.$refs.input.value = this.formatTime(this.timestamp);
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      offset: `${booking_const.Model.Interface}/offset`
	    }),
	    id() {
	      return 'booking-time-selector-menu';
	    },
	    timestamp() {
	      return this.modelValue;
	    }
	  },
	  methods: {
	    onFocus() {
	      if (this.isMenuShown) {
	        return;
	      }
	      setTimeout(() => {
	        if (!this.isMenuShown) {
	          this.menuShownOnFocus = true;
	          this.showMenu();
	          if (!this.focusByMouseDown) {
	            this.onAfterMenuShown();
	          }
	        }
	      }, 200);
	    },
	    onMouseDown() {
	      if (!this.isMenuShown) {
	        this.focusByMouseDown = true;
	        main_core.Event.bind(window, 'dragend', this.onAfterMenuShown);
	        main_core.Event.bind(window, 'click', this.onAfterMenuShown);
	      }
	    },
	    onClick() {
	      if (this.isMenuShown) {
	        if (!this.menuShownOnFocus) {
	          this.hideMenu();
	        }
	      } else {
	        this.showMenu();
	      }
	    },
	    onAfterMenuShown() {
	      setTimeout(() => {
	        this.menuShownOnFocus = false;
	        this.focusByMouseDown = false;
	        main_core.Event.unbind(window, 'dragend', this.onAfterMenuShown);
	        main_core.Event.unbind(window, 'click', this.onAfterMenuShown);
	      }, 0);
	    },
	    onKeyDown(event) {
	      if (event.key === 'Enter' && this.timestamp === this.parseTime(this.$refs.input.value)) {
	        this.$emit('enterSave');
	      }
	      this.hideMenu();
	    },
	    showMenu() {
	      main_popup.MenuManager.show({
	        id: this.id,
	        className: 'booking-time-selector-menu',
	        bindElement: this.$refs.input,
	        items: this.getMenuItems(),
	        autoHide: true,
	        maxHeight: 300,
	        minWidth: this.$refs.input.offsetWidth,
	        events: {
	          onShow: this.onShow,
	          onClose: this.hideMenu,
	          onDestroy: this.hideMenu
	        }
	      });
	      this.getMenu().getPopupWindow().autoHideHandler = ({
	        target
	      }) => {
	        const popup = this.getMenu().getPopupWindow();
	        const shouldHide = target !== popup.getPopupContainer() && !popup.getPopupContainer().contains(target);
	        return shouldHide && !this.menuShownOnFocus;
	      };
	      this.scrollToClosestItem();
	    },
	    getMenuItems() {
	      const date = new Date(this.timestamp);
	      const dateTs = new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime() - this.offset;
	      const timestamps = Array.from({
	        length: 49
	      }).fill(0).map((_, i) => dateTs + i * halfHour).filter(timestamp => timestamp >= this.minTs + hour);
	      if (this.minTs > 0 && timestamps[0] - (this.minTs + hour) >= hour / 4) {
	        timestamps.unshift(this.minTs + hour);
	      }
	      if (this.minTs > 0) {
	        timestamps.unshift(this.minTs + hour / 4, this.minTs + hour / 2, this.minTs + 3 * hour / 4);
	      }
	      return timestamps.map(timestamp => ({
	        id: timestamp,
	        html: `
					<span
						data-element="booking-time-selector-item"
						data-timestamp="${timestamp}"
					>
						${this.formatTime(timestamp)}
					</span>
					<span class="menu-popup-item-hint">${this.getDurationHint(timestamp)}</span>
				`,
	        className: timestamp === this.timestamp ? 'menu-popup-no-icon --selected' : 'menu-popup-no-icon',
	        onclick: () => {
	          this.$emit('update:modelValue', timestamp);
	          this.getMenu().close();
	        }
	      }));
	    },
	    getDurationHint(timestamp) {
	      if (this.minTs > 0) {
	        const diff = timestamp - this.minTs;
	        if (diff < hour) {
	          return new booking_lib_duration.Duration(diff).format();
	        }
	        const roundedDiff = Math.round(diff / halfHour) * halfHour;
	        const hint = new booking_lib_duration.Duration(roundedDiff).format();
	        return diff === roundedDiff ? hint : this.loc('BOOKING_TIME_SELECTOR_APPROXIMATE_VALUE', {
	          '#VALUE#': hint
	        });
	      }
	      return '';
	    },
	    scrollToClosestItem() {
	      var _closest$getLayout;
	      const closest = this.getMenu().getMenuItems().reduce((prev, curr) => {
	        return Math.abs(curr.getId() - this.timestamp) < Math.abs(prev.getId() - this.timestamp) ? curr : prev;
	      });
	      const closestItemNode = closest == null ? void 0 : (_closest$getLayout = closest.getLayout()) == null ? void 0 : _closest$getLayout.item;
	      if (!closestItemNode) {
	        return;
	      }
	      const menuContainer = this.getMenu().getPopupWindow().getContentContainer();
	      menuContainer.scrollTop = closestItemNode.offsetTop - closestItemNode.offsetHeight - 36 * 3;
	    },
	    isShown() {
	      var _this$getMenu$getPopu, _this$getMenu;
	      return (_this$getMenu$getPopu = (_this$getMenu = this.getMenu()) == null ? void 0 : _this$getMenu.getPopupWindow().isShown()) != null ? _this$getMenu$getPopu : false;
	    },
	    adjustMenuPosition() {
	      var _this$getMenu2;
	      (_this$getMenu2 = this.getMenu()) == null ? void 0 : _this$getMenu2.getPopupWindow().adjustPosition();
	    },
	    getMenu() {
	      return main_popup.MenuManager.getMenuById(this.id);
	    },
	    onShow() {
	      this.isMenuShown = true;
	      this.freeze();
	      this.$refs.input.select();
	    },
	    hideMenu() {
	      this.isMenuShown = false;
	      this.unfreeze();
	      main_popup.MenuManager.destroy(this.id);
	    },
	    freeze() {
	      this.$emit('freeze');
	    },
	    unfreeze() {
	      this.$emit('unfreeze');
	    },
	    onInput(event) {
	      const input = this.$refs.input;
	      if (event.inputType === 'deleteContentBackward') {
	        return;
	      }
	      input.value = timeFormatter.getMaskedTime(input.value, event.data);
	      if (input.value === this.formatTime(this.parseTime(input.value))) {
	        this.onChange();
	      }
	    },
	    onChange() {
	      let timestamp = this.parseTime(this.$refs.input.value);
	      if (timestamp < this.minTs) {
	        var _validItem$id;
	        const value = this.formatTime(timestamp);
	        const validItem = this.getMenuItems().find(item => item.text === value);
	        timestamp = (_validItem$id = validItem == null ? void 0 : validItem.id) != null ? _validItem$id : timestamp;
	      }
	      this.$refs.input.value = this.formatTime(timestamp);
	      this.$emit('update:modelValue', timestamp);
	    },
	    formatTime(timestamp) {
	      return timeFormatter.formatTime(timestamp + this.offset);
	    },
	    parseTime(value) {
	      return timeFormatter.parseTime(value, this.timestamp + this.offset) - this.offset;
	    }
	  },
	  watch: {
	    modelValue() {
	      this.$refs.input.value = this.formatTime(this.timestamp);
	    }
	  },
	  template: `
		<div
			class="booking-time-selector"
			:class="{'--menu-shown': isMenuShown}"
		>
			<input
				class="booking-time-selector-input"
				:class="{'--error': hasError}"
				ref="input"
				@focus="onFocus"
				@mousedown="onMouseDown"
				@click="onClick"
				@keydown="onKeyDown"
				@input="onInput"
				@change="onChange"
			/>
			<div
				class="ui-icon-set --chevron-down"
			></div>
		</div>
	`
	};

	exports.TimeSelector = TimeSelector;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX,BX.Main,BX.Vue3.Vuex,BX.Booking.Lib,BX.Booking.Const,BX.Main));
//# sourceMappingURL=time-selector.bundle.js.map
