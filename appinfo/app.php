<?php

use OCP\User\GetQuotaEvent;
use \OCA\GroupDefaultQuota\AppInfo\Application;

if (class_exists(GetQuotaEvent::class)) {
	/** @var Application $application */
	$application = \OC::$server->query(Application::class);
	$application->register();
}
