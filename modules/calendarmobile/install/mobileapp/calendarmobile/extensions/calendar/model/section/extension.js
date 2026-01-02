/**
 * @module calendar/model/section
 */
jn.define('calendar/model/section', (require, exports, module) => {
	const { Type } = require('type');
	const {
		BooleanParams,
		SectionPermissionActions,
		SectionExternalTypes,
		CalendarType,
	} = require('calendar/enums');

	/**
	 * @class SectionModel
	 */
	class SectionModel
	{
		constructor(props)
		{
			this.setFields(props);
		}

		setFields(props)
		{
			this.id = BX.prop.getNumber(props, 'ID', 0);
			this.name = BX.prop.getString(props, 'NAME', '');
			this.type = BX.prop.getString(props, 'CAL_TYPE', '');
			this.ownerId = BX.prop.getNumber(props, 'OWNER_ID', 0);
			this.color = BX.prop.getString(props, 'COLOR', '');
			this.externalType = BX.prop.getString(props, 'EXTERNAL_TYPE', '');
			this.active = BX.prop.getString(props, 'ACTIVE', 'Y');
			this.permissions = BX.prop.getObject(props, 'PERM', {});
			this.calDavCalendar = BX.prop.getString(props, 'CAL_DAV_CAL', '');
			this.calDavConnection = BX.prop.getString(props, 'CAL_DAV_CON', '');
			this.exchange = BX.prop.getBoolean(props, 'IS_EXCHANGE', false);
			this.collab = BX.prop.getBoolean(props, 'IS_COLLAB', false);
			this.connectionLinks = props?.connectionLinks ?? [];
			this.categoryId = 0;
		}

		getId()
		{
			return this.id;
		}

		getColor()
		{
			return this.color;
		}

		getName()
		{
			return this.name;
		}

		getType()
		{
			return this.type;
		}

		getOwnerId()
		{
			return this.ownerId;
		}

		getExternalType()
		{
			return this.externalType ?? (this.isCalDav() ? SectionExternalTypes.CALDAV : '');
		}

		getPermissions()
		{
			return this.permissions;
		}

		getCategoryId()
		{
			return this.categoryId;
		}

		isActive()
		{
			return this.active === BooleanParams.YES;
		}

		setSectionStatus(status)
		{
			this.active = status ? BooleanParams.YES : BooleanParams.NO;
		}

		setCategoryId(categoryId)
		{
			this.categoryId = categoryId;
		}

		canDo(action)
		{
			if (action === SectionPermissionActions.EDIT_SECTION && env.isCollaber)
			{
				return false;
			}

			return Boolean(this.permissions?.[action]);
		}

		isUserCalendar(userId)
		{
			return this.getType() === CalendarType.USER && this.getOwnerId() === userId;
		}

		isCompanyCalendar()
		{
			return this.getType() === CalendarType.COMPANY_CALENDAR && !this.getOwnerId();
		}

		isGroupCalendar()
		{
			return this.getType() === CalendarType.GROUP;
		}

		isCollab()
		{
			return this.collab;
		}

		isLocal()
		{
			return this.getExternalType() === SectionExternalTypes.LOCAL
				|| this.isCompanyCalendar()
				|| this.isGroupCalendar()
			;
		}

		isGoogle()
		{
			const googleTypes = [
				SectionExternalTypes.GOOGLE,
				SectionExternalTypes.GOOGLE_READONLY,
				SectionExternalTypes.GOOGLE_WRITE_READ,
				SectionExternalTypes.GOOGLE_FREEBUSY,
			];

			return googleTypes.includes(this.getExternalType());
		}

		isIcloud()
		{
			return this.getExternalType() === SectionExternalTypes.ICLOUD;
		}

		isOffice365()
		{
			return this.getExternalType() === SectionExternalTypes.OFFICE365;
		}

		isSyncSection()
		{
			if (this.isLocal())
			{
				return this.hasConnection();
			}

			return Object.values(SectionExternalTypes).find((type) => type === this.externalType)
				|| this.hasConnection()
				|| this.isCalDav()
			;
		}

		hasConnection()
		{
			return this.connectionLinks.length > 0;
		}

		isCalDav()
		{
			return Type.isStringFilled(this.calDavCalendar) && Type.isStringFilled(this.calDavConnection);
		}

		isExchange()
		{
			return this.exchange;
		}

		isExternal()
		{
			return !this.isLocal() && !this.isGoogle() && !this.isIcloud() && !this.isOffice365() && !this.isExchange();
		}
	}

	module.exports = { SectionModel };
});
