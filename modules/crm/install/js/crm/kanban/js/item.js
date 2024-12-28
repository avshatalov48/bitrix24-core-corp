// eslint-disable-next-line max-lines-per-function
(function() {
'use strict';

BX.namespace('BX.CRM.Kanban');

/**
 *
 * @param options
 * @extends {BX.Kanban.Item}
 * @constructor
 */
BX.CRM.Kanban.Item = function(options)
{
	/** @var {BX.CRM.Kanban.Grid} */
	this.grid = null;
	this.container = null;
	this.timer = null;
	this.popupTooltip = null;
	this.plannerCurrent = null;
	this.fieldsWrapper = null;
	this.badgesWrapper = null;
	this.footerWrapper = null;
	this.clientName = null;
	this.clientNameItems = [];
	this.useAnimation = false;
	this.isAnimationInProgress = false;
	this.changedInPullRequest = false;
	this.notChangeTotal = false;
	this.itemActivityZeroClass = 'crm-kanban-item-activity-zero';
	this.activityAddingPopup = null;

	// eslint-disable-next-line prefer-rest-params
	BX.Kanban.Item.apply(this, arguments);
};

BX.CRM.Kanban.Item.prototype = {
	__proto__: BX.Kanban.Item.prototype,
	constructor: BX.CRM.Kanban.Item,
	lastPosition: {
		columnId: null,
		targetId: null,
	},
	checked: false,

	setOptions(options)
	{
		if (!options)
		{
			return;
		}

		BX.Kanban.Item.prototype.setOptions.call(this, options);

		this.useAnimation = BX.type.isBoolean(options.useAnimation) ? options.useAnimation : false;
	},

	setDataKey(key, val)
	{
		const data = this.getData();
		data[key] = val;
		this.setData(data);
	},

	getDataKey(key)
	{
		const data = this.getData();

		return data[key];
	},

	switchClass(el, className, mode)
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

	switchVisible(element, mode)
	{
		if (mode)
		{
			BX.Dom.style(element, { display: '' });
		}
		else
		{
			BX.Dom.style(element, { display: 'none' });
		}
	},

	getLastPosition()
	{
		return this.lastPosition;
	},

	setLastPosition()
	{
		const column = this.getColumn();
		const sibling = column.getNextItemSibling(this);

		this.lastPosition = {
			columnId: column.getId(),
			targetId: sibling ? sibling.getId() : 0,
		};
	},

	getBodyContainer()
	{
		if (!this.layout.bodyContainer)
		{
			this.layout.bodyContainer = BX.Tag.render`<div class="main-kanban-item-wrapper"></div>`;
		}

		return this.layout.bodyContainer;
	},

	/**
	 * @returns {HTMLElement}
	 */
	render()
	{
		const data = this.getData();
		const specialType = data.special_type ?? null;

		if (specialType === 'import')
		{
			return this.getPreparedStartLayout();
		}

		if (specialType === 'rest')
		{
			return this.getPreparedIndustrySolutionsLayout();
		}

		if (!this.container)
		{
			this.createLayout();
		}

		if (this.isLayoutFooterEveryRender())
		{
			this.layoutFooter();
		}

		this.setBorderColor();
		this.setLink();
		this.setPriceFormattedHtml();

		this.date.textContent = data.date;

		this.setClientName();

		if (this.planner)
		{
			this.switchPlanner();
		}

		this.prepareContactTypeElements();
		this.appendLastActivity(data);

		if (this.needRenderFields())
		{
			this.fieldsWrapper.innerHTML = null;
			this.layoutFields();
		}

		this.layoutBadges();

		return this.container;
	},

	getPreparedStartLayout()
	{
		const layout = this.getStartLayout();
		this.emitOnSpecialItemDraw(layout);

		this.grid.ccItem = this;

		BX.Dom.style(this.getBodyContainer(), { background: 'none' });

		return layout;
	},

	/**
	 * Gets demo block for contact center.
	 * @returns {HTMLElement}
	 */
	getStartLayout()
	{
		this.getCloseStartLayout();

		const gridData = this.getGridData();

		const mainTitle = BX.Loc.getMessage('CRM_KANBAN_EMPTY_CARD_CT_TITLE');
		const secondTitle = BX.Loc.getMessage(`CRM_KANBAN_EMPTY_CARD_CT_TEXT${gridData.entityType}`);
		const cardImportNode = (
			gridData.rights.canImport
				? BX.Tag.render`
					<div>
						<div class="crm-kanban-item-contact-center-title crm-kanban-item-contact-center-title-import">
							${BX.Loc.getMessage('CRM_KANBAN_EMPTY_CARD_IMPORT')}
						</div>
					</div>
				`
				: null
		);

		return BX.Tag.render`
			<div class="crm-kanban-item-contact-center">
				<div class="crm-kanban-sidepanel" data-url="contact_center">
					${this.getCloseStartLayout()}
					<div class="crm-kanban-item-contact-center-title">
						<div class="crm-kanban-item-contact-center-title-item">${mainTitle}</div>
						<div class="crm-kanban-item-contact-center-title-item">${secondTitle}</div>
					</div>
					<div class="crm-kanban-item-contact-center-action">
						<div class="crm-kanban-item-contact-center-action-section">
							<a 
								href="#"
								data-url="ol_chat"
								class="crm-kanban-sidepanel crm-kanban-item-contact-center-action-item crm-kanban-item-contact-center-action-item-chat"
							>
								${BX.Loc.getMessage('CRM_KANBAN_EMPTY_CARD_CT_CHAT')}
							</a>
							<a 
								href="#"
								data-url="ol_forms"
								class="crm-kanban-sidepanel crm-kanban-item-contact-center-action-item crm-kanban-item-contact-center-action-item-crm-forms"
							>
								${BX.Loc.getMessage('CRM_KANBAN_EMPTY_CARD_CT_FORMS')}
							</a>
							<a 
								href="#"
								data-url="ol_viber"
								class="crm-kanban-sidepanel crm-kanban-item-contact-center-action-item crm-kanban-item-contact-center-action-item-viber"
							>
								Viber
							</a>
						</div>
						<div class="crm-kanban-item-contact-center-action-section">
							<a 
								href="#"
								data-url="telephony"
								class="crm-kanban-sidepanel crm-kanban-item-contact-center-action-item crm-kanban-item-contact-center-action-item-call"
							>
								${BX.Loc.getMessage('CRM_KANBAN_EMPTY_CARD_CT_PHONES')}
							</a>
							<a 
								href="#"
								data-url="email"
								class="crm-kanban-sidepanel crm-kanban-item-contact-center-action-item crm-kanban-item-contact-center-action-item-mail"
							>
								${BX.Loc.getMessage('CRM_KANBAN_EMPTY_CARD_CT_EMAIL')}
							</a>
							<a 
								href="#"
								data-url="ol_telegram"
								class="crm-kanban-sidepanel crm-kanban-item-contact-center-action-item crm-kanban-item-contact-center-action-item-telegram"
							>
								Telegram
							</a>
						</div>
					</div>
				</div>
				${cardImportNode}
			</div>
		`;
	},

	getPreparedIndustrySolutionsLayout()
	{
		const layout = this.getIndustrySolutionsLayout();
		this.emitOnSpecialItemDraw(layout);

		this.grid.restItem = this;

		return layout;
	},

	/**
	 * Gets REST block.
	 * @returns {Element}
	 */
	getIndustrySolutionsLayout()
	{
		const importList = [
			'CRM_KANBAN_REST_DEMO_FILE_IMPORT',
			'CRM_KANBAN_REST_DEMO_FILE_EXPORT',
			'CRM_KANBAN_REST_DEMO_CRM_MIGRATION',
			'CRM_KANBAN_REST_DEMO_MARKET_2',
			'CRM_KANBAN_REST_DEMO_PUBLICATION_2',
		];

		const importListNode = document.createDocumentFragment();
		importList.forEach((code, index) => {
			const className = `crm-kanban-item-industry-list-item crm-kanban-item-industry-list-item-${(index + 1)}`;
			const text = BX.Loc.getMessage(code);
			const element = BX.Tag.render`
				<div class="${className}">
					<div class="crm-kanban-item-industry-list-item-img"></div>
					<div class="crm-kanban-item-industry-list-item-text">${text}</div>
				</div>
			`;
			BX.Dom.append(element, importListNode);
		});

		return BX.Tag.render`
			<div class="crm-kanban-item-industry">
				<div class="crm-kanban-item-industry-title">
					${BX.Loc.getMessage('CRM_KANBAN_REST_DEMO_MARKET_SECTOR')}
				</div>
				<div class="crm-kanban-item-industry-list">
					${importListNode}
				</div>
				<span class="ui-btn ui-btn-sm ui-btn-primary ui-btn-round crm-kanban-sidepanel" data-url="rest_demo">
					${BX.Loc.getMessage('CRM_KANBAN_REST_DEMO_SETUP')}
				</span>
				<div class="crm-kanban-item-industry-close" onclick="${this.onIndustryCloseButtonClick.bind(this)}"></div>
			</div>
		`;
	},

	onIndustryCloseButtonClick(event)
	{
		event.stopPropagation(event);

		this.getGrid().toggleRest();

		this.getGrid().registerAnalyticsSpecialItemCloseEvent(
			this,
			BX.Crm.Integration.Analytics.Dictionary.SUB_SECTION_KANBAN,
			BX.Crm.Integration.Analytics.Dictionary.ELEMENT_CLOSE_BUTTON,
			BX.Crm.Integration.Analytics.Dictionary.TYPE_ITEM_INDUSTRY,
		);
	},

	emitOnSpecialItemDraw(layout)
	{
		BX.onCustomEvent('Crm.Kanban.Grid:onSpecialItemDraw', [this, layout]);
	},

	setBorderColor()
	{
		const color = this.getColumn().getColor();
		const rgb = BX.util.hex2rgb(color);
		const rgba = `rgba(${rgb.r},${rgb.g},${rgb.b},.7)`;

		BX.Dom.style(this.container, { '--crm-kanban-item-color': rgba });
	},

	setLink()
	{
		const data = this.getData();

		let linkHtml = this.clipTitle(data.name);
		if (data.isAutomationDebugItem)
		{
			const debugTitle = BX.Loc.getMessage('CRM_KANBAN_ITEM_DEBUG_TITLE_MSGVER_1');
			linkHtml = `<span class="crm-kanban-debug-item-label">${debugTitle}</span> ${linkHtml}`;
		}

		this.link.innerHTML = linkHtml;

		BX.Dom.attr(this.link, { href: data.link });
	},

	setPriceFormattedHtml()
	{
		const data = this.getData();

		if (this.totalPrice)
		{
			this.totalPrice.innerHTML = data.price_formatted;
		}
	},

	/**
	 * Add <span> for last word in title.
	 * @param {String} fullTitle
	 * @returns {String}
	 */
	clipTitle(fullTitle)
	{
		const separator = ' ';
		const arrTitle = fullTitle.split(separator);
		const lastWordIndex = arrTitle.length - 1;
		const lastWord = `<span>${arrTitle[lastWordIndex]}</span>`;

		arrTitle.splice(lastWordIndex);

		return `${arrTitle.join(separator)}${separator}${lastWord}`;
	},

	setClientName()
	{
		const data = this.getData();
		const gridData = this.getGridData();

		this.clientNameItems = [];
		if (
			data.contactId
			&& data.contactName
			&& gridData.customFields.includes('CLIENT')
		)
		{
			this.clientNameItems.push(data.contactTooltip);
		}

		if (
			data.companyId
			&& data.companyName
			&& gridData.customFields.includes('CLIENT')
		)
		{
			this.clientNameItems.push(data.companyTooltip);
		}

		if (BX.Type.isArrayFilled(this.clientNameItems))
		{
			this.clientName.innerHTML = this.clientNameItems.join('<br>');
			this.switchVisible(this.clientName, true);
		}
		else
		{
			this.switchVisible(this.clientName, false);
		}
	},

	prepareContactTypeElements()
	{
		const data = this.getData();

		const contactTypes = [
			'Phone',
			'Email',
			'Im',
		];

		contactTypes.forEach((type) => {
			const contactType = `contact${type}`;
			BX.Event.unbindAll(this[contactType]);

			const disabledClass = `crm-kanban-item-contact-${type.toLowerCase()}-disabled`;

			if (data[type.toLowerCase()])
			{
				BX.Event.bind(this[contactType], 'click', (event) => {
					this.clickContact(type.toLowerCase(), event.target);
				});
				this.switchClass(this[contactType], disabledClass, false);

				return;
			}

			BX.Event.bind(
				this[contactType],
				'mouseover',
				({ target }) => {
					const dataType = BX.Dom.attr(target, 'data-type');
					this.showTooltip(BX.Loc.getMessage(`CRM_KANBAN_NO_${dataType.toUpperCase()}`), target);
				},
			);
			BX.Event.bind(this[contactType], 'mouseout', this.hideTooltip.bind(this));

			this.switchClass(this[contactType], disabledClass, true);
		});
	},

	appendLastActivity(data)
	{
		BX.Dom.clean(this.lastActivityTime);
		BX.Dom.clean(this.lastActivityBy);

		const lastActivity = data.lastActivity;
		if (
			!BX.Type.isPlainObject(lastActivity)
			|| !BX.CRM.Kanban.Restriction.Instance.isLastActivityInfoInKanbanItemAvailable()
		)
		{
			return;
		}

		this.appendLastActivityTime(lastActivity);
		this.appendLastActivityUser(lastActivity);
	},

	appendLastActivityTime(lastActivity)
	{
		// server converts timezone to user before send
		const timestampInUserTimezone = BX.Text.toInteger(lastActivity.timestamp);
		if (timestampInUserTimezone <= 0)
		{
			return;
		}

		const utcTimestamp = timestampInUserTimezone - BX.Main.Timezone.Offset.USER_TO_SERVER;

		const timeInUserTimezone = BX.Main.Timezone.UserTime.getDate(utcTimestamp);
		const userNow = BX.Main.Timezone.UserTime.getDate();

		const secondsAgo = (userNow.getTime() - timeInUserTimezone.getTime()) / 1000;

		const ago = (
			secondsAgo <= 60
				? BX.Text.encode(BX.Loc.getMessage('CRM_KANBAN_JUST_NOW'))
				: this.getFormattedLastActiveDateTime(timeInUserTimezone, userNow)
		);

		const timeAgo = BX.Tag.render`
			<span class="crm-kanban-item-last-activity-time-ago">${ago}</span>
		`;

		BX.Dom.append(timeAgo, this.lastActivityTime);
	},

	appendLastActivityUser(lastActivity)
	{
		const lastActivityBy = lastActivity.user;
		if (!BX.Type.isPlainObject(lastActivityBy))
		{
			return;
		}

		let pictureStyle = '';
		if (BX.Type.isStringFilled(lastActivityBy.picture))
		{
			const pictureUrl = new BX.Uri(lastActivityBy.picture);
			const backgroundUrl = encodeURI(BX.Text.encode(pictureUrl.toString()));

			pictureStyle = `style="background-image: url(${backgroundUrl})"`;
		}

		const hasLink = (
			BX.Type.isStringFilled(lastActivityBy.link)
			&& lastActivityBy.link.startsWith('/')
		);
		const href = (hasLink ? lastActivityBy.link : '#');

		const userPic = BX.Tag.render`
			<a
				class="crm-kanban-item-last-activity-by-userpic"
				href="${BX.Text.encode(href)}"
			 	bx-tooltip-user-id="${BX.Text.toInteger(lastActivityBy.id)}"
				${pictureStyle}
			></a>
		`;

		BX.Dom.append(userPic, this.lastActivityBy);
	},

	getFormattedLastActiveDateTime(lastActivityTimeInUserTimezone, userNow)
	{
		const isCurrentYear = lastActivityTimeInUserTimezone.getFullYear() === (new Date()).getFullYear();
		const defaultFormat = (
			isCurrentYear
				? BX.Main.DateTimeFormat.getFormat('DAY_SHORT_MONTH_FORMAT')
				: BX.Main.DateTimeFormat.getFormat('MEDIUM_DATE_FORMAT')
		);

		let shortTimeFormat = BX.Main.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
		shortTimeFormat = shortTimeFormat.replace(/\b(a)\b/, 'A'); // for uppercase AM/PM markers: h:i a => h:i A

		const formattedDateTime = BX.Main.DateTimeFormat.format(
			[
				['i', 'idiff'],
				['yesterday', `yesterday, ${shortTimeFormat}`],
				['today', `today ${shortTimeFormat}`],
				['-', defaultFormat],
			],
			lastActivityTimeInUserTimezone,
			userNow,
		);

		return formattedDateTime
			.replaceAll('\\', '')
			.replaceAll(/(^|\s)(.)/g, (firstLetter) => firstLetter.toLocaleUpperCase())
		;
	},

	needRenderFields()
	{
		const wrapperCreated = Boolean(this.fieldsWrapper);
		const itemHasFields = Boolean(this.getData().fields);

		return Boolean(wrapperCreated && itemHasFields);
	},

	getItemFields()
	{
		if (!this.fieldsWrapper)
		{
			this.fieldsWrapper = BX.create('div', {
				props: {
					className: 'crm-kanban-item-fields',
				},
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

	layoutFields()
	{
		if (!this.fieldsWrapper)
		{
			return;
		}

		this.data.fields.forEach((field) => {
			this.layoutField(field);
		});
	},

	layoutField(field)
	{
		const code = field.code;

		if (code === 'TITLE')
		{
			this.switchVisible(this.link, true);

			return;
		}

		if (code === 'DATE_CREATE')
		{
			this.switchVisible(this.date, true);

			return;
		}

		if (code === 'CLIENT')
		{
			this.switchVisible(this.clientName, true);

			return;
		}

		if (code === 'OPPORTUNITY' || code === 'PRICE')
		{
			if (this.total)
			{
				this.switchVisible(this.total, true);
			}

			return;
		}

		let titleIcon = null;
		if (BX.Type.isObject(field.icon) && BX.Type.isArrayFilled(field.icon.url))
		{
			titleIcon = BX.Tag.render`
				<div class="crm-kanban-item-fields-item-title-icon">
					<img src="${field.icon.url}" title="${field.icon.title ?? ''}" alt="">
				</div>
			`;
		}

		const titleText = BX.Tag.render`<div class="crm-kanban-item-fields-item-title-text"></div>`;
		titleText.innerHTML = field.title;

		const fieldsElement = BX.Dom.create('div', this.getFieldParams(field));
		const fieldsItem = BX.Tag.render`
			<div class="crm-kanban-item-fields-item">
				<div class="crm-kanban-item-fields-item-title">
					${titleIcon}
					${titleText}
				</div>
				${fieldsElement}
			</div>
		`;

		BX.Dom.append(fieldsItem, this.fieldsWrapper);
	},

	/**
	 * @param field
	 * @property {string} code
	 * @property {boolean} html
	 * @property {string} icon
	 * @property {boolean} isMultiple
	 * @property {string} title
	 * @property {string} type
	 * @property {Object} value
	 * @property {string | null} valueDelimiter
	 * @returns {Object}
	 */
	getFieldParams(field)
	{
		const type = field.type ?? 'string';

		let params = {
			props: {
				className: 'crm-kanban-item-fields-item-value',
			},
		};

		if (type === 'user')
		{
			params = {
				...params,
				...this.getUserTypeFieldParams(field),
			};
		}
		else if (field.type === 'money' || field.html === true)
		{
			const delimiter = field.valueDelimiter ?? '<br>';
			params.html = BX.Type.isArray(field.value) ? field.value.join(delimiter) : field.value;

			if (params.html.includes('<b>'))
			{
				params.props.className = `${params.props.className} --normal-weight`;
			}
		}
		else
		{
			params.text = BX.Type.isArray(field.value) ? field.value.join(', ') : field.value;
		}

		return params;
	},

	getUserTypeFieldParams(field)
	{
		const params = {};

		if (field.html !== true)
		{
			params.text = this.getMessage('noname');

			return params;
		}

		if (BX.Type.isPlainObject(field.value))
		{
			let itemUserPic = '';
			let itemUserName = '';
			if (field.value.link === '')
			{
				itemUserPic = '<span class="crm-kanban-item-fields-item-value-userpic"></span>';
				itemUserName = `<span class="crm-kanban-item-fields-item-value-name">${field.value.title}</span>`;
			}
			else
			{
				let userPic = '';
				if (field.value.picture)
				{
					userPic = ` style="background-image: url(${encodeURI(field.value.picture)})"`;
				}
				itemUserPic = `<a class="crm-kanban-item-fields-item-value-userpic" href="${field.value.link}"${userPic}></a>`;
				itemUserName = `<a class="crm-kanban-item-fields-item-value-name" href="${field.value.link}">${field.value.title}</a>`;
			}

			params.html = `
				<div class="crm-kanban-item-fields-item-value-user">
					${itemUserPic}
					${itemUserName}
				</div>
			`;
		}
		else
		{
			params.html = BX.Type.isArray(field.value) ? field.value.join(', ') : field.value;
		}

		return params;
	},

	layoutBadges()
	{
		BX.Dom.clean(this.badgesWrapper);

		for (let i = 0; i < this.data.badges.length; i++)
		{
			const badge = this.data.badges[i];

			const badgeValueClass = 'crm-kanban-item-badges-item-value crm-kanban-item-badges-status';
			const badgeValueStyle = `
				background-color: ${badge.backgroundColor};
				border-color: ${badge.backgroundColor};
				color: ${badge.textColor};
			`;

			const item = BX.Tag.render`
				<div class="crm-kanban-item-badges-item">
					<div class="crm-kanban-item-badges-item-title">
						<div class="crm-kanban-item-badges-item-title-text">${badge.fieldName}</div>
					</div>
					<div class="${badgeValueClass}" style="${badgeValueStyle}">${badge.textValue}</div>
				</div>
			`;

			BX.Dom.append(item, this.badgesWrapper);
		}
	},

	layoutFooter()
	{
		BX.Dom.clean(this.footerWrapper);

		const elements = [
			{
				id: 'planner',
				node: this.createPlanner(),
			},
			{
				id: 'activityBlock',
				node: this.createLastActivityBlock(),
			},
		];

		const data = {
			elements,
			item: this,
		};

		BX.Event.EventEmitter.emit('BX.Crm.Kanban.Item::onBeforeFooterCreate', data);

		data.elements.forEach((element) => {
			BX.Dom.append(element.node, this.footerWrapper);
		});
	},

	/**
	 * Get close icon for demo-block.
	 * @return {Element}
	 */
	getCloseStartLayout()
	{
		return BX.create('div', {
			props: {
				className: 'crm-kanban-item-contact-center-close',
			},
			events: {
				click: function(e)
				{
					this.grid.toggleCC();
					e.stopPropagation(e);
				}.bind(this),
			},
		});
	},

	selectItem()
	{
		this.checked = true;
		// BX.onCustomEvent("BX.CRM.Kanban.Item.select", [this]);
		BX.addClass(this.checkedButton, 'crm-kanban-item-checkbox-checked');
		BX.addClass(this.container, 'crm-kanban-item-selected');
	},

	unSelectItem()
	{
		this.checked = false;
		// BX.onCustomEvent("BX.CRM.Kanban.Item.unSelect", [this]);
		BX.removeClass(this.checkedButton, 'crm-kanban-item-checkbox-checked');
		BX.removeClass(this.container, 'crm-kanban-item-selected');
	},

	createLayout()
	{
		const container = this.createContainer();

		const elements = [
			this.createTitleLink(),
			this.createLine(),
			this.createRepeated(),
			this.createTotalPrice(),
			this.createClientName(),
			this.createDate(),
			this.createCheckedButton(),
			this.hasFields() ? this.getItemFields() : null,
			this.createBadgesWrapper(),
			this.createAside(),
			this.createFooterWrapper(),
			this.createShadow(),
		];

		if (!this.isLayoutFooterEveryRender())
		{
			this.layoutFooter();
		}

		elements.forEach((element) => {
			BX.Dom.append(element, container);
		});
	},

	isLayoutFooterEveryRender()
	{
		return Boolean(this.getPerformanceSettings().layoutFooterEveryItemRender === 'Y');
	},

	getPerformanceSettings()
	{
		return this.getGrid().getData().performance;
	},

	createContainer()
	{
		let containerClassname = this.getGrid().getTypeInfoParam('kanbanItemClassName');
		if (this.useAnimation)
		{
			containerClassname += ` ${containerClassname}-new`;
		}

		this.container = BX.Tag.render`
			<div
				class="${containerClassname}"
				onclick="${this.onContainerClick.bind(this)}"
				ondblclick="${this.onContainerDblClick.bind(this)}"
				onmouseleave="${this.onContainerMouseLeave.bind(this)}"
			></div>
		`;

		BX.Event.bind(this.container, 'animationend', () => {
			BX.Dom.removeClass(this.layout.container, 'main-kanban-item-new');
		});

		return this.container;
	},

	onContainerClick(event)
	{
		const target = event.target;

		// maybe many classes, such as "main-kanban-item main-kanban-item-new"
		const classNames = this.container.className.replace(' ', '.');
		const parent = target.closest(`.${classNames}`);

		if (
			(target !== this.container && !parent)
			|| (parent && target.tagName === 'A')
			|| (
				parent
				&& target.tagName === 'SPAN'
				&& !BX.Dom.hasClass(target, 'crm-kanban-item-contact')
			)
		)
		{
			return;
		}

		const grid = this.getGrid();

		if (this.checked)
		{
			grid.unCheckItem(this);

			if (!BX.Type.isArrayFilled(grid.getChecked()))
			{
				grid.resetMultiSelectMode();
				grid.stopActionPanel();
			}
		}
		else
		{
			grid.checkItem(this);
			grid.onMultiSelectMode();
			grid.startActionPanel();
		}

		grid.calculateTotalCheckItems();
	},

	onContainerDblClick()
	{
		this.link.click();
	},

	onContainerMouseLeave()
	{
		this.removeHoverClass(this.container);
	},

	createTitleLink()
	{
		this.link = BX.Tag.render`<a class="crm-kanban-item-title" style="${this.getBlockStyleBasedOnFields()}"></a>`;

		return this.link;
	},

	createLine()
	{
		return BX.Tag.render`<div class="crm-kanban-item-line"></div>`;
	},

	createRepeated()
	{
		const optionsData = this.options.data;

		if (!optionsData.return && !optionsData.returnApproach)
		{
			return null;
		}

		const entityType = this.getGridData().entityType;
		const text = optionsData.returnApproach
			? BX.Loc.getMessage(`CRM_KANBAN_REPEATED_APPROACH_${entityType}`)
			: BX.Loc.getMessage(`CRM_KANBAN_REPEATED_${entityType}`)
		;

		return BX.Tag.render`<div class="crm-kanban-item-repeated">${text}</div>`;
	},

	createTotalPrice()
	{
		this.totalPrice = BX.Tag.render`<div class="crm-kanban-item-total-price"></div>`;
		this.total = BX.Tag.render`
			<div class="crm-kanban-item-total" style="${this.getBlockStyleBasedOnFields()}">${this.totalPrice}</div>
		`;

		return this.total;
	},

	createClientName()
	{
		this.clientName = BX.Tag.render`<span class="crm-kanban-item-contact"></span>`;

		return this.clientName;
	},

	createDate()
	{
		this.date = BX.Tag.render`
			<div class="crm-kanban-item-date" style="${this.getBlockStyleBasedOnFields()}"></div>
		`;

		return this.date;
	},

	getBlockStyleBasedOnFields()
	{
		return this.hasFields() ? 'display: none' : '';
	},

	hasFields()
	{
		return BX.Type.isArrayFilled(this.data.fields);
	},

	createCheckedButton()
	{
		this.checkedButton = BX.Tag.render`
			<div class="crm-kanban-item-checkbox" onclick="${this.onCheckedButtonClick.bind(this)}"></div>
		`;

		return this.checkedButton;
	},

	onCheckedButtonClick()
	{
		this.checked = !this.checked;

		const className = 'crm-kanban-item-checkbox-checked';
		if (this.checked)
		{
			BX.Dom.addClass(this.checkedButton, className);
		}
		else
		{
			BX.Dom.removeClass(this.checkedButton, className);
		}
	},

	createBadgesWrapper()
	{
		this.badgesWrapper = BX.Tag.render`<div class="crm-kanban-item-badges"></div>`;

		return this.badgesWrapper;
	},

	// runs only once and is not subsequently redrawn
	// BX.Crm.Kanban.Item::onBeforeAsideCreate is sent only once when the item is created
	createAside()
	{
		const limitExceededIcon = (
			this.isActivityLimitIsExceeded()
				? BX.Tag.render`<span class="crm-kanban-item-activity">${this.getActivityCounterHtml()}</span>`
				: null
		);

		const elements = [{
			id: 'limitExceededIcon',
			node: limitExceededIcon,
		}];

		if (this.isShowActivity())
		{
			this.activityExist = BX.Tag.render`
				<span class="crm-kanban-item-activity" onclick="${this.showCurrentPlan.bind(this)}"></span>
			`;

			elements.push({
				id: 'activityExist',
				node: this.activityExist,
			});

			this.activityEmpty = BX.Tag.render`
				<span class="crm-kanban-item-activity" onclick="${this.onActivityEmptyClick.bind(this)}"></span>
			`;

			elements.push({
				id: 'activityEmpty',
				node: this.activityEmpty,
			});
		}

		this.contactPhone = this.createContactItemNode('phone');
		elements.push({
			id: 'contactPhone',
			node: this.contactPhone,
		});

		this.contactEmail = this.createContactItemNode('email');
		elements.push({
			id: 'contactEmail',
			node: this.contactEmail,
		});

		this.contactIm = this.createContactItemNode('im');
		elements.push({
			id: 'contactIm',
			node: this.contactIm,
		});

		const data = {
			elements,
			item: this,
		};

		BX.Event.EventEmitter.emit('BX.Crm.Kanban.Item::onBeforeAsideCreate', data);

		const aside = BX.Tag.render`<div class="crm-kanban-item-aside"></div>`;

		data.elements.forEach((element) => {
			BX.Dom.append(element.node, aside);
		});

		return aside;
	},

	createContactItemNode(type)
	{
		return BX.Tag.render`
			<span
				class="crm-kanban-item-contact-${type} crm-kanban-item-contact-${type}-disabled"
				data-type="${type}"
			></span>
		`;
	},

	onActivityEmptyClick(event)
	{
		const activityMessage = this.getActivityMessage(this.getGridData().entityType);
		this.showTooltip(activityMessage, event.target, true);
	},

	createFooterWrapper()
	{
		this.footerWrapper = BX.Tag.render`<div class="crm-kanban-item-footer"></div>`;

		return this.footerWrapper;
	},

	createPlanner()
	{
		if (!this.isShowActivity())
		{
			return null;
		}

		this.activityPlan = BX.Tag.render`
			<span class="crm-kanban-item-plan" onclick="${this.showPlannerMenu.bind(this)}">
				+ ${BX.Loc.getMessage('CRM_KANBAN_ACTIVITY_TO_PLAN2')}
			</span>
		`;

		this.planner = BX.Tag.render`<div class="crm-kanban-item-planner">${this.activityPlan}</div>`;

		return this.planner;
	},

	isShowActivity()
	{
		return this.getGridData().showActivity;
	},

	createLastActivityBlock()
	{
		this.lastActivityTime = BX.Tag.render`<div class="crm-kanban-item-last-activity-time"></div>`;
		this.lastActivityBy = BX.Tag.render`<div class="crm-kanban-item-last-activity-by"></div>`;

		this.lastActivityBlock = BX.Tag.render`
			<div class="crm-kanban-item-last-activity">${this.lastActivityTime}${this.lastActivityBy}</div>
		`;

		return this.lastActivityBlock;
	},

	/**
	 * @returns {Boolean}
	 */
	isChecked()
	{
		return this.checked;
	},

	isHiddenPrice()
	{
		return this.getColumn()?.isHiddenTotalSum();
	},

	/**
	 * Get message for activity popup.
	 * @param {String} type of entity.
	 * @returns {String}
	 */
	getActivityMessage(type) {
		const content = BX.create('span');
		const typeTranslateCode = /DYNAMIC_(\d+)/.test(type) ? 'DYNAMIC' : type;
		content.innerHTML = BX.Loc.getMessage(`CRM_KANBAN_ACTIVITY_CHANGE_${typeTranslateCode}_MSGVER_1`)
			|| BX.Loc.getMessage(`CRM_KANBAN_ACTIVITY_CHANGE_${typeTranslateCode}_MSGVER_2`);

		const eventLink = content.querySelector('.crm-kanban-item-activity-link');
		BX.bind(eventLink, 'click', () => {
			this.showPlannerMenu(this.activityPlan);
			this.popupTooltip.destroy();
		});

		return content;
	},

	/**
	 * Get preloader for popup.
	 * @returns {String}
	 */
	getPreloader()
	{
		// eslint-disable-next-line no-multi-str
		return '\
			<div class="crm-kanban-preloader-wrapper">\n\
				<div class="crm-kanban-preloader">\n\
					<svg class="crm-kanban-circular" viewBox="25 25 50 50">\n\
						<circle class="crm-kanban-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"/>\n\
					</svg>\n\
				</div>\n\
			</div>\
		';
	},

	loadCurrentPlan()
	{
		this.getGrid().ajax(
			{
				action: 'activities',
				entity_id: this.getId(),
			},
			(data) => {
				this.plannerCurrent.setContent(data);
				this.plannerCurrent.adjustPosition();
			},
			(error) => {
				BX.Kanban.Utils.showErrorDialog(`Error: ${error}`, true);
			},
			'html',
		);
	},

	showCurrentPlan(event)
	{
		this.plannerCurrent = BX.PopupWindowManager.create(
			'kanban_planner_current',
			event.target,
			{
				closeIcon: false,
				autoHide: true,
				className: 'crm-kanban-popup-plan',
				closeByEsc: true,
				contentColor: 'white',
				angle: true,
				offsetLeft: 15,
				overlay: {
					backgroundColor: 'transparent',
					opacity: '0',
				},
				events: {
					onAfterPopupShow: this.loadCurrentPlan.bind(this),
					onPopupClose: () => {
						this.plannerCurrent.destroy();
						BX.removeClass(this.container, 'crm-kanban-item-hover');
						BX.Event.unbind(window, 'scroll', this.adjustPopup);
					},
				},
			},
		);
		this.plannerCurrent.setContent(this.getPreloader());
		this.plannerCurrent.show();

		BX.Event.bind(window, 'scroll', this.adjustPopup.bind(this));
	},

	clickContact(type, bindElement)
	{
		const contactInfo = this.getContactInfo(type);

		let totalContactsCount = 0;
		if (BX.Type.isObject(contactInfo))
		{
			if (BX.Type.isArray(contactInfo))
			{
				totalContactsCount = contactInfo.length;
			}
			else
			{
				totalContactsCount = Object
					.values(contactInfo)
					.reduce(
						(count, item) => {
							return count + (BX.Type.isArray(item) ? item.length : 0);
						},
						0,
					)
				;
			}
		}

		if (totalContactsCount > 1)
		{
			this.showManyContacts(contactInfo, type, bindElement);
		}
		else
		{
			this.showSingleContact(contactInfo, type);
		}
	},

	clickContactItem(item)
	{
		const data = this.getData();

		// eslint-disable-next-line no-undef
		if (item.type === 'phone' && !BX.Type.isUndefined(BXIM))
		{
			// eslint-disable-next-line no-undef
			BXIM.phoneTo(item.value, {
				ENTITY_TYPE: (item.clientType === undefined ? data.contactType : item.clientType),
				ENTITY_ID: (item.clientId === undefined ? data.contactId : item.clientId),
			});
		}
		// eslint-disable-next-line no-undef
		else if (item.type === 'im' && !BX.Type.isUndefined(BXIM))
		{
			// eslint-disable-next-line no-undef
			BXIM.openMessengerSlider(item.value, { RECENT: 'N', MENU: 'N' });
		}
		else if (item.type === 'email')
		{
			const hasActivityEditor = BX.CrmActivityEditor && BX.CrmActivityEditor.items.kanban_activity_editor;
			const hasSlider = top.BX.SidePanel && top.BX.SidePanel.Instance;
			if (hasActivityEditor && BX.CrmActivityProvider && hasSlider)
			{
				const gridData = this.getGridData();

				// @TODO: fix communication entity
				BX.CrmActivityEditor.items.kanban_activity_editor.addEmail({
					ownerType: gridData.entityType,
					ownerID: data.id,
					communications: [{
						type: 'EMAIL',
						value: item.value,
						entityId: data.id,
						entityType: gridData.entityType,
						entityTitle: data.name,
					}],
					communicationsLoaded: true,
				});
			}
			else
			{
				// @tmp
				top.location.href = `mailto:${item.value}`;
			}
		}
	},

	showManyContacts(contactCategories, type, bindElement)
	{
		const menuItems = [];

		// converting the entity's own contact data into an object for correct use
		if (Array.isArray(contactCategories))
		{
			// eslint-disable-next-line no-param-reassign
			contactCategories = { 0: contactCategories };
		}

		Object.keys(contactCategories).forEach((category) => {
			if (category === 'company' || category === 'contact')
			{
				menuItems.push({
					delimiter: true,
					text: this.getMessage(category),
				});
			}

			const fields = contactCategories[category];
			fields.forEach((field) => {
				let clientType = '';
				let clientId = '';
				const data = this.getData();
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
					value: field.value,
					type,
					clientType,
					clientId,
					text: `${field.value} (${field.title})`,
					onclick: this.clickContactItem.bind(this, {
						value: field.value,
						type,
					}),
				});
			});
		});

		const menu = new BX.Main.Menu(
			`kanban_contact_menu_${type}${this.getId()}`,
			bindElement,
			menuItems,
			{
				autoHide: true,
				zIndex: 1200,
				offsetLeft: 20,
				angle: true,
				closeByEsc: true,
				events: {
					onPopupClose: () => {
						BX.Dom.removeClass(this.container, 'crm-kanban-item-hover');
						BX.unbind(window, 'scroll', BX.proxy(this.adjustPopup, this));
					},
				},
			},
		);

		menu.show();

		BX.bind(window, 'scroll', BX.proxy(this.adjustPopup, this));
	},

	showSingleContact(contactInfo, type)
	{
		let fields = this.getSingleContactCategory(contactInfo);

		if (!Array.isArray(fields))
		{
			fields = [fields];
		}

		this.clickContactItem({
			value: (BX.Type.isUndefined(fields[0].value)) ? fields[0] : fields[0].value,
			type,
		});
	},

	getSingleContactCategory(contactInfo)
	{
		return (BX.Type.isObjectLike(contactInfo) ? contactInfo[Object.keys(contactInfo)[0]] : contactInfo);
	},

	/**
	 * @param {string} title
	 * @returns {string}
	 */
	getMessage(title)
	{
		return (BX.CRM.Kanban.Item.messages[title] || '');
	},

	/**
	 * Click one the item of plan menu
	 * @param {Integer} i
	 * @param {Object} item
	 * @returns {void}
	 */
	selectPlannerMenu(i, item)
	{
		BX.onCustomEvent('Crm.Kanban:selectPlannerMenu');
		const gridData = this.getGridData();

		switch (item.type)
		{
			case 'meeting':
			case 'call': {
				(new BX.Crm.Activity.Planner()).showEdit({
					TYPE_ID: BX.CrmActivityType[item.type],
					OWNER_TYPE: gridData.entityType,
					OWNER_ID: this.getId(),
				});

				break;
			}

			case 'task': {
				const taskData = {
					UF_CRM_TASK: [`${BX.CrmOwnerTypeAbbr.resolve(gridData.entityType)}_${this.getId()}`],
					TITLE: 'CRM: ',
					TAGS: 'crm',
				};

				let taskCreatePath = BX.Loc.getMessage('CRM_TASK_CREATION_PATH');
				taskCreatePath = taskCreatePath.replace('#user_id#', BX.Loc.getMessage('USER_ID'));
				taskCreatePath = BX.util.add_url_param(
					taskCreatePath,
					taskData,
				);

				if (BX.SidePanel)
				{
					BX.SidePanel.Instance.open(taskCreatePath);
				}
				else
				{
					window.top.location.href = taskCreatePath;
				}

				break;
			}

			case 'visit': {
				const visitParams = gridData.visitParams;
				visitParams.OWNER_TYPE = gridData.entityType;
				visitParams.OWNER_ID = this.getId();
				BX.CrmActivityVisit.create(visitParams).showEdit();

				break;
			}

			default: // Do nothing
		}

		const menu = BX.PopupMenu.getCurrentMenu();
		if (menu)
		{
			menu.close();
		}
	},

	/**
	 * Get menu for planner.
	 * @returns {Object}
	 */
	getPlannerMenu()
	{
		const gridData = this.getGrid().getData();

		return [
			{
				type: 'call',
				text: BX.Loc.getMessage('CRM_KANBAN_ACTIVITY_PLAN_CALL'),
				onclick: BX.delegate(this.selectPlannerMenu, this),
			},
			{
				type: 'meeting',
				text: BX.Loc.getMessage('CRM_KANBAN_ACTIVITY_PLAN_MEETING'),
				onclick: BX.delegate(this.selectPlannerMenu, this),
			},
			gridData.rights.canUseVisit ? (
				BX.getClass('BX.Crm.Restriction.Bitrix24') && BX.Crm.Restriction.Bitrix24.isRestricted('visit')
					? {
						type: 'visit',
						text: BX.Loc.getMessage('CRM_KANBAN_ACTIVITY_PLAN_VISIT'),
						className: 'crm-tariff-lock-behind',
						onclick: BX.Crm.Restriction.Bitrix24.getHandler('visit'),
					} : {
						type: 'visit',
						text: BX.Loc.getMessage('CRM_KANBAN_ACTIVITY_PLAN_VISIT'),
						onclick: BX.delegate(this.selectPlannerMenu, this),
					}
			) : null,
			{
				type: 'task',
				text: BX.Loc.getMessage('CRM_KANBAN_ACTIVITY_PLAN_TASK'),
				onclick: BX.delegate(this.selectPlannerMenu, this),
			},
		];
	},

	showPlannerMenu(node, mode = BX.Crm.Activity.TodoEditorMode.ADD, disableItem = false)
	{
		if (BX.CRM.Kanban.Restriction.Instance.isTodoActivityCreateAvailable())
		{
			this.prepareAndShowActivityAddingPopup(node, mode, disableItem);
		}
		else if (mode === BX.Crm.Activity.TodoEditorMode.ADD)
		{
			this.prepareAndShowPlannerPopup(node);
		}
	},

	prepareAndShowActivityAddingPopup(node, mode, disableItem)
	{
		const id = this.getId();

		if (disableItem)
		{
			this.disabledItem();
		}

		const data = this.getData();
		const gridData = this.getGridData();
		const pingSettings = data.pingSettings || gridData.pingSettings;
		const colorSettings = data.colorSettings || gridData.colorSettings;
		const calendarSettings = data.calendarSettings || gridData.calendarSettings;
		const settings = {
			pingSettings,
			colorSettings,
			calendarSettings,
		};

		const params = {
			context: this.getToDoEditorContext(),
			events: {
				onSave: () => {
					void this.animate({
						duration: this.grid.animationDuration,
						draw: (progress) => {
							BX.Dom.style(this.layout.container, 'opacity', `${100 - progress * 50}%`);
						},
						useAnimation: (BX.Dom.style(this.layout.container, 'opacity') === '1'),
					}).then(() => {
						void this.animate({
							duration: this.grid.animationDuration,
							draw: (progress) => {
								BX.Dom.style(this.layout.container, 'opacity', `${progress * 100}%`);
							},
							useAnimation: true,
						});
					});
				},
			},
		};

		if (!this.activityAddingPopup)
		{
			this.activityAddingPopup = new BX.Crm.Activity.AddingPopup(
				this.getGridData().entityTypeInt,
				id,
				this.getCurrentUser(),
				settings,
				params,
			);
		}

		this.activityAddingPopup.show(mode);
		if (disableItem)
		{
			this.unDisabledItem();
		}
	},

	getToDoEditorContext()
	{
		return {
			analytics: this.grid.getData().analytics,
		};
	},

	prepareAndShowPlannerPopup(node)
	{
		const id = this.getId();
		const popupId = `kanban_planner_menu_${id}`;
		const bindElement = node.isNode ? node : this.activityPlan;

		const popupMenu = new BX.Main.Menu(
			popupId,
			bindElement,
			this.getPlannerMenu(),
			{
				className: 'crm-kanban-planner-popup-window',
				autoHide: true,
				offsetLeft: 50,
				angle: true,
				overlay: {
					backgroundColor: 'transparent',
					opacity: '0',
				},
				events: {
					onPopupClose: () => {
						BX.Dom.removeClass(this.container, 'crm-kanban-item-hover');
						BX.Event.unbind(window, 'scroll', this.adjustPopup);
						popupMenu.destroy();
					},
				},
			},
		);

		BX.addCustomEvent(window, 'Crm.Kanban:selectPlannerMenu', () => {
			popupMenu.destroy();
		});

		popupMenu.show();
		BX.Event.bind(window, 'scroll', this.adjustPopup.bind(this));
	},

	switchPlanner()
	{
		const data = this.getData();
		const column = this.getColumn();
		const columnData = column.getData();

		if (data.activityProgress > 0)
		{
			this.switchVisible(this.activityExist, true);
			this.switchVisible(this.activityEmpty, false);
			this.setActivityExistInnerHtml();
		}
		else
		{
			const gridData = this.getGrid().getData();
			this.switchVisible(this.activityExist, false);
			this.switchVisible(this.activityPlan, true);
			this.switchVisible(this.activityEmpty, true);

			let activityEmptyHtml = '';
			if (gridData.reckonActivitylessItems && gridData.userId === parseInt(data.assignedBy, 10))
			{
				activityEmptyHtml = (columnData.type === 'PROGRESS' ? this.getActivityCounterHtml(1) : '');
			}
			else
			{
				activityEmptyHtml = this.getActivityCounterHtml(0);
				BX.Dom.addClass(this.activityEmpty, this.itemActivityZeroClass);
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
		if (BX.Type.isUndefined(this.activityExist))
		{
			return;
		}

		BX.Dom.removeClass(this.activityExist, ...this.activityExist.classList);
		BX.Dom.addClass(this.activityExist, 'crm-kanban-item-activity');

		const gridData = this.getGrid().getData();
		const errorCounterByActivityResponsible = gridData.showErrorCounterByActivityResponsible || false;
		const data = this.getData();
		const userId = gridData.userId;

		const html = errorCounterByActivityResponsible
			? this.makeCounterHtmlByActivityResponsible(data, userId)
			: this.makeCounterHtmlByEntityResponsible(data, userId)
		;

		if (BX.Type.isStringFilled(html))
		{
			this.activityExist.innerHTML = html;
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
			BX.Dom.addClass(this.activityExist, this.itemActivityZeroClass);
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
			BX.Dom.addClass(this.activityExist, this.itemActivityZeroClass);
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
				BX.Dom.addClass(this.activityExist, this.itemActivityZeroClass);
				html += '<span class="crm-kanban-item-activity-indicator"></span>';
			}
			else
			{
				html = this.getActivityCounterHtml(0);
				BX.Dom.addClass(this.activityExist, this.itemActivityZeroClass);
			}

			return html;
		}

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
		BX.Dom.addClass(this.activityExist, this.itemActivityZeroClass);

		return html;
	},

	getActivityCounterHtml(value, additionalClass = '')
	{
		let title = null;
		let counterValue = null;
		let counterAdditionalClass = null;
		if (this.isActivityLimitIsExceeded())
		{
			counterValue = '?';
			counterAdditionalClass = `${additionalClass} crm-kanban-item-activity-counter--limit-exceeded`;
			title = `title="${BX.Loc.getMessage('CRM_KANBAN_ITEM_COUNTER_LIMIT_IS_EXCEEDED')}"`;
		}
		else if (value > 99)
		{
			counterValue = '99+';
			counterAdditionalClass = `${additionalClass} crm-kanban-item-activity-counter--narrow`;
		}
		else
		{
			counterValue = String(value);
			counterAdditionalClass = String(additionalClass);
		}

		return `
			<span class="crm-kanban-item-activity-counter ${counterAdditionalClass}" ${title}>
				<span class="item-activity-counter__before"></span>
				${counterValue}
				<span class="item-activity-counter__after"></span>
			</span>
		`;
	},

	isActivityLimitIsExceeded()
	{
		return this.getGridData().isActivityLimitIsExceeded;
	},

	showTooltip(content, target, white)
	{
		const blackOverlay = {
			background: 'black',
			opacity: 0,
		};
		const overlay = white ? blackOverlay : null;
		const className = `crm-kanban-without-tooltip ${white ? 'crm-kanban-without-tooltip-white' : 'crm-kanban-tooltip-animate'}`;

		this.popupTooltip = BX.PopupWindowManager.create(
			'kanban_tooltip',
			target,
			{
				className,
				content,
				overlay,
				offsetLeft: 14,
				darkMode: !white,
				closeByEsc: true,
				angle: true,
				autoHide: true,
				events: {
					onPopupClose: () => {
						BX.Event.unbind(window, 'scroll', this.adjustPopup.bind(this));
					},
				},
			},
		);

		this.popupTooltip.show();

		BX.Event.bind(window, 'scroll', this.adjustPopup.bind(this));
	},

	hideTooltip()
	{
		this.popupTooltip.destroy();
	},

	createShadow()
	{
		return BX.Tag.render`<div class="crm-kanban-item-shadow"></div>`;
	},

	removeHoverClass(itemBlock)
	{
		BX.Dom.removeClass(itemBlock, 'crm-kanban-item-event');
		BX.Dom.removeClass(itemBlock, 'crm-kanban-item-hover');
	},

	adjustPopup()
	{
		const popup = BX.PopupWindowManager.getCurrentPopup();

		if (popup && popup.isShown())
		{
			popup.adjustPosition();
		}
	},

	onDragDrop(itemNode)
	{
		this.dropChangedInPullRequest();
		this.hideDragTarget();

		const draggableItem = this.getGrid().getItemByElement(itemNode);
		draggableItem.dropChangedInPullRequest();

		const event = new BX.Kanban.DragEvent();
		event.setItem(draggableItem);
		event.setTargetColumn(this.getColumn());
		event.setTargetItem(this);

		BX.onCustomEvent(this.getGrid(), 'Kanban.Grid:onBeforeItemMoved', [event]);
		if (!event.isActionAllowed())
		{
			return;
		}

		void this.getGrid()
			.moveItem(draggableItem, this.getColumn(), this, true)
			.then((result) => {
				if (result && result.status)
				{
					BX.onCustomEvent(
						this.getGrid(),
						'Kanban.Grid:onItemMoved',
						[
							draggableItem,
							this.getColumn(),
							this,
						],
					);
				}

				if (draggableItem.getColumn().getId() === this.getColumn().getId())
				{
					this.getGrid().resetMultiSelectMode();
					this.getGrid().cleanSelectedItems();
				}
			})
		;
	},

	onDragStart()
	{
		// this.grid.resetMultiSelectMode();

		if (this.dragElement)
		{
			return;
		}

		if (!this.checked || this.grid.getChecked().length === 1)
		{
			this.grid.resetMultiSelectMode();
		}

		if (this.grid.getChecked().length > 1)
		{
			const moveItems = this.grid.getChecked().reverse();

			this.dragElement = BX.create('div', {
				props: {
					className: 'main-kanban-item-drag-multi',
				},
			});

			for (let i = 0; i < moveItems.length; i++)
			{
				BX.onCustomEvent(this.getGrid(), 'Kanban.Grid:onItemDragStart', [moveItems[i]]);

				const itemNode = moveItems[i].getContainer().cloneNode(true);
				BX.Dom.style(itemNode, 'width', `${moveItems[i].getContainer().offsetWidth}px`);
				this.getContainer().maxHeight = `${moveItems[0].getContainer().offsetHeight}px`;
				BX.Dom.append(itemNode, this.dragElement);
			}

			for (const moveItem of moveItems)
			{
				BX.Dom.addClass(moveItem.getContainer(), 'main-kanban-item-disabled');
			}

			BX.Dom.append(this.dragElement, document.body);

			return;
		}

		BX.onCustomEvent(this.getGrid(), 'Kanban.Grid:onItemDragStart', [this]);

		const container = this.getContainer();
		BX.Dom.addClass(container, 'main-kanban-item-disabled');

		this.dragElement = container.cloneNode(true);

		BX.Dom.style(this.dragElement, {
			position: 'absolute',
			width: `${this.getBodyContainer().offsetWidth}px`,
		});
		BX.Dom.addClass(this.dragElement, 'main-kanban-item main-kanban-item-drag');

		BX.Dom.append(this.dragElement, document.body);
	},

	makeDroppable()
	{
		if (!this.isDroppable())
		{
			return;
		}

		const itemContainer = this.getContainer();

		itemContainer.onbxdestdraghover = BX.delegate(this.onDragEnter, this);
		itemContainer.onbxdestdraghout = BX.delegate(this.onDragLeave, this);
		itemContainer.onbxdestdragfinish = BX.delegate(this.onDragDrop, this);
		itemContainer.onbxdestdragstop = BX.delegate(this.onItemDragEnd, this);

		jsDD.registerDest(itemContainer, 5);

		if (this.getGrid().getDragMode() !== BX.Kanban.DragMode.ITEM)
		{
			// when we load new items in drag mode
			this.disableDropping();
		}
	},

	getContactInfo(type)
	{
		const data = this.getData();

		return data[type];
	},

	getStageId()
	{
		return this.getData().stageId;
	},

	animate(params)
	{
		const duration = params.duration;
		const draw = params.draw;

		// linear function by default, you can set non-linear animation function in timing key
		const timing = (params.timing || function(timeFraction) {
			return timeFraction;
		});

		const useAnimation = ((params.useAnimation && !this.isAnimationInProgress) || false);

		const start = performance.now();

		return new Promise(
			(resolve) => {
				if (!useAnimation)
				{
					this.isAnimationInProgress = false;

					resolve();

					return;
				}

				const item = this;
				item.isAnimationInProgress = true;

				requestAnimationFrame(function animate(time)
				{
					let timeFraction = (time - start) / duration;
					if (timeFraction > 1)
					{
						timeFraction = 1;
					}

					const progress = timing(timeFraction);
					draw(progress);

					if (timeFraction < 1)
					{
						requestAnimationFrame(animate);
					}

					if (progress === 1)
					{
						item.isAnimationInProgress = false;
						resolve();
					}
				});
			},
		);
	},

	setChangedInPullRequest()
	{
		this.changedInPullRequest = true;
	},

	dropChangedInPullRequest()
	{
		this.changedInPullRequest = false;
	},

	isChangedInPullRequest()
	{
		return (this.changedInPullRequest === true);
	},

	/**
	 * @returns {boolean}
	 */
	isItemMoveDisabled()
	{
		const grid = this.getGrid();

		if (!grid.options.canChangeItemStage)
		{
			return true;
		}

		if (
			grid.getData().viewMode === BX.Crm.Kanban.ViewMode.MODE_ACTIVITIES
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
	},

	/**
	 * @returns {BX.CRM.Kanban.Grid}
	 */
	getGrid()
	{
		return BX.Kanban.Item.prototype.getGrid.call(this);
	},

	/**
	 * @returns {Object}
	 * @property {Object[]} activitiesByUser
	 * @property {number} activityCounterTotal
	 * @property {number} activityError
	 * @property {number} activityIncomingTotal
	 * @property {number} activityProgress
	 * @property {number} activityShow
	 * @property {string} activityStageId
	 * @property {number} activityTotal
	 * @property {string} assignedBy
	 * @property {Object[]} badges
	 * @property {Object} calendarSettings
	 * @property {Object} colorSettings
	 * @property {string} columnColor
	 * @property {string} columnId
	 * @property {string} companyId
	 * @property {string} contactId
	 * @property {string} contactType
	 * @property {string} currency
	 * @property {string} date
	 * @property {string} dateCreate
	 * @property {boolean} draggable
	 * @property {string} entity_currency
	 * @property {string} entity_price
	 * @property {Object[]} fields
	 * @property {string} id
	 * @property {boolean} isAutomationDebugItem
	 * @property {Object} lastActivity
	 * @property {string} link
	 * @property {string} modifyByAvatar
	 * @property {string} modifyById
	 * @property {string} name
	 * @property {number} page
	 * @property {string} pingSettings
	 * @property {number} price
	 * @property {string} price_formatted
	 * @property {Object[]} required
	 * @property {Object[]} required_fm
	 * @property {boolean} return
	 * @property {boolean} returnApproach
	 * @property {Object} sort
	 * @property {string | null} special_type
	 * @property {string | null} contactTooltip
	 * @property {string | null} companyTooltip
	 */
	getData()
	{
		return BX.Kanban.Item.prototype.getData.call(this);
	},
};
})();
