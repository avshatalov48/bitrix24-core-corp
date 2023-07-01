(function ()
{
	const { PlanRestriction } = jn.require('layout/ui/plan-restriction');
	const { DialogSharing } = jn.require('calendar/layout/dialog/dialog-sharing');
	const { menuCalendarColor, menuCalendarGray } = jn.require('calendar/assets/common');
	const { Sharing } = jn.require('calendar/sharing');
	const { BottomSheet } = jn.require('bottom-sheet');
	const { Loc } = jn.require('loc');

	class EventList
	{
		constructor(list)
		{
			this.list = list
			this.items = []
			this.sections = []
			this.initEmptyState()
			this.list.on("onRefresh", () => {
				this.load()
			})
			this.list.on("onItemSelected", (item) => {
				console.log(item)
				this.onItemSelected(item)
			})

			BX.addCustomEvent("onCalendarEventChanged", this.onEventChanged.bind(this))
			BX.addCustomEvent('onCalendarEventRemoved', this.onEventRemove.bind(this));
			this.configureSearch()

			this.sharing = new Sharing();
			this.sharing.init()
				.then(() => {
					this.initRightMenu();
					this.initFloatingMenu();
				});
		}

		initEmptyState()
		{
			this.sections.push({id: "footer"})
			this.items.push({unselectable: true, sectionCode: "footer", id: "ignore", type: "loading", title: ""})
			this.list.setItems(this.items, this.sections)
		}

		configureSearch()
		{
			this.list.search.mode = "bar"
			this.list.search.on("show", () => {
				this.list.search.once("hide", () => this.list.setItems(this.items, this.sections))
				this.list.search.once("cancel", () => this.list.setItems(this.items, this.sections))
			})

			this.list.search.on("textChanged", ({text}) => {
				let result = this.items;
				if (text !== "")
				{
					result = this.items.filter(item => {
						const words = item.title.toLowerCase().split(" ")
						for (let i = 0; i < words.length; i++)
						{
							const word = words[i]
							if (word.indexOf(text.toLowerCase()) === 0)
							{
								return true
							}
						}

						return false;
					})

					if (result.length === 0)
					{
						this.list.setItems(
							[{
								unselectable: true,
								id: "ignore",
								title: BX.message('CALENDAR_EMPTY'),
								type: "button",
								sectionCode: "default"
							}],
							[{id: "default"}]
						)

						return;
					}
				}

				this.list.setItems(result, this.sections)
			})
		}

		load()
		{
			this.list.search.close()
			BX.ajax({url: "/mobile/?mobile_action=calendar", dataType: "json"}).then(result => {
				this.list.stopRefreshing()
				let footerText = ""
				if (result.TABLE_SETTINGS && result.TABLE_SETTINGS.footer)
				{
					footerText = result.TABLE_SETTINGS.footer
				}

				if (result.data)
				{
					this.items = [];
					this.sections = [];
					if (result.data.events)
					{
						this.items = result.data.events.map(EventList.prepareItemForDrawing)
						this.sections = result.sections.events.map(data => {
							return {
								title: data.NAME,
								id: data.ID,
								height: 30,
								backgroundColor: "#ffffff",
								styles: {
									title: {
										padding: {left: 0, right: 0, top: 10, bottom: 0},
										font: {size: 14, color: "#BE333333", fontStyle: "medium"},

									}
								}
							}
						})
					}
				}

				if (footerText !== "")
				{
					this.sections.push({id: "footer"})
					this.items.push(
						{unselectable: true, sectionCode: "footer", id: "ignore", type: "button", title: footerText})
				}
				console.log(this.items);
				list.setItems(this.items, this.sections, true)
			})
		}

		onEventChanged(event)
		{
			console.log("onEventChanged", event);
			this.closeEditForm()
			this.load()
		}

		onEventRemove(event)
		{
			console.log("onEventRemove", event);
			this.closeEditForm()
			this.load()
		}

		onItemSelected(item)
		{
			if (item.id === "ignore")
			{
				return
			}

			PageManager.openWidget("web", {
				// title:"Task №777",
				backdrop: {shouldResizeContent: true, showOnTop: true, topPosition: 100},
				page: {
					url: `/mobile/calendar/view_event.php?event_id=${item.id}`,
				}
			}).then(widget => {
				this.editForm = widget
			});
		}

		initRightMenu()
		{
			const buttons = [];

			buttons.push({
				type: "search", callback: () => this.list.search.show()
			});

			const isRestrictions = this.sharing.isRestriction();

			buttons.push({
				svg: {
					content:  this.sharing.isOn() && isRestrictions === false
						? menuCalendarColor
						: menuCalendarGray
				},
				type: 'options',
				badgeCode: 'sharing_categories_selector',
				callback: () => this.sharingDialog()
			});

			list.setRightButtons(buttons);
		}

		initFloatingMenu()
		{
			this.list.setFloatingButton({
				icon: "plus",
				callback: () => {
					PageManager.openPage({
						url: `/mobile/calendar/edit_event.php`,
						modal: true,
						data: {modal: "Y"}
					})
				},
			});
		}

		sharingDialog()
		{
			const isRestrictions = this.sharing.isRestriction();

			if (isRestrictions)
			{
				PlanRestriction.open({ title: Loc.getMessage('MC_PLAN_RESTRICTION_SHARING_SLOTS_FREE') });
			}
			else
			{
				(
					new BottomSheet({ component: new DialogSharing({
							sharing: this.sharing,
							onSharing: (fields) => {
								this.sharing.getModel().setFields(fields);
								this.initRightMenu();
							}
						})})
				)
				.setMediumPositionHeight(400)
				.open();
			}
		}

		closeEditForm()
		{
			if (this.editForm)
			{
				this.editForm.close()
				this.editForm = null
			}
		}

		static prepareItemForDrawing(event)
		{
			return {
				title: event.NAME,
				subtitle: event.TAGS,
				sectionCode: event.SECTION_ID,
				// color: "#f07f75",
				height: 60,
				imageUrl: event.IMAGE,
				styles: {
					title: {font: {size: 16}},
					subtitle: {font: {size: 12}},
					image: {image: {height: 44, borderRadius: 0}}
				},
				type: "info",
				id: event.ID,
				params: {
					url: event.URL,
				},
			}
		}
	}

	const events = new EventList(list)
	events.load()

}).bind(this)();

