<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Helpers;

use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Request;
use SproutModules\Karmabunny\Satis\Models\Site;

trait ControllerAuthTrait
{
    /**
    * Check if an auth has been attempted.
    *
    * This doesn't imply the auth is _valid_, just that it exists.
    *
    * @return bool
    */
   private function hasAuth()
   {
       if (AdminAuth::isLoggedIn()) {
           return true;
       }

       if (Request::getAuthorization('basic')) {
           return true;
       }

       return false;
   }


   /**
    * Check if the auth is valid.
    *
    * @return bool
    */
   private function checkAuth()
   {
       if (AdminAuth::isLoggedIn()) {
           return true;
       }

       $basic = Request::getAuthorization('basic');

       if (!$basic) {
           return false;
       }

       [$user, $pass] = explode(':', base64_decode($basic), 2) + [null, null];

       $log = AuthLog::create($user, $pass);

       $token = Site::find()
           ->where(['name' => $user])
           ->value('token', false);

       if ($token and $token === $pass) {
           $log->success();
           return true;
       }

       $log->error($token ? 'Invalid token' : 'Unknown user');
       return false;
   }
}