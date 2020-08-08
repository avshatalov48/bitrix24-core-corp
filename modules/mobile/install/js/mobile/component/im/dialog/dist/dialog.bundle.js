this.BX = this.BX || {};
this.BX.Messenger = this.BX.Messenger || {};
(function (exports,pull_components_status,main_date,mobile_pull_client,im_model,im_controller,im_provider_pull,im_provider_rest,im_tools_localstorage,im_tools_timer,im_tools_logger,im_const,im_utils,im_component_dialog,im_component_quotepanel,ui_vue_vuex,ui_vue) {
	'use strict';

	/**
	 * Bitrix Mobile Dialog
	 * Dialog Rest answers (Rest Answer Handler)
	 *
	 * @package bitrix
	 * @subpackage mobile
	 * @copyright 2001-2019 Bitrix
	 */
	var MobileRestAnswerHandler =
	/*#__PURE__*/
	function (_BaseRestAnswerHandle) {
	  babelHelpers.inherits(MobileRestAnswerHandler, _BaseRestAnswerHandle);

	  function MobileRestAnswerHandler(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, MobileRestAnswerHandler);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MobileRestAnswerHandler).call(this, params));

	    if (babelHelpers.typeof(params.context) === 'object' && params.context) {
	      _this.context = params.context;
	    }

	    return _this;
	  }

	  babelHelpers.createClass(MobileRestAnswerHandler, [{
	    key: "handleImChatGetSuccess",
	    value: function handleImChatGetSuccess(data) {
	      this.store.commit('application/set', {
	        dialog: {
	          chatId: data.id,
	          dialogId: data.dialog_id,
	          diskFolderId: data.disk_folder_id
	        }
	      });
	      this.context.redrawHeader();
	    }
	  }, {
	    key: "handleImChatGetError",
	    value: function handleImChatGetError(error) {
	      if (error.ex.error === 'ACCESS_ERROR') {
	        app.closeController();
	      }
	    }
	  }, {
	    key: "handleMobileBrowserConstGetSuccess",
	    value: function handleMobileBrowserConstGetSuccess(data) {
	      this.store.commit('application/set', {
	        disk: {
	          enabled: true,
	          maxFileSize: data.phpUploadMaxFilesize
	        }
	      });
	      this.context.addLocalize(data);
	      BX.message(data);
	      im_tools_localstorage.LocalStorage.set(this.controller.getSiteId(), 0, 'serverVariables', data || {});
	    }
	  }, {
	    key: "handleImDialogMessagesGetInitSuccess",
	    value: function handleImDialogMessagesGetInitSuccess() {
	      this.controller.emit(im_const.EventType.dialog.sendReadMessages);
	    }
	  }, {
	    key: "handleImMessageAddSuccess",
	    value: function handleImMessageAddSuccess(messageId, message) {
	      this.context.messagesQueue = this.context.messagesQueue.filter(function (el) {
	        return el.id !== message.id;
	      });
	    }
	  }, {
	    key: "handleImMessageAddError",
	    value: function handleImMessageAddError(error, message) {
	      this.context.messagesQueue = this.context.messagesQueue.filter(function (el) {
	        return el.id !== message.id;
	      });
	    }
	  }, {
	    key: "handleImDiskFileCommitSuccess",
	    value: function handleImDiskFileCommitSuccess(result, message) {
	      this.context.messagesQueue = this.context.messagesQueue.filter(function (el) {
	        return el.id !== message.id;
	      });
	    }
	  }]);
	  return MobileRestAnswerHandler;
	}(im_provider_rest.BaseRestAnswerHandler);

	/**
	 * Bitrix im dialog mobile
	 * Dialog runtime class
	 *
	 * @package bitrix
	 * @subpackage mobile
	 * @copyright 2001-2019 Bitrix
	 */

	var Dialog =
	/*#__PURE__*/
	function () {
	  /* region 01. Initialize and store data */
	  function Dialog() {
	    var _this = this;
	    babelHelpers.classCallCheck(this, Dialog);
	    this.ready = true;
	    this.offline = false;
	    this.host = this.getHost();
	    this.inited = false;
	    this.restClient = BX.rest;
	    this.customData = [];
	    this.localize = babelHelpers.objectSpread({}, BX.message);
	    this.subscribers = {};
	    this.dateFormat = null;
	    this.messagesQueue = [];
	    this.configRequestXhr = null;
	    this.windowFocused = true;
	    this.rootNode = document.getElementById('messenger-root');
	    this.template = null;
	    this.timer = new im_tools_timer.Timer();
	    window.addEventListener('orientationchange', function () {
	      if (!_this.store) {
	        return;
	      }

	      _this.store.commit('application/set', {
	        device: {
	          orientation: im_utils.Utils.device.getOrientation()
	        }
	      });

	      if (_this.store.state.application.device.type === im_const.DeviceType.mobile && _this.store.state.application.device.orientation === im_const.DeviceOrientation.horizontal) {
	        document.activeElement.blur();
	      }
	    }); // todo change to dynamic storage (LocalStorage web, PageParams for mobile)

	    var serverVariables = im_tools_localstorage.LocalStorage.get(this.getSiteId(), 0, 'serverVariables', false);

	    if (serverVariables) {
	      this.addLocalize(serverVariables);
	    } //alert('Pause: open console for debug');


	    BX.componentParameters.init().then(function (result) {
	      return _this.initMobileSettings(result);
	    }).then(function (result) {
	      return _this.initStorage(result);
	    }).then(function (result) {
	      return _this.initComponent(result);
	    }).then(function (result) {
	      return _this.requestData(result);
	    }).then(function (result) {
	      return _this.initEnvironment(result);
	    }).then(function (result) {
	      return _this.initMobileEnvironment(result);
	    }).then(function (result) {
	      return _this.initPullClient(result);
	    });
	  }

	  babelHelpers.createClass(Dialog, [{
	    key: "initMobileSettings",
	    value: function initMobileSettings(data) {
	      console.log('1. initMobileSettings');
	      return new Promise(function (resolve, reject) {
	        ApplicationStorage.getObject('settings.chat', {
	          quoteEnable: ChatPerformance.isGestureQuoteSupported(),
	          quoteFromRight: false,
	          autoplayVideo: ChatPerformance.isAutoPlayVideoSupported(),
	          backgroundType: 'LIGHT_GRAY'
	        }).then(function (value) {
	          data.OPTIONS = value;
	          resolve(data);
	        });
	      });
	    }
	  }, {
	    key: "initStorage",
	    value: function initStorage(data) {
	      console.log('2. initStorage');
	      this.controller = new im_controller.ApplicationController();
	      this.localize['SITE_DIR'] = data.SITE_DIR;
	      this.storedEvents = data.STORED_EVENTS || [];
	      var applicationVariables = {
	        common: {
	          host: this.host,
	          userId: data.USER_ID,
	          siteId: data.SITE_ID,
	          languageId: data.LANGUAGE_ID
	        },
	        device: {
	          type: im_utils.Utils.device.isMobile() ? im_const.DeviceType.mobile : im_const.DeviceType.desktop,
	          orientation: im_utils.Utils.device.getOrientation()
	        },
	        dialog: {
	          dialogId: data.DIALOG_ID,
	          messageLimit: this.controller.getDefaultMessageLimit(),
	          enableReadMessages: false
	        },
	        options: {
	          quoteEnable: data.OPTIONS.quoteEnable,
	          quoteFromRight: data.OPTIONS.quoteFromRight,
	          autoplayVideo: data.OPTIONS.autoplayVideo,
	          darkBackground: ChatDialogBackground && ChatDialogBackground[data.OPTIONS.backgroundType] && ChatDialogBackground[data.OPTIONS.backgroundType].dark
	        }
	      };
	      return new ui_vue_vuex.VuexBuilder().addModel(im_model.ApplicationModel.create().useDatabase(false).setVariables(applicationVariables)).addModel(im_model.MessagesModel.create().setVariables({
	        host: this.host
	      })).addModel(im_model.DialoguesModel.create().setVariables({
	        host: this.host
	      })).addModel(im_model.FilesModel.create().setVariables({
	        host: this.host,
	        default: {
	          name: this.getLocalize('IM_MESSENGER_MESSAGE_FILE_DELETED')
	        }
	      })).addModel(im_model.UsersModel.create().setVariables({
	        host: this.host,
	        default: {
	          name: this.getLocalize('IM_MESSENGER_MESSAGE_USER_ANONYM')
	        }
	      })).setDatabaseConfig({
	        name: 'mobile/im',
	        type: ui_vue_vuex.VuexBuilder.DatabaseType.jnSharedStorage,
	        siteId: data.SITE_ID,
	        userId: data.USER_ID
	      }).build();
	    }
	  }, {
	    key: "initComponent",
	    value: function initComponent(result) {
	      var _this2 = this;

	      console.log('3. initComponent');
	      this.store = result.store;
	      this.store.subscribe(function (mutation) {
	        return _this2.eventStoreInteraction(mutation);
	      });
	      this.storeCollector = result.builder;
	      this.controller.setStore(this.store);
	      this.controller.setRestClient(this.restClient);
	      this.controller.setPrepareFilesBeforeSaveFunction(this.prepareFileData.bind(this));
	      this.imRestAnswer = im_provider_rest.ImRestAnswerHandler.create({
	        store: this.store,
	        controller: this.controller
	      });
	      this.mobileRestAnswer = MobileRestAnswerHandler.create({
	        store: this.store,
	        controller: this.controller,
	        context: this
	      });
	      var dialog = this.store.getters['dialogues/get'](this.controller.getDialogId());

	      if (dialog) {
	        this.store.commit('application/set', {
	          dialog: {
	            chatId: dialog.chatId,
	            diskFolderId: dialog.diskFolderId || 0
	          }
	        });
	      }

	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initEnvironment",
	    value: function initEnvironment(result) {
	      var _this3 = this;

	      var executionTime = new Date();
	      this.template = this.attachTemplate();
	      console.log('5. initEnvironment', +new Date() - executionTime + 'ms');
	      this.controller.setTemplateEngine(this.template);
	      this.setTextareaMessage = im_utils.Utils.debounce(this.controller.setTextareaMessage, 300, this.controller);
	      window.addEventListener('orientationchange', function () {
	        if (!_this3.store) {
	          return;
	        }

	        _this3.store.commit('application/set', {
	          device: {
	            orientation: im_utils.Utils.device.getOrientation()
	          }
	        });
	      });
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initMobileEnvironment",
	    value: function initMobileEnvironment(result) {
	      var _this4 = this;

	      console.log('6. initMobileEnvironment');
	      BXMobileApp.UI.Page.Scroll.setEnabled(false);
	      BXMobileApp.UI.Page.captureKeyboardEvents(true);
	      BX.addCustomEvent("onKeyboardWillShow", function () {
	        _this4.store.dispatch('application/set', {
	          mobile: {
	            keyboardShow: true
	          }
	        });
	      });
	      BX.addCustomEvent("onKeyboardDidShow", function () {
	        _this4.controller.emit('EventType.dialog.scrollToBottom', {
	          duration: 300,
	          cancelIfScrollChange: true
	        });
	      });
	      BX.addCustomEvent("onKeyboardWillHide", function () {
	        clearInterval(_this4.keyboardOpeningInterval);

	        _this4.store.dispatch('application/set', {
	          mobile: {
	            keyboardShow: false
	          }
	        });
	      });

	      var checkWindowFocused = function checkWindowFocused() {
	        BXMobileApp.UI.Page.isVisible({
	          callback: function callback(data) {
	            _this4.windowFocused = data.status === 'visible';

	            if (_this4.windowFocused) {
	              ui_vue.Vue.event.$emit('bitrixmobile:controller:focus');
	            } else {
	              ui_vue.Vue.event.$emit('bitrixmobile:controller:blur');
	            }
	          }
	        });
	      };

	      BX.addCustomEvent("onAppActive", function () {
	        checkWindowFocused();
	        BXMobileApp.UI.Page.isVisible({
	          callback: function callback(data) {
	            if (data.status !== 'visible') {
	              return false;
	            }

	            _this4.getDialogUnread().then(function () {
	              _this4.processSendMessages();

	              _this4.controller.emit(im_const.EventType.dialog.sendReadMessages);
	            }).catch(function () {
	              _this4.processSendMessages();
	            });
	          }
	        });
	      });
	      BX.addCustomEvent("onAppPaused", function () {
	        _this4.windowFocused = false;
	        ui_vue.Vue.event.$emit('bitrixmobile:controller:blur');
	      });
	      BX.addCustomEvent("onOpenPageAfter", checkWindowFocused);
	      BX.addCustomEvent("onHidePageBefore", function () {
	        _this4.windowFocused = false;
	        ui_vue.Vue.event.$emit('bitrixmobile:controller:blur');
	      });
	      BXMobileApp.addCustomEvent("chatbackground::task::status::success", function (params) {
	        var action = params.taskId.toString().split('|')[0];

	        _this4.executeBackgroundTaskSuccess(action, params);
	      });
	      BXMobileApp.addCustomEvent("chatbackground::task::status::failure", function (params) {
	        var action = params.taskId.toString().split('|')[0];

	        _this4.executeBackgroundTaskFailure(action, params);
	      });
	      BXMobileApp.addCustomEvent("chatrecent::push::get", function (params) {
	        if (_this4.pullCommandHandler) {
	          params.optionImportant = true;

	          _this4.pullCommandHandler.handleMessageAdd(params, {});
	        }
	      });
	      BXMobileApp.UI.Page.TextPanel.setParams(this.getKeyboardParams());
	      this.changeChatKeyboardStatus();
	      BX.MobileUploadProvider.setListener(this.executeUploaderEvent.bind(this));
	      this.fileUpdateProgress = im_utils.Utils.throttle(function (chatId, fileId, progress, size) {
	        _this4.store.dispatch('files/update', {
	          chatId: chatId,
	          id: fileId,
	          fields: {
	            size: size,
	            progress: progress
	          }
	        });
	      }, 500);

	      if (im_utils.Utils.dialog.isChatId(this.controller.getDialogId())) {
	        var type = this.controller.getDialogData().type;

	        if (type !== im_const.DialogType.call) {
	          app.exec("setRightButtons", {
	            items: [{
	              type: "user_plus",
	              callback: function callback() {
	                fabric.Answers.sendCustomEvent("vueChatAddUserButton", {});

	                _this4.openAddUserDialog();
	              }
	            }]
	          });
	        }
	      }

	      if (!im_utils.Utils.dialog.isChatId(this.controller.getDialogId())) {
	        this.userShowWorkPosition = true;
	        setTimeout(function () {
	          _this4.userShowWorkPosition = false;

	          _this4.redrawHeader();
	        }, 1500);
	        setInterval(function () {
	          _this4.redrawHeader();
	        }, 60000);
	      }

	      this.redrawHeader();
	      this.widgetCache = new ChatWidgetCache(this.controller.getUserId(), this.controller.getLanguageId());
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initPullClient",
	    value: function initPullClient() {
	      var _this5 = this;

	      console.log('7. initPullClient');
	      mobile_pull_client.PULL.subscribe(this.pullCommandHandler = new im_provider_pull.ImPullCommandHandler({
	        store: this.store,
	        controller: this.controller
	      }));

	      if (this.storedEvents && this.storedEvents.length > 0) {
	        setTimeout(function () {
	          _this5.storedEvents = _this5.storedEvents.filter(function (event) {
	            BX.onCustomEvent('chatrecent::push::get', [event]);
	            return false;
	          });
	        }, 50);
	      }

	      mobile_pull_client.PULL.subscribe({
	        type: BX.PullClient.SubscriptionType.Server,
	        moduleId: 'im',
	        command: 'chatUserLeave',
	        callback: function callback(params) {
	          if (params.userId === _this5.controller.getUserId() && params.dialogId === _this5.controller.getDialogId()) {
	            app.closeController();
	          }
	        }
	      });
	      mobile_pull_client.PULL.subscribe({
	        type: mobile_pull_client.PullClient.SubscriptionType.Status,
	        callback: this.eventStatusInteraction.bind(this)
	      });

	      if (!im_utils.Utils.dialog.isChatId(this.controller.getDialogId())) {
	        mobile_pull_client.PULL.subscribe({
	          type: mobile_pull_client.PullClient.SubscriptionType.Online,
	          callback: this.eventOnlineInteraction.bind(this)
	        });
	      }

	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "requestData",
	    value: function requestData() {
	      var _query,
	          _this6 = this;

	      console.log('4. requestData');

	      if (this.requestDataSend) {
	        return true;
	      }

	      this.timer.start('data', 'load', .5, function () {
	        console.warn("ChatDialog.requestData: slow connection show progress icon");
	        app.titleAction("setParams", {
	          useProgress: true,
	          useLetterImage: false
	        });
	      });
	      this.requestDataSend = true;
	      var query = (_query = {}, babelHelpers.defineProperty(_query, im_const.RestMethodHandler.mobileBrowserConstGet, [im_const.RestMethod.mobileBrowserConstGet, {}]), babelHelpers.defineProperty(_query, im_const.RestMethodHandler.imChatGet, [im_const.RestMethod.imChatGet, {
	        dialog_id: this.controller.getDialogId()
	      }]), babelHelpers.defineProperty(_query, im_const.RestMethodHandler.imDialogMessagesGetInit, [im_const.RestMethod.imDialogMessagesGet, {
	        dialog_id: this.controller.getDialogId(),
	        limit: this.controller.getRequestMessageLimit(),
	        convert_text: 'Y'
	      }]), _query);

	      if (im_utils.Utils.dialog.isChatId(this.controller.getDialogId())) {
	        query[im_const.RestMethodHandler.imUserGet] = [im_const.RestMethod.imUserGet, {}];
	      } else {
	        query[im_const.RestMethodHandler.imUserListGet] = [im_const.RestMethod.imUserListGet, {
	          id: [this.controller.getUserId(), this.controller.getDialogId()]
	        }];
	      }

	      this.restClient.callBatch(query, function (response) {
	        if (!response) {
	          _this6.requestDataSend = false;

	          _this6.setError('EMPTY_RESPONSE', 'Server returned an empty response.');

	          return false;
	        }

	        var constGet = response[im_const.RestMethodHandler.mobileBrowserConstGet];

	        if (constGet.error()) ; else {
	          _this6.executeRestAnswer(im_const.RestMethodHandler.mobileBrowserConstGet, constGet);
	        }

	        var userGet = response[im_const.RestMethodHandler.imUserGet];

	        if (userGet && !userGet.error()) {
	          _this6.executeRestAnswer(im_const.RestMethodHandler.imUserGet, userGet);
	        }

	        var userListGet = response[im_const.RestMethodHandler.imUserListGet];

	        if (userListGet && !userListGet.error()) {
	          _this6.executeRestAnswer(im_const.RestMethodHandler.imUserListGet, userListGet);
	        }

	        var chatGetResult = response[im_const.RestMethodHandler.imChatGet];

	        _this6.executeRestAnswer(im_const.RestMethodHandler.imChatGet, chatGetResult);

	        var dialogMessagesGetResult = response[im_const.RestMethodHandler.imDialogMessagesGetInit];

	        if (dialogMessagesGetResult.error()) ; else {
	          app.titleAction("setParams", {
	            useProgress: false,
	            useLetterImage: true
	          });

	          _this6.timer.stop('data', 'load', true);

	          _this6.store.dispatch('dialogues/saveDialog', {
	            dialogId: _this6.controller.getDialogId(),
	            chatId: _this6.controller.getChatId()
	          });

	          if (_this6.pullCommandHandler) {
	            _this6.pullCommandHandler.option.skip = false;
	          }

	          _this6.store.dispatch('application/set', {
	            dialog: {
	              enableReadMessages: true
	            }
	          }).then(function () {
	            _this6.executeRestAnswer(im_const.RestMethodHandler.imDialogMessagesGetInit, dialogMessagesGetResult);
	          });

	          _this6.processSendMessages();
	        }

	        _this6.requestDataSend = false;
	      }, false, false, im_utils.Utils.getLogTrackingParams({
	        name: 'mobile.im.dialog',
	        dialog: this.controller.getDialogData()
	      }));
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "executeRestAnswer",
	    value: function executeRestAnswer(command, result, extra) {
	      console.warn(command, result, extra);
	      this.imRestAnswer.execute(command, result, extra);
	      this.mobileRestAnswer.execute(command, result, extra);
	    }
	  }, {
	    key: "executeUploaderEvent",
	    value: function executeUploaderEvent(eventName, eventData, taskId) {
	      if (eventName !== BX.MobileUploaderConst.FILE_UPLOAD_PROGRESS) {
	        console.log("ChatDialog.disk.eventRouter: ", eventName, taskId, eventData);
	      }

	      if (eventName === BX.MobileUploaderConst.FILE_UPLOAD_PROGRESS) {
	        if (eventData.percent > 95) {
	          eventData.percent = 95;
	        }

	        this.fileUpdateProgress(eventData.file.params.chatId, eventData.file.params.file.id, eventData.percent, eventData.byteTotal);
	      } else if (eventName === BX.MobileUploaderConst.FILE_CREATED) {
	        if (eventData.result.status === 'error') {
	          this.fileError(eventData.file.params.chatId, eventData.file.params.file.id, eventData.file.params.id);
	          console.error('File upload error', eventData.result.errors[0].message);
	        } else {
	          this.store.dispatch('files/update', {
	            chatId: eventData.file.params.chatId,
	            id: eventData.file.params.file.id,
	            fields: {
	              status: im_const.FileStatus.wait,
	              progress: 95
	            }
	          });
	        }
	      } else if (eventName === 'onimdiskmessageaddsuccess') {
	        console.info('ChatDialog.disk.eventRouter: DISK_MESSAGE_ADD_SUCCESS: ', eventData, taskId);
	        var file = eventData.result.FILES['upload' + eventData.result.DISK_ID[0]];
	        this.store.dispatch('files/update', {
	          chatId: eventData.file.params.chatId,
	          id: eventData.file.params.file.id,
	          fields: {
	            status: im_const.FileStatus.upload,
	            progress: 100,
	            id: file.id,
	            size: file.size,
	            urlDownload: file.urlDownload,
	            urlPreview: file.urlPreview,
	            urlShow: file.urlShow
	          }
	        });
	      } else if (eventName === 'onimdiskmessageaddfail') {
	        console.error('ChatDialog.disk.eventRouter: DISK_MESSAGE_ADD_FAIL: ', eventData, taskId);
	        this.fileError(eventData.file.params.chatId, eventData.file.params.file.id, eventData.file.params.id);
	      } else if (eventName === BX.MobileUploaderConst.TASK_CANCELLED || eventName === BX.MobileUploaderConst.TASK_NOT_FOUND) {
	        this.cancelUploadFile(eventData.file.params.file.id);
	      } else if (eventName === BX.MobileUploaderConst.FILE_CREATED_FAILED || eventName === BX.MobileUploaderConst.FILE_UPLOAD_FAILED || eventName === BX.MobileUploaderConst.FILE_READ_ERROR || eventName === BX.MobileUploaderConst.TASK_STARTED_FAILED) {
	        console.error('ChatDialog.disk.eventRouter: ', eventName, eventData, taskId);
	        this.fileError(eventData.file.params.chatId, eventData.file.params.file.id, eventData.file.params.id);
	      }

	      return true;
	    }
	  }, {
	    key: "prepareFileData",
	    value: function prepareFileData(files) {
	      var prepareFunction = function prepareFunction(file) {
	        if (file.urlPreview && file.urlPreview.startsWith('file://')) {
	          file.urlPreview = 'bx' + file.urlPreview;
	        }

	        if (file.urlShow && file.urlShow.startsWith('file://')) {
	          file.urlShow = 'bx' + file.urlShow;
	        }

	        if (file.type !== im_const.FileType.image) {
	          return file;
	        }

	        if (file.urlPreview) {
	          if (file.urlPreview.startsWith('/')) {
	            file.urlPreview = currentDomain + file.urlPreview;
	          }

	          file.urlPreview = file.urlPreview.replace('http://', 'bxhttp://').replace('https://', 'bxhttps://');
	        }

	        if (file.urlShow) {
	          if (file.urlShow.startsWith('/')) {
	            file.urlShow = currentDomain + file.urlShow;
	          }

	          file.urlShow = file.urlShow.replace('http://', 'bxhttp://').replace('https://', 'bxhttps://');
	        }

	        return file;
	      };

	      if (files instanceof Array) {
	        return files.map(function (file) {
	          return prepareFunction(file);
	        });
	      } else {
	        return prepareFunction(files);
	      }
	    }
	    /* endregion 01. Initialize and store data */

	    /* region 02. Mobile environment methods */

	  }, {
	    key: "redrawHeader",
	    value: function redrawHeader() {
	      var _this7 = this;

	      var headerProperties;

	      if (im_utils.Utils.dialog.isChatId(this.controller.getDialogId())) {
	        headerProperties = this.getChatHeaderParams();
	        this.changeChatKeyboardStatus();
	      } else {
	        headerProperties = this.getUserHeaderParams();
	        this.setCallMenu();
	      }

	      if (!headerProperties) {
	        return false;
	      }

	      if (!this.headerMenuInited) {
	        //BXMobileApp.UI.Page.TopBar.title.setUseLetterImage();
	        BXMobileApp.UI.Page.TopBar.title.params.useLetterImage = true; // TODO remove this

	        BXMobileApp.UI.Page.TopBar.title.setCallback(function () {
	          return _this7.openHeaderMenu();
	        });
	        this.headerMenuInited = true;
	      }

	      if (headerProperties.name) {
	        BXMobileApp.UI.Page.TopBar.title.setText(headerProperties.name);
	      }

	      if (headerProperties.desc) {
	        BXMobileApp.UI.Page.TopBar.title.setDetailText(headerProperties.desc);
	      }

	      if (headerProperties.avatar) {
	        BXMobileApp.UI.Page.TopBar.title.setImage(headerProperties.avatar);
	      } else if (headerProperties.color) {
	        //BXMobileApp.UI.Page.TopBar.title.setImageColor(dialog.color);
	        BXMobileApp.UI.Page.TopBar.title.params.imageColor = headerProperties.color;
	      }

	      return true;
	    }
	  }, {
	    key: "getUserHeaderParams",
	    value: function getUserHeaderParams() {
	      var user = this.store.getters['users/get'](this.controller.getDialogId());

	      if (!user || !user.init) {
	        return false;
	      }

	      var result = {
	        'name': null,
	        'desc': null,
	        'avatar': null,
	        'color': null
	      };

	      if (user.avatar) {
	        result.avatar = user.avatar;
	      } else {
	        result.color = user.color;
	      }

	      result.name = user.name;
	      var showLastDate = false;

	      if (!this.userShowWorkPosition && user.lastActivityDate) {
	        showLastDate = im_utils.Utils.user.getLastDateText(user, this.localize);
	      }

	      if (showLastDate) {
	        result.desc = showLastDate;
	      } else {
	        if (user.workPosition) {
	          result.desc = user.workPosition;
	        } else {
	          result.desc = this.localize['MOBILE_HEADER_MENU_CHAT_TYPE_USER'];
	        }
	      }

	      return result;
	    }
	  }, {
	    key: "getChatHeaderParams",
	    value: function getChatHeaderParams() {
	      var dialog = this.store.getters['dialogues/get'](this.controller.getDialogId());

	      if (!dialog || !dialog.init) {
	        return false;
	      }

	      var result = {
	        'name': null,
	        'desc': null,
	        'avatar': null,
	        'color': null
	      };

	      if (dialog.avatar) {
	        result.avatar = dialog.avatar;
	      } else {
	        result.color = dialog.color;
	      }

	      result.name = dialog.name;
	      var chatTypeTitle = this.localize['MOBILE_HEADER_MENU_CHAT_TYPE_CHAT'];

	      if (this.localize['MOBILE_HEADER_MENU_CHAT_TYPE_' + dialog.type.toUpperCase()]) {
	        chatTypeTitle = this.localize['MOBILE_HEADER_MENU_CHAT_TYPE_' + dialog.type.toUpperCase()];
	      }

	      result.desc = chatTypeTitle;
	      return result;
	    }
	  }, {
	    key: "changeChatKeyboardStatus",
	    value: function changeChatKeyboardStatus() {
	      var dialog = this.store.getters['dialogues/get'](this.controller.getDialogId());

	      if (!dialog || !dialog.init) {
	        BXMobileApp.UI.Page.TextPanel.show();
	        return true;
	      }

	      var keyboardShow = true;

	      if (dialog.type === 'announcement' && !dialog.managerList.includes(this.controller.getUserId())) {
	        keyboardShow = false;
	      }

	      if (typeof this.keyboardShowFlag !== 'undefined' && (this.keyboardShowFlag && keyboardShow || !this.keyboardShowFlag && !keyboardShow)) {
	        return this.keyboardShowFlag;
	      }

	      if (keyboardShow) {
	        BXMobileApp.UI.Page.TextPanel.show();
	        this.keyboardShowFlag = true;
	      } else {
	        BXMobileApp.UI.Page.TextPanel.hide();
	        this.keyboardShowFlag = false;
	      }

	      return this.keyboardShowFlag;
	    }
	  }, {
	    key: "setCallMenu",
	    value: function setCallMenu() {
	      var _this8 = this;

	      if (this.callMenuSetted) {
	        return true;
	      }

	      var userData = this.store.getters['users/get'](this.controller.getDialogId(), true);

	      if (!userData.init) {
	        return false;
	      }

	      if (this.controller.getUserId() === parseInt(this.controller.getDialogId()) || userData.bot || userData.network) {
	        app.exec("setRightButtons", {
	          items: []
	        });
	        this.callMenuSetted = true;
	        return true;
	      }

	      app.exec("setRightButtons", {
	        items: [{
	          type: "call_audio",
	          callback: function callback() {
	            _this8.openCallMenu();
	          }
	        }, {
	          type: "call_video",
	          callback: function callback() {
	            fabric.Answers.sendCustomEvent("vueChatCallVideoButton", {});
	            var userData = {};
	            userData[_this8.controller.getDialogId()] = _this8.store.getters['users/get'](_this8.controller.getDialogId(), true);
	            BXMobileApp.Events.postToComponent("onCallInvite", {
	              userId: _this8.controller.getDialogId(),
	              video: true,
	              userData: userData
	            }, "calls");
	          }
	        }]
	      });
	      this.callMenuSetted = true;
	      return true;
	    }
	  }, {
	    key: "openUserList",
	    value: function openUserList() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var _params$users = params.users,
	          users = _params$users === void 0 ? false : _params$users,
	          _params$title = params.title,
	          title = _params$title === void 0 ? '' : _params$title,
	          _params$listType = params.listType,
	          listType = _params$listType === void 0 ? 'LIST' : _params$listType,
	          _params$backdrop = params.backdrop,
	          backdrop = _params$backdrop === void 0 ? true : _params$backdrop;
	      var settings = {
	        title: title,
	        objectName: "ChatUserListInterface"
	      };

	      if (backdrop) {
	        settings.backdrop = {};
	      }

	      app.exec("openComponent", {
	        name: "JSStackComponent",
	        componentCode: 'im.dialog.list',
	        scriptPath: "/mobileapp/jn/im.chat.user.list/?version=" + BX.componentParameters.get('WIDGET_CHAT_USERS_VERSION', '1.0.0'),
	        params: {
	          "DIALOG_ID": this.controller.getDialogId(),
	          "DIALOG_OWNER_ID": this.controller.getDialogData().ownerId,
	          "USER_ID": this.controller.getUserId(),
	          "LIST_TYPE": listType,
	          "USERS": users,
	          "IS_BACKDROP": true
	        },
	        rootWidget: {
	          name: "list",
	          settings: settings
	        }
	      }, false);
	    }
	  }, {
	    key: "openCallMenu",
	    value: function openCallMenu() {
	      var _this9 = this;

	      fabric.Answers.sendCustomEvent("vueChatCallAudioButton", {});
	      var userData = this.store.getters['users/get'](this.controller.getDialogId(), true);

	      if (userData.phones.personalMobile || userData.phones.workPhone || userData.phones.personalPhone || userData.phones.innerPhone) {
	        BackdropMenu.create('im.dialog.menu.call|' + this.controller.getDialogId()).setItems([BackdropMenuItem.create('audio').setTitle(this.localize['MOBILE_HEADER_MENU_AUDIO_CALL']), BackdropMenuItem.create('personalMobile').setTitle(userData.phones.personalMobile).setSubTitle(this.localize['MOBILE_MENU_CALL_MOBILE']).skip(!userData.phones.personalMobile), BackdropMenuItem.create('workPhone').setTitle(userData.phones.workPhone).setSubTitle(this.localize['MOBILE_MENU_CALL_WORK']).skip(!userData.phones.workPhone), BackdropMenuItem.create('personalPhone').setTitle(userData.phones.personalPhone).setSubTitle(this.localize['MOBILE_MENU_CALL_PHONE']).skip(!userData.phones.personalPhone), BackdropMenuItem.create('innerPhone').setTitle(userData.phones.innerPhone).setSubTitle(this.localize['MOBILE_MENU_CALL_PHONE']).skip(!userData.phones.innerPhone)]).setVersion(BX.componentParameters.get('WIDGET_BACKDROP_MENU_VERSION', '1.0.0')).setEventListener(function (name, params, user, backdrop) {
	          if (name !== 'selected') {
	            return false;
	          }

	          if (params.id === 'audio') {
	            BXMobileApp.Events.postToComponent("onCallInvite", {
	              userId: _this9.controller.getDialogId(),
	              video: false,
	              userData: babelHelpers.defineProperty({}, user.id, user)
	            }, "calls");
	          } else if (params.id === 'innerPhone') {
	            BX.MobileTools.phoneTo(user.phones[params.id], {
	              callMethod: 'telephony'
	            });
	          } else {
	            BX.MobileTools.phoneTo(user.phones[params.id], {
	              callMethod: 'device'
	            }); // items options
	            //.setType(BackdropMenuItemType.menu)
	            //.disableClose(BX.MobileTools.canUseTelephony())
	            // if (!BX.MobileTools.canUseTelephony())
	            // {
	            // 	BX.MobileTools.phoneTo(user.phones[params.id], {callMethod: 'device'});
	            // 	return false
	            // }
	            //
	            // let subMenu = BackdropMenu
	            // 	.create('im.dialog.menu.call.submenu|'+this.controller.getDialogId())
	            // 	.setItems([
	            // 		BackdropMenuItem.create('number')
	            // 			.setType(BackdropMenuItemType.info)
	            // 			.setTitle(this.localize["MOBILE_MENU_CALL_TO"]
	            // 			.replace('#PHONE_NUMBER#', user.phones[params.id]))
	            // 			.setHeight(50)
	            // 			.setStyles(BackdropMenuStyle.create().setFont(WidgetListItemFont.create().setFontStyle('bold')))
	            // 			.setDisabled(),
	            // 		BackdropMenuItem.create('telephony').setTitle(this.localize["MOBILE_CALL_BY_B24"]),
	            // 		BackdropMenuItem.create('device').setTitle(this.localize["MOBILE_CALL_BY_MOBILE"]),
	            // 	])
	            // 	.setEventListener((name, params, options, backdrop) =>
	            // 	{
	            // 		if (name !== 'selected')
	            // 		{
	            // 			return false;
	            // 		}
	            // 		BX.MobileTools.phoneTo(options.phone, {callMethod: params.id});
	            // 	})
	            // 	.setCustomParams({phone: user.phones[params.id]})
	            // ;
	            // backdrop.showSubMenu(subMenu);
	          }
	        }).setCustomParams(userData).show();
	      } else {
	        BXMobileApp.Events.postToComponent("onCallInvite", {
	          userId: this.controller.getDialogId(),
	          video: false,
	          userData: babelHelpers.defineProperty({}, this.controller.getDialogId(), userData)
	        }, "calls");
	      }
	    }
	  }, {
	    key: "leaveChat",
	    value: function leaveChat() {
	      var _this10 = this;

	      var confirm = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;

	      if (!confirm) {
	        app.confirm({
	          title: this.localize.MOBILE_HEADER_MENU_LEAVE_CONFIRM,
	          text: '',
	          buttons: [this.localize.MOBILE_HEADER_MENU_LEAVE_YES, this.localize.MOBILE_HEADER_MENU_LEAVE_NO],
	          callback: function callback(button) {
	            if (button === 1) {
	              _this10.leaveChat(true);
	            }
	          }
	        });
	        return true;
	      }

	      var dialogId = this.controller.getDialogId();
	      this.restClient.callMethod(im_const.RestMethod.imChatLeave, {
	        DIALOG_ID: dialogId
	      }, null, null, im_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imChatLeave,
	        dialog: this.controller.getDialogData(dialogId)
	      })).then(function (response) {
	        app.closeController();
	      });
	    }
	  }, {
	    key: "openAddUserDialog",
	    value: function openAddUserDialog() {
	      var listUsers = this.getItemsForAddUserDialog();
	      app.exec("openComponent", {
	        name: "JSStackComponent",
	        componentCode: "im.chat.user.selector",
	        scriptPath: "/mobileapp/jn/im.chat.user.selector/?version=" + BX.componentParameters.get('WIDGET_CHAT_RECIPIENTS_VERSION', '1.0.0'),
	        params: {
	          "DIALOG_ID": this.controller.getDialogId(),
	          "USER_ID": this.controller.getUserId(),
	          "LIST_USERS": listUsers,
	          "LIST_DEPARTMENTS": [],
	          "SKIP_LIST": [],
	          "SEARCH_MIN_SIZE": BX.componentParameters.get('SEARCH_MIN_TOKEN_SIZE', 3)
	        },
	        rootWidget: {
	          name: "chat.recipients",
	          settings: {
	            objectName: "ChatUserSelectorInterface",
	            title: BX.message('MOBILE_HEADER_MENU_USER_ADD'),
	            limit: 100,
	            items: listUsers.map(function (element) {
	              return ChatDataConverter.getListElementByUser(element);
	            }),
	            scopes: [{
	              title: BX.message('MOBILE_SCOPE_USERS'),
	              id: "user"
	            }, {
	              title: BX.message('MOBILE_SCOPE_DEPARTMENTS'),
	              id: "department"
	            }],
	            modal: true
	          }
	        }
	      }, false);
	    }
	  }, {
	    key: "getItemsForAddUserDialog",
	    value: function getItemsForAddUserDialog() {
	      var items = [];
	      var itemsIndex = {};

	      if (!this.widgetCache) {
	        this.widgetCache = new ChatWidgetCache(this.controller.getUserId(), this.controller.getLanguageId());
	      }

	      if (this.widgetCache.recentList.length > 0) {
	        this.widgetCache.recentList.map(function (element) {
	          if (!element || itemsIndex[element.id]) {
	            return false;
	          }

	          if (element.type === 'user') {
	            items.push(element.user);
	            itemsIndex[element.id] = true;
	          }

	          return true;
	        });
	      }

	      this.widgetCache.colleaguesList.map(function (element) {
	        if (!element || itemsIndex[element.id]) {
	          return false;
	        }

	        items.push(element);
	        itemsIndex[element.id] = true;
	      });
	      this.widgetCache.lastSearchList.map(function (element) {
	        if (!element || itemsIndex[element.id]) {
	          return false;
	        }

	        if (!element) {
	          return false;
	        }

	        if (element.type === 'user') {
	          items.push(element.user);
	          itemsIndex[element.id] = true;
	        }

	        return true;
	      });
	      /*
	      let skipList = ChatMessengerCommon.getChatUsers();
	      if (skipList.indexOf(this.base.userId) === -1)
	      {
	      	skipList.push(this.base.userId)
	      }
	      items = items.filter((element) => skipList.indexOf(element.id) === -1);
	      */

	      return items;
	    }
	  }, {
	    key: "eventStoreInteraction",

	    /* endregion 02. Mobile environment methods */

	    /* region 02. Push & Pull */
	    value: function eventStoreInteraction(data) {
	      if (data.type === 'dialogues/update' && data.payload && data.payload.fields) {
	        if (typeof data.payload.fields.counter !== 'undefined' && typeof data.payload.dialogId !== 'undefined') {
	          BXMobileApp.Events.postToComponent("chatdialog::counter::change", [{
	            dialogId: data.payload.dialogId,
	            counter: data.payload.fields.counter
	          }, true], 'im.recent');
	        }
	      } else if (data.type === 'dialogues/set') {
	        data.payload.forEach(function (dialog) {
	          BXMobileApp.Events.postToComponent("chatdialog::counter::change", [{
	            dialogId: dialog.dialogId,
	            counter: dialog.counter
	          }, true], 'im.recent');
	        });
	      }
	    }
	  }, {
	    key: "eventStatusInteraction",
	    value: function eventStatusInteraction(data) {
	      var _this11 = this;

	      if (data.status === mobile_pull_client.PullClient.PullStatus.Online) {
	        this.offline = false;

	        if (this.pullRequestMessage) {
	          this.pullCommandHandler.option.skip = true;
	          this.getDialogUnread().then(function () {
	            _this11.pullCommandHandler.option.skip = false;

	            _this11.processSendMessages();

	            _this11.controller.emit(im_const.EventType.dialog.sendReadMessages);
	          }).catch(function () {
	            _this11.pullCommandHandler.option.skip = false;

	            _this11.processSendMessages();
	          });
	          this.pullRequestMessage = false;
	        } else {
	          this.readMessage();
	          this.processSendMessages();
	        }
	      } else if (data.status === mobile_pull_client.PullClient.PullStatus.Offline) {
	        this.pullRequestMessage = true;
	        this.offline = true;
	      }
	    }
	  }, {
	    key: "eventOnlineInteraction",
	    value: function eventOnlineInteraction(data) {
	      if (data.command === 'list' || data.command === 'userStatus') {
	        for (var userId in data.params.users) {
	          if (!data.params.users.hasOwnProperty(userId)) {
	            continue;
	          }

	          this.store.dispatch('users/update', {
	            id: data.params.users[userId].id,
	            fields: data.params.users[userId]
	          });

	          if (userId.toString() === this.controller.getDialogId()) {
	            this.redrawHeader();
	          }
	        }
	      }
	    }
	    /* endregion 02. Push & Pull */

	  }, {
	    key: "getKeyboardParams",
	    value: function getKeyboardParams() {
	      var _this12 = this;

	      var dialogData = this.controller.getDialogData();
	      var siteDir = this.localize.SITE_DIR ? this.localize.SITE_DIR : '/';
	      return {
	        text: dialogData ? dialogData.textareaMessage : '',
	        placeholder: this.getLocalize('MOBILE_CHAT_PANEL_PLACEHOLDER'),
	        smileButton: {},
	        useImageButton: true,
	        useAudioMessages: true,
	        mentionDataSource: {
	          outsection: "NO",
	          url: siteDir + "mobile/index.php?mobile_action=get_user_list&use_name_format=Y&with_bots"
	        },
	        attachFileSettings: {
	          previewMaxWidth: 640,
	          previewMaxHeight: 640,
	          resize: {
	            targetWidth: -1,
	            targetHeight: -1,
	            sourceType: 1,
	            encodingType: 0,
	            mediaType: 2,
	            allowsEdit: false,
	            saveToPhotoAlbum: true,
	            popoverOptions: false,
	            cameraDirection: 0
	          },
	          sendFileSeparately: true,
	          showAttachedFiles: true,
	          editingMediaFiles: false,
	          maxAttachedFilesCount: 100
	        },
	        attachButton: {
	          items: [{
	            id: "disk",
	            name: this.getLocalize("MOBILE_CHAT_PANEL_UPLOAD_DISK"),
	            dataSource: {
	              multiple: false,
	              url: siteDir + "mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=" + this.getLocalize("USER_ID"),
	              TABLE_SETTINGS: {
	                searchField: true,
	                showtitle: true,
	                modal: true,
	                name: this.getLocalize("MOBILE_CHAT_PANEL_UPLOAD_DISK_FILES")
	              }
	            }
	          }, {
	            id: "mediateka",
	            name: this.getLocalize("MOBILE_CHAT_PANEL_UPLOAD_GALLERY")
	          }, {
	            id: "camera",
	            name: this.getLocalize("MOBILE_CHAT_PANEL_UPLOAD_CAMERA")
	          }]
	        },
	        action: function action(data) {
	          if (typeof data === "string") {
	            data = {
	              text: data,
	              attachedFiles: []
	            };
	          }

	          var text = data.text.toString().trim();
	          var attachedFiles = data.attachedFiles instanceof Array ? data.attachedFiles : [];

	          if (attachedFiles.length <= 0) {
	            _this12.clearText();

	            _this12.addMessage(text);
	          } else {
	            attachedFiles.forEach(function (file) {
	              // disk
	              if (typeof file.dataAttributes !== 'undefined') {
	                fabric.Answers.sendCustomEvent("vueChatFileDisk", {});
	                return _this12.uploadFile({
	                  source: 'disk',
	                  name: file.name,
	                  type: file.type.toString().toLowerCase(),
	                  preview: !file.dataAttributes.URL || !file.dataAttributes.URL.PREVIEW ? null : {
	                    url: file.dataAttributes.URL.PREVIEW,
	                    width: file.dataAttributes.URL.PREVIEW.match(/(width=(\d+))/i)[2],
	                    height: file.dataAttributes.URL.PREVIEW.match(/(height=(\d+))/i)[2]
	                  },
	                  uploadLink: parseInt(file.dataAttributes.ID)
	                });
	              } // audio


	              if (file.type === 'audio/mp4') {
	                fabric.Answers.sendCustomEvent("vueChatFileAudio", {});
	                return _this12.uploadFile({
	                  source: 'audio',
	                  name: 'mobile_audio_' + new Date().toJSON().slice(0, 19).replace('T', '_').split(':').join('-') + '.mp3',
	                  type: 'mp3',
	                  preview: null,
	                  uploadLink: file.url
	                });
	              }

	              var filename = file.name;
	              var fileType = im_model.FilesModel.getType(file.name);

	              if (fileType === im_const.FileType.video) {
	                fabric.Answers.sendCustomEvent("vueChatFileVideo", {});
	              } else if (fileType === im_const.FileType.image) {
	                fabric.Answers.sendCustomEvent("vueChatFileImage", {});
	              } else {
	                fabric.Answers.sendCustomEvent("vueChatFileOther", {});
	              }

	              if (fileType === im_const.FileType.image || fileType === im_const.FileType.video) {
	                var extension = file.name.split('.').slice(-1)[0].toLowerCase();

	                if (file.type === 'image/heic') {
	                  extension = 'jpg';
	                }

	                filename = 'mobile_file_' + new Date().toJSON().slice(0, 19).replace('T', '_').split(':').join('-') + '.' + extension;
	              } // file


	              return _this12.uploadFile({
	                source: 'gallery',
	                name: filename,
	                type: file.type.toString().toLowerCase(),
	                preview: !file.previewUrl ? null : {
	                  url: file.previewUrl,
	                  width: file.previewWidth,
	                  height: file.previewHeight
	                },
	                uploadLink: file.url
	              });
	            });
	          }
	        },
	        callback: function callback(data) {
	          console.log('Textpanel callback', data);

	          if (!data.event) {
	            return false;
	          }

	          if (data.event === "onKeyPress") {
	            var text = data.text.toString();

	            if (text.trim().length > 2) {
	              _this12.controller.startWriting();
	            }

	            if (text.length === 0) {
	              _this12.controller.setTextareaMessage({
	                message: ''
	              });

	              _this12.controller.stopWriting();
	            } else {
	              _this12.setTextareaMessage({
	                message: text
	              });
	            }
	          } else if (Application.getPlatform() !== "android") {
	            if (data.event === "getFocus") {
	              if (im_utils.Utils.platform.isIos() && im_utils.Utils.platform.getIosVersion() > 12) {
	                _this12.controller.emit(im_const.EventType.dialog.scrollToBottom, {
	                  duration: 300,
	                  cancelIfScrollChange: true
	                });
	              }
	            } else if (data.event === "removeFocus") ;
	          }
	        }
	      };
	    }
	    /* region 03. Template engine */

	  }, {
	    key: "attachTemplate",
	    value: function attachTemplate() {
	      if (this.template) {
	        return true;
	      }

	      var application = this;
	      var controller = this.controller;
	      var restClient = this.restClient;
	      var pullClient = this.pullClient || null;
	      return ui_vue.Vue.create({
	        el: this.rootNode,
	        store: this.store,
	        template: '<bx-messenger/>',
	        beforeCreate: function beforeCreate() {
	          this.$bitrixApplication = application;
	          this.$bitrixController = controller;
	          this.$bitrixRestClient = restClient;
	          this.$bitrixPullClient = pullClient;
	          this.$bitrixMessages = application.localize;
	        },
	        destroyed: function destroyed() {
	          this.$bitrixApplication.template = null;
	          this.$bitrixApplication = null;
	          this.$bitrixController = null;
	          this.$bitrixRestClient = null;
	          this.$bitrixPullClient = null;
	          this.$bitrixMessages = null;
	        }
	      });
	    }
	  }, {
	    key: "detachTemplate",
	    value: function detachTemplate() {
	      if (!this.template) {
	        return true;
	      }

	      this.template.$destroy();
	      return true;
	    }
	    /* endregion 03. Template engine */

	    /* region 04. Rest methods */

	  }, {
	    key: "addMessage",
	    value: function addMessage(text) {
	      var _this13 = this;

	      var file = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

	      if (!text && !file) {
	        return false;
	      }

	      var quiteId = this.store.getters['dialogues/getQuoteId'](this.controller.getDialogId());

	      if (quiteId) {
	        var quoteMessage = this.store.getters['messages/getMessage'](this.controller.getChatId(), quiteId);

	        if (quoteMessage) {
	          var user = null;

	          if (quoteMessage.authorId) {
	            user = this.store.getters['users/get'](quoteMessage.authorId);
	          }

	          var files = this.store.getters['files/getList'](this.controller.getChatId());
	          var message = [];
	          message.push('-'.repeat(54));
	          message.push((user && user.name ? user.name : this.getLocalize('MOBILE_CHAT_SYSTEM_MESSAGE')) + ' [' + im_utils.Utils.date.format(quoteMessage.date, null, this.localize) + ']');
	          message.push(im_utils.Utils.text.quote(quoteMessage.text, quoteMessage.params, files, this.localize));
	          message.push('-'.repeat(54));
	          message.push(text);
	          text = message.join("\n");
	          this.quoteMessageClear();
	        }
	      }

	      console.warn('addMessage', text, file);

	      if (!this.controller.isUnreadMessagesLoaded()) {
	        this.sendMessage({
	          id: 0,
	          chatId: this.controller.getChatId(),
	          dialogId: this.controller.getDialogId(),
	          text: text,
	          file: file
	        });
	        this.processSendMessages();
	        return true;
	      }

	      this.store.commit('application/increaseDialogExtraCount');
	      var params = {};

	      if (file) {
	        params.FILE_ID = [file.id];
	      }

	      this.store.dispatch('messages/add', {
	        chatId: this.controller.getChatId(),
	        authorId: this.controller.getUserId(),
	        text: text,
	        params: params,
	        sending: !file
	      }).then(function (messageId) {
	        _this13.messagesQueue.push({
	          id: messageId,
	          chatId: _this13.controller.getChatId(),
	          dialogId: _this13.controller.getDialogId(),
	          text: text,
	          file: file,
	          sending: false
	        });

	        if (_this13.controller.getChatId()) {
	          _this13.processSendMessages();
	        } else {
	          _this13.requestData();
	        }
	      });
	      return true;
	    }
	  }, {
	    key: "uploadFile",
	    value: function uploadFile(file) {
	      var _this14 = this;

	      var text = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';

	      if (!file) {
	        return false;
	      }

	      console.warn('addFile', file, text);

	      if (!this.controller.isUnreadMessagesLoaded()) {
	        this.addMessage(text, {
	          id: 0,
	          source: file
	        });
	        return true;
	      }

	      this.store.dispatch('files/add', this.controller.prepareFilesBeforeSave({
	        chatId: this.controller.getChatId(),
	        authorId: this.controller.getUserId(),
	        name: file.name,
	        type: im_model.FilesModel.getType(file.name),
	        extension: file.name.split('.').splice(-1)[0],
	        size: 0,
	        image: !file.preview ? false : {
	          width: file.preview.width,
	          height: file.preview.height
	        },
	        status: file.source === 'disk' ? im_const.FileStatus.wait : im_const.FileStatus.upload,
	        progress: 0,
	        authorName: this.controller.getCurrentUser().name,
	        urlPreview: !file.preview ? '' : file.preview.url
	      })).then(function (fileId) {
	        return _this14.addMessage(text, babelHelpers.objectSpread({
	          id: fileId
	        }, file));
	      });
	      return true;
	    }
	  }, {
	    key: "cancelUploadFile",
	    value: function cancelUploadFile(fileId) {
	      var _this15 = this;

	      var element = this.messagesQueue.find(function (element) {
	        return element.file && element.file.id === fileId;
	      });

	      if (element) {
	        BX.MobileUploadProvider.cancelTasks(['imDialog' + fileId]);
	        this.store.dispatch('messages/delete', {
	          chatId: element.chatId,
	          id: element.id
	        }).then(function () {
	          _this15.store.dispatch('files/delete', {
	            chatId: element.chatId,
	            id: element.file.id
	          });

	          _this15.messagesQueue = _this15.messagesQueue.filter(function (el) {
	            return el.id !== element.id;
	          });
	        });
	      }
	    }
	  }, {
	    key: "retryUploadFile",
	    value: function retryUploadFile(fileId) {
	      var _this16 = this;

	      var element = this.messagesQueue.find(function (element) {
	        return element.file && element.file.id === fileId;
	      });

	      if (!element) {
	        return false;
	      }

	      this.store.dispatch('messages/actionStart', {
	        chatId: element.chatId,
	        id: element.id
	      }).then(function () {
	        _this16.store.dispatch('files/update', {
	          chatId: element.chatId,
	          id: element.file.id,
	          fields: {
	            status: im_const.FileStatus.upload,
	            progress: 0
	          }
	        });
	      });
	      element.sending = false;
	      this.processSendMessages();
	      return true;
	    }
	  }, {
	    key: "processSendMessages",
	    value: function processSendMessages() {
	      var _this17 = this;

	      this.messagesQueue.filter(function (element) {
	        return !element.sending;
	      }).forEach(function (element) {
	        element.sending = true;

	        if (element.file) {
	          if (element.file.source === 'disk') {
	            _this17.fileCommit({
	              chatId: element.chatId,
	              dialogId: element.dialogId,
	              diskId: element.file.uploadLink,
	              messageText: element.text,
	              messageId: element.id,
	              fileId: element.file.id,
	              fileType: im_model.FilesModel.getType(element.file.name)
	            }, element);
	          } else {
	            if (_this17.controller.getDiskFolderId()) {
	              _this17.sendMessageWithFile(element);
	            } else {
	              element.sending = false;

	              _this17.requestDiskFolderId();
	            }
	          }
	        } else {
	          element.sending = true;

	          _this17.sendMessage(element);
	        }
	      });
	      return true;
	    }
	  }, {
	    key: "processMarkReadMessages",
	    value: function processMarkReadMessages() {
	      this.controller.readMessageExecute(this.controller.getChatId(), true);
	      return true;
	    }
	  }, {
	    key: "sendMessage",
	    value: function sendMessage(message) {
	      message.text = message.text.replace(/^([-]{21}\n)/gm, '-'.repeat(54) + '\n');
	      this.controller.stopWriting(message.dialogId);
	      BXMobileApp.Events.postToComponent('chatbackground::task::add', ['sendMessage|' + message.id, [im_const.RestMethod.imMessageAdd, {
	        'TEMPLATE_ID': message.id,
	        'DIALOG_ID': message.dialogId,
	        'MESSAGE': message.text
	      }], message], 'background');
	    }
	  }, {
	    key: "sendMessageWithFile",
	    value: function sendMessageWithFile(message) {
	      var fileType = im_model.FilesModel.getType(message.file.name);
	      var fileExtension = message.file.name.toString().toLowerCase().split('.').splice(-1)[0];
	      var attachPreviewFile = fileType !== im_const.FileType.image && message.file.preview;
	      var needConvert = fileType === im_const.FileType.image && message.file.type !== 'image/gif' || fileType === im_const.FileType.video;
	      BX.MobileUploadProvider.addTasks([{
	        url: message.file.uploadLink,
	        params: message,
	        name: message.file.name,
	        type: fileExtension,
	        mimeType: fileType === im_const.FileType.audio ? 'audio/mp4' : null,
	        resize: !needConvert ? null : {
	          "quality": 80,
	          "width": 1920,
	          "height": 1080
	        },
	        previewUrl: attachPreviewFile ? message.file.preview.url : '',
	        folderId: this.controller.getDiskFolderId(),
	        taskId: 'imDialog' + message.file.id,
	        onDestroyEventName: 'onimdiskmessageaddsuccess'
	      }]);
	    }
	  }, {
	    key: "fileError",
	    value: function fileError(chatId, fileId) {
	      var messageId = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 0;
	      this.store.dispatch('files/update', {
	        chatId: chatId,
	        id: fileId,
	        fields: {
	          status: im_const.FileStatus.error,
	          progress: 0
	        }
	      });

	      if (messageId) {
	        this.store.dispatch('messages/actionError', {
	          chatId: chatId,
	          id: messageId,
	          retry: true
	        });
	      }
	    }
	  }, {
	    key: "fileCommit",
	    value: function fileCommit(params, message) {
	      var _this18 = this;

	      var queryParams = {
	        chat_id: params.chatId,
	        message: params.messageText,
	        template_id: params.messageId ? params.messageId : 0,
	        file_template_id: params.fileId ? params.fileId : 0
	      };

	      if (params.uploadId) {
	        queryParams.upload_id = params.uploadId;
	      } else if (params.diskId) {
	        queryParams.disk_id = params.diskId;
	      }

	      this.restClient.callMethod(im_const.RestMethod.imDiskFileCommit, queryParams, null, null, im_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imDiskFileCommit,
	        data: {
	          timMessageType: params.fileType
	        },
	        dialog: this.controller.getDialogData(params.dialogId)
	      })).then(function (response) {
	        _this18.executeRestAnswer(im_const.RestMethodHandler.imDiskFileCommit, response, message);
	      }).catch(function (error) {
	        _this18.executeRestAnswer(im_const.RestMethodHandler.imDiskFileCommit, error, message);
	      });
	      return true;
	    }
	  }, {
	    key: "requestDiskFolderId",
	    value: function requestDiskFolderId() {
	      var _this19 = this;

	      if (this.flagRequestDiskFolderIdSended || this.controller.getDiskFolderId()) {
	        return true;
	      }

	      this.flagRequestDiskFolderIdSended = true;
	      this.restClient.callMethod(im_const.RestMethod.imDiskFolderGet, {
	        chat_id: this.controller.getChatId()
	      }).then(function (response) {
	        _this19.executeRestAnswer(im_const.RestMethodHandler.imDiskFolderGet, response);

	        _this19.flagRequestDiskFolderIdSended = false;

	        _this19.processSendMessages();
	      }).catch(function (error) {
	        _this19.flagRequestDiskFolderIdSended = false;

	        _this19.executeRestAnswer(im_const.RestMethodHandler.imDiskFolderGet, error);
	      });
	      return true;
	    }
	  }, {
	    key: "getDialogHistory",
	    value: function getDialogHistory(lastId) {
	      var _this20 = this;

	      var limit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.controller.getRequestMessageLimit();
	      this.restClient.callMethod(im_const.RestMethod.imDialogMessagesGet, {
	        'CHAT_ID': this.controller.getChatId(),
	        'LAST_ID': lastId,
	        'LIMIT': limit,
	        'CONVERT_TEXT': 'Y'
	      }).then(function (result) {
	        _this20.executeRestAnswer(im_const.RestMethodHandler.imDialogMessagesGet, result);

	        _this20.controller.emit(im_const.EventType.dialog.requestHistoryResult, {
	          count: result.data().messages.length
	        });
	      }).catch(function (result) {
	        _this20.controller.emit(im_const.EventType.dialog.requestHistoryResult, {
	          error: result.error().ex
	        });
	      });
	    }
	  }, {
	    key: "getDialogUnread",
	    value: function getDialogUnread(lastId) {
	      var _this21 = this;

	      var limit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.controller.getRequestMessageLimit();

	      if (this.promiseGetDialogUnreadWait) {
	        return this.promiseGetDialogUnread;
	      }

	      this.promiseGetDialogUnread = new BX.Promise();
	      this.promiseGetDialogUnreadWait = true;

	      if (!lastId) {
	        lastId = this.store.getters['messages/getLastId'](this.controller.getChatId());
	      }

	      if (!lastId) {
	        this.controller.emit(im_const.EventType.dialog.requestUnreadResult, {
	          error: {
	            error: 'LAST_ID_EMPTY',
	            error_description: 'LastId is empty.'
	          }
	        });
	        this.promiseGetDialogUnread.reject();
	        this.promiseGetDialogUnreadWait = false;
	        return this.promiseGetDialogUnread;
	      }

	      this.controller.readMessage(lastId, true, true).then(function () {
	        var _query2;

	        _this21.timer.start('data', 'load', .5, function () {
	          console.warn("ChatDialog.requestData: slow connection show progress icon");
	          app.titleAction("setParams", {
	            useProgress: true,
	            useLetterImage: false
	          });
	        });

	        var query = (_query2 = {}, babelHelpers.defineProperty(_query2, im_const.RestMethodHandler.imDialogRead, [im_const.RestMethod.imDialogRead, {
	          dialog_id: _this21.controller.getDialogId(),
	          message_id: lastId
	        }]), babelHelpers.defineProperty(_query2, im_const.RestMethodHandler.imChatGet, [im_const.RestMethod.imChatGet, {
	          dialog_id: _this21.controller.getDialogId()
	        }]), babelHelpers.defineProperty(_query2, im_const.RestMethodHandler.imDialogMessagesGetUnread, [im_const.RestMethod.imDialogMessagesGet, {
	          chat_id: _this21.controller.getChatId(),
	          first_id: lastId,
	          limit: limit,
	          convert_text: 'Y'
	        }]), _query2);

	        _this21.restClient.callBatch(query, function (response) {
	          if (!response) {
	            _this21.controller.emit(im_const.EventType.dialog.requestUnreadResult, {
	              error: {
	                error: 'EMPTY_RESPONSE',
	                error_description: 'Server returned an empty response.'
	              }
	            });

	            _this21.promiseGetDialogUnread.reject();

	            _this21.promiseGetDialogUnreadWait = false;
	            return false;
	          }

	          var chatGetResult = response[im_const.RestMethodHandler.imChatGet];

	          if (!chatGetResult.error()) {
	            _this21.executeRestAnswer(im_const.RestMethodHandler.imChatGet, chatGetResult);
	          }

	          var dialogMessageUnread = response[im_const.RestMethodHandler.imDialogMessagesGetUnread];

	          if (dialogMessageUnread.error()) {
	            _this21.controller.emit(im_const.EventType.dialog.requestUnreadResult, {
	              error: dialogMessageUnread.error().ex
	            });
	          } else {
	            _this21.executeRestAnswer(im_const.RestMethodHandler.imDialogMessagesGetUnread, dialogMessageUnread);

	            _this21.controller.emit(im_const.EventType.dialog.requestUnreadResult, {
	              firstMessageId: dialogMessageUnread.data().messages.length > 0 ? dialogMessageUnread.data().messages[0].id : 0,
	              count: dialogMessageUnread.data().messages.length
	            });

	            app.titleAction("setParams", {
	              useProgress: false,
	              useLetterImage: true
	            });

	            _this21.timer.stop('data', 'load', true);
	          }

	          _this21.promiseGetDialogUnread.fulfill(response);

	          _this21.promiseGetDialogUnreadWait = false;
	        }, false, false, im_utils.Utils.getLogTrackingParams({
	          name: im_const.RestMethodHandler.imDialogMessagesGetUnread,
	          dialog: _this21.controller.getDialogData()
	        }));
	      });
	      return this.promiseGetDialogUnread;
	    }
	  }, {
	    key: "retrySendMessage",
	    value: function retrySendMessage(message) {
	      var element = this.messagesQueue.find(function (el) {
	        return el.id === message.id;
	      });

	      if (element) {
	        if (element.file && element.file.id) {
	          this.retryUploadFile(element.file.id);
	        }

	        return false;
	      }

	      this.messagesQueue.push({
	        id: message.id,
	        chatId: this.controller.getChatId(),
	        dialogId: this.controller.getDialogId(),
	        text: message.text,
	        sending: false
	      });
	      this.controller.setSendingMessageFlag(message.id);
	      this.processSendMessages();
	    }
	  }, {
	    key: "openProfile",
	    value: function openProfile(userId) {
	      BXMobileApp.Events.postToComponent("onUserProfileOpen", [userId, {
	        backdrop: true
	      }], 'communication');
	    }
	  }, {
	    key: "openDialog",
	    value: function openDialog(dialogId) {
	      BXMobileApp.Events.postToComponent("onOpenDialog", [{
	        dialogId: dialogId
	      }, true], 'im.recent');
	    }
	  }, {
	    key: "openPhoneMenu",
	    value: function openPhoneMenu(number) {
	      BX.MobileTools.phoneTo(number);
	    }
	  }, {
	    key: "openMessageMenu",
	    value: function openMessageMenu(message) {
	      var _this22 = this;

	      if (this.messagesQueue.find(function (el) {
	        return el.id === message.id;
	      })) {
	        return false;
	      }

	      this.store.dispatch('messages/update', {
	        id: message.id,
	        chatId: message.chatId,
	        fields: {
	          blink: true
	        }
	      });

	      if (!this.messageMenuInstance) {
	        var currentUser = this.controller.getCurrentUser();
	        var dialog = this.controller.getDialogData();
	        this.messageMenuInstance = BackdropMenu.create('im.dialog.menu.mess|' + this.controller.getDialogId()).setItems([BackdropMenuItem.create('reply').setTitle(this.localize['MOBILE_MESSAGE_MENU_REPLY']).setIcon(BackdropMenuIcon.reply).skip(function (message) {
	          var dialog = _this22.controller.getDialogData();

	          if (dialog.type === 'announcement' && !dialog.managerList.includes(_this22.controller.getUserId())) {
	            return true;
	          }

	          return !message.authorId || message.authorId === _this22.controller.getUserId();
	        }), BackdropMenuItem.create('copy').setTitle(this.localize['MOBILE_MESSAGE_MENU_COPY']).setIcon(BackdropMenuIcon.copy).skip(function (message) {
	          return message.params.IS_DELETED === 'Y';
	        }), BackdropMenuItem.create('quote').setTitle(this.localize['MOBILE_MESSAGE_MENU_QUOTE']).setIcon(BackdropMenuIcon.quote).skip(function (message) {
	          var dialog = _this22.controller.getDialogData();

	          if (dialog.type === 'announcement' && !dialog.managerList.includes(_this22.controller.getUserId())) {
	            return true;
	          }

	          return message.params.IS_DELETED === 'Y';
	        }), BackdropMenuItem.create('unread').setTitle(this.localize['MOBILE_MESSAGE_MENU_UNREAD']).setIcon(BackdropMenuIcon.unread).skip(function (message) {
	          return message.authorId === _this22.controller.getUserId() || message.unread;
	        }), BackdropMenuItem.create('read').setTitle(this.localize['MOBILE_MESSAGE_MENU_READ']).setIcon(BackdropMenuIcon.checked).skip(function (message) {
	          return !message.unread;
	        }), BackdropMenuItem.create('edit').setTitle(this.localize['MOBILE_MESSAGE_MENU_EDIT']).setIcon(BackdropMenuIcon.edit).skip(function (message) {
	          return message.authorId !== _this22.controller.getUserId() || message.params.IS_DELETED === 'Y';
	        }), BackdropMenuItem.create('share').setType(BackdropMenuItemType.menu).setIcon(BackdropMenuIcon.circle_plus).setTitle(this.localize['MOBILE_MESSAGE_MENU_SHARE_MENU']).disableClose().skip(currentUser.extranet || dialog.type === 'announcement'), BackdropMenuItem.create('profile').setTitle(this.localize['MOBILE_MESSAGE_MENU_PROFILE']).setIcon(BackdropMenuIcon.user).skip(function (message) {
	          return message.authorId <= 0 || !im_utils.Utils.dialog.isChatId(_this22.controller.getDialogId()) || message.authorId === _this22.controller.getUserId();
	        }), BackdropMenuItem.create('delete').setTitle(this.localize['MOBILE_MESSAGE_MENU_DELETE']).setStyles(BackdropMenuStyle.create().setFont(WidgetListItemFont.create().setColor('#c50000'))).setIcon(BackdropMenuIcon.trash).skip(function (message) {
	          return message.authorId !== _this22.controller.getUserId() || message.params.IS_DELETED === 'Y';
	        })]).setVersion(BX.componentParameters.get('WIDGET_BACKDROP_MENU_VERSION', '1.0.0')).setEventListener(function (name, params, message, backdrop) {
	          if (name !== 'selected') {
	            return false;
	          }

	          if (params.id === 'reply') {
	            _this22.replyToUser(message.authorId);
	          } else if (params.id === 'copy') {
	            _this22.copyMessage(message.id);
	          } else if (params.id === 'quote') {
	            _this22.quoteMessage(message.id);
	          } else if (params.id === 'edit') {
	            _this22.editMessage(message.id);
	          } else if (params.id === 'delete') {
	            _this22.deleteMessage(message.id);
	          } else if (params.id === 'unread') {
	            _this22.unreadMessage(message.id);
	          } else if (params.id === 'read') {
	            _this22.readMessage(message.id);
	          } else if (params.id === 'share') {
	            var _dialog = _this22.controller.getDialogData();

	            var subMenu = BackdropMenu.create('im.dialog.menu.mess.submenu|' + _this22.controller.getDialogId()).setItems([BackdropMenuItem.create('share_task').setIcon(BackdropMenuIcon.task).setTitle(_this22.localize['MOBILE_MESSAGE_MENU_SHARE_TASK']), BackdropMenuItem.create('share_post').setIcon(BackdropMenuIcon.lifefeed).setTitle(_this22.localize['MOBILE_MESSAGE_MENU_SHARE_POST']), BackdropMenuItem.create('share_chat').setIcon(BackdropMenuIcon.chat).setTitle(_this22.localize['MOBILE_MESSAGE_MENU_SHARE_CHAT'])]).setEventListener(function (name, params, options, backdrop) {
	              if (name !== 'selected') {
	                return false;
	              }

	              if (params.id === 'share_task') {
	                _this22.shareMessage(message.id, 'TASK');
	              } else if (params.id === 'share_post') {
	                _this22.shareMessage(message.id, 'POST');
	              } else if (params.id === 'share_chat') {
	                _this22.shareMessage(message.id, 'CHAT');
	              }
	            });
	            backdrop.showSubMenu(subMenu);
	          } else if (params.id === 'profile') {
	            _this22.openProfile(message.authorId);
	          } else {
	            console.warn('BackdropMenuItem is not implemented', params);
	          }
	        });
	      }

	      this.messageMenuInstance.setCustomParams(message).show();
	      fabric.Answers.sendCustomEvent("vueChatOpenDropdown", {});
	    }
	  }, {
	    key: "openHeaderMenu",
	    value: function openHeaderMenu() {
	      var _this23 = this;

	      fabric.Answers.sendCustomEvent("vueChatOpenHeaderMenu", {});

	      if (!this.headerMenu) {
	        this.headerMenu = HeaderMenu.create().setUseNavigationBarColor().setEventListener(function (name, params, customParams) {
	          if (name !== 'selected') {
	            return false;
	          }

	          if (params.id === 'profile') {
	            _this23.openProfile(_this23.controller.getDialogId());
	          } else if (params.id === 'user_list') {
	            _this23.openUserList({
	              listType: 'USERS',
	              title: _this23.localize.MOBILE_HEADER_MENU_USER_LIST,
	              backdrop: false
	            });
	          } else if (params.id === 'user_add') {
	            _this23.openAddUserDialog();
	          } else if (params.id === 'leave') {
	            _this23.leaveChat();
	          } else if (params.id === 'notify') {
	            _this23.controller.muteDialog();
	          } else if (params.id === 'call_chat_call') {
	            BX.MobileTools.phoneTo(_this23.controller.getDialogData().entityId);
	          } else if (params.id === 'goto_crm') {
	            var crmData = _this23.controller.getDialogCrmData();

	            var openWidget = BX.MobileTools.resolveOpenFunction('/crm/' + crmData.entityType + '/show/' + crmData.entityId + '/');

	            if (openWidget) {
	              openWidget();
	            }
	          } else if (params.id === 'reload') {
	            new BXMobileApp.UI.NotificationBar({
	              message: _this23.localize.MOBILE_HEADER_MENU_RELOAD_WAIT,
	              color: "#d920b0ff",
	              textColor: "#ffffff",
	              groupId: "refresh",
	              useLoader: true,
	              maxLines: 1,
	              align: "center",
	              hideOnTap: true
	            }, "copy").show();
	            ChatDialog.storeCollector.clearDatabase();
	            reload();
	          }
	        });
	      }

	      if (im_utils.Utils.dialog.isChatId(this.controller.getDialogId())) {
	        var dialogData = this.controller.getDialogData();
	        var notifyToggleText = !this.controller.isDialogMuted() ? this.localize['MOBILE_HEADER_MENU_NOTIFY_DISABLE'] : this.localize['MOBILE_HEADER_MENU_NOTIFY_ENABLE'];
	        var notifyToggleIcon = !this.controller.isDialogMuted() ? HeaderMenuIcon.notify : HeaderMenuIcon.notify_off;
	        var gotoCrmLocalize = '';

	        if (dialogData.type === im_const.DialogType.call || dialogData.type === im_const.DialogType.crm) {
	          var crmData = this.controller.getDialogCrmData();

	          if (crmData.enabled) {
	            if (crmData.entityType === im_const.DialogCrmType.lead) {
	              gotoCrmLocalize = this.localize['MOBILE_GOTO_CRM_LEAD'];
	            } else if (crmData.entityType === im_const.DialogCrmType.company) {
	              gotoCrmLocalize = this.localize['MOBILE_GOTO_CRM_COMPANY'];
	            } else if (crmData.entityType === im_const.DialogCrmType.contact) {
	              gotoCrmLocalize = this.localize['MOBILE_GOTO_CRM_CONTACT'];
	            } else if (crmData.entityType === im_const.DialogCrmType.deal) {
	              gotoCrmLocalize = this.localize['MOBILE_GOTO_CRM_DEAL'];
	            } else {
	              gotoCrmLocalize = this.localize['MOBILE_GOTO_CRM'];
	            }
	          }
	        }

	        if (dialogData.type === im_const.DialogType.call) {
	          this.headerMenu.setItems([HeaderMenuItem.create('call_chat_call').setTitle(this.localize['MOBILE_HEADER_MENU_AUDIO_CALL']).setIcon(HeaderMenuIcon.phone).skip(dialogData.entityId === 'UNIFY_CALL_CHAT'), HeaderMenuItem.create('goto_crm').setTitle(gotoCrmLocalize).setIcon(HeaderMenuIcon.lifefeed).skip(dialogData.entityId === 'UNIFY_CALL_CHAT'), HeaderMenuItem.create('reload').setTitle(this.localize['MOBILE_HEADER_MENU_RELOAD']).setIcon(HeaderMenuIcon.reload)]);
	        } else {
	          var items = [HeaderMenuItem.create('notify').setTitle(notifyToggleText).setIcon(notifyToggleIcon), HeaderMenuItem.create('user_list').setTitle(this.localize['MOBILE_HEADER_MENU_USER_LIST']).setIcon(HeaderMenuIcon.user), HeaderMenuItem.create('user_add').setTitle(this.localize['MOBILE_HEADER_MENU_USER_ADD']).setIcon(HeaderMenuIcon.user_plus), HeaderMenuItem.create('leave').setTitle(this.localize['MOBILE_HEADER_MENU_LEAVE']).setIcon(HeaderMenuIcon.cross), HeaderMenuItem.create('reload').setTitle(this.localize['MOBILE_HEADER_MENU_RELOAD']).setIcon(HeaderMenuIcon.reload)];

	          if (dialogData.type === im_const.DialogType.crm && gotoCrmLocalize) {
	            items.unshift(HeaderMenuItem.create('goto_crm').setTitle(gotoCrmLocalize).setIcon(HeaderMenuIcon.lifefeed));
	          }

	          this.headerMenu.setItems(items);
	        }
	      } else {
	        this.headerMenu.setItems([HeaderMenuItem.create('profile').setTitle(this.localize['MOBILE_HEADER_MENU_PROFILE']).setIcon('user').skip(im_utils.Utils.dialog.isChatId(this.controller.getDialogId())), HeaderMenuItem.create('user_add').setTitle(this.localize['MOBILE_HEADER_MENU_USER_ADD']).setIcon(HeaderMenuIcon.user_plus), HeaderMenuItem.create('reload').setTitle(this.localize['MOBILE_HEADER_MENU_RELOAD']).setIcon(HeaderMenuIcon.reload)]);
	      }

	      this.headerMenu.show(true);
	    }
	  }, {
	    key: "shareMessage",
	    value: function shareMessage(messageId, type) {
	      if (this.offline) {
	        return false;
	      }

	      return this.controller.shareMessage(messageId, type);
	    }
	  }, {
	    key: "readMessage",
	    value: function readMessage(messageId) {
	      // if (this.offline)
	      // {
	      // 	return false;
	      // }
	      this.controller.readMessage(messageId, true, true).then(function (result) {
	        if (result.lastId <= 0) {
	          return true;
	        }

	        BXMobileApp.Events.postToComponent('chatbackground::task::action', ['readMessage', 'readMessage|' + result.dialogId, result, false, 200], 'background');
	      });
	      return true;
	    }
	  }, {
	    key: "unreadMessage",
	    value: function unreadMessage(messageId) {
	      if (this.offline) {
	        return false;
	      }

	      return this.controller.unreadMessage(messageId);
	    }
	  }, {
	    key: "openReadedList",
	    value: function openReadedList(list) {
	      if (!im_utils.Utils.dialog.isChatId(this.controller.getDialogId())) {
	        return false;
	      }

	      if (!list || list.length <= 1) {
	        return false;
	      }

	      this.openUserList({
	        users: list.map(function (element) {
	          return element.userId;
	        }),
	        title: this.localize.MOBILE_MESSAGE_LIST_VIEW
	      });
	    }
	  }, {
	    key: "replyToUser",
	    value: function replyToUser(userId) {
	      var userData = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

	      if (this.offline) {
	        return false;
	      }

	      if (!userData) {
	        userData = this.store.getters['users/get'](userId);
	      }

	      return this.insertText({
	        text: "[USER=".concat(userId, "]").concat(userData.firstName, "[/USER] ")
	      });
	    }
	  }, {
	    key: "copyMessage",
	    value: function copyMessage(id) {
	      var quoteMessage = this.store.getters['messages/getMessage'](this.controller.getChatId(), id);
	      var text = '';

	      if (quoteMessage.params.FILE_ID && quoteMessage.params.FILE_ID.length) {
	        text = quoteMessage.params.FILE_ID.map(function (fileId) {
	          return '[DISK=' + fileId + ']';
	        }).join(" ");
	      }

	      if (quoteMessage.text) {
	        if (text) {
	          text += '\n';
	        }

	        text += quoteMessage.text.replace(/^([-]{54}\n)/gm, '-'.repeat(21) + '\n');
	      }

	      app.exec("copyToClipboard", {
	        text: text
	      });
	      new BXMobileApp.UI.NotificationBar({
	        message: BX.message("MOBILE_MESSAGE_MENU_COPY_SUCCESS"),
	        color: "#af000000",
	        textColor: "#ffffff",
	        groupId: "clipboard",
	        maxLines: 1,
	        align: "center",
	        isGlobal: true,
	        useCloseButton: true,
	        autoHideTimeout: 1500,
	        hideOnTap: true
	      }, "copy").show();
	    }
	  }, {
	    key: "quoteMessage",
	    value: function quoteMessage(id) {
	      var _this24 = this;

	      this.store.dispatch('dialogues/update', {
	        dialogId: this.controller.getDialogId(),
	        fields: {
	          quoteId: id
	        }
	      }).then(function () {
	        if (_this24.store.state.application.mobile.keyboardShow) {
	          setTimeout(function () {
	            return _this24.controller.emit(im_const.EventType.dialog.scrollToBottom, {
	              duration: 300,
	              cancelIfScrollChange: true
	            });
	          }, 300);
	        } else {
	          _this24.setTextFocus();
	        }
	      });
	    }
	  }, {
	    key: "reactMessage",
	    value: function reactMessage(id, reaction) {
	      BXMobileApp.Events.postToComponent('chatbackground::task::action', ['reactMessage', 'reactMessage|' + id, {
	        messageId: id,
	        action: reaction.action === 'auto' ? 'auto' : reaction.action === 'set' ? 'plus' : 'minus'
	      }, false, 1000], 'background');

	      if (reaction.action === 'set') {
	        setTimeout(function () {
	          return app.exec("callVibration");
	        }, 200);
	      }
	    }
	  }, {
	    key: "openMessageReactionList",
	    value: function openMessageReactionList(id, reactions) {
	      if (!im_utils.Utils.dialog.isChatId(this.controller.getDialogId())) {
	        return false;
	      }

	      var users = [];

	      for (var reaction in reactions) {
	        if (!reactions.hasOwnProperty(reaction)) {
	          continue;
	        }

	        users = users.concat(reactions[reaction]);
	      }

	      this.openUserList({
	        users: users,
	        title: this.localize.MOBILE_MESSAGE_LIST_LIKE
	      });
	    }
	  }, {
	    key: "execMessageKeyboardCommand",
	    value: function execMessageKeyboardCommand(data) {
	      if (data.action !== 'COMMAND') {
	        return false;
	      }

	      var _data$params = data.params,
	          dialogId = _data$params.dialogId,
	          messageId = _data$params.messageId,
	          botId = _data$params.botId,
	          command = _data$params.command,
	          params = _data$params.params;
	      this.restClient.callMethod(im_const.RestMethod.imMessageCommand, {
	        'MESSAGE_ID': messageId,
	        'DIALOG_ID': dialogId,
	        'BOT_ID': botId,
	        'COMMAND': command,
	        'COMMAND_PARAMS': params
	      });
	      return true;
	    }
	  }, {
	    key: "execMessageOpenChatTeaser",
	    value: function execMessageOpenChatTeaser(data) {
	      var _this25 = this;

	      this.controller.joinParentChat(data.message.id, 'chat' + data.message.params.CHAT_ID).then(function (dialogId) {
	        _this25.openDialog(dialogId);
	      }).catch(function () {});
	      return true;
	    }
	  }, {
	    key: "quoteMessageClear",
	    value: function quoteMessageClear() {
	      this.store.dispatch('dialogues/update', {
	        dialogId: this.controller.getDialogId(),
	        fields: {
	          quoteId: 0
	        }
	      });
	    }
	  }, {
	    key: "editMessage",
	    value: function editMessage(id) {
	      var _this26 = this;

	      //if (!this.checkEditMessage(messageId, 'edit'))
	      //	return false;
	      var message = this.store.getters['messages/getMessage'](this.controller.getChatId(), id);
	      this.store.dispatch('application/set', {
	        mobile: {
	          keyboardShow: true
	        }
	      });
	      var siteDir = this.localize.SITE_DIR ? this.localize.SITE_DIR : '/';
	      app.exec('showPostForm', {
	        mentionButton: {
	          dataSource: {
	            return_full_mode: "YES",
	            outsection: "NO",
	            multiple: "NO",
	            alphabet_index: "YES",
	            url: siteDir + 'mobile/index.php?mobile_action=get_user_list'
	          }
	        },
	        smileButton: {},
	        message: {
	          text: message.text
	        },
	        okButton: {
	          callback: function callback(data) {
	            return _this26.editMessageSend(id, data.text);
	          },
	          name: BX.message('MOBILE_EDIT_SAVE')
	        },
	        cancelButton: {
	          callback: function callback() {
	            _this26.store.dispatch('application/set', {
	              mobile: {
	                keyboardShow: false
	              }
	            });
	          },
	          name: BX.message('MOBILE_EDIT_CANCEL')
	        }
	      });
	    }
	  }, {
	    key: "editMessageSend",
	    value: function editMessageSend(id, text) {
	      this.restClient.callMethod(im_const.RestMethod.imMessageUpdate, {
	        'MESSAGE_ID': id,
	        'MESSAGE': text
	      }, null, null, im_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imMessageUpdate,
	        data: {
	          timMessageType: 'text'
	        },
	        dialog: this.controller.getDialogData()
	      }));
	    }
	  }, {
	    key: "deleteMessage",
	    value: function deleteMessage(id) {
	      var _this27 = this;

	      var message = this.store.getters['messages/getMessage'](this.controller.getChatId(), id);
	      var files = this.store.getters['files/getList'](this.controller.getChatId());
	      var messageText = im_utils.Utils.text.purify(message.text, message.params, files, this.localize);
	      messageText = messageText.length > 50 ? messageText.substr(0, 47) + '...' : messageText;
	      app.confirm({
	        title: this.localize.MOBILE_MESSAGE_MENU_DELETE_CONFIRM,
	        text: messageText ? '"' + messageText + '"' : '',
	        buttons: [this.localize.MOBILE_MESSAGE_MENU_DELETE_YES, this.localize.MOBILE_MESSAGE_MENU_DELETE_NO],
	        callback: function callback(button) {
	          if (button === 1) {
	            _this27.deleteMessageSend(id);
	          }
	        }
	      });
	    }
	  }, {
	    key: "deleteMessageSend",
	    value: function deleteMessageSend(id) {
	      this.restClient.callMethod(im_const.RestMethod.imMessageDelete, {
	        'MESSAGE_ID': id
	      }, null, null, im_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imMessageDelete,
	        data: {},
	        dialog: this.controller.getDialogData(this.controller.getDialogId())
	      }));
	    }
	  }, {
	    key: "insertText",
	    value: function insertText(params) {
	      var _this28 = this;

	      BXMobileApp.UI.Page.TextPanel.getText(function (text) {
	        text = text.toString().trim();
	        text = text ? text + ' ' + params.text : params.text;

	        _this28.setText(text);

	        _this28.setTextFocus();
	      });
	    }
	  }, {
	    key: "setText",
	    value: function setText() {
	      var text = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      text = text.toString();

	      if (text) {
	        BXMobileApp.UI.Page.TextPanel.setText(text);
	      } else {
	        BXMobileApp.UI.Page.TextPanel.clear();
	      }

	      this.setTextareaMessage({
	        message: text
	      });
	      console.log('Set new text in textarea', text ? text : '-- empty --');
	    }
	  }, {
	    key: "clearText",
	    value: function clearText() {
	      this.setText();
	    }
	  }, {
	    key: "setTextFocus",
	    value: function setTextFocus() {
	      if (!this.store.state.application.mobile.keyboardShow) {
	        BXMobileApp.UI.Page.TextPanel.focus();
	      }
	    }
	  }, {
	    key: "getHost",
	    value: function getHost() {
	      return currentDomain || '';
	    }
	  }, {
	    key: "getSiteId",
	    value: function getSiteId() {
	      return 's1';
	    }
	  }, {
	    key: "isBackground",
	    value: function isBackground() {
	      if ((typeof BXMobileAppContext === "undefined" ? "undefined" : babelHelpers.typeof(BXMobileAppContext)) !== "object") {
	        return false;
	      }

	      if (typeof BXMobileAppContext.isAppActive === "function" && !BXMobileAppContext.isAppActive()) {
	        return true;
	      }

	      if (typeof BXMobileAppContext.isBackground === "function") {
	        return BXMobileAppContext.isBackground();
	      }

	      return false;
	    }
	    /* endregion 05. Templates and template interaction */

	    /* region 05. Interaction and utils */

	  }, {
	    key: "executeBackgroundTaskSuccess",
	    value: function executeBackgroundTaskSuccess(action, _data) {
	      var successObject = {
	        error: function error() {
	          return false;
	        },
	        data: function data() {
	          return _data.result;
	        }
	      };
	      console.log('Dialog.executeBackgroundTaskSuccess', action, _data);

	      if (action === 'sendMessage') {
	        this.executeRestAnswer(im_const.RestMethodHandler.imMessageAdd, successObject, _data.extra);
	      } else if (action === 'readMessage') {
	        this.processMarkReadMessages();
	      }
	    }
	  }, {
	    key: "executeBackgroundTaskFailure",
	    value: function executeBackgroundTaskFailure(action, data) {
	      var errorObject = {
	        error: function error() {
	          return {
	            error: data.code,
	            error_description: data.text,
	            ex: {
	              status: data.status
	            }
	          };
	        },
	        data: function data() {
	          return false;
	        }
	      };
	      console.log('Dialog.executeBackgroundTaskFailure', action, data);

	      if (action === 'sendMessage') {
	        this.executeRestAnswer(im_const.RestMethodHandler.imMessageAdd, errorObject, data.extra);
	      }
	    }
	    /* endregion 05. Interaction and utils */

	    /* region 06. Interaction and utils */

	  }, {
	    key: "setError",
	    value: function setError() {
	      var code = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      var description = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
	      console.error("MobileChat.error: ".concat(code, " (").concat(description, ")"));
	      var localizeDescription = '';

	      if (code.endsWith('LOCALIZED')) {
	        localizeDescription = description;
	      }

	      this.store.commit('application/set', {
	        error: {
	          active: true,
	          code: code,
	          description: localizeDescription
	        }
	      });
	    }
	  }, {
	    key: "clearError",
	    value: function clearError() {
	      this.store.commit('application/set', {
	        error: {
	          active: false,
	          code: '',
	          description: ''
	        }
	      });
	    }
	  }, {
	    key: "addLocalize",
	    value: function addLocalize(phrases) {
	      if (babelHelpers.typeof(phrases) !== "object" || !phrases) {
	        return false;
	      }

	      for (var name in phrases) {
	        if (phrases.hasOwnProperty(name)) {
	          this.localize[name] = phrases[name];
	        }
	      }

	      return true;
	    }
	  }, {
	    key: "getLocalize",
	    value: function getLocalize(name) {
	      var phrase = '';

	      if (typeof name === 'undefined') {
	        return this.localize;
	      } else if (typeof this.localize[name.toString()] === 'undefined') {
	        console.warn("MobileChat.getLocalize: message with code '".concat(name.toString(), "' is undefined."));
	      } else {
	        phrase = this.localize[name];
	      }

	      return phrase;
	    }
	    /* endregion 06. Interaction and utils */

	  }]);
	  return Dialog;
	}();

	/**
	 * Bitrix im dialog mobile
	 * Dialog vue component
	 *
	 * @package bitrix
	 * @subpackage mobile
	 * @copyright 2001-2019 Bitrix
	 */
	/**
	 * @notice Do not mutate or clone this component! It is under development.
	 */

	ui_vue.Vue.component('bx-messenger', {
	  data: function data() {
	    return {
	      dialogState: 'loading'
	    };
	  },
	  computed: babelHelpers.objectSpread({
	    EventType: function EventType() {
	      return im_const.EventType;
	    },
	    localize: function localize() {
	      return Object.assign({}, ui_vue.Vue.getFilteredPhrases('MOBILE_CHAT_', this.$root.$bitrixMessages), ui_vue.Vue.getFilteredPhrases('IM_UTILS_', this.$root.$bitrixMessages));
	    },
	    widgetClassName: function widgetClassName(state) {
	      var className = ['bx-mobilechat-wrapper'];

	      if (this.showMessageDialog) {
	        className.push('bx-mobilechat-chat-start');
	      }

	      return className.join(' ');
	    },
	    quotePanelData: function quotePanelData() {
	      var result = {
	        id: 0,
	        title: '',
	        description: '',
	        color: ''
	      };

	      if (!this.showMessageDialog || !this.dialog.quoteId) {
	        return result;
	      }

	      var message = this.$store.getters['messages/getMessage'](this.dialog.chatId, this.dialog.quoteId);

	      if (!message) {
	        return result;
	      }

	      var user = this.$store.getters['users/get'](message.authorId);
	      var files = this.$store.getters['files/getList'](this.dialog.chatId);
	      return {
	        id: this.dialog.quoteId,
	        title: message.params.NAME ? message.params.NAME : user ? user.name : '',
	        color: user ? user.color : '',
	        description: im_utils.Utils.text.purify(message.text, message.params, files, this.localize)
	      };
	    },
	    isDialog: function isDialog() {
	      return im_utils.Utils.dialog.isChatId(this.dialog.dialogId);
	    },
	    isGestureQuoteSupported: function isGestureQuoteSupported() {
	      if (this.dialog && this.dialog.type === 'announcement' && !this.dialog.managerList.includes(this.application.common.userId)) {
	        return false;
	      }

	      return ChatPerformance.isGestureQuoteSupported();
	    },
	    isDarkBackground: function isDarkBackground() {
	      return this.application.options.darkBackground;
	    },
	    showMessageDialog: function showMessageDialog() {
	      var _this = this;

	      var result = this.messageCollection && this.messageCollection.length > 0;
	      var timeout = ChatPerformance.getDialogShowTimeout();

	      if (result) {
	        if (timeout > 0) {
	          clearTimeout(this.dialogStateTimeout);
	          this.dialogStateTimeout = setTimeout(function () {
	            _this.dialogState = 'show';
	          }, timeout);
	        } else {
	          this.dialogState = 'show';
	        }
	      } else if (this.dialog && this.dialog.init) {
	        if (timeout > 0) {
	          clearTimeout(this.dialogStateTimeout);
	          this.dialogStateTimeout = setTimeout(function () {
	            _this.dialogState = 'empty';
	          }, timeout);
	        } else {
	          this.dialogState = 'empty';
	        }
	      } else {
	        this.dialogState = 'loading';
	      }

	      return result;
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    application: function application(state) {
	      return state.application;
	    },
	    dialog: function dialog(state) {
	      return state.dialogues.collection[state.application.dialog.dialogId];
	    },
	    messageCollection: function messageCollection(state) {
	      return state.messages.collection[state.application.dialog.chatId];
	    }
	  })),
	  methods: {
	    logEvent: function logEvent(name) {
	      for (var _len = arguments.length, params = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
	        params[_key - 1] = arguments[_key];
	      }

	      im_tools_logger.Logger.info.apply(im_tools_logger.Logger, [name].concat(params));
	    },
	    onDialogRequestHistory: function onDialogRequestHistory(event) {
	      this.$root.$bitrixApplication.getDialogHistory(event.lastId);
	    },
	    onDialogRequestUnread: function onDialogRequestUnread(event) {
	      this.$root.$bitrixApplication.getDialogUnread(event.lastId);
	    },
	    onDialogMessageClickByUserName: function onDialogMessageClickByUserName(event) {
	      this.$root.$bitrixApplication.replyToUser(event.user.id, event.user);
	    },
	    onDialogMessageClickByUploadCancel: function onDialogMessageClickByUploadCancel(event) {
	      this.$root.$bitrixApplication.cancelUploadFile(event.file.id);
	    },
	    onDialogMessageClickByCommand: function onDialogMessageClickByCommand(event) {
	      if (event.type === 'put') {
	        this.$root.$bitrixApplication.insertText({
	          text: event.value + ' '
	        });
	      } else if (event.type === 'send') {
	        this.$root.$bitrixApplication.addMessage(event.value);
	      } else {
	        im_tools_logger.Logger.warn('Unprocessed command', event);
	      }
	    },
	    onDialogMessageClickByMention: function onDialogMessageClickByMention(event) {
	      if (event.type === 'USER') {
	        this.$root.$bitrixApplication.openProfile(event.value);
	      } else if (event.type === 'CHAT') {
	        this.$root.$bitrixApplication.openDialog(event.value);
	      } else if (event.type === 'CALL') {
	        this.$root.$bitrixApplication.openPhoneMenu(event.value);
	      }
	    },
	    onDialogMessageMenuClick: function onDialogMessageMenuClick(event) {
	      im_tools_logger.Logger.warn('Message menu:', event);
	      this.$root.$bitrixApplication.openMessageMenu(event.message);
	    },
	    onDialogMessageRetryClick: function onDialogMessageRetryClick(event) {
	      im_tools_logger.Logger.warn('Message retry:', event);
	      this.$root.$bitrixApplication.retrySendMessage(event.message);
	    },
	    onDialogReadMessage: function onDialogReadMessage(event) {
	      this.$root.$bitrixApplication.readMessage(event.id);
	    },
	    onDialogReadedListClick: function onDialogReadedListClick(event) {
	      this.$root.$bitrixApplication.openReadedList(event.list);
	    },
	    onDialogQuoteMessage: function onDialogQuoteMessage(event) {
	      this.$root.$bitrixApplication.quoteMessage(event.message.id);
	    },
	    onDialogMessageReactionSet: function onDialogMessageReactionSet(event) {
	      this.$root.$bitrixApplication.reactMessage(event.message.id, event.reaction);
	    },
	    onDialogMessageReactionListOpen: function onDialogMessageReactionListOpen(event) {
	      this.$root.$bitrixApplication.openMessageReactionList(event.message.id, event.values);
	    },
	    onDialogMessageClickByKeyboardButton: function onDialogMessageClickByKeyboardButton(event) {
	      this.$root.$bitrixApplication.execMessageKeyboardCommand(event);
	    },
	    onDialogMessageClickByChatTeaser: function onDialogMessageClickByChatTeaser(event) {
	      this.$root.$bitrixApplication.execMessageOpenChatTeaser(event);
	    },
	    onDialogClick: function onDialogClick(event) {},
	    onQuotePanelClose: function onQuotePanelClose() {
	      this.$root.$bitrixApplication.quoteMessageClear();
	    }
	  },
	  template: "\n\t\t<div :class=\"widgetClassName\">\n\t\t\t<div :class=\"['bx-mobilechat-box', {'bx-mobilechat-box-dark-background': isDarkBackground}]\">\n\t\t\t\t<template v-if=\"application.error.active\">\n\t\t\t\t\t<bx-messenger-body-error/>\n\t\t\t\t</template>\t\t\t\n\t\t\t\t<template v-else>\n\t\t\t\t\t<bx-pull-status/>\n\t\t\t\t\t<div :class=\"['bx-mobilechat-body', {'bx-mobilechat-body-with-message': dialogState == 'show'}]\" key=\"with-message\">\n\t\t\t\t\t\t<template v-if=\"dialogState == 'loading'\">\n\t\t\t\t\t\t\t<bx-messenger-body-loading/>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else-if=\"dialogState == 'empty'\">\n\t\t\t\t\t\t\t<bx-messenger-body-empty/>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t<div class=\"bx-mobilechat-dialog\">\n\t\t\t\t\t\t\t\t<bx-messenger-dialog\n\t\t\t\t\t\t\t\t\t:userId=\"application.common.userId\" \n\t\t\t\t\t\t\t\t\t:dialogId=\"application.dialog.dialogId\"\n\t\t\t\t\t\t\t\t\t:chatId=\"application.dialog.chatId\"\n\t\t\t\t\t\t\t\t\t:messageLimit=\"application.dialog.messageLimit\"\n\t\t\t\t\t\t\t\t\t:messageExtraCount=\"application.dialog.messageExtraCount\"\n\t\t\t\t\t\t\t\t\t:enableReadMessages=\"application.dialog.enableReadMessages\"\n\t\t\t\t\t\t\t\t\t:enableReactions=\"true\"\n\t\t\t\t\t\t\t\t\t:enableDateActions=\"false\"\n\t\t\t\t\t\t\t\t\t:enableCreateContent=\"false\"\n\t\t\t\t\t\t\t\t\t:enableGestureQuote=\"application.options.quoteEnable\"\n\t\t\t\t\t\t\t\t\t:enableGestureQuoteFromRight=\"application.options.quoteFromRight\"\n\t\t\t\t\t\t\t\t\t:enableGestureMenu=\"true\"\n\t\t\t\t\t\t\t\t\t:showMessageUserName=\"isDialog\"\n\t\t\t\t\t\t\t\t\t:showMessageAvatar=\"isDialog\"\n\t\t\t\t\t\t\t\t\t:showMessageMenu=\"false\"\n\t\t\t\t\t\t\t\t\t:listenEventScrollToBottom=\"EventType.dialog.scrollToBottom\"\n\t\t\t\t\t\t\t\t\t:listenEventRequestHistory=\"EventType.dialog.requestHistoryResult\"\n\t\t\t\t\t\t\t\t\t:listenEventRequestUnread=\"EventType.dialog.requestUnreadResult\"\n\t\t\t\t\t\t\t\t\t:listenEventSendReadMessages=\"EventType.dialog.sendReadMessages\"\n\t\t\t\t\t\t\t\t\t@readMessage=\"onDialogReadMessage\"\n\t\t\t\t\t\t\t\t\t@quoteMessage=\"onDialogQuoteMessage\"\n\t\t\t\t\t\t\t\t\t@requestHistory=\"onDialogRequestHistory\"\n\t\t\t\t\t\t\t\t\t@requestUnread=\"onDialogRequestUnread\"\n\t\t\t\t\t\t\t\t\t@clickByCommand=\"onDialogMessageClickByCommand\"\n\t\t\t\t\t\t\t\t\t@clickByMention=\"onDialogMessageClickByMention\"\n\t\t\t\t\t\t\t\t\t@clickByUserName=\"onDialogMessageClickByUserName\"\n\t\t\t\t\t\t\t\t\t@clickByMessageMenu=\"onDialogMessageMenuClick\"\n\t\t\t\t\t\t\t\t\t@clickByMessageRetry=\"onDialogMessageRetryClick\"\n\t\t\t\t\t\t\t\t\t@clickByUploadCancel=\"onDialogMessageClickByUploadCancel\"\n\t\t\t\t\t\t\t\t\t@clickByReadedList=\"onDialogReadedListClick\"\n\t\t\t\t\t\t\t\t\t@setMessageReaction=\"onDialogMessageReactionSet\"\n\t\t\t\t\t\t\t\t\t@openMessageReactionList=\"onDialogMessageReactionListOpen\"\n\t\t\t\t\t\t\t\t\t@clickByKeyboardButton=\"onDialogMessageClickByKeyboardButton\"\n\t\t\t\t\t\t\t\t\t@clickByChatTeaser=\"onDialogMessageClickByChatTeaser\"\n\t\t\t\t\t\t\t\t\t@click=\"onDialogClick\"\n\t\t\t\t\t\t\t\t />\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<bx-messenger-quote-panel :id=\"quotePanelData.id\" :title=\"quotePanelData.title\" :description=\"quotePanelData.description\" :color=\"quotePanelData.color\" @close=\"onQuotePanelClose\"/>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Body error component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.component('bx-messenger-body-error', {
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return Object.freeze({
	        MOBILE_CHAT_ERROR_TITLE: this.$root.$bitrixMessages.MOBILE_CHAT_ERROR_TITLE,
	        MOBILE_CHAT_ERROR_DESC: this.$root.$bitrixMessages.MOBILE_CHAT_ERROR_DESC
	      });
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    application: function application(state) {
	      return state.application;
	    }
	  })),
	  template: "\n\t\t<div class=\"bx-mobilechat-body\" key=\"error-body\">\n\t\t\t<div class=\"bx-mobilechat-warning-window\">\n\t\t\t\t<div class=\"bx-mobilechat-warning-icon\"></div>\n\t\t\t\t<template v-if=\"application.error.description\"> \n\t\t\t\t\t<div class=\"bx-mobilechat-help-title bx-mobilechat-help-title-sm bx-mobilechat-warning-msg\" v-html=\"application.error.description\"></div>\n\t\t\t\t</template> \n\t\t\t\t<template v-else>\n\t\t\t\t\t<div class=\"bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-warning-msg\">{{localize.MOBILE_CHAT_ERROR_TITLE}}</div>\n\t\t\t\t\t<div class=\"bx-mobilechat-help-title bx-mobilechat-help-title-sm bx-mobilechat-warning-msg\">{{localize.MOBILE_CHAT_ERROR_DESC}}</div>\n\t\t\t\t</template> \n\t\t\t</div>\n\t\t</div>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Body loading component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.component('bx-messenger-body-loading', {
	  computed: {
	    localize: function localize() {
	      return Object.freeze({
	        MOBILE_CHAT_LOADING: this.$root.$bitrixMessages.MOBILE_CHAT_LOADING
	      });
	    }
	  },
	  template: "\n\t\t<div class=\"bx-mobilechat-loading-window\">\n\t\t\t<svg class=\"bx-mobilechat-loading-circular\" viewBox=\"25 25 50 50\">\n\t\t\t\t<circle class=\"bx-mobilechat-loading-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-miterlimit=\"10\"/>\n\t\t\t\t<circle class=\"bx-mobilechat-loading-inner-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-miterlimit=\"10\"/>\n\t\t\t</svg>\n\t\t\t<h3 class=\"bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-loading-msg\">{{localize.MOBILE_CHAT_LOADING}}</h3>\n\t\t</div>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Body loading component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.component('bx-messenger-body-empty', {
	  computed: {
	    localize: function localize() {
	      return Object.freeze({
	        MOBILE_CHAT_EMPTY: this.$root.$bitrixMessages.MOBILE_CHAT_EMPTY
	      });
	    }
	  },
	  template: "\n\t\t<div class=\"bx-mobilechat-loading-window\">\n\t\t\t<h3 class=\"bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-loading-msg\">{{localize.MOBILE_CHAT_EMPTY}}</h3>\n\t\t</div>\n\t"
	});

	/**
	 * Bitrix im dialog mobile
	 * Registry class
	 *
	 * @package bitrix
	 * @subpackage mobile
	 * @copyright 2001-2019 Bitrix
	 */

	exports.Dialog = Dialog;

}((this.BX.Messenger.Runtime = this.BX.Messenger.Runtime || {}),window,BX,BX,BX.Messenger.Model,BX.Messenger.Controller,BX.Messenger.Provider.Pull,BX.Messenger.Provider.Pull,BX.Messenger,BX.Messenger,BX.Messenger,BX.Messenger.Const,BX.Messenger,window,window,BX,BX));
//# sourceMappingURL=dialog.bundle.js.map
