<?php
declare(strict_types = 1);

namespace T\Action;

use \T\HTTP as http, \T\Service\Logger;

/**
 * @property \T\HTTP\Request $request
 *     The request controlling object
 * @property \T\HTTP\Response $response
 *     The response controlling object
 */
abstract class IAction {

    public function __get(string $name) {

        switch ($name) {
        case 'request':

            return $this->request = new http\Request();

        case 'response':

            return $this->response = new http\Response();

        default:

            throw new \T\Msg\InvalidInvoke('Non-existent property "' . $name . '".');
        }
    }

    public function __isset(string $name) {

        switch ($name) {
        case 'request':
        case 'response':

            return true;

        default:

            return false;
        }
    }

    public function __invoke(array $args) {

        try {

            $this->main($args);

        } catch (\T\Core\IMessage $e) {

            $e->handle($this->request, $this->response);

        } catch (\PDOException $e) {

            Logger::write('sql.error', Logger::FETAL_ERROR, $e->__toString());

        } catch (\Exception $e) {

            Logger::write('bugs', Logger::FETAL_ERROR, $e->__toString());
        }

    }

    abstract public function main(array $args): int;

}
