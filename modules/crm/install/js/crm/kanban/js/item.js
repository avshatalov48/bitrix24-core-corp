(function() {

"use strict";

BX.namespace("BX.CRM.Kanban");

/**
 *
 * @param options
 * @extends {BX.Kanban.Item}
 * @constructor
 */
BX.CRM.Kanban.Item = function(options)
{
	/** @var {Element} **/
	this.container = null;
	this.timer = null;
	this.popupTooltip = null;
	this.plannerCurrent = null;
	this.fieldsWrapper = null;
	this.badgesWrapper = null;
	this.clientName = null;
	this.clientNameItems = [];
	this.useAnimation = false;
	this.isAnimationInProgress = false;
	this.changedInPullRequest = false;
	this.notChangeTotal = false;
	this.itemActivityZeroClass = 'crm-kanban-item-activity-zero';
	this.activityAddingPopup = null;

	BX.Kanban.Item.apply(this, arguments);
};

BX.CRM.Kanban.Item.prototype = {
	__proto__: BX.Kanban.Item.prototype,
	constructor: BX.CRM.Kanban.Item,
	lastPosition: {
		columnId: null,
		targetId: null
	},
	checked: false,

	setOptions: function(options)
	{
		if (!options)
		{
			return;
		}

		BX.Kanban.Item.prototype.setOptions.call(this, options);

		this.useAnimation = BX.type.isBoolean(options.useAnimation) ? options.useAnimation : false;
	},

	/**
	 * Add <span> for last word in title.
	 * @param {String} fullTitle
	 * @returns {String}
	 */
	clipTitle: function (fullTitle)
	{
		var title = fullTitle;
		var arrTitle = title.split(" ");
		var lastWord = "<span>" + arrTitle[arrTitle.length - 1] + "</span>";

		arrTitle.splice(arrTitle.length - 1);

		title = arrTitle.join(" ") + " " + lastWord;

		return title;
	},

	/**
	 * Store key in current data.
	 * @param {String} key
	 * @param {String} val
	 * @returns {void}
	 */
	setDataKey: function(key, val)
	{
		var data = this.getData();
		data[key] = val;
		this.setData(data);
	},

	/**
	 * Get key value from current data.
	 * @param {String} key
	 * @returns {String}
	 */
	getDataKey: function(key)
	{
		var data = this.getData();
		return data[key];
	},

	/**
	 * Add or remove class for element.
	 * @param {DOMNode} el
	 * @param {String} className
	 * @param {Boolean} mode
	 * @returns {void}
	 */
	switchClass: function(el, className, mode)
	{
		if (mode)
		{
			BX.addClass(el, className);
		}
		else
		{
			BX.removeClass(el, className);
		}
	},

	/**
	 * Show or hide element.
	 * @param {DOMNode} el
	 * @param {Boolean} mode
	 * @returns {void}
	 */
	switchVisible: function(el, mode)
	{
		if (mode)
		{
			el.style.display = "";
		}
		else
		{
			BX.hide(el);
		}
	},

	/**
	 * Get last position of item.
	 * @returns {void}
	 */
	getLastPosition: function()
	{
		return this.lastPosition;
	},


	/**
	 * Set last position of otem.
	 * @returns {void}
	 */
	setLastPosition: function()
	{
		var column = this.getColumn();
		var sibling = column.getNextItemSibling(this);

		this.lastPosition = {
			columnId: column.getId(),
			targetId: sibling ? sibling.getId() : 0
		};
	},

	getBodyContainer: function()
	{
		if (!this.layout.bodyContainer)
		{
			this.layout.bodyContainer = BX.create("div", {
				attrs: {
					className: "main-kanban-item-wrapper"
				}
			});
		}

		return this.layout.bodyContainer;
	},

	/**
	 * Return full node for item.
	 * @returns {Element}
	 */
	render: function()
	{
		var layout = null;
		var data = this.getData();
		var gridData = this.getGridData();

		if (data.special_type === "import")
		{
			layout = this.getStartLayout();
			BX.onCustomEvent("Crm.Kanban.Grid:onSpecialItemDraw", [
				this, layout
			]);
			this.grid.ccItem = this;
			this.getBodyContainer().style.background = "none";
			return layout;
		}
		else if (data.special_type === "rest")
		{
			layout = this.getIndustrySolutionsLayout();
			BX.onCustomEvent("Crm.Kanban.Grid:onSpecialItemDraw", [
				this, layout
			]);
			this.grid.restItem = this;
			return layout;
		}

		if (!this.container)
		{
			this.createLayout();
		}

		var column = this.getColumn();
		var color = column.getColor();
		var rgb = BX.util.hex2rgb(color);
		var rgba = "rgba(" + rgb.r + "," + rgb.g + "," + rgb.b + "," + ".7)";

		// border color
		this.container.style.setProperty("--crm-kanban-item-color", rgba);

		// item link
		const isAutomationDebugItem = data['isAutomationDebugItem'];
		const additionalLabel =
			isAutomationDebugItem
				? '<span class="crm-kanban-debug-item-label">' + BX.message('CRM_KANBAN_ITEM_DEBUG_TITLE_MSGVER_1') + ' </span>'
				: ''
		;
		this.link.innerHTML = additionalLabel + this.clipTitle(data.name);

		this.link.setAttribute(
			"href",
			data.link
		);
		// price
		if (this.totalPrice)
		{
			this.totalPrice.innerHTML = data.price_formatted;
		}
		// date
		this.date.textContent = data.date;
		// contact / company name
		this.clientNameItems = [];
		if (
			data.contactId &&
			data.contactName &&
			BX.util.in_array("CLIENT", gridData.customFields)
		)
		{
			this.clientNameItems.push(data.contactTooltip);
		}

		if (
			data.companyId &&
			data.companyName &&
			BX.util.in_array("CLIENT", gridData.customFields)
		)
		{
			this.clientNameItems.push(data.companyTooltip);
		}

		if (this.clientNameItems.length)
		{
			this.clientName.innerHTML = this.clientNameItems.join('<br>');
			this.switchVisible(this.clientName, true);
		}
		else
		{
			this.switchVisible(this.clientName, false);
		}

		// planner
		if (this.planner)
		{
			this.switchPlanner();
		}
		// phone/mail/chat exist or not
		var contactTypes = ["Phone", "Email", "Im"];
		for (var i = 0, c = contactTypes.length; i < c; i++)
		{
			var type = contactTypes[i];
			var disabledClass = "crm-kanban-item-contact-" + type.toLowerCase() + "-disabled";
			BX.unbindAll(this["contact" + type]);
			if (data[type.toLowerCase()])
			{
				BX.bind(this["contact" + type], "click", BX.delegate(this.clickContact, this));
				this.switchClass(this["contact" + type], disabledClass, false);
			}
			else
			{
				BX.bind(this["contact" + type], "mouseover", BX.delegate(function()
				{
					var type = BX.data(BX.proxy_context, "type");
					this.showTooltip(
						BX.message("CRM_KANBAN_NO_" + type.toUpperCase()),
						BX.proxy_context
					);
				}, this));
				BX.bind(this["contact" + type], "mouseout", BX.delegate(this.hideTooltip, this));
				this.switchClass(this["contact" + type], disabledClass, true);
			}
		}

		BX.Dom.clean(this.lastActivityTime);
		BX.Dom.clean(this.lastActivityBy);
		const lastActivity = data.lastActivity;
		if (BX.Type.isPlainObject(lastActivity) && BX.CRM.Kanban.Restriction.Instance.isLastActivityInfoInKanbanItemAvailable())
		{
			const timestamp = BX.Text.toInteger(lastActivity.timestamp);
			if (timestamp > 0)
			{
				const userNow = BX.Crm.DateTime.Factory.getUserNow();
				const userNowTimestamp = Math.round(userNow.getTime() / 1000);

				const ago = (
					userNowTimestamp - timestamp <= 60
						? BX.Text.encode(BX.Loc.getMessage('CRM_KANBAN_JUST_NOW'))
						: this.getFormattedLastActiveDateTime(timestamp, userNowTimestamp)
				);

				BX.Dom.append(
					BX.Tag.render`<span class="crm-kanban-item-last-activity-time-ago">${ago}</span>`,
					this.lastActivityTime,
				);
			}

			const lastActivityBy = lastActivity.user;
			if (BX.Type.isPlainObject(lastActivityBy))
			{
				let pictureStyle = '';
				if (BX.Type.isStringFilled(lastActivityBy.picture))
				{
					const currentUrl = new BX.Uri(window.location.href);
					const pictureUrl = new BX.Uri(lastActivityBy.picture);

					if (
						//relative url
						pictureUrl.getHost() === ''
						|| currentUrl.getHost() === pictureUrl.getHost()
					)
					{
						pictureStyle = `style="background-image: url(${encodeURI(BX.Text.encode(pictureUrl.toString()))})"`;
					}
				}

				let href = '#';
				if (BX.Type.isStringFilled(lastActivityBy.link) && lastActivityBy.link.startsWith('/'))
				{
					href = lastActivityBy.link;
				}

				BX.Dom.append(
					BX.Tag.render`<a
 						class="crm-kanban-item-last-activity-by-userpic"
 						href="${BX.Text.encode(href)}"
 						bx-tooltip-user-id="${BX.Text.toInteger(lastActivityBy.id)}"
						${pictureStyle}></a>`
					,
					this.lastActivityBy
				);
			}
		}

		if (this.needRenderFields())
		{
			this.fieldsWrapper.innerHTML = null;
			this.layoutFields();
		}

		BX.Dom.clean(this.badgesWrapper);
		this.layoutBadges();

		layout = this.container;

		return layout;
	},

	getFormattedLastActiveDateTime: function(timestamp, userNow)
	{
		const { Format } = BX.Crm.DateTime.Dictionary;
		const isCurrentYear = (new Date(timestamp * 1000)).getFullYear() === (new Date()).getFullYear();
		const defaultFormat = (
			isCurrentYear
				? Format.DAY_SHORT_MONTH_FORMAT
				: Format.MEDIUM_DATE_FORMAT
		);

		let shortTimeFormat = Format.SHORT_TIME_FORMAT;
		shortTimeFormat = shortTimeFormat.replace(/\b(a)\b/, 'A'); // for uppercase AM/PM markers: h:i a => h:i A

		const formattedDateTime = BX.Main.DateTimeFormat.format(
			[
				['i', 'idiff'],
				['yesterday', 'yesterday, ' + shortTimeFormat],
				['today', 'today, ' + shortTimeFormat],
				['-', defaultFormat],
			],
			timestamp,
			userNow,
		);

		return formattedDateTime
			.replaceAll('\\', '')
			.replace(/(^|\s)(.)/g, firstLetter => firstLetter.toLocaleUpperCase())
		;
	},

	needRenderFields: function()
	{
		var wrapperCreated = !!this.fieldsWrapper;
		var itemHasFields = !!this.getData().fields;

		return wrapperCreated && itemHasFields;
	},

	getItemFields: function()
	{
		if(!this.fieldsWrapper)
		{
			var gridData = this.getGridData();
			this.fieldsWrapper = BX.create("div", {
				props: {
					className: "crm-kanban-item-fields"
				}
			});

			if (this.getGrid().getTypeInfoParam('useRequiredVisibleFields'))
			{
				this.switchVisible(this.link, true);
				this.switchVisible(this.date, true);
				this.switchVisible(this.clientName, true);
				if (this.total)
				{
					this.switchVisible(this.total, true);
				}
				return this.fieldsWrapper;
			}
			this.layoutFields();
		}

		return this.fieldsWrapper;
	},

	layoutFields: function()
	{
		if (this.fieldsWrapper)
		{
			for (var i = 0; i < this.data.fields.length; i++)
			{
				// don't show main fields
				var code = this.data.fields[i].code;
				var type = this.data.fields[i].hasOwnProperty('type') ? this.data.fields[i].type : 'string';
				if (code === "TITLE")
				{
					this.switchVisible(this.link, true);
					continue;
				}
				if (code === "DATE_CREATE")
				{
					this.switchVisible(this.date, true);
					continue;
				}
				if (code === "CLIENT")
				{
					this.switchVisible(this.clientName, true);
					continue;
				}
				if (code === "OPPORTUNITY" || code === "PRICE")
				{
					if (this.total)
					{
						this.switchVisible(this.total, true);
					}
					continue;
				}

				var params = {
					props: {
						className: "crm-kanban-item-fields-item-value"
					}
				};

				if (type === 'user')
				{
					if (this.data.fields[i].html === true)
					{
						if (BX.Type.isPlainObject(this.data.fields[i].value))
						{
							var itemUserPic = '';
							var itemUserName = '';

							if (this.data.fields[i].value.link === '')
							{
								itemUserPic = "<span class=\"crm-kanban-item-fields-item-value-userpic\"></span>";
								itemUserName = "<span class=\"crm-kanban-item-fields-item-value-name\">" + this.data.fields[i].value.title + "</span>";
							}
							else
							{
								var userPic = '';
								if (this.data.fields[i].value.picture)
								{
									userPic = " style=\"background-image: url(" + encodeURI(this.data.fields[i].value.picture) + ")\"";
								}
								itemUserPic = "<a class=\"crm-kanban-item-fields-item-value-userpic\" href=\"" + this.data.fields[i].value.link + "\"" + userPic + "></a>";
								itemUserName = "<a class=\"crm-kanban-item-fields-item-value-name\" href=\"" + this.data.fields[i].value.link + "\">" + this.data.fields[i].value.title + "</a>";
							}
							params['html'] = "<div class=\"crm-kanban-item-fields-item-value-user\">"
								+ itemUserPic
								+ itemUserName
								+ "</div>";
						}
						else
						{
							params['html'] = BX.Type.isArray(this.data.fields[i].value)
								? this.data.fields[i].value.join(', ')
								: this.data.fields[i].value
							;
						}
					}
					else
					{
						params['text'] = this.getMessage('noname');
					}
				}
				else if(
					this.data.fields[i].type === "money"
					|| this.data.fields[i].html === true
				)
				{
					var delimiter = this.data.fields[i].valueDelimiter ? this.data.fields[i].valueDelimiter : '<br>';
					params['html'] = BX.Type.isArray(this.data.fields[i].value)
						? this.data.fields[i].value.join(delimiter)
						: this.data.fields[i].value
					;

					if (params['html'].includes('<b>'))
					{
						params.props.className = params.props.className + ' --normal-weight';
					}
				}
				else
				{
					params['text'] = BX.Type.isArray(this.data.fields[i].value)
						? this.data.fields[i].value.join(', ')
						: this.data.fields[i].value
					;
				}

				var titleNodes = [];
				if (this.data.fields[i].icon && this.data.fields[i].icon.url && this.data.fields[i].icon.url.length)
				{
					titleNodes.push(
						BX.create(
							'div',
							{
								props: {
									className: 'crm-kanban-item-fields-item-title-icon'
								},
								children: [
									BX.create(
										'img',
										{
											props: {
												src: this.data.fields[i].icon.url,
												title: this.data.fields[i].icon.title ? this.data.fields[i].icon.title : ''
											}
										}
									)
								]
							}
						)
					);
				}
				titleNodes.push(
					BX.create(
						'div',
						{
							props: {
								className: 'crm-kanban-item-fields-item-title-text'
							},
							html: this.data.fields[i].title
						}
					)
				);
				this.fieldsWrapper.appendChild(BX.create("div", {
					props: {
						className: "crm-kanban-item-fields-item"
					},
					children: [
						BX.create("div", {
							props: {
								className: "crm-kanban-item-fields-item-title"
							},
							children: titleNodes
						}),
						BX.create("div", params)
					]
				}))
			}
		}
	},

	layoutBadges: function()
	{
		for (let i = 0; i < this.data.badges.length; i++)
		{
			const badge = this.data.badges[i];

			const badgeValueClass = 'crm-kanban-item-fields-item-value crm-kanban-item-status';
			const badgeValueStyle = `
				background-color: ${badge.backgroundColor};
				border-color: ${badge.backgroundColor};
				color: ${badge.textColor};
			`;

			const item = BX.Tag.render`
				<div class="crm-kanban-item-fields-item">
					<div class="crm-kanban-item-fields-item-title">
						<div class="crm-kanban-item-fields-item-title-text">${badge.fieldName}</div>
					</div>
					<div class="${badgeValueClass}" style="${badgeValueStyle}">${badge.textValue}</div>
				</div>
			`;

			BX.Dom.append(item, this.badgesWrapper);
		}
	},

	getItemFieldsEditor: function()
	{
		var gridData = this.getGridData();
		var editorContainer = BX.create("div", {});
		var fieldsWrapper = BX.create("div", {
			props: {
				className: "crm-kanban-item-fields"
			},
			children: [
				editorContainer
			]
		});
		var serviceUrl = BX.Crm.PartialEditorDialog.entityEditorUrls[
			gridData.entityType
		];

		if (this.data.fieldsEditor)
		{
			var model = BX.Crm.EntityEditorModelFactory.create(
				"kanban_model",
				"",
				{data: this.data.fieldsEditor}
			);

			BX.Crm.EntityEditor.create(
				"kanban_" + gridData.entityType.toLowerCase() + "_" + this.getId(),
				{
					containerId: editorContainer,
					serviceUrl: serviceUrl,
					entityTypeId: gridData.entityTypeInt,
					entityId: this.getId(),
					scheme: gridData.schemeInline,
					model: model,
					initialMode: "view",
					enableModeToggle: true,
					enableToolPanel: true,
					enableRequiredUserFieldCheck: true,
					userFieldManager: gridData.userFieldManagerInline
				}
			);
		}

		return fieldsWrapper;
	},

	/**
	 * Get close icon for demo-block.
	 * @return {Element}
	 */
	getCloseStartLayout: function()
	{
		return BX.create("div", {
			props: {
				className: "crm-kanban-item-contact-center-close"
			},
			events: {
				click: function(e)
				{
					this.grid.toggleCC();
					e.stopPropagation(e);
				}.bind(this)
			}
		});
	},

	/**
	 * Gets REST block close button.
	 * @return {Element}
	 */
	getCloseRestLayout: function()
	{
		return BX.create("div", {
			props: {
				className: "crm-kanban-item-industry-close"
			},
			events: {
				click: function(e)
				{
					this.grid.toggleRest();
					e.stopPropagation(e);
				}.bind(this)
			}
		})
	},

	/**
	 * Gets demo block for contact center.
	 * @returns {Element}
	 */
	getStartLayout: function()
	{
		this.getCloseStartLayout();

		var gridData = this.getGridData();

		return BX.create("div", {
			props: {
				className: "crm-kanban-item-contact-center"
			},
			children: [
				BX.create("div", {
					dataset: {
						url: "contact_center"
					},
					props: {
						className: "crm-kanban-sidepanel"
					},
					children: [
						this.getCloseStartLayout(),
						BX.create("div", {
							props: {
								className: "crm-kanban-item-contact-center-title"
							},
							children: [
								BX.create("div", {
									props: {
										className: "crm-kanban-item-contact-center-title-item"
									},
									text: BX.message("CRM_KANBAN_EMPTY_CARD_CT_TITLE")
								}),
								BX.create("div", {
									props: {
										className: "crm-kanban-item-contact-center-title-item"
									},
									text: BX.message("CRM_KANBAN_EMPTY_CARD_CT_TEXT" + gridData.entityType)
								})
							]
						}),
						BX.create("div", {
							props: {
								className: "crm-kanban-item-contact-center-action"
							},
							children: [
								BX.create("div", {
									props: {
										className: "crm-kanban-item-contact-center-action-section"
									},
									children: [
										BX.create("a", {
											attrs: {
												href: "#"
											},
											props: {
												className: "crm-kanban-sidepanel crm-kanban-item-contact-center-action-item " +
															"crm-kanban-item-contact-center-action-item-chat"
											},
											text: BX.message("CRM_KANBAN_EMPTY_CARD_CT_CHAT"),
											dataset: {
												url: "ol_chat"
											}
										}),
										BX.create("a", {
											attrs: {
												href: "#"
											},
											props: {
												className: "crm-kanban-sidepanel crm-kanban-item-contact-center-action-item " +
															"crm-kanban-item-contact-center-action-item-crm-forms"
											},
											text: BX.message("CRM_KANBAN_EMPTY_CARD_CT_FORMS"),
											dataset: {
												url: "ol_forms"
											}
										}),
										BX.create("a", {
											attrs: {
												href: "#"
											},
											props: {
												className: "crm-kanban-sidepanel crm-kanban-item-contact-center-action-item " +
															"crm-kanban-item-contact-center-action-item-viber"
											},
											text: "Viber",
											dataset: {
												url: "ol_viber"
											}
										})
									]
								}),
								BX.create("div", {
									props: {
										className: "crm-kanban-item-contact-center-action-section"
									},
									children: [
										BX.create("a", {
											attrs: {
												href: "#"
											},
											props: {
												className: "crm-kanban-sidepanel crm-kanban-item-contact-center-action-item " +
															"crm-kanban-item-contact-center-action-item-call"
											},
											text: BX.message("CRM_KANBAN_EMPTY_CARD_CT_PHONES"),
											dataset: {
												url: "telephony"
											}
										}),
										BX.create("a", {
											attrs: {
												href: "#"
											},
											props: {
												className: "crm-kanban-sidepanel crm-kanban-item-contact-center-action-item " +
															"crm-kanban-item-contact-center-action-item-mail"
											},
											text: BX.message("CRM_KANBAN_EMPTY_CARD_CT_EMAIL"),
											dataset: {
												url: "email"
											}

										}),
										BX.create("a", {
											attrs: {
												href: "#"
											},
											props: {
												className: "crm-kanban-sidepanel crm-kanban-item-contact-center-action-item " +
															"crm-kanban-item-contact-center-action-item-telegram"
											},
											text: "Telegram",
											dataset: {
												url: "ol_telegram"
											}
										})
									]
								})
							]
						})
					]
				}),
				gridData.rights.canImport
				? BX.create("div", {
					children: [
						BX.create("div", {
							props: {
								className: "crm-kanban-item-contact-center-title crm-kanban-item-contact-center-title-import"
							},
							html: BX.message("CRM_KANBAN_EMPTY_CARD_IMPORT")
						})
					]
				})
				: null
			]
		});
	},

	/**
	 * Gets REST block.
	 * @returns {Element}
	 */
	getIndustrySolutionsLayout: function()
	{
		var importList = [
			{
				text: BX.message("CRM_KANBAN_REST_DEMO_FILE_IMPORT")
			},
			{
				text: BX.message("CRM_KANBAN_REST_DEMO_FILE_EXPORT")
			},
			{
				text: BX.message("CRM_KANBAN_REST_DEMO_CRM_MIGRATION")
			},
			{
				text: BX.message("CRM_KANBAN_REST_DEMO_MARKET_2")
			},
			{
				text: BX.message("CRM_KANBAN_REST_DEMO_PUBLICATION_2")
			}
		];

		var importListNode = document.createDocumentFragment();
		importList.map(function(data, index) {
			importListNode.appendChild(
				BX.create("div", {
					props: {
						className: "crm-kanban-item-industry-list-item crm-kanban-item-industry-list-item-" + (index + 1)
					},
					children: [
						BX.create("div", {
							props: {
								className: "crm-kanban-item-industry-list-item-img"
							}
						}),
						BX.create("div", {
							props: {
								className: "crm-kanban-item-industry-list-item-text"
							},
							text: data.text
						}),
					]
				})
			)
		});

		return BX.create("div", {
			props: {
				className: "crm-kanban-item-industry"
			},
			children: [
				BX.create("div", {
					props: {
						className: "crm-kanban-item-industry-title"
					},
					text: BX.message("CRM_KANBAN_REST_DEMO_MARKET_SECTOR")
				}),
				BX.create("div", {
					props: {
						className: "crm-kanban-item-industry-list"
					},
					children: [
						importListNode
					]
				}),
				BX.create("span", {
					props: {
						className: "ui-btn ui-btn-sm ui-btn-primary ui-btn-round crm-kanban-sidepanel"
					},
					dataset: {
						url: "rest_demo"
					},
					text: BX.message("CRM_KANBAN_REST_DEMO_SETUP")
				}),
				this.getCloseRestLayout()
			]
		})

	},

	/**
	 * Select thew item.
	 * @returns {void}
	 */
	selectItem: function()
	{
		this.checked = true;
		// BX.onCustomEvent("BX.CRM.Kanban.Item.select", [this]);
		BX.addClass(this.checkedButton, "crm-kanban-item-checkbox-checked");
		BX.addClass(this.container, "crm-kanban-item-selected");
	},

	/**
	 * Unselect the item.
	 * @returns {void}
	 */
	unSelectItem: function()
	{
		this.checked = false;
		// BX.onCustomEvent("BX.CRM.Kanban.Item.unSelect", [this]);
		BX.removeClass(this.checkedButton, "crm-kanban-item-checkbox-checked");
		BX.removeClass(this.container, "crm-kanban-item-selected");
	},

	/**
	 * Create layout for one item.
	 * @returns {void}
	 */
	createLayout: function()
	{
		var gridData = this.getGridData();

		// common container

		let containerClassname = this.getGrid().getTypeInfoParam('kanbanItemClassName');

		if (this.useAnimation)
		{
			containerClassname += ` ${containerClassname}-new`;
		}

		this.container = BX.create("div", {
			props: {
				className: containerClassname,
			},
			events: {
				click: function(e)
				{
					var parent = BX.findParent(e.target, {
						className: this.container.className
					});

					if (
						(e.target !== this.container && !parent)
						|| (parent && e.target.tagName === 'A')
						|| (
							parent
							&& e.target.tagName === 'SPAN'
							&& !e.target.classList.contains('crm-kanban-item-contact')
						)
					)
					{
						return;
					}

					if(this.checked)
					{
						this.getGrid().unCheckItem(this);

						if(this.getGrid().getChecked().length === 0)
						{
							this.getGrid().resetMultiSelectMode();
							this.getGrid().stopActionPanel();
						}
					}
					else
					{
						this.getGrid().checkItem(this);
						this.getGrid().onMultiSelectMode();
						this.getGrid().startActionPanel();
					}

					this.getGrid().calculateTotalCheckItems();
				}.bind(this),
				dblclick: function()
				{
					BX.fireEvent(this.link, "click");
				}.bind(this),
				mouseleave: function()
				{
					this.removeHoverClass(this.container);
				}.bind(this)
			}
		});

		BX.bind(this.container, "animationend", function()
		{
			BX.removeClass(this.layout.container, "main-kanban-item-new")
		}.bind(this));


		// title link
		this.link = BX.create("a", {
			props: {
				className: "crm-kanban-item-title"
			},
			style:
				this.data.fields.length > 0
				? { display: "none" }
				: {}
		});

		this.container.appendChild(this.link);

		this.container.appendChild(BX.Tag.render`<div class="crm-kanban-item-line"></div>`);

		// lead repeated
		if (
			this.options.data.return ||
			this.options.data.returnApproach
		)
		{
			this.repeated = BX.create("div", {
				props: {
					className: "crm-kanban-item-repeated"
				},
				text: this.options.data.returnApproach
						? BX.message("CRM_KANBAN_REPEATED_APPROACH_" + gridData.entityType)
						: BX.message("CRM_KANBAN_REPEATED_" + gridData.entityType)
			});
			this.container.appendChild(this.repeated);
		}

		// price
		this.totalPrice = BX.create("div", {
			props: {
				className: "crm-kanban-item-total-price"
			}
		});
		this.total = BX.create("div", {
			props: {
				className: "crm-kanban-item-total"
			},
			style: this.data.fields.length > 0
				? { display: "none" }
				: {},
			children: [
				this.totalPrice
			]
		});
		this.container.appendChild(this.total);

		// contact / company name
		this.clientName = BX.create("span", {
			props: {
				className: "crm-kanban-item-contact"
			}
		});
		this.container.appendChild(this.clientName);
		// date
		this.date = BX.create("div", {
			props: {
				className: "crm-kanban-item-date"
			},
			style: this.data.fields.length > 0
				? { display: "none" }
				: {}
		});
		this.container.appendChild(this.date);
		// checked button
		this.checkedButton = BX.create("div", {
			props: {
				className: "crm-kanban-item-checkbox"
			},
			events: {
				click: function()
				{
					this.checked = !this.checked;
					this.checked
						? BX.addClass(this.checkedButton, "crm-kanban-item-checkbox-checked")
						: BX.removeClass(this.checkedButton, "crm-kanban-item-checkbox-checked");
				}.bind(this)
			}
		});
		this.container.appendChild(this.checkedButton);

		if(this.data.fields.length)
		{
			this.container.appendChild(this.getItemFields());
		}

		this.badgesWrapper = BX.Tag.render`<div class="crm-kanban-item-badges"></div>`;
		BX.Dom.append(this.badgesWrapper, this.container);

		// plan
		if (gridData.showActivity)
		{
			this.activityExist = BX.create("span", {
				props: {
					className: "crm-kanban-item-activity"
				},
				events: {
					click: BX.delegate(this.showCurrentPlan, this)
				}
			});
			this.activityEmpty = BX.create("span", {
				props: {
					className: "crm-kanban-item-activity"
				},
				events: {
					click: BX.delegate(function()
					{
						this.showTooltip(
							this.getActivityMessage(gridData.entityType),
							BX.proxy_context,
							true
						);
					}, this)
					// mouseout: BX.delegate(this.hideTooltip, this)

				}
			});
			this.activityPlan = BX.create("span", {
				props: {
					className: "crm-kanban-item-plan"
				},
				text: "+ " + BX.message("CRM_KANBAN_ACTIVITY_TO_PLAN2"),
				events: {
					click: BX.delegate(this.showPlannerMenu, this)
				}
			});
			this.planner = BX.create("div", {
				props: {
					className: "crm-kanban-item-planner"
				},
				children: [
					this.activityPlan,
				],
			});
			this.container.appendChild(this.planner);
		}

		// phone, mail, chat
		this.contactPhone = BX.create("span", {
			props: {
				className: "crm-kanban-item-contact-phone crm-kanban-item-contact-phone-disabled"
			},
			attrs: {
				"data-type": "phone"
			}
		});
		this.contactEmail = BX.create("span", {
			props: {
				className: "crm-kanban-item-contact-email crm-kanban-item-contact-email-disabled"
			},
			attrs: {
				"data-type": "email"
			}
		});
		this.contactIm = BX.create("span", {
			props: {
				className: "crm-kanban-item-contact-im crm-kanban-item-contact-im-disabled"
			},
			attrs: {
				"data-type": "im"
			}
		});
		this.contactBlock = BX.create("div", {
			props: {
				className: "crm-kanban-item-connect"
			},
			children: [
				this.activityEmpty,
				this.activityExist,
				this.contactPhone,
				this.contactEmail,
				this.contactIm,
			],
		});
		this.container.appendChild(this.contactBlock);

		this.lastActivityTime = BX.Tag.render`<div class="crm-kanban-item-last-activity-time"></div>`;
		this.lastActivityBy = BX.Tag.render`<div class="crm-kanban-item-last-activity-by"></div>`;

		this.lastActivityBlock =
			BX.Tag.render`<div class="crm-kanban-item-last-activity">${this.lastActivityTime}${this.lastActivityBy}</div>`
		;
		this.container.appendChild(this.lastActivityBlock);

		// hover / shadow
		this.container.appendChild(this.createShadow());
	},

	/**
	 * Checked or not this item.
	 * @returns {Boolean}
	 */
	isChecked: function()
	{
		return this.checked;
	},

	/**
	 * Get message for activity popup.
	 * @param {String} type of entity.
	 * @returns {String}
	 */
	getActivityMessage: function(type) {
		const content = BX.create("span");
		const typeTranslateCode = /DYNAMIC_(\d+)/.test(type) ? 'DYNAMIC' : type;
		content.innerHTML = BX.message(`CRM_KANBAN_ACTIVITY_CHANGE_${typeTranslateCode}_MSGVER_1`)
			|| BX.message(`CRM_KANBAN_ACTIVITY_CHANGE_${typeTranslateCode}_MSGVER_2`);

		const eventLink = content.querySelector(".crm-kanban-item-activity-link");
		BX.bind(eventLink, "click", function() {
			this.showPlannerMenu(this.activityPlan);
			this.popupTooltip.destroy();
		}.bind(this));
		return content
	},

	/**
	 * Get preloader for popup.
	 * @returns {String}
	 */
	getPreloader: function()
	{
		return "<div class=\"crm-kanban-preloader-wapper\">\n\
								<div class=\"crm-kanban-preloader\">\n\
									<svg class=\"crm-kanban-circular\" viewBox=\"25 25 50 50\">\n\
										<circle class=\"crm-kanban-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-width=\"1\" stroke-miterlimit=\"10\"/>\n\
									</svg>\n\
								</div>\n\
						</div>";
	},

	/**
	 * Load current plan for item.
	 * @returns {void}
	 */
	loadCurrentPlan: function()
	{
		this.getGrid().ajax({
				action: "activities",
				entity_id: this.getId()
			},
			function(data)
			{
				this.plannerCurrent.setContent(data);
				this.plannerCurrent.adjustPosition();
			}.bind(this),
			function(error)
			{
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
			}.bind(this),
			"html"
		);
	},

	/**
	 * Show current plan items.
	 * @returns {void}
	 */
	showCurrentPlan: function()
	{
		this.plannerCurrent = BX.PopupWindowManager.create(
			"kanban_planner_current",
			BX.proxy_context,
			{
				closeIcon : false,
				autoHide: true,
				className: "crm-kanban-popup-plan",
				closeByEsc : true,
				contentColor: "white",
				angle: true,
				offsetLeft: 15,
				overlay: {
					backgroundColor: "transparent",
					opacity: "0"
				},
				events: {
					onAfterPopupShow: BX.delegate(this.loadCurrentPlan, this),
					onPopupClose: function()
					{
						this.plannerCurrent.destroy();
						BX.removeClass(this.container, "crm-kanban-item-hover");
						BX.unbind(window, "scroll", BX.proxy(this.adjustPopup, this));
					}.bind(this)
				}
			}
		);
		this.plannerCurrent.setContent(this.getPreloader());
		this.plannerCurrent.show();
		BX.bind(window, "scroll", BX.proxy(this.adjustPopup, this));
	},

	/**
	 * Click on phone/email/chat.
	 * @returns {void}
	 */
	clickContact: function()
	{
		var type = BX.data(BX.proxy_context, "type");
		var contactInfo = this.getContactInfo(type);

		var totalContactsCount = 0;
		if (typeof contactInfo === 'object')
		{
			if (Array.isArray(contactInfo))
			{
				totalContactsCount = contactInfo.length;
			}
			else
			{
				totalContactsCount =
					Object
						.values(contactInfo)
						.reduce(
							function(count, item) {
								return count + (Array.isArray(item) ? item.length : 0);
							},
							0
						)
				;
			}
		}

		if (totalContactsCount > 1)
		{
			this.showManyContacts(contactInfo, type);
		}
		else
		{
			this.showSingleContact(contactInfo, type);
		}
	},

	/**
	 * Click on phone/email/chat (one item).
	 * @param {Integer} i
	 * @param {Object} item
	 * @returns {void}
	 */
	clickContactItem: function(i, item)
	{
		var data = this.getData();

		if (item.type === "phone" && typeof(BXIM) !== "undefined")
		{
			BXIM.phoneTo(item.value, {
				ENTITY_TYPE: (item.clientType !== undefined ? item.clientType : data.contactType),
				ENTITY_ID: (item.clientId !== undefined ? item.clientId : data.contactId)
			});
		}
		else if (item.type === "im" && typeof(BXIM) !== "undefined")
		{
			BXIM.openMessengerSlider(item.value, {RECENT: "N", MENU: "N"});
		}
		else if (item.type === "email")
		{
			var hasActivityEditor = BX.CrmActivityEditor && BX.CrmActivityEditor.items["kanban_activity_editor"];
			var hasSlider = top.BX.Bitrix24 && top.BX.Bitrix24.Slider;
			if (hasActivityEditor && BX.CrmActivityProvider && hasSlider)
			{
				var gridData = this.getGridData();

				// @TODO: fix communication entity
				BX.CrmActivityEditor.items["kanban_activity_editor"].addEmail({
					"ownerType": gridData.entityType,
					"ownerID": data.id,
					"communications": [{
						"type": "EMAIL",
						"value": item.value,
						"entityId": data.id,
						"entityType": gridData.entityType,
						"entityTitle": data.name
					}],
					"communicationsLoaded": true
				});
			}
			else
			{
				//@tmp
				top.location.href = "mailto:" + item.value;
			}
		}
	},

	showManyContacts: function(contactCategories, type)
	{
		var menuItems = [];
		var fields = [];

		// converting the entity's own contact data into an object for correct use
		if (Array.isArray(contactCategories))
		{
			contactCategories = {0: contactCategories};
		}

		for (var category in contactCategories)
		{
			if (category === 'company' || category === 'contact')
			{
				menuItems.push({
					delimiter: true,
					text: this.getMessage(category)
				});
			}

			fields = contactCategories[category];
			for (var i = 0, c = fields.length; i < c; i++)
			{
				var clientType = '';
				var clientId = '';
				var data = this.getData();
				if (category === 'company')
				{
					clientType = 'CRM_COMPANY';
					clientId = data.companyId;
				}
				else if (category === 'contact')
				{
					clientType = 'CRM_CONTACT';
					clientId = data.contactId;
				}

				menuItems.push({
					value: fields[i]["value"],
					type: type,
					clientType: clientType,
					clientId: clientId,
					text: fields[i]["value"] + " (" + fields[i]["title"] + ")",
					onclick: BX.proxy(this.clickContactItem, this)
				});
			}
		}

		BX.PopupMenu.show(
			"kanban_contact_menu_" + type + this.getId(),
			BX.proxy_context,
			menuItems,
			{
				autoHide: true,
				zIndex: 1200,
				offsetLeft: 20,
				angle: true,
				closeByEsc : true,
				events: {
					onPopupClose: function()
					{
						BX.removeClass(this.container, "crm-kanban-item-hover");
						BX.unbind(window, "scroll", BX.proxy(this.adjustPopup, this));
					}.bind(this)
				}
			}
		);
		BX.bind(window, "scroll", BX.proxy(this.adjustPopup, this));
	},

	showSingleContact: function(contactInfo, type)
	{
		var fields = this.getSingleContactCategory(contactInfo);

		if (!Array.isArray(fields))
		{
			fields = [fields];
		}

		this.clickContactItem(0, {
			value: (typeof fields[0]["value"] !== "undefined")
				? fields[0]["value"]
				: fields[0],
			type: type
		});
	},

	getSingleContactCategory: function(contactInfo)
	{
		return (typeof contactInfo === 'object' ? contactInfo[Object.keys(contactInfo)[0]] : contactInfo);
	},

	getMessage: function(title)
	{
		return (BX.CRM.Kanban.Item.messages[title] || '');
	},

	/**
	 * Click one the item of plan menu
	 * @param {Integer} i
	 * @param {Object} item
	 * @returns {void}
	 */
	selectPlannerMenu: function(i, item)
	{
		BX.onCustomEvent("Crm.Kanban:selectPlannerMenu");
		var gridData = this.getGridData();

		if (item.type === "meeting" || item.type === "call")
		{
			(new BX.Crm.Activity.Planner()).showEdit({
				TYPE_ID: BX.CrmActivityType[item.type],
				OWNER_TYPE: gridData.entityType,
				OWNER_ID: this.getId()
			});
		}
		else if (item.type === "task")
		{
			if (typeof window["taskIFramePopup"] !== "undefined")
			{
				var taskData = {
					UF_CRM_TASK: [BX.CrmOwnerTypeAbbr.resolve(gridData.entityType) + "_" + this.getId()],
					TITLE: "CRM: ",
					TAGS: "crm"
				};
				window["taskIFramePopup"].add(taskData);
			}
		}
		else if (item.type === "visit")
		{
			var visitParams = gridData.visitParams;
			visitParams.OWNER_TYPE = gridData.entityType;
			visitParams.OWNER_ID = this.getId();
			BX.CrmActivityVisit.create(visitParams).showEdit();
		}

		var menu = BX.PopupMenu.getCurrentMenu();
		if (menu)
		{
			menu.close();
		}
	},

	/**
	 * Get menu for planner.
	 * @returns {Object}
	 */
	getPlannerMenu: function()
	{
		var gridData = this.getGrid().getData();

		return [
			{
				type: "call",
				text: BX.message("CRM_KANBAN_ACTIVITY_PLAN_CALL"),
				onclick: BX.delegate(this.selectPlannerMenu, this)
			},
			{
				type: "meeting",
				text: BX.message("CRM_KANBAN_ACTIVITY_PLAN_MEETING"),
				onclick: BX.delegate(this.selectPlannerMenu, this)
			},
			gridData.rights.canUseVisit ? (
				BX.getClass('BX.Crm.Restriction.Bitrix24') && BX.Crm.Restriction.Bitrix24.isRestricted('visit') ?
					{
						type: "visit",
						text: BX.message("CRM_KANBAN_ACTIVITY_PLAN_VISIT"),
						className: "crm-tariff-lock-behind",
						onclick: BX.Crm.Restriction.Bitrix24.getHandler('visit')
					} : {
						type: "visit",
						text: BX.message("CRM_KANBAN_ACTIVITY_PLAN_VISIT"),
						onclick: BX.delegate(this.selectPlannerMenu, this)
					}
			) : null,
			{
				type: "task",
				text: BX.message("CRM_KANBAN_ACTIVITY_PLAN_TASK"),
				onclick: BX.delegate(this.selectPlannerMenu, this)
			}
		];
	},

	/**
	 * Plan new activity.
	 * @returns {void}
	 */
	showPlannerMenu: function(node, mode = BX.Crm.Activity.TodoEditorMode.ADD, disableItem = false)
	{
		const id = this.getId();
		const popupId = `kanban_planner_menu_${id}`;
		const bindElement = node.isNode ? node : this.activityPlan;

		if (BX.CRM.Kanban.Restriction.Instance.isTodoActivityCreateAvailable())
		{
			if (disableItem)
			{
				this.disabledItem();
			}

			if (!this.activityAddingPopup)
			{
				this.activityAddingPopup = new BX.Crm.Activity.AddingPopup(
					this.getGridData().entityTypeInt,
					id,
					this.getCurrentUser(),
					{
						events: {
							onSave: function() {
								this.animate({
									duration: this.grid.animationDuration,
									draw: function(progress) {
										this.layout.container.style.opacity = 100 - progress * 50 + '%';
									}.bind(this),
									useAnimation: (this.layout.container.style.opacity === '1'),
								}).then(() => {
									void this.animate({
										duration: this.grid.animationDuration,
										draw: function(progress) {
											this.layout.container.style.opacity = progress * 100 + '%';
										}.bind(this),
										useAnimation: true,
									})
								});
							}.bind(this),
							onActualizePopupLayout: function({ data }) {
								const item = this.grid.getItem(data.entityId);
								if (item)
								{
									this.activityAddingPopup.bindPopup(item.planner.children[0]);
								}
							}.bind(this),
						}
					}
				);
			}

			this.activityAddingPopup.show(bindElement, mode);
			if (disableItem)
			{
				this.unDisabledItem();
			}
		}
		else if (mode === BX.Crm.Activity.TodoEditorMode.ADD )
		{
			var popupMenu = BX.PopupMenu.create(
				popupId,
				bindElement,
				this.getPlannerMenu(),
				{
					className: "crm-kanban-planner-popup-window",
					autoHide: true,
					offsetLeft: 50,
					angle: true,
					overlay: {
						backgroundColor: "transparent",
						opacity: "0"
					},
					events: {
						onPopupClose: function()
						{
							BX.removeClass(this.container, "crm-kanban-item-hover");
							BX.unbind(window, "scroll", BX.proxy(this.adjustPopup, this));
							popupMenu.destroy()
						}.bind(this)
					}
				}
			);

			BX.addCustomEvent(window, "Crm.Kanban:selectPlannerMenu", function()
			{
				popupMenu.destroy()
			});

			popupMenu.show();
			BX.bind(window, "scroll", BX.proxy(this.adjustPopup, this));
		}
	},

	/**
	 * Show / hide planner.
	 * @returns {void}
	 */
	switchPlanner: function()
	{
		var data = this.getData();
		var column = this.getColumn();
		var columnData = column.getData();

		if (data.activityProgress > 0)
		{
			this.switchVisible(this.activityExist, true);
			this.switchVisible(this.activityEmpty, false);
			this.setActivityExistInnerHtml();
		}
		else
		{
			var gridData = this.getGrid().getData();
			this.switchVisible(this.activityExist, false);
			this.switchVisible(this.activityPlan, true);
			this.switchVisible(this.activityEmpty, true);

			var activityEmptyHtml = '';
			if (gridData.reckonActivitylessItems && gridData.userId === parseInt(data.assignedBy, 10))
			{
				activityEmptyHtml = (columnData.type === "PROGRESS" ? this.getActivityCounterHtml(1) : '');
			}
			else
			{
				activityEmptyHtml = this.getActivityCounterHtml(0);
				this.activityEmpty.classList.add(this.itemActivityZeroClass);
			}

			this.activityEmpty.innerHTML = activityEmptyHtml;
		}
	},

	/**
	 * Description what the counter fields mean you can see
	 * at crm/lib/kanban/entityactivitycounter.php::appendToEntityItems
	 */
	setActivityExistInnerHtml()
	{
		if (this.activityExist !== undefined)
		{
			const data = this.getData();
			const column = this.getColumn();
			const columnData = column.getData();

			if (columnData.type !== 'PROGRESS')
			{
				return;
			}

			this.activityExist.classList.remove(...this.activityExist.classList);
			this.activityExist.classList.add('crm-kanban-item-activity');

			const userId = this.getGrid().getData().userId;

			const errorCounterByActivityResponsible = this.getGrid().getData().showErrorCounterByActivityResponsible || false;

			const html = errorCounterByActivityResponsible
				? this.makeCounterHtmlByActivityResponsible(data, userId)
				: this.makeCounterHtmlByEntityResponsible(data, userId);

			if (html.length > 0)
			{
				this.activityExist.innerHTML = html;
			}
		}
	},

	makeCounterHtmlByActivityResponsible(data, userId)
	{
		let html = '';

		const userActStat = data.activitiesByUser[userId] || {};

		const userActivityError = userActStat.activityError || 0;
		const userActivityIncoming = userActStat.incoming || 0;
		const userActivityProgress = userActStat.activityProgress || 0;
		const userActivityCounterTotal = userActStat.activityCounterTotal || 0;

		if (userActivityIncoming > 0 && userActivityError > 0)
		{
			html = this.getActivityCounterHtml(
				userActivityCounterTotal,
				'crm-kanban-item-activity-all-counters',
			);
		}
		else if (userActivityError > 0)
		{
			html = this.getActivityCounterHtml(
				userActivityError,
				'crm-kanban-item-activity-deadline-counter',
			);
		}
		else if (userActivityIncoming > 0)
		{
			html = this.getActivityCounterHtml(
				userActivityIncoming,
				'crm-kanban-item-activity-incoming-counter',
			);
		}
		else if (userActivityProgress > 0)
		{
			html = this.getActivityCounterHtml(0);
			this.activityExist.classList.add(this.itemActivityZeroClass);
			html += '<span class="crm-kanban-item-activity-indicator"></span>';
		}
		else
		{
			if (data.activityCounterTotal > 0)
			{
				html = this.getActivityCounterHtml(data.activityCounterTotal);
			}
			else
			{
				html = this.getActivityCounterHtml(0);
				html += '<span class="crm-kanban-item-activity-indicator crm-kanban-item-activity-indicator--grey"></span>';
			}
			this.activityExist.classList.add(this.itemActivityZeroClass);
		}

		return html;
	},

	makeCounterHtmlByEntityResponsible(data, userId)
	{
		let html = '';
		const isCurrentUserResponsibleToElement = userId === BX.prop.getNumber(this.data, 'assignedBy', 0);

		const activityProgress = data.activityProgress || 0;
		const activityError = data.activityError || 0;
		const activityIncomingTotal = data.activityIncomingTotal || 0;
		const activityCounterTotal = data.activityCounterTotal || 0;

		if (isCurrentUserResponsibleToElement)
		{
			if (activityIncomingTotal > 0 && activityError > 0)
			{
				html = this.getActivityCounterHtml(
					activityCounterTotal,
					'crm-kanban-item-activity-all-counters',
				);
			}
			else if (activityError > 0)
			{
				html = this.getActivityCounterHtml(
					activityError,
					'crm-kanban-item-activity-deadline-counter',
				);
			}
			else if (activityIncomingTotal > 0)
			{
				html = this.getActivityCounterHtml(
					activityIncomingTotal,
					'crm-kanban-item-activity-incoming-counter',
				);
			}
			else if (activityProgress > 0)
			{
				html = this.getActivityCounterHtml(0);
				this.activityExist.classList.add(this.itemActivityZeroClass);
				html += '<span class="crm-kanban-item-activity-indicator"></span>';
			}
			else
			{
				html = this.getActivityCounterHtml(0);
				this.activityExist.classList.add(this.itemActivityZeroClass);
			}
		}
		else
		{
			if (activityCounterTotal > 0)
			{
				html = this.getActivityCounterHtml(data.activityCounterTotal);
			}
			else if (activityProgress > 0)
			{
				html = this.getActivityCounterHtml(0);
				html += '<span class="crm-kanban-item-activity-indicator crm-kanban-item-activity-indicator--grey"></span>';
			}
			else
			{
				html = this.getActivityCounterHtml(0);
			}
			this.activityExist.classList.add(this.itemActivityZeroClass);
		}

		return html;
	},

	getActivityCounterHtml(value, additionalClass = '')
	{
		if (value > 99)
		{
			value = '99+';
			additionalClass += ' crm-kanban-item-activity-counter--narrow';
		}
		else
		{
			value = String(value);
		}

		return `
			<span class="crm-kanban-item-activity-counter ${additionalClass}">
				${value}
			</span>
		`;
	},

	getActivitiesIndicatorHtml(data, userId)
	{
		let indicatorClassName = 'crm-kanban-item-activity-indicator';
		if (!(
			data.activitiesByUser
			&& data.activitiesByUser[userId]
			&& data.activitiesByUser[userId]['activityProgress']
		))
		{
			indicatorClassName += ' crm-kanban-item-activity-indicator--grey';
		}

		return `<span class="${indicatorClassName}"></span>`;
	},

	/**
	 * Show some tooltip.
	 * @param {String} message
	 * @returns {void}
	 */
	showTooltip: function(message, context, white)
	{
		this.popupTooltip = new BX.PopupWindow(
			"kanban_tooltip",
			BX.proxy_context,
			{
				className: white
							? "crm-kanban-without-tooltip crm-kanban-without-tooltip-white"
							: "crm-kanban-without-tooltip crm-kanban-tooltip-animate",
				offsetLeft: 14,
				darkMode: white ? false : true,
				overlay: white ? {background: "black", opacity: 0} : null,
				closeByEsc: true,
				angle : true,
				autoHide: true,
				content: message,
				events: {
					onPopupClose: function()
					{
						BX.unbind(window, "scroll", BX.proxy(this.adjustPopup, this));
					}.bind(this)
				}
			}
		);

		BX.bind(window, "scroll", BX.proxy(this.adjustPopup, this));

		this.popupTooltip.show();
	},

	/**
	 * Hide tooltip.
	 * @returns {void}
	 */
	hideTooltip: function()
	{
		this.popupTooltip.destroy();
	},

	/**
	 * Add shadow to item.
	 * @returns {DOMNode}
	 */
	createShadow: function ()
	{
		return BX.create("div", {
			props: { className: "crm-kanban-item-shadow" }
		});
	},

	/**
	 * Remove hover from item.
	 * @param {DOMNode} itemBlock
	 * @returns {void}
	 */
	removeHoverClass: function (itemBlock)
	{
		BX.removeClass(itemBlock, "crm-kanban-item-event");
		BX.removeClass(itemBlock, "crm-kanban-item-hover");
	},

	/**
	 * Adjust position of current popup.
	 * @returns {void}
	 */
	adjustPopup: function()
	{
		var popup = BX.PopupWindowManager.getCurrentPopup();
		if (!popup)
		{
			if(menu)
			{
				var menu = BX.PopupMenu.getCurrentMenu();
				popup = menu.getPopupWindow();
			}
		}
		if (popup)
		{
			popup.adjustPosition();
		}
	},

	onDragDrop: function(itemNode, x, y)
	{
		this.dropChangedInPullRequest();
		this.hideDragTarget();

		var draggableItem,
			event,
			success;

		draggableItem = this.getGrid().getItemByElement(itemNode);
		draggableItem.dropChangedInPullRequest();

		event = new BX.Kanban.DragEvent();
		event.setItem(draggableItem);
		event.setTargetColumn(this.getColumn());
		event.setTargetItem(this);

		BX.onCustomEvent(this.getGrid(), "Kanban.Grid:onBeforeItemMoved", [event]);
		if (!event.isActionAllowed())
		{
			return;
		}

		this.getGrid().moveItem(draggableItem, this.getColumn(), this, true).then(function(result){
			if (result && result.status)
			{
				BX.onCustomEvent(
					this.getGrid(),
					'Kanban.Grid:onItemMoved',
					[
						draggableItem,
						this.getColumn(),
						this,
					]
				);
			}

			if (draggableItem.getColumn().getId() === this.getColumn().getId())
			{
				this.getGrid().resetMultiSelectMode();
				this.getGrid().cleanSelectedItems();
			}
		}.bind(this));
	},

	onDragStart: function()
	{
		// this.grid.resetMultiSelectMode();

		if (this.dragElement)
		{
			return;
		}

		if(!this.checked || this.grid.getChecked().length === 1)
		{
			this.grid.resetMultiSelectMode();
		}

		var itemContainer,
			bodyContainer;

		if(this.grid.getChecked().length > 1)
		{
			var moveItems = this.grid.getChecked().reverse();

			this.dragElement = BX.create("div", {
				props: {
					className: "main-kanban-item-drag-multi"
				}
			});

			for (var i = 0; i < moveItems.length && i < 3; i++)
			{
				BX.onCustomEvent(this.getGrid(), "Kanban.Grid:onItemDragStart", [moveItems[i]]);

				var itemNode = moveItems[i].getContainer().cloneNode(true);
				itemNode.style.width = moveItems[i].getContainer().offsetWidth + "px";
				this.getContainer().maxHeight = moveItems[0].getContainer().offsetHeight + "px";
				this.dragElement.appendChild(itemNode);
			}

			for (var i = 0; i < moveItems.length; i++)
			{
				moveItems[i].getContainer().classList.add("main-kanban-item-disabled");
			}

			document.body.appendChild(this.dragElement);

			return;
		}

		BX.onCustomEvent(this.getGrid(), "Kanban.Grid:onItemDragStart", [this]);

		itemContainer = this.getContainer();
		bodyContainer = this.getBodyContainer();
		this.getContainer().classList.add("main-kanban-item-disabled");

		this.dragElement = itemContainer.cloneNode(true);

		this.dragElement.style.position = "absolute";
		this.dragElement.style.width = bodyContainer.offsetWidth + "px";
		this.dragElement.className = "main-kanban-item main-kanban-item-drag";

		document.body.appendChild(this.dragElement);
	},

	makeDroppable: function()
	{
		if (!this.isDroppable())
		{
			return;
		}

		var itemContainer = this.getContainer();

		itemContainer.onbxdestdraghover = BX.delegate(this.onDragEnter, this);
		itemContainer.onbxdestdraghout = BX.delegate(this.onDragLeave, this);
		itemContainer.onbxdestdragfinish = BX.delegate(this.onDragDrop, this);

		itemContainer.onbxdestdragstop = BX.delegate(this.onItemDragEnd, this);

		jsDD.registerDest(itemContainer, 5);

		if (this.getGrid().getDragMode() !== BX.Kanban.DragMode.ITEM)
		{
			//when we load new items in drag mode
			this.disableDropping();
		}
	},

	getContactInfo: function(type)
	{
		var data = this.getData();
		return data[type];
	},

	getStageId: function ()
	{
		return this.getData().stageId;
	},

	animate: function(params)
	{
		var duration = params.duration;
		var draw = params.draw;

		// linear function by default, you can set non-linear animation function in timing key
		var timing = (params.timing || function(timeFraction){
			return timeFraction;
		});

		var useAnimation = ((params.useAnimation && !this.isAnimationInProgress) || false);

		var start = performance.now();

		return new Promise(
			function(resolve, reject)
			{
				if (!useAnimation)
				{
					this.isAnimationInProgress = false;
					return resolve();
				}

				var self = this;
				self.isAnimationInProgress = true;

				requestAnimationFrame(function animate(time)
				{
					var timeFraction = (time - start) / duration;
					if (timeFraction > 1)
					{
						timeFraction = 1;
					}

					var progress = timing(timeFraction);
					draw(progress);

					if (timeFraction < 1)
					{
						requestAnimationFrame(animate);
					}

					if (progress === 1)
					{
						self.isAnimationInProgress = false;
						resolve();
					}
				}.bind(this));
			}.bind(this)
		);
	},

	setChangedInPullRequest: function()
	{
		this.changedInPullRequest = true;
	},

	dropChangedInPullRequest: function()
	{
		this.changedInPullRequest = false;
	},

	isChangedInPullRequest: function()
	{
		return (this.changedInPullRequest === true);
	},

	/**
	 * @returns {boolean}
	 */
	isItemMoveDisabled: function()
	{
		const grid = this.getGrid();

		if (!grid.options.canChangeItemStage)
		{
			return true;
		}

		if (
			grid.getData().viewMode  === BX.Crm.Kanban.ViewMode.MODE_ACTIVITIES
			&& this.getData().activityIncomingTotal > 0
		)
		{
			return true;
		}

		const itemColumnData = this.getColumn().getData();

		return (grid.getTypeInfoParam('disableMoveToWin') && itemColumnData.type === 'WIN');
	},

	getCurrentUser()
	{
		const userId = this.getGrid().getData().userId;
		const currentUser = this.getGridData().currentUser;
		if (BX.type.isObject(currentUser) && userId > 0)
		{
			currentUser.userId = userId;
		}

		return currentUser;
	}
}

})();
