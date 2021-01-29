import {Label} from "./label";

const TileLabel = {
	props: {
		name: {
			type: String,
			required: true
		}
	},
	components:
		{
			'tile-label-block'	:	Label
		},

	methods:
	{
		onClick()
		{
			this.$emit('tile-label-on-click');
		},
	},
	template: `<div @click="onClick()">
					<tile-label-block :name="name"/>
				</div>`
};
export {
	TileLabel
}