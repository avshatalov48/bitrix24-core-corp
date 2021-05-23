this.BX = this.BX || {};
this.BX.Mobile = this.BX.Mobile || {};
this.BX.Mobile.Field = this.BX.Mobile.Field || {};
(function (exports,main_core) {
	'use strict';

	var BX = window.BX,
	    BXMobileApp = window.BXMobileApp;

	var nodeSelectUser = function () {
	  var nodeSelectUser = function nodeSelectUser(select, eventNode) {
	    this.click = BX.delegate(this.click, this);
	    this.callback = BX.delegate(this.callback, this);
	    this.drop = BX.delegate(this.drop, this);
	    this.select = BX(select);
	    this.container = this.select.nextElementSibling;
	    this.eventNode = BX(eventNode);
	    BX.bind(this.eventNode, "click", this.click);
	    this.multiple = select.hasAttribute("multiple");
	    this.showDrop = !(select.hasAttribute("bx-can-drop") && select.getAttribute("bx-can-drop").toString() == "false");
	    this.urls = {
	      "list": BX.message('SITE_DIR') + 'mobile/index.php?mobile_action=get_user_list',
	      "profile": BX.message("interface_form_user_url")
	    };
	    this.actualizeNodes(); // this.expand = BX("expand_" + this.select.getAttribute("id"));
	    // this.visCount = BX("count_" + this.select.getAttribute("id"));
	    // if (!this.container.parentNode.hasAttribute("bx-fastclick-bound"))
	    // {
	    // 	this.container.parentNode.setAttribute("bx-fastclick-bound", "Y");
	    // 	FastClick.attach(this.container.parentNode.parentNode);
	    // }
	  };

	  nodeSelectUser.prototype = {
	    click: function click(e) {
	      this.show();
	      return BX.PreventDefault(e);
	    },
	    show: function show() {
	      new BXMobileApp.UI.Table({
	        url: this.urls.list,
	        table_settings: {
	          callback: this.callback,
	          markmode: true,
	          multiple: this.multiple,
	          return_full_mode: true,
	          skipSpecialChars: true,
	          modal: true,
	          alphabet_index: true,
	          outsection: false,
	          okname: BX.message("interface_form_select"),
	          cancelname: BX.message("interface_form_cancel")
	        }
	      }, "users").show();
	    },
	    drop: function drop() {
	      var node = BX.proxy_context,
	          id = node.id.replace(this.select.id + '_del_', '');

	      for (var ii = 0; ii < this.select.options.length; ii++) {
	        if (this.select.options[ii].value === id) {
	          BX.remove(BX.findParent(node, {
	            "tagName": "DIV",
	            "className": "mobile-grid-field-select-user-item-outer"
	          }));
	          BX.remove(this.select.options[ii]);
	        }
	      }

	      if (this.select.options.length <= 0 && !this.multiple) {
	        this.eventNode.innerHTML = BX.message('interface_form_select');
	      } // if (this.expand)
	      // 	this.expand.value = this.select.options.length;
	      // if (this.visCount)
	      // 	this.visCount.innerHTML = this.select.options.length - 3;


	      BX.onCustomEvent(this, "onChange", [this, this.select]);
	    },
	    actualizeNodes: function actualizeNodes() {
	      // if (this.expand)
	      // {
	      // 	this.expand.value = this.select.options.length;
	      // }
	      // if (this.visCount)
	      // {
	      // 	this.visCount.innerHTML = this.select.options.length - 3;
	      // }
	      for (var ii = 0; ii < this.select.options.length; ii++) {
	        if (BX(this.select.id + '_del_' + this.select.options[ii].value)) {
	          BX.bind(BX(this.select.id + '_del_' + this.select.options[ii].value), "click", this.drop);
	        }
	      }
	    },
	    buildNodes: function buildNodes(items) {
	      var options = '',
	          html = '',
	          user,
	          existedUsers = [];

	      for (var _ii = 0; _ii < this.select.options.length; _ii++) {
	        existedUsers.push(this.select.options[_ii].value.toString());
	      }

	      for (var _ii2 = 0; _ii2 < Math.min(this.multiple ? items.length : 1, items.length); _ii2++) {
	        user = items[_ii2];

	        if (existedUsers.includes(user['ID'])) {
	          continue;
	        }

	        options += '<option value="' + user['ID'] + '" selected>' + user["NAME"] + '</option>';
	        html += ['<div class="mobile-grid-field-select-user-item-outer">', '<div class="mobile-grid-field-select-user-item">', this.showDrop ? '<del id="' + this.select.id + '_del_' + user["ID"] + '"></del>' : '', '<div class="avatar"', user["IMAGE"] ? ' style="background-image:url(\'' + user["IMAGE"] + '\')"' : '', '></div>', '<span onclick="BXMobileApp.Events.postToComponent(\'onUserProfileOpen\', ' + [user['ID']] + ', \'communication\');">' + user["NAME"] + '</span>', '</div>', '</div>'].join('').replace(' style="background-image:url(\'\')"', '');
	      } // if (this.expand)
	      // {
	      // 	this.expand.value = c;
	      // }
	      // if (this.visCount)
	      // {
	      // 	this.visCount.innerHTML = c - 3;
	      // }


	      if (html !== '') {
	        this.select.innerHTML = (this.multiple ? this.select.innerHTML : '') + options;
	        this.container.innerHTML = (this.multiple ? this.container.innerHTML : '') + html;

	        if (this.select.innerHTML !== '' && !this.multiple) {
	          this.eventNode.innerHTML = BX.message('interface_form_change');
	        }

	        BX.onCustomEvent(this, "onChange", [this, this.select]);
	        var ij = 0,
	            f = BX.proxy(function () {
	          if (ij < 100) {
	            if (this.container.childNodes.length > 0) {
	              this.actualizeNodes();
	            } else if (ij++) {
	              setTimeout(f, 50);
	            }
	          }
	        }, this);
	        setTimeout(f, 50);
	      }
	    },
	    callback: function callback(data) {
	      if (data && data.a_users) {
	        this.buildNodes(data.a_users);
	      }
	    }
	  };
	  return nodeSelectUser;
	}();

	window.app.exec('enableCaptureKeyboard', true);

	BX.Mobile.Field.SelectUser = function (params) {
	  this.init(params);
	};

	BX.Mobile.Field.SelectUser.prototype = {
	  __proto__: BX.Mobile.Field.prototype,
	  bindElement: function bindElement(node) {
	    var result = null;

	    if (BX(node)) {
	      result = new nodeSelectUser(node, BX("".concat(node.id, "_select")));
	    }

	    return result;
	  }
	};

}((this.BX.Mobile.Field.SelectUser = this.BX.Mobile.Field.SelectUser || {}),BX));
//# sourceMappingURL=mobile.js.map
