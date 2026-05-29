<?php
/**
 * 幸福小厨 🏠 授权验证系统
 * 域名绑定授权 — 每个站点唯一授权码
 */

class License
{
    private const SALT = 'HappyKitchen2024!@#SecretKey_好吃好吃';
    private const CONFIG_FILE = 'config/license.php';

    private string $domain;
    private string $configPath;

    public function __construct(string $configPath = null)
    {
        $this->domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $this->configPath = $configPath ?: __DIR__ . '/../' . self::CONFIG_FILE;
    }

    /**
     * 生成授权码
     * @param string $domain 域名
     * @return string 格式: XXXXX-XXXXX-XXXXX-XXXXX-XXXXX
     */
    public static function generate(string $domain): string
    {
        $hash = hash_hmac('sha256', strtolower(trim($domain)), self::SALT);
        $raw = strtoupper(substr($hash, 0, 25));
        return chunk_split($raw, 5, '-');
    }

    /**
     * 验证授权
     * @param string $licenseKey 授权码
     * @param string $domain 域名（可选，默认当前）
     * @return bool
     */
    public static function verify(string $licenseKey, string $domain = null): bool
    {
        $domain = $domain ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $expected = self::generate($domain);
        // 去除末尾的 '-'，比较时不区分大小写
        return strtoupper(trim($licenseKey, '-')) === strtoupper(trim($expected, '-'));
    }

    /**
     * 检查当前站点是否已授权
     */
    public function isLicensed(): bool
    {
        if (!file_exists($this->configPath)) {
            return false;
        }
        $cfg = $this->load();
        if (empty($cfg['license_key']) || empty($cfg['domain'])) {
            return false;
        }
        // 域名是否匹配
        $storedDomain = strtolower(trim($cfg['domain']));
        $currentDomain = strtolower(trim($this->domain));

        if ($storedDomain !== $currentDomain) {
            return false;
        }
        // 验证授权码
        return self::verify($cfg['license_key'], $currentDomain);
    }

    /**
     * 保存授权
     */
    public function save(string $licenseKey, string $domain = null): bool
    {
        $domain = $domain ?: $this->domain;

        if (!self::verify($licenseKey, $domain)) {
            return false;
        }

        $code = "<?php\n/** 授权配置（自动生成） */\n"
              . "define('LICENSE_DOMAIN', " . var_export($domain, true) . ");\n"
              . "define('LICENSE_KEY', " . var_export($licenseKey, true) . ");\n"
              . "define('LICENSE_VERIFIED_AT', " . var_export(date('Y-m-d H:i:s'), true) . ");\n";

        $dir = dirname($this->configPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return file_put_contents($this->configPath, $code) !== false;
    }

    /**
     * 获取当前授权信息
     */
    public function info(): array
    {
        $licensed = $this->isLicensed();
        $cfg = $this->load();
        return [
            'licensed'    => $licensed,
            'domain'      => $this->domain,
            'license_key' => $licensed ? ($cfg['license_key'] ?? '') : '',
            'verified_at' => $cfg['verified_at'] ?? '',
            'expected_key' => $licensed ? '' : self::generate($this->domain),
        ];
    }

    /** 加载配置文件 */
    private function load(): array
    {
        if (!file_exists($this->configPath)) {
            return [];
        }
        $cfg = [];
        try {
            require $this->configPath;
            $cfg['domain'] = defined('LICENSE_DOMAIN') ? LICENSE_DOMAIN : '';
            $cfg['license_key'] = defined('LICENSE_KEY') ? LICENSE_KEY : '';
            $cfg['verified_at'] = defined('LICENSE_VERIFIED_AT') ? LICENSE_VERIFIED_AT : '';
        } catch (\Exception $e) {
            return [];
        }
        return $cfg;
    }
}
