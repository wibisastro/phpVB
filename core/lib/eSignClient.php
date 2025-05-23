<?php namespace Gov2lib;


use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

class eSignClient
{
    private $USER;
    private $PASS;
    public $BASE_URI = 'http://esign.bkn.go.id/api/sign/';

    private $client;
    public $errors = [];
    public $error = null;

    public function __construct ($username = '', $password = '')
    {
        global $config;

        if (!$username) {
            $username = (string) $config->esign->username;
        }
        
        if (!$password) {
            $password = (string) $config->esign->password;
        }

        $this->USER = $username;
        $this->PASS = $password;

        $this->client = new Client([
            'verify'    => false,
            'base_uri'  => $this->BASE_URI,
            'auth'      => [$this->USER, $this->PASS],
            // 'debug'     => 'true'
        ]);
    }

    /**
     * Sign method wrapper. Saat ini baru hanya implemen tipe visible 
     * dengan QR menggunakan tag_koordinat. 
     * 
     * @param string $filename path/to/file.pdf
     * @param string $nik nik penandatangan
     * @param string $phrase passphrase penandatangan
     * @param string $linkQR
     * @param int $width
     * @param int $height
     * @param string $tag_koordinat
     * @return array
     */
    public function sign ($filename, $nik, $phrase, $linkQR, $width = 70, $height = 70, $tag_koordinat = '#')
    {

        if (!file_exists($filename)) {
            $error = [
                'status_code'   => 404,
                'error'         => "File {$filename} tidak ditemukan"
            ];
            
            array_push($this->errors, $error);
            $this->error = $error;
            return $error;
        }

        $options = [
            'multipart' => [
                [
                    'name'      => 'file',
                    'contents'  => fopen($filename, 'r')
                ],[
                    'name'      => 'nik',
                    'contents'  => $nik,
                ],[
                    'name'      => 'passphrase',
                    'contents'  => $phrase,
                ],[
                    'name'      => 'tampilan',
                    'contents'  => 'visible',
                ],[
                    'name'      => 'linkQR',
                    'contents'  => $linkQR,
                ],[
                    'name'      => 'width',
                    'contents'  => $width,
                ],[
                    'name'      => 'height',
                    'contents'  => $height,
                ],[
                    'name'      => 'tag_koordinat',
                    'contents'  => $tag_koordinat,
                ],
            ]
        ];

        $status_user = $this->cekStatusUser($nik);

        if ($status_user['status_code'] == 1111) {
            $result = $this->visibleQR($options, $filename);
        } else {
            array_push($this->errors, $status_user);
            $this->error = $status_user;
            $result = $status_user;
        }

        // TODO : IMPLEMENT keempat jenis eSign.
        
        return $result;
    }

    public function cekStatusUser ($nik)
    {
        try {
            $response = $this->client->get("/api/user/status/{$nik}");
            $response_code = $response->getStatusCode();

            if ($response_code == 200) {
               $result = $this->decodeResponse($response);
            }

        } catch (ClientException $e) {
            $this->exceptionHandler($e);
            $result = $this->error;
        } catch (ConnectException $e) {
            $error = [
                'status_code' => 404,
                'error' => 'Tidak dapat mengakses server eSign.',
                '_error' => $e->getMessage()
            ];
            array_push($this->errors, $error);
            $this->error = $error;
            $result = $error;
        }

        return $result;
    }

    /**
     * Cek dan buat direktori jika tidak ditemukan
     * 
     * @param string $filename path/to/dir
     * @return void|array array jika ada error saat membuat dir
     */
    private function ckDir($filename)
    {
        $result = null;
        if (!is_dir($filename)) {
            $created = mkdir($filename);

            if (!$created) {
                $error = [
                    'status_code' => 500,
                    'error' => 'Gagal membuat folder ' . $filename
                ];
                array_push($this->errors, $error);
                $this->error = $error;
                $result = $error;
            }
        }
        return $result;
    }

    /**
     * Unduh signed dokumen menggunakan id_dokumen
     * 
     * @param string $id_dokumen dari respon header ketika sign dokumen
     */
    public function download ($id_dokumen, $save_to = '')
    {
        $tmpDir = sys_get_temp_dir();

        if (!$save_to) {
            $filename = $tmpDir . DIRECTORY_SEPARATOR . $id_dokumen . '.pdf';
        } else {
            $basename = basename($save_to);

            if (strpos('.', $basename) === false) {
                $error = [
                    'status_code'   => 400,
                    'error'         => "Parameter 'save_to' tidak valid. {$save_to} tidak mengandung {filename}.pdf"
                ];

                array_push($this->errors, $error);
                $this->error = $error;
                return $error;
            } else {
                $filename = $save_to;

                if (!is_writable($save_to)) {
                    $error = [
                        'status_code'   => 403,
                        'error'         => "File {$save_to} tidak writable"
                    ];
                    
                    array_push($this->errors, $error);
                    $this->error = $error;
                    return $error;
                }
            }
        }

        $options = ['sink' => $filename];

        try {
            $response = $this->client->get("download/{$id_dokumen}", $options);
            $response_code = $response->getStatusCode();

            if ($response_code == 200) {
                $result = [
                    'status_code'   => $response_code,
                    'filename'      => $filename
                ];
            }

        } catch (ClientException $e) {
            $this->exceptionHandler($e);
            $result = $this->error;
        } catch (ConnectException $e) {
            $error = [
                'status_code' => 404,
                'error' => 'Tidak dapat mengakses server eSign.',
                '_error' => $e->getMessage()
            ];
            array_push($this->errors, $error);
            $this->error = $error;
            $result = $error;
        }

        return $result;
    }

    public function verify ($filename)
    {
        if (!file_exists($filename)) {
            $error = [
                'status_code'   => 404,
                'error'         => "File {$filename} tidak ditemukan"
            ];
            
            array_push($this->errors, $error);
            $this->error = $error;
            return $error;
        }

        $options = [
            'multipart' => [
                [
                    'name'      => 'signed_file',
                    'contents'  => fopen($filename, 'r')
                ]
            ]
        ];

        try {
            $response = $this->client->post('verify', $options);
            $response_code = $response->getStatusCode();

            if ($response_code == 200) {
                $result = $this->decodeResponse($response);
            }

        } catch (ClientException $e) {
            $this->exceptionHandler($e);
            $result = $this->error;
        } catch (ConnectException $e) {
            $error = [
                'status_code' => 404,
                'error' => 'Tidak dapat mengakses server eSign.',
                '_error' => $e->getMessage()
            ];
            array_push($this->errors, $error);
            $this->error = $error;
            $result = $error;
        }

        return $result;
    }

    private function invisible ()
    {

    }

    /**
     * eSign dokumen dengan QR
     * 
     * @param array $options Guzzle multipart https://docs.guzzlephp.org/en/6.5/request-options.html#multipart
     * @param string $filename {app}-{mvc}-{row_id}.pdf
     * @return array
     */
    private function visibleQR ($options, $filename)
    {
        global $config, $self;
        $IS_KEUANGAN = strpos($_SERVER[SERVER_NAME], 'keuangan') !== false;

        $folder = (string) $config->esign->folder;
        if ($IS_KEUANGAN) {
            $portal = $self->dsn;
        }

        $basename = basename($filename);
        $exp = explode('-', $basename);
        $app = $exp[0];
        $mvc = $exp[1];

        if ($IS_KEUANGAN) {
            $folder_portal = $folder . DIRECTORY_SEPARATOR . $portal;
            $folder_app = $folder_portal . DIRECTORY_SEPARATOR . $app;
        } else {
            $folder_app = $folder . DIRECTORY_SEPARATOR . $app;
        }

        $folder_mvc = $folder_app . DIRECTORY_SEPARATOR . $mvc;
        $filename = $folder_mvc . DIRECTORY_SEPARATOR . $basename;

        if ($IS_KEUANGAN) {
            $error = $this->ckDir($folder_portal);
            if ($error) {return $error;}
        }

        $error = $this->ckDir($folder_app);
        if ($error) {return $error;}
        $error = $this->ckDir($folder_mvc);
        if ($error) {return $error;}

        $options['sink'] = $filename;

        try {
            $response = $this->client->post('pdf', $options);
            $response_code = $response->getStatusCode();

            if ($response_code == 200) {
                $result = [
                    'id_dokumen' => $response->getHeader('id_dokumen')[0],
                    'signing_time' => $response->getHeader('signing_time')[0],
                    'status_code' => $response,
                    'filename' => $basename,
                    'path' => $filename
                ];
            }

        } catch (ClientException $e) {
            $this->exceptionHandler($e);
            $result = $this->error;
        } catch (ConnectException $e) {
            $error = [
                'status_code' => 404,
                'error' => 'Tidak dapat mengakses server eSign.',
                '_error' => $e->getMessage()
            ];
            array_push($this->errors, $error);
            $this->error = $error;
            $result = $error;
        }
        
        return $result;
    }

    private function visibleTTD ()
    {

    }

    /**
     * @return bool
     */
    public function hasError ()
    {
        return count($this->errors) > 0 ? true : false;
    }

    /**
     * Decode response
     * 
     * @param Psr\Http\Message\ResponseInterface $response
     * @return array|null
     */
    private function decodeResponse (object $response) 
    {
        $stream = (string) $response->getBody();
        return json_decode($stream, 1);
    }

    /**
     * @param ClientException $e
     * @return void
     */
    private function exceptionHandler(object $e) {
        // var_dump($e);exit;
        $error = $e->getResponse()->getBody(true);
        $error = json_decode($error, 1);
        $error['code'] = $e->getCode();
        $error['request'] = Psr7\Message::toString($e->getRequest()); // full error request string, including response body.
        $error['response'] = Psr7\Message::toString($e->getResponse()); // full error response string, including response body.
        array_push($this->errors, $error);
        $this->error = $error;
    }
}
?>