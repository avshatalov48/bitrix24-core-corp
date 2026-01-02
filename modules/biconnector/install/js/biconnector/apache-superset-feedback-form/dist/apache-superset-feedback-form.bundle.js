/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	class ApacheSupersetFeedbackForm {
	  static requestIntegrationFormOpen() {
	    const settingsCollection = main_core.Extension.getSettings('biconnector.apache-superset-feedback-form');
	    const region = settingsCollection.get('region');
	    const zones = [region];
	    const regionConfigs = {
	      ru: {
	        portalUri: 'https://bitrix24.team',
	        forms: [{
	          zones,
	          id: 3282,
	          lang: 'ru',
	          sec: '8x81lp'
	        }]
	      },
	      by: {
	        portalUri: 'https://belarus.bitrix24.by',
	        forms: [{
	          zones,
	          id: 3,
	          lang: 'ru',
	          sec: 'kde6g5'
	        }]
	      },
	      kz: {
	        portalUri: 'https://teamkz.bitrix24.kz',
	        forms: [{
	          zones,
	          id: 6,
	          lang: 'en',
	          sec: 'gozt5o'
	        }, {
	          zones,
	          id: 4,
	          lang: 'kz',
	          sec: 'id3qfm'
	        }, {
	          zones,
	          id: 2,
	          lang: 'ru',
	          sec: 'po3skw'
	        }]
	      },
	      uz: {
	        portalUri: 'https://team.bitrix24.uz',
	        forms: [{
	          zones,
	          id: 7,
	          lang: 'ru',
	          sec: 'xjkuqu'
	        }]
	      },
	      default: {
	        portalUri: 'https://global.bitrix24.com',
	        forms: [{
	          zones,
	          id: 34,
	          lang: 'en',
	          sec: '940xiq'
	        }, {
	          zones,
	          id: 58,
	          lang: 'br',
	          sec: 'db0u10'
	        }, {
	          zones,
	          id: 82,
	          lang: 'la',
	          sec: 'f5cg69'
	        }, {
	          zones,
	          id: 84,
	          lang: 'pl',
	          sec: 'xaq93h'
	        }, {
	          zones,
	          id: 86,
	          lang: 'tr',
	          sec: '6xbruy'
	        }, {
	          zones,
	          id: 88,
	          lang: 'vn',
	          sec: '8g591b'
	        }, {
	          zones,
	          id: 90,
	          lang: 'it',
	          sec: 'mc95rg'
	        }, {
	          zones,
	          id: 92,
	          lang: 'de',
	          sec: 'uokzr7'
	        }, {
	          zones,
	          id: 94,
	          lang: 'fr',
	          sec: 'gyzkzb'
	        }, {
	          zones,
	          id: 96,
	          lang: 'ms',
	          sec: 'zaetk0'
	        }, {
	          zones,
	          id: 108,
	          lang: 'id',
	          sec: '3gs5vj'
	        }, {
	          zones,
	          id: 98,
	          lang: 'sc',
	          sec: '532jfn'
	        }, {
	          zones,
	          id: 100,
	          lang: 'th',
	          sec: '1q4cis'
	        }, {
	          zones,
	          id: 102,
	          lang: 'ja',
	          sec: 'c84t56'
	        }, {
	          zones,
	          id: 104,
	          lang: 'tc',
	          sec: '6s7a1m'
	        }, {
	          zones,
	          id: 110,
	          lang: 'ar',
	          sec: 'zfkgno'
	        }, {
	          zones,
	          id: 106,
	          lang: 'ru',
	          sec: 'j1h7w4'
	        }, {
	          zones,
	          id: 34,
	          lang: 'ua',
	          sec: '940xiq'
	        }, {
	          zones,
	          id: 34,
	          lang: 'kz',
	          sec: '940xiq'
	        }]
	      }
	    };
	    const {
	      portalUri,
	      forms
	    } = regionConfigs[region] || regionConfigs.default;
	    const defaultForm = forms[0];
	    BX.UI.Feedback.Form.open({
	      id: 'order_dashboard',
	      title: main_core.Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_FEEDBACK_FORM_INTEGRATION_REQUEST_FORM'),
	      portalUri,
	      forms,
	      defaultForm,
	      presets: {
	        from_domain: settingsCollection.get('fromDomain'),
	        source: 'biconnector'
	      }
	    });
	  }
	  static feedbackFormOpen() {
	    const settingsCollection = main_core.Extension.getSettings('biconnector.apache-superset-feedback-form');
	    BX.UI.Feedback.Form.open({
	      id: 'feedback',
	      title: main_core.Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_FEEDBACK_FORM_FEEDBACK_FORM'),
	      forms: [{
	        zones: ['ru', 'kz', 'by'],
	        id: 656,
	        lang: 'ru',
	        sec: 'wxu17b'
	      }, {
	        zones: ['com.br'],
	        id: 658,
	        lang: 'br',
	        sec: 'g0yc31'
	      }, {
	        zones: ['de'],
	        id: 660,
	        lang: 'de',
	        sec: 'woterc'
	      }, {
	        zones: ['es'],
	        id: 664,
	        lang: 'es',
	        sec: '9ri3ml'
	      }],
	      defaultForm: {
	        id: 662,
	        lang: 'en',
	        sec: '3tamv8'
	      },
	      presets: {
	        from_domain: settingsCollection.get('fromDomain'),
	        source: 'biconnector'
	      }
	    });
	  }
	}

	exports.ApacheSupersetFeedbackForm = ApacheSupersetFeedbackForm;

}((this.BX.Biconnector = this.BX.Biconnector || {}),BX));
//# sourceMappingURL=apache-superset-feedback-form.bundle.js.map
