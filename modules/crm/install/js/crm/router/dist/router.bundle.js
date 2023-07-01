this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var instance = null;

	var ListViewTypes = function ListViewTypes() {
	  babelHelpers.classCallCheck(this, ListViewTypes);
	};

	babelHelpers.defineProperty(ListViewTypes, "KANBAN", 'KANBAN');
	babelHelpers.defineProperty(ListViewTypes, "LIST", 'LIST');

	/**
	 * @memberOf BX.Crm
	 */
	var Router = /*#__PURE__*/function () {
	  function Router() {
	    babelHelpers.classCallCheck(this, Router);
	    babelHelpers.defineProperty(this, "defaultRootUrlTemplates", {});
	    babelHelpers.defineProperty(this, "customRootUrlTemplates", {});
	    babelHelpers.defineProperty(this, "currentViews", {});
	  }

	  babelHelpers.createClass(Router, [{
	    key: "setUrlTemplates",

	    /**
	     * @public
	     * @param params
	     * @return {BX.Crm.Router}
	     */
	    value: function setUrlTemplates(params) {
	      if (main_core.Type.isPlainObject(params.defaultRootUrlTemplates)) {
	        this.defaultRootUrlTemplates = params.defaultRootUrlTemplates;
	      }

	      if (main_core.Type.isPlainObject(params.customRootUrlTemplates)) {
	        this.customRootUrlTemplates = params.customRootUrlTemplates;
	      }

	      return this;
	    }
	  }, {
	    key: "setCurrentListView",
	    value: function setCurrentListView(entityTypeId, view) {
	      this.currentViews[entityTypeId] = view;
	      return this;
	    }
	  }, {
	    key: "getCurrentListView",
	    value: function getCurrentListView(entityTypeId) {
	      return this.currentViews[entityTypeId] || ListViewTypes.LIST;
	    }
	  }, {
	    key: "openTypeDetail",
	    value: function openTypeDetail(typeId, options) {
	      if (!main_core.Type.isPlainObject(options)) {
	        options = {};
	      }

	      options.width = 702;
	      var uri = this.getTypeDetailUrl(typeId);

	      if (uri) {
	        return Router.openSlider(uri.toString(), options);
	      }

	      return null;
	    }
	    /**
	     * @protected
	     * @param component
	     * @param entityTypeId
	     * @return {string|null}
	     */

	  }, {
	    key: "getTemplate",
	    value: function getTemplate(component) {
	      var entityTypeId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;

	      if (entityTypeId > 0 && this.customRootUrlTemplates.hasOwnProperty(entityTypeId)) {
	        if (this.customRootUrlTemplates[entityTypeId].hasOwnProperty(component)) {
	          return this.customRootUrlTemplates[entityTypeId][component];
	        }

	        return null;
	      }

	      return this.defaultRootUrlTemplates.hasOwnProperty(component) ? this.defaultRootUrlTemplates[component] : null;
	    }
	  }, {
	    key: "getTypeDetailUrl",
	    value: function getTypeDetailUrl() {
	      var entityTypeId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
	      var template = this.getTemplate('bitrix:crm.type.detail', entityTypeId);

	      if (template) {
	        return new main_core.Uri(template.replace('#entityTypeId#', entityTypeId));
	      }

	      return null;
	    }
	  }, {
	    key: "getTypeListUrl",
	    value: function getTypeListUrl() {
	      var template = this.getTemplate('bitrix:crm.type.list');

	      if (template) {
	        return new main_core.Uri(template);
	      }

	      return null;
	    }
	  }, {
	    key: "openTypeHelpPage",
	    value: function openTypeHelpPage() {
	      Router.openHelper(null, 13315798);
	    }
	  }, {
	    key: "showFeatureSlider",
	    value: function showFeatureSlider(event, item) {
	      Router.Instance.closeSettingsMenu(event, item);
	      BX.UI.InfoHelper.show('limit_smart_process_automation');
	    }
	    /**
	     * For dynamic entities only.
	     * Does not support knowledge about whether kanban available or not.
	     *
	     * @param entityTypeId
	     * @param categoryId
	     */

	  }, {
	    key: "getItemListUrlInCurrentView",
	    value: function getItemListUrlInCurrentView(entityTypeId) {
	      var categoryId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
	      var currentListView = this.getCurrentListView(entityTypeId);
	      var template;

	      if (currentListView === ListViewTypes.KANBAN) {
	        template = this.getTemplate('bitrix:crm.kanban', entityTypeId);
	      } else {
	        template = this.getTemplate('bitrix:crm.item.list', entityTypeId);
	      }

	      if (template) {
	        return new main_core.Uri(template.replace('#entityTypeId#', entityTypeId).replace('#categoryId#', categoryId));
	      }

	      return null;
	    }
	    /**
	     * For factory based entities only.
	     * Does not support knowledge about whether kanban available or not.
	     *
	     * @public
	     * @param entityTypeId
	     * @param categoryId
	     * @return {null|BX.Uri}
	     */

	  }, {
	    key: "getKanbanUrl",
	    value: function getKanbanUrl(entityTypeId) {
	      var categoryId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
	      var template = this.getTemplate('bitrix:crm.item.kanban', entityTypeId);

	      if (template) {
	        return new main_core.Uri(template.replace('#entityTypeId#', entityTypeId).replace('#categoryId#', categoryId));
	      }

	      return null;
	    }
	    /**
	     * For factory based entities only
	     *
	     * @public
	     * @param entityTypeId
	     * @param categoryId
	     * @return {null|BX.Uri}
	     */

	  }, {
	    key: "getItemListUrl",
	    value: function getItemListUrl(entityTypeId) {
	      var categoryId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
	      var template = this.getTemplate('bitrix:crm.item.list', entityTypeId);

	      if (template) {
	        return new main_core.Uri(template.replace('#entityTypeId#', entityTypeId).replace('#categoryId#', categoryId));
	      }

	      return null;
	    }
	  }, {
	    key: "openDocumentSlider",
	    value: function openDocumentSlider(documentId) {
	      return Router.openSlider('/bitrix/components/bitrix/crm.document.view/slider.php?documentId=' + documentId, {
	        width: 1060,
	        loader: '/bitrix/components/bitrix/crm.document.view/templates/.default/images/document_view.svg'
	      });
	    }
	  }, {
	    key: "openSignDocumentSlider",
	    value: function openSignDocumentSlider(documentId, memberHash) {
	      // todo make a url template
	      return Router.openSlider('/bitrix/components/bitrix/crm.signdocument.view/slider.php?documentId=' + documentId + '&memberHash=' + memberHash, {
	        width: 1060
	      });
	    }
	  }, {
	    key: "openSignDocumentModifySlider",
	    value: function openSignDocumentModifySlider(documentId) {
	      return Router.openSlider('/sign/doc/0/?docId=' + documentId + '&stepId=changePartner&noRedirect=Y');
	    }
	  }, {
	    key: "openCalendarEventSlider",
	    value: function openCalendarEventSlider(eventId, isSharing) {
	      var sliderId = 'crm-calendar-slider-' + eventId + '-' + Math.floor(Math.random() * 1000);
	      return new (window.top.BX || window.BX).Calendar.SliderLoader(eventId, {
	        sliderId: sliderId,
	        isSharing: isSharing
	      }).show();
	    }
	  }, {
	    key: "closeSettingsMenu",
	    value: function closeSettingsMenu(event, item) {
	      if (item && main_core.Type.isFunction(item.getMenuWindow)) {
	        var _window = item.getMenuWindow();

	        if (_window) {
	          _window.close();

	          return;
	        }
	      }

	      var menu = this;

	      if (menu && main_core.Type.isFunction(menu.close)) {
	        menu.close();
	      }
	    }
	  }], [{
	    key: "openSlider",
	    value: function openSlider(url, options) {
	      if (!main_core.Type.isPlainObject(options)) {
	        options = {};
	      }

	      options = _objectSpread(_objectSpread({}, {
	        cacheable: false,
	        allowChangeHistory: true,
	        events: {}
	      }), options);
	      return new Promise(function (resolve) {
	        if (main_core.Type.isString(url) && url.length > 1) {
	          options.events.onClose = function (event) {
	            resolve(event.getSlider());
	          };

	          BX.SidePanel.Instance.open(url, options);
	        } else {
	          resolve();
	        }
	      });
	    }
	  }, {
	    key: "openHelper",
	    value: function openHelper() {
	      var event = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      var code = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

	      if (event && main_core.Type.isFunction(event.preventDefault)) {
	        event.preventDefault();
	      }

	      if (top.BX.Helper && code > 0) {
	        top.BX.Helper.show('redirect=detail&code=' + code);
	      }
	    }
	  }, {
	    key: "Instance",
	    get: function get() {
	      if (window.top !== window && main_core.Reflection.getClass('top.BX.Crm.Router')) {
	        return window.top.BX.Crm.Router.Instance;
	      }

	      if (instance === null) {
	        instance = new Router();
	      }

	      return instance;
	    }
	  }]);
	  return Router;
	}();

	exports.Router = Router;

}((this.BX.Crm = this.BX.Crm || {}),BX));
//# sourceMappingURL=router.bundle.js.map
