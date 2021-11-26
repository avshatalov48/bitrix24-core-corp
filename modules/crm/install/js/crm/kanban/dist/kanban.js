this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core_events,ui_notification,main_popup,main_core) {
	'use strict';

	var _queue = /*#__PURE__*/new WeakMap();

	var _grid = /*#__PURE__*/new WeakMap();

	var _isProgress = /*#__PURE__*/new WeakMap();

	var _isFreeze = /*#__PURE__*/new WeakMap();

	var PullQueue = /*#__PURE__*/function () {
	  function PullQueue(grid) {
	    babelHelpers.classCallCheck(this, PullQueue);

	    _queue.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _grid.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _isProgress.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _isFreeze.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _grid, grid);
	    babelHelpers.classPrivateFieldSet(this, _queue, new Set());
	    babelHelpers.classPrivateFieldSet(this, _isProgress, false);
	    babelHelpers.classPrivateFieldSet(this, _isFreeze, false);
	  }

	  babelHelpers.createClass(PullQueue, [{
	    key: "loadItem",
	    value: function loadItem(isForce) {
	      var _this = this;

	      setTimeout(function () {
	        isForce = isForce || false;

	        if (babelHelpers.classPrivateFieldGet(_this, _isProgress) && !isForce) {
	          return;
	        }

	        if (document.hidden || _this.isOverflow() || _this.isFreezed()) {
	          return;
	        }

	        var id = _this.pop();

	        if (id) {
	          var loadNextOnSuccess = function loadNextOnSuccess(response) {
	            if (_this.peek()) {
	              _this.loadItem(true);
	            }

	            babelHelpers.classPrivateFieldSet(_this, _isProgress, false);
	          };

	          var doNothingOnError = function doNothingOnError(err) {};

	          babelHelpers.classPrivateFieldSet(_this, _isProgress, true);
	          babelHelpers.classPrivateFieldGet(_this, _grid).loadNew(id, false, true, true).then(loadNextOnSuccess, doNothingOnError);
	        }
	      }, 1000);
	    }
	  }, {
	    key: "push",
	    value: function push(id) {
	      id = parseInt(id, 10);

	      if (babelHelpers.classPrivateFieldGet(this, _queue).has(id)) {
	        babelHelpers.classPrivateFieldGet(this, _queue).delete(id);
	      }

	      babelHelpers.classPrivateFieldGet(this, _queue).add(id);
	      return this;
	    }
	  }, {
	    key: "pop",
	    value: function pop() {
	      var values = babelHelpers.classPrivateFieldGet(this, _queue).values();
	      var first = values.next();

	      if (first.value !== undefined) {
	        babelHelpers.classPrivateFieldGet(this, _queue).delete(first.value);
	      }

	      return first.value;
	    }
	  }, {
	    key: "peek",
	    value: function peek() {
	      var values = babelHelpers.classPrivateFieldGet(this, _queue).values();
	      var first = values.next();
	      return first.value !== undefined ? first.value : null;
	    }
	  }, {
	    key: "delete",
	    value: function _delete(id) {
	      babelHelpers.classPrivateFieldGet(this, _queue).delete(id);
	    }
	  }, {
	    key: "has",
	    value: function has(id) {
	      return babelHelpers.classPrivateFieldGet(this, _queue).has(id);
	    }
	  }, {
	    key: "clear",
	    value: function clear() {
	      babelHelpers.classPrivateFieldGet(this, _queue).clear();
	    }
	  }, {
	    key: "isOverflow",
	    value: function isOverflow() {
	      var MAX_PENDING_ITEMS = 10;
	      return babelHelpers.classPrivateFieldGet(this, _queue).size > MAX_PENDING_ITEMS;
	    }
	  }, {
	    key: "freeze",
	    value: function freeze() {
	      babelHelpers.classPrivateFieldSet(this, _isFreeze, true);
	    }
	  }, {
	    key: "unfreeze",
	    value: function unfreeze() {
	      babelHelpers.classPrivateFieldSet(this, _isFreeze, false);
	    }
	  }, {
	    key: "isFreezed",
	    value: function isFreezed() {
	      return babelHelpers.classPrivateFieldGet(this, _isFreeze);
	    }
	  }]);
	  return PullQueue;
	}();

	var PullManager = /*#__PURE__*/function () {
	  function PullManager(grid) {
	    babelHelpers.classCallCheck(this, PullManager);
	    this.grid = grid;
	    this.queue = new PullQueue(this.grid);

	    if (main_core.Type.isString(grid.getData().moduleId) && grid.getData().userId > 0) {
	      this.init();
	    }

	    this.bindEvents();
	  }

	  babelHelpers.createClass(PullManager, [{
	    key: "init",
	    value: function init() {
	      var _this = this;

	      main_core.Event.ready(function () {
	        var Pull = BX.PULL;

	        if (!Pull) {
	          console.error('pull is not initialized');
	          return;
	        }

	        Pull.subscribe({
	          moduleId: _this.grid.getData().moduleId,
	          command: _this.grid.getData().pullTag,
	          callback: function callback(params) {
	            if (main_core.Type.isString(params.eventName)) {
	              if (_this.queue.isOverflow()) {
	                return;
	              }

	              if (params.eventName === 'ITEMUPDATED') {
	                _this.onPullItemUpdated(params);
	              } else if (params.eventName === 'ITEMADDED') {
	                _this.onPullItemAdded(params);
	              } else if (params.eventName === 'ITEMDELETED') {
	                _this.onPullItemDeleted(params);
	              } else if (params.eventName === 'STAGEADDED') {
	                _this.onPullStageAdded(params);
	              } else if (params.eventName === 'STAGEDELETED') {
	                _this.onPullStageDeleted(params);
	              } else if (params.eventName === 'STAGEUPDATED') {
	                _this.onPullStageUpdated(params);
	              }
	            }
	          }
	        });
	        Pull.extendWatch(_this.grid.getData().pullTag);
	        main_core.Event.bind(document, 'visibilitychange', function () {
	          if (!document.hidden) {
	            _this.onTabActivated();
	          }
	        });
	      });
	    }
	  }, {
	    key: "onPullItemUpdated",
	    value: function onPullItemUpdated(params) {
	      if (this.updateItem(params)) {
	        this.queue.loadItem();
	      }
	    }
	  }, {
	    key: "updateItem",
	    value: function updateItem(params) {
	      var item = this.grid.getItem(params.item.id);
	      var paramsItem = params.item;

	      if (item) {
	        var oldPrice = parseFloat(item.data.price);
	        var oldColumnId = item.data.columnId;

	        for (var key in paramsItem.data) {
	          if (key in item.data) {
	            item.data[key] = paramsItem.data[key];
	          }
	        }

	        item.rawData = paramsItem.rawData;
	        item.setActivityExistInnerHtml();
	        item.useAnimation = true;
	        item.setChangedInPullRequest();
	        this.grid.resetMultiSelectMode();
	        this.grid.insertItem(item);
	        var newColumn = this.grid.getColumn(paramsItem.data.columnId);
	        var newPrice = parseFloat(paramsItem.data.price);

	        if (oldColumnId !== paramsItem.data.columnId) {
	          var oldColumn = this.grid.getColumn(oldColumnId);
	          oldColumn.decPrice(oldPrice);
	          oldColumn.renderSubTitle();
	          newColumn.incPrice(newPrice);
	          newColumn.renderSubTitle();
	        } else {
	          if (oldPrice < newPrice) {
	            newColumn.incPrice(newPrice - oldPrice);
	            newColumn.renderSubTitle();
	          } else if (oldPrice > newPrice) {
	            newColumn.decPrice(oldPrice - newPrice);
	            newColumn.renderSubTitle();
	          }
	        }

	        item.columnId = paramsItem.data.columnId;
	        this.queue.push(item.id);
	        return true;
	      }

	      this.onPullItemAdded(params);
	      return false;
	    }
	  }, {
	    key: "onPullItemAdded",
	    value: function onPullItemAdded(params) {
	      this.addItem(params);
	      this.queue.loadItem();
	    }
	  }, {
	    key: "addItem",
	    value: function addItem(params) {
	      var oldItem = this.grid.getItem(params.item.id);

	      if (oldItem) {
	        return;
	      }

	      this.grid.addItemTop(params.item);
	      this.queue.push(params.item.id);
	    }
	  }, {
	    key: "onPullItemDeleted",
	    value: function onPullItemDeleted(params) {
	      if (!main_core.Type.isPlainObject(params.item)) {
	        return;
	      }
	      /**
	       * Delay so that the element has time to be rendered before deletion,
	       * if an event for changing the element came before. Ticket #141983
	       */


	      var delay = this.queue.has(params.item.id) ? 5000 : 0;
	      setTimeout(function () {
	        this.queue.delete(params.item.id);
	        this.grid.removeItem(params.item.id);
	        var column = this.grid.getColumn(params.item.data.columnId);
	        column.decPrice(params.item.data.price);
	        column.renderSubTitle();
	      }.bind(this), delay);
	    }
	  }, {
	    key: "onPullStageAdded",
	    value: function onPullStageAdded(params) {
	      this.grid.onApplyFilter();
	    }
	  }, {
	    key: "onPullStageDeleted",
	    value: function onPullStageDeleted(params) {
	      this.grid.removeColumn(params.stage.id);
	    }
	  }, {
	    key: "onPullStageUpdated",
	    value: function onPullStageUpdated(params) {
	      this.grid.onApplyFilter();
	    }
	  }, {
	    key: "onTabActivated",
	    value: function onTabActivated() {
	      if (this.queue.isOverflow()) {
	        this.showOutdatedDataDialog();
	      } else if (this.queue.peek()) {
	        this.queue.loadItem();
	      }
	    }
	  }, {
	    key: "showOutdatedDataDialog",
	    value: function showOutdatedDataDialog() {
	      var _this2 = this;

	      if (!this.notifier) {
	        this.notifier = BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('CRM_KANBAN_NOTIFY_OUTDATED_DATA'),
	          closeButton: false,
	          autoHide: false,
	          actions: [{
	            title: main_core.Loc.getMessage('CRM_KANBAN_GRID_RELOAD'),
	            events: {
	              click: function click(event, balloon, action) {
	                balloon.close();

	                _this2.grid.reload();

	                _this2.queue.clear();
	              }
	            }
	          }]
	        });
	      } else {
	        this.notifier.show();
	      }
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this3 = this;

	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onOpen', function (event) {
	        if (_this3.isEntitySlider(event.data[0].slider)) {
	          _this3.queue.freeze();
	        }
	      });
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onClose', function (event) {
	        if (_this3.isEntitySlider(event.data[0].slider)) {
	          _this3.queue.unfreeze();

	          _this3.onTabActivated();
	        }
	      });
	    }
	  }, {
	    key: "isEntitySlider",
	    value: function isEntitySlider(slider) {
	      var sliderUrl = slider.getUrl();
	      var entityPath = this.grid.getData().entityPath;
	      var maskUrl = entityPath.replace(/\#([^\#]+)\#/, '([\\d]+)');
	      return new RegExp(maskUrl).test(sliderUrl);
	    }
	  }]);
	  return PullManager;
	}();

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5;
	var TYPE_VIEW = 'view';
	var TYPE_EDIT = 'edit';

	var FieldsSelector = /*#__PURE__*/function () {
	  function FieldsSelector(options) {
	    babelHelpers.classCallCheck(this, FieldsSelector);
	    this.popup = null;
	    this.fields = null;
	    this.options = options;
	    this.type = this.options.hasOwnProperty('type') ? this.options.type : TYPE_VIEW;
	    this.selectedFields = this.options.hasOwnProperty('selectedFields') ? this.options.selectedFields.slice(0) : [];
	  }

	  babelHelpers.createClass(FieldsSelector, [{
	    key: "show",
	    value: function show() {
	      if (!this.popup) {
	        this.popup = this.createPopup();
	      }

	      if (this.fields) {
	        this.popup.setContent(this.getFieldsLayout());
	      } else {
	        this.loadPopupContent(this.popup);
	      }

	      this.popup.show();
	    }
	  }, {
	    key: "createPopup",
	    value: function createPopup() {
	      var _this = this;

	      return main_popup.PopupManager.create({
	        id: 'kanban_custom_fields_' + this.type,
	        className: 'crm-kanban-popup-field',
	        titleBar: BX.message('CRM_KANBAN_CUSTOM_FIELDS_' + this.type.toUpperCase()),
	        cacheable: false,
	        closeIcon: true,
	        lightShadow: true,
	        overlay: true,
	        draggable: true,
	        closeByEsc: true,
	        contentColor: 'white',
	        maxHeight: window.innerHeight - 50,
	        events: {
	          onClose: function onClose() {
	            return _this.popup = null;
	          }
	        },
	        buttons: [new BX.UI.SaveButton({
	          color: BX.UI.Button.Color.PRIMARY,
	          state: this.fields ? '' : BX.UI.Button.State.DISABLED,
	          onclick: function onclick() {
	            var selectedFields = _this.fields ? _this.fields.filter(function (field) {
	              return _this.selectedFields.indexOf(field.NAME) >= 0;
	            }) : [];

	            if (selectedFields.length) {
	              _this.popup.close();

	              _this.executeCallback(selectedFields);
	            } else {
	              ui_notification.UI.Notification.Center.notify({
	                content: main_core.Loc.getMessage('CRM_KANBAN_POPUP_AT_LEAST_ONE_FIELD'),
	                autoHide: true,
	                autoHideDelay: 2000
	              });
	            }
	          }
	        }), new BX.UI.CancelButton({
	          onclick: function onclick() {
	            _this.popup.close();
	          }
	        })]
	      });
	    }
	  }, {
	    key: "loadPopupContent",
	    value: function loadPopupContent(popup) {
	      var _this2 = this;

	      var loaderContainer = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-kanban-popup-field-loader\"></div>"])));
	      var loader = new BX.Loader({
	        target: loaderContainer,
	        size: 80
	      });
	      loader.show();
	      popup.setContent(loaderContainer);
	      BX.ajax.runComponentAction('bitrix:crm.kanban', 'getFields', {
	        mode: 'ajax',
	        data: {
	          entityType: this.options.entityTypeName,
	          viewType: this.type
	        }
	      }).then(function (response) {
	        loader.destroy();
	        _this2.fields = response.data;
	        popup.setContent(_this2.getFieldsLayout());
	        popup.getButtons().forEach(function (button) {
	          return button.setDisabled(false);
	        });
	        popup.adjustPosition();
	      }).catch(function (response) {
	        BX.Kanban.Utils.showErrorDialog(response.errors.pop().message);
	      });
	      return popup;
	    }
	  }, {
	    key: "getFieldsLayout",
	    value: function getFieldsLayout() {
	      var _this3 = this;

	      var sectionsWithFields = this.distributeFieldsBySections(this.fields);
	      var container = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-kanban-popup-field\"></div>"])));
	      this.getSections().forEach(function (section) {
	        var sectionName = section.name;

	        if (sectionsWithFields.hasOwnProperty(sectionName) && sectionsWithFields[sectionName].length) {
	          main_core.Dom.append(main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-kanban-popup-field-title\">", "</div>"])), main_core.Text.encode(section.title)), container);
	          main_core.Dom.append(main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-kanban-popup-field-wrapper\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>"])), sectionsWithFields[sectionName].map(function (field) {
	            var label = field.LABEL;

	            if (!label.length && section['elements'] && section['elements'][field.NAME] && section['elements'][field.NAME]['title'] && section['elements'][field.NAME]['title'].length) {
	              label = section['elements'][field.NAME]['title'];
	            }

	            var encodedLabel = main_core.Text.encode(label);
	            return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t\t<div class=\"crm-kanban-popup-field-item\" title=\"", "\">\n\t\t\t\t\t\t\t\t\t<input \n\t\t\t\t\t\t\t\t\t\tid=\"cf_", "\" \n\t\t\t\t\t\t\t\t\t\ttype=\"checkbox\" \n\t\t\t\t\t\t\t\t\t\tname=\"", "\"\n\t\t\t\t\t\t\t\t\t\tclass=\"crm-kanban-popup-field-item-input\"\n\t\t\t\t\t\t\t\t\t\tdata-label=\"", "\"\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t\t<label for=\"cf_", "\" class=\"crm-kanban-popup-field-item-label\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t\t\t</div>"])), encodedLabel, main_core.Text.encode(field.ID), main_core.Text.encode(field.NAME), encodedLabel, _this3.selectedFields.indexOf(field.NAME) >= 0 ? 'checked' : '', _this3.onFieldClick.bind(_this3), main_core.Text.encode(field.ID), encodedLabel);
	          })), container);
	        }
	      });
	      return container;
	    }
	  }, {
	    key: "distributeFieldsBySections",
	    value: function distributeFieldsBySections(fields) {
	      // remove ignored fields from result:
	      var ignoredFields = this.getIgnoredFields();
	      fields = fields.filter(function (item) {
	        return !(ignoredFields.hasOwnProperty(item.NAME) && ignoredFields[item.NAME]);
	      });
	      var fieldsBySections = {};
	      var defaultSectionName = '';
	      var sections = this.options.hasOwnProperty('sections') ? this.options.sections : [];

	      for (var i = 0; i < sections.length; i++) {
	        var section = sections[i];
	        var sectionName = section.name;
	        fieldsBySections[sectionName] = [];

	        if (main_core.Type.isPlainObject(section.elements)) {
	          fieldsBySections[sectionName] = this.filterFieldsByList(fields, section.elements);
	        } else if (section.hasOwnProperty('elementsRule')) {
	          fieldsBySections[sectionName] = this.filterFieldsByRule(fields, new RegExp(section.elementsRule));
	        } else if (section.elements === '*') {
	          defaultSectionName = sectionName;
	        }
	      }

	      if (defaultSectionName !== '') {
	        fieldsBySections[defaultSectionName] = this.filterNotUsedFields(fields, fieldsBySections);
	      }

	      return fieldsBySections;
	    }
	  }, {
	    key: "filterFieldsByList",
	    value: function filterFieldsByList(fields, whiteList) {
	      return fields.filter(function (item) {
	        return whiteList.hasOwnProperty(item.NAME);
	      });
	    }
	  }, {
	    key: "filterFieldsByRule",
	    value: function filterFieldsByRule(fields, rule) {
	      return fields.filter(function (item) {
	        return item.NAME.match(rule);
	      });
	    }
	  }, {
	    key: "filterNotUsedFields",
	    value: function filterNotUsedFields(fields, alreadyUsedFieldsBySection) {
	      var alreadyUsedFieldsNames = Object.values(alreadyUsedFieldsBySection).reduce(function (prevFields, sectionFields) {
	        return prevFields.concat(sectionFields.map(function (item) {
	          return item.NAME;
	        }));
	      }, []);
	      return fields.filter(function (item) {
	        return alreadyUsedFieldsNames.indexOf(item.NAME) < 0;
	      });
	    }
	  }, {
	    key: "getSections",
	    value: function getSections() {
	      return this.options.hasOwnProperty('sections') ? this.options.sections : [];
	    }
	  }, {
	    key: "getIgnoredFields",
	    value: function getIgnoredFields() {
	      var fields = Object.assign({}, this.options.ignoredFields);
	      var extraFields = [];

	      if (this.type === TYPE_EDIT) {
	        extraFields = ['ID', 'CLOSED', 'CLOSEDATE', 'DATE_CREATE', 'DATE_MODIFY', 'COMMENTS', 'OPPORTUNITY'];
	      } else {
	        extraFields = ['PHONE', 'EMAIL', 'WEB', 'IM'];
	      }

	      extraFields.forEach(function (fieldName) {
	        return fields[fieldName] = true;
	      });
	      return fields;
	    }
	  }, {
	    key: "executeCallback",
	    value: function executeCallback(selectedFields) {
	      if (!this.options.hasOwnProperty('onSelect') || !main_core.Type.isFunction(this.options.onSelect)) {
	        return;
	      }

	      var callbackPayload = {};
	      selectedFields.forEach(function (field) {
	        callbackPayload[field.NAME] = field.LABEL ? field.LABEL : '';
	      });
	      this.options.onSelect(callbackPayload);
	    }
	  }, {
	    key: "onFieldClick",
	    value: function onFieldClick(event) {
	      var fieldName = event.target.name;

	      if (event.target.checked && this.selectedFields.indexOf(fieldName) < 0) {
	        this.selectedFields.push(fieldName);
	      }

	      if (!event.target.checked && this.selectedFields.indexOf(fieldName) >= 0) {
	        this.selectedFields.splice(this.selectedFields.indexOf(fieldName), 1);
	      }
	    }
	  }]);
	  return FieldsSelector;
	}();

	exports.PullManager = PullManager;
	exports.FieldsSelector = FieldsSelector;

}((this.BX.Crm.Kanban = this.BX.Crm.Kanban || {}),BX.Event,BX,BX.Main,BX));
//# sourceMappingURL=kanban.js.map
