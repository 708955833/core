<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\tests\framework\mutex;

use yii\mutex\PgsqlMutex;
use yii\tests\framework\db\DatabaseTestCase;

/**
 * Class PgsqlMutexTest.
 *
 * @group mutex
 * @group db
 * @group pgsql
 */
class PgsqlMutexTest extends DatabaseTestCase
{
    use MutexTestTrait;

    protected $driverName = 'pgsql';

    /**
     * @return PgsqlMutex
     * @throws \yii\exceptions\InvalidConfigException
     */
    protected function createMutex()
    {
        return $this->app->createObject([
            '__class' => PgsqlMutex::class,
            'db' => $this->getConnection(),
        ]);
    }
}
