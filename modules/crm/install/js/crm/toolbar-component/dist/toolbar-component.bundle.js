this.BX = this.BX || {};
(function (exports,main_core_events,ui_buttons,crm_router,main_popup,ui_hint,main_core,ui_navigationpanel) {
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

	    _this.initHints();

	    _this.setEventNamespace('BX.Crm.ToolbarComponent');

	    main_core.Event.ready(_this.bindEvents.bind(babelHelpers.assertThisInitialized(_this)));
	    return _this;
	  }

	  babelHelpers.createClass(ToolbarComponent, [{
	    key: "initHints",
	    value: function initHints() {
	      BX.UI.Hint.init(BX('ui-toolbar-after-title-buttons'));
	      BX.UI.Hint.popupParameters = {
	        closeByEsc: true,
	        autoHide: true,
	        angle: {
	          offset: 60
	        },
	        offsetLeft: 40
	      };
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this2 = this;

	      var buttonNode = document.querySelector('[data-role="bx-crm-toolbar-categories-button"]');

	      if (buttonNode) {
	        var toolbar = BX.UI.ToolbarManager.getDefaultToolbar();
	        var button = toolbar.getButton(main_core.Dom.attr(buttonNode, 'data-btn-uniqid'));
	        var entityTypeId = Number(buttonNode.dataset.entityTypeId);

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
	      var _this3 = this;

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
	          var link;

	          if (entityTypeId === BX.CrmEntityType.enumeration.deal) {
	            link = '/crm/deal/category/' + category.id + '/';
	          } else {
	            link = crm_router.Router.Instance.getItemListUrlInCurrentView(entityTypeId, category.id);
	            link = link.toString();
	          }

	          items.splice(startKey, 0, {
	            id: 'toolbar-category-' + category.id,
	            text: main_core.Text.encode(category.name),
	            href: link ? link : null
	          });

	          if (category.id > 0 && categoryId > 0 && Number(categoryId) === Number(category.id)) {
	            button.setText(category.name);
	          }

	          startKey++;
	        });
	        var options = menu.params;
	        options.items = items;
	        button.menuWindow = new main_popup.Menu(options);
	        main_core.Event.bind(button.getContainer(), 'click', button.menuWindow.show.bind(button.menuWindow));

	        if (entityTypeId === BX.CrmEntityType.enumeration.deal) {
	          _this3.reloadAddButtonMenu(categories);
	        }
	      })["catch"](function (response) {
	        console.log('error trying reload categories', response.errors);
	      });
	    }
	  }, {
	    key: "reloadAddButtonMenu",
	    value: function reloadAddButtonMenu(categories) {
	      var _this4 = this;

	      var addButtonNode = document.querySelector('.ui-btn-split.ui-btn-success');

	      if (!addButtonNode) {
	        return;
	      }

	      var addButtonId = addButtonNode.dataset.btnUniqid;
	      var toolbar = BX.UI.ToolbarManager.getDefaultToolbar();
	      var button = toolbar.getButton(addButtonId, 'data-btn-uniqid');

	      if (!button) {
	        return;
	      }

	      var menu = button.menuWindow;

	      if (!menu) {
	        return;
	      }

	      var menuItemsIds = menu.getMenuItems().map(function (item) {
	        return item.id;
	      }).filter(function (id) {
	        return main_core.Type.isInteger(id);
	      });
	      var categoryIds = categories.map(function (item) {
	        return item.id;
	      });
	      var idsToRemove = menuItemsIds.filter(function (id) {
	        return !categoryIds.includes(id);
	      });
	      var newCategories = categories.filter(function (item) {
	        return !menuItemsIds.includes(item.id) && item.id > 0;
	      }); // remove menu item(s)

	      if (idsToRemove.length > 0) {
	        idsToRemove.forEach(function (idToRemove) {
	          return menu.removeMenuItem(idToRemove);
	        });
	      } // add new item(s)


	      if (newCategories.length > 0) {
	        var targetItemId = menu.getMenuItems().map(function (item) {
	          return item.id;
	        }).filter(function (id) {
	          return main_core.Type.isString(id);
	        }).at(1);
	        newCategories.forEach(function (item) {
	          menu.addMenuItem({
	            id: item.id,
	            text: item.name,
	            onclick: function (event) {
	              BX.SidePanel.Instance.open('/crm/deal/details/0/?category_id=' + item.id);
	            }.bind(_this4)
	          }, targetItemId);
	        });
	      }
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

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var namespace$1 = main_core.Reflection.namespace('BX.Crm');

	var _id = /*#__PURE__*/new WeakMap();

	var _binding = /*#__PURE__*/new WeakMap();

	var NavigationBar = /*#__PURE__*/function (_NavigationPanel) {
	  babelHelpers.inherits(NavigationBar, _NavigationPanel);

	  function NavigationBar(options) {
	    var _this;

	    babelHelpers.classCallCheck(this, NavigationBar);

	    if (!main_core.Type.isPlainObject(options)) {
	      throw 'BX.Crm.NavigationBar: The "options" argument must be object.';
	    }

	    options.items = main_core.Type.isArray(options.items) ? options.items : [];
	    options.items.forEach(function (item) {
	      if (!item.hasOwnProperty('active') && item.hasOwnProperty('isActive')) {
	        item.active = item.isActive;
	      }

	      if (main_core.Type.isStringFilled(item.url)) {
	        item.events = {
	          click: function click() {
	            return _this.openUrl(item.id, item.url);
	          }
	        };
	      }
	    });
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(NavigationBar).call(this, {
	      target: BX(options.id),
	      items: options.items
	    }));

	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _id, {
	      writable: true,
	      value: void 0
	    });

	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _binding, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _id, options.id);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _binding, options.binding);
	    return _this;
	  }

	  babelHelpers.createClass(NavigationBar, [{
	    key: "openUrl",
	    value: function openUrl(itemId, url) {
	      if (!main_core.Type.isStringFilled(url)) {
	        return;
	      }

	      if (babelHelpers.classPrivateFieldGet(this, _binding) && main_core.Type.isPlainObject(babelHelpers.classPrivateFieldGet(this, _binding))) {
	        var category = main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _binding).category) ? babelHelpers.classPrivateFieldGet(this, _binding).category : '';
	        var name = main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _binding).name) ? babelHelpers.classPrivateFieldGet(this, _binding).name : '';
	        var key = main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _binding).key) ? babelHelpers.classPrivateFieldGet(this, _binding).key : '';

	        if (category !== '' && name !== '' && key !== '') {
	          var value = itemId + ":" + BX.formatDate(new Date(), 'YYYYMMDD');
	          BX.userOptions.save(category, name, key, value, false);
	        }
	      }

	      setTimeout(function () {
	        window.location.href = url;
	      }, 150);
	    }
	  }]);
	  return NavigationBar;
	}(ui_navigationpanel.NavigationPanel);
	namespace$1.NavigationBar = NavigationBar;

	exports.ToolbarComponent = ToolbarComponent;
	exports.NavigationBar = NavigationBar;

}((this.BX.Crm = this.BX.Crm || {}),BX.Event,BX.UI,BX.Crm,BX.Main,BX,BX,BX.UI));
//# sourceMappingURL=toolbar-component.bundle.js.map
