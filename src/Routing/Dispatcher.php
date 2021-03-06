<?php

namespace Phat\Routing;

use Phat\Http\Exception\NotFoundException;
use Phat\Http\Request;
use Phat\Http\Response;
use Phat\Routing\Exception\DispatchException;

/**
 * The Dispatcher is responsible for calling the right controller and action given a Request.
 */
class Dispatcher
{
    /**
     * Takes the Request attributes to call the right controller and action with the right parameters.
     *
     * @param Request $request
     *
     * @throws DispatchException
     * @throws NotFoundException
     */
    public static function dispatch(Request $request)
    {
        $controller = self::loadController($request);
        $action = $request->action;

        if (!empty($request->prefix)) {
            $action = $request->prefix.'_'.$request->action;
        }

        if (!method_exists($controller, $action)) {
            throw new NotFoundException("The controller '$request->controller' has no method '$action'.");
        }

        // Everything is fine, time to do some Controller action !
        $controller->beforeAction();
        $response = call_user_func_array(array($controller, $action), $request->parameters);
        if (empty($response) || !($response instanceof Response)) {
            throw new DispatchException('Every Controller actions must return an object of type Response or a sub-class of Response.');
        }
        $controller->afterAction();

        // Send response to client
        $response->send();
    }

    /**
     * Parses the Request to extract the right controller class.
     *
     * @param Request $request
     *
     * @return mixed
     *
     * @throws DispatchException
     * @throws NotFoundException
     */
    private static function loadController(Request $request)
    {
        $ctrlName = $request->controller;

        if (false === strstr($ctrlName, '\\')) {
            $ctrlName = 'App\Controller\\'.ucfirst($ctrlName).'Controller';
        }

        if (!class_exists($ctrlName)) {
            throw new NotFoundException("The controller '$ctrlName' hasn't been found. Please make sure the namespace is included in the name and the class does exist");
        }
        if (!is_subclass_of($ctrlName, 'Phat\Controller\ControllerInterface')) {
            throw new DispatchException('Controllers must implement Phat\\Controller\\ControllerInterface.');
        }

        return new $ctrlName($request);
    }
}
