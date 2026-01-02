/**
 * @module calendar/booking/detail/fields/services
 */
jn.define('calendar/booking/detail/fields/services', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');

	const { Text5, Text4 } = require('ui-system/typography/text');
	const { Icon, IconView } = require('ui-system/blocks/icon');

	const { IconWithText } = require('calendar/event-view-form');

	class ServicesField extends PureComponent
	{
		/** @return {BookingModel | null} */
		get bookingInfo()
		{
			return this.props.value;
		}

		get services()
		{
			return this.bookingInfo?.getServices();
		}

		get servicesWithAccess()
		{
			return this.services?.filter((service) => this.bookingInfo.canReadService(service.id));
		}

		get hasServicesWithAccess()
		{
			return this.servicesWithAccess?.length > 0;
		}

		getId()
		{
			return this.props.id;
		}

		isReadOnly()
		{
			return this.props.readOnly;
		}

		isRequired()
		{
			return false;
		}

		isEmpty()
		{
			return this.services ? this.services.length === 0 : true;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				this.hasServicesWithAccess && this.renderTitle(),
				!this.hasServicesWithAccess && this.renderRestrictedTitle(),
				!this.hasServicesWithAccess && this.renderRestrictedServices(),
				...this.servicesWithAccess.map((service) => IconWithText(
					Icon.THREE_PERSONS,
					service.name,
					`calendar-booking-detail-service-${service.id}`,
					false,
				)),
			);
		}

		renderTitle()
		{
			return Text5({
				testId: 'calendar-booking-detail-services_TITLE',
				text: Loc.getMessage('M_CALENDAR_BOOKING_DETAIL_SERVICES_COUNT', {
					'#COUNT#': this.servicesWithAccess.length,
				}),
				color: Color.base4,
			});
		}

		renderRestrictedTitle()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Text5({
					testId: 'calendar-booking-detail-services_TITLE-EMPTY',
					text: Loc.getMessage('M_CALENDAR_BOOKING_DETAIL_SERVICES'),
					color: Color.base4,
				}),
				IconView({
					icon: Icon.LOCK,
					size: 16,
					color: Color.base4,
					style: {
						marginLeft: Indent.XS2.toNumber(),
					},
				}),
			);
		}

		renderRestrictedServices()
		{
			return Text4({
				text: Loc.getMessage('M_CALENDAR_BOOKING_DETAIL_NO_ACCESS'),
				color: Color.base4,
				style: {
					marginTop: Indent.L.toNumber(),
				},
			});
		}
	}

	module.exports = {
		ServicesField: (props) => new ServicesField(props),
	};
});
