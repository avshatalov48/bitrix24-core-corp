/**
 * @module app-rating-manager/src/manager
 */
jn.define('app-rating-manager/src/manager', (require, exports, module) => {
	const { AppRating, MinRateForStore, AppRatingAnalytics } = require('layout/ui/app-rating');
	const { Feature } = require('feature');
	const { TouristEventStorageProvider } = require('app-rating-manager/src/tourist-storage-provider');
	const { fetchAppRatingEnabledOptionIfNeeded, isAppRatingEnabled } = require('app-rating-manager/src/api');
	const { Type } = require('type');

	const RateEvent = {
		ANY_EVENT_COUNTER_REACHED_LIMIT: 'anyEventCounterReachedLimit',
		RATE_BOX_OPENED: 'rateBoxOpened',
		APP_RATED: 'appRated',
		USER_WENT_TO_STORE: 'userWentToStore',
	};

	const RateBoxDisplayIntervals = {
		SECOND_DISPLAY_NOT_RATED: 30,
		THIRD_DISPLAY_NOT_RATED: 60,
		SUBSEQUENT_DISPLAYS_NOT_RATED: 365,
		DISPLAY_FOR_RATED: 180,
	};

	const millisecondsInDay = 86_400_000; // 1000 * 60 * 60 * 24;
	const sharedStorageKey = 'app-rating-manager';
	const storedCountersDataKey = `appRatingCounters_user_${env.userId}`;
	const storedCounterLimitsDataKey = `appRatingCounterLimits_user_${env.userId}`;
	const sessionChangedTimeKey = `sessiomChanged_user_${env.userId}`;
	const eventLastTriggerTimeKey = `eventLastTriggerTime_${env.userId}`;

	class AppRatingManager
	{
		constructor({
			eventStorageProvider = new TouristEventStorageProvider(),
			isAppRatingEnabledFunction = isAppRatingEnabled,
		} = {})
		{
			this.eventStorageProvider = eventStorageProvider;
			this.onInitialized = this.#init();
			this.isAppRatingEnabled = isAppRatingEnabledFunction;
		}

		#init = async () => {
			await Promise.allSettled([
				this.eventStorageProvider.init(),
				fetchAppRatingEnabledOptionIfNeeded(),
			]);
		};

		#onSessionChanged = () => {
			this.#saveSessionChangedTime();
		};

		/**
		 * @param {Date} time
		 * @returns {void}
		 */
		#saveSessionChangedTime = (time = new Date()) => {
			Application.sharedStorage(sharedStorageKey).set(sessionChangedTimeKey, time.toISOString());
		};

		/**
		 * @returns {Date|null}
		 */
		#getLastSessionChangedTime = () => {
			const time = Application.sharedStorage(sharedStorageKey).get(sessionChangedTimeKey);

			return time ? new Date(time) : null;
		};

		/**
		 * Subscription to system events
		 * @public
		 */
		subscribe = () => {
			BX.addCustomEvent('onAppPaused', this.#onSessionChanged);
			BX.addCustomEvent('onTabsSelected', this.#onSessionChanged);
		};

		/**
		 * Opens the app rating dialog. Use only when you want to force the dialog
		 * to be shown without any conditions. In other cases, use tryOpenAppRating.
		 * Think twice if you need to use this method.
		 * @public
		 * @param {AppRatingProps} [props = {}]
		 * @param {boolean} [props.openInComponent = false]
		 */
		openAppRating(props = {})
		{
			const openInComponent = props.openInComponent ?? false;
			const triggerEvent = this.getLastTriggerEvent();

			this.#ready()
				.then(async () => {
					if (openInComponent)
					{
						PageManager.openComponent('JSStackComponent', {
							name: 'JSStackComponent',
							componentCode: 'apprating.box',
							// eslint-disable-next-line no-undef
							scriptPath: availableComponents['apprating.box'].publicUrl,
							canOpenInDefault: true,
							rootWidget: {
								name: 'layout',
								settings: {
									objectName: 'layout',
									modal: true,
									enableNavigationBarBorder: false,
									backdrop: {
										mediumPositionHeight: 450,
										hideNavigationBar: true,
										swipeAllowed: true,
										swipeContentAllowed: true,
										adoptHeightByKeyboard: true,
										horizontalSwipeAllowed: false,
									},
								},
							},
							params: {
								triggerEvent,
							},
						});
						await this.#rememberRateBoxOpened();

						return;
					}

					await AppRating.open({
						...props,
						triggerEvent,
						onGoToStoreButtonClick: async () => {
							await this.#rememberUserWentToStore();
							props.onGoToStoreButtonClick?.();
						},
						onRateAppButtonClick: async (value) => {
							await this.#rememberAppRated(value);
							props.onRateAppButtonClick?.(value);
						},
					});
					await this.#rememberRateBoxOpened();
				})
				.catch(console.error);
		}

		/**
		 * @public
		 * Attempts to open the app rating dialog based on certain conditions.
		 * @param {AppRatingProps} [props]
		 * @param {boolean} [props.openInComponent = false]
		 * @param {string|null} [props.event = null]
		 * @param {boolean} [props.shouldCheckSessionChangedTime = true]
		 */
		tryOpenAppRating(props = {})
		{
			const { event = null, shouldCheckSessionChangedTime = true } = props;
			this.#ready()
				.then(() => {
					if (!this.isAppRatingEnabled())
					{
						return;
					}

					if (this.canOpenAppRating(event, shouldCheckSessionChangedTime))
					{
						this.openAppRating(props);
					}
				})
				.catch(console.error);
		}

		/**
		 * @public
		 * Use only for 'apprating.box' component.
		 * @param {AppRatingProps} props
		 * @returns {LayoutComponent}
		 */
		renderAppRatingContent(props)
		{
			const triggerEvent = props?.triggerEvent ?? null;

			AppRatingAnalytics.sendDrawerOpen({
				section: triggerEvent,
			});

			return new AppRating({
				...props,
				onGoToStoreButtonClick: async () => {
					await this.#rememberUserWentToStore();
					props.onGoToStoreButtonClick?.();
				},
				onRateAppButtonClick: async (value) => {
					await this.#rememberAppRated(value);
					props.onRateAppButtonClick?.(value);
				},
			});
		}

		/**
		 * @public
		 * @param {string} event
		 * @param {number} limit
		 */
		registerLimit(event, limit)
		{
			const limitsFromStorage = this.getLimits();
			if (!limitsFromStorage[event] || limitsFromStorage[event] !== limit)
			{
				Application.sharedStorage(sharedStorageKey).set(storedCounterLimitsDataKey, JSON.stringify({
					...limitsFromStorage,
					[event]: limit,
				}));
			}
		}

		/**
		 * @public
		 * @param {object} limits
		 */
		registerLimits(limits)
		{
			const limitsFromStorage = this.getLimits();
			Application.sharedStorage(sharedStorageKey).set(storedCounterLimitsDataKey, JSON.stringify({
				...limitsFromStorage,
				...limits,
			}));
		}

		/**
		 * Use only for internal and testing purposes.
		 * @public
		 * @param event
		 * @returns {*|null}
		 */
		getLimit(event)
		{
			const limitsFromStorage = this.getLimits();

			return Type.isNil(limitsFromStorage[event]) ? null : limitsFromStorage[event];
		}

		/**
		 * Use only for internal and testing purposes.
		 * @public
		 * @returns {object}
		 */
		getLimits()
		{
			return JSON.parse(
				Application.sharedStorage(sharedStorageKey).get(storedCounterLimitsDataKey) ?? '{}',
			);
		}

		/**
		 * @public
		 * @param {string} event
		 * @returns {Promise<void>}
		 */
		async increaseCounter(event)
		{
			await this.#ready();

			if (!this.isAppRatingEnabled())
			{
				return;
			}
			this.#saveEventLastTriggerTime(event);

			const counters = this.getCounters();
			const limits = this.getLimits();

			if (
				Number.isNaN(counters[event])
				|| this.#isLimitReached(event, counters, limits))
			{
				return;
			}

			if (Type.isNil(counters[event]))
			{
				counters[event] = 0;
			}
			counters[event]++;
			this.saveCounters(counters);

			if (!this.#hasAnyLimitReachedInPast() && this.#hasAnyLimitReached(counters, limits))
			{
				await this.eventStorageProvider.save(RateEvent.ANY_EVENT_COUNTER_REACHED_LIMIT);
			}
		}

		getLastTriggerEvent()
		{
			const allTriggeredEvents = this.#getAllEventsLastTriggerTime();

			return Object.keys(allTriggeredEvents).reduce((acc, event) => {
				if (acc === null)
				{
					return event;
				}

				if (allTriggeredEvents[event] > allTriggeredEvents[acc])
				{
					return event;
				}

				return acc;
			}, null);
		}

		#isEventWasTriggeredAfterSessionChangedTime(event)
		{
			const sessionChangedTime = this.#getLastSessionChangedTime();
			if (sessionChangedTime === null)
			{
				return true;
			}

			const eventLastTriggerTime = this.#getEventLastTriggerTime(event);
			if (eventLastTriggerTime === null)
			{
				return false;
			}

			return eventLastTriggerTime > sessionChangedTime;
		}

		/**
		 * Should use only for internal tasks and testing purposes.
		 * @public
		 * @param {string|null} [event = null]
		 * @param {boolean} [shouldCheckSessionChangedTime = true]
		 * @returns {boolean}
		 */
		canOpenAppRating(event = null, shouldCheckSessionChangedTime = true)
		{
			if (!Feature.isNativeStoreSupported())
			{
				return false;
			}

			if (!this.#hasAnyLimitReachedInPast())
			{
				return false;
			}

			if (event && !this.#isLimitReached(event))
			{
				return false;
			}

			if (event && shouldCheckSessionChangedTime && !this.#isEventWasTriggeredAfterSessionChangedTime(event))
			{
				return false;
			}

			if (this.#isRateBoxNeverShown())
			{
				return true;
			}

			if (this.#isRateBoxShownOnce() && this.#isTimeForSecondDisplay())
			{
				return true;
			}

			if (this.#isRateBoxShownTwice() && this.#isTimeForThirdDisplay())
			{
				return true;
			}

			if (this.#isRateBoxShownMoreThanTwice() && this.#isTimeForSubsequentDisplays())
			{
				return true;
			}

			if (this.#isAppRated() && !this.#isAppTopRated() && this.#isTimeForRatedDisplay())
			{
				return true;
			}

			return this.#isAppTopRated() && !this.#isUserWentToStore() && this.#isTimeForRatedDisplay();
		}

		async #ready()
		{
			return this.onInitialized;
		}

		#getRateBoxLastDisplayTime()
		{
			return this.eventStorageProvider.getLastTime(RateEvent.RATE_BOX_OPENED) ?? null;
		}

		#getRateBoxDisplayCount()
		{
			return this.eventStorageProvider.getNumberOfTimes(RateEvent.RATE_BOX_OPENED);
		}

		#getRateLastTime()
		{
			return this.eventStorageProvider.getLastTime(RateEvent.APP_RATED) ?? null;
		}

		#getUserWentToStoreCount()
		{
			return this.eventStorageProvider.getNumberOfTimes(RateEvent.USER_WENT_TO_STORE);
		}

		#getAnyLimitReachedTime()
		{
			return this.eventStorageProvider.getLastTime(RateEvent.ANY_EVENT_COUNTER_REACHED_LIMIT) ?? null;
		}

		#getRateLastValue()
		{
			return this.eventStorageProvider.getContext(RateEvent.APP_RATED)?.value ?? null;
		}

		/**
		 * Use only for internal and testing purposes.
		 * @public
		 * @returns {object}
		 */
		getCounters()
		{
			return JSON.parse(
				Application.sharedStorage(sharedStorageKey).get(storedCountersDataKey) ?? '{}',
			);
		}

		/**
		 * Use only for internal and testing purposes.
		 * @public
		 * @param {object} counters
		 * @returns {object}
		 */
		saveCounters(counters)
		{
			Application.sharedStorage(sharedStorageKey).set(storedCountersDataKey, JSON.stringify(counters));
		}

		/**
		 * Use only for internal and testing purposes.
		 * @public
		 * @param event
		 * @param value
		 */
		setCounterValue(event, value)
		{
			const counters = this.getCounters();
			counters[event] = value;
			this.saveCounters(counters);
		}

		#getAllEventsLastTriggerTime()
		{
			const eventsLastTriggerTime = JSON.parse(
				Application.sharedStorage(sharedStorageKey).get(eventLastTriggerTimeKey) ?? '{}',
			);
			Object.keys(eventsLastTriggerTime).forEach((event) => {
				eventsLastTriggerTime[event] = new Date(eventsLastTriggerTime[event]);
			});

			return eventsLastTriggerTime;
		}

		/**
		 * @param {object} allEventsLastTriggerTime
		 */
		#saveAllEventsLastTriggerTime(allEventsLastTriggerTime = {})
		{
			const preparedData = allEventsLastTriggerTime;
			Object.keys(preparedData).forEach((event) => {
				preparedData[event] = preparedData[event].toISOString();
			});
			Application.sharedStorage(sharedStorageKey).set(
				eventLastTriggerTimeKey,
				JSON.stringify(allEventsLastTriggerTime),
			);
		}

		/**
		 * @param {string} event
		 * @returns {Date|null}
		 */
		#getEventLastTriggerTime(event)
		{
			const eventsLastTriggerTime = this.#getAllEventsLastTriggerTime();

			return eventsLastTriggerTime[event] ?? null;
		}

		/**
		 * @param {string} event
		 * @param {Date} [time = new Date()]
		 */
		#saveEventLastTriggerTime(event, time = new Date())
		{
			const allEventsLastTriggerTime = this.#getAllEventsLastTriggerTime();
			allEventsLastTriggerTime[event] = time;
			this.#saveAllEventsLastTriggerTime(allEventsLastTriggerTime);
		}

		/**
		 * @returns {Promise<void>}
		 */
		async #rememberRateBoxOpened()
		{
			await this.eventStorageProvider.save(RateEvent.RATE_BOX_OPENED);
		}

		/**
		 * @param {number} value
		 * @returns {Promise<void>}
		 */
		async #rememberAppRated(value)
		{
			await this.eventStorageProvider.save(RateEvent.APP_RATED, { context: { value } });
		}

		/**
		 * @returns {Promise<void>}
		 */
		async #rememberUserWentToStore()
		{
			if (this.#getUserWentToStoreCount() === 0)
			{
				await this.eventStorageProvider.save(RateEvent.USER_WENT_TO_STORE);
			}
		}

		#isRateBoxNeverShown()
		{
			return this.#getRateBoxDisplayCount() === 0;
		}

		#isRateBoxShownOnce()
		{
			return this.#getRateBoxDisplayCount() === 1;
		}

		#isRateBoxShownTwice()
		{
			return this.#getRateBoxDisplayCount() === 2;
		}

		#isRateBoxShownMoreThanTwice()
		{
			return this.#getRateBoxDisplayCount() > 2;
		}

		#isUserWentToStore()
		{
			return this.#getUserWentToStoreCount() > 0;
		}

		#isTimeForSecondDisplay()
		{
			return !this.#isAppRated()
				&& this.#getDaysCountSinceRateBoxWasShown() >= RateBoxDisplayIntervals.SECOND_DISPLAY_NOT_RATED;
		}

		#isTimeForThirdDisplay()
		{
			return !this.#isAppRated()
				&& this.#getDaysCountSinceRateBoxWasShown() >= RateBoxDisplayIntervals.THIRD_DISPLAY_NOT_RATED;
		}

		#isTimeForSubsequentDisplays()
		{
			return !this.#isAppRated()
				&& this.#getDaysCountSinceRateBoxWasShown() >= RateBoxDisplayIntervals.SUBSEQUENT_DISPLAYS_NOT_RATED;
		}

		#isTimeForRatedDisplay()
		{
			return this.#getDaysCountSinceRateBoxWasShown() >= RateBoxDisplayIntervals.DISPLAY_FOR_RATED;
		}

		#hasAnyLimitReachedInPast()
		{
			return this.#getAnyLimitReachedTime() !== null;
		}

		#hasAnyLimitReached(counters = null, limits = null)
		{
			const countersResult = counters ?? this.getCounters();
			const limitsResult = limits ?? this.getLimits();
			if (Type.isNil(countersResult) || Type.isNil(limitsResult))
			{
				return false;
			}

			return Object.keys(countersResult).some((event) => this.#isLimitReached(event, countersResult, limitsResult));
		}

		#isLimitReached(event, counters = null, limits = null)
		{
			const countersResult = counters ?? this.getCounters();
			const limitsResult = limits ?? this.getLimits();

			if (Type.isNil(countersResult[event]) || Type.isNil(limitsResult[event]))
			{
				return false;
			}

			return countersResult[event] >= limitsResult[event];
		}

		#isAppRated()
		{
			return this.#getRateLastTime() !== null;
		}

		#isAppTopRated()
		{
			return this.#getRateLastValue() >= MinRateForStore;
		}

		#getDaysCountSinceRateBoxWasShown()
		{
			const rateBoxLastDisplayTime = this.#getRateBoxLastDisplayTime();
			if (rateBoxLastDisplayTime === null)
			{
				return 0;
			}

			return Math.floor(
				(Date.now() - rateBoxLastDisplayTime) / millisecondsInDay,
			);
		}
	}

	module.exports = {
		AppRatingManager,
		RateEvent,
		RateBoxDisplayIntervals,
		millisecondsInDay,
		sharedStorageKey,
		storedCountersDataKey,
		storedCounterLimitsDataKey,
	};
});
