/**
 * @module bizproc/workflow/faces/timeline
 * */
jn.define('bizproc/workflow/faces/timeline', (require, exports, module) => {
	const { WorkflowFacesColumn } = require('bizproc/workflow/faces/column-view');
	const { Color } = require('tokens');
	const { Text, Stack } = require('layout/ui/elements-stack-steps');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Loc } = require('loc');
	const { formatRoundedTime, roundUpTimeInSeconds, findElderRank } = require('bizproc/helper/duration');
	const { Text7 } = require('ui-system/typography/text');
	const { H2 } = require('ui-system/typography/heading');

	class WorkflowFacesTimeline extends WorkflowFacesColumn
	{
		renderPhotos()
		{
			if (this.props.isCompleted)
			{
				return this.#renderTotalTime();
			}

			return Stack({}, this.#renderClock());
		}

		#renderTotalTime()
		{
			const texts = this.getDurationTexts();

			return View(
				{ style: { alignItems: 'center', marginTop: -2 } },
				texts.before && this.#renderDurationText(texts.before),
				H2({ text: texts.number, style: { color: Color.base1.toHex() } }),
				texts.after && this.#renderDurationText(texts.after),
			);
		}

		#renderDurationText(text)
		{
			return Text7({
				text,
				style: { color: Color.base1.toHex() },
				numberOfLines: 1,
				ellipsize: 'end',
			});
		}

		#renderClock()
		{
			return View(
				{
					style: {
						width: 32,
						height: 32,
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				IconView({ icon: Icon.CLOCK, color: Color.base6, size: 30 }),
			);
		}

		renderTime()
		{
			return Text({
				text: Loc.getMessage('BPMOBILE_WORKFLOW_FACES_TIMELINE_MSGVER_1'),
				textColor: Color.accentMainLink,
			});
		}

		getDurationTexts()
		{
			const roundedDurations = roundUpTimeInSeconds(this.props.duration);

			const searchNumber = String(findElderRank(roundedDurations).value);
			const formattedDuration = formatRoundedTime(roundedDurations);
			const index = formattedDuration.indexOf(searchNumber);

			return {
				before: index === -1 ? formattedDuration : formattedDuration.slice(0, index).trim(),
				number: index === -1 ? '' : searchNumber,
				after: index === -1 ? '' : formattedDuration.slice(index + searchNumber.length).trim(),
			};
		}
	}

	module.exports = { WorkflowFacesTimeline };
});
