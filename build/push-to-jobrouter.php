<?php

new JobLinkForGit($argv[1], $argv[2], $argv[3], $argv[4], $argv[5]);

class JobLinkForGit
{
    private false|CurlHandle $curl;
    private string $token;

    public function __construct(
        private readonly string $processName,
        private readonly int|string $version,
        private readonly string $jrBaseRestURL,
        private readonly string $jrUserName,
        private readonly string $jrPassword,
    )
    {
        if (!$this->processName || !$this->version || !$this->jrBaseRestURL || !$this->jrUserName || !$this->jrPassword) {
            echo "Insufficient parameters.";
            return -1;
        }

        $this->curl = curl_init();
        $this->generateTokenForUser($this->jrUserName, $this->jrPassword);
        $this->initSynchronization();
    }

    private function initSynchronization()
    {
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Jobrouter-Authorization: Bearer ' . $this->token
        ];

        $curlHandle = $this->curl;
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curlHandle, CURLOPT_URL, rtrim($this->jrBaseRestURL, '/') . "/api/rest/v2/designer/process/" . $this->processName . "/" . (int)$this->version . "/git/pull");
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_POST, 0);

        $response = curl_exec($curlHandle);
        $code = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

        if ($code !== 200) {
            echo 'An error occurred. Response code: ' . $code;
            throw new Exception($response);
        }
    }

    private function generateTokenForUser($username, $password)
    {
        $data = json_encode([
            "username" => $username,
            "password" => $password
        ]);

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $curlHandle = $this->curl;
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curlHandle, CURLOPT_URL, rtrim($this->jrBaseRestURL, '/') . "/api/rest/v2/application/tokens");
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_POST, 1);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($curlHandle);
        $code = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

        if ($code === 201) {
            $response = json_decode($response, true);
            $this->token = $response['tokens'][0];
        } else {
            echo 'An error occurred. Response code: ' . $code;
            throw new Exception($response);
        }
    }
}
