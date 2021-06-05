this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_model) {
	'use strict';

	/**
	 * @extends BX.Crm.Model
	 * @memberOf BX.Crm.Models
	 */
	var StageModel = /*#__PURE__*/function (_Model) {
	  babelHelpers.inherits(StageModel, _Model);

	  function StageModel(data, params) {
	    babelHelpers.classCallCheck(this, StageModel);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(StageModel).call(this, data, params));
	  }

	  babelHelpers.createClass(StageModel, [{
	    key: "getModelName",
	    value: function getModelName() {
	      return 'stage';
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return this.data.name;
	    }
	  }, {
	    key: "setName",
	    value: function setName(name) {
	      this.data.name = name;
	    }
	  }, {
	    key: "getEntityId",
	    value: function getEntityId() {
	      return this.data.entityId;
	    }
	  }, {
	    key: "getStatusId",
	    value: function getStatusId() {
	      return this.data.statusId;
	    }
	  }, {
	    key: "getSort",
	    value: function getSort() {
	      return this.data.sort;
	    }
	  }, {
	    key: "setSort",
	    value: function setSort(sort) {
	      this.data.sort = sort;
	    }
	  }, {
	    key: "getColor",
	    value: function getColor() {
	      return this.data.color;
	    }
	  }, {
	    key: "setColor",
	    value: function setColor(color) {
	      this.data.color = color;
	    }
	  }, {
	    key: "getSemantics",
	    value: function getSemantics() {
	      return this.data.semantics;
	    }
	  }, {
	    key: "getCategoryId",
	    value: function getCategoryId() {
	      return this.data.categoryId;
	    }
	  }, {
	    key: "isFinal",
	    value: function isFinal() {
	      return this.isSuccess() || this.isFailure();
	    }
	  }, {
	    key: "isSuccess",
	    value: function isSuccess() {
	      return this.getSemantics() === 'S';
	    }
	  }, {
	    key: "isFailure",
	    value: function isFailure() {
	      return this.getSemantics() === 'F';
	    }
	  }]);
	  return StageModel;
	}(crm_model.Model);

	exports.StageModel = StageModel;

}((this.BX.Crm.Models = this.BX.Crm.Models || {}),BX.Crm));
//# sourceMappingURL=stage-model.bundle.js.map
