<?php
declare (strict_types = 1);

namespace T\Action;

class APIGetUserInfo extends IAPIAction {
    
    public function main(array $args) {

        if (1 == 1) {
            $this->responseData('Oh yeah', [
                'name' => 'Yubo',
                'gender' => 'Male',
                'age' => '33'
            ]);
        } else {
            $this->responseFailure('Failed to find yubo', 123);
        }
    }
}
