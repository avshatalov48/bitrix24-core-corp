(function (exports,ui_vue,ui_vue_vuex) {
    'use strict';

    function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
    function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
    var EVENT_POSTFIX = 'Openlines';
    var LIVECHAT_PREFIX = 'livechat';
    ui_vue.BitrixVue.component('bx-imopenlines-form', {
      props: {
        message: {
          type: Object,
          required: false
        }
      },
      data: function data() {
        return {
          formSuccess: false,
          formError: false
        };
      },
      mounted: function mounted() {
        if (this.filledFormFlag) {
          this.formSuccess = true;
        }
      },
      computed: _objectSpread({
        chatId: function chatId() {
          return this.application.dialog.chatId;
        },
        formId: function formId() {
          if (this.message) {
            return String(this.message.params.CRM_FORM_ID);
          }
          if (this.widget.common.crmFormsSettings.welcomeFormId) {
            return this.widget.common.crmFormsSettings.welcomeFormId;
          }
          return '';
        },
        formSec: function formSec() {
          if (this.message) {
            return this.message.params.CRM_FORM_SEC;
          }
          if (this.widget.common.crmFormsSettings.welcomeFormSec) {
            return this.widget.common.crmFormsSettings.welcomeFormSec;
          }
          return '';
        },
        showForm: function showForm() {
          return this.formId && this.formSec && !this.formSuccess && !this.formError;
        },
        filledFormFlag: function filledFormFlag() {
          if (this.message) {
            return this.message.params.CRM_FORM_FILLED === 'Y';
          }
          return false;
        },
        messageCount: function messageCount() {
          return this.dialog.messageCount;
        }
      }, ui_vue_vuex.Vuex.mapState({
        widget: function widget(state) {
          return state.widget;
        },
        application: function application(state) {
          return state.application;
        },
        dialog: function dialog(state) {
          return state.dialogues.collection[state.application.dialog.dialogId];
        }
      })),
      watch: {
        filledFormFlag: function filledFormFlag(newValue) {
          if (newValue === true && !this.formSuccess) {
            this.formSuccess = true;
          }
        },
        chatId: function chatId(newValue) {
          // chatId > 0 means chat and user were initialized
          if (newValue !== 0 && this.widgetInitPromiseResolve) {
            this.widgetInitPromiseResolve();
          }
        }
      },
      methods: {
        getCrmBindings: function getCrmBindings() {
          var _this = this;
          return new Promise(function (resolve, reject) {
            _this.$Bitrix.RestClient.get().callMethod('imopenlines.widget.crm.bindings.get', {
              'OPENLINES_CODE': _this.buildOpenlinesCode()
            }).then(resolve)["catch"](reject);
          });
        },
        onBeforeFormSubmit: function onBeforeFormSubmit(eventData) {
          if (this.signedEntities && this.signedEntities !== '') {
            eventData.sign = this.signedEntities;
          }
        },
        onFormSubmit: function onFormSubmit(eventData) {
          var _this2 = this;
          this.eventData = eventData;
          // redefine form promise so we can send form manually later
          this.eventData.promise = this.eventData.promise.then(function () {
            return new Promise(function (resolve) {
              if (_this2.chatId === 0) {
                // promise we resolve after user and chat are inited, resolve method is saved to use in chatId watcher
                new Promise(function (widgetResolve, widgetReject) {
                  _this2.widgetInitPromiseResolve = widgetResolve;
                }).then(function () {
                  _this2.setFormProperties();
                  return resolve();
                });
                _this2.getApplication().requestData();
              }
              // we have user and chat so we can just resolve form promise instantly
              else {
                // request current crm bindings and attach them to form
                if (_this2.widget.common.crmFormsSettings.welcomeFormDelay) {
                  _this2.getCrmBindings().then(function (result) {
                    _this2.signedEntities = result.data();
                    _this2.setFormProperties();
                    return resolve();
                  })["catch"](function (error) {
                    console.error('Error getting CRM bindings', error);
                  });
                } else {
                  _this2.setFormProperties();
                  return resolve();
                }
              }
            });
          });
        },
        setFormProperties: function setFormProperties() {
          if (!this.eventData) {
            return false;
          }
          this.eventData.form.setProperty('eventNamePostfix', EVENT_POSTFIX);
          this.eventData.form.setProperty('openlinesCode', this.buildOpenlinesCode());
          if (this.message) {
            var _this$message$params$;
            this.eventData.form.setProperty('messageId', this.message.id);
            this.eventData.form.setProperty('isWelcomeForm', (_this$message$params$ = this.message.params.IS_WELCOME_FORM) !== null && _this$message$params$ !== void 0 ? _this$message$params$ : 'N');
          } else {
            this.eventData.form.setProperty('isWelcomeForm', 'Y');
          }
        },
        buildOpenlinesCode: function buildOpenlinesCode() {
          var configId = 0;
          if (this.dialog.entityId !== '') {
            configId = this.dialog.entityId.split('|')[0];
          }
          var chatId = this.dialog.chatId || 0;
          var userId = this.application.common.userId || 0;
          return "".concat(LIVECHAT_PREFIX, "|").concat(configId, "|").concat(chatId, "|").concat(userId);
        },
        onFormSendSuccess: function onFormSendSuccess() {
          if (!this.message) {
            this.$store.commit('widget/common', {
              dialogStart: true
            });
          }
          this.$emit('formSendSuccess');
          this.formSuccess = true;
        },
        onFormSendError: function onFormSendError(error) {
          this.formError = true;
          this.$emit('formSendError', {
            error: error
          });
        },
        getSuccessText: function getSuccessText() {
          return this.widget.common.crmFormsSettings.successText;
        },
        getErrorText: function getErrorText() {
          return this.widget.common.crmFormsSettings.errorText;
        },
        getApplication: function getApplication() {
          return this.$Bitrix.Application.get();
        }
      },
      template: "\n\t\t<div class=\"bx-im-message bx-im-message-without-menu bx-im-message-without-avatar bx-imopenlines-form-wrapper\">\n\t\t\t<div v-show=\"showForm\" class=\"bx-imopenlines-form-content\">\n\t\t\t\t<bx-crm-form\n\t\t\t\t\t:id=\"formId\"\n\t\t\t\t\t:sec=\"formSec\"\n\t\t\t\t\t:address=\"widget.common.host\"\n\t\t\t\t\t:lang=\"application.common.languageId\"\n\t\t\t\t\t@form:submit:post:before=\"onBeforeFormSubmit\"\n\t\t\t\t\t@form:submit=\"onFormSubmit\"\n\t\t\t\t\t@form:send:success=\"onFormSendSuccess\"\n\t\t\t\t\t@form:send:error=\"onFormSendError\"\n\t\t\t\t/>\n\t\t\t</div>\n\t\t\t<div v-show=\"formSuccess\" class=\"bx-imopenlines-form-result-container bx-imopenlines-form-success\">\n\t\t\t\t<div class=\"bx-imopenlines-form-result-icon\"></div>\n\t\t\t\t<div class=\"bx-imopenlines-form-result-title\">\n\t\t\t\t\t{{ getSuccessText() }}\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div v-show=\"formError\" class=\"bx-imopenlines-form-result-container bx-imopenlines-form-error\">\n\t\t\t\t<div class=\"bx-imopenlines-form-result-icon\"></div>\n\t\t\t\t<div class=\"bx-imopenlines-form-result-title\">\n\t\t\t\t\t{{ getErrorText() }}\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
    });

}((this.window = this.window || {}),BX,BX));
//# sourceMappingURL=form.bundle.js.map
