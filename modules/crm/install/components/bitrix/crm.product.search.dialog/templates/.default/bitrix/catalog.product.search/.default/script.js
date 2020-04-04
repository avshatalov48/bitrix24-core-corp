BX.namespace("BX.Crm");
if (typeof(BX.Crm.ProductSearchDialog) === "undefined")
{
	BX.Crm.ProductSearchDialog = (function () {
	
		var ProductSearchDialog = function (parameters) {
			this.tableId = parameters.tableId;
			this.callback = parameters.callback;
			this.callerName = parameters.callerName;
			this.currentUri = parameters.currentUri;
			this.popup = parameters.popup;
			this.iblockName = parameters.iblockName;
			this.jsEventsManagerId = parameters.jsEventsManagerId || "";
			this.jsEventsManager = BX.Crm[this.jsEventsManagerId] || null;
			this.leftContainerId = parameters.leftContainerId;
			this.leftContainer = BX(this.leftContainerId);
			this.splitterBtnId = parameters.splitterBtnId;
			this.splitterBtn = null;
			this.splitterBtnInner = null;
			this.splitter = null;
			this.searchTimer = null;
			this.isExternalSectionSelectDisabled = false;
			this.isSelectSectionEventDisabled = false;

			if (BX.type.isPlainObject(BX.Crm.ProductSearchDialog.splitterConfig))
			{
				this.splitterConfig = BX.Crm.ProductSearchDialog.splitterConfig;
			}
			else
			{
				BX.Crm.ProductSearchDialog.splitterConfig = this.splitterConfig = {
					isCollapsed: false,
					splitterBtnExpandValue: 270,
					leftContainerExpandValue: 275
				};
			}

			if (this.leftContainer && typeof(BX.Crm.Splitter) !== "undefined")
			{
				var container = this.leftContainer.parentNode;
				if (container)
				{
					this.splitterBtnInner = BX.create(
						'DIV',
						{
							attrs: {
								id: this.splitterBtnId + "_inner",
								className: "crm-catalog-scale-btn-inner"
							}
						}
					);
					this.splitterBtn = BX.create(
						'DIV',
						{
							attrs: {
								id: this.splitterBtnId,
								className: "crm-catalog-scale-btn"
							},
							children: [this.splitterBtnInner]
						}
					);
					if (BX.type.isPlainObject(this.splitterConfig))
					{
						this.leftContainer.style.width =
							(this.splitterConfig.isCollapsed ? 0 : this.splitterConfig.leftContainerExpandValue) + "px";
						this.splitterBtn.style.left =
							(this.splitterConfig.isCollapsed ? 0 : this.splitterConfig.splitterBtnExpandValue) + "px";
						if (this.splitterConfig.isCollapsed)
							BX.addClass(this.splitterBtn, "crm-catalog-close");
					}
					container.appendChild(this.splitterBtn);
					var self = this;
					this.splitter = new BX.Crm.Splitter({
						splitterMoveBtn :  this.splitterBtn,
						isCollapse: this.splitterConfig.isCollapsed,
						splitterElem : [
							{
								node : this.splitterBtn,
								attr : 'left',
								collapseValue : 0,
								expandValue : this.splitterConfig.splitterBtnExpandValue,
								minValue : 130,
								maxValue : 925
							},
							{
								node : this.leftContainer,
								attr : 'width',
								collapseValue : 0,
								expandValue : this.splitterConfig.leftContainerExpandValue,
								minValue : 135,
								maxValue : 930
							}
						],
						splitterCallBack : BX.delegate(this._handleSplitterState, this),
						collapse : {
							collapseBtn : this.splitterBtnInner,
							collapseCallback : function(isCollapse){
								if(this.moveBtn.classList)
									this.moveBtn.classList.toggle('crm-catalog-close');
								self._handleSplitterState(isCollapse);
							},
							collapseAnimDuration : 250,
							collapseAnimTimeFunc : BX.easing.makeEaseOut(BX.easing.transitions.linear)
						}
					});
				}
			}

			var me = this, keydownListener = function(e) {
				var dialogExists = !!BX(me.tableId+'_form');
				if (dialogExists)
				{
					if (!e.altKey && !e.ctrlKey && !e.metaKey
						&& document.activeElement.tagName != 'INPUT'
						&& document.activeElement.tagName != 'SELECT'
						&& document.activeElement.tagName != 'TEXTAREA')
					{
						//alpha and digits
						if (e.keyCode >= 48 && e.keyCode <= 90
							|| e.keyCode >= 96 && e.keyCode <= 105
							|| e.keyCode == 219 || e.keyCode == 221) {
							BX(me.tableId + '_query').focus();
						}
					}
				}
				else if(document.removeEventListener)
					document.removeEventListener('keydown', keydownListener);
				else
					document.detachEvent('onkeydown', keydownListener);
			};

			if(document.addEventListener)
				document.addEventListener('keydown', keydownListener);
			else if (document.attachEvent)
				document.attachEvent('onkeydown',keydownListener);

			this.jsEventsManager.registerEventHandler("CrmProduct_SelectSection", BX.proxy(this.onExternalSectionSelect, this));
		};

		ProductSearchDialog.prototype = {
			getIblockId: function ()
			{
				return BX(this.tableId + '_iblock').value;
			},
			getForm: function ()
			{
				return BX(this.tableId + '_form');
			},
			SelEl: function (params, scope)
			{
				this.clearSelection();
				if (scope)
					BX.removeClass(scope, "bx-active");
				if (params && params['id'])
					BX.onCustomEvent("CrmProductSearchDialog_SelectProduct", [params['id']]);
			},
			clearSelection: function () {
				if(document.selection && document.selection.empty) {
					document.selection.empty();
				} else if(window.getSelection) {
					var sel = window.getSelection();
					sel.removeAllRanges();
				}
			},
			onSubmitForm: function ()
			{
				var params = BX.ajax.prepareForm(this.getForm()), url = [];
				for (var k in params.data)
				{
					if (params.data.hasOwnProperty(k) && params.data[k])
					{
						url.push(encodeURIComponent(k) + '=' + encodeURIComponent(params.data[k]));
					}
				}
				url.push('sessid=' + BX.bitrix_sessid());
				url.push('JS_EVENTS_MANAGER_ID=' + this.jsEventsManagerId);

				url = this.currentUri + '?' + url.join('&');
				window["bxGrid_" + this.tableId].Reload(url);
				return false;
			},
			onSearch: function (s, time)
			{
				var queryInput = BX(this.tableId+'_query_value'), oldValue = queryInput.value;
				if (oldValue == s)
					return false;
				queryInput.value = s;

				var me = this;
				if (this.searchTimer != null) clearTimeout(this.searchTimer);
				this.searchTimer = setTimeout(function () {
					if (s.length == 0 || s.length > 2) {
						me.onSubmitForm();
					}
					BX(me.tableId + '_query_clear').style.display = s.length == 0 ? 'none' : 'inline';

					me.searchTimer = null;
				}, time || 500);
			},
			clearQuery: function ()
			{
				var el = BX(this.tableId + '_query'), old = el.value;
				el.value = '';
				if (old.length > 2)
					this.onSearch('', 10);
				return false;
			},
			checkSubstring: function()
			{
				var el = BX(this.tableId + '_query_substring'),
					input = BX(this.tableId + '_query_substring_value');
				if (BX.type.isElementNode(el) && BX.type.isElementNode(input))
				{
					input.value = (el.checked ? 'Y' : 'N');
					return true;
				}
				return false;
			},
			search: function()
			{
				var queryInput = BX(this.tableId + '_query_value'),
					query = BX(this.tableId + '_query');
				if (BX.type.isElementNode(queryInput) && BX.type.isElementNode(query))
				{
					queryInput.value = query.value;
					this.onSubmitForm();
				}
			},
			onExternalSectionSelect: function(params)
			{
				if (this.isExternalSectionSelectDisabled)
					return;

				this.isSelectSectionEventDisabled = true;

				if (params)
					this.onSectionChange(params["sectionId"], params["sectionName"]);

				this.isSelectSectionEventDisabled = false;
			},
			onSectionChange: function (sectionId, sectionName)
			{
				BX(this.tableId + '_section_id').value = sectionId;

				this.onSubmitForm();

				var labelEl = BX(this.tableId + '_section_label');
				labelEl.innerHTML = sectionId != '0' ? BX.util.htmlspecialchars(sectionName) + ' <span class="crm-search-tag-del" onclick="return ' + this.tableId + '_helper._handleLabelDelClick()"></span>' : '';
				labelEl.style.display = sectionId != '0' ? 'inline-block' : 'none';

				if (!this.isSelectSectionEventDisabled)
				{
					this.isExternalSectionSelectDisabled = true;
					this.jsEventsManager.fireEvent("CrmProduct_SelectSection", [{sectionId: sectionId, sectionName: sectionName}]);
					this.isExternalSectionSelectDisabled = false;
				}

				return false;
			},
			_handleLabelDelClick: function ()
			{
				this.onSectionChange("0", "");
			},
			_handleSplitterState: function(isCollapsed, paramsList)
			{
				var save = false;

				isCollapsed = !!isCollapsed;
				if (this.splitterConfig.isCollapsed !== isCollapsed)
				{
					this.splitterConfig.isCollapsed = isCollapsed;
					save = true;
				}

				if (paramsList instanceof Array)
				{
					for (var i=0; i<paramsList.length; i++)
					{
						if (paramsList[i].node && paramsList[i].node === this.splitterBtn)
						{
							this.splitterConfig.splitterBtnExpandValue = parseInt(paramsList[i].val);
							save = true;
							break;
						}
					}
				}

				if (save)
				{
					this.splitterConfig.leftContainerExpandValue = this.splitterConfig.splitterBtnExpandValue + 5;
					BX.Crm.ProductSearchDialog.splitterConfig = this.splitterConfig;
				}
			}
		};
	
		return ProductSearchDialog;
	})();
}
