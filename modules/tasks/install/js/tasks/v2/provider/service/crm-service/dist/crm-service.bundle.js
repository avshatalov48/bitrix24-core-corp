/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Provider = this.BX.Tasks.V2.Provider || {};
(function (exports,tasks_v2_const,tasks_v2_core,tasks_v2_lib_apiClient) {
	'use strict';

	const entityPrefixMap = {
	  [tasks_v2_const.EntitySelectorEntity.Deal]: 'D',
	  [tasks_v2_const.EntitySelectorEntity.Contact]: 'C',
	  [tasks_v2_const.EntitySelectorEntity.Company]: 'CO',
	  [tasks_v2_const.EntitySelectorEntity.Lead]: 'L',
	  [tasks_v2_const.EntitySelectorEntity.SmartInvoice]: 'SI'
	};
	const prefixEntityMap = Object.fromEntries(Object.entries(entityPrefixMap).map(it => it.reverse()));
	function mapDtoToModel(crmItemDto) {
	  return {
	    id: crmItemDto.id,
	    entityId: crmItemDto.entityId,
	    type: crmItemDto.type,
	    typeName: crmItemDto.typeName,
	    title: crmItemDto.title,
	    link: crmItemDto.link
	  };
	}
	function mapId(entityId, id) {
	  if (!entityPrefixMap[entityId]) {
	    const [typeId, itemId] = id.split(':');
	    return `T${Number(typeId).toString(16)}_${itemId}`;
	  }
	  return `${entityPrefixMap[entityId]}_${id}`;
	}
	function splitId(id) {
	  const [prefix, entityId] = id.split('_');
	  if (!prefixEntityMap[prefix]) {
	    return [tasks_v2_const.EntitySelectorEntity.DynamicMultiple, `${parseInt(prefix.slice(1), 16)}:${entityId}`];
	  }
	  return [prefixEntityMap[prefix], Number(entityId)];
	}

	const crmService = new class {
	  async list(id, crmItemIds) {
	    const data = await tasks_v2_lib_apiClient.apiClient.post('Task.CRM.Item.list', {
	      task: {
	        id,
	        crmItemIds
	      }
	    });
	    const crmItems = data.map(dto => mapDtoToModel(dto));
	    await this.$store.dispatch(`${tasks_v2_const.Model.CrmItems}/upsertMany`, crmItems);
	  }
	  get $store() {
	    return tasks_v2_core.Core.getStore();
	  }
	}();

	const CrmMappers = {
	  mapId,
	  splitId
	};

	exports.CrmMappers = CrmMappers;
	exports.crmService = crmService;

}((this.BX.Tasks.V2.Provider.Service = this.BX.Tasks.V2.Provider.Service || {}),BX.Tasks.V2.Const,BX.Tasks.V2,BX.Tasks.V2.Lib));
//# sourceMappingURL=crm-service.bundle.js.map
