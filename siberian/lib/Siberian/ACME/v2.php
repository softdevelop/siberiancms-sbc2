<?php

namespace Siberian\ACME;


/**
 * Class v2
 * @package Siberian\ACME
 */
class v2
{
    protected
        $directories = [
        'live' => 'https://acme-v02.api.letsencrypt.org/directory',
        'staging' => 'https://acme-staging-v02.api.letsencrypt.org/directory',
    ], $ch = null, $bits, $sha_bits, $directory, $resources, $jwk_header, $kid_header, $account_key, $thumbprint, $nonce, $mode;

    /**
     * v2 constructor.
     * @param bool $live
     * @throws \Exception
     */
    public function __construct($live = true)
    {
        $this->directory = $this->directories[$this->mode = ($live ? 'live' : 'staging')];
    }

    /**
     *
     */
    public function __destruct()
    {
        if ($this->account_key) {
            openssl_pkey_free($this->account_key);
        }
        if ($this->ch) {
            curl_close($this->ch);
        }
    }

    /**
     * @param $account_key_pem
     * @throws \Exception
     */
    public function loadAccountKey($account_key_pem)
    {
        if ($this->account_key) {
            openssl_pkey_free($this->account_key);
        }
        if (false === ($this->account_key = openssl_pkey_get_private($account_key_pem))) {
            throw new \Exception('Could not load account key: ' . $account_key_pem . ' (' . $this->get_openssl_error() . ')');
        }

        if (false === ($details = openssl_pkey_get_details($this->account_key))) {
            throw new \Exception('Could not get account key details: ' . $account_key_pem . ' (' . $this->get_openssl_error() . ')');
        }

        $this->bits = $details['bits'];
        switch ($details['type']) {
            case OPENSSL_KEYTYPE_EC:
                if (version_compare(PHP_VERSION, '7.1.0') < 0) {
                    throw new \Exception('PHP >= 7.1.0 required for EC keys !');
                }
                $this->sha_bits = ($this->bits == 521 ? 512 : $this->bits);
                $this->jwk_header = [ // JOSE Header - RFC7515
                    'alg' => 'ES' . $this->sha_bits,
                    'jwk' => [ // JSON Web Key
                        'crv' => 'P-' . $details['bits'],
                        'kty' => 'EC',
                        'x' => $this->base64url(str_pad($details['ec']['x'], ceil($this->bits / 8), "\x00", STR_PAD_LEFT)),
                        'y' => $this->base64url(str_pad($details['ec']['y'], ceil($this->bits / 8), "\x00", STR_PAD_LEFT)),
                    ],
                ];
                break;
            case OPENSSL_KEYTYPE_RSA:
                $this->sha_bits = 256;
                $this->jwk_header = [ // JOSE Header - RFC7515
                    'alg' => 'RS256',
                    'jwk' => [ // JSON Web Key
                        'e' => $this->base64url($details['rsa']['e']), // public exponent
                        'kty' => 'RSA',
                        'n' => $this->base64url($details['rsa']['n']) // public modulus
                    ],
                ];
                break;
            default:
                throw new \Exception('Unsupported key type! Must be RSA or EC key.');
                break;
        }

        $this->kid_header = [
            'alg' => $this->jwk_header['alg'],
            'kid' => null,
        ];

        $this->thumbprint = $this->base64url( // JSON Web Key (JWK) Thumbprint - RFC7638
            hash(
                'sha256',
                json_encode($this->jwk_header['jwk']),
                true
            )
        );
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getAccountID()
    {
        if (!$this->kid_header['kid']) self::getAccount();
        return $this->kid_header['kid'];
    }

    /**
     * @param $txt
     */
    public function log($txt)
    {
        //error_log($txt);


    }

    /**
     * @return string
     */
    protected function get_openssl_error()
    {
        $out = [];
        $arr = error_get_last();
        if (is_array($arr)) {
            $out[] = $arr['message'];
        }
        $out[] = openssl_error_string();
        return implode(' | ', $out);
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getAccount()
    {
        $this->log('Getting account info');
        $ret = $this->request('newAccount', ['onlyReturnExisting' => true]);
        $this->log('Account info retrieved');
        return $ret;
    }

    /**
     * @param $token
     * @return string
     */
    protected function keyAuthorization($token)
    {
        return $token . '.' . $this->thumbprint;
    }

    /**
     * @param $type
     * @param string $payload
     * @param bool $retry
     * @return array
     * @throws Exception
     */
    protected function request($type, $payload = '', $retry = false)
    {
        if (!$this->jwk_header) {
            throw new \Exception('use loadAccountKey to load an account key');
        }

        if (!$this->resources) {
            $this->log('Initializing ACME v2 ' . $this->mode . ' environment');
            $ret = $this->http_request($this->directory); // Read ACME Directory
            if (!is_array($ret['body'])) {
                throw new \Exception('Failed to read directory: ' . $this->directory);
            }
            $this->resources = $ret['body']; // store resources for later use
            $this->log('Initialized');
        }

        if (0 === stripos($type, 'http')) {
            $this->resources['_tmp'] = $type;
            $type = '_tmp';
        }

        try {
            $ret = $this->http_request($this->resources[$type], json_encode(
                $this->jws_encapsulate($type, $payload)
            ));
        } catch (Exception $e) { // retry previous request once, if replay-nonce expired/failed
            if (!$retry && $e->getType() === 'urn:ietf:params:acme:error:badNonce') {
                $this->log('Replay-Nonce expired, retrying previous request');
                return $this->request($type, $payload, true);
            }
            throw $e; // rethrow all other exceptions
        }

        if (!$this->kid_header['kid'] && $type === 'newAccount') {
            $this->kid_header['kid'] = $ret['headers']['location'];
            $this->log('AccountID: ' . $this->kid_header['kid']);
        }

        return $ret;
    }

    /**
     * @param $type
     * @param $payload
     * @param bool $is_inner_jws
     * @return array
     * @throws Exception
     */
    protected function jws_encapsulate($type, $payload, $is_inner_jws = false)
    { // RFC7515
        if ($type === 'newAccount' || $is_inner_jws) {
            $protected = $this->jwk_header;
        } else {
            $this->getAccountID();
            $protected = $this->kid_header;
        }

        if (!$is_inner_jws) {
            if (!$this->nonce) {
                $ret = $this->http_request($this->resources['newNonce'], false);
                $this->nonce = $ret['headers']['replay-nonce'];
            }
            $protected['nonce'] = $this->nonce;
        }

        $protected['url'] = $this->resources[$type];

        $protected64 = $this->base64url(json_encode($protected));
        $payload64 = $this->base64url(is_string($payload) ? $payload : json_encode($payload));

        if (false === openssl_sign(
                $protected64 . '.' . $payload64,
                $signature,
                $this->account_key,
                'SHA' . $this->sha_bits
            )) {
            throw new \Exception('Failed to sign payload !' . ' (' . $this->get_openssl_error() . ')');
        }

        return [
            'protected' => $protected64,
            'payload' => $payload64,
            'signature' => $this->base64url($this->jwk_header['alg'][0] == 'R' ? $signature : $this->asn2signature($signature, ceil($this->bits / 8))),
        ];
    }

    /**
     * @param $asn
     * @param $pad_len
     * @return string
     * @throws \Exception
     */
    private function asn2signature($asn, $pad_len)
    {
        if ($asn[0] !== "\x30") throw new \Exception('ASN.1 SEQUENCE not found !');
        $asn = substr($asn, $asn[1] === "\x81" ? 3 : 2);
        if ($asn[0] !== "\x02") throw new \Exception('ASN.1 INTEGER 1 not found !');
        $R = ltrim(substr($asn, 2, ord($asn[1])), "\x00");
        $asn = substr($asn, ord($asn[1]) + 2);
        if ($asn[0] !== "\x02") throw new \Exception('ASN.1 INTEGER 2 not found !');
        $S = ltrim(substr($asn, 2, ord($asn[1])), "\x00");
        return str_pad($R, $pad_len, "\x00", STR_PAD_LEFT) . str_pad($S, $pad_len, "\x00", STR_PAD_LEFT);
    }

    /**
     * @param $data
     * @return string
     */
    protected function base64url($data)
    { // RFC7515 - Appendix C
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param $str
     * @return mixed
     * @throws \Exception
     */
    private function json_decode($str)
    {
        $ret = json_decode($str, true);
        if ($ret === null) {
            throw new \Exception('Could not parse JSON: ' . $str);
        }
        return $ret;
    }

    /**
     * @param $url
     * @param null $data
     * @return array
     * @throws Exception
     */
    private function http_request($url, $data = null)
    {
        if ($this->ch === null) {
            if (extension_loaded('curl') && $this->ch = curl_init()) {
                $this->log('Using cURL');
            } elseif (ini_get('allow_url_fopen')) {
                $this->ch = false;
                $this->log('Using fopen wrappers');
            } else {
                throw new \Exception('Can not connect, no cURL or fopen wrappers enabled !');
            }
        }
        $method = $data === false ? 'HEAD' : ($data === null ? 'GET' : 'POST');
        $user_agent = 'ACMECert v2.3 (+https://github.com/skoerfgen/ACMECert)';
        $header = ($data === null || $data === false) ? [] : ['Content-Type: application/jose+json'];
        if ($this->ch) {
            $headers = [];
            curl_setopt_array($this->ch, [
                CURLOPT_URL => $url,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TCP_NODELAY => true,
                CURLOPT_NOBODY => $data === false,
                CURLOPT_USERAGENT => $user_agent,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$headers) {
                    $headers[] = $header;
                    return strlen($header);
                },
            ]);
            $took = microtime(true);
            $body = curl_exec($this->ch);
            $took = round(microtime(true) - $took, 2) . 's';
            if ($body === false) throw new \Exception('HTTP Request Error: ' . curl_error($this->ch));
        } else {
            $opts = [
                'http' => [
                    'header' => $header,
                    'method' => $method,
                    'user_agent' => $user_agent,
                    'ignore_errors' => true,
                    'timeout' => 60,
                    'content' => $data,
                ],
            ];
            $took = microtime(true);
            $body = file_get_contents($url, false, stream_context_create($opts));
            $took = round(microtime(true) - $took, 2) . 's';
            if ($body === false) throw new \Exception('HTTP Request Error: ' . $this->get_openssl_error());
            $headers = $http_response_header;
        }

        $headers = array_reduce( // parse http response headers into array
            array_filter($headers, function ($item) {
                return trim($item) != '';
            }),
            function ($carry, $item) use (&$code, &$status) {
                $parts = explode(':', $item, 2);
                if (count($parts) === 1) {
                    list(, $code, $status) = explode(' ', trim($item), 3);
                    $carry = [];
                } else {
                    list($k, $v) = $parts;
                    $carry[strtolower(trim($k))] = trim($v);
                }
                return $carry;
            },
            []
        );
        $this->log('  ' . $url . ' [' . $code . ' ' . $status . '] (' . $took . ')');

        if (!empty($headers['replay-nonce'])) $this->nonce = $headers['replay-nonce'];

        if (!empty($headers['content-type'])) {
            switch ($headers['content-type']) {
                case 'application/json':
                    $body = $this->json_decode($body);
                    break;
                case 'application/problem+json':
                    $body = $this->json_decode($body);
                    throw new Exception($body['type'], $body['detail'],
                        array_map(function ($subproblem) {
                            return new Exception(
                                $subproblem['type'],
                                '"' . $subproblem['identifier']['value'] . '": ' . $subproblem['detail']
                            );
                        }, isset($body['subproblems']) ? $body['subproblems'] : [])
                    );
                    break;
            }
        }

        if ($code[0] != '2') {
            throw new \Exception('Invalid HTTP-Status-Code received: ' . $code . ' [' . $status . ']: ' . $url);
        }

        $ret = [
            'code' => $code,
            'status' => $status,
            'headers' => $headers,
            'body' => $body,
        ];

        return $ret;
    }
}