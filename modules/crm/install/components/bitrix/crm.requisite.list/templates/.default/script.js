BX.namespace("BX.Crm");

BX.Crm.RequisiteGridEditorClass = (function ()
{
	var RequisiteGridEditorClass = function (parameters)
	{
		this.gridId = parameters.gridId;
		this.requisiteEntityTypeId = parameters.requisiteEntityTypeId;
		this.requisiteEntityId = parameters.requisiteEntityId;
		this.readOnlyMode = !!parameters.readOnlyMode;
		this.messages = parameters.messages || {};
		this.requisitePopupManager = null;
		this.requisiteAjaxUrl = parameters.requisiteAjaxUrl;
		this.requisitePopupAjaxUrl = parameters.requisitePopupAjaxUrl;
	};

	RequisiteGridEditorClass.prototype = {
		getMessage: function(msgId)
		{
			return this.messages[msgId];
		},
		onRequisiteEdit: function(requisiteEntityTypeId, requisiteEntityId, presetId, requisiteId, requisiteData,
                                    requisiteDataSign, blockIndex, copyMode, readOnlyMode)
		{
			if (BX.type.isNumber(blockIndex) && blockIndex >= 0 || BX.type.isNotEmptyString(blockIndex))
				blockIndex = parseInt(blockIndex);
			else
				blockIndex = -1;

			requisiteData = (BX.type.isNotEmptyString(requisiteData)) ? requisiteData : "";
			requisiteDataSign = (BX.type.isNotEmptyString(requisiteDataSign)) ? requisiteDataSign : "";
			copyMode = !!copyMode;
			readOnlyMode = !!readOnlyMode;

			if (!this.requisitePopupManager)
			{
				this.requisitePopupManager = new BX.Crm.RequisitePopupFormManagerClass({
					editor: this,
					blockArea: null,
					requisiteEntityTypeId: requisiteEntityTypeId,
					requisiteEntityId: requisiteEntityId,
					requisiteId: requisiteId,
					requisiteData: requisiteData,
					requisiteDataSign: requisiteDataSign,
					presetId: presetId,
					requisiteAjaxUrl: this.requisiteAjaxUrl,
					requisitePopupAjaxUrl: this.requisitePopupAjaxUrl,
					popupDestroyCallback: BX.delegate(this.onRequisitePopupDestroy, this),
					afterRequisiteEditCallback: BX.delegate(this.onAfterRequisiteEdit, this),
					blockIndex: blockIndex,
					copyMode: copyMode,
					readOnlyMode: readOnlyMode
				});
				this.requisitePopupManager.openPopup();
			}
		},
		onAfterRequisiteEdit: function(requisiteId, requisiteData, requisiteDataSign)
		{
			if (BX.type.isNotEmptyString(this.gridId))
			{
				var grid = window["bxGrid_" + this.gridId];
				if (grid)
					grid.Reload();
			}
		},
		onRequisitePopupDestroy: function()
		{
			if (this.requisitePopupManager)
			{
				this.requisitePopupManager.destroy();
				this.requisitePopupManager = null;
			}
		}
	};

	return RequisiteGridEditorClass;
})();
