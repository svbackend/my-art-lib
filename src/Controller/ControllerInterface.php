<?php

namespace App\Controller;


interface ControllerInterface
{
    // For correct autowiring in controller's methods
    /* config/services.yaml:
    _instanceof:
        App\Controller\ControllerInterface:
            tags: ['controller.service_arguments']
     */
}