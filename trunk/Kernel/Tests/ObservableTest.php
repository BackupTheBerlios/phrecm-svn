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
 * docs/LICENSE or at {@link http://www.gnu.org/licenses/gpl.html}
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
 * Observable Test
 *
 * Asserts:
 *
 * - Observers are called
 *
 * - Arguments are passed to Observers
 *
 * - Execution is stopped if a value == TRUE is returned
 *
 * - Observers might leave out handlers
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
class Kernel_Tests_ObservableTest extends PHPUnit2_Framework_TestCase
{

    private $observable;

    public function setUp()
    {
        $this->observable = new Kernel_Tests_Observable;
    }

    public function testCallsObservers()
    {
        $observer1 = $this->getMock('Kernel_Tests_Observer_OnTest', array('onTest'));
        $observer1->expects(self::once())->method('onTest');
        $observer2 = $this->getMock('Kernel_Tests_Observer_OnTest');
        $observer2->expects(self::once())->method('onTest');

        $this->observable->registerObserver($observer1);
        $this->observable->registerObserver($observer2);

        $this->observable->notifyObservers('onTest');
    }

    public function testPassesArgs()
    {
        $observer1 = $this->getMock('Kernel_Tests_Observer_OnTest');
        $observer1->expects(self::once())->method('onTest')->with(self::equalTo('arg1'), self::equalTo('arg2'));
        $observer2 = $this->getMock('Kernel_Tests_Observer_OnTest');
        $observer2->expects(self::once())->method('onTest')->with(self::equalTo('arg1'), self::equalTo('arg2'));

        $this->observable->registerObserver($observer1);
        $this->observable->registerObserver($observer2);

        $this->observable->notifyObservers('onTest', 'arg1', 'arg2');
    }

    public function testStopsExecution()
    {
        $observer1 = $this->getMock('Kernel_Tests_Observer_OnTest');
        $observer1->expects(self::once())->method('onTest')->will(self::returnValue('VALUE'));

        $observer2 = $this->getMock('Kernel_Tests_Observer_OnTest');
        $observer2->expects(self::never())->method('onTest');

        $this->observable->registerObserver($observer1);
        $this->observable->registerObserver($observer2);

        $this->assertEquals('VALUE', $this->observable->notifyObservers('onTest'));
    }

    public function testWorksWithoutHandler()
    {
        $observer = $this->getMock('Kernel_Tests_Observer_Empty');
        $this->observable->registerObserver($observer);
        $this->observable->notifyObservers('onTest');
    }

}
?>
