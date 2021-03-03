<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\GroupDefaultQuota\Tests;

use OCA\GroupDefaultQuota\QuotaManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use Test\TestCase;

class QuotaManagerTest extends TestCase {
	private $appConfigData = [];
	private $userConfigData = [];
	/** @var IConfig */
	private $config;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->config->method('deleteAppValue')
			->willReturnCallback(function ($app, $key) {
				if (isset($this->appConfigData[$app])) {
					unset($this->appConfigData[$app][$key]);
				}
			});
		$this->config->method('setAppValue')
			->willReturnCallback(function ($app, $key, $value) {
				if (!isset($this->appConfigData[$app])) {
					$this->appConfigData[$app] = [];
				}
				$this->appConfigData[$app][$key] = $value;
			});
		$this->config->method('getAppValue')
			->willReturnCallback(function ($app, $key, $default) {
				if (!isset($this->appConfigData[$app])) {
					$this->appConfigData[$app] = [];
				}
				if (isset($this->appConfigData[$app][$key])) {
					return $this->appConfigData[$app][$key];
				} else {
					return $default;
				}
			});
		$this->config->method('getUserValue')
			->willReturnCallback(function ($uid, $app, $key, $default) {
				if (!isset($this->userConfigData[$uid])) {
					$this->userConfigData[$uid] = [];
				}
				if (!isset($this->userConfigData[$uid][$app])) {
					$this->userConfigData[$uid][$app] = [];
				}
				if (isset($this->userConfigData[$uid][$app][$key])) {
					return $this->userConfigData[$uid][$app][$key];
				} else {
					return $default;
				}
			});
	}

	private function getGroupManager($users): IGroupManager {
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('getUserGroupIds')
			->willReturnCallback(function (IUser $user) use ($users) {
				if (isset($users[$user->getUID()])) {
					return $users[$user->getUID()];
				} else {
					return [];
				}
			});
		return $groupManager;
	}

	private function user(string $uid): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn($uid);
		return $user;
	}

	public function testUserQuotaNoGroups() {
		$manager = new QuotaManager($this->config, $this->getGroupManager([]));

		$this->assertEquals('default', $manager->getDefaultQuotaForUser($this->user('test_user')));
	}

	public function testUserQuotaNoGroupsWithQuota() {
		$manager = new QuotaManager($this->config, $this->getGroupManager(['test_user' => ['group1']]));

		$this->assertEquals('default', $manager->getDefaultQuotaForUser($this->user('test_user')));
	}

	public function testUserQuotaWithQuotas() {
		$manager = new QuotaManager($this->config, $this->getGroupManager(['test_user' => ['group1']]));
		$manager->setGroupDefault('group1', '10 GB');

		$this->assertEquals('10 GB', $manager->getDefaultQuotaForUser($this->user('test_user')));
	}

	public function testUserQuotaWithMultipleQuotas() {
		$manager = new QuotaManager($this->config, $this->getGroupManager(['test_user' => ['group1', 'group2']]));
		$manager->setGroupDefault('group1', '10 GB');
		$manager->setGroupDefault('group2', '5 GB');

		$this->assertEquals('10 GB', $manager->getDefaultQuotaForUser($this->user('test_user')));
	}

	public function testUserQuotaWithUserQuota() {
		$manager = new QuotaManager($this->config, $this->getGroupManager(['test_user' => ['group1']]));
		$manager->setGroupDefault('group1', '10 GB');
		$this->userConfigData['test_user']['files']['quota'] = '100 MB';

		$this->assertEquals('100 MB', $manager->getDefaultQuotaForUser($this->user('test_user')));
	}
}
