<?php
// tests/WebTestClient.php

class WebTestClient {
    private string $baseUrl;
    private string $cookieFile;
    private ?string $lastContent = null;
    private int $lastStatusCode = 0;

    public function __construct(string $baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'esg_cookie_');
    }

    public function __destruct() {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function resetSession(): void {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'esg_cookie_');
    }

    public function get(string $path, array $query = []): string {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if (!empty($query)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }
        return $this->request('GET', $url);
    }

    public function post(string $path, array $data = [], array $files = []): string {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        return $this->request('POST', $url, $data, $files);
    }

    private function request(string $method, string $url, array $data = [], array $files = []): string {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ESG-SMP-ScenarioTestRunner/1.0');

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($files)) {
                $postData = $data;
                foreach ($files as $name => $filePath) {
                    $postData[$name] = new CURLFile($filePath);
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        $this->lastContent = curl_exec($ch);
        $this->lastStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        usleep(80000); // 80ms throttle to prevent shared hosting connection flood

        return (string)$this->lastContent;
    }

    public function getStatusCode(): int {
        return $this->lastStatusCode;
    }

    public function getContent(): ?string {
        return $this->lastContent;
    }

    public function extractCsrfToken(): ?string {
        if (!$this->lastContent) return null;
        if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/i', $this->lastContent, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function login(string $username, string $password): bool {
        $this->get('index.php?route=login');
        $csrf = $this->extractCsrfToken();
        $this->post('index.php?route=login', [
            'csrf_token' => $csrf,
            'username' => $username,
            'password' => $password,
        ]);
        return ($this->lastStatusCode === 200 && strpos($this->lastContent, '登出') !== false);
    }
}
