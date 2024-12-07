/**
 * @module calendar/model/sharing
 */
jn.define('calendar/model/sharing', (require, exports, module) => {
	const { EventEmitter } = require('event-emitter');
	const { withCurrentDomain } = require('utils/url');
	const { Settings } = require('calendar/model/sharing/settings');
	const { SharingAjax } = require('calendar/ajax');

	const Context = {
		CRM: 'crm',
		CALENDAR: 'calendar',
	};

	const ModelSharingStatus = {
		ENABLE: 'enable',
		DISABLE: 'disable',
		UNDEFINED: 'undefined',
	};

	const state = {
		status: ModelSharingStatus.UNDEFINED,
		publicShortUrl: withCurrentDomain(),
		context: Context.CALENDAR,
	};

	/**
	 * @class ModelSharing
	 */
	class ModelSharing extends EventEmitter
	{
		/**
		 * @param context {string}
		 */
		constructor(context)
		{
			super();

			this.setUid(Random.getString());

			this.setContext(context);

			this.userLinks = {};
		}

		setFields(props)
		{
			const { isEnabled, shortUrl, settings, userInfo, options } = props;

			const status = (isEnabled === true)
				? ModelSharingStatus.ENABLE
				: ModelSharingStatus.DISABLE
			;

			this.setStatus(status);
			this.setPublicShortUrl(shortUrl);
			this.setSettings(new Settings(settings));

			if (options)
			{
				this.setOptions(options);
			}

			this.setUserInfo(userInfo);
			this.clearMembers();
		}

		getFieldsValues()
		{
			return {
				status: this.status,
				context: this.context,
				publicShortUrl: this.publicShortUrl,
				restrictionStatus: this.restrictionStatus,
				promoStatus: this.promoStatus,
				userInfo: this.userInfo,
				settings: this.settings,
			};
		}

		setStatus(value)
		{
			this.status = value && Object.values(ModelSharingStatus).includes(value)
				? value.toString()
				: state.status;
		}

		isEnabled()
		{
			return this.status === ModelSharingStatus.ENABLE;
		}

		setContext(value)
		{
			this.context = value && Object.values(Context).includes(value)
				? value.toString()
				: state.context;
		}

		setPublicShortUrl(value)
		{
			this.publicShortUrl = value && value.length > 0
				? value.toString()
				: state.publicShortUrl;
		}

		setUserInfo(userInfo)
		{
			this.userInfo = userInfo;
		}

		setSettings(settings)
		{
			this.settings = settings;
		}

		setOptions(options)
		{
			this.sortJointLinksByFrequentUse = options.sortJointLinksByFrequentUse;
		}

		/**
		 * @returns {string}
		 */
		getStatus()
		{
			return this.status;
		}

		/**
		 * @returns {string}
		 */
		getContext()
		{
			return this.context;
		}

		/**
		 * @returns {{id: number, name: string, avatar: string}}
		 */
		getUserInfo()
		{
			return this.userInfo;
		}

		/**
		 * @returns {Settings}
		 */
		getSettings()
		{
			return this.settings;
		}

		/**
		 * @returns {string}
		 */
		getRestrictionStatus()
		{
			return this.restrictionStatus;
		}

		/**
		 * @returns {string}
		 */
		getPromoStatus()
		{
			return this.promoStatus;
		}

		/**
		 * @returns {Promise}
		 */
		async getJointPublicShortUrl()
		{
			const members = this.getMembers();
			if (members.length === 1)
			{
				return this.getPublicShortUrl();
			}

			const memberIds = members.map((member) => member.id);
			const jointKey = this.getJointKey(memberIds);

			if (this.userLinks[jointKey])
			{
				this.clearMembers();

				this.emit('CalendarSharing:JointLinkCreated');

				const link = this.userLinks[jointKey];
				this.increaseFrequentUse(link.hash);

				return link.shortUrl;
			}

			const response = await SharingAjax.createJointLink({ memberIds });
			const link = response.data.link;

			link.members = members.filter((user) => user.id !== this.getUserInfo().id);
			link.dateCreate = (new Date()).toISOString();

			this.addUserLink(link);

			this.clearMembers();

			this.emit('CalendarSharing:JointLinkCreated');

			return link.shortUrl;
		}

		/**
		 * @returns {Promise}
		 */
		async loadUserLinks()
		{
			if (this.loadedUserLinks)
			{
				return this.getUserLinks();
			}

			const response = await SharingAjax.getAllUserLinks();

			const userLinks = Object.values(response.data.userLinks);

			userLinks.forEach((link) => this.addUserLink(link));

			this.loadedUserLinks = true;

			return this.getUserLinks();
		}

		getUserLinks()
		{
			return Object.values(this.userLinks);
		}

		addUserLink(link)
		{
			// eslint-disable-next-line no-param-reassign
			link.members = this.prepareLinkMembers(link.members);

			const memberIds = link.members.map((member) => member.id);
			const jointKey = this.getJointKey(memberIds);

			if (this.userLinks[jointKey]?.url === link.shortUrl)
			{
				return;
			}

			this.userLinks[jointKey] = link;
		}

		deleteLink(linkToDelete)
		{
			const memberIds = linkToDelete.members.map((member) => member.id);
			const jointKey = this.getJointKey(memberIds);
			delete this.userLinks[jointKey];

			void SharingAjax.deleteUserLink({
				hash: linkToDelete.hash,
			});

			this.emit('CalendarSharing:UserLinkDeleted');
		}

		clearLinks()
		{
			this.userLinks = {};
		}

		/**
		 * @private
		 */
		getJointKey(memberIds)
		{
			return [...memberIds].sort((a, b) => a - b).join(',');
		}

		/**
		 * @returns {string}
		 */
		getPublicShortUrl()
		{
			return this.publicShortUrl;
		}

		clearMembers()
		{
			this.members = [this.getUserInfo()];
		}

		setMembers(members)
		{
			this.members = this.prepareLinkMembers(members);

			this.emit('CalendarSharing:JointMembersUpdated');
		}

		prepareLinkMembers(linkMembers)
		{
			let members = Object.values(linkMembers);

			const owner = this.getUserInfo();
			if (!members.find((user) => user.id === owner.id))
			{
				members.push(owner);
			}

			members = [...members].sort((user) => (user.id === owner.id ? -1 : 0));

			return members;
		}

		getMembers()
		{
			return this.members;
		}

		increaseFrequentUse(hash)
		{
			void SharingAjax.increaseFrequentUse({ hash });
		}

		setSortByFrequentUse(doSortByFrequentUse)
		{
			this.sortJointLinksByFrequentUse = doSortByFrequentUse;
			void SharingAjax.setSortJointLinksByFrequentUse({ sortByFrequentUse: doSortByFrequentUse ? 'Y' : 'N' });
		}

		isSortByFrequentUse()
		{
			return this.sortJointLinksByFrequentUse;
		}
	}

	module.exports = {
		ModelSharing,
		ModelSharingStatus,
		SharingContext: Context,
	};
});
