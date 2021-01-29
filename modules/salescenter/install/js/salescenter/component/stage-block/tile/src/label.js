const Label = {
	props: {
		name: {
			type: String,
			required: true
		}
	},
	template: `
			<span>{{name}}</span>
`

};
export {
	Label
}