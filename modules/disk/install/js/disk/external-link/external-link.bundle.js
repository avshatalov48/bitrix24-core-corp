this.BX = this.BX || {};
(function (exports,ui_designTokens,ui_fonts_opensans,clipboard,ui_switcher,ui_layoutForm,main_core,main_core_events,main_date,ui_buttons,main_popup) {
	'use strict';

	var Backend = /*#__PURE__*/function () {
	  function Backend() {
	    babelHelpers.classCallCheck(this, Backend);
	  }
	  babelHelpers.createClass(Backend, null, [{
	    key: "disableExternalLink",
	    value: function disableExternalLink(objectId) {
	      return main_core.ajax.runAction('disk.api.commonActions.disableExternalLink', {
	        data: {
	          objectId: objectId
	        }
	      });
	    }
	  }, {
	    key: "generateExternalLink",
	    value: function generateExternalLink(objectId) {
	      return main_core.ajax.runAction('disk.api.commonActions.generateExternalLink', {
	        data: {
	          objectId: objectId
	        }
	      });
	    }
	  }, {
	    key: "getExternalLink",
	    value: function getExternalLink(objectId) {
	      return main_core.ajax.runAction('disk.api.commonActions.getExternalLink', {
	        data: {
	          objectId: objectId
	        }
	      });
	    }
	  }, {
	    key: "setDeathTime",
	    value: function setDeathTime(externalLinkId, deathTimeTimestamp) {
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('disk.api.externalLink.setDeathTime', {
	          data: {
	            externalLinkId: externalLinkId,
	            deathTime: deathTimeTimestamp
	          }
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "revokeDeathTime",
	    value: function revokeDeathTime(externalLinkId) {
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('disk.api.externalLink.revokeDeathTime', {
	          data: {
	            externalLinkId: externalLinkId
	          }
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "setPassword",
	    value: function setPassword(externalLinkId, newPassword) {
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('disk.api.externalLink.setPassword', {
	          data: {
	            externalLinkId: externalLinkId,
	            newPassword: newPassword
	          }
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "revokePassword",
	    value: function revokePassword(externalLinkId) {
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('disk.api.externalLink.revokePassword', {
	          data: {
	            externalLinkId: externalLinkId
	          }
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "allowEditDocument",
	    value: function allowEditDocument(externalLinkId) {
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('disk.api.externalLink.allowEditDocument', {
	          data: {
	            externalLinkId: externalLinkId
	          }
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "disallowEditDocument",
	    value: function disallowEditDocument(externalLinkId) {
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('disk.api.externalLink.disallowEditDocument', {
	          data: {
	            externalLinkId: externalLinkId
	          }
	        }).then(resolve, reject);
	      });
	    }
	  }]);
	  return Backend;
	}();
	var BackendForTrackedObject = /*#__PURE__*/function (_Backend) {
	  babelHelpers.inherits(BackendForTrackedObject, _Backend);
	  function BackendForTrackedObject() {
	    babelHelpers.classCallCheck(this, BackendForTrackedObject);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(BackendForTrackedObject).apply(this, arguments));
	  }
	  babelHelpers.createClass(BackendForTrackedObject, null, [{
	    key: "disableExternalLink",
	    value: function disableExternalLink(objectId) {
	      BX.ajax.runAction('disk.api.trackedObject.disableExternalLink', {
	        data: {
	          objectId: objectId
	        }
	      });
	    }
	  }, {
	    key: "generateExternalLink",
	    value: function generateExternalLink(objectId) {
	      return main_core.ajax.runAction('disk.api.trackedObject.generateExternalLink', {
	        data: {
	          objectId: objectId
	        }
	      });
	    }
	  }, {
	    key: "getExternalLink",
	    value: function getExternalLink(objectId) {
	      return main_core.ajax.runAction('disk.api.trackedObject.getExternalLink', {
	        data: {
	          objectId: objectId
	        }
	      });
	    }
	  }]);
	  return BackendForTrackedObject;
	}(Backend);

	var _templateObject, _templateObject2, _templateObject3;
	var Input = /*#__PURE__*/function () {
	  function Input(objectId, data) {
	    babelHelpers.classCallCheck(this, Input);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    babelHelpers.defineProperty(this, "data", {});
	    this.bindEvents();
	    if (main_core.Type.isPlainObject(objectId)) {
	      this.objectId = parseInt(objectId.objectId, 10);
	      this.setData(objectId, false);
	    } else {
	      this.objectId = parseInt(objectId, 10);
	      this.setData(data, false);
	    }
	  }
	  babelHelpers.createClass(Input, [{
	    key: "setData",
	    value: function setData(data) {
	      var fireEvent = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
	      if (data && main_core.Type.isPlainObject(data)) {
	        this.data = Object.assign(this.data, data);
	        this.data.id = this.data.id === null ? this.data.id : parseInt(this.data.id, 10);
	      } else {
	        this.data = {
	          id: null,
	          link: null,
	          hash: null,
	          hasPassword: null,
	          hasDeathTime: null,
	          availableEdit: null,
	          canEditDocument: null,
	          deathTime: null,
	          deathTimeTimestamp: null
	        };
	      }
	      this.adjustData();
	      main_core_events.EventEmitter.emit(this, 'Disk:ExternalLink:DataSet', data);
	      if (fireEvent !== false) {
	        main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'Disk:ExternalLink:HasChanged', {
	          objectId: this.objectId,
	          data: this.data,
	          target: this
	        });
	      }
	    }
	  }, {
	    key: "adjustData",
	    value: function adjustData() {
	      if (this.data.id === null) {
	        this.getSwitcher().check(false, false);
	        this.showUnchecked();
	      } else {
	        this.getSwitcher().check(true, false);
	        this.showChecked();
	        this.getLinkContainer().innerHTML = main_core.Text.encode(this.data.link);
	        this.getLinkContainer().href = main_core.Text.encode(this.data.link);
	        this.getPasswordContainer().innerHTML = this.data.hasPassword ? main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_WITH_PASSWORD') : main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_WITHOUT_PASSWORD');
	        this.getDeathTimeContainer().innerHTML = this.data.hasDeathTime ? main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_BEFORE').replace('#deathTime#', BX.Main.Date.format(BX.Main.Date.convertBitrixFormat(main_core.Loc.getMessage('FORMAT_DATETIME').replace(':SS', '')), new Date(this.data.deathTimeTimestamp * 1000))) : main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_FOREVER');
	        if (this.data.availableEdit === true) {
	          this.getRightsContainer().innerHTML = ", ".concat(this.data.canEditDocument ? main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_RIGHTS_CAN_EDIT') : main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_RIGHTS_CAN_READ'));
	          main_core.Dom.style(this.getRightsContainer(), 'display', '');
	        } else {
	          main_core.Dom.style(this.getRightsContainer(), 'display', 'none');
	        }
	      }
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;
	      main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'Disk:ExternalLink:HasChanged', function (_ref) {
	        var _ref$data = _ref.data,
	          objectId = _ref$data.objectId,
	          data = _ref$data.data,
	          target = _ref$data.target;
	        if (objectId !== _this.objectId || Object.is(target, _this)) {
	          return;
	        }
	        _this.setData(data, false);
	      });
	    }
	  }, {
	    key: "getBackend",
	    value: function getBackend() {
	      return Backend;
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var _this2 = this;
	      return this.cache.remember('main', function () {
	        var copyButton = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"disk-control-external-link-link-icon\"></div>"])));
	        BX.clipboard.bindCopyClick(copyButton, {
	          text: function text() {
	            return _this2.data.link;
	          }
	        });
	        var tune = function tune() {
	          return _this2.constructor.showPopup(_this2.objectId, _this2.data);
	        };
	        return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"disk-control-external-link-block", "\">\n\t\t\t\t\t<div class=\"disk-control-external-link\">\n\t\t\t\t\t\t<div class=\"disk-control-external-link-btn\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"disk-control-external-link-main\">\n\t\t\t\t\t\t\t<div class=\"disk-control-external-link-link-box\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"disk-control-external-link-subtitle\" onclick=\"", "\">", "<span>, </span>", "", "</div>\n\t\t\t\t\t\t\t<div class=\"disk-control-external-link-text\">", "</div>\n\t\t\t\t\t\t\t<div class=\"disk-control-external-link-skeleton\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), _this2.data.id === null ? '' : ' disk-control-external-link-block--active', _this2.getSwitcher().getNode(), _this2.getLinkContainer(), copyButton, tune, _this2.getDeathTimeContainer(), _this2.getPasswordContainer(), _this2.getRightsContainer(), main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_IS_NOT_PUBLISHED'));
	      });
	    }
	  }, {
	    key: "getSwitcher",
	    value: function getSwitcher() {
	      var _this3 = this;
	      return this.cache.remember('switcher', function () {
	        var switcherNode = document.createElement('span');
	        switcherNode.className = 'ui-switcher';
	        var switcher = new BX.UI.Switcher({
	          node: switcherNode,
	          checked: _this3.data.id !== null,
	          inputName: 'ACTIVE',
	          color: 'green'
	        });
	        switcher.handlers = {
	          toggled: _this3.toggle.bind(_this3, {
	            target: switcher
	          })
	        };
	        return switcher;
	      });
	    }
	  }, {
	    key: "getLinkContainer",
	    value: function getLinkContainer() {
	      var _this4 = this;
	      return this.cache.remember('link', function () {
	        return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<a href=\"", "\" class=\"disk-control-external-link-link\" target=\"_blank\">", "</a>"])), main_core.Text.encode(_this4.data.link), main_core.Text.encode(_this4.data.link));
	      });
	    }
	  }, {
	    key: "getRightsContainer",
	    value: function getRightsContainer() {
	      return this.cache.remember('rights', function () {
	        return document.createElement('span');
	      });
	    }
	  }, {
	    key: "getDeathTimeContainer",
	    value: function getDeathTimeContainer() {
	      return this.cache.remember('deathTime', function () {
	        return document.createElement('span');
	      });
	    }
	  }, {
	    key: "getPasswordContainer",
	    value: function getPasswordContainer() {
	      return this.cache.remember('password', function () {
	        return document.createElement('span');
	      });
	    }
	  }, {
	    key: "toggle",
	    value: function toggle(_ref2) {
	      var _this5 = this;
	      var target = _ref2.target;
	      if (target.isChecked()) {
	        this.showLoader();
	        void this.getBackend().generateExternalLink(this.objectId).then(function (_ref3) {
	          var externalLink = _ref3.data.externalLink;
	          _this5.setData(externalLink);
	          _this5.hideLoader();
	        });
	      } else {
	        this.getBackend().disableExternalLink(this.objectId);
	        this.setData(null);
	      }
	    }
	  }, {
	    key: "showChecked",
	    value: function showChecked() {
	      var baseClassName = this.getContainer().classList.item(0);
	      var activeClassName = [baseClassName, '--active'].join('');
	      main_core.Dom.addClass(this.getContainer(), activeClassName);
	    }
	  }, {
	    key: "showUnchecked",
	    value: function showUnchecked() {
	      var baseClassName = this.getContainer().classList.item(0);
	      var activeClassName = [baseClassName, '--active'].join('');
	      main_core.Dom.removeClass(this.getContainer(), activeClassName);
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader() {
	      main_core.Dom.addClass(this.getContainer(), 'disk-control-external-link-skeleton--active');
	    }
	  }, {
	    key: "hideLoader",
	    value: function hideLoader() {
	      main_core.Dom.removeClass(this.getContainer(), 'disk-control-external-link-skeleton--active');
	    }
	  }, {
	    key: "reload",
	    value: function reload() {
	      var _this6 = this;
	      this.showLoader();
	      return this.getBackend().getExternalLink(this.objectId).then(function (_ref4) {
	        var data = _ref4.data;
	        _this6.setData(data && data.externalLink ? data.externalLink : null);
	        _this6.hideLoader();
	      });
	    }
	  }]);
	  return Input;
	}();

	var _templateObject$1, _templateObject2$1, _templateObject3$1, _templateObject4, _templateObject5;
	var InputExtended = /*#__PURE__*/function (_Input) {
	  babelHelpers.inherits(InputExtended, _Input);
	  function InputExtended(objectId, data) {
	    babelHelpers.classCallCheck(this, InputExtended);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(InputExtended).call(this, objectId, data));
	  }
	  babelHelpers.createClass(InputExtended, [{
	    key: "adjustData",
	    value: function adjustData() {
	      if (this.data.id === null) {
	        this.getSwitcher().check(false, false);
	        this.showUnchecked();
	        if (this.cache.get('popup')) {
	          this.cache.get('popup').getPopupContainer().setAttribute('externalLinkIsSet', 'N');
	        }
	      } else {
	        this.getSwitcher().check(true, false);
	        this.showChecked();
	        this.getLinkContainer().innerHTML = main_core.Text.encode(this.data.link);
	        this.getLinkContainer().href = main_core.Text.encode(this.data.link);
	        this.getPasswordContainer().innerHTML = this.data.hasPassword ? main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_WITH_PASSWORD') : main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_WITHOUT_PASSWORD');
	        this.getDeathTimeContainer().innerHTML = this.data.hasDeathTime ? main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_BEFORE').replace('#deathTime#', BX.Main.Date.format(BX.Main.Date.convertBitrixFormat(main_core.Loc.getMessage('FORMAT_DATETIME').replace(':SS', '')), new Date(this.data.deathTimeTimestamp * 1000))) : main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_FOREVER');
	        if (this.data.availableEdit === true) {
	          this.getRightsContainer().innerHTML = ', ' + (this.data.canEditDocument ? main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_RIGHTS_CAN_EDIT') : main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_RIGHTS_CAN_READ'));
	          this.getRightsContainer().style.display = '';
	        } else {
	          this.getRightsContainer().style.display = 'none';
	        }
	        if (this.cache.get('popup')) {
	          this.cache.get('popup').getPopupContainer().setAttribute('externalLinkIsSet', 'Y');
	        }
	      }
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var _this = this;
	      return this.cache.remember('main', function () {
	        var copyButton = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"disk-control-external-link-link-icon\"></div>"])));
	        BX.clipboard.bindCopyClick(copyButton, {
	          text: function text() {
	            return _this.data.link;
	          }
	        });
	        _this.showSettings = _this.showSettings.bind(_this);
	        return main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"disk-control-external-link-block", " disk-control-external-link-block--tunable\">\n\t\t\t\t\t<div class=\"disk-control-external-link\">\n\t\t\t\t\t\t<div class=\"disk-control-external-link-btn\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"disk-control-external-link-main\">\n\t\t\t\t\t\t\t<div class=\"disk-control-external-link-link-box\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"disk-control-external-link-subtitle\" onclick=\"", "\">", "<span>, </span>", "", "</div>\n\t\t\t\t\t\t\t<div class=\"disk-control-external-link-text\">", "</div>\n\t\t\t\t\t\t\t<div class=\"disk-control-external-link-skeleton\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"disk-public-link-config\" onclick=\"", "\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"disk-control-external-link-settings\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), _this.data.id !== null ? ' disk-control-external-link-block--active' : '', _this.getSwitcher().getNode(), _this.getLinkContainer(), copyButton, _this.showSettings, _this.getDeathTimeContainer(), _this.getPasswordContainer(), _this.getRightsContainer(), main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_IS_NOT_PUBLISHED'), _this.showSettings, _this.getDeathTimeSettingsContainer(), _this.getPasswordSettingsContainer(), _this.getEditSettingsContainer());
	      });
	    }
	  }, {
	    key: "showSettings",
	    value: function showSettings() {
	      this.cache.set('settingsAreShown', 'Y');
	      if (this.cache.get('popup')) {
	        this.cache.get('popup').getPopupContainer().setAttribute('settingsAreShown', 'Y');
	      }
	    }
	  }, {
	    key: "hideSettings",
	    value: function hideSettings() {
	      this.cache.set('settingsAreShown', 'N');
	      if (this.cache.get('popup')) {
	        this.cache.get('popup').getPopupContainer().setAttribute('settingsAreShown', 'N');
	      }
	    }
	  }, {
	    key: "getDeathTimeSettingsContainer",
	    value: function getDeathTimeSettingsContainer() {
	      var _this2 = this;
	      return this.cache.remember('deathTimeSettings', function () {
	        var deathTimeSettings = main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form-line\">\n\t\t\t\t<input type=\"checkbox\" name=\"hasDeathTime\">\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<label class=\"ui-ctl ui-ctl-checkbox\">\n\t\t\t\t\t\t<input type=\"checkbox\" class=\"ui-ctl-element\" name=\"enableDeathTime\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t</label>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row-inline\" name=\"deathTimeIsNotSaved\">\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w25 ui-ctl-inline\">\n\t\t\t\t\t\t\t<input type=\"number\" min=\"1\" name=\"deathTimeValue\" class=\"ui-ctl-element\" value=\"10\" size=\"4\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-inline ui-ctl-w50\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t<select class=\"ui-ctl-element\" name=\"deathTimeMeasure\">\n\t\t\t\t\t\t\t\t<option value=\"60\" selected>", "</option>\n\t\t\t\t\t\t\t\t<option value=\"3600\">", "</option>\n\t\t\t\t\t\t\t\t<option value=\"86400\">", "</option>\n\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row-inline\" name=\"deathTimeIsSaved\">\n\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-no-border\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-element\" name=\"deathTimeParsed\">14.10.2014 16:33</div>\n\t\t\t\t\t\t\t<button name=\"deathTimeButtonUnset\" class=\"ui-ctl-after ui-ctl-icon-clear\"></button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>"])), main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_DEATHTIME_LIMIT_CHECKBOX'), main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_MINUTES'), main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_HOURS'), main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_DAYS'), main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_DEATHTIME_LIMIT_PREPOSITION'));

	        /*region bind settings form */
	        var onDeathTimeHasChanged = function onDeathTimeHasChanged() {
	          if (!(_this2.data['id'] > 0)) {
	            return;
	          }
	          if (deathTimeSettings.querySelector('input[name=enableDeathTime]').checked === true) {
	            deathTimeSettings.querySelector('input[name=deathTimeValue]').disabled = false;
	            deathTimeSettings.querySelector('[name=deathTimeMeasure]').disabled = false;
	          } else {
	            deathTimeSettings.querySelector('input[name=deathTimeValue]').disabled = true;
	            deathTimeSettings.querySelector('input[name=deathTimeValue]').value = '10';
	            deathTimeSettings.querySelector('[name=deathTimeMeasure]').disabled = true;
	            deathTimeSettings.querySelector('[name=deathTimeMeasure]').value = '60';
	          }
	        };
	        deathTimeSettings.querySelector('input[name=enableDeathTime]').addEventListener('click', function () {
	          onDeathTimeHasChanged();
	          deathTimeSettings.querySelector('input[name=enableDeathTime]').dataset.changed = 'Y';
	          main_core_events.EventEmitter.emit(_this2, 'Disk:ExternalLink:Settings:Change', {
	            field: 'deathTime'
	          });
	        });
	        deathTimeSettings.querySelector('button[name=deathTimeButtonUnset]').addEventListener('click', function () {
	          deathTimeSettings.querySelector('input[name=hasDeathTime]').checked = false;
	          deathTimeSettings.querySelector('input[name=enableDeathTime]').dataset.changed = 'Y';
	          main_core_events.EventEmitter.emit(_this2, 'Disk:ExternalLink:Settings:Change', {
	            field: 'deathTime'
	          });
	        });
	        var adjustSettings = function adjustSettings() {
	          if (!(_this2.data['id'] > 0)) {
	            return;
	          }
	          deathTimeSettings.querySelector('input[name=enableDeathTime]').dataset.changed = 'N';
	          if (_this2.data['hasDeathTime']) {
	            deathTimeSettings.querySelector('input[name=hasDeathTime]').checked = true;
	            deathTimeSettings.querySelector('div[name=deathTimeParsed]').innerHTML = BX.Main.Date.format(BX.Main.Date.convertBitrixFormat(main_core.Loc.getMessage('FORMAT_DATETIME').replace(':SS', '')), new Date(_this2.data.deathTimeTimestamp * 1000));
	            deathTimeSettings.querySelector('input[name=enableDeathTime]').checked = true;
	          } else {
	            deathTimeSettings.querySelector('input[name=hasDeathTime]').checked = false;
	            deathTimeSettings.querySelector('input[name=enableDeathTime]').checked = false;
	          }
	          onDeathTimeHasChanged();
	        };
	        main_core_events.EventEmitter.subscribe(_this2, 'Disk:ExternalLink:DataSet', adjustSettings);
	        adjustSettings();
	        /*endregion*/

	        return deathTimeSettings;
	      });
	    }
	  }, {
	    key: "getPasswordSettingsContainer",
	    value: function getPasswordSettingsContainer() {
	      var _this3 = this;
	      return this.cache.remember('passwordSettings', function () {
	        var passwordSettings = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form-line\">\n\t\t\t\t<input type=\"checkbox\" name=\"hasPassword\">\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<label class=\"ui-ctl ui-ctl-checkbox\">\n\t\t\t\t\t\t<input type=\"checkbox\" class=\"ui-ctl-element\" name=\"enablePassword\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t</label>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row-inline\" name=\"passwordIsNotSaved\">\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-after-icon\">\n\t\t\t\t\t\t\t<input type=\"password\" name=\"passwordValue\" class=\"ui-ctl-element\" placeholder=\"", "\" autocomplete=\"nope\">\n\t\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-angle disk-external-link-setting-popup-password-show\" name=\"passwordTypeSwitcher\"></button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row-inline\" name=\"passwordIsSaved\">\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-disabled ui-ctl-after-icon\">\n\t\t\t\t\t\t\t<input type=\"password\" class=\"ui-ctl-element\" readonly value=\"some password\">\n\t\t\t\t\t\t\t<button name=\"passwordButtonUnset\" class=\"ui-ctl-after ui-ctl-icon-clear\"></button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_PASSWORD_CHECKBOX'), main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_PASSWORD_PLACEHOLDER'));

	        /*region bind settings form */
	        var passwordValue = passwordSettings.querySelector('input[name=passwordValue]');
	        var onPasswordHasChanged = function onPasswordHasChanged() {
	          if (!(_this3.data['id'] > 0)) {
	            return;
	          }
	          if (passwordSettings.querySelector('input[name=enablePassword]').checked === true) {
	            passwordValue.disabled = false;
	          } else {
	            passwordValue.disabled = true;
	            passwordValue.value = '';
	            passwordValue.type = 'password';
	          }
	        };
	        passwordSettings.querySelector('input[name=enablePassword]').addEventListener('click', function () {
	          onPasswordHasChanged();
	          passwordSettings.querySelector('input[name=enablePassword]').dataset.changed = 'Y';
	          main_core_events.EventEmitter.emit(_this3, 'Disk:ExternalLink:Settings:Change', {
	            field: 'password'
	          });
	        });
	        passwordSettings.querySelector('button[name=passwordButtonUnset]').addEventListener('click', function () {
	          passwordSettings.querySelector('input[name=hasPassword]').checked = false;
	          passwordSettings.querySelector('input[name=enablePassword]').dataset.changed = 'Y';
	          passwordValue.value = '';
	          passwordValue.type = 'password';
	          main_core_events.EventEmitter.emit(_this3, 'Disk:ExternalLink:Settings:Change', {
	            field: 'password'
	          });
	        });
	        passwordSettings.querySelector('button[name=passwordTypeSwitcher]').addEventListener('click', function () {
	          passwordValue.type = passwordValue.type === 'text' ? 'password' : 'text';
	        });
	        var adjustSettings = function adjustSettings() {
	          if (!(_this3.data['id'] > 0)) {
	            return;
	          }
	          passwordSettings.querySelector('input[name=enablePassword]').dataset.changed = 'N';
	          passwordSettings.querySelector('input[name=hasPassword]').checked = _this3.data['hasPassword'] === true;
	          passwordSettings.querySelector('input[name=enablePassword]').checked = _this3.data['hasPassword'] === true;
	          onPasswordHasChanged();
	        };
	        main_core_events.EventEmitter.subscribe(_this3, 'Disk:ExternalLink:DataSet', adjustSettings);
	        adjustSettings();
	        /*endregion*/

	        return passwordSettings;
	      });
	    }
	  }, {
	    key: "getEditSettingsContainer",
	    value: function getEditSettingsContainer() {
	      var _this4 = this;
	      return this.cache.remember('editSettings', function () {
	        var editSettings = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form-line\">\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<label class=\"ui-ctl ui-ctl-checkbox\">\n\t\t\t\t\t\t<input type=\"checkbox\" class=\"ui-ctl-element\" name=\"canEditDocument\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t</label>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_ALLOW_EDITING'));
	        /*region bind settings form */
	        var canEditDocument = editSettings.querySelector('input[name=canEditDocument]');
	        canEditDocument.addEventListener('click', function () {
	          canEditDocument.dataset.changed = 'Y';
	          main_core_events.EventEmitter.emit(_this4, 'Disk:ExternalLink:Settings:Change', {
	            field: 'canEditDocument'
	          });
	        });
	        var adjustSettings = function adjustSettings() {
	          canEditDocument.checked = _this4.data['canEditDocument'] === true;
	          canEditDocument.dataset.changed = 'N';
	          if (_this4.data['availableEdit'] !== true) {
	            editSettings.style.display = 'none';
	            canEditDocument.disable = true;
	          } else {
	            editSettings.style.display = '';
	            delete editSettings.style.display;
	            delete canEditDocument.disable;
	          }
	        };
	        main_core_events.EventEmitter.subscribe(_this4, 'Disk:ExternalLink:DataSet', adjustSettings);
	        adjustSettings();
	        /*endregion*/

	        return editSettings;
	      });
	    }
	  }, {
	    key: "saveSettings",
	    value: function saveSettings() {
	      var _this5 = this;
	      if (!(this.data.id > 0)) {
	        return;
	      }
	      var settings = this.getContainer();
	      /*region DeathTime */
	      if (settings.querySelector('input[name=enableDeathTime]').dataset.changed === 'Y') {
	        var deathTimer = parseInt(settings.querySelector('input[name=deathTimeValue]').value) * parseInt(settings.querySelector('[name=deathTimeMeasure]').value);
	        var enableDeathTime = settings.querySelector('input[name=enableDeathTime]').checked === true && deathTimer > 0;
	        if (enableDeathTime === true) {
	          main_core_events.EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Save', function () {});
	          var deathTimeTimestamp = Math.floor(Date.now() / 1000) + deathTimer;
	          this.getBackend().setDeathTime(this.data.id, deathTimeTimestamp).then(function (_ref) {
	            var _ref$data$externalLin = _ref.data.externalLink,
	              hasDeathTime = _ref$data$externalLin.hasDeathTime,
	              deathTimeTimestamp = _ref$data$externalLin.deathTimeTimestamp,
	              deathTime = _ref$data$externalLin.deathTime;
	            _this5.setData({
	              hasDeathTime: hasDeathTime,
	              deathTimeTimestamp: deathTimeTimestamp,
	              deathTime: deathTime
	            });
	          })["finally"](function () {
	            main_core_events.EventEmitter.emit(_this5, 'Disk:ExternalLink:Settings:Saved', function () {});
	          });
	        } else if (enableDeathTime !== this.data.hasDeathTime) {
	          main_core_events.EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Save', function () {});
	          this.getBackend().revokeDeathTime(this.data['id']).then(function () {
	            _this5.setData({
	              hasDeathTime: false,
	              deathTimeTimestamp: null,
	              deathTime: null
	            });
	          })["finally"](function () {
	            main_core_events.EventEmitter.emit(_this5, 'Disk:ExternalLink:Settings:Saved', function () {});
	          });
	        }
	      }
	      /*endregion*/
	      /*region Password*/
	      if (settings.querySelector('input[name=enablePassword]').dataset.changed === 'Y') {
	        var passwordValue = settings.querySelector('input[name=passwordValue]').value.trim();
	        var enablePassword = settings.querySelector('input[name=enablePassword]').checked === true && passwordValue.length > 0;
	        if (enablePassword === true) {
	          main_core_events.EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Save', function () {});
	          this.getBackend().setPassword(this.data['id'], passwordValue).then(function () {
	            _this5.setData({
	              hasPassword: true
	            });
	          })["finally"](function () {
	            main_core_events.EventEmitter.emit(_this5, 'Disk:ExternalLink:Settings:Saved', function () {});
	          });
	        } else if (enablePassword !== this.data.hasPassword) {
	          main_core_events.EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Save', function () {});
	          this.getBackend().revokePassword(this.data['id']).then(function () {
	            _this5.setData({
	              hasPassword: false
	            });
	          })["finally"](function () {
	            main_core_events.EventEmitter.emit(_this5, 'Disk:ExternalLink:Settings:Saved', function () {});
	          });
	        }
	      }
	      /*endregion*/
	      /*region editing rights */
	      var canEditDocumentNode = settings.querySelector('input[name=canEditDocument]');
	      if (canEditDocumentNode && canEditDocumentNode.dataset.changed === 'Y' && canEditDocumentNode.checked !== this.data.canEditDocument) {
	        if (canEditDocumentNode.checked) {
	          main_core_events.EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Save', function () {});
	          this.getBackend().allowEditDocument(this.data['id']).then(function () {
	            _this5.setData({
	              canEditDocument: true
	            });
	          })["finally"](function () {
	            main_core_events.EventEmitter.emit(_this5, 'Disk:ExternalLink:Settings:Saved', function () {});
	          });
	        } else {
	          main_core_events.EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Save', function () {});
	          this.getBackend().disallowEditDocument(this.data['id']).then(function () {
	            _this5.setData({
	              canEditDocument: false
	            });
	          })["finally"](function () {
	            main_core_events.EventEmitter.emit(_this5, 'Disk:ExternalLink:Settings:Saved', function () {});
	          });
	        }
	      }
	      /*endregion*/
	    }
	  }, {
	    key: "getPopup",
	    value: function getPopup() {
	      var _this6 = this;
	      return this.cache.remember('popup', function () {
	        var popupSave = new ui_buttons.SaveButton({
	          state: ui_buttons.ButtonState.DISABLED,
	          onclick: function onclick() {
	            _this6.saveSettings();
	          }
	        });
	        popupSave.saveCounter = 0;
	        main_core_events.EventEmitter.subscribe(_this6, 'Disk:ExternalLink:Settings:Save', function () {
	          _this6.cache.get('popup').getPopupContainer().setAttribute('externalLinkIsWaiting', 'Y');
	          popupSave.saveCounter++;
	          popupSave.setWaiting();
	        });
	        main_core_events.EventEmitter.subscribe(_this6, 'Disk:ExternalLink:Settings:Saved', function () {
	          popupSave.saveCounter--;
	          if (popupSave.saveCounter <= 0) {
	            _this6.cache.get('popup').getPopupContainer().setAttribute('externalLinkIsWaiting', 'N');
	            popupSave.setDisabled(true);
	          }
	        });
	        main_core_events.EventEmitter.subscribe(_this6, 'Disk:ExternalLink:Settings:Change', function () {
	          popupSave.setDisabled(false);
	        });
	        main_core_events.EventEmitter.subscribe(_this6, 'Disk:ExternalLink:DataSet', function () {
	          popupSave.setDisabled(true);
	        });
	        var popup = new main_popup.Popup({
	          uniquePopupId: 'disk-external-link',
	          className: 'disk-external-link-popup',
	          titleBar: main_core.Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_TITLE'),
	          content: _this6.getContainer(),
	          autoHide: true,
	          closeIcon: true,
	          closeByEsc: true,
	          overlay: true,
	          cacheable: false,
	          minWidth: 410,
	          events: {
	            onClose: function onClose() {
	              _this6.cache["delete"]('popup');
	            }
	          },
	          buttons: [popupSave, new ui_buttons.CloseButton({
	            events: {
	              click: function click() {
	                popup.close();
	              }
	            }
	          })]
	        });
	        popup.getPopupContainer().setAttribute('externalLinkIsSet', _this6.data.id > 0 ? 'Y' : 'N');
	        popup.getPopupContainer().setAttribute('settingsAreShown', _this6.cache.get('settingsAreShown') === 'Y' ? 'Y' : 'N');
	        return popup;
	      });
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      this.getPopup().show();
	    }
	  }]);
	  return InputExtended;
	}(Input);

	var InputSimple = /*#__PURE__*/function (_Input) {
	  babelHelpers.inherits(InputSimple, _Input);
	  function InputSimple(objectId, data) {
	    babelHelpers.classCallCheck(this, InputSimple);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(InputSimple).call(this, objectId, data));
	  }
	  babelHelpers.createClass(InputSimple, null, [{
	    key: "getExtendedInputClass",
	    value: function getExtendedInputClass() {
	      return InputExtended;
	    }
	  }, {
	    key: "showPopup",
	    value: function showPopup(objectId) {
	      var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      var className = this.getExtendedInputClass();
	      var res = new className(objectId, data);
	      if (data === null) {
	        res.reload();
	      } else
	        // This behaviour is appropriate for current task
	        {
	          res.showSettings();
	        }
	      res.show();
	    }
	  }]);
	  return InputSimple;
	}(Input);

	var InputExtendedForTrackedObject = /*#__PURE__*/function (_InputExtended) {
	  babelHelpers.inherits(InputExtendedForTrackedObject, _InputExtended);
	  function InputExtendedForTrackedObject() {
	    babelHelpers.classCallCheck(this, InputExtendedForTrackedObject);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(InputExtendedForTrackedObject).apply(this, arguments));
	  }
	  babelHelpers.createClass(InputExtendedForTrackedObject, [{
	    key: "getBackend",
	    value: function getBackend() {
	      return BackendForTrackedObject;
	    }
	  }]);
	  return InputExtendedForTrackedObject;
	}(InputExtended);

	var InputSimpleForTrackedObject = /*#__PURE__*/function (_InputSimple) {
	  babelHelpers.inherits(InputSimpleForTrackedObject, _InputSimple);
	  function InputSimpleForTrackedObject(objectId, data) {
	    babelHelpers.classCallCheck(this, InputSimpleForTrackedObject);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(InputSimpleForTrackedObject).call(this, objectId, data));
	  }
	  babelHelpers.createClass(InputSimpleForTrackedObject, [{
	    key: "getBackend",
	    value: function getBackend() {
	      return BackendForTrackedObject;
	    }
	  }], [{
	    key: "getExtendedInputClass",
	    value: function getExtendedInputClass() {
	      return InputExtendedForTrackedObject;
	    }
	  }]);
	  return InputSimpleForTrackedObject;
	}(InputSimple);

	exports.ExternalLink = InputSimple;
	exports.ExternalLinkForTrackedObject = InputSimpleForTrackedObject;

}((this.BX.Disk = this.BX.Disk || {}),BX,BX,BX,BX.UI,BX.UI,BX,BX.Event,BX.Main,BX.UI,BX.Main));
//# sourceMappingURL=external-link.bundle.js.map
