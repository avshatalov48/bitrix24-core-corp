(function()
{
	const AppTheme = jn.require('apptheme');
	const { PlanRestriction } = jn.require('layout/ui/plan-restriction');
	const { DialogSharing } = jn.require('calendar/layout/dialog/dialog-sharing');
	const { Sharing, SharingContext } = jn.require('calendar/sharing');
	const { BottomSheet } = jn.require('bottom-sheet');
	const { Loc } = jn.require('loc');

	class EventList
	{
		constructor(list)
		{
			this.list = list;
			this.items = [];
			this.sections = [];

			this.initEmptyState();
			this.list.on('onRefresh', () => {
				this.load();
			});
			this.list.on('onItemSelected', (item) => {
				this.onItemSelected(item);
			});

			BX.addCustomEvent('onCalendarEventChanged', this.onEventChanged.bind(this));
			BX.addCustomEvent('onCalendarEventRemoved', this.onEventRemove.bind(this));
			this.configureSearch();

			this.sharing = new Sharing({
				type: SharingContext.CALENDAR,
			});
			// eslint-disable-next-line promise/catch-or-return
			this.sharing.init()
				.then(() => {
					this.initRightMenu();
					this.initFloatingMenu();
				});
		}

		initEmptyState()
		{
			this.sections.push({ id: 'footer' });
			this.items.push({ unselectable: true, sectionCode: 'footer', id: 'ignore', type: 'loading', title: '' });
			this.list.setItems(this.items, this.sections);
		}

		configureSearch()
		{
			this.list.search.mode = 'bar';
			this.list.search.on('show', () => {
				this.list.search.once('hide', () => this.list.setItems(this.items, this.sections));
				this.list.search.once('cancel', () => this.list.setItems(this.items, this.sections));
			});

			this.list.search.on('textChanged', ({ text }) => {
				let result = this.items;
				if (text !== '')
				{
					result = this.items.filter((item) => {
						const words = item.title.toLowerCase().split(' ');
						for (const word of words)
						{
							if (word.indexOf(text.toLowerCase()) === 0)
							{
								return true;
							}
						}

						return false;
					});

					if (result.length === 0)
					{
						this.list.setItems(
							[{
								unselectable: true,
								id: 'ignore',
								title: BX.message('CALENDAR_EMPTY'),
								type: 'button',
								sectionCode: 'default',
							}],
							[{ id: 'default' }],
						);

						return;
					}
				}

				this.list.setItems(result, this.sections);
			});
		}

		load()
		{
			this.list.search.close();
			// eslint-disable-next-line promise/catch-or-return
			BX.ajax({ url: '/mobile/?mobile_action=calendar', dataType: 'json' }).then((result) => {
				this.list.stopRefreshing();
				let footerText = '';
				if (result.TABLE_SETTINGS && result.TABLE_SETTINGS.footer)
				{
					footerText = result.TABLE_SETTINGS.footer;
				}

				if (result.data)
				{
					this.items = [];
					this.sections = [];
					if (result.data.events)
					{
						this.items = result.data.events.map(EventList.prepareItemForDrawing);
						this.sections = result.sections.events.map((data) => {
							return {
								title: data.NAME,
								id: data.ID,
								height: 30,
								backgroundColor: '#ffffff',
								styles: {
									title: {
										padding: { left: 0, right: 0, top: 10, bottom: 0 },
										font: { size: 14, color: '#BE333333', fontStyle: 'medium' },

									},
								},
							};
						});
					}
				}

				if (footerText !== '')
				{
					this.sections.push({ id: 'footer' });
					this.items.push({
						unselectable: true,
						sectionCode: 'footer',
						id: 'ignore',
						type: 'button',
						title: footerText,
					});
				}

				// eslint-disable-next-line no-undef
				list.setItems(this.items, this.sections, true);
			});
		}

		onEventChanged(event)
		{
			this.closeEditForm();
			this.load();
		}

		onEventRemove(event)
		{
			this.closeEditForm();
			this.load();
		}

		onItemSelected(item)
		{
			if (item.id === 'ignore')
			{
				return;
			}

			// eslint-disable-next-line promise/catch-or-return
			PageManager.openWidget('web', {
				backdrop: { shouldResizeContent: true, showOnTop: true, topPosition: 100 },
				page: {
					url: `/mobile/calendar/view_event.php?event_id=${item.id}`,
				},
			}).then((widget) => {
				this.editForm = widget;
			});
		}

		initRightMenu()
		{
			const buttons = [];

			buttons.push(
				{
					type: 'search', callback: () => this.list.search.show(),
				},
				{
					svg: {
						content: this.sharing.isOn()
							? icons.menuCalendarColor
							: icons.menuCalendarGray,
					},
					type: 'options',
					badgeCode: 'sharing_categories_selector',
					callback: () => this.sharingDialog(),
				},
			);

			// eslint-disable-next-line no-undef
			list.setRightButtons(buttons);
		}

		initFloatingMenu()
		{
			this.list.setFloatingButton({
				icon: 'plus',
				callback: () => {
					PageManager.openPage({
						url: '/mobile/calendar/edit_event.php',
						modal: true,
						data: { modal: 'Y' },
					});
				},
			});
		}

		sharingDialog()
		{
			const component = (layoutWidget) => new DialogSharing({
				layoutWidget,
				sharing: this.sharing,
				onSharing: (fields) => {
					this.sharing.getModel().setFields(fields);
					this.initRightMenu();
				},
			});

			void new BottomSheet({ component })
				.setBackgroundColor(AppTheme.colors.bgNavigation)
				.setMediumPositionPercent(80)
				.disableContentSwipe()
				.open()
			;
		}

		closeEditForm()
		{
			if (this.editForm)
			{
				this.editForm.close();
				this.editForm = null;
			}
		}

		static prepareItemForDrawing(event)
		{
			return {
				title: event.NAME,
				subtitle: event.TAGS,
				sectionCode: event.SECTION_ID,
				height: 60,
				imageUrl: event.IMAGE,
				styles: {
					title: { font: { size: 16 } },
					subtitle: { font: { size: 12 } },
					image: { image: { height: 44, borderRadius: 0 } },
				},
				type: 'info',
				id: event.ID,
				params: {
					url: event.URL,
				},
			};
		}
	}

	const icons = {
		menuCalendarColor: '<svg width="32" height="33" viewBox="0 0 32 33" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="1" y="1" width="31" height="32" rx="15.5" fill="#9DCF00"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.98621 21.56H13.2118L12.2396 23.6432H8.98731C8.43592 23.6432 7.98842 23.1766 7.98842 22.6016V12.1853C7.98442 12.1312 7.98242 12.078 7.98242 12.0249C7.98442 10.9604 8.8135 10.1 9.83437 10.1021H10.9851V10.6229C10.9851 11.4853 11.6554 12.1853 12.4834 12.1853C13.3115 12.1853 13.9818 11.4853 13.9818 10.6229V10.1021H17.0351V10.6229C17.0351 11.4853 17.7064 12.1853 18.5334 12.1853C19.3605 12.1853 20.0318 11.4853 20.0318 10.6229V10.1021H21.2853C22.3361 10.1687 23.1502 11.0864 23.1313 12.1853V15.9395L21.1335 14.2747V13.286H9.98621V21.56ZM13.2176 10.4166V9.27081C13.2196 8.84791 12.894 8.50314 12.4884 8.50001C12.0829 8.49793 11.7513 8.83854 11.7493 9.2604V9.27081V10.4166C11.7493 10.8395 12.0779 11.1822 12.4834 11.1822C12.889 11.1822 13.2176 10.8395 13.2176 10.4166ZM19.2266 10.385V9.29855C19.2266 8.89857 18.9159 8.57566 18.5334 8.57566C18.1508 8.57566 17.8401 8.89857 17.8401 9.29855V10.3839C17.8401 10.7829 18.1488 11.1068 18.5324 11.1079C18.9159 11.1079 19.2266 10.7839 19.2266 10.385ZM12.2783 15.1141C12.0021 15.1141 11.7783 15.338 11.7783 15.6141V16.5038C11.7783 16.78 12.0021 17.0038 12.2783 17.0038H13.168C13.4442 17.0038 13.668 16.78 13.668 16.5038V15.6141C13.668 15.338 13.4442 15.1141 13.168 15.1141H12.2783ZM14.6129 15.651C14.6129 15.3749 14.8368 15.151 15.1129 15.151H16.0026C16.2788 15.151 16.5027 15.3749 16.5027 15.651V16.5408C16.5027 16.8169 16.2788 17.0408 16.0027 17.0408H15.1129C14.8368 17.0408 14.6129 16.8169 14.6129 16.5408V15.651ZM20.4786 16.1586C20.4786 15.9791 20.7087 15.8844 20.8531 16.0045L25.9008 20.2049C26.0327 20.3147 26.0327 20.5059 25.9008 20.6157L20.8531 24.8161C20.7087 24.9363 20.4786 24.8416 20.4786 24.6621V21.9551C20.4512 21.9653 20.4214 21.9709 20.3902 21.9709C17.4663 21.9712 14.9309 23.8957 13.8895 24.8118C13.73 24.9522 13.4675 24.8298 13.5156 24.6311C13.9226 22.9501 15.4511 18.6043 20.3958 18.4745C20.4249 18.4737 20.4528 18.4783 20.4786 18.4872V16.1586Z" fill="white"/><rect width="31" height="32" rx="15.5" fill="white" fill-opacity="0.01"/></svg>',
		menuCalendarGray: '<svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.98621 17.56H9.21181L8.23962 19.6432H4.98731C4.43592 19.6432 3.98842 19.1766 3.98842 18.6016V8.18532C3.98442 8.13116 3.98242 8.07803 3.98242 8.02491C3.98442 6.96037 4.8135 6.09998 5.83437 6.10207H6.9851V6.62288C6.9851 7.48535 7.65536 8.18532 8.48344 8.18532C9.31152 8.18532 9.98178 7.48535 9.98178 6.62288V6.10207H13.0351V6.62288C13.0351 7.48535 13.7064 8.18532 14.5334 8.18532C15.3605 8.18532 16.0318 7.48535 16.0318 6.62288V6.10207H17.2853C18.3361 6.16873 19.1502 7.0864 19.1313 8.18532V11.9395L17.1335 10.2747V9.28601H5.98621V17.56ZM9.21763 6.41661V5.27081C9.21963 4.84791 8.89399 4.50314 8.48844 4.50001C8.08288 4.49793 7.75125 4.83854 7.74925 5.2604V5.27081V6.41661C7.74925 6.83951 8.07789 7.1822 8.48344 7.1822C8.88899 7.1822 9.21763 6.83951 9.21763 6.41661ZM15.2266 6.38497V5.29855C15.2266 4.89857 14.9159 4.57566 14.5334 4.57566C14.1508 4.57566 13.8401 4.89857 13.8401 5.29855V6.38393C13.8401 6.78287 14.1488 7.10682 14.5324 7.10786C14.9159 7.10786 15.2266 6.78392 15.2266 6.38497ZM8.27829 11.1141C8.00215 11.1141 7.77829 11.338 7.77829 11.6141V12.5038C7.77829 12.78 8.00215 13.0038 8.27829 13.0038H9.16803C9.44418 13.0038 9.66803 12.78 9.66803 12.5038V11.6141C9.66803 11.338 9.44418 11.1141 9.16803 11.1141H8.27829ZM10.6129 11.651C10.6129 11.3749 10.8368 11.151 11.1129 11.151H12.0026C12.2788 11.151 12.5027 11.3749 12.5027 11.651V12.5408C12.5027 12.8169 12.2788 13.0408 12.0027 13.0408H11.1129C10.8368 13.0408 10.6129 12.8169 10.6129 12.5408V11.651ZM16.4786 12.1586C16.4786 11.9791 16.7087 11.8844 16.8531 12.0045L21.9008 16.2049C22.0327 16.3147 22.0327 16.5059 21.9008 16.6157L16.8531 20.8161C16.7087 20.9363 16.4786 20.8416 16.4786 20.6621V17.9551C16.4512 17.9653 16.4214 17.9709 16.3902 17.9709C13.4663 17.9712 10.9309 19.8957 9.88954 20.8118C9.72999 20.9522 9.46753 20.8298 9.51562 20.6311C9.92256 18.9501 11.4511 14.6043 16.3958 14.4745C16.4249 14.4737 16.4528 14.4783 16.4786 14.4872V12.1586Z" fill="#a8adb4"/></svg>',
	};

	// eslint-disable-next-line no-undef
	const events = new EventList(list);
	events.load();
})();
