<?php

namespace Gov2lib;

use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

/**
 * eSign client for document signing
 */
class eSignClient
{
    private string $USER;
    private string $PASS;
    public string $BASE_URI = 'http://esign.bkn.go.id/api/sign/';
    private Client $client;
    public array $errors = [];
    public ?array $error = null;

    /**
     * Initialize eSign client
     */
    public function __construct(string $username = '', string $password = ''): void
    {
        global $config;

        if (!$username) {
            $username = (string)($config->esign->username ?? '');
        }

        if (!$password) {
            $password = (string)($config->esign->password ?? '');
        }

        $this->USER = $username;
        $this->PASS = $password;

        $this->client = new Client([
            'verify' => false,
            'base_uri' => $this->BASE_URI,
            'auth' => [$this->USER, $this->PASS],
        ]);
    }

    /**
     * Sign document with QR code
     *
     * @param string $filename Path to PDF file
     * @param string $nik NIK of signer
     * @param string $phrase Passphrase for signer
     * @param string $linkQR QR code link
     * @param int $width QR width in pixels
     * @param int $height QR height in pixels
     * @param string $tag_koordinat Coordinate tag
     * @return array
     */
    public function sign(
        string $filename,
        string $nik,
        string $phrase,
        string $linkQR,
        int $width = 70,
        int $height = 70,
        string $tag_koordinat = '#'
    ): array {
        if (!file_exists($filename)) {
            $error = [
                'status_code' => 404,
                'error' => "File {$filename} tidak ditemukan"
            ];

            array_push($this->errors, $error);
            $this->error = $error;
            return $error;
        }

        $options = [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filename, 'r')
                ],
                [
                    'name' => 'nik',
                    'contents' => $nik,
                ],
                [
                    'name' => 'passphrase',
                    'contents' => $phrase,
                ],
                [
                    'name' => 'tampilan',
                    'contents' => 'visible',
                ],
                [
                    'name' => 'linkQR',
                    'contents' => $linkQR,
                ],
                [
                    'name' => 'width',
                    'contents' => $width,
                ],
                [
                    'name' => 'height',
                    'contents' => $height,
                ],
                [
                    'name' => 'tag_koordinat',
                    'contents' => $tag_koordinat,
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

        return $result;
    }

    /**
     * Check user status
     */
    public function cekStatusUser(string $nik): array
    {
        try {
            $response = $this->client->get("/api/user/status/{$nik}");
            $response_code = $response->getStatusCode();

            if ($response_code == 200) {
                $result = $this->decodeResponse($response);
            }
        } catch (ClientException $e) {
            $this->exceptionHandler($e);
            $result = $this->error ?? [];
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

        return $result ?? [];
    }

    /**
     * Check and create directory if not exists
     */
    private function ckDir(string $filename): ?array
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
     * Download signed document
     *
     * @param string $id_dokumen Document ID
     * @param string $save_to Save path
     * @return array
     */
    public function download(string $id_dokumen, string $save_to = ''): array
    {
        $tmpDir = sys_get_temp_dir();

        if (!$save_to) {
            $filename = $tmpDir . DIRECTORY_SEPARATOR . $id_dokumen . '.pdf';
        } else {
            $basename = basename($save_to);

            if (strpos($basename, '.') === false) {
                $error = [
                    'status_code' => 400,
                    'error' => "Parameter 'save_to' tidak valid. {$save_to} tidak mengandung {filename}.pdf"
                ];

                array_push($this->errors, $error);
                $this->error = $error;
                return $error;
            } else {
                $filename = $save_to;

                if (!is_writable($save_to)) {
                    $error = [
                        'status_code' => 403,
                        'error' => "File {$save_to} tidak writable"
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
                    'status_code' => $response_code,
                    'filename' => $filename
                ];
            }
        } catch (ClientException $e) {
            $this->exceptionHandler($e);
            $result = $this->error ?? [];
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

        return $result ?? [];
    }

    /**
     * Verify signed document
     */
    public function verify(string $filename): array
    {
        if (!file_exists($filename)) {
            $error = [
                'status_code' => 404,
                'error' => "File {$filename} tidak ditemukan"
            ];

            array_push($this->errors, $error);
            $this->error = $error;
            return $error;
        }

        $options = [
            'multipart' => [
                [
                    'name' => 'signed_file',
                    'contents' => fopen($filename, 'r')
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
            $result = $this->error ?? [];
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

        return $result ?? [];
    }

    /**
     * Sign document with visible QR code
     */
    private function visibleQR(array $options, string $filename): array
    {
        global $config, $self;
        $IS_KEUANGAN = str_contains($_SERVER['SERVER_NAME'] ?? '', 'keuangan');

        $folder = (string)($config->esign->folder ?? '');
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
            if ($error) {
                return $error;
            }
        }

        $error = $this->ckDir($folder_app);
        if ($error) {
            return $error;
        }

        $error = $this->ckDir($folder_mvc);
        if ($error) {
            return $error;
        }

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
            $result = $this->error ?? [];
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

        return $result ?? [];
    }

    /**
     * Check if there are errors
     */
    public function hasError(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Decode JSON response
     */
    private function decodeResponse(object $response): array
    {
        $stream = (string)$response->getBody();
        return json_decode($stream, 1) ?? [];
    }

    /**
     * Handle exceptions
     */
    private function exceptionHandler(object $e): void
    {
        $error = $e->getResponse()?->getBody(true) ?? '';
        $error = json_decode($error, 1) ?? [];
        $error['code'] = $e->getCode();
        $error['request'] = Psr7\Message::toString($e->getRequest());
        $error['response'] = Psr7\Message::toString($e->getResponse());
        array_push($this->errors, $error);
        $this->error = $error;
    }
}
