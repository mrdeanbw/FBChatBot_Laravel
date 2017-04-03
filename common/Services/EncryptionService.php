<?php namespace Common\Services;

/**
 * @see     https://gist.github.com/joashp/a1ae9cb30fa533f4ad94
 * Class EncryptionService
 * @package Common\Services
 */
class EncryptionService
{

    const METHOD = 'AES-256-CBC';
    const SECRET_KEY = 'mdmLz1HlvHZU3aJ8LqMsbENgnFklUI0T';
    const SECRET_IV = 'cmIwlpCA7bd2QVMsL96jk4UdlwXS25eb';

    /** @var  EncryptionService */
    private static $instance;
    protected $key;
    protected $iv;

    /**
     * EncryptionService constructor.
     */
    private function __construct()
    {
        $this->key = hash('sha256', self::SECRET_KEY);
        $this->iv = substr(hash('sha256', self::SECRET_IV), 0, 16);
    }

    /**
     * @return EncryptionService
     */
    public static function Instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $string
     * @return string
     */
    public function encrypt($string)
    {
        return base64_encode(openssl_encrypt($string, self::METHOD, $this->key, 0, $this->iv));
    }

    /**
     * @param string $string
     * @return string
     */
    public function decrypt($string)
    {
        return openssl_decrypt(base64_decode($string), self::METHOD, $this->key, 0, $this->iv);
    }
}
