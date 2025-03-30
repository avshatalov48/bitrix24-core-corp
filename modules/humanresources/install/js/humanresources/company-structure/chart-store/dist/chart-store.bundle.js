/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,ui_vue3_pinia,humanresources_companyStructure_api) {
	'use strict';

	const useChartStore = ui_vue3_pinia.defineStore('hr-org-chart', {
	  state: () => ({
	    departments: new Map(),
	    currentDepartments: [],
	    focusedNode: 0,
	    searchedUserId: 0,
	    userId: 0
	  }),
	  actions: {
	    async refreshDepartments(nodeIds) {
	      const [departments, currentDepartments] = await Promise.all([humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.getByIds', {
	        nodeIds
	      }), humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.current')]);
	      this.currentDepartments = currentDepartments;
	      Object.keys(departments).forEach(id => {
	        const department = departments[id];
	        const existingDepartment = this.departments.get(Number(id)) || {};
	        this.departments.set(Number(id), {
	          ...existingDepartment,
	          heads: department.heads,
	          userCount: department.userCount,
	          employees: null,
	          employeeListOptions: {
	            page: 0,
	            shouldUpdateList: true,
	            isListUpdated: false
	          }
	        });
	      });
	    },
	    changeCurrentDepartment(oldDepartmentId, newDepartmentId) {
	      const currentDepartments = this.currentDepartments.filter(departmentId => {
	        return departmentId !== oldDepartmentId;
	      });
	      if (!newDepartmentId) {
	        this.currentDepartments = currentDepartments;
	        return;
	      }
	      this.currentDepartments = [...currentDepartments, newDepartmentId];
	    },
	    async loadHeads(nodeIds) {
	      if (nodeIds.length === 0) {
	        return;
	      }
	      const heads = await humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.getHeadsByIds', {
	        nodeIds
	      });
	      nodeIds.forEach(departmentId => {
	        const department = this.departments.get(departmentId);
	        if (heads[departmentId]) {
	          this.departments.set(departmentId, {
	            ...department,
	            heads: heads[departmentId]
	          });
	        }
	      });
	    }
	  }
	});

	exports.useChartStore = useChartStore;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Vue3.Pinia,BX.Humanresources.CompanyStructure));
//# sourceMappingURL=chart-store.bundle.js.map
