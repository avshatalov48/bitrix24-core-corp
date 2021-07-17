(() => {

include('CallTracker');

const VIEW_MODE_INFO = 'info';
const VIEW_MODE_LIST = 'list';
const VIEW_MODE_EMPTY = 'empty';
const VIEW_MODE_CONGRATULATIONS = 'congratulations';

class CrmCallTrackerListComponent extends LayoutComponent
{
	constructor(props)
	{
		super(props);

		this.itemsLimit = 20;

		this.needUpdateCounter = true;
		this.isLoading = false;
		this.isSimpleCrm = BX.componentParameters.get('isSimpleCrm', false);
		this.hasFeatureRestrictions = BX.componentParameters.get('hasFeatureRestrictions', false);
		this.isCallTrackerEnabled = CallTracker.checkEnableTracker();
		this.isInited = (
			Application.storage.getBoolean('crm_calltracker_inited', false)
			|| BX.componentParameters.get('hasActiveTab', false)
		);

		this.state = this.getInitialListState();
		this.state.items = [];

		this.state.viewMode = VIEW_MODE_LIST;
		if (this.needShowInfoScreen())
		{
			this.state.viewMode = VIEW_MODE_INFO;
		}

		if (this.state.viewMode === VIEW_MODE_LIST)
		{
			this.loadItems(0, this.itemsLimit, false);
			this.needUpdateCounter = false;
		}

		this.bindEvents();
	}

	getInitialListState()
	{
		return {
			offset: 0,
			allItemsLoaded: false,
			isRefreshing: true,
			items: []
		};
	}

	needShowInfoScreen()
	{
		return (!this.isSimpleCrm || this.hasFeatureRestrictions || !this.isCallTrackerEnabled);
	}

	createListViewItem(props)
	{
		const disabled = props.row.hasOwnProperty('disabled') ? !!props.row.disabled : false;

		return View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'space-between',
					alignItems: 'center',
					paddingBottom: 10,
					paddingTop: 10,
					paddingLeft: 12,
					borderBottomWidth: 1,
					borderBottomColor: '#e8e7e8',
					opacity: disabled ? 0.5 : 1
				},
				onClick: () =>
				{
					const url = BX.componentParameters.get('detailUrl', '/mobile/crm/deal/?page=calltracker_details&deal_id=#deal_id#').replace('#deal_id#', props.row.id);
					if (url)
					{
						PageManager.openPage({
							url: url
						});
					}
				},
				onLongClick: () =>
				{
					const disabled = props.row.hasOwnProperty('disabled') ? !!props.row.disabled : false;
					if (disabled)
					{
						return;
					}

					let menuItems = [
						{
							id: 'postpone',
							title: BX.message('CRM_CALL_TRACKER_POSTPONE'),
							sectionCode: 'default',
							iconName: 'action_revert_popup'
						}
					];

					if (BX.componentParameters.get('canAddToIgnored', false))
					{
						menuItems.push({
							id: 'addToIgnored',
							title: BX.message('CRM_CALL_TRACKER_TO_IGNORED'),
							sectionCode: 'default',
							iconName: 'action_spam_popup'
						})
					}

					let menu = dialogs.createPopupMenu();
					menu.setData(menuItems, [
						{
							id: 'default',
							title: BX.message('CRM_CALL_TRACKER_CONTEXT_MENU_TITLE'),
						}
					], (action, item) =>
					{
						if (action === 'onItemSelected')
						{
							switch (item.id)
							{
								case 'addToIgnored':
									this.addToIgnored(props.row.id);
									break;
								case 'postpone':
									this.postpone(props.row.id);
									break;
							}
						}
					});
					menu.show();
				}
			},
			View(
				{
					style: {
						flexDirection: 'row',
						flexGrow: 1
					}
				},
				this.getAvatarLayout(props.row),
				this.getTitleLayout(props.row)
			),
			this.getRightColumnLayout(props.row)
		);
	}

	getAvatarLayout(row)
	{
		const url = row.hasOwnProperty('photo') ? row.photo : '';
		const direction = row.hasOwnProperty('direction') ? parseInt(row.direction) : 0;

		let viewStyle = {
			borderRadius: 25
		};
		let imageParams = {
			style: {
				width: 50,
				height: 50,
				borderRadius: 25,
				alignSelf: 'center',
				resizeMode: 'center'
			}
		};
		if (url.length)
		{
			imageParams.uri = url;
		}
		else
		{
			imageParams.svg = {
				content: '<svg viewBox="0 0 50 50" version="1.1" width="50" height="50" xmlns="http://www.w3.org/2000/svg"><path d="M21.645 11.713c-1.054-1.67 7.832-3.057 8.422 2.054.232 1.54.232 3.107 0 4.647 0 0 1.328-.152.442 2.372 0 0-.488 1.816-1.238 1.408 0 0 .122 2.296-1.058 2.685 0 0 .084 1.223.084 1.306l.986.147s-.03 1.02.167 1.13c.9.58 1.886 1.021 2.923 1.305 3.062.777 4.616 2.11 4.616 3.278l.823 4.189c-3.544 1.485-7.657 2.373-12.055 2.466H24.22c-4.389-.093-8.493-.977-12.03-2.456.161-1.159.371-2.47.588-3.315.466-1.816 3.087-3.165 5.498-4.202 1.248-.537 1.518-.86 2.774-1.409.07-.334.098-.676.084-1.017l1.068-.127s.14.255-.085-1.245c0 0-1.2-.311-1.256-2.7 0 0-.902.3-.956-1.147-.039-.98-.808-1.832.299-2.537l-.564-1.502s-.592-5.8 2.005-5.33z" fill="#FFF" fill-rule="evenodd"/></svg>'
			};
			viewStyle.backgroundColor = '#7b8691';
		}
		return View(
			{
				style: {
					width: 50,
					height: 50,
				}
			},
			View(
			{
					style: viewStyle
				},
				Image(imageParams)
			),
			direction ? Image({
				style: {
					position: 'absolute',
					left: 27,
					top: 27,
					width: 27,
					height: 27
				},
				svg: {
					content: direction === 1 ? this.getIncomingIcon() : this.getOutgoingIcon()
				}
			}) : null
		);
	}

	getTitleLayout(row)
	{
		const title = row.hasOwnProperty('title') ? row.title : '';
		const client = row.hasOwnProperty('client') ? row.client : '';
		const duration = row.hasOwnProperty('duration') ? row.duration : '';

		const description = (duration.length ? duration + '  ' : '') + client;

		return View(
			{
				style: {
					justifyContent: 'space-around',
					flexDirection: 'column',
					flexGrow: 1
				}
			},
			Text({
				text: title,
				ellipsize: 'end',
				numberOfLines: 1,
				style: {
					fontSize: 18,
					fontWeight: 'bold',
					position: 'absolute',
					color: '#333333',
					height: 25,
					left: 12,
					right: 10,
					top: 0
				}
			}),
			Text({
				text: description,
				ellipsize: 'end',
				numberOfLines: 1,
				style: {
					fontSize: 16,
					position: 'absolute',
					color: '#828B95',
					height: 20,
					left: 12,
					right: 10,
					top: 30
				}
			})
		)
	}

	getRightColumnLayout(row)
	{
		const activityCount = row.hasOwnProperty('activityCount') ? parseInt(row.activityCount) : 0;
		const date = row.hasOwnProperty('date') ? row.date : '';

		if (activityCount > 0)
		{
			return View(
				{
					style: {
						justifyContent: 'space-around',
						flexDirection: 'column',
						marginRight: 15
					},
				},
				Text({
					style: {
						paddingBottom: 5,
						color: '#828B95',
						fontSize: 13
					},
					text: date,
				}),
				View(
					{
						style: {
							justifyContent: 'flex-end',
							flexDirection: 'row'
						}
					},
					View(
						{
							style: {
								backgroundColor: '#FF5752',
								borderRadius: 20,
								paddingLeft: 8,
								paddingRight: 8,
								paddingTop: 3,
								paddingBottom: 3
							}
						},
						Text({
							style: {
								color: '#ffffff',
								fontSize: 12
							},
							text: '' + activityCount
						}),
					)
				)
			);
		}
		else
		{
			return View(
				{
					style: {
						height: 50,
						justifyContent: 'flex-start',
					},
				},
				Text({
					style: {
						marginRight: 15,
						color: '#828B95',
						fontSize: 13
					},
					text: date
				})
			);
		}
	}

	loadMore()
	{
		this.loadItems(this.state.offset + 1, this.itemsLimit, true);
	}

	reloadList()
	{
		this.setState(
			this.getInitialListState(),
			() => {
				this.loadItems(0, this.itemsLimit, false);
			}
		);
	}

	render()
	{
		let container = null;

		switch (this.state.viewMode)
		{
			case VIEW_MODE_LIST:
				container = this.renderList();
				break;
			case VIEW_MODE_INFO:
				container = this.renderInfoScreen();
				break;
			case VIEW_MODE_EMPTY:
				container = this.renderEmptyScreen();
				break;
			case VIEW_MODE_CONGRATULATIONS:
				container = this.renderCongratulationsScreen();
				break;
		}

		return View(
			{},
			container
		);
	}

	renderInfoScreen()
	{
		let icon;
		let title;
		let description;
		let buttons;

		const buttonStyle = {
			color: '#ffffff',
			backgroundColor: '#00A2E8',
			fontSize: 17,
			width: '100%'
		};

		if (this.hasFeatureRestrictions)
		{
			icon = this.getRestrictionsIcon();
			title = BX.message('CRM_CALL_TRACKER_TITLE_LICENSE_RESTRICTIONS');
			buttons = Button({
				style: buttonStyle,
				text: BX.message('CRM_CALL_TRACKER_INFO_BUTTON_DETAILS'),
				onClick: () => {
					this.showInfoHelper('mh_calltracker_buy_plan');
				}
			});
		}
		else if (!this.isSimpleCrm)
		{
			icon = this.getSettingsIcon();
			title = BX.message('CRM_CALL_TRACKER_TITLE_IS_NOT_SIMPLE_CRM');
			description = BX.message('CRM_CALL_TRACKER_SUBTITLE_IS_NOT_SIMPLE_CRM');
			buttons = Button({
				style: buttonStyle,
				text: BX.message('CRM_CALL_TRACKER_INFO_BUTTON_DETAILS'),
				onClick: () => {
					this.showInfoHelper('mh_calltracker_simple_crm');
				}
			});
		}
		else if (!this.isCallTrackerEnabled)
		{
			icon = this.getWelcomeIcon();
			title = BX.message('CRM_CALL_TRACKER_TITLE_WELCOME');
			buttons = View(
				{
					style: {
						flexDirection: 'column',
						width: '100%'
					}
				},
				Button({
					style: buttonStyle,
					text: BX.message('CRM_CALL_TRACKER_INFO_BUTTON_ENABLE'),
					onClick: () => {
						this.enableCallTracker();
					}
				}),
				Button({
					style: {
						...buttonStyle,
						color: '#525C69',
						backgroundColor: '#ffffff',
						marginTop: 15
					},
					text: BX.message('CRM_CALL_TRACKER_INFO_BUTTON_DETAILS'),
					onClick: () => {
						this.showInfoHelper('mh_calltracker_work');
					}
				})
			);
		}

		return View(
			{
				style: {
					flex: 1,
					paddingTop: 50,
					paddingLeft: 50,
					paddingRight: 50,
					paddingBottom: 30,
					flexDirection: 'column',
					justifyContent: 'space-around',
					alignItems: 'center',
					backgroundColor: '#ffffff'
				}
			},
			Image({
				style: {
					width: 117,
					height: 117
				},
				svg: {
					content: icon
				}
			}),
			View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
						justifyContent: 'flex-start',
						alignItems: 'center'
					}
				},
				View(
					{
						style: {
							paddingTop: 40
						}
					},
					Text({
						style: {
							color: '#333333',
							fontSize: 18,
							textAlign: 'center'
						},
						text: title
					})
				),
				description ?
					View(
						{
							style: {
								paddingTop: 20
							}
						},
						Text({
							style: {
								color: '#828B95',
								fontSize: 16,
								textAlign: 'center'
							},
							text: description
						})
					)
					: null
			),
			buttons
		);
	}

	renderCongratulationsScreen()
	{
		return View(
			{
				style: {
					flex: 1,
					paddingTop: 50,
					paddingLeft: 30,
					paddingRight: 30,
					paddingBottom: 10,
					flexDirection: 'column',
					justifyContent: 'space-between',
					alignItems: 'center',
					backgroundColor: '#ffffff'
				}
			},
			Image({
				style: {
					width: 117,
					height: 117
				},
				svg: {
					content: this.getCongratulationsIcon()
				}
			}),
			View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
						justifyContent: 'flex-start',
						alignItems: 'center'
					}
				},
				View(
					{
						style: {
							paddingTop: 40
						}
					},
					Text({
						style: {
							color: '#333333',
							fontSize: 18,
							textAlign: 'center'
						},
						text: BX.message('CRM_CALL_TRACKER_TITLE_CONGRATULATIONS')
					})
				),
				View(
					{
						style: {
							paddingTop: 20
						}
					},
					Text({
						style: {
							color: '#828B95',
							fontSize: 16,
							textAlign: 'center'
						},
						text: BX.message('CRM_CALL_TRACKER_SUBTITLE_CONGRATULATIONS')
					})
				)
			),
			View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
						justifyContent: 'flex-start',
						alignItems: 'center',
						paddingTop: 10,
						width: '100%'
					}
				},

				View(
					{
						style: {
							width: '80%'
						}
					},
					Button({
						style: {
							color: '#525C69',
							backgroundColor: '#ffffff',
							fontSize: 17,
							borderRadius: 6,
							borderWidth: 1,
							borderColor: '#525C6980',
							width: '100%'
						},
						text: BX.message('CRM_CALL_TRACKER_ADD_TO_MENU_BUTTON'),
						onClick: () => {
							this.showMenuSettings();
							// this.setViewMode(VIEW_MODE_EMPTY);
						}
					})
				),
				View(
					{
						style: {
							paddingTop: 20,
							paddingLeft: 70
						}
					},
					Image({
						style: {
							width: 23,
							height: 69,
						},
						svg: {
							content: this.getAddToMenuArrowImage()
						}
					})
				),
			)
		);
	}

	renderEmptyScreen()
	{
		return View(
			{
				style: {
					flex: 1,
					paddingTop: 50,
					paddingLeft: 50,
					paddingRight: 50,
					paddingBottom: 30,
					flexDirection: 'column',
					justifyContent: 'flex-start',
					alignItems: 'center',
					backgroundColor: '#ffffff'
				}
			},
			Image({
				style: {
					width: 117,
					height: 117
				},
				svg: {
					content: this.getWelcomeIcon()
				}
			}),
			View(
				{
					style: {
						paddingTop: 40
					},
				},
				Text({
					style: {
						color: '#333333',
						fontSize: 18,
						textAlign: 'center'
					},
					text: BX.message('CRM_CALL_TRACKER_TITLE_EMPTY_LIST')
				})
			),
			View(
				{
					style: {
						paddingTop: 20
					},
				},
				Text({
					style: {
						color: '#828B95',
						fontSize: 16,
						textAlign: 'center'
					},
					text: BX.message('CRM_CALL_TRACKER_SUBTITLE_EMPTY_LIST')
				})
			)
		);
	}

	renderList()
	{
		const {isRefreshing, allItemsLoaded} = this.state;

		const items = this.state.items.map(
			(item) => {
				item.key = item.id;
				return item;
			});

		return ListView({
			style: {
				backgroundColor: '#ffffff'
			},
			data: [
				{
					items: items.filter((item) => !(item.hasOwnProperty('hidden') && item.hidden))
				}
			],
			isRefreshing: isRefreshing,
			renderItem: (item) => this.createListViewItem({row: item}),
			onRefresh: () =>
			{
				this.reloadList()
			},
			onLoadMore: allItemsLoaded ? null : () =>
			{
				this.loadMore()
			}
		})
	}

	loadItems(offset, limit, append)
	{
		if (this.state.allItemsLoaded)
		{
			this.setState({isRefreshing: false});
			return;
		}
		if (this.isLoading)
		{
			return;
		}
		this.isLoading = true;

		(new RequestExecutor('mobile.crm.calltracker.list'))
			.setOptions({
				'offset': offset,
				'limit': limit,
				'updateCounter': (this.needUpdateCounter ? '1' : '0')
			})
			.call()
			.then(({result, loadMore, error}) =>
			{
				this.isLoading = false;

				if (result && result.error)
				{
					switch (result.error)
					{
						case 'NOT_SIMPLE_CRM':
							this.isSimpleCrm = false;
							this.setViewMode(VIEW_MODE_INFO);
							return;
						case 'WRONG_TARIFF':
							this.hasFeatureRestrictions = true;
							this.setViewMode(VIEW_MODE_INFO);
							return;
					}
				}

				if (!error && result.error_description)
				{
					error = {
						description: result.error_description
					};
				}

				if (error)
				{
					this.setState({isRefreshing: false});
					this.showError(error.description ? error.description : '');
					return;
				}
				let newState = {
					isRefreshing: false
				};
				if (result.items && result.items.length)
				{
					if (append)
					{
						newState.items = [...this.state.items, ...result.items];
						newState.offset = this.state.offset + result.items.length - 1;
					}
					else
					{
						newState.items = result.items;
						newState.offset = result.items.length - 1;
					}

					if (result.items.length < limit)
					{
						newState.allItemsLoaded = true;
					}
				}
				else
				{
					if (!append && this.state.items.length)
					{
						newState.items = [];
					}
					newState.allItemsLoaded = true;
				}
				this.setState(
					newState,
					() => {
						this.changeViewModeOnEmptyList();
					}
				);
			}, ({result, loadMore, error}) =>
			{
				this.isLoading = false;
				this.setState({isRefreshing: false});
				this.showError(error && error.description ? error.description : '');
			});
	}

	changeViewModeOnEmptyList()
	{
		if (this.state.viewMode === VIEW_MODE_LIST)
		{
			const visibleItems = this.state.items.filter(
				(item) => !(item.hasOwnProperty('hidden') && item.hidden)
			);
			if (!visibleItems.length)
			{
				this.setViewMode(VIEW_MODE_EMPTY);
			}
		}
	}

	setViewMode(viewMode)
	{
		if (this.state.viewMode !== viewMode)
		{
			this.setState({viewMode});
		}
	}

	bindEvents()
	{
		BX.addCustomEvent('onCrmCallTrackerItemUpdated', (data) => {
			let id = data.ID ? parseInt(data.ID) : 0;
			if (this.isItemLoaded(id))
			{
				this.reloadItem(id);
				this.showNotification(BX.message('CRM_CALL_TRACKER_UPDATED_NOTIFICATION'));
			}
		});
		BX.addCustomEvent('onCrmCallTrackerItemCommentAdded', (data) => {
			let id = data.ID ? parseInt(data.ID) : 0;
			if (this.isItemLoaded(id))
			{
				this.markAsProcessed(id);
			}
		});
		BX.addCustomEvent('onCrmCallTrackerDetailPageClose', (data) => {
			/*
			* if onCrmCallTrackerDetailPageClose emitted, we need to check if
			* some data was changed, because onCrmCallTrackerItemUpdated event
			* will not be emitted (because emitter page will be closed already)
			*/
			BX.addCustomEvent(
				'onCrmCallTrackerItemStartUpdate',
				this._onCrmCallTrackerItemStartUpdateHandler
			);
			setTimeout(() => {
				BX.removeCustomEvent(
					'onCrmCallTrackerItemStartUpdate',
					this._onCrmCallTrackerItemStartUpdateHandler
				);
			}, 500);
		});
		BX.addCustomEvent('onCrmCallTrackerAddToIgnoredRequest', (data) => {
			let id = data.ID ? parseInt(data.ID) : 0;
			const item = this.getItemById(id);
			if (!(item && item.hasOwnProperty('disabled') && item.disabled))
			{
				this.addToIgnored(id);
			}
		});
		BX.addCustomEvent('onCrmCallTrackerPostponeRequest', (data) => {
			let id = data.ID ? parseInt(data.ID) : 0;
			const item = this.getItemById(id);
			if (!(item && item.hasOwnProperty('disabled') && item.disabled))
			{
				this.postpone(id);
			}
		});
		BX.addCustomEvent('onAppStart', () => {
			if (this.state.viewMode === VIEW_MODE_LIST || this.state.viewMode === VIEW_MODE_EMPTY)
			{
				this.setViewMode(VIEW_MODE_LIST);
				this.reloadList();
			}
			else if (this.state.viewMode === VIEW_MODE_INFO)
			{
				this.isCallTrackerEnabled = CallTracker.checkEnableTracker();
				if (!this.needShowInfoScreen())
				{
					if (this.isInited)
					{
						this.setViewMode(VIEW_MODE_LIST);
						this.reloadList();
					}
					else
					{
						this.showCongratulationsScreen();
					}
				}
			}
		});

		this._onCrmCallTrackerItemStartUpdateHandler = this.onCrmCallTrackerItemStartUpdateHandler.bind(this);
	}

	onCrmCallTrackerItemStartUpdateHandler(data)
	{
		let id = data.ID ? parseInt(data.ID) : 0;
		if (this.isItemLoaded(id))
		{
			this.setState({isRefreshing: true});
			setTimeout(() => {
				this.reloadItem(id);
				this.showNotification(BX.message('CRM_CALL_TRACKER_UPDATED_NOTIFICATION'));
			}, 1000);
		}
	}

	addToIgnored(id)
	{
		this.hideItem(id);
		(new RequestExecutor('mobile.crm.calltracker.addToIgnored'))
			.setOptions({
				'id': id
			})
			.call()
			.then(({result, loadMore, error}) =>
			{
				if (!error && result.error_description)
				{
					error = {
						description: result.error_description
					};
				}
				if (error)
				{
					this.showError(error.description ? error.description : '');
				}
			}, ({result, loadMore, error}) =>
			{
				this.showError(error && error.description ? error.description : '');
			});

		this.changeViewModeOnEmptyList();
		this.showNotification(BX.message('CRM_CALL_TRACKER_ADD_TO_IGNORED_NOTIFICATION'));
	}

	markAsProcessed(id)
	{
		this.updateItemData(id, {disabled: true, activityCount: 0})
	}

	hideItem(id)
	{
		this.updateItemData(id, {hidden: true})
	}

	getItemById(id)
	{
		if (id <= 0)
		{
			return null;
		}
		const items = this.state.items;
		for (let i = 0; i < items.length; i++)
		{
			if (parseInt(items[i].id) === id)
			{
				return items[i];
			}
		}
		return null;
	}

	updateItemData(id, data)
	{
		id = parseInt(id);
		if (id <= 0)
		{
			return;
		}
		let items = this.state.items;
		for (let i = 0; i < items.length; i++)
		{
			if (parseInt(items[i].id) === id)
			{
				items[i] = {...items[i], ...data};
				this.setState({items});
				break;
			}
		}
	}

	postpone(id)
	{
		this.setState({isRefreshing: true});
		(new RequestExecutor('mobile.crm.calltracker.postpone'))
			.setOptions({
				'id': id
			})
			.call()
			.then(({result, loadMore, error}) =>
			{
				if (!error && result.error_description)
				{
					error = {
						description: result.error_description
					};
				}
				if (error)
				{
					this.setState({isRefreshing: false});
					this.showError(error.description ? error.description : '');
					return;
				}
				this.reloadList();
			}, ({result, loadMore, error}) =>
			{
				this.setState({isRefreshing: false});
				this.showError(error && error.description ? error.description : '');
			});
	}

	isItemLoaded(id)
	{
		if (id <= 0)
		{
			return false;
		}
		const items = this.state.items;
		for (let i = 0; i < items.length; i++)
		{
			if (parseInt(items[i].id) === id)
			{
				return true;
			}
		}
		return false;
	}

	reloadItem(id)
	{
		if (this.isLoading)
		{
			// items are already reloading
			return;
		}
		if (!this.state.isRefreshing)
		{
			this.setState({isRefreshing: true});
		}

		(new RequestExecutor('mobile.crm.calltracker.get'))
			.setOptions({
				'id': id
			})
			.call()
			.then(({result, loadMore, error}) =>
			{
				this.setState({isRefreshing: false});

				if (!error && result.error_description)
				{
					error = {
						description: result.error_description
					};
				}
				if (error)
				{
					this.showError(error.description ? error.description : '');
				}
				else if (result.id && parseInt(result.id) === id)
				{
					this.updateItemData(id, result);
					return;
				}
				// if item wasn't successfully reloaded, it will be removed from list:
				this.hideItem(id);
			}, ({result, loadMore, error}) =>
			{
				this.setState({isRefreshing: false});
				this.showError(error && error.description ? error.description : '');
			});
	}

	showMenuSettings()
	{
		ComponentHelper.openList({
			name: "tab.settings",
			object: "list",
			version: availableComponents["tab.settings"].version,
			widgetParams:{
				backdrop:{onlyMediumPosition: false, mediumPositionPercent: 80},
				groupStyle: true,
				//title: '',
			},
			componentParams: {
				showManualPresetSettings: true
			}
		});
	}

	showError(errorText)
	{
		if (errorText.length)
		{
			navigator.notification.alert(errorText, null, '');
		}
	}

	showNotification(text)
	{
		if (this.state.viewMode === VIEW_MODE_LIST)
		{
			dialogs.showSnackbar({
				title: text,
				showCloseButton: true,
				id: 'callTrackerListNotification',
				backgroundColor: "#000000",
				textColor: "#ffffff",
				hideOnTap: true,
				autoHide: true
			},
			() => {});
		}
	}

	enableCallTracker()
	{
		CallTracker.enableCallTracker().then(
			(result) => {
				this.isCallTrackerEnabled = true;
				if (this.isInited)
				{
					this.setViewMode(VIEW_MODE_LIST);
					this.reloadList();
				}
				else
				{
					this.showCongratulationsScreen();
				}
			},
			(error) => {
				console.log('enableCallTracker fail:', error);
			}
		);
	}

	showCongratulationsScreen()
	{
		this.setViewMode(VIEW_MODE_CONGRATULATIONS);
		Application.storage.setBoolean('crm_calltracker_inited', true);
		this.isInited = true;
	}

	showInfoHelper(code)
	{
		Application.openHelpArticle(code, 'phone_tracker');
	}

	getRestrictionsIcon()
	{
		return '<svg width="117" height="117" viewBox="0 0 117 117" fill="none" xmlns="http://www.w3.org/2000/svg">\n' +
			'<path fill-rule="evenodd" clip-rule="evenodd" d="M58.5 117C90.8087 117 117 90.8087 117 58.5C117 26.1913 90.8087 0 58.5 0C26.1913 0 0 26.1913 0 58.5C0 90.8087 26.1913 117 58.5 117Z" fill="#E2E4E6"/>\n' +
			'<path fill-rule="evenodd" clip-rule="evenodd" d="M61.6 67.1037C61.1278 67.8721 60.6655 68.6946 60.6655 69.5966V72.1996C60.6655 73.6584 59.4829 74.841 58.0241 74.841C56.5653 74.841 55.3827 73.6584 55.3827 72.1996V69.5964C55.3827 68.6945 54.9205 67.8721 54.4483 67.1037C54.0521 66.4591 53.8233 65.6992 53.8233 64.8855C53.8233 62.5512 55.7041 60.659 58.0242 60.659C60.344 60.659 62.2249 62.5512 62.2249 64.8855C62.2249 65.6992 61.9962 66.459 61.6 67.1037ZM46.523 44.569C46.523 38.178 51.6723 32.9972 58.024 32.9972C64.3759 32.9972 69.5252 38.178 69.5252 44.569V50.8553H46.523V44.569ZM75.6032 50.8553V44.569C75.6032 34.8005 67.7327 26.8818 58.024 26.8818C48.3155 26.8818 40.4449 34.8005 40.4449 44.569V50.8553H38.1082C36.4513 50.8553 35.1082 52.1985 35.1082 53.8553V81.3526C35.1082 83.0095 36.4513 84.3526 38.1082 84.3526H77.94C79.5969 84.3526 80.94 83.0095 80.94 81.3526V53.8553C80.94 52.1985 79.5969 50.8553 77.94 50.8553H75.6032Z" fill="white"/>\n' +
			'</svg>';
	}

	getSettingsIcon()
	{
		return '<svg width="117" height="117" viewBox="0 0 117 117" fill="none" xmlns="http://www.w3.org/2000/svg">\n' +
			'<path fill-rule="evenodd" clip-rule="evenodd" d="M58.5 117C90.8087 117 117 90.8087 117 58.5C117 26.1913 90.8087 0 58.5 0C26.1913 0 0 26.1913 0 58.5C0 90.8087 26.1913 117 58.5 117Z" fill="#E2E4E6"/>\n' +
			'<path d="M81.6055 76.0415L73.7335 70.3211C70.9379 68.2839 66.7883 69.265 64.7477 72.0612L62.414 75.2694C61.9119 75.9339 60.8775 76.1425 60.1573 75.7392C52.9356 71.1704 46.7608 64.7946 42.4868 57.5193C42.0794 56.7659 42.2909 55.7829 43.0503 55.3137L46.3552 53.1744C49.3135 51.2595 50.5315 47.354 48.6032 44.4874L43.0976 36.6784C41.4291 34.2734 37.9368 33.9467 35.7446 35.9213C27.5144 43.5493 23.924 56.8002 41.9773 75.3109C60.0304 93.8216 73.7437 90.8604 81.9739 83.2325C84.1686 81.2572 83.9841 77.7939 81.6055 76.0415Z" fill="white"/>\n' +
			'<path fill-rule="evenodd" clip-rule="evenodd" d="M76.0766 50.3237C74.9351 52.4078 72.7111 53.6378 70.4231 53.4505C66.0609 53.0933 63.6492 48.3141 65.8253 44.3416C66.8543 42.4632 69.4158 41.0464 71.4781 41.2149C75.8398 41.5713 78.253 46.3502 76.0766 50.3237ZM84.9246 49.2636L82.2326 47.8941C82.295 46.974 82.2555 46.063 82.1124 45.1786C82.1064 45.1414 82.1221 45.1037 82.1528 45.0823L84.6457 43.3346C85.1857 42.9585 85.3879 42.2403 85.1271 41.6363L84.4035 39.9656C84.1408 39.3615 83.4921 39.0509 82.8685 39.2312L79.9469 40.0691C79.1284 38.9791 78.1111 38.0436 76.9286 37.3127C76.8972 37.2933 76.8803 37.2564 76.8856 37.2186L77.3164 34.1649C77.4104 33.5054 77.0287 32.8826 76.4137 32.693L74.7092 32.1674C74.0955 31.9782 73.4276 32.2775 73.1361 32.8761L71.7698 35.6425C71.7528 35.6767 71.7182 35.6976 71.6814 35.6959C70.5574 35.6459 69.4484 35.772 68.3809 36.0548C68.3449 36.0643 68.3074 36.0512 68.2853 36.0211L66.5372 33.6433C66.1527 33.1197 65.4502 32.9665 64.8762 33.2832L63.2862 34.1633C62.7128 34.4813 62.4379 35.1751 62.6354 35.8029L63.5294 38.6603C63.5406 38.6964 63.5299 38.7362 63.5022 38.7619C62.6849 39.5201 61.9666 40.4034 61.376 41.3937C61.3565 41.4265 61.3206 41.4446 61.2841 41.44L58.3149 41.0598C57.6784 40.977 57.0656 41.384 56.8687 42.0226L56.3233 43.7914C56.1264 44.43 56.4038 45.1114 56.9763 45.4015L59.6441 46.7591C59.6769 46.7759 59.6964 46.8112 59.694 46.8492C59.6365 47.7887 59.6805 48.7179 59.8373 49.6186C59.8438 49.6561 59.8282 49.6945 59.7972 49.7162L57.3173 51.4527C56.7788 51.8287 56.5751 52.547 56.8361 53.1506L57.5587 54.821C57.8212 55.426 58.4709 55.7349 59.0942 55.5554L61.999 54.7234C62.0346 54.7131 62.0721 54.7256 62.0948 54.7549C62.9037 55.7975 63.9036 56.6899 65.0536 57.3928L64.6165 60.51C64.5203 61.1689 64.9037 61.7923 65.5174 61.9815L67.222 62.507C67.837 62.6966 68.5031 62.3968 68.7968 61.7989L70.1612 59.0324C70.1781 58.9982 70.2128 58.9773 70.2496 58.9789C71.359 59.0285 72.453 58.9061 73.5091 58.6306C73.545 58.6212 73.5823 58.6343 73.6043 58.6643L75.4265 61.1448C75.8097 61.6694 76.5128 61.8224 77.0868 61.5042L78.6772 60.6245C79.2497 60.3078 79.5275 59.6153 79.3273 58.9856L78.4005 56.0185C78.3892 55.9825 78.3999 55.9427 78.4277 55.917C79.2475 55.1562 79.9685 54.2716 80.5592 53.276C80.5786 53.2432 80.6147 53.2249 80.6512 53.2296L83.587 53.6056C84.2252 53.6888 84.8363 53.2813 85.0332 52.6427L85.5785 50.8739C85.7745 50.235 85.4989 49.5542 84.9246 49.2636Z" fill="white"/>\n' +
			'</svg>';
	}

	getWelcomeIcon()
	{
		return '<svg width="117" height="117" viewBox="0 0 117 117" fill="none" xmlns="http://www.w3.org/2000/svg">\n' +
			'<path fill-rule="evenodd" clip-rule="evenodd" d="M58.5 117C90.8087 117 117 90.8087 117 58.5C117 26.1913 90.8087 0 58.5 0C26.1913 0 0 26.1913 0 58.5C0 90.8087 26.1913 117 58.5 117Z" fill="#E2E4E6"/>\n' +
			'<path d="M64.0592 47.7272C63.6457 48.2009 63.0423 48.4749 62.4034 48.4749C61.7645 48.4749 61.1523 48.2009 60.7246 47.7272L55.4076 42.0618C54.4705 41.0224 54.4457 39.4739 55.349 38.4363L60.4903 32.755C60.902 32.2813 61.5072 32.0108 62.1461 32.0126C62.785 32.0143 63.3955 32.2883 63.8214 32.7656C64.7549 33.7997 64.7833 35.3429 63.8835 36.3805L62.7105 37.678H84.6795C85.7815 37.7345 86.6441 38.6555 86.6121 39.7408L86.6263 40.7413C86.6937 41.8267 85.8596 42.7477 84.7593 42.8042H62.7921L64.0007 44.1017C64.9359 45.1411 64.959 46.6878 64.0592 47.7272Z" fill="white"/>\n' +
			'<path d="M80.39 47.8675C81.217 47.6324 82.1133 47.8958 82.6865 48.5392L88.023 54.217C88.9583 55.2582 88.9831 56.8066 88.0798 57.8478L82.9385 63.5185C82.3866 64.1619 81.4974 64.4235 80.6651 64.1902C79.8132 63.9215 79.1619 63.2286 78.9614 62.3695C78.7271 61.4928 78.9454 60.5647 79.5435 59.8895L80.7166 58.592H58.7493C57.649 58.5337 56.7865 57.6145 56.8185 56.5291L56.8007 55.5269C56.7386 54.4415 57.5692 53.5223 58.6695 53.4622H80.6207L79.4068 52.1665C78.7857 51.4913 78.539 50.5615 78.7449 49.6847C78.9206 48.8291 79.547 48.1344 80.39 47.8675Z" fill="white"/>\n' +
			'<path d="M81.6055 76.0415L73.7335 70.3211C70.9379 68.2839 66.7883 69.265 64.7477 72.0612L62.414 75.2694C61.9119 75.9339 60.8775 76.1424 60.1573 75.7391C52.9356 71.1704 46.7608 64.7946 42.4868 57.5192C42.0794 56.7659 42.2909 55.7828 43.0503 55.3137L46.3552 53.1744C49.3135 51.2595 50.5315 47.3539 48.6032 44.4874L43.0976 36.6784C41.4291 34.2733 37.9368 33.9467 35.7446 35.9212C27.5144 43.5492 23.924 56.8002 41.9773 75.3108C60.0304 93.8216 73.7437 90.8604 81.9739 83.2325C84.1686 81.2572 83.9841 77.7938 81.6055 76.0415Z" fill="white"/>\n' +
			'</svg>';
	}

	getCongratulationsIcon()
	{
		return '<svg width="117" height="117" viewBox="0 0 117 117" fill="none" xmlns="http://www.w3.org/2000/svg">\n' +
			'<path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M58.5 117C90.8087 117 117 90.8087 117 58.5C117 26.1913 90.8087 0 58.5 0C26.1913 0 0 26.1913 0 58.5C0 90.8087 26.1913 117 58.5 117Z" fill="#9DCF00"/>\n' +
			'<path d="M64.0592 47.7272C63.6457 48.2009 63.0423 48.4749 62.4034 48.4749C61.7645 48.4749 61.1523 48.2009 60.7246 47.7272L55.4076 42.0618C54.4705 41.0224 54.4457 39.4739 55.349 38.4363L60.4903 32.755C60.902 32.2813 61.5072 32.0108 62.1461 32.0126C62.785 32.0143 63.3955 32.2883 63.8214 32.7656C64.7549 33.7997 64.7833 35.3429 63.8835 36.3805L62.7105 37.678H84.6795C85.7815 37.7345 86.6441 38.6555 86.6121 39.7408L86.6263 40.7413C86.6937 41.8267 85.8596 42.7477 84.7593 42.8042H62.7921L64.0007 44.1017C64.9359 45.1411 64.959 46.6878 64.0592 47.7272Z" fill="white"/>\n' +
			'<path d="M80.39 47.8675C81.217 47.6324 82.1133 47.8958 82.6865 48.5392L88.023 54.217C88.9583 55.2582 88.9831 56.8066 88.0798 57.8478L82.9385 63.5185C82.3866 64.1619 81.4974 64.4235 80.6651 64.1902C79.8132 63.9215 79.1619 63.2286 78.9614 62.3695C78.7271 61.4928 78.9454 60.5647 79.5435 59.8895L80.7166 58.592H58.7493C57.649 58.5337 56.7865 57.6145 56.8185 56.5291L56.8007 55.5269C56.7386 54.4415 57.5692 53.5223 58.6695 53.4622H80.6207L79.4068 52.1665C78.7857 51.4913 78.539 50.5615 78.7449 49.6847C78.9206 48.8291 79.547 48.1344 80.39 47.8675Z" fill="white"/>\n' +
			'<path d="M81.6055 76.0415L73.7335 70.3211C70.9379 68.2839 66.7883 69.265 64.7477 72.0612L62.414 75.2694C61.9119 75.9339 60.8775 76.1424 60.1573 75.7391C52.9356 71.1704 46.7608 64.7946 42.4868 57.5192C42.0794 56.7659 42.2909 55.7828 43.0503 55.3137L46.3552 53.1744C49.3135 51.2595 50.5315 47.3539 48.6032 44.4874L43.0976 36.6784C41.4291 34.2733 37.9368 33.9467 35.7446 35.9212C27.5144 43.5492 23.924 56.8002 41.9773 75.3108C60.0304 93.8216 73.7437 90.8604 81.9739 83.2325C84.1686 81.2572 83.9841 77.7938 81.6055 76.0415Z" fill="white"/>\n' +
			'</svg>';
	}

	getAddToMenuArrowImage()
	{
		return '<svg width="25" height="71" viewBox="0 0 25 71" fill="none" xmlns="http://www.w3.org/2000/svg">\n' +
			'<path d="M3.82026 67.3577C3.82026 67.3577 23.25 43.75 23.75 1.25" stroke="#9DCF00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>\n' +
			'<path d="M1.13379 60.7746L2.98265 69.0549L11.5139 66.5571" stroke="#9DCF00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>\n' +
			'</svg>\n';
	}

	getOutgoingIcon()
	{
		return '<svg width="27" height="27" viewBox="0 0 27 27" fill="none" xmlns="http://www.w3.org/2000/svg">\n' +
			'<g filter="url(#filter0_d)">\n' +
			'<circle cx="13.3248" cy="12.075" r="11.025" fill="white"/>\n' +
			'</g>\n' +
			'<path fill-rule="evenodd" clip-rule="evenodd" d="M14.7779 7.33641C14.516 7.07452 14.7015 6.62687 15.0718 6.62683L18.355 6.6269C18.5845 6.62683 18.7707 6.81295 18.7706 7.04248L18.7707 10.3257C18.7706 10.696 18.323 10.8815 18.0611 10.6196L17.0073 9.56578L14.2614 12.3116C14.0991 12.4739 13.8359 12.4739 13.6736 12.3116L13.0859 11.7239C12.9236 11.5616 12.9236 11.2984 13.0859 11.1361L15.8317 8.39021L14.7779 7.33641ZM17.8275 15.9612L16.2009 14.7424C15.6232 14.3084 14.7657 14.5174 14.3441 15.1132L13.8619 15.7967C13.7581 15.9383 13.5444 15.9827 13.3955 15.8968C11.9033 14.9234 10.6274 13.5649 9.74422 12.0148C9.66001 11.8543 9.70373 11.6449 9.86065 11.5449L10.5436 11.0891C11.1548 10.6811 11.4065 9.849 11.0081 9.23824L9.87042 7.57443C9.52566 7.06201 8.80402 6.99241 8.35103 7.41312C6.65039 9.03836 5.90849 11.8616 9.63893 15.8056C13.3693 19.7495 16.203 19.1186 17.9036 17.4934C18.3571 17.0725 18.319 16.3346 17.8275 15.9612Z" fill="#8FBC00"/>\n' +
			'<defs>\n' +
			'<filter id="filter0_d" x="0.299805" y="0.0499878" width="26.05" height="26.05" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">\n' +
			'<feFlood flood-opacity="0" result="BackgroundImageFix"/>\n' +
			'<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/>\n' +
			'<feOffset dy="1"/>\n' +
			'<feGaussianBlur stdDeviation="1"/>\n' +
			'<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.06 0"/>\n' +
			'<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow"/>\n' +
			'<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow" result="shape"/>\n' +
			'</filter>\n' +
			'</defs>\n' +
			'</svg>';
	}

	getIncomingIcon()
	{
		return '<svg width="27" height="27" viewBox="0 0 27 27" fill="none" xmlns="http://www.w3.org/2000/svg">\n' +
			'<g filter="url(#filter0_d)">\n' +
			'<circle cx="13.3248" cy="12.075" r="11.025" fill="white"/>\n' +
			'</g>\n' +
			'<path fill-rule="evenodd" clip-rule="evenodd" d="M17.2507 11.4299C17.5126 11.6918 17.3271 12.1395 16.9569 12.1395L13.6736 12.1394C13.4441 12.1395 13.258 11.9534 13.2581 11.7238L13.258 8.44059C13.258 8.07032 13.7057 7.88485 13.9676 8.14674L15.0214 9.20054L17.7672 6.45467C17.9295 6.29237 18.1927 6.29237 18.355 6.45467L18.9428 7.04245C19.1051 7.20475 19.1051 7.46794 18.9428 7.63024L16.1969 10.3761L17.2507 11.4299ZM17.8275 15.9612L16.2009 14.7424C15.6232 14.3084 14.7657 14.5174 14.3441 15.1132L13.8619 15.7967C13.7581 15.9383 13.5444 15.9827 13.3955 15.8968C11.9033 14.9234 10.6274 13.5649 9.74422 12.0148C9.66001 11.8543 9.70373 11.6449 9.86065 11.5449L10.5436 11.0891C11.1548 10.6811 11.4065 9.84897 11.0081 9.23822L9.87042 7.57441C9.52566 7.06199 8.80402 6.99238 8.35103 7.41309C6.65039 9.03834 5.90849 11.8616 9.63893 15.8055C13.3693 19.7495 16.203 19.1186 17.9036 17.4933C18.3571 17.0725 18.319 16.3346 17.8275 15.9612Z" fill="#00ACE3"/>\n' +
			'<defs>\n' +
			'<filter id="filter0_d" x="0.299805" y="0.0499878" width="26.05" height="26.05" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">\n' +
			'<feFlood flood-opacity="0" result="BackgroundImageFix"/>\n' +
			'<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/>\n' +
			'<feOffset dy="1"/>\n' +
			'<feGaussianBlur stdDeviation="1"/>\n' +
			'<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.06 0"/>\n' +
			'<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow"/>\n' +
			'<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow" result="shape"/>\n' +
			'</filter>\n' +
			'</defs>\n' +
			'</svg>';
	}
}

BX.onViewLoaded(() =>
{
	layoutWidget.showComponent(new CrmCallTrackerListComponent())
});

})();
