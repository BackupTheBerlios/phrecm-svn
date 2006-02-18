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
 * @copyright (C) 2006 Maximilian Gass
 *
 * @author Maximilian Gass <maximilian.gass@arcor.de>
 *
 * @license http://www.gnu.org/licenses/gpl.html GPL
 *
 * @version $Revision$
 *
 */

/**
 *
 * Base class for Observables
 *
 * Allows Observers to register and get notified
 *
 * @package Kernel
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

abstract class Kernel_Observable
{

    /**
     *
     * Registered observers
     *
     * @var SplObjectStorage
     *
     */
    private $observers;

    /**
     *
     * Constructor
     *
     */
    public function __construct()
    {
        $this->observers = new SplObjectStorage;
    }    

    /**
     *
     * Register an observer
     *
     * @param Observer $observer Observer
     *
     * @return void
     *
     */
    public function registerObserver(Kernel_Observer $observer)
    {
        $this->observers->attach($observer);
    }

    /**
     *
     * Unregister an observer
     *
     * @param Observer $observer Observer
     *
     * @return void
     *
     */
    public function unregisterObserver(Kernel_Observer $observer)
    {
        $this->observers->detach($observer);
    }    

    /**
     *
     * Notify observers
     *
     * (All other arguments are passed to the Observers)
     *
     * If one of the Observers returns something that evaluates
     * to TRUE, execution is stopped and the value is returned.
     *
     * @param string $event Event ("onBoot" for example)
     *
     * @return mixed Return value of the stopping Observer or FALSE
     *
     */
    protected function notifyObservers($event)
    {
        $args = func_get_args();
        array_shift($args);

        foreach($this->observers as $observer)
        {
            if (method_exists($observer, $event))
            {
                if ($ret = call_user_func_array(array($observer, $event), $args))
                {
                    return $ret;
                }
            }
        }

        return FALSE;
    }

}

?>
