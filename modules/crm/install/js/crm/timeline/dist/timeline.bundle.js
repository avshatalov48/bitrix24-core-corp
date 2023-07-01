this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_datetime,crm_timeline_tools,crm_timeline_item,main_core) {
	'use strict';

	/** @memberof BX.Crm.Timeline.Types */
	const Item = {
	  undefined: 0,
	  activity: 1,
	  creation: 2,
	  modification: 3,
	  link: 4,
	  unlink: 5,
	  mark: 6,
	  comment: 7,
	  wait: 8,
	  bizproc: 9,
	  conversion: 10,
	  sender: 11,
	  document: 12,
	  restoration: 13,
	  order: 14,
	  orderCheck: 15,
	  scoring: 16,
	  externalNotification: 17,
	  finalSummary: 18,
	  delivery: 19,
	  finalSummaryDocuments: 20,
	  storeDocument: 21,
	  productCompilation: 22,
	  signDocument: 23
	};

	/** @memberof BX.Crm.Timeline.Types */
	const Mark = {
	  undefined: 0,
	  waiting: 1,
	  success: 2,
	  renew: 3,
	  ignored: 4,
	  failed: 5
	};

	/** @memberof BX.Crm.Timeline.Types */

	/** @memberof BX.Crm.Timeline.Types */
	const Order = {
	  encourageBuyProducts: 100
	};

	/** @memberof BX.Crm.Timeline.Types */
	const EditorMode = {
	  view: 1,
	  edit: 2
	};

	var types = /*#__PURE__*/Object.freeze({
		Item: Item,
		Mark: Mark,
		Order: Order,
		EditorMode: EditorMode
	});

	/** @memberof BX.Crm.Timeline */
	let CompatibleItem = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(CompatibleItem, _Item);
	  function CompatibleItem() {
	    var _this;
	    babelHelpers.classCallCheck(this, CompatibleItem);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CompatibleItem).call(this));
	    _this._id = "";
	    _this._settings = {};
	    _this._data = {};
	    _this._container = null;
	    _this._typeCategoryId = null;
	    _this._associatedEntityData = null;
	    _this._associatedEntityTypeId = null;
	    _this._associatedEntityId = null;
	    _this._isContextMenuShown = false;
	    _this._contextMenuButton = null;
	    _this._activityEditor = null;
	    _this._actions = [];
	    _this._actionContainer = null;
	    _this._existedStreamItemDeadLine = null;
	    return _this;
	  }
	  babelHelpers.createClass(CompatibleItem, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._setId(id);
	      this._settings = settings ? settings : {};
	      this._container = this.getSetting("container");
	      if (!BX.type.isPlainObject(settings['data'])) {
	        throw "Item. A required parameter 'data' is missing.";
	      }
	      this._data = settings['data'];
	      this._activityEditor = this.getSetting("activityEditor");
	      this.doInitialize();
	    }
	  }, {
	    key: "doInitialize",
	    value: function doInitialize() {}
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this._id;
	    }
	  }, {
	    key: "getSetting",
	    value: function getSetting(name, defaultval) {
	      return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	    }
	  }, {
	    key: "getData",
	    value: function getData() {
	      return this._data;
	    }
	  }, {
	    key: "setData",
	    value: function setData(data) {
	      if (BX.type.isPlainObject(data)) {
	        this._data = data;
	        this.clearCachedData();
	      }
	    }
	  }, {
	    key: "getSort",
	    value: function getSort() {
	      var _this$_data$sort;
	      return (_this$_data$sort = this._data['sort']) !== null && _this$_data$sort !== void 0 ? _this$_data$sort : [];
	    }
	  }, {
	    key: "getAssociatedEntityData",
	    value: function getAssociatedEntityData() {
	      if (this._associatedEntityData === null) {
	        this._associatedEntityData = BX.type.isPlainObject(this._data["ASSOCIATED_ENTITY"]) ? this._data["ASSOCIATED_ENTITY"] : {};
	      }
	      return this._associatedEntityData;
	    }
	  }, {
	    key: "getAssociatedEntityTypeId",
	    value: function getAssociatedEntityTypeId() {
	      if (this._associatedEntityTypeId === null) {
	        this._associatedEntityTypeId = BX.prop.getInteger(this._data, "ASSOCIATED_ENTITY_TYPE_ID", 0);
	      }
	      return this._associatedEntityTypeId;
	    }
	  }, {
	    key: "getAssociatedEntityId",
	    value: function getAssociatedEntityId() {
	      if (this._associatedEntityId === null) {
	        this._associatedEntityId = BX.prop.getInteger(this._data, "ASSOCIATED_ENTITY_ID", 0);
	      }
	      return this._associatedEntityId;
	    }
	  }, {
	    key: "setAssociatedEntityData",
	    value: function setAssociatedEntityData(associatedEntityData) {
	      if (!BX.type.isPlainObject(associatedEntityData)) {
	        associatedEntityData = {};
	      }
	      const data = this._data;
	      data.ASSOCIATED_ENTITY = associatedEntityData;
	      this.setData(data);
	    }
	  }, {
	    key: "hasPermissions",
	    value: function hasPermissions() {
	      const entityData = this.getAssociatedEntityData();
	      return BX.type.isPlainObject(entityData["PERMISSIONS"]);
	    }
	  }, {
	    key: "getPermissions",
	    value: function getPermissions() {
	      return BX.prop.getObject(this.getAssociatedEntityData(), "PERMISSIONS", {});
	    }
	  }, {
	    key: "setPermissions",
	    value: function setPermissions(permissions) {
	      const data = this._data;
	      if (!main_core.Type.isPlainObject(data.ASSOCIATED_ENTITY)) {
	        data.ASSOCIATED_ENTITY = {};
	      }
	      data.ASSOCIATED_ENTITY.PERMISSIONS = permissions;
	      this.setData(data);
	    }
	  }, {
	    key: "getTextDataParam",
	    value: function getTextDataParam(name) {
	      return BX.prop.getString(this._data, name, "");
	    }
	  }, {
	    key: "getObjectDataParam",
	    value: function getObjectDataParam(name) {
	      return BX.prop.getObject(this._data, name, {});
	    }
	  }, {
	    key: "getArrayDataParam",
	    value: function getArrayDataParam(name) {
	      return BX.prop.getArray(this._data, name, []);
	    }
	  }, {
	    key: "getTypeId",
	    value: function getTypeId() {
	      return Item.undefined;
	    }
	  }, {
	    key: "getTypeCategoryId",
	    value: function getTypeCategoryId() {
	      if (this._typeCategoryId === null) {
	        this._typeCategoryId = BX.prop.getInteger(this._data, "TYPE_CATEGORY_ID", 0);
	      }
	      return this._typeCategoryId;
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      return this._container;
	    }
	  }, {
	    key: "setContainer",
	    value: function setContainer(container) {
	      this._container = BX.type.isElementNode(container) ? container : null;
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      if (!BX.type.isElementNode(this._container)) {
	        throw "Item. Container is not assigned.";
	      }
	      this.prepareLayout(options);
	      //region Actions
	      /**/
	      this.prepareActions();
	      const actionQty = this._actions.length;
	      for (let i = 0; i < actionQty; i++) {
	        this._actions[i].layout();
	      }
	      this.showActions(actionQty > 0);
	      /**/
	      //endregion
	    }
	  }, {
	    key: "prepareLayout",
	    value: function prepareLayout(options) {}
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {}
	  }, {
	    key: "showActions",
	    value: function showActions(show) {}
	  }, {
	    key: "clearCachedData",
	    value: function clearCachedData() {
	      this._typeCategoryId = null;
	      this._associatedEntityData = null;
	      this._associatedEntityTypeId = null;
	      this._associatedEntityId = null;
	    }
	  }, {
	    key: "isDone",
	    value: function isDone() {
	      return false;
	    }
	  }, {
	    key: "markAsDone",
	    value: function markAsDone(isDone) {}
	  }, {
	    key: "view",
	    value: function view() {}
	  }, {
	    key: "edit",
	    value: function edit() {}
	  }, {
	    key: "fasten",
	    value: function fasten() {}
	  }, {
	    key: "unfasten",
	    value: function unfasten() {}
	  }, {
	    key: "remove",
	    value: function remove() {}
	  }, {
	    key: "cutOffText",
	    value: function cutOffText(text, length) {
	      if (!BX.type.isNumber(length)) {
	        length = 0;
	      }
	      if (length <= 0 || text.length <= length) {
	        return text;
	      }
	      let offset = length - 1;
	      const whilespaceOffset = text.substring(offset).search(/\s/i);
	      if (whilespaceOffset > 0) {
	        offset += whilespaceOffset;
	      }
	      return text.substring(0, offset);
	    }
	  }, {
	    key: "prepareMultilineCutOffElements",
	    value: function prepareMultilineCutOffElements(text, length, clickHandler) {
	      if (!BX.type.isNumber(length)) {
	        length = 0;
	      }
	      if (length <= 0 || text.length <= length) {
	        return [BX.util.htmlspecialchars(text).replace(/(?:\r\n|\r|\n)/g, '<br>')];
	      }
	      let offset = length - 1;
	      const whilespaceOffset = text.substring(offset).search(/\s/i);
	      if (whilespaceOffset > 0) {
	        offset += whilespaceOffset;
	      }
	      return [BX.util.htmlspecialchars(text.substring(0, offset)).replace(/(?:\r\n|\r|\n)/g, '<br>') + "&hellip;&nbsp;", BX.create("A", {
	        attrs: {
	          className: "crm-entity-stream-content-letter-more",
	          href: "#"
	        },
	        events: {
	          click: clickHandler
	        },
	        text: this.getMessage("details")
	      })];
	    }
	  }, {
	    key: "prepareCutOffElements",
	    value: function prepareCutOffElements(text, length, clickHandler) {
	      if (!BX.type.isNumber(length)) {
	        length = 0;
	      }
	      if (length <= 0 || text.length <= length) {
	        return [BX.util.htmlspecialchars(text)];
	      }
	      let offset = length - 1;
	      const whilespaceOffset = text.substring(offset).search(/\s/i);
	      if (whilespaceOffset > 0) {
	        offset += whilespaceOffset;
	      }
	      return [BX.util.htmlspecialchars(text.substring(0, offset)) + "&hellip;&nbsp;", BX.create("A", {
	        attrs: {
	          className: "crm-entity-stream-content-letter-more",
	          href: "#"
	        },
	        events: {
	          click: clickHandler
	        },
	        text: this.getMessage("details")
	      })];
	    }
	  }, {
	    key: "prepareAuthorLayout",
	    value: function prepareAuthorLayout() {
	      const authorInfo = this.getObjectDataParam("AUTHOR", null);
	      if (!authorInfo) {
	        return null;
	      }
	      const showUrl = BX.prop.getString(authorInfo, "SHOW_URL", "");
	      if (showUrl === "") {
	        return null;
	      }
	      const link = BX.create("A", {
	        attrs: {
	          className: "ui-icon ui-icon-common-user crm-entity-stream-content-detail-employee",
	          href: showUrl,
	          target: "_blank",
	          title: BX.prop.getString(authorInfo, "FORMATTED_NAME", "")
	        },
	        children: [BX.create('i', {})]
	      });
	      const imageUrl = BX.prop.getString(authorInfo, "IMAGE_URL", "");
	      if (imageUrl !== "") {
	        link.children[0].style.backgroundImage = "url('" + encodeURI(imageUrl) + "')";
	        link.children[0].style.backgroundSize = "21px";
	      }
	      return link;
	    }
	  }, {
	    key: "onActivityCreate",
	    value: function onActivityCreate(activity, data) {}
	  }, {
	    key: "isContextMenuEnabled",
	    value: function isContextMenuEnabled() {
	      return false;
	    }
	  }, {
	    key: "prepareContextMenuButton",
	    value: function prepareContextMenuButton() {
	      this._contextMenuButton = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-context-menu"
	        },
	        events: {
	          click: BX.delegate(this.onContextMenuButtonClick, this)
	        }
	      });
	      return this._contextMenuButton;
	    }
	  }, {
	    key: "onContextMenuButtonClick",
	    value: function onContextMenuButtonClick(e) {
	      if (!this._isContextMenuShown) {
	        this.openContextMenu();
	      } else {
	        this.closeContextMenu();
	      }
	    }
	  }, {
	    key: "openContextMenu",
	    value: function openContextMenu() {
	      const menuItems = this.prepareContextMenuItems();
	      if (typeof IntranetExtensions !== "undefined") {
	        menuItems.push(IntranetExtensions);
	      }
	      if (menuItems.length === 0) {
	        return;
	      }
	      BX.PopupMenu.show(this._id, this._contextMenuButton, menuItems, {
	        offsetTop: 0,
	        offsetLeft: 16,
	        angle: {
	          position: "top",
	          offset: 0
	        },
	        events: {
	          onPopupShow: BX.delegate(this.onContextMenuShow, this),
	          onPopupClose: BX.delegate(this.onContextMenuClose, this),
	          onPopupDestroy: BX.delegate(this.onContextMenuDestroy, this)
	        }
	      });
	      this._contextMenu = BX.PopupMenu.currentItem;
	    }
	  }, {
	    key: "closeContextMenu",
	    value: function closeContextMenu() {
	      if (this._contextMenu) {
	        this._contextMenu.close();
	      }
	    }
	  }, {
	    key: "prepareContextMenuItems",
	    value: function prepareContextMenuItems() {
	      return [];
	    }
	  }, {
	    key: "onContextMenuShow",
	    value: function onContextMenuShow() {
	      this._isContextMenuShown = true;
	      BX.addClass(this._contextMenuButton, "active");
	    }
	  }, {
	    key: "onContextMenuClose",
	    value: function onContextMenuClose() {
	      if (this._contextMenu) {
	        this._contextMenu.popupWindow.destroy();
	      }
	    }
	  }, {
	    key: "onContextMenuDestroy",
	    value: function onContextMenuDestroy() {
	      this._isContextMenuShown = false;
	      BX.removeClass(this._contextMenuButton, "active");
	      this._contextMenu = null;
	      if (typeof BX.PopupMenu.Data[this._id] !== "undefined") {
	        delete BX.PopupMenu.Data[this._id];
	      }
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = CompatibleItem.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "getUserTimezoneOffset",
	    value: function getUserTimezoneOffset() {
	      return crm_datetime.TimezoneOffset.USER_TO_SERVER;
	    }
	  }]);
	  return CompatibleItem;
	}(crm_timeline_item.Item);
	babelHelpers.defineProperty(CompatibleItem, "messages", {});

	/** @memberof BX.Crm.Timeline.Animation */
	let Fasten = /*#__PURE__*/function () {
	  function Fasten() {
	    babelHelpers.classCallCheck(this, Fasten);
	    this._id = "";
	    this._settings = {};
	    this._initialItem = null;
	    this._finalItem = null;
	    this._events = null;
	  }
	  babelHelpers.createClass(Fasten, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._settings = settings ? settings : {};
	      this._initialItem = this.getSetting("initialItem");
	      this._finalItem = this.getSetting("finalItem");
	      this._anchor = this.getSetting("anchor");
	      this._events = this.getSetting("events", {});
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this._id;
	    }
	  }, {
	    key: "getSetting",
	    value: function getSetting(name, defaultValue) {
	      return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultValue;
	    }
	  }, {
	    key: "addFixedHistoryItem",
	    value: function addFixedHistoryItem() {
	      const node = this._finalItem.getWrapper();
	      BX.addClass(node, 'crm-entity-stream-section-animate-start');
	      if (this._anchor.parentNode && node) {
	        this._anchor.parentNode.insertBefore(node, this._anchor.nextSibling);
	      }
	      setTimeout(BX.delegate(function () {
	        BX.removeClass(node, 'crm-entity-stream-section-animate-start');
	      }, this), 0);
	    }
	  }, {
	    key: "run",
	    value: function run() {
	      const node = this._initialItem.getWrapper();
	      this._clone = node.cloneNode(true);
	      BX.addClass(this._clone, 'crm-entity-stream-section-animate-start crm-entity-stream-section-top-fixed');
	      this._startPosition = BX.pos(node);
	      this._clone.style.position = "absolute";
	      this._clone.style.width = this._startPosition.width + "px";
	      let _cloneHeight = this._startPosition.height;
	      const _minHeight = 65;
	      const _sumPaddingContent = 18;
	      if (_cloneHeight < _sumPaddingContent + _minHeight) _cloneHeight = _sumPaddingContent + _minHeight;
	      this._clone.style.height = _cloneHeight + "px";
	      this._clone.style.top = this._startPosition.top + "px";
	      this._clone.style.left = this._startPosition.left + "px";
	      this._clone.style.zIndex = 960;
	      document.body.appendChild(this._clone);
	      setTimeout(BX.proxy(function () {
	        BX.addClass(this._clone, "crm-entity-stream-section-casper");
	      }, this), 0);
	      this._anchorPosition = BX.pos(this._anchor);
	      const finish = {
	        top: this._anchorPosition.top,
	        height: _cloneHeight + 15,
	        opacity: 1
	      };
	      const _difference = this._startPosition.top - this._anchorPosition.bottom;
	      const _deepHistoryLimit = 2 * (document.body.clientHeight + this._startPosition.height);
	      if (_difference > _deepHistoryLimit) {
	        finish.top = this._startPosition.top - _deepHistoryLimit;
	        finish.opacity = 0;
	      }
	      let _duration = Math.abs(finish.top - this._startPosition.top) * 2;
	      _duration = _duration < 1500 ? 1500 : _duration;
	      const movingEvent = new BX.easing({
	        duration: _duration,
	        start: {
	          top: this._startPosition.top,
	          height: 0,
	          opacity: 1
	        },
	        finish: finish,
	        transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: BX.proxy(function (state) {
	          this._clone.style.top = state.top + "px";
	          this._clone.style.opacity = state.opacity;
	          this._anchor.style.height = state.height + "px";
	        }, this),
	        complete: BX.proxy(function () {
	          this.finish();
	        }, this)
	      });
	      movingEvent.animate();
	    }
	  }, {
	    key: "finish",
	    value: function finish() {
	      this._anchor.style.height = 0;
	      this.addFixedHistoryItem();
	      BX.remove(this._clone);
	      if (BX.type.isFunction(this._events["complete"])) {
	        this._events["complete"]();
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Fasten();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Fasten;
	}();

	/** @memberof BX.Crm.Timeline.Items */
	let History = /*#__PURE__*/function (_CompatibleItem) {
	  babelHelpers.inherits(History, _CompatibleItem);
	  function History() {
	    var _this;
	    babelHelpers.classCallCheck(this, History);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(History).call(this));
	    _this._history = null;
	    _this._fixedHistory = null;
	    _this._typeId = null;
	    _this._createdTime = null;
	    _this._isFixed = false;
	    _this._headerClickHandler = BX.delegate(_this.onHeaderClick, babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(History, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      this._history = this.getSetting("history");
	      this._fixedHistory = this.getSetting("fixedHistory");
	    }
	  }, {
	    key: "getTypeId",
	    value: function getTypeId() {
	      if (this._typeId === null) {
	        this._typeId = BX.prop.getInteger(this._data, "TYPE_ID", Item.undefined);
	      }
	      return this._typeId;
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return "";
	    }
	  }, {
	    key: "isContextMenuEnabled",
	    value: function isContextMenuEnabled() {
	      return !this.isReadOnly();
	    }
	  }, {
	    key: "getCreatedTimestamp",
	    value: function getCreatedTimestamp() {
	      return this.getTextDataParam("CREATED_SERVER");
	    }
	  }, {
	    key: "getCreatedTime",
	    value: function getCreatedTime() {
	      if (this._createdTime === null) {
	        const time = BX.parseDate(this.getCreatedTimestamp(), false, "YYYY-MM-DD", "YYYY-MM-DD HH:MI:SS");
	        this._createdTime = new crm_timeline_tools.DatetimeConverter(time).toUserTime().getValue();
	      }
	      return this._createdTime;
	    }
	  }, {
	    key: "getCreatedDate",
	    value: function getCreatedDate() {
	      return BX.prop.extractDate(new Date(this.getCreatedTime().getTime()));
	    }
	  }, {
	    key: "getOwnerInfo",
	    value: function getOwnerInfo() {
	      return this._history ? this._history.getOwnerInfo() : null;
	    }
	  }, {
	    key: "getOwnerTypeId",
	    value: function getOwnerTypeId() {
	      return BX.prop.getInteger(this.getOwnerInfo(), "ENTITY_TYPE_ID", BX.CrmEntityType.enumeration.undefined);
	    }
	  }, {
	    key: "getOwnerId",
	    value: function getOwnerId() {
	      return BX.prop.getInteger(this.getOwnerInfo(), "ENTITY_ID", 0);
	    }
	  }, {
	    key: "isReadOnly",
	    value: function isReadOnly() {
	      return this._history.isReadOnly();
	    }
	  }, {
	    key: "isEditable",
	    value: function isEditable() {
	      return !this.isReadOnly();
	    }
	  }, {
	    key: "isDone",
	    value: function isDone() {
	      const typeId = this.getTypeId();
	      if (typeId === Item.activity) {
	        const entityData = this.getAssociatedEntityData();
	        return BX.CrmActivityStatus.isFinal(BX.prop.getInteger(entityData, "STATUS", 0));
	      }
	      return false;
	    }
	  }, {
	    key: "isFixed",
	    value: function isFixed() {
	      return this._isFixed;
	    }
	    /**
	     * deprecated
	     */
	  }, {
	    key: "fasten",
	    value: function fasten(e) {
	      if (this._fixedHistory._items.length >= 3) {
	        if (!this.fastenLimitPopup) {
	          this.fastenLimitPopup = new BX.PopupWindow('timeline_fasten_limit_popup_' + this._id, this._switcher, {
	            content: BX.message('CRM_TIMELINE_FASTEN_LIMIT_MESSAGE'),
	            darkMode: true,
	            autoHide: true,
	            zIndex: 990,
	            angle: true,
	            closeByEsc: true,
	            bindOptions: {
	              forceBindPosition: true
	            }
	          });
	        }
	        this.fastenLimitPopup.show();
	        this.closeContextMenu();
	        return;
	      }
	      BX.ajax({
	        url: this._history._serviceUrl,
	        method: "POST",
	        dataType: "json",
	        data: {
	          "ACTION": "CHANGE_FASTEN_ITEM",
	          "VALUE": 'Y',
	          "OWNER_TYPE_ID": this.getOwnerTypeId(),
	          "OWNER_ID": this.getOwnerId(),
	          "ID": this._id
	        }
	      });
	      this.closeContextMenu();
	    }
	    /**
	     * deprecated
	     */
	  }, {
	    key: "onSuccessFasten",
	    value: function onSuccessFasten(result) {
	      if (result && BX.type.isNotEmptyString(result.ERROR)) return;
	      if (!this.isFixed()) {
	        this._data.IS_FIXED = 'Y';
	        const fixedItem = this._fixedHistory.createItem(this._data);
	        fixedItem._isFixed = true;
	        this._fixedHistory.addItem(fixedItem, 0);
	        fixedItem.layout({
	          add: false
	        });
	        this.refreshLayout();
	        const animation = Fasten.create("", {
	          initialItem: this,
	          finalItem: fixedItem,
	          anchor: this._fixedHistory._anchor
	        });
	        animation.run();
	      }
	      this.closeContextMenu();
	    }
	    /**
	     * deprecated
	     */
	  }, {
	    key: "unfasten",
	    value: function unfasten(e) {
	      BX.ajax({
	        url: this._history._serviceUrl,
	        method: "POST",
	        dataType: "json",
	        data: {
	          "ACTION": "CHANGE_FASTEN_ITEM",
	          "VALUE": 'N',
	          "OWNER_TYPE_ID": this.getOwnerTypeId(),
	          "OWNER_ID": this.getOwnerId(),
	          "ID": this._id
	        }
	      });
	      this.closeContextMenu();
	    }
	    /**
	     * deprecated
	     */
	  }, {
	    key: "onSuccessUnfasten",
	    value: function onSuccessUnfasten(result) {
	      if (result && BX.type.isNotEmptyString(result.ERROR)) return;
	      let item;
	      let historyItem;
	      if (this.isFixed()) {
	        item = this;
	        historyItem = this._history.findItemById(this._id);
	      } else {
	        item = this._fixedHistory.findItemById(this._id);
	        historyItem = this;
	      }
	      if (item) {
	        const index = this._fixedHistory.getItemIndex(item);
	        item.clearAnimate();
	        this._fixedHistory.removeItemByIndex(index);
	        if (historyItem) {
	          historyItem._data.IS_FIXED = 'N';
	          historyItem.refreshLayout();
	          BX.LazyLoad.showImages();
	        }
	      }
	    }
	  }, {
	    key: "clearAnimate",
	    value: function clearAnimate() {
	      if (!BX.type.isDomNode(this._wrapper)) return;
	      const wrapperPosition = BX.pos(this._wrapper);
	      const hideEvent = new BX.easing({
	        duration: 1000,
	        start: {
	          height: wrapperPosition.height,
	          opacity: 1,
	          marginBottom: 15
	        },
	        finish: {
	          height: 0,
	          opacity: 0,
	          marginBottom: 0
	        },
	        transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: BX.proxy(function (state) {
	          this._wrapper.style.height = state.height + "px";
	          this._wrapper.style.opacity = state.opacity;
	          this._wrapper.style.marginBottom = state.marginBottom;
	        }, this),
	        complete: BX.proxy(function () {
	          this.clearLayout();
	        }, this)
	      });
	      hideEvent.animate();
	    }
	  }, {
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "";
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-info";
	    }
	  }, {
	    key: "prepareContentDetails",
	    value: function prepareContentDetails() {
	      return [];
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      let wrapperClassName = this.getWrapperClassName();
	      if (wrapperClassName !== "") {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history" + " " + wrapperClassName;
	      } else {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history";
	      }
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: wrapperClassName
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: this.getIconClassName()
	        }
	      }));
	      if (this.isContextMenuEnabled()) {
	        main_core.Dom.append(this.prepareContextMenuButton(), wrapper);
	      }
	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [contentWrapper]
	      }));
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: [BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-event-title"
	          },
	          children: [BX.create("A", {
	            attrs: {
	              href: "#"
	            },
	            events: {
	              click: this._headerClickHandler
	            },
	            text: this.getTitle()
	          })]
	        }), BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-event-time"
	          },
	          text: this.formatTime(this.getCreatedTime())
	        })]
	      });
	      contentWrapper.appendChild(header);
	      contentWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: this.prepareContentDetails()
	      }));

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      }
	      //endregion

	      return wrapper;
	    }
	  }, {
	    key: "prepareLayout",
	    value: function prepareLayout(options) {
	      this._wrapper = this.prepareContent();
	      if (this._wrapper) {
	        const enableAdd = BX.type.isPlainObject(options) ? BX.prop.getBoolean(options, "add", true) : true;
	        if (enableAdd) {
	          const anchor = BX.type.isPlainObject(options) && BX.type.isElementNode(options["anchor"]) ? options["anchor"] : null;
	          if (anchor && anchor.nextSibling) {
	            this._container.insertBefore(this._wrapper, anchor.nextSibling);
	          } else {
	            this._container.appendChild(this._wrapper);
	          }
	        }
	        this.markAsTerminated(this._history.checkItemForTermination(this));
	      }
	    }
	  }, {
	    key: "onHeaderClick",
	    value: function onHeaderClick(e) {
	      this.view();
	      e.preventDefault ? e.preventDefault() : e.returnValue = false;
	    }
	  }, {
	    key: "prepareTitleLayout",
	    value: function prepareTitleLayout() {
	      return BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-title"
	        },
	        text: this.getTitle()
	      });
	    }
	  }, {
	    key: "prepareFixedSwitcherLayout",
	    value: function prepareFixedSwitcherLayout() {
	      const isFixed = this.getTextDataParam("IS_FIXED") === 'Y';
	      this._switcher = BX.create("span", {
	        attrs: {
	          className: "crm-entity-stream-section-top-fixed-btn"
	        },
	        events: {
	          click: isFixed ? BX.delegate(this.unfasten, this) : BX.delegate(this.fasten, this)
	        }
	      });
	      if (isFixed) BX.addClass(this._switcher, "crm-entity-stream-section-top-fixed-btn-active");
	      if (!this.isReadOnly() && !isFixed) {
	        const manager = this._history.getManager();
	        if (!manager.isSpotlightShowed()) {
	          manager.setSpotlightShowed();
	          BX.addClass(this._switcher, "crm-entity-stream-section-top-fixed-btn-spotlight");
	          const spotlight = new BX.SpotLight({
	            targetElement: this._switcher,
	            targetVertex: "middle-center",
	            lightMode: false,
	            id: "CRM_TIMELINE_FASTEN_SWITCHER",
	            zIndex: 900,
	            top: -3,
	            left: -1,
	            autoSave: true,
	            content: BX.message('CRM_TIMELINE_SPOTLIGHT_FASTEN_MESSAGE')
	          });
	          spotlight.show();
	        }
	      }
	      return this._switcher;
	    }
	  }, {
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      const statusNode = this.getStatusNode();
	      if (main_core.Type.isDomNode(statusNode)) {
	        main_core.Dom.append(statusNode, header);
	      }
	      header.appendChild(BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: this.formatTime(this.getCreatedTime())
	      }));
	      return header;
	    }
	  }, {
	    key: "getStatusNode",
	    value: function getStatusNode() {
	      return null;
	    }
	  }, {
	    key: "onActivityCreate",
	    value: function onActivityCreate(activity, data) {
	      this._history.getManager().onActivityCreated(activity, data);
	    }
	  }, {
	    key: "formatTime",
	    value: function formatTime(time) {
	      if (this.isFixed()) {
	        return this._fixedHistory.formatTime(time);
	      }
	      return this._history.formatTime(time);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new History();
	      self.initialize(id, settings);
	      return self;
	    }
	  }, {
	    key: "isCounterEnabled",
	    value: function isCounterEnabled(deadline) {
	      if (!BX.type.isDate(deadline)) {
	        return false;
	      }
	      let start = new Date();
	      start.setHours(0);
	      start.setMinutes(0);
	      start.setSeconds(0);
	      start.setMilliseconds(0);
	      start = start.getTime();
	      let end = new Date();
	      end.setHours(23);
	      end.setMinutes(59);
	      end.setSeconds(59);
	      end.setMilliseconds(999);
	      end = end.getTime();
	      const time = deadline.getTime();
	      return time < start || time >= start && time <= end;
	    }
	  }, {
	    key: "isCounterEnabledByLightTime",
	    value: function isCounterEnabledByLightTime(lightTime) {
	      if (!BX.type.isDate(lightTime)) {
	        return false;
	      }
	      const now = new Date().getTime();
	      const time = lightTime.getTime();
	      return time < now;
	    }
	  }]);
	  return History;
	}(CompatibleItem);

	/** @memberof BX.Crm.Timeline.Items */
	let Scheduled = /*#__PURE__*/function (_CompatibleItem) {
	  babelHelpers.inherits(Scheduled, _CompatibleItem);
	  function Scheduled() {
	    var _this;
	    babelHelpers.classCallCheck(this, Scheduled);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Scheduled).call(this));
	    _this._schedule = null;
	    _this._deadlineNode = null;
	    _this._headerClickHandler = BX.delegate(_this.onHeaderClick, babelHelpers.assertThisInitialized(_this));
	    _this._setAsDoneButtonHandler = BX.delegate(_this.onSetAsDoneButtonClick, babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(Scheduled, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      this._schedule = this.getSetting("schedule");
	      if (!(this._activityEditor instanceof BX.CrmActivityEditor)) {
	        throw "Scheduled. The field 'activityEditor' is not assigned.";
	      }
	      if (this.hasPermissions() && !this.verifyPermissions()) {
	        this.loadPermissions();
	      }
	    }
	  }, {
	    key: "getTypeId",
	    value: function getTypeId() {
	      return Item.undefined;
	    }
	  }, {
	    key: "verifyPermissions",
	    value: function verifyPermissions() {
	      const userId = BX.prop.getInteger(this.getPermissions(), "USER_ID", 0);
	      return userId <= 0 || userId === this._schedule.getUserId();
	    }
	  }, {
	    key: "loadPermissions",
	    value: function loadPermissions() {
	      BX.ajax({
	        url: this._schedule.getServiceUrl(),
	        method: "POST",
	        dataType: "json",
	        data: {
	          "ACTION": "GET_PERMISSIONS",
	          "TYPE_ID": this.getTypeId(),
	          "ID": this.getAssociatedEntityId()
	        },
	        onsuccess: this.onPermissionsLoad.bind(this)
	      });
	    }
	  }, {
	    key: "onPermissionsLoad",
	    value: function onPermissionsLoad(result) {
	      const permissions = BX.prop.getObject(result, "PERMISSIONS", null);
	      if (!permissions) {
	        return;
	      }
	      this.setPermissions(permissions);
	      window.setTimeout(function () {
	        this.refreshLayout();
	      }.bind(this), 0);
	    }
	  }, {
	    key: "getDeadline",
	    value: function getDeadline() {
	      return null;
	    }
	  }, {
	    key: "getLightTime",
	    value: function getLightTime() {
	      return null;
	    }
	  }, {
	    key: "hasDeadline",
	    value: function hasDeadline() {
	      return BX.type.isDate(this.getDeadline());
	    }
	  }, {
	    key: "isCounterEnabled",
	    value: function isCounterEnabled() {
	      if (this.isDone()) {
	        return this._existedStreamItemDeadLine && History.isCounterEnabledByLightTime(this._existedStreamItemDeadLine);
	      }
	      const lightTime = this.getLightTime();
	      return lightTime && History.isCounterEnabledByLightTime(lightTime);
	    }
	  }, {
	    key: "isIncomingChannel",
	    value: function isIncomingChannel() {
	      return false;
	    }
	  }, {
	    key: "getSourceId",
	    value: function getSourceId() {
	      return BX.prop.getInteger(this.getAssociatedEntityData(), "ID", 0);
	    }
	  }, {
	    key: "onSetAsDoneCompleted",
	    value: function onSetAsDoneCompleted(data) {
	      if (!BX.prop.getBoolean(data, "COMPLETED")) {
	        return;
	      }
	      this.markAsDone(true);
	      this._schedule.onItemMarkedAsDone(this, {
	        'historyItemData': BX.prop.getObject(data, "HISTORY_ITEM")
	      });
	    }
	  }, {
	    key: "onPosponeCompleted",
	    value: function onPosponeCompleted(data) {}
	  }, {
	    key: "refreshDeadline",
	    value: function refreshDeadline() {
	      this._deadlineNode.innerHTML = this.formatDateTime(this.getDeadline());
	    }
	  }, {
	    key: "formatDateTime",
	    value: function formatDateTime(time) {
	      return this._schedule.formatDateTime(time);
	    }
	  }, {
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "";
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon";
	    }
	  }, {
	    key: "isReadOnly",
	    value: function isReadOnly() {
	      return this._schedule.isReadOnly();
	    }
	  }, {
	    key: "isEditable",
	    value: function isEditable() {
	      return !this.isReadOnly();
	    }
	  }, {
	    key: "canPostpone",
	    value: function canPostpone() {
	      if (this.isReadOnly()) {
	        return false;
	      }
	      if (this.isIncomingChannel()) {
	        return false;
	      }
	      const perms = BX.prop.getObject(this.getAssociatedEntityData(), "PERMISSIONS", {});
	      return BX.prop.getBoolean(perms, "POSTPONE", false);
	    }
	  }, {
	    key: "isDone",
	    value: function isDone() {
	      return BX.CrmActivityStatus.isFinal(BX.prop.getInteger(this.getAssociatedEntityData(), "STATUS", 0));
	    }
	  }, {
	    key: "canComplete",
	    value: function canComplete() {
	      if (this.isReadOnly()) {
	        return false;
	      }
	      const perms = BX.prop.getObject(this.getAssociatedEntityData(), "PERMISSIONS", {});
	      return BX.prop.getBoolean(perms, "COMPLETE", false);
	    }
	  }, {
	    key: "setAsDone",
	    value: function setAsDone(isDone) {}
	  }, {
	    key: "prepareContent",
	    value: function prepareContent(options) {
	      return null;
	    }
	  }, {
	    key: "prepareLayout",
	    value: function prepareLayout(options) {
	      this._wrapper = this.prepareContent();
	      if (this._wrapper) {
	        const enableAdd = BX.type.isPlainObject(options) ? BX.prop.getBoolean(options, "add", true) : true;
	        if (enableAdd) {
	          const anchor = BX.type.isPlainObject(options) && BX.type.isElementNode(options["anchor"]) ? options["anchor"] : null;
	          if (anchor && anchor.nextSibling) {
	            this._container.insertBefore(this._wrapper, anchor.nextSibling);
	          } else {
	            this._container.appendChild(this._wrapper);
	          }
	        }
	        this.markAsTerminated(this._schedule.checkItemForTermination(this));
	      }
	    }
	  }, {
	    key: "onHeaderClick",
	    value: function onHeaderClick(e) {
	      this.view();
	      e.preventDefault ? e.preventDefault() : e.returnValue = false;
	    }
	  }, {
	    key: "onSetAsDoneButtonClick",
	    value: function onSetAsDoneButtonClick(e) {
	      if (this.canComplete()) {
	        this.setAsDone(!this.isDone());
	      }
	    }
	  }, {
	    key: "onActivityCreate",
	    value: function onActivityCreate(activity, data) {
	      this._schedule.getManager().onActivityCreated(activity, data);
	    }
	  }], [{
	    key: "isDone",
	    value: function isDone(data) {
	      const entityData = BX.prop.getObject(data, "ASSOCIATED_ENTITY", {});
	      return BX.CrmActivityStatus.isFinal(BX.prop.getInteger(entityData, "STATUS", 0));
	    }
	  }, {
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Scheduled();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Scheduled;
	}(CompatibleItem);

	/** @memberof BX.Crm.Timeline.Animation */
	let Item$1 = /*#__PURE__*/function () {
	  function Item() {
	    babelHelpers.classCallCheck(this, Item);
	    this._id = "";
	    this._settings = {};
	    this._initialItem = null;
	    this._finalItem = null;
	    this._events = null;
	  }
	  babelHelpers.createClass(Item, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._settings = settings ? settings : {};
	      this._initialItem = this.getSetting("initialItem");
	      this._finalItem = this.getSetting("finalItem");
	      this._anchor = this.getSetting("anchor");
	      this._events = this.getSetting("events", {});
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this._id;
	    }
	  }, {
	    key: "getSetting",
	    value: function getSetting(name, defaultval) {
	      return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	    }
	  }, {
	    key: "run",
	    value: function run() {
	      this._node = this._initialItem.getWrapper();
	      const originalPosition = BX.pos(this._node);
	      this._initialYPosition = originalPosition.top;
	      this._initialXPosition = originalPosition.left;
	      this._initialWidth = this._node.offsetWidth;
	      this._initialHeight = this._node.offsetHeight;
	      this._anchorYPosition = BX.pos(this._anchor).top;
	      this.createStub();
	      this.createGhost();
	      this.moveGhost();
	    }
	  }, {
	    key: "createStub",
	    value: function createStub() {
	      this._stub = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-planned crm-entity-stream-section-shadow"
	        },
	        children: [BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-icon"
	          }
	        }), BX.create("DIV", {
	          props: {
	            className: "crm-entity-stream-section-content"
	          },
	          style: {
	            height: this._initialHeight + "px"
	          }
	        })]
	      });
	      this._node.parentNode.insertBefore(this._stub, this._node);
	    }
	  }, {
	    key: "createGhost",
	    value: function createGhost() {
	      this._ghostNode = this._node;
	      this._ghostNode.style.position = "absolute";
	      this._ghostNode.style.width = this._initialWidth + "px";
	      this._ghostNode.style.height = this._initialHeight + "px";
	      this._ghostNode.style.top = this._initialYPosition + "px";
	      this._ghostNode.style.left = this._initialXPosition + "px";
	      document.body.appendChild(this._ghostNode);
	      setTimeout(BX.proxy(function () {
	        BX.addClass(this._ghostNode, "crm-entity-stream-section-casper");
	      }, this), 20);
	    }
	  }, {
	    key: "moveGhost",
	    value: function moveGhost() {
	      const node = this._ghostNode;
	      const movingEvent = new BX.easing({
	        duration: 500,
	        start: {
	          top: this._initialYPosition
	        },
	        finish: {
	          top: this._anchorYPosition
	        },
	        transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: BX.proxy(function (state) {
	          node.style.top = state.top + "px";
	        }, this)
	      });
	      setTimeout(BX.proxy(function () {
	        movingEvent.animate();
	        node.style.boxShadow = "";
	      }, this), 500);
	      const placeEventAnim = new BX.easing({
	        duration: 500,
	        start: {
	          height: 0
	        },
	        finish: {
	          height: this._initialHeight + 20
	        },
	        transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: BX.proxy(function (state) {
	          this._anchor.style.height = state.height + "px";
	        }, this),
	        complete: BX.proxy(function () {
	          if (BX.type.isFunction(this._events["complete"])) {
	            this._events["complete"]();
	          }
	          this.addHistoryItem();
	          this.removeGhost();
	        }, this)
	      });
	      setTimeout(function () {
	        placeEventAnim.animate();
	      }, 500);
	    }
	  }, {
	    key: "addHistoryItem",
	    value: function addHistoryItem() {
	      const node = this._finalItem.getWrapper();
	      this._anchor.parentNode.insertBefore(node, this._anchor.nextSibling);
	      this._finalItemHeight = this._anchor.offsetHeight - node.offsetHeight;
	      this._anchor.style.height = 0;
	      node.style.marginBottom = this._finalItemHeight + "px";
	    }
	  }, {
	    key: "removeGhost",
	    value: function removeGhost() {
	      const ghostNode = this._ghostNode;
	      const finalNode = this._finalItem.getWrapper();
	      ghostNode.style.overflow = "hidden";
	      const hideCasperItem = new BX.easing({
	        duration: 70,
	        start: {
	          opacity: 100,
	          height: ghostNode.offsetHeight,
	          marginBottom: this._finalItemHeight
	        },
	        finish: {
	          opacity: 0,
	          height: finalNode.offsetHeight,
	          marginBottom: 20
	        },
	        // transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: BX.proxy(function (state) {
	          ghostNode.style.opacity = state.opacity / 100;
	          ghostNode.style.height = state.height + "px";
	          finalNode.style.marginBottom = state.marginBottom + "px";
	        }, this),
	        complete: BX.proxy(function () {
	          ghostNode.remove();
	          finalNode.style.marginBottom = "";
	          this.collapseStub();
	        }, this)
	      });
	      hideCasperItem.animate();
	    }
	  }, {
	    key: "collapseStub",
	    value: function collapseStub() {
	      const removePlannedEvent = new BX.easing({
	        duration: 500,
	        start: {
	          opacity: 100,
	          height: this._initialHeight,
	          marginBottom: 15
	        },
	        finish: {
	          opacity: 0,
	          height: 0,
	          marginBottom: 0
	        },
	        transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: BX.proxy(function (state) {
	          this._stub.style.height = state.height + "px";
	          this._stub.style.marginBottom = state.marginBottom + "px";
	          this._stub.style.opacity = state.opacity / 100;
	        }, this),
	        complete: BX.proxy(function () {
	          this.inited = false;
	        }, this)
	      });
	      removePlannedEvent.animate();
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Item();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Item;
	}();

	/** @memberof BX.Crm.Timeline.Animation */
	let Shift = /*#__PURE__*/function () {
	  function Shift() {
	    babelHelpers.classCallCheck(this, Shift);
	    this._node = null;
	    this._anchor = null;
	    this._nodeParent = null;
	    this._startPosition = null;
	    this._events = null;
	  }
	  babelHelpers.createClass(Shift, [{
	    key: "initialize",
	    value: function initialize(node, anchor, startPosition, shadowNode, events) {
	      this._node = node;
	      this._shadowNode = shadowNode;
	      this._anchor = anchor;
	      this._nodeParent = node.parentNode;
	      this._startPosition = startPosition;
	      this._events = BX.type.isPlainObject(events) ? events : {};
	    }
	  }, {
	    key: "run",
	    value: function run() {
	      this._anchorPosition = BX.pos(this._anchor);
	      setTimeout(BX.proxy(function () {
	        BX.addClass(this._node, "crm-entity-stream-section-casper");
	      }, this), 0);
	      const movingEvent = new BX.easing({
	        duration: 1500,
	        start: {
	          top: this._startPosition.top,
	          height: 0
	        },
	        finish: {
	          top: this._anchorPosition.top,
	          height: this._startPosition.height + 20
	        },
	        transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: BX.proxy(function (state) {
	          this._node.style.top = state.top + "px";
	          this._anchor.style.height = state.height + "px";
	        }, this),
	        complete: BX.proxy(function () {
	          this.finish();
	        }, this)
	      });
	      movingEvent.animate();
	    }
	  }, {
	    key: "finish",
	    value: function finish() {
	      if (BX.type.isFunction(this._events["complete"])) {
	        this._events["complete"]();
	      }
	      if (this._shadowNode !== false) ;
	    }
	  }], [{
	    key: "create",
	    value: function create(node, anchor, startPosition, shadowNode, events) {
	      const self = new Shift();
	      self.initialize(node, anchor, startPosition, shadowNode, events);
	      return self;
	    }
	  }]);
	  return Shift;
	}();

	/** @memberof BX.Crm.Timeline.Animation */
	let ItemNew = /*#__PURE__*/function () {
	  function ItemNew() {
	    babelHelpers.classCallCheck(this, ItemNew);
	    this._id = "";
	    this._settings = {};
	    this._initialItem = null;
	    this._finalItem = null;
	    this._events = null;
	    this._areAnimatedItemsVisible = null;
	  }
	  babelHelpers.createClass(ItemNew, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._settings = settings ? settings : {};
	      this._initialItem = this.getSetting("initialItem");
	      this._finalItem = this.getSetting("finalItem");
	      this._anchor = this.getSetting("anchor");
	      this._events = this.getSetting("events", {});
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this._id;
	    }
	  }, {
	    key: "getSetting",
	    value: function getSetting(name, defaultval) {
	      return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	    }
	  }, {
	    key: "addHistoryItem",
	    value: function addHistoryItem() {
	      if (this._finalItem instanceof CompatibleItem) {
	        const node = this._finalItem.getWrapper();
	        this._anchor.parentNode.insertBefore(node, this._anchor.nextSibling);
	      } else {
	        this._finalItem.initWrapper();
	        main_core.Dom.insertBefore(this._finalItem.getWrapper(), this._anchor.nextSibling);
	        this._finalItem.initLayoutApp({
	          add: false
	        });
	      }
	    }
	  }, {
	    key: "run",
	    value: function run() {
	      this._node = this._initialItem.getWrapper();
	      this._areAnimatedItemsVisible = this._node.offsetParent !== null;
	      if (this._areAnimatedItemsVisible) {
	        this.createStub();
	        BX.addClass(this._node, 'crm-entity-stream-section-animate-start');
	        this._startPosition = BX.pos(this._stub);
	        this._node.style.position = "absolute";
	        this._node.style.width = this._startPosition.width + "px";
	        this._node.style.height = this._startPosition.height + "px";
	        this._node.style.top = this._startPosition.top + "px";
	        this._node.style.left = this._startPosition.left + "px";
	        this._node.style.zIndex = 960;
	        document.body.appendChild(this._node);
	        const shift = Shift.create(this._node, this._anchor, this._startPosition, this._stub, {
	          complete: BX.delegate(this.finish, this)
	        });
	        shift.run();
	      } else {
	        this.finish();
	      }
	    }
	  }, {
	    key: "createStub",
	    value: function createStub() {
	      this._stub = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-planned crm-entity-stream-section-shadow"
	        },
	        children: [BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-icon"
	          }
	        }), BX.create("DIV", {
	          props: {
	            className: "crm-entity-stream-section-content"
	          },
	          style: {
	            height: this._initialItem._wrapper.clientHeight + "px"
	          }
	        })]
	      });
	      this._node.parentNode.insertBefore(this._stub, this._node);
	    }
	  }, {
	    key: "finish",
	    value: function finish() {
	      this._anchor.style.height = 0;
	      //this._anchor.parentNode.insertBefore(this._node, this._anchor.nextSibling);

	      if (this._areAnimatedItemsVisible) {
	        const stubContainer = this._stub.querySelector('.crm-entity-stream-section-content');
	        setTimeout(BX.delegate(function () {
	          BX.removeClass(this._node, 'crm-entity-stream-section-animate-start');
	        }, this), 0);
	        this._node.style.opacity = 0;
	        setTimeout(BX.delegate(function () {
	          stubContainer.style.height = 0;
	          stubContainer.style.opacity = 0;
	          stubContainer.style.paddingTop = 0;
	          stubContainer.style.paddingBottom = 0;
	        }, this), 120);
	      }
	      setTimeout(BX.delegate(function () {
	        if (this._areAnimatedItemsVisible) {
	          BX.remove(this._stub);
	        }
	        BX.remove(this._node);
	        this.addHistoryItem();
	        if (BX.type.isFunction(this._events["complete"])) {
	          this._events["complete"]();
	        }
	      }, this), 420);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new ItemNew();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return ItemNew;
	}();

	/** @memberof BX.Crm.Timeline */
	let Steam = /*#__PURE__*/function () {
	  function Steam() {
	    babelHelpers.classCallCheck(this, Steam);
	    this._id = "";
	    this._settings = {};
	    this._container = null;
	    this._manager = null;
	    this._activityEditor = null;
	    this._timeFormat = "";
	    this._year = 0;
	    this._isStubMode = false;
	    this._userId = 0;
	    this._readOnly = false;
	    this._streamType = crm_timeline_item.StreamType.history;
	    this._anchor = null;
	    this._serviceUrl = "";
	  }
	  babelHelpers.createClass(Steam, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._settings = settings ? settings : {};
	      this._container = BX(this.getSetting("container"));
	      if (!BX.type.isElementNode(this._container)) {
	        throw "Timeline. Container node is not found.";
	      }
	      this._editorContainer = BX(this.getSetting("editorContainer"));
	      this._manager = this.getSetting("manager");
	      if (!(this._manager instanceof Manager)) {
	        throw "Timeline. Manager instance is not found.";
	      }

	      //
	      const datetimeFormat = BX.message("FORMAT_DATETIME").replace(/:SS/, "");
	      const dateFormat = BX.message("FORMAT_DATE");
	      this._timeFormat = BX.date.convertBitrixFormat(BX.util.trim(datetimeFormat.replace(dateFormat, "")));
	      //
	      this._year = new Date().getFullYear();
	      this._activityEditor = this.getSetting("activityEditor");
	      this._isStubMode = BX.prop.getBoolean(this._settings, "isStubMode", false);
	      this._readOnly = BX.prop.getBoolean(this._settings, "readOnly", false);
	      this._userId = BX.prop.getInteger(this._settings, "userId", 0);
	      this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");
	      this.doInitialize();
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this._id;
	    }
	  }, {
	    key: "isScheduleStream",
	    value: function isScheduleStream() {
	      return this.getStreamType() === crm_timeline_item.StreamType.scheduled;
	    }
	  }, {
	    key: "isFixedHistoryStream",
	    value: function isFixedHistoryStream() {
	      return this.getStreamType() === crm_timeline_item.StreamType.pinned;
	    }
	  }, {
	    key: "isHistoryStream",
	    value: function isHistoryStream() {
	      return this.getStreamType() === crm_timeline_item.StreamType.history;
	    }
	  }, {
	    key: "getSetting",
	    value: function getSetting(name, defaultval) {
	      return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	    }
	  }, {
	    key: "doInitialize",
	    value: function doInitialize() {}
	  }, {
	    key: "layout",
	    value: function layout() {}
	  }, {
	    key: "isStubMode",
	    value: function isStubMode() {
	      return this._isStubMode;
	    }
	  }, {
	    key: "isReadOnly",
	    value: function isReadOnly() {
	      return this._readOnly;
	    }
	  }, {
	    key: "getUserId",
	    value: function getUserId() {
	      return this._userId;
	    }
	  }, {
	    key: "getServiceUrl",
	    value: function getServiceUrl() {
	      return this._serviceUrl;
	    }
	  }, {
	    key: "getAnchor",
	    value: function getAnchor() {
	      return this._anchor;
	    }
	  }, {
	    key: "getStreamType",
	    value: function getStreamType() {
	      return this._streamType;
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {}
	  }, {
	    key: "getManager",
	    value: function getManager() {
	      return this._manager;
	    }
	  }, {
	    key: "getOwnerInfo",
	    value: function getOwnerInfo() {
	      return this._manager.getOwnerInfo();
	    }
	  }, {
	    key: "reload",
	    value: function reload() {
	      const currentUrl = this.getSetting("currentUrl");
	      const ajaxId = this.getSetting("ajaxId");
	      if (ajaxId !== "") {
	        BX.ajax.insertToNode(BX.util.add_url_param(currentUrl, {
	          bxajaxid: ajaxId
	        }), "comp_" + ajaxId);
	      } else {
	        window.location = currentUrl;
	      }
	    }
	  }, {
	    key: "getUserTimezoneOffset",
	    value: function getUserTimezoneOffset() {
	      return crm_datetime.TimezoneOffset.USER_TO_SERVER;
	    }
	  }, {
	    key: "getServerTimezoneOffset",
	    value: function getServerTimezoneOffset() {
	      return crm_datetime.TimezoneOffset.SERVER_TO_UTC;
	    } // @todo replace by DatetimeConverter
	  }, {
	    key: "formatTime",
	    value: function formatTime(time, now, utc) {
	      return BX.date.format(this._timeFormat, time, now, utc);
	    } // @todo replace by DatetimeConverter
	  }, {
	    key: "formatDate",
	    value: function formatDate(date) {
	      return BX.date.format([["today", "today"], ["tommorow", "tommorow"], ["yesterday", "yesterday"], ["", date.getFullYear() === this._year ? crm_datetime.Format.DAY_MONTH_FORMAT : crm_datetime.Format.LONG_DATE_FORMAT]], date);
	    }
	  }, {
	    key: "cutOffText",
	    value: function cutOffText(text, length) {
	      if (!BX.type.isNumber(length)) {
	        length = 0;
	      }
	      if (length <= 0 || text.length <= length) {
	        return text;
	      }
	      let offset = length - 1;
	      const whitespaceOffset = text.substring(offset).search(/\s/i);
	      if (whitespaceOffset > 0) {
	        offset += whitespaceOffset;
	      }
	      return text.substring(0, offset) + "...";
	    }
	  }, {
	    key: "getItems",
	    value: function getItems() {
	      return [];
	    }
	    /**
	     * @abstract
	     */
	  }, {
	    key: "setItems",
	    value: function setItems(items) {
	      throw new Error('Stream.setItems() must be overridden');
	    }
	  }, {
	    key: "getLastItem",
	    value: function getLastItem() {
	      const items = this.getItems();
	      return items.length > 0 ? items[items.length - 1] : null;
	    }
	  }, {
	    key: "findItemById",
	    value: function findItemById(id) {
	      id = id.toString();
	      return this.getItems().find(item => item.getId() === id) || null;
	    }
	  }, {
	    key: "getItemIndex",
	    value: function getItemIndex(item) {
	      return this.getItems().findIndex(currentItem => currentItem === item);
	    }
	  }, {
	    key: "removeItemByIndex",
	    value: function removeItemByIndex(index) {
	      const items = this.getItems();
	      if (index < items.length) {
	        items.splice(index, 1);
	        this.setItems(items);
	      }
	    }
	    /**
	     * @abstract
	     */
	  }, {
	    key: "createItem",
	    value: function createItem(data) {
	      throw new Error('Stream.createItem() must be overridden');
	    }
	  }, {
	    key: "createItemCopy",
	    value: function createItemCopy(item) {
	      if (item instanceof crm_timeline_item.ConfigurableItem) {
	        return item.clone();
	      }
	      return this.createItem(item.getData());
	    }
	  }, {
	    key: "refreshItem",
	    value: function refreshItem(item, animateUpdate = true, animateMove) {
	      const index = this.getItemIndex(item);
	      if (index < 0) {
	        return Promise.resolve();
	      }
	      this.removeItemByIndex(index);
	      let itemPositionChanged = false;
	      let newIndex = 0;
	      let newItem;
	      if (this.isScheduleStream()) {
	        newItem = this.createItemCopy(item);
	        newIndex = this.calculateItemIndex(newItem);
	        itemPositionChanged = newIndex !== index;
	      }
	      if (!itemPositionChanged) {
	        this.addItem(item, newIndex);
	        item.refreshLayout();
	        if (animateUpdate) {
	          return this.animateItemAdding(item);
	        }
	        return Promise.resolve();
	      }
	      const anchor = this.createAnchor(newIndex);
	      this.addItem(newItem, newIndex);
	      if (animateMove) {
	        newItem.layout({
	          add: false
	        });
	        return new Promise(resolve => {
	          const animation = Item$1.create('', {
	            initialItem: item,
	            finalItem: newItem,
	            anchor: anchor,
	            events: {
	              complete: () => {
	                item.destroy();
	                resolve();
	              }
	            }
	          });
	          animation.run();
	        });
	      } else {
	        newItem.layout({
	          anchor: anchor
	        });
	        item.destroy();
	        return Promise.resolve();
	      }
	    }
	  }, {
	    key: "calculateItemIndex",
	    value: function calculateItemIndex(item) {
	      return 0;
	    }
	  }, {
	    key: "createAnchor",
	    value: function createAnchor(index) {
	      return null;
	    }
	    /**
	     * @abstract
	     */
	  }, {
	    key: "addItem",
	    value: function addItem(item, index) {
	      throw new Error('Stream.addItem() must be overridden');
	    }
	    /**
	     * @abstract
	     */
	  }, {
	    key: "deleteItem",
	    value: function deleteItem(item) {
	      throw new Error('Stream.deleteItem() must be overridden');
	    }
	  }, {
	    key: "deleteItemAnimated",
	    value: function deleteItemAnimated(item) {
	      if (!main_core.Type.isDomNode(item.getWrapper())) {
	        this.deleteItem(item);
	        return Promise.resolve();
	      }
	      return new Promise(resolve => {
	        const wrapperPosition = main_core.Dom.getPosition(item.getWrapper());
	        const hideEvent = new BX.easing({
	          duration: 1000,
	          start: {
	            height: wrapperPosition.height,
	            opacity: 1,
	            marginBottom: 15
	          },
	          finish: {
	            height: 0,
	            opacity: 0,
	            marginBottom: 0
	          },
	          transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	          step: state => {
	            main_core.Dom.style(item.getWrapper(), {
	              height: state.height + 'px',
	              opacity: state.opacity,
	              marginBottom: state.marginBottom
	            });
	          },
	          complete: () => {
	            this.deleteItem(item);
	            resolve();
	          }
	        });
	        hideEvent.animate();
	      });
	    }
	  }, {
	    key: "moveItemToStream",
	    value: function moveItemToStream(item, destinationStream, destinationItem) {
	      this.removeItemByIndex(this.getItemIndex(item));
	      if (this.getItems().length > 0) {
	        this.refreshLayout();
	      }
	      return new Promise(resolve => {
	        const animation = ItemNew.create('', {
	          initialItem: item,
	          finalItem: destinationItem,
	          anchor: destinationStream.createAnchor(),
	          events: {
	            complete: () => {
	              this.refreshLayout();
	              destinationStream.refreshLayout();
	              resolve();
	            }
	          }
	        });
	        animation.run();
	      });
	    }
	  }, {
	    key: "animateItemAdding",
	    value: function animateItemAdding(item) {
	      return Promise.resolve();
	    }
	  }]);
	  return Steam;
	}();

	/** @memberof BX.Crm.Timeline.Streams */
	let EntityChat = /*#__PURE__*/function (_Stream) {
	  babelHelpers.inherits(EntityChat, _Stream);
	  function EntityChat() {
	    var _this;
	    babelHelpers.classCallCheck(this, EntityChat);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EntityChat).call(this));
	    _this._data = null;
	    _this._layoutType = EntityChat.LayoutType.none;
	    _this._wrapper = null;
	    _this._contentWrapper = null;
	    _this._messageWrapper = null;
	    _this._messageDateNode = null;
	    _this._messageTexWrapper = null;
	    _this._messageTextNode = null;
	    _this._userWrapper = null;
	    _this._extraUserCounter = null;
	    _this._openChatHandler = BX.delegate(_this.onOpenChat, babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(EntityChat, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      this._data = BX.prop.getObject(this._settings, "data", {});
	    }
	  }, {
	    key: "getData",
	    value: function getData() {
	      return this._data;
	    }
	  }, {
	    key: "setData",
	    value: function setData(data) {
	      this._data = BX.type.isPlainObject(data) ? data : {};
	    }
	  }, {
	    key: "isEnabled",
	    value: function isEnabled() {
	      return BX.prop.getBoolean(this._data, "ENABLED", true);
	    }
	    /**
	     * @private
	     * @return {boolean}
	     */
	  }, {
	    key: "isRestricted",
	    value: function isRestricted() {
	      return BX.prop.getBoolean(this._data, "IS_RESTRICTED", false);
	    }
	    /**
	     * @private
	     * @return {void}
	     */
	  }, {
	    key: "applyLockScript",
	    value: function applyLockScript() {
	      const lockScript = BX.prop.getString(this._data, "LOCK_SCRIPT", null);
	      if (BX.Type.isString(lockScript) && lockScript !== '') {
	        eval(lockScript);
	      }
	    }
	  }, {
	    key: "getChatId",
	    value: function getChatId() {
	      return BX.prop.getInteger(this._data, "CHAT_ID", 0);
	    }
	  }, {
	    key: "getUserId",
	    value: function getUserId() {
	      const userId = parseInt(top.BX.message("USER_ID"));
	      return !isNaN(userId) ? userId : 0;
	    }
	  }, {
	    key: "getMessageData",
	    value: function getMessageData() {
	      return BX.prop.getObject(this._data, "MESSAGE", {});
	    }
	  }, {
	    key: "setMessageData",
	    value: function setMessageData(data) {
	      this._data["MESSAGE"] = BX.type.isPlainObject(data) ? data : {};
	    }
	  }, {
	    key: "getUserInfoData",
	    value: function getUserInfoData() {
	      return BX.prop.getObject(this._data, "USER_INFOS", {});
	    }
	  }, {
	    key: "setUserInfoData",
	    value: function setUserInfoData(data) {
	      this._data["USER_INFOS"] = BX.type.isPlainObject(data) ? data : {};
	    }
	  }, {
	    key: "hasUserInfo",
	    value: function hasUserInfo(userId) {
	      return userId > 0 && BX.type.isPlainObject(this.getUserInfoData()[userId]);
	    }
	  }, {
	    key: "getUserInfo",
	    value: function getUserInfo(userId) {
	      const userInfos = this.getUserInfoData();
	      return userId > 0 && BX.type.isPlainObject(userInfos[userId]) ? userInfos[userId] : null;
	    }
	  }, {
	    key: "removeUserInfo",
	    value: function removeUserInfo(userId) {
	      const userInfos = this.getUserInfoData();
	      if (userId > 0 && BX.type.isPlainObject(userInfos[userId])) {
	        delete userInfos[userId];
	      }
	    }
	  }, {
	    key: "setUnreadMessageCounter",
	    value: function setUnreadMessageCounter(userId, counter) {
	      const userInfos = this.getUserInfoData();
	      if (userId > 0 && BX.type.isPlainObject(userInfos[userId])) {
	        userInfos[userId]["counter"] = counter;
	      }
	    }
	  }, {
	    key: "layout",
	    value: function layout() {
	      if (!this.isEnabled() || this.isStubMode()) {
	        return;
	      }
	      this._wrapper = BX.create("div", {
	        props: {
	          className: "crm-entity-stream-section crm-entity-stream-section-live-im"
	        }
	      });
	      this._container.appendChild(this._wrapper);
	      this._wrapper.appendChild(BX.create("div", {
	        props: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-live-im"
	        }
	      }));
	      this._contentWrapper = BX.create("div", {
	        props: {
	          className: "crm-entity-stream-content-live-im-detail"
	        }
	      });
	      this._wrapper.appendChild(BX.create("div", {
	        props: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [BX.create("div", {
	          props: {
	            className: "crm-entity-stream-content-event"
	          },
	          children: [this._contentWrapper]
	        })]
	      }));
	      this._userWrapper = BX.create("div", {
	        props: {
	          className: "crm-entity-stream-live-im-user-avatars"
	        }
	      });
	      this._contentWrapper.appendChild(BX.create("div", {
	        props: {
	          className: "crm-entity-stream-live-im-users"
	        },
	        children: [this._userWrapper]
	      }));
	      this._extraUserCounter = BX.create("div", {
	        props: {
	          className: "crm-entity-stream-live-im-user-counter"
	        }
	      });
	      this._contentWrapper.appendChild(this._extraUserCounter);
	      this._layoutType = EntityChat.LayoutType.none;
	      if (this.getChatId() > 0) {
	        this.renderSummary();
	      } else {
	        this.renderInvitation();
	      }
	      BX.bind(this._contentWrapper, "click", this._openChatHandler);
	      BX.addCustomEvent("onPullEvent-im", this.onChatEvent.bind(this));
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {
	      BX.cleanNode(this._contentWrapper);
	      this._userWrapper = BX.create("div", {
	        props: {
	          className: "crm-entity-stream-live-im-user-avatars"
	        }
	      });
	      this._contentWrapper.appendChild(BX.create("div", {
	        props: {
	          className: "crm-entity-stream-live-im-users"
	        },
	        children: [this._userWrapper]
	      }));
	      this._extraUserCounter = BX.create("div", {
	        props: {
	          className: "crm-entity-stream-live-im-user-counter"
	        }
	      });
	      this._contentWrapper.appendChild(this._extraUserCounter);
	      this._layoutType = EntityChat.LayoutType.none;
	      if (this.getChatId() > 0) {
	        this.renderSummary();
	      } else {
	        this.renderInvitation();
	      }
	    }
	  }, {
	    key: "renderInvitation",
	    value: function renderInvitation() {
	      this._layoutType = EntityChat.LayoutType.invitation;
	      this.refreshUsers();
	      this._messageTextNode = BX.create("div", {
	        props: {
	          className: "crm-entity-stream-live-im-user-invite-text"
	        }
	      });
	      this._contentWrapper.appendChild(this._messageTextNode);
	      this._messageTextNode.innerHTML = this.getMessage("invite");
	    }
	  }, {
	    key: "renderSummary",
	    value: function renderSummary() {
	      this._layoutType = EntityChat.LayoutType.summary;
	      this.refreshUsers();
	      this._contentWrapper.appendChild(BX.create("div", {
	        props: {
	          className: "crm-entity-stream-live-im-separator"
	        }
	      }));
	      this._messageWrapper = BX.create("div", {
	        props: {
	          className: "crm-entity-stream-live-im-messanger"
	        }
	      });
	      this._contentWrapper.appendChild(this._messageWrapper);
	      this._messageDateNode = BX.create("div", {
	        props: {
	          className: "crm-entity-stream-live-im-time"
	        }
	      });
	      this._messageWrapper.appendChild(this._messageDateNode);
	      this._messageTexWraper = BX.create("div", {
	        props: {
	          className: "crm-entity-stream-live-im-message"
	        }
	      });
	      this._messageWrapper.appendChild(this._messageTexWraper);
	      this._messageTextNode = BX.create("div", {
	        props: {
	          className: "crm-entity-stream-live-im-message-text"
	        }
	      });
	      this._messageTexWraper.appendChild(this._messageTextNode);
	      this._messageCounterNode = BX.create("div", {
	        props: {
	          className: "crm-entity-stream-live-im-message-counter"
	        }
	      });
	      this._messageWrapper.appendChild(this._messageCounterNode);
	      this.refreshSummary();
	    }
	  }, {
	    key: "refreshUsers",
	    value: function refreshUsers() {
	      BX.cleanNode(this._userWrapper);
	      const infos = this.getUserInfoData();
	      const list = Object.values(infos);
	      if (list.length === 0) {
	        this._userWrapper.appendChild(BX.create("span", {
	          props: {
	            className: "crm-entity-stream-live-im-user-avatar ui-icon ui-icon-common-user"
	          },
	          children: [BX.create("i")]
	        }));
	      } else {
	        const count = list.length >= 3 ? 3 : list.length;
	        for (let i = 0; i < count; i++) {
	          const info = list[i];
	          const icon = BX.create("i");
	          const imageUrl = BX.prop.getString(info, "avatar", "");
	          if (imageUrl !== "") {
	            icon.style.backgroundImage = "url(" + encodeURI(imageUrl) + ")";
	          }
	          this._userWrapper.appendChild(BX.create("span", {
	            props: {
	              className: "crm-entity-stream-live-im-user-avatar ui-icon ui-icon-common-user"
	            },
	            children: [icon]
	          }));
	        }
	      }
	      if (this._layoutType === EntityChat.LayoutType.summary) {
	        if (list.length > 3) {
	          this._extraUserCounter.display = "";
	          this._extraUserCounter.innerHTML = "+" + (list.length - 3).toString();
	        } else {
	          if (this._extraUserCounter.innerHTML !== "") {
	            this._extraUserCounter.innerHTML = "";
	          }
	          this._extraUserCounter.display = "none";
	        }
	      } else
	        //if(this._layoutType === EntityChat.LayoutType.invitation)
	        {
	          if (this._extraUserCounter.innerHTML !== "") {
	            this._extraUserCounter.innerHTML = "";
	          }
	          this._extraUserCounter.display = "none";
	          this._userWrapper.appendChild(BX.create("span", {
	            props: {
	              className: "crm-entity-stream-live-im-user-invite-btn"
	            }
	          }));
	        }
	    }
	  }, {
	    key: "refreshSummary",
	    value: function refreshSummary() {
	      if (this._layoutType !== EntityChat.LayoutType.summary) {
	        return;
	      }
	      const message = this.getMessageData();

	      //region Message Date
	      const isoDate = BX.prop.getString(message, "date", "");
	      if (isoDate === "") {
	        this._messageDateNode.innerHTML = "";
	      } else {
	        // @todo replace by DatetimeConverter
	        const remoteDate = new Date(isoDate).getTime() / 1000 + this.getServerTimezoneOffset() + this.getUserTimezoneOffset();
	        const localTime = new Date().getTime() / 1000 + this.getServerTimezoneOffset() + this.getUserTimezoneOffset();
	        this._messageDateNode.innerHTML = this.formatTime(remoteDate, localTime, true);
	      }
	      //endregion

	      //region Message Text
	      let text = BX.prop.getString(message, "text", "");
	      const params = BX.prop.getObject(message, "params", {});
	      if (text === "") {
	        this._messageTextNode.innerHTML = "";
	      } else {
	        if (typeof top.BX.MessengerCommon !== "undefined") {
	          text = top.BX.MessengerCommon.purifyText(text, params);
	        }
	        this._messageTextNode.innerHTML = text;
	      }
	      //endregion

	      //region Unread Message Counter
	      let counter = 0;
	      const userId = this.getUserId();
	      if (userId > 0) {
	        counter = BX.prop.getInteger(BX.prop.getObject(BX.prop.getObject(this._data, "USER_INFOS", {}), userId, null), "counter", 0);
	      }
	      this._messageCounterNode.innerHTML = counter.toString();
	      this._messageCounterNode.style.display = counter > 0 ? "" : "none";
	      //endregion
	    }
	  }, {
	    key: "refreshUsersAnimated",
	    value: function refreshUsersAnimated() {
	      BX.removeClass(this._userWrapper, 'crm-entity-stream-live-im-message-show');
	      BX.addClass(this._userWrapper, 'crm-entity-stream-live-im-message-hide');
	      window.setTimeout(function () {
	        this.refreshUsers();
	        window.setTimeout(function () {
	          BX.removeClass(this._userWrapper, 'crm-entity-stream-live-im-message-hide');
	          BX.addClass(this._userWrapper, 'crm-entity-stream-live-im-message-show');
	        }.bind(this), 50);
	      }.bind(this), 500);
	    }
	  }, {
	    key: "refreshSummaryAnimated",
	    value: function refreshSummaryAnimated() {
	      BX.removeClass(this._messageWrapper, 'crm-entity-stream-live-im-message-show');
	      BX.addClass(this._messageWrapper, 'crm-entity-stream-live-im-message-hide');
	      window.setTimeout(function () {
	        this.refreshSummary();
	        window.setTimeout(function () {
	          BX.removeClass(this._messageWrapper, 'crm-entity-stream-live-im-message-hide');
	          BX.addClass(this._messageWrapper, 'crm-entity-stream-live-im-message-show');
	        }.bind(this), 50);
	      }.bind(this), 500);
	    }
	  }, {
	    key: "onOpenChat",
	    value: function onOpenChat(e) {
	      if (typeof top.BXIM === "undefined") {
	        return;
	      }
	      if (this.isRestricted()) {
	        this.applyLockScript();
	        return;
	      }
	      let slug = "";
	      const chatId = this.getChatId();
	      if (chatId > 0 && this.hasUserInfo(this.getUserId())) {
	        slug = "chat" + chatId.toString();
	      } else {
	        const ownerInfo = this.getOwnerInfo();
	        const entityId = BX.prop.getInteger(ownerInfo, "ENTITY_ID", 0);
	        const entityTypeName = BX.prop.getString(ownerInfo, "ENTITY_TYPE_NAME", "");
	        if (entityTypeName !== "" && entityId > 0) {
	          slug = "crm|" + entityTypeName + "|" + entityId.toString();
	        }
	      }
	      if (slug !== "") {
	        top.BXIM.openMessengerSlider(slug, {
	          RECENT: "N",
	          MENU: "N"
	        });
	      }
	    }
	  }, {
	    key: "onChatEvent",
	    value: function onChatEvent(command, params, extras) {
	      const chatId = this.getChatId();
	      if (chatId <= 0 || chatId !== BX.prop.getInteger(params, "chatId", 0)) {
	        return;
	      }
	      if (command === "chatUserAdd") {
	        this.setUserInfoData(BX.mergeEx(this.getUserInfoData(), BX.prop.getObject(params, "users", {})));
	        this.refreshUsersAnimated();
	      } else if (command === "chatUserLeave") {
	        this.removeUserInfo(BX.prop.getInteger(params, "userId", 0));
	        this.refreshUsersAnimated();
	      } else if (command === "messageChat") {
	        //Message was added.
	        this.setMessageData(BX.prop.getObject(params, "message", {}));
	        this.setUnreadMessageCounter(this.getUserId(), BX.prop.getInteger(params, "counter", 0));
	        this.refreshSummaryAnimated();
	      } else if (command === "messageUpdate" || command === "messageDelete") {
	        //Message was modified or removed.
	        if (command === "messageDelete") {
	          //HACK: date is not in ISO format
	          delete params["date"];
	        }
	        const message = this.getMessageData();
	        if (BX.prop.getInteger(message, "id", 0) === BX.prop.getInteger(params, "id", 0)) {
	          this.setMessageData(BX.mergeEx(message, params));
	          this.refreshSummaryAnimated();
	        }
	      } else if (command === "readMessageChat") {
	        this.setUnreadMessageCounter(this.getUserId(), 0);
	        this.refreshSummaryAnimated();
	      } else if (command === "unreadMessageChat") {
	        this.setUnreadMessageCounter(this.getUserId(), BX.prop.getInteger(params, "counter", 0));
	        this.refreshSummaryAnimated();
	      }
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      return BX.prop.getString(EntityChat.messages, name, name);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new EntityChat();
	      self.initialize(id, settings);
	      EntityChat.items[self.getId()] = self;
	      return self;
	    }
	  }]);
	  return EntityChat;
	}(Steam);
	babelHelpers.defineProperty(EntityChat, "LayoutType", {
	  none: 0,
	  invitation: 1,
	  summary: 2
	});
	babelHelpers.defineProperty(EntityChat, "items", {});
	babelHelpers.defineProperty(EntityChat, "messages", {});

	/** @memberof BX.Crm.Timeline.Tools */
	let AudioPlaybackRateSelector = /*#__PURE__*/function () {
	  function AudioPlaybackRateSelector(params) {
	    babelHelpers.classCallCheck(this, AudioPlaybackRateSelector);
	    this.name = params.name || 'crm-timeline-audio-playback-rate-selector';
	    this.menuId = this.name + '-menu';
	    if (BX.Type.isArray(params.availableRates)) {
	      this.availableRates = params.availableRates;
	    } else {
	      this.availableRates = [1, 1.5, 2, 3];
	    }
	    this.currentRate = this.normalizeRate(params.currentRate);
	    this.textMessageCode = params.textMessageCode;
	    this.renderedItems = [];
	    this.players = [];
	  }
	  babelHelpers.createClass(AudioPlaybackRateSelector, [{
	    key: "isRateCurrent",
	    value: function isRateCurrent(rateDescription, rate) {
	      return rateDescription.rate && rate === rateDescription.rate || rate === rateDescription;
	    }
	  }, {
	    key: "normalizeRate",
	    value: function normalizeRate(rate) {
	      rate = parseFloat(rate);
	      let i = 0;
	      const length = this.availableRates.length;
	      for (; i < length; i++) {
	        if (this.isRateCurrent(this.availableRates[i], rate)) {
	          return rate;
	        }
	      }
	      return this.availableRates[0].rate || this.availableRates[0];
	    }
	  }, {
	    key: "getMenuItems",
	    value: function getMenuItems() {
	      const selectedRate = this.getRate();
	      return this.availableRates.map(function (item) {
	        return {
	          text: (item.text || item) + '',
	          html: (item.html || item) + '',
	          className: this.isRateCurrent(item, selectedRate) ? 'menu-popup-item-text-active' : null,
	          onclick: function () {
	            this.setRate(item.rate || item);
	          }.bind(this)
	        };
	      }.bind(this));
	    }
	  }, {
	    key: "getPopup",
	    value: function getPopup(node) {
	      let popupMenu = BX.Main.MenuManager.getMenuById(this.menuId);
	      if (popupMenu) {
	        const popupWindow = popupMenu.getPopupWindow();
	        if (popupWindow) {
	          popupWindow.setBindElement(node);
	        }
	      } else {
	        popupMenu = BX.Main.MenuManager.create({
	          id: this.menuId,
	          bindElement: node,
	          items: this.getMenuItems(),
	          className: 'crm-audio-cap-speed-popup'
	        });
	      }
	      return popupMenu;
	    }
	  }, {
	    key: "getRate",
	    value: function getRate() {
	      return this.normalizeRate(this.currentRate);
	    }
	  }, {
	    key: "setRate",
	    value: function setRate(rate) {
	      this.getPopup().destroy();
	      rate = this.normalizeRate(rate);
	      if (this.currentRate === rate) {
	        return;
	      }
	      this.currentRate = rate;
	      BX.userOptions.save("crm", this.name, 'rate', rate);
	      for (let i = 0, length = this.renderedItems.length; i < length; i++) {
	        const textNode = this.renderedItems[i].querySelector('.crm-audio-cap-speed-text');
	        if (textNode) {
	          textNode.innerHTML = this.getText();
	        }
	      }
	      for (let i = 0, length = this.players.length; i < length; i++) {
	        this.players[i].vjsPlayer.playbackRate(this.getRate());
	      }
	    }
	  }, {
	    key: "getText",
	    value: function getText() {
	      let text;
	      if (this.textMessageCode) {
	        text = BX.Loc.getMessage(this.textMessageCode);
	      }
	      if (!text) {
	        text = '#RATE#';
	      }
	      return text.replace('#RATE#', '<span>' + this.getRate() + 'x</span>');
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      const item = BX.Dom.create('div', {
	        attrs: {
	          className: 'crm-audio-cap-speed-wrapper'
	        },
	        children: [BX.Dom.create('div', {
	          attrs: {
	            className: 'crm-audio-cap-speed'
	          },
	          children: [BX.Dom.create('div', {
	            attrs: {
	              className: 'crm-audio-cap-speed-text'
	            },
	            html: this.getText()
	          })]
	        })],
	        events: {
	          click: function (event) {
	            event.preventDefault();
	            this.getPopup(event.target).show();
	          }.bind(this)
	        }
	      });
	      this.renderedItems.push(item);
	      return item;
	    }
	  }, {
	    key: "addPlayer",
	    value: function addPlayer(player) {
	      if (BX.Fileman.Player && player instanceof BX.Fileman.Player) {
	        this.players.push(player);
	      }
	    }
	  }]);
	  return AudioPlaybackRateSelector;
	}();

	/** @memberof BX.Crm.Timeline.Tools */
	let SchedulePostponeController = /*#__PURE__*/function () {
	  function SchedulePostponeController() {
	    babelHelpers.classCallCheck(this, SchedulePostponeController);
	    this._item = null;
	  }
	  babelHelpers.createClass(SchedulePostponeController, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._settings = settings ? settings : {};
	      this._item = BX.prop.get(this._settings, "item", null);
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return this.getMessage("title");
	    }
	  }, {
	    key: "getCommandList",
	    value: function getCommandList() {
	      return [{
	        name: "postpone_hour_1",
	        title: this.getMessage("forOneHour")
	      }, {
	        name: "postpone_hour_2",
	        title: this.getMessage("forTwoHours")
	      }, {
	        name: "postpone_hour_3",
	        title: this.getMessage("forThreeHours")
	      }, {
	        name: "postpone_day_1",
	        title: this.getMessage("forOneDay")
	      }, {
	        name: "postpone_day_2",
	        title: this.getMessage("forTwoDays")
	      }, {
	        name: "postpone_day_3",
	        title: this.getMessage("forThreeDays")
	      }];
	    }
	  }, {
	    key: "processCommand",
	    value: function processCommand(command) {
	      if (command.indexOf("postpone") !== 0) {
	        return false;
	      }
	      let offset = 0;
	      if (command === "postpone_hour_1") {
	        offset = 3600;
	      } else if (command === "postpone_hour_2") {
	        offset = 7200;
	      } else if (command === "postpone_hour_3") {
	        offset = 10800;
	      } else if (command === "postpone_day_1") {
	        offset = 86400;
	      } else if (command === "postpone_day_2") {
	        offset = 172800;
	      } else if (command === "postpone_day_3") {
	        offset = 259200;
	      }
	      if (offset > 0 && this._item) {
	        this._item.postpone(offset);
	      }
	      return true;
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = SchedulePostponeController.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new SchedulePostponeController();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return SchedulePostponeController;
	}();
	babelHelpers.defineProperty(SchedulePostponeController, "messages", {});

	/** @memberof BX.Crm.Timeline.Items.Scheduled */
	let Activity = /*#__PURE__*/function (_Scheduled) {
	  babelHelpers.inherits(Activity, _Scheduled);
	  function Activity() {
	    var _this;
	    babelHelpers.classCallCheck(this, Activity);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Activity).call(this));
	    _this._postponeController = null;
	    return _this;
	  }
	  babelHelpers.createClass(Activity, [{
	    key: "getTypeId",
	    value: function getTypeId() {
	      return Item.activity;
	    }
	  }, {
	    key: "isDone",
	    value: function isDone() {
	      const status = BX.prop.getInteger(this.getAssociatedEntityData(), "STATUS");
	      return status === BX.CrmActivityStatus.completed || status === BX.CrmActivityStatus.autoCompleted;
	    }
	  }, {
	    key: "setAsDone",
	    value: function setAsDone(isDone) {
	      isDone = !!isDone;
	      if (this.isDone() === isDone) {
	        return;
	      }
	      const id = BX.prop.getInteger(this.getAssociatedEntityData(), "ID", 0);
	      if (id > 0) {
	        this._activityEditor.setActivityCompleted(id, isDone, BX.delegate(this.onSetAsDoneCompleted, this));
	      }
	    }
	  }, {
	    key: "postpone",
	    value: function postpone(offset) {
	      const id = this.getSourceId();
	      if (id > 0 && offset > 0) {
	        this._activityEditor.postponeActivity(id, offset, BX.delegate(this.onPosponeCompleted, this));
	      }
	    }
	  }, {
	    key: "view",
	    value: function view() {
	      const id = BX.prop.getInteger(this.getAssociatedEntityData(), "ID", 0);
	      if (id > 0) {
	        this._activityEditor.viewActivity(id);
	      }
	    }
	  }, {
	    key: "edit",
	    value: function edit() {
	      this.closeContextMenu();
	      const associatedEntityTypeId = this.getAssociatedEntityTypeId();
	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        const entityData = this.getAssociatedEntityData();
	        const id = BX.prop.getInteger(entityData, "ID", 0);
	        if (id > 0) {
	          this._activityEditor.editActivity(id);
	        }
	      }
	    }
	  }, {
	    key: "processRemoval",
	    value: function processRemoval() {
	      this.closeContextMenu();
	      this._detetionConfirmDlgId = "entity_timeline_deletion_" + this.getId() + "_confirm";
	      let dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);
	      if (!dlg) {
	        dlg = BX.Crm.ConfirmationDialog.create(this._detetionConfirmDlgId, {
	          title: this.getMessage("removeConfirmTitle"),
	          content: this.getRemoveMessage()
	        });
	      }
	      dlg.open().then(BX.delegate(this.onRemovalConfirm, this), BX.delegate(this.onRemovalCancel, this));
	    }
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      return this.getMessage('removeConfirm');
	    }
	  }, {
	    key: "onRemovalConfirm",
	    value: function onRemovalConfirm(result) {
	      if (BX.prop.getBoolean(result, "cancel", true)) {
	        return;
	      }
	      this.remove();
	    }
	  }, {
	    key: "onRemovalCancel",
	    value: function onRemovalCancel() {}
	  }, {
	    key: "remove",
	    value: function remove() {
	      const associatedEntityTypeId = this.getAssociatedEntityTypeId();
	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        const entityData = this.getAssociatedEntityData();
	        const id = BX.prop.getInteger(entityData, "ID", 0);
	        if (id > 0) {
	          const activityEditor = this._activityEditor;
	          const item = activityEditor.getItemById(id);
	          if (item) {
	            activityEditor.deleteActivity(id, true);
	          } else {
	            const activityType = activityEditor.getSetting('ownerType', '');
	            const activityId = activityEditor.getSetting('ownerID', '');
	            const serviceUrl = BX.util.add_url_param(activityEditor.getSetting('serviceUrl', ''), {
	              id: id,
	              action: 'get_activity',
	              ownertype: activityType,
	              ownerid: activityId
	            });
	            BX.ajax({
	              'url': serviceUrl,
	              'method': 'POST',
	              'dataType': 'json',
	              'data': {
	                'ACTION': 'GET_ACTIVITY',
	                'ID': id,
	                'OWNER_TYPE': activityType,
	                'OWNER_ID': activityId
	              },
	              onsuccess: BX.delegate(function (data) {
	                if (typeof data['ACTIVITY'] !== 'undefined') {
	                  activityEditor._handleActivityChange(data['ACTIVITY']);
	                  window.setTimeout(BX.delegate(this.remove, this), 500);
	                }
	              }, this),
	              onfailure: function (data) {}
	            });
	          }
	        }
	      }
	    }
	  }, {
	    key: "getDeadline",
	    value: function getDeadline() {
	      const entityData = this.getAssociatedEntityData();
	      const time = BX.parseDate(entityData["DEADLINE_SERVER"], false, "YYYY-MM-DD", "YYYY-MM-DD HH:MI:SS");
	      if (!time) {
	        return null;
	      }
	      return new crm_timeline_tools.DatetimeConverter(time).toUserTime().getValue();
	    }
	  }, {
	    key: "getLightTime",
	    value: function getLightTime() {
	      const entityData = this.getAssociatedEntityData();
	      const time = BX.parseDate(entityData["LIGHT_TIME_SERVER"], false, "YYYY-MM-DD", "YYYY-MM-DD HH:MI:SS");
	      if (!time) {
	        return null;
	      }
	      return new crm_timeline_tools.DatetimeConverter(time).toUserTime().getValue();
	    }
	  }, {
	    key: "getCreatedDate",
	    value: function getCreatedDate() {
	      const entityData = this.getAssociatedEntityData();
	      const time = BX.parseDate(entityData["CREATED_SERVER"], false, "YYYY-MM-DD", "YYYY-MM-DD HH:MI:SS");
	      if (!time) {
	        return null;
	      }
	      return new crm_timeline_tools.DatetimeConverter(time).toUserTime().getValue();
	    }
	  }, {
	    key: "isIncomingChannel",
	    value: function isIncomingChannel() {
	      if (this.isDone()) {
	        return false;
	      }
	      const entityData = this.getAssociatedEntityData();
	      return entityData.hasOwnProperty('IS_INCOMING_CHANNEL') && entityData.IS_INCOMING_CHANNEL === 'Y';
	    }
	  }, {
	    key: "markAsDone",
	    value: function markAsDone(isDone) {
	      isDone = !!isDone;
	      this.getAssociatedEntityData()["STATUS"] = isDone ? BX.CrmActivityStatus.completed : BX.CrmActivityStatus.waiting;
	    }
	  }, {
	    key: "getPrepositionText",
	    value: function getPrepositionText(direction) {
	      return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "from" : "to");
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription(direction) {
	      return "";
	    }
	  }, {
	    key: "isContextMenuEnabled",
	    value: function isContextMenuEnabled() {
	      return !!this.getDeadline() && this.canPostpone() || this.canComplete();
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent(options) {
	      let timeText = '';
	      const isIncomingChannel = this.isIncomingChannel();
	      if (isIncomingChannel) {
	        timeText = this.formatDateTime(this.getCreatedDate());
	      } else {
	        const deadline = this.getDeadline();
	        timeText = deadline ? this.formatDateTime(deadline) : this.getMessage("termless");
	      }
	      const entityData = this.getAssociatedEntityData();
	      const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	      const isDone = this.isDone();
	      const subject = BX.prop.getString(entityData, "SUBJECT", "");
	      let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      const title = BX.prop.getString(communication, "TITLE", "");
	      const showUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      const communicationValue = BX.prop.getString(communication, "TYPE", "") !== "" ? BX.prop.getString(communication, "VALUE", "") : "";
	      let wrapperClassName = this.getWrapperClassName();
	      if (wrapperClassName !== "") {
	        wrapperClassName = this._schedule.getItemClassName() + " " + wrapperClassName;
	      } else {
	        wrapperClassName = this._schedule.getItemClassName();
	      }
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: wrapperClassName
	        }
	      });
	      let iconClassName = this.getIconClassName();
	      if (this.isCounterEnabled()) {
	        iconClassName += " crm-entity-stream-section-counter";
	      }
	      if (isIncomingChannel) {
	        iconClassName += " crm-entity-stream-section-counter --incoming-counter";
	      }
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: iconClassName
	        }
	      }));

	      //region Context Menu
	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      }
	      //endregion

	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        }
	      });
	      wrapper.appendChild(contentWrapper);

	      //region Details
	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }
	      const contentInnerWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      contentWrapper.appendChild(contentInnerWrapper);
	      this._deadlineNode = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: timeText
	      });
	      const headerWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      headerWrapper.appendChild(BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-title"
	        },
	        text: this.getTypeDescription(direction)
	      }));
	      const statusNode = this.getStatusNode();
	      if (statusNode) {
	        headerWrapper.appendChild(statusNode);
	      }
	      headerWrapper.appendChild(this._deadlineNode);
	      contentInnerWrapper.appendChild(headerWrapper);
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      contentInnerWrapper.appendChild(detailWrapper);
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-title"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          events: {
	            "click": this._headerClickHandler
	          },
	          text: subject
	        })]
	      }));
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        text: this.cutOffText(description, 128)
	      }));
	      const additionalDetails = this.prepareDetailNodes();
	      if (BX.type.isArray(additionalDetails)) {
	        let i = 0;
	        const length = additionalDetails.length;
	        for (; i < length; i++) {
	          detailWrapper.appendChild(additionalDetails[i]);
	        }
	      }
	      const members = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-contact-info"
	        }
	      });
	      if (title !== '') {
	        members.appendChild(BX.create("SPAN", {
	          text: this.getPrepositionText(direction) + ": "
	        }));
	        if (showUrl !== '') {
	          members.appendChild(BX.create("A", {
	            attrs: {
	              href: showUrl
	            },
	            text: title
	          }));
	        } else {
	          members.appendChild(BX.create("SPAN", {
	            text: title
	          }));
	        }
	      }
	      if (communicationValue !== '') {
	        const communicationNode = this.prepareCommunicationNode(communicationValue);
	        if (communicationNode) {
	          members.appendChild(communicationNode);
	        }
	      }
	      detailWrapper.appendChild(members);
	      //endregion
	      //region Set as Done Button
	      const setAsDoneButton = BX.create("INPUT", {
	        attrs: {
	          type: "checkbox",
	          className: "crm-entity-stream-planned-apply-btn",
	          checked: isDone
	        },
	        events: {
	          change: this._setAsDoneButtonHandler
	        }
	      });
	      if (!this.canComplete()) {
	        setAsDoneButton.disabled = true;
	      }
	      const buttonContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-planned-action"
	        },
	        children: [setAsDoneButton]
	      });
	      contentInnerWrapper.appendChild(buttonContainer);
	      //endregion

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentInnerWrapper.appendChild(authorNode);
	      }
	      //endregion

	      //region  Actions
	      this._actionContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentInnerWrapper.appendChild(this._actionContainer);
	      //endregion

	      return wrapper;
	    }
	  }, {
	    key: "getStatusNode",
	    value: function getStatusNode() {
	      return null;
	    }
	  }, {
	    key: "prepareCommunicationNode",
	    value: function prepareCommunicationNode(communicationValue) {
	      return BX.create("SPAN", {
	        text: " " + communicationValue
	      });
	    }
	  }, {
	    key: "prepareDetailNodes",
	    value: function prepareDetailNodes() {
	      return [];
	    }
	  }, {
	    key: "prepareContextMenuItems",
	    value: function prepareContextMenuItems() {
	      const menuItems = [];
	      if (!this.isReadOnly()) {
	        if (this.isEditable()) {
	          menuItems.push({
	            id: "edit",
	            text: this.getMessage("menuEdit"),
	            onclick: BX.delegate(this.edit, this)
	          });
	        }
	        menuItems.push({
	          id: "remove",
	          text: this.getMessage("menuDelete"),
	          onclick: BX.delegate(this.processRemoval, this)
	        });
	      }
	      if (this.canPostpone()) {
	        const handler = BX.delegate(this.onContextMenuItemSelect, this);
	        if (!this._postponeController) {
	          this._postponeController = SchedulePostponeController.create("", {
	            item: this
	          });
	        }
	        const postponeMenu = {
	          id: "postpone",
	          text: this._postponeController.getTitle(),
	          items: []
	        };
	        const commands = this._postponeController.getCommandList();
	        let i = 0;
	        const length = commands.length;
	        for (; i < length; i++) {
	          const command = commands[i];
	          postponeMenu.items.push({
	            id: command["name"],
	            text: command["title"],
	            onclick: handler
	          });
	        }
	        menuItems.push(postponeMenu);
	      }
	      return menuItems;
	    }
	  }, {
	    key: "onContextMenuItemSelect",
	    value: function onContextMenuItemSelect(e, item) {
	      this.closeContextMenu();
	      if (this._postponeController) {
	        this._postponeController.processCommand(item.id);
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Activity();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Activity;
	}(Scheduled);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */
	let Email = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(Email, _Activity);
	  function Email() {
	    babelHelpers.classCallCheck(this, Email);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Email).call(this));
	  }
	  babelHelpers.createClass(Email, [{
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "crm-entity-stream-section-email";
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-email";
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {
	      if (this.isReadOnly()) {
	        return;
	      }
	      this._actions.push(BX.CrmScheduleEmailAction.create("email", {
	        item: this,
	        container: this._actionContainer,
	        entityData: this.getAssociatedEntityData(),
	        activityEditor: this._activityEditor
	      }));
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription(direction) {
	      return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingEmail" : "outgoingEmail");
	    }
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      const entityData = this.getAssociatedEntityData();
	      let title = BX.prop.getString(entityData, "SUBJECT", "");
	      title = BX.util.htmlspecialchars(title);
	      return this.getMessage('emailRemove').replace("#TITLE#", title);
	    }
	  }, {
	    key: "isEditable",
	    value: function isEditable() {
	      return false;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Email();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Email;
	}(Activity);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */
	let Call = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(Call, _Activity);
	  function Call() {
	    babelHelpers.classCallCheck(this, Call);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Call).call(this));
	  }
	  babelHelpers.createClass(Call, [{
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return 'crm-entity-stream-section-call';
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-call";
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {
	      if (this.isReadOnly()) {
	        return;
	      }
	      this._actions.push(BX.CrmScheduleCallAction.create("call", {
	        item: this,
	        container: this._actionContainer,
	        entityData: this.getAssociatedEntityData(),
	        activityEditor: this._activityEditor,
	        ownerInfo: this._schedule.getOwnerInfo()
	      }));
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription(direction) {
	      const entityData = this.getAssociatedEntityData();
	      const callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);
	      const callTypeText = callInfo !== null ? BX.prop.getString(callInfo, "CALL_TYPE_TEXT", "") : "";
	      if (callTypeText !== "") {
	        return callTypeText;
	      }
	      return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall");
	    }
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      const entityData = this.getAssociatedEntityData();
	      const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	      let title = BX.prop.getString(entityData, "SUBJECT", "");
	      const messageName = direction === BX.CrmActivityDirection.incoming ? 'incomingCallRemove' : 'outgoingCallRemove';
	      title = BX.util.htmlspecialchars(title);
	      return this.getMessage(messageName).replace("#TITLE#", title);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Call();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Call;
	}(Activity);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */
	let CallTracker = /*#__PURE__*/function (_Call) {
	  babelHelpers.inherits(CallTracker, _Call);
	  function CallTracker() {
	    babelHelpers.classCallCheck(this, CallTracker);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CallTracker).call(this));
	  }
	  babelHelpers.createClass(CallTracker, [{
	    key: "getStatusNode",
	    value: function getStatusNode() {
	      const entityData = this.getAssociatedEntityData();
	      const callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);
	      if (!callInfo) {
	        return false;
	      }
	      if (!BX.prop.getBoolean(callInfo, "HAS_STATUS", false)) {
	        return false;
	      }
	      const isSuccessfull = BX.prop.getBoolean(callInfo, "SUCCESSFUL", false);
	      const statusText = BX.prop.getString(callInfo, "STATUS_TEXT", "");
	      return BX.create("DIV", {
	        attrs: {
	          className: isSuccessfull ? "crm-entity-stream-content-event-successful" : "crm-entity-stream-content-event-missing"
	        },
	        text: statusText
	      });
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new CallTracker();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return CallTracker;
	}(Call);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */
	let Meeting = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(Meeting, _Activity);
	  function Meeting() {
	    babelHelpers.classCallCheck(this, Meeting);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Meeting).call(this));
	  }
	  babelHelpers.createClass(Meeting, [{
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "";
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-meeting";
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {}
	  }, {
	    key: "getPrepositionText",
	    value: function getPrepositionText() {
	      return this.getMessage("reciprocal");
	    }
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      const entityData = this.getAssociatedEntityData();
	      let title = BX.prop.getString(entityData, "SUBJECT", "");
	      title = BX.util.htmlspecialchars(title);
	      return this.getMessage('meetingRemove').replace("#TITLE#", title);
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription() {
	      return this.getMessage("meeting");
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Meeting();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Meeting;
	}(Activity);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */
	let Task = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(Task, _Activity);
	  function Task() {
	    babelHelpers.classCallCheck(this, Task);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Task).call(this));
	  }
	  babelHelpers.createClass(Task, [{
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "crm-entity-stream-section-planned-task";
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-task";
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription() {
	      return this.getMessage("task");
	    }
	  }, {
	    key: "getPrepositionText",
	    value: function getPrepositionText(direction) {
	      return this.getMessage("reciprocal");
	    }
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      const entityData = this.getAssociatedEntityData();
	      let title = BX.prop.getString(entityData, "SUBJECT", "");
	      title = BX.util.htmlspecialchars(title);
	      return this.getMessage('taskRemove').replace("#TITLE#", title);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Task();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Task;
	}(Activity);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */
	let WebForm = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(WebForm, _Activity);
	  function WebForm() {
	    babelHelpers.classCallCheck(this, WebForm);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(WebForm).call(this));
	  }
	  babelHelpers.createClass(WebForm, [{
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "";
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-crmForm";
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {}
	  }, {
	    key: "getPrepositionText",
	    value: function getPrepositionText() {
	      return this.getMessage("from");
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription() {
	      return this.getMessage("webform");
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new WebForm();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return WebForm;
	}(Activity);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */
	let Wait = /*#__PURE__*/function (_Scheduled) {
	  babelHelpers.inherits(Wait, _Scheduled);
	  function Wait() {
	    var _this;
	    babelHelpers.classCallCheck(this, Wait);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Wait).call(this));
	    _this._postponeController = null;
	    return _this;
	  }
	  babelHelpers.createClass(Wait, [{
	    key: "getTypeId",
	    value: function getTypeId() {
	      return Item.wait;
	    }
	  }, {
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "crm-entity-stream-section-wait";
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-wait";
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {}
	  }, {
	    key: "isCounterEnabled",
	    value: function isCounterEnabled() {
	      return false;
	    }
	  }, {
	    key: "getDeadline",
	    value: function getDeadline() {
	      const entityData = this.getAssociatedEntityData();
	      const time = BX.parseDate(entityData["DEADLINE_SERVER"], false, "YYYY-MM-DD", "YYYY-MM-DD HH:MI:SS");
	      if (!time) {
	        return null;
	      }
	      return new crm_timeline_tools.DatetimeConverter(time).toUserTime().getValue();
	    }
	  }, {
	    key: "isDone",
	    value: function isDone() {
	      return BX.prop.getString(this.getAssociatedEntityData(), "COMPLETED", "N") === "Y";
	    }
	  }, {
	    key: "setAsDone",
	    value: function setAsDone(isDone) {
	      isDone = !!isDone;
	      if (this.isDone() === isDone) {
	        return;
	      }
	      const id = this.getAssociatedEntityId();
	      if (id > 0) {
	        var _BX$Crm$Timeline, _BX$Crm$Timeline$Menu, _BX$Crm$Timeline$Menu2;
	        const editor = (_BX$Crm$Timeline = BX.Crm.Timeline) === null || _BX$Crm$Timeline === void 0 ? void 0 : (_BX$Crm$Timeline$Menu = _BX$Crm$Timeline.MenuBar) === null || _BX$Crm$Timeline$Menu === void 0 ? void 0 : (_BX$Crm$Timeline$Menu2 = _BX$Crm$Timeline$Menu.getDefault()) === null || _BX$Crm$Timeline$Menu2 === void 0 ? void 0 : _BX$Crm$Timeline$Menu2.getItemById('wait');
	        if (editor) {
	          editor.complete(id, isDone, BX.delegate(this.onSetAsDoneCompleted, this));
	        }
	      }
	    }
	  }, {
	    key: "postpone",
	    value: function postpone(offset) {
	      const id = this.getAssociatedEntityId();
	      if (id > 0 && offset > 0) {
	        var _BX$Crm$Timeline2, _BX$Crm$Timeline2$Men, _BX$Crm$Timeline2$Men2;
	        const editor = (_BX$Crm$Timeline2 = BX.Crm.Timeline) === null || _BX$Crm$Timeline2 === void 0 ? void 0 : (_BX$Crm$Timeline2$Men = _BX$Crm$Timeline2.MenuBar) === null || _BX$Crm$Timeline2$Men === void 0 ? void 0 : (_BX$Crm$Timeline2$Men2 = _BX$Crm$Timeline2$Men.getDefault()) === null || _BX$Crm$Timeline2$Men2 === void 0 ? void 0 : _BX$Crm$Timeline2$Men2.getItemById('wait');
	        if (editor) {
	          editor.postpone(id, offset, BX.delegate(this.onPosponeCompleted, this));
	        }
	      }
	    }
	  }, {
	    key: "isContextMenuEnabled",
	    value: function isContextMenuEnabled() {
	      return !!this.getDeadline() && this.canPostpone();
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const deadline = this.getDeadline();
	      const timeText = deadline ? this.formatDateTime(deadline) : this.getMessage("termless");
	      const entityData = this.getAssociatedEntityData();
	      const isDone = this.isDone();
	      let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      let wrapperClassName = this.getWrapperClassName();
	      if (wrapperClassName !== "") {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned" + " " + wrapperClassName;
	      } else {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned";
	      }
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: wrapperClassName
	        }
	      });
	      let iconClassName = this.getIconClassName();
	      if (this.isCounterEnabled()) {
	        iconClassName += " crm-entity-stream-section-counter";
	      }
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: iconClassName
	        }
	      }));

	      //region Context Menu
	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      }
	      //endregion

	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        }
	      });
	      wrapper.appendChild(contentWrapper);

	      //region Details
	      if (description !== "") {
	        description = BX.util.trim(description);
	        description = BX.util.strip_tags(description);
	        description = this.cutOffText(description, 512);
	        description = BX.util.nl2br(description);
	      }
	      const contentInnerWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      contentWrapper.appendChild(contentInnerWrapper);
	      this._deadlineNode = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: timeText
	      });
	      const headerWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: [BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-event-title"
	          },
	          text: this.getMessage("wait")
	        }), this._deadlineNode]
	      });
	      contentInnerWrapper.appendChild(headerWrapper);
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      contentInnerWrapper.appendChild(detailWrapper);
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        html: description
	      }));
	      const members = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-contact-info"
	        }
	      });
	      detailWrapper.appendChild(members);
	      //endregion

	      //region Set as Done Button
	      const setAsDoneButton = BX.create("INPUT", {
	        attrs: {
	          type: "checkbox",
	          className: "crm-entity-stream-planned-apply-btn",
	          checked: isDone
	        },
	        events: {
	          change: this._setAsDoneButtonHandler
	        }
	      });
	      if (!this.canComplete()) {
	        setAsDoneButton.disabled = true;
	      }
	      const buttonContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-planned-action"
	        },
	        children: [setAsDoneButton]
	      });
	      contentInnerWrapper.appendChild(buttonContainer);
	      //endregion

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentInnerWrapper.appendChild(authorNode);
	      }
	      //endregion

	      //region  Actions
	      this._actionContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentInnerWrapper.appendChild(this._actionContainer);
	      //endregion

	      return wrapper;
	    }
	  }, {
	    key: "prepareContextMenuItems",
	    value: function prepareContextMenuItems() {
	      const menuItems = [];
	      const handler = BX.delegate(this.onContextMenuItemSelect, this);
	      if (!this._postponeController) {
	        this._postponeController = SchedulePostponeController.create("", {
	          item: this
	        });
	      }
	      const postponeMenu = {
	        id: "postpone",
	        text: this._postponeController.getTitle(),
	        items: []
	      };
	      const commands = this._postponeController.getCommandList();
	      let i = 0;
	      const length = commands.length;
	      for (; i < length; i++) {
	        const command = commands[i];
	        postponeMenu.items.push({
	          id: command["name"],
	          text: command["title"],
	          onclick: handler
	        });
	      }
	      menuItems.push(postponeMenu);
	      return menuItems;
	    }
	  }, {
	    key: "onContextMenuItemSelect",
	    value: function onContextMenuItemSelect(e, item) {
	      this.closeContextMenu();
	      if (this._postponeController) {
	        this._postponeController.processCommand(item.id);
	      }
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription() {
	      return this.getMessage("wait");
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Wait();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Wait;
	}(Scheduled);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */
	let Request = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(Request, _Activity);
	  function Request() {
	    babelHelpers.classCallCheck(this, Request);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Request).call(this));
	  }
	  babelHelpers.createClass(Request, [{
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "";
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-robot";
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription() {
	      return this.getMessage("activityRequest");
	    }
	  }, {
	    key: "isEditable",
	    value: function isEditable() {
	      return false;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Request();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Request;
	}(Activity);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */
	let Rest = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(Rest, _Activity);
	  function Rest() {
	    babelHelpers.classCallCheck(this, Rest);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Rest).call(this));
	  }
	  babelHelpers.createClass(Rest, [{
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "";
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-rest";
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent(options) {
	      const wrapper = babelHelpers.get(babelHelpers.getPrototypeOf(Rest.prototype), "prepareContent", this).call(this, options);
	      const data = this.getAssociatedEntityData();
	      if (data['APP_TYPE'] && data['APP_TYPE']['ICON_SRC']) {
	        const iconNode = wrapper.querySelector('.' + this.getIconClassName().replace(/\s+/g, '.'));
	        if (iconNode) {
	          iconNode.style.backgroundImage = "url('" + data['APP_TYPE']['ICON_SRC'] + "')";
	          iconNode.style.backgroundPosition = "center center";
	          iconNode.style.backgroundSize = "cover";
	          iconNode.style.backgroundColor = "transparent";
	        }
	      }
	      return wrapper;
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription() {
	      const entityData = this.getAssociatedEntityData();
	      if (entityData['APP_TYPE'] && entityData['APP_TYPE']['NAME']) {
	        return entityData['APP_TYPE']['NAME'];
	      }
	      return this.getMessage("restApplication");
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Rest();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Rest;
	}(Activity);

	/** @memberof BX.Crm.Timeline */
	let Action = /*#__PURE__*/function () {
	  function Action() {
	    babelHelpers.classCallCheck(this, Action);
	    this._id = "";
	    this._settings = {};
	    this._container = null;
	  }
	  babelHelpers.createClass(Action, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._settings = settings ? settings : {};
	      this._container = this.getSetting("container");
	      if (!BX.type.isElementNode(this._container)) {
	        throw "BX.CrmTimelineAction: Could not find container.";
	      }
	      this.doInitialize();
	    }
	  }, {
	    key: "doInitialize",
	    value: function doInitialize() {}
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this._id;
	    }
	  }, {
	    key: "getSetting",
	    value: function getSetting(name, defaultval) {
	      return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	    }
	  }, {
	    key: "layout",
	    value: function layout() {
	      this.doLayout();
	    }
	  }, {
	    key: "doLayout",
	    value: function doLayout() {}
	  }]);
	  return Action;
	}();

	/** @memberof BX.Crm.Timeline.Actions */
	let Activity$1 = /*#__PURE__*/function (_Action) {
	  babelHelpers.inherits(Activity, _Action);
	  function Activity() {
	    var _this;
	    babelHelpers.classCallCheck(this, Activity);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Activity).call(this));
	    _this._activityEditor = null;
	    _this._entityData = null;
	    _this._item = null;
	    _this._isEnabled = true;
	    return _this;
	  }
	  babelHelpers.createClass(Activity, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      this._entityData = this.getSetting("entityData");
	      if (!BX.type.isPlainObject(this._entityData)) {
	        throw "BX.Crm.Timeline.Actions.Activity. A required parameter 'entityData' is missing.";
	      }
	      this._activityEditor = this.getSetting("activityEditor");
	      if (!(this._activityEditor instanceof BX.CrmActivityEditor)) {
	        throw "BX.Crm.Timeline.Actions.Activity. A required parameter 'activityEditor' is missing.";
	      }
	      this._item = this.getSetting("item");
	      this._isEnabled = this.getSetting("enabled", true);
	    }
	  }, {
	    key: "getActivityId",
	    value: function getActivityId() {
	      return BX.prop.getInteger(this._entityData, "ID", 0);
	    }
	  }, {
	    key: "loadActivityCommunications",
	    value: function loadActivityCommunications(callback) {
	      this._activityEditor.getActivityCommunications(this.getActivityId(), function (communications) {
	        if (BX.type.isFunction(callback)) {
	          callback(communications);
	        }
	      }, true);
	    }
	  }, {
	    key: "getItemData",
	    value: function getItemData() {
	      return this._item ? this._item.getData() : null;
	    }
	  }]);
	  return Activity;
	}(Action);

	/** @memberof BX.Crm.Timeline.Actions */
	let OpenLine = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(OpenLine, _Activity);
	  function OpenLine() {
	    var _this;
	    babelHelpers.classCallCheck(this, OpenLine);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OpenLine).call(this));
	    _this._clickHandler = BX.delegate(_this.onClick, babelHelpers.assertThisInitialized(_this));
	    _this._button = null;
	    return _this;
	  }
	  babelHelpers.createClass(OpenLine, [{
	    key: "getButton",
	    value: function getButton() {
	      return this._button;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      if (typeof window.top['BXIM'] === 'undefined') {
	        window.alert(this.getMessage("openLineNotSupported"));
	        return;
	      }
	      let slug = "";
	      const communication = BX.prop.getObject(this._entityData, "COMMUNICATION", null);
	      if (communication) {
	        if (BX.prop.getString(communication, "TYPE") === "IM") {
	          slug = BX.prop.getString(communication, "VALUE");
	        }
	      }
	      if (slug !== "") {
	        window.top['BXIM'].openMessengerSlider(slug, {
	          RECENT: 'N',
	          MENU: 'N'
	        });
	      }
	    }
	  }, {
	    key: "doLayout",
	    value: function doLayout() {
	      this._button = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-action-reply-btn"
	        },
	        events: {
	          "click": this._clickHandler
	        }
	      });
	      this._container.appendChild(this._button);
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = OpenLine.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new OpenLine();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return OpenLine;
	}(Activity$1);
	babelHelpers.defineProperty(OpenLine, "messages", {});

	/** @memberof BX.Crm.Timeline.Items.Scheduled */
	let OpenLine$1 = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(OpenLine$$1, _Activity);
	  function OpenLine$$1() {
	    babelHelpers.classCallCheck(this, OpenLine$$1);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OpenLine$$1).call(this));
	  }
	  babelHelpers.createClass(OpenLine$$1, [{
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "crm-entity-stream-section-IM";
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-IM";
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {
	      if (this.isReadOnly()) {
	        return;
	      }
	      this._actions.push(OpenLine.create("openline", {
	        item: this,
	        container: this._actionContainer,
	        entityData: this.getAssociatedEntityData(),
	        activityEditor: this._activityEditor,
	        ownerInfo: this._schedule.getOwnerInfo()
	      }));
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription() {
	      return this.getMessage("openLine");
	    }
	  }, {
	    key: "getPrepositionText",
	    value: function getPrepositionText(direction) {
	      return this.getMessage("reciprocal");
	    }
	  }, {
	    key: "prepareCommunicationNode",
	    value: function prepareCommunicationNode(communicationValue) {
	      return null;
	    }
	  }, {
	    key: "prepareDetailNodes",
	    value: function prepareDetailNodes() {
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-IM"
	        }
	      });
	      const messageWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-IM-messages"
	        }
	      });
	      wrapper.appendChild(messageWrapper);
	      const openLineData = BX.prop.getObject(this.getAssociatedEntityData(), "OPENLINE_INFO", null);
	      if (openLineData) {
	        const messages = BX.prop.getArray(openLineData, "MESSAGES", []);
	        let i = 0;
	        const length = messages.length;
	        for (; i < length; i++) {
	          const message = messages[i];
	          const isExternal = BX.prop.getBoolean(message, "IS_EXTERNAL", true);
	          messageWrapper.appendChild(BX.create("DIV", {
	            attrs: {
	              className: isExternal ? "crm-entity-stream-content-detail-IM-message-incoming" : "crm-entity-stream-content-detail-IM-message-outgoing"
	            },
	            html: BX.prop.getString(message, "MESSAGE", "")
	          }));
	        }
	      }
	      return [wrapper];
	    }
	  }, {
	    key: "view",
	    value: function view() {
	      if (typeof window.top['BXIM'] === 'undefined') {
	        window.alert(this.getMessage("openLineNotSupported"));
	        return;
	      }
	      let slug = "";
	      const communication = BX.prop.getObject(this.getAssociatedEntityData(), "COMMUNICATION", null);
	      if (communication) {
	        if (BX.prop.getString(communication, "TYPE") === "IM") {
	          slug = BX.prop.getString(communication, "VALUE");
	        }
	      }
	      if (slug !== "") {
	        window.top['BXIM'].openMessengerSlider(slug, {
	          RECENT: 'N',
	          MENU: 'N'
	        });
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new OpenLine$$1();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return OpenLine$$1;
	}(Activity);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */
	let Zoom = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(Zoom, _Activity);
	  function Zoom() {
	    babelHelpers.classCallCheck(this, Zoom);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Zoom).call(this));
	  }
	  babelHelpers.createClass(Zoom, [{
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "crm-entity-stream-section-zoom";
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-zoom";
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription() {
	      return this.getMessage("zoom");
	    }
	  }, {
	    key: "getPrepositionText",
	    value: function getPrepositionText(direction) {}
	  }, {
	    key: "prepareCommunicationNode",
	    value: function prepareCommunicationNode(communicationValue) {
	      return null;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent(options) {
	      const deadline = this.getDeadline();
	      const timeText = deadline ? this.formatDateTime(deadline) : this.getMessage("termless");
	      const entityData = this.getAssociatedEntityData();
	      const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	      const isDone = this.isDone();
	      const subject = BX.prop.getString(entityData, "SUBJECT", "");
	      let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      const title = BX.prop.getString(communication, "TITLE", "");
	      const showUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      const communicationValue = BX.prop.getString(communication, "TYPE", "") !== "" ? BX.prop.getString(communication, "VALUE", "") : "";
	      let wrapperClassName = this.getWrapperClassName();
	      if (wrapperClassName !== "") {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned" + " " + wrapperClassName;
	      } else {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned";
	      }
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: wrapperClassName
	        }
	      });
	      let iconClassName = this.getIconClassName();
	      if (this.isCounterEnabled()) {
	        iconClassName += " crm-entity-stream-section-counter";
	      }
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: iconClassName
	        }
	      }));

	      //region Context Menu
	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      }
	      //endregion

	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        }
	      });
	      wrapper.appendChild(contentWrapper);

	      //region Details
	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }
	      const contentInnerWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      contentWrapper.appendChild(contentInnerWrapper);
	      this._deadlineNode = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: timeText
	      });
	      const headerWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: [BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-event-title"
	          },
	          text: this.getTypeDescription(direction)
	        }), this._deadlineNode]
	      });
	      contentInnerWrapper.appendChild(headerWrapper);
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      contentInnerWrapper.appendChild(detailWrapper);
	      if (entityData['ZOOM_INFO']) {
	        const topic = entityData['ZOOM_INFO']['TOPIC'];
	        const duration = entityData['ZOOM_INFO']['DURATION'];
	        const startTime = BX.parseDate(entityData['ZOOM_INFO']['CONF_START_TIME'], false, "YYYY-MM-DD", "YYYY-MM-DD HH:MI:SS");
	        const date = new crm_timeline_tools.DatetimeConverter(startTime).toUserTime().toDatetimeString({
	          delimiter: ', '
	        });
	        const detailZoomMessage = BX.create("span", {
	          text: this.getMessage("zoomCreatedMessage").replace("#CONFERENCE_TITLE#", topic).replace("#DATE_TIME#", date).replace("#DURATION#", duration)
	        });
	        const detailZoomInfoLink = BX.create("A", {
	          attrs: {
	            href: entityData['ZOOM_INFO']['CONF_URL'],
	            target: "_blank"
	          },
	          text: entityData['ZOOM_INFO']['CONF_URL']
	        });
	        const detailZoomInfo = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-zoom-info"
	          },
	          children: [detailZoomMessage, detailZoomInfoLink]
	        });
	        detailWrapper.appendChild(detailZoomInfo);
	        const detailZoomCopyInviteLink = BX.create("A", {
	          attrs: {
	            className: 'ui-link ui-link-dashed',
	            "data-url": entityData['ZOOM_INFO']['CONF_URL']
	          },
	          text: this.getMessage("zoomCreatedCopyInviteLink")
	        });
	        BX.clipboard.bindCopyClick(detailZoomCopyInviteLink, {
	          text: entityData['ZOOM_INFO']['CONF_URL']
	        });
	        const detailZoomStartConferenceButton = BX.create("BUTTON", {
	          attrs: {
	            className: 'ui-btn ui-btn-sm ui-btn-primary'
	          },
	          text: this.getMessage("zoomCreatedStartConference"),
	          events: {
	            "click": function () {
	              window.open(entityData['ZOOM_INFO']['CONF_URL']);
	            }
	          }
	        });
	        const detailZoomCopyInviteLinkWrapper = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-zoom-link-wrapper"
	          },
	          children: [detailZoomCopyInviteLink]
	        });
	        detailWrapper.appendChild(detailZoomCopyInviteLinkWrapper);
	        detailWrapper.appendChild(detailZoomStartConferenceButton);
	      }
	      const additionalDetails = this.prepareDetailNodes();
	      if (BX.type.isArray(additionalDetails)) {
	        let i = 0;
	        const length = additionalDetails.length;
	        for (; i < length; i++) {
	          detailWrapper.appendChild(additionalDetails[i]);
	        }
	      }

	      //endregion
	      //region Set as Done Button
	      const setAsDoneButton = BX.create("INPUT", {
	        attrs: {
	          type: "checkbox",
	          className: "crm-entity-stream-planned-apply-btn",
	          checked: isDone
	        },
	        events: {
	          change: this._setAsDoneButtonHandler
	        }
	      });
	      if (!this.canComplete()) {
	        setAsDoneButton.disabled = true;
	      }
	      const buttonContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-planned-action"
	        },
	        children: [setAsDoneButton]
	      });
	      contentInnerWrapper.appendChild(buttonContainer);
	      //endregion

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentInnerWrapper.appendChild(authorNode);
	      }
	      //endregion

	      //region  Actions
	      this._actionContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentInnerWrapper.appendChild(this._actionContainer);
	      //endregion

	      return wrapper;
	    }
	  }, {
	    key: "prepareDetailNodes",
	    value: function prepareDetailNodes() {}
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Zoom();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Zoom;
	}(Activity);

	/** @memberof BX.Crm.Timeline.Streams */
	let Schedule = /*#__PURE__*/function (_Stream) {
	  babelHelpers.inherits(Schedule, _Stream);
	  function Schedule() {
	    var _this;
	    babelHelpers.classCallCheck(this, Schedule);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Schedule).call(this));
	    _this._items = [];
	    _this._history = null;
	    _this._wrapper = null;
	    _this._anchor = null;
	    _this._stub = null;
	    return _this;
	  }
	  babelHelpers.createClass(Schedule, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      if (!this.isStubMode()) {
	        let itemData = this.getSetting("itemData");
	        if (!BX.type.isArray(itemData)) {
	          itemData = [];
	        }
	        let i, length, item;
	        for (i = 0, length = itemData.length; i < length; i++) {
	          item = this.createItem(itemData[i]);
	          if (item) {
	            this._items.push(item);
	          }
	        }
	      }
	    }
	  }, {
	    key: "layout",
	    value: function layout() {
	      this._wrapper = BX.create("DIV", {});
	      this._container.appendChild(this._wrapper);
	      const label = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-planned-label"
	        },
	        text: this.getMessage("planned")
	      });
	      const wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned-label";
	      this._wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: wrapperClassName
	        },
	        children: [BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-content"
	          },
	          children: [label]
	        })]
	      }));
	      if (this.isStubMode()) {
	        this.addStub();
	      } else {
	        const length = this._items.length;
	        if (length === 0) {
	          this.addStub();
	        } else {
	          for (let i = 0; i < length; i++) {
	            const item = this._items[i];
	            item.setContainer(this._wrapper);
	            item.layout();
	          }
	        }
	      }
	      this.refreshLayout();
	      this._manager.processSheduleLayoutChange();
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {
	      BX.onCustomEvent('Schedule:onBeforeRefreshLayout', [this]);
	      const length = this._items.length;
	      if (length === 0) {
	        this.addStub();
	        if (this._history && this._history.hasContent()) {
	          BX.removeClass(this._stub, "crm-entity-stream-section-last");
	        } else {
	          BX.addClass(this._stub, "crm-entity-stream-section-last");
	        }
	        const stubIcon = this._stub.querySelector(".crm-entity-stream-section-icon");
	        if (stubIcon) {
	          if (this._manager.isStubCounterEnabled()) {
	            BX.addClass(stubIcon, "crm-entity-stream-section-counter");
	          } else {
	            BX.removeClass(stubIcon, "crm-entity-stream-section-counter");
	          }
	        }
	        return;
	      }
	      let i, item;
	      if (this._history && this._history.hasContent()) {
	        for (i = 0; i < length; i++) {
	          item = this._items[i];
	          if (item.isTerminated()) {
	            item.markAsTerminated(false);
	          }
	        }
	      } else {
	        if (length > 1) {
	          for (i = 0; i < length - 1; i++) {
	            item = this._items[i];
	            if (item.isTerminated()) {
	              item.markAsTerminated(false);
	            }
	          }
	        }
	        this._items[length - 1].markAsTerminated(true);
	      }
	    }
	  }, {
	    key: "formatDateTime",
	    value: function formatDateTime(time) {
	      return new crm_timeline_tools.DatetimeConverter(time).toDatetimeString({
	        delimiter: ', '
	      });
	    }
	  }, {
	    key: "checkItemForTermination",
	    value: function checkItemForTermination(item) {
	      if (this._history && this._history.getItemCount() > 0) {
	        return false;
	      }
	      return this.getLastItem() === item;
	    }
	  }, {
	    key: "getItems",
	    value: function getItems() {
	      return this._items;
	    }
	  }, {
	    key: "setItems",
	    value: function setItems(items) {
	      this._items = items;
	    }
	  }, {
	    key: "calculateItemIndex",
	    value: function calculateItemIndex(item) {
	      const sort = item.getSort();
	      for (let i = 0; i < this._items.length; i++) {
	        const curSort = this._items[i].getSort();
	        for (let j = 0; j < curSort.length; j++) {
	          if (sort.length <= j || sort[j] !== curSort[j]) {
	            if (sort[j] < curSort[j]) {
	              return i;
	            }
	            break;
	          }
	        }
	      }
	      return this._items.length;
	    }
	  }, {
	    key: "getItemCount",
	    value: function getItemCount() {
	      return this._items.length;
	    }
	  }, {
	    key: "getItemByAssociatedEntity",
	    value: function getItemByAssociatedEntity($entityTypeId, entityId) {
	      if (!BX.type.isNumber($entityTypeId)) {
	        $entityTypeId = parseInt($entityTypeId);
	      }
	      if (!BX.type.isNumber(entityId)) {
	        entityId = parseInt(entityId);
	      }
	      if (isNaN($entityTypeId) || $entityTypeId <= 0 || isNaN(entityId) || entityId <= 0) {
	        return null;
	      }
	      for (let i = 0, length = this._items.length; i < length; i++) {
	        const item = this._items[i];
	        if (item.getAssociatedEntityTypeId() === $entityTypeId && item.getAssociatedEntityId() === entityId) {
	          return item;
	        }
	      }
	      return null;
	    }
	  }, {
	    key: "getItemByData",
	    value: function getItemByData(itemData) {
	      if (!BX.type.isPlainObject(itemData)) {
	        return null;
	      }
	      return this.getItemByAssociatedEntity(BX.prop.getInteger(itemData, "ASSOCIATED_ENTITY_TYPE_ID", 0), BX.prop.getInteger(itemData, "ASSOCIATED_ENTITY_ID", 0));
	    }
	  }, {
	    key: "getItemByIndex",
	    value: function getItemByIndex(index) {
	      return index < this._items.length ? this._items[index] : null;
	    }
	  }, {
	    key: "createItem",
	    value: function createItem(data) {
	      const entityTypeID = BX.prop.getInteger(data, "ASSOCIATED_ENTITY_TYPE_ID", 0);
	      const entityID = BX.prop.getInteger(data, "ASSOCIATED_ENTITY_ID", 0);
	      const entityData = BX.prop.getObject(data, "ASSOCIATED_ENTITY", {});
	      let itemId = BX.CrmEntityType.resolveName(entityTypeID) + "_" + entityID.toString();
	      if (data.hasOwnProperty('type')) {
	        itemId = data.id;
	        return crm_timeline_item.ConfigurableItem.create(itemId, {
	          timelineId: this.getId(),
	          container: this.getWrapper(),
	          itemClassName: this.getItemClassName(),
	          isReadOnly: this.isReadOnly(),
	          currentUser: this._manager.getCurrentUser(),
	          ownerTypeId: this._manager.getOwnerTypeId(),
	          ownerId: this._manager.getOwnerId(),
	          streamType: this.getStreamType(),
	          data: data
	        });
	      }
	      if (entityTypeID === BX.CrmEntityType.enumeration.wait) {
	        return Wait.create(itemId, {
	          schedule: this,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else
	        // if(entityTypeID === BX.CrmEntityType.enumeration.activity)
	        {
	          const typeId = BX.prop.getInteger(entityData, "TYPE_ID", 0);
	          const providerId = BX.prop.getString(entityData, "PROVIDER_ID", "");
	          if (typeId === BX.CrmActivityType.email) {
	            return Email.create(itemId, {
	              schedule: this,
	              container: this._wrapper,
	              activityEditor: this._activityEditor,
	              data: data
	            });
	          } else if (typeId === BX.CrmActivityType.call) {
	            return Call.create(itemId, {
	              schedule: this,
	              container: this._wrapper,
	              activityEditor: this._activityEditor,
	              data: data
	            });
	          } else if (typeId === BX.CrmActivityType.meeting) {
	            return Meeting.create(itemId, {
	              schedule: this,
	              container: this._wrapper,
	              activityEditor: this._activityEditor,
	              data: data
	            });
	          } else if (typeId === BX.CrmActivityType.task) {
	            return Task.create(itemId, {
	              schedule: this,
	              container: this._wrapper,
	              activityEditor: this._activityEditor,
	              data: data
	            });
	          } else if (typeId === BX.CrmActivityType.provider) {
	            if (providerId === "CRM_WEBFORM") {
	              return WebForm.create(itemId, {
	                schedule: this,
	                container: this._wrapper,
	                activityEditor: this._activityEditor,
	                data: data
	              });
	            } else if (providerId === "CRM_REQUEST") {
	              return Request.create(itemId, {
	                schedule: this,
	                container: this._wrapper,
	                activityEditor: this._activityEditor,
	                data: data
	              });
	            } else if (providerId === "IMOPENLINES_SESSION") {
	              return OpenLine$1.create(itemId, {
	                schedule: this,
	                container: this._wrapper,
	                activityEditor: this._activityEditor,
	                data: data
	              });
	            } else if (providerId === "ZOOM") {
	              return Zoom.create(itemId, {
	                schedule: this,
	                container: this._wrapper,
	                activityEditor: this._activityEditor,
	                data: data
	              });
	            } else if (providerId === "REST_APP") {
	              return Rest.create(itemId, {
	                schedule: this,
	                container: this._wrapper,
	                activityEditor: this._activityEditor,
	                data: data
	              });
	            } else if (providerId === 'CRM_CALL_TRACKER') {
	              return CallTracker.create(itemId, {
	                schedule: this,
	                container: this._wrapper,
	                activityEditor: this._activityEditor,
	                data: data
	              });
	            }
	          }
	        }
	      return null;
	    }
	  }, {
	    key: "getWrapper",
	    value: function getWrapper() {
	      return this._wrapper;
	    }
	  }, {
	    key: "getItemClassName",
	    value: function getItemClassName() {
	      return 'crm-entity-stream-section crm-entity-stream-section-planned';
	    }
	  }, {
	    key: "getStreamType",
	    value: function getStreamType() {
	      return crm_timeline_item.StreamType.scheduled;
	    }
	  }, {
	    key: "addItem",
	    value: function addItem(item, index) {
	      if (!BX.type.isNumber(index) || index < 0) {
	        index = this.calculateItemIndex(item);
	      }
	      if (index < this._items.length) {
	        this._items.splice(index, 0, item);
	      } else {
	        this._items.push(item);
	      }
	      this.removeStub();
	      this.refreshLayout();
	      this._manager.processSheduleLayoutChange();
	    }
	  }, {
	    key: "getHistory",
	    value: function getHistory() {
	      return this._history;
	    }
	  }, {
	    key: "setHistory",
	    value: function setHistory(history) {
	      this._history = history;
	    }
	  }, {
	    key: "createAnchor",
	    value: function createAnchor(index) {
	      this._anchor = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-shadow"
	        }
	      });
	      if (index >= 0 && index < this._items.length) {
	        this._wrapper.insertBefore(this._anchor, this._items[index].getWrapper());
	      } else {
	        this._wrapper.appendChild(this._anchor);
	      }
	      return this._anchor;
	    }
	  }, {
	    key: "deleteItem",
	    value: function deleteItem(item) {
	      const index = this.getItemIndex(item);
	      if (index < 0) {
	        return;
	      }
	      item.clearLayout();
	      this.removeItemByIndex(index);
	      this.refreshLayout();
	      this._manager.processSheduleLayoutChange();
	    }
	  }, {
	    key: "transferItemToHistory",
	    value: function transferItemToHistory(item, historyItemData) {
	      const index = this.getItemIndex(item);
	      if (index < 0) {
	        return;
	      }
	      this.removeItemByIndex(index);
	      this.refreshLayout();
	      this._manager.processSheduleLayoutChange();
	      const historyItem = this._history.createItem(historyItemData);
	      this._history.addItem(historyItem, 0);
	      historyItem.layout({
	        add: false
	      });
	      const animation = ItemNew.create("", {
	        initialItem: item,
	        finalItem: historyItem,
	        anchor: this._history.createAnchor(),
	        events: {
	          complete: BX.delegate(this.onTransferComplete, this)
	        }
	      });
	      animation.run();
	    }
	  }, {
	    key: "onTransferComplete",
	    value: function onTransferComplete() {
	      this._history.refreshLayout();
	      if (this._items.length === 0) {
	        this.addStub();
	      }
	    }
	  }, {
	    key: "onItemMarkedAsDone",
	    value: function onItemMarkedAsDone(item, params) {}
	  }, {
	    key: "addStub",
	    value: function addStub() {
	      if (!this._stub) {
	        var _BX$Crm$Timeline, _BX$Crm$Timeline$Menu, _BX$Crm$Timeline$Menu2;
	        let stubClassName = "crm-entity-stream-section crm-entity-stream-section-planned crm-entity-stream-section-notTask";
	        let stubIconClassName = "crm-entity-stream-section-icon crm-entity-stream-section-icon-info";
	        const canAddTodo = !!((_BX$Crm$Timeline = BX.Crm.Timeline) !== null && _BX$Crm$Timeline !== void 0 && (_BX$Crm$Timeline$Menu = _BX$Crm$Timeline.MenuBar) !== null && _BX$Crm$Timeline$Menu !== void 0 && (_BX$Crm$Timeline$Menu2 = _BX$Crm$Timeline$Menu.getDefault()) !== null && _BX$Crm$Timeline$Menu2 !== void 0 && _BX$Crm$Timeline$Menu2.getItemById('todo'));
	        if (canAddTodo && !this.isReadOnly()) {
	          stubClassName += ' --active';
	        }
	        let stubMessage = this.getMessage("stub");
	        const ownerTypeId = this._manager.getOwnerTypeId();
	        if (ownerTypeId === BX.CrmEntityType.enumeration.lead) {
	          stubMessage = this.getMessage("leadStub");
	        } else if (ownerTypeId === BX.CrmEntityType.enumeration.deal) {
	          stubMessage = this.getMessage("dealStub");
	        }
	        if (this._manager.isStubCounterEnabled()) {
	          stubIconClassName += " crm-entity-stream-section-counter";
	        }
	        this._stub = BX.create("DIV", {
	          attrs: {
	            className: stubClassName
	          },
	          children: [BX.create("DIV", {
	            attrs: {
	              className: stubIconClassName
	            }
	          }), BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-section-content"
	            },
	            children: [BX.create("DIV", {
	              attrs: {
	                className: "crm-entity-stream-content-event"
	              },
	              children: [BX.create("DIV", {
	                attrs: {
	                  className: "crm-entity-stream-content-title"
	                },
	                text: this.getMessage("stubTitle")
	              }), BX.create("DIV", {
	                attrs: {
	                  className: "crm-entity-stream-content-detail"
	                },
	                text: stubMessage
	              })]
	            })]
	          })]
	        });
	        if (canAddTodo && !this.isReadOnly()) {
	          BX.bind(this._stub, "click", BX.delegate(this.focusOnTodoEditor, this));
	        }
	        this._wrapper.appendChild(this._stub);
	      }
	      if (this._history && this._history.getItemCount() > 0) {
	        BX.removeClass(this._stub, "crm-entity-stream-section-last");
	      } else {
	        BX.addClass(this._stub, "crm-entity-stream-section-last");
	      }
	    }
	  }, {
	    key: "removeStub",
	    value: function removeStub() {
	      if (this._stub) {
	        this._stub = BX.remove(this._stub);
	      }
	    }
	  }, {
	    key: "focusOnTodoEditor",
	    value: function focusOnTodoEditor() {
	      const menuBar = BX.Crm.Timeline.MenuBar.getDefault();
	      if (menuBar) {
	        menuBar.setActiveItemById('todo');
	        const todoEditor = menuBar.getItemById('todo');
	        todoEditor === null || todoEditor === void 0 ? void 0 : todoEditor.focus();
	      }
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = Schedule.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }, {
	    key: "animateItemAdding",
	    value: function animateItemAdding(item) {
	      item.addWrapperClass('crm-entity-stream-section-updated', 1000);
	      return Promise.resolve();
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Schedule();
	      self.initialize(id, settings);
	      Schedule.items[self.getId()] = self;
	      return self;
	    }
	  }]);
	  return Schedule;
	}(Steam);
	babelHelpers.defineProperty(Schedule, "items", {});
	babelHelpers.defineProperty(Schedule, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */
	let Modification = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(Modification, _History);
	  function Modification() {
	    babelHelpers.classCallCheck(this, Modification);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Modification).call(this));
	  }
	  babelHelpers.createClass(Modification, [{
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = Modification.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return this.getTextDataParam("TITLE");
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-info"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info"
	        }
	      }));
	      const content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      const header = this.prepareHeaderLayout();
	      const contentChildren = [];
	      if (BX.type.isNotEmptyString(this.getTextDataParam("START_NAME"))) {
	        contentChildren.push(BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-detain-info-status"
	          },
	          text: this.getTextDataParam("START_NAME")
	        }));
	        contentChildren.push(BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-info-separator-icon"
	          }
	        }));
	      }
	      if (BX.type.isNotEmptyString(this.getTextDataParam("FINISH_NAME"))) {
	        contentChildren.push(BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-detain-info-status"
	          },
	          text: this.getTextDataParam("FINISH_NAME")
	        }));
	      }
	      content.appendChild(header);
	      content.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: [BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-info"
	          },
	          children: contentChildren
	        })]
	      }));

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        content.appendChild(authorNode);
	      }
	      //endregion

	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [content]
	      }));
	      return wrapper;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Modification();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Modification;
	}(History);
	babelHelpers.defineProperty(Modification, "messages", {});

	/** @memberof BX.Crm.Timeline.Actions */
	let Conversion = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(Conversion, _History);
	  function Conversion() {
	    babelHelpers.classCallCheck(this, Conversion);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Conversion).call(this));
	  }
	  babelHelpers.createClass(Conversion, [{
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = Conversion.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return this.getTextDataParam("TITLE");
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-convert crm-entity-stream-section-history"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-convert"
	        }
	      }));
	      const content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      const header = this.prepareHeaderLayout();
	      content.appendChild(header);
	      const entityNodes = [];
	      const entityInfos = this.getArrayDataParam("ENTITIES");
	      let i = 0;
	      const length = entityInfos.length;
	      for (; i < length; i++) {
	        const entityInfo = entityInfos[i];
	        let entityNode;
	        if (BX.prop.getString(entityInfo, 'SHOW_URL', "") === "") {
	          entityNode = BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-content-detail-convert"
	            },
	            children: [BX.create("DIV", {
	              attrs: {
	                className: "crm-entity-stream-content-detain-convert-status"
	              },
	              children: [BX.create("SPAN", {
	                attrs: {
	                  className: "crm-entity-stream-content-detail-status-text"
	                },
	                text: BX.CrmEntityType.getNotFoundMessage(entityInfo['ENTITY_TYPE_ID'])
	              })]
	            })]
	          });
	        } else {
	          entityNode = BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-content-detail-convert"
	            },
	            children: [BX.create("DIV", {
	              attrs: {
	                className: "crm-entity-stream-content-detain-convert-status"
	              },
	              children: [BX.create("SPAN", {
	                attrs: {
	                  className: "crm-entity-stream-content-detail-status-text"
	                },
	                text: entityInfo['ENTITY_TYPE_CAPTION']
	              })]
	            }), BX.create("SPAN", {
	              attrs: {
	                className: "crm-entity-stream-content-detail-convert-separator-icon"
	              }
	            }), BX.create("DIV", {
	              attrs: {
	                className: "crm-entity-stream-content-detain-convert-status"
	              },
	              children: [BX.create("A", {
	                attrs: {
	                  className: "crm-entity-stream-content-detail-target",
	                  href: entityInfo['SHOW_URL']
	                },
	                text: entityInfo['TITLE']
	              })]
	            })]
	          });
	        }
	        entityNodes.push(entityNode);
	      }
	      content.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: entityNodes
	      }));

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        content.appendChild(authorNode);
	      }
	      //endregion

	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [content]
	      }));
	      return wrapper;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Conversion();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Conversion;
	}(History);
	babelHelpers.defineProperty(Conversion, "messages", {});

	/** @memberof BX.Crm.Timeline.Actions */
	let Email$1 = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(Email, _Activity);
	  function Email() {
	    var _this;
	    babelHelpers.classCallCheck(this, Email);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Email).call(this));
	    _this._clickHandler = BX.delegate(_this.onClick, babelHelpers.assertThisInitialized(_this));
	    _this._saveHandler = BX.delegate(_this.onSave, babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(Email, [{
	    key: "onClick",
	    value: function onClick(e) {
	      const settings = {
	        "ownerType": BX.CrmEntityType.resolveName(BX.prop.getInteger(this._entityData, "OWNER_TYPE_ID", 0)),
	        "ownerID": BX.prop.getInteger(this._entityData, "OWNER_ID", 0),
	        "ownerUrl": BX.prop.getString(this._entityData, "OWNER_URL", ""),
	        "ownerTitle": BX.prop.getString(this._entityData, "OWNER_TITLE", ""),
	        "originalMessageID": BX.prop.getInteger(this._entityData, "ID", 0),
	        "messageType": "RE"
	      };
	      if (BX.CrmActivityProvider && top.BX.Bitrix24 && top.BX.Bitrix24.Slider) {
	        const activity = this._activityEditor.addEmail(settings);
	        activity.addOnSave(this._saveHandler);
	      } else {
	        this.loadActivityCommunications(BX.delegate(function (communications) {
	          settings['communications'] = BX.type.isArray(communications) ? communications : [];
	          settings['communicationsLoaded'] = true;
	          BX.CrmActivityEmail.prepareReply(settings);
	          const activity = this._activityEditor.addEmail(settings);
	          activity.addOnSave(this._saveHandler);
	        }, this));
	      }
	      return BX.PreventDefault(e);
	    }
	  }, {
	    key: "onSave",
	    value: function onSave(activity, data) {
	      if (BX.type.isFunction(this._item.onActivityCreate)) {
	        this._item.onActivityCreate(activity, data);
	      }
	    }
	  }]);
	  return Email;
	}(Activity$1);

	/** @memberof BX.Crm.Timeline.Actions */
	let HistoryEmail = /*#__PURE__*/function (_Email) {
	  babelHelpers.inherits(HistoryEmail, _Email);
	  function HistoryEmail() {
	    babelHelpers.classCallCheck(this, HistoryEmail);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(HistoryEmail).call(this));
	  }
	  babelHelpers.createClass(HistoryEmail, [{
	    key: "doLayout",
	    value: function doLayout() {
	      this._container.appendChild(BX.create("A", {
	        attrs: {
	          className: "crm-entity-stream-content-action-reply-btn"
	        },
	        events: {
	          "click": this._clickHandler
	        }
	      }));
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new HistoryEmail();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return HistoryEmail;
	}(Email$1);

	/** @memberof BX.Crm.Timeline.Actions */
	let ScheduleEmail = /*#__PURE__*/function (_Email2) {
	  babelHelpers.inherits(ScheduleEmail, _Email2);
	  function ScheduleEmail() {
	    babelHelpers.classCallCheck(this, ScheduleEmail);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ScheduleEmail).call(this));
	  }
	  babelHelpers.createClass(ScheduleEmail, [{
	    key: "doLayout",
	    value: function doLayout() {
	      this._container.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-action-reply-btn"
	        },
	        events: {
	          "click": this._clickHandler
	        }
	      }));
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new ScheduleEmail();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return ScheduleEmail;
	}(Email$1);

	/** @memberof BX.Crm.Timeline.Items */
	let HistoryActivity = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(HistoryActivity, _History);
	  function HistoryActivity() {
	    babelHelpers.classCallCheck(this, HistoryActivity);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(HistoryActivity).call(this));
	  }
	  babelHelpers.createClass(HistoryActivity, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(HistoryActivity.prototype), "doInitialize", this).call(this);
	      if (!(this._activityEditor instanceof BX.CrmActivityEditor)) {
	        throw "HistoryActivity. The field 'activityEditor' is not assigned.";
	      }
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return BX.prop.getString(this.getAssociatedEntityData(), "SUBJECT", "");
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription() {
	      const entityData = this.getAssociatedEntityData();
	      const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	      const typeCategoryId = this.getTypeCategoryId();
	      if (typeCategoryId === BX.CrmActivityType.email) {
	        return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingEmail" : "outgoingEmail");
	      } else if (typeCategoryId === BX.CrmActivityType.call) {
	        return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall");
	      } else if (typeCategoryId === BX.CrmActivityType.meeting) {
	        return this.getMessage("meeting");
	      } else if (typeCategoryId === BX.CrmActivityType.task) {
	        return this.getMessage("task");
	      } else if (typeCategoryId === BX.CrmActivityType.provider) {
	        const providerId = BX.prop.getString(entityData, "PROVIDER_ID", "");
	        if (providerId === "CRM_WEBFORM") {
	          return this.getMessage("webform");
	        } else if (providerId === "CRM_SMS") {
	          return this.getMessage("sms");
	        } else if (providerId === "CRM_REQUEST") {
	          return this.getMessage("activityRequest");
	        } else if (providerId === "IMOPENLINES_SESSION") {
	          return this.getMessage("openLine");
	        } else if (providerId === "REST_APP") {
	          return this.getMessage("restApplication");
	        } else if (providerId === "VISIT_TRACKER") {
	          return this.getMessage("visit");
	        } else if (providerId === "ZOOM") {
	          return this.getMessage("zoom");
	        }
	      }
	      return "";
	    }
	  }, {
	    key: "prepareTitleLayout",
	    value: function prepareTitleLayout() {
	      return BX.create("A", {
	        attrs: {
	          href: "#",
	          className: "crm-entity-stream-content-event-title"
	        },
	        events: {
	          "click": this._headerClickHandler
	        },
	        text: this.getTypeDescription()
	      });
	    }
	  }, {
	    key: "prepareTimeLayout",
	    value: function prepareTimeLayout() {
	      return BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: this.formatTime(this.getCreatedTime())
	      });
	    }
	  }, {
	    key: "prepareMarkLayout",
	    value: function prepareMarkLayout() {
	      const entityData = this.getAssociatedEntityData();
	      const markTypeId = BX.prop.getInteger(entityData, "MARK_TYPE_ID", 0);
	      if (markTypeId <= 0) {
	        return null;
	      }
	      let messageName = "";
	      if (markTypeId === Mark.success) {
	        messageName = "SuccessMark";
	      } else if (markTypeId === Mark.renew) {
	        messageName = "RenewMark";
	      }
	      if (messageName === "") {
	        return null;
	      }
	      let markText = "";
	      const typeCategoryId = this.getTypeCategoryId();
	      if (typeCategoryId === BX.CrmActivityType.email) {
	        markText = this.getMessage("email" + messageName);
	      } else if (typeCategoryId === BX.CrmActivityType.call) {
	        markText = this.getMessage("call" + messageName);
	      } else if (typeCategoryId === BX.CrmActivityType.meeting) {
	        markText = this.getMessage("meeting" + messageName);
	      } else if (typeCategoryId === BX.CrmActivityType.task) {
	        markText = this.getMessage("task" + messageName);
	      }
	      if (markText === "") {
	        return null;
	      }
	      return BX.create("SPAN", {
	        props: {
	          className: "crm-entity-stream-content-event-skipped"
	        },
	        text: markText
	      });
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {
	      if (this.isReadOnly()) {
	        return;
	      }
	      const typeCategoryId = this.getTypeCategoryId();
	      if (typeCategoryId === BX.CrmActivityType.email) {
	        this._actions.push(HistoryEmail.create("email", {
	          item: this,
	          container: this._actionContainer,
	          entityData: this.getAssociatedEntityData(),
	          activityEditor: this._activityEditor
	        }));
	      }
	    }
	  }, {
	    key: "prepareContextMenuItems",
	    value: function prepareContextMenuItems() {
	      if (this._isMenuShown) {
	        return;
	      }
	      const menuItems = [];
	      if (!this.isReadOnly()) {
	        if (this.isEditable()) {
	          menuItems.push({
	            id: "edit",
	            text: this.getMessage("menuEdit"),
	            onclick: BX.delegate(this.edit, this)
	          });
	        }
	        menuItems.push({
	          id: "remove",
	          text: this.getMessage("menuDelete"),
	          onclick: BX.delegate(this.processRemoval, this)
	        });
	        if (this.isFixed() || this._fixedHistory.findItemById(this._id)) menuItems.push({
	          id: "unfasten",
	          text: this.getMessage("menuUnfasten"),
	          onclick: BX.delegate(this.unfasten, this)
	        });else menuItems.push({
	          id: "fasten",
	          text: this.getMessage("menuFasten"),
	          onclick: BX.delegate(this.fasten, this)
	        });
	      }
	      return menuItems;
	    }
	  }, {
	    key: "view",
	    value: function view() {
	      this.closeContextMenu();
	      const entityData = this.getAssociatedEntityData();
	      const id = BX.prop.getInteger(entityData, "ID", 0);
	      if (id > 0) {
	        this._activityEditor.viewActivity(id);
	      }
	    }
	  }, {
	    key: "edit",
	    value: function edit() {
	      this.closeContextMenu();
	      const associatedEntityTypeId = this.getAssociatedEntityTypeId();
	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        const entityData = this.getAssociatedEntityData();
	        const id = BX.prop.getInteger(entityData, "ID", 0);
	        if (id > 0) {
	          this._activityEditor.editActivity(id);
	        }
	      }
	    }
	  }, {
	    key: "processRemoval",
	    value: function processRemoval() {
	      this.closeContextMenu();
	      this._detetionConfirmDlgId = "entity_timeline_deletion_" + this.getId() + "_confirm";
	      let dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);
	      if (!dlg) {
	        dlg = BX.Crm.ConfirmationDialog.create(this._detetionConfirmDlgId, {
	          title: this.getMessage("removeConfirmTitle"),
	          content: this.getRemoveMessage()
	        });
	      }
	      dlg.open().then(BX.delegate(this.onRemovalConfirm, this), BX.delegate(this.onRemovalCancel, this));
	    }
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      return this.getMessage('removeConfirm');
	    }
	  }, {
	    key: "onRemovalConfirm",
	    value: function onRemovalConfirm(result) {
	      if (BX.prop.getBoolean(result, "cancel", true)) {
	        return;
	      }
	      this.remove();
	    }
	  }, {
	    key: "onRemovalCancel",
	    value: function onRemovalCancel() {}
	  }, {
	    key: "remove",
	    value: function remove() {
	      const associatedEntityTypeId = this.getAssociatedEntityTypeId();
	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        const entityData = this.getAssociatedEntityData();
	        const id = BX.prop.getInteger(entityData, "ID", 0);
	        if (id > 0) {
	          const activityEditor = this._activityEditor;
	          const item = activityEditor.getItemById(id);
	          if (item) {
	            activityEditor.deleteActivity(id, true);
	          } else {
	            const serviceUrl = BX.util.add_url_param(activityEditor.getSetting('serviceUrl', ''), {
	              id: id,
	              action: 'get_activity',
	              ownertype: activityEditor.getSetting('ownerType', ''),
	              ownerid: activityEditor.getSetting('ownerID', '')
	            });
	            BX.ajax({
	              'url': serviceUrl,
	              'method': 'POST',
	              'dataType': 'json',
	              'data': {
	                'ACTION': 'GET_ACTIVITY',
	                'ID': id,
	                'OWNER_TYPE': activityEditor.getSetting('ownerType', ''),
	                'OWNER_ID': activityEditor.getSetting('ownerID', '')
	              },
	              onsuccess: BX.delegate(function (data) {
	                if (typeof data['ACTIVITY'] !== 'undefined') {
	                  activityEditor._handleActivityChange(data['ACTIVITY']);
	                  window.setTimeout(BX.delegate(this.remove, this), 500);
	                }
	              }, this),
	              onfailure: function (data) {}
	            });
	          }
	        }
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new HistoryActivity();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return HistoryActivity;
	}(History);
	babelHelpers.defineProperty(HistoryActivity, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */
	let Email$2 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Email, _HistoryActivity);
	  function Email() {
	    babelHelpers.classCallCheck(this, Email);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Email).call(this));
	  }
	  babelHelpers.createClass(Email, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      const entityData = this.getAssociatedEntityData();
	      const emailInfo = BX.prop.getObject(entityData, "EMAIL_INFO", null);
	      const statusText = emailInfo !== null ? BX.prop.getString(emailInfo, "STATUS_TEXT", "") : "";
	      const error = emailInfo !== null ? BX.prop.getBoolean(emailInfo, "STATUS_ERROR", false) : false;
	      const className = !error ? "crm-entity-stream-content-event-skipped" : "crm-entity-stream-content-event-missing";
	      if (statusText !== "") {
	        header.appendChild(BX.create("SPAN", {
	          props: {
	            className: className
	          },
	          text: statusText
	        }));
	      }
	      const markNode = this.prepareMarkLayout();
	      if (markNode) {
	        header.appendChild(markNode);
	      }
	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareContextMenuItems",
	    value: function prepareContextMenuItems() {
	      const menuItems = [];
	      if (!this.isReadOnly()) {
	        menuItems.push({
	          id: "view",
	          text: this.getMessage("menuView"),
	          onclick: BX.delegate(this.view, this)
	        });
	        menuItems.push({
	          id: "remove",
	          text: this.getMessage("menuDelete"),
	          onclick: BX.delegate(this.processRemoval, this)
	        });
	        if (this.isFixed() || this._fixedHistory.findItemById(this._id)) menuItems.push({
	          id: "unfasten",
	          text: this.getMessage("menuUnfasten"),
	          onclick: BX.delegate(this.unfasten, this)
	        });else menuItems.push({
	          id: "fasten",
	          text: this.getMessage("menuFasten"),
	          onclick: BX.delegate(this.fasten, this)
	        });
	      }
	      return menuItems;
	    }
	  }, {
	    key: "reply",
	    value: function reply() {}
	  }, {
	    key: "replyAll",
	    value: function replyAll() {}
	  }, {
	    key: "forward",
	    value: function forward() {}
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      const title = BX.util.htmlspecialchars(this.getTitle());
	      return this.getMessage('emailRemove').replace("#TITLE#", title);
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const entityData = this.getAssociatedEntityData();
	      let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }
	      const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      const communicationTitle = BX.prop.getString(communication, "TITLE", "");
	      const communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      const communicationValue = BX.prop.getString(communication, "VALUE", "");
	      const outerWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-email"
	        }
	      });
	      outerWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-email"
	        }
	      }));
	      if (this.isFixed()) BX.addClass(outerWrapper, 'crm-entity-stream-section-top-fixed');
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      outerWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [wrapper]
	      }));

	      //Header
	      const header = this.prepareHeaderLayout();
	      wrapper.appendChild(header);

	      //region Context Menu
	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      }
	      //endregion

	      //Details
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-email"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: [detailWrapper]
	      }));

	      //TODO: Add status text
	      /*
	      detailWrapper.appendChild(
	      	BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail-email-read-status" } })
	      );
	      */

	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-email-title"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          events: {
	            "click": this._headerClickHandler
	          },
	          text: this.getTitle()
	        })]
	      }));
	      const communicationWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-email-to"
	        }
	      });
	      detailWrapper.appendChild(communicationWrapper);

	      //Communications
	      if (communicationTitle !== "") {
	        if (communicationShowUrl !== "") {
	          communicationWrapper.appendChild(BX.create("A", {
	            attrs: {
	              href: communicationShowUrl
	            },
	            text: communicationTitle
	          }));
	        } else {
	          communicationWrapper.appendChild(BX.create("SPAN", {
	            text: communicationTitle
	          }));
	        }
	      }
	      if (communicationValue !== "") {
	        if (communicationTitle !== "") {
	          communicationWrapper.appendChild(BX.create("SPAN", {
	            text: " "
	          }));
	        }
	        communicationWrapper.appendChild(BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-email-address"
	          },
	          text: communicationValue
	        }));
	      }

	      //Content
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-email-fragment"
	        },
	        children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
	      }));

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        wrapper.appendChild(authorNode);
	      }
	      //endregion

	      //region  Actions
	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      wrapper.appendChild(this._actionContainer);
	      //endregion

	      if (!this.isReadOnly()) wrapper.appendChild(this.prepareFixedSwitcherLayout());
	      return outerWrapper;
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {
	      if (this.isReadOnly()) {
	        return;
	      }
	      this._actions.push(HistoryEmail.create("email", {
	        item: this,
	        container: this._actionContainer,
	        entityData: this.getAssociatedEntityData(),
	        activityEditor: this._activityEditor
	      }));
	    }
	  }, {
	    key: "showActions",
	    value: function showActions(show) {
	      if (this._actionContainer) {
	        this._actionContainer.style.display = show ? "" : "none";
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Email();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Email;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Actions */
	let Call$1 = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(Call, _Activity);
	  function Call() {
	    var _this;
	    babelHelpers.classCallCheck(this, Call);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Call).call(this));
	    _this._clickHandler = BX.delegate(_this.onClick, babelHelpers.assertThisInitialized(_this));
	    _this._menu = null;
	    _this._isMenuShown = false;
	    _this._menuItems = null;
	    return _this;
	  }
	  babelHelpers.createClass(Call, [{
	    key: "getButton",
	    value: function getButton() {
	      return null;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick(e) {
	      if (typeof window.top['BXIM'] === 'undefined') {
	        window.alert(this.getMessage("telephonyNotSupported"));
	        return;
	      }
	      let phone = "";
	      const itemData = this.getItemData();
	      const phones = BX.prop.getArray(itemData, "PHONE", []);
	      if (phones.length === 1) {
	        this.addCall(phones[0]['VALUE']);
	      } else if (phones.length > 1) {
	        this.showMenu();
	      } else {
	        const communication = BX.prop.getObject(this._entityData, "COMMUNICATION", null);
	        if (communication) {
	          if (BX.prop.getString(communication, "TYPE") === "PHONE") {
	            phone = BX.prop.getString(communication, "VALUE");
	            if (phone) {
	              this.addCall(phone);
	            }
	          }
	        }
	      }
	      return BX.PreventDefault(e);
	    }
	  }, {
	    key: "showMenu",
	    value: function showMenu() {
	      if (this._isMenuShown) {
	        return;
	      }
	      this.prepareMenuItems();
	      if (!this._menuItems || this._menuItems.length === 0) {
	        return;
	      }
	      this._menu = new BX.PopupMenuWindow(this._id, this._container, this._menuItems, {
	        offsetTop: 0,
	        offsetLeft: 16,
	        events: {
	          onPopupShow: BX.delegate(this.onMenuShow, this),
	          onPopupClose: BX.delegate(this.onMenuClose, this),
	          onPopupDestroy: BX.delegate(this.onMenuDestroy, this)
	        }
	      });
	      this._menu.popupWindow.show();
	    }
	  }, {
	    key: "closeMenu",
	    value: function closeMenu() {
	      if (!this._isMenuShown) {
	        return;
	      }
	      if (this._menu) {
	        this._menu.close();
	      }
	    }
	  }, {
	    key: "prepareMenuItems",
	    value: function prepareMenuItems() {
	      if (this._menuItems) {
	        return;
	      }
	      const itemData = this.getItemData();
	      const phones = BX.prop.getArray(itemData, "PHONE", []);
	      const handler = BX.delegate(this.onMenuItemClick, this);
	      this._menuItems = [];
	      if (phones.length === 0) {
	        return;
	      }
	      let i = 0;
	      const l = phones.length;
	      for (; i < l; i++) {
	        const value = BX.prop.getString(phones[i], "VALUE");
	        const formattedValue = BX.prop.getString(phones[i], "VALUE_FORMATTED");
	        const complexName = BX.prop.getString(phones[i], "COMPLEX_NAME");
	        const itemText = (complexName ? complexName + ': ' : '') + (formattedValue ? formattedValue : value);
	        if (value !== "") {
	          this._menuItems.push({
	            id: value,
	            text: itemText,
	            onclick: handler
	          });
	        }
	      }
	    }
	  }, {
	    key: "onMenuItemClick",
	    value: function onMenuItemClick(e, item) {
	      this.closeMenu();
	      this.addCall(item.id);
	    }
	  }, {
	    key: "onMenuShow",
	    value: function onMenuShow() {
	      this._isMenuShown = true;
	    }
	  }, {
	    key: "onMenuClose",
	    value: function onMenuClose() {
	      this._isMenuShown = false;
	      this._menu.popupWindow.destroy();
	    }
	  }, {
	    key: "onMenuDestroy",
	    value: function onMenuDestroy() {
	      this._menu = null;
	    }
	  }, {
	    key: "addCall",
	    value: function addCall(phone) {
	      const communication = BX.prop.getObject(this._entityData, "COMMUNICATION", null);
	      let entityTypeId = parseInt(BX.prop.getString(communication, "ENTITY_TYPE_ID", "0"));
	      if (isNaN(entityTypeId)) {
	        entityTypeId = 0;
	      }
	      let entityId = parseInt(BX.prop.getString(communication, "ENTITY_ID", "0"));
	      if (isNaN(entityId)) {
	        entityId = 0;
	      }
	      let ownerTypeId = 0;
	      let ownerId = 0;
	      const ownerInfo = BX.prop.getObject(this._settings, "ownerInfo");
	      if (ownerInfo) {
	        ownerTypeId = BX.prop.getInteger(ownerInfo, "ENTITY_TYPE_ID", 0);
	        ownerId = BX.prop.getInteger(ownerInfo, "ENTITY_ID", 0);
	      }
	      if (ownerTypeId <= 0 || ownerId <= 0) {
	        ownerTypeId = BX.prop.getInteger(this._entityData, "OWNER_TYPE_ID", 0);
	        ownerId = BX.prop.getInteger(this._entityData, "OWNER_ID", "0");
	      }
	      if (ownerTypeId <= 0 || ownerId <= 0) {
	        ownerTypeId = entityTypeId;
	        ownerId = entityId;
	      }
	      let activityId = parseInt(BX.prop.getString(this._entityData, "ID", "0"));
	      if (isNaN(activityId)) {
	        activityId = 0;
	      }
	      const params = {
	        "ENTITY_TYPE_NAME": BX.CrmEntityType.resolveName(entityTypeId),
	        "ENTITY_ID": entityId,
	        "AUTO_FOLD": true
	      };
	      if (ownerTypeId !== entityTypeId || ownerId !== entityId) {
	        params["BINDINGS"] = [{
	          "OWNER_TYPE_NAME": BX.CrmEntityType.resolveName(ownerTypeId),
	          "OWNER_ID": ownerId
	        }];
	      }
	      if (activityId > 0) {
	        params["SRC_ACTIVITY_ID"] = activityId;
	      }
	      window.top['BXIM'].phoneTo(phone, params);
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = Call.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }]);
	  return Call;
	}(Activity$1);

	/** @memberof BX.Crm.Timeline.Actions */
	babelHelpers.defineProperty(Call$1, "messages", {});
	let HistoryCall = /*#__PURE__*/function (_Call) {
	  babelHelpers.inherits(HistoryCall, _Call);
	  function HistoryCall() {
	    var _this2;
	    babelHelpers.classCallCheck(this, HistoryCall);
	    _this2 = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(HistoryCall).call(this));
	    _this2._button = null;
	    return _this2;
	  }
	  babelHelpers.createClass(HistoryCall, [{
	    key: "getButton",
	    value: function getButton() {
	      return this._button;
	    }
	  }, {
	    key: "doLayout",
	    value: function doLayout() {
	      this._button = BX.create("A", {
	        attrs: {
	          className: "crm-entity-stream-content-action-reply-btn"
	        },
	        events: {
	          "click": this._clickHandler
	        }
	      });
	      this._container.appendChild(this._button);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new HistoryCall();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return HistoryCall;
	}(Call$1);
	let ScheduleCall = /*#__PURE__*/function (_Call2) {
	  babelHelpers.inherits(ScheduleCall, _Call2);
	  function ScheduleCall() {
	    babelHelpers.classCallCheck(this, ScheduleCall);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ScheduleCall).call(this));
	  }
	  babelHelpers.createClass(ScheduleCall, [{
	    key: "doLayout",
	    value: function doLayout() {
	      this._container.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-action-reply-btn"
	        },
	        events: {
	          "click": this._clickHandler
	        }
	      }));
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new ScheduleCall();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return ScheduleCall;
	}(Call$1);

	/** @memberof BX.Crm.Timeline.Items */
	let Call$2 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Call, _HistoryActivity);
	  function Call() {
	    var _this;
	    babelHelpers.classCallCheck(this, Call);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Call).call(this));
	    _this._playerDummyClickHandler = BX.delegate(_this.onPlayerDummyClick, babelHelpers.assertThisInitialized(_this));
	    _this._playerWrapper = null;
	    _this._transcriptWrapper = null;
	    _this._mediaFileInfo = null;
	    return _this;
	  }
	  babelHelpers.createClass(Call, [{
	    key: "getTypeDescription",
	    value: function getTypeDescription() {
	      const entityData = this.getAssociatedEntityData();
	      const callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);
	      const callTypeText = callInfo !== null ? BX.prop.getString(callInfo, "CALL_TYPE_TEXT", "") : "";
	      if (callTypeText !== "") {
	        return callTypeText;
	      }
	      const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	      return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall");
	    }
	  }, {
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());

	      //Position is important
	      const entityData = this.getAssociatedEntityData();
	      const callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);
	      const hasCallInfo = callInfo !== null;
	      const isSuccessfull = hasCallInfo ? BX.prop.getBoolean(callInfo, "SUCCESSFUL", false) : false;
	      const statusText = hasCallInfo ? BX.prop.getString(callInfo, "STATUS_TEXT", "") : "";
	      if (hasCallInfo && statusText.length) {
	        header.appendChild(BX.create("DIV", {
	          attrs: {
	            className: isSuccessfull ? "crm-entity-stream-content-event-successful" : "crm-entity-stream-content-event-missing"
	          },
	          text: statusText
	        }));
	      }
	      header.appendChild(BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: this.formatTime(this.getCreatedTime())
	      }));
	      return header;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const entityData = this.getAssociatedEntityData();
	      let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }
	      const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      const communicationTitle = BX.prop.getString(communication, "TITLE", "");
	      const communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      const communicationValue = BX.prop.getString(communication, "VALUE", "");
	      const communicationValueFormatted = BX.prop.getString(communication, "FORMATTED_VALUE", communicationValue);
	      const callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);
	      const hasCallInfo = callInfo !== null;
	      const durationText = hasCallInfo ? BX.prop.getString(callInfo, "DURATION_TEXT", "") : "";
	      const hasTranscript = hasCallInfo ? BX.prop.getBoolean(callInfo, "HAS_TRANSCRIPT", "") : "";
	      const isTranscriptPending = hasCallInfo ? BX.prop.getBoolean(callInfo, "TRANSCRIPT_PENDING", "") : "";
	      const callId = hasCallInfo ? BX.prop.getString(callInfo, "CALL_ID", "") : "";
	      const callComment = hasCallInfo ? BX.prop.getString(callInfo, "COMMENT", "") : "";
	      const outerWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-call"
	        }
	      });
	      outerWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-call"
	        }
	      }));
	      if (this.isFixed()) BX.addClass(outerWrapper, 'crm-entity-stream-section-top-fixed');
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      outerWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [wrapper]
	      }));

	      //Header
	      const header = this.prepareHeaderLayout();
	      wrapper.appendChild(header);

	      //region Context Menu
	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      }
	      //endregion

	      //Details
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      wrapper.appendChild(detailWrapper);
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-title"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          events: {
	            "click": this._headerClickHandler
	          },
	          text: this.getTitle()
	        })]
	      }));

	      //Content
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        children: this.prepareMultilineCutOffElements(description, 128, this._headerClickHandler)
	      }));
	      if (hasCallInfo) {
	        const callInfoWrapper = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-call crm-entity-stream-content-detail-call-inline"
	          }
	        });
	        detailWrapper.appendChild(callInfoWrapper);
	        this._mediaFileInfo = BX.prop.getObject(entityData, "MEDIA_FILE_INFO", null);
	        if (this._mediaFileInfo !== null) {
	          this._playerWrapper = this._history.getManager().renderAudioDummy(durationText, this._playerDummyClickHandler);
	          callInfoWrapper.appendChild(this._playerWrapper);
	          callInfoWrapper.appendChild(this._history.getManager().getAudioPlaybackRateSelector().render());
	        }
	        if (hasTranscript) {
	          this._transcriptWrapper = BX.create("DIV", {
	            attrs: {
	              className: "crm-audio-transcript-wrap-container"
	            },
	            events: {
	              click: function (e) {
	                if (BX.Voximplant && BX.Voximplant.Transcript) {
	                  BX.Voximplant.Transcript.create({
	                    callId: callId
	                  }).show();
	                }
	              }
	            },
	            children: [BX.create("DIV", {
	              attrs: {
	                className: "crm-audio-transcript-icon"
	              }
	            }), BX.create("DIV", {
	              attrs: {
	                className: "crm-audio-transcript-conversation"
	              },
	              text: BX.message("CRM_TIMELINE_CALL_TRANSCRIPT")
	            })]
	          });
	          callInfoWrapper.appendChild(this._transcriptWrapper);
	        } else if (isTranscriptPending) {
	          this._transcriptWrapper = BX.create("DIV", {
	            attrs: {
	              className: "crm-audio-transcript-wrap-container-pending"
	            },
	            children: [BX.create("DIV", {
	              attrs: {
	                className: "crm-audio-transcript-icon-pending"
	              },
	              html: '<svg class="crm-transcript-loader-circular" viewBox="25 25 50 50"><circle class="crm-transcript-loader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"></circle></svg>'
	            }), BX.create("DIV", {
	              attrs: {
	                className: "crm-audio-transcript-conversation"
	              },
	              text: BX.message("CRM_TIMELINE_CALL_TRANSCRIPT_PENDING")
	            })]
	          });
	          callInfoWrapper.appendChild(this._transcriptWrapper);
	        }
	        if (callComment) {
	          detailWrapper.appendChild(BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-content-detail-description"
	            },
	            text: callComment
	          }));
	        }
	      }
	      const communicationWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-contact-info"
	        }
	      });
	      detailWrapper.appendChild(communicationWrapper);

	      //Communications
	      if (communicationTitle !== "") {
	        if (communicationShowUrl !== "") {
	          communicationWrapper.appendChild(BX.create("A", {
	            attrs: {
	              href: communicationShowUrl
	            },
	            text: communicationTitle
	          }));
	        } else {
	          communicationWrapper.appendChild(BX.create("SPAN", {
	            text: communicationTitle
	          }));
	        }
	      }
	      if (communicationValueFormatted !== "") {
	        if (communicationTitle !== "") {
	          communicationWrapper.appendChild(BX.create("SPAN", {
	            text: " "
	          }));
	        }
	        communicationWrapper.appendChild(BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-email-address"
	          },
	          text: communicationValueFormatted
	        }));
	      }

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        wrapper.appendChild(authorNode);
	      }
	      //endregion

	      //region  Actions
	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      wrapper.appendChild(this._actionContainer);
	      //endregion

	      if (!this.isReadOnly()) {
	        wrapper.appendChild(this.prepareFixedSwitcherLayout());
	      }
	      return outerWrapper;
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {
	      if (this.isReadOnly()) {
	        return;
	      }
	      this._actions.push(HistoryCall.create("call", {
	        item: this,
	        container: this._actionContainer,
	        entityData: this.getAssociatedEntityData(),
	        activityEditor: this._activityEditor,
	        ownerInfo: this._history.getOwnerInfo()
	      }));
	    }
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      const entityData = this.getAssociatedEntityData();
	      const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	      const messageName = direction === BX.CrmActivityDirection.incoming ? 'incomingCallRemove' : 'outgoingCallRemove';
	      const title = BX.util.htmlspecialchars(this.getTitle());
	      return this.getMessage(messageName).replace("#TITLE#", title);
	    }
	  }, {
	    key: "onPlayerDummyClick",
	    value: function onPlayerDummyClick(e) {
	      const stubNode = this._playerWrapper.querySelector(".crm-audio-cap-wrap");
	      if (stubNode) {
	        BX.addClass(stubNode, "crm-audio-cap-wrap-loader");
	      }
	      this._history.getManager().getAudioPlaybackRateSelector().addPlayer(this._history.getManager().loadMediaPlayer("history_" + this.getId(), this._mediaFileInfo["URL"], this._mediaFileInfo["TYPE"], this._playerWrapper, this._mediaFileInfo["DURATION"], {
	        playbackRate: this._history.getManager().getAudioPlaybackRateSelector().getRate()
	      }));
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Call();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Call;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */
	let Meeting$1 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Meeting, _HistoryActivity);
	  function Meeting() {
	    babelHelpers.classCallCheck(this, Meeting);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Meeting).call(this));
	  }
	  babelHelpers.createClass(Meeting, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      const markNode = this.prepareMarkLayout();
	      if (markNode) {
	        header.appendChild(markNode);
	      }
	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const entityData = this.getAssociatedEntityData();
	      let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }
	      const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      const communicationTitle = BX.prop.getString(communication, "TITLE", "");
	      const communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      const communicationValue = BX.prop.getString(communication, "VALUE", "");
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-meeting"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-meeting"
	        }
	      }));
	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      }
	      if (this.isFixed()) BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [contentWrapper]
	      }));
	      const header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      contentWrapper.appendChild(detailWrapper);
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-title"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          events: {
	            "click": this._headerClickHandler
	          },
	          text: this.getTitle()
	        })]
	      }));

	      //Content
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
	      }));
	      const communicationWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-contact-info"
	        }
	      });
	      detailWrapper.appendChild(communicationWrapper);
	      if (communicationTitle !== '') {
	        communicationWrapper.appendChild(BX.create("SPAN", {
	          text: this.getMessage("reciprocal") + ": "
	        }));
	        if (communicationShowUrl !== '') {
	          communicationWrapper.appendChild(BX.create("A", {
	            attrs: {
	              href: communicationShowUrl
	            },
	            text: communicationTitle
	          }));
	        } else {
	          communicationWrapper.appendChild(BX.create("SPAN", {
	            text: communicationTitle
	          }));
	        }
	      }
	      communicationWrapper.appendChild(BX.create("SPAN", {
	        text: " " + communicationValue
	      }));

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      }
	      //endregion

	      //region  Actions
	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer);
	      //endregion

	      if (!this.isReadOnly()) contentWrapper.appendChild(this.prepareFixedSwitcherLayout());
	      return wrapper;
	    }
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      const title = BX.util.htmlspecialchars(this.getTitle());
	      return this.getMessage('meetingRemove').replace("#TITLE#", title);
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {}
	  }, {
	    key: "showActions",
	    value: function showActions(show) {
	      if (this._actionContainer) {
	        this._actionContainer.style.display = show ? "" : "none";
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Meeting();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Meeting;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */
	let Task$1 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Task, _HistoryActivity);
	  function Task() {
	    babelHelpers.classCallCheck(this, Task);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Task).call(this));
	  }
	  babelHelpers.createClass(Task, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      const markNode = this.prepareMarkLayout();
	      if (markNode) {
	        header.appendChild(markNode);
	      }
	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const entityData = this.getAssociatedEntityData();
	      let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }
	      const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      const communicationTitle = BX.prop.getString(communication, "TITLE", "");
	      const communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-task"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-task"
	        }
	      }));
	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      }
	      if (this.isFixed()) BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [contentWrapper]
	      }));
	      const header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      contentWrapper.appendChild(detailWrapper);
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-title"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          events: {
	            "click": this._headerClickHandler
	          },
	          text: this.getTitle()
	        })]
	      }));

	      //Content
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
	      }));
	      const communicationWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-contact-info"
	        }
	      });
	      detailWrapper.appendChild(communicationWrapper);
	      if (communicationTitle !== '') {
	        communicationWrapper.appendChild(BX.create("SPAN", {
	          text: this.getMessage("reciprocal") + ": "
	        }));
	        if (communicationShowUrl !== '') {
	          communicationWrapper.appendChild(BX.create("A", {
	            attrs: {
	              href: communicationShowUrl
	            },
	            text: communicationTitle
	          }));
	        } else {
	          communicationWrapper.appendChild(BX.create("SPAN", {
	            text: communicationTitle
	          }));
	        }
	      }

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      }
	      //endregion

	      //region  Actions
	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer);
	      //endregion

	      if (!this.isReadOnly()) contentWrapper.appendChild(this.prepareFixedSwitcherLayout());
	      return wrapper;
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {}
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      const title = BX.util.htmlspecialchars(this.getTitle());
	      return this.getMessage('taskRemove').replace("#TITLE#", title);
	    }
	  }, {
	    key: "showActions",
	    value: function showActions(show) {
	      if (this._actionContainer) {
	        this._actionContainer.style.display = show ? "" : "none";
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Task();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Task;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */
	let WebForm$1 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(WebForm, _HistoryActivity);
	  function WebForm() {
	    babelHelpers.classCallCheck(this, WebForm);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(WebForm).call(this));
	  }
	  babelHelpers.createClass(WebForm, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-crmForm"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-crmForm"
	        }
	      }));
	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [contentWrapper]
	      }));
	      if (this.isFixed()) BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
	      const header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      contentWrapper.appendChild(detailWrapper);
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-title"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          events: {
	            "click": this._headerClickHandler
	          },
	          text: this.getTitle()
	        })]
	      }));

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      }
	      //endregion

	      //region  Actions
	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer);
	      //endregion

	      if (!this.isReadOnly()) contentWrapper.appendChild(this.prepareFixedSwitcherLayout());
	      return wrapper;
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {}
	  }, {
	    key: "showActions",
	    value: function showActions(show) {
	      if (this._actionContainer) {
	        this._actionContainer.style.display = show ? "" : "none";
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new WebForm();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return WebForm;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */
	let Request$1 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Request, _HistoryActivity);
	  function Request() {
	    babelHelpers.classCallCheck(this, Request);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Request).call(this));
	  }
	  babelHelpers.createClass(Request, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const entityData = this.getAssociatedEntityData();
	      let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }

	      //var entityData = this.getAssociatedEntityData();
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-today crm-entity-stream-section-robot"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-robot"
	        }
	      }));
	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      }
	      if (this.isFixed()) BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [contentWrapper]
	      }));
	      const header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      contentWrapper.appendChild(detailWrapper);
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-title"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          events: {
	            "click": this._headerClickHandler
	          },
	          text: this.getTitle()
	        })]
	      }));

	      //Content
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
	      }));

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      }
	      //endregion

	      //region  Actions
	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer);
	      //endregion

	      if (!this.isReadOnly()) contentWrapper.appendChild(this.prepareFixedSwitcherLayout());
	      return wrapper;
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {}
	  }, {
	    key: "showActions",
	    value: function showActions(show) {
	      if (this._actionContainer) {
	        this._actionContainer.style.display = show ? "" : "none";
	      }
	    }
	  }, {
	    key: "isEditable",
	    value: function isEditable() {
	      return false;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Request();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Request;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */
	let OpenLine$2 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(OpenLine$$1, _HistoryActivity);
	  function OpenLine$$1() {
	    babelHelpers.classCallCheck(this, OpenLine$$1);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OpenLine$$1).call(this));
	  }
	  babelHelpers.createClass(OpenLine$$1, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const entityData = this.getAssociatedEntityData();
	      let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }
	      const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      const communicationTitle = BX.prop.getString(communication, "TITLE", "");
	      const communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-IM"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-IM"
	        }
	      }));
	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      }
	      if (this.isFixed()) BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [contentWrapper]
	      }));
	      const header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      contentWrapper.appendChild(detailWrapper);
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-title"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          events: {
	            "click": this._headerClickHandler
	          },
	          text: this.getTitle()
	        })]
	      }));

	      //Content
	      const entityDetailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-IM"
	        }
	      });
	      detailWrapper.appendChild(entityDetailWrapper);
	      const messageWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-IM-messages"
	        }
	      });
	      entityDetailWrapper.appendChild(messageWrapper);
	      const openLineData = BX.prop.getObject(this.getAssociatedEntityData(), "OPENLINE_INFO", null);
	      if (openLineData) {
	        const messages = BX.prop.getArray(openLineData, "MESSAGES", []);
	        let i = 0;
	        const length = messages.length;
	        for (; i < length; i++) {
	          const message = messages[i];
	          const isExternal = BX.prop.getBoolean(message, "IS_EXTERNAL", true);
	          messageWrapper.appendChild(BX.create("DIV", {
	            attrs: {
	              className: isExternal ? "crm-entity-stream-content-detail-IM-message-incoming" : "crm-entity-stream-content-detail-IM-message-outgoing"
	            },
	            html: BX.prop.getString(message, "MESSAGE", "")
	          }));
	        }
	      }
	      const communicationWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-contact-info"
	        }
	      });
	      detailWrapper.appendChild(communicationWrapper);
	      if (communicationTitle !== '') {
	        communicationWrapper.appendChild(BX.create("SPAN", {
	          text: this.getMessage("reciprocal") + ": "
	        }));
	        if (communicationShowUrl !== '') {
	          communicationWrapper.appendChild(BX.create("A", {
	            attrs: {
	              href: communicationShowUrl
	            },
	            text: communicationTitle
	          }));
	        } else {
	          communicationWrapper.appendChild(BX.create("SPAN", {
	            text: communicationTitle
	          }));
	        }
	      }

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      }
	      //endregion

	      //region  Actions
	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer);
	      //endregion

	      if (!this.isReadOnly()) contentWrapper.appendChild(this.prepareFixedSwitcherLayout());
	      return wrapper;
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {
	      if (this.isReadOnly()) {
	        return;
	      }
	      this._actions.push(OpenLine.create("openline", {
	        item: this,
	        container: this._actionContainer,
	        entityData: this.getAssociatedEntityData(),
	        activityEditor: this._activityEditor,
	        ownerInfo: this._history.getOwnerInfo()
	      }));
	    }
	  }, {
	    key: "view",
	    value: function view() {
	      if (typeof window.top['BXIM'] === 'undefined') {
	        window.alert(this.getMessage("openLineNotSupported"));
	        return;
	      }
	      let slug = "";
	      const communication = BX.prop.getObject(this.getAssociatedEntityData(), "COMMUNICATION", null);
	      if (communication) {
	        if (BX.prop.getString(communication, "TYPE") === "IM") {
	          slug = BX.prop.getString(communication, "VALUE");
	        }
	      }
	      if (slug !== "") {
	        window.top['BXIM'].openMessengerSlider(slug, {
	          RECENT: 'N',
	          MENU: 'N'
	        });
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new OpenLine$$1();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return OpenLine$$1;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */
	let Rest$1 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Rest, _HistoryActivity);
	  function Rest() {
	    babelHelpers.classCallCheck(this, Rest);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Rest).call(this));
	  }
	  babelHelpers.createClass(Rest, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription() {
	      const entityData = this.getAssociatedEntityData();
	      if (entityData['APP_TYPE'] && entityData['APP_TYPE']['NAME']) {
	        return entityData['APP_TYPE']['NAME'];
	      }
	      return babelHelpers.get(babelHelpers.getPrototypeOf(Rest.prototype), "getTypeDescription", this).call(this);
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const entityData = this.getAssociatedEntityData();
	      let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }

	      //var entityData = this.getAssociatedEntityData();
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-today crm-entity-stream-section-rest"
	        }
	      });
	      const iconNode = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-rest"
	        }
	      });
	      wrapper.appendChild(iconNode);
	      if (entityData['APP_TYPE'] && entityData['APP_TYPE']['ICON_SRC']) {
	        if (iconNode) {
	          iconNode.style.backgroundImage = "url('" + entityData['APP_TYPE']['ICON_SRC'] + "')";
	          iconNode.style.backgroundPosition = "center center";
	          iconNode.style.backgroundSize = "cover";
	          iconNode.style.backgroundColor = "transparent";
	        }
	      }
	      if (this.isFixed()) BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [contentWrapper]
	      }));
	      const header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      contentWrapper.appendChild(detailWrapper);
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-title"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          events: {
	            "click": this._headerClickHandler
	          },
	          text: this.getTitle()
	        })]
	      }));

	      //Content
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
	      }));

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      }
	      //endregion

	      //region  Actions
	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer);
	      //endregion

	      if (!this.isReadOnly()) contentWrapper.appendChild(this.prepareFixedSwitcherLayout());
	      return wrapper;
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {}
	  }, {
	    key: "showActions",
	    value: function showActions(show) {
	      if (this._actionContainer) {
	        this._actionContainer.style.display = show ? "" : "none";
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Rest();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Rest;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Actions */
	let Visit = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Visit, _HistoryActivity);
	  function Visit() {
	    var _this;
	    babelHelpers.classCallCheck(this, Visit);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Visit).call(this));
	    _this._playerDummyClickHandler = BX.delegate(_this.onPlayerDummyClick, babelHelpers.assertThisInitialized(_this));
	    _this._playerWrapper = null;
	    _this._transcriptWrapper = null;
	    _this._mediaFileInfo = null;
	    return _this;
	  }
	  babelHelpers.createClass(Visit, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      const entityData = this.getAssociatedEntityData();
	      const visitInfo = BX.prop.getObject(entityData, "VISIT_INFO", {});
	      const recordLength = BX.prop.getInteger(visitInfo, "RECORD_LENGTH", 0);
	      const recordLengthFormatted = BX.prop.getString(visitInfo, "RECORD_LENGTH_FORMATTED_FULL", "");
	      header.appendChild(BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: (recordLength > 0 ? recordLengthFormatted + ', ' + BX.message('CRM_TIMELINE_VISIT_AT') + ' ' : '') + this.formatTime(this.getCreatedTime())
	      }));
	      return header;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const entityData = this.getAssociatedEntityData();
	      const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      const communicationTitle = BX.prop.getString(communication, "TITLE", "");
	      const communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      const visitInfo = BX.prop.getObject(entityData, "VISIT_INFO", {});
	      const recordLength = BX.prop.getInteger(visitInfo, "RECORD_LENGTH", 0);
	      const recordLengthFormatted = BX.prop.getString(visitInfo, "RECORD_LENGTH_FORMATTED_SHORT", "");
	      const vkProfile = BX.prop.getString(visitInfo, "VK_PROFILE", "");
	      const outerWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-visit"
	        }
	      });
	      outerWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-visit"
	        }
	      }));
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      outerWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [wrapper]
	      }));

	      //Header
	      const header = this.prepareHeaderLayout();
	      wrapper.appendChild(header);

	      //region Context Menu
	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      }
	      //endregion

	      //Details
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail crm-entity-stream-content-detail-call-inline"
	        }
	      });
	      wrapper.appendChild(detailWrapper);
	      this._mediaFileInfo = BX.prop.getObject(entityData, "MEDIA_FILE_INFO", null);
	      if (this._mediaFileInfo !== null && recordLength > 0) {
	        this._playerWrapper = this._history.getManager().renderAudioDummy(recordLengthFormatted, this._playerDummyClickHandler);
	        detailWrapper.appendChild(
	        //crm-entity-stream-content-detail-call
	        this._playerWrapper);
	        detailWrapper.appendChild(this._history.getManager().getAudioPlaybackRateSelector().render());
	      }
	      const communicationWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-contact-info"
	        }
	      });
	      wrapper.appendChild(communicationWrapper);

	      //Communications
	      if (communicationTitle !== "") {
	        communicationWrapper.appendChild(document.createTextNode(BX.message("CRM_TIMELINE_VISIT_WITH") + ' '));
	        if (communicationShowUrl !== "") {
	          communicationWrapper.appendChild(BX.create("A", {
	            attrs: {
	              href: communicationShowUrl
	            },
	            text: communicationTitle
	          }));
	        } else {
	          communicationWrapper.appendChild(BX.create("SPAN", {
	            text: communicationTitle
	          }));
	        }
	      }
	      if (BX.type.isNotEmptyString(vkProfile)) {
	        communicationWrapper.appendChild(document.createTextNode(" "));
	        communicationWrapper.appendChild(BX.create("a", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-additional",
	            target: "_blank",
	            href: this.getVkProfileUrl(vkProfile)
	          },
	          text: BX.message('CRM_TIMELINE_VISIT_VKONTAKTE_PROFILE')
	        }));
	      }

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        wrapper.appendChild(authorNode);
	      }
	      //endregion

	      return outerWrapper;
	    }
	  }, {
	    key: "onPlayerDummyClick",
	    value: function onPlayerDummyClick(e) {
	      const stubNode = this._playerWrapper.querySelector(".crm-audio-cap-wrap");
	      if (stubNode) {
	        BX.addClass(stubNode, "crm-audio-cap-wrap-loader");
	      }
	      this._history.getManager().getAudioPlaybackRateSelector().addPlayer(this._history.getManager().loadMediaPlayer("history_" + this.getId(), this._mediaFileInfo["URL"], this._mediaFileInfo["TYPE"], this._playerWrapper, this._mediaFileInfo["DURATION"], {
	        playbackRate: this._history.getManager().getAudioPlaybackRateSelector().getRate()
	      }));
	    }
	  }, {
	    key: "getVkProfileUrl",
	    value: function getVkProfileUrl(profile) {
	      return 'https://vk.com/' + BX.util.htmlspecialchars(profile);
	    }
	  }, {
	    key: "view",
	    value: function view() {
	      if (BX.getClass('BX.Crm.Restriction.Bitrix24') && BX.Crm.Restriction.Bitrix24.isRestricted('visit')) {
	        return BX.Crm.Restriction.Bitrix24.getHandler('visit').call();
	      }
	      babelHelpers.get(babelHelpers.getPrototypeOf(Visit.prototype), "view", this).call(this);
	    }
	  }, {
	    key: "edit",
	    value: function edit() {
	      if (BX.getClass('BX.Crm.Restriction.Bitrix24') && BX.Crm.Restriction.Bitrix24.isRestricted('visit')) {
	        return BX.Crm.Restriction.Bitrix24.getHandler('visit').call();
	      }
	      babelHelpers.get(babelHelpers.getPrototypeOf(Visit.prototype), "edit", this).call(this);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Visit();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Visit;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Actions */
	let Zoom$1 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Zoom, _HistoryActivity);
	  function Zoom() {
	    var _this;
	    babelHelpers.classCallCheck(this, Zoom);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Zoom).call(this));
	    _this._videoDummy = null;
	    _this._audioDummy = null;
	    _this._videoPlayer = null;
	    _this._audioPlayer = null;
	    _this._audioLengthElement = null;
	    _this._recordings = [];
	    _this._currentRecordingIndex = 0;
	    _this.zoomActivitySubject = null;
	    _this._downloadWrapper = null;
	    _this._downloadSubject = null;
	    _this._downloadSubjectDetail = null;
	    _this._downloadVideoLink = null;
	    _this._downloadSeparator = null;
	    _this._downloadAudioLink = null;
	    _this._playVideoLink = null;
	    _this.detailZoomCopyVideoLink = null;
	    return _this;
	  }
	  babelHelpers.createClass(Zoom, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      if (!this._data.hasOwnProperty('PROVIDER_DATA') || this._data["PROVIDER_DATA"]["ZOOM_EVENT_TYPE"] !== 'ZOOM_CONF_JOINED') {
	        header.appendChild(this.prepareSuccessfulLayout());
	      }
	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareSuccessfulLayout",
	    value: function prepareSuccessfulLayout() {
	      return BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-successful"
	        },
	        text: BX.message('CRM_TIMELINE_ZOOM_SUCCESSFUL_ACTIVITY')
	      });
	    }
	  }, {
	    key: "prepareTitleLayout",
	    value: function prepareTitleLayout() {
	      if (this._data.hasOwnProperty('PROVIDER_DATA') && this._data["PROVIDER_DATA"]["ZOOM_EVENT_TYPE"] === 'ZOOM_CONF_JOINED') {
	        return BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-event-title"
	          },
	          text: BX.message('CRM_TIMELINE_ZOOM_JOINED_CONFERENCE')
	        });
	      } else {
	        return BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-event-title"
	          },
	          text: BX.message('CRM_TIMELINE_ZOOM_CONFERENCE_END')
	        });
	      }
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history"
	        }
	      });
	      let entityDetailWrapper;
	      const zoomData = BX.prop.getObject(this.getAssociatedEntityData(), "ZOOM_INFO", null);
	      const subject = BX.prop.getString(this.getAssociatedEntityData(), "SUBJECT", null);
	      this._recordings = BX.prop.getArray(zoomData, "RECORDINGS", []);
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-zoom"
	        }
	      }));
	      if (this.isFixed()) BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [contentWrapper]
	      }));
	      const header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      contentWrapper.appendChild(detailWrapper);
	      if (this._data.hasOwnProperty('PROVIDER_DATA') && this._data["PROVIDER_DATA"]["ZOOM_EVENT_TYPE"] === 'ZOOM_CONF_JOINED') {
	        entityDetailWrapper = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-description"
	          },
	          text: zoomData['CONF_URL']
	        });
	      } else {
	        entityDetailWrapper = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-description"
	          }
	        });
	        if (this._recordings.length > 0) {
	          if (this._recordings.length > 1) {
	            //render video parts header

	            const tabs = this._recordings.map(function (recording, index) {
	              return {
	                id: index,
	                title: BX.message("CRM_TIMELINE_ZOOM_MEETING_RECORD_PART").replace("#NUMBER#", index + 1),
	                time: recording["AUDIO"] ? recording["AUDIO"]["LENGTH_FORMATTED"] : "",
	                active: index === 0
	              };
	            });
	            const tabsComponent = new Zoom.TabsComponent({
	              tabs: tabs
	            });
	            tabsComponent.eventEmitter.subscribe("onTabChange", this._onTabChange.bind(this));
	            detailWrapper.appendChild(tabsComponent.render());
	          }
	          this._videoDummy = BX.create("DIV", {
	            props: {
	              className: "crm-entity-stream-content-detail-zoom-video-wrap"
	            },
	            children: [BX.create("DIV", {
	              props: {
	                className: "crm-entity-stream-content-detail-zoom-video"
	              },
	              events: {
	                click: this._onVideoDummyClick.bind(this)
	              },
	              children: [BX.create("DIV", {
	                props: {
	                  className: "crm-entity-stream-content-detail-zoom-video-inner"
	                },
	                children: [BX.create("DIV", {
	                  props: {
	                    className: "crm-entity-stream-content-detail-zoom-video-btn"
	                  },
	                  dataset: {
	                    hint: BX.message("CRM_TIMELINE_ZOOM_LOGIN_REQUIRED"),
	                    'hintNoIcon': 'Y'
	                  }
	                }), BX.create("SPAN", {
	                  props: {
	                    className: "crm-entity-stream-content-detail-zoom-video-text"
	                  },
	                  text: BX.message("CRM_TIMELINE_ZOOM_CLICK_TO_WATCH")
	                })]
	              })]
	            })]
	          });
	          BX.UI.Hint.init(this._videoDummy);
	          this._audioDummy = this._history.getManager().renderAudioDummy("00:15", this._onAudioDummyClick.bind(this));
	          this._audioLengthElement = this._audioDummy.querySelector('.crm-audio-cap-time');
	          if (zoomData['RECORDINGS'][0]['VIDEO']) {
	            //video download link with token valid for 24h
	            const videoLinkExpireTS = zoomData['RECORDINGS'][0]['VIDEO']['END_DATE_TS'] * 1000 + 60 * 60 * 23 * 1000;
	            if (videoLinkExpireTS < Date.now()) {
	              const videoLinkContainer = BX.create("DIV", {
	                props: {
	                  className: "crm-entity-stream-content-detail-zoom-desc"
	                }
	              });
	              this._playVideoLink = BX.create("DIV", {
	                html: BX.message("CRM_TIMELINE_ZOOM_PLAY_LINK_VIDEO")
	              });
	              this._detailZoomCopyVideoLink = BX.create("A", {
	                attrs: {
	                  className: 'ui-link ui-link-dashed'
	                },
	                text: BX.message("CRM_TIMELINE_ZOOM_COPY_PASSWORD")
	              });
	              videoLinkContainer.appendChild(this._playVideoLink);
	              videoLinkContainer.appendChild(this._detailZoomCopyVideoLink);
	              entityDetailWrapper.appendChild(videoLinkContainer);
	            } else {
	              entityDetailWrapper.appendChild(this._videoDummy);
	            }
	          }
	          if (zoomData['RECORDINGS'][0]['AUDIO']) {
	            const zoomAudioDetailWrapper = BX.create("DIV", {
	              attrs: {
	                className: "crm-entity-stream-content-detail-call crm-entity-stream-content-detail-call-inline"
	              }
	            });
	            zoomAudioDetailWrapper.appendChild(this._audioDummy);
	            zoomAudioDetailWrapper.appendChild(this._history.getManager().getAudioPlaybackRateSelector().render());
	            entityDetailWrapper.appendChild(zoomAudioDetailWrapper);
	          }
	          this._downloadWrapper = BX.create("DIV", {
	            props: {
	              className: "crm-entity-stream-content-detail-zoom-desc"
	            }
	          });
	          entityDetailWrapper.appendChild(this._downloadWrapper);
	          this._downloadSubject = BX.create("SPAN", {
	            props: {
	              className: "crm-entity-stream-content-detail-zoom-desc-subject"
	            }
	          });
	          this._downloadSubjectDetail = BX.create("SPAN", {
	            props: {
	              className: "crm-entity-stream-content-detail-zoom-desc-detail"
	            }
	          });
	          this._downloadVideoLink = BX.create("A", {
	            props: {
	              className: "crm-entity-stream-content-detail-zoom-desc-link"
	            },
	            text: BX.message("CRM_TIMELINE_ZOOM_DOWNLOAD_VIDEO")
	          });
	          this._downloadSeparator = BX.create("SPAN", {
	            props: {
	              className: "crm-entity-stream-content-detail-zoom-desc-separate"
	            },
	            html: "&mdash;"
	          });
	          this._downloadAudioLink = BX.create("A", {
	            props: {
	              className: "crm-entity-stream-content-detail-zoom-desc-link"
	            },
	            text: BX.message("CRM_TIMELINE_ZOOM_DOWNLOAD_AUDIO")
	          });
	          this.setCurrentRecording(0);
	        } else {
	          this.zoomActivitySubject = BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-content-detail-title"
	            },
	            children: [BX.create("A", {
	              attrs: {
	                href: "#"
	              },
	              events: {
	                "click": this._headerClickHandler
	              },
	              text: subject
	            })]
	          });
	          entityDetailWrapper.appendChild(this.zoomActivitySubject);
	          if (zoomData['HAS_RECORDING'] === 'Y') {
	            entityDetailWrapper.appendChild(BX.create("DIV", {
	              props: {
	                className: "crm-entity-stream-content-detail-zoom-video"
	              },
	              children: [BX.create("DIV", {
	                props: {
	                  className: "crm-entity-stream-content-detail-zoom-video-inner"
	                },
	                children: [BX.create("DIV", {
	                  props: {
	                    className: "crm-entity-stream-content-detail-zoom-video-img"
	                  }
	                }), BX.create("SPAN", {
	                  props: {
	                    className: "crm-entity-stream-content-detail-zoom-video-text"
	                  },
	                  text: BX.message("CRM_TIMELINE_ZOOM_MEETING_RECORD_IN_PROCESS")
	                })]
	              })]
	            }));
	          }
	        }
	      }
	      /*else
	      {
	      	detailWrapper.appendChild(BX.create("span", {text: "456"}));
	      		var entityDetailWrapper = BX.create("DIV",
	      		{
	      			attrs: { className: "crm-entity-stream-content-detail-description" },
	      			text: BX.prop.getString(zoomData, "CONF_URL", "")
	      		}
	      	);
	      }*/
	      //Content //todo

	      if (entityDetailWrapper) {
	        detailWrapper.appendChild(entityDetailWrapper);
	      }

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      }
	      //endregion

	      return wrapper;
	    }
	  }, {
	    key: "_onVideoDummyClick",
	    value: function _onVideoDummyClick() {
	      BX.UI.Hint.hide();
	      const recording = this._recordings[this._currentRecordingIndex]["VIDEO"];
	      if (!recording) {
	        return;
	      }
	      this._videoPlayer = this._history.getManager().loadMediaPlayer("zoom_video_" + this.getId(), recording["DOWNLOAD_URL"], "video/mp4", this._videoDummy, recording["LENGTH"], {
	        video: true,
	        skin: "",
	        width: 480,
	        height: 270
	      });
	    }
	  }, {
	    key: "_onAudioDummyClick",
	    value: function _onAudioDummyClick() {
	      const recording = this._recordings[this._currentRecordingIndex]["AUDIO"];
	      if (!recording) {
	        return;
	      }
	      this._history.getManager().getAudioPlaybackRateSelector().addPlayer(this._audioPlayer = this._history.getManager().loadMediaPlayer("zoom_audio_" + this.getId(), recording["DOWNLOAD_URL"], "audio/mp4", this._audioDummy, recording["LENGTH"], {
	        playbackRate: this._history.getManager().getAudioPlaybackRateSelector().getRate()
	      }));
	    }
	  }, {
	    key: "_onTabChange",
	    value: function _onTabChange(event) {
	      this.setCurrentRecording(event.data.tabId);
	    }
	  }, {
	    key: "setCurrentRecording",
	    value: function setCurrentRecording(recordingIndex) {
	      this._currentRecordingIndex = recordingIndex;
	      const videoRecording = this._recordings[this._currentRecordingIndex]["VIDEO"];
	      const audioRecording = this._recordings[this._currentRecordingIndex]["AUDIO"];
	      if (videoRecording) {
	        this._videoDummy.hidden = false;
	        if (this._videoPlayer) {
	          this._videoPlayer.pause();
	          this._videoPlayer.setSource(videoRecording["DOWNLOAD_URL"]);
	          this._downloadVideoLink.href = videoRecording["DOWNLOAD_URL"];
	        }
	      } else {
	        this._videoDummy.hidden = true;
	      }
	      if (audioRecording) {
	        this._audioDummy.hidden = false;
	        if (this._audioPlayer) {
	          this._audioPlayer.pause();
	          this._audioPlayer.setSource(audioRecording["DOWNLOAD_URL"]);
	        }
	        this._downloadAudioLink.href = audioRecording["DOWNLOAD_URL"];
	        this._audioLengthElement.innerText = audioRecording["LENGTH_FORMATTED"];
	      } else {
	        this._audioDummy.hidden = true;
	      }
	      BX.clean(this._downloadWrapper);
	      if (audioRecording || videoRecording) {
	        const lengthHuman = audioRecording ? audioRecording["LENGTH_HUMAN"] : videoRecording["LENGTH_HUMAN"];
	        this._downloadWrapper.appendChild(this._downloadSubject);
	        this._downloadSubject.innerHTML = BX.util.htmlspecialchars(BX.message("CRM_TIMELINE_ZOOM_MEETING_RECORD").replace("#DURATION#", lengthHuman)) + " &mdash; ";
	        this._downloadWrapper.appendChild(this._downloadSubjectDetail);
	      }
	      if (videoRecording) {
	        this._downloadSubjectDetail.appendChild(this._downloadVideoLink);
	        this._downloadVideoLink.href = videoRecording['DOWNLOAD_URL'];
	        if (audioRecording) {
	          this._downloadSubjectDetail.appendChild(this._downloadSeparator);
	        }
	        if (this._playVideoLink) {
	          this._playVideoLink.lastElementChild.href = videoRecording["PLAY_URL"];
	          this._downloadVideoLink.href = videoRecording["PLAY_URL"];
	        }
	        if (this._detailZoomCopyVideoLink) {
	          BX.clipboard.bindCopyClick(this._detailZoomCopyVideoLink, {
	            text: videoRecording['PASSWORD']
	          });
	        }
	      }
	      if (audioRecording) {
	        this._downloadSubjectDetail.appendChild(this._downloadAudioLink);
	        this._downloadAudioLink.href = audioRecording['DOWNLOAD_URL'];
	      }
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {}
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Zoom();
	      self.initialize(id, settings);

	      //todo: remove debug
	      if (!window['zoom']) {
	        window['zoom'] = [];
	      }
	      window['zoom'].push(self);
	      return self;
	    }
	  }]);
	  return Zoom;
	}(HistoryActivity);
	Zoom$1.TabsComponent = /*#__PURE__*/function () {
	  function _class(config) {
	    babelHelpers.classCallCheck(this, _class);
	    this.tabs = BX.prop.getArray(config, "tabs", []);
	    this.elements = {
	      container: null,
	      tabs: {}
	    };
	    this.eventEmitter = new BX.Event.EventEmitter(this, 'Zoom.TabsComponent');
	  }
	  babelHelpers.createClass(_class, [{
	    key: "render",
	    value: function render() {
	      if (this.elements.container) {
	        return this.elements.container;
	      }
	      this.elements.container = BX.create("DIV", {
	        props: {
	          className: "crm-entity-stream-content-detail-zoom-section-wrapper"
	        },
	        children: [BX.create("DIV", {
	          props: {
	            className: "crm-entity-stream-content-detail-zoom-section-list"
	          },
	          children: this.tabs.map(this._renderTab, this)
	        })]
	      });
	      return this.elements.container;
	    }
	  }, {
	    key: "_renderTab",
	    value: function _renderTab(tabDescription) {
	      const tabId = tabDescription.id;
	      this.elements.tabs[tabId] = BX.create("DIV", {
	        props: {
	          className: "crm-entity-stream-content-detail-zoom-section" + (tabDescription.active ? " crm-entity-stream-content-detail-zoom-section-active" : "")
	        },
	        children: [BX.create("DIV", {
	          props: {
	            className: "crm-entity-stream-content-detail-zoom-section-inner"
	          },
	          children: [BX.create("DIV", {
	            props: {
	              className: "crm-entity-stream-content-detail-zoom-section-title"
	            },
	            text: tabDescription.title
	          }), BX.create("DIV", {
	            props: {
	              className: "crm-entity-stream-content-detail-zoom-section-time"
	            },
	            text: tabDescription.time
	          })]
	        })],
	        events: {
	          click: function () {
	            this.setActiveTab(tabDescription.id);
	          }.bind(this)
	        }
	      });
	      return this.elements.tabs[tabId];
	    }
	  }, {
	    key: "setActiveTab",
	    value: function setActiveTab(tabId) {
	      if (!this.elements.tabs[tabId]) {
	        throw new Error("Tab " + tabId + " is not found");
	      }
	      for (let id in this.elements.tabs) {
	        if (!this.elements.tabs.hasOwnProperty(id)) {
	          continue;
	        }
	        id = Number.parseInt(id, 10);
	        if (id === tabId) {
	          this.elements.tabs[id].classList.add("crm-entity-stream-content-detail-zoom-section-active");
	        } else {
	          this.elements.tabs[id].classList.remove("crm-entity-stream-content-detail-zoom-section-active");
	        }
	      }
	      this.eventEmitter.emit("onTabChange", {
	        tabId: tabId
	      });
	    }
	  }]);
	  return _class;
	}();

	/** @memberof BX.Crm.Timeline.Actions */
	let OrderModification = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(OrderModification, _History);
	  function OrderModification() {
	    babelHelpers.classCallCheck(this, OrderModification);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OrderModification).call(this));
	  }
	  babelHelpers.createClass(OrderModification, [{
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = OrderModification.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return this.getTextDataParam("TITLE");
	    }
	  }, {
	    key: "getStatusInfo",
	    value: function getStatusInfo() {
	      const statusInfo = {};
	      let value = null;
	      let classCode = null;
	      const fieldName = this.getTextDataParam("CHANGED_ENTITY");
	      const fields = this.getObjectDataParam('FIELDS');
	      const entityData = this.getAssociatedEntityData();
	      if (fieldName === BX.CrmEntityType.names.order) {
	        if (BX.prop.get(fields, 'ORDER_CANCELED') === 'Y') {
	          value = "canceled";
	          classCode = "not-paid";
	        } else if (BX.prop.get(fields, 'ORDER_DONE') === 'Y') {
	          value = "done";
	          classCode = "done";
	        } else if (BX.prop.getString(entityData, "VIEWED", '') === 'Y') {
	          value = "viewed";
	          classCode = "done";
	        } else if (BX.prop.getString(entityData, "SENT", '') === 'Y') {
	          value = "sent";
	          classCode = "sent";
	        }
	      }
	      if (fieldName === BX.CrmEntityType.names.orderpayment) {
	        const psStatusCode = BX.prop.get(fields, 'STATUS_CODE', false);
	        if (psStatusCode) {
	          if (psStatusCode === 'ERROR') {
	            value = "orderPaymentError";
	            classCode = "payment-error";
	          }
	        } else if (BX.prop.getString(entityData, "VIEWED", '') === 'Y') {
	          value = "viewed";
	          classCode = "done";
	        } else if (BX.prop.getString(entityData, "SENT", '') === 'Y') {
	          value = "sent";
	          classCode = "sent";
	        } else {
	          value = BX.prop.get(fields, 'ORDER_PAID') === 'Y' ? "paid" : "unpaid";
	          classCode = BX.prop.get(fields, 'ORDER_PAID') === 'Y' ? "paid" : "not-paid";
	        }
	      } else if (fieldName === BX.CrmEntityType.names.ordershipment && BX.prop.get(fields, 'ORDER_DEDUCTED', false)) {
	        value = BX.prop.get(fields, 'ORDER_DEDUCTED') === 'Y' ? "deducted" : "unshipped";
	        classCode = BX.prop.get(fields, 'ORDER_DEDUCTED') === 'Y' ? "shipped" : "not-shipped";
	      } else if (fieldName === BX.CrmEntityType.names.ordershipment && BX.prop.get(fields, 'ORDER_ALLOW_DELIVERY', false)) {
	        value = BX.prop.get(fields, 'ORDER_ALLOW_DELIVERY') === 'Y' ? "allowedDelivery" : "disallowedDelivery";
	        classCode = BX.prop.get(fields, 'ORDER_ALLOW_DELIVERY') === 'Y' ? "allowed-delivery" : "disallowed-delivery";
	      }
	      if (value) {
	        statusInfo.className = "crm-entity-stream-content-event-" + classCode;
	        statusInfo.message = this.getMessage(value);
	      }
	      return statusInfo;
	    }
	  }, {
	    key: "getHeaderChildren",
	    value: function getHeaderChildren() {
	      const children = [BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event-title"
	        },
	        events: {
	          click: this._headerClickHandler
	        },
	        text: this.getTitle()
	      })];
	      const statusInfo = this.getStatusInfo();
	      if (BX.type.isNotEmptyObject(statusInfo)) {
	        children.push(BX.create("SPAN", {
	          attrs: {
	            className: statusInfo.className
	          },
	          text: statusInfo.message
	        }));
	      }
	      children.push(BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: this.formatTime(this.getCreatedTime())
	      }));
	      return children;
	    }
	  }, {
	    key: "prepareContentDetails",
	    value: function prepareContentDetails() {
	      const entityData = this.getAssociatedEntityData();
	      const entityTypeId = this.getAssociatedEntityTypeId();
	      const entityId = this.getAssociatedEntityId();
	      const title = BX.prop.getString(entityData, "TITLE");
	      const htmlTitle = BX.prop.getString(entityData, "HTML_TITLE", "");
	      const showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
	      const nodes = [];
	      if (title !== "") {
	        const descriptionNode = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-description"
	          }
	        });
	        if (showUrl === "" || entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId()) {
	          descriptionNode.appendChild(BX.create("SPAN", {
	            text: title + " " + htmlTitle
	          }));
	        } else {
	          if (htmlTitle === "") {
	            descriptionNode.appendChild(BX.create("A", {
	              attrs: {
	                href: showUrl
	              },
	              text: title
	            }));
	          } else {
	            descriptionNode.appendChild(BX.create("SPAN", {
	              text: title + " "
	            }));
	            descriptionNode.appendChild(BX.create("A", {
	              attrs: {
	                href: showUrl
	              },
	              text: htmlTitle
	            }));
	          }
	        }
	        const legend = BX.prop.getString(entityData, "LEGEND");
	        if (legend !== "") {
	          descriptionNode.appendChild(BX.create("SPAN", {
	            html: " " + legend
	          }));
	        }
	        const sublegend = BX.prop.getString(entityData, "SUBLEGEND", '');
	        if (sublegend !== '') {
	          descriptionNode.appendChild(BX.create("BR"));
	          descriptionNode.appendChild(BX.create("SPAN", {
	            text: " " + sublegend
	          }));
	        }
	        nodes.push(descriptionNode);
	      }
	      return nodes;
	    }
	  }, {
	    key: "prepareViewedContentDetails",
	    value: function prepareViewedContentDetails() {
	      const entityData = this.getAssociatedEntityData();
	      const entityTypeId = this.getAssociatedEntityTypeId();
	      const entityId = this.getAssociatedEntityId();
	      const title = BX.prop.getString(entityData, "TITLE");
	      const showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
	      const nodes = [];
	      if (title !== "") {
	        const sublegend = BX.prop.getString(entityData, "SUBLEGEND", '');
	        if (sublegend !== "") {
	          const descriptionNode = BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-content-detail-description"
	            },
	            text: sublegend
	          });
	          nodes.push(descriptionNode);
	        }
	        if (entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId()) {
	          nodes.push(BX.create("SPAN", {
	            text: title
	          }));
	        } else {
	          nodes.push(BX.create("A", {
	            attrs: {
	              href: showUrl
	            },
	            text: title
	          }));
	        }
	        const legend = BX.prop.getString(entityData, "LEGEND");
	        if (legend !== "") {
	          nodes.push(BX.create("SPAN", {
	            html: " " + legend
	          }));
	        }
	      }
	      return nodes;
	    }
	  }, {
	    key: "prepareSentContentDetails",
	    value: function prepareSentContentDetails() {
	      const entityData = this.getAssociatedEntityData();
	      const entityTypeId = this.getAssociatedEntityTypeId();
	      const entityId = this.getAssociatedEntityId();
	      const title = BX.prop.getString(entityData, "TITLE");
	      const showUrl = BX.prop.getString(entityData, 'SHOW_URL', '');
	      const destination = BX.prop.getString(entityData, 'DESTINATION_TITLE', '');
	      const nodes = [];
	      if (title !== "") {
	        const detailNode = BX.create('DIV', {
	          attrs: {
	            className: 'crm-entity-stream-content-detail-description'
	          }
	        });
	        if (showUrl === "" || entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId()) {
	          detailNode.appendChild(BX.create("SPAN", {
	            text: title
	          }));
	        } else {
	          detailNode.appendChild(BX.create('A', {
	            attrs: {
	              href: showUrl
	            },
	            text: title
	          }));
	        }
	        const legend = BX.prop.getString(entityData, "LEGEND");
	        if (legend !== "") {
	          detailNode.appendChild(BX.create("SPAN", {
	            html: " " + legend
	          }));
	        }
	        if (destination) {
	          detailNode.appendChild(BX.create('SPAN', {
	            attrs: {
	              className: 'crm-entity-stream-content-detail-order-destination'
	            },
	            text: destination
	          }));
	        }
	        nodes.push(detailNode);
	        const sliderLinkNode = BX.create('A', {
	          attrs: {
	            href: "#"
	          },
	          text: this.getMessage('orderPaymentProcess'),
	          events: {
	            click: BX.proxy(this.startSalescenterApplication, this)
	          }
	        });
	        nodes.push(sliderLinkNode);
	      }
	      return nodes;
	    }
	  }, {
	    key: "startSalescenterApplication",
	    value: function startSalescenterApplication() {
	      BX.loadExt('salescenter.manager').then(function () {
	        const fields = this.getObjectDataParam('FIELDS'),
	          ownerTypeId = BX.prop.get(fields, 'OWNER_TYPE_ID', BX.CrmEntityType.enumeration.deal);
	        let ownerId = BX.prop.get(fields, 'OWNER_ID', 0);
	        const paymentId = BX.prop.get(fields, 'PAYMENT_ID', 0),
	          shipmentId = BX.prop.get(fields, 'SHIPMENT_ID', 0),
	          orderId = BX.prop.get(fields, 'ORDER_ID', 0);

	        // compatibility
	        if (!ownerId) {
	          ownerId = BX.prop.get(fields, 'DEAL_ID', 0);
	        }
	        BX.Salescenter.Manager.openApplication({
	          disableSendButton: '',
	          context: 'deal',
	          ownerTypeId: ownerTypeId,
	          ownerId: ownerId,
	          mode: ownerTypeId === BX.CrmEntityType.enumeration.deal ? 'payment_delivery' : 'payment',
	          templateMode: 'view',
	          orderId: orderId,
	          paymentId: paymentId,
	          shipmentId: shipmentId
	        });
	      }.bind(this));
	    }
	  }, {
	    key: "preparePaidPaymentContentDetails",
	    value: function preparePaidPaymentContentDetails() {
	      const entityData = this.getAssociatedEntityData(),
	        title = BX.prop.getString(entityData, "TITLE"),
	        date = BX.prop.getString(entityData, "DATE", ""),
	        paySystemName = BX.prop.getString(entityData, "PAY_SYSTEM_NAME", ""),
	        sum = BX.prop.getString(entityData, 'SUM', ''),
	        currency = BX.prop.getString(entityData, 'CURRENCY', ''),
	        nodes = [];
	      if (title !== "") {
	        const paymentDetail = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-payment"
	          }
	        });
	        paymentDetail.appendChild(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-payment-value"
	          },
	          children: [BX.create('SPAN', {
	            attrs: {
	              className: "crm-entity-stream-content-detail-payment-text"
	            },
	            html: sum
	          }), BX.create('SPAN', {
	            attrs: {
	              className: "crm-entity-stream-content-detail-payment-currency"
	            },
	            html: currency
	          })]
	        }));
	        const logotip = BX.prop.getString(entityData, "LOGOTIP", null);
	        if (logotip) {
	          paymentDetail.appendChild(BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-content-detail-payment-logo"
	            },
	            style: {
	              backgroundImage: "url(" + encodeURI(logotip) + ")"
	            }
	          }));
	        }
	        nodes.push(paymentDetail);
	        const descriptionNode = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-description"
	          },
	          children: [BX.create('SPAN', {
	            text: date
	          }), BX.create('SPAN', {
	            attrs: {
	              className: "crm-entity-stream-content-detail-description-info"
	            },
	            text: this.getMessage('orderPaySystemTitle')
	          }), BX.create('SPAN', {
	            text: paySystemName
	          })]
	        });
	        nodes.push(descriptionNode);
	      }
	      return nodes;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const fields = this.getObjectDataParam('FIELDS'),
	        isPaid = BX.prop.get(fields, 'ORDER_PAID') === 'Y',
	        isClick = BX.prop.get(fields, 'PAY_SYSTEM_CLICK') === 'Y',
	        isManualContinuePay = BX.prop.get(fields, 'MANUAL_CONTINUE_PAY') === 'Y',
	        isManualAddCheck = BX.prop.get(fields, 'NEED_MANUAL_ADD_CHECK') === 'Y',
	        entityId = this.getAssociatedEntityTypeId();
	      if (entityId === BX.CrmEntityType.enumeration.orderpayment && isPaid) {
	        return this.preparePaidPaymentContent();
	      } else if (entityId === BX.CrmEntityType.enumeration.orderpayment && isClick) {
	        return this.prepareClickedPaymentContent();
	      } else if (entityId === BX.CrmEntityType.enumeration.order && isManualContinuePay) {
	        return this.prepareManualContinuePayContent();
	      } else if (entityId === BX.CrmEntityType.enumeration.orderpayment && isManualAddCheck) {
	        return this.prepareManualAddCheck();
	      }
	      return this.prepareItemOrderContent();
	    }
	  }, {
	    key: "prepareItemOrderContent",
	    value: function prepareItemOrderContent() {
	      const entityData = this.getAssociatedEntityData();
	      const isViewed = BX.prop.getString(entityData, "VIEWED", '') === 'Y';
	      const isSent = BX.prop.getString(entityData, "SENT", '') === 'Y';
	      const fields = this.getObjectDataParam('FIELDS');
	      const psStatusCode = BX.prop.get(fields, 'STATUS_CODE', false);
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section crm-entity-stream-section-history'
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section-icon ' + this.getIconClassName()
	        }
	      }));
	      const content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: this.getHeaderChildren()
	      });
	      let contentChildren = null;
	      if (isViewed) {
	        contentChildren = this.prepareViewedContentDetails();
	      } else if (isSent) {
	        contentChildren = this.prepareSentContentDetails();
	      } else if (psStatusCode === 'ERROR') {
	        contentChildren = this.prepareErrorPaymentContentDetails();
	      } else {
	        contentChildren = this.prepareContentDetails();
	      }
	      content.appendChild(header);
	      content.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: contentChildren
	      }));

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        content.appendChild(authorNode);
	      }
	      //endregion

	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [content]
	      }));
	      return wrapper;
	    }
	  }, {
	    key: "preparePaidPaymentContent",
	    value: function preparePaidPaymentContent() {
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section'
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section-icon crm-entity-stream-section-icon-wallet'
	        }
	      }));
	      const header = [BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event-title"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          events: {
	            click: this._headerClickHandler
	          },
	          text: this.getMessage('orderPaymentSuccessTitle')
	        })]
	      })];
	      header.push(BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: this.formatTime(this.getCreatedTime())
	      }));
	      const content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      const headerWrap = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: header
	      });
	      const contentChildren = this.preparePaidPaymentContentDetails();
	      content.appendChild(headerWrap);
	      content.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: contentChildren
	      }));

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        content.appendChild(authorNode);
	      }
	      //endregion

	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [content]
	      }));
	      return wrapper;
	    }
	  }, {
	    key: "prepareErrorPaymentContentDetails",
	    value: function prepareErrorPaymentContentDetails() {
	      const entityData = this.getAssociatedEntityData(),
	        date = BX.prop.getString(entityData, 'DATE', ''),
	        fields = this.getObjectDataParam('FIELDS'),
	        paySystemName = BX.prop.getString(fields, 'PAY_SYSTEM_NAME', ''),
	        paySystemError = BX.prop.getString(fields, 'STATUS_DESCRIPTION', ''),
	        nodes = [];
	      const descriptionNode = BX.create('DIV', {
	        attrs: {
	          className: 'crm-entity-stream-content-detail-description'
	        },
	        children: [BX.create('SPAN', {
	          text: date
	        }), BX.create('SPAN', {
	          attrs: {
	            className: 'crm-entity-stream-content-detail-description-info'
	          },
	          text: this.getMessage('orderPaySystemTitle')
	        }), BX.create('SPAN', {
	          text: paySystemName
	        })]
	      });
	      nodes.push(descriptionNode);
	      const errorDetailNode = BX.create('DIV', {
	        attrs: {
	          className: 'crm-entity-stream-content-event-payment-initiate-pay-error'
	        },
	        text: this.getMessage('orderPaymentStatusErrorReason').replace("#PAYSYSTEM_ERROR#", paySystemError)
	      });
	      nodes.push(errorDetailNode);
	      return nodes;
	    }
	  }, {
	    key: "prepareClickedPaymentContent",
	    value: function prepareClickedPaymentContent() {
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section crm-entity-stream-section-history'
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section-icon ' + this.getIconClassName()
	        }
	      }));
	      const header = [BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event-title"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          events: {
	            click: this._headerClickHandler
	          },
	          text: this.getTitle()
	        })]
	      })];
	      header.push(BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: this.formatTime(this.getCreatedTime())
	      }));
	      const content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      const headerWrap = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: header
	      });
	      const contentChildren = this.prepareClickedPaymentContentDetails();
	      content.appendChild(headerWrap);
	      content.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: contentChildren
	      }));

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        content.appendChild(authorNode);
	      }
	      //endregion

	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [content]
	      }));
	      return wrapper;
	    }
	  }, {
	    key: "prepareClickedPaymentContentDetails",
	    value: function prepareClickedPaymentContentDetails() {
	      const fields = this.getObjectDataParam('FIELDS'),
	        paySystemName = BX.prop.getString(fields, 'PAY_SYSTEM_NAME', ''),
	        nodes = [];
	      if (paySystemName !== '') {
	        const descriptionNode = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-description"
	          }
	        });
	        descriptionNode.appendChild(BX.create('SPAN', {
	          attrs: {
	            className: "crm-entity-stream-content-clicked-description-info"
	          },
	          text: this.getMessage('orderPaymentPaySystemClick')
	        }));
	        descriptionNode.appendChild(BX.create('SPAN', {
	          attrs: {
	            className: "crm-entity-stream-content-clicked-description-name"
	          },
	          text: paySystemName
	        }));
	        nodes.push(descriptionNode);
	      }
	      return nodes;
	    }
	  }, {
	    key: "prepareManualContinuePayContent",
	    value: function prepareManualContinuePayContent() {
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-advice'
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section-icon crm-entity-stream-section-icon-advice'
	        },
	        children: [BX.create('i')]
	      }));
	      const content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-advice-info"
	        },
	        text: this.getMessage('orderManualContinuePay')
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-advice-content"
	        },
	        children: [content]
	      }));
	      return wrapper;
	    }
	  }, {
	    key: "prepareManualAddCheck",
	    value: function prepareManualAddCheck() {
	      const entityData = this.getAssociatedEntityData();
	      const showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-advice'
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section-icon crm-entity-stream-section-icon-advice'
	        }
	      }));
	      const htmlTitle = this.getMessage('orderManualAddCheck').replace("#HREF#", showUrl);
	      const content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-advice-info"
	        },
	        html: htmlTitle
	      });
	      const link = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-advice-info"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-target",
	            href: "#"
	          },
	          events: {
	            click: BX.delegate(function (e) {
	              top.BX.Helper.show('redirect=detail&code=13742126');
	              e.preventDefault ? e.preventDefault() : e.returnValue = false;
	            })
	          },
	          html: this.getMessage('orderManualAddCheckHelpLink')
	        })]
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-advice-content"
	        },
	        children: [content, link]
	      }));
	      return wrapper;
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return 'crm-entity-stream-section-icon-store';
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new OrderModification();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return OrderModification;
	}(History);
	babelHelpers.defineProperty(OrderModification, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */
	let ExternalNoticeModification = /*#__PURE__*/function (_OrderModification) {
	  babelHelpers.inherits(ExternalNoticeModification, _OrderModification);
	  function ExternalNoticeModification() {
	    babelHelpers.classCallCheck(this, ExternalNoticeModification);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ExternalNoticeModification).call(this));
	  }
	  babelHelpers.createClass(ExternalNoticeModification, [{
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return 'crm-entity-stream-section-icon-restApp';
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new ExternalNoticeModification();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return ExternalNoticeModification;
	}(OrderModification);

	/** @memberof BX.Crm.Timeline.Items */
	let ExternalNoticeStatusModification = /*#__PURE__*/function (_ExternalNoticeModifi) {
	  babelHelpers.inherits(ExternalNoticeStatusModification, _ExternalNoticeModifi);
	  function ExternalNoticeStatusModification() {
	    babelHelpers.classCallCheck(this, ExternalNoticeStatusModification);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ExternalNoticeStatusModification).call(this));
	  }
	  babelHelpers.createClass(ExternalNoticeStatusModification, [{
	    key: "prepareContentDetails",
	    value: function prepareContentDetails() {
	      const nodes = [];
	      const contentChildren = [];
	      if (BX.type.isNotEmptyString(this.getTextDataParam("START_NAME"))) {
	        contentChildren.push(BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-detain-info-status"
	          },
	          text: this.getTextDataParam("START_NAME")
	        }));
	        contentChildren.push(BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-info-separator-icon"
	          }
	        }));
	      }
	      if (BX.type.isNotEmptyString(this.getTextDataParam("FINISH_NAME"))) {
	        contentChildren.push(BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-detain-info-status"
	          },
	          text: this.getTextDataParam("FINISH_NAME")
	        }));
	      }
	      nodes.push(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-info"
	        },
	        children: contentChildren
	      }));
	      return nodes;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new ExternalNoticeStatusModification();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return ExternalNoticeStatusModification;
	}(ExternalNoticeModification);

	/** @memberof BX.Crm.Timeline.Items */
	let Creation = /*#__PURE__*/function (_HistoryItem) {
	  babelHelpers.inherits(Creation, _HistoryItem);
	  function Creation() {
	    babelHelpers.classCallCheck(this, Creation);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Creation).call(this));
	  }
	  babelHelpers.createClass(Creation, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Creation.prototype), "doInitialize", this).call(this);
	      if (!(this._activityEditor instanceof BX.CrmActivityEditor)) {
	        throw "Creation. The field 'activityEditor' is not assigned.";
	      }
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      const entityTypeId = this.getAssociatedEntityTypeId();
	      const entityData = this.getAssociatedEntityData();
	      if (entityTypeId === BX.CrmEntityType.enumeration.activity) {
	        const typeId = BX.prop.getInteger(entityData, "TYPE_ID");
	        const title = this.getMessage(typeId === BX.CrmActivityType.task ? "task" : "activity");
	        return title.replace(/#TITLE#/gi, this.cutOffText(BX.prop.getString(entityData, "SUBJECT")), 64);
	      }
	      if (entityTypeId === BX.CrmEntityType.enumeration.storeDocument) {
	        const docType = BX.prop.getString(entityData, "DOC_TYPE");
	        if (docType === 'A') {
	          return this.getMessage('arrivalDocument');
	        }
	        if (docType === 'S') {
	          return this.getMessage('storeAdjustmentDocument');
	        }
	        if (docType === 'M') {
	          return this.getMessage('movingDocument');
	        }
	        if (docType === 'D') {
	          return this.getMessage('deductDocument');
	        }
	        if (docType === 'W') {
	          return this.getMessage('shipmentDocument');
	        }
	        return '';
	      }
	      const entityTypeName = BX.CrmEntityType.resolveName(this.getAssociatedEntityTypeId()).toLowerCase();
	      let msg = this.getMessage(entityTypeName);
	      const isMessageNotFound = msg === entityTypeName;
	      if (!BX.type.isNotEmptyString(msg) || isMessageNotFound) {
	        msg = this.getTextDataParam("TITLE");
	      }
	      return msg;
	    }
	  }, {
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "crm-entity-stream-section-createEntity";
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const entityTypeId = this.getAssociatedEntityTypeId();
	      if (entityTypeId === BX.CrmEntityType.enumeration.ordershipment || entityTypeId === BX.CrmEntityType.enumeration.orderpayment) {
	        const data = this.getData();
	        data.TYPE_CATEGORY_ID = Item.modification;
	        if (data.hasOwnProperty('ASSOCIATED_ENTITY')) {
	          data.ASSOCIATED_ENTITY.HTML_TITLE = '';
	        }
	        const createOrderEntityItem = this._history.createOrderEntityItem(data);
	        return createOrderEntityItem.prepareContent();
	      }
	      return babelHelpers.get(babelHelpers.getPrototypeOf(Creation.prototype), "prepareContent", this).call(this);
	    }
	  }, {
	    key: "prepareContentDetails",
	    value: function prepareContentDetails() {
	      const entityTypeId = this.getAssociatedEntityTypeId();
	      const entityId = this.getAssociatedEntityId();
	      const entityData = this.getAssociatedEntityData();
	      if (entityTypeId === BX.CrmEntityType.enumeration.activity) {
	        const link = BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          html: this.cutOffText(BX.prop.getString(entityData, "DESCRIPTION_RAW"), 128)
	        });
	        BX.bind(link, "click", this._headerClickHandler);
	        return [link];
	      }
	      const title = BX.prop.getString(entityData, "TITLE", "");
	      let htmlTitle = BX.prop.getString(entityData, "HTML_TITLE", "");
	      const showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
	      if (entityTypeId === BX.CrmEntityType.enumeration.deal && BX.prop.getObject(entityData, "ORDER", null)) {
	        const orderData = BX.prop.getObject(entityData, "ORDER", null);
	        htmlTitle = this.getMessage('dealOrderTitle').replace("#ORDER_ID#", orderData.ID).replace("#DATE_TIME#", orderData.ORDER_DATE).replace("#HREF#", orderData.SHOW_URL).replace("#PRICE_WITH_CURRENCY#", orderData.SUM);
	      }
	      if (title !== "" || htmlTitle !== "") {
	        const nodes = [];
	        if (showUrl === "" || entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId()) {
	          const spanAttrs = htmlTitle !== "" ? {
	            html: htmlTitle
	          } : {
	            text: title
	          };
	          nodes.push(BX.create("SPAN", spanAttrs));
	        } else {
	          let linkAttrs = {
	            attrs: {
	              href: showUrl
	            },
	            text: title
	          };
	          if (htmlTitle !== "") {
	            linkAttrs = {
	              attrs: {
	                href: showUrl
	              },
	              html: htmlTitle
	            };
	          }
	          nodes.push(BX.create("A", linkAttrs));
	        }
	        const legend = this.getTextDataParam("LEGEND");
	        if (legend !== "") {
	          nodes.push(BX.create("BR"));
	          nodes.push(BX.create("SPAN", {
	            text: legend
	          }));
	        }
	        const baseEntityData = this.getObjectDataParam("BASE");
	        const baseEntityInfo = BX.prop.getObject(baseEntityData, "ENTITY_INFO");
	        if (baseEntityInfo) {
	          nodes.push(BX.create("BR"));
	          nodes.push(BX.create("SPAN", {
	            text: BX.prop.getString(baseEntityData, "CAPTION") + ": "
	          }));
	          nodes.push(BX.create("A", {
	            attrs: {
	              href: BX.prop.getString(baseEntityInfo, "SHOW_URL", "#")
	            },
	            text: BX.prop.getString(baseEntityInfo, "TITLE", "")
	          }));
	        }
	        return nodes;
	      }
	      return [];
	    }
	  }, {
	    key: "view",
	    value: function view() {
	      const entityTypeId = this.getAssociatedEntityTypeId();
	      if (entityTypeId === BX.CrmEntityType.enumeration.activity) {
	        const entityData = this.getAssociatedEntityData();
	        const id = BX.prop.getInteger(entityData, "ID", 0);
	        if (id > 0) {
	          this._activityEditor.viewActivity(id);
	        }
	      }
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = Creation.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Creation();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Creation;
	}(History);
	babelHelpers.defineProperty(Creation, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */
	let Restoration = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(Restoration, _History);
	  function Restoration() {
	    babelHelpers.classCallCheck(this, Restoration);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Restoration).call(this));
	  }
	  babelHelpers.createClass(Restoration, [{
	    key: "getTitle",
	    value: function getTitle() {
	      return this.getTextDataParam("TITLE");
	    }
	  }, {
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "crm-entity-stream-section-restoreEntity";
	    }
	  }, {
	    key: "prepareContentDetails",
	    value: function prepareContentDetails() {
	      const entityData = this.getAssociatedEntityData();
	      const title = BX.prop.getString(entityData, "TITLE");
	      return title !== "" ? [BX.create("SPAN", {
	        text: title
	      })] : [];
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = Restoration.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Restoration();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Restoration;
	}(History);
	babelHelpers.defineProperty(Restoration, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */
	let Relation = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(Relation, _History);
	  function Relation() {
	    babelHelpers.classCallCheck(this, Relation);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Relation).call(this));
	  }
	  babelHelpers.createClass(Relation, [{
	    key: "getTitle",
	    value: function getTitle() {
	      return this.getMessage('title');
	    }
	  }, {
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "crm-entity-stream-section-createEntity";
	    }
	  }, {
	    key: "prepareContentDetails",
	    value: function prepareContentDetails() {
	      const entityData = this.getAssociatedEntityData();
	      let link = BX.prop.getString(entityData, "SHOW_URL", "");
	      if (link.indexOf('/') !== 0) {
	        link = '#';
	      }
	      const content = this.getMessage('contentTemplate').replace('#ENTITY_TYPE_CAPTION#', BX.Text.encode(BX.prop.getString(entityData, 'ENTITY_TYPE_CAPTION', ''))).replace('#LEGEND#', '').replace('#LINK#', BX.Text.encode(link)).replace('#LINK_TITLE#', BX.Text.encode(BX.prop.getString(entityData, "TITLE", '')));
	      const nodes = [];
	      nodes.push(BX.create('SPAN', {
	        html: content
	      }));
	      return nodes;
	    }
	  }]);
	  return Relation;
	}(History);

	/** @memberof BX.Crm.Timeline.Items */
	let Link = /*#__PURE__*/function (_Relation) {
	  babelHelpers.inherits(Link, _Relation);
	  function Link() {
	    babelHelpers.classCallCheck(this, Link);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Link).call(this));
	  }
	  babelHelpers.createClass(Link, [{
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-link";
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = Link.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Link();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Link;
	}(Relation);
	babelHelpers.defineProperty(Link, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */
	let Unlink = /*#__PURE__*/function (_Relation) {
	  babelHelpers.inherits(Unlink, _Relation);
	  function Unlink() {
	    babelHelpers.classCallCheck(this, Unlink);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Unlink).call(this));
	  }
	  babelHelpers.createClass(Unlink, [{
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-unlink";
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = Unlink.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Unlink();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Unlink;
	}(Relation);
	babelHelpers.defineProperty(Unlink, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */
	let Mark$1 = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(Mark$$1, _History);
	  function Mark$$1() {
	    babelHelpers.classCallCheck(this, Mark$$1);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Mark$$1).call(this));
	  }
	  babelHelpers.createClass(Mark$$1, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Mark$$1.prototype), "doInitialize", this).call(this);
	      if (!(this._activityEditor instanceof BX.CrmActivityEditor)) {
	        throw "Mark. The field 'activityEditor' is not assigned.";
	      }
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = Mark$$1.messages;
	      if (m.hasOwnProperty(name)) {
	        return m[name];
	      }
	      return babelHelpers.get(babelHelpers.getPrototypeOf(Mark$$1.prototype), "getMessage", this).call(this, name);
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      let title = "";
	      const entityData = this.getAssociatedEntityData();
	      const associatedEntityTypeId = this.getAssociatedEntityTypeId();
	      const typeCategoryId = this.getTypeCategoryId();
	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        const entityTypeId = BX.prop.getInteger(entityData, "TYPE_ID", 0);
	        const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	        const activityProviderId = BX.prop.getString(entityData, "PROVIDER_ID", '');
	        if (entityTypeId === BX.CrmActivityType.email) {
	          if (typeCategoryId === Mark.success) {
	            title = this.getMessage((direction === BX.CrmActivityDirection.incoming ? "incomingEmail" : "outgoingEmail") + "SuccessMark");
	          } else if (typeCategoryId === Mark.renew) {
	            title = this.getMessage((direction === BX.CrmActivityDirection.incoming ? "incomingEmail" : "outgoingEmail") + "RenewMark");
	          }
	        } else if (entityTypeId === BX.CrmActivityType.call) {
	          if (typeCategoryId === Mark.success) {
	            title = this.getMessage((direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall") + "SuccessMark");
	          } else if (typeCategoryId === Mark.renew) {
	            title = this.getMessage((direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall") + "RenewMark");
	          }
	        } else if (entityTypeId === BX.CrmActivityType.meeting) {
	          if (typeCategoryId === Mark.success) {
	            title = this.getMessage("meetingSuccessMark");
	          } else if (typeCategoryId === Mark.renew) {
	            title = this.getMessage("meetingRenewMark");
	          }
	        } else if (entityTypeId === BX.CrmActivityType.task) {
	          if (typeCategoryId === Mark.success) {
	            title = this.getMessage("taskSuccessMark");
	          } else if (typeCategoryId === Mark.renew) {
	            title = this.getMessage("taskRenewMark");
	          }
	        } else if (entityTypeId === BX.CrmActivityType.provider) {
	          if (activityProviderId === 'CRM_REQUEST') {
	            if (typeCategoryId === Mark.success) {
	              title = this.getMessage("requestSuccessMark");
	            } else if (typeCategoryId === Mark.renew) {
	              title = this.getMessage("requestRenewMark");
	            }
	          } else if (typeCategoryId === Mark.success) {
	            title = this.getMessage("webformSuccessMark");
	          } else if (typeCategoryId === Mark.renew) {
	            title = this.getMessage("webformRenewMark");
	          }
	        }
	      } else if (associatedEntityTypeId === BX.CrmEntityType.enumeration.deal) {
	        if (typeCategoryId === Mark.success) {
	          title = this.getMessage("dealSuccessMark");
	        } else if (typeCategoryId === Mark.failed) {
	          title = this.getMessage("dealFailedMark");
	        }
	      } else if (associatedEntityTypeId === BX.CrmEntityType.enumeration.order) {
	        if (typeCategoryId === Mark.success) {
	          title = this.getMessage("orderSuccessMark");
	        } else if (typeCategoryId === Mark.failed) {
	          title = this.getMessage("orderFailedMark");
	        }
	      } else {
	        if (BX.CrmEntityType.isDefined(associatedEntityTypeId)) {
	          if (typeCategoryId === Mark.success) {
	            title = this.getMessage('entitySuccessMark');
	          } else if (typeCategoryId === Mark.failed) {
	            title = this.getMessage('entityFailedMark');
	          }
	        }
	      }
	      return title;
	    }
	  }, {
	    key: "prepareTitleLayout",
	    value: function prepareTitleLayout() {
	      const associatedEntityTypeId = this.getAssociatedEntityTypeId();
	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.order) {
	        return BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-event-title"
	          },
	          text: this.getTitle()
	        });
	      } else {
	        return BX.create("A", {
	          attrs: {
	            href: "#",
	            className: "crm-entity-stream-content-event-title"
	          },
	          events: {
	            "click": this._headerClickHandler
	          },
	          text: this.getTitle()
	        });
	      }
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const entityData = this.getAssociatedEntityData();
	      const associatedEntityTypeId = this.getAssociatedEntityTypeId();
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-completed"
	        }
	      });
	      if (this.isFixed()) BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

	      //region Context Menu
	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      }
	      //endregion

	      const content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      const header = this.prepareHeaderLayout();
	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        const entityTypeId = BX.prop.getInteger(entityData, "TYPE_ID", 0);
	        let iconClassName = "crm-entity-stream-section-icon";
	        if (entityTypeId === BX.CrmActivityType.email) {
	          iconClassName += " crm-entity-stream-section-icon-email";
	        } else if (entityTypeId === BX.CrmActivityType.call) {
	          iconClassName += " crm-entity-stream-section-icon-call";
	        } else if (entityTypeId === BX.CrmActivityType.meeting) {
	          iconClassName += " crm-entity-stream-section-icon-meeting";
	        } else if (entityTypeId === BX.CrmActivityType.task) {
	          iconClassName += " crm-entity-stream-section-icon-task";
	        } else if (entityTypeId === BX.CrmActivityType.provider) {
	          const providerId = BX.prop.getString(entityData, "PROVIDER_ID", "");
	          if (providerId === "CRM_WEBFORM") {
	            iconClassName += " crm-entity-stream-section-icon-crmForm";
	          }
	        }
	        wrapper.appendChild(BX.create("DIV", {
	          attrs: {
	            className: iconClassName
	          }
	        }));
	        content.appendChild(header);
	        const detailWrapper = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail"
	          }
	        });
	        content.appendChild(detailWrapper);
	        detailWrapper.appendChild(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-title"
	          },
	          children: [BX.create("A", {
	            attrs: {
	              href: "#"
	            },
	            events: {
	              "click": this._headerClickHandler
	            },
	            text: this.cutOffText(BX.prop.getString(entityData, "SUBJECT", ""), 128)
	          })]
	        }));
	        const summary = this.getTextDataParam("SUMMARY");
	        if (summary !== "") {
	          detailWrapper.appendChild(BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-content-detail-description"
	            },
	            text: summary
	          }));
	        }
	      } else if (associatedEntityTypeId === BX.CrmEntityType.enumeration.order) {
	        wrapper.appendChild(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info"
	          }
	        }));
	        content.appendChild(header);
	        content.appendChild(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail"
	          },
	          text: this.cutOffText(this.getTextDataParam("MESSAGE"), 128)
	        }));
	      } else {
	        wrapper.appendChild(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info"
	          }
	        }));
	        content.appendChild(header);
	        const innerWrapper = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail"
	          }
	        });
	        const associatedEntityTitle = this.cutOffText(BX.prop.getString(entityData, "TITLE", ""), 128);
	        if (BX.CrmEntityType.isDefined(associatedEntityTypeId)) {
	          let link = BX.prop.getString(entityData, 'SHOW_URL', '');
	          if (link.indexOf('/') !== 0) {
	            link = '#';
	          }
	          const contentTemplate = this.getMessage('entityContentTemplate').replace('#ENTITY_TYPE_CAPTION#', BX.Text.encode(BX.prop.getString(entityData, 'ENTITY_TYPE_CAPTION', ''))).replace('#LINK#', BX.Text.encode(link)).replace('#LINK_TITLE#', BX.Text.encode(associatedEntityTitle));
	          innerWrapper.appendChild(BX.create('SPAN', {
	            html: contentTemplate
	          }));
	        } else {
	          innerWrapper.innerText = associatedEntityTitle;
	        }
	        content.appendChild(innerWrapper);
	      }

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        content.appendChild(authorNode);
	      }
	      //endregion

	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [content]
	      }));
	      if (!this.isReadOnly()) wrapper.appendChild(this.prepareFixedSwitcherLayout());
	      return wrapper;
	    }
	  }, {
	    key: "prepareContextMenuItems",
	    value: function prepareContextMenuItems() {
	      const menuItems = [];
	      if (!this.isReadOnly()) {
	        if (this.isFixed() || this._fixedHistory.findItemById(this._id)) menuItems.push({
	          id: "unfasten",
	          text: this.getMessage("menuUnfasten"),
	          onclick: BX.delegate(this.unfasten, this)
	        });else menuItems.push({
	          id: "fasten",
	          text: this.getMessage("menuFasten"),
	          onclick: BX.delegate(this.fasten, this)
	        });
	      }
	      return menuItems;
	    }
	  }, {
	    key: "view",
	    value: function view() {
	      const entityData = this.getAssociatedEntityData();
	      const associatedEntityTypeId = this.getAssociatedEntityTypeId();
	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        const id = BX.prop.getInteger(entityData, "ID", 0);
	        if (id > 0) {
	          this._activityEditor.viewActivity(id);
	        }
	      } else {
	        const showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
	        if (showUrl !== "") {
	          BX.Crm.Page.open(showUrl);
	        }
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Mark$$1();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Mark$$1;
	}(History);
	babelHelpers.defineProperty(Mark$1, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */
	let Comment$1 = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(Comment, _History);
	  function Comment() {
	    var _this;
	    babelHelpers.classCallCheck(this, Comment);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Comment).call(this));
	    _this._isCollapsed = false;
	    _this._isMenuShown = false;
	    _this._isFixed = false;
	    _this._hasFiles = false;
	    _this._postForm = null;
	    _this._editor = null;
	    _this._commentMessage = '';
	    _this._mode = EditorMode.view;
	    _this._streamContentEventBlock = '';
	    _this._playerWrappers = {};
	    BX.Event.EventEmitter.subscribe("BX.Disk.Files:onShowFiles", BX.delegate(_this.addPlayer, babelHelpers.assertThisInitialized(_this)));
	    return _this;
	  }
	  babelHelpers.createClass(Comment, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Comment.prototype), "doInitialize", this).call(this);
	      this._hasFiles = this.getTextDataParam("HAS_FILES") === 'Y';
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return this.getMessage("comment");
	    }
	  }, {
	    key: "onPlayerDummyClick",
	    value: function onPlayerDummyClick(file) {
	      const playerWrapper = this._playerWrappers[file.id];
	      const stubNode = playerWrapper.querySelector(".crm-audio-cap-wrap");
	      if (stubNode) {
	        BX.addClass(stubNode, "crm-audio-cap-wrap-loader");
	      }
	      this._history.getManager().getAudioPlaybackRateSelector().addPlayer(this._history.getManager().loadMediaPlayer("history_" + this.getId() + '_' + file.id, file.url, 'audio/mp3', playerWrapper, null, {
	        playbackRate: this._history.getManager().getAudioPlaybackRateSelector().getRate()
	      }));
	    }
	  }, {
	    key: "addPlayer",
	    value: function addPlayer(event) {
	      if (event.data.entityValueId === parseInt(this.getId(), 10)) {
	        this.files = event.data.files;
	        event.data.files.forEach(function (file) {
	          if (file.extension === 'mp3') {
	            if (this._playerWrappers[file.id]) {
	              return;
	            }
	            const callInfoWrapper = BX.create("DIV", {
	              attrs: {
	                className: "crm-entity-stream-content-detail-call crm-entity-stream-content-detail-call-inline"
	              }
	            });
	            this._streamContentEventBlock.appendChild(callInfoWrapper);
	            this._playerWrappers[file.id] = this._history.getManager().renderAudioDummy(null, this.onPlayerDummyClick.bind(this, file));
	            this._playerWrappers[file.id].firstElementChild.classList.add("crm-audio-cap-wrap-without-duration-text");
	            callInfoWrapper.appendChild(this._playerWrappers[file.id]);
	            callInfoWrapper.appendChild(this._history.getManager().getAudioPlaybackRateSelector().render());
	          }
	        }.bind(this));
	      }
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-comment"
	        }
	      });
	      if (this.isReadOnly()) {
	        BX.addClass(wrapper, "crm-entity-stream-section-comment-read-only");
	      }
	      if (this.isFixed()) BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-comment"
	        }
	      }));

	      //region Context Menu
	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      }
	      //endregion

	      this._streamContentEventBlock = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      const header = this.prepareHeaderLayout();
	      this._streamContentEventBlock.appendChild(header);
	      if (!this.isReadOnly()) wrapper.appendChild(this.prepareFixedSwitcherLayout());
	      const detailChildren = [];
	      if (this._mode !== EditorMode.edit) {
	        this._commentWrapper = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-description"
	          }
	        });
	        BX.html(this._commentWrapper, this.getTextDataParam("COMMENT", ""));
	        detailChildren.push(this._commentWrapper);
	        if (!this.isReadOnly()) {
	          BX.bind(this._commentWrapper, "click", BX.delegate(this.switchToEditMode, this));
	          BX.bind(header, "click", BX.delegate(this.switchToEditMode, this));
	        }
	      } else {
	        if (!BX.type.isDomNode(this._editorContainer)) this._editorContainer = BX.create("div", {
	          attrs: {
	            className: "crm-entity-stream-section-comment-editor"
	          }
	        });
	        detailChildren.push(this._editorContainer);
	        const buttons = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-comment-edit-btn-container"
	          },
	          children: [BX.create("button", {
	            attrs: {
	              className: "ui-btn ui-btn-xs ui-btn-primary"
	            },
	            html: this.getMessage("send"),
	            events: {
	              click: BX.delegate(this.save, this)
	            }
	          }), BX.create("a", {
	            attrs: {
	              className: "ui-btn ui-btn-xs ui-btn-link"
	            },
	            html: this.getMessage("cancel"),
	            events: {
	              click: BX.delegate(this.switchToViewMode, this)
	            }
	          })]
	        });
	        detailChildren.push(buttons);
	      }
	      this._streamContentEventBlock.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: detailChildren
	      }));

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        this._streamContentEventBlock.appendChild(authorNode);
	      }
	      //endregion
	      const cleanText = this.getTextDataParam("TEXT", "");
	      const _hasInlineAttachment = this.getTextDataParam("HAS_INLINE_ATTACHMENT", "") === 'Y';
	      if (cleanText.length <= 128 && !_hasInlineAttachment || this._mode === EditorMode.edit) {
	        this._isCollapsed = false;
	        wrapper.appendChild(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-content"
	          },
	          children: [this._streamContentEventBlock]
	        }));
	      } else {
	        this._isCollapsed = true;
	        wrapper.appendChild(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-content crm-entity-stream-section-content-collapsed"
	          },
	          children: [this._streamContentEventBlock]
	        }));
	        wrapper.querySelector(".crm-entity-stream-content-event").appendChild(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-content-expand-btn-container"
	          },
	          children: [BX.create("A", {
	            attrs: {
	              className: "crm-entity-stream-section-content-expand-btn",
	              href: "#"
	            },
	            events: {
	              click: BX.delegate(this.onExpandButtonClick, this)
	            },
	            text: this.getMessage("expand")
	          })]
	        }));
	      }
	      if (this._mode === EditorMode.view && this._hasFiles) {
	        this._textLoaded = false;
	        this._fileBlock = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-files-inner"
	          },
	          children: [BX.create("DIV", {
	            attrs: {
	              className: "crm-timeline-wait"
	            }
	          })]
	        });
	        wrapper.querySelector(".crm-entity-stream-section-content").appendChild(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-files"
	          },
	          children: [this._fileBlock]
	        }));
	        BX.ready(BX.delegate(function () {
	          window.setTimeout(BX.delegate(function () {
	            this.loadContent(this._fileBlock, "GET_FILE_BLOCK");
	          }, this), 100);
	        }, this));
	      }
	      return wrapper;
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {
	      if (this._mode === EditorMode.view && BX.type.isDomNode(this._commentWrapper)) {
	        this.registerImages(this._commentWrapper);
	        if (!BX.getClass('BX.Disk.apiVersion')) {
	          BX.viewElementBind(this._commentWrapper, {
	            showTitle: true
	          }, function (node) {
	            return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer') || node.getAttribute('data-bx-image'));
	          });
	        }
	      }
	    }
	  }, {
	    key: "loadContent",
	    value: function loadContent(node, type) {
	      if (!BX.type.isDomNode(node)) return;
	      BX.ajax({
	        url: this._history._serviceUrl,
	        method: "POST",
	        dataType: "json",
	        data: {
	          "ACTION": "GET_COMMENT_CONTENT",
	          "ID": this.getId(),
	          "ENTITY_TYPE_ID": this.getOwnerTypeId(),
	          "ENTITY_ID": this.getOwnerId(),
	          "TYPE": type
	        },
	        onsuccess: BX.delegate(function (result) {
	          if (BX.type.isNotEmptyString(result.ERROR) && type === 'GET_FILE_BLOCK') {
	            BX.remove(node);
	            return;
	          }
	          if (BX.type.isNotEmptyString(result.BLOCK)) {
	            const promise = BX.html(node, result.BLOCK);
	            promise.then(BX.delegate(function () {
	              this.registerImages(node);
	              BX.LazyLoad.showImages();
	            }, this));
	          }
	        }, this)
	      });
	    }
	  }, {
	    key: "loadEditor",
	    value: function loadEditor() {
	      this._editorName = 'CrmTimeLineComment' + this._id + BX.util.getRandomString(4);
	      if (this._postForm) {
	        this._postForm.oEditor.SetContent(this._commentMessage);
	        this._editor.ReInitIframe();
	        return;
	      }
	      const actionData = {
	        data: {
	          id: this._id,
	          name: this._editorName
	        }
	      };
	      BX.ajax.runAction("crm.api.timeline.loadEditor", actionData).then(this.onLoadEditorSuccess.bind(this)).catch(this.switchToViewMode.bind(this));
	    }
	  }, {
	    key: "onLoadEditorSuccess",
	    value: function onLoadEditorSuccess(result) {
	      if (!BX.type.isDomNode(this._editorContainer)) this._editorContainer = BX.create("div", {
	        attrs: {
	          className: "crm-entity-stream-section-comment-editor"
	        }
	      });
	      const html = BX.prop.getString(BX.prop.getObject(result, "data", {}), "html", '');
	      BX.html(this._editorContainer, html).then(BX.delegate(this.showEditor, this));
	    }
	  }, {
	    key: "showEditor",
	    value: function showEditor() {
	      if (LHEPostForm) {
	        window.setTimeout(BX.delegate(function () {
	          this._postForm = LHEPostForm.getHandler(this._editorName);
	          this._editor = BXHtmlEditor.Get(this._editorName);
	          BX.onCustomEvent(this._postForm.eventNode, 'OnShowLHE', [true]);
	          this._commentMessage = this._postForm.oEditor.GetContent();
	        }, this), 0);
	      }
	    }
	  }, {
	    key: "registerImages",
	    value: function registerImages(node) {
	      const commentImages = node.querySelectorAll('[data-bx-viewer="image"]');
	      const commentImagesLength = commentImages.length;
	      const idsList = [];
	      if (commentImagesLength > 0) {
	        for (let i = 0; i < commentImagesLength; ++i) {
	          if (BX.type.isDomNode(commentImages[i])) {
	            commentImages[i].id += BX.util.getRandomString(4);
	            idsList.push(commentImages[i].id);
	          }
	        }
	        if (idsList.length > 0) {
	          BX.LazyLoad.registerImages(idsList);
	        }
	      }
	      BX.LazyLoad.registerImages(idsList);
	    }
	  }, {
	    key: "toggleMode",
	    value: function toggleMode(type) {
	      this._mode = parseInt(type);
	      this._hasFiles = this.getTextDataParam("HAS_FILES") === 'Y';
	      this.refreshLayout();
	      this.closeContextMenu();
	    }
	  }, {
	    key: "switchToViewMode",
	    value: function switchToViewMode(e) {
	      // if (LHEPostForm)
	      // 	LHEPostForm.unsetHandler(this._editorName);
	      this.toggleMode(EditorMode.view);
	    }
	  }, {
	    key: "switchToEditMode",
	    value: function switchToEditMode(e) {
	      const tagName = e.target.tagName.toLowerCase();
	      if (tagName === 'a' || tagName === 'img' || BX.hasClass(e.target, "feed-con-file-changes-link-more") || BX.hasClass(e.target, "feed-com-file-inline") || BX.type.isNotEmptyString(document.getSelection().toString())) {
	        return;
	      }
	      this.toggleMode(EditorMode.edit);
	      window.setTimeout(BX.delegate(function () {
	        this.loadEditor();
	      }, this), 100);
	    }
	  }, {
	    key: "prepareContextMenuItems",
	    value: function prepareContextMenuItems() {
	      if (this._isMenuShown) {
	        return;
	      }
	      const menuItems = [];
	      if (!this.isReadOnly()) {
	        if (this._mode !== EditorMode.edit) {
	          menuItems.push({
	            id: "edit",
	            text: this.getMessage("menuEdit"),
	            onclick: BX.delegate(this.switchToEditMode, this)
	          });
	        } else {
	          menuItems.push({
	            id: "cancel",
	            text: this.getMessage("menuCancel"),
	            onclick: BX.delegate(this.switchToViewMode, this)
	          });
	        }
	        menuItems.push({
	          id: "remove",
	          text: this.getMessage("menuDelete"),
	          onclick: BX.delegate(this.processRemoval, this)
	        });
	        if (this.isFixed() || this._fixedHistory.findItemById(this._id)) menuItems.push({
	          id: "unfasten",
	          text: this.getMessage("menuUnfasten"),
	          onclick: BX.delegate(this.unfasten, this)
	        });else menuItems.push({
	          id: "fasten",
	          text: this.getMessage("menuFasten"),
	          onclick: BX.delegate(this.fasten, this)
	        });
	      }
	      return menuItems;
	    }
	  }, {
	    key: "save",
	    value: function save(e) {
	      const attachmentList = [];
	      let text = "";
	      if (this._postForm) {
	        text = this._postForm.oEditor.GetContent();
	        this._commentMessage = text;
	        this._postForm.eventNode.querySelectorAll('input[name="UF_CRM_COMMENT_FILES[]"]').forEach(function (input) {
	          attachmentList.push(input.value);
	        });
	      }
	      if (!BX.type.isNotEmptyString(text)) {
	        if (!this.emptyCommentMessage) {
	          this.emptyCommentMessage = new BX.PopupWindow('timeline_empty_comment_' + this._id, e.target, {
	            content: BX.message('CRM_TIMELINE_EMPTY_COMMENT_MESSAGE'),
	            darkMode: true,
	            autoHide: true,
	            zIndex: 990,
	            angle: {
	              position: 'top',
	              offset: 77
	            },
	            closeByEsc: true,
	            bindOptions: {
	              forceBindPosition: true
	            }
	          });
	        }
	        this.emptyCommentMessage.show();
	        return;
	      }
	      if (this._isRequestRunning && BX.type.isNotEmptyString(text)) {
	        return;
	      }
	      this._isRequestRunning = true;
	      BX.ajax({
	        url: this._history._serviceUrl,
	        method: "POST",
	        dataType: "json",
	        data: {
	          "ACTION": "UPDATE_COMMENT",
	          "ID": this.getId(),
	          "TEXT": text,
	          "OWNER_TYPE_ID": this.getOwnerTypeId(),
	          "OWNER_ID": this.getOwnerId(),
	          "ATTACHMENTS": attachmentList
	        },
	        onsuccess: BX.delegate(this.onSaveSuccess, this),
	        onfailure: BX.delegate(this.onRequestFailure, this)
	      });
	    }
	  }, {
	    key: "processRemoval",
	    value: function processRemoval() {
	      this.closeContextMenu();
	      this._detetionConfirmDlgId = "entity_timeline_deletion_" + this.getId() + "_confirm";
	      let dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);
	      if (!dlg) {
	        dlg = BX.Crm.ConfirmationDialog.create(this._detetionConfirmDlgId, {
	          title: this.getMessage("removeConfirmTitle"),
	          content: this.getMessage('commentRemove')
	        });
	      }
	      dlg.open().then(BX.delegate(this.onRemovalConfirm, this), BX.delegate(this.onRemovalCancel, this));
	    }
	  }, {
	    key: "onRemovalConfirm",
	    value: function onRemovalConfirm(result) {
	      if (BX.prop.getBoolean(result, "cancel", true)) {
	        return;
	      }
	      this.remove();
	    }
	  }, {
	    key: "onRemovalCancel",
	    value: function onRemovalCancel() {}
	  }, {
	    key: "remove",
	    value: function remove(e) {
	      if (this._isRequestRunning) {
	        return;
	      }
	      const history = this._history._manager.getHistory();
	      const deleteItem = history.findItemById(this._id);
	      if (deleteItem instanceof Comment) deleteItem.clearAnimate();
	      const fixedHistory = this._history._manager.getFixedHistory();
	      const deleteFixedItem = fixedHistory.findItemById(this._id);
	      if (deleteFixedItem instanceof Comment) deleteFixedItem.clearAnimate();
	      this._isRequestRunning = true;
	      BX.ajax({
	        url: this._history._serviceUrl,
	        method: "POST",
	        dataType: "json",
	        data: {
	          "ACTION": "DELETE_COMMENT",
	          "OWNER_TYPE_ID": this.getOwnerTypeId(),
	          "OWNER_ID": this.getOwnerId(),
	          "ID": this.getId()
	        },
	        onsuccess: BX.delegate(this.onRemoveSuccess, this),
	        onfailure: BX.delegate(this.onRequestFailure, this)
	      });
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {
	      this._playerWrappers = {};
	      babelHelpers.get(babelHelpers.getPrototypeOf(Comment.prototype), "refreshLayout", this).call(this);
	    }
	  }, {
	    key: "onSaveSuccess",
	    value: function onSaveSuccess(data) {
	      this._isRequestRunning = false;
	      const itemData = BX.prop.getObject(data, "HISTORY_ITEM");
	      const updateFixedItem = this._fixedHistory.findItemById(this._id);
	      if (updateFixedItem instanceof Comment) {
	        if (!BX.type.isNotEmptyString(itemData['IS_FIXED'])) itemData['IS_FIXED'] = 'Y';
	        updateFixedItem.setData(itemData);
	        updateFixedItem._id = BX.prop.getString(itemData, "ID");
	        updateFixedItem.switchToViewMode();
	      }
	      const updateItem = this._history.findItemById(this._id);
	      if (updateItem instanceof Comment) {
	        updateItem.setData(itemData);
	        updateItem._id = BX.prop.getString(itemData, "ID");
	        updateItem.switchToViewMode();
	      }
	      this._postForm = null;
	    }
	  }, {
	    key: "onRemoveSuccess",
	    value: function onRemoveSuccess(data) {}
	  }, {
	    key: "onRequestFailure",
	    value: function onRequestFailure(data) {
	      this._isRequestRunning = this._isLocked = false;
	    }
	  }, {
	    key: "onExpandButtonClick",
	    value: function onExpandButtonClick(e) {
	      if (!this._wrapper) {
	        return BX.PreventDefault(e);
	      }
	      const contentWrapper = this._wrapper.querySelector("div.crm-entity-stream-section-content");
	      if (!contentWrapper) {
	        return BX.PreventDefault(e);
	      }
	      if (this._hasFiles && BX.type.isDomNode(this._commentWrapper) && !this._textLoaded) {
	        this._textLoaded = true;
	        this.loadContent(this._commentWrapper, "GET_TEXT");
	      }
	      const eventWrapper = contentWrapper.querySelector(".crm-entity-stream-content-event");
	      if (this._isCollapsed) {
	        eventWrapper.style.maxHeight = eventWrapper.scrollHeight + 130 + "px";
	        BX.removeClass(contentWrapper, "crm-entity-stream-section-content-collapsed");
	        BX.addClass(contentWrapper, "crm-entity-stream-section-content-expand");
	        setTimeout(BX.delegate(function () {
	          eventWrapper.style.maxHeight = "";
	        }, this), 300);
	      } else {
	        eventWrapper.style.maxHeight = eventWrapper.clientHeight + "px";
	        BX.removeClass(contentWrapper, "crm-entity-stream-section-content-expand");
	        BX.addClass(contentWrapper, "crm-entity-stream-section-content-collapsed");
	        setTimeout(BX.delegate(function () {
	          eventWrapper.style.maxHeight = "";
	        }, this), 0);
	      }
	      this._isCollapsed = !this._isCollapsed;
	      const button = contentWrapper.querySelector("a.crm-entity-stream-section-content-expand-btn");
	      if (button) {
	        button.innerHTML = this.getMessage(this._isCollapsed ? "expand" : "collapse");
	      }
	      return BX.PreventDefault(e);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Comment();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Comment;
	}(History);

	/** @memberof BX.Crm.Timeline.Items */
	let Wait$1 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Wait, _HistoryActivity);
	  function Wait() {
	    babelHelpers.classCallCheck(this, Wait);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Wait).call(this));
	  }
	  babelHelpers.createClass(Wait, [{
	    key: "getTitle",
	    value: function getTitle() {
	      return this.getMessage("wait");
	    }
	  }, {
	    key: "prepareTitleLayout",
	    value: function prepareTitleLayout() {
	      return BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-title"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          events: {
	            "click": this._headerClickHandler
	          },
	          text: this.getTitle()
	        })]
	      });
	    }
	  }, {
	    key: "prepareTimeLayout",
	    value: function prepareTimeLayout() {
	      return BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: this.formatTime(this.getCreatedTime())
	      });
	    }
	  }, {
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const entityData = this.getAssociatedEntityData();
	      let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      if (description !== "") {
	        description = BX.util.trim(description);
	        description = BX.util.strip_tags(description);
	        description = BX.util.nl2br(description);
	      }
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-wait"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-complete"
	        }
	      }));
	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [contentWrapper]
	      }));
	      const header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        html: description
	      });
	      contentWrapper.appendChild(detailWrapper);

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      }
	      //endregion

	      //region  Actions
	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer);
	      //endregion

	      return wrapper;
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {}
	  }, {
	    key: "showActions",
	    value: function showActions(show) {
	      if (this._actionContainer) {
	        this._actionContainer.style.display = show ? "" : "none";
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Wait();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Wait;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */
	let Document = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Document, _HistoryActivity);
	  function Document() {
	    babelHelpers.classCallCheck(this, Document);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Document).call(this));
	  }
	  babelHelpers.createClass(Document, [{
	    key: "getTitle",
	    value: function getTitle() {
	      const typeCategoryId = BX.prop.getInteger(this._data, "TYPE_CATEGORY_ID", 0);
	      if (typeCategoryId === 3) {
	        return BX.Loc.getMessage('CRM_TIMELINE_DOCUMENT_VIEWED');
	      }
	      return this.getMessage("document");
	    }
	  }, {
	    key: "prepareTitleLayout",
	    value: function prepareTitleLayout() {
	      return BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-title"
	        },
	        children: [BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          events: {
	            "click": BX.delegate(this.editDocument, this)
	          },
	          text: this.getTitle()
	        })]
	      });
	    }
	  }, {
	    key: "prepareTitleStatusLayout",
	    value: function prepareTitleStatusLayout() {
	      const typeCategoryId = BX.prop.getInteger(this._data, "TYPE_CATEGORY_ID", 0);
	      if (typeCategoryId === 3) {
	        return BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-event-done"
	          },
	          text: BX.Loc.getMessage('CRM_TIMELINE_DOCUMENT_VIEWED_STATUS')
	        });
	      }
	      if (typeCategoryId === 2) {
	        return BX.create("SPAN", {
	          attrs: {
	            className: "crm-entity-stream-content-event-sent"
	          },
	          text: BX.Loc.getMessage('CRM_TIMELINE_DOCUMENT_CREATED_STATUS')
	        });
	      }
	      return null;
	    }
	  }, {
	    key: "prepareTimeLayout",
	    value: function prepareTimeLayout() {
	      return BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: this.formatTime(this.getCreatedTime())
	      });
	    }
	  }, {
	    key: "isContextMenuEnabled",
	    value: function isContextMenuEnabled() {
	      const typeCategoryId = BX.prop.getInteger(this._data, "TYPE_CATEGORY_ID", 0);
	      return typeCategoryId !== 3;
	    }
	  }, {
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      const statusLayout = this.prepareTitleStatusLayout();
	      if (statusLayout) {
	        header.appendChild(statusLayout);
	      }
	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const text = this.getTextDataParam("COMMENT", "");
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-document"
	        }
	      });
	      if (this.isFixed()) {
	        BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
	      }
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-document"
	        }
	      }));
	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      }
	      if (!this.isReadOnly()) {
	        wrapper.appendChild(this.prepareFixedSwitcherLayout());
	      }
	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [contentWrapper]
	      }));
	      const header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        html: text
	      });
	      const title = BX.findChildByClassName(detailWrapper, 'document-title-link');
	      if (title) {
	        BX.bind(title, 'click', BX.proxy(this.editDocument, this));
	      }
	      contentWrapper.appendChild(detailWrapper);

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      }
	      //endregion

	      //region  Actions
	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer);
	      //endregion

	      return wrapper;
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {}
	  }, {
	    key: "showActions",
	    value: function showActions(show) {
	      if (this._actionContainer) {
	        this._actionContainer.style.display = show ? "" : "none";
	      }
	    }
	  }, {
	    key: "prepareContextMenuItems",
	    value: function prepareContextMenuItems() {
	      const menuItems = [];
	      if (!this.isReadOnly()) {
	        menuItems.push({
	          id: "edit",
	          text: this.getMessage("menuEdit"),
	          onclick: BX.delegate(this.editDocument, this)
	        });
	        menuItems.push({
	          id: "remove",
	          text: this.getMessage("menuDelete"),
	          onclick: BX.delegate(this.confirmDelete, this)
	        });
	        if (this.isFixed() || this._fixedHistory.findItemById(this._id)) {
	          menuItems.push({
	            id: "unfasten",
	            text: this.getMessage("menuUnfasten"),
	            onclick: BX.delegate(this.unfasten, this)
	          });
	        } else {
	          menuItems.push({
	            id: "fasten",
	            text: this.getMessage("menuFasten"),
	            onclick: BX.delegate(this.fasten, this)
	          });
	        }
	      }
	      return menuItems;
	    }
	  }, {
	    key: "confirmDelete",
	    value: function confirmDelete() {
	      this.closeContextMenu();
	      this._detetionConfirmDlgId = "entity_timeline_deletion_" + this.getId() + "_confirm";
	      let dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);
	      if (!dlg) {
	        dlg = BX.Crm.ConfirmationDialog.create(this._detetionConfirmDlgId, {
	          title: this.getMessage("removeConfirmTitle"),
	          content: this.getMessage('documentRemove')
	        });
	      }
	      dlg.open().then(BX.delegate(this.onConfirmDelete, this), BX.DoNothing);
	    }
	  }, {
	    key: "onConfirmDelete",
	    value: function onConfirmDelete(result) {
	      if (BX.prop.getBoolean(result, "cancel", true)) {
	        return;
	      }
	      this.deleteDocument();
	    }
	  }, {
	    key: "deleteDocument",
	    value: function deleteDocument() {
	      if (this._isRequestRunning) {
	        return;
	      }
	      this._isRequestRunning = true;
	      BX.ajax({
	        url: this._history._serviceUrl,
	        method: "POST",
	        dataType: "json",
	        data: {
	          "ACTION": "DELETE_DOCUMENT",
	          "OWNER_TYPE_ID": this.getOwnerTypeId(),
	          "OWNER_ID": this.getOwnerId(),
	          "ID": this.getId()
	        },
	        onsuccess: BX.delegate(function (result) {
	          this._isRequestRunning = false;
	          if (BX.type.isNotEmptyString(result.ERROR)) {
	            alert(result.ERROR);
	          } else {
	            const deleteItem = this._history.findItemById(this._id);
	            if (deleteItem instanceof Document) {
	              deleteItem.clearAnimate();
	            }
	            const deleteFixedItem = this._fixedHistory.findItemById(this._id);
	            if (deleteFixedItem instanceof Document) {
	              deleteFixedItem.clearAnimate();
	            }
	          }
	        }, this),
	        onfailure: BX.delegate(function () {
	          this._isRequestRunning = false;
	        }, this)
	      });
	    }
	  }, {
	    key: "editDocument",
	    value: function editDocument() {
	      const documentId = this.getData().DOCUMENT_ID || 0;
	      if (documentId > 0) {
	        let url = '/bitrix/components/bitrix/crm.document.view/slider.php';
	        url = BX.util.add_url_param(url, {
	          documentId: documentId
	        });
	        if (BX.SidePanel) {
	          BX.SidePanel.Instance.open(url, {
	            width: 1060
	          });
	        } else {
	          top.location.href = url;
	        }
	      }
	    }
	  }, {
	    key: "updateWrapper",
	    value: function updateWrapper() {
	      const wrapper = this.getWrapper();
	      if (wrapper) {
	        const detailWrapper = BX.findChildByClassName(wrapper, 'crm-entity-stream-content-detail');
	        if (detailWrapper) {
	          BX.adjust(detailWrapper, {
	            html: this.getTextDataParam("COMMENT", "")
	          });
	          const title = BX.findChildByClassName(detailWrapper, 'document-title-link');
	          if (title) {
	            BX.bind(title, 'click', BX.proxy(this.editDocument, this));
	          }
	        }
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Document();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Document;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */
	let Sender = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Sender, _HistoryActivity);
	  function Sender() {
	    babelHelpers.classCallCheck(this, Sender);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Sender).call(this));
	  }
	  babelHelpers.createClass(Sender, [{
	    key: "getDataSetting",
	    value: function getDataSetting(name) {
	      const settings = this.getObjectDataParam('SETTINGS') || {};
	      return settings[name] || null;
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = Sender.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return this.getDataSetting('messageName');
	    }
	  }, {
	    key: "prepareTitleLayout",
	    value: function prepareTitleLayout() {
	      const self = this;
	      return BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-title"
	        },
	        children: [this.isRemoved() ? BX.create("SPAN", {
	          text: this.getTitle()
	        }) : BX.create("A", {
	          attrs: {
	            href: ""
	          },
	          events: {
	            "click": function (e) {
	              if (BX.SidePanel) {
	                BX.SidePanel.Instance.open(self.getDataSetting('path'));
	              } else {
	                top.location.href = self.getDataSetting('path');
	              }
	              e.preventDefault();
	              e.stopPropagation();
	            }
	          },
	          text: this.getTitle()
	        })]
	      });
	    }
	  }, {
	    key: "prepareTimeLayout",
	    value: function prepareTimeLayout() {
	      return BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: this.formatTime(this.getCreatedTime())
	      });
	    }
	  }, {
	    key: "prepareStatusLayout",
	    value: function prepareStatusLayout() {
	      let layoutClassName, textCaption;
	      if (this.getDataSetting('isError')) {
	        textCaption = this.getMessage('error');
	        layoutClassName = "crm-entity-stream-content-event-missing";
	      } else if (this.getDataSetting('isUnsub')) {
	        textCaption = this.getMessage('unsub');
	        layoutClassName = "crm-entity-stream-content-event-missing";
	      } else if (this.getDataSetting('isClick')) {
	        textCaption = this.getMessage('click');
	        layoutClassName = "crm-entity-stream-content-event-successful";
	      } else {
	        textCaption = this.getMessage('read');
	        layoutClassName = "crm-entity-stream-content-event-skipped";
	      }
	      return BX.create("SPAN", {
	        attrs: {
	          className: layoutClassName
	        },
	        text: textCaption
	      });
	    }
	  }, {
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      const header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      if (this.getDataSetting('isError') || this.getDataSetting('isRead') || this.getDataSetting('isUnsub')) {
	        header.appendChild(this.prepareStatusLayout());
	      }
	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "isRemoved",
	    value: function isRemoved() {
	      return !this.getDataSetting('letterTitle');
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const description = this.isRemoved() ? this.getMessage('removed') : this.getMessage('title') + ': ' + this.getDataSetting('letterTitle');
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-wait"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-complete"
	        }
	      }));
	      const contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [contentWrapper]
	      }));
	      const header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      const detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        html: description
	      });
	      contentWrapper.appendChild(detailWrapper);

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      }
	      //endregion

	      return wrapper;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Sender();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Sender;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */
	let Bizproc = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(Bizproc, _History);
	  function Bizproc() {
	    babelHelpers.classCallCheck(this, Bizproc);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Bizproc).call(this));
	  }
	  babelHelpers.createClass(Bizproc, [{
	    key: "getTitle",
	    value: function getTitle() {
	      const type = this.getTextDataParam("TYPE");
	      if (type === 'AUTOMATION_DEBUG_INFORMATION') {
	        return this.getMessage('automationDebugger');
	      }
	      return this.getMessage("bizproc");
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      const wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-bp"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-bp"
	        }
	      }));
	      const content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      const header = this.prepareHeaderLayout();
	      content.appendChild(header);
	      content.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: [BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-description"
	          },
	          html: this.prepareContentTextHtml()
	        })]
	      }));

	      //region Author
	      const authorNode = this.prepareAuthorLayout();
	      if (authorNode) {
	        content.appendChild(authorNode);
	      }
	      //endregion

	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [content]
	      }));
	      return wrapper;
	    }
	  }, {
	    key: "prepareContentTextHtml",
	    value: function prepareContentTextHtml() {
	      const type = this.getTextDataParam("TYPE");
	      if (type === 'ACTIVITY_ERROR') {
	        return '<strong>#TITLE#</strong>: #ERROR_TEXT#'.replace('#TITLE#', BX.util.htmlspecialchars(this.getTextDataParam("ACTIVITY_TITLE"))).replace('#ERROR_TEXT#', BX.util.htmlspecialchars(this.getTextDataParam("ERROR_TEXT")));
	      } else if (type === 'AUTOMATION_DEBUG_INFORMATION') {
	        return BX.Text.encode(this.getTextDataParam('AUTOMATION_DEBUG_TEXT'));
	      }
	      const workflowName = this.getTextDataParam("WORKFLOW_TEMPLATE_NAME");
	      const workflowStatus = this.getTextDataParam("WORKFLOW_STATUS_NAME");
	      if (!workflowName || workflowStatus !== 'Created' && workflowStatus !== 'Completed' && workflowStatus !== 'Terminated') {
	        return BX.util.htmlspecialchars(this.getTextDataParam("COMMENT"));
	      }
	      let label = BX.message('CRM_TIMELINE_BIZPROC_CREATED');
	      if (workflowStatus === 'Completed') {
	        label = BX.message('CRM_TIMELINE_BIZPROC_COMPLETED');
	      } else if (workflowStatus === 'Terminated') {
	        label = BX.message('CRM_TIMELINE_BIZPROC_TERMINATED');
	      }
	      return BX.util.htmlspecialchars(label).replace('#NAME#', '<strong>' + BX.util.htmlspecialchars(workflowName) + '</strong>');
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Bizproc();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Bizproc;
	}(History);

	/** @memberof BX.Crm.Timeline.Actions */
	let Scoring = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(Scoring, _History);
	  function Scoring() {
	    babelHelpers.classCallCheck(this, Scoring);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Scoring).call(this));
	  }
	  babelHelpers.createClass(Scoring, [{
	    key: "prepareContent",
	    value: function prepareContent() {
	      const outerWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-scoring"
	        },
	        events: {
	          click: function () {
	            let url = "/crm/ml/#entity#/#id#/detail";
	            const ownerTypeId = this.getOwnerTypeId();
	            const ownerId = this.getOwnerId();
	            let ownerType;
	            if (ownerTypeId === 1) {
	              ownerType = "lead";
	            } else if (ownerTypeId === 2) {
	              ownerType = "deal";
	            } else {
	              return;
	            }
	            url = url.replace("#entity#", ownerType);
	            url = url.replace("#id#", ownerId);
	            if (BX.SidePanel) {
	              BX.SidePanel.Instance.open(url, {
	                width: 840
	              });
	            } else {
	              top.location.href = url;
	            }
	          }.bind(this)
	        }
	      });
	      const scoringInfo = BX.prop.getObject(this._data, "SCORING_INFO", null);
	      if (!scoringInfo) {
	        return outerWrapper;
	      }
	      let score = BX.prop.getNumber(scoringInfo, "SCORE", 0);
	      let scoreDelta = BX.prop.getNumber(scoringInfo, "SCORE_DELTA", 0);
	      score = Math.round(score * 100);
	      scoreDelta = Math.round(scoreDelta * 100);
	      const result = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-scoring-total-result"
	        },
	        text: score + "%"
	      });
	      let iconClass = "crm-entity-stream-content-scoring-total-icon";
	      if (score < 50) {
	        iconClass += " crm-entity-stream-content-scoring-total-icon-fail";
	      } else if (score < 75) {
	        iconClass += " crm-entity-stream-content-scoring-total-icon-middle";
	      } else {
	        iconClass += " crm-entity-stream-content-scoring-total-icon-success";
	      }
	      const icon = BX.create("DIV", {
	        attrs: {
	          className: iconClass
	        }
	      });
	      outerWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-scoring-total"
	          },
	          children: [BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-content-scoring-total-text"
	            },
	            text: BX.message("CRM_TIMELINE_SCORING_TITLE_2")
	          }), result, icon]
	        }), BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-scoring-event"
	          },
	          children: [scoreDelta !== 0 ? BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-content-scoring-event-offset"
	            },
	            text: (scoreDelta > 0 ? "+" : "") + scoreDelta + "%"
	          }) : null

	          /*BX.create("DIV",
	          	{
	          		attrs: { className: "crm-entity-stream-content-scoring-event-detail" },
	          		text: "<activity subject>"
	          	}
	          )*/]
	        })]
	      }));

	      return outerWrapper;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Scoring();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Scoring;
	}(History);

	/** @memberof BX.Crm.Timeline.Animation */
	let Expand = /*#__PURE__*/function () {
	  function Expand() {
	    babelHelpers.classCallCheck(this, Expand);
	    this._node = null;
	    this._callback = null;
	  }
	  babelHelpers.createClass(Expand, [{
	    key: "initialize",
	    value: function initialize(node, callback) {
	      this._node = node;
	      this._callback = BX.type.isFunction(callback) ? callback : null;
	    }
	  }, {
	    key: "run",
	    value: function run() {
	      const position = BX.pos(this._node);
	      this._node.style.height = 0;
	      this._node.style.opacity = 0;
	      this._node.style.overflow = "hidden";
	      new BX.easing({
	        duration: 150,
	        start: {
	          height: 0
	        },
	        finish: {
	          height: position.height
	        },
	        transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: BX.delegate(this.onNodeHeightStep, this),
	        complete: BX.delegate(this.onNodeHeightComplete, this)
	      }).animate();
	    }
	  }, {
	    key: "onNodeHeightStep",
	    value: function onNodeHeightStep(state) {
	      this._node.style.height = state.height + "px";
	    }
	  }, {
	    key: "onNodeHeightComplete",
	    value: function onNodeHeightComplete() {
	      this._node.style.overflow = "";
	      new BX.easing({
	        duration: 150,
	        start: {
	          opacity: 0
	        },
	        finish: {
	          opacity: 100
	        },
	        transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: BX.delegate(this.onNodeOpacityStep, this),
	        complete: BX.delegate(this.onNodeOpacityComplete, this)
	      }).animate();
	    }
	  }, {
	    key: "onNodeOpacityStep",
	    value: function onNodeOpacityStep(state) {
	      this._node.style.opacity = state.opacity / 100;
	    }
	  }, {
	    key: "onNodeOpacityComplete",
	    value: function onNodeOpacityComplete() {
	      this._node.style.height = "";
	      this._node.style.opacity = "";
	      if (this._callback) {
	        this._callback();
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(node, callback) {
	      const self = new Expand();
	      self.initialize(node, callback);
	      return self;
	    }
	  }]);
	  return Expand;
	}();

	/** @memberof BX.Crm.Timeline.Streams */
	let History$1 = /*#__PURE__*/function (_Stream) {
	  babelHelpers.inherits(History$$1, _Stream);
	  function History$$1() {
	    var _this;
	    babelHelpers.classCallCheck(this, History$$1);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(History$$1).call(this));
	    _this._items = [];
	    _this._wrapper = null;
	    _this._fixedHistory = null;
	    _this._emptySection = null;
	    _this._currentDaySection = null;
	    _this._lastDaySection = null;
	    _this._lastDate = null;
	    _this._anchor = null;
	    _this._history = babelHelpers.assertThisInitialized(_this);
	    _this._enableLoading = false;
	    _this._navigation = null;
	    _this._scrollHandler = null;
	    _this._loadingWaiter = null;
	    _this._filterId = "";
	    _this._isFilterApplied = false;
	    _this._isFilterShown = false;
	    _this._isRequestRunning = false;
	    _this._filterButton = null;
	    _this._filterWrapper = null;
	    _this._filterResultStub = null;
	    return _this;
	  }
	  babelHelpers.createClass(History$$1, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      this._fixedHistory = this.getSetting("fixedHistory");
	      this._ownerTypeId = this.getSetting("ownerTypeId");
	      this._ownerId = this.getSetting("ownerId");
	      this._serviceUrl = this.getSetting("serviceUrl", "");
	      if (!this.isStubMode()) {
	        let itemData = this.getSetting("itemData");
	        if (!BX.type.isArray(itemData)) {
	          itemData = [];
	        }
	        let i, length, item;
	        for (i = 0, length = itemData.length; i < length; i++) {
	          item = this.createItem(itemData[i]);
	          if (item) {
	            this._items.push(item);
	          }
	        }
	        this._navigation = this.getSetting("navigation", {});
	        this._filterWrapper = BX("timeline-filter");
	        this._filterId = BX.prop.getString(this._settings, "filterId", this._id);
	        this._isFilterShown = this._filterWrapper && BX.hasClass(this._filterWrapper, "crm-entity-stream-section-filter-show");
	        this._isFilterApplied = BX.prop.getBoolean(this._settings, "isFilterApplied", false);
	        BX.addCustomEvent("BX.Main.Filter:apply", this.onFilterApply.bind(this));
	      }
	    }
	  }, {
	    key: "layout",
	    value: function layout() {
	      this._wrapper = BX.create("DIV", {});
	      this._container.appendChild(this._wrapper);
	      const now = BX.prop.extractDate(new Date());
	      let i, length, item;
	      if (!this.isStubMode()) {
	        if (this._filterWrapper) {
	          const closeFilterButton = this._filterWrapper.querySelector(".crm-entity-stream-filter-close");
	          if (closeFilterButton) {
	            BX.bind(closeFilterButton, "click", this.onFilterClose.bind(this));
	          }
	        }
	        for (i = 0, length = this._items.length; i < length; i++) {
	          item = this._items[i];
	          item.setContainer(this._wrapper);
	          const created = item.getCreatedDate();
	          if (this._lastDate === null || this._lastDate.getTime() !== created.getTime()) {
	            this._lastDate = created;
	            if (now.getTime() === created.getTime()) {
	              this._currentDaySection = this._lastDaySection = this.createCurrentDaySection();
	              this._wrapper.appendChild(this._currentDaySection);
	            } else {
	              this._lastDaySection = this.createDaySection(this._lastDate);
	              this._wrapper.appendChild(this._lastDaySection);
	            }
	          }
	          item._lastDate = this._lastDate;
	          item.layout();
	        }
	        this.enableLoading(this._items.length > 0);
	        this.refreshLayout();
	      } else {
	        this._currentDaySection = this._lastDaySection = this.createCurrentDaySection();
	        this._wrapper.appendChild(this._currentDaySection);
	        this._wrapper.appendChild(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section crm-entity-stream-section-createEntity crm-entity-stream-section-last"
	          },
	          children: [BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info"
	            }
	          }), BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-section-content"
	            },
	            children: [BX.create("DIV", {
	              attrs: {
	                className: "crm-entity-stream-content-event"
	              },
	              children: [BX.create("DIV", {
	                attrs: {
	                  className: "crm-entity-stream-content-header"
	                }
	              }), BX.create("DIV", {
	                attrs: {
	                  className: "crm-entity-stream-content-detail"
	                },
	                text: BX.message("CRM_TIMELINE_HISTORY_STUB")
	              })]
	            })]
	          })]
	        }));
	      }
	      this._manager.processHistoryLayoutChange();
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {
	      if (this._filterWrapper) {
	        if (this._wrapper.firstChild && this._filterWrapper !== this._wrapper.firstChild) {
	          this._wrapper.insertBefore(this._filterWrapper, this._wrapper.firstChild);
	        } else if (!this._wrapper.firstChild && this._filterWrapper.parentNode !== this._wrapper) {
	          this._wrapper.appendChild(this._filterWrapper);
	        }
	      }
	      this.adjustFilterButton();
	      const length = this._items.length;
	      if (length === 0 && this._isFilterApplied) {
	        if (!this._filterEmptyResultSection) {
	          this._filterEmptyResultSection = this.createFilterEmptyResultSection();
	        }
	        this._wrapper.appendChild(this._filterEmptyResultSection);
	        return;
	      }
	      if (this._filterEmptyResultSection) {
	        this._filterEmptyResultSection = BX.remove(this._filterEmptyResultSection);
	      }
	      if (length === 0) {
	        return;
	      }
	      for (let i = 0; i < length - 1; i++) {
	        const item = this._items[i];
	        if (item.isTerminated()) {
	          item.markAsTerminated(false);
	        }
	      }
	      this._items[length - 1].markAsTerminated(true);
	    }
	  }, {
	    key: "calculateItemIndex",
	    value: function calculateItemIndex(item) {
	      return 0;
	    }
	  }, {
	    key: "checkItemForTermination",
	    value: function checkItemForTermination(item) {
	      return this.getLastItem() === item;
	    }
	  }, {
	    key: "hasContent",
	    value: function hasContent() {
	      return this._items.length > 0 || this._isFilterApplied || this._isStubMode;
	    }
	  }, {
	    key: "getItems",
	    value: function getItems() {
	      return this._items;
	    }
	  }, {
	    key: "setItems",
	    value: function setItems(items) {
	      this._items = items;
	    }
	  }, {
	    key: "getItemByIndex",
	    value: function getItemByIndex(index) {
	      return index < this._items.length ? this._items[index] : null;
	    }
	  }, {
	    key: "getItemCount",
	    value: function getItemCount() {
	      return this._items.length;
	    }
	  }, {
	    key: "getItemsByAssociatedEntity",
	    value: function getItemsByAssociatedEntity($entityTypeId, entityId) {
	      if (!BX.type.isNumber($entityTypeId)) {
	        $entityTypeId = parseInt($entityTypeId);
	      }
	      if (!BX.type.isNumber(entityId)) {
	        entityId = parseInt(entityId);
	      }
	      if (isNaN($entityTypeId) || $entityTypeId <= 0 || isNaN(entityId) || entityId <= 0) {
	        return [];
	      }
	      const results = [];
	      for (let i = 0, l = this._items.length; i < l; i++) {
	        const item = this._items[i];
	        if (item.getAssociatedEntityTypeId() === $entityTypeId && item.getAssociatedEntityId() === entityId) {
	          results.push(item);
	        }
	      }
	      return results;
	    }
	  }, {
	    key: "createFilterEmptyResultSection",
	    value: function createFilterEmptyResultSection() {
	      return BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-filter-empty"
	        },
	        children: [BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-content"
	          },
	          children: [BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-filter-empty"
	            },
	            children: [BX.create("DIV", {
	              attrs: {
	                className: "crm-entity-stream-filter-empty-img"
	              }
	            }), BX.create("DIV", {
	              attrs: {
	                className: "crm-entity-stream-filter-empty-text"
	              },
	              text: this.getMessage("filterEmptyResultStub")
	            })]
	          })]
	        })]
	      });
	    }
	  }, {
	    key: "adjustFilterButton",
	    value: function adjustFilterButton() {
	      if (!this._filterWrapper) {
	        return;
	      }
	      if (!this._isFilterShown && this._items.length === 0) {
	        if (!this._emptySection) {
	          this._emptySection = this.createEmptySection();
	        }
	        this._wrapper.insertBefore(this._emptySection, this._filterWrapper);
	      } else if (this._emptySection) {
	        this._emptySection = BX.remove(this._emptySection);
	      }
	      if (!this._filterButton) {
	        this._filterButton = BX.create("BUTTON", {
	          attrs: {
	            className: "crm-entity-stream-filter-label"
	          },
	          text: this.getMessage("filterButtonCaption")
	        });
	        BX.bind(this._filterButton, "click", function (e) {
	          this.showFilter();
	        }.bind(this));
	      }
	      const section = this._wrapper.querySelector(".crm-entity-stream-section-today-label, .crm-entity-stream-section-planned-label, .crm-entity-stream-section-history-label");
	      if (section) {
	        const sectionWrapper = section.querySelector(".crm-entity-stream-section-content");
	        if (sectionWrapper) {
	          if (this._filterButton.parentNode !== sectionWrapper) {
	            sectionWrapper.appendChild(this._filterButton);
	          }
	        }
	      }
	      if (this._isFilterApplied) {
	        BX.addClass(this._filterButton, "crm-entity-stream-filter-label-active");
	      } else {
	        BX.removeClass(this._filterButton, "crm-entity-stream-filter-label-active");
	      }
	    }
	  }, {
	    key: "showFilter",
	    value: function showFilter(params) {
	      if (!this._filterWrapper) {
	        return;
	      }
	      BX.removeClass(this._filterWrapper, "crm-entity-stream-section-filter-hide");
	      BX.addClass(this._filterWrapper, "crm-entity-stream-section-filter-show");
	      this._isFilterShown = true;
	      if (BX.prop.getBoolean(params, "enableAdjust", true)) {
	        this.adjustFilterButton();
	      }
	    }
	  }, {
	    key: "hideFilter",
	    value: function hideFilter(params) {
	      if (!this._filterWrapper) {
	        return;
	      }
	      BX.removeClass(this._filterWrapper, "crm-entity-stream-section-filter-show");
	      BX.addClass(this._filterWrapper, "crm-entity-stream-section-filter-hide");
	      this._isFilterShown = false;
	      if (BX.prop.getBoolean(params, "enableAdjust", true)) {
	        this.adjustFilterButton();
	      }
	    }
	  }, {
	    key: "onFilterClose",
	    value: function onFilterClose(e) {
	      this.hideFilter();
	      window.setTimeout(function () {
	        const filter = BX.Main.filterManager.getById(this._filterId);
	        if (filter) {
	          filter.resetFilter();
	        }
	      }.bind(this), 500);
	    }
	  }, {
	    key: "createEmptySection",
	    value: function createEmptySection() {
	      return BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-planned-label"
	        },
	        children: [BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-content"
	          }
	        })]
	      });
	    }
	  }, {
	    key: "createCurrentDaySection",
	    value: function createCurrentDaySection() {
	      let formattedDate = this.formatDate(BX.prop.extractDate(new Date()));
	      formattedDate = formattedDate[0].toUpperCase() + formattedDate.substring(1);
	      return BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-today-label"
	        },
	        children: [BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-content"
	          },
	          children: [BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-today-label"
	            },
	            text: formattedDate
	          })]
	        })]
	      });
	    }
	  }, {
	    key: "createDaySection",
	    value: function createDaySection(date) {
	      let formattedDate = this.formatDate(date);
	      formattedDate = formattedDate[0].toUpperCase() + formattedDate.substring(1);
	      return BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history-label"
	        },
	        children: [BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section-content"
	          },
	          children: [BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-history-label"
	            },
	            text: formattedDate
	          })]
	        })]
	      });
	    }
	  }, {
	    key: "createAnchor",
	    value: function createAnchor(index) {
	      if (this._emptySection) {
	        this._emptySection = BX.remove(this._emptySection);
	      }
	      if (this._currentDaySection === null) {
	        this._currentDaySection = this.createCurrentDaySection();
	        if (this._wrapper.firstChild) {
	          this._wrapper.insertBefore(this._currentDaySection, this._wrapper.firstChild);
	        } else {
	          this._wrapper.appendChild(this._currentDaySection);
	        }
	      }
	      if (this._anchor === null) {
	        this._anchor = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-section crm-entity-stream-section-shadow"
	          }
	        });
	        if (this._currentDaySection.nextSibling) {
	          this._wrapper.insertBefore(this._anchor, this._currentDaySection.nextSibling);
	        } else {
	          this._wrapper.appendChild(this._anchor);
	        }
	      }
	      return this._anchor;
	    }
	  }, {
	    key: "createActivityItem",
	    value: function createActivityItem(data) {
	      const typeId = BX.prop.getInteger(data, "TYPE_ID", Item.undefined);
	      const typeCategoryId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);
	      const providerId = BX.prop.getString(BX.prop.getObject(data, "ASSOCIATED_ENTITY", {}), "PROVIDER_ID", "");
	      if (typeId !== Item.activity) {
	        return null;
	      }
	      if (typeCategoryId === BX.CrmActivityType.email) {
	        return Email$2.create(data["ID"], {
	          history: this._history,
	          fixedHistory: this._fixedHistory,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      }
	      if (typeCategoryId === BX.CrmActivityType.call) {
	        return Call$2.create(data["ID"], {
	          history: this._history,
	          fixedHistory: this._fixedHistory,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeCategoryId === BX.CrmActivityType.meeting) {
	        return Meeting$1.create(data["ID"], {
	          history: this._history,
	          fixedHistory: this._fixedHistory,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeCategoryId === BX.CrmActivityType.task) {
	        return Task$1.create(data["ID"], {
	          history: this._history,
	          fixedHistory: this._fixedHistory,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeCategoryId === BX.CrmActivityType.provider) {
	        if (providerId === "CRM_WEBFORM") {
	          return WebForm$1.create(data["ID"], {
	            history: this._history,
	            fixedHistory: this._fixedHistory,
	            container: this._wrapper,
	            activityEditor: this._activityEditor,
	            data: data
	          });
	        } else if (providerId === 'CRM_REQUEST') {
	          return Request$1.create(data["ID"], {
	            history: this._history,
	            fixedHistory: this._fixedHistory,
	            container: this._wrapper,
	            activityEditor: this._activityEditor,
	            data: data
	          });
	        } else if (providerId === "IMOPENLINES_SESSION") {
	          return OpenLine$2.create(data["ID"], {
	            history: this._history,
	            fixedHistory: this._fixedHistory,
	            container: this._wrapper,
	            activityEditor: this._activityEditor,
	            data: data
	          });
	        } else if (providerId === 'REST_APP') {
	          return Rest$1.create(data["ID"], {
	            history: this._history,
	            fixedHistory: this._fixedHistory,
	            container: this._wrapper,
	            activityEditor: this._activityEditor,
	            data: data
	          });
	        } else if (providerId === 'VISIT_TRACKER') {
	          return Visit.create(data["ID"], {
	            history: this,
	            fixedHistory: this._fixedHistory,
	            container: this._wrapper,
	            activityEditor: this._activityEditor,
	            data: data
	          });
	        } else if (providerId === 'ZOOM') {
	          return Zoom$1.create(data["ID"], {
	            history: this,
	            fixedHistory: this._fixedHistory,
	            container: this._wrapper,
	            activityEditor: this._activityEditor,
	            data: data
	          });
	        } else if (providerId === 'CRM_CALL_TRACKER') {
	          return Call$2.create(data["ID"], {
	            history: this,
	            fixedHistory: this._fixedHistory,
	            container: this._wrapper,
	            activityEditor: this._activityEditor,
	            data: data
	          });
	        }
	      }
	      return HistoryActivity.create(data["ID"], {
	        history: this._history,
	        fixedHistory: this._fixedHistory,
	        container: this._wrapper,
	        activityEditor: this._activityEditor,
	        data: data
	      });
	    }
	  }, {
	    key: "createExternalNotificationItem",
	    value: function createExternalNotificationItem(data) {
	      const typeId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);
	      const changedFieldName = BX.prop.getString(data, 'CHANGED_FIELD_NAME', '');
	      if (typeId === Item.modification && changedFieldName === 'STATUS_ID') {
	        return ExternalNoticeStatusModification.create(data["ID"], {
	          history: this._history,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      }
	      return ExternalNoticeModification.create(data["ID"], {
	        history: this._history,
	        container: this._wrapper,
	        activityEditor: this._activityEditor,
	        data: data
	      });
	    }
	  }, {
	    key: "createItem",
	    value: function createItem(data) {
	      if (data.hasOwnProperty('type')) {
	        return crm_timeline_item.ConfigurableItem.create(data.id, {
	          timelineId: this.getId(),
	          container: this.getWrapper(),
	          itemClassName: this.getItemClassName(),
	          useShortTimeFormat: this.getStreamType() === crm_timeline_item.StreamType.history,
	          isReadOnly: this.isReadOnly(),
	          currentUser: this._manager.getCurrentUser(),
	          ownerTypeId: this._manager.getOwnerTypeId(),
	          ownerId: this._manager.getOwnerId(),
	          streamType: this.getStreamType(),
	          data: data
	        });
	      }
	      const typeId = BX.prop.getInteger(data, "TYPE_ID", Item.undefined);
	      const typeCategoryId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);
	      if (typeId === Item.activity) {
	        return this.createActivityItem(data);
	      } else if (typeId === Item.externalNotification) {
	        return this.createExternalNotificationItem(data);
	      } else if (typeId === Item.creation) {
	        return Creation.create(data["ID"], {
	          history: this._history,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeId === Item.restoration) {
	        return Restoration.create(data["ID"], {
	          history: this._history,
	          container: this._wrapper,
	          data: data
	        });
	      } else if (typeId === Item.link) {
	        return Link.create(data["ID"], {
	          history: this,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeId === Item.unlink) {
	        return Unlink.create(data["ID"], {
	          history: this,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeId === Item.mark) {
	        return Mark$1.create(data["ID"], {
	          history: this._history,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          fixedHistory: this._fixedHistory,
	          data: data
	        });
	      } else if (typeId === Item.comment) {
	        return Comment$1.create(data["ID"], {
	          history: this._history,
	          fixedHistory: this._fixedHistory,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeId === Item.wait) {
	        return Wait$1.create(data["ID"], {
	          history: this._history,
	          fixedHistory: this._fixedHistory,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeId === Item.document) {
	        return Document.create(data["ID"], {
	          history: this._history,
	          fixedHistory: this._fixedHistory,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeId === Item.sender) {
	        return Sender.create(data["ID"], {
	          history: this,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeId === Item.modification) {
	        return Modification.create(data["ID"], {
	          history: this._history,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeId === Item.conversion) {
	        return Conversion.create(data["ID"], {
	          history: this._history,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeId === Item.bizproc) {
	        return Bizproc.create(data["ID"], {
	          history: this._history,
	          fixedHistory: this._fixedHistory,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeId === Item.scoring) {
	        return Scoring.create(data["ID"], {
	          history: this._history,
	          fixedHistory: this._fixedHistory,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      }
	      return History.create(data["ID"], {
	        history: this._history,
	        fixedHistory: this._fixedHistory,
	        container: this._wrapper,
	        activityEditor: this._activityEditor,
	        data: data
	      });
	    }
	  }, {
	    key: "getWrapper",
	    value: function getWrapper() {
	      return this._wrapper;
	    }
	  }, {
	    key: "getItemClassName",
	    value: function getItemClassName() {
	      return 'crm-entity-stream-section crm-entity-stream-section-history';
	    }
	  }, {
	    key: "addItem",
	    value: function addItem(item, index) {
	      if (!BX.type.isNumber(index) || index < 0) {
	        index = this.calculateItemIndex(item);
	      }
	      if (index < this._items.length) {
	        this._items.splice(index, 0, item);
	      } else {
	        this._items.push(item);
	      }
	      this.refreshLayout();
	      this._manager.processHistoryLayoutChange();
	    }
	  }, {
	    key: "deleteItem",
	    value: function deleteItem(item) {
	      const index = this.getItemIndex(item);
	      if (index < 0) {
	        return;
	      }
	      item.clearLayout();
	      this.removeItemByIndex(index);
	      this.refreshLayout();
	      this._manager.processHistoryLayoutChange();
	    }
	  }, {
	    key: "resetLayout",
	    value: function resetLayout() {
	      let i;
	      for (i = this._items.length - 1; i >= 0; i--) {
	        this._items[i].clearLayout();
	      }
	      this._items = [];
	      this._currentDaySection = this._lastDaySection = this._emptySection = this._filterEmptyResultSection = null;
	      this._anchor = null;
	      this._lastDate = null;

	      //Clean wrapper. Skip filter for prevent trembling.
	      const children = [];
	      let child;
	      for (i = 0; child = this._wrapper.children[i]; i++) {
	        if (child !== this._filterWrapper) {
	          children.push(child);
	        }
	      }
	      for (i = 0; child = children[i]; i++) {
	        this._wrapper.removeChild(child);
	      }
	    }
	  }, {
	    key: "onWindowScroll",
	    value: function onWindowScroll(e) {
	      if (!this._loadingWaiter || !this._enableLoading || this._isRequestRunning) {
	        return;
	      }
	      const pos = this._loadingWaiter.getBoundingClientRect();
	      if (pos.top <= document.documentElement.clientHeight) {
	        this.loadItems();
	      }
	    }
	  }, {
	    key: "onFilterApply",
	    value: function onFilterApply(id, data, ctx, promise, params) {
	      if (id !== this._filterId) {
	        return;
	      }
	      params.autoResolve = false;
	      this._isFilterApplied = BX.prop.getString(data, "action", "") === "apply";
	      this._isRequestRunning = true;
	      BX.CrmDataLoader.create(this._id, {
	        serviceUrl: this.getSetting("serviceUrl", ""),
	        action: "GET_HISTORY_ITEMS",
	        params: {
	          "GUID": this._id,
	          "OWNER_TYPE_ID": this._manager.getOwnerTypeId(),
	          "OWNER_ID": this._manager.getOwnerId()
	        }
	      }).load(function (sender, result) {
	        this.resetLayout();
	        this.bulkCreateItems(BX.prop.getArray(result, "HISTORY_ITEMS", []));
	        this.setNavigation(BX.prop.getObject(result, "HISTORY_NAVIGATION", {}));
	        this.refreshLayout();
	        if (this._items.length > 0) {
	          this._manager.processHistoryLayoutChange();
	        }
	        promise.fulfill();
	        this._isRequestRunning = false;
	      }.bind(this));
	    }
	  }, {
	    key: "bulkCreateItems",
	    value: function bulkCreateItems(itemData) {
	      const length = itemData.length;
	      if (length === 0) {
	        return;
	      }
	      if (this._filterEmptyResultSection) {
	        this._filterEmptyResultSection = BX.remove(this._filterEmptyResultSection);
	      }
	      const now = BX.prop.extractDate(new Date());
	      let i, item;
	      for (i = 0; i < length; i++) {
	        const itemId = BX.prop.getInteger(itemData[i], 'id', BX.prop.getInteger(itemData[i], 'ID', 0));
	        if (itemId <= 0) {
	          continue;
	        }
	        if (this.findItemById(itemId) !== null) {
	          continue;
	        }
	        item = this.createItem(itemData[i]);
	        this._items.push(item);
	        const created = item.getCreatedDate();
	        if (this._lastDate === null || this._lastDate.getTime() !== created.getTime()) {
	          this._lastDate = created;
	          if (now.getTime() === created.getTime()) {
	            this._currentDaySection = this._lastDaySection = this.createCurrentDaySection();
	            this._wrapper.appendChild(this._currentDaySection);
	          } else {
	            this._lastDaySection = this.createDaySection(this._lastDate);
	            this._wrapper.appendChild(this._lastDaySection);
	          }
	        }
	        item.layout();
	      }
	    }
	  }, {
	    key: "loadItems",
	    value: function loadItems() {
	      this._isRequestRunning = true;
	      BX.CrmDataLoader.create(this._id, {
	        serviceUrl: this.getSetting("serviceUrl", ""),
	        action: "GET_HISTORY_ITEMS",
	        params: {
	          "GUID": this._id,
	          "OWNER_TYPE_ID": this._manager.getOwnerTypeId(),
	          "OWNER_ID": this._manager.getOwnerId(),
	          "NAVIGATION": this._navigation
	        }
	      }).load(function (sender, result) {
	        this.bulkCreateItems(BX.prop.getArray(result, "HISTORY_ITEMS", []));
	        this.setNavigation(BX.prop.getObject(result, "HISTORY_NAVIGATION", {}));
	        this.refreshLayout();
	        if (this._items.length > 0) {
	          this._manager.processHistoryLayoutChange();
	        }
	        this._isRequestRunning = false;
	      }.bind(this));
	    }
	  }, {
	    key: "getNavigation",
	    value: function getNavigation() {
	      return this._navigation;
	    }
	  }, {
	    key: "setNavigation",
	    value: function setNavigation(navigation) {
	      if (!BX.type.isPlainObject(navigation)) {
	        navigation = {};
	      }
	      this._navigation = navigation;
	      this.enableLoading(BX.prop.getString(this._navigation, "OFFSET_TIMESTAMP", "") !== "");
	    }
	  }, {
	    key: "isLoadingEnabled",
	    value: function isLoadingEnabled() {
	      return this._enableLoading;
	    }
	  }, {
	    key: "enableLoading",
	    value: function enableLoading(enable) {
	      enable = !!enable;
	      if (this._enableLoading === enable) {
	        return;
	      }
	      this._enableLoading = enable;
	      if (this._enableLoading) {
	        if (this._items.length > 0) {
	          this._loadingWaiter = this._items[this._items.length - 1].getWrapper();
	        }
	        if (!this._scrollHandler) {
	          this._scrollHandler = BX.delegate(this.onWindowScroll, this);
	          BX.bind(window, "scroll", this._scrollHandler);
	        }
	      } else {
	        this._loadingWaiter = null;
	        if (this._scrollHandler) {
	          BX.unbind(window, "scroll", this._scrollHandler);
	          this._scrollHandler = null;
	        }
	      }
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = History$$1.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }, {
	    key: "animateItemAdding",
	    value: function animateItemAdding(item) {
	      return new Promise(resolve => {
	        Expand.create(item.getWrapper(), resolve).run();
	      });
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new History$$1();
	      self.initialize(id, settings);
	      History$$1.instances[self.getId()] = self;
	      return self;
	    }
	  }]);
	  return History$$1;
	}(Steam);
	babelHelpers.defineProperty(History$1, "messages", {});
	babelHelpers.defineProperty(History$1, "instances", {});

	/** @memberof BX.Crm.Timeline.Streams */
	let FixedHistory = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(FixedHistory, _History);
	  function FixedHistory() {
	    var _this;
	    babelHelpers.classCallCheck(this, FixedHistory);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FixedHistory).call(this));
	    _this._items = [];
	    _this._wrapper = null;
	    _this._fixedHistory = babelHelpers.assertThisInitialized(_this);
	    _this._history = babelHelpers.assertThisInitialized(_this);
	    _this._isRequestRunning = false;
	    return _this;
	  }
	  babelHelpers.createClass(FixedHistory, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      const datetimeFormat = BX.message("FORMAT_DATETIME").replace(/:SS/, "");
	      this._timeFormat = BX.date.convertBitrixFormat(datetimeFormat);
	      let itemData = this.getSetting("itemData");
	      if (!BX.type.isArray(itemData)) {
	        itemData = [];
	      }
	      let i, length, item;
	      for (i = 0, length = itemData.length; i < length; i++) {
	        item = this.createItem(itemData[i]);
	        item._isFixed = true;
	        this._items.push(item);
	      }
	    }
	  }, {
	    key: "setHistory",
	    value: function setHistory(history) {
	      this._history = history;
	    }
	  }, {
	    key: "checkItemForTermination",
	    value: function checkItemForTermination(item) {
	      return false;
	    }
	  }, {
	    key: "layout",
	    value: function layout() {
	      this._wrapper = BX.create("DIV", {});
	      this.createAnchor();
	      this._container.insertBefore(this._wrapper, this._editorContainer.nextElementSibling);
	      for (let i = 0; i < this._items.length; i++) {
	        this._items[i].setContainer(this._wrapper);
	        this._items[i].layout();
	      }
	      this.refreshLayout();
	      this._manager.processHistoryLayoutChange();
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {}
	  }, {
	    key: "formatDate",
	    value: function formatDate(date) {}
	  }, {
	    key: "createCurrentDaySection",
	    value: function createCurrentDaySection() {}
	  }, {
	    key: "createDaySection",
	    value: function createDaySection(date) {}
	  }, {
	    key: "createAnchor",
	    value: function createAnchor(index) {
	      this._anchor = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-fixed-anchor"
	        }
	      });
	      this._wrapper.appendChild(this._anchor);
	    }
	  }, {
	    key: "onWindowScroll",
	    value: function onWindowScroll(e) {}
	  }, {
	    key: "onItemsLoad",
	    value: function onItemsLoad(sender, result) {}
	  }, {
	    key: "loadItems",
	    value: function loadItems() {
	      this._isRequestRunning = true;
	      BX.CrmDataLoader.create(this._id, {
	        serviceUrl: this.getSetting("serviceUrl", ""),
	        action: "GET_FIXED_HISTORY_ITEMS",
	        params: {
	          "OWNER_TYPE_ID": this._manager.getOwnerTypeId(),
	          "OWNER_ID": this._manager.getOwnerId()
	        }
	      }).load(BX.delegate(this.onItemsLoad, this));
	    }
	  }, {
	    key: "addItem",
	    value: function addItem(item, index) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(FixedHistory.prototype), "addItem", this).call(this, item, index);
	      if (item instanceof CompatibleItem) {
	        item._isFixed = true;
	      }
	    }
	  }, {
	    key: "getItemClassName",
	    value: function getItemClassName() {
	      return 'crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-top-fixed';
	    }
	  }, {
	    key: "getStreamType",
	    value: function getStreamType() {
	      return crm_timeline_item.StreamType.pinned;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      let self = new FixedHistory();
	      self.initialize(id, settings);
	      this.instances[self.getId()] = self;
	      return self;
	    }
	  }]);
	  return FixedHistory;
	}(History$1);
	FixedHistory.instances = {};

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _scheduleStream = /*#__PURE__*/new WeakMap();
	var _fixedHistoryStream = /*#__PURE__*/new WeakMap();
	var _historyStream = /*#__PURE__*/new WeakMap();
	var _itemsQueue = /*#__PURE__*/new WeakMap();
	var _itemsQueueProcessing = /*#__PURE__*/new WeakMap();
	var _reloadingMessagesQueue = /*#__PURE__*/new WeakMap();
	var _ownerTypeId = /*#__PURE__*/new WeakMap();
	var _ownerId = /*#__PURE__*/new WeakMap();
	var _itemDataShouldBeReloaded = /*#__PURE__*/new WeakSet();
	var _addToQueue = /*#__PURE__*/new WeakSet();
	var _processQueueItem = /*#__PURE__*/new WeakSet();
	var _addItem = /*#__PURE__*/new WeakSet();
	var _updateItem = /*#__PURE__*/new WeakSet();
	var _deleteItem = /*#__PURE__*/new WeakSet();
	var _moveItem = /*#__PURE__*/new WeakSet();
	var _pinItem = /*#__PURE__*/new WeakSet();
	var _unpinItem = /*#__PURE__*/new WeakSet();
	var _getStreamByName = /*#__PURE__*/new WeakSet();
	var _fetchItems = /*#__PURE__*/new WeakSet();
	let PullActionProcessor = /*#__PURE__*/function () {
	  function PullActionProcessor(params) {
	    babelHelpers.classCallCheck(this, PullActionProcessor);
	    _classPrivateMethodInitSpec(this, _fetchItems);
	    _classPrivateMethodInitSpec(this, _getStreamByName);
	    _classPrivateMethodInitSpec(this, _unpinItem);
	    _classPrivateMethodInitSpec(this, _pinItem);
	    _classPrivateMethodInitSpec(this, _moveItem);
	    _classPrivateMethodInitSpec(this, _deleteItem);
	    _classPrivateMethodInitSpec(this, _updateItem);
	    _classPrivateMethodInitSpec(this, _addItem);
	    _classPrivateMethodInitSpec(this, _processQueueItem);
	    _classPrivateMethodInitSpec(this, _addToQueue);
	    _classPrivateMethodInitSpec(this, _itemDataShouldBeReloaded);
	    _classPrivateFieldInitSpec(this, _scheduleStream, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _fixedHistoryStream, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _historyStream, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _itemsQueue, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec(this, _itemsQueueProcessing, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _reloadingMessagesQueue, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec(this, _ownerTypeId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _ownerId, {
	      writable: true,
	      value: void 0
	    });
	    if (!main_core.Type.isObject(params.scheduleStream) || !main_core.Type.isObject(params.fixedHistoryStream) || !main_core.Type.isObject(params.historyStream)) {
	      throw new Error(`params scheduleStream, fixedHistoryStream and historyStream are required`);
	    }
	    if (!main_core.Type.isNumber(params.ownerTypeId) || !main_core.Type.isNumber(params.ownerId)) {
	      throw new Error('params ownerTypeId and ownerId are required');
	    }
	    babelHelpers.classPrivateFieldSet(this, _scheduleStream, params.scheduleStream);
	    babelHelpers.classPrivateFieldSet(this, _fixedHistoryStream, params.fixedHistoryStream);
	    babelHelpers.classPrivateFieldSet(this, _historyStream, params.historyStream);
	    babelHelpers.classPrivateFieldSet(this, _ownerTypeId, params.ownerTypeId);
	    babelHelpers.classPrivateFieldSet(this, _ownerId, params.ownerId);
	  }
	  babelHelpers.createClass(PullActionProcessor, [{
	    key: "processAction",
	    value: function processAction(actionParams) {
	      if (_classPrivateMethodGet(this, _itemDataShouldBeReloaded, _itemDataShouldBeReloaded2).call(this, actionParams)) {
	        babelHelpers.classPrivateFieldGet(this, _reloadingMessagesQueue).push(actionParams);
	        _classPrivateMethodGet(this, _fetchItems, _fetchItems2).call(this);
	      } else {
	        _classPrivateMethodGet(this, _addToQueue, _addToQueue2).call(this, actionParams);
	      }
	    }
	  }]);
	  return PullActionProcessor;
	}();
	function _itemDataShouldBeReloaded2(actionParams) {
	  const {
	    item
	  } = actionParams;
	  if (!item) {
	    return false;
	  }
	  const appLanguage = main_core.Loc.getMessage('LANGUAGE_ID').toLowerCase();
	  const languageId = BX.prop.getString(item, 'languageId', appLanguage).toLowerCase();
	  const canBeReloaded = BX.prop.getBoolean(item, 'canBeReloaded', true);
	  return languageId !== appLanguage && canBeReloaded;
	}
	function _addToQueue2(actionParams) {
	  babelHelpers.classPrivateFieldGet(this, _itemsQueue).push(actionParams);
	  if (!babelHelpers.classPrivateFieldGet(this, _itemsQueueProcessing)) {
	    _classPrivateMethodGet(this, _processQueueItem, _processQueueItem2).call(this);
	  }
	}
	function _processQueueItem2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _itemsQueue).length) {
	    babelHelpers.classPrivateFieldSet(this, _itemsQueueProcessing, false);
	    return;
	  }
	  babelHelpers.classPrivateFieldSet(this, _itemsQueueProcessing, true);
	  const actionParams = babelHelpers.classPrivateFieldGet(this, _itemsQueue).shift();
	  const stream = _classPrivateMethodGet(this, _getStreamByName, _getStreamByName2).call(this, actionParams.stream);
	  const promises = [];
	  switch (actionParams.action) {
	    case 'add':
	      promises.push(_classPrivateMethodGet(this, _addItem, _addItem2).call(this, actionParams.id, actionParams.item, stream));
	      break;
	    case 'update':
	      promises.push(_classPrivateMethodGet(this, _updateItem, _updateItem2).call(this, actionParams.id, actionParams.item, stream, false, true));
	      if (stream.isHistoryStream()) {
	        // fixed history stream can contain the same item as a history stream, so both should be updated:
	        promises.push(_classPrivateMethodGet(this, _updateItem, _updateItem2).call(this, actionParams.id, actionParams.item, babelHelpers.classPrivateFieldGet(this, _fixedHistoryStream), false, true));
	      }
	      break;
	    case 'delete':
	      promises.push(_classPrivateMethodGet(this, _deleteItem, _deleteItem2).call(this, actionParams.id, stream));
	      if (stream.isHistoryStream()) {
	        // fixed history stream can contain the same item as a history stream, so both should be updated:
	        promises.push(_classPrivateMethodGet(this, _deleteItem, _deleteItem2).call(this, actionParams.id, babelHelpers.classPrivateFieldGet(this, _fixedHistoryStream)));
	      }
	      break;
	    case 'move':
	      // move item from one stream to another one:
	      const sourceStream = _classPrivateMethodGet(this, _getStreamByName, _getStreamByName2).call(this, actionParams.params.fromStream);
	      promises.push(_classPrivateMethodGet(this, _moveItem, _moveItem2).call(this, actionParams.params.fromId, sourceStream, actionParams.id, stream, actionParams.item));
	      break;
	    case 'changePinned':
	      // pin or unpin item
	      if (_classPrivateMethodGet(this, _getStreamByName, _getStreamByName2).call(this, actionParams.params.fromStream).isHistoryStream()) {
	        promises.push(_classPrivateMethodGet(this, _pinItem, _pinItem2).call(this, actionParams.id, actionParams.item));
	      } else {
	        promises.push(_classPrivateMethodGet(this, _unpinItem, _unpinItem2).call(this, actionParams.id, actionParams.item));
	      }
	  }
	  Promise.all(promises).then(() => {
	    _classPrivateMethodGet(this, _processQueueItem, _processQueueItem2).call(this);
	  });
	}
	function _addItem2(id, itemData, stream) {
	  const existedStreamItem = stream.findItemById(id);
	  if (existedStreamItem) {
	    return Promise.resolve();
	  }
	  const streamItem = stream.createItem(itemData);
	  if (!streamItem) {
	    return Promise.resolve();
	  }
	  const index = stream.calculateItemIndex(streamItem);
	  const anchor = stream.createAnchor(index);
	  stream.addItem(streamItem, index);
	  streamItem.layout({
	    anchor: anchor
	  });
	  return stream.animateItemAdding(streamItem);
	}
	function _updateItem2(id, itemData, stream, animateUpdate = true, animateMove) {
	  const isDone = BX.prop.getString(itemData['ASSOCIATED_ENTITY'], 'COMPLETED') === 'Y';
	  const existedStreamItem = stream.findItemById(id);
	  if (!existedStreamItem) {
	    return Promise.resolve();
	  }
	  if (existedStreamItem instanceof CompatibleItem && isDone) {
	    existedStreamItem._existedStreamItemDeadLine = existedStreamItem.getLightTime();
	  }
	  existedStreamItem.setData(itemData);
	  return stream.refreshItem(existedStreamItem, animateUpdate, animateMove);
	}
	function _deleteItem2(id, stream) {
	  const item = stream.findItemById(id);
	  if (item) {
	    return stream.deleteItemAnimated(item);
	  }
	  return Promise.resolve();
	}
	function _moveItem2(sourceId, sourceStream, destinationId, destinationStream, destinationItemData) {
	  const sourceItem = sourceStream.findItemById(sourceId);
	  if (!sourceItem) {
	    return _classPrivateMethodGet(this, _addItem, _addItem2).call(this, destinationId, destinationItemData, destinationStream);
	  }
	  const existedDestinationItem = destinationStream.findItemById(destinationId);
	  if (sourceItem && existedDestinationItem) {
	    return _classPrivateMethodGet(this, _deleteItem, _deleteItem2).call(this, sourceId, sourceStream);
	  }
	  const destinationItem = destinationStream.createItem(destinationItemData);
	  destinationStream.addItem(destinationItem, destinationStream.calculateItemIndex(destinationItem));
	  if (destinationItem instanceof CompatibleItem) {
	    destinationItem.layout({
	      add: false
	    });
	  }
	  return sourceStream.moveItemToStream(sourceItem, destinationStream, destinationItem);
	}
	function _pinItem2(id, itemData) {
	  if (babelHelpers.classPrivateFieldGet(this, _fixedHistoryStream).findItemById(id)) {
	    return Promise.resolve();
	  }
	  const historyItem = babelHelpers.classPrivateFieldGet(this, _historyStream).findItemById(id);
	  if (!historyItem)
	    // fixed history item does not exist into history items stream, so just add to fixed history stream
	    {
	      return _classPrivateMethodGet(this, _addItem, _addItem2).call(this, id, itemData, babelHelpers.classPrivateFieldGet(this, _fixedHistoryStream));
	    }
	  if (historyItem instanceof CompatibleItem) {
	    historyItem.onSuccessFasten();
	    return Promise.resolve();
	  } else {
	    // hide files block in comment content before pin
	    const historyCommentBlock = historyItem.getLayoutContentBlockById('commentContent');
	    if (historyCommentBlock) {
	      historyCommentBlock.setIsFilesBlockDisplayed(false);
	      historyCommentBlock.setIsMoving();
	    }
	    return _classPrivateMethodGet(this, _updateItem, _updateItem2).call(this, id, itemData, babelHelpers.classPrivateFieldGet(this, _historyStream), false, false).then(() => {
	      const fixedHistoryItem = babelHelpers.classPrivateFieldGet(this, _fixedHistoryStream).createItem(itemData);
	      fixedHistoryItem.initWrapper();
	      babelHelpers.classPrivateFieldGet(this, _fixedHistoryStream).addItem(fixedHistoryItem, 0);
	      return new Promise(resolve => {
	        const animation = Fasten.create('', {
	          initialItem: historyItem,
	          finalItem: fixedHistoryItem,
	          anchor: babelHelpers.classPrivateFieldGet(this, _fixedHistoryStream).getAnchor(),
	          events: {
	            complete: () => {
	              fixedHistoryItem.initLayoutApp({
	                add: false
	              });

	              // show files block in comment content after pin record
	              if (historyCommentBlock) {
	                historyCommentBlock.setIsFilesBlockDisplayed();
	                historyCommentBlock.setIsMoving(false);
	                const fixedHistoryCommentBlock = fixedHistoryItem.getLayoutContentBlockById('commentContent');
	                if (fixedHistoryCommentBlock) {
	                  fixedHistoryCommentBlock.setIsFilesBlockDisplayed();
	                  fixedHistoryCommentBlock.setIsMoving(false);
	                }
	              }
	              resolve();
	            }
	          }
	        });
	        animation.run();
	      });
	    });
	  }
	}
	function _unpinItem2(id, itemData) {
	  const fixedHistoryItem = babelHelpers.classPrivateFieldGet(this, _fixedHistoryStream).findItemById(id);
	  if (fixedHistoryItem instanceof CompatibleItem) {
	    fixedHistoryItem.onSuccessUnfasten();
	    return Promise.resolve();
	  } else {
	    return _classPrivateMethodGet(this, _updateItem, _updateItem2).call(this, id, itemData, babelHelpers.classPrivateFieldGet(this, _historyStream), false, false).then(() => {
	      return _classPrivateMethodGet(this, _deleteItem, _deleteItem2).call(this, id, babelHelpers.classPrivateFieldGet(this, _fixedHistoryStream));
	    });
	  }
	}
	function _getStreamByName2(streamName) {
	  switch (streamName) {
	    case 'scheduled':
	      return babelHelpers.classPrivateFieldGet(this, _scheduleStream);
	    case 'fixedHistory':
	      return babelHelpers.classPrivateFieldGet(this, _fixedHistoryStream);
	    case 'history':
	      return babelHelpers.classPrivateFieldGet(this, _historyStream);
	  }
	  throw new Error(`Stream "${streamName}" not found`);
	}
	function _fetchItems2() {
	  setTimeout(() => {
	    const messages = main_core.clone(babelHelpers.classPrivateFieldGet(this, _reloadingMessagesQueue));
	    babelHelpers.classPrivateFieldSet(this, _reloadingMessagesQueue, []);
	    const activityIds = [];
	    const historyIds = [];
	    messages.forEach(message => {
	      const container = message.stream === 'scheduled' ? activityIds : historyIds;
	      container.push(message.id);
	    });
	    if (messages.length) {
	      const data = {
	        activityIds,
	        historyIds,
	        ownerTypeId: babelHelpers.classPrivateFieldGet(this, _ownerTypeId),
	        ownerId: babelHelpers.classPrivateFieldGet(this, _ownerId)
	      };
	      main_core.ajax.runAction('crm.timeline.item.load', {
	        data
	      }).then(response => {
	        messages.forEach(message => {
	          if (response.data[message.id]) {
	            message.item = response.data[message.id];
	          }
	          _classPrivateMethodGet(this, _addToQueue, _addToQueue2).call(this, message);
	        });
	      }).catch(err => {
	        console.error(err);
	        messages.forEach(message => _classPrivateMethodGet(this, _addToQueue, _addToQueue2).call(this, message));
	      });
	    }
	  }, 1500);
	}

	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
	function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }

	/** @memberof BX.Crm.Timeline */
	var _itemPullActionProcessor = /*#__PURE__*/new WeakMap();
	let Manager = /*#__PURE__*/function () {
	  function Manager() {
	    babelHelpers.classCallCheck(this, Manager);
	    _classPrivateFieldInitSpec$1(this, _itemPullActionProcessor, {
	      writable: true,
	      value: null
	    });
	    this._id = "";
	    this._settings = {};
	    this._container = null;
	    this._ownerTypeId = 0;
	    this._ownerId = 0;
	    this._ownerInfo = null;
	    this._progressSemantics = "";
	    this._chat = null;
	    this._schedule = null;
	    this._history = null;
	    this._fixedHistory = null;
	    this._activityEditor = null;
	    this._userId = 0;
	    this._readOnly = false;
	    this._currentUser = null;
	    this._pullTagName = "";
	  }
	  babelHelpers.createClass(Manager, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._settings = settings ? settings : {};
	      this._ownerTypeId = parseInt(this.getSetting("ownerTypeId"));
	      this._ownerId = parseInt(this.getSetting("ownerId"));
	      this._ownerInfo = this.getSetting("ownerInfo");
	      this._progressSemantics = BX.prop.getString(this._settings, "progressSemantics", "");
	      this._spotlightFastenShowed = this.getSetting("spotlightFastenShowed", true);
	      this._audioPlaybackRate = parseFloat(this.getSetting("audioPlaybackRate", 1));
	      const containerId = this.getSetting("containerId");
	      if (!BX.type.isNotEmptyString(containerId)) {
	        throw "Manager. A required parameter 'containerId' is missing.";
	      }
	      this._container = BX(containerId);
	      if (!BX.type.isElementNode(this._container)) {
	        throw "Manager. Container node is not found.";
	      }
	      this._editorContainer = BX(this.getSetting("editorContainer"));
	      this._userId = BX.prop.getInteger(this._settings, "userId", 0);
	      this._readOnly = BX.prop.getBoolean(this._settings, "readOnly", false);
	      this._currentUser = BX.prop.getObject(this._settings, "currentUser", null);
	      const activityEditorId = this.getSetting("activityEditorId");
	      if (BX.type.isNotEmptyString(activityEditorId)) {
	        this._activityEditor = BX.CrmActivityEditor.items[activityEditorId];
	        if (!(this._activityEditor instanceof BX.CrmActivityEditor)) {
	          throw "BX.CrmTimeline. Activity editor instance is not found.";
	        }
	      }
	      const ajaxId = this.getSetting("ajaxId");
	      const currentUrl = this.getSetting("currentUrl");
	      const serviceUrl = this.getSetting("serviceUrl");
	      this._chat = EntityChat.create(this._id, {
	        manager: this,
	        container: this._container,
	        data: this.getSetting("chatData"),
	        isStubMode: this._ownerId <= 0,
	        readOnly: this._readOnly
	      });
	      this._schedule = Schedule.create(this._id, {
	        manager: this,
	        container: this._container,
	        activityEditor: this._activityEditor,
	        itemData: this.getSetting("scheduleData"),
	        templates: this.getSetting("templates"),
	        isStubMode: this._ownerId <= 0,
	        ajaxId: ajaxId,
	        serviceUrl: serviceUrl,
	        currentUrl: currentUrl,
	        userId: this._userId,
	        readOnly: this._readOnly
	      });
	      this._fixedHistory = FixedHistory.create(this._id, {
	        manager: this,
	        container: this._container,
	        editorContainer: this._editorContainer,
	        activityEditor: this._activityEditor,
	        itemData: this.getSetting("fixedData"),
	        templates: this.getSetting("templates"),
	        isStubMode: this._ownerId <= 0,
	        ajaxId: ajaxId,
	        serviceUrl: serviceUrl,
	        currentUrl: currentUrl,
	        userId: this._userId,
	        readOnly: this._readOnly
	      });
	      this._history = History$1.create(this._id, {
	        manager: this,
	        container: this._container,
	        fixedHistory: this._fixedHistory,
	        activityEditor: this._activityEditor,
	        itemData: this.getSetting("historyData"),
	        templates: this.getSetting("templates"),
	        navigation: this.getSetting("historyNavigation", {}),
	        filterId: BX.prop.getString(this._settings, "historyFilterId", this._id),
	        isFilterApplied: BX.prop.getBoolean(this._settings, "isHistoryFilterApplied", false),
	        isStubMode: this._ownerId <= 0,
	        ajaxId: ajaxId,
	        serviceUrl: serviceUrl,
	        currentUrl: currentUrl,
	        userId: this._userId,
	        readOnly: this._readOnly
	      });
	      this._schedule.setHistory(this._history);
	      this._fixedHistory.setHistory(this._history);
	      this._chat.layout();
	      this._schedule.layout();
	      this._fixedHistory.layout();
	      this._history.layout();
	      this._pullTagName = BX.prop.getString(this._settings, "pullTagName", "");
	      if (this._pullTagName !== "") {
	        BX.addCustomEvent("onPullEvent-crm", BX.delegate(this.onPullEvent, this));
	        this.extendWatch();
	        babelHelpers.classPrivateFieldSet(this, _itemPullActionProcessor, new PullActionProcessor({
	          scheduleStream: this._schedule,
	          fixedHistoryStream: this._fixedHistory,
	          historyStream: this._history,
	          ownerTypeId: this._ownerTypeId,
	          ownerId: this._ownerId
	        }));
	      }
	      BX.addCustomEvent(window, "Crm.EntityProgress.Change", BX.delegate(this.onEntityProgressChange, this));
	      BX.ready(function () {
	        window.addEventListener("scroll", BX.throttle(function () {
	          BX.LazyLoad.onScroll();
	        }, 80));
	      });
	    }
	  }, {
	    key: "extendWatch",
	    value: function extendWatch() {
	      if (BX.type.isFunction(BX.PULL) && this._pullTagName !== "") {
	        BX.PULL.extendWatch(this._pullTagName);
	        window.setTimeout(BX.delegate(this.extendWatch, this), 60000);
	      }
	    }
	  }, {
	    key: "onPullEvent",
	    value: function onPullEvent(command, params) {
	      if (this._pullTagName !== BX.prop.getString(params, "TAG", "")) {
	        return;
	      }
	      if (command === 'timeline_item_action') {
	        babelHelpers.classPrivateFieldGet(this, _itemPullActionProcessor).processAction(params);
	        return;
	      }
	      if (command === "timeline_chat_create") {
	        this.processChatCreate(params);
	      } else if (command === "timeline_activity_add") {
	        this.processActivityExternalAdd(params);
	      } else if (command === "timeline_activity_update") {
	        this.processActivityExternalUpdate(params);
	      } else if (command === "timeline_activity_delete") {
	        this.processActivityExternalDelete(params);
	      } else if (command === "timeline_comment_add") {
	        this.processCommentExternalAdd(params);
	      } else if (command === "timeline_comment_update") {
	        this.processCommentExternalUpdate(params);
	      } else if (command === "timeline_comment_delete") {
	        this.processCommentExternalDelete(params);
	      } else if (command === "timeline_changed_binding") {
	        this.processChangeBinding(params);
	      } else if (command === "timeline_item_update") {
	        this.processItemExternalUpdate(params);
	      } else if (command === "timeline_wait_add") {
	        this.processWaitExternalAdd(params);
	      } else if (command === "timeline_wait_update") {
	        this.processWaitExternalUpdate(params);
	      } else if (command === "timeline_wait_delete") {
	        this.processWaitExternalDelete(params);
	      } else if (command === "timeline_bizproc_status") {
	        this.processBizprocStatus(params);
	      } else if (command === "timeline_scoring_add") {
	        this.processScoringExternalAdd(params);
	      }
	    }
	  }, {
	    key: "processChatCreate",
	    value: function processChatCreate(params) {
	      if (this._chat) {
	        this._chat.setData(BX.prop.getObject(params, "CHAT_DATA", {}));
	        this._chat.refreshLayout();
	      }
	    }
	  }, {
	    key: "processActivityExternalAdd",
	    value: function processActivityExternalAdd(params) {
	      let entityData, scheduleItemData, historyItemData, scheduleItem, historyItem;
	      entityData = BX.prop.getObject(params, "ENTITY", null);
	      scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
	      historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
	      if (entityData && historyItemData && !BX.type.isPlainObject(historyItemData["ASSOCIATED_ENTITY"])) {
	        historyItemData["ASSOCIATED_ENTITY"] = entityData;
	      }
	      if (scheduleItemData !== null && this._schedule.getItemByData(scheduleItemData) === null) {
	        scheduleItem = this.addScheduleItem(scheduleItemData);
	        scheduleItem.addWrapperClass("crm-entity-stream-section-updated", 1000);
	      }
	      if (historyItemData !== null) {
	        historyItem = this._history.findItemById(BX.prop.getString(historyItemData, "ID"));
	        if (!historyItem) {
	          historyItem = this.addHistoryItem(historyItemData);
	          Expand.create(historyItem.getWrapper(), null).run();
	        }
	      }
	    }
	  }, {
	    key: "processActivityExternalUpdate",
	    value: function processActivityExternalUpdate(params) {
	      let entityData, scheduleItemData, scheduleItem, historyItemData, historyItem, fixedHistoryItem;
	      entityData = BX.prop.getObject(params, "ENTITY", null);
	      scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
	      historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
	      if (entityData) {
	        if (historyItemData && !BX.type.isPlainObject(historyItemData["ASSOCIATED_ENTITY"])) {
	          historyItemData["ASSOCIATED_ENTITY"] = entityData;
	        }
	        const entityId = BX.prop.getInteger(entityData, "ID", 0);
	        const historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
	        for (let i = 0, length = historyItems.length; i < length; i++) {
	          historyItem = historyItems[i];
	          historyItem.setAssociatedEntityData(entityData);
	          historyItem.refreshLayout();
	        }
	        const fixedHistoryItems = this._fixedHistory.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
	        for (let i = 0, length = fixedHistoryItems.length; i < length; i++) {
	          fixedHistoryItem = fixedHistoryItems[i];
	          fixedHistoryItem.setAssociatedEntityData(entityData);
	          fixedHistoryItem.refreshLayout();
	        }
	      }
	      if (scheduleItemData !== null) {
	        scheduleItem = this._schedule.getItemByAssociatedEntity(BX.CrmEntityType.enumeration.activity, BX.prop.getInteger(scheduleItemData, "ASSOCIATED_ENTITY_ID"));
	        if (scheduleItem) {
	          scheduleItem.setData(scheduleItemData);
	          if (!scheduleItem.isDone()) {
	            this._schedule.refreshItem(scheduleItem);
	          } else {
	            if (historyItemData) {
	              this._schedule.transferItemToHistory(scheduleItem, historyItemData);
	              //History data are already processed
	              historyItemData = null;
	            } else {
	              this._schedule.deleteItem(scheduleItem);
	            }
	          }
	        } else if (!Scheduled.isDone(scheduleItemData)) {
	          scheduleItem = this.addScheduleItem(scheduleItemData);
	          scheduleItem.addWrapperClass("crm-entity-stream-section-updated", 1000);
	        }
	      }
	      if (historyItemData !== null) {
	        historyItem = this._history.findItemById(BX.prop.getString(historyItemData, "ID"));
	        if (!historyItem) {
	          historyItem = this.addHistoryItem(historyItemData);
	          Expand.create(historyItem.getWrapper(), null).run();
	        } else {
	          historyItem.setData(historyItemData);
	          historyItem.refreshLayout();
	          fixedHistoryItem = this._fixedHistory.findItemById(BX.prop.getString(historyItemData, "ID"));
	          if (fixedHistoryItem) {
	            fixedHistoryItem.setData(historyItemData);
	            fixedHistoryItem.refreshLayout();
	          }
	        }
	      }
	    }
	  }, {
	    key: "processActivityExternalDelete",
	    value: function processActivityExternalDelete(params) {
	      const entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
	      const historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
	      for (let i = 0, length = historyItems.length; i < length; i++) {
	        this._history.deleteItem(historyItems[i]);
	      }
	      const fixedHistoryItems = this._fixedHistory.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
	      for (let i = 0, length = fixedHistoryItems.length; i < length; i++) {
	        this._fixedHistory.deleteItem(fixedHistoryItems[i]);
	      }
	      const scheduleItem = this._schedule.getItemByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
	      if (scheduleItem) {
	        this._schedule.deleteItem(scheduleItem);
	      }
	    }
	  }, {
	    key: "processCommentExternalAdd",
	    value: function processCommentExternalAdd(params) {
	      let historyItemData, historyItem;
	      historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
	      if (historyItemData !== null) {
	        window.setTimeout(BX.delegate(function () {
	          if (!this._history.findItemById(historyItemData['ID'])) {
	            historyItem = this.addHistoryItem(historyItemData);
	            Expand.create(historyItem.getWrapper(), null).run();
	          }
	        }, this), 1500);
	      }
	    }
	  }, {
	    key: "processCommentExternalUpdate",
	    value: function processCommentExternalUpdate(params) {
	      const entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
	      const historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
	      const updateItem = this._history.findItemById(entityId);
	      if (updateItem instanceof Comment && historyItemData !== null) {
	        updateItem.setData(historyItemData);
	        updateItem.switchToViewMode();
	      }
	      const updateFixedItem = this._fixedHistory.findItemById(entityId);
	      if (updateFixedItem instanceof Comment && historyItemData !== null) {
	        updateFixedItem.setData(historyItemData);
	        updateFixedItem.switchToViewMode();
	      }
	    }
	  }, {
	    key: "processCommentExternalDelete",
	    value: function processCommentExternalDelete(params) {
	      const entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
	      window.setTimeout(BX.delegate(function () {
	        const deleteItem = this._history.findItemById(entityId);
	        if (deleteItem instanceof Comment) {
	          this._history.deleteItem(deleteItem);
	        }
	        const deleteFixedItem = this._fixedHistory.findItemById(entityId);
	        if (deleteFixedItem instanceof Comment) {
	          this._fixedHistory.deleteItem(deleteFixedItem);
	        }
	      }, this), 1200);
	    }
	  }, {
	    key: "processChangeBinding",
	    value: function processChangeBinding(params) {
	      const entityId = BX.prop.getString(params, "OLD_ID", 0);
	      const entityNewId = BX.prop.getString(params, "NEW_ID", 0);
	      const item = this._history.findItemById(entityId);
	      if (item instanceof crm_timeline_item.Item) {
	        item._id = entityNewId;
	        const itemData = item.getData();
	        itemData.ID = entityNewId;
	        item.setData(itemData);
	      }
	      const fixedItem = this._fixedHistory.findItemById(entityId);
	      if (fixedItem instanceof crm_timeline_item.Item) {
	        fixedItem._id = entityNewId;
	        const fixedItemData = fixedItem.getData();
	        fixedItemData.ID = entityNewId;
	        fixedItem.setData(fixedItemData);
	      }
	    }
	  }, {
	    key: "processItemExternalUpdate",
	    value: function processItemExternalUpdate(params) {
	      const entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
	      const historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
	      const historyItem = this._history.findItemById(entityId);
	      if (historyItem && historyItemData !== null) {
	        historyItem.setData(historyItemData);
	        historyItem.markAsTerminated(this._history.checkItemForTermination(historyItem));
	        historyItem.refreshLayout();
	        if (historyItem.isTerminated()) {
	          BX.addClass(historyItem._wrapper, "crm-entity-stream-section-last");
	        }
	      }
	    }
	  }, {
	    key: "processWaitExternalAdd",
	    value: function processWaitExternalAdd(params) {
	      const scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
	      if (scheduleItemData !== null) {
	        this.addScheduleItem(scheduleItemData);
	      }
	    }
	  }, {
	    key: "processWaitExternalUpdate",
	    value: function processWaitExternalUpdate(params) {
	      let entityData, scheduleItemData, scheduleItem, historyItemData, historyItem;
	      entityData = BX.prop.getObject(params, "ENTITY", null);
	      scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
	      historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
	      if (entityData) {
	        if (historyItemData && !BX.type.isPlainObject(historyItemData["ASSOCIATED_ENTITY"])) {
	          historyItemData["ASSOCIATED_ENTITY"] = entityData;
	        }
	        const entityId = BX.prop.getInteger(entityData, "ID", 0);
	        const historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.wait, entityId);
	        let i = 0;
	        const length = historyItems.length;
	        for (; i < length; i++) {
	          historyItem = historyItems[i];
	          historyItem.setAssociatedEntityData(entityData);
	          historyItem.refreshLayout();
	        }
	      }
	      if (scheduleItemData !== null) {
	        scheduleItem = this._schedule.getItemByAssociatedEntity(BX.CrmEntityType.enumeration.wait, BX.prop.getInteger(scheduleItemData, "ASSOCIATED_ENTITY_ID"));
	        if (!scheduleItem) {
	          this.addScheduleItem(scheduleItemData);
	        } else {
	          scheduleItem.setData(scheduleItemData);
	          if (!scheduleItem.isDone()) {
	            this._schedule.refreshItem(scheduleItem);
	          } else {
	            if (historyItemData) {
	              this._schedule.transferItemToHistory(scheduleItem, historyItemData);
	              //History data are already processed
	              historyItemData = null;
	            } else {
	              this._schedule.deleteItem(scheduleItem);
	            }
	          }
	        }
	      }
	      if (historyItemData !== null) {
	        historyItem = this._history.findItemById(BX.prop.getString(historyItemData, "ID"));
	        if (!historyItem) {
	          historyItem = this.addHistoryItem(historyItemData);
	          Expand.create(historyItem.getWrapper(), null).run();
	        } else {
	          historyItem.setData(historyItemData);
	          historyItem.refreshLayout();
	        }
	      }
	    }
	  }, {
	    key: "processWaitExternalDelete",
	    value: function processWaitExternalDelete(params) {
	      const entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
	      const historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.wait, entityId);
	      let i = 0;
	      const length = historyItems.length;
	      for (; i < length; i++) {
	        this._history.deleteItem(historyItems[i]);
	      }
	      const scheduleItem = this._schedule.getItemByAssociatedEntity(BX.CrmEntityType.enumeration.wait, entityId);
	      if (scheduleItem) {
	        this._schedule.deleteItem(scheduleItem);
	      }
	    }
	  }, {
	    key: "processBizprocStatus",
	    value: function processBizprocStatus(params) {
	      let historyItemData, historyItem;
	      historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
	      if (historyItemData !== null) {
	        historyItem = this.addHistoryItem(historyItemData);
	        Expand.create(historyItem.getWrapper(), null).run();
	      }
	    }
	  }, {
	    key: "processScoringExternalAdd",
	    value: function processScoringExternalAdd(params) {
	      let historyItemData, historyItem;
	      historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
	      if (historyItemData !== null) {
	        historyItem = this.addHistoryItem(historyItemData);
	        Expand.create(historyItem.getWrapper(), null).run();
	      }
	    }
	  }, {
	    key: "onEntityProgressChange",
	    value: function onEntityProgressChange(sender, eventArgs) {
	      if (BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== this._ownerTypeId || BX.prop.getInteger(eventArgs, "entityId", 0) !== this._ownerId) {
	        return;
	      }
	      const semantics = BX.prop.getString(eventArgs, "semantics", "");
	      if (semantics === this._progressSemantics) {
	        return;
	      }
	      this._progressSemantics = semantics;
	      this._schedule.refreshLayout();
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this._id;
	    }
	  }, {
	    key: "getSetting",
	    value: function getSetting(name, defaultval) {
	      return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	    }
	  }, {
	    key: "getOwnerTypeId",
	    value: function getOwnerTypeId() {
	      return this._ownerTypeId;
	    }
	  }, {
	    key: "getOwnerId",
	    value: function getOwnerId() {
	      return this._ownerId;
	    }
	  }, {
	    key: "getOwnerInfo",
	    value: function getOwnerInfo() {
	      return this._ownerInfo;
	    }
	  }, {
	    key: "isStubCounterEnabled",
	    value: function isStubCounterEnabled() {
	      return false;
	    }
	  }, {
	    key: "getSchedule",
	    value: function getSchedule() {
	      return this._schedule;
	    }
	  }, {
	    key: "getHistory",
	    value: function getHistory() {
	      return this._history;
	    }
	  }, {
	    key: "getFixedHistory",
	    value: function getFixedHistory() {
	      return this._fixedHistory;
	    }
	  }, {
	    key: "processSheduleLayoutChange",
	    value: function processSheduleLayoutChange() {}
	  }, {
	    key: "processHistoryLayoutChange",
	    value: function processHistoryLayoutChange() {
	      this._schedule.refreshLayout();
	    }
	  }, {
	    key: "addScheduleItem",
	    value: function addScheduleItem(data) {
	      const item = this._schedule.createItem(data);
	      const index = this._schedule.calculateItemIndex(item);
	      const anchor = this._schedule.createAnchor(index);
	      this._schedule.addItem(item, index);
	      item.layout({
	        anchor: anchor
	      });
	      return item;
	    }
	  }, {
	    key: "addHistoryItem",
	    value: function addHistoryItem(data) {
	      const item = this._history.createItem(data);
	      const index = this._history.calculateItemIndex(item);
	      const historyAnchor = this._history.createAnchor(index);
	      this._history.addItem(item, index);
	      item.layout({
	        anchor: historyAnchor
	      });
	      return item;
	    }
	  }, {
	    key: "renderAudioDummy",
	    value: function renderAudioDummy(durationText, onClick) {
	      return BX.create("DIV", {
	        attrs: {
	          className: "crm-audio-cap-wrap-container"
	        },
	        children: [BX.create("DIV", {
	          attrs: {
	            className: "crm-audio-cap-wrap"
	          },
	          children: [BX.create("DIV", {
	            attrs: {
	              className: "crm-audio-cap-time"
	            },
	            text: durationText
	          })],
	          events: {
	            click: onClick
	          }
	        })]
	      });
	    }
	  }, {
	    key: "loadMediaPlayer",
	    value: function loadMediaPlayer(id, filePath, mediaType, node, duration, options) {
	      if (!duration) {
	        duration = 0;
	      }
	      if (!options) {
	        options = {};
	      }
	      const player = new BX.Fileman.Player(id, {
	        sources: [{
	          src: filePath,
	          type: mediaType
	        }],
	        isAudio: !options.video,
	        skin: options.hasOwnProperty('skin') ? options.skin : 'vjs-timeline_player-skin',
	        width: options.width || 350,
	        height: options.height || 30,
	        duration: duration,
	        playbackRate: options.playbackRate || null,
	        onInit: function (player) {
	          player.vjsPlayer.controlBar.removeChild('timeDivider');
	          player.vjsPlayer.controlBar.removeChild('durationDisplay');
	          player.vjsPlayer.controlBar.removeChild('fullscreenToggle');
	          player.vjsPlayer.controlBar.addChild('timeDivider');
	          player.vjsPlayer.controlBar.addChild('durationDisplay');
	          if (!player.isPlaying()) {
	            player.play();
	          }
	        }
	      });
	      BX.cleanNode(node, false);
	      node.appendChild(player.createElement());
	      player.init();
	      // todo remove this after player will be able to get float playbackRate
	      if (options.playbackRate > 1) {
	        player.vjsPlayer.playbackRate(options.playbackRate);
	      }
	      return player;
	    }
	  }, {
	    key: "onActivityCreated",
	    value: function onActivityCreated(activity, data) {
	      //Already processed in onPullEvent
	    }
	  }, {
	    key: "isSpotlightShowed",
	    value: function isSpotlightShowed() {
	      return this._spotlightFastenShowed;
	    }
	  }, {
	    key: "setSpotlightShowed",
	    value: function setSpotlightShowed() {
	      this._spotlightFastenShowed = true;
	    }
	  }, {
	    key: "getCurrentUser",
	    value: function getCurrentUser() {
	      if (BX.type.isObject(this._currentUser) && this._userId > 0) {
	        this._currentUser.userId = this._userId;
	      }
	      return this._currentUser;
	    }
	  }, {
	    key: "getAudioPlaybackRateSelector",
	    value: function getAudioPlaybackRateSelector() {
	      if (!this.audioPlaybackRateSelector) {
	        this.audioPlaybackRateSelector = new AudioPlaybackRateSelector({
	          name: 'timeline_audio_playback',
	          currentRate: this._audioPlaybackRate,
	          availableRates: [{
	            rate: 1,
	            html: BX.Loc.getMessage('CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_1').replace('#RATE#', '<span class="crm-audio-cap-speed-param">1x</span>')
	          }, {
	            rate: 1.5,
	            html: BX.Loc.getMessage('CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_1.5').replace('#RATE#', '<span class="crm-audio-cap-speed-param">1.5x</span>')
	          }, {
	            rate: 2,
	            html: BX.Loc.getMessage('CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_2').replace('#RATE#', '<span class="crm-audio-cap-speed-param">2x</span>')
	          }, {
	            rate: 3,
	            html: BX.Loc.getMessage('CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_3').replace('#RATE#', '<span class="crm-audio-cap-speed-param">3x</span>')
	          }],
	          textMessageCode: 'CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_TEXT'
	        });
	      }
	      return this.audioPlaybackRateSelector;
	    }
	  }, {
	    key: "hasScheduledItems",
	    value: function hasScheduledItems() {
	      return this._schedule.getItems().length > 0;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new Manager();
	      self.initialize(id, settings);
	      Manager.instances[self.getId()] = self;
	      return self;
	    }
	  }, {
	    key: "getDefault",
	    value: function getDefault() {
	      return _classStaticPrivateFieldSpecGet(Manager, Manager, _defaultInstance);
	    }
	  }, {
	    key: "setDefault",
	    value: function setDefault(instance) {
	      _classStaticPrivateFieldSpecSet(Manager, Manager, _defaultInstance, instance);
	    }
	  }, {
	    key: "getById",
	    value: function getById(id) {
	      return Manager.instances[id] || null;
	    }
	  }]);
	  return Manager;
	}();
	var _defaultInstance = {
	  writable: true,
	  value: null
	};
	babelHelpers.defineProperty(Manager, "instances", {});

	/** @memberof BX.Crm.Timeline.Actions */
	let SchedulePostpone = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(SchedulePostpone, _Activity);
	  function SchedulePostpone() {
	    var _this;
	    babelHelpers.classCallCheck(this, SchedulePostpone);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SchedulePostpone).call(this));
	    _this._button = null;
	    _this._clickHandler = BX.delegate(_this.onClick, babelHelpers.assertThisInitialized(_this));
	    _this._isMenuShown = false;
	    _this._menu = false;
	    return _this;
	  }
	  babelHelpers.createClass(SchedulePostpone, [{
	    key: "doLayout",
	    value: function doLayout() {
	      this._button = BX.create("DIV", {
	        attrs: {
	          className: this._isEnabled ? "crm-entity-stream-planned-action-aside" : "crm-entity-stream-planned-action-aside-disabled"
	        },
	        text: this.getMessage("postpone")
	      });
	      if (this._isEnabled) {
	        BX.bind(this._button, "click", this._clickHandler);
	      }
	      this._container.appendChild(this._button);
	    }
	  }, {
	    key: "openMenu",
	    value: function openMenu() {
	      if (this._isMenuShown) {
	        return;
	      }
	      const handler = BX.delegate(this.onMenuItemClick, this);
	      const menuItems = [{
	        id: "hour_1",
	        text: this.getMessage("forOneHour"),
	        onclick: handler
	      }, {
	        id: "hour_2",
	        text: this.getMessage("forTwoHours"),
	        onclick: handler
	      }, {
	        id: "hour_3",
	        text: this.getMessage("forThreeHours"),
	        onclick: handler
	      }, {
	        id: "day_1",
	        text: this.getMessage("forOneDay"),
	        onclick: handler
	      }, {
	        id: "day_2",
	        text: this.getMessage("forTwoDays"),
	        onclick: handler
	      }, {
	        id: "day_3",
	        text: this.getMessage("forThreeDays"),
	        onclick: handler
	      }];
	      BX.PopupMenu.show(this._id, this._button, menuItems, {
	        offsetTop: 0,
	        offsetLeft: 16,
	        events: {
	          onPopupShow: BX.delegate(this.onMenuShow, this),
	          onPopupClose: BX.delegate(this.onMenuClose, this),
	          onPopupDestroy: BX.delegate(this.onMenuDestroy, this)
	        }
	      });
	      this._menu = BX.PopupMenu.currentItem;
	    }
	  }, {
	    key: "closeMenu",
	    value: function closeMenu() {
	      if (!this._isMenuShown) {
	        return;
	      }
	      if (this._menu) {
	        this._menu.close();
	      }
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      if (!this._isEnabled) {
	        return;
	      }
	      if (this._isMenuShown) {
	        this.closeMenu();
	      } else {
	        this.openMenu();
	      }
	    }
	  }, {
	    key: "onMenuItemClick",
	    value: function onMenuItemClick(e, item) {
	      this.closeMenu();
	      let offset = 0;
	      if (item.id === "hour_1") {
	        offset = 3600;
	      } else if (item.id === "hour_2") {
	        offset = 7200;
	      } else if (item.id === "hour_3") {
	        offset = 10800;
	      } else if (item.id === "day_1") {
	        offset = 86400;
	      } else if (item.id === "day_2") {
	        offset = 172800;
	      } else if (item.id === "day_3") {
	        offset = 259200;
	      }
	      if (offset > 0 && this._item) {
	        this._item.postpone(offset);
	      }
	    }
	  }, {
	    key: "onMenuShow",
	    value: function onMenuShow() {
	      this._isMenuShown = true;
	    }
	  }, {
	    key: "onMenuClose",
	    value: function onMenuClose() {
	      if (this._menu && this._menu.popupWindow) {
	        this._menu.popupWindow.destroy();
	      }
	    }
	  }, {
	    key: "onMenuDestroy",
	    value: function onMenuDestroy() {
	      this._isMenuShown = false;
	      this._menu = null;
	      if (typeof BX.PopupMenu.Data[this._id] !== "undefined") {
	        delete BX.PopupMenu.Data[this._id];
	      }
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      const m = SchedulePostpone.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new SchedulePostpone();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return SchedulePostpone;
	}(Activity$1);
	babelHelpers.defineProperty(SchedulePostpone, "messages", {});

	/** @memberof BX.Crm.Timeline.Animation */
	let Comment$2 = /*#__PURE__*/function () {
	  function Comment() {
	    babelHelpers.classCallCheck(this, Comment);
	    this._node = null;
	    this._anchor = null;
	    this._nodeParent = null;
	    this._startPosition = null;
	    this._events = null;
	  }
	  babelHelpers.createClass(Comment, [{
	    key: "initialize",
	    value: function initialize(node, anchor, startPosition, events) {
	      this._node = node;
	      this._anchor = anchor;
	      this._nodeParent = node.parentNode;
	      this._startPosition = startPosition;
	      this._events = BX.type.isPlainObject(events) ? events : {};
	    }
	  }, {
	    key: "run",
	    value: function run() {
	      BX.addClass(this._node, 'crm-entity-stream-section-animate-start');
	      this._node.style.position = "absolute";
	      this._node.style.width = this._startPosition.width + "px";
	      this._node.style.height = this._startPosition.height + "px";
	      this._node.style.top = this._startPosition.top - 30 + "px";
	      this._node.style.left = this._startPosition.left + "px";
	      this._node.style.opacity = 0;
	      this._node.style.zIndex = 960;
	      document.body.appendChild(this._node);
	      const nodeOpacityAnim = new BX.easing({
	        duration: 350,
	        start: {
	          opacity: 0
	        },
	        finish: {
	          opacity: 100
	        },
	        transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: BX.proxy(function (state) {
	          this._node.style.opacity = state.opacity / 100;
	        }, this),
	        complete: BX.proxy(function () {
	          if (BX.type.isFunction(this._events["start"])) {
	            this._events["start"]();
	          }
	          const shift = Shift.create(this._node, this._anchor, this._startPosition, false, {
	            complete: BX.delegate(this.finish, this)
	          });
	          shift.run();
	        }, this)
	      });
	      nodeOpacityAnim.animate();
	      if (BX.type.isFunction(this._events["complete"])) {
	        this._events["complete"]();
	      }
	    }
	  }, {
	    key: "finish",
	    value: function finish() {
	      this._node.style.position = "";
	      this._node.style.width = "";
	      this._node.style.height = "";
	      this._node.style.top = "";
	      this._node.style.left = "";
	      this._node.style.opacity = "";
	      this._node.style.zIndex = "";
	      this._anchor.style.height = "";
	      this._anchor.parentNode.insertBefore(this._node, this._anchor.nextSibling);
	      setTimeout(BX.delegate(function () {
	        BX.removeClass(this._node, 'crm-entity-stream-section-animate-start');
	        BX.remove(this._anchor);
	      }, this), 0);
	    }
	  }], [{
	    key: "create",
	    value: function create(node, anchor, startPosition, events) {
	      const self = new Comment();
	      self.initialize(node, anchor, startPosition, events);
	      return self;
	    }
	  }]);
	  return Comment;
	}();

	const Streams = {
	  History: History$1,
	  FixedHistory,
	  EntityChat,
	  Schedule
	};
	const Tools = {
	  SchedulePostponeController,
	  AudioPlaybackRateSelector
	};
	const Actions = {
	  Activity: Activity$1,
	  Call: Call$1,
	  HistoryCall,
	  ScheduleCall,
	  Email: Email$1,
	  HistoryEmail,
	  ScheduleEmail,
	  OpenLine,
	  SchedulePostpone
	};
	const ScheduledItems = {
	  Activity: Activity,
	  Email: Email,
	  Call: Call,
	  CallTracker,
	  Meeting: Meeting,
	  Task: Task,
	  WebForm: WebForm,
	  Wait: Wait,
	  Request: Request,
	  Rest: Rest,
	  OpenLine: OpenLine$1,
	  Zoom: Zoom
	};
	const Items = {
	  History: History,
	  HistoryActivity,
	  Comment: Comment$1,
	  Modification,
	  Mark: Mark$1,
	  Creation,
	  Restoration,
	  Relation,
	  Link,
	  Unlink,
	  Email: Email$2,
	  Call: Call$2,
	  Meeting: Meeting$1,
	  Task: Task$1,
	  WebForm: WebForm$1,
	  Wait: Wait$1,
	  Document,
	  Sender,
	  Bizproc,
	  Request: Request$1,
	  Rest: Rest$1,
	  OpenLine: OpenLine$2,
	  Zoom: Zoom$1,
	  Conversion,
	  Visit,
	  Scoring,
	  ExternalNoticeModification,
	  ExternalNoticeStatusModification,
	  ScheduledBase: Scheduled,
	  Scheduled: ScheduledItems
	};
	const Animations = {
	  Item: Item$1,
	  ItemNew,
	  Expand,
	  Shift,
	  Comment: Comment$2,
	  Fasten
	};

	exports.Manager = Manager;
	exports.Stream = Steam;
	exports.Streams = Streams;
	exports.Tools = Tools;
	exports.Types = types;
	exports.Action = Action;
	exports.Actions = Actions;
	exports.Items = Items;
	exports.Animations = Animations;
	exports.CompatibleItem = CompatibleItem;

}((this.BX.Crm.Timeline = this.BX.Crm.Timeline || {}),BX.Crm.DateTime,BX.Crm.Timeline,BX.Crm.Timeline,BX));
//# sourceMappingURL=timeline.bundle.js.map
