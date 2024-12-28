/**
 * @module im/messenger/controller/sidebar/channel/tabs/participants/participants-service
 */
jn.define('im/messenger/controller/sidebar/channel/tabs/participants/participants-service', (require, exports, module) => {
	const { ParticipantsService } = require('im/messenger/controller/sidebar/chat/tabs/participants/participants-service');

	/**
	 * @class ChannelParticipantsService
	 */
	class ChannelParticipantsService extends ParticipantsService
	{
		/**
		 * @desc Returns prepared user-item object for tab listview participants
		 * @param {object} user - users data
		 * @param {number} currentUserId - for check is me
		 * @param {?DialoguesModelState} dialogData
		 * @param {string} youTitle
		 * @return {object}
		 */
		prepareUserData(user, currentUserId, dialogData, youTitle)
		{
			const ownerId = dialogData?.owner || currentUserId;
			const userTitle = this.sidebarUserService.getTitleDataById(user.id);
			const isYou = currentUserId === user.id;
			const userAvatar = this.sidebarUserService.getAvatarDataById(user.id);
			const statusSvg = this.sidebarUserService.getUserStatus(user.id);
			const isAdmin = ownerId === user.id;
			const isManager = dialogData?.managerList.includes(user.id);
			const crownStatus = (isAdmin || isManager) ? this.sidebarUserService.getStatusCrown(isAdmin) : null;

			return {
				id: user.id,
				title: userTitle.title,
				isYouTitle: isYou ? youTitle : null,
				desc: userTitle.desc,
				imageUrl: userAvatar.imageUrl,
				imageColor: userAvatar.imageColor,
				statusSvg,
				crownStatus,
				isAdmin,
				isYou,
				isManager,
				isSuperEllipseAvatar: this.isSuperEllipseAvatar(),
			};
		}
	}

	module.exports = {
		ChannelParticipantsService,
	};
});
