<?php

namespace Drupal\simple_voting\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_voting\Entity\VotingQuestion;
use Drupal\simple_voting\Service\VotingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Voting form displayed on the CMS voting page and in the home block.
 */
class VotingVoteForm extends FormBase {

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
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'simple_voting_vote_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\simple_voting\Entity\VotingQuestion|null $voting_question
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?VotingQuestion $voting_question = NULL): array {
    if (!$voting_question) {
      $form['message'] = ['#markup' => $this->t('No question found.')];
      return $form;
    }

    $form['#question'] = $voting_question;
    $form['question_id'] = [
      '#type'  => 'hidden',
      '#value' => $voting_question->id(),
    ];

    // Voting globally disabled.
    if (!$this->votingManager->isVotingEnabled()) {
      $form['message'] = ['#markup' => $this->t('Voting is currently unavailable.')];
      return $form;
    }

    // Question inactive.
    if (!$voting_question->get('status')->value) {
      $form['message'] = ['#markup' => $this->t('This question is no longer accepting votes.')];
      return $form;
    }

    $currentUser = \Drupal::currentUser();

    // User already voted — show results if allowed.
    if ($this->votingManager->hasUserVoted((int) $voting_question->id(), (int) $currentUser->id())) {
      return $this->buildResultsSection($form, $voting_question);
    }

    // Build options as radio buttons.
    $options = \Drupal::entityTypeManager()
      ->getStorage('voting_option')
      ->loadByProperties(['question_id' => $voting_question->id()]);

    $radioOptions = [];
    foreach ($options as $option) {
      $radioOptions[$option->id()] = $option->label();
    }

    $form['title'] = [
      '#markup' => '<h2>' . $voting_question->label() . '</h2>',
    ];

    $form['option_id'] = [
      '#type'     => 'radios',
      '#title'    => $this->t('Choose an option'),
      '#options'  => $radioOptions,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Vote'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $questionId = (int) $form_state->getValue('question_id');
    $optionId   = (int) $form_state->getValue('option_id');
    $userId     = (int) \Drupal::currentUser()->id();

    $result = $this->votingManager->castVote($questionId, $optionId, $userId, 'cms');

    if ($result['success']) {
      $this->messenger()->addStatus($this->t('Your vote has been registered!'));
    }
    else {
      $this->messenger()->addError($this->t('@msg', ['@msg' => $result['message']]));
    }
  }

  /**
   * Builds the results display section.
   */
  protected function buildResultsSection(array $form, VotingQuestion $question): array {
    $results = $this->votingManager->getResults((int) $question->id());

    $form['title'] = [
      '#markup' => '<h2>' . $question->label() . '</h2>',
    ];

    if (!$results['show_results']) {
      $form['message'] = ['#markup' => $this->t('Thank you for voting! Results are not publicly available.')];
      return $form;
    }

    $form['results'] = [
      '#theme'   => 'item_list',
      '#title'   => $this->t('Results (@total votes)', ['@total' => $results['total']]),
      '#items'   => array_map(
        fn($o) => $this->t('@title: @votes votes (@pct%)', [
          '@title' => $o['title'],
          '@votes' => $o['votes'],
          '@pct'   => $o['percentage'],
        ]),
        $results['options']
      ),
    ];

    return $form;
  }

}
