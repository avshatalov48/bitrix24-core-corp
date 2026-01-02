/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,ui_vue3_pinia,humanresources_companyStructure_api,humanresources_companyStructure_chartStore,main_core,humanresources_companyStructure_utils) {
	'use strict';

	class UserServiceClass {
	  async addUsersToEntity(entityId, users,
	  // array of user objects. Each object should contain user data such as id, name, etc
	  userCount, role) {
	    var _targetEntity$heads, _targetEntity$employe;
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    const targetEntity = store.departments.get(entityId);
	    if (!targetEntity) {
	      return;
	    }
	    const memberRoles = humanresources_companyStructure_api.getMemberRoles(targetEntity.entityType);
	    const newMemberUserIds = new Set(users.map(user => user.id));
	    if (newMemberUserIds.has(store.userId)) {
	      store.changeCurrentDepartment(0, targetEntity.id);
	    }
	    const heads = ((_targetEntity$heads = targetEntity.heads) != null ? _targetEntity$heads : []).filter(user => !newMemberUserIds.has(user.id));
	    const employees = ((_targetEntity$employe = targetEntity.employees) != null ? _targetEntity$employe : []).filter(user => !newMemberUserIds.has(user.id));
	    (role === memberRoles.employee ? employees : heads).push(...users);
	    targetEntity.heads = heads;
	    targetEntity.employees = employees;
	    targetEntity.userCount = userCount;
	    await this.refreshMultipleUsers();
	  }
	  removeUserFromEntity(entityId, userId, role) {
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    const entity = store.departments.get(entityId);
	    if (!entity) {
	      return;
	    }
	    const oldMemberRoles = humanresources_companyStructure_api.getMemberRoles(entity.entityType);
	    if (userId === store.userId) {
	      store.changeCurrentDepartment(entityId);
	    }
	    entity.userCount -= 1;
	    if (role === oldMemberRoles.employee) {
	      entity.employees = entity.employees.filter(employee => employee.id !== userId);
	    } else {
	      entity.heads = entity.heads.filter(head => head.id !== userId);
	    }
	    const key = String(userId);
	    const nodeIds = store.multipleUsers[key];
	    if (main_core.Type.isArray(nodeIds)) {
	      const filtered = nodeIds.filter(id => Number(id) !== Number(entityId));
	      if (filtered.length >= 2) {
	        store.multipleUsers[key] = filtered;
	      } else {
	        delete store.multipleUsers[key];
	      }
	    }
	  }
	  moveUserToEntity(entityId, userId, targetEntityId, role) {
	    var _entity$employees;
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    const entity = store.departments.get(entityId);
	    const targetEntity = store.departments.get(targetEntityId);
	    if (!entity || !targetEntity) {
	      return;
	    }
	    const oldMemberRoles = humanresources_companyStructure_api.getMemberRoles(entity.entityType);
	    const targetMemberRoles = humanresources_companyStructure_api.getMemberRoles(targetEntity.entityType);
	    const user = role === oldMemberRoles.employee ? (_entity$employees = entity.employees) == null ? void 0 : _entity$employees.find(employee => employee.id === userId) : entity.heads.find(head => head.id === userId);
	    if (!user) {
	      return;
	    }
	    entity.userCount -= 1;
	    if (role === oldMemberRoles.employee) {
	      entity.employees = entity.employees.filter(employee => employee.id !== userId);
	    } else {
	      entity.heads = entity.heads.filter(head => head.id !== userId);
	    }
	    targetEntity.userCount += 1;
	    if (userId === store.userId) {
	      store.changeCurrentDepartment(entityId, targetEntityId);
	    }
	    user.role = targetMemberRoles.employee;
	    if (!targetEntity.employees) {
	      return;
	    }
	    targetEntity.employees.push(user);
	    if (entity.entityType === humanresources_companyStructure_utils.EntityTypes.department) {
	      const key = String(userId);
	      const entityIds = store.multipleUsers[key];
	      if (main_core.Type.isArray(entityIds)) {
	        const current = new Set(entityIds.map(v => Number(v)));
	        current.delete(Number(entityId));
	        current.add(Number(targetEntityId));
	        store.multipleUsers[key] = [...current];
	      }
	    }
	  }
	  async moveUsersToEntity(entityId, users,
	  // array of user objects. Each object should contain user data such as id, name, etc
	  userCount, updatedDepartmentIds) {
	    var _entityDepartment$emp;
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    const entityDepartment = store.departments.get(entityId);
	    if (!entityDepartment) {
	      return;
	    }
	    const newMemberUserIds = new Set(users.map(user => user.id));
	    const employees = ((_entityDepartment$emp = entityDepartment.employees) != null ? _entityDepartment$emp : []).filter(user => !newMemberUserIds.has(user.id));
	    const headsUserIds = new Set(entityDepartment.heads.map(head => head.id));
	    const newUsers = users.filter(user => !headsUserIds.has(user.id));
	    employees.push(...newUsers);
	    entityDepartment.employees = employees;
	    entityDepartment.userCount = userCount;
	    users.forEach(({
	      id
	    }) => {
	      const key = String(id);
	      if (Object.prototype.hasOwnProperty.call(store.multipleUsers, key)) {
	        delete store.multipleUsers[key];
	      }
	    });
	    if (updatedDepartmentIds.length > 0) {
	      void store.refreshDepartments(updatedDepartmentIds);
	    }
	  }
	  async removeUserFromAllEntities(userId) {
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    const entities = store.departments;
	    const entitiesToUpdate = [];
	    for (const [key, entity] of entities) {
	      if ('heads' in entity && main_core.Type.isArray(entity.heads) && entity.heads.some(employee => employee.id === userId) || 'employees' in entity && main_core.Type.isArray(entity.employees) && entity.employees.some(employee => employee.id === userId)) {
	        entitiesToUpdate.push(key);
	      }
	    }
	    delete store.multipleUsers[String(userId)];
	    return store.refreshDepartments(entitiesToUpdate);
	  }
	  async refreshMultipleUsers() {
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    store.multipleUsers = await humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.Member.getMultipleUsersMap');
	  }
	}
	const UserService = new UserServiceClass();

	const useChartStore = ui_vue3_pinia.defineStore('hr-org-chart', {
	  state: () => ({
	    departments: new Map(),
	    // list of all entities of structures visible to the user with detailed information
	    currentDepartments: [],
	    focusedNode: 0,
	    searchedUserId: 0,
	    userId: 0,
	    /** @var Map<number, { id: number, parentId: number, entityType: string }> */
	    structureMap: new Map(),
	    // map of the entire structure (all entities) with minimal information
	    multipleUsers: []
	  }),
	  actions: {
	    async refreshDepartments(nodeIds) {
	      if (nodeIds.length === 0) {
	        return;
	      }
	      const [departments, currentDepartments] = await Promise.all([humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.getByIds', {
	        nodeIds
	      }), humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.current')]);
	      this.currentDepartments = currentDepartments;
	      Object.keys(departments).forEach(id => {
	        const department = departments[id];
	        const existingDepartment = this.departments.get(Number(id)) || {};
	        this.departments.set(Number(id), {
	          ...existingDepartment,
	          ...department,
	          heads: department.heads,
	          userCount: department.userCount,
	          employees: [],
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
	        return departmentId !== oldDepartmentId && departmentId !== newDepartmentId;
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
	    },
	    updateDepartment(departmentData, position) {
	      const {
	        id,
	        parentId
	      } = departmentData;
	      const oldData = this.departments.get(id);
	      const entityType = oldData.entityType;
	      const prevParent = this.departments.get(oldData.parentId);
	      this.departments.set(id, {
	        ...oldData,
	        ...departmentData
	      });
	      this.structureMap.set(id, {
	        id,
	        parentId: parentId != null ? parentId : oldData.parentId,
	        entityType
	      });
	      if (parentId !== 0 && prevParent.id !== parentId) {
	        var _newParent$children;
	        prevParent.children = prevParent.children.filter(childId => childId !== id);
	        const newParent = this.departments.get(parentId);
	        newParent.children = (_newParent$children = newParent.children) != null ? _newParent$children : [];
	        if (main_core.Type.isNumber(position)) {
	          newParent.children.splice(position, 0, id);
	        } else {
	          newParent.children.push(id);
	        }
	        this.departments.set(id, {
	          ...this.departments.get(id),
	          prevParentId: prevParent.id
	        });
	      }
	    },
	    updateChatsInChildrenNodes(parentNodeId) {
	      const parentDepartment = this.departments.get(parentNodeId);
	      if (!parentDepartment || !parentDepartment.children) {
	        return;
	      }
	      this.markDescendantsForChatReload(parentDepartment.children);
	    },
	    markDescendantsForChatReload(childrenIds) {
	      const store = useChartStore();
	      const queue = [...childrenIds];
	      const visited = new Set();
	      const maxIterations = 10000;
	      let iterations = 0;
	      while (queue.length > 0 && iterations < maxIterations) {
	        iterations++;
	        const childId = queue.shift();
	        if (visited.has(childId)) {
	          continue;
	        }
	        visited.add(childId);
	        const childDepartment = store.departments.get(childId);
	        if (!childDepartment) {
	          continue;
	        }
	        childDepartment.chatsDetailed = null;
	        childDepartment.channelsDetailed = null;
	        childDepartment.collabsDetailed = null;
	        if (childDepartment.children && childDepartment.children.length > 0) {
	          queue.push(...childDepartment.children);
	        }
	      }
	    }
	  }
	});

	exports.useChartStore = useChartStore;
	exports.UserService = UserService;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Vue3.Pinia,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX,BX.Humanresources.CompanyStructure));
//# sourceMappingURL=chart-store.bundle.js.map
