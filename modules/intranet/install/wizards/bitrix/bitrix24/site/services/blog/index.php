<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("blog"))
	return;

COption::SetOptionString("blog", "use_image_perm", "Y");
	
$SocNetGroupID = false; 
$db_blog_group = CBlogGroup::GetList(array("ID" => "ASC"), array("SITE_ID" => WIZARD_SITE_ID, "NAME" => "[".WIZARD_SITE_ID."] ".GetMessage("BLOG_DEMO_GROUP_SOCNET")));
if ($res_blog_group = $db_blog_group->Fetch())
{
	$SocNetGroupID = $res_blog_group["ID"];
}

COption::SetOptionString('blog','avatar_max_size','30000');
COption::SetOptionString('blog','avatar_max_width','100');
COption::SetOptionString('blog','avatar_max_height','100');
COption::SetOptionString('blog','image_max_width','800');
COption::SetOptionString('blog','image_max_height','600');
COption::SetOptionString('blog','allow_alias','Y');
COption::SetOptionString('blog','block_url_change','Y');
COption::SetOptionString('blog','GROUP_DEFAULT_RIGHT','D');
COption::SetOptionString('blog','show_ip','N');
COption::SetOptionString('blog','enable_trackback','N');
COption::SetOptionString('blog','allow_html','N');

$APPLICATION->SetGroupRight("blog", WIZARD_PORTAL_ADMINISTRATION_GROUP, "W");
COption::SetOptionString("blog", "GROUP_DEFAULT_RIGHT", "D");

CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."company/personal/user/#user_id#/blog/", "TYPE" => "B"));
CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."company/personal/user/#user_id#/blog/#post_id#/", "TYPE" => "P"));
CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."company/personal/user/#user_id#/", "TYPE" => "U"));
CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."workgroup/group/#group_id#/blog/", "TYPE" => "G"));
CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."workgroup/group/#group_id#/blog/#post_id#/", "TYPE" => "H"));	

if (!$SocNetGroupID)
	$SocNetGroupID = CBlogGroup::Add(array(
		"SITE_ID" => WIZARD_SITE_ID, 
		"NAME" => "[".WIZARD_SITE_ID."] " . GetMessage("BLOG_DEMO_GROUP_SOCNET")));


$rsUser = CUser::GetByID(1);
$arUser = $rsUser->Fetch();

$blogID = CBlog::Add(
		Array(
			"NAME" =>  GetMessage("BLG_NAME")." ".CUser::FormatName(CSite::GetNameFormat(false), $arUser),
			"DESCRIPTION" => "",
			"GROUP_ID" => $SocNetGroupID,
			"ENABLE_IMG_VERIF" => 'Y',
			"EMAIL_NOTIFY" => 'Y',
			"USE_SOCNET" => 'Y',
			"ENABLE_RSS" => "Y",
			"ALLOW_HTML" => "Y",
			"URL" => str_replace(" ", "_", $arUser["ID"])."-blog-".WIZARD_SITE_ID,
			"ACTIVE" => "Y",
			"=DATE_CREATE" => $DB->GetNowFunction(),
			"=DATE_UPDATE" => $DB->GetNowFunction(),
			"OWNER_ID" => 1,
			"PERMS_POST" => Array("1" => BLOG_PERMS_READ, "2" => BLOG_PERMS_READ), 
			"PERMS_COMMENT" => array("1" => BLOG_PERMS_WRITE , "2" => BLOG_PERMS_WRITE),
		)
	);

CBlog::AddSocnetRead($blogID);

$categoryID[] = CBlogCategory::Add(Array("BLOG_ID" => $blogID, "NAME" => GetMessage("BLOG_DEMO_CATEGORY_1")));
$categoryID[] = CBlogCategory::Add(Array("BLOG_ID" => $blogID, "NAME" => GetMessage("BLOG_DEMO_CATEGORY_2")));

//posts
$arBlogPosts = array(
	array(
		"TITLE" => GetMessage("BLOG_DEMO_MESSAGE_TITLE4"),
		"DETAIL_TEXT" => GetMessage("BLOG_DEMO_MESSAGE_BODY4"),
		"DETAIL_TEXT_TYPE" => "text",
		"BLOG_ID" => $blogID,
		"AUTHOR_ID" => 1,
		"VIEWS" => 5,
		"=DATE_CREATE" => $DB->GetNowFunction(),
		"=DATE_PUBLISH" => $DB->GetNowFunction(),
		"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
		"ENABLE_TRACKBACK" => 'N',
		"ENABLE_COMMENTS" => 'Y',
		"CATEGORY_ID" =>  implode(",", $categoryID),
		"PERMS_POST" => array(),
		"PERMS_COMMENT" => array(),
		"SOCNET_RIGHTS" => array()
	)
);

foreach($arBlogPosts as $arBlogPostFields)
{
	$postID = CBlogPost::Add($arBlogPostFields);

	foreach($categoryID as $v)
		CBlogPostCategory::Add(Array("BLOG_ID" => $blogID, "POST_ID" => $postID, "CATEGORY_ID"=>$v));

	$arAddVote = array(
		'ENTITY_TYPE_ID'  =>  'BLOG_POST',
		'ENTITY_ID'       =>  $postID,
		'VALUE'           =>  1,
		'USER_ID'         =>  1,
		'USER_IP'         =>  'demo'
	);
	CRatings::AddRatingVote($arAddVote);

	if (CModule::IncludeModule("socialnetwork"))
	{
		$parserBlog = new blogTextParser(false, "/bitrix/images/socialnetwork/smile/");
		$arAllow = array("HTML" => "N", "ANCHOR" => "N", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "N", "VIDEO" => "N");
		$text4message = $parserBlog->convert($arBlogPostFields["DETAIL_TEXT"], true, array(), $arAllow);

		$arSoFields = Array(
			"EVENT_ID" => "blog_post",
			"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"TITLE_TEMPLATE" => "#USER_NAME# ".GetMessage("BPC_SONET_POST_TITLE"),
			"TITLE" => $arBlogPostFields["TITLE"],
			"MESSAGE" => $text4message,
			"TEXT_MESSAGE" => $arBlogPostFields["DETAIL_TEXT"],
			"MODULE_ID" => "blog",
			"CALLBACK_FUNC" => false,
			"ENTITY_TYPE" => "U",
			"ENTITY_ID" => 1,
			"URL" => CComponentEngine::MakePathFromTemplate(WIZARD_SITE_DIR."company/personal/user/#user_id#/blog/#post_id#/", array("user_id" => 1, "post_id" => $postID)),
			"USER_ID" => 1,
			"SITE_ID" => WIZARD_SITE_ID,
			"SOURCE_ID" => $postID,
			"ENABLE_COMMENTS" => "Y",
			"RATING_TYPE_ID" => "BLOG_POST",
			"RATING_ENTITY_ID" => $postID
		);
		if ($post = \Bitrix\Blog\Item\Post::getById($postID))
		{
			$arSoFields["TAG"] = $post->getTags();
		}

		$logID = CSocNetLog::Add($arSoFields, false);

		if (intval($logID) > 0)
		{
			CSocNetLog::Update($logID, array("TMP_ID" => $logID));
//		CSocNetLogRights::SetForSonet($logID, $arSoFields["ENTITY_TYPE"], $arSoFields["ENTITY_ID"], "blog", "view_post", true);
			CSocNetLogRights::Add($logID, array("G2", "U1"));
		}
	}
}

//comments
/*$arBlogCommentFields = Array(
	"TITLE" => GetMessage("BLOG_DEMO_COMMENT_TITLE"),
	"POST_TEXT" => GetMessage("BLOG_DEMO_COMMENT_BODY"),
	"BLOG_ID" => $blogID,
	"POST_ID" => $postID,
	"PARENT_ID" => 0,
	"AUTHOR_ID" => 1,
	"DATE_CREATE" => ConvertTimeStamp(false, "FULL"), 
	"AUTHOR_IP" => "192.168.0.108",
);
	
$commmentId = CBlogComment::Add($arBlogCommentFields);

if (CModule::IncludeModule("socialnetwork"))
{
	$arAllow = array("HTML" => "N", "ANCHOR" => "N", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "N", "VIDEO" => "N");
	$text4message = $parserBlog->convert($arBlogCommentFields["POST_TEXT"], false, array(), $arAllow);
	$text4mail = $parserBlog->convert4mail($arBlogCommentFields["POST_TEXT"]);

	$arBlogUser = CBlogUser::GetByID(1, BLOG_BY_USER_ID); 
	$arBlogUser = CBlogTools::htmlspecialcharsExArray($arBlogUser);	
	
	$AuthorName = CBlogUser::GetUserName($arBlogUser["~ALIAS"], $arUser["~NAME"], $arUser["~LAST_NAME"], $arUser["~LOGIN"], $arUser["~SECOND_NAME"]); 
	
	$commentUrl = CComponentEngine::MakePathFromTemplate(
		WIZARD_SITE_DIR."company/personal/user/#user_id#/blog/#post_id#/", 
		array(
			"post_id"=> $postID,
			"user_id" => 1
		)
	);
				
	if(strpos($commentUrl, "?") !== false)
		$commentUrl .= "&";
	else
		$commentUrl .= "?";
	$commentUrl .= "commentId=".$commmentId."#".$commmentId;

	$arSoFields = array(
		"ENTITY_TYPE" => "U",
		"ENTITY_ID" => 1,
		"EVENT_ID" => "blog_comment",
		"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
		"MESSAGE" => $text4message,
		"TEXT_MESSAGE" => $text4mail,
		"URL" => $commentUrl,
		"MODULE_ID" => false,
		"SOURCE_ID" => $commmentId,
		"USER_ID" => 1,
		"LOG_ID" => $logID,
		"RATING_TYPE_ID" => "BLOG_COMMENT",
		"RATING_ENTITY_ID" => intval($commmentId)
	);

	CSocNetLogComments::Add($arSoFields);
}*/
?>