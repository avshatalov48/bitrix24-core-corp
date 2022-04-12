(function()
{
	"use strict";

	var AJAX_URL = '/bitrix/components/bitrix/voximplant.ivr.edit/ajax.php';
	var ivrDefaults = {
		VOICE_LIST: null,
		SPEED_LIST: null,
		VOLUME_LIST: null,
		DEFAULT_VOICE: null,
		DEFAULT_SPEED: null,
		DEFAULT_VOLUME: null,
		IVR_LIST_URL: null,
		TELEPHONY_GROUPS: [],
		STRUCTURE: {},
		USERS: {},
		IS_ENABLED: false,
		MAX_DEPTH: 0,
		MAX_GROUPS: -1
	};

	var randomString = function(length)
	{
		var chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		var result = '';
		for (var i = length; i > 0; --i)
			result += chars[Math.floor(Math.random() * chars.length)];

		return result;
	};

	BX.IvrEditor = function(params)
	{
		this.node = params.node;
		this.isNew = params.isNew;
		this.ttsDisclaimer = params.ttsDisclaimer;
		if(this.isNew)
		{
			this.ivrData = {
				'ID': 0,
				'NAME': '',
				'ROOT_ITEM': this.createItem({
					LEVEL: 0,
					ACTIONS: [
						this.createAction({
							DIGIT: '0',
							ACTION: 'exit'
						}),
						this.createAction({
							DIGIT: '1'
						}),
						this.createAction({
							DIGIT: '2'
						}),
						this.createAction({
							DIGIT: '3'
						}),
						this.createAction({
							DIGIT: '4'
						}),
						this.createAction({
							DIGIT: '5'
						})
					]
				})
			}
		}
		else
		{
			this.ivrData = params.ivrData;
		}

		this.saving = false;

		this.keypadPopup = null;
		this.actionTypeMenu = null;

		this.currentGroupAction = null;

		this.elements = {
			saveButton: null
		};
	};

	BX.IvrEditor.setDefaults = function (params)
	{
		ivrDefaults.VOICE_LIST = params.VOICE_LIST;
		ivrDefaults.SPEED_LIST = params.SPEED_LIST;
		ivrDefaults.VOLUME_LIST = params.VOLUME_LIST;
		ivrDefaults.DEFAULT_VOICE = params.DEFAULT_VOICE;
		ivrDefaults.DEFAULT_SPEED = params.DEFAULT_SPEED;
		ivrDefaults.DEFAULT_VOLUME = params.DEFAULT_VOLUME;
		ivrDefaults.IVR_LIST_URL = params.IVR_LIST_URL;
		ivrDefaults.TELEPHONY_GROUPS = params.TELEPHONY_GROUPS || [];
		ivrDefaults.STRUCTURE = params.STRUCTURE || {};
		ivrDefaults.USERS = params.USERS || {};
		ivrDefaults.IS_ENABLED = params.IS_ENABLED == true;
		ivrDefaults.MAX_DEPTH = params.MAX_DEPTH || 0;
		ivrDefaults.MAX_GROUPS = BX.prop.getInteger(params, "MAX_GROUPS", -1);
	};

	BX.IvrEditor.prototype.init = function()
	{
		var self = this;

		if(!ivrDefaults.IS_ENABLED)
		{
			this.showUnavailablePopup();
		}

		BX.addCustomEvent(window, 'SidePanel.Slider:onClose', this._onSliderClosed.bind(this));
		BX.addCustomEvent(window, 'SidePanel.Slider:onMessage', this._onSliderMessageReceived.bind(this));

		this.render();
	};

	BX.IvrEditor.prototype.getNode = function(name, scope)
	{
		if (!scope)
			scope = this.getMainNode();

		return scope ? scope.querySelector('[data-role="'+name+'"]') : null;
	};

	BX.IvrEditor.prototype.getMainNode = function()
	{
		return this.node;
	};

	BX.IvrEditor.prototype.getTemplate = function(name, scope)
	{
		if (!scope)
			scope = this.getMainNode();

		var node = scope.querySelector('script[data-template="'+name+'"]');
		return node ? node.innerHTML : '';
	};

	BX.IvrEditor.prototype.render = function()
	{
		var self = this;
		BX.cleanNode(this.node);
		var ivrNode = BX.create("div", {props: {className: 'ivr-wrap'}, children: [
			//BX.create("h3", {props: {className: 'ivr-section-title'}, text: BX.message('VOX_IVR_EDIT_MENU_SETUP')}),
			BX.create("div", {props: {className: 'ivr-root-lvl'}, children: [
				BX.create("div", {props: {className: 'ivr-block'}, children: [
					// BX.create("h4", {props: {className: 'ivr-block-title'}, text: BX.message('VOX_IVR_EDIT_MENU_TITLE')}),
					BX.create("div", {props: {className: 'ivr-block-row ivr-title-editable'}, children: [
						BX.create("input", {attrs: {className: 'ivr-input-text', value: this.ivrData.NAME, placeholder: BX.message('VOX_IVR_EDIT_MENU_TITLE')}, events: {
							bxchange: function(e)
							{
								self.ivrData.NAME = e.target.value;
							}
						}})
					]})
				]}),
				this.renderItem(this.ivrData.ROOT_ITEM)
			]}),
			BX.create("div", {props: {className: 'tel-set-footer-btn-fixed'}, children: [
				this.elements.saveButton = !ivrDefaults.IS_ENABLED ? null : BX.create("button", {
					props: {className: "ui-btn ui-btn-success"},
					text: BX.message('VOX_IVR_EDIT_SAVE'),
					events: {
						click: this._onSaveButtonClick.bind(this)
					}
				}),
				BX.create("span", {
					props: {className: 'ui-btn ui-btn-link'},
					text: BX.message('VOX_IVR_EDIT_CANCEL'),
					events: {
						click: this._onCancelButtonClick.bind(this)
					}
				})
			]})
		]});
		this.node.appendChild(ivrNode);
	};

	BX.IvrEditor.prototype.hide = function(nodes)
	{
		if(BX.type.isArray(nodes))
		{
			nodes.forEach(function(node)
			{
				node.style.display = 'none';
			})
		}
		else if(BX.type.isDomNode(nodes))
		{
			nodes.style.display = 'none';
		}
	};

	BX.IvrEditor.prototype.show = function (nodes)
	{
		if(BX.type.isArray(nodes))
		{
			nodes.forEach(function(node)
			{
				node.style.removeProperty('display');
			})
		}
		else if(BX.type.isDomNode(nodes))
		{
			nodes.style.removeProperty('display');
		}
	};

	BX.IvrEditor.prototype.renderItem = function (itemDescriptor)
	{
		var self = this;
		var subElements = {
			fileUploader: null,
			fileDescription: null,
			textInputContainer: null,
			additionalTtsBlock: null,
			ivrExitHint: null,
			textInput: null,
			ttsDisclaimer: null
		};

		var result = BX.create("div", {children: [
			BX.create("div", {props: {className: 'ivr-block'}, children: [
				BX.create("h4", {props: {className: 'ivr-block-title'}, text: BX.message('VOX_IVR_EDIT_MENU_SOURCE')}),
				BX.create("div", {props: {className: 'ivr-block-row'}, children: [
					BX.create("select", {
						props: {className: 'ivr-input-select'},
						children: [
							BX.create("option", {attrs: {value: 'file', selected: (itemDescriptor.TYPE == 'file')}, text: BX.message('VOX_IVR_EDIT_MENU_SOURCE_FILE')}),
							BX.create("option", {attrs: {value: 'message', selected: (itemDescriptor.TYPE == 'message')}, text: BX.message('VOX_IVR_EDIT_MENU_SOURCE_MESSAGE')})
						],
						events: {
							change: function(e)
							{
								itemDescriptor.TYPE = e.target.value;
								switch (itemDescriptor.TYPE)
								{
									case 'file':
										self.hide([subElements.textInputContainer, subElements.ttsDisclaimer]);
										self.show([subElements.fileUploader, subElements.fileDescription]);
										if(itemDescriptor._uploader)
										{
											itemDescriptor._uploader.resize();
										}
										break;
									case 'message':
										self.show([subElements.textInputContainer, subElements.ttsDisclaimer]);
										self.hide([subElements.fileUploader, subElements.fileDescription]);
										break;
								}
							}
					}}),
					subElements.fileUploader = BX.create("div", {props: {className: 'ivr-uploader-container'}, style: (itemDescriptor.TYPE == 'message' ? {display: 'none'} : {})})
				]}),
				subElements.ttsDisclaimer = BX.create("div", {
					props: {className: 'ivr-block-row'},
					style: (itemDescriptor.TYPE == 'file') ? {display: 'none'} : {},
					children: [
					BX.create("div", {
						props: {className: 'ui-alert ui-alert-warning ui-alert-icon-danger ',},
						children: [
							BX.create("span", {
								props: {className: 'ui-alert-message'},
								html: this.ttsDisclaimer
							}),
							BX.create("span", {
								props: {className: 'ui-alert-close-btn'},
								events: {
									click: () => this.hide(subElements.ttsDisclaimer)
								}
							}),

						]
					}),
				]}),
				subElements.fileDescription = BX.create("div", {
					props: {className: 'ivr-block-row'},
					style: (itemDescriptor.TYPE == 'message' ? {display: 'none'} : {}),
					children: [
						BX.create("div", {props: {className: 'ivr-block-description'}, text: BX.message('VOX_IVR_EDIT_MENU_DESCRIPTION_FILE')})
					]
				})
			]}),
			subElements.textInputContainer = BX.create("div", {
				props: {className: 'ivr-block'},
				style: (itemDescriptor.TYPE == 'file' ? {display: 'none'} : {}),
				children: [
					BX.create("h4", {props: {className: 'ivr-block-title'}, text: BX.message('VOX_IVR_EDIT_MENU_TEXT_TO_PRONOUNCE')}),
					BX.create("div", {props: {className: 'ivr-block-row'}, children: [
						BX.create("div", {props: {className: 'ivr-block-description'}, text: BX.message('VOX_IVR_EDIT_MENU_DESCRIPTION_TEXT')}),
						subElements.textInput = BX.create("textarea", {props: {className: 'ivr-input-textarea', value: itemDescriptor.MESSAGE}, events: {
							bxchange: BX.debounce(function(e)
							{
								itemDescriptor.MESSAGE = e.target.value;
								this._adjustTextAreaSize(e.target);
							}, 100, this)
						}}),
						BX.create("div", {children: [
							BX.create("span", {props: {className: 'ivr-input-text-additional'}, text: BX.message('VOX_IVR_EDIT_MENU_CHANGE_TTS_SETTINGS'), events: {
								click: function()
								{
									BX.toggleClass(subElements.additionalTtsBlock, 'ivr-input-text-additional-block-folded');
								}
							}})
						]})
					]}),
					subElements.additionalTtsBlock = BX.create("div", {props: {className: 'ivr-input-text-additional-block ivr-input-text-additional-block-folded'}, children: [
						BX.create("h4", {props: {className: 'ivr-block-title'}, text: BX.message('VOX_IVR_EDIT_MENU_CHANGE_TTS_VOICE')}),
						BX.create("select", {
							props: {className: 'ivr-input-select'},
							children: this._createOptions(ivrDefaults.VOICE_LIST, itemDescriptor.TTS_VOICE),
							events: {
								bxchange: function(e)
								{
									itemDescriptor.TTS_VOICE = e.target.value;
								}
							}
						}),
						BX.create("h4", {props: {className: 'ivr-block-title'}, text: BX.message('VOX_IVR_EDIT_MENU_CHANGE_TTS_SPEED')}),
						BX.create("select", {
							props: {className: 'ivr-input-select'},
							children: this._createOptions(ivrDefaults.SPEED_LIST, itemDescriptor.TTS_SPEED),
							events: {
								bxchange: function(e)
								{
									itemDescriptor.TTS_SPEED = e.target.value;
								}
							}
						}),
						BX.create("h4", {props: {className: 'ivr-block-title'}, text: BX.message('VOX_IVR_EDIT_MENU_CHANGE_TTS_VOLUME')}),
						BX.create("select", {
							props: {className: 'ivr-input-select'},
							children: this._createOptions(ivrDefaults.VOLUME_LIST, itemDescriptor.TTS_VOLUME),
							events: {
								bxchange: function(e)
								{
									itemDescriptor.TTS_VOLUME = e.target.value;
								}
							}
						})
					]})
				]
			}),
			BX.create("div", {props: {className: 'ivr-block'}, children: [
				BX.create("h4", {props: {className: 'ivr-block-title'}, text: BX.message('VOX_IVR_EDIT_MENU_TIMEOUT_ACTION')}),
				BX.create("div", {props: {
					id: 'ivrHint',
					className: 'ivr-block-row'
					},
					children: [
					BX.create("select", {
						props: {className: 'ivr-input-select'},
						children: [
							BX.create("option", {attrs: {value: 'exit', selected: (itemDescriptor.TIMEOUT_ACTION == 'exit')}, text: BX.message('VOX_IVR_EDIT_MENU_ACTION_EXIT')}),
							BX.create("option", {attrs: {value: 'repeat', selected: (itemDescriptor.TIMEOUT_ACTION == 'repeat')}, text: BX.message('VOX_IVR_EDIT_MENU_ACTION_REPEAT')})
						],
						events: {
							change: function(e)
							{
								itemDescriptor.TIMEOUT_ACTION = e.target.value;
								if(itemDescriptor.TIMEOUT_ACTION == 'exit')
									subElements.ivrExitHint.style.maxHeight = '20px';
								else
									subElements.ivrExitHint.style.maxHeight = '0';
							}
						}
					}),
					BX.create("span", {style: {padding: '0 5px'}, text: BX.message('VOX_IVR_EDIT_MENU_TIMEOUT') + ':'}),
					BX.create("input", {
						attrs: {type: 'number'},
						props: {className: 'ivr-input-number', value: itemDescriptor.TIMEOUT},
						style: {width: '3em'},
						events: {
							change: function(e)
							{
								itemDescriptor.TIMEOUT = e.target.value;
							}
						}
					}),
					// BX.create('div', {
					// 	props: {
					// 		className: 'ivr-hint-box'
					// 	},
					// 	children: [
                     //        BX.UI.Hint.createNode(BX.message('VOX_IVR_EXIT_HINT'))
					// 	]
					// })
				]}),
				subElements.ivrExitHint = BX.create("div", {
					props: {className: 'ivr-block-description ivr-block-description-animated'},
					text: BX.message('VOX_IVR_EXIT_HINT'),
					style: {maxHeight: (itemDescriptor.TIMEOUT_ACTION == 'exit' ? '20px' : '0')}
				})
			]}),
			BX.create("div", {props: {className: 'ivr-block'}, children: [
				BX.create("h4", {props: {className: 'ivr-block-title'}, text: BX.message('VOX_IVR_EDIT_MENU_ACTIONS')}),
				BX.create("div", {props: {className: 'ivr-actions-container'}, children: [
					itemDescriptor._actionsContainer = BX.create("div", {props: {className: 'ivr-logic-tree'}, children: this.renderActions(itemDescriptor.ACTIONS, itemDescriptor)})
				]}),
				BX.create("div", {props: {className: 'ivr-logic-tree-section ivr-logic-tree-section-new'}, children: [
					BX.create("div", {props: {className: 'ivr-logic-tree-section-new-btn'}, events: {
						click: function()
						{
							self.selectDigit({
								node: this,
								occupiedDigits: BX.IvrEditor._getOccupiedDigits(itemDescriptor),
								onSelect: function(e)
								{
									var newAction = self.createAction({DIGIT: e.digit});
									var actionNode = self.renderAction(newAction, itemDescriptor);
									newAction._node = actionNode;
									itemDescriptor.ACTIONS.push(newAction);
									itemDescriptor._actionsContainer.appendChild(actionNode);
								}
							});
						}
					}})
				]})
			]})
		]});
		itemDescriptor._uploader = Uploader.create({
			node: subElements.fileUploader,
			fileId: itemDescriptor.FILE_ID,
			fileUrl: itemDescriptor.FILE_SRC,
			onSelect: function(e)
			{
				itemDescriptor.FILE_ID = e.FILE_ID;
			},
			onDelete: function(e)
			{
				itemDescriptor.FILE_ID = '';
			}
		});
		setTimeout(function()
		{
			subElements.textInput.style.height = subElements.textInput.scrollHeight + 'px';
		}, 50);
		itemDescriptor._node = result;
		return result;
	};

	BX.IvrEditor.prototype.renderActions = function(actions, itemDescriptor)
	{
		if(!BX.type.isArray(actions))
			return [];

		var self = this;
		var result = [];

		actions.map(function (action)
		{
			var actionNode = self.renderAction(action, itemDescriptor);
			result.push(actionNode);
			action._node = actionNode;
		});

		result.push();
		return result;
	};

	BX.IvrEditor.prototype.renderAction = function (action, itemDescriptor)
	{
		var self = this;
		var digitNode;
		var actionContentNode;
		var userSelectorNode;
		var voiceMailUserSelectorNode;
		var subSectionContentNode;
		var subSectionMeasuringNode;
		var subItemNode;
		var resultNode;
		var groupSelectNode;

		var animationTimeout;

		if(action.ACTION == '')
		{
			actionContentNode = null
		}
		else if(action.ACTION == 'item')
		{
			actionContentNode = BX.create("div", {props: {className: 'ivr-logic-tree-section-content'}, children: [
				BX.create("div", {props: {className: 'ivr-block'}, children: [
					BX.create("div", {props: {className: 'ivr-block-row'}, children: [
						BX.create("div", {props: {className: 'ivr-logic-tree-section-title'}, children: [
							BX.create("span", {props: {className: 'ivr-logic-tree-section-title-text'}, text: BX.IvrEditor.message('VOX_IVR_EDIT_ENCLOSED_MENU', {'#DIGIT#': action.DIGIT})}),
							BX.create("span", {props: {className: 'ivr-logic-tree-section-collapse-btn'}, text: BX.message('VOX_IVR_EDIT_FOLD'), events: {
								click: function(e)
								{
									clearTimeout(animationTimeout);
									if(subSectionContentNode.dataset.collapsed == 1)
									{
										subSectionContentNode.style.height = 0;
										animationTimeout = setTimeout(function()
										{
											subSectionContentNode.style.height = subSectionMeasuringNode.clientHeight.toString() + 'px';
											subSectionContentNode.dataset.collapsed = 0;
											e.target.innerText = BX.message('VOX_IVR_EDIT_FOLD');
										}, 100);
									}
									else
									{
										subSectionContentNode.style.height = subSectionMeasuringNode.clientHeight.toString() + 'px';
										animationTimeout  = setTimeout(function()
										{
											subSectionContentNode.style.height = 0;
											subSectionContentNode.dataset.collapsed = 1;
											e.target.innerText = BX.message('VOX_IVR_EDIT_UNFOLD');

										}, 100);
									}
								}
							}})
						]})
					]})
				]}),
				subSectionContentNode = BX.create("div", {
					props: {className: 'ivr-logic-tree-subsection-content'},
					dataset: {collapsed: 0},
					children: [
						subSectionMeasuringNode = BX.create("div", {props: {className: 'ivr-logic-tree-subsection-measuring-wrapper'}, children: [
							subItemNode = this.renderItem(action.ITEM)
						]})
					],
					events: {
						'transitionend': function()
						{
							if(subSectionContentNode.dataset.collapsed == 0)
							{
								subSectionContentNode.style.removeProperty('height');
							}
						}
					}
				})
			]});
		}
		else
		{
			actionContentNode = BX.create("div", {props: {className: 'ivr-logic-tree-section-content'}, children: [
				BX.create("div", {props: {className: 'ivr-block'}, children: [
					BX.create("div", {props: {className: 'ivr-block-row'}, children: [
						BX.create("select",{
							props: {className: 'ivr-input-select'},
							children: [
								BX.create("option", {props: {value: 'user', selected: action.ACTION == 'user'}, text: BX.message('VOX_IVR_EDIT_MENU_ACTION_USER')}),
								BX.create("option", {props: {value: 'queue', selected: action.ACTION == 'queue'}, text: BX.message('VOX_IVR_EDIT_MENU_ACTION_QUEUE')}),
								BX.create("option", {props: {value: 'phone', selected: action.ACTION == 'phone'}, text: BX.message('VOX_IVR_EDIT_MENU_ACTION_PHONE')}),
								BX.create("option", {props: {value: 'directCode', selected: action.ACTION == 'directCode'}, text: BX.message('VOX_IVR_EDIT_MENU_ACTION_DIRECTCODE')}),
								BX.create("option", {props: {value: 'voicemail', selected: action.ACTION == 'voicemail'}, text: BX.message('VOX_IVR_EDIT_MENU_ACTION_VOICEMAIL')}),
								BX.create("option", {props: {value: 'repeat', selected: action.ACTION == 'repeat'}, text: BX.message('VOX_IVR_EDIT_MENU_ACTION_REPEAT')}),
								/*BX.create("option", {props: {value: 'message', selected: action.ACTION == 'message'}, text: BX.message('VOX_IVR_EDIT_MENU_ACTION_MESSAGE')}),*/
								(itemDescriptor.LEVEL > 0 ?BX.create("option", {props: {value: 'return', selected: action.ACTION == 'return'}, text: BX.message('VOX_IVR_EDIT_MENU_ACTION_RETURN')}) : null),
								BX.create("option", {props: {value: 'exit', selected: action.ACTION == 'exit'}, text: BX.message('VOX_IVR_EDIT_MENU_ACTION_EXIT')})
							],
							events: {
								change: function(e)
								{
									self._onActionChangeType(action, itemDescriptor, e.target.value);
								}
							}
						})
					]}),
					BX.create("div", {
						props: {className: 'ivr-block-description'},
						text: BX.message('VOX_IVR_EXIT_HINT'),
						style: {display: (action.ACTION === 'exit' ? 'block': 'none')}
					})
				]}),
				BX.create("div", {
					style: {display: (action.ACTION === 'repeat' ? 'block': 'none')},
					dataset: {role: 'action-parameters', actionType: 'repeat'}
				}),
				BX.create("div", {
					props: {className: 'ivr-block'},
					style: {display: (action.ACTION === 'queue' ? 'block': 'none')},
					dataset: {role: 'action-parameters', actionType: 'queue'},
					children: [
						BX.create("div", {props: {className: 'ivr-block-row'}, children: [
							groupSelectNode = BX.create("select", {
								props: {className: 'ivr-input-select'},
								events: {
									bxchange: function (e)
									{
										if(e.target.value == 'new')
										{
											var groupCount = e.target.options.length - 2;
											if(ivrDefaults.MAX_GROUPS > -1 && groupCount >= ivrDefaults.MAX_GROUPS)
											{
												e.target.value = e.target.options.item(2).value;
												if (ivrDefaults.MAX_GROUPS == 0)
												{
													BX.UI.InfoHelper.show('limit_contact_center_telephony_groups_zero');
												}
												else
												{
													BX.UI.InfoHelper.show('limit_contact_center_telephony_groups');
												}
											}
											else
											{
												self.showGroupSettings(action, 0);
											}
										}
										else
										{
											action.PARAMETERS.QUEUE_ID = e.target.value;
										}
									}
								},
								children: BX.IvrEditor._groupsToOptions(ivrDefaults.TELEPHONY_GROUPS, action.PARAMETERS.QUEUE_ID)
							}),
							BX.create("span", {
								props: {className: 'ivr-group-show-config'},
								text: BX.message('VOX_IVR_GROUP_SETTINGS'),
								dataset: {settingsOpen: "N"},
								events: {
									click: function(e)
									{
										self.showGroupSettings(action, groupSelectNode.value);
									}
								}
							})
						]})
					]
				}),
				BX.create("div", {
					props: {className: 'ivr-block'},
					style: {display: (action.ACTION === 'user' ? 'block': 'none')},
					dataset: {role: 'action-parameters', actionType: 'user'},
					children: [
						BX.create("div", {props: {className: 'ivr-block-row'}, children: [
							userSelectorNode = BX.create("div")
						]})
					]
				}),
				BX.create("div", {
					props: {className: 'ivr-block'},
					style: {display: (action.ACTION === 'phone' ? 'block': 'none')},
					dataset: {role: 'action-parameters', actionType: 'phone'},
					children: [
						BX.create("div", {props: {className: 'ivr-block-row'}, children: [
							BX.create("input", {
								props: {className: 'ivr-input-select', value: action.PARAMETERS.PHONE_NUMBER},
								events: {
									bxchange: function(e)
									{
										action.PARAMETERS.PHONE_NUMBER = e.target.value;
									}
								}
							})
						]})
					]
				}),
				BX.create("div", {
					style: {display: (action.ACTION === 'directCode' ? 'block': 'none')},
					dataset: {role: 'action-parameters', actionType: 'directCode'}
				}),
				BX.create("div", {
					props: {className: 'ivr-block'},
					style: {display: (action.ACTION === 'voicemail' ? 'block': 'none')},
					dataset: {role: 'action-parameters', actionType: 'voicemail'},
					children: [
						BX.create("h4", {props: {className: 'ivr-block-title'}, text: BX.message('VOX_IVR_EDIT_VOICEMAIL_RECEIVER')}),
						BX.create("div", {props: {className: 'ivr-block-row'}, children: [
							voiceMailUserSelectorNode = BX.create("div")
						]})
					]
				})
			]})
		}

		if(action.ACTION == 'user')
		{
			UserSelector.create({
				node: userSelectorNode,
				userId: action.PARAMETERS.USER_ID,
				onSelect: function(e)
				{
					action.PARAMETERS.USER_ID = e.userId;
				},
				onDelete: function ()
				{
					action.PARAMETERS.USER_ID = '';
				}
			})
		}
		else if(action.ACTION == 'voicemail')
		{
			UserSelector.create({
				node: voiceMailUserSelectorNode,
				userId: action.PARAMETERS.USER_ID,
				onSelect: function(e)
				{
					action.PARAMETERS.USER_ID = e.userId;
				},
				onDelete: function ()
				{
					action.PARAMETERS.USER_ID = '';
				}
			})
		}

		resultNode = BX.create("div", {props: {className: action.ACTION == '' ? 'ivr-logic-tree-section ivr-logic-tree-section-empty' : 'ivr-logic-tree-section'}, children: [
			BX.create("div", {props: {className: 'ivr-logic-tree-section-number-container'}, children: [
				BX.create("div", {props: {className: 'ivr-logic-tree-section-number'}, children: [
					digitNode = BX.create("span", {props: {className: 'ivr-logic-tree-section-number-value'}, text: action.DIGIT}),
					BX.create("span", {
						props: {className: 'ivr-logic-tree-section-number-value-change-btn'},
						events: {
							click: function()
							{
								self.selectDigit({
									node: this,
									occupiedDigits: BX.IvrEditor._getOccupiedDigits(itemDescriptor),
									onSelect: function(e)
									{
										digitNode.innerText = e.digit;
										action.DIGIT = e.digit;
									}
								})
							}
						}
					}),
					BX.create("div", {
						props: {className: 'ivr-logic-tree-section-remove-btn'},
						events: {
							click: function (e)
							{
								self._onActionRemoveClick(action, itemDescriptor);
							}
						}
					})
				]}),
				BX.create("div", {
					props: {className: 'ivr-logic-tree-section-number-action'},
					events: {
						click: function(e)
						{
							self._onActionSelectTypeClick(action, itemDescriptor, e.target);
						}
					}
				})
			]}),
			BX.create("div", {props: {className: 'ivr-logic-tree-section-line'}}),
			actionContentNode
		]});
		return resultNode;
	};

	BX.IvrEditor.prototype.selectDigit = function(params)
	{
		var self = this;
		if(this.keypadPopup)
			this.keypadPopup.close();

		this.keypadPopup = KeyPad.create({
			node: params.node,
			occupiedDigits: params.occupiedDigits,
			onSelect: function(e){
				params.onSelect(e);
				self.keypadPopup.close();
			},
			onClose: function()
			{
				self.keypadPopup.dispose();
				self.keypadPopup = null;
			}
		});

		this.keypadPopup.show();
	};

	BX.IvrEditor.prototype._onActionChangeType = function(action, item, newType)
	{
		var self = this;
		action.ACTION = newType;
		action.LEAD_FIELDS = null;
		action.PARAMETERS = {};
		action.ITEM_ID = '';

		switch (newType)
		{
			case 'phone':
				action.PARAMETERS.PHONE_NUMBER = '';
				break;
			case 'queue':
				action.PARAMETERS.QUEUE_ID = ivrDefaults.TELEPHONY_GROUPS[0].ID;
				break;
			case 'user':
				action.PARAMETERS.USER_ID = '';
				break;
		}

		var newNode = this.renderAction(action, item);
		action._node.parentNode.replaceChild(newNode, action._node);
		action._node = newNode;
	};

	BX.IvrEditor.prototype._onActionSelectTypeClick = function (action, item, node)
	{
		var self = this;
		if(action.ACTION == '')
		{
			this.selectActionType({
				node: node,
				item: item,
				onSelect: function(e)
				{
					var newNode;
					if(e.type === 'item')
					{
						var nextLevel = parseInt(item.LEVEL) + 1;
						if(ivrDefaults.MAX_DEPTH == 0 || nextLevel < ivrDefaults.MAX_DEPTH)
						{
							action.ITEM = self.createItem({
								LEVEL: nextLevel,
								ACTIONS: [
									self.createAction({
										DIGIT: '0',
										ACTION: 'exit'
									})
								]
							});

							action.ACTION = e.type;
							newNode = self.renderAction(action, item);
							action._node.parentNode.replaceChild(newNode, action._node);
							action._node = newNode;
						}
						else
						{
							self.showLimitReachedPopup();
						}
					}
					else
					{
						switch (e.type)
						{
							case 'phone':
								action.PARAMETERS.PHONE_NUMBER = '';
								break;
							case 'queue':
								action.PARAMETERS.QUEUE_ID = ivrDefaults.TELEPHONY_GROUPS[0].ID;
								break;
							case 'user':
								action.PARAMETERS.USER_ID = '';
								break;
						}

						action.ACTION = e.type;
						newNode = self.renderAction(action, item);
						action._node.parentNode.replaceChild(newNode, action._node);
						action._node = newNode;
					}
				}
			})
		}
		else
		{
			action.ACTION = '';
			action.ITEM_ID = '';
			action.ITEM = null;
			action.LEAD_FIELDS = null;
			action.PARAMETERS = {};

			var newNode = self.renderAction(action, item);
			action._node.parentNode.replaceChild(newNode, action._node);
			action._node = newNode;
		}
	};

	BX.IvrEditor.prototype._onActionRemoveClick = function(actionToDelete, item)
	{
		var self = this;
		var indexToDelete = null;
		item.ACTIONS.forEach(function(action, index)
		{
			if (action == actionToDelete)
			{
				indexToDelete = index;
			}
		});
		if(indexToDelete === null)
			return;

		item.ACTIONS.splice(indexToDelete, 1);
		BX.cleanNode(actionToDelete._node, true);
	};

	BX.IvrEditor.prototype._onCancelButtonClick = function()
	{
		if(BX.SidePanel.Instance.isOpen())
		{
			BX.SidePanel.Instance.close();
		}
		else
		{
			document.location.href = ivrDefaults.IVR_LIST_URL;
		}
	};

	BX.IvrEditor.prototype._onSaveButtonClick = function()
	{
		if (this.saving)
			return;

		this.saving = true;
		var self = this;
		var waitNode = BX.create('span', {props : {className : "wait"}});
		BX.addClass(this.elements.saveButton, "webform-small-button-wait webform-small-button-active");
		this.elements.saveButton.appendChild(waitNode);

		var postParams = {
			action: 'save',
			sessid: BX.bitrix_sessid(),
			IVR: JSON.stringify(this.ivrData)
		};

		BX.ajax({
			url: AJAX_URL,
			method: 'POST',
			data: postParams,
			onsuccess: function(response)
			{
				BX.removeClass(self.elements.saveButton, "webform-small-button-wait webform-small-button-active");
				BX.remove(waitNode);
				try
				{
					response = JSON.parse(response)
				}
				catch (e)
				{
					BX.debug('Error decoding server response');
					return false;
				}

				if(response.SUCCESS === true)
				{
					if(BX.SidePanel.Instance.isOpen())
					{
						BX.SidePanel.Instance.postMessage(
							window,
							"IvrEditor::onSave",
							{
								ivrId: response.DATA.IVR.ID,
								ivr: response.DATA.IVR
							}
						);
						BX.SidePanel.Instance.close();
					}
					else
					{
						document.location.href = ivrDefaults.IVR_LIST_URL;
					}
				}
				else
				{
					alert(response.ERROR);
					self.saving = false;
				}
			}
		})
	};

	BX.IvrEditor.prototype.selectActionType = function (params)
	{
		var self = this;
		var item = params.item;
		if(!BX.type.isPlainObject(params))
			params = {};

		if (!BX.type.isFunction(params.onSelect))
			params.onSelect = BX.DoNothing;

		var selectCallback = function(actionType)
		{
			return function()
			{
				var e = {
					type: actionType
				};

				self.actionTypeMenu.popupWindow.close();
				params.onSelect(e);
			}
		};

		var menuItems = [
			{
				id: 'ivr-action-menu-user',
				text: BX.message('VOX_IVR_EDIT_MENU_ACTION_USER'),
				onclick: selectCallback('user')
			},
			{
				id: 'ivr-action-menu-queue',
				text: BX.message('VOX_IVR_EDIT_MENU_ACTION_QUEUE'),
				onclick: selectCallback('queue')
			},
			{
				id: 'ivr-action-menu-phone',
				text: BX.message('VOX_IVR_EDIT_MENU_ACTION_PHONE'),
				onclick: selectCallback('phone')
			},
			{
				id: 'ivr-action-menu-directCode',
				text: BX.message('VOX_IVR_EDIT_MENU_ACTION_DIRECTCODE'),
				onclick: selectCallback('directCode')
			},
			{
				id: 'ivr-action-menu-voicemail',
				text: BX.message('VOX_IVR_EDIT_MENU_ACTION_VOICEMAIL'),
				onclick: selectCallback('voicemail')
			},
			{
				id: 'ivr-action-menu-repeat',
				text: BX.message('VOX_IVR_EDIT_MENU_ACTION_REPEAT'),
				onclick: selectCallback('repeat')
			}
			/*{
				id: 'ivr-action-menu-message',
				text: BX.message('VOX_IVR_EDIT_MENU_ACTION_MESSAGE'),
				onclick: selectCallback('message')
			},*/
		];
		if(item.LEVEL > 0)
		{
			menuItems.push({
				id: 'ivr-action-menu-return',
				text: BX.message('VOX_IVR_EDIT_MENU_ACTION_RETURN'),
				onclick: selectCallback('return')
			});
		}
		menuItems.push({
			id: 'ivr-action-menu-exit',
			text: BX.message('VOX_IVR_EDIT_MENU_ACTION_EXIT'),
			onclick: selectCallback('exit')
		});
		menuItems.push({
			delimiter: true
		});
		menuItems.push({
			id: 'ivr-action-menu-item',
			text: BX.message('VOX_IVR_EDIT_MENU_ACTION_NEW_ITEM'),
			onclick: selectCallback('item')
		});

		this.actionTypeMenu = BX.PopupMenu.create(
			'ivr-action-type-menu',
			params.node,
			menuItems,
			{
				closeByEsc: true,
				autoHide: true,
				offsetTop: 0,
				offsetLeft: 12,
				angle: {position: "top"},
				events: {
					onPopupClose : function()
					{
						self.actionTypeMenu.popupWindow.destroy();
						BX.PopupMenu.destroy('ivr-action-type-menu');
					},
					onPopupDestroy: function ()
					{
						self.actionTypeMenu = null;
					}
				}
			}
		);
		this.actionTypeMenu.popupWindow.show();
	};

	BX.IvrEditor.prototype.createAction = function (params)
	{
		if(!BX.type.isPlainObject(params))
			params = {};

		return {
			ID: null,
			ACTION: params.ACTION || '',
			DIGIT: params.DIGIT,
			ITEM_ID: '',
			LEAD_FIELDS: null,
			PARAMETERS: {}
		}
	};

	BX.IvrEditor.prototype.createItem = function (params)
	{
		return {
			ID: null,
			TYPE: 'message',
			MESSAGE: '',
			TTS_VOICE: ivrDefaults.DEFAULT_VOICE,
			TTS_SPEED: ivrDefaults.DEFAULT_SPEED,
			TTS_VOLUME: ivrDefaults.DEFAULT_VOLUME,
			TIMEOUT: 15,
			TIMEOUT_ACTION: 'exit',
			ACTIONS: BX.type.isArray(params.ACTIONS) ? params.ACTIONS : [],
			LEVEL: params.LEVEL
		}
	};

	BX.IvrEditor.prototype._createOptions = function (srcObject, defaultValue)
	{
		var result = [];
		if(!BX.type.isPlainObject(srcObject))
			return result;

		for(var fieldName in srcObject)
		{
			if(srcObject.hasOwnProperty(fieldName) && BX.type.isNotEmptyString(srcObject[fieldName]))
			{
				result.push(BX.create("option", {
					props: {value: fieldName, selected: (fieldName == defaultValue)},
					text: BX.util.htmlspecialchars(srcObject[fieldName])
				}));
			}
		}
		return result;
	};

	BX.IvrEditor.prototype._adjustTextAreaSize = function(node)
	{
		if (!BX.type.isDomNode)
			return false;

		if(node.scrollHeight > node.clientHeight) node.style.height = node.scrollHeight + 'px';
	};

	BX.IvrEditor.prototype.showUnavailablePopup = function()
	{
		BX.UI.InfoHelper.show('limit_contact_center_telephony_ivr');
	};

	BX.IvrEditor.prototype.showLimitReachedPopup = function()
	{
		BX.UI.InfoHelper.show('limit_contact_center_telephony_ivr');
	};

	BX.IvrEditor.prototype.showGroupSettings = function (action, groupId)
	{
		this.currentGroupAction = action;
		BX.SidePanel.Instance.open("/telephony/editgroup.php?ID=" + groupId, {cacheable: false})
	};

	BX.IvrEditor.prototype._onSliderClosed = function(event)
	{
		this.render();
		this.currentGroupAction = null;
	};

	BX.IvrEditor.prototype._onSliderMessageReceived = function(event)
	{
		var eventId = event.getEventId();

		if(eventId === "QueueEditor::onSave")
		{
			var groupFields = event.getData()['DATA']['GROUP'];
			if(!groupFields['ID'])
			{
				return;
			}
			this.afterGroupSaved(groupFields);
			this.render();
		}
	};

	BX.IvrEditor.prototype.afterGroupSaved = function(groupFields)
	{
		var found = false;
		for (var i = 0; i < ivrDefaults.TELEPHONY_GROUPS.length; i++)
		{
			if (ivrDefaults.TELEPHONY_GROUPS[i].ID == groupFields.ID)
			{
				ivrDefaults.TELEPHONY_GROUPS[i].NAME = groupFields.NAME;
				found = true;
				break;
			}
		}
		if(!found)
		{
			ivrDefaults.TELEPHONY_GROUPS.push({
				ID: groupFields.ID,
				NAME: groupFields.NAME
			});
		}
		if(this.currentGroupAction)
		{
			this.currentGroupAction.PARAMETERS['QUEUE_ID'] = groupFields.ID;
		}
	};

	BX.IvrEditor._groupsToOptions = function (groups, selectedGroupId)
	{
		var result = [
			BX.create("option", {props: {value: "new"}, text: BX.message('VOX_IVR_HIDE_GROUP_CREATE')}),
			BX.create("option", {props: {disabled: true}, html: '&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;'})
		];

		if(!BX.type.isArray(groups))
			return result;

		groups.forEach(function(group)
		{
			result.push(BX.create("option", {props: {value: group.ID, selected: (group.ID == selectedGroupId)}, text: group.NAME}));
		});
		return result;
	};

	BX.IvrEditor._getOccupiedDigits = function(itemDescriptor)
	{
		var result = [];

		itemDescriptor.ACTIONS.forEach(function(action)
		{
			result.push(action.DIGIT);
		});
		return result;
	};

	BX.IvrEditor.message = function (message, replacements)
	{
		var result = BX.message(message);
		if(BX.type.isPlainObject(replacements))
		{
			for(var replacementKey in replacements)
			{
				if(replacements.hasOwnProperty(replacementKey))
					result = result.replace(replacementKey, replacements[replacementKey]);
			}
		}
		return result;
	};

	var KeyPad = function(params)
	{
		this.node = params.node;
		this.occupiedDigits = BX.type.isArray(params.occupiedDigits) ? params.occupiedDigits : [];
		this.callbacks = {
			onSelect: BX.type.isFunction(params.onSelect) ? params.onSelect : BX.DoNothing,
			onClose: BX.type.isFunction(params.onClose) ? params.onClose : BX.DoNothing
		};
		this.popup = null;
	};

	KeyPad.create = function(params)
	{
		return new KeyPad(params);
	};

	KeyPad.prototype.show = function()
	{
		var self = this;
		if(this.popup)
			this.popup.show();

		this.popup = new BX.PopupWindow(this.createId(), this.node, {
			content: this.createLayout(),
			closeByEsc: true,
			autoHide: true,
			overlay: {backgroundColor: 'black', opacity: 30},
			events: {
				onPopupClose: function()
				{
					self.callbacks.onClose();
				}
			}
		});

		this.popup.show();
	};

	KeyPad.prototype.close = function()
	{
		if(this.popup)
			this.popup.close();
	};

	KeyPad.prototype.createLayout = function()
	{
		var self = this;
		var createDigitNode = function(digit)
		{
			var containerClassName = (self.occupiedDigits.indexOf(digit) === -1 ? 'ivr-keypad-btn' : 'ivr-keypad-btn ivr-keypad-btn-occupied');
			var valueClassName = (digit === '*' ? 'ivr-keypad-btn-value ivr-keypad-btn-value-star' : 'ivr-keypad-btn-value');
			return BX.create("span", {
				props: {className: containerClassName},
				dataset: {digit: digit},
				events: {click: self.onDigitClick.bind(self, digit)},
				children: [
					BX.create("span", {props: {className: valueClassName}, text: digit})
				]
			});
		};

		return BX.create("div", {props:{className: 'ivr-keypad-container'}, children: [
			createDigitNode("1"),
			createDigitNode("2"),
			createDigitNode("3"),
			createDigitNode("4"),
			createDigitNode("5"),
			createDigitNode("6"),
			createDigitNode("7"),
			createDigitNode("8"),
			createDigitNode("9"),
			createDigitNode("*"),
			createDigitNode("0"),
			createDigitNode("#")
		]})
	};

	KeyPad.prototype.createId = function()
	{
		return 'vox-ivr-keypad-' + (new Date()).getTime().toString();
	};

	KeyPad.prototype.onDigitClick = function(digit)
	{
		if(this.occupiedDigits.indexOf(digit) != -1)
			return;

		this.callbacks.onSelect({digit: digit});
	};

	KeyPad.prototype.dispose = function()
	{
		if(this.popup)
			this.popup.destroy();

		this.popup = null;
	};

	var Uploader = function(params)
	{
		this.node = params.node;
		this.fileId = params.fileId || 0;
		this.fileUrl = params.fileUrl || '';

		this.maxFileSize = 2097152;

		this.callbacks = {
			onSelect: (BX.type.isFunction(params.onSelect)) ? params.onSelect : BX.DoNothing,
			onDelete: (BX.type.isFunction(params.onDelete)) ? params.onDelete : BX.DoNothing
		};

		this.elements = {
			fileInput: null
		};

		this.playerId = '';

		this.init();
	};

	Uploader.create = function(params)
	{
		return new Uploader(params);
	};

	Uploader.prototype.init = function()
	{
		this.render();
	};

	Uploader.prototype.render = function()
	{
		var self = this;
		var uploaderNode;
		BX.cleanNode(this.node);

		if(this.fileId == 0 && this.fileUrl == '')
		{
			uploaderNode =
				BX.create('div', {
                    props: {
                    	id: 'ivrHint',
                    	className: 'ivr-dot-btn-box'
					},
					children: [
                        BX.create("div", {
                            children: [
							BX.create('span', {
								props: {className: 'ivr-dot-btn'},
								text: BX.message('VOX_IVR_EDIT_MENU_LOAD_FILE'),
								events: {
									click: this._onUploadButtonClick.bind(this)
								}
							}),
							this.elements.fileInput = BX.create('input', {
								attrs: {type: 'file', accept: 'audio/*'},
								style: {display: 'none'},
								events: {
									change: this._onFileSelected.bind(this)
								}
							})
						]}),
                        BX.create('div', {
                        	children: [
                        		BX.UI.Hint.createNode(BX.message('VOX_IVR_EDIT_MENU_DESCRIPTION_FILE'))
							]
						})
					]
				})

		}
		else
		{
			this.playerId = 'file_player_' + BX.util.getRandomString(10);
			uploaderNode = BX.create("div", {props: {className: 'ivr-uploader-subcontainer'}, children: [
				BX.create("div", {props: {className: 'ivr-uploader-wrap'}, children: [
					this.elements.playerContainer = BX.create('div', {props: {className: 'ivr-uploader-wrap'}, attrs: {id: this.playerId}})
				]}),
				BX.create("div", {props: {className: 'ivr-uploader-delete-icon'}, children: [
					BX.create('i', {props: {className: 'fa fa-times'}, attrs: {'aria-hidden': 'true'}, events: {
						click: this._onDeleteButtonClick.bind(this)
					}})
				]})
			]});

			setTimeout(function()
			{
				jwplayer(self.playerId).setup({
					height: 24,
					width: 200,
					file: self.fileUrl,
					controlbar: 'bottom',
					primary: 'html5',
					fallback: false,
					modes: [
						{type: "html5"}
					],
					flashplayer: '/bitrix/components/bitrix/player/mediaplayer/player'
				});
			}, 10)
		}
		this.node.appendChild(uploaderNode);
	};

	Uploader.prototype.resize = function()
	{
		var player = jwplayer(this.playerId);
		if(player)
			player.resize(200, 24);
	};

	Uploader.prototype._onUploadButtonClick = function()
	{
		if(this.elements.fileInput)
			this.elements.fileInput.click();
	};

	Uploader.prototype._onFileSelected = function(e)
	{
		var self = this;
		var file = e.target.files[0];

		if(!(file instanceof File))
			return;

		this.upload(file, function(result)
		{
			self.fileId = result.FILE_ID;
			self.fileUrl = result.FILE_SRC;
			self.render();
			self.callbacks.onSelect(result);
		});
	};

	Uploader.prototype._onDeleteButtonClick = function()
	{
		this.fileId = 0;
		this.fileUrl = '';
		this.render();
		this.callbacks.onDelete();
	};

	Uploader.prototype.upload = function(file, next)
	{
		var formData = new FormData();
		formData.append('sessid', BX.bitrix_sessid());
		formData.append('action', 'upload_file');
		formData.append('FILE', file);

		BX.ajax({
			url: AJAX_URL,
			method: 'POST',
			data: formData,
			preparePost: false,
			onsuccess: function(response)
			{
				var data;
				try
				{
					data = JSON.parse(response);
				}
				catch (e)
				{
					BX.debug('Error parsing server response');
					return;
				}

				if(!data.SUCCESS)
				{
					alert(data.ERROR)
				}
				else
				{
					next(data.DATA);
				}
			}
		});
	};

	var UserSelector = function(params)
	{
		this.id = 'ivr-user-selector-' + randomString(16);
		this.node = params.node;
		this.callbacks = {
			onSelect: (BX.type.isFunction(params.onSelect) ? params.onSelect : BX.DoNothing),
			onDelete: (BX.type.isFunction(params.onDelete) ? params.onDelete : BX.DoNothing)
		};

		this.elements = {
			container: null,
			item: null,
			inputBox: null,
			input: null,
			addButton: null
		};

		this.userId = params.userId || 0;
		this.userName = this.getUserName(this.userId);

		this.init();
	};

	UserSelector.create = function(params)
	{
		return new UserSelector(params);
	};

	UserSelector.prototype.init = function()
	{
		var self = this;
		this.render();
		BX.SocNetLogDestination.init({
			name : this.id,
			searchInput : this.elements.input,
			departmentSelectDisable : true,
			extranetUser :  false,
			allowAddSocNetGroup: false,
			bindMainPopup : {
				node : this.elements.container,
				offsetTop : '5px',
				offsetLeft: '15px'
			},
			bindSearchPopup : {
				node : this.elements.container,
				offsetTop : '5px',
				offsetLeft: '15px'
			},
			callback : {
				select : this.onSelect.bind(this),
				unSelect : BX.DoNothing,
				openDialog : this.onOpenDialog.bind(this),
				closeDialog : this.onCloseDialog.bind(this),
				openSearch : BX.DoNothing,
				closeSearch : this.onCloseSearch.bind(this)
			},
			items : {
				users : {},
				groups : {},
				sonetgroups : {},
				department : ivrDefaults.STRUCTURE.department,
				departmentRelation : ivrDefaults.STRUCTURE.department_relation
			},
			itemsLast : {
				users : {},
				sonetgroups : {},
				department : {},
				groups : {}
			},
			itemsSelected: {},
			destSort: {}
		});
	};

	UserSelector.prototype.render = function()
	{
		var self = this;
		BX.cleanNode(this.node);
		var selectorNode = BX.create("div", {props: {className: 'tel-set-destination-container'}, children: [
			this.elements.container = BX.create("span", {props: {className: 'bx-destination-wrap'}, children: [
				this.elements.item = BX.create("span", {children: this.renderUsers()}),
				this.elements.inputBox = BX.create("span", {props: {className: 'bx-destination-input-box'}, children: [
					this.elements.input = BX.create("input", {props: {className: 'bx-destination-input'}, events: {
						keyup: this.onInputKeyUp.bind(this),
						keydown: this.onInputKeyDown.bind(this)
					}})
				]}),
				this.elements.addButton = BX.create("a", {
					props: {className: 'bx-destination-add'},
					text: (this.userId == 0 ? BX.message('VOX_IVR_EDIT_SELECT') : BX.message('VOX_IVR_EDIT_CHANGE')),
					events: {
						click: function(e)
						{
							BX.SocNetLogDestination.deleteLastItem(self.id);
							BX.SocNetLogDestination.openDialog(self.id);
							BX.PreventDefault(e);
						}
					}
				})
			]})

		]});
		this.node.appendChild(selectorNode);
	};

	UserSelector.prototype.renderUsers = function()
	{
		var result = [];
		if(this.userId > 0 && this.userName != '')
		{
			result.push(BX.create("span", {props: {className: 'bx-destination bx-destination-users'}, dataset: {userId: this.userId}, children: [
				BX.create("span", {props: {className: 'bx-destination-text'}, text: BX.util.htmlspecialchars(this.userName)}),
				BX.create("span", {props: {className: 'bx-destination-del-but'}})
			]}))
		}
		return result;
	};

	UserSelector.prototype.onInputKeyDown = function(event)
	{
		if (event.keyCode == 8 && this.elements.input.value.length <= 0)
		{
			BX.SocNetLogDestination.sendEvent = false;
			BX.SocNetLogDestination.deleteLastItem(this.id);
		}
		return true;
	};

	UserSelector.prototype.onInputKeyUp = function(event)
	{
		if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
			return false;

		if (event.keyCode == 13)
		{
			BX.SocNetLogDestination.selectFirstSearchItem(this.id);
			return true;
		}
		if (event.keyCode == 27)
		{
			this.elements.input.value = '';
			BX.style(this.elements.addButton, 'display', 'inline');
		}
		else
		{
			BX.SocNetLogDestination.search(this.elements.input.value, true, this.id);
		}

		if (!BX.SocNetLogDestination.isOpenDialog() && this.elements.input.value.length <= 0)
		{
			BX.SocNetLogDestination.openDialog(this.id);
		}
		else if (BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.isOpenDialog())
		{
			BX.SocNetLogDestination.closeDialog();
		}
		if (event.keyCode == 8)
		{
			BX.SocNetLogDestination.sendEvent = true;
		}
		return true;
	};

	UserSelector.prototype.onOpenDialog = function()
	{
		BX.style(this.elements.inputBox, 'display', 'inline-block');
		BX.style(this.elements.addButton, 'display', 'none');
		BX.focus(this.elements.input);
	};

	UserSelector.prototype.onCloseDialog = function()
	{
		if (this.elements.input.value.length <= 0)
		{
			BX.style(this.elements.inputBox, 'display', 'none');
			BX.style(this.elements.addButton, 'display', 'inline-block');
			this.elements.input.value = '';
		}
	};

	UserSelector.prototype.onCloseSearch = function()
	{
		BX.style(this.elements.inputBox, 'display', 'none');
		BX.style(this.elements.addButton, 'display', 'inline-block');
		this.elements.input.value = '';
	};

	UserSelector.prototype.onSelect = function(userInfo)
	{
		this.userId = userInfo.entityId;
		this.userName = userInfo.name;
		if(BX.SocNetLogDestination.isOpenDialog())
		{
			BX.SocNetLogDestination.closeDialog();
		}
		BX.cleanNode(this.elements.item);
		BX.adjust(this.elements.item, {children: this.renderUsers()});
		this.elements.addButton.innerText = BX.message('VOX_IVR_EDIT_CHANGE');

		this.callbacks.onSelect({
			userId: this.userId,
			userName: this.userName
		})
	};

	UserSelector.prototype.onDelete = function()
	{
		this.userId = 0;
		this.userName = '';
		this.cleanNode(this.elements.item);
		this.elements.addButton.innerText = BX.message('VOX_IVR_EDIT_SELECT');
		this.callbacks.onDelete();
	};

	UserSelector.prototype.getUserName = function(userId)
	{
		userId = parseInt(userId);

		if(userId == 0)
			return '';

		if(ivrDefaults.USERS['U' + userId])
			return ivrDefaults.USERS['U' + userId]['name'];
		else
			return '';
	};
})();