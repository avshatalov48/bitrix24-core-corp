/**
 * @module layout/ui/kanban/toolbar/stage-dropdown
 */
jn.define('layout/ui/kanban/toolbar/stage-dropdown', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { Filler } = require('layout/ui/kanban/toolbar/filler');
	const { PureComponent } = require('layout/pure-component');
	const nothing = () => {};

	const StageDropdown = ({ onClick, activeStage, counter, title, loading }) => {
		const Logo = () => Image({
			style: Styles.stageLogo,
			svg: {
				content: activeStage ? Icons.singleStage(activeStage.color) : Icons.allStages,
			},
		});

		const PipelineInfo = () => {
			if (!title)
			{
				return Filler(100, {
					marginTop: Application.getPlatform() === 'android' ? 8 : 6,
					marginBottom: Application.getPlatform() === 'android' ? 8 : 7,
				});
			}

			return Text({
				style: Styles.categoryName,
				testId: 'stageToolbarCategoryName',
				text: title,
				ellipsize: 'end',
				numberOfLines: 1,
			});
		};

		const StageInfo = () => View(
			{
				style: Styles.stageInfoWrapper,
			},
			View(
				{
					style: Styles.stageNameWrapper,
				},
				StageTitle(),
				StageCounter(),
			),
			ArrowDown(),
		);

		const StageTitle = () => {
			const text = activeStage ? activeStage.name : Loc.getMessage('MCRM_STAGE_TOOLBAR_ALL_STAGES');

			return Text({
				text,
				style: Styles.stageName,
				testId: 'stageToolbarStageName',
				ellipsize: 'end',
				numberOfLines: 1,
			});
		};

		const StageCounter = () => {
			if (counter)
			{
				return Text({
					testId: 'stageToolbarStageCounter',
					style: Styles.stageCount,
					text: ` (${counter.count}) `,
					numberOfLines: 1,
				});
			}

			return View(
				{
					style: Styles.stageCountFiller,
				},
				Filler(21, {
					marginTop: Application.getPlatform() === 'android' ? 8 : 6,
				}),
			);
		};

		const ArrowDown = () => View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'center',
					alignItems: 'center',
				},
			},
			Image({
				style: Styles.stageNameArrow,
				svg: {
					content: Icons.arrow,
				},
			}),
		);

		return View(
			{
				style: Styles.rootWrapper,
				onClick: onClick || nothing,
			},
			Logo(),
			View(
				{
					style: Styles.stageSelector,
				},
				PipelineInfo(),
				StageInfo(),
			),
		);
	};

	class StageDropdownClass extends PureComponent
	{
		componentWillReceiveProps(props)
		{
			if (!props.activeStageExist && props.setDefaultStage)
			{
				props.setDefaultStage();
			}
		}

		render()
		{
			return StageDropdown(this.props);
		}
	}

	const Styles = {
		rootWrapper: {
			flexDirection: 'row',
		},
		stageLogo: {
			width: 38,
			height: 38,
			marginLeft: 9,
			marginRight: 9,
		},
		stageSelector: {
			paddingRight: 10,
			flex: 1,
		},
		categoryName: {
			color: AppTheme.colors.base4,
			fontSize: 14,
			fontWeight: '500',
			marginBottom: Application.getPlatform() === 'android' ? 0 : 2,
		},
		stageInfoWrapper: {
			flexDirection: 'row',
			alignItems: 'center',
			flexWrap: 'no-wrap',
			paddingRight: 10,
			paddingTop: 0,
		},
		stageNameWrapper: {
			flexShrink: 2,
			flexDirection: 'row',
		},
		stageName: {
			color: AppTheme.colors.base2,
			fontSize: 16,
			fontWeight: '500',
			flexShrink: 2,
		},
		stageCount: {
			marginTop: 1,
			color: AppTheme.colors.base2,
			textAlign: 'left',
			fontWeight: '500',
		},
		stageCountFiller: {
			marginTop: 2,
			color: AppTheme.colors.base1,
			flexDirection: 'row',
			marginRight: 4,
			marginLeft: 10,
		},
		stageNameArrow: {
			width: 10,
			height: 7,
		},
	};

	const Icons = {
		allStages: `<svg width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg"><circle opacity="0.18" cx="19" cy="19" r="18" fill="#A8ADB4"/><path d="M13 15.0625C13 13.9234 13.8822 13 14.9704 13H21.7444C22.3787 13 22.9742 13.3196 23.3444 13.8588L25.8148 17.8981C26.0617 18.2577 26.0617 18.7423 25.8148 19.1019L23.3444 23.1412C22.9742 23.6804 22.3787 24 21.7444 24L14.9704 24C13.8822 24 13 23.0766 13 21.9375V15.0625Z" fill="#2FC6F6"/><path d="M18.711 15.5H18.711L12.1329 15.5002C12.1328 15.5002 12.1328 15.5002 12.1328 15.5002C10.6947 15.5002 9.5 16.6318 9.5 18.0627V24.9377C9.5 26.3686 10.6947 27.5002 12.1329 27.5002H12.1329L18.711 27.5C18.7111 27.5 18.7111 27.5 18.7111 27.5C19.5517 27.5 20.3468 27.1088 20.8443 26.4395L20.8542 26.4261L20.8633 26.4121L23.4579 22.3854C23.8423 21.8551 23.8423 21.1449 23.4579 20.6146L20.8633 16.5879L20.8542 16.5739L20.8442 16.5605C20.3468 15.8912 19.5517 15.5 18.711 15.5Z" fill="#dfe0e3" stroke= "${AppTheme.colors.bgSecondary}"/><path d="M20.711 13.5H20.711L14.1329 13.5002C14.1328 13.5002 14.1328 13.5002 14.1328 13.5002C12.6947 13.5002 11.5 14.6318 11.5 16.0627V22.9377C11.5 24.3686 12.6947 25.5002 14.1329 25.5002H14.1329L20.711 25.5C20.7111 25.5 20.7111 25.5 20.7111 25.5C21.5517 25.5 22.3468 25.1088 22.8443 24.4395L22.8542 24.4261L22.8633 24.4121L25.4579 20.3854C25.8423 19.8551 25.8423 19.1449 25.4579 18.6146L22.8633 14.5879L22.8542 14.5739L22.8442 14.5605C22.3468 13.8912 21.5517 13.5 20.711 13.5Z" fill="#d5d7db" stroke="${AppTheme.colors.bgSecondary}"/><path d="M23.711 10.5H23.711L17.1329 10.5002C17.1328 10.5002 17.1328 10.5002 17.1328 10.5002C15.6947 10.5002 14.5 11.6318 14.5 13.0627V19.9377C14.5 21.3686 15.6947 22.5002 17.1329 22.5002H17.1329L23.711 22.5C23.7111 22.5 23.7111 22.5 23.7111 22.5C24.5517 22.5 25.3468 22.1088 25.8443 21.4395L25.8542 21.4261L25.8633 21.4121L28.4579 17.3854C28.8423 16.8551 28.8423 16.1449 28.4579 15.6146L25.8633 11.5879L25.8542 11.5739L25.8442 11.5605C25.3468 10.8912 24.5517 10.5 23.711 10.5Z" fill="#BDC1C6" stroke="#EEF2F4"/></svg>`,
		singleStage: (color) => `<svg width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg"><circle opacity="0.18" cx="19" cy="19" r="18" fill="${color}"/><path d="M13 15.0625C13 13.9234 13.8822 13 14.9704 13H21.7444C22.3787 13 22.9742 13.3196 23.3444 13.8588L25.8148 17.8981C26.0617 18.2577 26.0617 18.7423 25.8148 19.1019L23.3444 23.1412C22.9742 23.6804 22.3787 24 21.7444 24L14.9704 24C13.8822 24 13 23.0766 13 21.9375V15.0625Z" fill="${color}"/></svg>`,
		arrow: '<svg width="10" height="7" viewBox="0 0 10 7" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.77822 0.933411L5.76018 3.95144L4.99984 4.70002L4.25391 3.95144L1.23588 0.933411L0.170898 1.99839L5.007 6.8345L9.84311 1.99839L8.77822 0.933411Z" fill="#A8ADB4"/></svg>',
	};

	module.exports = { StageDropdown, StageDropdownClass };
});
