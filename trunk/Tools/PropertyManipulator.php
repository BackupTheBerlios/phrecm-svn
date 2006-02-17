<?php
/**
Property Manipulator for PHP 5
(C) 2006 Maximilian Gass

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/

class PropertyManipulatorMethods
{

    public function getProperty($property)
    {
        return $this->$property;
    }

    public function setProperty($property, $value)
    {
        $this->$property = $value;
    }

    public static function getStaticProperty($property)
    {
        return self::$$property;
    }

    public static function setStaticProperty($property, $value)
    {
        self::$$property = $value;
    }

}

class PropertyManipulator
{

    public static function addMethods($class)
    {
        foreach(get_class_methods('PropertyManipulatorMethods') as $method)
        {
            runkit_method_copy($class, $method, 'PropertyManipulatorMethods');
        }
    }

}
