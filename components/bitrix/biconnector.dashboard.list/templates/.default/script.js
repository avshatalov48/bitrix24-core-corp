/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,ui_tour) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _options = /*#__PURE__*/new WeakMap();
	var _spotLight = /*#__PURE__*/new WeakMap();
	var _guide = /*#__PURE__*/new WeakMap();
	var _getSpotlight = /*#__PURE__*/new WeakSet();
	var _getGuide = /*#__PURE__*/new WeakSet();
	var DashboardGrid = /*#__PURE__*/function () {
	  function DashboardGrid(options) {
	    babelHelpers.classCallCheck(this, DashboardGrid);
	    _classPrivateMethodInitSpec(this, _getGuide);
	    _classPrivateMethodInitSpec(this, _getSpotlight);
	    _classPrivateFieldInitSpec(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _spotLight, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _guide, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _options, options);
	  }
	  babelHelpers.createClass(DashboardGrid, [{
	    key: "showOnboarding",
	    value: function showOnboarding() {
	      var _this = this;
	      _classPrivateMethodGet(this, _getSpotlight, _getSpotlight2).call(this).show();
	      _classPrivateMethodGet(this, _getGuide, _getGuide2).call(this).start();
	      _classPrivateMethodGet(this, _getSpotlight, _getSpotlight2).call(this).getTargetContainer().addEventListener('mouseover', function () {
	        _classPrivateMethodGet(_this, _getSpotlight, _getSpotlight2).call(_this).close();
	      });
	    }
	  }], [{
	    key: "deleteRow",
	    value: function deleteRow(id) {
	      var grid = BX.Main.gridManager.getInstanceById(DashboardGrid.gridId);
	      grid.confirmDialog({
	        CONFIRM: true,
	        CONFIRM_MESSAGE: main_core.Loc.getMessage('CC_BBDL_ACTION_MENU_DELETE_CONF')
	      }, function () {
	        main_core.ajax.runComponentAction(DashboardGrid.componentName, 'deleteRow', {
	          mode: 'class',
	          data: {
	            id: id
	          }
	        }).then(function () {
	          grid.removeRow(id);
	        });
	      });
	    }
	  }]);
	  return DashboardGrid;
	}();
	function _getSpotlight2() {
	  if (babelHelpers.classPrivateFieldGet(this, _spotLight)) {
	    return babelHelpers.classPrivateFieldGet(this, _spotLight);
	  }
	  babelHelpers.classPrivateFieldSet(this, _spotLight, new BX.SpotLight({
	    targetElement: babelHelpers.classPrivateFieldGet(this, _options).bindElement,
	    targetVertex: 'middle-center',
	    id: DashboardGrid.gridId,
	    lightMode: true
	  }));
	  return babelHelpers.classPrivateFieldGet(this, _spotLight);
	}
	function _getGuide2() {
	  var _this2 = this;
	  if (babelHelpers.classPrivateFieldGet(this, _guide)) {
	    return babelHelpers.classPrivateFieldGet(this, _guide);
	  }
	  babelHelpers.classPrivateFieldSet(this, _guide, new ui_tour.Guide({
	    simpleMode: true,
	    onEvents: true,
	    overlay: false,
	    steps: [{
	      target: babelHelpers.classPrivateFieldGet(this, _options).bindElement,
	      title: main_core.Loc.getMessage('CC_BBDL_ONBOARDING_TITLE'),
	      text: main_core.Loc.getMessage('CC_BBDL_ONBOARDING_DESCRIPTION'),
	      buttons: null,
	      events: {
	        onClose: function onClose() {
	          _classPrivateMethodGet(_this2, _getSpotlight, _getSpotlight2).call(_this2).close();
	        },
	        onShow: function onShow() {
	          main_core.ajax.runComponentAction(DashboardGrid.componentName, 'markShowOnboarding', {
	            mode: 'class'
	          });
	        }
	      },
	      article: babelHelpers.classPrivateFieldGet(this, _options).article
	    }],
	    autoHide: true
	  }));
	  babelHelpers.classPrivateFieldGet(this, _guide).getPopup().setWidth(320);
	  babelHelpers.classPrivateFieldGet(this, _guide).getPopup().setAngle({
	    offset: babelHelpers.classPrivateFieldGet(this, _options).bindElement.offsetWidth / 2
	  });
	  babelHelpers.classPrivateFieldGet(this, _guide).getPopup().setAutoHide(true);
	  return babelHelpers.classPrivateFieldGet(this, _guide);
	}
	babelHelpers.defineProperty(DashboardGrid, "gridId", 'biconnector_dashboard_list');
	babelHelpers.defineProperty(DashboardGrid, "componentName", 'bitrix:biconnector.dashboard.list');

	exports.DashboardGrid = DashboardGrid;

}((this.BX.BIConnector = this.BX.BIConnector || {}),BX,BX.UI.Tour));
//# sourceMappingURL=script.js.map
