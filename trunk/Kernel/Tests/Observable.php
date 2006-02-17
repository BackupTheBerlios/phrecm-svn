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

/**
 *
 * Observable for testing
 *
 * Makes notifyObservers() public
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

class Kernel_Tests_Observable extends Kernel_Observable
{

    public function notifyObservers()
    {
        $args = func_get_args();
        return call_user_func_array(array('Kernel_Observable', 'notifyObservers'), $args);
    }

}

?>
