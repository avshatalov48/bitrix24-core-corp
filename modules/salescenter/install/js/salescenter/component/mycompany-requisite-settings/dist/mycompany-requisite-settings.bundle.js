this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
(function (exports,ui_vue,salescenter_manager) {
	'use strict';

	var RequisiteBlock = {
	  props: ['companyId', 'isNewCompany'],
	  computed: {
	    loc: function loc() {
	      return ui_vue.Vue.getFilteredPhrases('SC_MYCOMPANY_SETTINGS_');
	    }
	  },
	  methods: {
	    openSlider: function openSlider() {
	      var _this = this;

	      if (this.isNewCompany) {
	        var url = '/crm/configs/mycompany/';
	        window.open(url);
	      } else {
	        var _url = '/crm/company/details/' + this.companyId + '/?init_mode=edit';

	        salescenter_manager.Manager.openSlider(_url).then(function () {
	          return _this.onSettings();
	        });
	      }
	    },
	    onSettings: function onSettings() {
	      this.$emit('on-mycompany-requisite-settings');
	    }
	  },
	  template: "   \n\t\t\t<div>\n\t\t\t\t<div class=\"salescenter-company-contacts-text\">{{loc.SC_MYCOMPANY_SETTINGS_COMPANY_REQUISITE_INFO_V2}}</div>\n\t\t\t\t<div class=\"salescenter-company-contacts-text salescenter-company-contacts-text--link\" @click=\"openSlider\">{{loc.SC_MYCOMPANY_SETTINGS_COMPANY_REQUISITE_EDIT}}</div>\n\t\t\t</div>"
	};

	exports.RequisiteBlock = RequisiteBlock;

}((this.BX.Salescenter.Component = this.BX.Salescenter.Component || {}),BX,BX.Salescenter));
//# sourceMappingURL=mycompany-requisite-settings.bundle.js.map
