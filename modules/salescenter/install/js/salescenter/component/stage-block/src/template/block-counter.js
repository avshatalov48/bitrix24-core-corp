import {Counter} 	from "../counter";

const BlockCounter = {

	components: {
		'block-counter'	: 	Counter
	},
	template: `
		<div>
			<block-counter/>
			<slot name="block-container"></slot>
		</div>
	`
};

export {
	BlockCounter
}