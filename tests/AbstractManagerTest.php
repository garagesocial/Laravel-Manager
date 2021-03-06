<?php

/**
 * This file is part of Laravel Manager by Graham Campbell.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at http://bit.ly/UWsjkb.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace GrahamCampbell\Tests\Manager;

use Mockery;
use GrahamCampbell\Manager\AbstractManager;
use GrahamCampbell\TestBench\AbstractTestCase;

/**
 * This is the abstract manager test class.
 *
 * @author    Graham Campbell <graham@mineuk.com>
 * @copyright 2014 Graham Campbell
 * @license   <https://github.com/GrahamCampbell/Laravel-Manager/blob/master/LICENSE.md> Apache 2.0
 */
class AbstractManagerTest extends AbstractTestCase
{
    public function testConnectionName()
    {
        $config = array('driver' => 'manager');

        $manager = $this->getConfigManager($config);

        $this->assertEquals($manager->getConnections(), array());

        $return = $manager->connection('example');

        $this->assertInstanceOf('GrahamCampbell\Tests\Manager\ExampleClass', $return);

        $this->assertEquals($return->getName(), 'example');

        $this->assertEquals($return->getDriver(), 'manager');

        $this->assertArrayHasKey('example', $manager->getConnections());

        $return = $manager->reconnect('example');

        $this->assertInstanceOf('GrahamCampbell\Tests\Manager\ExampleClass', $return);

        $this->assertEquals($return->getName(), 'example');

        $this->assertEquals($return->getDriver(), 'manager');

        $this->assertArrayHasKey('example', $manager->getConnections());

        $manager = $this->getManager();

        $manager->disconnect('example');

        $this->assertEquals($manager->getConnections(), array());
    }

    public function testConnectionNull()
    {
        $config = array('driver' => 'manager');

        $manager = $this->getConfigManager($config);

        $manager->getConfig()->shouldReceive('get')->twice()
            ->with('graham-campbell/manager::default')->andReturn('example');

        $this->assertEquals($manager->getConnections(), array());

        $return = $manager->connection();

        $this->assertInstanceOf('GrahamCampbell\Tests\Manager\ExampleClass', $return);

        $this->assertEquals($return->getName(), 'example');

        $this->assertEquals($return->getDriver(), 'manager');

        $this->assertArrayHasKey('example', $manager->getConnections());

        $return = $manager->reconnect();

        $this->assertInstanceOf('GrahamCampbell\Tests\Manager\ExampleClass', $return);

        $this->assertEquals($return->getName(), 'example');

        $this->assertEquals($return->getDriver(), 'manager');

        $this->assertArrayHasKey('example', $manager->getConnections());

        $manager = $this->getManager();

        $manager->getConfig()->shouldReceive('get')->once()
            ->with('graham-campbell/manager::default')->andReturn('example');

        $manager->disconnect();

        $this->assertEquals($manager->getConnections(), array());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConnectionError()
    {
        $manager = $this->getManager();

        $config = array('driver' => 'error');

        $manager->getConfig()->shouldReceive('get')->once()
            ->with('graham-campbell/manager::connections')->andReturn(array('example' => $config));

        $this->assertEquals($manager->getConnections(), array());

        $manager->connection('error');
    }

    public function testGetDefaultConnection()
    {
        $manager = $this->getManager();

        $manager->getConfig()->shouldReceive('get')->once()
            ->with('graham-campbell/manager::default')->andReturn('example');

        $return = $manager->getDefaultConnection();

        $this->assertEquals($return, 'example');
    }

    public function testSetDefaultConnection()
    {
        $manager = $this->getManager();

        $manager->getConfig()->shouldReceive('set')->once()
            ->with('graham-campbell/manager::default', 'new');

        $manager->setDefaultConnection('new');
    }

    public function testExtendName()
    {
        $manager = $this->getManager();

        $manager->getConfig()->shouldReceive('get')->once()
            ->with('graham-campbell/manager::connections')->andReturn(array('foo' => array('driver' => 'hello')));

        $manager->extend('foo', function (array $config) {
            return new FooClass($config['name'], $config['driver']);
        });

        $this->assertEquals($manager->getConnections(), array());

        $return = $manager->connection('foo');

        $this->assertInstanceOf('GrahamCampbell\Tests\Manager\FooClass', $return);

        $this->assertEquals($return->getName(), 'foo');

        $this->assertEquals($return->getDriver(), 'hello');

        $this->assertArrayHasKey('foo', $manager->getConnections());
    }

    public function testExtendDriver()
    {
        $manager = $this->getManager();

        $manager->getConfig()->shouldReceive('get')->once()
            ->with('graham-campbell/manager::connections')->andReturn(array('qwerty' => array('driver' => 'bar')));

        $manager->extend('bar', function (array $config) {
            return new BarClass($config['name'], $config['driver']);
        });

        $this->assertEquals($manager->getConnections(), array());

        $return = $manager->connection('qwerty');

        $this->assertInstanceOf('GrahamCampbell\Tests\Manager\BarClass', $return);

        $this->assertEquals($return->getName(), 'qwerty');

        $this->assertEquals($return->getDriver(), 'bar');

        $this->assertArrayHasKey('qwerty', $manager->getConnections());
    }

    public function testCall()
    {
        $config = array('driver' => 'manager');

        $manager = $this->getManager();

        $manager->getConfig()->shouldReceive('get')->once()
            ->with('graham-campbell/manager::default')->andReturn('example');

        $manager->getConfig()->shouldReceive('get')->once()
            ->with('graham-campbell/manager::connections')->andReturn(array('example' => $config));

        $this->assertEquals($manager->getConnections(), array());

        $return = $manager->getName();

        $this->assertEquals($return, 'example');

        $this->assertArrayHasKey('example', $manager->getConnections());
    }

    protected function getManager()
    {
        $repo = Mockery::mock('Illuminate\Config\Repository');

        return new ExampleManager($repo);
    }

    protected function getConfigManager(array $config)
    {
        $manager = $this->getManager();

        $manager->getConfig()->shouldReceive('get')->twice()
            ->with('graham-campbell/manager::connections')->andReturn(array('example' => $config));

        return $manager;
    }
}

class ExampleManager extends AbstractManager
{
    /**
     * Create the connection instance.
     *
     * @param array $config
     *
     * @return string
     */
    protected function createConnection(array $config)
    {
        return new ExampleClass($config['name'], $config['driver']);
    }

    /**
     * Get the configuration name.
     *
     * @return string
     */
    protected function getConfigName()
    {
        return 'graham-campbell/manager';
    }
}

abstract class AbstractClass
{
    protected $name;
    protected $driver;

    public function __construct($name, $driver)
    {
        $this->name = $name;
        $this->driver = $driver;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDriver()
    {
        return $this->driver;
    }
}

class ExampleClass extends AbstractClass
{
    //
}


class FooClass extends AbstractClass
{
    //
}

class BarClass extends AbstractClass
{
    //
}
