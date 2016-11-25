<?php
declare (strict_types = 1);

namespace T\Model;

class UserFactory extends ModelFactory {

    const MODEL_TABLE = 'users';
    const MODEL_PRIMARY_KEY = 'id';

    /**
     * 
     * @return \T\Model\User
     */
    public function get($id) {

        return new User();
    }
}

class User extends IModel {

    public function abc() {

        echo 123;
    }
}
