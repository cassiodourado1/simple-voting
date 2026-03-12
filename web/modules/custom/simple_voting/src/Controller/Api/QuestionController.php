<?php

namespace Drupal\simple_voting\Controller\Api;

use Drupal\Core\Controller\ControllerBase;
use Drupal\simple_voting\Service\VotingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for listing and retrieving voting questions.
 */
class QuestionController extends ControllerBase {

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
   * GET /api/v1/voting/questions
   *
   * Returns all active questions.
   */
  public function list(): JsonResponse {
    if (!$this->votingManager->isVotingEnabled()) {
      return new JsonResponse(['error' => 'Voting is currently disabled.'], 503);
    }

    $questions = $this->entityTypeManager()
      ->getStorage('voting_question')
      ->loadByProperties(['status' => 1]);

    $data = [];
    foreach ($questions as $question) {
      $data[] = [
        'uuid'         => $question->uuid(),
        'title'        => $question->label(),
        'show_results' => (bool) $question->get('show_results')->value,
      ];
    }

    return new JsonResponse($data);
  }

  /**
   * GET /api/v1/voting/questions/{uuid}
   *
   * Returns a single question with its options.
   */
  public function detail(string $uuid, Request $request): JsonResponse {
    if (!$this->votingManager->isVotingEnabled()) {
      return new JsonResponse(['error' => 'Voting is currently disabled.'], 503);
    }

    $question = $this->votingManager->loadQuestionByUuid($uuid);
    if (!$question || !$question->get('status')->value) {
      return new JsonResponse(['error' => 'Question not found or inactive.'], 404);
    }

    $options = $this->entityTypeManager()
      ->getStorage('voting_option')
      ->loadByProperties(['question_id' => $question->id()]);

    $optionsData = [];
    foreach ($options as $option) {
      $optionsData[] = [
        'id'          => (int) $option->id(),
        'title'       => $option->label(),
        'description' => $option->get('description')->value ?? '',
      ];
    }

    return new JsonResponse([
      'uuid'         => $question->uuid(),
      'title'        => $question->label(),
      'show_results' => (bool) $question->get('show_results')->value,
      'options'      => $optionsData,
    ]);
  }

}
