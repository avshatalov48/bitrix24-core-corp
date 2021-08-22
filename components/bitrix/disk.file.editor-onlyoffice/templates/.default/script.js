this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,main_popup,ui_buttons,disk_users,main_core_events,disk_sharingLegacyPopup,disk_externalLink,pull_client,main_core) {
	'use strict';

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var ALLOWED_ATTEMPTS_TO_GET_USER_INFO = 3;
	var SECONDS_TO_ACTUALIZE_ONLINE = 25;

	var _makeLinkAbsolute = /*#__PURE__*/new WeakSet();

	var UserManager = /*#__PURE__*/function () {
	  function UserManager(options) {
	    babelHelpers.classCallCheck(this, UserManager);

	    _makeLinkAbsolute.add(this);

	    babelHelpers.defineProperty(this, "userBoxNode", null);
	    babelHelpers.defineProperty(this, "context", null);
	    babelHelpers.defineProperty(this, "alreadySaidHi", false);
	    this.users = new BX.Disk.Users([]);
	    this.badAttempts = new Map();
	    this.context = options.context;
	    this.userBoxNode = options.userBoxNode;
	    this.alreadySaidHi = false;
	    this.add(this.context.currentUser);
	    this.bindEvents();
	  }

	  babelHelpers.createClass(UserManager, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;

	      main_core_events.EventEmitter.subscribe('onPullStatus', function (event) {
	        if (event.getData()[0] === 'online') {
	          _this.handleWhenPullConnected();
	        }
	      });
	    }
	  }, {
	    key: "handleWhenPullConnected",
	    value: function handleWhenPullConnected() {
	      if (!this.sentGreetings()) {
	        this.sendHiToUsers();
	        setInterval(this.actualizeOnline.bind(this), 1000 * SECONDS_TO_ACTUALIZE_ONLINE);
	      }
	    }
	  }, {
	    key: "actualizeOnline",
	    value: function actualizeOnline() {
	      this.refineUsersByOnline();

	      if (!this.sentGreetings()) {
	        this.sendHiToUsers();
	      } else {
	        this.sendPingToUsers();
	      }
	    }
	  }, {
	    key: "sentGreetings",
	    value: function sentGreetings() {
	      return this.alreadySaidHi;
	    }
	  }, {
	    key: "sendHiToUsers",
	    value: function sendHiToUsers() {
	      if (!pull_client.PULL.isConnected()) {
	        return;
	      }

	      pull_client.PULL.sendMessage([-1], 'disk', 'hiToDocument', {
	        user: {
	          id: this.context.currentUser.id,
	          name: this.context.currentUser.name,
	          avatar: _classPrivateMethodGet(this, _makeLinkAbsolute, _makeLinkAbsolute2).call(this, this.context.currentUser.avatar)
	        }
	      });
	      this.alreadySaidHi = true;
	    }
	  }, {
	    key: "sendWelcomeToUser",
	    value: function sendWelcomeToUser() {
	      if (!pull_client.PULL.isConnected()) {
	        return;
	      }

	      pull_client.PULL.sendMessage([-1], 'disk', 'welcomeToDocument', {
	        user: {
	          id: this.context.currentUser.id,
	          name: this.context.currentUser.name,
	          avatar: _classPrivateMethodGet(this, _makeLinkAbsolute, _makeLinkAbsolute2).call(this, this.context.currentUser.avatar)
	        }
	      });
	    }
	  }, {
	    key: "sendPingToUsers",
	    value: function sendPingToUsers() {
	      if (!pull_client.PULL.isConnected()) {
	        return;
	      }

	      pull_client.PULL.sendMessage([-1], 'disk', 'pingDocument', {
	        fromUserId: this.context.currentUser.id,
	        infoToken: this.context.currentUser.infoToken
	      });
	    }
	  }, {
	    key: "add",
	    value: function add(user) {
	      if (!this.users.hasUser(user.id)) {
	        this.users.addUser(user);
	        console.log('Hi new user!', user.id);
	      }

	      this.updateOnline(user.id);
	      this.renderBox();
	    }
	  }, {
	    key: "updateOnline",
	    value: function updateOnline(userId) {
	      if (this.users.hasUser(userId)) {
	        this.users.getUser(userId).onlineAt = Date.now();
	      }
	    }
	  }, {
	    key: "getUserInfo",
	    value: function getUserInfo(userId, infoToken) {
	      var _this2 = this;

	      if (this.badAttempts.get(userId) >= ALLOWED_ATTEMPTS_TO_GET_USER_INFO) {
	        return new Promise(function (resolve, reject) {
	          reject({
	            status: 'blocked'
	          });
	        });
	      }

	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runComponentAction('bitrix:disk.file.editor-onlyoffice', 'getUserInfo', {
	          mode: 'ajax',
	          json: {
	            documentSessionId: _this2.context.documentSession.id,
	            documentSessionHash: _this2.context.documentSession.hash,
	            userId: userId,
	            infoToken: infoToken
	          }
	        }).then(function (response) {
	          if (response.status === 'success') {
	            _this2.badAttempts.delete(userId);

	            resolve(response.data.user);
	          }
	        }, function (response) {
	          var attempts = _this2.badAttempts.get(userId) || 0;

	          _this2.badAttempts.set(userId, attempts + 1);

	          console.log(_this2.badAttempts);
	          reject(response);
	        });
	      });
	    }
	  }, {
	    key: "has",
	    value: function has(userId) {
	      return this.users.hasUser(userId);
	    }
	  }, {
	    key: "remove",
	    value: function remove(userId) {
	      if (userId === this.context.currentUser.id) {
	        return;
	      }

	      this.users.deleteUser(userId);
	      this.renderBox();
	    }
	  }, {
	    key: "refineUsersByOnline",
	    value: function refineUsersByOnline() {
	      var _this3 = this;

	      var secondsToOffline = 1000 * (SECONDS_TO_ACTUALIZE_ONLINE + 1) * 2;
	      var now = Date.now();
	      this.users.forEach(function (user) {
	        if (now - user.onlineAt > secondsToOffline) {
	          _this3.remove(user.id);
	        }
	      });
	    }
	  }, {
	    key: "renderBox",
	    value: function renderBox() {
	      if (!this.userBoxNode.childElementCount) {
	        this.userBoxNode.appendChild(this.users.getContainer());
	      }
	    }
	  }]);
	  return UserManager;
	}();

	function _makeLinkAbsolute2(link) {
	  if (link.includes('http://') || link.includes('https://')) {
	    return link;
	  }

	  return document.location.origin + link;
	}

	var BaseCommandHandler = /*#__PURE__*/function () {
	  function BaseCommandHandler(commandOptions) {
	    babelHelpers.classCallCheck(this, BaseCommandHandler);
	    babelHelpers.defineProperty(this, "options", null);
	    babelHelpers.defineProperty(this, "userManager", null);
	    this.options = commandOptions;
	    this.userManager = commandOptions.userManager;
	    this.context = commandOptions.context;
	  }

	  babelHelpers.createClass(BaseCommandHandler, [{
	    key: "getModuleId",
	    value: function getModuleId() {
	      return 'disk';
	    }
	  }, {
	    key: "getSubscriptionType",
	    value: function getSubscriptionType() {
	      return pull_client.PullClient.SubscriptionType.Server;
	    }
	  }, {
	    key: "filterCurrentObject",
	    value: function filterCurrentObject(handler) {
	      var _this = this;

	      return function (data) {
	        if (_this.context.object.id !== data.object.id) {
	          return;
	        }

	        return handler(data);
	      };
	    }
	  }, {
	    key: "isCurrentUser",
	    value: function isCurrentUser(userId) {
	      return this.context.currentUser.id === userId;
	    }
	  }]);
	  return BaseCommandHandler;
	}();

	var ClientCommandHandler = /*#__PURE__*/function (_BaseCommandHandler) {
	  babelHelpers.inherits(ClientCommandHandler, _BaseCommandHandler);

	  function ClientCommandHandler() {
	    babelHelpers.classCallCheck(this, ClientCommandHandler);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ClientCommandHandler).apply(this, arguments));
	  }

	  babelHelpers.createClass(ClientCommandHandler, [{
	    key: "getSubscriptionType",
	    value: function getSubscriptionType() {
	      return pull_client.PullClient.SubscriptionType.Client;
	    }
	  }, {
	    key: "getMap",
	    value: function getMap() {
	      return {
	        exitDocument: this.handleExitDocument.bind(this),
	        pingDocument: this.handlePingDocument.bind(this),
	        hiToDocument: this.handleHiToDocument.bind(this),
	        welcomeToDocument: this.handleWelcomeToDocument.bind(this)
	      };
	    }
	  }, {
	    key: "handleExitDocument",
	    value: function handleExitDocument(data) {
	      console.log('exitDocument', data);
	      var fromUserId = data.fromUserId;

	      if (!this.isCurrentUser(fromUserId)) {
	        this.userManager.remove(fromUserId);
	      }
	    }
	  }, {
	    key: "handleWelcomeToDocument",
	    value: function handleWelcomeToDocument(data) {
	      console.log('handleWelcomeToDocument', data);
	      this.processNewbieInDocument(data);
	    }
	  }, {
	    key: "handleHiToDocument",
	    value: function handleHiToDocument(data) {
	      console.log('handleHiToDocument', data);
	      var newbieAdded = this.processNewbieInDocument(data);

	      if (newbieAdded) {
	        //immediately send welcome to add actual online information for new user.
	        this.userManager.sendWelcomeToUser();
	      }
	    }
	  }, {
	    key: "processNewbieInDocument",
	    value: function processNewbieInDocument(data) {
	      var fromUserId = data.user.id;

	      if (this.isCurrentUser(fromUserId)) {
	        return false;
	      }

	      if (this.userManager.has(fromUserId)) {
	        this.userManager.updateOnline(fromUserId);
	        return false;
	      }

	      this.userManager.add(data.user);
	      return true;
	    }
	  }, {
	    key: "handlePingDocument",
	    value: function handlePingDocument(data) {
	      var _this = this;

	      console.log('handlePingDocument', data);
	      var fromUserId = data.fromUserId;

	      if (this.isCurrentUser(fromUserId)) {
	        return;
	      }

	      if (this.userManager.has(fromUserId)) {
	        this.userManager.updateOnline(fromUserId);
	      } else {
	        if (this.userManager.sentGreetings()) {
	          this.userManager.getUserInfo(data.fromUserId, data.infoToken).then(function (userData) {
	            _this.userManager.add(userData);
	          }, function () {});
	        }
	      }
	    }
	  }]);
	  return ClientCommandHandler;
	}(BaseCommandHandler);

	var OnlyOffice = /*#__PURE__*/function () {
	  function OnlyOffice(editorOptions) {
	    babelHelpers.classCallCheck(this, OnlyOffice);
	    babelHelpers.defineProperty(this, "editor", null);
	    babelHelpers.defineProperty(this, "editorJson", null);
	    babelHelpers.defineProperty(this, "userBoxNode", null);
	    babelHelpers.defineProperty(this, "editorNode", null);
	    babelHelpers.defineProperty(this, "editorWrapperNode", null);
	    babelHelpers.defineProperty(this, "targetNode", null);
	    babelHelpers.defineProperty(this, "documentSession", null);
	    babelHelpers.defineProperty(this, "linkToEdit", null);
	    babelHelpers.defineProperty(this, "pullConfig", null);
	    babelHelpers.defineProperty(this, "editButton", null);
	    babelHelpers.defineProperty(this, "setupSharingButton", null);
	    babelHelpers.defineProperty(this, "documentWasChanged", false);
	    babelHelpers.defineProperty(this, "dontEndCurrentDocumentSession", false);
	    babelHelpers.defineProperty(this, "context", null);
	    babelHelpers.defineProperty(this, "usersInDocument", null);
	    babelHelpers.defineProperty(this, "sharingControlType", null);
	    var options = main_core.Type.isPlainObject(editorOptions) ? editorOptions : {};
	    this.pullConfig = options.pullConfig;
	    this.documentSession = options.documentSession;
	    this.linkToEdit = options.linkToEdit;
	    this.targetNode = options.targetNode;
	    this.userBoxNode = options.userBoxNode;
	    this.editorNode = options.editorNode;
	    this.editorWrapperNode = options.editorWrapperNode;
	    this.editButton = ui_buttons.ButtonManager.createByUniqId(editorOptions.panelButtonUniqIds.edit);
	    this.setupSharingButton = ui_buttons.ButtonManager.createByUniqId(editorOptions.panelButtonUniqIds.setupSharing);
	    this.sharingControlType = editorOptions.sharingControlType;
	    this.context = {
	      currentUser: options.currentUser,
	      documentSession: this.documentSession,
	      object: options.object,
	      attachedObject: options.attachedObject
	    };
	    this.usersInDocument = new UserManager({
	      context: this.context,
	      userBoxNode: this.userBoxNode
	    });
	    this.sendTelemetryEvent('load');
	    this.initializeEditor(options.editorJson);
	    var currentSlider = BX.SidePanel.Instance.getSliderByWindow(window);

	    if (currentSlider) {
	      currentSlider.getData().set('documentSession', this.documentSession);
	      this.loadDiskExtensionInTopWindow();
	    }

	    this.initPull();
	    this.bindEvents();
	  }

	  babelHelpers.createClass(OnlyOffice, [{
	    key: "initPull",
	    value: function initPull() {
	      if (this.pullConfig) {
	        BX.PULL = new pull_client.PullClient({
	          skipStorageInit: true
	        });
	        BX.PULL.start(this.pullConfig);
	      }
	    }
	  }, {
	    key: "sendTelemetryEvent",
	    value: function sendTelemetryEvent(action, data) {
	      data = data || {};
	      var currentSlider = BX.SidePanel.Instance.getSliderByWindow(window);

	      if (!currentSlider) {
	        return;
	      }

	      var currentSliderData = currentSlider.getData();
	      data.action = action;
	      data.uid = currentSliderData.get('uid');
	      data.documentSessionId = this.context.documentSession.id;
	      data.documentSessionHash = this.context.documentSession.hash;
	      data.fileSize = this.context.object.size;
	      BX.Disk.sendTelemetryEvent(data);
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      main_core_events.EventEmitter.subscribe("SidePanel.Slider:onClose", this.handleClose.bind(this));
	      window.addEventListener("beforeunload", this.handleClose.bind(this));

	      if (this.editorJson.document.permissions.edit === true && this.editButton) {
	        if (this.editButton.hasOwnProperty('mainButton')) {
	          this.editButton.getMainButton().bindEvent('click', this.handleClickEditButton.bind(this));
	          var menuWindow = this.editButton.getMenuWindow();
	          var menuItems = main_core.Runtime.clone(menuWindow.getMenuItems());

	          for (var i = 0; i < menuItems.length; i++) {
	            var menuItem = menuItems[i];
	            var menuItemOptions = main_core.Runtime.clone(menuItem.options);
	            menuItemOptions.onclick = this.handleClickEditSubItems.bind(this);
	            menuWindow.removeMenuItem(menuItem.getId());
	            menuWindow.addMenuItem(menuItemOptions);
	          }
	        } else {
	          this.editButton.bindEvent('click', this.handleClickEditButton.bind(this));
	        }
	      }

	      if (this.setupSharingButton) {
	        var _menuWindow = this.setupSharingButton.getMenuWindow();

	        var extLinkOptions = _menuWindow.getMenuItem('ext-link').options;

	        extLinkOptions.onclick = this.handleClickSharingByExternalLink.bind(this);

	        _menuWindow.removeMenuItem('ext-link');

	        _menuWindow.addMenuItem(extLinkOptions);

	        var sharingOptions = _menuWindow.getMenuItem('sharing').options;

	        sharingOptions.onclick = this.handleClickSharing.bind(this);

	        _menuWindow.removeMenuItem('sharing');

	        _menuWindow.addMenuItem(sharingOptions);
	      }

	      pull_client.PULL.subscribe(new ClientCommandHandler({
	        context: this.context,
	        userManager: this.usersInDocument
	      }));
	    }
	  }, {
	    key: "initializeEditor",
	    value: function initializeEditor(options) {
	      this.adjustEditorHeight(options);
	      options.events = {
	        onDocumentStateChange: this.handleDocumentStateChange.bind(this),
	        onDocumentReady: this.handleDocumentReady.bind(this),
	        onMetaChange: this.handleMetaChange.bind(this),
	        onInfo: this.handleInfo.bind(this) // onRequestClose: this.handleClose.bind(this),

	      };

	      if (options.document.permissions.rename) {
	        options.events.onRequestRename = this.handleRequestRename.bind(this);
	      }

	      this.editorJson = options;
	      this.editor = new DocsAPI.DocEditor(this.editorNode.id, options);
	    }
	  }, {
	    key: "adjustEditorHeight",
	    value: function adjustEditorHeight(options) {
	      options.height = document.body.clientHeight - 70 + 'px';
	    }
	  }, {
	    key: "loadDiskExtensionInTopWindow",
	    value: function loadDiskExtensionInTopWindow() {
	      if (window.top !== window && !BX.getClass('window.top.BX.Disk.endEditSession')) {
	        top.BX.loadExt('disk');
	      }
	    }
	  }, {
	    key: "emitEventOnSaved",
	    value: function emitEventOnSaved() {
	      var sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);

	      if (sliderByWindow) {
	        BX.SidePanel.Instance.postMessageAll(window, 'Disk.OnlyOffice:onSaved', {
	          documentSession: this.documentSession,
	          object: this.context.object
	        });
	      }

	      main_core_events.EventEmitter.emit('Disk.OnlyOffice:onSaved', {
	        documentSession: this.documentSession,
	        object: this.context.object
	      });
	    }
	  }, {
	    key: "emitEventOnClosed",
	    value: function emitEventOnClosed() {
	      var sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);
	      var process = 'edit';

	      if (sliderByWindow) {
	        process = sliderByWindow.getData().get('process') || 'edit';
	        BX.SidePanel.Instance.postMessageAll(window, 'Disk.OnlyOffice:onClosed', {
	          documentSession: this.documentSession,
	          object: this.context.object,
	          process: process
	        });
	      }

	      main_core_events.EventEmitter.emit('Disk.OnlyOffice:onClosed', {
	        documentSession: this.documentSession,
	        object: this.context.object,
	        process: process
	      });
	    }
	  }, {
	    key: "handleClickEditButton",
	    value: function handleClickEditButton() {
	      this.handleRequestEditRights();
	    }
	  }, {
	    key: "handleClickSharing",
	    value: function handleClickSharing() {
	      switch (this.sharingControlType) {
	        case disk_sharingLegacyPopup.SharingControlType.WITH_CHANGE_RIGHTS:
	          new disk_sharingLegacyPopup.LegacyPopup().showSharingDetailWithChangeRights({
	            object: this.context.object
	          });
	          break;

	        case disk_sharingLegacyPopup.SharingControlType.WITH_SHARING:
	          new disk_sharingLegacyPopup.LegacyPopup().showSharingDetailWithChangeRights({
	            object: this.context.object
	          });
	          break;

	        case disk_sharingLegacyPopup.SharingControlType.WITHOUT_EDIT:
	          new disk_sharingLegacyPopup.LegacyPopup().showSharingDetailWithoutEdit({
	            object: this.context.object
	          });
	          break;
	      }
	    }
	  }, {
	    key: "handleClickSharingByExternalLink",
	    value: function handleClickSharingByExternalLink() {
	      disk_externalLink.ExternalLink.showPopup(this.context.object.id);
	    }
	  }, {
	    key: "handleClickEditSubItems",
	    value: function handleClickEditSubItems(event, menuItem) {
	      var serviceCode = menuItem.getId();

	      if (serviceCode === 'onlyoffice') {
	        this.handleClickEditButton();
	        return;
	      }

	      BX.Disk.Viewer.Actions.runActionEdit({
	        objectId: this.context.object.id,
	        attachedObjectId: this.context.attachedObject.id,
	        serviceCode: serviceCode
	      });
	    }
	  }, {
	    key: "handleSaveButtonClick",
	    value: function handleSaveButtonClick() {
	      var _this = this;

	      pull_client.PULL.subscribe({
	        moduleId: 'disk',
	        command: 'onlyoffice',
	        callback: function callback(data) {
	          if (data.hash === _this.documentSession.hash) {
	            _this.emitEventOnSaved();

	            window.BX.Disk.showModalWithStatusAction();
	            BX.SidePanel.Instance.close();
	          }
	        }
	      });
	    }
	  }, {
	    key: "handleClose",
	    value: function handleClose() {
	      pull_client.PULL.sendMessage([-1], 'disk', 'exitDocument', {
	        fromUserId: this.context.currentUser.id
	      });
	      this.sendTelemetryEvent('exit');
	      this.emitEventOnClosed();

	      if (this.dontEndCurrentDocumentSession) {
	        return;
	      }

	      top.BX.Disk.endEditSession({
	        id: this.documentSession.id,
	        hash: this.documentSession.hash,
	        documentWasChanged: this.documentWasChanged
	      });
	    }
	  }, {
	    key: "handleDocumentStateChange",
	    value: function handleDocumentStateChange(event) {
	      if (!this.caughtDocumentReady || !this.caughtInfoEvent) {
	        return;
	      }

	      if (Date.now() - Math.max(this.caughtDocumentReady, this.caughtInfoEvent) < 500) {
	        return;
	      }

	      this.documentWasChanged = true;
	    }
	  }, {
	    key: "handleInfo",
	    value: function handleInfo() {
	      this.caughtInfoEvent = Date.now();
	    }
	  }, {
	    key: "handleRequestRename",
	    value: function handleRequestRename(event) {
	      var newName = event.data;
	      main_core.ajax.runAction('disk.api.onlyoffice.renameDocument', {
	        mode: 'ajax',
	        json: {
	          documentSessionId: this.context.documentSession.id,
	          documentSessionHash: this.context.documentSession.hash,
	          newName: newName
	        }
	      });
	    }
	  }, {
	    key: "handleMetaChange",
	    value: function handleMetaChange(event) {}
	  }, {
	    key: "handleDocumentReady",
	    value: function handleDocumentReady() {
	      this.sendTelemetryEvent('ready');
	      this.caughtDocumentReady = Date.now();
	    }
	  }, {
	    key: "handleRequestEditRights",
	    value: function handleRequestEditRights() {
	      this.dontEndCurrentDocumentSession = true;
	      var linkToEdit = BX.util.add_url_param('/bitrix/services/main/ajax.php', {
	        action: 'disk.api.documentService.goToEdit',
	        serviceCode: 'onlyoffice',
	        documentSessionId: this.documentSession.id,
	        documentSessionHash: this.documentSession.hash
	      });

	      if (this.linkToEdit) {
	        linkToEdit = this.linkToEdit;
	      }

	      var currentSlider = BX.SidePanel.Instance.getSliderByWindow(window);

	      if (!currentSlider) {
	        window.location = linkToEdit;
	        return;
	      }

	      var customLeftBoundary = currentSlider.getCustomLeftBoundary();
	      currentSlider.close();
	      BX.SidePanel.Instance.open(linkToEdit, {
	        width: '100%',
	        customLeftBoundary: customLeftBoundary,
	        cacheable: false,
	        allowChangeHistory: false,
	        data: {
	          documentEditor: true
	        }
	      });
	    }
	  }, {
	    key: "getEditor",
	    value: function getEditor() {
	      return this.editor;
	    }
	  }, {
	    key: "getEditorNode",
	    value: function getEditorNode() {
	      return this.editorNode;
	    }
	  }, {
	    key: "getEditorWrapperNode",
	    value: function getEditorWrapperNode() {
	      return this.editorWrapperNode;
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      return this.targetNode;
	    }
	  }]);
	  return OnlyOffice;
	}();

	var ServerCommandHandler = /*#__PURE__*/function (_BaseCommandHandler) {
	  babelHelpers.inherits(ServerCommandHandler, _BaseCommandHandler);

	  function ServerCommandHandler() {
	    babelHelpers.classCallCheck(this, ServerCommandHandler);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ServerCommandHandler).apply(this, arguments));
	  }

	  babelHelpers.createClass(ServerCommandHandler, [{
	    key: "getMap",
	    value: function getMap() {
	      return {
	        onlyoffice: this.filterCurrentObject(this.handleSavedDocument.bind(this))
	      };
	    }
	  }, {
	    key: "handleSavedDocument",
	    value: function handleSavedDocument(data) {
	      console.log('handleSavedDocument', data);
	      main_core.ajax.runAction('disk.api.onlyoffice.continueWithNewSession', {
	        mode: 'ajax',
	        json: {
	          sessionId: this.context.documentSession.id,
	          documentSessionHash: this.context.documentSession.hash
	        }
	      }).then(function (response) {
	        if (response.status === 'success') {
	          document.location.href = response.data.documentSession.link;
	        }
	      });
	    }
	  }]);
	  return ServerCommandHandler;
	}(BaseCommandHandler);

	var Waiting = /*#__PURE__*/function () {
	  function Waiting(editorOptions) {
	    babelHelpers.classCallCheck(this, Waiting);
	    babelHelpers.defineProperty(this, "documentSession", null);
	    babelHelpers.defineProperty(this, "object", null);
	    var options = main_core.Type.isPlainObject(editorOptions) ? editorOptions : {};
	    this.documentSession = options.documentSession;
	    this.object = options.object;
	    var loader = new BX.Loader({
	      target: document.querySelector('#test')
	    });
	    loader.show();
	    this.bindEvents();
	  }

	  babelHelpers.createClass(Waiting, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      pull_client.PULL.subscribe(new ServerCommandHandler({
	        context: {
	          object: this.object,
	          documentSession: this.documentSession
	        },
	        userManager: null
	      }));
	    }
	  }]);
	  return Waiting;
	}();

	exports.OnlyOffice = OnlyOffice;
	exports.Waiting = Waiting;

}((this.BX.Disk.Editor = this.BX.Disk.Editor || {}),BX.Main,BX.UI,BX.Disk,BX.Event,BX.Disk.Sharing,BX.Disk,BX,BX));
//# sourceMappingURL=script.js.map
