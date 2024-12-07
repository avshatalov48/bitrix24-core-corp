/**
 * @module layout/ui/plan-restriction/demo-activation-success
 */
jn.define('layout/ui/plan-restriction/demo-activation-success', (require, exports, module) => {
	const { BottomSheet } = require('bottom-sheet');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons');

	/**
	 * @class DemoActivationSuccess
	 */
	class DemoActivationSuccess extends LayoutComponent
	{
		static open(parentWidget = PageManager)
		{
			void new BottomSheet({
				component: (layout) => new DemoActivationSuccess({ layout }),
			})
				.setParentWidget(parentWidget)
				.setMediumPositionHeight(430)
				.disableSwipe()
				.open()
			;
		}

		componentDidMount()
		{
			this.props.layout.on('onViewHidden', () => this.relogin());
		}

		render()
		{
			return Box(
				{
					backgroundColor: Color.bgContentPrimary,
					footer: BoxFooter(
						{
							safeArea: true,
						},
						Button({
							testId: 'DEMO_ACTIVATION_SUCCESS_BUTTON_OK',
							text: Loc.getMessage('PLAN_RESTRICTION_DEMO_ACTIVATION_SUCCESS_BUTTON_OK'),
							size: ButtonSize.L,
							stretched: true,
							style: {
								marginBottom: Indent.L.toNumber(),
							},
							onClick: () => this.relogin(),
						}),
						Button({
							testId: 'DEMO_ACTIVATION_SUCCESS_BUTTON_MORE',
							text: Loc.getMessage('PLAN_RESTRICTION_DEMO_ACTIVATION_SUCCESS_BUTTON_MORE'),
							size: ButtonSize.L,
							design: ButtonDesign.PLAN_ACCENT,
							stretched: true,
							onClick: () => helpdesk.openHelpArticle('22476482', 'helpdesk'),
						}),
					),
				},
				Image({
					resizeMode: 'stretch',
					style: {
						width: '100%',
						height: 149,
						position: 'absolute',
					},
					svg: {
						content: '<svg xmlns="http://www.w3.org/2000/svg" width="375" height="149" viewBox="0 0 375 149" fill="none"><g opacity="0.52"><path d="M64.6084 7.88688L67.7089 16.5274L65.1037 17.2927L62.0032 8.6521L64.6084 7.88688Z" fill="#B6D40C"/><path d="M43.9873 25.7588L38.2836 38.5876L34 37.4513L39.7036 24.6224L43.9873 25.7588Z" fill="#F5CD0B"/><path d="M14.6503 9.90191L20.8319 22.7617L17.1179 23.8436L10.9362 10.9838L14.6503 9.90191Z" fill="#F169B3"/><path d="M8.6747 60.1293L3.61137 70.915L0.791685 69.1103L5.85505 58.3244L8.6747 60.1293Z" fill="#2DBEEC"/><path d="M-0.890603 24.0592L-3.16991 36.5412L-6.90292 35.5687L-4.62245 23.0869L-0.890603 24.0592Z" fill="#29B54B"/><path d="M54.5104 41.9593L65.5756 45.7076L64.0794 48.9056L53.0142 45.1573L54.5104 41.9593Z" fill="#B6D40C"/><path d="M89.6198 33.5714L93.7594 42.647L90.895 43.5866L86.7554 34.511L89.6198 33.5714Z" fill="#2DBEEC"/><path d="M184.51 21.9593L195.576 25.7076L194.079 28.9056L183.014 25.1573L184.51 21.9593Z" fill="#B6D40C"/><path d="M42.1323 77L49.957 93.3045L45.0671 96.742L37.2422 80.4374L42.1323 77Z" fill="#F5CD0B"/><path d="M7.18792 93.2357L23.5756 97.5978L21.7717 101.802L5.38394 97.4395L7.18792 93.2357Z" fill="#F169B3"/><path d="M27.8111 132.226L14.7258 139.886L12.7885 135.723L25.8739 128.063L27.8111 132.226Z" fill="#2DBEEC"/><path d="M127.811 22.2262L114.726 29.886L112.788 25.7227L125.874 18.0628L127.811 22.2262Z" fill="#2DBEEC"/><path d="M328.121 20.0242L335.122 29.3783L332.078 31.1678L325.078 21.8136L328.121 20.0242Z" fill="#B6D40C"/><path d="M248.218 3L250.998 20.8709L245.324 22.7483L242.544 4.87737L248.218 3Z" fill="#F5CD0B"/><path d="M208.674 6.29844L223.102 15.2112L220.16 18.7143L205.732 9.8015L208.674 6.29844Z" fill="#F169B3"/><path d="M279.089 36.1611L281.005 51.2027L276.415 51.352L274.5 36.3103L279.089 36.1611Z" fill="#2DBEEC"/><path d="M90.6755 11.2419L84.2 25.3585L80.2563 23.2159L86.7331 9.09999L90.6755 11.2419Z" fill="#29B54B"/><path d="M34.3953 56.6289L35.0437 67.4086L32.1476 67.0627L31.4993 56.2831L34.3953 56.6289Z" fill="#1DCEC4"/><path d="M69.386 65.3212L60.2482 71.0764L59.158 68.3711L68.2957 62.6161L69.386 65.3212Z" fill="#F169B3"/><path d="M240.444 41.0528L235.326 31.5435L238.099 30.6404L243.217 40.1497L240.444 41.0528Z" fill="#B6D40C"/><path d="M381.201 101.481L378.741 112.906L375.394 111.784L377.854 100.36L381.201 101.481Z" fill="#B6D40C"/><path d="M365.06 98.3887L352.056 106.581L348.68 102.785L361.684 94.5922L365.06 98.3887Z" fill="#F5CD0B"/><path d="M355.053 58.6424L348.598 72.0279L345.279 69.783L351.735 56.3976L355.053 58.6424Z" fill="#F169B3"/><path d="M309.88 69.0382L299.449 75.2132L297.885 71.8907L308.316 65.7157L309.88 69.0382Z" fill="#1DCEC4"/><path d="M315.425 34.2346L304.623 45.3928L301.618 42.0588L312.421 30.9016L315.425 34.2346Z" fill="#29B54B"/><path d="M263.584 20.0374L257.452 34.3056L253.459 32.2567L259.592 17.9892L263.584 20.0374Z" fill="#F169B3"/><path d="M217.078 17.1605L218.743 29.2272L215.231 29.4698L213.567 17.4029L217.078 17.1605Z" fill="#2DBEEC"/><path d="M157.532 0.274974L163.737 14.5118L159.51 16.0206L153.306 1.78328L157.532 0.274974Z" fill="#1DCEC4"/><path d="M281.011 17.1373L270.216 17.2661L270.7 14.3893L281.496 14.2605L281.011 17.1373Z" fill="#1DCEC4"/><path d="M344.21 35.664L355.823 34.3909L355.817 37.9216L344.204 39.1946L344.21 35.664Z" fill="#B6D40C"/><path d="M335.89 5.28809L349.865 16.7651L346.883 21.9453L332.908 10.4683L335.89 5.28809Z" fill="#F5CD0B"/><path d="M318.389 88.6956L330.09 86.6187L330.188 89.8231L318.486 91.8999L318.389 88.6956Z" fill="#F169B3"/><path d="M325.181 110.487L325.373 121.615L322.013 121.356L321.821 110.228L325.181 110.487Z" fill="#2DBEEC"/><path d="M354.202 133.333L368.198 140.061L365.985 143.966L351.989 137.236L354.202 133.333Z" fill="#29B54B"/><path d="M301.904 5.7567L305.436 15.9618L302.554 16.4103L299.022 6.20528L301.904 5.7567Z" fill="#1DCEC4"/></g></svg>',
					},
				}),
				StatusBlock({
					image: Image({
						style: {
							width: 108,
							height: 108,
						},
						svg: {
							uri: makeLibraryImagePath('demo-activation-success.svg', 'graphic'),
						},
					}),
					title: Loc.getMessage('PLAN_RESTRICTION_DEMO_ACTIVATION_SUCCESS_TITLE'),
					description: Loc.getMessage('PLAN_RESTRICTION_DEMO_ACTIVATION_SUCCESS_DESCRIPTION'),
					descriptionColor: Color.base1,
					testId: 'DEMO_ACTIVATION_SUCCESS',
				}),
			);
		}

		relogin()
		{
			Application.clearCache();
			Application.relogin();
		}
	}

	module.exports = { DemoActivationSuccess };
});
