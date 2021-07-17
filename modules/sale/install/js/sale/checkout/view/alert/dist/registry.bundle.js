this.BX = this.BX || {};
this.BX.Sale = this.BX.Sale || {};
this.BX.Sale.Checkout = this.BX.Sale.Checkout || {};
(function (exports,ui_vue) {
    'use strict';

    ui_vue.Vue.component('sale-checkout-view-alert', {
      props: ['error'],
      // language=Vue
      template: "\n\t\t<div class=\"checkout-form-alert\">\n\t\t\t<div class=\"checkout-form-alert-icon\"></div>\n\t\t\t<span class=\"text-danger\">{{this.error.message}}</span>\n\t\t</div>\n\t"
    });

    ui_vue.Vue.component('sale-checkout-view-alert-list', {
      props: ['errors'],
      // language=Vue
      template: "\n\t\t<div v-if=\"errors.length>0\">\n          <template v-for=\"(error) in errors\" >\n            <sale-checkout-view-alert :error=\"error\"/>\n          </template>\n        </div>\n\t"
    });

}((this.BX.Sale.Checkout.View = this.BX.Sale.Checkout.View || {}),BX));
//# sourceMappingURL=registry.bundle.js.map
