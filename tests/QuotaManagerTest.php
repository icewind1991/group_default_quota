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
use OCP\Config\IUserConfig;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class QuotaManagerTest extends TestCase {
	private array $appConfigData = [];
	private array $userConfigData = [];
	private IAppConfig&MockObject $appConfig;
	private IUserConfig&MockObject $userConfig;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->method('getKeys')
			->willReturnCallback(function ($app) {
				if (isset($this->appConfigData[$app])) {
					return array_keys($this->appConfigData[$app]);
				} else {
					return [];
				}
			});
		$this->appConfig->method('deleteKey')
			->willReturnCallback(function ($app, $key) {
				if (isset($this->appConfigData[$app])) {
					unset($this->appConfigData[$app][$key]);
				}
			});
		$this->appConfig->method('setValueString')
			->willReturnCallback(function ($app, $key, $value) {
				$this->assertLessThan(64, strlen($key)); // AppConfig::KEY_MAX_LENGTH
				if (!isset($this->appConfigData[$app])) {
					$this->appConfigData[$app] = [];
				}
				$this->appConfigData[$app][$key] = $value;
				return true;
			});
		$this->appConfig->method('getValueString')
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
		$this->userConfig = $this->createMock(IUserConfig::class);
		$this->userConfig->method('getValueString')
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

	private function getQuotaManager(array $groups): QuotaManager {
		return new QuotaManager($this->appConfig, $this->userConfig, $this->getGroupManager($groups));
	}

	public function testUserQuotaNoGroups() {
		$manager = $this->getQuotaManager([]);

		$this->assertEquals('default', $manager->getDefaultQuotaForUser($this->user('test_user')));
	}

	public function testUserQuotaNoGroupsWithQuota() {
		$manager = $this->getQuotaManager(['test_user' => ['group1']]);

		$this->assertEquals('default', $manager->getDefaultQuotaForUser($this->user('test_user')));
	}

	public function testUserQuotaWithQuotas() {
		$manager = $this->getQuotaManager(['test_user' => ['group1']]);
		$manager->setGroupDefault('group1', '10 GB');

		$this->assertEquals('10 GB', $manager->getDefaultQuotaForUser($this->user('test_user')));
	}

	public function testUserQuotaWithMultipleQuotas() {
		$manager = $this->getQuotaManager(['test_user' => ['group1', 'group2']]);
		$manager->setGroupDefault('group1', '10 GB');
		$manager->setGroupDefault('group2', '5 GB');

		$this->assertEquals('10 GB', $manager->getDefaultQuotaForUser($this->user('test_user')));
	}

	public function testUserQuotaWithUserQuota() {
		$manager = $this->getQuotaManager(['test_user' => ['group1']]);
		$manager->setGroupDefault('group1', '10 GB');
		$this->userConfigData['test_user']['files']['quota'] = '100 MB';

		$this->assertEquals('100 MB', $manager->getDefaultQuotaForUser($this->user('test_user')));
	}

	public function testListQuota() {
		$manager = $this->getQuotaManager(['test_user' => ['group1']]);
		$manager->setGroupDefault('group1', '10 GB');

		$this->assertEquals(['group1' => '10 GB'], $manager->getQuotaList());

		$manager->setGroupDefault('group1', 'default');

		$this->assertEquals([], $manager->getQuotaList());
	}

	public static function groupQuotaProvider() {
		return [
			['short_id', '10G'],
			['very_long_group_id_that_doesn\'t_fit_in_the_column_without_encoding_it_somehow', '10G'],
		];
	}

	#[DataProvider('groupQuotaProvider')]
	public function testGetSetQuota(string $group, string $quota) {
		$manager = $this->getQuotaManager([]);
		$manager->setGroupDefault($group, $quota);
		$this->assertEquals($quota, $manager->getGroupDefault($group));
	}
}
