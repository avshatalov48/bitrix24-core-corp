/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,humanresources_companyStructure_api,humanresources_companyStructure_permissionChecker,main_core,humanresources_companyStructure_chartStore,humanresources_companyStructure_utils) {
	'use strict';

	const createTreeDataStore = treeData => {
	  const dataMap = new Map();
	  treeData.forEach(item => {
	    var _dataMap$get, _dataMap$get2, _mapParentItem$childr;
	    const {
	      id,
	      parentId,
	      colorName,
	      entityType
	    } = item;
	    const mapItem = (_dataMap$get = dataMap.get(id)) != null ? _dataMap$get : {};
	    const teamColor = humanresources_companyStructure_utils.getNodeColorSettings(colorName, entityType);
	    dataMap.set(id, {
	      ...mapItem,
	      ...item,
	      teamColor
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
	  updateDepartment: (nodeId, parentId) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.update', {
	      nodeId,
	      parentId,
	      name: null
	    });
	  },
	  changeOrder: (draggedId, direction, count) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.changeOrder', {
	      nodeId: draggedId,
	      direction,
	      count
	    });
	  },
	  createTreeDataStore
	};

	class BasePermissionChecker {
	  isCheckerAction(action) {
	    throw new Error('The method isCheckerAction must be implemented in the subclass');
	  }
	  hasPermission(action, nodeId, permissionValue, minLevel) {
	    throw new Error('The method hasPermission must be implemented in the subclass');
	  }
	}

	const PermissionActions = Object.freeze({
	  structureView: 'ACTION_STRUCTURE_VIEW',
	  departmentCreate: 'ACTION_DEPARTMENT_CREATE',
	  departmentDelete: 'ACTION_DEPARTMENT_DELETE',
	  departmentEdit: 'ACTION_DEPARTMENT_EDIT',
	  employeeAddToDepartment: 'ACTION_EMPLOYEE_ADD_TO_DEPARTMENT',
	  employeeRemoveFromDepartment: 'ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT',
	  employeeFire: 'ACTION_FIRE_EMPLOYEE',
	  departmentSettingsEdit: 'ACTION_DEPARTMENT_SETTINGS_EDIT',
	  departmentChatEdit: 'ACTION_DEPARTMENT_CHAT_EDIT',
	  departmentChannelEdit: 'ACTION_DEPARTMENT_CHANNEL_EDIT',
	  departmentCollabEdit: 'ACTION_DEPARTMENT_COLLAB_EDIT',
	  accessEdit: 'ACTION_USERS_ACCESS_EDIT',
	  teamAccessEdit: 'ACTION_TEAM_ACCESS_EDIT',
	  inviteToDepartment: 'ACTION_USER_INVITE',
	  teamView: 'ACTION_TEAM_VIEW',
	  teamCreate: 'ACTION_TEAM_CREATE',
	  teamEdit: 'ACTION_TEAM_EDIT',
	  teamDelete: 'ACTION_TEAM_DELETE',
	  teamAddMember: 'ACTION_TEAM_MEMBER_ADD',
	  teamRemoveMember: 'ACTION_TEAM_MEMBER_REMOVE',
	  teamSettingsEdit: 'ACTION_TEAM_SETTINGS_EDIT',
	  teamChatEdit: 'ACTION_TEAM_CHAT_EDIT',
	  teamChannelEdit: 'ACTION_TEAM_CHANNEL_EDIT',
	  teamCollabEdit: 'ACTION_TEAM_COLLAB_EDIT'
	});
	const PermissionLevels = Object.freeze({
	  fullCompany: 30,
	  selfAndSub: 20,
	  self: 10,
	  none: 0
	});

	class TeamPermissionChecker extends BasePermissionChecker {
	  constructor(departmentChecker, currentUserPermissions) {
	    super();
	    this.departmentChecker = departmentChecker;
	    this.currentUserPermissions = currentUserPermissions;
	  }
	  isCheckerAction(action) {
	    const teamActions = [PermissionActions.teamView, PermissionActions.teamCreate, PermissionActions.teamEdit, PermissionActions.teamDelete, PermissionActions.teamAddMember, PermissionActions.teamRemoveMember, PermissionActions.teamSettingsEdit, PermissionActions.teamChatEdit, PermissionActions.teamChannelEdit, PermissionActions.teamCollabEdit];
	    return teamActions.includes(action);
	  }
	  hasPermission(action, entityId, permissionValue, minValue) {
	    if (minValue) {
	      const isTeamPermissionInsufficient = permissionValue.TEAM < ((minValue == null ? void 0 : minValue.TEAM) || 0);
	      const isDepartmentPermissionInsufficient = permissionValue.DEPARTMENT < ((minValue == null ? void 0 : minValue.DEPARTMENT) || 0);
	      if (isTeamPermissionInsufficient || isDepartmentPermissionInsufficient) {
	        return false;
	      }
	    }
	    if (permissionValue.TEAM === PermissionLevels.fullCompany) {
	      const entities = humanresources_companyStructure_chartStore.useChartStore().structureMap;
	      const currentNode = entities.get(entityId);
	      if ((action === PermissionActions.teamCreate || action === PermissionActions.teamEdit) && currentNode.entityType === humanresources_companyStructure_utils.EntityTypes.department) {
	        return humanresources_companyStructure_permissionChecker.PermissionChecker.hasPermission(PermissionActions.structureView, currentNode.id);
	      }
	      return true;
	    }
	    if (this.hasPermissionByTeamPermission(entityId, permissionValue.TEAM)) {
	      return true;
	    }
	    return this.hasPermissionByDepartmentPermission(entityId, permissionValue.DEPARTMENT, action);
	  }
	  hasPermissionByTeamPermission(nodeId, level = PermissionLevels.none) {
	    if (level === PermissionLevels.none) {
	      return false;
	    }
	    const entities = humanresources_companyStructure_chartStore.useChartStore().structureMap;
	    const userEntities = humanresources_companyStructure_chartStore.useChartStore().currentDepartments;
	    const userTeams = new Set(userEntities.filter(id => {
	      const department = entities.get(id);
	      return department && department.entityType === humanresources_companyStructure_utils.EntityTypes.team;
	    }));
	    if (userTeams.has(nodeId)) {
	      return true;
	    }
	    if (level === PermissionLevels.self) {
	      return false;
	    }
	    let currentDepartment = entities.get(nodeId);
	    while (currentDepartment) {
	      if (currentDepartment.entityType === humanresources_companyStructure_utils.EntityTypes.department) {
	        return false;
	      }
	      if (userTeams.has(currentDepartment.id)) {
	        return true;
	      }
	      currentDepartment = entities.get(currentDepartment.parentId);
	    }
	    return false;
	  }
	  hasPermissionByDepartmentPermission(nodeId, level = PermissionLevels.none, action = '') {
	    if (level === PermissionLevels.none) {
	      return false;
	    }
	    const entities = humanresources_companyStructure_chartStore.useChartStore().structureMap;
	    const userEntities = humanresources_companyStructure_chartStore.useChartStore().currentDepartments;
	    const userDepartments = new Set(userEntities.filter(id => {
	      const department = entities.get(id);
	      return department && department.entityType === humanresources_companyStructure_utils.EntityTypes.department;
	    }));
	    if (userDepartments.has(nodeId)) {
	      return true;
	    }
	    let currentDepartment = entities.get(nodeId);
	    while (currentDepartment) {
	      if (userDepartments.has(currentDepartment.id)) {
	        return true;
	      }
	      if (level === PermissionLevels.self && currentDepartment.entityType === humanresources_companyStructure_utils.EntityTypes.department) {
	        if (action === PermissionActions.teamCreate || action === PermissionActions.teamEdit) {
	          return userDepartments.has(currentDepartment.id);
	        }
	        return false;
	      }
	      currentDepartment = entities.get(currentDepartment.parentId);
	    }
	    return false;
	  }
	}

	class DepartmentPermissionChecker extends BasePermissionChecker {
	  isCheckerAction(action) {
	    const departmentActions = [PermissionActions.structureView, PermissionActions.departmentCreate, PermissionActions.departmentDelete, PermissionActions.departmentEdit, PermissionActions.employeeAddToDepartment, PermissionActions.employeeRemoveFromDepartment, PermissionActions.employeeFire, PermissionActions.departmentSettingsEdit, PermissionActions.departmentChatEdit, PermissionActions.departmentChannelEdit, PermissionActions.departmentCollabEdit, PermissionActions.inviteToDepartment];
	    return departmentActions.includes(action);
	  }
	  hasPermission(action, departmentId, permissionValue, minLevel) {
	    if (!this.isCheckerAction(action) && !main_core.Type.isNumber(permissionValue)) {
	      return false;
	    }
	    if (minLevel !== null && permissionValue < minLevel) {
	      return false;
	    }
	    const departments = humanresources_companyStructure_chartStore.useChartStore().structureMap;
	    if (action === PermissionActions.departmentDelete) {
	      const rootId = [...departments.values()].find(department => department.parentId === 0).id;
	      if (departmentId === rootId) {
	        return false;
	      }
	    }
	    const userDepartments = humanresources_companyStructure_chartStore.useChartStore().currentDepartments;
	    switch (permissionValue) {
	      case PermissionLevels.fullCompany:
	        {
	          return true;
	        }
	      case PermissionLevels.selfAndSub:
	        {
	          let currentDepartment = departments.get(departmentId);
	          while (currentDepartment) {
	            if (userDepartments.includes(currentDepartment.id)) {
	              return true;
	            }
	            currentDepartment = departments.get(currentDepartment.parentId);
	          }
	          return false;
	        }
	      case PermissionLevels.self:
	        {
	          return userDepartments.includes(departmentId);
	        }
	      case PermissionLevels.none:
	      default:
	        {
	          return false;
	        }
	    }
	  }
	}

	/* eslint-disable no-constructor-return */
	class PermissionCheckerClass {
	  constructor() {
	    if (!PermissionCheckerClass.instance) {
	      this.currentUserPermissions = {};
	      this.isTeamsAvailable = false;
	      this.isCollabsAvailable = false;
	      this.isDeputyApprovesBPAvailable = false;
	      this.isInitialized = false;
	      this.departmentChecker = new DepartmentPermissionChecker();
	      this.teamChecker = new TeamPermissionChecker(this.departmentChecker, this.currentUserPermissions);
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
	      teamsAvailable,
	      collabsAvailable,
	      deputyApprovesBP,
	      departmentSettingsAvailable
	    } = await chartAPI.getDictionary();
	    this.currentUserPermissions = currentUserPermissions;
	    this.isTeamsAvailable = teamsAvailable;
	    this.isCollabsAvailable = collabsAvailable;
	    this.isDeputyApprovesBPAvailable = deputyApprovesBP;
	    this.isDepartmentSettingsAvailable = departmentSettingsAvailable;
	    this.isInitialized = true;
	  }
	  hasPermission(action, entityId, minLevel = null) {
	    const permissionLevel = this.currentUserPermissions[action];
	    if (!permissionLevel || !entityId) {
	      return false;
	    }
	    if (this.departmentChecker.isCheckerAction(action)) {
	      return this.departmentChecker.hasPermission(action, entityId, permissionLevel, minLevel);
	    }
	    if (this.isTeamsAvailable && this.teamChecker.isCheckerAction(action)) {
	      return this.teamChecker.hasPermission(action, entityId, permissionLevel, minLevel);
	    }
	    return false;
	  }
	  checkDeputyApprovalBPAvailable() {
	    return this.isDeputyApprovesBPAvailable;
	  }
	  checkDepartmentSettingsAvailable() {
	    return this.isDepartmentSettingsAvailable;
	  }
	  hasPermissionOfAction(action) {
	    const permissionLevel = this.currentUserPermissions[action];
	    if (!permissionLevel) {
	      return false;
	    }
	    if (this.teamChecker.isCheckerAction(action)) {
	      if (!this.isTeamsAvailable) {
	        return false;
	      }
	      return permissionLevel.TEAM > PermissionLevels.none || permissionLevel.DEPARTMENT > PermissionLevels.none;
	    }
	    return permissionLevel > PermissionLevels.none;
	  }
	  canSortEntitiesByParentId(entityId) {
	    const entities = humanresources_companyStructure_chartStore.useChartStore().departments;
	    const entity = entities.get(entityId);
	    if (!entity) {
	      return false;
	    }
	    const teamTeamMinValue = {
	      TEAM: PermissionLevels.selfAndSub
	    };
	    const teamDepartmentMinValue = {
	      DEPARTMENT: PermissionLevels.selfAndSub
	    };
	    const teamAction = PermissionActions.teamEdit;
	    if (entity.entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	      return this.hasPermission(teamAction, entityId, teamTeamMinValue) || this.hasPermission(teamAction, entityId, teamDepartmentMinValue);
	    }
	    if (entity.entityType === humanresources_companyStructure_utils.EntityTypes.department) {
	      const departmentAction = PermissionActions.departmentEdit;
	      return this.hasPermission(departmentAction, entityId, PermissionLevels.selfAndSub);
	    }
	    return false;
	  }
	  canBeParentForEntity(entityId, type) {
	    if (type === humanresources_companyStructure_utils.EntityTypes.department) {
	      return this.hasPermission(PermissionActions.departmentEdit, entityId);
	    }
	    if (type === humanresources_companyStructure_utils.EntityTypes.team) {
	      return this.hasPermission(PermissionActions.teamEdit, entityId);
	    }
	    return false;
	  }
	}
	const PermissionChecker = new PermissionCheckerClass();

	exports.PermissionChecker = PermissionChecker;
	exports.PermissionActions = PermissionActions;
	exports.PermissionLevels = PermissionLevels;
	exports.PermissionCheckerClass = PermissionCheckerClass;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure));
//# sourceMappingURL=checker.bundle.js.map
