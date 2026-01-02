/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_core,ui_notification,main_core_events,main_date,sign_v2_api) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _documentUids = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentUids");
	var _documentDateField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentDateField");
	var _selectedDate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedDate");
	var _signingMinMinutes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("signingMinMinutes");
	var _signingMaxMonth = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("signingMaxMonth");
	var _getDateField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDateField");
	var _isDatePassMinValidation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDatePassMinValidation");
	var _isDatePassMaxValidation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDatePassMaxValidation");
	class DatetimeLimitSelector extends main_core_events.EventEmitter {
	  constructor() {
	    super();
	    Object.defineProperty(this, _isDatePassMaxValidation, {
	      value: _isDatePassMaxValidation2
	    });
	    Object.defineProperty(this, _isDatePassMinValidation, {
	      value: _isDatePassMinValidation2
	    });
	    Object.defineProperty(this, _getDateField, {
	      value: _getDateField2
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentUids, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentDateField, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedDate, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _signingMinMinutes, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _signingMaxMonth, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Sign.V2.DatetimeLimitSelector');
	    babelHelpers.classPrivateFieldLooseBase(this, _documentUids)[_documentUids] = [];
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _documentDateField)[_documentDateField] = babelHelpers.classPrivateFieldLooseBase(this, _getDateField)[_getDateField]();
	    const settings = main_core.Extension.getSettings('sign.v2.datetime-limit-selector');
	    const defaultSignUntilDate = settings.get('defaultSignUntilDate', null);
	    this.setDate(defaultSignUntilDate ? new Date(defaultSignUntilDate) : new Date());
	    babelHelpers.classPrivateFieldLooseBase(this, _signingMinMinutes)[_signingMinMinutes] = settings.get('signingMinMinutes', 5);
	    babelHelpers.classPrivateFieldLooseBase(this, _signingMaxMonth)[_signingMaxMonth] = settings.get('signingMaxMonth', 3);
	  }
	  getLayout() {
	    return main_core.Tag.render(_t || (_t = _`
			<div class="sign-datetime-limit-selector">
				<span class="sign-datetime-limit-selector__label">
					${0}
				</span>
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_BLANK_DATETIME_SELECTOR_LABEL'), babelHelpers.classPrivateFieldLooseBase(this, _documentDateField)[_documentDateField]);
	  }
	  getSelectedDate() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _selectedDate)[_selectedDate];
	  }
	  setDate(date) {
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedDate)[_selectedDate] = date;
	    const dateFormatted = main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('MEDIUM_DATE_FORMAT'), date);
	    const timeFormatted = main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT'), date);
	    const formattedDate = main_core.Loc.getMessage('SIGN_BLANK_DATETIME_SELECTOR_DATE', {
	      '#MEDIUM_DATE#': dateFormatted,
	      '#SHORT_TIME#': timeFormatted
	    });
	    const dateTextNode = babelHelpers.classPrivateFieldLooseBase(this, _documentDateField)[_documentDateField].firstElementChild;
	    dateTextNode.textContent = formattedDate;
	  }
	  isValid() {
	    return !main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _documentDateField)[_documentDateField], '--invalid');
	  }
	  setValidClass() {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _documentDateField)[_documentDateField], '--invalid');
	  }
	  setInvalidClass() {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _documentDateField)[_documentDateField], '--invalid');
	  }
	  async saveSelectedDateForUids() {
	    const timestamp = main_date.Timezone.UserTime.toUTCTimestamp(babelHelpers.classPrivateFieldLooseBase(this, _selectedDate)[_selectedDate]);
	    try {
	      await Promise.all(babelHelpers.classPrivateFieldLooseBase(this, _documentUids)[_documentUids].map(uid => babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].modifyDateSignUntil(uid, timestamp)));
	      this.setValidClass();
	    } catch (error) {
	      this.setInvalidClass();
	      throw error;
	    }
	  }
	  setDocumentUids(uids) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentUids)[_documentUids] = uids;
	  }
	}
	function _getDateField2() {
	  return main_core.Tag.render(_t2 || (_t2 = _`
			<div
				class="sign-datetime-limit-selector_field"
				onclick="${0}"
			>
				<span class="sign-datetime-limit-selector_field-text"></span>
			</div>
		`), () => {
	    BX.calendar({
	      node: babelHelpers.classPrivateFieldLooseBase(this, _documentDateField)[_documentDateField],
	      field: babelHelpers.classPrivateFieldLooseBase(this, _documentDateField)[_documentDateField],
	      currentTime: babelHelpers.classPrivateFieldLooseBase(this, _selectedDate)[_selectedDate].getTime() / 1000,
	      value: main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('FORMAT_DATETIME'), babelHelpers.classPrivateFieldLooseBase(this, _selectedDate)[_selectedDate].getTime() / 1000),
	      bTime: true,
	      bHideTime: false,
	      callback: date => {
	        this.emit('beforeDateModify');
	        if (!babelHelpers.classPrivateFieldLooseBase(this, _isDatePassMinValidation)[_isDatePassMinValidation](date)) {
	          const validationErrorMessage = main_core.Loc.getMessagePlural('PERIOD_TOO_SHORT', babelHelpers.classPrivateFieldLooseBase(this, _signingMinMinutes)[_signingMinMinutes], {
	            '#MIN_PERIOD#': babelHelpers.classPrivateFieldLooseBase(this, _signingMinMinutes)[_signingMinMinutes]
	          });
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Text.encode(validationErrorMessage),
	            autoHideDelay: 4000
	          });
	          return false;
	        }
	        if (!babelHelpers.classPrivateFieldLooseBase(this, _isDatePassMaxValidation)[_isDatePassMaxValidation](date)) {
	          const validationErrorMessage = main_core.Loc.getMessagePlural('PERIOD_TOO_LONG', babelHelpers.classPrivateFieldLooseBase(this, _signingMaxMonth)[_signingMaxMonth], {
	            '#MONTH#': babelHelpers.classPrivateFieldLooseBase(this, _signingMaxMonth)[_signingMaxMonth]
	          });
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Text.encode(validationErrorMessage),
	            autoHideDelay: 4000
	          });
	          return false;
	        }
	        this.emit('afterDateModify');
	        return true;
	      },
	      callback_after: date => {
	        this.setDate(date);
	      }
	    });
	  });
	}
	function _isDatePassMinValidation2(date) {
	  const minValidDateTime = main_date.Timezone.UserTime.getDate();
	  minValidDateTime.setMinutes(minValidDateTime.getMinutes() + babelHelpers.classPrivateFieldLooseBase(this, _signingMinMinutes)[_signingMinMinutes]);
	  return date.getTime() > minValidDateTime.getTime();
	}
	function _isDatePassMaxValidation2(date) {
	  const maxValidDateTime = main_date.Timezone.UserTime.getDate();
	  maxValidDateTime.setMonth(maxValidDateTime.getMonth() + babelHelpers.classPrivateFieldLooseBase(this, _signingMaxMonth)[_signingMaxMonth]);
	  return date.getTime() < maxValidDateTime.getTime();
	}

	exports.DatetimeLimitSelector = DatetimeLimitSelector;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX,BX,BX.Event,BX.Main,BX.Sign.V2));
//# sourceMappingURL=datetime-limit-selector.bundle.js.map
