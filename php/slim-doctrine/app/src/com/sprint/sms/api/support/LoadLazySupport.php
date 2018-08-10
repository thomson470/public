<?php
namespace App\com\sprint\sms\api\support;

interface LoadLazySupport
{
    public function loadLazy();
	
	public function isLazyLoaded();
	
}