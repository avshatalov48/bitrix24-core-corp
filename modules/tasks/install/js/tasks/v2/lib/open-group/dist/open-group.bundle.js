/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,tasks_v2_lib_hrefClick,tasks_v2_provider_service_groupService) {
	'use strict';

	const openGroup = async (id, type) => {
	  const href = await tasks_v2_provider_service_groupService.groupService.getUrl(id, type);
	  tasks_v2_lib_hrefClick.hrefClick(href);
	};

	exports.openGroup = openGroup;

}((this.BX.Tasks.V2.Lib = this.BX.Tasks.V2.Lib || {}),BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=open-group.bundle.js.map
