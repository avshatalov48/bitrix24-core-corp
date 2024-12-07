/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,main_core_events,main_popup,ui_buttons) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _entityTypeName = /*#__PURE__*/new WeakMap();
	var _entityId = /*#__PURE__*/new WeakMap();
	var _entityTypeTitle = /*#__PURE__*/new WeakMap();
	var _entityTitle = /*#__PURE__*/new WeakMap();
	var _isMy = /*#__PURE__*/new WeakMap();
	var _isHidden = /*#__PURE__*/new WeakMap();
	var _entityUrl = /*#__PURE__*/new WeakMap();
	var _relatedEntityTitle = /*#__PURE__*/new WeakMap();
	var _responsible = /*#__PURE__*/new WeakMap();
	var _communications = /*#__PURE__*/new WeakMap();
	var _matchIndex = /*#__PURE__*/new WeakMap();
	var _addCommunicationValue = /*#__PURE__*/new WeakSet();
	var _addCommunicationList = /*#__PURE__*/new WeakSet();
	var ItemInfo = /*#__PURE__*/function () {
	  function ItemInfo() {
	    babelHelpers.classCallCheck(this, ItemInfo);
	    _classPrivateMethodInitSpec(this, _addCommunicationList);
	    _classPrivateMethodInitSpec(this, _addCommunicationValue);
	    _classPrivateFieldInitSpec(this, _entityTypeName, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _entityId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _entityTypeTitle, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _entityTitle, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _isMy, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _isHidden, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _entityUrl, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _relatedEntityTitle, {
	      writable: true,
	      value: ""
	    });
	    _classPrivateFieldInitSpec(this, _responsible, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _communications, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _matchIndex, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _communications, {
	      phone: [],
	      email: []
	    });
	    babelHelpers.classPrivateFieldSet(this, _matchIndex, {
	      phone: [],
	      email: []
	    });
	    babelHelpers.classPrivateFieldSet(this, _responsible, {
	      id: 0,
	      fullName: "",
	      profileUrl: "",
	      photoUrl: ""
	    });
	  }
	  babelHelpers.createClass(ItemInfo, [{
	    key: "toPlainObject",
	    value: function toPlainObject() {
	      return {
	        entityTypeName: babelHelpers.classPrivateFieldGet(this, _entityTypeName),
	        entityId: babelHelpers.classPrivateFieldGet(this, _entityId),
	        entityTypeTitle: babelHelpers.classPrivateFieldGet(this, _entityTypeTitle),
	        entityTitle: babelHelpers.classPrivateFieldGet(this, _entityTitle),
	        isMy: babelHelpers.classPrivateFieldGet(this, _isMy),
	        isHidden: babelHelpers.classPrivateFieldGet(this, _isHidden),
	        entityUrl: babelHelpers.classPrivateFieldGet(this, _entityUrl),
	        relatedEntityTitle: babelHelpers.classPrivateFieldGet(this, _relatedEntityTitle),
	        responsible: babelHelpers.classPrivateFieldGet(this, _responsible),
	        communications: babelHelpers.classPrivateFieldGet(this, _communications),
	        matchIndex: babelHelpers.classPrivateFieldGet(this, _matchIndex)
	      };
	    }
	  }, {
	    key: "addPhones",
	    value: function addPhones(values, matchIndex) {
	      var matchIndexPhone = BX.prop.getArray(matchIndex, "PHONE", []);
	      _classPrivateMethodGet(this, _addCommunicationList, _addCommunicationList2).call(this, "phone", values, matchIndexPhone);
	    }
	  }, {
	    key: "addEmails",
	    value: function addEmails(values, matchIndex) {
	      var matchIndexEmail = BX.prop.getArray(matchIndex, "EMAIL", []);
	      _classPrivateMethodGet(this, _addCommunicationList, _addCommunicationList2).call(this, "email", values, matchIndexEmail);
	    }
	  }, {
	    key: "entityTypeName",
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _entityTypeName, value);
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _entityTypeName);
	    }
	  }, {
	    key: "entityId",
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _entityId, value);
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _entityId);
	    }
	  }, {
	    key: "entityTypeTitle",
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _entityTypeTitle, value);
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _entityTypeTitle);
	    }
	  }, {
	    key: "entityTitle",
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _entityTitle, value);
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _entityTitle);
	    }
	  }, {
	    key: "isMy",
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _isMy, value);
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _isMy);
	    }
	  }, {
	    key: "isHidden",
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _isHidden, value);
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _isHidden);
	    }
	  }, {
	    key: "entityUrl",
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _entityUrl, value);
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _entityUrl);
	    }
	  }, {
	    key: "relatedEntityTitle",
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _relatedEntityTitle, value);
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _relatedEntityTitle);
	    }
	  }, {
	    key: "responsible",
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _responsible, value);
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _responsible);
	    }
	  }]);
	  return ItemInfo;
	}();
	function _addCommunicationValue2(communicationType, value, isMatched) {
	  if (babelHelpers.classPrivateFieldGet(this, _communications)[communicationType].indexOf(value) < 0) {
	    if (isMatched) {
	      babelHelpers.classPrivateFieldGet(this, _matchIndex)[communicationType].push(babelHelpers.classPrivateFieldGet(this, _communications)[communicationType].length);
	    }
	    babelHelpers.classPrivateFieldGet(this, _communications)[communicationType].push(value);
	  }
	}
	function _addCommunicationList2(communicationType, list, matchIndex) {
	  for (var i = 0; i < list.length; i++) {
	    _classPrivateMethodGet(this, _addCommunicationValue, _addCommunicationValue2).call(this, communicationType, list[i], matchIndex.includes(i.toString()));
	  }
	}
	var _handleWindowResize = /*#__PURE__*/new WeakMap();
	var _getPopupBackgroundColor = /*#__PURE__*/new WeakSet();
	var _renderResponsible = /*#__PURE__*/new WeakSet();
	var _renderAddButton = /*#__PURE__*/new WeakSet();
	var SummaryList = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SummaryList, _EventEmitter);
	  function SummaryList() {
	    var _this;
	    babelHelpers.classCallCheck(this, SummaryList);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SummaryList).call(this));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _renderAddButton);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _renderResponsible);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getPopupBackgroundColor);
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _handleWindowResize, {
	      writable: true,
	      value: void 0
	    });
	    _this.setEventNamespace('crm.entity-editor.summary-list.close');
	    _this.id = '';
	    _this.popupId = '';
	    _this.settings = {};
	    _this.anchor = null;
	    _this.wrapper = null;
	    _this.clientSearchBox = null;
	    _this.enableEntitySelect = false;
	    _this.items = [];
	    _this.padding = 0;
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _handleWindowResize, null);
	    return _this;
	  }
	  babelHelpers.createClass(SummaryList, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this.id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this.popupId = this.id + "_popup";
	      if (main_core.Type.isPlainObject(settings)) {
	        this.settings = settings;
	        this.anchor = BX.prop.getElementNode(settings, "anchor", null);
	        this.wrapper = BX.prop.getElementNode(settings, "wrapper", null);
	        this.clientSearchBox = BX.prop.get(settings, "clientSearchBox", null);
	        this.enableEntitySelect = BX.prop.getBoolean(settings, "enableEntitySelect", false);
	      }
	      this.padding = BX.prop.getInteger(settings, 'padding', 11);
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      var _this2 = this;
	      var popup = main_popup.PopupManager.create({
	        id: this.popupId,
	        cacheable: false,
	        padding: this.padding,
	        contentPadding: 0,
	        content: this.getLayout(),
	        closeIcon: {
	          top: '11px',
	          right: '5px'
	        },
	        borderRadius: '12px',
	        closeByEsc: false,
	        background: _classPrivateMethodGet(this, _getPopupBackgroundColor, _getPopupBackgroundColor2).call(this),
	        animation: {
	          closeAnimationType: 'animation',
	          showClassName: 'crm-dups-popup-open',
	          closeClassName: 'crm-dups-popup-close'
	        }
	      });
	      if (!babelHelpers.classPrivateFieldGet(this, _handleWindowResize)) {
	        babelHelpers.classPrivateFieldSet(this, _handleWindowResize, this.adjustPosition.bind(this));
	        main_core.bind(window, 'resize', babelHelpers.classPrivateFieldGet(this, _handleWindowResize));
	      }
	      popup.subscribe('onDestroy', function () {
	        _this2.emit('close', _this2);
	      });
	      popup.subscribe("onFirstShow", function (event) {
	        event.target.getZIndexComponent().subscribe("onZIndexChange", function (event) {
	          if (event.target.getZIndex() !== 850) {
	            event.target.setZIndex(850);
	          }
	        });
	      });
	      popup.show();
	      this.adjustPosition();
	    }
	  }, {
	    key: "getController",
	    value: function getController() {
	      var controller = BX.prop.get(this.settings, "controller", null);
	      return controller instanceof BX.CrmDupController ? controller : null;
	    }
	  }, {
	    key: "getTargetEntityTypeName",
	    value: function getTargetEntityTypeName() {
	      var controller = this.getController();
	      return controller ? controller.getEntityTypeName() : "";
	    }
	  }, {
	    key: "getDuplicateData",
	    value: function getDuplicateData() {
	      var controller = this.getController();
	      return controller ? controller.getDuplicateData() : {};
	    }
	  }, {
	    key: "getGroup",
	    value: function getGroup(groupId) {
	      var controller = main_core.Type.isStringFilled(groupId) ? this.getController() : null;
	      return controller ? controller.getGroup(groupId) : null;
	    }
	  }, {
	    key: "getGroupSummaryTitle",
	    value: function getGroupSummaryTitle(groupId, groupData) {
	      if (main_core.Type.isPlainObject(groupData) && groupData.hasOwnProperty("totalText") && main_core.Type.isStringFilled(groupData['totalText'])) {
	        var group = this.getGroup(groupId);
	        var title = group ? group.getSummaryTitle() : "";
	        if (main_core.Type.isStringFilled(title)) {
	          return groupData['totalText'] + " " + title;
	        }
	      }
	      return "";
	    }
	  }, {
	    key: "getLayoutData",
	    value: function getLayoutData() {
	      var result = {
	        title: "",
	        groups: []
	      };
	      var data = this.getDuplicateData();
	      var totalItemCount = 0;
	      for (var groupId in data) {
	        if (!data.hasOwnProperty(groupId)) {
	          continue;
	        }
	        var groupData = main_core.Type.isPlainObject(data[groupId]) ? data[groupId] : {};
	        var items = main_core.Type.isArray(groupData["items"]) ? groupData["items"] : [];
	        var groupInfo = {
	          title: this.getGroupSummaryTitle(groupId, groupData),
	          items: []
	        };
	        var entityTypeIdMap = [];
	        for (var i = 0; i < items.length; i++) {
	          var item = items[i];
	          var entities = main_core.Type.isArray(item["ENTITIES"]) ? item["ENTITIES"] : [];
	          for (var j = 0; j < entities.length; j++) {
	            var entity = entities[j];
	            var entityTypeId = this.getEntityTypeId(entity);
	            if (!BX.CrmEntityType.isDefined(entityTypeId)) {
	              continue;
	            }
	            var entityTypeName = BX.CrmEntityType.resolveName(entityTypeId);
	            var entityId = this.getEntityId(entity);
	            var needAdd = false;
	            if (!entityTypeIdMap.hasOwnProperty(entityTypeName)) {
	              entityTypeIdMap[entityTypeName] = [entityId];
	              needAdd = true;
	            } else {
	              var isExists = entityTypeIdMap[entityTypeName].indexOf(entityId) >= 0;
	              if (!isExists) {
	                entityTypeIdMap[entityTypeName].push(entityId);
	                needAdd = true;
	              }
	            }
	            if (needAdd) {
	              groupInfo.items.push(this.prepareItemInfo(entity));
	            }
	          }
	        }
	        if (groupInfo.items.length > 0) {
	          totalItemCount += groupInfo.items.length;
	          result.groups.push(groupInfo);
	        }
	      }
	      result.title = main_core.Loc.getMessage("DUPLICATE_SUMMARY_LIST_TOTAL_COUNT_TITLE", {
	        "#COUNT#": totalItemCount
	      });
	      return result;
	    }
	  }, {
	    key: "getEntityTypeId",
	    value: function getEntityTypeId(entity) {
	      return main_core.Type.isStringFilled(entity["ENTITY_TYPE_ID"]) ? parseInt(entity["ENTITY_TYPE_ID"]) : 0;
	    }
	  }, {
	    key: "getEntityId",
	    value: function getEntityId(entity) {
	      return main_core.Type.isStringFilled(entity["ENTITY_ID"]) ? parseInt(entity["ENTITY_ID"]) : 0;
	    }
	  }, {
	    key: "prepareItemInfo",
	    value: function prepareItemInfo(entity) {
	      var itemInfo = new ItemInfo();
	      var entityTypeId = this.getEntityTypeId(entity);
	      itemInfo.entityTypeName = BX.CrmEntityType.resolveName(entityTypeId);
	      itemInfo.entityId = this.getEntityId(entity);
	      itemInfo.entityTypeTitle = BX.prop.getString(entity, 'CATEGORY_NAME', BX.CrmEntityType.getCaption(entityTypeId));
	      itemInfo.entityTitle = BX.prop.getString(entity, "TITLE", "");
	      itemInfo.isMy = entityTypeId === BX.CrmEntityType.enumeration.company && BX.prop.getString(entity, "IS_MY_COMPANY", "") === "Y";
	      itemInfo.isHidden = BX.prop.getString(entity, "IS_HIDDEN", "") === "Y";
	      itemInfo.entityUrl = BX.prop.getString(entity, "URL", "");
	      itemInfo.responsible = {
	        id: BX.prop.getInteger(entity, "RESPONSIBLE_ID", 0),
	        fullName: BX.prop.getString(entity, "RESPONSIBLE_FULL_NAME", ""),
	        profileUrl: BX.prop.getString(entity, "RESPONSIBLE_URL", "#"),
	        photoUrl: BX.prop.getString(entity, "RESPONSIBLE_PHOTO_URL", "#")
	      };
	      var matchIndex = BX.prop.getObject(entity, "MATCH_INDEX", {
	        PHONE: [],
	        EMAIL: []
	      });
	      itemInfo.addPhones(BX.prop.getArray(entity, "PHONE", []), matchIndex);
	      itemInfo.addEmails(BX.prop.getArray(entity, "EMAIL", []), matchIndex);
	      return itemInfo.toPlainObject();
	    }
	  }, {
	    key: "renderItemDetails",
	    value: function renderItemDetails(item) {
	      var content = "";
	      var communications = item["communications"];
	      var matchIndex = BX.prop.getObject(item, "matchIndex", {
	        phone: [],
	        email: []
	      });
	      ["phone", "email"].forEach(function (type) {
	        var maxItems = 5;
	        var needDots = false;
	        if (!needDots && communications[type].length > maxItems) {
	          needDots = true;
	        }
	        if (communications[type].length > 0) {
	          for (var i = 0; i < communications[type].length; i++) {
	            if (i >= maxItems) {
	              break;
	            }
	            if (content.length > 0) {
	              content += ", ";
	            }
	            var isMatched = matchIndex[type].includes(i);
	            var value = main_core.Text.encode(communications[type][i]);
	            content += isMatched ? "<span class=\"crm-dups-item-details-matched\">" + value + "</span>" : value;
	          }
	          if (needDots) {
	            content += ", ...";
	          }
	        }
	      });
	      return content;
	    }
	  }, {
	    key: "renderHiddenItem",
	    value: function renderHiddenItem(item) {
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-dups-item\">\n\t\t\t\t<div class=\"crm-dups-item-top\">\n\t\t\t\t\t<div class=\"crm-dups-item-header\">\n\t\t\t\t\t\t<div class=\"crm-dups-item-type\">", "</div>\n\t\t\t\t\t\t<span class=\"crm-dups-item-title-hidden\">", "</span>\n\t\t\t\t\t\t<div class=\"crm-dups-item-rel-title hidden\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-dups-item-photo bx-ui-tooltip-photo\">\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tclass=\"bx-ui-tooltip-info-data-photo no-photo\"\n\t\t\t\t\t\t\tstyle=\"width: 20px; height: 20px;\"\n\t\t\t\t\t\t></span>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-dups-item-details\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(item["entityTypeTitle"]), main_core.Text.encode(item["entityTitle"]));
	    }
	  }, {
	    key: "renderVisibleItem",
	    value: function renderVisibleItem(item) {
	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-dups-item\">\n\t\t\t\t<div class=\"crm-dups-item-top\">\n\t\t\t\t\t<div class=\"crm-dups-item-header\">\n\t\t\t\t\t\t<div class=\"crm-dups-item-type\">", "</div>\n\t\t\t\t\t\t<a\n\t\t\t\t\t\t\thref=\"", "\"\n\t\t\t\t\t\t\tclass=\"crm-dups-item-title\">", "</a>\n\t\t\t\t\t\t<div class=\"crm-dups-item-rel-title hidden\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-dups-item-details\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(item["entityTypeTitle"]), main_core.Text.encode(item["entityUrl"]), main_core.Text.encode(item["entityTitle"]), _classPrivateMethodGet(this, _renderResponsible, _renderResponsible2).call(this, item["responsible"]), this.renderItemDetails(item), _classPrivateMethodGet(this, _renderAddButton, _renderAddButton2).call(this, {
	        "type": item["entityTypeName"],
	        "id": item["entityId"],
	        "title": item["entityTitle"],
	        "isMy": item["isMy"]
	      }));
	    }
	  }, {
	    key: "renderItem",
	    value: function renderItem(item) {
	      return item["isHidden"] ? this.renderHiddenItem(item) : this.renderVisibleItem(item);
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      var _this3 = this;
	      var layoutData = this.getLayoutData();
	      if (!(main_core.Type.isStringFilled(layoutData["title"]) && main_core.Type.isArrayFilled(layoutData["groups"]))) {
	        return "";
	      }
	      return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-dups-wrapper\">\n\t\t\t\t<div class=\"crm-dups-header\">", "</div>\n\t\t\t\t<div class=\"crm-dups-list\">", "</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(layoutData["title"]), layoutData["groups"].map(function (group) {
	        return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"crm-dups-group\">\n\t\t\t\t\t\t<div class=\"crm-dups-group-header\">", "</div>\n\t\t\t\t\t\t<div class=\"crm-dups-group-items\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), main_core.Text.encode(group["title"]), group["items"].map(function (item) {
	          return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t"])), _this3.renderItem(item));
	        }));
	      }));
	    }
	  }, {
	    key: "adjustPosition",
	    value: function adjustPosition() {
	      var popup = main_popup.PopupManager.getPopupById(this.popupId);
	      if (!popup || !popup.isShown() || !main_core.Type.isDomNode(this.anchor) || !main_core.Type.isDomNode(this.wrapper)) {
	        return;
	      }
	      var wrapperRect = this.wrapper.getBoundingClientRect();
	      var itemRect = this.anchor.getBoundingClientRect();
	      var viewRect = document.documentElement.getBoundingClientRect();
	      var viewTop = -viewRect.top;
	      var viewBottom = viewRect.height - viewRect.top;
	      var offsetLeft = -viewRect.left + wrapperRect.left + wrapperRect.width + this.padding;
	      var popupHeight = popup.getPopupContainer().clientHeight;
	      var popupVerticalPosition;
	      var angleOffset;
	      var itemVerticalCenter = viewTop + itemRect.top + itemRect.height / 2;
	      if (itemVerticalCenter < viewTop) {
	        popupVerticalPosition = viewTop + itemRect.top - this.padding;
	        angleOffset = this.padding + itemRect.height / 2;
	      } else if (itemVerticalCenter > viewBottom) {
	        popupVerticalPosition = viewTop + itemRect.bottom + this.padding - popupHeight;
	        angleOffset = popupHeight - this.padding - itemRect.height / 2;
	      } else if (popupHeight < viewRect.height) {
	        var verticalOffset = 0;
	        popupVerticalPosition = itemVerticalCenter - popupHeight / 2;
	        if (popupVerticalPosition < viewTop) {
	          verticalOffset = viewTop - popupVerticalPosition;
	        } else if (viewBottom < popupVerticalPosition + popupHeight) {
	          verticalOffset = viewBottom - popupVerticalPosition - popupHeight;
	        }
	        popupVerticalPosition += verticalOffset;
	        angleOffset = itemVerticalCenter - popupVerticalPosition;
	      } else {
	        popupVerticalPosition = viewTop;
	        angleOffset = itemVerticalCenter - popupVerticalPosition;
	        if (angleOffset < 0) {
	          angleOffset += popupHeight;
	        }
	      }
	      angleOffset -= this.padding;
	      popup.setBindElement({
	        left: offsetLeft,
	        top: popupVerticalPosition
	      });
	      popup.setAngle({
	        position: "left",
	        offset: angleOffset
	      });
	      popup.adjustPosition();
	      setTimeout(function () {
	        return popup.getZIndexComponent().setZIndex(850);
	      }, 0);
	    }
	  }, {
	    key: "isShown",
	    value: function isShown() {
	      var popup = main_popup.PopupManager.getPopupById(this.popupId);
	      return popup && popup.isShown();
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      var popup = main_popup.PopupManager.getPopupById(this.popupId);
	      popup ? popup.close() : null;
	      main_core.unbind(document, 'resize', babelHelpers.classPrivateFieldGet(this, _handleWindowResize));
	      babelHelpers.classPrivateFieldSet(this, _handleWindowResize, null);
	    }
	  }, {
	    key: "onAddButtonClick",
	    value: function onAddButtonClick(context) {
	      if (this.clientSearchBox) {
	        main_core_events.EventEmitter.emit(this.clientSearchBox, 'onSelectEntityExternal', context);
	      }
	      this.close();
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new SummaryList();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return SummaryList;
	}(main_core_events.EventEmitter);
	function _getPopupBackgroundColor2() {
	  var bodyStyles = getComputedStyle(document.body);
	  return (bodyStyles === null || bodyStyles === void 0 ? void 0 : bodyStyles.getPropertyValue("--ui-color-background-primary")) || '#FFFFFF';
	}
	function _renderResponsible2(options) {
	  var isPhoto = main_core.Type.isStringFilled(options["photoUrl"]) && options["photoUrl"] !== "#";
	  var noPhotoClass = isPhoto ? "" : " no-photo";
	  var backgroundStyle = isPhoto ? " background: url('".concat(main_core.Text.encode(options["photoUrl"]), "') no-repeat center; background-size: cover;") : "";
	  var responsibleContainer = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-dups-item-photo bx-ui-tooltip-photo\">\n\t\t\t<a\n\t\t\t\thref=\"", "\"\n\t\t\t\tclass=\"bx-ui-tooltip-info-data-photo", "\"\n\t\t\t\tstyle=\"width: 20px; height: 20px;", "\"\n\t\t\t\tdata-hint=\"", "\"\n\t\t\t\tdata-hint-no-icon\n\t\t\t></a>\n\t\t</div>"])), main_core.Text.encode(options["profileUrl"]), noPhotoClass, backgroundStyle, main_core.Text.encode(options["fullName"]));
	  BX.UI.Hint.popupParameters = {
	    padding: 10
	  };
	  BX.UI.Hint.init(responsibleContainer);
	  return responsibleContainer;
	}
	function _renderAddButton2(options) {
	  var _this4 = this;
	  if (!this.enableEntitySelect || options.hasOwnProperty("isMy") && options["isMy"] || main_core.Type.isPlainObject(options) && options.hasOwnProperty("type") && options["type"] !== this.getTargetEntityTypeName()) {
	    return "";
	  }
	  var btn = new ui_buttons.Button({
	    round: true,
	    color: ui_buttons.Button.Color.LIGHT_BORDER,
	    size: ui_buttons.Button.Size.EXTRA_SMALL,
	    text: main_core.Loc.getMessage('DUPLICATE_SUMMARY_LIST_ITEM_ADD_BUTTON'),
	    context: {
	      type: options["type"],
	      id: options["id"],
	      title: BX.prop.getString(options, "title", "")
	    },
	    onclick: function onclick(btn, e) {
	      e.stopPropagation();
	      _this4.onAddButtonClick(btn.getContext());
	    }
	  });
	  return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-dups-item-add-btn\">", "</div>"])), btn.render());
	}

	exports.SummaryList = SummaryList;

}((this.BX.Crm.Duplicate = this.BX.Crm.Duplicate || {}),BX,BX.Event,BX.Main,BX.UI));
//# sourceMappingURL=summary-list.bundle.js.map
