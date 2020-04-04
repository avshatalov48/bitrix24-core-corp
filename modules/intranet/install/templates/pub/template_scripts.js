;(function(){
	if (
		location.href.indexOf('#') < 0
		&& location.href.indexOf('commentId=') >= 0
	)
	{
		var urlCommentId = decodeURIComponent((new RegExp('[?|&]' + 'commentId=' + '([^&;]+?)(&|#|;|$)').exec(location.search) || [null, ''])[1].replace(/\+/g, '%20')) || null;
		if (!!urlCommentId)
		{
			location.hash = '#com' + urlCommentId;
		}
	}
})();
