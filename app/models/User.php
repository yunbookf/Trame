<?php
declare (strict_types = 1);

namespace T\Model;

interface USER_MODEL_CONFIG {

    const MODEL_TABLE = 'user_accounts';
    const MODEL_PRIMARY_KEY = 'id';

    const __FILED_MAP_TABLE = [
        'id' => 'id',
        'account' => 'login_name',
        'email' => 'email',
        'passwordHash' => 'password_hash',
        'passwordSecret' => 'password_secret',
        'createAt' => 'create_date',
        'updateAt' => 'last_access_date',
        'loginAt' => 'last_login_date',
        'status' => 'status'
    ];
}

/**
 * @property int $id
 * @property string $email
 * @property string $account
 * @property string $passwordHash
 * @property string $passwordSecret
 * @property int $createAt
 * @property int $updateAt
 * @property int $loginAt
 * @property int $status
 */
class User extends IORModel implements USER_MODEL_CONFIG {
}

/**
 * @method \T\Model\User get(array $conds)
 *     根据用户 ID 获取用户对象
 *
 */
class UserFactory extends IORModelFactory implements USER_MODEL_CONFIG {

    const MODEL_CLASS = User::class;
}
