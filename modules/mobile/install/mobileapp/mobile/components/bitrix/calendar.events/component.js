(function ()
{
	class EventList extends BaseList
	{

		constructor(listObject)
		{
			super(listObject);
			BX.addCustomEvent("onCalendarEventChanged", this.onEventChanged.bind(this))
		}

		setParams(options = {})
		{
			this.options = options;
			return this;
		}

		onEventChanged(event)
		{
			console.log("onCalendarEventChanged", event);
			if (event.name)
			{
				if(event.event_id === 0)
				{
					this._list.addItems([
						EventList.prepareItemForDrawing(event)
					]);
				}
				else
				{
					this._list.updateItems([
						{
							filter: {id: event.event_id}, element: {
								title: event.name,
								subtitle: event.from_date,
							}
						},
					]);
				}

			}
		}

		static method()
		{
			return "calendar.event.get"
		}

		static id()
		{
			return "events";
		}

		params()
		{
			return this.options;
		}

		sections(items = [])
		{
			return super.sections(items)
				.map(section=>{
				if(section.id === this.constructor.id())
				{
					section.height = 40;
					section.backgroundColor = "#f0f0f0";
					section.styles = {
						title: {
							padding:{left:0, right:0, top:0, bottom:0},
							font:{size: 15, color:"#333333", fontStyle:"bold"},

						}
					}
				}

				return section;
			})


		}

		static prepareItemForDrawing(event)
		{
			return {
				title: event.NAME,
				subtitle: event.DATE_FROM,
				sectionCode: EventList.id(),
				color: "#f07f75",
				height: 80,
				imageUrl: `${component.path}/images/event.png`,
				styles: {
					title: {font: {size: 16}},
					subtitle: {font: {size: 12}},
					image: {image: {height: 44, borderRadius: 50}}
				},
				type: "info",
				useLetterImage: true,
				id: event.ID,
				sortValues: {
					name: event.NAME
				},
				params: {
					id: event.ID,
				},
			}
		}
	}

	const handlers = {
		prepareItems: items => items.map(event => EventList.prepareItemForDrawing(event)),
		onRefresh: function ()
		{
			reload()
		},
		onItemSelected: item => PageManager.openPage({
			url: `/mobile/calendar/edit_event.php?event_id=${item.id}`,
			backdrop: {shouldResizeContent: true, showOnTop: true, topPosition: 100},
			data: {modal: "Y"}
		})
	};

	class Calendar
	{
		constructor(list)
		{
			this.list = list;
			this.personal = (new EventList(list))
				.setParams({type: "user", ownerId: env.userId})
				.setHandlers(handlers);

			this.company = (new EventList(list))
				.setParams({type: "company_calendar", ownerId: ""})
				.setHandlers(handlers);

			this.switchTo("personal");

			setTimeout(()=>{
				let spotlight = dialogs.createSpotlight();
				spotlight.setTarget("calendar_menu_button");
				spotlight.setHint({text:"Вы можете переключаться между календарями из меню"});
				spotlight.show();
			}, 200);
		}

		setMenu(id)
		{
			let check = `${component.path}/images/check.png`;
			let items =this.getMenuItems().map(item =>
			{
				item.iconUrl = item.id === id ? check : "";
				return item;
			});

			Menu.setButtonMenu(list, {
				code: "calendar_menu_button",
				items: items,
				sections: [
					{title: "", id: "main"}
				],
				callback: item => this.switchTo(item.id)
			});

			if(typeof this.list["setFloatingButton"] !== "undefined")
			{
				this.list.setFloatingButton({
					icon:"plus",
					callback:()=>{
						PageManager.openPage({
							url: `/mobile/calendar/edit_event.php`,
							modal: true,
							data: {modal: "Y"}
						})
					},
				});
			}


		}

		getMenuItems()
		{
			return [
				{title: "Личный календарь", id: "personal", sectionCode: "main"},
				{title: "Календарь компании", id: "company", sectionCode: "main"}
			]
		}

		switchTo(id)
		{
			let eventList = id === "personal" ? this.personal : this.company;
			eventList.listenToListEvents();
			eventList.init().catch(()=>eventList.draw());
			this.setMenu(id);
			this.list.setTitle({
				text: this.getMenuItems().find(item => item.id === id).title
			})
		}
	}

	new Calendar(list);

}).bind(this)();

