<?php

namespace Drupal\simple_voting\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\simple_voting\Service\VotingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Simple Voting block.
 *
 * Displays the most recently active question with its voting form.
 *
 * @Block(
 *   id = "simple_voting_block",
 *   admin_label = @Translation("Simple Voting"),
 *   category = @Translation("Simple Voting"),
 * )
 */
class VotingBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected VotingManager $votingManager,
    protected FormBuilderInterface $formBuilder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_voting.voting_manager'),
      $container->get('form_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    if (!$this->votingManager->isVotingEnabled()) {
      return ['#markup' => $this->t('Voting is currently unavailable.')];
    }

    // Load the most recent active question.
    $questions = \Drupal::entityTypeManager()
      ->getStorage('voting_question')
      ->loadByProperties(['status' => 1]);

    if (empty($questions)) {
      return ['#markup' => $this->t('No active questions available.')];
    }

    // Use the last created active question.
    $question = end($questions);

    return $this->formBuilder->getForm(
      \Drupal\simple_voting\Form\VotingVoteForm::class,
      $question
    );
  }

}
