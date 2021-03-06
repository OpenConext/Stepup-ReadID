<?php

declare(strict_types=1);

/**
 * Copyright 2017 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace StepupReadId\Infrastructure\Controller;

use Psr\Log\LoggerInterface;
use StepupReadId\Application\PendingSession\RegisterPendingSessionCommand;
use StepupReadId\Application\ReadySession\CreateReadySessionCommand;
use StepupReadId\Domain\ReadySession\Model\ReadySession;
use StepupReadId\Domain\ReadySession\Model\ReadySessionTTL;
use Surfnet\GsspBundle\Saml\ResponseContextInterface;
use Surfnet\GsspBundle\Service\StateHandlerInterface;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use function sprintf;

/**
 * @Route("/saml/sso", name="readid_saml_sso", methods={"GET"})
 */
final class SingleSignOnController extends AbstractController
{
    use HandleTrait;

    /** @var LoggerInterface */
    private $logger;
    /** @var StateHandlerInterface */
    private $stateHandler;
    /** @var ResponseContextInterface */
    private $responseContext;
    /** @var string */
    private $readIdOpaqueId;

    public function __construct(
        StateHandlerInterface $stateHandler,
        LoggerInterface $logger,
        MessageBusInterface $messageBus,
        ResponseContextInterface $responseContext,
        string $readIdOpaqueId
    ) {
        $this->stateHandler    = $stateHandler;
        $this->logger          = $logger;
        $this->messageBus      = $messageBus;
        $this->responseContext = $responseContext;
        $this->readIdOpaqueId  = $readIdOpaqueId;
    }

    public function __invoke(Request $request, ReceivedAuthnRequest $authnRequest): Response
    {
        $this->logger->notice('Received sso request');

        // If we already have a request, we clear the current state.
        if ($this->responseContext->hasRequest()) {
            $this->logger->warning('There is already state present, clear previous state');
            $this->stateHandler->invalidate();
        }

        $this->stateHandler->saveAuthenticationRequest(
            $authnRequest,
            $this->getRelayStateFromRequest($request)
        );

        $this->logger->info(sprintf(
            'AuthnRequest stored in state'
        ));

        $this->registerNewReadySession();

        $this->logger->notice(sprintf(
            'Redirect user to the application authentication route'
        ));

        return $this->redirectToRoute('readid_authentication');
    }

    private function getRelayStateFromRequest(Request $request): string
    {
        return $request->get(AuthnRequest::PARAMETER_RELAY_STATE, '');
    }

    private function registerNewReadySession(): void
    {
        $readySession = $this->requestNewReadySession();

        $this->logger->info(sprintf(
            'ReadySession stored in state'
        ));

        $this->messageBus->dispatch(
            new RegisterPendingSessionCommand(
                $readySession->id()->value(),
                $readySession->timestamp()->value()
            )
        );

        $this->logger->info(sprintf(
            'Pending session stored in cache'
        ));
    }

    private function requestNewReadySession(): ReadySession
    {
        return $this->handle(
            new CreateReadySessionCommand(
                $this->readIdOpaqueId,
                ReadySessionTTL::MINIMUM_TTL
            )
        );
    }
}
