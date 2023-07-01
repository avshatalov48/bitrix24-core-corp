this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,main_core_uploader,main_popup,ui_progressround,ui_ears,main_core,main_core_events) {
	'use strict';

	var Options = /*#__PURE__*/function () {
	  function Options() {
	    babelHelpers.classCallCheck(this, Options);
	  }

	  babelHelpers.createClass(Options, null, [{
	    key: "set",
	    value: function set(optionsToSet) {
	      for (var optionName in optionsToSet) {
	        if (Options.hasOwnProperty(optionName)) {
	          Options[optionName] = optionsToSet[optionName];
	        }
	      }
	    }
	  }, {
	    key: "getDocumentHandlers",
	    value: function getDocumentHandlers() {
	      if (!Options.documentHandlers) {
	        return [];
	      }

	      return Options.documentHandlers;
	    }
	  }, {
	    key: "getDocumentHandler",
	    value: function getDocumentHandler(code) {
	      if (!Options.documentHandlers) {
	        return {};
	      }

	      var handler = Options.documentHandlers.find(function (handler) {
	        return handler.code === code;
	      });
	      return handler || {};
	    }
	  }]);
	  return Options;
	}();

	babelHelpers.defineProperty(Options, "urlUpload", null);
	babelHelpers.defineProperty(Options, "documentHandlers", null);
	babelHelpers.defineProperty(Options, "previewSize", {
	  width: 115,
	  height: 115
	});

	var _templateObject;

	var ItemNew = /*#__PURE__*/function () {
	  function ItemNew(fileId, fileObject) {
	    babelHelpers.classCallCheck(this, ItemNew);
	    this.id = fileId;
	    this.object = fileObject;
	    this.data = {
	      NAME: fileObject.name
	    };
	    main_core_events.EventEmitter.subscribe(this.object, 'onUploadProgress', this.onUploadProgress.bind(this));
	    main_core_events.EventEmitter.subscribe(this.object, 'onUploadDone', this.onUploadDone.bind(this));
	    main_core_events.EventEmitter.subscribe(this.object, 'onUploadError', this.onUploadError.bind(this));
	    this.progress = new BX.UI.ProgressRound({
	      width: 18,
	      colorTrack: 'rgba(255,255,255,.3)',
	      colorBar: '#fff',
	      lineSize: 3,
	      statusType: BX.UI.ProgressRound.Status.INCIRCLE,
	      textBefore: '<i></i>',
	      textAfter: this.object.size
	    });
	  }

	  babelHelpers.createClass(ItemNew, [{
	    key: "getContainer",
	    value: function getContainer() {
	      if (!this.container) {
	        var extension = this.object.name.split('.').pop().toLowerCase();
	        extension = main_core.Text.encode(extension === this.object.name ? '' : extension);
	        this.container = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"disk-file-thumb disk-file-thumb-file disk-file-thumb--", " disk-file-thumb--active\">\n\t\t\t<div class=\"ui-icon ui-icon-file-", " disk-file-thumb-icon\"><i></i></div>\n\t\t\t<div class=\"disk-file-thumb-text\">", "</div>\n\t\t\t<div class=\"disk-file-thumb-loader\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"disk-file-thumb-loader-btn\" onclick=\"", "\"></div>\n\t\t\t</div>\n\t\t</div>\n\t\t"])), extension, extension, main_core.Text.encode(this.object.name), this.progress.getContainer(), this.onClickDelete.bind(this));
	      }

	      return this.container;
	    }
	  }, {
	    key: "onClickDelete",
	    value: function onClickDelete(event) {
	      var _this = this;

	      event.preventDefault();
	      event.stopPropagation();
	      main_core_events.EventEmitter.emit(this, 'onDelete', [this]);
	      setTimeout(function () {
	        _this.object.deleteFile();
	      }, 400);
	      delete this.container;
	    }
	  }, {
	    key: "onUploadProgress",
	    value: function onUploadProgress(_ref) {
	      var _ref$compatData = babelHelpers.slicedToArray(_ref.compatData, 2),
	          fileObject = _ref$compatData[0],
	          progress = _ref$compatData[1];

	      progress = Math.min(Math.max(this.progress.getValue(), progress), 98);
	      this.progress.update(progress);
	    }
	  }, {
	    key: "onUploadDone",
	    value: function onUploadDone(_ref2) {
	      var _ref2$compatData = babelHelpers.slicedToArray(_ref2.compatData, 2),
	          fileObject = _ref2$compatData[0],
	          file = _ref2$compatData[1].file;

	      main_core_events.EventEmitter.emit(this, 'onUploadDone', [file, fileObject.file]);
	      delete this.object.hash;
	      this.object.deleteFile();
	    }
	  }, {
	    key: "onUploadError",
	    value: function onUploadError(_ref3) {
	      var _ref3$compatData = babelHelpers.slicedToArray(_ref3.compatData, 1),
	          fileObject = _ref3$compatData[0];

	      main_core_events.EventEmitter.emit(this, 'onUploadError', [fileObject.file]);
	      this.progress.setTextBefore(main_core.Loc.getMessage('WDUF_ITEM_ERROR'));
	      this.container.classList.add('disk-file-upload-error');
	      this.object.deleteFile();
	    }
	  }]);
	  return ItemNew;
	}();

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }

	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }

	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }

	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }

	var _itemsCount = /*#__PURE__*/new WeakMap();

	var FileUploader = /*#__PURE__*/function () {
	  function FileUploader(_ref) {
	    var id = _ref.id,
	        container = _ref.container,
	        dropZone = _ref.dropZone,
	        input = _ref.input;
	    babelHelpers.classCallCheck(this, FileUploader);

	    _classPrivateFieldInitSpec(this, _itemsCount, {
	      writable: true,
	      value: 0
	    });

	    this.container = container;
	    this.agent = BX.Uploader.getInstance({
	      id: id,
	      allowUpload: 'A',
	      uploadFormData: 'N',
	      uploadMethod: 'immediate',
	      uploadFileUrl: Options.urlUpload,
	      showImage: false,
	      sortItems: false,
	      dropZone: dropZone,
	      input: input,
	      pasteFileHashInForm: false
	    });
	    main_core_events.EventEmitter.subscribe(this.agent, 'onFileIsCreated', this.catchFile.bind(this));
	    main_core_events.EventEmitter.subscribe(this.agent, 'onPackageIsInitialized', function (_ref2) {
	      var _ref2$compatData = babelHelpers.slicedToArray(_ref2.compatData, 1),
	          packageFormer = _ref2$compatData[0];

	      var previewParams = {
	        width: Options.previewSize.width,
	        height: Options.previewSize.height,
	        exact: 'N'
	      };

	      if (packageFormer.data) {
	        packageFormer.data['previewParams'] = previewParams;
	      } else {
	        packageFormer.post.data['previewParams'] = previewParams;
	      }
	    });
	  }

	  babelHelpers.createClass(FileUploader, [{
	    key: "catchFile",
	    value: function catchFile(_ref3) {
	      var _this = this;

	      var _ref3$compatData = babelHelpers.slicedToArray(_ref3.compatData, 2),
	          fileId = _ref3$compatData[0],
	          fileObject = _ref3$compatData[1];

	      this.incrementItemsCount();
	      var item = new ItemNew(fileId, fileObject);
	      main_core_events.EventEmitter.subscribe(item, 'onUploadDone', function (_ref4) {
	        var _ref4$data = babelHelpers.slicedToArray(_ref4.data, 2),
	            itemData = _ref4$data[0],
	            blob = _ref4$data[1];

	        if (!item[_classStaticPrivateFieldSpecGet(_this.constructor, FileUploader, _deleted)]) {
	          _this.decrementItemsCount();

	          main_core_events.EventEmitter.emit(_this, 'onUploadDone', {
	            itemData: _this.convertToItemSavedType(itemData),
	            itemContainer: item.getContainer(),
	            blob: blob
	          });
	        }
	      });
	      main_core_events.EventEmitter.subscribe(item, 'onUploadError', function (_ref5) {
	        var _ref5$data = babelHelpers.slicedToArray(_ref5.data, 1),
	            blob = _ref5$data[0];

	        _this.decrementItemsCount();

	        item[_classStaticPrivateFieldSpecGet(_this.constructor, FileUploader, _deleted)] = true;
	        main_core_events.EventEmitter.emit(_this, 'onUploadError', {
	          itemContainer: item.getContainer(),
	          blob: blob
	        });
	      });
	      main_core_events.EventEmitter.subscribe(item, 'onDelete', function () {
	        _this.decrementItemsCount();

	        item[_classStaticPrivateFieldSpecGet(_this.constructor, FileUploader, _deleted)] = true;

	        _this.container.removeChild(item.getContainer());
	      });
	      this.container.appendChild(item.getContainer());
	    }
	  }, {
	    key: "addTestThumb",
	    value: function addTestThumb(progressPercent) {
	      var _this2 = this;

	      var item = new ItemNew(Math.ceil(Math.random() * 1000), {
	        name: 'test.file.js',
	        size: '348 Kb',
	        deleteFile: function deleteFile() {}
	      });
	      this.container.appendChild(item.getContainer());

	      if (progressPercent > 0) {
	        item.onUploadProgress({
	          compatData: [null, progressPercent]
	        });
	      }

	      main_core_events.EventEmitter.subscribe(item, 'onDelete', function () {
	        _this2.container.removeChild(item.getContainer());
	      });
	    }
	  }, {
	    key: "addTestErrorThumb",
	    value: function addTestErrorThumb() {
	      var _this3 = this;

	      var item = new ItemNew(Math.ceil(Math.random() * 1000), {
	        name: 'test.file.js',
	        size: '348 Kb',
	        deleteFile: function deleteFile() {}
	      });
	      this.container.appendChild(item.getContainer());
	      item.onUploadError({
	        compatData: [{
	          file: null
	        }]
	      });
	      main_core_events.EventEmitter.subscribe(item, 'onDelete', function () {
	        _this3.container.removeChild(item.getContainer());
	      });
	    }
	  }, {
	    key: "upload",
	    value: function upload(_ref6) {
	      var _ref7 = babelHelpers.toArray(_ref6),
	          files = _ref7.slice(0);

	      this.agent.onAttach(files);
	    }
	  }, {
	    key: "convertToItemSavedType",
	    value: function convertToItemSavedType(item) {
	      return {
	        ID: item.attachId,
	        IS_LOCKED: false,
	        IS_MARK_DELETED: false,
	        EDITABLE: false,
	        FROM_EXTERNAL_SYSTEM: false,
	        CAN_RESTORE: false,
	        CAN_UPDATE: item.canChangeName,
	        CAN_RENAME: item.canChangeName,
	        CAN_MOVE: item.canChangeName,
	        COPY_TO_ME_URL: null,
	        DELETE_URL: null,
	        DOWNLOAD_URL: null,
	        EDIT_URL: null,
	        VIEW_URL: null,
	        PREVIEW_URL: item.previewUrl ? item.previewUrl : '',
	        BIG_PREVIEW_URL: item.previewUrl ? item.previewUrl.replace(/\&(width|height)=\d+/gi, '') : null,
	        EXTENSION: item.ext,
	        NAME: item.name,
	        SIZE: item.size,
	        SIZE_BYTES: item.sizeInt,
	        STORAGE: item.storage,
	        TYPE_FILE: item.fileType
	      };
	    }
	  }, {
	    key: "incrementItemsCount",
	    value: function incrementItemsCount() {
	      var _this$itemsCount;

	      if (babelHelpers.classPrivateFieldGet(this, _itemsCount) <= 0) {
	        main_core_events.EventEmitter.emit(this, 'onUploadIsStart');
	      }

	      babelHelpers.classPrivateFieldSet(this, _itemsCount, (_this$itemsCount = +babelHelpers.classPrivateFieldGet(this, _itemsCount)) + 1), _this$itemsCount;
	    }
	  }, {
	    key: "decrementItemsCount",
	    value: function decrementItemsCount() {
	      var _this$itemsCount2;

	      if (babelHelpers.classPrivateFieldGet(this, _itemsCount) === 1) {
	        main_core_events.EventEmitter.emit(this, 'onUploadIsDone');
	      }

	      babelHelpers.classPrivateFieldSet(this, _itemsCount, Math.max((babelHelpers.classPrivateFieldSet(this, _itemsCount, (_this$itemsCount2 = +babelHelpers.classPrivateFieldGet(this, _itemsCount)) - 1), _this$itemsCount2), 0));
	    }
	  }]);
	  return FileUploader;
	}();

	var _deleted = {
	  writable: true,
	  value: Symbol('deleted')
	};

	var DefaultController = /*#__PURE__*/function () {
	  function DefaultController(_ref) {
	    var container = _ref.container,
	        eventObject = _ref.eventObject;
	    babelHelpers.classCallCheck(this, DefaultController);
	    babelHelpers.defineProperty(this, "hasContainer", false);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    babelHelpers.defineProperty(this, "properties", {
	      pluggedIn: false
	    });
	    this.container = container;
	    this.eventObject = eventObject;
	    this.properties.pluggedIn = this.eventObject && this.eventObject.dataset.bxHtmlEditable === 'Y';

	    if (!this.container) {
	      return;
	    }

	    this.hasContainer = true;
	  }

	  babelHelpers.createClass(DefaultController, [{
	    key: "isRelevant",
	    value: function isRelevant() {
	      return this.hasContainer;
	    }
	  }, {
	    key: "getEventObject",
	    value: function getEventObject() {
	      return this.eventObject;
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      return this.container;
	    }
	  }, {
	    key: "isPluggedIn",
	    value: function isPluggedIn() {
	      return this.properties.pluggedIn;
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      this.container.style.display = '';
	      delete this.container.style.display;
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      this.container.style.display = 'none';
	    }
	  }]);
	  return DefaultController;
	}();

	var _templateObject$1;

	var ItemMoreButton = /*#__PURE__*/function () {
	  function ItemMoreButton() {
	    babelHelpers.classCallCheck(this, ItemMoreButton);
	    babelHelpers.defineProperty(this, "hiddenFilesCount", 0);
	    babelHelpers.defineProperty(this, "stepNumber", 0);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    babelHelpers.defineProperty(this, "stepValue", 5);
	  }

	  babelHelpers.createClass(ItemMoreButton, [{
	    key: "getContainer",
	    value: function getContainer() {
	      var _this = this;

	      return this.cache.remember('container', function () {
	        return main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"disk-file-thumb disk-file-thumb-file\" onclick=\"", "\">\n\t\t\t<div class=\"ui-icon ui-icon-more disk-file-thumb-icon\">\n\t\t\t\t<i></i>\n\t\t\t</div>\n\t\t\t<div class=\"disk-file-thumb-text\">", "</div>\n\t\t</div>"])), _this.onClick.bind(_this), _this.getValueContainer());
	      });
	    }
	  }, {
	    key: "getValueContainer",
	    value: function getValueContainer() {
	      return this.cache.remember('valueContainer', function () {
	        return document.createElement('DIV');
	      });
	    }
	  }, {
	    key: "reset",
	    value: function reset() {
	      this.hiddenFilesCount = 1;
	      this.stepNumber = 0;
	      this.getValueContainer().innerHTML = this.hiddenFilesCount;
	      this.show();
	      return this;
	    }
	  }, {
	    key: "increment",
	    value: function increment() {
	      this.hiddenFilesCount++;
	      this.getValueContainer().innerHTML = this.hiddenFilesCount;
	      this.show();
	    }
	  }, {
	    key: "decrement",
	    value: function decrement() {
	      var step = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 1;
	      this.hiddenFilesCount -= step;
	      this.getValueContainer().innerHTML = this.hiddenFilesCount;

	      if (this.hiddenFilesCount <= 0) {
	        this.hide();
	      }
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      this.stepNumber++;
	      var itemsCount = Math.min(30, this.stepValue * this.stepNumber);
	      this.decrement(itemsCount);
	      main_core_events.EventEmitter.emit(this, 'onGetMore', {
	        itemsCount: itemsCount
	      });
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      delete this.getContainer().style.display;
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      this.getContainer().style.display = 'none';
	    }
	  }]);
	  return ItemMoreButton;
	}();

	var Backend = /*#__PURE__*/function () {
	  function Backend() {
	    babelHelpers.classCallCheck(this, Backend);
	  }

	  babelHelpers.createClass(Backend, null, [{
	    key: "getSelectedFile",
	    value: function getSelectedFile(fileId, fileName, dialogName) {
	      var _this = this;

	      return new Promise(function (resolve) {
	        main_core.ajax.get(main_core.Uri.addParam(_this.urlSelect, {
	          ACTION: 'none',
	          MULTI: 'Y',
	          ID: ['E', fileId].join(''),
	          NAME: fileName,
	          wish: 'fakemove',
	          dialogName: dialogName
	        }), resolve);
	      });
	    }
	  }, {
	    key: "loadFolder",
	    value: function loadFolder(fileId, fileName, dialogName) {
	      var targetID = fileId;
	      var libLink = main_core.Uri.addParam([BX.DiskFileDialog.obCurrentTab[dialogName].link.replace('/index.php', '').replace('/files/lib/', '/files/'), 'element/upload', targetID].join('/'), {
	        use_light_view: 'Y',
	        AJAX_CALL: 'Y',
	        SIMPLE_UPLOAD: 'Y',
	        IFRAME: 'Y',
	        sessid: BX.bitrix_sessid(),
	        SECTION_ID: targetID,
	        CHECK_NAME: fileName
	      });
	      return new Promise(function (resolve) {
	        main_core.ajax.loadJSON(libLink, {}, resolve);
	      });
	    }
	  }, {
	    key: "getSelectedData",
	    value: function getSelectedData(dialogName) {
	      var _this2 = this;

	      return new Promise(function (resolve) {
	        main_core.ajax.get(main_core.Uri.addParam(_this2.urlSelect, {
	          dialogName: dialogName
	        }), resolve);
	      });
	    }
	  }, {
	    key: "getSelectedCloudData",
	    value: function getSelectedCloudData(dialogName, service) {
	      var _this3 = this;

	      return new Promise(function (resolve) {
	        main_core.ajax.get(main_core.Uri.addParam(_this3.urlSelect, {
	          cloudImport: 1,
	          service: service,
	          dialogName: dialogName
	        }), resolve);
	      });
	    }
	  }, {
	    key: "moveFile",
	    value: function moveFile(id, targetFolderId) {
	      return new Promise(function (resolve) {
	        BX.Disk.ajax({
	          method: 'POST',
	          dataType: 'json',
	          url: BX.Disk.addToLinkParam('/bitrix/tools/disk/uf.php', 'action', 'moveUploadedFile'),
	          data: {
	            attachedId: id,
	            targetFolderId: targetFolderId
	          },
	          onsuccess: resolve
	        });
	      });
	    }
	  }, {
	    key: "getMetaDataForCreatedFileInUf",
	    value: function getMetaDataForCreatedFileInUf(id) {
	      return main_core.ajax.runAction('disk.api.file.getMetaDataForCreatedFileInUf', {
	        data: {
	          id: id
	        }
	      });
	    }
	  }, {
	    key: "renameAction",
	    value: function renameAction(id, newName) {
	      return new Promise(function (resolve) {
	        main_core.ajax.post('/bitrix/tools/disk/uf.php?action=renameFile', {
	          newName: newName,
	          attachedId: id,
	          sessid: main_core.Loc.getMessage('bitrix_sessid')
	        }, resolve);
	      });
	    }
	  }, {
	    key: "deleteAction",
	    value: function deleteAction(id) {
	      return new Promise(function (resolve) {
	        main_core.ajax.post('/bitrix/tools/disk/uf.php?action=deleteFile', {
	          attachedId: id,
	          sessid: main_core.Loc.getMessage('bitrix_sessid')
	        }, resolve);
	      });
	    }
	  }]);
	  return Backend;
	}();

	babelHelpers.defineProperty(Backend, "urlSelect", '/bitrix/tools/disk/uf.php?action=selectFile&SITE_ID=' + main_core.Loc.getMessage('SITE_ID') + '&dialog2=Y&ACTION=SELECT&MULTI=Y');

	var _templateObject$2, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7;

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }

	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _hintPopup = /*#__PURE__*/new WeakMap();

	var _handleMouseEnter = /*#__PURE__*/new WeakSet();

	var _handleMouseLeave = /*#__PURE__*/new WeakSet();

	var Item = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Item, _EventEmitter);

	  function Item(itemData) {
	    var _this;

	    babelHelpers.classCallCheck(this, Item);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Item).call(this));

	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _handleMouseLeave);

	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _handleMouseEnter);

	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "cache", new main_core.Cache.MemoryCache());
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "properties", {
	      pluggedIn: false,
	      insertedInText: false
	    });

	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _hintPopup, {
	      writable: true,
	      value: void 0
	    });

	    _this.setEventNamespace('Disk:UF:');

	    _this.id = String(itemData['ID']);

	    _this.setData(itemData);

	    _this.subscribe('onMoved', _this.onMoved.bind(babelHelpers.assertThisInitialized(_this)));

	    return _this;
	  }

	  babelHelpers.createClass(Item, [{
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "getFileId",
	    value: function getFileId() {
	      return this.data.FILE_ID;
	    }
	  }, {
	    key: "getData",
	    value: function getData(key) {
	      if (key) {
	        return this.data[key];
	      }

	      return this.data;
	    }
	  }, {
	    key: "getAllIds",
	    value: function getAllIds() {
	      return [this.getId(), ['n', this.data.FILE_ID].join('')];
	    }
	  }, {
	    key: "setData",
	    value: function setData(data) {
	      this.data = data;
	    }
	  }, {
	    key: "setPluggedIn",
	    value: function setPluggedIn() {
	      var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	      this.properties.pluggedIn = value === true;
	    }
	  }, {
	    key: "isPluggedIn",
	    value: function isPluggedIn() {
	      return this.properties.pluggedIn;
	    }
	  }, {
	    key: "setInsertedInText",
	    value: function setInsertedInText() {
	      var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	      this.properties.insertedInText = value === true;
	      main_core.Dom.addClass(this.getContainer(), '--edit-text-preview');
	    }
	  }, {
	    key: "isInsertedInText",
	    value: function isInsertedInText() {
	      return this.properties.insertedInText;
	    }
	  }, {
	    key: "getNameWithoutExtension",
	    value: function getNameWithoutExtension() {
	      var nameParts = this.data['NAME'].split('.');

	      if (nameParts.length > 1) {
	        nameParts.pop();
	      }

	      var nameWithoutExtension = nameParts.join('.');

	      if (nameWithoutExtension.length > 50) {
	        return nameWithoutExtension.substr(0, 39) + '...' + nameWithoutExtension.substr(-5);
	      }

	      return nameWithoutExtension;
	    }
	  }, {
	    key: "getButtonBox",
	    value: function getButtonBox() {
	      var insertInText = '';

	      if (this.isPluggedIn()) {
	        insertInText = main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div\n\t\t\t\t\tclass=\"disk-file-thumb-btn-text-copy\"\n\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\tonmouseenter=\"", "\"\n\t\t\t\t\tonmouseleave=\"", "\"\n\t\t\t\t>\n\t\t\t\t</div>"])), this.onClickInsertInText.bind(this), _classPrivateMethodGet(this, _handleMouseEnter, _handleMouseEnter2).bind(this), _classPrivateMethodGet(this, _handleMouseLeave, _handleMouseLeave2).bind(this));
	      }

	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"disk-file-thumb-btn-box\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"disk-file-thumb-btn-more\" data-bx-role=\"more\" onclick=\"", "\"></div>\n\t\t\t</div>\n\t\t"])), insertInText, this.onClickMore.bind(this));
	    }
	  }, {
	    key: "getDeleteButton",
	    value: function getDeleteButton() {
	      return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"disk-file-thumb-btn-close-box\">\n\t\t\t\t<div class=\"disk-file-thumb-btn-close\" onclick=\"", "\"></div>\n\t\t\t</div>\n\t\t"])), this.onClickDelete.bind(this));
	    }
	  }, {
	    key: "getNameBox",
	    value: function getNameBox(nameWithoutExtension, extension) {
	      var extensionNode = '';

	      if (extension) {
	        extensionNode = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<span class=\"disk-file-thumb-file-extension\">.", "</span>"])), extension);
	      }

	      return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"disk-file-thumb-text-box\">\n\t\t\t\t<div data-bx-role=\"name\" class=\"disk-file-thumb-text\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), nameWithoutExtension, extensionNode);
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon(extension) {
	      return main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div data-bx-role=\"icon\" class=\"ui-icon ui-icon-file-", " disk-file-thumb-icon\"><i></i></div>\n\t\t"])), extension);
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var _this2 = this;

	      return this.cache.remember('container', function () {
	        var nameWithoutExtension = main_core.Text.encode(_this2.getNameWithoutExtension());
	        var extension = main_core.Text.encode(_this2.data['EXTENSION']).toLowerCase();

	        switch (extension) {
	          case 'pptx':
	            extension = 'ppt';
	            break;
	        }

	        return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"disk-file-thumb disk-file-thumb-file disk-file-thumb--", "\">\n\t\t\t", "\n\t\t\t", "\n\t\t\t", "\n\t\t\t", "\n\t\t</div>"])), extension, _this2.getIcon(extension), _this2.getNameBox(nameWithoutExtension, extension), _this2.getDeleteButton(), _this2.getButtonBox());
	      });
	    }
	  }, {
	    key: "rename",
	    value: function rename(newName) {
	      this.data['NAME'] = newName;

	      if (this.hasContainer()) {
	        this.getContainer().querySelector('[data-bx-role="name"]').innerHTML = main_core.Text.encode(this.data['NAME']);
	      }
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      var _this3 = this;

	      var keys = this.cache.keys();
	      keys.forEach(function (key) {
	        var node = _this3.cache.get(key);

	        if (main_core.Type.isDomNode(node) && node.parentNode) {
	          node.parentNode.removeChild(node);
	        }

	        _this3.cache["delete"](key);
	      });
	      this.emit('onDestroy');
	    }
	  }, {
	    key: "hasContainer",
	    value: function hasContainer() {
	      return this.cache.has('container');
	    }
	  }, {
	    key: "getMenu",
	    value: function getMenu() {
	      var _this4 = this;

	      var extension = this.getData('NAME').split('.').pop();
	      extension = extension === this.getData('NAME') ? '' : ['.', extension].join('');
	      var fullName = String(this.getData('NAME'));
	      var cleanName = fullName.substring(0, fullName.length - extension.length);
	      return this.cache.remember('menu', function () {
	        var moreButton = _this4.getContainer().querySelector('div[data-bx-role=\'more\']');

	        var contextMenu = new main_popup.Menu({
	          id: "crm-tunnels-menu-".concat(main_core.Text.getRandom().toLowerCase()),
	          bindElement: moreButton,
	          items: [_this4.isPluggedIn() ? {
	            dataset: {
	              bxRole: 'insertIntoTheText'
	            },
	            text: main_core.Loc.getMessage('WDUF_ITEM_MENU_INSERT_INTO_THE_TEXT'),
	            onclick: function onclick(event, item) {
	              contextMenu.close();

	              _this4.onClickInsertInText(event);
	            }
	          } : null, {
	            dataset: {
	              bxRole: 'deleteFile'
	            },
	            text: main_core.Loc.getMessage('WDUF_ITEM_MENU_DELETE'),
	            onclick: function onclick(event) {
	              contextMenu.close();

	              _this4.onClickDelete(event);
	            }
	          },
	          /*TODO For the Future
	             (this.data['CAN_UPDATE'] === true && this.data['EDITABLE'] === true ? {
	          	text:  Loc.getMessage('WDUF_ITEM_MENU_EDIT'),
	          	className: 'menu-popup-item-edit',
	          	onclick: (event) => {
	          		contextMenu.close();
	          		this.onClickEdit(event);
	          	}
	          } : null),*/
	          _this4.data['CAN_RENAME'] === true ? {
	            dataset: {
	              bxRole: 'renameFile'
	            },
	            text: main_core.Loc.getMessage('WDUF_ITEM_MENU_RENAME'),
	            items: [{
	              html: ["<textarea class=\"disk-file-popup-rename-file-textarea\"\n\t\t\t\t\t\t\t\t\t\t\tname=\"rename\" onkeydown=\"if(event.keyCode===13){ BX.fireEvent(event.target.parentNode.querySelector('input[name=save]'), 'click'); }\"\n\t\t\t\t\t\t\t\t\t\t\tplaceholder=\"".concat(main_core.Text.encode(cleanName), "\">").concat(main_core.Text.encode(cleanName), "</textarea>"), "<div class=\"ui-btn-container ui-btn-container-center\">\n\t\t\t\t\t\t\t\t\t\t\t<input type=\"button\" class=\"ui-btn ui-btn-sm ui-btn-primary\" name=\"save\" value=\"".concat(main_core.Loc.getMessage('WDUF_ITEM_MENU_RENAME_SAVE'), "\">\n\t\t\t\t\t\t\t\t\t\t\t<input type=\"button\" class=\"ui-btn ui-btn-sm ui-btn-link\" value=\"").concat(main_core.Loc.getMessage('WDUF_ITEM_MENU_RENAME_CANCEL'), "\">\n\t\t\t\t\t\t\t\t\t\t</div>")].join(''),
	              className: 'menu-popup-item-rename-form',
	              onclick: function onclick(event, item) {
	                if (main_core.Type.isDomNode(event.target) && event.target.type === 'button') {
	                  if (event.target.name === 'save') {
	                    _this4.onRenamed([item.getContainer().querySelector('textarea').value, extension].join(''));

	                    _this4.clearMenu();
	                  }

	                  contextMenu.close();
	                }
	              }
	            }]
	          } : null, {
	            delimiter: true,
	            text: [main_core.Loc.getMessage('WDUF_ITEM_MENU_FILE'), main_core.Text.encode(_this4.data['SIZE'])].join(' ')
	          }, _this4.data['CAN_MOVE'] === true ? {
	            dataset: {
	              bxRole: 'moveFile'
	            },
	            text: main_core.Text.encode(_this4.data['STORAGE']),
	            className: 'menu-popup-item-storage',
	            onclick: function onclick(event, item) {
	              var _event$counter;

	              event['counter'] = ((_event$counter = event['counter']) !== null && _event$counter !== void 0 ? _event$counter : 1) + 1;
	              contextMenu.close();

	              _this4.onClickMoveTo(event, item);
	            }
	          } : {
	            text: main_core.Text.encode(_this4.data['STORAGE']),
	            className: 'menu-popup-item-storage'
	          }].filter(function (preItem) {
	            return preItem !== null;
	          }),
	          angle: true,
	          offsetLeft: 9
	        });
	        return contextMenu;
	      });
	    }
	  }, {
	    key: "clearMenu",
	    value: function clearMenu() {
	      if (this.cache.has('menu')) {
	        this.cache.get('menu').destroy();
	        this.cache["delete"]('menu');
	      }
	    }
	  }, {
	    key: "onClick",
	    value: function onClick(event) {
	      this.emit('onClick');
	    }
	  }, {
	    key: "onClickDelete",
	    value: function onClickDelete(event) {
	      event.preventDefault();
	      event.stopPropagation();
	      this.emit('onDelete');
	      this.destroy();
	      Backend.deleteAction(this.getId());
	    }
	  }, {
	    key: "onClickInsertInText",
	    value: function onClickInsertInText(event) {
	      this.emit('onClickInsertInText');
	    }
	  }, {
	    key: "onClickMore",
	    value: function onClickMore(event) {
	      event.preventDefault();
	      event.stopPropagation();
	      this.getMenu().show();
	    }
	  }, {
	    key: "onClickEdit",
	    value: function onClickEdit() {}
	  }, {
	    key: "onClickMoveTo",
	    value: function onClickMoveTo(element_id, name, row) {
	      var result = main_core_events.EventEmitter.emit(BX.DiskFileDialog, 'onFileNeedsToMove', [this]);
	      return result.length > 0;
	    }
	  }, {
	    key: "onMoved",
	    value: function onMoved(_ref) {
	      var data = _ref.data;
	      this.data['STORAGE'] = data;
	      this.clearMenu();
	    }
	  }, {
	    key: "onRenamed",
	    value: function onRenamed(newName) {
	      if (this.getData('NAME') === newName) {
	        return;
	      }

	      this.rename(newName);
	      Backend.renameAction(this.getId(), newName);
	    } //region HTMLEditor functions

	  }, {
	    key: "getHTMLForHTMLEditor",
	    value: function getHTMLForHTMLEditor(tagId) {
	      if (this.getData('TYPE_FILE') === 'player') {
	        return "<img contenteditable=\"false\" class=\"bxhtmled-player-surrogate\" data-bx-file-id=\"".concat(main_core.Text.encode(this.data.ID), "\" id=\"").concat(tagId, "\" src=\"data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7\" />");
	      }

	      return "<span contenteditable=\"false\" data-bx-file-id=\"".concat(main_core.Text.encode(this.data.ID), "\" id=\"").concat(tagId, "\" style=\"color: #2067B0; border-bottom: 1px dashed #2067B0; margin:0 2px;\">").concat(main_core.Text.encode(this.data.NAME), "</span>");
	    } //endregion

	  }], [{
	    key: "detect",
	    value: function detect() {
	      return true;
	    }
	  }]);
	  return Item;
	}(main_core_events.EventEmitter);

	function _handleMouseEnter2(event) {
	  var _this5 = this;

	  if (babelHelpers.classPrivateFieldGet(this, _hintPopup)) {
	    return;
	  }

	  var targetNode = event.currentTarget;
	  var targetNodeWidth = targetNode.offsetWidth;
	  babelHelpers.classPrivateFieldSet(this, _hintPopup, new BX.PopupWindow({
	    content: main_core.Loc.getMessage('WDUF_ITEM_MENU_INSERT_INTO_THE_TEXT'),
	    cacheable: false,
	    animation: 'fading-slide',
	    bindElement: targetNode,
	    offsetTop: 0,
	    bindOptions: {
	      position: 'top'
	    },
	    darkMode: true,
	    events: {
	      onClose: function onClose() {
	        babelHelpers.classPrivateFieldGet(_this5, _hintPopup).destroy();
	        babelHelpers.classPrivateFieldSet(_this5, _hintPopup, null);
	      },
	      onShow: function onShow(event) {
	        var popup = event.getTarget();
	        popup.getPopupContainer().style.display = 'block'; // bad hack

	        var offsetLeft = targetNodeWidth / 2 - popup.getPopupContainer().offsetWidth / 2;
	        popup.setOffset({
	          offsetLeft: offsetLeft + 40
	        });
	        popup.setAngle({
	          offset: popup.getPopupContainer().offsetWidth / 2 - 17
	        });
	      }
	    }
	  }));
	  babelHelpers.classPrivateFieldGet(this, _hintPopup).show();
	}

	function _handleMouseLeave2(event) {
	  if (!babelHelpers.classPrivateFieldGet(this, _hintPopup)) {
	    return;
	  }

	  babelHelpers.classPrivateFieldGet(this, _hintPopup).close();
	  babelHelpers.classPrivateFieldSet(this, _hintPopup, null);
	}

	var _templateObject$3;

	var ItemImage = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemImage, _Item);

	  function ItemImage() {
	    var _babelHelpers$getProt;

	    var _this;

	    babelHelpers.classCallCheck(this, ItemImage);

	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }

	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(ItemImage)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "cache", new main_core.Cache.MemoryCache());
	    return _this;
	  }

	  babelHelpers.createClass(ItemImage, [{
	    key: "setData",
	    value: function setData(data) {
	      this.data = data;
	      this.data.BIG_REVIEW_URL = this.data.PREVIEW_URL.replace(/\&(width|height)\=\d+/gi, '');
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var _this2 = this;

	      return this.cache.remember('container', function () {
	        var nameWithoutExtension = main_core.Text.encode(_this2.getNameWithoutExtension());
	        var extension = main_core.Text.encode(_this2.data['EXTENSION']).toLowerCase();
	        return main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"disk-file-thumb disk-file-thumb-preview\">\n\t\t\t<div style=\"background-image: url('", "'); background-size: cover;\" class=\"disk-file-thumb-image\"></div>\n\t\t\t", "\n\t\t\t", "\n\t\t\t", "\n\t\t\t", "\n\t\t</div>"])), encodeURI(_this2.data['PREVIEW_URL']), _this2.getIcon(extension), _this2.getNameBox(nameWithoutExtension, extension), _this2.getDeleteButton(), _this2.getButtonBox());
	      });
	    }
	  }, {
	    key: "getHTMLForHTMLEditor",
	    value: function getHTMLForHTMLEditor(tagId) {
	      return "<img style=\"max-width: 90%;\" data-bx-file-id=\"".concat(main_core.Text.encode(this.data.ID), "\" id=\"").concat(tagId, "\" src=\"").concat(this.data.BIG_REVIEW_URL, "\" />");
	    }
	  }], [{
	    key: "detect",
	    value: function detect(itemData) {
	      return !!itemData['PREVIEW_URL'];
	    }
	  }]);
	  return ItemImage;
	}(Item);

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }

	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var justCounter = 0;

	var _getHTMLByItem = /*#__PURE__*/new WeakSet();

	var FileParser = /*#__PURE__*/function () {
	  function FileParser(fileController) {
	    babelHelpers.classCallCheck(this, FileParser);

	    _classPrivateMethodInitSpec$1(this, _getHTMLByItem);

	    babelHelpers.defineProperty(this, "tag", '[DISK FILE ID=#id#]');
	    babelHelpers.defineProperty(this, "regexp", /\[(?:DOCUMENT ID|DISK FILE ID)=([n0-9]+)\]/ig);
	    this.id = ['diskfile' + justCounter++].join('_');
	    this.items = fileController.items;
	  }

	  babelHelpers.createClass(FileParser, [{
	    key: "getInterface",
	    value: function getInterface() {
	      var _this = this;

	      return {
	        id: this.id,
	        init: function init(htmlEditor) {
	          _this.htmlEditor = htmlEditor;
	        },
	        parse: this.parse.bind(this),
	        unparse: this.unparse.bind(this)
	      };
	    }
	  }, {
	    key: "hasInterface",
	    value: function hasInterface() {
	      return this.regexp !== null && this.tag !== null;
	    }
	  }, {
	    key: "setParams",
	    value: function setParams(_ref) {
	      var tag = _ref.tag,
	          regexp = _ref.regexp;

	      if (main_core.Type.isStringFilled(tag) && tag !== 'null') {
	        this.tag = tag;
	      } else {
	        this.tag = null;
	      }

	      if (main_core.Type.isStringFilled(regexp) && regexp !== 'null') {
	        this.regexp = new RegExp(regexp);
	      } else if (regexp instanceof RegExp) {
	        this.regexp = regexp;
	      } else {
	        this.regexp = null;
	      }
	    }
	  }, {
	    key: "insertFile",
	    value: function insertFile(id) {
	      var item = this.items.get(String(id));

	      if (item) {
	        var bbDelimiter = item instanceof ItemImage ? '\n' : ' ';
	        var htmlDelimiter = item instanceof ItemImage ? '<br>' : '&nbsp;';
	        main_core_events.EventEmitter.emit(this.htmlEditor, 'OnInsertContent', [bbDelimiter + this.getItemBBCode(id) + bbDelimiter, htmlDelimiter + this.getItemHTML(id) + htmlDelimiter]);
	      }
	    }
	  }, {
	    key: "getItemBBCode",
	    value: function getItemBBCode(id) {
	      var item = this.items.get(String(id));

	      if (item && item.isPluggedIn()) {
	        item.setInsertedInText();
	      }

	      return this.tag.replace('#id#', id);
	    }
	  }, {
	    key: "getItemHTML",
	    value: function getItemHTML(id) {
	      var item = this.items.get(String(id));

	      if (item) {
	        return _classPrivateMethodGet$1(this, _getHTMLByItem, _getHTMLByItem2).call(this, item, id);
	      }

	      return null;
	    }
	  }, {
	    key: "deleteFile",
	    value: function deleteFile(fileId) {
	      if (!this.items.has(fileId)) {
	        return;
	      }

	      var fileIds = this.items.get(fileId).getAllIds();

	      if (this.htmlEditor.GetViewMode() === 'wysiwyg') {
	        var doc = this.htmlEditor.GetIframeDoc();

	        for (var ii in this.htmlEditor.bxTags) {
	          if (this.htmlEditor.bxTags.hasOwnProperty(ii) && babelHelpers["typeof"](this.htmlEditor.bxTags[ii]) === 'object' && this.htmlEditor.bxTags[ii]['tag'] === this.id && fileIds.indexOf(String(this.htmlEditor.bxTags[ii]['itemId'])) >= 0 && doc.getElementById(ii)) {
	            var node = doc.getElementById(ii);
	            node.parentNode.removeChild(node);
	          }
	        }

	        this.htmlEditor.SaveContent();
	      } else {
	        var content = this.htmlEditor.GetContent().replace(this.regexp, function (str, foundId) {
	          return fileIds.indexOf(foundId) >= 0 ? '' : str;
	        });
	        this.htmlEditor.SetContent(content);
	        this.htmlEditor.Focus();
	      }
	    }
	  }, {
	    key: "parse",
	    value: function parse(content) {
	      if (!this.regexp.test(content)) {
	        return content;
	      }

	      content = content.replace(this.regexp, function (str, id) {
	        var foundedItem = this.items.has(id) ? this.items.get(id) : babelHelpers.toConsumableArray(this.items.values()).find(function (item) {
	          return item.getAllIds().indexOf(id) >= 0;
	        });

	        if (foundedItem) {
	          return _classPrivateMethodGet$1(this, _getHTMLByItem, _getHTMLByItem2).call(this, foundedItem, id);
	        }

	        return str;
	      }.bind(this));
	      return content;
	    }
	  }, {
	    key: "unparse",
	    value: function unparse(bxTag, _ref2) {
	      var node = _ref2.node;
	      var id = bxTag.itemId;

	      if (this.items.has(id)) {
	        return this.getItemBBCode(id);
	      }

	      return '';
	    }
	  }]);
	  return FileParser;
	}();

	function _getHTMLByItem2(item, id) {
	  if (item.isPluggedIn()) {
	    item.setInsertedInText();
	  }

	  return item.getHTMLForHTMLEditor(this.htmlEditor.SetBxTag(false, {
	    tag: this.id,
	    fileId: id,
	    itemId: item.getId()
	  }));
	}

	var itemMappings = [Item, ItemImage];
	function getItem(itemData) {
	  var itemClassName = Item;
	  itemMappings.forEach(function (itemClass) {
	    if (itemClass.detect(itemData)) {
	      itemClassName = itemClass;
	    }
	  });
	  return new itemClassName(itemData);
	}

	var FileController = /*#__PURE__*/function (_DefaultController) {
	  babelHelpers.inherits(FileController, _DefaultController);

	  function FileController(_ref) {
	    var _this;

	    var id = _ref.id,
	        container = _ref.container,
	        fieldName = _ref.fieldName,
	        multiple = _ref.multiple,
	        eventObject = _ref.eventObject;
	    babelHelpers.classCallCheck(this, FileController);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FileController).call(this, {
	      container: container.querySelector('[data-bx-role="placeholder"]'),
	      eventObject: eventObject
	    }));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "maxVisibleCount", 10);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "prefixHTMLNode", 'disk-attach-');
	    _this.items = new Map();
	    _this.multiple = multiple !== false;
	    _this.fieldName = fieldName;

	    if (!container.querySelector('[data-bx-role="placeholder"]')) {
	      return babelHelpers.possibleConstructorReturn(_this);
	    }

	    if (_this.isPluggedIn()) {
	      _this.buildInHTMLEditor();
	    }

	    return _this;
	  }

	  babelHelpers.createClass(FileController, [{
	    key: "buildInHTMLEditor",
	    value: function buildInHTMLEditor() {
	      var _this2 = this;

	      if (this.getParser().hasInterface()) {
	        main_core_events.EventEmitter.emit(this.eventObject, 'OnParserRegister', this.getParser().getInterface());
	      }

	      main_core_events.EventEmitter.subscribe(this.eventObject, 'onReinitializeBefore', function (_ref2) {
	        var _ref2$data = babelHelpers.slicedToArray(_ref2.data, 2),
	            text = _ref2$data[0],
	            fileData = _ref2$data[1];

	        var manualClearing = true;

	        if (fileData) {
	          Object.values(fileData).forEach(function (uf) {
	            if (uf && uf['USER_TYPE_ID'] === "disk_file" && uf['FIELD_NAME'].replace('[]', '') === _this2.fieldName.replace('[]', '')) {
	              try {
	                var items = [];
	                var duplicateControlItems = {};

	                if (main_core.Type.isArray(uf['VALUE'])) {
	                  uf['VALUE'].forEach(function (id) {
	                    var node = document.querySelector(['#', _this2.prefixHTMLNode, id].join(''));
	                    var stringId = String(id);

	                    if (!node || duplicateControlItems[stringId]) {
	                      return;
	                    }

	                    duplicateControlItems[stringId] = true;
	                    var img = node.querySelector('img') || node.querySelector('div[data-bx-preview]');
	                    var infoNode = img || node;
	                    var name = infoNode.hasAttribute("data-title") ? infoNode.getAttribute("data-title") : infoNode.hasAttribute("data-bx-title") ? infoNode.getAttribute("data-bx-title") : '';
	                    var itemData = {
	                      ID: id,
	                      FILE_ID: infoNode.getAttribute("bx-attach-file-id"),
	                      // IS_LOCKED: boolean
	                      // IS_MARK_DELETED: boolean
	                      // EDITABLE: boolean
	                      // FROM_EXTERNAL_SYSTEM: boolean
	                      CAN_RESTORE: false,
	                      CAN_UPDATE: false,
	                      CAN_RENAME: false,
	                      CAN_MOVE: false,
	                      COPY_TO_ME_URL: null,
	                      DELETE_URL: null,
	                      DOWNLOAD_URL: null,
	                      EDIT_URL: null,
	                      VIEW_URL: null,
	                      PREVIEW_URL: null,
	                      BIG_PREVIEW_URL: null,
	                      EXTENSION: name.split('.').pop(),
	                      NAME: name,
	                      SIZE: infoNode.getAttribute("data-bx-size"),
	                      SIZE_BYTES: infoNode.getAttribute("data-bx-size"),
	                      STORAGE: 'disk',
	                      TYPE_FILE: infoNode.getAttribute("bx-attach-file-type") // width: node.getAttribute("data-bx-width"),
	                      // height: node.getAttribute("data-bx-height"),

	                    };

	                    if (img) {
	                      itemData['PREVIEW_URL'] = img.hasAttribute('data-bx-preview') && main_core.Type.isStringFilled(img.getAttribute('data-bx-preview')) ? img.getAttribute('data-bx-preview') : img.hasAttribute('data-thumb-src') && main_core.Type.isStringFilled(img.getAttribute('data-thumb-src')) ? img.getAttribute('data-thumb-src') : img.src;
	                      itemData['BIG_PREVIEW_URL'] = img.hasAttribute("data-bx-src") ? img.getAttribute("data-bx-src") : img.getAttribute('data-src');
	                    }

	                    items.push(itemData);
	                  });
	                }

	                _this2.set(items);

	                manualClearing = false;
	              } catch (e) {
	                console.log('e: ', e);
	              }
	            }
	          });
	        }

	        if (manualClearing !== false) {
	          _this2.clear();
	        }
	      });
	    }
	  }, {
	    key: "set",
	    value: function set(values) {
	      var _this3 = this;

	      this.clear();
	      var counter = this.maxVisibleCount;
	      return new Promise(function (resolve) {
	        values.forEach(function (itemData) {
	          var item = _this3.addItem(itemData);

	          counter--;

	          if (counter > 0) {
	            _this3.appendNode(item);
	          } else if (counter === 0) {
	            _this3.container.appendChild(_this3.getItemMore().reset().getContainer());
	          } else {
	            _this3.getItemMore().increment();
	          }
	        });
	        resolve();
	      });
	    }
	  }, {
	    key: "clear",
	    value: function clear() {
	      this.items.forEach(function (item) {
	        item.destroy();
	      });
	      this.container.innerHTML = '';
	    } // to add into DOM fom uploader

	  }, {
	    key: "add",
	    value: function add(itemData, itemContainer) {
	      this.multiple || this.clear();
	      var item = this.addItem(itemData);
	      this.appendNode(item, itemContainer);
	      return item;
	    }
	  }, {
	    key: "addItem",
	    value: function addItem(itemData) {
	      var _this4 = this;

	      var item = getItem(itemData);
	      var input = this.getContainer().querySelector("[data-bx-role=\"reserve-item\"][value=\"".concat(main_core.Text.encode(item.getId()), "\"]"));

	      if (!input) {
	        input = document.createElement('INPUT');
	        input.name = this.fieldName;
	        input.type = 'hidden';
	        input.value = item.getId();
	        this.container.appendChild(input);
	      }

	      item.subscribe('onDelete', function () {
	        if (input && input.parentNode) {
	          input.parentNode.removeChild(input);
	        }

	        main_core_events.EventEmitter.emit(_this4, 'OnItemDelete', item);
	      });
	      item.subscribe('onDestroy', function () {
	        _this4.items["delete"](item.getId());
	      });

	      if (this.isPluggedIn()) {
	        main_core_events.EventEmitter.emit(this.eventObject, 'onShowControllers:File:Increment');

	        if (this.getParser().hasInterface()) {
	          item.setPluggedIn();
	          item.subscribe('onClickInsertInText', function () {
	            _this4.getParser().insertFile(item.getId());
	          });
	          item.subscribe('onDelete', function () {
	            _this4.getParser().deleteFile(item.getId());
	          });
	          item.subscribe('onDestroy', function () {
	            main_core_events.EventEmitter.emit(_this4.eventObject, 'onShowControllers:File:Decrement');
	          });
	        }
	      }

	      this.items.set(item.getId(), item);
	      return item;
	    }
	  }, {
	    key: "getItem",
	    value: function getItem$$1(id) {
	      return this.items.get(id);
	    }
	    /*@
	    Appends node to the container
	     */

	  }, {
	    key: "appendNode",
	    value: function appendNode(item, itemContainer) {
	      if (itemContainer) {
	        if (itemContainer.parentNode) {
	          itemContainer.parentNode.replaceChild(item.getContainer(), itemContainer);
	        }
	      } else {
	        this.container.appendChild(item.getContainer());
	      }

	      return item;
	    }
	  }, {
	    key: "getItemMore",
	    value: function getItemMore() {
	      var _this5 = this;

	      return this.cache.remember('moreButton', function () {
	        var res = new ItemMoreButton();
	        main_core_events.EventEmitter.subscribe(res, 'onGetMore', _this5.showMoreItems.bind(_this5));
	        return res;
	      });
	    }
	  }, {
	    key: "showMoreItems",
	    value: function showMoreItems(_ref3) {
	      var itemsCount = _ref3.data.itemsCount;
	      var timeoutCounter = itemsCount;
	      var itemMoreNode = this.getItemMore().getContainer();
	      itemMoreNode.style.opacity = '0';
	      itemMoreNode.style.visibility = 'hidden';
	      this.items.forEach(function (item) {
	        if (itemsCount > 0) {
	          if (!item.hasContainer()) {
	            itemsCount--;
	            var node = document.createElement('DIV');
	            itemMoreNode.parentNode.insertBefore(node, itemMoreNode);
	            setTimeout(function () {
	              main_core.Dom.style(item.getContainer(), 'opacity', '0');
	              main_core.Dom.addClass(item.getContainer(), 'disk-file-thumb--animate');
	              main_core.Dom.style(item.getContainer(), 'opacity', '');
	              node.parentNode.replaceChild(item.getContainer(), node);
	              item.getContainer().addEventListener('transitionend', function () {
	                main_core.Dom.removeClass(item.getContainer(), 'disk-file-thumb--animate');
	              });
	            }, (timeoutCounter - itemsCount) * 100);
	          }
	        }
	      });

	      if (itemsCount <= 0) {
	        setTimeout(function () {
	          itemMoreNode.style.opacity = '';
	          itemMoreNode.style.visibility = '';
	        }, timeoutCounter * 100 + 100);
	      }
	    }
	  }, {
	    key: "getParser",
	    value: function getParser() {
	      this.parser = this.parser || new FileParser(this);
	      return this.parser;
	    }
	  }]);
	  return FileController;
	}(DefaultController);

	var SettingsController = /*#__PURE__*/function (_DefaultController) {
	  babelHelpers.inherits(SettingsController, _DefaultController);

	  function SettingsController(_ref) {
	    var _this;

	    var container = _ref.container,
	        eventObject = _ref.eventObject;
	    babelHelpers.classCallCheck(this, SettingsController);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SettingsController).call(this, {
	      container: container.querySelector('[data-bx-role="setting"]'),
	      eventObject: eventObject
	    }));

	    if (_this.getContainer()) {
	      _this.getContainer().addEventListener('click', _this.show.bind(babelHelpers.assertThisInitialized(_this)));

	      _this.allowEditNode = container.querySelector('input[data-bx-role="settings-allow-edit"]');
	      _this.allowGridNode = container.querySelector('input[data-bx-role="settings-allow-grid"]');
	    }

	    return _this;
	  }

	  babelHelpers.createClass(SettingsController, [{
	    key: "show",
	    value: function show() {
	      var _this2 = this;

	      if (!this.popup) {
	        this.popup = new main_popup.Menu({
	          bindElement: this.getContainer(),
	          className: 'disk-uf-file-popup-settings',
	          items: [this.allowEditNode ? {
	            dataset: {
	              bxRole: 'allowEdit'
	            },
	            className: this.allowEditNode.checked ? 'menu-popup-item-take' : '',
	            text: main_core.Loc.getMessage('WDUF_ALLOW_EDIT'),
	            onclick: function (event, item) {
	              this.allowEditNode.checked = !this.allowEditNode.checked;

	              if (this.allowEditNode.checked) {
	                item.getContainer().classList.add('menu-popup-item-take');
	                item.getContainer().classList.remove('menu-popup-no-icon');
	              } else {
	                item.getContainer().classList.remove('menu-popup-item-take');
	                item.getContainer().classList.add('menu-popup-no-icon');
	              }
	            }.bind(this)
	          } : null, {
	            dataset: {
	              bxRole: 'allowGrid'
	            },
	            className: this.allowGridNode.checked ? 'menu-popup-item-take' : '',
	            text: main_core.Loc.getMessage('WDUF_ALLOW_COLLAGE'),
	            onclick: function (event, item) {
	              this.allowGridNode.checked = !this.allowGridNode.checked;

	              if (this.allowGridNode.dataset.bxSave === 'Y') {
	                BX.userOptions.save('disk', 'disk.uf.file', this.allowGridNode.dataset.bxName, this.allowGridNode.checked ? 'grid' : '.default');
	              }

	              if (this.allowGridNode.checked) {
	                item.getContainer().classList.add('menu-popup-item-take');
	                item.getContainer().classList.remove('menu-popup-no-icon');
	              } else {
	                item.getContainer().classList.remove('menu-popup-item-take');
	                item.getContainer().classList.add('menu-popup-no-icon');
	              }
	            }.bind(this)
	          }, {
	            text: this.buildDocumentServiceTextLabel(),
	            items: this.buildSubMenuWithDocumentServices()
	          }],
	          angle: true,
	          offsetTop: -16,
	          offsetLeft: 16,
	          events: {
	            onClose: function onClose() {
	              delete _this2.popup;
	            }
	          }
	        });
	      }

	      this.popup.show();
	    }
	  }, {
	    key: "buildDocumentServiceTextLabel",
	    value: function buildDocumentServiceTextLabel() {
	      var currentService = BX.Disk.getDocumentService();

	      if (!currentService && BX.Disk.isAvailableOnlyOffice()) {
	        currentService = 'onlyoffice';
	      } else if (!currentService) {
	        currentService = 'l';
	      }

	      var name = Options.getDocumentHandler(currentService).name;
	      return main_core.Loc.getMessage('DISK_UF_FILE_EDIT_SERVICE_LABEL', {
	        '#NAME#': name
	      });
	    }
	  }, {
	    key: "buildSubMenuWithDocumentServices",
	    value: function buildSubMenuWithDocumentServices() {
	      var _this3 = this;

	      var items = [];
	      Options.getDocumentHandlers().forEach(function (item) {
	        items.push({
	          text: item.name,
	          dataset: {
	            code: item.code
	          },
	          onclick: function onclick(event, item) {
	            BX.Disk.saveDocumentService(item.dataset.code);
	            item.getMenuWindow().getParentMenuItem().setText(_this3.buildDocumentServiceTextLabel());
	            item.getMenuWindow().getPopupWindow().close();
	          }
	        });
	      });
	      return items;
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      if (this.popup) {
	        this.popup.close();
	      }
	    }
	  }]);
	  return SettingsController;
	}(DefaultController);

	var DocumentController = /*#__PURE__*/function (_DefaultController) {
	  babelHelpers.inherits(DocumentController, _DefaultController);

	  function DocumentController(_ref) {
	    var _this;

	    var container = _ref.container,
	        eventObject = _ref.eventObject;
	    babelHelpers.classCallCheck(this, DocumentController);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DocumentController).call(this, {
	      container: container.querySelector('[data-bx-role="document-area"]'),
	      eventObject: eventObject
	    }));

	    if (_this.getContainer()) {
	      Array.from(_this.getContainer().querySelectorAll('[data-bx-handler]')).forEach(function (item) {
	        item.addEventListener('click', function () {
	          _this.createDocument(item.getAttribute('data-bx-handler'));
	        });
	      });
	    }

	    return _this;
	  }

	  babelHelpers.createClass(DocumentController, [{
	    key: "createDocument",
	    value: function createDocument(documentType) {
	      if (!BX.Disk.getDocumentService() && BX.Disk.isAvailableOnlyOffice()) {
	        BX.Disk.saveDocumentService('onlyoffice');
	      } else if (!BX.Disk.getDocumentService()) {
	        BX.Disk.saveDocumentService('l');
	      }

	      var insertDocumentIntoUf = function (extendedFileData) {
	        var _this2 = this;

	        var parts = extendedFileData.object.name.split('.');
	        parts.pop();
	        setTimeout(function () {
	          main_core_events.EventEmitter.emit(_this2, 'onFileIsCreated', {
	            itemData: _this2.convertToItemSavedType(extendedFileData)
	          });
	        }, 200);
	      }.bind(this);

	      if (BX.Disk.Document.Local.Instance.isSetWorkWithLocalBDisk()) {
	        BX.Disk.Document.Local.Instance.createFile({
	          type: documentType
	        }).then(function (response) {
	          insertDocumentIntoUf(response);
	        }.bind(this));
	        return;
	      }

	      var createProcess = new BX.Disk.Document.CreateProcess({
	        typeFile: documentType,
	        serviceCode: BX.Disk.getDocumentService(),
	        onAfterSave: function onAfterSave(response, extendedFileData) {
	          if (response.status !== 'success') {
	            return;
	          }

	          if (!extendedFileData) {
	            Backend.getMetaDataForCreatedFileInUf(response.object.id).then(function (_ref2) {
	              var data = _ref2.data;
	              insertDocumentIntoUf(data);
	            });
	          } else {
	            insertDocumentIntoUf(extendedFileData);
	          }
	        }
	      });
	      createProcess.start();
	    }
	  }, {
	    key: "convertToItemSavedType",
	    value: function convertToItemSavedType(extendedFileData) {
	      return {
	        ID: 'n' + extendedFileData.object.id,
	        IS_LOCKED: false,
	        IS_MARK_DELETED: false,
	        EDITABLE: false,
	        FROM_EXTERNAL_SYSTEM: false,
	        CAN_RESTORE: false,
	        CAN_UPDATE: true,
	        CAN_RENAME: true,
	        CAN_MOVE: true,
	        COPY_TO_ME_URL: null,
	        DELETE_URL: null,
	        DOWNLOAD_URL: null,
	        EDIT_URL: null,
	        VIEW_URL: extendedFileData.link,
	        PREVIEW_URL: null,
	        BIG_PREVIEW_URL: null,
	        EXTENSION: extendedFileData.object.extension,
	        NAME: extendedFileData.object.name,
	        SIZE: extendedFileData.object.size,
	        SIZE_BYTES: extendedFileData.object.sizeInt,
	        STORAGE: extendedFileData.folderName
	      };
	    }
	  }]);
	  return DocumentController;
	}(DefaultController);

	var PanelController = /*#__PURE__*/function (_DefaultController) {
	  babelHelpers.inherits(PanelController, _DefaultController);

	  function PanelController(_ref) {
	    var container = _ref.container,
	        eventObject = _ref.eventObject;
	    babelHelpers.classCallCheck(this, PanelController);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PanelController).call(this, {
	      container: container.querySelector('[data-bx-role="control-panel"]'),
	      eventObject: eventObject
	    }));
	  }

	  return PanelController;
	}(DefaultController);

	var justCounter$1 = 0;

	var FileSelector = /*#__PURE__*/function (_DefaultController) {
	  babelHelpers.inherits(FileSelector, _DefaultController);

	  function FileSelector(_ref) {
	    var _this;

	    var container = _ref.container,
	        eventObject = _ref.eventObject;
	    babelHelpers.classCallCheck(this, FileSelector);
	    var node = container.querySelector('[data-bx-role="file-local-controller"]') || container.querySelector('.diskuf-selector-link');
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FileSelector).call(this, {
	      container: node,
	      eventObject: eventObject
	    }));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "opened", false);
	    _this.dialogName = ['selectFile', justCounter$1++].join('-');

	    if (!_this.getContainer()) {
	      return babelHelpers.possibleConstructorReturn(_this);
	    }

	    _this.getContainer().addEventListener('click', _this.onClick.bind(babelHelpers.assertThisInitialized(_this)));

	    _this.openSection = _this.openSection.bind(babelHelpers.assertThisInitialized(_this));
	    _this.selectFile = _this.selectFile.bind(babelHelpers.assertThisInitialized(_this));
	    main_core_events.EventEmitter.subscribe(BX.DiskFileDialog, 'loadItems', _this.openSection);
	    return _this;
	  }

	  babelHelpers.createClass(FileSelector, [{
	    key: "onClick",
	    value: function onClick() {
	      var _this2 = this;

	      Backend.getSelectedData(this.dialogName).then(function () {
	        setTimeout(function () {
	          BX.DiskFileDialog.obCallback[_this2.dialogName] = {
	            saveButton: _this2.selectFile
	          };
	          BX.DiskFileDialog.openDialog(_this2.dialogName);
	        }, 10);
	      });
	    }
	  }, {
	    key: "openSection",
	    value: function openSection(_ref2) {
	      var _ref2$data = babelHelpers.slicedToArray(_ref2.data, 2),
	          link = _ref2$data[0],
	          name = _ref2$data[1];

	      if (name === this.dialogName) {
	        BX.DiskFileDialog.target[name] = main_core.Uri.addParam(link, {
	          dialog2: 'Y'
	        });
	      }
	    }
	  }, {
	    key: "selectFile",
	    value: function selectFile(tab, path, selected) {
	      main_core_events.EventEmitter.unsubscribe(BX.DiskFileDialog, 'loadItems', this.openSection);

	      for (var id in selected) {
	        if (selected.hasOwnProperty(id)) {
	          main_core_events.EventEmitter.emit(this, 'onUploadDone', {
	            itemData: this.convertToItemSavedType(selected[id])
	          });
	        }
	      }
	    }
	  }, {
	    key: "convertToItemSavedType",
	    value: function convertToItemSavedType(item) {
	      var _item$storage;

	      return {
	        ID: item.id,
	        IS_LOCKED: false,
	        IS_MARK_DELETED: false,
	        EDITABLE: false,
	        FROM_EXTERNAL_SYSTEM: false,
	        CAN_RESTORE: false,
	        CAN_RENAME: false,
	        CAN_UPDATE: false,
	        CAN_MOVE: false,
	        COPY_TO_ME_URL: null,
	        DELETE_URL: null,
	        DOWNLOAD_URL: null,
	        EDIT_URL: null,
	        VIEW_URL: null,
	        PREVIEW_URL: item.previewUrl ? item.previewUrl : '',
	        BIG_PREVIEW_URL: item.previewUrl ? item.previewUrl.replace(/\&(width|height)=\d+/gi, '') : null,
	        EXTENSION: item.ext,
	        NAME: item.name,
	        SIZE: item.size,
	        SIZE_BYTES: item.sizeInt,
	        STORAGE: (_item$storage = item['storage']) !== null && _item$storage !== void 0 ? _item$storage : main_core.Loc.getMessage('WDUF_MY_DISK')
	      };
	    }
	  }]);
	  return FileSelector;
	}(DefaultController);

	var ItemNewSelectedCloud = /*#__PURE__*/function (_ItemNew) {
	  babelHelpers.inherits(ItemNewSelectedCloud, _ItemNew);

	  function ItemNewSelectedCloud(fileId, fileObject) {
	    var _this;

	    babelHelpers.classCallCheck(this, ItemNewSelectedCloud);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemNewSelectedCloud).call(this, fileId, fileObject));
	    BX.Disk.ExternalLoader.startLoad({
	      file: {
	        id: _this.id,
	        name: _this.object.name,
	        service: _this.object.service
	      },
	      onFinish: function onFinish(newData) {
	        main_core_events.EventEmitter.emit(babelHelpers.assertThisInitialized(_this), 'onUploadDone', [newData]);
	      },
	      onProgress: function onProgress(progress) {
	        _this.onUploadProgress({
	          compatData: [{}, progress]
	        });
	      }
	    });
	    return _this;
	  }

	  babelHelpers.createClass(ItemNewSelectedCloud, [{
	    key: "onClickDelete",
	    value: function onClickDelete(event) {
	      event.preventDefault();
	      event.stopPropagation();
	      main_core_events.EventEmitter.emit(this, 'onDelete', [this]);
	      delete this.container;
	    }
	  }, {
	    key: "onUploadDone",
	    value: function onUploadDone(_ref) {
	      var _ref$compatData = babelHelpers.slicedToArray(_ref.compatData, 2),
	          fileObject = _ref$compatData[0],
	          file = _ref$compatData[1].file;
	    }
	  }, {
	    key: "onUploadError",
	    value: function onUploadError(_ref2) {
	      var _ref2$compatData = babelHelpers.slicedToArray(_ref2.compatData, 1),
	          fileObject = _ref2$compatData[0];

	      main_core_events.EventEmitter.emit(this, 'onUploadError', [fileObject.file]);
	      this.progress.getContainer().parentNode.removeChild(this.progress.getContainer());
	      this.container.classList.add('disk-file-upload-error');
	    }
	  }]);
	  return ItemNewSelectedCloud;
	}(ItemNew);

	var justCounter$2 = 0;

	var FileSelectorCloud = /*#__PURE__*/function (_DefaultController) {
	  babelHelpers.inherits(FileSelectorCloud, _DefaultController);

	  function FileSelectorCloud(_ref) {
	    var _this;

	    var container = _ref.container,
	        eventObject = _ref.eventObject;
	    babelHelpers.classCallCheck(this, FileSelectorCloud);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FileSelectorCloud).call(this, {
	      container: container.querySelector('[data-bx-role="placeholder"]'),
	      eventObject: eventObject
	    }));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "services", []);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "items", new Map());

	    if (!_this.getContainer()) {
	      return babelHelpers.possibleConstructorReturn(_this);
	    }

	    Array.from(container.querySelectorAll('[data-bx-role="file-external-controller"]')).forEach(function (item) {
	      _this.services.push(item);
	    });
	    Array.from(container.querySelectorAll('.diskuf-selector-link-cloud')).forEach(function (item) {
	      _this.services.push(item);
	    });
	    _this.eventObject = eventObject;
	    _this.dialogName = ['selectFileCloud', justCounter$2++].join('-');

	    _this.services.forEach(function (node) {
	      node.addEventListener('click', _this.onClick.bind(babelHelpers.assertThisInitialized(_this)));
	    });

	    _this.openSection = _this.openSection.bind(babelHelpers.assertThisInitialized(_this));
	    _this.selectFile = _this.selectFile.bind(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }

	  babelHelpers.createClass(FileSelectorCloud, [{
	    key: "onClick",
	    value: function onClick(event) {
	      var _this2 = this;

	      main_core_events.EventEmitter.subscribe(BX.DiskFileDialog, 'loadItems', this.openSection);
	      this.currentService = event.currentTarget.getAttribute('data-bx-doc-handler');
	      Backend.getSelectedCloudData(this.dialogName, this.currentService).then(function () {
	        setTimeout(function () {
	          BX.DiskFileDialog.obCallback[_this2.dialogName] = {
	            saveButton: _this2.selectFile
	          };
	          BX.DiskFileDialog.openDialog(_this2.dialogName);
	        }, 10);
	      });
	    }
	  }, {
	    key: "openSection",
	    value: function openSection(_ref2) {
	      var _ref2$data = babelHelpers.slicedToArray(_ref2.data, 2),
	          link = _ref2$data[0],
	          name = _ref2$data[1];

	      if (name === this.dialogName) {
	        BX.DiskFileDialog.target[name] = main_core.Uri.addParam(link, {
	          dialog2: 'Y'
	        });
	      }
	    }
	  }, {
	    key: "selectFile",
	    value: function selectFile(tab, path, selected) {
	      main_core_events.EventEmitter.unsubscribe(BX.DiskFileDialog, 'loadItems', this.openSection);

	      for (var id in selected) {
	        if (selected.hasOwnProperty(id) && selected[id].type === 'file') {
	          this.catchFile(selected[id], this.currentService);
	        }
	      }

	      this.currentService = null;
	    }
	  }, {
	    key: "catchFile",
	    value: function catchFile(fileObject, service) {
	      var _this3 = this;

	      if (this.items.has(fileObject.id)) {
	        return;
	      }

	      fileObject['service'] = fileObject['provider'] || service;
	      var item = new ItemNewSelectedCloud(fileObject.id, fileObject);
	      this.items.set(item.id, item);
	      main_core_events.EventEmitter.subscribe(item, 'onUploadDone', function (_ref3) {
	        var _ref3$data = babelHelpers.slicedToArray(_ref3.data, 1),
	            itemData = _ref3$data[0];

	        if (_this3.items.has(item.id)) {
	          _this3.items["delete"](item.id);

	          main_core_events.EventEmitter.emit(_this3, 'onUploadDone', {
	            itemData: _this3.convertToItemSavedType(itemData),
	            itemContainer: item.getContainer()
	          });
	        }
	      });
	      main_core_events.EventEmitter.subscribe(item, 'onUploadError', function () {
	        _this3.items["delete"](item.id);

	        main_core_events.EventEmitter.emit(_this3, 'onUploadError', {
	          itemContainer: item.getContainer()
	        });
	      });
	      main_core_events.EventEmitter.subscribe(item, 'onDelete', function () {
	        _this3.items["delete"](item.id);

	        _this3.container.removeChild(item.getContainer());
	      });
	      this.container.appendChild(item.getContainer());
	    }
	  }, {
	    key: "convertToItemSavedType",
	    value: function convertToItemSavedType(item) {
	      var extension = item.name.split('.').pop().toLowerCase();
	      var itemData = {
	        ID: item.ufId,
	        IS_LOCKED: false,
	        IS_MARK_DELETED: false,
	        EDITABLE: false,
	        FROM_EXTERNAL_SYSTEM: true,
	        CAN_RESTORE: false,
	        CAN_RENAME: false,
	        CAN_UPDATE: false,
	        CAN_MOVE: false,
	        COPY_TO_ME_URL: null,
	        DELETE_URL: null,
	        DOWNLOAD_URL: null,
	        EDIT_URL: null,
	        VIEW_URL: null,
	        PREVIEW_URL: item.previewUrl ? item.previewUrl : '',
	        BIG_PREVIEW_URL: item.previewUrl ? item.previewUrl.replace(/\&(width|height)=\d+/gi, '') : null,
	        EXTENSION: extension === item.name ? '' : extension,
	        NAME: item.name,
	        SIZE: item.sizeFormatted,
	        SIZE_BYTES: item.size,
	        STORAGE: item.storage
	      };
	      return itemData;
	    }
	  }]);
	  return FileSelectorCloud;
	}(DefaultController);

	var FileMover = /*#__PURE__*/function () {
	  function FileMover() {
	    babelHelpers.classCallCheck(this, FileMover);
	    babelHelpers.defineProperty(this, "dialogName", 'moveFile');
	    this.openSection = this.openSection.bind(this);
	    this.loadFolder = this.loadFolder.bind(this);
	    this.stopLoadingFolder = this.stopLoadingFolder.bind(this);
	    this.checkFileName = this.checkFileName.bind(this);
	    this.onApply = this.onApply.bind(this);
	    this.onCancel = this.onCancel.bind(this);
	  }

	  babelHelpers.createClass(FileMover, [{
	    key: "fire",
	    value: function fire(item) {
	      var _this = this;

	      if (this.item !== null && this.item !== item) {
	        this.onCancel();
	      }

	      this.item = item;
	      Backend.getSelectedFile(item.getFileId(), item.getData('NAME'), this.dialogName).then(function () {
	        setTimeout(function () {
	          BX.DiskFileDialog.obCallback[_this.dialogName] = {
	            saveButton: _this.onApply,
	            cancelButton: _this.onCancel
	          };
	          BX.DiskFileDialog.openDialog(_this.dialogName);
	        }, 10);
	      });
	      main_core_events.EventEmitter.subscribe(BX.DiskFileDialog, 'loadItems', this.openSection); //EventEmitter.subscribe(BX.DiskFileDialog, 'loadItemsDone', this.checkFileName);

	      main_core_events.EventEmitter.subscribe(BX.DiskFileDialog, 'selectItem', this.loadFolder);
	      main_core_events.EventEmitter.subscribe(BX.DiskFileDialog, 'unSelectItem', this.stopLoadingFolder);
	    }
	  }, {
	    key: "openSection",
	    value: function openSection(_ref) {
	      var _ref$data = babelHelpers.slicedToArray(_ref.data, 2),
	          link = _ref$data[0],
	          someDialogName = _ref$data[1];

	      if (someDialogName === this.dialogName) {
	        BX.DiskFileDialog.target[someDialogName] = main_core.Uri.addParam(link, {
	          dialog2: 'Y'
	        });
	      }
	    }
	  }, {
	    key: "loadFolder",
	    value: function loadFolder(_ref2) {
	      var _this2 = this;

	      var _ref2$data = babelHelpers.slicedToArray(_ref2.data, 3),
	          element = _ref2$data[0],
	          itemId = _ref2$data[1],
	          someDialogName = _ref2$data[2];

	      if (someDialogName !== this.dialogName) {
	        return;
	      }

	      Backend.loadFolder(itemId.substr(1), this.item.getData('NAME'), this.dialogName).then(function (result) {
	        var documentExists = result.permission === true && result["okmsg"] !== '';

	        if (_this2.timeout > 0) {
	          clearTimeout(_this2.timeout);
	        }

	        _this2.timeout = setTimeout(function () {
	          if (documentExists) {
	            BX.DiskFileDialog.showNotice(main_core.Loc.getMessage('WDUF_FILE_IS_EXISTS'), _this2.dialogName);
	          } else {
	            BX.DiskFileDialog.closeNotice(_this2.dialogName);
	          }
	        }, 200);
	      });
	    }
	  }, {
	    key: "stopLoadingFolder",
	    value: function stopLoadingFolder(_ref3) {
	      var someData = _ref3.data;

	      if (this.timeout > 0) {
	        clearTimeout(this.timeout);
	      }

	      this.timeout = setTimeout(this.checkFileName, 200);
	    }
	  }, {
	    key: "onApply",
	    value: function onApply(tab, path, selected, folderByPath) {
	      var _this3 = this;

	      main_core_events.EventEmitter.unsubscribe(BX.DiskFileDialog, 'loadItems', this.openSection); //EventEmitter.unsubscribe(BX.DiskFileDialog, 'loadItemsDone', this.checkFileName);

	      main_core_events.EventEmitter.unsubscribe(BX.DiskFileDialog, 'selectItem', this.loadFolder);
	      main_core_events.EventEmitter.unsubscribe(BX.DiskFileDialog, 'unSelectItem', this.stopLoadingFolder);

	      if (!this.item) {
	        return;
	      }

	      var id = this.item.getId();
	      var moved = false;

	      var moveQuery = function moveQuery(id, targetFolderId, sectionProperties, sectionPath) {
	        Backend.moveFile(id, targetFolderId).then(function (response) {
	          if (!response || response.status !== 'success') {
	            BX.Disk.showModalWithStatusAction(response);
	            return;
	          }

	          _this3.showMovedFile(id, sectionProperties, sectionPath);
	        });
	      };

	      var sectionPath, sectionProperties;

	      for (var i in selected) {
	        if (selected.hasOwnProperty(i) && selected[i].type === 'folder') {
	          sectionPath = tab.name + selected[i].path;
	          sectionProperties = {
	            sectionID: i,
	            iblockID: tab.iblock_id
	          };
	          moveQuery(id, selected[i].id, sectionProperties, sectionPath);
	          moved = true;
	        }
	      }

	      if (!moved) {
	        sectionPath = tab.name;
	        sectionProperties = {
	          sectionID: tab.section_id,
	          iblockID: tab.iblock_id
	        };

	        if (!!folderByPath && !!folderByPath.path && folderByPath.path !== '/') {
	          sectionPath += folderByPath.path;
	          sectionProperties.sectionID = folderByPath.id;

	          if (!!folderByPath) {
	            moveQuery(id, folderByPath.id, sectionProperties, sectionPath);
	          }
	        }
	      }
	    }
	  }, {
	    key: "checkFileName",
	    value: function checkFileName(someDialogName) {
	      if (this.timeout > 0) {
	        clearTimeout(this.timeout);
	      }

	      if (someDialogName !== this.dialogName || !this.item) {
	        return;
	      }

	      var fileName = this.item.getData('NAME');
	      var exist = false;

	      for (var i in BX.DiskFileDialog.obItems[this.dialogName]) {
	        if (BX.DiskFileDialog.obItems[this.dialogName].hasOwnProperty(i) && BX.DiskFileDialog.obItems[this.dialogName][i]['name'] === fileName) {
	          exist = true;
	          break;
	        }
	      }

	      if (exist) {
	        BX.DiskFileDialog.showNotice(main_core.Loc.getMessage('WDUF_FILE_IS_EXISTS'), this.dialogName);
	      } else {
	        BX.DiskFileDialog.closeNotice(this.dialogName);
	      }
	    }
	  }, {
	    key: "showMovedFile",
	    value: function showMovedFile(id, sectionProperties, sectionPath) {
	      if (this.item) {
	        this.item.emit('onMoved', sectionPath);
	      }

	      this.item = null;
	    }
	  }, {
	    key: "onCancel",
	    value: function onCancel() {
	      main_core_events.EventEmitter.unsubscribe(BX.DiskFileDialog, 'loadItems', this.openSection); //EventEmitter.unsubscribe(BX.DiskFileDialog, 'loadItemsDone', this.checkFileName);

	      main_core_events.EventEmitter.unsubscribe(BX.DiskFileDialog, 'selectItem', this.loadFolder);
	      main_core_events.EventEmitter.unsubscribe(BX.DiskFileDialog, 'unSelectItem', this.stopLoadingFolder);
	      this.item = null;

	      if (this.timeout > 0) {
	        clearTimeout(this.timeout);
	      }

	      this.timeout = null;
	    }
	  }], [{
	    key: "subscribe",
	    value: function subscribe() {
	      if (BX.DiskFileDialog.subscribed !== true) {
	        BX.DiskFileDialog.subscribed = true;
	        main_core_events.EventEmitter.subscribe(BX.DiskFileDialog, 'onFileNeedsToMove', function (event) {
	          event.stopImmediatePropagation();
	          FileMover.getInstance().fire(babelHelpers.toConsumableArray(event.getData()).shift());
	        });
	      }
	    }
	  }, {
	    key: "getInstance",
	    value: function getInstance() {
	      if (this.instance === null) {
	        this.instance = new FileMover();
	      }

	      return this.instance;
	    }
	  }]);
	  return FileMover;
	}();

	babelHelpers.defineProperty(FileMover, "subscribed", false);
	babelHelpers.defineProperty(FileMover, "instance", null);

	var _templateObject$4, _templateObject2$1;
	var justCounter$3 = 0;

	function _camelToSNAKE(obj) {
	  var o = {},
	      i,
	      k;

	  for (i in obj) {
	    k = i.replace(/(.)([A-Z])/g, "$1_$2").toUpperCase();
	    o[k] = obj[i];
	    o[i] = obj[i];
	  }

	  return o;
	}

	var FormBrief = /*#__PURE__*/function (_DefaultController) {
	  babelHelpers.inherits(FormBrief, _DefaultController);

	  function FormBrief(_ref) {
	    var _this;

	    var id = _ref.id,
	        fieldName = _ref.fieldName,
	        container = _ref.container,
	        eventObject = _ref.eventObject,
	        input = _ref.input;
	    babelHelpers.classCallCheck(this, FormBrief);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FormBrief).call(this, {
	      container: container,
	      eventObject: eventObject
	    }));
	    _this.id = id;
	    _this.fieldName = fieldName;
	    _this.input = input;

	    if (!_this.input) {
	      return babelHelpers.possibleConstructorReturn(_this);
	    }

	    _this.agent = BX.Uploader.getInstance({
	      id: id,
	      streams: 1,
	      allowUpload: 'A',
	      uploadFormData: 'N',
	      uploadMethod: 'immediate',
	      uploadFileUrl: Options.urlUpload,
	      showImage: false,
	      sortItems: false,
	      dropZone: null,
	      input: _this.input,
	      pasteFileHashInForm: false
	    });
	    _this.onUploadDone = _this.onUploadDone.bind(babelHelpers.assertThisInitialized(_this));
	    _this.onUploadError = _this.onUploadError.bind(babelHelpers.assertThisInitialized(_this));
	    main_core_events.EventEmitter.subscribe(_this.agent, "onFileIsUploaded", function (_ref2) {
	      var _ref2$compatData = babelHelpers.slicedToArray(_ref2.compatData, 3),
	          itemId = _ref2$compatData[0],
	          item = _ref2$compatData[1],
	          params = _ref2$compatData[2];

	      _this.onUploadDone(item, params);
	    });
	    main_core_events.EventEmitter.subscribe(_this.agent, "onFileIsUploadedWithError", function (_ref3) {
	      var _ref3$compatData = babelHelpers.slicedToArray(_ref3.compatData, 3),
	          itemId = _ref3$compatData[0],
	          item = _ref3$compatData[1],
	          params = _ref3$compatData[2];

	      _this.onUploadError(item, params);
	    });
	    _this.fileSelector = new FileSelector(babelHelpers.assertThisInitialized(_this));
	    main_core_events.EventEmitter.subscribe(_this.fileSelector, 'onUploadDone', function (_ref4) {
	      var itemData = _ref4.data.itemData;

	      _this.onSelectionIsDone(itemData);
	    });
	    _this.fileSelectorCloud = new FileSelectorCloud(babelHelpers.assertThisInitialized(_this));
	    main_core_events.EventEmitter.subscribe(_this.fileSelectorCloud, 'onUploadDone', function (_ref5) {
	      var itemData = _ref5.data.itemData;

	      _this.onSelectionIsDone(itemData);
	    });
	    return _this;
	  }

	  babelHelpers.createClass(FormBrief, [{
	    key: "onSelectionIsDone",
	    value: function onSelectionIsDone(item) {
	      var attrs = {
	        id: 'disk-edit-attach' + item['ID'],
	        'bx-agentFileId': item['ID']
	      };
	      if (item["FILE_ID"]) attrs["bx-attach-file-id"] = 'n' + item["FILE_ID"];
	      var node = main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\" name=\"", "\" value=\"", "\">"])), this.fieldName, item['ID']);

	      for (var ii in attrs) {
	        if (attrs.hasOwnProperty(ii)) {
	          node.setAttribute(ii, attrs[ii]);
	        }
	      }

	      this.getContainer().appendChild(node);
	      var res = {
	        element_id: item['ID'],
	        element_name: item['NAME'],
	        element_url: item['PREVIEW_URL'],
	        storage: item['STORAGE']
	      };
	      main_core_events.EventEmitter.emit(this.getEventObject(), 'OnFileUploadSuccess', new main_core_events.BaseEvent({
	        compatData: [res, {}, null, {
	          id: justCounter$3++,
	          name: item['NAME'],
	          size: item['SIZE'],
	          sizeInt: item['SIZE_INT']
	        }]
	      }));
	    }
	  }, {
	    key: "onUploadDone",
	    value: function onUploadDone(item, result) {
	      if (result["file"] && result["file"]["attachId"] !== result["file"]["id"]) {
	        result["file"]["id"] = result["file"]["attachId"];
	        delete result["file"]["attachId"];
	      }

	      var file = _camelToSNAKE(result["file"]);

	      var attrs = {
	        id: 'disk-edit-attach' + file.id,
	        'bx-agentFileId': item.id
	      };
	      if (file["XML_ID"]) attrs["bx-attach-xml-id"] = file["XML_ID"];
	      if (file["FILE_ID"]) attrs["bx-attach-file-id"] = 'n' + file["FILE_ID"];
	      if (file['FILE_TYPE']) attrs["bx-attach-file-type"] = file["FILE_TYPE"];
	      file.element_id = file.id;
	      var node = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\" name=\"", "\" value=\"", "\">"])), this.fieldName, file.id);

	      for (var ii in attrs) {
	        if (attrs.hasOwnProperty(ii)) {
	          node.setAttribute(ii, attrs[ii]);
	        }
	      }

	      this.getContainer().appendChild(node);
	      this.onFileIs(item, file);
	    }
	  }, {
	    key: "onFileIs",
	    value: function onFileIs(item, file) {
	      var res = {
	        element_id: file.element_id,
	        element_name: file.element_name || item.name,
	        element_url: file.element_name || file.previewUrl || file.preview_url,
	        storage: 'disk'
	      };
	      main_core_events.EventEmitter.emit(this.getEventObject(), 'OnFileUploadSuccess', new main_core_events.BaseEvent({
	        compatData: [res, this, item.file, item]
	      }));
	    }
	  }, {
	    key: "onUploadError",
	    value: function onUploadError(item, params) {
	      BX.onCustomEvent(this.getEventObject(), 'OnFileUploadFailed', [this, item.file, item]);
	    }
	  }]);
	  return FormBrief;
	}(DefaultController);

	var Form = /*#__PURE__*/function (_DefaultController) {
	  babelHelpers.inherits(Form, _DefaultController);

	  function Form(_ref, values) {
	    var _this;

	    var id = _ref.id,
	        fieldName = _ref.fieldName,
	        container = _ref.container,
	        eventObject = _ref.eventObject,
	        input = _ref.input,
	        parserParams = _ref.parserParams;
	    babelHelpers.classCallCheck(this, Form);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Form).call(this, {
	      container: container,
	      eventObject: eventObject
	    }));
	    _this.id = id;
	    _this.fieldName = fieldName;
	    _this.input = input;

	    if (parserParams) {
	      _this.getFileController().getParser().setParams(parserParams);
	    }

	    _this.init();

	    if (values.length > 0) {
	      main_core_events.EventEmitter.emit(_this.getEventObject(), 'onShowControllers', 'show');

	      _this.show(values);
	    }

	    return _this;
	  }

	  babelHelpers.createClass(Form, [{
	    key: "init",
	    value: function init() {
	      var _this2 = this;

	      if (this.input) {
	        this.getFilesUploader();
	      }

	      this.initDocumentController();
	      this.settingsController = new SettingsController(this, {});
	      this.panelController = new PanelController(this);
	      this.fileSelector = new FileSelector(this);
	      main_core_events.EventEmitter.subscribe(this.fileSelector, 'onUploadDone', function (_ref2) {
	        var itemData = _ref2.data.itemData;

	        _this2.getFileController().add(itemData);
	      });
	      this.fileSelectorCloud = new FileSelectorCloud(this);
	      main_core_events.EventEmitter.subscribe(this.fileSelectorCloud, 'onUploadDone', function (_ref3) {
	        var _ref3$data = _ref3.data,
	            itemData = _ref3$data.itemData,
	            itemContainer = _ref3$data.itemContainer;

	        _this2.getFileController().add(itemData, itemContainer);
	      });

	      var switcher = function switcher(event) {
	        var status = main_core.Type.isArray(event.getData()) ? event.getData().shift() : event.getData();

	        if (status === 'show') {
	          _this2.show();
	        } else {
	          _this2.hide();
	        }
	      }; //region compatibility


	      main_core_events.EventEmitter.subscribe(this.getEventObject(), 'onCollectControllers', function (event) {
	        event.data[_this2.fieldName] = {
	          storage: 'disk',
	          tag: _this2.getFileController().isPluggedIn() ? _this2.getFileController().getParser().tag : null,
	          values: [],
	          handler: {
	            selectFile: function selectFile(tab, path, selected) {
	              _this2.fileSelector.selectFile(tab, path, selected);
	            }
	          }
	        };
	        Array.from(_this2.getContainer().querySelectorAll("input[type=\"hidden\"][name=\"".concat(_this2.fieldName, "\"]"))).forEach(function (nodeItem) {
	          event.data[_this2.fieldName].values.push(nodeItem.value);
	        });
	      }); //endregion

	      main_core_events.EventEmitter.subscribe(this.getEventObject(), 'onShowControllers', switcher);
	      main_core_events.EventEmitter.subscribe(this.getEventObject(), 'DiskLoadFormController', switcher); // (new Ears({
	      // 	container: this.getContainer().querySelector('[data-bx-role="control-panel-main-actions"]'),
	      // 	noScrollbar: false,
	      // 	className: 'disk-documents-ears'
	      // })).init();

	      main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'disk.uf.file:create:thumb:upload', function (_ref4) {
	        var data = _ref4.data;

	        _this2.show();

	        _this2.getFilesUploader().addTestThumb(data);
	      });
	      main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'disk.uf.file:create:thumb:error', function () {
	        _this2.show();

	        _this2.getFilesUploader().addTestErrorThumb();
	      });
	    }
	  }, {
	    key: "initDocumentController",
	    value: function initDocumentController() {
	      var _this3 = this;

	      this.documentController = new DocumentController({
	        container: this.getContainer(),
	        eventObject: this.getEventObject()
	      });
	      main_core_events.EventEmitter.subscribe(this.documentController, 'onFileIsCreated', function (_ref5) {
	        var itemData = _ref5.data.itemData;
	        main_core_events.EventEmitter.emit(_this3.getEventObject(), 'onShowControllers', 'show');

	        _this3.getFileController().add(itemData);
	      });

	      if (!this.documentController.isRelevant()) {
	        return;
	      } else if (!this.isPluggedIn()) {
	        this.documentController.show();
	        return;
	      }

	      if (this.eventObject.dataset.bxDiskDocumentButton !== 'added') {
	        this.eventObject.dataset.bxDiskDocumentButton = 'added';
	        var node = document.createElement('DIV');
	        node.addEventListener('click', function () {
	          var container = node.closest('[data-id="disk-document"]');

	          if (container && container.hasAttribute('data-bx-button-status')) {
	            main_core_events.EventEmitter.emit(_this3.getEventObject(), 'onHideDocuments');
	          } else {
	            main_core_events.EventEmitter.emit(_this3.getEventObject(), 'onShowControllers', 'hide');
	            main_core_events.EventEmitter.emit(_this3.getEventObject(), 'onShowDocuments', 'show');
	          }
	        });
	        node.innerHTML = '<i></i>' + main_core.Loc.getMessage('WDUF_CREATE_DOCUMENT');
	        main_core_events.EventEmitter.emit(this.eventObject, 'OnAddButton', [{
	          BODY: node,
	          ID: 'disk-document'
	        }, 'file']);
	        main_core_events.EventEmitter.subscribe(this.getEventObject(), 'onShowDocuments', function () {
	          var container = node.closest('[data-id="disk-document"]');

	          if (container) {
	            container.setAttribute('data-bx-button-status', 'active');
	          }
	        });
	        main_core_events.EventEmitter.subscribe(this.getEventObject(), 'onHideDocuments', function () {
	          var container = node.closest('[data-id="disk-document"]');

	          if (container) {
	            container.removeAttribute('data-bx-button-status');
	          }
	        });
	      }

	      main_core_events.EventEmitter.subscribe(this.getEventObject(), 'onShowDocuments', function () {
	        babelHelpers.get(babelHelpers.getPrototypeOf(Form.prototype), "show", _this3).call(_this3);

	        _this3.documentController.show();
	      });
	      main_core_events.EventEmitter.subscribe(this.getEventObject(), 'onHideDocuments', function () {
	        babelHelpers.get(babelHelpers.getPrototypeOf(Form.prototype), "hide", _this3).call(_this3);

	        _this3.documentController.hide();
	      });

	      var switcher = function switcher(_ref6) {
	        var data = _ref6.data;

	        if (data === 'show') {
	          main_core_events.EventEmitter.emit(_this3.getEventObject(), 'onHideDocuments');
	        }
	      };

	      main_core_events.EventEmitter.subscribe(this.getEventObject(), 'DiskLoadFormController', switcher);
	      main_core_events.EventEmitter.subscribe(this.getEventObject(), 'onShowControllers', switcher);
	    }
	  }, {
	    key: "getFileController",
	    value: function getFileController() {
	      if (!this.filesController) {
	        this.filesController = new FileController({
	          id: this.id,
	          fieldName: this.fieldName,
	          container: this.container,
	          eventObject: this.eventObject
	        });
	      }

	      return this.filesController;
	    }
	  }, {
	    key: "getFilesUploader",
	    value: function getFilesUploader() {
	      var _this4 = this;

	      if (!this.filesUploader) {
	        this.filesUploader = new FileUploader({
	          id: this.id,
	          container: this.container.querySelector('[data-bx-role="placeholder"]'),
	          dropZone: this.container,
	          input: this.input
	        }); //Video

	        main_core_events.EventEmitter.subscribe(this.getEventObject(), 'OnVideoHasCaught', function (event) {
	          var fileToUpload = event.getData();

	          var onSuccess = function onSuccess(_ref7) {
	            var _ref7$data = _ref7.data,
	                itemData = _ref7$data.itemData,
	                itemContainer = _ref7$data.itemContainer,
	                blob = _ref7$data.blob;

	            if (fileToUpload === blob) {
	              main_core_events.EventEmitter.unsubscribe(_this4.filesUploader, 'onUploadDone', onSuccess);

	              _this4.getFileController().getParser().insertFile(itemData.ID);
	            }
	          };

	          main_core_events.EventEmitter.subscribe(_this4.filesUploader, 'onUploadDone', onSuccess);

	          _this4.filesUploader.upload([fileToUpload]);

	          event.stopImmediatePropagation();
	        }); //Image

	        main_core_events.EventEmitter.subscribe(this.getEventObject(), 'OnImageHasCaught', function (event) {
	          var fileToUpload = event.getData();
	          event.stopImmediatePropagation();
	          return new Promise(function (resolve, reject) {
	            var onSuccess = function onSuccess(_ref8) {
	              var _ref8$data = _ref8.data,
	                  itemData = _ref8$data.itemData,
	                  itemContainer = _ref8$data.itemContainer,
	                  blob = _ref8$data.blob;

	              if (fileToUpload === blob) {
	                main_core_events.EventEmitter.unsubscribe(_this4.filesUploader, 'onUploadDone', onSuccess);
	                main_core_events.EventEmitter.unsubscribe(_this4.filesUploader, 'onUploadDone', onFailed);
	                resolve({
	                  image: {
	                    src: itemData.PREVIEW_URL
	                  },
	                  html: _this4.getFileController().getParser().getItemHTML(itemData.ID)
	                });
	              }
	            };

	            main_core_events.EventEmitter.subscribe(_this4.filesUploader, 'onUploadDone', onSuccess);

	            var onFailed = function onFailed(_ref9) {
	              var blob = _ref9.data.blob;

	              if (fileToUpload === blob) {
	                main_core_events.EventEmitter.unsubscribe(_this4.filesUploader, 'onUploadDone', onSuccess);
	                main_core_events.EventEmitter.unsubscribe(_this4.filesUploader, 'onUploadDone', onFailed);
	                reject();
	              }
	            };

	            main_core_events.EventEmitter.subscribe(_this4.filesUploader, 'onUploadDone', onFailed);

	            _this4.filesUploader.upload([fileToUpload]);
	          });
	        });
	        main_core_events.EventEmitter.subscribe(this.getEventObject(), 'onFilesHaveCaught', function (event) {
	          event.stopImmediatePropagation();

	          _this4.filesUploader.upload(babelHelpers.toConsumableArray(event.getData()));
	        });
	        main_core_events.EventEmitter.subscribe(this.filesUploader, 'onUploadDone', function (_ref10) {
	          var _ref10$data = _ref10.data,
	              itemData = _ref10$data.itemData,
	              itemContainer = _ref10$data.itemContainer;

	          _this4.getFileController().add(itemData, itemContainer);
	        });
	        main_core_events.EventEmitter.subscribe(this.filesUploader, 'onUploadIsStart', function () {
	          main_core_events.EventEmitter.emit(_this4.getEventObject(), 'onBusy', _this4);
	        });
	        main_core_events.EventEmitter.subscribe(this.filesUploader, 'onUploadIsDone', function () {
	          main_core_events.EventEmitter.emit(_this4.getEventObject(), 'onReady', _this4);
	        });
	      }

	      return this.filesUploader;
	    }
	  }, {
	    key: "getPanelController",
	    value: function getPanelController() {
	      return this.panelController;
	    }
	  }, {
	    key: "show",
	    value: function show(values) {
	      var _this5 = this;

	      var switcher = this.getContainer().parentNode.querySelector('#' + this.getContainer().id + '-switcher');

	      if (switcher) {
	        switcher.parentNode.removeChild(switcher);
	      }

	      if (this.getFileController().isPluggedIn()) {
	        this.getContainer().setAttribute('data-bx-plugged-in', 'Y');
	      }

	      babelHelpers.get(babelHelpers.getPrototypeOf(Form.prototype), "show", this).call(this);
	      this.showInitialLoader();
	      this.getFileController().show();
	      this.getPanelController().show();

	      if (values) {
	        this.getFileController().set(values).then(function () {
	          _this5.hideInitialLoader();
	        });
	      } else {
	        this.hideInitialLoader();
	      }
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Form.prototype), "hide", this).call(this);
	      this.getFileController().hide();
	      this.getPanelController().hide();
	    }
	  }, {
	    key: "showInitialLoader",
	    value: function showInitialLoader() {}
	  }, {
	    key: "hideInitialLoader",
	    value: function hideInitialLoader() {}
	  }], [{
	    key: "getInstance",
	    value: function getInstance(data, values) {
	      if (!this.repo[data['id']]) {
	        this.repo[data['id']] = new Form(data, values);
	      }

	      return this.repo[data['id']];
	    }
	  }, {
	    key: "getBriefInstance",
	    value: function getBriefInstance(data) {
	      if (!this.repo[data['id']]) {
	        this.repo[data['id']] = new FormBrief(data, []);
	      }

	      return this.repo[data['id']];
	    }
	  }]);
	  return Form;
	}(DefaultController);

	babelHelpers.defineProperty(Form, "repo", {});
	setTimeout(function () {
	  if (BX.DiskFileDialog) {
	    FileMover.subscribe();
	  }
	}, 1000);

	var add = function add(params) {
	  var container = BX('diskuf-selectdialog-' + params['UID']);

	  if (container && BX.isNodeInDom(container)) {
	    var eventObject = container.parentNode;

	    if (!container.hasAttribute("bx-disk-load-is-bound")) {
	      container.setAttribute("bx-disk-load-is-bound", "Y");
	      BX.addCustomEvent(eventObject, "DiskLoadFormController", function (status) {
	        try {
	          BX.Disk.UF.Options.set({
	            urlUpload: params.urlUpload
	          });
	          return BX.Disk.UF.Form.getBriefInstance({
	            container: container,
	            eventObject: container.parentNode,
	            id: params.UID,
	            fieldName: params.controlName,
	            input: BX.findChild(container, {
	              className: 'diskuf-fileUploader'
	            }, true)
	          });
	        } catch (e) {
	          console.log('Error with compatibility', e);
	        }
	      });
	    }

	    if (!!params['values'] && params['values'].length > 0 && !params['hideSelectDialog']) BX.onCustomEvent(container.parentNode, 'DiskLoadFormController', ['show']);
	  }
	};

	exports.Form = Form;
	exports.Options = Options;
	exports.add = add;

}((this.BX.Disk.UF = this.BX.Disk.UF || {}),BX,BX.Main,BX.UI,BX.UI,BX,BX.Event));



;(function(window){

	if (window.BX.Disk && window.BX.Disk.UFShowController)
		return;

	var BX = window.BX;
	var diskufMenuNumber = 0;
	var showRepo = {};

	var getBreadcrumbsByAttachedObject = function(attachedId) {
		return BX.Disk.ajaxPromise({
			url: BX.Disk.addToLinkParam('/bitrix/tools/disk/uf.php', 'action', 'getBreadcrumbs'),
			method: 'POST',
			dataType: 'json',
			data: {
				attachedId: attachedId
			}
		});
	};

	var __preview = function(img)
	{
		if (!BX(img) || img.hasAttribute("bx-is-bound"))
			return;
		img.setAttribute("bx-is-bound", "Y");

		this.img = img;
		this.node = img.parentNode.parentNode.parentNode;

		BX.unbindAll(img);
		BX.unbindAll(this.node);

		BX.show(this.node);
		BX.remove(this.node.nextSibling);
		this.id = 'wufdp_' + Math.random();
		// BX.bind(this.node, "mouseover", BX.delegate(function(){this.turnOn();}, this));
		// BX.bind(this.node, "mouseout", BX.delegate(function(){this.turnOff();}, this));
	};
	__preview.prototype =
	{
		turnOn : function()
		{
			this.timeout = setTimeout(BX.delegate(function(){this.show();}, this), 500);
		},
		turnOff : function()
		{
			clearTimeout(this.timeout);
			this.timeout = null;
			this.hide();
		},
		show : function()
		{
			if (this.popup != null)
				this.popup.close();
			if (this.popup == null)
			{
				var props = {
						width : this.img.naturalWidth,
						height : this.img.naturalHeight
					};
				if (BX["UploaderUtils"])
				{
					var res2 = BX.UploaderUtils.scaleImage(props, {
							width : parseInt(BX.message("DISK_THUMB_WIDTH")),
							height : parseInt(BX.message("DISK_THUMB_HEIGHT"))
						});
					props = res2.destin;
				}
				this.popup = new BX.PopupWindow('bx-wufd-preview-img-' + this.id, this.img.parentNode,
					{
						lightShadow : true,
						offsetTop: -7,
						offsetLeft: (51-28)/2 + 14,
						autoHide: true,
						closeByEsc: true,
						bindOptions: {position: "top"},
						events : {
							onPopupClose : function() { this.destroy() },
							onPopupDestroy : BX.proxy(function() { this.popup = null; }, this)
						},
						content : BX.create(
							"DIV",
							{
								props: props,
								children : [
									BX.create(
										"IMG",
										{
											props : props,
											attrs: {
												src: this.img.src
											}
										}
									)
								]
							}
						)
					}
				);
				this.popup.show();
			}
			this.popup.setAngle({position:'bottom'});
			this.popup.bindOptions.forceBindPosition = true;
			this.popup.adjustPosition();
			this.popup.bindOptions.forceBindPosition = false;
		},
		hide : function()
		{
			if (this.popup != null)
				this.popup.close();
		}
	};
	BX.addCustomEvent('onDiskPreviewIsReady', function(img) { new __preview(img); });

BX.Disk.UF.runImport = function(params)
{
	BX.Disk.showActionModal({text: BX.message('DISK_UF_FILE_STATUS_PROCESS_LOADING'), showLoaderIcon: true, autoHide: false});

	BX.Disk.ExternalLoader.reloadLoadAttachedObject({
		attachedObject: {
			id: params.id,
			name: params.name,
			service: params.service
		},

		onFinish: BX.delegate(function(newData){
			if(newData.hasOwnProperty('hasNewVersion') && !newData.hasNewVersion)
			{
				BX.Disk.showActionModal({text: BX.message('DISK_UF_FILE_STATUS_HAS_LAST_VERSION'), showSuccessIcon: true, autoHide: true});
			}
			else if(newData.status === 'success')
			{
				BX.Disk.showActionModal({text: BX.message('DISK_UF_FILE_STATUS_SUCCESS_LOADING'), showSuccessIcon: true, autoHide: true});
			}
			else
			{
				BX.Disk.showActionModal({text: BX.message('DISK_UF_FILE_STATUS_FAIL_LOADING'), autoHide: true});
			}
		}, this),
		onProgress: BX.delegate(function(progress){

		}, this)
	}).start();

};

BX.Disk.UF.disableAutoCommentToAttachedObject = function(params)
{
	var attachedId = params.attachedId;
	BX.Disk.ajax({
		method: 'POST',
		dataType: 'json',
		url: BX.Disk.addToLinkParam('/bitrix/tools/disk/uf.php', 'action', 'disableAutoCommentToAttachedObject'),
		data: {
			attachedId: attachedId
		},
		onsuccess: BX.delegate(function (response) {
			//BX.Disk.showModalWithStatusAction(response);
		}, this)
	});

};
BX.Disk.UF.enableAutoCommentToAttachedObject = function(params)
{
	var attachedId = params.attachedId;
	BX.Disk.ajax({
		method: 'POST',
		dataType: 'json',
		url: BX.Disk.addToLinkParam('/bitrix/tools/disk/uf.php', 'action', 'enableAutoCommentToAttachedObject'),
		data: {
			attachedId: attachedId
		},
		onsuccess: BX.delegate(function (response) {
			//BX.Disk.showModalWithStatusAction(response);
		}, this)
	});

};

BX.Disk.UF.showTransformationUpgradePopup = function(event)
{
	B24.licenseInfoPopup.show(
		'disk_transformation_video_limit',
		BX.message('DISK_UF_CONTROLLER_TRANSFORMATION_UPGRADE_POPUP_TITLE'),
		BX.message('DISK_UF_CONTROLLER_TRANSFORMATION_UPGRADE_POPUP_CONTENT'),
		false
	);
};

BX.Disk.UFShowController = function(params) {
	if (!BX.type.isPlainObject(params))
	{
		params = {};
	}

	this.entityType = (BX.type.isNotEmptyString(params.entityType) ? params.entityType : '');
	this.entityId = (parseInt(params.entityId) > 0 ? params.entityId : '');
	this.signedParameters = (BX.type.isNotEmptyString(params.signedParameters) ? params.signedParameters : '');
	this.loader = null;

	this.container = (
		BX.type.isNotEmptyString(params.nodeId)
			? document.getElementById(params.nodeId)
			: null
	);

	if (this.container)
	{
		var
			toggleViewlink = this.container.querySelector('.disk-uf-file-switch-control');

		if (toggleViewlink)
		{
			BX.Event.bind(toggleViewlink, 'click', BX.Disk.UFShowController.onToggleView);
		}
	}

	if (BX.type.isNotEmptyString(params.nodeId))
	{
		showRepo[params.nodeId] = this;
	}
};

BX.Disk.UFShowController.getInstance = function(nodeId)
{
	return (
		BX.type.isNotEmptyString(nodeId)
		&& showRepo[nodeId]
			? showRepo[nodeId]
			: null
	);
};

BX.Disk.UFShowController.onToggleView = function(event)
{
	var
		container = event.currentTarget.closest('.diskuf-files-toggle-container'),
		viewType = event.currentTarget.getAttribute('data-bx-view-type');

	if (
		!BX.type.isDomNode(container)
		|| !BX.type.isNotEmptyString(container.id)
	)
	{
		return;
	}

	var
		controller = BX.Disk.UFShowController.getInstance(container.id);

	if (controller)
	{
		controller.toggleViewType({
			viewType: viewType
		});
	}

	event.preventDefault();
};

BX.Disk.UFShowController.prototype.toggleViewType = function(params)
{
	this.showToggleViewLoader();

	BX.ajax.runComponentAction('bitrix:disk.uf.file', 'toggleViewType', {
		mode: 'class',
		signedParameters: this.signedParameters,
		data: {
			params: {
				viewType: params.viewType
			}
		}
	}).then(function(response) {
		this.hideToggleViewLoader();
		BX.clean(this.container);
		BX.html(this.container, response.data.html);

	}.bind(this), function(response) {

		this.hideToggleViewLoader();

	});
};

BX.Disk.UFShowController.prototype.showToggleViewLoader = function(params)
{
	this.container.classList.add('diskuf-files-toggle-container-active');

	this.loader = new BX.Loader({
		target: this.container
	});
	this.loader.show();
};

BX.Disk.UFShowController.prototype.hideToggleViewLoader = function(params)
{
	this.container.classList.remove('diskuf-files-toggle-container-active');

	if (this.loader)
	{
		this.loader.destroy();
	}
};

	window.DiskOpenMenuCreateService = function(targetElement)
	{
		var items = [
			(BX.Disk.UF.getDocumentHandler('onlyoffice')? {
				text: BX.Disk.UF.getDocumentHandler('onlyoffice').name,
				className: "bx-viewer-popup-item item-b24-docs",
				onclick: function (event, popupItem)
				{
					popupItem.getMenuWindow().close();

					BX.Disk.saveDocumentService('onlyoffice');

					BX.adjust(targetElement, {text: BX.Disk.UF.getDocumentHandler('onlyoffice').name});
				}
			}: null),
			(BX.Disk.Document.Local.Instance.isEnabled()? {
				text: BX.message('DISK_FOLDER_TOOLBAR_LABEL_LOCAL_BDISK_EDIT'),
				className: "bx-viewer-popup-item item-b24",
				onclick: function (event, popupItem)
				{
					popupItem.getMenuWindow().close();

					BX.Disk.saveDocumentService('l');

					BX.adjust(targetElement, {text: BX.message('DISK_FOLDER_TOOLBAR_LABEL_LOCAL_BDISK_EDIT')});
				}
			}: null),
			{
				text: BX.Disk.UF.getDocumentHandler('gdrive').name,
				className: "bx-viewer-popup-item item-gdocs",
				onclick: function (event, popupItem)
				{
					popupItem.getMenuWindow().close();

					BX.Disk.saveDocumentService('gdrive');

					BX.adjust(targetElement, {text: BX.Disk.UF.getDocumentHandler('gdrive').name});
				}
			},
			{
				text: BX.Disk.UF.getDocumentHandler('office365').name,
				className: "bx-viewer-popup-item item-office365",
				onclick: function (event, popupItem)
				{
					popupItem.getMenuWindow().close();

					BX.Disk.saveDocumentService('office365');

					BX.adjust(targetElement, {text: BX.Disk.UF.getDocumentHandler('office365').name});
				}
			},
			{
				text: BX.Disk.UF.getDocumentHandler('onedrive').name,
				className: "bx-viewer-popup-item item-office",
				onclick: function (event, popupItem)
				{
					popupItem.getMenuWindow().close();

					BX.Disk.saveDocumentService('onedrive');

					BX.adjust(targetElement, {text: BX.Disk.UF.getDocumentHandler('onedrive').name});
				}
			}
		];

		BX.PopupMenu.show('disk_open_menu_with_services', BX(targetElement), items,
			{
				offsetTop: 0,
				offsetLeft: 25,
				angle: {
					position: 'top',
					offset: 45
				},
				autoHide: true,
				zIndex: 10000,
				overlay: {
					opacity: 0.01
				},
				events : {}
			}
		);
	};

	window.DiskOpenMenuImportService = function(targetElement, listCloudStorages)
	{
		var list = [];
		for(var i in listCloudStorages)
		{
			if(!listCloudStorages.hasOwnProperty(i))
				continue;

			list.push({
				text: listCloudStorages[i].name,
				code: listCloudStorages[i].id,
				href: "#",
				onclick: function (e, item)
				{
					var helpItem = item.layout.item;
					BX.addClass(helpItem, 'diskuf-selector-link-cloud');
					helpItem.setAttribute('data-bx-doc-handler', item.code);
					BX.onCustomEvent('onManualChooseCloudImport', [{
						target: helpItem
					}]);
					BX.removeClass(helpItem, 'diskuf-selector-link-cloud');
					helpItem.removeAttribute('data-bx-doc-handler');

					BX.PopupMenu.destroy('disk_open_menu_with_import_services');

					return BX.PreventDefault(e);
				}
			});
		}

		var obElementViewer = new BX.CViewer({});
		obElementViewer.openMenu('disk_open_menu_with_import_services', BX(targetElement), list, {
			offsetTop: 0,
			offsetLeft: 25
		});

		return BX.PreventDefault();
	};

	window.DiskActionFileMenu = function(id, bindElement, buttons)
	{
		diskufMenuNumber++;
		BX.PopupMenu.show('bx-viewer-wd-popup' + diskufMenuNumber + '_' + id, BX(bindElement), buttons,
			{
				angle: {
					position: 'top',
					offset: 25
				},
				autoHide: true
			}
		);

		return false;
	};
	/**
	 * Forward click event from inline element to main element (with additional properties)
	 * @param element
	 * @param realElementId main element (in attached block)
	 * @returns {boolean}
	 * @constructor
	 */
	window.WDInlineElementClickDispatcher = function(element, realElementId)
	{
		var realElement = BX(realElementId);
		if(realElement)
		{
			BX.fireEvent(realElement, 'click');
		}
		return false;
	};
})(window);

//# sourceMappingURL=script.js.map