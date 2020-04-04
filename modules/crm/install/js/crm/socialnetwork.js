if(typeof(BX.CrmSocialnetworkIntegration) == 'undefined')
{
	BX.CrmSocialnetworkIntegration = function()
	{
		BX.addCustomEvent("BX.CommentAux.initialize", function() {
			if (typeof BX.CommentAux != 'undefined')
			{
				BX.CommentAux.postEventTypeList.push('CRM_LEAD');
				BX.CommentAux.commentEventTypeList.push('CRM_ENTITY_COMMENT');
			}
		});
	};

	BX.CrmSocialnetworkIntegration();
}
