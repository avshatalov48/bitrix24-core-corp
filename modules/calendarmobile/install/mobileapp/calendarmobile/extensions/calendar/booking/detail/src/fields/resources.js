/**
 * @module calendar/booking/detail/fields/resources
 */
jn.define('calendar/booking/detail/fields/resources', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');

	const { Text5, Text4 } = require('ui-system/typography/text');
	const { Icon, IconView } = require('ui-system/blocks/icon');

	const { IconWithText } = require('calendar/event-view-form');

	class ResourcesField extends PureComponent
	{
		/** @return {BookingModel | null} */
		get bookingInfo()
		{
			return this.props.value;
		}

		get resources()
		{
			return this.bookingInfo?.getResources();
		}

		get resourcesWithAccess()
		{
			return this.resources?.filter((resource) => this.bookingInfo.canReadResource(resource.id));
		}

		get hasResourcesWithAccess()
		{
			return this.resourcesWithAccess?.length > 0;
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
			return this.resources ? this.resources.length === 0 : true;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				this.hasResourcesWithAccess && this.renderTitle(),
				!this.hasResourcesWithAccess && this.renderRestrictedTitle(),
				!this.hasResourcesWithAccess && this.renderRestrictedResources(),
				...this.resourcesWithAccess.map((resource) => IconWithText(
					Icon.PRODUCT,
					resource.name,
					`calendar-booking-detail-resource-${resource.id}`,
					false,
				)),
			);
		}

		renderTitle()
		{
			return Text5({
				testId: 'calendar-booking-detail-resources_TITLE',
				text: Loc.getMessage('M_CALENDAR_BOOKING_DETAIL_RESOURCES_COUNT', {
					'#COUNT#': this.resourcesWithAccess.length,
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
					testId: 'calendar-booking-detail-resources_TITLE-EMPTY',
					text: Loc.getMessage('M_CALENDAR_BOOKING_DETAIL_RESOURCES'),
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

		renderRestrictedResources()
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
		ResourcesField: (props) => new ResourcesField(props),
	};
});
