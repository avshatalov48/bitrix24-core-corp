/**
 * @module calendar/event-view-form/fields/entity-relation
 */
jn.define('calendar/event-view-form/fields/entity-relation', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { NotifyManager } = require('notify-manager');
	const { Text5 } = require('ui-system/typography/text');
	const { inAppUrl } = require('in-app-url');

	const { UserFieldClass } = require('layout/ui/fields/user');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { withTheme } = require('layout/ui/fields/theme');

	const { EntityRelationType } = require('calendar/enums');
	const { UserFieldAirTheme } = require('calendar/layout/user-field-air-theme');

	/**
	 * @class EntityRelationField
	 */
	class EntityRelationField extends UserFieldClass
	{
		constructor(props)
		{
			super(props);

			this.customContentClickHandler = this.openEntityDetail;
		}

		get parentLayout()
		{
			return this.props.layout;
		}

		get entityRelation()
		{
			return this.props.entityRelation;
		}

		get ownerId()
		{
			return this.entityRelation.owner.id;
		}

		get entityType()
		{
			return this.entityRelation.entity.type;
		}

		get entityId()
		{
			return this.entityRelation.entity.id;
		}

		hasCustomTitle()
		{
			return true;
		}

		hasPermissions()
		{
			return true;
		}

		isEmpty()
		{
			return !this.entityRelation?.eventId;
		}

		renderCustomTitle()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
						paddingBottom: Indent.XL.toNumber(),
					},
				},
				Text5({
					text: this.getCustomTitleText(),
					color: Color.base4,
				}),
				this.renderDetailIcon(),
			);
		}

		renderRightIcons()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				this.renderMessageIcon(),
			);
		}

		renderMessageIcon()
		{
			return View(
				{
					testId: 'calendar-event-view-form-entity-relation-message-icon',
					style: {
						marginLeft: Indent.XL.toNumber(),
					},
					onClick: this.handleMessageIconClick,
				},
				IconView({
					icon: Icon.MESSAGE,
					size: 24,
					color: Color.base5,
				}),
			);
		}

		renderDetailIcon()
		{
			return View(
				{
					testId: 'calendar-event-view-form-entity-relation-detail-icon',
					style: {
						marginLeft: Indent.XL.toNumber(),
					},
					onClick: this.openEntityDetail,
				},
				IconView({
					icon: Icon.CHEVRON_TO_THE_RIGHT,
					size: 20,
					color: Color.base2,
				}),
			);
		}

		getCustomTitleText()
		{
			return this.entityRelation?.entity?.type === EntityRelationType.BOOKING
				? Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_ENTITY_RELATION_BOOKING_TITLE')
				: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_ENTITY_RELATION_DEAL_TITLE')
			;
		}

		handleMessageIconClick = () => {
			try
			{
				const dialogId = String(this.ownerId);

				void NotifyManager.showLoadingIndicator();

				void requireLazy('im:messenger/api/dialog-opener').then(({ DialogOpener }) => {
					NotifyManager.hideLoadingIndicator(true);
					DialogOpener.open({ dialogId });
				});
			}
			catch (errors)
			{
				NotifyManager.hideLoadingIndicator(false);
				console.error(errors);
			}
		};

		openEntityDetail = async () => {
			if (this.entityType === EntityRelationType.BOOKING)
			{
				void requireLazy('calendar:booking/detail').then(({ BookingDetail }) => {
					const bookingDetail = new BookingDetail({
						bookingId: this.entityId,
						entityRelation: this.entityRelation,
					});

					bookingDetail.open(this.parentLayout);
				});
			}
			else if (this.entityType === EntityRelationType.DEAL)
			{
				inAppUrl.open(`/crm/deal/details/${this.entityId}/`);
			}
		};
	}

	module.exports = { EntityRelationField: withTheme(EntityRelationField, UserFieldAirTheme) };
});
