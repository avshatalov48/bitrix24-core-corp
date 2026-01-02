/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,ui_vue3_vuex,tasks_v2_const) {
	'use strict';

	class Groups extends ui_vue3_vuex.BuilderEntityModel {
	  getName() {
	    return tasks_v2_const.Model.Groups;
	  }
	  getElementState() {
	    return {
	      id: 0,
	      name: '',
	      image: ''
	    };
	  }
	}

	exports.Groups = Groups;

}((this.BX.Tasks.V2.Model = this.BX.Tasks.V2.Model || {}),BX.Vue3.Vuex,BX.Tasks.V2.Const));
//# sourceMappingURL=groups.bundle.js.map
