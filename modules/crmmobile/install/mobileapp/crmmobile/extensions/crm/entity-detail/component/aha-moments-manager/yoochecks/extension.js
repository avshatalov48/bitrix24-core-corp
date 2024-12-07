/**
 * @module crm/entity-detail/component/aha-moments-manager/yoochecks
 */
jn.define('crm/entity-detail/component/aha-moments-manager/yoochecks', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { Oauth } = require('crm/payment-system/creation/actions/oauth');
	const { Before } = require('crm/payment-system/creation/actions/before');
	const { handleErrors } = require('crm/error');

	const pathToExtension = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/entity-detail/component/aha-moments-manager/yoochecks`;

	/**
	 * @class Yoochecks
	 */
	class Yoochecks extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				isVisible: false,
				isClosed: false,
			};
			this.uid = props.uid || Random.getString();

			this.banner = this.getBanner();

			this.psCreationOauthAction = (new Oauth({ showMenuCustomSection: true }))
				.setContext('popup-yoochecks');
			this.psCreationBeforeAction = new Before();

			this.close = this.close.bind(this);
		}

		get detailCard()
		{
			return this.props.detailCard;
		}

		show()
		{
			this.banner.show();
		}

		/**
		 * @public
		 */
		actualize()
		{
			const isVisible = this.isVisible();

			if (isVisible)
			{
				this.setState({ isVisible }, () => {
					if (isVisible)
					{
						this.show();
					}
				});
			}
		}

		isVisible()
		{
			const { isClosed } = this.state;
			const { detailCard } = this;
			const { ahaMoments } = detailCard.getComponentParams();

			return (
				detailCard.hasEntityModel()
				&& !isClosed
				&& ahaMoments
				&& ahaMoments.yoochecks
			);
		}

		render()
		{
			return View();
		}

		getBanner()
		{
			return new ContextMenu({
				banner: {
					featureItems: [
						Loc.getMessage('M_CRM_DETAIL_AHA_YOOCHECKS_FEATURE_1'),
						Loc.getMessage('M_CRM_DETAIL_AHA_YOOCHECKS_FEATURE_2'),
						Loc.getMessage('M_CRM_DETAIL_AHA_YOOCHECKS_FEATURE_3'),
						Loc.getMessage('M_CRM_DETAIL_AHA_YOOCHECKS_FEATURE_4'),
					],
					imagePath: `${pathToExtension}/images/icon.png`,
					positioning: 'vertical',
					title: Loc.getMessage('M_CRM_DETAIL_AHA_YOOCHECKS_TITLE'),
					showSubtitle: false,
					subtext: Loc.getMessage('M_CRM_DETAIL_AHA_YOOCHECKS_SUBTEXT'),
					buttonText: Loc.getMessage('M_CRM_DETAIL_AHA_YOOCHECKS_BUTTON'),
					onButtonClick: this.handleButtonClick.bind(this),
				},
				params: {
					title: Loc.getMessage('M_CRM_DETAIL_AHA_YOOCHECKS_BACKDROP_TITLE'),
					helpUrl: helpdesk.getArticleUrl('17886650'),
				},
				onClose: () => this.close(),
			});
		}

		handleButtonClick()
		{
			BX.ajax
				.runAction('crmmobile.ReceivePayment.PaySystemMode.initializeOauthParams', { json: {} })
				.then((response) => {
					const oauthData = response.data.oauthData[Handlers.yandexcheckout];
					const beforeData = response.data.beforeData[Handlers.yandexcheckout];

					this.banner.close(() => {
						this.psCreationOauthAction.run(oauthData)
							.then(this.psCreationBeforeAction.run.bind(null, beforeData))
							.catch(handleErrors);
					});
				}).catch(handleErrors);
		}

		close()
		{
			BX.ajax.runAction('crmmobile.AhaMoment.setViewed', {
				data: {
					name: 'yoochecks',
				},
			})
				.catch(console.error);

			this.setState({
				isClosed: true,
				isVisible: false,
			});
		}
	}

	const Handlers = {
		yandexcheckout: 'yandexcheckout',
	};

	module.exports = { Yoochecks };
});
