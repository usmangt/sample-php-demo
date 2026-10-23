<?php
/**
 * Minimal hand-rolled OTLP tracer for Step 2.
 * Sends one span at a time to Tempo via OTLP HTTP JSON.
 * Not the official OpenTelemetry PHP SDK — intentional for this demo.
 */
class SimpleTracer
{
    private $correlationId;
    private $traceId;
    private $tempoUrl;
    private $debugLog;

    public function __construct($correlationId = null, $tempoUrl = null)
    {
        $this->correlationId = $correlationId ?: uniqid('req_', true);
        $this->traceId = $this->generateTraceId();
        // From inside Docker network; override with TEMPO_OTLP_URL if needed
        $this->tempoUrl = $tempoUrl
            ?: (getenv('TEMPO_OTLP_URL') ?: 'http://tempo:4318/v1/traces');
        $this->debugLog = '/var/log/app/traces.log';
    }

    public function getTraceId()
    {
        return $this->traceId;
    }

    public function getCorrelationId()
    {
        return $this->correlationId;
    }

    public function startSpan($name)
    {
        return [
            'trace_id' => $this->traceId,
            'span_id' => $this->generateSpanId(),
            'name' => $name,
            'correlation_id' => $this->correlationId,
            'start_time' => microtime(true),
            'attributes' => [],
        ];
    }

    public function addAttribute(&$span, $key, $value)
    {
        $span['attributes'][$key] = (string) $value;
    }

    /**
     * Finish span and POST to Tempo (synchronous — easier to debug).
     * $otelStatus: 0=UNSET, 1=OK, 2=ERROR
     */
    public function finishSpan(array &$span, array $extraAttributes = [], $otelStatus = 0)
    {
        $span['end_time'] = microtime(true);
        $span['otel_status'] = (int) $otelStatus;
        foreach ($extraAttributes as $k => $v) {
            $span['attributes'][$k] = (string) $v;
        }

        $payload = $this->buildOtlpPayload($span);
        $result = $this->sendOtlp($payload);
        $this->writeDebug($span, $payload, $result);
        return $result;
    }

    private function buildOtlpPayload(array $span)
    {
        $startNs = sprintf('%.0f', $span['start_time'] * 1e9);
        $endNs = sprintf('%.0f', $span['end_time'] * 1e9);

        $attributes = [
            [
                'key' => 'correlation_id',
                'value' => ['stringValue' => $span['correlation_id']],
            ],
        ];
        foreach ($span['attributes'] as $key => $value) {
            $attributes[] = [
                'key' => $key,
                'value' => ['stringValue' => $value],
            ];
        }

        return [
            'resourceSpans' => [
                [
                    'resource' => [
                        'attributes' => [
                            [
                                'key' => 'service.name',
                                'value' => ['stringValue' => 'php-app'],
                            ],
                            [
                                'key' => 'service.version',
                                'value' => ['stringValue' => '1.0.0'],
                            ],
                        ],
                    ],
                    'scopeSpans' => [
                        [
                            'scope' => [
                                'name' => 'simple-tracer',
                                'version' => 'step2',
                            ],
                            'spans' => [
                                [
                                    'traceId' => $span['trace_id'],
                                    'spanId' => $span['span_id'],
                                    'name' => $span['name'],
                                    'kind' => 2, // SERVER
                                    'startTimeUnixNano' => $startNs,
                                    'endTimeUnixNano' => $endNs,
                                    'attributes' => $attributes,
                                    'status' => $this->formatStatus($span),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function formatStatus(array $span)
    {
        $code = isset($span['otel_status']) ? (int) $span['otel_status'] : 0;
        if ($code === 2) {
            return [
                'code' => 2, // ERROR
                'message' => $span['attributes']['error.message'] ?? 'request error',
            ];
        }
        if ($code === 1) {
            return ['code' => 1]; // OK
        }
        return new stdClass();
    }

    private function sendOtlp(array $payload)
    {
        $json = json_encode($payload);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nContent-Length: " . strlen($json) . "\r\n",
                'content' => $json,
                'timeout' => 2,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($this->tempoUrl, false, $context);
        $httpCode = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $httpCode = (int) $m[1];
        }

        return [
            'ok' => ($httpCode === 200),
            'http_code' => $httpCode,
            'body' => $body === false ? '' : $body,
            'url' => $this->tempoUrl,
        ];
    }

    private function writeDebug(array $span, array $payload, array $result)
    {
        $entry = [
            'timestamp' => date('c'),
            'trace_id' => $span['trace_id'],
            'span_id' => $span['span_id'],
            'correlation_id' => $span['correlation_id'],
            'span_name' => $span['name'],
            'tempo_http_code' => $result['http_code'],
            'tempo_ok' => $result['ok'],
            'tempo_url' => $result['url'],
        ];
        @file_put_contents(
            $this->debugLog,
            json_encode($entry, JSON_UNESCAPED_SLASHES) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    private function generateTraceId()
    {
        return bin2hex(random_bytes(16));
    }

    private function generateSpanId()
    {
        return bin2hex(random_bytes(8));
    }
}
