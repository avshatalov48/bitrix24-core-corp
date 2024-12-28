/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,ui_vue3_pinia) {
	'use strict';

	const useChartStore = ui_vue3_pinia.defineStore('hr-org-chart', {
	  state: () => ({
	    departments: new Map(),
	    currentDepartments: [],
	    focusedNode: 0,
	    searchedUserId: 0
	  })
	});

	exports.useChartStore = useChartStore;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Vue3.Pinia));
//# sourceMappingURL=chart-store.bundle.js.map
