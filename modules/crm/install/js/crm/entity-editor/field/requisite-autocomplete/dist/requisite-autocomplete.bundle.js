this.BX = this.BX || {};
(function (exports,main_core_events,main_core,crm_entityEditor_field_requisite_autocomplete) {
	'use strict';

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-entity-editor-content-block\"></div>"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral([" <span class=\"tariff-lock\"></span>"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var EntityEditorRequisiteAutocomplete = /*#__PURE__*/function (_BX$UI$EntityEditorFi) {
	  babelHelpers.inherits(EntityEditorRequisiteAutocomplete, _BX$UI$EntityEditorFi);

	  function EntityEditorRequisiteAutocomplete() {
	    var _this;

	    babelHelpers.classCallCheck(this, EntityEditorRequisiteAutocomplete);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EntityEditorRequisiteAutocomplete).call(this));
	    _this._autocomplete = null;
	    _this._autocompleteData = null;
	    return _this;
	  }

	  babelHelpers.createClass(EntityEditorRequisiteAutocomplete, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      var params = this._schemeElement.getData();

	      var enabled = BX.prop.getBoolean(params, "enabled", false);
	      this._autocomplete = crm_entityEditor_field_requisite_autocomplete.RequisiteAutocompleteField.create(this.getName(), {
	        placeholderText: BX.prop.getString(params, "placeholder", ""),
	        enabled: enabled,
	        featureRestrictionCallback: BX.prop.getString(params, "featureRestrictionCallback", ''),
	        searchAction: 'crm.requisite.entity.search',
	        feedbackFormParams: BX.prop.getObject(params, "feedback_form", {}),
	        showFeedbackLink: !enabled
	      });

	      this._autocomplete.subscribe('onSelectValue', this.onSelectAutocompleteValue.bind(this));

	      this._autocomplete.subscribe('onClear', this.onClearAutocompleteValue.bind(this));
	    }
	  }, {
	    key: "createTitleMarker",
	    value: function createTitleMarker() {
	      if (this._mode === BX.UI.EntityEditorMode.view) {
	        return null;
	      }

	      var restrictionCallback = BX.prop.getString(this._schemeElement.getData(), "featureRestrictionCallback", '');

	      if (restrictionCallback === '') {
	        return babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorRequisiteAutocomplete.prototype), "createTitleMarker", this).call(this);
	      }

	      var lockIcon = main_core.Tag.render(_templateObject());
	      lockIcon.setAttribute('onclick', restrictionCallback);
	      return lockIcon;
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      if (this._hasLayout) {
	        return;
	      }

	      if (this._mode === BX.UI.EntityEditorMode.view) {
	        if (!this._wrapper) {
	          this._wrapper = BX.create("div");
	        }
	      } else {
	        this.ensureWrapperCreated({
	          classNames: ["ui-entity-editor-field-text"]
	        });
	        this.adjustWrapper();
	      }

	      if (!this.isNeedToDisplay()) {
	        this.registerLayout(options);
	        this._hasLayout = true;
	        return;
	      }

	      if (this.isDragEnabled()) {
	        main_core.Dom.append(this.createDragButton(), this._wrapper);
	      }

	      main_core.Dom.append(this.createTitleNode(this.getTitle()), this._wrapper);

	      if (this._mode === BX.UI.EntityEditorMode.edit) {
	        var autocompleteContainer = main_core.Tag.render(_templateObject2());

	        this._autocomplete.layout(autocompleteContainer);

	        this.updateAutocompleteState();
	        main_core.Dom.append(autocompleteContainer, this._wrapper);
	      }

	      if (this.isContextMenuEnabled()) {
	        this._wrapper.appendChild(this.createContextMenuButton());
	      }

	      if (this.isDragEnabled()) {
	        this.initializeDragDropAbilities();
	      }

	      this.registerLayout(options);
	      this._hasLayout = true;
	    }
	  }, {
	    key: "isNeedToDisplay",
	    value: function isNeedToDisplay() {
	      return babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorRequisiteAutocomplete.prototype), "isNeedToDisplay", this).call(this) && this._mode === BX.UI.EntityEditorMode.edit;
	    }
	  }, {
	    key: "updateAutocompleteState",
	    value: function updateAutocompleteState() {
	      var autocompleteState = null;

	      try {
	        autocompleteState = JSON.parse(this.getValue());
	      } catch (e) {}

	      this._autocomplete.setState(autocompleteState);

	      this._autocomplete.setContext(this.getAutocompleteContext());
	    }
	  }, {
	    key: "onSelectAutocompleteValue",
	    value: function onSelectAutocompleteValue(event) {
	      this._autocompleteData = event.getData();
	      this.markAsChanged();
	    }
	  }, {
	    key: "onClearAutocompleteValue",
	    value: function onClearAutocompleteValue(event) {
	      this._autocomplete.setCurrentItem(null);

	      this._autocompleteData = null;
	    }
	  }, {
	    key: "getAutocompleteData",
	    value: function getAutocompleteData() {
	      return this._autocompleteData;
	    }
	  }, {
	    key: "getAutocompleteContext",
	    value: function getAutocompleteContext() {
	      return {
	        'typeId': 'ITIN',
	        'presetId': this._editor.getControlById('PRESET_ID').getValue()
	      };
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this(id, settings);
	      self.initialize(id, settings);
	      return self;
	    }
	  }, {
	    key: "onInitializeEditorControlFactory",
	    value: function onInitializeEditorControlFactory(event) {
	      var data = event.getData();

	      if (data[0]) {
	        data[0].methods["requisite_autocomplete"] = function (type, controlId, settings) {
	          if (type === "requisite_autocomplete") {
	            return EntityEditorRequisiteAutocomplete.create(controlId, settings);
	          }

	          return null;
	        };
	      }

	      event.setData(data);
	    }
	  }]);
	  return EntityEditorRequisiteAutocomplete;
	}(BX.UI.EntityEditorField);
	main_core_events.EventEmitter.subscribe('BX.UI.EntityEditorControlFactory:onInitialize', EntityEditorRequisiteAutocomplete.onInitializeEditorControlFactory);

	exports.EntityEditorRequisiteAutocomplete = EntityEditorRequisiteAutocomplete;

}((this.BX.Crm = this.BX.Crm || {}),BX.Event,BX,BX.Crm));
//# sourceMappingURL=requisite-autocomplete.bundle.js.map
