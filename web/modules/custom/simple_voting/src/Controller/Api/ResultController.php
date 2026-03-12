<?php

namespace Drupal\simple_voting\Controller\Api;

use Drupal\Core\Controller\ControllerBase;
use Drupal\simple_voting\Service\VotingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * API controller for displaying voting results.
 */
class ResultController extends ControllerBase {

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
   * GET /api/v1/voting/questions/{uuid}/results
   *
   * Returns vote results respecting the question's show_results setting.
   */
  public function show(string $uuid): JsonResponse {
    $question = $this->votingManager->loadQuestionByUuid($uuid);
    if (!$question) {
      return new JsonResponse(['error' => 'Question not found.'], 404);
    }

    $results = $this->votingManager->getResults((int) $question->id());

    if (!$results['show_results']) {
      return new JsonResponse(['error' => 'Results are not available for this question.'], 403);
    }

    return new JsonResponse([
      'uuid'    => $question->uuid(),
      'title'   => $question->label(),
      'total'   => $results['total'],
      'options' => $results['options'],
    ]);
  }

}
