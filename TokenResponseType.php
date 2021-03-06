<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\ImplicitGrant;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use function Safe\sprintf;

final class TokenResponseType implements ResponseType
{
    private AccessTokenRepository $accessTokenRepository;

    private int $accessTokenLifetime;

    public function __construct(AccessTokenRepository $accessTokenRepository, int $accessTokenLifetime)
    {
        $this->accessTokenRepository = $accessTokenRepository;
        $this->accessTokenLifetime = $accessTokenLifetime;
    }

    public function associatedGrantTypes(): array
    {
        return ['implicit'];
    }

    public function name(): string
    {
        return 'token';
    }

    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    public function preProcess(AuthorizationRequest $authorization): void
    {
        // Nothing to do
    }

    public function process(AuthorizationRequest $authorization, TokenType $tokenType): void
    {
        $additionalInformation = $tokenType->getAdditionalInformation();
        $accessToken = $this->accessTokenRepository->create(
            $authorization->getClient()->getClientId(),
            $authorization->getUserAccount()->getUserAccountId(),
            new \DateTimeImmutable(sprintf('now +%d seconds', $this->accessTokenLifetime)),
            new DataBag($additionalInformation),
            $authorization->getMetadata(),
            null
        );
        $this->accessTokenRepository->save($accessToken);

        foreach ($accessToken->getResponseData() as $k => $v) {
            $authorization->setResponseParameter($k, $v);
        }
    }
}
