(function (exports,main_core,rpa_component,rpa_fieldspopup) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Rpa');

	var StageComponent =
	/*#__PURE__*/
	function (_Component) {
	  babelHelpers.inherits(StageComponent, _Component);
	  babelHelpers.createClass(StageComponent, [{
	    key: "getPermissionSelectors",
	    value: function getPermissionSelectors() {
	      return [{
	        action: 'VIEW',
	        selector: '[data-role="permission-setting-view"]'
	      }, {
	        action: 'MODIFY',
	        selector: '[data-role="permission-setting-create"]'
	      }, {
	        action: 'MODIFY',
	        selector: '[data-role="permission-setting-modify"]'
	      }, {
	        action: 'MOVE',
	        selector: '[data-role="permission-setting-move"]'
	      }];
	    }
	  }]);

	  function StageComponent() {
	    var _babelHelpers$getProt;

	    var _this;

	    babelHelpers.classCallCheck(this, StageComponent);

	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }

	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(StageComponent)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _this.popups = {};

	    if (args[1].fields && main_core.Type.isPlainObject(args[1].fields)) {
	      for (var _i = 0, _Object$entries = Object.entries(args[1].fields); _i < _Object$entries.length; _i++) {
	        var _Object$entries$_i = babelHelpers.slicedToArray(_Object$entries[_i], 2),
	            visibility = _Object$entries$_i[0],
	            settings = _Object$entries$_i[1];

	        _this.popups[visibility] = new rpa_fieldspopup.FieldsPopup('stage-' + visibility + '-fields', settings.fields, settings.title);

	        _this.bindFieldButton(document.getElementById(settings.id), _this.popups[visibility]);
	      }
	    }

	    return _this;
	  }

	  babelHelpers.createClass(StageComponent, [{
	    key: "init",
	    value: function init() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(StageComponent.prototype), "init", this).call(this);
	      this.adjustDeleteButtonVisibility();
	    }
	  }, {
	    key: "adjustDeleteButtonVisibility",
	    value: function adjustDeleteButtonVisibility() {
	      var data = this.prepareData();

	      if (data.id && data.id > 0) {
	        this.deleteButton.style.display = 'block';
	      } else {
	        this.deleteButton.style.display = 'none';
	      }
	    }
	  }, {
	    key: "bindFieldButton",
	    value: function bindFieldButton(node, popup) {
	      main_core.Event.bind(node, 'click', function (event) {
	        event.preventDefault();
	        popup.show();
	      });
	    }
	  }, {
	    key: "prepareData",
	    value: function prepareData() {
	      var data = {};
	      var fields = {};
	      fields.typeId = this.form.querySelector('[name="typeId"]').value;
	      var id = this.form.querySelector('[name="id"]').value;

	      if (id > 0) {
	        data.id = id;
	      }

	      fields.name = this.form.querySelector('[name="name"]').value;
	      fields.code = this.form.querySelector('[name="code"]').value;
	      fields.permissions = this.getPermissions();
	      fields.fields = {};

	      for (var _i2 = 0, _Object$entries2 = Object.entries(this.popups); _i2 < _Object$entries2.length; _i2++) {
	        var _Object$entries2$_i = babelHelpers.slicedToArray(_Object$entries2[_i2], 2),
	            visibility = _Object$entries2$_i[0],
	            popup = _Object$entries2$_i[1];

	        fields.fields[visibility] = Array.from(popup.getSelectedFields());
	      }

	      fields.possibleNextStages = this.getPossibleNextStages();
	      data.fields = fields;
	      return data;
	    }
	  }, {
	    key: "getPossibleNextStages",
	    value: function getPossibleNextStages() {
	      var stages = [];
	      var select = this.form.querySelector('[name="possibleNextStages[]"]');
	      var options = select.querySelectorAll('option');
	      options.forEach(function (option) {
	        if (option.selected) {
	          stages.push(option.value);
	        }
	      });
	      return stages;
	    }
	  }, {
	    key: "afterSave",
	    value: function afterSave(result) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(StageComponent.prototype), "afterSave", this).call(this, result);
	      var slider = this.getSlider();

	      if (slider) {
	        slider.close();
	        return;
	      }

	      var title = main_core.Loc.getMessage('RPA_STAGE_DETAIL_TITLE').replace('#TITLE#', result.data.stage.name);

	      if (this.method === 'rpa.stage.add') {
	        var stageId = result.data.stage.id;
	        var url = new main_core.Uri(location.href);
	        url.setQueryParam('id', result.data.stage.id);
	        window.history.pushState({}, title, url.toString());
	        this.form.querySelector('[name="id"]').value = stageId;
	        this.method = 'rpa.stage.update';
	        this.analyticsLabel = 'rpaStageUpdate';
	      }

	      this.adjustDeleteButtonVisibility();
	      document.getElementById('pagetitle').innerText = title;
	    }
	  }, {
	    key: "delete",
	    value: function _delete(event) {
	      var _this2 = this;

	      event.preventDefault();

	      if (this.isProgress) {
	        return;
	      }

	      var data = this.prepareData();

	      if (data.id > 0) {
	        data = {
	          id: data.id
	        };
	      } else {
	        return;
	      }

	      if (confirm(main_core.Loc.getMessage('RPA_STAGE_DELETE_CONFIRM'))) {
	        this.startProgress();
	        main_core.ajax.runAction('rpa.stage.delete', {
	          analyticsLabel: 'rpaStageDelete',
	          data: data
	        }).then(function (result) {
	          _this2.afterDelete(result);

	          _this2.stopProgress();
	        }).catch(function (result) {
	          _this2.showErrors(result.errors);

	          _this2.stopProgress();
	        });
	      }
	    }
	  }, {
	    key: "afterDelete",
	    value: function afterDelete() {
	      this.cancelButton.click();
	    }
	  }]);
	  return StageComponent;
	}(rpa_component.Component);

	namespace.StageComponent = StageComponent;

}((this.window = this.window || {}),BX,BX.Rpa,BX.Rpa));
//# sourceMappingURL=script.js.map
