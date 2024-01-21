/**
* @bxjs_lang_path extension.php
*/
(() => {
	this.ListHolder = {
		Loading: { type: 'loading', title: BX.message('HOLDER_LOADING'), unselectable: true, params: { code: 'skip_handle' } },
		MoreButton: { type: 'button', title: BX.message('HOLDER_LOAD_MORE') },
		EmptyResult: { type: 'button', title: BX.message('HOLDER_EMPTY_RESULT'), params: { code: 'skip_handle' }, unselectable: true },
	};
})();
