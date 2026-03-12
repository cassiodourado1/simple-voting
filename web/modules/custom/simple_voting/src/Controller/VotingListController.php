<?php

namespace Drupal\simple_voting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\simple_voting\Service\VotingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the public voting listing page.
 */
class VotingListController extends ControllerBase {

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
   * Renders a listing of all active voting questions.
   */
  public function listing(): array {
    $questions = $this->entityTypeManager()
      ->getStorage('voting_question')
      ->loadByProperties(['status' => 1]);

    if (empty($questions)) {
      return [
        '#markup' => $this->t('Nenhuma votação disponível no momento.'),
      ];
    }

    $userId = (int) $this->currentUser()->id();

    $items = [];
    foreach ($questions as $question) {
      $alreadyVoted = $this->votingManager->hasUserVoted((int) $question->id(), $userId);
      $url = Url::fromRoute('simple_voting.vote', ['voting_question' => $question->id()]);

      if ($alreadyVoted) {
        $linkLabel = $this->t('Ver resultado');
        $linkClass = ['voting-vote-link', 'voting-vote-link--voted'];
      }
      else {
        $linkLabel = $this->t('Votar');
        $linkClass = ['voting-vote-link'];
      }

      $items[] = [
        '#type'       => 'container',
        '#attributes' => ['class' => ['voting-question-item']],
        'title' => [
          '#markup' => '<span class="voting-question-title">' . htmlspecialchars($question->label(), ENT_QUOTES, 'UTF-8') . '</span>',
        ],
        'link' => [
          '#type'       => 'link',
          '#title'      => $linkLabel,
          '#url'        => $url,
          '#attributes' => ['class' => $linkClass],
        ],
      ];
    }

    return [
      '#type'       => 'container',
      '#attributes' => ['class' => ['voting-list']],
      '#cache'      => [
        'contexts' => ['user'],
      ],
      'heading' => [
        '#markup' => '<h2 class="voting-list-title">' . $this->t('Votações disponíveis') . '</h2>',
      ],
      'items' => $items,
    ];
  }

}
