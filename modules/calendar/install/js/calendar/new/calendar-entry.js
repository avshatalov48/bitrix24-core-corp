;(function(window) {

	function EntryController(calendar, data)
	{
		this.calendar = calendar;
		this.pulledEntriesIndex = {};
		this.requestedEntriesIndex = {};
		this.entriesRaw = [];
		this.userIndex = {};
		this.loadedEntriesIndex = {};
		this.externalEntryIndex = {};
	}

	EntryController.prototype = {
		getList: function (params)
		{
			if ((params.startDate
				&& params.finishDate
				&& !this.checkDateRange(params.startDate, params.finishDate))
				|| params.loadNext
				|| params.loadPrevious
			)
			{
				this.loadEntries(params);
				return false;
			}

			var
				activeSectionIndex = {},
				entry,
				entries = [],
				entriesRaw = this.entriesRaw;

			if(this.calendar.util.type === 'location')
			{
				this.calendar.roomsManager.getRoomsInfo().allActive.forEach(function(sectionId)
				{
					activeSectionIndex[sectionId === 'tasks' ? sectionId : parseInt(sectionId)] = true;
				});
			}
			else
			{
				this.calendar.sectionManager.getSectionsInfo().allActive.forEach(function(sectionId)
				{
					activeSectionIndex[sectionId === 'tasks' ? sectionId : parseInt(sectionId)] = true;
				});
			}

			for (var i = 0; i < entriesRaw.length; i++)
			{
				if (entriesRaw[i])
				{
					if ((entriesRaw[i]['~TYPE'] === 'tasks' && !activeSectionIndex['tasks'])
						||
						(entriesRaw[i]['~TYPE'] !== 'tasks' && entriesRaw[i]['SECT_ID']
						&& !activeSectionIndex[parseInt(entriesRaw[i]['SECT_ID'])])
					)
					{
						continue;
					}

					entry = new Entry(this.calendar, entriesRaw[i]);
					if (params.viewRange)
					{
						if (entry.applyViewRange(params.viewRange))
						{
							entries.push(entry);
						}
					}
					else
					{
						entries.push(entry);
					}
				}
			}

			return entries;
		},

		canDo: function(entry, action)
		{
			if (typeof entry !== 'object' && action === 'add_event')
			{
				if(this.calendar.util.type === 'location')
				{
					return true;
				}
				return !this.calendar.util.readOnlyMode();
			}

			if ((action === 'edit' || action === 'delete') && !this.calendar.util.readOnlyMode())
			{
				if ((entry.isMeeting() && entry.id !== entry.parentId)
				|| entry.isResourcebooking())
				{
					return false;
				}

				var section = this.calendar.sectionManager.getSection(entry.sectionId);
				return section && section.canDo && section.canDo('edit');
			}
			return false;
		},

		getUsableDateTime: function(timestamp, roundMin)
		{
			if (typeof timestamp == 'object' && timestamp.getTime)
				timestamp = timestamp.getTime();

			var r = (roundMin || 10) * 60 * 1000;
			timestamp = Math.ceil(timestamp / r) * r;
			return new Date(timestamp);
		},

		getTimeForNewEntry: function(date)
		{
			date = this.getUsableDateTime(date);

			return {
				from : date,
				to : new Date(date.getTime() + 3600000)
			};
		},

		getDefaultEntryName: function()
		{
			return this.calendar.newEntryName || BX.message('EC_DEFAULT_ENTRY_NAME');
		},

		moveEventToNewDate: function(entry, dateFrom, dateTo, options)
		{
			if (!options)
			{
				options = {};
			}
			entry.from.setFullYear(dateFrom.getFullYear(), dateFrom.getMonth(), dateFrom.getDate());
			if (entry.fullDay)
			{
				entry.from.setHours(dateFrom.getHours(), dateFrom.getMinutes(), 0, 0);
			}

			if (dateTo && BX.type.isDate(dateTo))
			{
				entry.to.setFullYear(dateTo.getFullYear(), dateTo.getMonth(), dateTo.getDate());
				if (entry.fullDay)
				{
					entry.to.setHours(dateTo.getHours(), dateTo.getMinutes(), 0, 0);
				}
			}
			else
			{
				entry.to = new Date(entry.from.getTime() + (entry.data.DT_LENGTH - (entry.fullDay ? 1 : 0)) * 1000);
			}

			if (this.calendar.isExternalMode())
			{
				this.calendar.triggerEvent('entryOnDragEnd', {
					entry: entry,
					dateFrom: entry.from,
					dateTo: entry.to
				});
				return;
			}

			if (this.calendar.isExternalMode())
			{
				this.calendar.triggerEvent('entryOnDragEnd', {
					entry: entry,
					dateFrom: entry.from,
					dateTo: entry.to,
					previousDateFrom: BX.parseDate(entry.data.DATE_FROM),
					previousDateTo: BX.parseDate(entry.data.DATE_TO),
				});
				return;
			}

			if (entry.isMeeting()
				&& options.sendInvitesAgain === undefined
				&& entry.getAttendees().find(function(item){return item.STATUS === 'N';}))
			{
				BX.Calendar.EntryManager.showReInviteUsersDialog({
					callback: function(params) {
						this.moveEventToNewDate(
							entry, dateFrom, dateTo,
							{sendInvitesAgain: params.sendInvitesAgain});
					}.bind(this)
				});
				return false;
			}

			var attendees = [];
			if (entry.isMeeting())
			{
				entry.data['ATTENDEE_LIST'].forEach(function(user){attendees.push(user['id']);});
			}

			BX.ajax.runAction('calendar.api.calendarentryajax.moveEvent', {
				data: {
					id: entry.id,
					current_date_from: entry.data.DATE_FROM,
					date_from: entry.isFullDay() ? this.calendar.util.formatDate(entry.from) : this.calendar.util.formatDateTime(entry.from),
					date_to: entry.isFullDay() ? this.calendar.util.formatDate(entry.to) : this.calendar.util.formatDateTime(entry.to),
					skip_time: entry.isFullDay() ? 'Y' : 'N',
					attendees: attendees,
					location: entry.location || '',
					recursive: entry.isRecursive() ? 'Y' : 'N',
					is_meeting: entry.isMeeting() ? 'Y' : 'N',
					section: entry.sectionId,
					timezone: this.calendar.util.getUserOption('timezoneName'), //timezone
					set_timezone: 'Y',
					sendInvitesAgain: options.sendInvitesAgain ? 'Y' : 'N',
					requestUid: BX.Calendar.Util.registerRequestId()
				}
			}).then(function(response){
				if (response && response.data)
				{
					if (entry.isMeeting() && response.data.busy_warning)
					{
						alert(BX.message('EC_BUSY_ALERT'));
					}

					if (response.data.location_busy_warning)
					{
						alert(BX.message('EC_LOCATION_RESERVE_ERROR'));
					}

					this.calendar.reload();
				}
			}.bind(this));
		},

		viewEntry: function(params)
		{
			this.calendar.getView().showViewSlider(params);
		},

		editEntry: function(params)
		{
			this.calendar.getView().showEditSlider(params);
		},

		checkDateRange: function(start, end, params)
		{
			params = BX.Type.isObjectLike(params) ? params : {};

			if (this.calendar.isExternalMode())
			{
				return this.externalEntryIndex[this.getChunkIdByDate(start)]
					&& this.externalEntryIndex[this.getChunkIdByDate(end)];
			}
			else
			{
				if(this.calendar.util.type === 'location')
				{
					params.sections = params.sections || this.calendar.roomsManager.getRoomsInfo().allActive;
				}
				else
				{
					params.sections = params.sections || this.calendar.sectionManager.getSectionsInfo().allActive;
				}
				params.index = params.index || this.pulledEntriesIndex;
				var i, sectionId;
				for (i = 0; i < params.sections.length; i++)
				{
					sectionId = params.sections[i];
					if (!params.index[sectionId]
						|| !params.index[sectionId][this.getChunkIdByDate(start)]
						|| !params.index[sectionId][this.getChunkIdByDate(end)]
					)
					{
						return false;
					}
				}
			}

			return true;
		},

		getChunkIdByDate: function(date)
		{
			return date.getFullYear() + '-' + (date.getMonth() + 1);
		},

		fillChunkIndex: function(startDate, finishDate, params)
		{
			params = BX.Type.isObjectLike(params) ? params : {};

			if (!this.loadedStartDate)
				this.loadedStartDate = startDate;
			else if (startDate.getTime() < this.loadedStartDate.getTime())
				this.loadedStartDate = startDate;

			if (!this.loadedFinishDate)
				this.loadedFinishDate = finishDate;
			else if (finishDate.getTime() > this.loadedFinishDate.getTime())
				this.loadedFinishDate = finishDate;

			var date = new Date();
			var iter = 0;
			date.setFullYear(startDate.getFullYear(), startDate.getMonth(), 1);
			var lastChunkId = this.getChunkIdByDate(finishDate);
			var chunkId = this.getChunkIdByDate(date);
			var value = params.value === undefined ? true : params.value;

			if (this.calendar.isExternalMode())
			{
				this.externalEntryIndex[chunkId] = value;
				this.externalEntryIndex[lastChunkId] = value;
				while (chunkId !== lastChunkId && iter < 100)
				{
					this.externalEntryIndex[chunkId] = value;
					date.setMonth(date.getMonth() + 1);
					chunkId = this.getChunkIdByDate(date);
					iter++;
				}
			}
			else
			{
				if(this.calendar.util.type === 'location')
				{
					params.sections = params.sections || this.calendar.roomsManager.getRoomsInfo().allActive;
				}
				else
				{
					params.sections = params.sections || this.calendar.sectionManager.getSectionsInfo().allActive;
				}
				params.index = params.index || this.pulledEntriesIndex;

				var index = params.index;
				params.sections.forEach(function(sectinId)
				{
					if (!index[sectinId])
					{
						index[sectinId] = {};
					}

					index[sectinId][chunkId] = value;
					index[sectinId][lastChunkId] = value;
				});

				while (chunkId !== lastChunkId && iter < 100)
				{
					params.sections.forEach(function(sectinId)
					{
						index[sectinId][chunkId] = value;
					});

					date.setMonth(date.getMonth() + 1);
					chunkId = this.getChunkIdByDate(date);
					iter++;
				}
			}
		},

		getLoadedEntiesLimits: function()
		{
			return {start: this.loadedStartDate, end: this.loadedFinishDate};
		},

		loadEntries: function (params)
		{
			if (this.calendar.isExternalMode())
			{
				return this.loadExternalEntries(params);
			}

			// Fulfill previous deletions to avoid data inconsistency
			BX.Calendar.EntryManager.doDelayedActions()
				.then(function() {
					if (this.loadInProgress)
					{
						this.delayedReload = true;
						return;
					}

					this.loadInProgress = true;
					var sections = this.calendar.util.type === 'location'
						? this.calendar.roomsManager.getRoomsInfo()
						: this.calendar.sectionManager.getSectionsInfo();

					BX.ajax.runAction('calendar.api.calendarentryajax.loadEntries', {
						data: {
							ownerId: this.calendar.util.ownerId,
							type: this.calendar.util.type,
							month_from: params.startDate ? (params.startDate.getMonth() + 1) : '',
							year_from: params.startDate ? params.startDate.getFullYear() : '',
							month_to: params.finishDate ? params.finishDate.getMonth() + 1 : '',
							year_to: params.finishDate ? params.finishDate.getFullYear() : '',
							active_sect: sections.active,
							sup_sect: sections.superposed,
							loadNext: params.loadNext ? 'Y' : 'N',
							loadPrevious: params.loadPrevious ? 'Y' : 'N',
							loadLimit: params.loadLimit || 0,
							cal_dav_data_sync: this.calendar.reloadGoogle ? 'Y' : 'N'
						}
					}).then(
						function(response){
							this.calendar.hideLoader();
							this.loadInProgress = false;

							if (this.delayedReload)
							{
								this.delayedReload = false;
								this.loadEntries(params);
							}
							else
							{
								if (response && response.data)
								{
									this.handleEntriesList(response.data.entries, response.data.userIndex);

									if (!params.finishDate && this.entriesRaw.length > 0)
									{
										var finishDate = this.entriesRaw[this.entriesRaw.length - 1].DATE_FROM;
										finishDate = BX.parseDate(finishDate);
										if (finishDate)
										{
											finishDate.setFullYear(finishDate.getFullYear(), finishDate.getMonth(), 0);
											params.finishDate = finishDate;
										}
									}

									if (params.startDate && params.finishDate)
									{
										this.fillChunkIndex(params.startDate, params.finishDate, {
											sections: sections.allActive
										});
									}

									if (BX.type.isFunction(params.finishCallback))
									{
										params.finishCallback(response);
									}

									this.calendar.reloadGoogle = false;

									BX.Event.EventEmitter.emit('BX.Calendar:onEntryListReload');
								}
							}
						}.bind(this),
						function(){
							this.loadInprogress = false;
							this.calendar.hideLoader();
						}.bind(this)
					);
				}.bind(this));
		},

		loadExternalEntries: function (params)
		{
			if (params.showLoader)
			{
				this.calendar.showLoader();
			}

			this.calendar.triggerEvent('loadEntries',
				{
					params: params,
					onLoadCallback : function(json)
					{
						this.calendar.hideLoader();
						this.handleEntriesList(json.entries);

						if (!params.finishDate && this.entriesRaw.length > 0)
						{
							var finishDate = this.entriesRaw[this.entriesRaw.length - 1].DATE_FROM;
							finishDate = BX.parseDate(finishDate);
							if (finishDate)
							{
								finishDate.setFullYear(finishDate.getFullYear(), finishDate.getMonth(), 0);
								params.finishDate = finishDate;
							}
						}

						if (params.startDate && params.finishDate)
						{
							this.fillChunkIndex(params.startDate, params.finishDate);
						}

						if (BX.type.isFunction(params.finishCallback))
						{
							params.finishCallback(json);
						}
					}.bind(this),
					onErrorCallback : function(error)
					{
						this.calendar.hideLoader();
					}.bind(this)
				});
		},

		handleEntriesList: function(entries, userIndex)
		{
			if (entries && entries.length)
			{
				var
					i,
					smartId,
					showDeclined = this.calendar.util.getUserOption('showDeclined');

				for (i = 0; i < entries.length; i++)
				{
					if((!showDeclined || parseInt(entries[i].CREATED_BY) !== this.calendar.util.userId)
						&& entries[i].MEETING_STATUS === 'N')
					{
						continue;
					}
					smartId = this.getUniqueId(entries[i]);
					if (this.loadedEntriesIndex[smartId] === undefined)
					{
						this.entriesRaw.push(entries[i]);
						this.loadedEntriesIndex[smartId] = this.entriesRaw.length - 1;
					}
					else
					{
						if (entries[i].CAL_TYPE === this.calendar.util.type
							&&
							parseInt(entries[i].OWNER_ID) === parseInt(this.calendar.util.ownerId)
						)
						{
							this.entriesRaw[this.loadedEntriesIndex[smartId]] = entries[i];
						}
					}
				}
			}

			if (BX.type.isNotEmptyObject(userIndex))
			{
				for (var id in userIndex)
				{
					if (userIndex.hasOwnProperty(id))
					{
						this.userIndex[id] = userIndex[id];
					}
				}
			}

			BX.Calendar.EntryManager.setUserIndex(this.userIndex);
		},

		getUniqueId: function(entryData, entry)
		{
			var sid = entryData.PARENT_ID || entryData.ID;
			if (entryData.RRULE)
			{
				sid += '|' + (entry ? this.calendar.util.formatDate(entry.from) : this.calendar.util.formatDate(BX.parseDate(entryData.DATE_FROM)));
			}

			if (entryData['~TYPE'] === 'tasks')
			{
				sid += '|' + 'task';
			}
			return sid;
		},

		sort: function(a, b)
		{
			if (a.entry.isTask() !==  b.entry.isTask())
			{
				if (a.entry.isTask())
					return 1;
				if (b.entry.isTask())
					return -1;
			}

			if (a.part.daysCount == b.part.daysCount && a.part.daysCount == 1)
			{
				return a.entry.from.getTime() - b.entry.from.getTime();
			}
			else
			{
				if (a.part.daysCount == b.part.daysCount)
					return a.entry.from.getTime() - b.entry.from.getTime();
				else
					return a.part.daysCount - b.part.daysCount;
			}
		},

		clearLoadIndexCache: function()
		{
			this.pulledEntriesIndex = {};
			this.requestedEntriesIndex = {};
			this.entriesRaw = [];
			this.loadedEntriesIndex = {};
			this.externalEntryIndex = {};
		},

		checkMeetingByCodes: function(codes)
		{
			var code, n = 0;
			if (codes)
			{
				for (code in codes)
				{
					if (codes.hasOwnProperty(code))
					{
						if (codes[code] != 'users' || n > 0)
						{
							return true;
						}
						n++;
					}
				}
			}
			return false;
		},

		getUserIndex: function()
		{
			return this.userIndex;
		}
	};

	function Entry(calendar, data)
	{
		this.calendar = calendar;
		this.data = data;
		this.id = data.ID || 0;

		if (!this.data.DT_SKIP_TIME)
		{
			this.data.DT_SKIP_TIME = this.data.SKIP_TIME ? 'Y' : 'N';
		}

		this.fullDay = data.DT_SKIP_TIME === 'Y';
		this.parentId = data.PARENT_ID || 0;
		this.accessibility = data.ACCESSIBILITY;
		this.important = data.IMPORTANCE === 'high';
		this.private = !!data.PRIVATE_EVENT;
		this.sectionId = this.isTask() ? 'tasks' : parseInt(data.SECT_ID);
		this.name = this.isLocation()
			? this.calendar.roomsManager.getRoomName(data.SECT_ID) + ': ' + data.NAME
			: data.NAME
		;

		this.parts = [];

		var
			_this = this,
			util = this.calendar.util,
			startDayCode, endDayCode,
			color = data.COLOR || (this.isLocation()
				? this.calendar.roomsManager.getRoom(this.sectionId).color
				: this.calendar.sectionManager.getSection(this.sectionId).color)
			;

		Object.defineProperties(this, {
			startDayCode: {
				get: function(){return startDayCode;},
				set: function(value){startDayCode = util.getDayCode(value);}
			},
			endDayCode: {
				get: function(){return endDayCode;},
				set: function(value){endDayCode = util.getDayCode(value);}
			},
			color: {
				get: function(){return color;},
				set: function(value){color = value;}
			},
			location: {
				value: data.LOCATION,
				writable: true,
				enumerable : true
			}
		});

		this.prepareData();

		this.uid = this.calendar.entryController.getUniqueId(data, this);
	}

	Entry.prototype = {
		prepareData: function()
		{
			if (!this.data.DT_LENGTH)
			{
				this.data.DT_LENGTH = this.data.DURATION || 0;
			}
			if (this.fullDay && !this.data.DT_LENGTH)
			{
				this.data.DT_LENGTH = 86400;
			}


			if (this.isTask())
			{
				this.from = BX.parseDate(this.data.DATE_FROM) || new Date();
				this.to = BX.parseDate(this.data.DATE_TO) || this.from;
			}
			else
			{
				this.from = BX.parseDate(this.data.DATE_FROM) || new Date();
				if (this.fullDay)
				{
					this.from.setHours(0, 0, 0, 0);
				}

				if (this.data.DT_SKIP_TIME !== "Y")
				{
					this.from = new Date(this.from.getTime() - (parseInt(this.data['~USER_OFFSET_FROM']) || 0) * 1000);
				}

				if (this.fullDay)
				{
					// For all-day events we calculate finish date by subtracting one hour + one second
					// 3601 - one hour + one second. To avoid collisions on timezones with season time (mantis #97261)
					// TODO: find better way to calculate from/to dates
					this.to = new Date(this.from.getTime() + (this.data.DT_LENGTH - 3601) * 1000);
					this.to.setHours(0, 0, 0, 0);
				}
				else
				{
					this.to = new Date(this.from.getTime() + this.data.DT_LENGTH * 1000);
				}
			}

			if (!this.data.ATTENDEES_CODES && !this.isTask())
			{
				if (this.data.CAL_TYPE === 'user')
				{
					this.data.ATTENDEES_CODES = ['U' + this.data.OWNER_ID];
				}
				else
				{
					this.data.ATTENDEES_CODES = ['U' + this.data.CREATED_BY];
				}
			}

			this.startDayCode = this.from;
			this.endDayCode = this.to;
		},

		getAttendeesCodes: function()
		{
			return this.data.ATTENDEES_CODES;
		},

		getAttendees: function()
		{
			if (!this.attendeeList && BX.type.isArray(this.data['ATTENDEE_LIST']))
			{
				this.attendeeList = [];
				var userIndex = this.calendar.entryController.getUserIndex();

				this.data['ATTENDEE_LIST'].forEach(function(user)
				{
					if (userIndex[user.id])
					{
						var attendee = BX.clone(userIndex[user.id]);
						attendee.STATUS = user.status;
						attendee.ENTRY_ID = user.entryId;
						this.attendeeList.push(attendee);
					}
				}, this);
			}
			return this.attendeeList || [];
		},

		cleanParts: function()
		{
			this.parts = [];
		},

		startPart: function(part)
		{
			part.partIndex = this.parts.length;
			this.parts.push(part);
			return this.parts[part.partIndex];
		},

		registerPartNode: function(part, params)
		{
			part.params = params;
		},

		checkPartIsRegistered: function(part)
		{
			return BX.type.isPlainObject(part.params);
		},

		getPart: function(partIndex)
		{
			return this.parts[partIndex] || false;
		},

		getWrap: function(partIndex)
		{
			return this.parts[partIndex || 0].params.wrapNode;
		},

		getSectionName: function()
		{
			return this.calendar.sectionManager.getSection(this.sectionId).name || '';
		},

		getDescription: function()
		{
			return this.data.DESCRIPTION || '';
		},

		applyViewRange: function(viewRange)
		{
			var
				viewRangeStart = viewRange.start.getTime(),
				viewRangeEnd = viewRange.end.getTime(),
				fromTime = this.from.getTime(),
				toTime = this.to.getTime();

			if (toTime < viewRangeStart || fromTime > viewRangeEnd)
				return false;

			if (fromTime < viewRangeStart)
			{
				this.displayFrom = viewRange.start;
				this.startDayCode = this.displayFrom;
			}

			if (toTime > viewRangeEnd)
			{
				this.displayTo = viewRange.end;
				this.endDayCode = this.displayTo;
			}
			return true;
		},

		isPersonal: function()
		{
			return (this.data.CAL_TYPE === 'user' && this.data.OWNER_ID == this.calendar.util.userId);
		},

		isMeeting: function()
		{
			return !!this.data.IS_MEETING;
		},

		isResourcebooking: function()
		{
			return this.data.EVENT_TYPE === '#resourcebooking#';
		},

		isTask: function()
		{
			return this.data['~TYPE'] === 'tasks';
		},

		isLocation: function()
		{
			return this.data.CAL_TYPE === 'location';
		},

		isFullDay: function()
		{
			return this.fullDay;
		},

		isLongWithTime: function()
		{
			return !this.fullDay && this.calendar.util.getDayCode(this.from) != this.calendar.util.getDayCode(this.to);
		},

		isExpired: function()
		{
			return this.to.getTime() < new Date().getTime();
		},

		hasEmailAttendees: function()
		{
			if (this.emailAttendeesCache === undefined && BX.type.isArray(this.data['ATTENDEE_LIST']))
			{
				var userIndex = BX.Calendar.EntryManager.getUserIndex();
				var user;
				for (var i = 0; i < this.data['ATTENDEE_LIST'].length; i++)
				{
					user = this.data['ATTENDEE_LIST'][i];
					if ((user.status === 'Y' || user.status === 'Q')
						&& userIndex[user.id]
						&& userIndex[user.id].EMAIL_USER
					)
					{
						this.emailAttendeesCache = true;
						break;
					}
				}
			}
			return this.emailAttendeesCache;
		},

		ownerIsEmailUser: function()
		{
			if (this.ownerIsEmailUserCache === undefined)
			{
				var userIndex = BX.Calendar.EntryManager.getUserIndex();
				this.ownerIsEmailUserCache = userIndex[parseInt(this.data.MEETING_HOST)]
						&& userIndex[parseInt(this.data.MEETING_HOST)].EMAIL_USER;
			}
			return this.ownerIsEmailUserCache;
		},

		isSelected: function()
		{
			return !!this.selected;
		},

		isCrm: function()
		{
			return !!this.data.UF_CRM_CAL_EVENT;
		},

		isFirstReccurentEntry: function()
		{
			return (this.data.DATE_FROM_TS_UTC === Math.floor(BX.parseDate(this.data['~DATE_FROM']).getTime() / 1000) * 1000
				||
				BX.parseDate(this.data['DATE_FROM']).getTime() === BX.parseDate(this.data['~DATE_FROM']).getTime()
				) && !this.data.RECURRENCE_ID;
		},

		isRecursive: function()
		{
			return !!this.data.RRULE;
		},

		getMeetingHost: function()
		{
			return parseInt(this.data.MEETING_HOST);
		},

		getRrule: function()
		{
			return this.data.RRULE;
		},

		hasRecurrenceId: function()
		{
			return this.data.RECURRENCE_ID;
		},

		wasEverRecursive: function()
		{
			return this.data.RRULE || this.data.RECURRENCE_ID;
		},

		deselect: function()
		{
			this.selected = false;
		},

		select: function()
		{
			this.selected = true;
		},

		// deleteParts: function()
		// {
		// 	this.parts.forEach(function(part){
		// 		if (part.params)
		// 		{
		// 			if (part.params.wrapNode)
		// 			{
		// 				part.params.wrapNode.style.opacity = 0;
		// 			}
		// 		}
		// 	}, this);
		//
		// 	setTimeout(BX.delegate(function(){
		// 		this.parts.forEach(function(part){
		// 			if (part.params)
		// 			{
		// 				if (part.params.wrapNode)
		// 				{
		// 					BX.remove(part.params.wrapNode);
		// 				}
		// 			}
		// 		}, this);
		// 	}, this), 300);
		// },

		getUniqueId: function()
		{
			var sid = this.data.PARENT_ID || this.data.PARENT_ID;
			if (this.isRecursive())
				sid += '|' + this.data.DT_FROM_TS;

			if (this.data['~TYPE'] == 'tasks')
				sid += '|' + 'task';

			return sid;
		},

		getCurrentStatus: function()
		{
			var i, user, status = false;
			if (this.isMeeting())
			{
				if (this.calendar.util.userId == this.data.CREATED_BY
					||
					this.calendar.util.userId == this.data.MEETING_HOST
				)
				{
					status = this.data.MEETING_STATUS;
				}
				else if (this.calendar.util.userId == this.data.MEETING_HOST)
				{
					status = this.data.MEETING_STATUS;
				}
				else if (BX.type.isArray(this.data['ATTENDEE_LIST']))
				{
					for (i = 0; i < this.data['ATTENDEE_LIST'].length; i++)
					{
						user = this.data['ATTENDEE_LIST'][i];
						if (this.data['ATTENDEE_LIST'][i].id == this.calendar.util.userId)
						{
							status = this.data['ATTENDEE_LIST'][i].status;
							break;
						}
					}
				}
			}
			return status;
		},

		getReminders: function()
		{
			var res = [];
			if (this.data && this.data.REMIND)
			{
				this.data.REMIND.forEach(function (remind)
				{
					if (remind.type == 'min')
					{
						res.push(remind.count);
					}
					else if (remind.type == 'hour')
					{
						res.push(parseInt(remind.count) * 60);
					}
					if (remind.type == 'day')
					{
						res.push(parseInt(remind.count) * 60 * 24);
					}
				});
			}
			return res;
		},

		getLengthInDays: function()
		{
			var
				from = new Date(this.from.getFullYear(), this.from.getMonth(), this.from.getDate(), 0, 0, 0),
				to = new Date(this.to.getFullYear(), this.to.getMonth(), this.to.getDate(), 0, 0, 0);

			return Math.round((to.getTime() - from.getTime()) / this.calendar.util.dayLength) + 1;
		},

		getColor: function()
		{
			return this.color;
		}
	};

	if (window.BXEventCalendar)
	{
		window.BXEventCalendar.Entry = Entry;
		window.BXEventCalendar.EntryController = EntryController;
	}
	else
	{
		BX.addCustomEvent(window, "onBXEventCalendarInit", function()
		{
			window.BXEventCalendar.Entry = Entry;
			window.BXEventCalendar.EntryController = EntryController;
		});
	}
})(window);