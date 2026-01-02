/* eslint-disable */
(function() {

	const iframeMode = window !== window.top;
	const search = window.location.search;
	const sliderMode = search.indexOf("IFRAME=") !== -1 || search.indexOf("IFRAME%3D") !== -1;

	const CMR_SMART_INVOICE_TYPE_ID = 31;
	const CMR_QUOTE_TYPE_ID = 7;
	const CMR_SMART_DOCUMENT_TYPE_ID = 36;

	if (iframeMode && sliderMode)
	{
		return;
	}

	const siteDir = ('/' + (BX.message.SITE_DIR || '/')
		.replace(/[\\*+?.()|[\]{}]/g, '\\$&') + '/')
		.replace(/\/+/g, '/')
	;

	this.mailLoader = BX.create("div", {
		props: {
			className: 'mail-loader-node'
		},
	});

	const detectCrmSliderWidth = function()
	{
		if (window.innerWidth < 1500)
		{
			return null; // default slider width
		}

		return 1500 + Math.floor((window.innerWidth - 1500) / 3);
	};

	const getQueryParams = function(url)
	{
		// Extract the query string part after '?'
		const queryStringMatch = url.match(/\?([^#]*)/);
		if (!queryStringMatch) return {};

		const queryString = queryStringMatch[1];
		const params = {};

		// Regex to match key=value pairs
		queryString.replace(/([^&=]+)=([^&]*)/g, (match, key, value) => {
			params[decodeURIComponent(key)] = decodeURIComponent(value);
			return '';
		});

		return params;
	}

	BX.SidePanel.Instance.bindAnchors({
		rules: [
			{
				condition: [
					/^(http|https):\/\/([^\/]+)\/knowledge/i,
					/^(http|https):\/\/([^\/]+)\/extranet\/knowledge/i
				],
				handler: function(event, link) {

					var sliderMode = link.anchor.href.indexOf('IFRAME=') !== -1 ||
									link.anchor.href.indexOf('IFRAME%3D') !== -1;

					if (!sliderMode)
					{
						event.preventDefault();
						BX.SidePanel.Instance.open(link.url, {cacheable: false, customLeftBoundary: 60});
					}
				},
				customLeftBoundary: 240
			},
			{
				condition: [
					'/company/personal/user/(\\d+)/tasks/effective/show/',
					'/company/personal/user/(\\d+)/tasks/effective/inprogress/'
				],
				loader: 'default-loader'
			},
			{
				condition: ['/company/personal/user/(\\d+)/tasks/import/'],
				loader: 'default-loader',
				options: {
					cacheable: false
				}
			},
			{
				condition: [
					new RegExp("/company/personal/user/(\\d+)/tasks/task/view/(\\d+)/\\?commentAction=([a-zA-Z]+)&deadline=([0-9]+)", "i"),
					new RegExp("/company/personal/user/(\\d+)/tasks/task/view/(\\d+)/\\?commentAction=([a-zA-Z]+)", "i"),

					new RegExp("/extranet/contacts/personal/user/(\\d+)/tasks/task/view/(\\d+)/\\?commentAction=([a-zA-Z]+)&deadline=([0-9]+)", "i"),
					new RegExp("/extranet/contacts/personal/user/(\\d+)/tasks/task/view/(\\d+)/\\?commentAction=([a-zA-Z]+)", "i"),
				],
				handler: function(event, link) {
					if (BX.Tasks.CommentActionController)
					{
						BX.Tasks.CommentActionController.processLink(link);
					}
					event.preventDefault();
				}
			},
			{
				condition: [
					'(?<url>/company/personal/user/(\\d+)/tasks/task/view/(?<taskId>\\d+)/\\?chatAction=(?<chatAction>[A-Za-z]+)(?:&entityId=(?<entityId>\\d+))?)',
					'(?<url>/workgroups/group/(\\d+)/tasks/task/view/(?<taskId>\\d+)/\\?chatAction=(?<chatAction>[A-Za-z]+)(?:&entityId=(?<entityId>\\d+))?)',
					'(?<url>/extranet/contacts/personal/user/(\\d+)/tasks/task/view/(?<taskId>\\d+)/\\?chatAction=(?<chatAction>[A-Za-z]+)(?:&entityId=(?<entityId>\\d+))?)',
				],
				handler: async function(event, link) {
					event.preventDefault();

					const { chatActionService } = await BX.Runtime.loadExtension('tasks.v2.lib.chat-action-service');

					if (chatActionService)
					{
						const coordinates = { x: event.pageX, y: event.pageY };

						chatActionService.process(link, { coordinates });
					}
				}
			},
			{
				condition: [
					'(?<url>/company/personal/user/(\\d+)/tasks/task/view/(?<taskId>\\d+)/)',
					'(?<url>/company/personal/user/(\\d+)/tasks/task/edit/(?<taskId>\\d+)/)',
					'(?<url>/workgroups/group/(?<groupId>\\d+)/tasks/task/view/(?<taskId>\\d+)/)',
					'(?<url>/workgroups/group/(?<groupId>\\d+)/tasks/task/edit/(?<taskId>\\d+)/)',
					'(?<url>/extranet/contacts/personal/user/(\\d+)/tasks/task/view/(?<taskId>\\d+)/)',
					'(?<url>/extranet/contacts/personal/user/(\\d+)/tasks/task/edit/(?<taskId>\\d+)/)',
				],
				minimizeOptions: (link) => {
					return {
						entityType: 'tasks:task',
						entityId: link.matches.groups.taskId,
						entityName: BX.message('INTRANET_BINDINGS_TASK'),
						url: link.matches.groups.url,
					};
				},
				stopParameters: [
					'PAGEN_(\\d+)',
				],
				options: (link) => {
					const queryParams = getQueryParams(link.url);
					const taskId = parseInt(link.matches.groups.taskId, 10);
					const groupId = parseInt(link.matches.groups.groupId, 10) || undefined;
					const hasTemplate = Number(queryParams.TEMPLATE) > 0 || Number(queryParams.FLOW_ID) > 0;
					const isCommentLink = Boolean(queryParams.MID);

					const tasksSettings = BX.Extension.getSettings('intranet.sidepanel.bindings').get('tasks');
					const isV2Form = tasksSettings.isV2Form || tasksSettings.allowedGroups.includes(groupId);
					if (queryParams.OLD_FORM === 'Y' || hasTemplate || isCommentLink || !isV2Form)
					{
						return {
							label: {
								text: BX.message('INTRANET_BINDINGS_TASK'),
								bgColor: '#2FC6F6',
							},
							events: {
								onCloseComplete: function() {
									BX.Runtime.loadExtension('bitrix24.feedbackcollector').then(function(exports) {
										var feedbackCollectorClass = exports.FeedbackCollector;
										if (feedbackCollectorClass)
										{
											(new feedbackCollectorClass({feedbackId: 'tasksFeedbackSliderClose'})).run();
										}
									});
								},
							},
							printable: true,
							loader: 'intranet:task-detail',
						};
					}

					const url = link.matches.groups.url;
					const analytics = {
						context: queryParams.ta_sec,
						element: queryParams.ta_el,
					};

					let card = null;

					return {
						width: 1510,
						customLeftBoundary: 0,
						cacheable: false,
						contentCallback: (slider) => {
							void BX.Runtime.loadExtension('tasks.v2.application.task-full-card').then((exports) => {
								card = new exports.TaskFullCard({ taskId, groupId, url, link, analytics });
								card.mount(slider);
							});

							return taskSkeleton();
						},
						events: {
							onCloseComplete: () => card?.unmount(),
						},
						allowChangeHistory: true,
						printable: true,
						copyLinkLabel: true,
						newWindowUrl: url,
					};
				},
				handler: async function(event, link) {
					event.preventDefault();

					const queryParams = getQueryParams(link.url);
					const hasTemplate = Number(queryParams.TEMPLATE) > 0 || Number(queryParams.FLOW_ID) > 0;

					if (!hasTemplate && queryParams.miniform)
					{
						const { TaskCard } = await BX.Runtime.loadExtension('tasks.v2.application.task-card');

						TaskCard.showCompactCard({
							analytics: {
								context: queryParams.ta_sec,
								element: queryParams.ta_el,
							},
						});

						return;
					}

					if (link.anchor?.dataset.sliderMaximize)
					{
						BX.SidePanel.Instance.maximize(link.url, this.options);
					}
					else
					{
						BX.SidePanel.Instance.open(link.url, this.options);
					}
				},
			},
			{
				condition: [
					'/company/personal/user/(\\d+)/tasks/templates/template/view/(\\d+)/',
				],
				loader: 'intranet:task-detail',
				stopParameters: [
					'PAGEN_(\\d+)',
				],
				options: {
					label: {
						text: BX.message("INTRANET_BINDINGS_TASK"),
						bgColor: "#2FC6F6"
					}
				}
			},
			{
				condition: [
					'/company/personal/user/\\d+/tasks/flow/\\?apply_filter=Y&ID_numsel=exact&ID_from=\\d+&editFormFlowId=(\\d+)',
				],
				handler: (event, link) => {
					top.BX.Runtime.loadExtension('tasks.flow.edit-form')
						.then((exports) => {
							const flowId = link.matches[1];
							const editForm = exports?.EditForm.createInstance({
								flowId,
							});
							editForm?.openInSlider();
						})
					;
					event.preventDefault();
				},
				options: {
					cacheable: false,
				},
			},
			{
				condition: [
					'/company/personal/user/\\d+/tasks/flow/\\?apply_filter=Y&ID_numsel=exact&ID_from=(\\d+)',
				],
				loader: 'default-loader',
				options: {
					cacheable: false,
				},
			},
			{
				condition: [
					'/company/personal/user/(\\d+)/tasks/templates/template/edit/0/',
				],
				loader: 'intranet:task-add',
				options: {
					label: {
						text: BX.message('INTRANET_BINDINGS_TASK'),
						bgColor: '#2FC6F6',
					},
				},
			},
			{
				condition: [
					'/company/personal/user/(\\d+)/tasks/task/edit/(\\d+)/',
					'/company/personal/user/(\\d+)/tasks/templates/template/edit/(\\d+)/',
					'/workgroups/group/(\\d+)/tasks/task/edit/(\\d+)/',
					'/extranet/contacts/personal/user/(\\d+)/tasks/task/edit/(\\d+)/'
				],
				loader: 'intranet:task-add',
				options: {
					label: {
						text: BX.message("INTRANET_BINDINGS_TASK"),
						bgColor: "#2FC6F6"
					}
				}
			},
			{
				condition: [
					'/company/personal/user/\\d+/tasks/\\?F_STATE=sVag',
					'/workgroups/group/\\d+/tasks/\\?F_STATE=sVag',
					'/extranet/contacts/personal/user/\\d+/tasks/\\?F_STATE=sVag'
				],
				handler: function(event) {
					debugger;
					const Messenger = BX.Reflection.getClass('BX.Messenger.Public')
					if (!Messenger)
					{
						return;
					}

					Messenger.openNavigationItem({ id: 'tasksTask' });

					event.preventDefault();
				},
			},
			{
				condition: ['/crm/button/edit/(\\d+)/'],
				loader: 'intranet:crm-button-view-loader'
			},
			{
				condition: ['/marketplace/einvoice/'],
				options: {
					width: 575,
					allowChangeHistory: false,
				},
			},
			{
				condition: [
					new RegExp("/marketplace\/view\/quick\/"),
				],
				options: {
					width: 500,
					allowChangeHistory: false,
					cacheable: false
				}
			},
			{
				condition: [
					new RegExp("/marketplace/configuration/import_"),
					new RegExp("/marketplace/configuration/export_"),
				],
				options: {
					width: 491,
					allowChangeHistory: false,
					cacheable: false,
					data: {
						rightBoundary: 0,
					},
				}
			},
			{
				condition: [
					new RegExp("/marketplace\/configuration/"),
					new RegExp("/marketplace\/booklet/"),
				],
				options: {
					width: 940,
					allowChangeHistory: false,
					cacheable: false
				}
			},
			{
				condition: [
					new RegExp("/marketplace\/placement/"),
					new RegExp("/marketplace\/view/"),
				],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					customLeftBoundary: 0
				}
			},
			{
				condition: [
					new RegExp("/marketplace\/installed/"),
				],
				options: {
					customLeftBoundary: 0,
					// not
					loader: "rest:marketplace2"
				}
			},
			{
				condition: [
					new RegExp("\\/marketplace\\/.*?((\\?|\\&)(tag|placement))"),
				],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					customLeftBoundary: 0,
					// not
					loader: "rest:marketplace1"
				}
			},
			{
				condition: [

					new RegExp("/marketplace\/($|\\?)"),
				],
				options: {
					cacheable: false,
					customLeftBoundary: 0,
					// not
					loader: "rest:marketplace1"
				}
			},
			{
				condition: [
					new RegExp("/marketplace\/"),
				],
				options: {
					customLeftBoundary: 0,
					// not
					loader: "rest:marketplace1"
				}
			},
			{
				condition: [
					new RegExp("/devops\/"),
				],
				options: {
					cacheable: false,
				}
			},
			{
				condition: [
					new RegExp("/market/detail/")
				],
				options: {
					customLeftBoundary: 0,
					cacheable: false,
					loader: "market:detail",
					width: 1162,
				}
			},
			{
				condition: [
					new RegExp("/market/collection/page/[0-9]+/")
				],
				options: {
					cacheable: false,
					loader: "market:page",
					width: 900,
				}
			},
			{
				condition: [
					new RegExp("/market/reviews/")
				],
				options: {
					cacheable: false,
					width: 773,
				}
			},
			{
				condition: [
					new RegExp("/market/collection/"),
					new RegExp("/market/category/"),
					new RegExp("/market/favorites/"),
					new RegExp("/market/installed/"),
				],
				options: {
					cacheable: false,
					loader: "market:list",
					customLeftBoundary: 0,
				}
			},
			{
				condition: [
					new RegExp("\\/market\\/(\\?[\\w=&]+)*$"),
				],
				options: {
					cacheable: false,
					loader: "market:main",
					customLeftBoundary: 0,
				}
			},
			{
				condition: [
					/(FEATURE_PROMOTER)=([^&]+)/
				],
				handler: function(event, link)
				{
					const code = link.matches[2];

					if (BX.UI.FeaturePromotersRegistry && code)
					{
						BX.UI.FeaturePromotersRegistry.getPromoter({ code: code }).show();
					}

					event.preventDefault();
				},
			},
			{
				condition: [
					/(AI_UX_TRIGGER)=([^&]+)/,
				],
				handler: function(event, link)
				{
					const code = link.matches[2];
					if (code === 'box_agreement')
					{
						BX.Runtime.loadExtension('ai.copilot-agreement').then((exports) => {
							const CopilotAgreementClass = exports['CopilotAgreement'];
							if (CopilotAgreementClass)
							{
								const optionsCopilotAgreement = {
									moduleId: 'im',
									contextId: 'chat',
									events: {
										onAccept: () => {},
										onCancel: () => {},
									},
								};
								(new CopilotAgreementClass(optionsCopilotAgreement)).checkAgreement();
							}
						});

						event.preventDefault();
					}
				},
			},
			{
				condition: [
					/\?IM_COLLAB\b$/i,
				],
				handler: function(event, link)
				{
					const isAirTemplate = BX.Reflection.getClass('BX.Intranet.Bitrix24.Template') !== null;
					if (!window.BXIM || isAirTemplate)
					{
						return;
					}

					BX.Messenger.Public.openCollab();

					event.preventDefault();
				}
			},
			{
				condition: [
					/\?(IM_DIALOG|IM_HISTORY|IM_LINES|IM_COPILOT|IM_CHANNEL|IM_COLLAB|IM_TASK)=([^&]+)(&IM_MESSAGE=([^&]+))?(?:&BOT_CONTEXT=([^&]+))?/i
				],
				handler: function(event, link)
				{
					if (!window.BXIM)
					{
						return;
					}

					var type = link.matches[1];
					var dialogId = decodeURI(link.matches[2]);
					const messageId = link.matches[4] ? Number(link.matches[4]) : 0;
					const botContext = link.matches[5] ?? '';

					if (type === "IM_HISTORY")
					{
						BXIM.openHistory(dialogId);
					}
					else if (type === "IM_LINES")
					{
						BX.Messenger.Public.openLines(dialogId);
					}
					else if (type === 'IM_COPILOT')
					{
						BX.Messenger.Public.openCopilot(dialogId);
					}
					else if (type === 'IM_CHANNEL')
					{
						BX.Messenger.Public?.openNavigationItem({
							id: 'channel',
							entityId: dialogId,
						});
					}
					else if (type === 'IM_COLLAB')
					{
						BX.Messenger.Public.openCollab(dialogId);
					}
					else if (type === 'IM_TASK')
					{
						BX.Messenger.Public.openTaskComments(dialogId, messageId);
					}
					else if (botContext.length !== 0)
					{
						let decodedContext = {};
						try
						{
							decodedContext = JSON.parse(decodeURIComponent(botContext));
						}
						catch (error)
						{
							console.error('Intranet bindings: incorrect bot context', error);
						}

						BX.Messenger.Public.openChatWithBotContext(dialogId, decodedContext);
					}
					else
					{
						BX.Messenger.Public.openChat(dialogId, messageId);
					}

					event.preventDefault();
				}
			},
			{
				condition: [
					new RegExp(location.origin+'/online\/$'),
					/^\/online\/$/,
					/^\/extranet\/online\/$/,
				],
				handler: function(event, link)
				{
					const isAirTemplate = BX.Reflection.getClass('BX.Intranet.Bitrix24.Template') !== null;
					if (!window.BXIM || isAirTemplate)
					{
						return;
					}

					BXIM.openMessenger();

					event.preventDefault();
				}
			},
			{
				condition: [
					/^(https|http):\/\/(.*)\/online\/call\/([.\-0-9a-zA-Z]+)/i,
					/^(https|http):\/\/(.*)\/video\/([.\-0-9a-zA-Z]+)/i
				],
				allowCrossDomain: true,
				handler: function(event, link)
				{
					if (!window.BXIM)
					{
						return;
					}

					if (typeof BXIM.openVideoconfByUrl !== "function")
					{
						return;
					}

					if (BXIM.openVideoconfByUrl(link.url))
					{
						event.preventDefault();
					}
				}
			},
			{
				condition: [
					/^(http|https):\/\/helpdesk\.bitrix24\.([a-zA-Z\.]{2,})\/open\/([a-zA-Z0-9_|]+)/i
				],
				allowCrossDomain: true,
				handler: function(event, link)
				{
					if (BX.desktop && !BX.desktop.enableInVersion(60))
					{
						return true;
					}

					event.preventDefault();
					event.stopPropagation();

					var articleId = link.matches[3];
					if (articleId.substr(0,5).toLowerCase() === 'code_' )
					{
						var articleCode = articleId.slice(5);
						BX.Helper.show("redirect=detail&code="+articleCode);
					}
					else
					{
						BX.Helper.show("redirect=detail&HD_ID=" + articleId);
					}
				}
			},
			{
				condition: [ new RegExp("/shop/orders/details/[0-9]+/", "i") ],
				loader: "intranet:crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						text: BX.message("INTRANET_BINDINGS_ORDER"),
					},
					width: detectCrmSliderWidth(),
				},
			},
			{
				condition: [
					new RegExp("/shop/documents/details/[0-9]+/", "i"),
					new RegExp("/shop/documents/details/sales_order/[0-9]+/", "i")
				],
				options: {
					loader: "intranet:crm-entity-details-loader",
					cacheable: false,
					customLeftBoundary: 0,
				},
			},
			{
				condition: [
					new RegExp("/terminal/details/[0-9]+/", "i"),
				],
				options: {
					allowChangeHistory: false,
					cacheable: false,
					width: 450,
				}
			},
			{
				condition: [
					new RegExp("/shop/settings/cat_store_document_edit.php", "i"),
				],
				options: {
					cacheable: false,
				}
			},
			{
				condition: [
					new RegExp("/shop/documents-catalog/(\\d+)/product/", "i")
				],
				options: {
					cacheable: false,
				}
			},
			{
				condition: [
					new RegExp("/shop/documents-catalog/product/(\\d+)/", "i")
				],
				options: {
					cacheable: false,
				}
			},
			{
				condition: [
					new RegExp("/shop/documents-catalog/section/(\\d+)/", "i")
				],
				options: {
					cacheable: false,
				}
			},
			{
				condition: [new RegExp('(?<url>/crm/lead/details/(?<id>\\d+)/)', 'i')],
				minimizeOptions: (link) => {
					return {
						entityType: 'crm:lead',
						entityId: link.matches.groups.id,
						entityName: BX.message('INTRANET_BINDINGS_LEAD'),
						url: link.matches.groups.url,
					};
				},
				loader: "intranet:crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						text: BX.message("INTRANET_BINDINGS_LEAD"),
						bgColor: "#55D0E0",
					},
					width: detectCrmSliderWidth(),
				},
			},
			{
				condition: [new RegExp('(?<url>/crm/contact/details/(?<id>[0-9]+)/)', 'i')],
				minimizeOptions: (link) => {
					return {
						entityType: 'crm:contact',
						entityId: link.matches.groups.id,
						entityName: BX.message('INTRANET_BINDINGS_CONTACT'),
						url: link.matches.groups.url,
					};
				},
				loader: "intranet:crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						text: BX.message("INTRANET_BINDINGS_CONTACT"),
						bgColor: "#7BD500",
					},
					width: detectCrmSliderWidth(),
				},
			},
			{
				condition: [new RegExp('(?<url>/crm/company/details/(?<id>[0-9]+)/)', 'i')],
				minimizeOptions: (link) => {
					return {
						entityType: 'crm:company',
						entityId: link.matches.groups.id,
						entityName: BX.message('INTRANET_BINDINGS_COMPANY'),
						url: link.matches.groups.url,
					};
				},
				loader: "intranet:crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						bgColor: "#F7A700",
						text: BX.message("INTRANET_BINDINGS_COMPANY"),
					},
					width: detectCrmSliderWidth(),
				},
			},
			{
				condition: [new RegExp('(?<url>/crm/deal/details/(?<id>[0-9]+)/)', 'i')],
				minimizeOptions: (link) => {
					return {
						entityType: 'crm:deal',
						entityId: link.matches.groups.id,
						entityName: BX.message('INTRANET_BINDINGS_DEAL'),
						url: link.matches.groups.url,
					};
				},
				loader: "intranet:crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						text: BX.message("INTRANET_BINDINGS_DEAL"),
						bgColor: "#9985DD",
					},
					events: {
						onCloseComplete: function() {
							BX.Runtime.loadExtension('bitrix24.feedbackcollector').then(function(exports) {
								var feedbackCollectorClass = exports["FeedbackCollector"];
								if (feedbackCollectorClass)
								{
									(new feedbackCollectorClass({feedbackId: 'crmFeedbackSliderClose'})).run();
								}
							});
						},
					},
					width: detectCrmSliderWidth(),
				},
			},
			{
				condition: [new RegExp(`(?<url>/crm/type/${CMR_SMART_INVOICE_TYPE_ID}/details/(?<id>[0-9]+)/)`, 'i')],
				minimizeOptions: (link) => {
					return {
						entityType: 'crm:smart-invoice',
						entityId: link.matches.groups.id,
						entityName: BX.message('INTRANET_BINDINGS_SMART_INVOICE'),
						url: link.matches.groups.url,
					};
				},
				loader: "intranet:crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						text: BX.message("INTRANET_BINDINGS_SMART_INVOICE"),
						bgColor: "#1E6EC2",
					},
					width: detectCrmSliderWidth(),
				},
			},
			{
				condition: [new RegExp(`(?<url>/crm/type/${CMR_QUOTE_TYPE_ID}/details/(?<id>[0-9]+/))`, 'i')],
				minimizeOptions: (link) => {
					return {
						entityType: 'crm:quote',
						entityId: link.matches.groups.id,
						entityName: BX.message('INTRANET_BINDINGS_QUOTE_MSGVER_1'),
						url: link.matches.groups.url,
					};
				},
				loader: "intranet:crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						text: BX.message("INTRANET_BINDINGS_QUOTE_MSGVER_1"),
						bgColor: "#00B4AC",
					},
					width: detectCrmSliderWidth(),
				},
			},
			{
				condition: [new RegExp(`(?<url>/crm/type/${CMR_SMART_DOCUMENT_TYPE_ID}/details/(?<id>[0-9]+)/)`, 'i')],
				minimizeOptions: (link) => {
					return {
						entityType: 'crm:smart-document',
						entityId: link.matches.groups.id,
						entityName: BX.message('INTRANET_BINDINGS_SMART_DOCUMENT_MSGVER_1'),
						url: link.matches.groups.url,
					};
				},
				loader: "intranet:crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						text: BX.message("INTRANET_BINDINGS_SMART_DOCUMENT_MSGVER_1"),
						bgColor: "#C48300",
					},
					width: detectCrmSliderWidth(),
				},
			},
			{
				condition: [new RegExp('(?<url>^/.*/type/(?<type>[0-9]+)/details/(?<id>[0-9]+)/)', 'i')],
				loader: "intranet:crm-entity-details-loader",
				minimizeOptions: (link) => {
					return {
						entityType: 'crm:smart-process',
						entityId: `${link.matches.groups.type}:${link.matches.groups.id}`,
						url: link.matches.groups.url,
					};
				},
				options: {
					cacheable: false,
					width: detectCrmSliderWidth(),
				},
			},
			{
				condition: [new RegExp('(?<url>/crm/(company|contact)/requisite/([0-9]+)/)', 'i')],
				options: {
					cacheable: false,
					width: 950,
				},
			},
			{
				condition: [
					/^\/bitrix\/components\/bitrix\/biconnector.externalconnection\/slider.php\?sourceId=\d+/,
				],
				options: {
					cacheable: false,
					customLeftBoundary: 0,
					width: 564,
				},
			},
			{
				condition: [
					new RegExp("/report/analytics"),
					new RegExp("/report/analytics/\\?analyticBoardKey=(\\w+)"),
					new RegExp("/report/telephony"),
					new RegExp("/report/telephony/\\?analyticBoardKey=(\\w+)")
				],
				options: {
					cacheable: false,
					customLeftBoundary: 0,
					loader: "report:analytics"
				}
			},
			{
				condition: [
					new RegExp("/hr/structure"),
				],
				options: {
					cacheable: false,
					customLeftBoundary: 0,
				}
			},
			{
				condition: [ new RegExp("/bitrix/tools/disk/focus.php\\?.*(inSidePanel).*action=(openFileDetail)", "i") ]
			},
			{
				condition: [
					new RegExp(`(?<url>${siteDir}company/personal/user/(?<userId>[0-9]+)/)($|\\?)`, 'i'),
					new RegExp(`(?<url>${siteDir}contacts/personal/user/(?<userId>[0-9]+)/)($|\\?)`, 'i'),
					new RegExp(`(?<url>/company/personal/user/(?<userId>[0-9]+)/)($|\\?)`, 'i'),
					new RegExp(`(?<url>/contacts/personal/user/(?<userId>[0-9]+)/)($|\\?)`, 'i'),
				],
				minimizeOptions: (link) => {
					return {
						entityType: 'socialnetwork:user',
						entityName: BX.message('INTRANET_BINDINGS_EMPLOYEE'),
						entityId: link.matches.groups.userId,
						url: link.matches.groups.url,
					};
				},
				options: {
					contentClassName: "bitrix24-profile-slider-content",
					loader: "intranet:slider-profile",
					width: 1100
				}
			},
			{
				condition: [
					new RegExp(siteDir + "timeman/worktime/records/[0-9]+/report/($|\\?)", "i")
				],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					width: 800
				}
			},
			{
				condition: [
					new RegExp(siteDir + "company/personal/user/[0-9]+/edit/($|\\?)", "i"),
					new RegExp(siteDir + "contacts/personal/user/[0-9]+/edit/($|\\?)", "i"),
					new RegExp("/company/personal/user/[0-9]+/edit/($|\\?)", "i"),
					new RegExp("/contacts/personal/user/[0-9]+/edit/($|\\?)", "i"),
				],
				handler: function(event, link)
				{
					event.preventDefault();
					var newLink = link.url.replace("\/edit", "");

					BX.SidePanel.Instance.open(
						newLink,
						{
							cacheable: false,
							allowChangeHistory: false,
							contentClassName: "bitrix24-profile-slider-content",
							loader: "intranet:slider-profile",
							width: 1100
						}
					);
				}
			},
			{
				condition: [ new RegExp("/saleshub/orders/order/\\?.*") ],
				loader: "intranet:crm-entity-details-loader"
			},
			{
				condition: [
					'^' + siteDir + 'mail/config/(new|edit)',
				],
				options: {
					width: 760,
					cacheable: false
				}
			},
			{
				condition: [
					'^' + siteDir + 'mail/message/new'
				],
				options: {
					width: 1080,
					loader: 'intranet:create-mail-loader'
				}
			},
			{
				condition: [
					new RegExp(`(?<url>${siteDir}mail/message/(?<id>\\d+))`),
				],
				minimizeOptions: (link) => {
					return {
						entityType: 'mail:message',
						entityId: link.matches.groups.id,
						entityName: BX.message('INTRANET_BINDINGS_MAIL_MESSAGE'),
						url: link.matches.groups.url,
					};
				},
				options: {
					width: 1080,
					loader: 'intranet:view-mail-loader'
				}
			},
			{
				condition: [
					'^' + siteDir + 'mail/config/dirs'
				],
				options: {
					width: 640
				}
			},
			{
				condition: [
					'^' + siteDir + 'mail/message'
				],
				options: {
					width: 1080
				}
			},
			{
				condition: [
					'^' + siteDir + 'mail/config',
				],
				options: {
					width: 820,
				},
			},
			{
				condition: [
					'^' + siteDir + 'mail/(blacklist|signature|addressbook)'
				],
				options: {
					width: 1080,
					cacheable: false
				}
			},
			{
				condition: [
					'^' + siteDir + 'mail(\/|$)',
				],
				options: {
					//loading animation is assigned to this class
					contentClassName: "mail-loader-modifier",
					//replacing the standard loader with an empty element
					loader: this.mailLoader,
					cacheable: false,
					customLeftBoundary: 0,
				}
			},
			{
				condition: ['/company/personal/user/(\\d+)/social_services/$'],
				options: {
					width: 1100
				}
			},
			{
				condition: [
					new RegExp(siteDir + 'workgroups\\/group\\/([0-9]+)\\/calendar\\/slots'),
					new RegExp(siteDir + 'extranet\\/workgroups\\/group\\/([0-9]+)\\/calendar\\/slots'),
				],
				handler: function(event, link)
				{
					const groupId = parseInt(link.matches[1], 10);
					if (!groupId)
					{
						return;
					}

					event.preventDefault();

					BX.Runtime.loadExtension('calendar.sharing.interface')
						.then(({ GroupSharingController }) => {
							if (!GroupSharingController)
							{
								return;
							}

							GroupSharingController
								.getGroupSharing(groupId, event.target)
								.then((groupSharing) => groupSharing.openDialog())
								.catch((errors) => console.error(errors))
							;
						});
				},
			},
			{
				condition: [
					new RegExp('\\/workgroups\\/group\\/([0-9]+)\\/calendar\\/\\?EVENT_ID=([^&]+)(?:&EVENT_DATE=([^&]+))?', 'i'),
				],
				handler: function(event, link)
				{
					if (BX.Calendar && BX.Calendar.SliderLoader)
					{
						const slider = new BX.Calendar.SliderLoader(link.matches[2], {
							entryDateFrom: BX.parseDate(link.matches[3]),
							link: link.url,
							type: 'group',
							ownerId: link.matches[1],
						});
						slider.show();
						event.preventDefault();
					}
				},
			},
			{
				condition: [
					new RegExp('\\/company\\/personal\\/user\\/[0-9]+\\/calendar\\/\\?EVENT_ID=([^&]+)(?:&EVENT_DATE=([^&]+))?', 'i'),
					new RegExp('\\/contacts\\/personal\\/user\\/[0-9]+\\/calendar\\/\\?EVENT_ID=([^&]+)(?:&EVENT_DATE=([^&]+))?', 'i'),
					new RegExp('\\/calendar\\/\\?EVENT_ID=([^&]+)(?:&EVENT_DATE=([^&]+))?', 'i'),
				],
				handler: function(event, link)
				{
					if (BX.Calendar && BX.Calendar.SliderLoader)
					{
						const slider = new BX.Calendar.SliderLoader(link.matches[1], {
							entryDateFrom: BX.parseDate(link.matches[2]),
							link: link.url,
						});
						slider.show();
						event.preventDefault();
					}
				},
			},
			{
				condition: [
					new RegExp('\\/calendar\\/ics\\/\\?EVENT_ID=([^&]+)(?:&EVENT_DATE=([^&]+))?', 'i'),
				],
				handler: function(event, link)
				{
					const eventId = Number(link.matches[1]);
					if (!eventId)
					{
						return;
					}
					event.preventDefault();
					BX.Runtime.loadExtension('calendar.util').then(() =>
						BX.Calendar.Util.downloadIcsFileByEventId(eventId)
					);
				},
			},
			{
				condition: [
					new RegExp('\\/workgroups\\/group\\/[0-9]+\\/calendar\\/', 'i'),
				],
				options: {
					cacheable: false,
					customLeftBoundary: 0,
				},
			},
			{
				condition: ['/configs/userfield_list.php'],
				options: {
					cacheable: false,
					allowChangeHistory: false,
				}
			},
			{
				condition: ['/configs/userfield.php'],
				options: {
					width: 900,
					cacheable: false,
					allowChangeHistory: false,
				}
			},
			{
				condition: [
					"/shop/catalog/(\\d+)/product/(\\d+)/store_amount/",
					"/crm/catalog/(\\d+)/product/(\\d+)/store_amount/"
				],
				options: {
					cacheable: false,
					label: {
						text: BX.message('INTRANET_BINDINGS_PRODUCT_STORE_AMOUNT')
					}
				}
			},
			{
				condition: [
					"/shop/catalog/(\\d+)/product/(\\d+)/variation/(\\d+)/",
					"/crm/catalog/(\\d+)/product/(\\d+)/variation/(\\d+)/"
				],
				options: {
					cacheable: false,
					label: {
						text: BX.message('INTRANET_BINDINGS_VARIATION')
					}
				}
			},
			{
				condition: [
					"/shop/catalog/(\\d+)/product/(\\d+)/",
					"/crm/catalog/(\\d+)/product/(\\d+)/"
				],
				options: {
					cacheable: false,
					label: {
						text: BX.message('INTRANET_BINDINGS_PRODUCT')
					}
				}
			},
			{
				condition: [
					"/shop/import/instagram/edit/"
				],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					width: 700
				}
			},
			{
				condition: [
					"/shop/import/instagram/feedback/"
				],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					width: 580
				}
			},
			{
				condition: [
					"/shop/import/instagram/"
				],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					width: 1028
				}
			},
			{
				condition: [
					"/bitrix/services/main/ajax.php\\?action=disk.controller.documentservice.goToPreview"
				],
				options: {
					cacheable: false,
					width: '100%',
					customLeftBoundary: 30,
					allowChangeHistory: false,
					data: {
						documentEditor: true
					}
				}
			},
			{
				condition: [ new RegExp("/shop/orders/payment/details/[0-9]+/", "i") ],
				loader: "intranet:crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						text: BX.message("INTRANET_BINDINGS_PAYMENT"),
					},
					width: detectCrmSliderWidth(),
				},
			},
			{
				condition: [ new RegExp("/shop/orders/shipment/details/[0-9]+/", "i") ],
				loader: "intranet:crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						text: BX.message("INTRANET_BINDINGS_SHIPMENT"),
					},
					width: detectCrmSliderWidth(),
				},
			},
			{
				condition: [/\/contact_center\/lines_edit\/\?(.+)SIDE=Y/i,],
				// loader: "/bitrix/components/bitrix/imopenlines.lines.edit/templates/.default/images/imopenlines-view.svg",
				loader: "intranet:imopenlines-view",
				options: {
					width: 996,
					allowChangeHistory: false,
					cacheable: false
				}
			},
			{
				condition: [ new RegExp("/telephony/edit.php\\?ID=[0-9]+") ],
				options: {
					cacheable: false,
					allowChangeHistory: false
				}
			},
			{
				condition: [ '/bitrix/components/bitrix/bitrix24.license.scan/' ],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					width: 1195,
				}
			},
			{
				condition: ['/settings/license_all.php'],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					width: 1250,
					customRightBoundary: 0,
				},
			},
			{
				condition: ['/settings/order/make.php'],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					width: 1586,
					customRightBoundary: 0,
				},
			},
			{
				condition: [
					new RegExp("/settings/configs/\\?analyticContext=[a-z]+", 'i'),
					'/settings/configs/index.php',
					new RegExp("/settings/configs/\\?page=[a-z]+", 'i'),
					new RegExp("/configs/\\?analyticContext=[a-z]+", 'i'),
					'/configs/index.php',
					new RegExp("/configs/\\?page=[a-z]+", 'i'),
				],
				options: {
					loader: 'intranet:slider-settings',
					width: 1034
				}
			},
			{
				condition: [
					new RegExp('/sign/link/member/(\\d+)/', 'i'),
				],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					width: 900,
				},
				handler(event, link)
				{
					BX.SidePanel.Instance.open('sign:stub:sign-link', {
						width: 900,
						cacheable: false,
						allowCrossOrigin: true,
						allowCrossDomain: true,
						allowChangeHistory: false,
						newWindowUrl: link.url,
						copyLinkLabel: true,
						newWindowLabel: true,
						loader: '/bitrix/js/intranet/sidepanel/bindings/images/sign_mask.svg',
						label: {
							text: BX.message('INTRANET_BINDINGS_SMART_DOCUMENT_MSGVER_1'),
							bgColor: '#C48300',
						},
						async contentCallback(slider) {
							return BX.Runtime.loadExtension('sign.v2.b2e.sign-link').then((exports) => {
								return (new exports.SignLink({ memberId: link.matches[1], slider }))
									.render()
								;
							});
						},
					});

					event.preventDefault();
				},
			},
			{
				condition: [ '/bitrix/components/bitrix/bitrix24.license.scan/' ],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					width: 1195,
				}
			},
			{
				condition: [ new RegExp("/company/personal/user/[0-9]+/common_security/\\?page=auth") ],
				options: {
					width: 1100,
				}
			},
			{
				condition: [ new RegExp("/check-in/statistics/") ],
				options: {
					cacheable: false,
					allowChangeHistory: false,
				},
				handler: function(event, link)
				{
					event.preventDefault();
					BX.Runtime.loadExtension('stafftrack.user-statistics-link').then((exports) => {
						const userStatisticsLink = exports.UserStatisticsLink;
						if (userStatisticsLink)
						{
							return (new userStatisticsLink()).show();
						}
					})
				},
			},
			{
				condition: [
					/^\/bitrix\/components\/bitrix\/sign.document.list\/slider.php\?type=document&entity_id=\d+&apply_filter=Y/,
				],
				options: {
					cacheable: false,
					width: 900,
					allowChangeHistory: false,
				},
				handler(event, link)
				{
					BX.SidePanel.Instance.open(link.url);
					event.stopPropagation();
					event.preventDefault();
				},
			},
			{
				condition: [
					new RegExp(siteDir + "call/[0-9]+/($|\\?)", "i"),
					new RegExp("/call/($|\\?)", "i"),
					new RegExp("/call/detail/[0-9]+", "i"),
					new RegExp("/extranet/call/detail/[0-9]+", "i"),
				],
				options: {
					width: 1100,
					copyLinkLabel: true,
				},
				handler: function(event, link)
				{
					BX.SidePanel.Instance.open(
						link.url,
						{
							cacheable: false,
							allowChangeHistory: false,
							//contentClassName: "bitrix24-profile-slider-content",
							loader: 'default-loader',
							width: 1100,
							copyLinkLabel: true,
						}
					);
					event.stopPropagation();
					event.preventDefault();
				}
			},
			{
				condition: ['/booking/detail/(\\d+)/'],
				options: {
					cacheable: false,
					width: 450,
					allowChangeHistory: false,
				},
				handler(event, link)
				{
					BX.SidePanel.Instance.open(link.url);
					event.stopPropagation();
					event.preventDefault();
				},
			},
			{
				condition: [ new RegExp("/bitrix/components/bitrix/voting\.attached\.result/slider\.php", "i") ],
				options: {
					cacheable: false,
					width: 480,
				},
			},
			{
				condition: [ '/vote-result/' ],
				options: {
					cacheable: false,
					width: 480,
					copyLinkLabel: true,
				},
			},
			{
				condition: [
					new RegExp('(?<url>/bitrix/components/bitrix/bizproc.workflow.timeline.slider/index.php\\?workflowId=(?<workflowId>[a-z0-9.]+))', 'i')
				],
				options: {
					width: 950,
					allowChangeHistory: false,
					cacheable: false,
					loader: '/bitrix/js/bizproc/workflow/timeline/img/skeleton.svg',
					printable: true,
				},
				minimizeOptions: (link) => {
					return {
						entityType: 'bizproc:timeline',
						entityId: link.matches.groups.workflowId.replace('.', '_'),
						entityName: BX.message('INTRANET_BINDINGS_PROTOCOL'),
						url: link.matches.groups.url,
					};
				},
			},
			{
				condition: [new RegExp('(?<url>/company/personal/bizproc/(?<id>[a-z0-9.]+)/)', 'i')],
				minimizeOptions: (link) => {
					return {
						entityType: 'bizproc:info',
						entityId: link.matches.groups.id.replace('.', '_'),
						entityName: BX.message('INTRANET_BINDINGS_ASSIGNMENT'),
						url: link.matches.groups.url,
					};
				},
				loader: 'bizproc:workflow-info',
				options: {
					width: detectCrmSliderWidth(),
					cacheable: false,
				},
			},
			{
				condition: ['/task/comments/(\\d+)/'],
				options: {
					cacheable: true,
					width: 800,
					allowChangeHistory: false,
				},
			},
		]
	});

	const line = BX.UI.System.Line;
	const circle = BX.UI.System.Circle;

	const taskSkeleton = () => BX.Tag.render`
		<div class="task-skeleton --full">
			<div class="--main">
				<div style="padding: 28px 24px">
					<div class="--full-title --row">
						${line(350, 18)}
						${circle()}
					</div>
					<div class="--full-description">${line(260, 18)}</div>
					${line(null, 84)}
					<div style="margin: 12px 0">${line(null, 84)}</div>
					${line(null, 84)}
					<div class="--full-chips --row">
						${line(88, 32)}
						${line(88, 32)}
						${line(88, 32)}
					</div>
				</div>
				<div class="--row --footer">
					${line(85, 38)}
					<div class="--more">${line(38, 38)}</div>
					${line(131, 22)}
				</div>
			</div>
			<div class="--chat">
				<div class="--chat-title --row">
					${circle(40)}
					<div class="--chat-info">
						${line(110, 12)}
						${line(75, 10)}
					</div>
					${circle()}
					<div style="margin-left: 8px">${circle()}</div>
				</div>
				<div class="--chat-bg">
					<div class="--textarea --row">
						${line(null, 47)}
						${circle(44)}
					</div>
				</div>
			</div>
		</div>
	`;

})();
