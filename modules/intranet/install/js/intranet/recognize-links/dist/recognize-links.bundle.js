/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	class RecognizeLinks {
	  constructor() {
	    this.serverName = location.origin;
	    this.netUrl = main_core.Extension.getSettings('intranet.recognize-links').netUrl;
	  }
	  analyze() {
	    const isDesktop = typeof BXDesktopSystem !== 'undefined';
	    const referrer = document.referrer;
	    if (!isDesktop && !BX.browser.IsMobile() && referrer !== '' && !referrer.includes(this.serverName) && (this.netUrl === '' || !referrer.includes(this.netUrl))) {
	      main_core.Runtime.loadExtension('im.public').then(exports => {
	        const {
	          Messenger
	        } = exports;
	        Messenger.desktop.openPage(document.location.href, {
	          skipNativeBrowser: true
	        });
	      });
	    }
	  }
	}
	setTimeout(() => {
	  const isImInstalled = main_core.Extension.getSettings('intranet.recognize-links').isImInstalled;
	  if (isImInstalled) {
	    new RecognizeLinks().analyze();
	  }
	}, 200);

}((this.BX.Intranet = this.BX.Intranet || {}),BX));
//# sourceMappingURL=recognize-links.bundle.js.map
