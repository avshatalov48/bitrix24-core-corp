/**
 * @module bizproc/workflow/faces/timeline
 * */
jn.define('bizproc/workflow/faces/timeline', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');
	const {
		formatRoundedTime,
		roundTimeInSeconds,
		roundUpTimeInSeconds,
		calculateSum,
		findElderRank,
	} = require('bizproc/helper/duration');

	class WorkflowFacesTimeline extends PureComponent
	{
		render()
		{
			return View(
				{
					style: { width: 83, height: 100, justifyContent: 'center' },
					testId: 'WorkflowFacesTimeline',
				},
				Image({
					style: { width: 83, height: 100, position: 'absolute' },
					svg: {
						content: rectangle(
							this.props.isCompleted ? AppTheme.colors.accentSoftGreen3 : AppTheme.colors.bgContentPrimary,
						),
					},
				}),
				View(
					{
						style: {
							flexDirection: 'column',
							alignItems: 'center',
							marginLeft: 13,
							marginRight: 4,
						},
					},
					this.renderTitle(),
					this.renderTime(),
					this.renderLink(),
				),
			);
		}

		renderTitle()
		{
			return Text({
				style: {
					fontWeight: '400',
					fontSize: 11,
					color: this.props.isCompleted ? AppTheme.colors.base3 : AppTheme.colors.base4,
				},
				text: (
					this.props.isCompleted
						? Loc.getMessage('BPMOBILE_WORKFLOW_FACES_DURATION_TITLE_FINAL')
						: Loc.getMessage('BPMOBILE_WORKFLOW_FACES_DURATION_TITLE_MSGVER_1')
				),
				numberOfLines: 1,
				ellipsize: 'end',
			});
		}

		renderTime()
		{
			if (!this.props.isCompleted)
			{
				return Image({
					style: { width: 30, height: 30, marginVertical: 8 },
					svg: { content: clock },
				});
			}

			const texts = this.getDurationTexts();

			return View(
				{
					style: {
						marginVertical: 4,
						marginLeft: 2,
						alignItems: 'center',
					},
				},
				texts.before && Text({
					style: {
						fontSize: 10,
						fontWeight: '400',
						color: AppTheme.colors.base0,
					},
					numberOfLines: 1,
					ellipsize: 'end',
					text: texts.before,
				}),
				Text({
					style: {
						fontSize: 24,
						fontWeight: '600',
						color: AppTheme.colors.base0,
					},
					text: texts.number,
				}),
				texts.after && Text({
					style: {
						fontSize: 10,
						fontWeight: '400',
						color: AppTheme.colors.base0,
						marginTop: -3,
					},
					numberOfLines: 1,
					ellipsize: 'end',
					text: texts.after,
				}),
			);
		}

		getDurationTexts()
		{
			const roundedAuthorTime = roundTimeInSeconds(this.props.durations.author);
			const roundedCompletedTime = roundTimeInSeconds(this.props.durations.completed);
			const roundedDoneTime = roundTimeInSeconds(this.props.durations.done);

			const roundedDurations = roundUpTimeInSeconds(
				calculateSum([roundedAuthorTime, roundedCompletedTime, roundedDoneTime]),
			);

			const searchNumber = String(findElderRank(roundedDurations).value);
			const formattedDuration = formatRoundedTime(roundedDurations);
			const index = formattedDuration.indexOf(searchNumber);

			return {
				before: index === -1 ? formattedDuration : formattedDuration.slice(0, index).trim(),
				number: index === -1 ? '' : searchNumber,
				after: index === -1 ? '' : formattedDuration.slice(index + searchNumber.length).trim(),
			};
		}

		renderLink()
		{
			return Text({
				testId: 'WorkflowFacesTimelineText',
				style: {
					fontWeight: '400',
					fontSize: 11,
					color: AppTheme.colors.accentMainLinks,
					marginTop: 1,
				},
				text: Loc.getMessage('BPMOBILE_WORKFLOW_FACES_TIMELINE_MSGVER_1'),
				numberOfLines: 1,
				ellipsize: 'end',
			});
		}
	}

	const rectangle = (backgroundColor) => `
		<svg width="83" height="100" viewBox="0 0 83 100" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path
				d="M0.656109 0.5H77C80.0376 0.5 82.5 2.96243 82.5 6V94C82.5 97.0376 80.0376 99.5 77 99.5H0.667406L14.6634 51.703L14.7033 51.5668L14.6656 51.4299L0.656109 0.5Z"
				fill="${backgroundColor}"
				stroke="${AppTheme.colors.base7}"
			/>
		</svg>
	`;

	const clock = `
		<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path
				fill-rule="evenodd"
				clip-rule="evenodd"
				d="M5.91496 19.085C7.58204 22.7922 11.3345 25.1144 15.396 24.9525C20.7842 24.8422 25.0645 20.3877 24.9597 14.9993C24.9595 10.9346 22.4894 7.27777 18.7187 5.75992C14.9479 4.24206 10.6332 5.16771 7.81694 8.09869C5.00065 11.0297 4.24788 15.3779 5.91496 19.085ZM8.23367 18.0428C9.47526 20.8037 12.2699 22.5333 15.2948 22.4127C19.3078 22.3305 22.4956 19.0129 22.4176 14.9999C22.4174 11.9726 20.5777 9.24911 17.7694 8.11866C14.9611 6.98821 11.7477 7.6776 9.6502 9.8605C7.55272 12.0434 6.99208 15.2818 8.23367 18.0428ZM13.7396 9.98535H16.2396V13.7354H19.9896V16.2354H16.2396H13.7396V9.98535Z"
				fill="${AppTheme.colors.base6}"
			/>
		</svg>
	`;

	module.exports = { WorkflowFacesTimeline };
});
