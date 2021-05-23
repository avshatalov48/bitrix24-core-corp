;(function ()
{
	var namespace = BX.namespace('BX.Intranet.UserProfile');
	if (namespace.ProfilePost)
	{
		return;
	}
	namespace.ProfilePost = function(params) {
		this.init(params);
	};

	namespace.ProfilePost.prototype = {

		init: function(params)
		{
			this.managerInstance = params.managerInstance;
			this.options = params.options;

			this.loaderNode = BX('intranet-user-profile-about-loader');
			this.wrapperNode = BX('intranet-user-profile-about-wrapper');
			this.containerNode = BX('intranet-user-profile-post-container');
			this.editLinkNode = BX('intranet-user-profile-post-edit-link');
			this.stubButtonNode = BX('intranet-user-profile-post-edit-stub-button');

			this.loader = null;

			this.LHEEventNode = BX('div' + this.options.profilePostData.lheId);

			this.getData();

			this.managerInstance.processSliderCloseEvent({
				entityType: 'profilePost',
				callback: function() {
					BX.cleanNode(this.wrapperNode);
					this.getData();
				}.bind(this)
			});

			if (this.editLinkNode)
			{
				BX.bind(this.editLinkNode, 'click', function() {
					this.getFormData();
				}.bind(this));
			}

			if (this.stubButtonNode)
			{
				BX.bind(this.stubButtonNode, 'click', function() {
					this.getFormData();
				}.bind(this));
			}

			if (this.LHEEventNode)
			{
				BX.addCustomEvent(this.LHEEventNode, 'OnProfilePostFormAfterShow', function(text, data) {
					this.OnProfilePostFormAfterShow(text, data);
				}.bind(this));

				BX.addCustomEvent(this.LHEEventNode, 'OnClickSubmit', function(ob) {
					this.OnClickSubmit(ob);
				}.bind(this));

				BX.addCustomEvent(this.LHEEventNode, 'OnClickCancel', function(ob) {
					this.setContainerEditMode(false);
					this.showPostContainer(true);
				}.bind(this));
			}
		},

		getData: function()
		{
			BX.addClass(this.loaderNode, 'intranet-user-profile-loader-getdata');
			this.loader = this.managerInstance.showLoader({
				node: this.loaderNode,
				loader: this.loader
			});

			BX.ajax.runComponentAction(this.managerInstance.componentName, 'getProfileBlogPost', {
				mode: 'class',
				signedParameters: this.managerInstance.signedParameters,
				data: {}
			}).then(function (response) {
				this.managerInstance.hideLoader({
					node: this.loaderNode,
					loader: this.loader
				});

				BX.removeClass(this.loaderNode, 'intranet-user-profile-loader-getdata');
				this.setContainerEmpty(!BX.type.isNotEmptyObject(response.data));

				if (BX.type.isNotEmptyObject(response.data))
				{
					this.showData(response.data);
				}
			}.bind(this), function (response) {
				this.managerInstance.hideLoader({
					node: this.loaderNode,
					loader: this.loader
				});
				BX.removeClass(this.loaderNode, 'intranet-user-profile-loader-getdata');
			}.bind(this));
		},

		setContainerEmpty: function(status)
		{
			if (this.containerNode)
			{
				if (status)
				{
					BX.addClass(this.containerNode, 'intranet-user-profile-container-empty');
				}
				else
				{
					BX.removeClass(this.containerNode, 'intranet-user-profile-container-empty');
				}
			}
		},

		setContainerEditMode: function(status)
		{
			if (this.containerNode)
			{
				if (status)
				{
					BX.addClass(this.containerNode, 'intranet-user-profile-container-editmode');
				}
				else
				{
					BX.removeClass(this.containerNode, 'intranet-user-profile-container-editmode');
				}
			}
		},

		showData: function(data)
		{
			if (!this.wrapperNode)
			{
				return;
			}

			this.showPostContainer(true);

			if (
				BX.type.isNotEmptyObject(data)
				&& BX.type.isNotEmptyObject(data.additionalParams)
				&& BX.type.isNotEmptyObject(data.additionalParams.POST)
			)
			{
				var postData = data.additionalParams.POST;

				if (BX.type.isNotEmptyString(postData.TITLE))
				{
					this.wrapperNode.appendChild(BX.create('DIV', {
						props: {
							className: 'intranet-user-profile-container-body-title'
						},
						html: postData.TITLE
					}));
				}

				if (BX.type.isNotEmptyString(data.html))
				{
					var contNode = BX.create('DIV', {
						props: {
							className: 'intranet-user-profile-about'
						}
					});
					this.wrapperNode.appendChild(contNode);
					BX.html(contNode, data.html);
				}

				if (BX.type.isNotEmptyObject(postData.UF_RENDERED))
				{
					BX.Intranet.UserProfile.Utils.processAjaxBlock({
						data: postData.UF_RENDERED,
						className: 'intranet-user-profile-about-props',
						containerNode: this.wrapperNode
					});
				}
			}
		},

		getFormData: function()
		{
			this.showPostContainer(false);
			this.setContainerEditMode(true);

			this.managerInstance.showLoader({
				node: this.loaderNode,
				loader: this.loader
			});

			BX.ajax.runComponentAction(this.managerInstance.componentName, 'getProfileBlogPostForm', {
				mode: 'class',
				signedParameters: this.managerInstance.signedParameters,
				data: {}
			}).then(function (response) {
				this.managerInstance.hideLoader({
					node: this.loaderNode,
					loader: this.loader
				});
				this.showFormData(response.data);
			}.bind(this), function (response) {
				this.managerInstance.hideLoader({
					node: this.loaderNode,
					loader: this.loader
				});
				this.showPostContainer(true);
			}.bind(this));
		},

		showPostContainer: function(value)
		{
			if (!this.wrapperNode)
			{
				return;
			}

			this.wrapperNode.style.display = (!!value ? 'block' : 'none');
		},

		showFormData: function(data)
		{
			if (this.LHEEventNode)
			{
				BX.onCustomEvent(this.LHEEventNode, 'OnShowLHE', ['show']);
				BX.onCustomEvent(this.LHEEventNode, 'OnProfilePostFormAfterShow', [ data ] );
			}
		},

		OnProfilePostFormAfterShow: function(data)
		{
			data = (!!data ? data : {});

			var
				text = (BX.type.isNotEmptyString(data.DETAIL_TEXT) ? data.DETAIL_TEXT : ''),
				fileData = {};

			if (
				BX.type.isNotEmptyObject(data.UF)
				&& BX.type.isNotEmptyObject(data.UF.UF_BLOG_POST_FILE)
			)
			{
				fileData["UF_BLOG_POST_FILE"] = {
					USER_TYPE_ID : "disk_file",
					FIELD_NAME : "UF_BLOG_POST_FILE[]",
					VALUE : BX.clone(data.UF.UF_BLOG_POST_FILE.VALUE)};
			}

			LHEPostForm.reinitData(this.options.profilePostData.lheId, text, fileData);
		},

		OnClickSubmit: function (ob)
		{
			var
				text = ob.oEditor.GetContent(),
				formId = ob.formID,
				form = (BX.type.isNotEmptyString(formId) ? BX(formId) : null),
				submitButton = (BX.type.isNotEmptyString(formId) ? BX('lhe_button_submit_' + formId) : null);

			if (form)
			{
				var data = BX.ajax.prepareForm(form);
				this.sendFormData({
					text: text,
					submitButton: submitButton,
					additionalData: data.data
				});
			}

		},

		sendFormData: function(params)
		{
			this.showButtonWait(params.submitButton, true);

			if (BX.type.isNotEmptyString(params.text))
			{
				BX.ajax.runComponentAction(this.managerInstance.componentName, 'sendProfileBlogPostForm', {
					mode: 'class',
					signedParameters: this.managerInstance.signedParameters,
					data: {
						params: {
							text: params.text,
							additionalData: params.additionalData
						}
					}
				}).then(function (response) {
					this.showButtonWait(params.submitButton, false);
					BX.onCustomEvent(this.LHEEventNode, 'OnShowLHE', ['hide']);
					BX.cleanNode(this.wrapperNode);
					this.getData();
					this.setContainerEditMode(false);
				}.bind(this), function (response) {
					this.setContainerEditMode(false);
					this.showButtonWait(params.submitButton, false);
				}.bind(this));
			}
			else
			{
				BX.ajax.runComponentAction(this.managerInstance.componentName, 'deleteProfileBlogPost', {
					mode: 'class',
					signedParameters: this.managerInstance.signedParameters,
					data: {
						params: {
						}
					}
				}).then(function (response) {
					this.showButtonWait(params.submitButton, false);
					BX.onCustomEvent(this.LHEEventNode, 'OnShowLHE', ['hide']);
					BX.cleanNode(this.wrapperNode);
					this.getData();
					this.setContainerEditMode(false);
				}.bind(this), function (response) {
					this.setContainerEditMode(false);
					this.showButtonWait(params.submitButton, false);
				}.bind(this));
			}
		},

		showButtonWait: function(buttonNode, value)
		{
			if (buttonNode)
			{
				if (!!value)
				{
					BX.addClass(buttonNode, 'ui-btn-clock');
				}
				else
				{
					BX.removeClass(buttonNode, 'ui-btn-clock');
				}
			}
		}

	};

})();