<?php

/**
 * Сервісні екшени.
 * Наприклад, генерація ліцензій
 */
class UtilsController extends ControllerBase
{

    /**
     * Генерація ліцензій
     */
    public function generateLicenseAction()
    {

        $login      = 'admin';
        $passwd     = 'HelloWorld';

        if( !isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $login &&
            !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_PW'] != $passwd )
        {
            header('WWW-Authenticate: Basic realm="Links"');
            header('HTTP/1.0 401 Unauthorized');

            die;
        }

        if ($this->request->isPost())
        {
            $data =
            [
                'title'         => $this->request->getPost('title', 'string', ''),
                'users_count'   => $this->request->getPost('users_count', 'string', ''),
                'expired_date'  => $this->request->getPost('expired_date', 'string', ''),
            ];

            // Приватний ключ для підпису
            $private_key_pem = file_get_contents('../app/config/keys/dev_private.pem');
            // Публічний ключ для перевірки
            $public_key_pem = file_get_contents('../app/config/keys/dev_public.pem');

            if ($private_key_pem === false || $public_key_pem === false)
            {
                die('No keys :(');
            }

            // Підписання
            openssl_sign(json_encode($data), $signature, $private_key_pem, OPENSSL_ALGO_SHA256);

            $data['signature'] = base64_encode($signature);
            $key = base64_encode(json_encode($data));

            $data = json_decode(base64_decode( $key ), true);

            // Перевірка підпису

            $string_to_check = json_encode(
                [
                    'title'        => $data['title'],
                    'users_count'  => $data['users_count'],
                    'expired_date' => $data['expired_date'],
                ]
            );

            $signature = base64_decode( $data['signature'] );

            $is_valid = openssl_verify($string_to_check, $signature, $public_key_pem, OPENSSL_ALGO_SHA256);

            return $this->view->setVars(
                [
                    'title'        => $data['title'],
                    'users_count'  => $data['users_count'],
                    'expired_date' => $data['expired_date'],
                    'signature'    => $key,
                    'is_valid'     => $is_valid,
                ]
            );
        }
    }
}