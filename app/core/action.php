<?php
declare(strict_types = 1);

namespace T\Action;

use \T\HTTP as http, \T\Service as service;

/**
 * @property \T\HTTP\Request $request
 *     HTTP 请求的描述对象（首次调用时分配）
 * @property \T\HTTP\Response $response
 *     HTTP 请求的响应对象（首次调用时分配）
 * @property \T\TDBI\IDBConnection $db
 *     默认的数据库链接对象（首次调用时分配）
 * @property \T\TDBI\IDBConnection $dbWriter
 *     默认的写专用数据库链接对象（首次调用时分配）
 * @property \T\TDBI\IDBConnection $dbReader
 *     默认的读专用数据库链接对象（首次调用时分配）
 * @property \T\KVCache\IConnection $cache
 *     默认的缓存链接对象（首次调用时分配）
 */
abstract class IAction {

    use \T\Core\TDelayInitializer;

    /**
     * You should call this constructor to get default DI.
     * 
     * Or ignore this if you wanna customize the default DI.
     */
    public function __construct() { 

        $this->di = [
            'request' => function() {
                return new http\Request();
            },
            'response' => function() {
                return new http\Response();
            },
            'cache' => function() {
                return \T\Service\KVCache::get(\T\Links\CACHE_DEFAULT);
            },
            'db' => function() {
                return \T\Service\Database::get(\T\Links\DATABASE_DEFAULT);
            },
            'dbReader' => function() {
                return \T\Service\Database::get(\T\Links\DATABASE_DEFAULT_READ);
            },
            'dbWriter' => function() {
                return \T\Service\Database::get(\T\Links\DATABASE_DEFAULT_WRITE);
            }
        ];
    }

    public function __invoke(array $args) {

        try {

            $this->main($args);

        } catch (\T\Msg\IMessage $e) {

            $e->handle($this->request, $this->response);

        } catch (\PDOException $e) {

            service\Logger::write('sql.error', service\Logger::FETAL_ERROR, $e->__toString());

        } catch (\Exception $e) {

            service\Logger::write('bugs', service\Logger::FETAL_ERROR, $e->__toString());
        }

    }

    /**
     * Action 的处理器入口方法。
     * @param array $args
     * @return int
     */
    abstract public function main(array $args): int;

}
