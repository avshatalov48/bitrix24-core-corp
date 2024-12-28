/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_core_events,sign_v2_api,ui_uploader_core) {
	'use strict';

	var _uploader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("uploader");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _subscribeOnEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeOnEvents");
	var _importBlank = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("importBlank");
	class BlankImporter extends main_core_events.EventEmitter {
	  constructor(target) {
	    super();
	    Object.defineProperty(this, _importBlank, {
	      value: _importBlank2
	    });
	    Object.defineProperty(this, _subscribeOnEvents, {
	      value: _subscribeOnEvents2
	    });
	    Object.defineProperty(this, _uploader, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Sign.V2.B2e.BlankImporter');
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _uploader)[_uploader] = new ui_uploader_core.Uploader({
	      browseElement: target,
	      autoUpload: false,
	      multiple: false,
	      acceptedFileTypes: '.json'
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeOnEvents)[_subscribeOnEvents]();
	  }
	}
	function _subscribeOnEvents2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _uploader)[_uploader].subscribe('File:onAdd', e => {
	    const uploaderFile = e.getData().file;
	    const reader = new FileReader();
	    reader.onload = event => babelHelpers.classPrivateFieldLooseBase(this, _importBlank)[_importBlank](event.target.result);
	    reader.readAsText(uploaderFile.getBinary());
	  });
	}
	async function _importBlank2(serializedJson) {
	  try {
	    await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].template.importBlank(serializedJson);
	    window.top.BX.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage('SIGN_BLANK_IMPORTER_SUCCESS')
	    });
	    this.emit('onSuccessImport');
	  } catch (e) {
	    console.error(e);
	    window.top.BX.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage('SIGN_BLANK_IMPORTER_FAILURE')
	    });
	  }
	}

	exports.BlankImporter = BlankImporter;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Event,BX.Sign.V2,BX.UI.Uploader));
//# sourceMappingURL=blank-importer.bundle.js.map
