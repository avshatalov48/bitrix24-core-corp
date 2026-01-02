/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,ui_vue3_vuex,tasks_v2_const) {
	'use strict';

	class Users extends ui_vue3_vuex.BuilderEntityModel {
	  getName() {
	    return tasks_v2_const.Model.Users;
	  }
	  getElementState() {
	    return {
	      id: 0,
	      name: '',
	      image: ''
	    };
	  }
	}

	const UserTypes = Object.freeze({
	  Employee: 'employee',
	  Collaber: 'collaber',
	  Extranet: 'extranet'
	});

	exports.Users = Users;
	exports.UserTypes = UserTypes;

}((this.BX.Tasks.V2.Model = this.BX.Tasks.V2.Model || {}),BX.Vue3.Vuex,BX.Tasks.V2.Const));
//# sourceMappingURL=users.bundle.js.map
