<?php
/**
 * 幸福小厨 🏠 简易 SMTP 邮件发送类
 * 无需外部依赖，使用 PHP socket 直连 SMTP 服务器
 */
class Mailer
{
    private string $host;
    private int    $port;
    private string $user;
    private string $pass;
    private string $from;
    private string $fromName;
    private bool   $ssl;
    private $socket = null;

    public function __construct(array $config)
    {
        $this->host     = $config['host']     ?? 'smtp.qq.com';
        $this->port     = (int)($config['port']     ?? 465);
        $this->user     = $config['user']     ?? '';
        $this->pass     = $config['pass']     ?? '';
        $this->from     = $config['from']     ?? $this->user;
        $this->fromName = $config['from_name'] ?? '幸福小厨';
        $this->ssl      = $this->port === 465;
    }

    /**
     * 发送邮件
     * @param string $to      收件人
     * @param string $subject 主题
     * @param string $body    HTML 内容
     */
    public function send(string $to, string $subject, string $body): bool
    {
        try {
            $this->connect();
            $this->auth();
            $this->sendMail($to, $subject, $body);
            $this->disconnect();
            return true;
        } catch (Exception $e) {
            $this->disconnect();
            throw $e;
        }
    }

    /* ---- 连接 SMTP 服务器 ---- */
    private function connect(): void
    {
        $host = $this->ssl ? 'ssl://' . $this->host : $this->host;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $this->socket = @stream_socket_client($host . ':' . $this->port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
        if (!$this->socket) {
            throw new Exception("SMTP 连接失败: $errstr ($errno)");
        }
        $this->readReply();
        $this->sendCommand("EHLO happy-kitchen");
    }

    /* ---- 认证 ---- */
    private function auth(): void
    {
        $this->sendCommand("AUTH LOGIN");
        $this->sendCommand(base64_encode($this->user));
        $this->sendCommand(base64_encode($this->pass));
    }

    /* ---- 发送邮件内容 ---- */
    private function sendMail(string $to, string $subject, string $body): void
    {
        $this->sendCommand("MAIL FROM:<{$this->from}>");
        $this->sendCommand("RCPT TO:<{$to}>");
        $this->sendCommand("DATA");

        $headers = [
            'From: ' . $this->fromName . ' <' . $this->from . '>',
            'To: <' . $to . '>',
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: HappyKitchen/1.0',
        ];
        $header = implode("\r\n", $headers) . "\r\n\r\n";
        $this->sendCommand($header . $body . "\r\n.");
    }

    /* ---- 断开连接 ---- */
    private function disconnect(): void
    {
        if ($this->socket) {
            @fwrite($this->socket, "QUIT\r\n");
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    /* ---- 发送命令并读取响应 ---- */
    private function sendCommand(string $cmd): void
    {
        if (!$this->socket) throw new Exception('SMTP 连接已断开');
        @fwrite($this->socket, $cmd . "\r\n");
        $this->readReply();
    }

    /* ---- 读取服务器响应 ---- */
    private function readReply(): void
    {
        $code = 0;
        do {
            $line = @fgets($this->socket, 512);
            if ($line === false) throw new Exception('SMTP 服务器无响应');
            $code = (int)substr($line, 0, 3);
        } while ($line[3] === '-');
        if ($code >= 400) {
            throw new Exception("SMTP 错误: $line");
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
