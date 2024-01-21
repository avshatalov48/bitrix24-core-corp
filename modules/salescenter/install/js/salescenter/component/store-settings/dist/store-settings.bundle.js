/* eslint-disable */
this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
(function (exports,ui_vue,rest_client,salescenter_manager,main_popup,main_core,salescenter_component_storePreview,salescenter_component_mycompanyRequisiteSettings) {
    'use strict';

    var StoreSettings = /*#__PURE__*/function () {
      function StoreSettings(containerId, parameters) {
        babelHelpers.classCallCheck(this, StoreSettings);
        parameters = parameters || {};
        var data = {};
        data.companyId = BX.prop.get(parameters, "companyId", 0);
        data.companyTitle = BX.prop.get(parameters, "companyTitle", '');
        data.previewLang = BX.prop.get(parameters, "previewLang", '');
        data.companyPhone = '';
        data.phoneIdSelected = BX.prop.get(parameters, "phoneIdSelected", 0);
        data.companyPhoneList = BX.prop.get(parameters, "companyPhoneList", '');
        data.phoneValueSelected = BX.prop.get(parameters, "phoneValueSelected", '');
        data.originPhoneIdSelected = BX.prop.get(parameters, "phoneIdSelected", 0);
        this.componentName = 'bitrix:salescenter.company.contacts';
        this.slider = BX.SidePanel.Instance.getTopSlider();
        var context = this;
        ui_vue.Vue.create({
          el: '#' + containerId,
          data: data,
          components: {
            'my-company-requisite-block': salescenter_component_mycompanyRequisiteSettings.RequisiteBlock,
            'store-preview-block': salescenter_component_storePreview.PreviewBlock
          },
          computed: {
            loc: function loc() {
              return ui_vue.Vue.getFilteredPhrases('SC_STORE_SETTINGS_');
            },
            isNew: function isNew() {
              return this.companyId === undefined || parseInt(this.companyId) <= 0;
            },
            getPhonesList: function getPhonesList() {
              return BX.type.isArray(this.companyPhoneList) ? this.companyPhoneList : [];
            },
            getSelectedPhoneId: function getSelectedPhoneId() {
              return parseInt(this.phoneIdSelected) > 0 ? this.phoneIdSelected : 0;
            },
            getSelectedPhoneValue: function getSelectedPhoneValue() {
              return this.phoneValueSelected === '' ? this.loc.SC_STORE_SETTINGS_COMPANY_PHONE_DEFAULT : this.phoneValueSelected;
            },
            getSelectedPhoneNumber: function getSelectedPhoneNumber() {
              var _this = this;
              var phones = this.getPhonesList;
              var number = '';
              if (phones.length > 0) {
                phones.forEach(function (item) {
                  if (item.id === _this.getSelectedPhoneId) {
                    number = item.value;
                  }
                });
              }
              return number;
            },
            getOriginSelectedPhoneId: function getOriginSelectedPhoneId() {
              var _this2 = this;
              var phones = this.getPhonesList;
              var id = 0;
              if (phones.length > 0) {
                phones.forEach(function (item) {
                  if (item.id === _this2.originPhoneIdSelected) {
                    id = item.id;
                  }
                });
              }
              return id;
            }
          },
          created: function created() {
            this.$app = context;
          },
          mounted: function mounted() {
            BX.UI.Hint.init(BX('salescenter-company-contacts-wrapper'));
          },
          methods: {
            requisiteOpenSlider: function requisiteOpenSlider(e) {
              var _this3 = this;
              var url = '/crm/company/details/' + this.companyId + '/?init_mode=edit';
              salescenter_manager.Manager.openSlider(url).then(function () {
                return _this3.refresh();
              });
            },
            showPopupMenu: function showPopupMenu(e) {
              var _this4 = this;
              var phoneItems = [];
              var setItem = function setItem(ev, data) {
                _this4.phoneIdSelected = data.id;
                _this4.popupMenu.close();
              };
              var phones = this.getPhonesList;
              if (phones.length > 0) {
                phoneItems.push({
                  text: this.loc.SC_STORE_SETTINGS_COMPANY_PHONE_DEFAULT,
                  id: '0',
                  onclick: setItem.bind(this)
                });
                phoneItems.push({
                  text: this.loc.SC_STORE_SETTINGS_COMPANY_PHONE_DELIMETER,
                  id: '-1',
                  delimiter: true
                });
                phones.forEach(function (item) {
                  phoneItems.push({
                    text: main_core.Text.encode(item.value),
                    id: item.id,
                    onclick: setItem.bind(_this4)
                  });
                });
              }
              this.popupMenu = new main_popup.PopupMenuWindow({
                bindElement: e.target,
                minWidth: e.target.offsetWidth,
                items: phoneItems
              });
              this.popupMenu.show();
            },
            reset: function reset() {
              this.companyPhoneList = [];
              this.phoneIdSelected = 0;
              this.companyTitle = '';
            },
            refresh: function refresh() {
              var _this5 = this;
              rest_client.rest.callMethod('crm.company.get', {
                id: this.companyId
              }).then(function (result) {
                var answer = result.data();
                _this5.reset();
                _this5.companyTitle = BX.prop.get(answer, "TITLE", '');
                var phones = BX.prop.get(answer, "PHONE", []);
                if (BX.type.isObject(phones) && Object.values(phones).length > 0) {
                  Object.values(phones).forEach(function (item) {
                    _this5.companyPhoneList.push({
                      id: item.ID,
                      value: item.VALUE
                    });
                  });
                }
                _this5.phoneIdSelected = _this5.getOriginSelectedPhoneId;
              });
            },
            save: function save() {
              if (this.isNew) {
                this.addCompany();
              } else {
                this.updateCompany();
              }
            },
            updateCompany: function updateCompany() {
              var _this6 = this;
              this.$app.query('updateCompanyContacts', {
                id: this.companyId,
                fields: {
                  title: this.companyTitle,
                  phoneIdSelected: this.phoneIdSelected
                }
              }, 'salescenterContactsCompanyUpdate').then(function (response) {
                _this6.$app.closeApplication();
              })["catch"](function (result) {
                var errors = BX.prop.getArray(result, "errors", []);
                if (BX.type.isArray(errors) && errors.length > 0) {
                  var error = BX.prop.get(errors[0], 'message', '');
                  salescenter_manager.Manager.showNotification(error);
                }
              });
            },
            addCompany: function addCompany() {
              var _this7 = this;
              this.$app.query('saveCompanyContacts', {
                fields: {
                  title: this.companyTitle,
                  phone: this.companyPhone
                }
              }, 'salescenterContactsCompanyAdd').then(function (response) {
                _this7.$app.closeApplication();
              })["catch"](function (result) {
                var errors = BX.prop.getArray(result, "errors", []);
                if (BX.type.isArray(errors) && errors.length > 0) {
                  var error = BX.prop.get(errors[0], 'message', '');
                  salescenter_manager.Manager.showNotification(error);
                }
              });
            },
            close: function close() {
              this.$app.closeApplication();
            }
          },
          watch: {
            phoneIdSelected: function phoneIdSelected() {
              this.phoneValueSelected = this.getSelectedPhoneNumber;
            }
          },
          template: "   \n\t\t\t\t<div class=\"salescenter-company-contacts-wrapper\" id=\"salescenter-company-contacts-wrapper\">\n\t\t\t\t\t<div class=\"salescenter-company-contacts-item\">\n\t\t\t\t\t\t<div class=\"salescenter-company-contacts-area\">\n\t\t\t\t\t\t\t<div class=\"salescenter-company-contacts-area-item\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t{{loc.SC_STORE_SETTINGS_COMPANY_NAME}}\n\t\t\t\t\t\t\t\t\t<span class=\"ui-hint\" :data-hint=\"loc.SC_STORE_SETTINGS_COMPANY_NAME_HINT\"></span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element\" name=\"name\" v-model=\"companyTitle\">\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t<div class=\"salescenter-company-contacts-area-item\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">{{loc.SC_STORE_SETTINGS_COMPANY_PHONE_NUMBER}}</div>\n\t\t\t\t\t\t\t\t<template v-if=\"isNew\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element\" name=\"phone\" v-model=\"companyPhone\">\n\t\t\t\t\t\t\t\t\t</div>\t\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t<template v-if=\"getPhonesList.length === 0\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"salescenter-company-contacts-text\">{{loc.SC_STORE_SETTINGS_COMPANY_PHONE_EMPTY}}<a href=\"javascript:void(0)\" @click=\"requisiteOpenSlider()\">{{loc.SC_STORE_SETTINGS_COMPANY_PHONE_ADD}}</a></div>\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown salescenter-company-contacts-area-item-input\" @click=\"showPopupMenu($event)\">\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-element\">{{this.getSelectedPhoneValue}}</div>\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"salescenter-company-contacts-controls\">\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"salescenter-company-contacts-link\" @click=\"requisiteOpenSlider()\">{{loc.SC_STORE_SETTINGS_COMPANY_PHONE_ADD_SMALL}}</div>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<my-company-requisite-block \n\t\t\t\t\t\t\t:isNewCompany=\"isNew\"\n\t\t\t\t\t\t\t:companyId=\"companyId\"\n\t\t\t\t\t\t\tv-on:on-mycompany-requisite-settings=\"refresh\"/>\n\t\t\t\t\t\t<div class=\"salescenter-company-contacts-panel\">\n\t\t\t\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-success\" @click=\"save\">{{loc.SC_STORE_SETTINGS_SAVE}}</button>\n\t\t\t\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-link\" @click=\"close\">{{loc.SC_STORE_SETTINGS_CANCEL}}</button> \n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<store-preview-block :options=\"{lang: this.previewLang}\"/>\n\t\t\t</div>"
        });
      }
      babelHelpers.createClass(StoreSettings, [{
        key: "query",
        value: function query(action, data, analyticsLabel) {
          var result;
          result = BX.ajax.runComponentAction(this.componentName, action, {
            mode: 'class',
            data: data,
            analyticsLabel: analyticsLabel
          });
          return result;
        }
      }, {
        key: "closeApplication",
        value: function closeApplication() {
          if (this.slider) {
            this.slider.close();
          }
        }
      }], [{
        key: "showNotification",
        value: function showNotification(message) {
          if (!message) {
            return;
          }
          BX.loadExt('ui.notification').then(function () {
            BX.UI.Notification.Center.notify({
              content: message
            });
          });
        }
      }]);
      return StoreSettings;
    }();

    exports.StoreSettings = StoreSettings;

}((this.BX.Salescenter.Component = this.BX.Salescenter.Component || {}),BX,BX,BX.Salescenter,BX.Main,BX,BX.Salescenter.Component,BX.Salescenter.Component));
//# sourceMappingURL=store-settings.bundle.js.map
