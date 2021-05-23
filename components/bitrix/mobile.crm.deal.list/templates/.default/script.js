BX.namespace("BX.Mobile.Crm.Deal.ListConverter");

BX.Mobile.Crm.Deal.ListConverter = {
	convertMessages: {},
	ajaxConvertPath: "",
	converterList: [],

	init: function(params)
	{
		if (typeof params === "object" && params)
		{
			this.convertMessages = params.convertMessages || {};
			this.ajaxConvertPath = params.ajaxConvertPath || "";
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
				invoice: permissions['CAN_CONVERT_TO_INVOICE'],
				quote: permissions['CAN_CONVERT_TO_QUOTE']
			},
			messages : this.convertMessages
		};

		if (!this.converterList[id])
			this.converterList[id] = new BX.Mobile.Crm.DealConversionScheme(jsParams);
		else
			this.converterList[id].showActionSheet();
	}
};