this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core_events,currency,ui_notification,pull_client,ui_vue,main_core) {
	'use strict';

	/** @memberof BX.Crm.Timeline.Types */
	var Item = {
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
	  storeDocument: 21
	};
	/** @memberof BX.Crm.Timeline.Types */

	var Mark = {
	  undefined: 0,
	  waiting: 1,
	  success: 2,
	  renew: 3,
	  ignored: 4,
	  failed: 5
	};
	/** @memberof BX.Crm.Timeline.Types */

	var Delivery = {
	  undefined: 0,
	  taxiEstimationRequest: 1,
	  taxiCallRequest: 2,
	  taxiCancelledByManager: 3,
	  taxiCancelledByDriver: 4,
	  taxiPerformerNotFound: 5,
	  taxiSmsProviderIssue: 6,
	  taxiReturnedFinish: 7,
	  deliveryMessage: 101,
	  deliveryCalculation: 102
	};
	/** @memberof BX.Crm.Timeline.Types */

	var Order = {
	  encourageBuyProducts: 100
	};
	/** @memberof BX.Crm.Timeline.Types */

	var EditorMode = {
	  view: 1,
	  edit: 2
	};

	var types = /*#__PURE__*/Object.freeze({
		Item: Item,
		Mark: Mark,
		Delivery: Delivery,
		Order: Order,
		EditorMode: EditorMode
	});

	/** @memberof BX.Crm.Timeline */

	var Item$1 = /*#__PURE__*/function () {
	  function Item$$1() {
	    babelHelpers.classCallCheck(this, Item$$1);
	    this._id = "";
	    this._settings = {};
	    this._data = {};
	    this._container = null;
	    this._wrapper = null;
	    this._typeCategoryId = null;
	    this._associatedEntityData = null;
	    this._associatedEntityTypeId = null;
	    this._associatedEntityId = null;
	    this._isContextMenuShown = false;
	    this._contextMenuButton = null;
	    this._activityEditor = null;
	    this._actions = [];
	    this._actionContainer = null;
	    this._isTerminated = false;
	    this._vueComponent = null;
	    this._vueComponentMountedNode = null;
	  }

	  babelHelpers.createClass(Item$$1, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._settings = settings ? settings : {};
	      this._container = this.getSetting("container");

	      if (!BX.type.isPlainObject(settings['data'])) {
	        throw "Item. A required parameter 'data' is missing.";
	      }

	      this._data = settings['data'];
	      this._activityEditor = this.getSetting("activityEditor");
	      this._vueComponent = this.getSetting("vueComponent");
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

	      this._data["ASSOCIATED_ENTITY"] = associatedEntityData;
	      this.clearCachedData();
	    }
	  }, {
	    key: "hasPermissions",
	    value: function hasPermissions() {
	      var entityData = this.getAssociatedEntityData();
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
	      if (!BX.type.isPlainObject(this._data["ASSOCIATED_ENTITY"])) {
	        this._data["ASSOCIATED_ENTITY"] = {};
	      }

	      this._data["ASSOCIATED_ENTITY"]["PERMISSIONS"] = permissions;
	      this.clearCachedData();
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
	    key: "getWrapper",
	    value: function getWrapper() {
	      return this._wrapper;
	    }
	  }, {
	    key: "addWrapperClass",
	    value: function addWrapperClass(className, timeout) {
	      if (!this._wrapper) {
	        return;
	      }

	      BX.addClass(this._wrapper, className);

	      if (BX.type.isNumber(timeout) && timeout >= 0) {
	        window.setTimeout(BX.delegate(function () {
	          this.removeWrapperClass(className);
	        }, this), timeout);
	      }
	    }
	  }, {
	    key: "removeWrapperClass",
	    value: function removeWrapperClass(className, timeout) {
	      if (!this._wrapper) {
	        return;
	      }

	      BX.removeClass(this._wrapper, className);

	      if (BX.type.isNumber(timeout) && timeout >= 0) {
	        window.setTimeout(BX.delegate(function () {
	          this.addWrapperClass(className);
	        }, this), timeout);
	      }
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      if (!BX.type.isElementNode(this._container)) {
	        throw "Item. Container is not assigned.";
	      }

	      this.prepareLayout(options); //region Actions

	      /**/

	      this.prepareActions();
	      var actionQty = this._actions.length;

	      for (var i = 0; i < actionQty; i++) {
	        this._actions[i].layout();
	      }

	      this.showActions(actionQty > 0);
	      /**/
	      //endregion
	    }
	  }, {
	    key: "makeVueComponent",
	    value: function makeVueComponent(options, mode) {
	      if (this._vueComponentMountedNode) {
	        return this._vueComponentMountedNode;
	      }

	      if (!this._vueComponent) {
	        return null;
	      }

	      var app = new this._vueComponent({
	        propsData: {
	          self: this,
	          langMessages: Item$$1.messages,
	          mode: mode
	        }
	      });
	      app.$mount();
	      this._vueComponentMountedNode = app.$el;
	      return this._vueComponentMountedNode;
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
	    key: "clearLayout",
	    value: function clearLayout() {
	      this._wrapper = BX.remove(this._wrapper);
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {
	      var anchor = this._wrapper.previousSibling;
	      this._wrapper = BX.remove(this._wrapper);
	      this._playerWrappers = {};
	      this.layout({
	        anchor: anchor
	      });
	    }
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
	    key: "isTerminated",
	    value: function isTerminated() {
	      return this._isTerminated;
	    }
	  }, {
	    key: "markAsTerminated",
	    value: function markAsTerminated(terminated) {
	      terminated = !!terminated;

	      if (this._isTerminated === terminated) {
	        return;
	      }

	      this._isTerminated = terminated;

	      if (!this._wrapper) {
	        return;
	      }

	      if (terminated) {
	        BX.addClass(this._wrapper, "crm-entity-stream-section-last");
	      } else {
	        BX.removeClass(this._wrapper, "crm-entity-stream-section-last");
	      }
	    }
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

	      var offset = length - 1;
	      var whilespaceOffset = text.substring(offset).search(/\s/i);

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

	      var offset = length - 1;
	      var whilespaceOffset = text.substring(offset).search(/\s/i);

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

	      var offset = length - 1;
	      var whilespaceOffset = text.substring(offset).search(/\s/i);

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
	      var authorInfo = this.getObjectDataParam("AUTHOR", null);

	      if (!authorInfo) {
	        return null;
	      }

	      var showUrl = BX.prop.getString(authorInfo, "SHOW_URL", "");

	      if (showUrl === "") {
	        return null;
	      }

	      var link = BX.create("A", {
	        attrs: {
	          className: "ui-icon ui-icon-common-user crm-entity-stream-content-detail-employee",
	          href: showUrl,
	          target: "_blank",
	          title: BX.prop.getString(authorInfo, "FORMATTED_NAME", "")
	        },
	        children: [BX.create('i', {})]
	      });
	      var imageUrl = BX.prop.getString(authorInfo, "IMAGE_URL", "");

	      if (imageUrl !== "") {
	        link.children[0].style.backgroundImage = "url('" + imageUrl + "')";
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
	      var menuItems = this.prepareContextMenuItems();

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
	      var m = Item$$1.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "getUserTimezoneOffset",
	    value: function getUserTimezoneOffset() {
	      if (!this.userTimezoneOffset) {
	        this.userTimezoneOffset = parseInt(BX.message("USER_TZ_OFFSET"));

	        if (isNaN(this.userTimezoneOffset)) {
	          this.userTimezoneOffset = 0;
	        }
	      }

	      return this.userTimezoneOffset;
	    }
	  }]);
	  return Item$$1;
	}();

	babelHelpers.defineProperty(Item$1, "messages", {});

	/** @memberof BX.Crm.Timeline.Animation */
	var Fasten = /*#__PURE__*/function () {
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
	      var node = this._finalItem.getWrapper();

	      BX.addClass(node, 'crm-entity-stream-section-animate-start');

	      this._anchor.parentNode.insertBefore(node, this._anchor.nextSibling);

	      setTimeout(BX.delegate(function () {
	        BX.removeClass(node, 'crm-entity-stream-section-animate-start');
	      }, this), 0);

	      this._finalItem.onFinishFasten();
	    }
	  }, {
	    key: "run",
	    value: function run() {
	      var node = this._initialItem.getWrapper();

	      this._clone = node.cloneNode(true);
	      BX.addClass(this._clone, 'crm-entity-stream-section-animate-start crm-entity-stream-section-top-fixed');
	      this._startPosition = BX.pos(node);
	      this._clone.style.position = "absolute";
	      this._clone.style.width = this._startPosition.width + "px";
	      var _cloneHeight = this._startPosition.height;
	      var _minHeight = 65;
	      var _sumPaddingContent = 18;
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
	      var finish = {
	        top: this._anchorPosition.top,
	        height: _cloneHeight + 15,
	        opacity: 1
	      };

	      var _difference = this._startPosition.top - this._anchorPosition.bottom;

	      var _deepHistoryLimit = 2 * (document.body.clientHeight + this._startPosition.height);

	      if (_difference > _deepHistoryLimit) {
	        finish.top = this._startPosition.top - _deepHistoryLimit;
	        finish.opacity = 0;
	      }

	      var _duration = Math.abs(finish.top - this._startPosition.top) * 2;

	      _duration = _duration < 1500 ? 1500 : _duration;
	      var movingEvent = new BX.easing({
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
	      var self = new Fasten();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Fasten;
	}();

	/** @memberof BX.Crm.Timeline.Items */

	var History = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(History, _Item);

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
	        var time = BX.parseDate(this.getCreatedTimestamp(), false, "YYYY-MM-DD", "YYYY-MM-DD HH:MI:SS");
	        this._createdTime = new Date(time.getTime() + 1000 * Item$1.getUserTimezoneOffset());
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
	      var typeId = this.getTypeId();

	      if (typeId === Item.activity) {
	        var entityData = this.getAssociatedEntityData();
	        return BX.CrmActivityStatus.isFinal(BX.prop.getInteger(entityData, "STATUS", 0));
	      }

	      return false;
	    }
	  }, {
	    key: "isFixed",
	    value: function isFixed() {
	      return this._isFixed;
	    }
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
	        },
	        onsuccess: BX.delegate(this.onSuccessFasten, this)
	      });
	      this.closeContextMenu();
	    }
	  }, {
	    key: "onSuccessFasten",
	    value: function onSuccessFasten(result) {
	      if (BX.type.isNotEmptyString(result.ERROR)) return;

	      if (!this.isFixed()) {
	        this._data.IS_FIXED = 'Y';

	        var fixedItem = this._fixedHistory.createItem(this._data);

	        fixedItem._isFixed = true;

	        this._fixedHistory.addItem(fixedItem, 0);

	        fixedItem.layout({
	          add: false
	        });
	        this.refreshLayout();
	        var animation = Fasten.create("", {
	          initialItem: this,
	          finalItem: fixedItem,
	          anchor: this._fixedHistory._anchor
	        });
	        animation.run();
	      }

	      this.closeContextMenu();
	    }
	  }, {
	    key: "onFinishFasten",
	    value: function onFinishFasten(e) {}
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
	        },
	        onsuccess: BX.delegate(this.onSuccessUnfasten, this)
	      });
	      this.closeContextMenu();
	    }
	  }, {
	    key: "onSuccessUnfasten",
	    value: function onSuccessUnfasten(result) {
	      if (BX.type.isNotEmptyString(result.ERROR)) return;
	      var item;
	      var historyItem;

	      if (this.isFixed()) {
	        item = this;
	        historyItem = this._history.findItemById(this._id);
	      } else {
	        item = this._fixedHistory.findItemById(this._id);
	        historyItem = this;
	      }

	      if (item) {
	        var index = this._fixedHistory.getItemIndex(item);

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
	      var wrapperPosition = BX.pos(this._wrapper);
	      var hideEvent = new BX.easing({
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
	      var wrapperClassName = this.getWrapperClassName();

	      if (wrapperClassName !== "") {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history" + " " + wrapperClassName;
	      } else {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history";
	      }

	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: wrapperClassName
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: this.getIconClassName()
	        }
	      }));
	      var contentWrapper = BX.create("DIV", {
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
	      var header = BX.create("DIV", {
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
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion


	      return wrapper;
	    }
	  }, {
	    key: "prepareLayout",
	    value: function prepareLayout(options) {
	      var vueComponent = this.makeVueComponent(options, 'history');
	      this._wrapper = vueComponent ? vueComponent : this.prepareContent();

	      if (this._wrapper) {
	        var enableAdd = BX.type.isPlainObject(options) ? BX.prop.getBoolean(options, "add", true) : true;

	        if (enableAdd) {
	          var anchor = BX.type.isPlainObject(options) && BX.type.isElementNode(options["anchor"]) ? options["anchor"] : null;

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
	      var isFixed = this.getTextDataParam("IS_FIXED") === 'Y';
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
	        var manager = this._history.getManager();

	        if (!manager.isSpotlightShowed()) {
	          manager.setSpotlightShowed();
	          BX.addClass(this._switcher, "crm-entity-stream-section-top-fixed-btn-spotlight");
	          var spotlight = new BX.SpotLight({
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
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      header.appendChild(BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: this.formatTime(this.getCreatedTime())
	      }));
	      return header;
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
	      var self = new History();
	      self.initialize(id, settings);
	      return self;
	    }
	  }, {
	    key: "isCounterEnabled",
	    value: function isCounterEnabled(deadline) {
	      if (!BX.type.isDate(deadline)) {
	        return false;
	      }

	      var start = new Date();
	      start.setHours(0);
	      start.setMinutes(0);
	      start.setSeconds(0);
	      start.setMilliseconds(0);
	      start = start.getTime();
	      var end = new Date();
	      end.setHours(23);
	      end.setMinutes(59);
	      end.setSeconds(59);
	      end.setMilliseconds(999);
	      end = end.getTime();
	      var time = deadline.getTime();
	      return time < start || time >= start && time <= end;
	    }
	  }]);
	  return History;
	}(Item$1);

	var HistoryItemMixin = {
	  props: {
	    self: {
	      required: true,
	      type: Object
	    },
	    langMessages: {
	      required: false,
	      type: Object
	    }
	  },
	  computed: {
	    data: function data() {
	      return this.self._data;
	    },
	    fields: function fields() {
	      return this.data.FIELDS ? this.data.FIELDS : null;
	    },
	    author: function author() {
	      return this.data.AUTHOR ? this.data.AUTHOR : null;
	    },
	    createdAt: function createdAt() {
	      return this.self instanceof History ? this.self.formatTime(this.self.getCreatedTime()) : '';
	    }
	  },
	  methods: {
	    getLangMessage: function getLangMessage(key) {
	      return this.langMessages.hasOwnProperty(key) ? this.langMessages[key] : key;
	    }
	  }
	};

	var Product = {
	  props: {
	    product: {
	      required: true,
	      type: Object
	    },
	    dealId: {
	      required: true,
	      type: Number
	    },
	    isAddToDealVisible: {
	      required: true,
	      type: Boolean
	    }
	  },
	  methods: {
	    addProductToDeal: function addProductToDeal() {
	      var _this = this;

	      if (this.product.isInDeal) {
	        return;
	      }

	      this.$emit('product-adding-to-deal');
	      this.product.isInDeal = true;
	      main_core.ajax.runAction('crm.timeline.encouragebuyproducts.addproducttodeal', {
	        data: {
	          dealId: this.dealId,
	          productId: this.product.offerId,
	          options: {
	            price: this.product.price
	          }
	        }
	      }).then(function (result) {
	        _this.$emit('product-added-to-deal');

	        _this.product.isInDeal = true;
	      })["catch"](function (result) {
	        _this.product.isInDeal = false;
	      });
	    },
	    openDetailPage: function openDetailPage() {
	      if (BX.type.isNotEmptyString(this.product.adminLink)) {
	        var _this$product;

	        if (((_this$product = this.product) === null || _this$product === void 0 ? void 0 : _this$product.slider) === 'N') {
	          window.open(this.product.adminLink, '_blank');
	        } else {
	          BX.SidePanel.Instance.open(this.product.adminLink);
	        }
	      }
	    }
	  },
	  computed: {
	    isBottomAreaVisible: function isBottomAreaVisible() {
	      return this.isVariationInfoVisible || this.isPriceVisible;
	    },
	    isVariationInfoVisible: function isVariationInfoVisible() {
	      return this.product.hasOwnProperty('variationInfo') && this.product.variationInfo;
	    },
	    isPriceVisible: function isPriceVisible() {
	      return this.product.hasOwnProperty('price') && this.product.hasOwnProperty('currency') && this.product.price && this.product.currency;
	    },
	    price: function price() {
	      return BX.Currency.currencyFormat(this.product.price, this.product.currency, true);
	    },
	    imageStyle: function imageStyle() {
	      if (!this.product.image) {
	        return {};
	      }

	      return {
	        backgroundImage: 'url(' + this.product.image + ')'
	      };
	    },
	    buttonText: function buttonText() {
	      return main_core.Loc.getMessage(this.product.isInDeal ? 'CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_PRODUCT_IN_DEAL' : 'CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_ADD_PRODUCT_TO_DEAL');
	    }
	  },
	  template: "\n\t\t<li\n\t\t\t:class=\"{'crm-entity-stream-advice-list-item--active': product.isInDeal}\"\n\t\t\tclass=\"crm-entity-stream-advice-list-item\"\n\t\t>\t\n\t\t\t<div class=\"crm-entity-stream-advice-list-content\">\n\t\t\t\t<div\t\n\t\t\t\t\t:style=\"imageStyle\"\n\t\t\t\t\tclass=\"crm-entity-stream-advice-list-icon\"\n\t\t\t\t>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-advice-list-inner\">\n\t\t\t\t\t<a\n\t\t\t\t\t\t@click.prevent=\"openDetailPage\"\n\t\t\t\t\t\thref=\"#\"\n\t\t\t\t\t\tclass=\"crm-entity-stream-advice-list-name\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{product.name}}\n\t\t\t\t\t</a>\n\t\t\t\t\t<div\n\t\t\t\t\t\tv-if=\"isBottomAreaVisible\"\n\t\t\t\t\t\tclass=\"crm-entity-stream-advice-list-desc-box\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tv-if=\"isVariationInfoVisible\"\n\t\t\t\t\t\t\tclass=\"crm-entity-stream-advice-list-desc-name\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{product.variationInfo}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tv-if=\"isPriceVisible\"\n\t\t\t\t\t\t\tv-html=\"price\"\n\t\t\t\t\t\t\tclass=\"crm-entity-stream-advice-list-desc-value\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div v-if=\"isAddToDealVisible\" class=\"crm-entity-stream-advice-list-btn-box\">\t\t\t\t\n\t\t\t\t<button\n\t\t\t\t\t@click=\"addProductToDeal\"\n\t\t\t\t\tclass=\"ui-btn ui-btn-round ui-btn-xs crm-entity-stream-advice-list-btn\"\n\t\t\t\t>\n\t\t\t\t\t{{buttonText}}\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t</li>\n\t"
	};

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var component = ui_vue.Vue.extend({
	  mixins: [HistoryItemMixin],
	  components: {
	    'product': Product
	  },
	  data: function data() {
	    return {
	      isShortList: true,
	      shortListProductsCnt: 3,
	      isNotificationShown: false,
	      activeRequestsCnt: 0,
	      dealId: null,
	      products: [],
	      isProductsGridAvailable: false
	    };
	  },
	  created: function created() {
	    var _this = this;

	    this.products = this.data.VIEWED_PRODUCTS;
	    this.dealId = this.data.DEAL_ID;
	    this._productsGrid = null;
	    this.subscribeCustomEvents();
	    BX.Crm.EntityEditor.getDefault().tapController('PRODUCT_LIST', function (controller) {
	      _this.setProductsGrid(controller.getProductList());
	    });
	  },
	  methods: {
	    setProductsGrid: function setProductsGrid(productsGrid) {
	      this._productsGrid = productsGrid;

	      if (this._productsGrid) {
	        this.onProductsGridChanged();
	        this.isProductsGridAvailable = true;
	      }
	    },
	    showMore: function showMore() {
	      this.isShortList = false;
	      var listWrap = document.querySelector('.crm-entity-stream-advice-list');
	      listWrap.style.maxHeight = 950 + 'px';
	    },
	    // region event handlers
	    handleProductAddingToDeal: function handleProductAddingToDeal() {
	      this.activeRequestsCnt++;
	    },
	    handleProductAddedToDeal: function handleProductAddedToDeal() {
	      var _this2 = this;

	      if (this.activeRequestsCnt > 0) {
	        this.activeRequestsCnt--;
	      }

	      if (!(this.activeRequestsCnt === 0 && this._productsGrid)) {
	        return;
	      }

	      BX.Crm.EntityEditor.getDefault().reload();

	      this._productsGrid.reloadGrid(false);

	      if (!this.isNotificationShown) {
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_PRODUCTS_ADDED_TO_DEAL'),
	          events: {
	            onClose: function onClose(event) {
	              _this2.isNotificationShown = false;
	            }
	          },
	          actions: [{
	            title: main_core.Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_EDIT_PRODUCTS'),
	            events: {
	              click: function click(event, balloon, action) {
	                BX.onCustomEvent(window, 'OpenEntityDetailTab', ['tab_products']);
	                balloon.close();
	              }
	            }
	          }]
	        });
	        this.isNotificationShown = true;
	      }
	    },
	    // endregion
	    // region custom events
	    subscribeCustomEvents: function subscribeCustomEvents() {
	      main_core_events.EventEmitter.subscribe('EntityProductListController', this.onProductsGridCreated);
	      main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onSave', this.onProductsGridChanged);
	    },
	    unsubscribeCustomEvents: function unsubscribeCustomEvents() {
	      main_core_events.EventEmitter.unsubscribe('EntityProductListController', this.onProductsGridCreated);
	      main_core_events.EventEmitter.unsubscribe('BX.Crm.EntityEditor:onSave', this.onProductsGridChanged);
	    },
	    onProductsGridCreated: function onProductsGridCreated(event) {
	      this.setProductsGrid(event.getData()[0]);
	    },
	    onProductsGridChanged: function onProductsGridChanged(event) {
	      var _this3 = this;

	      if (!this._productsGrid) {
	        return;
	      }

	      var dealOfferIds = this._productsGrid.products.map(function (product, index) {
	        if (!(product.hasOwnProperty('fields') && product.fields.hasOwnProperty('OFFER_ID'))) {
	          return null;
	        }

	        return product.fields.OFFER_ID;
	      });

	      var _iterator = _createForOfIteratorHelper(this.products.entries()),
	          _step;

	      try {
	        var _loop = function _loop() {
	          var _step$value = babelHelpers.slicedToArray(_step.value, 2),
	              i = _step$value[0],
	              product = _step$value[1];

	          var isInDeal = dealOfferIds.some(function (id) {
	            return id == product.offerId;
	          });

	          if (product.isInDeal === isInDeal) {
	            return "continue";
	          }

	          ui_vue.Vue.set(_this3.products, i, Object.assign({}, product, {
	            isInDeal: isInDeal
	          }));
	        };

	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var _ret = _loop();

	          if (_ret === "continue") continue;
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	    },
	    // endregion
	    beforeDestroy: function beforeDestroy() {
	      this.unsubscribeCustomEvents();
	    }
	  },
	  computed: {
	    visibleProducts: function visibleProducts() {
	      var result = [];
	      var i = 1;

	      var _iterator2 = _createForOfIteratorHelper(this.products),
	          _step2;

	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var product = _step2.value;

	          if (this.isShortList && i > this.shortListProductsCnt) {
	            break;
	          }

	          result.push(product);
	          i++;
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }

	      return result;
	    },
	    isShowMoreVisible: function isShowMoreVisible() {
	      return this.isShortList && this.products.length > this.shortListProductsCnt;
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-section crm-entity-stream-section-advice\">\n\t\t\t<div class=\"crm-entity-stream-section-icon crm-entity-stream-section-icon-advice\"></div>\n\t\t\t<div class=\"crm-entity-stream-advice-content\">\n\t\t\t\t<div class=\"crm-entity-stream-advice-info\">\n\t\t\t\t\t".concat(main_core.Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_LOOK_AT_CLIENT_PRODUCTS'), "\n\t\t\t\t\t").concat(main_core.Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_ENCOURAGE_CLIENT_BUY_PRODUCTS'), "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-advice-inner\">\n\t\t\t\t\t<h3 class=\"crm-entity-stream-advice-subtitle\">\n\t\t\t\t\t\t").concat(main_core.Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_VIEWED_PRODUCTS'), "\n\t\t\t\t\t</h3>\n\t\t\t\t\t<!--<ul class=\"crm-entity-stream-advice-list\">-->\n\t\t\t\t\t<transition-group class=\"crm-entity-stream-advice-list\" name=\"list\" tag=\"ul\">\t\t\t\t\t\t\n\t\t\t\t\t\t<product\n\t\t\t\t\t\t\tv-for=\"product in visibleProducts\"\n\t\t\t\t\t\t\tv-bind:key=\"product\"\n\t\t\t\t\t\t\t:product=\"product\"\n\t\t\t\t\t\t\t:dealId=\"dealId\"\n\t\t\t\t\t\t\t:isAddToDealVisible=\"isProductsGridAvailable\"\n\t\t\t\t\t\t\t@product-added-to-deal=\"handleProductAddedToDeal\"\n\t\t\t\t\t\t\t@product-adding-to-deal=\"handleProductAddingToDeal\"\n\t\t\t\t\t\t></product>\n\t\t\t\t\t</transition-group>\n\t\t\t\t\t<!--</ul>-->\n\t\t\t\t\t<a\n\t\t\t\t\t\tv-if=\"isShowMoreVisible\"\n\t\t\t\t\t\t@click.prevent=\"showMore\"\n\t\t\t\t\t\tclass=\"crm-entity-stream-advice-link\"\n\t\t\t\t\t\thref=\"#\"\n\t\t\t\t\t>\n\t\t\t\t\t\t").concat(main_core.Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_SHOW_MORE'), "\n\t\t\t\t\t</a>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t")
	});

	var Author = {
	  props: {
	    author: {
	      required: true,
	      type: Object
	    }
	  },
	  computed: {
	    iStyle: function iStyle() {
	      if (!this.author.IMAGE_URL) {
	        return {};
	      }

	      return {
	        'background-image': 'url(' + this.author.IMAGE_URL + ')',
	        'background-size': '21px'
	      };
	    }
	  },
	  template: "\n\t\t<a\n\t\t\tv-if=\"author.SHOW_URL\"\n\t\t\t:href=\"author.SHOW_URL\"\n\t\t\ttarget=\"_blank\"\n\t\t\t:title=\"author.FORMATTED_NAME\"\n\t\t\tclass=\"ui-icon ui-icon-common-user crm-entity-stream-content-detail-employee\"\n\t\t>\n\t\t\t<i :style=\"iStyle\"></i>\t\n\t\t</a>\n\t"
	};

	var component$1 = ui_vue.Vue.extend({
	  mixins: [HistoryItemMixin],
	  components: {
	    'author': Author
	  },
	  data: function data() {
	    return {
	      entityData: null,
	      messageId: null,
	      text: null,
	      title: null,
	      status: {
	        name: null,
	        semantics: null,
	        description: null
	      },
	      provider: null,
	      isRefreshing: false
	    };
	  },
	  created: function created() {
	    var _this = this;

	    this.entityData = this.self.getAssociatedEntityData();

	    if (this.entityData['MESSAGE_INFO']) {
	      this.setMessageInfo(this.entityData['MESSAGE_INFO']);
	    }

	    pull_client.PULL.subscribe({
	      moduleId: 'notifications',
	      command: 'message_update',
	      callback: function callback(params) {
	        if (params.message.ID == _this.messageId) {
	          _this.refresh();
	        }
	      }
	    });

	    if (this.entityData['PULL_TAG_NAME']) {
	      pull_client.PULL.extendWatch(this.entityData['PULL_TAG_NAME']);
	    }
	  },
	  methods: {
	    setMessageInfo: function setMessageInfo(messageInfo) {
	      this.messageId = messageInfo['MESSAGE']['ID'];

	      if (messageInfo['HISTORY_ITEMS'] && Array.isArray(messageInfo['HISTORY_ITEMS']) && messageInfo['HISTORY_ITEMS'].length > 0 && messageInfo['HISTORY_ITEMS'][0] && messageInfo['HISTORY_ITEMS'][0]['PROVIDER_DATA'] && messageInfo['HISTORY_ITEMS'][0]['PROVIDER_DATA']['DESCRIPTION']) {
	        this.provider = messageInfo['HISTORY_ITEMS'][0]['PROVIDER_DATA']['DESCRIPTION'];
	        this.title = this.provider + ' ' + main_core.Loc.getMessage('CRM_TIMELINE_NOTIFICATION_MESSAGE');
	      } else {
	        this.title = this.capitalizeFirstLetter(main_core.Loc.getMessage('CRM_TIMELINE_NOTIFICATION_MESSAGE'));
	      }

	      if (messageInfo['HISTORY_ITEMS'] && Array.isArray(messageInfo['HISTORY_ITEMS']) && messageInfo['HISTORY_ITEMS'].length > 0 && messageInfo['HISTORY_ITEMS'][0] && messageInfo['HISTORY_ITEMS'][0]['STATUS_DATA'] && messageInfo['HISTORY_ITEMS'][0]['STATUS_DATA']['DESCRIPTION']) {
	        this.status.name = messageInfo['HISTORY_ITEMS'][0]['STATUS_DATA']['DESCRIPTION'];
	        this.status.semantics = messageInfo['HISTORY_ITEMS'][0]['STATUS_DATA']['SEMANTICS'];
	        this.status.description = messageInfo['HISTORY_ITEMS'][0]['ERROR_MESSAGE'];
	      }

	      this.text = messageInfo['MESSAGE']['TEXT'] ? messageInfo['MESSAGE']['TEXT'] : main_core.Loc.getMessage('CRM_TIMELINE_NOTIFICATION_NO_MESSAGE_TEXT_2');
	    },
	    refresh: function refresh() {
	      var _this2 = this;

	      if (this.isRefreshing) {
	        return;
	      }

	      this.isRefreshing = true;
	      main_core.ajax.runAction('crm.timeline.notification.getmessageinfo', {
	        data: {
	          messageId: this.messageId
	        }
	      }).then(function (result) {
	        _this2.setMessageInfo(result.data);

	        _this2.isRefreshing = false;
	      })["catch"](function (result) {
	        _this2.isRefreshing = false;
	      });
	    },
	    viewActivity: function viewActivity() {
	      this.self.view();
	    },
	    capitalizeFirstLetter: function capitalizeFirstLetter(str) {
	      var locale = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : navigator.language;
	      return str.replace(/^(?:[a-z\xB5\xDF-\xF6\xF8-\xFF\u0101\u0103\u0105\u0107\u0109\u010B\u010D\u010F\u0111\u0113\u0115\u0117\u0119\u011B\u011D\u011F\u0121\u0123\u0125\u0127\u0129\u012B\u012D\u012F\u0131\u0133\u0135\u0137\u013A\u013C\u013E\u0140\u0142\u0144\u0146\u0148\u0149\u014B\u014D\u014F\u0151\u0153\u0155\u0157\u0159\u015B\u015D\u015F\u0161\u0163\u0165\u0167\u0169\u016B\u016D\u016F\u0171\u0173\u0175\u0177\u017A\u017C\u017E-\u0180\u0183\u0185\u0188\u018C\u0192\u0195\u0199\u019A\u019E\u01A1\u01A3\u01A5\u01A8\u01AD\u01B0\u01B4\u01B6\u01B9\u01BD\u01BF\u01C5\u01C6\u01C8\u01C9\u01CB\u01CC\u01CE\u01D0\u01D2\u01D4\u01D6\u01D8\u01DA\u01DC\u01DD\u01DF\u01E1\u01E3\u01E5\u01E7\u01E9\u01EB\u01ED\u01EF\u01F0\u01F2\u01F3\u01F5\u01F9\u01FB\u01FD\u01FF\u0201\u0203\u0205\u0207\u0209\u020B\u020D\u020F\u0211\u0213\u0215\u0217\u0219\u021B\u021D\u021F\u0223\u0225\u0227\u0229\u022B\u022D\u022F\u0231\u0233\u023C\u023F\u0240\u0242\u0247\u0249\u024B\u024D\u024F-\u0254\u0256\u0257\u0259\u025B\u025C\u0260\u0261\u0263\u0265\u0266\u0268-\u026C\u026F\u0271\u0272\u0275\u027D\u0280\u0282\u0283\u0287-\u028C\u0292\u029D\u029E\u0345\u0371\u0373\u0377\u037B-\u037D\u0390\u03AC-\u03CE\u03D0\u03D1\u03D5-\u03D7\u03D9\u03DB\u03DD\u03DF\u03E1\u03E3\u03E5\u03E7\u03E9\u03EB\u03ED\u03EF-\u03F3\u03F5\u03F8\u03FB\u0430-\u045F\u0461\u0463\u0465\u0467\u0469\u046B\u046D\u046F\u0471\u0473\u0475\u0477\u0479\u047B\u047D\u047F\u0481\u048B\u048D\u048F\u0491\u0493\u0495\u0497\u0499\u049B\u049D\u049F\u04A1\u04A3\u04A5\u04A7\u04A9\u04AB\u04AD\u04AF\u04B1\u04B3\u04B5\u04B7\u04B9\u04BB\u04BD\u04BF\u04C2\u04C4\u04C6\u04C8\u04CA\u04CC\u04CE\u04CF\u04D1\u04D3\u04D5\u04D7\u04D9\u04DB\u04DD\u04DF\u04E1\u04E3\u04E5\u04E7\u04E9\u04EB\u04ED\u04EF\u04F1\u04F3\u04F5\u04F7\u04F9\u04FB\u04FD\u04FF\u0501\u0503\u0505\u0507\u0509\u050B\u050D\u050F\u0511\u0513\u0515\u0517\u0519\u051B\u051D\u051F\u0521\u0523\u0525\u0527\u0529\u052B\u052D\u052F\u0561-\u0587\u10D0-\u10FA\u10FD-\u10FF\u13F8-\u13FD\u1C80-\u1C88\u1D79\u1D7D\u1D8E\u1E01\u1E03\u1E05\u1E07\u1E09\u1E0B\u1E0D\u1E0F\u1E11\u1E13\u1E15\u1E17\u1E19\u1E1B\u1E1D\u1E1F\u1E21\u1E23\u1E25\u1E27\u1E29\u1E2B\u1E2D\u1E2F\u1E31\u1E33\u1E35\u1E37\u1E39\u1E3B\u1E3D\u1E3F\u1E41\u1E43\u1E45\u1E47\u1E49\u1E4B\u1E4D\u1E4F\u1E51\u1E53\u1E55\u1E57\u1E59\u1E5B\u1E5D\u1E5F\u1E61\u1E63\u1E65\u1E67\u1E69\u1E6B\u1E6D\u1E6F\u1E71\u1E73\u1E75\u1E77\u1E79\u1E7B\u1E7D\u1E7F\u1E81\u1E83\u1E85\u1E87\u1E89\u1E8B\u1E8D\u1E8F\u1E91\u1E93\u1E95-\u1E9B\u1EA1\u1EA3\u1EA5\u1EA7\u1EA9\u1EAB\u1EAD\u1EAF\u1EB1\u1EB3\u1EB5\u1EB7\u1EB9\u1EBB\u1EBD\u1EBF\u1EC1\u1EC3\u1EC5\u1EC7\u1EC9\u1ECB\u1ECD\u1ECF\u1ED1\u1ED3\u1ED5\u1ED7\u1ED9\u1EDB\u1EDD\u1EDF\u1EE1\u1EE3\u1EE5\u1EE7\u1EE9\u1EEB\u1EED\u1EEF\u1EF1\u1EF3\u1EF5\u1EF7\u1EF9\u1EFB\u1EFD\u1EFF-\u1F07\u1F10-\u1F15\u1F20-\u1F27\u1F30-\u1F37\u1F40-\u1F45\u1F50-\u1F57\u1F60-\u1F67\u1F70-\u1F7D\u1F80-\u1FB4\u1FB6\u1FB7\u1FBC\u1FBE\u1FC2-\u1FC4\u1FC6\u1FC7\u1FCC\u1FD0-\u1FD3\u1FD6\u1FD7\u1FE0-\u1FE7\u1FF2-\u1FF4\u1FF6\u1FF7\u1FFC\u214E\u2170-\u217F\u2184\u24D0-\u24E9\u2C30-\u2C5F\u2C61\u2C65\u2C66\u2C68\u2C6A\u2C6C\u2C73\u2C76\u2C81\u2C83\u2C85\u2C87\u2C89\u2C8B\u2C8D\u2C8F\u2C91\u2C93\u2C95\u2C97\u2C99\u2C9B\u2C9D\u2C9F\u2CA1\u2CA3\u2CA5\u2CA7\u2CA9\u2CAB\u2CAD\u2CAF\u2CB1\u2CB3\u2CB5\u2CB7\u2CB9\u2CBB\u2CBD\u2CBF\u2CC1\u2CC3\u2CC5\u2CC7\u2CC9\u2CCB\u2CCD\u2CCF\u2CD1\u2CD3\u2CD5\u2CD7\u2CD9\u2CDB\u2CDD\u2CDF\u2CE1\u2CE3\u2CEC\u2CEE\u2CF3\u2D00-\u2D25\u2D27\u2D2D\uA641\uA643\uA645\uA647\uA649\uA64B\uA64D\uA64F\uA651\uA653\uA655\uA657\uA659\uA65B\uA65D\uA65F\uA661\uA663\uA665\uA667\uA669\uA66B\uA66D\uA681\uA683\uA685\uA687\uA689\uA68B\uA68D\uA68F\uA691\uA693\uA695\uA697\uA699\uA69B\uA723\uA725\uA727\uA729\uA72B\uA72D\uA72F\uA733\uA735\uA737\uA739\uA73B\uA73D\uA73F\uA741\uA743\uA745\uA747\uA749\uA74B\uA74D\uA74F\uA751\uA753\uA755\uA757\uA759\uA75B\uA75D\uA75F\uA761\uA763\uA765\uA767\uA769\uA76B\uA76D\uA76F\uA77A\uA77C\uA77F\uA781\uA783\uA785\uA787\uA78C\uA791\uA793\uA794\uA797\uA799\uA79B\uA79D\uA79F\uA7A1\uA7A3\uA7A5\uA7A7\uA7A9\uA7B5\uA7B7\uA7B9\uA7BB\uA7BD\uA7BF\uA7C1\uA7C3\uA7C8\uA7CA\uA7D1\uA7D7\uA7D9\uA7F6\uAB53\uAB70-\uABBF\uFB00-\uFB06\uFB13-\uFB17\uFF41-\uFF5A]|\uD801[\uDC28-\uDC4F\uDCD8-\uDCFB\uDD97-\uDDA1\uDDA3-\uDDB1\uDDB3-\uDDB9\uDDBB\uDDBC]|\uD803[\uDCC0-\uDCF2]|\uD806[\uDCC0-\uDCDF]|\uD81B[\uDE60-\uDE7F]|\uD83A[\uDD22-\uDD43])/, function (_char) {
	        return _char.toLocaleUpperCase(locale);
	      });
	    }
	  },
	  computed: {
	    communication: function communication() {
	      return this.entityData['COMMUNICATION'] ? this.entityData['COMMUNICATION'] : null;
	    },
	    statusClass: function statusClass() {
	      return {
	        'crm-entity-stream-content-event-process': this.status.semantics === 'process',
	        'crm-entity-stream-content-event-successful': this.status.semantics === 'success',
	        'crm-entity-stream-content-event-missing': this.status.semantics === 'failure',
	        'crm-entity-stream-content-event-error-tip': this.isStatusError
	      };
	    },
	    isStatusError: function isStatusError() {
	      return this.status.semantics === 'failure' && !!this.status.description;
	    },
	    statusErrorDescription: function statusErrorDescription() {
	      return this.isStatusError ? this.status.description : '';
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-sms\">\n\t\t\t<div class=\"crm-entity-stream-section-icon crm-entity-stream-section-icon-sms\"></div>\n\t\t\t<div class=\"crm-entity-stream-section-content\">\n\t\t\t\t<div class=\"crm-entity-stream-content-event\">\n\t\t\t\t\t<div class=\"crm-entity-stream-content-header\">\n\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t@click.prevent=\"viewActivity\"\n\t\t\t\t\t\t\thref=\"#\"\n\t\t\t\t\t\t\tclass=\"crm-entity-stream-content-event-title\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{title}}\n\t\t\t\t\t\t</a>\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tv-if=\"status\"\n\t\t\t\t\t\t\t:class=\"statusClass\"\n\t\t\t\t\t\t\t:title=\"statusErrorDescription\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{status.name}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-time\">{{createdAt}}</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-entity-stream-content-detail\">\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-detail-sms\">\n\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-detail-sms-status\">\n\t\t\t\t\t\t\t\t".concat(main_core.Loc.getMessage('CRM_TIMELINE_NOTIFICATION_VIA'), " \n\t\t\t\t\t\t\t\t<strong>\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('CRM_TIMELINE_NOTIFICATION_BITRIX24'), "\n\t\t\t\t\t\t\t\t</strong>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-detail-sms-fragment\">\n\t\t\t\t\t\t\t\t<span>{{text}}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tv-if=\"communication\"\n\t\t\t\t\t\t\tclass=\"crm-entity-stream-content-detail-contact-info\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{BX.message('CRM_TIMELINE_SMS_TO')}}\n\t\t\t\t\t\t\t<a v-if=\"communication.SHOW_URL\" :href=\"communication.SHOW_URL\">\n\t\t\t\t\t\t\t\t{{communication.TITLE}}\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t{{communication.TITLE}}\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<span v-if=\"communication.VALUE\">{{communication.VALUE}}</span>\n\t\t\t\t\t\t\t<template v-if=\"provider\">\n\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('CRM_TIMELINE_NOTIFICATION_IN_MESSENGER'), " {{provider}}\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<author v-if=\"author\" :author=\"author\"></author>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\t\n\t")
	});

	var DeliveryServiceInfo = {
	  props: {
	    deliveryService: {
	      required: true,
	      type: Object
	    }
	  },
	  computed: {
	    isDeliveryServiceProfile: function isDeliveryServiceProfile() {
	      return this.deliveryService.IS_PROFILE;
	    },
	    deliveryServiceName: function deliveryServiceName() {
	      return this.isDeliveryServiceProfile ? this.deliveryService.PARENT_NAME : this.deliveryService.NAME;
	    },
	    deliveryProfileServiceName: function deliveryProfileServiceName() {
	      return this.deliveryService.NAME;
	    },
	    deliveryServiceLogoBackgroundUrl: function deliveryServiceLogoBackgroundUrl() {
	      return this.isDeliveryServiceProfile ? this.deliveryService.PARENT_LOGO : this.deliveryService.LOGO;
	      return logo ? {
	        'background-image': 'url(' + logo + ')'
	      } : {};
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-content-delivery-title\">\n\t\t\t<div\n\t\t\t\tv-if=\"isDeliveryServiceProfile && deliveryService.LOGO\"\n\t\t\t\tclass=\"crm-entity-stream-content-delivery-icon\"\n\t\t\t\t:style=\"{'background-image': 'url(' + deliveryService.LOGO + ')'}\"\n\t\t\t>\n\t\t\t</div>\n\t\t\t<div class=\"crm-entity-stream-content-delivery-title-contnet\">\n\t\t\t\t<div\n\t\t\t\t\tv-if=\"deliveryServiceLogoBackgroundUrl\"\n\t\t\t\t\tclass=\"crm-entity-stream-content-delivery-title-logo\"\n\t\t\t\t\t:style=\"{'background-image': 'url(' + deliveryServiceLogoBackgroundUrl + ')'}\"\n\t\t\t\t></div>\n\t\t\t\t<div class=\"crm-entity-stream-content-delivery-title-info\">\n\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-title-name\">\n\t\t\t\t\t\t{{deliveryServiceName}}\n\t\t\t\t\t</div>\n\t\t\t\t\t<div\n\t\t\t\t\t\tv-if=\"isDeliveryServiceProfile\"\n\t\t\t\t\t\tclass=\"crm-entity-stream-content-delivery-title-param\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{deliveryProfileServiceName}}\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var component$2 = ui_vue.Vue.extend({
	  mixins: [HistoryItemMixin],
	  components: {
	    'author': Author,
	    'delivery-service-info': DeliveryServiceInfo
	  },
	  props: {
	    mode: {
	      required: true,
	      type: String
	    }
	  },
	  data: function data() {
	    return {
	      entityData: null,
	      deliveryInfo: null,
	      isRefreshing: false,
	      isCreatingRequest: false,
	      isCancellingRequest: false
	    };
	  },
	  methods: {
	    // region common activity methods
	    completeActivity: function completeActivity() {
	      if (this.self.canComplete()) {
	        this.self.setAsDone(!this.self.isDone());
	      }
	    },
	    showContextMenu: function showContextMenu(event) {
	      var _this = this;

	      var popup = BX.PopupMenu.create('taxi_activity_context_menu_' + this.self.getId(), event.target, [{
	        id: 'delete',
	        text: this.getLangMessage('menuDelete'),
	        onclick: function onclick() {
	          popup.close();
	          var deletionDlgId = 'entity_timeline_deletion_' + _this.self.getId() + '_confirm';
	          var dlg = BX.Crm.ConfirmationDialog.get(deletionDlgId);

	          if (!dlg) {
	            dlg = BX.Crm.ConfirmationDialog.create(deletionDlgId, {
	              title: _this.getLangMessage('removeConfirmTitle'),
	              content: _this.getLangMessage('deliveryRemove')
	            });
	          }

	          dlg.open().then(function (result) {
	            if (result.cancel) {
	              return;
	            }

	            _this.self.remove();
	          }, function (result) {});
	        }
	      }], {
	        autoHide: true,
	        offsetTop: 0,
	        offsetLeft: 16,
	        angle: {
	          position: "top",
	          offset: 0
	        },
	        events: {
	          onPopupShow: function onPopupShow() {
	            return BX.addClass(event.target, 'active');
	          },
	          onPopupClose: function onPopupClose() {
	            return BX.removeClass(event.target, 'active');
	          }
	        }
	      });
	      popup.show();
	    },
	    // endregion
	    // region delivery request methods
	    createDeliveryRequest: function createDeliveryRequest() {
	      var _this2 = this;

	      if (this.isLocked) {
	        return;
	      }

	      this.isCreatingRequest = true;
	      BX.ajax.runAction('sale.deliveryrequest.create', {
	        analyticsLabel: 'saleDeliveryTaxiCall',
	        data: {
	          shipmentIds: this.shipmentIds,
	          additional: {
	            ACTIVITY_ID: this.activityId
	          }
	        }
	      }).then(function (result) {
	        _this2.refresh(function () {
	          _this2.isCreatingRequest = false;
	        });
	      })["catch"](function (result) {
	        _this2.isCreatingRequest = false;

	        _this2.showError(result.errors.map(function (item) {
	          return item.message;
	        }).join());
	      });
	    },
	    cancelDeliveryRequest: function cancelDeliveryRequest() {
	      var _this3 = this;

	      if (this.isLocked || !this.deliveryRequest) {
	        return;
	      }

	      this.isCancellingRequest = true;
	      BX.ajax.runAction('sale.deliveryrequest.execute', {
	        data: {
	          requestId: this.deliveryRequest['ID'],
	          actionType: this.deliveryService['CANCEL_ACTION_CODE']
	        }
	      }).then(function (result) {
	        var data = result.data;
	        BX.ajax.runAction('crm.timeline.deliveryactivity.createcanceldeliveryrequestmessage', {
	          data: {
	            requestId: _this3.deliveryRequest['ID'],
	            message: data.message
	          }
	        }).then(function (result) {
	          BX.ajax.runAction('sale.deliveryrequest.delete', {
	            data: {
	              requestId: _this3.deliveryRequest['ID']
	            }
	          }).then(function (result) {
	            _this3.refresh(function () {
	              _this3.isCancellingRequest = false;
	            });
	          })["catch"](function (result) {
	            _this3.isCancellingRequest = false;

	            _this3.showError(result.errors.map(function (item) {
	              return item.message;
	            }).join());
	          });
	        });
	      })["catch"](function (result) {
	        _this3.isCancellingRequest = false;

	        _this3.showError(result.errors.map(function (item) {
	          return item.message;
	        }).join());
	      });
	    },
	    checkRequestStatus: function checkRequestStatus() {
	      BX.ajax.runAction('crm.timeline.deliveryactivity.checkrequeststatus');
	    },
	    startCheckingRequestStatus: function startCheckingRequestStatus() {
	      var _this4 = this;

	      clearTimeout(this._checkRequestStatusTimeoutId);
	      this._checkRequestStatusTimeoutId = setInterval(function () {
	        return _this4.checkRequestStatus();
	      }, 30 * 1000);
	    },
	    stopCheckingRequestStatus: function stopCheckingRequestStatus() {
	      clearTimeout(this._checkRequestStatusTimeoutId);
	    },
	    // endregion
	    // region refresh methods
	    setDeliveryInfo: function setDeliveryInfo(deliveryInfo) {
	      this.deliveryInfo = deliveryInfo;
	    },
	    refresh: function refresh() {
	      var _this5 = this;

	      var callback = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

	      if (this.isRefreshing) {
	        return;
	      }

	      this.isRefreshing = true;

	      var finallyCallback = function finallyCallback() {
	        _this5.isRefreshing = false;

	        if (callback) {
	          callback();
	        }
	      };

	      main_core.ajax.runAction('crm.timeline.deliveryactivity.getdeliveryinfo', {
	        data: {
	          activityId: this.activityId
	        }
	      }).then(function (result) {
	        _this5.setDeliveryInfo(result.data);

	        finallyCallback();
	      })["catch"](function (result) {
	        finallyCallback();
	      });
	    },
	    subscribePullEvents: function subscribePullEvents() {
	      var _this6 = this;

	      if (this._isPullSubscribed) {
	        return;
	      }

	      pull_client.PULL.subscribe({
	        moduleId: 'crm',
	        command: 'onOrderShipmentSave',
	        callback: function callback(params) {
	          if (_this6.shipmentIds.some(function (id) {
	            return id == params.FIELDS.ID;
	          })) {
	            _this6.refresh();
	          }
	        }
	      });
	      pull_client.PULL.subscribe({
	        moduleId: 'sale',
	        command: 'onDeliveryServiceSave',
	        callback: function callback(params) {
	          if (_this6.deliveryServiceIds.some(function (id) {
	            return id == params.ID;
	          })) {
	            _this6.refresh();
	          }
	        }
	      });
	      pull_client.PULL.subscribe({
	        moduleId: 'sale',
	        command: 'onDeliveryRequestUpdate',
	        callback: function callback(params) {
	          if (_this6.deliveryRequestId == params.ID) {
	            _this6.refresh();
	          }
	        }
	      });
	      pull_client.PULL.subscribe({
	        moduleId: 'sale',
	        command: 'onDeliveryRequestDelete',
	        callback: function callback(params) {
	          if (_this6.deliveryRequestId == params.ID) {
	            _this6.refresh();
	          }
	        }
	      });
	      pull_client.PULL.extendWatch('SALE_DELIVERY_SERVICE');
	      pull_client.PULL.extendWatch('CRM_ENTITY_ORDER_SHIPMENT');
	      pull_client.PULL.extendWatch('SALE_DELIVERY_REQUEST');
	      this._isPullSubscribed = true;
	    },
	    //endregion
	    // region miscellaneous
	    callPhone: function callPhone(phone) {
	      if (this.canUseTelephony && typeof top.BXIM !== 'undefined') {
	        top.BXIM.phoneTo(phone);
	      } else {
	        window.location.href = 'tel:' + phone;
	      }
	    },
	    isPhone: function isPhone(property) {
	      return property.hasOwnProperty('TAGS') && Array.isArray(property['TAGS']) && property['TAGS'].includes('phone');
	    },
	    showError: function showError(message) {
	      BX.loadExt('ui.notification').then(function () {
	        BX.UI.Notification.Center.notify({
	          content: message
	        });
	      });
	    } // endregion

	  },
	  created: function created() {
	    this.entityData = this.self.getAssociatedEntityData();

	    if (this.entityData['DELIVERY_INFO']) {
	      this.setDeliveryInfo(this.entityData['DELIVERY_INFO']);
	    }

	    this.subscribePullEvents();
	    this._checkRequestStatusTimeoutId = null;

	    if (this.needCheckRequestStatus) {
	      this.startCheckingRequestStatus();
	    }
	  },
	  computed: {
	    activityId: function activityId() {
	      return this.data.ASSOCIATED_ENTITY.ID;
	    },
	    // region shipments
	    shipments: function shipments() {
	      if (this.deliveryInfo && this.deliveryInfo.hasOwnProperty('SHIPMENTS') && Array.isArray(this.deliveryInfo['SHIPMENTS'])) {
	        return this.deliveryInfo['SHIPMENTS'];
	      }

	      return null;
	    },
	    shipmentIds: function shipmentIds() {
	      return this.shipments ? this.shipments.map(function (shipment) {
	        return shipment['ID'];
	      }) : [];
	    },
	    shipment: function shipment() {
	      if (this.shipments && Array.isArray(this.shipments) && this.shipments.length > 0) {
	        return this.shipments[0];
	      }

	      return null;
	    },
	    expectedDeliveryPriceFormatted: function expectedDeliveryPriceFormatted() {
	      return this.shipment && this.shipment.hasOwnProperty('BASE_PRICE_DELIVERY') ? this.shipment['BASE_PRICE_DELIVERY_FORMATTED'] : this.shipment['PRICE_DELIVERY_FORMATTED'];
	    },
	    // endregion
	    // region delivery service
	    deliveryService: function deliveryService() {
	      if (this.deliveryInfo && this.deliveryInfo.hasOwnProperty('DELIVERY_SERVICE') && babelHelpers["typeof"](this.deliveryInfo['DELIVERY_SERVICE']) === 'object' && this.deliveryInfo['DELIVERY_SERVICE'] !== null) {
	        return this.deliveryInfo['DELIVERY_SERVICE'];
	      }

	      return null;
	    },
	    deliveryServiceIds: function deliveryServiceIds() {
	      // @TODO
	      if (!this.deliveryService) {
	        return null;
	      }

	      return this.deliveryService.IDS;
	    },
	    // endregion
	    // region delivery request
	    deliveryRequest: function deliveryRequest() {
	      if (this.deliveryInfo && this.deliveryInfo.hasOwnProperty('DELIVERY_REQUEST') && babelHelpers["typeof"](this.deliveryInfo['DELIVERY_REQUEST']) === 'object' && this.deliveryInfo['DELIVERY_REQUEST'] !== null) {
	        return this.deliveryInfo['DELIVERY_REQUEST'];
	      }

	      return null;
	    },
	    deliveryRequestId: function deliveryRequestId() {
	      if (this.deliveryRequest && this.deliveryRequest.hasOwnProperty('ID')) {
	        return this.deliveryRequest['ID'];
	      }

	      return null;
	    },
	    deliveryRequestProperties: function deliveryRequestProperties() {
	      if (this.deliveryRequest && this.deliveryRequest.hasOwnProperty('EXTERNAL_PROPERTIES') && babelHelpers["typeof"](this.deliveryRequest['EXTERNAL_PROPERTIES']) === 'object' && this.deliveryRequest['EXTERNAL_PROPERTIES'] !== null) {
	        return this.deliveryRequest['EXTERNAL_PROPERTIES'];
	      }

	      return null;
	    },
	    deliveryRequestStatus: function deliveryRequestStatus() {
	      if (!this.deliveryRequest) {
	        return null;
	      }

	      return this.deliveryRequest['EXTERNAL_STATUS'];
	    },
	    deliveryRequestStatusSemantic: function deliveryRequestStatusSemantic() {
	      if (!this.deliveryRequest) {
	        return null;
	      }

	      return this.deliveryRequest['EXTERNAL_STATUS_SEMANTIC'];
	    },
	    isConnectedWithDeliveryRequest: function isConnectedWithDeliveryRequest() {
	      return !!this.deliveryRequest;
	    },
	    needCheckRequestStatus: function needCheckRequestStatus() {
	      return this.isConnectedWithDeliveryRequest && this.mode === 'schedule';
	    },
	    isSendRequestButtonVisible: function isSendRequestButtonVisible() {
	      return !this.isCreatingRequest && !this.isConnectedWithDeliveryRequest;
	    },
	    // endregion
	    //region miscellaneous
	    miscellaneous: function miscellaneous() {
	      if (this.deliveryInfo && this.deliveryInfo.hasOwnProperty('MISCELLANEOUS')) {
	        return this.deliveryInfo['MISCELLANEOUS'];
	      }

	      return null;
	    },
	    canUseTelephony: function canUseTelephony() {
	      return this.miscellaneous && this.miscellaneous.hasOwnProperty('CAN_USE_TELEPHONY') && this.miscellaneous['CAN_USE_TELEPHONY'];
	    },
	    template: function template() {
	      if (!this.miscellaneous || !this.miscellaneous.hasOwnProperty('TEMPLATE')) {
	        return null;
	      }

	      return this.miscellaneous['TEMPLATE'];
	    },
	    // endregion
	    // region classes
	    cancelRequestButtonStyle: function cancelRequestButtonStyle() {
	      return {
	        'ui-btn': true,
	        'ui-btn-sm': true,
	        'ui-btn-light-border': true,
	        'ui-btn-wait': this.isCancellingRequest
	      };
	    },
	    statusClass: function statusClass() {
	      return {
	        'crm-entity-stream-content-event-process': this.deliveryRequestStatusSemantic === 'process',
	        'crm-entity-stream-content-event-missing': this.deliveryRequestStatusSemantic === 'error',
	        'crm-entity-stream-content-event-done': this.deliveryRequestStatusSemantic === 'success'
	      };
	    },
	    wrapperContainerClass: function wrapperContainerClass() {
	      return {
	        'crm-entity-stream-section-planned': this.mode === 'schedule'
	      };
	    },
	    innerWrapperContainerClass: function innerWrapperContainerClass() {
	      return {
	        'crm-entity-stream-content-event--delivery': this.mode !== 'schedule'
	      };
	    },
	    // endregion
	    isLocked: function isLocked() {
	      return this.isRefreshing || this.isCreatingRequest || this.isCancellingRequest;
	    }
	  },
	  watch: {
	    needCheckRequestStatus: function needCheckRequestStatus(value) {
	      if (value) {
	        this.startCheckingRequestStatus();
	      } else {
	        this.stopCheckingRequestStatus();
	      }
	    }
	  },
	  template: "\n\t\t<div\n\t\t\tclass=\"crm-entity-stream-section crm-entity-stream-section-new\"\n\t\t\t:class=\"wrapperContainerClass\"\n\t\t>\n\t\t\t<div class=\"crm-entity-stream-section-icon crm-entity-stream-section-icon-new crm-entity-stream-section-icon-taxi\"></div>\n\t\t\t<div\n\t\t\t\tv-if=\"mode === 'schedule'\"\n\t\t\t\t@click=\"showContextMenu\"\n\t\t\t\tclass=\"crm-entity-stream-section-context-menu\"\n\t\t\t></div>\n\t\t\t<div class=\"crm-entity-stream-section-content\">\n\t\t\t\t<div\n\t\t\t\t\tclass=\"crm-entity-stream-content-event\"\n\t\t\t\t\t:class=\"innerWrapperContainerClass\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"crm-entity-stream-content-header\">\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-title\">\n\t\t\t\t\t\t\t".concat(main_core.Loc.getMessage('TIMELINE_DELIVERY_TAXI_SERVICE'), "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tv-if=\"deliveryRequestStatus && deliveryRequestStatusSemantic\"\n\t\t\t\t\t\t\t:class=\"statusClass\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{deliveryRequestStatus}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-time\">{{createdAt}}</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-entity-stream-content-detail crm-entity-stream-content-delivery\">\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-row crm-entity-stream-content-delivery-row--flex\">\n\t\t\t\t\t\t\t<template v-if=\"mode === 'schedule'\">\n\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\tv-if=\"isSendRequestButtonVisible\"\n\t\t\t\t\t\t\t\t\t@click=\"createDeliveryRequest\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-sm ui-btn-primary\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('TIMELINE_DELIVERY_CREATE_DELIVERY_REQUEST'), "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<span v-if=\"isCreatingRequest\" class=\"crm-entity-stream-content-delivery-status\">\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('TIMELINE_DELIVERY_CREATING_REQUEST'), "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\tv-if=\"isConnectedWithDeliveryRequest && deliveryService && deliveryService.IS_CANCELLABLE\"\n\t\t\t\t\t\t\t\t\t@click=\"cancelDeliveryRequest\"\n\t\t\t\t\t\t\t\t\t:class=\"cancelRequestButtonStyle\"\n\t\t\t\t\t\t\t\t>\t\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t\t\t{{deliveryService.CANCEL_ACTION_NAME}}\n\t\t\t\t\t\t\t\t</span>\t\t\t\t\t\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<delivery-service-info\n\t\t\t\t\t\t\t\tv-if=\"deliveryService\"\n\t\t\t\t\t\t\t\t:deliveryService=\"deliveryService\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t</delivery-service-info>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-row\">\n\t\t\t\t\t\t\t<table class=\"crm-entity-stream-content-delivery-order\">\n\t\t\t\t\t\t\t\t<tr v-if=\"shipment && shipment.ADDRESS_FROM_FORMATTED && shipment.ADDRESS_TO_FORMATTED\">\n\t\t\t\t\t\t\t\t\t<td colspan=\"2\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-item\">\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm\">\n\t\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-box\">\n\t\t\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-box-label\">\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('TIMELINE_DELIVERY_TAXI_ADDRESS_FROM'), "\n\t\t\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<span v-html=\"shipment.ADDRESS_FROM_FORMATTED\">\n\t\t\t\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-box\">\n\t\t\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-box-label\">\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('TIMELINE_DELIVERY_TAXI_ADDRESS_TO'), "\n\t\t\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<span v-html=\"shipment.ADDRESS_TO_FORMATTED\">\n\t\t\t\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr v-if=\"shipment\">\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-item\">\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-label\">\n\t\t\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('TIMELINE_DELIVERY_TAXI_CLIENT_DELIVERY_PRICE'), "\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm\">\n\t\t\t\t\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\t\t\t\t\tv-html=\"shipment.PRICE_DELIVERY_FORMATTED\"\n\t\t\t\t\t\t\t\t\t\t\t\t\t style=\"font-size: 14px; color: #333;\"\n\t\t\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-item\">\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-label\">\n\t\t\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('TIMELINE_DELIVERY_TAXI_EXPECTED_DELIVERY_PRICE'), "\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm\">\n\t\t\t\t\t\t\t\t\t\t\t\t<span style=\"font-size: 14px; color: #333; opacity: .5;\">\n\t\t\t\t\t\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\t\t\t\t\t\tv-html=\"expectedDeliveryPriceFormatted\"\n\t\t\t\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t\t\t\t<span v-else>\n\t\t\t\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('TIMELINE_DELIVERY_TAXI_EXPECTED_PRICE_NOT_RECEIVED'), "\n\t\t\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<!-- Properties --->\n\t\t\t\t\t\t\t\t<tr v-for=\"property in deliveryRequestProperties\">\n\t\t\t\t\t\t\t\t\t<td colspan=\"2\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-item\">\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-label\">\n\t\t\t\t\t\t\t\t\t\t\t\t{{property.NAME}}\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm\">\n\t\t\t\t\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\t\t\t\t\tv-if=\"isPhone(property)\"\n\t\t\t\t\t\t\t\t\t\t\t\t\t@click=\"callPhone(property.VALUE)\"\n\t\t\t\t\t\t\t\t\t\t\t\t\tclass=\"crm-entity-stream-content-delivery-link\"\n\t\t\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t\t\t\t{{property.VALUE}}\n\t\t\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t\t\t\t<span v-else>\n\t\t\t\t\t\t\t\t\t\t\t\t\t{{property.VALUE}}\n\t\t\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<!-- end Properties --->\n\t\t\t\t\t\t\t</table>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div v-if=\"mode === 'schedule'\" class=\"crm-entity-stream-content-detail-planned-action\">\n\t\t\t\t\t\t<input @click=\"completeActivity\" type=\"checkbox\" class=\"crm-entity-stream-planned-apply-btn\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<author v-if=\"author\" :author=\"author\">\n\t\t\t\t\t</author>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t")
	});

	var component$3 = ui_vue.Vue.extend({
	  mixins: [HistoryItemMixin],
	  components: {
	    'author': Author,
	    'delivery-service-info': DeliveryServiceInfo
	  },
	  computed: {
	    deliveryService: function deliveryService() {
	      if (!this.data.FIELDS.hasOwnProperty('DELIVERY_SERVICE')) {
	        return null;
	      }

	      return this.data.FIELDS.DELIVERY_SERVICE;
	    },
	    messageData: function messageData() {
	      if (!this.data.FIELDS.hasOwnProperty('MESSAGE_DATA')) {
	        return null;
	      }

	      return this.data.FIELDS.MESSAGE_DATA;
	    },
	    messageTitle: function messageTitle() {
	      if (!this.messageData) {
	        return null;
	      }

	      return this.messageData['TITLE'];
	    },
	    messageDescription: function messageDescription() {
	      if (!this.messageData) {
	        return null;
	      }

	      return this.messageData['DESCRIPTION'];
	    },
	    messageStatus: function messageStatus() {
	      if (!this.messageData) {
	        return null;
	      }

	      return this.messageData['STATUS'];
	    },
	    messageStatusSemantics: function messageStatusSemantics() {
	      if (!this.messageData) {
	        return null;
	      }

	      return this.messageData['STATUS_SEMANTIC'];
	    },
	    messageStatusSemanticsClass: function messageStatusSemanticsClass() {
	      return {
	        'crm-entity-stream-content-event-process': this.messageStatusSemantics === 'process',
	        'crm-entity-stream-content-event-missing': this.messageStatusSemantics === 'error',
	        'crm-entity-stream-content-event-done': this.messageStatusSemantics === 'success'
	      };
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-section crm-entity-stream-section-new\">\n\t\t\t<div class=\"crm-entity-stream-section-icon crm-entity-stream-section-icon-new crm-entity-stream-section-icon-taxi\"></div>\n\t\t\t<div class=\"crm-entity-stream-section-content\">\n\t\t\t\t<div class=\"crm-entity-stream-content-event\">\n\t\t\t\t\t<div class=\"crm-entity-stream-content-header\">\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tv-if=\"messageTitle\"\n\t\t\t\t\t\t\tclass=\"crm-entity-stream-content-event-title\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{messageTitle}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tv-if=\"messageStatus && messageStatusSemantics\"\n\t\t\t\t\t\t\t:class=\"messageStatusSemanticsClass\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{messageStatus}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-time\">\n\t\t\t\t\t\t\t<span v-html=\"createdAt\">\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-entity-stream-content-detail\">\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-row crm-entity-stream-content-delivery-row--flex\">\n\t\t\t\t\t\t\t<delivery-service-info\n\t\t\t\t\t\t\t\tv-if=\"deliveryService\"\n\t\t\t\t\t\t\t\t:deliveryService=\"deliveryService\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t</delivery-service-info>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tv-if=\"messageDescription\"\n\t\t\t\t\t\t\tclass=\"crm-entity-stream-content-delivery-description\"\n\t\t\t\t\t\t\tv-html=\"messageDescription\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<author v-if=\"author\" :author=\"author\">\n\t\t\t\t\t</author>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	var component$4 = ui_vue.Vue.extend({
	  mixins: [HistoryItemMixin],
	  components: {
	    'author': Author,
	    'delivery-service-info': DeliveryServiceInfo
	  },
	  computed: {
	    deliveryService: function deliveryService() {
	      if (!this.data.FIELDS.hasOwnProperty('DELIVERY_SERVICE')) {
	        return null;
	      }

	      return this.data.FIELDS.DELIVERY_SERVICE;
	    },
	    messageData: function messageData() {
	      if (!this.data.FIELDS.hasOwnProperty('MESSAGE_DATA')) {
	        return null;
	      }

	      return this.data.FIELDS.MESSAGE_DATA;
	    },
	    messageTitle: function messageTitle() {
	      if (!this.messageData) {
	        return null;
	      }

	      return this.messageData['TITLE'];
	    },
	    messageDescription: function messageDescription() {
	      if (!this.messageData) {
	        return null;
	      }

	      return this.messageData['DESCRIPTION'];
	    },
	    messageStatus: function messageStatus() {
	      if (!this.messageData) {
	        return null;
	      }

	      return this.messageData['STATUS'];
	    },
	    messageStatusSemantics: function messageStatusSemantics() {
	      if (!this.messageData) {
	        return null;
	      }

	      return this.messageData['STATUS_SEMANTIC'];
	    },
	    messageStatusSemanticsClass: function messageStatusSemanticsClass() {
	      return {
	        'crm-entity-stream-content-event-process': this.messageStatusSemantics === 'process',
	        'crm-entity-stream-content-event-missing': this.messageStatusSemantics === 'error',
	        'crm-entity-stream-content-event-done': this.messageStatusSemantics === 'success'
	      };
	    },
	    addressFrom: function addressFrom() {
	      if (!this.data.FIELDS.hasOwnProperty('ADDRESS_FROM_FORMATTED')) {
	        return null;
	      }

	      return this.data.FIELDS.ADDRESS_FROM_FORMATTED;
	    },
	    addressTo: function addressTo() {
	      if (!this.data.FIELDS.hasOwnProperty('ADDRESS_TO_FORMATTED')) {
	        return null;
	      }

	      return this.data.FIELDS.ADDRESS_TO_FORMATTED;
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-section crm-entity-stream-section-new\">\n\t\t\t<div class=\"crm-entity-stream-section-icon crm-entity-stream-section-icon-new crm-entity-stream-section-icon-taxi\"></div>\n\t\t\t\n\t\t\t<div class=\"crm-entity-stream-section-content\">\n\t\t\t\t<div class=\"crm-entity-stream-content-event\">\n\t\t\t\t\t<div class=\"crm-entity-stream-content-header\">\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tv-if=\"messageTitle\"\n\t\t\t\t\t\t\tclass=\"crm-entity-stream-content-event-title\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{messageTitle}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tv-if=\"messageStatus && messageStatusSemantics\"\n\t\t\t\t\t\t\t:class=\"messageStatusSemanticsClass\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{messageStatus}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-time\">\n\t\t\t\t\t\t\t<span v-html=\"createdAt\">\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-entity-stream-content-detail\">\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tv-html=\"messageDescription\"\n\t\t\t\t\t\t\tclass=\"crm-entity-stream-content-detail-description crm-delivery-taxi-caption\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-detail-description\">\n\t\t\t\t\t\t\t<div v-if=\"addressFrom\" class=\"crm-entity-stream-content-delivery-order-box\">\n\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-box-label\">\n\t\t\t\t\t\t\t\t\t".concat(main_core.Loc.getMessage('TIMELINE_DELIVERY_TAXI_ADDRESS_FROM'), "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<span>{{addressFrom}}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div v-if=\"addressTo\" class=\"crm-entity-stream-content-delivery-order-box\">\n\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-box-label\">\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('TIMELINE_DELIVERY_TAXI_ADDRESS_TO'), "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<span>{{addressTo}}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<author v-if=\"author\" :author=\"author\">\n\t\t\t\t\t</author>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t")
	});

	/** @memberof BX.Crm.Timeline */

	var Editor = /*#__PURE__*/function () {
	  function Editor() {
	    babelHelpers.classCallCheck(this, Editor);
	    this._id = "";
	    this._settings = {};
	    this._manager = null;
	    this._ownerTypeId = 0;
	    this._ownerId = 0;
	    this._container = null;
	    this._input = null;
	    this._saveButton = null;
	    this._cancelButton = null;
	    this._ghostInput = null;
	    this._saveButtonHandler = BX.delegate(this.onSaveButtonClick, this);
	    this._cancelButtonHandler = BX.delegate(this.onCancelButtonClick, this);
	    this._focusHandler = BX.delegate(this.onFocus, this);
	    this._blurHandler = BX.delegate(this.onBlur, this);
	    this._keyupHandler = BX.delegate(this.resizeForm, this);
	    this._delayedKeyupHandler = BX.delegate(function () {
	      setTimeout(this.resizeForm.bind(this), 0);
	    }, this);
	    this._isVisible = true;
	    this._hideButtonsOnBlur = true;
	  }

	  babelHelpers.createClass(Editor, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._settings = settings ? settings : {};
	      this._manager = this.getSetting("manager");

	      if (!(this._manager instanceof Manager)) {
	        throw "Editor. Manager instance is not found.";
	      }

	      this._ownerTypeId = this.getSetting("ownerTypeId", 0);
	      this._ownerId = this.getSetting("ownerId", 0);
	      this._container = BX(this.getSetting("container"));
	      this._input = BX(this.getSetting("input"));
	      this._saveButton = BX(this.getSetting("button"));
	      this._cancelButton = BX(this.getSetting("cancelButton"));
	      BX.bind(this._saveButton, "click", this._saveButtonHandler);

	      if (this._cancelButton) {
	        BX.bind(this._cancelButton, "click", this._cancelButtonHandler);
	      }

	      BX.bind(this._input, "focus", this._focusHandler);
	      BX.bind(this._input, "blur", this._blurHandler);
	      BX.bind(this._input, "keyup", this._keyupHandler);
	      BX.bind(this._input, "cut", this._delayedKeyupHandler);
	      BX.bind(this._input, "paste", this._delayedKeyupHandler);
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
	    key: "setVisible",
	    value: function setVisible(visible) {
	      visible = !!visible;

	      if (this._isVisible === visible) {
	        return;
	      }

	      this._isVisible = visible;
	      this._container.style.display = visible ? "" : "none";
	    }
	  }, {
	    key: "isVisible",
	    value: function isVisible() {
	      return this._isVisible;
	    }
	  }, {
	    key: "onFocus",
	    value: function onFocus(e) {
	      BX.addClass(this._container, "focus");
	    }
	  }, {
	    key: "onBlur",
	    value: function onBlur(e) {
	      if (!this._hideButtonsOnBlur) {
	        return;
	      }

	      if (this._input.value === "") {
	        window.setTimeout(BX.delegate(function () {
	          BX.removeClass(this._container, "focus");
	          this._input.style.minHeight = "";
	        }, this), 200);
	      }
	    }
	  }, {
	    key: "onSaveButtonClick",
	    value: function onSaveButtonClick(e) {
	      this.save();
	    }
	  }, {
	    key: "onCancelButtonClick",
	    value: function onCancelButtonClick() {
	      this.cancel();

	      this._manager.processEditingCancellation(this);
	    }
	  }, {
	    key: "save",
	    value: function save() {}
	  }, {
	    key: "cancel",
	    value: function cancel() {}
	  }, {
	    key: "release",
	    value: function release() {
	      if (this._ghostInput) {
	        this._ghostInput = BX.remove(this._ghostInput);
	      }
	    }
	  }, {
	    key: "ensureGhostCreated",
	    value: function ensureGhostCreated() {
	      if (this._ghostInput) {
	        return this._ghostInput;
	      }

	      this._ghostInput = BX.create('div', {
	        props: {
	          className: 'crm-entity-stream-content-new-comment-textarea-shadow'
	        },
	        text: this._input.value
	      });
	      this._ghostInput.style.width = this._input.offsetWidth + 'px';
	      document.body.appendChild(this._ghostInput);
	      return this._ghostInput;
	    }
	  }, {
	    key: "resizeForm",
	    value: function resizeForm() {
	      var ghost = this.ensureGhostCreated();
	      var computedStyle = getComputedStyle(this._input);
	      var diff = parseInt(computedStyle.paddingBottom) + parseInt(computedStyle.paddingTop) + parseInt(computedStyle.borderTopWidth) + parseInt(computedStyle.borderBottomWidth) || 0;
	      ghost.innerHTML = BX.util.htmlspecialchars(this._input.value.replace(/[\r\n]{1}/g, '<br>'));
	      this._input.style.minHeight = ghost.scrollHeight + diff + 'px';
	    }
	  }]);
	  return Editor;
	}();

	/** @memberof BX.Crm.Timeline.Tools */

	var WaitConfigurationDialog = /*#__PURE__*/function () {
	  function WaitConfigurationDialog() {
	    babelHelpers.classCallCheck(this, WaitConfigurationDialog);
	    this._id = "";
	    this._settings = {};
	    this._type = Wait.WaitingType.undefined;
	    this._duration = 0;
	    this._target = "";
	    this._targetDates = null;
	    this._container = null;
	    this._durationMeasureNode = null;
	    this._durationInput = null;
	    this._targetDateNode = null;
	    this._popup = null;
	  }

	  babelHelpers.createClass(WaitConfigurationDialog, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._settings = settings ? settings : {};
	      this._type = BX.prop.getInteger(this._settings, "type", Wait.WaitingType.after);
	      this._duration = BX.prop.getInteger(this._settings, "duration", 1);
	      this._target = BX.prop.getString(this._settings, "target", "");
	      this._targetDates = BX.prop.getArray(this._settings, "targetDates", []);
	      this._menuId = this._id + "_target_date_sel";
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this._id;
	    }
	  }, {
	    key: "getType",
	    value: function getType() {
	      return this._type;
	    }
	  }, {
	    key: "setType",
	    value: function setType(type) {
	      this._type = type;
	    }
	  }, {
	    key: "getDuration",
	    value: function getDuration() {
	      return this._duration;
	    }
	  }, {
	    key: "setDuration",
	    value: function setDuration(duration) {
	      this._duration = duration;
	    }
	  }, {
	    key: "getTarget",
	    value: function getTarget() {
	      return this._target;
	    }
	  }, {
	    key: "setTarget",
	    value: function setTarget(target) {
	      this._target = target;
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = WaitConfigurationDialog.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }, {
	    key: "getDurationText",
	    value: function getDurationText(duration, enableNumber) {
	      return Wait.Helper.getDurationText(duration, enableNumber);
	    }
	  }, {
	    key: "getTargetDateCaption",
	    value: function getTargetDateCaption(name) {
	      var length = this._targetDates.length;

	      for (var i = 0; i < length; i++) {
	        var info = this._targetDates[i];

	        if (info["name"] === name) {
	          return info["caption"];
	        }
	      }

	      return "";
	    }
	  }, {
	    key: "open",
	    value: function open() {
	      this._popup = new BX.PopupWindow(this._id, null, //this._configSelector,
	      {
	        autoHide: true,
	        draggable: false,
	        bindOptions: {
	          forceBindPosition: false
	        },
	        closeByEsc: true,
	        zIndex: 0,
	        content: this.prepareDialogContent(),
	        events: {
	          onPopupShow: BX.delegate(this.onPopupShow, this),
	          onPopupClose: BX.delegate(this.onPopupClose, this),
	          onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
	        },
	        buttons: [new BX.PopupWindowButton({
	          text: this.getMessage("select"),
	          className: "popup-window-button-accept",
	          events: {
	            click: BX.delegate(this.onSaveButtonClick, this)
	          }
	        }), new BX.PopupWindowButtonLink({
	          text: BX.message("JS_CORE_WINDOW_CANCEL"),
	          events: {
	            click: BX.delegate(this.onCancelButtonClick, this)
	          }
	        })]
	      });

	      this._popup.show();
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      if (this._popup) {
	        this._popup.close();
	      }
	    }
	  }, {
	    key: "prepareDialogContent",
	    value: function prepareDialogContent() {
	      var container = BX.create("div", {
	        attrs: {
	          className: "crm-wait-popup-select-block"
	        }
	      });
	      var wrapper = BX.create("div", {
	        attrs: {
	          className: "crm-wait-popup-select-wrapper"
	        }
	      });
	      container.appendChild(wrapper);
	      this._durationInput = BX.create("input", {
	        attrs: {
	          type: "text",
	          className: "crm-wait-popup-settings-input",
	          value: this._duration
	        },
	        events: {
	          keyup: BX.delegate(this.onDurationChange, this)
	        }
	      });
	      this._durationMeasureNode = BX.create("span", {
	        attrs: {
	          className: "crm-wait-popup-settings-title"
	        },
	        text: this.getDurationText(this._duration, false)
	      });

	      if (this._type === Wait.WaitingType.after) {
	        wrapper.appendChild(BX.create("span", {
	          attrs: {
	            className: "crm-wait-popup-settings-title"
	          },
	          text: this.getMessage("prefixTypeAfter")
	        }));
	        wrapper.appendChild(this._durationInput);
	        wrapper.appendChild(this._durationMeasureNode);
	      } else {
	        wrapper.appendChild(BX.create("span", {
	          attrs: {
	            className: "crm-wait-popup-settings-title"
	          },
	          text: this.getMessage("prefixTypeBefore")
	        }));
	        wrapper.appendChild(this._durationInput);
	        wrapper.appendChild(this._durationMeasureNode);
	        wrapper.appendChild(BX.create("span", {
	          attrs: {
	            className: "crm-wait-popup-settings-title"
	          },
	          text: " " + this.getMessage("targetPrefixTypeBefore")
	        }));
	        this._targetDateNode = BX.create("span", {
	          attrs: {
	            className: "crm-automation-popup-settings-link"
	          },
	          text: this.getTargetDateCaption(this._target),
	          events: {
	            click: BX.delegate(this.toggleTargetMenu, this)
	          }
	        });
	        wrapper.appendChild(this._targetDateNode);
	      }

	      return container;
	    }
	  }, {
	    key: "onDurationChange",
	    value: function onDurationChange() {
	      var duration = parseInt(this._durationInput.value);

	      if (isNaN(duration) || duration <= 0) {
	        duration = 1;
	      }

	      this._duration = duration;
	      this._durationMeasureNode.innerHTML = BX.util.htmlspecialchars(this.getDurationText(duration, false));
	    }
	  }, {
	    key: "toggleTargetMenu",
	    value: function toggleTargetMenu() {
	      if (this.isTargetMenuOpened()) {
	        this.closeTargetMenu();
	      } else {
	        this.openTargetMenu();
	      }
	    }
	  }, {
	    key: "isTargetMenuOpened",
	    value: function isTargetMenuOpened() {
	      return !!BX.PopupMenu.getMenuById(this._menuId);
	    }
	  }, {
	    key: "openTargetMenu",
	    value: function openTargetMenu() {
	      var menuItems = [];
	      var i = 0;
	      var length = this._targetDates.length;

	      for (; i < length; i++) {
	        var info = this._targetDates[i];
	        menuItems.push({
	          text: info["caption"],
	          title: info["caption"],
	          value: info["name"],
	          onclick: BX.delegate(this.onTargetSelect, this)
	        });
	      }

	      BX.PopupMenu.show(this._menuId, this._targetDateNode, menuItems, {
	        zIndex: 200,
	        autoHide: true,
	        offsetLeft: BX.pos(this._targetDateNode)["width"] / 2,
	        angle: {
	          position: 'top',
	          offset: 0
	        }
	      });
	    }
	  }, {
	    key: "closeTargetMenu",
	    value: function closeTargetMenu() {
	      BX.PopupMenu.destroy(this._menuId);
	    }
	  }, {
	    key: "onPopupShow",
	    value: function onPopupShow(e, item) {}
	  }, {
	    key: "onPopupClose",
	    value: function onPopupClose() {
	      if (this._popup) {
	        this._popup.destroy();
	      }

	      this.closeTargetMenu();
	    }
	  }, {
	    key: "onPopupDestroy",
	    value: function onPopupDestroy() {
	      if (this._popup) {
	        this._popup = null;
	      }
	    }
	  }, {
	    key: "onSaveButtonClick",
	    value: function onSaveButtonClick(e) {
	      var callback = BX.prop.getFunction(this._settings, "onSave", null);

	      if (!callback) {
	        return;
	      }

	      var params = {
	        type: this._type
	      };
	      params["duration"] = this._duration;
	      params["target"] = this._type === Wait.WaitingType.before ? this._target : "";
	      callback(this, params);
	    }
	  }, {
	    key: "onCancelButtonClick",
	    value: function onCancelButtonClick(e) {
	      var callback = BX.prop.getFunction(this._settings, "onCancel", null);

	      if (callback) {
	        callback(this);
	      }
	    }
	  }, {
	    key: "onTargetSelect",
	    value: function onTargetSelect(e, item) {
	      var fieldName = BX.prop.getString(item, "value", "");

	      if (fieldName !== "") {
	        this._target = fieldName;
	        this._targetDateNode.innerHTML = BX.util.htmlspecialchars(this.getTargetDateCaption(fieldName));
	      }

	      this.closeTargetMenu();
	      e.preventDefault ? e.preventDefault() : e.returnValue = false;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new WaitConfigurationDialog();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return WaitConfigurationDialog;
	}();

	babelHelpers.defineProperty(WaitConfigurationDialog, "messages", {});

	/** @memberof BX.Crm.Timeline.Editors */

	var Wait = /*#__PURE__*/function (_Editor) {
	  babelHelpers.inherits(Wait, _Editor);

	  function Wait() {
	    var _this;

	    babelHelpers.classCallCheck(this, Wait);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Wait).call(this));
	    _this._serviceUrl = "";
	    _this._isRequestRunning = false;
	    _this._isLocked = false;
	    _this._hideButtonsOnBlur = false; //region Config

	    _this._type = Wait.WaitingType.after;
	    _this._duration = 1;
	    _this._target = "";
	    _this._configContainer = null;
	    _this._configSelector = null; //endregion

	    _this._isMenuShown = false;
	    _this._menu = null;
	    _this._configDialog = null;
	    return _this;
	  }

	  babelHelpers.createClass(Wait, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      this._configContainer = BX(this.getSetting("configContainer"));
	      this._serviceUrl = this.getSetting("serviceUrl", "");
	      var config = BX.prop.getObject(this._settings, "config", {});
	      this._type = Wait.WaitingType.resolveTypeId(BX.prop.getString(config, "type", Wait.WaitingType.names.after));
	      this._duration = BX.prop.getInteger(config, "duration", 1);
	      this._target = BX.prop.getString(config, "target", "");
	      this._targetDates = BX.prop.getArray(this._settings, "targetDates", []);
	      this.layoutConfigurationSummary();
	    }
	  }, {
	    key: "getDurationText",
	    value: function getDurationText(duration, enableNumber) {
	      return Wait.Helper.getDurationText(duration, enableNumber);
	    }
	  }, {
	    key: "getTargetDateCaption",
	    value: function getTargetDateCaption(name) {
	      var i = 0;
	      var length = this._targetDates.length;

	      for (; i < length; i++) {
	        var info = this._targetDates[i];

	        if (info["name"] === name) {
	          return info["caption"];
	        }
	      }

	      return "";
	    }
	  }, {
	    key: "onSelectorClick",
	    value: function onSelectorClick(e) {
	      if (!this._isMenuShown) {
	        this.openMenu();
	      } else {
	        this.closeMenu();
	      }

	      e.preventDefault ? e.preventDefault() : e.returnValue = false;
	    }
	  }, {
	    key: "openMenu",
	    value: function openMenu() {
	      if (this._isMenuShown) {
	        return;
	      }

	      var handler = BX.delegate(this.onMenuItemClick, this);
	      var menuItems = [{
	        id: "day_1",
	        text: this.getMessage("oneDay"),
	        onclick: handler
	      }, {
	        id: "day_2",
	        text: this.getMessage("twoDays"),
	        onclick: handler
	      }, {
	        id: "day_3",
	        text: this.getMessage("threeDays"),
	        onclick: handler
	      }, {
	        id: "week_1",
	        text: this.getMessage("oneWeek"),
	        onclick: handler
	      }, {
	        id: "week_2",
	        text: this.getMessage("twoWeek"),
	        onclick: handler
	      }, {
	        id: "week_3",
	        text: this.getMessage("threeWeeks"),
	        onclick: handler
	      }];
	      var customMenu = {
	        id: "custom",
	        text: this.getMessage("custom"),
	        items: []
	      };
	      customMenu["items"].push({
	        id: "afterDays",
	        text: this.getMessage("afterDays"),
	        onclick: handler
	      });

	      if (this._targetDates.length > 0) {
	        customMenu["items"].push({
	          id: "beforeDate",
	          text: this.getMessage("beforeDate"),
	          onclick: handler
	        });
	      }

	      menuItems.push(customMenu);
	      BX.PopupMenu.show(this._id, this._configSelector, menuItems, {
	        offsetTop: 0,
	        offsetLeft: 36,
	        angle: {
	          position: "top",
	          offset: 0
	        },
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
	    key: "onMenuItemClick",
	    value: function onMenuItemClick(e, item) {
	      this.closeMenu();

	      if (item.id === "afterDays" || item.id === "beforeDate") {
	        this.openConfigDialog(item.id === "afterDays" ? Wait.WaitingType.after : Wait.WaitingType.before);
	        return;
	      }

	      var params = {
	        type: Wait.WaitingType.after
	      };

	      if (item.id === "day_1") {
	        params["duration"] = 1;
	      } else if (item.id === "day_2") {
	        params["duration"] = 2;
	      } else if (item.id === "day_3") {
	        params["duration"] = 3;
	      }

	      if (item.id === "week_1") {
	        params["duration"] = 7;
	      } else if (item.id === "week_2") {
	        params["duration"] = 14;
	      } else if (item.id === "week_3") {
	        params["duration"] = 21;
	      }

	      this.saveConfiguration(params);
	    }
	  }, {
	    key: "openConfigDialog",
	    value: function openConfigDialog(type) {
	      if (!this._configDialog) {
	        this._configDialog = WaitConfigurationDialog.create("", {
	          targetDates: this._targetDates,
	          onSave: BX.delegate(this.onConfigDialogSave, this),
	          onCancel: BX.delegate(this.onConfigDialogCancel, this)
	        });
	      }

	      this._configDialog.setType(type);

	      this._configDialog.setDuration(this._duration);

	      var target = this._target;

	      if (target === "" && this._targetDates.length > 0) {
	        target = this._targetDates[0]["name"];
	      }

	      this._configDialog.setTarget(target);

	      this._configDialog.open();
	    }
	  }, {
	    key: "onConfigDialogSave",
	    value: function onConfigDialogSave(sender, params) {
	      this.saveConfiguration(params);

	      this._configDialog.close();
	    }
	  }, {
	    key: "onConfigDialogCancel",
	    value: function onConfigDialogCancel(sender) {
	      this._configDialog.close();
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
	    key: "saveConfiguration",
	    value: function saveConfiguration(params) {
	      //region Parse params
	      this._type = BX.prop.getInteger(params, "type", Wait.WaitingType.after);
	      this._duration = BX.prop.getInteger(params, "duration", 0);

	      if (this._duration <= 0) {
	        this._duration = 1;
	      }

	      this._target = this._type === Wait.WaitingType.before ? BX.prop.getString(params, "target", "") : ""; //endregion
	      //region Save settings

	      var optionName = this._manager.getId().toLowerCase();

	      BX.userOptions.save("crm.timeline.wait", optionName, "type", this._type === Wait.WaitingType.after ? "after" : "before");
	      BX.userOptions.save("crm.timeline.wait", optionName, "duration", this._duration);
	      BX.userOptions.save("crm.timeline.wait", optionName, "target", this._target); //endregion

	      this.layoutConfigurationSummary();
	    }
	  }, {
	    key: "getSummaryHtml",
	    value: function getSummaryHtml() {
	      if (this._type === Wait.WaitingType.before) {
	        return this.getMessage("completionTypeBefore").replace("#DURATION#", this.getDurationText(this._duration, true)).replace("#TARGET_DATE#", this.getTargetDateCaption(this._target));
	      }

	      return this.getMessage("completionTypeAfter").replace("#DURATION#", this.getDurationText(this._duration, true));
	    }
	  }, {
	    key: "getSummaryText",
	    value: function getSummaryText() {
	      return BX.util.strip_tags(this.getSummaryHtml());
	    }
	  }, {
	    key: "layoutConfigurationSummary",
	    value: function layoutConfigurationSummary() {
	      this._configContainer.innerHTML = this.getSummaryHtml();
	      this._configSelector = this._configContainer.querySelector("a");

	      if (this._configSelector) {
	        BX.bind(this._configSelector, "click", BX.delegate(this.onSelectorClick, this));
	      }
	    }
	  }, {
	    key: "postpone",
	    value: function postpone(id, offset, callback) {
	      BX.ajax({
	        url: this._serviceUrl,
	        method: "POST",
	        dataType: "json",
	        data: {
	          "ACTION": "POSTPONE_WAIT",
	          "DATA": {
	            "ID": id,
	            "OFFSET": offset
	          }
	        },
	        onsuccess: callback
	      });
	    }
	  }, {
	    key: "complete",
	    value: function complete(id, completed, callback) {
	      BX.ajax({
	        url: this._serviceUrl,
	        method: "POST",
	        dataType: "json",
	        data: {
	          "ACTION": "COMPLETE_WAIT",
	          "DATA": {
	            "ID": id,
	            "COMPLETED": completed ? 'Y' : 'N'
	          }
	        },
	        onsuccess: callback
	      });
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      if (this._isRequestRunning || this._isLocked) {
	        return;
	      }

	      var description = this.getSummaryText();
	      var comment = BX.util.trim(this._input.value);

	      if (comment !== "") {
	        description += "\n" + comment;
	      }

	      var data = {
	        ID: 0,
	        typeId: this._type,
	        duration: this._duration,
	        targetFieldName: this._target,
	        subject: "",
	        description: description,
	        completed: 0,
	        ownerType: BX.CrmEntityType.resolveName(this._ownerTypeId),
	        ownerID: this._ownerId
	      };
	      BX.ajax({
	        url: this._serviceUrl,
	        method: "POST",
	        dataType: "json",
	        data: {
	          "ACTION": "SAVE_WAIT",
	          "DATA": data
	        },
	        onsuccess: BX.delegate(this.onSaveSuccess, this),
	        onfailure: BX.delegate(this.onSaveFailure, this)
	      });
	      this._isRequestRunning = this._isLocked = true;
	    }
	  }, {
	    key: "cancel",
	    value: function cancel() {
	      this._input.value = "";
	      this._input.style.minHeight = "";
	      this.release();
	    }
	  }, {
	    key: "onSaveSuccess",
	    value: function onSaveSuccess(data) {
	      this._isRequestRunning = this._isLocked = false;
	      var error = BX.prop.getString(data, "ERROR", "");

	      if (error !== "") {
	        alert(error);
	        return;
	      }

	      this._input.value = "";
	      this._input.style.minHeight = "";

	      this._manager.processEditingCompletion(this);

	      this.release();
	    }
	  }, {
	    key: "onSaveFailure",
	    value: function onSaveFailure() {
	      this._isRequestRunning = this._isLocked = false;
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = Wait.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Wait();
	      self.initialize(id, settings);
	      this.items[self.getId()] = self;
	      return self;
	    }
	  }]);
	  return Wait;
	}(Editor);

	babelHelpers.defineProperty(Wait, "WaitingType", {
	  undefined: 0,
	  after: 1,
	  before: 2,
	  names: {
	    after: "after",
	    before: "before"
	  },
	  resolveTypeId: function resolveTypeId(name) {
	    if (name === this.names.after) {
	      return this.after;
	    } else if (name === this.names.before) {
	      return this.before;
	    }

	    return this.undefined;
	  }
	});
	babelHelpers.defineProperty(Wait, "messages", {});
	babelHelpers.defineProperty(Wait, "items", {});
	babelHelpers.defineProperty(Wait, "Helper", {
	  getDurationText: function getDurationText(duration, enableNumber) {
	    enableNumber = !!enableNumber;
	    var result = "";
	    var type = "D";

	    if (enableNumber) {
	      if (duration % 7 === 0) {
	        duration = duration / 7;
	        type = "W";
	      }
	    }

	    if (type === "W") {
	      result = BX.Loc.getMessagePlural('CRM_TIMELINE_WAIT_WEEK', duration);
	    } else {
	      result = BX.Loc.getMessagePlural('CRM_TIMELINE_WAIT_DAY', duration);
	    }

	    if (enableNumber) {
	      result = duration.toString() + " " + result;
	    }

	    return result;
	  },
	  getMessage: function getMessage(name) {
	    return Wait.Helper.messages.hasOwnProperty(name) ? Wait.Helper.messages[name] : name;
	  },
	  messages: {}
	});

	/** @memberof BX.Crm.Timeline.Editors */

	var Sms = /*#__PURE__*/function (_Editor) {
	  babelHelpers.inherits(Sms, _Editor);

	  function Sms() {
	    var _this;

	    babelHelpers.classCallCheck(this, Sms);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Sms).call(this));
	    _this._history = null;
	    _this._serviceUrl = "";
	    _this._isRequestRunning = false;
	    _this._isLocked = false;
	    _this._senderId = null;
	    _this._from = null;
	    _this._commEntityTypeId = null;
	    _this._commEntityId = null;
	    _this._to = null;
	    _this._canUse = null;
	    _this._canSendMessage = null;
	    _this._manageUrl = '';
	    _this._senders = [];
	    _this._fromList = [];
	    _this._toList = [];
	    _this._defaults = {};
	    _this._communications = [];
	    _this._menu = null;
	    _this._isMenuShown = false;
	    _this._shownMenuId = null;
	    _this._documentSelector = null;
	    _this._source = null;
	    _this._paymentId = null;
	    _this._shipmentId = null;
	    _this._templateId = null;
	    _this._templatesContainer = null;
	    _this._templateFieldHintNode = null;
	    _this._templateSelectorNode = null;
	    _this._templateTemplateTitleNode = null;
	    _this._templatePreviewNode = null;
	    _this._templateSelectorMenuId = 'CrmTimelineSmsEditorTemplateSelector';
	    _this._templateFieldHintHandler = BX.delegate(_this.onTemplateHintIconClick, babelHelpers.assertThisInitialized(_this));
	    _this._templateSeletorClickHandler = BX.delegate(_this.onTemplateSelectClick, babelHelpers.assertThisInitialized(_this));
	    _this._selectTemplateHandler = BX.delegate(_this.onSelectTemplate, babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }

	  babelHelpers.createClass(Sms, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      this._serviceUrl = BX.util.remove_url_param(this.getSetting("serviceUrl", ""), ['sessid', 'site']);
	      var config = BX.prop.getObject(this._settings, "config", {});
	      this._canUse = BX.prop.getBoolean(config, "canUse", false);
	      this._canSendMessage = BX.prop.getBoolean(config, "canSendMessage", false);
	      this._manageUrl = BX.prop.getString(config, "manageUrl", '');
	      this._senders = BX.prop.getArray(config, "senders", []);
	      this._defaults = BX.prop.getObject(config, "defaults", {
	        senderId: null,
	        from: null
	      });
	      this._communications = BX.prop.getArray(config, "communications", []);
	      this._isSalescenterEnabled = BX.prop.getBoolean(config, "isSalescenterEnabled", false);
	      this._isDocumentsEnabled = BX.prop.getBoolean(config, "isDocumentsEnabled", false);

	      if (this._isDocumentsEnabled) {
	        this._documentsProvider = BX.prop.getString(config, "documentsProvider", '');
	        this._documentsValue = BX.prop.getString(config, "documentsValue", '');
	      }

	      this._isFilesEnabled = BX.prop.getBoolean(config, "isFilesEnabled", false);

	      if (this._isFilesEnabled) {
	        this._diskUrls = BX.prop.getObject(config, "diskUrls");
	        this._isFilesExternalLinkEnabled = BX.prop.getBoolean(config, "isFilesExternalLinkEnabled", true);
	      }

	      this._senderSelectorNode = this._container.querySelector('[data-role="sender-selector"]');
	      this._fromContainerNode = this._container.querySelector('[data-role="from-container"]');
	      this._fromSelectorNode = this._container.querySelector('[data-role="from-selector"]');
	      this._clientContainerNode = this._container.querySelector('[data-role="client-container"]');
	      this._clientSelectorNode = this._container.querySelector('[data-role="client-selector"]');
	      this._toSelectorNode = this._container.querySelector('[data-role="to-selector"]');
	      this._messageLengthCounterWrapperNode = this._container.querySelector('[data-role="message-length-counter-wrap"]');
	      this._messageLengthCounterNode = this._container.querySelector('[data-role="message-length-counter"]');
	      this._salescenterStarter = this._container.querySelector('[data-role="salescenter-starter"]');
	      this._smsDetailSwitcher = this._container.querySelector('[data-role="sms-detail-switcher"]');
	      this._smsDetail = this._container.querySelector('[data-role="sms-detail"]');
	      this._documentSelectorButton = this._container.querySelector('[data-role="sms-document-selector"]');
	      this._fileSelectorButton = this._container.querySelector('[data-role="sms-file-selector"]');
	      this._fileUploadZone = this._container.querySelector('[data-role="sms-file-upload-zone"]');
	      this._fileUploadLabel = this._container.querySelector('[data-role="sms-file-upload-label"]');
	      this._fileSelectorBitrix = this._container.querySelector('[data-role="sms-file-selector-bitrix"]');
	      this._fileExternalLinkDisabledContent = this._container.querySelector('[data-role="sms-file-external-link-disabled"]');
	      this._templatesContainer = BX(this.getSetting("templatesContainer"));

	      if (this._templatesContainer) {
	        this._templateFieldHintNode = this._templatesContainer.querySelector('[data-role="hint"]');
	        this._templateSelectorNode = this._templatesContainer.querySelector('[data-role="template-selector"]');
	        this._templateTemplateTitleNode = this._templatesContainer.querySelector('[data-role="template-title"]');
	        this._templatePreviewNode = this._templatesContainer.querySelector('[data-role="preview"]');
	      }

	      if (this._templateFieldHintNode) {
	        BX.bind(this._templateFieldHintNode, "click", this._templateFieldHintHandler);
	      }

	      if (this._templateSelectorNode) {
	        BX.bind(this._templateSelectorNode, "click", this._templateSeletorClickHandler);
	      }

	      if (this._canUse && this._senders.length > 0) {
	        this.initSenderSelector();
	      }

	      if (this._canUse && this._canSendMessage) {
	        this.initDetailSwitcher();
	        this.initFromSelector();
	        this.initClientContainer();
	        this.initClientSelector();
	        this.initToSelector();
	        this.initMessageLengthCounter();
	        this.setMessageLengthCounter();

	        if (this._isDocumentsEnabled) {
	          this.initDocumentSelector();
	        }

	        if (this._isFilesEnabled) {
	          this.initFileSelector();
	        }
	      }

	      if (this._isSalescenterEnabled) {
	        this.initSalescenterApplication();
	      }
	    }
	  }, {
	    key: "initDetailSwitcher",
	    value: function initDetailSwitcher() {
	      BX.bind(this._smsDetailSwitcher, 'click', function () {
	        if (this._smsDetail.classList.contains('hidden')) {
	          this._smsDetail.classList.remove('hidden');

	          this._smsDetailSwitcher.innerText = BX.message('CRM_TIMELINE_COLLAPSE');
	        } else {
	          this._smsDetail.classList.add('hidden');

	          this._smsDetailSwitcher.innerText = BX.message('CRM_TIMELINE_DETAILS');
	        }
	      }.bind(this));
	    }
	  }, {
	    key: "initSenderSelector",
	    value: function initSenderSelector() {
	      var defaultSenderId = this._defaults.senderId;
	      var defaultSender = this._senders[0].canUse ? this._senders[0] : null;
	      var restSender = null;
	      var menuItems = [];
	      var handler = this.onSenderSelectorClick.bind(this);

	      for (var i = 0; i < this._senders.length; ++i) {
	        if (this._senders[i].canUse && this._senders[i].fromList.length && (this._senders[i].id === defaultSenderId || !defaultSender)) {
	          defaultSender = this._senders[i];
	        }

	        if (this._senders[i].id === 'rest') {
	          restSender = this._senders[i];
	          continue;
	        }

	        menuItems.push({
	          text: this._senders[i].name,
	          sender: this._senders[i],
	          onclick: handler,
	          className: !this._senders[i].canUse || !this._senders[i].fromList.length ? 'crm-timeline-popup-menu-item-disabled menu-popup-no-icon' : ''
	        });
	      }

	      if (restSender) {
	        if (restSender.fromList.length > 0) {
	          menuItems.push({
	            delimiter: true
	          });

	          for (var _i = 0; _i < restSender.fromList.length; ++_i) {
	            menuItems.push({
	              text: restSender.fromList[_i].name,
	              sender: restSender,
	              from: restSender.fromList[_i],
	              onclick: handler
	            });
	          }
	        }

	        menuItems.push({
	          delimiter: true
	        }, {
	          text: BX.message('CRM_TIMELINE_SMS_REST_MARKETPLACE'),
	          href: '/marketplace/category/crm_robot_sms/',
	          target: '_blank'
	        });
	      }

	      if (defaultSender) {
	        this.setSender(defaultSender);
	      }

	      BX.bind(this._senderSelectorNode, 'click', this.openMenu.bind(this, 'sender', this._senderSelectorNode, menuItems));
	    }
	  }, {
	    key: "onSenderSelectorClick",
	    value: function onSenderSelectorClick(e, item) {
	      if (item.sender) {
	        if (!item.sender.canUse || !item.sender.fromList.length) {
	          var url = BX.Uri.addParam(item.sender.manageUrl, {
	            'IFRAME': 'Y'
	          });
	          var slider = BX.SidePanel.Instance.getTopSlider();
	          var options = {
	            events: {
	              onClose: function onClose() {
	                if (slider) {
	                  slider.reload();
	                }
	              },
	              onCloseComplete: function onCloseComplete() {
	                if (!slider) {
	                  document.location.reload();
	                }
	              }
	            }
	          };

	          if (item.sender.id === 'ednaru') {
	            options.width = 700;
	          }

	          BX.SidePanel.Instance.open(url, options);
	          return;
	        }

	        this.setSender(item.sender, true);
	        var from = item.from ? item.from : item.sender.fromList[0];
	        this.setFrom(from, true);
	      }

	      this._menu.close();
	    }
	  }, {
	    key: "setSender",
	    value: function setSender(sender, setAsDefault) {
	      this._senderId = sender.id;
	      this._fromList = sender.fromList;
	      this._senderSelectorNode.textContent = sender.shortName ? sender.shortName : sender.name;
	      this._templateId = null;

	      if (sender.isTemplatesBased) {
	        this.showNode(this._templatesContainer);
	        this.hideNode(this._messageLengthCounterWrapperNode);
	        this.hideNode(this._fileSelectorButton);
	        this.hideNode(this._documentSelectorButton);
	        this.hideNode(this._input);
	        this.toggleTemplateSelectAvailability();
	        this.toggleSaveButton();
	        this._hideButtonsOnBlur = false;
	        this.onFocus();
	      } else {
	        this.hideNode(this._templatesContainer);
	        this.showNode(this._messageLengthCounterWrapperNode);
	        this.showNode(this._fileSelectorButton);
	        this.showNode(this._documentSelectorButton);
	        this.showNode(this._input);
	        this.setMessageLengthCounter();
	        this._hideButtonsOnBlur = true;
	      }

	      var visualFn = sender.id === 'rest' ? 'hide' : 'show';
	      BX[visualFn](this._fromContainerNode);

	      if (setAsDefault) {
	        BX.userOptions.save("crm", "sms_manager_editor", "senderId", this._senderId);
	      }
	    }
	  }, {
	    key: "showNode",
	    value: function showNode(node) {
	      if (node) {
	        node.style.display = "";
	      }
	    }
	  }, {
	    key: "hideNode",
	    value: function hideNode(node) {
	      if (node) {
	        node.style.display = "none";
	      }
	    }
	  }, {
	    key: "initFromSelector",
	    value: function initFromSelector() {
	      if (this._fromList.length > 0) {
	        var defaultFromId = this._defaults.from || this._fromList[0].id;
	        var defaultFrom = null;

	        for (var i = 0; i < this._fromList.length; ++i) {
	          if (this._fromList[i].id === defaultFromId || !defaultFrom) {
	            defaultFrom = this._fromList[i];
	          }
	        }

	        if (defaultFrom) {
	          this.setFrom(defaultFrom);
	        }
	      }

	      BX.bind(this._fromSelectorNode, 'click', this.onFromSelectorClick.bind(this));
	    }
	  }, {
	    key: "onFromSelectorClick",
	    value: function onFromSelectorClick(e) {
	      var menuItems = [];
	      var handler = this.onFromSelectorItemClick.bind(this);

	      for (var i = 0; i < this._fromList.length; ++i) {
	        menuItems.push({
	          text: this._fromList[i].name,
	          from: this._fromList[i],
	          onclick: handler
	        });
	      }

	      this.openMenu('from_' + this._senderId, this._fromSelectorNode, menuItems, e);
	    }
	  }, {
	    key: "onFromSelectorItemClick",
	    value: function onFromSelectorItemClick(e, item) {
	      if (item.from) {
	        this.setFrom(item.from, true);
	      }

	      this._menu.close();
	    }
	  }, {
	    key: "setFrom",
	    value: function setFrom(from, setAsDefault) {
	      this._from = from.id;

	      if (this._senderId === 'rest') {
	        this._senderSelectorNode.textContent = from.name;
	      } else {
	        this._fromSelectorNode.textContent = from.name;
	      }

	      if (setAsDefault) {
	        BX.userOptions.save("crm", "sms_manager_editor", "from", this._from);
	      }
	    }
	  }, {
	    key: "initClientContainer",
	    value: function initClientContainer() {
	      if (this._communications.length === 0) {
	        BX.hide(this._clientContainerNode);
	      }
	    }
	  }, {
	    key: "initClientSelector",
	    value: function initClientSelector() {
	      var menuItems = [];
	      var handler = this.onClientSelectorClick.bind(this);

	      for (var i = 0; i < this._communications.length; ++i) {
	        menuItems.push({
	          text: this._communications[i].caption,
	          client: this._communications[i],
	          onclick: handler
	        });

	        if (i === 0) {
	          this.setClient(this._communications[i]);
	        }
	      }

	      BX.bind(this._clientSelectorNode, 'click', this.openMenu.bind(this, 'comm', this._clientSelectorNode, menuItems));
	    }
	  }, {
	    key: "onClientSelectorClick",
	    value: function onClientSelectorClick(e, item) {
	      if (item.client) {
	        this.setClient(item.client);
	      }

	      this._menu.close();
	    }
	  }, {
	    key: "setClient",
	    value: function setClient(client) {
	      this._commEntityTypeId = client.entityTypeId;
	      this._commEntityId = client.entityId;
	      this._clientSelectorNode.textContent = client.caption;
	      this._toList = client.phones;
	      this.setTo(client.phones[0]);
	    }
	  }, {
	    key: "initToSelector",
	    value: function initToSelector() {
	      BX.bind(this._toSelectorNode, 'click', this.onToSelectorClick.bind(this));
	    }
	  }, {
	    key: "onToSelectorClick",
	    value: function onToSelectorClick(e) {
	      var menuItems = [];
	      var handler = this.onToSelectorItemClick.bind(this);

	      for (var i = 0; i < this._toList.length; ++i) {
	        menuItems.push({
	          text: this._toList[i].valueFormatted || this._toList[i].value,
	          to: this._toList[i],
	          onclick: handler
	        });
	      }

	      this.openMenu('to_' + this._commEntityTypeId + '_' + this._commEntityId, this._toSelectorNode, menuItems, e);
	    }
	  }, {
	    key: "onToSelectorItemClick",
	    value: function onToSelectorItemClick(e, item) {
	      if (item.to) {
	        this.setTo(item.to);
	      }

	      this._menu.close();
	    }
	  }, {
	    key: "setTo",
	    value: function setTo(to) {
	      this._to = to.value;
	      this._toSelectorNode.textContent = to.valueFormatted || to.value;
	    }
	  }, {
	    key: "openMenu",
	    value: function openMenu(menuId, bindElement, menuItems, e) {
	      if (this._shownMenuId === menuId) {
	        return;
	      }

	      if (this._shownMenuId !== null && this._menu) {
	        this._menu.close();

	        this._shownMenuId = null;
	      }

	      BX.PopupMenu.show(this._id + menuId, bindElement, menuItems, {
	        offsetTop: 0,
	        offsetLeft: 36,
	        angle: {
	          position: "top",
	          offset: 0
	        },
	        events: {
	          onPopupClose: BX.delegate(this.onMenuClose, this)
	        }
	      });
	      this._menu = BX.PopupMenu.currentItem;
	      e.preventDefault();
	    }
	  }, {
	    key: "onMenuClose",
	    value: function onMenuClose() {
	      this._shownMenuId = null;
	      this._menu = null;
	    }
	  }, {
	    key: "initMessageLengthCounter",
	    value: function initMessageLengthCounter() {
	      this._messageLengthMax = parseInt(this._messageLengthCounterNode.getAttribute('data-length-max'));
	      BX.bind(this._input, 'keyup', this.setMessageLengthCounter.bind(this));
	      BX.bind(this._input, 'cut', this.setMessageLengthCounterDelayed.bind(this));
	      BX.bind(this._input, 'paste', this.setMessageLengthCounterDelayed.bind(this));
	    }
	  }, {
	    key: "setMessageLengthCounterDelayed",
	    value: function setMessageLengthCounterDelayed() {
	      setTimeout(this.setMessageLengthCounter.bind(this), 0);
	    }
	  }, {
	    key: "setMessageLengthCounter",
	    value: function setMessageLengthCounter() {
	      var length = this._input.value.length;
	      this._messageLengthCounterNode.textContent = length;
	      var classFn = length >= this._messageLengthMax ? 'addClass' : 'removeClass';
	      BX[classFn](this._messageLengthCounterNode, 'crm-entity-stream-content-sms-symbol-counter-number-overhead');
	      this.toggleSaveButton();
	    }
	  }, {
	    key: "toggleSaveButton",
	    value: function toggleSaveButton() {
	      var sender = this.getSelectedSender();
	      var enabled;

	      if (!sender || !sender.isTemplatesBased) {
	        enabled = this._input.value.length > 0;
	      } else {
	        enabled = !!this._templateId;
	      }

	      if (enabled) {
	        BX.removeClass(this._saveButton, 'ui-btn-disabled');
	      } else {
	        BX.addClass(this._saveButton, 'ui-btn-disabled');
	      }
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      var sender = this.getSelectedSender();
	      var text = '';
	      var templateId = '';

	      if (!sender || !sender.isTemplatesBased) {
	        text = this._input.value;

	        if (text === '') {
	          return;
	        }
	      } else {
	        var template = this.getSelectedTemplate();

	        if (!template) {
	          return;
	        }

	        text = template.PREVIEW;
	        templateId = template.ID;
	      }

	      if (!this._communications.length) {
	        alert(BX.message('CRM_TIMELINE_SMS_ERROR_NO_COMMUNICATIONS'));
	        return;
	      }

	      if (this._isRequestRunning || this._isLocked) {
	        return;
	      }

	      this._isRequestRunning = this._isLocked = true;
	      BX.ajax({
	        url: BX.util.add_url_param(this._serviceUrl, {
	          "action": "save_sms_message",
	          "sender": this._senderId
	        }),
	        method: "POST",
	        dataType: "json",
	        data: {
	          'site': BX.message('SITE_ID'),
	          'sessid': BX.bitrix_sessid(),
	          'source': this._source,
	          "ACTION": "SAVE_SMS_MESSAGE",
	          "SENDER_ID": this._senderId,
	          "MESSAGE_FROM": this._from,
	          "MESSAGE_TO": this._to,
	          "MESSAGE_BODY": text,
	          "MESSAGE_TEMPLATE": templateId,
	          "OWNER_TYPE_ID": this._ownerTypeId,
	          "OWNER_ID": this._ownerId,
	          "TO_ENTITY_TYPE_ID": this._commEntityTypeId,
	          "TO_ENTITY_ID": this._commEntityId,
	          "PAYMENT_ID": this._paymentId,
	          "SHIPMENT_ID": this._shipmentId
	        },
	        onsuccess: BX.delegate(this.onSaveSuccess, this),
	        onfailure: BX.delegate(this.onSaveFailure, this)
	      });
	    }
	  }, {
	    key: "cancel",
	    value: function cancel() {
	      this._input.value = "";
	      this.setMessageLengthCounter();
	      this._input.style.minHeight = "";
	      this.release();
	    }
	  }, {
	    key: "onSaveSuccess",
	    value: function onSaveSuccess(data) {
	      this._isRequestRunning = this._isLocked = false;
	      var error = BX.prop.getString(data, "ERROR", "");

	      if (error !== "") {
	        alert(error);
	        return;
	      }

	      this._input.value = "";
	      this.setMessageLengthCounter();
	      this._input.style.minHeight = "";

	      this._manager.processEditingCompletion(this);

	      this.release();
	    }
	  }, {
	    key: "onSaveFailure",
	    value: function onSaveFailure() {
	      this._isRequestRunning = this._isLocked = false;
	    }
	  }, {
	    key: "initSalescenterApplication",
	    value: function initSalescenterApplication() {
	      BX.bind(this._salescenterStarter, 'click', this.startSalescenterApplication.bind(this));
	    }
	  }, {
	    key: "startSalescenterApplication",
	    value: function startSalescenterApplication() {
	      BX.loadExt('salescenter.manager').then(function () {
	        BX.Salescenter.Manager.openApplication({
	          disableSendButton: this._canSendMessage ? '' : 'y',
	          context: 'sms',
	          ownerTypeId: this._ownerTypeId,
	          ownerId: this._ownerId,
	          mode: this._ownerTypeId === BX.CrmEntityType.enumeration.deal ? 'payment_delivery' : 'payment'
	        }).then(function (result) {
	          if (result && result.get('action')) {
	            if (result.get('action') === 'sendPage' && result.get('page') && result.get('page').url) {
	              this._input.focus();

	              this._input.value = this._input.value + result.get('page').name + ' ' + result.get('page').url;
	              this.setMessageLengthCounter();
	            } else if (result.get('action') === 'sendPayment' && result.get('order')) {
	              this._input.focus();

	              this._input.value = this._input.value + result.get('order').title;
	              this.setMessageLengthCounter();
	              this._source = 'order';
	              this._paymentId = result.get('order').paymentId;
	              this._shipmentId = result.get('order').shipmentId;
	            }
	          }
	        }.bind(this));
	      }.bind(this));
	    }
	  }, {
	    key: "initDocumentSelector",
	    value: function initDocumentSelector() {
	      BX.bind(this._documentSelectorButton, 'click', this.onDocumentSelectorClick.bind(this));
	    }
	  }, {
	    key: "onDocumentSelectorClick",
	    value: function onDocumentSelectorClick() {
	      if (!this._documentSelector) {
	        BX.loadExt('documentgenerator.selector').then(function () {
	          this._documentSelector = new BX.DocumentGenerator.Selector.Menu({
	            node: this._documentSelectorButton,
	            moduleId: 'crm',
	            provider: this._documentsProvider,
	            value: this._documentsValue,
	            analyticsLabelPrefix: 'crmTimelineSmsEditor'
	          });
	          this.selectPublicUrl();
	        }.bind(this));
	      } else {
	        this.selectPublicUrl();
	      }
	    }
	  }, {
	    key: "selectPublicUrl",
	    value: function selectPublicUrl() {
	      if (!this._documentSelector) {
	        return;
	      }

	      this._documentSelector.show().then(function (object) {
	        if (object instanceof BX.DocumentGenerator.Selector.Template) {
	          this._documentSelector.createDocument(object).then(function (document) {
	            this.pasteDocumentUrl(document);
	          }.bind(this))["catch"](function (error) {
	            console.error(error);
	          }.bind(this));
	        } else if (object instanceof BX.DocumentGenerator.Selector.Document) {
	          this.pasteDocumentUrl(object);
	        }
	      }.bind(this))["catch"](function (error) {
	        console.error(error);
	      }.bind(this));
	    }
	  }, {
	    key: "pasteDocumentUrl",
	    value: function pasteDocumentUrl(document) {
	      this._documentSelector.getDocumentPublicUrl(document).then(function (publicUrl) {
	        this._input.focus();

	        this._input.value = this._input.value + ' ' + document.getTitle() + ' ' + publicUrl;
	        this.setMessageLengthCounter();
	        this._source = 'document';
	      }.bind(this))["catch"](function (error) {
	        console.error(error);
	      }.bind(this));
	    }
	  }, {
	    key: "initFileSelector",
	    value: function initFileSelector() {
	      BX.bind(this._fileSelectorButton, 'click', this.onFileSelectorClick.bind(this));
	    }
	  }, {
	    key: "closeFileSelector",
	    value: function closeFileSelector() {
	      BX.PopupMenu.destroy('sms-file-selector');
	    }
	  }, {
	    key: "onFileSelectorClick",
	    value: function onFileSelectorClick() {
	      BX.PopupMenu.show('sms-file-selector', this._fileSelectorButton, [{
	        text: BX.message('CRM_TIMELINE_SMS_UPLOAD_FILE'),
	        onclick: this.uploadFile.bind(this),
	        className: this._isFilesExternalLinkEnabled ? '' : 'crm-entity-stream-content-sms-menu-item-with-lock'
	      }, {
	        text: BX.message('CRM_TIMELINE_SMS_FIND_FILE'),
	        onclick: this.findFile.bind(this),
	        className: this._isFilesExternalLinkEnabled ? '' : 'crm-entity-stream-content-sms-menu-item-with-lock'
	      }]);
	    }
	  }, {
	    key: "getFileUploadInput",
	    value: function getFileUploadInput() {
	      return document.getElementById(this._fileUploadLabel.getAttribute('for'));
	    }
	  }, {
	    key: "uploadFile",
	    value: function uploadFile() {
	      this.closeFileSelector();

	      if (this._isFilesExternalLinkEnabled) {
	        this.initDiskUF();
	        BX.fireEvent(this.getFileUploadInput(), 'click');
	      } else {
	        this.showFilesExternalLinkFeaturePopup();
	      }
	    }
	  }, {
	    key: "findFile",
	    value: function findFile() {
	      this.closeFileSelector();

	      if (this._isFilesExternalLinkEnabled) {
	        this.initDiskUF();
	        BX.fireEvent(this._fileSelectorBitrix, 'click');
	      } else {
	        this.showFilesExternalLinkFeaturePopup();
	      }
	    }
	  }, {
	    key: "getLoader",
	    value: function getLoader() {
	      if (!this.loader) {
	        this.loader = new BX.Loader({
	          size: 50
	        });
	      }

	      return this.loader;
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader(node) {
	      if (node && !this.getLoader().isShown()) {
	        this.getLoader().show(node);
	      }
	    }
	  }, {
	    key: "hideLoader",
	    value: function hideLoader() {
	      if (this.getLoader().isShown()) {
	        this.getLoader().hide();
	      }
	    }
	  }, {
	    key: "initDiskUF",
	    value: function initDiskUF() {
	      if (this.isDiskFileUploaderInited || !this._isFilesEnabled) {
	        return;
	      }

	      this.isDiskFileUploaderInited = true;
	      BX.addCustomEvent(this._fileUploadZone, 'OnFileUploadSuccess', this.OnFileUploadSuccess.bind(this));
	      BX.addCustomEvent(this._fileUploadZone, 'DiskDLoadFormControllerInit', function (uf) {
	        uf._onUploadProgress = function () {
	          this.showLoader(this._fileSelectorButton.parentNode.parentNode);
	        }.bind(this);
	      }.bind(this));
	      BX.Disk.UF.add({
	        UID: this._fileUploadZone.getAttribute('data-node-id'),
	        controlName: this._fileUploadLabel.getAttribute('for'),
	        hideSelectDialog: false,
	        urlSelect: this._diskUrls.urlSelect,
	        urlRenameFile: this._diskUrls.urlRenameFile,
	        urlDeleteFile: this._diskUrls.urlDeleteFile,
	        urlUpload: this._diskUrls.urlUpload
	      });
	      BX.onCustomEvent(this._fileUploadZone, 'DiskLoadFormController', ['show']);
	    }
	  }, {
	    key: "OnFileUploadSuccess",
	    value: function OnFileUploadSuccess(fileResult, uf, file, uploaderFile) {
	      this.hideLoader();
	      var diskFileId = parseInt(fileResult.element_id.replace('n', ''));
	      var fileName = fileResult.element_name;
	      this.pasteFileUrl(diskFileId, fileName);
	    }
	  }, {
	    key: "pasteFileUrl",
	    value: function pasteFileUrl(diskFileId, fileName) {
	      this.showLoader(this._fileSelectorButton.parentNode.parentNode);
	      BX.ajax.runAction('disk.file.generateExternalLink', {
	        analyticsLabel: 'crmTimelineSmsEditorGetFilePublicUrl',
	        data: {
	          fileId: diskFileId
	        }
	      }).then(function (response) {
	        this.hideLoader();

	        if (response.data.externalLink && response.data.externalLink.link) {
	          this._input.focus();

	          this._input.value = this._input.value + ' ' + fileName + ' ' + response.data.externalLink.link;
	          this.setMessageLengthCounter();
	          this._source = 'file';
	        }
	      }.bind(this))["catch"](function (response) {
	        console.error(response.errors.pop().message);
	      });
	    }
	  }, {
	    key: "getFeaturePopup",
	    value: function getFeaturePopup(content) {
	      if (this.featurePopup != null) {
	        return this.featurePopup;
	      }

	      this.featurePopup = new BX.PopupWindow('bx-popup-crm-sms-editor-feature-popup', null, {
	        zIndex: 200,
	        autoHide: true,
	        closeByEsc: true,
	        closeIcon: true,
	        overlay: true,
	        events: {
	          onPopupDestroy: function () {
	            this.featurePopup = null;
	          }.bind(this)
	        },
	        content: content,
	        contentColor: 'white'
	      });
	      return this.featurePopup;
	    }
	  }, {
	    key: "showFilesExternalLinkFeaturePopup",
	    value: function showFilesExternalLinkFeaturePopup() {
	      this.getFeaturePopup(this._fileExternalLinkDisabledContent).show();
	    }
	  }, {
	    key: "onTemplateHintIconClick",
	    value: function onTemplateHintIconClick() {
	      if (this._senderId === 'ednaru') {
	        top.BX.Helper.show("redirect=detail&code=14214014");
	      }
	    }
	  }, {
	    key: "showTemplateSelectDropdown",
	    value: function showTemplateSelectDropdown(items) {
	      var menuItems = [];

	      if (BX.Type.isArray(items)) {
	        if (items.length) {
	          items.forEach(function (item) {
	            menuItems.push({
	              value: item.ID,
	              text: item.TITLE,
	              onclick: this._selectTemplateHandler
	            });
	          }.bind(this));
	          BX.PopupMenu.show({
	            id: this._templateSelectorMenuId,
	            bindElement: this._templateSelectorNode,
	            items: menuItems,
	            angle: false,
	            width: this._templateSelectorNode.offsetWidth
	          });
	        }
	      } else if (this._senderId) {
	        var loaderMenuId = this._templateSelectorMenuId + 'loader';
	        var loaderMenuLoaderId = this._templateSelectorMenuId + 'loader';
	        BX.PopupMenu.show({
	          id: loaderMenuId,
	          bindElement: this._templateSelectorNode,
	          items: [{
	            html: '<div id="' + loaderMenuLoaderId + '"></div>'
	          }],
	          angle: false,
	          width: this._templateSelectorNode.offsetWidth,
	          height: 60,
	          events: {
	            onDestroy: function () {
	              this.hideLoader();
	            }.bind(this)
	          }
	        });
	        this.showLoader(BX(loaderMenuLoaderId));

	        if (!this._isRequestRunning) {
	          this._isRequestRunning = true;
	          var senderId = this._senderId;
	          BX.ajax.runAction('messageservice.Sender.getTemplates', {
	            data: {
	              id: senderId,
	              context: {
	                module: 'crm',
	                entityTypeId: this._manager._ownerTypeId,
	                entityId: this._manager._ownerId
	              }
	            }
	          }).then(function (response) {
	            this._isRequestRunning = false;

	            var sender = this._senders.find(function (sender) {
	              return sender.id === senderId;
	            }.bind(this));

	            if (sender) {
	              sender.templates = response.data.templates;
	              this.toggleTemplateSelectAvailability();

	              if (BX.PopupMenu.getMenuById(loaderMenuId)) {
	                BX.PopupMenu.getMenuById(loaderMenuId).close();
	                this.showTemplateSelectDropdown(sender.templates);
	              }
	            }
	          }.bind(this))["catch"](function (response) {
	            this._isRequestRunning = false;

	            if (BX.PopupMenu.getMenuById(loaderMenuId)) {
	              if (response && response.errors && response.errors[0] && response.errors[0].message) {
	                alert(response.errors[0].message);
	              }

	              BX.PopupMenu.getMenuById(loaderMenuId).close();
	            }
	          }.bind(this));
	        }
	      }
	    }
	  }, {
	    key: "getSelectedSender",
	    value: function getSelectedSender() {
	      return this._senders.find(function (sender) {
	        return sender.id === this._senderId;
	      }.bind(this));
	    }
	  }, {
	    key: "getSelectedTemplate",
	    value: function getSelectedTemplate() {
	      var sender = this.getSelectedSender();

	      if (!this._templateId || !sender || !sender.templates) {
	        return null;
	      }

	      var template = sender.templates.find(function (template) {
	        return template.ID == this._templateId;
	      }.bind(this));
	      return template ? template : null;
	    }
	  }, {
	    key: "onTemplateSelectClick",
	    value: function onTemplateSelectClick() {
	      var sender = this.getSelectedSender();

	      if (sender) {
	        this.showTemplateSelectDropdown(sender.templates);
	      }
	    }
	  }, {
	    key: "onSelectTemplate",
	    value: function onSelectTemplate(e, item) {
	      this._templateId = item.value;
	      this.applySelectedTemplate();
	      this.toggleSaveButton();
	      var menu = BX.PopupMenu.getMenuById(this._templateSelectorMenuId);

	      if (menu) {
	        menu.close();
	      }
	    }
	  }, {
	    key: "toggleTemplateSelectAvailability",
	    value: function toggleTemplateSelectAvailability() {
	      var sender = this.getSelectedSender();

	      if (sender && BX.Type.isArray(sender.templates) && !sender.templates.length) {
	        BX.addClass(this._templateSelectorNode, 'ui-ctl-disabled');
	        this._templateTemplateTitleNode.textContent = BX.message('CRM_TIMELINE_SMS_TEMPLATES_NOT_FOUND');
	      } else {
	        BX.removeClass(this._templateSelectorNode, 'ui-ctl-disabled');
	        this.applySelectedTemplate();
	      }
	    }
	  }, {
	    key: "applySelectedTemplate",
	    value: function applySelectedTemplate() {
	      var sender = this.getSelectedSender();

	      if (!this._templateId || !sender || !sender.templates) {
	        this.hideNode(this._templatePreviewNode);
	        this._templateTemplateTitleNode.textContent = '';
	      } else {
	        var template = this.getSelectedTemplate();

	        if (template) {
	          var preview = BX.Text.encode(template.PREVIEW).replace(/\n/g, '<br>');
	          this.showNode(this._templatePreviewNode);
	          this._templatePreviewNode.innerHTML = preview;
	          this._templateTemplateTitleNode.textContent = template.TITLE;
	        } else {
	          this.hideNode(this._templatePreviewNode);
	          this._templateTemplateTitleNode.textContent = '';
	        }
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Sms();
	      self.initialize(id, settings);
	      Sms.items[self.getId()] = self;
	      return self;
	    }
	  }]);
	  return Sms;
	}(Editor);

	babelHelpers.defineProperty(Sms, "items", {});

	/** @memberof BX.Crm.Timeline.Editors */

	var Rest = /*#__PURE__*/function (_Editor) {
	  babelHelpers.inherits(Rest, _Editor);

	  function Rest() {
	    var _this;

	    babelHelpers.classCallCheck(this, Rest);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Rest).call(this));
	    _this._interfaceInitialized = false;
	    return _this;
	  }

	  babelHelpers.createClass(Rest, [{
	    key: "action",
	    value: function action(_action) {
	      if (!this._interfaceInitialized) {
	        this._interfaceInitialized = true;
	        this.initializeInterface();
	      }

	      if (_action === 'activity_rest_applist') {
	        BX.rest.Marketplace.open({
	          PLACEMENT: this.getSetting("placement", '')
	        });
	        top.BX.addCustomEvent(top, 'Rest:AppLayout:ApplicationInstall', BX.proxy(this.fireUpdateEvent, this));
	      } else {
	        var appId = _action.replace('activity_rest_', '');

	        var appData = appId.split('_');
	        BX.rest.AppLayout.openApplication(appData[0], {
	          ID: this._ownerId
	        }, {
	          PLACEMENT: this.getSetting("placement", ''),
	          PLACEMENT_ID: appData[1]
	        });
	      }
	    }
	  }, {
	    key: "initializeInterface",
	    value: function initializeInterface() {
	      if (!!top.BX.rest && !!top.BX.rest.AppLayout) {
	        var entityTypeId = this._manager._ownerTypeId,
	            entityId = this._manager._ownerId;
	        var PlacementInterface = top.BX.rest.AppLayout.initializePlacement(this.getSetting("placement", ''));

	        PlacementInterface.prototype.reloadData = function (params, cb) {
	          BX.Crm.EntityEvent.fireUpdate(entityTypeId, entityId, '');
	          cb();
	        };
	      }
	    }
	  }, {
	    key: "fireUpdateEvent",
	    value: function fireUpdateEvent() {
	      var entityTypeId = this._manager._ownerTypeId,
	          entityId = this._manager._ownerId;
	      setTimeout(function () {
	        console.log('fireUpdate', entityId, entityTypeId);
	        BX.Crm.EntityEvent.fire(BX.Crm.EntityEvent.names.invalidate, entityTypeId, entityId, '');
	      }, 3000);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Rest();
	      self.initialize(id, settings);
	      Rest.items[self.getId()] = self;
	      return self;
	    }
	  }]);
	  return Rest;
	}(Editor);

	babelHelpers.defineProperty(Rest, "items", {});

	/** @memberof BX.Crm.Timeline.Editors */

	var Comment = /*#__PURE__*/function (_Editor) {
	  babelHelpers.inherits(Comment, _Editor);

	  function Comment() {
	    var _this;

	    babelHelpers.classCallCheck(this, Comment);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Comment).call(this));
	    _this._history = null;
	    _this._serviceUrl = "";
	    _this._postForm = null;
	    _this._editor = null;
	    _this._isRequestRunning = false;
	    _this._isLocked = false;
	    return _this;
	  }

	  babelHelpers.createClass(Comment, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      this._serviceUrl = this.getSetting("serviceUrl", "");
	      BX.unbind(this._input, "blur", this._blurHandler);
	      BX.unbind(this._input, "keyup", this._keyupHandler);
	    }
	  }, {
	    key: "loadEditor",
	    value: function loadEditor() {
	      this._editorName = 'CrmTimeLineComment0';
	      if (this._postForm) return;
	      BX.ajax.runAction("crm.api.timeline.loadEditor", {
	        data: {
	          name: this._editorName
	        }
	      }).then(this.onLoadEditorSuccess.bind(this));
	    }
	  }, {
	    key: "onLoadEditorSuccess",
	    value: function onLoadEditorSuccess(result) {
	      var html = BX.prop.getString(BX.prop.getObject(result, "data", {}), "html", '');
	      BX.html(this._editorContainer, html).then(BX.delegate(this.showEditor, this)).then(BX.delegate(this.addEvents, this));
	    }
	  }, {
	    key: "addEvents",
	    value: function addEvents() {
	      BX.addCustomEvent(this._editorContainer.firstElementChild, 'onFileIsAppended', BX.delegate(function (id, item) {
	        BX.addClass(this._saveButton, 'ui-btn-disabled');
	        BX.addClass(this._saveButton, 'ui-btn-clock');

	        this._saveButton.removeEventListener("click", this._saveButtonHandler);
	      }, this));
	      BX.addCustomEvent(this._editorContainer.firstElementChild, 'onFileIsAdded', BX.delegate(function (file, controller, obj, blob) {
	        BX.removeClass(this._saveButton, 'ui-btn-clock');
	        BX.removeClass(this._saveButton, 'ui-btn-disabled');

	        this._saveButton.addEventListener("click", this._saveButtonHandler);
	      }, this));
	    }
	  }, {
	    key: "showEditor",
	    value: function showEditor() {
	      if (LHEPostForm) {
	        window.setTimeout(BX.delegate(function () {
	          this._postForm = LHEPostForm.getHandler(this._editorName);
	          this._editor = BXHtmlEditor.Get(this._editorName);
	          BX.onCustomEvent(this._postForm.eventNode, 'OnShowLHE', [true]);
	        }, this), 100);
	      }
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
	    key: "onFocus",
	    value: function onFocus(e) {
	      this._input.style.display = 'none';

	      if (this._editor && this._postForm) {
	        this._postForm.eventNode.style.display = 'block';

	        this._editor.Focus();
	      } else {
	        if (!BX.type.isDomNode(this._editorContainer)) {
	          this._editorContainer = BX.create("div", {
	            attrs: {
	              className: "crm-entity-stream-section-comment-editor"
	            }
	          });

	          this._editorContainer.appendChild(BX.create("DIV", {
	            attrs: {
	              className: "crm-timeline-wait"
	            }
	          }));

	          this._container.appendChild(this._editorContainer);
	        }

	        window.setTimeout(BX.delegate(function () {
	          this.loadEditor();
	        }, this), 100);
	      }

	      BX.addClass(this._container, "focus");
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      var text = "";
	      var attachmentList = [];

	      if (this._postForm) {
	        text = this._postForm.oEditor.GetContent();

	        this._postForm.eventNode.querySelectorAll('input[name="UF_CRM_COMMENT_FILES[]"]').forEach(function (input) {
	          attachmentList.push(input.value);
	        });
	      } else {
	        text = this._input.value;
	      }

	      if (text === "") {
	        if (!this.emptyCommentMessage) {
	          this.emptyCommentMessage = new BX.PopupWindow('timeline_empty_new_comment_' + this._ownerId, this._saveButton, {
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

	      if (this._isRequestRunning || this._isLocked) {
	        return;
	      }

	      this._isRequestRunning = this._isLocked = true;
	      BX.ajax({
	        url: this._serviceUrl,
	        method: "POST",
	        dataType: "json",
	        data: {
	          "ACTION": "SAVE_COMMENT",
	          "TEXT": text,
	          "OWNER_TYPE_ID": this._ownerTypeId,
	          "OWNER_ID": this._ownerId,
	          "ATTACHMENTS": attachmentList
	        },
	        onsuccess: BX.delegate(this.onSaveSuccess, this),
	        onfailure: BX.delegate(this.onSaveFailure, this)
	      });
	    }
	  }, {
	    key: "cancel",
	    value: function cancel() {
	      this._input.value = "";
	      this._input.style.minHeight = "";
	      if (BX.type.isDomNode(this._editorContainer)) this._postForm.eventNode.style.display = 'none';
	      this._input.style.display = 'block';
	      BX.removeClass(this._container, "focus");
	      this.release();
	    }
	  }, {
	    key: "onSaveSuccess",
	    value: function onSaveSuccess(data) {
	      this._isRequestRunning = false;

	      if (this._postForm) {
	        this._postForm.reinit('', {});
	      }

	      this.cancel();
	      var itemData = BX.prop.getObject(data, "HISTORY_ITEM");

	      var historyItem = this._history.createItem(itemData);

	      this._history.addItem(historyItem, 0);

	      var anchor = this._history.createAnchor();

	      historyItem.layout({
	        anchor: anchor
	      });
	      var move = BX.CrmCommentAnimation.create(historyItem.getWrapper(), anchor, BX.pos(this._input), {
	        start: BX.delegate(this.onAnimationStart, this),
	        complete: BX.delegate(this.onAnimationComplete, this)
	      });
	      move.run();
	    }
	  }, {
	    key: "onSaveFailure",
	    value: function onSaveFailure() {
	      this._isRequestRunning = this._isLocked = false;
	    }
	  }, {
	    key: "onAnimationStart",
	    value: function onAnimationStart() {
	      this._input.value = "";
	    }
	  }, {
	    key: "onAnimationComplete",
	    value: function onAnimationComplete() {
	      this._isLocked = false;
	      BX.removeClass(this._container, "focus");
	      this._input.style.minHeight = "";

	      this._manager.processEditingCompletion(this);

	      this.release();
	      this._history._anchor = null;

	      this._history.refreshLayout();
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Comment();
	      self.initialize(id, settings);
	      Comment.items[self.getId()] = self;
	      return self;
	    }
	  }]);
	  return Comment;
	}(Editor);

	babelHelpers.defineProperty(Comment, "items", {});

	/** @memberof BX.Crm.Timeline.Items */

	var Scheduled = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(Scheduled, _Item);

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
	      var userId = BX.prop.getInteger(this.getPermissions(), "USER_ID", 0);
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
	      var permissions = BX.prop.getObject(result, "PERMISSIONS", null);

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
	    key: "hasDeadline",
	    value: function hasDeadline() {
	      return BX.type.isDate(this.getDeadline());
	    }
	  }, {
	    key: "isCounterEnabled",
	    value: function isCounterEnabled() {
	      var deadline = this.getDeadline();
	      return deadline && History.isCounterEnabled(deadline);
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

	      var perms = BX.prop.getObject(this.getAssociatedEntityData(), "PERMISSIONS", {});
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

	      var perms = BX.prop.getObject(this.getAssociatedEntityData(), "PERMISSIONS", {});
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
	      var vueComponent = this.makeVueComponent(options, 'schedule');
	      this._wrapper = vueComponent ? vueComponent : this.prepareContent();

	      if (this._wrapper) {
	        var enableAdd = BX.type.isPlainObject(options) ? BX.prop.getBoolean(options, "add", true) : true;

	        if (enableAdd) {
	          var anchor = BX.type.isPlainObject(options) && BX.type.isElementNode(options["anchor"]) ? options["anchor"] : null;

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
	      var entityData = BX.prop.getObject(data, "ASSOCIATED_ENTITY", {});
	      return BX.CrmActivityStatus.isFinal(BX.prop.getInteger(entityData, "STATUS", 0));
	    }
	  }, {
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Scheduled();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Scheduled;
	}(Item$1);

	/** @memberof BX.Crm.Timeline */
	var Action = /*#__PURE__*/function () {
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

	var Activity = /*#__PURE__*/function (_Action) {
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

	var Email = /*#__PURE__*/function (_Activity) {
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
	      var settings = {
	        "ownerType": BX.CrmEntityType.resolveName(BX.prop.getInteger(this._entityData, "OWNER_TYPE_ID", 0)),
	        "ownerID": BX.prop.getInteger(this._entityData, "OWNER_ID", 0),
	        "ownerUrl": BX.prop.getString(this._entityData, "OWNER_URL", ""),
	        "ownerTitle": BX.prop.getString(this._entityData, "OWNER_TITLE", ""),
	        "originalMessageID": BX.prop.getInteger(this._entityData, "ID", 0),
	        "messageType": "RE"
	      };

	      if (BX.CrmActivityProvider && top.BX.Bitrix24 && top.BX.Bitrix24.Slider) {
	        var activity = this._activityEditor.addEmail(settings);

	        activity.addOnSave(this._saveHandler);
	      } else {
	        this.loadActivityCommunications(BX.delegate(function (communications) {
	          settings['communications'] = BX.type.isArray(communications) ? communications : [];
	          settings['communicationsLoaded'] = true;
	          BX.CrmActivityEmail.prepareReply(settings);

	          var activity = this._activityEditor.addEmail(settings);

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
	}(Activity);
	/** @memberof BX.Crm.Timeline.Actions */

	var HistoryEmail = /*#__PURE__*/function (_Email) {
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
	      var self = new HistoryEmail();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return HistoryEmail;
	}(Email);
	/** @memberof BX.Crm.Timeline.Actions */

	var ScheduleEmail = /*#__PURE__*/function (_Email2) {
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
	      var self = new ScheduleEmail();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return ScheduleEmail;
	}(Email);

	/** @memberof BX.Crm.Timeline.Items */

	var HistoryActivity = /*#__PURE__*/function (_History) {
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
	      var entityData = this.getAssociatedEntityData();
	      var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	      var typeCategoryId = this.getTypeCategoryId();

	      if (typeCategoryId === BX.CrmActivityType.email) {
	        return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingEmail" : "outgoingEmail");
	      } else if (typeCategoryId === BX.CrmActivityType.call) {
	        return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall");
	      } else if (typeCategoryId === BX.CrmActivityType.meeting) {
	        return this.getMessage("meeting");
	      } else if (typeCategoryId === BX.CrmActivityType.task) {
	        return this.getMessage("task");
	      } else if (typeCategoryId === BX.CrmActivityType.provider) {
	        var providerId = BX.prop.getString(entityData, "PROVIDER_ID", "");

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
	      var entityData = this.getAssociatedEntityData();
	      var markTypeId = BX.prop.getInteger(entityData, "MARK_TYPE_ID", 0);

	      if (markTypeId <= 0) {
	        return null;
	      }

	      var messageName = "";

	      if (markTypeId === Mark.success) {
	        messageName = "SuccessMark";
	      } else if (markTypeId === Mark.renew) {
	        messageName = "RenewMark";
	      }

	      if (messageName === "") {
	        return null;
	      }

	      var markText = "";
	      var typeCategoryId = this.getTypeCategoryId();

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

	      var typeCategoryId = this.getTypeCategoryId();

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

	      var menuItems = [];

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
	      var entityData = this.getAssociatedEntityData();
	      var id = BX.prop.getInteger(entityData, "ID", 0);

	      if (id > 0) {
	        this._activityEditor.viewActivity(id);
	      }
	    }
	  }, {
	    key: "edit",
	    value: function edit() {
	      this.closeContextMenu();
	      var associatedEntityTypeId = this.getAssociatedEntityTypeId();

	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        var entityData = this.getAssociatedEntityData();
	        var id = BX.prop.getInteger(entityData, "ID", 0);

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
	      var dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);

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
	      var associatedEntityTypeId = this.getAssociatedEntityTypeId();

	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        var entityData = this.getAssociatedEntityData();
	        var id = BX.prop.getInteger(entityData, "ID", 0);

	        if (id > 0) {
	          var activityEditor = this._activityEditor;
	          var item = activityEditor.getItemById(id);

	          if (item) {
	            activityEditor.deleteActivity(id, true);
	          } else {
	            var serviceUrl = BX.util.add_url_param(activityEditor.getSetting('serviceUrl', ''), {
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
	              onfailure: function onfailure(data) {}
	            });
	          }
	        }
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new HistoryActivity();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return HistoryActivity;
	}(History);

	babelHelpers.defineProperty(HistoryActivity, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */

	var Document = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Document, _HistoryActivity);

	  function Document() {
	    babelHelpers.classCallCheck(this, Document);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Document).call(this));
	  }

	  babelHelpers.createClass(Document, [{
	    key: "getTitle",
	    value: function getTitle() {
	      var typeCategoryId = BX.prop.getInteger(this._data, "TYPE_CATEGORY_ID", 0);

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
	      var typeCategoryId = BX.prop.getInteger(this._data, "TYPE_CATEGORY_ID", 0);

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
	      var typeCategoryId = BX.prop.getInteger(this._data, "TYPE_CATEGORY_ID", 0);
	      return typeCategoryId !== 3;
	    }
	  }, {
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      var statusLayout = this.prepareTitleStatusLayout();

	      if (statusLayout) {
	        header.appendChild(statusLayout);
	      }

	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      var text = this.getTextDataParam("COMMENT", "");
	      var wrapper = BX.create("DIV", {
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

	      var contentWrapper = BX.create("DIV", {
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
	      var header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      var detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        html: text
	      });
	      var title = BX.findChildByClassName(detailWrapper, 'document-title-link');

	      if (title) {
	        BX.bind(title, 'click', BX.proxy(this.editDocument, this));
	      }

	      contentWrapper.appendChild(detailWrapper); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer); //endregion

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
	      var menuItems = [];

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
	      var dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);

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
	            var deleteItem = this._history.findItemById(this._id);

	            if (deleteItem instanceof Document) {
	              deleteItem.clearAnimate();
	            }

	            var deleteFixedItem = this._fixedHistory.findItemById(this._id);

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
	      var documentId = this.getData().DOCUMENT_ID || 0;

	      if (documentId > 0) {
	        var url = '/bitrix/components/bitrix/crm.document.view/slider.php';
	        url = BX.util.add_url_param(url, {
	          documentId: documentId
	        });

	        if (BX.SidePanel) {
	          BX.SidePanel.Instance.open(url, {
	            width: 980
	          });
	        } else {
	          top.location.href = url;
	        }
	      }
	    }
	  }, {
	    key: "updateWrapper",
	    value: function updateWrapper() {
	      var wrapper = this.getWrapper();

	      if (wrapper) {
	        var detailWrapper = BX.findChildByClassName(wrapper, 'crm-entity-stream-content-detail');

	        if (detailWrapper) {
	          BX.adjust(detailWrapper, {
	            html: this.getTextDataParam("COMMENT", "")
	          });
	          var title = BX.findChildByClassName(detailWrapper, 'document-title-link');

	          if (title) {
	            BX.bind(title, 'click', BX.proxy(this.editDocument, this));
	          }
	        }
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Document();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Document;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline */

	var Steam = /*#__PURE__*/function () {
	  function Steam() {
	    babelHelpers.classCallCheck(this, Steam);
	    this._id = "";
	    this._settings = {};
	    this._container = null;
	    this._manager = null;
	    this._activityEditor = null;
	    this._userTimezoneOffset = null;
	    this._serverTimezoneOffset = null;
	    this._timeFormat = "";
	    this._year = 0;
	    this._isStubMode = false;
	    this._userId = 0;
	    this._readOnly = false;
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
	      } //


	      var datetimeFormat = BX.message("FORMAT_DATETIME").replace(/:SS/, "");
	      var dateFormat = BX.message("FORMAT_DATE");
	      this._timeFormat = BX.date.convertBitrixFormat(BX.util.trim(datetimeFormat.replace(dateFormat, ""))); //

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
	      var currentUrl = this.getSetting("currentUrl");
	      var ajaxId = this.getSetting("ajaxId");

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
	      if (!this._userTimezoneOffset) {
	        this._userTimezoneOffset = parseInt(BX.message("USER_TZ_OFFSET"));

	        if (isNaN(this._userTimezoneOffset)) {
	          this._userTimezoneOffset = 0;
	        }
	      }

	      return this._userTimezoneOffset;
	    }
	  }, {
	    key: "getServerTimezoneOffset",
	    value: function getServerTimezoneOffset() {
	      if (!this._serverTimezoneOffset) {
	        this._serverTimezoneOffset = parseInt(BX.message("SERVER_TZ_OFFSET"));

	        if (isNaN(this._serverTimezoneOffset)) {
	          this._serverTimezoneOffset = 0;
	        }
	      }

	      return this._serverTimezoneOffset;
	    }
	  }, {
	    key: "formatTime",
	    value: function formatTime(time, now, utc) {
	      return BX.date.format(this._timeFormat, time, now, utc);
	    }
	  }, {
	    key: "formatDate",
	    value: function formatDate(date) {
	      return BX.date.format([["today", "today"], ["tommorow", "tommorow"], ["yesterday", "yesterday"], ["", date.getFullYear() === this._year ? "j F" : "j F Y"]], date);
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

	      var offset = length - 1;
	      var whitespaceOffset = text.substring(offset).search(/\s/i);

	      if (whitespaceOffset > 0) {
	        offset += whitespaceOffset;
	      }

	      return text.substring(0, offset) + "...";
	    }
	  }]);
	  return Steam;
	}();

	/** @memberof BX.Crm.Timeline.Streams */

	var EntityChat = /*#__PURE__*/function (_Stream) {
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
	      var lockScript = BX.prop.getString(this._data, "LOCK_SCRIPT", null);

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
	      var userId = parseInt(top.BX.message("USER_ID"));
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
	      var userInfos = this.getUserInfoData();
	      return userId > 0 && BX.type.isPlainObject(userInfos[userId]) ? userInfos[userId] : null;
	    }
	  }, {
	    key: "removeUserInfo",
	    value: function removeUserInfo(userId) {
	      var userInfos = this.getUserInfoData();

	      if (userId > 0 && BX.type.isPlainObject(userInfos[userId])) {
	        delete userInfos[userId];
	      }
	    }
	  }, {
	    key: "setUnreadMessageCounter",
	    value: function setUnreadMessageCounter(userId, counter) {
	      var userInfos = this.getUserInfoData();

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
	      var infos = this.getUserInfoData();
	      var list = Object.values(infos);

	      if (list.length === 0) {
	        this._userWrapper.appendChild(BX.create("span", {
	          props: {
	            className: "crm-entity-stream-live-im-user-avatar ui-icon ui-icon-common-user"
	          },
	          children: [BX.create("i")]
	        }));
	      } else {
	        var count = list.length >= 3 ? 3 : list.length;

	        for (var i = 0; i < count; i++) {
	          var info = list[i];
	          var icon = BX.create("i");
	          var imageUrl = BX.prop.getString(info, "avatar", "");

	          if (imageUrl !== "") {
	            icon.style.backgroundImage = "url(" + imageUrl + ")";
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
	      } else //if(this._layoutType === EntityChat.LayoutType.invitation)
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

	      var message = this.getMessageData(); //region Message Date

	      var isoDate = BX.prop.getString(message, "date", "");

	      if (isoDate === "") {
	        this._messageDateNode.innerHTML = "";
	      } else {
	        var remoteDate = new Date(isoDate).getTime() / 1000 + this.getServerTimezoneOffset() + this.getUserTimezoneOffset();
	        var localTime = new Date().getTime() / 1000 + this.getServerTimezoneOffset() + this.getUserTimezoneOffset();
	        this._messageDateNode.innerHTML = this.formatTime(remoteDate, localTime, true);
	      } //endregion
	      //region Message Text


	      var text = BX.prop.getString(message, "text", "");
	      var params = BX.prop.getObject(message, "params", {});

	      if (text === "") {
	        this._messageTextNode.innerHTML = "";
	      } else {
	        if (typeof top.BX.MessengerCommon !== "undefined") {
	          text = top.BX.MessengerCommon.purifyText(text, params);
	        }

	        this._messageTextNode.innerHTML = text;
	      } //endregion
	      //region Unread Message Counter


	      var counter = 0;
	      var userId = this.getUserId();

	      if (userId > 0) {
	        counter = BX.prop.getInteger(BX.prop.getObject(BX.prop.getObject(this._data, "USER_INFOS", {}), userId, null), "counter", 0);
	      }

	      this._messageCounterNode.innerHTML = counter.toString();
	      this._messageCounterNode.style.display = counter > 0 ? "" : "none"; //endregion
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

	      var slug = "";
	      var chatId = this.getChatId();

	      if (chatId > 0 && this.hasUserInfo(this.getUserId())) {
	        slug = "chat" + chatId.toString();
	      } else {
	        var ownerInfo = this.getOwnerInfo();
	        var entityId = BX.prop.getInteger(ownerInfo, "ENTITY_ID", 0);
	        var entityTypeName = BX.prop.getString(ownerInfo, "ENTITY_TYPE_NAME", "");

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
	      var chatId = this.getChatId();

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

	        var message = this.getMessageData();

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
	      var self = new EntityChat();
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

	var MenuBar = /*#__PURE__*/function () {
	  function MenuBar() {
	    babelHelpers.classCallCheck(this, MenuBar);
	    this._id = "";
	    this._ownerInfo = null;
	    this._container = null;
	    this._activityEditor = null;
	    this._commentEditor = null;
	    this._waitEditor = null;
	    this._smsEditor = null;
	    this._zoomEditor = null;
	    this._readOnly = false;
	    this._menu = null;
	    this._manager = null;
	  }

	  babelHelpers.createClass(MenuBar, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._settings = settings ? settings : {};
	      this._ownerInfo = BX.prop.getObject(this._settings, "ownerInfo");

	      if (!this._ownerInfo) {
	        throw "MenuBar. A required parameter 'ownerInfo' is missing.";
	      }

	      this._activityEditor = BX.prop.get(this._settings, "activityEditor", null);
	      this._commentEditor = BX.prop.get(this._settings, "commentEditor");
	      this._waitEditor = BX.prop.get(this._settings, "waitEditor");
	      this._smsEditor = BX.prop.get(this._settings, "smsEditor");
	      this._zoomEditor = BX.prop.get(this._settings, "zoomEditor");
	      this._restEditor = BX.prop.get(this._settings, "restEditor");
	      this._manager = BX.prop.get(this._settings, "manager");

	      if (!(this._manager instanceof Manager)) {
	        throw "BX.CrmTimeline. Manager instance is not found.";
	      }

	      this._readOnly = BX.prop.getBoolean(this._settings, "readOnly", false);
	      this._menu = BX.Main.interfaceButtonsManager.getById(BX.prop.getString(this._settings, "menuId", (this._ownerInfo['ENTITY_TYPE_NAME'] + "_menu").toLowerCase()));
	      BX.addCustomEvent(this._manager.getId() + "_menu", function (id) {
	        this.setActiveItemById(id);
	      }.bind(this));
	      this._activeItem = this._menu.getActive();
	    }
	  }, {
	    key: "reset",
	    value: function reset() {
	      var firstId = null;

	      this._menu.getAllItems().forEach(function (item) {
	        if (firstId === null) {
	          var id = item.dataset.id;

	          if (["comment", "wait", "sms", "zoom"].indexOf(id) >= 0 && this["_" + id + "Editor"]) {
	            firstId = id;
	          }
	        }
	      }.bind(this));

	      this.setActiveItemById(firstId || "comment");
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this._id;
	    }
	  }, {
	    key: "setActiveItemById",
	    value: function setActiveItemById(id) {
	      if (this.processItemSelection(id) === true) {
	        var currentDiv = this._menu.getItemById(id);

	        if (currentDiv && this._activeItem !== currentDiv) {
	          var wasActiveInMoreMenu = this._menu.isActiveInMoreMenu();

	          BX.addClass(currentDiv, this._menu.classes.itemActive);

	          if (this._menu.getItemData) {
	            var currentDivData = this._menu.getItemData(currentDiv);

	            currentDivData['IS_ACTIVE'] = true;

	            if (BX.type.isDomNode(this._activeItem)) {
	              BX.removeClass(this._activeItem, this._menu.classes.itemActive);

	              var activeItemData = this._menu.getItemData(this._activeItem);

	              activeItemData['IS_ACTIVE'] = false;
	            }
	          } else {
	            // Old approach
	            var isActiveData = {};

	            try {
	              isActiveData = JSON.parse(currentDiv.dataset.item);
	            } catch (err) {
	              isActiveData = {};
	            }

	            isActiveData.IS_ACTIVE = true;
	            currentDiv.dataset.item = JSON.stringify(isActiveData);
	            var wasActiveData = {};

	            if (BX.type.isDomNode(this._activeItem)) {
	              BX.removeClass(this._activeItem, this._menu.classes.itemActive);

	              try {
	                wasActiveData = JSON.parse(this._activeItem.dataset.item);
	              } catch (err) {
	                wasActiveData = {};
	              }

	              wasActiveData.IS_ACTIVE = false;
	              this._activeItem.dataset.item = JSON.stringify(wasActiveData);
	            }
	          }

	          var isActiveInMoreMenu = this._menu.isActiveInMoreMenu();

	          if (isActiveInMoreMenu || wasActiveInMoreMenu) {
	            var submenu = this._menu["getSubmenu"] ? this._menu.getSubmenu() : BX.PopupMenu.getMenuById("main_buttons_popup_" + String(this._ownerInfo['ENTITY_TYPE_NAME']).toLowerCase() + "_menu");

	            if (submenu) {
	              submenu.getMenuItems().forEach(function (menuItem) {
	                var container = menuItem.getContainer();

	                if (isActiveInMoreMenu && container.title === currentDiv.title) {
	                  BX.addClass(container, this._menu.classes.itemActive);
	                } else if (wasActiveInMoreMenu && container.title === this._activeItem.title) {
	                  BX.removeClass(container, this._menu.classes.itemActive);
	                }
	              }.bind(this));
	            }

	            if (isActiveInMoreMenu) {
	              BX.addClass(this._menu.getMoreButton(), this._menu.classes.itemActive);
	            } else if (wasActiveInMoreMenu) {
	              BX.removeClass(this._menu.getMoreButton(), this._menu.classes.itemActive);
	            }
	          }

	          this._activeItem = currentDiv;
	        }
	      }

	      this._menu.closeSubmenu();
	    }
	  }, {
	    key: "processItemSelection",
	    value: function processItemSelection(menuId) {
	      if (this._readOnly) {
	        return false;
	      }

	      var planner = null;
	      var action = menuId;

	      if (action === "call") {
	        planner = new BX.Crm.Activity.Planner();
	        planner.showEdit({
	          "TYPE_ID": BX.CrmActivityType.call,
	          "OWNER_TYPE_ID": this._ownerInfo['ENTITY_TYPE_ID'],
	          "OWNER_ID": this._ownerInfo['ENTITY_ID']
	        });
	      }

	      if (action === "meeting") {
	        planner = new BX.Crm.Activity.Planner();
	        planner.showEdit({
	          "TYPE_ID": BX.CrmActivityType.meeting,
	          "OWNER_TYPE_ID": this._ownerInfo['ENTITY_TYPE_ID'],
	          "OWNER_ID": this._ownerInfo['ENTITY_ID']
	        });
	      } else if (action === "email") {
	        this._activityEditor.addEmail({
	          "ownerType": this._ownerInfo['ENTITY_TYPE_NAME'],
	          "ownerID": this._ownerInfo['ENTITY_ID'],
	          "ownerUrl": this._ownerInfo['SHOW_URL'],
	          "ownerTitle": this._ownerInfo['TITLE'],
	          "subject": ""
	        });
	      } else if (action === "delivery") {
	        this._activityEditor.addDelivery({
	          "ownerType": this._ownerInfo['ENTITY_TYPE_NAME'],
	          "ownerID": this._ownerInfo['ENTITY_ID'],
	          "orderList": this._ownerInfo['ORDER_LIST']
	        });
	      } else if (action === "task") {
	        this._activityEditor.addTask({
	          "ownerType": this._ownerInfo['ENTITY_TYPE_NAME'],
	          "ownerID": this._ownerInfo['ENTITY_ID']
	        });
	      } else if (["comment", "wait", "sms", "zoom"].indexOf(action) >= 0 && this["_" + action + "Editor"]) {
	        if (this._commentEditor) {
	          this._commentEditor.setVisible(action === "comment");
	        }

	        if (this._waitEditor) {
	          this._waitEditor.setVisible(action === "wait");
	        }

	        if (this._smsEditor) {
	          this._smsEditor.setVisible(action === "sms");
	        }

	        if (this._zoomEditor) {
	          this._zoomEditor.setVisible(action === "zoom");
	        }

	        return true;
	      } else if (action === "visit") {
	        var visitParameters = this._manager.getSetting("visitParameters");

	        visitParameters['OWNER_TYPE'] = this._ownerInfo['ENTITY_TYPE_NAME'];
	        visitParameters['OWNER_ID'] = this._ownerInfo['ENTITY_ID'];
	        BX.CrmActivityVisit.create(visitParameters).showEdit();
	      } else if (action.match(/^activity_rest_/)) {
	        if (this._restEditor) {
	          this._restEditor.action(action);
	        }
	      }

	      return false;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new MenuBar();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return MenuBar;
	}();

	/** @memberof BX.Crm.Timeline.Tools */
	var AudioPlaybackRateSelector = /*#__PURE__*/function () {
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
	      var i = 0;
	      var length = this.availableRates.length;

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
	      var selectedRate = this.getRate();
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
	      var popupMenu = BX.Main.MenuManager.getMenuById(this.menuId);

	      if (popupMenu) {
	        var popupWindow = popupMenu.getPopupWindow();

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

	      for (var i = 0, length = this.renderedItems.length; i < length; i++) {
	        var textNode = this.renderedItems[i].querySelector('.crm-audio-cap-speed-text');

	        if (textNode) {
	          textNode.innerHTML = this.getText();
	        }
	      }

	      for (var _i = 0, _length = this.players.length; _i < _length; _i++) {
	        this.players[_i].vjsPlayer.playbackRate(this.getRate());
	      }
	    }
	  }, {
	    key: "getText",
	    value: function getText() {
	      var text;

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
	      var item = BX.Dom.create('div', {
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
	var SchedulePostponeController = /*#__PURE__*/function () {
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

	      var offset = 0;

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
	      var m = SchedulePostponeController.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new SchedulePostponeController();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return SchedulePostponeController;
	}();

	babelHelpers.defineProperty(SchedulePostponeController, "messages", {});

	/** @memberof BX.Crm.Timeline.Items.Scheduled */

	var Activity$1 = /*#__PURE__*/function (_Scheduled) {
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
	      var status = BX.prop.getInteger(this.getAssociatedEntityData(), "STATUS");
	      return status === BX.CrmActivityStatus.completed || status === BX.CrmActivityStatus.autoCompleted;
	    }
	  }, {
	    key: "setAsDone",
	    value: function setAsDone(isDone) {
	      isDone = !!isDone;

	      if (this.isDone() === isDone) {
	        return;
	      }

	      var id = BX.prop.getInteger(this.getAssociatedEntityData(), "ID", 0);

	      if (id > 0) {
	        this._activityEditor.setActivityCompleted(id, isDone, BX.delegate(this.onSetAsDoneCompleted, this));
	      }
	    }
	  }, {
	    key: "postpone",
	    value: function postpone(offset) {
	      var id = this.getSourceId();

	      if (id > 0 && offset > 0) {
	        this._activityEditor.postponeActivity(id, offset, BX.delegate(this.onPosponeCompleted, this));
	      }
	    }
	  }, {
	    key: "view",
	    value: function view() {
	      var id = BX.prop.getInteger(this.getAssociatedEntityData(), "ID", 0);

	      if (id > 0) {
	        this._activityEditor.viewActivity(id);
	      }
	    }
	  }, {
	    key: "edit",
	    value: function edit() {
	      this.closeContextMenu();
	      var associatedEntityTypeId = this.getAssociatedEntityTypeId();

	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        var entityData = this.getAssociatedEntityData();
	        var id = BX.prop.getInteger(entityData, "ID", 0);

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
	      var dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);

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
	      var associatedEntityTypeId = this.getAssociatedEntityTypeId();

	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        var entityData = this.getAssociatedEntityData();
	        var id = BX.prop.getInteger(entityData, "ID", 0);

	        if (id > 0) {
	          var activityEditor = this._activityEditor;
	          var item = activityEditor.getItemById(id);

	          if (item) {
	            activityEditor.deleteActivity(id, true);
	          } else {
	            var activityType = activityEditor.getSetting('ownerType', '');
	            var activityId = activityEditor.getSetting('ownerID', '');
	            var serviceUrl = BX.util.add_url_param(activityEditor.getSetting('serviceUrl', ''), {
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
	              onfailure: function onfailure(data) {}
	            });
	          }
	        }
	      }
	    }
	  }, {
	    key: "getDeadline",
	    value: function getDeadline() {
	      var entityData = this.getAssociatedEntityData();
	      var time = BX.parseDate(entityData["DEADLINE_SERVER"], false, "YYYY-MM-DD", "YYYY-MM-DD HH:MI:SS");

	      if (!time) {
	        return null;
	      }

	      return new Date(time.getTime() + 1000 * Item$1.getUserTimezoneOffset());
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
	      var deadline = this.getDeadline();
	      var timeText = deadline ? this.formatDateTime(deadline) : this.getMessage("termless");
	      var entityData = this.getAssociatedEntityData();
	      var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	      var isDone = this.isDone();
	      var subject = BX.prop.getString(entityData, "SUBJECT", "");
	      var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      var communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      var title = BX.prop.getString(communication, "TITLE", "");
	      var showUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      var communicationValue = BX.prop.getString(communication, "TYPE", "") !== "" ? BX.prop.getString(communication, "VALUE", "") : "";
	      var wrapperClassName = this.getWrapperClassName();

	      if (wrapperClassName !== "") {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned" + " " + wrapperClassName;
	      } else {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned";
	      }

	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: wrapperClassName
	        }
	      });
	      var iconClassName = this.getIconClassName();

	      if (this.isCounterEnabled()) {
	        iconClassName += " crm-entity-stream-section-counter";
	      }

	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: iconClassName
	        }
	      })); //region Context Menu

	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      } //endregion


	      var contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        }
	      });
	      wrapper.appendChild(contentWrapper); //region Details

	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }

	      var contentInnerWrapper = BX.create("DIV", {
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
	      var headerWrapper = BX.create("DIV", {
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
	      var statusNode = this.getStatusNode();

	      if (statusNode) {
	        headerWrapper.appendChild(statusNode);
	      }

	      headerWrapper.appendChild(this._deadlineNode);
	      contentInnerWrapper.appendChild(headerWrapper);
	      var detailWrapper = BX.create("DIV", {
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
	      var additionalDetails = this.prepareDetailNodes();

	      if (BX.type.isArray(additionalDetails)) {
	        var i = 0;
	        var length = additionalDetails.length;

	        for (; i < length; i++) {
	          detailWrapper.appendChild(additionalDetails[i]);
	        }
	      }

	      var members = BX.create("DIV", {
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
	        var communicationNode = this.prepareCommunicationNode(communicationValue);

	        if (communicationNode) {
	          members.appendChild(communicationNode);
	        }
	      }

	      detailWrapper.appendChild(members); //endregion
	      //region Set as Done Button

	      var setAsDoneButton = BX.create("INPUT", {
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

	      var buttonContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-planned-action"
	        },
	        children: [setAsDoneButton]
	      });
	      contentInnerWrapper.appendChild(buttonContainer); //endregion
	      //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentInnerWrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentInnerWrapper.appendChild(this._actionContainer); //endregion

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
	      var menuItems = [];

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

	      var handler = BX.delegate(this.onContextMenuItemSelect, this);

	      if (!this._postponeController) {
	        this._postponeController = SchedulePostponeController.create("", {
	          item: this
	        });
	      }

	      var postponeMenu = {
	        id: "postpone",
	        text: this._postponeController.getTitle(),
	        items: []
	      };

	      var commands = this._postponeController.getCommandList();

	      var i = 0;
	      var length = commands.length;

	      for (; i < length; i++) {
	        var command = commands[i];
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
	  }]);
	  return Activity;
	}(Scheduled);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */

	var Email$1 = /*#__PURE__*/function (_Activity) {
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
	      var entityData = this.getAssociatedEntityData();
	      var title = BX.prop.getString(entityData, "SUBJECT", "");
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
	      var self = new Email();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Email;
	}(Activity$1);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */

	var Call = /*#__PURE__*/function (_Activity) {
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
	      var entityData = this.getAssociatedEntityData();
	      var callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);
	      var callTypeText = callInfo !== null ? BX.prop.getString(callInfo, "CALL_TYPE_TEXT", "") : "";

	      if (callTypeText !== "") {
	        return callTypeText;
	      }

	      return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall");
	    }
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      var entityData = this.getAssociatedEntityData();
	      var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	      var title = BX.prop.getString(entityData, "SUBJECT", "");
	      var messageName = direction === BX.CrmActivityDirection.incoming ? 'incomingCallRemove' : 'outgoingCallRemove';
	      title = BX.util.htmlspecialchars(title);
	      return this.getMessage(messageName).replace("#TITLE#", title);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Call();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Call;
	}(Activity$1);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */

	var CallTracker = /*#__PURE__*/function (_Call) {
	  babelHelpers.inherits(CallTracker, _Call);

	  function CallTracker() {
	    babelHelpers.classCallCheck(this, CallTracker);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CallTracker).call(this));
	  }

	  babelHelpers.createClass(CallTracker, [{
	    key: "getStatusNode",
	    value: function getStatusNode() {
	      var entityData = this.getAssociatedEntityData();
	      var callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);

	      if (!callInfo) {
	        return false;
	      }

	      if (!BX.prop.getBoolean(callInfo, "HAS_STATUS", false)) {
	        return false;
	      }

	      var isSuccessfull = BX.prop.getBoolean(callInfo, "SUCCESSFUL", false);
	      var statusText = BX.prop.getString(callInfo, "STATUS_TEXT", "");
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
	      var self = new CallTracker();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return CallTracker;
	}(Call);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */

	var Meeting = /*#__PURE__*/function (_Activity) {
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
	      var entityData = this.getAssociatedEntityData();
	      var title = BX.prop.getString(entityData, "SUBJECT", "");
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
	      var self = new Meeting();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Meeting;
	}(Activity$1);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */

	var Task = /*#__PURE__*/function (_Activity) {
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
	      var entityData = this.getAssociatedEntityData();
	      var title = BX.prop.getString(entityData, "SUBJECT", "");
	      title = BX.util.htmlspecialchars(title);
	      return this.getMessage('taskRemove').replace("#TITLE#", title);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Task();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Task;
	}(Activity$1);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */

	var StoreDocument = /*#__PURE__*/function (_Activity) {
	  babelHelpers.inherits(StoreDocument, _Activity);

	  function StoreDocument() {
	    babelHelpers.classCallCheck(this, StoreDocument);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(StoreDocument).call(this));
	  }

	  babelHelpers.createClass(StoreDocument, [{
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "crm-entity-stream-section-planned-store-document";
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-store-document";
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription() {
	      return this.getMessage("storeDocument");
	    }
	  }, {
	    key: "getPrepositionText",
	    value: function getPrepositionText(direction) {
	      return this.getMessage("reciprocal");
	    }
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      var entityData = this.getAssociatedEntityData();
	      var title = BX.prop.getString(entityData, "SUBJECT", "");
	      title = BX.util.htmlspecialchars(title);
	      return this.getMessage('taskRemove').replace("#TITLE#", title);
	    }
	  }, {
	    key: "isEditable",
	    value: function isEditable() {
	      return false;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent(options) {
	      var deadline = this.getDeadline();
	      var timeText = deadline ? this.formatDateTime(deadline) : this.getMessage("termless");
	      var entityData = this.getAssociatedEntityData();
	      var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	      var isDone = this.isDone();
	      var wrapperClassName = this.getWrapperClassName();

	      if (wrapperClassName !== "") {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned" + " " + wrapperClassName;
	      } else {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned";
	      }

	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: wrapperClassName
	        }
	      });
	      var iconClassName = this.getIconClassName();

	      if (this.isCounterEnabled()) {
	        iconClassName += " crm-entity-stream-section-counter";
	      }

	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: iconClassName
	        }
	      })); //region Context Menu

	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      } //endregion


	      var contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        }
	      });
	      wrapper.appendChild(contentWrapper); //region Details

	      var contentInnerWrapper = BX.create("DIV", {
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
	      var headerWrapper = BX.create("DIV", {
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
	      var statusNode = this.getStatusNode();

	      if (statusNode) {
	        headerWrapper.appendChild(statusNode);
	      }

	      headerWrapper.appendChild(this._deadlineNode);
	      contentInnerWrapper.appendChild(headerWrapper);
	      var detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      contentInnerWrapper.appendChild(detailWrapper);
	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        children: [BX.create("SPAN", {
	          text: this.getMessage("storeDocumentDescription")
	        }), BX.create("A", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-target",
	            href: "#"
	          },
	          events: {
	            click: BX.delegate(function (e) {
	              top.BX.Helper.show('redirect=detail&code=14828480');
	              e.preventDefault ? e.preventDefault() : e.returnValue = false;
	            })
	          },
	          text: " " + BX.message('CRM_TIMELINE_DETAILS')
	        })]
	      }));
	      var additionalDetails = this.prepareDetailNodes();

	      if (BX.type.isArray(additionalDetails)) {
	        var i = 0;
	        var length = additionalDetails.length;

	        for (; i < length; i++) {
	          detailWrapper.appendChild(additionalDetails[i]);
	        }
	      } //endregion
	      //region Set as Done Button


	      var setAsDoneButton = BX.create("INPUT", {
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

	      var buttonContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-planned-action"
	        },
	        children: [setAsDoneButton]
	      });
	      contentInnerWrapper.appendChild(buttonContainer); //endregion
	      //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentInnerWrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentInnerWrapper.appendChild(this._actionContainer); //endregion

	      return wrapper;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new StoreDocument();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return StoreDocument;
	}(Activity$1);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */

	var WebForm = /*#__PURE__*/function (_Activity) {
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
	      var self = new WebForm();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return WebForm;
	}(Activity$1);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */

	var Wait$1 = /*#__PURE__*/function (_Scheduled) {
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
	      var entityData = this.getAssociatedEntityData();
	      var time = BX.parseDate(entityData["DEADLINE_SERVER"], false, "YYYY-MM-DD", "YYYY-MM-DD HH:MI:SS");

	      if (!time) {
	        return null;
	      }

	      return new Date(time.getTime() + 1000 * Item$1.getUserTimezoneOffset());
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

	      var id = this.getAssociatedEntityId();

	      if (id > 0) {
	        var editor = this._schedule.getManager().getWaitEditor();

	        if (editor) {
	          editor.complete(id, isDone, BX.delegate(this.onSetAsDoneCompleted, this));
	        }
	      }
	    }
	  }, {
	    key: "postpone",
	    value: function postpone(offset) {
	      var id = this.getAssociatedEntityId();

	      if (id > 0 && offset > 0) {
	        var editor = this._schedule.getManager().getWaitEditor();

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
	      var deadline = this.getDeadline();
	      var timeText = deadline ? this.formatDateTime(deadline) : this.getMessage("termless");
	      var entityData = this.getAssociatedEntityData();
	      var isDone = this.isDone();
	      var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      var wrapperClassName = this.getWrapperClassName();

	      if (wrapperClassName !== "") {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned" + " " + wrapperClassName;
	      } else {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned";
	      }

	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: wrapperClassName
	        }
	      });
	      var iconClassName = this.getIconClassName();

	      if (this.isCounterEnabled()) {
	        iconClassName += " crm-entity-stream-section-counter";
	      }

	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: iconClassName
	        }
	      })); //region Context Menu

	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      } //endregion


	      var contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        }
	      });
	      wrapper.appendChild(contentWrapper); //region Details

	      if (description !== "") {
	        description = BX.util.trim(description);
	        description = BX.util.strip_tags(description);
	        description = this.cutOffText(description, 512);
	        description = BX.util.nl2br(description);
	      }

	      var contentInnerWrapper = BX.create("DIV", {
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
	      var headerWrapper = BX.create("DIV", {
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
	      var detailWrapper = BX.create("DIV", {
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
	      var members = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-contact-info"
	        }
	      });
	      detailWrapper.appendChild(members); //endregion
	      //region Set as Done Button

	      var setAsDoneButton = BX.create("INPUT", {
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

	      var buttonContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-planned-action"
	        },
	        children: [setAsDoneButton]
	      });
	      contentInnerWrapper.appendChild(buttonContainer); //endregion
	      //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentInnerWrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentInnerWrapper.appendChild(this._actionContainer); //endregion

	      return wrapper;
	    }
	  }, {
	    key: "prepareContextMenuItems",
	    value: function prepareContextMenuItems() {
	      var menuItems = [];
	      var handler = BX.delegate(this.onContextMenuItemSelect, this);

	      if (!this._postponeController) {
	        this._postponeController = SchedulePostponeController.create("", {
	          item: this
	        });
	      }

	      var postponeMenu = {
	        id: "postpone",
	        text: this._postponeController.getTitle(),
	        items: []
	      };

	      var commands = this._postponeController.getCommandList();

	      var i = 0;
	      var length = commands.length;

	      for (; i < length; i++) {
	        var command = commands[i];
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
	      var self = new Wait();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Wait;
	}(Scheduled);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */

	var Request = /*#__PURE__*/function (_Activity) {
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
	      var self = new Request();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Request;
	}(Activity$1);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */

	var Rest$1 = /*#__PURE__*/function (_Activity) {
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
	      var wrapper = babelHelpers.get(babelHelpers.getPrototypeOf(Rest.prototype), "prepareContent", this).call(this, options);
	      var data = this.getAssociatedEntityData();

	      if (data['APP_TYPE'] && data['APP_TYPE']['ICON_SRC']) {
	        var iconNode = wrapper.querySelector('[class="' + this.getIconClassName() + '"]');

	        if (iconNode) {
	          iconNode.style.backgroundImage = "url('" + data['APP_TYPE']['ICON_SRC'] + "')";
	          iconNode.style.backgroundPosition = "center center";
	        }
	      }

	      return wrapper;
	    }
	  }, {
	    key: "getTypeDescription",
	    value: function getTypeDescription() {
	      var entityData = this.getAssociatedEntityData();

	      if (entityData['APP_TYPE'] && entityData['APP_TYPE']['NAME']) {
	        return entityData['APP_TYPE']['NAME'];
	      }

	      return this.getMessage("restApplication");
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Rest();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Rest;
	}(Activity$1);

	/** @memberof BX.Crm.Timeline.Actions */

	var OpenLine = /*#__PURE__*/function (_Activity) {
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

	      var slug = "";
	      var communication = BX.prop.getObject(this._entityData, "COMMUNICATION", null);

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
	      var m = OpenLine.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new OpenLine();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return OpenLine;
	}(Activity);
	babelHelpers.defineProperty(OpenLine, "messages", {});

	/** @memberof BX.Crm.Timeline.Items.Scheduled */

	var OpenLine$1 = /*#__PURE__*/function (_Activity) {
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
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-IM"
	        }
	      });
	      var messageWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-IM-messages"
	        }
	      });
	      wrapper.appendChild(messageWrapper);
	      var openLineData = BX.prop.getObject(this.getAssociatedEntityData(), "OPENLINE_INFO", null);

	      if (openLineData) {
	        var messages = BX.prop.getArray(openLineData, "MESSAGES", []);
	        var i = 0;
	        var length = messages.length;

	        for (; i < length; i++) {
	          var message = messages[i];
	          var isExternal = BX.prop.getBoolean(message, "IS_EXTERNAL", true);
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

	      var slug = "";
	      var communication = BX.prop.getObject(this.getAssociatedEntityData(), "COMMUNICATION", null);

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
	      var self = new OpenLine$$1();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return OpenLine$$1;
	}(Activity$1);

	/** @memberof BX.Crm.Timeline.Items.Scheduled */

	var Zoom = /*#__PURE__*/function (_Activity) {
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
	      var deadline = this.getDeadline();
	      var timeText = deadline ? this.formatDateTime(deadline) : this.getMessage("termless");
	      var entityData = this.getAssociatedEntityData();
	      var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	      var isDone = this.isDone();
	      var subject = BX.prop.getString(entityData, "SUBJECT", "");
	      var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
	      var communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      var title = BX.prop.getString(communication, "TITLE", "");
	      var showUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      var communicationValue = BX.prop.getString(communication, "TYPE", "") !== "" ? BX.prop.getString(communication, "VALUE", "") : "";
	      var wrapperClassName = this.getWrapperClassName();

	      if (wrapperClassName !== "") {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned" + " " + wrapperClassName;
	      } else {
	        wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned";
	      }

	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: wrapperClassName
	        }
	      });
	      var iconClassName = this.getIconClassName();

	      if (this.isCounterEnabled()) {
	        iconClassName += " crm-entity-stream-section-counter";
	      }

	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: iconClassName
	        }
	      })); //region Context Menu

	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      } //endregion


	      var contentWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        }
	      });
	      wrapper.appendChild(contentWrapper); //region Details

	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }

	      var contentInnerWrapper = BX.create("DIV", {
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
	      var headerWrapper = BX.create("DIV", {
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
	      var detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      contentInnerWrapper.appendChild(detailWrapper);

	      if (entityData['ZOOM_INFO']) {
	        var topic = entityData['ZOOM_INFO']['TOPIC'];
	        var duration = entityData['ZOOM_INFO']['DURATION'];
	        var startTimeStamp = BX.parseDate(entityData['ZOOM_INFO']['CONF_START_TIME'], false, "YYYY-MM-DD", "YYYY-MM-DD HH:MI:SS");
	        var date = new Date(startTimeStamp.getTime() + 1000 * Item$1.getUserTimezoneOffset());
	        var detailZoomMessage = BX.create("span", {
	          text: this.getMessage("zoomCreatedMessage").replace("#CONFERENCE_TITLE#", topic).replace("#DATE_TIME#", this.formatDateTime(date)).replace("#DURATION#", duration)
	        });
	        var detailZoomInfoLink = BX.create("A", {
	          attrs: {
	            href: entityData['ZOOM_INFO']['CONF_URL'],
	            target: "_blank"
	          },
	          text: entityData['ZOOM_INFO']['CONF_URL']
	        });
	        var detailZoomInfo = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-zoom-info"
	          },
	          children: [detailZoomMessage, detailZoomInfoLink]
	        });
	        detailWrapper.appendChild(detailZoomInfo);
	        var detailZoomCopyInviteLink = BX.create("A", {
	          attrs: {
	            className: 'ui-link ui-link-dashed',
	            "data-url": entityData['ZOOM_INFO']['CONF_URL']
	          },
	          text: this.getMessage("zoomCreatedCopyInviteLink")
	        });
	        BX.clipboard.bindCopyClick(detailZoomCopyInviteLink, {
	          text: entityData['ZOOM_INFO']['CONF_URL']
	        });
	        var detailZoomStartConferenceButton = BX.create("BUTTON", {
	          attrs: {
	            className: 'ui-btn ui-btn-sm ui-btn-primary'
	          },
	          text: this.getMessage("zoomCreatedStartConference"),
	          events: {
	            "click": function click() {
	              window.open(entityData['ZOOM_INFO']['CONF_URL']);
	            }
	          }
	        });
	        var detailZoomCopyInviteLinkWrapper = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-zoom-link-wrapper"
	          },
	          children: [detailZoomCopyInviteLink]
	        });
	        detailWrapper.appendChild(detailZoomCopyInviteLinkWrapper);
	        detailWrapper.appendChild(detailZoomStartConferenceButton);
	      }

	      var additionalDetails = this.prepareDetailNodes();

	      if (BX.type.isArray(additionalDetails)) {
	        var i = 0;
	        var length = additionalDetails.length;

	        for (; i < length; i++) {
	          detailWrapper.appendChild(additionalDetails[i]);
	        }
	      } //endregion
	      //region Set as Done Button


	      var setAsDoneButton = BX.create("INPUT", {
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

	      var buttonContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-planned-action"
	        },
	        children: [setAsDoneButton]
	      });
	      contentInnerWrapper.appendChild(buttonContainer); //endregion
	      //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentInnerWrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentInnerWrapper.appendChild(this._actionContainer); //endregion

	      return wrapper;
	    }
	  }, {
	    key: "prepareDetailNodes",
	    value: function prepareDetailNodes() {}
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Zoom();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Zoom;
	}(Activity$1);

	/** @memberof BX.Crm.Timeline.Animation */
	var Item$2 = /*#__PURE__*/function () {
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
	      var originalPosition = BX.pos(this._node);
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
	      var node = this._ghostNode;
	      var movingEvent = new BX.easing({
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
	      var placeEventAnim = new BX.easing({
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
	      var node = this._finalItem.getWrapper();

	      this._anchor.parentNode.insertBefore(node, this._anchor.nextSibling);

	      this._finalItemHeight = this._anchor.offsetHeight - node.offsetHeight;
	      this._anchor.style.height = 0;
	      node.style.marginBottom = this._finalItemHeight + "px";
	    }
	  }, {
	    key: "removeGhost",
	    value: function removeGhost() {
	      var ghostNode = this._ghostNode;

	      var finalNode = this._finalItem.getWrapper();

	      ghostNode.style.overflow = "hidden";
	      var hideCasperItem = new BX.easing({
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
	      var removePlannedEvent = new BX.easing({
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
	      var self = new Item();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Item;
	}();

	/** @memberof BX.Crm.Timeline.Animation */
	var Shift = /*#__PURE__*/function () {
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
	      var movingEvent = new BX.easing({
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
	      var self = new Shift();
	      self.initialize(node, anchor, startPosition, shadowNode, events);
	      return self;
	    }
	  }]);
	  return Shift;
	}();

	/** @memberof BX.Crm.Timeline.Animation */

	var ItemNew = /*#__PURE__*/function () {
	  function ItemNew() {
	    babelHelpers.classCallCheck(this, ItemNew);
	    this._id = "";
	    this._settings = {};
	    this._initialItem = null;
	    this._finalItem = null;
	    this._events = null;
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
	      var node = this._finalItem.getWrapper();

	      this._anchor.parentNode.insertBefore(node, this._anchor.nextSibling);
	    }
	  }, {
	    key: "run",
	    value: function run() {
	      this._node = this._initialItem.getWrapper();
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
	      var shift = Shift.create(this._node, this._anchor, this._startPosition, this._stub, {
	        complete: BX.delegate(this.finish, this)
	      });
	      shift.run();
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
	      var stubContainer = this._stub.querySelector('.crm-entity-stream-section-content');

	      this._anchor.style.height = 0; //this._anchor.parentNode.insertBefore(this._node, this._anchor.nextSibling);

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
	      setTimeout(BX.delegate(function () {
	        BX.remove(this._stub);
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
	      var self = new ItemNew();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return ItemNew;
	}();

	/** @memberof BX.Crm.Timeline.Streams */

	var Schedule = /*#__PURE__*/function (_Stream) {
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
	    _this._timeFormat = "";
	    return _this;
	  }

	  babelHelpers.createClass(Schedule, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      var datetimeFormat = BX.message("FORMAT_DATETIME").replace(/:SS/, "");
	      var dateFormat = BX.message("FORMAT_DATE");
	      var timeFormat = BX.util.trim(datetimeFormat.replace(dateFormat, ""));
	      this._timeFormat = BX.date.convertBitrixFormat(timeFormat);

	      if (!this.isStubMode()) {
	        var itemData = this.getSetting("itemData");

	        if (!BX.type.isArray(itemData)) {
	          itemData = [];
	        }

	        var i, length, item;

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

	      var label = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-planned-label"
	        },
	        text: this.getMessage("planned")
	      });
	      var wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned-label";

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
	        var length = this._items.length;

	        if (length === 0) {
	          this.addStub();
	        } else {
	          for (var i = 0; i < length; i++) {
	            var item = this._items[i];
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
	      var length = this._items.length;

	      if (length === 0) {
	        this.addStub();

	        if (this._history && this._history.hasContent()) {
	          BX.removeClass(this._stub, "crm-entity-stream-section-last");
	        } else {
	          BX.addClass(this._stub, "crm-entity-stream-section-last");
	        }

	        var stubIcon = this._stub.querySelector(".crm-entity-stream-section-icon");

	        if (stubIcon) {
	          if (this._manager.isStubCounterEnabled()) {
	            BX.addClass(stubIcon, "crm-entity-stream-section-counter");
	          } else {
	            BX.removeClass(stubIcon, "crm-entity-stream-section-counter");
	          }
	        }

	        return;
	      }

	      var i, item;

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
	      var now = new Date();
	      return BX.date.format([["today", "today, " + this._timeFormat], ["tommorow", "tommorow, " + this._timeFormat], ["yesterday", "yesterday, " + this._timeFormat], ["", (time.getFullYear() === now.getFullYear() ? "j F " : "j F Y ") + this._timeFormat]], time, now);
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
	    key: "getLastItem",
	    value: function getLastItem() {
	      return this._items.length > 0 ? this._items[this._items.length - 1] : null;
	    }
	  }, {
	    key: "calculateItemIndex",
	    value: function calculateItemIndex(item) {
	      var i, length;
	      var time = item.getDeadline();

	      if (time) {
	        //Item has deadline
	        for (i = 0, length = this._items.length; i < length; i++) {
	          var curTime = this._items[i].getDeadline();

	          if (!curTime || time <= curTime) {
	            return i;
	          }
	        }
	      } else {
	        //Item has't deadline
	        var sourceId = item.getSourceId();

	        for (i = 0, length = this._items.length; i < length; i++) {
	          if (this._items[i].getDeadline()) {
	            continue;
	          }

	          if (sourceId <= this._items[i].getSourceId()) {
	            return i;
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
	    key: "getItems",
	    value: function getItems() {
	      return this._items;
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

	      for (var i = 0, length = this._items.length; i < length; i++) {
	        var item = this._items[i];

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
	    key: "getItemIndex",
	    value: function getItemIndex(item) {
	      for (var i = 0, l = this._items.length; i < l; i++) {
	        if (this._items[i] === item) {
	          return i;
	        }
	      }

	      return -1;
	    }
	  }, {
	    key: "getItemByIndex",
	    value: function getItemByIndex(index) {
	      return index < this._items.length ? this._items[index] : null;
	    }
	  }, {
	    key: "removeItemByIndex",
	    value: function removeItemByIndex(index) {
	      if (index < this._items.length) {
	        this._items.splice(index, 1);
	      }
	    }
	  }, {
	    key: "createItem",
	    value: function createItem(data) {
	      var entityTypeID = BX.prop.getInteger(data, "ASSOCIATED_ENTITY_TYPE_ID", 0);
	      var entityID = BX.prop.getInteger(data, "ASSOCIATED_ENTITY_ID", 0);
	      var entityData = BX.prop.getObject(data, "ASSOCIATED_ENTITY", {});
	      var itemId = BX.CrmEntityType.resolveName(entityTypeID) + "_" + entityID.toString();

	      if (entityTypeID === BX.CrmEntityType.enumeration.wait) {
	        return Wait$1.create(itemId, {
	          schedule: this,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else // if(entityTypeID === BX.CrmEntityType.enumeration.activity)
	        {
	          var typeId = BX.prop.getInteger(entityData, "TYPE_ID", 0);
	          var providerId = BX.prop.getString(entityData, "PROVIDER_ID", "");

	          if (typeId === BX.CrmActivityType.email) {
	            return Email$1.create(itemId, {
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
	              return Rest$1.create(itemId, {
	                schedule: this,
	                container: this._wrapper,
	                activityEditor: this._activityEditor,
	                data: data
	              });
	            } else if (providerId === 'CRM_DELIVERY') {
	              return Activity$1.create(itemId, {
	                schedule: this,
	                container: this._wrapper,
	                activityEditor: this._activityEditor,
	                data: data,
	                vueComponent: BX.Crm.Timeline.DeliveryActivity
	              });
	            } else if (providerId === 'CRM_CALL_TRACKER') {
	              return CallTracker.create(itemId, {
	                schedule: this,
	                container: this._wrapper,
	                activityEditor: this._activityEditor,
	                data: data
	              });
	            } else if (providerId === 'STORE_DOCUMENT') {
	              return StoreDocument.create(itemId, {
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
	      var index = this.getItemIndex(item);

	      if (index < 0) {
	        return;
	      }

	      item.clearLayout();
	      this.removeItemByIndex(index);
	      this.refreshLayout();

	      this._manager.processSheduleLayoutChange();
	    }
	  }, {
	    key: "refreshItem",
	    value: function refreshItem(item) {
	      var index = this.getItemIndex(item);

	      if (index < 0) {
	        return;
	      }

	      this.removeItemByIndex(index);
	      var newItem = this.createItem(item.getData());
	      var newIndex = this.calculateItemIndex(newItem);

	      if (newIndex === index) {
	        this.addItem(item, newIndex);
	        item.refreshLayout();
	        item.addWrapperClass("crm-entity-stream-section-updated", 1000);
	        return;
	      }

	      var anchor = this.createAnchor(newIndex);
	      this.addItem(newItem, newIndex);
	      newItem.layout({
	        add: false
	      });
	      var animation = Item$2.create("", {
	        initialItem: item,
	        finalItem: newItem,
	        anchor: anchor
	      });
	      animation.run();
	    }
	  }, {
	    key: "transferItemToHistory",
	    value: function transferItemToHistory(item, historyItemData) {
	      var index = this.getItemIndex(item);

	      if (index < 0) {
	        return;
	      }

	      this.removeItemByIndex(index);
	      this.refreshLayout();

	      this._manager.processSheduleLayoutChange();

	      var historyItem = this._history.createItem(historyItemData);

	      this._history.addItem(historyItem, 0);

	      historyItem.layout({
	        add: false
	      });
	      var animation = ItemNew.create("", {
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
	        var stubClassName = "crm-entity-stream-section crm-entity-stream-section-planned crm-entity-stream-section-notTask";
	        var stubIconClassName = "crm-entity-stream-section-icon crm-entity-stream-section-icon-info";
	        var stubMessage = this.getMessage("stub");

	        var ownerTypeId = this._manager.getOwnerTypeId();

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
	                  className: "crm-entity-stream-content-detail"
	                },
	                text: stubMessage
	              })]
	            })]
	          })]
	        });

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
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = Schedule.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Schedule();
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

	var Modification = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(Modification, _History);

	  function Modification() {
	    babelHelpers.classCallCheck(this, Modification);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Modification).call(this));
	  }

	  babelHelpers.createClass(Modification, [{
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = Modification.messages;
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
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-info"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info"
	        }
	      }));
	      var content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      var header = this.prepareHeaderLayout();
	      var contentChildren = [];

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
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        content.appendChild(authorNode);
	      } //endregion


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
	      var self = new Modification();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Modification;
	}(History);

	babelHelpers.defineProperty(Modification, "messages", {});

	/** @memberof BX.Crm.Timeline.Actions */

	var Conversion = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(Conversion, _History);

	  function Conversion() {
	    babelHelpers.classCallCheck(this, Conversion);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Conversion).call(this));
	  }

	  babelHelpers.createClass(Conversion, [{
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = Conversion.messages;
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
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-convert crm-entity-stream-section-history"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-convert"
	        }
	      }));
	      var content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      var header = this.prepareHeaderLayout();
	      content.appendChild(header);
	      var entityNodes = [];
	      var entityInfos = this.getArrayDataParam("ENTITIES");
	      var i = 0;
	      var length = entityInfos.length;

	      for (; i < length; i++) {
	        var entityInfo = entityInfos[i];
	        var entityNode = void 0;

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
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        content.appendChild(authorNode);
	      } //endregion


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
	      var self = new Conversion();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Conversion;
	}(History);

	babelHelpers.defineProperty(Conversion, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */

	var Email$2 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Email$$1, _HistoryActivity);

	  function Email$$1() {
	    babelHelpers.classCallCheck(this, Email$$1);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Email$$1).call(this));
	  }

	  babelHelpers.createClass(Email$$1, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      var entityData = this.getAssociatedEntityData();
	      var emailInfo = BX.prop.getObject(entityData, "EMAIL_INFO", null);
	      var statusText = emailInfo !== null ? BX.prop.getString(emailInfo, "STATUS_TEXT", "") : "";
	      var error = emailInfo !== null ? BX.prop.getBoolean(emailInfo, "STATUS_ERROR", false) : false;
	      var className = !error ? "crm-entity-stream-content-event-skipped" : "crm-entity-stream-content-event-missing";

	      if (statusText !== "") {
	        header.appendChild(BX.create("SPAN", {
	          props: {
	            className: className
	          },
	          text: statusText
	        }));
	      }

	      var markNode = this.prepareMarkLayout();

	      if (markNode) {
	        header.appendChild(markNode);
	      }

	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareContextMenuItems",
	    value: function prepareContextMenuItems() {
	      var menuItems = [];

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
	      var title = BX.util.htmlspecialchars(this.getTitle());
	      return this.getMessage('emailRemove').replace("#TITLE#", title);
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      var entityData = this.getAssociatedEntityData();
	      var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");

	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }

	      var communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      var communicationTitle = BX.prop.getString(communication, "TITLE", "");
	      var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      var communicationValue = BX.prop.getString(communication, "VALUE", "");
	      var outerWrapper = BX.create("DIV", {
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
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      outerWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [wrapper]
	      })); //Header

	      var header = this.prepareHeaderLayout();
	      wrapper.appendChild(header); //region Context Menu

	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      } //endregion
	      //Details


	      var detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-email"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: [detailWrapper]
	      })); //TODO: Add status text

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
	      var communicationWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-email-to"
	        }
	      });
	      detailWrapper.appendChild(communicationWrapper); //Communications

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
	      } //Content


	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-email-fragment"
	        },
	        children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        wrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      wrapper.appendChild(this._actionContainer); //endregion

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
	      var self = new Email$$1();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Email$$1;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Actions */

	var Call$1 = /*#__PURE__*/function (_Activity) {
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

	      var phone = "";
	      var itemData = this.getItemData();
	      var phones = BX.prop.getArray(itemData, "PHONE", []);

	      if (phones.length === 1) {
	        this.addCall(phones[0]['VALUE']);
	      } else if (phones.length > 1) {
	        this.showMenu();
	      } else {
	        var communication = BX.prop.getObject(this._entityData, "COMMUNICATION", null);

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

	      var itemData = this.getItemData();
	      var phones = BX.prop.getArray(itemData, "PHONE", []);
	      var handler = BX.delegate(this.onMenuItemClick, this);
	      this._menuItems = [];

	      if (phones.length === 0) {
	        return;
	      }

	      var i = 0;
	      var l = phones.length;

	      for (; i < l; i++) {
	        var value = BX.prop.getString(phones[i], "VALUE");
	        var formattedValue = BX.prop.getString(phones[i], "VALUE_FORMATTED");
	        var complexName = BX.prop.getString(phones[i], "COMPLEX_NAME");
	        var itemText = (complexName ? complexName + ': ' : '') + (formattedValue ? formattedValue : value);

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
	      var communication = BX.prop.getObject(this._entityData, "COMMUNICATION", null);
	      var entityTypeId = parseInt(BX.prop.getString(communication, "ENTITY_TYPE_ID", "0"));

	      if (isNaN(entityTypeId)) {
	        entityTypeId = 0;
	      }

	      var entityId = parseInt(BX.prop.getString(communication, "ENTITY_ID", "0"));

	      if (isNaN(entityId)) {
	        entityId = 0;
	      }

	      var ownerTypeId = 0;
	      var ownerId = 0;
	      var ownerInfo = BX.prop.getObject(this._settings, "ownerInfo");

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

	      var activityId = parseInt(BX.prop.getString(this._entityData, "ID", "0"));

	      if (isNaN(activityId)) {
	        activityId = 0;
	      }

	      var params = {
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
	      var m = Call.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }]);
	  return Call;
	}(Activity);
	/** @memberof BX.Crm.Timeline.Actions */

	babelHelpers.defineProperty(Call$1, "messages", {});
	var HistoryCall = /*#__PURE__*/function (_Call) {
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
	      var self = new HistoryCall();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return HistoryCall;
	}(Call$1);
	var ScheduleCall = /*#__PURE__*/function (_Call2) {
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
	      var self = new ScheduleCall();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return ScheduleCall;
	}(Call$1);

	/** @memberof BX.Crm.Timeline.Items */

	var Call$2 = /*#__PURE__*/function (_HistoryActivity) {
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
	      var entityData = this.getAssociatedEntityData();
	      var callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);
	      var callTypeText = callInfo !== null ? BX.prop.getString(callInfo, "CALL_TYPE_TEXT", "") : "";

	      if (callTypeText !== "") {
	        return callTypeText;
	      }

	      var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	      return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall");
	    }
	  }, {
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout()); //Position is important

	      var entityData = this.getAssociatedEntityData();
	      var callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);
	      var hasCallInfo = callInfo !== null;
	      var isSuccessfull = hasCallInfo ? BX.prop.getBoolean(callInfo, "SUCCESSFUL", false) : false;
	      var statusText = hasCallInfo ? BX.prop.getString(callInfo, "STATUS_TEXT", "") : "";

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
	      var entityData = this.getAssociatedEntityData();
	      var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");

	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }

	      var communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      var communicationTitle = BX.prop.getString(communication, "TITLE", "");
	      var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      var communicationValue = BX.prop.getString(communication, "VALUE", "");
	      var communicationValueFormatted = BX.prop.getString(communication, "FORMATTED_VALUE", communicationValue);
	      var callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);
	      var hasCallInfo = callInfo !== null;
	      var durationText = hasCallInfo ? BX.prop.getString(callInfo, "DURATION_TEXT", "") : "";
	      var hasTranscript = hasCallInfo ? BX.prop.getBoolean(callInfo, "HAS_TRANSCRIPT", "") : "";
	      var isTranscriptPending = hasCallInfo ? BX.prop.getBoolean(callInfo, "TRANSCRIPT_PENDING", "") : "";
	      var callId = hasCallInfo ? BX.prop.getString(callInfo, "CALL_ID", "") : "";
	      var callComment = hasCallInfo ? BX.prop.getString(callInfo, "COMMENT", "") : "";
	      var outerWrapper = BX.create("DIV", {
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
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      outerWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [wrapper]
	      })); //Header

	      var header = this.prepareHeaderLayout();
	      wrapper.appendChild(header); //region Context Menu

	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      } //endregion
	      //Details


	      var detailWrapper = BX.create("DIV", {
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
	      })); //Content

	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        children: this.prepareMultilineCutOffElements(description, 128, this._headerClickHandler)
	      }));

	      if (hasCallInfo) {
	        var callInfoWrapper = BX.create("DIV", {
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
	              click: function click(e) {
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

	      var communicationWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-contact-info"
	        }
	      });
	      detailWrapper.appendChild(communicationWrapper); //Communications

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
	      } //region Author


	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        wrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      wrapper.appendChild(this._actionContainer); //endregion

	      if (!this.isReadOnly()) wrapper.appendChild(this.prepareFixedSwitcherLayout());
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
	      var entityData = this.getAssociatedEntityData();
	      var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	      var messageName = direction === BX.CrmActivityDirection.incoming ? 'incomingCallRemove' : 'outgoingCallRemove';
	      var title = BX.util.htmlspecialchars(this.getTitle());
	      return this.getMessage(messageName).replace("#TITLE#", title);
	    }
	  }, {
	    key: "onPlayerDummyClick",
	    value: function onPlayerDummyClick(e) {
	      var stubNode = this._playerWrapper.querySelector(".crm-audio-cap-wrap");

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
	      var self = new Call();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Call;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */

	var Meeting$1 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Meeting, _HistoryActivity);

	  function Meeting() {
	    babelHelpers.classCallCheck(this, Meeting);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Meeting).call(this));
	  }

	  babelHelpers.createClass(Meeting, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      var markNode = this.prepareMarkLayout();

	      if (markNode) {
	        header.appendChild(markNode);
	      }

	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      var entityData = this.getAssociatedEntityData();
	      var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");

	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }

	      var communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      var communicationTitle = BX.prop.getString(communication, "TITLE", "");
	      var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      var communicationValue = BX.prop.getString(communication, "VALUE", "");
	      var wrapper = BX.create("DIV", {
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
	      var contentWrapper = BX.create("DIV", {
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
	      var header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      var detailWrapper = BX.create("DIV", {
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
	      })); //Content

	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
	      }));
	      var communicationWrapper = BX.create("DIV", {
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
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer); //endregion

	      if (!this.isReadOnly()) contentWrapper.appendChild(this.prepareFixedSwitcherLayout());
	      return wrapper;
	    }
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      var title = BX.util.htmlspecialchars(this.getTitle());
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
	      var self = new Meeting();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Meeting;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */

	var Task$1 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Task, _HistoryActivity);

	  function Task() {
	    babelHelpers.classCallCheck(this, Task);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Task).call(this));
	  }

	  babelHelpers.createClass(Task, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      var markNode = this.prepareMarkLayout();

	      if (markNode) {
	        header.appendChild(markNode);
	      }

	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      var entityData = this.getAssociatedEntityData();
	      var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");

	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }

	      var communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      var communicationTitle = BX.prop.getString(communication, "TITLE", "");
	      var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      var wrapper = BX.create("DIV", {
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
	      var contentWrapper = BX.create("DIV", {
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
	      var header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      var detailWrapper = BX.create("DIV", {
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
	      })); //Content

	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
	      }));
	      var communicationWrapper = BX.create("DIV", {
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
	      } //region Author


	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer); //endregion

	      if (!this.isReadOnly()) contentWrapper.appendChild(this.prepareFixedSwitcherLayout());
	      return wrapper;
	    }
	  }, {
	    key: "prepareActions",
	    value: function prepareActions() {}
	  }, {
	    key: "getRemoveMessage",
	    value: function getRemoveMessage() {
	      var title = BX.util.htmlspecialchars(this.getTitle());
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
	      var self = new Task();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Task;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */

	var WebForm$1 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(WebForm, _HistoryActivity);

	  function WebForm() {
	    babelHelpers.classCallCheck(this, WebForm);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(WebForm).call(this));
	  }

	  babelHelpers.createClass(WebForm, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      var header = BX.create("DIV", {
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
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-crmForm"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-crmForm"
	        }
	      }));
	      var contentWrapper = BX.create("DIV", {
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
	      var header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      var detailWrapper = BX.create("DIV", {
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
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer); //endregion

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
	      var self = new WebForm();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return WebForm;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */

	var Sms$1 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Sms, _HistoryActivity);

	  function Sms() {
	    babelHelpers.classCallCheck(this, Sms);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Sms).call(this));
	  }

	  babelHelpers.createClass(Sms, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      header.appendChild(this.prepareMessageStatusLayout());
	      header.appendChild(this.prepareTimeLayout());
	      return header;
	    }
	  }, {
	    key: "prepareMessageStatusLayout",
	    value: function prepareMessageStatusLayout() {
	      return this._messageStatusNode = BX.create("SPAN");
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      var entityData = this.getAssociatedEntityData();
	      var communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      var communicationTitle = BX.prop.getString(communication, "TITLE", "");
	      var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      var communicationValue = BX.prop.getString(communication, "VALUE", "");
	      var smsInfo = BX.prop.getObject(entityData, "SMS_INFO", {});
	      var wrapperClassName = "crm-entity-stream-section-sms";
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history" + " " + wrapperClassName
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-sms"
	        }
	      }));
	      if (this.isFixed()) BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
	      var contentWrapper = BX.create("DIV", {
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
	      var header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      var detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        }
	      });
	      contentWrapper.appendChild(detailWrapper);
	      var messageWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-sms"
	        }
	      });

	      if (smsInfo.senderId) {
	        var senderId = smsInfo.senderId;
	        var senderName = smsInfo.senderShortName;

	        if (senderId === 'rest' && smsInfo.fromName) {
	          senderName = smsInfo.fromName;
	        }

	        var messageSenderWrapper = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-sms-status"
	          },
	          children: [BX.message('CRM_TIMELINE_SMS_SENDER') + ' ', BX.create('STRONG', {
	            text: senderName
	          })]
	        });

	        if (senderId !== 'rest' && smsInfo.fromName) {
	          messageSenderWrapper.innerHTML += ' ' + BX.message('CRM_TIMELINE_SMS_FROM') + ' ';
	          messageSenderWrapper.appendChild(BX.create('STRONG', {
	            text: smsInfo.fromName
	          }));
	        }

	        messageWrapper.appendChild(messageSenderWrapper);
	      }

	      if (smsInfo.statusId !== '') {
	        this.setMessageStatus(smsInfo.statusId, smsInfo.errorText);
	      }

	      var bodyText = BX.util.htmlspecialchars(entityData['DESCRIPTION_RAW']).replace(/\r\n|\r|\n/g, "<br/>");
	      var messageBodyWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-sms-fragment"
	        }
	      });
	      messageBodyWrapper.appendChild(BX.create('SPAN', {
	        html: bodyText
	      }));
	      messageWrapper.appendChild(messageBodyWrapper);
	      detailWrapper.appendChild(messageWrapper);
	      var communicationWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-contact-info"
	        },
	        text: BX.message('CRM_TIMELINE_SMS_TO') + ' '
	      });
	      detailWrapper.appendChild(communicationWrapper);

	      if (communicationTitle !== '') {
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
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer); //endregion

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
	    key: "setMessageStatus",
	    value: function setMessageStatus(status, errorText) {
	      status = parseInt(status);
	      if (isNaN(status) || !this._messageStatusNode) return;
	      var statuses = this.getSetting('smsStatusDescriptions', {});

	      if (statuses.hasOwnProperty(status)) {
	        this._messageStatusNode.textContent = statuses[status];
	        this.setMessageStatusErrorText(errorText);
	        var statusSemantic = this.getMessageStatusSemantic(status);
	        this.setMessageStatusSemantic(statusSemantic);
	      }
	    }
	  }, {
	    key: "setMessageStatusSemantic",
	    value: function setMessageStatusSemantic(semantic) {
	      var classMap = {
	        process: 'crm-entity-stream-content-event-process',
	        success: 'crm-entity-stream-content-event-successful',
	        failure: 'crm-entity-stream-content-event-missing'
	      };

	      for (var checkSemantic in classMap) {
	        var fn = checkSemantic === semantic ? 'addClass' : 'removeClass';
	        BX[fn](this._messageStatusNode, classMap[checkSemantic]);
	      }
	    }
	  }, {
	    key: "setMessageStatusErrorText",
	    value: function setMessageStatusErrorText(errorText) {
	      if (!errorText) {
	        this._messageStatusNode.removeAttribute('title');

	        BX.removeClass(this._messageStatusNode, 'crm-entity-stream-content-event-error-tip');
	      } else {
	        this._messageStatusNode.setAttribute('title', errorText);

	        BX.addClass(this._messageStatusNode, 'crm-entity-stream-content-event-error-tip');
	      }
	    }
	  }, {
	    key: "getMessageStatusSemantic",
	    value: function getMessageStatusSemantic(status) {
	      var semantics = this.getSetting('smsStatusSemantics', {});
	      return semantics.hasOwnProperty(status) ? semantics[status] : 'failure';
	    }
	  }, {
	    key: "subscribe",
	    value: function subscribe() {
	      if (!BX.CrmSmsWatcher) return;
	      var entityData = this.getAssociatedEntityData();
	      var smsInfo = BX.prop.getObject(entityData, "SMS_INFO", {});

	      if (smsInfo.id) {
	        BX.CrmSmsWatcher.subscribeOnMessageUpdate(smsInfo.id, this.onMessageUpdate.bind(this));
	      }
	    }
	  }, {
	    key: "onMessageUpdate",
	    value: function onMessageUpdate(message) {
	      if (message.STATUS_ID) {
	        this.setMessageStatus(message.STATUS_ID, message.EXEC_ERROR);
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Sms();
	      self.initialize(id, settings);
	      self.subscribe();
	      return self;
	    }
	  }]);
	  return Sms;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */

	var Request$1 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Request, _HistoryActivity);

	  function Request() {
	    babelHelpers.classCallCheck(this, Request);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Request).call(this));
	  }

	  babelHelpers.createClass(Request, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      var header = BX.create("DIV", {
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
	      var entityData = this.getAssociatedEntityData();
	      var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");

	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      } //var entityData = this.getAssociatedEntityData();


	      var wrapper = BX.create("DIV", {
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
	      var contentWrapper = BX.create("DIV", {
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
	      var header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      var detailWrapper = BX.create("DIV", {
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
	      })); //Content

	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer); //endregion

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
	      var self = new Request();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Request;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */

	var OpenLine$2 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(OpenLine$$1, _HistoryActivity);

	  function OpenLine$$1() {
	    babelHelpers.classCallCheck(this, OpenLine$$1);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OpenLine$$1).call(this));
	  }

	  babelHelpers.createClass(OpenLine$$1, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      var header = BX.create("DIV", {
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
	      var entityData = this.getAssociatedEntityData();
	      var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");

	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      }

	      var communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      var communicationTitle = BX.prop.getString(communication, "TITLE", "");
	      var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      var wrapper = BX.create("DIV", {
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
	      var contentWrapper = BX.create("DIV", {
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
	      var header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      var detailWrapper = BX.create("DIV", {
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
	      })); //Content

	      var entityDetailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-IM"
	        }
	      });
	      detailWrapper.appendChild(entityDetailWrapper);
	      var messageWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-IM-messages"
	        }
	      });
	      entityDetailWrapper.appendChild(messageWrapper);
	      var openLineData = BX.prop.getObject(this.getAssociatedEntityData(), "OPENLINE_INFO", null);

	      if (openLineData) {
	        var messages = BX.prop.getArray(openLineData, "MESSAGES", []);
	        var i = 0;
	        var length = messages.length;

	        for (; i < length; i++) {
	          var message = messages[i];
	          var isExternal = BX.prop.getBoolean(message, "IS_EXTERNAL", true);
	          messageWrapper.appendChild(BX.create("DIV", {
	            attrs: {
	              className: isExternal ? "crm-entity-stream-content-detail-IM-message-incoming" : "crm-entity-stream-content-detail-IM-message-outgoing"
	            },
	            html: BX.prop.getString(message, "MESSAGE", "")
	          }));
	        }
	      }

	      var communicationWrapper = BX.create("DIV", {
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
	      } //region Author


	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer); //endregion

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

	      var slug = "";
	      var communication = BX.prop.getObject(this.getAssociatedEntityData(), "COMMUNICATION", null);

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
	      var self = new OpenLine$$1();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return OpenLine$$1;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */

	var Rest$2 = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Rest, _HistoryActivity);

	  function Rest() {
	    babelHelpers.classCallCheck(this, Rest);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Rest).call(this));
	  }

	  babelHelpers.createClass(Rest, [{
	    key: "prepareHeaderLayout",
	    value: function prepareHeaderLayout() {
	      var header = BX.create("DIV", {
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
	      var entityData = this.getAssociatedEntityData();

	      if (entityData['APP_TYPE'] && entityData['APP_TYPE']['NAME']) {
	        return entityData['APP_TYPE']['NAME'];
	      }

	      return babelHelpers.get(babelHelpers.getPrototypeOf(Rest.prototype), "getTypeDescription", this).call(this);
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      var entityData = this.getAssociatedEntityData();
	      var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");

	      if (description !== "") {
	        //trim leading spaces
	        description = description.replace(/^\s+/, '');
	      } //var entityData = this.getAssociatedEntityData();


	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-today crm-entity-stream-section-rest"
	        }
	      });
	      var iconNode = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-rest"
	        }
	      });
	      wrapper.appendChild(iconNode);

	      if (entityData['APP_TYPE'] && entityData['APP_TYPE']['ICON_SRC']) {
	        if (iconNode) {
	          iconNode.style.backgroundImage = "url('" + entityData['APP_TYPE']['ICON_SRC'] + "')";
	          iconNode.style.backgroundPosition = "center center";
	        }
	      }

	      if (this.isFixed()) BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
	      var contentWrapper = BX.create("DIV", {
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
	      var header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      var detailWrapper = BX.create("DIV", {
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
	      })); //Content

	      detailWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer); //endregion

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
	      var self = new Rest();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Rest;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Actions */

	var Visit = /*#__PURE__*/function (_HistoryActivity) {
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
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        }
	      });
	      header.appendChild(this.prepareTitleLayout());
	      var entityData = this.getAssociatedEntityData();
	      var visitInfo = BX.prop.getObject(entityData, "VISIT_INFO", {});
	      var recordLength = BX.prop.getInteger(visitInfo, "RECORD_LENGTH", 0);
	      var recordLengthFormatted = BX.prop.getString(visitInfo, "RECORD_LENGTH_FORMATTED_FULL", "");
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
	      var entityData = this.getAssociatedEntityData();
	      var communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
	      var communicationTitle = BX.prop.getString(communication, "TITLE", "");
	      var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
	      var visitInfo = BX.prop.getObject(entityData, "VISIT_INFO", {});
	      var recordLength = BX.prop.getInteger(visitInfo, "RECORD_LENGTH", 0);
	      var recordLengthFormatted = BX.prop.getString(visitInfo, "RECORD_LENGTH_FORMATTED_SHORT", "");
	      var vkProfile = BX.prop.getString(visitInfo, "VK_PROFILE", "");
	      var outerWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-visit"
	        }
	      });
	      outerWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-visit"
	        }
	      }));
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      outerWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [wrapper]
	      })); //Header

	      var header = this.prepareHeaderLayout();
	      wrapper.appendChild(header); //region Context Menu

	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      } //endregion
	      //Details


	      var detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail crm-entity-stream-content-detail-call-inline"
	        }
	      });
	      wrapper.appendChild(detailWrapper);
	      this._mediaFileInfo = BX.prop.getObject(entityData, "MEDIA_FILE_INFO", null);

	      if (this._mediaFileInfo !== null && recordLength > 0) {
	        this._playerWrapper = this._history.getManager().renderAudioDummy(recordLengthFormatted, this._playerDummyClickHandler);
	        detailWrapper.appendChild( //crm-entity-stream-content-detail-call
	        this._playerWrapper);
	        detailWrapper.appendChild(this._history.getManager().getAudioPlaybackRateSelector().render());
	      }

	      var communicationWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-contact-info"
	        }
	      });
	      wrapper.appendChild(communicationWrapper); //Communications

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
	      } //region Author


	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        wrapper.appendChild(authorNode);
	      } //endregion


	      return outerWrapper;
	    }
	  }, {
	    key: "onPlayerDummyClick",
	    value: function onPlayerDummyClick(e) {
	      var stubNode = this._playerWrapper.querySelector(".crm-audio-cap-wrap");

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
	      var self = new Visit();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Visit;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Actions */

	var Zoom$1 = /*#__PURE__*/function (_HistoryActivity) {
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
	      var header = BX.create("DIV", {
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
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history"
	        }
	      });
	      var entityDetailWrapper;
	      var zoomData = BX.prop.getObject(this.getAssociatedEntityData(), "ZOOM_INFO", null);
	      var subject = BX.prop.getString(this.getAssociatedEntityData(), "SUBJECT", null);
	      this._recordings = BX.prop.getArray(zoomData, "RECORDINGS", []);
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-zoom"
	        }
	      }));
	      if (this.isFixed()) BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
	      var contentWrapper = BX.create("DIV", {
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
	      var header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      var detailWrapper = BX.create("DIV", {
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
	            var tabs = this._recordings.map(function (recording, index) {
	              return {
	                id: index,
	                title: BX.message("CRM_TIMELINE_ZOOM_MEETING_RECORD_PART").replace("#NUMBER#", index + 1),
	                time: recording["AUDIO"] ? recording["AUDIO"]["LENGTH_FORMATTED"] : "",
	                active: index === 0
	              };
	            });

	            var tabsComponent = new Zoom.TabsComponent({
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
	            var videoLinkExpireTS = zoomData['RECORDINGS'][0]['VIDEO']['END_DATE_TS'] * 1000 + 60 * 60 * 23 * 1000;

	            if (videoLinkExpireTS < Date.now()) {
	              var videoLinkContainer = BX.create("DIV", {
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
	            var zoomAudioDetailWrapper = BX.create("DIV", {
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
	      } //region Author


	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion


	      return wrapper;
	    }
	  }, {
	    key: "_onVideoDummyClick",
	    value: function _onVideoDummyClick() {
	      BX.UI.Hint.hide();
	      var recording = this._recordings[this._currentRecordingIndex]["VIDEO"];

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
	      var recording = this._recordings[this._currentRecordingIndex]["AUDIO"];

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
	      var videoRecording = this._recordings[this._currentRecordingIndex]["VIDEO"];
	      var audioRecording = this._recordings[this._currentRecordingIndex]["AUDIO"];

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
	        var lengthHuman = audioRecording ? audioRecording["LENGTH_HUMAN"] : videoRecording["LENGTH_HUMAN"];

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
	      var self = new Zoom();
	      self.initialize(id, settings); //todo: remove debug

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
	      var tabId = tabDescription.id;
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

	      for (var id in this.elements.tabs) {
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

	var OrderCreation = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(OrderCreation, _History);

	  function OrderCreation() {
	    babelHelpers.classCallCheck(this, OrderCreation);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OrderCreation).call(this));
	  }

	  babelHelpers.createClass(OrderCreation, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(OrderCreation.prototype), "doInitialize", this).call(this);

	      if (!(this._activityEditor instanceof BX.CrmActivityEditor)) {
	        throw "OrderCreation. The field 'activityEditor' is not assigned.";
	      }
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      var msg = this.getMessage(BX.CrmEntityType.resolveName(this.getAssociatedEntityTypeId()).toLowerCase());

	      if (!BX.type.isNotEmptyString(msg)) {
	        msg = this.getTextDataParam("TITLE");
	      }

	      return msg;
	    }
	  }, {
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "crm-entity-stream-section-createOrderEntity";
	    }
	  }, {
	    key: "getHeaderChildren",
	    value: function getHeaderChildren() {
	      var statusMessage = '';
	      var statusClass = '';
	      var fields = this.getObjectDataParam('FIELDS');

	      if (BX.prop.get(fields, 'DONE') === 'Y') {
	        statusMessage = this.getMessage("done");
	        statusClass = "crm-entity-stream-content-event-done";
	      } else if (BX.prop.get(fields, 'CANCELED') === 'Y') {
	        statusMessage = this.getMessage("canceled");
	        statusClass = "crm-entity-stream-content-event-canceled";
	      } else {
	        if (BX.prop.get(fields, 'PAID') === 'Y') {
	          statusMessage = this.getMessage("paid");
	          statusClass = "crm-entity-stream-content-event-paid";
	        } else if (BX.prop.get(fields, 'PAID') === 'N') {
	          statusMessage = this.getMessage("unpaid");
	          statusClass = "crm-entity-stream-content-event-not-paid";
	        }
	      }

	      return [BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event-title"
	        },
	        events: {
	          click: this._headerClickHandler
	        },
	        text: this.getTitle()
	      }), BX.create("SPAN", {
	        attrs: {
	          className: statusClass
	        },
	        text: statusMessage
	      }), BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: this.formatTime(this.getCreatedTime())
	      })];
	    }
	  }, {
	    key: "prepareContentDetails",
	    value: function prepareContentDetails() {
	      var entityData = this.getAssociatedEntityData();
	      var entityTypeId = this.getAssociatedEntityTypeId();
	      var entityId = this.getAssociatedEntityId();
	      var title = BX.util.htmlspecialchars(BX.prop.getString(entityData, "TITLE", ""));
	      var showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
	      var legend = BX.prop.getString(entityData, "LEGEND", "");

	      if (legend !== "") {
	        title += " " + legend;
	      }

	      var nodes = [];

	      if (title !== "") {
	        if (showUrl === "" || entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId()) {
	          nodes.push(BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-content-detail-description"
	            },
	            html: title
	          }));
	        } else {
	          nodes.push(BX.create("DIV", {
	            attrs: {
	              className: "crm-entity-stream-content-detail-description"
	            },
	            html: title
	          }));
	          nodes.push(BX.create("A", {
	            attrs: {
	              href: showUrl
	            },
	            text: this.getMessage('urlOrderLink')
	          }));
	        }
	      }

	      return nodes;
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-store";
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      var wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history";
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: wrapperClassName
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: this.getIconClassName()
	        }
	      }));
	      var contentWrapper = BX.create("DIV", {
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
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: this.getHeaderChildren()
	      });
	      contentWrapper.appendChild(header);
	      contentWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: this.prepareContentDetails()
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion


	      return wrapper;
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = OrderCreation.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new OrderCreation();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return OrderCreation;
	}(History);

	babelHelpers.defineProperty(OrderCreation, "messages", {});

	/** @memberof BX.Crm.Timeline.Actions */

	var OrderModification = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(OrderModification, _History);

	  function OrderModification() {
	    babelHelpers.classCallCheck(this, OrderModification);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OrderModification).call(this));
	  }

	  babelHelpers.createClass(OrderModification, [{
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = OrderModification.messages;
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
	      var statusInfo = {};
	      var value = null;
	      var classCode = null;
	      var fieldName = this.getTextDataParam("CHANGED_ENTITY");
	      var fields = this.getObjectDataParam('FIELDS');
	      var entityData = this.getAssociatedEntityData();

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
	        var psStatusCode = BX.prop.get(fields, 'STATUS_CODE', false);

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
	      var children = [BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event-title"
	        },
	        events: {
	          click: this._headerClickHandler
	        },
	        text: this.getTitle()
	      })];
	      var statusInfo = this.getStatusInfo();

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
	      var entityData = this.getAssociatedEntityData();
	      var entityTypeId = this.getAssociatedEntityTypeId();
	      var entityId = this.getAssociatedEntityId();
	      var title = BX.prop.getString(entityData, "TITLE");
	      var htmlTitle = BX.prop.getString(entityData, "HTML_TITLE", "");
	      var showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
	      var nodes = [];

	      if (title !== "") {
	        var descriptionNode = BX.create("DIV", {
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

	        var legend = BX.prop.getString(entityData, "LEGEND");

	        if (legend !== "") {
	          descriptionNode.appendChild(BX.create("SPAN", {
	            html: " " + legend
	          }));
	        }

	        var sublegend = BX.prop.getString(entityData, "SUBLEGEND", '');

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
	      var entityData = this.getAssociatedEntityData();
	      var entityTypeId = this.getAssociatedEntityTypeId();
	      var entityId = this.getAssociatedEntityId();
	      var title = BX.prop.getString(entityData, "TITLE");
	      var showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
	      var nodes = [];

	      if (title !== "") {
	        var sublegend = BX.prop.getString(entityData, "SUBLEGEND", '');

	        if (sublegend !== "") {
	          var descriptionNode = BX.create("DIV", {
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

	        var legend = BX.prop.getString(entityData, "LEGEND");

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
	      var entityData = this.getAssociatedEntityData();
	      var entityTypeId = this.getAssociatedEntityTypeId();
	      var entityId = this.getAssociatedEntityId();
	      var title = BX.prop.getString(entityData, "TITLE");
	      var showUrl = BX.prop.getString(entityData, 'SHOW_URL', '');
	      var destination = BX.prop.getString(entityData, 'DESTINATION_TITLE', '');
	      var nodes = [];

	      if (title !== "") {
	        var detailNode = BX.create('DIV', {
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

	        var legend = BX.prop.getString(entityData, "LEGEND");

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
	        var sliderLinkNode = BX.create('A', {
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
	        var fields = this.getObjectDataParam('FIELDS'),
	            ownerTypeId = BX.prop.get(fields, 'OWNER_TYPE_ID', BX.CrmEntityType.enumeration.deal);
	        var ownerId = BX.prop.get(fields, 'OWNER_ID', 0);
	        var paymentId = BX.prop.get(fields, 'PAYMENT_ID', 0),
	            shipmentId = BX.prop.get(fields, 'SHIPMENT_ID', 0),
	            orderId = BX.prop.get(fields, 'ORDER_ID', 0); // compatibility

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
	      var entityData = this.getAssociatedEntityData(),
	          title = BX.prop.getString(entityData, "TITLE"),
	          date = BX.prop.getString(entityData, "DATE", ""),
	          paySystemName = BX.prop.getString(entityData, "PAY_SYSTEM_NAME", ""),
	          sum = BX.prop.getString(entityData, 'SUM', ''),
	          currency$$1 = BX.prop.getString(entityData, 'CURRENCY', ''),
	          nodes = [];

	      if (title !== "") {
	        var paymentDetail = BX.create("DIV", {
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
	            html: currency$$1
	          })]
	        }));
	        var logotip = BX.prop.getString(entityData, "LOGOTIP", null);

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
	        var descriptionNode = BX.create("DIV", {
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
	      var fields = this.getObjectDataParam('FIELDS'),
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
	      var entityData = this.getAssociatedEntityData();
	      var isViewed = BX.prop.getString(entityData, "VIEWED", '') === 'Y';
	      var isSent = BX.prop.getString(entityData, "SENT", '') === 'Y';
	      var fields = this.getObjectDataParam('FIELDS');
	      var psStatusCode = BX.prop.get(fields, 'STATUS_CODE', false);
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section crm-entity-stream-section-history'
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section-icon ' + this.getIconClassName()
	        }
	      }));
	      var content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: this.getHeaderChildren()
	      });
	      var contentChildren = null;

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
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        content.appendChild(authorNode);
	      } //endregion


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
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section'
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section-icon crm-entity-stream-section-icon-wallet'
	        }
	      }));
	      var header = [BX.create("DIV", {
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
	      var content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      var headerWrap = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: header
	      });
	      var contentChildren = this.preparePaidPaymentContentDetails();
	      content.appendChild(headerWrap);
	      content.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: contentChildren
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        content.appendChild(authorNode);
	      } //endregion


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
	      var entityData = this.getAssociatedEntityData(),
	          date = BX.prop.getString(entityData, 'DATE', ''),
	          fields = this.getObjectDataParam('FIELDS'),
	          paySystemName = BX.prop.getString(fields, 'PAY_SYSTEM_NAME', ''),
	          paySystemError = BX.prop.getString(fields, 'STATUS_DESCRIPTION', ''),
	          nodes = [];
	      var descriptionNode = BX.create('DIV', {
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
	      var errorDetailNode = BX.create('DIV', {
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
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section crm-entity-stream-section-history'
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section-icon ' + this.getIconClassName()
	        }
	      }));
	      var header = [BX.create("DIV", {
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
	      var content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      var headerWrap = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: header
	      });
	      var contentChildren = this.prepareClickedPaymentContentDetails();
	      content.appendChild(headerWrap);
	      content.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: contentChildren
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        content.appendChild(authorNode);
	      } //endregion


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
	      var fields = this.getObjectDataParam('FIELDS'),
	          paySystemName = BX.prop.getString(fields, 'PAY_SYSTEM_NAME', ''),
	          nodes = [];

	      if (paySystemName !== '') {
	        var descriptionNode = BX.create("DIV", {
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
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section crm-entity-stream-section-advice'
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section-icon crm-entity-stream-section-icon-advice'
	        }
	      }));
	      var content = BX.create("DIV", {
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
	      var entityData = this.getAssociatedEntityData();
	      var showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section crm-entity-stream-section-advice'
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section-icon crm-entity-stream-section-icon-advice'
	        }
	      }));
	      var htmlTitle = this.getMessage('orderManualAddCheck').replace("#HREF#", showUrl);
	      var content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-advice-info"
	        },
	        html: htmlTitle
	      });
	      var link = BX.create("DIV", {
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
	      var self = new OrderModification();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return OrderModification;
	}(History);

	babelHelpers.defineProperty(OrderModification, "messages", {});

	/** @memberof BX.Crm.Timeline.Actions */

	var StoreDocumentCreation = /*#__PURE__*/function (_OrderCreation) {
	  babelHelpers.inherits(StoreDocumentCreation, _OrderCreation);

	  function StoreDocumentCreation() {
	    babelHelpers.classCallCheck(this, StoreDocumentCreation);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(StoreDocumentCreation).call(this));
	  }

	  babelHelpers.createClass(StoreDocumentCreation, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(StoreDocumentCreation.prototype), "doInitialize", this).call(this);

	      if (!(this._activityEditor instanceof BX.CrmActivityEditor)) {
	        throw "StoreDocumentCreation. The field 'activityEditor' is not assigned.";
	      }
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-info";
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      var entityData = this.getAssociatedEntityData();
	      var docType = BX.prop.getString(entityData, "DOC_TYPE");

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
	  }, {
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "crm-entity-stream-section-createStoreDocumentEntity";
	    }
	  }, {
	    key: "prepareContentDetails",
	    value: function prepareContentDetails() {
	      var entityData = this.getAssociatedEntityData();
	      var title = BX.prop.getString(entityData, "TITLE", "");
	      var nodes = [];

	      if (title === '') {
	        return nodes;
	      }

	      var titleNode = BX.create('span', {
	        text: title
	      });
	      var titleTemplate = BX.prop.getString(this._data, 'TITLE_TEMPLATE', '');

	      if (titleTemplate) {
	        var docType = BX.prop.getString(entityData, "DOC_TYPE");

	        if (docType === 'W') {
	          if (this.getOwnerTypeId() === BX.CrmEntityType.enumeration.deal) {
	            var documentDetailUrl = BX.prop.getString(this._data, 'DETAIL_LINK', '');
	            var documentLinkTag = '<a href="' + documentDetailUrl + '">' + title + '</a>';
	            titleNode.innerHTML = titleTemplate.replace('#TITLE#', documentLinkTag);
	          } else {
	            titleNode.innerHTML = titleTemplate.replace('#TITLE#', title);
	          }
	        } else {
	          titleNode.innerHTML = titleTemplate.replace('#TITLE#', title);
	        }
	      }

	      nodes.push(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-description"
	        },
	        children: [titleNode]
	      }));
	      return nodes;
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = StoreDocumentCreation.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new StoreDocumentCreation();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return StoreDocumentCreation;
	}(OrderCreation);

	babelHelpers.defineProperty(StoreDocumentCreation, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */

	var StoreDocumentModification = /*#__PURE__*/function (_Modification) {
	  babelHelpers.inherits(StoreDocumentModification, _Modification);

	  function StoreDocumentModification() {
	    babelHelpers.classCallCheck(this, StoreDocumentModification);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(StoreDocumentModification).call(this));
	  }

	  babelHelpers.createClass(StoreDocumentModification, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(StoreDocumentModification.prototype), "doInitialize", this).call(this);

	      if (!(this._activityEditor instanceof BX.CrmActivityEditor)) {
	        throw "StoreDocumentModification. The field 'activityEditor' is not assigned.";
	      }
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return "crm-entity-stream-section-icon crm-entity-stream-section-icon-info";
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      var error = this.getTextDataParam("ERROR");

	      if (error === 'CONDUCT') {
	        return this.getMessage('conductError');
	      }

	      var entityData = this.getAssociatedEntityData();
	      var field = this.getTextDataParam("FIELD");
	      var docType = BX.prop.getString(entityData, "DOC_TYPE");

	      if (docType === 'A') {
	        if (field === 'STATUS') {
	          return this.getMessage('arrivalDocument');
	        } else {
	          return this.getMessage('arrivalModification');
	        }
	      }

	      if (docType === 'S') {
	        if (field === 'STATUS') {
	          return this.getMessage('storeAdjustmentDocument');
	        } else {
	          return this.getMessage('storeAdjustmentModification');
	        }
	      }

	      if (docType === 'M') {
	        if (field === 'STATUS') {
	          return this.getMessage('movingDocument');
	        } else {
	          return this.getMessage('movingModification');
	        }
	      }

	      if (docType === 'D') {
	        if (field === 'STATUS') {
	          return this.getMessage('deductDocument');
	        } else {
	          return this.getMessage('deductModification');
	        }
	      }

	      if (docType === 'W') {
	        if (field === 'STATUS') {
	          return this.getMessage('shipmentDocument');
	        } else {
	          return this.getMessage('shipmentModification');
	        }
	      }

	      return '';
	    }
	  }, {
	    key: "getStatusInfo",
	    value: function getStatusInfo() {
	      var statusInfo = {};
	      var statusName = this.getTextDataParam('STATUS_TITLE');
	      var classCode = this.getTextDataParam('STATUS_CLASS');
	      {
	        statusInfo.message = statusName;
	        statusInfo.className = "crm-entity-stream-content-event-" + classCode;
	      }
	      return statusInfo;
	    }
	  }, {
	    key: "getHeaderChildren",
	    value: function getHeaderChildren() {
	      var children = [BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event-title"
	        },
	        events: {
	          click: this._headerClickHandler
	        },
	        text: this.getTitle()
	      })];
	      var statusInfo = this.getStatusInfo();

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
	    key: "prepareContent",
	    value: function prepareContent() {
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info"
	        }
	      }));
	      var content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: this.getHeaderChildren()
	      });
	      var entityData = this.getAssociatedEntityData();
	      var title = BX.prop.getString(entityData, "TITLE", "");
	      var error = this.getTextDataParam("ERROR");
	      var contentChildren = [];

	      if (error) {
	        var errorMessage = this.getTextDataParam("ERROR_MESSAGE");
	        contentChildren.push(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-description"
	          },
	          children: errorMessage
	        }));
	      } else if (title !== "") {
	        var titleNode = BX.create('span', {
	          text: title
	        });
	        var titleTemplate = BX.prop.getString(this._data, 'TITLE_TEMPLATE', '');

	        if (titleTemplate) {
	          var docType = BX.prop.getString(entityData, "DOC_TYPE");

	          if (docType === 'W') {
	            if (this.getOwnerTypeId() === BX.CrmEntityType.enumeration.deal) {
	              var documentDetailUrl = BX.prop.getString(this._data, 'DETAIL_LINK', '');
	              var documentLinkTag = '<a href="' + documentDetailUrl + '">' + title + '</a>';
	              titleNode.innerHTML = titleTemplate.replace('#TITLE#', documentLinkTag);
	            } else {
	              titleNode.innerHTML = titleTemplate.replace('#TITLE#', title);
	            }
	          } else {
	            titleNode.innerHTML = titleTemplate.replace('#TITLE#', title);
	          }
	        }

	        contentChildren.push(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail-description"
	          },
	          children: [titleNode]
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
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        content.appendChild(authorNode);
	      } //endregion


	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [content]
	      }));
	      return wrapper;
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = StoreDocumentModification.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new StoreDocumentModification();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return StoreDocumentModification;
	}(Modification);

	babelHelpers.defineProperty(StoreDocumentModification, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */

	var ExternalNoticeModification = /*#__PURE__*/function (_OrderModification) {
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
	      var self = new ExternalNoticeModification();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return ExternalNoticeModification;
	}(OrderModification);

	/** @memberof BX.Crm.Timeline.Items */

	var ExternalNoticeStatusModification = /*#__PURE__*/function (_ExternalNoticeModifi) {
	  babelHelpers.inherits(ExternalNoticeStatusModification, _ExternalNoticeModifi);

	  function ExternalNoticeStatusModification() {
	    babelHelpers.classCallCheck(this, ExternalNoticeStatusModification);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ExternalNoticeStatusModification).call(this));
	  }

	  babelHelpers.createClass(ExternalNoticeStatusModification, [{
	    key: "prepareContentDetails",
	    value: function prepareContentDetails() {
	      var nodes = [];
	      var contentChildren = [];

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
	      var self = new ExternalNoticeStatusModification();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return ExternalNoticeStatusModification;
	}(ExternalNoticeModification);

	/** @memberof BX.Crm.Timeline.Items */

	var OrderCheck = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(OrderCheck, _History);

	  function OrderCheck() {
	    babelHelpers.classCallCheck(this, OrderCheck);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OrderCheck).call(this));
	  }

	  babelHelpers.createClass(OrderCheck, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(OrderCheck.prototype), "doInitialize", this).call(this);

	      if (!(this._activityEditor instanceof BX.CrmActivityEditor)) {
	        throw "OrderCheck. The field 'activityEditor' is not assigned.";
	      }
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      var result = this.getMessage('orderCheck');
	      var checkName = this.getTextDataParam('CHECK_NAME');

	      if (checkName !== '') {
	        result += ' "' + checkName + '"';
	      }

	      return result;
	    }
	  }, {
	    key: "getWrapperClassName",
	    value: function getWrapperClassName() {
	      return "crm-entity-stream-section-createOrderEntity";
	    }
	  }, {
	    key: "getHeaderChildren",
	    value: function getHeaderChildren() {
	      var statusMessage = '';
	      var statusClass = '';
	      var title = this.getTitle();

	      if (this.getTextDataParam("SENDED") !== '') {
	        title = this.getMessage('sendedTitle');
	      } else {
	        statusMessage = this.getMessage("printed");
	        statusClass = "crm-entity-stream-content-event-successful";

	        if (this.getTextDataParam("PRINTED") !== 'Y') {
	          statusMessage = this.getMessage("unprinted");
	          statusClass = "crm-entity-stream-content-event-missing";
	        }
	      }

	      return [BX.create("DIV", {
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
	          text: title
	        })]
	      }), BX.create("SPAN", {
	        attrs: {
	          className: statusClass
	        },
	        text: statusMessage
	      }), BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: this.formatTime(this.getCreatedTime())
	      })];
	    }
	  }, {
	    key: "prepareContentDetails",
	    value: function prepareContentDetails() {
	      var entityData = this.getAssociatedEntityData();
	      var title = this.getTextDataParam("TITLE");
	      var showUrl = BX.prop.getString(entityData, "SHOW_URL", '');
	      var nodes = [];

	      if (title !== "") {
	        var isSended = this.getTextDataParam("SENDED") !== '';
	        var className = isSended ? 'crm-entity-stream-content-detail-order' : 'crm-entity-stream-content-detail-description';
	        var descriptionNode = BX.create("DIV", {
	          attrs: {
	            className: className
	          }
	        });

	        if (showUrl !== "") {
	          descriptionNode.appendChild(BX.create("A", {
	            attrs: {
	              href: showUrl
	            },
	            events: {
	              click: BX.delegate(function (e) {
	                BX.Crm.Page.openSlider(showUrl, {
	                  width: 500
	                });
	                e.preventDefault ? e.preventDefault() : e.returnValue = false;
	              }, this)
	            },
	            text: title
	          }));
	        }

	        var legend = this.getTextDataParam("LEGEND");
	        var legendNode;

	        if (legend !== "") {
	          legendNode = BX.create("SPAN", {
	            html: " " + legend
	          });
	        }

	        if (isSended) {
	          nodes.push(descriptionNode);

	          if (legendNode) {
	            nodes.push(legendNode);
	          }
	        } else {
	          if (legendNode) {
	            descriptionNode.appendChild(legendNode);
	          }

	          nodes.push(descriptionNode);
	        }
	      }

	      var checkUrl = this.getTextDataParam("CHECK_URL");

	      if (checkUrl) {
	        nodes.push(BX.create("DIV", {
	          attrs: {
	            className: 'crm-entity-stream-content-detail-payment-info'
	          },
	          children: [BX.create("A", {
	            attrs: {
	              href: checkUrl,
	              target: '_blank'
	            },
	            text: this.getMessage('urlLink')
	          })]
	        }));
	      }

	      return nodes;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      var wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-createOrderEntity";
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: wrapperClassName
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: this.getIconClassName()
	        }
	      }));
	      var contentWrapper = BX.create("DIV", {
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
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: this.getHeaderChildren()
	      });
	      contentWrapper.appendChild(header);
	      contentWrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        children: this.prepareContentDetails()
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion


	      return wrapper;
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = OrderCheck.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new OrderCheck();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return OrderCheck;
	}(History);

	babelHelpers.defineProperty(OrderCheck, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */

	var FinalSummaryDocuments = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(FinalSummaryDocuments, _History);

	  function FinalSummaryDocuments() {
	    babelHelpers.classCallCheck(this, FinalSummaryDocuments);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FinalSummaryDocuments).call(this));
	  }

	  babelHelpers.createClass(FinalSummaryDocuments, [{
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = FinalSummaryDocuments.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return this.getMessage('title');
	    }
	  }, {
	    key: "getHeaderChildren",
	    value: function getHeaderChildren() {
	      var children = [BX.create("DIV", {
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
	      children.push(BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-event-time"
	        },
	        text: this.formatTime(this.getCreatedTime())
	      }));
	      return children;
	    }
	  }, {
	    key: "createCheckBlock",
	    value: function createCheckBlock(check) {
	      var blockNode = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-notice"
	        }
	      });
	      blockNode.appendChild(BX.create("a", {
	        attrs: {
	          href: check.URL,
	          target: '_blank'
	        },
	        text: check.TITLE
	      }));
	      return blockNode;
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-payment"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section-icon ' + this.getIconClassName()
	        }
	      }));
	      var content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        }
	      });
	      var contentItem = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: this.getHeaderChildren()
	      });
	      contentItem.appendChild(header);
	      var data = this.getData();

	      if (data.RESULT) {
	        var summaryOptions = {
	          'OWNER_ID': data.ASSOCIATED_ENTITY_ID,
	          'OWNER_TYPE_ID': data.ASSOCIATED_ENTITY_TYPE_ID,
	          'PARENT_CONTEXT': this,
	          'CONTEXT': BX.CrmEntityType.resolveName(data.ASSOCIATED_ENTITY_TYPE_ID).toLowerCase(),
	          'IS_WITH_ORDERS_MODE': false
	        };
	        var timelineSummaryDocuments = new BX.Crm.TimelineSummaryDocuments(summaryOptions);
	        var options = data.RESULT.TIMELINE_SUMMARY_OPTIONS;
	        timelineSummaryDocuments.setOptions(options);
	        var nodes = [timelineSummaryDocuments.render()];
	        contentItem.appendChild(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail"
	          },
	          children: nodes
	        }));
	        content.appendChild(contentItem);
	      } //region Author


	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        content.appendChild(authorNode);
	      } //endregion


	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        },
	        children: [content]
	      }));
	      return wrapper;
	    }
	  }, {
	    key: "getIconClassName",
	    value: function getIconClassName() {
	      return 'crm-entity-stream-section-icon-complete';
	    }
	  }, {
	    key: "startSalescenterApplication",
	    value: function startSalescenterApplication(orderId, options) {
	      if (options === undefined) {
	        return;
	      }

	      BX.loadExt('salescenter.manager').then(function () {
	        BX.Salescenter.Manager.openApplication(options);
	      }.bind(this));
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new FinalSummaryDocuments();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return FinalSummaryDocuments;
	}(History);

	babelHelpers.defineProperty(FinalSummaryDocuments, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */

	var FinalSummary = /*#__PURE__*/function (_FinalSummaryDocument) {
	  babelHelpers.inherits(FinalSummary, _FinalSummaryDocument);

	  function FinalSummary() {
	    babelHelpers.classCallCheck(this, FinalSummary);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FinalSummary).call(this));
	  }

	  babelHelpers.createClass(FinalSummary, [{
	    key: "prepareContent",
	    value: function prepareContent() {
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-payment"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: 'crm-entity-stream-section-icon ' + this.getIconClassName()
	        }
	      }));
	      var content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-content"
	        }
	      });
	      var contentItem = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      var header = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-header"
	        },
	        children: this.getHeaderChildren()
	      });
	      contentItem.appendChild(header);
	      var data = this.getData();

	      if (data.RESULT) {
	        var summaryOptions = {
	          'OWNER_ID': data.ASSOCIATED_ENTITY_ID,
	          'OWNER_TYPE_ID': data.ASSOCIATED_ENTITY_TYPE_ID,
	          'PARENT_CONTEXT': this,
	          'CONTEXT': BX.CrmEntityType.resolveName(data.ASSOCIATED_ENTITY_TYPE_ID).toLowerCase(),
	          'IS_WITH_ORDERS_MODE': true
	        };
	        var timelineSummaryDocuments = new BX.Crm.TimelineSummaryDocuments(summaryOptions);
	        var options = data.RESULT.TIMELINE_SUMMARY_OPTIONS;
	        timelineSummaryDocuments.setOptions(options);
	        var nodes = [timelineSummaryDocuments.render()];
	        contentItem.appendChild(BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail"
	          },
	          children: nodes
	        }));
	        content.appendChild(contentItem);
	      } //region Author


	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        content.appendChild(authorNode);
	      } //endregion


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
	      var self = new FinalSummary();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return FinalSummary;
	}(FinalSummaryDocuments);

	/** @memberof BX.Crm.Timeline.Items */

	var Creation = /*#__PURE__*/function (_HistoryItem) {
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
	      var entityTypeId = this.getAssociatedEntityTypeId();
	      var entityData = this.getAssociatedEntityData();

	      if (entityTypeId === BX.CrmEntityType.enumeration.activity) {
	        var typeId = BX.prop.getInteger(entityData, "TYPE_ID");
	        var title = this.getMessage(typeId === BX.CrmActivityType.task ? "task" : "activity");
	        return title.replace(/#TITLE#/gi, this.cutOffText(BX.prop.getString(entityData, "SUBJECT")), 64);
	      }

	      if (entityTypeId === BX.CrmEntityType.enumeration.storeDocument) {
	        var docType = BX.prop.getString(entityData, "DOC_TYPE");

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

	      var entityTypeName = BX.CrmEntityType.resolveName(this.getAssociatedEntityTypeId()).toLowerCase();
	      var msg = this.getMessage(entityTypeName);
	      var isMessageNotFound = msg === entityTypeName;

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
	      var entityTypeId = this.getAssociatedEntityTypeId();

	      if (entityTypeId === BX.CrmEntityType.enumeration.ordershipment || entityTypeId === BX.CrmEntityType.enumeration.orderpayment) {
	        var data = this.getData();
	        data.TYPE_CATEGORY_ID = Item.modification;

	        if (data.hasOwnProperty('ASSOCIATED_ENTITY')) {
	          data.ASSOCIATED_ENTITY.HTML_TITLE = '';
	        }

	        var createOrderEntityItem = this._history.createOrderEntityItem(data);

	        return createOrderEntityItem.prepareContent();
	      }

	      return babelHelpers.get(babelHelpers.getPrototypeOf(Creation.prototype), "prepareContent", this).call(this);
	    }
	  }, {
	    key: "prepareContentDetails",
	    value: function prepareContentDetails() {
	      var entityTypeId = this.getAssociatedEntityTypeId();
	      var entityId = this.getAssociatedEntityId();
	      var entityData = this.getAssociatedEntityData();

	      if (entityTypeId === BX.CrmEntityType.enumeration.activity) {
	        var link = BX.create("A", {
	          attrs: {
	            href: "#"
	          },
	          html: this.cutOffText(BX.prop.getString(entityData, "DESCRIPTION_RAW"), 128)
	        });
	        BX.bind(link, "click", this._headerClickHandler);
	        return [link];
	      }

	      var title = BX.prop.getString(entityData, "TITLE", "");
	      var htmlTitle = BX.prop.getString(entityData, "HTML_TITLE", "");
	      var showUrl = BX.prop.getString(entityData, "SHOW_URL", "");

	      if (entityTypeId === BX.CrmEntityType.enumeration.deal && BX.prop.getObject(entityData, "ORDER", null)) {
	        var orderData = BX.prop.getObject(entityData, "ORDER", null);
	        htmlTitle = this.getMessage('dealOrderTitle').replace("#ORDER_ID#", orderData.ID).replace("#DATE_TIME#", orderData.ORDER_DATE).replace("#HREF#", orderData.SHOW_URL).replace("#PRICE_WITH_CURRENCY#", orderData.SUM);
	      }

	      if (title !== "" || htmlTitle !== "") {
	        var nodes = [];

	        if (showUrl === "" || entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId()) {
	          var spanAttrs = htmlTitle !== "" ? {
	            html: htmlTitle
	          } : {
	            text: title
	          };
	          nodes.push(BX.create("SPAN", spanAttrs));
	        } else {
	          var linkAttrs = {
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

	        var legend = this.getTextDataParam("LEGEND");

	        if (legend !== "") {
	          nodes.push(BX.create("BR"));
	          nodes.push(BX.create("SPAN", {
	            text: legend
	          }));
	        }

	        var baseEntityData = this.getObjectDataParam("BASE");
	        var baseEntityInfo = BX.prop.getObject(baseEntityData, "ENTITY_INFO");

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
	      var entityTypeId = this.getAssociatedEntityTypeId();

	      if (entityTypeId === BX.CrmEntityType.enumeration.activity) {
	        var entityData = this.getAssociatedEntityData();
	        var id = BX.prop.getInteger(entityData, "ID", 0);

	        if (id > 0) {
	          this._activityEditor.viewActivity(id);
	        }
	      }
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = Creation.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Creation();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Creation;
	}(History);

	babelHelpers.defineProperty(Creation, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */

	var Restoration = /*#__PURE__*/function (_History) {
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
	      var entityData = this.getAssociatedEntityData();
	      var title = BX.prop.getString(entityData, "TITLE");
	      return title !== "" ? [BX.create("SPAN", {
	        text: title
	      })] : [];
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = Restoration.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Restoration();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Restoration;
	}(History);

	babelHelpers.defineProperty(Restoration, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */

	var Relation = /*#__PURE__*/function (_History) {
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
	      var entityData = this.getAssociatedEntityData();
	      var link = BX.prop.getString(entityData, "SHOW_URL", "");

	      if (link.indexOf('/') !== 0) {
	        link = '#';
	      }

	      var content = this.getMessage('contentTemplate').replace('#ENTITY_TYPE_CAPTION#', BX.Text.encode(BX.prop.getString(entityData, 'ENTITY_TYPE_CAPTION', ''))).replace('#LEGEND#', '').replace('#LINK#', BX.Text.encode(link)).replace('#LINK_TITLE#', BX.Text.encode(BX.prop.getString(entityData, "TITLE", '')));
	      var nodes = [];
	      nodes.push(BX.create('SPAN', {
	        html: content
	      }));
	      return nodes;
	    }
	  }]);
	  return Relation;
	}(History);

	/** @memberof BX.Crm.Timeline.Items */

	var Link = /*#__PURE__*/function (_Relation) {
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
	      var m = Link.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Link();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Link;
	}(Relation);

	babelHelpers.defineProperty(Link, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */

	var Unlink = /*#__PURE__*/function (_Relation) {
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
	      var m = Unlink.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Unlink();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Unlink;
	}(Relation);

	babelHelpers.defineProperty(Unlink, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */

	var Mark$1 = /*#__PURE__*/function (_History) {
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
	      var m = Mark$$1.messages;

	      if (m.hasOwnProperty(name)) {
	        return m[name];
	      }

	      return babelHelpers.get(babelHelpers.getPrototypeOf(Mark$$1.prototype), "getMessage", this).call(this, name);
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      var title = "";
	      var entityData = this.getAssociatedEntityData();
	      var associatedEntityTypeId = this.getAssociatedEntityTypeId();
	      var typeCategoryId = this.getTypeCategoryId();

	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        var entityTypeId = BX.prop.getInteger(entityData, "TYPE_ID", 0);
	        var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
	        var activityProviderId = BX.prop.getString(entityData, "PROVIDER_ID", '');

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
	      var associatedEntityTypeId = this.getAssociatedEntityTypeId();

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
	      var entityData = this.getAssociatedEntityData();
	      var associatedEntityTypeId = this.getAssociatedEntityTypeId();
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-completed"
	        }
	      });
	      if (this.isFixed()) BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed'); //region Context Menu

	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      } //endregion


	      var content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      var header = this.prepareHeaderLayout();

	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        var entityTypeId = BX.prop.getInteger(entityData, "TYPE_ID", 0);
	        var iconClassName = "crm-entity-stream-section-icon";

	        if (entityTypeId === BX.CrmActivityType.email) {
	          iconClassName += " crm-entity-stream-section-icon-email";
	        } else if (entityTypeId === BX.CrmActivityType.call) {
	          iconClassName += " crm-entity-stream-section-icon-call";
	        } else if (entityTypeId === BX.CrmActivityType.meeting) {
	          iconClassName += " crm-entity-stream-section-icon-meeting";
	        } else if (entityTypeId === BX.CrmActivityType.task) {
	          iconClassName += " crm-entity-stream-section-icon-task";
	        } else if (entityTypeId === BX.CrmActivityType.provider) {
	          var providerId = BX.prop.getString(entityData, "PROVIDER_ID", "");

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
	        var detailWrapper = BX.create("DIV", {
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
	        var summary = this.getTextDataParam("SUMMARY");

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
	        var innerWrapper = BX.create("DIV", {
	          attrs: {
	            className: "crm-entity-stream-content-detail"
	          }
	        });
	        var associatedEntityTitle = this.cutOffText(BX.prop.getString(entityData, "TITLE", ""), 128);

	        if (BX.CrmEntityType.isDefined(associatedEntityTypeId)) {
	          var link = BX.prop.getString(entityData, 'SHOW_URL', '');

	          if (link.indexOf('/') !== 0) {
	            link = '#';
	          }

	          var contentTemplate = this.getMessage('entityContentTemplate').replace('#ENTITY_TYPE_CAPTION#', BX.Text.encode(BX.prop.getString(entityData, 'ENTITY_TYPE_CAPTION', ''))).replace('#LINK#', BX.Text.encode(link)).replace('#LINK_TITLE#', BX.Text.encode(associatedEntityTitle));
	          innerWrapper.appendChild(BX.create('SPAN', {
	            html: contentTemplate
	          }));
	        } else {
	          innerWrapper.innerText = associatedEntityTitle;
	        }

	        content.appendChild(innerWrapper);
	      } //region Author


	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        content.appendChild(authorNode);
	      } //endregion


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
	      var menuItems = [];

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
	      var entityData = this.getAssociatedEntityData();
	      var associatedEntityTypeId = this.getAssociatedEntityTypeId();

	      if (associatedEntityTypeId === BX.CrmEntityType.enumeration.activity) {
	        var id = BX.prop.getInteger(entityData, "ID", 0);

	        if (id > 0) {
	          this._activityEditor.viewActivity(id);
	        }
	      } else {
	        var showUrl = BX.prop.getString(entityData, "SHOW_URL", "");

	        if (showUrl !== "") {
	          BX.Crm.Page.open(showUrl);
	        }
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Mark$$1();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Mark$$1;
	}(History);

	babelHelpers.defineProperty(Mark$1, "messages", {});

	/** @memberof BX.Crm.Timeline.Items */

	var Comment$1 = /*#__PURE__*/function (_History) {
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
	      var playerWrapper = this._playerWrappers[file.id];
	      var stubNode = playerWrapper.querySelector(".crm-audio-cap-wrap");

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

	            var callInfoWrapper = BX.create("DIV", {
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
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-comment"
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
	      })); //region Context Menu

	      if (this.isContextMenuEnabled()) {
	        wrapper.appendChild(this.prepareContextMenuButton());
	      } //endregion


	      this._streamContentEventBlock = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      var header = this.prepareHeaderLayout();

	      this._streamContentEventBlock.appendChild(header);

	      if (!this.isReadOnly()) wrapper.appendChild(this.prepareFixedSwitcherLayout());
	      var detailChildren = [];

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
	        var buttons = BX.create("DIV", {
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
	      })); //region Author


	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        this._streamContentEventBlock.appendChild(authorNode);
	      } //endregion


	      var cleanText = this.getTextDataParam("TEXT", "");

	      var _hasInlineAttachment = this.getTextDataParam("HAS_INLINE_ATTACHMENT", "") === 'Y';

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
	            var promise = BX.html(node, result.BLOCK);
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

	      var actionData = {
	        data: {
	          id: this._id,
	          name: this._editorName
	        }
	      };
	      BX.ajax.runAction("crm.api.timeline.loadEditor", actionData).then(this.onLoadEditorSuccess.bind(this))["catch"](this.switchToViewMode.bind(this));
	    }
	  }, {
	    key: "onLoadEditorSuccess",
	    value: function onLoadEditorSuccess(result) {
	      if (!BX.type.isDomNode(this._editorContainer)) this._editorContainer = BX.create("div", {
	        attrs: {
	          className: "crm-entity-stream-section-comment-editor"
	        }
	      });
	      var html = BX.prop.getString(BX.prop.getObject(result, "data", {}), "html", '');
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
	    key: "onFinishFasten",
	    value: function onFinishFasten() {
	      this.registerImages(this._commentWrapper);
	      if (BX.type.isDomNode(this._fileBlock)) this.registerImages(this._fileBlock);
	      BX.LazyLoad.showImages();
	    }
	  }, {
	    key: "registerImages",
	    value: function registerImages(node) {
	      var commentImages = node.querySelectorAll('[data-bx-viewer="image"]');
	      var commentImagesLength = commentImages.length;
	      var idsList = [];

	      if (commentImagesLength > 0) {
	        for (var i = 0; i < commentImagesLength; ++i) {
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
	      var tagName = e.target.tagName.toLowerCase();

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

	      var menuItems = [];

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
	      var attachmentList = [];
	      var text = "";

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
	      var dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);

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

	      var history = this._history._manager.getHistory();

	      var deleteItem = history.findItemById(this._id);
	      if (deleteItem instanceof Comment) deleteItem.clearAnimate();

	      var fixedHistory = this._history._manager.getFixedHistory();

	      var deleteFixedItem = fixedHistory.findItemById(this._id);
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
	    key: "onSaveSuccess",
	    value: function onSaveSuccess(data) {
	      this._isRequestRunning = false;
	      var itemData = BX.prop.getObject(data, "HISTORY_ITEM");

	      var updateFixedItem = this._fixedHistory.findItemById(this._id);

	      if (updateFixedItem instanceof Comment) {
	        if (!BX.type.isNotEmptyString(itemData['IS_FIXED'])) itemData['IS_FIXED'] = 'Y';
	        updateFixedItem.setData(itemData);
	        updateFixedItem._id = BX.prop.getString(itemData, "ID");
	        updateFixedItem.switchToViewMode();
	      }

	      var updateItem = this._history.findItemById(this._id);

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

	      var contentWrapper = this._wrapper.querySelector("div.crm-entity-stream-section-content");

	      if (!contentWrapper) {
	        return BX.PreventDefault(e);
	      }

	      if (this._hasFiles && BX.type.isDomNode(this._commentWrapper) && !this._textLoaded) {
	        this._textLoaded = true;
	        this.loadContent(this._commentWrapper, "GET_TEXT");
	      }

	      var eventWrapper = contentWrapper.querySelector(".crm-entity-stream-content-event");

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
	      var button = contentWrapper.querySelector("a.crm-entity-stream-section-content-expand-btn");

	      if (button) {
	        button.innerHTML = this.getMessage(this._isCollapsed ? "expand" : "collapse");
	      }

	      return BX.PreventDefault(e);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Comment();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Comment;
	}(History);

	/** @memberof BX.Crm.Timeline.Items */

	var Wait$2 = /*#__PURE__*/function (_HistoryActivity) {
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
	      var header = BX.create("DIV", {
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
	      var entityData = this.getAssociatedEntityData();
	      var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");

	      if (description !== "") {
	        description = BX.util.trim(description);
	        description = BX.util.strip_tags(description);
	        description = BX.util.nl2br(description);
	      }

	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-wait"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-complete"
	        }
	      }));
	      var contentWrapper = BX.create("DIV", {
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
	      var header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      var detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        html: description
	      });
	      contentWrapper.appendChild(detailWrapper); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion
	      //region  Actions


	      this._actionContainer = BX.create("SPAN", {
	        attrs: {
	          className: "crm-entity-stream-content-detail-action"
	        }
	      });
	      contentWrapper.appendChild(this._actionContainer); //endregion

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
	      var self = new Wait();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Wait;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */

	var Sender = /*#__PURE__*/function (_HistoryActivity) {
	  babelHelpers.inherits(Sender, _HistoryActivity);

	  function Sender() {
	    babelHelpers.classCallCheck(this, Sender);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Sender).call(this));
	  }

	  babelHelpers.createClass(Sender, [{
	    key: "getDataSetting",
	    value: function getDataSetting(name) {
	      var settings = this.getObjectDataParam('SETTINGS') || {};
	      return settings[name] || null;
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      var m = Sender.messages;
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
	      var self = this;
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
	            "click": function click(e) {
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
	      var layoutClassName, textCaption;

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
	      var header = BX.create("DIV", {
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
	      var description = this.isRemoved() ? this.getMessage('removed') : this.getMessage('title') + ': ' + this.getDataSetting('letterTitle');
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-wait"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-complete"
	        }
	      }));
	      var contentWrapper = BX.create("DIV", {
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
	      var header = this.prepareHeaderLayout();
	      contentWrapper.appendChild(header);
	      var detailWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-detail"
	        },
	        html: description
	      });
	      contentWrapper.appendChild(detailWrapper); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        contentWrapper.appendChild(authorNode);
	      } //endregion


	      return wrapper;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Sender();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Sender;
	}(HistoryActivity);

	/** @memberof BX.Crm.Timeline.Items */

	var Bizproc = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(Bizproc, _History);

	  function Bizproc() {
	    babelHelpers.classCallCheck(this, Bizproc);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Bizproc).call(this));
	  }

	  babelHelpers.createClass(Bizproc, [{
	    key: "getTitle",
	    value: function getTitle() {
	      return this.getMessage("bizproc");
	    }
	  }, {
	    key: "prepareContent",
	    value: function prepareContent() {
	      var wrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-bp"
	        }
	      });
	      wrapper.appendChild(BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-bp"
	        }
	      }));
	      var content = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-event"
	        }
	      });
	      var header = this.prepareHeaderLayout();
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
	      })); //region Author

	      var authorNode = this.prepareAuthorLayout();

	      if (authorNode) {
	        content.appendChild(authorNode);
	      } //endregion


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
	      var type = this.getTextDataParam("TYPE");

	      if (type === 'ACTIVITY_ERROR') {
	        return '<strong>#TITLE#</strong>: #ERROR_TEXT#'.replace('#TITLE#', BX.util.htmlspecialchars(this.getTextDataParam("ACTIVITY_TITLE"))).replace('#ERROR_TEXT#', BX.util.htmlspecialchars(this.getTextDataParam("ERROR_TEXT")));
	      }

	      var workflowName = this.getTextDataParam("WORKFLOW_TEMPLATE_NAME");
	      var workflowStatus = this.getTextDataParam("WORKFLOW_STATUS_NAME");

	      if (!workflowName || workflowStatus !== 'Created' && workflowStatus !== 'Completed' && workflowStatus !== 'Terminated') {
	        return BX.util.htmlspecialchars(this.getTextDataParam("COMMENT"));
	      }

	      var label = BX.message('CRM_TIMELINE_BIZPROC_CREATED');

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
	      var self = new Bizproc();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Bizproc;
	}(History);

	/** @memberof BX.Crm.Timeline.Actions */

	var Scoring = /*#__PURE__*/function (_History) {
	  babelHelpers.inherits(Scoring, _History);

	  function Scoring() {
	    babelHelpers.classCallCheck(this, Scoring);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Scoring).call(this));
	  }

	  babelHelpers.createClass(Scoring, [{
	    key: "prepareContent",
	    value: function prepareContent() {
	      var outerWrapper = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-scoring"
	        },
	        events: {
	          click: function () {
	            var url = "/crm/ml/#entity#/#id#/detail";
	            var ownerTypeId = this.getOwnerTypeId();
	            var ownerId = this.getOwnerId();
	            var ownerType;

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
	      var scoringInfo = BX.prop.getObject(this._data, "SCORING_INFO", null);

	      if (!scoringInfo) {
	        return outerWrapper;
	      }

	      var score = BX.prop.getNumber(scoringInfo, "SCORE", 0);
	      var scoreDelta = BX.prop.getNumber(scoringInfo, "SCORE_DELTA", 0);
	      score = Math.round(score * 100);
	      scoreDelta = Math.round(scoreDelta * 100);
	      var result = BX.create("DIV", {
	        attrs: {
	          className: "crm-entity-stream-content-scoring-total-result"
	        },
	        text: score + "%"
	      });
	      var iconClass = "crm-entity-stream-content-scoring-total-icon";

	      if (score < 50) {
	        iconClass += " crm-entity-stream-content-scoring-total-icon-fail";
	      } else if (score < 75) {
	        iconClass += " crm-entity-stream-content-scoring-total-icon-middle";
	      } else {
	        iconClass += " crm-entity-stream-content-scoring-total-icon-success";
	      }

	      var icon = BX.create("DIV", {
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
	          )*/
	          ]
	        })]
	      }));
	      return outerWrapper;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Scoring();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return Scoring;
	}(History);

	/** @memberof BX.Crm.Timeline.Streams */

	var History$1 = /*#__PURE__*/function (_Stream) {
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
	        var itemData = this.getSetting("itemData");

	        if (!BX.type.isArray(itemData)) {
	          itemData = [];
	        }

	        var i, length, item;

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

	      var now = BX.prop.extractDate(new Date());
	      var i, length, item;

	      if (!this.isStubMode()) {
	        if (this._filterWrapper) {
	          var closeFilterButton = this._filterWrapper.querySelector(".crm-entity-stream-filter-close");

	          if (closeFilterButton) {
	            BX.bind(closeFilterButton, "click", this.onFilterClose.bind(this));
	          }
	        }

	        for (i = 0, length = this._items.length; i < length; i++) {
	          item = this._items[i];
	          item.setContainer(this._wrapper);
	          var created = item.getCreatedDate();

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
	      var length = this._items.length;

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

	      for (var i = 0; i < length - 1; i++) {
	        var item = this._items[i];

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
	    key: "getLastItem",
	    value: function getLastItem() {
	      return this._items.length > 0 ? this._items[this._items.length - 1] : null;
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
	    key: "removeItemByIndex",
	    value: function removeItemByIndex(index) {
	      if (index < this._items.length) {
	        this._items.splice(index, 1);
	      }
	    }
	  }, {
	    key: "getItemIndex",
	    value: function getItemIndex(item) {
	      for (var i = 0, length = this._items.length; i < length; i++) {
	        if (this._items[i] === item) {
	          return i;
	        }
	      }

	      return -1;
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

	      var results = [];

	      for (var i = 0, l = this._items.length; i < l; i++) {
	        var item = this._items[i];

	        if (item.getAssociatedEntityTypeId() === $entityTypeId && item.getAssociatedEntityId() === entityId) {
	          results.push(item);
	        }
	      }

	      return results;
	    }
	  }, {
	    key: "findItemById",
	    value: function findItemById(id) {
	      id = id.toString();

	      for (var i = 0, l = this._items.length; i < l; i++) {
	        if (this._items[i].getId() === id) {
	          return this._items[i];
	        }
	      }

	      return null;
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

	      var section = this._wrapper.querySelector(".crm-entity-stream-section-today-label, .crm-entity-stream-section-planned-label, .crm-entity-stream-section-history-label");

	      if (section) {
	        var sectionWrapper = section.querySelector(".crm-entity-stream-section-content");

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
	        var filter = BX.Main.filterManager.getById(this._filterId);

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
	            text: this.formatDate(BX.prop.extractDate(new Date()))
	          })]
	        })]
	      });
	    }
	  }, {
	    key: "createDaySection",
	    value: function createDaySection(date) {
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
	            text: this.formatDate(date)
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
	      var typeId = BX.prop.getInteger(data, "TYPE_ID", Item.undefined);
	      var typeCategoryId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);
	      var providerId = BX.prop.getString(BX.prop.getObject(data, "ASSOCIATED_ENTITY", {}), "PROVIDER_ID", "");
	      var vueComponentId = 'TYPE_' + typeCategoryId + (providerId ? '_' + providerId : '');
	      var vueComponentsMap = new Map([['TYPE_' + BX.CrmActivityType.provider + '_CRM_NOTIFICATION', BX.Crm.Timeline.Notification], ['TYPE_' + BX.CrmActivityType.provider + '_CRM_DELIVERY', BX.Crm.Timeline.DeliveryActivity]]);
	      var vueComponent = vueComponentsMap.has(vueComponentId) ? vueComponentsMap.get(vueComponentId) : null;

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
	        } else if (providerId === 'CRM_SMS') {
	          return Sms$1.create(data["ID"], {
	            history: this._history,
	            fixedHistory: this._fixedHistory,
	            container: this._wrapper,
	            activityEditor: this._activityEditor,
	            data: data,
	            smsStatusDescriptions: this._manager.getSetting('smsStatusDescriptions', {}),
	            smsStatusSemantics: this._manager.getSetting('smsStatusSemantics', {})
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
	          return Rest$2.create(data["ID"], {
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
	        } else if (providerId === 'CRM_DELIVERY') {
	          return HistoryActivity.create(data["ID"], {
	            history: this._history,
	            fixedHistory: this._fixedHistory,
	            container: this._wrapper,
	            activityEditor: this._activityEditor,
	            data: data,
	            vueComponent: vueComponent
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
	        data: data,
	        vueComponent: vueComponent
	      });
	    }
	  }, {
	    key: "createOrderEntityItem",
	    value: function createOrderEntityItem(data) {
	      var entityId = BX.prop.getInteger(data, "ASSOCIATED_ENTITY_TYPE_ID", 0);
	      var typeId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);

	      if (entityId !== BX.CrmEntityType.enumeration.order && entityId !== BX.CrmEntityType.enumeration.orderpayment && entityId !== BX.CrmEntityType.enumeration.ordershipment) {
	        return null;
	      }

	      var settings = {
	        history: this._history,
	        fixedHistory: this._fixedHistory,
	        container: this._wrapper,
	        activityEditor: this._activityEditor,
	        data: data
	      };

	      if (typeId === Item.creation) {
	        return OrderCreation.create(data["ID"], settings);
	      } else if (typeId === Item.modification) {
	        return OrderModification.create(data["ID"], settings);
	      } else if (typeId === Order.encourageBuyProducts) {
	        settings.vueComponent = BX.Crm.Timeline.EncourageBuyProducts;
	        return History.create(data["ID"], settings);
	      }
	    }
	  }, {
	    key: "createStoreDocumentItem",
	    value: function createStoreDocumentItem(data) {
	      var entityId = BX.prop.getInteger(data, "ASSOCIATED_ENTITY_TYPE_ID", 0);
	      var typeId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);

	      if (entityId !== BX.CrmEntityType.enumeration.storeDocument && entityId !== BX.CrmEntityType.enumeration.shipmentDocument) {
	        return null;
	      }

	      var settings = {
	        history: this._history,
	        fixedHistory: this._fixedHistory,
	        container: this._wrapper,
	        activityEditor: this._activityEditor,
	        data: data
	      };

	      if (typeId === Item.creation) {
	        return StoreDocumentCreation.create(data["ID"], settings);
	      } else if (typeId === Item.modification) {
	        return StoreDocumentModification.create(data["ID"], settings);
	      }
	    }
	  }, {
	    key: "createExternalNotificationItem",
	    value: function createExternalNotificationItem(data) {
	      var typeId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);
	      var changedFieldName = BX.prop.getString(data, 'CHANGED_FIELD_NAME', '');

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
	    key: "createDeliveryItem",
	    value: function createDeliveryItem(data) {
	      var typeId = BX.prop.getInteger(data, "TYPE_ID", Item.undefined);
	      var typeCategoryId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);

	      if (typeId !== Item.delivery) {
	        return null;
	      }

	      var vueComponentsMap = new Map([[Delivery.taxiEstimationRequest, BX.Crm.Delivery.Taxi.EstimationRequest], [Delivery.taxiCallRequest, BX.Crm.Delivery.Taxi.CallRequest], [Delivery.taxiCancelledByManager, BX.Crm.Delivery.Taxi.CancelledByManager], [Delivery.taxiCancelledByDriver, BX.Crm.Delivery.Taxi.CancelledByDriver], [Delivery.taxiPerformerNotFound, BX.Crm.Delivery.Taxi.PerformerNotFound], [Delivery.taxiSmsProviderIssue, BX.Crm.Delivery.Taxi.SmsProviderIssue], [Delivery.taxiReturnedFinish, BX.Crm.Delivery.Taxi.ReturnedFinish], [Delivery.deliveryMessage, BX.Crm.Timeline.DeliveryMessage], [Delivery.deliveryCalculation, BX.Crm.Timeline.DeliveryCalculation]]);

	      if (vueComponentsMap.has(typeCategoryId)) {
	        return History.create(data["ID"], {
	          history: this._history,
	          fixedHistory: this._fixedHistory,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data,
	          vueComponent: vueComponentsMap.get(typeCategoryId)
	        });
	      }
	    }
	  }, {
	    key: "createItem",
	    value: function createItem(data) {
	      var typeId = BX.prop.getInteger(data, "TYPE_ID", Item.undefined);
	      var typeCategoryId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);

	      if (typeId === Item.activity) {
	        return this.createActivityItem(data);
	      } else if (typeId === Item.order) {
	        return this.createOrderEntityItem(data);
	      } else if (typeId === Item.storeDocument) {
	        return this.createStoreDocumentItem(data);
	      } else if (typeId === Item.externalNotification) {
	        return this.createExternalNotificationItem(data);
	      } else if (typeId === Item.orderCheck) {
	        return OrderCheck.create(data["ID"], {
	          history: this._history,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeId === Item.finalSummary) {
	        return FinalSummary.create(data["ID"], {
	          history: this._history,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
	      } else if (typeId === Item.finalSummaryDocuments) {
	        return FinalSummaryDocuments.create(data["ID"], {
	          history: this._history,
	          container: this._wrapper,
	          activityEditor: this._activityEditor,
	          data: data
	        });
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
	        return Wait$2.create(data["ID"], {
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
	      } else if (typeId === Item.delivery) {
	        return this.createDeliveryItem(data);
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
	      var index = this.getItemIndex(item);

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
	      var i;

	      for (i = this._items.length - 1; i >= 0; i--) {
	        this._items[i].clearLayout();
	      }

	      this._items = [];
	      this._currentDaySection = this._lastDaySection = this._emptySection = this._filterEmptyResultSection = null;
	      this._anchor = null;
	      this._lastDate = null; //Clean wrapper. Skip filter for prevent trembling.

	      var children = [];
	      var child;

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

	      var pos = this._loadingWaiter.getBoundingClientRect();

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
	      var length = itemData.length;

	      if (length === 0) {
	        return;
	      }

	      if (this._filterEmptyResultSection) {
	        this._filterEmptyResultSection = BX.remove(this._filterEmptyResultSection);
	      }

	      var now = BX.prop.extractDate(new Date());
	      var i, item;
	      var lastItemTime = "";

	      for (i = 0; i < length; i++) {
	        var itemId = BX.prop.getInteger(itemData[i], "ID", 0);

	        if (itemId <= 0) {
	          continue;
	        }

	        lastItemTime = BX.prop.getString(itemData[i], "CREATED_SERVER", "");

	        if (this.findItemById(itemId) !== null) {
	          continue;
	        }

	        item = this.createItem(itemData[i]);

	        this._items.push(item);

	        var created = item.getCreatedDate();

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
	      var m = History$$1.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new History$$1();
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

	var FixedHistory = /*#__PURE__*/function (_History) {
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
	      var datetimeFormat = BX.message("FORMAT_DATETIME").replace(/:SS/, "");
	      this._timeFormat = BX.date.convertBitrixFormat(datetimeFormat);
	      var itemData = this.getSetting("itemData");

	      if (!BX.type.isArray(itemData)) {
	        itemData = [];
	      }

	      var i, length, item;

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

	      for (var i = 0; i < this._items.length; i++) {
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
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new FixedHistory();
	      self.initialize(id, settings);
	      this.instances[self.getId()] = self;
	      return self;
	    }
	  }]);
	  return FixedHistory;
	}(History$1);
	FixedHistory.instances = {};

	/** @memberof BX.Crm.Timeline.Animation */
	var Expand = /*#__PURE__*/function () {
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
	      var position = BX.pos(this._node);
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
	      var self = new Expand();
	      self.initialize(node, callback);
	      return self;
	    }
	  }]);
	  return Expand;
	}();

	/** @memberof BX.Crm.Timeline */

	var Manager = /*#__PURE__*/function () {
	  function Manager() {
	    babelHelpers.classCallCheck(this, Manager);
	    this._id = "";
	    this._settings = {};
	    this._container = null;
	    this._ownerTypeId = 0;
	    this._ownerId = 0;
	    this._ownerInfo = null;
	    this._progressSemantics = "";
	    this._commentEditor = null;
	    this._waitEditor = null;
	    this._smsEditor = null;
	    this._zoomEditor = null;
	    this._chat = null;
	    this._schedule = null;
	    this._history = null;
	    this._fixedHistory = null;
	    this._activityEditor = null;
	    this._menuBar = null;
	    this._userId = 0;
	    this._readOnly = false;
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
	      var containerId = this.getSetting("containerId");

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
	      var activityEditorId = this.getSetting("activityEditorId");

	      if (BX.type.isNotEmptyString(activityEditorId)) {
	        this._activityEditor = BX.CrmActivityEditor.items[activityEditorId];

	        if (!(this._activityEditor instanceof BX.CrmActivityEditor)) {
	          throw "BX.CrmTimeline. Activity editor instance is not found.";
	        }
	      }

	      var ajaxId = this.getSetting("ajaxId");
	      var currentUrl = this.getSetting("currentUrl");
	      var serviceUrl = this.getSetting("serviceUrl");
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

	      this._commentEditor = Comment.create(this._id, {
	        manager: this,
	        ownerTypeId: this._ownerTypeId,
	        ownerId: this._ownerId,
	        serviceUrl: this.getSetting("serviceUrl"),
	        container: this.getSetting("editorCommentContainer"),
	        input: this.getSetting("editorCommentInput"),
	        editorName: this.getSetting("editorCommentEditorName"),
	        button: this.getSetting("editorCommentButton"),
	        cancelButton: this.getSetting("editorCommentCancelButton")
	      });

	      this._commentEditor.setVisible(true);

	      this._commentEditor.setHistory(this._history);

	      if (this._readOnly) {
	        this._commentEditor.setVisible(false);
	      }

	      if (BX.prop.getBoolean(this._settings, "enableWait", false)) {
	        this._waitEditor = Wait.create(this._id, {
	          manager: this,
	          ownerTypeId: this._ownerTypeId,
	          ownerId: this._ownerId,
	          serviceUrl: this.getSetting("serviceUrl"),
	          config: this.getSetting("editorWaitConfig", {}),
	          targetDates: this.getSetting("editorWaitTargetDates", []),
	          container: this.getSetting("editorWaitContainer"),
	          configContainer: this.getSetting("editorWaitConfigContainer"),
	          input: this.getSetting("editorWaitInput"),
	          button: this.getSetting("editorWaitButton"),
	          cancelButton: this.getSetting("editorWaitCancelButton")
	        });

	        this._waitEditor.setVisible(false);
	      }

	      if (BX.prop.getBoolean(this._settings, "enableSms", false)) {
	        this._smsEditor = Sms.create(this._id, {
	          manager: this,
	          ownerTypeId: this._ownerTypeId,
	          ownerId: this._ownerId,
	          serviceUrl: this.getSetting("serviceUrl"),
	          config: this.getSetting("editorSmsConfig", {}),
	          container: this.getSetting("editorSmsContainer"),
	          input: this.getSetting("editorSmsInput"),
	          templatesContainer: this.getSetting("editorSmsTemplatesContainer"),
	          button: this.getSetting("editorSmsButton"),
	          cancelButton: this.getSetting("editorSmsCancelButton")
	        });

	        this._smsEditor.setVisible(false);
	      }

	      if (BX.prop.getBoolean(this._settings, "enableZoom", false) && BX.prop.getBoolean(this._settings, "statusZoom", false)) {
	        this._zoomEditor = new BX.Crm.Zoom({
	          id: this._id,
	          manager: this,
	          ownerTypeId: this._ownerTypeId,
	          ownerId: this._ownerId,
	          container: this.getSetting("editorZoomContainer"),
	          userId: this._userId
	        });

	        this._zoomEditor.setVisible(false);
	      }

	      if (BX.prop.getBoolean(this._settings, "enableRest", false)) {
	        this._restEditor = Rest.create(this._id, {
	          manager: this,
	          ownerTypeId: this._ownerTypeId,
	          ownerId: this._ownerId,
	          placement: BX.prop.getString(this._settings, "restPlacement", '')
	        });
	      }

	      this._chat.layout();

	      this._schedule.layout();

	      this._fixedHistory.layout();

	      this._history.layout();

	      this._pullTagName = BX.prop.getString(this._settings, "pullTagName", "");

	      if (this._pullTagName !== "") {
	        BX.addCustomEvent("onPullEvent-crm", BX.delegate(this.onPullEvent, this));
	        this.extendWatch();
	      }

	      this._menuBar = MenuBar.create(this._id, {
	        container: BX(this.getSetting("menuBarContainer")),
	        menuId: this.getSetting("menuBarObjectId"),
	        ownerInfo: this._ownerInfo,
	        activityEditor: this._activityEditor,
	        commentEditor: this._commentEditor,
	        waitEditor: this._waitEditor,
	        smsEditor: this._smsEditor,
	        zoomEditor: this._zoomEditor,
	        restEditor: this._restEditor,
	        readOnly: this._readOnly,
	        manager: this
	      });

	      if (!this._readOnly) {
	        this._menuBar.reset();
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
	      } else if (command === "timeline_link_add") {
	        this.processLinkExternalAdd(params);
	      } else if (command === "timeline_link_delete") {
	        this.processLinkExternalDelete(params);
	      } else if (command === "timeline_document_add") {
	        this.processLinkExternalAdd(params);
	      } else if (command === "timeline_document_update") {
	        this.processDocumentExternalUpdate(params);
	      } else if (command === "timeline_document_delete") {
	        this.processDocumentExternalDelete(params);
	      } else if (command === "timeline_comment_update") {
	        this.processCommentExternalUpdate(params);
	      } else if (command === "timeline_comment_delete") {
	        this.processCommentExternalDelete(params);
	      } else if (command === "timeline_changed_binding") {
	        this.processChangeBinding(params);
	      } else if (command === "timeline_item_change_fasten") {
	        this.processItemChangeFasten(params);
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
	      var entityData, scheduleItemData, historyItemData, scheduleItem, historyItem;
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
	      var entityData, scheduleItemData, scheduleItem, historyItemData, historyItem, fixedHistoryItem;
	      entityData = BX.prop.getObject(params, "ENTITY", null);
	      scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
	      historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);

	      if (entityData) {
	        if (historyItemData && !BX.type.isPlainObject(historyItemData["ASSOCIATED_ENTITY"])) {
	          historyItemData["ASSOCIATED_ENTITY"] = entityData;
	        }

	        var entityId = BX.prop.getInteger(entityData, "ID", 0);

	        var historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);

	        for (var i = 0, length = historyItems.length; i < length; i++) {
	          historyItem = historyItems[i];
	          historyItem.setAssociatedEntityData(entityData);
	          historyItem.refreshLayout();
	        }

	        var fixedHistoryItems = this._fixedHistory.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);

	        for (var _i = 0, _length = fixedHistoryItems.length; _i < _length; _i++) {
	          fixedHistoryItem = fixedHistoryItems[_i];
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
	              this._schedule.transferItemToHistory(scheduleItem, historyItemData); //History data are already processed


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
	      var entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);

	      var historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);

	      for (var i = 0, length = historyItems.length; i < length; i++) {
	        this._history.deleteItem(historyItems[i]);
	      }

	      var fixedHistoryItems = this._fixedHistory.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);

	      for (var _i2 = 0, _length2 = fixedHistoryItems.length; _i2 < _length2; _i2++) {
	        this._fixedHistory.deleteItem(fixedHistoryItems[_i2]);
	      }

	      var scheduleItem = this._schedule.getItemByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);

	      if (scheduleItem) {
	        this._schedule.deleteItem(scheduleItem);
	      }
	    }
	  }, {
	    key: "processCommentExternalAdd",
	    value: function processCommentExternalAdd(params) {
	      var historyItemData, historyItem;
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
	    key: "processLinkExternalAdd",
	    value: function processLinkExternalAdd(params) {
	      var historyItemData, historyItem;
	      historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);

	      if (historyItemData !== null) {
	        historyItem = this.addHistoryItem(historyItemData);
	        Expand.create(historyItem.getWrapper(), null).run();
	      }
	    }
	  }, {
	    key: "processLinkExternalDelete",
	    value: function processLinkExternalDelete(params) {
	      this.processLinkExternalAdd(params);
	    }
	  }, {
	    key: "processCommentExternalUpdate",
	    value: function processCommentExternalUpdate(params) {
	      var entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
	      var historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);

	      var updateItem = this._history.findItemById(entityId);

	      if (updateItem instanceof Comment && historyItemData !== null) {
	        updateItem.setData(historyItemData);
	        updateItem.switchToViewMode();
	      }

	      var updateFixedItem = this._fixedHistory.findItemById(entityId);

	      if (updateFixedItem instanceof Comment && historyItemData !== null) {
	        updateFixedItem.setData(historyItemData);
	        updateFixedItem.switchToViewMode();
	      }
	    }
	  }, {
	    key: "processCommentExternalDelete",
	    value: function processCommentExternalDelete(params) {
	      var entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
	      window.setTimeout(BX.delegate(function () {
	        var deleteItem = this._history.findItemById(entityId);

	        if (deleteItem instanceof Comment) {
	          this._history.deleteItem(deleteItem);
	        }

	        var deleteFixedItem = this._fixedHistory.findItemById(entityId);

	        if (deleteFixedItem instanceof Comment) {
	          this._fixedHistory.deleteItem(deleteFixedItem);
	        }
	      }, this), 1200);
	    }
	  }, {
	    key: "processDocumentExternalDelete",
	    value: function processDocumentExternalDelete(params) {
	      window.setTimeout(BX.delegate(function () {
	        var historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
	        var i, length;
	        var associatedEntityId = BX.prop.getInteger(historyItemData, "ASSOCIATED_ENTITY_ID", 0);

	        var historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.document, associatedEntityId);

	        for (i = 0, length = historyItems.length; i < length; i++) {
	          if (historyItems[i] instanceof Document) {
	            this._history.deleteItem(historyItems[i]);
	          }
	        }

	        historyItems = this._fixedHistory.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.document, associatedEntityId);

	        for (i = 0, length = historyItems.length; i < length; i++) {
	          if (historyItems[i] instanceof Document) {
	            this._fixedHistory.deleteItem(historyItems[i]);
	          }
	        }
	      }, this), 100);
	    }
	  }, {
	    key: "processDocumentExternalUpdate",
	    value: function processDocumentExternalUpdate(params) {
	      var historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
	      var id = BX.prop.getInteger(historyItemData, "ID", 0);

	      var updateItem = this._history.findItemById(id);

	      if (updateItem instanceof Document && historyItemData !== null) {
	        updateItem.setData(historyItemData);
	        updateItem.updateWrapper();
	      }

	      var updateFixedItem = this._fixedHistory.findItemById(id);

	      if (updateFixedItem instanceof Document && historyItemData !== null) {
	        updateFixedItem.setData(historyItemData);
	        updateFixedItem.updateWrapper();
	      }
	    }
	  }, {
	    key: "processChangeBinding",
	    value: function processChangeBinding(params) {
	      var entityId = BX.prop.getString(params, "OLD_ID", 0);
	      var entityNewId = BX.prop.getString(params, "NEW_ID", 0);

	      var item = this._history.findItemById(entityId);

	      if (item instanceof Item$1) {
	        item._id = entityNewId;
	        var itemData = item.getData();
	        itemData.ID = entityNewId;
	        item.setData(itemData);
	      }

	      var fixedItem = this._fixedHistory.findItemById(entityId);

	      if (fixedItem instanceof Item$1) {
	        fixedItem._id = entityNewId;
	        var fixedItemData = fixedItem.getData();
	        fixedItemData.ID = entityNewId;
	        fixedItem.setData(fixedItemData);
	      }
	    }
	  }, {
	    key: "processItemChangeFasten",
	    value: function processItemChangeFasten(params) {
	      var entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
	      var historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
	      window.setTimeout(BX.delegate(function () {
	        var fixedItem = this._fixedHistory.findItemById(entityId);

	        if (historyItemData['IS_FIXED'] === 'N' && fixedItem) {
	          fixedItem.onSuccessUnfasten();
	        } else if (historyItemData['IS_FIXED'] === 'Y' && !fixedItem) {
	          var historyItem = this._history.findItemById(entityId);

	          if (historyItem) {
	            historyItem.onSuccessFasten();
	          } else {
	            var newFixedItem = this._fixedHistory.createItem(this._data);

	            newFixedItem._isFixed = true;

	            this._fixedHistory.addItem(newFixedItem, 0);

	            newFixedItem.layout();
	          }
	        }
	      }, this), 1200);
	    }
	  }, {
	    key: "processItemExternalUpdate",
	    value: function processItemExternalUpdate(params) {
	      var entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
	      var historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);

	      var historyItem = this._history.findItemById(entityId);

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
	      var scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);

	      if (scheduleItemData !== null) {
	        this.addScheduleItem(scheduleItemData);
	      }
	    }
	  }, {
	    key: "processWaitExternalUpdate",
	    value: function processWaitExternalUpdate(params) {
	      var entityData, scheduleItemData, scheduleItem, historyItemData, historyItem;
	      entityData = BX.prop.getObject(params, "ENTITY", null);
	      scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
	      historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);

	      if (entityData) {
	        if (historyItemData && !BX.type.isPlainObject(historyItemData["ASSOCIATED_ENTITY"])) {
	          historyItemData["ASSOCIATED_ENTITY"] = entityData;
	        }

	        var entityId = BX.prop.getInteger(entityData, "ID", 0);

	        var historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.wait, entityId);

	        var i = 0;
	        var length = historyItems.length;

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
	              this._schedule.transferItemToHistory(scheduleItem, historyItemData); //History data are already processed


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
	      var entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);

	      var historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.wait, entityId);

	      var i = 0;
	      var length = historyItems.length;

	      for (; i < length; i++) {
	        this._history.deleteItem(historyItems[i]);
	      }

	      var scheduleItem = this._schedule.getItemByAssociatedEntity(BX.CrmEntityType.enumeration.wait, entityId);

	      if (scheduleItem) {
	        this._schedule.deleteItem(scheduleItem);
	      }
	    }
	  }, {
	    key: "processBizprocStatus",
	    value: function processBizprocStatus(params) {
	      var historyItemData, historyItem;
	      historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);

	      if (historyItemData !== null) {
	        historyItem = this.addHistoryItem(historyItemData);
	        Expand.create(historyItem.getWrapper(), null).run();
	      }
	    }
	  }, {
	    key: "processScoringExternalAdd",
	    value: function processScoringExternalAdd(params) {
	      var historyItemData, historyItem;
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

	      var semantics = BX.prop.getString(eventArgs, "semantics", "");

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
	      if (this._ownerId <= 0) {
	        return false;
	      }

	      return (this._ownerTypeId === BX.CrmEntityType.enumeration.deal || this._ownerTypeId === BX.CrmEntityType.enumeration.lead) && this._progressSemantics === "process";
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
	    key: "getWaitEditor",
	    value: function getWaitEditor() {
	      return this._waitEditor;
	    }
	  }, {
	    key: "getSmsEditor",
	    value: function getSmsEditor() {
	      return this._smsEditor;
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
	    key: "processEditingCompletion",
	    value: function processEditingCompletion(editor) {
	      if (this._waitEditor && editor === this._waitEditor) {
	        this._waitEditor.setVisible(false);

	        this._commentEditor.setVisible(true);

	        this._menuBar.setActiveItemById("comment");
	      }

	      if (this._smsEditor && editor === this._smsEditor) {
	        this._smsEditor.setVisible(false);

	        this._commentEditor.setVisible(true);

	        this._menuBar.setActiveItemById("comment");
	      }
	    }
	  }, {
	    key: "processEditingCancellation",
	    value: function processEditingCancellation(editor) {
	      if (this._waitEditor && editor === this._waitEditor) {
	        this._waitEditor.setVisible(false);

	        this._commentEditor.setVisible(true);

	        this._menuBar.setActiveItemById("comment");
	      }

	      if (this._smsEditor && editor === this._smsEditor) {
	        this._smsEditor.setVisible(false);

	        this._commentEditor.setVisible(true);

	        this._menuBar.setActiveItemById("comment");
	      }
	    }
	  }, {
	    key: "addScheduleItem",
	    value: function addScheduleItem(data) {
	      var item = this._schedule.createItem(data);

	      var index = this._schedule.calculateItemIndex(item);

	      var anchor = this._schedule.createAnchor(index);

	      this._schedule.addItem(item, index);

	      item.layout({
	        anchor: anchor
	      });
	      return item;
	    }
	  }, {
	    key: "addHistoryItem",
	    value: function addHistoryItem(data) {
	      var item = this._history.createItem(data);

	      var index = this._history.calculateItemIndex(item);

	      var historyAnchor = this._history.createAnchor(index);

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

	      var player = new BX.Fileman.Player(id, {
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
	        onInit: function onInit(player) {
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
	      player.init(); // todo remove this after player will be able to get float playbackRate

	      if (options.playbackRate > 1) {
	        player.vjsPlayer.playbackRate(options.playbackRate);
	      }

	      return player;
	    }
	  }, {
	    key: "onActivityCreated",
	    value: function onActivityCreated(activity, data) {//Already processed in onPullEvent
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
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new Manager();
	      self.initialize(id, settings);
	      Manager.instances[self.getId()] = self;
	      return self;
	    }
	  }]);
	  return Manager;
	}();

	babelHelpers.defineProperty(Manager, "instances", {});

	/** @memberof BX.Crm.Timeline.Tools */
	var SmsWatcher = {
	  _pullTagName: 'MESSAGESERVICE',
	  _pullInited: false,
	  _listeners: {},
	  initPull: function initPull() {
	    if (this._pullInited) return;
	    BX.addCustomEvent("onPullEvent-messageservice", this.onPullEvent.bind(this));
	    this.extendWatch();
	    this._pullInited = true;
	  },
	  subscribeOnMessageUpdate: function subscribeOnMessageUpdate(messageId, callback) {
	    this.initPull();
	    this._listeners[messageId] = callback;
	  },
	  fireExternalStatusUpdate: function fireExternalStatusUpdate(messageId, message) {
	    var listener = this._listeners[messageId];

	    if (listener) {
	      listener(message);
	    }
	  },
	  onPullEvent: function onPullEvent(command, params) {
	    // console.log(command, params);
	    if (command === 'message_update') {
	      for (var i = 0; i < params.messages.length; ++i) {
	        var message = params.messages[i];
	        this.fireExternalStatusUpdate(message['ID'], message);
	      }
	    }
	  },
	  extendWatch: function extendWatch() {
	    if (BX.type.isFunction(BX.PULL)) {
	      BX.PULL.extendWatch(this._pullTagName);
	      window.setTimeout(this.extendWatch.bind(this), 60000);
	    }
	  }
	};

	/** @memberof BX.Crm.Timeline.Actions */

	var SchedulePostpone = /*#__PURE__*/function (_Activity) {
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

	      var handler = BX.delegate(this.onMenuItemClick, this);
	      var menuItems = [{
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
	      var offset = 0;

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
	      var m = SchedulePostpone.messages;
	      return m.hasOwnProperty(name) ? m[name] : name;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new SchedulePostpone();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return SchedulePostpone;
	}(Activity);

	babelHelpers.defineProperty(SchedulePostpone, "messages", {});

	/** @memberof BX.Crm.Timeline.Animation */

	var Comment$2 = /*#__PURE__*/function () {
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
	      var nodeOpacityAnim = new BX.easing({
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

	          var shift = Shift.create(this._node, this._anchor, this._startPosition, false, {
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
	      var self = new Comment();
	      self.initialize(node, anchor, startPosition, events);
	      return self;
	    }
	  }]);
	  return Comment;
	}();

	var Streams = {
	  History: History$1,
	  FixedHistory: FixedHistory,
	  EntityChat: EntityChat,
	  Schedule: Schedule
	};
	var Editors = {
	  Comment: Comment,
	  Wait: Wait,
	  Rest: Rest,
	  Sms: Sms
	};
	var Tools = {
	  WaitConfigurationDialog: WaitConfigurationDialog,
	  SchedulePostponeController: SchedulePostponeController,
	  MenuBar: MenuBar,
	  SmsWatcher: SmsWatcher,
	  AudioPlaybackRateSelector: AudioPlaybackRateSelector
	};
	var Actions = {
	  Activity: Activity,
	  Call: Call$1,
	  HistoryCall: HistoryCall,
	  ScheduleCall: ScheduleCall,
	  Email: Email,
	  HistoryEmail: HistoryEmail,
	  ScheduleEmail: ScheduleEmail,
	  OpenLine: OpenLine,
	  SchedulePostpone: SchedulePostpone
	};
	var ScheduledItems = {
	  Activity: Activity$1,
	  Email: Email$1,
	  Call: Call,
	  CallTracker: CallTracker,
	  Meeting: Meeting,
	  Task: Task,
	  StoreDocument: StoreDocument,
	  WebForm: WebForm,
	  Wait: Wait$1,
	  Request: Request,
	  Rest: Rest$1,
	  OpenLine: OpenLine$1,
	  Zoom: Zoom
	};
	var Items = {
	  History: History,
	  HistoryActivity: HistoryActivity,
	  Comment: Comment$1,
	  Modification: Modification,
	  Mark: Mark$1,
	  Creation: Creation,
	  Restoration: Restoration,
	  Relation: Relation,
	  Link: Link,
	  Unlink: Unlink,
	  Email: Email$2,
	  Call: Call$2,
	  Meeting: Meeting$1,
	  Task: Task$1,
	  WebForm: WebForm$1,
	  Wait: Wait$2,
	  Document: Document,
	  Sender: Sender,
	  Bizproc: Bizproc,
	  Sms: Sms$1,
	  Request: Request$1,
	  Rest: Rest$2,
	  OpenLine: OpenLine$2,
	  Zoom: Zoom$1,
	  Conversion: Conversion,
	  Visit: Visit,
	  Scoring: Scoring,
	  OrderCreation: OrderCreation,
	  OrderModification: OrderModification,
	  StoreDocumentCreation: StoreDocumentCreation,
	  StoreDocumentModification: StoreDocumentModification,
	  FinalSummaryDocuments: FinalSummaryDocuments,
	  FinalSummary: FinalSummary,
	  ExternalNoticeModification: ExternalNoticeModification,
	  ExternalNoticeStatusModification: ExternalNoticeStatusModification,
	  OrderCheck: OrderCheck,
	  ScheduledBase: Scheduled,
	  Scheduled: ScheduledItems
	};
	var Animations = {
	  Item: Item$2,
	  ItemNew: ItemNew,
	  Expand: Expand,
	  Shift: Shift,
	  Comment: Comment$2,
	  Fasten: Fasten
	};

	exports.EncourageBuyProducts = component;
	exports.Notification = component$1;
	exports.DeliveryActivity = component$2;
	exports.DeliveryMessage = component$3;
	exports.DeliveryCalculation = component$4;
	exports.Manager = Manager;
	exports.Stream = Steam;
	exports.Streams = Streams;
	exports.Editor = Editor;
	exports.Editors = Editors;
	exports.Tools = Tools;
	exports.Types = types;
	exports.Action = Action;
	exports.Actions = Actions;
	exports.Item = Item$1;
	exports.Items = Items;
	exports.Animations = Animations;

}((this.BX.Crm.Timeline = this.BX.Crm.Timeline || {}),BX.Event,BX,BX,BX,BX,BX));
//# sourceMappingURL=timeline.bundle.js.map
