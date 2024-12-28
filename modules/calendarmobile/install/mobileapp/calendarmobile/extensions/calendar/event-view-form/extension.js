/**
 * @module calendar/event-view-form
 */

jn.define('calendar/event-view-form', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');

	const { H2 } = require('ui-system/typography/heading');

	const { MoreButton } = require('calendar/event-view-form/layout/more-button');
	const { EventViewForm } = require('calendar/event-view-form/form');
	const { CalendarType } = require('calendar/enums');
	const { DataLoader } = require('calendar/event-view-form/data-loader');

	const initialLayoutTitle = {
		text: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_TITLE'),
		type: 'entity',
	};

	/**
	 * @class EventViewForm
	 */
	class EventView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutTitle = initialLayoutTitle;

			this.init();
		}

		init()
		{
			this.moreButton = new MoreButton(this.props);

			if (this.props.eventId)
			{
				this.props.layout.setRightButtons([this.moreButton.getButton()]);
			}
		}

		render()
		{
			return View(
				{},
				this.props.eventId && this.renderContent(),
				!this.props.eventId && this.renderEmptyState(),
			);
		}

		renderContent()
		{
			return ScrollView(
				{
					showsVerticalScrollIndicator: false,
					scrollEventThrottle: 10,
					onScroll: this.handleStickyTitle,
					style: {
						flex: 1,
						width: '100%',
						backgroundColorGradient: {
							start: Color.bgNavigation.toHex(),
							middle: Color.bgContentSecondary.toHex(),
							end: Color.bgContentSecondary.toHex(),
							angle: 90,
						},
					},
				},
				View(
					{
						style: {
							paddingBottom: 96,
							backgroundColor: Color.bgContentSecondary.toHex(),
						},
					},
					EventViewForm({
						...this.props,
						moreButton: this.moreButton,
					}),
				),
			);
		}

		renderEmptyState()
		{
			return View(
				{
					style: {
						flex: 1,
						alignItems: 'center',
						justifyContent: 'center',
						paddingHorizontal: Indent.XL3.toNumber(),
					},
				},
				H2({
					text: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_EMPTY_TITLE'),
					color: Color.base2,
					style: {
						textAlign: 'center',
					},
				}),
			);
		}

		handleStickyTitle = ({ contentOffset }) => {
			const layoutTitle = contentOffset.y > 35 ? this.getEventNameLayoutTitle() : initialLayoutTitle;

			if (layoutTitle.type !== this.layoutTitle.type && layoutTitle.text)
			{
				this.layoutTitle = layoutTitle;
				this.props.layout.setTitle(layoutTitle);
			}
		};

		getEventNameLayoutTitle()
		{
			return {
				text: this.props.event?.name,
				type: 'common',
			};
		}

		/**
		 * @public
		 * @param {object} params
		 * @param {PageManager} [params.parentLayout]
		 * @param {number} [params.eventId]
		 * @param {number} [params.dateFromTs]
		 * @param {number} [params.ownerId]
		 * @param {string} [params.calType]
		 * @return void
		 */
		static async open({
			parentLayout,
			eventId,
			dateFromTs,
			ownerId = env.userId,
			calType = CalendarType.USER,
		})
		{
			const event = DataLoader.getEvent({ eventId, dateFromTs });
			const notRequestedUsers = DataLoader.getNotRequestedUserIds(event);
			const getEventById = true;

			if (!event?.permissions || !event?.files)
			{
				const loadEventPromise = DataLoader.loadEvent({
					eventId,
					event,
					notRequestedUsers,
					getEventById,
				});

				if (!event)
				{
					await loadEventPromise;
				}
			}

			// eslint-disable-next-line promise/catch-or-return
			parentLayout.openWidget('layout', {
				titleParams: initialLayoutTitle,
			}).then((layout) => {
				const component = new EventView({
					ownerId,
					calType,
					layout,
					event,
					eventId,
					dateFromTs,
				});

				layout.showComponent(component);
			});
		}
	}

	module.exports = { EventView };
});
