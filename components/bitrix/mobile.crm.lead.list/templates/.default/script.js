BX.namespace("BX.Mobile.Crm.Lead.ListConverter");

BX.Mobile.Crm.Lead.ListConverter = {
	convertMessages: {},
	ajaxConvertPath: "",
	contactSelectUrl: "",
	companySelectUrl: "",
	converterList: [],

	init: function(params)
	{
		if (typeof params === "object" && params)
		{
			this.convertMessages = params.convertMessages || {};
			this.ajaxConvertPath = params.ajaxConvertPath || "";
			this.contactSelectUrl = params.contactSelectUrl || "";
			this.companySelectUrl = params.companySelectUrl || "";
		}
	},

	showConvertDialog: function(id, permissions)
	{
		if (!id || !permissions)
			return;

		var jsParams = {
			ajaxPath: this.ajaxConvertPath,
			entityId: id,
			permissions: {
				contact: permissions['CAN_CONVERT_TO_CONTACT'],
				company: permissions['CAN_CONVERT_TO_COMPANY'],
				deal: permissions['CAN_CONVERT_TO_DEAL']
			},
			messages : this.convertMessages,
			contactSelectUrl: this.contactSelectUrl,
			companySelectUrl: this.companySelectUrl
		};

		if (!this.converterList[id])
			this.converterList[id] = new BX.Mobile.Crm.LeadConversionScheme(jsParams);
		else
			this.converterList[id].showActionSheet();
	}
};