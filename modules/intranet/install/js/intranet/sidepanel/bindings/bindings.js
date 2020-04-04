(function() {

	var iframeMode = window !== window.top;
	var search = window.location.search;
	var sliderMode = search.indexOf("IFRAME=") !== -1 || search.indexOf("IFRAME%3D") !== -1;

	if (iframeMode && sliderMode)
	{
		return;
	}

	var siteDir = ('/' + (BX.message.SITE_DIR || '/').replace(/[\\*+?.()|[\]{}]/g, '\\$&') + '/').replace(/\/+/g, '/');

	BX.SidePanel.Instance.bindAnchors({
		rules: [
			{
				condition: [
					/^(http|https):\/\/([^\/]+)\/knowledge/i
				],
				handler: function(event, link) {

					var sliderMode = link.anchor.href.indexOf('IFRAME=') !== -1 ||
									link.anchor.href.indexOf('IFRAME%3D') !== -1;

					if (!sliderMode)
					{
						event.preventDefault();
						BX.SidePanel.Instance.open(link.url);
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
					// '/company/personal/user/(\\d+)/tasks/report/construct/(\\d+)/create/',
				],
				loader: 'task-view-loader',
				stopParameters: [
					'PAGEN_(\\d+)',
				]
			},
			{
				condition: [
					'/company/personal/user/(\\d+)/tasks/task/view/(\\d+)/',
					'/company/personal/user/(\\d+)/tasks/templates/template/view/(\\d+)/',
					// '/company/personal/user/(\\d+)/tasks/report/view/(\\d+)/',
					'/workgroups/group/(\\d+)/tasks/task/view/(\\d+)/',
					'/extranet/contacts/personal/user/(\\d+)/tasks/task/view/(\\d+)/'
				],
				loader: 'task-view-loader',
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
					'/company/personal/user/(\\d+)/tasks/task/edit/0/',
					'/company/personal/user/(\\d+)/tasks/templates/template/edit/0/',
					'/workgroups/group/(\\d+)/tasks/task/edit/0/',
					'/extranet/contacts/personal/user/(\\d+)/tasks/task/edit/0/'
				],
				loader: 'task-new-loader',
				options: {
					label: {
						text: BX.message("INTRANET_BINDINGS_TASK"),
						bgColor: "#2FC6F6"
					}
				}
			},
			{
				condition: [
					'/company/personal/user/(\\d+)/tasks/task/edit/(\\d+)/',
					'/company/personal/user/(\\d+)/tasks/templates/template/edit/(\\d+)/',
					'/workgroups/group/(\\d+)/tasks/task/edit/(\\d+)/',
					'/extranet/contacts/personal/user/(\\d+)/tasks/task/edit/(\\d+)/'
				],
				loader: 'task-edit-loader',
				options: {
					label: {
						text: BX.message("INTRANET_BINDINGS_TASK"),
						bgColor: "#2FC6F6"
					}
				}
			},
			{
				condition: ['/crm/button/edit/(\\d+)/'],
				loader: 'crm-button-view-loader'
			},
			{
				condition: ['/crm/webform/edit/(\\d+)/'],
				loader: 'crm-webform-view-loader'
			},
			{
				condition: [
					new RegExp("^/marketplace\/hook/"),
				],
				options: {
					customLeftBoundary: 0,
					loader: "rest:marketplace"
				}
			},
			{
				condition: [
					new RegExp("/marketplace\/configuration/"),
				],
				options: {
					width: 940,
					cacheable: false
				}
			},
			{
				condition: [
					new RegExp("^/marketplace\/installed/"),
				],
				options: {
					customLeftBoundary: 0,
					loader: "rest:marketplace2"
				}
			},
			{
				condition: [
					new RegExp("^\\/marketplace\\/.*?((\\?|\\&)(tag|placement))"),
				],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					customLeftBoundary: 0,
					loader: "rest:marketplace1"
				}
			},
			{
				condition: [
					new RegExp("^/marketplace\/($|\\?)"),
				],
				options: {
					cacheable: false,
					customLeftBoundary: 0,
					loader: "rest:marketplace1"
				}
			},
			{
				condition: [
					new RegExp("^/marketplace\/"),
				],
				options: {
					customLeftBoundary: 0,
				}
			},
			{
				condition: [
					/\/online\/\?(IM_DIALOG|IM_HISTORY)=(.+)/i
				],
				handler: function(event, link)
				{
					if (!window.BXIM)
					{
						return;
					}

					var type = link.matches[1];
					var id = decodeURI(link.matches[2]);

					if (type === "IM_HISTORY")
					{
						BXIM.openHistory(id);
					}
					else
					{
						BXIM.openMessenger(id);
					}

					event.preventDefault();
				}
			},
			{
				condition: [
					/^(http|https):\/\/helpdesk\.bitrix24\.([a-zA-Z\.]{2,})\/open\/([a-zA-Z0-9_|]+)/i
				],
				allowCrossDomain: true,
				handler: function(event, link)
				{
					if (BX.desktop)
					{
						return true;
					}

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
					event.preventDefault();
				}
			},
			{
				condition: [ new RegExp("/shop/orders/details/[0-9]+/", "i") ],
				loader: "crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						text: BX.message("INTRANET_BINDINGS_ORDER"),
					}
				}
			},
			{
				condition: [ new RegExp("/crm/lead/details/[0-9]+/", "i") ],
				loader: "crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						text: BX.message("INTRANET_BINDINGS_LEAD"),
						bgColor: "#55D0E0"
					}
				}
			},
			{
				condition: [ new RegExp("/crm/contact/details/[0-9]+/", "i") ],
				loader: "crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						text: BX.message("INTRANET_BINDINGS_CONTACT"),
						bgColor: "#7BD500"
					}
				}
			},
			{
				condition: [ new RegExp("/crm/company/details/[0-9]+/", "i") ],
				loader: "crm-entity-details-loader",
				options: {
					label: {
						text: BX.message("INTRANET_BINDINGS_COMPANY"),
						bgColor: "#F7A700"
					}
				}
			},
			{
				condition: [ new RegExp("/crm/deal/details/[0-9]+/", "i") ],
				loader: "crm-entity-details-loader",
				options: {
					cacheable: false,
					label: {
						text: BX.message("INTRANET_BINDINGS_DEAL"),
						bgColor: "#9985DD"
					}
				}
			},
			{
				condition: [
					new RegExp("/report/analytics"),
					new RegExp("/report/analytics/\\?analyticBoardKey=(\\w+)")
				],
				options: {
					cacheable: false,
					customLeftBoundary: 0,
					loader: "report:analytics"
				}
			},
			{
				condition: [ new RegExp("/bitrix/tools/disk/focus.php\\?.*(inSidePanel).*action=(openFileDetail)", "i") ]
			},
			{
				condition: [
					new RegExp(siteDir + "company/personal/user/[0-9]+/($|\\?)", "i"),
					new RegExp(siteDir + "contacts/personal/user/[0-9]+/($|\\?)", "i")
				],
				options: {
					contentClassName: "bitrix24-profile-slider-content",
					loader: "intranet:profile",
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
					new RegExp(siteDir + "contacts/personal/user/[0-9]+/edit/($|\\?)", "i")
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
							loader: "intranet:profile",
							width: 1100
						}
					);
				}
			},
			{
				condition: [ new RegExp("/saleshub/orders/order/\\?.*") ],
				loader: "crm-entity-details-loader"
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
					'^' + siteDir + 'mail/(blacklist|signature|config|message)'
				],
				options: {
					width: 1080
				}
			},
			{
				condition: [
					'^' + siteDir + 'mail/',
				],
				options: {
					cacheable: false
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
					new RegExp(siteDir + "company/personal/user/[0-9]+/calendar/\\?EVENT_ID=([a-zA-Z0-9_|]+)", "i")
				],
				handler: function(event, link)
				{
					if (BX.Calendar && BX.Calendar.SliderLoader)
					{
						var slider = new BX.Calendar.SliderLoader(link.matches[1]);
						slider.show();
						event.preventDefault();
					}
				}
			},
		]
	});

})();
