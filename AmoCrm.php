<?php

class AmoCrm
{

    private string $domain = 'https://rapilet159.amocrm.ru';

    public string $clientId = 'e7506f32-88b3-4def-9191-86ba8f6fb051';
    private string $clientSecret = '9furwbUgrZf88U7ozJCy65YplRwcSqRFqLwCrYJsadSj9PuX3oRR2u53qLAQlaoz';
    private string $redirectUri = 'http://ahiles3005.temp.swtest.ru/getCode.php';

    private string $accessToken = '';


    private string $tokenFilePath = __DIR__.'/token.json';

    public function __construct()
    {
        $tokenData = $this->_loadToken();
        if (count($tokenData) > 0) {
            $this->_updateToken($tokenData);
            $tokenData = $this->_loadToken();
            $this->accessToken = $tokenData['access_token'];
        }
    }

    private function _curl(string $link, array $data = [], $useToken = false, $usePost = true)
    {
        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $usePost ? 'POST' : 'GET');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        if ($useToken) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer '.$this->accessToken
            ]);
        }

        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную

        curl_close($curl);

        return json_decode($out, true);
    }

    public function authorize(string $code): void
    {
        $link = "{$this->domain}/oauth2/access_token";


        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ];


        $result = $this->_curl($link, $data);

        $this->_saveToken($result);
    }


    public function getUserById(int $userId): array
    {
        $link = "{$this->domain}/api/v4/users/{$userId}";
        return $this->_curl($link, [], true, false);
    }

    public function setNotice(string $text, string $entityType, int $id): void
    {
        $link = "{$this->domain}/api/v4/{$entityType}/notes";
        $data = [
            [
                'entity_id' => $id,
                'note_type' => 'common',
                'params' => [
                    'text' => $text
                ]
            ]
        ];
        $result = $this->_curl($link, $data, true);
    }

    public function addToDb(int $id, array $data, string $type)
    {
        $json = json_encode($data);
        if ($type == 'leads') {
            $sql = 'INSERT INTO leads (id, json) VALUES (:id,:json)';
            $sth = $this->_pdo()->prepare($sql);
            $sth->execute(['id' => $id, 'json' => $json]);
            $sth->fetchAll();
        } elseif ($type == 'contact') {
            $sql = 'INSERT INTO contact (id, json) VALUES (:id,:json)';
            $sth = $this->_pdo()->prepare($sql);
            $sth->execute(['id' => $id, 'json' => $json]);
            $sth->fetchAll();
        }
    }

    public function updateToDb(int $id, array $data, string $type)
    {
        $json = json_encode($data);
        if ($type == 'leads') {
            $sql = 'UPDATE leads SET json=:json WHERE id=:id';
            $sth = $this->_pdo()->prepare($sql);
            $sth->execute(['id' => $id, 'json' => $json]);
            $sth->fetchAll();
        } elseif ($type == 'contact') {
            $sql = 'UPDATE contact SET json=:json WHERE id=:id';
            $sth = $this->_pdo()->prepare($sql);
            $sth->execute(['id' => $id, 'json' => $json]);
            $sth->fetchAll();
        }
    }

    public function getFromDb(int $id, string $type): array
    {
        if ($type == 'leads') {
            $sql = 'SELECT json FROM leads WHERE id=:id';
            $sth = $this->_pdo()->prepare($sql);
            $sth->execute(['id' => $id]);
            $result = $sth->fetchAll();
        } elseif ($type == 'contact') {
            $sql = 'SELECT json FROM contact WHERE id=:id';
            $sth = $this->_pdo()->prepare($sql);
            $sth->execute(['id' => $id]);
            $result = $sth->fetchAll();
        }

        $json = $result[0]['json'] ?? null;

        if ($json !== null) {
            $result = json_decode($json, true);
            unset($result['created_at']);
            unset($result['updated_at']);
            unset($result['date_create']);
            unset($result['last_modified']);
            unset($result['created_user_id']);
            unset($result['modified_user_id']);
            return $result;
        }
        return [];
    }


    private function _pdo(): PDO
    {
        $pdo = new PDO("mysql:host=localhost;dbname=ahiles3005", 'ahiles3005', '_7U2NHBSAG56WcY5');
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        return $pdo;
    }


    private function _saveToken(array $tokenData)
    {
        $tokenData['time'] = time();
        file_put_contents($this->tokenFilePath, json_encode($tokenData));
    }


    private function _loadToken(): array
    {
        $result = [];
        if (file_exists($this->tokenFilePath)) {
            $json = file_get_contents($this->tokenFilePath);
            $result = json_decode($json, true);
        }

        return $result;
    }


    private function _updateToken(array $tokenData): void
    {
        $link = "{$this->domain}/oauth2/access_token";

        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokenData['refresh_token'],
            'redirect_uri' => $this->redirectUri,
        ];
        $time = (int) $tokenData['time'] + (int) $tokenData['expires_in'];
        if ($time > time() - 600) {
            $result = $this->_curl($link, $data);
            $this->_saveToken($result);
        }
    }

}