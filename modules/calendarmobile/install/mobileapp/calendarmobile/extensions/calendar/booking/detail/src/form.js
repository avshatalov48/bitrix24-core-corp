/**
 * @module calendar/booking/detail/form
 */
jn.define('calendar/booking/detail/form', (require, exports, module) => {
	const { Form, CompactMode } = require('layout/ui/form');
	const { Color, Component, Indent } = require('tokens');
	const { Type } = require('type');
	const { Loc } = require('loc');

	const { connect } = require('statemanager/redux/connect');
	const { usersSelector } = require('statemanager/redux/slices/users');

	const { ClientField } = require('calendar/booking/detail/fields/client');
	const { NoteField } = require('calendar/booking/detail/fields/note');
	const { ManagerField } = require('calendar/booking/detail/fields/manager');
	const { ResourcesField } = require('calendar/booking/detail/fields/resources');
	const { ServicesField } = require('calendar/booking/detail/fields/services');

	const NullCompactCreateFactory = {
		create: () => null,
	};

	const BookingDetailForm = (props) => {
		const {
			bookingInfo,
			entityRelation,
			parentWidget,
			owner,
		} = props;

		return new Form({
			parentWidget,
			testId: 'calendar-booking-detail-form',
			useState: false,
			style: BookingDetailFormStyles,
			compactMode: CompactMode.NONE,
			compactFieldFactory: NullCompactCreateFactory,
			hideEmptyReadonlyFields: true,
			primaryFields: [
				{
					factory: ClientField,
					props: {
						id: BookingDetailFields.CLIENT,
						value: bookingInfo,
						readOnly: true,
						required: true,
					},
				},
				Type.isStringFilled(bookingInfo?.note) && {
					factory: NoteField,
					props: {
						id: BookingDetailFields.NOTE,
						value: bookingInfo,
						readOnly: true,
					},
				},
			].filter(Boolean),
			secondaryFields: [
				{
					factory: ManagerField,
					props: {
						id: BookingDetailFields.MANAGER,
						value: [entityRelation.owner.id],
						ownerId: entityRelation.owner.id,
						readOnly: true,
						required: true,
						multiple: false,
						showTitle: false,
						title: Loc.getMessage('M_CALENDAR_BOOKING_DETAIL_MANAGER'),
						useState: false,
						config: {
							canOpenUserList: false,
							items: [owner],
							provider: {
								context: 'CALENDAR_BOOKING_DETAIL_owner',
							},
							useLettersForEmptyAvatar: true,
							selectorTitle: Loc.getMessage('M_CALENDAR_BOOKING_DETAIL_MANAGER'),
						},
					},
				},
				bookingInfo?.resources?.length > 0 && {
					factory: ResourcesField,
					props: {
						id: BookingDetailFields.RESOURCES,
						value: bookingInfo,
						readOnly: true,
					},
				},
				bookingInfo?.services?.length > 0 && {
					factory: ServicesField,
					props: {
						id: BookingDetailFields.SERVICES,
						value: bookingInfo,
						readOnly: true,
					},
				},
			].filter(Boolean),
		});
	};

	const selectMappedUserById = (state, id) => {
		const user = usersSelector.selectById(state, id);

		return user ? {
			id: user.id,
			title: user.fullName,
			imageUrl: user.avatarSize100,
			customData: {
				position: user.workPosition,
			},
		} : undefined;
	};

	const BookingDetailFields = {
		CLIENT: 'client',
		NOTE: 'note',
		MANAGER: 'manager',
		SERVICES: 'services',
		RESOURCES: 'resources',
	};

	const BookingDetailFormStyles = {
		primaryContainer: {
			backgroundColor: Color.bgContentPrimary.toHex(),
			paddingTop: Indent.L.toNumber(),
			paddingBottom: 0,
		},
		primaryField: {
			paddingHorizontal: Component.areaPaddingLr.toNumber(),
			marginBottom: Indent.XL3.toNumber(),
		},
		secondaryContainer: {
			marginTop: Indent.XL2.toNumber(),
			paddingHorizontal: Component.areaPaddingLr.toNumber(),
		},
		secondaryField: {
			marginBottom: Component.cardListGap.toNumber(),
		},
	};

	const mapStateToProps = (state, { entityRelation }) => {
		return {
			owner: selectMappedUserById(state, entityRelation.owner.id),
		};
	};

	module.exports = {
		BookingDetailForm: connect(mapStateToProps)(BookingDetailForm),
	};
});
