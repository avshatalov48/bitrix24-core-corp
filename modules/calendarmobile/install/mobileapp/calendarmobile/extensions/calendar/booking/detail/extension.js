/**
 * @module calendar/booking/detail
 */
jn.define('calendar/booking/detail', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');
	const { PureComponent } = require('layout/pure-component');

	const { BookingAjax } = require('calendar/ajax/booking');
	const { BookingManager } = require('calendar/booking/manager');
	const { BookingDetailForm } = require('calendar/booking/detail/form');

	/**
	 * @class BookingDetail
	 */
	class BookingDetail extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.parentLayout = null;
			this.layoutWidget = null;

			this.state = {
				bookingInfo: null,
			};
		}

		get bookingId()
		{
			return this.props.bookingId;
		}

		get entityRelation()
		{
			return this.props.entityRelation;
		}

		async init()
		{
			const bookingInfo = BookingManager.getBooking(this.bookingId);
			if (Type.isNil(bookingInfo))
			{
				const { data } = await BookingAjax.bookingInfo(this.bookingId);

				BookingManager.setBooking(data);
			}

			this.setState({
				bookingInfo: BookingManager.getBooking(this.bookingId),
			});
		}

		render()
		{
			return ScrollView(
				{
					showsVerticalScrollIndicator: false,
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
					BookingDetailForm({
						bookingId: this.bookingId,
						entityRelation: this.entityRelation,
						bookingInfo: this.state.bookingInfo,
						parentWidget: this.parentLayout,
					}),
				),
			);
		}

		open(parentLayout = PageManager)
		{
			void new BottomSheet({ component: this })
				.setParentWidget(parentLayout)
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setTitleParams({
					text: Loc.getMessage('M_CALENDAR_BOOKING_DETAIL_TITLE'),
					type: 'wizard',
				})
				.showOnTop()
				.open()
				.then((widget) => {
					this.layoutWidget = widget;
					this.parentLayout = parentLayout;

					void this.init();
				})
			;
		}
	}

	module.exports = { BookingDetail };
});
