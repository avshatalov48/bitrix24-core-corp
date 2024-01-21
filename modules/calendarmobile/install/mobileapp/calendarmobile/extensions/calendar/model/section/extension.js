/**
 * @module calendar/model/section
 */
jn.define('calendar/model/section', (require, exports, module) => {
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

		isActive()
		{
			return this.active === 'Y';
		}

		setSectionStatus(status)
		{
			this.active = status ? 'Y' : 'N';
		}
	}

	module.exports = { SectionModel };
});
