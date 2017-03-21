<?php
/**
 * Created by PhpStorm.
 * User: EinsteinCarrey
 * Date: 3/20/2017
 * Time: 12:40 PM
 */


    if(!function_exists('load_this_controller'))
    {
        function load_this_controller($name)
        {
            $filename = realpath(__dir__ . '/../controllers/'.$name.'.php');

            if(file_exists($filename))
            {
                require_once $filename;

                $class = ucfirst($name);

                if(class_exists($class))
                {
                    $ci =& get_instance();

                    if(!isset($ci->{$name.'_'}))
                    {
                        $ci->{$name.'_'} = new $class();
                    }
                }
            }
        }
    }