this.BX = this.BX || {};
this.BX.Mobile = this.BX.Mobile || {};
this.BX.Mobile.Field = this.BX.Mobile.Field || {};
(function (exports,main_core) {
	'use strict';

	var BX = window.BX,
	    BXMobileApp = window.BXMobileApp;

	var nodeElementCrm = function () {
	  var nodeElementCrm = function nodeElementCrm(node, container, useOnChangeEvent, availableTypes) {
	    this.node = node;
	    this.container = container;
	    this.click = BX.delegate(this.click, this);
	    this.callback = BX.delegate(this.callback, this);
	    this.multiple = this.container.hasAttribute('multiple');
	    this.useOnChangeEvent = useOnChangeEvent || false;
	    this.availableTypes = availableTypes || [];
	    BX.bind(this.node, "click", this.click);
	    this.urls = this.getUrls();
	  };

	  nodeElementCrm.prototype = {
	    getUrls: function getUrls() {
	      var listUrl = BX.message('SITE_DIR') + 'mobile/index.php?mobile_action=get_element_crm_list&' + this.encodeQueryData(this.availableTypes);
	      return {
	        "list": listUrl,
	        "profile": BX.message("interface_form_user_url")
	      };
	    },
	    encodeQueryData: function encodeQueryData(data) {
	      var ret = [];

	      for (var d in data) {
	        ret.push(encodeURIComponent(d) + '=' + encodeURIComponent(data[d]));
	      }

	      return ret.join('&');
	    },
	    click: function click(e) {
	      this.show();
	      return BX.PreventDefault(e);
	    },
	    show: function show() {
	      new BXMobileApp.UI.Table({
	        url: this.urls.list,
	        table_settings: {
	          callback: this.callback,
	          //use_sections:true,
	          multiple: this.multiple,
	          searchField: true,
	          selected: this.getSelectedItems(),
	          //({company: ['CO_6']})
	          showtitle: true,
	          //name: "List",
	          markmode: true,
	          // multiple: this.multiple,
	          // return_full_mode: true,
	          skipSpecialChars: true,
	          //	use_sections:true,
	          modal: true,
	          alphabet_index: true,
	          // outsection: false,
	          okname: BX.message("interface_form_select"),
	          cancelname: BX.message("interface_form_cancel"),
	          cache: false
	        }
	      }, "users").show();
	    },
	    callback: function callback(data) {
	      var _this = this;

	      this.container.length = 0;
	      var div = this.container.nextElementSibling;

	      while (div.firstChild) {
	        div.removeChild(div.firstChild);
	      }

	      var sections = [];

	      for (var key in data) {
	        if (data.hasOwnProperty(key)) {
	          if (!(key in sections)) {
	            sections.push(key);
	            var span = document.createElement('span');
	            span.setAttribute('class', 'mobile-grid-data-span mobile-grid-crm-element-category-title');
	            span.innerHTML = main_core.Loc.getMessage('CRM_ENTITY_TYPE_' + key.toUpperCase());
	            div.appendChild(span);
	          }

	          var section = data[key];
	          section.forEach(function (item, i, arr) {
	            var option = new Option(item.NAME, item.ID, true, true);

	            _this.container.add(option);

	            var link = document.createElement('a');
	            link.href = item.LINK;
	            link.text = item.NAME;
	            var span = document.createElement('span');
	            span.setAttribute('class', 'mobile-grid-data-span');
	            span.appendChild(link);
	            div.appendChild(span);
	          });
	        }
	      }

	      if (this.useOnChangeEvent) {
	        BX.onCustomEvent(this, 'BX.Mobile.Field:onChangeUserField', [this, this.node]);
	      }
	    },
	    getSelectedItems: function getSelectedItems() {
	      var options = this.container.options;
	      var selectedItems = {};

	      for (var key in options) {
	        if (options.hasOwnProperty(key)) {
	          if (selectedItems[options[key].getAttribute('data-category')] === undefined) {
	            selectedItems[options[key].getAttribute('data-category')] = [];
	          }

	          selectedItems[options[key].getAttribute('data-category')].push(options[key].value);
	        }
	      }

	      return selectedItems;
	    }
	  };
	  return nodeElementCrm;
	}();

	window.app.exec('enableCaptureKeyboard', true);

	BX.Mobile.Field.ElementCrm = function (params) {
	  this.availableTypes = params['availableTypes'] || [];
	  this.useOnChangeEvent = params['useOnChangeEvent'] || false;
	  this.init(params);
	};

	BX.Mobile.Field.ElementCrm.prototype = {
	  __proto__: BX.Mobile.Field.prototype,
	  bindElement: function bindElement(node) {
	    var result = null;

	    if (BX(node)) {
	      result = new nodeElementCrm(node, BX("".concat(node.id, "_select")), this.useOnChangeEvent, this.availableTypes);
	    }

	    return result;
	  }
	};

}((this.BX.Mobile.Field.ElementCrm = this.BX.Mobile.Field.ElementCrm || {}),BX));
//# sourceMappingURL=mobile.js.map
