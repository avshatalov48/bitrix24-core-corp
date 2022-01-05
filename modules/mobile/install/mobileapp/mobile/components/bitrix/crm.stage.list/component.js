(()=> {
	const stages = [
		{
			"id": '1',
			"name": "NEW 11111111111111111111111111111",
			"sort": "10",
			"color": "#00A8EF",
			"semantics": "process",
			"counter": '2',
			"tunnels" : [],
		},
		{
			"id": '2',
			"name": "PREPARATION",
			"sort": "20",
			"color": "#ffA8EF",
			"semantics": "process",
			"counter": '0',
			"tunnels" : [
				{
					"categoryName": "Newwww wwwww wwwwww wwwww",
					"stageName": "Supportingggggggggggggggggggggg",
					"color": "#3A6BE8",
				},
				{
					"categoryName": "New",
					"stageName": "Supporting",
					"color": "#A4A4A4",
				},
			],
		},
		{
			"id": '3',
			"name": "PREPAYMENT_INVOICE",
			"sort": "30",
			"color": "#55D0E0",
			"semantics": "process",
			"counter": '1',
			"tunnels" : [],
		},
		{
			"id": '4',
			"name": "EXECUTING 11111111111111111",
			"sort": "40",
			"color": "#47E4C2",
			"semantics": "process",
			"counter": '0',
			"tunnels" : [
				{
					"categoryName": "New",
					"stageName": "NPS",
					"color": "#3A6BE8",
				},
				{
					"categoryName": "New",
					"stageName": "Supporting",
					"color": "#A4A4A4",
				},
				{
					"categoryName": "Preparing",
					"stageName": "Supporting",
					"color": "#FFF058",
				},
			],
		},
		{
			"id": '5',
			"name": "FINAL_INVOICE",
			"sort": "50",
			"color": "#FFA900",
			"semantics": "process",
			"counter": '0',
			"tunnels" : [],
		},
		{
			"id": '6',
			"name": "WON",
			"sort": "60",
			"color": "#7BD500",
			"semantics": "success",
			"hint": "Close the deal",
			"counter": '0',
			"tunnels" : [
				{
					"categoryName": "New",
					"stageName": "Supporting",
					"color": "#3A6BE8",
				},
			],
		},
		{
			"id": '7',
			"name": "LOSE",
			"sort": "70",
			"color": "#FF5752",
			"semantics": "failure",
			"counter": '0',
			"tunnels" : [],
		},
		{
			"id": '8',
			"name": "APOLOGY",
			"sort": "80",
			"color": "#FF5752",
			"semantics": "apology",
			"counter": '0',
			"tunnels" : [
				{
					"categoryName": "New",
					"stageName": "Supporting",
					"color": "#3A6BE8",
				},
			],
		}
	]

	const icons = {
		funnel: `<svg width="19" height="14" viewBox="0 0 19 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.812168 0H18.1881C18.6365 0 19 0.345544 19 0.771798C19 0.858197 18.9847 0.943985 18.9549 1.02558L18.4298 2.45893C18.3162 2.7691 18.0086 2.97695 17.663 2.97695H1.33293C0.986659 2.97695 0.678494 2.76821 0.56546 2.45709L0.0447001 1.02375C-0.101682 0.620841 0.123258 0.181423 0.547118 0.0422786C0.632384 0.0142877 0.721961 0 0.812168 0ZM3.67407 5.50289H15.3262C15.7746 5.50289 16.1381 5.84844 16.1381 6.2747C16.1381 6.36724 16.1206 6.45902 16.0864 6.54567L15.521 7.97901C15.4022 8.28027 15.0992 8.47985 14.7608 8.47985H4.2094C3.86569 8.47985 3.55923 8.27414 3.44424 7.96625L2.90891 6.5329C2.75889 6.13121 2.97985 5.68997 3.40243 5.54737C3.48965 5.51793 3.58152 5.50289 3.67407 5.50289ZM7.29162 11.0231H11.7086C12.157 11.0231 12.5205 11.3686 12.5205 11.7949C12.5205 11.8699 12.509 11.9446 12.4863 12.0166L12.0341 13.4499C11.9311 13.7764 11.615 14 11.2564 14H7.79284C7.4431 14 7.13266 13.7871 7.02234 13.4717L6.52112 12.0383C6.37967 11.6338 6.60997 11.1969 7.03551 11.0625C7.1181 11.0364 7.20458 11.0231 7.29162 11.0231Z" fill="#525C69"/></svg>`,
		combinedShape: `<svg width="6" height="14" viewBox="0 0 6 14" fill="none" xmlns="http://www.w3.org/2000/svg"><g opacity="0.54"><path d="M2 0H0V2H2V0Z" fill="#999999"/><path d="M6 0H4V2H6V0Z" fill="#999999"/><path d="M0 4H2V6H0V4Z" fill="#999999"/><path d="M6 4H4V6H6V4Z" fill="#999999"/><path d="M0 8H2V10H0V8Z" fill="#999999"/><path d="M6 8H4V10H6V8Z" fill="#999999"/><path d="M0 12H2V14H0V12Z" fill="#999999"/><path d="M6 12H4V14H6V12Z" fill="#999999"/></g></svg>`,
		select: `<svg width="13" height="14" viewBox="0 0 13 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 1C0 0.447715 0.447715 0 1 0H2C2.55228 0 3 0.447715 3 1C3 1.55228 2.55228 2 2 2H1C0.447715 2 0 1.55228 0 1Z" fill="#999999"/><path d="M0 5C0 4.44772 0.447715 4 1 4H2C2.55228 4 3 4.44772 3 5C3 5.55228 2.55228 6 2 6H1C0.447715 6 0 5.55228 0 5Z" fill="#999999"/><path d="M0 9C0 8.44772 0.447715 8 1 8H2C2.55228 8 3 8.44772 3 9C3 9.55228 2.55228 10 2 10H1C0.447715 10 0 9.55228 0 9Z" fill="#999999"/><path d="M0 13C0 12.4477 0.447715 12 1 12H2C2.55228 12 3 12.4477 3 13C3 13.5523 2.55228 14 2 14H1C0.447715 14 0 13.5523 0 13Z" fill="#999999"/><path d="M4 1C4 0.447715 4.44772 0 5 0H12C12.5523 0 13 0.447715 13 1C13 1.55228 12.5523 2 12 2H5C4.44772 2 4 1.55228 4 1Z" fill="#999999"/><path d="M4 5C4 4.44772 4.44772 4 5 4H12C12.5523 4 13 4.44772 13 5C13 5.55228 12.5523 6 12 6H5C4.44772 6 4 5.55228 4 5Z" fill="#999999"/><path d="M4 9C4 8.44772 4.44772 8 5 8H12C12.5523 8 13 8.44772 13 9C13 9.55228 12.5523 10 12 10H5C4.44772 10 4 9.55228 4 9Z" fill="#999999"/><path d="M4 13C4 12.4477 4.44772 12 5 12H12C12.5523 12 13 12.4477 13 13C13 13.5523 12.5523 14 12 14H5C4.44772 14 4 13.5523 4 13Z" fill="#999999"/></svg>`,
		stageItem: `<svg width="218" height="37" viewBox="0 0 218 37" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.335938 4C0.335938 1.79086 2.1268 0 4.33594 0L205.063 0C206.975 0 208.719 1.09002 209.557 2.80823L217.21 18.5L209.557 34.1918C208.719 35.91 206.975 37 205.063 37H4.33594C2.1268 37 0.335938 35.2091 0.335938 33V4Z" fill="#EFF4F7"/><path d="M1.03594 4C1.03594 2.17746 2.5134 0.7 4.33594 0.7H205.063C206.707 0.7 208.207 1.63742 208.928 3.11508L216.431 18.5L208.928 33.8849C208.207 35.3626 206.707 36.3 205.063 36.3H4.33594C2.5134 36.3 1.03594 34.8225 1.03594 33V4Z" stroke="#515E68" stroke-opacity="0.18" stroke-width="1.4"/></svg>`,
		rightTriangle: `<svg xmlns="http://www.w3.org/2000/svg" version="1.1" class="svg-triangle" width='29' height='36'><polygon points="5,18 0,36 0,0"/></svg>`,
		pen: `<svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd" d="M11.5505 0.708708C11.9426 0.31773 12.5779 0.319865 12.9674 0.71347L14.2992 2.05937C14.6867 2.45089 14.6846 3.08201 14.2945 3.47092L5.28648 12.4522L2.54781 9.68469L11.5505 0.708708ZM0.00953897 14.6436C-0.0163586 14.7416 0.0113888 14.8452 0.0816823 14.9173C0.153826 14.9894 0.257416 15.0172 0.355457 14.9894L3.41693 14.1646L0.834563 11.5831L0.00953897 14.6436Z" fill="#767C87"/></svg>`,
		stageInfoDefault: `<svg width="218" height="37" viewBox="0 0 218 37" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.335938 4C0.335938 1.79086 2.1268 0 4.33594 0L205.063 0C206.975 0 208.719 1.09002 209.557 2.80823L217.21 18.5L209.557 34.1918C208.719 35.91 206.975 37 205.063 37H4.33594C2.1268 37 0.335938 35.2091 0.335938 33V4Z" fill="#EFF4F7"/><path d="M1.03594 4C1.03594 2.17746 2.5134 0.7 4.33594 0.7H205.063C206.707 0.7 208.207 1.63742 208.928 3.11508L216.431 18.5L208.928 33.8849C208.207 35.3626 206.707 36.3 205.063 36.3H4.33594C2.5134 36.3 1.03594 34.8225 1.03594 33V4Z" stroke="#515E68" stroke-opacity="0.18" stroke-width="1.4"/></svg>`,
		successIcon: `<svg width="19" height="14" viewBox="0 0 19 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.47688 13.702L0 7.38981L2.26691 5.18052L6.47688 9.28348L16.0025 0L18.2694 2.20928L6.47688 13.702Z" fill="#92C019"/></svg>`,
		cancelIcon: `<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1.80755 0L0.000103432 1.80746L5.19255 6.99995L0 12.1925L1.80745 14L7 8.80741L12.1926 14L14 12.1925L8.80745 6.99995L13.9999 1.80746L12.1924 2.55004e-06L7 5.19249L1.80755 0Z" fill="#FF5752"/></svg>`,
		tunnelFirstVector: `<svg width="11" height="21" viewBox="0 0 11 21" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="4" fill="#CBCED2" stroke="white" stroke-width="2"/><path d="M5 6V18C5 19.1046 5.89543 20 7 20H11" stroke="#CBCED2" stroke-width="1.6"/></svg>`,
		tunnelArrow: `<svg width="5" height="8" viewBox="0 0 5 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M0 0.880785L2.55719 3.37704L3.21954 3.99978L2.55719 4.62289L0 7.11914L0.902358 8L5 4L0.902358 0L0 0.880785Z" fill="#989DA5"/></svg>`,
		tunnelStageIcon: `<svg width="13" height="11" viewBox="0 0 13 11" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 2C0 0.895431 0.895431 0 2 0L8.52745 0C9.22536 0 9.87278 0.3638 10.2357 0.959904L13 5.5L10.2357 10.0401C9.87278 10.6362 9.22536 11 8.52745 11H2C0.895432 11 0 10.1046 0 9V2Z" fill="#2FC6F6"/></svg>`,
		addNewStageIcon: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 0H10V17H7V0Z" fill="#91969F"/><path d="M17 7V10L0 10L1.19209e-07 7L17 7Z" fill="#91969F"/></svg>`,
		tunnelVector: `<svg width="7" height="31" viewBox="0 0 7 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 0V28C1 29.1046 1.89543 30 3 30H7" stroke="#CBCED2" stroke-width="1.6"/></svg>`,
	}

	const styles = {
		container: {
			backgroundColor: '#EEF3F5',
			alignItems: 'center',
			paddingBottom: 45,
		},
		titleContainer: {
			width: '100%',
			justifyContent: 'space-between',
			alignItems: 'center',
			flexDirection: 'row',
			paddingTop: 23,
			paddingBottom: 16,
			paddingLeft: 23,
			paddingRight: 20,
		},
		titleContent: {
			flexDirection: 'row',
			alignItems: 'center',
		},
		titleContentIcon: {
			width: 19,
			height: 14,
			marginRight: 10,
		},
		titleContentText: {
			color: '#525C69',
			fontWeight: 'bold',
			fontSize: 16,
			marginRight: 4,
		},
		titleChangeCategory: {
			fontSize: 14,
			color: '#525C69',
		},
		scroll: {
			width: '100%',
		},
		listContainer: {
			backgroundColor: '#ffffff',
			borderRadius: 8,
			marginBottom: 8,
		},
		separator: {
			height: 4,
			width: '100%',
			backgroundColor: '#EEF3F5',
		},
		createNewButton: {
			paddingTop: 25,
			paddingLeft: 25,
			paddingBottom: 25,
			backgroundColor: '#FFFFFF',
			borderRadius: 8,
			flexDirection: 'row',
			alignItems: 'center',
		},
		createNewButtonIcon: {
			width: 17,
			height: 17,
			marginRight: 17,
		},
		createNewButtonText: {
			color: '#333333',
			fontSize: 18,
		},
		stageListHeaderContainer: {
			paddingTop: 12,
			paddingBottom: 10,
			paddingLeft: 23,
			paddingRight: 62,
			width: '100%',
		},
		stageListHeaderText: {
			marginBottom: 8,
			color: '#525C69',
			fontSize: 14,
		},
		stageListHeaderStageInfoContainer: {
			flexDirection: 'row',
			alignItems: 'flex-start',
			flex: 1,
		},
		stageListHeaderStageInfoIcon: {
			marginRight: 12.5,
			marginTop: 22,
			width: 13,
			height: 14,
		},
		stageListHeaderStageInfoWrapper: {
			position: 'relative',
			flex: 1,
			paddingTop: 9,
			paddingRight: 9,
			marginTop: 10,
		},
		stageListHeaderStageInfo: {
			width: '100%',
			alignItems: 'center',
			flexDirection: 'row',
		},
		stageListInfoArrow: {
			width: 12,
			height: 37,
			marginLeft: -1.4,
		},
		stageListInfoTitle: {
			color: '#565A62',
			fontWeight: 'bold',
			fontSize: 16,
			marginRight: 3,
		},
		stageListInfoText: {
			fontSize: 16,
			color: '#565A62',
			opacity: 0.5,
			marginRight: 'auto',
		},
		stageItemContainer: {
			flexDirection: 'row',
			alignItems: 'flex-start',
		},
		stageItemIcon: {
			width: 6,
			height: 14,
			marginRight: 11,
			marginLeft: 9,
			marginTop: 32,
		},
		stageFinalItemIcon: (semantics) => ({
			width: semantics === 'success' ? 18 : 14,
			height: 14,
			marginLeft: semantics === 'success' ? 19 : 23,
			marginRight: 22,
			marginTop: 29,
		}),
		stageItemId: {
			color: '#333333',
			marginRight: 23,
			marginTop: 29,
			opacity: 0.5,
			fontSize: 16,
		},
		stageItemContent: {
			flexDirection: 'row',
			flex: 1,
			alignItems: 'flex-start',
			paddingTop: 16,
			paddingBottom: 16,
			borderTopWidth: 2,
			borderTopColor: '#EEF3F5',
		},
		stageFinalItemContent: (index) => ({
			flexDirection: 'row',
			alignItems: 'flex-start',
			justifyContent: 'space-between',
			paddingTop: 16,
			paddingBottom: 16,
			borderTopWidth: index === 0 ? 0: 2,
			borderTopColor: '#EEF3F5',
			flex: 1,
		}),
		stageItemEditContainer: {
			paddingTop: 11,
			paddingBottom: 11,
			paddingLeft: 15,
			paddingRight: 15,
			marginLeft: 6,
			marginRight: 9,
		},
		stageItemEditIcon: {
			width: 15,
			height: 15,
		},
		stageInfoContainer: {
			flex: 1,
		},
		stageInfoContent: {
			width: '100%',
			alignItems: 'center',
			flexDirection: 'row',
			position: 'relative',
		},
		stageInfoBackground: (backgroundColor, borderColor) => ({
			flexGrow: 1,
			height: 37,
			borderRadius: 3,
			backgroundColor: backgroundColor.replace(/[^#0-9a-fA-F]/g,''),
			borderWidth: 1.4,
			borderColor: borderColor.replace(/[^#0-9a-fA-F]/g,''),
		}),
		stageInfoText: (color) => ({
			color: color,
			marginRight: 'auto',
			flex: 1,
			paddingRight: 5,
		}),
		counterContainer: {
			backgroundColor: '#FF5752',
			borderRadius: 10,
			paddingLeft: 7,
			paddingRight: 7,
			height: 20,
			justifyContent: 'center',
			alignItems: 'center',
		},
		counterText: {
			color: '#ffffff',
			fontSize: 12,
			textAlign: 'center',
		},
		tunnelsContainer: {
			marginLeft: 1,
			marginTop: -5,
		},
		tunnelContainer: (index) => ({
			flexDirection: 'row',
			marginTop: index !== 0 ? -20: 0,
			marginLeft: index !== 0 ? 2: 0,
		}),
		tunnelDecorationIcon: (index) => ({
			width: 11,
			height: index !== 0 ? 31: 21,
			marginRight: index !== 0 ? 0: 2,
		}),
		tunnelContent: (index) => ({
			marginTop: index !== 0 ? 20: 10,
			marginLeft: 3,
			flexDirection: 'row',
			alignItems: 'center',
			flex: 1,
		}),
		tunnelTitle: {
			color: '#767C87',
			opacity: 0.55,
			fontSize: 13,
			fontWeight: 'bold',
		},
		tunnelArrowIcon: {
			width: 5,
			height: 8,
			marginLeft: 4,
			marginRight: 5,
		},
		tunnelCategoryIcon: {
			width: 13,
			height: 11,
			marginRight: 4,
		},
		tunnelTextWrapper: {
			flexDirection: 'row',
			flex: 1,
		},
		tunnelText: {
			color: '#378EE7',
			maxWidth: '50%',
		},
	};

	class StagesSettings extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			const counterTotalValue = this.prepareCounterTotalValue(props.stages).toString();
			this.state = {
				stages: props.stages,
				counterTotalValue: counterTotalValue,
			}
		}

		render()
		{
			const stages = this.state.stages;
			return View({
					style: styles.container,
				},
				View({
						style: styles.titleContainer,
					},
					View({
							style: styles.titleContent,
						},
						Image({
							style: styles.titleContentIcon,
							resizeMode: 'center',
							svg: {
								content: icons.funnel,
							},
						}),
						Text({
							style: styles.titleContentText,
							text: BX.message('CRM_STAGE_LIST_TITLE'),
						}),
						this.renderCounter(this.state.counterTotalValue),
					),
					Text({
						style: styles.titleChangeCategory,
						text: BX.message('CRM_STAGE_LIST_CHANGE_TUNNEL'),
					}),
				),
				ScrollView({
						style: styles.scroll,
					},
					View({},
						View({
								style: styles.listContainer,
							},
							this.renderStageListHeader(this.state.counterTotalValue),
							...stages
								.filter((stage) => stage.semantics === 'process')
								.map((stage) => this.renderStageItem(stage)),
							View({
									style: styles.separator,
								},
							),
							...stages
								.filter((stage) => stage.semantics !== 'process')
								.map((stage, index) => this.renderFinalStage(stage, index)),
						),
						View({
								style: styles.createNewButton,
							},
							Image({
									style: styles.createNewButtonIcon,
									svg: {
										content: icons.addNewStageIcon,
									},
								},
							),
							Text({
									style: styles.createNewButtonText,
									text: BX.message('CRM_STAGE_LIST_CREATE_STAGE'),
								},
							),
						),
					),
				),
			);
		}

		renderStageListHeader(counterTotalValue)
		{
			return View({
					style: styles.stageListHeaderContainer,
				},
				Text({
						style: styles.stageListHeaderText,
						text: BX.message('CRM_STAGE_LIST_SELECT_STAGE'),
					},
				),
				View({
						style: styles.stageListHeaderStageInfoContainer,
					},
					Image({
							style: styles.stageListHeaderStageInfoIcon,
							svg: {
								content: icons.select,
							},
						},
					),
					View(
						{
							style: styles.stageListHeaderStageInfoWrapper,
						},
						View(
							{
								style: styles.stageListHeaderStageInfo,
							},
							View(
								{
									style: styles.stageInfoBackground('#EFF4F7', '#D3D9DD'),
								}
							),
							Image(
								{
									style: styles.stageListInfoArrow,
									svg: {
										content: `<svg width="12" height="37" viewBox="0 0 218 37"  fill="none" xmlns="http://www.w3.org/2000/svg"><path transform= 'translate(-206,0)' fill-rule="evenodd" clip-rule="evenodd" fill="#EFF4F7" d="M0.335938 4C0.335938 1.79086 2.1268 0 4.33594 0L205.063 0C206.975 0 208.719 1.09002 209.557 2.80823L217.21 18.5L209.557 34.1918C208.719 35.91 206.975 37 205.063 37H4.33594C2.1268 37 0.335938 35.2091 0.335938 33V4Z"/><path transform= 'translate(-206,0)' stroke="#515E68" stroke-opacity="0.18" stroke-width="1.4" d="M1.03594 4C1.03594 2.17746 2.5134 0.7 4.33594 0.7H205.063C206.707 0.7 208.207 1.63742 208.928 3.11508L216.431 18.5L208.928 33.8849C208.207 35.3626 206.707 36.3 205.063 36.3H4.33594C2.5134 36.3 1.03594 34.8225 1.03594 33V4Z"/></svg>`,
									}
								},
							)
						),
						View(
							{
								style: {
									...styles.stageListHeaderStageInfo,
									position: 'absolute',
									left: 4.5,
									bottom: 4.5,
								}
							},
							View(
								{
									style: styles.stageInfoBackground('#EFF4F7', '#D3D9DD'),
								}
							),
							Image(
								{
									style: styles.stageListInfoArrow,
									svg: {
										content: `<svg width="12" height="37" viewBox="0 0 218 37"  fill="none" xmlns="http://www.w3.org/2000/svg"><path transform= 'translate(-206,0)' fill-rule="evenodd" clip-rule="evenodd" fill="#EFF4F7" d="M0.335938 4C0.335938 1.79086 2.1268 0 4.33594 0L205.063 0C206.975 0 208.719 1.09002 209.557 2.80823L217.21 18.5L209.557 34.1918C208.719 35.91 206.975 37 205.063 37H4.33594C2.1268 37 0.335938 35.2091 0.335938 33V4Z"/><path transform= 'translate(-206,0)' stroke="#515E68" stroke-opacity="0.18" stroke-width="1.4" d="M1.03594 4C1.03594 2.17746 2.5134 0.7 4.33594 0.7H205.063C206.707 0.7 208.207 1.63742 208.928 3.11508L216.431 18.5L208.928 33.8849C208.207 35.3626 206.707 36.3 205.063 36.3H4.33594C2.5134 36.3 1.03594 34.8225 1.03594 33V4Z"/></svg>`,
									}
								},
							)
						),
						View(
							{
								style: {
									...styles.stageListHeaderStageInfo,
									position: 'absolute',
									left: 9,
									bottom: 9,
								}
							},
							View(
								{
									style: {
										...styles.stageInfoBackground('#EFF4F7', '#D3D9DD'),
										flexDirection: 'row',
										alignItems: 'center',
										paddingRight: 15,
										paddingLeft: 8,
									}
								},
								Text({
									style: styles.stageListInfoTitle,
									text: BX.message('CRM_STAGE_LIST_ALL_STAGES_LIST'),
								}),
								Text({
									style: styles.stageListInfoText,
									text: BX.message('CRM_STAGE_LIST_ALL_STAGES_LIST_SUBTEXT'),
								}),
								this.renderCounter(counterTotalValue),
							),
							Image(
								{
									style: styles.stageListInfoArrow,
									svg: {
										content: `<svg width="12" height="37" viewBox="0 0 218 37"  fill="none" xmlns="http://www.w3.org/2000/svg"><path transform= 'translate(-206,0)' fill-rule="evenodd" clip-rule="evenodd" fill="#EFF4F7" d="M0.335938 4C0.335938 1.79086 2.1268 0 4.33594 0L205.063 0C206.975 0 208.719 1.09002 209.557 2.80823L217.21 18.5L209.557 34.1918C208.719 35.91 206.975 37 205.063 37H4.33594C2.1268 37 0.335938 35.2091 0.335938 33V4Z"/><path transform= 'translate(-206,0)' stroke="#515E68" stroke-opacity="0.18" stroke-width="1.4" d="M1.03594 4C1.03594 2.17746 2.5134 0.7 4.33594 0.7H205.063C206.707 0.7 208.207 1.63742 208.928 3.11508L216.431 18.5L208.928 33.8849C208.207 35.3626 206.707 36.3 205.063 36.3H4.33594C2.5134 36.3 1.03594 34.8225 1.03594 33V4Z"/></svg>`,
									}
								},
							)
						),
					),
				),
			);
		}

		renderStageItem(item)
		{
			return View({
					style: styles.stageItemContainer,
				},
				Image({
					style: styles.stageItemIcon,
					resizeMode: 'center',
					svg: {content: icons.combinedShape },
				}),
				Text({
						style: styles.stageItemId,
						text: item.id,
					},
				),
				View({
						style: styles.stageItemContent,
					},
					this.renderStageInfo(item),
					View(
						{
							style: styles.stageItemEditContainer,
						},
						Image({
							style: styles.stageItemEditIcon,
							svg: {
								content: icons.pen,
							},
						}),
					),
				),
			);
		}

		renderFinalStage(stage, index)
		{
			return View({
					style:  styles.stageItemContainer,
				},
				Image(
					{
						style: styles.stageFinalItemIcon(stage.semantics),
						resizeMode: 'center',
						svg: {
							content: stage.semantics === 'success'? icons.successIcon: icons.cancelIcon,
						},
					},
				),
				View(
					{
						style: styles.stageFinalItemContent(index),
					},
					this.renderStageInfo(stage),
					View(
						{
							style: styles.stageItemEditContainer,
						},
						Image({
							style: styles.stageItemEditIcon,
							svg: {
								content: icons.pen,
							},
						}),
					),
				),
			);
		}

		renderStageInfo(item)
		{
			return View({
					style: styles.stageInfoContainer,
				},
				View(
					{
						style: styles.stageInfoContent,
					},
					View(
						{
							style: {
								...styles.stageInfoBackground(item.color, item.color),
								flexDirection: 'row',
								alignItems: 'center',
								paddingRight: 15,
								paddingLeft: 8,
								flex: 1,
							},
						},
						Text({
							style: styles.stageInfoText(this.calculateTextColor(item.color)),
							numberOfLines: 1,
							ellipsize: 'end',
							text: item.name,
						}),
						typeof item.counter === 'string' && item.counter !== '0' ? this.renderCounter(item.counter) : null,
					),
					Image(
						{
							style: styles.stageListInfoArrow,
							svg: {
								content: `<svg width="12" height="37" viewBox="0 0 218 37"  fill="none" xmlns="http://www.w3.org/2000/svg"><path transform= 'translate(-206,0)' fill-rule="evenodd" clip-rule="evenodd" fill="${item.color.replace(/[^#0-9a-fA-F]/g,'')}" d="M0.335938 4C0.335938 1.79086 2.1268 0 4.33594 0L205.063 0C206.975 0 208.719 1.09002 209.557 2.80823L217.21 18.5L209.557 34.1918C208.719 35.91 206.975 37 205.063 37H4.33594C2.1268 37 0.335938 35.2091 0.335938 33V4Z"/><path transform= 'translate(-206,0)' stroke="${item.color.replace(/[^#0-9a-fA-F]/g,'')}" stroke-opacity="0.18" stroke-width="1.4" d="M1.03594 4C1.03594 2.17746 2.5134 0.7 4.33594 0.7H205.063C206.707 0.7 208.207 1.63742 208.928 3.11508L216.431 18.5L208.928 33.8849C208.207 35.3626 206.707 36.3 205.063 36.3H4.33594C2.5134 36.3 1.03594 34.8225 1.03594 33V4Z"/></svg>`,
							}
						},
					)
				),
				Array.isArray(item.tunnels) && item.tunnels.length ? this.renderTunnels(item.tunnels): null,
			);
		}

		calculateTextColor(baseColor)
		{
			let r, g, b;
			if ( baseColor > 7 )
			{
				let hexComponent = baseColor.split("(")[1].split(")")[0];
				hexComponent = hexComponent.split(",");
				r = parseInt(hexComponent[0]);
				g = parseInt(hexComponent[1]);
				b = parseInt(hexComponent[2]);
			}
			else
			{
				if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(baseColor))
				{
					let c = baseColor.substring(1).split('');
					if(c.length === 3)
					{
						c = [c[0], c[0], c[1], c[1], c[2], c[2]];
					}
					c = '0x' + c.join('');
					r = ( c >> 16 ) & 255;
					g = ( c >> 8 ) & 255;
					b =  c & 255;
				}
			}

			const y = 0.21 * r + 0.72 * g + 0.07 * b;
			return ( y < 145 ) ? "#ffffff" : "#333333";
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

		prepareCounterTotalValue(stages)
		{
			return stages.reduce((counterTotalValue, {counter}) => counterTotalValue += parseInt(counter), 0);
		}

		renderTunnels(tunnels)
		{
			return View({
					style: styles.tunnelsContainer,
				},
				...tunnels.map((tunnel, index) => this.renderStageTunnel(tunnel, index)),
			)
		}

		renderStageTunnel(tunnel, index)
		{
			return View({
					style: styles.tunnelContainer(index),
				},
				Image({
						style: styles.tunnelDecorationIcon(index),
						resizeMode: 'center',
						svg: {
							content: index === 0? icons.tunnelFirstVector: icons.tunnelVector,
						},
					},
				),
				View({
						style: styles.tunnelContent(index),
					},
					Text({
						style: styles.tunnelTitle,
						text: BX.message('CRM_STAGE_LIST_TUNNEL'),
					}),
					Image({
							style: styles.tunnelArrowIcon,
							resizeMode: 'center',
							svg: {
								content: icons.tunnelArrow,
							},
						},
					),
					Image({
							style: styles.tunnelCategoryIcon,
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
						Text({
								style: styles.tunnelText,
								numberOfLines: 1,
								ellipsize: 'middle',
								text: `${tunnel.categoryName}`,
							},
						),
						Text({
								style: styles.tunnelText,
								numberOfLines: 1,
								ellipsize: 'end',
								text: `/${tunnel.stageName}`,
							},
						),
					),
				),
			);
		}
	}

	layout.showComponent(new StagesSettings({stages}));
})();
