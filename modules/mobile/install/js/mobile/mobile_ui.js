;(function ()
{
	/**
	 * @bxjs_lang_path mobile_ui_messages.php
	 */

	if (window.BX.MobileUI)
		return;

	window.BX.MobileUI = {
		events:{
			MOBILE_UI_TEXT_FIELD_SET_PARAMS:"mobile.ui/textfield/setdefaultparams"
		},
		TextField: {
			__generateParams: function(params)
			{
				var userId = BX.message("USER_ID");
				var panelParams = {
					smileButton: {},
					useImageButton: true,
					mentionDataSource: {
						"outsection": false,
						"url": "/mobile/index.php?mobile_action=get_user_list&use_name_format=Y"
					},
					attachFileSettings: {
						"resize": {
							"quality": 40,
							"destinationType": 1,
							"sourceType": 1,
							"targetWidth": 1000,
							"targetHeight": 1000,
							"encodingType": 0,
							"mediaType": 2,
							"allowsEdit": true,
							"correctOrientation": "YES",
							"saveToPhotoAlbum": "NO",
							"popoverOptions": "NO",
							"cameraDirection": 0
						},
						// "showAttachedFiles": true,
						// "sendLocalFileMethod": "base64",
						"maxAttachedFilesCount": 10
					},
					attachButton: {
						items: [
							{
								"id": "disk",
								"name": BX.message("MUI_B24DISK"),
								"dataSource": {
									"multiple": "NO",
									"url": "/mobile/?mobile_action=disk_folder_list&type=user&path=/&entityId=" + userId,
									"TABLE_SETTINGS": {
										"searchField": true,
										"showtitle": true,
										"modal": "YES",
										"name": BX.message("MUI_CHOOSE_FILE_TITLE")
									}
								}
							},
							{
								"id": "mediateka",
								"name": BX.message("MUI_CHOOSE_PHOTO")
							},
							{
								"id": "camera",
								"name": BX.message("MUI_CAMERA_ROLL")
							}
						]
					}
				};

				if (params["useAudioMessages"] === true || params["useAudioMessages"] === false)
				{
					panelParams.useAudioMessages = params["useAudioMessages"];
				}

				if (params["text"])
				{
					panelParams.text = params["text"];
				}

				if (params["placeholder"])
				{
					panelParams.placeholder = params["placeholder"];
				}

				if (params["useSmiles"] && params["useSmiles"] != true)
				{
					panelParams.smileButton = "";
				}

				if (params["useMentions"] && params["useMentions"] != true)
				{
					panelParams.mentionDataSource = {};
				}

				if (params["onPlusPressed"] && typeof(params["onPlusPressed"]) == "function")
				{
					delete panelParams["attachButton"];
					panelParams["plusAction"] = params["onPlusPressed"];
				}
				else if (params["useFilePanel"] && params["useFilePanel"] != true)
				{
					delete panelParams["attachButton"];
				}
				else {
					if (params["attachOptions"])
					{
						panelParams["attachFileSettings"]["resize"] = params["attachOptions"];
					}

					if (params["attachUseBase64"] && params["attachUseBase64"] == true)
					{
						panelParams["attachFileSettings"]["sendLocalFileMethod"] = "base64";
					}

					if (params["attachSources"])
					{
						panelParams["attachFileSettings"]["items"] = params["attachSources"];
					}
				}

				if (typeof((params["onSend"]) == "function"))
				{
					if(!panelParams["attachFileSettings"]["sendLocalFileMethod"])
					{
						panelParams.action = function (data)
						{
							if(data.attachedFiles && data.attachedFiles.length > 0)
							{
								data.attachedFiles = data.attachedFiles.map(function(file){

									file["disk"] = (typeof file["dataAttributes"] != "undefined");
									if(!file["disk"])
									{
										file["base64"] = "fakebase64";// temporary fix
									}

									return file;
								})
							}

							params["onSend"](data);
						}
					}
					else
					{
						panelParams.action = params["onSend"];
					}
				}
				if (typeof((params["onEvent"]) == "function"))
				{
					panelParams.callback = params["onEvent"];
				}

				return panelParams;
			},
			setDefaultParams: function (params)
			{
				BX.MobileUI.TextField.defaultParams = BX.MobileUI.TextField.__generateParams(params);
				BX.onCustomEvent(BX.MobileUI.events.MOBILE_UI_TEXT_FIELD_SET_PARAMS, [BX.MobileUI.TextField.defaultParams]);

			},
			/**
			 * @param params
			 * @config {boolean} useFilePanel - show panel to manage attachments
			 * @config {boolean} useMentions - use mention function
			 * @config {boolean} useSmiles - show smile panel
			 * @config {object} attachSources - attach items
			 * @config {object} attachResizeOptions - attach settings
			 * @config {object} attachUseBase64 -
			 * @config {function} onSend - send-button handler
			 * @config {function} onEvent - event handler
			 * @config {function} onPlusPressed - event handler
			 */
			show: function (params)
			{
				var panelParams = {};
				if(typeof params != "undefined")
				{
					panelParams = BX.MobileUI.TextField.__generateParams(params);
				}
				else
				{
					if( typeof BX.MobileUI.TextField.defaultParams  != "undefined")
					{
						panelParams = BX.MobileUI.TextField.defaultParams;
					}
				}



				BXMPage.TextPanel.show(panelParams);
			}
		},
		createLoader: function ()
		{
			var loader = {
				show: function (text)
				{
					if (text)
					{
						this.setText(text);
					}

					if (!this.view.parentNode)
					{
						this.overlay.style.top = document.body.scrollTop + "px";
						this.view.style.top = document.body.scrollTop + screen.height / 2 - 100 + "px";

						document.body.appendChild(this.overlay);
						document.body.appendChild(this.view);
						document.body.addEventListener("touchmove", this._prevent)
					}

					return this;
				},
				hide: function ()
				{
					if (this.view.parentNode)
					{

						loader.view.parentNode.removeChild(this.overlay);
						loader.view.parentNode.removeChild(this.view);
						document.body.removeEventListener("touchmove", this._prevent)
					}
				},
				_prevent: function (e)
				{
					e.preventDefault();
				},
				onCancel: function ()
				{
					return true;
				},
				view: null,
				textView: null,
				setText: function (text)
				{
					this.textView.innerHTML = text;
				}
			};

			var _onCancel = function ()
			{

				if (loader.onCancel())
				{
					loader.hide()
				}

			};
			var loaderView = BX.create("TABLE", {
				props: {
					className: "mobile-iu-loader-wrap"
				},
				children: [
					BX.create("TR", {
						children: [
							BX.create("TD", {
								attrs: {
									height: "50px",
									style: "vertical-align:bottom;text-align: center"
								},
								children: [
									BX.create("DIV", {
										props: {
											className: "mobile-ui-loader"
										}
									})
								]
							})
						]
					}),
					BX.create("TR", {
						children: [
							BX.create("TD", {
								attrs: {
									height: "30px",
									style: "vertical-align:center;text-align: center"
								},
								children: [
									loader.textView = BX.create("DIV", {
										props: {
											className: "mobile-ui-loader-message"
										}
									})
								]
							})
						]
					}),
					BX.create("TR", {
						children: [
							BX.create("TD", {
								props: {},
								attrs: {
									height: "50px",
									style: "text-align: center"
								},
								children: [
									BX.create("BUTTON", {
										html: BX.message("MUI_CANCEL"),
										events: {
											"click": _onCancel
										}
									})
								]
							})
						]
					})

				]
			});

			loader.view = loaderView;
			loader.overlay = BX.create("DIV", {
				props: {
					className: "mobile-iu-loader-overlay"
				}
			});
			return loader;

		},
		bottomPanel: (function ()
		{

			var bindHover = function (node)
			{
				BX.bind(node, "touchstart", function ()
				{
					BX.addClass(node, "bottom_button_active");
				});
				BX.bind(node, "touchend", function ()
				{
					BX.removeClass(node, "bottom_button_active");
				})
			};

			var panel = {
				holderContainer: null,
				holder: BX.create("DIV", {
					props: {
						className: "bottom_button_holder"
					}
				}),
				node: BX.create("DIV", {
					props: {
						className: "bottom-panel"
					}
				}),
				buttons: [],
				/**
				 *
				 * @param buttonsArray
				 */
				setButtons: function (buttonsArray)
				{
					this.clear();

					for (var i = 0; i < buttonsArray.length; i++)
					{
						if (i > 0)
						{
							this.node.appendChild(BX.create("DIV", {
								props: {
									className: "bottom-panel-delimiter"
								}
							}));
						}

						this.buttons.push(BX.create("DIV", {
							html: buttonsArray[i].name,
							events: {
								"click": buttonsArray[i].callback
							}

						}));

						if (buttonsArray[i].className)
						{
							BX.addClass(this.buttons[this.buttons.length - 1], buttonsArray[i].className);
						}

						bindHover(this.buttons[this.buttons.length - 1]);
						FastClick.attach(this.buttons[this.buttons.length - 1]);
						BX.addClass(this.buttons[this.buttons.length - 1], "bottom_button");

						this.node.appendChild(this.buttons[this.buttons.length - 1]);
					}

					document.body.appendChild(this.node);
					var holderContainer = this.holderContainer != null ? this.holderContainer : document.body;
					if (!this.holder.parentNode)
					{
						holderContainer.appendChild(this.holder);

					}
				},

				clear: function ()
				{

					if (this.node && this.node.parentNode)
					{
						this.node.parentNode.removeChild(this.node);
						while (this.node.firstChild) {
							this.node.removeChild(this.node.firstChild);
						}
					}

					if (this.holder.parentNode)
					{
						this.holder.parentNode.removeChild(this.holder);
					}
				}
			};

			return panel;

		})(),
		addCopyableDialog: function (node, highlightBlockClass, textBlockClass, getTextFunction)
		{
			BX.MobileApp.Gesture.addLongTapListener(node, function (targetNode)
			{
				var highlightNode = BX.findParent(targetNode, { className: highlightBlockClass}, node);

				if (!highlightNode)
					return false;

				var textBlock;
				if (textBlockClass)
				{
					var copyableBlock = BX.findChild(highlightNode, {className: textBlockClass}, true);
					if (copyableBlock)
					{
						textBlock = copyableBlock;
					}

				}
				else {
					textBlock = highlightNode;
				}

				if (!textBlock)
				{
					return false;
				}

				BX.addClass(highlightNode, "long-tap-activate");

				(new BXMobileApp.UI.ActionSheet({
					buttons: [
						{
							title: BX.message("MUI_COPY"),
							callback: function ()
							{

								var text = null;

								if (typeof getTextFunction === "function")
								{
									text = getTextFunction(textBlock)
								}
								else {
									text = textBlock.innerHTML;
								}

								if (text !== null)
								{
									app.exec("copyToClipboard", {text: text});

									(new BXMobileApp.UI.NotificationBar({
										message: BX.message("MUI_TEXT_COPIED"),
										color: "#3a3735",
										textColor: "#ffffff",
										groupId: "clipboard",
										maxLines: 1,
										align: "center",
										isGlobal: true,
										useCloseButton: true,
										autoHideTimeout: 1000,
										hideOnTap: true
									}, "copy")).show();
								}

							}
						}
					]
				}, "copydialog")).show();

				app.exec("callVibration");

				setTimeout(function ()
				{
					BX.removeClass(highlightNode, "long-tap-activate");
				}, 1000);

			});
		},
		addLivefeedLongTapHandler: function(node, params)
		{
			this.currentLivefeedTouchEndHandler = function()
			{
				this.livefeedTouchEndHandler(node);
			}.bind(this);

			BX.MobileApp.Gesture.addLongTapListener(node, function (targetNode)
			{
				node.addEventListener('touchend', this.currentLivefeedTouchEndHandler);

				if (!this.showReactionsPopupByLogTap(targetNode, node, params))
				{
					this.showCopyDialogByLongTap(targetNode, node, params);
				}
			}.bind(this));
		},

		livefeedTouchEndHandler: function(node)
		{
			if (BX.Type.isDomNode(this.copyDialogHighlightNode))
			{
				BX.removeClass(this.copyDialogHighlightNode, "long-tap-activate");
			}

			node.removeEventListener('touchend', this.currentLivefeedTouchEndHandler);
		},

		showReactionsPopupByLogTap: function(targetNode, node, params)
		{
			var likeButtonNode = null;

			if (
				typeof BXRL != 'undefined'
				&& !BX.Type.isUndefined(BXRL.render)
				&& BX.Type.isStringFilled(params.likeNodeClass)
			)
			{
				if (BX.hasClass(targetNode, params.likeNodeClass))
				{
					likeButtonNode = targetNode;
				}
				else
				{
					likeButtonNode = BX.findParent(targetNode, { className: params.likeNodeClass}, node);
				}

				if (likeButtonNode)
				{
					var likeId = likeButtonNode.getAttribute('data-rating-vote-id');
					if (BX.type.isNotEmptyString(likeId))
					{
						app.exec("callVibration");

						BXRL.render.showReactionsPopup({
							bindElement: likeButtonNode,
							likeId: likeId
						});
					}
				}
			}

			return BX.Type.isDomNode(likeButtonNode);
		},

		showCopyDialogByLongTap: function (targetNode, node, params)
		{
			var menuItems = [];

			this.copyDialogHighlightNode = BX.findParent(targetNode, { className: params.copyItemClass }, node);

			if (this.copyDialogHighlightNode)
			{
				var textBlock = null;
				if (BX.type.isNotEmptyString(params.copyTextClass))
				{
					var copyableBlock = BX.findChild(this.copyDialogHighlightNode, { className: params.copyTextClass }, true);
					if (copyableBlock)
					{
						textBlock = copyableBlock;
					}
				}
				else
				{
					textBlock = this.copyDialogHighlightNode;
				}

				if (textBlock)
				{
					BX.addClass(this.copyDialogHighlightNode, "long-tap-activate");

					menuItems.push({
						title: BX.message("MUI_COPY_TEXT"),
						callback: function ()
						{
							var text = BX.MobileTools.getTextBlock(textBlock);
							if (text !== null)
							{
								app.exec("copyToClipboard", {text: text});

								(new BXMobileApp.UI.NotificationBar({
									message: BX.message("MUI_TEXT_COPIED"),
									color: "#3a3735",
									textColor: "#ffffff",
									groupId: "clipboard",
									maxLines: 1,
									align: "center",
									isGlobal: true,
									useCloseButton: true,
									autoHideTimeout: 1000,
									hideOnTap: true
								}, "copy")).show();
							}

						}
					});
				}
			}

			var menuItemsNode = null;
			if (BX.hasClass(targetNode, 'mobile-longtap-menu'))
			{
				menuItemsNode = targetNode;
			}
			else
			{
				menuItemsNode = BX.findParent(targetNode, { className: 'mobile-longtap-menu'}, node);
			}

			if (menuItemsNode)
			{
				var menuEventName = menuItemsNode.getAttribute('bx-longtap-menu-eventname');
				if (BX.type.isNotEmptyString(menuEventName))
				{
					BX.Event.EventEmitter.emit(menuEventName, {
						targetNode: targetNode,
						menuItems: menuItems
					});
				}
			}

			if (menuItems.length > 0)
			{
				(new BXMobileApp.UI.ActionSheet({
					buttons: menuItems
				}, "menudialog")).show();

				app.exec("callVibration");
			}
		},

		/**
		 * @type Window.BX.MobileUI.List {addListener:Function, show:Function}
		 */
		List: new (function()
		{
			var onEvent = function (data)
			{
				var list = null;

				if (data.objectId)
				{
					list = new (function ListObject(internalId, id)
					{
						this.internalId = internalId;
						this.id = id;
						this.showMenu = function (items, title)
						{
							window.BX.MobileUI.List.instance.exec("showMenu",
								{title: title, items: items, internalId: this.internalId});
						};
					})(data.objectId.internalId, data.objectId.id);
				}
				BX.onCustomEvent(
					"onMobileList" + (typeof data.objectId.id != "undefined" ? "_" + data.objectId.id : ""),
					[data.eventName, data.params, list])
			};

			this.Events = {
				ON_ITEM_MORE_CHOOSED: "onOverflowListChoose",
				ON_MENU_ITEM_CHOOSED: "onOverflowListMenuChoose"
			};
			this.show = function (data, id)
			{
				data.id = id;

				if(app.enableInVersion(20))
				{
					window.BX.MobileUI.List.instance.exec("show", data);
				}
				else
				{
					(new BXMobileApp.UI.Table(data)).show();
				}
			};

			this.addListener = function (listener, id)
			{
				BX.addCustomEvent("onMobileList" + (typeof id != "undefined" ? "_" + id : ""), listener);
			};

			this.instance = new BXCordovaPlugin("ListCordovaPlugin");
			this.instance.exec("init", {
				eventListener: BX.proxy(onEvent, this),
				jsCallbackProvider: "window.BX.MobileUI.List.instance"
			});

		})()
	};
})();

