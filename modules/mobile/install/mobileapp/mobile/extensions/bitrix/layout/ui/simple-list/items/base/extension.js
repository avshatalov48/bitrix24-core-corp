(() => {
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
			this.itemDetailOpenHandler = props.itemDetailOpenHandler;
			this.params = (this.props.params || {});
			this.editable = (this.props.editable || false);
			this.section = (this.props.section !== undefined ? this.props.section : null);
			this.row = (this.props.row !== undefined ? this.props.row : null);
		}

		componentWillReceiveProps(props)
		{
			this.row = (props.row !== undefined ? props.row : null);
		}

		animate(callback)
		{
			if (this.elementRef)
			{
				this.elementRef.animate(
					{
						duration: 300,
						backgroundColor: '#FFECC4',
					},
					() => {
						this.elementRef.animate(
							{
								duration: 300,
								backgroundColor: '#FFFFFF',
							},
							() => {
								if (callback)
								{
									callback();
								}
							}
						)
					}
				);
			}
		}

		render()
		{
			const itemData = this.props.item.data;
			const activityTotal = (itemData.activityTotal || 0);

			return View(
				{
					style: styles.wrapper,
					onClick: () => this.itemDetailOpenHandler(this.props.item.id, this.props.item.data),
					onLongClick: this.isMenuEnabled() && (() => this.showMenuHandler(this.props.item.data.id))
				},
				View(
					{
						style: styles.item,
						ref: ref => this.elementRef = ref
					},
					View(
						{
							style: styles.header
						},
						Text({
							style: styles.title,
							text: (itemData.name || itemData.id)
						}),

						View(
							{
								style: styles.dateView,
							},
							FieldFactory.create(
								FieldFactory.Type.DATE,
								{
									value: itemData.date,
									readOnly: true,
									showTitle: false,
									config: {
										styles: {
											value: styles.date,
											readOnlyWrapper: {
												paddingTop: 4,
											}
										}
									}
								}
							),
							FieldFactory.create(
								FieldFactory.Type.STRING,
								{
									value: itemData.legend,
									readOnly: true,
									showTitle: false,
									hidden: !Boolean(itemData.legend),
									config: {
										numberOfLines: 1,
										ellipsize: 'end',
										styles: {
											value: styles.legend,
											readOnlyWrapper: {
												paddingTop: 0,
											}
										}
									}
								}
							),
						),
					),

					...this.renderMenuIcon(activityTotal),

					View(
						{
							style: styles.contentWrapper
						},

						this.getFields('fields'),
						this.getFields('userFields'),

						(
							this.blockManager.can('useConnectsBlock')
								? View(
									{
										style: styles.connectsContainer
									},
									this.renderConnects()
								)
								: null
						)
					)
				),
			)
		}

		isMenuEnabled()
		{
			return (
				this.blockManager.can('useItemMenu')
				&& this.props.hasActions
				&& this.editable
			);
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
							onClick: () => this.showMenuHandler(this.props.item.data.id)
						},
						ImageButton({
							style: styles.menu,
							svg: {
								content: this.getMenuFilledColor(svgImages.menu, activityTotal)
							},
							onClick: () => this.showMenuHandler(this.props.item.data.id)
						}),
					)
				);

				results.push(
					View(
						{
							style: styles.counterWrapper,
						},
						this.renderCounter(activityTotal)
					)
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
			const filledColor = this.hasCounter(counterValue) ? '#FF5752' : '#B9C0CA';
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
					name: (statuses[id] ? statuses[id].title : '').toLocaleUpperCase(Application.getLang()),
					backgroundColor:  (statuses[id] ? statuses[id].backgroundColor : '#E3F3CC'),
					color: (statuses[id] ? statuses[id].textColor : '#739F00'),
				}
			});
		}

		renderConnects()
		{
			return ConnectComponent({
				data: (this.props.item.data || {})
			});
		}

		getFields(section)
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
				};

				fields.push(FieldFactory.create(
					field.type,
					{
						title: field.title,
						value: field.value,
						readOnly: (field.params && field.params.readOnly),
						config: config,
					}
				));
			});

			return View(
				{
					style: {
						marginBottom: 0,
						flexDirection: 'column',
					}
				},
				...fields
			);
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
					value: styles[field.params.styleName]
				}
			}

			return {};
		}
	}

	function ConnectNotification()
		{
			return View(
				{
					style: {
						width: 6,
						height: 6,
						position: 'absolute',
						top: 0,
						right: 0,
						backgroundColor: '#FF5752',
						borderWidth: 1,
						borderRadius: 3,
						borderColor: '#ffffff'
					}
				},
			)
		}

	function ConnectItem({ value, svg, style }) {
		function getBackgroundColor(elements)
		{
			const enabledColor = '#e6f6fd';
			const disabledColor = '#f6f7f7';

			return (elements.length ? enabledColor : disabledColor);
		}

		function getFilledSvgContent(svg, elements)
		{
			return svg.content.replace(/%color%/g, getColor(elements));
		}

		function getColor(elements)
		{
			const enabledColor = '#378EE7';
			const disabledColor = '#B9C0CA';

			return (elements.length ? enabledColor : disabledColor);
		}

		return View(
			{
				style: {
					padding: 12,
					backgroundColor: getBackgroundColor(value),
					borderRadius: 20,
					alignItems: 'center',
					justifyContent: 'center',
					width: 42,
					height: 42,
					marginBottom: 10,
				}
			},
			View(
				{
					style: {
						...connectStyles.defaultIconWrapper,
						...style
					},
				},
				Image({
					style: {
						width: style.width - 3,
						height: style.height - 3,
					},
					svg: {
						content: getFilledSvgContent(svg, value),
					}
				}),
				(Array.isArray(value) && value.length ? ConnectNotification() : null),
			)
		);
	}

	function ConnectComponent(props) {
		const chats = (props.data.chat || []);
		const mails = (props.data.email || []);
		const phones = (props.data.phone || []);

		return View({},
			ConnectItem({ value: phones, svg: svgImages.phone, style: connectStyles.phoneIconWrapper }),
			ConnectItem({ value: chats, svg: svgImages.chat, style: connectStyles.chatIconWrapper }),
			ConnectItem({ value: mails, svg: svgImages.mail, style: connectStyles.mailsIconWrapper }),
		);
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
		mail: {
			content: '<svg width="18" height="12" viewBox="0 0 18 12" fill="none" xmlns="http://www.w3.org/2000/svg"><g><path d="M9.00002 4.88332L1.38462 0H16.6154L9.00002 4.88332Z" fill="%color%"/><path d="M18 1.47201L9.00002 7.66886L0 1.47198V10.9425C0 11.5273 0.579747 12 1.29438 12H16.7056C17.422 12 18 11.5266 18 10.9425V1.47201Z" fill="%color%"/></g></svg>',
		},
		chat: {
			content: '<svg width="17" height="15" viewBox="0 0 17 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.42474 0C1.53331 0 0 1.50968 0 3.37197V8.85776C0 10.6758 1.46133 12.1578 3.29057 12.2272V13.8739C3.29057 14.8298 4.42561 15.3494 5.16794 14.7332L8.18414 12.2297H13.5753C15.4667 12.2297 17 10.72 17 8.85776V3.37197C17 1.50968 15.4667 0 13.5753 0H3.42474Z" fill="%color%"/></svg>'
		},
		phone: {
			content: '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9 18C13.9706 18 18 13.9706 18 9C18 4.02944 13.9706 0 9 0C4.02944 0 0 4.02944 0 9C0 13.9706 4.02944 18 9 18ZM13.2578 11.1232L12.0073 10.1862C11.5632 9.85252 10.904 10.0132 10.5798 10.4712L10.2091 10.9967C10.1293 11.1056 9.96498 11.1397 9.85057 11.0737C8.70336 10.3253 7.72244 9.28096 7.04349 8.08927C6.97875 7.96587 7.01236 7.80485 7.133 7.728L7.658 7.37758C8.12796 7.06393 8.32144 6.4242 8.01512 5.95467L7.14051 4.67555C6.87546 4.28161 6.32068 4.2281 5.97243 4.55154C4.665 5.801 4.09464 7.97149 6.96255 11.0035C9.83041 14.0356 12.0089 13.5505 13.3163 12.3011C13.665 11.9775 13.6356 11.4102 13.2578 11.1232Z" fill="%color%"/></svg>'
		},
		menu: {
			content: '<svg width="21" height="5" viewBox="0 0 21 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.83871 2.41935C4.83871 3.75553 3.75553 4.83871 2.41935 4.83871C1.08318 4.83871 0 3.75553 0 2.41935C0 1.08318 1.08318 0 2.41935 0C3.75553 0 4.83871 1.08318 4.83871 2.41935Z" fill="%color%"/><path d="M10.1613 4.83871C11.4975 4.83871 12.5806 3.75553 12.5806 2.41935C12.5806 1.08318 11.4975 0 10.1613 0C8.82512 0 7.74194 1.08318 7.74194 2.41935C7.74194 3.75553 8.82512 4.83871 10.1613 4.83871Z" fill="%color%"/><path d="M17.9032 4.83871C19.2394 4.83871 20.3226 3.75553 20.3226 2.41935C20.3226 1.08318 19.2394 0 17.9032 0C16.5671 0 15.4839 1.08318 15.4839 2.41935C15.4839 3.75553 16.5671 4.83871 17.9032 4.83871Z" fill="%color%"/></svg>'
		}
	}

	const styles = {
		wrapper: {
			paddingBottom: 12,
			backgroundColor: '#f0f2f5',
		},
		item: {
			backgroundColor: '#FFFFFF',
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
			flexDirection: 'row',
			flexWrap: 'wrap',
			marginRight: 61,
			marginBottom: 5,
			marginLeft: 24,
		},
		title: {
			color: '#000000',
			fontWeight: 'bold',
			fontSize: 18,
			width: '100%',
		},
		dateView: {
			flexWrap: 'no-wrap',
			flexDirection: 'row',
		},
		date: {
			fontSize: 13,
			color: '#82888F',
			flex: 0,
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
			borderColor: '#ffffff',
		},
		contentWrapper: {
			position: 'relative',
			marginLeft: 24,
			marginRight: 14,
		},
		money: {
			fontSize: 21,
			//fontWeight: 'bold',
			color: '#333333',
		},
		client: {
			fontSize: 19,
			color: '#333333',
		},
		connectsContainer: {
			position: 'absolute',
			top: 20,
			right: 0,
		},
		menuContainer: (hasCounter) => ({
			position: 'absolute',
			top: 8,
			right: 11,
			width: 42,
			height: 42,
			padding: 9,
			backgroundColor: (hasCounter ? '#ffdcdb' : '#ffffff'),
			borderRadius: 20,
			justifyContent: 'center',
			alignItems: 'center',
		}),
	}

	const connectStyles = {
		defaultIconWrapper: {
			position: 'relative',
			alignItems: 'center',
			justifyContent: 'center',
		},
		phoneIconWrapper: {
			width: 21,
			height: 21,
		},
		chatIconWrapper: {
			width: 20,
			height: 18,
		},
		mailsIconWrapper: {
			width: 21,
			height: 15,
		}
	}

	this.ListItems = this.ListItems || {};
	this.ListItems.Base = Base;
})();
