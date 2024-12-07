/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	var _loadGis = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadGis");
	var _loadScript = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadScript");
	var _createPicker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createPicker");
	var _showPicker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showPicker");
	var _pickerCallback = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pickerCallback");
	var _convertItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("convertItem");
	var _formatDate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("formatDate");
	var _verifyGoogleOAuthToken = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("verifyGoogleOAuthToken");
	class GoogleDrivePicker {
	  constructor(clientId, appId, apiKey, oauthToken, saveCallback) {
	    Object.defineProperty(this, _verifyGoogleOAuthToken, {
	      value: _verifyGoogleOAuthToken2
	    });
	    Object.defineProperty(this, _formatDate, {
	      value: _formatDate2
	    });
	    Object.defineProperty(this, _convertItem, {
	      value: _convertItem2
	    });
	    Object.defineProperty(this, _pickerCallback, {
	      value: _pickerCallback2
	    });
	    Object.defineProperty(this, _createPicker, {
	      value: _createPicker2
	    });
	    Object.defineProperty(this, _loadScript, {
	      value: _loadScript2
	    });
	    Object.defineProperty(this, _loadGis, {
	      value: _loadGis2
	    });
	    this.scopes = main_core.Extension.getSettings('disk.google-drive-picker').get('scopes');
	    this.provider = 'gdrive';
	    Object.defineProperty(this, _showPicker, {
	      writable: true,
	      value: () => {
	        if (this.oauthToken) {
	          const googleViewId = window.google.picker.ViewId.DOCS;
	          const docsView = new window.google.picker.DocsView(googleViewId).setParent('root').setMode(window.google.picker.DocsViewMode.LIST).setIncludeFolders(true);
	          const picker = new window.google.picker.PickerBuilder().enableFeature(window.google.picker.Feature.NAV_HIDDEN).setLocale(main_core.Loc.getMessage('LANGUAGE_ID')).addView(docsView).setOAuthToken(this.oauthToken).setDeveloperKey(this.apiKey).setAppId(this.appId).setCallback(data => babelHelpers.classPrivateFieldLooseBase(this, _pickerCallback)[_pickerCallback](data)).enableFeature(window.google.picker.Feature.MULTISELECT_ENABLED).build();
	          picker.setVisible(true);
	        }
	      }
	    });
	    this.clientId = clientId;
	    this.appId = appId;
	    this.apiKey = apiKey;
	    this.saveCallback = saveCallback;
	    this.oauthToken = oauthToken;
	  }
	  async loadAndShowPicker() {
	    if (!this.oauthToken) {
	      return;
	    }
	    try {
	      await babelHelpers.classPrivateFieldLooseBase(this, _loadGis)[_loadGis]();
	      await babelHelpers.classPrivateFieldLooseBase(this, _verifyGoogleOAuthToken)[_verifyGoogleOAuthToken](this.oauthToken);
	      babelHelpers.classPrivateFieldLooseBase(this, _createPicker)[_createPicker]();
	    } catch (error) {
	      // debug
	      throw new Error(`Error: ${error}`);
	    }
	  }
	}
	async function _loadGis2() {
	  const scripts = ['https://apis.google.com/js/api.js', 'https://accounts.google.com/gsi/client'];
	  await Promise.all(scripts.map(script => babelHelpers.classPrivateFieldLooseBase(this, _loadScript)[_loadScript](script)));
	}
	function _loadScript2(url) {
	  return new Promise((resolve, reject) => {
	    const script = document.createElement('script');
	    script.src = url;
	    script.onload = () => resolve(url);
	    script.onerror = () => reject(new Error(`Failed to load script ${url}`));
	    main_core.Dom.append(script, document.head);
	  });
	}
	function _createPicker2() {
	  window.gapi.load('client:auth2:picker', babelHelpers.classPrivateFieldLooseBase(this, _showPicker)[_showPicker]);
	}
	function _pickerCallback2(data) {
	  if (data.action === window.google.picker.Action.PICKED) {
	    const documents = data.docs.map(doc => babelHelpers.classPrivateFieldLooseBase(this, _convertItem)[_convertItem](doc));
	    this.saveCallback.saveButton(this.provider, '/', documents);
	  }
	  switch (data.action) {
	    case 'loaded':
	      document.body.style.setProperty('overflow', 'hidden');
	      break;
	    case 'cancel':
	    case 'picked':
	      document.body.style.removeProperty('overflow');
	      break;
	  }
	}
	function _convertItem2(document) {
	  const modifyDate = new Date(document.lastEditedUtc);
	  return {
	    id: document.id,
	    name: document.name,
	    type: 'file',
	    size: document.sizeBytes,
	    sizeInt: document.sizeBytes,
	    modifyBy: '',
	    modifyDate: babelHelpers.classPrivateFieldLooseBase(this, _formatDate)[_formatDate](modifyDate),
	    modifyDateInt: modifyDate.getTime(),
	    provider: this.provider
	  };
	}
	function _formatDate2(date) {
	  return `${date.getDay()}.${date.getMonth()}.${date.getFullYear()}`;
	}
	async function _verifyGoogleOAuthToken2(token) {
	  try {
	    const url = `https://oauth2.googleapis.com/tokeninfo?access_token=${token}`;
	    const response = await fetch(url);
	    if (!response.ok) {
	      new Error(`Error verifying token: ${response.statusText}`);
	    }
	    return await response.json();
	  } catch (error) {
	    throw new Error(`Error verifying token: ${error.message}`);
	  }
	}

	exports.GoogleDrivePicker = GoogleDrivePicker;

}((this.BX.Disk = this.BX.Disk || {}),BX));
//# sourceMappingURL=google-drive-picker.bundle.js.map
