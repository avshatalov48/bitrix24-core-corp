BX.namespace("BX.Mobile.Crm.Quote.ListConverter");

BX.Mobile.Crm.Quote.ListConverter = {
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
				deal: permissions['CAN_CONVERT_TO_DEAL']
			},
			messages : this.convertMessages
		};

		if (!this.converterList[id])
			this.converterList[id] = new BX.Mobile.Crm.QuoteConversionScheme(jsParams);
		else
			this.converterList[id].showActionSheet();
	}
};