<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

CBitrixComponent::includeComponentClass('bitrix:imconnector.facebook');

class ImConnectorFacebookComments extends \ImConnectorFacebook
{
	protected $connector = 'facebookcomments';

	protected $pageId = 'page_fbcomm';
};
