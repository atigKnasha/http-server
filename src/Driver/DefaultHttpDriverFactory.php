<?php

namespace Amp\Http\Server\Driver;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\RequestHandler;
use Psr\Log\LoggerInterface as PsrLogger;

final class DefaultHttpDriverFactory implements HttpDriverFactory
{
    private const ALPN = ["h2", "http/1.1"];

    public function __construct(
        private readonly PsrLogger $logger,
        private readonly int $streamTimeout = HttpDriver::DEFAULT_STREAM_TIMEOUT,
        private readonly int $connectionTimeout = HttpDriver::DEFAULT_CONNECTION_TIMEOUT,
        private readonly int $headerSizeLimit = HttpDriver::DEFAULT_HEADER_SIZE_LIMIT,
        private readonly int $bodySizeLimit = HttpDriver::DEFAULT_BODY_SIZE_LIMIT,
        private readonly int $streamThreshold = HttpDriver::DEFAULT_STREAM_THRESHOLD,
        private readonly array $allowedMethods = HttpDriver::DEFAULT_ALLOWED_METHODS,
        private readonly bool $allowHttp2Upgrade = false,
        private readonly bool $pushEnabled = true,
    ) {
    }

    public function createHttpDriver(
        RequestHandler $requestHandler,
        ErrorHandler $errorHandler,
        Client $client,
    ): HttpDriver {
        if ($client->getTlsInfo()?->getApplicationLayerProtocol() === "h2") {
            return new Http2Driver(
                requestHandler: $requestHandler,
                errorHandler: $errorHandler,
                logger: $this->logger,
                streamTimeout: $this->streamTimeout,
                connectionTimeout: $this->connectionTimeout,
                headerSizeLimit: $this->headerSizeLimit,
                bodySizeLimit: $this->bodySizeLimit,
                streamThreshold: $this->streamThreshold,
                allowedMethods: $this->allowedMethods,
                pushEnabled: $this->pushEnabled,
            );
        }

        return new Http1Driver(
            requestHandler: $requestHandler,
            errorHandler: $errorHandler,
            logger: $this->logger,
            connectionTimeout: $this->streamTimeout, // Intentional use of stream instead of connection timeout
            headerSizeLimit: $this->headerSizeLimit,
            bodySizeLimit: $this->bodySizeLimit,
            streamThreshold: $this->streamThreshold,
            allowedMethods: $this->allowedMethods,
            allowHttp2Upgrade: $this->allowHttp2Upgrade,
        );
    }

    public function getApplicationLayerProtocols(): array
    {
        return self::ALPN;
    }
}
