/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_accessrights) {
	'use strict';

	var _dialogOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialogOptions");
	var _entitiesIdsEncoder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entitiesIdsEncoder");
	var _entitiesIdsDecoder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entitiesIdsDecoder");
	var _normalizeType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("normalizeType");
	class AccessRightsWrapper {
	  constructor() {
	    Object.defineProperty(this, _normalizeType, {
	      value: _normalizeType2
	    });
	    Object.defineProperty(this, _entitiesIdsDecoder, {
	      value: _entitiesIdsDecoder2
	    });
	    Object.defineProperty(this, _entitiesIdsEncoder, {
	      value: _entitiesIdsEncoder2
	    });
	    Object.defineProperty(this, _dialogOptions, {
	      value: _dialogOptions2
	    });
	  }
	  draw(userGroups, accessRights, renderTo) {
	    this.accessRightsInstance = new ui_accessrights.AccessRights({
	      component: 'bitrix:crm.config.perms.v2',
	      actionSave: 'save',
	      actionDelete: 'delete',
	      renderTo,
	      userGroups,
	      accessRights,
	      isSaveOnlyChangedRights: true,
	      useEntitySelectorDialogAsPopup: true,
	      entitySelectorDialogOptions: {
	        options: babelHelpers.classPrivateFieldLooseBase(this, _dialogOptions)[_dialogOptions](),
	        entitiesIdsEncoder: babelHelpers.classPrivateFieldLooseBase(this, _entitiesIdsEncoder)[_entitiesIdsEncoder](),
	        entitiesIdsDecoder: babelHelpers.classPrivateFieldLooseBase(this, _entitiesIdsDecoder)[_entitiesIdsDecoder](),
	        normalizeType: babelHelpers.classPrivateFieldLooseBase(this, _normalizeType)[_normalizeType]()
	      }
	    });
	    this.accessRightsInstance.draw();
	  }
	  sendActionRequest() {
	    if (this.accessRightsInstance) {
	      this.accessRightsInstance.sendActionRequest();
	    }
	  }
	  fireEventReset() {
	    if (this.accessRightsInstance) {
	      this.accessRightsInstance.fireEventReset();
	    }
	  }
	}
	function _dialogOptions2() {
	  return {
	    enableSearch: true,
	    context: 'CRM_PERMS',
	    entities: [{
	      id: 'user',
	      options: {
	        intranetUsersOnly: true,
	        emailUsers: false,
	        inviteEmployeeLink: false,
	        inviteGuestLink: false
	      }
	    }, {
	      id: 'department',
	      options: {
	        selectMode: 'usersAndDepartments',
	        allowSelectRootDepartment: true,
	        allowFlatDepartments: true
	      }
	    }, {
	      id: 'meta-user',
	      options: {
	        'all-users': true
	      }
	    }, {
	      id: 'projectmembers',
	      dynamicLoad: true,
	      options: {
	        addProjectMembersCategories: true
	      },
	      itemOptions: {
	        default: {
	          link: '',
	          linkTitle: ''
	        }
	      }
	    }, {
	      id: 'site_groups',
	      dynamicLoad: true,
	      dynamicSearch: true
	    }]
	  };
	}
	function _entitiesIdsEncoder2() {
	  return code => {
	    if (/^U(\d+)$/.test(code)) {
	      const match = code.match(/^U(\d+)$/) || null;
	      const userId = match ? match[1] : null;
	      return {
	        entityName: 'user',
	        id: userId
	      };
	    } else if (/^DR(\d+)$/.test(code)) {
	      const match = code.match(/^DR(\d+)$/) || null;
	      const departmentId = match ? match[1] : null;
	      return {
	        entityName: 'department',
	        id: `${departmentId}:F`
	      };
	    } else if (/^D(\d+)$/.test(code)) {
	      const match = code.match(/^D(\d+)$/) || null;
	      const departmentId = match ? match[1] : null;
	      return {
	        entityName: 'department',
	        id: departmentId
	      };
	    } else if (/^G(\d+)$/.test(code)) {
	      return {
	        entityName: 'site_groups',
	        id: code
	      };
	    } else if (/^SG(\d+)_([AEK])$/.test(code)) {
	      const match = code.match(/^SG(\d+)_([AEK])$/) || null;
	      const projectId = match ? match[1] : null;
	      const postfix = match ? match[2] : null;
	      return {
	        entityName: 'project',
	        id: `${projectId}:${postfix}`
	      };
	    }
	    return {
	      entityName: 'unknown',
	      id: code
	    };
	  };
	}
	function _entitiesIdsDecoder2() {
	  return item => {
	    const entityId = item.entityId;
	    let code = '';
	    switch (entityId) {
	      case 'user':
	        code = `U${item.id}`;
	        break;
	      case 'department':
	        if (/:F$/.test(item.id)) {
	          const match = item.id.match(/^(\d+):F$/);
	          const originalId = match ? match[1] : null;
	          code = `DR${originalId}`;
	        } else {
	          code = `D${item.id}`;
	        }
	        break;
	      case 'site_groups':
	        if (/^(\d+)$/.test(item.id)) {
	          code = `G${item.id}`;
	        } else {
	          code = item.id;
	        }
	        break;
	      case 'projectmembers':
	        const subType = item.customData.get('memberCategory');
	        const originalId = item.customData.get('parentId');
	        switch (subType) {
	          case 'owner':
	            code = `SG${originalId}_A`;
	            break;
	          case 'moderator':
	            code = `SG${originalId}_E`;
	            break;
	          case 'all':
	            code = `SG${originalId}_K`;
	            break;
	        }
	        break;
	    }
	    return code;
	  };
	}
	function _normalizeType2() {
	  return originalType => {
	    switch (originalType) {
	      case 'user':
	        return 'users';
	      case 'intranet':
	        return 'departments';
	      case 'socnetgroup':
	        return 'sonetgroups';
	      case 'group':
	        return 'groups';
	      default:
	        return '';
	    }
	  };
	}

	exports.AccessRightsWrapper = AccessRightsWrapper;

}((this.BX.Crm.Perms = this.BX.Crm.Perms || {}),BX.UI));
//# sourceMappingURL=access-rights-wrapper.bundle.js.map
