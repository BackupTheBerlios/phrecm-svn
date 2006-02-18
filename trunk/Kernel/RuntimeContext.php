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
 * Runtime Context
 *
 * Provides kernel observable and manages extensions
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

class Kernel_RuntimeContext extends Kernel_Observable
{

    public function initalizeExtension($extension)
    {
    }    

    public function boot()
    {
        $this->notifyObservers('onBoot');
    }

    public function start()
    {
        $this->notifyObservers('onStart');
    }

    public function stop()
    {
        $this->notifyObservers('onStop');
    }

    public function shutdown()
    {
        $this->notifyObservers('onShutdown');
    }    

}

?>
