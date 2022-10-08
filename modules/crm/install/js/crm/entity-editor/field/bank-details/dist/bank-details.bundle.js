this.BX = this.BX || {};
(function (exports,crm_entityEditor_field_fieldset,main_core_events,main_core) {
	'use strict';

	var _templateObject;
	var EntityEditorBankDetailsField = /*#__PURE__*/function (_EntityEditorFieldset) {
	  babelHelpers.inherits(EntityEditorBankDetailsField, _EntityEditorFieldset);

	  function EntityEditorBankDetailsField() {
	    babelHelpers.classCallCheck(this, EntityEditorBankDetailsField);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EntityEditorBankDetailsField).apply(this, arguments));
	  }

	  babelHelpers.createClass(EntityEditorBankDetailsField, [{
	    key: "getAddButton",
	    value: function getAddButton() {
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"ui-entity-editor-container-actions\">\n\t\t\t<span>", "</span>\n\t\t\t<span class=\"ui-entity-editor-content-create-lnk\" onclick=\"", "\">", "</span>\n\t\t</div>"])), main_core.Loc.getMessage('CRM_EDITOR_REQUISITE_BANK_DETAILS_ADD_LABEL'), this.onAddButtonClick.bind(this), main_core.Loc.getMessage('CRM_EDITOR_REQUISITE_BANK_DETAILS_ADD_LINK_TEXT'));
	    }
	  }, {
	    key: "onAddButtonClick",
	    value: function onAddButtonClick() {
	      this.addEmptyValue({
	        scrollToTop: true
	      });
	    }
	  }, {
	    key: "addEmptyValue",
	    value: function addEmptyValue(options) {
	      var editor = babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorBankDetailsField.prototype), "addEmptyValue", this).call(this, options);

	      if (BX.prop.getBoolean(options, 'scrollToTop', false)) {
	        setTimeout(function () {
	          var container = editor.getContainer();

	          if (main_core.Type.isDomNode(container)) {
	            var pos = BX.pos(container);
	            var startPos = window.pageYOffset;
	            var finishPos = pos.top - 15;
	            var reverseDirection = startPos > finishPos;

	            if (reverseDirection) {
	              startPos = -startPos;
	              finishPos = -finishPos;
	            }

	            new BX.easing({
	              duration: 500,
	              start: {
	                top: startPos
	              },
	              finish: {
	                top: finishPos
	              },
	              transition: BX.easing.transitions.quart,
	              step: function step(state) {
	                window.scrollTo(0, state.top * (reverseDirection ? -1 : 1));
	              }
	            }).animate();
	          }
	        }, 10);
	      }

	      return editor;
	    }
	  }]);
	  return EntityEditorBankDetailsField;
	}(crm_entityEditor_field_fieldset.EntityEditorFieldsetField);
	main_core_events.EventEmitter.subscribe('BX.UI.EntityEditorControlFactory:onInitialize', function (event) {
	  var data = event.getData();

	  if (data[0]) {
	    data[0].methods["bankDetails"] = function (type, controlId, settings) {
	      if (type === "bankDetails") {
	        return EntityEditorBankDetailsField.create(controlId, settings);
	      }

	      return null;
	    };
	  }

	  event.setData(data);
	});

	exports.EntityEditorBankDetailsField = EntityEditorBankDetailsField;

}((this.BX.Crm = this.BX.Crm || {}),BX.Crm,BX.Event,BX));
//# sourceMappingURL=bank-details.bundle.js.map
