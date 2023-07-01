this.BX = this.BX || {};
(function (exports,main_core_events,main_core) {
	'use strict';

	var EntityEditorImage = /*#__PURE__*/function (_BX$UI$EntityEditorIm) {
	  babelHelpers.inherits(EntityEditorImage, _BX$UI$EntityEditorIm);

	  function EntityEditorImage() {
	    babelHelpers.classCallCheck(this, EntityEditorImage);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EntityEditorImage).apply(this, arguments));
	  }

	  babelHelpers.createClass(EntityEditorImage, [{
	    key: "loadInput",
	    value: function loadInput() {
	      var _this = this;

	      var context = {};

	      if (this._schemeElement) {
	        context.ownerEntityTypeId = this._schemeElement.getDataIntegerParam('ownerEntityTypeId', null);
	        context.ownerEntityId = this._schemeElement.getDataIntegerParam('ownerEntityId', null);
	        context.ownerEntityCategoryId = this._schemeElement.getDataIntegerParam('ownerEntityCategoryId', null);
	        context.permissionToken = this._schemeElement.getDataStringParam('permissionToken', null);
	      }

	      main_core.ajax.runAction('crm.entity.renderImageInput', {
	        data: {
	          entityTypeName: this._editor.getEntityTypeName(),
	          entityId: this._editor.getEntityId(),
	          fieldName: this.getDataKey(),
	          fieldValue: this.getValue(),
	          context: context
	        }
	      }).then(function (result) {
	        var assets = result.data.assets;
	        var assetsToLoad = [].concat(babelHelpers.toConsumableArray(assets.hasOwnProperty('css') ? assets.css : []), babelHelpers.toConsumableArray(assets.hasOwnProperty('js') ? assets.js : []));
	        BX.load(assetsToLoad, function () {
	          if (assets.hasOwnProperty('string')) {
	            Promise.all(assets.string.map(function (stringValue) {
	              return main_core.Runtime.html(null, stringValue);
	            })).then(function () {
	              _this.onEditorHtmlLoad(result.data.html);
	            });
	          } else {
	            _this.onEditorHtmlLoad(result.data.html);
	          }
	        });
	      }, function (result) {
	        _this.onEditorHtmlLoad(result.errors[0].message);
	      });
	    }
	  }, {
	    key: "onEditorHtmlLoad",
	    value: function onEditorHtmlLoad(html) {
	      var _this2 = this;

	      if (this._mode === BX.UI.EntityEditorMode.edit && this._innerWrapper) {
	        main_core.Runtime.html(this._innerWrapper, html);
	        BX.addCustomEvent(window, "onAfterPopupShow", this._dialogShowHandler);
	        BX.addCustomEvent(window, "onPopupClose", this._dialogCloseHandler);
	        setTimeout(function () {
	          _this2.bindFileEvents();
	        }, 500);
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new BX.Crm.EntityEditorImage();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return EntityEditorImage;
	}(BX.UI.EntityEditorImage); // crm implementation of image field for ui version of entity editor

	main_core_events.EventEmitter.subscribe('BX.UI.EntityEditorControlFactory:onInitialize', function (event) {
	  var data = event.getData();

	  if (data[0]) {
	    data[0].methods['crm_image'] = function (type, controlId, settings) {
	      if (type === 'crm_image') {
	        return BX.Crm.EntityEditorImage.create(controlId, settings);
	      }

	      return null;
	    };
	  }

	  event.setData(data);
	});

	exports.EntityEditorImage = EntityEditorImage;

}((this.BX.Crm = this.BX.Crm || {}),BX.Event,BX));
//# sourceMappingURL=image.bundle.js.map
