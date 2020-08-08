this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
(function (exports,ui_vue) {
    'use strict';

    var PreviewBlock = {
      props: ['options'],
      computed: {
        loc: function loc() {
          return ui_vue.Vue.getFilteredPhrases('SC_STORE_PREVIEW_');
        },
        getClassPreviewImage: function getClassPreviewImage() {
          return {
            'salescenter-company-contacts-prev': this.options.lang === 'ru',
            'salescenter-company-contacts-prev-en': this.options.lang === 'en',
            'salescenter-company-contacts-prev-ua': this.options.lang === 'ua'
          };
        }
      },
      template: "\n\t\t\t<div class=\"salescenter-company-contacts-item salescenter-company-contacts-item--gray\">\n\t\t\t\t<div class=\"salescenter-company-contacts-item-preview\">\n\t\t\t\t\t<div class=\"salescenter-company-contacts-item-preview-image\">\n\t\t\t\t\t\t<div :class=\"getClassPreviewImage\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>"
    };

    exports.PreviewBlock = PreviewBlock;

}((this.BX.Salescenter.Component = this.BX.Salescenter.Component || {}),BX));
//# sourceMappingURL=store-preview.bundle.js.map
