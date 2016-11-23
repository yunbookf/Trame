<?php
declare(strict_types = 1);

namespace T\Action;

use \T\HTTP as http, \T\Service\Logger;

/**
 * @property \T\HTTP\Request $request
 *     The request controlling object
 * @property \T\HTTP\Response $response
 *     The response controlling object
 * @property \T\TDBI\IDBConnection $db
 *     The default database connection object
 * @property \T\KVCache\IConnection $cache
 *     The default cache connection object
 */
abstract class IAction {

    public function __construct() { }

    public function __get(string $name) {

        switch ($name) {
        case 'request':

            return $this->request = new http\Request();

        case 'response':

            return $this->response = new http\Response();

        case 'cache':

            return $this->cache = \T\Service\KVCache::get(\T\Links\CACHE_DEFAULT);

        case 'db':

            return $this->db = \T\Service\Database::get(\T\Links\DATABASE_DEFAULT);

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
