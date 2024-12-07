/**
 * @module stafftrack/analytics
 */
jn.define('stafftrack/analytics', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { BaseEnum } = require('utils/enums/base');

	const { CheckinOpenEnum } = require('stafftrack/analytics/enum/checkin-open');
	const { SetupGeoEnum } = require('stafftrack/analytics/enum/setup-geo');
	const { SetupChatEnum } = require('stafftrack/analytics/enum/setup-chat');
	const { SetupPhotoEnum } = require('stafftrack/analytics/enum/setup-photo');
	const { CheckinSentEnum } = require('stafftrack/analytics/enum/checkin-sent');
	const { GeoBoolEnum } = require('stafftrack/analytics/enum/geo-bool');
	const { ChatBoolEnum } = require('stafftrack/analytics/enum/chat-bool');
	const { ImageBoolEnum } = require('stafftrack/analytics/enum/image-bool');
	const { StatsOpenEnum } = require('stafftrack/analytics/enum/stats-open');
	const { HelpdeskEnum } = require('stafftrack/analytics/enum/helpdesk');

	const TOOL = 'checkin';
	const CATEGORY = 'shift';

	class Analytics
	{
		/**
		 * @param type {CheckinOpenEnum}
		 */
		static sendCheckinOpen(type)
		{
			this.sendAnalytics('drawer_open', { type });
		}

		/**
		 * @param doSend {boolean}
		 */
		static sendSetupGeo(doSend)
		{
			const type = doSend ? SetupGeoEnum.TURN_ON : SetupGeoEnum.TURN_OFF;
			this.sendAnalytics('setup_geo', { type });
		}

		/**
		 * @param doSend {boolean}
		 */
		static sendSetupChat(doSend)
		{
			const type = doSend ? SetupChatEnum.TURN_ON : SetupChatEnum.TURN_OFF;
			this.sendAnalytics('setup_chat', { type });
		}

		/**
		 * @param type {SetupPhotoEnum}
		 */
		static sendSetupPhoto(type)
		{
			this.sendAnalytics('setup_photo', { type });
		}

		/**
		 * @param type {CheckinSentEnum}
		 * @param params {{geoSent: boolean, chatSent: boolean, imageSent: boolean}}
		 */
		static sendCheckIn(type, params)
		{
			const { geoSent, chatSent, imageSent } = params;

			this.sendAnalytics('checkin_sent', {
				type,
				p1: geoSent ? GeoBoolEnum.GEO_Y : GeoBoolEnum.GEO_N,
				p2: chatSent ? ChatBoolEnum.CHAT_Y : ChatBoolEnum.CHAT_N,
				p3: imageSent ? ImageBoolEnum.IMAGE_Y : ImageBoolEnum.IMAGE_N,
			});
		}

		/**
		 * @param type {StatsOpenEnum}
		 */
		static sendStatisticsOpen(type)
		{
			this.sendAnalytics('stats_open', { type });
		}

		/**
		 * @param section {HelpdeskEnum}
		 */
		static sendHelpdeskOpen(section)
		{
			this.sendAnalytics('reading_about', { section });
		}

		/**
		 * @private
		 * @param event {string}
		 * @param params {{type: BaseEnum, section: BaseEnum, p1: BaseEnum, p2: BaseEnum, p3: BaseEnum}}
		 */
		static sendAnalytics(event, params)
		{
			const { type, section, p1, p2, p3 } = params;

			const analytics = new AnalyticsEvent()
				.setTool(TOOL)
				.setCategory(CATEGORY)
				.setEvent(event)
			;

			if (type instanceof BaseEnum)
			{
				analytics.setType(type.getValue());
			}

			if (section instanceof BaseEnum)
			{
				analytics.setSection(section.getValue());
			}

			if (p1 instanceof BaseEnum)
			{
				analytics.setP1(p1.getValue());
			}

			if (p2 instanceof BaseEnum)
			{
				analytics.setP2(p2.getValue());
			}

			if (p3 instanceof BaseEnum)
			{
				analytics.setP3(p3.getValue());
			}

			analytics.send();
		}
	}

	module.exports = {
		Analytics,
		CheckinOpenEnum,
		SetupPhotoEnum,
		CheckinSentEnum,
		StatsOpenEnum,
		HelpdeskEnum,
	};
});
