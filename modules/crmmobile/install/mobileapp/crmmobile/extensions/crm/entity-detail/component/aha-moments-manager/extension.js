/**
 * @module crm/entity-detail/component/aha-moments-manager
 */
jn.define('crm/entity-detail/component/aha-moments-manager', (require, exports, module) => {
	const { GoToChat } = require('crm/entity-detail/component/aha-moments-manager/go-to-chat');
	const { Yoochecks } = require('crm/entity-detail/component/aha-moments-manager/yoochecks');

	/**
	 * @class AhaMomentsManager
	 */
	class AhaMomentsManager
	{
		constructor()
		{
			this.availableAhaMoments = {
				goToChat: GoToChat,
				yoochecks: Yoochecks,
			};
		}

		getAvailableAhaMoments()
		{
			return this.availableAhaMoments;
		}

		getAhaMomentClassByName(ahaMomentName)
		{
			if (!this.getAvailableAhaMoments().hasOwnProperty(ahaMomentName))
			{
				console.error(`Unknown aha: ${ahaMomentName}`);

				return null;
			}

			return this.getAvailableAhaMoments()[ahaMomentName];
		}

		chooseAhaMoment(context)
		{
			let moment = null;
			const contextAhaMoments = context.getAvailableAhaMoments();

			for (const index in contextAhaMoments)
			{
				if (this.getAvailableAhaMoments().hasOwnProperty(contextAhaMoments[index]))
				{
					const MomentClass = this.getAhaMomentClassByName(contextAhaMoments[index]);
					moment = new MomentClass({
						detailCard: context,
					});

					if (moment.isVisible())
					{
						return moment;
					}
				}
			}

			return null;
		}
	}

	module.exports = { AhaMomentsManager: new AhaMomentsManager() };
});
