<?php

namespace Drupal\simple_voting\Controller\Api;

use Drupal\Core\Controller\ControllerBase;
use Drupal\simple_voting\Service\VotingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for registering votes.
 */
class VoteController extends ControllerBase {

  public function __construct(
    protected VotingManager $votingManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('simple_voting.voting_manager'),
    );
  }

  /**
   * POST /api/v1/voting/questions/{uuid}/vote
   *
   * Expected JSON body: { "option_id": 1 }
   */
  public function submit(string $uuid, Request $request): JsonResponse {
    // Parse JSON body.
    $body = json_decode($request->getContent(), TRUE);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($body['option_id'])) {
      return new JsonResponse(['error' => 'Invalid request body. Expected JSON with "option_id".'], 400);
    }

    $optionId = (int) $body['option_id'];
    if ($optionId <= 0) {
      return new JsonResponse(['error' => 'Invalid option_id.'], 400);
    }

    $question = $this->votingManager->loadQuestionByUuid($uuid);
    if (!$question) {
      return new JsonResponse(['error' => 'Question not found.'], 404);
    }

    $userId = (int) $this->currentUser()->id();

    $result = $this->votingManager->castVote(
      (int) $question->id(),
      $optionId,
      $userId,
      'api'
    );

    $statusCode = $result['success'] ? 201 : 422;
    return new JsonResponse(['message' => $result['message']], $statusCode);
  }

}
