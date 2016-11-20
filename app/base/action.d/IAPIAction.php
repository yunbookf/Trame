<?php
declare (strict_types = 1);

namespace T\Action;

abstract class IAPIAction extends IAction {

    public function responseData(string $msg, $data): int {

        echo json_encode([
            'status' => [
                'code' => 0,
                'detail' => $msg
            ],
            'data' => $data
        ]);

        return 0;
    }

    public function responseSuccess(string $msg): int {

        echo json_encode([
            'status' => [
                'code' => 0,
                'detail' => $msg
            ]
        ]);

        return 0;
    }

    public function responseFailure(string $msg, int $errorCode): int {

        echo json_encode([
            'status' => [
                'code' => $errorCode,
                'detail' => $msg
            ]
        ]);

        return 0;
    }
}
