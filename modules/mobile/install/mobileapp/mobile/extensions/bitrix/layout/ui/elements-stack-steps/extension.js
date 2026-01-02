/**
 * @module layout/ui/elements-stack-steps
 */
jn.define('layout/ui/elements-stack-steps', (require, exports, module) => {
	const { ElementsStackSteps } = require('layout/ui/elements-stack-steps/elements-stack-steps');
	const { Step } = require('layout/ui/elements-stack-steps/step');
	const { Stack } = require('layout/ui/elements-stack-steps/stack');
	const { Stick } = require('layout/ui/elements-stack-steps/stick');
	const { Text } = require('layout/ui/elements-stack-steps/block/text');
	const { TextStub } = require('layout/ui/elements-stack-steps/block/text-stub');
	const { Duration } = require('layout/ui/elements-stack-steps/block/duration');
	const { Avatar } = require('layout/ui/elements-stack-steps/block/avatar');
	const { AvatarStub } = require('layout/ui/elements-stack-steps/block/avatar-stub');
	const { Counter } = require('layout/ui/elements-stack-steps/block/counter');

	module.exports = {
		ElementsStackSteps,
		Step,
		Stack,
		Text,
		TextStub,
		Duration,
		Avatar,
		AvatarStub,
		Counter,
		Stick,
	};
});
