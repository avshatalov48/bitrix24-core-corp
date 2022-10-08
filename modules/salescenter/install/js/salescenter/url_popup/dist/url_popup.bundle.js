this.BX = this.BX || {};
(function (exports,main_core,main_popup,ui_vue,salescenter_manager) {
	'use strict';

	ui_vue.Vue.component('bx-salescenter-url-popup', {
	  data: function data() {
	    return {
	      urlCheckStatus: 0,
	      errorMessage: null,
	      previousUrl: null,
	      isEmptyNameOnSave: false,
	      isDefaultName: false,
	      previousName: null,
	      fieldsPopupMenuId: 'salescenter-url-fields-popup',
	      params: [],
	      fieldsMap: null
	    };
	  },
	  created: function created() {
	    var _this = this;

	    this.$root.$on('onAddUrlPopupCreate', this.setPageData);
	    this.previousUrl = new Set();
	    var checkUrlDebounce = BX.debounce(this.checkUrl, 1500, this);

	    this.debounceCheckUrl = function () {
	      var url = _this.$refs['urlInput'].value;

	      if (_this.previousUrl.has(url)) {
	        return;
	      }

	      _this.errorMessage = null;
	      _this.urlCheckStatus = 0;
	      checkUrlDebounce();
	    };
	  },
	  mounted: function mounted() {
	    var _this2 = this;

	    setTimeout(function () {
	      _this2.$refs['urlInput'].focus();
	    }, 100);
	  },
	  beforeDestroy: function beforeDestroy() {
	    this.$root.$off('onAddUrlPopupCreate', this.setPageData);
	  },
	  methods: {
	    onTitleKeyUp: function onTitleKeyUp() {
	      if (this.isDefaultName && this.$refs['nameInput'].value !== this.previousName) {
	        this.isDefaultName = false;
	        this.previousName = this.$refs['nameInput'].value;
	      }
	    },
	    checkUrl: function checkUrl() {
	      var _this3 = this;

	      var url = this.$refs['urlInput'].value;
	      this.$refs['isFrameDeniedInput'].value = '';
	      this.errorMessage = null;
	      this.urlCheckStatus = 0;
	      this.previousUrl = new Set();
	      this.previousUrl.add(url);

	      if (url.length < 5) {
	        return;
	      }

	      this.urlCheckStatus = 1;
	      this.$root.$app.checkUrl(url).then(function (result) {
	        _this3.urlCheckStatus = 11;

	        if (!result.answer.result) {
	          //this.urlCheckStatus = 23;
	          if (url.indexOf('http://') !== 0 && url.indexOf('https://') !== 0) {
	            _this3.$refs['urlInput'].value = 'http://' + url;
	          }

	          _this3.errorMessage = _this3.localize.SALESCENTER_ACTION_ADD_CUSTOM_NO_META;
	        } else {
	          if (_this3.$refs['nameInput'].value.length <= 0 || _this3.isDefaultName) {
	            _this3.$refs['nameInput'].value = result.answer.result.title;
	            _this3.isDefaultName = true;
	            _this3.previousName = _this3.$refs['nameInput'].value;
	          }

	          if (result.answer.result.extra && result.answer.result.extra.effectiveUrl) {
	            _this3.$refs['urlInput'].value = result.answer.result.extra.effectiveUrl;

	            _this3.previousUrl.add(result.answer.result.extra.effectiveUrl);
	          } else {
	            _this3.$refs['urlInput'].value = result.answer.result.url;
	          }

	          if (result.answer.result.isFrameDenied === true) {
	            _this3.$refs['isFrameDeniedInput'].value = 'Y';
	          }

	          _this3.$refs['nameInput'].focus();
	        }
	      })["catch"](function (result) {
	        _this3.urlCheckStatus = 22;
	        _this3.errorMessage = result.answer.error_description;
	      });
	    },
	    save: function save() {
	      var _this4 = this;

	      this.isEmptyNameOnSave = false;

	      if (this.$refs['nameInput'].value.length <= 0) {
	        this.isEmptyNameOnSave = true;
	        return;
	      }

	      if (this.urlCheckStatus > 10 && this.urlCheckStatus < 20) {
	        var params = [];
	        this.params.forEach(function (param) {
	          params.push(param.chain);
	        });

	        if (params.length <= 0) {
	          params = 'false';
	        }

	        this.$root.$app.addPage({
	          url: this.$refs['urlInput'].value,
	          name: this.$refs['nameInput'].value,
	          id: this.$refs['idInput'].value,
	          isFrameDenied: this.$refs['isFrameDeniedInput'].value,
	          isWebform: this.$refs['isWebformInput'].value,
	          params: params
	        }).then(function (result) {
	          _this4.$refs['idInput'].value = result.answer.result.page.id;
	          _this4.$refs['isSaved'].value = 'y';

	          if (_this4.$root.$app.addUrlPopup) {
	            _this4.$root.$app.addUrlPopup.close();
	          }
	        })["catch"](function (result) {
	          _this4.errorMessage = result.answer.error_description;
	        });
	      }
	    },
	    cancel: function cancel() {
	      if (this.$root.$app.addUrlPopup) {
	        this.$root.$app.addUrlPopup.close();
	      }
	    },
	    setPageData: function setPageData() {
	      var _this5 = this;

	      var page = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
	        url: '',
	        name: '',
	        id: 0,
	        isWebform: '',
	        params: []
	      };
	      var fields = ['url', 'name', 'id', 'isWebform'];

	      for (var i in fields) {
	        var name = fields[i];
	        var value = '';

	        if (page.hasOwnProperty(name)) {
	          value = page[name];
	        }

	        var input = this.$refs[name + 'Input'];

	        if (input) {
	          input.value = value;
	        }
	      }

	      this.checkUrl();
	      this.$refs['isSaved'].value = 'n';
	      this.params = [];

	      if (main_core.Type.isArray(page.params) && page.params.length > 0) {
	        salescenter_manager.Manager.getFieldsMap().then(function (fields) {
	          page.params.forEach(function (param) {
	            var field = _this5.findFieldByParam(fields, param.field);

	            if (field) {
	              _this5.params.push(field);
	            }
	          });
	        });
	      }
	    },
	    findFieldByParam: function findFieldByParam(fields, param) {
	      if (!main_core.Type.isArray(fields) || !main_core.Type.isString(param) || param.length <= 0) {
	        return null;
	      }

	      return this.getEntityField({
	        items: fields
	      }, param);
	    },
	    getEntityField: function getEntityField(entity, chain) {
	      var _this6 = this;

	      var result = null;

	      if (!main_core.Type.isPlainObject(entity) || !main_core.Type.isString(chain) || chain.length <= 0 || !entity.items || !main_core.Type.isArray(entity.items)) {
	        return result;
	      }

	      var parts = chain.split('.');
	      entity.items.forEach(function (field) {
	        if (!result) {
	          if (field.name === parts[0]) {
	            if (parts.length === 1) {
	              result = field;
	            } else {
	              parts.shift();
	              result = _this6.getEntityField(field, parts.join('.'));
	            }
	          }
	        }
	      });
	      return result;
	    },
	    openFieldsMenu: function openFieldsMenu() {
	      var _this7 = this;

	      salescenter_manager.Manager.getFieldsMap().then(function (fields) {
	        fields = _this7.prepareFieldsMenu(fields);
	        main_popup.PopupMenu.show({
	          id: _this7.fieldsPopupMenuId,
	          bindElement: _this7.$refs['fieldsSelector'],
	          items: fields,
	          offsetLeft: 0,
	          offsetTop: 0,
	          closeByEsc: true,
	          zIndex: 2000,
	          zIndexAbsolute: 2000,
	          maxHeight: 500
	        });
	      })["catch"](function (errors) {
	        if (main_core.Type.isArray(errors)) {
	          _this7.errorMessage = errors.pop().message;
	        } else {
	          _this7.errorMessage = errors;
	        }
	      });
	    },
	    prepareFieldsMenu: function prepareFieldsMenu(fields) {
	      var _this8 = this;

	      var result = [];
	      fields.forEach(function (item) {
	        var menu = {
	          text: main_core.Text.encode(item.title),
	          dataset: {
	            rootMenu: _this8.fieldsPopupMenuId
	          }
	        };

	        if (main_core.Type.isArray(item.items)) {
	          menu.items = _this8.prepareFieldsMenu(item.items);
	        } else {
	          menu.onclick = function () {
	            _this8.selectField(item);
	          };
	        }

	        result.push(menu);
	      });
	      return result;
	    },
	    selectField: function selectField(field) {
	      this.removeParam(field);
	      this.params.push(field);
	    },
	    removeParam: function removeParam(param) {
	      this.params = this.params.filter(function (item) {
	        return item.chain !== param.chain;
	      });
	    }
	  },
	  computed: {
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALESCENTER_');
	    },
	    hasParams: function hasParams() {
	      return this.params && this.params.length > 0;
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-add-custom-url-popup\">\n\t\t\t<div class=\"salescenter-add-custom-url-popup-form\">\n\t\t\t\t<div class=\"ui-ctl-label-text\">{{localize.SALESCENTER_ACTION_ADD_CUSTOM_INPUT}}</div>\n\t\t\t\t<div class=\"ui-ctl ui-ctl-ext-after-icon ui-ctl-textbox\" :class=\"{\n\t\t\t\t\t'ui-ctl-danger': this.urlCheckStatus > 20 || errorMessage, \n\t\t\t\t\t'ui-ctl-success': this.urlCheckStatus > 10 && this.urlCheckStatus < 20\n\t\t\t\t}\">\n\t\t\t\t\t<div class=\"ui-ctl-ext-after ui-ctl-icon-dots salescenter-url-params-selector\" @click=\"openFieldsMenu\" ref=\"fieldsSelector\"></div>\n\t\t\t\t\t<input name=\"url\" class=\"ui-ctl-element\" @keyup=\"debounceCheckUrl\" ref=\"urlInput\" :placeholder=\"localize.SALESCENTER_ACTION_ADD_CUSTOM_INPUT_PLACEHOLDER\" autocomplete=\"off\"/>\n\t\t\t\t</div>\n\t\t\t\t<div style=\"min-height: 20px;\">\n\t\t\t\t\t<template v-if=\"urlCheckStatus > 20 || errorMessage\" class=\"salescenter-url-message\">{{errorMessage}}</template>\n\t\t\t\t\t<template v-else-if=\"urlCheckStatus > 10\"></template>\n\t\t\t\t\t<template v-else-if=\"urlCheckStatus > 0\">{{localize.SALESCENTER_ACTION_ADD_CUSTOM_PENDING}}</template>\n\t\t\t\t</div>\n\t\t\t\t<label class=\"ui-ctl\" :class=\"{\n\t\t\t\t\t'hidden': !this.hasParams\n\t\t\t\t}\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">{{localize.SALESCENTER_ACTION_URL_PARAMS}}</div>\n\t\t\t\t\t<div class=\"salescenter-url-params-field\">\n\t\t\t\t\t\t<div v-for=\"param in params\" class=\"salescenter-url-param\">\n\t\t\t\t\t\t\t<span class=\"salescenter-url-param-name\">{{param.fullName}}</span>\n\t\t\t\t\t\t\t<span class=\"salescenter-url-param-delete\" @click=\"removeParam(param)\"></span>\n\t\t\t\t\t    </div>\n\t\t\t\t    </div>\n\t\t\t\t</label>\n\t\t\t\t<div class=\"ui-ctl-label-text\">{{localize.SALESCENTER_ACTION_ADD_CUSTOM_NAME}}</div>\n\t\t\t\t<label class=\"ui-ctl ui-ctl-textbox\" :class=\"{\n\t\t\t\t\t'ui-ctl-danger': this.isEmptyNameOnSave\n\t\t\t\t}\">\n\t\t\t\t\t<input class=\"ui-ctl-element\" name=\"name\" @keyup=\"onTitleKeyUp\" ref=\"nameInput\" :placeholder=\"localize.SALESCENTER_ACTION_ADD_CUSTOM_NAME_PLACEHOLDER\" autocomplete=\"off\"/>\n\t\t\t\t</label>\n\t\t\t\t<input type=\"hidden\" name=\"id\" value=\"\" ref=\"idInput\" id=\"salescenter-app-add-custom-url-id\" />\n\t\t\t\t<input type=\"hidden\" name=\"isFrameDenied\" value=\"\" ref=\"isFrameDeniedInput\"/>\n\t\t\t\t<input type=\"hidden\" name=\"isWebform\" value=\"\" ref=\"isWebformInput\"/>\n\t\t\t\t<input type=\"hidden\" name=\"isSaved\" value=\"n\" id=\"salescenter-app-add-custom-url-is-saved\" ref=\"isSaved\" />\n\t\t\t</div>\n\t\t\t<div class=\"popup-window-buttons salescenter-add-custom-url-popup-buttons\">\n\t\t\t\t<button :class=\"{\n\t\t\t\t\t'ui-btn-disabled': this.urlCheckStatus < 10 || this.urlCheckStatus > 20\n\t\t\t\t}\" class=\"ui-btn ui-btn-md ui-btn-success\" @click=\"save\">{{localize.SALESCENTER_ACTION_ADD_CUSTOM_SAVE}}</button>\n\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-link\" @click=\"cancel\">{{localize.SALESCENTER_CANCEL}}</button>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

}((this.BX.Salescenter = this.BX.Salescenter || {}),BX,BX.Main,BX,BX.Salescenter));
//# sourceMappingURL=url_popup.bundle.js.map
