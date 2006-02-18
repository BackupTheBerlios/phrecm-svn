<?php
/**
 * Phrecm - PHP REST CMS
 * Copyright (C) 2006 Maximilian Gass
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You can find a copy of the GNU General Public License in
 * Documentation/LICENSE or at {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @package Kernel
 *
 * @subpackage Kernel_Tests
 *
 * @copyright (C) 2006 Maximilian Gass
 *
 * @author Maximilian Gass <maximilian.gass@arcor.de>
 *
 * @license http://www.gnu.org/licenses/gpl.html GPL
 *
 * @version $Revision$
 *
 */

require_once dirname(__FILE__) . '/../../Tools/InitTests.php';

/**
 *
 * Config Test
 *
 * Asserts:
 *
 * - Singleton instances are the same
 * 
 * - Get calls observers with arguments and returns value
 *
 * @package Kernel
 *
 * @subpackage Kernel_Tests
 *
 * @copyright (C) 2006 Maximilian Gass
 *
 * @author Maximilian Gass <maximilian.gass@arcor.de>
 *
 * @license http://www.gnu.org/licenses/gpl.html GPL
 *
 * @version $Revision$
 *
 */

class Kernel_Tests_ConfigTest extends PHPUnit2_Framework_TestCase
{

    private $config;
    private $observer;

    public function setUp()
    {
        $this->config = Kernel_Config::getInstance();
        $this->observer = $this->getMock('Kernel_Tests_Observer_OnSetOnGet', array('onSet', 'onGet'));
        $this->config->registerObserver($this->observer);
    }

    public function tearDown()
    {
        $this->config->unregisterObserver($this->observer);
    }    

    public function testSingleton()
    {
        $this->assertSame(Kernel_Config::getInstance(), Kernel_Config::getInstance());
    }    

    public function testGet()
    {
        $this->observer->expects(self::once())->method('onGet')->with(self::identicalTo('key'))->will(self::returnValue('value'));
        
        $this->assertSame('value', $this->config->key);
    }    

    public function testSet()
    {
        $this->observer->expects(self::once())->method('onSet')->with(self::identicalTo('key'), self::identicalTo('value'));

        $this->config->key = 'value';
    }
}
?>
