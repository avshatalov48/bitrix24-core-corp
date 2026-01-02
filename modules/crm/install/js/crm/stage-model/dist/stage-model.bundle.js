/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_model) {
	'use strict';

	/**
	 * @extends BX.Crm.Model
	 * @memberOf BX.Crm.Models
	 */
	class StageModel extends crm_model.Model {
	  constructor(data, params) {
	    super(data, params);
	  }
	  getModelName() {
	    return 'stage';
	  }
	  getName() {
	    return this.data.name;
	  }
	  setName(name) {
	    this.data.name = name;
	  }
	  getEntityId() {
	    return this.data.entityId;
	  }
	  getStatusId() {
	    return this.data.statusId;
	  }
	  getSort() {
	    return this.data.sort;
	  }
	  setSort(sort) {
	    this.data.sort = sort;
	  }
	  getColor() {
	    return this.data.color;
	  }
	  setColor(color) {
	    this.data.color = color;
	  }
	  getSemantics() {
	    return this.data.semantics;
	  }
	  getCategoryId() {
	    return this.data.categoryId;
	  }
	  getStagesToMove() {
	    return this.data.stagesToMove;
	  }
	  isAllowedMoveToAnyStage() {
	    var _this$data$allowMoveT;
	    return (_this$data$allowMoveT = this.data.allowMoveToAnyStage) != null ? _this$data$allowMoveT : false;
	  }
	  isFinal() {
	    return this.isSuccess() || this.isFailure();
	  }
	  isSuccess() {
	    return this.getSemantics() === 'S';
	  }
	  isFailure() {
	    return this.getSemantics() === 'F';
	  }
	}

	exports.StageModel = StageModel;

}((this.BX.Crm.Models = this.BX.Crm.Models || {}),BX.Crm));
//# sourceMappingURL=stage-model.bundle.js.map
