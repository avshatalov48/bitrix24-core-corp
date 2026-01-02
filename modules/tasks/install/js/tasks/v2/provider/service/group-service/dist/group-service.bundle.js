/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Provider = this.BX.Tasks.V2.Provider || {};
(function (exports,tasks_v2_const,tasks_v2_core,tasks_v2_lib_apiClient) {
	'use strict';

	function mapDtoToModel(groupDto) {
	  var _groupDto$image, _groupDto$stages;
	  return {
	    id: groupDto.id,
	    name: groupDto.name,
	    image: (_groupDto$image = groupDto.image) == null ? void 0 : _groupDto$image.src,
	    type: groupDto.type,
	    stagesIds: (_groupDto$stages = groupDto.stages) == null ? void 0 : _groupDto$stages.map(({
	      id
	    }) => id)
	  };
	}
	function mapStageDtoToModel(stageDto) {
	  return {
	    id: stageDto.id,
	    title: stageDto.title,
	    color: stageDto.color
	  };
	}

	var _scrumInfoCache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scrumInfoCache");
	var _groupInfoPromises = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("groupInfoPromises");
	class GroupService {
	  constructor() {
	    Object.defineProperty(this, _scrumInfoCache, {
	      writable: true,
	      value: {
	        0: true
	      }
	    });
	    Object.defineProperty(this, _groupInfoPromises, {
	      writable: true,
	      value: {}
	    });
	  }
	  async getUrl(id, type) {
	    if (type !== tasks_v2_const.GroupType.Collab) {
	      return `/workgroups/group/${id}/`;
	    }
	    try {
	      return tasks_v2_lib_apiClient.apiClient.post('Group.Url.get', {
	        group: {
	          id,
	          type
	        }
	      });
	    } catch (error) {
	      console.error('GroupService: getUrl error', error);
	      return '';
	    }
	  }
	  async getStages(id) {
	    try {
	      const data = await tasks_v2_lib_apiClient.apiClient.post('Group.Stage.list', {
	        group: {
	          id
	        }
	      });
	      const stages = data.map(stage => mapStageDtoToModel(stage));
	      const stagesIds = stages.map(stage => stage.id);
	      await Promise.all([tasks_v2_core.Core.getStore().dispatch(`${tasks_v2_const.Model.Stages}/upsertMany`, stages), tasks_v2_core.Core.getStore().dispatch(`${tasks_v2_const.Model.Groups}/update`, {
	        id,
	        fields: {
	          stagesIds
	        }
	      })]);
	    } catch (error) {
	      console.error('GroupService: getStages error', error);
	    }
	  }
	  async getGroup(id) {
	    try {
	      const data = await tasks_v2_lib_apiClient.apiClient.post('Group.get', {
	        group: {
	          id
	        }
	      });
	      const group = mapDtoToModel(data);
	      await tasks_v2_core.Core.getStore().dispatch(`${tasks_v2_const.Model.Groups}/insert`, group);
	      return group;
	    } catch (error) {
	      console.error('GroupService: getGroup error', error);
	      return null;
	    }
	  }
	  async getScrumInfo(taskId) {
	    if (this.hasScrumInfo(taskId)) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _scrumInfoCache)[_scrumInfoCache][taskId] = true;
	    try {
	      var _data$epic;
	      const data = await tasks_v2_lib_apiClient.apiClient.post('Scrum.getTaskInfo', {
	        taskId
	      });
	      await Promise.all([tasks_v2_core.Core.getStore().dispatch(`${tasks_v2_const.Model.Epics}/upsert`, data.epic), tasks_v2_core.Core.getStore().dispatch(`${tasks_v2_const.Model.Tasks}/update`, {
	        id: taskId,
	        fields: {
	          storyPoints: data.storyPoints,
	          epicId: (_data$epic = data.epic) == null ? void 0 : _data$epic.id
	        }
	      })]);
	    } catch (error) {
	      console.error('GroupService: getScrumInfo error', error);
	    }
	  }
	  hasScrumInfo(taskId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _scrumInfoCache)[_scrumInfoCache][taskId];
	  }
	  async getGroupInfo(groupId) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _groupInfoPromises)[_groupInfoPromises][groupId]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _groupInfoPromises)[_groupInfoPromises][groupId];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _groupInfoPromises)[_groupInfoPromises][groupId] = new Resolvable();
	    try {
	      var _data$GroupFields$Own, _data$GroupFields$Own2, _data$GroupFields$Sub;
	      const GroupFields = Object.freeze({
	        OwnerData: 'OWNER_DATA',
	        DateCreate: 'DATE_CREATE',
	        SubjectData: 'SUBJECT_DATA',
	        NumberOfMembers: 'NUMBER_OF_MEMBERS'
	      });
	      const {
	        data
	      } = await BX.ajax.runAction('socialnetwork.api.workgroup.get', {
	        data: {
	          params: {
	            select: Object.values(GroupFields),
	            groupId
	          }
	        }
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _groupInfoPromises)[_groupInfoPromises][groupId].resolve({
	        ownerId: (_data$GroupFields$Own = data[GroupFields.OwnerData]) == null ? void 0 : _data$GroupFields$Own.ID,
	        ownerName: (_data$GroupFields$Own2 = data[GroupFields.OwnerData]) == null ? void 0 : _data$GroupFields$Own2.FORMATTED_NAME,
	        dateCreate: data[GroupFields.DateCreate],
	        subjectTitle: (_data$GroupFields$Sub = data[GroupFields.SubjectData]) == null ? void 0 : _data$GroupFields$Sub.NAME,
	        numberOfMembers: data[GroupFields.NumberOfMembers]
	      });
	      return babelHelpers.classPrivateFieldLooseBase(this, _groupInfoPromises)[_groupInfoPromises][groupId];
	    } catch (error) {
	      console.error('GroupService: getGroupInfo error', error);
	      return {};
	    }
	  }
	}
	const groupService = new GroupService();
	function Resolvable() {
	  const promise = new Promise(resolve => {
	    this.resolve = resolve;
	  });
	  promise.resolve = this.resolve;
	  return promise;
	}

	const GroupMappers = {
	  mapDtoToModel,
	  mapStageDtoToModel
	};

	exports.GroupMappers = GroupMappers;
	exports.groupService = groupService;

}((this.BX.Tasks.V2.Provider.Service = this.BX.Tasks.V2.Provider.Service || {}),BX.Tasks.V2.Const,BX.Tasks.V2,BX.Tasks.V2.Lib));
//# sourceMappingURL=group-service.bundle.js.map
