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
 * @subpackage Kernel_Config
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
 * Configuration
 *
 * Manages Configuration using its Observers
 *
 * @package Kernel
 *
 * @subpackage Kernel_Config
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

class Kernel_Config extends Kernel_Observable
{

    /**
     *
     * Singleton instance
     *
     * @var Kernel_Config
     *
     */
    private static $instance;

   /**
     *
     * Get instance
     *
     * @return Kernel_Config Instance
     *
     */
    public static function getInstance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new Kernel_Config;
        }

        return self::$instance;
    }   

    /**
     *
     * Get configuration option
     *
     * @param string $key Option key
     *
     * @return string Option value
     *
     */
    public function __get($key)
    {
        return $this->notifyObservers('onGet', $key);
    }

    /**
     *
     * Set configuration option
     *
     * @param string $key Option key
     *
     * @param string $value Option value
     *
     * @return string Option value
     *
     */
    public function __set($key, $value)
    {
        $this->notifyObservers('onSet', $key, $value);
    }

}

?>
