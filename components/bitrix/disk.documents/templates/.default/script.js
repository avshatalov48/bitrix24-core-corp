/* eslint-disable */
this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,disk_users,main_polyfill_intersectionobserver,main_loader,main_popup,clipboard,disk_externalLink,disk_sharingLegacyPopup,ui_dialogs_messagebox,ui_ears,main_core_events,main_core,ui_tour) {
	'use strict';

	var intersectionObserver;
	function observeIntersection(entity, callback) {
	  if (!intersectionObserver) {
	    intersectionObserver = new IntersectionObserver(function (entries) {
	      entries.forEach(function (entry) {
	        if (entry.isIntersecting) {
	          intersectionObserver.unobserve(entry.target);
	          var observedCallback = entry.target.observedCallback;
	          delete entry.target.observedCallback;
	          setTimeout(observedCallback);
	        }
	      });
	    }, {
	      threshold: 0
	    });
	  }
	  entity.observedCallback = callback;
	  intersectionObserver.observe(entity);
	}

	var BackendInner = function BackendInner() {
	  babelHelpers.classCallCheck(this, BackendInner);
	};
	babelHelpers.defineProperty(BackendInner, "idsForShared", {});
	babelHelpers.defineProperty(BackendInner, "idsForExternalLinks", {});
	babelHelpers.defineProperty(BackendInner, "sendForInfo", main_core.Runtime.debounce(function () {
	  var requestData = {
	    'shared': BackendInner.idsForShared,
	    'externalLink': BackendInner.idsForExternalLinks
	  };
	  BackendInner.idsForShared = {};
	  BackendInner.idsForExternalLinks = {};
	  var request = {};
	  for (var action in requestData) {
	    if (requestData.hasOwnProperty(action)) {
	      for (var id in requestData[action]) {
	        if (requestData[action].hasOwnProperty(id)) {
	          request[id] = request[id] || [];
	          request[id].push(action);
	        }
	      }
	    }
	  }
	  main_core.ajax.runComponentAction(Backend.component, 'getInfo', {
	    mode: 'ajax',
	    data: {
	      trackedObjectIds: request
	    }
	  }).then(function (_ref) {
	    var data = _ref.data;
	    for (var _action in requestData) {
	      if (requestData.hasOwnProperty(_action)) {
	        for (var _id in requestData[_action]) {
	          if (requestData[_action].hasOwnProperty(_id)) {
	            requestData[_action][_id][0]({
	              data: data[_id][_action]
	            });
	          }
	        }
	      }
	    }
	  })["catch"](function (_ref2) {
	    var errors = _ref2.errors;
	    for (var _action2 in requestData) {
	      if (requestData.hasOwnProperty(_action2)) {
	        for (var _id2 in requestData[_action2]) {
	          if (requestData[_action2].hasOwnProperty(_id2)) {
	            requestData[_action2][_id2][1]({
	              errors: errors
	            });
	          }
	        }
	      }
	    }
	  });
	}, 500));
	var Backend = /*#__PURE__*/function () {
	  function Backend() {
	    babelHelpers.classCallCheck(this, Backend);
	  }
	  babelHelpers.createClass(Backend, null, [{
	    key: "getShared",
	    value: function getShared(id) {
	      return new Promise(function (resolve, reject) {
	        BackendInner.idsForShared[id] = [resolve, reject];
	        BackendInner.sendForInfo();
	      });
	    }
	  }, {
	    key: "getExternalLink",
	    value: function getExternalLink(id) {
	      return new Promise(function (resolve, reject) {
	        BackendInner.idsForExternalLinks[id] = [resolve, reject];
	        BackendInner.sendForInfo();
	      });
	    }
	  }, {
	    key: "getMenuActions",
	    value: function getMenuActions(id) {
	      return main_core.ajax.runComponentAction(Backend.component, 'getMenuActions', {
	        mode: 'ajax',
	        data: {
	          trackedObjectId: id
	        },
	        analyticsLabel: Backend.component + '.gridMenuActions'
	      });
	    }
	  }, {
	    key: "getMenuOpenAction",
	    value: function getMenuOpenAction(id) {
	      return main_core.ajax.runComponentAction(Backend.component, 'getMenuOpenAction', {
	        mode: 'ajax',
	        data: {
	          trackedObjectId: id
	        },
	        analyticsLabel: Backend.component + '.gridMenuOpenAction'
	      });
	    }
	  }, {
	    key: "renameAction",
	    value: function renameAction(id, newName) {
	      return main_core.ajax.runAction('disk.api.trackedObject.rename', {
	        data: {
	          objectId: id,
	          newName: newName
	        }
	      });
	    }
	  }]);
	  return Backend;
	}();
	babelHelpers.defineProperty(Backend, "component", 'bitrix:disk.documents');

	var Sharing = /*#__PURE__*/function () {
	  function Sharing(id, node) {
	    babelHelpers.classCallCheck(this, Sharing);
	    this.id = id;
	    this.node = node;
	    this.init();
	    this.observe();
	  }
	  babelHelpers.createClass(Sharing, [{
	    key: "init",
	    value: function init() {
	      this.actionName = 'getShared';
	    }
	  }, {
	    key: "observe",
	    value: function observe() {
	      var _this = this;
	      observeIntersection(this.node, function () {
	        _this.showLoading();
	        Backend[_this.actionName](_this.id).then(function (_ref) {
	          var data = _ref.data;
	          _this.hideLoading();
	          _this.renderData(data);
	        }, function (_ref2) {
	          var errors = _ref2.errors;
	          _this.hideLoading();
	          var errorMessages = [];
	          errors.forEach(function (error) {
	            errorMessages.push(error.message);
	          });
	          _this.node.innerHTML = 'Error! ' + errorMessages.join('<br>');
	        });
	      });
	    }
	  }, {
	    key: "showLoading",
	    value: function showLoading() {
	      this.loader = this.loader || new main_loader.Loader({
	        target: this.node,
	        mode: 'inline',
	        size: 20
	      });
	      this.loader.show();
	      this.node.dataset.bxLoading = 'Y';
	    }
	  }, {
	    key: "hideLoading",
	    value: function hideLoading() {
	      delete this.node.dataset.bxLoading;
	      this.loader.hide();
	    }
	  }, {
	    key: "renderData",
	    value: function renderData(data) {
	      this.node.innerHTML = '';
	      var res = new disk_users.Users(data, null, {
	        placeInGrid: true
	      });
	      this.node.appendChild(res.getContainer());
	    }
	  }]);
	  return Sharing;
	}();

	var ExternalLink = /*#__PURE__*/function (_Sharing) {
	  babelHelpers.inherits(ExternalLink, _Sharing);
	  function ExternalLink() {
	    babelHelpers.classCallCheck(this, ExternalLink);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ExternalLink).apply(this, arguments));
	  }
	  babelHelpers.createClass(ExternalLink, [{
	    key: "init",
	    value: function init() {
	      this.actionName = 'getExternalLink';
	    }
	  }, {
	    key: "showLoading",
	    value: function showLoading() {}
	  }, {
	    key: "hideLoading",
	    value: function hideLoading() {}
	  }, {
	    key: "renderData",
	    value: function renderData(data) {
	      this.node.innerHTML = '';
	      var res = new disk_externalLink.ExternalLinkForTrackedObject(this.id, data);
	      this.node.appendChild(res.getContainer());
	    }
	  }]);
	  return ExternalLink;
	}(Sharing);

	var CommonGrid = /*#__PURE__*/function () {
	  /**
	   * @type {BX.TileGrid.Grid|BX.Main.grid}
	   */

	  function CommonGrid(options) {
	    babelHelpers.classCallCheck(this, CommonGrid);
	    babelHelpers.defineProperty(this, "gridInstance", null);
	    this.gridInstance = options.gridInstance;
	  }
	  babelHelpers.createClass(CommonGrid, [{
	    key: "getId",
	    value: function getId() {
	      return this.gridInstance.getId();
	    }
	  }, {
	    key: "isGrid",
	    value: function isGrid() {
	      return !this.isTile();
	    }
	  }, {
	    key: "isTile",
	    value: function isTile() {
	      return BX.TileGrid.Grid && this.gridInstance instanceof BX.TileGrid.Grid;
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      return this.gridInstance.getContainer();
	    }
	  }, {
	    key: "fade",
	    value: function fade() {
	      if (this.isGrid()) {
	        this.gridInstance.tableFade();
	      } else {
	        this.gridInstance.setFadeContainer();
	        this.gridInstance.getLoader();
	        this.gridInstance.showLoader();
	      }
	    }
	  }, {
	    key: "unFade",
	    value: function unFade() {
	      if (this.isGrid()) {
	        this.gridInstance.tableUnfade();
	      } else {
	        this.gridInstance.getLoader().hide();
	        this.gridInstance.unSetFadeContainer();
	      }
	    }
	  }, {
	    key: "getActionKey",
	    value: function getActionKey() {
	      return 'action_button_' + this.gridInstance.getId();
	    }
	  }, {
	    key: "getSelectedIds",
	    value: function getSelectedIds() {
	      if (this.isGrid()) {
	        return this.gridInstance.getRows().getSelectedIds();
	      } else {
	        return this.gridInstance.getSelectedItems().map(function (item) {
	          return item.getId();
	        });
	      }
	    }
	  }, {
	    key: "getIds",
	    value: function getIds() {
	      if (this.isGrid()) {
	        return this.gridInstance.getRows().getBodyChild().map(function (row) {
	          return row.getId();
	        });
	      } else {
	        return this.gridInstance.items.map(function (item) {
	          return item.id;
	        });
	      }
	    }
	  }, {
	    key: "countItems",
	    value: function countItems() {
	      if (this.isGrid()) {
	        return this.gridInstance.getRows().getBodyChild().length;
	      } else {
	        return this.gridInstance.countItems();
	      }
	    }
	  }, {
	    key: "reload",
	    value: function reload(url, data) {
	      data = data || {};
	      if (this.isGrid()) {
	        var promise = new BX.Promise();
	        this.gridInstance.reloadTable("POST", data, function () {
	          promise.fulfill();
	        }, url);
	        return promise;
	      } else {
	        return this.gridInstance.reload(url, data);
	      }
	    }
	  }, {
	    key: "getActionsMenu",
	    value: function getActionsMenu(itemId) {
	      if (this.isGrid()) {
	        return this.gridInstance.getRows().getById(itemId).getActionsMenu();
	      } else {
	        var item = this.gridInstance.getItem(itemId);
	        if (item) {
	          return item.getActionsMenu();
	        }
	      }
	    }
	  }, {
	    key: "getItemById",
	    value: function getItemById(id) {
	      if (this.isGrid()) {
	        return this.gridInstance.getRows().getById(id);
	      } else {
	        return this.gridInstance.getItem(id);
	      }
	    }
	  }, {
	    key: "scrollTo",
	    value: function scrollTo(id) {
	      var contentNode;
	      if (this.isGrid()) {
	        var row = this.gridInstance.getRows().getById(id);
	        if (row && row.node) {
	          contentNode = row.node;
	        }
	      } else {
	        var item = this.gridInstance.getItem(id);
	        if (row && row.node) {
	          contentNode = row.getContainer();
	        }
	      }
	      if (contentNode) {
	        new BX.easing({
	          duration: 500,
	          start: {
	            scroll: window.pageYOffset || document.documentElement.scrollTop
	          },
	          finish: {
	            scroll: BX.pos(contentNode).top
	          },
	          transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	          step: function step(state) {
	            window.scrollTo(0, state.scroll);
	          }
	        }).animate();
	      }
	    }
	  }, {
	    key: "getActionById",
	    value: function getActionById(id, menuItemId) {
	      var item = this.getItemById(id);
	      if (item) {
	        var actions = item.getActions();
	        for (var i = 0; i < actions.length; i++) {
	          if (actions[i].id && actions[i].id === menuItemId) {
	            return actions[i];
	          }
	        }
	      }
	      return null;
	    }
	  }, {
	    key: "removeItemById",
	    value: function removeItemById(itemId) {
	      BX.fireEvent(document, 'click');
	      if (this.isGrid()) {
	        this.gridInstance.removeRow(itemId);
	      } else {
	        var item = this.gridInstance.getItem(itemId);
	        if (item) {
	          //todo here we have to remove item from server
	          this.gridInstance.removeItem(item);
	        }
	      }
	    }
	  }, {
	    key: "selectItemById",
	    value: function selectItemById(itemId) {
	      var item;
	      if (this.isGrid()) {
	        item = this.gridInstance.getRows().getById(itemId);
	        if (item) {
	          item.select();
	        }
	      } else {
	        item = this.gridInstance.getItem(itemId);
	        if (item) {
	          this.gridInstance.selectItem(item);
	        }
	      }
	    }
	  }, {
	    key: "removeSelected",
	    value: function removeSelected() {
	      if (this.isGrid()) {
	        this.gridInstance.removeSelected();
	      }
	    }
	  }, {
	    key: "sortByColumn",
	    value: function sortByColumn(column) {
	      this.gridInstance.sortByColumn(column);
	    }
	  }]);
	  return CommonGrid;
	}();

	var Options = /*#__PURE__*/function () {
	  function Options() {
	    babelHelpers.classCallCheck(this, Options);
	  }
	  babelHelpers.createClass(Options, null, [{
	    key: "getGridId",
	    // $arParams['GRID_ID']
	    // $arParams['GRID_ID']
	    value: function getGridId() {
	      return Options.gridId;
	    }
	  }, {
	    key: "getCommonGrid",
	    value: function getCommonGrid() {
	      var gridInstance;
	      var gridId = this.getGridId();
	      if (main_core.Reflection.getClass('BX.Main.gridManager') && BX.Main.gridManager.getInstanceById(gridId)) {
	        gridInstance = BX.Main.gridManager.getInstanceById(gridId);
	      } else if (main_core.Reflection.getClass('BX.Main.tileGridManager') && BX.Main.tileGridManager.getInstanceById(gridId)) {
	        gridInstance = BX.Main.tileGridManager.getInstanceById(gridId);
	      }
	      return new CommonGrid({
	        gridInstance: gridInstance
	      });
	    }
	  }, {
	    key: "setGridId",
	    value: function setGridId(gridId) {
	      Options.gridId = gridId;
	    }
	  }, {
	    key: "getFilterId",
	    value: function getFilterId() {
	      return Options.filterId;
	    }
	  }, {
	    key: "getEditableExt",
	    value: function getEditableExt() {
	      return Options.editableExt;
	    }
	  }, {
	    key: "setEditableExt",
	    value: function setEditableExt(extensions) {
	      Options.editableExt = extensions;
	    }
	  }, {
	    key: "setViewList",
	    value: function setViewList() {
	      BX.userOptions.save('disk', 'documents', 'viewMode', 'list');
	      BX.userOptions.save('disk', 'documents', 'viewSize', '');
	      window.location.reload();
	    }
	  }, {
	    key: "setViewSmallTile",
	    value: function setViewSmallTile() {
	      BX.userOptions.save('disk', 'documents', 'viewMode', 'tile');
	      BX.userOptions.save('disk', 'documents', 'viewSize', 'm');
	      window.location.reload();
	    }
	  }, {
	    key: "setViewBigTile",
	    value: function setViewBigTile() {
	      BX.userOptions.save('disk', 'documents', 'viewMode', 'tile');
	      BX.userOptions.save('disk', 'documents', 'viewSize', 'xl');
	      window.location.reload();
	    }
	  }]);
	  return Options;
	}();
	babelHelpers.defineProperty(Options, "gridId", 'diskDocumentsGrid');
	babelHelpers.defineProperty(Options, "filterId", 'diskDocumentsFilter');
	babelHelpers.defineProperty(Options, "editableExt", ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'xodt']);

	var Toolbar = /*#__PURE__*/function () {
	  function Toolbar() {
	    babelHelpers.classCallCheck(this, Toolbar);
	  }
	  babelHelpers.createClass(Toolbar, null, [{
	    key: "reloadGridAndFocus",
	    value: function reloadGridAndFocus(rowId) {
	      var commonGrid = Options.getCommonGrid();
	      commonGrid.reload();
	    }
	  }, {
	    key: "runCreating",
	    value: function runCreating(documentType, service) {
	      var _this = this;
	      if (BX.message('disk_restriction')) {
	        //this.blockFeatures();
	        return;
	      }
	      if (service === 'l' && BX.Disk.Document.Local.Instance.isEnabled()) {
	        BX.Disk.Document.Local.Instance.createFile({
	          type: documentType
	        }).then(function (response) {
	          _this.reloadGridAndFocus(response.object.id);
	        });
	        return;
	      }
	      var createProcess = new BX.Disk.Document.CreateProcess({
	        typeFile: documentType,
	        serviceCode: service,
	        onAfterSave: function onAfterSave(response) {
	          if (response.status === 'success') {
	            _this.reloadGridAndFocus(response.object.id);
	          }
	        }
	      });
	      createProcess.start();
	    }
	  }, {
	    key: "resolveServiceCode",
	    value: function resolveServiceCode(service) {
	      if (!service) {
	        service = BX.Disk.getDocumentService();
	      }
	      if (service) {
	        return service;
	      }
	      if (BX.Disk.isAvailableOnlyOffice()) {
	        return 'onlyoffice';
	      }
	      BX.Disk.InformationPopups.openWindowForSelectDocumentService({});
	      return null;
	    }
	  }, {
	    key: "createBoard",
	    value: function createBoard() {
	      var newTab = window.open('', '_blank');
	      BX.ajax.runAction('disk.integration.flipchart.createDocument').then(function (response) {
	        if (response.status === 'success' && response.data.file) {
	          var _manager$getById;
	          var manager = BX.Main.gridManager || BX.Main.tileGridManager;
	          var grid = (_manager$getById = manager.getById('diskDocumentsGrid')) === null || _manager$getById === void 0 ? void 0 : _manager$getById.instance;
	          if (grid) {
	            grid.reload();
	          }
	          if (response.data.viewUrl) {
	            newTab.location.href = response.data.viewUrl;
	          }
	        }
	      });
	    }
	  }, {
	    key: "createDocx",
	    value: function createDocx(service) {
	      var code = this.resolveServiceCode(service);
	      if (code) {
	        this.runCreating('docx', code);
	      }
	    }
	  }, {
	    key: "createXlsx",
	    value: function createXlsx(service) {
	      var code = this.resolveServiceCode(service);
	      if (code) {
	        this.runCreating('xlsx', code);
	      }
	    }
	  }, {
	    key: "createPptx",
	    value: function createPptx(service) {
	      var code = this.resolveServiceCode(service);
	      if (code) {
	        this.runCreating('pptx', code);
	      }
	    }
	  }, {
	    key: "createByDefault",
	    value: function createByDefault(service) {
	      console.log('createByDefault: ', service);
	      console.log('try to upload just for the test');
	    }
	  }]);
	  return Toolbar;
	}();

	var Item = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Item, _EventEmitter);
	  function Item(trackedObjectId, itemData) {
	    var _this;
	    babelHelpers.classCallCheck(this, Item);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Item).call(this));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "data", {});
	    _this.setEventNamespace('Disk:Documents:');
	    _this.trackedObjectId = trackedObjectId;
	    _this.data = Object.assign({}, itemData);
	    _this.data['className'] = (_this.data['className'] || '') + ' disk-folder-list-context-menu-item';
	    if (!_this.data['text']) {
	      _this.data['text'] = _this.data['id'];
	    }
	    _this.objectId = _this.data['objectId'];
	    delete _this.data['objectId'];
	    return _this;
	  }
	  babelHelpers.createClass(Item, [{
	    key: "getData",
	    value: function getData(key) {
	      if (key) {
	        return this.data[key];
	      }
	      return this.data;
	    }
	  }, {
	    key: "showError",
	    value: function showError(errors) {
	      console.log('errors: ', errors);
	    }
	  }, {
	    key: "addPopupMenuItem",
	    value: function addPopupMenuItem(popupMenu) {
	      this.popupMenuItem = popupMenu.addMenuItem(this.data);
	    }
	  }, {
	    key: "showLoad",
	    value: function showLoad() {
	      if (this.popupMenuItem) {
	        this.loader = this.loader || new main_loader.Loader({
	          target: this.popupMenuItem.getContainer(),
	          size: 32
	        });
	        this.loader.show();
	      }
	    }
	  }, {
	    key: "hideLoad",
	    value: function hideLoad() {
	      if (this.loader) {
	        this.loader.hide();
	      }
	    }
	  }], [{
	    key: "detect",
	    value: function detect(itemData) {
	      return true;
	    }
	  }]);
	  return Item;
	}(main_core_events.EventEmitter);

	var ItemHistory = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemHistory, _Item);
	  function ItemHistory(trackedObjectId, itemData) {
	    var _this;
	    babelHelpers.classCallCheck(this, ItemHistory);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemHistory).call(this, trackedObjectId, itemData));
	    _this.object = {
	      id: itemData.dataset.objectId,
	      fileHistoryUrl: itemData.dataset.fileHistoryUrl,
	      name: itemData.dataset.objectName,
	      blockedByFeature: itemData.dataset.blockedByFeature
	    };
	    _this.data.onclick = _this.handleClick.bind(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(ItemHistory, [{
	    key: "handleClick",
	    value: function handleClick() {
	      this.emit('close');
	      if (this.object.blockedByFeature) {
	        top.BX.UI.InfoHelper.show('limit_office_version_storage');
	        return;
	      }
	      var fileHistoryUrl = this.object.fileHistoryUrl;
	      BX.SidePanel.Instance.open(fileHistoryUrl, {
	        cacheable: false,
	        allowChangeHistory: false
	      });
	    }
	  }], [{
	    key: "detect",
	    value: function detect(itemData) {
	      return itemData.id === 'history';
	    }
	  }]);
	  return ItemHistory;
	}(Item);

	var ItemOpen = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemOpen, _Item);
	  function ItemOpen(objectId, itemData) {
	    var _this;
	    babelHelpers.classCallCheck(this, ItemOpen);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemOpen).call(this, objectId, itemData));
	    _this.data['dataset'] = _this.data['dataset'] || {};
	    _this.data['dataset']['preventCloseContextMenu'] = true;
	    _this.data['onclick'] = function () {
	      var _this2 = this;
	      if (this.data['href']) {
	        return this.open();
	      }
	      this.showLoad();
	      Backend.getMenuOpenAction(this.objectId).then(function (_ref) {
	        var data = _ref.data;
	        _this2.hideLoad();
	        _this2.data['href'] = data;
	        _this2.open();
	      })["catch"](function (_ref2) {
	        var errors = _ref2.errors;
	        _this2.hideLoad();
	        _this2.showError(errors);
	      });
	    }.bind(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(ItemOpen, [{
	    key: "open",
	    value: function open() {
	      if (main_core.Type.isStringFilled(this.data['href'])) {
	        if (!this.data['target']) {
	          BX.SidePanel.Instance.open(this.data['href']);
	        }
	        this.emit('close');
	      } else {
	        this.showError([{
	          text: 'Empty href'
	        }]);
	      }
	    }
	  }], [{
	    key: "detect",
	    value: function detect(itemData) {
	      return itemData['id'] === 'open';
	    }
	  }]);
	  return ItemOpen;
	}(Item);

	var ItemShareSection = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemShareSection, _Item);
	  function ItemShareSection(objectId, itemData) {
	    var _this;
	    babelHelpers.classCallCheck(this, ItemShareSection);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemShareSection).call(this, objectId, itemData));
	    _this.data['dataset'] = _this.data['dataset'] || {};
	    _this.data['dataset']['preventCloseContextMenu'] = true;
	    return _this;
	  }
	  babelHelpers.createClass(ItemShareSection, null, [{
	    key: "detect",
	    value: function detect(itemData) {
	      return itemData['id'] === 'share-section';
	    }
	  }]);
	  return ItemShareSection;
	}(Item);

	var ItemInternalLink = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemInternalLink, _Item);
	  function ItemInternalLink(trackedObjectId, itemData) {
	    var _this;
	    babelHelpers.classCallCheck(this, ItemInternalLink);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemInternalLink).call(this, trackedObjectId, itemData));
	    _this.data['className'] += ' disk-documents-grid-actions-copy-internal-link';
	    _this.data['html'] = [_this.data.text, '<span class="disk-documents-grid-actions-copy-internal-link-icon">' + '<span class="disk-documents-grid-actions-copy-internal-link-icon-inner">' + '</span>' + '</span>'].join('');
	    delete _this.data['text'];
	    _this.data['dataset'] = _this.data['dataset'] || {};
	    _this.data['dataset']['preventCloseContextMenu'] = true;
	    _this.data['onclick'] = function (event, menuItem) {
	      var target = menuItem.getLayout().item;
	      target.classList.add('menu-popup-item-accept', 'disk-folder-list-context-menu-item-accept-animate');
	      target.style.minWidth = target.offsetWidth + 'px';
	      var textNode = target.querySelector('.menu-popup-item-text');
	      if (textNode) {
	        textNode.textContent = this.data['dataset']['textCopied'];
	      }
	      BX.clipboard.copy(this.data['dataset']['internalLink']);
	    }.bind(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(ItemInternalLink, null, [{
	    key: "detect",
	    value: function detect(itemData) {
	      return itemData['id'] === 'internalLink';
	    }
	  }]);
	  return ItemInternalLink;
	}(Item);

	var ItemExternalLink = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemExternalLink, _Item);
	  function ItemExternalLink(objectId, itemData) {
	    var _this;
	    babelHelpers.classCallCheck(this, ItemExternalLink);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemExternalLink).call(this, objectId, itemData));
	    var shouldBlockFeature = itemData['dataset']['shouldBlockFeature'];
	    var blocker = itemData['dataset']['blocker'];
	    _this.data['onclick'] = function () {
	      this.emit('close');
	      if (shouldBlockFeature && blocker) {
	        eval(blocker);
	        return;
	      }
	      disk_externalLink.ExternalLinkForTrackedObject.showPopup(this.trackedObjectId);
	    }.bind(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(ItemExternalLink, null, [{
	    key: "detect",
	    value: function detect(itemData) {
	      return itemData['id'] === 'externalLink';
	    }
	  }]);
	  return ItemExternalLink;
	}(Item);

	var ItemRename = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemRename, _Item);
	  function ItemRename(trackedObjectId, itemData) {
	    var _this;
	    babelHelpers.classCallCheck(this, ItemRename);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemRename).call(this, trackedObjectId, itemData));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "buffExtension", '');
	    if (!_this.data['onclick']) {
	      _this.data['onclick'] = _this.rename.bind(babelHelpers.assertThisInitialized(_this));
	    }
	    return _this;
	  }
	  babelHelpers.createClass(ItemRename, [{
	    key: "cutExtension",
	    value: function cutExtension(name) {
	      this.buffExtension = '';
	      if (name.lastIndexOf('.') > 0) {
	        this.buffExtension = name.substr(name.lastIndexOf('.'));
	        return name.substr(0, name.lastIndexOf('.'));
	      }
	      return name;
	    }
	  }, {
	    key: "restoreExtension",
	    value: function restoreExtension(name) {
	      name += this.buffExtension;
	      this.buffExtension = '';
	      return name;
	    }
	  }, {
	    key: "rename",
	    value: function rename() {
	      var _this2 = this;
	      var grid = BX.Main.gridManager.getInstanceById(Options.getGridId());
	      var row = grid.getRows().getById(this.trackedObjectId);
	      row.edit();
	      var editorContainer = BX.Grid.Utils.getByClass(row.getNode(), 'main-grid-editor-container', true);
	      var input = editorContainer.querySelector('input');
	      if (input) {
	        input.value = this.cutExtension(input.value);
	        var onBlur = function (event) {
	          onBeforeSend(event);
	        }.bind(this);
	        var onBeforeSend = function onBeforeSend(event) {
	          event.stopPropagation();
	          event.preventDefault();
	          var fullName = _this2.restoreExtension(input.value);
	          Backend.renameAction(_this2.trackedObjectId, fullName).then(function (_ref) {
	            var name = _ref.data.object.name;
	            if (fullName !== name) {
	              row.getNode().querySelector('#disk_obj_' + _this2.trackedObjectId).innerHTML = main_core.Text.encode(name);
	              row.editData['NAME'] = name;
	            }
	          });
	          input.removeEventListener('blur', onBlur);
	          row.getNode().querySelector('#disk_obj_' + _this2.trackedObjectId).innerHTML = main_core.Text.encode(fullName);
	          row.editData['NAME'] = fullName;
	          row.editCancel();
	        };
	        input.addEventListener('keydown', function (event) {
	          if (event.key === 'Enter') {
	            onBeforeSend(event);
	          } else if (event.key === 'Escape') {
	            input.removeEventListener('blur', onBlur);
	            row.editCancel();
	          }
	        }.bind(this));
	        input.addEventListener('blur', onBlur);
	        BX.focus(input);
	      }
	    }
	  }], [{
	    key: "detect",
	    value: function detect(itemData) {
	      return itemData['id'] === 'rename';
	    }
	  }]);
	  return ItemRename;
	}(Item);

	var ItemSharing = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemSharing, _Item);
	  function ItemSharing(trackedObjectId, itemData) {
	    var _this;
	    babelHelpers.classCallCheck(this, ItemSharing);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemSharing).call(this, trackedObjectId, itemData));
	    var object = {
	      id: itemData['dataset']['objectId'],
	      name: itemData['dataset']['objectName']
	    };
	    _this.data['onclick'] = function () {
	      _this.emit('close');
	      switch (_this.data['dataset']['type']) {
	        case disk_sharingLegacyPopup.SharingControlType.WITH_CHANGE_RIGHTS:
	          new disk_sharingLegacyPopup.LegacyPopup().showSharingDetailWithChangeRights({
	            object: object
	          });
	          break;
	        case disk_sharingLegacyPopup.SharingControlType.WITH_SHARING:
	          new disk_sharingLegacyPopup.LegacyPopup().showSharingDetailWithChangeRights({
	            object: object
	          });
	          break;
	        case disk_sharingLegacyPopup.SharingControlType.WITHOUT_EDIT:
	          new disk_sharingLegacyPopup.LegacyPopup().showSharingDetailWithoutEdit({
	            object: object
	          });
	          break;
	      }
	    };
	    return _this;
	  }
	  babelHelpers.createClass(ItemSharing, null, [{
	    key: "detect",
	    value: function detect(itemData) {
	      return itemData['id'] === 'sharing';
	    }
	  }]);
	  return ItemSharing;
	}(Item);

	var ItemDelete = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemDelete, _Item);
	  function ItemDelete(trackedObjectId, itemData) {
	    var _this;
	    babelHelpers.classCallCheck(this, ItemDelete);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemDelete).call(this, trackedObjectId, itemData));
	    _this.object = {
	      id: itemData['dataset']['objectId'],
	      name: itemData['dataset']['objectName']
	    };
	    _this.data['onclick'] = _this.handleClick.bind(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(ItemDelete, [{
	    key: "handleClick",
	    value: function handleClick() {
	      this.emit('close');
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('DISK_DOCUMENTS_ACT_DELETE_TITLE'),
	        message: main_core.Loc.getMessage('DISK_DOCUMENTS_ACT_DELETE_MESSAGE', {
	          '#NAME#': this.object.name
	        }),
	        modal: true,
	        buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
	        okCaption: main_core.Loc.getMessage('DISK_DOCUMENTS_ACT_DELETE_OK_BUTTON'),
	        onOk: this.handleClickDelete.bind(this)
	      });
	    }
	  }, {
	    key: "handleClickDelete",
	    value: function handleClickDelete() {
	      var _this2 = this;
	      main_core.ajax.runAction('disk.api.commonActions.markDeleted', {
	        analyticsLabel: 'folder.list.dd',
	        data: {
	          objectId: this.object.id
	        }
	      }).then(function (response) {
	        if (response.status === 'success') {
	          var commonGrid = Options.getCommonGrid();
	          commonGrid.removeItemById(_this2.trackedObjectId);
	        }
	      });
	      return true;
	    }
	  }], [{
	    key: "detect",
	    value: function detect(itemData) {
	      return itemData['id'] === 'delete';
	    }
	  }]);
	  return ItemDelete;
	}(Item);

	var itemMappings = [ItemOpen, ItemShareSection, ItemSharing, ItemInternalLink, ItemExternalLink, ItemHistory, ItemRename, ItemDelete];
	function getMenuItem(trackedObjectId, itemData) {
	  var itemClassName = Item;
	  itemMappings.forEach(function (itemClass) {
	    if (itemClass.detect(itemData)) {
	      itemClassName = itemClass;
	    }
	  });
	  return new itemClassName(trackedObjectId, itemData);
	}

	var List = /*#__PURE__*/function () {
	  function List() {
	    babelHelpers.classCallCheck(this, List);
	    this.addReloadGrid();
	    this.addMenuActionLoader();
	    this.bindEvents();
	  }
	  babelHelpers.createClass(List, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      main_core_events.EventEmitter.subscribe('Disk.OnlyOffice:onSaved', this.handleDocumentSaved.bind(this));
	    }
	  }, {
	    key: "handleDocumentSaved",
	    value: function handleDocumentSaved(event) {
	      var _event$getCompatData = event.getCompatData(),
	        _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 2),
	        object = _event$getCompatData2[0],
	        documentSession = _event$getCompatData2[1];
	      var grid = BX.Main.gridManager.getInstanceById(Options.getGridId());
	      var objectNode = grid.getBody().querySelector("span[data-object-id=\"".concat(object.id, "\"]"));
	      if (!objectNode) {
	        return;
	      }
	      var row = objectNode.closest('.main-grid-row');
	      if (!row || !row.dataset.id) {
	        return;
	      }
	      var rowId = row.dataset.id;
	      grid.updateRow(rowId, null, null, function () {
	        var rowNode = grid.getRows().getById(rowId).getNode();
	        if (!rowNode) {
	          return;
	        }
	        main_core.Dom.addClass(rowNode, 'main-grid-row-checked');
	        setInterval(function () {
	          main_core.Dom.removeClass(rowNode, 'main-grid-row-checked');
	        }, 8000);
	      });
	    }
	  }, {
	    key: "addReloadGrid",
	    value: function addReloadGrid() {
	      BX.addCustomEvent('onPopupFileUploadClose', function () {
	        BX.Main.gridManager.getInstanceById(Options.getGridId()).reload();
	      });
	    }
	  }, {
	    key: "addMenuActionLoader",
	    value: function addMenuActionLoader() {
	      main_core_events.EventEmitter.subscribe('onPopupFirstShow', function (_ref) {
	        var _ref$compatData = babelHelpers.slicedToArray(_ref.compatData, 1),
	          popup = _ref$compatData[0];
	        if (popup.uniquePopupId.indexOf('menu-popup-main-grid-actions-menu-') !== 0) {
	          return;
	        }
	        var objectId = popup.uniquePopupId.replace(/^menu-popup-main-grid-actions-menu-/, '');
	        popup.getContentContainer().classList.add('disk-documents-animate');
	        popup.getContentContainer().style.height = 80 + 'px';
	        Backend.getMenuActions(objectId).then(function (_ref2) {
	          var data = _ref2.data;
	          var row = BX.Main.gridManager.getInstanceById(Options.getGridId()).getRows().getById(objectId);
	          var menu = row.getActionsMenu();
	          row.actions = [];
	          var prepareActionMenu = function prepareActionMenu(item, index, ar) {
	            if (item['items']) {
	              item['items'].forEach(prepareActionMenu);
	            }
	            var menuItem = getMenuItem(objectId, item);
	            menuItem.subscribe('close', function () {
	              row.closeActionsMenu();
	            });
	            if (ar === data) {
	              menuItem.addPopupMenuItem(menu);
	              row.actions.push(menuItem.getData());
	            } else {
	              ar[index] = menuItem.getData();
	            }
	          };
	          setTimeout(function () {
	            popup.getContentContainer().style.height = data.length * 36 + 16 + 'px';
	          });
	          popup.getContentContainer().addEventListener('transitionend', function () {
	            popup.getContentContainer().classList.remove('disk-documents-animate');
	            popup.getContentContainer().style.height = '';
	          });
	          data.forEach(prepareActionMenu);
	          if (menu) {
	            menu.removeMenuItem('loader');
	          }
	        }.bind(this))["catch"](function (_ref3) {
	          var errors = _ref3.errors;
	          //Hide Loader and show errors
	          console.log(errors);
	        }.bind(this));
	      }.bind(this));
	    }
	  }]);
	  return List;
	}();

	var _templateObject;
	var Tile = /*#__PURE__*/function () {
	  function Tile() {
	    babelHelpers.classCallCheck(this, Tile);
	    this.addReloadGrid();
	    this.addMenuActionLoader();
	    this.addFilterSequence();
	  }
	  babelHelpers.createClass(Tile, [{
	    key: "addFilterSequence",
	    value: function addFilterSequence() {
	      main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', function (_ref) {
	        var _ref$compatData = babelHelpers.slicedToArray(_ref.compatData, 5),
	          filterId = _ref$compatData[0],
	          data = _ref$compatData[1],
	          filter = _ref$compatData[2],
	          promise = _ref$compatData[3],
	          params = _ref$compatData[4];
	        if (filterId === Options.getFilterId()) {
	          promise.then(function () {
	            BX.Main.tileGridManager.getInstanceById(Options.getGridId()).reload();
	          }.bind(this));
	        }
	      });
	    }
	  }, {
	    key: "addReloadGrid",
	    value: function addReloadGrid() {
	      BX.addCustomEvent('onPopupFileUploadClose', function () {
	        BX.Main.tileGridManager.getInstanceById(Options.getGridId()).reload();
	      });
	    }
	  }, {
	    key: "addMenuActionLoader",
	    value: function addMenuActionLoader() {
	      main_core_events.EventEmitter.subscribe('Disk:Documents:TileGrid:MenuAction:FirstShow', function (_ref2) {
	        var _ref2$compatData = babelHelpers.slicedToArray(_ref2.compatData, 3),
	          row = _ref2$compatData[0],
	          objectId = _ref2$compatData[1],
	          menuPopup = _ref2$compatData[2];
	        Backend.getMenuActions(objectId).then(function (_ref3) {
	          var data = _ref3.data;
	          var menu = menuPopup;
	          row.actions = [];
	          var prepareActionMenu = function prepareActionMenu(item, index, ar) {
	            if (item['items']) {
	              item['items'].forEach(prepareActionMenu);
	            }
	            if (item['id'] === 'rename') {
	              item['onclick'] = row.onRename.bind(row);
	            }
	            var menuItem = getMenuItem(objectId, item);
	            menuItem.subscribe('close', function () {
	              menu.close();
	            });
	            if (ar === data) {
	              menuItem.addPopupMenuItem(menu);
	              row.actions.push(menuItem.getData());
	            } else {
	              ar[index] = menuItem.getData();
	            }
	          };
	          data.forEach(prepareActionMenu);
	          if (menu) {
	            menu.removeMenuItem('loader');
	          }
	        }.bind(this))["catch"](function (_ref4) {
	          var errors = _ref4.errors;
	          //Hide Loader and show errors
	          console.log(errors);
	        }.bind(this));
	      }.bind(this));
	    }
	  }], [{
	    key: "generateEmptyBlock",
	    value: function generateEmptyBlock() {
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"disk-folder-list-no-data-inner\">\n\t\t\t<div class=\"disk-folder-list-no-data-inner-message\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t<div class=\"disk-folder-list-no-data-inner-variable\">\n\t\t\t\t<div class=\"disk-folder-list-no-data-inner-create-file\" onmouseover=\"BX.onCustomEvent(window, 'onDiskUploadPopupShow', [this]);\">\n\t\t\t\t\t", "</div>\n\t\t\t</div>\n\t\t</div>"])), main_core.Loc.getMessage('DISK_DOCUMENTS_GRID_TILE_EMPTY_BLOCK_TITLE'), main_core.Loc.getMessage('DISK_DOCUMENTS_GRID_TILE_EMPTY_BLOCK_UPLOAD'));
	    }
	  }]);
	  return Tile;
	}();

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _checkParams = /*#__PURE__*/new WeakSet();
	var _createGuide = /*#__PURE__*/new WeakSet();
	var _createSpotlight = /*#__PURE__*/new WeakSet();
	var _getTitle = /*#__PURE__*/new WeakSet();
	var _getText = /*#__PURE__*/new WeakSet();
	var BoardsGuide = /*#__PURE__*/function () {
	  function BoardsGuide(options) {
	    babelHelpers.classCallCheck(this, BoardsGuide);
	    _classPrivateMethodInitSpec(this, _getText);
	    _classPrivateMethodInitSpec(this, _getTitle);
	    _classPrivateMethodInitSpec(this, _createSpotlight);
	    _classPrivateMethodInitSpec(this, _createGuide);
	    _classPrivateMethodInitSpec(this, _checkParams);
	    babelHelpers.defineProperty(this, "target", null);
	    babelHelpers.defineProperty(this, "targetSpotlight", null);
	    babelHelpers.defineProperty(this, "guide", null);
	    this.target = document.querySelector(options.targetSelector);
	    this.targetSpotlight = document.querySelector(options.spotlightSelector);
	    this.isBoardsPage = options.isBoardsPage;
	    if (_classPrivateMethodGet(this, _checkParams, _checkParams2).call(this)) {
	      this.guide = _classPrivateMethodGet(this, _createGuide, _createGuide2).call(this, options.id);
	    } else {
	      console.error('Unable to create guide');
	    }
	  }
	  babelHelpers.createClass(BoardsGuide, [{
	    key: "start",
	    value: function start() {
	      var _this = this;
	      if (this.guide === null) {
	        console.error('Unable to start guide');
	        return;
	      }
	      setTimeout(function () {
	        _this.guide.scrollToTarget(_this.target);
	        _this.guide.start();
	      }, 1000);
	    }
	  }]);
	  return BoardsGuide;
	}();
	function _checkParams2() {
	  return this.target !== null && this.targetSpotlight !== null;
	}
	function _createGuide2(id) {
	  var spotlight = _classPrivateMethodGet(this, _createSpotlight, _createSpotlight2).call(this);
	  var guide = new ui_tour.Guide({
	    id: id,
	    simpleMode: true,
	    overlay: false,
	    onEvents: true,
	    autoSave: true,
	    steps: [{
	      target: this.target,
	      title: _classPrivateMethodGet(this, _getTitle, _getTitle2).call(this),
	      text: _classPrivateMethodGet(this, _getText, _getText2).call(this),
	      position: 'bottom',
	      condition: {
	        color: 'primary',
	        bottom: false,
	        top: true
	      }
	    }],
	    events: {
	      onStart: function onStart() {
	        spotlight.show();
	      },
	      onFinish: function onFinish() {
	        spotlight.close();
	      }
	    }
	  });
	  var guidePopup = guide.getPopup();
	  guidePopup.setWidth(380);
	  guidePopup.setAngle({
	    offset: this.target.offsetWidth / 2 - guidePopup.contentContainer.offsetWidth / 2
	  });
	  return guide;
	}
	function _createSpotlight2() {
	  var spotLight = new BX.SpotLight({
	    targetElement: this.targetSpotlight,
	    targetVertex: 'middle-center',
	    lightMode: true
	  });
	  spotLight.getTargetContainer().style.pointerEvents = 'none';
	  return spotLight;
	}
	function _getTitle2() {
	  // noinspection JSAnnotator
	  return this.isBoardsPage ? main_core.Loc.getMessage('DISK_BOARD_TOUR_TITLE') : main_core.Loc.getMessage('DISK_DOCUMENTS_TOUR_TITLE');
	}
	function _getText2() {
	  // noinspection JSAnnotator
	  return this.isBoardsPage ? main_core.Loc.getMessage('DISK_BOARD_TOUR_DESCRIPTION') : main_core.Loc.getMessage('DISK_DOCUMENTS_TOUR_DESCRIPTION');
	}

	function showShared(objectId, node) {
	  new Sharing(objectId, node);
	}
	function showExternalLink(objectId, node) {
	  new ExternalLink(objectId, node);
	}
	var TileGridEmptyBlockGenerator = Tile.generateEmptyBlock;

	//Template things
	BX.ready(function () {
	  if (BX.Main.gridManager && BX.Main.gridManager.getInstanceById(Options.getGridId())) {
	    new List();
	  } else if (BX.Main.tileGridManager && BX.Main.tileGridManager.getInstanceById(Options.getGridId())) {
	    new Tile();
	  } else {
	    main_core_events.EventEmitter.subscribeOnce(main_core_events.EventEmitter.GLOBAL_TARGET, 'Grid::ready', function (_ref) {
	      var _ref$compatData = babelHelpers.slicedToArray(_ref.compatData, 1),
	        instance = _ref$compatData[0];
	      if (instance && instance.getId() === Options.getGridId()) {
	        new List();
	      }
	    });
	    main_core_events.EventEmitter.subscribeOnce(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.TileGrid.Grid:initialized', function (_ref2) {
	      var _ref2$compatData = babelHelpers.slicedToArray(_ref2.compatData, 1),
	        instance = _ref2$compatData[0];
	      if (instance && instance.getId() === Options.getGridId()) {
	        new Tile();
	      }
	    });
	  }
	  if (document.querySelector('#disk-documents-control-panel')) {
	    var ears = new ui_ears.Ears({
	      container: document.querySelector('#disk-documents-control-panel'),
	      noScrollbar: false,
	      className: 'disk-documents-ears'
	    });
	    ears.init();
	  }
	  var func = function func(id, uploader) {
	    uploader.limits["uploadFileExt"] = Options.getEditableExt().join(',');
	    uploader.limits["uploadFile"] = '.' + Options.getEditableExt().join(',.');
	    if (uploader.fileInput) {
	      uploader.fileInput.accept = uploader.limits["uploadFile"];
	    }
	  };
	  if (BX.UploaderManager && BX.UploaderManager.getById('DiskDocuments')) {
	    func('DiskDocuments', BX.UploaderManager.getById('DiskDocuments'));
	  } else {
	    var listener = function listener(_ref3) {
	      var _ref3$compatData = babelHelpers.slicedToArray(_ref3.compatData, 2),
	        id = _ref3$compatData[0],
	        uploader = _ref3$compatData[1];
	      setTimeout(function () {
	        func(id, uploader);
	      }, 200);
	      main_core_events.EventEmitter.unsubscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'onUploaderIsInited', listener);
	    };
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'onUploaderIsInited', listener);
	  }
	});

	exports.showExternalLink = showExternalLink;
	exports.showShared = showShared;
	exports.Toolbar = Toolbar;
	exports.Options = Options;
	exports.TileGridEmptyBlockGenerator = TileGridEmptyBlockGenerator;
	exports.Backend = Backend;
	exports.BoardsGuide = BoardsGuide;

}((this.BX.Disk.Documents = this.BX.Disk.Documents || {}),BX.Disk,BX,BX,BX.Main,BX,BX.Disk,BX.Disk.Sharing,BX.UI.Dialogs,BX.UI,BX.Event,BX,BX.UI.Tour));
//# sourceMappingURL=script.js.map
