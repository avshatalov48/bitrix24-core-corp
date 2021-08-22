(function(){
	this.UrlRewriter = {
		get: originalUrl=>{
			for (let i = 0; i < rewriteRules.length; i++)
			{
				let rule = rewriteRules[i]
				let mobileLink = originalUrl.replace(rule.exp, rule.replace);
				if (mobileLink !== originalUrl) {
					return mobileLink;
				}
			}

			return originalUrl;
		}
	}
})();