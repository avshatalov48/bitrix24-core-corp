;(function ()
{
	var namespace = BX.namespace('BX.Intranet.UserProfile');
	if (namespace.Tags)
	{
		return;
	}

	namespace.Tags = function(params) {
		this.init(params);

		return this;
	};

	namespace.Tags.prototype = {
		init: function(params)
		{
			this.tagsLoader = null;
			this.tagsLoaderNode = BX('intranet-user-profile-tags-loader');

			this.managerInstance = params.managerInstance;
			this.inputNode = params.inputNode;
			this.tagsNode = params.tagsNode;


			this.popup = null;
			this.popupContent = null;
//			this.tags = [];
			this.defferedTags = [];

			this.container = {
				input: null,
				inputWrapper: null,
			};
			this.popupContainer = null;
			this.initialized = null;

			this.tagsUsersPopupInstanceList = {};

			this.getTagsData();

			this.containerNode = BX('intranet-user-profile-tags-container');
			this.editLinkNode = BX('intranet-user-profile-add-tags');
			this.stubButtonNode = BX('intranet-user-profile-interests-stub-button');

			if (this.editLinkNode)
			{
				BX.bind(this.editLinkNode, 'click', this.show.bind(this));
			}

			if (this.stubButtonNode)
			{
				BX.bind(this.stubButtonNode, 'click', this.show.bind(this));
			}

			BX.bind(window, 'click', function(ev)
			{
				if(
					!BX.findParent(ev.target, {className: 'intranet-user-profile-tags-input-wrapper'})
					&& !BX.findParent(ev.target, {className: 'intranet-user-profile-tags-popup'})
					&& ev.target !== this.editLinkNode
					&& ev.target !== this.stubButtonNode
				)
				{
					if(this.defferedTags.length > 0)
					{
						for (var i = 0; i < this.defferedTags.length; i++)
						{
							this.addTag({tag: this.defferedTags[i]});
						}
						this.reindexUser();
						this.defferedTags = [];
					}

					this.cleanInput();
					BX.cleanNode(this.getInputWrapper());
					this.getInputWrapper().appendChild(this.getInput());
					this.hide();
					this.closePopup();
				}
			}.bind(this))
		},

		closePopup: function()
		{
			if (this.popup)
			{
				this.popup.close();
			}
		},

		getTagsData: function(params)
		{
			BX.addClass(this.tagsLoaderNode, 'intranet-user-profile-loader-getdata');
			this.tagsLoader = this.managerInstance.showLoader({
				node: this.tagsLoaderNode,
				loader: this.tagsLoader
			});

			BX.ajax.runComponentAction(this.managerInstance.componentName, 'getTagsList', {
				mode: 'class',
				signedParameters: this.managerInstance.signedParameters,
				data: {
					params: {}
				}
			}).then(function (response) {
				this.managerInstance.hideLoader({
					node: this.tagsLoaderNode,
					loader: this.tagsLoader
				});
				BX.removeClass(this.tagsLoaderNode, 'intranet-user-profile-loader-getdata');

				if (BX.type.isNotEmptyObject(response.data))
				{
					this.showTagsList(response.data);
				}
			}.bind(this), function (response) {
				this.managerInstance.hideLoader({
					node: this.tagsLoaderNode,
					loader: this.tagsLoader
				});
				BX.removeClass(this.tagsLoaderNode, 'intranet-user-profile-loader-getdata');
			}.bind(this));
		},

		showTagsList: function(tagsData, tagNode)
		{
			for (var tagName in tagsData)
			{
				if (!tagsData.hasOwnProperty(tagName))
				{
					continue;
				}

				var newTagNode = this.getTagNode({
					tag: tagName,
					tagData: tagsData[tagName],
					openListSlider: true
				});

				if (tagNode)
				{
					this.tagsNode.insertBefore(newTagNode, tagNode);
					BX.cleanNode(tagNode, true);
				}
				else
				{
					this.tagsNode.appendChild(newTagNode);
				}
			}
			this.checkEmptyTagsList();
		},

		getTagNode: function(params)
		{
			var itemNode = BX.create('DIV', {
				attrs: {
					'bx-data-user-tag': params.tag
				},
				props: {
					className: 'intranet-user-profile-tags-item intranet-user-profile-tags-item-new'
				},
				children: [
					BX.create('DIV', {
						props: {
							className: 'intranet-user-profile-tags-item-title',
							title: params.tag
						},
						text: params.tag,
						events: params.openListSlider ? {
							click: function() {
								BX.SidePanel.Instance.open(
									decodeURIComponent(this.managerInstance.urls.PathToTag).replace('#TAG#', params.tag),
									{
										cacheable: false,
										allowChangeHistory: true,
										width: 1000
									}
								);
							}.bind(this)
						} : null
					}),
					(
						this.managerInstance.isOwnProfile
							? BX.create('DIV', {
								props: {
									className: 'intranet-user-profile-tags-item-remove'
								},
								events: {
									click: function(e) {
										this.removeTag({
											tag: params.tag,
											node: itemNode,
											checksum: params.tagData.CHECKSUM,
										});
										this.reindexUser();
										e.stopPropagation();
										e.preventDefault();
									}.bind(this)
								}
							})
							: null
					),
					(
						params.tagData
							? this.getTagUsersNode({
								tag: params.tag,
								checksum: params.tagData.CHECKSUM,
								usersObject: params.tagData.USERS
							})
							: null
					),
					params.tagData ? this.getTagCounterNode(params.tagData.COUNT-params.tagData.USERS.length) : null,
					(
						params.tagData
							? BX.create('SPAN', {
								attrs: {
									id: 'iup-tags-item-users-popup-container-' + params.tagData.CHECKSUM,
									style: 'display: none;'
								},
								props: {
									className: 'iup-tags-users-popup-cont'
								},
								children: [
									BX.create('SPAN', {
										props: {
											className: 'iup-tags-item-users-popup-outer'
										},
										children: [
											BX.create('SPAN', {
												props: {
													className: 'iup-tags-item-users-popup'
												},
												children: [
													BX.create('SPAN', {
														props: {
															className: 'iup-tags-item-users-waiter'
														}
													})
												]
											}),
											BX.create('SPAN', {
												props: {
													className: 'iup-tags-item-users-action'
												},
												children: [
													BX.create('SPAN', {
														props: {
															className: 'ui-btn ui-btn-xs ui-btn-primary'
														},
														text: BX.message('INTRANET_USER_PROFILE_TAGS_POPUP_ADD')
													})
												]
											})
										]
									})
								]
							})
							: null
					)
				]
			});

			if (params.tagData)
			{
				this.tagsUsersPopupInstanceList[params.tagData.CHECKSUM] = new namespace.TagsUsersPopup({
					containerNodeId: 'iup-tags-item-users-popup-container-' + params.tagData.CHECKSUM,
					tagsInstance: this,
					tag: params.tag,
					tagNode: itemNode,
					checksum: params.tagData.CHECKSUM
				});
			}


			BX.bind(itemNode, 'animationend', function() {
				itemNode.classList.remove('intranet-user-profile-tags-item-new');
			});

			return itemNode;
		},

		removeTag: function(params)
		{
			var node = (BX.type.isNotEmptyObject(params) ? BX(params.node) : null);
			var tag = (BX.type.isNotEmptyObject(params) && BX.type.isNotEmptyString(params.tag) ? params.tag : null);
			var checksum = (BX.type.isNotEmptyObject(params) && BX.Type.isStringFilled(params.checksum) ? params.checksum : null);

			if (
				!node
				|| !BX.type.isNotEmptyString(tag)
			)
			{
				return;
			}

			this.removeTagNode(node);
			if (checksum)
			{
				var tagUsersPopup = this.tagsUsersPopupInstanceList[checksum].popup;
				if (tagUsersPopup)
				{
					tagUsersPopup.destroy();
				}
			}

			BX.ajax.runComponentAction(this.managerInstance.componentName, 'removeTag', {
				mode: 'class',
				signedParameters: this.managerInstance.signedParameters,
				data: {
					params: {
						tag: params.tag
					}
				}
			}).then(function (response) {
			}.bind(this), function (response) {
			}.bind(this));
		},

		removeTagNode: function(node)
		{
			node.style.opacity = '0';

			BX.bind(node, 'transitionend', function() {
				if (node.parentNode)
				{
					node.parentNode.removeChild(node);
				}
				this.checkEmptyTagsList();
			}.bind(this));
		},

		checkEmptyTagsList: function()
		{
			this.setContainerEmpty(!this.tagsNode.hasChildNodes());
		},

		getTagUsersNode: function(params)
		{
			var users = BX.create('DIV', {
				props: {
					className: 'intranet-user-profile-tags-users'
				},
				events: {
					mouseenter: function(e) {
						if (this.tagsUsersPopupInstanceList[params.checksum])
						{
							this.tagsUsersPopupInstanceList[params.checksum].onMouseOver({
								event: e
							});
						}
					}.bind(this),
					mouseleave: function(e) {
						if (this.tagsUsersPopupInstanceList[params.checksum])
						{
							this.tagsUsersPopupInstanceList[params.checksum].onMouseOut({
								event: e
							});
						}
					}.bind(this),
					click: function(e) {
						if (this.tagsUsersPopupInstanceList[params.checksum])
						{
							this.tagsUsersPopupInstanceList[params.checksum].onClick({
								event: e
							});
						}
					}.bind(this)
				}
			});

			for (var i = 0; i < params.usersObject.length; i++)
			{
				var user = params.usersObject[i];

				users.appendChild(BX.create('DIV', {
					props: {
						className: 'ui-icon ui-icon-common-user intranet-user-profile-tags-user'
					},
					children: [
						BX.create('i', {
							style: (
								BX.type.isNotEmptyString(user.PERSONAL_PHOTO_SRC)
									? {
										backgroundImage: "url(\'" + encodeURI(user.PERSONAL_PHOTO_SRC) + "\')"
									}
									: null
							)
						})
					]
				}));
			}

			return users
		},

		getTagCounterNode: function(value)
		{
			return (
				value > 0
					? BX.create('DIV', {
						props: {
							className: 'intranet-user-profile-tags-users-counter'
						},
						text: parseInt(value)
					})
					: null
			);
		},

		addTag: function(params)
		{
			BX.ajax.runComponentAction(this.managerInstance.componentName, 'addTag', {
				mode: 'class',
				signedParameters: this.managerInstance.signedParameters,
				data: {
					params: {
						tag: params.tag,
						userId: (typeof params.userId != 'undefined' ? parseInt(params.userId) : null)
					}
				}
			}).then(function (response) {
				if (BX.type.isNotEmptyObject(response.data))
				{
					this.showTagsList(response.data, (BX.type.isDomNode(params.tagNode) ? params.tagNode : null));
				}
			}.bind(this), function (response) {
			}.bind(this));
		},

		reindexUser: function(params)
		{
			if (!BX.type.isNotEmptyObject(params))
			{
				params = {};
			}

			setTimeout(function() {
				BX.ajax.runComponentAction(this.managerInstance.componentName, 'reindexUser', {
					mode: 'class',
					signedParameters: this.managerInstance.signedParameters,
					data: {
						params: {
							userId: (typeof params.userId != 'undefined' ? parseInt(params.userId) : null)
						}
					}
				}).then(function (response) {
				}.bind(this), function (response) {
				}.bind(this));
			}.bind(this), 1000);
		},

		getInputValue: function()
		{
			return this.getInput().value;
		},

		cleanInput: function()
		{
			this.getInput().value = '';
		},

		focusInput: function()
		{
			this.getInput().focus();
		},

		getInputWrapper: function()
		{
			if(!this.container.inputWrapper)
			{
				this.container.inputWrapper = BX.create('DIV', {
					props: {
						className: 'intranet-user-profile-tags-input-wrapper'
					}
				});

				this.container.inputWrapper.appendChild(this.getInput())
			}

			return this.container.inputWrapper
		},

		parseLongString: function()
		{
			var text = this.getInputValue();

			if (text === '')
			{
				return false;
			}

			var tagsList = text.split(/[\s\t]+/);

			if (
				BX.type.isArray(tagsList)
				&& tagsList.length > 1
			)
			{
				for (var i = 0; i < tagsList.length; i++)
				{
					if (!BX.type.isNotEmptyString(tagsList[i]))
					{
						continue;
					}
					this.addDeferredTag(tagsList[i], this.getTagNode({
						tag: tagsList[i]
					}));
				}

				return true;

			}

			return false;
		},

		checkDeferredTag: function(tag)
		{
			return BX.util.in_array(tag, this.defferedTags);
		},

		addDeferredTag: function(tag, tagNode)
		{
			if (!this.checkDeferredTag(tag))
			{
				this.defferedTags.push(tag);
				this.addDeferredTagNode(tagNode);
			}
		},

		addDeferredTagNode: function(tagNode)
		{
			this.cleanInput();
			this.getInputWrapper().insertBefore(BX.create('div', {
				props: {
					className: 'intranet-user-profile-tags-input-deferred'
				},
				children: [ tagNode ]
			}), this.getInputWrapper().children[this.getInputWrapper().children.length - 1]);
		},

		removeDeferredTag: function(tag)
		{
			if(this.defferedTags.indexOf(tag) !== -1)
			{
				this.defferedTags.splice(this.defferedTags.indexOf(tag), 1)
			}
		},

		removeDeferredTagNode: function(tagNode)
		{
			this.getInputWrapper().removeChild(tagNode);
		},

		getInput: function()
		{
			if(!this.container.input)
			{
				this.container.input = BX.create('INPUT', {
					props: {
						type: 'text',
						className: 'intranet-user-profile-tags-input'
					},
					events: {
						keydown: function(ev)
						{
							if(
								(
									ev.code
									&& (
										ev.code.toLowerCase() === 'enter'
										|| ev.code.toLowerCase() === 'numpadenter'
									)
								)
								|| (
									ev.keyCode
									&& ev.keyCode === 13
								)
							)
							{
								if(
									this.defferedTags.length === 0
									&& this.getInputValue() === ''
								)
								{
									return;
								}

								if (this.parseLongString())
								{
									return;
								}

								if(this.defferedTags.length > 0)
								{
									for (var i = 0; i < this.defferedTags.length; i++)
									{
										this.addTag({tag: this.defferedTags[i]});
									}
									this.reindexUser();
									this.defferedTags = [];
								}

								if(this.getInputValue() !== '')
								{
									this.addTag({tag: this.getInputValue()});
									this.reindexUser();
								}
								this.cleanInput();
								BX.cleanNode(this.getInputWrapper());
								this.getInputWrapper().appendChild(this.getInput());
								this.hide();
								this.closePopup();
							}
							else if(
								(
									ev.code
									&& ev.code.toLowerCase() === 'escape'
								)
								|| (
									ev.keyCode
									&& ev.keyCode === 27
								)
							)
							{
								this.hide();
								this.cleanInput();
								BX.cleanNode(this.getInputWrapper());
								this.getInputWrapper().appendChild(this.getInput());
								this.defferedTags = [];
								this.closePopup();
								ev.preventDefault();
								ev.stopPropagation();
							}
							else if(
								(
									ev.code
									&& (
										ev.code.toLowerCase() === 'arrowdown'
										|| ev.code.toLowerCase() === 'arrowup'
									)
								)
								|| (
									ev.keyCode
									&& (
										ev.keyCode === 38
										|| ev.keyCode === 40
									)
								)
							)
							{
//								this.adjustActiveTag(ev);
							}
							else if(
								(
									(
										ev.code
										&& ev.code.toLowerCase() === 'space'
									)
									|| (
										ev.keyCode
										&& ev.keyCode === 32
									)
								)
								&& this.getInputValue() !== ''
							)
							{
								if (!this.parseLongString())
								{
									this.addDeferredTag(this.getInputValue(), this.getTagNode(
										{tag: this.getInputValue()}
									));
								}

								this.cleanInput();
								BX.PreventDefault(ev);
							}
							else if(
								(
									(
										ev.code
										&& ev.code.toLowerCase() === 'backspace'
									)
									|| (
										ev.keyCode
										&& ev.keyCode === 8
									)
								)
								&& this.getInputValue() === ''
								&& this.defferedTags.length > 0
							)
							{
								var tagNode = this.getInputWrapper().children[this.getInputWrapper().children.length - 2];
								this.removeDeferredTag(this.defferedTags[this.defferedTags.length - 1]);
								this.removeDeferredTagNode(tagNode);
							}
						}.bind(this),

						input: BX.debounce(function()
						{
							if(this.getInputValue() !== '')
							{
								BX.ajax.runComponentAction(this.managerInstance.componentName, 'searchTags', {
									mode: 'class',
									signedParameters: this.managerInstance.signedParameters,
									data: {
										params: {
											searchString: this.getInputValue()
										}
									}
								}).then(function (response) {
									if (BX.type.isNotEmptyObject(response.data))
									{
										this.getPopup({
											tags: response.data,
											mode: 'search'
										}).show();
									}
									else
									{
//										this.tags = [];
										this.closePopup();
									}
								}.bind(this), function (response) {
//									this.tags = [];
									this.closePopup();
								}.bind(this));

								return;
							}

							this.getPopup().destroy();
							this.popup = null;
						}, 1000, this)
					}
				})
			}

			return this.container.input
		},

		hide: function()
		{
			this.getInputWrapper().style.height = this.getInputWrapper().offsetHeight + 'px';

			setTimeout(function() {
				this.getInputWrapper().classList.remove('intranet-user-profile-tags-input-show');
				this.getInputWrapper().classList.add('intranet-user-profile-tags-hide');
				this.getInputWrapper().style.height = null;
				this.setContainerEditMode(false);
			}.bind(this));

			this.initialized = false;
		},

		show: function()
		{
			if(!this.initialized)
			{
				this.inputNode.appendChild(this.getInputWrapper());
			}

			this.getInputWrapper().classList.remove('intranet-user-profile-tags-hide');

			setTimeout(function() {
				this.setContainerEditMode(true);
				this.getInputWrapper().classList.add('intranet-user-profile-tags-input-show');

				setTimeout(function() {
					this.getInputWrapper().style.height = 'auto';
				}.bind(this), 400);
			}.bind(this));

			this.focusInput();
			this.getPopularTags();

			this.initialized = true;
		},

		getPopup: function(params)
		{
			var input = this.getInput();
			if (!BX.type.isNotEmptyObject(params))
			{
				params = {};
			}

			if(!this.popup)
			{
				this.popup = new BX.PopupWindow({
					className: 'intranet-user-profile-tags-popup',
					bindElement: input,
					width: this.getInput().offsetWidth,
					content: this.getPopupContainer(params),
					animation: 'fading-slide',
					offsetTop: 5
				});
			}
			else
			{
				this.popup.setContent(this.getPopupContainer(params));
				this.popup.setBindElement(input);
			}

			this.popup.setBindElement(input);

			return this.popup
		},

		getPopupContainer: function(params)
		{
			this.popupContent = BX.create('div', {
				props: {
					className: 'intranet-user-profile-tags-popup'
				},
				children: [
					this.getPopupContentEmpty(params)
				]
			});

			return this.popupContent;
		},

		getPopularTags: function()
		{
			// get popular tags
			BX.ajax.runComponentAction(this.managerInstance.componentName, 'searchTags', {
				mode: 'class',
				signedParameters: this.managerInstance.signedParameters,
				data: {
					params: {}
				}
			}).then(function (response) {
				if (BX.type.isNotEmptyObject(response.data))
				{
					setTimeout(function() {
						this.getPopup({
							tags: response.data,
							mode: 'popular'
						}).show();
					}.bind(this), 200)
				}
			}.bind(this), function (response) {

			}.bind(this));
		},

		getPopupContentEmpty: function(params)
		{
			var mode = (BX.type.isNotEmptyString(params.mode) ? params.mode : 'popular');


			this.popupContent = BX.create('div', {
				props: {
					className: 'intranet-user-profile-tags-popup-empty-cont'
				}
			});

			this.popupContainer = BX.create('div', {
				props: {
					className: 'intranet-user-profile-tags-popup-empty'
				},
				children: [
					BX.create('div', {
						props: {
							className: 'intranet-user-profile-tags-popup-empty-title'
						},
						text: BX.message(mode == 'search' ? 'INTRANET_USER_PROFILE_TAGS_POPUP_SEARCH_TITLE' : 'INTRANET_USER_PROFILE_TAGS_POPUP_TITLE')
					}),
					this.popupContent,
					BX.create('div', {
						props: {
							className: 'intranet-user-profile-tags-popup-empty-text'
						},
						text: BX.message('INTRANET_USER_PROFILE_TAGS_POPUP_HINT_3')
// 							text: BX.message('INTRANET_USER_PROFILE_TAGS_POPUP_HINT_2')
					})
				]
			});

			var
				tagsList = (BX.type.isNotEmptyObject(params) && BX.type.isArray(params.tags) ? params.tags : []),
				popularTagNode = null,
				tagName = null;

			for (var i = 0; i < tagsList.length; i++)
			{
				popularTagNode = this.getTagNode({
					tag: tagsList[i].NAME,
					tagData: tagsList[i]
				});

				BX.bind(popularTagNode, 'click', function(ev)
				{
					tagName = ev.currentTarget.getAttribute('bx-data-user-tag');
					if (BX.type.isNotEmptyString(tagName))
					{
						this.addDeferredTag(tagName, this.getTagNode(
							{
								tag: tagName
							}
						));
					}
					this.focusInput();
				}.bind(this));

				this.popupContent.appendChild(popularTagNode);
			}

			return this.popupContainer;
		},

		getTagList: function()
		{
			var tagsObj = this.tags;

			var tagList = BX.create('DIV', {
				props: {
					className: 'intranet-user-profile-tags-popup-list'
				}
			});

			for (var i = 0; i < tagsObj.length; i++)
			{
				var tagNode = BX.create('DIV', {
					attrs: {
						'bx-data-user-tag': tagsObj[i].text
					},
					props: {
						className: 'intranet-user-profile-tags-popup-list-item'
					},
					text: tagsObj[i].text,
					events: {
						click: function(e) {
							this.addTag({tag: e.currentTarget.getAttribute('bx-data-user-tag')});
							this.reindexUser();
							this.cleanInput();
							this.closePopup();
						}.bind(this)
					}
				});

				tagList.appendChild(tagNode);
			}

			return tagList;
		},

		setActivePopularTag: function(node)
		{
			node.classList.add('intranet-user-profile-tags-item-active');
		},

		resetActivePopularTag: function(node)
		{
			node.classList.remove('intranet-user-profile-tags-item-active');
		},
/*
		setActiveTag: function(node)
		{
			node.setAttribute('data-role', 'intranet-user-profile-tags-popup-list-item-active');
			node.classList.add('intranet-user-profile-tags-popup-list-item-active');
		},

		resetActiveTag: function(node)
		{
			node.setAttribute('data-role', ' ');
			node.classList.remove('intranet-user-profile-tags-popup-list-item-active');
		},

		adjustActiveTag: function(ev)
		{
			var tagsWrapper = this.getPopupContainer().firstChild;
			var activeTag = tagsWrapper.querySelector('[data-role="intranet-user-profile-tags-popup-list-item-active"]');

			if(activeTag)
			{
				if(
					ev.code.toLowerCase() === 'arrowdown'
					&& activeTag.nextElementSibling
				)
				{
					this.setActiveTag(activeTag.nextElementSibling);
					this.setInputValue(activeTag.nextElementSibling.innerHTML);
					this.resetActiveTag(activeTag);
				}

				if(
					ev.code.toLowerCase() === 'arrowup'
					&& activeTag.previousElementSibling
				)
				{
					this.setActiveTag(activeTag.previousElementSibling);
					this.setInputValue(activeTag.previousElementSibling.innerHTML);
					this.resetActiveTag(activeTag);
				}

				ev.preventDefault();
				ev.stopPropagation();

				return;
			}

			if(ev.code.toLowerCase() === 'arrowdown')
			{
				this.setActiveTag(tagsWrapper.firstChild);
				this.setInputValue(tagsWrapper.firstChild.innerHTML);
			}

			if(ev.code.toLowerCase() === 'arrowup')
			{
				this.setActiveTag(tagsWrapper.lastChild);
				this.setInputValue(tagsWrapper.lastChild.innerHTML);
			}

			ev.preventDefault();
			ev.stopPropagation();
		},
*/
		setInputValue: function(value)
		{
			this.getInput().value = value;
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
	};


})();
