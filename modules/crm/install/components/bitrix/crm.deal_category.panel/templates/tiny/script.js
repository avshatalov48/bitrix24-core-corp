this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Deal = this.BX.Crm.Deal || {};
(function (exports,main_core,main_popup) {
	'use strict';

	var Panel = /*#__PURE__*/function (_Event$EventEmitter) {
	  babelHelpers.inherits(Panel, _Event$EventEmitter);
	  babelHelpers.createClass(Panel, null, [{
	    key: "createMenuItem",
	    value: function createMenuItem(options) {
	      var item = {
	        id: options.ID,
	        html: main_core.Text.encode(options.NAME),
	        href: options.URL
	      };
	      var count = Number.parseInt(options.COUNTER, 10);

	      if (main_core.Type.isNumber(count) && count > 0) {
	        var counter = "<span class=\"main-buttons-item-counter\">".concat(options.COUNTER, "</span>");
	        item.html = "".concat(item.html, " ").concat(counter);
	      }

	      return item;
	    }
	  }]);

	  function Panel(options) {
	    var _this;

	    babelHelpers.classCallCheck(this, Panel);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Panel).call(this));
	    _this.button = options.button;
	    _this.counter = options.counter;
	    _this.container = options.container;
	    _this.items = options.items;
	    _this.tunnelsUrl = options.tunnelsUrl;
	    _this.componentParams = options.componentParams;
	    _this.onButtonClick = _this.onButtonClick.bind(babelHelpers.assertThisInitialized(_this));
	    main_core.Event.bind(_this.button, 'click', _this.onButtonClick);
	    return _this;
	  }

	  babelHelpers.createClass(Panel, [{
	    key: "isDropdown",
	    value: function isDropdown() {
	      return main_core.Dom.hasClass(this.button, 'ui-btn-dropdown');
	    }
	  }, {
	    key: "reload",
	    value: function reload() {
	      var _this2 = this;

	      return main_core.ajax.runComponentAction('bitrix:crm.deal_category.panel', 'getComponent', {
	        data: {
	          params: this.componentParams
	        }
	      }).then(function (response) {
	        var newContainer = main_core.Runtime.html(null, response.data.html);
	        main_core.Dom.replace(_this2.container, newContainer);

	        _this2.getMenu().destroy();
	      });
	    }
	  }, {
	    key: "onButtonClick",
	    value: function onButtonClick(event) {
	      event.preventDefault();

	      if (this.isDropdown()) {
	        this.getMenu().show();
	        return;
	      }

	      this.showTunnelSlider();
	    }
	  }, {
	    key: "showTunnelSlider",
	    value: function showTunnelSlider() {
	      var _this3 = this;

	      // eslint-disable-next-line
	      BX.SidePanel.Instance.open(this.tunnelsUrl, {
	        cacheable: false,
	        customLeftBoundary: 40,
	        allowChangeHistory: false,
	        events: {
	          onClose: function onClose() {
	            _this3.reload();

	            if (window.top.BX.Main && window.top.BX.Main.filterManager) {
	              var data = window.top.BX.Main.filterManager.data; // eslint-disable-next-line

	              Object.values(data).forEach(function (filter) {
	                return filter._onFindButtonClick();
	              });
	            }
	          }
	        }
	      });
	    }
	  }, {
	    key: "getMenu",
	    value: function getMenu() {
	      if (!this.menu) {
	        var menuItems = this.items.map(function (item) {
	          return Panel.createMenuItem(item);
	        });
	        menuItems.push({
	          delimiter: true
	        });
	        menuItems.push({
	          id: 'tunnels',
	          text: main_core.Loc.getMessage('CRM_DEAL_CATEGORY_PANEL_TUNNELS2'),
	          onclick: this.showTunnelSlider.bind(this)
	        });
	        this.menu = new main_popup.PopupMenuWindow({
	          bindElement: this.button,
	          items: menuItems
	        });
	      }

	      return this.menu;
	    }
	  }]);
	  return Panel;
	}(main_core.Event.EventEmitter);

	exports.Panel = Panel;

}((this.BX.Crm.Deal.Category = this.BX.Crm.Deal.Category || {}),BX,BX.Main));
//# sourceMappingURL=script.js.map
