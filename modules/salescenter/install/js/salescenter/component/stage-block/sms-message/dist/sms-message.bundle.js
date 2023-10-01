this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
this.BX.Salescenter.Component = this.BX.Salescenter.Component || {};
(function (exports,main_core_events,main_core,main_popup,salescenter_manager,ui_vue) {
	'use strict';

	var Error = {
	  props: {
	    error: {
	      type: Object,
	      required: true
	    }
	  },
	  methods: {
	    openSlider: function openSlider() {
	      var _this = this;
	      this.error.fixer().then(function () {
	        return _this.onConfigure(_this.error);
	      });
	    },
	    onConfigure: function onConfigure(data) {
	      this.$emit('on-configure', new main_core_events.BaseEvent({
	        data: data
	      }));
	    }
	  },
	  template: "\n\t\t<div class=\"ui-alert ui-alert-warning ui-alert-xs ui-alert-icon-danger salescenter-app-payment-by-sms-item-container-alert\">\n\t\t\t<span class=\"ui-alert-message\">\n\t\t\t\t{{error.text}}\n\t\t\t</span>\n\t\t\t<span\n\t\t\t\tv-if=\"error.fixer && error.fixText\"\n\t\t\t\tclass=\"salescenter-app-payment-by-sms-item-container-alert-config\"\n\t\t\t\t@click=\"openSlider()\"\n\t\t\t>\n\t\t\t\t{{error.fixText}}\n\t\t\t</span>\n\t\t</div>\n\t"
	};

	var MessageControl = {
	  props: {
	    editable: {
	      type: Boolean,
	      required: true
	    }
	  },
	  computed: {
	    classObject: function classObject() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-sms-content-edit': true,
	        'salescenter-app-payment-by-sms-item-container-sms-content-save': this.editable
	      };
	    }
	  },
	  methods: {
	    onSave: function onSave(e) {
	      this.$emit('control-on-save', e);
	    }
	  },
	  template: "\n\t\t<div :class=\"classObject\" @click=\"onSave($event)\"></div>\n\t"
	};

	var MessageEdit = {
	  props: {
	    text: {
	      type: String,
	      required: true
	    },
	    selectedMode: {
	      type: String,
	      required: true
	    }
	  },
	  computed: {
	    classObject: function classObject() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-sms-content-message-text ': true,
	        'salescenter-app-payment-by-sms-item-container-sms-content-message-text-edit': true
	      };
	    }
	  },
	  methods: {
	    updateMessage: function updateMessage(e) {
	      this.text = e.target.innerText;
	      this.$emit('edit-on-update-template', this.text);
	    },
	    isHasLink: function isHasLink() {
	      return this.text.match(/#LINK#/);
	    },
	    saveSmsTemplate: function saveSmsTemplate(smsText) {
	      BX.ajax.runComponentAction("bitrix:salescenter.app", "saveSmsTemplate", {
	        mode: "class",
	        data: {
	          smsTemplate: smsText,
	          mode: this.selectedMode
	        },
	        analyticsLabel: 'salescenterSmsTemplateChange'
	      })["catch"](function (response) {
	        var errorMessage = response.errors.map(function (err) {
	          return err.message;
	        }).join("; ");
	        alert(errorMessage);
	      });
	    },
	    adjustUpdateMessage: function adjustUpdateMessage(e) {
	      this.updateMessage(e);
	      if (!this.isHasLink()) {
	        this.showErrorHasLink(e);
	      } else {
	        this.saveSmsTemplate(this.text);
	      }
	    },
	    onPressKey: function onPressKey(e) {
	      if (e.code === "Enter") {
	        this.adjustUpdateMessage(e);
	        this.afterPressKey(e);
	      }
	    },
	    onBlur: function onBlur(e) {
	      this.beforeBlur();
	      this.adjustUpdateMessage(e);
	    },
	    afterPressKey: function afterPressKey(e) {
	      this.$emit('edit-on-after-press-key', e);
	    },
	    beforeBlur: function beforeBlur(e) {
	      this.$emit('edit-on-before-blur', e);
	    },
	    showErrorHasLink: function showErrorHasLink(e) {
	      this.$emit('edit-on-has-link-error', e);
	    }
	  },
	  template: "\n\n\t\t<div \n\t\t\tcontenteditable=\"true\"\t\n\t\t\t:class=\"classObject\"\n\t\t\t@blur=\"onBlur($event)\"\n\t\t\t@keydown=\"onPressKey($event)\"\n\t\t>{{text}}\n</div>\n\n\t"
	};

	var MessageView = {
	  props: {
	    text: {
	      type: String,
	      required: true
	    },
	    orderPublicUrl: {
	      type: String,
	      required: true
	    }
	  },
	  computed: {
	    classObject: function classObject() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-sms-content-message-text': true
	      };
	    }
	  },
	  methods: {
	    onMouseenter: function onMouseenter(e) {
	      this.$emit('view-on-mouseenter', e);
	    },
	    onMouseleave: function onMouseleave() {
	      this.$emit('view-on-mouseleave');
	    },
	    getSmsMessage: function getSmsMessage() {
	      var link = "<span class=\"salescenter-app-payment-by-sms-item-container-sms-content-message-link\">".concat(this.orderPublicUrl, "</span><span class=\"salescenter-app-payment-by-sms-item-container-sms-content-message-link-ref\">xxxxx</span>") + " ";
	      var text = this.text;
	      return main_core.Text.encode(text).replace(/#LINK#/g, link);
	    }
	  },
	  template: "\n\t\t<div \n\t\t\tv-html=\"getSmsMessage()\"\n\t\t\tcontenteditable=\"false\" \n\t\t\t:class=\"classObject\" \n\t\t\tv-on:mouseenter=\"onMouseenter($event)\" \n\t\t\tv-on:mouseleave=\"onMouseleave\"\n\t\t/>\n\t"
	};

	var MODE_VIEW = 'view';
	var MODE_EDIT = 'edit';
	var MessageEditor = {
	  props: {
	    editor: {
	      type: Object,
	      required: true
	    },
	    isReadOnly: {
	      type: Boolean,
	      "default": false
	    },
	    selectedMode: {
	      type: String,
	      required: true
	    }
	  },
	  data: function data() {
	    return {
	      mode: MODE_VIEW,
	      text: this.editor.template,
	      hasError: false,
	      orderPublicUrl: this.editor.url,
	      smsEditMessageMode: false
	    };
	  },
	  components: {
	    'sms-message-edit-block': MessageEdit,
	    'sms-message-view-block': MessageView,
	    'sms-message-control-block': MessageControl
	  },
	  computed: {
	    getMode: function getMode() {
	      return this.mode;
	    },
	    setMode: function setMode(value) {
	      this.mode = value;
	    }
	  },
	  methods: {
	    isEditable: function isEditable() {
	      return this.mode === MODE_EDIT && !this.isReadOnly;
	    },
	    resetError: function resetError() {
	      this.hasError = false;
	    },
	    //region edit
	    updateTemplate: function updateTemplate(text) {
	      this.editor.template = text;
	      this.$root.$app.sendingMethodDesc.text = text;
	      this.$root.$app.sendingMethodDesc.text_modes[this.selectedMode] = text;
	    },
	    showPopupHint: function showPopupHint(target, message, timer) {
	      var _this = this;
	      if (this.popup) {
	        this.popup.destroy();
	        this.popup = null;
	      }
	      if (!target && !message) {
	        return;
	      }
	      this.popup = new main_popup.Popup(null, target, {
	        events: {
	          onPopupClose: function onPopupClose() {
	            _this.popup.destroy();
	            _this.popup = null;
	          }
	        },
	        darkMode: true,
	        content: message,
	        offsetLeft: target.offsetWidth
	      });
	      if (timer) {
	        setTimeout(function () {
	          _this.popup.destroy();
	          _this.popup = null;
	        }, timer);
	      }
	      this.popup.show();
	    },
	    afterPressKey: function afterPressKey(e) {
	      this.afterSavePressKey(e);
	    },
	    beforeBlur: function beforeBlur() {
	      this.hasError = false;
	    },
	    showHasLinkErrorHint: function showHasLinkErrorHint(e) {
	      this.hasError = true;
	    },
	    afterSavePressKey: function afterSavePressKey(e) {
	      this.reverseMode();
	      if (this.hasError) {
	        this.showPopupHint(e.target, main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_TEMPLATE_ERROR'), 2000);
	      }
	      this.resetError();
	    },
	    //endregion
	    //region view
	    showSmsMessagePopupHint: function showSmsMessagePopupHint(e) {
	      this.showPopupHint(e.target, main_core.Loc.getMessage('SALESCENTER_SMS_MESSAGE_HINT'));
	    },
	    hidePopupHint: function hidePopupHint() {
	      if (this.popup) {
	        this.popup.destroy();
	      }
	    },
	    //endregion
	    //region control
	    reverseMode: function reverseMode() {
	      this.mode === MODE_EDIT ? this.mode = MODE_VIEW : this.mode = MODE_EDIT;
	    },
	    afterSaveControl: function afterSaveControl(e) {
	      if (!this.hasError) {
	        this.reverseMode();
	      } else {
	        this.showPopupHint(e.target, main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_TEMPLATE_ERROR'), 2000);
	      }
	    } //endregion
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms-content\">\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms-content-message\">\t\n\t\t\t\t<template v-if=\"isEditable()\">\n\t\t\t\t\t<sms-message-edit-block\t\t\t\t\n\t\t\t\t\t\t:text=\"editor.template\"\n\t\t\t\t\t\t:selectedMode=\"selectedMode\"\n\t\t\t\t\t\tv-on:edit-on-before-blur=\"beforeBlur\"\n\t\t\t\t\t\tv-on:edit-on-after-press-key=\"afterPressKey\"\n\t\t\t\t\t\tv-on:edit-on-update-template=\"updateTemplate\"\n\t\t\t\t\t\tv-on:edit-on-has-link-error=\"showHasLinkErrorHint\"\n\t\t\t\t\t/>\n\t\t\t\t</template> \n\t\t\t\t<template v-else>\n\t\t\t\t\t<sms-message-view-block\n\t\t\t\t\t\t:text=\"editor.template\"\n\t\t\t\t\t\t:orderPublicUrl=\"orderPublicUrl\"\n\t\t\t\t\t\tv-on:view-on-mouseenter=\"showSmsMessagePopupHint\"\n\t\t\t\t\t\tv-on:view-on-mouseleave=\"hidePopupHint\"\n\t\t\t\t\t/>\n\t\t\t\t</template>\n\t\t\t\t<sms-message-control-block v-if=\"!isReadOnly\"\n\t\t\t\t\t:editable=\"isEditable()\"\n\t\t\t\t\tv-on:control-on-save=\"afterSaveControl\"\n\t\t\t\t/>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var SenderList = {
	  props: ['list', 'initSelected', 'settingUrl'],
	  computed: {
	    selectedSender: function selectedSender() {
	      var _this = this;
	      return this.list.find(function (sender) {
	        return sender.id === _this.selected;
	      });
	    },
	    selectedSenderName: function selectedSenderName() {
	      return this.selectedSender ? this.selectedSender.name : '';
	    },
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALESCENTER_SENDER_LIST_CONTENT_');
	    }
	  },
	  data: function data() {
	    return {
	      selected: null
	    };
	  },
	  created: function created() {
	    if (this.initSelected) {
	      this.onSelectedSender(this.initSelected);
	    } else if (this.list && this.list.length > 0) {
	      this.onSelectedSender(this.list[0].id);
	    }
	  },
	  methods: {
	    openSlider: function openSlider() {
	      var _this2 = this;
	      salescenter_manager.Manager.openSlider(this.settingUrl).then(function () {
	        return _this2.onConfigure();
	      });
	    },
	    onConfigure: function onConfigure() {
	      this.$emit('on-configure');
	    },
	    onSelectedSender: function onSelectedSender(value) {
	      this.selected = value;
	      this.$emit('on-selected', value);
	    },
	    render: function render(target, array) {
	      var _this3 = this;
	      var menuItems = [];
	      var setItem = function setItem(ev) {
	        target.innerHTML = ev.target.innerHTML;
	        _this3.onSelectedSender(ev.currentTarget.getAttribute('data-item-sender-value'));
	        _this3.popupMenu.close();
	      };
	      for (var index in array) {
	        if (!array.hasOwnProperty(index)) {
	          continue;
	        }
	        menuItems.push({
	          text: array[index].name,
	          dataset: {
	            'itemSenderValue': array[index].id
	          },
	          onclick: setItem
	        });
	      }
	      menuItems.push({
	        text: this.localize.SALESCENTER_SENDER_LIST_CONTENT_SETTINGS,
	        onclick: function onclick() {
	          _this3.openSlider();
	          _this3.popupMenu.close();
	        }
	      });
	      this.popupMenu = new main_popup.PopupMenuWindow({
	        bindElement: target,
	        items: menuItems
	      });
	      this.popupMenu.show();
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms-content-info\">\n\t\t\t<slot name=\"sms-sender-list-text-send-from\"></slot>\n\t\t\t<span @click=\"render($event.target, list)\">{{selectedSenderName}}</span>\n\t\t</div>\n\t"
	};

	var UserAvatar = {
	  props: {
	    manager: {
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    avatarStyle: function avatarStyle() {
	      var url = this.manager.photo ? {
	        'background-image': 'url(' + this.manager.photo + ')'
	      } : null;
	      return [url];
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms-user\">\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms-user-avatar\" :style=\"avatarStyle\"></div>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms-user-name\">{{manager.name}}</div>\n\t\t</div>\n\t"
	};

	exports.Error = Error;
	exports.MessageControl = MessageControl;
	exports.MessageEdit = MessageEdit;
	exports.MessageEditor = MessageEditor;
	exports.MessageView = MessageView;
	exports.SenderList = SenderList;
	exports.UserAvatar = UserAvatar;

}((this.BX.Salescenter.Component.StageBlock = this.BX.Salescenter.Component.StageBlock || {}),BX.Event,BX,BX.Main,BX.Salescenter,BX));
//# sourceMappingURL=sms-message.bundle.js.map
