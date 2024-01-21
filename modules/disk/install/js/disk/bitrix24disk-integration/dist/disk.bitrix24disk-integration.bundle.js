this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,im_v2_const,im_v2_lib_desktopApi,main_core,ui_notificationManager) {
	'use strict';

	var _uid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("uid");
	var _canUsePull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("canUsePull");
	var _answerWithPull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("answerWithPull");
	var _answerWithAjax = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("answerWithAjax");
	class Responder {
	  constructor(uid) {
	    Object.defineProperty(this, _answerWithAjax, {
	      value: _answerWithAjax2
	    });
	    Object.defineProperty(this, _answerWithPull, {
	      value: _answerWithPull2
	    });
	    Object.defineProperty(this, _canUsePull, {
	      value: _canUsePull2
	    });
	    Object.defineProperty(this, _uid, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _uid)[_uid] = uid;
	  }
	  answer() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _canUsePull)[_canUsePull]()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _answerWithPull)[_answerWithPull]();
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _answerWithAjax)[_answerWithAjax]();
	    }
	  }
	}
	function _canUsePull2() {
	  return main_core.Reflection.getClass('BX.PULL.isPublishingEnabled') && BX.PULL.isPublishingEnabled() && BX.PULL.isConnected();
	}
	function _answerWithPull2() {
	  BX.PULL.sendMessage([main_core.Loc.getMessage('USER_ID')], 'disk', 'bdisk', {
	    // eslint-disable-next-line no-undef
	    status: BXFileStorage.GetStatus().status,
	    uidRequest: babelHelpers.classPrivateFieldLooseBase(this, _uid)[_uid]
	  });
	}
	function _answerWithAjax2() {
	  void main_core.ajax.runAction('disk.documentService.setStatusWorkWithLocalDocument', {
	    data: {
	      // eslint-disable-next-line no-undef
	      status: BXFileStorage.GetStatus().status,
	      uidRequest: babelHelpers.classPrivateFieldLooseBase(this, _uid)[_uid]
	    }
	  });
	}

	var _objectId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("objectId");
	var _url = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("url");
	var _name = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("name");
	var _handleAppLaunched = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleAppLaunched");
	var _appLaunchedNotifyWasShown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appLaunchedNotifyWasShown");
	class OpenReadOnlyFile {
	  constructor({
	    objectId,
	    url,
	    name
	  }) {
	    Object.defineProperty(this, _objectId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _url, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _name, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _handleAppLaunched, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _appLaunchedNotifyWasShown, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _objectId)[_objectId] = objectId;
	    babelHelpers.classPrivateFieldLooseBase(this, _url)[_url] = url;
	    babelHelpers.classPrivateFieldLooseBase(this, _name)[_name] = name;
	    babelHelpers.classPrivateFieldLooseBase(this, _handleAppLaunched)[_handleAppLaunched] = this.handleAppLaunched.bind(this);
	  }
	  getObjectId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _objectId)[_objectId];
	  }
	  getUrl() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _url)[_url];
	  }
	  getName() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _name)[_name];
	  }
	  subscribeToFileOpen() {
	    void im_v2_lib_desktopApi.DesktopApi.subscribe('BXFileStorageLaunchApp', babelHelpers.classPrivateFieldLooseBase(this, _handleAppLaunched)[_handleAppLaunched]);
	  }
	  unsubscribeToFileOpen() {
	    babelHelpers.classPrivateFieldLooseBase(this, _appLaunchedNotifyWasShown)[_appLaunchedNotifyWasShown] = true;
	    void im_v2_lib_desktopApi.DesktopApi.unsubscribe('BXFileStorageLaunchApp', babelHelpers.classPrivateFieldLooseBase(this, _handleAppLaunched)[_handleAppLaunched]);
	  }
	  showNotification(options) {
	    ui_notificationManager.Notifier.notify(options);
	  }
	  handleAppLaunched(name) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _appLaunchedNotifyWasShown)[_appLaunchedNotifyWasShown]) {
	      return;
	    }
	    this.unsubscribeToFileOpen();
	    const notificationOptions = {
	      id: 'launchApp',
	      title: name,
	      text: main_core.Loc.getMessage('JS_B24DISK_LAUNCH_APP_DESCR')
	    };
	    this.showNotification(notificationOptions);
	  }
	  showOpenNotification() {
	    const notificationOptions = {
	      id: 'openFile',
	      title: this.getName(),
	      text: main_core.Loc.getMessage('JS_B24DISK_FILE_DOWNLOAD_STARTED_DESCR')
	    };
	    this.showNotification(notificationOptions);
	  }
	  exists() {
	    return new Promise((resolve, reject) => {
	      // eslint-disable-next-line no-undef
	      const filePath = BXFileStorage.FindPathByPartOfId(`|f${this.getObjectId()}`);
	      // eslint-disable-next-line no-undef
	      BXFileStorage.FileExist(filePath, exist => {
	        if (exist && filePath) {
	          resolve(filePath);
	        } else {
	          reject();
	        }
	      });
	    });
	  }
	  getDownloadUrl() {
	    return this.getUrl();
	  }
	  run() {
	    this.exists().then(filePath => {
	      // eslint-disable-next-line no-undef
	      BXFileStorage.ObjectOpen(filePath, () => {});
	    }).catch(() => {
	      if (!this.getUrl()) {
	        return;
	      }
	      this.showOpenNotification();
	      this.subscribeToFileOpen();
	      this.openFile();
	    });
	  }
	  openFile() {
	    // eslint-disable-next-line no-undef
	    BXFileStorage.ViewFile(this.getDownloadUrl(), this.getName());
	  }
	}

	var _handleFileUploadFinished = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleFileUploadFinished");
	var _fileUploadFinishedWasShown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fileUploadFinishedWasShown");
	class EditFile extends OpenReadOnlyFile {
	  constructor({
	    objectId,
	    url,
	    name
	  }) {
	    super({
	      objectId,
	      url,
	      name
	    });
	    Object.defineProperty(this, _handleFileUploadFinished, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _fileUploadFinishedWasShown, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _handleFileUploadFinished)[_handleFileUploadFinished] = this.handleFileUploadFinished.bind(this);
	  }
	  getDownloadUrl() {
	    return main_core.Uri.addParam(this.getUrl(), {
	      editIn: 'l',
	      action: 'start'
	    });
	  }
	  getUploadUrl() {
	    return main_core.Uri.addParam(this.getUrl(), {
	      editIn: 'l',
	      action: 'commit',
	      primaryAction: 'commit'
	    });
	  }
	  openFile() {
	    // eslint-disable-next-line no-undef
	    BXFileStorage.EditFile(this.getDownloadUrl(), this.getUploadUrl(), this.getName());
	  }
	  handleFileUploadFinished() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _fileUploadFinishedWasShown)[_fileUploadFinishedWasShown]) {
	      return;
	    }
	    this.unsubscribeToFinishUpload();
	    const notificationOptions = {
	      id: 'uploadFinished',
	      title: this.getName(),
	      text: main_core.Loc.getMessage('JS_B24DISK_FILE_UPLOAD_FINISHED')
	    };
	    this.showNotification(notificationOptions);
	  }
	  subscribeToFinishUpload() {
	    void im_v2_lib_desktopApi.DesktopApi.subscribe('BXFileStorageSyncStatusFinalFile', babelHelpers.classPrivateFieldLooseBase(this, _handleFileUploadFinished)[_handleFileUploadFinished]);
	  }
	  unsubscribeToFinishUpload() {
	    babelHelpers.classPrivateFieldLooseBase(this, _fileUploadFinishedWasShown)[_fileUploadFinishedWasShown] = true;
	    void im_v2_lib_desktopApi.DesktopApi.unsubscribe('BXFileStorageSyncStatusFinalFile', babelHelpers.classPrivateFieldLooseBase(this, _handleFileUploadFinished)[_handleFileUploadFinished]);
	  }
	}

	const Command = {
	  openFile: 'v2openFile',
	  viewFile: 'v2viewFile'
	};

	var _subscribeToBxProtocolEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToBxProtocolEvent");
	class BxLinkHandler {
	  static init() {
	    return new BxLinkHandler();
	  }
	  constructor() {
	    Object.defineProperty(this, _subscribeToBxProtocolEvent, {
	      value: _subscribeToBxProtocolEvent2
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeToBxProtocolEvent)[_subscribeToBxProtocolEvent]();
	  }
	}
	function _subscribeToBxProtocolEvent2() {
	  im_v2_lib_desktopApi.DesktopApi.subscribe(im_v2_const.EventType.desktop.onBxLink, (command, rawParams) => {
	    const params = rawParams != null ? rawParams : {};
	    Object.entries(params).forEach(([key, value]) => {
	      params[key] = decodeURIComponent(value);
	    });
	    const {
	      objectId,
	      url,
	      name,
	      uidRequest
	    } = params;
	    if (!objectId && !url) {
	      return;
	    }
	    if (uidRequest) {
	      new Responder(uidRequest).answer();
	    }
	    if (command === Command.openFile) {
	      const editFileScenario = new EditFile({
	        objectId,
	        url,
	        name
	      });
	      editFileScenario.run();
	    } else if (command === Command.viewFile) {
	      const readOnlyFileScenario = new OpenReadOnlyFile({
	        objectId,
	        url,
	        name
	      });
	      readOnlyFileScenario.run();
	    }
	  });
	}

	exports.BxLinkHandler = BxLinkHandler;

}((this.BX.Disk.Bitrix24Disk = this.BX.Disk.Bitrix24Disk || {}),BX.Messenger.v2.Const,BX.Messenger.v2.Lib,BX,BX.UI.NotificationManager));
//# sourceMappingURL=disk.bitrix24disk-integration.bundle.js.map
