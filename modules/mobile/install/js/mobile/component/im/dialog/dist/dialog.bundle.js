(function (exports,im_const,im_model,im_controller) {
	'use strict';

	/* region 00. Startup operations */

	var GetObjectValues = function GetObjectValues(source) {
	  var destination = [];

	  for (var value in source) {
	    if (source.hasOwnProperty(value)) {
	      destination.push(source[value]);
	    }
	  }

	  return destination;
	};
	var RestMethod = Object.freeze({
	  pullServerTime: 'server.time',
	  // TODO: method is not implemented
	  pullConfigGet: 'pull.config.get',
	  // TODO: method is not implemented
	  imMessageAdd: 'im.message.add',
	  imMessageUpdate: 'im.message.update',
	  // TODO: method is not implemented
	  imMessageDelete: 'im.message.delete',
	  // TODO: method is  not implemented
	  imMessageLike: 'im.message.like',
	  // TODO: method is  not implemented
	  imChatGet: 'im.chat.get',
	  // TODO: method is not implemented
	  imChatSendTyping: 'im.chat.sendTyping',
	  // TODO: method is not implemented
	  imDialogMessagesGet: 'im.dialog.messages.get',
	  // TODO: method is not implemented
	  imDialogMessagesUnread: 'im.dialog.messages.unread',
	  // TODO: method is not implemented
	  imDialogRead: 'im.dialog.read',
	  // TODO: method is not implemented
	  diskFolderGet: 'im.disk.folder.get',
	  // TODO: method is not implemented
	  diskFileUpload: 'disk.folder.uploadfile',
	  // TODO: method is not implemented
	  diskFileCommit: 'im.disk.file.commit' // TODO: method is not implemented

	});
	var RestMethodCheck = GetObjectValues(RestMethod);
	/* endregion 01. Constants */

	/* region 03. Dialog interface */

	var MessengerDialog =
	/*#__PURE__*/
	function () {
	  function MessengerDialog() {
	    var _this = this;
	    babelHelpers.classCallCheck(this, MessengerDialog);
	    this.restClient = null;
	    this.pullClient = null;
	    this.offline = false;
	    this.dateFormat = null;
	    this.messagesQueue = [];
	    this.filesQueue = [];
	    this.filesQueueIndex = 0;
	    this.messageLastReadId = null;
	    this.messageReadQueue = [];
	    this.defaultMessageLimit = 20;
	    this.requestMessageLimit = this.defaultMessageLimit;
	    this.rootNode = document.body;
	    this.template = null;
	    /* TODO rewrite to IndexedDB */

	    var serverVariables = BX.Messenger.LocalStorage.get(this.getSiteId(), 0, 'serverVariables', false);

	    if (serverVariables) {
	      this.addLocalize(serverVariables);
	    }

	    this.timer = new BX.Messenger.Timer();
	    this.controller = new im_controller.ApplicationController();
	    var applicationVariables = {
	      common: {
	        siteId: this.getSiteId(),
	        languageId: this.getLanguageId()
	      },
	      device: {
	        type: BX.Messenger.Utils.device.isMobile() ? im_const.DeviceType.mobile : im_const.DeviceType.desktop,
	        orientation: BX.Messenger.Utils.device.getOrientation()
	      },
	      dialog: {
	        messageLimit: this.defaultMessageLimit
	      }
	    };
	    new BX.VuexBuilder().addModel(im_model.ApplicationModel.create().setVariables(applicationVariables)).addModel(im_model.MessagesModel.create()).addModel(im_model.DialoguesModel.create().setVariables({
	      host: this.getHost()
	    }).useDatabase(false)).addModel(im_model.UsersModel.create().setVariables({
	      host: this.getHost(),
	      defaultName: BX.message('IM_MESSENGER_MESSAGE_USER_ANONYM')
	    }).useDatabase(false)).addModel(im_model.FilesModel.create().setVariables({
	      host: this.getHost()
	    }).useDatabase(false)).setDatabaseConfig({
	      type: BX.VuexBuilder.DatabaseType.indexedDb,
	      siteId: this.getSiteId()
	    }).build(function (result) {
	      _this.store = result.store;
	      _this.storeCollector = result.builder;
	      _this.restClient = BX.rest;
	      _this.pullClient = BX.PULL;

	      _this.controller.setPrepareFilesBeforeSaveFunction(_this.prepareFileData);

	      _this.requestData(_this.getDialogId());

	      _this.attachTemplate();
	    });
	  }

	  babelHelpers.createClass(MessengerDialog, [{
	    key: "addLocalize",
	    value: function addLocalize(phrases) {
	      if (babelHelpers.typeof(phrases) !== "object") {
	        return false;
	      }

	      for (var name in phrases) {
	        if (phrases.hasOwnProperty(name)) {
	          BX.message[name] = phrases[name];
	        }
	      }

	      return true;
	    }
	  }, {
	    key: "getHost",
	    value: function getHost() {
	      return location.protocol + '//' + location.host; // TODO read variables
	    }
	  }, {
	    key: "getSiteId",
	    value: function getSiteId() {
	      return 'default'; // TODO read variables
	    }
	  }, {
	    key: "getDialogId",
	    value: function getDialogId() {
	      return 'chat56'; // TODO read variables
	    }
	  }, {
	    key: "getLanguageId",
	    value: function getLanguageId() {
	      return 'ru'; // TODO read variables
	    }
	  }, {
	    key: "requestData",
	    value: function requestData() {}
	  }, {
	    key: "executeRestAnswer",
	    value: function executeRestAnswer(type, result) {}
	  }, {
	    key: "prepareFileData",
	    value: function prepareFileData(files) {
	      {
	          return files;
	        }

	      return files.map(function (file) {
	        if (file.urlPreview) {
	          file.urlPreview = file.urlPreview.replace('http://', 'bx://').replace('https://', 'bx://');
	        }

	        if (file.urlShow) {
	          file.urlShow = file.urlShow.replace('http://', 'bx://').replace('https://', 'bx://');
	        }

	        if (file.urlDownload) {
	          file.urlDownload = file.urlDownload.replace('http://', 'bx://').replace('https://', 'bx://');
	        }

	        return file;
	      });
	    }
	  }, {
	    key: "attachTemplate",
	    value: function attachTemplate() {
	      this.rootNode.innerHTML = '';
	      var widgetContext = this;
	      var restClient = this.restClient;
	      var pullClient = this.pullClient;
	      this.template = BX.Vue.create({
	        el: this.rootNode.firstChild,
	        store: this.store,
	        template: '<bx-mobile-dialog/>',
	        beforeCreate: function beforeCreate() {
	          this.$bitrixWidget = widgetContext;
	          this.$bitrixRestClient = restClient;
	          this.$bitrixMessages = widgetContext.localize;
	        }
	      });
	      return true;
	    }
	  }]);
	  return MessengerDialog;
	}();

	window.MessengerDialog = new MessengerDialog();

}((this.window = this.window || {}),BX.Messenger.Const,BX.Messenger.Model,BX.Messenger.Controller));
//# sourceMappingURL=dialog.bundle.js.map
