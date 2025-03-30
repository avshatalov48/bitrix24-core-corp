/* eslint-disable */
this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,main_core,ui_promoVideoPopup,ui_buttons,ui_iconSet_api_core) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _promoVideoPopup = /*#__PURE__*/new WeakMap();
	var _requestToBackend = /*#__PURE__*/new WeakSet();
	var DiskVideoPopup = /*#__PURE__*/function () {
	  function DiskVideoPopup(options) {
	    babelHelpers.classCallCheck(this, DiskVideoPopup);
	    _classPrivateMethodInitSpec(this, _requestToBackend);
	    _classPrivateFieldInitSpec(this, _promoVideoPopup, {
	      writable: true,
	      value: null
	    });
	    this.targetOptions = options.targetOptions ? options.targetOptions : window;
	    this.boardsUrl = options.boardsUrl;
	    this.componentName = options.componentName;
	  }
	  babelHelpers.createClass(DiskVideoPopup, [{
	    key: "getWidth",
	    value: function getWidth() {
	      return ui_promoVideoPopup.PromoVideoPopup.getWidth();
	    }
	  }, {
	    key: "getPopup",
	    value: function getPopup() {
	      var _this = this;
	      var language = main_core.Loc.getMessage('LANGUAGE_ID');
	      var sources = {
	        ru: '/bitrix/js/disk/boards-promo-popup/video/ru/disk-promo-ru.webm',
	        en: '/bitrix/js/disk/boards-promo-popup/video/en/disk-promo-en.webm'
	      };
	      if (babelHelpers.classPrivateFieldGet(this, _promoVideoPopup) === null) {
	        babelHelpers.classPrivateFieldSet(this, _promoVideoPopup, new ui_promoVideoPopup.PromoVideoPopup({
	          videoSrc: language === 'ru' ? sources.ru : sources.en,
	          videoContainerMinHeight: 255,
	          title: main_core.Loc.getMessage('DISK_PROMO_VIDEO_POPUP_TITLE'),
	          text: main_core.Loc.getMessage('DISK_PROMO_VIDEO_POPUP_TEXT'),
	          targetOptions: this.targetOptions,
	          icon: ui_iconSet_api_core.Main.DEMONSTRATION_GRAPHICS,
	          button: {
	            text: main_core.Loc.getMessage('DISK_PROMO_VIDEO_POPUP_BUTTON'),
	            color: ui_buttons.Button.Color.SUCCESS,
	            size: ui_buttons.Button.Size.LARGE,
	            position: ui_promoVideoPopup.PromoVideoPopupButtonPosition.RIGHT
	          },
	          offset: {
	            top: 50,
	            left: 50
	          },
	          useOverlay: true,
	          autoHide: false
	        }));
	        babelHelpers.classPrivateFieldGet(this, _promoVideoPopup).subscribe(ui_promoVideoPopup.PromoVideoPopupEvents.ACCEPT, function () {
	          _this.setCompleted().then(function () {
	            window.location.href = _this.boardsUrl;
	          });
	        });
	        babelHelpers.classPrivateFieldGet(this, _promoVideoPopup).subscribe(ui_promoVideoPopup.PromoVideoPopupEvents.HIDE, function () {
	          _this.setViewed();
	          document.body.style.overflowY = 'scroll';
	        });
	      }
	      return babelHelpers.classPrivateFieldGet(this, _promoVideoPopup);
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      this.getPopup().show();
	      document.body.style.overflowY = 'hidden';
	    }
	  }, {
	    key: "setViewed",
	    value: function setViewed() {
	      _classPrivateMethodGet(this, _requestToBackend, _requestToBackend2).call(this, 'setViewed');
	    }
	  }, {
	    key: "setCompleted",
	    value: function setCompleted() {
	      return _classPrivateMethodGet(this, _requestToBackend, _requestToBackend2).call(this, 'setCompleted');
	    }
	  }]);
	  return DiskVideoPopup;
	}();
	function _requestToBackend2(action) {
	  return main_core.ajax.runComponentAction(this.componentName, action, {
	    mode: 'class'
	  });
	}

	exports.DiskVideoPopup = DiskVideoPopup;

}((this.BX.Disk.BoardsPromoPopup = this.BX.Disk.BoardsPromoPopup || {}),BX,BX.UI,BX.UI,BX.UI.IconSet));
//# sourceMappingURL=disk.boards-promo-popup.bundle.js.map
