/**
 * @module calendar/booking/detail/fields/client
 */
jn.define('calendar/booking/detail/fields/client', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { inAppUrl } = require('in-app-url');
	const { showToast } = require('toast');
	const { Circle, Line } = require('utils/skeleton');

	const { Text6, Text4 } = require('ui-system/typography/text');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { Avatar, AvatarEntityType } = require('ui-system/blocks/avatar');

	class ClientField extends PureComponent
	{
		/** @return {BookingModel | null} */
		get bookingInfo()
		{
			return this.props.value;
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
			return false;
		}

		render()
		{
			return View(
				{},
				!this.bookingInfo && this.renderSkeleton(),
				this.bookingInfo && this.renderContent(),
			);
		}

		renderSkeleton()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Circle(32),
				View(
					{
						style: {
							flex: 1,
							marginLeft: Indent.M.toNumber(),
							flexDirection: 'column',
						},
					},
					this.renderEntityTitle(),
					Line(200, 14, Indent.XS.toNumber()),
				),
			);
		}

		renderContent()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
					onClick: this.openClientDetailCard,
				},
				this.renderEntityAvatar(),
				this.renderEntity(),
				this.bookingInfo.hasClient() && this.renderPhoneIcon(),
			);
		}

		renderEntityAvatar()
		{
			return Avatar({
				size: 32,
				entityType: AvatarEntityType.user,
				testId: 'calendar-booking-detail-client-avatar',
				entityId: this.bookingInfo.hasClient() ? this.bookingInfo.getClient().id : 0,
				uri: (this.bookingInfo.hasClient() && this.bookingInfo.canReadClient())
					? this.bookingInfo.getClient().image
					: null
				,
			});
		}

		renderEntity()
		{
			return View(
				{
					style: {
						flex: 1,
						marginLeft: Indent.M.toNumber(),
						flexDirection: 'column',
					},
				},
				this.bookingInfo.hasClient() && this.renderEntityTitle(),
				this.bookingInfo.hasClient() && this.renderEntityName(),
				!this.bookingInfo.hasClient() && this.renderEmptyEntityTitle(),
			);
		}

		renderEntityTitle()
		{
			return Text6({
				text: Loc.getMessage('M_CALENDAR_BOOKING_DETAIL_CLIENT'),
				color: Color.base4,
			});
		}

		renderEntityName()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				Text4({
					color: this.bookingInfo.canReadClient() ? Color.accentMainLink : Color.base4,
					ellipsize: 'end',
					numberOfLines: 1,
					text: this.bookingInfo.canReadClient()
						? this.bookingInfo.getClient().name
						: Loc.getMessage('M_CALENDAR_BOOKING_DETAIL_NO_ACCESS')
					,
				}),
				!this.bookingInfo.canReadClient() && IconView(
					{
						icon: Icon.LOCK,
						size: 20,
						color: Color.base4,
					},
				),
			);
		}

		renderEmptyEntityTitle()
		{
			return Text4({
				text: Loc.getMessage('M_CALENDAR_BOOKING_DETAIL_NO_CLIENT'),
				color: Color.base4,
			});
		}

		renderPhoneIcon()
		{
			if (!this.bookingInfo.canReadClient() || !this.bookingInfo.getClientPhones())
			{
				return null;
			}

			return IconView({
				icon: Icon.PHONE_UP,
				size: 28,
				color: Color.base4,
				onClick: this.onPhoneClick,
			});
		}

		onPhoneClick = () => {
			showToast({
				message: Loc.getMessage('M_CALENDAR_BOOKING_DETAIL_SOON'),
				iconName: Icon.INFO_CIRCLE.getIconName(),
			});
		};

		openClientDetailCard = () => {
			if (!this.bookingInfo.canReadClient())
			{
				return;
			}

			const entityType = this.bookingInfo.getClient().type.toLowerCase();
			const entityId = String(this.bookingInfo.getClient().id);

			inAppUrl.open(`/crm/${entityType}/details/${entityId}/`);
		};
	}

	module.exports = {
		ClientField: (props) => new ClientField(props),
	};
});
