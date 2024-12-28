/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,ui_iconSet_api_core,main_popup,sign_tour) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4;
	var _userCountersLimit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userCountersLimit");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _counterNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("counterNode");
	var _isShowLimitPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isShowLimitPopup");
	var _incrementTariffLinkContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("incrementTariffLinkContainer");
	var _getIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getIcon");
	var _getLimitContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLimitContainer");
	var _adjustPopupWithCenterAngle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("adjustPopupWithCenterAngle");
	var _getContainerForIncrementTariff = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContainerForIncrementTariff");
	class UserPartyCounters {
	  constructor(options) {
	    Object.defineProperty(this, _getContainerForIncrementTariff, {
	      value: _getContainerForIncrementTariff2
	    });
	    Object.defineProperty(this, _adjustPopupWithCenterAngle, {
	      value: _adjustPopupWithCenterAngle2
	    });
	    Object.defineProperty(this, _getLimitContainer, {
	      value: _getLimitContainer2
	    });
	    Object.defineProperty(this, _getIcon, {
	      value: _getIcon2
	    });
	    Object.defineProperty(this, _userCountersLimit, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _counterNode, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isShowLimitPopup, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _incrementTariffLinkContainer, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _userCountersLimit)[_userCountersLimit] = options.userCountersLimit;
	    babelHelpers.classPrivateFieldLooseBase(this, _counterNode)[_counterNode] = main_core.Tag.render(_t || (_t = _`<span class="sign-b2e-settings__user-party-counter-select">0</span>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = main_core.Tag.render(_t2 || (_t2 = _`
			<div class="sign-b2e-settings__user-party-counter">
				${0}
				<div class="sign-b2e-settings__user-party-counter_limit-block">
					${0}
					${0}
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getIcon)[_getIcon]().render(), babelHelpers.classPrivateFieldLooseBase(this, _counterNode)[_counterNode], babelHelpers.classPrivateFieldLooseBase(this, _getLimitContainer)[_getLimitContainer]());
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	  getCount() {
	    return Number(babelHelpers.classPrivateFieldLooseBase(this, _counterNode)[_counterNode].textContent);
	  }
	  update(size) {
	    babelHelpers.classPrivateFieldLooseBase(this, _counterNode)[_counterNode].textContent = size;
	    if (!main_core.Type.isNumber(babelHelpers.classPrivateFieldLooseBase(this, _userCountersLimit)[_userCountersLimit])) {
	      return;
	    }
	    if (size > babelHelpers.classPrivateFieldLooseBase(this, _userCountersLimit)[_userCountersLimit]) {
	      if (!main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], '--alert')) {
	        if (!babelHelpers.classPrivateFieldLooseBase(this, _incrementTariffLinkContainer)[_incrementTariffLinkContainer]) {
	          main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getContainerForIncrementTariff)[_getContainerForIncrementTariff](), babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	        }
	        main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], '--alert');
	      }
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _isShowLimitPopup)[_isShowLimitPopup]) {
	        const guide = new sign_tour.Guide({
	          id: 'sign-b2e-tariff-limit-user-party-counter-tour',
	          onEvents: true,
	          autoSave: false,
	          adjustPopupPosition: babelHelpers.classPrivateFieldLooseBase(this, _adjustPopupWithCenterAngle)[_adjustPopupWithCenterAngle].bind(this),
	          steps: [{
	            target: babelHelpers.classPrivateFieldLooseBase(this, _container)[_container],
	            title: main_core.Loc.getMessage('SIGN_V2_B2E_USER_PARTY_COUNTER_LIMIT_TITLE'),
	            text: main_core.Loc.getMessage('SIGN_V2_B2E_USER_PARTY_COUNTER_LIMIT_MESSAGE'),
	            condition: {
	              top: true,
	              bottom: false,
	              color: 'primary'
	            },
	            link: 'javascript:void(0);'
	          }],
	          popupOptions: {
	            width: 450,
	            autoHide: false
	          }
	        });
	        guide.getLink().setAttribute('onclick', "BX.PreventDefault();top.BX.UI.InfoHelper.show('limit_office_e_signature');");
	        guide.start();
	        babelHelpers.classPrivateFieldLooseBase(this, _isShowLimitPopup)[_isShowLimitPopup] = true;
	      }
	    } else {
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], '--alert');
	    }
	  }
	}
	function _getIcon2() {
	  return new ui_iconSet_api_core.Icon({
	    icon: ui_iconSet_api_core.Main.PERSONS_2,
	    size: 18,
	    color: getComputedStyle(document.body).getPropertyValue('--ui-color-palette-gray-60')
	  });
	}
	function _getLimitContainer2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _userCountersLimit)[_userCountersLimit]) {
	    return null;
	  }
	  return main_core.Tag.render(_t3 || (_t3 = _`<span class="sign-b2e-settings__user-party-counter-limit">/ ${0}</span>`), Number(babelHelpers.classPrivateFieldLooseBase(this, _userCountersLimit)[_userCountersLimit]));
	}
	function _adjustPopupWithCenterAngle2(popup) {
	  const popupWidth = popup.getWidth();
	  const {
	    left: startX
	  } = babelHelpers.classPrivateFieldLooseBase(this, _container)[_container].getBoundingClientRect();
	  const bindElementCenter = startX + babelHelpers.classPrivateFieldLooseBase(this, _container)[_container].offsetWidth / 2;
	  const popupCenter = startX + popup.getPopupContainer().offsetWidth / 2;
	  const offsetLeft = main_popup.Popup.getOption('angleLeftOffset') + bindElementCenter - popupCenter;
	  popup.setOffset({
	    offsetLeft
	  });
	  popup.adjustPosition();
	  const {
	    angleArrowElement
	  } = popup;
	  popup.setAngle({
	    offset: (popupWidth - angleArrowElement.parentElement.offsetWidth) / 2
	  });
	}
	function _getContainerForIncrementTariff2() {
	  const rightChevron = new ui_iconSet_api_core.Icon({
	    icon: ui_iconSet_api_core.Actions.CHEVRON_RIGHT,
	    size: 18,
	    color: getComputedStyle(document.body).getPropertyValue('--ui-color-link-primary-base')
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _incrementTariffLinkContainer)[_incrementTariffLinkContainer] = main_core.Tag.render(_t4 || (_t4 = _`
			<a class="sign-b2e-settings__user-party-counter-increment-tariff" href="javascript:void(0);" onclick="BX.PreventDefault();top.BX.UI.InfoHelper.show('limit_office_e_signature');">
				 -
				 <div class="sign-b2e-settings__user-party-counter-increment-tariff_text">
					${0}
				 </div>
				 ${0}
			</a>
		`), main_core.Loc.getMessage('SIGN_V2_B2E_USER_PARTY_COUNTER_LIMIT_INCREMENT_TARIFF'), rightChevron.render());
	  return babelHelpers.classPrivateFieldLooseBase(this, _incrementTariffLinkContainer)[_incrementTariffLinkContainer];
	}

	exports.UserPartyCounters = UserPartyCounters;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.UI.IconSet,BX.Main,BX.Sign.Tour));
//# sourceMappingURL=user-party-counters.bundle.js.map
