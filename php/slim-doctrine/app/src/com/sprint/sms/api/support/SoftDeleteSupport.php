<?php
namespace App\com\sprint\sms\api\support;

interface SoftDeleteSupport
{
    public function getDeleted();

	public function setDeleted(boolean $deleted);
	
}