/**
 * @module bizproc/workflow/faces
 * */

jn.define('bizproc/workflow/faces', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');
	const AppTheme = require('apptheme');
	const { AvatarStack } = require('layout/ui/user/avatar-stack');

	class WorkflowFaces extends PureComponent
	{
		get faces()
		{
			return this.props.faces;
		}

		render()
		{
			return View(
				{ style: styles.wrapper },
				View(
					{
						style: styles.facesWrapper,
					},
					this.renderStick(),
					this.renderFirstColumn(),
					this.renderSecondColumn() ?? this.renderEmptyColumn(),
					this.renderThirdColumn() ?? this.renderEmptyColumn(),
				),
				this.renderTimelineLink(),
			);
		}

		renderFirstColumn()
		{
			return View(
				{
					style: {
						...styles.facesColumn,
						...styles.facesColumnFirst,
					},
				},
				Text(
					{
						text: Loc.getMessage('BPMOBILE_WORKFLOW_FACES_AUTHOR'),
						style: styles.facesLabel,
					},
				),
				this.renderPhotos([this.faces.author]),
			);
		}

		renderSecondColumn()
		{
			if (this.faces.completed.length === 0)
			{
				return this.renderRunningColumn();
			}

			return View(
				{ style: styles.facesColumn },
				Text(
					{
						text: Loc.getMessage('BPMOBILE_WORKFLOW_FACES_COMPLETED'),
						style: styles.facesLabel,
					},
				),
				this.renderPhotos(
					this.faces.completed,
					this.faces.completedSuccess ? 'accept' : 'decline',
				),
			);
		}

		renderThirdColumn()
		{
			if (this.faces.completed.length === 0)
			{
				return null;
			}

			return this.renderRunningColumn();
		}

		renderRunningColumn()
		{
			let faces = this.faces.running;
			let status = 'progress';
			let title = Loc.getMessage('BPMOBILE_WORKFLOW_FACES_RUNNING');

			if (faces.length === 0 && this.faces.workflowIsCompleted && this.faces.completed.length > 0)
			{
				faces = [this.faces.completed.at(-1)];
				status = this.faces.completedSuccess ? 'accept' : 'decline';
				title = Loc.getMessage('BPMOBILE_WORKFLOW_FACES_COMPLETED_DONE');
			}

			if (faces.length === 0)
			{
				return null;
			}

			return View(
				{ style: styles.facesColumn },
				Text(
					{
						text: title,
						style: styles.facesLabel,
					},
				),
				this.renderPhotos(faces, status),
			);
		}

		renderStatus(status)
		{
			return View(
				{
					style: {
						marginLeft: -12,
						marginTop: -4,
					},
				},
				Image({
					style: {
						width: 18,
						height: 18,
						alignSelf: 'center',
					},
					svg: {
						content: icons[status],
					},
				}),
			);
		}

		renderTimelineLink()
		{
			return View(
				{
					style: styles.timeline,
					testId: `${this.testId}_TIMELINE`,
				},
				Text(
					{
						testId: `${this.testId}_TIMELINE_TEXT`,
						text: Loc.getMessage('BPMOBILE_WORKFLOW_FACES_TIMELINE'),
						style: styles.timelineText,
					},
				),
			);
		}

		renderPhotos(avatars, status)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						paddingTop: 4,
						paddingBottom: 2,
					},
				},
				new AvatarStack({
					avatars,
					reverse: false,
					styles: {
						avatar: {
							borderWidth: 1,
							borderColor: AppTheme.colors.bgContentPrimary,
						},
					},
				}),
				status && this.renderStatus(status),
			);
		}

		renderEmptyColumn()
		{
			return View(
				{
					testId: `${this.testId}_EMPTY_BLOCK`,
					style: styles.facesColumn,
				},
				View({ style: styles.emptyFacesTitle }),
				View({ style: styles.emptyFacesPhoto }),
			);
		}

		renderStick()
		{
			return View({ style: styles.facesStick });
		}
	}

	const styles = {
		wrapper: {
			flexDirection: 'row',
			alignItems: 'stretch',
			paddingTop: 4,
			paddingBottom: 16,
		},
		facesWrapper: {
			flex: 1,
			flexDirection: 'row',
			alignItems: 'flex-start',
			// justifyContent: 'space-between',
		},
		facesColumn: {
			flexDirection: 'column',
			width: 66,
			alignItems: 'center',
			marginLeft: 24,
			// borderColor: '#000000',
			// borderWidth: 1,
		},
		facesColumnFirst: {
			width: 60,
			// borderColor: '#0000FF',
			marginLeft: 0,
		},
		facesLabel: {
			fontWeight: '400',
			fontSize: 11,
			color: AppTheme.colors.base4,
			marginBottom: 4,
		},
		timeline: {
			width: 68,
			flexDirection: 'column',
			alignItems: 'flex-end',
		},
		timelineText: {
			marginTop: 28,
			fontSize: 12,
			fontWeight: '400',
			color: AppTheme.colors.base4,
		},
		emptyFacesTitle: {
			borderRadius: 4,
			backgroundColor: AppTheme.colors.base7,
			width: 44,
			height: 7,
			marginBottom: 10,
			marginTop: 4,
		},
		emptyFacesPhoto: {
			width: 30,
			height: 30,
			backgroundColor: AppTheme.colors.base7,
			borderRadius: 15,
		},
		facesStick: {
			height: 1,
			position: 'absolute',
			top: 36,
			left: 30,
			width: 165,
			// right: 30,
			backgroundColor: AppTheme.colors.base6,
		},
	};

	const icons = {
		accept: (() => {
			const fillBack = AppTheme.colors.base8;
			const fillMiddle = AppTheme.colors.baseWhiteFixed;
			const fillFore = AppTheme.colors.accentMainSuccess;

			return `
				<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
					<g clip-path="url(#clip0_1651_44333)">
					<circle cx="9" cy="9" r="9" fill="${fillBack}"/>
					<circle cx="9" cy="9" r="7" fill="${fillMiddle}"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M17 9C17 13.4183 13.4183 17 9 17C4.58172 17 1 13.4183 1 9C1 4.58172 4.58172 1 9 1C13.4183 1 17 4.58172 17 9ZM7.73785 9.87379L5.88808 7.95795L4.43469 9.80772L7.73785 13.1109L13.9836 6.86519L12.3623 5.18329L7.73785 9.87379Z" fill="${fillFore}"/>
					</g>
					<defs>
					<clipPath id="clip0_1651_44333">
					<rect width="18" height="18" fill="${fillBack}"/>
					</clipPath>
					</defs>
				</svg>
			`;
		})(),
		decline: (() => {
			const fillBack = AppTheme.colors.base8;
			const fillMiddle = AppTheme.colors.baseWhiteFixed;
			const fillFore = AppTheme.colors.base5;

			return `
				<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="9" cy="9" r="9" fill="${fillBack}"/>
					<circle cx="9" cy="9" r="8" fill="${fillFore}"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M10.1127 9L13 11.8873L11.8873 13L9 10.1127L6.11272 13L5 11.8873L7.88728 9L5 6.11272L6.11272 5L9 7.88728L11.8873 5L13 6.11272L10.1127 9Z" fill="${fillMiddle}"/>
				</svg>
			`;
		})(),
		progress: (() => {
			const fillBack = AppTheme.colors.base8;
			const fillMiddle = AppTheme.colors.baseWhiteFixed;
			const fillFore = AppTheme.colors.accentMainPrimary;

			return `
				<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="9" cy="9" r="9" fill="${fillBack}"/>
					<circle cx="9" cy="9" r="6" fill="${fillMiddle}"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M9 17C13.4183 17 17 13.4183 17 9C17 4.58172 13.4183 1 9 1C4.58172 1 1 4.58172 1 9C1 13.4183 4.58172 17 9 17ZM9.99015 4.04879H8.00983V8.00941V8.99957V9.98972H12.9606V8.00941H9.99015V4.04879Z" fill="${fillFore}"/>
				</svg>
			`;
		})(),
	};

	module.exports = { WorkflowFaces };
});
