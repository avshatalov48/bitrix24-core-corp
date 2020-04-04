<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?php
/** @var CAllMain $APPLICATION */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<?php echo CJSCore::GetHTML(array('ajax', 'viewer')); ?>
		<?//$APPLICATION->ShowHead();?>
		<style>
		</style>
	</head>
	<body style="height: 100%;margin: 0;padding: 0;">
		<script type="text/javascript">
			function closeConfirm()
			{
				if (typeof(BX.PULL) != 'undefined' && typeof(BX.PULL.tryConnectDelay) == 'function') // TODO change to right code in near future (e.shelenkov)
				{
					BX.PULL.tryConnectDelay();
				}
				if(window.opener)
				{
					if((BX.browser.IsIE() || BX.browser.IsIE11()) && window.opener._ie_elementViewer && (window.opener._ie_elementViewer.bVisible || window.opener._ie_elementViewer.createDoc))
					{
						window.opener._ie_elementViewer.closeConfirm();
						window.opener.BX.CViewer.unlockScroll();
						return true;
					}
					else if(window.elementViewer && (window.elementViewer.bVisible || window.elementViewer.createDoc))
					{
						window.elementViewer.closeConfirm();
						window.opener.BX.CViewer.unlockScroll();
						return true;
					}
				}
				return false;
			}

			BX.ready(function(){
				window.successLoadCommitData = false;
				window.runAuthAction = false;
				window.onbeforeunload = function(e)
				{
					try{
						if(!window.successLoadCommitData && !window.runAuthAction)
						{
							closeConfirm();
						}
					} catch(e){}
				}
				BX.ajax({
					'method': 'POST',
					'dataType': 'json',
					'url': '<?= CUtil::JSEscape($APPLICATION->GetCurUri('proccess=1')) ?>',
					'data':  {
					},
					'onsuccess': function(data){
						if(data.error)
						{
							BX.hide(BX('loader'));
							BX.adjust(BX('error'), {style: {display: 'table'}});
							BX.adjust(BX('error-text'), {text: data.error});
							closeConfirm();

							return;
						}
						if(data.authUrl)
						{
							window.runAuthAction = true;
							window.location.href = data.authUrl;
						}
						else if(data.iframeSrc)
						{
							window.successLoadCommitData = true;
							if(window.opener)
							{
								if(
									(BX.browser.IsIE() || BX.browser.IsIE11()) &&
									window.opener._ie_elementViewer &&
									(window.opener._ie_elementViewer.createDoc || window.opener._ie_elementViewer.bVisible && window.opener._ie_elementViewer.isCurrent(window.opener.window._ie_currentElement)))
								{
									var iframeSrc = data.iframeSrc;
									var uriToDoc = data.uriToDoc;
									var idDoc = data.idDoc;
									window.opener._ie_currentElement.setDataForCommit(
										iframeSrc,
										uriToDoc,
										idDoc
									);
								}
								else if(window.elementViewer && window.elementViewer.bVisible)
								{
									if(window.elementViewer.isCurrent(window.currentElement))
									{
										window.currentElement.setDataForCommit(data);
									}
								}
								else if(window.opener._ie_elementViewer && window.opener._ie_elementViewer.bVisible)
								{
									if(window.opener._ie_elementViewer.isCurrent(window.opener.window._ie_currentElement))
									{
										window.opener._ie_currentElement.setDataForCommit(data);
									}
								}
								else if(window.elementViewer && window.elementViewer.createDoc)
								{
										window.currentElement.setDataForCommit(data);
								}
								else if(window.opener._ie_elementViewer && window.opener._ie_elementViewer.createDoc)
								{
									window.opener._ie_currentElement.setDataForCommit(data);
								}
							}

							window.location.href = data.iframeSrc;
						}
					}
				});
			});
		</script>
		<div id="loader" style="display: table;width:  100%;height: 100%; margin-top: 25%;">
			<div style="display: table-cell; vertical-align: middle;text-align: center;"><div class="bx-viewer-wrap-loading-white"></div><?= GetMessage('WD_DOC_EDIT_UPLOAD_DOC_TO_GOOGLE'); ?></div>
		</div>
		<div id="error" style="display: none;width:  100%;height: 100%; margin-top: 25%;">
			<div style="display: table-cell; vertical-align: middle;text-align: center;"><span id="error-text"></span></div>
		</div>
<!--		<div id="error" style="vertical-align: middle;text-align: center;"></div>-->
	</body>
</html>

