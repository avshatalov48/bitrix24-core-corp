/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Provider = this.BX.Tasks.V2.Provider || {};
(function (exports,tasks_v2_const,tasks_v2_core,tasks_v2_lib_apiClient) {
	'use strict';

	function mapDtoToModel(flowDto) {
	  return {
	    id: flowDto.id,
	    name: flowDto.name,
	    efficiency: flowDto.efficiency
	  };
	}

	class FlowService {
	  getUrl(id, userId) {
	    return `/company/personal/user/${userId}/tasks/flow/?ID_numsel=exact&ID_from=${id}&ID_to=${id}&apply_filter=Y`;
	  }
	  async getFlow(id) {
	    try {
	      const data = await tasks_v2_lib_apiClient.apiClient.post('Flow.get', {
	        flow: {
	          id
	        }
	      });

	      // TODO: insert group
	      console.log(data);
	      const flow = mapDtoToModel(data);
	      await tasks_v2_core.Core.getStore().dispatch(`${tasks_v2_const.Model.Flows}/insert`, flow);
	    } catch (error) {
	      console.error('FlowService: getFlow error', error);
	    }
	  }
	}
	const flowService = new FlowService();

	const FlowMappers = {
	  mapDtoToModel
	};

	exports.FlowMappers = FlowMappers;
	exports.flowService = flowService;

}((this.BX.Tasks.V2.Provider.Service = this.BX.Tasks.V2.Provider.Service || {}),BX.Tasks.V2.Const,BX.Tasks.V2,BX.Tasks.V2.Lib));
//# sourceMappingURL=flow-service.bundle.js.map
