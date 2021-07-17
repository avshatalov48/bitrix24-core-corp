let MixinTemplatesType = {
	data() {
		return {
			editable: true
		};
	},
	created(){
		this.$root.$on("on-change-editable", (value) => {
			this.editable = value;
		});
	}
}

export {
	MixinTemplatesType
}