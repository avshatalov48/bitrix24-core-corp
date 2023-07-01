/**
 * @module crm/entity-detail/component/aha-moments-manager/go-to-chat
 */
jn.define('crm/entity-detail/component/aha-moments-manager/go-to-chat', (require, exports, module) => {
	const { Loc } = require('loc');
	const { cross } = require('assets/common');
	const { getEntityMessage } = require('crm/loc');

	const pathToExtension = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/entity-detail/component/aha-moments-manager/go-to-chat`;

	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class GoToChat
	 * */
	class GoToChat extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				isVisible: false,
				isClosed: false,
			};

			this.nodeRef = null;
			this.popupRef = null;

			this.close = this.close.bind(this);
		}

		get detailCard()
		{
			return this.props.detailCard;
		}

		show()
		{
			if (this.nodeRef && this.popupRef)
			{
				const animationOptions = {
					duration: 300,
					opacity: 1,
					option: 'easeInOut',
				};

				this.nodeRef.animate(animationOptions);
				this.popupRef.animate(animationOptions);
			}
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
						setTimeout(() => this.show(), 300);
					}
				});
			}
		}

		isVisible()
		{
			const { isClosed } = this.state;
			const { ahaMoments } = this.detailCard.getComponentParams();

			return (
				this.detailCard.hasEntityModel()
				&& !isClosed
				&& ahaMoments
				&& ahaMoments.goToChat
			);
		}

		render()
		{
			const { isVisible } = this.state;

			// fix android different positioning with native bottom buttons enabled or not
			let ahaMomentPosition = isAndroid ? 0 : device.screen.safeArea.bottom;
			ahaMomentPosition += 65;

			const bottomTriangleRightPosition = isAndroid ? 16 : 18;

			return View(
				{
					style: {
						position: 'absolute',
						display: isVisible ? undefined : 'none',
						zIndex: 10,
						bottom: 0,
						top: 0,
						left: 0,
						right: 0,
						opacity: 0,
					},
					ref: (ref) => this.nodeRef = ref,
				},
				View(
					{
						style: {
							backgroundColor: '#000000',
							opacity: 0.4,
							flex: 1,
							position: 'absolute',
							top: 44,
							left: 0,
							right: 0,
							bottom: 0,
						},
						onClick: isVisible && this.close,
					},
				),
				View(
					{
						style: {
							alignItems: 'flex-end',
							justifyContent: 'center',
							width: '100%',
							paddingVertical: 14,
							paddingHorizontal: 16,
							position: 'absolute',
							opacity: 0,
							bottom: ahaMomentPosition,
							flexDirection: 'column',
						},
						ref: (ref) => this.popupRef = ref,
						onClick: isVisible && (() => {}),
					},
					View(
						{
							style: {
								flexDirection: 'row',
								borderRadius: 12,
								padding: 12,
								width: '100%',
								backgroundColor: '#fff',
							},
						},
						View(
							{
								style: {
									backgroundColor: '#e5f9ff',
									borderRadius: 6,
									width: 94,
									alignItems: 'center',
									justifyContent: 'center',
									alignContent: 'center',
									marginRight: 12,
								},
							},
							Image({
								style: {
									width: 74,
									height: 74,
								},
								svg: {
									uri: `${pathToExtension}/images/icon.svg`,
								},
							}),
						),
						View(
							{
								style: {
									flex: 1,
									justifyContent: 'center',
									alignItems: 'flex-start',
								},
							},
							Text({
								style: {
									fontSize: 16,
									fontWeight: '500',
									color: '#333333',
									paddingBottom: 5,
								},
								text: Loc.getMessage('M_CRM_DETAIL_AHA_GO2CHAT_TITLE'),
							}),
							Text({
								style: {
									fontSize: 14,
									color: '#525c69',
									lineHeightMultiple: 1.2,
								},
								text: getEntityMessage(
									'M_CRM_DETAIL_AHA_GO2CHAT_DESCRIPTION',
									this.detailCard.getEntityTypeId(),
								),
							}),
						),
						Image({
							style: {
								position: 'absolute',
								width: 24,
								height: 24,
								right: 4,
								top: 4,
							},
							svg: {
								content: cross('#cccccc'),
							},
							onClick: isVisible && this.close,
						}),
					),
					Image({
						style: {
							width: 22,
							height: 12,
							zIndex: 20,
							right: bottomTriangleRightPosition,
						},
						svg: {
							content: '<svg width="22" height="12" viewBox="0 0 22 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 0L12.1057 10.7937C11.5112 11.4423 10.4888 11.4423 9.89427 10.7937L0 0H22Z" fill="white"/></svg>',
						},
					}),
				),
			);
		}

		close()
		{
			BX.ajax.runAction('crmmobile.AhaMoment.setViewed', {
				data: {
					name: 'GoToChat',
				},
			})
				.catch(console.error);

			this.nodeRef.animate({
				duration: 0,
				opacity: 0,
			}, () => this.setState({
				isClosed: true,
				isVisible: false,
			}));
		}
	}

	module.exports = { GoToChat };
});
