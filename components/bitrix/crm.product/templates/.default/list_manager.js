BX.namespace("BX.Crm");

if (typeof(BX.Crm.ProductListManagerClass) === "undefined")
{
	BX.Crm.ProductListManagerClass = function()
	{
		this._settings = {};

		this.splitter = null;
	};

	BX.Crm.ProductListManagerClass.prototype = {
		initialize: function (settings)
		{
			this._settings = settings ? settings : {};

			this._settings.splitterBtnId = this._settings.splitterBtnId || "";
			this._settings.splitterNodeId = this._settings.splitterNodeId || "";
			this._settings.splitterNode = BX(this._settings.splitterNodeId);
			this._settings.hideBtnId = this._settings.hideBtnId || "";
			this._settings.viewOptionId = this._settings.viewOptionId || "";

			if (typeof(this._settings.splitterState) !== "object")
			{
				this._settings.splitterState = {
					rightSideWidth: 250,
					rightSideClosed: "N"
				}
			}
			this._settings.splitterState.rightSideWidth = parseInt(this._settings.splitterState.rightSideWidth);
			if (this._settings.splitterState.rightSideWidth < 100)
			{
				this._settings.splitterState.rightSideWidth = 250;
				this._settings.splitterState.rightSideClosed = "N";
			}
			if (this._settings.splitterState.rightSideClosed !== "Y"
				&& this._settings.splitterState.rightSideClosed !== "N")
			{
				this._settings.splitterState.rightSideClosed = "N";
			}

			this.ajaxUrl = "/bitrix/components/bitrix/crm.product/ajax.php";

			this.initSplitter();
		},
		initSplitter: function()
		{
			var self = this;
			this.splitter = new BX.Crm.Splitter({
				splitterMoveBtn :  BX(this._settings.splitterBtnId),
				isCollapse: this._settings.splitterState.rightSideClosed === "Y",
				splitterElem : [
					{
						node : BX(this._settings.splitterNodeId),
						attr : 'width',
						collapseValue : 0,
						expandValue: this._settings.splitterState.rightSideWidth,
						minValue : 100,
						maxValue : 930,
						isInversion : true
					}
				],
				splitterCallBack : BX.delegate(this._handleSplitterState, this),
				collapse : {
					collapseBtn : BX(this._settings.hideBtnId),
					collapseCallback : function(isCollapse){
						if(this.collapseBtn.classList)
							this.collapseBtn.classList.toggle('bx-crm-goods-close');
						self._handleSplitterState(isCollapse);
					},
					collapseAnimDuration : 250,
					collapseAnimTimeFunc : BX.easing.makeEaseOut(BX.easing.transitions.linear)
				}
			});
		},
		_handleSplitterState: function(isCollapsed, paramsList)
		{
			var save = false;

			isCollapsed = !!isCollapsed;
			if ((this._settings.splitterState.rightSideClosed === "Y") !== isCollapsed)
			{
				this._settings.splitterState.rightSideClosed = isCollapsed ? "Y" : "N";
				save = true;
			}

			if (paramsList instanceof Array)
			{
				for (var i=0; i<paramsList.length; i++)
				{
					if (paramsList[i].node && paramsList[i].node === this._settings.splitterNode)
					{
						this._settings.splitterState.rightSideWidth = parseInt(paramsList[i].val);
						save = true;
						break;
					}
				}
			}

			if (save)
				this.saveViewOptions(this._settings.splitterState);
		},
		saveViewOptions: function(options)
		{
			BX.ajax({
				method: "POST",
				dataType: "json",
				url: this.ajaxUrl,
				data: {
					sessid: BX.bitrix_sessid(),
					action: "saveViewOptions",
					viewOptionId: this._settings.viewOptionId,
					rightSideWidth: options.rightSideWidth || 250,
					rightSideClosed: options.rightSideClosed || "N"
				}
			})
		}
	};

	BX.Crm.ProductListManagerClass.create = function(settings)
	{
		var self = new BX.Crm.ProductListManagerClass();
		self.initialize(settings);
		return self;
	};
}
