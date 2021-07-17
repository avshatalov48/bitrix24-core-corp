(function (exports,main_core,main_core_events,ui_buttons,crm_router,main_popup) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Crm');
	var instance = null;

	var ToolbarEvents = function ToolbarEvents() {
	  babelHelpers.classCallCheck(this, ToolbarEvents);
	};

	babelHelpers.defineProperty(ToolbarEvents, "TYPE_UPDATED", 'TypeUpdated');
	babelHelpers.defineProperty(ToolbarEvents, "CATEGORIES_UPDATED", 'CategoriesUpdated');

	var ToolbarComponent = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(ToolbarComponent, _EventEmitter);

	  function ToolbarComponent() {
	    var _this;

	    babelHelpers.classCallCheck(this, ToolbarComponent);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ToolbarComponent).call(this));

	    _this.setEventNamespace('BX.Crm.ToolbarComponent');

	    main_core.Event.ready(_this.bindEvents.bind(babelHelpers.assertThisInitialized(_this)));
	    return _this;
	  }

	  babelHelpers.createClass(ToolbarComponent, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this2 = this;

	      var buttonNode = document.querySelector('[data-role="bx-crm-toolbar-categories-button"]');

	      if (buttonNode) {
	        var entityTypeId = Number(buttonNode.dataset.entityTypeId);
	        var button = ui_buttons.ButtonManager.createFromNode(buttonNode);

	        if (button && entityTypeId > 0) {
	          this.subscribeCategoriesUpdatedEvent(function () {
	            _this2.reloadCategoriesMenu(button, entityTypeId, buttonNode.dataset.categoryId);
	          });
	        }
	      }
	    }
	  }, {
	    key: "emitTypeUpdatedEvent",
	    value: function emitTypeUpdatedEvent(data) {
	      this.emit(ToolbarEvents.TYPE_UPDATED, data);
	    }
	  }, {
	    key: "emitCategoriesUpdatedEvent",
	    value: function emitCategoriesUpdatedEvent(data) {
	      this.emit(ToolbarEvents.CATEGORIES_UPDATED, data);
	    }
	  }, {
	    key: "subscribeTypeUpdatedEvent",
	    value: function subscribeTypeUpdatedEvent(callback) {
	      this.subscribe(ToolbarEvents.TYPE_UPDATED, callback);
	    }
	  }, {
	    key: "subscribeCategoriesUpdatedEvent",
	    value: function subscribeCategoriesUpdatedEvent(callback) {
	      this.subscribe(ToolbarEvents.CATEGORIES_UPDATED, callback);
	    }
	  }, {
	    key: "reloadCategoriesMenu",
	    value: function reloadCategoriesMenu(button, entityTypeId, categoryId) {
	      var menu = button.getMenuWindow();

	      if (!menu) {
	        return;
	      }

	      main_core.ajax.runAction('crm.controller.category.list', {
	        data: {
	          entityTypeId: entityTypeId
	        }
	      }).then(function (response) {
	        var startKey = 0;
	        var items = [];
	        var categories = response.data.categories;
	        menu.menuItems.forEach(function (item) {
	          if (item.id.indexOf('toolbar-category-') !== 0) {
	            items.push(item.options);
	          } else if (item.id === 'toolbar-category-all') {
	            items.push(item.options);
	            startKey = 1;
	          }
	        });
	        menu.destroy();
	        main_core.Event.unbindAll(button.getContainer(), 'click');
	        categories.forEach(function (category) {
	          var link = crm_router.Router.Instance.getItemListUrlInCurrentView(entityTypeId, category.id);
	          items.splice(startKey, 0, {
	            id: 'toolbar-category-' + category.id,
	            text: main_core.Text.encode(category.name),
	            href: link ? link.toString() : null
	          });

	          if (category.id > 0 && categoryId > 0 && Number(categoryId) === Number(category.id)) {
	            button.setText(main_core.Text.encode(category.name));
	          }

	          startKey++;
	        });
	        var options = menu.params;
	        options.items = items;
	        button.menuWindow = new main_popup.Menu(options);
	        main_core.Event.bind(button.getContainer(), 'click', button.menuWindow.show.bind(button.menuWindow));
	      }).catch(function (response) {
	        console.log('error trying reload categories', response.errors);
	      });
	    }
	  }], [{
	    key: "Instance",
	    get: function get() {
	      if (window.top !== window && main_core.Reflection.getClass('top.BX.Crm.ToolbarComponent')) {
	        return window.top.BX.Crm.ToolbarComponent.Instance;
	      }

	      if (instance === null) {
	        instance = new ToolbarComponent();
	      }

	      return instance;
	    }
	  }]);
	  return ToolbarComponent;
	}(main_core_events.EventEmitter);

	namespace.ToolbarComponent = ToolbarComponent;

}((this.window = this.window || {}),BX,BX.Event,BX.UI,BX.Crm,BX.Main));
//# sourceMappingURL=script.js.map
