/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Provider = this.BX.Tasks.V2.Provider || {};
(function (exports,tasks_v2_lib_apiClient) {
	'use strict';

	var _fetchPromise = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fetchPromise");
	class TaskInterfaceService {
	  constructor() {
	    Object.defineProperty(this, _fetchPromise, {
	      writable: true,
	      value: void 0
	    });
	  }
	  async fetchData() {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    // eslint-disable-next-line no-async-promise-executor
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _fetchPromise))[_fetchPromise]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_fetchPromise] = new Promise(async (resolve, reject) => {
	      try {
	        const data = await new tasks_v2_lib_apiClient.ApiClient().post('TaskInterface.get', {});
	        resolve(data);
	      } catch (error) {
	        reject(error);
	      }
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _fetchPromise)[_fetchPromise];
	  }
	}
	const taskInterfaceService = new TaskInterfaceService();

	exports.taskInterfaceService = taskInterfaceService;

}((this.BX.Tasks.V2.Provider.Service = this.BX.Tasks.V2.Provider.Service || {}),BX.Tasks.V2.Lib));
//# sourceMappingURL=task-interface-service.bundle.js.map
