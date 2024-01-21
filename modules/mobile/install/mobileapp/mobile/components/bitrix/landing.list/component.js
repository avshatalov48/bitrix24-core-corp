class LandingListMenu extends LayoutComponent
{
	constructor(params)
	{
		super(params);
		this.item = params.item;
	}

	onExternalAction()
	{
		QRCodeAuthComponent.open(layoutWidget, {
			redirectUrl: this.getTabDesktopUrl(),
			showHint: true,
			title: BX.message(`MOBILE_LANDING_TITLE_${this.getType().toUpperCase()}`),
		});
	}

	render()
	{
		return ScrollView(
			{},
			ImageButton({
				iconName: '',
				style: {},
				onClick: () => this.onExternalAction(),
				text: 'View',
			}),
			ImageButton({
				iconName: '',
				style: {},
				onClick: () => {
					dialogs.showSharingDialog({ message: this.item.PUBLIC_URL });
				},
				text: BX.message('MOBILE_LANDING_ACTION_SHARE'),
			}),
			ImageButton({
				iconName: '',
				style: {},
				onClick: () => {
				},
				text: 'Public',
			}),
			ImageButton({
				iconName: '',
				style: {},
				onClick: () => {
				},
				text: 'Delete',
			}),
		);
	}
}

class LandingList extends LayoutComponent
{
	constructor(props)
	{
		super(props);

		this.items = [];

		this.state = {
			type: BX.componentParameters.get('type', null),
			isRefreshing: false,
			items: [],
		};

		this.setRightsButtons();
	}

	getType()
	{
		return this.state.type;
	}

	getUrlPrefix()
	{
		switch (this.getType())
		{
			case 'store':
				return '/shop/stores';
			default:
				return '/sites';
		}
	}

	setRightsButtons()
	{
		layoutWidget.setRightButtons([
			{
				name: '...',
				type: 'text',
				color: '#2066b0',
				callback: () => this.onExternalAction('/', BX.message(`MOBILE_LANDING_TITLE_${this.getType().toUpperCase()}`)),
			},
		]);
	}

	onExternalAction(url, title)
	{
		QRCodeAuthComponent.open(layoutWidget, {
			redirectUrl: this.getUrlPrefix() + url,
			showHint: true,
			title: `${title} @ ${url}`,
		});
	}

	onShare(url)
	{
		dialogs.showSharingDialog({ message: url });
	}

	onLoadMore()
	{
		const data = {
			params: {
				select: [
					'ID', 'TITLE', 'PUBLIC_URL', 'PREVIEW_PICTURE', 'TYPE', 'PHONE',
				],
				filter: {
					TYPE: this.getType().toUpperCase(),
				},
				order: {
					DATE_MODIFY: 'desc',
				},
			},
		};

		BX.rest.callMethod('landing.site.getList', data)
			.then((response) => {
				if (0)
				{
					ErrorNotifier.showError(response.answer.result[0].TYPE).then(() => {
						console.log('Ok button pressed');
					});
				}
				// return;

				// const items = this.state.items;
				const items = [];

				response.answer.result.map((item) => {
					items.push(item);
				});

				this.setState({
					...this.state,
					items,
				});
			})
			.catch((error) => console.log(error));
	}

	showTileMenu(item)
	{
		layoutWidget.openWidget('layout', {
			backdrop: {
				bounceEnable: false,
				mediumPositionHeight: 200,
			},
			title: 'Menu',
			onReady: (layout) => {
				layout.showComponent(
					new LandingListMenu({
						parentWidget: layout,
						item,
					}),
				);
			},
			onError: (error) => console.log(error),
		});
	}

	showTile3Dots(item)
	{
		return View(
			{
				style: {
					position: 'absolute',
					top: 0,
					right: 10,
					zIndex: 666,
				},
			},
			Button({
				style: {
					backgroundColor: '#ffffff',
					color: '#000000',
					borderRadius: 4,
				},
				text: '...',
				onClick: () => this.showTileMenu(item),
			}),
		);
	}

	showImage(item)
	{
		return View(
			{
				style: {
					position: 'relative',
					paddingRight: 10,
					paddingLeft: 10,
				},
			},
			this.showTile3Dots(item),
			Image({
				style: {
					width: '100%',
					height: 185,
					alignSelf: 'center',
					borderRadius: 10,
				},
				resizeMode: 'cover',
				uri: item.PREVIEW_PICTURE,
			}),
		);
	}

	showTitle(item)
	{
		return View(
			{
				style: {
					backgroundColor: '#ffffff',
					marginTop: 15,
					marginRight: 10,
					marginLeft: 10,
					borderRadius: 6,
				},
				clickable: true,
				onClick: () => this.onExternalAction(`/site/contacts/${item.ID}/?width=600`, item.TITLE),
			},
			Shadow(
				{
					radius: 6,
					color: '#cdcdcd',
					offset: { x: 0, y: 0 },
					style: {
						backgroundColor: '#ffffff',
						borderRadius: 6,
					},
				},
				View(
					{
						style: {
							// backgroundImage: 'put_here_icon_src',
							// backgroundResizeMode: 'cover',
							// backgroundPosition: 'right',
						},
					},
					Text({
						style: {
							fontSize: 20,
							margin: 10,
							marginBottom: 0,
						},
						text: item.TITLE,
					}),
					Text({
						style: {
							fontSize: 15,
							margin: 10,
							marginTop: 0,
							color: '#999fa8',
						},
						text: item.PHONE,
					}),
				),
			),
		);
	}

	showPublicationButton()
	{
		return ImageButton({
			iconName: '',
			style: {
				backgroundColor: '#a5cc3f',
				color: '#ffffff',
				borderRadius: 2,
			},
			onClick: () => {
				ErrorNotifier.showError('Not implemented yet...').then(() => {
					console.log('Ok button pressed');
				});
			},
			text: 'Public',
		});
	}

	showShareButton(url)
	{
		return ImageButton({
			iconName: '',
			style: {
				backgroundColor: '#ffffff',
				color: '#000000',
				borderRadius: 2,
			},
			onClick: () => this.onShare(url),
			text: BX.message('MOBILE_LANDING_ACTION_SHARE'),
		});
	}

	showDealsButton()
	{
		return ImageButton({
			iconName: '',
			style: {
				backgroundColor: '#ffffff',
				color: '#000000',
				borderRadius: 2,
			},
			onClick: () => {
				ErrorNotifier.showError('Not implemented yet...').then(() => {
					console.log('Ok button pressed');
				});
			},
			text: BX.message('MOBILE_LANDING_ACTION_DEALS'),
		});
	}

	showCookiesButton(item)
	{
		return ImageButton({
			iconName: '',
			style: {
				backgroundColor: '#ffffff',
				color: '#000000',
				borderRadius: 2,
			},
			onClick: () => this.onExternalAction(`/site/settings/${item.ID}/#cookies`, item.TITLE),
			text: 'Cookies',
		});
	}

	showButtons(item)
	{
		return View(
			{},
			View(
				{
					style: {
						flexDirection: 'row',
						marginTop: 15,
						marginRight: 15,
						marginLeft: 15,
						borderRadius: 4,
					},
				},
				View(
					{
						style: {
							width: '50%',
							paddingRight: 10,
						},
					},
					this.showPublicationButton(),
				),
				View(
					{
						style: {
							width: '50%',
							paddingLeft: 10,
						},
					},
					this.showShareButton(item.PUBLIC_URL),
				),
			),
			View(
				{
					style: {
						flexDirection: 'row',
						marginTop: 15,
						marginRight: 15,
						marginBottom: 20,
						marginLeft: 15,
						borderRadius: 4,
					},
				},
				View(
					{
						style: {
							width: '50%',
							paddingRight: 10,
						},
					},
					this.showDealsButton(),
				),
				View(
					{
						style: {
							width: '50%',
							paddingLeft: 10,
						},
					},
					this.showCookiesButton(item),
				),
			),
		);
	}

	showTile(item)
	{
		return View(
			{
				style: {
					paddingTop: 15,
					paddingRight: 10,
					paddingBottom: 10,
					paddingLeft: 10,
				},
			},
			Shadow(
				{
					radius: 10,
					color: '#cdcdcd',
					offset: { x: -3, y: 3 },
					inset: {
						top: 0,
						right: 3,
						left: 3,
					},
					style: {
						backgroundColor: '#f8f9fb',
						borderRadius: 10,
					},
				},
				View(
					{},
					this.showImage(item),
					this.showTitle(item),
					this.showButtons(item),
				),
			),
		);
	}

	onButtonClick(index)
	{
		const items = this.state.items;

		items[index].loader = true;

		this.setState({
			...this.state,
			items,
		});

		BX.rest.callMethod('landing.site.getList', {})
			.then((newItems) => {
				ErrorNotifier.showError('Some error text').then(() => {
					console.log('Ok button pressed');
				});

				const items = this.state.items;

				items[index].title = 'updated';
				items[index].color = '#ff0000';
				items[index].loader = false;

				this.setState({
					...this.state,
					items,
				});
			})
			.catch((error) => console.log(error));
	}

	render()
	{
		return ListView({
			style: {
				backgroundColor: '#f2f2f6',
			},
			data: [{ items: this.state.items }],
			isRefreshing: this.state.isRefreshing,
			renderItem: (item) => this.showTile(item),
			onLoadMore: () => this.onLoadMore(),
			onRefresh: () => {
			},
		});
	}
}

const LandingListComponent = new LandingList();

BX.onViewLoaded(() => {
	layoutWidget.showComponent(LandingListComponent);
});
