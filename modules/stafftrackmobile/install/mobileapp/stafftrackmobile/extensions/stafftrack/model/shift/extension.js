/**
 * @module stafftrack/model/shift
 */
jn.define('stafftrack/model/shift', (require, exports, module) => {
	const { StatusEnum } = require('stafftrack/model/shift/status');
	const { LocationEnum } = require('stafftrack/model/shift/location');
	const { CancelReasonEnum } = require('stafftrack/model/shift/cancel-reason');
	const { PullCommandEnum } = require('stafftrack/model/shift/pull-command');

	/**
	 * @class ShiftModel
	 */
	class ShiftModel
	{
		/**
		 * @param shiftDto {shiftDto}
		 */
		constructor(shiftDto)
		{
			this.shiftDto = shiftDto;
			this.id = BX.prop.getNumber(shiftDto, 'id', 0);
			this.userId = BX.prop.getNumber(shiftDto, 'userId', 0);
			this.shiftDate = this.prepareDate(shiftDto, 'shiftDate');
			this.dateCreate = this.prepareDate(shiftDto, 'dateCreate');
			this.status = BX.prop.getNumber(shiftDto, 'status', 0);
			this.location = BX.prop.getString(shiftDto, 'location', '');
			this.address = BX.prop.getString(shiftDto, 'address', '');
			this.geoImageUrl = BX.prop.getString(shiftDto, 'geoImageUrl', '');
			this.cancelReason = BX.prop.getString(shiftDto, 'cancelReason', '');
			this.dateCancel = this.prepareDate(shiftDto, 'dateCancel');
		}

		prepareDate(props, param)
		{
			const value = BX.prop.getString(props, param, '');

			return value === '' ? null : new Date(value);
		}

		getId()
		{
			return this.id;
		}

		getUserId()
		{
			return this.userId;
		}

		getShiftDate()
		{
			return this.shiftDate;
		}

		getDateCreate()
		{
			return this.dateCreate;
		}

		getStatus()
		{
			return this.status;
		}

		getLocation()
		{
			return this.location;
		}

		hasAddress()
		{
			return this.address !== '';
		}

		getAddress()
		{
			return this.address;
		}

		getGeoImageUrl()
		{
			return this.geoImageUrl;
		}

		getCancelReason()
		{
			return this.cancelReason;
		}

		getDateCancel()
		{
			return this.dateCancel;
		}

		isEmptyStatus()
		{
			return this.status === 0;
		}

		isWorkingStatus()
		{
			return this.status === StatusEnum.WORKING.toNumber();
		}

		isNotWorkingStatus()
		{
			return this.status === StatusEnum.NOT_WORKING.toNumber();
		}

		isCancelStatus()
		{
			return this.status === StatusEnum.CANCEL_WORKING.toNumber();
		}

		isCancelOrNotWorkingStatus()
		{
			return this.isNotWorkingStatus() || this.isCancelStatus();
		}

		getDto()
		{
			return this.shiftDto;
		}
	}

	module.exports = {
		ShiftModel,
		StatusEnum,
		LocationEnum,
		PullCommandEnum,
		CancelReasonEnum,
	};
});
