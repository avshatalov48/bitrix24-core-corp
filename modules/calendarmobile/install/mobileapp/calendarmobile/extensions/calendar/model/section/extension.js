/**
 * @module calendar/model/section
 */
jn.define('calendar/model/section', (require, exports, module) => {
	const { Type } = require('type');
	const {
		BooleanParams,
		SectionPermissionActions,
		SectionExternalTypes,
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
			this.connectionLinks = props?.connectionLinks ?? [];
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
			return this.externalType;
		}

		getPermissions()
		{
			return this.permissions;
		}

		isActive()
		{
			return this.active === BooleanParams.YES;
		}

		setSectionStatus(status)
		{
			this.active = status ? BooleanParams.YES : BooleanParams.NO;
		}

		canDo(action)
		{
			if (action === SectionPermissionActions.EDIT_SECTION && env.isCollaber)
			{
				return false;
			}

			return Boolean(this.permissions?.[action]);
		}

		isSyncSection()
		{
			if (this.externalType === SectionExternalTypes.LOCAL)
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
	}

	module.exports = { SectionModel };
});
