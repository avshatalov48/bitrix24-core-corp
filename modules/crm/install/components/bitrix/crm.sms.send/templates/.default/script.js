(function (exports,main_core) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Crm.Component');

	/**
	 * @memberof BX.Crm.Component.CrmSmsSend
	 */
	var CrmSmsSend = /*#__PURE__*/function () {
	  function CrmSmsSend(container) {
	    babelHelpers.classCallCheck(this, CrmSmsSend);
	    this._senderId = null;
	    this._from = null;
	    this._commEntityTypeId = null;
	    this._commEntityId = null;
	    this._to = null;
	    this._fromList = [];
	    this._toList = [];
	    this._input = null;
	    this._menu = null;
	    this._isMenuShown = false;
	    this._shownMenuId = null;
	    this._canUse = null;
	    this._canSendMessage = null;
	    this._manageUrl = null;
	    this._senders = null;
	    this._defaults = null;
	    this._communications = null;
	    this._container = container;
	    this._isRequestRunning = false;
	    this._isSenderFixed = false;
	    this.saveButton = null;
	    this.cancelButton = null;
	    this._serviceUrl = '';
	    this._ownerTypeId = '';
	    this._ownerId = '';
	  }

	  babelHelpers.createClass(CrmSmsSend, [{
	    key: "init",
	    value: function init(settings) {
	      this._canUse = BX.prop.getBoolean(settings, "canUse", false);
	      this._canSendMessage = BX.prop.getBoolean(settings, "canSendMessage", false);
	      this._manageUrl = BX.prop.getString(settings, "manageUrl", '');
	      this._senders = BX.prop.getArray(settings, "senders", []);
	      this._defaults = BX.prop.getObject(settings, "defaults", {
	        senderId: null,
	        from: null
	      });
	      this._communications = BX.prop.getArray(settings, "communications", []);
	      this._serviceUrl = BX.prop.getString(settings, 'serviceUrl', '');
	      this._ownerTypeId = BX.prop.getInteger(settings, 'ownerTypeId', 0);
	      this._ownerId = BX.prop.getInteger(settings, 'ownerId', 0);
	      this._senderId = BX.prop.getString(settings, 'providerId');
	      this._isSenderFixed = BX.prop.getBoolean(settings, 'isProviderFixed', false);
	      this._title = this._container.querySelector('[data-role="sender-title"]');
	      this._senderContainerNode = this._container.querySelector('[data-role="sender-container"]');
	      this._senderSelectorNode = this._container.querySelector('[data-role="sender-selector"]');
	      this._senderSelectorBlockNode = this._container.querySelector('[data-role="sender-selector-block"]');
	      this._fromContainerNode = this._container.querySelector('[data-role="from-container"]');
	      this._fromSelectorNode = this._container.querySelector('[data-role="from-selector"]');
	      this._clientContainerNode = this._container.querySelector('[data-role="client-container"]');
	      this._clientSelectorNode = this._container.querySelector('[data-role="client-selector"]');
	      this._toSelectorNode = this._container.querySelector('[data-role="to-selector"]');
	      this._messageLengthCounterNode = this._container.querySelector('[data-role="message-length-counter"]');
	      this._input = this._container.querySelector('[data-role="input"]');
	      this.saveButton = this._container.querySelector('[data-role="button-save"]');
	      this.cancelButton = this._container.querySelector('[data-role="button-cancel"]');

	      if (this._canUse && this._canSendMessage) {
	        this.initSenderSelector();
	        this.initFromSelector();
	        this.initClientContainer();
	        this.initClientSelector();
	        this.initToSelector();
	        this.initMessageLengthCounter();
	        this.initButtons();
	        this.setMessageLengthCounter();
	      }
	    }
	  }, {
	    key: "switchToFixedSenderAppearance",
	    value: function switchToFixedSenderAppearance(sender) {
	      this.setSender(sender);
	      var subtitleNode = document.querySelector('.ui-side-panel-wrap-below');

	      if (subtitleNode) {
	        subtitleNode.classList.add('crm-sms-send-subtitle');
	        subtitleNode.innerHTML = main_core.Loc.getMessage('CRM_SMS_SEND_SENDER_SUBTITLE', {
	          '#SENDER#': '<span class="crm-sms-send-subtitle-sender">' + sender.name + '</span>'
	        });
	      }

	      this._senderSelectorBlockNode.style.display = 'none';
	      this._senderContainerNode.style.display = 'inline';
	      this._clientContainerNode.style.display = 'inline';
	    }
	  }, {
	    key: "initSenderSelector",
	    value: function initSenderSelector() {
	      var _this$_senderId;

	      var defaultSenderId = (_this$_senderId = this._senderId) !== null && _this$_senderId !== void 0 ? _this$_senderId : this._defaults.senderId;
	      var defaultSender = this._senders[0].canUse ? this._senders[0] : null;
	      var restSender = null;
	      var menuItems = [];
	      var handler = this.onSenderSelectorClick.bind(this);

	      for (var i = 0; i < this._senders.length; ++i) {
	        if (this._senders[i].canUse && this._senders[i].fromList.length && (this._senders[i].id === defaultSenderId || !defaultSender)) {
	          defaultSender = this._senders[i];
	        }

	        if (this._senders[i].id === 'rest') {
	          restSender = this._senders[i];
	          continue;
	        }

	        menuItems.push({
	          text: this._senders[i].name,
	          sender: this._senders[i],
	          onclick: handler,
	          className: !this._senders[i].canUse || !this._senders[i].fromList.length ? 'crm-sms-send-popup-menu-item-disabled menu-popup-no-icon' : ''
	        });
	      }

	      if (defaultSender && this._isSenderFixed) {
	        this.switchToFixedSenderAppearance(defaultSender);
	        return;
	      }

	      if (restSender) {
	        if (restSender.fromList.length > 0) {
	          menuItems.push({
	            delimiter: true
	          });

	          for (var _i = 0; _i < restSender.fromList.length; ++_i) {
	            menuItems.push({
	              text: restSender.fromList[_i].name,
	              sender: restSender,
	              from: restSender.fromList[_i],
	              onclick: handler
	            });
	          }
	        }

	        menuItems.push({
	          delimiter: true
	        }, {
	          text: main_core.Loc.getMessage('CRM_SMS_REST_MARKETPLACE'),
	          href: '/marketplace/category/crm_robot_sms/',
	          target: '_blank'
	        });
	      }

	      if (defaultSender) {
	        this.setSender(defaultSender);
	      }

	      BX.bind(this._senderSelectorNode, 'click', this.openMenu.bind(this, 'sender', this._senderSelectorNode, menuItems));
	    }
	  }, {
	    key: "onSenderSelectorClick",
	    value: function onSenderSelectorClick(e, item) {
	      if (item.sender) {
	        if (!item.sender.canUse || !item.sender.fromList.length) {
	          window.open(item.sender.manageUrl);
	          return;
	        }

	        this.setSender(item.sender, true);
	        var from = item.from ? item.from : item.sender.fromList[0];
	        this.setFrom(from, true);
	      }

	      this._menu.close();
	    }
	  }, {
	    key: "setSender",
	    value: function setSender(sender, setAsDefault) {
	      this._senderId = sender.id;
	      this._fromList = sender.fromList;
	      this._senderSelectorNode.textContent = sender.shortName ? sender.shortName : sender.name;
	      var visualFn = sender.id === 'rest' ? 'hide' : 'show';
	      BX[visualFn](this._fromContainerNode);

	      if (setAsDefault) {
	        BX.userOptions.save("crm", "sms_manager_editor", "senderId", this._senderId);
	      }
	    }
	  }, {
	    key: "initFromSelector",
	    value: function initFromSelector() {
	      if (this._fromList.length > 0) {
	        var defaultFromId = this._defaults.from || this._fromList[0].id;
	        var defaultFrom = null;

	        for (var i = 0; i < this._fromList.length; ++i) {
	          if (this._fromList[i].id === defaultFromId || !defaultFrom) {
	            defaultFrom = this._fromList[i];
	          }
	        }

	        if (defaultFrom) {
	          this.setFrom(defaultFrom);
	        }
	      }

	      BX.bind(this._fromSelectorNode, 'click', this.onFromSelectorClick.bind(this));
	    }
	  }, {
	    key: "onFromSelectorClick",
	    value: function onFromSelectorClick(e) {
	      var menuItems = [];
	      var handler = this.onFromSelectorItemClick.bind(this);

	      for (var i = 0; i < this._fromList.length; ++i) {
	        menuItems.push({
	          text: this._fromList[i].name,
	          from: this._fromList[i],
	          onclick: handler
	        });
	      }

	      this.openMenu('from_' + this._senderId, this._fromSelectorNode, menuItems, e);
	    }
	  }, {
	    key: "onFromSelectorItemClick",
	    value: function onFromSelectorItemClick(e, item) {
	      if (item.from) {
	        this.setFrom(item.from, true);
	      }

	      this._menu.close();
	    }
	  }, {
	    key: "setFrom",
	    value: function setFrom(from, setAsDefault) {
	      this._from = from.id;

	      if (this._senderId === 'rest') {
	        this._senderSelectorNode.textContent = from.name;
	      } else {
	        this._fromSelectorNode.textContent = from.name;
	      }

	      if (setAsDefault) {
	        BX.userOptions.save("crm", "sms_manager_editor", "from", this._from);
	      }
	    }
	  }, {
	    key: "initClientContainer",
	    value: function initClientContainer() {
	      if (this._communications.length === 0) {
	        BX.hide(this._clientContainerNode);
	      }
	    }
	  }, {
	    key: "initClientSelector",
	    value: function initClientSelector() {
	      var menuItems = [];
	      var handler = this.onClientSelectorClick.bind(this);

	      for (var i = 0; i < this._communications.length; ++i) {
	        menuItems.push({
	          text: this._communications[i].caption,
	          client: this._communications[i],
	          onclick: handler
	        });

	        if (i === 0) {
	          this.setClient(this._communications[i]);
	        }
	      }

	      BX.bind(this._clientSelectorNode, 'click', this.openMenu.bind(this, 'comm', this._clientSelectorNode, menuItems));
	    }
	  }, {
	    key: "onClientSelectorClick",
	    value: function onClientSelectorClick(e, item) {
	      if (item.client) {
	        this.setClient(item.client);
	      }

	      this._menu.close();
	    }
	  }, {
	    key: "setClient",
	    value: function setClient(client) {
	      this._commEntityTypeId = client.entityTypeId;
	      this._commEntityId = client.entityId;
	      this._clientSelectorNode.textContent = client.caption;
	      this._toList = client.phones;
	      this.setTo(client.phones[0]);
	    }
	  }, {
	    key: "initToSelector",
	    value: function initToSelector() {
	      BX.bind(this._toSelectorNode, 'click', this.onToSelectorClick.bind(this));
	    }
	  }, {
	    key: "onToSelectorClick",
	    value: function onToSelectorClick(e) {
	      var menuItems = [];
	      var handler = this.onToSelectorItemClick.bind(this);

	      for (var i = 0; i < this._toList.length; ++i) {
	        menuItems.push({
	          text: this._toList[i].valueFormatted || this._toList[i].value,
	          to: this._toList[i],
	          onclick: handler
	        });
	      }

	      this.openMenu('to_' + this._commEntityTypeId + '_' + this._commEntityId, this._toSelectorNode, menuItems, e);
	    }
	  }, {
	    key: "onToSelectorItemClick",
	    value: function onToSelectorItemClick(e, item) {
	      if (item.to) {
	        this.setTo(item.to);
	      }

	      this._menu.close();
	    }
	  }, {
	    key: "setTo",
	    value: function setTo(to) {
	      this._to = to.value;
	      this._toSelectorNode.textContent = to.valueFormatted || to.value;
	    }
	  }, {
	    key: "openMenu",
	    value: function openMenu(menuId, bindElement, menuItems, e) {
	      if (this._shownMenuId === menuId) {
	        return;
	      }

	      if (this._shownMenuId !== null && this._menu) {
	        this._menu.close();

	        this._shownMenuId = null;
	      }

	      BX.PopupMenu.show(this._id + menuId, bindElement, menuItems, {
	        offsetTop: 0,
	        offsetLeft: 36,
	        angle: {
	          position: "top",
	          offset: 0
	        },
	        events: {
	          onPopupClose: this.onMenuClose.bind(this)
	        }
	      });
	      this._menu = BX.PopupMenu.currentItem;
	      e.preventDefault();
	    }
	  }, {
	    key: "onMenuClose",
	    value: function onMenuClose() {
	      this._shownMenuId = null;
	      this._menu = null;
	    }
	  }, {
	    key: "initMessageLengthCounter",
	    value: function initMessageLengthCounter() {
	      this._messageLengthMax = parseInt(this._messageLengthCounterNode.getAttribute('data-length-max'));
	      BX.bind(this._input, 'keyup', this.setMessageLengthCounter.bind(this));
	    }
	  }, {
	    key: "setMessageLengthCounter",
	    value: function setMessageLengthCounter() {
	      var length = this._input.value.length;
	      this._messageLengthCounterNode.textContent = length;
	      var classFn = length >= this._messageLengthMax ? 'addClass' : 'removeClass';
	      BX[classFn](this._messageLengthCounterNode, 'sms-symbol-counter-number-overhead');
	    }
	  }, {
	    key: "initButtons",
	    value: function initButtons() {
	      BX.bind(this.saveButton, 'click', this.save.bind(this));
	      BX.bind(this.cancelButton, 'click', this.cancel.bind(this));
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      var text = this._input.value;

	      if (text === "") {
	        return;
	      }

	      if (!this._communications.length) {
	        alert(main_core.Loc.getMessage('CRM_SMS_ERROR_NO_COMMUNICATIONS'));
	        return;
	      }

	      if (this._isRequestRunning) {
	        return;
	      }

	      this._isRequestRunning = true;
	      BX.ajax({
	        url: BX.util.add_url_param(this._serviceUrl, {
	          "action": "save_sms_message",
	          "sender": this._senderId
	        }),
	        method: "POST",
	        dataType: "json",
	        data: {
	          'site': main_core.Loc.getMessage('SITE_ID'),
	          'sessid': BX.bitrix_sessid(),
	          "ACTION": "SAVE_SMS_MESSAGE",
	          "SENDER_ID": this._senderId,
	          "MESSAGE_FROM": this._from,
	          "MESSAGE_TO": this._to,
	          "MESSAGE_BODY": text,
	          "OWNER_TYPE_ID": this._ownerTypeId,
	          "OWNER_ID": this._ownerId,
	          "TO_ENTITY_TYPE_ID": this._commEntityTypeId,
	          "TO_ENTITY_ID": this._commEntityId
	        },
	        onsuccess: this.onSaveSuccess.bind(this),
	        onfailure: this.onSaveFailure.bind(this)
	      });
	    }
	  }, {
	    key: "cancel",
	    value: function cancel() {
	      if (BX.SidePanel) {
	        var curSlider = BX.SidePanel.Instance.getSliderByWindow(window);

	        if (curSlider) {
	          curSlider.close();
	        }
	      }
	    }
	  }, {
	    key: "onSaveSuccess",
	    value: function onSaveSuccess(data) {
	      this._isRequestRunning = false;
	      var error = BX.prop.getString(data, "ERROR", "");

	      if (error !== "") {
	        alert(error);
	        return;
	      }

	      this.cancel();
	    }
	  }, {
	    key: "onSaveFailure",
	    value: function onSaveFailure(data) {
	      this._isRequestRunning = false;
	    }
	  }]);
	  return CrmSmsSend;
	}();

	namespace.CrmSmsSend = CrmSmsSend;

}((this.window = this.window || {}),BX));
//# sourceMappingURL=script.js.map
