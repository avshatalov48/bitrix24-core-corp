this.BX = this.BX || {};
this.BX.Mobile = this.BX.Mobile || {};
this.BX.Mobile.Crm = this.BX.Mobile.Crm || {};
(function (exports,main_core) {
	'use strict';

	var Titlebar = /*#__PURE__*/function () {
	  function Titlebar(settings) {
	    babelHelpers.classCallCheck(this, Titlebar);
	    this.title = settings.hasOwnProperty('title') ? settings.title : '';
	    this.subTitle = settings.hasOwnProperty('subTitle') ? settings.subTitle : '';
	    this.photo = settings.hasOwnProperty('photo') ? settings.photo : '';
	    this.init();
	  }

	  babelHelpers.createClass(Titlebar, [{
	    key: "init",
	    value: function init() {
	      BXMobileApp.UI.Page.TopBar.title.setText(this.title);

	      if (this.subTitle.length) {
	        BXMobileApp.UI.Page.TopBar.title.setDetailText(this.subTitle);
	      }

	      BXMobileApp.UI.Page.TopBar.title.setImage(this.photo);
	      BXMobileApp.UI.Page.TopBar.title.show();
	    }
	  }, {
	    key: "setMenu",
	    value: function setMenu(menuItems) {
	      app.menuCreate({
	        items: menuItems
	      });
	      window.BXMobileApp.UI.Page.TopBar.updateButtons({
	        menuButton: {
	          type: 'more',
	          style: 'custom',
	          callback: function callback() {
	            app.menuShow();
	          }
	        }
	      });
	    }
	  }, {
	    key: "removeMenu",
	    value: function removeMenu() {
	      window.BXMobileApp.UI.Page.TopBar.updateButtons({
	        menuButton: {}
	      });
	    }
	  }], [{
	    key: "create",
	    value: function create(settings) {
	      return new Titlebar(settings);
	    }
	  }]);
	  return Titlebar;
	}();

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"crm-phonetracker-detail-control-container\">\n\t\t\t<div class=\"crm-phonetracker-detail-control-icon crm-phonetracker-detail-control-icon-avatar\" ", "></div>\n\t\t\t<div class=\"crm-phonetracker-detail-control-inner\">\n\t\t\t\t<div class=\"crm-phonetracker-detail-control-title\">", "</div>\n\t\t\t\t<div class=\"crm-phonetracker-detail-control-field-container\">\n\t\t\t\t\t<input\n\t\t\t\t\t\tonchange=\"BX.onCustomEvent('onCrmCallTrackerNeedToSendForm", "')\"\n\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\tname=\"CONTACTS[", "][FULL_NAME]\"\n\t\t\t\t\t\tplaceholder=\"", "\"\n\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\tclass=\"crm-phonetracker-detail-control-field\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	var Contact = /*#__PURE__*/function () {
	  //defined in template
	  function Contact(container, formId, data) {
	    babelHelpers.classCallCheck(this, Contact);
	    this.container = container;
	    this.formId = formId;
	    this.init(data);
	  }

	  babelHelpers.createClass(Contact, [{
	    key: "init",
	    value: function init(data) {
	      if (data.id > 0) {
	        this.isNew = false;
	        this.id = parseInt(data.id);
	        Contact.ids.push(this.id);
	      } else {
	        this.isNew = true;
	        this.id = ['n', Contact.newIdsCounter++].join('');
	      }

	      this.name = main_core.Type.isString(data.name) ? data.name : '';
	      this.avatar = main_core.Type.isString(data.avatar) ? data.avatar : '';
	      this.draw();
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      var avatar = this.avatar !== '' ? "style=\"background-image: url('".concat(main_core.Text.encode(this.avatar), "')\"") : '';
	      var avatar2 = this.avatar !== '' ? "<i style=\"background-image:url('".concat(main_core.Text.encode(this.avatar), "')\"></i>") : '';
	      var onclick = this.onclick.bind(this);
	      return main_core.Tag.render(_templateObject(), avatar, main_core.Loc.getMessage('CRM_CONTACT'), this.formId, onclick, this.id, main_core.Loc.getMessage('CRM_CONTACT_PLACEHOLDER'), main_core.Text.encode(this.name));
	    }
	  }, {
	    key: "draw",
	    value: function draw() {
	      var newNode = this.getNode();
	      this.container.parentNode.replaceChild(newNode, this.container);
	      this.container = newNode;
	    }
	  }, {
	    key: "onclick",
	    value: function onclick() {
	      if (this.isNew) {
	        var eventName = ['onCrmContactSelectForDeal', this.id].join('_');
	        BX.Mobile.Crm.loadPageModal(main_core.Uri.addParam(Contact.selectorUrl, {
	          entity: 'contact',
	          event: eventName
	        }));

	        var funct = function (data) {
	          BX.removeCustomEvent(eventName, funct);

	          if (data && data.id) {
	            this.init(data);
	            BX.onCustomEvent("onCrmCallTrackerNeedToSendForm".concat(this.formId));
	          }
	        }.bind(this);

	        BXMobileApp.addCustomEvent(eventName, funct);
	      }
	    }
	  }], [{
	    key: "bind",
	    value: function bind(container, formId, contacts) {
	      contacts.forEach(function (_ref) {
	        var id = _ref.id,
	            name = _ref.name,
	            avatar = _ref.avatar;
	        new Contact(container, formId, {
	          id: id,
	          name: name,
	          avatar: avatar
	        });
	      });

	      if (contacts.length <= 0) {
	        new Contact(container, formId, {});
	      }
	    }
	  }]);
	  return Contact;
	}();

	babelHelpers.defineProperty(Contact, "selectorUrl", '');
	babelHelpers.defineProperty(Contact, "newIdsCounter", 0);
	babelHelpers.defineProperty(Contact, "ids", []);

	function _templateObject$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"crm-phonetracker-detail-control-container\">\n\t\t\t<div class=\"crm-phonetracker-detail-control-icon crm-phonetracker-detail-control-icon-avatar\" ", "></div>\n\t\t\t<div class=\"crm-phonetracker-detail-control-inner\">\n\t\t\t\t<div class=\"crm-phonetracker-detail-control-title\">", "</div>\n\t\t\t\t<div class=\"crm-phonetracker-detail-control-field-container\">\n\t\t\t\t\t<input type=\"hidden\" name=\"COMPANY[ID]\" value=\"", "\" >\n\t\t\t\t\t<input\n\t\t\t\t\t\tonchange=\"BX.onCustomEvent('onCrmCallTrackerNeedToSendForm", "')\"\n\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\tname=\"COMPANY[TITLE]\"\n\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\t", "\n\t\t\t\t\t\tplaceholder=\"", "\"\n\t\t\t\t\t\tclass=\"crm-phonetracker-detail-control-field\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>"]);

	  _templateObject$1 = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	var Company = /*#__PURE__*/function () {
	  // defined in template
	  function Company(container, formId, data) {
	    babelHelpers.classCallCheck(this, Company);
	    this.container = container;
	    this.formId = formId;
	    this.init(data);
	  }

	  babelHelpers.createClass(Company, [{
	    key: "init",
	    value: function init(data) {
	      if (data.id > 0) {
	        this.isNew = false;
	        this.id = parseInt(data.id);
	      } else {
	        this.isNew = true;
	        this.id = null;
	      }

	      this.title = main_core.Type.isString(data.title) ? data.title : '';
	      this.logo = main_core.Type.isString(data.logo) ? data.logo : '';
	      this.draw();
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      var logo = this.logo !== '' ? "style=\"background-image: url('".concat(main_core.Text.encode(this.logo), "')\"") : '';
	      var logo2 = this.logo !== '' ? "<i style=\"background-image:url('".concat(main_core.Text.encode(this.logo), "')\"></i>") : '';
	      var onclick = this.onclick.bind(this);
	      return main_core.Tag.render(_templateObject$1(), logo, main_core.Loc.getMessage('CRM_COMPANY'), this.id, this.formId, onclick, main_core.Text.encode(this.title), this.id <= 0 ? ' readonly ' : '', main_core.Loc.getMessage('CRM_COMPANY_PLACEHOLDER'));
	    }
	  }, {
	    key: "draw",
	    value: function draw() {
	      var newNode = this.getNode();
	      this.container.parentNode.replaceChild(newNode, this.container);
	      this.container = newNode;
	    }
	  }, {
	    key: "onclick",
	    value: function onclick() {
	      if (this.isNew) {
	        var eventName = ['onCrmCompanySelectForDeal', this.id].join('_');
	        BX.Mobile.Crm.loadPageModal(main_core.Uri.addParam(Company.selectorUrl, {
	          entity: 'company',
	          event: eventName
	        }));

	        var funct = function (data) {
	          BX.removeCustomEvent(eventName, funct);

	          if (data && data.id) {
	            this.init({
	              id: data.id,
	              title: data.name,
	              logo: data.image // multi: data.multy,

	            });
	            BX.onCustomEvent("onCrmCallTrackerNeedToSendForm".concat(this.formId));
	          }
	        }.bind(this);

	        BXMobileApp.addCustomEvent(eventName, funct);
	      }
	    }
	  }], [{
	    key: "bind",
	    value: function bind(container, formId, company) {
	      new Company(container, formId, company);
	    }
	  }]);
	  return Company;
	}();

	babelHelpers.defineProperty(Company, "selectorUrl", '');

	var Action = /*#__PURE__*/function () {
	  function Action() {
	    babelHelpers.classCallCheck(this, Action);
	  }

	  babelHelpers.createClass(Action, null, [{
	    key: "addToIgnored",
	    value: function addToIgnored(id) {
	      BXMobileApp.Events.postToComponent('onCrmCallTrackerAddToIgnoredRequest', {
	        ID: id
	      });
	      BXMobileApp.UI.Page.close();
	    }
	  }, {
	    key: "postpone",
	    value: function postpone(id) {
	      BXMobileApp.Events.postToComponent('onCrmCallTrackerPostponeRequest', {
	        ID: id
	      });
	      BXMobileApp.UI.Page.close();
	    }
	  }]);
	  return Action;
	}();

	exports.Titlebar = Titlebar;
	exports.Contact = Contact;
	exports.Company = Company;
	exports.Action = Action;

}((this.BX.Mobile.Crm.Calltracker = this.BX.Mobile.Crm.Calltracker || {}),BX));
//# sourceMappingURL=script.js.map
