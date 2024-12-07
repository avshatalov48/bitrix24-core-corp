/**
 * @module stafftrack/data-managers/shift-manager
 */
jn.define('stafftrack/data-managers/shift-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { EventEmitter } = require('event-emitter');

	const { ShiftAjax, DepartmentStatisticsAjax } = require('stafftrack/ajax');
	const { OptionManager } = require('stafftrack/data-managers/option-manager');
	const { SettingsManager } = require('stafftrack/data-managers/settings-manager');
	const { DateHelper } = require('stafftrack/date-helper');
	const { ShiftModel, PullCommandEnum } = require('stafftrack/model/shift');

	class ShiftManager extends EventEmitter
	{
		constructor()
		{
			super();

			this.setUid('Stafftrack.ShiftManager');

			this.mainData = null;
			this.usersShifts = {};
			this.departments = {};
			this.departmentMonths = {};

			this.pullSubscribe();
		}

		pullSubscribe()
		{
			BX.PULL.subscribe({
				moduleId: 'stafftrack',
				callback: (data) => {
					const command = BX.prop.getString(data, 'command', '');
					const params = BX.prop.getObject(data, 'params', {});

					if (command === PullCommandEnum.SHIFT_ADD.getValue())
					{
						this.addShiftToCache(params.shift, params.departmentIds);
					}

					if (command === PullCommandEnum.SHIFT_UPDATE.getValue())
					{
						this.updateShiftCache(params.shift, params.departmentIds);
					}

					if (command === PullCommandEnum.SHIFT_DELETE.getValue())
					{
						this.deleteShiftFromCache(new ShiftModel(params.shift).getId());
					}

					this.emit('updated');
				},
			});
		}

		/**
		 * @public
		 * @param date {string}
		 * @return {Promise<any>}
		 */
		async getMain(date)
		{
			if (!this.mainData)
			{
				this.mainData = await this.loadMain(date);

				OptionManager.setOptions(this.mainData.options);
				SettingsManager.setEnabledBySettings(this.mainData.config.enabledBySettings);
				SettingsManager.setGeoEnabled(this.mainData.config.isCheckInGeoEnabled);
				SettingsManager.setTimemanAvailable(this.mainData.config.timemanAvailable);
			}

			return this.mainData;
		}

		/**
		 * @private
		 * @param date {string}
		 * @return {Promise<any>}
		 */
		async loadMain(date)
		{
			this.loadMainPromise ??= ShiftAjax.loadMain(DateHelper.getCurrentDayCode());

			const { data } = await this.loadMainPromise;

			return data;
		}

		/**
		 * @public
		 * @param shiftDto {ShiftDto}
		 * @param departments {Department[]}
		 * @return {Promise<ShiftDto>}
		 */
		async addShift(shiftDto, departments)
		{
			this.mainData.currentShift = shiftDto;

			const { data } = await ShiftAjax.add(shiftDto);

			this.addShiftToCache(data.shift, departments.map((department) => department.id));

			return data;
		}

		addShiftToCache(shiftDto, departmentIds)
		{
			const shift = new ShiftModel(shiftDto);

			if (shift.getUserId() === this.mainData?.userInfo.id)
			{
				this.mainData.currentShift = shiftDto;
			}

			if (this.getCachedShiftById(shift.getId()))
			{
				return;
			}

			const userId = shift.getUserId();
			const monthCode = DateHelper.getMonthCode(shift.getShiftDate());

			if (this.usersShifts[userId]?.[monthCode])
			{
				this.usersShifts[userId][monthCode].push(shift);
			}

			for (const departmentId of departmentIds)
			{
				if (this.departments[departmentId])
				{
					this.departments[departmentId].shifts.push(shift);
				}

				if (this.departmentMonths[departmentId]?.[monthCode])
				{
					const statistics = this.departmentMonths[departmentId][monthCode];

					statistics.find((statisticsItem) => statisticsItem.userId === userId).checkinCount++;
				}
			}
		}

		/**
		 * @public
		 * @param shiftDto {ShiftDto}
		 * @param departments {Department[]}
		 * @return {Promise<ShiftDto>}
		 */
		async updateShift(shiftDto, departments)
		{
			const departmentIds = departments.map((department) => department.id);

			this.updateShiftCache(shiftDto, departmentIds);

			const { data } = await ShiftAjax.update(shiftDto.id, shiftDto);

			this.updateShiftCache(data.shift, departmentIds);

			return data;
		}

		updateShiftCache(shiftDto, departmentIds)
		{
			const shift = new ShiftModel(shiftDto);

			if (this.mainData?.currentShift && this.mainData.currentShift.id === shift.getId())
			{
				this.mainData.currentShift = shiftDto;
			}

			if (!this.getCachedShiftById(shift.getId()))
			{
				return;
			}

			const userId = shift.getUserId();
			const monthCode = DateHelper.getMonthCode(shift.getShiftDate());

			if (this.usersShifts[userId]?.[monthCode])
			{
				this.usersShifts[userId][monthCode] = this.usersShifts[userId][monthCode].map((it) => (
					it.getId() === shift.getId() ? shift : it
				));
			}

			for (const departmentId of departmentIds)
			{
				if (this.departments[departmentId])
				{
					this.departments[departmentId].shifts = this.departments[departmentId].shifts.map((it) => (
						it.getId() === shift.getId() ? shift : it
					));
				}
			}
		}

		async deleteShift(shiftId)
		{
			const result = await ShiftAjax.delete(shiftId);

			this.deleteShiftFromCache(shiftId);

			return result;
		}

		deleteShiftFromCache(shiftId)
		{
			const shiftToDelete = this.getCachedShiftById(shiftId);
			if (!shiftToDelete)
			{
				return;
			}

			const userId = shiftToDelete.getUserId();
			const monthCode = DateHelper.getMonthCode(shiftToDelete.getShiftDate());

			if (this.usersShifts[userId]?.[monthCode])
			{
				const userShifts = this.usersShifts[userId][monthCode];
				this.usersShifts[userId][monthCode] = userShifts.filter((it) => it.getId() !== shiftToDelete.getId());
			}

			for (const departmentId of Object.keys(this.departments))
			{
				const departmentShifts = this.departments[departmentId].shifts;
				this.departments[departmentId].shifts = departmentShifts.filter((shift) => shift.getId() !== shiftId);
			}

			for (const departmentId of Object.keys(this.departmentMonths))
			{
				const statistics = this.departmentMonths[departmentId][monthCode];

				statistics.find((statisticsItem) => statisticsItem.userId === userId).checkinCount--;
			}
		}

		getCachedShiftById(shiftId)
		{
			for (const userId of Object.keys(this.usersShifts))
			{
				for (const monthCode of Object.keys(this.usersShifts[userId]))
				{
					const shift = this.usersShifts[userId][monthCode].find((it) => it.getId() === shiftId);
					if (shift)
					{
						return shift;
					}
				}
			}

			for (const departmentId of Object.keys(this.departments))
			{
				const shift = this.departments[departmentId].shifts.find((it) => it.getId() === shiftId);
				if (shift)
				{
					return shift;
				}
			}

			return null;
		}

		hasUserShiftsForMonth(userId, monthCode)
		{
			return !Type.isNil(this.getCachedUserShiftsForMonth(userId, monthCode));
		}

		/**
		 * @public
		 * @param userId {number}
		 * @param monthCode {string}
		 * @return {ShiftModel[]|null}
		 */
		getCachedUserShiftsForMonth(userId, monthCode)
		{
			return this.usersShifts[userId]?.[monthCode];
		}

		/**
		 * @public
		 * @param userId {number}
		 * @param monthCode {string}
		 * @return {Promise<ShiftModel[]>}
		 */
		async getUserShiftsForMonth(userId, monthCode)
		{
			this.usersShifts[userId] ??= {};
			this.usersShifts[userId][monthCode] ??= await this.loadUserShiftsForMonth(userId, monthCode);

			return this.usersShifts[userId][monthCode];
		}

		/**
		 * @private
		 * @param userId {number}
		 * @param monthCode {string}
		 * @return {Promise<ShiftModel[]>}
		 */
		async loadUserShiftsForMonth(userId, monthCode)
		{
			const date = DateHelper.getDateFromMonthCode(monthCode);
			const start = new Date(date.getFullYear(), date.getMonth(), 1);
			const end = new Date(date.getFullYear(), date.getMonth() + 1, 0);

			this.usersShiftPromises ??= {};
			this.usersShiftPromises[userId] ??= {};
			this.usersShiftPromises[userId][monthCode] ??= ShiftAjax.list({
				DATE_FROM: DateHelper.getDayCode(start),
				DATE_TO: DateHelper.getDayCode(end),
				USER_ID: userId,
			});

			const { data } = await this.usersShiftPromises[userId][monthCode];

			return data.shiftList.map((rawShift) => new ShiftModel(rawShift));
		}

		hasDepartmentMonthStatistics(departmentId, monthCode)
		{
			return !Type.isNil(this.departmentMonths[departmentId])
				&& !Type.isNil(this.departmentMonths[departmentId][monthCode])
			;
		}

		hasDepartmentStatistics(departmentId)
		{
			return !Type.isNil(this.departments[departmentId]);
		}

		/**
		 * @public
		 * @param departmentId {number}
		 * @param monthCode {string}
		 * @return {Promise<MonthStatistics>}
		 */
		async getDepartmentMonthStatistics(departmentId, monthCode)
		{
			this.departmentMonths[departmentId] ??= {};
			this.departmentMonths[departmentId][monthCode] ??= await this.loadDepartmentMonthStatistics(
				departmentId,
				monthCode,
			);

			return this.departmentMonths[departmentId][monthCode];
		}

		/**
		 * @private
		 * @param departmentId {number}
		 * @param monthCode {string}
		 * @return {Promise<MonthStatistics>}
		 */
		async loadDepartmentMonthStatistics(departmentId, monthCode)
		{
			this.departmentMonthsPromises ??= {};
			this.departmentMonthsPromises[departmentId] ??= {};
			this.departmentMonthsPromises[departmentId][monthCode] ??= DepartmentStatisticsAjax.getForMonth(
				departmentId,
				monthCode,
			);

			const { data } = await this.departmentMonthsPromises[departmentId][monthCode];

			return data.statistics.map((statisticsItem) => ({
				userId: parseInt(statisticsItem.id, 10),
				checkinCount: parseInt(statisticsItem.checkinCount, 10),
			}));
		}

		/**
		 * @public
		 * @param departmentId {number}
		 * @return {Promise<{users: User[], shifts: ShiftModel[]}>}
		 */
		async getDepartmentStatistics(departmentId)
		{
			this.departments[departmentId] ??= await this.loadDepartmentStatistics(departmentId);

			return this.departments[departmentId];
		}

		/**
		 * @private
		 * @param departmentId {number}
		 * @return {Promise<{users: User[], shifts: ShiftModel[]}>}
		 */
		async loadDepartmentStatistics(departmentId)
		{
			this.departmentPromises ??= {};
			this.departmentPromises[departmentId] ??= DepartmentStatisticsAjax.get(
				departmentId,
				DateHelper.getCurrentDayCode(),
			);

			const { data } = await this.departmentPromises[departmentId];

			return {
				users: data.users.map((user) => ({
					...user,
					id: parseInt(user.id, 10),
				})),
				shifts: data.shifts.map((rawShift) => new ShiftModel(rawShift)),
			};
		}
	}

	module.exports = { ShiftManager: new ShiftManager() };
});
