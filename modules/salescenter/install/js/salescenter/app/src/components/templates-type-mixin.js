const MixinTemplatesType = {
	data() {
		return {
			editable: true,
		};
	},
	created() {
		// TODO: this code is really weird; the only place this event is emitted in is in the products block;
		// if there's no products block on the page, the entire slider breaks
		// perhaps a little refactoring is due
		this.$root.$on('on-change-editable', (value) => {
			this.editable = value;
		});
	},
};

export {
	MixinTemplatesType,
};
