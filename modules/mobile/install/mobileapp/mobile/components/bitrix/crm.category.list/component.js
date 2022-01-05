(() => {
	const categories = [
		{
			id: 0,
			name: 'Sales Supporting Supporting Supporting Supporting',
			tunnels: [
				{
					categoryName: 'Supporting',
					stageName: 'Supporting',
					color: '#3A6BE8',
				},
				{
					categoryName: 'New',
					stageName: 'Supporting',
					color: '#A4A4A4',
				},
			],
		},
		{
			id: 1,
			name: 'Supporting',
			tunnels: [
				{
					categoryName: 'Supporting',
					stageName: 'Supporting',
					color: '#3A6BE8',
				},
				{
					categoryName: 'New',
					stageName: 'Supporting',
					color: '#A4A4A4',
				},
				{
					categoryName: 'Preparing',
					stageName: 'Supporting',
					color: '#FFF058',
				},
			],
		},
		{
			id: 2,
			name: 'Another',
			tunnels: []
		}
	];
	const icons = {
		addNewCategoryIcon: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 0H10V17H7V0Z" fill="#91969F"/><path d="M17 7V10L0 10L1.19209e-07 7L17 7Z" fill="#91969F"/></svg>`,
		funnel: `<svg width="19" height="14" viewBox="0 0 19 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.812168 0H18.1881C18.6365 0 19 0.345544 19 0.771798C19 0.858197 18.9847 0.943985 18.9549 1.02558L18.4298 2.45893C18.3162 2.7691 18.0086 2.97695 17.663 2.97695H1.33293C0.986659 2.97695 0.678494 2.76821 0.56546 2.45709L0.0447001 1.02375C-0.101682 0.620841 0.123258 0.181423 0.547118 0.0422786C0.632384 0.0142877 0.721961 0 0.812168 0ZM3.67407 5.50289H15.3262C15.7746 5.50289 16.1381 5.84844 16.1381 6.2747C16.1381 6.36724 16.1206 6.45902 16.0864 6.54567L15.521 7.97901C15.4022 8.28027 15.0992 8.47985 14.7608 8.47985H4.2094C3.86569 8.47985 3.55923 8.27414 3.44424 7.96625L2.90891 6.5329C2.75889 6.13121 2.97985 5.68997 3.40243 5.54737C3.48965 5.51793 3.58152 5.50289 3.67407 5.50289ZM7.29162 11.0231H11.7086C12.157 11.0231 12.5205 11.3686 12.5205 11.7949C12.5205 11.8699 12.509 11.9446 12.4863 12.0166L12.0341 13.4499C11.9311 13.7764 11.615 14 11.2564 14H7.79284C7.4431 14 7.13266 13.7871 7.02234 13.4717L6.52112 12.0383C6.37967 11.6338 6.60997 11.1969 7.03551 11.0625C7.1181 11.0364 7.20458 11.0231 7.29162 11.0231Z" fill="#525C69"/></svg>`,
		selectedCategory: `<svg width="19" height="15" viewBox="0 0 19 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.47688 14.351L0 8.03878L2.26691 5.82949L6.47688 9.93245L16.0025 0.648972L18.2694 2.85826L6.47688 14.351Z" fill="#378EE7"/></svg>`,
		tunnelArrow: `<svg width="5" height="8" viewBox="0 0 5 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M0 0.880785L2.55719 3.37704L3.21954 3.99978L2.55719 4.62289L0 7.11914L0.902358 8L5 4L0.902358 0L0 0.880785Z" fill="#989DA5"/></svg>`,
		pen: `<svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd" d="M11.5505 0.708708C11.9426 0.31773 12.5779 0.319865 12.9674 0.71347L14.2992 2.05937C14.6867 2.45089 14.6846 3.08201 14.2945 3.47092L5.28648 12.4522L2.54781 9.68469L11.5505 0.708708ZM0.00953897 14.6436C-0.0163586 14.7416 0.0113888 14.8452 0.0816823 14.9173C0.153826 14.9894 0.257416 15.0172 0.355457 14.9894L3.41693 14.1646L0.834563 11.5831L0.00953897 14.6436Z" fill="#767C87"/></svg>`,
		cancelIcon: `<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><g opacity="0.22"><path d="M2.95028e-07 1.46451L1.41421 0.0502943L13.8995 12.5356L12.4853 13.9498L2.95028e-07 1.46451Z" fill="#515E68"/><path d="M12.4853 0.050293L13.8995 1.46451L1.41421 13.9498L0 12.5356L12.4853 0.050293Z" fill="#515E68"/></g></svg>`,
	}

	const styles = {
		container: {
			backgroundColor: '#EEF3F5',
			alignItems: 'center',
		},
		titleContainer: {
			width: '100%',
			padding: 16,
		},
		titleText: {
			color: '#525C69',
			fontWeight: 'bold',
			fontSize: 16,
			marginRight: 'auto',
		},
		scrollViewContainer: {
			width: '100%',
		},
		categoriesListWrap: {
			paddingBottom: 45,
		},
		categoriesListContainer: {
			width: '100%',
			borderRadius: 12,
			backgroundColor: '#ffffff',
			marginBottom: 45,
		},
		categoriesListHeader: {
			paddingTop: 21,
			paddingBottom: 22,
			paddingLeft: 25,
			paddingRight: 25,
			flexDirection: 'row',
			alignItems: 'center',
		},
		categoriesListIcon: {
			width: 17,
			height: 17,
			marginRight: 17,
		},
		categoriesListText: {
			color: '#91969F',
			fontSize: 18,
			flex: 1,
		},
		categoryContainer: {
			flexDirection: 'row',
			paddingLeft: 24,
			width: '100%',
		},
		categoryFunnelIcon: (hasTunnels) => ({
			width: 19,
			height: 14,
			marginRight: 16,
			marginTop: hasTunnels ? 17 : 27,
		}),
		categoryContentWrapper: {
			flexDirection: 'row',
			flex: 1,
			borderTopWidth: 1,
			borderTopColor: '#EEF3F5',
		},
		categoryContent: (hasTunnels) => ({
			marginRight: 8,
			flex: 1,
			paddingLeft: 3,
			paddingTop: hasTunnels ? 10 : 20,
			paddingBottom: hasTunnels ? 13 : 23,
		}),
		categoryTextWrapper: {
			flexDirection: 'row',
			alignItems: 'center',
		},
		categoryText: {
			color: '#333333',
			fontSize: 18,
			marginRight: 8,
			flexShrink: 1,
		},
		categoryEdit: {
			width: 48,
			marginLeft: 6,
			marginRight: 6,
		},
		categoryEditIcon: (hasTunnels) => ({
			width: 13,
			height: 13,
			marginLeft: 23,
			marginTop: hasTunnels ? 17 : 28,
		}),
		categorySelectedIcon: {
			width: 18,
			height: 14,
		},
		tunnelContainer: {
			flexDirection: 'row',
			alignItems: 'center',
			marginTop: 3,
			marginRight: 8,
		},
		tunnelTextWrapper: {
			flexDirection: 'row',
			flex: 1,
		},
		tunnelText: {
			color: '#767C87',
			opacity: 0.55,
			fontSize: 13,
			maxWidth: '50%',
		},
		tunnelArrowIcon: {
			width: 5,
			height: 8,
			marginLeft: 4,
			marginRight: 5,
		},
		tunnelColorIcon: {
			width: 13,
			height: 11,
			marginRight: 4,
		},
		tunnelTitle: {
			color: '#767C87',
			opacity: 0.55,
			fontSize: 13,
			fontWeight: 'bold',
		},
		counterContainer: {
			backgroundColor: '#FF5752',
			borderRadius: 10,
			paddingLeft: 7,
			paddingRight: 7,
			marginTop: 15,
			height: 20,
			justifyContent: 'center',
			alignItems: 'center',
		},
		counterText: {
			color: '#ffffff',
			fontSize: 12,
			textAlign: 'center',
		}
	}
	class CategoryList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				categories: props.categories,
				currentCategoryId: props.currentCategoryId,
			}
		}

		render()
		{
			const categories = this.state.categories;
			return View(
				{
					style: styles.container,
				},
				View(
					{
						style: styles.titleContainer,
					},
					Text(
						{
							style: styles.titleText,
							text: BX.message('CRM_CATEGORY_LIST_TITLE'),
						}
					)
				),
				ScrollView(
					{
						style: styles.scrollViewContainer,
					},
					View(
						{
							style: styles.categoriesListWrap,
						},
						View(
							{
								style: styles.categoriesListContainer,
							},
							View(
								{
									style: styles.categoriesListHeader,
								},
								Image(
									{
										style: styles.categoriesListIcon,
										svg: {
											content: icons.addNewCategoryIcon
										},
									}
								),
								Text(
									{
										style: styles.categoriesListText,
										text: BX.message('CRM_CATEGORY_LIST_NEW_CATEGORY'),
									}
								)
							),
							...categories.map((category) => this.renderCategory(category)),
						),
					)
				),
			)
		}

		renderCategory(category)
		{
			const hasTunnels = Array.isArray(category.tunnels) && category.tunnels.length;
			return View(
				{
					style: styles.categoryContainer,
				},
				Image(
					{
						style: styles.categoryFunnelIcon(hasTunnels),
						resizeMode: 'center',
						svg: {
							content: icons.funnel
						},
					}),
				View(
					{
						style: styles.categoryContentWrapper,
					},
					View(
						{
							style: styles.categoryContent(hasTunnels),
						},
						View(
							{
								style: styles.categoryTextWrapper,
							},
							Text(
								{
									style: styles.categoryText,
									ellipsize: 'end',
									numberOfLines: 1,
									text: category.name
								}
							),
							Number.isInteger(this.state.currentCategoryId) && this.state.currentCategoryId === category.id ? this.renderSelectedCategoryIcon() : null,
						),
						hasTunnels ? this.renderTunnels(category.tunnels) : null
					),
					hasTunnels ? this.renderCounter(category.tunnels.length.toString()) : null,
					View(
						{
							style: styles.categoryEdit,
						},
						Image({
							style: styles.categoryEditIcon(hasTunnels),
							svg: {
								content: icons.pen
							},
						}),
					)
				),
			)
		}

		renderSelectedCategoryIcon()
		{
			return Image({
				style: styles.categorySelectedIcon,
				resizeMode: 'center',
				svg: {
					content: icons.selectedCategory,
				},
			})
		}

		renderTunnels(tunnels)
		{
			return 	View(
				{},
				...tunnels.map((tunnel) => this.renderTunnel(tunnel))
			)
		}

		renderTunnel(tunnel)
		{
			return View(
				{
					style: styles.tunnelContainer,
				},
				Text(
					{
						style: styles.tunnelTitle,
						text: BX.message('CRM_CATEGORY_LIST_TUNNEL_TITLE'),
					}
				),
				Image({
						style: styles.tunnelArrowIcon,
						resizeMode: 'center',
						svg: {
							content: icons.tunnelArrow
						},
					},
				),
				Image({
						style: styles.tunnelColorIcon,
						resizeMode: 'center',
						svg: {
							content: `<svg width="13" height="11" viewBox="0 0 13 11" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 2C0 0.895431 0.895431 0 2 0L8.52745 0C9.22536 0 9.87278 0.3638 10.2357 0.959904L13 5.5L10.2357 10.0401C9.87278 10.6362 9.22536 11 8.52745 11H2C0.895432 11 0 10.1046 0 9V2Z" fill="${tunnel.color.replace(/[^#0-9a-fA-F]/g,'')}"/></svg>`,
						},
					},
				),
				View(
					{
						style: styles.tunnelTextWrapper,
					},
					Text(
						{
							style: styles.tunnelText,
							ellipsize: 'middle',
							numberOfLines: 1,
							text: `${tunnel.categoryName}`,
						}
					),
					Text(
						{
							style: styles.tunnelText,
							ellipsize: 'end',
							numberOfLines: 1,
							text: `/${tunnel.stageName}`,
						}
					)
				),
			)
		}

		renderCounter(counterValue)
		{
			return View({
					style: styles.counterContainer,
				},
				Text({
					style: styles.counterText,
					text: counterValue,
				}),
			);
		}
	}

	const currentCategoryId = 0;
	layout.showComponent(new CategoryList({categories, currentCategoryId}));
})();