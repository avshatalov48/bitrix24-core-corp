/**
 * @module copilot-features
 */
jn.define('copilot-features', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Area } = require('ui-system/layout/area');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { CopilotFeatureDetails } = require('copilot-features/src/details');
	const { Text4 } = require('ui-system/typography/text');
	const { Color, Indent, Corner } = require('tokens');
	const { H4 } = require('ui-system/typography/heading');
	const { ChipStatus, ChipStatusDesign, ChipStatusMode } = require('ui-system/blocks/chips/chip-status');
	const { Text5 } = require('ui-system/typography/text');
	const { makeLibraryImagePath } = require('asset-manager');

	const COPILOT_ARTICLE_IDS = {
		CRM: '24481492',
		FLOW_UP: '24481514',
	};

	/**
	 * @class CopilotFeatures
	 */
	class CopilotFeatures extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.features = this.getFeatures(this.props.availableFeatures);
		}

		get layout()
		{
			return this.props.layout;
		}

		getFeatures(availableFeatures)
		{
			const features = [];
			if (availableFeatures?.copilotChat)
			{
				features.push({
					title: Loc.getMessage('COPILOT_FEATURE_CHAT_TITLE'),
					description: Loc.getMessage('COPILOT_FEATURE_CHAT_DESCRIPTION'),
					imageSrc: makeLibraryImagePath('copilot-chat.svg', 'graphic'),
					isNew: true,
					onClick: () => this.openCopilotTab(),
				});
			}

			features.push({
				title: Loc.getMessage('COPILOT_FEATURE_FLOW_UP_TITLE'),
				description: Loc.getMessage('COPILOT_FEATURE_FLOW_UP_DESCRIPTION'),
				imageSrc: makeLibraryImagePath('copilot-flow-up.svg', 'graphic'),
				isNew: true,
				onClick: () => {
					CopilotFeatureDetails.open({
						parentWidget: this.layout,
						title: Loc.getMessage('COPILOT_FEATURE_FLOW_UP_TITLE'),
						imageSrc: makeLibraryImagePath('copilot-flow-up.svg', 'graphic'),
						advantages: [
							{
								icon: Icon.CIRCLE_CHECK,
								text: Loc.getMessage('COPILOT_FEATURE_FLOW_UP_ADVANTAGE_1'),
							},
							{
								icon: Icon.EDIT,
								text: Loc.getMessage('COPILOT_FEATURE_FLOW_UP_ADVANTAGE_2'),
							},
							{
								icon: Icon.MESSAGE,
								text: Loc.getMessage('COPILOT_FEATURE_FLOW_UP_ADVANTAGE_3'),
							},
						],
						onButtonClick: () => this.openHelpArticle(COPILOT_ARTICLE_IDS.FLOW_UP),
					});
				},
			});

			if (availableFeatures?.flowCopilot)
			{
				features.push({
					title: Loc.getMessage('COPILOT_FEATURE_FLOW_TITLE'),
					description: Loc.getMessage('COPILOT_FEATURE_FLOW_DESCRIPTION'),
					imageSrc: makeLibraryImagePath('copilot-flow.svg', 'graphic'),
					isNew: false,
					onClick: () => this.openFlowTab(),
				});
			}

			features.push({
				title: Loc.getMessage('COPILOT_FEATURE_CRM_TITLE'),
				description: Loc.getMessage('COPILOT_FEATURE_CRM_DESCRIPTION'),
				imageSrc: makeLibraryImagePath('copilot-crm.svg', 'graphic'),
				isNew: false,
				onClick: () => {
					CopilotFeatureDetails.open({
						parentWidget: this.layout,
						title: Loc.getMessage('COPILOT_FEATURE_CRM_TITLE'),
						imageSrc: makeLibraryImagePath('copilot-crm.svg', 'graphic'),
						advantages: [
							{
								icon: Icon.CIRCLE_CHECK,
								text: Loc.getMessage('COPILOT_FEATURE_CRM_ADVANTAGE_1'),
							},
							{
								icon: Icon.EDIT,
								text: Loc.getMessage('COPILOT_FEATURE_CRM_ADVANTAGE_2'),
							},
							{
								icon: Icon.MESSAGE,
								text: Loc.getMessage('COPILOT_FEATURE_CRM_ADVANTAGE_3'),
							},
						],
						onButtonClick: () => this.openHelpArticle(COPILOT_ARTICLE_IDS.CRM),
					});
				},
			});

			return features;
		}

		render()
		{
			return AreaList(
				{
					withScroll: true,
				},
				Area(
					{
						excludePaddingSide: {
							bottom: true,
						},

					},
					Text4({
						text: Loc.getMessage('COPILOT_FEATURE_DESCRIPTION'),
						color: Color.base3,
					}),
				),
				...this.features.map((feature, index) => Area(
					{
						excludePaddingSide: {
							bottom: true,
						},
						isFirst: true,
					},
					this.renderFeature(feature, index === 0),
					feature.isNew && ChipStatus({
						testId: 'copilot-feature-new-status',
						text: Loc.getMessage('COPILOT_FEATURE_NEW_STATUS_TEXT'),
						design: ChipStatusDesign.SUCCESS,
						mode: ChipStatusMode.TINTED,
						style: {
							position: 'absolute',
							top: index !== 0 && 5,
							right: 44,
							maxWidth: 250,
						},
					}),
				)),
			);
		}

		renderFeature(feature, isFirst)
		{
			const {
				title,
				description,
				imageSrc,
				onClick,
			} = feature;

			return View(
				{
					style: {
						borderColor: Color.bgSeparatorPrimary.toHex(),
						borderWidth: 1,
						paddingHorizontal: Indent.XL.toNumber(),
						paddingVertical: Indent.XL3.toNumber(),
						borderRadius: Corner.L.toNumber(),
						marginTop: !isFirst && Indent.XS.toNumber(),
					},
					onClick,
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							marginBottom: Indent.L.toNumber(),
						},
					},
					Image({
						style: {
							width: 47,
							height: 47,
							alignSelf: 'center',
						},
						resizeMode: 'cover',
						svg: {
							uri: imageSrc,
						},
					}),
					H4({
						text: title,
						color: Color.base1,
						style: {
							marginHorizontal: Indent.M.toNumber(),
							flex: 2,
						},
						numberOfLines: 2,
						ellipsize: 'end',
					}),
					IconView({
						icon: Icon.CHEVRON_TO_THE_RIGHT,
						size: 32,
					}),
				),
				Text5({
					text: description,
					color: Color.base1,
					style: {
						flexShrink: 2,
					},
				}),
			);
		}

		/**
		 * @param {string} articleId
		 */
		openHelpArticle(articleId)
		{
			helpdesk.openHelpArticle(articleId, 'helpdesk');
		}

		openFlowTab()
		{
			requireLazy('tasks:navigator/meta')
				.then(({
					TASKS_TABS_NAVIGATOR,
					TASKS_TABS,
				}) => {
					this.layout.back();
					setTimeout(() => {
						BX.postComponentEvent(TASKS_TABS_NAVIGATOR.makeActive, [TASKS_TABS.FLOW]);
					}, 100);
				})
				.catch((error) => {
					console.error(error);
				});
		}

		openCopilotTab()
		{
			// eslint-disable-next-line no-undef
			requireLazyBatch(['im:messenger/api/tab', 'im:messenger/api/navigation'], false)
				.then(async (extensions) => {
					const { openCopilotTab } = extensions.get('im:messenger/api/tab');
					const { closeAll } = extensions.get('im:messenger/api/navigation');

					try
					{
						await closeAll?.();
						await openCopilotTab?.();
					}
					catch (error)
					{
						console.error(error);
					}
				})
				.catch((error) => console.error(error));
		}
	}

	const openCopilotFeatures = (availableFeatures) => {
		PageManager.openWidget('layout', {
			titleParams: {
				text: Loc.getMessage('COPILOT_FEATURE_TITLE'),
			},
			onReady: (layout) => {
				layout.showComponent(new CopilotFeatures({
					layout,
					availableFeatures,
				}));
			},
		});
	};

	module.exports = { CopilotFeatures, openCopilotFeatures };
});
