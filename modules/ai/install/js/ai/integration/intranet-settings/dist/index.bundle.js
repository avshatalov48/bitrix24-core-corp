/* eslint-disable */
this.BX = this.BX || {};
this.BX.AI = this.BX.AI || {};
this.BX.AI.Integration = this.BX.AI.Integration || {};
(function (exports,main_core,main_core_events,ui_alerts,ui_section,ai_ui_field_selectorfield,ui_formElements_view,ui_formElements_field) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _itemRelations = /*#__PURE__*/new WeakMap();
	var _itemFields = /*#__PURE__*/new WeakMap();
	var _onSaveCheckers = /*#__PURE__*/new WeakMap();
	var _buildGroup = /*#__PURE__*/new WeakSet();
	var _buildItem = /*#__PURE__*/new WeakSet();
	var _addField = /*#__PURE__*/new WeakSet();
	var _bindEvents = /*#__PURE__*/new WeakSet();
	var AiPage = /*#__PURE__*/function (_BaseSettingsPage) {
	  babelHelpers.inherits(AiPage, _BaseSettingsPage);
	  function AiPage() {
	    var _this;
	    babelHelpers.classCallCheck(this, AiPage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(AiPage).call(this));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _bindEvents);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _addField);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _buildItem);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _buildGroup);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "titlePage", '');
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "descriptionPage", '');
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _itemRelations, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _itemFields, {
	      writable: true,
	      value: {}
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _onSaveCheckers, {
	      writable: true,
	      value: []
	    });
	    _this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_AI');
	    _this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_AI_DESC');
	    return _this;
	  }
	  babelHelpers.createClass(AiPage, [{
	    key: "getType",
	    value: function getType() {
	      return 'ai';
	    }
	  }, {
	    key: "appendSections",
	    value: function appendSections(contentNode) {
	      var _this2 = this;
	      var groups = this.getValue('fields');
	      if (groups) {
	        this.isOpen = true;
	        for (var groupCode in groups) {
	          var section = _classPrivateMethodGet(this, _buildGroup, _buildGroup2).call(this, groups[groupCode]);
	          if (this.isOpen === true) {
	            this.isOpen = false;
	          }
	          if (section) {
	            section.renderTo(contentNode);
	          }
	          if (groups[groupCode].relations) {
	            groups[groupCode].relations.forEach(function (relation) {
	              babelHelpers.classPrivateFieldGet(_this2, _itemRelations).push(relation);
	            });
	          }
	        }
	      }
	      _classPrivateMethodGet(this, _bindEvents, _bindEvents2).call(this);
	    }
	  }]);
	  return AiPage;
	}(ui_formElements_field.BaseSettingsPage);
	function _buildGroup2(group) {
	  var _icon$set,
	    _icon$code,
	    _this3 = this;
	  if (!group.items) {
	    return;
	  }
	  var items = Object.values(group.items);
	  if (items.length <= 0) {
	    return;
	  }
	  var title = group.title,
	    helpdesk = group.helpdesk,
	    icon = group.icon;
	  main_core.Runtime.loadExtension((_icon$set = icon.set) !== null && _icon$set !== void 0 ? _icon$set : _classStaticPrivateFieldSpecGet(AiPage, AiPage, _groupIconDefaultSet));
	  var section = new ui_formElements_field.SettingsSection({
	    parent: this,
	    section: {
	      title: title,
	      titleIconClasses: 'ui-icon-set ' + ((_icon$code = icon.code) !== null && _icon$code !== void 0 ? _icon$code : _classStaticPrivateFieldSpecGet(AiPage, AiPage, _groupIconDefaultIcon)),
	      isOpen: this.isOpen
	    }
	  });
	  if (group.description) {
	    var description = group.description;
	    if (helpdesk) {
	      var helpdeskCode = 'redirect=detail&code=' + helpdesk;
	      description += ' <a href="javascript: void();"' + ' onclick="BX.PreventDefault(); top.BX.Helper.show(\'' + helpdeskCode + '\');"' + '>' + main_core.Loc.getMessage('INTRANET_SETTINGS_HELPDESK_LINK') + '</a>';
	    }
	    section.getSectionView().append(new ui_section.Row({
	      content: new ui_alerts.Alert({
	        row: {
	          separator: 'null'
	        },
	        text: description,
	        inline: true,
	        size: ui_alerts.AlertSize.SMALL,
	        color: ui_alerts.AlertColor.PRIMARY
	      }).getContainer()
	    }).render());
	  }
	  items.forEach(function (item) {
	    var row = _classPrivateMethodGet(_this3, _buildItem, _buildItem2).call(_this3, item);
	    if (row.getChildrenElements().length > 0) {
	      row.setParentElement(section);
	    }
	  });
	  return section;
	}
	function _buildItem2(item) {
	  var code = item.code,
	    type = item.type,
	    title = item.title,
	    header = item.header,
	    value = item.value,
	    options = item.options,
	    recommended = item.recommended;
	  var withOnSave = item.onSave && item.onSave.switcher;
	  var row = new ui_formElements_field.SettingsRow({
	    row: {
	      className: withOnSave ? '--with-on-save' : ''
	    }
	  });
	  var field = null;
	  if (type === 'boolean') {
	    field = new ui_formElements_view.Checker({
	      inputName: code,
	      title: title,
	      checked: value,
	      hintOn: header,
	      hintOff: header
	    });
	  } else if (type === 'list') {
	    if (options && value) {
	      var items = [];
	      var additionalItems = [];
	      for (var option in options) {
	        if (main_core.Type.isString(options[option])) {
	          items.push({
	            name: options[option],
	            value: option,
	            selected: option === value
	          });
	        } else if (main_core.Type.isPlainObject(options[option])) {
	          additionalItems.push(options[option]);
	        }
	      }
	      if (items.length > 0) {
	        field = new ai_ui_field_selectorfield.SelectorField({
	          inputName: code,
	          label: title,
	          name: code,
	          items: items,
	          additionalItems: additionalItems,
	          recommendedItems: recommended,
	          current: value
	        });
	      }
	    }
	  }
	  if (field) {
	    _classPrivateMethodGet(this, _addField, _addField2).call(this, code, field, row);
	  }
	  if (withOnSave) {
	    var onSaveField = new ui_formElements_view.Checker({
	      inputName: code + '_onsave',
	      title: item.onSave.switcher,
	      checked: false,
	      size: 'extra-small',
	      noMarginBottom: true
	    });
	    _classPrivateMethodGet(this, _addField, _addField2).call(this, code, onSaveField, row);
	    babelHelpers.classPrivateFieldGet(this, _onSaveCheckers).push(onSaveField);
	  }
	  return row;
	}
	function _addField2(code, field, row) {
	  row.addChild(new ui_formElements_field.SettingsField({
	    fieldView: field
	  }));
	  babelHelpers.classPrivateFieldGet(this, _itemFields)[code] = {
	    code: code,
	    field: field,
	    row: row
	  };
	}
	function _bindEvents2() {
	  var _this4 = this;
	  if (babelHelpers.classPrivateFieldGet(this, _onSaveCheckers).length > 0) {
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onBeforeSave', function () {
	      babelHelpers.classPrivateFieldGet(_this4, _onSaveCheckers).forEach(function (field) {
	        var _field$switcher;
	        (_field$switcher = field.switcher) === null || _field$switcher === void 0 ? void 0 : _field$switcher.check(false, false);
	      });
	    });
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _itemRelations).length > 0) {
	    babelHelpers.classPrivateFieldGet(this, _itemRelations).forEach(function (relation) {
	      var parent = babelHelpers.classPrivateFieldGet(_this4, _itemFields)[relation.parent];
	      if (parent && parent.field && parent.field instanceof ui_formElements_view.Checker) {
	        main_core_events.EventEmitter.subscribe(parent.field, 'change', function (event) {
	          var isActive = event.getData();
	          relation.children.forEach(function (child) {
	            var _babelHelpers$classPr, _babelHelpers$classPr2;
	            var node = (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(_this4, _itemFields)[child]) === null || _babelHelpers$classPr === void 0 ? void 0 : (_babelHelpers$classPr2 = _babelHelpers$classPr.row) === null || _babelHelpers$classPr2 === void 0 ? void 0 : _babelHelpers$classPr2.getRowView();
	            if (node) {
	              isActive ? node.show() : node.hide();
	            }
	          });
	        });
	      }
	    });
	  }
	}
	var _groupIconDefaultSet = {
	  writable: true,
	  value: 'ui.icon-set.main'
	};
	var _groupIconDefaultIcon = {
	  writable: true,
	  value: '--copilot-ai'
	};

	main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onExternalPageLoaded:ai', function () {
	  return new AiPage();
	});

}((this.BX.AI.Integration.IntranetSettings = this.BX.AI.Integration.IntranetSettings || {}),BX,BX.Event,BX.UI,BX.UI,BX.AI.UI.Field,BX.UI.FormElements,BX.UI.FormElements));
//# sourceMappingURL=index.bundle.js.map
