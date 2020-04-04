if(typeof(BX.CrmSonetEventEditor) === "undefined")
{
	BX.CrmSonetEventEditor = function()
	{
		this._settings = {};
		this._id = "";
		this._prefix = "";
		this._editorName = "";
		this._formId = "";

		this._editorContainer = null;
		this._containerMicro = null;
		this._containerMicroInner = null;
		this._containerMicroHeight = null;

		this._animation = null;
		this._animationContainer = null;

		this._msgTitleWrapper = this._msgTitle = this._msgTitleCloseBtn = this._msgTitleToggleBtn = this._enableMsgTitle = null;
		this._isMsgTitleEnabled = false;
		this._isVisible = false;
		this._isFormSubmit = false;
	};

	BX.CrmSonetEventEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._prefix = this.getSetting("prefix");
			this._formId = this.getSetting("formId");

			this._msgTitleWrapper = this._resolveElement("message_title_wrapper");
			this._msgTitle = this._resolveElement("message_title");
			this._enableMsgTitle = this._resolveElement("enable_message_title");
			this._isMsgTitleEnabled = BX.type.isDomNode(this._enableMsgTitle) && this._enableMsgTitle.value === "Y";


			this._msgTitleCloseBtn = this._resolveElement("message_title_close_btn");
			if(BX.type.isDomNode(this._msgTitleCloseBtn))
			{
				BX.bind(this._msgTitleCloseBtn, "click", BX.delegate(this._onMessageTitleCloseButtonClick, this))
			}

			this._msgTitleToggleBtn = this._resolveElement("message_title_toggle_btn");
			if(BX.type.isDomNode(this._msgTitleToggleBtn))
			{
				BX.bind(this._msgTitleToggleBtn, "click", BX.delegate(this._onMessageTitleToggleButtonClick, this))
			}

			this._containerMicro = this._resolveElement("micro");
			this._containerMicroInner = this._resolveElement("micro_inner");
			this._containerMicroHeight = this._containerMicro.offsetHeight;

			if(BX.type.isDomNode(this._containerMicro))
			{
				BX.bind(this._containerMicro, "click", BX.delegate(this.showEditor, this))
			}

			this._editorName = this.getSetting("editorName");
			this._editorContainer = this._editorName !== "" ? BX("div" + this._editorName) : null;
			if(BX.type.isDomNode(this._editorContainer))
			{
				BX.addCustomEvent(this._editorContainer, "OnAfterShowLHE", BX.delegate(this._onEditorShow, this));
				BX.addCustomEvent(this._editorContainer, "OnAfterHideLHE", BX.delegate(this._onEditorHide, this));
				if (this._editorContainer.style.display !== "none")
					this._onEditorShow();
				else
					this._onEditorHide();
			}

			this._animationContainer = BX("microblog-form");
			var cancelBtn = this._resolveElement("button_cancel");
			if(BX.type.isDomNode(cancelBtn))
			{
				BX.bind(cancelBtn, "click", BX.delegate(this._onCancelButtonClick, this));
			}

			var saveBtn = this._resolveElement("button_save");
			if(BX.type.isDomNode(saveBtn))
			{
				BX.bind(saveBtn, "click", BX.delegate(this._onSaveButtonClick, this));
			}
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		setSetting: function(name, val)
		{
			this._settings[name] = val;
		},
		enableMessageTitle: function(enabled)
		{
			if(!this._msgTitleWrapper)
			{
				return;
			}

			enabled = !!enabled;
			if(this._isMsgTitleEnabled !== enabled)
			{
				this._enableMsgTitle.value = enabled ? 'Y' : 'N';
				this._isMsgTitleEnabled = enabled;
				this._showMessageTitle(enabled);
			}
		},
		_showMessageTitle: function(show)
		{
			show = !!show;
			if(BX.type.isDomNode(this._msgTitleWrapper))
			{
				this._msgTitleWrapper.style.display = show ? "" : "none";
			}

			if(show && BX.type.isDomNode(this._msgTitle))
			{
				BX.focus(this._msgTitle);
			}
		},
		_onMessageTitleCloseButtonClick: function(e)
		{
			this.enableMessageTitle(false);
		},
		_onMessageTitleToggleButtonClick: function(e)
		{
			var enabled = !this._isMsgTitleEnabled;
			this.enableMessageTitle(enabled);

			if(enabled)
			{
				BX.addClass(this._msgTitleToggleBtn, "feed-add-post-form-btn-active");
			}
			else
			{
				BX.removeClass(this._msgTitleToggleBtn, "feed-add-post-form-btn-active");
			}
		},
		_resolveElement: function(id)
		{
			var elementId = id;
			if(this._prefix)
			{
				elementId = this._prefix + elementId
			}

			return BX(elementId);
		},
		_onEditorShow: function()
		{
			this._processEditorVisibilityChange(true);
		},
		_onEditorHide: function()
		{
			this._processEditorVisibilityChange(false);
		},
		_processEditorVisibilityChange: function(visible)
		{
			visible = !!visible;
			this._isVisible = visible;

			if(this._isMsgTitleEnabled)
			{
				this._showMessageTitle(visible);
			}

			if(visible)
			{
				BX.adjust(
					this._resolveElement("button_block"),
					{ style: { height: "auto", opacity: "1" } }
				);

				var editor = window.BXHtmlEditor.Get(this._editorName);
				if(editor)
				{
					editor.Focus();
				}
			}
			else
			{
				BX.adjust(
					this._resolveElement("button_block"),
					{ style: { height: "0", opacity: "0" } }
				);

				if(this._isMsgTitleEnabled)
				{
					this._showMessageTitle(false);
				}
			}
		},
		showEditor : function()
		{
			if(this._isVisible)
			{
				return;
			}
			this._containerMicro.style.display = 'none';
			BX.onCustomEvent(this._editorContainer, "OnShowLHE", [true]);
		},
		hideEditor : function()
		{
			if(!this._isVisible)
			{
				return;
			}
			this._containerMicro.style.display = 'block';
			this._startAnimation();
			BX.onCustomEvent(this._editorContainer, "OnShowLHE", [false]);
			this._endAnimation();
		},
		_startAnimation : function()
		{
			if (this._animation)
				this._animation.stop();

			var container = this._animationContainer;

			if (this._containerMicroHeight > 0)
			{
				this.animationStartHeight = this._containerMicroHeight;
				this._containerMicroHeight = 0;
			}
			else
			{
				this.animationStartHeight = container.parentNode.offsetHeight;
			}

			container.parentNode.style.height = this.animationStartHeight + "px";
			container.parentNode.style.overflowY = "hidden";
			container.parentNode.style.position = "relative";
			container.style.opacity = 0;
		},
		_endAnimation : function()
		{
			var container = this._animationContainer;

			this._animation = new BX.easing({
				duration : 500,
				start : { height: this.animationStartHeight, opacity : 0 },
				finish : { height: container.offsetHeight + container.offsetTop, opacity : 100 },
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),

				step : function(state){
					container.parentNode.style.height = state.height + "px";
					container.style.opacity = state.opacity / 100;
				},

				complete : BX.proxy(function() {
					container.style.cssText = "";
					container.parentNode.style.cssText = "";
					this._animation = null;
				}, this)

			});

			this._animation.animate();
		},
		_onCancelButtonClick: function(e)
		{
			this.hideEditor();
			return BX.PreventDefault(e);
		},
		_onSaveButtonClick: function(e)
		{
			this.submitForm();
			return BX.PreventDefault(e);
		},
		submitForm: function()
		{
			if(this._isFormSubmit)
			{
				return;
			}

			var form = BX(this._formId);

			//if(BX('blog-title').style.display == "none")
			//	BX('POST_TITLE').value = "";

			BX.submit(form, 'save');
			this._isFormSubmit = true;
		},
		createSubmitHandler: function()
		{
			return BX.delegate(this.submitForm, this);
		}
	};

	BX.CrmSonetEventEditor.items = {};
	BX.CrmSonetEventEditor.create = function(id, settings)
	{
		var self = new BX.CrmSonetEventEditor();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};

	BX.CrmSonetEventEditor.destinationInit = function(params)
	{
		var whereFormName = 'destination_crm_where';

		BX.SocNetLogDestination.init({
			name : whereFormName,
			searchInput : BX('feed-add-post-where-input'),
			extranetUser :  false,
			bindMainPopup : {
				node: BX('feed-add-post-where-container'),
				offsetTop: '5px',
				offsetLeft: '15px'
			},
			bindSearchPopup : {
				node : BX('feed-add-post-where-container'),
				offsetTop : '5px',
				offsetLeft: '15px'
			},
			callback : {
				select : BX.CrmSonetEventEditor.BXfpdSelectCallback,
				unSelect : BX.delegate(BX.SocNetLogDestination.BXfpUnSelectCallback, {
					formName: whereFormName,
					inputContainerName: 'feed-add-post-where-item',
					inputName: 'feed-add-post-where-input',
					tagInputName: 'bx-where-tag',
					tagLink1: BX.message('CRM_SL_EVENT_EDIT_MPF_WHERE_1'),
					tagLink2: BX.message('CRM_SL_EVENT_EDIT_MPF_WHERE_2')
				}),
				openDialog : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
					inputBoxName: 'feed-add-post-where-input-box',
					inputName: 'feed-add-post-where-input',
					tagInputName: 'bx-where-tag'
				}),
				closeDialog : BX.delegate(BX.SocNetLogDestination.BXfpCloseDialogCallback, {
					inputBoxName: 'feed-add-post-where-input-box',
					inputName: 'feed-add-post-where-input',
					tagInputName: 'bx-where-tag'
				}),
				openSearch : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
					inputBoxName: 'feed-add-post-where-input-box',
					inputName: 'feed-add-post-where-input',
					tagInputName: 'bx-where-tag'
				}),
				closeSearch : BX.delegate(BX.SocNetLogDestination.BXfpCloseSearchCallback, {
					inputBoxName: 'feed-add-post-where-input-box',
					inputName: 'feed-add-post-where-input',
					tagInputName: 'bx-where-tag'
				})
			},
			items : params.items,
			itemsLast : params.itemsLast,
			itemsSelected : params.itemsSelected,
			isCrmFeed : true,
			useClientDatabase: false,
			destSort: {},
			allowAddUser: false,
			allowSearchCrmEmailUsers: false,
			allowUserSearch: false,
			userNameTemplate: (typeof params.userNameTemplate != 'undefined' ? params.userNameTemplate : '')
		});

		BX.bind(BX('feed-add-post-where-input'), 'keyup', BX.delegate(BX.SocNetLogDestination.BXfpSearch, {
			formName: whereFormName,
			inputName: 'feed-add-post-where-input',
			tagInputName: 'bx-where-tag'
		}));
		BX.bind(BX('feed-add-post-where-input'), 'keydown', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
			formName: whereFormName,
			inputName: 'feed-add-post-where-input'
		}));
		BX.bind(BX('feed-add-post-where-input'), 'blur', BX.delegate(BX.SocNetLogDestination.BXfpBlurInput, {
			inputBoxName: 'feed-add-post-where-input-box',
			tagInputName: 'bx-where-tag'
		}));
		BX.bind(BX('bx-where-tag'), 'focus', function(e) {
			BX.SocNetLogDestination.openDialog(
				whereFormName,
				{
					bByFocusEvent: true
				}
			);
			return BX.PreventDefault(e);
		});
		BX.bind(BX('feed-add-post-where-container'), 'click', function(e) {
			BX.SocNetLogDestination.openDialog(whereFormName);
			return BX.PreventDefault(e);
		});

		BX.SocNetLogDestination.BXfpSetLinkName({
			formName: whereFormName,
			tagInputName: 'bx-where-tag',
			tagLink1: BX.message('CRM_SL_EVENT_EDIT_MPF_WHERE_1'),
			tagLink2: BX.message('CRM_SL_EVENT_EDIT_MPF_WHERE_2')
		});
	};

	BX.CrmSonetEventEditor.BXfpdSelectCallback = function(item, type, search, bUndeleted)
	{
		BX.SocNetLogDestination.BXfpSelectCallback({
			item: item,
			type: type,
			bUndeleted: bUndeleted,
			containerInput: BX('feed-add-post-where-item'),
			valueInput: BX('feed-add-post-where-input'),
			formName: 'destination_crm_where',
			tagInputName: 'bx-where-tag',
			tagLink1: BX.message('CRM_SL_EVENT_EDIT_MPF_WHERE_1'),
			tagLink2: BX.message('CRM_SL_EVENT_EDIT_MPF_WHERE_2')
		});
	};


}
