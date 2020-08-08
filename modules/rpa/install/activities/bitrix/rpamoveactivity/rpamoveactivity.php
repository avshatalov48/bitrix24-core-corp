<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$runtime = CBPRuntime::GetRuntime()->IncludeActivityFile('RpaReviewActivity');

class CBPRpaMoveActivity extends CBPRpaReviewActivity
{
}