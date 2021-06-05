import {Loc, Type} from 'main.core';
import {VuexBuilderModel} from 'ui.vue.vuex';
import {EntityType, EntityGroup} from 'timeman.const';
import {Code} from './utils/code';
import {Time} from './utils/time';
import {Logger} from '../lib/logger';
import {Debug} from '../lib/debug';

export class MonitorModel extends VuexBuilderModel
{
	getName()
	{
		return 'monitor';
	}

	getSaveTimeout()
	{
		return 1000;
	}

	getLoadTimeout()
	{
		return false;
	}

	getState()
	{
		return {
			config: {
				secret: Code.createSecret(),
				desktopCode: Code.getDesktopCode(),
				otherTime: this.getVariable('config.otherTime', 1800000),
				shortAbsenceTime: this.getVariable('config.shortAbsenceTime', 1800000),
			},
			reportState: {
				dateLog: this.getDateLog(),
			},
			personal: [],
			strictlyWorking: [],
			entity: [],
			history: [],
			sentQueue: [],
		};
	}

	getEntityState()
	{
		return {
			type: EntityType.unknown,
			title: '',
			publicCode: '',
			privateCode: '',
			comment: '',
			extra: {},
		};
	}

	getHistoryState()
	{
		return {
			dateLog: this.getDateLog(),
			privateCode: '',
			time: [{
				start: new Date(),
				preFinish: null,
				finish: null
			}]
		};
	}

	getSentQueueState()
	{
		return {
			dateLog: this.getDateLog(),
			historyPackage: [],
			chartPackage: [],
			desktopCode: '',
		};
	}

	getActions()
	{
		return {
			setDateLog(store, payload)
			{
				if (Type.isString(payload))
				{
					const date = new Date(payload);

					if (
						Type.isDate(date)
						&& !isNaN(date)
						&& payload.length === 10
					)
					{
						store.commit('setDateLog', payload);
					}
				}
			},

			addPersonal: (store, privateCode) =>
			{
				store.commit('addPersonal', this.validatePersonal(privateCode));
			},

			removePersonal: (store, privateCode) =>
			{
				store.commit('removePersonal', this.validatePersonal(privateCode));
			},

			addToStrictlyWorking: (store, privateCode) =>
			{
				store.commit('addToStrictlyWorking', privateCode);
			},

			removeFromStrictlyWorking: (store, privateCode) =>
			{
				store.commit('removeFromStrictlyWorking', privateCode);
			},

			clearStrictlyWorking: (store) =>
			{
				store.commit('clearStrictlyWorking');
			},

			clearPersonal: (store) =>
			{
				store.commit('clearPersonal');
			},

			addEntity: (store, payload) =>
			{
				let result = this.validateEntity({...payload});

				if (
					result.type !== EntityType.absence
					&& result.type !== EntityType.custom
				)
				{
					result.publicCode = Code.createPublic(result.title);
					result.privateCode = Code.createPrivate(result.title, store.state.config.secret);
				}
				else
				{
					const date = new Date();
					const timestamp = +date;

					result.publicCode = Code.createPublic(result.title, timestamp);
					result.privateCode = Code.createPrivate(result.title, timestamp, store.state.config.secret);

					if (result.type === EntityType.absence)
					{
						result.extra = {
							timeStart: date,
						};
						result.title +=
							' '
							+ Loc.getMessage('TIMEMAN_PWT_REPORT_ABSENCE_FROM_TIME')
							+ ' '
							+ Time.formatDateToTime(result.extra.timeStart)
						;
					}
				}

				store.commit('addEntity', {
					...this.getEntityState(),
					...result,
				});

				if (!store.state.strictlyWorking.find(privateCode => privateCode === result.privateCode))
				{
					const isBitrix24Cp = (
						result.type === EntityType.site
						&& result.title === location.host
					);

					const isBitrix24Desktop = (
						result.type === EntityType.app
						&& payload.isBitrix24Desktop
					);

					if (
						isBitrix24Cp
						|| isBitrix24Desktop
						|| result.type === EntityType.custom
					)
					{
						store.commit('addToStrictlyWorking', result.privateCode);
					}
				}

				return result;
			},

			removeEntityByPrivateCode: (store, payload) =>
			{
				store.commit('removeEntityByPrivateCode', payload);
			},

			clearEntities: (store) =>
			{
				store.commit('clearEntities');
			},

			addHistory: (store, payload) =>
			{
				store.commit('finishLastInterval');

				const result = this.validateHistory({...payload});

				let entity;
				let privateCode;
				let historyEntry = null;

				if (
					result.type === EntityType.app
					|| result.type === EntityType.site
					|| result.type === EntityType.unknown
					|| result.type === EntityType.incognito
				)
				{
					entity = this.getEntityByTitle(store, result.title);
					privateCode = (entity ? entity.privateCode : null);

					if (!privateCode)
					{
						privateCode = this.getActions().addEntity(store, payload).privateCode;
					}

					historyEntry = (entity && entity.type === EntityType.site)
						? this.getHistoryEntryBySiteUrl(store, result.siteUrl)
						: this.getHistoryEntryByPrivateCode(store, privateCode);
				}
				else if (
					result.type === EntityType.absence
					|| result.type === EntityType.custom
				)
				{
					entity = this.getActions().addEntity(store, payload);
					privateCode = entity.privateCode;
				}

				if (!historyEntry)
				{
					delete result.title;

					store.commit('addHistory', {
						...this.getHistoryState(),
						...result,
						privateCode,
					});

					return;
				}

				if (result.type !== EntityType.custom)
				{
					store.commit('startIntervalForHistoryEntry', historyEntry);
				}
			},

			preFinishLastInterval: (store) =>
			{
				store.commit('preFinishLastInterval');
			},

			finishLastInterval: (store) =>
			{
				store.commit('finishLastInterval');
			},

			clearHistory: (store) =>
			{
				store.commit('clearHistory');
			},

			createSentQueue: (store) =>
			{
				if (store.state.history.length === 0)
				{
					return;
				}

				let sentQueue = this.collectSentQueue(store);

				let result = this.validateSentQueue({
					dateLog: store.state.reportState.dateLog,
					historyPackage: sentQueue.history,
					chartPackage: sentQueue.chart,
					desktopCode: store.state.config.desktopCode,
				});

				store.commit('createSentQueue', {
					...this.getSentQueueState(),
					...result
				});
			},

			clearSentQueue: (store) =>
			{
				store.commit('clearSentQueue');
			},

			clearStorage: (store) =>
			{
				store.commit('clearStorage');
			},

			setComment: (store, payload) =>
			{
				const entity = store.state.entity.find(entity => entity.privateCode === payload.privateCode);

				if (entity && (Type.isString(payload.comment) || Type.isNumber(payload.comment)))
				{
					store.commit('setComment', {
						entity,
						comment: payload.comment.toString()
					})
				}
			},

			processUnfinishedEvents: (store) =>
			{
				store.commit('processUnfinishedEvents');
			}
		}
	}

	getMutations()
	{
		return {
			setDateLog: (state, payload) =>
			{
				state.reportState.dateLog = payload;

				super.saveState(state);
			},

			addPersonal: (state, payload) =>
			{
				state.personal.push(payload);

				super.saveState(state);
			},

			removePersonal: (state, payload) =>
			{
				state.personal = state.personal.filter(privateCode => privateCode !== payload);

				super.saveState(state);
			},

			addToStrictlyWorking: (state, payload) =>
			{
				state.strictlyWorking.push(payload);

				super.saveState(state);
			},

			removeFromStrictlyWorking: (state, payload) =>
			{
				state.strictlyWorking = state.strictlyWorking.filter(publicCode => publicCode !== payload)

				super.saveState(state);
			},

			clearStrictlyWorking: (state) =>
			{
				state.strictlyWorking = [];

				super.saveState(state);
			},

			clearPersonal: (state) =>
			{
				state.personal = [];

				super.saveState(state);
			},

			addEntity: (state, payload) =>
			{
				state.entity.push(payload);

				super.saveState(state);
			},

			removeEntityByPrivateCode: (state, payload) =>
			{
				state.entity = state.entity.filter(entity => entity.privateCode !== payload);
				state.history = state.history.filter(entry => entry.privateCode !== payload);
				state.strictlyWorking = state.strictlyWorking.filter(privateCode => privateCode !== payload);
				state.personal = state.personal.filter(privateCode => privateCode !== payload);

				super.saveState(state);
			},

			clearEntities: (state) =>
			{
				state.entity = [];

				super.saveState(state);
			},

			addHistory: (state, payload) =>
			{
				state.history.push(payload);

				super.saveState(state);
			},

			startIntervalForHistoryEntry: (state, historyEntry) =>
			{
				historyEntry.time.push({
					start: new Date(),
					finish: null
				});

				super.saveState(state);
			},

			finishLastInterval: (state) =>
			{
				state.history.map(entry => {
					entry.time = entry.time.map(time => {
						if (time.finish === null)
						{
							time.finish = new Date();
							time.preFinish = null;

							if (entry.type !== EntityType.absence)
							{
								return time;
							}

							let shortAbsenceTimeRest = Time.msToSec(state.config.shortAbsenceTime);

							state.entity
								.filter(entity => {
									if (!state.personal.includes(entity.privateCode)) {
										return entity;
									}
								})
								.map(entity => {
									return {
										...entity,
										time: Time.calculateInEntity(state, entity),
									}
								})
								.sort((currentEntity, nextEntity) => currentEntity.time - nextEntity.time)
								.forEach(entity => {
									if (
										state.strictlyWorking.includes(entity.privateCode)
										|| entity.type !== EntityType.absence
									) {
										return;
									}

									if (entity.comment.trim() === '') {
										if (shortAbsenceTimeRest - entity.time >= 0) {
											shortAbsenceTimeRest -= entity.time;
											return;
										}

										state.personal.push(entity.privateCode);
									}
								});
						}

						return time;
					})

					return entry;
				});

				super.saveState(state);
			},

			preFinishLastInterval: (state) =>
			{
				state.history = state.history.map(entry => {
					entry.time = entry.time.map(time => {
						if (time.finish === null)
						{
							time.preFinish = new Date();
							Logger.log('Last interval for ', entry.privateCode, ' preFinished');
						}

						return time;
					})

					return entry;
				});

				super.saveState(state);
			},

			clearHistory: (state) =>
			{
				state.history = [];

				super.saveState(state);
			},

			createSentQueue: (state, payload) =>
			{
				state.sentQueue.push(payload);

				super.saveState(state);
			},

			clearSentQueue: (state) =>
			{
				state.sentQueue = [];

				super.saveState(state);
			},

			clearStorage: (state) =>
			{
				const getCodesToStore = privateCode => {

					const entity = state.entity.find(entity => entity.privateCode === privateCode);

					if (Type.isObject(entity) && entity.hasOwnProperty('type'))
					{
						if (entity.type !== EntityType.absence)
						{
							return true;
						}
					}

					return false;
				}

				state.personal = state.personal.filter(getCodesToStore);
				state.strictlyWorking = state.strictlyWorking.filter(getCodesToStore);

				state.entity = [];
				state.history = [];
				state.sentQueue = [];

				Logger.log('Local storage cleared');
				Debug.space();
				Debug.log('Local storage cleared');

				super.saveState(state);
			},

			setComment: (state, payload) =>
			{
				payload.entity.comment = payload.comment;

				super.saveState(state);
			},

			processUnfinishedEvents: (state) =>
			{
				state.history.map(entry => {
					entry.time = entry.time.map(interval => {
						if (
							interval.finish === null
							&& interval.preFinish !== null
						)
						{
							interval.finish = interval.preFinish;
							interval.preFinish = null;

							Logger.log('Unfinished interval closed based on preFinish time');
							Debug.space();
							Debug.log('Unfinished interval closed based on preFinish time');
						}

						return interval;
					});

					entry.time = entry.time.filter(time => {
						if (time.finish != null)
						{
							return true;
						}
						else
						{
							Logger.log('Unfinished interval has been removed');
							Debug.space();
							Debug.log('Unfinished interval has been removed');

							return false;
						}
					});

					return entry;
				});

				super.saveState(state);
			}
		}
	}

	getGetters()
	{
		return {
			getWorkingEntities(state)
			{
				let workingEntities = state.entity.filter(entity => {
					if (!state.personal.includes(entity.privateCode))
					{
						return entity;
					}
				});

				workingEntities = workingEntities.map(entity => {
					const workingEntity = {
						...entity,
						time: Time.calculateInEntity(state, entity),
					}

					if (workingEntity.type === EntityType.unknown)
					{
						workingEntity.hint = EntityGroup.unknown.hint;
					}

					return workingEntity;
				});

				let otherTimeRest = Time.msToSec(state.config.otherTime);
				const others = workingEntities
					.sort((currentEntity, nextEntity) => currentEntity.time - nextEntity.time)
					.filter(entity => {
						if (
							state.strictlyWorking.includes(entity.privateCode)
							|| entity.type === EntityType.absence
						)
						{
							return false;
						}

						if (otherTimeRest - entity.time >= 0)
						{
							otherTimeRest -= entity.time;

							return true;
						}
						else
						{
							return false;
						}
					});

				let shortAbsenceTimeRest = Time.msToSec(state.config.shortAbsenceTime);

				const shortAbsence = workingEntities
					.sort((currentEntity, nextEntity) => currentEntity.time - nextEntity.time)
					.filter(entity => {
						if (
							state.strictlyWorking.includes(entity.privateCode)
							|| entity.type !== EntityType.absence
						)
						{
							return false;
						}

						if (entity.comment.trim() === '')
						{
							if (shortAbsenceTimeRest - entity.time >= 0)
							{
								shortAbsenceTimeRest -= entity.time;

								return true;
							}

							return false;
						}
					});

				const otherCodes = others.map(entity => entity.privateCode);
				const shortAbsenceCodes = shortAbsence.map(entity => entity.privateCode);
				const excludeCodes = otherCodes.concat(shortAbsenceCodes);

				workingEntities = workingEntities
					.filter(entity => !excludeCodes.includes(entity.privateCode))
					.sort((currentEntity, nextEntity) => nextEntity.time - currentEntity.time);

				if (Type.isArrayFilled(others))
				{
					workingEntities.push({
						type: EntityType.group,
						title: EntityGroup.other.title,
						time: Time.msToSec(state.config.otherTime) - otherTimeRest,
						allowedTime: Time.msToSec(state.config.otherTime),
						hint: EntityGroup.other.hint,
					});
				}

				if (Type.isArrayFilled(shortAbsence))
				{
					workingEntities.push({
						type: EntityType.group,
						title: EntityGroup.absence.title,
						time: shortAbsence.reduce((sum, entity) => sum + entity.time, 0),
						allowedTime: Time.msToSec(state.config.shortAbsenceTime),
						hint: EntityGroup.absence.hint,
					});
				}

				return workingEntities;
			},
			getPersonalEntities(state)
			{
				let personalEntities = state.entity.filter(entity => {
					if (state.personal.includes(entity.privateCode))
					{
						return entity;
					}
				});

				return personalEntities
					.map(entity => {
						return {
							...entity,
							time: Time.calculateInEntity(state, entity),
						}
					})
					.sort((a, b) => b.time - a.time);
			},
			getSiteDetailByPrivateCode: state => privateCode => {
				const history = BX.util.objectClone(state.history);

				let entries = history.filter(entry => entry.privateCode === privateCode);

				entries.map(entry => {
					entry.time = Time.calculateInEntry(entry);
				});

				return entries;
			},
			getChartData(state)
			{
				let segments = [];

				const reportDate = new Date(state.reportState.dateLog);
				const emptyChart = [{
					start: new Date(
						reportDate.getFullYear(),
						reportDate.getMonth(),
						reportDate.getDate(),
						0,
						0
					),
					finish: new Date(
						reportDate.getFullYear(),
						reportDate.getMonth(),
						reportDate.getDate(),
						23,
						59
					),
					type: EntityGroup.inactive.value,
					clickable: true,
					clickableHint: Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_CLICKABLE_HINT'),
					stretchable: true,
				}];

				if (!Type.isArrayFilled(state.history))
				{
					return emptyChart;
				}

				const history = BX.util.objectClone(state.history);
				const minute = 60000;

				//collecting real intervals
				history.forEach(entry => {
					const type = state.personal.includes(entry.privateCode)
						? EntityGroup.personal.value
						: EntityGroup.working.value
					;

					entry.time.forEach(interval => {
						const start = new Date(interval.start);
						const finish = interval.finish ? new Date(interval.finish) : new Date();

						segments.push({ type, start, finish });
					})
				});

				if (!Type.isArrayFilled(segments))
				{
					return emptyChart;
				}

				segments = segments
					.sort((currentSegment, nextSegment) => currentSegment.start - nextSegment.start)
				;

				//fill the voids with inactive intervals

				//create the leftmost interval
				let firstSegmentFrom = segments[0].start;
				if (firstSegmentFrom.getHours() + firstSegmentFrom.getMinutes() > 0)
				{
					segments.unshift({
						start: new Date(
							firstSegmentFrom.getFullYear(),
							firstSegmentFrom.getMonth(),
							firstSegmentFrom.getDate(),
							0,
							0
						),
						finish: firstSegmentFrom,
						type: EntityGroup.inactive.value,
					});
				}

				//create inactive intervals throughout the day
				segments
					.forEach((interval, index) => {
						if (
							index > 0
							&& interval.start - segments[index - 1].finish >= minute * 3
						)
						{
							const start = segments[index - 1].finish;
							const finish = interval.start;

							start.setMinutes(start.getMinutes() + 1);
							finish.setMinutes(finish.getMinutes() - 1);

							segments.push({
								start,
								finish,
								type: EntityGroup.inactive.value,
							});
						}
					})
				;

				segments = segments
					.sort((currentSegment, nextSegment) => currentSegment.start - nextSegment.start)
				;

				//create the rightmost interval
				let lastSegmentTo = segments[segments.length - 1].finish;
				if (lastSegmentTo.getHours() + lastSegmentTo.getMinutes() < 82)
				{
					lastSegmentTo.setMinutes(lastSegmentTo.getMinutes() + 1);

					segments.push({
						start: lastSegmentTo,
						finish: new Date(
							lastSegmentTo.getFullYear(),
							lastSegmentTo.getMonth(),
							lastSegmentTo.getDate(),
							23,
							59
						),
						type: EntityGroup.inactive.value,
					});
				}

				//collapse intervals shorter than a minute
				segments = segments.filter(interval => interval.finish - interval.start >= minute);

				let chartData = [];
				let lastSegmentType = null;

				//create data for the graph from intervals
				segments
					.forEach((segment, index) => {
						if (index > 0 && segment.type !== EntityGroup.inactive.value)
						{
							chartData[chartData.length - 1].finish = segment.start;
						}

						if (segment.type !== lastSegmentType)
						{
							lastSegmentType = segment.type;

							chartData.push({
								start: segment.start,
								finish: segment.finish,
								type: segment.type,
								clickable: (
										segment.type === EntityGroup.inactive.value
										&& segment.start < new Date()
								),
								clickableHint: segment.type === EntityGroup.inactive.value
									? Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_CLICKABLE_HINT')
									: '',
							});
						}
						else if (segment.type !== EntityGroup.inactive.value)
						{
							chartData[chartData.length - 1].finish = segment.finish;
						}
					})
				;

				return chartData;
			}
		}
	}

	validatePersonal(personal = '')
	{
		let result = '';

		if (personal && (Type.isString(personal) || Type.isNumber(personal)))
		{
			result = personal.toString();
		}

		return result;
	}

	validateEntity(entity = {})
	{
		let result = {};

		if (Type.isObject(entity) && entity)
		{
			if (Type.isString(entity.type))
			{
				result.type = entity.type;
			}

			if (Type.isString(entity.title) || Type.isNumber(entity.title))
			{
				result.title = entity.title.toString();
			}

			if (Type.isString(entity.comment) || Type.isNumber(entity.comment))
			{
				result.comment = entity.comment.toString();
			}
		}

		return result;
	}

	validateHistory(historyEntry= {})
	{
		let result = {};

		if (Type.isObject(historyEntry) && historyEntry)
		{
			if (Type.isString(historyEntry.title) || Type.isNumber(historyEntry.title))
			{
				result.title = historyEntry.title.toString();
			}

			if (Type.isString(historyEntry.type) && EntityType.hasOwnProperty(historyEntry.type))
			{
				result.type = historyEntry.type;

				if (historyEntry.type === EntityType.site)
				{
					result.siteUrl = historyEntry.siteUrl;
					result.siteTitle = historyEntry.siteTitle.toString();
				}
			}

			if (Type.isArrayFilled(historyEntry.time))
			{
				result.time = historyEntry.time;
			}
		}

		return result;
	}

	validateSentQueue(sentQueueItem = {})
	{
		let result = {};

		if (Type.isObject(sentQueueItem) && sentQueueItem)
		{
			if (Type.isString(sentQueueItem.dateLog))
			{
				result.dateLog = sentQueueItem.dateLog;
			}

			if (Type.isArrayFilled(sentQueueItem.historyPackage))
			{
				result.historyPackage = sentQueueItem.historyPackage;
			}

			if (Type.isArrayFilled(sentQueueItem.chartPackage))
			{
				result.chartPackage = sentQueueItem.chartPackage;
			}

			if (Type.isString(sentQueueItem.desktopCode))
			{
				result.desktopCode = sentQueueItem.desktopCode;
			}
		}

		return result;
	}

	getHistoryEntryByPrivateCode(store, privateCode)
	{
		return store.state.history.find(entry => entry.privateCode === privateCode);
	}

	getHistoryEntryBySiteUrl(store, siteUrl)
	{
		return store.state.history.find(entry => entry.siteUrl === siteUrl);
	}

	getEntityByPrivateCode(store, privateCode)
	{
		return store.state.entity.find(entity => entity.privateCode === privateCode);
	}

	getEntityByTitle(store, title)
	{
		return store.state.entity.find(entity => entity.title === title);
	}

	getDateLog()
	{
		const date = new Date();
		const addZero = num => (num >= 0 && num <= 9) ? '0' + num : num;

		const year = date.getFullYear();
		const month = addZero(date.getMonth() + 1);
		const day = addZero(date.getDate());

		return year + '-' + month + '-' + day;
	}

	collectSentQueue(store)
	{
		let history = BX.util.objectClone(this.getGetters().getWorkingEntities(store.state));

		history = history.map(entry => {
			if (
				entry.type === EntityType.group
				&& entry.title === EntityGroup.other.title
			)
			{
				entry.type = EntityType.other;
				entry.publicCode = Code.createPublic(EntityType.other);
				entry.privateCode = Code.createPrivate(EntityType.other);

				delete entry.items;
			}
			else if (
				entry.type === EntityType.group
				&& entry.title === EntityGroup.absence.title
			)
			{
				entry.type = EntityType.absenceShort;
				entry.publicCode = Code.createPublic(EntityType.absenceShort);
				entry.privateCode = Code.createPrivate(EntityType.absenceShort);

				delete entry.items;
			}
			else if (entry.type === EntityType.absence)
			{
				if (entry.extra.hasOwnProperty('timeStart'))
				{
					entry.timeStart = entry.extra.timeStart;
				}

				entry.title = Loc.getMessage('TIMEMAN_PWT_REPORT_ABSENCE');
				entry.publicCode = Code.createPublic(EntityType.absence);
				entry.privateCode = Code.createPrivate(EntityType.absence);
			}

			delete entry.extra;

			return entry;
		});

		Logger.log('History to send:', history);
		Debug.space();
		Debug.log('History to send:', history);

		let chart = this.getGetters().getChartData(store.state).map(interval => {
			return {
				type: interval.type,
				start: interval.start,
				finish: interval.finish,
			}
		});

		Logger.log('ChartData to send:', chart);

		return {
			history,
			chart,
		};
	}
}