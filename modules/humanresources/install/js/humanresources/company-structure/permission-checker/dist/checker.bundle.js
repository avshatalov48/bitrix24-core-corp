/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,humanresources_companyStructure_api,humanresources_companyStructure_chartStore) {
	'use strict';

	const createTreeDataStore = treeData => {
	  const dataMap = new Map();
	  treeData.forEach(item => {
	    var _dataMap$get, _dataMap$get2, _mapParentItem$childr;
	    const {
	      id,
	      parentId
	    } = item;
	    const mapItem = (_dataMap$get = dataMap.get(id)) != null ? _dataMap$get : {};
	    dataMap.set(id, {
	      ...mapItem,
	      ...item
	    });
	    if (parentId === 0) {
	      return;
	    }
	    const mapParentItem = (_dataMap$get2 = dataMap.get(parentId)) != null ? _dataMap$get2 : {};
	    const children = (_mapParentItem$childr = mapParentItem.children) != null ? _mapParentItem$childr : [];
	    dataMap.set(parentId, {
	      ...mapParentItem,
	      children: [...children, id]
	    });
	  });
	  return dataMap;
	};
	const chartAPI = {
	  removeDepartment: id => {
	    return humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.delete', {
	      nodeId: id
	    });
	  },
	  getDepartmentsData: () => {
	    return humanresources_companyStructure_api.getData('humanresources.api.Structure.get', {}, {
	      tool: 'structure',
	      category: 'structure',
	      event: 'open_structure'
	    });
	  },
	  getCurrentDepartments: () => {
	    return humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.current');
	  },
	  getDictionary: () => {
	    return humanresources_companyStructure_api.getData('humanresources.api.Structure.dictionary');
	  },
	  getUserId: () => {
	    return humanresources_companyStructure_api.getData('humanresources.api.User.getCurrentId');
	  },
	  firstTimeOpened: () => {
	    return humanresources_companyStructure_api.postData('humanresources.api.User.firstTimeOpen');
	  },
	  createTreeDataStore
	};

	/* eslint-disable no-constructor-return */
	const PermissionActions = Object.freeze({
	  structureView: 'ACTION_STRUCTURE_VIEW',
	  chanelBindToStructure: 'ACTION_CHANEL_BIND_TO_STRUCTURE',
	  chanelUnbindToStructure: 'ACTION_CHANEL_UNBIND_TO_STRUCTURE',
	  chatBindToStructure: 'ACTION_CHAT_BIND_TO_STRUCTURE',
	  chatUnbindToStructure: 'ACTION_CHAT_UNBIND_TO_STRUCTURE',
	  departmentCreate: 'ACTION_DEPARTMENT_CREATE',
	  departmentDelete: 'ACTION_DEPARTMENT_DELETE',
	  departmentEdit: 'ACTION_DEPARTMENT_EDIT',
	  employeeAddToDepartment: 'ACTION_EMPLOYEE_ADD_TO_DEPARTMENT',
	  employeeRemoveFromDepartment: 'ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT',
	  accessEdit: 'ACTION_USERS_ACCESS_EDIT',
	  inviteToDepartment: 'ACTION_USER_INVITE'
	});
	class PermissionCheckerClass {
	  constructor() {
	    if (!PermissionCheckerClass.instance) {
	      this.currentUserPermissions = {};
	      this.permissionVariablesDictionary = [];
	      this.isInitialized = false;
	      PermissionCheckerClass.instance = this;
	    }
	    return PermissionCheckerClass.instance;
	  }
	  getInstance() {
	    return PermissionCheckerClass.instance;
	  }
	  async init() {
	    if (this.isInitialized) {
	      return;
	    }
	    const {
	      currentUserPermissions,
	      permissionVariablesDictionary
	    } = await chartAPI.getDictionary();
	    this.currentUserPermissions = currentUserPermissions;
	    this.permissionVariablesDictionary = permissionVariablesDictionary;
	    this.isInitialized = true;
	  }
	  hasPermission(action, departmentId) {
	    const permissionLevel = this.currentUserPermissions[action];
	    if (!permissionLevel) {
	      return false;
	    }
	    const permissionObject = this.permissionVariablesDictionary.find(item => item.id === permissionLevel);
	    if (!permissionObject) {
	      return false;
	    }
	    const departments = humanresources_companyStructure_chartStore.useChartStore().departments;
	    if (action === PermissionActions.departmentDelete) {
	      const rootId = [...departments.values()].find(department => department.parentId === 0).id;
	      if (departmentId === rootId) {
	        return false;
	      }
	    }
	    const userDepartments = humanresources_companyStructure_chartStore.useChartStore().currentDepartments;
	    switch (permissionObject.id) {
	      case PermissionCheckerClass.FULL_COMPANY:
	        return true;
	      case PermissionCheckerClass.SELF_AND_SUB:
	        {
	          if (userDepartments.includes(departmentId)) {
	            return true;
	          }
	          let currentDepartment = departments.get(departmentId);
	          while (currentDepartment) {
	            if (userDepartments.includes(currentDepartment.id)) {
	              return true;
	            }
	            currentDepartment = departments.get(currentDepartment.parentId);
	          }
	          return false;
	        }
	      case PermissionCheckerClass.SELF:
	        return userDepartments.includes(departmentId);
	      case PermissionCheckerClass.NONE:
	      default:
	        return false;
	    }
	  }
	  hasPermissionOfAction(action) {
	    return this.currentUserPermissions[action] !== undefined && this.currentUserPermissions[action] !== null && this.currentUserPermissions[action] !== PermissionCheckerClass.NONE;
	  }
	}
	PermissionCheckerClass.FULL_COMPANY = 30;
	PermissionCheckerClass.SELF_AND_SUB = 20;
	PermissionCheckerClass.SELF = 10;
	PermissionCheckerClass.NONE = 0;
	const PermissionChecker = new PermissionCheckerClass();

	exports.PermissionActions = PermissionActions;
	exports.PermissionChecker = PermissionChecker;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure));
//# sourceMappingURL=checker.bundle.js.map
