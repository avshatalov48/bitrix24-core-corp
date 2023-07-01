(() => {

	const require = (ext) => jn.require(ext);

	const { ClientType } = require('layout/ui/fields/client');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { FieldFactory, StringType } = require('layout/ui/fields');
	const { CounterComponent } = require('layout/ui/kanban/counter');
	const { CommunicationButton } = require('crm/communication/button');
	const { chain, transition } = require('animation');
	const { Haptics } = require('haptics');
	const { mergeImmutable, get } = require('utils/object');
	const { CounterView } = require('layout/ui/counter-view');
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { Moment } = require('utils/date');
	const { longDate, dayMonth } = require('utils/date/formats');

	/**
	 * @class ListItems.Base
	 */
	class Base extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.blockManager = new ItemLayoutBlocksManager(this.props.itemLayoutOptions);
			this.showMenuHandler = props.showMenuHandler;
			this.styles = styles;

			/** @var {CommunicationButton} */
			this.communicationButtonRef = null;

			this.showCommunicationButton = this.showCommunicationButton.bind(this);
		}

		get testId()
		{
			return this.props.testId || '';
		}

		get params()
		{
			return this.props.params || {};
		}

		blink(callback = null, showUpdated = true)
		{
			this.setLoading(this.dropLoading.bind(this, callback, showUpdated));
		}

		render()
		{
			const { item } = this.props;
			const itemData = item.data;
			const activityTotal = (itemData.activityTotal || 0);

			const customStyles = this.getCustomStyles();
			let wrapperStyle = BX.prop.getObject(customStyles, 'wrapper', {});
			wrapperStyle = mergeImmutable(styles.wrapper, wrapperStyle);

			return View(
				{
					style: wrapperStyle,
					testId: `${this.testId}_ITEM_${item.id}`,
					onClick: () => this.props.itemDetailOpenHandler(item.id, item.data, this.params),
					onLongClick: this.isMenuEnabled() && (() => {
						Haptics.impactLight();
						this.showMenuHandler(item.data.id);
					}),
				},
				View(
					{
						style: styles.item,
						ref: ref => this.elementRef = ref,
					},
					View(
						{
							testId: `${this.testId}_SECTION`,
							style: styles.header,
						},
						Text({
							testId: `${this.testId}_SECTION_TITLE`,
							style: styles.title,
							text: (itemData.name || itemData.id),
							numberOfLines: 2,
							ellipsize: 'end',
						}),
						View(
							{
								testId: `${this.testId}_SECTION_DATE`,
								style: styles.dateView,
							},
							this.renderSubTitle(itemData),
							Boolean(itemData.legend) && FieldFactory.create(StringType, {
								value: itemData.legend,
								readOnly: true,
								showTitle: false,
								config: {
									numberOfLines: 1,
									ellipsize: 'end',
									styles: {
										value: styles.legend,
										readOnlyWrapper: {
											paddingTop: 0,
										},
									},
								},
							}),
						),
					),

					...this.renderMenuIcon(activityTotal),
					this.renderContent(itemData),
				),
			);
		}

		getCustomStyles()
		{
			return BX.prop.getObject(this.props, 'customStyles', {});
		}

		renderContent(itemData)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'flex-start',
					},
				},
				View(
					{
						style: {
							flex: 1,
						},
					},
					...this.renderSpecialFields(itemData),
					this.renderFields('fields'),
					this.renderFields('userFields'),
					...this.renderBottomSpecialFields(itemData),
				),
				this.renderRightBlock(),
			);
		}

		isMenuEnabled()
		{
			return (this.blockManager.can('useItemMenu') && this.props.hasActions);
		}

		renderSubTitle(data)
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				...this.getSubTitleComponents(data),
			);
		}

		getSubTitleComponents(data)
		{
			return [
				this.renderDate(data),
			];
		}

		renderDate(data)
		{
			const moment = Moment.createFromTimestamp(data.date);
			const defaultFormat = moment.inThisYear ? dayMonth() : longDate();

			return (new FriendlyDate({
				moment,
				defaultFormat,
				showTime: true,
				useTimeAgo: true,
				style: {
					...this.styles.date,
					paddingTop: 4,
				},
			}));
		}

		renderMenuIcon(activityTotal)
		{
			activityTotal = (activityTotal || 0);
			const results = [];

			if (this.isMenuEnabled())
			{
				results.push(
					View(
						{
							style: styles.menuContainer(this.hasCounter(activityTotal)),
							onClick: () => this.showMenuHandler(this.props.item.data.id),
						},
						ImageButton({
							testId: `${this.testId}_CONTEXT_MENU_BTN`,
							style: styles.menu,
							svg: {
								content: this.getMenuFilledColor(svgImages.menu, activityTotal),
							},
							onClick: () => this.showMenuHandler(this.props.item.data.id),
						}),
					),
				);
			}

			return results;
		}

		/**
		 * @param {String|function} property
		 * @returns {boolean}
		 */
		isVisible(property)
		{
			if (typeof property === 'function')
			{
				return property(this.props);
			}

			return (this.props.item.data.hasOwnProperty(property) && this.props.item.data[property].length);
		}

		getClientName()
		{
			const itemData = (this.props.item.data || {});
			return (itemData.contactName && itemData.companyName)
				? itemData.companyName + ' (' + itemData.contactName + ')'
				: (itemData.contactName || itemData.companyName || '');
		}

		hasCounter(counterValue)
		{
			return Number.isInteger(counterValue) && counterValue > 0;
		}

		getMenuFilledColor(svg, counterValue)
		{
			const filledColor = this.hasCounter(counterValue) ? '#ff5752' : '#bdc1c6';
			return svg.content.replace(/%color%/g, filledColor);
		}

		renderCounter(value)
		{
			return this.hasCounter(value) ? CounterView(value) : null;
		}

		getStatuses(fieldStatuses = [])
		{
			if (!Array.isArray(fieldStatuses) || !fieldStatuses.length)
			{
				return [];
			}

			const statuses = (this.params.statuses || {});
			return fieldStatuses.map(id => {
				return {
					name: (statuses[id] ? statuses[id].title : '').toLocaleUpperCase(env.languageId),
					backgroundColor: (statuses[id] ? statuses[id].backgroundColor : '#e3f3cc'),
					color: (statuses[id] ? statuses[id].textColor : '#739f00'),
				};
			});
		}

		renderRightBlock()
		{
			const useConnectsBlock = this.blockManager.can('useConnectsBlock');
			const useCountersBlock = this.blockManager.can('useCountersBlock');

			if (!useConnectsBlock && !useCountersBlock)
			{
				return null;
			}

			const { item } = this.props;

			return View(
				{
					style: {
						justifyContent: 'center',
						flexDirection: 'column',
						alignItems: 'center',
						width: (Application.getPlatform() === 'android' ? 80 : 76),
					},
				},
				useCountersBlock && new CounterComponent({
					...item.data.counters,
					onClick: this.props.itemDetailOpenHandler && (() => this.props.itemDetailOpenHandler(
						item.id,
						item.data,
						{ ...this.params, activeTab: TabType.TIMELINE },
					)),
					onLongClick: this.props.itemCounterLongClickHandler && (() => {
						Haptics.impactLight();
						this.props.itemCounterLongClickHandler('activity', item.id);
					}),
				}),
				useConnectsBlock && this.renderConnectionComponent(),
			);
		}

		renderConnectionComponent()
		{
			const { item } = this.props;
			const ownerTypeName = this.params.entityTypeName;
			const hasTelegramConnection = get(this.params, 'connectors.telegram', true);
			const openLinesAccess = get(this.params, 'entityPermissions.openLinesAccess', false);

			return View(
				{
					style: {
						flexDirection: 'column',
						alignItems: 'center',
						justifyContent: 'center',
						width: 76,
					},
					onClick: this.showCommunicationButton,
				},
				new CommunicationButton({
					ref: (ref) => this.communicationButtonRef = ref,
					border: false,
					horizontal: false,
					showTelegramConnection: !hasTelegramConnection,
					value: item.data[ClientType],
					permissions: {
						openLinesAccess,
					},
					ownerInfo: {
						ownerId: item.id,
						ownerTypeName,
					},
				}),
			);
		}

		showCommunicationButton()
		{
			if (this.communicationButtonRef)
			{
				this.communicationButtonRef.showMenu();
			}
		}

		renderSpecialFields(data)
		{
			return [];
		}

		renderBottomSpecialFields(data)
		{
			return [];
		}

		renderFields(section)
		{
			const data = this.props.item.data;
			if (!data[section])
			{
				return null;
			}

			const fields = [];
			data[section].map(field => {
				const config = {
					...(field.config || {}),
					styles: this.getFieldStyle(field),
					reloadEntityListFromProps: true,
					ellipsize: true,
					deepMergeStyles: this.getFieldDeepMergeStyles(),
				};

				const fieldComponent = FieldFactory.create(field.type, {
					testId: `${this.testId}_${field.type}_${field.name}`.toUpperCase(),
					title: field.title,
					value: field.value,
					readOnly: (field.params && field.params.readOnly),
					config: config,
					multiple: (field.multiple || false),
					isShowAnimate: (field.isShowAnimate || false),
				});
				if (fieldComponent)
				{
					fields.push(fieldComponent);
				}
				else
				{
					console.warn(`Field ${field.title} with type ${field.type} is not yet supported.`);
				}
			});

			return View(
				{
					testId: `${this.testId}_FIELDS_LIST`,
					style: {
						marginBottom: 0,
						flexDirection: 'column',
					},
				},
				...fields,
			);
		}

		getFieldDeepMergeStyles()
		{
			return {
				externalWrapper: {
					marginLeft: 24,
				},
			};
		}

		getFieldStyle(field)
		{
			if (!field.params || !field.params.styleName)
			{
				return {};
			}

			if (styles[field.params.styleName])
			{
				return {
					value: styles[field.params.styleName],
				};
			}

			return {};
		}

		setLoading(callback = null)
		{
			if (!this.elementRef)
			{
				return null;
			}

			const duration = 300;
			const opacity = 0.5;

			this.elementRef.animate({ duration, opacity }, callback);
		}

		dropLoading(callback = null, blink = true)
		{
			if (!this.elementRef)
			{
				return null;
			}

			const transitionToBeige = transition(this.elementRef, {
				duration: 300,
				backgroundColor: '#ffe9be',
				opacity: 1,
			});

			const transitionToWhite = transition(this.elementRef, {
				duration: 300,
				backgroundColor: '#ffffff',
				opacity: 1,
			});

			const animate = (
				blink
					? chain(
						transitionToBeige,
						transitionToWhite,
					)
					: chain(
						transitionToWhite,
					)
			);

			animate().then(callback);
		}

		prepareActions(actions)
		{
			// may be implemented in a child class
		}
	}

	class ItemLayoutBlocksManager
	{
		constructor(options = {})
		{
			this.options = options;
		}

		can(...params)
		{
			return params.every(param => {
				return (this.options[param] || false);
			});
		}
	}

	const svgImages = {
		// marker: {
		// 	content: '<svg width="13" height="11" viewBox="0 0 13 11" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 0L8.52745 0C9.22536 0 9.87278 0.3638 10.2357 0.959904L13 5.5L10.2357 10.0401C9.87278 10.6362 9.22536 11 8.52745 11H0V0Z" fill="#2FC6F6"/></svg>',
		// },
		separator: {
			content: '<svg width="5" height="6" viewBox="0 0 5 6" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.2" d="M2.61816 5.13232C3.84131 5.13232 4.84473 4.12891 4.84473 2.90576C4.84473 1.68262 3.84131 0.679199 2.61816 0.679199C1.39502 0.679199 0.391602 1.68262 0.391602 2.90576C0.391602 4.12891 1.39502 5.13232 2.61816 5.13232Z" fill="#515E68"/></svg>',
		},
		menu: {
			content: '<svg width="21" height="5" viewBox="0 0 21 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.83871 2.41935C4.83871 3.75553 3.75553 4.83871 2.41935 4.83871C1.08318 4.83871 0 3.75553 0 2.41935C0 1.08318 1.08318 0 2.41935 0C3.75553 0 4.83871 1.08318 4.83871 2.41935Z" fill="%color%"/><path d="M10.1613 4.83871C11.4975 4.83871 12.5806 3.75553 12.5806 2.41935C12.5806 1.08318 11.4975 0 10.1613 0C8.82512 0 7.74194 1.08318 7.74194 2.41935C7.74194 3.75553 8.82512 4.83871 10.1613 4.83871Z" fill="%color%"/><path d="M17.9032 4.83871C19.2394 4.83871 20.3226 3.75553 20.3226 2.41935C20.3226 1.08318 19.2394 0 17.9032 0C16.5671 0 15.4839 1.08318 15.4839 2.41935C15.4839 3.75553 16.5671 4.83871 17.9032 4.83871Z" fill="%color%"/></svg>',
		},
	};

	const styles = {
		wrapper: {
			paddingBottom: 12,
			backgroundColor: '#f5f7f8',
		},
		item: {
			backgroundColor: '#ffffff',
			borderRadius: 12,
			paddingTop: 17,
			paddingBottom: 17,
			position: 'relative',
		},
		// marker: {
		// 	position: 'absolute',
		// 	top: 24,
		// 	left: 0,
		// 	width: 13,
		// 	height: 11,
		// 	flexGrow: 0,
		// },
		header: {
			flexDirection: 'column',
			marginRight: 56,
			marginBottom: 4,
			marginLeft: 24,
		},
		title: {
			color: '#000000',
			fontWeight: 'bold',
			fontSize: 18,
		},
		dateView: {
			flexWrap: 'no-wrap',
			flexDirection: 'row',
		},
		date: {
			fontSize: 13,
			color: '#828b95',
			flexShink: 2,
		},
		legend: {
			flex: 0,
			marginLeft: 10,
		},
		menu: {
			width: 22,
			height: 22,
		},
		counterWrapper: {
			position: 'absolute',
			top: 4,
			right: 6,
			borderRadius: 10,
			borderWidth: 2,
		},
		client: {
			fontSize: 19,
			color: '#333333',
		},
		menuContainer: (hasCounter) => ({
			position: 'absolute',
			top: 8,
			right: Application.getPlatform() === 'android' ? 18 : 16,
			width: 42,
			height: 42,
			padding: 9,
			backgroundColor: (hasCounter ? '#ffdcdb' : ''),
			borderRadius: 20,
			justifyContent: 'center',
			alignItems: 'center',
		}),
	};

	this.ListItems = this.ListItems || {};
	this.ListItems.Base = Base;
})();
