/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var instance = null;
	var Manager = /*#__PURE__*/function () {
	  function Manager() {
	    babelHelpers.classCallCheck(this, Manager);
	    babelHelpers.defineProperty(this, "urlTemplates", {});
	  }
	  babelHelpers.createClass(Manager, [{
	    key: "setUrlTemplates",
	    value: function setUrlTemplates(urlTemplates) {
	      if (main_core.Type.isPlainObject(urlTemplates)) {
	        this.urlTemplates = urlTemplates;
	      }
	      return this;
	    }
	  }, {
	    key: "openTasks",
	    value: function openTasks(typeId, itemId) {
	      var _this = this;
	      return new Promise(function (resolve) {
	        Manager.openSlider(_this.getTasksUrl(typeId, itemId).toString(), {
	          width: 580,
	          cacheable: false,
	          allowChangeHistory: false
	        }).then(function (slider) {
	          var isCompleted = false;
	          var item = null;
	          if (slider.isLoaded()) {
	            isCompleted = slider.getData().get('isCompleted') || false;
	            item = slider.getData().get('item') || null;
	          }
	          resolve({
	            isCompleted: isCompleted,
	            item: item
	          });
	        });
	      });
	    }
	  }, {
	    key: "getTasksUrl",
	    value: function getTasksUrl(typeId, itemId) {
	      var template = this.urlTemplates['bitrix:rpa.task'];
	      if (template) {
	        return new main_core.Uri(template.replace('#typeId#', typeId).replace('#elementId#', itemId));
	      }
	      return null;
	    }
	  }, {
	    key: "openKanban",
	    value: function openKanban(typeId) {
	      var template = this.urlTemplates['bitrix:rpa.kanban'];
	      if (template) {
	        location.href = new main_core.Uri(template.replace('#typeId#', typeId)).toString();
	        return true;
	      }
	      return false;
	    }
	  }, {
	    key: "openTypeDetail",
	    value: function openTypeDetail(typeId, options) {
	      if (!main_core.Type.isPlainObject(options)) {
	        options = {};
	      }
	      options.width = 702;
	      var template = this.urlTemplates['bitrix:rpa.type.detail'];
	      if (template) {
	        return Manager.openSlider(template.replace('#id#', typeId), options);
	      }
	      return null;
	    }
	  }, {
	    key: "getItemDetailUrl",
	    value: function getItemDetailUrl(typeId) {
	      var itemId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
	      var template = this.urlTemplates['bitrix:rpa.item.detail'];
	      if (template) {
	        return new main_core.Uri(template.replace('#typeId#', typeId).replace('#id#', itemId));
	      }
	      return null;
	    }
	  }, {
	    key: "openItemDetail",
	    value: function openItemDetail(typeId) {
	      var itemId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
	      var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
	      var uri = this.getItemDetailUrl(typeId, itemId);
	      if (uri) {
	        return Manager.openSlider(uri.toString(), options);
	      }
	      return null;
	    }
	  }, {
	    key: "getStageListUrl",
	    value: function getStageListUrl(typeId) {
	      var template = this.urlTemplates['bitrix:rpa.stage.list'];
	      if (template) {
	        return new main_core.Uri(template.replace('#typeId#', typeId));
	      }
	      return null;
	    }
	  }, {
	    key: "openStageList",
	    value: function openStageList(typeId) {
	      var url = this.getStageListUrl(typeId);
	      if (url) {
	        return Manager.openSlider(url.toString());
	      }
	      return null;
	    }
	  }, {
	    key: "getFieldsListUrl",
	    value: function getFieldsListUrl(typeId) {
	      var template = this.urlTemplates['fieldsList'];
	      if (template) {
	        return new main_core.Uri(template.replace('#typeId#', typeId));
	      }
	      return null;
	    }
	  }, {
	    key: "openFieldsList",
	    value: function openFieldsList(typeId) {
	      var url = this.getFieldsListUrl(typeId);
	      if (url) {
	        return Manager.openSlider(url.toString());
	      }
	      return null;
	    }
	  }, {
	    key: "getFieldDetailUrl",
	    value: function getFieldDetailUrl(typeId, fieldId) {
	      var template = this.urlTemplates['fieldDetail'];
	      if (template) {
	        return new main_core.Uri(template.replace('#typeId#', typeId).replace('#fieldId#', fieldId));
	      }
	      return null;
	    }
	  }, {
	    key: "openFieldDetail",
	    value: function openFieldDetail(typeId, fieldId, options) {
	      var url = this.getFieldDetailUrl(typeId, fieldId);
	      if (url) {
	        return Manager.openSlider(url.toString(), options);
	      }
	      return null;
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
	  }, {
	    key: "showFeatureSlider",
	    value: function showFeatureSlider(event, item) {
	      Manager.Instance.closeSettingsMenu(event, item);
	      BX.UI.InfoHelper.show('limit_robotic_process_automation');
	    }
	  }], [{
	    key: "addEditor",
	    value: function addEditor(typeId, itemId, editor) {
	      var editorClass = main_core.Reflection.getClass('BX.UI.EntityEditor');
	      if (!editorClass) {
	        return;
	      }
	      if (main_core.Type.isInteger(typeId) && main_core.Type.isInteger(itemId) && editor instanceof BX.UI.EntityEditor) {
	        if (!Manager.editors[typeId]) {
	          Manager.editors[typeId] = {};
	        }
	        Manager.editors[typeId][itemId] = editor;
	      }
	    }
	    /**
	     * @param typeId
	     * @param itemId
	     * @returns {null|BX.UI.EntityEditor}
	     */
	  }, {
	    key: "getEditor",
	    value: function getEditor(typeId, itemId) {
	      if (main_core.Type.isInteger(typeId) && main_core.Type.isInteger(itemId) && Manager.editors[typeId] && Manager.editors[typeId][itemId]) {
	        return Manager.editors[typeId][itemId];
	      }
	      return null;
	    }
	  }, {
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
	    key: "calculateTextColor",
	    value: function calculateTextColor(baseColor) {
	      var r, g, b;
	      if (baseColor.length > 7) {
	        var hexComponent = baseColor.split("(")[1].split(")")[0];
	        hexComponent = hexComponent.split(",");
	        r = parseInt(hexComponent[0]);
	        g = parseInt(hexComponent[1]);
	        b = parseInt(hexComponent[2]);
	      } else {
	        if (/^#([A-Fa-f0-9]{3}){1,2}$/.test(baseColor)) {
	          var c = baseColor.substring(1).split('');
	          if (c.length === 3) {
	            c = [c[0], c[0], c[1], c[1], c[2], c[2]];
	          }
	          c = '0x' + c.join('');
	          r = c >> 16 & 255;
	          g = c >> 8 & 255;
	          b = c & 255;
	        }
	      }
	      var y = 0.21 * r + 0.72 * g + 0.07 * b;
	      return y < 145 ? "#fff" : "#333";
	    }
	  }, {
	    key: "Instance",
	    get: function get() {
	      if (window.top !== window) {
	        return window.top.BX.Rpa.Manager.Instance;
	      }
	      if (instance === null) {
	        instance = new Manager();
	      }
	      return instance;
	    }
	  }]);
	  return Manager;
	}();
	babelHelpers.defineProperty(Manager, "editors", {});

	exports.Manager = Manager;

}((this.BX.Rpa = this.BX.Rpa || {}),BX));
//# sourceMappingURL=manager.bundle.js.map
